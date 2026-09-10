<?php
/**
 * ARCHIVO: app/views/partials/navbar.php
 * Header de una sola fila: logo · menú · buscador colapsable · CTA.
 */

use App\Services\SettingService;

$companyName = SettingService::companyName();
$logo        = (string) setting('company_logo', '');

// "Máquinas usadas" es el listado de maquinaria filtrado por condición.
$usadasActive = is_current('/maquinaria') && ($_GET['condicion'] ?? '') === 'usado';

$navLinks = [
    ['/', 'Inicio'],
    ['/maquinaria', 'Maquinaria'],
    ['/maquinaria?condicion=usado', 'Máquinas usadas'],
    ['/repuestos', 'Repuestos'],
    ['/servicios', 'Servicios'],
    ['/contacto', 'Contacto'],
];
?>
<header class="site-header" id="siteHeader">
    <nav class="mainbar" aria-label="Navegación principal">
        <div class="container mainbar__inner">

            <a class="brand" href="<?= url() ?>">
                <?php if ($logo !== ''): ?>
                    <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($companyName) ?>" class="brand__logo">
                <?php else: ?>
                    <span class="brand__mark" aria-hidden="true"><i class="bi bi-truck-front-fill"></i></span>
                    <span class="brand__text">
                        <strong><?= e($companyName) ?></strong>
                        <small><?= e(str_limit((string) setting('company_slogan', 'Maquinaria y repuestos'), 42)) ?></small>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Menú: inline en escritorio, panel lateral en móvil -->
            <div class="navmenu" id="mainMenu">
                <ul class="navmenu__list">
                    <?php foreach ($navLinks as [$path, $label]): ?>
                        <?php
                        if ($path === '/maquinaria') {
                            $isActive = is_current('/maquinaria') && !$usadasActive;
                        } elseif ($path === '/maquinaria?condicion=usado') {
                            $isActive = $usadasActive;
                        } else {
                            $isActive = is_current($path);
                        }
                        ?>
                        <li><a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e(url(ltrim($path, '/'))) ?>"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= url('cotizador') ?>" class="btn btn-accent btn-sm navmenu__cta">
                    <i class="bi bi-file-earmark-text"></i> Ver cotización
                </a>
            </div>

            <div class="mainbar__actions">
                <!-- Buscador colapsable -->
                <div class="searchbox" id="searchBox">
                    <button type="button" class="searchbox__toggle" id="searchToggle"
                            aria-label="Buscar" aria-expanded="false">
                        <i class="bi bi-search"></i>
                    </button>
                    <form class="searchbox__form" action="<?= url('buscar') ?>" method="get" role="search" autocomplete="off">
                        <input type="search" name="q" id="globalSearch" class="searchbox__input"
                               placeholder="Buscá por nombre, código o modelo"
                               value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar en el catálogo">
                        <button type="submit" class="searchbox__btn" aria-label="Buscar"><i class="bi bi-arrow-right"></i></button>
                        <div class="searchbox__results" id="searchResults" hidden></div>
                    </form>
                </div>

                <a href="<?= url('cotizador') ?>" class="btn btn-accent btn-sm mainbar__cta">
                    <i class="bi bi-file-earmark-text"></i> Ver cotización
                </a>

                <button class="mainbar__toggle" type="button" id="navToggle"
                        aria-label="Abrir menú" aria-expanded="false" aria-controls="mainMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>
</header>
