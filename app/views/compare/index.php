<?php
/**
 * ARCHIVO: app/views/compare/index.php
 * Comparador de máquinas.
 */

use App\Services\PriceService;
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
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-bar-chart-steps"></i>
                <h3>Todavía no elegiste equipos para comparar</h3>
                <p>Entrá al catálogo y tocá el ícono de comparar en las máquinas que te interesen.</p>
                <a href="<?= url('maquinaria') ?>" class="btn btn-accent">Ver maquinaria</a>
            </div>
        <?php else: ?>

            <div class="table-responsive-admin">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;width:210px">Característica</th>
                            <?php foreach ($products as $product): ?>
                                <th>
                                    <img src="<?= e(upload_url($product['thumb'] ?? $product['image'])) ?>"
                                         alt="<?= e($product['name']) ?>"
                                         style="width:100%;max-width:160px;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:8px">
                                    <span class="d-block" style="font-size:.92rem;line-height:1.3"><?= e($product['name']) ?></span>
                                    <small class="text-muted-2 d-block mb-2"><?= e($product['code']) ?></small>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= e(machine_url($product)) ?>" class="btn btn-accent btn-sm">Ver ficha</a>
                                        <button type="button" class="btn btn-ghost btn-sm" data-compare-toggle="<?= (int) $product['id'] ?>"
                                                title="Quitar del comparador">
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
                                <th scope="row"><?= e($row['label']) ?></th>
                                <?php foreach ($row['cells'] as $cell): ?>
                                    <td><?= e($cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <th scope="row">Acciones</th>
                            <?php foreach ($products as $product): ?>
                                <td>
                                    <div class="d-flex flex-column gap-1 align-items-center">
                                        <button type="button" class="btn btn-accent btn-sm w-100" data-quote-add="<?= (int) $product['id'] ?>">
                                            <i class="bi bi-file-earmark-plus"></i> Cotizar
                                        </button>
                                        <a href="<?= e(App\Services\WhatsAppService::machineLink($product)) ?>"
                                           target="_blank" rel="noopener" class="btn btn-wa btn-sm w-100">
                                            <i class="bi bi-whatsapp"></i> Consultar
                                        </a>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if (count($products) < $max): ?>
                <div class="text-center mt-4">
                    <p class="text-muted-2">Podés agregar <?= $max - count($products) ?> equipo(s) más a la comparación.</p>
                    <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent">
                        <i class="bi bi-plus-lg"></i> Agregar otra máquina
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
