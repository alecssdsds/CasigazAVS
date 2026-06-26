<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== SUPABASE (API - RECOMANDAT) ====================

// 🔴 COMPLETEAZĂ AICI CU DATELE TALE DIN SUPABASE
define('SUPABASE_URL', 'https://wdoifaypyxdcenlitpsj.supabase.co/rest/v1/');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indkb2lmYXlweXhkY2VubGl0cHNqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODI0MzE4ODEsImV4cCI6MjA5ODAwNzg4MX0.Umm1oBw2WcQGXrPVswWzkmhRrVutrLKoZ1PSDYoa0Xg');

// ==================== SITE ====================

define('SITE_NAME', 'Casigaz');
define('SITE_URL', 'https://casigaz-serv.ro');
define('SITE_EMAIL', 'contact@casigaz-serv.ro');
define('ADMIN_EMAIL', 'casigazserv@yahoo.com');

// ==================== UPLOAD ====================

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 150 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ==================== COOKIE ====================

define('COOKIE_NAME', 'casigaz_session');
define('COOKIE_EXPIRY', 30 * 24 * 60 * 60);

// ==================== TIMEZONE ====================

date_default_timezone_set('Europe/Bucharest');


// ==================== SUPABASE FUNCTION (IMPORTANT) ====================

function supabaseRequest($table, $method = 'GET', $data = null, $query = '') {

    $url = SUPABASE_URL . "/rest/v1/" . $table . $query;

    $ch = curl_init($url);

    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    if ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "success" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return json_decode($response, true);
}


// ==================== HELPERS ====================

function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

function jsonResponse($success, $data = null, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = ['success' => $success];

    if ($data !== null) $response['data'] = $data;
    if ($message !== null) $response['message'] = $message;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
?>