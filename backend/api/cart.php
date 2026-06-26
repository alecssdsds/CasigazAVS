<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$token  = getSessionToken();

/* ==================== GET ==================== */
if ($method === 'GET') {

    $items = db()->fetchAll(
        'SELECT ci.id, ci.product_id, ci.quantity,
                p.name, p.price, p.price_on_request
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.session_token = ?
         ORDER BY ci.id ASC',
        [$token]
    );

    $total = 0;
    foreach ($items as &$item) {
        $item['price'] = (float)$item['price'];
        $item['price_on_request'] = (int)$item['price_on_request'];
        $item['quantity'] = (int)$item['quantity'];
        if (!$item['price_on_request']) {
            $total += $item['price'] * $item['quantity'];
        }
        $item['main_image'] = getProductMainImage($item['product_id']);
    }
    unset($item);

    jsonResponse(true, ['items' => $items, 'total' => $total]);
}

/* ==================== POST (add) ==================== */
if ($method === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $productId = (int)($input['product_id'] ?? 0);
    $quantity  = max(1, (int)($input['quantity'] ?? 1));

    if (!$productId) {
        jsonResponse(false, null, 'ID produs necesar', 400);
    }

    $product = db()->fetchOne(
        'SELECT id, price_on_request FROM products WHERE id = ? AND status = 1 LIMIT 1',
        [$productId]
    );

    if (!$product) {
        jsonResponse(false, null, 'Produs inexistent', 404);
    }
    if ($product['price_on_request']) {
        jsonResponse(false, null, 'Produsul necesită ofertă', 400);
    }

    $existing = db()->fetchOne(
        'SELECT id, quantity FROM cart_items WHERE session_token = ? AND product_id = ? LIMIT 1',
        [$token, $productId]
    );

    if ($existing) {
        db()->update('cart_items',
            ['quantity' => $existing['quantity'] + $quantity],
            'id = ?', [$existing['id']]
        );
    } else {
        db()->insert('cart_items', [
            'session_token' => $token,
            'product_id'    => $productId,
            'quantity'      => $quantity,
        ]);
    }

    jsonResponse(true, ['count' => getCartCount()], 'Produs adăugat în coș');
}

/* ==================== PUT (update qty) ==================== */
if ($method === 'PUT') {

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $cartId   = (int)($input['cart_id'] ?? 0);
    $quantity = max(1, (int)($input['quantity'] ?? 1));

    if (!$cartId) {
        jsonResponse(false, null, 'ID coș necesar', 400);
    }

    db()->update('cart_items',
        ['quantity' => $quantity],
        'id = ? AND session_token = ?', [$cartId, $token]
    );

    jsonResponse(true, null, 'Coș actualizat');
}

/* ==================== DELETE ==================== */
if ($method === 'DELETE') {

    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) {
        jsonResponse(false, null, 'ID produs necesar', 400);
    }

    db()->delete('cart_items',
        'session_token = ? AND product_id = ?', [$token, $productId]
    );

    jsonResponse(true, null, 'Produs eliminat din coș');
}

jsonResponse(false, null, 'Metodă nepermisă', 405);
