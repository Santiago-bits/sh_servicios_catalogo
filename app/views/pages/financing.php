<?php
/**
 * ARCHIVO: app/views/pages/financing.php
 */
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Financiación</span></nav>
        <h1>Financiación</h1>
        <p>Planes configurables para maquinaria y repuestos: contado, anticipo más cuotas, financiación propia y leasing.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <span class="eyebrow">Formas de pago</span>
                <h2 class="section-title">Elegí cómo pagar</h2>
                <div class="divider-accent"></div>

                <div class="row g-3 mt-2">
                    <?php foreach ($methods as $method): ?>
                        <div class="col-md-6">
                            <div class="cat-card h-100">
                                <span class="cat-card__icon"><i class="bi <?= e($method['icon'] ?: 'bi-cash-coin') ?>"></i></span>
                                <h3 class="cat-card__title"><?= e($method['name']) ?></h3>
                                <p class="text-muted-2 small mb-0"><?= e($method['description']) ?></p>
                                <?php if ((float) $method['discount_percent'] > 0): ?>
                                    <span class="tag tag--success mt-2">
                                        <?= e(percent((float) $method['discount_percent'], 0)) ?> de descuento
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Calculadora libre -->
                <div class="calc" id="financeCalc" data-currency="ARS">
                    <h3><i class="bi bi-calculator"></i> Simulá tu financiación</h3>
                    <p class="small text-muted-2 mb-3">Ingresá el monto y ajustá anticipo, cuotas e interés.</p>

                    <div class="mb-3">
                        <label for="calcPrice">Monto de la operación</label>
                        <input type="number" class="form-control" id="calcPrice" value="20000000" min="0" step="100000">
                    </div>

                    <div class="mb-3">
                        <label for="calcDown">Anticipo</label>
                        <input type="number" class="form-control" id="calcDown" value="6000000" min="0" step="100000">
                        <input type="range" id="calcDownRange" min="0" max="100" value="30" class="mt-2">
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="calcInstallments">Cuotas</label>
                            <select class="form-select" id="calcInstallments">
                                <?php foreach ([1, 3, 6, 12, 18, 24, 36, 48] as $n): ?>
                                    <option value="<?= $n ?>" <?= $n === 12 ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="calcInterest">Interés total (%)</label>
                            <input type="number" class="form-control" id="calcInterest" value="24" min="0" max="300" step="1">
                        </div>
                    </div>

                    <div class="calc-result">
                        <div class="calc-result__item"><span>Anticipo</span><strong id="outDown">—</strong></div>
                        <div class="calc-result__item"><span>Saldo</span><strong id="outBalance">—</strong></div>
                        <div class="calc-result__item"><span>Interés</span><strong id="outInterest">—</strong></div>
                        <div class="calc-result__item calc-result__item--main">
                            <span><span id="outCount">12</span> cuotas de</span><strong id="outFee">—</strong>
                        </div>
                        <div class="calc-result__item"><span>Total final</span><strong id="outTotal">—</strong></div>
                    </div>

                    <p class="small text-muted-2 mt-3 mb-0">
                        Valores orientativos. La cuota definitiva se confirma en la cotización.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($options)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Planes vigentes</span>
                <h2 class="section-title">Planes de financiación</h2>
                <p class="section-lead">Los planes se aplican según el tipo de producto y el monto de la operación.</p>
            </div>
        </div>

        <div class="table-responsive-admin">
            <table class="compare-table" style="background:#fff;border-radius:10px;overflow:hidden">
                <thead>
                    <tr>
                        <th style="text-align:left">Plan</th>
                        <th>Anticipo</th>
                        <th>Cuotas</th>
                        <th>Interés</th>
                        <th>Aplica a</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($options as $option): ?>
                    <tr>
                        <th scope="row" style="background:#fff">
                            <strong class="d-block text-dark"><?= e($option['name']) ?></strong>
                            <small class="text-muted-2"><?= e($option['description'] ?? '') ?></small>
                        </th>
                        <td><?= e(percent((float) $option['down_payment_percent'], 0)) ?></td>
                        <td><?= (int) $option['installments'] ?></td>
                        <td>
                            <?= e(percent((float) $option['interest_percent'], 0)) ?>
                            <small class="text-muted-2 d-block"><?= $option['interest_type'] === 'mensual' ? 'mensual' : 'total' ?></small>
                        </td>
                        <td>
                            <?= match ($option['applies_to']) {
                                'machine'    => 'Maquinaria',
                                'spare_part' => 'Repuestos',
                                default      => 'Todo',
                            } ?>
                        </td>
                        <td class="small">
                            <?php if ($option['min_amount'] !== null): ?>
                                desde <?= e(money((float) $option['min_amount'])) ?>
                            <?php endif; ?>
                            <?php if ($option['max_amount'] !== null): ?>
                                hasta <?= e(money((float) $option['max_amount'])) ?>
                            <?php endif; ?>
                            <?php if ($option['min_amount'] === null && $option['max_amount'] === null): ?>
                                Sin límite
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿Querés un plan a medida?</h2>
                <p>Armamos la financiación según el equipo, el plazo y tu operación.</p>
            </div>
            <a href="<?= url('cotizador') ?>" class="btn btn-dark-2 btn-lg">
                <i class="bi bi-file-earmark-text"></i> Pedir cotización
            </a>
        </div>
    </div>
</section>
