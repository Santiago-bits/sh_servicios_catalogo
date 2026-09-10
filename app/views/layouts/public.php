<?php
/**
 * ARCHIVO: app/views/layouts/public.php
 * Layout del sitio público.
 *
 * @var \Core\View $view
 * @var array<string,string> $settings
 */

use App\Services\QuoteService;
use App\Services\SettingService;
use App\Services\WhatsAppService;

$companyName = SettingService::companyName();
$title       = $pageTitle ?? $companyName;
$description = $metaDescription ?? (string) setting('seo_description', '');
$robotsMeta  = $robots ?? 'index, follow';
?>
<!doctype html>
<html lang="es" data-base="<?= e(BASE_URL) ?>">
<head>
    <meta charset="utf-8">
    <script nonce="<?= csp_nonce() ?>">document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111111">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="<?= e($robotsMeta) ?>">
    <?php if (!empty($canonical)): ?>
        <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($companyName) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical ?? url()) ?>">
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= asset('img/favicon-sh.png') ?>">
    <link rel="shortcut icon" href="<?= asset('img/favicon-sh.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/favicon-sh.png') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">

    <?php $gaId = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($settings['google_analytics'] ?? '')); ?>
    <?php if ($gaId !== ''): ?>
        <!-- Google Analytics (GA4) — ID configurado en Panel · Configuración · SEO -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $gaId ?>"></script>
        <script nonce="<?= csp_nonce() ?>">
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '<?= $gaId ?>');
        </script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<a class="skip-link" href="#contenido">Ir al contenido</a>

<?php $view->partial('navbar'); ?>

<main id="contenido">
    <?= $view->section('content') ?>
</main>

<?php $view->partial('footer'); ?>

<!-- Botón flotante de WhatsApp -->
<?php if (WhatsAppService::isConfigured()): ?>
    <a class="wa-float" href="<?= e(WhatsAppService::generalLink()) ?>" target="_blank" rel="noopener"
       aria-label="Consultar por WhatsApp">
        <i class="bi bi-whatsapp"></i>
        <span class="wa-float__label">Consultanos</span>
    </a>
<?php endif; ?>

<!-- Barra flotante del comparador -->
<div class="compare-bar" id="compareBar" hidden>
    <div class="container compare-bar__inner">
        <div class="compare-bar__items" id="compareItems"></div>
        <div class="compare-bar__actions">
            <button type="button" class="btn btn-ghost btn-sm" id="compareClear">Vaciar</button>
            <a href="<?= url('comparar') ?>" class="btn btn-accent btn-sm" id="compareGo">
                Comparar <span class="badge-count" id="compareCount">0</span>
            </a>
        </div>
    </div>
</div>

<!-- Contenedor de notificaciones -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script nonce="<?= csp_nonce() ?>">
    window.SHS = {
        baseUrl: <?= js(BASE_URL) ?>,
        csrf: <?= js(csrf_token()) ?>,
        compareMax: <?= (int) setting('compare_max', 3) ?>,
        quoteCount: <?= QuoteService::count() ?>,
        flash: <?= js(array_map(static fn (array $f): array => ['type' => $f['type'], 'message' => $f['message']], $flash ?? [])) ?>
    };
</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?= $view->section('scripts') ?>
</body>
</html>
