<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(false, null, 'Metodă nepermisă', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

/*
====================================================
  VALIDARE CAMPURI
====================================================
*/
$required = ['first_name', 'last_name', 'email', 'phone', 'address', 'city'];

foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, null, "Câmpul {$field} este obligatoriu", 400);
    }
}

$token = getSessionToken();

/*
====================================================
  PRELUARE COȘ (SUPABASE)
====================================================
*/
$cartItems = db()->fetchAll(
    "cart_items",
    "?session_token=eq." . $token
);

if (empty($cartItems)) {
    jsonResponse(false, null, 'Coșul este gol', 400);
}

$subtotal = 0;
$itemsWithProducts = [];

/*
====================================================
  CALCUL TOTAL + PRODUSE
====================================================
*/
foreach ($cartItems as $item) {

    $product = db()->fetchAll(
        "products",
        "?id=eq." . $item['product_id'] . "&limit=1"
    );

    $product = $product[0] ?? null;

    if (!$product) continue;

    $lineTotal = 0;

    if (!($product['price_on_request'] ?? 0)) {
        $lineTotal = $product['price'] * $item['quantity'];
        $subtotal += $lineTotal;
    }

    $itemsWithProducts[] = [
        'product_id' => $item['product_id'],
        'product_name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $item['quantity'],
        'line_total' => $lineTotal
    ];
}

if (empty($itemsWithProducts)) {
    jsonResponse(false, null, 'Coș invalid', 400);
}

$shipping = 0;
$total = $subtotal + $shipping;

$orderNumber = generateOrderNumber();

/*
====================================================
  CREARE COMANDĂ
====================================================
*/
$orderData = [
    'order_number' => $orderNumber,
    'first_name' => sanitize($input['first_name']),
    'last_name' => sanitize($input['last_name']),
    'phone' => sanitize($input['phone']),
    'email' => sanitize($input['email']),
    'county' => sanitize($input['county'] ?? ''),
    'city' => sanitize($input['city']),
    'address' => sanitize($input['address']),
    'notes' => sanitize($input['notes'] ?? ''),
    'subtotal' => $subtotal,
    'shipping' => $shipping,
    'total' => $total,
    'status' => 'noua'
];

$order = db()->insert('orders', $orderData);

$orderId = $order[0]['id'] ?? null;

/*
====================================================
  ADAUGĂ ORDER ITEMS
====================================================
*/
foreach ($itemsWithProducts as $item) {

    db()->insert('order_items', [
        'order_id' => $orderId,
        'product_id' => $item['product_id'],
        'product_name' => $item['product_name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'line_total' => $item['line_total']
    ]);
}

/*
====================================================
  GOLIRE COȘ
====================================================
*/
db()->delete(
    "cart_items",
    "?session_token=eq." . $token
);

/*
====================================================
  EMAIL CLIENT
====================================================
*/
$subject = "Comandă confirmată - {$orderNumber}";

$message =
"Salut {$input['first_name']},\n\n" .
"Comanda ta {$orderNumber} a fost plasată.\n" .
"Total: " . formatPrice($total) . "\n\n" .
"Mulțumim!\n" . SITE_NAME;

mail($input['email'], $subject, $message, "From: " . SITE_EMAIL);

/*
====================================================
  EMAIL ADMIN
====================================================
*/
$adminSubject = "Comandă nouă - {$orderNumber}";

$adminMessage =
"Comandă nouă!\n\n" .
"Număr: {$orderNumber}\n" .
"Client: {$input['first_name']} {$input['last_name']}\n" .
"Total: " . formatPrice($total);

mail(ADMIN_EMAIL, $adminSubject, $adminMessage, "From: " . SITE_EMAIL);

/*
====================================================
  RESPONSE
====================================================
*/
jsonResponse(true, [
    'order_id' => $orderId,
    'order_number' => $orderNumber
], 'Comandă plasată');
?>