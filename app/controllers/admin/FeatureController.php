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
               FROM features f ORDER BY f.applies_to ASC, f.sort_order ASC, f.name ASC'
        );

        $grouped = [];
        foreach ($features as $feature) {
            $grouped[$feature['group_name'] ?: 'General'][] = $feature;
        }

        $this->view('admin/features/index', [
            'pageTitle'  => 'Características técnicas · Panel',
            'adminTitle' => 'Características técnicas',
            'robots'     => 'noindex, nofollow',
            'features'   => $features,
            'grouped'    => $grouped,
            'groups'     => array_values(array_unique(array_column($features, 'group_name'))),
            'types'      => ['machine' => 'Maquinaria', 'spare_part' => 'Repuestos', 'both' => 'Ambos'],
            'inputTypes' => ['text' => 'Texto', 'number' => 'Número', 'select' => 'Lista de opciones', 'boolean' => 'Sí / No'],
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

    /** @return array<string,mixed> */
    private function validateInput(): array
    {
        $data = $this->validate(Request::all(), [
            'name'       => 'required|string|min:2|max:120',
            'group_name' => 'max:80',
            'unit'       => 'max:20',
            'input_type' => 'required|in:text,number,select,boolean',
            'applies_to' => 'required|in:machine,spare_part,both',
            'sort_order' => 'integer',
            'options'    => 'max:2000',
        ], [
            'name'       => 'nombre',
            'input_type' => 'tipo de dato',
            'applies_to' => 'se aplica a',
        ]);

        // Las opciones se cargan una por línea y se guardan como JSON
        $options = null;
        if (($data['input_type'] ?? '') === 'select' && !empty($data['options'])) {
            $lines   = array_values(array_filter(array_map('trim', explode("\n", (string) $data['options']))));
            $options = $lines === [] ? null : json_encode($lines, JSON_UNESCAPED_UNICODE);
        }

        return [
            'name'       => $data['name'],
            'group_name' => $data['group_name'] ?? 'General',
            'unit'       => $data['unit'] ?? null,
            'input_type' => $data['input_type'],
            'applies_to' => $data['applies_to'],
            'options'    => $options,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'filterable' => Request::bool('filterable') ? 1 : 0,
            'comparable' => Request::bool('comparable', true) ? 1 : 0,
            'public'     => Request::bool('public', true) ? 1 : 0,
            'active'     => Request::bool('active', true) ? 1 : 0,
        ];
    }
}
