<?php
/**
 * ARCHIVO: app/controllers/admin/ServiceController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Service;
use App\Services\AuditService;
use Core\Request;
use Core\Uploader;

class ServiceController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/services/index', [
            'pageTitle'  => 'Servicios · Panel',
            'adminTitle' => 'Servicios',
            'robots'     => 'noindex, nofollow',
            'services'   => (new Service())->all('sort_order', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new Service();

        $id = $model->create(array_merge($data, [
            'slug'  => $model->uniqueSlug((string) $data['title']),
            'image' => $this->uploadImage(),
        ]));

        AuditService::log('create', 'catalog', 'service', $id, 'Servicio creado: ' . $data['title']);

        $this->success('Servicio creado.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model   = new Service();
        $service = $model->find((int) $id);

        if ($service === null) {
            $this->abort(404, 'El servicio no existe.');
        }

        $data = $this->validateInput();

        if ($service['title'] !== $data['title']) {
            $data['slug'] = $model->uniqueSlug((string) $data['title'], (int) $id);
        }

        $image = $this->uploadImage();
        if ($image !== null) {
            Uploader::delete($service['image'] !== null ? (string) $service['image'] : null);
            $data['image'] = $image;
        }

        $model->updateById((int) $id, $data);

        AuditService::log('update', 'catalog', 'service', (int) $id, 'Servicio editado: ' . $data['title']);

        $this->success('Servicio actualizado.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model   = new Service();
        $service = $model->find((int) $id);

        if ($service === null) {
            $this->abort(404, 'El servicio no existe.');
        }

        Uploader::delete($service['image'] !== null ? (string) $service['image'] : null);
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'service', (int) $id, 'Servicio eliminado: ' . $service['title']);

        $this->success('Servicio eliminado.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'title'             => 'required|string|min:3|max:160',
            'icon'              => 'max:60',
            'short_description' => 'max:300',
            'description'       => 'max:20000',
            'bullets'           => 'max:2000',
            'sort_order'        => 'integer',
        ], ['title' => 'título']);

        // Características: una por línea → JSON
        $bullets = null;
        if (!empty($data['bullets'])) {
            $lines   = array_values(array_filter(array_map('trim', explode("\n", (string) $data['bullets']))));
            $bullets = $lines === [] ? null : json_encode($lines, JSON_UNESCAPED_UNICODE);
        }

        return [
            'title'             => $data['title'],
            'icon'              => $data['icon'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description'       => clean_html((string) ($data['description'] ?? '')) ?: null,
            'bullets'           => $bullets,
            'featured'          => Request::bool('featured') ? 1 : 0,
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
            'active'            => Request::bool('active', true) ? 1 : 0,
        ];
    }

    private function uploadImage(): ?string
    {
        $file = Request::file('image');
        if ($file === null) {
            return null;
        }

        $result = Uploader::image($file, 'services', 1400, true);

        if (!$result['ok']) {
            $this->error($result['message']);
            return null;
        }

        return $result['path'];
    }
}
