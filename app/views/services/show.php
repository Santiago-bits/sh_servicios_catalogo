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
                <div class="panel">
                    <div class="panel__head">
                        <h2><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i> <?= e($service['title']) ?></h2>
                    </div>
                    <div class="panel__body service-body">
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

                <?php
                // Galería: la imagen principal (si hay) + las fotos cargadas.
                $gallery = [];
                if (!empty($service['image'])) {
                    $gallery[] = ['path' => $service['image'], 'thumb' => $service['image']];
                }
                foreach ($images as $img) {
                    $gallery[] = ['path' => $img['path'], 'thumb' => $img['thumb'] ?: $img['path']];
                }
                ?>
                <?php if ($gallery !== []): ?>
                    <div class="panel">
                        <div class="panel__head"><h2><i class="bi bi-images"></i> Galería</h2></div>
                        <div class="panel__body">
                            <div class="service-gallery" data-gallery>
                                <?php foreach ($gallery as $i => $img): ?>
                                    <a href="<?= e(upload_url($img['path'])) ?>" class="service-gallery__item" data-gallery-item="<?= $i ?>">
                                        <img src="<?= e(upload_url($img['thumb'])) ?>" alt="<?= e($service['title']) ?> · foto <?= $i + 1 ?>" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="gallery-lightbox" id="serviceLightbox" hidden>
                        <button type="button" class="gallery-lightbox__close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__nav--prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                        <img src="" alt="">
                        <button type="button" class="gallery-lightbox__nav gallery-lightbox__nav--next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <script>
                    (function () {
                        var items = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-item]'));
                        var box = document.getElementById('serviceLightbox');
                        if (!items.length || !box) return;
                        var img = box.querySelector('img'), idx = 0;
                        function show(i) { idx = (i + items.length) % items.length; img.src = items[idx].href; box.hidden = false; document.body.style.overflow = 'hidden'; }
                        function hide() { box.hidden = true; img.src = ''; document.body.style.overflow = ''; }
                        items.forEach(function (a, i) { a.addEventListener('click', function (ev) { ev.preventDefault(); show(i); }); });
                        box.querySelector('.gallery-lightbox__close').addEventListener('click', hide);
                        box.querySelector('.gallery-lightbox__nav--prev').addEventListener('click', function () { show(idx - 1); });
                        box.querySelector('.gallery-lightbox__nav--next').addEventListener('click', function () { show(idx + 1); });
                        box.addEventListener('click', function (ev) { if (ev.target === box) hide(); });
                        document.addEventListener('keydown', function (ev) {
                            if (box.hidden) return;
                            if (ev.key === 'Escape') hide();
                            if (ev.key === 'ArrowLeft') show(idx - 1);
                            if (ev.key === 'ArrowRight') show(idx + 1);
                        });
                    })();
                    </script>
                <?php endif; ?>

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
                    <p class="text-muted-2 small">Atención 24 hs.</p>

                    <a href="<?= e(WhatsAppService::link('Hola, quisiera consultar por el servicio de ' . $service['title'] . '.')) ?>"
                       target="_blank" rel="noopener" class="btn btn-wa w-100 mb-2">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <?php if (setting('contact_whatsapp_parts') && setting('contact_whatsapp_parts') !== setting('contact_whatsapp')): ?>
                        <a href="<?= e(WhatsAppService::link('Hola, quisiera consultar por el servicio de ' . $service['title'] . '.', preg_replace('/\D+/', '', (string) setting('contact_whatsapp_parts')))) ?>"
                           target="_blank" rel="noopener" class="btn btn-outline-accent w-100 mb-2">
                            <i class="bi bi-whatsapp"></i> +<?= e(preg_replace('/\D+/', '', (string) setting('contact_whatsapp_parts'))) ?>
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
