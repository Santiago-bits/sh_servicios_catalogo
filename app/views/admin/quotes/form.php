<?php
/**
 * ARCHIVO: app/views/admin/quotes/form.php
 * Alta y edición de cotizaciones desde el panel.
 */

$id     = $isEdit ? (int) $quote['id'] : 0;
$action = $isEdit ? admin_url('cotizaciones/' . $id) : admin_url('cotizaciones');

// Catálogo liviano para el buscador de ítems (sin costos).
$catalogJs = array_map(static fn (array $p): array => [
    'id'    => (int) $p['id'],
    'code'  => (string) $p['code'],
    'name'  => (string) $p['name'],
    'type'  => (string) $p['type'],
    'price' => (float) ((int) $p['is_offer'] === 1 && (float) $p['offer_price'] > 0 ? $p['offer_price'] : $p['final_price']),
], $catalog);
?>

<form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-8">

            <!-- Cliente -->
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-person-vcard"></i> Datos del cliente</h2>
                    <span class="chip chip--accent text-mono"><?= e($nextNumber) ?></span>
                </div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_name">Cliente *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required
                                   maxlength="160" value="<?= e($isEdit ? $quote['customer_name'] : old('customer_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_company">Empresa</label>
                            <input type="text" class="form-control" id="customer_company" name="customer_company"
                                   maxlength="160" value="<?= e($isEdit ? ($quote['customer_company'] ?? '') : old('customer_company')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="customer_email">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email"
                                   maxlength="160" value="<?= e($isEdit ? ($quote['customer_email'] ?? '') : old('customer_email')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="customer_phone">Teléfono</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone"
                                   maxlength="40" value="<?= e($isEdit ? ($quote['customer_phone'] ?? '') : old('customer_phone')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="customer_taxid">CUIT</label>
                            <input type="text" class="form-control" id="customer_taxid" name="customer_taxid"
                                   maxlength="40" value="<?= e($isEdit ? ($quote['customer_taxid'] ?? '') : old('customer_taxid')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="customer_address">Dirección</label>
                            <input type="text" class="form-control" id="customer_address" name="customer_address"
                                   maxlength="255" value="<?= e($isEdit ? ($quote['customer_address'] ?? '') : old('customer_address')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ítems -->
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-list-check"></i> Ítems de la cotización</h2>
                    <button type="button" class="btn btn-ghost btn-sm" id="quoteAddItem">
                        <i class="bi bi-plus-lg"></i> Ítem manual
                    </button>
                </div>

                <div class="card-admin__body">
                    <div class="quote-line fw-bold text-muted-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em">
                        <span>Descripción</span>
                        <span>Cantidad</span>
                        <span>Precio unit.</span>
                        <span>Desc. %</span>
                        <span class="text-end">Total</span>
                        <span></span>
                    </div>

                    <div id="quoteItems">
                        <?php foreach ($items as $index => $item): ?>
                            <div class="quote-line">
                                <div>
                                    <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= (int) ($item['product_id'] ?? 0) ?>">
                                    <input type="hidden" name="items[<?= $index ?>][item_type]" value="<?= e($item['item_type']) ?>">
                                    <input type="text" class="form-control form-control-sm mb-1"
                                           name="items[<?= $index ?>][description]" value="<?= e($item['description']) ?>" required>
                                    <input type="text" class="form-control form-control-sm text-mono"
                                           name="items[<?= $index ?>][code]" value="<?= e($item['code'] ?? '') ?>" placeholder="Código">
                                </div>
                                <input type="text" class="form-control form-control-sm" name="items[<?= $index ?>][quantity]"
                                       value="<?= e(number_format((float) $item['quantity'], 2, '.', '')) ?>">
                                <input type="text" class="form-control form-control-sm" name="items[<?= $index ?>][unit_price]"
                                       value="<?= e(number_format((float) $item['unit_price'], 2, '.', '')) ?>">
                                <input type="text" class="form-control form-control-sm" name="items[<?= $index ?>][discount_percent]"
                                       value="<?= e(number_format((float) $item['discount_percent'], 2, '.', '')) ?>">
                                <span class="quote-line__total" data-line-total><?= e(money((float) $item['line_total'])) ?></span>
                                <button type="button" class="btn-icon btn-icon--danger" data-remove-line>
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($items)): ?>
                        <p class="form-hint mt-3">Buscá productos abajo o agregá un ítem manual.</p>
                    <?php endif; ?>
                </div>

                <div class="card-admin__foot">
                    <label class="form-label" for="quotePickerSearch">Agregar del catálogo</label>
                    <input type="search" class="form-control mb-2" id="quotePickerSearch"
                           placeholder="Buscá por nombre o código…">
                    <div class="picker" id="quotePicker" style="max-height:230px"></div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-chat-left-text"></i> Observaciones y condiciones</h2></div>
                <div class="card-admin__body">
                    <div class="mb-3">
                        <label class="form-label" for="notes">Observaciones</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="4000"><?= e($isEdit ? ($quote['notes'] ?? '') : '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label" for="conditions">Condiciones comerciales</label>
                        <textarea class="form-control" id="conditions" name="conditions" rows="5" maxlength="4000"><?= e($isEdit ? ($quote['conditions'] ?? '') : $conditions) ?></textarea>
                        <p class="form-hint">Se imprimen al pie del PDF. El texto por defecto se configura en Configuración.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ LATERAL ============ -->
        <div class="col-lg-4">
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-calculator"></i> Totales</h2></div>
                <div class="card-admin__body">
                    <div class="summary-row"><span>Subtotal</span><strong id="sumSubtotal">$0,00</strong></div>

                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <label class="form-label" for="discount_percent">Descuento %</label>
                            <input type="text" class="form-control form-control-sm" id="discount_percent" name="discount_percent"
                                   value="<?= e($isEdit ? number_format((float) $quote['discount_percent'], 2, '.', '') : '0') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="discount_amount">Descuento $</label>
                            <input type="text" class="form-control form-control-sm" id="discount_amount" name="discount_amount"
                                   value="<?= e($isEdit ? number_format((float) $quote['discount_amount'], 2, '.', '') : '0') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="shipping_cost">Transporte</label>
                            <input type="text" class="form-control form-control-sm" id="shipping_cost" name="shipping_cost"
                                   value="<?= e($isEdit ? number_format((float) $quote['shipping_cost'], 2, '.', '') : '0') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="other_costs">Otros costos</label>
                            <input type="text" class="form-control form-control-sm" id="other_costs" name="other_costs"
                                   value="<?= e($isEdit ? number_format((float) $quote['other_costs'], 2, '.', '') : '0') ?>">
                        </div>
                    </div>

                    <div class="summary-row mt-2"><span>Descuento</span><strong id="sumDiscount">$0,00</strong></div>
                    <div class="summary-row"><span>Transporte</span><strong id="sumShipping">$0,00</strong></div>
                    <div class="summary-row"><span>Otros costos</span><strong id="sumOther">$0,00</strong></div>

                    <div class="summary-total">
                        <span>Total</span>
                        <strong id="sumTotal">$0,00</strong>
                    </div>
                </div>
            </div>

            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-calendar-check"></i> Financiación</h2></div>
                <div class="card-admin__body">
                    <label class="form-label" for="financing_option_id">Plan</label>
                    <select class="form-select" id="financing_option_id" name="financing_option_id">
                        <option value="">Sin financiación</option>
                        <?php foreach ($financing as $option): ?>
                            <?php if ((int) $option['active'] !== 1) { continue; } ?>
                            <option value="<?= (int) $option['id'] ?>"
                                    data-down="<?= e((float) $option['down_payment_percent']) ?>"
                                    data-installments="<?= (int) $option['installments'] ?>"
                                    data-interest="<?= e((float) $option['interest_percent']) ?>"
                                    <?= $isEdit && (int) $quote['financing_option_id'] === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= e($option['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="mt-3 p-3" style="background:#FAFBFC;border-radius:6px" id="financePreview">
                        <span class="text-muted-2">Sin plan de financiación.</span>
                    </div>
                </div>
            </div>

            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-sliders"></i> Datos de la cotización</h2></div>
                <div class="card-admin__body">
                    <div class="mb-3">
                        <label class="form-label" for="status">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= $isEdit && $quote['status'] === $status ? 'selected' : '' ?>>
                                    <?= e(quote_status_badge($status)['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="currency">Moneda</label>
                        <select class="form-select" id="currency" name="currency">
                            <option value="ARS" <?= $isEdit && $quote['currency'] === 'ARS' ? 'selected' : '' ?>>Pesos (ARS)</option>
                            <option value="USD" <?= $isEdit && $quote['currency'] === 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="valid_until">Válida hasta</label>
                        <input type="date" class="form-control" id="valid_until" name="valid_until"
                               value="<?= e($isEdit ? ($quote['valid_until'] ?? '') : date('Y-m-d', strtotime('+' . $validity . ' days'))) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-sticky-actions">
        <a href="<?= admin_url('cotizaciones') ?>" class="btn btn-ghost">Cancelar</a>
        <?php if ($isEdit): ?>
            <a href="<?= admin_url('cotizaciones/' . $id . '/pdf') ?>" target="_blank" class="btn btn-outline-accent">
                <i class="bi bi-file-earmark-pdf"></i> Ver PDF
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear cotización' ?>
        </button>
    </div>
</form>

<!-- Plantilla de fila (la usa admin.js) -->
<template id="quoteItemTemplate">
    <div class="quote-line">
        <div>
            <input type="hidden" name="items[__INDEX__][product_id]" value="">
            <input type="hidden" name="items[__INDEX__][item_type]" value="other">
            <input type="text" class="form-control form-control-sm mb-1" name="items[__INDEX__][description]" placeholder="Descripción" required>
            <input type="text" class="form-control form-control-sm text-mono" name="items[__INDEX__][code]" placeholder="Código">
        </div>
        <input type="text" class="form-control form-control-sm" name="items[__INDEX__][quantity]" value="1">
        <input type="text" class="form-control form-control-sm" name="items[__INDEX__][unit_price]" value="0">
        <input type="text" class="form-control form-control-sm" name="items[__INDEX__][discount_percent]" value="0">
        <span class="quote-line__total" data-line-total>$0,00</span>
        <button type="button" class="btn-icon btn-icon--danger" data-remove-line><i class="bi bi-x-lg"></i></button>
    </div>
</template>

<script>
    window.QUOTE_CATALOG = <?= js($catalogJs) ?>;
</script>
