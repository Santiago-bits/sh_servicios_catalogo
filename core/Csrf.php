<?php
/**
 * ARCHIVO: core/Csrf.php
 * ---------------------------------------------------------------------
 * Protección CSRF por token de sesión. Todo formulario POST/PUT/DELETE
 * y toda petición AJAX que modifique datos debe incluir el token.
 */

declare(strict_types=1);

namespace Core;

final class Csrf
{
    public const FIELD  = '_token';
    public const HEADER = 'HTTP_X_CSRF_TOKEN';

    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /** Campo oculto listo para pegar dentro de un <form>. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(?string $token): bool
    {
        if ($token === null || $token === '' || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals((string) $_SESSION['_csrf_token'], $token);
    }

    /** Valida la petición actual; corta la ejecución con 419 si falla. */
    public static function verifyRequest(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = $_POST[self::FIELD] ?? $_SERVER[self::HEADER] ?? null;

        if (!self::check(is_string($token) ? $token : null)) {
            self::rotate();

            if (Request::isAjax()) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok'      => false,
                    'message' => 'La sesión expiró o el token de seguridad no es válido. Actualizá la página.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(419);
            Session::flash('danger', 'El token de seguridad expiró. Volvé a enviar el formulario.');
            $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
            header('Location: ' . $referer);
            exit;
        }
    }

    public static function rotate(): void
    {
        unset($_SESSION['_csrf_token']);
    }
}
