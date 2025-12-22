# 👥 Guide Utilisateur - Virtual Airline Management System

**Version :** 2.0  
**Date :** Décembre 2025  
**Langues disponibles :** Français, Anglais, Espagnol  

---

## 📑 Table des Matières

1. [Introduction](#introduction)
2. [Premiers Pas](#premiers-pas)
3. [Interface Pilote](#interface-pilote)
4. [Utilisation SimAddon](#utilisation-simaddon)
5. [Interface Administrateur](#interface-administrateur)
6. [Gestion Financière](#gestion-financière)
7. [FAQ Utilisateur](#faq-utilisateur)

---

## 🌟 Introduction

Bienvenue dans votre compagnie aérienne virtuelle ! Ce système vous permet de :

### Pour les Pilotes ✈️
- Enregistrer vos vols automatiquement via SimAddon (addon MSFS)
- Suivre votre progression (heures, grades, salaires)
- Réserver des avions
- Effectuer des missions spéciales
- Consulter vos statistiques

### Pour les Administrateurs 👔
- Gérer la flotte d'avions
- Gérer les pilotes et leurs grades
- Créer missions et lignes régulières
- Suivre les finances de la compagnie
- Configurer les variables métier

---

## 🚀 Premiers Pas

### Créer un Compte

1. **Accéder au site :**
   ```
   https://votre-compagnie.com
   ```

2. **Cliquer sur "Inscription"** (en haut à droite)

3. **Remplir le formulaire :**

   | Champ | Exemple | Règles |
   |-------|---------|--------|
   | **Callsign** | `SKY0042` | Unique, 3-20 caractères, alphanumérique |
   | **Nom** | `Dupont` | Votre nom de famille |
   | **Prénom** | `Jean` | Votre prénom |
   | **Email** | `jean.dupont@email.com` | Email valide, unique |
   | **Mot de passe** | `MonMotDePasse123!` | 8+ caractères, 1 majuscule, 1 chiffre |
   | **Langue** | `Français` | Interface sera dans cette langue |

4. **Valider :** Cliquez "S'inscrire"

5. **Attendre activation :** Un administrateur doit activer votre compte. Vous recevrez un email de confirmation.

### Première Connexion

1. **Cliquer "Connexion"**

2. **Entrer vos identifiants :**
   - Callsign : `SKY0042`
   - Mot de passe : `MonMotDePasse123!`

3. **Vous êtes connecté !** Le menu principal s'affiche avec :
   - Vols en cours (Live Flights)
   - Vos statistiques personnelles
   - Accès à toutes les fonctionnalités

### Changer la Langue

**En haut à droite :**
- 🇫🇷 Français
- 🇬🇧 English
- 🇪🇸 Español

Cliquez sur le drapeau souhaité. L'interface bascule immédiatement.

### Modifier Votre Profil

**Menu : Mon Compte**

Vous pouvez modifier :
- Nom / Prénom
- Email
- Mot de passe
- Langue préférée

**⚠️ Le callsign ne peut pas être modifié** (c'est votre identifiant unique).

---

## ✈️ Interface Pilote

### Tableau de Bord (Page d'Accueil)

**Informations affichées :**
- 🕒 **Vols en cours** (Live Flights) : Pilotes actuellement en vol
- 📊 **Vos statistiques** :
  - Heures de vol totales
  - Grade actuel
  - Prochain grade (heures nécessaires)
  - Salaire cumulé
- 📰 **Message de bienvenue** (configurable par admin)

### Mes Statistiques

**Menu : Stats**

**Vue d'ensemble :**
```
┌─────────────────────────────────────┐
│  Pilote : SKY0042 (Jean Dupont)     │
│  Grade : First Officer              │
│  Heures : 287.5h                    │
│  Prochain grade : Captain (500h)    │
│  Progression : ████████░░░░ 57%     │
│  Salaire cumulé : 21,562.50 EUR     │
└─────────────────────────────────────┘
```

**Graphiques disponibles :**
- 📈 Évolution heures de vol par mois
- 🗺️ Routes les plus volées
- ✈️ Appareils les plus utilisés
- 🎯 Missions accomplies

### Mes Vols

**Menu : Mes Vols**

**Tableau récapitulatif :**

| Date | Départ | Arrivée | Appareil | Temps Vol | Payload | Mission | Recette | Note |
|------|--------|---------|----------|-----------|---------|---------|---------|------|
| 21/12/25 | LFLL | LFST | F-GNSS (Cessna 172) | 01:27 | 622 kg | Vol libre | -267.85 EUR | 9/10 |
| 20/12/25 | LFPG | LFLL | F-GRHU (Baron 58) | 00:52 | 450 kg | Fret | 10,984.53 EUR | 10/10 |

**Filtres :**
- 📅 Par date (mois, année)
- ✈️ Par appareil
- 🎯 Par mission
- 📍 Par aéroport (départ/arrivée)

**Détails d'un vol :** Cliquez sur une ligne pour voir :
- Trace GPS (si disponible)
- Détails carburant (départ/arrivée)
- Calcul détaillé recette
- Commentaire PIREP

### Réserver un Avion

**Menu : Réserver un Avion**

**Processus :**

1. **Liste avions disponibles :**

   ```
   ┌──────────────────────────────────────────────────┐
   │ F-GNSS - Cessna 172 Skyhawk                      │
   │ Localisation : LFLL (Lyon)                       │
   │ Carburant : 479 L                                │
   │ État : 86% (Bon)                                 │
   │ [Réserver]                                       │
   └──────────────────────────────────────────────────┘
   
   ┌──────────────────────────────────────────────────┐
   │ F-GRHU - Beechcraft Baron 58                     │
   │ Localisation : LFPG (Paris CDG)                  │
   │ Carburant : 412 L                                │
   │ État : 92% (Excellent)                           │
   │ [Réserver]                                       │
   └──────────────────────────────────────────────────┘
   ```

2. **Cliquer "Réserver"**

3. **Confirmation :**
   ```
   ✅ Réservation confirmée !
   Avion : F-GNSS
   Expire le : 23/12/2025 15:30
   Durée : 24 heures
   ```

4. **La réservation apparaît dans SimAddon** automatiquement

**⚠️ Important :**
- Réservation valable **24 heures**
- Vous ne pouvez avoir qu'**1 réservation active** à la fois
- Si vous ne volez pas dans les 24h, la réservation expire automatiquement
- L'avion doit être à votre localisation (ou vous devez vous y rendre)

### Saisie Manuelle de Vol

**Menu : Saisie Manuelle**

**⚠️ Utilisation :** Si vous avez volé sans SimAddon (panne, oubli, etc.)

**Formulaire :**

| Champ | Exemple | Obligatoire |
|-------|---------|-------------|
| Pilote | SKY0042 (auto-rempli) | ✓ |
| Immatriculation | F-GNSS | ✓ |
| Départ (ICAO) | LFLL | ✓ |
| Fuel départ | 750 L | ✓ |
| Date/heure départ | 21/12/2025 08:13 | ✓ |
| Arrivée (ICAO) | LFST | ✓ |
| Fuel arrivée | 479 L | ✓ |
| Date/heure arrivée | 21/12/2025 09:40 | ✓ |
| Payload | 622 kg | Non |
| Note du vol | 9 (1-10) | ✓ |
| Mission | Vol libre | ✓ |
| Commentaire | Vol parfait, temps clair | Non |

**Calculs automatiques :**
- Temps de vol (différence départ/arrivée)
- Consommation carburant
- Distance (via codes ICAO)
- Revenu net du vol

**Validation :** Même contrôles qu'un vol ACARS (doublons, cohérence, etc.)

### Missions Spéciales

**Menu : Missions**

**Types de missions :**

#### 1. Vol Libre
```
┌─────────────────────────────────────┐
│ 🎯 Vol Libre                         │
│ Majoration : x1.0 (normal)          │
│ Description : Vol standard sans     │
│ contrainte particulière.            │
└─────────────────────────────────────┘
```

#### 2. Fret Commercial
```
┌─────────────────────────────────────┐
│ 📦 Fret Commercial                   │
│ Majoration : x1.2 (+20%)            │
│ Description : Transport de fret     │
│ commercial prioritaire.             │
└─────────────────────────────────────┘
```

#### 3. Humanitaire
```
┌─────────────────────────────────────┐
│ 🚑 Vol Humanitaire                   │
│ Majoration : x1.5 (+50%)            │
│ Description : Transport médical ou  │
│ aide d'urgence.                     │
└─────────────────────────────────────┘
```

#### 4. Vol Charter
```
┌─────────────────────────────────────┐
│ 👥 Vol Charter                       │
│ Majoration : x1.3 (+30%)            │
│ Description : Vol privé affrété     │
│ pour un client VIP.                 │
└─────────────────────────────────────┘
```

**💡 Astuce :** Choisissez des missions avec majoration élevée pour maximiser vos revenus !

### Lignes Régulières

**Menu : Lignes Régulières**

**Qu'est-ce que c'est ?**
Routes pré-définies par les administrateurs avec caractéristiques spécifiques.

**Exemple :**
```
┌──────────────────────────────────────────────────┐
│ Ligne : Paris - Nice                             │
│ Route : LFPG → LFMN                              │
│ Type : Domestique                                │
│ Fréquence recommandée : Quotidienne              │
│ Avion suggéré : Turboprop ou Jet                 │
│ Distance : 425 NM                                │
│ [Réserver et Voler]                              │
└──────────────────────────────────────────────────┘
```

**Avantages :**
- Routes optimisées
- Fret garanti aux aéroports
- Bonus de régularité (si vous volez souvent la même ligne)

### Consulter les Aéroports

**Menu : Aéroports**

**Recherche :**
- Par code ICAO (ex: `LFPG`)
- Par nom (ex: `Charles de Gaulle`)
- Par pays

**Détails aéroport :**
```
┌──────────────────────────────────────────────────┐
│ LFPG - Paris Charles de Gaulle                   │
│ Pays : France                                    │
│ Fret disponible : 135,398 kg                     │
│ Dernière mise à jour : 20/12/2025 04:00          │
│                                                  │
│ Avions stationnés :                              │
│  • F-GRHU (Baron 58)                             │
│  • F-ABCD (King Air 350)                         │
└──────────────────────────────────────────────────┘
```

**💡 Astuce :** Choisissez des aéroports avec beaucoup de fret pour maximiser vos gains !

### Suivre les Vols en Cours

**Menu : Live Flights**

**Carte temps réel :**
- 🗺️ Carte interactive (si activée)
- ✈️ Avions en vol actuellement
- 📍 Position, cap, altitude
- ⏱️ Temps écoulé

**Liste vols :**
| Pilote | Avion | Départ | Arrivée | Temps Écoulé | Statut |
|--------|-------|--------|---------|--------------|--------|
| SKY0001 | F-ABCD | LFPG | LFLL | 00:23 | En vol |
| SKY0015 | N12345 | KJFK | KLAX | 02:45 | En vol |

**💡 Astuce :** Vous pouvez suivre vos collègues pilotes en temps réel !

---

## 🎮 Utilisation SimAddon

### Qu'est-ce que SimAddon ?

**SimAddon** est un addon pour Microsoft Flight Simulator qui :
- Enregistre automatiquement vos vols
- Envoie les données à votre compagnie virtuelle
- Gère vos réservations
- Affiche vos missions

### Installation SimAddon

1. **Télécharger :** Depuis le site de votre compagnie ou GitHub
2. **Installer :** Suivre l'assistant d'installation MSFS
3. **Redémarrer MSFS**

### Configuration SimAddon

**Étape 1 : Générer votre Token**

1. Connectez-vous sur le site de la VA
2. Menu **"Mon Compte"**
3. Section **"Token SimAddon"**
4. Cliquez **"Générer un nouveau token"**
5. **Copiez le token** (64 caractères alphanumériques)

   ```
   Exemple : a1b2c3d4e5f6...xyz789
   ```

   **⚠️ IMPORTANT :**
   - Ne partagez JAMAIS votre token !
   - Il permet d'enregistrer des vols en votre nom
   - Si compromis : régénérez-en un nouveau

**Étape 2 : Configurer SimAddon**

1. **Lancer MSFS**
2. **Ouvrir SimAddon** (panneau VFR ou toolbar)
3. **Onglet "Configuration"**
4. Remplir :

   | Champ | Valeur |
   |-------|--------|
   | **URL API** | `https://votre-compagnie.com/api/` |
   | **Token** | (coller le token copié) |
   | **Callsign** | SKY0042 |

5. **Cliquer "Tester connexion"**
6. **Si vert ✓ : OK, sinon vérifier URL/Token**

### Utiliser SimAddon en Vol

**Avant le vol :**

1. **Lancer MSFS** et **charger à un aéroport**
2. **Ouvrir SimAddon**
3. **Vérifier statut :** "✅ Connecté à [VotrVA]"

**Réservation (optionnel) :**

Si vous avez réservé un avion sur le site, SimAddon affiche :
```
┌─────────────────────────────────────┐
│ Réservation active                   │
│ Avion : F-GNSS (Cessna 172)         │
│ Localisation : LFLL                  │
│ Expire : 23/12/2025 15:30           │
└─────────────────────────────────────┘
```

**Sinon :** Vous pouvez voler avec n'importe quel avion (SimAddon détectera automatiquement).

**Démarrage vol :**

1. **Sélectionner mission :**
   ```
   Mission : [Vol libre ▼]
   ```

2. **Cliquer "Démarrer Vol"** ou démarrage automatique quand :
   - Moteur allumé
   - Avion en mouvement

3. **SimAddon affiche :**
   ```
   ┌─────────────────────────────────────┐
   │ 🛫 Vol en cours                      │
   │ Départ : LFLL (Lyon)                │
   │ Destination : LFST (Strasbourg)     │
   │ Temps écoulé : 00:23:45             │
   │ Payload : 622 kg                    │
   │ Carburant : 479 L (départ: 750 L)   │
   │ Note actuelle : 9/10                │
   └─────────────────────────────────────┘
   ```

**Pendant le vol :**

SimAddon enregistre automatiquement :
- ✓ Position GPS (toutes les 30 secondes)
- ✓ Altitude, vitesse, cap
- ✓ Fuel consommé
- ✓ Incidents (crash, surcharge, etc.)

**Note de vol calculée en temps réel :**
- 10/10 : Vol parfait (pas de crash, vitesse correcte, altitude OK)
- 9/10 : Très bon (légers écarts)
- 8/10 : Bon
- ...
- 1/10 : Crash

**Fin de vol :**

Quand vous atterrissez et **arrêtez les moteurs** :

1. **SimAddon détecte la fin**
2. **Popup :**
   ```
   ┌─────────────────────────────────────┐
   │ 🎉 Vol terminé !                     │
   │                                     │
   │ Départ : LFLL 08:13                 │
   │ Arrivée : LFST 09:40                │
   │ Temps : 01:27:00                    │
   │ Fuel : 750 L → 479 L (271 L)        │
   │ Payload : 622 kg                    │
   │ Note : 9/10                         │
   │                                     │
   │ [Envoyer à la VA] [Annuler]         │
   └─────────────────────────────────────┘
   ```

3. **Cliquer "Envoyer à la VA"**

4. **Envoi des données :** SimAddon envoie :
   - Informations vol (départ, arrivée, durée, fuel, payload)
   - Trace GPS
   - Note du vol

5. **Confirmation :**
   ```
   ✅ Vol enregistré avec succès !
   Revenu net : -267.85 EUR
   ID Vol : #38195
   ```

**Consulter le vol :**
- Allez sur le site
- Menu "Mes Vols"
- Le vol apparaît immédiatement !

### Problèmes Courants SimAddon

#### "Impossible de se connecter à la VA"

**Solutions :**
1. Vérifier URL API (doit finir par `/api/`)
2. Vérifier token (64 caractères exacts)
3. Vérifier connexion internet
4. Tester URL dans navigateur : `https://votre-va.com/api/api_check_session.php?token=VOTRE_TOKEN`

#### "Token invalide ou expiré"

**Solution :**
1. Aller sur le site → Mon Compte
2. Générer nouveau token
3. Copier/coller dans SimAddon

#### "Vol non enregistré"

**Vérifications :**
1. Vous étiez bien connecté au début du vol ?
2. Avion actif dans la flotte de la VA ?
3. Consulter logs SimAddon (dossier `SimAddon/logs/`)

---

## 👔 Interface Administrateur

**⚠️ Cette section est réservée aux pilotes avec niveau Admin ou Super Admin.**

### Accéder au Panneau Admin

**Menu : Admin** (visible uniquement si vous êtes admin)

**Sections disponibles :**
- 👥 Gestion des Pilotes
- ✈️ Gestion de la Flotte
- 🛩️ Types d'Appareils
- 🎯 Gestion des Missions
- 📍 Gestion des Aéroports
- 🛣️ Lignes Régulières
- 🎖️ Gestion des Grades
- ⚙️ Variables de Configuration (Super Admin uniquement)

### Gestion des Pilotes

**Menu Admin → Gestion des Pilotes**

**Actions disponibles :**

#### Activer/Désactiver un Pilote

**Liste pilotes :**
| Callsign | Nom | Email | Grade | Heures | Actif | Admin | Actions |
|----------|-----|-------|-------|--------|-------|-------|---------|
| SKY0001 | Jean Dupont | jean@mail.com | Captain | 523h | ✓ | 0 | [Modifier] [Désactiver] |
| SKY0042 | Marie Martin | marie@mail.com | First Officer | 287h | ✓ | 0 | [Modifier] [Désactiver] |
| SKY0999 | Test User | test@mail.com | Cadet | 0h | ✗ | 0 | [Activer] [Supprimer] |

**Activer :** Pilote peut se connecter et voler  
**Désactiver :** Pilote ne peut plus se connecter (suspension)  
**Supprimer :** Supprime définitivement le compte ⚠️

#### Promouvoir en Admin

**Niveaux admin :**
- **0 = Pilote** (aucun accès admin)
- **1 = Admin** (gestion flotte, missions, pilotes)
- **2 = Super Admin** (+ variables config, types appareils)

**Procédure :**
1. Cliquer **[Modifier]** sur le pilote
2. Changer **"Niveau Admin"** : `0` → `1` ou `2`
3. Cliquer **"Enregistrer"**
4. Le pilote voit maintenant le menu "Admin"

#### Modifier Heures/Salaire Manuellement

**Cas d'usage :** Correction d'erreur, bonus exceptionnel, etc.

**Procédure :**
1. Cliquer **[Modifier]**
2. Modifier **"Heures de vol total"** ou **"Salaire cumulé"**
3. **Enregistrer**

**⚠️ Attention :** Modification manuelle uniquement, les vols futurs s'ajouteront normalement.

### Gestion de la Flotte

**Menu Admin → Gestion de la Flotte**

#### Acheter un Avion

**Bouton "Acheter un Avion"**

**Formulaire :**
| Champ | Exemple | Description |
|-------|---------|-------------|
| **Type d'appareil** | Cessna 172 Skyhawk | Liste des types créés |
| **Immatriculation** | F-GSKY | Unique, format ICAO (F-XXXX, N12345, etc.) |
| **Localisation** | LFPG | Code ICAO aéroport où sera l'avion |
| **Prix d'achat** | 150,000 EUR | Prix payé |
| **Assurance mensuelle** | 500 EUR/mois | Prélèvement automatique chaque mois |
| **Mode paiement** | Comptant / Crédit | - |

**Si Crédit :**
| Champ | Exemple |
|-------|---------|
| **Nombre de mensualités** | 24 |
| **Taux d'intérêt** | 3.5% (auto depuis config) |

**Calculs automatiques :**
```
Prix : 150,000 EUR
Crédit : 24 mois à 3.5%
Mensualité : 6,458.33 EUR/mois
Total à payer : 154,999.92 EUR
```

**Validation :**
1. Vérifier fonds disponibles (si comptant)
2. Cliquer **"Confirmer l'achat"**
3. Avion ajouté à la flotte immédiatement
4. Dépense enregistrée dans finances

#### Vendre un Avion

**Liste flotte → [Vendre]**

**Valeur de revente :**
```
Valeur = Prix d'achat × (État actuel / 100) × 0.7

Exemple :
Prix achat : 150,000 EUR
État : 86%
Valeur revente : 150,000 × 0.86 × 0.7 = 90,300 EUR
```

**Confirmation :**
```
┌─────────────────────────────────────┐
│ Vendre F-GSKY ?                      │
│                                     │
│ Prix achat : 150,000 EUR            │
│ État : 86%                          │
│ Valeur revente : 90,300 EUR         │
│                                     │
│ Perte : -59,700 EUR                 │
│                                     │
│ [Confirmer] [Annuler]               │
└─────────────────────────────────────┘
```

**⚠️ Attention :**
- Vente définitive
- Si crédit en cours : solde restant dû déduit de la vente
- Recette créditée dans finances

#### Faire le Plein d'un Avion

**Liste flotte → [Faire le plein]**

**Calcul coût :**
```
Capacité totale : 500 L (depuis type appareil)
Fuel actuel : 150 L
À ajouter : 350 L

Prix litre : 0.88 EUR (depuis config)
Coût total : 350 × 0.88 = 308 EUR
```

**Confirmation :**
```
✅ Plein effectué !
Avion : F-GSKY
Fuel : 150 L → 500 L
Coût : 308 EUR
```

**Dépense enregistrée automatiquement.**

#### Effectuer une Maintenance

**Liste flotte → [Maintenance]**

**Coût maintenance :**
```
Coût = (100 - État actuel) × 500 EUR

Exemple :
État actuel : 65%
Coût = (100 - 65) × 500 = 17,500 EUR
```

**Résultat :**
```
✅ Maintenance effectuée !
Avion : F-GSKY
État : 65% → 100%
Coût : 17,500 EUR
```

**💡 Astuce :** Maintenez vos avions avant qu'ils tombent sous 50% (deviennent inutilisables).

### Types d'Appareils

**Menu Admin → Types d'Appareils**

**⚠️ Réservé aux Super Admins**

#### Créer un Nouveau Type

**Bouton "Ajouter un Type"**

**Formulaire complet :**

| Champ | Exemple | Description |
|-------|---------|-------------|
| **Nom** | Cessna 172 Skyhawk | Nom commercial |
| **Catégorie** | Monomoteur | Monomoteur / Bimoteur / Turboprop / Jet |
| **Capacité fret** | 200 kg | Charge utile maximum |
| **Capacité carburant** | 200 L | Réservoir total |
| **Coût horaire** | 100 EUR/h | Coût d'utilisation par heure de vol |
| **Vitesse croisière** | 120 kt | Vitesse typique |
| **Consommation moyenne** | 30 L/h | Fuel consommé par heure |
| **Image** | (upload) | Photo de l'appareil (optionnel) |

**💡 Impact de la catégorie sur les revenus :**
- **Monomoteur** : 5 EUR/kg/1000NM
- **Bimoteur** : 8 EUR/kg/1000NM
- **Turboprop** : 12 EUR/kg/1000NM
- **Jet** : 20 EUR/kg/1000NM

**Exemple :**
```
Vol avec 622 kg sur 198 NM :
- Monomoteur : 616 EUR
- Bimoteur : 985 EUR
- Turboprop : 1,478 EUR
- Jet : 2,463 EUR
```

#### Modifier un Type Existant

**Liste types → [Modifier]**

**⚠️ Attention :** Modification affecte tous les avions de ce type.

### Gestion des Missions

**Menu Admin → Gestion des Missions**

#### Créer une Mission

**Bouton "Nouvelle Mission"**

**Formulaire :**
| Champ | Exemple | Description |
|-------|---------|-------------|
| **Libellé** | Rapatriement médical | Nom de la mission |
| **Majoration** | 2.0 | Multiplicateur revenu (1.0 = normal, 2.0 = double) |
| **Active** | ✓ | Visible par les pilotes ? |
| **Description** | Transport médical d'urgence | Texte explicatif (optionnel) |

**Exemples de missions :**
- Vol VIP (majoration 1.8)
- Cargo express (majoration 1.5)
- Formation (majoration 0.8)
- Vol de nuit (majoration 1.3)

**💡 Créativité encouragée !**

#### Désactiver une Mission

**Liste missions → [Désactiver]**

**Effet :** Mission n'apparaît plus dans les listes de sélection (SimAddon, saisie manuelle).

**Cas d'usage :**
- Mission temporaire (événement)
- Mission saisonnière
- Test

### Gestion des Aéroports

**Menu Admin → Gestion des Aéroports**

**⚠️ 87 aéroports pré-chargés. Vous pouvez en ajouter plus.**

#### Ajouter un Aéroport

**Bouton "Ajouter Aéroport"**

**Formulaire :**
| Champ | Exemple | Règles |
|-------|---------|--------|
| **Code ICAO** | LFPG | 4 lettres, unique |
| **Nom** | Paris Charles de Gaulle | Nom complet |
| **Pays** | France | - |
| **Latitude** | 49.0097 | Décimal |
| **Longitude** | 2.5479 | Décimal |
| **Fret initial** | 10,000 kg | Fret disponible au départ |

**💡 Trouver coordonnées :**
- https://skyvector.com
- https://ourairports.com

#### Modifier Fret Manuellement

**Liste aéroports → [Modifier]**

**Modifier "Fret disponible" :** Permet ajustement manuel (ex: événement spécial).

**⚠️ Note :** Script automatique `update_fret.php` ajoute fret aléatoire chaque semaine.

### Lignes Régulières

**Menu Admin → Lignes Régulières**

#### Créer une Ligne

**Bouton "Nouvelle Ligne"**

**Formulaire :**
| Champ | Exemple |
|-------|---------|
| **Nom ligne** | Paris - Nice |
| **Départ (ICAO)** | LFPG |
| **Arrivée (ICAO)** | LFMN |
| **Type ligne** | Domestique (Domestique/Internationale/Intercontinentale) |
| **Fréquence recommandée** | Quotidienne |
| **Description** | Ligne côte d'azur, forte demande été |
| **Active** | ✓ |

**Utilité :**
- Guide les pilotes
- Certaines VA donnent bonus sur lignes régulières
- Statistiques par ligne

### Gestion des Grades

**Menu Admin → Gestion des Grades**

**Grades par défaut :**
| Grade | Heures Min | Salaire Horaire | Ordre |
|-------|------------|-----------------|-------|
| Cadet | 0h | 30 EUR/h | 1 |
| Second Officer | 50h | 50 EUR/h | 2 |
| First Officer | 150h | 75 EUR/h | 3 |
| Captain | 500h | 100 EUR/h | 4 |
| Senior Captain | 1500h | 150 EUR/h | 5 |

#### Créer un Grade

**Bouton "Nouveau Grade"**

**Formulaire :**
| Champ | Exemple |
|-------|---------|
| **Nom** | Training Captain |
| **Heures minimum** | 2500 |
| **Salaire horaire** | 200 EUR/h |
| **Ordre** | 6 |

**Ordre :** Détermine la hiérarchie (1 = plus bas, 6 = plus haut).

#### Modifier Salaires

**Liste grades → [Modifier]**

**⚠️ Attention :** Modification affecte :
- Futurs salaires des pilotes de ce grade
- Pas les salaires déjà versés

### Variables de Configuration

**Menu Admin → Variables Config**

**⚠️ Réservé aux Super Admins - Impact direct sur l'économie**

**Variables principales :**

| Variable | Valeur Défaut | Impact |
|----------|---------------|--------|
| `prix_litre_essence` | 0.88 EUR | Coût ravitaillement + calcul coût vol |
| `assurance_base_mois` | 500 EUR | Coût assurance par défaut |
| `salaire_base_heure` | 50 EUR | Base calcul salaires |
| `taux_interet_credit` | 3.5% | Intérêts crédits avions |

**Modifier une variable :**
1. Cliquer **[Modifier]**
2. Changer **"Valeur"**
3. **Enregistrer**

**⚠️ Exemples d'impact :**

**Augmenter prix_litre_essence (0.88 → 1.20) :**
```
Vol consommant 271 L :
Avant : 271 × 0.88 = 238.48 EUR
Après : 271 × 1.20 = 325.20 EUR
Impact : -86.72 EUR sur recette vol
```

**Baisser salaire_base_heure (50 → 40) :**
```
Pilote First Officer (75 EUR/h base)
20 heures de vol dans le mois :
Avant : 75 × 20 = 1,500 EUR
Après : 60 × 20 = 1,200 EUR
Impact : -300 EUR salaire mensuel
```

**💡 Recommandation :** Modifier avec parcimonie, consulter les pilotes avant changements majeurs.

---

## 💰 Gestion Financière

### Tableau de Bord Financier

**Menu : Finances** (visible par tous)

**Vue d'ensemble :**
```
┌─────────────────────────────────────────────────┐
│ 💰 Balance Commerciale                          │
│                                                 │
│ Balance actuelle : -129,521,123.77 EUR         │
│ Dernière mise à jour : 22/12/2025 15:25        │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 📊 Statistiques du Mois                         │
│                                                 │
│ Recettes : 45,230 EUR                           │
│ Dépenses : 67,890 EUR                           │
│ Solde mois : -22,660 EUR                        │
└─────────────────────────────────────────────────┘
```

**Graphiques :**
- 📈 Évolution balance sur 12 mois
- 🥧 Répartition recettes (vols, ventes avions, etc.)
- 🥧 Répartition dépenses (assurances, salaires, fuel, maintenance)

### Détail Recettes

**Onglet "Recettes"**

**Types de recettes :**
- ✈️ **Vols** : Revenus nets des vols (peuvent être négatifs)
- 💸 **Ventes avions** : Revente d'appareils
- 💰 **Autres** : Dons, subventions (ajouts manuels admin)

**Tableau :**
| Date | Type | Montant | Référence | Commentaire |
|------|------|---------|-----------|-------------|
| 22/12/25 15:25 | Vol | -525.89 EUR | Vol #38201 | SKY0014, LFQP→ELLX, F-GNSS |
| 21/12/25 11:12 | Vol | -120.48 EUR | Vol #38197 | SKY0014, LFST→LFQP, F-GNSS |
| 20/12/25 15:46 | Vol | 10,984.53 EUR | Vol #38190 | SKY0014, LFBD→LFMT, F-GRHU |

**Filtres :**
- 📅 Par période
- 🏷️ Par type
- 👤 Par pilote (si recette vol)

### Détail Dépenses

**Onglet "Dépenses"**

**Types de dépenses :**
- ⛽ **Fuel** : Ravitaillements
- 🔧 **Maintenance** : Réparations avions
- 🛡️ **Assurances** : Prélèvements mensuels automatiques
- 💵 **Salaires** : Paiements pilotes mensuels
- 🏦 **Crédits** : Mensualités avions
- ✈️ **Achats avions** : Prix d'achat
- 📋 **Autres** : Dépenses manuelles

**Tableau :**
| Date | Type | Montant | Référence | Commentaire |
|------|------|---------|-----------|-------------|
| 01/12/25 01:00 | Assurance | -500 EUR | Avion #12 | Assurance mensuelle F-GNSS |
| 01/12/25 02:00 | Crédit | -6,458.33 EUR | Avion #15 | Mensualité 5/24 F-ABCD |
| 01/12/25 03:00 | Salaire | -21,562.50 EUR | Pilote #42 | Salaire mensuel SKY0042 |

### Comprendre les Recettes de Vol

**Pourquoi certains vols sont négatifs ?**

**Exemple de calcul détaillé :**
```
Vol SKY0014 : LFLL → LFST (198 NM)
Avion : F-GNSS (Cessna 172 - Monomoteur)
Payload : 622 kg
Temps vol : 01:27:00 (1.45h)
Carburant : 271 L
Note : 9/10
Mission : Vol libre (majoration 1.0)

REVENUS :
  Fret : 622 kg × 5 EUR/kg/1000NM × 198 NM × 1.0 / 1000
       = 616.38 EUR

COÛTS :
  Carburant : 271 L × 0.88 EUR/L
            = 238.48 EUR
  
  Appareil : 100 EUR/h × 1.45h × 0.7 (coef note 9)
           = 1,015 EUR
  
  Total coûts : 1,253.48 EUR

RECETTE NETTE : 616.38 - 1,253.48 = -637.10 EUR

❌ VOL NON RENTABLE
```

**Comment avoir des vols rentables ?**

1. **Vols longs** (> 500 NM) : Amortissement coût horaire
2. **Payload élevé** : Plus de fret = plus de revenus
3. **Note élevée** (9-10) : Coefficient coût faible
4. **Missions à majoration** : Fret×1.5 ou×2.0
5. **Avions adaptés** : Jet pour long courrier, monomoteur pour court
6. **Aéroports avec fret** : Maximiser payload

**Exemple vol rentable :**
```
Vol : LFPG → LFMN (425 NM)
Avion : King Air 350 (Turboprop)
Payload : 1,200 kg
Temps : 02:15:00 (2.25h)
Fuel : 450 L
Note : 10/10
Mission : Fret commercial (×1.2)

REVENUS :
  1,200 kg × 12 EUR/kg × 425 NM × 1.2 / 1000 = 7,344 EUR

COÛTS :
  Fuel : 450 × 0.88 = 396 EUR
  Appareil : 500 EUR/h × 2.25h × 0.5 (note 10) = 562.50 EUR
  Total : 958.50 EUR

RECETTE NETTE : 7,344 - 958.50 = +6,385.50 EUR

✅ VOL TRÈS RENTABLE !
```

### Historique Salaires

**Menu : Finances → Salaires**

**Votre historique :**
| Mois | Heures Volées | Salaire Horaire | Montant Perçu |
|------|---------------|-----------------|---------------|
| Décembre 2025 | 23.5h | 75 EUR/h | 1,762.50 EUR |
| Novembre 2025 | 18.2h | 75 EUR/h | 1,365 EUR |
| Octobre 2025 | 31.8h | 50 EUR/h | 1,590 EUR (promu en cours de mois) |

**Total cumulé : 21,562.50 EUR**

**💡 Astuce :** Plus vous volez, plus vous gagnez. Les promotions augmentent votre salaire horaire !

---

## ❓ FAQ Utilisateur

### Compte & Connexion

**Q : J'ai oublié mon mot de passe, que faire ?**

R : Cliquez "Mot de passe oublié" sur la page de connexion. Entrez votre email, vous recevrez un lien de réinitialisation valable 1 heure.

**Q : Puis-je changer mon callsign ?**

R : Non, le callsign est votre identifiant unique permanent. Contactez un admin si vraiment nécessaire (rare).

**Q : Mon compte est inactif, pourquoi ?**

R : Les nouveaux comptes doivent être activés par un admin. Soyez patient ou contactez-les.

### Vols & SimAddon

**Q : Puis-je voler sans SimAddon ?**

R : Oui, utilisez "Saisie Manuelle" pour enregistrer vos vols. Mais SimAddon est beaucoup plus pratique !

**Q : Mon vol SimAddon n'est pas enregistré**

R : Vérifiez :
1. Token valide (Mon Compte)
2. Connexion internet active
3. URL API correcte (finit par `/api/`)
4. Avion existe dans la flotte
5. Pas de doublon (même vol déjà envoyé)

**Q : Comment améliorer ma note de vol ?**

R : 
- Évitez les crashs (note 1)
- Respectez vitesses (pas de survitesse)
- Atterrissage doux (vertical speed < 500 fpm)
- Respect altitude (pas trop bas)

**Q : Pourquoi mon vol est à -500 EUR ?**

R : Vols courts ou peu chargés coûtent plus qu'ils ne rapportent. Privilégiez :
- Vols longs (> 200 NM)
- Payload maximum
- Missions à majoration

### Réservations

**Q : Je ne peux pas réserver d'avion**

R : Causes possibles :
1. Vous avez déjà une réservation active (1 seule à la fois)
2. Avion déjà réservé par quelqu'un d'autre
3. Avion inactif (maintenance)

**Q : Ma réservation a expiré, pourquoi ?**

R : Réservations valables 24h. Si vous ne volez pas dans ce délai, elle expire automatiquement.

**Q : L'avion réservé n'est pas à mon aéroport**

R : Vous devez vous rendre (en vol) à la localisation de l'avion réservé.

### Grades & Progression

**Q : Quand serai-je promu ?**

R : Les promotions sont automatiques (script mensuel). Si vous atteignez les heures requises en cours de mois, attendez le 1er du mois suivant.

**Q : Puis-je sauter des grades ?**

R : Non, progression linéaire. Ex: Cadet → Second Officer → First Officer → etc.

**Q : Mon salaire n'a pas augmenté après promotion**

R : Le nouveau salaire horaire s'applique aux heures volées après la promotion.

### Finances

**Q : Pourquoi la balance est négative ?**

R : Normal en début de vie d'une VA (achats avions, salaires). L'objectif est de la rendre positive avec le temps.

**Q : Quand suis-je payé ?**

R : Salaires versés automatiquement le 1er de chaque mois (basés sur vos heures du mois précédent).

**Q : Puis-je consulter les finances globales ?**

R : Oui, menu "Finances". Tous les pilotes voient la balance et statistiques.

### Avions

**Q : Qu'est-ce que l'état d'un avion (86%) ?**

R : Usure de l'avion. 100% = neuf, 0% = détruit. < 50% = inutilisable (maintenance obligatoire).

**Q : Comment l'usure diminue ?**

R : Chaque vol dégrade l'avion selon la note :
- Note 10 : -1%
- Note 9 : -3%
- Note 8 : -5%
- ...
- Note 1 (crash) : -50%

**Q : Comment réparer un avion ?**

R : Admin peut faire "Maintenance" (coût = (100 - état) × 500 EUR). Remet à 100%.

### Aéroports & Fret

**Q : Il n'y a pas de fret à l'aéroport**

R : Fret s'épuise quand les pilotes le transportent. Il se régénère :
- Automatiquement chaque semaine (script)
- Manuellement par admin

**Q : Puis-je proposer un nouvel aéroport ?**

R : Oui, contactez un admin avec le code ICAO et justification.

### Technique

**Q : Le site est lent**

R : Causes possibles :
- Serveur surchargé (beaucoup de pilotes connectés)
- Votre connexion internet
- Contactez admin si persistant

**Q : J'ai trouvé un bug**

R : Signalez-le :
1. Via formulaire contact du site
2. Discord de la communauté
3. GitHub Issues (si vous connaissez)

Fournissez : navigateur, date/heure, action effectuée, erreur vue.

**Q : Le site est inaccessible**

R : Vérifiez :
1. Votre connexion internet
2. Statut du serveur (Discord/réseaux sociaux de la VA)
3. Si maintenance planifiée annoncée

### Divers

**Q : Puis-je avoir plusieurs comptes ?**

R : Non, un compte par pilote. Les multi-comptes sont interdits et peuvent mener au bannissement.

**Q : Comment quitter la compagnie ?**

R : Contactez un admin pour désactiver votre compte. Vos données restent (historique vols) mais vous ne pouvez plus vous connecter.

**Q : Puis-je importer mes vols d'une autre VA ?**

R : Généralement non (risque de triche). Contactez admin pour cas exceptionnels (fusion VA, etc.).

---

## 🎓 Conseils pour Bien Débuter

### Nouveau Pilote

1. **Jour 1 : Configuration**
   - Créer compte
   - Générer token SimAddon
   - Configurer SimAddon
   - Tester connexion

2. **Jour 2 : Premier vol**
   - Choisir avion simple (Cessna 172)
   - Vol court (< 100 NM)
   - Mission "Vol libre"
   - Objectif : note 9 ou 10

3. **Semaine 1 : Routine**
   - 5-10 vols courts
   - Découvrir différents aéroports
   - Tester différentes missions
   - Comprendre calculs recettes

4. **Mois 1 : Optimisation**
   - Vols plus longs
   - Maximiser payload
   - Missions à majoration
   - Viser promotion (50h pour Second Officer)

### Pilote Expérimenté

**Objectif : Rentabilité**

1. **Planification :**
   - Routes longues (> 300 NM)
   - Aéroports avec fret élevé
   - Missions fret/humanitaire (×1.2 à ×1.5)

2. **Optimisation :**
   - Payload maximum
   - Note 10 systématique
   - Avions adaptés (Turboprop/Jet pour long courrier)

3. **Régularité :**
   - Lignes régulières
   - Bonus fidélité (certaines VA)

**Objectif : Promotion**

1. **Volume :**
   - Maximiser heures de vol
   - Vols courts mais nombreux
   - Régularité (tous les jours si possible)

2. **Qualité :**
   - Note 9-10 constante
   - Pas de crashs (note 1 = pénalité énorme)

### Devenir Administrateur

**Qualités recherchées :**
- Pilote actif et régulier
- Bon esprit d'équipe
- Connaissances techniques (optionnel mais +)
- Disponibilité pour gérer demandes pilotes

**Processus :**
1. Candidature spontanée ou sur demande admin existant
2. Période d'essai (Admin niveau 1)
3. Promotion Super Admin si réussite

**Responsabilités :**
- Activer nouveaux pilotes
- Gérer flotte (achats, ventes, maintenance)
- Créer missions/lignes
- Répondre questions pilotes
- Surveiller finances

---

## 🆘 Obtenir de l'Aide

### Support Communautaire

**Discord :** https://discord.gg/K52UfAnSdk
- Canal #support : Questions générales
- Canal #simaddon : Problèmes technique addon
- Canal #admin : Demandes admin

### Support Technique

**GitHub Issues :** https://github.com/Skall34/simweb/issues
- Bugs système
- Demandes de fonctionnalités
- Documentation manquante

### Contact Direct

**Formulaire site :** Menu "Contact"
- Questions spécifiques à votre VA
- Problèmes compte
- Suggestions

---

## 📖 Ressources Supplémentaires

### Documentation Complémentaire

- **[Guide Installation](INSTALLATION_GUIDE.md)** : Installer votre propre VA
- **[Documentation Technique](TECHNICAL_DOCUMENTATION.md)** : Architecture système
- **[Documentation SimAddon](../assets/acars/DocumentationUtilisateurSimAddon.pdf)** : Guide complet addon

### Tutoriels Vidéo

*(À créer par la communauté)*

- Configuration SimAddon
- Premier vol enregistré
- Optimiser rentabilité vols
- Panel administrateur

### Communauté

- **Forum** : (si disponible)
- **Discord** : https://discord.gg/K52UfAnSdk
- **Reddit** : r/VirtualAirlines (général VA)

---

## 🎉 Bon Vol !

Vous avez maintenant toutes les clés pour profiter pleinement de votre compagnie aérienne virtuelle.

**N'oubliez pas :**
- 🎯 Amusez-vous avant tout !
- 🤝 Entraidez-vous entre pilotes
- 📊 Suivez votre progression
- ✈️ Volez régulièrement

**Bienvenue à bord et bon vol ! ✈️**

---

*Guide utilisateur créé le 22 décembre 2025*  
*Maintenu par la communauté*  
*Version 2.0*
