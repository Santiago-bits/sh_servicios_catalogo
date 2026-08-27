<?php
/**
 * ARCHIVO: app/middleware/AuthMiddleware.php
 * ---------------------------------------------------------------------
 * Exige sesión iniciada para acceder al panel.
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Request;
use Core\Session;

final class AuthMiddleware
{
    public function handle(?string $argument = null): void
    {
        if (Auth::check()) {
            return;
        }

        if (Request::isAjax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sesión expirada. Volvé a iniciar sesión.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Se recuerda a dónde quería ir para volver después del login
        Session::set('_intended_url', Request::url());
        Session::flash('warning', 'Iniciá sesión para acceder al panel.');

        header('Location: ' . BASE_URL . '/admin/login');
        exit;
    }
}
