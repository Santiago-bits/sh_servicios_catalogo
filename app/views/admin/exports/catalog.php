<?php
/**
 * ARCHIVO: app/views/admin/exports/catalog.php
 * Generador de catálogo PDF.
 */
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-file-earmark-pdf"></i> Generar catálogo PDF</h2></div>
            <div class="card-admin__body">
                <form method="post" action="<?= admin_url('catalogo-pdf') ?>" class="row g-3" target="_blank">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="cat-titulo">Título del catálogo</label>
                        <input type="text" class="form-control" id="cat-titulo" name="titulo"
                               maxlength="120" placeholder="Ej: Catálogo de autoelevadores 2026">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="cat-tipo">Tipo de producto</label>
                        <select class="form-select" id="cat-tipo" name="tipo">
                            <option value="machine">Maquinaria</option>
                            <option value="spare_part">Repuestos</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="cat-orden">Ordenar por</label>
                        <select class="form-select" id="cat-orden" name="orden">
                            <option value="az">Nombre A-Z</option>
                            <option value="destacados">Destacados primero</option>
                            <option value="precio_asc">Precio: menor a mayor</option>
                            <option value="precio_desc">Precio: mayor a menor</option>
                            <option value="nuevos">Más nuevos</option>
                            <option value="codigo">Código</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="cat-categoria">Categoría</label>
                        <select class="form-select" id="cat-categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'service') { continue; } ?>
                                <option value="<?= (int) $category['id'] ?>">
                                    <?= e($category['name']) ?>
                                    (<?= $category['type'] === 'machine' ? 'maquinaria' : 'repuestos' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="cat-marca">Marca</label>
                        <select class="form-select" id="cat-marca" name="marca">
                            <option value="">Todas</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= (int) $brand['id'] ?>"><?= e($brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="filter-check"><input type="checkbox" name="con_precios" value="1" checked> Incluir precios</label>
                        <label class="filter-check"><input type="checkbox" name="solo_destacados" value="1"> Sólo productos destacados</label>
                        <label class="filter-check"><input type="checkbox" name="solo_con_stock" value="1"> Sólo con stock disponible</label>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100 btn-lg">
                            <i class="bi bi-file-earmark-pdf"></i> Generar y descargar PDF
                        </button>
                        <p class="form-hint mt-2 mb-0">
                            Se incluyen hasta 200 productos por catálogo. El PDF se genera con el logo y los datos
                            de la empresa cargados en Configuración.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-lightbulb"></i> Qué incluye el PDF</h2></div>
            <div class="card-admin__body">
                <ul class="service-card__list">
                    <li>Encabezado con logo y datos de la empresa</li>
                    <li>Una ficha por producto con imagen principal</li>
                    <li>Código, marca, modelo, año y código OEM</li>
                    <li>Descripción corta y ficha técnica resumida</li>
                    <li>Precio destacado (opcional)</li>
                    <li>Pie con datos de contacto y numeración de páginas</li>
                </ul>

                <div class="alert alert-light border mt-3 mb-0">
                    <strong class="d-block mb-1"><i class="bi bi-info-circle"></i> Sin dependencias</strong>
                    <p class="mb-0 small text-muted-2">
                        El PDF se genera con un motor propio incluido en el proyecto
                        (<code>lib/Pdf.php</code>). No hace falta instalar Composer ni ninguna librería externa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
