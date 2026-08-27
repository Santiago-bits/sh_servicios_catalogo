<?php
/**
 * ARCHIVO: app/views/pages/search.php
 * Resultados de la búsqueda global.
 */
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Búsqueda</span></nav>
        <h1><?= $term !== '' ? 'Resultados para “' . e($term) . '”' : 'Buscador' ?></h1>
        <p>
            <?php if ($term !== ''): ?>
                <?= number_es($results['total']) ?> resultado(s) entre máquinas, repuestos y compatibilidades.
            <?php else: ?>
                Buscá por nombre, código interno, código OEM o modelo de máquina.
            <?php endif; ?>
        </p>

        <form action="<?= url('buscar') ?>" method="get" class="mt-3">
            <div class="input-group input-group-lg" style="max-width:620px">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control border-0" value="<?= e($term) ?>"
                       placeholder="Ej: 8FG25, filtro de aceite, bomba hidráulica" aria-label="Buscar">
                <button class="btn btn-accent" type="submit">Buscar</button>
            </div>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if ($term === ''): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>Escribí qué estás buscando</h3>
                <p>Podés buscar por nombre, código interno, código OEM, código de fabricante o modelo de máquina.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= url('maquinaria') ?>" class="btn btn-accent">Ver maquinaria</a>
                    <a href="<?= url('repuestos') ?>" class="btn btn-outline-accent">Ver repuestos</a>
                </div>
            </div>

        <?php elseif ($results['total'] === 0 && empty($results['brands']) && empty($results['categories'])): ?>
            <div class="empty-state">
                <i class="bi bi-emoji-frown"></i>
                <h3>No encontramos nada para “<?= e($term) ?>”</h3>
                <p>
                    Puede que lo tengamos igual: mandanos el número de parte o el modelo de la máquina
                    y lo buscamos por vos.
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= url('contacto') ?>" class="btn btn-accent"><i class="bi bi-send"></i> Consultar</a>
                    <a href="<?= url('repuestos') ?>" class="btn btn-outline-accent">Ver todos los repuestos</a>
                </div>
            </div>

        <?php else: ?>

            <?php if (!empty($results['brands']) || !empty($results['categories'])): ?>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php foreach ($results['categories'] as $category): ?>
                        <a class="cat-chip" href="<?= e(url(($category['type'] === 'machine' ? 'maquinaria/' : 'repuestos/') . $category['slug'])) ?>">
                            <i class="bi <?= e($category['icon'] ?: 'bi-folder') ?>"></i> <?= e($category['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($results['brands'] as $brand): ?>
                        <a class="cat-chip" href="<?= e(url('maquinaria?marca=' . $brand['slug'])) ?>">
                            <i class="bi bi-award"></i> <?= e($brand['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['machines'])): ?>
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Maquinaria</span>
                        <h2 class="section-title" style="font-size:1.5rem"><?= count($results['machines']) ?> máquina(s)</h2>
                    </div>
                    <a href="<?= e(url('maquinaria?q=' . urlencode($term))) ?>" class="btn btn-ghost btn-sm">Ver en el catálogo</a>
                </div>
                <div class="product-grid mb-5">
                    <?php foreach ($results['machines'] as $product): ?>
                        <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['parts'])): ?>
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Repuestos</span>
                        <h2 class="section-title" style="font-size:1.5rem"><?= count($results['parts']) ?> repuesto(s)</h2>
                    </div>
                    <a href="<?= e(url('repuestos?q=' . urlencode($term))) ?>" class="btn btn-ghost btn-sm">Ver en el catálogo</a>
                </div>
                <div class="product-grid mb-5">
                    <?php foreach ($results['parts'] as $product): ?>
                        <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['compatible'])): ?>
                <div class="alert d-flex align-items-center gap-3 mb-3"
                     style="background:#FFFDF3;border:1px solid #F0E4B0;border-radius:10px">
                    <i class="bi bi-diagram-3-fill fs-3 text-accent"></i>
                    <div>
                        <strong>Repuestos compatibles con <?= e($results['matched_model']) ?></strong>
                        <div class="small text-muted-2">Detectamos que tu búsqueda coincide con un modelo de máquina.</div>
                    </div>
                </div>
                <div class="product-grid">
                    <?php foreach ($results['compatible'] as $product): ?>
                        <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
