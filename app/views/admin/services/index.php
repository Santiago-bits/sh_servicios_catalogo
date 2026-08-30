<?php
/**
 * ARCHIVO: app/views/admin/services/index.php
 */

use App\Models\Service as ServiceModel;
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-tools"></i> Servicios</h2>
                <span class="text-muted-2 small"><?= count($services) ?> servicio(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <span class="table-thumb d-grid" style="place-items:center;background:#111;color:#F5C400">
                                            <i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i>
                                        </span>
                                        <span>
                                            <span class="table-product__name"><?= e($service['title']) ?></span>
                                            <span class="table-product__meta text-mono"><?= e($service['slug']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-muted-2 small"><?= e(str_limit((string) $service['short_description'], 70)) ?></td>
                                <td><?= (int) $service['sort_order'] ?></td>
                                <td>
                                    <span class="chip chip--<?= (int) $service['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $service['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                    <?php if ((int) $service['featured'] === 1): ?>
                                        <span class="chip chip--accent">Destacado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="<?= e(url('servicios/' . $service['slug'])) ?>" target="_blank" class="btn-icon"><i class="bi bi-box-arrow-up-right"></i></a>
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#svcModal<?= (int) $service['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('servicios/' . (int) $service['id'] . '/eliminar') ?>"
                                          class="d-inline" data-confirm="¿Eliminar el servicio «<?= e($service['title']) ?>»?">
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
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nuevo servicio</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('servicios') ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="s-title">Título *</label>
                        <input type="text" class="form-control" id="s-title" name="title" required maxlength="160">
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="s-icon">Ícono</label>
                        <input type="text" class="form-control text-mono" id="s-icon" name="icon" maxlength="60" placeholder="bi-wrench">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="s-order">Orden</label>
                        <input type="number" class="form-control" id="s-order" name="sort_order" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="s-short">Descripción corta</label>
                        <input type="text" class="form-control" id="s-short" name="short_description" maxlength="300">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="s-desc">Descripción completa</label>
                        <textarea class="form-control" id="s-desc" name="description" rows="4" maxlength="20000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="s-image">Imagen</label>
                        <input type="file" class="form-control" id="s-image" name="image" accept="image/*">
                    </div>
                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="featured" value="1"> Destacado</label>
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activo</label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear servicio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($services as $service): ?>
    <div class="modal fade" id="svcModal<?= (int) $service['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($service['title']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="<?= admin_url('servicios/' . (int) $service['id']) ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Título *</label>
                                <input type="text" class="form-control" name="title" required maxlength="160" value="<?= e($service['title']) ?>">
                            </div>
                            <div class="col-8">
                                <label class="form-label">Ícono</label>
                                <input type="text" class="form-control text-mono" name="icon" maxlength="60" value="<?= e($service['icon'] ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $service['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción corta</label>
                                <input type="text" class="form-control" name="short_description" maxlength="300" value="<?= e($service['short_description'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción completa</label>
                                <textarea class="form-control" name="description" rows="5" maxlength="20000"><?= e($service['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reemplazar imagen</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="filter-check"><input type="checkbox" name="featured" value="1" <?= (int) $service['featured'] === 1 ? 'checked' : '' ?>> Destacado</label>
                                <label class="filter-check"><input type="checkbox" name="active" value="1" <?= (int) $service['active'] === 1 ? 'checked' : '' ?>> Activo</label>
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
