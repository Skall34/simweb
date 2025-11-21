# ✈️ Système de Gestion de Compagnie Aérienne Virtuelle

![Version](https://img.shields.io/badge/version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)
![Languages](https://img.shields.io/badge/languages-FR%20%7C%20EN%20%7C%20ES-orange)

Un système complet de gestion de compagnie aérienne virtuelle pour les communautés Microsoft Flight Simulator. Il inclut le suivi des vols, la gestion de flotte, les statistiques des pilotes, les missions et une intégration complète avec le client SimAddon pour l'enregistrement automatique des vols.

---

## 🌟 Fonctionnalités

### Pour les Pilotes
- ✈️ **Enregistrement Automatique des Vols** via SimAddon (addon MSFS)
- 📊 **Statistiques Personnelles** (heures, grades, salaires)
- 🗺️ **Missions Personnalisées** (vols spéciaux, fret, humanitaire)
- 🛩️ **Système de Réservation d'Appareils**
- 📈 **Progression de Grade** basée sur les heures de vol
- 💰 **Économie Virtuelle** (salaires, achats d'appareils)
- 🌍 **Multilingue** (Français, Anglais, Espagnol)

### Pour les Administrateurs
- 🏢 **Gestion de Flotte** (achat, vente, maintenance)
- 👥 **Gestion des Pilotes** (grades, activation, statistiques)
- 🎯 **Création de Missions** (routes, événements spéciaux)
- 📧 **Notifications Email** (promotions, rapports)
- 💵 **Tableau de Bord Financier** (revenus, dépenses, balance)
- ⚙️ **Scripts Automatisés** (assurance, salaires, maintenance)
- 🔧 **Panneau de Configuration** (variables, aéroports, types d'appareils)

---

## 📋 Prérequis

- **PHP 7.4+** (recommandé 8.1+)
- **MySQL 5.7+** ou MariaDB 10.3+
- **Apache** ou Nginx avec mod_rewrite
- **PHPMailer** (inclus)
- **Certificat SSL** (recommandé pour la production)

---

## 🚀 Démarrage Rapide

### **NOUVEAU : Installateur Web Automatisé** ⚡

La façon la plus simple d'installer ! Seulement 3 étapes :

1. **Téléversez** tous les fichiers sur votre serveur web
2. **Accédez** à `http://votre-domaine.com/install/`
3. **Suivez** l'assistant interactif (5 minutes max)

L'installateur fait automatiquement :
- ✅ Vérification de votre environnement
- ✅ Création de la base de données
- ✅ Génération des fichiers de configuration
- ✅ Import de toutes les données
- ✅ Création du compte administrateur par défaut

➡️ **[Guide d'installation complet](install/README.md)**

---

### Installation Manuelle (Avancé)

Si vous préférez une installation manuelle :

#### 1. Vérifier l'Environnement (Recommandé)
Téléversez et exécutez `Documentation/check_installation.php` :
```
http://votre-domaine.com/check_installation.php
```

#### 2. Configuration Base de Données
```bash
# Importer les scripts SQL dans l'ordre
mysql -u root -p < sql_database/01_Main_Database.sql
mysql -u root -p VA_Database < sql_database/02_Airports_data.sql
```

#### 3. Configurer les Fichiers
Éditez `includes/db_connect.php` et `includes/config.php` avec vos identifiants.

#### 4. Première Connexion
Connectez-vous avec `ADM0001` / `admin123`, créez votre compte admin, puis supprimez ADM0001.

📖 **Instructions détaillées :**
- 🇫🇷 [INSTALLATION.md](INSTALLATION.md) (Français)
- 🇬🇧 [INSTALLATION_EN.md](INSTALLATION_EN.md) (English)

---

## 📁 Structure du Projet

```
yourva/
├── admin/              # Pages d'administration
├── api/                # Points d'API pour SimAddon
├── assets/             # Images, documentation ACARS
├── css/                # Feuilles de style
├── includes/           # Utilitaires PHP, base de données, authentification
├── lang/               # Traductions (fr.php, en.php, es.php)
├── pages/              # Pages publiques (vols, stats, missions...)
├── scripts/            # Scripts de maintenance automatisés
├── sql_database/       # Création & structure de la base de données
```

---

## 🔄 Scripts Automatisés

SkyWings inclut des scripts automatisés pour des opérations de compagnie réalistes :

| Script | Fréquence | Fonction |
|--------|-----------|----------|
| `assurance_mensuelle.php` | Mensuel | Facture l'assurance sur tous les appareils |
| `credit_mensualite.php` | Mensuel | Traite les paiements des prêts |
| `paiement_salaires_pilotes.php` | Mensuel | Paie les salaires des pilotes |
| `promotion_grades_pilotes.php` | Mensuel | Promeut les pilotes selon leurs heures |
| `maintenance.php` | Mensuel | Applique l'usure aux appareils |
| `update_fret.php` | Hebdomadaire | Ajoute du fret aux aéroports |
| `expire_reservations.php` | Quotidien | Annule les réservations expirées |

Configurez avec cron (Linux) ou Planificateur de Tâches (Windows) - voir le guide d'installation.

---

## 🌐 Support Multilingue

Interface complète traduite en 3 langues :
- 🇫🇷 **Français**
- 🇬🇧 **English** (Anglais)
- 🇪🇸 **Español** (Espagnol)

**944 clés de traduction** couvrant toutes les pages et fonctionnalités.

---

## 🔌 Intégration SimAddon

**SimAddon** est l'addon MSFS compagnon qui enregistre automatiquement les vols :
- Suivi en temps réel des vols
- Téléversement automatique des données (départ, arrivée, durée, carburant)
- Enregistrement de la trace GPS
- Authentification par token

Documentation : `assets/acars/DocumentationUtilisateurSimAddon.pdf`

---

## 🛠️ Configuration

### Configuration Email
Éditez `includes/mail_utils.php` :
```php
define('ADMIN_EMAIL', 'admin@votre-domaine.com');
$mail->Host = 'smtp.votre-hote.com';
$mail->Username = 'admin@votre-domaine.com';
$mail->Password = 'votre-mot-de-passe';
```

### Personnalisation
- **Nom de la compagnie** : `includes/header.php`
- **Logo** : `assets/images/logo.png`
- **Couleurs** : `css/styles.css`

---

## 📚 Documentation

- **Guide Utilisateur** : Disponible dans l'application sous le menu "Documentation"
- **Guide Admin** : Accessible via le panneau Admin
- **Référence API** : Voir le dossier `api/` pour l'intégration SimAddon
- **Documentation des Scripts** : Documentation détaillée dans `pages/doc_scripts/`

---

## 🤝 Contribuer

Les contributions sont bienvenues ! Merci de :
1. Forker le dépôt
2. Créer une branche de fonctionnalité
3. Commiter vos changements
4. Pusher et créer une Pull Request

---

## 🐛 Support

- **Issues** : [GitHub Issues](https://github.com/Skall34/simweb/issues)
- **Discord** : [Rejoindre notre communauté](https://discord.gg/K52UfAnSdk)
- **Email** : Contact via le formulaire de contact dans l'application

---

## 📜 Licence

Ce projet est sous licence MIT - voir [LICENSE.txt](LICENSE.txt) pour les détails.

---

## 🙏 Crédits

Créé avec ❤️ par la communauté de simulation de vol pour les passionnés de compagnies aériennes virtuelles du monde entier.

**Remerciements particuliers à :**
- Tous les bêta-testeurs et contributeurs
- La communauté MSFS
- Les développeurs de PHPMailer

---

## 🎯 Feuille de Route

- [ ] Améliorations du design responsive mobile
- [ ] API REST pour intégrations tierces
- [ ] Statistiques et analyses avancées
- [ ] Système d'événements multijoueurs
- [ ] Carte de vol en temps réel avec WebSocket
- [ ] Intégration avec données météo réelles

---

**Bon vol ! ✈️**

*Pour les instructions d'installation détaillées, référez-vous à INSTALLATION.md (Français) ou INSTALLATION_EN.md (English)*
