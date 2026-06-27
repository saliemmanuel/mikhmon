# Mikhmon SaaS & Vente de Tickets (Campay)

Ce dossier contient l'intégralité du code source de votre plateforme de revente de tickets WiFi (SaaS).

## 🚀 Architecture
- **Serveur VPN Central** : Un script bash (`setup-saas.sh`) configure votre serveur (AWS EC2) pour qu'il agisse comme un serveur VPN PPTP. Les routeurs MikroTik s'y connectent pour recevoir une IP fixe locale (ex: `10.8.0.x`).
- **Portail d'Administration** : `admin.php` permet de créer des comptes pour d'autres gérants.
- **Portail Client** : `dashboard.php` permet à vos clients de voir leur script MikroTik (à coller dans Winbox) et de configurer leurs clés Campay.
- **Boutique en ligne (`shop/`)** : Le module qui affiche les forfaits et traite le paiement Mobile Money avec Campay.

## 🛠️ Déploiement

### 1. Préparer le Serveur (Ubuntu)
Envoyez le fichier `setup-saas.sh` sur votre serveur Ubuntu (AWS EC2) et lancez-le avec les droits administrateur :
```bash
sudo bash setup-saas.sh
```

### 2. Déployer l'interface Web (SaaS)
Copiez l'intégralité du dossier `saas/` dans le répertoire web public de votre serveur (généralement `/var/www/html/`).
Importez la base de données `database.sql` dans MariaDB.

### 3. Utilisation de la Boutique (Campay)
1. Le client navigue sur `votredomaine.com/shop/` (ou un sous-domaine si configuré).
2. Il entre son numéro de téléphone et choisit son forfait.
3. Il reçoit un "Push" USSD sur son téléphone pour valider le paiement (Code PIN).
4. Campay appelle de manière invisible votre fichier `shop/webhook.php`.
5. Ce fichier vérifie que le paiement est bien reçu, puis se connecte à l'IP VPN du routeur MikroTik du client pour lui générer son ticket final.

---

*Notes de production : Pour que l'API de paiement Campay fonctionne correctement, il est obligatoire d'installer un certificat SSL (HTTPS) sur votre nom de domaine via Certbot (Let's Encrypt).*
