<?php
/**
 * ARCHIVO: app/views/admin/machines/form.php
 * Alta y edición de maquinaria — formulario con pestañas.
 *
 * Pestañas: General · Precio · Ficha técnica · Fotos y video · Documentos
 * Barra lateral fija (todas las pestañas): Publicación + Etiquetas
 */

$id     = $isEdit ? (int) $product['id'] : 0;
$action = $isEdit ? admin_url('maquinaria/' . $id) : admin_url('maquinaria');

/** Valor actual del campo (edición) o el enviado antes de un error. */
$val = static function (string $key, mixed $default = '') use ($isEdit, $product) {
    $old = old($key, null);
    if ($old !== null && $old !== '') {
        return $old;
    }
    return $isEdit ? ($product[$key] ?? $default) : $default;
};

$ab = $isEdit ? availability_badge((string) $product['availability']) : null;
?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="machineForm">
    <?= csrf_field() ?>

    <div class="pform">

        <!-- ================= ENCABEZADO ================= -->
        <header class="pform-header">
            <nav class="breadcrumbs">
                <a href="<?= admin_url('maquinaria') ?>">Maquinaria</a><span><?= $isEdit ? 'Editar' : 'Nueva' ?></span>
            </nav>
            <div class="pform-header__row">
                <h1 class="pform-title"><?= $isEdit ? e($product['name']) : 'Nueva máquina' ?></h1>
                <?php if ($isEdit): ?>
                    <span class="pform-code"><?= e($product['code']) ?></span>
                    <span class="chip chip--<?= $ab['class'] === 'ok' ? 'ok' : ($ab['class'] === 'off' ? 'danger' : 'warn') ?>">
                        <?= e($ab['label']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- ================= PESTAÑAS ================= -->
        <nav class="form-tabs" role="tablist" aria-label="Secciones del producto">
            <button type="button" class="form-tab" data-tab="general" role="tab">General</button>
            <button type="button" class="form-tab" data-tab="precio"  role="tab">Precio</button>
            <button type="button" class="form-tab" data-tab="ficha"   role="tab">Ficha técnica</button>
            <button type="button" class="form-tab" data-tab="fotos"   role="tab">Fotos y video</button>
            <?php if ($isEdit): ?>
                <button type="button" class="form-tab" data-tab="docs" role="tab">Documentos</button>
            <?php endif; ?>
        </nav>

        <div class="pform-body">
            <div class="pform-main">

                <!-- ========== GENERAL ========== -->
                <section class="form-tabpanel" data-panel="general" role="tabpanel">
                    <div class="pform-card">
                        <h2 class="pform-card__title">Identificación</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="code">Código interno *</label>
                                <input type="text" class="form-control text-mono <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                       id="code" name="code" required maxlength="60"
                                       value="<?= e($val('code', $nextCode ?? '')) ?>">
                                <span class="form-error"><?= e($errors['code'] ?? '') ?></span>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="name">Nombre público *</label>
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
                        </div>
                    </div>

                    <div class="pform-card">
                        <h2 class="pform-card__title">Descripción</h2>
                        <div class="mb-3">
                            <label class="form-label" for="short_description">Resumen — se ve en el listado</label>
                            <input type="text" class="form-control" id="short_description" name="short_description"
                                   maxlength="400" placeholder="Una línea con lo más importante"
                                   value="<?= e($val('short_description')) ?>">
                            <span class="form-hint" data-counter></span>
                        </div>
                        <div>
                            <label class="form-label" for="description">Descripción completa</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      data-autogrow maxlength="20000"><?= e($val('description')) ?></textarea>
                            <p class="form-hint">
                                Se admiten etiquetas básicas de formato (negrita, listas, párrafos).
                                El resto se limpia automáticamente por seguridad.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========== PRECIO ========== -->
                <section class="form-tabpanel" data-panel="precio" role="tabpanel" hidden>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Precio</h2>
                        <?php if ($canSeeCost): ?>
                            <div class="price-calc" id="priceCalc">
                                <div class="row g-2">
                                    <div class="col-md-4 col-6">
                                        <label class="form-label" for="currency">Moneda</label>
                                        <select class="form-select" id="currency" name="currency">
                                            <?php foreach ($currencies as $code => $label): ?>
                                                <option value="<?= e($code) ?>" <?= $val('currency', 'ARS') === $code ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <label class="form-label" for="cost_price">Costo</label>
                                        <input type="text" class="form-control" id="cost_price" name="cost_price"
                                               value="<?= e($isEdit ? number_format((float) $product['cost_price'], 2, '.', '') : '0') ?>">
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <label class="form-label" for="profit_percent">Ganancia (%)</label>
                                        <input type="text" class="form-control" id="profit_percent" name="profit_percent"
                                               value="<?= e($isEdit ? number_format((float) $product['profit_percent'], 2, '.', '') : '25') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="final_price">Precio final</label>
                                        <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                               value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>">
                                    </div>
                                </div>

                                <div class="price-calc__result">
                                    <div class="price-calc__cell"><span>Ganancia $</span><strong id="outProfitAmount">—</strong></div>
                                    <div class="price-calc__cell"><span>Margen s/venta</span><strong id="outMargin">—</strong></div>
                                    <div class="price-calc__cell price-calc__cell--main"><span>Precio público</span><strong id="outFinalPrice">—</strong></div>
                                </div>
                                <p class="form-hint mt-2 mb-0">Escribí costo + ganancia, o directo el precio final: lo otro se recalcula solo.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="final_price">Precio final</label>
                                    <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                           value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>"
                                           <?= can('prices.edit') ? '' : 'readonly' ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="currency">Moneda</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <?php foreach ($currencies as $code => $label): ?>
                                            <option value="<?= e($code) ?>" <?= $val('currency', 'ARS') === $code ? 'selected' : '' ?>>
                                                <?= e($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <p class="form-hint">Tu rol no tiene acceso al costo ni a la ganancia.</p>
                        <?php endif; ?>
                    </div>

                    <div class="pform-card">
                        <h2 class="pform-card__title">Oferta y visibilidad del precio</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-switch-row">
                                    <div><strong>Oferta activa</strong><small>Muestra el precio tachado</small></div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="is_offer" value="1"
                                               <?= $isEdit && (int) $product['is_offer'] === 1 ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-switch-row">
                                    <div><strong>Mostrar precio al público</strong><small>Si está apagado dice “Consultar”</small></div>
                                    <div class="form-check form-switch m-0">
                                        <input type="hidden" name="price_visible" value="0">
                                        <input class="form-check-input" type="checkbox" name="price_visible" value="1"
                                               <?= !$isEdit || (int) $product['price_visible'] === 1 ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="offer_price">Precio de oferta</label>
                                <input type="text" class="form-control" id="offer_price" name="offer_price"
                                       value="<?= e($isEdit && $product['offer_price'] !== null ? number_format((float) $product['offer_price'], 2, '.', '') : '') ?>">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========== FICHA TÉCNICA ========== -->
                <section class="form-tabpanel" data-panel="ficha" role="tabpanel" hidden>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Estado del equipo</h2>
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

                    <div class="pform-card">
                        <h2 class="pform-card__title">Capacidades</h2>
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

                    <div class="pform-card">
                        <h2 class="pform-card__title">Dimensiones</h2>
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

                    <div class="pform-card">
                        <h2 class="pform-card__title">Motorización</h2>
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

                    <?php /*
                       Sección "Características" desactivada: repetía los mismos datos
                       que las secciones de arriba (Capacidad, Combustible, Motor, etc.).
                       La ficha técnica se carga solo con los campos fijos de acá.
                       Para reactivarla, volver a poner el bloque foreach ($features).
                    */ ?>

                    <div class="pform-card">
                        <h2 class="pform-card__title">Repuestos compatibles</h2>
                        <p class="form-hint mb-2">
                            Lo que marques acá se muestra en la ficha pública como “Repuestos compatibles”
                            y también aparece en la ficha del repuesto. (<?= count($selectedParts) ?> seleccionado/s)
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
                </section>

                <!-- ========== FOTOS Y VIDEO ========== -->
                <section class="form-tabpanel" data-panel="fotos" role="tabpanel" hidden>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Fotos</h2>
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
                                                        data-submit-form="mainImg<?= (int) $image['id'] ?>">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" title="Eliminar"
                                                    data-submit-form="delImg<?= (int) $image['id'] ?>"
                                                    data-confirm="¿Eliminar esta imagen?">
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

                    <div class="pform-card">
                        <h2 class="pform-card__title">Video</h2>
                        <label class="form-label" for="videos">URLs de YouTube o Vimeo (una por línea)</label>
                        <textarea class="form-control text-mono" id="videos" name="videos" rows="3"
                                  placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX"><?= e($videos ?? '') ?></textarea>
                        <p class="form-hint">Se muestran en la ficha pública como “Ver la máquina trabajando”.</p>

                        <?php if ($isEdit): ?>
                            <hr class="my-3">
                            <label class="form-label" for="video-file">Subir un video desde tu compu</label>
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-7">
                                    <input type="file" class="form-control" id="video-file" name="video"
                                           form="videoUploadForm" accept="video/mp4,video/webm,video/quicktime" required>
                                </div>
                                <div class="col-sm-5">
                                    <button type="submit" form="videoUploadForm" class="btn btn-dark-2 w-100">
                                        <i class="bi bi-upload"></i> Subir video
                                    </button>
                                </div>
                            </div>
                            <p class="form-hint">MP4, WebM o MOV · máx. 40 MB. Para videos largos, mejor subilo a YouTube y pegá el link arriba.</p>

                            <?php if (!empty($uploadedVideos)): ?>
                                <div class="mt-3 d-flex flex-column gap-2">
                                    <?php foreach ($uploadedVideos as $vid): ?>
                                        <div class="doc-row">
                                            <span class="doc-row__icon"><i class="bi bi-film"></i></span>
                                            <span class="flex-grow-1">
                                                <span class="doc-row__name d-block"><?= e($vid['title']) ?></span>
                                                <span class="doc-row__meta">Archivo subido</span>
                                            </span>
                                            <a href="<?= e(upload_url($vid['video_ref'])) ?>" target="_blank" class="btn-icon" title="Ver"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="btn-icon btn-icon--danger" title="Quitar"
                                                    data-submit-form="delVideo<?= (int) $vid['id'] ?>"
                                                    data-confirm="¿Eliminar este video?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- ========== DOCUMENTOS ========== -->
                <?php if ($isEdit): ?>
                    <section class="form-tabpanel" data-panel="docs" role="tabpanel" hidden>
                        <div class="pform-card">
                            <h2 class="pform-card__title">Documentación</h2>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label" for="doc-titulo">Título</label>
                                    <input type="text" class="form-control" id="doc-titulo" name="titulo" maxlength="180"
                                           form="docUploadForm" placeholder="Ej: Manual de operación">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="doc-tipo">Tipo</label>
                                    <select class="form-select" id="doc-tipo" name="tipo" form="docUploadForm">
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
                                           form="docUploadForm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="filter-check mb-2">
                                        <input type="checkbox" name="publico" value="1" form="docUploadForm" checked> Público
                                    </label>
                                    <button type="submit" form="docUploadForm" class="btn btn-dark-2 w-100">
                                        <i class="bi bi-upload"></i> Subir
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3">
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
                                            <a href="<?= e(upload_url($doc['path'])) ?>" target="_blank" class="btn-icon" title="Ver"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="btn-icon btn-icon--danger" title="Quitar"
                                                    data-submit-form="delDoc<?= (int) $doc['id'] ?>"
                                                    data-confirm="¿Eliminar el documento?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="form-hint mb-0">Todavía no hay documentos cargados.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

            </div><!-- /.pform-main -->

            <!-- ================= BARRA LATERAL FIJA ================= -->
            <aside class="pform-side">
                <div class="pform-card">
                    <h2 class="pform-card__title">Publicación</h2>
                    <div class="row g-2 mb-1">
                        <div class="col-6">
                            <label class="form-label" for="availability">Estado</label>
                            <select class="form-select" id="availability" name="availability">
                                <?php foreach (['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $val('availability', 'disponible') === $status ? 'selected' : '' ?>>
                                        <?= e(availability_badge($status)['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="location">Ubicación</label>
                            <input type="text" class="form-control" id="location" name="location" maxlength="160"
                                   placeholder="Ej: Depósito Central" value="<?= e($val('location')) ?>">
                        </div>
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

                <?php if (!empty($tags)): ?>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Etiquetas</h2>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <label class="filter-check">
                                    <input type="checkbox" name="tags[]" value="<?= (int) $tag['id'] ?>"
                                        <?= in_array((int) $tag['id'], $selectedTags, true) ? 'checked' : '' ?>>
                                    <span class="tag tag--<?= e($tag['color']) ?>"><?= e($tag['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="form-hint mt-2 mb-0">Tocá para activar. Se administran desde <a href="<?= admin_url('etiquetas') ?>">Etiquetas</a>.</p>
                    </div>
                <?php endif; ?>
            </aside>
        </div><!-- /.pform-body -->

        <!-- ================= PIE FIJO ================= -->
        <div class="pform-footer">
            <div class="pform-footer__meta">
                <?php if ($isEdit && !empty($product['updated_at'])): ?>
                    Último guardado <?= e(date_es((string) $product['updated_at'], true)) ?><?php if (!empty($product['updated_by_name'])): ?> por <?= e($product['updated_by_name']) ?><?php endif; ?>
                <?php else: ?>
                    Máquina nueva — todavía sin guardar
                <?php endif; ?>
            </div>
            <div class="pform-footer__actions">
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
        </div>

    </div><!-- /.pform -->
</form>

<!-- ================= FORMULARIOS AUXILIARES (fuera del principal) ================= -->
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

    <form id="docUploadForm" method="post"
          action="<?= admin_url('maquinaria/' . $id . '/documentos') ?>" enctype="multipart/form-data" class="d-none">
        <?= csrf_field() ?>
    </form>

    <form id="videoUploadForm" method="post"
          action="<?= admin_url('maquinaria/' . $id . '/videos') ?>" enctype="multipart/form-data" class="d-none">
        <?= csrf_field() ?>
    </form>
    <?php foreach ($uploadedVideos as $vid): ?>
        <form id="delVideo<?= (int) $vid['id'] ?>" method="post"
              action="<?= admin_url('maquinaria/' . $id . '/videos/' . (int) $vid['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
