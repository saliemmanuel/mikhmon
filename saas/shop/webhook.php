<?php
require '../db.php';

// Le Webhook est appelé silencieusement par les serveurs de Campay 
// quand le client a tapé son code PIN sur son téléphone.

// Pour la simulation locale, on accepte le POST du bouton de test
$status = $_POST['status'] ?? 'FAILED';
$client_id = $_POST['client_id'] ?? 1;
$plan_id = $_POST['plan_id'] ?? 1;

if ($status === 'SUCCESSFUL') {
    // Le paiement a réussi !
    // 1. Récupérer l'IP VPN du routeur du client
    $stmt = $pdo->prepare("SELECT vpn_ip, username FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if ($client) {
        $router_ip = $client['vpn_ip'];
        $router_user = 'admin'; // À stocker en BDD idéalement
        $router_pass = 'modesali'; // À stocker en BDD idéalement (vu sur votre capture)
        
        // 2. Connexion à l'API MikroTik (Simulée ici, utiliser RouterosAPI.php en prod)
        $ticket_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
        
        /* 
        require('routeros_api.class.php');
        $API = new RouterosAPI();
        if ($API->connect($router_ip, $router_user, $router_pass)) {
            $API->comm("/ip/hotspot/user/add", array(
                "name"     => $ticket_code,
                "password" => $ticket_code,
                "profile"  => "1H", // Dépend du $plan_id
                "server"   => "all"
            ));
            $API->disconnect();
        }
        */

        // 3. Afficher le ticket
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
        echo "<h1 style='color:green;'>✅ Paiement Validé !</h1>";
        echo "<p>Voici votre code Wifi :</p>";
        echo "<div style='background:#f4f4f4; padding:20px; font-size:40px; letter-spacing:5px; font-weight:bold; border:2px dashed #ccc; display:inline-block;'>";
        echo $ticket_code;
        echo "</div>";
        echo "<p>Entrez ce code sur la page de connexion du réseau pour naviguer.</p>";
        echo "</div>";
    } else {
        echo "Client introuvable.";
    }

} else {
    echo "Paiement échoué ou annulé.";
}
?>
