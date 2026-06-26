<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$SUPABASE_URL = SUPABASE_URL;
$SUPABASE_KEY = SUPABASE_SERVICE_ROLE_KEY;

function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $ch = curl_init($SUPABASE_URL . "/rest/v1/" . $endpoint);

    $headers = [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $allowed = ['noua', 'confirmata', 'procesare', 'expediata', 'livrata', 'anulata'];

    if (in_array($status, $allowed)) {
        supabaseRequest(
            "orders?id=eq.$orderId",
            "PATCH",
            ["status" => $status]
        );

        header("Location: orders.php?msg=updated");
        exit();
    }
}

$orders = supabaseRequest("orders?select=*&order=created_at.desc");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Comenzi - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container-fluid">
<div class="row">

<div class="col-md-2 bg-dark text-white p-3">
    <h4>Admin</h4>
    <a class="text-white d-block" href="products.php">Produse</a>
    <a class="text-white d-block" href="orders.php">Comenzi</a>
    <a class="text-white d-block" href="offers.php">Oferte</a>
</div>

<div class="col-md-10 p-4">

<h2>Comenzi</h2>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">Status actualizat</div>
<?php endif; ?>

<table class="table table-bordered">
<thead>
<tr>
<th>#</th>
<th>Client</th>
<th>Email</th>
<th>Total</th>
<th>Status</th>
<th>Data</th>
<th>Acțiuni</th>
</tr>
</thead>

<tbody>
<?php foreach ($orders as $o): ?>
<tr>
<td><?= $o['order_number'] ?></td>
<td><?= $o['first_name'] . ' ' . $o['last_name'] ?></td>
<td><?= $o['email'] ?></td>
<td><?= number_format($o['total'], 2) ?> RON</td>
<td><?= $o['status'] ?></td>
<td><?= $o['created_at'] ?></td>

<td>
<form method="POST">
<input type="hidden" name="order_id" value="<?= $o['id'] ?>">
<select name="status" class="form-select form-select-sm">
<option value="noua">Nouă</option>
<option value="confirmata">Confirmată</option>
<option value="procesare">Procesare</option>
<option value="expediata">Expediată</option>
<option value="livrata">Livrată</option>
<option value="anulata">Anulată</option>
</select>
<button class="btn btn-sm btn-success mt-1">Update</button>
</form>
</td>

</tr>
<?php endforeach; ?>
</tbody>

</table>

</div>
</div>
</div>

</body>
</html>