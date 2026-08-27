<?php
/**
 * ARCHIVO: app/views/admin/imports/index.php
 */
?>
<div class="row g-3">
    <div class="col-lg-<?= $preview ? '5' : '7' ?>">
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-upload"></i> Importar desde CSV</h2></div>
            <div class="card-admin__body">
                <ol class="text-muted-2 small mb-4" style="padding-left:18px">
                    <li>Descargá la plantilla del tipo de producto que querés importar.</li>
                    <li>Completala en Excel y guardala como <strong>CSV (delimitado por punto y coma)</strong>.</li>
                    <li>Subila acá: primero vas a ver una previsualización con los errores detectados.</li>
                    <li>Recién después confirmás la importación.</li>
                </ol>

                <form method="post" action="<?= admin_url('importar/previsualizar') ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-12">
                        <label class="form-label" for="imp-tipo">Tipo de datos</label>
                        <select class="form-select" id="imp-tipo" name="tipo">
                            <option value="maquinaria">Maquinaria</option>
                            <option value="repuestos">Repuestos</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="imp-file">Archivo CSV</label>
                        <input type="file" class="form-control" id="imp-file" name="archivo" accept=".csv,.txt" required>
                        <p class="form-hint">Máximo 6 MB. Separador: punto y coma o coma (se detecta solo).</p>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-search"></i> Previsualizar y validar
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= admin_url('importar/plantilla/maquinaria') ?>" class="btn btn-ghost btn-sm">
                        <i class="bi bi-download"></i> Plantilla de maquinaria
                    </a>
                    <a href="<?= admin_url('importar/plantilla/repuestos') ?>" class="btn btn-ghost btn-sm">
                        <i class="bi bi-download"></i> Plantilla de repuestos
                    </a>
                </div>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-list-columns"></i> Columnas de las plantillas</h2></div>
            <div class="card-admin__body">
                <h3 class="form-section__title">Maquinaria</h3>
                <div class="d-flex flex-wrap gap-1 mb-4">
                    <?php foreach ($machineColumns as $column): ?>
                        <code class="chip chip--neutral"><?= e($column) ?></code>
                    <?php endforeach; ?>
                </div>

                <h3 class="form-section__title">Repuestos</h3>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($partColumns as $column): ?>
                        <code class="chip chip--neutral"><?= e($column) ?></code>
                    <?php endforeach; ?>
                </div>

                <p class="form-hint mt-3 mb-0">
                    En <code>compatibilidad</code> podés poner varias máquinas separadas por <code>|</code>,
                    por ejemplo: <code>Toyota 8FG25|Hyster H2.5</code>. Las marcas y categorías que no existan
                    se crean automáticamente.
                </p>
            </div>
        </div>
    </div>

    <?php if ($preview): ?>
        <div class="col-lg-7">
            <div class="card-admin">
                <div class="card-admin__head">
                    <h2><i class="bi bi-eye"></i> Previsualización · <?= e($preview['name']) ?></h2>
                    <div class="d-flex gap-2">
                        <span class="chip chip--ok"><?= count($preview['valid']) ?> correcta(s)</span>
                        <span class="chip chip--danger"><?= count($preview['invalid']) ?> con error</span>
                    </div>
                </div>

                <div class="card-admin__body card-admin__body--flush">
                    <?php if (!empty($preview['invalid'])): ?>
                        <div class="p-3">
                            <h3 class="form-section__title"><i class="bi bi-exclamation-triangle"></i> Filas con error (no se importan)</h3>
                        </div>
                        <div class="table-responsive-admin">
                            <table class="table-admin">
                                <thead><tr><th>Línea</th><th>Código</th><th>Nombre</th><th>Errores</th></tr></thead>
                                <tbody>
                                <?php foreach (array_slice($preview['invalid'], 0, 40) as $row): ?>
                                    <tr class="import-row-error">
                                        <td><?= (int) $row['line'] ?></td>
                                        <td class="text-mono"><?= e($row['raw']['codigo'] ?? '—') ?></td>
                                        <td><?= e(str_limit((string) ($row['raw']['nombre'] ?? ''), 34)) ?></td>
                                        <td class="import-errors">
                                            <?php foreach ($row['errors'] as $error): ?>
                                                <div><?= e($error) ?></div>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($preview['valid'])): ?>
                        <div class="p-3">
                            <h3 class="form-section__title"><i class="bi bi-check-circle"></i> Filas correctas</h3>
                        </div>
                        <div class="table-responsive-admin" style="max-height:420px;overflow-y:auto">
                            <table class="table-admin">
                                <thead>
                                    <tr>
                                        <th>Línea</th><th>Código</th><th>Nombre</th><th>Marca</th>
                                        <th class="num">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($preview['valid'] as $row): ?>
                                    <tr class="import-row-ok">
                                        <td><?= (int) $row['line'] ?></td>
                                        <td class="text-mono"><?= e($row['data']['code']) ?></td>
                                        <td><?= e(str_limit((string) $row['data']['name'], 40)) ?></td>
                                        <td class="text-muted-2"><?= e($row['data']['brand'] ?: '—') ?></td>
                                        <td class="num"><?= e(money((float) $row['data']['price'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($preview['valid'])): ?>
                    <div class="card-admin__foot">
                        <form method="post" action="<?= admin_url('importar/confirmar') ?>"
                              data-confirm="¿Confirmás la importación de <?= count($preview['valid']) ?> fila(s)?">
                            <?= csrf_field() ?>

                            <label class="filter-check mb-3">
                                <input type="checkbox" name="actualizar" value="1" checked>
                                Actualizar los productos cuyo código ya exista (si lo desmarcás, se omiten)
                            </label>

                            <button type="submit" class="btn btn-accent">
                                <i class="bi bi-check-lg"></i> Importar <?= count($preview['valid']) ?> fila(s)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
