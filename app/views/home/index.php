<?php
/**
 * ARCHIVO: app/views/home/index.php
 * Página de inicio.
 *
 * @var \Core\View $view
 * @var array<int,array<string,mixed>> $machineCategories
 * @var array<int,array<string,mixed>> $partCategories
 * @var array<int,array<string,mixed>> $featuredMachines
 * @var array<int,array<string,mixed>> $featuredParts
 * @var array<int,array<string,mixed>> $services
 * @var array<int,array{key:string,label:string,items:array}> $brandGroups
 */

use App\Models\Service as ServiceModel;
use App\Services\WhatsAppService;

$totalMachines = array_sum(array_column($machineCategories, 'products_count'));
$totalParts    = array_sum(array_column($partCategories, 'products_count'));

// El texto del hero se edita desde Configuración -> Empresa.
$heroTitle = trim((string) setting('company_slogan', ''));
$heroLead  = trim((string) setting('company_description', ''));

// La imagen del hero (Configuración -> Empresa -> Imagen del inicio) ahora
// es el fondo completo de la sección, no un recuadro al costado.
$heroImage = trim((string) setting('hero_image', ''));
$heroBg    = $heroImage !== '' ? upload_url($heroImage) : asset('img/hero-forklift.svg');
?>

<!-- =================================================================
     HERO
     ================================================================= -->
<section class="hero" style="background-image: linear-gradient(90deg, rgba(8,8,8,.92) 0%, rgba(8,8,8,.68) 42%, rgba(8,8,8,.32) 100%), url('<?= e($heroBg) ?>')">
    <div class="container hero__inner">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero__badge"><i class="bi bi-shield-check"></i> Ventas · Alquiler · Servicios · Repuestos</span>

                <h1 class="hero__title">
                    <?php if ($heroTitle !== ''): ?>
                        <?= e($heroTitle) ?>
                    <?php else: ?>
                        Soluciones para la <em>industria</em> y el movimiento de cargas
                    <?php endif; ?>
                </h1>

                <p class="hero__lead">
                    <?php if ($heroLead !== ''): ?>
                        <?= nl2br(e($heroLead)) ?>
                    <?php else: ?>
                        Autoelevadores, maquinaria, repuestos y soluciones para tu empresa.
                        Equipos revisados, stock permanente de repuestos y taller propio.
                    <?php endif; ?>
                </p>


            </div>
        </div>
    </div>
</section>

<!-- Banda de confianza -->
<section class="trustbar">
    <div class="container-fluid px-0">
        <div class="trustbar__inner">
            <div class="trustbar__item">
                <i class="bi bi-tools"></i>
                <div><strong>Taller propio</strong><span>Servicio y reparación integral</span></div>
            </div>
            <div class="trustbar__item">
                <i class="bi bi-box-seam-fill"></i>
                <div><strong>Stock de repuestos</strong><span>Búsqueda por código OEM</span></div>
            </div>
            <div class="trustbar__item">
                <i class="bi bi-truck"></i>
                <div><strong>Logística propia</strong><span>Entregas en todo el país</span></div>
            </div>
        </div>
    </div>
</section>

<!-- =================================================================
     MÁQUINAS DESTACADAS
     ================================================================= -->
<?php if (!empty($featuredMachines)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Selección</span>
                <h2 class="section-title">Máquinas destacadas</h2>
                <p class="section-lead">Equipos listos para entregar, con servicio inicial.</p>
            </div>
            <a href="<?= url('maquinaria?orden=destacados') ?>" class="btn btn-outline-accent">
                Ver más <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="product-grid">
            <?php foreach ($featuredMachines as $product): ?>
                <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =================================================================
     CTA: NO ENCONTRÁS EL EQUIPO
     ================================================================= -->
<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿No encontrás el equipo que buscás?</h2>
                <p>Conseguimos máquinas a pedido. Contanos qué necesitás y te lo buscamos.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('contacto') ?>" class="btn btn-outline-accent"><i class="bi bi-send"></i> Escribinos</a>
            </div>
        </div>
    </div>
</section>

<!-- =================================================================
     CTA: BATERÍAS DE LITIO
     ================================================================= -->
<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Cambiá tu batería de ácido plomo por litio</h2>
                <p>Invertí en tecnología. Mayor disponibilidad, mínimo mantenimiento y una batería diseñada para acompañar tu operación durante más tiempo.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e(WhatsAppService::link('Hola, quiero mi batería de litio.')) ?>" target="_blank" rel="noopener" class="btn btn-outline-accent">
                    <i class="bi bi-whatsapp"></i> Quiero mi batería de litio
                </a>
            </div>
        </div>
    </div>
</section>

<!-- =================================================================
     REPUESTOS DESTACADOS
     ================================================================= -->
<?php if (!empty($featuredParts)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Más pedidos</span>
                <h2 class="section-title">Repuestos destacados</h2>
                <p class="section-lead">Los que más nos consultan, con compatibilidad verificada por modelo.</p>
            </div>
            <a href="<?= url('repuestos?orden=destacados') ?>" class="btn btn-outline-accent">
                Ver todos <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="product-grid">
            <?php foreach ($featuredParts as $product): ?>
                <?php $view->partial('product-card', ['product' => $product, 'viewMode' => 'grid']); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =================================================================
     SERVICIOS
     ================================================================= -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Lo que hacemos</span>
                <h2 class="section-title">Servicios</h2>
                <p class="section-lead">Acompañamos todo el ciclo del equipo: venta, puesta en marcha, mantenimiento y repuestos.</p>
            </div>
            <a href="<?= url('servicios') ?>" class="btn btn-outline-accent">
                Todos los servicios <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="service-card reveal">
                        <span class="service-card__icon"><i class="bi <?= e($service['icon'] ?: 'bi-tools') ?>"></i></span>
                        <h3><?= e($service['title']) ?></h3>
                        <p><?= e($service['short_description']) ?></p>

                        <?php $bullets = ServiceModel::bullets($service['bullets']); ?>
                        <?php if ($bullets !== []): ?>
                            <ul class="service-card__list">
                                <?php foreach (array_slice($bullets, 0, 3) as $bullet): ?>
                                    <li><?= e($bullet) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <a href="<?= e(url('servicios/' . $service['slug'])) ?>" class="btn btn-outline-accent btn-sm">
                            Más información <i class="bi bi-arrow-right"></i>
                        </a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- =================================================================
     CATEGORÍAS DE MAQUINARIA
     ================================================================= -->
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Catálogo</span>
                <h2 class="section-title">Maquinaria por categoría</h2>
                <p class="section-lead">
                    Equipos para depósito, planta, obra y logística. Todos revisados y con respaldo técnico.
                </p>
            </div>
            <a href="<?= url('maquinaria') ?>" class="btn btn-outline-accent">
                Ver todo el catálogo <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="cat-grid">
            <?php foreach ($machineCategories as $category): ?>
                <a class="cat-card reveal" href="<?= e(url('maquinaria/' . $category['slug'])) ?>">
                    <span class="cat-card__icon<?= !empty($category['image']) ? ' cat-card__icon--img' : '' ?>">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?= e(upload_url($category['image'])) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <i class="bi <?= e($category['icon'] ?: 'bi-gear-fill') ?>"></i>
                        <?php endif; ?>
                    </span>
                    <h3 class="cat-card__title"><?= e($category['name']) ?></h3>
                    <span class="cat-card__count"><?= (int) $category['products_count'] ?> equipo(s)</span>
                    <span class="cat-card__arrow">Ver equipos <i class="bi bi-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- =================================================================
     BUSCADOR DE REPUESTOS (versión simple: sólo el buscador)
     ================================================================= -->
<section class="section section--dark section--search">
    <div class="container">
        <div class="search-simple">
            <span class="eyebrow">Repuestos</span>
            <h2 class="section-title section-title--light">Buscá tu repuesto</h2>

            <form action="<?= url('repuestos') ?>" method="get" class="mt-4">
                <div class="input-group">
                    <input type="search" name="q" class="form-control form-control-lg"
                           placeholder="Código, código OEM o modelo de tu máquina"
                           aria-label="Buscar repuesto">
                    <button class="btn btn-accent btn-lg" type="submit">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- =================================================================
     MARCAS (se editan en Panel → Marcas)
     ================================================================= -->
<?php if (!empty($brandGroups)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Marcas</span>
                <h2 class="section-title">Con las que trabajamos</h2>
            </div>
        </div>

        <?php foreach ($brandGroups as $group): ?>
            <h3 class="brand-group-title"><?= e($group['label']) ?></h3>
            <div class="brand-strip mb-4">
                <?php foreach ($group['items'] as $brand): ?>
                    <div class="brand-cell" title="<?= e($brand['name']) ?>">
                        <?php if (!empty($brand['logo'])): ?>
                            <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span><?= e($brand['name']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- =================================================================
     BENEFICIOS
     ================================================================= -->
<section class="section section--dark">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Por qué elegirnos</span>
                <h2 class="section-title section-title--light">Experiencia, servicio y soluciones para tu operación</h2>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ([
                ['bi-clock-history', 'Menos tiempo parado', 'Repuestos en stock y talleres móviles.'],
                ['bi-clipboard-check', 'Equipos verificados', 'Cada máquina pasa por una revisión integral.'],
                ['bi-people-fill', 'Asesoramiento previo', 'Te asesoramos para que elijas lo que más se adapte a tu necesidad.'],
                ['bi-shield-lock-fill', 'Respaldo posventa', 'Garantía, servicio programado y operadores capacitados.'],
            ] as [$icon, $title, $text]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit reveal">
                        <span class="benefit__icon"><i class="bi <?= $icon ?>"></i></span>
                        <div>
                            <h3><?= e($title) ?></h3>
                            <p><?= e($text) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- =================================================================
     CONSULTA + CONTACTO
     ================================================================= -->
<section class="section section--contact">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <span class="eyebrow">Hablemos</span>
                <h2 class="section-title">Contanos qué necesitás</h2>
                <p class="section-lead mb-4">
                    Completá el formulario y te respondemos con una propuesta concreta.
                    Si preferís, escribinos directo por WhatsApp.
                </p>

                <?php $view->include('partials/inquiry-form', ['productId' => null, 'compact' => false]); ?>
            </div>

            <div class="col-lg-5">
                <?php $view->include('partials/contact-details', ['headingTag' => 'h3']); ?>
            </div>
        </div>
    </div>
</section>

<!-- =================================================================
     CTA FINAL
     ================================================================= -->
<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿Necesitás una cotización?</h2>
                <p>Armá tu pedido con máquinas, repuestos y servicios, y recibila en PDF.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('cotizador') ?>" class="btn btn-dark-2 btn-lg">
                    <i class="bi bi-file-earmark-text"></i> Solicitar cotización
                </a>
                <?php if (WhatsAppService::isConfigured()): ?>
                    <a href="<?= e(WhatsAppService::generalLink()) ?>" target="_blank" rel="noopener" class="btn btn-outline-accent btn-lg">
                        <i class="bi bi-whatsapp"></i> Hablar ahora
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
