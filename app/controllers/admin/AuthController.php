<?php
/**
 * ARCHIVO: app/controllers/admin/AuthController.php
 * ---------------------------------------------------------------------
 * Login, logout y perfil del usuario interno.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\User;
use App\Services\AuditService;
use App\Services\SettingService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Session;

class AuthController extends Controller
{
    protected string $layout = 'auth';

    public function showLogin(): void
    {
        $this->view('admin/auth/login', [
            'pageTitle' => 'Acceso al panel · ' . SettingService::companyName(),
            'bodyClass' => 'page-login',
            'robots'    => 'noindex, nofollow',
        ]);
    }

    public function login(): void
    {
        $data = $this->validate(Request::all(), [
            'email'    => 'required|email|max:160',
            'password' => 'required|string|min:6|max:200',
        ], [
            'email'    => 'email',
            'password' => 'contraseña',
        ]);

        $result = Auth::attempt((string) $data['email'], (string) $data['password']);

        if (!$result['ok']) {
            Session::flashInput(['email' => $data['email']]);
            $this->error($result['message']);
            $this->redirect('admin/login');
        }

        $this->success($result['message']);

        $intended = Session::get('_intended_url');
        Session::forget('_intended_url');

        if (is_string($intended) && str_starts_with($intended, BASE_URL)) {
            $this->redirect($intended);
        }

        $this->redirect('admin');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Cerraste sesión correctamente.');
        $this->redirect('admin/login');
    }

    // ----------------------------------------------------------------
    // Perfil
    // ----------------------------------------------------------------

    public function profile(): void
    {
        $this->layout = 'admin';

        $this->view('admin/auth/profile', [
            'pageTitle'  => 'Mi perfil · Panel',
            'adminTitle' => 'Mi perfil',
            'robots'     => 'noindex, nofollow',
            'user'       => (new User())->findWithRole((int) Auth::id()),
            'permissions'=> Auth::permissions(),
        ]);
    }

    public function updateProfile(): void
    {
        $userId = (int) Auth::id();

        $data = $this->validate(Request::all(), [
            'name'  => 'required|string|min:3|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'max:40',
        ], [
            'name'  => 'nombre',
            'email' => 'email',
            'phone' => 'teléfono',
        ]);

        $userModel = new User();

        if ($userModel->emailExists((string) $data['email'], $userId)) {
            $this->error('Ese email ya está en uso por otra cuenta.');
            $this->back();
        }

        $userModel->updateById($userId, [
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        AuditService::log('update', 'users', 'user', $userId, 'Actualizó su propio perfil');

        $this->success('Perfil actualizado.');
        $this->back();
    }

    public function updatePassword(): void
    {
        $userId = (int) Auth::id();

        $data = $this->validate(Request::all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|max:200|confirmed',
        ], [
            'current_password' => 'contraseña actual',
            'password'         => 'contraseña nueva',
        ]);

        $user = (new User())->find($userId);

        if ($user === null || !password_verify((string) $data['current_password'], (string) $user['password'])) {
            $this->error('La contraseña actual no es correcta.');
            $this->back();
        }

        // Exigencia mínima de robustez
        if (!preg_match('/[A-Za-z]/', (string) $data['password']) || !preg_match('/\d/', (string) $data['password'])) {
            $this->error('La contraseña nueva debe combinar letras y números.');
            $this->back();
        }

        (new User())->updateById($userId, [
            'password'       => Auth::hash((string) $data['password']),
            'must_change_pw' => 0,
        ]);

        AuditService::log('password_change', 'users', 'user', $userId, 'Cambió su contraseña');

        Session::regenerate();

        $this->success('Contraseña actualizada.');
        $this->back();
    }
}
