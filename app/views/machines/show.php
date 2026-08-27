<?php
/**
 * ARCHIVO: app/views/machines/show.php
 * Ficha completa de una máquina.
 *
 * @var \Core\View $view
 * @var array<string,mixed> $product
 */

use App\Services\PriceService;
use App\Services\CurrencyService;

$availability = availability_badge((string) $product['availability']);
$showPrice    = PriceService::isPublicPriceVisible($product);
$price        = PriceService::effectivePrice($product);
$hasOffer     = (int) $product['is_offer'] === 1 && (float) ($product['offer_price'] ?? 0) > 0;
$mainImage    = $images[0]['path'] ?? $product['image'] ?? null;
?>

<section class="page-hero" style="padding:26px 0 30px">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="<?= url() ?>">Inicio</a>
            <span><a href="<?= url('maquinaria') ?>">Maquinaria</a></span>
            <?php if (!empty($product['category_slug'])): ?>
                <span><a href="<?= e(url('maquinaria/' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a></span>
            <?php endif; ?>
            <span><?= e(str_limit((string) $product['name'], 46)) ?></span>
        </nav>
    </div>
</section>

<section class="section" style="padding-top:34px">
    <div class="container">
        <div class="product-layout">

            <!-- ============ GALERÍA ============ -->
            <div>
                <div class="gallery__main" id="galleryMain">
                    <img src="<?= e(upload_url($mainImage)) ?>" alt="<?= e($product['name']) ?>" width="800" height="600">
                    <span class="gallery__zoom-hint"><i class="bi bi-zoom-in"></i> Pasá el mouse para ampliar</span>
                </div>

                <?php if (count($images) > 1): ?>
                    <div class="gallery__thumbs">
                        <?php foreach ($images as $i => $image): ?>
                            <div class="gallery__thumb <?= $i === 0 ? 'is-active' : '' ?>"
                                 data-full="<?= e(upload_url($image['path'])) ?>"
                                 title="<?= e($image['zone'] ?: 'Foto ' . ($i + 1)) ?>">
                                <img src="<?= e(upload_url($image['thumb_path'] ?? $image['path'])) ?>"
                                     alt="<?= e($image['alt'] ?: $product['name']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Video -->
                <?php if (!empty($videos)): ?>
                    <div class="panel mt-4">
                        <div class="panel__head">
                            <h2><i class="bi bi-play-btn-fill"></i> Ver la máquina trabajando</h2>
                        </div>
                        <div class="panel__body">
                            <?php foreach (array_slice($videos, 0, 2) as $video): ?>
                                <div class="video-embed mb-3">
                                    <?php if ($video['provider'] === 'youtube'): ?>
                                        <iframe src="https://www.youtube-nocookie.com/embed/<?= e($video['video_ref']) ?>"
                                                title="<?= e($video['title']) ?>" allowfullscreen loading="lazy"></iframe>
                                    <?php elseif ($video['provider'] === 'vimeo'): ?>
                                        <iframe src="https://player.vimeo.com/video/<?= e($video['video_ref']) ?>"
                                                title="<?= e($video['title']) ?>" allowfullscreen loading="lazy"></iframe>
                                    <?php else: ?>
                                        <video controls src="<?= e(upload_url($video['video_ref'])) ?>"></video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============ INFORMACIÓN ============ -->
            <div class="product-head">
                <div class="product-head__meta">
                    <?php if (!empty($product['brand_name'])): ?>
                        <a href="<?= e(url('maquinaria?marca=' . $product['brand_slug'])) ?>" class="tag tag--dark">
                            <?= e($product['brand_name']) ?>
                        </a>
                    <?php endif; ?>
                    <span class="product-code"><?= e($product['code']) ?></span>
                    <span class="status status--<?= e($availability['class']) ?>"><?= e($availability['label']) ?></span>
                </div>

                <h1><?= e($product['name']) ?></h1>

                <?php if (!empty($tags)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($tags as $tag): ?>
                            <span class="tag tag--<?= e($tag['color']) ?>">
                                <?php if (!empty($tag['icon'])): ?><i class="bi <?= e($tag['icon']) ?>"></i><?php endif; ?>
                                <?= e($tag['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['short_description'])): ?>
                    <p class="text-muted-2"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <!-- Precio -->
                <div class="price-box">
                    <span class="price-box__label">Precio</span>
                    <?php if ($showPrice): ?>
                        <?php if ($hasOffer): ?>
                            <del><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></del>
                        <?php endif; ?>
                        <div class="price-box__value"><?= e(money($price, (string) $product['currency'])) ?></div>
                        <?php if (setting('show_dual_currency', '0') === '1'): ?>
                            <div class="price-box__alt">
                                Equivalente aprox.
                                <?= e(money(
                                    CurrencyService::convert($price, (string) $product['currency'], $product['currency'] === 'ARS' ? 'USD' : 'ARS'),
                                    $product['currency'] === 'ARS' ? 'USD' : 'ARS',
                                    0
                                )) ?>
                            </div>
                        <?php endif; ?>
                        <p class="price-box__note">Precio + IVA. Sujeto a modificación sin previo aviso.</p>
                    <?php else: ?>
                        <div class="price-box__value">Consultar</div>
                        <p class="price-box__note">Escribinos y te pasamos el precio actualizado.</p>
                    <?php endif; ?>
                </div>

                <!-- Acciones -->
                <div class="product-actions">
                    <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                        <i class="bi bi-whatsapp"></i> Consultar por WhatsApp
                    </a>
                    <button type="button" class="btn btn-accent btn-lg" data-quote-add="<?= (int) $product['id'] ?>">
                        <i class="bi bi-file-earmark-plus"></i> Agregar a mi cotización
                    </button>
                    <button type="button" class="btn btn-outline-accent" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                        <i class="bi bi-envelope"></i> Consultar por email
                    </button>
                    <button type="button" class="icon-action" data-fav-toggle="<?= (int) $product['id'] ?>" title="Guardar en favoritos">
                        <i class="bi bi-heart"></i>
                    </button>
                    <button type="button" class="icon-action" data-compare-toggle="<?= (int) $product['id'] ?>" title="Comparar">
                        <i class="bi bi-bar-chart-steps"></i>
                    </button>
                </div>

                <!-- Ficha rápida -->
                <div class="quick-specs">
                    <?php
                    $quick = [
                        ['Modelo',      $product['model'] ?? null],
                        ['Año',         $product['year'] ?? null],
                        ['Capacidad',   !empty($product['capacity_kg']) ? kg_to_human((float) $product['capacity_kg']) : null],
                        ['Altura máx.', !empty($product['lift_height_mm']) ? mm_to_human((int) $product['lift_height_mm']) : null],
                        ['Combustible', !empty($product['fuel']) ? fuel_label((string) $product['fuel']) : null],
                        ['Horas',       !empty($product['hours']) ? number_es((float) $product['hours']) . ' hs' : null],
                        ['Condición',   !empty($product['condition_type']) ? ucfirst((string) $product['condition_type']) : null],
                        ['Ubicación',   $product['location'] ?? null],
                    ];
                    ?>
                    <?php foreach ($quick as [$label, $value]): ?>
                        <?php if ($value !== null && $value !== ''): ?>
                            <div class="quick-spec">
                                <span><?= e($label) ?></span>
                                <strong><?= e((string) $value) ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($product['warranty'])): ?>
                    <p class="d-flex align-items-center gap-2 text-muted-2">
                        <i class="bi bi-shield-check text-accent fs-5"></i>
                        Garantía: <strong class="text-dark"><?= e($product['warranty']) ?></strong>
                    </p>
                <?php endif; ?>

                <!-- Documentación -->
                <?php if (!empty($documents)): ?>
                    <div class="panel mt-3">
                        <div class="panel__head"><h2><i class="bi bi-file-earmark-pdf"></i> Documentación</h2></div>
                        <div class="panel__body">
                            <?php foreach ($documents as $doc): ?>
                                <a class="doc-row" href="<?= e(upload_url($doc['path'])) ?>" target="_blank" rel="noopener">
                                    <span class="doc-row__icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                    <span class="flex-grow-1">
                                        <span class="doc-row__name d-block"><?= e($doc['title']) ?></span>
                                        <span class="doc-row__meta">
                                            <?= e(ucfirst(str_replace('_', ' ', (string) $doc['doc_type']))) ?>
                                            <?php if (!empty($doc['size_bytes'])): ?>
                                                · <?= number_es((int) $doc['size_bytes'] / 1024) ?> KB
                                            <?php endif; ?>
                                        </span>
                                    </span>
                                    <i class="bi bi-download"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ DESCRIPCIÓN Y FICHA TÉCNICA ============ -->
<section class="section section--gray" style="padding-top:0">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <?php if (!empty($product['description'])): ?>
                    <div class="panel">
                        <div class="panel__head"><h2><i class="bi bi-card-text"></i> Descripción</h2></div>
                        <div class="panel__body">
                            <?= clean_html((string) $product['description']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($featureGroups)): ?>
                    <div class="panel">
                        <div class="panel__head"><h2><i class="bi bi-sliders"></i> Características técnicas</h2></div>
                        <div class="panel__body panel__body--flush">
                            <?php foreach ($featureGroups as $groupName => $features): ?>
                                <h3 class="spec-group__title"><?= e($groupName) ?></h3>
                                <table class="spec-table">
                                    <tbody>
                                    <?php foreach ($features as $feature): ?>
                                        <tr>
                                            <th scope="row"><?= e($feature['name']) ?></th>
                                            <td>
                                                <?= e($feature['value_text']) ?>
                                                <?php if (!empty($feature['unit'])): ?>
                                                    <span class="text-muted-2"><?= e($feature['unit']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <!-- Financiación -->
                <?php if (!empty($financingPlans)): ?>
                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="bi bi-calendar-check"></i> Financiación</h2>
                            <a href="<?= url('financiacion') ?>" class="small">Ver todos los planes</a>
                        </div>
                        <div class="panel__body">
                            <div class="finance-grid">
                                <?php foreach (array_slice($financingPlans, 0, 4) as $plan): ?>
                                    <button type="button"
                                            class="finance-card text-start <?= $plan['featured'] ? 'is-featured' : '' ?>"
                                            data-plan='<?= ejs([
                                                'down_payment'     => $plan['down_payment'],
                                                'installments'     => $plan['installments'],
                                                'interest_percent' => $plan['interest_percent'],
                                            ]) ?>'>
                                        <div class="finance-card__name"><?= e($plan['option_name']) ?></div>
                                        <?php if ($plan['installments'] > 1): ?>
                                            <div class="finance-card__value">
                                                <?= (int) $plan['installments'] ?> × <?= e(money($plan['installment_amount'], $plan['currency'])) ?>
                                            </div>
                                            <div class="finance-card__detail">
                                                <span>Anticipo: <?= e(money($plan['down_payment'], $plan['currency'])) ?></span>
                                                <span>Total: <?= e(money($plan['total'], $plan['currency'])) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="finance-card__value"><?= e(money($plan['total'], $plan['currency'])) ?></div>
                                            <div class="finance-card__detail">
                                                <span><?= e($plan['description'] ?: 'Pago total') ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Calculadora -->
                <?php if ($showPrice): ?>
                    <div class="calc" id="financeCalc" data-currency="<?= e($product['currency']) ?>">
                        <h3><i class="bi bi-calculator"></i> Calculá tu cuota</h3>
                        <p class="small text-muted-2 mb-3">Movés los valores y ves el resultado al instante.</p>

                        <input type="hidden" id="calcPrice" value="<?= (float) $price ?>">

                        <div class="mb-3">
                            <label for="calcDown">Anticipo</label>
                            <input type="number" class="form-control" id="calcDown" value="<?= (int) round($price * 0.3) ?>" min="0" step="10000">
                            <input type="range" id="calcDownRange" min="0" max="100" value="30" class="mt-2">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="calcInstallments">Cuotas</label>
                                <select class="form-select" id="calcInstallments">
                                    <?php foreach ([1, 3, 6, 12, 18, 24, 36] as $n): ?>
                                        <option value="<?= $n ?>" <?= $n === 12 ? 'selected' : '' ?>><?= $n ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="calcInterest">Interés total (%)</label>
                                <input type="number" class="form-control" id="calcInterest" value="24" min="0" max="200" step="1">
                            </div>
                        </div>

                        <div class="calc-result">
                            <div class="calc-result__item">
                                <span>Anticipo</span><strong id="outDown">—</strong>
                            </div>
                            <div class="calc-result__item">
                                <span>Saldo</span><strong id="outBalance">—</strong>
                            </div>
                            <div class="calc-result__item">
                                <span>Interés</span><strong id="outInterest">—</strong>
                            </div>
                            <div class="calc-result__item calc-result__item--main">
                                <span><span id="outCount">12</span> cuotas de</span><strong id="outFee">—</strong>
                            </div>
                            <div class="calc-result__item">
                                <span>Total final</span><strong id="outTotal">—</strong>
                            </div>
                        </div>

                        <p class="small text-muted-2 mt-3 mb-0">
                            Los valores son orientativos. La cuota final se confirma en la cotización.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ REPUESTOS COMPATIBLES ============ -->
<?php if (!empty($compatibleParts)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Mantenimiento</span>
                <h2 class="section-title">Repuestos compatibles</h2>
                <p class="section-lead">Todo lo que necesitás para mantener este equipo, con compatibilidad verificada.</p>
            </div>
            <a href="<?= e(url('repuestos?q=' . urlencode((string) ($product['model'] ?? $product['name'])))) ?>" class="btn btn-outline-accent">
                Ver todos <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="product-grid">
            <?php foreach ($compatibleParts as $part): ?>
                <?php $view->partial('product-card', ['product' => $part, 'viewMode' => 'grid']); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ MÁQUINAS SIMILARES ============ -->
<?php if (!empty($similar)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">También te puede servir</span>
                <h2 class="section-title">Máquinas similares</h2>
            </div>
        </div>

        <div class="product-grid">
            <?php foreach ($similar as $item): ?>
                <?php $view->partial('product-card', ['product' => $item, 'viewMode' => 'grid']); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Modal de consulta -->
<?php $view->partial('inquiry-modal', ['product' => $product]); ?>
