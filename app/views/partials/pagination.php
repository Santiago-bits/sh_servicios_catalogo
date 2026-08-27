<?php
/**
 * ARCHIVO: app/views/partials/pagination.php
 *
 * @var array{page:int,last_page:int,total:int,from:int,to:int} $result
 */

$page  = (int) ($result['page'] ?? 1);
$last  = (int) ($result['last_page'] ?? 1);

if ($last <= 1) {
    return;
}

$window = 2;
$start  = max(1, $page - $window);
$end    = min($last, $page + $window);
?>
<nav class="pagination-wrap" aria-label="Paginación">
    <ul class="pagination-list">

        <li>
            <?php if ($page > 1): ?>
                <a href="<?= e(query_url(['pagina' => $page - 1])) ?>" rel="prev" aria-label="Página anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="is-disabled"><i class="bi bi-chevron-left"></i></span>
            <?php endif; ?>
        </li>

        <?php if ($start > 1): ?>
            <li><a href="<?= e(query_url(['pagina' => 1])) ?>">1</a></li>
            <?php if ($start > 2): ?><li><span class="is-disabled">…</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li>
                <?php if ($i === $page): ?>
                    <span class="is-current" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e(query_url(['pagina' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>

        <?php if ($end < $last): ?>
            <?php if ($end < $last - 1): ?><li><span class="is-disabled">…</span></li><?php endif; ?>
            <li><a href="<?= e(query_url(['pagina' => $last])) ?>"><?= $last ?></a></li>
        <?php endif; ?>

        <li>
            <?php if ($page < $last): ?>
                <a href="<?= e(query_url(['pagina' => $page + 1])) ?>" rel="next" aria-label="Página siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="is-disabled"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
