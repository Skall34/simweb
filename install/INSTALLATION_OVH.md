# 🚀 Installation sur hébergement OVH (et hébergeurs similaires)

## ⚠️ IMPORTANT - Spécificités OVH

Chez OVH et la plupart des hébergeurs mutualisés, **vous n'avez PAS le privilège de créer des bases de données via SQL**. La base de données doit être créée manuellement via l'interface web.

---

## 📝 Étape par étape

### 1️⃣ Créer la base de données via l'espace client OVH

1. **Connectez-vous** à votre [espace client OVH](https://www.ovh.com/manager/)

2. **Naviguez vers** : `Web Cloud` → `Hébergements` → Votre hébergement → `Bases de données`

3. **Cliquez sur** : `Créer une base de données`

4. **Sélectionnez** :
   - **Moteur** : MySQL ou MariaDB (recommandé)
   - **Version** : La plus récente disponible (8.0+ recommandé)
   - **Type** : Selon votre forfait

5. **Notez PRÉCIEUSEMENT** les informations suivantes :
   ```
   Nom de la base : skywinjdemova (exemple)
   Utilisateur    : skywinjdemova (souvent identique au nom de la base)
   Mot de passe   : [celui que vous définissez]
   Serveur (hôte) : skywinjdemova.mysql.db (fourni par OVH)
   Port           : 3306 (par défaut)
   ```

⚠️ **ASTUCE** : Prenez une capture d'écran de ces informations !

---

### 2️⃣ Uploader les fichiers du projet

Via **FTP** (FileZilla, WinSCP, etc.) ou via le **gestionnaire de fichiers OVH** :

1. **Connectez-vous en FTP** :
   - Hôte : `ftp.votre-domaine.com` ou via l'espace client OVH
   - Utilisateur : fourni par OVH
   - Mot de passe : celui de votre compte FTP

2. **Uploadez TOUS les fichiers** du projet dans le dossier `www` (ou `public_html`)

3. **Vérifiez** que tous les dossiers sont présents :
   ```
   www/
   ├── admin/
   ├── api/
   ├── assets/
   ├── css/
   ├── includes/
   ├── install/          ← IMPORTANT
   ├── lang/
   ├── pages/
   ├── scripts/
   ├── index.php
   ├── login.php
   └── ...
   ```

---

### 3️⃣ Lancer l'installateur

1. **Ouvrez votre navigateur** : `http://votre-domaine.com/install/`

2. **Étape 1 - Vérifications** :
   - Tout doit être **vert** ✅
   - Si rouge 🔴 : les permissions seront corrigées automatiquement si possible

3. **Étape 2 - Configuration base de données** :
   
   Renseignez **exactement** les informations notées à l'étape 1 :
   
   | Champ | Valeur exemple | Où trouver |
   |-------|---------------|------------|
   | **Hôte** | `skywinjdemova.mysql.db` | Espace client OVH → Bases de données |
   | **Port** | `3306` | Par défaut (ne pas changer sauf indication OVH) |
   | **Nom de la base** | `skywinjdemova` | Celui que vous avez créé |
   | **Utilisateur** | `skywinjdemova` | Fourni lors de la création |
   | **Mot de passe** | `********` | Celui que vous avez défini |
   
   ⚠️ **ATTENTION** : Le nom de la base doit correspondre **exactement** à celui créé sur OVH !

4. **Étape 3 - Configuration de votre VA** :
   - Nom de votre compagnie
   - Code ICAO (3-4 lettres)
   - Email de contact
   - URL de votre site (ex: `http://votre-domaine.com`)
   - SMTP (optionnel, vous pouvez le configurer plus tard)

5. **Étape 4 - Installation** :
   - Vérifiez le récapitulatif
   - Cliquez sur **"Lancer l'installation"**
   - ⏱️ **Patientez** 2-5 minutes (import de 80 000+ aéroports)
   - Tous les logs doivent être verts ✓

6. **Étape 5 - Terminé !** 🎉
   - L'installateur est automatiquement verrouillé
   - Vous pouvez accéder à votre site

---

## 🔓 Première connexion

1. **Accédez à** : `http://votre-domaine.com/`

2. **Connectez-vous avec** :
   - Identifiant : `ADM0001`
   - Mot de passe : `admin123`

3. **IMMÉDIATEMENT après** :
   - Changez le mot de passe (Menu → Mon Compte)
   - Créez votre propre compte admin
   - Supprimez le compte ADM0001 (pour la sécurité)

---

## ❌ Erreurs fréquentes OVH

### Erreur : "Access denied for user to database"

**Cause** : Mauvais nom de base de données ou mauvaises informations de connexion

**Solution** :
1. Vérifiez dans l'espace client OVH les informations exactes
2. Le nom de la base doit être **exactement** celui créé
3. Vérifiez que l'utilisateur a bien les droits sur cette base

---

### Erreur : "SQLSTATE[HY000] [2002] Connection refused"

**Cause** : Mauvais hôte de base de données

**Solution** :
1. Vérifiez le nom d'hôte dans l'espace client OVH
2. Format habituel : `nombase.mysql.db` ou `mysql51-XX.perso.ovh.net`
3. N'utilisez PAS `localhost` chez OVH !

---

### Erreur : "Can't connect to MySQL server"

**Cause** : Port incorrect ou firewall

**Solution** :
1. Utilisez le port **3306** (par défaut)
2. Vérifiez que votre IP n'est pas bloquée
3. Contactez le support OVH si le problème persiste

---

### Installation très lente à l'étape 4

**Normal !** L'import de 80 000+ aéroports peut prendre 2-5 minutes selon :
- La vitesse de votre connexion
- La charge du serveur OVH
- La version de MySQL/MariaDB

**Ne rafraîchissez pas la page !** Laissez le processus se terminer.

---

## 🔒 Sécurité post-installation

Après une installation réussie :

1. ✅ Le fichier `install/.installed` est créé automatiquement
2. ✅ L'installateur est verrouillé (ne peut plus être réexécuté)
3. ✅ Le fichier `config.ini` est créé à la racine
4. ⚠️ **Changez IMMÉDIATEMENT le mot de passe de ADM0001**

---

## 🔄 Réinstallation

Si vous devez réinstaller :

### Via FTP :
1. Supprimez `install/.installed`
2. Supprimez `config.ini` à la racine
3. Via phpMyAdmin OVH, supprimez toutes les tables de la base
4. Relancez `http://votre-domaine.com/install/`

### Via SSH (si disponible) :
```bash
cd /home/votre-user/www
rm install/.installed
rm config.ini
# Puis supprimez les tables via phpMyAdmin OVH
```

---

## 📞 Support

### Problème avec l'installateur :
- Documentation complète : `/Documentation/INSTALLATION_GUIDE.md`
- FAQ : `/Documentation/FAQ.md`
- GitHub Issues : https://github.com/Skall34/simweb/issues

### Problème avec OVH :
- [Centre d'aide OVH](https://docs.ovh.com/)
- Support technique OVH : via votre espace client

---

## ✅ Checklist finale

- [ ] Base de données créée sur OVH
- [ ] Informations de connexion notées
- [ ] Tous les fichiers uploadés via FTP
- [ ] Installation terminée avec succès
- [ ] Connexion réussie avec ADM0001
- [ ] Mot de passe ADM0001 changé
- [ ] Premier type d'appareil créé
- [ ] Premier avion acheté
- [ ] Première mission créée

---

**Bon vols ! ✈️**
