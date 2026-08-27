<?php
/**
 * ARCHIVO: app/views/admin/prices/index.php
 * Gestión de precios con edición rápida y ajuste masivo.
 */

use App\Services\PriceService;
?>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <input type="hidden" name="tipo" value="<?= e($type) ?>">

        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                   placeholder="Nombre o código">
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

        <div class="pb-1">
            <label class="filter-check m-0">
                <input type="checkbox" name="sin_precio" value="1" <?= !empty($filters['sin_precio']) ? 'checked' : '' ?>>
                Sólo sin precio
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>

        <div class="ms-auto">
            <div class="view-switch">
                <a href="<?= admin_url('precios?tipo=machine') ?>" class="<?= $type === 'machine' ? 'is-active' : '' ?>"
                   style="width:auto;padding:0 14px" title="Maquinaria">
                    <i class="bi bi-truck-front"></i>
                </a>
                <a href="<?= admin_url('precios?tipo=spare_part') ?>" class="<?= $type === 'spare_part' ? 'is-active' : '' ?>"
                   style="width:auto;padding:0 14px" title="Repuestos">
                    <i class="bi bi-nut"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<div class="row g-3">
    <div class="col-xl-9">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-cash-stack"></i> <?= $type === 'machine' ? 'Precios de maquinaria' : 'Precios de repuestos' ?></h2>
                <span class="text-muted-2 small"><?= number_es($result['total']) ?> producto(s)</span>
            </div>

            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <?php if ($canSeeCost): ?>
                                    <th style="width:130px">Costo</th>
                                    <th style="width:110px">Ganancia %</th>
                                    <th class="num">Ganancia $</th>
                                <?php endif; ?>
                                <th style="width:140px">Precio final</th>
                                <?php if ($canEdit): ?>
                                    <th style="width:180px">Motivo</th>
                                    <th class="actions">Guardar</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <span>
                                            <span class="table-product__name"><?= e(str_limit((string) $product['name'], 40)) ?></span>
                                            <span class="table-product__meta text-mono"><?= e($product['code']) ?> · <?= e($product['currency']) ?></span>
                                        </span>
                                    </div>
                                </td>

                                <?php
                                // Los inputs se asocian al formulario por su atributo form=""
                                // (HTML5): así el <form> queda fuera de la tabla y el marcado
                                // sigue siendo válido.
                                $formId = 'pf' . (int) $product['id'];
                                ?>

                                <?php if ($canSeeCost): ?>
                                    <td>
                                        <?php if ($canEdit): ?>
                                            <input type="text" form="<?= $formId ?>" class="form-control form-control-sm" name="cost_price"
                                                   value="<?= e(number_format((float) $product['cost_price'], 2, '.', '')) ?>">
                                        <?php else: ?>
                                            <?= e(money((float) $product['cost_price'], (string) $product['currency'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canEdit): ?>
                                            <input type="text" form="<?= $formId ?>" class="form-control form-control-sm" name="profit_percent"
                                                   value="<?= e(number_format((float) $product['profit_percent'], 2, '.', '')) ?>">
                                        <?php else: ?>
                                            <?= e(percent((float) $product['profit_percent'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num text-muted-2" data-price-out="profit_amount">
                                        <?= e(money((float) $product['profit_amount'], (string) $product['currency'])) ?>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <?php if ($canEdit): ?>
                                        <input type="text" form="<?= $formId ?>" class="form-control form-control-sm fw-bold" name="final_price"
                                               value="<?= e(number_format((float) $product['final_price'], 2, '.', '')) ?>">
                                    <?php else: ?>
                                        <strong data-price-out="final_price"><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></strong>
                                    <?php endif; ?>
                                </td>

                                <?php if ($canEdit): ?>
                                    <td>
                                        <input type="text" form="<?= $formId ?>" class="form-control form-control-sm" name="reason"
                                               placeholder="Motivo del cambio" maxlength="255">
                                    </td>
                                    <td class="actions">
                                        <button type="submit" form="<?= $formId ?>" class="btn-icon btn-icon--ok" title="Guardar precio">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <?php if (can('prices.history')): ?>
                                            <a href="<?= admin_url('precios/' . (int) $product['id'] . '/historial') ?>"
                                               class="btn-icon" title="Historial"><i class="bi bi-clock-history"></i></a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($products)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted-2">No hay productos con esos filtros.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (($result['last_page'] ?? 1) > 1): ?>
                <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($canEdit): ?>
            <!-- Formularios de edición rápida (uno por producto, fuera de la tabla) -->
            <?php foreach ($products as $product): ?>
                <form id="pf<?= (int) $product['id'] ?>" method="post"
                      action="<?= admin_url('precios/' . (int) $product['id']) ?>" data-price-form class="d-none">
                    <?= csrf_field() ?>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-xl-3">
        <?php if ($canEdit): ?>
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-percent"></i> Ajuste masivo</h2></div>
                <div class="card-admin__body">
                    <p class="form-hint">
                        Aplica un porcentaje sobre todos los productos que cumplan el filtro.
                        Queda registrado en el historial de cada producto.
                    </p>

                    <form method="post" action="<?= admin_url('precios/masivo') ?>" class="row g-2"
                          data-confirm="¿Confirmás el ajuste masivo de precios? La acción queda auditada.">
                        <?= csrf_field() ?>

                        <div class="col-12">
                            <label class="form-label" for="bulk-tipo">Aplicar a</label>
                            <select class="form-select" id="bulk-tipo" name="tipo">
                                <option value="">Todos los productos</option>
                                <option value="machine" <?= $type === 'machine' ? 'selected' : '' ?>>Sólo maquinaria</option>
                                <option value="spare_part" <?= $type === 'spare_part' ? 'selected' : '' ?>>Sólo repuestos</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="bulk-cat">Categoría</label>
                            <select class="form-select" id="bulk-cat" name="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="bulk-marca">Marca</label>
                            <select class="form-select" id="bulk-marca" name="marca">
                                <option value="">Todas</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int) $brand['id'] ?>"><?= e($brand['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label" for="bulk-percent">Porcentaje</label>
                            <div class="input-group-admin">
                                <input type="text" class="form-control" id="bulk-percent" name="percent" placeholder="12,5" required>
                                <span class="input-group-admin__addon">%</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label" for="bulk-target">Sobre el</label>
                            <select class="form-select" id="bulk-target" name="target">
                                <option value="precio">Precio final</option>
                                <?php if ($canSeeCost): ?>
                                    <option value="costo">Costo</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="bulk-reason">Motivo *</label>
                            <input type="text" class="form-control" id="bulk-reason" name="reason" required
                                   maxlength="255" placeholder="Ej: Actualización lista septiembre">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-accent w-100">
                                <i class="bi bi-arrow-up-right"></i> Aplicar ajuste
                            </button>
                            <p class="form-hint mt-2 mb-0">Usá valores negativos para descuentos (ej: -10).</p>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canSeeCost && !empty($recent)): ?>
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-clock-history"></i> Cambios recientes</h2></div>
                <div class="card-admin__body">
                    <div class="timeline">
                        <?php foreach ($recent as $change): ?>
                            <div class="timeline__item">
                                <div class="timeline__time">
                                    <?= e(date_es((string) $change['created_at'], true)) ?> · <?= e($change['user_name'] ?? 'Sistema') ?>
                                </div>
                                <div class="timeline__text">
                                    <strong><?= e($change['product_code']) ?></strong>
                                    <?= e(money((float) $change['old_price'], (string) $change['currency'])) ?>
                                    <i class="bi bi-arrow-right"></i>
                                    <strong><?= e(money((float) $change['new_price'], (string) $change['currency'])) ?></strong>
                                    <?php if (!empty($change['reason'])): ?>
                                        <small class="d-block text-muted-2"><?= e($change['reason']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$canSeeCost): ?>
            <div class="card-admin">
                <div class="card-admin__body">
                    <p class="mb-0 text-muted-2 small">
                        <i class="bi bi-shield-lock text-accent"></i>
                        Tu rol no tiene el permiso <code>prices.view_cost</code>, así que el costo y la ganancia
                        no se cargan desde el servidor.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
