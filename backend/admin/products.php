<?php

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

/*
====================================================
  DELETE PRODUCT
====================================================
*/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    // șterge imagini din disk (OK rămâne PHP)
    $images = db()->fetchAll(
        "product_images",
        "?product_id=eq." . $id
    );

    foreach ($images as $img) {
        $file = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // șterge DB
    db()->delete("product_images", "?product_id=eq." . $id);
    db()->delete("products", "?id=eq." . $id);

    header('Location: products.php?msg=deleted');
    exit();
}

/*
====================================================
  TOGGLE STATUS
====================================================
*/
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {

    $id = (int)$_GET['toggle'];

    $product = db()->fetchAll(
        "products",
        "?id=eq." . $id . "&limit=1"
    );

    $product = $product[0] ?? null;

    if ($product) {

        $newStatus = $product['status'] ? 0 : 1;

        db()->update(
            "products",
            ["status" => $newStatus],
            "?id=eq." . $id
        );
    }

    header('Location: products.php?msg=updated');
    exit();
}

/*
====================================================
  LOAD PRODUCTS
====================================================
*/
$products = db()->fetchAll(
    "products",
    "?order=created_at.desc"
);

/*
====================================================
  LOAD CATEGORIES (for join logic)
====================================================
*/
$categories = db()->fetchAll("categories");

$catMap = [];
foreach ($categories as $c) {
    $catMap[$c['id']] = $c['name'];
}

/*
====================================================
  HTML START
====================================================
*/
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produse - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body { background: #f1f5f9; }
        .sidebar { min-height: 100vh; background: #0f172a; color: white; padding: 1.5rem 0; }
        .sidebar .brand { font-size: 1.5rem; font-weight: 700; padding: 0 1.5rem; margin-bottom: 2rem; }
        .sidebar .brand span { color: #10b981; }
        .main-content { padding: 2rem; }
        .btn-add { background: #10b981; color: white; border-radius: 50px; padding: 0.5rem 1.5rem; }
        .table-card { background: white; border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>

<body>

<div class="container-fluid">
<div class="row">

    <div class="col-md-2 sidebar d-none d-md-block">
        <div class="brand">Casigaz <span>Admin</span></div>

        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link active" href="products.php">Produse</a>
            <a class="nav-link" href="categories.php">Categorii</a>
            <a class="nav-link" href="orders.php">Comenzi</a>
            <a class="nav-link" href="offers.php">Oferte</a>
            <a class="nav-link" href="settings.php">Setări</a>
        </nav>
    </div>

    <div class="col-md-10 main-content">

        <div class="d-flex justify-content-between mb-3">
            <h2>Produse</h2>
            <a href="product-edit.php" class="btn-add">+ Adaugă</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">Acțiune realizată!</div>
        <?php endif; ?>

        <div class="table-card">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Categorie</th>
                    <th>Preț</th>
                    <th>Stoc</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $catMap[$p['category_id']] ?? '-' ?></td>

                        <td>
                            <?= $p['price_on_request'] ? 'La cerere' : number_format($p['price'], 2) . ' RON' ?>
                        </td>

                        <td><?= $p['price_on_request'] ? '-' : $p['stock'] ?></td>

                        <td>
                            <span class="badge bg-<?= $p['status'] ? 'success' : 'danger' ?>">
                                <?= $p['status'] ? 'Activ' : 'Inactiv' ?>
                            </span>
                        </td>

                        <td>
                            <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Toggle</a>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Sigur?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                    <tr><td colspan="7">Niciun produs</td></tr>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>
</div>

</body>
</html>