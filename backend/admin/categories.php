<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = db();

/* DELETE PRODUCT */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $images = $db->fetchAll("SELECT image_path FROM product_images WHERE product_id = ?", [$id]);
    foreach ($images as $img) {
        $file = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($file)) unlink($file);
    }

    $db->delete('product_images', 'product_id = ?', [$id]);
    $db->delete('products', 'id = ?', [$id]);

    header('Location: products.php?msg=deleted');
    exit();
}

/* TOGGLE STATUS */
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $current = $db->fetchOne("SELECT status FROM products WHERE id = ?", [$id]);
    if ($current) {
        $new = $current['status'] ? 0 : 1;
        $db->update('products', ['status' => $new], 'id = ?', [$id]);
    }

    header('Location: products.php?msg=updated');
    exit();
}

/* PRODUCTS LIST */
$products = $db->fetchAll("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Produse</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>Produse</h3>
        <a href="product-edit.php" class="btn btn-success">+ Adaugă produs</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= $_GET['msg'] === 'deleted' ? 'Produs șters' : 'Actualizat' ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered bg-white">
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
                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                <td>
                    <?= $p['price_on_request']
                        ? 'La cerere'
                        : number_format($p['price'], 2) . ' RON' ?>
                </td>
                <td><?= $p['stock'] ?></td>
                <td>
                    <span class="badge bg-<?= $p['status'] ? 'success' : 'danger' ?>">
                        <?= $p['status'] ? 'Activ' : 'Inactiv' ?>
                    </span>
                </td>
                <td>
                    <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Toggle</a>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi?')">Del</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>