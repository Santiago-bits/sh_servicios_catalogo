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
 * @var array<int,array<string,mixed>> $brands
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
                <span class="hero__badge"><i class="bi bi-shield-check"></i> Venta · Alquiler · Service · Repuestos</span>

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

                <div class="hero__actions">
                    <a href="<?= url('maquinaria') ?>" class="btn btn-accent btn-lg" title="Ver maquinaria">
                        <i class="bi bi-truck-front-fill"></i><span class="btn__label">Ver maquinaria</span>
                    </a>
                    <a href="<?= url('repuestos') ?>" class="btn btn-outline-light-2 btn-lg" title="Buscar repuestos">
                        <i class="bi bi-search"></i><span class="btn__label">Buscar repuestos</span>
                    </a>
                    <a href="<?= url('cotizador') ?>" class="btn btn-outline-light-2 btn-lg" title="Ver cotización">
                        <i class="bi bi-file-earmark-text"></i><span class="btn__label">Ver cotización</span>
                    </a>
                </div>

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
                <div><strong>Taller propio</strong><span>Service y reparación integral</span></div>
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
     CATEGORÍAS DE MAQUINARIA
     ================================================================= -->
<section class="section">
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
     MÁQUINAS DESTACADAS
     ================================================================= -->
<?php if (!empty($featuredMachines)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Selección</span>
                <h2 class="section-title">Máquinas destacadas</h2>
                <p class="section-lead">Equipos listos para entregar, con garantía escrita y service inicial.</p>
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
     BUSCADOR DE REPUESTOS
     ================================================================= -->
<section class="section section--dark section--search">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="eyebrow">Repuestos</span>
                <h2 class="section-title section-title--light">Buscá tu repuesto por código o por modelo</h2>
                <p class="section-lead">
                    Escribí el código interno, el código OEM o directamente el modelo de tu máquina
                    (por ejemplo <strong class="text-accent">8FG25</strong>) y te mostramos todo lo compatible.
                </p>

                <form action="<?= url('repuestos') ?>" method="get" class="mt-4">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control form-control-lg"
                               placeholder="Ej: 8FG25, FIL-00125, 15601-U2100-71"
                               aria-label="Buscar repuesto">
                        <button class="btn btn-accent btn-lg" type="submit">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="text-muted-2 small me-1">Búsquedas frecuentes:</span>
                    <?php foreach (['Filtro de aceite', 'Bomba hidráulica', 'Pastillas de freno', '8FG25'] as $term): ?>
                        <a href="<?= e(url('repuestos?q=' . urlencode($term))) ?>" class="tag tag--dark"><?= e($term) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($partCategories as $category): ?>
                        <a class="cat-chip reveal" href="<?= e(url('repuestos/' . $category['slug'])) ?>">
                            <i class="bi <?= e($category['icon'] ?: 'bi-nut-fill') ?>"></i>
                            <?= e($category['name']) ?>
                            <small>(<?= (int) $category['products_count'] ?>)</small>
                        </a>
                    <?php endforeach; ?>
                    <a class="cat-chip" href="<?= url('repuestos') ?>">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Ver todas
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =================================================================
     REPUESTOS DESTACADOS
     ================================================================= -->
<?php if (!empty($featuredParts)): ?>
<section class="section">
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
<section class="section section--gray">
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
     MARCAS
     ================================================================= -->
<?php if (!empty($brands)): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Marcas</span>
                <h2 class="section-title">Con las que trabajamos</h2>
            </div>
        </div>

        <div class="brand-strip">
            <?php foreach ($brands as $brand): ?>
                <a class="brand-cell" href="<?= e(url('maquinaria?marca=' . $brand['slug'])) ?>" title="<?= e($brand['name']) ?>">
                    <?php if (!empty($brand['logo'])): ?>
                        <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span><?= e($brand['name']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
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
                <h2 class="section-title section-title--light">Trabajamos como trabaja tu planta</h2>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ([
                ['bi-clock-history', 'Menos tiempo parado', 'Repuestos en stock y unidades móviles para atender en tu planta.'],
                ['bi-clipboard-check', 'Equipos verificados', 'Cada máquina pasa por revisión de motor, hidráulica, frenos y mástil.'],
                ['bi-diagram-3-fill', 'Compatibilidad garantizada', 'Cargamos la compatibilidad de cada repuesto por marca y modelo.'],
                ['bi-people-fill', 'Asesoramiento previo', 'Analizamos cargas, alturas y pasillos antes de recomendarte un equipo.'],
                ['bi-shield-lock-fill', 'Respaldo posventa', 'Garantía escrita, service programado y capacitación de operadores.'],
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
                <?php
                $iconDark = 'width:44px;height:44px;flex-shrink:0;display:grid;place-items:center;border-radius:10px;background:#1b1b1b;color:#F5C400';
                $iconWa   = 'width:44px;height:44px;flex-shrink:0;display:grid;place-items:center;border-radius:10px;background:#25D366;color:#fff';
                $grpLabel = 'font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#9a9a9a;margin:16px 0 2px';
                ?>
                <div class="contact-card h-100">
                    <h3 class="h5 mb-1">Datos de contacto</h3>

                    <!-- VENTAS -->
                    <p style="<?= $grpLabel ?>;margin-top:8px">Ventas</p>
                    <?php if (setting('contact_whatsapp')): ?>
                        <div class="contact-info-item">
                            <span style="<?= $iconWa ?>"><?= bs_icon('whatsapp') ?></span>
                            <div>
                                <strong>Teléfono de ventas</strong>
                                <a href="<?= e(WhatsAppService::generalLink()) ?>" target="_blank" rel="noopener">+<?= e(setting('contact_whatsapp')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (setting('contact_email')): ?>
                        <div class="contact-info-item">
                            <span style="<?= $iconDark ?>"><?= bs_icon('envelope-fill') ?></span>
                            <div>
                                <strong>Email de ventas</strong>
                                <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- GENERAL -->
                    <?php if (setting('contact_address') || setting('contact_hours')): ?>
                        <p style="<?= $grpLabel ?>">Dónde y cuándo</p>
                        <?php if (setting('contact_address')): ?>
                            <div class="contact-info-item">
                                <span style="<?= $iconDark ?>"><?= bs_icon('geo-alt-fill') ?></span>
                                <div>
                                    <strong>Dirección</strong>
                                    <span><?= e(setting('contact_address')) ?><?= setting('contact_city') ? ', ' . e(setting('contact_city')) : '' ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (setting('contact_hours')): ?>
                            <div class="contact-info-item">
                                <span style="<?= $iconDark ?>"><?= bs_icon('clock-fill') ?></span>
                                <div>
                                    <strong>Horarios</strong>
                                    <span><?= nl2br(e(setting('contact_hours'))) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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
