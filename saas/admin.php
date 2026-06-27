<?php
session_start();
require 'db.php';

if (!isset($_SESSION['client_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_client') {
    $username = $_POST['username'];
    $password_plain = $_POST['password'];
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $vpn_ip = $_POST['vpn_ip'];
    $vpn_pass = substr(md5(uniqid()), 0, 8); // Génère un mot de passe VPN aléatoire

    try {
        $stmt = $pdo->prepare("INSERT INTO clients (username, password, vpn_password, vpn_ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $vpn_pass, $vpn_ip]);
        
        // Appel au script shell pour créer le compte VPN réel sur le serveur
        $cmd = "sudo /usr/local/bin/vpn-manager.sh add " . escapeshellarg($username) . " " . escapeshellarg($vpn_pass) . " " . escapeshellarg($vpn_ip);
        shell_exec($cmd);
        
        $success = "Client $username ajouté avec succès ! IP VPN: $vpn_ip";
    } catch (\PDOException $e) {
        $error = "Erreur: Ce nom d'utilisateur ou cette adresse IP est déjà utilisé.";
    }
}

$stmt = $pdo->query("SELECT * FROM clients WHERE username != 'admin' ORDER BY id DESC");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Mikhmon SaaS</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .form-group { margin-bottom: 15px; }
        input { padding: 8px; width: 200px; }
        button { padding: 9px 15px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Panneau d'Administration SaaS</h1>
        <p><a href="logout.php">Déconnexion</a></p>
        
        <?php if(isset($success)) echo "<p style='color:green'>$success</p>"; ?>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

        <h3>Ajouter un nouveau client</h3>
        <form method="POST" style="background:#f9f9f9; padding:15px; border-radius:5px;">
            <input type="hidden" name="action" value="add_client">
            <input type="text" name="username" placeholder="Identifiant Client" required>
            <input type="text" name="password" placeholder="Mot de passe Web" required>
            <input type="text" name="vpn_ip" placeholder="IP VPN (ex: 10.8.0.5)" required>
            <button type="submit">Créer le client</button>
        </form>

        <h3>Liste des Clients</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>IP VPN</th>
                <th>Mot de Passe VPN</th>
                <th>Campay Configuré</th>
            </tr>
            <?php foreach($clients as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['username']) ?></td>
                <td><?= htmlspecialchars($c['vpn_ip']) ?></td>
                <td><?= htmlspecialchars($c['vpn_password']) ?></td>
                <td><?= $c['campay_app_id'] ? '✅ Oui' : '❌ Non' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
