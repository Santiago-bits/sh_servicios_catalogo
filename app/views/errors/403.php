<?php
/**
 * ARCHIVO: app/views/errors/403.php
 */
?>
<div class="card-admin" style="max-width:520px;margin:0 auto">
    <div class="card-admin__body text-center" style="padding:40px 28px">
        <div class="brand__mark mx-auto mb-3" style="width:66px;height:66px;font-size:1.9rem">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1 style="font-size:2.6rem;margin:0">403</h1>
        <h2 class="h6 mb-2">Acceso denegado</h2>
        <p class="text-muted-2"><?= e($message ?? 'No tenés permiso para acceder a esta sección.') ?></p>
        <p class="form-hint">Si creés que es un error, pedile al administrador que revise los permisos de tu rol.</p>

        <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
            <a href="<?= admin_url() ?>" class="btn btn-accent"><i class="bi bi-speedometer2"></i> Ir al dashboard</a>
            <a href="<?= url() ?>" class="btn btn-ghost">Ver el sitio</a>
        </div>
    </div>
</div>
