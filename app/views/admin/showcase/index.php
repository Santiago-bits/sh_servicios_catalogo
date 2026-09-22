<?php
/**
 * ARCHIVO: app/views/admin/showcase/index.php
 * Marcas en la web (home y logos de clientes de Alquiler).
 *
 * @var array<string,array<int,array<string,mixed>>> $groups
 * @var array<string,string> $sections
 */

$sectionHelp = [
    'equipos'    => 'Se muestran en el inicio, bloque «Equipos y repuestos».',
    'neumaticos' => 'Se muestran en el inicio, bloque «Neumáticos».',
    'clientes'   => 'Logos de empresas que confían en el servicio de Alquiler (se ven en su detalle).',
];
?>
<div class="row g-3">
    <div class="col-lg-8">
        <?php foreach ($sections as $key => $label): ?>
            <?php $items = $groups[$key] ?? []; ?>
            <div class="card-admin mb-3">
                <div class="card-admin__head">
                    <h2><i class="bi <?= $key === 'clientes' ? 'bi-building' : 'bi-award-fill' ?>"></i> <?= e($key === 'clientes' ? 'Clientes (Alquiler)' : $label) ?></h2>
                    <span class="text-muted-2 small"><?= count($items) ?> · <?= e($sectionHelp[$key]) ?></span>
                </div>
                <div class="card-admin__body card-admin__body--flush">
                    <?php if ($items === []): ?>
                        <p class="text-muted-2 small p-3 m-0">Todavía no hay ninguna. Agregala desde el formulario de la derecha.</p>
                    <?php else: ?>
                        <div class="table-responsive-admin">
                            <table class="table-admin">
                                <thead>
                                    <tr><th>Marca</th><th class="num">Orden</th><th>Estado</th><th class="actions">Acciones</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($items as $brand): ?>
                                    <tr>
                                        <td>
                                            <div class="table-product">
                                                <?php if (!empty($brand['logo'])): ?>
                                                    <img class="table-thumb" src="<?= e(upload_url($brand['logo'])) ?>" alt="" style="object-fit:contain;background:#fff">
                                                <?php else: ?>
                                                    <span class="table-thumb d-grid" style="place-items:center;color:#B4B4B4" title="Sin logo: se muestra el nombre"><i class="bi bi-image"></i></span>
                                                <?php endif; ?>
                                                <span class="table-product__name"><?= e($brand['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="num"><?= (int) $brand['sort_order'] ?></td>
                                        <td>
                                            <span class="chip chip--<?= (int) $brand['active'] === 1 ? 'ok' : 'neutral' ?>">
                                                <?= (int) $brand['active'] === 1 ? 'Visible' : 'Oculta' ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#sbModal<?= (int) $brand['id'] ?>" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" action="<?= admin_url('marcas-web/' . (int) $brand['id'] . '/eliminar') ?>"
                                                  class="d-inline" data-confirm="¿Eliminar «<?= e($brand['name']) ?>» de la web?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Agregar</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('marcas-web') ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" for="sb-section">Dónde se muestra *</label>
                        <select class="form-select" id="sb-section" name="section">
                            <?php foreach ($sections as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($key === 'clientes' ? 'Clientes (Alquiler)' : $label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sb-name">Nombre *</label>
                        <input type="text" class="form-control" id="sb-name" name="name" required maxlength="120">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sb-logo">Logo</label>
                        <input type="file" class="form-control" id="sb-logo" name="logo" accept="image/png,image/jpeg,image/webp">
                        <p class="form-hint">Ideal PNG con fondo transparente. Sin logo se muestra el nombre.</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="sb-order">Orden</label>
                        <input type="number" class="form-control" id="sb-order" name="sort_order" placeholder="Al final">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <label class="filter-check m-0">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1" checked> Visible
                        </label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($groups as $items): foreach ($items as $brand): ?>
    <div class="modal fade" id="sbModal<?= (int) $brand['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($brand['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="post" action="<?= admin_url('marcas-web/' . (int) $brand['id']) ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Dónde se muestra</label>
                                <select class="form-select" name="section">
                                    <?php foreach ($sections as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= $brand['section'] === $key ? 'selected' : '' ?>><?= e($key === 'clientes' ? 'Clientes (Alquiler)' : $label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($brand['name']) ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $brand['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Logo</label>
                                <?php if (!empty($brand['logo'])): ?>
                                    <div class="mb-2 d-flex align-items-center gap-3">
                                        <img src="<?= e(upload_url($brand['logo'])) ?>" alt="" style="max-height:44px;background:#fff;border-radius:6px;padding:4px;border:1px solid #E2E4E8">
                                        <label class="filter-check m-0"><input type="checkbox" name="remove_logo" value="1"> Quitar logo</label>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp">
                                <p class="form-hint">Dejalo vacío para conservar el actual.</p>
                            </div>
                            <div class="col-12">
                                <label class="filter-check m-0">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" <?= (int) $brand['active'] === 1 ? 'checked' : '' ?>> Visible en la web
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
<?php endforeach; endforeach; ?>
