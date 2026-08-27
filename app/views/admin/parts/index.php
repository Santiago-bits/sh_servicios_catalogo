<?php
/**
 * ARCHIVO: app/views/admin/parts/index.php
 * Listado administrativo de repuestos.
 */

use App\Services\StockService;
?>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" placeholder="Nombre, código, OEM"
                   value="<?= e($filters['q'] ?? '') ?>">
        </div>

        <div>
            <label class="form-label" for="f-cat">Categoría</label>
            <select class="form-select" id="f-cat" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) ($filters['categoria'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" for="f-brand">Marca</label>
            <select class="form-select" id="f-brand" name="marca">
                <option value="">Todas</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int) $brand['id'] ?>" <?= (string) ($filters['marca'] ?? '') === (string) $brand['id'] ? 'selected' : '' ?>>
                        <?= e($brand['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" for="f-activo">Publicación</label>
            <select class="form-select" id="f-activo" name="activo">
                <option value="">Todas</option>
                <option value="1" <?= ($filters['activo'] ?? '') === '1' ? 'selected' : '' ?>>Publicados</option>
                <option value="0" <?= ($filters['activo'] ?? '') === '0' ? 'selected' : '' ?>>No publicados</option>
            </select>
        </div>

        <div class="d-flex align-items-center gap-3 pb-1">
            <label class="filter-check m-0">
                <input type="checkbox" name="stock_bajo" value="1" <?= !empty($filters['stock_bajo']) ? 'checked' : '' ?>>
                Stock bajo
            </label>
            <label class="filter-check m-0">
                <input type="checkbox" name="sin_precio" value="1" <?= !empty($filters['sin_precio']) ? 'checked' : '' ?>>
                Sin precio
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('repuestos') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <div class="ms-auto d-flex gap-2">
            <?php if (can('parts.create')): ?>
                <a href="<?= admin_url('repuestos/crear') ?>" class="btn btn-accent">
                    <i class="bi bi-plus-lg"></i> Nuevo repuesto
                </a>
            <?php endif; ?>
            <?php if (can('data.import')): ?>
                <a href="<?= admin_url('importar') ?>" class="btn btn-ghost" title="Importar desde CSV">
                    <i class="bi bi-upload"></i>
                </a>
            <?php endif; ?>
            <?php if (can('data.export')): ?>
                <a href="<?= admin_url('exportar/repuestos/xlsx') ?>" class="btn btn-ghost" title="Exportar a Excel">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-nut-fill"></i> Repuestos</h2>
        <span class="text-muted-2 small"><?= number_es($result['total']) ?> registro(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Repuesto</th>
                        <th>Códigos</th>
                        <th>Categoría</th>
                        <th class="num">Stock</th>
                        <?php if ($canSeeCost): ?><th class="num">Costo</th><?php endif; ?>
                        <th class="num">Precio</th>
                        <th>Ubicación</th>
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $stock = stock_badge($product); ?>
                    <tr>
                        <td>
                            <div class="table-product">
                                <?php if (!empty($product['thumb'])): ?>
                                    <img class="table-thumb" src="<?= e(upload_url($product['thumb'])) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="table-thumb d-grid" style="place-items:center;color:#B4B4B4"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                                <span>
                                    <span class="table-product__name"><?= e(str_limit((string) $product['name'], 44)) ?></span>
                                    <span class="table-product__meta">
                                        <?= e($product['brand_name'] ?? 'Sin marca') ?>
                                        <?php if ((int) $product['active'] !== 1): ?>
                                            <span class="chip chip--neutral ms-1">Oculto</span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </div>
                        </td>

                        <td class="text-mono" style="font-size:.78rem">
                            <strong><?= e($product['code']) ?></strong>
                            <?php if (!empty($product['oem_code'])): ?>
                                <span class="d-block text-muted-2">OEM <?= e($product['oem_code']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td><?= e($product['category_name'] ?? '—') ?></td>

                        <td class="num">
                            <span class="chip chip--<?= $stock['class'] === 'ok' ? 'ok' : ($stock['class'] === 'off' ? 'danger' : ($stock['class'] === 'warn' ? 'warn' : 'neutral')) ?>">
                                <?php if ((int) $product['track_stock'] === 1): ?>
                                    <?= StockService::available($product) ?> u.
                                <?php else: ?>
                                    Sin control
                                <?php endif; ?>
                            </span>
                            <?php if ((int) $product['stock_reserved'] > 0): ?>
                                <small class="d-block text-muted-2"><?= (int) $product['stock_reserved'] ?> reservada(s)</small>
                            <?php endif; ?>
                        </td>

                        <?php if ($canSeeCost): ?>
                            <td class="num text-muted-2"><?= e(money((float) ($product['cost_price'] ?? 0), (string) $product['currency'])) ?></td>
                        <?php endif; ?>

                        <td class="num">
                            <strong><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></strong>
                            <?php if ($canSeeCost && (float) ($product['profit_percent'] ?? 0) > 0): ?>
                                <small class="d-block text-muted-2">+<?= e(percent((float) $product['profit_percent'], 0)) ?></small>
                            <?php endif; ?>
                        </td>

                        <td class="small text-muted-2">
                            <?= e(trim(($product['shelf'] ?? '') . ' ' . ($product['position'] ?? ''))) ?: '—' ?>
                        </td>

                        <td class="actions">
                            <a href="<?= e(part_url($product)) ?>" target="_blank" class="btn-icon" title="Ver en el sitio">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php if (can('stock.move')): ?>
                                <button type="button" class="btn-icon btn-icon--ok" title="Movimiento de stock"
                                        data-bs-toggle="modal" data-bs-target="#stockModal"
                                        data-stock-move="<?= (int) $product['id'] ?>"
                                        data-product-name="<?= e($product['name']) ?>"
                                        data-current-stock="<?= (int) $product['stock'] ?>">
                                    <i class="bi bi-box-seam"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (can('parts.edit')): ?>
                                <a href="<?= admin_url('repuestos/' . (int) $product['id'] . '/editar') ?>" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (can('parts.delete')): ?>
                                <form method="post" action="<?= admin_url('repuestos/' . (int) $product['id'] . '/eliminar') ?>"
                                      class="d-inline" data-confirm="¿Eliminar «<?= e($product['name']) ?>»?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="<?= $canSeeCost ? 8 : 7 ?>" class="text-center py-5 text-muted-2">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                            No hay repuestos que coincidan con los filtros.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($result['last_page'] ?? 1) > 1): ?>
        <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
    <?php endif; ?>
</div>

<?php if (can('stock.move')): ?>
    <?php $view->include('admin/stock/modal'); ?>
<?php endif; ?>
