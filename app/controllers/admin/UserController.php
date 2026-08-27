<?php
/**
 * ARCHIVO: app/controllers/admin/UserController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Core\Auth;
use Core\Request;

class UserController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/users/index', [
            'pageTitle'  => 'Usuarios · Panel',
            'adminTitle' => 'Usuarios',
            'robots'     => 'noindex, nofollow',
            'users'      => (new User())->allWithRole(),
            'roles'      => (new Role())->allWithCounts(),
            'canManage'  => can('users.manage'),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('users.manage');

        $data = $this->validate(Request::all(), [
            'name'     => 'required|string|min:3|max:120',
            'email'    => 'required|email|max:160|unique:users,email',
            'password' => 'required|string|min:8|max:200|confirmed',
            'role_id'  => 'required|integer|exists:roles,id',
            'phone'    => 'max:40',
            'position' => 'max:120',
        ], [
            'name'     => 'nombre',
            'email'    => 'email',
            'password' => 'contraseña',
            'role_id'  => 'rol',
            'position' => 'puesto',
        ]);

        if (!preg_match('/[A-Za-z]/', (string) $data['password']) || !preg_match('/\d/', (string) $data['password'])) {
            $this->error('La contraseña debe combinar letras y números.');
            $this->back();
        }

        $id = (new User())->create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => Auth::hash((string) $data['password']),
            'role_id'        => (int) $data['role_id'],
            'phone'          => $data['phone'] ?? null,
            'position'       => $data['position'] ?? null,
            'active'         => Request::bool('active', true) ? 1 : 0,
            'must_change_pw' => Request::bool('must_change_pw') ? 1 : 0,
        ]);

        AuditService::log('create', 'users', 'user', $id, 'Usuario creado: ' . $data['email']);

        $this->success('Usuario creado.');
        $this->back();
    }

    public function update(string $id): void
    {
        $this->requirePermission('users.manage');

        $model = new User();
        $user  = $model->find((int) $id);

        if ($user === null) {
            $this->abort(404, 'El usuario no existe.');
        }

        $data = $this->validate(Request::all(), [
            'name'     => 'required|string|min:3|max:120',
            'email'    => 'required|email|max:160',
            'role_id'  => 'required|integer|exists:roles,id',
            'phone'    => 'max:40',
            'position' => 'max:120',
            'password' => 'min:8|max:200',
        ], [
            'name'    => 'nombre',
            'email'   => 'email',
            'role_id' => 'rol',
        ]);

        if ($model->emailExists((string) $data['email'], (int) $id)) {
            $this->error('Ese email ya está en uso por otra cuenta.');
            $this->back();
        }

        $active = Request::bool('active', true) ? 1 : 0;

        // Protección: no dejar el sistema sin administradores
        if (($active === 0 || (int) $data['role_id'] !== (int) $user['role_id']) && $model->isLastAdmin((int) $id)) {
            $adminRole = (int) \Core\Database::scalar('SELECT id FROM roles WHERE slug = \'admin\'');
            if ((int) $user['role_id'] === $adminRole) {
                $this->error('No podés desactivar ni cambiar el rol del único administrador activo.');
                $this->back();
            }
        }

        $payload = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role_id'  => (int) $data['role_id'],
            'phone'    => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'active'   => $active,
        ];

        if (!empty($data['password'])) {
            if (!preg_match('/[A-Za-z]/', (string) $data['password']) || !preg_match('/\d/', (string) $data['password'])) {
                $this->error('La contraseña debe combinar letras y números.');
                $this->back();
            }
            $payload['password']      = Auth::hash((string) $data['password']);
            $payload['failed_logins'] = 0;
            $payload['locked_until']  = null;
        }

        // Desbloqueo manual
        if (Request::bool('unlock')) {
            $payload['failed_logins'] = 0;
            $payload['locked_until']  = null;
        }

        $model->updateById((int) $id, $payload);

        unset($payload['password']);
        AuditService::logChanges('users', 'user', (int) $id, $user, $payload, 'Usuario editado: ' . $data['email']);

        $this->success('Usuario actualizado.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('users.manage');

        $model = new User();
        $user  = $model->find((int) $id);

        if ($user === null) {
            $this->abort(404, 'El usuario no existe.');
        }

        if ((int) $id === (int) Auth::id()) {
            $this->error('No podés eliminar tu propia cuenta.');
            $this->back();
        }

        if ($model->isLastAdmin((int) $id)) {
            $this->error('No podés eliminar al único administrador activo.');
            $this->back();
        }

        // Se desactiva en lugar de borrar: conserva la trazabilidad
        $model->updateById((int) $id, ['active' => 0]);

        AuditService::log('delete', 'users', 'user', (int) $id, 'Usuario desactivado: ' . $user['email']);

        $this->success('Usuario desactivado. Su historial de auditoría se conserva.');
        $this->back();
    }
}
