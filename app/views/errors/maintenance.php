<?php
/**
 * ARCHIVO: app/views/errors/maintenance.php
 * Se muestra cuando el modo mantenimiento está activo.
 */

use App\Services\WhatsAppService;
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <div class="empty-state" style="border-style:solid;border-color:#E8E8E8;background:#fff">
            <div class="brand__mark mx-auto mb-3" style="width:70px;height:70px;font-size:2rem">
                <i class="bi bi-cone-striped"></i>
            </div>
            <h1 class="h3 mb-2">Estamos en mantenimiento</h1>
            <p>
                Estamos actualizando el sitio de <strong><?= e(setting('company_name', 'la empresa')) ?></strong>.
                Volvemos en un rato. Mientras tanto podés escribirnos.
            </p>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <?php if (WhatsAppService::isConfigured()): ?>
                    <a href="<?= e(WhatsAppService::generalLink()) ?>" target="_blank" rel="noopener" class="btn btn-wa">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                <?php endif; ?>
                <?php if (setting('contact_email')): ?>
                    <a href="mailto:<?= e(setting('contact_email')) ?>" class="btn btn-outline-accent">
                        <i class="bi bi-envelope"></i> <?= e(setting('contact_email')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
