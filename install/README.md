# 🚀 Installation rapide - Virtual Airline System

## ⚠️ IMPORTANT - Lisez attentivement

Cette installation automatisée va :
- ✅ Créer votre base de données
- ✅ Importer toutes les tables
- ✅ Générer les fichiers de configuration
- ✅ Créer le compte administrateur par défaut

**Durée totale : 5-10 minutes** ⏱️

---

## 📦 Étape 1 : Uploadez TOUS les fichiers

**IMPORTANT** : Vous devez uploader **l'intégralité du projet**, pas juste le dossier `install/` !

### Via Git (recommandé)
```bash
cd /var/www/votre-site/
sudo git clone https://github.com/votre-compte/simweb.git .
```

### Via FTP/SCP
Uploadez **TOUS** ces dossiers et fichiers (liste complète) :

**📁 Dossiers obligatoires :**
```
votre-site/
├── admin/            ← Pages d'administration
├── api/              ← API pour SimAddon (OBLIGATOIRE)
├── assets/           ← Images, documents ACARS
├── css/              ← Feuilles de style (OBLIGATOIRE)
├── Documentation/    ← Documentation complète
├── includes/         ← Configuration PHP (OBLIGATOIRE)
├── install/          ← L'installateur web
├── lang/             ← Traductions FR/EN/ES (OBLIGATOIRE)
├── pages/            ← Pages du site (OBLIGATOIRE)
├── scripts/          ← Scripts automatisés (OBLIGATOIRE)
├── sql_database/     ← Scripts SQL (OBLIGATOIRE)
└── tools/            ← Outils de développement (optionnel)
```

**📄 Fichiers obligatoires à la racine :**
```
├── .htaccess         ← Configuration Apache
├── index.php         ← Page d'accueil
├── lang.php          ← Gestion des langues
├── live_flights.php  ← Vols en cours
├── login.php         ← Connexion
├── logout.php        ← Déconnexion
└── LICENSE.txt       ← Licence MIT
```

**⚠️ IMPORTANT : Si un seul dossier manque, l'installation échouera !**

### ✅ Vérification automatique après upload

**Méthode 1 : Script de vérification complet**
```bash
cd /var/www/votre-site/
bash install/check_files.sh
```
✓ Vérifie tous les dossiers et fichiers  
✓ Affiche ce qui manque en rouge  
✓ Vous dit si vous pouvez continuer  

**Méthode 2 : Vérification manuelle rapide**
```bash
cd /var/www/votre-site/

# Vérifier les dossiers critiques
ls -d admin api assets css includes install lang pages scripts sql_database 2>/dev/null | wc -l
# Doit afficher : 10

# Vérifier les fichiers SQL
ls sql_database/*.sql 2>/dev/null | wc -l  
# Doit afficher : 2

# Si les chiffres ne correspondent pas, des fichiers manquent !
```

---

## 🔐 Étape 2 : Permissions (Linux/Raspberry/VPS uniquement)

```bash
# Allez dans le dossier de votre site
cd /var/www/votre-site/

# Permissions 777 temporaires pour l'installation
sudo chmod -R 777 includes/ scripts/
sudo chown -R www-data:www-data /var/www/votre-site/

# IMPORTANT : Ces permissions seront sécurisées après l'installation
```

**Sur Windows/XAMPP** : Les permissions sont automatiquement OK, passez à l'étape suivante.

---

## 🗄️ Étape 3 : Préparez MySQL

### Sur Raspberry/Debian/Ubuntu
Créez un utilisateur MySQL avec droits complets :

```bash
sudo mysql
```

```sql
CREATE USER 'va_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise';
GRANT ALL PRIVILEGES ON *.* TO 'va_user'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

**Notez bien ces informations, vous en aurez besoin !**
- Utilisateur : `va_user`
- Mot de passe : `VotreMotDePasseSecurise`
- Hôte : `localhost`

### Sur Windows/XAMPP
Utilisez généralement :
- Utilisateur : `root`
- Mot de passe : (vide ou celui que vous avez défini)
- Hôte : `localhost`

---

## 🧹 Étape 4 : Nettoyage (si réinstallation)

**Seulement si vous réinstallez** :

```bash
# Supprimez les anciennes configurations
rm /var/www/votre-site/includes/db_connect.php
rm /var/www/votre-site/includes/config.php
rm /var/www/votre-site/install/.installed

# Supprimez l'ancienne base de données
sudo mysql -e "DROP DATABASE IF EXISTS nom_ancienne_base;"
```

---

## 🎯 Étape 5 : Lancez l'installateur

1. **Ouvrez votre navigateur** : `http://votre-ip-ou-domaine/install/`

2. **Étape 1 - Vérifications** : Tout doit être vert ✅
   - Si rouge : corrigez les permissions avec les commandes affichées

3. **Étape 2 - Base de données** :
   - Hôte : `localhost` (ou `127.0.0.1`)
   - Port : `3306`
   - Nom de la base : **choisissez un nom** (ex: `ma_va`, `skyairlines`, etc.)
   - Utilisateur : celui créé à l'étape 3 (`va_user`)
   - Mot de passe : celui créé à l'étape 3

4. **Étape 3 - Configuration VA** :
   - Nom de votre Virtual Airline
   - Email de contact
   - URL : `http://votre-ip` (sans `/install/` à la fin !)
   - SMTP : optionnel, vous pouvez le configurer plus tard

5. **Étape 4 - Installation** :
   - Vérifiez le récapitulatif
   - Cliquez sur "Lancer l'installation"
   - **Attendez** que tous les logs soient verts ✓

6. **Étape 5 - Terminé !** 🎉

---

## 🔓 Première connexion

1. Allez sur `http://votre-ip/` (pas `/install/`)
2. Connectez-vous avec :
   - **Identifiant** : `ADM0001`
   - **Mot de passe** : `admin123`

3. **IMMÉDIATEMENT après connexion** :
   - Créez votre propre compte administrateur
   - Supprimez le compte `ADM0001` (Menu Admin → Gestion Pilotes)

---

## 🔒 Sécurisation post-installation

```bash
# Remettez les permissions normales
cd /var/www/votre-site/
sudo chmod -R 755 includes/ scripts/
sudo chmod 644 includes/db_connect.php includes/config.php

# L'installateur est automatiquement verrouillé
```

---

## ❌ En cas de problème

### L'installateur reste rouge à l'étape 1

```bash
# Forcez les permissions 777
sudo chmod -R 777 /var/www/votre-site/includes/
sudo chmod -R 777 /var/www/votre-site/scripts/
```

### Erreur de collation MySQL

```bash
# Si erreur "utf8mb4_0900_ai_ci" :
sudo sed -i 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' /var/www/votre-site/sql_database/01_Main_Database.sql
```

### Tables manquantes après installation

```bash
# Importez manuellement
sudo mysql VOTRE_NOM_BASE < /var/www/votre-site/sql_database/01_Main_Database.sql
sudo mysql VOTRE_NOM_BASE < /var/www/votre-site/sql_database/02_Airports_data.sql
```

### Erreur 500 après login

Vérifiez les logs Apache :
```bash
sudo tail -50 /var/log/apache2/error.log
```

---

## 📚 Support

- Documentation complète : `/Documentation/INSTALLATION.md`
- FAQ : `/Documentation/FAQ.md`
- GitHub Issues : https://github.com/Skall34/simweb/issues

---

**Bon vols ! ✈️**

---

## Prérequis

- **PHP** 7.4 ou supérieur (8.1+ recommandé)
- **MySQL** 5.7+ ou **MariaDB** 10.2+
- **Extensions PHP** : PDO, pdo_mysql, mbstring, json
- **Serveur web** : Apache ou Nginx

---

## L'assistant d'installation va :

✅ Vérifier votre environnement PHP et MySQL  
✅ Créer automatiquement la base de données  
✅ Importer toutes les tables nécessaires  
✅ Générer les fichiers de configuration  
✅ Créer le compte administrateur par défaut  

**Aucun fichier à éditer manuellement !**

---

## Après l'installation

1. Connectez-vous avec le compte par défaut :
   - **Identifiant** : `ADM0001`
   - **Mot de passe** : `admin123`

2. **IMPORTANT** : Créez immédiatement votre propre compte admin et supprimez le compte ADM0001

3. Personnalisez votre VA dans le menu administration

---

## En cas de problème

Si l'installation échoue ou si vous devez recommencer :

1. Supprimez le fichier `install/.installed`
2. Supprimez les fichiers `includes/db_connect.php` et `includes/config.php`
3. Rechargez la page d'installation

---

## Besoin d'aide ?

- 📚 **Documentation complète** : `/Documentation/`
- ❓ **FAQ** : `/Documentation/FAQ.md`
- 🐛 **Rapport de bug** : `/Documentation/BUG_REPORT_TEMPLATE.md`

---

## Prochaines étapes après installation

1. **Configuration VA** : Nom, logo, paramètres généraux
2. **Gestion flotte** : Ajoutez vos types d'appareils et avions
3. **Création missions** : Définissez vos routes et missions
4. **ACARS** : Configurez l'addon de suivi de vol (`/assets/acars/`)
5. **Pilotes** : Ouvrez les inscriptions et gérez votre équipage

---

**Bon vols ! ✈️**
