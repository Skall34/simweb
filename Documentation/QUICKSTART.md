# 🚀 Guide de Démarrage Rapide - Virtual Airline System

**Pour l'installation complète, consultez [INSTALLATION.md](INSTALLATION.md)**

---

## ⚡ Installation en 5 minutes

### 0️⃣ Vérification (recommandé)

**Uploadez et lancez** `Documentation/check_installation.php` :
```
http://votre-domaine.com/check_installation.php
```
✅ Vérifiez que tout est OK, puis supprimez le fichier.

### 1️⃣ Préparez votre environnement

**Vous avez besoin de :**
- Un serveur web (Apache/Nginx)
- PHP 7.4+ avec MySQL
- Une base de données MySQL

---

### 2️⃣ Importez les scripts SQL

**Via PhpMyAdmin :**
1. Onglet "Importer"
2. Importez **dans l'ordre** :
   - `sql_database/01_Main_Database.sql` (crée la base + tables)
   - `sql_database/02_Airports_data.sql` (données aéroports)

✅ Un compte admin par défaut est créé : `ADM0001` / `admin123`

---

### 4️⃣ Configurez la connexion

```bash
# Renommez le fichier exemple
cp includes/db_connect_exemple.php includes/db_connect.php
```

**Éditez `includes/db_connect.php` :**
```php
$host = 'localhost';
$db   = 'yourva';
$user = 'votre_utilisateur';
$pass = 'votre_mot_de_passe';
```

---

### 5️⃣ Configurez les emails

**Éditez `includes/mail_utils.php` :**

```php
// Ligne 18
define('ADMIN_EMAIL', 'admin@votre-domaine.com');

// Lignes 24-28
$mail->Host = 'smtp.votre-hebergeur.com';
$mail->Username = 'admin@votre-domaine.com';
$mail->Password = 'votre-mot-de-passe-smtp';
$mail->Port = 587;
```

---

### 6️⃣ Première connexion

1. Accédez à `http://votre-domaine.com/`
2. Connectez-vous avec : `ADM0001` / `admin123`
3. Créez votre propre compte admin via "S'inscrire"
4. Promouvoir votre compte : Admin → Gestion des pilotes
5. ⚠️ **Supprimez le compte `ADM0001`** pour la sécurité

---

## ✅ C'est prêt !

Votre compagnie virtuelle est opérationnelle.

**Prochaines étapes :**
- Configurez les tâches automatiques (voir INSTALLATION.md)
- Personnalisez le nom et le logo
- Créez vos premières missions
- Invitez vos pilotes

---

## 📁 Fichiers à ne PAS oublier

| Fichier | Action | Importance |
|---------|--------|------------|
| `includes/db_connect.php` | Renommer depuis `db_connect_exemple.php` | ⚠️ **CRITIQUE** |
| `includes/mail_utils.php` | Configurer SMTP | 🔴 Importante |
| `sql_database/*.sql` | Importer dans MySQL | ⚠️ **CRITIQUE** |

---

## 🆘 Problème ?

**Page blanche** → Vérifiez les logs PHP  
**Erreur DB** → Vérifiez `db_connect.php`  
**Emails non envoyés** → Vérifiez `mail_utils.php`

📖 **Guide complet :** [INSTALLATION.md](INSTALLATION.md)

---

**Bon vol ! ✈️**
