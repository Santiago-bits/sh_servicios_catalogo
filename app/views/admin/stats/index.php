<?php
/**
 * ARCHIVO: app/views/admin/stats/index.php
 */
?>
<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-dias">Período</label>
            <select class="form-select" id="f-dias" name="dias" data-autosubmit>
                <?php foreach ([7 => 'Últimos 7 días', 30 => 'Últimos 30 días', 90 => 'Últimos 90 días', 180 => 'Últimos 6 meses', 365 => 'Último año'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $days === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (can('data.export')): ?>
            <div class="ms-auto">
                <a href="<?= admin_url('exportar') ?>" class="btn btn-ghost"><i class="bi bi-download"></i> Exportar datos</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label"><i class="bi bi-eye"></i> Visitas totales</div>
        <div class="stat-card__value"><?= number_es($stats['visitas_totales'] ?? 0) ?></div>
        <div class="stat-card__hint"><?= number_es($stats['visitas_mes'] ?? 0) ?> en los últimos 30 días</div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__label"><i class="bi bi-search"></i> Búsquedas</div>
        <div class="stat-card__value"><?= number_es($stats['busquedas_mes'] ?? 0) ?></div>
        <div class="stat-card__hint">Últimos 30 días</div>
    </div>
    <div class="stat-card stat-card--warn">
        <div class="stat-card__label"><i class="bi bi-chat-dots"></i> Consultas</div>
        <div class="stat-card__value"><?= number_es($stats['consultas'] ?? 0) ?></div>
    </div>
    <div class="stat-card stat-card--ok">
        <div class="stat-card__label"><i class="bi bi-file-earmark-text"></i> Cotizaciones</div>
        <div class="stat-card__value"><?= number_es($stats['cotizaciones'] ?? 0) ?></div>
        <div class="stat-card__hint"><?= e(money((float) ($stats['monto_aceptado'] ?? 0))) ?> aceptados</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-graph-up"></i> Visitas por día</h2></div>
            <div class="card-admin__body">
                <div class="chart-box">
                    <canvas data-chart='<?= ejs(['type' => 'line', 'labels' => $viewsChart['labels'], 'values' => $viewsChart['values'], 'label' => 'Visitas']) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-pie-chart"></i> Consultas por canal</h2></div>
            <div class="card-admin__body">
                <div class="chart-box">
                    <canvas data-chart='<?= ejs(['type' => 'doughnut', 'labels' => $channelChart['labels'], 'values' => $channelChart['values'], 'multicolor' => true]) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-bar-chart"></i> Cotizaciones por mes</h2></div>
            <div class="card-admin__body">
                <div class="chart-box chart-box--sm">
                    <canvas data-chart='<?= ejs(['type' => 'bar', 'labels' => $quotesChart['labels'], 'values' => $quotesChart['values'], 'label' => 'Cotizaciones']) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-diagram-2"></i> Productos por categoría</h2></div>
            <div class="card-admin__body">
                <div class="chart-box chart-box--sm">
                    <canvas data-chart='<?= ejs(['type' => 'bar', 'labels' => $machineCatChart['labels'], 'values' => $machineCatChart['values'], 'label' => 'Máquinas']) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos más vistos -->
    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-truck-front"></i> Maquinaria más vista</h2></div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Visitas</th>
                                <th class="num">Consultas</th>
                                <th class="num">Cotiz.</th>
                                <th class="num">Favs</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topMachines as $product): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e(str_limit((string) $product['name'], 36)) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($product['code']) ?></span>
                                </td>
                                <td class="num fw-bold"><?= number_es($product['views']) ?></td>
                                <td class="num"><?= number_es($product['inquiries_count']) ?></td>
                                <td class="num"><?= number_es($product['quotes_count']) ?></td>
                                <td class="num"><?= number_es($product['favoritos']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topMachines)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted-2">Sin datos todavía.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-nut"></i> Repuestos más vistos</h2></div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Repuesto</th>
                                <th class="num">Visitas</th>
                                <th class="num">Consultas</th>
                                <th class="num">Cotiz.</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topParts as $product): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e(str_limit((string) $product['name'], 36)) ?></span>
                                    <span class="table-product__meta text-mono"><?= e($product['code']) ?></span>
                                </td>
                                <td class="num fw-bold"><?= number_es($product['views']) ?></td>
                                <td class="num"><?= number_es($product['inquiries_count']) ?></td>
                                <td class="num"><?= number_es($product['quotes_count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topParts)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted-2">Sin datos todavía.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Analítica de búsquedas -->
    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-search"></i> Repuestos más buscados</h2>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <table class="table-admin">
                    <thead>
                        <tr><th>Término</th><th class="num">Búsquedas</th><th class="num">Sin resultados</th><th>Última</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($topPartSearches as $search): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('repuestos?q=' . urlencode((string) $search['term']))) ?>" target="_blank">
                                    <?= e($search['term']) ?>
                                </a>
                            </td>
                            <td class="num fw-bold"><?= (int) $search['total'] ?></td>
                            <td class="num">
                                <?php if ((int) $search['sin_resultados'] > 0): ?>
                                    <span class="chip chip--danger"><?= (int) $search['sin_resultados'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="small text-muted-2"><?= e(time_ago((string) $search['ultima'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topPartSearches)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted-2">Sin búsquedas registradas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-exclamation-triangle"></i> Búsquedas sin resultados</h2>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="p-3 pb-0">
                    <p class="form-hint mb-2">
                        Oportunidades: lo que la gente busca y hoy no tenemos cargado.
                    </p>
                </div>
                <table class="table-admin">
                    <thead>
                        <tr><th>Término</th><th class="num">Veces</th><th>Última</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($noResults as $search): ?>
                        <tr>
                            <td><?= e($search['term']) ?></td>
                            <td class="num fw-bold"><?= (int) $search['total'] ?></td>
                            <td class="small text-muted-2"><?= e(time_ago((string) $search['ultima'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($noResults)): ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted-2">Todas las búsquedas encontraron resultados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Categorías populares -->
    <div class="col-12">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-collection"></i> Categorías más visitadas</h2></div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr><th>Categoría</th><th>Tipo</th><th class="num">Productos</th><th class="num">Visitas</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topCategories as $category): ?>
                            <tr>
                                <td><strong><?= e($category['name']) ?></strong></td>
                                <td>
                                    <span class="chip chip--<?= $category['type'] === 'machine' ? 'accent' : 'info' ?>">
                                        <?= $category['type'] === 'machine' ? 'Maquinaria' : 'Repuestos' ?>
                                    </span>
                                </td>
                                <td class="num"><?= (int) $category['productos'] ?></td>
                                <td class="num fw-bold"><?= number_es($category['visitas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topCategories)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted-2">Sin datos todavía.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
