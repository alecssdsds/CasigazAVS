<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = db();

/*
====================================================
  UPDATE STATUS ORDER
====================================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {

    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $allowed = ['noua', 'confirmata', 'procesare', 'expediata', 'livrata', 'anulata'];

    if (in_array($status, $allowed)) {

        $db->update(
            'orders',
            ['status' => $status],
            "?id=eq.$orderId"
        );

        header("Location: orders.php?msg=updated");
        exit();
    }
}

/*
====================================================
  GET ORDERS (Supabase format)
====================================================
*/
$orders = $db->fetchAll(
    'orders',
    "?order=created_at.desc"
);
?>