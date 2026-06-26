<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$token = getSessionToken();

/*
====================================================
  GET CART ITEMS
====================================================
*/
if ($method === 'GET') {

    $items = db()->fetchAll(
        "cart_items",
        "?session_token=eq." . $token
    );

    $total = 0;

    foreach ($items as &$item) {

        $product = db()->fetchAll(
            "products",
            "?id=eq." . $item['product_id'] . "&limit=1"
        );

        $product = $product[0] ?? null;

        if ($product) {

            if (!($product['price_on_request'] ?? 0)) {
                $total += $product['price'] * $item['quantity'];
            }

            $item['name'] = $product['name'];
            $item['price'] = $product['price'];
            $item['price_on_request'] = $product['price_on_request'];
        }

        $item['main_image'] = getProductMainImage($item['product_id']);
    }

    jsonResponse(true, [
        'items' => $items,
        'total' => $total
    ]);
}

/*
====================================================
  ADD TO CART
====================================================
*/
if ($method === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    $productId = (int)($input['product_id'] ?? 0);
    $quantity = max(1, (int)($input['quantity'] ?? 1));

    if (!$productId) {
        jsonResponse(false, null, 'ID produs necesar', 400);
    }

    $product = db()->fetchAll(
        "products",
        "?id=eq." . $productId . "&status=eq.1&limit=1"
    );

    $product = $product[0] ?? null;

    if (!$product) {
        jsonResponse(false, null, 'Produs inexistent', 404);
    }

    if ($product['price_on_request']) {
        jsonResponse(false, null, 'Produsul necesită ofertă', 400);
    }

    $existing = db()->fetchAll(
        "cart_items",
        "?session_token=eq." . $token . "&product_id=eq." . $productId . "&limit=1"
    );

    if (!empty($existing)) {

        $item = $existing[0];

        db()->update(
            "cart_items",
            [
                "quantity" => $item['quantity'] + $quantity
            ],
            "?id=eq." . $item['id']
        );

    } else {

        db()->insert("cart_items", [
            "session_token" => $token,
            "product_id" => $productId,
            "quantity" => $quantity
        ]);
    }

    jsonResponse(true, null, 'Produs adăugat în coș');
}

/*
====================================================
  UPDATE CART ITEM
====================================================
*/
if ($method === 'PUT') {

    $input = json_decode(file_get_contents('php://input'), true);

    $cartId = (int)($input['cart_id'] ?? 0);
    $quantity = max(1, (int)($input['quantity'] ?? 1));

    if (!$cartId) {
        jsonResponse(false, null, 'ID coș necesar', 400);
    }

    db()->update(
        "cart_items",
        ["quantity" => $quantity],
        "?id=eq." . $cartId . "&session_token=eq." . $token
    );

    jsonResponse(true, null, 'Coș actualizat');
}

/*
====================================================
  DELETE FROM CART
====================================================
*/
if ($method === 'DELETE') {

    $productId = (int)($_GET['product_id'] ?? 0);

    if (!$productId) {
        jsonResponse(false, null, 'ID produs necesar', 400);
    }

    db()->delete(
        "cart_items",
        "?session_token=eq." . $token . "&product_id=eq." . $productId
    );

    jsonResponse(true, null, 'Produs eliminat din coș');
}

jsonResponse(false, null, 'Metodă nepermisă', 405);
?>