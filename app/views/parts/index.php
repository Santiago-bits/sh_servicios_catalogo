<?php
/**
 * ARCHIVO: app/views/parts/index.php
 * Catálogo público de repuestos, con búsqueda por código y por modelo.
 *
 * @var \Core\View $view
 */
?>

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="<?= url() ?>">Inicio</a>
            <span><a href="<?= url('repuestos') ?>">Repuestos</a></span>
            <?php if ($currentCategory): ?>
                <span><?= e($currentCategory['name']) ?></span>
            <?php endif; ?>
        </nav>

        <h1><?= e($currentCategory['name'] ?? 'Repuestos') ?></h1>
        <p><?= e($currentCategory['description'] ?? 'Buscá por código interno, código OEM, código de fabricante o directamente por el modelo de tu máquina.') ?></p>
    </div>
</section>

<?php if (!empty($matchedModel)): ?>
    <div class="container mt-4">
        <div class="alert d-flex align-items-center gap-3" style="background:#FFFDF3;border:1px solid #F0E4B0;border-radius:10px">
            <i class="bi bi-diagram-3-fill fs-3 text-accent"></i>
            <div>
                <strong>Repuestos compatibles con <?= e($matchedModel) ?></strong>
                <div class="small text-muted-2">
                    Detectamos que tu búsqueda coincide con un modelo de máquina.
                    Además de los resultados por nombre, abajo listamos lo compatible.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<section class="section" style="padding-top:28px">
    <div class="container">
        <div class="catalog-layout">

            <!-- ================= FILTROS ================= -->
            <aside class="filters" id="filtersPanel">
                <div class="filters__head">
                    <h2><i class="bi bi-funnel-fill"></i> Filtros</h2>
                    <div class="d-flex gap-2">
                        <a href="<?= url('repuestos' . ($currentCategory ? '/' . $currentCategory['slug'] : '')) ?>" class="btn btn-ghost btn-sm">Limpiar</a>
                        <button type="button" class="btn btn-ghost btn-sm d-lg-none" id="filtersClose"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>

                <form id="catalogFilters" method="get" action="<?= url('repuestos' . ($currentCategory ? '/' . $currentCategory['slug'] : '')) ?>">
                    <?php if (!empty($filters['orden'])): ?>
                        <input type="hidden" name="orden" value="<?= e($filters['orden']) ?>">
                    <?php endif; ?>

                    <div class="filter-group">
                        <div class="filter-group__body pt-3">
                            <input type="search" name="q" class="form-control" placeholder="Código, OEM o nombre"
                                   value="<?= e($filters['q'] ?? '') ?>">
                        </div>
                    </div>

                    <?php if (!$currentCategory): ?>
                        <div class="filter-group">
                            <button type="button" class="filter-group__head" aria-expanded="true">
                                Categoría <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="filter-group__body" style="max-height:280px;overflow-y:auto">
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

                    <?php if (!empty($brands)): ?>
                        <div class="filter-group">
                            <button type="button" class="filter-group__head" aria-expanded="true">
                                Marca <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="filter-group__body" style="max-height:240px;overflow-y:auto">
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

                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Precio <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <div class="filter-range">
                                <input type="number" name="precio_min" class="form-control form-control-sm" placeholder="Desde"
                                       value="<?= e($filters['precio_min'] ?? '') ?>">
                                <span>—</span>
                                <input type="number" name="precio_max" class="form-control form-control-sm" placeholder="Hasta"
                                       value="<?= e($filters['precio_max'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="filter-group">
                        <button type="button" class="filter-group__head" aria-expanded="true">
                            Disponibilidad <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="filter-group__body">
                            <label class="filter-check">
                                <input type="checkbox" name="ofertas" value="1" <?= !empty($filters['ofertas']) ? 'checked' : '' ?>>
                                🔖 En oferta
                            </label>
                            <label class="filter-check">
                                <input type="checkbox" name="destacados" value="1" <?= !empty($filters['destacados']) ? 'checked' : '' ?>>
                                ⭐ Destacados
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group__body pt-3">
                            <button type="submit" class="btn btn-accent w-100">
                                <i class="bi bi-funnel-fill"></i> Aplicar filtros
                            </button>
                        </div>
                    </div>
                </form>

                <div class="filter-group">
                    <div class="filter-group__body pt-3">
                        <div class="p-3 rounded" style="background:#111;color:#ccc">
                            <strong class="d-block text-white mb-1"><i class="bi bi-question-circle text-accent"></i> ¿No encontrás el repuesto?</strong>
                            <p class="small mb-2">Mandanos el número de parte o una foto y lo buscamos.</p>
                            <a href="<?= url('contacto') ?>" class="btn btn-accent btn-sm w-100">Consultar</a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- ================= RESULTADOS ================= -->
            <div>
                <div class="catalog-toolbar">
                    <button type="button" class="btn btn-outline-accent btn-sm filters-mobile-btn" id="filtersOpen">
                        <i class="bi bi-funnel-fill"></i> Filtros
                    </button>

                    <span class="catalog-toolbar__count">
                        <strong><?= number_es($result['total']) ?></strong> repuesto(s)
                        <?php if (!empty($filters['q'])): ?>
                            para “<strong><?= e($filters['q']) ?></strong>”
                        <?php endif; ?>
                    </span>

                    <div class="catalog-toolbar__right">
                        <form method="get" class="d-flex align-items-center gap-2">
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if ($key !== 'orden' && $key !== 'pagina' && !is_array($value)): ?>
                                    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <select name="orden" class="form-select form-select-sm" data-autosubmit>
                                <?php foreach ($sorts as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= ($filters['orden'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <div class="view-switch">
                            <a href="<?= e(query_url(['vista' => 'grid'])) ?>" class="<?= $viewMode === 'grid' ? 'is-active' : '' ?>"><i class="bi bi-grid-fill"></i></a>
                            <a href="<?= e(query_url(['vista' => 'lista'])) ?>" class="<?= $viewMode === 'lista' ? 'is-active' : '' ?>"><i class="bi bi-list-ul"></i></a>
                        </div>
                    </div>
                </div>

                <div id="catalogResults">
                    <?php $view->partial('product-grid', ['products' => $products, 'viewMode' => $viewMode]); ?>
                </div>

                <?php $view->partial('pagination', ['result' => $result]); ?>

                <!-- Compatibles por modelo -->
                <?php if (!empty($compatibleList)): ?>
                    <div class="mt-5">
                        <div class="section-head">
                            <div>
                                <span class="eyebrow">Compatibilidad</span>
                                <h2 class="section-title" style="font-size:1.5rem">
                                    También sirven para <?= e($matchedModel) ?>
                                </h2>
                            </div>
                        </div>
                        <?php $view->partial('product-grid', ['products' => $compatibleList, 'viewMode' => 'grid']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Buscamos el repuesto que necesites</h2>
                <p>Mandanos el número de parte, el modelo de la máquina o una foto de la pieza.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('contacto') ?>" class="btn btn-dark-2"><i class="bi bi-send"></i> Consultar</a>
            </div>
        </div>
    </div>
</section>
