<?php
/**
 * ARCHIVO: core/Auth.php
 * ---------------------------------------------------------------------
 * Autenticación de usuarios internos (admin / operario / vendedor):
 * hashing con password_hash, verificación con password_verify, bloqueo
 * por intentos fallidos, regeneración de sesión y caché de permisos.
 */

declare(strict_types=1);

namespace Core;

use App\Services\AuditService;

final class Auth
{
    private const SESSION_KEY = '_auth_user_id';

    /** @var array<string,mixed>|null */
    private static ?array $user = null;
    /** @var array<int,string>|null */
    private static ?array $permissions = null;

    /**
     * Intenta iniciar sesión.
     *
     * @return array{ok:bool,message:string}
     */
    public static function attempt(string $email, string $password): array
    {
        $genericError = 'Usuario o contraseña incorrectos.';

        $user = Database::selectOne(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
               FROM users u
               INNER JOIN roles r ON r.id = u.role_id
              WHERE u.email = :email
              LIMIT 1',
            ['email' => $email]
        );

        // Se hashea igual cuando el usuario no existe para no filtrar
        // por diferencia de tiempo qué emails están registrados.
        if ($user === null) {
            password_verify($password, '$2y$12$usuarioinexistenteusuarioinexistenteusuarioinexistente00');
            return ['ok' => false, 'message' => $genericError];
        }

        if ((int) $user['active'] !== 1) {
            return ['ok' => false, 'message' => 'La cuenta está desactivada. Contactá al administrador.'];
        }

        if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            $minutes = (int) ceil((strtotime((string) $user['locked_until']) - time()) / 60);
            return ['ok' => false, 'message' => 'Cuenta bloqueada temporalmente. Reintentá en ' . $minutes . ' minuto(s).'];
        }

        if (!password_verify($password, (string) $user['password'])) {
            self::registerFailedAttempt($user);
            return ['ok' => false, 'message' => $genericError];
        }

        // Rehash si el algoritmo por defecto cambió o subió el costo.
        // Mismo costo (12) que Auth::hash(), para no generar hashes más débiles.
        if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT, ['cost' => 12])) {
            Database::update(
                'users',
                ['password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 12])],
                'id = :id',
                ['id' => (int) $user['id']]
            );
        }

        Database::update('users', [
            'failed_logins' => 0,
            'locked_until'  => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => Request::ip(),
        ], 'id = :id', ['id' => (int) $user['id']]);

        // Anti session fixation: ID nuevo al autenticar + token CSRF nuevo
        Session::regenerate();
        Csrf::rotate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        Session::set('_auth_fingerprint', self::fingerprint());

        self::$user        = null;
        self::$permissions = null;

        AuditService::log('login', 'auth', null, null, 'Inicio de sesión correcto', [], (int) $user['id']);

        return ['ok' => true, 'message' => '¡Bienvenido/a, ' . $user['name'] . '!'];
    }

    private static function registerFailedAttempt(array $user): void
    {
        $max      = Env::int('LOGIN_MAX_ATTEMPTS', 5);
        $minutes  = Env::int('LOGIN_LOCK_MINUTES', 15);
        $attempts = (int) $user['failed_logins'] + 1;

        $data = ['failed_logins' => $attempts];
        if ($attempts >= $max) {
            $data['locked_until']  = date('Y-m-d H:i:s', time() + ($minutes * 60));
            $data['failed_logins'] = 0;
        }

        Database::update('users', $data, 'id = :id', ['id' => (int) $user['id']]);

        AuditService::log(
            'login_failed',
            'auth',
            'user',
            (int) $user['id'],
            'Intento de acceso fallido (' . $attempts . ')',
            [],
            null
        );
    }

    public static function logout(): void
    {
        if (self::check()) {
            AuditService::log('logout', 'auth', null, null, 'Cierre de sesión');
        }
        Session::forget(self::SESSION_KEY);
        Session::forget('_auth_fingerprint');
        self::$user        = null;
        self::$permissions = null;
        Session::regenerate();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get(self::SESSION_KEY);
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        // El "fingerprint" ata la sesión al navegador que la inició.
        if (Session::get('_auth_fingerprint') !== self::fingerprint()) {
            Session::forget(self::SESSION_KEY);
            return null;
        }

        $user = Database::selectOne(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
               FROM users u
               INNER JOIN roles r ON r.id = u.role_id
              WHERE u.id = :id AND u.active = 1
              LIMIT 1',
            ['id' => (int) $id]
        );

        if ($user === null) {
            Session::forget(self::SESSION_KEY);
            return null;
        }

        unset($user['password']);
        self::$user = $user;

        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user === null ? null : (int) $user['id'];
    }

    public static function name(): string
    {
        return (string) (self::user()['name'] ?? 'Invitado');
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role_slug'] ?? '') === 'admin';
    }

    /** @return array<int,string> */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }

        $user = self::user();
        if ($user === null) {
            return self::$permissions = [];
        }

        $rows = Database::select(
            'SELECT p.slug
               FROM role_permissions rp
               INNER JOIN permissions p ON p.id = rp.permission_id
              WHERE rp.role_id = :role',
            ['role' => (int) $user['role_id']]
        );

        return self::$permissions = array_column($rows, 'slug');
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true;  // el administrador tiene acceso total
        }
        return in_array($permission, self::permissions(), true);
    }

    public static function canAny(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    /** ¿Este usuario puede ver costos y ganancias? */
    public static function canSeeCost(): bool
    {
        return self::can('prices.view_cost');
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    private static function fingerprint(): string
    {
        return hash_hmac('sha256', Request::userAgent(), APP_KEY);
    }
}
