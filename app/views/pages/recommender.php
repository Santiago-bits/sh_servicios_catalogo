<?php
/**
 * ARCHIVO: app/views/pages/recommender.php
 * Asistente "¿Qué máquina necesitás?"
 */

$answers = $answers ?? [];
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Asistente</span></nav>
        <h1>¿Qué máquina necesitás?</h1>
        <p>Respondé seis preguntas y te mostramos los equipos de nuestro catálogo que mejor se adaptan a tu operación.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-<?= $results === null ? '8' : '5' ?>">
                <form method="post" action="<?= url('recomendador') ?>">
                    <?= csrf_field() ?>

                    <div class="wizard-step">
                        <span class="wizard-step__num">1</span>
                        <label class="form-label" for="rc-capacidad">¿Cuánto peso necesitás levantar? (kg)</label>
                        <input type="number" class="form-control form-control-lg" id="rc-capacidad" name="capacidad"
                               min="0" step="100" placeholder="Ej: 2500"
                               value="<?= e($answers['capacidad'] ?? '') ?>">
                        <p class="form-hint">Tomá el pallet más pesado que movés habitualmente, con un margen de seguridad.</p>
                    </div>

                    <div class="wizard-step">
                        <span class="wizard-step__num">2</span>
                        <label class="form-label" for="rc-altura">¿A qué altura necesitás elevar? (mm)</label>
                        <input type="number" class="form-control form-control-lg" id="rc-altura" name="altura"
                               min="0" step="100" placeholder="Ej: 4500"
                               value="<?= e($answers['altura'] ?? '') ?>">
                        <p class="form-hint">Medí desde el piso hasta la última posición del rack.</p>
                    </div>

                    <div class="wizard-step">
                        <span class="wizard-step__num">3</span>
                        <label class="form-label d-block">¿Dónde va a trabajar?</label>
                        <div class="option-cards">
                            <?php foreach ([
                                'interior' => ['bi-building', 'Interior'],
                                'exterior' => ['bi-tree', 'Exterior'],
                                'mixto'    => ['bi-arrow-left-right', 'Mixto'],
                            ] as $value => [$icon, $label]): ?>
                                <label class="option-card">
                                    <input type="radio" name="uso" value="<?= $value ?>"
                                        <?= ($answers['uso'] ?? 'interior') === $value ? 'checked' : '' ?>>
                                    <i class="bi <?= $icon ?>"></i>
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step">
                        <span class="wizard-step__num">4</span>
                        <label class="form-label d-block">¿Tenés preferencia de combustible?</label>
                        <div class="option-cards">
                            <?php foreach ([
                                ''          => ['bi-question-circle', 'Indistinto'],
                                'electrico' => ['bi-lightning-charge-fill', 'Eléctrico'],
                                'diesel'    => ['bi-fuel-pump-fill', 'Diésel'],
                                'gas'       => ['bi-fire', 'Gas / GLP'],
                            ] as $value => [$icon, $label]): ?>
                                <label class="option-card">
                                    <input type="radio" name="combustible" value="<?= $value ?>"
                                        <?= ($answers['combustible'] ?? '') === $value ? 'checked' : '' ?>>
                                    <i class="bi <?= $icon ?>"></i>
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step">
                        <span class="wizard-step__num">5</span>
                        <label class="form-label d-block">¿Cuántas horas por día lo vas a usar?</label>
                        <div class="option-cards">
                            <?php foreach ([
                                'poco'      => ['bi-clock', 'Menos de 4 h'],
                                'medio'     => ['bi-clock-history', '4 a 8 h'],
                                'intensivo' => ['bi-hourglass-split', 'Más de 8 h'],
                            ] as $value => [$icon, $label]): ?>
                                <label class="option-card">
                                    <input type="radio" name="horas" value="<?= $value ?>"
                                        <?= ($answers['horas'] ?? '') === $value ? 'checked' : '' ?>>
                                    <i class="bi <?= $icon ?>"></i>
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step">
                        <span class="wizard-step__num">6</span>
                        <label class="form-label" for="rc-presupuesto">¿Cuál es tu presupuesto aproximado?</label>
                        <input type="number" class="form-control form-control-lg" id="rc-presupuesto" name="presupuesto"
                               min="0" step="100000" placeholder="Opcional"
                               value="<?= e($answers['presupuesto'] ?? '') ?>">
                        <p class="form-hint">Si lo dejás vacío, te mostramos todas las opciones que cumplen los requisitos.</p>
                    </div>

                    <button type="submit" class="btn btn-accent btn-lg w-100">
                        <i class="bi bi-magic"></i> Ver equipos recomendados
                    </button>
                </form>
            </div>

            <div class="col-lg-<?= $results === null ? '4' : '7' ?>">
                <?php if ($results === null): ?>
                    <div class="panel">
                        <div class="panel__head"><h2><i class="bi bi-info-circle"></i> Cómo funciona</h2></div>
                        <div class="panel__body">
                            <p class="text-muted-2">
                                El asistente filtra el catálogo con los datos que cargás y ordena los equipos por
                                capacidad y precio. No reemplaza al asesoramiento técnico: si la operación es
                                exigente, conviene que veamos el lugar.
                            </p>
                            <ul class="service-card__list">
                                <li>Usamos la capacidad y la altura como filtros mínimos</li>
                                <li>Para trabajo en interior priorizamos equipos eléctricos</li>
                                <li>El presupuesto admite un 15% de margen</li>
                            </ul>
                            <a href="<?= url('contacto') ?>" class="btn btn-outline-accent w-100">
                                <i class="bi bi-headset"></i> Prefiero que me asesoren
                            </a>
                        </div>
                    </div>

                <?php elseif ($results === []): ?>
                    <div class="empty-state">
                        <i class="bi bi-emoji-neutral"></i>
                        <h3>No encontramos equipos con esos requisitos</h3>
                        <p>Probá ampliando el presupuesto o bajando la capacidad. También conseguimos equipos a pedido.</p>
                        <a href="<?= url('contacto') ?>" class="btn btn-accent"><i class="bi bi-send"></i> Consultar</a>
                    </div>

                <?php else: ?>
                    <div class="section-head mb-3">
                        <div>
                            <span class="eyebrow">Resultado</span>
                            <h2 class="section-title" style="font-size:1.5rem"><?= count($results) ?> equipo(s) recomendado(s)</h2>
                        </div>
                    </div>

                    <?php foreach ($results as $product): ?>
                        <div class="quote-item mb-3">
                            <img class="quote-item__img" src="<?= e(upload_url($product['thumb'] ?? null)) ?>" alt="<?= e($product['name']) ?>">
                            <div class="quote-item__body">
                                <h3 class="quote-item__title">
                                    <a href="<?= e(machine_url($product)) ?>"><?= e($product['name']) ?></a>
                                </h3>
                                <div class="quote-item__meta mb-2">
                                    <?= e($product['brand_name'] ?? '') ?> · <?= e($product['code']) ?>
                                </div>

                                <?php foreach ($product['reasons'] as $reason): ?>
                                    <div class="match-reason">
                                        <i class="bi bi-check-circle-fill"></i> <?= e($reason) ?>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <a href="<?= e(machine_url($product)) ?>" class="btn btn-outline-accent btn-sm">Ver ficha</a>
                                    <button type="button" class="btn btn-accent btn-sm" data-quote-add="<?= (int) $product['id'] ?>">
                                        <i class="bi bi-file-earmark-plus"></i> Cotizar
                                    </button>
                                </div>
                            </div>
                            <div class="quote-item__price">
                                <?= e(App\Services\PriceService::displayPrice($product)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
