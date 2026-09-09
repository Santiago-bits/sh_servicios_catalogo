<?php
/**
 * ARCHIVO: app/controllers/admin/ProductAdminController.php
 * ---------------------------------------------------------------------
 * Lógica compartida por el ABM de maquinaria y el de repuestos:
 * listado, alta, edición, borrado lógico, galería y documentación.
 *
 * Las particularidades de cada tipo se resuelven en los métodos
 * abstractos que implementan MachineController y PartController.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Product;
use App\Models\Tag;
use App\Services\AuditService;
use App\Services\ImageService;
use App\Services\PriceService;
use App\Services\SettingService;
use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Uploader;

abstract class ProductAdminController extends AdminController
{
    /** 'machine' | 'spare_part' */
    protected string $type = 'machine';
    /** Segmento de URL del panel: 'maquinaria' | 'repuestos' */
    protected string $routeBase = 'maquinaria';
    /** Carpeta dentro de uploads/ */
    protected string $uploadFolder = 'machines';
    /** Prefijo de permisos: 'machines' | 'parts' */
    protected string $permission = 'machines';
    protected string $labelSingular = 'máquina';
    protected string $labelPlural = 'Maquinaria';

    /** Guarda los datos propios del tipo (machines / spare_parts). */
    abstract protected function saveTypeData(int $productId, array $input): void;

    /** Datos extra que necesita el formulario. @return array<string,mixed> */
    abstract protected function formExtras(?array $product): array;

    /** Reglas de validación adicionales. @return array<string,string> */
    protected function extraRules(): array
    {
        return [];
    }

    // =================================================================
    // Listado
    // =================================================================

    public function index(): void
    {
        $filters = [
            'q'          => Request::get('q'),
            'categoria'  => Request::get('categoria'),
            'marca'      => Request::get('marca'),
            'estado'     => Request::get('estado'),
            'activo'     => Request::get('activo'),
            'sin_precio'    => Request::get('sin_precio'),
            'sin_imagen'    => Request::get('sin_imagen'),
            'sin_categoria' => Request::get('sin_categoria'),
            'orden'         => Request::get('orden', 'nuevos'),
        ];

        $result = (new Product())->catalog(
            $this->type,
            $filters,
            max(1, Request::int('pagina', 1)),
            25,
            true                       // vista interna: incluye costo si hay permiso
        );

        // Si el usuario no puede ver costos, se quitan antes de la vista
        if (!Auth::canSeeCost()) {
            $result['data'] = array_map(
                static fn (array $p): array => PriceService::publicView($p),
                $result['data']
            );
        }

        $this->view('admin/' . ($this->type === 'machine' ? 'machines' : 'parts') . '/index', [
            'pageTitle'  => $this->labelPlural . ' · Panel',
            'adminTitle' => $this->labelPlural,
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'products'   => $result['data'],
            'filters'    => $filters,
            'categories' => (new Category())->ofType($this->type, false),
            'brands'     => (new Brand())->active(),
            'routeBase'  => $this->routeBase,
            'canSeeCost' => Auth::canSeeCost(),
        ]);
    }

    // =================================================================
    // Alta / edición
    // =================================================================

    public function create(): void
    {
        $this->renderForm(null);
    }

    public function edit(string $id): void
    {
        $product = (new Product())->findFull((int) $id);

        if ($product === null || $product['type'] !== $this->type) {
            $this->abort(404, 'El producto no existe.');
        }

        $this->renderForm($product);
    }

    private function renderForm(?array $product): void
    {
        $productModel = new Product();
        $featureModel = new Feature();
        $isEdit       = $product !== null;

        $data = array_merge([
            'pageTitle'   => ($isEdit ? 'Editar' : 'Nueva') . ' ' . $this->labelSingular . ' · Panel',
            'adminTitle'  => ($isEdit ? 'Editar ' : 'Nueva ') . $this->labelSingular,
            'robots'      => 'noindex, nofollow',
            'product'     => $product,
            'isEdit'      => $isEdit,
            'routeBase'   => $this->routeBase,
            'categories'  => (new Category())->ofType($this->type, false),
            'brands'      => (new Brand())->active(),
            'tags'        => (new Tag())->active(),
            'selectedTags'=> $isEdit ? (new Tag())->idsForProduct((int) $product['id']) : [],
            'features'    => $featureModel->forType($this->type),
            'featureValues'=> $isEdit ? $featureModel->valuesFor((int) $product['id']) : [],
            'images'      => $isEdit ? $productModel->images((int) $product['id']) : [],
            'documents'   => $isEdit ? $productModel->documents((int) $product['id'], false) : [],
            'uploadedVideos' => $isEdit
                ? array_values(array_filter(
                    $productModel->videos((int) $product['id']),
                    static fn (array $v): bool => ($v['provider'] ?? '') === 'file'
                ))
                : [],
            'imageZones'  => ImageService::ZONES,
            'nextCode'    => $isEdit ? null : $productModel->nextCode($this->type),
            'canSeeCost'  => Auth::canSeeCost(),
            'currencies'  => ['ARS' => 'Pesos (ARS)', 'USD' => 'Dólares (USD)'],
        ], $this->formExtras($product));

        $this->view('admin/' . ($this->type === 'machine' ? 'machines' : 'parts') . '/form', $data);
    }

    public function store(): void
    {
        $input = Request::all();
        $data  = $this->validateProduct($input, null);

        $productModel = new Product();

        $productId = Database::transaction(function () use ($productModel, $data, $input): int {
            $prices = $this->resolvePrices($input, null);

            $payload = array_merge($this->basePayload($data, $input, $prices), [
                'type'       => $this->type,
                'slug'       => $productModel->uniqueSlug((string) $data['name'] . '-' . $data['code']),
                'created_by' => Auth::id(),
            ]);

            $id = $productModel->create($payload);

            $this->saveTypeData($id, $input);
            $this->saveRelations($id, $input);

            AuditService::log('create', $this->permission, 'product', $id, 'Alta de ' . $this->labelSingular . ': ' . $data['code'] . ' — ' . $data['name']);

            return $id;
        });

        // Imágenes iniciales
        $files = Request::files('imagenes');
        if ($files !== []) {
            ImageService::attach($productId, $files, $this->uploadFolder, (string) Request::post('zona', ''));
        }

        $this->success(ucfirst($this->labelSingular) . ' creada correctamente.');
        $this->redirect('admin/' . $this->routeBase . '/' . $productId . '/editar');
    }

    public function update(string $id): void
    {
        $productModel = new Product();
        $product      = $productModel->findFull((int) $id);

        if ($product === null || $product['type'] !== $this->type) {
            $this->abort(404, 'El producto no existe.');
        }

        $input = Request::all();
        $data  = $this->validateProduct($input, (int) $id);

        Database::transaction(function () use ($productModel, $product, $data, $input, $id): void {
            $prices  = $this->resolvePrices($input, $product);
            $payload = $this->basePayload($data, $input, $prices);

            $payload['updated_by'] = Auth::id();

            // El slug sólo se regenera si cambió el nombre
            if ($product['name'] !== $data['name']) {
                $payload['slug'] = $productModel->uniqueSlug((string) $data['name'] . '-' . $data['code'], (int) $id);
            }

            $productModel->updateById((int) $id, $payload);

            $this->saveTypeData((int) $id, $input);
            $this->saveRelations((int) $id, $input);

            AuditService::logChanges(
                $this->permission,
                'product',
                (int) $id,
                array_intersect_key($product, $payload),
                $payload,
                'Edición de ' . $this->labelSingular . ': ' . $data['code']
            );
        });

        $files = Request::files('imagenes');
        if ($files !== []) {
            ImageService::attach((int) $id, $files, $this->uploadFolder, (string) Request::post('zona', ''));
        }

        $this->success('Cambios guardados.');
        $this->redirect('admin/' . $this->routeBase . '/' . $id . '/editar');
    }

    public function destroy(string $id): void
    {
        $productModel = new Product();
        $product      = $productModel->find((int) $id);

        if ($product === null || $product['type'] !== $this->type) {
            $this->abort(404, 'El producto no existe.');
        }

        // Borrado lógico: se conserva el historial de precios, stock y
        // cotizaciones. El borrado físico sólo si nunca se usó.
        $usedInQuotes = (int) Database::scalar(
            'SELECT COUNT(*) FROM quote_items WHERE product_id = :id',
            ['id' => (int) $id]
        ) > 0;

        // En los dos casos se borran de disco las fotos, miniaturas,
        // documentos y videos: no tiene sentido guardar archivos de un
        // producto que ya no se muestra.
        $this->purgeMedia((int) $id);

        if ($usedInQuotes) {
            $productModel->updateById((int) $id, ['active' => 0]);
            Database::execute('UPDATE products SET deleted_at = NOW() WHERE id = :id', ['id' => (int) $id]);
            $message = 'El producto se archivó (aparece en cotizaciones) y se borraron sus fotos y documentos.';
        } else {
            $productModel->deleteById((int) $id);
            $message = ucfirst($this->labelSingular) . ' eliminada correctamente.';
        }

        AuditService::log('delete', $this->permission, 'product', (int) $id, 'Baja de ' . $this->labelSingular . ': ' . $product['code']);

        $this->success($message);
        $this->redirect('admin/' . $this->routeBase);
    }

    /** Borra de disco y de la base las fotos, miniaturas, documentos y videos de un producto. */
    private function purgeMedia(int $id): void
    {
        // Fotos + miniaturas (borra archivos y filas)
        ImageService::removeAll($id);

        // Documentos: primero los archivos, después las filas
        foreach (Database::select('SELECT path FROM documents WHERE product_id = :id', ['id' => $id]) as $doc) {
            Uploader::delete((string) $doc['path']);
        }
        Database::delete('documents', 'product_id = :id', ['id' => $id]);

        // Videos: sólo los subidos como archivo tienen algo en disco
        foreach (Database::select("SELECT video_ref FROM videos WHERE product_id = :id AND provider = 'file'", ['id' => $id]) as $vid) {
            Uploader::delete((string) $vid['video_ref']);
        }
        Database::delete('videos', 'product_id = :id', ['id' => $id]);
    }

    // =================================================================
    // Galería y documentación
    // =================================================================

    public function uploadImages(string $id): void
    {
        $this->ensureExists((int) $id);

        $files = Request::files('imagenes');
        if ($files === []) {
            $this->error('No seleccionaste ninguna imagen.');
            $this->back();
        }

        $result = ImageService::attach((int) $id, $files, $this->uploadFolder, (string) Request::post('zona', ''));

        if ($result['uploaded'] > 0) {
            $this->success($result['uploaded'] . ' imagen(es) subida(s).');
            AuditService::log('upload', $this->permission, 'product', (int) $id, $result['uploaded'] . ' imagen(es) agregada(s)');
        }
        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->back();
    }

    public function setMainImage(string $id, string $imageId): void
    {
        $this->ensureExists((int) $id);
        ImageService::setMain((int) $id, (int) $imageId);

        $this->success('Imagen principal actualizada.');
        $this->back();
    }

    public function deleteImage(string $id, string $imageId): void
    {
        $this->ensureExists((int) $id);

        if (ImageService::remove((int) $id, (int) $imageId)) {
            $this->success('Imagen eliminada.');
        } else {
            $this->error('No se encontró la imagen.');
        }

        $this->back();
    }

    public function uploadDocument(string $id): void
    {
        $this->ensureExists((int) $id);

        $file = Request::file('documento');
        if ($file === null) {
            $this->error('Seleccioná un archivo.');
            $this->back();
        }

        $result = Uploader::document($file, 'documents');

        if (!$result['ok']) {
            $this->error($result['message']);
            $this->back();
        }

        $validTypes = ['manual', 'ficha_tecnica', 'certificado', 'mantenimiento', 'compatibilidad', 'otro'];
        $docType    = (string) Request::post('tipo', 'otro');

        Database::insert('documents', [
            'product_id' => (int) $id,
            'title'      => mb_substr((string) (Request::post('titulo') ?: $file['name']), 0, 180),
            'doc_type'   => in_array($docType, $validTypes, true) ? $docType : 'otro',
            'path'       => $result['path'],
            'mime'       => $result['mime'],
            'size_bytes' => $result['size'],
            'public'     => Request::flag('publico', true),
        ]);

        AuditService::log('upload', $this->permission, 'product', (int) $id, 'Documento agregado');

        $this->success('Documento agregado.');
        $this->back();
    }

    public function deleteDocument(string $id, string $docId): void
    {
        $this->ensureExists((int) $id);

        $document = Database::selectOne(
            'SELECT * FROM documents WHERE id = :doc AND product_id = :id',
            ['doc' => (int) $docId, 'id' => (int) $id]
        );

        if ($document !== null) {
            Uploader::delete((string) $document['path']);
            Database::delete('documents', 'id = :doc', ['doc' => (int) $docId]);
            $this->success('Documento eliminado.');
        }

        $this->back();
    }

    // =================================================================
    // Video subido como archivo (MP4/WebM/MOV)
    // =================================================================

    public function uploadVideo(string $id): void
    {
        $this->ensureExists((int) $id);

        $file = Request::file('video');
        if ($file === null) {
            $this->error('Seleccioná un archivo de video.');
            $this->back();
        }

        $result = Uploader::video($file, 'videos');
        if (!$result['ok']) {
            $this->error($result['message']);
            $this->back();
        }

        $order = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 FROM videos WHERE product_id = :id',
            ['id' => (int) $id]
        );

        Database::insert('videos', [
            'product_id' => (int) $id,
            'title'      => mb_substr((string) (Request::post('titulo') ?: 'Video'), 0, 180),
            'provider'   => 'file',
            'video_ref'  => $result['path'],
            'sort_order' => $order,
        ]);

        AuditService::log('upload', $this->permission, 'product', (int) $id, 'Video agregado');

        $this->success('Video agregado.');
        $this->back();
    }

    public function deleteVideo(string $id, string $videoId): void
    {
        $this->ensureExists((int) $id);

        $video = Database::selectOne(
            'SELECT * FROM videos WHERE id = :vid AND product_id = :id',
            ['vid' => (int) $videoId, 'id' => (int) $id]
        );

        if ($video !== null) {
            if (($video['provider'] ?? '') === 'file') {
                Uploader::delete((string) $video['video_ref']);
            }
            Database::delete('videos', 'id = :vid', ['vid' => (int) $videoId]);
            $this->success('Video eliminado.');
        }

        $this->back();
    }

    /**
     * Guarda los videos de YouTube/Vimeo del textarea (una URL por línea).
     * NO toca los videos subidos como archivo (provider = 'file'): esos se
     * administran uno por uno con su propio botón.
     */
    protected function saveVideoLinks(int $productId, string $raw): void
    {
        Database::delete('videos', "product_id = :id AND provider <> 'file'", ['id' => $productId]);

        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $order = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 FROM videos WHERE product_id = :id',
            ['id' => $productId]
        );

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $provider = 'youtube';
            $ref      = $line;

            if (preg_match('~(?:youtube\.com/watch\?(?:[^\s]*&)?v=|youtu\.be/|youtube\.com/(?:embed|shorts|live|v)/)([A-Za-z0-9_-]{6,20})~i', $line, $m)) {
                $ref = $m[1];
            } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $line, $m)) {
                $provider = 'vimeo';
                $ref      = $m[1];
            } elseif (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $line)) {
                continue; // no parece un video válido
            }

            Database::insert('videos', [
                'product_id' => $productId,
                'title'      => 'Ver el producto en video',
                'provider'   => $provider,
                'video_ref'  => $ref,
                'sort_order' => $order++,
            ]);
        }
    }

    // =================================================================
    // Auxiliares
    // =================================================================

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    protected function validateProduct(array $input, ?int $ignoreId): array
    {
        $unique = 'unique:products,code' . ($ignoreId !== null ? ',' . $ignoreId : '');

        $rules = array_merge([
            'code'              => 'required|string|max:60|' . $unique,
            'name'              => 'required|string|min:3|max:200',
            'category_id'       => 'integer',
            'brand_id'          => 'integer',
            'short_description' => 'max:400',
            'description'       => 'max:20000',
            'currency'          => 'in:ARS,USD',
            'availability'      => 'in:disponible,reservada,vendida,mantenimiento,consultar',
            'meta_title'        => 'max:180',
            'meta_description'  => 'max:300',
        ], $this->extraRules());

        return $this->validate($input, $rules, [
            'code'              => 'código',
            'name'              => 'nombre',
            'category_id'       => 'categoría',
            'brand_id'          => 'marca',
            'short_description' => 'descripción corta',
            'description'       => 'descripción',
            'availability'      => 'estado',
        ]);
    }

    /**
     * Resuelve costo/ganancia/precio respetando permisos: quien no puede
     * ver costos, tampoco puede modificarlos.
     *
     * @param array<string,mixed>      $input
     * @param array<string,mixed>|null $current
     * @return array<string,float>
     */
    protected function resolvePrices(array $input, ?array $current): array
    {
        // Costo y ganancia sólo se tocan si el formulario los envía
        // (modo avanzado). Si no, se conserva lo que ya tenía el producto.
        $cost   = (float) ($current['cost_price'] ?? 0);
        $profit = (float) ($current['profit_percent'] ?? 0);

        if (Auth::canSeeCost() && array_key_exists('cost_price', $input)) {
            $cost = normalize_decimal((string) $input['cost_price']);
        }
        if (Auth::canSeeCost() && array_key_exists('profit_percent', $input)) {
            $profit = normalize_decimal((string) $input['profit_percent']);
        }

        $final = array_key_exists('final_price', $input) && (Auth::canSeeCost() || Auth::can('prices.edit'))
            ? normalize_decimal((string) $input['final_price'])
            : (float) ($current['final_price'] ?? 0);

        // Si se cargó un precio final, la ganancia se recalcula a partir de él.
        return PriceService::calculate($cost, $final > 0 ? 0.0 : $profit, $final > 0 ? $final : null);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $input
     * @param array<string,float> $prices
     * @return array<string,mixed>
     */
    protected function basePayload(array $data, array $input, array $prices): array
    {
        $payload = [
            'code'              => mb_strtoupper(trim((string) $data['code'])),
            'name'              => $data['name'],
            'category_id'       => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'brand_id'          => !empty($data['brand_id']) ? (int) $data['brand_id'] : null,
            'short_description' => $data['short_description'] ?? null,
            'description'       => html_to_text((string) ($data['description'] ?? '')) ?: null,
            'cost_price'        => $prices['cost_price'],
            'profit_percent'    => $prices['profit_percent'],
            'profit_amount'     => $prices['profit_amount'],
            'final_price'       => $prices['final_price'],
            'currency'          => in_array($input['currency'] ?? 'ARS', ['ARS', 'USD'], true) ? $input['currency'] : 'ARS',
            'price_visible'     => Request::flag('price_visible', true),
            'price_updated_at'  => date('Y-m-d H:i:s'),
            'availability'      => $data['availability'] ?? 'disponible',
            'featured'          => Request::flag('featured'),
            'is_new'            => Request::flag('is_new'),
            'active'            => Request::flag('active'),
        ];

        // Oferta: sólo si el formulario la trae.
        if (array_key_exists('offer_price', $input)) {
            $offer = normalize_decimal((string) $input['offer_price']);
            $payload['offer_price'] = $offer > 0 ? $offer : null;
            $payload['is_offer']    = ($offer > 0 && Request::bool('is_offer')) ? 1 : 0;
        }

        // SEO: sólo se toca si el formulario lo trae (el de máquinas ya no lo tiene).
        if (array_key_exists('meta_title', $input)) {
            $payload['meta_title'] = trim((string) $input['meta_title']) !== '' ? $data['meta_title'] : null;
        }
        if (array_key_exists('meta_description', $input)) {
            $payload['meta_description'] = trim((string) $input['meta_description']) !== '' ? $data['meta_description'] : null;
        }

        return $payload;
    }

    /** Etiquetas y características técnicas. @param array<string,mixed> $input */
    protected function saveRelations(int $productId, array $input): void
    {
        if (array_key_exists('tags', $input)) {
            (new Tag())->syncProduct($productId, array_map('intval', (array) $input['tags']));
        }

        $features = $input['features'] ?? null;
        if (is_array($features)) {
            (new Feature())->saveValues($productId, $features);
        }
    }

    protected function ensureExists(int $id): void
    {
        $product = (new Product())->find($id);

        if ($product === null || $product['type'] !== $this->type) {
            $this->abort(404, 'El producto no existe.');
        }
    }
}
