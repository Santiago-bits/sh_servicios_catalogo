<?php
/**
 * ARCHIVO: app/controllers/admin/CategoryController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Services\AuditService;
use Core\Request;
use Core\Uploader;

class CategoryController extends AdminController
{
    public function index(): void
    {
        $model = new Category();

        $this->view('admin/categories/index', [
            'pageTitle'  => 'Categorías · Panel',
            'adminTitle' => 'Categorías',
            'robots'     => 'noindex, nofollow',
            'categories' => $model->withParent(),
            'types'      => ['machine' => 'Maquinaria', 'spare_part' => 'Repuestos', 'service' => 'Servicios'],
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new Category();

        $id = $model->create([
            'type'             => $data['type'],
            'parent_id'        => $data['parent_id'],
            'name'             => $data['name'],
            'slug'             => $model->uniqueSlug($data['name']),
            'description'      => $data['description'],
            'icon'             => $data['icon'],
            'image'            => $this->uploadImage(),
            'meta_title'       => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'sort_order'       => $data['sort_order'],
            'featured'         => $data['featured'],
            'active'           => $data['active'],
        ]);

        AuditService::log('create', 'catalog', 'category', $id, 'Categoría creada: ' . $data['name']);

        $this->success('Categoría creada.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model    = new Category();
        $category = $model->find((int) $id);

        if ($category === null) {
            $this->abort(404, 'La categoría no existe.');
        }

        $data = $this->validateInput();

        // Una categoría no puede ser su propio padre
        if ($data['parent_id'] === (int) $id) {
            $data['parent_id'] = null;
        }

        $payload = [
            'type'             => $data['type'],
            'parent_id'        => $data['parent_id'],
            'name'             => $data['name'],
            'description'      => $data['description'],
            'icon'             => $data['icon'],
            'meta_title'       => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'sort_order'       => $data['sort_order'],
            'featured'         => $data['featured'],
            'active'           => $data['active'],
        ];

        if ($category['name'] !== $data['name']) {
            $payload['slug'] = $model->uniqueSlug($data['name'], (int) $id);
        }

        $image = $this->uploadImage();
        if ($image !== null) {
            Uploader::delete($category['image'] !== null ? (string) $category['image'] : null);
            $payload['image'] = $image;
        }

        $model->updateById((int) $id, $payload);

        AuditService::logChanges('catalog', 'category', (int) $id, $category, $payload, 'Categoría editada: ' . $data['name']);

        $this->success('Categoría actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model    = new Category();
        $category = $model->find((int) $id);

        if ($category === null) {
            $this->abort(404, 'La categoría no existe.');
        }

        if ($model->hasProducts((int) $id)) {
            $this->error('No se puede eliminar: tiene productos asociados. Desactivala o movelos a otra categoría.');
            $this->back();
        }

        Uploader::delete($category['image'] !== null ? (string) $category['image'] : null);
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'category', (int) $id, 'Categoría eliminada: ' . $category['name']);

        $this->success('Categoría eliminada.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'             => 'required|string|min:2|max:120',
            'type'             => 'required|in:machine,spare_part,service',
            'description'      => 'max:2000',
            'icon'             => 'max:60',
            'meta_title'       => 'max:180',
            'meta_description' => 'max:300',
            'sort_order'       => 'integer',
        ], [
            'name' => 'nombre',
            'type' => 'tipo',
        ]);

        return [
            'name'             => $data['name'],
            'type'             => $data['type'],
            'parent_id'        => Request::int('parent_id') ?: null,
            'description'      => $data['description'] ?? null,
            'icon'             => $data['icon'] ?? null,
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'featured'         => Request::bool('featured') ? 1 : 0,
            'active'           => Request::bool('active', true) ? 1 : 0,
        ];
    }

    private function uploadImage(): ?string
    {
        $file = Request::file('image');
        if ($file === null) {
            return null;
        }

        $result = Uploader::image($file, 'categories', 1200, false);

        if (!$result['ok']) {
            $this->error($result['message']);
            return null;
        }

        return $result['path'];
    }
}
