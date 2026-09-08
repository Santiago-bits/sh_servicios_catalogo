<?php
/**
 * ARCHIVO: index.php  (raíz del proyecto)
 * ---------------------------------------------------------------------
 * Punto de entrada único de la aplicación (Front Controller).
 * Apache reescribe todas las peticiones hacia acá (ver .htaccess).
 *
 * El proyecto ya no usa la subcarpeta /public: la raíz del proyecto es
 * también la raíz web. Los archivos estáticos (assets/, uploads/) y el
 * .htaccess con la protección del código fuente están en esta carpeta.
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_START', microtime(true));

require BASE_PATH . '/config/config.php';

use Core\Csrf;
use Core\Request;
use Core\Router;
use Core\View;
use App\Services\AuditService;
use App\Services\ExchangeRateService;
use App\Services\SettingService;

// ---------------------------------------------------------------------
// Cabeceras de seguridad
// ---------------------------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 0'); // obsoleto y contraproducente; se usa CSP
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header_remove('X-Powered-By');

// HSTS: sólo cuando la respuesta ya viaja por HTTPS (no rompe XAMPP local).
$overHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if ($overHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSP: los <script> inline propios se autorizan con nonce (CSP_NONCE),
// así se saca 'unsafe-inline' de script-src. style-src lo conserva porque
// el proyecto usa muchos style="" en las vistas.
header(
    "Content-Security-Policy: default-src 'self'; " .
    "img-src 'self' data: blob: https:; " .
    "media-src 'self' https:; " .
    "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.google.com https://maps.google.com; " .
    "script-src 'self' 'nonce-" . CSP_NONCE . "'; " .
    "style-src 'self' 'unsafe-inline'; " .
    "font-src 'self' data:; " .
    "connect-src 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'self'; " .
    "base-uri 'self'; " .
    "object-src 'none'"
);

// ---------------------------------------------------------------------
// CSRF en toda petición que modifique estado
// ---------------------------------------------------------------------
Csrf::verifyRequest();

// ---------------------------------------------------------------------
// Modo mantenimiento (el panel sigue accesible)
// ---------------------------------------------------------------------
$uri = Request::uri();

if (SettingService::get('maintenance_mode', '0') === '1' && !str_starts_with($uri, '/admin')) {
    http_response_code(503);
    echo View::make('errors/maintenance', ['settings' => SettingService::all(), 'flash' => []], 'public');
    exit;
}

// ---------------------------------------------------------------------
// Datos compartidos con todas las vistas
// ---------------------------------------------------------------------
View::share('settings', SettingService::all());
View::share('currentUri', $uri);

// Cotización del dólar automática (lanacion.com.ar). Sólo sale a
// internet cuando venció el TTL configurado y nunca rompe la página.
ExchangeRateService::refreshIfStale();

// Auditoría: se conserva sólo la última semana, se revisa una vez por día.
AuditService::purgeIfDue();

// ---------------------------------------------------------------------
// Enrutamiento
// ---------------------------------------------------------------------
$router = new Router();
require CONFIG_PATH . '/routes.php';

try {
    $router->dispatch(Request::method(), $uri);
} catch (Throwable $e) {
    error_log('[APP] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    if (APP_DEBUG) {
        http_response_code(500);
        echo '<pre style="background:#111;color:#F5C400;padding:24px;font:14px/1.6 monospace;white-space:pre-wrap">';
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
        exit;
    }

    http_response_code(500);
    echo View::make('errors/500', ['settings' => SettingService::all(), 'flash' => []], 'public');
}
