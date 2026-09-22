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
        $model    = new Service();
        $services = $model->all('sort_order', 'ASC');

        $images = [];
        foreach ($services as $service) {
            $images[(int) $service['id']] = $model->images((int) $service['id']);
        }

        $this->view('admin/services/index', [
            'pageTitle'  => 'Servicios · Panel',
            'adminTitle' => 'Servicios',
            'robots'     => 'noindex, nofollow',
            'services'   => $services,
            'images'     => $images,
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

        $this->uploadGallery($model, $id);

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
        $this->uploadGallery($model, (int) $id);

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
        foreach ($model->images((int) $id) as $img) {
            Uploader::delete((string) $img['path']);
            Uploader::delete($img['thumb'] !== null ? (string) $img['thumb'] : null);
        }
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'service', (int) $id, 'Servicio eliminado: ' . $service['title']);

        $this->success('Servicio eliminado.');
        $this->back();
    }

    public function deleteImage(string $id, string $imageId): void
    {
        $model = new Service();
        $image = $model->findImage((int) $id, (int) $imageId);

        if ($image === null) {
            $this->abort(404, 'La foto no existe.');
        }

        Uploader::delete((string) $image['path']);
        Uploader::delete($image['thumb'] !== null ? (string) $image['thumb'] : null);
        $model->deleteImage((int) $imageId);

        $this->success('Foto eliminada.');
        $this->back();
    }

    /** Sube las fotos del input gallery[] a la galería del servicio. */
    private function uploadGallery(Service $model, int $serviceId): void
    {
        $files = Request::files('gallery');
        if ($files === []) {
            return;
        }

        $ok = 0;
        foreach ($files as $file) {
            $result = Uploader::image($file, 'services', 1600, true);
            if (!$result['ok']) {
                $this->error(($file['name'] ?? 'Foto') . ': ' . $result['message']);
                continue;
            }
            try {
                $model->addImage($serviceId, $result['path'], $result['thumb'] ?? null);
                $ok++;
            } catch (\Throwable $e) {
                Uploader::delete($result['path']);
                $this->error('No se pudo guardar la galería. ¿Corriste la migración database/migracion_2026_09_22.sql?');
                return;
            }
        }

        if ($ok > 0) {
            $this->success($ok . ' foto(s) agregada(s) a la galería.');
        }
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'title'             => 'required|string|min:3|max:160',
            'icon'              => 'max:60',
            'short_description' => 'max:300',
            'description'       => 'max:20000',
            'sort_order'        => 'integer',
        ], ['title' => 'título']);

        return [
            'title'             => $data['title'],
            'icon'              => $data['icon'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description'       => clean_html((string) ($data['description'] ?? '')) ?: null,
            'featured'          => Request::flag('featured'),
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
            'active'            => Request::flag('active'),
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
