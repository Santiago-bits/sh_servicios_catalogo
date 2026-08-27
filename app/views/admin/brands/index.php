<?php
/**
 * ARCHIVO: app/views/admin/brands/index.php
 */
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-award-fill"></i> Marcas</h2>
                <span class="text-muted-2 small"><?= count($brands) ?> marca(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th>País</th>
                                <th class="num">Máquinas</th>
                                <th class="num">Repuestos</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <?php if (!empty($brand['logo'])): ?>
                                            <img class="table-thumb" src="<?= e(upload_url($brand['logo'])) ?>" alt="" style="object-fit:contain;background:#fff">
                                        <?php else: ?>
                                            <span class="table-thumb d-grid" style="place-items:center;color:#B4B4B4"><i class="bi bi-award"></i></span>
                                        <?php endif; ?>
                                        <span>
                                            <span class="table-product__name"><?= e($brand['name']) ?></span>
                                            <span class="table-product__meta">
                                                <?php if (!empty($brand['website'])): ?>
                                                    <a href="<?= e($brand['website']) ?>" target="_blank" rel="noopener">Sitio web</a>
                                                <?php else: ?>
                                                    <?= e($brand['slug']) ?>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-muted-2"><?= e($brand['country'] ?? '—') ?></td>
                                <td class="num"><?= (int) $brand['machines_count'] ?></td>
                                <td class="num"><?= (int) $brand['parts_count'] ?></td>
                                <td>
                                    <span class="chip chip--<?= (int) $brand['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $brand['active'] === 1 ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                    <?php if ((int) $brand['featured'] === 1): ?>
                                        <span class="chip chip--accent">Destacada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#brandModal<?= (int) $brand['id'] ?>" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('marcas/' . (int) $brand['id'] . '/eliminar') ?>"
                                          class="d-inline" data-confirm="¿Eliminar la marca «<?= e($brand['name']) ?>»?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar"><i class="bi bi-trash"></i></button>
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
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nueva marca</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('marcas') ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="b-name">Nombre *</label>
                        <input type="text" class="form-control" id="b-name" name="name" required maxlength="120">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="b-web">Sitio web</label>
                        <input type="text" class="form-control" id="b-web" name="website" maxlength="255" placeholder="toyota-forklifts.com">
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="b-country">País</label>
                        <input type="text" class="form-control" id="b-country" name="country" maxlength="80">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="b-order">Orden</label>
                        <input type="number" class="form-control" id="b-order" name="sort_order" value="0">
                        <p class="form-hint">0 = al final</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="b-desc">Descripción</label>
                        <textarea class="form-control" id="b-desc" name="description" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="b-logo">Logo</label>
                        <input type="file" class="form-control" id="b-logo" name="logo" accept="image/*">
                        <p class="form-hint">Se muestra en el carrusel de marcas de la home.</p>
                    </div>
                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="featured" value="1"> Destacada</label>
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activa</label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($brands as $brand): ?>
    <div class="modal fade" id="brandModal<?= (int) $brand['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($brand['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="post" action="<?= admin_url('marcas/' . (int) $brand['id']) ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($brand['name']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Sitio web</label>
                                <input type="text" class="form-control" name="website" maxlength="255" value="<?= e($brand['website'] ?? '') ?>">
                            </div>
                            <div class="col-8">
                                <label class="form-label">País</label>
                                <input type="text" class="form-control" name="country" maxlength="80" value="<?= e($brand['country'] ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $brand['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="2" maxlength="2000"><?= e($brand['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reemplazar logo</label>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="filter-check">
                                    <input type="checkbox" name="featured" value="1" <?= (int) $brand['featured'] === 1 ? 'checked' : '' ?>> Destacada
                                </label>
                                <label class="filter-check">
                                    <input type="checkbox" name="active" value="1" <?= (int) $brand['active'] === 1 ? 'checked' : '' ?>> Activa
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
