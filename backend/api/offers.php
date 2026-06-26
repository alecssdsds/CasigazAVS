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
$required = ['company_name', 'contact_name', 'phone', 'email'];

foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, null, "Câmpul {$field} este obligatoriu", 400);
    }
}

$productId = !empty($input['product_id']) ? (int)$input['product_id'] : null;

/*
====================================================
  VERIFICARE PRODUS (SUPABASE)
====================================================
*/
$product = null;

if ($productId) {

    $result = db()->fetchAll(
        "products",
        "?id=eq." . $productId . "&status=eq.1&limit=1"
    );

    $product = $result[0] ?? null;

    if (!$product) {
        jsonResponse(false, null, 'Produs inexistent', 404);
    }
}

/*
====================================================
  CREARE OFERTĂ
====================================================
*/
$offerData = [
    'product_id' => $productId,
    'company_name' => sanitize($input['company_name']),
    'contact_name' => sanitize($input['contact_name']),
    'phone' => sanitize($input['phone']),
    'email' => sanitize($input['email']),
    'message' => sanitize($input['message'] ?? ''),
    'status' => 'noua'
];

$offer = db()->insert('offers', $offerData);

$offerId = $offer[0]['id'] ?? null;

/*
====================================================
  EMAIL ADMIN
====================================================
*/
$subject = "Solicitare ofertă nouă - Casigaz";

$message =
"Solicitare ofertă nouă!\n\n" .
"Companie: {$input['company_name']}\n" .
"Contact: {$input['contact_name']}\n" .
"Telefon: {$input['phone']}\n" .
"Email: {$input['email']}\n";

if ($product) {
    $message .= "Produs: " . $product['name'] . "\n";
}

if (!empty($input['message'])) {
    $message .= "\nMesaj:\n" . $input['message'] . "\n";
}

mail(ADMIN_EMAIL, $subject, $message, "From: " . SITE_EMAIL);

/*
====================================================
  RESPONSE
====================================================
*/
jsonResponse(true, [
    'offer_id' => $offerId
], 'Solicitarea a fost trimisă');
?>