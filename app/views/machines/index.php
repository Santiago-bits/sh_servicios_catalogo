<?php
/**
 * ARCHIVO: app/views/machines/index.php
 * Catálogo público de maquinaria.
 *
 * @var \Core\View $view
 * @var array $result
 * @var array $filters
 * @var array|null $currentCategory
 */

$activeFilters = array_filter($filters, static fn ($v, $k) => !in_array($k, ['orden'], true) && $v !== null && $v !== '' && $v !== [], ARRAY_FILTER_USE_BOTH);
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="<?= url() ?>">Inicio</a>
            <span><a href="<?= url('maquinaria') ?>">Maquinaria</a></span>
            <?php if ($currentCategory): ?>
                <span><?= e($currentCategory['name']) ?></span>
            <?php endif; ?>
        </nav>

        <h1><?= e($currentCategory['name'] ?? 'Maquinaria') ?></h1>
        <p>
            <?= e($currentCategory['description'] ?? 'Autoelevadores, apiladores, zorras eléctricas, plataformas y maquinaria industrial. Equipos nuevos y usados, revisados y con garantía.') ?>
        </p>
    </div>
</section>

<section class="section" style="padding-top:32px">
    <div class="container">
        <div class="catalog-layout">

            <!-- ================= FILTROS ================= -->
            <aside class="filters" id="filtersPanel">
                <div class="filters__head">
                    <h2><i class="bi bi-funnel-fill"></i> Filtros</h2>
                    <div class="d-flex gap-2">
                        <a href="<?= url('maquinaria' . ($currentCategory ? '/' . $currentCategory['slug'] : '')) ?>"
                           class="btn btn-ghost btn-sm">Limpiar</a>
                        <button type="button" class="btn btn-ghost btn-sm d-lg-none" id="filtersClose">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <form id="catalogFilters" method="get" action="<?= url('maquinaria' . ($currentCategory ? '/' . $currentCategory['slug'] : '')) ?>">
                    <?php if (!empty($filters['orden'])): ?>
                        <input type="hidden" name="orden" value="<?= e($filters['orden']) ?>">
                    <?php endif; ?>

                    <!-- Búsqueda -->
                    <div class="filter-group">
                        <div class="filter-group__body pt-3">
                            <div class="position-relative">
                                <input type="search" name="q" class="form-control" placeholder="Nombre, código o modelo"
                                       value="<?= e($filters['q'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Categoría -->
                    <?php if (!$currentCategory): ?>
                        <div class="filter-group">
                            <button type="button" class="filter-group__head" aria-expanded="true">
                                Categoría <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="filter-group__body">
                                <?php foreach ($categories as $category): ?>
                                    <label class="filter-check">
                                        <input type="radio" name="categoria" value="<?= e($category['slug']) ?>"
                                            <?= ($filters['categoria'] ?? '') === $category['slug'] ? 'checked' : '' ?>>
                                        <?= e($category['name']) ?>
                                        <span class="count"><?= (int) $category['products_count'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Marca -->
                    <?php if (!empty($brands)): ?>
                        <div class="filter-group">
                            <button type="button" class="filter-group__head" aria-expanded="true">
                                Marca <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="filter-group__body">
                                <?php
                                $selectedBrands = (array) ($filters['marca'] ?? []);
                                $selectedBrands = array_map('strval', is_array($selectedBrands) ? $selectedBrands : [$selectedBrands]);
                                ?>
                                <?php foreach ($brands as $brand): ?>
                                    <label class="filter-check">
                                        <input type="checkbox" name="marca[]" value="<?= e($brand['slug']) ?>"
                                            <?= in_array($brand['slug'], $selectedBrands, true) ? 'checked' : '' ?>>
                                        <?= e($brand['name']) ?>
                                        <span class="count"><?= (int) $brand['total'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Precio -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Precio <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <div class="filter-range">
                                <input type="number" name="precio_min" class="form-control form-control-sm"
                                       placeholder="Desde" min="0" value="<?= e($filters['precio_min'] ?? '') ?>">
                                <span>—</span>
                                <input type="number" name="precio_max" class="form-control form-control-sm"
                                       placeholder="Hasta" min="0" value="<?= e($filters['precio_max'] ?? '') ?>">
                            </div>
                            <?php if (($priceRange['max'] ?? 0) > 0): ?>
                                <p class="form-hint mt-2 mb-0">
                                    Rango del catálogo: <?= e(money($priceRange['min'])) ?> — <?= e(money($priceRange['max'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Capacidad -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Capacidad de carga (kg) <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <div class="filter-range">
                                <input type="number" name="capacidad_min" class="form-control form-control-sm"
                                       placeholder="Desde" min="0" step="100" value="<?= e($filters['capacidad_min'] ?? '') ?>">
                                <span>—</span>
                                <input type="number" name="capacidad_max" class="form-control form-control-sm"
                                       placeholder="Hasta" min="0" step="100" value="<?= e($filters['capacidad_max'] ?? '') ?>">
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <?php foreach ([[0,1500],[1500,2500],[2500,3500],[3500,0]] as [$min, $max]): ?>
                                    <a class="tag" href="<?= e(query_url(['capacidad_min' => $min ?: null, 'capacidad_max' => $max ?: null, 'pagina' => null])) ?>">
                                        <?= $max ? ($min ? $min . '–' . $max : 'Hasta ' . $max) : 'Más de ' . $min ?> kg
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Altura -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="false">
                            Altura de elevación (mm) <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body" style="display:none">
                            <div class="filter-range">
                                <input type="number" name="altura_min" class="form-control form-control-sm"
                                       placeholder="Desde" min="0" step="100" value="<?= e($filters['altura_min'] ?? '') ?>">
                                <span>—</span>
                                <input type="number" name="altura_max" class="form-control form-control-sm"
                                       placeholder="Hasta" min="0" step="100" value="<?= e($filters['altura_max'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Combustible -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Combustible <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <?php
                            $selectedFuels = (array) ($filters['combustible'] ?? []);
                            $selectedFuels = is_array($selectedFuels) ? $selectedFuels : [$selectedFuels];
                            ?>
                            <?php foreach (['electrico', 'diesel', 'gas', 'glp', 'nafta', 'manual'] as $fuel): ?>
                                <label class="filter-check">
                                    <input type="checkbox" name="combustible[]" value="<?= $fuel ?>"
                                        <?= in_array($fuel, $selectedFuels, true) ? 'checked' : '' ?>>
                                    <?= e(fuel_label($fuel)) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Año -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="false">
                            Año <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body" style="display:none">
                            <div class="filter-range">
                                <input type="number" name="anio_min" class="form-control form-control-sm"
                                       placeholder="<?= (int) ($yearRange['min'] ?: 2000) ?>" value="<?= e($filters['anio_min'] ?? '') ?>">
                                <span>—</span>
                                <input type="number" name="anio_max" class="form-control form-control-sm"
                                       placeholder="<?= (int) ($yearRange['max'] ?: date('Y')) ?>" value="<?= e($filters['anio_max'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Estado y condición -->
                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Estado <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <?php foreach (['disponible' => 'Disponible', 'reservada' => 'Reservada', 'mantenimiento' => 'En mantenimiento', 'consultar' => 'Consultar'] as $value => $label): ?>
                                <label class="filter-check">
                                    <input type="radio" name="estado" value="<?= $value ?>"
                                        <?= ($filters['estado'] ?? '') === $value ? 'checked' : '' ?>>
                                    <?= e($label) ?>
                                </label>
                            <?php endforeach; ?>

                            <hr class="my-2">

                            <?php foreach (['nuevo' => 'Nuevo', 'usado' => 'Usado', 'reacondicionado' => 'Reacondicionado'] as $value => $label): ?>
                                <label class="filter-check">
                                    <input type="radio" name="condicion" value="<?= $value ?>"
                                        <?= ($filters['condicion'] ?? '') === $value ? 'checked' : '' ?>>
                                    <?= e($label) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <?php if (!empty($locations)): ?>
                        <div class="filter-group">
                            <button type="button" class="filter-group__head" aria-expanded="false">
                                Ubicación <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="filter-group__body" style="display:none">
                                <?php foreach ($locations as $location): ?>
                                    <label class="filter-check">
                                        <input type="radio" name="ubicacion" value="<?= e($location) ?>"
                                            <?= ($filters['ubicacion'] ?? '') === $location ? 'checked' : '' ?>>
                                        <?= e($location) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="filter-group">
                        <div class="filter-group__body pt-3">
                            <button type="submit" class="btn btn-accent w-100">
                                <i class="bi bi-funnel-fill"></i> Aplicar filtros
                            </button>
                        </div>
                    </div>
                </form>
            </aside>

            <!-- ================= RESULTADOS ================= -->
            <div>
                <div class="catalog-toolbar">
                    <button type="button" class="btn btn-outline-accent btn-sm filters-mobile-btn" id="filtersOpen">
                        <i class="bi bi-funnel-fill"></i> Filtros
                    </button>

                    <span class="catalog-toolbar__count">
                        <strong><?= number_es($result['total']) ?></strong> equipo(s)
                        <?php if ($result['total'] > 0): ?>
                            · mostrando <?= (int) $result['from'] ?>–<?= (int) $result['to'] ?>
                        <?php endif; ?>
                    </span>

                    <div class="catalog-toolbar__right">
                        <form method="get" class="d-flex align-items-center gap-2">
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if ($key !== 'orden' && $key !== 'pagina' && !is_array($value)): ?>
                                    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <label class="form-label mb-0 d-none d-sm-block">Ordenar</label>
                            <select name="orden" class="form-select form-select-sm" data-autosubmit>
                                <?php foreach ($sorts as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= ($filters['orden'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <div class="view-switch">
                            <a href="<?= e(query_url(['vista' => 'grid'])) ?>" class="<?= $viewMode === 'grid' ? 'is-active' : '' ?>" title="Vista de tarjetas">
                                <i class="bi bi-grid-fill"></i>
                            </a>
                            <a href="<?= e(query_url(['vista' => 'lista'])) ?>" class="<?= $viewMode === 'lista' ? 'is-active' : '' ?>" title="Vista de lista">
                                <i class="bi bi-list-ul"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($activeFilters !== []): ?>
                    <div class="active-filters">
                        <?php foreach ($activeFilters as $key => $value): ?>
                            <?php foreach ((array) $value as $single): ?>
                                <?php if ($single === '' || $single === null) { continue; } ?>
                                <span class="active-filter">
                                    <?= e(ucfirst(str_replace('_', ' ', $key))) ?>: <?= e(is_scalar($single) ? (string) $single : '') ?>
                                    <a href="<?= e(query_url([$key => null, 'pagina' => null])) ?>" aria-label="Quitar filtro">&times;</a>
                                </span>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="catalogResults">
                    <?php $view->partial('product-grid', ['products' => $products, 'viewMode' => $viewMode]); ?>
                </div>

                <?php $view->partial('pagination', ['result' => $result]); ?>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>¿No encontrás el equipo que buscás?</h2>
                <p>Conseguimos máquinas a pedido. Contanos qué necesitás y te lo buscamos.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('recomendador') ?>" class="btn btn-dark-2"><i class="bi bi-magic"></i> Usar el asistente</a>
                <a href="<?= url('contacto') ?>" class="btn btn-outline-accent"><i class="bi bi-send"></i> Escribinos</a>
            </div>
        </div>
    </div>
</section>
