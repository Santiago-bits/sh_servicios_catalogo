<?php
/**
 * ARCHIVO: app/views/admin/features/index.php
 * Características técnicas (versión simplificada: nombre + unidad + a qué aplica).
 *
 * @var array<int,array<string,mixed>> $features
 * @var array<string,string> $types
 */
?>
<div class="card-admin">
    <div class="card-admin__body">
        <p class="mb-0 text-muted-2">
            <i class="bi bi-info-circle text-accent"></i>
            Definí acá los datos técnicos que querés cargar en los productos (ej: Capacidad, Motor, Altura).
            En cada producto aparecen como un campo para completar, y se muestran en la ficha pública.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-sliders"></i> Características</h2>
                <span class="text-muted-2 small"><?= count($features) ?> en total</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Unidad</th>
                                <th>Se aplica a</th>
                                <th class="num">Usos</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($features)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted-2">Todavía no cargaste ninguna característica.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($features as $feature): ?>
                            <tr>
                                <td><span class="table-product__name"><?= e($feature['name']) ?></span></td>
                                <td><?= e($feature['unit'] ?: '—') ?></td>
                                <td><?= e($types[$feature['applies_to']] ?? $feature['applies_to']) ?></td>
                                <td class="num"><?= (int) $feature['uses'] ?></td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#featModal<?= (int) $feature['id'] ?>" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('caracteristicas/' . (int) $feature['id'] . '/eliminar') ?>"
                                          class="d-inline"
                                          data-confirm="¿Eliminar «<?= e($feature['name']) ?>»? Se perderán los <?= (int) $feature['uses'] ?> valor(es) cargado(s) en los productos.">
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
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nueva característica</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('caracteristicas') ?>" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="f-name">Nombre *</label>
                        <input type="text" class="form-control" id="f-name" name="name" required maxlength="120"
                               placeholder="Ej: Capacidad de carga">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="f-unit">Unidad <span class="text-muted-2">(opcional)</span></label>
                        <input type="text" class="form-control" id="f-unit" name="unit" maxlength="20" placeholder="kg, mm, HP…">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="f-applies">Se aplica a *</label>
                        <select class="form-select" id="f-applies" name="applies_to" required>
                            <?php foreach ($types as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear característica</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($features as $feature): ?>
    <div class="modal fade" id="featModal<?= (int) $feature['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($feature['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="<?= admin_url('caracteristicas/' . (int) $feature['id']) ?>">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($feature['name']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Unidad <span class="text-muted-2">(opcional)</span></label>
                                <input type="text" class="form-control" name="unit" maxlength="20" value="<?= e($feature['unit'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Se aplica a *</label>
                                <select class="form-select" name="applies_to" required>
                                    <?php foreach ($types as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $feature['applies_to'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
