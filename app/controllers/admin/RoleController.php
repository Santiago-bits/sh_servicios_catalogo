<?php
/**
 * ARCHIVO: app/controllers/admin/RoleController.php
 * ---------------------------------------------------------------------
 * Roles y permisos. Los permisos se asignan por rol y se verifican en
 * el servidor en cada petición (middleware + Auth::can).
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Role;
use App\Services\AuditService;
use Core\Request;

class RoleController extends AdminController
{
    public function index(): void
    {
        $model = new Role();

        $this->view('admin/roles/index', [
            'pageTitle'   => 'Roles y permisos · Panel',
            'adminTitle'  => 'Roles y permisos',
            'robots'      => 'noindex, nofollow',
            'roles'       => $model->allWithCounts(),
            'permissions' => $model->allPermissions(),
            'moduleNames' => $this->moduleNames(),
        ]);
    }

    public function edit(string $id): void
    {
        $model = new Role();
        $role  = $model->find((int) $id);

        if ($role === null) {
            $this->abort(404, 'El rol no existe.');
        }

        $this->view('admin/roles/edit', [
            'pageTitle'   => 'Permisos de ' . $role['name'] . ' · Panel',
            'adminTitle'  => 'Permisos de ' . $role['name'],
            'robots'      => 'noindex, nofollow',
            'role'        => $role,
            'permissions' => $model->allPermissions(),
            'assigned'    => $model->permissionIds((int) $id),
            'moduleNames' => $this->moduleNames(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validate(Request::all(), [
            'name'        => 'required|string|min:3|max:60',
            'description' => 'max:255',
        ], ['name' => 'nombre']);

        $model = new Role();

        $id = $model->create([
            'name'        => $data['name'],
            'slug'        => $model->uniqueSlug((string) $data['name']),
            'description' => $data['description'] ?? null,
            'is_system'   => 0,
            'active'      => 1,
        ]);

        $model->syncPermissions($id, array_map('intval', Request::array('permissions')));

        AuditService::log('create', 'users', 'role', $id, 'Rol creado: ' . $data['name']);

        $this->success('Rol creado. Asignale los permisos que corresponda.');
        $this->redirect('admin/roles/' . $id);
    }

    public function update(string $id): void
    {
        $model = new Role();
        $role  = $model->find((int) $id);

        if ($role === null) {
            $this->abort(404, 'El rol no existe.');
        }

        $data = $this->validate(Request::all(), [
            'name'        => 'required|string|min:3|max:60',
            'description' => 'max:255',
        ], ['name' => 'nombre']);

        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'active'      => Request::flag('active', true),
        ];

        // Los roles de sistema no cambian de nombre clave ni se desactivan
        if ((int) $role['is_system'] === 1) {
            $payload['active'] = 1;
        }

        $model->updateById((int) $id, $payload);

        $permissions = array_map('intval', Request::array('permissions'));

        // El rol admin siempre conserva todos los permisos
        if ($role['slug'] !== 'admin') {
            $model->syncPermissions((int) $id, $permissions);
        }

        AuditService::log(
            'permissions_change',
            'users',
            'role',
            (int) $id,
            'Permisos del rol ' . $role['name'] . ' actualizados (' . count($permissions) . ')'
        );

        $this->success('Rol y permisos actualizados.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model = new Role();
        $role  = $model->find((int) $id);

        if ($role === null) {
            $this->abort(404, 'El rol no existe.');
        }

        if ((int) $role['is_system'] === 1) {
            $this->error('Los roles del sistema no se pueden eliminar.');
            $this->back();
        }

        if ($model->hasUsers((int) $id)) {
            $this->error('No se puede eliminar: hay usuarios con este rol.');
            $this->back();
        }

        $model->deleteById((int) $id);

        AuditService::log('delete', 'users', 'role', (int) $id, 'Rol eliminado: ' . $role['name']);

        $this->success('Rol eliminado.');
        $this->redirect('admin/roles');
    }

    /** @return array<string,string> */
    private function moduleNames(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'machines'  => 'Maquinaria',
            'parts'     => 'Repuestos',
            'catalog'   => 'Catálogo (categorías, marcas, etc.)',
            'prices'    => 'Precios',
            'financing' => 'Financiación',
            'quotes'    => 'Cotizaciones',
            'inquiries' => 'Consultas',
            'users'     => 'Usuarios y roles',
            'stats'     => 'Estadísticas',
            'audit'     => 'Auditoría',
            'settings'  => 'Configuración',
            'data'      => 'Importación / exportación',
        ];
    }
}
