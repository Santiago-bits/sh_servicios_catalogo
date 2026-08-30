<?php
/**
 * ARCHIVO: app/views/admin/categories/index.php
 * Categorías (versión simplificada: nombre + tipo + ícono + imagen).
 *
 * @var array<int,array<string,mixed>> $categories
 * @var array<string,string> $types
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
                                <th class="num">Productos</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <?php if (!empty($category['image'])): ?>
                                            <img class="table-thumb" src="<?= e(upload_url($category['image'])) ?>" alt="" style="object-fit:cover">
                                        <?php else: ?>
                                            <span class="table-thumb d-grid" style="place-items:center;background:#111;color:#F5C400">
                                                <i class="bi <?= e($category['icon'] ?: 'bi-folder') ?>"></i>
                                            </span>
                                        <?php endif; ?>
                                        <span class="table-product__name"><?= e($category['name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="chip chip--<?= $category['type'] === 'machine' ? 'accent' : ($category['type'] === 'spare_part' ? 'info' : 'neutral') ?>">
                                        <?= e($types[$category['type']] ?? $category['type']) ?>
                                    </span>
                                </td>
                                <td class="num"><?= (int) $category['products_count'] ?></td>
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
                        <label class="form-label" for="c-icon">Ícono <span class="text-muted-2">(opcional)</span></label>
                        <input type="text" class="form-control text-mono" id="c-icon" name="icon"
                               maxlength="60" placeholder="bi-truck-front-fill">
                        <p class="form-hint">Nombre de un ícono de <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">Bootstrap Icons</a>.</p>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="c-image">Imagen</label>
                        <input type="file" class="form-control" id="c-image" name="image" accept="image/png,image/jpeg,image/webp">
                        <p class="form-hint">Si subís una imagen, se usa en vez del ícono (útil cuando Bootstrap Icons no tiene el dibujo, ej. un autoelevador). PNG con fondo transparente, ~120&times;120 px.</p>
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
                            <div class="col-12">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" name="type" required>
                                    <?php foreach ($types as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $category['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ícono <span class="text-muted-2">(opcional)</span></label>
                                <input type="text" class="form-control text-mono" name="icon" maxlength="60" value="<?= e($category['icon'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Imagen</label>
                                <?php if (!empty($category['image'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= e(upload_url($category['image'])) ?>" alt="" style="max-height:60px;border-radius:6px;border:1px solid #E2E4E8">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="image" accept="image/png,image/jpeg,image/webp">
                                <p class="form-hint">Dejalo vacío para conservar la actual.</p>
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
