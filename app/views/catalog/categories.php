<?php
/**
 * ARCHIVO: app/views/catalog/categories.php
 * Portada de Maquinaria / Repuestos: grilla de categorías con productos.
 *
 * @var string $title
 * @var string $lead
 * @var string $base   'maquinaria' | 'repuestos'
 * @var string $type   'machine' | 'spare_part'
 * @var array<int,array<string,mixed>> $categories
 */

$isMachine = $type === 'machine';
$unit      = $isMachine ? 'equipo(s)' : 'repuesto(s)';
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="<?= url() ?>">Inicio</a>
            <span><?= e($title) ?></span>
        </nav>
        <h1><?= e($title) ?></h1>
        <p><?= e($lead) ?></p>
    </div>
</section>

<section class="section" style="padding-top:32px">
    <div class="container">
        <?php if (!$isMachine): ?>
            <form action="<?= url('repuestos') ?>" method="get" class="catalog-search mb-4">
                <div class="input-group">
                    <input type="search" name="q" class="form-control form-control-lg"
                           placeholder="Código, código OEM o modelo de tu máquina" aria-label="Buscar repuesto">
                    <button class="btn btn-accent btn-lg" type="submit"><i class="bi bi-search"></i> Buscar</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="cat-grid">
            <?php foreach ($categories as $category): ?>
                <a class="cat-card reveal" href="<?= e(url($base . '/' . $category['slug'])) ?>">
                    <span class="cat-card__icon<?= !empty($category['image']) ? ' cat-card__icon--img' : '' ?>">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?= e(upload_url($category['image'])) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <i class="bi <?= e($category['icon'] ?: ($isMachine ? 'bi-gear-fill' : 'bi-nut-fill')) ?>"></i>
                        <?php endif; ?>
                    </span>
                    <h2 class="cat-card__title"><?= e($category['name']) ?></h2>
                    <span class="cat-card__count"><?= (int) $category['products_count'] ?> <?= $unit ?></span>
                    <span class="cat-card__arrow">Ver <?= $isMachine ? 'equipos' : 'repuestos' ?> <i class="bi bi-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?= e(url($base . '?todos=1')) ?>" class="btn btn-outline-accent">
                Ver todos los <?= $isMachine ? 'equipos' : 'repuestos' ?> <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
