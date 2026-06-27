<?php
require '../db.php';

// Dans une vraie app SaaS, on déterminerait le client avec le sous-domaine
// Ex: si l'URL est client1.mikhmon-afrique.com, on cherche "client1"
// Pour l'instant, on prend le premier client (pour le test)
$stmt = $pdo->query("SELECT * FROM clients WHERE username != 'admin' LIMIT 1");
$client = $stmt->fetch();

if (!$client) {
    die("Boutique indisponible. Aucun client configuré.");
}

// Récupérer les forfaits de ce client
$stmt = $pdo->prepare("SELECT * FROM plans WHERE client_id = ?");
$stmt->execute([$client['id']]);
$plans = $stmt->fetchAll();

// Si pas de forfaits, on met des faux forfaits de test
if (empty($plans)) {
    $plans = [
        ['id' => 1, 'name' => 'Ticket 1 Heure', 'price' => 100, 'mikrotik_profile' => '1H'],
        ['id' => 2, 'name' => 'Ticket 24 Heures', 'price' => 500, 'mikrotik_profile' => '24H'],
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Achat de Ticket Wifi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e9ecef; margin: 0; padding: 20px; }
        .shop-container { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; font-size: 24px; }
        .plan-card { border: 2px solid #007bff; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: 0.3s; }
        .plan-card:hover { background: #f8f9fa; }
        .plan-price { font-size: 20px; font-weight: bold; color: #28a745; float: right; }
        .buy-btn { width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 6px; font-size: 18px; cursor: pointer; margin-top: 15px; }
        input[type="tel"] { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; box-sizing: border-box;}
    </style>
</head>
<body>
    <div class="shop-container">
        <h1>📶 Acheter un Ticket Wifi</h1>
        <p style="text-align:center; color:#666;">Réseau de <?= htmlspecialchars($client['username']) ?></p>

        <form action="pay.php" method="POST">
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            
            <h3>1. Choisissez votre forfait</h3>
            <?php foreach($plans as $plan): ?>
            <label class="plan-card" style="display:block;">
                <input type="radio" name="plan_id" value="<?= $plan['id'] ?>" required>
                <b><?= htmlspecialchars($plan['name']) ?></b>
                <span class="plan-price"><?= $plan['price'] ?> FCFA</span>
            </label>
            <?php endforeach; ?>

            <h3>2. Numéro Mobile Money</h3>
            <input type="tel" name="phone" placeholder="Ex: 6XXXXXXXX" required>

            <button type="submit" class="buy-btn">Payer avec Campay</button>
        </form>
    </div>
</body>
</html>
