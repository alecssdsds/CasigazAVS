<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

/* ---------- SAVE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'submit') continue;
        $exists = db()->fetchOne('SELECT id FROM settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            db()->update('settings', ['setting_value' => sanitize($value)], 'setting_key = ?', [$key]);
        } else {
            db()->insert('settings', ['setting_key' => $key, 'setting_value' => sanitize($value)]);
        }
    }
    header('Location: settings.php?msg=saved');
    exit();
}

/* ---------- LOAD ---------- */
$settings = [];
foreach (db()->fetchAll('SELECT setting_key, setting_value FROM settings') as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

adminHeader('settings', 'Setări');
?>
<h2 class="mb-3">Setări generale</h2>
<?php flashMsg(); ?>

<div class="card-soft" style="max-width:640px">
    <form method="POST">
        <div class="mb-2"><label class="form-label">Nume site</label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'Casigaz') ?>"></div>
        <div class="mb-2"><label class="form-label">Email contact</label>
            <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label">Telefon</label>
            <input type="text" name="site_phone" class="form-control" value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label">Adresă</label>
            <input type="text" name="site_address" class="form-control" value="<?= htmlspecialchars($settings['site_address'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label">Monedă</label>
            <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($settings['currency'] ?? 'RON') ?>"></div>
        <div class="mb-2"><label class="form-label">Cost transport</label>
            <input type="text" name="shipping_cost" class="form-control" value="<?= htmlspecialchars($settings['shipping_cost'] ?? '0') ?>"></div>
        <div class="mb-3"><label class="form-label">TVA (%)</label>
            <input type="text" name="vat_rate" class="form-control" value="<?= htmlspecialchars($settings['vat_rate'] ?? '19') ?>"></div>
        <button class="btn-brand" type="submit">Salvează</button>
    </form>
</div>
<?php adminFooter(); ?>
