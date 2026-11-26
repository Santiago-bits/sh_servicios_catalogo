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

        $this->guardAdminAccounts(null, (int) $data['role_id']);

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
            'active'         => Request::flag('active', true),
            'must_change_pw' => Request::flag('must_change_pw'),
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

        $this->guardAdminAccounts($user, (int) $data['role_id']);

        // Nadie que no sea administrador puede cambiarse su propio rol: si
        // no, quien tiene "users.manage" se pasaría a un rol con más permisos.
        if (!Auth::isAdmin() && (int) $id === (int) Auth::id() && (int) $data['role_id'] !== (int) $user['role_id']) {
            $this->error('No podés cambiar tu propio rol. Pedíselo a un administrador.');
            $this->back();
        }

        if ($model->emailExists((string) $data['email'], (int) $id)) {
            $this->error('Ese email ya está en uso por otra cuenta.');
            $this->back();
        }

        $active = Request::flag('active', true);

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

        $this->guardAdminAccounts($user);

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

    /**
     * Borrado definitivo (a diferencia de destroy(), que sólo desactiva).
     * Las referencias a este usuario en otras tablas (auditoría, consultas
     * asignadas, historial de precios, productos, cotizaciones, movimientos
     * de stock) quedan en NULL: ese contenido no se borra, sólo deja de
     * estar atribuido a alguien.
     */
    public function purge(string $id): void
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

        $this->guardAdminAccounts($user);

        if ($model->isLastAdmin((int) $id)) {
            $this->error('No podés eliminar al único administrador activo.');
            $this->back();
        }

        AuditService::log('delete', 'users', 'user', (int) $id, 'Usuario eliminado definitivamente: ' . $user['email']);

        $model->deleteById((int) $id);

        $this->success('Usuario eliminado definitivamente.');
        $this->back();
    }

    /**
     * Sólo un administrador puede crear, editar, desactivar o borrar una
     * cuenta de administrador, o darle ese rol a alguien. Sin este control,
     * un usuario con el permiso "users.manage" podía cambiarle la contraseña
     * al administrador (y entrar con su cuenta) o ponerse él mismo el rol
     * de administrador.
     *
     * @param array<string,mixed>|null $target usuario existente (null al crear)
     */
    private function guardAdminAccounts(?array $target, ?int $newRoleId = null): void
    {
        if (Auth::isAdmin()) {
            return;
        }

        $adminRoleId = (int) \Core\Database::scalar("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");

        $touchesAdmin = ($target !== null && (int) $target['role_id'] === $adminRoleId)
            || ($newRoleId !== null && $newRoleId === $adminRoleId);

        if (!$touchesAdmin) {
            return;
        }

        AuditService::log(
            'access_denied',
            'security',
            'user',
            $target !== null ? (int) $target['id'] : null,
            'Intento de gestionar una cuenta o el rol de administrador sin ser administrador'
        );

        $this->error('Sólo un administrador puede gestionar cuentas de administrador.');
        $this->back();
    }
}
