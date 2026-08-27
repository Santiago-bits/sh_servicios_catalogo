<?php
/**
 * ARCHIVO: app/views/admin/stock/history.php
 */

use App\Services\StockService;
?>
<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-box-seam"></i> <?= e($product['name']) ?></h2>
        <div class="d-flex gap-2">
            <?php if (can('stock.move')): ?>
                <button type="button" class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#stockModal"
                        data-stock-move="<?= (int) $product['id'] ?>"
                        data-product-name="<?= e($product['name']) ?>"
                        data-current-stock="<?= (int) $product['stock'] ?>">
                    <i class="bi bi-plus-slash-minus"></i> Nuevo movimiento
                </button>
            <?php endif; ?>
            <a href="<?= admin_url('stock') ?>" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="card-admin__body">
        <div class="stat-grid" style="margin-bottom:0">
            <div class="stat-card">
                <div class="stat-card__label">Código</div>
                <div class="stat-card__value text-mono" style="font-size:1.15rem"><?= e($product['code']) ?></div>
            </div>
            <div class="stat-card stat-card--info">
                <div class="stat-card__label">Stock total</div>
                <div class="stat-card__value"><?= (int) $product['stock'] ?></div>
            </div>
            <div class="stat-card stat-card--warn">
                <div class="stat-card__label">Reservado</div>
                <div class="stat-card__value"><?= (int) $product['stock_reserved'] ?></div>
            </div>
            <div class="stat-card stat-card--ok">
                <div class="stat-card__label">Disponible</div>
                <div class="stat-card__value"><?= StockService::available($product) ?></div>
                <div class="stat-card__hint">Mínimo: <?= (int) $product['stock_min'] ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($chart['labels'])): ?>
    <div class="card-admin">
        <div class="card-admin__head"><h2><i class="bi bi-graph-up"></i> Evolución del stock</h2></div>
        <div class="card-admin__body">
            <div class="chart-box">
                <canvas data-chart='<?= ejs([
                    'type'   => 'line',
                    'labels' => $chart['labels'],
                    'values' => $chart['values'],
                    'label'  => 'Stock',
                ]) ?>'></canvas>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-list-columns-reverse"></i> Movimientos</h2>
        <span class="text-muted-2 small"><?= count($movements) ?> registro(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <?php if (empty($movements)): ?>
            <div class="text-center py-5 text-muted-2">Todavía no hay movimientos para este repuesto.</div>
        <?php else: ?>
            <div class="table-responsive-admin">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th class="num">Cantidad</th>
                            <th class="num">Antes</th>
                            <th class="num">Después</th>
                            <th>Motivo</th>
                            <th>Referencia</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= e(date_es((string) $movement['created_at'], true)) ?></td>
                            <td>
                                <span class="chip chip--<?= in_array($movement['type'], ['entrada', 'liberacion'], true) ? 'ok' : (in_array($movement['type'], ['salida', 'venta'], true) ? 'danger' : 'neutral') ?>">
                                    <?= e($types[$movement['type']] ?? $movement['type']) ?>
                                </span>
                            </td>
                            <td class="num fw-bold"><?= (int) $movement['quantity'] ?></td>
                            <td class="num text-muted-2"><?= (int) $movement['stock_before'] ?></td>
                            <td class="num"><?= (int) $movement['stock_after'] ?></td>
                            <td class="small text-muted-2"><?= e($movement['reason'] ?? '—') ?></td>
                            <td class="small text-mono"><?= e($movement['reference'] ?? '—') ?></td>
                            <td class="small"><?= e($movement['user_name'] ?? 'Sistema') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (can('stock.move')): ?>
    <?php $view->include('admin/stock/modal'); ?>
<?php endif; ?>
