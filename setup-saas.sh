#!/bin/bash
# Script de fondation pour Serveur SaaS Mikhmon
# Installe : Nginx, PHP, MariaDB, L2TP/IPsec (Serveur VPN)

if [ "$EUID" -ne 0 ]; then
  echo "Veuillez exécuter avec sudo bash setup-saas.sh"
  exit
fi

echo "--> Mise à jour du système..."
apt update && apt upgrade -y

echo "--> Installation des paquets web (LEMP)..."
apt install nginx php-fpm php-mysql php-xml php-mbstring php-curl php-zip mariadb-server mariadb-client ufw curl -y

echo "--> Installation du Serveur VPN (L2TP/IPsec)..."
apt install strongswan xl2tpd iptables -y

echo "--> Configuration d'IPsec (strongswan)..."
# Clé pré-partagée (PSK) pour l'authentification IPsec
VPN_IPSEC_PSK="MikhmonSaaS2024"
VPN_SERVER_IP=$(curl -s ifconfig.me)

cat > /etc/ipsec.conf <<EOF
config setup
    charondebug="ike 1, knl 1, cfg 0"
    uniqueids=no

conn ikev1-l2tp
    authby=secret
    auto=add
    keyingtries=3
    rekey=no
    ikelifetime=8h
    keylife=1h
    type=transport
    left=%defaultroute
    leftprotoport=17/1701
    right=%any
    rightprotoport=17/%any
    fragmentation=yes
    forceencaps=yes
    dpddelay=30
    dpdtimeout=120
    dpdaction=clear
EOF

cat > /etc/ipsec.secrets <<EOF
: PSK "$VPN_IPSEC_PSK"
EOF

echo "--> Configuration de L2TP (xl2tpd)..."
cat > /etc/xl2tpd/xl2tpd.conf <<EOF
[global]
port = 1701

[lns default]
ip range = 10.8.0.10-250
local ip = 10.8.0.1
require chap = yes
refuse pap = yes
require authentication = yes
name = l2tpd
pppoptfile = /etc/ppp/options.xl2tpd
length bit = yes
EOF

cat > /etc/ppp/options.xl2tpd <<EOF
ipcp-accept-local
ipcp-accept-remote
ms-dns 8.8.8.8
ms-dns 8.8.4.4
noccp
auth
mtu 1280
mru 1280
proxyarp
lcp-echo-failure 4
lcp-echo-interval 30
connect-delay 5000
EOF

echo "--> Activation du forwarding IP..."
sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/g' /etc/sysctl.conf
sysctl -p

echo "--> Configuration du pare-feu..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw allow 500/udp   # IPsec IKE
ufw allow 4500/udp  # IPsec NAT-T
ufw allow 1701/udp  # L2TP
ufw --force enable

echo "--> Démarrage des services VPN..."
systemctl restart strongswan-starter
systemctl restart xl2tpd
systemctl enable strongswan-starter
systemctl enable xl2tpd

echo "--> Création du script de gestion des utilisateurs VPN..."
cat > /usr/local/bin/vpn-manager.sh <<'EOF'
#!/bin/bash
ACTION=$1
USER=$2
PASS=$3

if [ "$ACTION" == "add" ]; then
    # Retirer l'utilisateur s'il existe déjà
    sed -i "/^$USER \*/d" /etc/ppp/chap-secrets
    # Ajouter le nouvel utilisateur
    echo "$USER * $PASS *" >> /etc/ppp/chap-secrets
    systemctl restart xl2tpd
    echo "User $USER added."
elif [ "$ACTION" == "remove" ]; then
    sed -i "/^$USER \*/d" /etc/ppp/chap-secrets
    systemctl restart xl2tpd
    echo "User $USER removed."
fi
EOF
chmod +x /usr/local/bin/vpn-manager.sh

echo "--> Autoriser PHP à gérer les utilisateurs VPN..."
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/vpn-manager.sh" > /etc/sudoers.d/mikhmon-vpn

echo ""
echo "================================================================"
echo "✅ Serveur SaaS prêt !"
echo ""
echo "📌 NOTEZ CES INFORMATIONS (pour configurer vos MikroTik) :"
echo "   IP Serveur : $VPN_SERVER_IP"
echo "   Clé IPsec  : $VPN_IPSEC_PSK"
echo "   Les utilisateurs/mots de passe VPN sont dans /etc/ppp/chap-secrets"
echo "================================================================"
