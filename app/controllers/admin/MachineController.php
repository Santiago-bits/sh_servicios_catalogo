<?php
/**
 * ARCHIVO: app/controllers/admin/MachineController.php
 * ---------------------------------------------------------------------
 * ABM de maquinaria. Hereda el grueso de ProductAdminController y
 * agrega la ficha técnica propia de las máquinas y la vinculación con
 * los repuestos compatibles.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Tag;
use App\Services\AuditService;
use Core\Auth;
use Core\Database;

class MachineController extends ProductAdminController
{
    protected string $type          = 'machine';
    protected string $routeBase     = 'maquinaria';
    protected string $uploadFolder  = 'machines';
    protected string $permission    = 'machines';
    protected string $labelSingular = 'máquina';
    protected string $labelPlural   = 'Maquinaria';

    protected function extraRules(): array
    {
        return [
            'model' => 'max:120',
            'year'  => 'integer|between:1950,2100',
        ];
    }

    /** @param array<string,mixed> $input */
    protected function saveTypeData(int $productId, array $input): void
    {
        $fuels      = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
        $conditions = ['nuevo', 'usado', 'reacondicionado'];

        (new Machine())->save($productId, [
            'model'            => $this->text($input, 'model', 120),
            'year'             => $this->int($input, 'year'),
            'serial_number'    => $this->text($input, 'serial_number', 80),
            'condition_type'   => in_array($input['condition_type'] ?? '', $conditions, true) ? $input['condition_type'] : 'usado',
            'hours'            => $this->int($input, 'hours'),
            'fuel'             => in_array($input['fuel'] ?? '', $fuels, true) ? $input['fuel'] : null,
            'engine'           => $this->text($input, 'engine', 120),
            'power_hp'         => $this->decimal($input, 'power_hp'),
            'transmission'     => $this->text($input, 'transmission', 120),
            'capacity_kg'      => $this->decimal($input, 'capacity_kg'),
            'lift_height_mm'   => $this->int($input, 'lift_height_mm'),
            'closed_height_mm' => $this->int($input, 'closed_height_mm'),
            'weight_kg'        => $this->decimal($input, 'weight_kg'),
            'length_mm'        => $this->int($input, 'length_mm'),
            'width_mm'         => $this->int($input, 'width_mm'),
            'turn_radius_mm'   => $this->int($input, 'turn_radius_mm'),
            'battery'          => $this->text($input, 'battery', 120),
            'voltage'          => $this->text($input, 'voltage', 40),
            'mast_type'        => $this->text($input, 'mast_type', 80),
            'tire_type'        => $this->text($input, 'tire_type', 80),
            'fork_size'        => $this->text($input, 'fork_size', 120),
            'location'         => $this->text($input, 'location', 160),
            'warranty'         => $this->text($input, 'warranty', 160),
        ]);

        // Repuestos compatibles seleccionados
        (new Machine())->syncParts(
            $productId,
            array_map('intval', (array) ($input['spare_parts'] ?? [])),
            array_map('intval', (array) ($input['recommended_parts'] ?? []))
        );

        // Videos: enlaces de YouTube/Vimeo del textarea (los subidos como
        // archivo se administran aparte y no se tocan acá).
        if (array_key_exists('videos', $input)) {
            $this->saveVideoLinks($productId, (string) $input['videos']);
        }
    }

    /** @return array<string,mixed> */
    protected function formExtras(?array $product): array
    {
        $productId = $product !== null ? (int) $product['id'] : 0;

        return [
            'availableParts' => Database::select(
                'SELECT p.id, p.code, p.name, c.name AS category_name
                   FROM products p LEFT JOIN categories c ON c.id = p.category_id
                  WHERE p.type = \'spare_part\' AND p.deleted_at IS NULL
                  ORDER BY c.name ASC, p.name ASC'
            ),
            'selectedParts'    => $productId > 0 ? (new Machine())->partIds($productId) : [],
            'recommendedParts' => $productId > 0
                ? array_map('intval', array_column(Database::select(
                    'SELECT spare_part_id FROM machine_spare_parts WHERE machine_id = :id AND recommended = 1',
                    ['id' => $productId]
                ), 'spare_part_id'))
                : [],
            'videos' => $productId > 0
                ? implode("\n", array_map(
                    static fn (array $v): string => $v['provider'] === 'youtube'
                        ? 'https://www.youtube.com/watch?v=' . $v['video_ref']
                        : ($v['provider'] === 'vimeo'
                            ? 'https://vimeo.com/' . $v['video_ref']
                            : (string) $v['video_ref']),
                    array_filter(
                        (new Product())->videos($productId),
                        static fn (array $v): bool => ($v['provider'] ?? '') !== 'file'
                    )
                ))
                : '',
            'fuels' => [
                ''          => 'Sin especificar',
                'electrico' => 'Eléctrico',
                'diesel'    => 'Diésel',
                'nafta'     => 'Nafta',
                'gas'       => 'Gas',
                'glp'       => 'GLP',
                'hibrido'   => 'Híbrido',
                'manual'    => 'Manual',
            ],
            'conditions' => [
                'nuevo'           => 'Nuevo',
                'usado'           => 'Usado',
                'reacondicionado' => 'Reacondicionado',
            ],
        ];
    }

    /**
     * Pasa una máquina de nueva a usada (o al revés) con un solo click,
     * sin tener que abrir el formulario completo. Si estaba "reacondicionado"
     * pasa a "usado" (nunca vuelve a nuevo con un toggle).
     */
    public function toggleCondition(string $id): void
    {
        $productId = (int) $id;
        $product   = (new Product())->find($productId);
        if ($product === null || $product['type'] !== 'machine') {
            $this->abort(404, 'Máquina inexistente.');
        }

        $current = (string) Database::scalar(
            'SELECT condition_type FROM machines WHERE product_id = :id',
            ['id' => $productId]
        );
        $new = $current === 'usado' ? 'nuevo' : 'usado';

        Database::execute('UPDATE machines SET condition_type = :c WHERE product_id = :id', ['c' => $new, 'id' => $productId]);

        AuditService::log(
            'update',
            $this->permission,
            'product',
            $productId,
            'Condición cambiada a ' . ($new === 'usado' ? 'usada' : 'nueva') . ': ' . $product['code']
        );

        $this->success('«' . $product['name'] . '» ahora figura como ' . ($new === 'usado' ? 'usada' : 'nueva') . '.');
        $this->back();
    }

    /**
     * Crea una copia idéntica de la máquina (misma ficha técnica, precio,
     * etiquetas, repuestos compatibles y características) y manda directo
     * a editarla. Las fotos y documentos NO se copian (son archivos
     * físicos propios de cada unidad) y la copia arranca sin publicar
     * hasta que se termine de ajustar lo que la distingue del original.
     */
    public function duplicate(string $id): void
    {
        $original = (new Product())->findFull((int) $id);
        if ($original === null || $original['type'] !== 'machine') {
            $this->abort(404, 'Máquina inexistente.');
        }

        $productModel = new Product();
        $originalId   = (int) $original['id'];

        $newId = Database::transaction(function () use ($productModel, $original, $originalId): int {
            $code = $productModel->nextCode('machine');

            $payload = array_merge($original, [
                'code'              => $code,
                'slug'              => $productModel->uniqueSlug($original['name'] . '-' . $code),
                'price_updated_at'  => date('Y-m-d H:i:s'),
                'views'             => 0,
                'og_image'          => null,
                'featured'          => 0,
                'active'            => 0,
                // La copia es una unidad distinta: no hereda si la original
                // ya estaba vendida/reservada, ni el SEO con el código viejo.
                'availability'      => 'disponible',
                'meta_title'        => null,
                'meta_description'  => null,
                'created_by'        => Auth::id(),
                'updated_by'        => null,
            ]);

            $newId = $productModel->create($payload);

            (new Machine())->save($newId, $original);

            // Repuestos compatibles (y los recomendados entre ellos)
            Database::execute(
                'INSERT IGNORE INTO machine_spare_parts (machine_id, spare_part_id, recommended)
                 SELECT :new, spare_part_id, recommended FROM machine_spare_parts WHERE machine_id = :orig',
                ['new' => $newId, 'orig' => $originalId]
            );

            // Etiquetas
            Database::execute(
                'INSERT IGNORE INTO product_tags (product_id, tag_id) SELECT :new, tag_id FROM product_tags WHERE product_id = :orig',
                ['new' => $newId, 'orig' => $originalId]
            );

            // Características técnicas (ficha)
            Database::execute(
                'INSERT INTO feature_values (product_id, feature_id, value_text, value_number)
                 SELECT :new, feature_id, value_text, value_number FROM feature_values WHERE product_id = :orig',
                ['new' => $newId, 'orig' => $originalId]
            );

            AuditService::log('create', $this->permission, 'product', $newId, 'Duplicado de ' . $this->labelSingular . ': ' . $original['code'] . ' → ' . $code);

            return $newId;
        });

        // Fotos y videos: se copian los archivos físicos (fuera de la
        // transacción, igual que las imágenes al crear un producto nuevo).
        $this->duplicateMedia($originalId, $newId);

        $this->success('Se creó la copia «' . $original['name'] . '». Ajustá lo que la distingue del original.');
        $this->redirect('admin/' . $this->routeBase . '/' . $newId . '/editar');
    }

    // ----------------------------------------------------------------
    // Helpers de conversión
    // ----------------------------------------------------------------

    private function text(array $input, string $key, int $max): ?string
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function int(array $input, string $key): ?int
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : (int) normalize_decimal($value);
    }

    private function decimal(array $input, string $key): ?float
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : normalize_decimal($value);
    }
}
