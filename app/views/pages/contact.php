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

<section class="section contact-page">
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
                <div class="mb-4">
                    <?php $view->include('partials/contact-details', ['headingTag' => 'h2']); ?>
                </div>

                <?php if (WhatsAppService::isConfigured()): ?>
                    <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg w-100">
                        <?= bs_icon('whatsapp') ?> Escribir por WhatsApp
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
