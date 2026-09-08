<?php
/**
 * ARCHIVO: app/views/admin/dashboard/index.php
 * Inicio: novedades + resumen del catálogo + cosas para revisar + últimas consultas.
 *
 * @var array<string,int> $stats
 * @var array<string,array{label:string,count:int,url:string,icon:string}> $review
 * @var array<int,array<string,mixed>> $inquiries
 * @var string $news         HTML ya saneado con las notas de las actualizaciones
 * @var bool   $canEditNews  ¿el usuario actual puede editar las novedades?
 */
$totalReview = array_sum(array_column($review, 'count'));
?>

<!-- ================= NOVEDADES ================= -->
<?php if ($news !== '' || $canEditNews): ?>
<div class="card-admin card-admin--news mb-3">
    <div class="card-admin__head">
        <h2><i class="bi bi-megaphone-fill"></i> Novedades</h2>
        <?php if ($canEditNews): ?>
            <a href="<?= admin_url('novedades') ?>" class="btn btn-ghost btn-sm">
                <i class="bi bi-pencil"></i> Editar
            </a>
        <?php endif; ?>
    </div>
    <div class="card-admin__body">
        <?php if ($news !== ''): ?>
            <div class="news-body"><?= $news ?></div>
        <?php else: ?>
            <p class="text-muted-2 mb-0">
                Todavía no hay novedades cargadas.
                <?php if ($canEditNews): ?>
                    <a href="<?= admin_url('novedades') ?>">Cargá la primera</a>.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ================= NÚMEROS PRINCIPALES ================= -->
<div class="stat-grid">
    <div class="stat-card stat-card--dark">
        <div class="stat-card__label"><i class="bi bi-truck-front"></i> Maquinaria</div>
        <div class="stat-card__value"><?= number_es($stats['machines']) ?></div>
        <div class="stat-card__hint"><?= number_es($stats['machines_active']) ?> publicadas · <a href="<?= admin_url('maquinaria') ?>">ver</a></div>
        <i class="bi bi-truck-front stat-card__icon"></i>
    </div>

    <div class="stat-card stat-card--info">
        <div class="stat-card__label"><i class="bi bi-nut"></i> Repuestos</div>
        <div class="stat-card__value"><?= number_es($stats['parts']) ?></div>
        <div class="stat-card__hint"><?= number_es($stats['parts_active']) ?> publicados · <a href="<?= admin_url('repuestos') ?>">ver</a></div>
        <i class="bi bi-nut stat-card__icon"></i>
    </div>

    <div class="stat-card <?= $stats['inquiries_new'] > 0 ? 'stat-card--warn' : '' ?>">
        <div class="stat-card__label"><i class="bi bi-chat-dots"></i> Consultas</div>
        <div class="stat-card__value"><?= number_es($stats['inquiries_total']) ?></div>
        <div class="stat-card__hint">
            <?php if ($stats['inquiries_new'] > 0): ?>
                <span class="chip chip--accent"><?= (int) $stats['inquiries_new'] ?> sin responder</span>
            <?php else: ?>Todo respondido<?php endif; ?>
        </div>
        <i class="bi bi-chat-dots stat-card__icon"></i>
    </div>
</div>

<div class="row g-3 mt-1">

    <!-- ============ PARA REVISAR ============ -->
    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-clipboard-check"></i> Para revisar</h2>
                <span class="text-muted-2 small"><?= number_es($totalReview) ?> pendiente(s)</span>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <?php if ($totalReview === 0): ?>
                    <p class="p-3 mb-0 text-muted-2"><i class="bi bi-check-circle text-accent"></i> No hay nada pendiente. Todo el catálogo está completo.</p>
                <?php else: ?>
                    <table class="table-admin">
                        <tbody>
                        <?php foreach ($review as $item): ?>
                            <?php if ($item['count'] === 0) { continue; } ?>
                            <tr>
                                <td>
                                    <i class="bi <?= e($item['icon']) ?> text-muted-2"></i>
                                    <?= e($item['label']) ?>
                                </td>
                                <td class="num"><span class="chip chip--warn"><?= number_es($item['count']) ?></span></td>
                                <td class="actions"><a href="<?= e($item['url']) ?>" class="btn btn-ghost btn-sm">Ver</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen del catálogo -->
        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-collection"></i> Resumen del catálogo</h2></div>
            <div class="card-admin__body card-admin__body--flush">
                <table class="table-admin">
                    <tbody>
                        <tr><td>Categorías</td><td class="num fw-bold"><?= number_es($stats['categories']) ?></td>
                            <td class="actions"><a href="<?= admin_url('categorias') ?>" class="btn btn-ghost btn-sm">Ver</a></td></tr>
                        <tr><td>Marcas</td><td class="num fw-bold"><?= number_es($stats['brands']) ?></td>
                            <td class="actions"><a href="<?= admin_url('marcas') ?>" class="btn btn-ghost btn-sm">Ver</a></td></tr>
                        <tr><td>Servicios</td><td class="num fw-bold"><?= number_es($stats['services']) ?></td>
                            <td class="actions"><a href="<?= admin_url('servicios') ?>" class="btn btn-ghost btn-sm">Ver</a></td></tr>
                        <tr><td>Productos destacados</td><td class="num fw-bold"><?= number_es($stats['featured']) ?></td><td></td></tr>
                        <tr><td>Productos en oferta</td><td class="num fw-bold"><?= number_es($stats['offers']) ?></td><td></td></tr>
                        <tr><td>Productos sin publicar</td><td class="num fw-bold"><?= number_es($stats['inactive']) ?></td><td></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============ ÚLTIMAS CONSULTAS + ACCESOS ============ -->
    <div class="col-lg-6">
        <div class="card-admin">
            <div class="card-admin__head">
                <h2><i class="bi bi-chat-dots-fill"></i> Últimas consultas</h2>
                <a href="<?= admin_url('consultas') ?>" class="text-muted-2 small">Ver todas</a>
            </div>
            <div class="card-admin__body card-admin__body--flush">
                <?php if (empty($inquiries)): ?>
                    <p class="p-3 mb-0 text-muted-2">Todavía no llegaron consultas.</p>
                <?php else: ?>
                    <table class="table-admin">
                        <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td>
                                    <a href="<?= admin_url('consultas/' . (int) $inq['id']) ?>"><strong><?= e($inq['name'] ?? 'Sin nombre') ?></strong></a>
                                    <?php if (!empty($inq['company'])): ?><span class="text-muted-2">· <?= e($inq['company']) ?></span><?php endif; ?>
                                    <div class="text-muted-2 small"><?= e(str_limit((string) ($inq['subject'] ?? ''), 60)) ?></div>
                                </td>
                                <td class="text-muted-2 small text-nowrap">
                                    <?= e(date('d/m/Y', strtotime((string) ($inq['created_at'] ?? 'now')))) ?>
                                    <?php if (($inq['status'] ?? '') === 'nueva'): ?><span class="chip chip--accent">Nueva</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-admin">
            <div class="card-admin__head"><h2><i class="bi bi-lightning-charge-fill"></i> Accesos rápidos</h2></div>
            <div class="card-admin__body">
                <div class="d-grid gap-2">
                    <a href="<?= admin_url('maquinaria/crear') ?>" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Cargar una máquina</a>
                    <a href="<?= admin_url('repuestos/crear') ?>" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Cargar un repuesto</a>
                    <a href="<?= admin_url('importar') ?>" class="btn btn-ghost"><i class="bi bi-upload"></i> Importar productos</a>
                    <a href="<?= admin_url('exportar') ?>" class="btn btn-ghost"><i class="bi bi-download"></i> Exportar productos</a>
                    <a href="<?= url() ?>" target="_blank" rel="noopener" class="btn btn-ghost"><i class="bi bi-box-arrow-up-right"></i> Ver el sitio publicado</a>
                </div>
            </div>
        </div>
    </div>
</div>
