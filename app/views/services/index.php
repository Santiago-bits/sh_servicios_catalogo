<?php
/**
 * ARCHIVO: app/views/services/index.php
 */

use App\Services\WhatsAppService;
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Servicios</span></nav>
        <h1>Servicios</h1>
        <p>El respaldo que necesita tu equipo en cada etapa.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-3 justify-content-center">
            <?php foreach ($services as $service): ?>
                <div class="col-sm-6 col-lg-4">
                    <a class="service-tile reveal" href="<?= e(url('servicios/' . $service['slug'])) ?>">
                        <span class="service-tile__icon"><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i></span>
                        <h2 class="service-tile__title"><?= e($service['title']) ?></h2>
                        <span class="btn btn-outline-accent btn-sm">Ver detalle</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿Necesitás un servicio o mantenimiento?</h2>
                <p>Coordinamos una visita técnica y te pasamos un presupuesto sin cargo.</p>
            </div>
            <a href="<?= url('contacto') ?>" class="btn btn-dark-2 btn-lg"><i class="bi bi-calendar-check"></i> Coordinar visita</a>
        </div>
    </div>
</section>
