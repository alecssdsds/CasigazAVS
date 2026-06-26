<?php

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' RON';
}

function generateOrderNumber() {
    return 'CAS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/*
====================================================
  SUPABASE HELPERS (FĂRĂ PDO)
====================================================
*/

function getProductMainImage($productId) {

    $result = db()->fetchAll(
        "product_images",
        "?product_id=eq." . $productId . "&is_main=eq.1&limit=1"
    );

    return $result[0]['image_path'] ?? null;
}

function getProductImages($productId) {

    return db()->fetchAll(
        "product_images",
        "?product_id=eq." . $productId . "&order=sort_order.asc"
    );
}

function getCategoryName($categoryId) {

    if (!$categoryId) return null;

    $result = db()->fetchAll(
        "categories",
        "?id=eq." . $categoryId . "&limit=1"
    );

    return $result[0]['name'] ?? null;
}

function getCartCount() {

    $token = getSessionToken();

    $result = db()->fetchAll(
        "cart_items",
        "?session_token=eq." . $token
    );

    $total = 0;

    foreach ($result as $item) {
        $total += $item['quantity'] ?? 0;
    }

    return $total;
}

function getCartTotal() {

    $token = getSessionToken();

    $cart = db()->fetchAll(
        "cart_items",
        "?session_token=eq." . $token
    );

    $total = 0;

    foreach ($cart as $item) {

        $product = db()->fetchAll(
            "products",
            "?id=eq." . $item['product_id'] . "&limit=1"
        );

        if (!empty($product[0]) && ($product[0]['price_on_request'] ?? 0) == 0) {
            $total += ($item['quantity'] * $product[0]['price']);
        }
    }

    return $total;
}
?>