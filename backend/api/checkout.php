<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Metodă nepermisă', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$required = ['first_name', 'last_name', 'email', 'phone', 'address', 'city'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, null, "Câmpul {$field} este obligatoriu", 400);
    }
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, null, 'Email invalid', 400);
}

$token = getSessionToken();

$cartItems = db()->fetchAll(
    'SELECT ci.product_id, ci.quantity, p.name, p.price, p.price_on_request
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.session_token = ?',
    [$token]
);

if (empty($cartItems)) {
    jsonResponse(false, null, 'Coșul este gol', 400);
}

$subtotal = 0;
$lines = [];
foreach ($cartItems as $item) {
    $lineTotal = $item['price_on_request'] ? 0 : (float)$item['price'] * (int)$item['quantity'];
    $subtotal += $lineTotal;
    $lines[] = [
        'product_id'   => $item['product_id'],
        'product_name' => $item['name'],
        'price'        => (float)$item['price'],
        'quantity'     => (int)$item['quantity'],
        'line_total'   => $lineTotal,
    ];
}

$shipping = 0;
$total = $subtotal + $shipping;
$orderNumber = generateOrderNumber();

$pdo = db()->pdo();
$pdo->beginTransaction();
try {
    $orderId = db()->insert('orders', [
        'order_number' => $orderNumber,
        'first_name'   => sanitize($input['first_name']),
        'last_name'    => sanitize($input['last_name']),
        'email'        => sanitize($input['email']),
        'phone'        => sanitize($input['phone']),
        'address'      => sanitize($input['address']),
        'city'         => sanitize($input['city']),
        'county'       => sanitize($input['county'] ?? ''),
        'postal_code'  => sanitize($input['postal_code'] ?? ''),
        'country'      => sanitize($input['country'] ?? 'România'),
        'company_name' => sanitize($input['company_name'] ?? ''),
        'cui'          => sanitize($input['cui'] ?? ''),
        'notes'        => sanitize($input['notes'] ?? ''),
        'subtotal'     => $subtotal,
        'shipping'     => $shipping,
        'total'        => $total,
        'status'       => 'noua',
    ]);

    foreach ($lines as $line) {
        db()->insert('order_items', [
            'order_id'     => $orderId,
            'product_id'   => $line['product_id'],
            'product_name' => $line['product_name'],
            'price'        => $line['price'],
            'quantity'     => $line['quantity'],
            'line_total'   => $line['line_total'],
        ]);
    }

    db()->delete('cart_items', 'session_token = ?', [$token]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonResponse(false, null, 'Eroare la salvarea comenzii', 500);
}

/* ---------- EMAIL ---------- */
$itemsText = '';
foreach ($lines as $line) {
    $priceTxt = $line['line_total'] > 0 ? formatPrice($line['line_total']) : 'La cerere';
    $itemsText .= "- {$line['product_name']} x{$line['quantity']}  ({$priceTxt})\n";
}

$clientBody =
    "Salut {$input['first_name']},\n\n" .
    "Îți mulțumim pentru comandă! Numărul comenzii tale este {$orderNumber}.\n\n" .
    "Produse:\n{$itemsText}\n" .
    "Total: " . formatPrice($total) . "\n\n" .
    "Te vom contacta în curând pentru confirmare.\n\n" .
    "Echipa " . SITE_NAME;

sendMail($input['email'], "Comandă confirmată - {$orderNumber}", $clientBody);

$adminBody =
    "Comandă nouă pe site!\n\n" .
    "Număr: {$orderNumber}\n" .
    "Client: {$input['first_name']} {$input['last_name']}\n" .
    "Email: {$input['email']}\n" .
    "Telefon: {$input['phone']}\n" .
    "Adresă: {$input['address']}, {$input['city']} " . ($input['county'] ?? '') . "\n" .
    (!empty($input['company_name']) ? "Firmă: {$input['company_name']} (CUI: " . ($input['cui'] ?? '-') . ")\n" : '') .
    "\nProduse:\n{$itemsText}\n" .
    "Total: " . formatPrice($total);

sendMail(ADMIN_EMAIL, "Comandă nouă - {$orderNumber}", $adminBody);

jsonResponse(true, [
    'order_id'     => $orderId,
    'order_number' => $orderNumber,
], 'Comandă plasată cu succes');
