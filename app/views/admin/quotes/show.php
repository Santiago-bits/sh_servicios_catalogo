<?php
/**
 * ARCHIVO: app/views/admin/quotes/show.php
 * Detalle de una cotización con vista previa y acciones de envío.
 */

$badge = quote_status_badge((string) $quote['status']);
?>

<div class="card-admin no-print">
    <div class="card-admin__head">
        <h2>
            <i class="bi bi-file-earmark-text"></i>
            <?= e($quote['number']) ?>
            <span class="chip chip--<?= e($badge['class']) ?> ms-2"><?= e($badge['label']) ?></span>
        </h2>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/pdf') ?>" target="_blank" class="btn btn-dark-2 btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Ver PDF
            </a>
            <a href="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/pdf?descargar=1') ?>" class="btn btn-ghost btn-sm">
                <i class="bi bi-download"></i> Descargar
            </a>

            <?php if (can('quotes.edit') && !empty($quote['customer_email'])): ?>
                <form method="post" action="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/email') ?>" class="d-inline"
                      data-confirm="¿Enviar la cotización por email a <?= e($quote['customer_email']) ?>?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-envelope"></i> Enviar por email</button>
                </form>
            <?php endif; ?>

            <?php if ($whatsappLink !== '#'): ?>
                <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                    <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
                </a>
            <?php endif; ?>

            <?php if (can('quotes.edit')): ?>
                <a href="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/editar') ?>" class="btn btn-accent btn-sm">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (can('quotes.edit')): ?>
        <div class="card-admin__body">
            <form method="post" action="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/estado') ?>"
                  class="d-flex flex-wrap gap-2 align-items-end">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="q-status">Cambiar estado</label>
                    <select class="form-select" id="q-status" name="status" style="min-width:190px">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $quote['status'] === $status ? 'selected' : '' ?>>
                                <?= e(quote_status_badge($status)['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-dark-2"><i class="bi bi-check-lg"></i> Actualizar estado</button>

                <div class="ms-auto text-muted-2 small">
                    <?php if (!empty($quote['sent_at'])): ?>
                        Enviada el <?= e(date_es((string) $quote['sent_at'], true)) ?>
                    <?php endif; ?>
                    <?php if (!empty($quote['responded_at'])): ?>
                        · Respondida el <?= e(date_es((string) $quote['responded_at'], true)) ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Vista previa tipo documento -->
<div class="doc-preview">
    <div class="doc-preview__head">
        <div>
            <div class="brand__mark mb-2"><i class="bi bi-truck-front-fill"></i></div>
            <strong style="font-size:1.1rem;text-transform:uppercase;letter-spacing:.05em">
                <?= e(setting('company_name', 'SH Servicios')) ?>
            </strong>
            <div class="text-muted-2 small">
                <?= e(setting('contact_address', '')) ?><?= setting('contact_city') ? ', ' . e(setting('contact_city')) : '' ?><br>
                <?= e(setting('contact_phone', '')) ?> · <?= e(setting('contact_email', '')) ?>
            </div>
        </div>

        <div class="text-end">
            <h1 style="font-size:1.6rem;margin:0">COTIZACIÓN</h1>
            <div class="product-code d-inline-block mt-1"><?= e($quote['number']) ?></div>
            <div class="text-muted-2 small mt-2">
                Fecha: <?= e(date_es((string) $quote['created_at'])) ?><br>
                Validez: <?= e(date_es($quote['valid_until'] ?? null)) ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <h2 class="form-section__title"><i class="bi bi-person"></i> Cliente</h2>
            <table class="table-admin">
                <tbody>
                    <tr><td class="text-muted-2">Nombre</td><td class="fw-bold"><?= e($quote['customer_name']) ?></td></tr>
                    <tr><td class="text-muted-2">Empresa</td><td><?= e($quote['customer_company'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted-2">CUIT</td><td><?= e($quote['customer_taxid'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted-2">Email</td><td><?= e($quote['customer_email'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted-2">Teléfono</td><td><?= e($quote['customer_phone'] ?: '—') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="col-md-6">
            <h2 class="form-section__title"><i class="bi bi-info-circle"></i> Cotización</h2>
            <table class="table-admin">
                <tbody>
                    <tr><td class="text-muted-2">Estado</td><td><span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span></td></tr>
                    <tr><td class="text-muted-2">Origen</td><td><?= $quote['source'] === 'web' ? 'Formulario web' : 'Carga interna' ?></td></tr>
                    <tr><td class="text-muted-2">Asesor</td><td><?= e($quote['user_name'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted-2">Moneda</td><td><?= e($quote['currency']) ?></td></tr>
                    <?php if (!empty($quote['financing_name'])): ?>
                        <tr><td class="text-muted-2">Financiación</td><td><?= e($quote['financing_name']) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h2 class="form-section__title"><i class="bi bi-list-check"></i> Detalle</h2>
    <div class="table-responsive-admin">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="num">Cant.</th>
                    <th class="num">Unitario</th>
                    <th class="num">Desc.</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($quote['items'] as $item): ?>
                <tr>
                    <td class="text-mono"><?= e($item['code'] ?: '—') ?></td>
                    <td>
                        <?= e($item['description']) ?>
                        <?php if (!empty($item['product_id'])): ?>
                            <a href="<?= admin_url(($item['product_type'] === 'machine' ? 'maquinaria/' : 'repuestos/') . (int) $item['product_id'] . '/editar') ?>"
                               class="text-muted-2 small d-block">Ver producto</a>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= e(number_es((float) $item['quantity'], 0)) ?></td>
                    <td class="num"><?= e(money((float) $item['unit_price'], (string) $quote['currency'])) ?></td>
                    <td class="num"><?= (float) $item['discount_percent'] > 0 ? e(percent((float) $item['discount_percent'], 0)) : '—' ?></td>
                    <td class="num"><strong><?= e(money((float) $item['line_total'], (string) $quote['currency'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-7">
            <?php if (!empty($quote['payments'])): ?>
                <h2 class="form-section__title"><i class="bi bi-calendar-check"></i> Plan de pagos</h2>
                <table class="table-admin">
                    <tbody>
                    <?php foreach ($quote['payments'] as $payment): ?>
                        <tr>
                            <td><?= e($payment['concept']) ?></td>
                            <td class="text-muted-2"><?= e(date_es($payment['due_date'] ?? null)) ?></td>
                            <td class="num fw-bold"><?= e(money((float) $payment['amount'], (string) $quote['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($quote['notes'])): ?>
                <h2 class="form-section__title mt-4"><i class="bi bi-chat-left-text"></i> Observaciones</h2>
                <p class="text-muted-2"><?= nl2br(e($quote['notes'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($quote['conditions'])): ?>
                <h2 class="form-section__title mt-4"><i class="bi bi-file-text"></i> Condiciones</h2>
                <p class="text-muted-2 small"><?= nl2br(e($quote['conditions'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="col-md-5">
            <div class="summary-box" style="position:static">
                <div class="summary-row"><span>Subtotal</span><strong><?= e(money((float) $quote['subtotal'], (string) $quote['currency'])) ?></strong></div>
                <?php if ((float) $quote['discount_amount'] > 0): ?>
                    <div class="summary-row"><span>Descuento</span><strong>− <?= e(money((float) $quote['discount_amount'], (string) $quote['currency'])) ?></strong></div>
                <?php endif; ?>
                <?php if ((float) $quote['shipping_cost'] > 0): ?>
                    <div class="summary-row"><span>Transporte</span><strong><?= e(money((float) $quote['shipping_cost'], (string) $quote['currency'])) ?></strong></div>
                <?php endif; ?>
                <?php if ((float) $quote['other_costs'] > 0): ?>
                    <div class="summary-row"><span>Otros costos</span><strong><?= e(money((float) $quote['other_costs'], (string) $quote['currency'])) ?></strong></div>
                <?php endif; ?>
                <?php if ((float) $quote['interest_amount'] > 0): ?>
                    <div class="summary-row"><span>Intereses</span><strong><?= e(money((float) $quote['interest_amount'], (string) $quote['currency'])) ?></strong></div>
                <?php endif; ?>

                <div class="summary-total">
                    <span>Total</span>
                    <strong><?= e(money((float) $quote['total'], (string) $quote['currency'])) ?></strong>
                </div>

                <?php if ((int) $quote['installments'] > 0): ?>
                    <p class="form-hint mt-3 mb-0">
                        Anticipo <?= e(money((float) $quote['down_payment'], (string) $quote['currency'])) ?> +
                        <?= (int) $quote['installments'] ?> cuotas de
                        <strong><?= e(money((float) $quote['installment_amount'], (string) $quote['currency'])) ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
