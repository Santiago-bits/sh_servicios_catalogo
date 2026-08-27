<?php
/**
 * ARCHIVO: app/views/admin/exports/index.php
 */

$icons = [
    'maquinaria'   => 'bi-truck-front-fill',
    'repuestos'    => 'bi-nut-fill',
    'stock'        => 'bi-box-seam-fill',
    'precios'      => 'bi-cash-stack',
    'consultas'    => 'bi-chat-dots-fill',
    'cotizaciones' => 'bi-file-earmark-text',
    'movimientos'  => 'bi-arrow-left-right',
    'auditoria'    => 'bi-shield-check',
];
?>
<div class="card-admin">
    <div class="card-admin__body">
        <p class="mb-0 text-muted-2">
            <i class="bi bi-info-circle text-accent"></i>
            Los archivos se generan en el momento con los datos actuales.
            <?php if (!$canSeeCost): ?>
                <strong>Tu rol no incluye costos ni ganancias</strong>, así que esas columnas no se exportan.
            <?php endif; ?>
        </p>

        <?php if (!App\Services\ExportService::canExportXlsx()): ?>
            <p class="mb-0 mt-2 text-muted-2">
                <i class="bi bi-exclamation-triangle text-accent"></i>
                Este servidor no tiene la extensión <code>zip</code> de PHP, así que los botones de
                <strong>Excel</strong> descargan un <strong>CSV</strong> (se abre igual con Excel,
                elegí "delimitado por punto y coma").
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($datasets as $key => $label): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card-admin h-100">
                <div class="card-admin__body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="admin-user__avatar" style="width:46px;height:46px;font-size:1.2rem">
                            <i class="bi <?= e($icons[$key] ?? 'bi-table') ?>"></i>
                        </span>
                        <div>
                            <strong class="d-block"><?= e($label) ?></strong>
                            <small class="text-muted-2 text-mono"><?= e($key) ?></small>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= admin_url('exportar/' . $key . '/xlsx') ?>" class="btn btn-accent btn-sm flex-grow-1">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </a>
                        <a href="<?= admin_url('exportar/' . $key . '/csv') ?>" class="btn btn-ghost btn-sm flex-grow-1">
                            <i class="bi bi-filetype-csv"></i> CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card-admin">
    <div class="card-admin__head">
        <h2><i class="bi bi-file-earmark-pdf"></i> Catálogo en PDF</h2>
        <a href="<?= admin_url('catalogo-pdf') ?>" class="btn btn-accent btn-sm">
            <i class="bi bi-magic"></i> Generar catálogo
        </a>
    </div>
    <div class="card-admin__body">
        <p class="mb-0 text-muted-2">
            Armá un PDF con la selección de productos que quieras (por categoría, marca o sólo destacados),
            con o sin precios, listo para mandar por email o imprimir.
        </p>
    </div>
</div>
