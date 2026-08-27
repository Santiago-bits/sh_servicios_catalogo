<?php
/**
 * ARCHIVO: app/views/pages/about.php
 */

use App\Models\Service as ServiceModel;
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs"><a href="<?= url() ?>">Inicio</a><span>Nosotros</span></nav>
        <h1><?= e(setting('company_name', 'Nosotros')) ?></h1>
        <p><?= e(setting('company_slogan', '')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="eyebrow">Quiénes somos</span>
                <h2 class="section-title">Movimiento de cargas, resuelto</h2>
                <div class="divider-accent"></div>

                <p class="lead"><?= nl2br(e(setting('company_description', ''))) ?></p>

                <p class="text-muted-2">
                    Trabajamos con empresas de logística, industria, agro y construcción. Vendemos y alquilamos
                    equipos, los mantenemos con taller propio y sostenemos un stock de repuestos con compatibilidad
                    cargada modelo por modelo, para que una máquina parada vuelva a operar lo antes posible.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="<?= url('maquinaria') ?>" class="btn btn-accent"><i class="bi bi-truck-front-fill"></i> Ver maquinaria</a>
                    <a href="<?= url('contacto') ?>" class="btn btn-outline-accent"><i class="bi bi-send"></i> Contactarnos</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero__visual-frame" style="background:linear-gradient(160deg,#F3F3F3,#E2E2E2);border-color:#DDD">
                    <img src="<?= asset('img/hero-forklift.svg') ?>" alt="Equipos de movimiento de cargas">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--dark">
    <div class="container">
        <div class="hero__stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
            <div class="hero-stat">
                <strong><?= number_es($counts['maquinas'] ?? 0) ?></strong>
                <span>Máquinas publicadas</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_es($counts['repuestos'] ?? 0) ?></strong>
                <span>Repuestos en catálogo</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_es($counts['marcas'] ?? 0) ?></strong>
                <span>Marcas que atendemos</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_es($counts['servicios'] ?? 0) ?></strong>
                <span>Servicios</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Cómo trabajamos</span>
                <h2 class="section-title">De la consulta a la máquina operando</h2>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ([
                ['01', 'Entendemos tu operación', 'Cargas, alturas, pasillos, turnos y tipo de piso. Sin eso no se recomienda un equipo.'],
                ['02', 'Proponemos opciones', 'Te mostramos alternativas reales del catálogo, con precio y financiación.'],
                ['03', 'Preparamos el equipo', 'Revisión completa antes de la entrega: motor, hidráulica, frenos y mástil.'],
                ['04', 'Entregamos y capacitamos', 'Puesta en marcha en tu planta y capacitación de los operadores.'],
                ['05', 'Mantenemos', 'Plan de mantenimiento preventivo con repuestos en stock.'],
                ['06', 'Respondemos', 'Service en taller o a domicilio cuando algo falla.'],
            ] as [$number, $title, $text]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="cat-card reveal h-100">
                        <span class="cat-card__icon" style="font-weight:800;font-size:1.1rem"><?= $number ?></span>
                        <h3 class="cat-card__title"><?= e($title) ?></h3>
                        <p class="text-muted-2 small mb-0"><?= e($text) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Lo que ofrecemos</span>
                <h2 class="section-title">Servicios</h2>
            </div>
            <a href="<?= url('servicios') ?>" class="btn btn-outline-accent">Ver detalle <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row g-3">
            <?php foreach (array_slice($services, 0, 6) as $service): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="service-card h-100">
                        <span class="service-card__icon"><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i></span>
                        <h3><?= e($service['title']) ?></h3>
                        <p><?= e($service['short_description']) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($brands)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Marcas</span>
                <h2 class="section-title">Trabajamos con</h2>
            </div>
        </div>
        <div class="brand-strip">
            <?php foreach ($brands as $brand): ?>
                <span class="brand-cell">
                    <?php if (!empty($brand['logo'])): ?>
                        <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span><?= e($brand['name']) ?></span>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Contanos qué necesitás</h2>
                <p>Te asesoramos sin cargo para que elijas el equipo correcto.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('recomendador') ?>" class="btn btn-dark-2"><i class="bi bi-magic"></i> Usar el asistente</a>
                <a href="<?= url('contacto') ?>" class="btn btn-outline-accent"><i class="bi bi-send"></i> Escribinos</a>
            </div>
        </div>
    </div>
</section>
