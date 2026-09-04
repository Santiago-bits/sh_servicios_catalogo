<?php
/**
 * ARCHIVO: app/views/partials/tag-list.php
 * Lista de etiquetas de una tarjeta de producto (chips).
 *
 * @var array<int,array{color:string,icon:?string,label:string}> $tags
 */
foreach (($tags ?? []) as $t): ?>
    <span class="tag tag--<?= e($t['color']) ?>">
        <?php if (!empty($t['icon'])): ?><i class="bi <?= e($t['icon']) ?>"></i> <?php endif; ?><?= e($t['label']) ?>
    </span>
<?php endforeach; ?>
