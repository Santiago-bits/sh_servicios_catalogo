<?php
/**
 * ARCHIVO: app/views/services/index.php
 */

use App\Models\Service as ServiceModel;
use App\Services\WhatsAppService;
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Servicios</span></nav>
        <h1>Servicios</h1>
        <p>Acompañamos todo el ciclo de vida del equipo: venta, alquiler, puesta en marcha, mantenimiento, reparación y repuestos.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-3">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="service-card reveal h-100">
                        <span class="service-card__icon"><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i></span>
                        <h2 class="h5"><?= e($service['title']) ?></h2>
                        <p><?= e($service['short_description']) ?></p>

                        <?php $bullets = ServiceModel::bullets($service['bullets']); ?>
                        <?php if ($bullets !== []): ?>
                            <ul class="service-card__list">
                                <?php foreach ($bullets as $bullet): ?>
                                    <li><?= e($bullet) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= e(url('servicios/' . $service['slug'])) ?>" class="btn btn-outline-accent btn-sm">
                                Ver detalle
                            </a>
                            <a href="<?= e(WhatsAppService::link('Hola, quisiera consultar por el servicio de ' . $service['title'] . '.')) ?>"
                               target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                                <i class="bi bi-whatsapp"></i> Consultar
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿Necesitás un service o mantenimiento?</h2>
                <p>Coordinamos una visita técnica y te pasamos un presupuesto sin cargo.</p>
            </div>
            <a href="<?= url('contacto') ?>" class="btn btn-dark-2 btn-lg"><i class="bi bi-calendar-check"></i> Coordinar visita</a>
        </div>
    </div>
</section>
