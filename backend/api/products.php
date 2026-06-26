<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(false, null, 'Metodă nepermisă', 405);
}

/*
====================================================
  PARAMETRI
====================================================
*/
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 12);
$offset = ($page - 1) * $limit;

$category = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');

$query = "?status=eq.1";

/*
====================================================
  FILTRU CATEGORIE
====================================================
*/
if ($category) {

    $cat = db()->fetchAll(
        "categories",
        "?slug=eq." . urlencode($category) . "&limit=1"
    );

    $catId = $cat[0]['id'] ?? null;

    if ($catId) {
        $query .= "&category_id=eq." . $catId;
    }
}

/*
====================================================
  FETCH PRODUSE
====================================================
*/
$products = db()->fetchAll(
    "products",
    $query . "&order=featured.desc,created_at.desc"
);

/*
====================================================
  SEARCH (FILTRARE PHP)
====================================================
*/
if ($search) {

    $searchLower = strtolower($search);

    $products = array_filter($products, function ($p) use ($searchLower) {
        return (
            str_contains(strtolower($p['name']), $searchLower) ||
            str_contains(strtolower($p['short_description'] ?? ''), $searchLower)
        );
    });
}

/*
====================================================
  TOTAL + PAGINARE
====================================================
*/
$total = count($products);
$pages = ceil($total / $limit);

/*
====================================================
  SLICE PAGINARE
====================================================
*/
$products = array_slice($products, $offset, $limit);

/*
====================================================
  ADAUGĂ IMAGINI + URL
====================================================
*/
foreach ($products as &$product) {

    $image = getProductMainImage($product['id']);

    $product['image'] = $image ? '/backend/' . $image : null;
    $product['url'] = '/shop.html?product=' . $product['slug'];
}

/*
====================================================
  RESPONSE
====================================================
*/
jsonResponse(true, [
    'data' => array_values($products),
    'page' => $page,
    'pages' => $pages,
    'total' => $total
]);
?>