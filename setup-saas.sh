#!/bin/bash
# Script de fondation pour Serveur SaaS Mikhmon
# Installe : Nginx, PHP, MariaDB, PPTPD (Serveur VPN)

if [ "$EUID" -ne 0 ]; then
  echo "Veuillez exécuter avec sudo bash setup-saas.sh"
  exit
fi

echo "--> Mise à jour du système..."
apt update && apt upgrade -y

echo "--> Installation des paquets web (LEMP)..."
apt install nginx php-fpm php-mysql php-xml php-mbstring php-curl php-zip mariadb-server mariadb-client ufw curl -y

echo "--> Installation du Serveur VPN (PPTP)..."
apt install pptpd iptables -y

echo "--> Configuration du VPN PPTP..."
# Définir l'IP locale du serveur VPN et la plage d'IPs allouées aux clients (les routeurs MikroTik)
cat > /etc/pptpd.conf <<EOF
option /etc/ppp/pptpd-options
logwtmp
localip 10.8.0.1
remoteip 10.8.0.10-250
EOF

cat > /etc/ppp/pptpd-options <<EOF
name pptpd
refuse-pap
refuse-chap
refuse-mschap
require-mschap-v2
require-mppe-128
ms-dns 8.8.8.8
ms-dns 8.8.4.4
proxyarp
nodefaultroute
lock
nobsdcomp
novj
novjccomp
nologfd
EOF

# Redémarrer PPTPD
systemctl restart pptpd

echo "--> Configuration du pare-feu et du routage..."
# Activer le forwarding IP pour que le VPN fonctionne
sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/g' /etc/sysctl.conf
sysctl -p

ufw allow OpenSSH
ufw allow 'Nginx Full'
# Ouvrir le port PPTP
ufw allow 1723/tcp
ufw --force enable

echo "--> Création du script de gestion VPN pour PHP..."
# Ce script permettra au code PHP d'ajouter des clients VPN facilement
cat > /usr/local/bin/vpn-manager.sh <<'EOF'
#!/bin/bash
ACTION=$1
USER=$2
PASS=$3
IP=$4

if [ "$ACTION" == "add" ]; then
    # Supprimer l'utilisateur s'il existe déjà
    sed -i "/^$USER /d" /etc/ppp/chap-secrets
    # Ajouter le nouvel utilisateur avec son IP fixe
    echo "$USER pptpd $PASS $IP" >> /etc/ppp/chap-secrets
    echo "User $USER added."
elif [ "$ACTION" == "remove" ]; then
    sed -i "/^$USER /d" /etc/ppp/chap-secrets
    echo "User $USER removed."
fi
EOF
chmod +x /usr/local/bin/vpn-manager.sh

echo "--> Autoriser PHP à exécuter vpn-manager sans mot de passe..."
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/vpn-manager.sh" > /etc/sudoers.d/mikhmon-vpn

echo "================================================================"
echo "✅ Serveur SaaS prêt pour la Phase 2 !"
echo "La base de données et le VPN sont installés."
echo "================================================================"
