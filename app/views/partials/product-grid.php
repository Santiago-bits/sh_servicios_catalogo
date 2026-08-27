<?php
/**
 * ARCHIVO: app/views/partials/product-grid.php
 * Grilla de productos (se reutiliza en las respuestas AJAX).
 *
 * @var array<int,array<string,mixed>> $products
 * @var string $view
 * @var \Core\View $view_ (el objeto View llega como $view en el layout, acá usamos $this)
 */

$listView = ($viewMode ?? 'grid') === 'lista';
?>
<?php if (empty($products)): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <h3>No encontramos productos con esos filtros</h3>
        <p>Probá quitando algún filtro, buscando por código OEM o escribiendo el modelo de la máquina.</p>
        <a href="<?= e(strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>" class="btn btn-outline-accent">
            <i class="bi bi-arrow-counterclockwise"></i> Limpiar filtros
        </a>
    </div>
<?php else: ?>
    <div class="product-grid <?= $listView ? 'product-grid--list' : '' ?>">
        <?php foreach ($products as $product): ?>
            <?php include VIEW_PATH . '/partials/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
