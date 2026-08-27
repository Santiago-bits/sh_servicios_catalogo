<?php
/**
 * ARCHIVO: app/views/admin/categories/index.php
 */
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-diagram-3-fill"></i> Categorías</h2>
                <span class="text-muted-2 small"><?= count($categories) ?> categoría(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Padre</th>
                                <th class="num">Productos</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <span class="table-thumb d-grid" style="place-items:center;background:#111;color:#F5C400">
                                            <i class="bi <?= e($category['icon'] ?: 'bi-folder') ?>"></i>
                                        </span>
                                        <span>
                                            <span class="table-product__name"><?= e($category['name']) ?></span>
                                            <span class="table-product__meta text-mono"><?= e($category['slug']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="chip chip--<?= $category['type'] === 'machine' ? 'accent' : ($category['type'] === 'spare_part' ? 'info' : 'neutral') ?>">
                                        <?= e($types[$category['type']] ?? $category['type']) ?>
                                    </span>
                                </td>
                                <td class="text-muted-2"><?= e($category['parent_name'] ?? '—') ?></td>
                                <td class="num"><?= (int) $category['products_count'] ?></td>
                                <td><?= (int) $category['sort_order'] ?></td>
                                <td>
                                    <span class="chip chip--<?= (int) $category['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $category['active'] === 1 ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                    <?php if ((int) $category['featured'] === 1): ?>
                                        <span class="chip chip--accent">Destacada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" title="Editar"
                                            data-bs-toggle="modal" data-bs-target="#catModal<?= (int) $category['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('categorias/' . (int) $category['id'] . '/eliminar') ?>"
                                          class="d-inline" data-confirm="¿Eliminar la categoría «<?= e($category['name']) ?>»?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nueva categoría</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('categorias') ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="c-name">Nombre *</label>
                        <input type="text" class="form-control" id="c-name" name="name" required maxlength="120">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="c-type">Tipo *</label>
                        <select class="form-select" id="c-type" name="type" required>
                            <?php foreach ($types as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="c-parent">Categoría padre</label>
                        <select class="form-select" id="c-parent" name="parent_id">
                            <option value="">Ninguna (categoría principal)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>">
                                    <?= e($category['name']) ?> (<?= e($types[$category['type']] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-8">
                        <label class="form-label" for="c-icon">Ícono</label>
                        <input type="text" class="form-control text-mono" id="c-icon" name="icon"
                               maxlength="60" placeholder="bi-truck-front-fill">
                        <p class="form-hint">Nombre de un ícono de Bootstrap Icons.</p>
                    </div>

                    <div class="col-4">
                        <label class="form-label" for="c-order">Orden</label>
                        <input type="number" class="form-control" id="c-order" name="sort_order" value="0">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="c-desc">Descripción</label>
                        <textarea class="form-control" id="c-desc" name="description" rows="3" maxlength="2000"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="c-image">Imagen</label>
                        <input type="file" class="form-control" id="c-image" name="image" accept="image/*">
                    </div>

                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="featured" value="1"> Destacada en la home</label>
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activa</label>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modales de edición -->
<?php foreach ($categories as $category): ?>
    <div class="modal fade" id="catModal<?= (int) $category['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($category['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="post" action="<?= admin_url('categorias/' . (int) $category['id']) ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($category['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" name="type" required>
                                    <?php foreach ($types as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $category['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoría padre</label>
                                <select class="form-select" name="parent_id">
                                    <option value="">Ninguna</option>
                                    <?php foreach ($categories as $option): ?>
                                        <?php if ((int) $option['id'] === (int) $category['id']) { continue; } ?>
                                        <option value="<?= (int) $option['id'] ?>" <?= (int) $category['parent_id'] === (int) $option['id'] ? 'selected' : '' ?>>
                                            <?= e($option['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ícono</label>
                                <input type="text" class="form-control text-mono" name="icon" maxlength="60" value="<?= e($category['icon'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $category['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="3" maxlength="2000"><?= e($category['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Título SEO</label>
                                <input type="text" class="form-control" name="meta_title" maxlength="180" value="<?= e($category['meta_title'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Meta descripción</label>
                                <textarea class="form-control" name="meta_description" rows="2" maxlength="300"><?= e($category['meta_description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reemplazar imagen</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="filter-check">
                                    <input type="checkbox" name="featured" value="1" <?= (int) $category['featured'] === 1 ? 'checked' : '' ?>> Destacada
                                </label>
                                <label class="filter-check">
                                    <input type="checkbox" name="active" value="1" <?= (int) $category['active'] === 1 ? 'checked' : '' ?>> Activa
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer" style="border-top:1px solid #eee">
                        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
