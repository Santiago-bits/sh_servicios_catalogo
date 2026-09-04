<?php
/**
 * ARCHIVO: app/views/admin/settings/index.php
 * Configuración general del sistema.
 */
?>
<form method="post" action="<?= admin_url('configuracion') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="settings-nav">
        <?php foreach ($groups as $groupKey => $items): ?>
            <?php [$label, $icon] = $groupLabels[$groupKey] ?? [ucfirst($groupKey), 'bi-gear']; ?>
            <button type="button" data-group="<?= e($groupKey) ?>">
                <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($groups as $groupKey => $items): ?>
        <?php [$label, $icon] = $groupLabels[$groupKey] ?? [ucfirst($groupKey), 'bi-gear']; ?>

        <div class="settings-panel" data-group="<?= e($groupKey) ?>">
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <?php foreach ($items as $setting): ?>
                            <?php
                            $key     = (string) $setting['key_name'];
                            $value   = (string) ($setting['value'] ?? '');
                            $options = json_decode((string) ($setting['options'] ?? ''), true);
                            $width   = in_array($setting['type'], ['textarea', 'json'], true) ? 12 : 6;
                            ?>
                            <div class="col-md-<?= $width ?>">
                                <?php if ($setting['type'] === 'boolean'): ?>
                                    <div class="form-switch-row">
                                        <div>
                                            <strong><?= e($setting['label']) ?></strong>
                                            <?php if (!empty($setting['help'])): ?>
                                                <small><?= e($setting['help']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" name="<?= e($key) ?>" value="1"
                                                   id="set-<?= e($key) ?>" <?= $value === '1' ? 'checked' : '' ?>>
                                        </div>
                                    </div>

                                <?php elseif ($setting['type'] === 'textarea'): ?>
                                    <label class="form-label" for="set-<?= e($key) ?>"><?= e($setting['label']) ?></label>
                                    <textarea class="form-control" id="set-<?= e($key) ?>" name="<?= e($key) ?>"
                                              rows="<?= $key === 'quote_conditions' ? 5 : 3 ?>"><?= e($value) ?></textarea>
                                    <?php if (!empty($setting['help'])): ?>
                                        <p class="form-hint"><?= e($setting['help']) ?></p>
                                    <?php endif; ?>

                                <?php elseif ($setting['type'] === 'select' && is_array($options)): ?>
                                    <label class="form-label" for="set-<?= e($key) ?>"><?= e($setting['label']) ?></label>
                                    <select class="form-select" id="set-<?= e($key) ?>" name="<?= e($key) ?>">
                                        <?php foreach ($options as $optKey => $optLabel): ?>
                                            <?php $optValue = is_int($optKey) ? $optLabel : $optKey; ?>
                                            <option value="<?= e((string) $optValue) ?>" <?= $value === (string) $optValue ? 'selected' : '' ?>>
                                                <?= e((string) $optLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($setting['help'])): ?>
                                        <p class="form-hint"><?= e($setting['help']) ?></p>
                                    <?php endif; ?>

                                <?php elseif ($setting['type'] === 'image'): ?>
                                    <label class="form-label" for="set-file-<?= e($key) ?>"><?= e($setting['label']) ?></label>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <img id="prev-<?= e($key) ?>"
                                             src="<?= $value !== '' ? e(upload_url($value)) : '' ?>"
                                             alt="" <?= $value === '' ? 'hidden' : '' ?>
                                             style="max-height:56px;max-width:220px;background:#fff;border-radius:6px;padding:4px;border:1px solid #E2E4E8;object-fit:contain">
                                        <span class="text-muted-2 small text-mono"><?= $value !== '' ? e($value) : 'Sin imagen cargada' ?></span>
                                    </div>
                                    <input type="file" class="form-control" id="set-file-<?= e($key) ?>"
                                           name="file_<?= e($key) ?>" accept="image/png,image/jpeg,image/webp,image/gif"
                                           data-image-preview="prev-<?= e($key) ?>">
                                    <p class="form-hint">
                                        <?= !empty($setting['help']) ? e($setting['help']) . ' ' : '' ?>
                                        Elegí un archivo PNG, JPG o WEBP y tocá <strong>Guardar configuración</strong>.
                                    </p>

                                <?php else: ?>
                                    <label class="form-label" for="set-<?= e($key) ?>"><?= e($setting['label']) ?></label>
                                    <input type="<?= $setting['type'] === 'number' ? 'text' : ($setting['type'] === 'email' ? 'email' : 'text') ?>"
                                           class="form-control <?= $setting['type'] === 'url' || $key === 'contact_whatsapp' ? 'text-mono' : '' ?>"
                                           id="set-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
                                    <?php if (!empty($setting['help'])): ?>
                                        <p class="form-hint"><?= e($setting['help']) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($groupKey === 'moneda'): ?>
                        <hr class="my-4">
                        <h3 class="form-section__title"><i class="bi bi-currency-exchange"></i> Monedas configuradas</h3>
                        <table class="table-admin">
                            <thead>
                                <tr><th>Código</th><th>Nombre</th><th>Símbolo</th><th class="num">Equivalencia</th><th>Base</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td class="text-mono fw-bold"><?= e($currency['code']) ?></td>
                                    <td><?= e($currency['name']) ?></td>
                                    <td><?= e($currency['symbol']) ?></td>
                                    <td class="num"><?= e(number_es((float) $currency['rate_to_base'], 2)) ?></td>
                                    <td>
                                        <?php if ((int) $currency['is_base'] === 1): ?>
                                            <span class="chip chip--accent">Base</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <hr class="my-4">
                        <h3 class="form-section__title"><i class="bi bi-arrow-repeat"></i> Cotización automática (lanacion.com.ar)</h3>
                        <?php $usd = \App\Services\ExchangeRateService::cached(); ?>
                        <?php if ($usd !== null): ?>
                            <p class="mb-2">
                                Último valor traído:
                                <strong>$<?= e(number_es((float) $usd['rate'], 2)) ?></strong>
                                (dólar <?= e($usd['source'] ?? '') ?>) ·
                                <span class="text-muted-2">
                                    <?= e(date('d/m/Y H:i', strtotime((string) ($usd['at'] ?? 'now')))) ?> hs
                                </span>
                            </p>
                        <?php else: ?>
                            <p class="mb-2 text-muted-2">Todavía no se trajo ninguna cotización automática.</p>
                        <?php endif; ?>
                        <p class="form-hint mb-2">
                            Activá <strong>“Actualizar la cotización del dólar automáticamente”</strong> para que el
                            campo <strong>“Cotización del dólar”</strong> se complete solo con el valor de lanacion.com.ar.
                            Con “Actualizar ahora” lo traés en el momento. Si el hosting bloquea la salida a internet,
                            seguí cargando el valor a mano en el campo de arriba.
                        </p>
                        <button type="button" class="btn btn-outline-accent btn-sm"
                                data-form-action="<?= admin_url('configuracion/dolar') ?>">
                            <i class="bi bi-arrow-repeat"></i> Actualizar ahora desde lanacion.com.ar
                        </button>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="form-sticky-actions">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar configuración</button>
    </div>
</form>
