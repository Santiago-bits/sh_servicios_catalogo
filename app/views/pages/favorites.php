<?php
/**
 * ARCHIVO: app/views/pages/favorites.php
 */
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Favoritos</span></nav>
        <h1>Mis favoritos</h1>
        <p>Los productos que guardaste quedan en este navegador. Podés compartir el enlace o pedir una cotización.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-heart"></i>
                <h3>Todavía no guardaste nada</h3>
                <p>Tocá el corazón en cualquier máquina o repuesto para guardarlo acá y consultarlo después.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= url('maquinaria') ?>" class="btn btn-accent">Ver maquinaria</a>
                    <a href="<?= url('repuestos') ?>" class="btn btn-outline-accent">Ver repuestos</a>
                </div>
            </div>
        <?php else: ?>
            <div class="catalog-toolbar">
                <span class="catalog-toolbar__count"><strong><?= count($products) ?></strong> producto(s) guardado(s)</span>
                <div class="catalog-toolbar__right">
                    <a href="<?= url('cotizador') ?>" class="btn btn-accent btn-sm">
                        <i class="bi bi-file-earmark-text"></i> Ir a mi cotización
                    </a>
                </div>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
