<?php
/**
 * ARCHIVO: app/views/admin/quotes/index.php
 */
?>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label"><i class="bi bi-files"></i> Total</div>
        <div class="stat-card__value"><?= number_es($stats['total'] ?? 0) ?></div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__label"><i class="bi bi-send"></i> Enviadas</div>
        <div class="stat-card__value"><?= number_es($stats['enviadas'] ?? 0) ?></div>
    </div>
    <div class="stat-card stat-card--ok">
        <div class="stat-card__label"><i class="bi bi-check-circle"></i> Aceptadas</div>
        <div class="stat-card__value"><?= number_es($stats['aceptadas'] ?? 0) ?></div>
        <div class="stat-card__hint"><?= e(money((float) ($stats['monto_aceptado'] ?? 0))) ?></div>
    </div>
    <div class="stat-card stat-card--warn">
        <div class="stat-card__label"><i class="bi bi-pencil"></i> Borradores</div>
        <div class="stat-card__value"><?= number_es($stats['borradores'] ?? 0) ?></div>
    </div>
</div>

<div class="admin-filters">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end w-100">
        <div>
            <label class="form-label" for="f-q">Buscar</label>
            <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                   placeholder="Número, cliente, empresa">
        </div>
        <div>
            <label class="form-label" for="f-estado">Estado</label>
            <select class="form-select" id="f-estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['estado'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e(quote_status_badge($status)['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-desde">Desde</label>
            <input type="date" class="form-control" id="f-desde" name="desde" value="<?= e($filters['desde'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" for="f-hasta">Hasta</label>
            <input type="date" class="form-control" id="f-hasta" name="hasta" value="<?= e($filters['hasta'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label" for="f-user">Asesor</label>
            <select class="form-select" id="f-user" name="usuario">
                <option value="">Todos</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (string) ($filters['usuario'] ?? '') === (string) $user['id'] ? 'selected' : '' ?>>
                        <?= e($user['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark-2"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= admin_url('cotizaciones') ?>" class="btn btn-ghost">Limpiar</a>
        </div>

        <div class="ms-auto d-flex gap-2">
            <?php if (can('quotes.create')): ?>
                <a href="<?= admin_url('cotizaciones/crear') ?>" class="btn btn-accent">
                    <i class="bi bi-plus-lg"></i> Nueva cotización
                </a>
            <?php endif; ?>
            <?php if (can('data.export')): ?>
                <a href="<?= admin_url('exportar/cotizaciones/xlsx') ?>" class="btn btn-ghost" title="Exportar">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-file-earmark-text"></i> Cotizaciones</h2>
        <span class="text-muted-2 small"><?= number_es($result['total']) ?> registro(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <div class="table-responsive-admin">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th class="num">Ítems</th>
                        <th class="num">Total</th>
                        <th>Estado</th>
                        <th>Validez</th>
                        <th>Asesor</th>
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <?php
                    $badge   = quote_status_badge((string) $quote['status']);
                    $expired = !empty($quote['valid_until']) && strtotime((string) $quote['valid_until']) < strtotime('today');
                    ?>
                    <tr>
                        <td>
                            <a href="<?= admin_url('cotizaciones/' . (int) $quote['id']) ?>" class="fw-bold text-mono">
                                <?= e($quote['number']) ?>
                            </a>
                            <small class="d-block text-muted-2"><?= e(date_es((string) $quote['created_at'])) ?></small>
                        </td>
                        <td>
                            <span class="table-product__name"><?= e($quote['customer_name']) ?></span>
                            <span class="table-product__meta"><?= e($quote['customer_company'] ?? $quote['customer_email'] ?? '') ?></span>
                        </td>
                        <td class="num"><?= (int) $quote['items_count'] ?></td>
                        <td class="num"><strong><?= e(money((float) $quote['total'], (string) $quote['currency'])) ?></strong></td>
                        <td><span class="chip chip--<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span></td>
                        <td>
                            <?= e(date_es($quote['valid_until'] ?? null)) ?>
                            <?php if ($expired && $quote['status'] === 'enviada'): ?>
                                <span class="chip chip--danger d-block mt-1">Vencida</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted-2 small"><?= e($quote['user_name'] ?? '—') ?></td>
                        <td class="actions">
                            <a href="<?= admin_url('cotizaciones/' . (int) $quote['id']) ?>" class="btn-icon" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/pdf') ?>" target="_blank"
                               class="btn-icon" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            <?php if (can('quotes.edit')): ?>
                                <a href="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/editar') ?>" class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (can('quotes.delete')): ?>
                                <form method="post" action="<?= admin_url('cotizaciones/' . (int) $quote['id'] . '/eliminar') ?>"
                                      class="d-inline" data-confirm="¿Eliminar la cotización <?= e($quote['number']) ?>?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-icon btn-icon--danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted-2">
                            <i class="bi bi-file-earmark" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                            No hay cotizaciones con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($result['last_page'] ?? 1) > 1): ?>
        <div class="card-admin__foot"><?php $view->partial('pagination', ['result' => $result]); ?></div>
    <?php endif; ?>
</div>
