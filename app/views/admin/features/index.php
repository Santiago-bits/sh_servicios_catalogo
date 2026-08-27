<?php
/**
 * ARCHIVO: app/views/admin/features/index.php
 * Administración de características técnicas dinámicas.
 */
?>
<div class="card-admin">
    <div class="card-admin__body">
        <p class="mb-0 text-muted-2">
            <i class="bi bi-info-circle text-accent"></i>
            Las características que definís acá son las que aparecen en el formulario de cada producto
            y en la ficha pública. No hace falta tocar la base de datos para agregar una nueva.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <?php foreach ($grouped as $groupName => $items): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-collection"></i> <?= e($groupName) ?></h2>
                    <span class="text-muted-2 small"><?= count($items) ?> característica(s)</span>
                </div>
                <div class="card-admin__body card-admin__body--flush">
                    <div class="table-responsive-admin">
                        <table class="table-admin">
                            <thead>
                                <tr>
                                    <th>Característica</th>
                                    <th>Unidad</th>
                                    <th>Tipo</th>
                                    <th>Aplica a</th>
                                    <th>Opciones</th>
                                    <th class="num">Usos</th>
                                    <th class="actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $feature): ?>
                                <tr>
                                    <td>
                                        <span class="table-product__name"><?= e($feature['name']) ?></span>
                                        <span class="table-product__meta text-mono"><?= e($feature['slug']) ?></span>
                                    </td>
                                    <td><?= e($feature['unit'] ?: '—') ?></td>
                                    <td><span class="chip chip--neutral"><?= e($inputTypes[$feature['input_type']] ?? $feature['input_type']) ?></span></td>
                                    <td><?= e($types[$feature['applies_to']] ?? $feature['applies_to']) ?></td>
                                    <td class="small">
                                        <?php if ((int) $feature['filterable'] === 1): ?><span class="chip chip--accent">Filtro</span><?php endif; ?>
                                        <?php if ((int) $feature['comparable'] === 1): ?><span class="chip chip--info">Comparar</span><?php endif; ?>
                                        <?php if ((int) $feature['public'] !== 1): ?><span class="chip chip--neutral">Interna</span><?php endif; ?>
                                        <?php if ((int) $feature['active'] !== 1): ?><span class="chip chip--danger">Inactiva</span><?php endif; ?>
                                    </td>
                                    <td class="num"><?= (int) $feature['uses'] ?></td>
                                    <td class="actions">
                                        <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#featModal<?= (int) $feature['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" action="<?= admin_url('caracteristicas/' . (int) $feature['id'] . '/eliminar') ?>"
                                              class="d-inline"
                                              data-confirm="¿Eliminar «<?= e($feature['name']) ?>»? Se perderán los <?= (int) $feature['uses'] ?> valor(es) cargado(s) en los productos.">
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
        <?php endforeach; ?>
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
                               placeholder="Ej: Altura replegada">
                    </div>

                    <div class="col-8">
                        <label class="form-label" for="f-group">Grupo</label>
                        <input type="text" class="form-control" id="f-group" name="group_name" maxlength="80"
                               list="featureGroups" value="General">
                        <datalist id="featureGroups">
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= e($group) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-4">
                        <label class="form-label" for="f-unit">Unidad</label>
                        <input type="text" class="form-control" id="f-unit" name="unit" maxlength="20" placeholder="mm">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="f-input">Tipo de dato *</label>
                        <select class="form-select" id="f-input" name="input_type" required>
                            <?php foreach ($inputTypes as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="f-applies">Aplica a *</label>
                        <select class="form-select" id="f-applies" name="applies_to" required>
                            <?php foreach ($types as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="f-options">Opciones (una por línea)</label>
                        <textarea class="form-control" id="f-options" name="options" rows="3" maxlength="2000"
                                  placeholder="Sólo para el tipo «Lista de opciones»"></textarea>
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="f-order">Orden</label>
                        <input type="number" class="form-control" id="f-order" name="sort_order" value="0">
                    </div>

                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="filterable" value="1"> Usar como filtro</label>
                        <label class="filter-check"><input type="checkbox" name="comparable" value="1" checked> Mostrar en el comparador</label>
                        <label class="filter-check"><input type="checkbox" name="public" value="1" checked> Visible en el sitio</label>
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activa</label>
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
                            <div class="col-8">
                                <label class="form-label">Grupo</label>
                                <input type="text" class="form-control" name="group_name" maxlength="80" value="<?= e($feature['group_name']) ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Unidad</label>
                                <input type="text" class="form-control" name="unit" maxlength="20" value="<?= e($feature['unit'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tipo de dato *</label>
                                <select class="form-select" name="input_type" required>
                                    <?php foreach ($inputTypes as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $feature['input_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Aplica a *</label>
                                <select class="form-select" name="applies_to" required>
                                    <?php foreach ($types as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $feature['applies_to'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Opciones (una por línea)</label>
                                <textarea class="form-control" name="options" rows="3" maxlength="2000"><?php
                                    $options = json_decode((string) ($feature['options'] ?? ''), true);
                                    echo e(is_array($options) ? implode("\n", $options) : '');
                                ?></textarea>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $feature['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="filter-check"><input type="checkbox" name="filterable" value="1" <?= (int) $feature['filterable'] === 1 ? 'checked' : '' ?>> Usar como filtro</label>
                                <label class="filter-check"><input type="checkbox" name="comparable" value="1" <?= (int) $feature['comparable'] === 1 ? 'checked' : '' ?>> Comparador</label>
                                <label class="filter-check"><input type="checkbox" name="public" value="1" <?= (int) $feature['public'] === 1 ? 'checked' : '' ?>> Visible en el sitio</label>
                                <label class="filter-check"><input type="checkbox" name="active" value="1" <?= (int) $feature['active'] === 1 ? 'checked' : '' ?>> Activa</label>
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
