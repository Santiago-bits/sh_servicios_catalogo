<?php
/**
 * ARCHIVO: app/views/admin/tags/index.php
 */
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-tags-fill"></i> Etiquetas</h2>
                <span class="text-muted-2 small"><?= count($tags) ?> etiqueta(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Etiqueta</th>
                                <th>Vista previa</th>
                                <th class="num">Productos</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e($tag['name']) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($tag['slug']) ?></span>
                                </td>
                                <td>
                                    <span class="tag tag--<?= e($tag['color']) ?>">
                                        <?php if (!empty($tag['icon'])): ?><i class="bi <?= e($tag['icon']) ?>"></i><?php endif; ?>
                                        <?= e($tag['name']) ?>
                                    </span>
                                </td>
                                <td class="num"><?= (int) $tag['products_count'] ?></td>
                                <td><?= (int) $tag['sort_order'] ?></td>
                                <td>
                                    <span class="chip chip--<?= (int) $tag['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $tag['active'] === 1 ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#tagModal<?= (int) $tag['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('etiquetas/' . (int) $tag['id'] . '/eliminar') ?>"
                                          class="d-inline" data-confirm="¿Eliminar la etiqueta «<?= e($tag['name']) ?>»?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-icon btn-icon--danger"><i class="bi bi-trash"></i></button>
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
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nueva etiqueta</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('etiquetas') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" for="t-name">Nombre *</label>
                        <input type="text" class="form-control" id="t-name" name="name" required maxlength="60">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="t-color">Color *</label>
                        <select class="form-select" id="t-color" name="color" required>
                            <?php foreach ($colors as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="t-icon">Ícono</label>
                        <input type="text" class="form-control text-mono" id="t-icon" name="icon" maxlength="60" placeholder="bi-star-fill">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="t-order">Orden</label>
                        <input type="number" class="form-control" id="t-order" name="sort_order" value="0">
                    </div>
                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activa</label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear etiqueta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($tags as $tag): ?>
    <div class="modal fade" id="tagModal<?= (int) $tag['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($tag['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="<?= admin_url('etiquetas/' . (int) $tag['id']) ?>">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="60" value="<?= e($tag['name']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Color *</label>
                                <select class="form-select" name="color" required>
                                    <?php foreach ($colors as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $tag['color'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label">Ícono</label>
                                <input type="text" class="form-control text-mono" name="icon" maxlength="60" value="<?= e($tag['icon'] ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $tag['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="filter-check">
                                    <input type="checkbox" name="active" value="1" <?= (int) $tag['active'] === 1 ? 'checked' : '' ?>> Activa
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
