<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

$statuses = ['noua','in_lucru','trimisa','finalizata','anulata'];

/* ---------- UPDATE STATUS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'], $_POST['status'])) {
    $offerId = (int)$_POST['offer_id'];
    $status  = $_POST['status'];
    if (in_array($status, $statuses, true)) {
        db()->update('offers', ['status' => $status], 'id = ?', [$offerId]);
    }
    header('Location: offers.php?msg=updated');
    exit();
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db()->delete('offers', 'id = ?', [(int)$_GET['delete']]);
    header('Location: offers.php?msg=deleted');
    exit();
}

$offers = db()->fetchAll(
    'SELECT o.*, p.name AS product_name
     FROM offers o
     LEFT JOIN products p ON p.id = o.product_id
     ORDER BY o.created_at DESC'
);

adminHeader('offers', 'Oferte');
?>
<h2 class="mb-3">Solicitări ofertă</h2>
<?php flashMsg(); ?>

<div class="card-soft">
    <table class="table align-middle">
        <thead>
        <tr><th>Contact</th><th>Companie</th><th>Telefon</th><th>Email</th><th>Produs</th><th>Mesaj</th><th>Status</th><th>Data</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($offers as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['contact_name']) ?></td>
                <td><?= htmlspecialchars($o['company_name'] ?: '-') ?></td>
                <td><?= htmlspecialchars($o['phone']) ?></td>
                <td><?= htmlspecialchars($o['email']) ?></td>
                <td><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
                <td style="max-width:220px"><small><?= nl2br(htmlspecialchars($o['message'] ?? '')) ?></small></td>
                <td>
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width:auto">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-success">OK</button>
                    </form>
                </td>
                <td><small><?= htmlspecialchars($o['created_at']) ?></small></td>
                <td><a href="?delete=<?= $o['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi?')">Del</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($offers)): ?>
            <tr><td colspan="9" class="text-muted">Nicio solicitare încă.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminFooter(); ?>
