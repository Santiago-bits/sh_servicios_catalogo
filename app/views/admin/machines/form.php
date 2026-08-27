<?php
/**
 * ARCHIVO: app/views/admin/machines/form.php
 * Alta y edición de maquinaria.
 */

$id     = $isEdit ? (int) $product['id'] : 0;
$action = $isEdit ? admin_url('maquinaria/' . $id) : admin_url('maquinaria');

/** Devuelve el valor actual del campo (edición) o el enviado antes de un error. */
$val = static function (string $key, mixed $default = '') use ($isEdit, $product) {
    $old = old($key, null);
    if ($old !== null && $old !== '') {
        return $old;
    }
    return $isEdit ? ($product[$key] ?? $default) : $default;
};
?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="machineForm">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- ============ COLUMNA PRINCIPAL ============ -->
        <div class="col-lg-8">

            <!-- Datos generales -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-info-circle"></i> Datos generales</h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="code">Código interno *</label>
                            <input type="text" class="form-control text-mono <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                   id="code" name="code" required maxlength="60"
                                   value="<?= e($val('code', $nextCode ?? '')) ?>">
                            <span class="form-error"><?= e($errors['code'] ?? '') ?></span>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="name">Nombre *</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   id="name" name="name" required maxlength="200"
                                   placeholder="Ej: Autoelevador Toyota 8FG25 2.500 kg"
                                   value="<?= e($val('name')) ?>">
                            <span class="form-error"><?= e($errors['name'] ?? '') ?></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category_id">Categoría</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"
                                        <?= (int) $val('category_id') === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="brand_id">Marca</label>
                            <select class="form-select" id="brand_id" name="brand_id">
                                <option value="">Sin marca</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int) $brand['id'] ?>"
                                        <?= (int) $val('brand_id') === (int) $brand['id'] ? 'selected' : '' ?>>
                                        <?= e($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="model">Modelo</label>
                            <input type="text" class="form-control" id="model" name="model" maxlength="120"
                                   placeholder="Ej: 8FG25" value="<?= e($val('model')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="short_description">Descripción corta</label>
                            <input type="text" class="form-control" id="short_description" name="short_description"
                                   maxlength="400" placeholder="Se muestra en las tarjetas del catálogo"
                                   value="<?= e($val('short_description')) ?>">
                            <span class="form-hint" data-counter></span>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Descripción completa</label>
                            <textarea class="form-control" id="description" name="description" rows="6"
                                      maxlength="20000"><?= e($val('description')) ?></textarea>
                            <p class="form-hint">
                                Se admiten etiquetas básicas de formato (negrita, listas, párrafos).
                                Todo lo demás se limpia automáticamente por seguridad.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ficha técnica -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-gear-wide-connected"></i> Ficha técnica</h2></div>
                <div class="card-admin__body">

                    <div class="form-section">
                        <h3 class="form-section__title"><i class="bi bi-clipboard-data"></i> Estado del equipo</h3>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="condition_type">Condición</label>
                                <select class="form-select" id="condition_type" name="condition_type">
                                    <?php foreach ($conditions as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $val('condition_type', 'usado') === $value ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="year">Año</label>
                                <input type="number" class="form-control" id="year" name="year"
                                       min="1950" max="<?= (int) date('Y') + 1 ?>" value="<?= e($val('year')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="hours">Horas de uso</label>
                                <input type="number" class="form-control" id="hours" name="hours" min="0" value="<?= e($val('hours')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="serial_number">Nº de serie</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number"
                                       maxlength="80" value="<?= e($val('serial_number')) ?>">
                                <p class="form-hint">Uso interno.</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section__title"><i class="bi bi-arrows-expand"></i> Capacidades</h3>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="capacity_kg">Capacidad (kg)</label>
                                <input type="number" class="form-control" id="capacity_kg" name="capacity_kg"
                                       min="0" step="1" value="<?= e($val('capacity_kg')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="lift_height_mm">Altura máxima (mm)</label>
                                <input type="number" class="form-control" id="lift_height_mm" name="lift_height_mm"
                                       min="0" value="<?= e($val('lift_height_mm')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="closed_height_mm">Altura replegada (mm)</label>
                                <input type="number" class="form-control" id="closed_height_mm" name="closed_height_mm"
                                       min="0" value="<?= e($val('closed_height_mm')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="weight_kg">Peso operativo (kg)</label>
                                <input type="number" class="form-control" id="weight_kg" name="weight_kg"
                                       min="0" value="<?= e($val('weight_kg')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section__title"><i class="bi bi-rulers"></i> Dimensiones</h3>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="length_mm">Largo (mm)</label>
                                <input type="number" class="form-control" id="length_mm" name="length_mm" min="0" value="<?= e($val('length_mm')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="width_mm">Ancho (mm)</label>
                                <input type="number" class="form-control" id="width_mm" name="width_mm" min="0" value="<?= e($val('width_mm')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="turn_radius_mm">Radio de giro (mm)</label>
                                <input type="number" class="form-control" id="turn_radius_mm" name="turn_radius_mm" min="0" value="<?= e($val('turn_radius_mm')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="mast_type">Tipo de mástil</label>
                                <input type="text" class="form-control" id="mast_type" name="mast_type" maxlength="80" value="<?= e($val('mast_type')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section__title"><i class="bi bi-fuel-pump"></i> Motorización</h3>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="fuel">Combustible</label>
                                <select class="form-select" id="fuel" name="fuel">
                                    <?php foreach ($fuels as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= (string) $val('fuel') === (string) $value ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="engine">Motor</label>
                                <input type="text" class="form-control" id="engine" name="engine" maxlength="120" value="<?= e($val('engine')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="power_hp">Potencia (HP)</label>
                                <input type="number" class="form-control" id="power_hp" name="power_hp" min="0" step="0.1" value="<?= e($val('power_hp')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="transmission">Transmisión</label>
                                <input type="text" class="form-control" id="transmission" name="transmission" maxlength="120" value="<?= e($val('transmission')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="battery">Batería</label>
                                <input type="text" class="form-control" id="battery" name="battery" maxlength="120"
                                       placeholder="Ej: Tracción 48V/625Ah" value="<?= e($val('battery')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="voltage">Voltaje</label>
                                <input type="text" class="form-control" id="voltage" name="voltage" maxlength="40" value="<?= e($val('voltage')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="tire_type">Tipo de rueda</label>
                                <input type="text" class="form-control" id="tire_type" name="tire_type" maxlength="80" value="<?= e($val('tire_type')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="warranty">Garantía</label>
                                <input type="text" class="form-control" id="warranty" name="warranty" maxlength="160"
                                       placeholder="Ej: 6 meses" value="<?= e($val('warranty')) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Características dinámicas -->
                    <?php if (!empty($features)): ?>
                        <div class="form-section">
                            <h3 class="form-section__title"><i class="bi bi-sliders"></i> Características configurables</h3>
                            <p class="form-hint mb-3">
                                Estas características se administran desde
                                <a href="<?= admin_url('caracteristicas') ?>">Características técnicas</a>
                                y se muestran en la ficha pública agrupadas.
                            </p>
                            <div class="row g-3">
                                <?php foreach ($features as $feature): ?>
                                    <?php $current = $featureValues[$feature['slug']]['value_text'] ?? ''; ?>
                                    <div class="col-md-4">
                                        <label class="form-label" for="feat-<?= (int) $feature['id'] ?>">
                                            <?= e($feature['name']) ?>
                                            <?php if (!empty($feature['unit'])): ?>
                                                <span class="text-muted-2">(<?= e($feature['unit']) ?>)</span>
                                            <?php endif; ?>
                                        </label>

                                        <?php if ($feature['input_type'] === 'select' && !empty($feature['options'])): ?>
                                            <select class="form-select" id="feat-<?= (int) $feature['id'] ?>" name="features[<?= (int) $feature['id'] ?>]">
                                                <option value="">—</option>
                                                <?php foreach ((array) json_decode((string) $feature['options'], true) as $option): ?>
                                                    <option value="<?= e($option) ?>" <?= $current === $option ? 'selected' : '' ?>>
                                                        <?= e($option) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="<?= $feature['input_type'] === 'number' ? 'text' : 'text' ?>"
                                                   class="form-control" id="feat-<?= (int) $feature['id'] ?>"
                                                   name="features[<?= (int) $feature['id'] ?>]"
                                                   value="<?= e($current) ?>" maxlength="255">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Repuestos compatibles -->
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-diagram-3-fill"></i> Repuestos compatibles</h2>
                    <span class="text-muted-2 small"><?= count($selectedParts) ?> seleccionado(s)</span>
                </div>
                <div class="card-admin__body">
                    <p class="form-hint mb-2">
                        Lo que marques acá se muestra en la ficha pública como “Repuestos compatibles”
                        y también aparece en la ficha del repuesto.
                    </p>

                    <input type="search" class="form-control mb-2" placeholder="Filtrar repuestos…"
                           data-picker-search="#partsPicker">

                    <div class="picker" id="partsPicker">
                        <?php foreach ($availableParts as $part): ?>
                            <div class="picker__item">
                                <label class="d-flex align-items-center gap-2 flex-grow-1 m-0" style="cursor:pointer">
                                    <input type="checkbox" name="spare_parts[]" value="<?= (int) $part['id'] ?>"
                                        <?= in_array((int) $part['id'], $selectedParts, true) ? 'checked' : '' ?>>
                                    <span>
                                        <?= e($part['name']) ?>
                                        <code class="d-block"><?= e($part['code']) ?> · <?= e($part['category_name'] ?? 'Sin categoría') ?></code>
                                    </span>
                                </label>
                                <label class="d-flex align-items-center gap-1 m-0" style="cursor:pointer"
                                       title="Marcar como recomendado para el mantenimiento">
                                    <input type="checkbox" name="recommended_parts[]" value="<?= (int) $part['id'] ?>"
                                        <?= in_array((int) $part['id'], $recommendedParts, true) ? 'checked' : '' ?>>
                                    <i class="bi bi-star"></i>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($availableParts)): ?>
                            <div class="p-3 text-muted-2">
                                Todavía no hay repuestos cargados.
                                <a href="<?= admin_url('repuestos/crear') ?>">Crear el primero</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Videos -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-play-btn"></i> Videos</h2></div>
                <div class="card-admin__body">
                    <label class="form-label" for="videos">URLs de YouTube o Vimeo (una por línea)</label>
                    <textarea class="form-control text-mono" id="videos" name="videos" rows="3"
                              placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX"><?= e($videos ?? '') ?></textarea>
                    <p class="form-hint">Se muestran en la ficha pública como “Ver la máquina trabajando”.</p>
                </div>
            </div>

            <!-- SEO -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-search"></i> SEO</h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="meta_title">Título SEO</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title"
                                   maxlength="180" value="<?= e($val('meta_title')) ?>"
                                   placeholder="Si lo dejás vacío se usa el nombre del producto">
                            <span class="form-hint" data-counter></span>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="meta_description">Meta descripción</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                                      maxlength="300"><?= e($val('meta_description')) ?></textarea>
                            <span class="form-hint" data-counter></span>
                        </div>
                        <?php if ($isEdit): ?>
                            <div class="col-12">
                                <label class="form-label">URL pública</label>
                                <div class="input-group-admin">
                                    <input type="text" class="form-control text-mono" value="<?= e(machine_url($product)) ?>" readonly>
                                    <span class="input-group-admin__addon">
                                        <a href="<?= e(machine_url($product)) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ COLUMNA LATERAL ============ -->
        <div class="col-lg-4">

            <!-- Publicación -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-broadcast"></i> Publicación</h2></div>
                <div class="card-admin__body">
                    <div class="mb-3">
                        <label class="form-label" for="availability">Estado</label>
                        <select class="form-select" id="availability" name="availability">
                            <?php foreach (['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'] as $status): ?>
                                <option value="<?= $status ?>" <?= $val('availability', 'disponible') === $status ? 'selected' : '' ?>>
                                    <?= e(availability_badge($status)['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="location">Ubicación</label>
                        <input type="text" class="form-control" id="location" name="location" maxlength="160"
                               placeholder="Ej: Depósito Central" value="<?= e($val('location')) ?>">
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Publicada en el sitio</strong><small>Si está apagada no aparece en el catálogo</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="active" value="1"
                                   <?= !$isEdit || (int) $product['active'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Destacada</strong><small>Aparece primero y en la home</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="featured" value="1"
                                   <?= $isEdit && (int) $product['featured'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Marcar como nueva</strong><small>Muestra la etiqueta “Nuevo”</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_new" value="1"
                                   <?= $isEdit && (int) $product['is_new'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Precio -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-cash-stack"></i> Precio</h2></div>
                <div class="card-admin__body">
                    <?php if ($canSeeCost): ?>
                        <div class="price-calc" id="priceCalc">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label" for="cost_price">Costo</label>
                                    <input type="text" class="form-control" id="cost_price" name="cost_price"
                                           value="<?= e($isEdit ? number_format((float) $product['cost_price'], 2, '.', '') : '0') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="profit_percent">Ganancia (%)</label>
                                    <input type="text" class="form-control" id="profit_percent" name="profit_percent"
                                           value="<?= e($isEdit ? number_format((float) $product['profit_percent'], 2, '.', '') : '25') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="final_price">Precio final</label>
                                    <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                           value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>">
                                    <p class="form-hint">Si escribís el precio final, la ganancia se recalcula sola.</p>
                                </div>
                            </div>

                            <div class="price-calc__result">
                                <div class="price-calc__cell">
                                    <span>Ganancia $</span><strong id="outProfitAmount">—</strong>
                                </div>
                                <div class="price-calc__cell">
                                    <span>Margen s/venta</span><strong id="outMargin">—</strong>
                                </div>
                                <div class="price-calc__cell price-calc__cell--main">
                                    <span>Precio final</span><strong id="outFinalPrice">—</strong>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label" for="final_price">Precio final</label>
                            <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                   value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>"
                                   <?= can('prices.edit') ? '' : 'readonly' ?>>
                            <p class="form-hint">Tu rol no tiene acceso al costo ni a la ganancia.</p>
                        </div>
                    <?php endif; ?>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <label class="form-label" for="currency">Moneda</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php foreach ($currencies as $code => $label): ?>
                                    <option value="<?= e($code) ?>" <?= $val('currency', 'ARS') === $code ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="offer_price">Precio de oferta</label>
                            <input type="text" class="form-control" id="offer_price" name="offer_price"
                                   value="<?= e($isEdit && $product['offer_price'] !== null ? number_format((float) $product['offer_price'], 2, '.', '') : '') ?>">
                        </div>
                    </div>

                    <div class="form-switch-row mt-3">
                        <div><strong>Oferta activa</strong><small>Muestra el precio tachado</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_offer" value="1"
                                   <?= $isEdit && (int) $product['is_offer'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Mostrar precio al público</strong><small>Si está apagado dice “Consultar”</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="price_visible" value="1"
                                   <?= !$isEdit || (int) $product['price_visible'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <?php if ($isEdit && can('prices.history')): ?>
                        <a href="<?= admin_url('precios/' . $id . '/historial') ?>" class="btn btn-ghost btn-sm w-100 mt-2">
                            <i class="bi bi-clock-history"></i> Ver historial de precios
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Etiquetas -->
            <?php if (!empty($tags)): ?>
                <div class="card-admin">
                    <div class="card-admin__head"><h2><i class="bi bi-tags"></i> Etiquetas</h2></div>
                    <div class="card-admin__body">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <label class="filter-check">
                                    <input type="checkbox" name="tags[]" value="<?= (int) $tag['id'] ?>"
                                        <?= in_array((int) $tag['id'], $selectedTags, true) ? 'checked' : '' ?>>
                                    <span class="tag tag--<?= e($tag['color']) ?>"><?= e($tag['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Imágenes -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-images"></i> Galería</h2></div>
                <div class="card-admin__body">
                    <div class="mb-3">
                        <label class="form-label" for="zona">Zona de las fotos que vas a subir</label>
                        <select class="form-select" id="zona" name="zona">
                            <?php foreach ($imageZones as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dropzone" data-input="imagenes">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span data-file-label data-default="Arrastrá las imágenes o hacé clic para elegirlas">
                            Arrastrá las imágenes o hacé clic para elegirlas
                        </span>
                        <p class="form-hint mt-2 mb-0">JPG, PNG o WebP · máx. 8 MB por archivo</p>
                    </div>
                    <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple hidden>

                    <?php if ($isEdit && !empty($images)): ?>
                        <div class="img-grid mt-3">
                            <?php foreach ($images as $image): ?>
                                <div class="img-tile <?= (int) $image['is_main'] === 1 ? 'is-main' : '' ?>">
                                    <img src="<?= e(upload_url($image['thumb_path'] ?? $image['path'])) ?>" alt="" loading="lazy">
                                    <?php if ((int) $image['is_main'] === 1): ?>
                                        <span class="img-tile__badge">Principal</span>
                                    <?php endif; ?>
                                    <div class="img-tile__actions">
                                        <?php if ((int) $image['is_main'] !== 1): ?>
                                            <button type="button" title="Marcar como principal"
                                                    onclick="document.getElementById('mainImg<?= (int) $image['id'] ?>').submit()">
                                                <i class="bi bi-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" title="Eliminar"
                                                onclick="if(confirm('¿Eliminar esta imagen?')) document.getElementById('delImg<?= (int) $image['id'] ?>').submit()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($isEdit): ?>
                        <p class="form-hint mt-3 mb-0">Todavía no hay imágenes cargadas.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documentación -->
            <?php if ($isEdit): ?>
                <div class="card-admin">
                    <div class="card-admin__head"><h2><i class="bi bi-file-earmark-pdf"></i> Documentación</h2></div>
                    <div class="card-admin__body">
                        <?php if (!empty($documents)): ?>
                            <?php foreach ($documents as $doc): ?>
                                <div class="doc-row">
                                    <span class="doc-row__icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                    <span class="flex-grow-1">
                                        <span class="doc-row__name d-block"><?= e($doc['title']) ?></span>
                                        <span class="doc-row__meta">
                                            <?= e(ucfirst(str_replace('_', ' ', (string) $doc['doc_type']))) ?>
                                            <?= (int) $doc['public'] === 1 ? '· público' : '· interno' ?>
                                        </span>
                                    </span>
                                    <a href="<?= e(upload_url($doc['path'])) ?>" target="_blank" class="btn-icon"><i class="bi bi-eye"></i></a>
                                    <button type="button" class="btn-icon btn-icon--danger"
                                            onclick="if(confirm('¿Eliminar el documento?')) document.getElementById('delDoc<?= (int) $doc['id'] ?>').submit()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="form-hint">Todavía no hay documentos cargados.</p>
                        <?php endif; ?>

                        <p class="form-hint mt-2 mb-0">
                            Los documentos se suben desde el formulario de abajo (fuera de este formulario principal).
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-sticky-actions">
        <a href="<?= admin_url('maquinaria') ?>" class="btn btn-ghost">Cancelar</a>
        <?php if ($isEdit): ?>
            <a href="<?= e(machine_url($product)) ?>" target="_blank" class="btn btn-outline-accent">
                <i class="bi bi-eye"></i> Ver en el sitio
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear máquina' ?>
        </button>
    </div>
</form>

<!-- Formularios auxiliares (fuera del formulario principal) -->
<?php if ($isEdit): ?>
    <?php foreach ($images as $image): ?>
        <form id="mainImg<?= (int) $image['id'] ?>" method="post"
              action="<?= admin_url('maquinaria/' . $id . '/imagenes/' . (int) $image['id'] . '/principal') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
        <form id="delImg<?= (int) $image['id'] ?>" method="post"
              action="<?= admin_url('maquinaria/' . $id . '/imagenes/' . (int) $image['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <?php foreach ($documents as $doc): ?>
        <form id="delDoc<?= (int) $doc['id'] ?>" method="post"
              action="<?= admin_url('maquinaria/' . $id . '/documentos/' . (int) $doc['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <div class="card-admin">
        <div class="card-admin__head"><h2><i class="bi bi-upload"></i> Subir documentación</h2></div>
        <div class="card-admin__body">
            <form method="post" action="<?= admin_url('maquinaria/' . $id . '/documentos') ?>"
                  enctype="multipart/form-data" class="row g-3 align-items-end">
                <?= csrf_field() ?>

                <div class="col-md-4">
                    <label class="form-label" for="doc-titulo">Título</label>
                    <input type="text" class="form-control" id="doc-titulo" name="titulo" maxlength="180"
                           placeholder="Ej: Manual de operación">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="doc-tipo">Tipo</label>
                    <select class="form-select" id="doc-tipo" name="tipo">
                        <option value="manual">Manual</option>
                        <option value="ficha_tecnica">Ficha técnica</option>
                        <option value="certificado">Certificado</option>
                        <option value="mantenimiento">Manual de mantenimiento</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="doc-file">Archivo</label>
                    <input type="file" class="form-control" id="doc-file" name="documento"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" required>
                </div>
                <div class="col-md-2">
                    <label class="filter-check mb-2">
                        <input type="checkbox" name="publico" value="1" checked> Público
                    </label>
                    <button type="submit" class="btn btn-dark-2 w-100"><i class="bi bi-upload"></i> Subir</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
