<?php
require_once __DIR__ . '/config.php';

class Database {

    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*
    ====================================================
      CORE REQUEST SUPABASE
    ====================================================
    */
    private function request($table, $method = 'GET', $data = null, $query = '') {

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

        // methods
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return [
                "success" => false,
                "message" => curl_error($ch)
            ];
        }

        // IMPORTANT: fără curl_close (PHP 8+)

        $decoded = json_decode($response, true);

        // dacă Supabase dă eroare
        if ($httpCode >= 400) {
            return [
                "success" => false,
                "status" => $httpCode,
                "response" => $decoded,
                "raw" => $response
            ];
        }

        return $decoded ?? [];
    }

    /*
    ====================================================
      PUBLIC METHODS
    ====================================================
    */

    public function fetchAll($table, $query = '') {
        return $this->request($table, 'GET', null, $query);
    }

    public function fetchOne($table, $query = '') {
        $result = $this->request($table, 'GET', null, $query);
        return $result[0] ?? null;
    }

    public function insert($table, $data) {
        return $this->request($table, 'POST', $data);
    }

    public function update($table, $data, $query) {
        return $this->request($table, 'PATCH', $data, $query);
    }

    public function delete($table, $query) {
        return $this->request($table, 'DELETE', null, $query);
    }

    /*
    ====================================================
      🔥 NEW: UPDATE ADMIN (username + password)
    ====================================================
    */
    public function updateAdmin($id, $username, $password = null) {

        $data = [
            "username" => $username
        ];

        if (!empty($password)) {
            $data["password_hash"] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $this->request(
            "_admins",
            "PATCH",
            $data,
            "?id=eq." . $id
        );
    }
}

/*
====================================================
  HELPER
====================================================
*/
function db() {
    return Database::getInstance();
}

/*
====================================================
  SESSION TOKEN
====================================================
*/
function getSessionToken() {
    if (!isset($_COOKIE[COOKIE_NAME])) {
        $token = bin2hex(random_bytes(32));
        setcookie(COOKIE_NAME, $token, time() + COOKIE_EXPIRY, '/', '', false, true);
        $_COOKIE[COOKIE_NAME] = $token;
    }
    return $_COOKIE[COOKIE_NAME];
}
?>