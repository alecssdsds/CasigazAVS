<?php
require_once __DIR__ . '/database.php';

/*
====================================================
  GENERAL HELPERS
====================================================
*/
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function createSlug($string) {
    $string = (string)$string;
    // transliterare diacritice românești
    $string = strtr($string, [
        'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
        'Ă'=>'a','Â'=>'a','Î'=>'i','Ș'=>'s','Ş'=>'s','Ț'=>'t','Ţ'=>'t',
    ]);
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    return trim($string, '-');
}

function uniqueProductSlug($name, $ignoreId = null) {
    $base = createSlug($name);
    if ($base === '') $base = 'produs';
    $slug = $base;
    $i = 2;
    while (true) {
        if ($ignoreId) {
            $row = db()->fetchOne('SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $ignoreId]);
        } else {
            $row = db()->fetchOne('SELECT id FROM products WHERE slug = ? LIMIT 1', [$slug]);
        }
        if (!$row) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function uniqueCategorySlug($name, $ignoreId = null) {
    $base = createSlug($name);
    if ($base === '') $base = 'categorie';
    $slug = $base;
    $i = 2;
    while (true) {
        if ($ignoreId) {
            $row = db()->fetchOne('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $ignoreId]);
        } else {
            $row = db()->fetchOne('SELECT id FROM categories WHERE slug = ? LIMIT 1', [$slug]);
        }
        if (!$row) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function formatPrice($price) {
    return number_format((float)$price, 2, ',', '.') . ' RON';
}

function generateOrderNumber() {
    return 'CAS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/*
====================================================
  PRODUCT / IMAGE HELPERS
====================================================
*/
function getProductMainImage($productId) {
    $row = db()->fetchOne(
        'SELECT image_path FROM product_images
         WHERE product_id = ?
         ORDER BY is_main DESC, sort_order ASC, id ASC
         LIMIT 1',
        [$productId]
    );
    return $row['image_path'] ?? null;
}

function getProductImages($productId) {
    return db()->fetchAll(
        'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
        [$productId]
    );
}

function getCategoryName($categoryId) {
    if (!$categoryId) return null;
    $row = db()->fetchOne('SELECT name FROM categories WHERE id = ? LIMIT 1', [$categoryId]);
    return $row['name'] ?? null;
}

/*
====================================================
  CART HELPERS
====================================================
*/
function getCartCount() {
    $token = getSessionToken();
    $row = db()->fetchOne(
        'SELECT COALESCE(SUM(quantity),0) AS c FROM cart_items WHERE session_token = ?',
        [$token]
    );
    return (int)($row['c'] ?? 0);
}

function getCartTotal() {
    $token = getSessionToken();
    $row = db()->fetchOne(
        'SELECT COALESCE(SUM(ci.quantity * p.price),0) AS t
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.session_token = ? AND p.price_on_request = 0',
        [$token]
    );
    return (float)($row['t'] ?? 0);
}

/*
====================================================
  EMAIL HELPER (UTF-8)
====================================================
*/
function sendMail($to, $subject, $body) {
    $headers  = 'From: ' . SITE_NAME . ' <' . SITE_EMAIL . ">\r\n";
    $headers .= 'Reply-To: ' . SITE_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    return @mail($to, $encodedSubject, $body, $headers);
}
