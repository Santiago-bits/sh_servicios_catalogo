<?php
/**
 * ARCHIVO: app/controllers/admin/FeatureController.php
 * ---------------------------------------------------------------------
 * Características técnicas configurables: el administrador puede crear
 * las que necesite sin tocar la estructura de la base.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Feature;
use App\Services\AuditService;
use Core\Database;
use Core\Request;

class FeatureController extends AdminController
{
    public function index(): void
    {
        $features = Database::select(
            'SELECT f.*, (SELECT COUNT(*) FROM feature_values fv WHERE fv.feature_id = f.id) AS uses
               FROM features f ORDER BY f.name ASC'
        );

        $this->view('admin/features/index', [
            'pageTitle'  => 'Características · Panel',
            'adminTitle' => 'Características',
            'robots'     => 'noindex, nofollow',
            'features'   => $features,
            'types'      => ['machine' => 'Maquinaria', 'spare_part' => 'Repuestos', 'both' => 'Ambos'],
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateInput();
        $model = new Feature();

        $id = $model->create(array_merge($data, ['slug' => $model->uniqueSlug((string) $data['name'])]));

        AuditService::log('create', 'catalog', 'feature', $id, 'Característica creada: ' . $data['name']);

        $this->success('Característica creada.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model   = new Feature();
        $feature = $model->find((int) $id);

        if ($feature === null) {
            $this->abort(404, 'La característica no existe.');
        }

        $data = $this->validateInput();

        if ($feature['name'] !== $data['name']) {
            $data['slug'] = $model->uniqueSlug((string) $data['name'], (int) $id);
        }

        $model->updateById((int) $id, $data);

        AuditService::logChanges('catalog', 'feature', (int) $id, $feature, $data, 'Característica editada: ' . $data['name']);

        $this->success('Característica actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model   = new Feature();
        $feature = $model->find((int) $id);

        if ($feature === null) {
            $this->abort(404, 'La característica no existe.');
        }

        // Al borrarla se pierden los valores cargados en los productos
        $uses = (int) Database::scalar('SELECT COUNT(*) FROM feature_values WHERE feature_id = :id', ['id' => (int) $id]);

        $model->deleteById((int) $id);

        AuditService::log(
            'delete',
            'catalog',
            'feature',
            (int) $id,
            'Característica eliminada: ' . $feature['name'] . ' (' . $uses . ' valores cargados)'
        );

        $this->success('Característica eliminada' . ($uses > 0 ? ' junto con ' . $uses . ' valor(es) cargado(s).' : '.'));
        $this->back();
    }

    /**
     * Versión simplificada: una característica es sólo un nombre, una
     * unidad opcional y a qué productos se aplica. Todo lo demás queda
     * fijo (campo de texto, visible en el sitio, activa).
     *
     * @return array<string,mixed>
     */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'       => 'required|string|min:2|max:120',
            'unit'       => 'max:20',
            'applies_to' => 'required|in:machine,spare_part,both',
        ], [
            'name'       => 'nombre',
            'applies_to' => 'se aplica a',
        ]);

        return [
            'name'       => $data['name'],
            'group_name' => 'General',
            'unit'       => ($data['unit'] ?? '') !== '' ? $data['unit'] : null,
            'input_type' => 'text',
            'applies_to' => $data['applies_to'],
            'options'    => null,
            'sort_order' => 0,
            'filterable' => 0,
            'comparable' => 1,
            'public'     => 1,
            'active'     => 1,
        ];
    }
}
