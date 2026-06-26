<?php
require_once __DIR__ . '/config.php';

/*
====================================================
  DATABASE - PDO MySQL (singleton)
====================================================
*/
class Database {

    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Nu expunem credențialele în mesaj.
            if (PHP_SAPI === 'cli') {
                die('DB connection failed: ' . $e->getMessage());
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Eroare conexiune bază de date']);
            exit();
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo() {
        return $this->pdo;
    }

    /* ---------- CORE ---------- */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /* ---------- CRUD HELPERS ---------- */
    public function insert($table, $data) {
        $cols         = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);

        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $cols) . '`) '
             . 'VALUES (' . implode(', ', $placeholders) . ')';

        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }

        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        // Totul pe placeholdere poziționale (?) ca să nu amestecăm cu WHERE.
        $set = [];
        $params = [];

        foreach ($data as $k => $v) {
            $set[] = '`' . $k . '` = ?';
            $params[] = $v;
        }

        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE ' . $where;

        foreach ($this->normalizeWhereParams($whereParams) as $p) {
            $params[] = $p;
        }

        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = 'DELETE FROM `' . $table . '` WHERE ' . $where;
        return $this->query($sql, $this->normalizeWhereParams($whereParams))->rowCount();
    }

    private function normalizeWhereParams($params) {
        if (!is_array($params)) return [$params];
        return $params;
    }
}

/* ---------- HELPER ---------- */
function db() {
    return Database::getInstance();
}

/*
====================================================
  SESSION TOKEN (pentru coș anonim)
====================================================
*/
function getSessionToken() {
    if (empty($_COOKIE[COOKIE_NAME])) {
        $token = bin2hex(random_bytes(32));
        setcookie(COOKIE_NAME, $token, [
            'expires'  => time() + COOKIE_EXPIRY,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[COOKIE_NAME] = $token;
    }
    return $_COOKIE[COOKIE_NAME];
}
