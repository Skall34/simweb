# 📚 Documentation - Virtual Airline Management System

Bienvenue dans la documentation complète de votre système de gestion de compagnie aérienne virtuelle !

---

## 🗂️ Structure de la Documentation

Cette documentation est organisée en **3 guides principaux** selon vos besoins :

### 1. 👥 [Guide Utilisateur](USER_GUIDE.md)
**Pour les pilotes et administrateurs**

Apprenez à utiliser le système au quotidien :
- ✈️ Créer un compte et voler
- 🎮 Configurer et utiliser SimAddon (addon MSFS)
- 📊 Suivre vos statistiques et progression
- 👔 Gérer la compagnie (interface admin)
- 💰 Comprendre le système financier
- ❓ FAQ et résolution de problèmes

**👉 [Lire le Guide Utilisateur →](USER_GUIDE.md)**

---

### 2. 🛠️ [Guide d'Installation](INSTALLATION_GUIDE.md)
**Pour installer votre propre compagnie virtuelle**

Instructions complètes pour déployer le système :
- 📋 Prérequis serveur (PHP 7.4+, MySQL 5.7+)
- 🚀 **Méthode 1** : Installation automatique (installateur web)
- 🔧 **Méthode 2** : Installation manuelle (ligne de commande)
- ⚙️ Configuration serveur (Apache/Nginx, SMTP, CRON)
- 🔒 Sécurisation (SSL, permissions, backups)
- 🆘 Dépannage et solutions aux problèmes courants

**👉 [Lire le Guide d'Installation →](INSTALLATION_GUIDE.md)**

---

### 3. 🔬 [Documentation Technique](TECHNICAL_DOCUMENTATION.md)
**Pour les développeurs et contributeurs**

Référence technique complète du système :
- 🏗️ Architecture et stack technologique
- 🗄️ Schéma de base de données (22 tables détaillées)
- 🔌 API REST (15 endpoints SimAddon)
- 🔐 Système d'authentification
- 💸 Moteur de calcul financier
- 🤖 Scripts automatisés (CRON)
- 🐛 Debugging et performance
- 🔒 Sécurité et bonnes pratiques

**👉 [Lire la Documentation Technique →](TECHNICAL_DOCUMENTATION.md)**

---

## ⚡ Démarrage Rapide

### Nouveau Pilote ?

1. **Créer un compte** sur le site de votre compagnie
2. **Installer SimAddon** pour Microsoft Flight Simulator
3. **Configurer votre token** (Mon Compte → Token SimAddon)
4. **Voler et enregistrer** vos vols automatiquement !

📖 **Guide complet** : [USER_GUIDE.md](USER_GUIDE.md)

### Nouveau Administrateur ?

1. **Installer le système** sur votre serveur
2. **Configurer base de données** et variables
3. **Créer votre flotte** d'avions
4. **Inviter des pilotes** à rejoindre la VA

📖 **Guide complet** : [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

### Développeur ?

1. **Cloner le repository** GitHub
2. **Analyser l'architecture** et la base de données
3. **Comprendre l'API** SimAddon
4. **Contribuer** au projet !

📖 **Guide complet** : [TECHNICAL_DOCUMENTATION.md](TECHNICAL_DOCUMENTATION.md)

---

## 🎯 Fonctionnalités Principales

### Pour les Pilotes ✈️

- **Enregistrement automatique** des vols via SimAddon (addon MSFS)
- **Suivi de progression** : heures, grades, salaires
- **Système de réservation** d'avions
- **Missions spéciales** avec majorations de revenus
- **Statistiques détaillées** et historique complet
- **Multi-langue** : Français, Anglais, Espagnol

### Pour les Administrateurs 👔

- **Gestion de flotte** : achats, ventes, maintenance
- **Gestion des pilotes** : activation, promotions, salaires
- **Création de missions** et lignes régulières
- **Tableau de bord financier** complet
- **87 aéroports** pré-chargés avec système de fret
- **Configuration flexible** via variables métier

### Technique 🔧

- **API REST** complète (15 endpoints)
- **Token d'authentification** sécurisé
- **Scripts automatisés** (assurances, salaires, promotions)
- **Trace GPS** des vols
- **Système de notes** ACARS (1-10)
- **Gestion crédit** avions avec intérêts

---

## 📦 Stack Technique

| Composant | Technologie |
|-----------|-------------|
| **Backend** | PHP 7.4+ |
| **Base de données** | MySQL 5.7+ / MariaDB 10.3+ |
| **Serveur web** | Apache 2.4+ / Nginx 1.18+ |
| **Email** | PHPMailer 6.x |
| **Frontend** | HTML5, CSS3, JavaScript (vanilla) |
| **Addon** | SimAddon (C# pour MSFS) |

---

## 📂 Autres Ressources

### Documentation Complémentaire

- **[CHANGELOG.md](CHANGELOG.md)** : Historique des versions et modifications
- **[FAQ.md](FAQ.md)** : Questions fréquentes (technique)
- **[BUG_REPORT_TEMPLATE.md](BUG_REPORT_TEMPLATE.md)** : Template pour signaler des bugs
- **[DISTRIBUTION_CHECKLIST.md](DISTRIBUTION_CHECKLIST.md)** : Checklist avant release
- **[check_installation.php](check_installation.php)** : Script de vérification installation

### Anciennes Documentations

Les anciennes versions de la documentation sont archivées dans **[archive/](archive/)** pour référence historique.

---

## 🆘 Support & Aide

### Problème d'utilisation ?
📖 Consultez le **[Guide Utilisateur](USER_GUIDE.md)** section FAQ

### Problème d'installation ?
📖 Consultez le **[Guide d'Installation](INSTALLATION_GUIDE.md)** section Dépannage

### Bug ou erreur technique ?
🐛 Ouvrez une issue sur **[GitHub](https://github.com/Skall34/simweb/issues)**

### Question générale ?
💬 Rejoignez le **[Discord](https://discord.gg/K52UfAnSdk)**

---

## 🤝 Contribuer

Contributions bienvenues ! Pour contribuer :

1. **Fork** le repository
2. **Créer une branche** : `git checkout -b feature/ma-fonctionnalite`
3. **Commiter** : `git commit -m "Ajout fonctionnalité X"`
4. **Push** : `git push origin feature/ma-fonctionnalite`
5. **Pull Request** sur GitHub

📖 Consultez [TECHNICAL_DOCUMENTATION.md](TECHNICAL_DOCUMENTATION.md) pour comprendre l'architecture.

---

## 📜 Licence

Ce projet est sous licence **GNU General Public License v3.0**.

Voir [LICENSE.txt](../LICENSE.txt) pour plus de détails.

---

## 👏 Crédits

Développé avec ❤️ par la communauté Virtual Airlines.

**Remerciements spéciaux :**
- Tous les pilotes testeurs
- Contributeurs GitHub
- Communauté Discord

---

## 🚀 Versions

**Version actuelle :** 2.0 (Décembre 2025)

**Nouveautés v2.0 :**
- ✅ Documentation complète restructurée
- ✅ Guide utilisateur exhaustif
- ✅ Guide d'installation détaillé
- ✅ Documentation technique approfondie

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique complet.

---

**Bon vol ! ✈️**

*Documentation mise à jour le 22 décembre 2025*
