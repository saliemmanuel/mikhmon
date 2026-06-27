<?php
session_start();
require 'db.php';
if (!isset($_SESSION['saas_client_id']) || $_SESSION['saas_username'] === 'admin') {
    header("Location: index.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$_SESSION['saas_client_id']]);
$client = $stmt->fetch();

// Sauvegarder les clés Campay si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['campay_app_id'])) {
    $stmt = $pdo->prepare("UPDATE clients SET campay_app_id=?, campay_app_secret=? WHERE id=?");
    $stmt->execute([$_POST['campay_app_id'], $_POST['campay_app_secret'], $client['id']]);
    $client['campay_app_id'] = $_POST['campay_app_id'];
    $campay_saved = true;
}

$domain = $_SERVER['HTTP_HOST'];
// Clé publique du serveur WireGuard (générée lors du setup-saas.sh)
$server_public_key = "RJpxC0yH9VCYPHrjDsxm0iWGEy12THrPzKyDiDIAXGk=";
$server_ip = "13.51.55.40";
$wg_port = "51820";

// Clés WireGuard du client (générées automatiquement à la création du compte)
$client_private_key = $client['wg_private_key'] ?? 'Non généré - Recréez le compte';
$client_vpn_ip = $client['vpn_ip'];

$mikrotik_script = "/interface wireguard add name=wg-saas private-key=\"$client_private_key\"
/interface wireguard peers add interface=wg-saas public-key=\"$server_public_key\" endpoint-address=$server_ip endpoint-port=$wg_port allowed-address=10.8.0.0/24 persistent-keepalive=25
/ip address add address=$client_vpn_ip/24 interface=wg-saas
/ip route add distance=1 dst-address=10.8.0.1/32 gateway=wg-saas";

// Récupérer les forfaits
$stmt_plans = $pdo->prepare("SELECT * FROM plans WHERE client_id = ?");
$stmt_plans->execute([$client['id']]);
$plans = $stmt_plans->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>MIKHMON SaaS - Espace Client</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/mikhmon-ui.blue.min.css">
    <link rel="icon" href="../img/favicon.png">
    <script src="../js/jquery.min.js"></script>
    <style>
        .tuto-step { padding:8px 0; border-bottom:1px solid #f0f0f0; }
        .tuto-step:last-child { border-bottom:none; }
        .script-box { background:#1e1e1e; color:#00e676; padding:15px; border-radius:5px; font-family:monospace; font-size:13px; white-space:pre-wrap; word-break:break-all; }
        .copy-btn { cursor:pointer; float:right; background:#009688; color:#fff; border:none; padding:5px 10px; border-radius:3px; font-size:12px; }
    </style>
</head>
<body>
<div class="wrapper">

<!-- Navbar -->
<div id="navbar" class="navbar">
  <div class="navbar-left">
    <a id="brand" class="text-center" href="javascript:void(0)">MIKHMON</a>
    <a id="openNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a id="closeNav" class="navbar-hover" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
    <a class="navbar-left" href="javascript:void(0)">Espace Client</a>
  </div>
  <div class="navbar-right">
    <span style="padding:0 10px;"><i class="fa fa-user mr-1"></i> <?= htmlspecialchars($client['username']) ?></span>
    <a href="logout.php"><i class="fa fa-sign-out mr-1"></i> Déconnexion</a>
  </div>
</div>

<!-- Sidenav -->
<div id="sidenav" class="sidenav">
  <div class="menu text-center" style="padding:15px;font-size:13px;"><b><?= htmlspecialchars($client['username']) ?></b><br><small>IP VPN : <?= $client['vpn_ip'] ?></small></div>
  <a href="#mikrotik" class="menu"><i class="fa fa-plug"></i> Connexion MikroTik</a>
  <a href="#campay" class="menu"><i class="fa fa-credit-card"></i> Paiement Campay</a>
  <a href="#boutique" class="menu"><i class="fa fa-shopping-cart"></i> Ma Boutique</a>
  <a href="logout.php" class="menu"><i class="fa fa-sign-out"></i> Déconnexion</a>
</div>

<!-- Content -->
<div id="content">

  <?php if(isset($campay_saved)): ?>
  <div class="card" style="background:#e8f5e9;border-left:4px solid #4caf50;padding:12px;margin:10px 0;">
    <i class="fa fa-check-circle" style="color:green;"></i> Identifiants Campay sauvegardés avec succès !
  </div>
  <?php endif; ?>

  <!-- Étape 1 : MikroTik -->
  <a name="mikrotik"></a>
  <div class="card-header"><b><i class="fa fa-plug mr-1"></i> Étape 1 : Connecter votre routeur MikroTik</b></div>
  <div class="card" style="padding:15px;">
    <div style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px;border-radius:4px;margin-bottom:15px;">
      <div class="tuto-step"><b>1️⃣</b> Ouvrez votre logiciel <b>Winbox</b> et connectez-vous à votre routeur.</div>
      <div class="tuto-step"><b>2️⃣</b> Dans le menu de gauche, cliquez sur <b>"New Terminal"</b>.</div>
      <div class="tuto-step"><b>3️⃣</b> Copiez le code ci-dessous, faites un <b>clic-droit → Paste</b> dans la fenêtre noire.</div>
      <div class="tuto-step"><b>4️⃣</b> Appuyez sur la touche <b>Entrée</b>. C'est tout !</div>
    </div>
    <button class="copy-btn" onclick="copyScript()"><i class="fa fa-copy mr-1"></i> Copier</button>
    <div class="script-box" id="scriptBox"><?= htmlspecialchars($mikrotik_script) ?></div>
    <p style="color:#777;font-size:12px;margin-top:10px;"><i class="fa fa-info-circle mr-1"></i> Une fois exécuté, votre routeur sera connecté à la plateforme avec l'IP interne : <b><?= $client['vpn_ip'] ?></b></p>
  </div>

  <!-- Étape 2 : Campay -->
  <a name="campay"></a>
  <div class="card-header mt-10"><b><i class="fa fa-credit-card mr-1"></i> Étape 2 : Configuration Campay</b></div>
  <div class="card" style="padding:15px;">
    <p>Entrez vos identifiants API Campay pour que vos clients puissent payer directement sur votre compte Mobile Money.</p>
    <form method="POST">
      <table class="table">
        <tr>
          <td>App ID (Username)</td>
          <td><input type="text" class="form-control" name="campay_app_id" value="<?= htmlspecialchars($client['campay_app_id'] ?? '') ?>" placeholder="Votre App ID Campay" required></td>
        </tr>
        <tr>
          <td>App Secret (Password)</td>
          <td><input type="password" class="form-control" name="campay_app_secret" value="<?= htmlspecialchars($client['campay_app_secret'] ?? '') ?>" placeholder="Votre App Secret Campay" required></td>
        </tr>
        <tr>
          <td colspan="2"><button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i> Enregistrer</button></td>
        </tr>
      </table>
    </form>
  </div>

  <!-- Étape 3 : Boutique -->
  <a name="boutique"></a>
  <div class="card-header mt-10"><b><i class="fa fa-shopping-cart mr-1"></i> Étape 3 : Votre Boutique en ligne</b></div>
  <div class="card" style="padding:15px;">
    <p>Voici le lien public de votre boutique à partager avec vos clients :</p>
    <div class="script-box" style="font-size:15px;padding:12px;">http://<?= $domain ?>/saas/shop/?client=<?= $client['id'] ?></div>
    <br>
    <?php if(!$client['campay_app_id']): ?>
    <div style="background:#ffebee;border-left:4px solid #f44336;padding:10px;border-radius:4px;">
      <i class="fa fa-warning" style="color:#f44336;"></i> Veuillez d'abord configurer vos identifiants Campay (Étape 2) pour activer les paiements.
    </div>
    <?php else: ?>
    <div style="background:#e8f5e9;border-left:4px solid #4caf50;padding:10px;border-radius:4px;">
      <i class="fa fa-check-circle" style="color:green;"></i> Campay configuré. Votre boutique est prête à recevoir des paiements !
    </div>
    <?php endif; ?>
  </div>

</div><!-- /content -->
</div><!-- /wrapper -->

<script>
$(document).ready(function(){
  $("#openNav").click(function(){ $("#sidenav").css("width","200px"); $("#content").css("margin-left","200px"); });
  $("#closeNav").click(function(){ $("#sidenav").css("width","0"); $("#content").css("margin-left","0"); });
});
function copyScript() {
  var text = document.getElementById("scriptBox").innerText;
  navigator.clipboard.writeText(text).then(function() {
    alert("Code copié dans le presse-papiers !");
  });
}
</script>
</body>
</html>
