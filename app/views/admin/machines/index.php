<?php
/**
 * ARCHIVO: app/views/admin/machines/index.php
 * Listado administrativo de maquinaria.
 */

use App\Services\PriceService;
?>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" placeholder="Nombre, código, modelo"
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
            <label class="form-label" for="f-estado">Estado</label>
            <select class="form-select" id="f-estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'] as $status): ?>
                    <option value="<?= $status ?>" <?= ($filters['estado'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e(availability_badge($status)['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" for="f-activo">Publicación</label>
            <select class="form-select" id="f-activo" name="activo">
                <option value="">Todas</option>
                <option value="1" <?= ($filters['activo'] ?? '') === '1' ? 'selected' : '' ?>>Publicadas</option>
                <option value="0" <?= ($filters['activo'] ?? '') === '0' ? 'selected' : '' ?>>No publicadas</option>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('maquinaria') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <div class="ms-auto d-flex gap-2">
            <?php if (can('machines.create')): ?>
                <a href="<?= admin_url('maquinaria/crear') ?>" class="btn btn-accent">
                    <i class="bi bi-plus-lg"></i> Nueva máquina
                </a>
            <?php endif; ?>
            <?php if (can('data.export')): ?>
                <a href="<?= admin_url('exportar/maquinaria/xlsx') ?>" class="btn btn-ghost" title="Exportar a Excel">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-truck-front-fill"></i> Maquinaria</h2>
        <span class="text-muted-2 small">
            <?= number_es($result['total']) ?> registro(s)
            <?php if ($result['total'] > 0): ?>· <?= (int) $result['from'] ?>–<?= (int) $result['to'] ?><?php endif; ?>
        </span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th>Marca / Modelo</th>
                        <th>Año</th>
                        <th>Capacidad</th>
                        <?php if ($canSeeCost): ?><th class="num">Costo</th><?php endif; ?>
                        <th class="num">Precio</th>
                        <th>Estado</th>
                        <th>Publicada</th>
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $availability = availability_badge((string) $product['availability']); ?>
                    <tr>
                        <td>
                            <div class="table-product">
                                <?php if (!empty($product['thumb'])): ?>
                                    <img class="table-thumb" src="<?= e(upload_url($product['thumb'])) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="table-thumb d-grid" style="place-items:center;color:#B4B4B4">
                                        <i class="bi bi-image"></i>
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <span class="table-product__name"><?= e(str_limit((string) $product['name'], 46)) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($product['code']) ?></span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?= e($product['brand_name'] ?? '—') ?>
                            <small class="d-block text-muted-2"><?= e($product['model'] ?? '') ?></small>
                        </td>
                        <td><?= $product['year'] ? (int) $product['year'] : '—' ?></td>
                        <td><?= !empty($product['capacity_kg']) ? e(kg_to_human((float) $product['capacity_kg'])) : '—' ?></td>

                        <?php if ($canSeeCost): ?>
                            <td class="num text-muted-2"><?= e(money((float) ($product['cost_price'] ?? 0), (string) $product['currency'])) ?></td>
                        <?php endif; ?>

                        <td class="num">
                            <strong><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></strong>
                            <?php if ($canSeeCost && (float) ($product['profit_percent'] ?? 0) > 0): ?>
                                <small class="d-block text-muted-2">+<?= e(percent((float) $product['profit_percent'], 0)) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><span class="chip chip--<?= $availability['class'] === 'ok' ? 'ok' : ($availability['class'] === 'off' ? 'danger' : 'warn') ?>">
                            <?= e($availability['label']) ?>
                        </span></td>

                        <td>
                            <?php if ((int) $product['active'] === 1): ?>
                                <span class="chip chip--ok">Sí</span>
                            <?php else: ?>
                                <span class="chip chip--neutral">No</span>
                            <?php endif; ?>
                        </td>

                        <td class="actions">
                            <a href="<?= e(machine_url($product)) ?>" target="_blank" class="btn-icon" title="Ver en el sitio">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php if (can('machines.edit')): ?>
                                <a href="<?= admin_url('maquinaria/' . (int) $product['id'] . '/editar') ?>" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (can('prices.history')): ?>
                                <a href="<?= admin_url('precios/' . (int) $product['id'] . '/historial') ?>" class="btn-icon" title="Historial de precios">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (can('machines.delete')): ?>
                                <form method="post" action="<?= admin_url('maquinaria/' . (int) $product['id'] . '/eliminar') ?>"
                                      class="d-inline" data-confirm="¿Eliminar «<?= e($product['name']) ?>»? Esta acción no se puede deshacer.">
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
                        <td colspan="<?= $canSeeCost ? 9 : 8 ?>" class="text-center py-5 text-muted-2">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                            No hay máquinas que coincidan con los filtros.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($result['last_page'] ?? 1) > 1): ?>
        <div class="card-admin__foot">
            <?php $view->partial('pagination', ['result' => $result]); ?>
        </div>
    <?php endif; ?>
</div>
