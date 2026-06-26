<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

$statuses = ['noua','confirmata','procesare','expediata','livrata','anulata'];

/* ---------- UPDATE STATUS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status  = $_POST['status'];
    if (in_array($status, $statuses, true)) {
        db()->update('orders', ['status' => $status], 'id = ?', [$orderId]);
    }
    header('Location: orders.php?msg=updated');
    exit();
}

/* ---------- DETAIL ---------- */
$detail = null; $detailItems = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $detail = db()->fetchOne('SELECT * FROM orders WHERE id = ?', [(int)$_GET['view']]);
    if ($detail) {
        $detailItems = db()->fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$detail['id']]);
    }
}

$orders = db()->fetchAll('SELECT * FROM orders ORDER BY created_at DESC');

adminHeader('orders', 'Comenzi');
?>
<h2 class="mb-3">Comenzi</h2>
<?php flashMsg(); ?>

<?php if ($detail): ?>
    <div class="card-soft mb-4">
        <div class="d-flex justify-content-between">
            <h5>Comanda <?= htmlspecialchars($detail['order_number']) ?></h5>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary">Închide</a>
        </div>
        <p class="mb-1"><strong><?= htmlspecialchars($detail['first_name'].' '.$detail['last_name']) ?></strong>
            — <?= htmlspecialchars($detail['email']) ?> — <?= htmlspecialchars($detail['phone']) ?></p>
        <p class="mb-1"><?= htmlspecialchars($detail['address'].', '.$detail['city'].' '.($detail['county'] ?? '').' '.($detail['postal_code'] ?? '')) ?></p>
        <?php if ($detail['company_name']): ?>
            <p class="mb-1">Firmă: <?= htmlspecialchars($detail['company_name']) ?> (CUI: <?= htmlspecialchars($detail['cui'] ?? '-') ?>)</p>
        <?php endif; ?>
        <table class="table mt-3">
            <thead><tr><th>Produs</th><th>Preț</th><th>Cant.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($detailItems as $it): ?>
                <tr>
                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                    <td><?= formatPrice($it['price']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><?= $it['line_total'] > 0 ? formatPrice($it['line_total']) : 'La cerere' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total</th><th><?= formatPrice($detail['total']) ?></th></tr></tfoot>
        </table>
    </div>
<?php endif; ?>

<div class="card-soft">
    <table class="table align-middle">
        <thead>
        <tr><th>Comandă</th><th>Client</th><th>Email</th><th>Total</th><th>Status</th><th>Data</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['order_number']) ?></td>
                <td><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?></td>
                <td><?= htmlspecialchars($o['email']) ?></td>
                <td><?= formatPrice($o['total']) ?></td>
                <td>
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width:auto">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-success">OK</button>
                    </form>
                </td>
                <td><small><?= htmlspecialchars($o['created_at']) ?></small></td>
                <td><a href="?view=<?= $o['id'] ?>" class="btn btn-sm btn-primary">Vezi</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <tr><td colspan="7" class="text-muted">Nicio comandă încă.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminFooter(); ?>
