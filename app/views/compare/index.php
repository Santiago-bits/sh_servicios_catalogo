<?php
/**
 * ARCHIVO: app/views/compare/index.php
 * Comparador de máquinas.
 */

use App\Services\WhatsAppService;

$count = count($products);
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Comparador</span></nav>
        <h1>Comparador de máquinas</h1>
        <p>Compará hasta <?= (int) $max ?> equipos lado a lado: capacidad, altura, motor, dimensiones y precio.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($count === 0): ?>
            <div class="empty-state">
                <i class="bi bi-bar-chart-steps"></i>
                <h3>Todavía no elegiste equipos para comparar</h3>
                <p>Entrá al catálogo y tocá el ícono de comparar en las máquinas que te interesen.</p>
                <a href="<?= url('maquinaria') ?>" class="btn btn-accent">Ver maquinaria</a>
            </div>
        <?php else: ?>

            <?php if ($count === 1): ?>
                <div class="compare-hint">
                    <i class="bi bi-info-circle"></i>
                    Agregá al menos otra máquina para verlas enfrentadas.
                </div>
            <?php endif; ?>

            <div class="compare-wrap">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th class="compare-table__label">
                                <span class="eyebrow">Comparando <?= $count ?> de <?= (int) $max ?></span>
                            </th>
                            <?php foreach ($products as $product): ?>
                                <th>
                                    <img src="<?= e(upload_url($product['thumb'] ?? $product['image'])) ?>"
                                         alt="<?= e($product['name']) ?>" class="compare-table__img">
                                    <span class="compare-table__name"><?= e($product['name']) ?></span>
                                    <small class="text-muted-2 d-block"><?= e($product['code']) ?></small>
                                    <div class="compare-table__head-actions">
                                        <a href="<?= e(machine_url($product)) ?>" class="btn btn-accent btn-sm">Ver ficha</a>
                                        <button type="button" class="btn btn-ghost btn-sm compare-table__remove"
                                                data-compare-toggle="<?= (int) $product['id'] ?>" title="Quitar del comparador">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <th scope="row" class="compare-table__label"><?= e($row['label']) ?></th>
                                <?php foreach ($row['cells'] as $i => $cell): ?>
                                    <td class="<?= isset($row['bestIdx']) && $row['bestIdx'] === $i ? 'is-best' : '' ?>">
                                        <?= e($cell) ?>
                                        <?php if (isset($row['bestIdx']) && $row['bestIdx'] === $i): ?>
                                            <i class="bi bi-check-circle-fill compare-table__best" title="Mejor valor"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <th scope="row" class="compare-table__label">Acciones</th>
                            <?php foreach ($products as $product): ?>
                                <td>
                                    <div class="compare-table__actions">
                                        <button type="button" class="btn btn-accent btn-sm" data-quote-add="<?= (int) $product['id'] ?>">
                                            <i class="bi bi-file-earmark-plus"></i> Cotizar
                                        </button>
                                        <a href="<?= e(WhatsAppService::machineLink($product)) ?>"
                                           target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                                            <i class="bi bi-whatsapp"></i> Consultar
                                        </a>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($count < $max): ?>
                <div class="text-center mt-4">
                    <p class="text-muted-2">Podés agregar <?= $max - $count ?> equipo(s) más a la comparación.</p>
                    <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent">
                        <i class="bi bi-plus-lg"></i> Agregar otra máquina
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
