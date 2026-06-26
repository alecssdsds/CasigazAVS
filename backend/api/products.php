<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Metodă nepermisă', 405);
}

/* ---- Detaliu produs (?slug=...) ---- */
$slug = trim($_GET['slug'] ?? $_GET['product'] ?? '');
if ($slug !== '') {
    $p = db()->fetchOne(
        'SELECT p.*, c.name AS category, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.status = 1
         LIMIT 1',
        [$slug]
    );
    if (!$p) {
        jsonResponse(false, null, 'Produs inexistent', 404);
    }

    $images = getProductImages($p['id']);
    $p['images'] = array_map(fn($i) => '/backend/' .$i['image_path'], $images);
    $main = getProductMainImage($p['id']);
    $p['image'] = $main ? '/backend/' .$main : null;
    $p['url'] = '/shop.html?product=' . $p['slug'];
    $p['price_on_request'] = (int)$p['price_on_request'];
    $p['price'] = (float)$p['price'];

    jsonResponse(true, ['data' => $p]);
}

/* ---- Listare ---- */
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, min(60, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search'] ?? '');
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;

$where  = ['p.status = 1'];
$params = [];

if ($category !== '') {
    // categoria poate veni ca slug sau ca nume (frontend trimite numele)
    $where[] = '(c.slug = ? OR c.name = ?)';
    $params[] = $category;
    $params[] = $category;
}

if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.short_description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($featured === 1) {
    $where[] = 'p.featured = 1';
}

$whereSql = implode(' AND ', $where);

$totalRow = db()->fetchOne(
    "SELECT COUNT(*) AS c
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql",
    $params
);
$total = (int)($totalRow['c'] ?? 0);
$pages = (int)ceil($total / $limit);

$rows = db()->fetchAll(
    "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.old_price,
            p.price_on_request, p.stock, p.in_stock, p.featured,
            c.name AS category, c.slug AS category_slug
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY p.featured DESC, p.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

foreach ($rows as &$product) {
    $image = getProductMainImage($product['id']);
    $product['image'] = $image ? '/backend/' .$image : null;
    $product['url']   = '/shop.html?product=' . $product['slug'];
    $product['price'] = (float)$product['price'];
    $product['price_on_request'] = (int)$product['price_on_request'];
    $product['in_stock'] = (int)$product['in_stock'];
}
unset($product);

jsonResponse(true, [
    'data'  => $rows,
    'page'  => $page,
    'pages' => $pages,
    'total' => $total,
]);
