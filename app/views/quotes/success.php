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

            <?php
            $canales = [];
            if (!empty($quote['customer_email'])) {
                $canales[] = 'por correo a <strong>' . e($quote['customer_email']) . '</strong>';
            }
            if (!empty($quote['customer_phone'])) {
                $canales[] = 'por WhatsApp al <strong>' . e($quote['customer_phone']) . '</strong>';
            }
            ?>
            <h1 class="mb-2">¡Recibimos tu solicitud!</h1>
            <p class="text-muted-2 mb-4">
                Un asesor la va a revisar y te va a contactar
                <?= $canales !== [] ? implode(' o ', $canales) . ' ' : '' ?>a la brevedad.
            </p>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                    <i class="bi bi-whatsapp"></i> Avisar por WhatsApp
                </a>
                <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent btn-lg">
                    Seguir viendo el catálogo
                </a>
            </div>
        </div>
    </div>
</section>
