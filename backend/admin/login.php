<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (hash_equals(ADMIN_USER, $username) && hash_equals(ADMIN_PASS, $password)) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = ADMIN_USER;
        header('Location: dashboard.php');
        exit();
    }
    $error = 'Utilizator sau parolă incorectă';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Casigaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3a8a, #10b981); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; border-radius: 1.5rem; padding: 3rem; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .btn-login { background: linear-gradient(135deg, #10b981, #059669); border: none; color: #fff; border-radius: 50px; padding: .75rem; font-weight: 600; width: 100%; }
        .btn-login:hover { background: linear-gradient(135deg, #059669, #047857); }
    </style>
</head>
<body>
<div class="login-card">
    <h3 class="mb-2">🔐 Panou Admin Casigaz</h3>
    <p class="text-muted mb-4">Autentificare sistem</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Utilizator</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Parolă</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn-login">Autentificare</button>
    </form>
</div>
</body>
</html>
