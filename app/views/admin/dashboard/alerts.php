<?php
/**
 * ARCHIVO: app/views/admin/dashboard/alerts.php
 */
?>
<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-bell-fill"></i> Alertas del sistema</h2>
        <a href="<?= admin_url() ?>" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Volver al dashboard</a>
    </div>
    <div class="card-admin__body">
        <p class="text-muted-2">
            Estas alertas se calculan en tiempo real sobre los datos del catálogo. Resolverlas mejora
            la calidad de la información que ve el cliente.
        </p>
    </div>
</div>

<?php if (empty($alerts)): ?>
    <div class="card-admin">
        <div class="card-admin__body text-center py-5">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;color:#2E9E5B"></i>
            <h2 class="h5 mt-3">Todo en orden</h2>
            <p class="text-muted-2 mb-0">No hay alertas pendientes en este momento.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($alerts as $alert): ?>
            <div class="col-lg-6">
                <div class="alert-card alert-card--<?= e($alert['level']) ?> h-100">
                    <div class="alert-card__head">
                        <i class="bi <?= e($alert['icon']) ?>"></i>
                        <strong><?= e($alert['title']) ?></strong>
                        <span class="alert-card__count"><?= (int) $alert['count'] ?></span>
                    </div>

                    <ul class="alert-card__list">
                        <?php foreach ($alert['items'] as $item): ?>
                            <li>
                                <span>
                                    <?php if (!empty($item['code'])): ?>
                                        <code class="text-mono"><?= e($item['code']) ?></code>
                                    <?php endif; ?>
                                    <?= e(str_limit((string) ($item['name'] ?? $item['number'] ?? $item['customer_name'] ?? $item['subject'] ?? ''), 46)) ?>
                                </span>
                                <strong>
                                    <?php if (isset($item['available'])): ?>
                                        <?= (int) $item['available'] ?> u. (mín. <?= (int) $item['stock_min'] ?>)
                                    <?php elseif (isset($item['valid_until'])): ?>
                                        vence <?= e(date_es((string) $item['valid_until'])) ?>
                                    <?php elseif (isset($item['new_price'])): ?>
                                        <?= e(money((float) $item['new_price'])) ?>
                                    <?php elseif (isset($item['created_at'])): ?>
                                        <?= e(time_ago((string) $item['created_at'])) ?>
                                    <?php elseif (isset($item['type'])): ?>
                                        <?= $item['type'] === 'machine' ? 'Máquina' : 'Repuesto' ?>
                                    <?php endif; ?>
                                </strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($alert['count'] > count($alert['items'])): ?>
                        <p class="form-hint mt-2 mb-2">
                            y <?= $alert['count'] - count($alert['items']) ?> más…
                        </p>
                    <?php endif; ?>

                    <a href="<?= e($alert['url']) ?>" class="btn btn-accent btn-sm mt-2">
                        <i class="bi bi-arrow-right"></i> Ir a resolver
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
