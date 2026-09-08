<?php
/**
 * ARCHIVO: app/views/admin/dashboard/novedades.php
 * Novedades del panel. El contenido sale de content/novedades.html
 * (HTML plano que se edita a mano). Acá sólo se muestra.
 *
 * @var string $news      HTML del archivo
 * @var string $newsPath  Ruta del archivo (para el aviso)
 */
?>

<div class="card-admin card-admin--news">
    <div class="card-admin__head">
        <h2><i class="bi bi-megaphone-fill"></i> Novedades</h2>
        <span class="text-muted-2 small">Notas de las actualizaciones del sistema</span>
    </div>
    <div class="card-admin__body">
        <?php if (trim($news) !== ''): ?>
            <div class="news-body"><?= $news ?></div>
        <?php else: ?>
            <p class="text-muted-2 mb-0">Todavía no hay novedades cargadas.</p>
        <?php endif; ?>
    </div>
</div>

<p class="text-muted-2 small">
    <i class="bi bi-info-circle"></i>
    Para cambiar este texto, editá el archivo <code><?= e($newsPath) ?></code> con el bloc de notas
    y subilo con el resto de la carpeta. Es HTML común: <code>&lt;h4&gt;</code> para la fecha,
    <code>&lt;ul&gt;&lt;li&gt;</code> para la lista.
</p>
