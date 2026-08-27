<?php
/**
 * ARCHIVO: app/views/quotes/success.php
 */
?>

<section class="section">
    <div class="container" style="max-width:760px">
        <div class="contact-card text-center" style="padding:48px 32px">
            <div class="brand__mark mx-auto mb-4" style="width:76px;height:76px;font-size:2.2rem">
                <i class="bi bi-check-lg"></i>
            </div>

            <h1 class="mb-2">¡Recibimos tu solicitud!</h1>
            <p class="text-muted-2 mb-4">
                Guardamos tu pedido con el número
                <strong class="product-code"><?= e($quote['number']) ?></strong>.
                Un asesor lo va a revisar y te va a enviar la cotización formal
                <?php if (!empty($quote['customer_email'])): ?>
                    a <strong><?= e($quote['customer_email']) ?></strong>.
                <?php else: ?>
                    a la brevedad.
                <?php endif; ?>
            </p>

            <div class="quick-specs mb-4">
                <div class="quick-spec">
                    <span>Número</span>
                    <strong><?= e($quote['number']) ?></strong>
                </div>
                <div class="quick-spec">
                    <span>Fecha</span>
                    <strong><?= e(date_es((string) $quote['created_at'])) ?></strong>
                </div>
                <div class="quick-spec">
                    <span>Validez estimada</span>
                    <strong><?= e(date_es((string) $quote['valid_until'])) ?></strong>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                    <i class="bi bi-whatsapp"></i> Avisar por WhatsApp
                </a>
                <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent btn-lg">
                    Seguir viendo el catálogo
                </a>
            </div>

            <p class="form-hint mt-4 mb-0">
                Anotá el número <strong><?= e($quote['number']) ?></strong> para mencionarlo si nos escribís.
            </p>
        </div>
    </div>
</section>
