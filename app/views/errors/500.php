<?php
/**
 * ARCHIVO: app/views/errors/500.php
 */
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <div class="empty-state" style="border-style:solid;border-color:#E8E8E8;background:#fff">
            <div class="brand__mark mx-auto mb-3" style="width:70px;height:70px;font-size:2rem">
                <i class="bi bi-tools"></i>
            </div>
            <h1 style="font-size:3.4rem;margin:0">500</h1>
            <h2 class="h5 mb-2">Algo se rompió de nuestro lado</h2>
            <p>Ya quedó registrado el error. Probá de nuevo en unos minutos o escribinos si es urgente.</p>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= url() ?>" class="btn btn-accent"><i class="bi bi-house"></i> Volver al inicio</a>
                <a href="<?= url('contacto') ?>" class="btn btn-outline-accent">Contactarnos</a>
            </div>
        </div>
    </div>
</section>
