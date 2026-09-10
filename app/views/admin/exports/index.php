<?php
/**
 * ARCHIVO: app/views/admin/exports/index.php
 */

$icons = [
    'maquinaria'   => 'bi-truck-front-fill',
    'repuestos'    => 'bi-nut-fill',
    'precios'      => 'bi-cash-stack',
    'consultas'    => 'bi-chat-dots-fill',
    'cotizaciones' => 'bi-file-earmark-text',
    'auditoria'    => 'bi-shield-check',
];

$descriptions = [
    'maquinaria'   => 'Todas las máquinas con la ficha técnica completa (motor, potencia, medidas, batería, etc.), precios, oferta y estado. Mismas columnas que la plantilla de importación.',
    'repuestos'    => 'Todos los repuestos con códigos, origen, unidad, peso, compatibilidad, precios y estado.',
    'precios'      => 'Lista de precios de todo el catálogo (costo, ganancia, precio final, oferta y estado).',
    'consultas'    => 'Consultas recibidas desde el sitio, con datos de contacto y estado.',
    'cotizaciones' => 'Cotizaciones generadas, con cliente, total y estado.',
    'auditoria'    => 'Registro de quién hizo cada cambio en el panel.',
];
?>
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

<div class="card-admin">
    <div class="card-admin__body">
        <p class="mb-0 text-muted-2">
            <i class="bi bi-info-circle text-accent"></i>
            Cada botón descarga un archivo con los datos de ese momento: <strong>Excel</strong> y
            <strong>CSV</strong> traen <strong>todas las columnas</strong> para trabajarlo en una planilla;
            el <strong>PDF</strong> muestra sólo las columnas principales, para imprimir o mandar.
            <?php if (!$canSeeCost): ?>
                <br><strong>Tu rol no incluye costos ni ganancias</strong>, así que esas columnas no se exportan.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($datasets as $key => $label): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card-admin h-100">
                <div class="card-admin__body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="admin-user__avatar" style="width:46px;height:46px;font-size:1.2rem">
                            <i class="bi <?= e($icons[$key] ?? 'bi-table') ?>"></i>
                        </span>
                        <strong class="d-block"><?= e($label) ?></strong>
                    </div>
                    <p class="text-muted-2 small flex-grow-1"><?= e($descriptions[$key] ?? '') ?></p>

                    <div class="d-flex gap-2 mt-auto">
                        <a href="<?= admin_url('exportar/' . $key . '/xlsx') ?>" class="btn btn-accent btn-sm flex-grow-1">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </a>
                        <a href="<?= admin_url('exportar/' . $key . '/csv') ?>" class="btn btn-ghost btn-sm flex-grow-1">
                            <i class="bi bi-filetype-csv"></i> CSV
                        </a>
                        <a href="<?= admin_url('exportar/' . $key . '/pdf') ?>" class="btn btn-ghost btn-sm flex-grow-1">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
