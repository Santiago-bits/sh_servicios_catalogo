<?php
/**
 * ARCHIVO: app/views/parts/show.php
 * Ficha completa de un repuesto.
 *
 * @var \Core\View $view
 * @var array<string,mixed> $product
 */

use App\Services\CurrencyService;
use App\Services\PriceService;

$availability = availability_badge((string) $product['availability']);
$showPrice    = PriceService::isPublicPriceVisible($product);
$price        = PriceService::effectivePrice($product);
$hasOffer     = (int) $product['is_offer'] === 1 && (float) ($product['offer_price'] ?? 0) > 0;
$mainImage    = $images[0]['path'] ?? $product['image'] ?? null;

$codeTypes = [
    'interno'     => 'Código interno',
    'oem'         => 'Código OEM',
    'fabricante'  => 'Fabricante',
    'alternativo' => 'Alternativo',
    'cruzado'     => 'Equivalencia',
];
?>

<section class="page-hero" style="padding:26px 0 30px">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="<?= url() ?>">Inicio</a>
            <span><a href="<?= url('repuestos') ?>">Repuestos</a></span>
            <?php if (!empty($product['category_slug'])): ?>
                <span><a href="<?= e(url('repuestos/' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a></span>
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
                    <img src="<?= e(upload_url($mainImage, 'img/placeholder-part.svg')) ?>"
                         alt="<?= e($product['name']) ?>" width="800" height="600">
                    <span class="gallery__zoom-hint"><i class="bi bi-zoom-in"></i> Pasá el mouse para ampliar</span>
                </div>

                <?php if (count($images) > 1 || !empty($videos)): ?>
                    <div class="gallery__thumbs">
                        <?php foreach ($images as $i => $image): ?>
                            <div class="gallery__thumb <?= $i === 0 ? 'is-active' : '' ?>" data-full="<?= e(upload_url($image['path'])) ?>">
                                <img src="<?= e(upload_url($image['thumb_path'] ?? $image['path'])) ?>"
                                     alt="<?= e($image['alt'] ?: $product['name']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($videos as $video): ?>
                            <div class="gallery__thumb gallery__thumb--video"
                                 data-video-provider="<?= e($video['provider']) ?>"
                                 data-video-ref="<?= e($video['provider'] === 'file' ? upload_url($video['video_ref']) : $video['video_ref']) ?>"
                                 title="<?= e($video['title'] ?: 'Video') ?>">
                                <?php if ($video['provider'] === 'youtube'): ?>
                                    <img src="https://i.ytimg.com/vi/<?= e($video['video_ref']) ?>/hqdefault.jpg"
                                         alt="Video" loading="lazy">
                                <?php else: ?>
                                    <span class="gallery__thumb-poster"><i class="bi bi-camera-video-fill"></i></span>
                                <?php endif; ?>
                                <span class="gallery__thumb-play"><i class="bi bi-play-fill"></i></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Códigos -->
                <?php if (!empty($codes) || !empty($product['oem_code']) || !empty($product['manufacturer_code'])): ?>
                    <div class="panel mt-4">
                        <div class="panel__head">
                            <h2><i class="bi bi-upc-scan"></i> Códigos y equivalencias</h2>
                        </div>
                        <div class="panel__body">
                            <div class="code-list">
                                <div class="code-row">
                                    <span class="code-row__type">Interno</span>
                                    <span class="code-row__value"><?= e($product['code']) ?></span>
                                    <button type="button" class="code-copy" data-copy="<?= e($product['code']) ?>" title="Copiar">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>

                                <?php if (!empty($product['oem_code'])): ?>
                                    <div class="code-row">
                                        <span class="code-row__type">OEM</span>
                                        <span class="code-row__value"><?= e($product['oem_code']) ?></span>
                                        <button type="button" class="code-copy" data-copy="<?= e($product['oem_code']) ?>" title="Copiar">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($product['manufacturer_code'])): ?>
                                    <div class="code-row">
                                        <span class="code-row__type">Fabricante</span>
                                        <span class="code-row__value"><?= e($product['manufacturer_code']) ?></span>
                                        <button type="button" class="code-copy" data-copy="<?= e($product['manufacturer_code']) ?>" title="Copiar">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($codes as $code): ?>
                                    <?php if (in_array($code['code_type'], ['interno', 'oem', 'fabricante'], true)
                                              && in_array($code['code'], [$product['code'], $product['oem_code'], $product['manufacturer_code']], true)) { continue; } ?>
                                    <div class="code-row">
                                        <span class="code-row__type"><?= e($codeTypes[$code['code_type']] ?? $code['code_type']) ?></span>
                                        <span class="code-row__value"><?= e($code['code']) ?></span>
                                        <?php if (!empty($code['note'])): ?>
                                            <small class="text-muted-2"><?= e($code['note']) ?></small>
                                        <?php endif; ?>
                                        <button type="button" class="code-copy" data-copy="<?= e($code['code']) ?>" title="Copiar">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============ INFORMACIÓN ============ -->
            <div class="product-head">
                <div class="product-head__meta">
                    <?php if (!empty($product['brand_name'])): ?>
                        <a href="<?= e(url('repuestos?marca=' . urlencode((string) $product['brand_name']))) ?>" class="tag tag--dark"><?= e($product['brand_name']) ?></a>
                    <?php endif; ?>
                    <span class="product-code"><?= e($product['code']) ?></span>
                    <span class="status status--<?= e($availability['class']) ?>"><?= e($availability['label']) ?></span>
                </div>

                <h1><?= e($product['name']) ?></h1>

                <?php if (!empty($tags)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($tags as $tag): ?>
                            <span class="tag tag--<?= e($tag['color']) ?>"><?= e($tag['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['short_description'])): ?>
                    <p class="text-muted-2"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

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
                        <p class="price-box__note">
                            Precio + IVA por <?= e($product['unit'] ?? 'unidad') ?>. Sujeto a modificación sin previo aviso.
                        </p>
                    <?php else: ?>
                        <div class="price-box__value">Consultar</div>
                        <p class="price-box__note">Escribinos y te pasamos precio y disponibilidad.</p>
                    <?php endif; ?>
                </div>

                <div class="product-actions">
                    <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                        <i class="bi bi-whatsapp"></i> Consultar disponibilidad
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
                </div>

                <!-- Datos rápidos -->
                <div class="quick-specs">
                    <?php
                    $quick = [
                        ['Categoría',    $product['category_name'] ?? null],
                        ['Marca',        $product['brand_name'] ?? null],
                        ['Origen',       !empty($product['origin']) ? ucfirst((string) $product['origin']) : null],
                        ['Unidad',       $product['unit'] ?? null],
                        ['Peso',         !empty($product['weight_kg']) ? number_es((float) $product['weight_kg'], 2) . ' kg' : null],
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

                <!-- Compatibilidad -->
                <?php if (!empty($compatibility) || !empty($compatibleMachines)): ?>
                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="bi bi-diagram-3-fill"></i> Compatible con</h2>
                        </div>
                        <div class="panel__body">
                            <?php if (!empty($compatibility)): ?>
                                <div class="compat-list mb-3">
                                    <?php foreach ($compatibility as $compat): ?>
                                        <a class="compat-item"
                                           href="<?= e(url('repuestos?q=' . urlencode((string) $compat['model']))) ?>">
                                            <i class="bi bi-truck-front-fill"></i>
                                            <?= e(trim(($compat['brand_name'] ?? '') . ' ' . $compat['model'])) ?>
                                            <?php if (!empty($compat['year_from'])): ?>
                                                <small>(<?= (int) $compat['year_from'] ?><?= !empty($compat['year_to']) ? '–' . (int) $compat['year_to'] : '' ?>)</small>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <p class="small text-muted-2 mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    Verificá siempre el código original de tu equipo antes de comprar. Ante la duda, escribinos.
                                </p>
                            <?php else: ?>
                                <p class="text-muted-2 mb-0">Consultanos la compatibilidad con tu equipo.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Documentación -->
                <?php if (!empty($documents)): ?>
                    <div class="panel">
                        <div class="panel__head"><h2><i class="bi bi-file-earmark-pdf"></i> Documentación</h2></div>
                        <div class="panel__body">
                            <?php foreach ($documents as $doc): ?>
                                <a class="doc-row" href="<?= e(upload_url($doc['path'])) ?>" target="_blank" rel="noopener">
                                    <span class="doc-row__icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                    <span class="flex-grow-1">
                                        <span class="doc-row__name d-block"><?= e($doc['title']) ?></span>
                                        <span class="doc-row__meta"><?= e(ucfirst(str_replace('_', ' ', (string) $doc['doc_type']))) ?></span>
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

<!-- ============ DESCRIPCIÓN ============ -->
<?php if (!empty($product['description'])): ?>
<section class="section section--gray" style="padding-top:0">
    <div class="container">
        <div class="panel">
            <div class="panel__head"><h2><i class="bi bi-card-text"></i> Descripción</h2></div>
            <div class="panel__body"><?= clean_html((string) $product['description']) ?></div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ MÁQUINAS COMPATIBLES DEL CATÁLOGO ============ -->
<?php if (!empty($compatibleMachines)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">En nuestro catálogo</span>
                <h2 class="section-title">Máquinas que usan este repuesto</h2>
            </div>
        </div>
        <div class="product-grid">
            <?php foreach ($compatibleMachines as $machine): ?>
                <?php $view->partial('product-card', ['product' => $machine, 'viewMode' => 'grid']); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ REPUESTOS RELACIONADOS ============ -->
<?php if (!empty($similar)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">También te puede servir</span>
                <h2 class="section-title">Repuestos relacionados</h2>
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

<?php $view->partial('inquiry-modal', ['product' => $product]); ?>
