<?php
/**
 * ARCHIVO: coneccion.php  (raíz del proyecto)
 * ---------------------------------------------------------------------
 * Datos de conexión a MySQL con DOS apartados:
 *
 *     · LOCALHOST (XAMPP)  → se usa cuando abrís el sitio en tu PC
 *     · HOST InfinityFree  → se usa cuando el sitio está publicado
 *
 * El apartado correcto se elige SOLO, mirando el dominio con el que se
 * entró (ver más abajo "Detección de entorno"). No hace falta tocar
 * nada al subir los archivos al hosting.
 *
 * Este archivo devuelve un array y NO imprime nada. Lo consume
 * config/database.php, que le agrega las opciones de PDO.
 *
 * >>> NO se debe acceder a este archivo directamente por el navegador. <<<
 */

declare(strict_types=1);

// Cortafuegos: si alguien entra a https://tudominio/coneccion.php, no
// se ejecuta nada (además el .htaccess ya lo bloquea).
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// =====================================================================
//  1) APARTADO LOCALHOST · XAMPP
//     Valores por defecto de una instalación limpia de XAMPP:
//     usuario "root", sin contraseña.
// =====================================================================
$LOCALHOST = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'sh_servicios',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

// =====================================================================
//  2) APARTADO HOSTING · InfinityFree
//     Copiá estos datos desde el panel de InfinityFree:
//       Panel  →  MySQL Databases
//
//     · MySQL Host Name  → suele ser  sqlXXX.infinityfree.com
//                          (NO uses "localhost" en InfinityFree)
//     · MySQL User Name  → empieza con  epiz_XXXXXXX  o  if0_XXXXXXX
//     · MySQL DB Name    → epiz_XXXXXXX_shservicios
//     · Password         → la de tu cuenta de InfinityFree
// =====================================================================
$INFINITYFREE = [
    'host'     => 'sqlXXX.infinityfree.com',   // <-- CAMBIAR
    'port'     => 3306,
    'database' => 'epiz_XXXXXXX_shservicios',  // <-- CAMBIAR
    'username' => 'epiz_XXXXXXX',              // <-- CAMBIAR
    'password' => 'TU_PASSWORD_INFINITYFREE',  // <-- CAMBIAR
    'charset'  => 'utf8mb4',
];

// =====================================================================
//  Detección de entorno
//  --------------------------------------------------------------------
//  Es "local" cuando:
//    · el dominio es localhost / 127.0.0.1 / ::1
//    · el dominio termina en .local / .test / .localhost
//    · el proyecto está dentro de una carpeta "xampp"
//  En cualquier otro caso se asume que está publicado (InfinityFree).
// =====================================================================
$esLocal = (static function (): bool {
    // Override manual opcional:  SH_ENV=local  ó  SH_ENV=infinityfree
    $forzado = strtolower((string) (getenv('SH_ENV') ?: ($_SERVER['SH_ENV'] ?? '')));
    if ($forzado === 'local' || $forzado === 'xampp') {
        return true;
    }
    if ($forzado === 'infinityfree' || $forzado === 'produccion' || $forzado === 'production') {
        return false;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = explode(':', $host)[0]; // saca el puerto (localhost:8000 -> localhost)

    if ($host === '' && PHP_SAPI === 'cli') {
        // Consultas por línea de comandos (migraciones, seeds). En
        // InfinityFree gratuito no hay acceso por consola, así que si
        // se corre por CLI casi siempre es en tu PC. Se asume local,
        // salvo que sea claramente un Linux de hosting.
        return stripos(BASE_PATH, 'xampp') !== false
            || DIRECTORY_SEPARATOR === '\\'   // Windows -> tu PC
            || !str_contains(strtolower(BASE_PATH), '/home/');
    }

    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
        return true;
    }

    foreach (['.local', '.test', '.localhost'] as $sufijo) {
        if (str_ends_with($host, $sufijo)) {
            return true;
        }
    }

    return stripos(BASE_PATH, 'xampp') !== false;
})();

// Devuelve el apartado que corresponde.
return $esLocal ? $LOCALHOST : $INFINITYFREE;
