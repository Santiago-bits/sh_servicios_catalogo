<?php
/**
 * ARCHIVO: app/views/services/show.php
 */

use App\Models\Service as ServiceModel;
use App\Services\WhatsAppService;

$bullets = ServiceModel::bullets($service['bullets']);
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs">
            <a href="<?= url() ?>">Inicio</a>
            <span><a href="<?= url('servicios') ?>">Servicios</a></span>
            <span><?= e($service['title']) ?></span>
        </nav>
        <h1><?= e($service['title']) ?></h1>
        <p><?= e($service['short_description']) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (!empty($service['image'])): ?>
                    <img src="<?= e(upload_url($service['image'])) ?>" alt="<?= e($service['title']) ?>"
                         class="w-100 mb-4" style="border-radius:10px">
                <?php endif; ?>

                <div class="panel">
                    <div class="panel__head">
                        <h2><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i> <?= e($service['title']) ?></h2>
                    </div>
                    <div class="panel__body">
                        <?= clean_html((string) $service['description']) ?: '<p>' . e($service['short_description']) . '</p>' ?>

                        <?php if ($bullets !== []): ?>
                            <div class="row g-2 mt-3">
                                <?php foreach ($bullets as $bullet): ?>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-2 align-items-start">
                                            <i class="bi bi-check-circle-fill text-accent"></i>
                                            <span><?= e($bullet) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel__head"><h2><i class="bi bi-envelope"></i> Consultar por este servicio</h2></div>
                    <div class="panel__body">
                        <?php $view->include('partials/inquiry-form', ['productId' => null, 'compact' => true]); ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="contact-card mb-3">
                    <h3 class="h6 text-uppercase">Contacto directo</h3>
                    <p class="text-muted-2 small">Respondemos dentro del horario de atención.</p>

                    <a href="<?= e(WhatsAppService::link('Hola, quisiera consultar por el servicio de ' . $service['title'] . '.')) ?>"
                       target="_blank" rel="noopener" class="btn btn-wa w-100 mb-2">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <?php if (setting('contact_phone')): ?>
                        <a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('contact_phone'))) ?>" class="btn btn-outline-accent w-100 mb-2">
                            <i class="bi bi-telephone"></i> <?= e(setting('contact_phone')) ?>
                        </a>
                    <?php endif; ?>
                    <?php if (setting('contact_email')): ?>
                        <a href="mailto:<?= e(setting('contact_email')) ?>" class="btn btn-ghost w-100">
                            <i class="bi bi-envelope"></i> Escribir un email
                        </a>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel__head"><h3>Otros servicios</h3></div>
                    <div class="panel__body panel__body--flush">
                        <?php foreach (array_slice($others, 0, 8) as $other): ?>
                            <a class="doc-row m-0 border-0 border-bottom rounded-0" href="<?= e(url('servicios/' . $other['slug'])) ?>">
                                <span class="doc-row__icon" style="background:#111;color:#F5C400">
                                    <i class="bi <?= e($other['icon'] ?: 'bi-tools') ?>"></i>
                                </span>
                                <span class="flex-grow-1">
                                    <span class="doc-row__name d-block"><?= e($other['title']) ?></span>
                                    <span class="doc-row__meta"><?= e(str_limit((string) $other['short_description'], 52)) ?></span>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
