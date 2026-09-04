<?php
/**
 * ARCHIVO: compatibilidad.php  (raíz del proyecto)
 * ---------------------------------------------------------------------
 * Diagnóstico de compatibilidad del servidor.
 *
 * Subí el sistema, entrá a  https://tudominio/compatibilidad.php  y te
 * dice exactamente qué funciona y qué no en ese hosting.
 *
 * >>> BORRÁ ESTE ARCHIVO CUANDO TERMINES DE INSTALAR. <<<
 * No expone datos sensibles, pero no hay razón para dejarlo publicado.
 */

declare(strict_types=1);

// El proyecto ya no usa /public: este archivo está en la raíz.
$root = __DIR__;

// En producción no se sirve salvo que se pida a propósito con ?ok=1,
// así un escáner que pegue en /compatibilidad.php no obtiene nada.
$envFile = @file_get_contents($root . '/.env');
$isProd  = is_string($envFile) && preg_match('/^\s*APP_ENV\s*=\s*production\s*$/mi', $envFile);
if ($isProd && ($_GET['ok'] ?? '') !== '1') {
    http_response_code(404);
    exit('No encontrado. (Si estás instalando, entrá con ?ok=1 y borrá este archivo al terminar.)');
}

/** @return array{ok:bool,label:string,detail:string,level:string} */
function check(string $label, bool $ok, string $okText, string $failText, string $level = 'critico'): array
{
    return [
        'ok'     => $ok,
        'label'  => $label,
        'detail' => $ok ? $okText : $failText,
        'level'  => $level,
    ];
}

$phpOk = PHP_VERSION_ID >= 80100;

$checks = [
    'Servidor' => [
        check(
            'Versión de PHP',
            $phpOk,
            'PHP ' . PHP_VERSION . ' — correcto',
            'PHP ' . PHP_VERSION . ' — el sistema necesita 8.1 o superior. Cambiá la versión en el panel del hosting.'
        ),
        check(
            'PDO MySQL',
            extension_loaded('pdo_mysql'),
            'Disponible',
            'FALTA. Sin esto el sistema no puede conectarse a la base de datos.'
        ),
        check(
            'mbstring',
            extension_loaded('mbstring'),
            'Disponible',
            'FALTA. Los textos con acentos se van a cortar mal.'
        ),
        check(
            'Reescritura de URLs (mod_rewrite)',
            !function_exists('apache_get_modules') || in_array('mod_rewrite', apache_get_modules(), true),
            'Disponible o no detectable (probalo entrando a /maquinaria)',
            'No detectado. Sin esto sólo funciona la portada.',
            'importante'
        ),
    ],

    'Funciones que se adaptan si faltan' => [
        check(
            'GD (imágenes)',
            extension_loaded('gd'),
            'Disponible: miniaturas, reescalado y fotos en el PDF funcionan completos.',
            'NO disponible. El sistema sigue andando: las imágenes se guardan tal cual (sin miniatura ni reescalado) y los PDF salen sin fotos.',
            'degradable'
        ),
        check(
            'ZipArchive (Excel)',
            class_exists('ZipArchive'),
            'Disponible: la exportación a .xlsx funciona.',
            'NO disponible. La exportación a Excel cae automáticamente a CSV (se abre igual en Excel).',
            'degradable'
        ),
        check(
            'fileinfo (validación de subidas)',
            extension_loaded('fileinfo'),
            'Disponible: se valida el tipo real de cada archivo subido.',
            'NO disponible. Se valida sólo por extensión: subí archivos únicamente de fuentes confiables.',
            'degradable'
        ),
        check(
            'iconv (acentos en el PDF)',
            function_exists('iconv'),
            'Disponible.',
            'NO disponible. Los acentos pueden verse raros en los PDF.',
            'degradable'
        ),
        check(
            'Función mail()',
            function_exists('mail') && !in_array('mail', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true),
            'Habilitada. Podés usar MAIL_MAILER=mail.',
            'Deshabilitada (normal en hosting gratuito). Usá MAIL_MAILER=api con Brevo/SendGrid, o dejá "log": las consultas se guardan igual en el panel.',
            'degradable'
        ),
        check(
            'Conexiones salientes (SMTP / API de correo)',
            function_exists('stream_socket_client') || function_exists('curl_init'),
            'Disponibles. Ojo: muchos hostings gratuitos igual bloquean el puerto 587.',
            'Bloqueadas. No vas a poder enviar correos desde el servidor.',
            'degradable'
        ),
    ],

    'Permisos de escritura' => [
        check(
            'uploads',
            is_writable($root . '/uploads'),
            'Escribible.',
            'SIN permiso de escritura. No vas a poder subir imágenes. Poné permiso 755 o 777 en esa carpeta.'
        ),
        check(
            'storage/logs',
            is_writable($root . '/storage/logs'),
            'Escribible.',
            'SIN permiso de escritura. No se van a registrar los errores.',
            'importante'
        ),
        check(
            'storage/cache',
            is_writable($root . '/storage/cache'),
            'Escribible.',
            'SIN permiso de escritura. La importación de CSV no va a funcionar.',
            'importante'
        ),
    ],
];

// --- Configuración y base de datos -----------------------------------
$envExists = is_file($root . '/.env');
$dbStatus  = ['ok' => false, 'msg' => 'No se pudo probar (falta el archivo .env).'];

if ($envExists) {
    try {
        define('BASE_PATH', $root);
        require $root . '/config/config.php';

        $config = require $root . '/config/database.php';
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']),
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );

        $tables   = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
        $products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $users    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        $dbStatus = [
            'ok'  => $tables >= 30,
            'msg' => $tables >= 30
                ? sprintf('Conectado. %d tablas, %d productos, %d usuario(s).', $tables, $products, $users)
                : sprintf('Conectado, pero sólo hay %d tablas. ¿Importaste database/database.sql?', $tables),
        ];
    } catch (Throwable $e) {
        $dbStatus = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
    }
}

// --- Protección del código fuente ------------------------------------
$exposed = [];
foreach (['/.env', '/config/database.php', '/database/database.sql'] as $path) {
    if (is_file($root . $path)) {
        $exposed[] = $path;
    }
}

$totals = ['ok' => 0, 'fail' => 0, 'degraded' => 0];
foreach ($checks as $group) {
    foreach ($group as $c) {
        if ($c['ok']) {
            $totals['ok']++;
        } elseif ($c['level'] === 'degradable') {
            $totals['degraded']++;
        } else {
            $totals['fail']++;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Diagnóstico de compatibilidad · SH Servicios</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#F1F2F4;color:#252525;
         font:15px/1.6 "Segoe UI",Roboto,Arial,sans-serif;padding:28px 16px}
    .wrap{max-width:900px;margin:0 auto}
    header{background:#111;color:#fff;padding:26px 28px;border-radius:10px 10px 0 0;position:relative;overflow:hidden}
    header::after{content:'';position:absolute;inset:auto 0 0 0;height:5px;
        background:repeating-linear-gradient(135deg,#F5C400 0 14px,transparent 14px 28px)}
    header h1{margin:0;font-size:1.3rem;text-transform:uppercase;letter-spacing:.05em}
    header p{margin:6px 0 0;color:#9A9A9A;font-size:.9rem}
    .card{background:#fff;border:1px solid #E2E4E8;border-top:0;padding:22px 28px}
    .card:last-of-type{border-radius:0 0 10px 10px}
    h2{font-size:.8rem;text-transform:uppercase;letter-spacing:.1em;color:#6B6B6B;
       margin:0 0 14px;padding-bottom:8px;border-bottom:2px solid #EFF1F4}
    .row{display:flex;gap:12px;padding:11px 0;border-bottom:1px solid #F3F4F6;align-items:flex-start}
    .row:last-child{border-bottom:0}
    .dot{width:22px;height:22px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
         font-weight:800;color:#fff;font-size:.75rem;margin-top:2px}
    .ok .dot{background:#2E9E5B}.warn .dot{background:#E08A00}.bad .dot{background:#C6402F}
    .row strong{display:block;font-size:.95rem}
    .row span{color:#6B6B6B;font-size:.86rem}
    .summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:4px}
    .tile{padding:16px 18px;border-radius:8px;border-left:5px solid}
    .tile b{display:block;font-size:1.7rem;line-height:1.1}
    .tile small{color:#6B6B6B;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em}
    .t-ok{background:#F1FAF4;border-color:#2E9E5B}
    .t-warn{background:#FEF8EC;border-color:#E08A00}
    .t-bad{background:#FDF2F0;border-color:#C6402F}
    .note{padding:14px 16px;border-radius:8px;font-size:.88rem;margin-top:14px}
    .note-bad{background:#FDF2F0;border:1px solid #F0C4BC}
    .note-ok{background:#F1FAF4;border:1px solid #BFE4CD}
    code{background:#F1F2F4;padding:2px 6px;border-radius:4px;font-size:.85em}
    .footer{text-align:center;color:#6B6B6B;font-size:.82rem;margin-top:18px}
</style>
</head>
<body>
<div class="wrap">

    <header>
        <h1>Diagnóstico de compatibilidad</h1>
        <p>SH Servicios · <?= date('d/m/Y H:i') ?> · <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'servidor desconocido', ENT_QUOTES) ?></p>
    </header>

    <div class="card">
        <div class="summary">
            <div class="tile t-ok"><b><?= $totals['ok'] ?></b><small>Funcionan</small></div>
            <div class="tile t-warn"><b><?= $totals['degraded'] ?></b><small>Modo reducido</small></div>
            <div class="tile t-bad"><b><?= $totals['fail'] ?></b><small>Bloqueantes</small></div>
        </div>

        <?php if ($totals['fail'] === 0): ?>
            <div class="note note-ok">
                <strong>El sistema puede funcionar en este servidor.</strong>
                <?php if ($totals['degraded'] > 0): ?>
                    Hay <?= $totals['degraded'] ?> función(es) en modo reducido — mirá el detalle abajo:
                    el sistema se adapta solo, no se rompe nada.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="note note-bad">
                <strong>Hay <?= $totals['fail'] ?> problema(s) bloqueante(s).</strong>
                Resolvelos antes de seguir: están marcados en rojo.
            </div>
        <?php endif; ?>
    </div>

    <?php foreach ($checks as $groupName => $items): ?>
        <div class="card">
            <h2><?= htmlspecialchars($groupName, ENT_QUOTES) ?></h2>
            <?php foreach ($items as $c): ?>
                <?php $cls = $c['ok'] ? 'ok' : ($c['level'] === 'degradable' ? 'warn' : 'bad'); ?>
                <div class="row <?= $cls ?>">
                    <span class="dot"><?= $c['ok'] ? '✓' : ($c['level'] === 'degradable' ? '!' : '×') ?></span>
                    <div>
                        <strong><?= htmlspecialchars($c['label'], ENT_QUOTES) ?></strong>
                        <span><?= htmlspecialchars($c['detail'], ENT_QUOTES) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="card">
        <h2>Configuración y base de datos</h2>

        <div class="row <?= $envExists ? 'ok' : 'bad' ?>">
            <span class="dot"><?= $envExists ? '✓' : '×' ?></span>
            <div>
                <strong>Archivo .env</strong>
                <span><?= $envExists
                    ? 'Encontrado.'
                    : 'No existe. Copiá .env.example a .env y cargá los datos de tu base de datos.' ?></span>
            </div>
        </div>

        <div class="row <?= $dbStatus['ok'] ? 'ok' : 'bad' ?>">
            <span class="dot"><?= $dbStatus['ok'] ? '✓' : '×' ?></span>
            <div>
                <strong>Conexión a la base de datos</strong>
                <span><?= htmlspecialchars($dbStatus['msg'], ENT_QUOTES) ?></span>
            </div>
        </div>

        <div class="row <?= $exposed === [] ? 'ok' : 'warn' ?>">
            <span class="dot"><?= $exposed === [] ? '✓' : '!' ?></span>
            <div>
                <strong>Protección del código fuente</strong>
                <span>
                    <?php if ($exposed === []): ?>
                        Los archivos sensibles no están en la carpeta pública.
                    <?php else: ?>
                        Comprobá manualmente que estas URLs den error 403 o 404:
                        <?php foreach ($exposed as $p): ?>
                            <code><?= htmlspecialchars($p, ENT_QUOTES) ?></code>
                        <?php endforeach; ?>
                        (el archivo <code>.htaccess</code> de la raíz debería bloquearlas).
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Datos útiles del servidor</h2>
        <div class="row ok">
            <span class="dot">i</span>
            <div>
                <strong>Límites de PHP</strong>
                <span>
                    Subida máxima: <code><?= ini_get('upload_max_filesize') ?></code> ·
                    POST máximo: <code><?= ini_get('post_max_size') ?></code> ·
                    Memoria: <code><?= ini_get('memory_limit') ?></code> ·
                    Tiempo máximo: <code><?= ini_get('max_execution_time') ?>s</code>
                </span>
            </div>
        </div>
        <div class="row ok">
            <span class="dot">i</span>
            <div>
                <strong>Funciones deshabilitadas por el hosting</strong>
                <span><code><?= htmlspecialchars(ini_get('disable_functions') ?: 'ninguna', ENT_QUOTES) ?></code></span>
            </div>
        </div>
    </div>

    <p class="footer">
        Cuando termines de instalar, <strong>borrá este archivo</strong> (<code>compatibilidad.php</code>).
    </p>
</div>
</body>
</html>
