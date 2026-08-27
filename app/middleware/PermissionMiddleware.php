<?php
/**
 * ARCHIVO: app/middleware/PermissionMiddleware.php
 * ---------------------------------------------------------------------
 * Control de acceso por permiso. Se declara en la ruta como
 * 'permission:machines.edit'. El control es del lado del servidor:
 * ocultar un botón en la vista NO es una medida de seguridad.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuditService;
use Core\Auth;
use Core\Request;
use Core\View;

final class PermissionMiddleware
{
    public function handle(?string $argument = null): void
    {
        // Sin sesión, AuthMiddleware ya redirigió; esto es defensa en profundidad.
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }

        if ($argument === null || $argument === '' || Auth::can($argument)) {
            return;
        }

        AuditService::log(
            'access_denied',
            'security',
            null,
            null,
            'Intento de acceso sin permiso a ' . Request::uri() . ' (requiere: ' . $argument . ')'
        );

        http_response_code(403);

        if (Request::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'No tenés permiso para esta acción.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo View::make('errors/403', [
            'code'     => 403,
            'message'  => 'Tu rol no tiene habilitada esta sección.',
            'settings' => \App\Services\SettingService::all(),
            'flash'    => [],
        ], 'admin-bare');

        exit;
    }
}
