<?php
session_start();
require 'db.php';

if (!isset($_SESSION['client_id']) || $_SESSION['username'] === 'admin') {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$_SESSION['client_id']]);
$client = $stmt->fetch();

// Remplacer "votre-domaine.com" par le vrai domaine plus tard
$domain = "votre-domaine.com";

$mikrotik_script = "/interface pptp-client add connect-to=$domain disabled=no name=SaaS-VPN password={$client['vpn_password']} profile=default-encryption user={$client['username']}
/ip route add distance=1 dst-address=10.8.0.1/32 gateway=SaaS-VPN";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Client - Mikhmon SaaS</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .box { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        pre { background: #333; color: #0f0; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 14px; }
        .btn { padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        .tuto-step { margin-bottom: 10px; font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($client['username']) ?></h1>
            <a href="logout.php" style="color:red;">Déconnexion</a>
        </div>

        <div class="box">
            <h3>🔗 Étape 1 : Connecter votre routeur MikroTik</h3>
            
            <p>Suivez ce petit tutoriel pour relier votre routeur à la plateforme :</p>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 15px;">
                <div class="tuto-step">1️⃣ Ouvrez votre logiciel Winbox et connectez-vous à votre routeur.</div>
                <div class="tuto-step">2️⃣ Dans le menu de gauche, cliquez sur le bouton <b>"New Terminal"</b>.</div>
                <div class="tuto-step">3️⃣ Copiez le code vert ci-dessous, faites un clic-droit dans la fenêtre noire du terminal et choisissez <b>"Paste"</b> (Coller).</div>
                <div class="tuto-step">4️⃣ Appuyez sur la touche <b>Entrée</b> de votre clavier.</div>
            </div>

            <pre><?= htmlspecialchars($mikrotik_script) ?></pre>
            
            <p><small><i>✅ C'est tout ! Votre routeur est maintenant connecté à notre plateforme sécurisée avec l'adresse IP interne : <b><?= $client['vpn_ip'] ?></b></i></small></p>
        </div>

        <div class="box">
            <h3>💳 Étape 2 : Configuration Campay</h3>
            <p>Entrez vos identifiants d'API Campay pour recevoir l'argent de vos ventes de tickets directement sur votre compte Mobile Money.</p>
            <form action="save_campay.php" method="POST">
                <input type="text" name="app_id" placeholder="Campay App ID" value="<?= htmlspecialchars($client['campay_app_id'] ?? '') ?>" style="width:300px; padding:8px;" required>
                <input type="password" name="app_secret" placeholder="Campay App Secret" value="<?= htmlspecialchars($client['campay_app_secret'] ?? '') ?>" style="width:300px; padding:8px;" required>
                <button type="submit" style="padding:9px; background:#28a745; color:white; border:none; cursor:pointer;">Enregistrer</button>
            </form>
        </div>

        <div class="box">
            <h3>🎫 Étape 3 : Votre Boutique & Mikhmon</h3>
            <p>Lien de votre boutique publique : <a href="http://<?= $client['username'] ?>.<?= $domain ?>" target="_blank">http://<?= $client['username'] ?>.<?= $domain ?></a></p>
        </div>
    </div>
</body>
</html>
