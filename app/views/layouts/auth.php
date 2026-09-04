<?php
/**
 * ARCHIVO: app/views/layouts/auth.php
 * Layout mínimo para la pantalla de acceso al panel.
 *
 * @var \Core\View $view
 */

use App\Services\SettingService;
?>
<!doctype html>
<html lang="es" data-base="<?= e(BASE_URL) ?>">
<head>
    <meta charset="utf-8">
    <script nonce="<?= csp_nonce() ?>">document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111111">
    <title><?= e($pageTitle ?? 'Acceso · ' . SettingService::companyName()) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="auth-body <?= e($bodyClass ?? '') ?>">

<?= $view->section('content') ?>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<script nonce="<?= csp_nonce() ?>">
    window.SHS = {
        baseUrl: <?= js(BASE_URL) ?>,
        csrf: <?= js(csrf_token()) ?>,
        flash: <?= js(array_map(static fn (array $f): array => ['type' => $f['type'], 'message' => $f['message']], $flash ?? [])) ?>
    };
</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
