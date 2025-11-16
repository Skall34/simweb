# ❓ FAQ - SkyWings Virtual Airline

**Questions fréquemment posées et leurs réponses**

---

## 📦 Installation

### Q: Quels sont les prérequis pour installer SkyWings ?
**R:** 
- PHP 7.4 ou supérieur (recommandé 8.1+)
- MySQL 5.7+ ou MariaDB 10.3+
- Serveur web Apache ou Nginx
- Extensions PHP : pdo_mysql, mbstring, json, curl, openssl

### Q: Où puis-je héberger SkyWings ?
**R:** SkyWings fonctionne sur :
- Hébergement mutualisé (si PHP 7.4+ disponible)
- VPS / Serveur dédié
- Local (XAMPP, WAMP, MAMP)
- Docker (configuration à adapter)

### Q: Combien d'espace disque faut-il ?
**R:** Minimum 500 Mo, recommandé 2 Go pour les logs et données à long terme.

### Q: Le HTTPS est-il obligatoire ?
**R:** Fortement recommandé pour la sécurité, mais pas obligatoire pour tester en local.

---

## 🔧 Configuration

### Q: Comment renommer `db_connect_exemple.php` ?
**R:** 
```bash
# Linux/Mac
cp includes/db_connect_exemple.php includes/db_connect.php

# Windows (ligne de commande)
copy includes\db_connect_exemple.php includes\db_connect.php
```
Puis éditez `db_connect.php` avec vos identifiants.

### Q: Où configurer les emails ?
**R:** Dans le fichier `includes/mail_utils.php`, lignes 18-28. Configurez votre serveur SMTP.

### Q: Puis-je utiliser Gmail pour les emails ?
**R:** Oui ! Configuration exemple :
```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'votre-email@gmail.com';
$mail->Password = 'mot-de-passe-application';  // Pas votre mot de passe normal !
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```
Note : Créez un "mot de passe d'application" dans les paramètres Google.

### Q: Comment changer le nom de la compagnie ?
**R:** Éditez le fichier `includes/config.php` et modifiez les constantes :
```php
// Nom de votre Virtual Airline
define('VA_NAME', 'Votre Nom de VA');

// Code ICAO (3-4 lettres)
define('VA_ICAO', 'VVA');

// Emails de contact
define('VA_CONTACT_EMAIL', 'contact@votre-domaine.com');
define('VA_ADMIN_EMAIL', 'admin@votre-domaine.com');
```
Le nom s'affichera automatiquement partout sur le site !

> **Note avancée** : Pour personnaliser complètement (y compris dans les traductions), vous pouvez aussi rechercher/remplacer "SkyWings" par votre nom dans les fichiers `lang/fr.php`, `lang/en.php`, `lang/es.php`.

---

## 👤 Comptes & Administration

### Q: Comment créer un compte administrateur ?
**R:** 
1. Inscrivez-vous normalement sur le site
2. Dans la base de données, exécutez :
   ```sql
   UPDATE PILOTES SET is_admin = 1 WHERE callsign = 'VOTRE_CALLSIGN';
   ```
3. Reconnectez-vous pour voir le menu Admin

### Q: Puis-je avoir plusieurs administrateurs ?
**R:** Oui, répétez l'opération SQL ci-dessus pour chaque pilote à promouvoir admin.

### Q: Comment supprimer un pilote ?
**R:** Via le panel Admin → Gestion Pilotes → Désactiver le compte (recommandé) ou supprimer dans la base.

### Q: J'ai oublié mon mot de passe admin, que faire ?
**R:** Utilisez "Mot de passe oublié" sur la page de connexion, ou réinitialisez directement dans la base :
```sql
UPDATE PILOTES SET password = '$2y$10$...' WHERE callsign = 'SKY001';
```
(Générez le hash avec password_hash() en PHP)

---

## 🌍 Langues

### Q: Comment ajouter une nouvelle langue ?
**R:** 
1. Copiez `lang/fr.php` vers `lang/xx.php` (xx = code langue)
2. Traduisez toutes les valeurs (gardez les clés identiques)
3. Ajoutez l'option dans `includes/header.php` dans le sélecteur de langue

### Q: Puis-je modifier les traductions existantes ?
**R:** Oui ! Éditez directement les fichiers dans `lang/` (fr.php, en.php, es.php).

### Q: Comment changer la langue par défaut ?
**R:** Dans `lang.php`, ligne ~5, modifiez :
```php
$lang = $_SESSION['lang'] ?? 'fr';  // Changez 'fr' par 'en' ou 'es'
```

---

## 🛩️ Utilisation

### Q: Comment ajouter un avion à la flotte ?
**R:** Admin → Gestion Flotte → Remplir le formulaire "Acheter un nouvel appareil".

### Q: Qu'est-ce que SimAddon ?
**R:** C'est l'addon MSFS qui enregistre automatiquement les vols depuis Flight Simulator vers SkyWings. Documentation dans `assets/acars/`.

### Q: Les pilotes peuvent-ils enregistrer des vols manuellement ?
**R:** Oui, via la page "Saisie manuelle" (nécessite connexion).

### Q: Comment créer une mission ?
**R:** Admin → Gestion Missions → Créer une nouvelle mission avec dates, aéroports, récompenses.

### Q: Les grades sont-ils automatiques ?
**R:** Oui ! Le script `promotion_grades_pilotes.php` promeut automatiquement selon les heures de vol (si configuré en cron).

---

## ⏰ Tâches Automatiques

### Q: Les scripts automatiques sont-ils obligatoires ?
**R:** Non, mais fortement recommandés pour :
- Payer les salaires
- Facturer les assurances
- Promouvoir les pilotes
- Ajouter du fret

### Q: Comment configurer les cron sur Linux ?
**R:** 
```bash
sudo crontab -e
# Ajoutez les lignes du guide INSTALLATION.md
```

### Q: Comment faire sur hébergement mutualisé ?
**R:** Utilisez le panneau cPanel → "Cron Jobs" avec la même syntaxe.

### Q: Puis-je lancer les scripts manuellement ?
**R:** Oui ! En ligne de commande :
```bash
php scripts/assurance_mensuelle.php
```
Ou créez une page admin pour les lancer (sécurisée).

### Q: À quelle fréquence doivent-ils tourner ?
**R:** Voir le tableau dans INSTALLATION.md. Exemple :
- Salaires : 1x/mois
- Fret : 1x/semaine
- Réservations expirées : 1x/jour

---

## 💾 Base de données

### Q: Puis-je utiliser un autre nom de base que "skywings" ?
**R:** Oui, choisissez le nom que vous voulez lors de la création, et mettez-le dans `db_connect.php`.

### Q: Comment sauvegarder ma base de données ?
**R:** Via PhpMyAdmin → Exporter, ou en ligne de commande :
```bash
mysqldump -u user -p skywings > backup_$(date +%Y%m%d).sql
```

### Q: Comment restaurer une sauvegarde ?
**R:** 
```bash
mysql -u user -p skywings < backup_20251115.sql
```

### Q: La base est trop grosse, comment la nettoyer ?
**R:** Archivez les anciens vols, supprimez les logs via le script `rotate_logs.php`.

---

## 🐛 Dépannage

### Q: Page blanche, que faire ?
**R:** 
1. Activez l'affichage des erreurs dans `db_connect.php` :
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Consultez les logs Apache : `/var/log/apache2/error.log`

### Q: "Erreur de connexion à la base de données"
**R:** 
- Vérifiez `includes/db_connect.php` (host, user, pass, dbname)
- Vérifiez que MySQL est démarré : `sudo systemctl status mysql`
- Testez la connexion : `mysql -u user -p dbname`

### Q: Les emails ne partent pas
**R:** 
- Vérifiez `includes/mail_utils.php` (SMTP)
- Vérifiez que les ports ne sont pas bloqués (587, 465)
- Testez avec un email simple depuis PHP

### Q: Erreur 500 après modification
**R:** Erreur de syntaxe PHP. Vérifiez avec :
```bash
php -l votre-fichier.php
```

### Q: mod_rewrite non disponible
**R:** 
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Q: Les images ne s'affichent pas
**R:** Vérifiez les chemins et permissions du dossier `assets/images/` (755).

---

## 🔐 Sécurité

### Q: Comment sécuriser l'installation ?
**R:** 
- Utilisez HTTPS (certificat SSL)
- Mots de passe forts pour MySQL et admin
- Mettez à jour PHP régulièrement
- Permissions correctes (755 pour dossiers, 644 pour fichiers)
- Ne commitez jamais `db_connect.php` sur Git

### Q: Les mots de passe sont-ils sécurisés ?
**R:** Oui, SkyWings utilise `password_hash()` avec bcrypt (très sécurisé).

### Q: Puis-je activer l'authentification 2FA ?
**R:** Pas nativement dans v2.0, mais peut être ajouté via une extension.

---

## 📊 Performance

### Q: Mon site est lent, que faire ?
**R:** 
- Activez le cache MySQL
- Optimisez la base (index, nettoyage)
- Utilisez un CDN pour les assets
- Passez à PHP 8.1+ (plus rapide)

### Q: Combien d'utilisateurs peut gérer SkyWings ?
**R:** Testé jusqu'à 100 pilotes actifs. Au-delà, optimisations nécessaires.

---

## 🔄 Mises à jour

### Q: Comment mettre à jour vers une nouvelle version ?
**R:** 
1. Sauvegardez la base et les fichiers
2. Téléchargez la nouvelle version
3. Remplacez les fichiers (sauf `db_connect.php`)
4. Exécutez les scripts de migration SQL si fournis
5. Testez !

### Q: Mes modifications seront-elles écrasées ?
**R:** Oui si vous écrasez les fichiers. Documentez vos changements pour les réappliquer.

---

## 💬 Communauté

### Q: Où obtenir de l'aide ?
**R:** 
- GitHub Issues : Bugs et features
- Discord : Support communauté
- Email : Voir page Contact du site

### Q: Puis-je contribuer au projet ?
**R:** Oui ! Fork sur GitHub, faites vos modifs, créez une Pull Request.

### Q: Où signaler un bug ?
**R:** Sur GitHub Issues avec le template BUG_REPORT_TEMPLATE.md.

### Q: Puis-je suggérer une fonctionnalité ?
**R:** Oui, sur GitHub Issues avec le label "enhancement".

---

## 📜 Licence

### Q: SkyWings est-il gratuit ?
**R:** Oui, 100% gratuit et open-source (licence MIT).

### Q: Puis-je le modifier ?
**R:** Oui, vous pouvez adapter SkyWings à vos besoins.

### Q: Puis-je le redistribuer ?
**R:** Oui, tant que vous respectez la licence MIT (crédit à l'auteur).

### Q: Puis-je l'utiliser commercialement ?
**R:** Oui, la licence MIT le permet.

---

## 🆘 Besoin d'aide supplémentaire ?

**Cette FAQ ne répond pas à votre question ?**

- 📖 Consultez [INSTALLATION.md](INSTALLATION.md) pour le guide détaillé
- 🚀 Voir [QUICKSTART.md](QUICKSTART.md) pour le démarrage rapide
- 🐛 Utilisez [BUG_REPORT_TEMPLATE.md](BUG_REPORT_TEMPLATE.md) pour signaler un problème
- 💬 Rejoignez le Discord : https://discord.gg/K52UfAnSdk
- 📧 Email : Formulaire de contact sur le site

---

**FAQ mise à jour : Novembre 2025 - Version 2.0**
