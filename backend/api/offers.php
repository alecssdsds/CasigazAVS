<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Metodă nepermisă', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// contact_name e obligatoriu; company_name opțional (persoane fizice)
$required = ['contact_name', 'phone', 'email'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, null, "Câmpul {$field} este obligatoriu", 400);
    }
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, null, 'Email invalid', 400);
}

$productId = !empty($input['product_id']) ? (int)$input['product_id'] : null;
$product = null;

if ($productId) {
    $product = db()->fetchOne(
        'SELECT id, name FROM products WHERE id = ? AND status = 1 LIMIT 1',
        [$productId]
    );
    if (!$product) {
        jsonResponse(false, null, 'Produs inexistent', 404);
    }
}

$offerId = db()->insert('offers', [
    'product_id'   => $productId,
    'company_name' => sanitize($input['company_name'] ?? ''),
    'contact_name' => sanitize($input['contact_name']),
    'phone'        => sanitize($input['phone']),
    'email'        => sanitize($input['email']),
    'message'      => sanitize($input['message'] ?? ''),
    'status'       => 'noua',
]);

/* ---------- EMAIL ADMIN ---------- */
$body =
    "Solicitare ofertă nouă!\n\n" .
    (!empty($input['company_name']) ? "Companie: {$input['company_name']}\n" : '') .
    "Contact: {$input['contact_name']}\n" .
    "Telefon: {$input['phone']}\n" .
    "Email: {$input['email']}\n";

if ($product) {
    $body .= "Produs: {$product['name']}\n";
}
if (!empty($input['message'])) {
    $body .= "\nMesaj:\n{$input['message']}\n";
}

sendMail(ADMIN_EMAIL, 'Solicitare ofertă nouă - Casigaz', $body);

jsonResponse(true, ['offer_id' => $offerId], 'Solicitarea a fost trimisă. Te vom contacta în curând!');
