<?php
/**
 * ARCHIVO: app/views/admin/stock/index.php
 */

use App\Services\StockService;
?>
<div class="stat-grid">
    <div class="stat-card <?= $lowCount > 0 ? 'stat-card--danger' : 'stat-card--ok' ?>">
        <div class="stat-card__label"><i class="bi bi-battery-low"></i> Repuestos con stock bajo</div>
        <div class="stat-card__value"><?= number_es($lowCount) ?></div>
        <div class="stat-card__hint">
            <?php if ($onlyLow): ?>
                <a href="<?= admin_url('stock') ?>">Ver todos los repuestos</a>
            <?php else: ?>
                <a href="<?= admin_url('stock?filtro=bajo') ?>">Ver sólo los críticos</a>
            <?php endif; ?>
        </div>
        <i class="bi bi-battery-low stat-card__icon"></i>
    </div>

    <div class="stat-card stat-card--info">
        <div class="stat-card__label"><i class="bi bi-boxes"></i> Repuestos listados</div>
        <div class="stat-card__value"><?= number_es($result['total']) ?></div>
        <i class="bi bi-boxes stat-card__icon"></i>
    </div>
</div>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <?php if ($onlyLow): ?><input type="hidden" name="filtro" value="bajo"><?php endif; ?>

        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                   placeholder="Nombre, código, OEM">
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('stock') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <?php if (can('data.export')): ?>
            <div class="ms-auto">
                <a href="<?= admin_url('exportar/stock/xlsx') ?>" class="btn btn-ghost">
                    <i class="bi bi-file-earmark-excel"></i> Exportar stock
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-box-seam-fill"></i> <?= $onlyLow ? 'Repuestos con stock bajo' : 'Stock de repuestos' ?></h2>
            </div>

            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Repuesto</th>
                                <th>Categoría</th>
                                <th class="num">Stock</th>
                                <th class="num">Reservado</th>
                                <th class="num">Disponible</th>
                                <th class="num">Mínimo</th>
                                <th>Ubicación</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $available = (int) $product['available'];
                            $isLow     = (int) $product['track_stock'] === 1 && $available <= (int) $product['stock_min'];
                            ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e(str_limit((string) $product['name'], 42)) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($product['code']) ?></span>
                                </td>
                                <td class="text-muted-2 small"><?= e($product['category_name'] ?? '—') ?></td>
                                <td class="num"><?= (int) $product['stock'] ?></td>
                                <td class="num text-muted-2"><?= (int) $product['stock_reserved'] ?></td>
                                <td class="num">
                                    <span class="chip chip--<?= (int) $product['track_stock'] !== 1 ? 'neutral' : ($available <= 0 ? 'danger' : ($isLow ? 'warn' : 'ok')) ?>">
                                        <?= (int) $product['track_stock'] === 1 ? $available : 'Sin control' ?>
                                    </span>
                                </td>
                                <td class="num text-muted-2"><?= (int) $product['stock_min'] ?></td>
                                <td class="small text-muted-2">
                                    <?= e(trim(($product['warehouse_name'] ?? '') . ' ' . ($product['shelf'] ?? '') . ' ' . ($product['position'] ?? ''))) ?: '—' ?>
                                </td>
                                <td class="actions">
                                    <?php if (can('stock.move')): ?>
                                        <button type="button" class="btn-icon btn-icon--ok" title="Registrar movimiento"
                                                data-bs-toggle="modal" data-bs-target="#stockModal"
                                                data-stock-move="<?= (int) $product['id'] ?>"
                                                data-product-name="<?= e($product['name']) ?>"
                                                data-current-stock="<?= (int) $product['stock'] ?>">
                                            <i class="bi bi-plus-slash-minus"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= admin_url('stock/' . (int) $product['id'] . '/historial') ?>" class="btn-icon" title="Historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <?php if (can('parts.edit')): ?>
                                        <a href="<?= admin_url('repuestos/' . (int) $product['id'] . '/editar') ?>" class="btn-icon" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($products)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted-2">No hay repuestos que mostrar.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (($result['last_page'] ?? 1) > 1): ?>
                <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-clock-history"></i> Últimos movimientos</h2></div>
            <div class="card-admin__body card-admin__body--flush">
                <table class="table-admin">
                    <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td>
                                <span class="table-product__name"><?= e(str_limit((string) $movement['product_name'], 30)) ?></span>
                                <span class="table-product__meta">
                                    <?= e(date_es((string) $movement['created_at'], true)) ?>
                                    · <?= e($movement['user_name'] ?? 'Sistema') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="chip chip--<?= in_array($movement['type'], ['entrada', 'liberacion'], true) ? 'ok' : (in_array($movement['type'], ['salida', 'venta'], true) ? 'danger' : 'neutral') ?>">
                                    <?= e($types[$movement['type']] ?? $movement['type']) ?>
                                </span>
                                <strong class="d-block"><?= (int) $movement['stock_before'] ?> → <?= (int) $movement['stock_after'] ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($movements)): ?>
                        <tr><td class="text-center py-4 text-muted-2">Sin movimientos registrados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (can('stock.move')): ?>
    <?php $view->include('admin/stock/modal'); ?>
<?php endif; ?>
