<?php
/**
 * ARCHIVO: config/database.php
 * ---------------------------------------------------------------------
 * Parámetros de conexión a MySQL usados por Core\Database (PDO).
 *
 * Las credenciales (host, base, usuario, contraseña) salen de
 * coneccion.php, en la raíz del proyecto, que tiene dos apartados:
 * uno para localhost/XAMPP y otro para el hosting InfinityFree, y
 * elige el correcto según el dominio con el que se entró.
 *
 * Acá sólo se agregan las opciones de PDO y la collation.
 */

declare(strict_types=1);

/** @var array{host:string,port:int,database:string,username:string,password:string,charset:string} $conn */
$conn = require BASE_PATH . '/coneccion.php';

return [
    'driver'   => 'mysql',
    'host'     => $conn['host'],
    'port'     => (int) $conn['port'],
    'database' => $conn['database'],
    'username' => $conn['username'],
    'password' => $conn['password'],
    'charset'  => $conn['charset'] ?? 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',

    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sentencias realmente preparadas del lado del servidor:
        // es la defensa principal contra inyección SQL.
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];
