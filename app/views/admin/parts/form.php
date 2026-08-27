<?php
/**
 * ARCHIVO: app/views/admin/parts/form.php
 * Alta y edición de repuestos.
 */

$id     = $isEdit ? (int) $product['id'] : 0;
$action = $isEdit ? admin_url('repuestos/' . $id) : admin_url('repuestos');

$val = static function (string $key, mixed $default = '') use ($isEdit, $product) {
    $old = old($key, null);
    if ($old !== null && $old !== '') {
        return $old;
    }
    return $isEdit ? ($product[$key] ?? $default) : $default;
};

$codeTypes = [
    'oem'         => 'Código OEM',
    'fabricante'  => 'Fabricante',
    'alternativo' => 'Alternativo',
    'cruzado'     => 'Equivalencia',
];
?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- ============ COLUMNA PRINCIPAL ============ -->
        <div class="col-lg-8">

            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-info-circle"></i> Datos generales</h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="code">Código interno *</label>
                            <input type="text" class="form-control text-mono <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                   id="code" name="code" required maxlength="60" value="<?= e($val('code', $nextCode ?? '')) ?>">
                            <span class="form-error"><?= e($errors['code'] ?? '') ?></span>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="name">Nombre *</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   id="name" name="name" required maxlength="200"
                                   placeholder="Ej: Filtro de aceite motor Toyota serie 8" value="<?= e($val('name')) ?>">
                            <span class="form-error"><?= e($errors['name'] ?? '') ?></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category_id">Categoría</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (int) $val('category_id') === (int) $category['id'] ? 'selected' : '' ?>>
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
                                    <option value="<?= (int) $brand['id'] ?>" <?= (int) $val('brand_id') === (int) $brand['id'] ? 'selected' : '' ?>>
                                        <?= e($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="origin">Origen</label>
                            <select class="form-select" id="origin" name="origin">
                                <?php foreach ($origins as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $val('origin', 'alternativo') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="short_description">Descripción corta</label>
                            <input type="text" class="form-control" id="short_description" name="short_description"
                                   maxlength="400" value="<?= e($val('short_description')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Descripción completa</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      maxlength="20000"><?= e($val('description')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Códigos -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-upc-scan"></i> Códigos</h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="oem_code">Código OEM</label>
                            <input type="text" class="form-control text-mono" id="oem_code" name="oem_code"
                                   maxlength="80" value="<?= e($val('oem_code')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="manufacturer_code">Código de fabricante</label>
                            <input type="text" class="form-control text-mono" id="manufacturer_code" name="manufacturer_code"
                                   maxlength="80" value="<?= e($val('manufacturer_code')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="manufacturer">Fabricante</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer"
                                   maxlength="120" value="<?= e($val('manufacturer')) ?>">
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                        <hr class="my-4">
                        <h3 class="form-section__title"><i class="bi bi-plus-circle"></i> Códigos alternativos y equivalencias</h3>

                        <?php if (!empty($codes)): ?>
                            <div class="code-list mb-3">
                                <?php foreach ($codes as $code): ?>
                                    <div class="code-row">
                                        <span class="code-row__type"><?= e($codeTypes[$code['code_type']] ?? $code['code_type']) ?></span>
                                        <span class="code-row__value"><?= e($code['code']) ?></span>
                                        <?php if (!empty($code['note'])): ?>
                                            <small class="text-muted-2"><?= e($code['note']) ?></small>
                                        <?php endif; ?>
                                        <button type="button" class="btn-icon btn-icon--danger ms-auto"
                                                onclick="if(confirm('¿Eliminar el código?')) document.getElementById('delCode<?= (int) $code['id'] ?>').submit()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="form-hint">Todavía no cargaste códigos alternativos.</p>
                        <?php endif; ?>

                        <p class="form-hint mb-0">
                            Los códigos alternativos se agregan desde el bloque que está más abajo
                            (fuera de este formulario principal).
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Compatibilidad -->
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-diagram-3-fill"></i> Compatibilidad</h2>
                </div>
                <div class="card-admin__body">

                    <h3 class="form-section__title"><i class="bi bi-truck-front"></i> Máquinas del catálogo</h3>
                    <p class="form-hint mb-2">
                        Vinculá este repuesto con las máquinas que tenés publicadas. Aparece en ambas fichas.
                    </p>

                    <input type="search" class="form-control mb-2" placeholder="Filtrar máquinas…" data-picker-search="#machinesPicker">

                    <div class="picker mb-4" id="machinesPicker">
                        <?php foreach ($availableMachines as $machine): ?>
                            <label class="picker__item">
                                <input type="checkbox" name="machines[]" value="<?= (int) $machine['id'] ?>"
                                    <?= in_array((int) $machine['id'], $selectedMachines, true) ? 'checked' : '' ?>>
                                <span class="flex-grow-1">
                                    <?= e($machine['name']) ?>
                                    <code class="d-block"><?= e($machine['code']) ?><?= $machine['model'] ? ' · ' . e($machine['model']) : '' ?></code>
                                </span>
                            </label>
                        <?php endforeach; ?>

                        <?php if (empty($availableMachines)): ?>
                            <div class="p-3 text-muted-2">Todavía no hay máquinas cargadas.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEdit): ?>
                        <h3 class="form-section__title"><i class="bi bi-list-check"></i> Compatibilidad por marca y modelo</h3>
                        <p class="form-hint mb-2">
                            Sirve aunque la máquina no esté publicada en el catálogo. Es lo que hace que el cliente
                            encuentre el repuesto escribiendo, por ejemplo, <code>8FG25</code>.
                        </p>

                        <?php if (!empty($compatibility)): ?>
                            <div class="compat-list mb-3">
                                <?php foreach ($compatibility as $compat): ?>
                                    <span class="compat-item">
                                        <i class="bi bi-truck-front-fill"></i>
                                        <?= e(trim(($compat['brand_name'] ?? '') . ' ' . $compat['model'])) ?>
                                        <?php if (!empty($compat['year_from'])): ?>
                                            <small>(<?= (int) $compat['year_from'] ?><?= $compat['year_to'] ? '–' . (int) $compat['year_to'] : '' ?>)</small>
                                        <?php endif; ?>
                                        <button type="button" class="btn-icon btn-icon--danger ms-1" style="width:26px;height:26px"
                                                onclick="if(confirm('¿Quitar la compatibilidad?')) document.getElementById('delCompat<?= (int) $compat['id'] ?>').submit()">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="form-hint">Todavía no cargaste compatibilidades.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="form-hint">
                            Una vez creado el repuesto vas a poder cargar la compatibilidad por marca y modelo.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Características -->
            <?php if (!empty($features)): ?>
                <div class="card-admin">
                    <div class="card-admin__head"><h2><i class="bi bi-sliders"></i> Especificaciones</h2></div>
                    <div class="card-admin__body">
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
                                    <input type="text" class="form-control" id="feat-<?= (int) $feature['id'] ?>"
                                           name="features[<?= (int) $feature['id'] ?>]" value="<?= e($current) ?>" maxlength="255">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SEO -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-search"></i> SEO</h2></div>
                <div class="card-admin__body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="meta_title">Título SEO</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title"
                                   maxlength="180" value="<?= e($val('meta_title')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="meta_description">Meta descripción</label>
                            <textarea class="form-control" id="meta_description" name="meta_description"
                                      rows="2" maxlength="300"><?= e($val('meta_description')) ?></textarea>
                        </div>
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
                            <?php foreach (['disponible', 'consultar', 'reservada', 'vendida'] as $status): ?>
                                <option value="<?= $status ?>" <?= $val('availability', 'disponible') === $status ? 'selected' : '' ?>>
                                    <?= e(availability_badge($status)['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Publicado en el sitio</strong></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="active" value="1"
                                   <?= !$isEdit || (int) $product['active'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Destacado</strong><small>Aparece en la home</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="featured" value="1"
                                   <?= $isEdit && (int) $product['featured'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Nuevo</strong></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_new" value="1"
                                   <?= $isEdit && (int) $product['is_new'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock -->
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-box-seam"></i> Stock y ubicación</h2></div>
                <div class="card-admin__body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="stock">Stock actual</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0"
                                   value="<?= e($isEdit ? (int) $product['stock'] : 0) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="stock_min">Stock mínimo</label>
                            <input type="number" class="form-control" id="stock_min" name="stock_min" min="0"
                                   value="<?= e($isEdit ? (int) $product['stock_min'] : 1) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="stock_reason">Motivo del ajuste</label>
                            <input type="text" class="form-control" id="stock_reason" name="stock_reason"
                                   maxlength="255" placeholder="Se registra si cambiás el stock">
                        </div>
                    </div>

                    <div class="form-switch-row mt-3">
                        <div><strong>Controlar stock</strong><small>Si está apagado dice “Consultar disponibilidad”</small></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="track_stock" value="1"
                                   <?= !$isEdit || (int) $product['track_stock'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label" for="warehouse_id">Depósito</label>
                            <select class="form-select" id="warehouse_id" name="warehouse_id">
                                <option value="">Sin definir</option>
                                <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?= (int) $warehouse['id'] ?>" <?= (int) $val('warehouse_id') === (int) $warehouse['id'] ? 'selected' : '' ?>>
                                        <?= e($warehouse['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="sector">Sector</label>
                            <input type="text" class="form-control" id="sector" name="sector" maxlength="60" value="<?= e($val('sector')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="shelf">Estantería</label>
                            <input type="text" class="form-control" id="shelf" name="shelf" maxlength="60" value="<?= e($val('shelf')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="position">Posición</label>
                            <input type="text" class="form-control" id="position" name="position" maxlength="60" value="<?= e($val('position')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="lead_time_days">Plazo (días)</label>
                            <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" min="0"
                                   value="<?= e($val('lead_time_days')) ?>">
                        </div>
                    </div>

                    <p class="form-hint mt-2">La ubicación es información interna: no se muestra al público.</p>

                    <?php if ($isEdit && can('stock.view')): ?>
                        <a href="<?= admin_url('stock/' . $id . '/historial') ?>" class="btn btn-ghost btn-sm w-100 mt-2">
                            <i class="bi bi-clock-history"></i> Ver historial de stock
                        </a>
                    <?php endif; ?>
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
                                           value="<?= e($isEdit ? number_format((float) $product['profit_percent'], 2, '.', '') : '55') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="final_price">Precio final</label>
                                    <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                           value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>">
                                </div>
                            </div>

                            <div class="price-calc__result">
                                <div class="price-calc__cell"><span>Ganancia $</span><strong id="outProfitAmount">—</strong></div>
                                <div class="price-calc__cell"><span>Margen</span><strong id="outMargin">—</strong></div>
                                <div class="price-calc__cell price-calc__cell--main"><span>Final</span><strong id="outFinalPrice">—</strong></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <label class="form-label" for="final_price">Precio final</label>
                        <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                               value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>"
                               <?= can('prices.edit') ? '' : 'readonly' ?>>
                        <p class="form-hint">Tu rol no tiene acceso al costo ni a la ganancia.</p>
                    <?php endif; ?>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <label class="form-label" for="currency">Moneda</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php foreach ($currencies as $code => $label): ?>
                                    <option value="<?= e($code) ?>" <?= $val('currency', 'ARS') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="unit">Unidad de venta</label>
                            <select class="form-select" id="unit" name="unit">
                                <?php foreach ($units as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $val('unit', 'unidad') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="offer_price">Precio de oferta</label>
                            <input type="text" class="form-control" id="offer_price" name="offer_price"
                                   value="<?= e($isEdit && $product['offer_price'] !== null ? number_format((float) $product['offer_price'], 2, '.', '') : '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="weight_kg">Peso (kg)</label>
                            <input type="text" class="form-control" id="weight_kg" name="weight_kg" value="<?= e($val('weight_kg')) ?>">
                        </div>
                    </div>

                    <div class="form-switch-row mt-3">
                        <div><strong>Oferta activa</strong></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_offer" value="1"
                                   <?= $isEdit && (int) $product['is_offer'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="form-switch-row">
                        <div><strong>Mostrar precio al público</strong></div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="price_visible" value="1"
                                   <?= !$isEdit || (int) $product['price_visible'] === 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
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
                <div class="card-admin__head"><h2><i class="bi bi-images"></i> Imágenes</h2></div>
                <div class="card-admin__body">
                    <div class="dropzone" data-input="imagenes">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span data-file-label data-default="Arrastrá las imágenes o hacé clic">Arrastrá las imágenes o hacé clic</span>
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
                                        <button type="button" title="Eliminar"
                                                onclick="if(confirm('¿Eliminar esta imagen?')) document.getElementById('delImg<?= (int) $image['id'] ?>').submit()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-sticky-actions">
        <a href="<?= admin_url('repuestos') ?>" class="btn btn-ghost">Cancelar</a>
        <?php if ($isEdit): ?>
            <a href="<?= e(part_url($product)) ?>" target="_blank" class="btn btn-outline-accent">
                <i class="bi bi-eye"></i> Ver en el sitio
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear repuesto' ?>
        </button>
    </div>
</form>

<?php if ($isEdit): ?>
    <!-- Formularios auxiliares -->
    <?php foreach ($images as $image): ?>
        <form id="delImg<?= (int) $image['id'] ?>" method="post"
              action="<?= admin_url('repuestos/' . $id . '/imagenes/' . (int) $image['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <?php foreach ($codes as $code): ?>
        <form id="delCode<?= (int) $code['id'] ?>" method="post"
              action="<?= admin_url('repuestos/' . $id . '/codigos/' . (int) $code['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <?php foreach ($compatibility as $compat): ?>
        <form id="delCompat<?= (int) $compat['id'] ?>" method="post"
              action="<?= admin_url('repuestos/' . $id . '/compatibilidad/' . (int) $compat['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Agregar código alternativo</h2></div>
                <div class="card-admin__body">
                    <form method="post" action="<?= admin_url('repuestos/' . $id . '/codigos') ?>" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-md-4">
                            <label class="form-label" for="nc-type">Tipo</label>
                            <select class="form-select" id="nc-type" name="code_type">
                                <?php foreach ($codeTypes as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="nc-code">Código</label>
                            <input type="text" class="form-control text-mono" id="nc-code" name="code" required maxlength="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="nc-note">Nota</label>
                            <input type="text" class="form-control" id="nc-note" name="note" maxlength="160" placeholder="Ej: Equivalente Mann">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-dark-2 btn-sm"><i class="bi bi-plus-lg"></i> Agregar código</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-admin">
                <div class="card-admin__head"><h2><i class="bi bi-plus-circle"></i> Agregar compatibilidad</h2></div>
                <div class="card-admin__body">
                    <form method="post" action="<?= admin_url('repuestos/' . $id . '/compatibilidad') ?>" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-md-5">
                            <label class="form-label" for="ncomp-brand">Marca</label>
                            <select class="form-select" id="ncomp-brand" name="brand_id">
                                <option value="">Sin marca</option>
                                <?php foreach ($allBrands as $brand): ?>
                                    <option value="<?= (int) $brand['id'] ?>"><?= e($brand['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="ncomp-model">Modelo *</label>
                            <input type="text" class="form-control" id="ncomp-model" name="model" required
                                   maxlength="120" placeholder="Ej: 8FG25">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="ncomp-from">Desde</label>
                            <input type="number" class="form-control" id="ncomp-from" name="year_from" min="1950" max="2100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="ncomp-to">Hasta</label>
                            <input type="number" class="form-control" id="ncomp-to" name="year_to" min="1950" max="2100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ncomp-note">Nota</label>
                            <input type="text" class="form-control" id="ncomp-note" name="note" maxlength="200">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-dark-2 btn-sm"><i class="bi bi-plus-lg"></i> Agregar compatibilidad</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
