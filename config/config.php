<?php
/**
 * ARCHIVO: config/config.php
 * ---------------------------------------------------------------------
 * Bootstrap de la aplicación: constantes, autoload, entorno, errores,
 * zona horaria y arranque de sesión segura.
 *
 * Este archivo NO imprime nada y no debe ser accedido directamente.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('APP_PATH',      BASE_PATH . '/app');
define('CORE_PATH',     BASE_PATH . '/core');
define('CONFIG_PATH',   BASE_PATH . '/config');
define('VIEW_PATH',     APP_PATH  . '/views');
// El proyecto ya no usa la subcarpeta /public: la raíz web es BASE_PATH.
define('PUBLIC_PATH',   BASE_PATH);
define('UPLOAD_PATH',   PUBLIC_PATH . '/uploads');
define('STORAGE_PATH',  BASE_PATH . '/storage');
define('LIB_PATH',      BASE_PATH . '/lib');
define('DATABASE_PATH', BASE_PATH . '/database');

// ---------------------------------------------------------------------
// Autoload PSR-4 simplificado (sin Composer)
// ---------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $map = [
        'Core\\'            => CORE_PATH . '/',
        'App\\Controllers\\'=> APP_PATH  . '/controllers/',
        'App\\Models\\'     => APP_PATH  . '/models/',
        'App\\Services\\'   => APP_PATH  . '/services/',
        'App\\Middleware\\' => APP_PATH  . '/middleware/',
        'Lib\\'             => LIB_PATH  . '/',
    ];

    foreach ($map as $prefix => $dir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $parts    = explode('/', $relative);
        $fileName  = array_pop($parts);

        // Se prueba primero la ruta tal cual (App\Controllers\Admin\X →
        // controllers/Admin/X.php) y después con las carpetas en minúscula
        // (controllers/admin/X.php), que es como está organizado el proyecto.
        $candidates = [
            $dir . ($parts === [] ? '' : implode('/', $parts) . '/') . $fileName . '.php',
            $dir . ($parts === [] ? '' : implode('/', array_map('strtolower', $parts)) . '/') . $fileName . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ---------------------------------------------------------------------
// Variables de entorno (.env)
// ---------------------------------------------------------------------
require_once CORE_PATH . '/Env.php';
Core\Env::load(BASE_PATH . '/.env');

// ---------------------------------------------------------------------
// Errores: en desarrollo se muestran, en producción se registran
// ---------------------------------------------------------------------
$debug = Core\Env::bool('APP_DEBUG', false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

if (!is_dir(STORAGE_PATH . '/logs')) {
    @mkdir(STORAGE_PATH . '/logs', 0775, true);
}

define('APP_DEBUG', $debug);
define('APP_ENV',   Core\Env::get('APP_ENV', 'production'));
define('APP_NAME',  Core\Env::get('APP_NAME', 'SH Servicios'));
define('APP_KEY',   Core\Env::get('APP_KEY', 'sh-servicios-default-key'));

date_default_timezone_set(Core\Env::get('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'));
setlocale(LC_ALL, 'es_AR.UTF-8', 'es_AR', 'Spanish_Argentina', 'es');
mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------------
// URL base: detección automática si APP_URL está vacío
// ---------------------------------------------------------------------
$appUrl = trim((string) Core\Env::get('APP_URL', ''));
if ($appUrl === '') {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appUrl = $scheme . '://' . $host . Core\Request::basePath();
}
define('BASE_URL',  rtrim($appUrl, '/'));
define('ASSET_URL', BASE_URL . '/assets');

require_once APP_PATH . '/helpers/functions.php';

// ---------------------------------------------------------------------
// Sesión segura
// ---------------------------------------------------------------------
Core\Session::start();
