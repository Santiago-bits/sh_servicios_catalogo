<?php
/**
 * ARCHIVO: app/views/admin/wip.php
 * Cartel "en desarrollo" para secciones todavia no habilitadas.
 *
 * @var string $wipTitle
 * @var string $wipText
 */
?>
<div class="card-admin">
    <div class="card-admin__body text-center" style="padding:56px 28px">
        <div class="brand__mark mx-auto mb-4" style="width:72px;height:72px;font-size:2rem;background:var(--yellow);color:#111;border-radius:12px;display:grid;place-items:center">
            <i class="bi bi-tools"></i>
        </div>
        <h2 class="mb-2"><?= e($wipTitle ?? 'En desarrollo') ?></h2>
        <p class="text-muted-2 mb-0" style="max-width:460px;margin:0 auto">
            <?= e($wipText ?? 'Estamos trabajando en esta seccion. Va a estar disponible proximamente.') ?>
        </p>
    </div>
</div>
