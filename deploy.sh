#!/bin/bash
# Script de déploiement automatique pour Ubuntu (Pile Nginx + PHP)
# À exécuter avec les droits root (sudo bash deploy.sh) depuis le dossier contenant les fichiers de votre site

# Vérifie si le script est lancé en tant que root
if [ "$EUID" -ne 0 ]; then
  echo "Veuillez exécuter ce script avec les droits d'administrateur (sudo bash deploy.sh)"
  exit
fi

echo "--> Mise à jour du système..."
apt update && apt upgrade -y

echo "--> Installation de Nginx et PHP..."
apt install nginx php-fpm php-mysql php-xml php-mbstring php-curl php-zip ufw curl -y

echo "--> Configuration du pare-feu..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

echo "--> Copie des fichiers vers /var/www/html..."
# Supprime la page d'accueil par défaut de Nginx
rm -f /var/www/html/index.html
rm -f /var/www/html/index.nginx-debian.html

# Copie le contenu du dossier actuel dans le dossier web public
cp -r ./* /var/www/html/

echo "--> Application des permissions appropriées..."
chown -R www-data:www-data /var/www/html/
chmod -R 755 /var/www/html/

echo "--> Configuration de Nginx pour interpréter le PHP et répondre à l'adresse IP..."
# On récupère automatiquement la version de PHP-FPM installée (ex: 8.1 ou 8.3)
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)

cat > /etc/nginx/sites-available/default <<EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # Dossier où se trouvent les fichiers du site
    root /var/www/html;
    
    # Ordre de priorité des fichiers d'index
    index index.php index.html index.htm;

    # _ signifie qu'il répondra directement à l'adresse IP du serveur
    server_name _; 

    location / {
        try_files \$uri \$uri/ =404;
    }

    # Configuration pour traiter les fichiers PHP
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
    }

    # Bloque l'accès aux fichiers cachés comme .htaccess
    location ~ /\.ht {
        deny all;
    }
}
EOF

echo "--> Redémarrage de Nginx..."
systemctl restart nginx

# Tente de récupérer l'adresse IP publique du serveur
IP_ADDRESS=$(curl -s ifconfig.me)

echo ""
echo "================================================================"
echo "✅ Déploiement terminé avec succès !"
echo "Votre site est maintenant accessible publiquement via votre IP :"
echo "👉 http://$IP_ADDRESS"
echo "================================================================"
