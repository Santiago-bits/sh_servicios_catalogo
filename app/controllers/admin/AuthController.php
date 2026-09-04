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
use Core\RateLimiter;
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

        // Freno por IP, además del bloqueo por usuario de Auth::attempt().
        $throttleKey = 'login:' . Request::ip();
        if (RateLimiter::tooMany($throttleKey, 15, 600)) {
            Session::flashInput(['email' => $data['email']]);
            $this->error('Demasiados intentos desde esta conexión. Esperá unos minutos.');
            $this->redirect('admin/login');
        }

        $result = Auth::attempt((string) $data['email'], (string) $data['password']);

        if (!$result['ok']) {
            RateLimiter::hit($throttleKey, 600);
            Session::flashInput(['email' => $data['email']]);
            $this->error($result['message']);
            $this->redirect('admin/login');
        }

        RateLimiter::clear($throttleKey);
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
            'current_password' => 'required|string',
            'name'             => 'required|string|min:3|max:120',
            'email'            => 'required|email|max:160',
            'phone'            => 'max:40',
        ], [
            'current_password' => 'contraseña actual',
            'name'             => 'nombre',
            'email'            => 'email',
            'phone'            => 'teléfono',
        ]);

        $userModel = new User();
        $current   = $userModel->find($userId);

        if ($current === null || !password_verify((string) $data['current_password'], (string) $current['password'])) {
            $this->error('Confirmá tu contraseña actual para guardar los cambios.');
            $this->back();
        }

        if ($userModel->emailExists((string) $data['email'], $userId)) {
            $this->error('Ese email ya está en uso por otra cuenta.');
            $this->back();
        }

        $emailChanged = ($current['email'] ?? '') !== $data['email'];

        $userModel->updateById($userId, [
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        AuditService::log(
            'update',
            'users',
            'user',
            $userId,
            $emailChanged ? 'Cambió su email: ' . ($current['email'] ?? '') . ' -> ' . $data['email'] : 'Actualizó su propio perfil'
        );

        if ($emailChanged) {
            Session::regenerate();
        }

        $this->success('Perfil actualizado.');
        $this->back();
    }

    public function updatePassword(): void
    {
        $userId = (int) Auth::id();

        $data = $this->validate(Request::all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:12|max:200|confirmed',
        ], [
            'current_password' => 'contraseña actual',
            'password'         => 'contraseña nueva',
        ]);

        $user = (new User())->find($userId);

        if ($user === null || !password_verify((string) $data['current_password'], (string) $user['password'])) {
            $this->error('La contraseña actual no es correcta.');
            $this->back();
        }

        if (($policyError = self::passwordPolicyError((string) $data['password'])) !== null) {
            $this->error($policyError);
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

    /**
     * Política de contraseña para cuando se define una nueva (no en el login).
     * Devuelve el texto del error o null si pasa.
     */
    public static function passwordPolicyError(string $password): ?string
    {
        $classes = 0;
        $classes += preg_match('/[a-z]/', $password);
        $classes += preg_match('/[A-Z]/', $password);
        $classes += preg_match('/\d/', $password);
        $classes += preg_match('/[^A-Za-z0-9]/', $password);

        if ($classes < 3) {
            return 'La contraseña debe combinar al menos 3 de: minúsculas, mayúsculas, números y símbolos.';
        }

        $common = [
            'password', 'contraseña', '123456789012', 'qwertyuiop12', 'administrator',
            'admin1234567', 'sh-servicios', 'shservicios1', 'cambiar12345', '111111111111',
        ];
        $low = mb_strtolower($password);
        foreach ($common as $bad) {
            if ($low === $bad || str_contains($low, $bad)) {
                return 'Esa contraseña es demasiado predecible. Elegí una distinta.';
            }
        }

        return null;
    }
}
