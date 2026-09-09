<?php
/**
 * ARCHIVO: app/views/partials/product-card.php
 * Tarjeta de producto reutilizable (grid y lista).
 *
 * @var array<string,mixed> $product
 * @var string $view   'grid' | 'lista'
 */

use App\Services\PriceService;
use App\Services\WhatsAppService;

$isMachine   = ($product['type'] ?? 'machine') === 'machine';
$url         = product_url($product);
$availability= availability_badge((string) ($product['availability'] ?? 'consultar'));
$showPrice   = PriceService::isPublicPriceVisible($product);
$price       = PriceService::effectivePrice($product);
$hasOffer    = (int) ($product['is_offer'] ?? 0) === 1 && (float) ($product['offer_price'] ?? 0) > 0;
$tags        = $product['tags'] ?? [];
$listView    = ($viewMode ?? 'grid') === 'lista';

// Etiquetas de la tarjeta (se muestran sobre la imagen en escritorio y,
// en teléfono, dentro del cuerpo en lugar de las características).
$cardTags = [];
if ((int) ($product['featured'] ?? 0) === 1) {
    $cardTags[] = ['color' => 'accent', 'icon' => 'bi-star-fill', 'label' => 'Destacado'];
}
if ($hasOffer) {
    $cardTags[] = ['color' => 'danger', 'icon' => 'bi-tag-fill', 'label' => 'Oferta'];
}
foreach (array_slice($tags, 0, 2) as $tag) {
    if (!in_array($tag['slug'], ['destacado', 'oferta'], true)) {
        $cardTags[] = ['color' => $tag['color'], 'icon' => null, 'label' => $tag['name']];
    }
}
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

        <?php if ($cardTags !== []): ?>
            <span class="pcard__badges"><?php $view->partial('tag-list', ['tags' => $cardTags]); ?></span>
        <?php endif; ?>
    </a>

    <div class="pcard__actions">
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

        <?php if ($cardTags !== []): ?>
            <div class="pcard__tags"><?php $view->partial('tag-list', ['tags' => $cardTags]); ?></div>
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
            <span class="status status--<?= e($availability['class']) ?>"><?= e($availability['label']) ?></span>
        </div>

        <div class="pcard__foot">
            <div class="pcard__price">
                <?php if ($showPrice): ?>
                    <?php if ($hasOffer): ?>
                        <?php $offPct = (int) round((1 - $price / (float) $product['final_price']) * 100); ?>
                        <span class="pcard__was">
                            <del><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></del>
                            <?php if ($offPct > 0): ?><span class="price-off"><?= $offPct ?>% OFF</span><?php endif; ?>
                        </span>
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
