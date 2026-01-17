# 🚀 Installation rapide - Virtual Airline System

## ⚠️ IMPORTANT - Lisez attentivement

## Prérequis

- **PHP** 7.4 ou supérieur (8.1+ recommandé)
- **MySQL** 5.7+ ou **MariaDB** 10.2+
- **Extensions PHP** : PDO, pdo_mysql, mbstring, json
- **Serveur web** : Apache

---

## L'assistant d'installation va :

✅ Vérifier votre environnement PHP et MySQL<br>
✅ Créer automatiquement la base de données<br>
✅ Importer toutes les tables nécessaires<br>
✅ Générer les fichiers de configuration<br>
✅ Créer le compte administrateur par défaut<br>

**Aucun fichier à éditer manuellement !**

**Durée totale : 5-10 minutes** ⏱️

---

## 📦 Étape 1 : Uploadez TOUS les fichiers

**IMPORTANT** : Vous devez uploader **l'intégralité du projet**, pas juste le dossier `install/` !

### Via FTP/SCP
Uploadez **TOUS** ces dossiers et fichiers (liste complète) :

**📁 Dossiers obligatoires :**

votre-site/<br>
├── admin/            ← Pages d'administration<br>
├── api/              ← API pour SimAddon (OBLIGATOIRE)<br>
├── assets/           ← Images, documents ACARS<br>
├── css/              ← Feuilles de style (OBLIGATOIRE)<br>
├── Documentation/    ← Documentation complète<br>
├── includes/         ← Configuration PHP (OBLIGATOIRE)<br>
├── install/          ← L'installateur web (OBLIGATOIRE)<br>
├──├── sql_database   ← Fichiers de création de la base de données (OBLIGATOIRE)<br>
├──├── steps          ← Les 5 étapes de l'installeur lui-même<br>
├── lang/             ← Traductions FR/EN/ES (OBLIGATOIRE)<br>
├── pages/            ← Pages du site (OBLIGATOIRE)<br>
├── scripts/          ← Scripts automatisés (OBLIGATOIRE)<br>

**📄 Fichiers obligatoires à la racine :**

├── index.php         ← Page d'accueil<br>
├── lang.php          ← Gestion des langues<br>
├── live_flights.php  ← Vols en cours<br>
├── login.php         ← Connexion<br>
├── logout.php        ← Déconnexion<br>
└── LICENSE.txt       ← Licence MIT<br>


**⚠️ IMPORTANT : Si un seul dossier manque, l'installation échouera !**

## 🧹 Étape 2 : Créez votre base de données MySQL

**IMPORTANT** : Chez la plupart des hébergeurs (OVH, etc.), vous devez **créer la base de données via leur interface web** AVANT de lancer l'installateur.

### Chez OVH (et hébergeurs similaires) :

1. **Connectez-vous à votre espace client OVH**
2. **Allez dans "Bases de données" → "Créer une base de données"**
3. **Notez précieusement** :
   - Nom de la base (ex: `skywinjdemova`)
   - Nom d'utilisateur (ex: `skywinjdemova`)
   - Mot de passe
   - Hôte (ex: `skywinjdemova.mysql.db`)
   - Port (généralement `3306`)

⚠️ **L'installateur ne peut PAS créer la base de données** chez ces hébergeurs, elle doit être créée manuellement.

---

## 🧹 Étape 3 : Connectez-vous sur adresse_de_votre_site/install

1. **Ouvrez votre navigateur** : `http://votre-ip-ou-domaine/install/`

2. **Étape 1 - Vérifications** : Tout doit être vert ✅
   - Si rouge : corrigez les permissions avec les commandes affichées

3. **Étape 2 - Base de données** :
   - Hôte : **celui fourni par OVH** (ex: `skywinjdemova.mysql.db`)
   - Port : **généralement 3306**
   - Nom de la base : **celui que vous avez créé** (ex: `skywinjdemova`)
   - Utilisateur : **celui créé par OVH**
   - Mot de passe : **celui défini lors de la création**

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
   - 3 petites choses restent à faire pour finaliser la création de votre compagnie
   - Modifiez le mot de passe du compte `ADM0001` (Menu Mon Compte)
      - Déconnectez vous du site et enregistrez un nouveau pilote, qui sera l'administrateur du site. ADM0001 étant un SUPER ADMIN, qui à en plus les droits de configuration du site.
      - Reconnectez vous en `ADM0001` et allez via le menu admin/Administration des pilotes, et passez le nouveau pilote en Admin
   - Créer un premier Type d'appareil
   - Acheter un premier appareil



## 🧹 Nettoyage (si réinstallation)

**Seulement si vous réinstallez** :

```bash
# Supprimez les anciennes configurations
rm /var/www/votre-site/config.ini
rm /var/www/votre-site/install/.installed

# Supprimez l'ancienne base de données
sudo mysql -e "DROP DATABASE IF EXISTS nom_ancienne_base;"
```


# 🔒L'installateur est automatiquement verrouillé

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

## 📚 Support

- Documentation complète : `/Documentation/INSTALLATION.md`
- FAQ : `/Documentation/FAQ.md`
- GitHub Issues : https://github.com/Skall34/simweb/issues
---

**Bon vols ! ✈️**

---



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
