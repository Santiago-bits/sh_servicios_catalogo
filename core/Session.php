<?php
/**
 * ARCHIVO: core/Session.php
 * ---------------------------------------------------------------------
 * Manejo de sesión con cookies endurecidas, rotación periódica del ID
 * y utilidades de flash messages.
 */

declare(strict_types=1);

namespace Core;

final class Session
{
    private const REGENERATE_EVERY = 900; // 15 minutos

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = Env::int('SESSION_LIFETIME', 120) * 60;

        session_name(Env::get('SESSION_NAME', 'shs_session'));

        session_set_cookie_params([
            'lifetime' => 0,                       // cookie de sesión
            'path'     => '/',
            'domain'   => '',
            'secure'   => Env::bool('SESSION_SECURE', false),
            'httponly' => true,                    // inaccesible desde JavaScript
            'samesite' => 'Lax',                   // mitiga CSRF
        ]);

        ini_set('session.use_strict_mode', '1');   // rechaza IDs no generados por el servidor
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_start();

        // Expiración por inactividad
        $now = time();
        if (isset($_SESSION['_last_activity']) && ($now - (int) $_SESSION['_last_activity']) > $lifetime) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = $now;

        // Rotación periódica del ID de sesión (anti session fixation)
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = $now;
        } elseif (($now - (int) $_SESSION['_created']) > self::REGENERATE_EVERY) {
            self::regenerate();
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    // ----------------------------------------------------------------
    // Flash messages
    // ----------------------------------------------------------------

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /** Guarda los datos del formulario para repoblarlo tras un error. */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token']);
        $_SESSION['_old_input'] = $input;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    /** @return array<string,string> */
    public static function errors(): array
    {
        return $_SESSION['_errors'] ?? [];
    }

    /** Token estable por visitante anónimo (favoritos, comparador). */
    public static function visitorToken(): string
    {
        if (empty($_SESSION['_visitor_token'])) {
            $_SESSION['_visitor_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_visitor_token'];
    }
}
