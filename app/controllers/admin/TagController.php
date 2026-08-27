<?php
/**
 * ARCHIVO: app/controllers/admin/TagController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Tag;
use App\Services\AuditService;
use Core\Request;

class TagController extends AdminController
{
    private const COLORS = [
        'accent'    => 'Amarillo',
        'dark'      => 'Negro',
        'secondary' => 'Gris',
        'success'   => 'Verde',
        'danger'    => 'Rojo',
        'warning'   => 'Naranja',
        'info'      => 'Azul',
    ];

    public function index(): void
    {
        $this->view('admin/tags/index', [
            'pageTitle'  => 'Etiquetas · Panel',
            'adminTitle' => 'Etiquetas',
            'robots'     => 'noindex, nofollow',
            'tags'       => (new Tag())->withCounts(),
            'colors'     => self::COLORS,
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new Tag();

        $id = $model->create(array_merge($data, ['slug' => $model->uniqueSlug((string) $data['name'])]));

        AuditService::log('create', 'catalog', 'tag', $id, 'Etiqueta creada: ' . $data['name']);

        $this->success('Etiqueta creada.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model = new Tag();
        $tag   = $model->find((int) $id);

        if ($tag === null) {
            $this->abort(404, 'La etiqueta no existe.');
        }

        $data = $this->validateInput();

        if ($tag['name'] !== $data['name']) {
            $data['slug'] = $model->uniqueSlug((string) $data['name'], (int) $id);
        }

        $model->updateById((int) $id, $data);

        AuditService::log('update', 'catalog', 'tag', (int) $id, 'Etiqueta editada: ' . $data['name']);

        $this->success('Etiqueta actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model = new Tag();
        $tag   = $model->find((int) $id);

        if ($tag === null) {
            $this->abort(404, 'La etiqueta no existe.');
        }

        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'tag', (int) $id, 'Etiqueta eliminada: ' . $tag['name']);

        $this->success('Etiqueta eliminada.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'       => 'required|string|min:2|max:60',
            'color'      => 'required|in:' . implode(',', array_keys(self::COLORS)),
            'icon'       => 'max:60',
            'sort_order' => 'integer',
        ], ['name' => 'nombre', 'color' => 'color']);

        return [
            'name'       => $data['name'],
            'color'      => $data['color'],
            'icon'       => $data['icon'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active'     => Request::bool('active', true) ? 1 : 0,
        ];
    }
}
