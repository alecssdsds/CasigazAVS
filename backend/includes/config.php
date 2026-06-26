<?php
/*
====================================================
  CASIGAZ - CONFIG (MySQL 8.0)
====================================================
*/

// --- Erori (dezactivează display_errors în producție) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== DATABASE ====================
// IONOS: numele bazei de date este de regulă și username-ul.
// Dacă hostul nu este "localhost", schimbă DB_HOST cu hostul din panoul IONOS
// (ex: db5017xxxxx.hosting-data.io).
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '01144012_avs');
define('DB_USER', getenv('DB_USER') ?: '01144012_avs');
define('DB_PASS', getenv('DB_PASS') ?: 'AVSolutions2026Parola');
define('DB_CHARSET', 'utf8mb4');

// ==================== SITE ====================
define('SITE_NAME', 'Casigaz');
define('SITE_URL', 'https://casigaz-serv.ro');
define('SITE_EMAIL', 'contact@casigaz-serv.ro');
define('ADMIN_EMAIL', 'casigazserv@yahoo.com');

// ==================== ADMIN LOGIN ====================
define('ADMIN_USER', 'Casigaz');
define('ADMIN_PASS', 'Casigaz2026');

// ==================== UPLOAD ====================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/backend/uploads/');
define('MAX_FILE_SIZE', 15 * 1024 * 1024); // 15 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ==================== COOKIE / SESSION ====================
define('COOKIE_NAME', 'casigaz_session');
define('COOKIE_EXPIRY', 30 * 24 * 60 * 60); // 30 zile

// ==================== TIMEZONE ====================
date_default_timezone_set('Europe/Bucharest');

/*
====================================================
  HTTP HELPERS
====================================================
*/
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

function jsonResponse($success, $data = null, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => (bool)$success];

    // Permitem ca $data să fie un array care se contopește în răspuns
    // (ex: ['items' => ..., 'total' => ...]) pentru compatibilitate cu frontend-ul.
    if (is_array($data)) {
        $response = array_merge($response, $data);
    } elseif ($data !== null) {
        $response['data'] = $data;
    }

    if ($message !== null) $response['message'] = $message;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
