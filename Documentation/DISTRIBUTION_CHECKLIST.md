# ✅ Checklist de Distribution - SkyWings

**Avant de partager SkyWings avec la communauté, vérifiez cette checklist.**

---

## 📦 Préparation du Package

### Fichiers à inclure
- [ ] Tous les fichiers PHP du projet
- [ ] Dossier `assets/` complet (images, ACARS)
- [ ] Dossier `css/` avec styles.css
- [ ] Dossier `lang/` avec les 3 fichiers de traduction
- [ ] Dossier `sql_database/` avec tous les fichiers .sql
- [ ] `LICENSE.txt`
- [ ] `README.md`
- [ ] `INSTALLATION.md` (FR)
- [ ] `INSTALLATION_EN.md` (EN)
- [ ] `QUICKSTART.md`
- [ ] `CHANGELOG.md`

### Fichiers à EXCLURE
- [ ] ✅ `includes/db_connect.php` (données sensibles)
- [ ] ✅ `scripts/logs/*.log` (logs personnels)
- [ ] ✅ `.git/` (historique Git)
- [ ] ✅ `.env` ou fichiers de config locaux
- [ ] ✅ Dossier `tools/` (si contient des scripts de dev)

### Fichiers à vérifier
- [ ] `includes/db_connect_exemple.php` présent et bien nommé
- [ ] `includes/mail_utils.php` contient des valeurs génériques
- [ ] `.htaccess` présent pour les redirections HTTPS
- [ ] Pas de mots de passe en dur dans le code

---

## 🔒 Sécurité

### Données sensibles à nettoyer
- [ ] Aucun mot de passe réel dans `mail_utils.php`
- [ ] Aucune clé API privée dans le code
- [ ] Aucune donnée personnelle dans les fichiers SQL
- [ ] Email admin remplacé par un exemple générique

### Permissions
- [ ] Documenter les permissions requises (755, 644)
- [ ] Expliquer la configuration du dossier logs/

---

## 📝 Documentation

### Vérifications manuelles
- [ ] `README.md` est complet et à jour
- [ ] `INSTALLATION.md` couvre toutes les étapes
- [ ] `INSTALLATION_EN.md` est la traduction exacte
- [ ] `QUICKSTART.md` est clair et concis
- [ ] `CHANGELOG.md` liste toutes les fonctionnalités v2.0
- [ ] Liens Discord et GitHub sont corrects

### Screenshots (optionnel mais recommandé)
- [ ] Page d'accueil
- [ ] Dashboard pilote
- [ ] Panel admin
- [ ] Carte des vols en direct
- [ ] Gestion de flotte

---

## 🗄️ Base de données

### Fichiers SQL
- [ ] `VA_mysql_db_creation.sql` est à jour
- [ ] `create_session_tokens_table.sql` inclus
- [ ] Pas de données de test personnelles
- [ ] Structure compatible MySQL 5.7+

### Données de démonstration
- [ ] Quelques aéroports pré-remplis
- [ ] Types d'avions de base présents
- [ ] Table GRADES pré-remplie
- [ ] Missions exemple (optionnel)

---

## 🧪 Tests

### Tests fonctionnels de base
- [ ] Installation fraîche sur environnement propre testée
- [ ] Création de compte fonctionne
- [ ] Connexion/déconnexion OK
- [ ] Changement de langue fonctionne
- [ ] Pages admin accessibles avec droits
- [ ] Pas d'erreur PHP dans les logs

### Tests de compatibilité
- [ ] Testé sur PHP 7.4
- [ ] Testé sur PHP 8.1+
- [ ] Testé sur MySQL 5.7+
- [ ] Testé sur Apache
- [ ] Testé sur hébergement mutualisé

---

## 📤 Empaquetage

### Structure du ZIP
```
skywings-v2.0.zip
├── skywings/
│   ├── admin/
│   ├── api/
│   ├── assets/
│   ├── css/
│   ├── includes/
│   │   ├── db_connect_exemple.php ✅
│   │   └── mail_utils.php
│   ├── lang/
│   ├── pages/
│   ├── scripts/
│   ├── sql_database/
│   ├── .htaccess
│   ├── index.php
│   ├── LICENSE.txt
│   ├── README.md
│   ├── INSTALLATION.md
│   ├── INSTALLATION_EN.md
│   ├── QUICKSTART.md
│   └── CHANGELOG.md
```

### Nom du fichier
- [ ] Format: `skywings-v2.0.zip` ou `skywings-v2.0.0.zip`
- [ ] Inclure le numéro de version
- [ ] Taille raisonnable (< 100 MB)

---

## 🌐 Publication

### Plateformes
- [ ] GitHub Release créée
- [ ] Tag Git v2.0.0 créé
- [ ] Description de release complète
- [ ] Assets (ZIP) uploadés

### Communication
- [ ] Post sur Discord avec lien de téléchargement
- [ ] Post sur forums de simulation (si applicable)
- [ ] Email aux bêta-testeurs
- [ ] Annonce sur les réseaux sociaux

### Support
- [ ] GitHub Issues activé
- [ ] Discord configuré pour support
- [ ] Email de contact fonctionnel
- [ ] Template de rapport de bug préparé

---

## 📊 Métriques (optionnel)

### Tracking
- [ ] Google Analytics ou équivalent (si souhaité)
- [ ] Statistiques de téléchargement
- [ ] Feedback utilisateur collecté

---

## ⚠️ Points d'attention

### Rappels importants
- [ ] ⚠️ Vérifier que `db_connect_exemple.php` est bien l'exemple (pas le vrai)
- [ ] ⚠️ Aucun mot de passe en clair dans le code
- [ ] ⚠️ Licence MIT bien présente et claire
- [ ] ⚠️ Crédits et remerciements à jour
- [ ] ⚠️ Liens de support fonctionnels

### Legal
- [ ] Licence compatible avec distribution
- [ ] Pas de code propriétaire inclus
- [ ] Assets (images) libres de droits ou créés par vous
- [ ] PHPMailer inclus avec sa licence

---

## 🎉 Distribution finale

### Avant publication
1. [ ] Toute la checklist validée
2. [ ] Tests d'installation frais effectués
3. [ ] Documentation relue
4. [ ] Version stable et testée

### Publication
1. [ ] Créer le tag Git: `git tag -a v2.0.0 -m "Version 2.0.0"`
2. [ ] Pusher le tag: `git push origin v2.0.0`
3. [ ] Créer la release GitHub
4. [ ] Uploader le ZIP
5. [ ] Publier l'annonce

### Après publication
1. [ ] Monitor les premiers retours
2. [ ] Répondre aux questions rapidement
3. [ ] Corriger les bugs critiques en priorité
4. [ ] Planifier la v2.1

---

## 📞 Support Post-Release

### Canaux de support
- **GitHub Issues**: Bugs et features
- **Discord**: Support communauté
- **Email**: Contact direct

### Réponses types préparées
- [ ] "Comment installer ?" → Lien INSTALLATION.md
- [ ] "Erreur de connexion DB" → Vérifier db_connect.php
- [ ] "Emails ne partent pas" → Vérifier mail_utils.php
- [ ] "Comment devenir admin ?" → UPDATE PILOTES SET is_admin=1

---

**✅ Checklist complète = Prêt pour la distribution !**

**Bon courage pour le lancement ! 🚀**
