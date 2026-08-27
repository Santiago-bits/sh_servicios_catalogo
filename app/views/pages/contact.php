<?php
/**
 * ARCHIVO: app/views/pages/contact.php
 */

use App\Services\WhatsAppService;
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Contacto</span></nav>
        <h1>Contacto</h1>
        <p>Escribinos por el canal que te resulte más cómodo. Respondemos dentro del horario de atención.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (isset($_GET['enviado'])): ?>
            <div class="alert d-flex align-items-center gap-3 mb-4"
                 style="background:#E4F5EA;border:1px solid #B6E2C5;border-radius:10px">
                <i class="bi bi-check-circle-fill fs-3" style="color:#1E7A45"></i>
                <div>
                    <strong>¡Gracias! Recibimos tu consulta.</strong>
                    <div class="small text-muted-2">Te vamos a responder a la brevedad.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="contact-card">
                    <h2 class="h5 mb-1">Envianos tu consulta</h2>
                    <p class="text-muted-2 small mb-4">Los campos con * son obligatorios.</p>
                    <?php $view->include('partials/inquiry-form', ['productId' => null, 'compact' => false]); ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="contact-card mb-4">
                    <h2 class="h5 mb-3">Datos de contacto</h2>

                    <?php if (setting('contact_whatsapp')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon" style="background:#25D366;color:#fff"><i class="bi bi-whatsapp"></i></span>
                            <div>
                                <strong>WhatsApp</strong>
                                <a href="<?= e(WhatsAppService::generalLink()) ?>" target="_blank" rel="noopener">
                                    +<?= e(setting('contact_whatsapp')) ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (setting('contact_phone')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-telephone-fill"></i></span>
                            <div>
                                <strong>Teléfono</strong>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (setting('contact_email')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-envelope-fill"></i></span>
                            <div>
                                <strong>Email ventas</strong>
                                <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (setting('contact_email_parts')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-nut-fill"></i></span>
                            <div>
                                <strong>Email repuestos</strong>
                                <a href="mailto:<?= e(setting('contact_email_parts')) ?>"><?= e(setting('contact_email_parts')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (setting('contact_address')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-geo-alt-fill"></i></span>
                            <div>
                                <strong>Dirección</strong>
                                <span><?= e(setting('contact_address')) ?><?= setting('contact_city') ? ', ' . e(setting('contact_city')) : '' ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (setting('contact_hours')): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-clock-fill"></i></span>
                            <div>
                                <strong>Horarios</strong>
                                <span><?= nl2br(e(setting('contact_hours'))) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $socials = array_filter([
                        'social_instagram' => ['bi-instagram', 'Instagram'],
                        'social_facebook'  => ['bi-facebook', 'Facebook'],
                        'social_linkedin'  => ['bi-linkedin', 'LinkedIn'],
                        'social_youtube'   => ['bi-youtube', 'YouTube'],
                    ], static fn ($v, $k) => setting($k) !== '', ARRAY_FILTER_USE_BOTH);
                    ?>
                    <?php if ($socials !== []): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-item__icon"><i class="bi bi-share-fill"></i></span>
                            <div>
                                <strong>Redes</strong>
                                <div class="footer-social mt-1" style="filter:invert(0)">
                                    <?php foreach ($socials as $key => [$icon, $label]): ?>
                                        <a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener"
                                           aria-label="<?= e($label) ?>" style="background:#111"><i class="bi <?= $icon ?>"></i></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (WhatsAppService::isConfigured()): ?>
                    <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg w-100">
                        <i class="bi bi-whatsapp"></i> Escribir por WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (setting('contact_map_embed')): ?>
            <div class="map-frame mt-4">
                <iframe src="<?= e(setting('contact_map_embed')) ?>" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" title="Ubicación"></iframe>
            </div>
        <?php endif; ?>
    </div>
</section>
