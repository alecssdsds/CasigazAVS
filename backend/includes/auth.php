<?php

/*
====================================================
  ADMIN AUTH HELPERS (SAFE VERSION)
====================================================
*/

// asigură session pornit
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
====================================================
  PROTECT ADMIN PAGES
====================================================
*/
function requireAdmin() {

    if (empty($_SESSION['admin_id'])) {

        header('Location: /backend/admin/login.php');
        exit();
    }
}

/*
====================================================
  CHECK IF ADMIN LOGGED IN
====================================================
*/
function isAdmin() {
    return !empty($_SESSION['admin_id']);
}

/*
====================================================
  GET ADMIN ID
====================================================
*/
function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/*
====================================================
  GET ADMIN USERNAME
====================================================
*/
function getAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/*
====================================================
  LOGOUT (UTIL)
====================================================
*/
function logoutAdmin() {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
?>