<?php

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

/*
====================================================
  SAVE SETTINGS
====================================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST as $key => $value) {

        if ($key === 'submit') continue;

        $existing = db()->fetchAll(
            "settings",
            "?setting_key=eq." . urlencode($key) . "&limit=1"
        );

        if (!empty($existing)) {

            db()->update(
                "settings",
                ["setting_value" => sanitize($value)],
                "?setting_key=eq." . urlencode($key)
            );

        } else {

            db()->insert("settings", [
                "setting_key" => $key,
                "setting_value" => sanitize($value)
            ]);
        }
    }

    header('Location: settings.php?msg=saved');
    exit();
}

/*
====================================================
  LOAD SETTINGS
====================================================
*/
$result = db()->fetchAll("settings");

$settings = [];

foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setări - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body { background: #f1f5f9; }
        .sidebar { min-height: 100vh; background: #0f172a; color: white; padding: 1.5rem 0; }
        .sidebar .brand { font-size: 1.5rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 2rem; }
        .sidebar .brand span { color: #10b981; }
        .sidebar .nav-link { color: #94a3b8; padding: 0.75rem 1.5rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; }
        .main-content { padding: 2rem; }
        .form-card { background: white; border-radius: 1rem; padding: 2rem; }
        .btn-save { background: #10b981; color: white; border: none; border-radius: 50px; padding: 0.5rem 2rem; }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <div class="col-md-2 sidebar d-none d-md-block">
            <div class="brand">Casigaz <span>Admin</span></div>

            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="products.php">Produse</a>
                <a class="nav-link" href="categories.php">Categorii</a>
                <a class="nav-link" href="orders.php">Comenzi</a>
                <a class="nav-link" href="offers.php">Oferte</a>
                <a class="nav-link active" href="settings.php">Setări</a>
            </nav>
        </div>

        <div class="col-md-10 main-content">

            <h2>Setări Generale</h2>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">Setări salvate!</div>
            <?php endif; ?>

            <form method="POST" class="form-card">

                <input type="text" name="site_name"
                    value="<?= htmlspecialchars($settings['site_name'] ?? 'Casigaz') ?>"
                    class="form-control mb-2"
                    placeholder="Nume site">

                <input type="email" name="site_email"
                    value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>"
                    class="form-control mb-2"
                    placeholder="Email">

                <input type="text" name="site_phone"
                    value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>"
                    class="form-control mb-2"
                    placeholder="Telefon">

                <input type="text" name="site_address"
                    value="<?= htmlspecialchars($settings['site_address'] ?? '') ?>"
                    class="form-control mb-2"
                    placeholder="Adresă">

                <input type="text" name="currency"
                    value="<?= htmlspecialchars($settings['currency'] ?? 'RON') ?>"
                    class="form-control mb-2"
                    placeholder="Monedă">

                <input type="text" name="shipping_cost"
                    value="<?= htmlspecialchars($settings['shipping_cost'] ?? '0') ?>"
                    class="form-control mb-2"
                    placeholder="Transport">

                <input type="text" name="vat_rate"
                    value="<?= htmlspecialchars($settings['vat_rate'] ?? '19') ?>"
                    class="form-control mb-3"
                    placeholder="TVA">

                <button class="btn-save" type="submit">Salvează</button>

            </form>

        </div>
    </div>
</div>

</body>
</html>