<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

/*
====================================================
  SUPABASE REQUEST SAFE
====================================================
*/
function supabaseRequest($endpoint) {

    $ch = curl_init(SUPABASE_URL . "/rest/v1/" . $endpoint);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . SUPABASE_SERVICE_ROLE_KEY,
        "Authorization: Bearer " . SUPABASE_SERVICE_ROLE_KEY,
        "Content-Type: application/json"
    ]);

    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $data = json_decode($res, true);

    if ($http >= 400 || !is_array($data)) {
        return [];
    }

    return $data;
}

/*
====================================================
  DATA LOAD
====================================================
*/

// 🔥 full data (for counting)
$products   = supabaseRequest("products?select=id");
$categories = supabaseRequest("categories?select=id");
$newOrders  = supabaseRequest("orders?select=id&status=eq.noua");
$newOffers  = supabaseRequest("offers?select=id&status=eq.noua");

// stock logic (needs full check)
$allProducts = supabaseRequest("products?select=id,stock,status,price_on_request");

// recent data
$recentOrders = supabaseRequest("orders?select=*&order=created_at.desc&limit=5");
$recentOffers = supabaseRequest("offers?select=*,products(name)&order=created_at.desc&limit=5");

/*
====================================================
  STATS SAFE
====================================================
*/
$totalProducts   = count($products);
$totalCategories = count($categories);
$newOrdersCount  = count($newOrders);
$newOffersCount  = count($newOffers);

$outOfStock = 0;

foreach ($allProducts as $p) {
    if (($p['stock'] ?? 0) <= 0 && ($p['status'] ?? 0) == 1 && ($p['price_on_request'] ?? 0) == 0) {
        $outOfStock++;
    }
}
?>