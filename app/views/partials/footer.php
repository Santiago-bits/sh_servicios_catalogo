<?php
/**
 * ARCHIVO: app/views/partials/footer.php
 */

use App\Models\Category;
use App\Services\SettingService;

$companyName = SettingService::companyName();
$logo        = (string) setting('company_logo', '');

// Categorías destacadas para los accesos rápidos del pie
$footerMachines = (new Category())->featuredWithProducts('machine', 6);
$footerParts    = (new Category())->featuredWithProducts('spare_part', 6);
?>
<footer class="site-footer">
    <div class="site-footer__stripe" aria-hidden="true"></div>

    <div class="container">
        <div class="row g-4 site-footer__main">

            <div class="col-lg-4">
                <div class="footer-brand">
                    <?php if ($logo !== ''): ?>
                        <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($companyName) ?>" class="footer-brand__logo">
                    <?php else: ?>
                        <span class="brand__mark" aria-hidden="true"><i class="bi bi-truck-front-fill"></i></span>
                        <div>
                            <strong><?= e($companyName) ?></strong>
                            <span><?= e(setting('company_slogan', '')) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php $footerAbout = trim((string) setting('footer_about', '')) ?: (string) setting('company_description', ''); ?>
                <p class="footer-about"><?= e(str_limit($footerAbout, 260)) ?></p>

                <div class="footer-social">
                    <?php foreach ([
                        'social_instagram' => ['bi-instagram', 'Instagram'],
                        'social_facebook'  => ['bi-facebook', 'Facebook'],
                        'social_linkedin'  => ['bi-linkedin', 'LinkedIn'],
                        'social_youtube'   => ['bi-youtube', 'YouTube'],
                    ] as $key => [$icon, $label]): ?>
                        <?php if (setting($key)): ?>
                            <?php $net = str_replace('social_', '', $key); ?>
                            <a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener"
                               class="footer-social__link footer-social__link--<?= e($net) ?>" aria-label="<?= e($label) ?>">
                                <i class="bi <?= $icon ?>"></i>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h3 class="footer-title">Maquinaria</h3>
                <ul class="footer-list">
                    <?php foreach (array_slice($footerMachines, 0, 6) as $category): ?>
                        <li><a href="<?= e(url('maquinaria/' . $category['slug'])) ?>"><?= e($category['name']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= url('maquinaria') ?>" class="footer-list__all">Ver todo <i class="bi bi-arrow-right"></i></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h3 class="footer-title">Repuestos</h3>
                <ul class="footer-list">
                    <?php foreach (array_slice($footerParts, 0, 6) as $category): ?>
                        <li><a href="<?= e(url('repuestos/' . $category['slug'])) ?>"><?= e($category['name']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= url('repuestos') ?>" class="footer-list__all">Ver todo <i class="bi bi-arrow-right"></i></a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h3 class="footer-title">Contacto</h3>
                <ul class="footer-contact">
                    <?php if (setting('contact_address')): ?>
                        <li>
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= e(setting('contact_address')) ?><?= setting('contact_city') ? ', ' . e(setting('contact_city')) : '' ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (setting('contact_phone')): ?>
                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            <a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (setting('contact_email')): ?>
                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (setting('contact_email_parts')): ?>
                        <li>
                            <i class="bi bi-nut-fill"></i>
                            <a href="mailto:<?= e(setting('contact_email_parts')) ?>"><?= e(setting('contact_email_parts')) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (setting('contact_hours')): ?>
                        <li><i class="bi bi-clock-fill"></i> <span><?= nl2br(e(setting('contact_hours'))) ?></span></li>
                    <?php endif; ?>
                </ul>

                <a href="<?= url('contacto') ?>" class="btn btn-outline-light btn-sm mt-2">
                    <i class="bi bi-send"></i> Escribinos
                </a>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>&copy; <?= date('Y') ?> <?= e($companyName) ?>. Todos los derechos reservados.</p>
            <nav class="site-footer__links" aria-label="Enlaces secundarios">
                <a href="<?= url('servicios') ?>">Servicios</a>
                <a href="<?= url('recomendador') ?>">Asistente</a>
                <a href="<?= url('comparar') ?>">Comparador</a>
            </nav>
            <p class="site-footer__credit">
                Creado por <a href="https://baseocho.com/" target="_blank" rel="noopener">baseocho</a>
            </p>
        </div>
    </div>
</footer>
