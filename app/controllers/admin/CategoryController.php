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
            'type'       => $data['type'],
            'name'       => $data['name'],
            'slug'       => $model->uniqueSlug($data['name']),
            'icon'       => $data['icon'],
            'image'      => $this->uploadImage(),
            'sort_order' => $model->nextSortOrder($data['type']),
            'featured'   => 1,
            'active'     => 1,
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

        $payload = [
            'type' => $data['type'],
            'name' => $data['name'],
            'icon' => $data['icon'],
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

    /**
     * Versión simplificada: una categoría es nombre, tipo, ícono e imagen.
     * El orden se asigna solo y la categoría queda activa y visible.
     *
     * @return array<string,mixed>
     */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name' => 'required|string|min:2|max:120',
            'type' => 'required|in:machine,spare_part,service',
            'icon' => 'max:60',
        ], [
            'name' => 'nombre',
            'type' => 'tipo',
        ]);

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => ($data['icon'] ?? '') !== '' ? $data['icon'] : null,
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
