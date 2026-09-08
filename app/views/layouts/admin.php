<?php
/**
 * ARCHIVO: app/views/layouts/admin.php
 * Layout del panel administrativo.
 *
 * @var \Core\View $view
 */

use App\Services\SettingService;
use Core\Auth;

$user = Auth::user();

// ---------------------------------------------------------------------
//  PANEL SIMPLIFICADO
//  Se dejó solo lo necesario para un catálogo: productos, su
//  organización, importar/exportar y configuración básica.
//  Las secciones ocultas (Precios, Financiación, Cotizaciones, Stock,
//  Estadísticas, Auditoría, Usuarios, Roles, Alertas) siguen en el
//  código; para reactivar una, volvé a agregar su línea acá y
//  descomentá su ruta en config/routes.php.
// ---------------------------------------------------------------------
$nav  = [
    ['section' => 'General'],
    ['label' => 'Inicio',         'icon' => 'bi-speedometer2',      'path' => '',                'perm' => 'dashboard.view'],

    ['section' => 'Catálogo'],
    ['label' => 'Maquinaria',     'icon' => 'bi-truck-front-fill',  'path' => 'maquinaria',      'perm' => 'machines.view'],
    ['label' => 'Repuestos',      'icon' => 'bi-nut-fill',          'path' => 'repuestos',       'perm' => 'parts.view'],
    ['label' => 'Categorías',     'icon' => 'bi-diagram-3-fill',    'path' => 'categorias',      'perm' => 'categories.manage'],
    ['label' => 'Marcas',         'icon' => 'bi-award-fill',        'path' => 'marcas',          'perm' => 'brands.manage'],
    ['label' => 'Etiquetas',      'icon' => 'bi-tags-fill',         'path' => 'etiquetas',       'perm' => 'tags.manage'],
    ['label' => 'Servicios',      'icon' => 'bi-tools',             'path' => 'servicios',       'perm' => 'services.manage'],

    ['section' => 'Gestión'],
    ['label' => 'Consultas',      'icon' => 'bi-chat-dots-fill',    'path' => 'consultas',       'perm' => 'inquiries.view', 'badge' => $pendingInquiries ?? 0],
    ['label' => 'Importar',       'icon' => 'bi-upload',            'path' => 'importar',        'perm' => 'data.import'],
    ['label' => 'Exportar',       'icon' => 'bi-download',          'path' => 'exportar',        'perm' => 'data.export'],
    ['label' => 'Configuración',  'icon' => 'bi-gear-fill',         'path' => 'configuracion',   'perm' => 'settings.manage'],

    ['section' => 'Seguridad'],
    ['label' => 'Usuarios',       'icon' => 'bi-people-fill',       'path' => 'usuarios',        'perm' => 'users.view'],
    ['label' => 'Roles',          'icon' => 'bi-shield-lock-fill',  'path' => 'roles',           'perm' => 'roles.manage'],
    ['label' => 'Auditoría',      'icon' => 'bi-clipboard-check-fill','path' => 'auditoria',     'perm' => 'audit.view'],
];
?>
<!doctype html>
<html lang="es" data-base="<?= e(BASE_URL) ?>">
<head>
    <meta charset="utf-8">
    <script nonce="<?= csp_nonce() ?>">document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111111">
    <title><?= e($pageTitle ?? 'Panel · ' . SettingService::companyName()) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body <?= e($bodyClass ?? '') ?>">

<div class="admin-shell">

    <!-- Barra lateral -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar__head">
            <button type="button" class="admin-sidebar__close" id="sidebarClose" aria-label="Cerrar menú">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="admin-nav" aria-label="Menú del panel">
            <?php foreach ($nav as $item): ?>
                <?php if (isset($item['section'])): ?>
                    <p class="admin-nav__section"><?= e($item['section']) ?></p>
                <?php elseif (can($item['perm'])): ?>
                    <?php
                    $current  = \Core\Request::uri();
                    $target   = '/admin' . ($item['path'] === '' ? '' : '/' . $item['path']);
                    $isActive = $item['path'] === ''
                        ? $current === '/admin'
                        : str_starts_with($current, $target);
                    ?>
                    <a class="admin-nav__link <?= $isActive ? 'is-active' : '' ?>" href="<?= e(admin_url($item['path'])) ?>">
                        <i class="bi <?= e($item['icon']) ?>"></i>
                        <span><?= e($item['label']) ?></span>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="admin-nav__badge"><?= (int) $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__foot">
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="admin-sidebar__site">
                <i class="bi bi-box-arrow-up-right"></i> Ver el sitio
            </a>
        </div>
    </aside>

    <!-- Contenido -->
    <div class="admin-main">

        <header class="admin-topbar">
            <button type="button" class="admin-topbar__toggle" id="sidebarToggle" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>

            <h1 class="admin-topbar__title"><?= e($adminTitle ?? 'Panel') ?></h1>

            <div class="admin-topbar__actions">
                <div class="dropdown">
                    <button class="admin-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-user__avatar"><?= e(mb_strtoupper(mb_substr((string) ($user['name'] ?? '?'), 0, 1))) ?></span>
                        <span class="admin-user__info">
                            <strong><?= e($user['name'] ?? '') ?></strong>
                            <small><?= e($user['role_name'] ?? '') ?></small>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= admin_url('perfil') ?>"><i class="bi bi-person"></i> Mi perfil</a></li>
                        <li><a class="dropdown-item" href="<?= url() ?>" target="_blank"><i class="bi bi-globe"></i> Ver el sitio</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= admin_url('logout') ?>" method="post" class="px-2">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <?= $view->section('content') ?>
        </div>
    </div>
</div>

<div class="admin-backdrop" id="adminBackdrop"></div>
<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<script nonce="<?= csp_nonce() ?>">
    window.SHS = {
        baseUrl: <?= js(BASE_URL) ?>,
        csrf: <?= js(csrf_token()) ?>,
        flash: <?= js(array_map(static fn (array $f): array => ['type' => $f['type'], 'message' => $f['message']], $flash ?? [])) ?>
    };
</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" defer></script>
<?php /* Chart.js sólo lo usaba la sección Estadísticas (oculta). Si se
       reactiva, volver a agregar: vendor/chartjs/chart.umd.min.js */ ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/admin.js') ?>" defer></script>
<?= $view->section('scripts') ?>
</body>
</html>
