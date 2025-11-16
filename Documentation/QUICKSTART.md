# 🚀 Guide de Démarrage Rapide - SkyWings

**Pour l'installation complète, consultez [INSTALLATION.md](INSTALLATION.md)**

---

## ⚡ Installation en 5 minutes

### 1️⃣ Préparez votre environnement

**Vous avez besoin de :**
- Un serveur web (Apache/Nginx)
- PHP 7.4+ avec MySQL
- Une base de données MySQL

---

### 2️⃣ Créez la base de données

```sql
CREATE DATABASE skywings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### 3️⃣ Importez les données

**Via PhpMyAdmin :**
1. Sélectionnez votre base `skywings`
2. Onglet "Importer"
3. Choisissez `sql_database/VA_mysql_db_creation.sql`
4. Exécutez

**Puis importez aussi :**
```bash
sql_database/create_session_tokens_table.sql
```

---

### 4️⃣ Configurez la connexion

```bash
# Renommez le fichier exemple
cp includes/db_connect_exemple.php includes/db_connect.php
```

**Éditez `includes/db_connect.php` :**
```php
$host = 'localhost';
$db   = 'skywings';
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

### 6️⃣ Créez votre compte admin

1. Accédez à `http://votre-domaine.com/`
2. Cliquez sur "S'inscrire"
3. Créez votre compte (ex: SKY001)
4. Dans la base de données :
   ```sql
   UPDATE PILOTES SET is_admin = 1 WHERE callsign = 'SKY001';
   ```

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
