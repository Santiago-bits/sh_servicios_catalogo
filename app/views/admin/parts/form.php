<?php
/**
 * ARCHIVO: app/views/admin/parts/form.php
 * Alta y edición de repuestos — formulario con pestañas.
 *
 * Pestañas: General · Precio · Fotos
 * Barra lateral fija: Publicación + Etiquetas
 *
 * Los datos técnicos (códigos OEM, compatibilidad, SEO) no se
 * editan desde acá.
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

$ab = $isEdit ? availability_badge((string) $product['availability']) : null;
?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="partForm">
    <?= csrf_field() ?>

    <div class="pform">

        <!-- ================= ENCABEZADO ================= -->
        <header class="pform-header">
            <nav class="breadcrumbs">
                <a href="<?= admin_url('repuestos') ?>">Repuestos</a><span><?= $isEdit ? 'Editar' : 'Nuevo' ?></span>
            </nav>
            <div class="pform-header__row">
                <h1 class="pform-title"><?= $isEdit ? e($product['name']) : 'Nuevo repuesto' ?></h1>
                <?php if ($isEdit): ?>
                    <span class="pform-code"><?= e($product['code']) ?></span>
                    <span class="chip chip--<?= $ab['class'] === 'ok' ? 'ok' : ($ab['class'] === 'off' ? 'danger' : 'warn') ?>">
                        <?= e($ab['label']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- ================= PESTAÑAS ================= -->
        <nav class="form-tabs" role="tablist" aria-label="Secciones del repuesto">
            <button type="button" class="form-tab" data-tab="general" role="tab">General</button>
            <button type="button" class="form-tab" data-tab="precio"  role="tab">Precio</button>
            <button type="button" class="form-tab" data-tab="fotos"   role="tab">Fotos y video</button>
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
                                       id="code" name="code" required maxlength="60" value="<?= e($val('code', $nextCode ?? '')) ?>">
                                <span class="form-error"><?= e($errors['code'] ?? '') ?></span>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="name">Nombre público *</label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                       id="name" name="name" required maxlength="200"
                                       placeholder="Ej: Filtro de aceite motor Toyota serie 8" value="<?= e($val('name')) ?>">
                                <span class="form-error"><?= e($errors['name'] ?? '') ?></span>
                            </div>
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label class="form-label" for="manufacturer">Marca</label>
                                <input type="text" class="form-control" id="manufacturer" name="manufacturer"
                                       maxlength="120" value="<?= e($val('manufacturer')) ?>"
                                       list="manufacturerList" placeholder="Ej: Mann Filter, Bosch, SKF…">
                                <?php $manufacturers = $manufacturers ?? []; ?>
                                <?php if ($manufacturers !== []): ?>
                                    <datalist id="manufacturerList">
                                        <?php foreach ($manufacturers as $m): ?>
                                            <option value="<?= e($m) ?>"></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                <?php endif; ?>
                                <p class="form-hint">Texto libre. No aparece en las marcas de la portada.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pform-card">
                        <h2 class="pform-card__title">Descripción</h2>
                        <div class="mb-3">
                            <label class="form-label" for="short_description">Resumen — se ve en el listado</label>
                            <input type="text" class="form-control" id="short_description" name="short_description"
                                   maxlength="400" value="<?= e($val('short_description')) ?>">
                        </div>
                        <div>
                            <label class="form-label" for="description">Descripción completa</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      data-autogrow maxlength="20000"><?= e($val('description')) ?></textarea>
                            <p class="form-hint">
                                Escribí normal: dejá una <strong>línea en blanco</strong> para separar párrafos.
                                Se respeta tal cual lo que escribís.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========== PRECIO ========== -->
                <section class="form-tabpanel" data-panel="precio" role="tabpanel" hidden>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Precio</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="final_price">Precio final</label>
                                <input type="text" class="form-control fw-bold" id="final_price" name="final_price"
                                       value="<?= e($isEdit ? number_format((float) $product['final_price'], 2, '.', '') : '0') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="currency">Moneda</label>
                                <select class="form-select" id="currency" name="currency">
                                    <?php foreach ($currencies as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $val('currency', 'ARS') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-switch-row">
                                    <div><strong>Mostrar precio al público</strong><small>Si está apagado dice “Consultar”</small></div>
                                    <div class="form-check form-switch m-0">
                                        <input type="hidden" name="price_visible" value="0">
                                        <input class="form-check-input" type="checkbox" name="price_visible" value="1"
                                               <?= !$isEdit || (int) $product['price_visible'] === 1 ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========== FOTOS ========== -->
                <section class="form-tabpanel" data-panel="fotos" role="tabpanel" hidden>
                    <div class="pform-card">
                        <h2 class="pform-card__title">Fotos</h2>
                        <div class="dropzone" data-input="imagenes">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <span data-file-label data-default="Arrastrá las imágenes o hacé clic">Arrastrá las imágenes o hacé clic</span>
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
                        <p class="form-hint">Se muestran en la ficha pública del repuesto.</p>

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

            </div><!-- /.pform-main -->

            <!-- ================= BARRA LATERAL FIJA ================= -->
            <aside class="pform-side">
                <div class="pform-card">
                    <h2 class="pform-card__title">Publicación</h2>
                    <div class="mb-2">
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
                    Repuesto nuevo — todavía sin guardar
                <?php endif; ?>
            </div>
            <div class="pform-footer__actions">
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
        </div>

    </div><!-- /.pform -->
</form>

<?php if ($isEdit): ?>
    <?php foreach ($images as $image): ?>
        <form id="delImg<?= (int) $image['id'] ?>" method="post"
              action="<?= admin_url('repuestos/' . $id . '/imagenes/' . (int) $image['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <form id="videoUploadForm" method="post"
          action="<?= admin_url('repuestos/' . $id . '/videos') ?>" enctype="multipart/form-data" class="d-none">
        <?= csrf_field() ?>
    </form>
    <?php foreach ($uploadedVideos as $vid): ?>
        <form id="delVideo<?= (int) $vid['id'] ?>" method="post"
              action="<?= admin_url('repuestos/' . $id . '/videos/' . (int) $vid['id'] . '/eliminar') ?>" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
