<?php
/**
 * ARCHIVO: app/views/partials/navbar.php
 */

use App\Services\QuoteService;
use App\Services\SettingService;
use App\Services\WhatsAppService;

$companyName = SettingService::companyName();
$logo        = (string) setting('company_logo', '');
?>
<header class="site-header" id="siteHeader">

    <!-- Barra superior de contacto -->
    <div class="topbar d-none d-lg-block">
        <div class="container topbar__inner">
            <div class="topbar__left">
                <?php if (setting('contact_phone')): ?>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('contact_phone'))) ?>">
                        <i class="bi bi-telephone-fill"></i> <?= e(setting('contact_phone')) ?>
                    </a>
                <?php endif; ?>
                <?php if (setting('contact_email')): ?>
                    <a href="mailto:<?= e(setting('contact_email')) ?>">
                        <i class="bi bi-envelope-fill"></i> <?= e(setting('contact_email')) ?>
                    </a>
                <?php endif; ?>
                <?php if (setting('contact_hours')): ?>
                    <span class="topbar__hours">
                        <i class="bi bi-clock-fill"></i> <?= e(str_limit((string) setting('contact_hours'), 60)) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="topbar__right">
                <a href="<?= url('favoritos') ?>" class="topbar__link">
                    <i class="bi bi-heart"></i> Favoritos <span class="badge-count" data-favorites-count>0</span>
                </a>
                <a href="<?= url('cotizador') ?>" class="topbar__link">
                    <i class="bi bi-file-earmark-text"></i> Mi cotización
                    <span class="badge-count" data-quote-count><?= QuoteService::count() ?></span>
                </a>
                <?php foreach ([
                    'social_instagram' => 'bi-instagram',
                    'social_facebook'  => 'bi-facebook',
                    'social_linkedin'  => 'bi-linkedin',
                    'social_youtube'   => 'bi-youtube',
                ] as $key => $icon): ?>
                    <?php if (setting($key)): ?>
                        <a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener" class="topbar__social" aria-label="<?= e($key) ?>">
                            <i class="bi <?= $icon ?>"></i>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Barra principal -->
    <nav class="mainbar" aria-label="Navegación principal">
        <div class="container mainbar__inner">

            <a class="brand" href="<?= url() ?>">
                <?php if ($logo !== ''): ?>
                    <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($companyName) ?>" class="brand__logo">
                <?php else: ?>
                    <span class="brand__mark" aria-hidden="true">
                        <i class="bi bi-truck-front-fill"></i>
                    </span>
                    <span class="brand__text">
                        <strong><?= e($companyName) ?></strong>
                        <small><?= e(str_limit((string) setting('company_slogan', 'Maquinaria y repuestos'), 42)) ?></small>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Buscador global -->
            <form class="searchbox" action="<?= url('buscar') ?>" method="get" role="search" autocomplete="off">
                <i class="bi bi-search searchbox__icon"></i>
                <input type="search" name="q" id="globalSearch" class="searchbox__input"
                       placeholder="Buscá por nombre, código o modelo (ej. 8FG25)"
                       value="<?= e($_GET['q'] ?? '') ?>"
                       aria-label="Buscar en el catálogo">
                <button type="submit" class="searchbox__btn">Buscar</button>
                <div class="searchbox__results" id="searchResults" hidden></div>
            </form>

            <div class="mainbar__actions">
                <?php if (WhatsAppService::isConfigured()): ?>
                    <a class="btn btn-wa d-none d-md-inline-flex" href="<?= e(WhatsAppService::generalLink()) ?>"
                       target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                <?php endif; ?>

                <button class="mainbar__toggle" type="button" id="navToggle"
                        aria-label="Abrir menú" aria-expanded="false" aria-controls="mainMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Menú -->
    <div class="navmenu" id="mainMenu">
        <div class="container navmenu__inner">
            <ul class="navmenu__list">
                <li><a class="<?= active('/', 'is-active') ?>" href="<?= url() ?>">Inicio</a></li>
                <li><a class="<?= active('/maquinaria', 'is-active') ?>" href="<?= url('maquinaria') ?>">Maquinaria</a></li>
                <li><a class="<?= active('/repuestos', 'is-active') ?>" href="<?= url('repuestos') ?>">Repuestos</a></li>
                <li><a class="<?= active('/servicios', 'is-active') ?>" href="<?= url('servicios') ?>">Servicios</a></li>
                <li><a class="<?= active('/financiacion', 'is-active') ?>" href="<?= url('financiacion') ?>">Financiación</a></li>
                <li><a class="<?= active('/nosotros', 'is-active') ?>" href="<?= url('nosotros') ?>">Nosotros</a></li>
                <li><a class="<?= active('/contacto', 'is-active') ?>" href="<?= url('contacto') ?>">Contacto</a></li>
            </ul>

            <div class="navmenu__cta">
                <a href="<?= url('recomendador') ?>" class="navmenu__helper">
                    <i class="bi bi-magic"></i> ¿Qué máquina necesitás?
                </a>
                <a href="<?= url('cotizador') ?>" class="btn btn-accent btn-sm">
                    <i class="bi bi-file-earmark-text"></i> Solicitar cotización
                </a>
            </div>
        </div>
    </div>
</header>
