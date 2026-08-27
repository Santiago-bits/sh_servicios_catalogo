<?php
/**
 * ARCHIVO: app/views/partials/product-card.php
 * Tarjeta de producto reutilizable (grid y lista).
 *
 * @var array<string,mixed> $product
 * @var string $view   'grid' | 'lista'
 */

use App\Services\PriceService;
use App\Services\StockService;
use App\Services\WhatsAppService;

$isMachine   = ($product['type'] ?? 'machine') === 'machine';
$url         = product_url($product);
$availability= availability_badge((string) ($product['availability'] ?? 'consultar'));
$showPrice   = PriceService::isPublicPriceVisible($product);
$price       = PriceService::effectivePrice($product);
$hasOffer    = (int) ($product['is_offer'] ?? 0) === 1 && (float) ($product['offer_price'] ?? 0) > 0;
$tags        = $product['tags'] ?? [];
$listView    = ($viewMode ?? 'grid') === 'lista';
?>
<article class="pcard reveal" data-product-id="<?= (int) $product['id'] ?>">

    <a class="pcard__media" href="<?= e($url) ?>" aria-label="<?= e($product['name']) ?>">
        <?php if (!empty($product['thumb']) || !empty($product['image'])): ?>
            <img src="<?= e(upload_url($product['thumb'] ?? $product['image'])) ?>"
                 alt="<?= e($product['name']) ?>" loading="lazy" width="400" height="300">
        <?php else: ?>
            <span class="pcard__placeholder">
                <i class="bi <?= $isMachine ? 'bi-truck-front' : 'bi-nut' ?>"></i>
            </span>
        <?php endif; ?>

        <span class="pcard__badges">
            <?php if ((int) ($product['featured'] ?? 0) === 1): ?>
                <span class="tag tag--accent"><i class="bi bi-star-fill"></i> Destacado</span>
            <?php endif; ?>
            <?php if ($hasOffer): ?>
                <span class="tag tag--danger"><i class="bi bi-tag-fill"></i> Oferta</span>
            <?php endif; ?>
            <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                <?php if (!in_array($tag['slug'], ['destacado', 'oferta'], true)): ?>
                    <span class="tag tag--<?= e($tag['color']) ?>"><?= e($tag['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </span>
    </a>

    <div class="pcard__actions">
        <button type="button" class="icon-action" data-fav-toggle="<?= (int) $product['id'] ?>"
                aria-pressed="false" title="Guardar en favoritos">
            <i class="bi bi-heart"></i>
        </button>
        <?php if ($isMachine): ?>
            <button type="button" class="icon-action" data-compare-toggle="<?= (int) $product['id'] ?>"
                    title="Agregar al comparador">
                <i class="bi bi-bar-chart-steps"></i>
            </button>
        <?php endif; ?>
        <button type="button" class="icon-action" data-quote-add="<?= (int) $product['id'] ?>"
                title="Agregar a mi cotización">
            <i class="bi bi-file-earmark-plus"></i>
        </button>
    </div>

    <div class="pcard__body">
        <div class="pcard__meta">
            <?php if (!empty($product['brand_name'])): ?>
                <strong><?= e($product['brand_name']) ?></strong>
            <?php endif; ?>
            <span class="text-mono"><?= e($product['code']) ?></span>
        </div>

        <h3 class="pcard__title"><a href="<?= e($url) ?>"><?= e($product['name']) ?></a></h3>

        <?php if ($listView && !empty($product['short_description'])): ?>
            <p class="text-muted-2 small mb-1"><?= e(str_limit((string) $product['short_description'], 150)) ?></p>
        <?php endif; ?>

        <div class="pcard__specs">
            <?php if ($isMachine): ?>
                <?php if (!empty($product['capacity_kg'])): ?>
                    <span class="spec-pill"><i class="bi bi-box-seam"></i> <?= e(kg_to_human((float) $product['capacity_kg'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['lift_height_mm'])): ?>
                    <span class="spec-pill"><i class="bi bi-arrows-vertical"></i> <?= e(mm_to_human((int) $product['lift_height_mm'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['fuel'])): ?>
                    <span class="spec-pill"><i class="bi bi-fuel-pump"></i> <?= e(fuel_label((string) $product['fuel'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['year'])): ?>
                    <span class="spec-pill"><i class="bi bi-calendar3"></i> <?= (int) $product['year'] ?></span>
                <?php endif; ?>
            <?php else: ?>
                <?php if (!empty($product['oem_code'])): ?>
                    <span class="spec-pill"><i class="bi bi-upc"></i> OEM <?= e($product['oem_code']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['category_name'])): ?>
                    <span class="spec-pill"><i class="bi bi-folder"></i> <?= e($product['category_name']) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="mt-1">
            <?php if (!$isMachine && (int) ($product['track_stock'] ?? 0) === 1): ?>
                <?php $stock = stock_badge($product); ?>
                <span class="status status--<?= e($stock['class']) ?>"><?= e($stock['label']) ?>
                    <?php if ($stock['class'] === 'ok' || $stock['class'] === 'warn'): ?>
                        <small class="text-muted-2">(<?= StockService::available($product) ?>)</small>
                    <?php endif; ?>
                </span>
            <?php else: ?>
                <span class="status status--<?= e($availability['class']) ?>"><?= e($availability['label']) ?></span>
            <?php endif; ?>
        </div>

        <div class="pcard__foot">
            <div class="pcard__price">
                <?php if ($showPrice): ?>
                    <?php if ($hasOffer): ?>
                        <del><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></del>
                    <?php endif; ?>
                    <strong><?= e(money($price, (string) $product['currency'])) ?></strong>
                    <?php if (setting('show_dual_currency', '0') === '1' && ($product['currency'] ?? 'ARS') === 'ARS'): ?>
                        <small><?= e(money(App\Services\CurrencyService::convert($price, 'ARS', 'USD'), 'USD', 0)) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <strong class="fs-6">Consultar precio</strong>
                <?php endif; ?>
            </div>

            <div class="pcard__cta">
                <a class="btn btn-wa btn-sm" href="<?= e(WhatsAppService::productLink($product)) ?>"
                   target="_blank" rel="noopener" title="Consultar por WhatsApp">
                    <i class="bi bi-whatsapp"></i><span class="d-none d-lg-inline"> Consultar</span>
                </a>
                <a class="btn btn-outline-accent btn-sm" href="<?= e($url) ?>">
                    Ver<span class="d-none d-lg-inline"> ficha</span>
                </a>
            </div>
        </div>
    </div>
</article>
