<?php
/**
 * ARCHIVO: app/views/admin/prices/history.php
 */
?>
<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-clock-history"></i> <?= e($product['name']) ?></h2>
        <div class="d-flex gap-2">
            <a href="<?= admin_url(($product['type'] === 'machine' ? 'maquinaria/' : 'repuestos/') . (int) $product['id'] . '/editar') ?>"
               class="btn btn-ghost btn-sm"><i class="bi bi-pencil"></i> Editar producto</a>
            <a href="<?= admin_url('precios?tipo=' . e($product['type'])) ?>" class="btn btn-ghost btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a precios
            </a>
        </div>
    </div>

    <div class="card-admin__body">
        <div class="stat-grid" style="margin-bottom:0">
            <div class="stat-card">
                <div class="stat-card__label">Código</div>
                <div class="stat-card__value text-mono" style="font-size:1.2rem"><?= e($product['code']) ?></div>
            </div>

            <?php if ($canSeeCost): ?>
                <div class="stat-card stat-card--info">
                    <div class="stat-card__label">Costo actual</div>
                    <div class="stat-card__value" style="font-size:1.4rem"><?= e(money((float) $product['cost_price'], (string) $product['currency'])) ?></div>
                </div>
                <div class="stat-card stat-card--ok">
                    <div class="stat-card__label">Ganancia</div>
                    <div class="stat-card__value" style="font-size:1.4rem"><?= e(percent((float) $product['profit_percent'])) ?></div>
                    <div class="stat-card__hint"><?= e(money((float) $product['profit_amount'], (string) $product['currency'])) ?></div>
                </div>
            <?php endif; ?>

            <div class="stat-card">
                <div class="stat-card__label">Precio final</div>
                <div class="stat-card__value" style="font-size:1.4rem"><?= e(money((float) $product['final_price'], (string) $product['currency'])) ?></div>
                <div class="stat-card__hint">Actualizado <?= e(date_es($product['price_updated_at'] ?? null, true)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-list-columns-reverse"></i> Historial de cambios</h2>
        <span class="text-muted-2 small"><?= count($history) ?> registro(s)</span>
    </div>

    <div class="card-admin__body card-admin__body--flush">
        <?php if (empty($history)): ?>
            <div class="text-center py-5 text-muted-2">
                <i class="bi bi-clock" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                Todavía no hay cambios registrados para este producto.
            </div>
        <?php else: ?>
            <div class="table-responsive-admin">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <?php if ($canSeeCost): ?>
                                <th class="num">Costo</th>
                                <th class="num">Ganancia</th>
                            <?php endif; ?>
                            <th class="num">Precio anterior</th>
                            <th class="num">Precio nuevo</th>
                            <th class="num">Variación</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $row): ?>
                        <?php
                        $old   = (float) $row['old_price'];
                        $new   = (float) $row['new_price'];
                        $delta = $old > 0 ? (($new - $old) / $old) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= e(date_es((string) $row['created_at'], true)) ?></td>
                            <td><?= e($row['user_name'] ?? 'Sistema') ?></td>

                            <?php if ($canSeeCost): ?>
                                <td class="num text-muted-2">
                                    <?= e(money((float) $row['old_cost'], (string) $row['currency'])) ?>
                                    <i class="bi bi-arrow-right"></i>
                                    <?= e(money((float) $row['new_cost'], (string) $row['currency'])) ?>
                                </td>
                                <td class="num text-muted-2">
                                    <?= e(percent((float) $row['old_profit_percent'], 1)) ?>
                                    <i class="bi bi-arrow-right"></i>
                                    <?= e(percent((float) $row['new_profit_percent'], 1)) ?>
                                </td>
                            <?php endif; ?>

                            <td class="num"><?= e(money($old, (string) $row['currency'])) ?></td>
                            <td class="num"><strong><?= e(money($new, (string) $row['currency'])) ?></strong></td>
                            <td class="num">
                                <span class="chip chip--<?= $delta > 0 ? 'warn' : ($delta < 0 ? 'ok' : 'neutral') ?>">
                                    <?= $delta > 0 ? '+' : '' ?><?= e(number_es($delta, 1)) ?>%
                                </span>
                            </td>
                            <td class="text-muted-2 small"><?= e($row['reason'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
