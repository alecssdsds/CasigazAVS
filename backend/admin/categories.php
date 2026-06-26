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
    $cat = db()->fetchOne('SELECT image_path FROM categories WHERE id = ?', [$id]);
    if ($cat && $cat['image_path']) deleteImageFile($cat['image_path']);
    db()->delete('categories', 'id = ?', [$id]); // produsele rămân (category_id -> NULL)
    header('Location: categories.php?msg=deleted');
    exit();
}

/* ---------- CREATE / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($name !== '') {
        $data = [
            'name'        => sanitize($name),
            'description' => sanitize($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'status'      => isset($_POST['status']) ? 1 : 0,
        ];

        // imagine opțională
        if (!empty($_FILES['image']['name'])) {
            $up = uploadCategoryImage($_FILES['image']);
            if ($up['success']) {
                $data['image_path'] = $up['path'];
            }
        }

        if ($id > 0) {
            $data['slug'] = uniqueCategorySlug($name, $id);
            db()->update('categories', $data, 'id = ?', [$id]);
        } else {
            $data['slug'] = uniqueCategorySlug($name);
            db()->insert('categories', $data);
        }
    }
    header('Location: categories.php?msg=saved');
    exit();
}

/* ---------- EDIT TARGET ---------- */
$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit = db()->fetchOne('SELECT * FROM categories WHERE id = ?', [(int)$_GET['edit']]);
}

/* ---------- LIST ---------- */
$categories = db()->fetchAll(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c
     ORDER BY c.sort_order ASC, c.name ASC'
);

adminHeader('categories', 'Categorii');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Categorii</h2>
</div>
<?php flashMsg(); ?>

<div class="row g-4">
    <!-- FORM -->
    <div class="col-md-4">
        <div class="card-soft">
            <h5><?= $edit ? 'Editează categoria' : 'Adaugă categorie' ?></h5>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <?php endif; ?>

                <div class="mb-2">
                    <label class="form-label">Nume *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Descriere</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Ordine</label>
                    <input type="number" name="sort_order" class="form-control"
                           value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Imagine (opțional)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if (!empty($edit['image_path'])): ?>
                        <img src="/backend/<?= htmlspecialchars($edit['image_path']) ?>" class="img-thumbnail mt-2" style="max-height:80px">
                    <?php endif; ?>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="status" class="form-check-input" id="catStatus"
                        <?= !$edit || $edit['status'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="catStatus">Activă</label>
                </div>
                <button class="btn-brand" type="submit"><?= $edit ? 'Salvează' : 'Adaugă' ?></button>
                <?php if ($edit): ?>
                    <a href="categories.php" class="btn btn-link">Anulează</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- LIST -->
    <div class="col-md-8">
        <div class="card-soft">
            <table class="table align-middle">
                <thead><tr><th>Nume</th><th>Slug</th><th>Produse</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($c['slug']) ?></small></td>
                        <td><?= (int)$c['product_count'] ?></td>
                        <td>
                            <span class="badge bg-<?= $c['status'] ? 'success' : 'secondary' ?>">
                                <?= $c['status'] ? 'Activă' : 'Inactivă' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Ștergi categoria?')">Del</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="5" class="text-muted">Nicio categorie încă.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php adminFooter(); ?>
