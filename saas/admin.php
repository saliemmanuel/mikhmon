<?php
session_start();
require 'db.php';
if (!isset($_SESSION['saas_client_id']) || $_SESSION['saas_username'] !== 'admin') {
    header("Location: index.php"); exit;
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_client') {
    $username  = $_POST['username'];
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $vpn_ip    = $_POST['vpn_ip'];
    $vpn_pass  = substr(md5(uniqid()), 0, 10);
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (username, password, vpn_password, vpn_ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $vpn_pass, $vpn_ip]);
        $cmd = "sudo /usr/local/bin/vpn-manager.sh add " . escapeshellarg($username) . " " . escapeshellarg($vpn_pass) . " " . escapeshellarg($vpn_ip);
        shell_exec($cmd);
        $success = "Client \"$username\" créé ! IP VPN : <b>$vpn_ip</b>";
    } catch (\PDOException $e) {
        $error = "Erreur : Ce nom d'utilisateur ou cette adresse IP est déjà utilisé.";
    }
}

$stmt = $pdo->query("SELECT * FROM clients WHERE username != 'admin' ORDER BY id DESC");
$clients = $stmt->fetchAll();

// Couleurs des cartes comme Mikhmon
$colors = ['#26a69a','#7c4dff','#e91e63','#f57c00','#0288d1','#388e3c'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>MIKHMON SaaS - Administration</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/mikhmon-ui.min.css">
    <link rel="icon" href="../img/favicon.png">
    <script src="../js/jquery.min.js"></script>
</head>
<body>
<div class="wrapper">

<!-- Navbar -->
<div id="navbar" class="navbar">
  <div class="navbar-left">
    <a id="brand" class="text-center" href="javascript:void(0)">MIKHMON</a>
    <a id="openNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="closeNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a class="navbar-left" href="javascript:void(0)">Administration SaaS</a>
  </div>
  <div class="navbar-right">
    <a href="logout.php"><i class="fa fa-sign-out mr-1"></i> Déconnexion</a>
  </div>
</div>

<!-- Sidenav -->
<div id="sidenav" class="sidenav">
  <a href="admin.php" class="menu active"><i class="fa fa-tachometer"></i> Tableau de bord</a>
  <a href="admin.php#add" class="menu"><i class="fa fa-plus"></i> Ajouter un client</a>
  <a href="logout.php" class="menu"><i class="fa fa-sign-out"></i> Déconnexion</a>
</div>

<!-- Content -->
<div id="content">
  <div class="card-header"><b><i class="fa fa-users mr-1"></i> Gestion des Clients</b></div>

  <?php if($success): ?>
  <div class="card" style="background:#e8f5e9;border-left:4px solid #4caf50;padding:12px;margin:10px 0;">
      <i class="fa fa-check-circle" style="color:green;"></i> <?= $success ?>
  </div>
  <?php endif; ?>
  <?php if($error): ?>
  <div class="card" style="background:#ffebee;border-left:4px solid #f44336;padding:12px;margin:10px 0;">
      <i class="fa fa-times-circle" style="color:red;"></i> <?= $error ?>
  </div>
  <?php endif; ?>

  <!-- Formulaire Ajout -->
  <div class="card" id="add">
    <div class="card-header"><i class="fa fa-plus mr-1"></i> Nouveau Client</div>
    <div style="padding:15px;">
      <form method="POST">
        <input type="hidden" name="action" value="add_client">
        <table class="table">
          <tr>
            <td>Identifiant</td>
            <td><input type="text" class="form-control" name="username" placeholder="Ex: client1" required></td>
          </tr>
          <tr>
            <td>Mot de passe</td>
            <td><input type="password" class="form-control" name="password" placeholder="Mot de passe" required></td>
          </tr>
          <tr>
            <td>IP VPN fixe</td>
            <td><input type="text" class="form-control" name="vpn_ip" placeholder="Ex: 10.8.0.5" required></td>
          </tr>
          <tr>
            <td colspan="2"><button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i> Créer le client</button></td>
          </tr>
        </table>
      </form>
    </div>
  </div>

  <!-- Liste des clients -->
  <div class="card-header mt-10"><b><i class="fa fa-list mr-1"></i> Liste des clients (<?= count($clients) ?>)</b></div>
  <?php foreach($clients as $i => $c): ?>
  <div class="card" style="background:<?= $colors[$i % count($colors)] ?>;color:#fff;margin:5px 0;">
    <div style="padding:12px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <i class="fa fa-user mr-1"></i> <b><?= htmlspecialchars($c['username']) ?></b><br>
        <small><i class="fa fa-globe mr-1"></i> IP VPN : <?= htmlspecialchars($c['vpn_ip']) ?> &nbsp;|&nbsp; 
        Campay : <?= $c['campay_app_id'] ? '✅ Configuré' : '⚠️ Non configuré' ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($clients)): ?>
  <div class="card" style="padding:20px;text-align:center;color:#999;">
    <i class="fa fa-info-circle"></i> Aucun client pour l'instant. Utilisez le formulaire ci-dessus pour en ajouter un.
  </div>
  <?php endif; ?>

</div><!-- /content -->
</div><!-- /wrapper -->

<script src="../js/mikhmon.js"></script>
<script>
$(document).ready(function(){
  $("#openNav").click(function(){ $("#sidenav").css("width","200px"); $("#content").css("margin-left","200px"); });
  $("#closeNav").click(function(){ $("#sidenav").css("width","0"); $("#content").css("margin-left","0"); });
});
</script>
</body>
</html>
