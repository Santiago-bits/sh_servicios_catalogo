<?php
/**
 * ARCHIVO: app/views/admin/dashboard/novedades.php
 * Editor de las "Novedades" que se muestran en el inicio del panel.
 *
 * @var string $news  HTML ya saneado
 */
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-megaphone-fill"></i> Editar novedades</h2>
            </div>
            <div class="card-admin__body">
                <p class="text-muted-2 small mb-3">
                    Escribí en HTML directo las notas de cada actualización. Se muestran en el
                    <a href="<?= admin_url('') ?>">inicio del panel</a>, así tu cliente ve los cambios de un vistazo.
                    Poné lo más nuevo arriba.
                </p>

                <form method="post" action="<?= admin_url('novedades') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_return" value="novedades">

                    <label class="form-label" for="news-html">Contenido</label>
                    <textarea class="form-control text-mono" id="news-html" name="news" rows="18"
                              placeholder="&lt;h4&gt;07/09/2026&lt;/h4&gt;&#10;&lt;ul&gt;&#10;  &lt;li&gt;Ahora se pueden subir videos a las máquinas y repuestos.&lt;/li&gt;&#10;&lt;/ul&gt;"><?= e($news) ?></textarea>

                    <p class="form-hint">
                        Se permiten <code>&lt;p&gt; &lt;h3&gt; &lt;h4&gt; &lt;h5&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;
                        &lt;strong&gt; &lt;em&gt; &lt;u&gt; &lt;a href&gt;</code> y tablas
                        (<code>&lt;table&gt; &lt;tr&gt; &lt;th&gt; &lt;td&gt;</code>).
                        Los <code>&lt;script&gt;</code>, estilos y demás se quitan solos al guardar.
                    </p>

                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-check-lg"></i> Guardar novedades
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-admin card-admin--news">
            <div class="card-admin__head">
                <h2><i class="bi bi-eye"></i> Vista previa</h2>
                <span class="text-muted-2 small">Así lo ve el cliente en el inicio</span>
            </div>
            <div class="card-admin__body">
                <?php if (trim($news) !== ''): ?>
                    <div class="news-body"><?= $news ?></div>
                <?php else: ?>
                    <p class="text-muted-2 mb-0">Todavía no hay novedades cargadas.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-lightbulb"></i> Ejemplo</h2></div>
            <div class="card-admin__body">
<pre class="text-mono small mb-0" style="white-space:pre-wrap">&lt;h4&gt;Septiembre 2026&lt;/h4&gt;
&lt;ul&gt;
  &lt;li&gt;Nuevo: se pueden subir &lt;strong&gt;videos&lt;/strong&gt; a los productos.&lt;/li&gt;
  &lt;li&gt;Arreglado: el bot&oacute;n de vaciar la cotizaci&oacute;n.&lt;/li&gt;
&lt;/ul&gt;

&lt;h4&gt;Agosto 2026&lt;/h4&gt;
&lt;ul&gt;
  &lt;li&gt;Primera versi&oacute;n del panel.&lt;/li&gt;
&lt;/ul&gt;</pre>
            </div>
        </div>
    </div>
</div>
