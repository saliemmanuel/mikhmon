-- Base de données pour Mikhmon SaaS

CREATE DATABASE IF NOT EXISTS mikhmon_saas;

-- Création d'un utilisateur dédié pour l'application PHP
CREATE USER IF NOT EXISTS 'saas_user'@'localhost' IDENTIFIED BY 'Saas_Password_123!';
GRANT ALL PRIVILEGES ON mikhmon_saas.* TO 'saas_user'@'localhost';
FLUSH PRIVILEGES;

USE mikhmon_saas;

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    vpn_password VARCHAR(50) NOT NULL,
    vpn_ip VARCHAR(20) NOT NULL UNIQUE,
    wg_private_key VARCHAR(255),
    wg_public_key VARCHAR(255),
    campay_app_id VARCHAR(255),
    campay_app_secret VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    price INT NOT NULL,
    mikrotik_profile VARCHAR(50) NOT NULL,
    duration_hours INT NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Insérer un compte admin par défaut (mot de passe: admin)
INSERT IGNORE INTO clients (username, password, vpn_password, vpn_ip) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'adminvpn', '10.8.0.2');
