<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

$totalProducts   = (int)(db()->fetchOne('SELECT COUNT(*) c FROM products')['c'] ?? 0);
$totalCategories = (int)(db()->fetchOne('SELECT COUNT(*) c FROM categories')['c'] ?? 0);
$newOrders       = (int)(db()->fetchOne("SELECT COUNT(*) c FROM orders WHERE status = 'noua'")['c'] ?? 0);
$newOffers       = (int)(db()->fetchOne("SELECT COUNT(*) c FROM offers WHERE status = 'noua'")['c'] ?? 0);
$outOfStock      = (int)(db()->fetchOne('SELECT COUNT(*) c FROM products WHERE price_on_request = 0 AND in_stock = 0 AND status = 1')['c'] ?? 0);

$recentOrders = db()->fetchAll('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5');
$recentOffers = db()->fetchAll(
    'SELECT o.*, p.name AS product_name FROM offers o
     LEFT JOIN products p ON p.id = o.product_id
     ORDER BY o.created_at DESC LIMIT 5'
);

function statCard($label, $value, $icon, $color) {
    echo '<div class="col-md-3"><div class="card-soft d-flex align-items-center gap-3">'
       . '<i class="bi ' . $icon . '" style="font-size:2rem;color:' . $color . '"></i>'
       . '<div><div class="text-muted small">' . $label . '</div>'
       . '<div class="fs-4 fw-bold">' . $value . '</div></div></div></div>';
}

adminHeader('dashboard', 'Dashboard');
?>
<h2 class="mb-4">Dashboard</h2>

<div class="row g-3 mb-4">
    <?php
    statCard('Produse', $totalProducts, 'bi-box-seam', '#10b981');
    statCard('Categorii', $totalCategories, 'bi-tags', '#3b82f6');
    statCard('Comenzi noi', $newOrders, 'bi-cart-check', '#f59e0b');
    statCard('Cereri ofertă', $newOffers, 'bi-envelope', '#8b5cf6');
    ?>
</div>

<?php if ($outOfStock > 0): ?>
    <div class="alert alert-warning"><?= $outOfStock ?> produs(e) marcate ca indisponibile.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-soft">
            <h5 class="mb-3">Comenzi recente</h5>
            <table class="table table-sm">
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><?= htmlspecialchars($o['order_number']) ?></td>
                        <td><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?></td>
                        <td><?= formatPrice($o['total']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($o['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?><tr><td class="text-muted">Nicio comandă.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <a href="orders.php" class="btn btn-sm btn-outline-success">Toate comenzile</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-soft">
            <h5 class="mb-3">Cereri ofertă recente</h5>
            <table class="table table-sm">
                <tbody>
                <?php foreach ($recentOffers as $o): ?>
                    <tr>
                        <td><?= htmlspecialchars($o['contact_name']) ?></td>
                        <td><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($o['phone']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOffers)): ?><tr><td class="text-muted">Nicio cerere.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <a href="offers.php" class="btn btn-sm btn-outline-success">Toate ofertele</a>
        </div>
    </div>
</div>
<?php adminFooter(); ?>
