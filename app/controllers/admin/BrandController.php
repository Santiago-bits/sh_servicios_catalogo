<?php
/**
 * ARCHIVO: app/controllers/admin/BrandController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Brand;
use App\Services\AuditService;
use Core\Database;
use Core\Request;
use Core\Uploader;

class BrandController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/brands/index', [
            'pageTitle'  => 'Marcas · Panel',
            'adminTitle' => 'Marcas',
            'robots'     => 'noindex, nofollow',
            'brands'     => (new Brand())->withCounts(),
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new Brand();

        // Si no se indicó un orden, la marca nueva va al final de la grilla.
        if ((int) $data['sort_order'] <= 0) {
            $data['sort_order'] = $model->nextSortOrder();
        }

        $id = $model->create(array_merge($data, [
            'slug' => $model->uniqueSlug((string) $data['name']),
            'logo' => $this->uploadLogo(),
        ]));

        AuditService::log('create', 'catalog', 'brand', $id, 'Marca creada: ' . $data['name']);

        $this->success('Marca creada.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model = new Brand();
        $brand = $model->find((int) $id);

        if ($brand === null) {
            $this->abort(404, 'La marca no existe.');
        }

        $data = $this->validateInput();

        if ($brand['name'] !== $data['name']) {
            $data['slug'] = $model->uniqueSlug((string) $data['name'], (int) $id);
        }

        $logo = $this->uploadLogo();
        if ($logo !== null) {
            Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
            $data['logo'] = $logo;
        }

        $model->updateById((int) $id, $data);

        AuditService::logChanges('catalog', 'brand', (int) $id, $brand, $data, 'Marca editada: ' . $data['name']);

        $this->success('Marca actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model = new Brand();
        $brand = $model->find((int) $id);

        if ($brand === null) {
            $this->abort(404, 'La marca no existe.');
        }

        $inUse = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE brand_id = :id', ['id' => (int) $id]) > 0;

        if ($inUse) {
            $this->error('No se puede eliminar: hay productos con esta marca. Desactivala en su lugar.');
            $this->back();
        }

        Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'brand', (int) $id, 'Marca eliminada: ' . $brand['name']);

        $this->success('Marca eliminada.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'        => 'required|string|min:2|max:120',
            'description' => 'max:2000',
            'website'     => 'max:255',
            'country'     => 'max:80',
            'sort_order'  => 'integer',
        ], ['name' => 'nombre']);

        $website = trim((string) ($data['website'] ?? ''));
        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        return [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'website'     => $website !== '' ? $website : null,
            'country'     => $data['country'] ?? null,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'featured'    => Request::bool('featured') ? 1 : 0,
            'active'      => Request::bool('active', true) ? 1 : 0,
        ];
    }

    private function uploadLogo(): ?string
    {
        $file = Request::file('logo');
        if ($file === null) {
            return null;
        }

        $result = Uploader::image($file, 'brands', 600, false);

        if (!$result['ok']) {
            $this->error($result['message']);
            return null;
        }

        return $result['path'];
    }
}
