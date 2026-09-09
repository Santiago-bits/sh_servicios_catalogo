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

// Videos subidos como archivo → van en la galería como una foto más.
// Los de YouTube/Vimeo → en su panel aparte, abajo.
$fileVideos = array_values(array_filter($videos ?? [], static fn ($v) => ($v['provider'] ?? '') === 'file'));
$linkVideos = array_values(array_filter($videos ?? [], static fn ($v) => ($v['provider'] ?? '') !== 'file'));
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
            <div class="gallery-col">
                <div class="gallery__main" id="galleryMain">
                    <img src="<?= e(upload_url($mainImage)) ?>" alt="<?= e($product['name']) ?>" width="800" height="600">
                    <span class="gallery__zoom-hint"><i class="bi bi-zoom-in"></i> Pasá el mouse para ampliar</span>
                </div>

                <?php if (count($images) > 1 || $fileVideos !== []): ?>
                    <div class="gallery__thumbs">
                        <?php foreach ($images as $i => $image): ?>
                            <div class="gallery__thumb <?= $i === 0 ? 'is-active' : '' ?>"
                                 data-full="<?= e(upload_url($image['path'])) ?>"
                                 title="<?= e($image['zone'] ?: 'Foto ' . ($i + 1)) ?>">
                                <img src="<?= e(upload_url($image['thumb_path'] ?? $image['path'])) ?>"
                                     alt="<?= e($image['alt'] ?: $product['name']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($fileVideos as $video): ?>
                            <div class="gallery__thumb gallery__thumb--video"
                                 data-video-provider="file"
                                 data-video-ref="<?= e(upload_url($video['video_ref'])) ?>"
                                 title="<?= e($video['title'] ?: 'Video') ?>">
                                <span class="gallery__thumb-poster"><i class="bi bi-camera-video-fill"></i></span>
                                <span class="gallery__thumb-play"><i class="bi bi-play-fill"></i></span>
                            </div>
                        <?php endforeach; ?>
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
                            <?php $offPct = (int) round((1 - $price / (float) $product['final_price']) * 100); ?>
                            <div class="price-box__was">
                                <del><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></del>
                                <?php if ($offPct > 0): ?><span class="price-off"><?= $offPct ?>% OFF</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="price-box__value"><?= e(money($price, (string) $product['currency'])) ?></div>
                        <?php if (setting('show_dual_currency', '0') === '1'): ?>
                            <?php
                            $altCurrency = $product['currency'] === 'ARS' ? 'USD' : 'ARS';
                            $altAmount   = money(CurrencyService::convert($price, (string) $product['currency'], $altCurrency), $altCurrency, 0);
                            ?>
                            <div class="price-box__alt">
                                <span class="price-box__alt-label">Equivalente aprox.</span>
                                <strong><?= e($altCurrency === 'ARS' ? 'ARS' . $altAmount : $altAmount) ?></strong>
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
                    <div class="qty-stepper" data-qty>
                        <button type="button" data-qty-minus aria-label="Restar uno">−</button>
                        <input type="text" inputmode="numeric" value="1" data-qty-input aria-label="Cantidad">
                        <button type="button" data-qty-plus aria-label="Sumar uno">+</button>
                    </div>
                    <button type="button" class="btn btn-accent btn-lg" data-quote-add="<?= (int) $product['id'] ?>" data-quote-go>
                        <i class="bi bi-file-earmark-plus"></i> Agregar a mi cotización
                    </button>
                    <button type="button" class="btn btn-outline-accent" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                        <i class="bi bi-envelope"></i> Consultar por email
                    </button>
                    <button type="button" class="icon-action" data-compare-toggle="<?= (int) $product['id'] ?>" title="Comparar">
                        <i class="bi bi-bar-chart-steps"></i>
                    </button>
                </div>

                <!-- Ficha rápida -->
                <?php
                $quick = array_values(array_filter([
                    ['Modelo',       $product['model'] ?? null],
                    ['Año',          $product['year'] ?? null],
                    ['Capacidad',    !empty($product['capacity_kg']) ? kg_to_human((float) $product['capacity_kg']) : null],
                    ['Altura máx.',  !empty($product['lift_height_mm']) ? mm_to_human((int) $product['lift_height_mm']) : null],
                    ['Combustible',  !empty($product['fuel']) ? fuel_label((string) $product['fuel']) : null],
                    ['Motor',        $product['engine'] ?? null],
                    ['Potencia',     !empty($product['power_hp']) ? number_es((float) $product['power_hp']) . ' HP' : null],
                    ['Transmisión',  $product['transmission'] ?? null],
                    ['Horas',        !empty($product['hours']) ? number_es((float) $product['hours']) . ' hs' : null],
                    ['Peso',         !empty($product['weight_kg']) ? kg_to_human((float) $product['weight_kg']) : null],
                    ['Ruedas',       $product['tire_type'] ?? null],
                    ['Uñas',         $product['fork_size'] ?? null],
                    ['Condición',    !empty($product['condition_type']) ? ucfirst((string) $product['condition_type']) : null],
                    ['Garantía',     $product['warranty'] ?? null],
                    ['Ubicación',    $product['location'] ?? null],
                ], static fn ($row) => $row[1] !== null && $row[1] !== ''));
                ?>
                <?php if ($quick !== []): ?>
                    <div class="quick-specs">
                        <?php foreach ($quick as [$label, $value]): ?>
                            <div class="quick-spec">
                                <span><?= e($label) ?></span>
                                <strong><?= e((string) $value) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <?php /* Rellena la última fila para que no quede el hueco gris */ ?>
                        <?php for ($f = (4 - count($quick) % 4) % 4; $f > 0; $f--): ?>
                            <div class="quick-spec quick-spec--filler" aria-hidden="true"></div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <h2 class="product-description__title"><i class="bi bi-card-text"></i> Descripción</h2>
                        <?= clean_html(text_to_html((string) $product['description'])) ?>
                    </div>
                <?php endif; ?>


            </div>

            <!-- ============ VIDEO (YouTube / Vimeo) ============ -->
            <?php if ($linkVideos !== []): ?>
                <div class="product-video">
                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="bi bi-play-btn-fill"></i> Ver la máquina trabajando</h2>
                        </div>
                        <div class="panel__body">
                            <?php foreach (array_slice($linkVideos, 0, 2) as $video): ?>
                                <div class="video-embed mb-3">
                                    <?php if ($video['provider'] === 'vimeo'): ?>
                                        <iframe src="https://player.vimeo.com/video/<?= e($video['video_ref']) ?>"
                                                title="<?= e($video['title']) ?>" allowfullscreen loading="lazy"></iframe>
                                    <?php else: ?>
                                        <iframe src="https://www.youtube-nocookie.com/embed/<?= e($video['video_ref']) ?>"
                                                title="<?= e($video['title']) ?>" allowfullscreen loading="lazy"></iframe>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ============ DOCUMENTACIÓN ============ -->
            <?php if (!empty($documents)): ?>
                <div class="product-docs">
                    <div class="panel">
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
                </div>
            <?php endif; ?>
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
