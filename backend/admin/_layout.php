<?php
/*
====================================================
  ADMIN LAYOUT (header + sidebar + footer)
====================================================
*/
function adminHeader($active = '', $title = 'Admin') {
    $nav = [
        'dashboard'  => ['Dashboard',  'dashboard.php'],
        'products'   => ['Produse',    'products.php'],
        'categories' => ['Categorii',  'categories.php'],
        'orders'     => ['Comenzi',    'orders.php'],
        'offers'     => ['Oferte',     'offers.php'],
        'settings'   => ['Setări',     'settings.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Casigaz Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; }
        .sidebar { min-height: 100vh; background: #0f172a; }
        .sidebar .brand { font-size: 1.4rem; font-weight: 700; padding: 1.25rem 1.5rem; color: #fff; }
        .sidebar .brand span { color: #10b981; }
        .sidebar .nav-link { color: #94a3b8; padding: .7rem 1.5rem; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; border-left-color: #10b981; background: rgba(16,185,129,.08); }
        .main-content { padding: 2rem; }
        .card-soft { background: #fff; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .btn-brand { background: #10b981; color: #fff; border: none; border-radius: 50px; padding: .5rem 1.5rem; }
        .btn-brand:hover { background: #059669; color:#fff; }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <div class="col-md-2 sidebar p-0 d-none d-md-block">
        <div class="brand">Casigaz <span>Admin</span></div>
        <nav class="nav flex-column">
            <?php foreach ($nav as $key => [$label, $href]): ?>
                <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a class="nav-link mt-3 text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    <div class="col-md-10 main-content">
    <?php
}

function adminFooter() {
    ?>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}

function flashMsg() {
    if (isset($_GET['msg'])) {
        $map = [
            'saved'   => 'Salvat cu succes!',
            'deleted' => 'Șters cu succes!',
            'updated' => 'Actualizat cu succes!',
        ];
        $text = $map[$_GET['msg']] ?? 'Acțiune realizată!';
        echo '<div class="alert alert-success">' . htmlspecialchars($text) . '</div>';
    }
}
