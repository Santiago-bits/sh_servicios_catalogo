<?php
/**
 * ARCHIVO: app/views/admin/financing/index.php
 */
?>
<div class="row g-3">
    <div class="col-xl-8">

        <!-- Planes -->
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-calendar-check"></i> Planes de financiación</h2>
                <span class="text-muted-2 small"><?= count($options) ?> plan(es)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Método</th>
                                <th class="num">Anticipo</th>
                                <th class="num">Cuotas</th>
                                <th class="num">Interés</th>
                                <th>Aplica a</th>
                                <th>Rango</th>
                                <th>Estado</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($options as $option): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e($option['name']) ?></span>
                                    <span class="table-product__meta"><?= e(str_limit((string) $option['description'], 46)) ?></span>
                                </td>
                                <td class="text-muted-2"><?= e($option['method_name'] ?? '—') ?></td>
                                <td class="num"><?= e(percent((float) $option['down_payment_percent'], 0)) ?></td>
                                <td class="num"><?= (int) $option['installments'] ?></td>
                                <td class="num">
                                    <?= e(percent((float) $option['interest_percent'], 0)) ?>
                                    <small class="d-block text-muted-2"><?= $option['interest_type'] === 'mensual' ? 'mensual' : 'total' ?></small>
                                </td>
                                <td><?= e($appliesTo[$option['applies_to']] ?? $option['applies_to']) ?></td>
                                <td class="small text-muted-2">
                                    <?= $option['min_amount'] !== null ? 'desde ' . money((float) $option['min_amount']) : '' ?>
                                    <?= $option['max_amount'] !== null ? ' hasta ' . money((float) $option['max_amount']) : '' ?>
                                    <?= $option['min_amount'] === null && $option['max_amount'] === null ? 'Sin límite' : '' ?>
                                </td>
                                <td>
                                    <span class="chip chip--<?= (int) $option['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $option['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                    <?php if ((int) $option['featured'] === 1): ?>
                                        <span class="chip chip--accent">Destacado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#planModal<?= (int) $option['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= admin_url('financiacion/' . (int) $option['id'] . '/eliminar') ?>"
                                          class="d-inline" data-confirm="¿Eliminar el plan «<?= e($option['name']) ?>»?">
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

        <!-- Vista previa -->
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-calculator"></i> Vista previa de los planes</h2>
                <form method="get" class="d-flex gap-2 align-items-center">
                    <label class="form-label m-0 small">Importe de ejemplo</label>
                    <input type="number" class="form-control form-control-sm" name="ejemplo" style="width:160px"
                           value="<?= (int) $sample ?>" step="100000">
                    <button type="submit" class="btn btn-dark-2 btn-sm">Calcular</button>
                </form>
            </div>
            <div class="card-admin__body">
                <?php if (empty($preview)): ?>
                    <p class="text-muted-2 mb-0">No hay planes aplicables a ese importe.</p>
                <?php else: ?>
                    <div class="finance-grid">
                        <?php foreach ($preview as $plan): ?>
                            <div class="finance-card <?= $plan['featured'] ? 'is-featured' : '' ?>">
                                <div class="finance-card__name"><?= e($plan['option_name']) ?></div>
                                <?php if ($plan['installments'] > 1): ?>
                                    <div class="finance-card__value">
                                        <?= (int) $plan['installments'] ?> × <?= e(money($plan['installment_amount'])) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="finance-card__value"><?= e(money($plan['total'])) ?></div>
                                <?php endif; ?>
                                <div class="finance-card__detail">
                                    <span>Anticipo: <?= e(money($plan['down_payment'])) ?></span>
                                    <span>Interés: <?= e(money($plan['interest_amount'])) ?></span>
                                    <span>Total: <strong><?= e(money($plan['total'])) ?></strong></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Métodos de pago -->
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-credit-card"></i> Métodos de pago</h2>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th>Descripción</th>
                                <th class="num">Descuento</th>
                                <th>Estado</th>
                                <th class="actions">Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($methods as $method): ?>
                            <tr>
                                <td>
                                    <i class="bi <?= e($method['icon'] ?: 'bi-cash') ?> text-accent"></i>
                                    <strong><?= e($method['name']) ?></strong>
                                </td>
                                <td class="text-muted-2 small"><?= e(str_limit((string) $method['description'], 60)) ?></td>
                                <td class="num"><?= e(percent((float) $method['discount_percent'], 0)) ?></td>
                                <td>
                                    <span class="chip chip--<?= (int) $method['active'] === 1 ? 'ok' : 'neutral' ?>">
                                        <?= (int) $method['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-icon" data-bs-toggle="modal" data-bs-target="#methodModal<?= (int) $method['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Formularios de alta -->
    <div class="col-xl-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nuevo plan</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('financiacion') ?>" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="p-name">Nombre *</label>
                        <input type="text" class="form-control" id="p-name" name="name" required maxlength="120"
                               placeholder="Ej: 30% + 12 cuotas">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="p-method">Método de pago</label>
                        <select class="form-select" id="p-method" name="payment_method_id">
                            <option value="">Sin asociar</option>
                            <?php foreach ($methods as $method): ?>
                                <option value="<?= (int) $method['id'] ?>"><?= e($method['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-down">Anticipo (%)</label>
                        <input type="text" class="form-control" id="p-down" name="down_payment_percent" value="30">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-inst">Cuotas *</label>
                        <input type="number" class="form-control" id="p-inst" name="installments" value="12" min="1" max="120" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-int">Interés (%)</label>
                        <input type="text" class="form-control" id="p-int" name="interest_percent" value="24">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-inttype">Tipo de interés *</label>
                        <select class="form-select" id="p-inttype" name="interest_type" required>
                            <option value="total">Total sobre el saldo</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-min">Monto mínimo</label>
                        <input type="text" class="form-control" id="p-min" name="min_amount">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="p-max">Monto máximo</label>
                        <input type="text" class="form-control" id="p-max" name="max_amount">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="p-applies">Aplica a *</label>
                        <select class="form-select" id="p-applies" name="applies_to" required>
                            <?php foreach ($appliesTo as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="p-desc">Descripción</label>
                        <input type="text" class="form-control" id="p-desc" name="description" maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="featured" value="1"> Destacado</label>
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activo</label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i> Crear plan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Nuevo método de pago</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('financiacion/metodos') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" for="m-name">Nombre *</label>
                        <input type="text" class="form-control" id="m-name" name="name" required maxlength="120">
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="m-icon">Ícono</label>
                        <input type="text" class="form-control text-mono" id="m-icon" name="icon" maxlength="60">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="m-disc">Descuento %</label>
                        <input type="text" class="form-control" id="m-disc" name="discount_percent" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="m-desc">Descripción</label>
                        <input type="text" class="form-control" id="m-desc" name="description" maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="active" value="1" checked> Activo</label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-dark-2 w-100"><i class="bi bi-plus-lg"></i> Crear método</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modales de edición de planes -->
<?php foreach ($options as $option): ?>
    <div class="modal fade" id="planModal<?= (int) $option['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($option['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="<?= admin_url('financiacion/' . (int) $option['id']) ?>">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($option['name']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Método de pago</label>
                                <select class="form-select" name="payment_method_id">
                                    <option value="">Sin asociar</option>
                                    <?php foreach ($methods as $method): ?>
                                        <option value="<?= (int) $method['id'] ?>" <?= (int) $option['payment_method_id'] === (int) $method['id'] ? 'selected' : '' ?>>
                                            <?= e($method['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Anticipo (%)</label>
                                <input type="text" class="form-control" name="down_payment_percent" value="<?= e(number_format((float) $option['down_payment_percent'], 2, '.', '')) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Cuotas *</label>
                                <input type="number" class="form-control" name="installments" required min="1" max="120" value="<?= (int) $option['installments'] ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Interés (%)</label>
                                <input type="text" class="form-control" name="interest_percent" value="<?= e(number_format((float) $option['interest_percent'], 2, '.', '')) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tipo de interés *</label>
                                <select class="form-select" name="interest_type" required>
                                    <option value="total" <?= $option['interest_type'] === 'total' ? 'selected' : '' ?>>Total sobre el saldo</option>
                                    <option value="mensual" <?= $option['interest_type'] === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Monto mínimo</label>
                                <input type="text" class="form-control" name="min_amount" value="<?= $option['min_amount'] !== null ? e(number_format((float) $option['min_amount'], 2, '.', '')) : '' ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Monto máximo</label>
                                <input type="text" class="form-control" name="max_amount" value="<?= $option['max_amount'] !== null ? e(number_format((float) $option['max_amount'], 2, '.', '')) : '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Aplica a *</label>
                                <select class="form-select" name="applies_to" required>
                                    <?php foreach ($appliesTo as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $option['applies_to'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <input type="text" class="form-control" name="description" maxlength="255" value="<?= e($option['description'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="filter-check"><input type="checkbox" name="featured" value="1" <?= (int) $option['featured'] === 1 ? 'checked' : '' ?>> Destacado</label>
                                <label class="filter-check"><input type="checkbox" name="active" value="1" <?= (int) $option['active'] === 1 ? 'checked' : '' ?>> Activo</label>
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

<!-- Modales de métodos -->
<?php foreach ($methods as $method): ?>
    <div class="modal fade" id="methodModal<?= (int) $method['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:0;border-radius:10px;overflow:hidden">
                <div class="modal-header" style="background:#111;color:#fff;border:0">
                    <h2 class="modal-title h6 mb-0">Editar «<?= e($method['name']) ?>»</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="<?= admin_url('financiacion/metodos/' . (int) $method['id']) ?>">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="name" required maxlength="120" value="<?= e($method['name']) ?>">
                            </div>
                            <div class="col-8">
                                <label class="form-label">Ícono</label>
                                <input type="text" class="form-control text-mono" name="icon" maxlength="60" value="<?= e($method['icon'] ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Descuento %</label>
                                <input type="text" class="form-control" name="discount_percent" value="<?= e(number_format((float) $method['discount_percent'], 2, '.', '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <input type="text" class="form-control" name="description" maxlength="255" value="<?= e($method['description'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= (int) $method['sort_order'] ?>">
                            </div>
                            <div class="col-12">
                                <label class="filter-check"><input type="checkbox" name="active" value="1" <?= (int) $method['active'] === 1 ? 'checked' : '' ?>> Activo</label>
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
