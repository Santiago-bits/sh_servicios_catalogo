<?php
/**
 * ARCHIVO: app/views/admin/dashboard/index.php
 */

use App\Services\StockService;
?>

<!-- ================= MÉTRICAS ================= -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label"><i class="bi bi-box-seam"></i> Productos</div>
        <div class="stat-card__value"><?= number_es($stats['productos'] ?? 0) ?></div>
        <div class="stat-card__hint"><?= number_es($stats['activos'] ?? 0) ?> activos</div>
        <i class="bi bi-boxes stat-card__icon"></i>
    </div>

    <div class="stat-card stat-card--dark">
        <div class="stat-card__label"><i class="bi bi-truck-front"></i> Maquinaria</div>
        <div class="stat-card__value"><?= number_es($stats['maquinas'] ?? 0) ?></div>
        <div class="stat-card__hint"><a href="<?= admin_url('maquinaria') ?>">Administrar</a></div>
        <i class="bi bi-truck-front stat-card__icon"></i>
    </div>

    <div class="stat-card stat-card--info">
        <div class="stat-card__label"><i class="bi bi-nut"></i> Repuestos</div>
        <div class="stat-card__value"><?= number_es($stats['repuestos'] ?? 0) ?></div>
        <div class="stat-card__hint"><a href="<?= admin_url('repuestos') ?>">Administrar</a></div>
        <i class="bi bi-nut stat-card__icon"></i>
    </div>

    <div class="stat-card <?= ($stats['consultas_nuevas'] ?? 0) > 0 ? 'stat-card--warn' : '' ?>">
        <div class="stat-card__label"><i class="bi bi-chat-dots"></i> Consultas</div>
        <div class="stat-card__value"><?= number_es($stats['consultas'] ?? 0) ?></div>
        <div class="stat-card__hint">
            <?php if (($stats['consultas_nuevas'] ?? 0) > 0): ?>
                <span class="chip chip--accent"><?= (int) $stats['consultas_nuevas'] ?> sin responder</span>
            <?php else: ?>
                Todo respondido
            <?php endif; ?>
        </div>
        <i class="bi bi-chat-dots stat-card__icon"></i>
    </div>

    <div class="stat-card stat-card--ok">
        <div class="stat-card__label"><i class="bi bi-file-earmark-text"></i> Cotizaciones</div>
        <div class="stat-card__value"><?= number_es($stats['cotizaciones'] ?? 0) ?></div>
        <div class="stat-card__hint"><?= (int) ($stats['cotizaciones_enviadas'] ?? 0) ?> enviadas</div>
        <i class="bi bi-file-earmark-text stat-card__icon"></i>
    </div>

    <div class="stat-card <?= ($stats['stock_bajo'] ?? 0) > 0 ? 'stat-card--danger' : 'stat-card--ok' ?>">
        <div class="stat-card__label"><i class="bi bi-battery-low"></i> Stock bajo</div>
        <div class="stat-card__value"><?= number_es($stats['stock_bajo'] ?? 0) ?></div>
        <div class="stat-card__hint"><a href="<?= admin_url('stock?filtro=bajo') ?>">Ver repuestos</a></div>
        <i class="bi bi-battery-low stat-card__icon"></i>
    </div>
</div>

<div class="row g-3">

    <!-- ================= GRÁFICOS ================= -->
    <div class="col-lg-8">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-graph-up"></i> Visitas de los últimos 30 días</h2>
                <a href="<?= admin_url('estadisticas') ?>" class="btn btn-ghost btn-sm">Ver estadísticas</a>
            </div>
            <div class="card-admin__body">
                <div class="chart-box">
                    <canvas data-chart='<?= ejs([
                        'type'   => 'line',
                        'labels' => $viewsChart['labels'],
                        'values' => $viewsChart['values'],
                        'label'  => 'Visitas',
                    ]) ?>'></canvas>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card-admin h-100">
                    <div class="card-admin__head"><h2><i class="bi bi-bar-chart"></i> Cotizaciones por mes</h2></div>
                    <div class="card-admin__body">
                        <div class="chart-box chart-box--sm">
                            <canvas data-chart='<?= ejs([
                                'type'   => 'bar',
                                'labels' => $quotesChart['labels'],
                                'values' => $quotesChart['values'],
                                'label'  => 'Cotizaciones',
                            ]) ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card-admin h-100">
                    <div class="card-admin__head"><h2><i class="bi bi-pie-chart"></i> Máquinas por categoría</h2></div>
                    <div class="card-admin__body">
                        <div class="chart-box chart-box--sm">
                            <canvas data-chart='<?= ejs([
                                'type'       => 'doughnut',
                                'labels'     => $categoryChart['labels'],
                                'values'     => $categoryChart['values'],
                                'multicolor' => true,
                            ]) ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos más vistos -->
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-eye"></i> Productos más vistos</h2>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <div class="table-responsive-admin">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Visitas</th>
                                <th class="num">Consultas</th>
                                <th class="num">Cotizaciones</th>
                                <th class="num">Favoritos</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_merge($topMachines, $topParts) as $product): ?>
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <span class="table-thumb d-grid" style="place-items:center">
                                            <i class="bi <?= $product['type'] === 'machine' ? 'bi-truck-front' : 'bi-nut' ?>"></i>
                                        </span>
                                        <span>
                                            <span class="table-product__name"><?= e(str_limit((string) $product['name'], 52)) ?></span>
                                            <span class="table-product__meta"><?= e($product['code']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="num fw-bold"><?= number_es($product['views']) ?></td>
                                <td class="num"><?= number_es($product['inquiries_count']) ?></td>
                                <td class="num"><?= number_es($product['quotes_count']) ?></td>
                                <td class="num"><?= number_es($product['favoritos']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= COLUMNA LATERAL ================= -->
    <div class="col-lg-4">

        <!-- Alertas -->
        <?php if (!empty($alerts)): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-bell-fill"></i> Alertas</h2>
                    <a href="<?= admin_url('alertas') ?>" class="btn btn-ghost btn-sm">Ver todas</a>
                </div>
                <div class="card-admin__body">
                    <?php foreach (array_slice($alerts, 0, 4) as $alert): ?>
                        <div class="alert-card alert-card--<?= e($alert['level']) ?>">
                            <div class="alert-card__head">
                                <i class="bi <?= e($alert['icon']) ?>"></i>
                                <strong><?= e($alert['title']) ?></strong>
                                <span class="alert-card__count"><?= (int) $alert['count'] ?></span>
                            </div>
                            <ul class="alert-card__list">
                                <?php foreach (array_slice($alert['items'], 0, 3) as $item): ?>
                                    <li>
                                        <span><?= e(str_limit((string) ($item['name'] ?? $item['number'] ?? $item['code'] ?? ''), 40)) ?></span>
                                        <?php if (isset($item['available'])): ?>
                                            <strong><?= (int) $item['available'] ?> u.</strong>
                                        <?php elseif (isset($item['valid_until'])): ?>
                                            <strong><?= e(date_es((string) $item['valid_until'])) ?></strong>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?= e($alert['url']) ?>" class="btn btn-ghost btn-sm mt-2 w-100">Resolver</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Últimas consultas -->
        <?php if (can('inquiries.view')): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-chat-dots"></i> Últimas consultas</h2>
                    <a href="<?= admin_url('consultas') ?>" class="btn btn-ghost btn-sm">Ver todas</a>
                </div>
                <div class="card-admin__body card-admin__body--flush">
                    <table class="table-admin">
                        <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <?php $badge = inquiry_status_badge((string) $inquiry['status']); ?>
                            <tr>
                                <td>
                                    <a href="<?= admin_url('consultas/' . (int) $inquiry['id']) ?>" class="fw-bold d-block">
                                        <?= e($inquiry['name']) ?>
                                    </a>
                                    <small class="text-muted-2">
                                        <?= e(str_limit((string) ($inquiry['subject'] ?? $inquiry['product_name'] ?? 'Consulta general'), 40)) ?>
                                        · <?= e(time_ago((string) $inquiry['created_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inquiries)): ?>
                            <tr><td class="text-muted-2 text-center py-4">Sin consultas todavía.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Últimas cotizaciones -->
        <?php if (can('quotes.view')): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-file-earmark-text"></i> Cotizaciones recientes</h2>
                    <a href="<?= admin_url('cotizaciones') ?>" class="btn btn-ghost btn-sm">Ver todas</a>
                </div>
                <div class="card-admin__body card-admin__body--flush">
                    <table class="table-admin">
                        <tbody>
                        <?php foreach ($quotes as $quote): ?>
                            <?php $badge = quote_status_badge((string) $quote['status']); ?>
                            <tr>
                                <td>
                                    <a href="<?= admin_url('cotizaciones/' . (int) $quote['id']) ?>" class="fw-bold d-block text-mono">
                                        <?= e($quote['number']) ?>
                                    </a>
                                    <small class="text-muted-2"><?= e(str_limit((string) $quote['customer_name'], 30)) ?></small>
                                </td>
                                <td class="text-end">
                                    <strong class="d-block"><?= e(money((float) $quote['total'], (string) $quote['currency'])) ?></strong>
                                    <span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($quotes)): ?>
                            <tr><td class="text-muted-2 text-center py-4">Sin cotizaciones todavía.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Búsquedas más frecuentes -->
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-search"></i> Búsquedas frecuentes</h2></div>
            <div class="card-admin__body">
                <?php if (empty($topSearches)): ?>
                    <p class="text-muted-2 mb-0">Todavía no hay búsquedas registradas.</p>
                <?php else: ?>
                    <?php foreach ($topSearches as $search): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span>
                                <?= e($search['term']) ?>
                                <?php if ((int) $search['sin_resultados'] > 0): ?>
                                    <span class="chip chip--danger ms-1" title="Búsquedas sin resultados">
                                        <?= (int) $search['sin_resultados'] ?> sin resultado
                                    </span>
                                <?php endif; ?>
                            </span>
                            <strong><?= (int) $search['total'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock bajo -->
        <?php if (can('stock.view') && !empty($lowStock)): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-battery-low"></i> Stock bajo</h2>
                    <a href="<?= admin_url('stock?filtro=bajo') ?>" class="btn btn-ghost btn-sm">Ver</a>
                </div>
                <div class="card-admin__body card-admin__body--flush">
                    <table class="table-admin">
                        <tbody>
                        <?php foreach ($lowStock as $item): ?>
                            <tr>
                                <td>
                                    <span class="table-product__name"><?= e(str_limit((string) $item['name'], 34)) ?></span>
                                    <span class="table-product__meta"><?= e($item['code']) ?></span>
                                </td>
                                <td class="num">
                                    <span class="chip chip--<?= (int) $item['available'] <= 0 ? 'danger' : 'warn' ?>">
                                        <?= (int) $item['available'] ?> / mín. <?= (int) $item['stock_min'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actividad reciente -->
        <?php if (!empty($activity)): ?>
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-shield-check"></i> Actividad reciente</h2>
                    <a href="<?= admin_url('auditoria') ?>" class="btn btn-ghost btn-sm">Auditoría</a>
                </div>
                <div class="card-admin__body">
                    <div class="timeline">
                        <?php foreach ($activity as $log): ?>
                            <div class="timeline__item">
                                <div class="timeline__time"><?= e(time_ago((string) $log['created_at'])) ?></div>
                                <div class="timeline__text">
                                    <strong><?= e($log['user_name'] ?? 'Sistema') ?></strong>
                                    <?= e(str_limit((string) $log['description'], 70)) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
