#!/bin/bash
# Script de fondation pour Serveur SaaS Mikhmon
# Installe : Nginx, PHP, MariaDB, WireGuard (Serveur VPN moderne)

if [ "$EUID" -ne 0 ]; then
  echo "Veuillez exécuter avec sudo bash setup-saas.sh"
  exit
fi

echo "--> Mise à jour du système..."
apt update && apt upgrade -y

echo "--> Installation des paquets web (LEMP)..."
apt install nginx php-fpm php-mysql php-xml php-mbstring php-curl php-zip mariadb-server mariadb-client ufw curl -y

echo "--> Installation de WireGuard..."
apt install wireguard wireguard-tools -y

echo "--> Génération des clés WireGuard du serveur..."
wg genkey | tee /etc/wireguard/server_private.key | wg pubkey > /etc/wireguard/server_public.key
chmod 600 /etc/wireguard/server_private.key

SERVER_PRIVATE_KEY=$(cat /etc/wireguard/server_private.key)
SERVER_PUBLIC_KEY=$(cat /etc/wireguard/server_public.key)
SERVER_IP=$(curl -s ifconfig.me)

echo "--> Création de l'interface WireGuard (wg0)..."
cat > /etc/wireguard/wg0.conf <<EOF
[Interface]
PrivateKey = $SERVER_PRIVATE_KEY
Address = 10.8.0.1/24
ListenPort = 51820
SaveConfig = true

# Règles NAT pour permettre le trafic VPN vers Internet
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
EOF

echo "--> Activation du forwarding IP..."
sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/g' /etc/sysctl.conf
sysctl -p

echo "--> Démarrage et activation de WireGuard..."
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

echo "--> Configuration du pare-feu..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw allow 51820/udp  # WireGuard
ufw --force enable

echo "--> Création du script de gestion des clients VPN..."
cat > /usr/local/bin/vpn-manager.sh <<'VPNEOF'
#!/bin/bash
# Usage: vpn-manager.sh add <username> <client_public_key> <client_ip>
#        vpn-manager.sh remove <username>
ACTION=$1
USERNAME=$2
CLIENT_PUBKEY=$3
CLIENT_IP=$4

if [ "$ACTION" == "add" ]; then
    # Retirer le peer s'il existe déjà
    wg set wg0 peer "$CLIENT_PUBKEY" remove 2>/dev/null
    # Ajouter le nouveau peer avec son IP fixe
    wg set wg0 peer "$CLIENT_PUBKEY" allowed-ips "$CLIENT_IP/32"
    # Sauvegarder la config pour qu'elle persiste après redémarrage
    wg-quick save wg0
    echo "Peer $USERNAME ($CLIENT_IP) added."
elif [ "$ACTION" == "remove" ]; then
    wg set wg0 peer "$CLIENT_PUBKEY" remove
    wg-quick save wg0
    echo "Peer $USERNAME removed."
fi
VPNEOF
chmod +x /usr/local/bin/vpn-manager.sh

echo "--> Autoriser PHP à gérer les clients VPN..."
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/vpn-manager.sh" > /etc/sudoers.d/mikhmon-vpn

echo ""
echo "================================================================"
echo "✅ Serveur SaaS prêt !"
echo ""
echo "📌 CLÉ PUBLIQUE WireGuard DE VOTRE SERVEUR (à noter absolument) :"
echo "$SERVER_PUBLIC_KEY"
echo ""
echo "   IP du Serveur : $SERVER_IP"
echo "   Port WireGuard : 51820"
echo "================================================================"
