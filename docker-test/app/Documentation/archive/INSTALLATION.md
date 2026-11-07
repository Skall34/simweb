# 📖 Guide d'Installation - Virtual Airline Management System

**Version :** 2.0  
**Date :** Novembre 2025  
**Langues supportées :** Français, Anglais, Espagnol

---

## 🚀 Installation rapide (RECOMMANDÉ)

### **Nouvelle méthode : Installation automatisée**

Nous avons créé un **installateur web interactif** qui simplifie drastiquement l'installation !

1. **Uploadez** tous les fichiers sur votre serveur web
2. **Accédez** à `http://votre-domaine.com/install/`
3. **Suivez** l'assistant en 5 étapes :
   - ✅ Vérification de l'environnement
   - ✅ Configuration de la base de données
   - ✅ Configuration de votre VA
   - ✅ Installation automatique
   - ✅ Connexion avec le compte par défaut

**C'est tout !** L'installateur génère automatiquement tous les fichiers de configuration et importe la base de données.

➡️ **[Voir le guide rapide](../install/README.md)**

---

## 📋 Table des matières

1. [Installation rapide](#installation-rapide-recommandé)
2. [Installation manuelle](#installation-manuelle-avancée)
3. [Prérequis](#prérequis)
4. [Dépannage](#dépannage)

---

## 🔧 Prérequis

### Serveur Web
- **PHP 7.4** ou supérieur (recommandé : PHP 8.1+)
- **MySQL 5.7** ou supérieur (ou MariaDB 10.3+)
- **Apache** ou **Nginx** avec mod_rewrite activé
- **HTTPS** (certificat SSL recommandé pour la production)

### Extensions PHP requises
- `pdo_mysql`
- `mbstring`
- `json`
- `curl`
- `openssl`
- `session`

### Espace disque
- Minimum : **500 Mo**
- Recommandé : **2 Go** (pour les logs et les données)

---

---

## ✅ Installation réussie - Et maintenant ?

Une fois l'installateur terminé :

1. **Connectez-vous** avec `ADM0001` / `admin123`
2. **Créez votre compte admin** personnel
3. **Supprimez ADM0001** (Menu Admin → Gestion Pilotes)
4. **Configurez votre VA** :
   - Logo : `assets/images/logo.png`
   - Couleurs : `css/styles.css`
   - Types d'appareils : Menu Admin → Types d'appareils
   - Flotte : Menu Admin → Gestion Flotte
   - Missions : Menu Admin → Gestion Missions

5. **Scripts automatisés** (optionnel) :
   ```bash
   crontab -e
   # Ajouter :
   0 0 1 * * php /var/www/votre-site/scripts/assurance_mensuelle.php
   0 0 1 * * php /var/www/votre-site/scripts/paiement_salaires_pilotes.php
   ```

---

## 📦 Installation manuelle (avancée - non recommandée)

⚠️ **Utilisez l'installateur automatique !** Cette méthode est obsolète et complexe.

Si vous devez absolument installer manuellement :

### Étape 1 : Vérification de l'environnement

Avant de commencer l'installation, nous vous recommandons d'utiliser le script de vérification :

1. **Uploadez** le fichier `Documentation/check_installation.php` à la racine de votre serveur
2. **Accédez** à `http://votre-domaine.com/check_installation.php`
3. **Vérifiez** que tous les prérequis sont OK (PHP, extensions, permissions)
4. ⚠️ **Supprimez** ce fichier après vérification

✅ Ce script vous indiquera exactement ce qui manque avant de continuer.

---

## 🌐 Installation du serveur web

### Option 1 : Installation sur hébergement mutualisé

1. **Téléchargez le fichier ZIP** du système
2. **Décompressez** le contenu sur votre ordinateur
3. **Uploadez tous les fichiers** via FTP dans le dossier racine de votre hébergement (généralement `/public_html` ou `/www`)
4. **Vérifiez les permissions** :
   - Dossiers `scripts/logs/` : **755** (lecture/écriture)
   - Tous les autres fichiers : **644**

### Option 2 : Installation sur serveur dédié/VPS

#### Sur Ubuntu/Debian :
```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer Apache, PHP et MySQL
sudo apt install apache2 php php-mysql php-mbstring php-json php-curl mysql-server -y

# Activer mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Créer le dossier pour l'application
sudo mkdir -p /var/www/skywings
sudo chown -R www-data:www-data /var/www/skywings

# Copier les fichiers de l'application
# (décompressez le ZIP dans /var/www/skywings)
```

#### Sur Windows (XAMPP/WAMP) :
1. Installez **XAMPP** ou **WAMP**
2. Décompressez le ZIP dans `C:\xampp\htdocs\yourva\` (ou équivalent)
3. Démarrez Apache et MySQL depuis le panneau de contrôle

---

## 💾 Configuration de la base de données

### Import des scripts SQL

**Deux fichiers SQL sont à importer dans l'ordre :**

#### Via PhpMyAdmin :
1. Connectez-vous à **PhpMyAdmin**
2. Cliquez sur l'onglet **"Importer"**
3. **Premier import** : Sélectionnez `sql_database/01_Main_Database.sql`
   - ✅ Ce fichier crée automatiquement la base de données `VA_Database` et toutes les tables
4. Cliquez sur **"Exécuter"** et patientez (1-2 minutes)
5. **Deuxième import** : Sélectionnez `sql_database/02_Airports_data.sql`
   - ✅ Ce fichier ajoute les données des aéroports
6. Cliquez sur **"Exécuter"** et patientez (peut prendre quelques minutes)

#### Via ligne de commande :
```bash
mysql -u root -p < sql_database/01_Main_Database.sql
mysql -u root -p VA_Database < sql_database/02_Airports_data.sql
```

✅ **La base de données est maintenant créée avec :**
- Toutes les tables nécessaires
- Les données des aéroports
- Un compte administrateur par défaut : **ADM0001** (mot de passe : `admin123`)

---

## ⚙️ Configuration de l'application

### Étape 1 : Configuration de la connexion à la base de données

1. **Localisez le fichier** `includes/db_connect_exemple.php`
2. **Renommez-le** en `includes/db_connect.php`
3. **Éditez le fichier** avec vos identifiants :

```php
<?php
$host = 'localhost';          // Adresse du serveur MySQL
$db   = 'yourva';           // Nom de votre base de données
$user = 'yourva_user';      // Utilisateur MySQL
$pass = 'VotreMotDePasseSecurise';  // Mot de passe MySQL
$charset = 'utf8mb4';

// Ne modifiez pas les lignes suivantes
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit("Erreur de connexion à la base de données : " . $e->getMessage());
}
```

### Étape 2 : Configuration générale de l'application

1. **Localisez le fichier** `includes/config_exemple.php`
2. **Renommez-le** en `includes/config.php`
3. **Éditez le fichier** pour personnaliser votre Virtual Airline :

```php
<?php
// ==================== INFORMATIONS COMPAGNIE ====================

// Nom de votre Virtual Airline
define('VA_NAME', 'Nom de votre VA');  // ⚠️ MODIFIEZ ICI

// Code ICAO de votre compagnie (3-4 lettres)
define('VA_ICAO', 'SKW');

// Code IATA de votre compagnie (2 lettres, optionnel)
define('VA_IATA', 'SW');

// Slogan ou description courte
define('VA_TAGLINE', 'Votre compagnie aérienne virtuelle');

// ==================== CONTACT ====================

// Email de contact principal
define('VA_CONTACT_EMAIL', 'contact@votre-domaine.com');  // ⚠️ MODIFIEZ ICI

// Email administrateur (notifications système)
define('VA_ADMIN_EMAIL', 'admin@votre-domaine.com');  // ⚠️ MODIFIEZ ICI

// ==================== PARAMÈTRES FINANCIERS ====================

// Devise utilisée (EUR, USD, GBP, etc.)
define('VA_CURRENCY', 'EUR');

// Balance de départ pour nouveaux pilotes
define('VA_STARTING_BALANCE', 10000);

// ==================== PARAMÈTRES SYSTÈME ====================

// Fuseau horaire (liste : https://www.php.net/manual/fr/timezones.php)
define('VA_TIMEZONE', 'Europe/Paris');  // ⚠️ MODIFIEZ selon votre région

// Langue par défaut (fr, en, es)
define('VA_DEFAULT_LANGUAGE', 'fr');

// Activer l'inscription des nouveaux pilotes
define('VA_REGISTRATION_ENABLED', true);
```

> **📝 Note** : Ce fichier centralise tous les paramètres de votre VA. Vous pourrez personnaliser le nom, les emails, la devise, etc. sans toucher au code.

### Étape 3 : Vérification de la connexion

Accédez à votre site : `http://votre-domaine.com/`

✅ **Si tout fonctionne** : Vous verrez la page d'accueil avec le nom de votre VA  
❌ **Si erreur** : Vérifiez vos identifiants de base de données

---

## 📧 Configuration des emails

Le système utilise **PHPMailer** pour envoyer des emails (notifications, récapitulatifs, etc.)

### Étape 1 : Configuration du serveur SMTP

Éditez le fichier `includes/mail_utils.php` :

```php
// Ligne 18 : Adresse email de l'administrateur
define('ADMIN_EMAIL', 'admin@votre-domaine.com');

// Lignes 24-28 : Configuration SMTP
$mail->Host = 'smtp.votre-hebergeur.com';  // Serveur SMTP
$mail->Username = 'admin@votre-domaine.com';  // Email SMTP
$mail->Password = 'VotreMotDePasseSMTP';      // Mot de passe SMTP
$mail->SMTPSecure = 'tls';                    // 'tls' ou 'ssl'
$mail->Port = 587;                            // 587 (TLS) ou 465 (SSL)
```

### Exemples de configuration SMTP populaires :

#### Gmail :
```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'votre-email@gmail.com';
$mail->Password = 'votre-mot-de-passe-application';  // Voir : https://support.google.com/accounts/answer/185833
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

#### OVH :
```php
$mail->Host = 'ssl0.ovh.net';
$mail->Username = 'admin@votre-domaine.com';
$mail->Password = 'votre-mot-de-passe';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

#### Office 365 / Outlook :
```php
$mail->Host = 'smtp.office365.com';
$mail->Username = 'admin@votre-domaine.com';
$mail->Password = 'votre-mot-de-passe';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

### Étape 2 : Test des emails

Les emails sont envoyés automatiquement pour :
- Inscription d'un nouveau pilote
- Réinitialisation de mot de passe
- Promotions de grades
- Récapitulatifs mensuels des scripts

---

## ⏰ Configuration des tâches planifiées

Les scripts automatiques permettent de maintenir le système à jour (assurances, salaires, promotions, etc.)

### Scripts disponibles dans le dossier `scripts/` :

| Script | Fréquence | Description |
|--------|-----------|-------------|
| `assurance_mensuelle.php` | 1x/mois (1er à 3h) | Prélève l'assurance sur tous les avions |
| `credit_mensualite.php` | 1x/mois (1er à 2h) | Prélève les mensualités des avions à crédit |
| `paiement_salaires_pilotes.php` | 1x/mois (1er à 1h) | Paie les pilotes selon leurs heures de vol |
| `promotion_grades_pilotes.php` | 1x/mois (1er à 23h) | Promeut les pilotes selon leurs heures |
| `update_fret.php` | 1x/semaine (vendredi 4h) | Ajoute du fret aléatoire aux aéroports |
| `expire_reservations.php` | 1x/jour (2h) | Annule les réservations expirées |
| `maintenance.php` | 1x/mois (1er à 4h) | Applique l'usure aux avions |
| `rotate_logs.php` | 1x/mois (1er à 5h) | Archive les anciens logs |

### Configuration sur Linux (crontab) :

```bash
# Éditer le crontab
sudo crontab -e

# Ajouter ces lignes (ajustez le chemin /var/www/yourva) :
0 1 1 * * /usr/bin/php /var/www/yourva/scripts/paiement_salaires_pilotes.php
0 2 1 * * /usr/bin/php /var/www/yourva/scripts/credit_mensualite.php
0 3 1 * * /usr/bin/php /var/www/yourva/scripts/assurance_mensuelle.php
0 4 1 * * /usr/bin/php /var/www/yourva/scripts/maintenance.php
0 5 1 * * /usr/bin/php /var/www/yourva/scripts/rotate_logs.php
0 23 1 * * /usr/bin/php /var/www/yourva/scripts/promotion_grades_pilotes.php
0 4 * * 5 /usr/bin/php /var/www/yourva/scripts/update_fret.php
0 2 * * * /usr/bin/php /var/www/yourva/scripts/expire_reservations.php
```

### Configuration sur Windows (Planificateur de tâches) :

1. Ouvrez le **Planificateur de tâches Windows**
2. Créez une nouvelle tâche :
   - **Déclencheur** : Quotidien / Mensuel selon le script
   - **Action** : Démarrer un programme
   - **Programme** : `C:\xampp\php\php.exe`
   - **Arguments** : `C:\xampp\htdocs\yourva\scripts\nom_du_script.php`

### Configuration sur hébergement mutualisé (cPanel) :

1. Connectez-vous à **cPanel**
2. Cherchez **"Tâches Cron"** ou **"Cron Jobs"**
3. Ajoutez les tâches avec la syntaxe :
   ```
   0 1 1 * * /usr/bin/php /home/votre-user/public_html/scripts/paiement_salaires_pilotes.php
   ```

---

## 🚀 Premier lancement

### Étape 1 : Accéder au site

Ouvrez votre navigateur et accédez à :
```
http://votre-domaine.com/
```

Vous devriez voir la page d'accueil avec :
- Le logo de votre compagnie
- Les vols en cours (aucun pour l'instant)
- Un formulaire de connexion

### Étape 2 : Connexion avec le compte administrateur par défaut

1. Connectez-vous avec les identifiants suivants :
   - **Callsign** : `ADM0001`
   - **Mot de passe** : `admin123`

2. ✅ Vous devriez maintenant voir le menu **"Admin"** en haut de la page

### Étape 3 : Créer votre propre compte administrateur

⚠️ **IMPORTANT pour la sécurité** : Le compte `ADM0001` doit être supprimé après cette étape.

1. Cliquez sur **"Déconnexion"**
2. Cliquez sur **"S'inscrire"** (ou **"Register"**)
3. Remplissez le formulaire avec **vos informations** :
   - **Callsign** : Votre indicatif (ex: ABC0001)
   - **Nom** et **Prénom** : Vos informations
   - **Email** : Votre email réel
   - **Mot de passe** : Un mot de passe sécurisé
4. Validez l'inscription

### Étape 4 : Promouvoir votre compte en administrateur

Reconnectez-vous avec le compte **ADM0001**, puis :

1. Allez dans **Admin** → **Gestion des pilotes**
2. Trouvez votre nouveau compte dans la liste
3. Cochez la case **"Admin"** sur votre ligne
4. Enregistrez

### Étape 5 : Supprimer le compte par défaut

⚠️ **Critique pour la sécurité** :

1. Déconnectez-vous de `ADM0001`
2. Reconnectez-vous avec **votre propre compte**
3. Allez dans **Admin** → **Gestion des pilotes**
4. **Supprimez** le compte `ADM0001`

✅ Votre installation est maintenant sécurisée !

---

## 🎨 Personnalisation (optionnel)

### Changer le nom de la compagnie

Éditez le fichier `includes/header.php` :
```php
<div class="nom-compagnie">Votre VA</div>  <!-- Ligne 25 environ -->
```

### Changer le logo

Remplacez le fichier `assets/images/logo.png` par votre propre logo (PNG, 150x150px recommandé)

### Modifier les couleurs

Éditez le fichier `css/styles.css` :
```css
/* Couleur principale (bleu foncé) */
.btn {
  background-color: #004080;  /* Ligne 235 - Changez cette valeur */
}
```

---

## 🔍 Dépannage

### 💡 Utilisez le script de diagnostic

En cas de problème, utilisez le script de vérification :
```
http://votre-domaine.com/check_installation.php
```
Il vous indiquera exactement ce qui ne fonctionne pas (extensions PHP, permissions, connexion base de données, etc.).

⚠️ N'oubliez pas de le supprimer après utilisation.

---

### Problème : Page blanche

**Cause** : Erreur PHP non affichée

**Solution** :
1. Activez l'affichage des erreurs dans `includes/db_connect.php` :
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
2. Vérifiez les logs Apache : `/var/log/apache2/error.log`

### Problème : "Erreur de connexion à la base de données"

**Causes possibles** :
- Identifiants incorrects dans `db_connect.php`
- MySQL non démarré
- Pare-feu bloquant le port 3306

**Solution** :
```bash
# Vérifier que MySQL fonctionne
sudo systemctl status mysql

# Tester la connexion
mysql -u yourva_user -p yourva
```

### Problème : Les emails ne partent pas

**Causes possibles** :
- Configuration SMTP incorrecte
- Pare-feu bloquant les ports SMTP

**Solution** :
1. Vérifiez `includes/mail_utils.php`
2. Testez avec un script simple :
```bash
php -r "echo mail('test@example.com', 'Test', 'Corps du message') ? 'OK' : 'ERREUR';"
```

### Problème : Erreur 500 après modification

**Cause** : Erreur de syntaxe PHP

**Solution** :
1. Vérifiez les logs Apache
2. Annulez la dernière modification
3. Vérifiez la syntaxe avec :
```bash
php -l votre-fichier.php
```

### Problème : Les scripts automatiques ne fonctionnent pas

**Causes possibles** :
- Cron non configuré
- Permissions insuffisantes sur le dossier `scripts/logs/`

**Solution** :
```bash
# Donner les bonnes permissions
sudo chmod 755 scripts/logs/
sudo chown -R www-data:www-data scripts/logs/

# Tester manuellement un script
php scripts/assurance_mensuelle.php
```

### Problème : "RewriteEngine not available"

**Cause** : mod_rewrite non activé

**Solution** :
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📚 Ressources supplémentaires

### Documentation technique
Consultez les pages de documentation dans le menu **Admin → Documentation** pour comprendre le fonctionnement interne des scripts.

### Support
- **GitHub** : [Signaler un bug](https://github.com/Skall34/simweb/issues)
- **Discord** : [Rejoindre la communauté](https://discord.gg/K52UfAnSdk)

### SimAddon (client pour Flight Simulator)
Pour que vos pilotes puissent enregistrer automatiquement leurs vols depuis Microsoft Flight Simulator, ils doivent installer **SimAddon**.

Documentation disponible dans `assets/acars/DocumentationUtilisateurSimAddon.pdf`

---

## ✅ Checklist finale

Avant de mettre en production, vérifiez que :

- [ ] La base de données est importée et accessible
- [ ] Le fichier `db_connect.php` est configuré avec les bons identifiants
- [ ] Les emails sont configurés et testés
- [ ] Un compte administrateur est créé et fonctionnel
- [ ] Les tâches cron sont configurées (optionnel mais recommandé)
- [ ] Le HTTPS est activé (certificat SSL)
- [ ] Les permissions des dossiers sont correctes (755 pour logs/)
- [ ] Le site est accessible depuis l'extérieur
- [ ] Toutes les pages se chargent sans erreur
- [ ] Le changement de langue fonctionne

---

## 🎉 Félicitations !

Votre compagnie aérienne virtuelle est maintenant opérationnelle !

Vous pouvez :
- Créer des missions personnalisées
- Gérer votre flotte d'avions
- Suivre les performances de vos pilotes
- Consulter les statistiques et finances

**Bon vol ! ✈️**

---

*Guide créé avec ❤️ par la communauté de simulation de vol*  
*Version 2.0 - Novembre 2025*
