<?php

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $productId > 0;

/*
====================================================
  LOAD PRODUCT
====================================================
*/
$product = null;

if ($isEdit) {
    $res = db()->fetchAll(
        "products",
        "?id=eq." . $productId . "&limit=1"
    );
    $product = $res[0] ?? null;

    if (!$product) {
        header('Location: products.php');
        exit();
    }
}

/*
====================================================
  LOAD CATEGORIES
====================================================
*/
$categories = db()->fetchAll(
    "categories",
    "?status=eq.1&order=name.asc"
);

/*
====================================================
  LOAD IMAGES
====================================================
*/
$images = [];

if ($isEdit) {
    $images = db()->fetchAll(
        "product_images",
        "?product_id=eq." . $productId . "&order=sort_order.asc"
    );
}

/*
====================================================
  SAVE PRODUCT
====================================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitize($_POST['name']);
    $slug = createSlug($name);

    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    $data = [
        'name' => $name,
        'slug' => $slug,
        'category_id' => $category_id,
        'short_description' => sanitize($_POST['short_description'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'sku' => sanitize($_POST['sku'] ?? ''),
        'price' => (float)($_POST['price'] ?? 0),
        'old_price' => !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null,
        'price_on_request' => isset($_POST['price_on_request']) ? 1 : 0,
        'stock' => (int)($_POST['stock'] ?? 0),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'status' => isset($_POST['status']) ? 1 : 0,
        'seo_title' => sanitize($_POST['seo_title'] ?? ''),
        'seo_description' => sanitize($_POST['seo_description'] ?? '')
    ];

    /*
    -------------------------
    UPDATE / INSERT
    -------------------------
    */
    if ($isEdit) {

        db()->update(
            "products",
            $data,
            "?id=eq." . $productId
        );

    } else {

        $created = db()->insert("products", $data);
        $productId = $created[0]['id'] ?? null;
        $isEdit = true;
    }

    /*
    -------------------------
    UPLOAD IMAGES
    -------------------------
    */
    if (!empty($_FILES['images'])) {

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {

            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {

                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'tmp_name' => $tmpName,
                    'error' => 0,
                    'size' => $_FILES['images']['size'][$key]
                ];

                $result = uploadProductImage($file, $productId);

                if ($result['success']) {

                    $existing = db()->fetchAll(
                        "product_images",
                        "?product_id=eq." . $productId
                    );

                    $isMain = empty($existing) ? 1 : 0;

                    db()->insert("product_images", [
                        'product_id' => $productId,
                        'image_path' => $result['path'],
                        'is_main' => $isMain,
                        'sort_order' => count($existing)
                    ]);
                }
            }
        }
    }

    /*
    -------------------------
    SET MAIN IMAGE
    -------------------------
    */
    if (!empty($_POST['set_main'])) {

        $imageId = (int)$_POST['set_main'];

        db()->update(
            "product_images",
            ["is_main" => 0],
            "?product_id=eq." . $productId
        );

        db()->update(
            "product_images",
            ["is_main" => 1],
            "?id=eq." . $imageId
        );
    }

    /*
    -------------------------
    DELETE IMAGE
    -------------------------
    */
    if (!empty($_POST['delete_image'])) {

        $imageId = (int)$_POST['delete_image'];

        $img = db()->fetchAll(
            "product_images",
            "?id=eq." . $imageId . "&limit=1"
        );

        $img = $img[0] ?? null;

        if ($img) {
            deleteImage($img['image_path']);
            db()->delete("product_images", "?id=eq." . $imageId);
        }
    }

    header('Location: product-edit.php?id=' . $productId . '&msg=saved');
    exit();
}

?>