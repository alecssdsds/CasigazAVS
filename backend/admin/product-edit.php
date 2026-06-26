<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $productId > 0;

/* ---------- SAVE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        header('Location: product-edit.php' . ($isEdit ? '?id=' . $productId . '&' : '?') . 'err=name');
        exit();
    }

    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    $data = [
        'name'              => sanitize($name),
        'category_id'       => $categoryId,
        'short_description' => sanitize($_POST['short_description'] ?? ''),
        'description'       => trim($_POST['description'] ?? ''),
        'sku'               => sanitize($_POST['sku'] ?? ''),
        'price'             => (float)($_POST['price'] ?? 0),
        'old_price'         => $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null,
        'price_on_request'  => isset($_POST['price_on_request']) ? 1 : 0,
        'stock'             => (int)($_POST['stock'] ?? 0),
        'in_stock'          => isset($_POST['in_stock']) ? 1 : 0,
        'featured'          => isset($_POST['featured']) ? 1 : 0,
        'status'            => isset($_POST['status']) ? 1 : 0,
        'seo_title'         => sanitize($_POST['seo_title'] ?? ''),
        'seo_description'   => sanitize($_POST['seo_description'] ?? ''),
    ];

    if ($isEdit) {
        $data['slug'] = uniqueProductSlug($name, $productId);
        db()->update('products', $data, 'id = ?', [$productId]);
    } else {
        $data['slug'] = uniqueProductSlug($name);
        $productId = db()->insert('products', $data);
        $isEdit = true;
    }

    /* upload imagini multiple */
    if (!empty($_FILES['images']['name'][0])) {
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];

            $up = uploadProductImage($file);
            if ($up['success']) {
                $existing = db()->fetchOne('SELECT COUNT(*) AS c FROM product_images WHERE product_id = ?', [$productId]);
                $isMain = ((int)($existing['c'] ?? 0) === 0) ? 1 : 0;
                db()->insert('product_images', [
                    'product_id' => $productId,
                    'image_path' => $up['path'],
                    'is_main'    => $isMain,
                    'sort_order' => (int)($existing['c'] ?? 0),
                ]);
            }
        }
    }

    /* set main image */
    if (!empty($_POST['set_main'])) {
        $imgId = (int)$_POST['set_main'];
        db()->update('product_images', ['is_main' => 0], 'product_id = ?', [$productId]);
        db()->update('product_images', ['is_main' => 1], 'id = ?', [$imgId]);
    }

    /* delete image */
    if (!empty($_POST['delete_image'])) {
        $imgId = (int)$_POST['delete_image'];
        $img = db()->fetchOne('SELECT image_path FROM product_images WHERE id = ? AND product_id = ?', [$imgId, $productId]);
        if ($img) {
            deleteImageFile($img['image_path']);
            db()->delete('product_images', 'id = ?', [$imgId]);
        }
    }

    header('Location: product-edit.php?id=' . $productId . '&msg=saved');
    exit();
}

/* ---------- LOAD ---------- */
$product = null;
if ($isEdit) {
    $product = db()->fetchOne('SELECT * FROM products WHERE id = ?', [$productId]);
    if (!$product) { header('Location: products.php'); exit(); }
}

$categories = db()->fetchAll('SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC');
$images = $isEdit ? getProductImages($productId) : [];

function val($product, $key, $default = '') {
    return htmlspecialchars((string)($product[$key] ?? $default));
}
function checked($product, $key, $defaultTrue = false) {
    if ($product === null) return $defaultTrue ? 'checked' : '';
    return !empty($product[$key]) ? 'checked' : '';
}

adminHeader('products', $isEdit ? 'Editează produs' : 'Adaugă produs');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= $isEdit ? 'Editează produs' : 'Adaugă produs' ?></h2>
    <a href="products.php" class="btn btn-outline-secondary">← Înapoi</a>
</div>
<?php flashMsg(); ?>

<form method="POST" enctype="multipart/form-data">
<div class="row g-4">
    <div class="col-md-8">
        <div class="card-soft mb-4">
            <div class="mb-3">
                <label class="form-label">Nume produs *</label>
                <input type="text" name="name" class="form-control" required value="<?= val($product, 'name') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Descriere scurtă</label>
                <input type="text" name="short_description" class="form-control" maxlength="500" value="<?= val($product, 'short_description') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Descriere completă</label>
                <textarea name="description" class="form-control" rows="6"><?= val($product, 'description') ?></textarea>
            </div>
        </div>

        <div class="card-soft">
            <h5 class="mb-3">Imagini</h5>
            <div class="row g-2 mb-3">
                <?php foreach ($images as $img): ?>
                    <div class="col-3">
                        <div class="border rounded p-1 text-center">
                            <img src="/backend/<?= htmlspecialchars($img['image_path']) ?>" class="img-fluid mb-1" style="height:90px;object-fit:cover">
                            <?php if ($img['is_main']): ?>
                                <div><span class="badge bg-success">Principală</span></div>
                            <?php else: ?>
                                <button name="set_main" value="<?= $img['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mb-1">Setează principală</button>
                            <?php endif; ?>
                            <button name="delete_image" value="<?= $img['id'] ?>" class="btn btn-sm btn-outline-danger w-100"
                                    onclick="return confirm('Ștergi imaginea?')">Șterge</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($images)): ?>
                    <p class="text-muted">Nicio imagine încă.</p>
                <?php endif; ?>
            </div>
            <label class="form-label">Adaugă imagini (poți selecta mai multe)</label>
            <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            <?php if (!$isEdit): ?>
                <small class="text-muted">Salvează produsul, apoi adaugă imagini.</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-soft mb-4">
            <h5 class="mb-3">Preț & stoc</h5>
            <div class="form-check mb-2">
                <input type="checkbox" name="price_on_request" class="form-check-input" id="por" <?= checked($product, 'price_on_request') ?>>
                <label class="form-check-label" for="por"><strong>Preț la cerere</strong> (afișează „CERE OFERTĂ”)</label>
            </div>
            <div class="mb-2">
                <label class="form-label">Preț (RON)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= val($product, 'price', '0') ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Preț vechi (opțional)</label>
                <input type="number" step="0.01" name="old_price" class="form-control" value="<?= $product && $product['old_price'] !== null ? htmlspecialchars($product['old_price']) : '' ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Stoc</label>
                <input type="number" name="stock" class="form-control" value="<?= val($product, 'stock', '0') ?>">
            </div>
            <div class="form-check">
                <input type="checkbox" name="in_stock" class="form-check-input" id="instock" <?= checked($product, 'in_stock', true) ?>>
                <label class="form-check-label" for="instock">Disponibil</label>
            </div>
        </div>

        <div class="card-soft mb-4">
            <h5 class="mb-3">Organizare</h5>
            <div class="mb-2">
                <label class="form-label">Categorie</label>
                <select name="category_id" class="form-select">
                    <option value="">— Fără categorie —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $product && $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">SKU</label>
                <input type="text" name="sku" class="form-control" value="<?= val($product, 'sku') ?>">
            </div>
            <div class="form-check">
                <input type="checkbox" name="featured" class="form-check-input" id="feat" <?= checked($product, 'featured') ?>>
                <label class="form-check-label" for="feat">Produs recomandat (apare pe prima pagină)</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="status" class="form-check-input" id="stat" <?= checked($product, 'status', true) ?>>
                <label class="form-check-label" for="stat">Vizibil pe site</label>
            </div>
        </div>

        <button type="submit" class="btn-brand w-100">Salvează produsul</button>
    </div>
</div>
</form>
<?php adminFooter(); ?>
