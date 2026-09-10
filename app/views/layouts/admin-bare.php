<?php
/**
 * ARCHIVO: app/views/layouts/admin-bare.php
 * Layout del panel sin barra lateral (errores 403 / 404 internos).
 *
 * @var \Core\View $view
 */

use App\Services\SettingService;
?>
<!doctype html>
<html lang="es" data-base="<?= e(BASE_URL) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Panel · ' . SettingService::companyName()) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= asset('img/favicon-sh.png') ?>">
    <link rel="shortcut icon" href="<?= asset('img/favicon-sh.png') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body admin-body--bare">
    <div class="admin-bare">
        <?= $view->section('content') ?>
    </div>
</body>
</html>
