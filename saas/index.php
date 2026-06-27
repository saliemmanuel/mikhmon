<?php
session_start();
if (isset($_SESSION['saas_client_id'])) {
    if ($_SESSION['saas_username'] === 'admin') header("Location: admin.php");
    else header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db.php';
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['saas_client_id'] = $user['id'];
        $_SESSION['saas_username'] = $user['username'];
        if ($user['username'] === 'admin') header("Location: admin.php");
        else header("Location: dashboard.php");
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MIKHMON SaaS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/mikhmon-ui.min.css">
    <link rel="icon" href="../img/favicon.png">
    <script src="../js/jquery.min.js"></script>
    <style>
        .login-wrap { display:flex; align-items:center; justify-content:center; height:100vh; background:#f4f4f4; }
        .login-box { background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.15); width:100%; max-width:380px; }
        .login-box h2 { text-align:center; margin-bottom:20px; font-size:22px; color:#333; }
        .login-box input { width:100%; padding:10px; margin-bottom:12px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; font-size:14px; }
        .login-box button { width:100%; padding:11px; background:#009688; color:#fff; border:none; border-radius:4px; font-size:15px; cursor:pointer; }
        .login-box button:hover { background:#00796b; }
        .error-msg { color:red; text-align:center; margin-bottom:10px; font-size:13px; }
        .brand-title { text-align:center; font-size:26px; font-weight:bold; letter-spacing:2px; color:#009688; margin-bottom:5px; }
        .brand-sub { text-align:center; font-size:12px; color:#aaa; margin-bottom:25px; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <div class="brand-title"><i class="fa fa-wifi"></i> MIKHMON</div>
        <div class="brand-sub">Plateforme SaaS Multi-Clients</div>
        <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit"><i class="fa fa-sign-in"></i> Se connecter</button>
        </form>
    </div>
</div>
</body>
</html>
