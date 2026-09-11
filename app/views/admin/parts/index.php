<?php
/**
 * ARCHIVO: app/views/admin/parts/index.php
 * Listado administrativo de repuestos.
 */

?>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <?php foreach (['sin_imagen', 'sin_categoria'] as $flag): ?>
            <?php if (!empty($filters[$flag])): ?>
                <input type="hidden" name="<?= $flag ?>" value="1">
            <?php endif; ?>
        <?php endforeach; ?>
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
            <input type="text" class="form-control" id="f-brand" name="marca"
                   value="<?= e(is_array($filters['marca'] ?? '') ? '' : (string) ($filters['marca'] ?? '')) ?>"
                   placeholder="Ej: Bosch">
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
                <input type="checkbox" name="sin_precio" value="1" <?= !empty($filters['sin_precio']) ? 'checked' : '' ?>>
                Sin precio
            </label>
        </div>

        <div class="d-flex gap-2 ms-auto">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('repuestos') ?>" class="btn btn-ghost">Limpiar</a>
        </div>
    </form>
</div>

<?php
$activeFaults = array_values(array_filter([
    !empty($filters['sin_precio'])    ? 'sin precio'    : null,
    !empty($filters['sin_imagen'])    ? 'sin foto'      : null,
    !empty($filters['sin_categoria']) ? 'sin categoría' : null,
]));
?>
<?php if ($activeFaults !== []): ?>
    <div class="alert alert-warning d-flex flex-wrap gap-2 justify-content-between align-items-center py-2 px-3 mb-3">
        <span><i class="bi bi-funnel-fill"></i> Mostrando sólo repuestos <strong><?= e(implode(' y ', $activeFaults)) ?></strong>.</span>
        <a href="<?= admin_url('repuestos') ?>" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
<?php endif; ?>

<div class="card-admin">
    <div class="card-admin__head">
        <h2>
            <i class="bi bi-nut-fill"></i> Repuestos
            <span class="text-muted-2 small fw-normal ms-1"><?= number_es($result['total']) ?> registro(s)</span>
        </h2>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (can('parts.create')): ?>
                <a href="<?= admin_url('repuestos/crear') ?>" class="btn btn-accent btn-sm">
                    <i class="bi bi-plus-lg"></i> Nuevo repuesto
                </a>
            <?php endif; ?>
            <?php if (can('data.import')): ?>
                <a href="<?= admin_url('importar') ?>" class="btn btn-ghost btn-sm" title="Importar desde CSV">
                    <i class="bi bi-upload"></i>
                </a>
            <?php endif; ?>
            <?php if (can('data.export')): ?>
                <a href="<?= admin_url('exportar/repuestos/xlsx') ?>" class="btn btn-ghost btn-sm" title="Exportar a Excel">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Repuesto</th>
                        <th>Códigos</th>
                        <th>Categoría</th>
                        <th class="num">Precio</th>
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
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
                            <strong><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></strong>
                            <?php if ($canSeeCost && (float) ($product['profit_percent'] ?? 0) > 0): ?>
                                <small class="d-block text-muted-2">+<?= e(percent((float) $product['profit_percent'], 0)) ?></small>
                            <?php endif; ?>
                        </td>

                        <td class="actions">
                            <div class="dropdown">
                                <button type="button" class="btn-icon" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Acciones">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="<?= e(part_url($product)) ?>" target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i> Ver en el sitio
                                        </a>
                                    </li>
                                    <?php if (can('parts.edit')): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= admin_url('repuestos/' . (int) $product['id'] . '/editar') ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (can('parts.create')): ?>
                                        <li>
                                            <form method="post" action="<?= admin_url('repuestos/' . (int) $product['id'] . '/duplicar') ?>"
                                                  data-confirm="¿Crear una copia idéntica de «<?= e($product['name']) ?>»? Se abrirá para editarla.">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-files"></i> Duplicar
                                                </button>
                                            </form>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (can('parts.delete')): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="post" action="<?= admin_url('repuestos/' . (int) $product['id'] . '/eliminar') ?>"
                                                  data-confirm="¿Eliminar «<?= e($product['name']) ?>»?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted-2">
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
