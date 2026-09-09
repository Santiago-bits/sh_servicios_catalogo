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

        // La marca nueva va al final de la grilla.
        $id = $model->create(array_merge($data, [
            'slug'       => $model->uniqueSlug((string) $data['name']),
            'logo'       => $this->uploadLogo(),
            'sort_order' => $model->nextSortOrder(),
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

        // Sólo bloquean los productos vivos: los borrados que todavía apuntan
        // a la marca no cuentan (la FK products.brand_id es ON DELETE SET NULL,
        // así que al borrar la marca esos quedan sin marca sin romper nada).
        $inUse = (int) Database::scalar(
            'SELECT COUNT(*) FROM products WHERE brand_id = :id AND deleted_at IS NULL',
            ['id' => (int) $id]
        );

        if ($inUse > 0) {
            $this->error('No se puede eliminar: ' . $inUse . ' producto(s) usan esta marca. Cambiáles la marca o eliminá esos productos primero.');
            $this->back();
        }

        Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'brand', (int) $id, 'Marca eliminada: ' . $brand['name']);

        $this->success('Marca eliminada.');
        $this->back();
    }

    /**
     * Versión simplificada: una marca es sólo nombre y logo.
     * El orden se asigna solo (al final) y toda marca queda activa.
     *
     * @return array<string,mixed>
     */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name' => 'required|string|min:2|max:120',
        ], ['name' => 'nombre']);

        return [
            'name'   => $data['name'],
            'active' => 1,
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
