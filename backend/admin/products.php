<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

/* ---------- DELETE ---------- */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $images = db()->fetchAll('SELECT image_path FROM product_images WHERE product_id = ?', [$id]);
    foreach ($images as $img) {
        deleteImageFile($img['image_path']);
    }
    db()->delete('products', 'id = ?', [$id]); // product_images se șterg prin FK CASCADE
    header('Location: products.php?msg=deleted');
    exit();
}

/* ---------- TOGGLE STATUS ---------- */
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $p = db()->fetchOne('SELECT status FROM products WHERE id = ?', [$id]);
    if ($p) {
        db()->update('products', ['status' => $p['status'] ? 0 : 1], 'id = ?', [$id]);
    }
    header('Location: products.php?msg=updated');
    exit();
}

/* ---------- LIST ---------- */
$products = db()->fetchAll(
    'SELECT p.*, c.name AS category_name,
            (SELECT image_path FROM product_images pi
             WHERE pi.product_id = p.id ORDER BY pi.is_main DESC, pi.sort_order ASC LIMIT 1) AS image
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC'
);

adminHeader('products', 'Produse');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Produse</h2>
    <a href="product-edit.php" class="btn-brand">+ Adaugă produs</a>
</div>
<?php flashMsg(); ?>

<div class="card-soft">
    <table class="table align-middle">
        <thead>
        <tr>
            <th>#</th><th>Imagine</th><th>Nume</th><th>Categorie</th>
            <th>Preț</th><th>Disponibilitate</th><th>Status</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <?php if ($p['image']): ?>
                        <img src="/backend/<?= htmlspecialchars($p['image']) ?>" style="width:46px;height:46px;object-fit:cover;border-radius:6px">
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($p['name']) ?>
                    <?php if ($p['price_on_request']): ?>
                        <span class="badge bg-warning text-dark">CERE OFERTĂ</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                <td><?= $p['price_on_request'] ? 'La cerere' : formatPrice($p['price']) ?></td>
                <td>
                    <?php if ($p['price_on_request']): ?>
                        <span class="text-muted">—</span>
                    <?php elseif ($p['in_stock']): ?>
                        <span class="badge bg-success">În stoc</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Indisponibil</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $p['status'] ? 'success' : 'danger' ?>">
                        <?= $p['status'] ? 'Activ' : 'Inactiv' ?>
                    </span>
                </td>
                <td class="text-end text-nowrap">
                    <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Toggle</a>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Ștergi produsul?')">Del</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <tr><td colspan="8" class="text-muted">Niciun produs încă.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminFooter(); ?>
