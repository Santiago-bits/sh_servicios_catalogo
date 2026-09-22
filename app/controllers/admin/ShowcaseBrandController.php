<?php
/**
 * ARCHIVO: app/controllers/admin/ShowcaseBrandController.php
 * ---------------------------------------------------------------------
 * "Marcas en la web": marcas que se muestran en la home (Equipos y
 * repuestos / Neumáticos) y logos de clientes del servicio de Alquiler.
 * No dependen de las marcas asignadas a los productos.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ShowcaseBrand;
use App\Services\AuditService;
use Core\Request;
use Core\Uploader;

class ShowcaseBrandController extends AdminController
{
    public function index(): void
    {
        $model = new ShowcaseBrand();

        $this->view('admin/showcase/index', [
            'pageTitle'  => 'Marcas en la web · Panel',
            'adminTitle' => 'Marcas en la web',
            'robots'     => 'noindex, nofollow',
            'groups'     => $model->grouped(),
            'sections'   => ShowcaseBrand::SECTIONS,
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new ShowcaseBrand();

        $id = $model->create(array_merge($data, [
            'logo'       => $this->uploadLogo(),
            'sort_order' => $data['sort_order'] > 0 ? $data['sort_order'] : $model->nextSortOrder($data['section']),
        ]));

        AuditService::log('create', 'catalog', 'showcase_brand', $id, 'Marca en la web creada: ' . $data['name']);

        $this->success('Marca agregada.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model = new ShowcaseBrand();
        $brand = $model->find((int) $id);

        if ($brand === null) {
            $this->abort(404, 'La marca no existe.');
        }

        $data = $this->validateInput();
        if ($data['sort_order'] <= 0) {
            $data['sort_order'] = (int) $brand['sort_order'];
        }

        if (Request::flag('remove_logo')) {
            Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
            $data['logo'] = null;
        }

        $logo = $this->uploadLogo();
        if ($logo !== null) {
            Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
            $data['logo'] = $logo;
        }

        $model->updateById((int) $id, $data);

        AuditService::log('update', 'catalog', 'showcase_brand', (int) $id, 'Marca en la web editada: ' . $data['name']);

        $this->success('Marca actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model = new ShowcaseBrand();
        $brand = $model->find((int) $id);

        if ($brand === null) {
            $this->abort(404, 'La marca no existe.');
        }

        Uploader::delete($brand['logo'] !== null ? (string) $brand['logo'] : null);
        $model->deleteById((int) $id);

        AuditService::log('delete', 'catalog', 'showcase_brand', (int) $id, 'Marca en la web eliminada: ' . $brand['name']);

        $this->success('Marca eliminada.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'       => 'required|string|min:2|max:120',
            'sort_order' => 'integer',
        ], ['name' => 'nombre']);

        $section = (string) Request::input('section', 'equipos');
        if (!array_key_exists($section, ShowcaseBrand::SECTIONS)) {
            $section = 'equipos';
        }

        return [
            'section'    => $section,
            'name'       => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active'     => Request::flag('active', true),
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
