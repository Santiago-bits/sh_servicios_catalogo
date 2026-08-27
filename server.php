<?php
/**
 * ARCHIVO: server.php
 * ---------------------------------------------------------------------
 * Router para el servidor embebido de PHP. Sirve para probar el sistema
 * SIN Apache, ejecutando desde la carpeta del proyecto:
 *
 *     php -S localhost:8000 server.php
 *
 * y abriendo http://localhost:8000
 *
 * En XAMPP / Apache este archivo NO se usa: ahí manda el .htaccess.
 */

declare(strict_types=1);

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = realpath(__DIR__ . $uri);
$root = realpath(__DIR__);

// Código fuente y archivos sensibles: nunca se sirven por HTTP.
if (
    preg_match('#^/(config|core|app|database|storage|lib)(/|$)#i', $uri)
    || preg_match('#(^|/)(\.env|\.git|coneccion.*\.php|server\.php)($|/)#i', $uri)
) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if (
    $uri !== '/'
    && $path !== false
    && $root !== false
    && is_file($path)
    && str_starts_with($path, $root)
) {

    // Nunca se sirve código PHP ni archivos sensibles como texto.
    $extension  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $prohibidas = ['php', 'phtml', 'phar', 'env', 'sql', 'log', 'ini', 'htaccess'];

    if (in_array($extension, $prohibidas, true) || basename($path)[0] === '.') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    $types = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'map'   => 'application/json',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'pdf'   => 'application/pdf',
        'txt'   => 'text/plain; charset=utf-8',
        'xml'   => 'application/xml; charset=utf-8',
    ];

    header('Content-Type: ' . ($types[$extension] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store'); // en desarrollo conviene no cachear

    readfile($path);
    exit;
}

// Todo lo demás va al front controller.
require __DIR__ . '/index.php';
