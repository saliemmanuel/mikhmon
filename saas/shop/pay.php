<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') die('Accès refusé');

$client_id = $_POST['client_id'];
$plan_id = $_POST['plan_id']; // Dans un vrai projet, on vérifierait en BDD le prix de ce plan
$phone = $_POST['phone'];

// On récupère les clés Campay du client
$stmt = $pdo->prepare("SELECT campay_app_id, campay_app_secret FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client || empty($client['campay_app_id'])) {
    die("Ce réseau n'accepte pas encore les paiements en ligne.");
}

// Logique d'intégration API Campay
// 1. Obtenir un token d'accès
// 2. Initier une requête de paiement (Payment Request)
// Note: Ceci est un code de démonstration structuré pour Campay

$amount = 100; // Normalement, on tire $amount depuis la table 'plans' via $plan_id
$reference = uniqid('TICKET_');

// -------------------------------------------------------------
// DÉMONSTRATION: Appel à l'API Campay
// -------------------------------------------------------------
/*
// Etape 1: Get Token
$ch = curl_init('https://demo.campay.net/api/token/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => $client['campay_app_id'],
    'password' => $client['campay_app_secret']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$token_data = json_decode($response, true);
$token = $token_data['token'];

// Etape 2: Request Payment
$ch2 = curl_init('https://demo.campay.net/api/collect/');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
    'amount' => $amount,
    'currency' => 'XAF',
    'from' => '237' . $phone, // Ajustez le code pays
    'description' => 'Achat Ticket Wifi',
    'external_reference' => $reference
]));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Token ' . $token
]);
$pay_response = curl_exec($ch2);
*/

// Pour l'interface de test local, on simule que le paiement a marché
echo "<h2>Demande de paiement envoyée au $phone</h2>";
echo "<p>Veuillez valider sur votre téléphone.</p>";
echo "<p><i>(En production, cette page écouterait le webhook de Campay pour afficher le code).</i></p>";
echo "<hr>";
echo "<form action='webhook.php' method='POST'>";
echo "<input type='hidden' name='status' value='SUCCESSFUL'>";
echo "<input type='hidden' name='client_id' value='$client_id'>";
echo "<input type='hidden' name='plan_id' value='$plan_id'>";
echo "<button type='submit'>Bouton de Test : Simuler que le paiement a réussi</button>";
echo "</form>";
?>
