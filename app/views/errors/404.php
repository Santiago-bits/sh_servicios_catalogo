<?php
/**
 * ARCHIVO: app/views/errors/404.php
 */
?>
<section class="section">
    <div class="container" style="max-width:640px">
        <div class="empty-state" style="border-style:solid;border-color:#E8E8E8;background:#fff">
            <div class="brand__mark mx-auto mb-3" style="width:70px;height:70px;font-size:2rem">
                <i class="bi bi-cone-striped"></i>
            </div>
            <h1 style="font-size:3.4rem;margin:0">404</h1>
            <h2 class="h5 mb-2">Página no encontrada</h2>
            <p><?= e($message ?? 'La página que buscás no existe o fue movida.') ?></p>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="<?= url() ?>" class="btn btn-accent"><i class="bi bi-house"></i> Volver al inicio</a>
                <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent">Ver maquinaria</a>
                <a href="<?= url('repuestos') ?>" class="btn btn-outline-accent">Ver repuestos</a>
            </div>
        </div>
    </div>
</section>
