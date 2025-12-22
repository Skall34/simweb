# 🚀 Guide d'Installation Complet - Virtual Airline Management System

**Version :** 2.0  
**Date :** Décembre 2025  
**Langues supportées :** Français, Anglais, Espagnol  

---

## 📑 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Prérequis Détaillés](#prérequis-détaillés)
3. [Méthode 1 : Installation Automatisée (RECOMMANDÉE)](#méthode-1--installation-automatisée-recommandée)
4. [Méthode 2 : Installation Manuelle Complète](#méthode-2--installation-manuelle-complète)
5. [Configuration Post-Installation](#configuration-post-installation)
6. [Configuration Serveur](#configuration-serveur)
7. [Configuration SMTP](#configuration-smtp)
8. [Tâches Planifiées (CRON)](#tâches-planifiées-cron)
9. [Sécurisation](#sécurisation)
10. [Migration & Mise à Jour](#migration--mise-à-jour)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 Vue d'Ensemble

Ce système de gestion de compagnie aérienne virtuelle peut être installé de deux façons :

### **🌟 Méthode 1 : Installation Automatisée** (⏱️ 10 minutes)
- Interface web interactive
- Vérification automatique de l'environnement
- Création base de données
- Génération fichiers de configuration
- **Recommandée pour 95% des utilisateurs**

### **⚙️ Méthode 2 : Installation Manuelle** (⏱️ 45-60 minutes)
- Contrôle total sur chaque étape
- Pour utilisateurs avancés
- Nécessaire si serveur sans interface web

---

## 🔧 Prérequis Détaillés

### Serveur Web

#### ✅ Configuration Minimale
- **PHP** : 7.4 ou supérieur
- **MySQL** : 5.7 ou supérieur (ou MariaDB 10.3+)
- **Serveur** : Apache 2.4+ ou Nginx 1.18+
- **Espace disque** : 500 MB minimum (2 GB recommandé)
- **RAM** : 512 MB minimum (1 GB recommandé)

#### ⭐ Configuration Recommandée (Production)
- **PHP** : 8.1 ou supérieur
- **MySQL** : 8.0 ou MariaDB 10.6+
- **Serveur** : Apache 2.4+ avec mod_rewrite
- **Espace disque** : 5 GB
- **RAM** : 2 GB minimum
- **HTTPS** : Certificat SSL (Let's Encrypt gratuit)

### Extensions PHP Requises

```bash
# Vérifier extensions installées
php -m

# Extensions critiques :
✓ pdo
✓ pdo_mysql
✓ mbstring
✓ json
✓ curl
✓ openssl
✓ session
✓ fileinfo
✓ zip (pour installation)
```

**Installation extensions (Debian/Ubuntu) :**
```bash
sudo apt-get install php-mysql php-mbstring php-curl php-json php-xml
sudo systemctl restart apache2
```

**Installation extensions (CentOS/RHEL) :**
```bash
sudo yum install php-mysqlnd php-mbstring php-curl php-json
sudo systemctl restart httpd
```

### Permissions

```bash
# Le serveur web (www-data, apache, nginx) doit pouvoir :
- Lire tous les fichiers PHP
- Écrire dans scripts/logs/
- Écrire dans assets/ (upload images)
```

### Outils Nécessaires

- **Accès SSH** (pour serveur distant)
- **Client FTP/SFTP** (FileZilla, WinSCP) OU accès direct serveur
- **Client MySQL** (phpMyAdmin, MySQL Workbench) OU ligne de commande
- **Éditeur de texte** (Notepad++, VSCode, nano, vim)

---

## 🌟 Méthode 1 : Installation Automatisée (RECOMMANDÉE)

### Étape 1 : Téléchargement

**Option A : Depuis GitHub Release**
```bash
wget https://github.com/Skall34/simweb/releases/latest/download/va-system-v2.0.zip
unzip va-system-v2.0.zip
```

**Option B : Clone Git**
```bash
git clone https://github.com/Skall34/simweb.git
cd simweb
```

### Étape 2 : Upload des Fichiers

**Via FTP/SFTP (Windows) :**
1. Connectez-vous à votre serveur avec FileZilla
2. Uploadez **TOUS** les fichiers vers `/public_html/` ou `/var/www/html/`
3. Conservez la structure des dossiers intacte

**Via SSH (Linux) :**
```bash
# Depuis votre machine locale
scp -r simweb/ user@votreserveur.com:/var/www/html/

# OU depuis le serveur
cd /var/www/html/
git clone https://github.com/Skall34/simweb.git .
```

**⚠️ IMPORTANT : Structure à uploader**
```
/var/www/html/  (ou /public_html/)
├── admin/
├── api/
├── assets/
├── css/
├── Documentation/
├── includes/
├── install/          ← OBLIGATOIRE
│   ├── index.php
│   ├── steps/
│   └── sql_database/ ← OBLIGATOIRE (fichiers SQL)
├── lang/
├── pages/
├── scripts/
├── index.php
├── login.php
└── ...
```

### Étape 3 : Permissions Initiales

```bash
# Rendre le dossier logs accessible en écriture
chmod 755 -R /var/www/html/
chmod 770 /var/www/html/scripts/logs/
chown -R www-data:www-data /var/www/html/

# Ou avec votre utilisateur serveur
chown -R apache:apache /var/www/html/  # CentOS
chown -R nginx:nginx /var/www/html/    # Nginx
```

### Étape 4 : Lancer l'Installateur Web

1. **Ouvrir votre navigateur :**
   ```
   http://votre-domaine.com/install/
   OU
   http://votre-ip/install/
   ```

2. **Vous verrez 5 étapes :**

#### **📋 Étape 1 : Vérification Environnement**

L'installateur vérifie automatiquement :
- ✅ Version PHP (>= 7.4)
- ✅ Extensions PHP requises
- ✅ Permissions dossiers
- ✅ Fonction `exec()` pour scripts
- ✅ Fonction `mail()` pour emails

**Si tout est vert :** Cliquez "Suivant"  
**Si rouge :** Suivez les instructions affichées, puis rechargez la page

**Exemple correction permissions :**
```bash
# Si erreur "scripts/logs/ non accessible en écriture"
chmod 770 /var/www/html/scripts/logs/
chown www-data:www-data /var/www/html/scripts/logs/
```

#### **💾 Étape 2 : Configuration Base de Données**

Remplissez le formulaire :

| Champ | Exemple | Description |
|-------|---------|-------------|
| **Hôte** | `localhost` | Adresse serveur MySQL (généralement localhost) |
| **Port** | `3306` | Port MySQL (3306 par défaut) |
| **Nom base** | `va_database` | Nom base de données (sera créée si inexistante) |
| **Utilisateur** | `va_user` | Utilisateur MySQL avec droits CREATE/INSERT/UPDATE |
| **Mot de passe** | `VotreMotDePasse123!` | Mot de passe MySQL |

**⚠️ L'utilisateur MySQL doit avoir les privilèges :**
```sql
GRANT ALL PRIVILEGES ON va_database.* TO 'va_user'@'localhost';
FLUSH PRIVILEGES;
```

**Création utilisateur MySQL (si besoin) :**
```sql
# Se connecter en root
mysql -u root -p

# Créer base et utilisateur
CREATE DATABASE va_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'va_user'@'localhost' IDENTIFIED BY 'VotreMotDePasse123!';
GRANT ALL PRIVILEGES ON va_database.* TO 'va_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Cliquez **"Tester la connexion"** pour vérifier. Si OK, cliquez **"Suivant"**.

#### **⚙️ Étape 3 : Configuration Compagnie**

| Champ | Exemple | Description |
|-------|---------|-------------|
| **Nom VA** | `SkyWings Airlines` | Nom de votre compagnie (affiché partout) |
| **Email contact** | `admin@skywings.com` | Email administrateur (recevra alertes) |
| **URL site** | `https://skywings.com` | URL complète **SANS** `/install/` |
| **Langue par défaut** | `Français` | fr, en ou es |

**Configuration SMTP (optionnel - peut être fait après) :**

Si vous voulez activer les emails maintenant :

| Champ SMTP | Exemple | Description |
|------------|---------|-------------|
| **Hôte** | `smtp.gmail.com` | Serveur SMTP |
| **Port** | `587` | 587 (TLS) ou 465 (SSL) |
| **Utilisateur** | `yourva@gmail.com` | Email SMTP |
| **Mot de passe** | `votremotdepasse` | Mot de passe SMTP |
| **Sécurité** | `TLS` | TLS ou SSL |
| **Expéditeur** | `SkyWings Admin` | Nom affiché dans emails |

**💡 Conseil :** Laissez vide pour l'instant, configurez après installation.

#### **🚀 Étape 4 : Installation**

Récapitulatif de la configuration s'affiche. Vérifiez tout, puis cliquez **"Lancer l'installation"**.

**L'installateur va :**
1. ✅ Générer `includes/db_connect.php`
2. ✅ Générer `includes/config.php`
3. ✅ Créer les 22 tables dans la base
4. ✅ Importer 87 aéroports avec données fret
5. ✅ Créer le compte admin par défaut `ADM0001`
6. ✅ Insérer variables de configuration
7. ✅ Créer les grades par défaut
8. ✅ Créer les missions par défaut

**⏱️ Durée : 30-60 secondes**

Vous verrez les logs s'afficher en temps réel :
```
✓ Connexion à la base de données réussie
✓ Fichier db_connect.php créé
✓ Table PILOTES créée
✓ Table FLOTTE créée
...
✓ 87 aéroports importés
✓ Compte admin ADM0001 créé
✓ Installation terminée avec succès !
```

#### **🎉 Étape 5 : Terminé !**

**🔒 SÉCURITÉ CRITIQUE :**

L'installateur affiche un message important :
```
⚠️ SUPPRIMEZ LE DOSSIER /install/ IMMÉDIATEMENT !
```

**Suppression installateur :**
```bash
# Depuis SSH
rm -rf /var/www/html/install/

# Ou via FTP : supprimer le dossier "install"
```

**Identifiants par défaut :**
- **Callsign :** `ADM0001`
- **Mot de passe :** `admin123`

Cliquez **"Accéder au site"** → Vous êtes redirigé vers la page de connexion.

### Étape 5 : Première Connexion

1. Allez sur `http://votre-domaine.com/`
2. Cliquez "Connexion"
3. Entrez :
   - Callsign : `ADM0001`
   - Mot de passe : `admin123`
4. Vous êtes connecté avec droits **Super Admin**

### Étape 6 : Sécurisation Compte Admin

**🚨 IMPORTANT : À faire IMMÉDIATEMENT**

1. **Créer votre compte personnel :**
   - Cliquez "Inscription" (ou déconnectez-vous)
   - Créez un compte avec votre vrai callsign (ex: `SKY0001`)
   - Utilisez un mot de passe FORT

2. **Promouvoir votre compte en Admin :**
   - Reconnectez-vous avec `ADM0001`
   - Menu **Admin → Gestion des Pilotes**
   - Trouvez votre nouveau compte
   - Changez **Niveau Admin** à `2` (Super Admin)
   - Cliquez "Modifier"

3. **Supprimer ADM0001 :**
   - Toujours dans Gestion des Pilotes
   - Trouvez `ADM0001`
   - Cliquez "Désactiver" ou "Supprimer"
   - Confirmez

4. **Se reconnecter avec votre compte :**
   - Déconnexion
   - Connexion avec votre callsign

**✅ Installation automatisée terminée !**

Passez à la section [Configuration Post-Installation](#configuration-post-installation).

---

## ⚙️ Méthode 2 : Installation Manuelle Complète

**⚠️ Réservé aux utilisateurs avancés. Préférez l'installateur automatique.**

### Prérequis Supplémentaires

- Accès SSH au serveur
- Connaissance de la ligne de commande MySQL
- Éditeur de texte (nano, vim, ou éditeur local)

### Étape 1 : Téléchargement et Upload

Identique à la Méthode 1 (Étapes 1-2).

### Étape 2 : Création Base de Données

**Se connecter à MySQL :**
```bash
mysql -u root -p
```

**Créer la base :**
```sql
CREATE DATABASE va_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'va_user'@'localhost' IDENTIFIED BY 'MotDePasseSecurise123!';
GRANT ALL PRIVILEGES ON va_database.* TO 'va_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Étape 3 : Import Tables SQL

**Importer les scripts dans l'ordre :**
```bash
cd /var/www/html/install/sql_database/

# 1. Structure principale (22 tables)
mysql -u va_user -p va_database < 01_Main_Database.sql

# 2. Données aéroports (87 aéroports)
mysql -u va_user -p va_database < 02_Airports_data.sql
```

**Vérification :**
```sql
mysql -u va_user -p va_database

SHOW TABLES;
-- Doit afficher 22 tables

SELECT COUNT(*) FROM AEROPORTS;
-- Doit afficher 87

EXIT;
```

### Étape 4 : Configuration db_connect.php

**Copier le fichier exemple :**
```bash
cd /var/www/html/includes/
cp db_connect_exemple.php db_connect.php
```

**Éditer avec vos identifiants :**
```bash
nano db_connect.php
```

**Modifier les lignes :**
```php
<?php
$host = 'localhost';              // Adresse serveur MySQL
$dbname = 'va_database';          // Nom base de données
$username = 'va_user';            // Utilisateur MySQL
$password = 'MotDePasseSecurise123!'; // Mot de passe MySQL
$port = 3306;                     // Port MySQL

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
```

**Sauvegarder :** Ctrl+O, Entrée, Ctrl+X (nano)

**Tester la connexion :**
```bash
php -r "require 'db_connect.php'; echo 'Connexion OK';"
# Doit afficher : Connexion OK
```

### Étape 5 : Configuration config.php

**Créer le fichier :**
```bash
nano config.php
```

**Contenu :**
```php
<?php
// Configuration générale de la Virtual Airline

// Informations compagnie
define('VA_NAME', 'SkyWings Airlines');        // Nom de votre VA
define('VA_ADMIN_EMAIL', 'admin@skywings.com'); // Email admin
define('VA_URL', 'https://skywings.com');       // URL site (sans / final)
define('VA_DEFAULT_LANG', 'fr');                // fr, en, es

// Fuseau horaire
date_default_timezone_set('Europe/Paris'); // Ajuster selon localisation

// Mode debug (TOUJOURS false en production)
define('DEBUG_MODE', false);
define('DISPLAY_ERRORS', DEBUG_MODE);
ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(DEBUG_MODE ? E_ALL : E_ERROR);

// Chemins
define('BASE_PATH', __DIR__ . '/..');
define('LOGS_PATH', BASE_PATH . '/scripts/logs');

// Sécurité sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
```

### Étape 6 : Insérer Données Initiales

**Se connecter à MySQL :**
```sql
mysql -u va_user -p va_database
```

**Insérer variables de configuration :**
```sql
INSERT INTO VARIABLES_CONFIG (cle, valeur, description, type) VALUES
('prix_litre_essence', '0.88', 'Prix du litre d''essence en EUR', 'float'),
('assurance_base_mois', '500', 'Assurance mensuelle de base en EUR', 'int'),
('salaire_base_heure', '50', 'Salaire de base par heure de vol en EUR', 'int'),
('taux_interet_credit', '3.5', 'Taux d''intérêt annuel pour crédits (%)', 'float');
```

**Créer grades par défaut :**
```sql
INSERT INTO GRADES (nom, heures_min, salaire_horaire, ordre) VALUES
('Cadet', 0, 30, 1),
('Second Officer', 50, 50, 2),
('First Officer', 150, 75, 3),
('Captain', 500, 100, 4),
('Senior Captain', 1500, 150, 5);
```

**Créer missions par défaut :**
```sql
INSERT INTO MISSIONS (libelle, majoration, active) VALUES
('Vol libre', 1.0, 1),
('Fret commercial', 1.2, 1),
('Humanitaire', 1.5, 1),
('Vol charter', 1.3, 1);
```

**Créer compte admin par défaut :**
```sql
-- Hash bcrypt de "admin123"
INSERT INTO PILOTES (callsign, nom, prenom, email, password, grade_id, niveau_admin, actif, langue) VALUES
('ADM0001', 'Administrateur', 'Système', 'admin@change-me.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 2, 1, 'fr');
```

**Insérer balance initiale :**
```sql
INSERT INTO BALANCE_COMMERCIALE (balance_actuelle, derniere_maj, commentaire) VALUES
(0.00, NOW(), 'Initialisation');
```

**Quitter MySQL :**
```sql
EXIT;
```

### Étape 7 : Permissions Finales

```bash
# Propriétaire
chown -R www-data:www-data /var/www/html/

# Permissions
find /var/www/html/ -type d -exec chmod 755 {} \;
find /var/www/html/ -type f -exec chmod 644 {} \;

# Logs écriture
chmod 770 /var/www/html/scripts/logs/

# Scripts exécutables
chmod 750 /var/www/html/scripts/*.php
```

### Étape 8 : Suppression Installateur

```bash
rm -rf /var/www/html/install/
```

### Étape 9 : Test Installation

**Accéder au site :**
```
http://votre-domaine.com/
```

**Se connecter :**
- Callsign : `ADM0001`
- Mot de passe : `admin123`

**✅ Si connexion OK : Installation manuelle réussie !**

---

## 🔧 Configuration Post-Installation

Ces étapes sont communes aux deux méthodes d'installation.

### 1. Créer Types d'Appareils

**Menu Admin → Types d'Appareils → Ajouter**

Exemples :

| Nom | Catégorie | Capacité Fret | Capacité Fuel | Coût Horaire | Vitesse Croisière |
|-----|-----------|---------------|---------------|--------------|-------------------|
| Cessna 172 | Monomoteur | 200 kg | 200 L | 100 EUR/h | 120 kt |
| Beechcraft Baron | Bimoteur | 500 kg | 500 L | 200 EUR/h | 180 kt |
| King Air 350 | Turboprop | 1200 kg | 1500 L | 500 EUR/h | 250 kt |
| Citation CJ4 | Jet | 800 kg | 2500 L | 1000 EUR/h | 400 kt |

### 2. Acheter Premier Avion

**Menu Admin → Gestion Flotte → Acheter un Avion**

| Champ | Exemple |
|-------|---------|
| Type | Cessna 172 |
| Immatriculation | F-GSKY |
| Localisation initiale | LFPG (CDG Paris) |
| Prix d'achat | 150 000 EUR |
| Assurance mensuelle | 500 EUR |
| Mode paiement | Comptant OU Crédit (24 mensualités) |

**💡 L'achat crée automatiquement :**
- Entrée dans `finances_depenses`
- Si crédit : `nb_mensualites_restantes` rempli
- Avion disponible immédiatement

### 3. Créer Missions Personnalisées

**Menu Admin → Gestion Missions → Nouvelle Mission**

Exemples :

| Libellé | Majoration | Active |
|---------|------------|--------|
| Rapatriement médical | 2.0 | ✓ |
| Vol VIP | 1.8 | ✓ |
| Cargo express | 1.5 | ✓ |
| Formation | 0.8 | ✓ |

**Majoration** : Multiplicateur appliqué au revenu brut (1.0 = normal, 2.0 = double revenu)

### 4. Personnalisation Visuelle

#### Logo
```bash
# Remplacer le logo
cp votre-logo.png /var/www/html/assets/images/logo.png
```

**Format recommandé :** PNG transparent, 250x100 px

#### Couleurs (CSS)

**Éditer :** `css/styles.css`

```css
:root {
    --primary-color: #1e40af;      /* Bleu principal */
    --secondary-color: #dc2626;    /* Rouge secondaire */
    --success-color: #16a34a;      /* Vert succès */
    --background-color: #f3f4f6;   /* Fond gris clair */
}

/* En-tête */
header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}
```

#### Nom Compagnie

**Éditer :** `includes/header.php`

```php
<h1><?= VA_NAME ?></h1>
<!-- Récupère automatiquement depuis config.php -->
```

### 5. Inviter Pilotes

**Deux méthodes :**

**A. Auto-inscription (recommandée) :**
- Les pilotes vont sur votre site
- Cliquez "Inscription"
- Remplissent le formulaire
- **Vous devez ensuite les activer** (Admin → Gestion Pilotes → Activer)

**B. Création manuelle :**
- Admin → Gestion Pilotes → Ajouter Pilote
- Remplir formulaire
- Cocher "Actif"
- Envoyer callsign/password au pilote

### 6. Configurer SimAddon (Client ACARS)

**Documentation complète :** `assets/acars/DocumentationUtilisateurSimAddon.pdf`

**Résumé :**

1. **Pilote génère son token :**
   - Menu "Mon Compte" → Section "Token SimAddon"
   - Cliquer "Générer un nouveau token"
   - **Copier le token (64 caractères)**

2. **Configuration SimAddon (addon MSFS) :**
   - Ouvrir SimAddon dans MSFS
   - Onglet "Configuration"
   - Coller :
     - **URL API :** `https://votre-domaine.com/api/`
     - **Token :** (le token copié)
   - Sauvegarder

3. **Test :**
   - Lancer un vol dans MSFS
   - SimAddon doit afficher "Connecté"
   - À l'atterrissage, vol importé automatiquement

---

## 🌐 Configuration Serveur

### Apache

#### Configuration Minimale

**Fichier :** `/etc/apache2/sites-available/votre-site.conf`

```apache
<VirtualHost *:80>
    ServerName votre-domaine.com
    ServerAlias www.votre-domaine.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/va-error.log
    CustomLog ${APACHE_LOG_DIR}/va-access.log combined

    # Redirection HTTPS (si certificat SSL installé)
    # RewriteEngine On
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

**Activer :**
```bash
sudo a2ensite votre-site.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Configuration Production (avec SSL)

**Fichier :** `/etc/apache2/sites-available/votre-site-ssl.conf`

```apache
<VirtualHost *:443>
    ServerName votre-domaine.com
    DocumentRoot /var/www/html

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/votre-domaine.crt
    SSLCertificateKeyFile /etc/ssl/private/votre-domaine.key
    SSLCertificateChainFile /etc/ssl/certs/chain.pem

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Sécurité headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    # Compression GZIP
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/css text/javascript application/json
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/va-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/va-ssl-access.log combined
</VirtualHost>
```

**Activer SSL :**
```bash
sudo a2enmod ssl headers deflate
sudo a2ensite votre-site-ssl.conf
sudo systemctl restart apache2
```

#### .htaccess (déjà inclus)

**Fichier racine :** `/var/www/html/.htaccess`

```apache
# Activer réécriture URL
RewriteEngine On

# Bloquer accès fichiers sensibles
<FilesMatch "\.(log|sql|md|gitignore)$">
    Require all denied
</FilesMatch>

# Bloquer accès dossiers sensibles
RewriteRule ^(includes|scripts|install)/ - [F,L]

# Redirection HTTP → HTTPS (si SSL actif)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]

# Protection index
Options -Indexes
```

### Nginx

**Fichier :** `/etc/nginx/sites-available/votre-site`

```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/html;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/va-access.log;
    error_log /var/log/nginx/va-error.log;

    # Bloquer fichiers sensibles
    location ~ \.(log|sql|md|gitignore)$ {
        deny all;
    }

    # Bloquer dossiers sensibles
    location ~ ^/(includes|scripts|install)/ {
        deny all;
    }

    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Fichiers statiques
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# Configuration SSL
server {
    listen 443 ssl http2;
    server_name votre-domaine.com;
    root /var/www/html;

    ssl_certificate /etc/ssl/certs/votre-domaine.crt;
    ssl_certificate_key /etc/ssl/private/votre-domaine.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Mêmes règles que ci-dessus...
}
```

**Activer :**
```bash
sudo ln -s /etc/nginx/sites-available/votre-site /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Certificat SSL (Let's Encrypt)

**Installation Certbot :**
```bash
# Debian/Ubuntu
sudo apt-get install certbot python3-certbot-apache

# CentOS
sudo yum install certbot python3-certbot-apache
```

**Générer certificat :**
```bash
sudo certbot --apache -d votre-domaine.com -d www.votre-domaine.com
```

**Renouvellement auto :**
```bash
# Ajouter au cron
sudo crontab -e

# Ajouter cette ligne
0 3 * * * certbot renew --quiet
```

---

## 📧 Configuration SMTP

### Gmail (Simple mais limité)

**Éditer :** `includes/mail_utils.php`

```php
<?php
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

// Email admin
define('VA_ADMIN_EMAIL', 'admin@skywings.com');

// Configuration SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');  // 'tls' ou 'ssl'
define('SMTP_USERNAME', 'yourva@gmail.com');
define('SMTP_PASSWORD', 'votre_mot_de_passe_app'); // Pas le mot de passe Gmail !
define('SMTP_FROM_EMAIL', 'yourva@gmail.com');
define('SMTP_FROM_NAME', 'SkyWings Admin');
```

**⚠️ Gmail nécessite "Mot de passe d'application" :**
1. Activer 2FA sur compte Gmail
2. Aller dans Paramètres Google → Sécurité
3. Créer "Mot de passe d'application"
4. Utiliser ce mot de passe dans SMTP_PASSWORD

**Limite Gmail :** 500 emails/jour (suffisant pour petites VA)

### SendGrid (Professionnel)

**Créer compte gratuit :** https://sendgrid.com (100 emails/jour gratuits)

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'votre_api_key_sendgrid');
define('SMTP_FROM_EMAIL', 'noreply@votre-domaine.com');
define('SMTP_FROM_NAME', 'SkyWings System');
```

### Serveur SMTP Hébergeur

**Vérifier paramètres avec votre hébergeur. Exemple OVH :**
```php
define('SMTP_HOST', 'ssl0.ovh.net');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
define('SMTP_USERNAME', 'contact@votre-domaine.com');
define('SMTP_PASSWORD', 'votre_mot_de_passe_email');
```

### Test Envoi Email

**Créer :** `test_mail.php` (à la racine, puis supprimer après test)

```php
<?php
require_once __DIR__ . '/includes/mail_utils.php';

$result = sendSummaryMail(
    'Test Email',
    'Ceci est un email de test depuis votre VA.',
    'votre-email@test.com'
);

if ($result === true) {
    echo "✅ Email envoyé avec succès !";
} else {
    echo "❌ Erreur : " . print_r($result, true);
}
```

**Exécuter :**
```bash
php test_mail.php
```

**Supprimer après test :**
```bash
rm test_mail.php
```

---

## ⏰ Tâches Planifiées (CRON)

### Linux (CRON)

**Éditer crontab :**
```bash
sudo crontab -e
```

**Configuration complète :**
```bash
# Virtual Airline - Scripts Automatisés
# Chemins absolus requis !

# MENSUELS (1er du mois)
0 1 1 * * /usr/bin/php /var/www/html/scripts/assurance_mensuelle.php >> /var/www/html/scripts/logs/cron.log 2>&1
0 2 1 * * /usr/bin/php /var/www/html/scripts/credit_mensualite.php >> /var/www/html/scripts/logs/cron.log 2>&1
0 3 1 * * /usr/bin/php /var/www/html/scripts/paiement_salaires_pilotes.php >> /var/www/html/scripts/logs/cron.log 2>&1
0 4 1 * * /usr/bin/php /var/www/html/scripts/promotion_grades_pilotes.php >> /var/www/html/scripts/logs/cron.log 2>&1
0 5 1 * * /usr/bin/php /var/www/html/scripts/maintenance.php >> /var/www/html/scripts/logs/cron.log 2>&1

# HEBDOMADAIRE (vendredi 4h)
0 4 * * 5 /usr/bin/php /var/www/html/scripts/update_fret.php >> /var/www/html/scripts/logs/cron.log 2>&1

# QUOTIDIEN (2h)
0 2 * * * /usr/bin/php /var/www/html/scripts/expire_reservations.php >> /var/www/html/scripts/logs/cron.log 2>&1
```

**Vérifier chemin PHP :**
```bash
which php
# Utiliser le chemin retourné dans le crontab
```

**Tester un script manuellement :**
```bash
php /var/www/html/scripts/expire_reservations.php
# Vérifier logs/expire_reservations.log
```

### Windows (Task Scheduler)

**PowerShell script :** `run_script.ps1`
```powershell
$phpPath = "C:\php\php.exe"
$scriptPath = "C:\wamp64\www\scripts\expire_reservations.php"
$logPath = "C:\wamp64\www\scripts\logs\cron.log"

& $phpPath $scriptPath >> $logPath 2>&1
```

**Créer tâche planifiée :**
1. Ouvrir "Planificateur de tâches"
2. Action → Créer une tâche de base
3. Nom : "VA - Expire Reservations"
4. Déclencheur : Quotidien, 02:00
5. Action : Démarrer un programme
   - Programme : `powershell.exe`
   - Arguments : `-File C:\chemin\run_script.ps1`
6. Terminer

**Répéter pour chaque script avec horaires appropriés.**

---

## 🔒 Sécurisation

### Checklist Sécurité Production

- [ ] **HTTPS activé** (certificat SSL valide)
- [ ] **Dossier /install/ supprimé**
- [ ] **Compte ADM0001 supprimé**
- [ ] **Mots de passe BDD forts** (16+ caractères, alphanumérique)
- [ ] **DEBUG_MODE = false** dans config.php
- [ ] **Permissions fichiers correctes** (755/644)
- [ ] **Logs non accessibles web** (scripts/logs/)
- [ ] **db_connect.php non lisible** (chmod 640)
- [ ] **.gitignore configuré** (si Git)
- [ ] **Sauvegardes automatiques BDD** (quotidiennes)
- [ ] **Fail2ban configuré** (optionnel mais recommandé)

### Sauvegardes Automatiques

**Script backup :** `scripts/backup_database.sh`

```bash
#!/bin/bash
# Backup quotidien base de données

DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="/var/backups/va_database"
DB_NAME="va_database"
DB_USER="va_user"
DB_PASS="VotreMotDePasse"

mkdir -p $BACKUP_DIR

# Dump BDD
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/va_backup_$DATE.sql.gz

# Garder seulement 30 derniers jours
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +30 -delete

echo "Backup terminé : va_backup_$DATE.sql.gz"
```

**Rendre exécutable :**
```bash
chmod +x /var/www/html/scripts/backup_database.sh
```

**Ajouter au cron (tous les jours 1h) :**
```bash
0 1 * * * /var/www/html/scripts/backup_database.sh >> /var/www/html/scripts/logs/backup.log 2>&1
```

### Fail2ban (Protection Brute-Force)

**Installation :**
```bash
sudo apt-get install fail2ban
```

**Configuration :** `/etc/fail2ban/jail.local`

```ini
[va-login]
enabled = true
filter = va-login
logpath = /var/www/html/scripts/logs/auth.log
maxretry = 5
bantime = 3600
findtime = 600
```

**Filtre :** `/etc/fail2ban/filter.d/va-login.conf`

```ini
[Definition]
failregex = ^.* Login failed for callsign: <HOST>
ignoreregex =
```

**Ajouter logs dans login.php :**
```php
// En cas d'échec connexion
logMsg("Login failed for callsign: " . $_SERVER['REMOTE_ADDR'], __DIR__ . '/scripts/logs/auth.log');
```

---

## 🔄 Migration & Mise à Jour

### Migration depuis Ancienne Version

**Si vous avez déjà une version < 2.0 installée :**

1. **Sauvegarder BDD actuelle :**
   ```bash
   mysqldump -u root -p old_va_database > backup_old_va.sql
   ```

2. **Sauvegarder fichiers actuels :**
   ```bash
   tar -czf backup_old_files.tar.gz /var/www/html/
   ```

3. **Exporter données pilotes/vols :**
   ```sql
   SELECT * FROM PILOTES INTO OUTFILE '/tmp/pilotes.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';
   SELECT * FROM CARNET_VOL INTO OUTFILE '/tmp/vols.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';
   ```

4. **Installer nouvelle version** (méthode 1 ou 2)

5. **Importer données :**
   - Utiliser scripts SQL personnalisés ou
   - Import manuel via phpMyAdmin

**⚠️ Contactez le support pour assistance migration complexe.**

### Mise à Jour Mineure (2.0 → 2.1)

**Via Git :**
```bash
cd /var/www/html/
git pull origin main
```

**Vérifier changements BDD :**
```bash
# Lire CHANGELOG.md pour scripts SQL éventuels
cat Documentation/CHANGELOG.md
```

**Appliquer modifications BDD si nécessaire :**
```bash
mysql -u va_user -p va_database < updates/update_2.1.sql
```

---

## 🔧 Troubleshooting

### Problème : Page blanche / Erreur 500

**Cause possible :** Erreur PHP non affichée

**Solution :**
1. Activer affichage erreurs temporairement :
   ```php
   // Dans config.php
   define('DEBUG_MODE', true);
   ```

2. Vérifier logs Apache :
   ```bash
   tail -f /var/log/apache2/va-error.log
   ```

3. Vérifier logs PHP :
   ```bash
   tail -f /var/log/php8.1-fpm.log
   ```

### Problème : "Database connection failed"

**Vérifications :**

```bash
# 1. MySQL fonctionne ?
sudo systemctl status mysql

# 2. Connexion possible ?
mysql -u va_user -p va_database

# 3. Identifiants corrects dans db_connect.php ?
cat /var/www/html/includes/db_connect.php
```

**Erreur courante :** `Access denied for user`
```sql
-- Recréer utilisateur
DROP USER 'va_user'@'localhost';
CREATE USER 'va_user'@'localhost' IDENTIFIED BY 'NouveauMotDePasse';
GRANT ALL PRIVILEGES ON va_database.* TO 'va_user'@'localhost';
FLUSH PRIVILEGES;
```

### Problème : Emails non envoyés

**Vérifications :**

1. **Test SMTP manuel :**
   ```php
   // test_smtp.php
   require 'includes/PHPMailer/PHPMailer.php';
   $mail = new PHPMailer\PHPMailer\PHPMailer(true);
   $mail->SMTPDebug = 2;  // Verbose debug
   $mail->isSMTP();
   $mail->Host = 'smtp.gmail.com';
   $mail->Port = 587;
   $mail->SMTPAuth = true;
   $mail->Username = 'yourva@gmail.com';
   $mail->Password = 'votre_mot_de_passe_app';
   $mail->SMTPSecure = 'tls';
   $mail->setFrom('yourva@gmail.com');
   $mail->addAddress('test@test.com');
   $mail->Subject = 'Test';
   $mail->Body = 'Test body';
   $mail->send();
   ```

2. **Vérifier logs :**
   ```bash
   grep "SMTP" /var/www/html/scripts/logs/importer_vol_direct.log
   ```

3. **Gmail :** Vérifier "Mot de passe d'application" activé

4. **Serveur bloque port 25/587 ?**
   ```bash
   telnet smtp.gmail.com 587
   ```

### Problème : SimAddon ne se connecte pas

**Vérifications :**

1. **Token valide ?**
   ```sql
   SELECT * FROM simaddon_tokens WHERE pilote_id = X;
   -- Vérifier expires_at > NOW()
   ```

2. **URL API correcte ?**
   - Doit finir par `/api/`
   - Ex: `https://skywings.com/api/`

3. **Firewall bloque requêtes ?**
   ```bash
   # Vérifier logs Apache
   tail -f /var/log/apache2/va-access.log | grep "api_"
   ```

4. **Test API manuellement :**
   ```bash
   curl -X POST https://votre-domaine.com/api/api_getFlotte.php \
     -H "Authorization: Bearer VOTRE_TOKEN" \
     -d "callsign=SKY0001"
   ```

### Problème : Scripts CRON ne s'exécutent pas

**Vérifications :**

1. **CRON fonctionne ?**
   ```bash
   sudo systemctl status cron
   ```

2. **Permissions scripts ?**
   ```bash
   ls -la /var/www/html/scripts/*.php
   # Doivent être lisibles par www-data
   ```

3. **Chemin PHP correct ?**
   ```bash
   which php
   # Utiliser ce chemin dans crontab
   ```

4. **Logs CRON :**
   ```bash
   grep CRON /var/log/syslog
   ```

5. **Tester script manuellement :**
   ```bash
   sudo -u www-data php /var/www/html/scripts/expire_reservations.php
   # Vérifier si erreur
   ```

### Problème : Permissions fichiers incorrectes

**Réinitialiser toutes les permissions :**
```bash
cd /var/www/html/

# Propriétaire
sudo chown -R www-data:www-data .

# Dossiers 755
find . -type d -exec chmod 755 {} \;

# Fichiers 644
find . -type f -exec chmod 644 {} \;

# Logs 770 (écriture)
chmod 770 scripts/logs/

# Scripts exécutables
chmod 750 scripts/*.php
```

### Problème : Sessions déconnectées trop rapidement

**Augmenter durée session :**

```php
// Dans config.php
ini_set('session.gc_maxlifetime', 86400);  // 24 heures
session_set_cookie_params(86400);
```

### Problème : Installation bloquée à l'étape 2 (BDD)

**Erreurs courantes :**

1. **"Can't create database" :**
   ```sql
   -- Utilisateur n'a pas droit CREATE DATABASE
   -- Solution : créer manuellement
   CREATE DATABASE va_database;
   GRANT ALL PRIVILEGES ON va_database.* TO 'va_user'@'localhost';
   ```

2. **"Access denied" :**
   - Vérifier host (`localhost` vs `127.0.0.1`)
   - Vérifier mot de passe

3. **"Unknown database" :**
   - Base n'existe pas
   - Créer manuellement avant installation

---

## ✅ Checklist Finale

Avant de déclarer installation terminée :

### Technique
- [ ] PHP 7.4+ installé et fonctionnel
- [ ] MySQL 5.7+ installé
- [ ] Base de données créée (22 tables)
- [ ] 87 aéroports importés
- [ ] db_connect.php configuré et testé
- [ ] config.php configuré

### Sécurité
- [ ] Dossier /install/ supprimé
- [ ] Compte ADM0001 supprimé
- [ ] Compte admin personnel créé (Super Admin)
- [ ] HTTPS activé (production)
- [ ] Permissions fichiers correctes
- [ ] DEBUG_MODE = false

### Fonctionnalités
- [ ] Connexion/déconnexion OK
- [ ] Menu admin accessible
- [ ] Au moins 1 type d'appareil créé
- [ ] Au moins 1 avion acheté
- [ ] Missions personnalisées créées
- [ ] Emails testés et fonctionnels
- [ ] SimAddon connecté et testé

### Automatisation
- [ ] Scripts CRON configurés
- [ ] Backup BDD automatique configuré
- [ ] Test script mensuel exécuté manuellement

### Personnalisation
- [ ] Logo personnalisé uploadé
- [ ] Nom compagnie configuré
- [ ] Couleurs CSS ajustées (optionnel)

---

## 🎉 Félicitations !

Votre compagnie aérienne virtuelle est maintenant opérationnelle !

**Prochaines étapes :**
1. Inviter vos pilotes
2. Créer vos premières lignes régulières
3. Configurer missions spéciales
4. Suivre les statistiques et finances

**Support :**
- Documentation utilisateur : [USER_GUIDE.md](USER_GUIDE.md)
- Documentation technique : [TECHNICAL_DOCUMENTATION.md](TECHNICAL_DOCUMENTATION.md)
- Issues GitHub : https://github.com/Skall34/simweb/issues
- Discord : https://discord.gg/K52UfAnSdk

**Bon vol ! ✈️**

---

*Guide d'installation créé le 22 décembre 2025*  
*Maintenu par la communauté*
