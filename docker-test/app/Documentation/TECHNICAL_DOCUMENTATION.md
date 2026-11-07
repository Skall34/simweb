# 📘 Documentation Technique - Virtual Airline Management System

**Version :** 2.0  
**Date :** Décembre 2025  
**Technologies :** PHP 7.4+, MySQL 5.7+, Apache/Nginx  

---

## 📑 Table des Matières

1. [Architecture Générale](#architecture-générale)
2. [Structure de la Base de Données](#structure-de-la-base-de-données)
3. [API REST](#api-rest)
4. [Système d'Authentification](#système-dauthentification)
5. [Moteur de Calcul Financier](#moteur-de-calcul-financier)
6. [Scripts Automatisés](#scripts-automatisés)
7. [Gestion des Logs](#gestion-des-logs)
8. [Sécurité](#sécurité)
9. [Debugging](#debugging)
10. [Performances](#performances)

---

## 🏗️ Architecture Générale

### Vue d'ensemble

Le système est une application web PHP/MySQL suivant une architecture MVC simplifiée :

```
┌─────────────────────────────────────────────────────────────┐
│                    Client (Navigateur)                       │
│                  + SimAddon (MSFS Addon)                     │
└─────────────────────────────────┬───────────────────────────┘
                                  │
                     ┌────────────┴────────────┐
                     │    HTTP/HTTPS Request   │
                     │                         │
         ┌───────────┴──────────┐  ┌──────────┴──────────┐
         │   Pages Web (PHP)    │  │   API REST (PHP)    │
         │  - Vues utilisateur  │  │  - SimAddon calls   │
         │  - Admin panels      │  │  - JSON responses   │
         └───────────┬──────────┘  └──────────┬──────────┘
                     │                        │
         ┌───────────┴────────────────────────┴──────────┐
         │        Includes (Business Logic)              │
         │  - fonctions_importer_vol.php                │
         │  - fonctions_financieres.php                 │
         │  - calcul_cout.php                           │
         │  - mail_utils.php                            │
         └───────────┬────────────────────────────────────┘
                     │
         ┌───────────┴──────────┐
         │   Database (MySQL)   │
         │  - 22 tables         │
         │  - Transactions      │
         └──────────────────────┘
```

### Structure des Répertoires

```
simweb/
├── admin/                    # Pages d'administration
│   ├── admin_aeroport.php
│   ├── admin_flotte.php
│   ├── admin_missions.php
│   └── ...
├── api/                      # API REST pour SimAddon
│   ├── api_import_vol_direct.php      # Import vol ACARS
│   ├── api_getFlotte.php              # Récupération flotte
│   ├── api_check_reservation.php      # Vérif réservation
│   └── ...                            # 15 endpoints au total
├── assets/
│   ├── acars/               # Documentation SimAddon
│   └── images/              # Logos, images
├── css/
│   └── styles.css           # Feuilles de style globales
├── includes/                # Modules PHP critiques
│   ├── db_connect.php       # Connexion BDD
│   ├── config.php           # Configuration générale
│   ├── require_login.php    # Authentification
│   ├── require_admin.php    # Vérification admin
│   ├── header.php / footer.php
│   ├── menu_logged.php / menu_guest.php
│   ├── fonctions_importer_vol.php    # Logique import vol
│   ├── fonctions_financieres.php     # Gestion finances
│   ├── calcul_cout.php               # Calculs métier
│   ├── mail_utils.php                # Envoi emails
│   ├── log_func.php                  # Logging
│   ├── tokens.php                    # Gestion tokens ACARS
│   └── PHPMailer/                    # Lib email
├── install/                 # Installateur web
│   ├── index.php
│   ├── steps/              # Étapes installation
│   └── sql_database/       # Scripts SQL
├── lang/                    # Traductions (i18n)
│   ├── fr.php              # 944 clés de traduction
│   ├── en.php
│   ├── es.php
│   └── check_keys.php      # Vérif cohérence
├── pages/                   # Pages publiques/pilotes
│   ├── flights.php
│   ├── stats.php
│   ├── mon_compte.php
│   ├── saisie_manuelle.php
│   ├── missions/           # Sous-dossier missions
│   └── doc_scripts/        # Documentation intégrée
├── scripts/                 # Scripts automatisés
│   ├── assurance_mensuelle.php
│   ├── paiement_salaires_pilotes.php
│   ├── update_fret.php
│   ├── expire_reservations.php
│   └── logs/               # Logs des scripts
├── index.php               # Page d'accueil
├── login.php / logout.php
├── live_flights.php
└── lang.php                # Gestionnaire de langue
```

### Flux de Données Critiques

#### 1. Import d'un Vol ACARS
```
SimAddon (MSFS) 
    ↓ POST /api/api_import_vol_direct.php
    ├─ Authentification (token)
    ├─ Validation données (callsign, immat, ICAO, fuel, payload)
    ├─ Détection doublons (fonctions_importer_vol.php)
    ├─ Calcul distance (haversine)
    ├─ Calcul coût vol (calcul_cout.php)
    ├─ Mise à jour fret (départ/arrivée)
    ├─ Mise à jour flotte (fuel, localisation)
    ├─ Enregistrement carnet vol (CARNET_DE_VOL_GENERAL)
    ├─ Enregistrement trace GPS (TRACE_GPS)
    ├─ Mise à jour finances (finances_recettes)
    ├─ Application usure (basée sur note vol)
    ├─ Envoi mail récapitulatif (avec retry SMTP)
    └─ Logs détaillés (scripts/logs/importer_vol_direct.log)
```

#### 2. Réservation d'Avion
```
Pilote → pages/reserver_ligne.php
    ↓
    ├─ Vérification disponibilité (RESERVATIONS)
    ├─ Vérification localisation avion (FLOTTE)
    ├─ Création réservation (expires_at = NOW() + 24h)
    ├─ API accessible via api_check_reservation.php
    └─ Expiration auto via scripts/expire_reservations.php (cron daily)
```

---

## 🗄️ Structure de la Base de Données

### Schéma Global (22 Tables)

```sql
-- Tables principales
PILOTES                    -- Utilisateurs/pilotes
FLOTTE                     -- Avions de la compagnie
FLEET_TYPE                 -- Types d'appareils (configs)
MISSIONS                   -- Missions disponibles
GRADES                     -- Système de grades/promotions
AEROPORTS                  -- Aéroports avec fret dynamique
CARNET_DE_VOL_GENERAL      -- Historique de tous les vols

-- Tables financières
BALANCE_COMMERCIALE        -- Balance globale VA
finances_recettes          -- Recettes détaillées
finances_depenses          -- Dépenses détaillées
SALAIRES                   -- Historique paie pilotes

-- Tables de réservation
RESERVATIONS               -- Réservations d'avions actives
LIGNES_REGULIERES          -- Lignes régulières (routes)
TYPE_LIGNE                 -- Classification lignes

-- Tables techniques
simaddon_tokens            -- Tokens auth SimAddon
TRACE_GPS                  -- Traces GPS des vols
Live_FLIGHTS               -- Vols en cours (temps réel)
VOLS_REJETES               -- Vols rejetés (audit)

-- Tables système
VARIABLES_CONFIG           -- Variables métier configurables
password_resets            -- Reset mot de passe
rate_limits                -- Protection brute-force
AEROPORTS_LAST_ADMIN_UPDATE -- Suivi maj aéroports
```

### Tables Détaillées

#### PILOTES
```sql
CREATE TABLE PILOTES (
  id INT PRIMARY KEY AUTO_INCREMENT,
  callsign VARCHAR(20) UNIQUE NOT NULL,      -- Identifiant unique pilote
  nom VARCHAR(100),
  prenom VARCHAR(100),
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,            -- Hash bcrypt
  grade_id INT,                              -- FK → GRADES
  heures_vol_total DECIMAL(10,2) DEFAULT 0,  -- Cumul heures
  salaire_cumule DECIMAL(15,2) DEFAULT 0,    -- Cumul salaires perçus
  niveau_admin TINYINT DEFAULT 0,            -- 0=pilote, 1=admin, 2=superadmin
  actif TINYINT DEFAULT 1,                   -- Compte activé ?
  date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
  langue VARCHAR(2) DEFAULT 'fr',            -- fr|en|es
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  INDEX idx_callsign (callsign),
  INDEX idx_email (email),
  FOREIGN KEY (grade_id) REFERENCES GRADES(id)
);
```

#### FLOTTE
```sql
CREATE TABLE FLOTTE (
  id INT PRIMARY KEY AUTO_INCREMENT,
  immat VARCHAR(20) UNIQUE NOT NULL,         -- F-ABCD, N12345
  type_id INT NOT NULL,                      -- FK → FLEET_TYPE
  localisation VARCHAR(10),                  -- Code ICAO actuel
  fuel_actuel INT DEFAULT 0,                 -- Litres restants
  etat INT DEFAULT 100,                      -- Usure (100% = neuf)
  actif TINYINT DEFAULT 1,                   -- Avion disponible ?
  prix_achat DECIMAL(15,2),                  -- Prix d'achat initial
  date_achat DATE,
  assurance_mensuelle DECIMAL(10,2),         -- Coût assurance/mois
  credit_mensuel DECIMAL(10,2) DEFAULT 0,    -- Mensualité crédit
  nb_mensualites_restantes INT DEFAULT 0,    -- Crédit en cours
  INDEX idx_immat (immat),
  INDEX idx_localisation (localisation),
  INDEX idx_type (type_id),
  FOREIGN KEY (type_id) REFERENCES FLEET_TYPE(id)
);
```

#### CARNET_DE_VOL_GENERAL
```sql
CREATE TABLE CARNET_DE_VOL_GENERAL (
  id INT PRIMARY KEY AUTO_INCREMENT,
  date_vol DATE NOT NULL,
  pilote_id INT NOT NULL,                    -- FK → PILOTES
  appareil_id INT NOT NULL,                  -- FK → FLOTTE
  depart VARCHAR(10) NOT NULL,               -- Code ICAO
  destination VARCHAR(10) NOT NULL,          -- Code ICAO
  fuel_depart DECIMAL(10,2),                 -- Litres départ
  fuel_arrivee DECIMAL(10,2),                -- Litres arrivée
  payload DECIMAL(10,2),                     -- Kg fret
  heure_depart TIME,                         -- HH:MM:SS
  heure_arrivee TIME,                        -- HH:MM:SS
  temps_vol TIME,                            -- Durée effective
  mission_id INT,                            -- FK → MISSIONS
  pirep_maintenance TEXT,                    -- Commentaire pilote
  note_du_vol INT,                           -- 1-10 (qualité vol)
  cout_vol DECIMAL(15,2),                    -- Revenu net (peut être négatif)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pilote (pilote_id),
  INDEX idx_date (date_vol),
  INDEX idx_appareil (appareil_id),
  FOREIGN KEY (pilote_id) REFERENCES PILOTES(id),
  FOREIGN KEY (appareil_id) REFERENCES FLOTTE(id),
  FOREIGN KEY (mission_id) REFERENCES MISSIONS(id)
);
```

#### finances_recettes / finances_depenses
```sql
CREATE TABLE finances_recettes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  date_operation DATETIME DEFAULT CURRENT_TIMESTAMP,
  type VARCHAR(50),                          -- 'vol', 'vente_avion', etc.
  montant DECIMAL(15,2) NOT NULL,
  reference_id INT,                          -- ID vol, avion, etc.
  reference_type VARCHAR(50),                -- Type référence
  commentaire TEXT,
  INDEX idx_date (date_operation),
  INDEX idx_type (type)
);

-- Idem pour finances_depenses (assurance, salaires, achat avion...)
```

#### simaddon_tokens
```sql
CREATE TABLE simaddon_tokens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  pilote_id INT NOT NULL,                    -- FK → PILOTES
  token VARCHAR(64) UNIQUE NOT NULL,         -- SHA-256 unique
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME,                       -- NULL = jamais expire
  last_used_at DATETIME,                     -- Dernière utilisation
  INDEX idx_token (token),
  INDEX idx_pilote (pilote_id),
  FOREIGN KEY (pilote_id) REFERENCES PILOTES(id) ON DELETE CASCADE
);
```

#### VARIABLES_CONFIG
```sql
CREATE TABLE VARIABLES_CONFIG (
  id INT PRIMARY KEY AUTO_INCREMENT,
  cle VARCHAR(100) UNIQUE NOT NULL,          -- nom_variable
  valeur TEXT,                               -- Valeur (peut être JSON)
  description TEXT,                          -- Documentation
  type VARCHAR(20) DEFAULT 'string',         -- string|int|float|bool
  updated_at TIMESTAMP
);

-- Exemples de variables :
-- prix_litre_essence: 0.88
-- assurance_base_mois: 500
-- salaire_base_heure: 50
-- etc.
```

### Relations Principales

```
PILOTES (1) ──── (N) CARNET_DE_VOL_GENERAL
   │                      │
   │ (1)                  │ (N)
   │                      │
   └──── (1) GRADES       │
                          │ (1)
                          │
                  FLOTTE ─┘
                    │ (1)
                    │
                    └─── (N) FLEET_TYPE
```

---

## 🔌 API REST

### Endpoints Disponibles

Tous les endpoints sont dans le dossier `api/` et retournent du JSON.

#### 1. **api_import_vol_direct.php** (CRITIQUE)
**Méthode :** POST  
**Auth :** Token SimAddon requis  
**Fonction :** Import d'un vol ACARS complet

**Paramètres POST :**
```php
[
  'callsign'         => 'SKY0014',           // REQUIS
  'immatriculation'  => 'F-GNSS',            // REQUIS
  'departure_icao'   => 'LFLL',              // REQUIS
  'departure_fuel'   => '750',               // REQUIS (litres)
  'departure_time'   => '2025-12-21T08:13',  // REQUIS (ISO 8601)
  'arrival_icao'     => 'LFST',              // REQUIS
  'arrival_fuel'     => '479',               // REQUIS (litres)
  'arrival_time'     => '2025-12-21T09:40',  // REQUIS
  'payload'          => '622',               // REQUIS (kg)
  'note_du_vol'      => '9',                 // REQUIS (1-10)
  'mission'          => 'VOLLIBRE',          // REQUIS
  'commentaire'      => 'Vol parfait',       // OPTIONNEL
  'tracegps'         => 'base64_encoded'     // OPTIONNEL (trace GPS)
]
```

**Réponse succès :**
```json
{
  "status": "success",
  "message": "Vol importé avec succès",
  "vol_id": 38195,
  "cout_vol": -267.85
}
```

**Réponse erreur :**
```json
{
  "status": "error",
  "message": "Pilote 'SKY9999' introuvable dans PILOTES."
}
```

**Contrôles effectués :**
1. Token valide et non expiré
2. Pilote existe et actif
3. Avion existe et actif
4. Codes ICAO valides
5. Fuel départ/arrivée/conso > 0
6. Note entre 1 et 10
7. **Détection doublon** (même pilote, route, payload, fuel, mission - NOTE EXCLUE)

**Traitements :**
1. Déduction fret départ (AEROPORTS)
2. Ajout fret arrivée (AEROPORTS)
3. Calcul distance (formule haversine)
4. Calcul coût vol (via `calculerRevenuNetVol()`)
5. Insertion CARNET_DE_VOL_GENERAL
6. Insertion TRACE_GPS (si fournie)
7. Mise à jour FLOTTE (fuel, localisation)
8. Mise à jour finances_recettes
9. Application usure (via `deduireUsure()`)
10. Envoi mail récap (avec retry mécanism 5 tentatives)

#### 2. **api_check_reservation.php**
**Méthode :** POST  
**Auth :** Token SimAddon  
**Fonction :** Vérifie si le pilote a une réservation active

**Paramètres :**
```json
{
  "callsign": "SKY0014"
}
```

**Réponse :**
```json
{
  "reserved": true,
  "immat": "F-GNSS",
  "expires_at": "2025-12-23 15:30:00"
}
```

#### 3. **api_getFlotte.php**
**Méthode :** GET  
**Auth :** Token  
**Fonction :** Liste la flotte disponible

**Réponse :**
```json
{
  "flotte": [
    {
      "id": 12,
      "immat": "F-GNSS",
      "type": "Cessna 172",
      "localisation": "LFLL",
      "fuel_actuel": 479,
      "etat": 86
    }
  ]
}
```

#### 4. **api_getMissions.php**
**Méthode :** GET  
**Auth :** Token  
**Fonction :** Liste missions actives

#### 5. **api_getAirports.php**
**Méthode :** GET  
**Auth :** Token  
**Fonction :** Liste aéroports avec fret

**Paramètres optionnels :**
- `search`: Filtre par nom/ICAO
- `limit`: Nombre max résultats

#### 6. **api_update_status.php**
**Méthode :** POST  
**Auth :** Token  
**Fonction :** MAJ statut vol en cours (temps réel)

#### 7. **api_live_flights.php**
**Méthode :** GET  
**Auth :** Aucune (public)  
**Fonction :** Récupère vols en cours pour affichage

### Authentification API

**Mécanisme :** Token Bearer dans header ou query string

**Génération token :**
```php
// Dans includes/tokens.php
function generateToken($pilote_id) {
    $token = bin2hex(random_bytes(32)); // 64 caractères hex
    // Insertion dans simaddon_tokens
    // Retourne $token
}
```

**Validation token :**
```php
function validateToken($token) {
    // SELECT pilote_id FROM simaddon_tokens 
    // WHERE token = ? AND (expires_at IS NULL OR expires_at > NOW())
    // Mise à jour last_used_at
    // Retourne pilote_id ou false
}
```

**Utilisation :**
```
POST /api/api_import_vol_direct.php
Header: Authorization: Bearer abc123def456...
OU
POST /api/api_import_vol_direct.php?token=abc123def456...
```

---

## 🔐 Système d'Authentification

### Niveaux d'Accès

```php
// Dans PILOTES.niveau_admin
0 = Pilote standard (accès pages publiques uniquement)
1 = Admin (accès panneau admin, gestion flotte/missions/pilotes)
2 = Super Admin (+ accès variables config, types appareils)
```

### Fichiers de Protection

#### require_login.php
```php
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
// Récupération infos pilote depuis BDD
$_SESSION['callsign'], $_SESSION['niveau_admin'], etc.
```

#### require_admin.php
```php
<?php
require_once 'require_login.php';
if ($_SESSION['niveau_admin'] < 1) {
    header('Location: /index.php');
    exit;
}
```

### Flux de Connexion

```
login.php (formulaire)
    ↓ POST
    ├─ Rate limiting (5 tentatives/15min via rate_limits)
    ├─ SELECT * FROM PILOTES WHERE callsign = ? AND actif = 1
    ├─ password_verify($input, $hash_bdd)
    ├─ Si OK: Création session
    │   ├─ $_SESSION['user_id'] = $pilote['id']
    │   ├─ $_SESSION['callsign'] = $pilote['callsign']
    │   ├─ $_SESSION['niveau_admin'] = $pilote['niveau_admin']
    │   └─ Redirection index.php
    └─ Si KO: Message erreur + increment rate_limit
```

### Sécurité Mots de Passe

```php
// Création compte (register.php)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Vérification
if (password_verify($input_password, $stored_hash)) {
    // OK
}
```

### Reset Mot de Passe

**Table :** `password_resets`

```sql
CREATE TABLE password_resets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  token VARCHAR(64) UNIQUE NOT NULL,      -- Unique random token
  expires_at DATETIME NOT NULL,           -- Valide 1h
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Flux :**
1. Pilote demande reset (forgot_password.php)
2. Génération token unique (expires dans 1h)
3. Envoi email avec lien `/reset_password.php?token=...`
4. Validation token + nouveau mot de passe
5. Suppression token de la table

---

## 💰 Moteur de Calcul Financier

### Fonction Principale : calculerRevenuNetVol()

**Fichier :** `includes/calcul_cout.php`

**Signature :**
```php
function calculerRevenuNetVol(
    $payload,              // kg fret
    $temps_vol,            // HH:MM:SS
    $distance,             // NM (nautical miles)
    $majoration_mission,   // Coefficient (1.0 à 2.0)
    $carburant,            // Litres consommés
    $note,                 // Note vol (1-10)
    $cout_horaire,         // EUR/heure (depuis FLEET_TYPE)
    $immat                 // Immatriculation avion
): float
```

**Algorithme Complet :**

```php
// 1. Conversion temps vol en heures décimales
[$h, $m, $s] = explode(':', $temps_vol);
$heures = $h + ($m / 60) + ($s / 3600);  // Ex: 01:27:00 = 1.45h

// 2. Coefficient de note (pénalité si mauvais vol)
$coef_note = [
    1 => 3.0,    // Crash catastrophique
    2 => 2.5,
    3 => 2.0,
    4 => 1.5,
    5 => 1.2,
    6 => 1.0,
    7 => 0.9,
    8 => 0.8,
    9 => 0.7,
    10 => 0.5   // Vol parfait
][$note];

// 3. Prix au kg selon catégorie appareil
$type_appareil = getCategorieAppareil($immat); // 'Monomoteur', 'Bimoteur', 'Turboprop', 'Jet'
$prix_kg_fret = [
    'Monomoteur' => 5,    // EUR/kg/1000NM
    'Bimoteur'   => 8,
    'Turboprop'  => 12,
    'Jet'        => 20
][$type_appareil];

// 4. Bonus distance courte (< 50 NM)
$bonus_distance = ($distance < 50) ? 1.5 : 1.0;

// 5. CALCUL REVENU BRUT
$revenu_brut = ($payload * $prix_kg_fret * $distance * $majoration_mission * $bonus_distance) / 1000;
// Exemple: 622kg * 8EUR/kg * 198NM * 1.0 * 1.0 / 1000 = 985.63 EUR

// 6. CALCUL COUTS
$prix_litre_essence = getVariableConfig('prix_litre_essence'); // 0.88 EUR
$cout_carburant = $carburant * $prix_litre_essence;
// Exemple: 271L * 0.88 = 238.48 EUR

$cout_appareil = $cout_horaire * $heures * $coef_note;
// Exemple: 1000 EUR/h * 1.45h * 0.7 = 1015 EUR

// 7. REVENU NET
$revenu_net = $revenu_brut - ($cout_carburant + $cout_appareil);
// Exemple: 985.63 - (238.48 + 1015) = -267.85 EUR (PERTE)

return round($revenu_net, 2);
```

**Cas d'Usage :**

| Scénario | Résultat |
|----------|----------|
| Vol court (< 50 NM), payload faible, note 10 | Léger profit |
| Vol long (> 500 NM), payload élevé, note 9-10 | Profit important |
| Vol moyen, payload moyen, note < 7 | Perte (coef_note trop élevé) |
| Crash (note 1-3) | Perte importante |

### Usure des Avions

**Fonction :** `deduireUsure($immat, $note, $logFile)`

**Algorithme :**
```php
// Récupération état actuel
$etat_actuel = // SELECT etat FROM FLOTTE WHERE immat = ?

// Calcul usure basée sur note
$usure = match($note) {
    10 => 1,    // Vol parfait : -1%
    9  => 3,    // Bon vol : -3%
    8  => 5,    // Vol correct : -5%
    7  => 7,
    6  => 10,
    5  => 15,
    4  => 20,
    3  => 30,   // Mauvais vol : -30%
    2  => 40,
    1  => 50    // Crash : -50%
};

$nouvel_etat = max(0, $etat_actuel - $usure);

// UPDATE FLOTTE SET etat = ? WHERE immat = ?
```

**Maintenance :**
- Les avions < 50% d'état deviennent inutilisables (actif=0)
- Maintenance manuelle via panel admin
- Script mensuel `scripts/maintenance.php` peut désactiver auto les avions trop usés

---

## ⚙️ Scripts Automatisés

### Vue d'Ensemble

7 scripts dans `scripts/` à exécuter via CRON :

| Script | Fréquence | Fonction | Log |
|--------|-----------|----------|-----|
| `assurance_mensuelle.php` | 1/mois (1er à 1h) | Facture assurance tous avions | `logs/assurance.log` |
| `credit_mensualite.php` | 1/mois (1er à 2h) | Prélève mensualités crédits | `logs/credit.log` |
| `paiement_salaires_pilotes.php` | 1/mois (1er à 3h) | Paie salaires pilotes | `logs/salaires.log` |
| `promotion_grades_pilotes.php` | 1/mois (1er à 4h) | Promeut pilotes si heures suffisantes | `logs/promotions.log` |
| `maintenance.php` | 1/mois (1er à 5h) | Désactive avions trop usés | `logs/maintenance.log` |
| `update_fret.php` | 1/semaine (vendredi 4h) | Ajoute fret aléatoire aéroports | `logs/update_fret.log` |
| `expire_reservations.php` | 1/jour (2h) | Annule réservations expirées | `logs/expire_reservations.log` |

### Détail des Scripts

#### 1. assurance_mensuelle.php

**Fonction :** Prélève l'assurance mensuelle de tous les avions actifs

**Algorithme :**
```php
// Pour chaque avion actif
$avions = SELECT * FROM FLOTTE WHERE actif = 1;

foreach ($avions as $avion) {
    $montant = $avion['assurance_mensuelle'];
    
    // Insertion dépense
    INSERT INTO finances_depenses (
        type = 'assurance',
        montant = $montant,
        reference_id = $avion['id'],
        commentaire = 'Assurance mensuelle ' . $avion['immat']
    );
    
    // MAJ balance
    mettreAJourBalance(-$montant);
}

// Envoi mail récap admin
```

**CRON :**
```bash
0 1 1 * * php /var/www/scripts/assurance_mensuelle.php
```

#### 2. paiement_salaires_pilotes.php

**Fonction :** Calcule et paie les salaires mensuels des pilotes

**Algorithme :**
```php
// Pour chaque pilote actif
$pilotes = SELECT * FROM PILOTES WHERE actif = 1;

foreach ($pilotes as $pilote) {
    // 1. Calcul heures du mois dernier
    $heures_mois = SELECT SUM(TIME_TO_SEC(temps_vol))/3600 
                   FROM CARNET_DE_VOL_GENERAL 
                   WHERE pilote_id = ? 
                   AND MONTH(date_vol) = MONTH(NOW() - INTERVAL 1 MONTH);
    
    // 2. Récupération salaire horaire du grade
    $salaire_horaire = SELECT salaire_horaire FROM GRADES WHERE id = $pilote['grade_id'];
    
    // 3. Calcul salaire total
    $salaire_total = $heures_mois * $salaire_horaire;
    
    // 4. Enregistrement
    INSERT INTO SALAIRES (pilote_id, montant, mois, annee, heures_vol);
    
    // 5. MAJ cumul pilote
    UPDATE PILOTES SET salaire_cumule = salaire_cumule + $salaire_total WHERE id = ?;
    
    // 6. Enregistrement dépense
    INSERT INTO finances_depenses (type='salaire', montant=$salaire_total);
    
    // 7. Envoi mail au pilote avec détail
    sendMail($pilote['email'], "Salaire mensuel", "Vous avez perçu $salaire_total EUR...");
}

// Mail récap admin avec total
```

#### 3. update_fret.php

**Fonction :** Ajoute fret aléatoire aux aéroports (simulation économie)

**Algorithme :**
```php
$aeroports = SELECT * FROM AEROPORTS;

foreach ($aeroports as $aeroport) {
    // Fret aléatoire entre 500 et 5000 kg
    $fret_ajoute = rand(500, 5000);
    
    UPDATE AEROPORTS 
    SET fret = fret + $fret_ajoute,
        last_update = NOW()
    WHERE ident = $aeroport['ident'];
}

// Log détaillé du nombre d'aéroports mis à jour
```

#### 4. expire_reservations.php

**Fonction :** Supprime les réservations expirées (> 24h)

**Algorithme :**
```php
// Réservations expirées
$expired = SELECT * FROM RESERVATIONS WHERE expires_at < NOW();

foreach ($expired as $reservation) {
    logMsg("Expiration réservation ID " . $reservation['id'] . 
           " pour " . $reservation['callsign']);
    
    DELETE FROM RESERVATIONS WHERE id = $reservation['id'];
    
    // Optionnel: Mail au pilote
    sendMail($email, "Réservation expirée", "Votre réservation de ...");
}
```

### Configuration CRON

**Exemple complet (Linux) :**
```bash
# Éditer crontab
crontab -e

# Ajouter :
# Scripts mensuels (1er du mois)
0 1 1 * * /usr/bin/php /var/www/html/scripts/assurance_mensuelle.php
0 2 1 * * /usr/bin/php /var/www/html/scripts/credit_mensualite.php
0 3 1 * * /usr/bin/php /var/www/html/scripts/paiement_salaires_pilotes.php
0 4 1 * * /usr/bin/php /var/www/html/scripts/promotion_grades_pilotes.php
0 5 1 * * /usr/bin/php /var/www/html/scripts/maintenance.php

# Script hebdomadaire (vendredi 4h)
0 4 * * 5 /usr/bin/php /var/www/html/scripts/update_fret.php

# Script quotidien (2h)
0 2 * * * /usr/bin/php /var/www/html/scripts/expire_reservations.php
```

**Windows Task Scheduler :**
- Créer tâche planifiée
- Programme : `C:\php\php.exe`
- Arguments : `C:\wamp64\www\scripts\assurance_mensuelle.php`
- Déclencheur : Mensuel, 1er du mois, 01:00

---

## 📝 Gestion des Logs

### Fichiers de Logs

**Emplacement :** `scripts/logs/`

| Fichier | Usage | Rotation |
|---------|-------|----------|
| `importer_vol_direct.log` | Import vols ACARS (API) | Critique, rotate mensuel |
| `importer_vol_manual.log` | Import vols manuels (page) | Rotate mensuel |
| `assurance.log` | Script assurance | Rotate annuel |
| `salaires.log` | Script salaires | Rotate annuel |
| `update_fret.log` | Script fret | Rotate mensuel |
| `expire_reservations.log` | Script réservations | Rotate mensuel |

### Fonction logMsg()

**Fichier :** `includes/log_func.php`

```php
function logMsg($message, $logFile = null) {
    if ($logFile === null) {
        $logFile = __DIR__ . '/../scripts/logs/general.log';
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message\n";
    
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
```

**Usage :**
```php
logMsg("[api_import_vol_direct] ✅ Vol traité avec succès (callsign: SKY0014)", $logFile);
```

### Format des Logs

**Standard :**
```
2025-12-21 09:54:05 [module] Message détaillé
2025-12-21 09:54:05 [api_import_vol_direct] ✅ Début traitement vol (callsign: SKY0014)
2025-12-21 09:54:05 [deduireFretDepart] Déduction fret départ : ICAO=LFLL, Demande=622
2025-12-21 09:54:05 [calculerRevenuNetVol] revenu_net = 985.63 - (238.48 + 1015) = -267.85
```

**Retry SMTP (important) :**
```
2025-12-21 09:54:05 RETRY: Tentative 1/5 pour envoi mail...
2025-12-21 09:54:08 RETRY: Tentative 2/5 après délai de 3s...
2025-12-21 09:54:11 RETRY: SUCCESS après 2 tentative(s)
OU
2025-12-21 09:54:30 RETRY: FATAL - Échec définitif après 5 tentatives
```

### Rotation des Logs

**Script :** `scripts/rotate_logs.php`

```php
// Archiver logs > 30 jours
$logs = glob(__DIR__ . '/logs/*.log');
foreach ($logs as $log) {
    if (filemtime($log) < time() - 30*24*3600) {
        $archive = str_replace('.log', '_' . date('Y-m') . '.log.gz', $log);
        // Compression gzip
        system("gzip -c $log > $archive");
        unlink($log);
    }
}
```

---

## 🔒 Sécurité

### Protection Injection SQL

**Utilisation exclusive de requêtes préparées (PDO) :**
```php
// ✅ BON
$stmt = $pdo->prepare("SELECT * FROM PILOTES WHERE callsign = ?");
$stmt->execute([$callsign]);

// ❌ MAUVAIS (jamais utilisé dans le code)
$query = "SELECT * FROM PILOTES WHERE callsign = '$callsign'";
```

### Protection XSS

**Échappement systématique en sortie :**
```php
// Dans les vues
<?= htmlspecialchars($pilote['nom'], ENT_QUOTES, 'UTF-8') ?>
```

### Protection CSRF

**Tokens de session (à implémenter si pas déjà fait) :**
```php
// Génération
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validation
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token invalide');
}
```

### Rate Limiting

**Table :** `rate_limits`

```php
function checkRateLimit($identifier, $max_attempts = 5, $window_minutes = 15) {
    // SELECT attempts FROM rate_limits WHERE identifier = ? AND created_at > NOW() - INTERVAL ? MINUTE
    // Si > max_attempts : bloquer
    // Sinon : incrementer
}
```

**Usage :** Login (5 tentatives/15 min), API (100 req/min)

### Permissions Fichiers

```bash
# Recommandations
chmod 755 /var/www/html/                 # Répertoires
chmod 644 /var/www/html/*.php            # Fichiers PHP
chmod 750 /var/www/html/scripts/         # Scripts
chmod 770 /var/www/html/scripts/logs/    # Logs (écriture)
chown www-data:www-data -R /var/www/html/
```

### HTTPS Obligatoire

**Configuration Apache :**
```apache
<VirtualHost *:80>
    ServerName yourva.com
    Redirect permanent / https://yourva.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourva.com
    DocumentRoot /var/www/html
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/yourva.crt
    SSLCertificateKeyFile /etc/ssl/private/yourva.key
</VirtualHost>
```

### Variables Sensibles

**Fichiers à protéger (jamais commit Git) :**
- `includes/db_connect.php` (credentials BDD)
- `includes/mail_utils.php` (SMTP password)
- `includes/config.php` (secrets)

**.gitignore :**
```
includes/db_connect.php
includes/config.php
scripts/logs/*.log
```

---

## 🐛 Debugging

### Mode Debug

**Activer dans `includes/config.php` :**
```php
define('DEBUG_MODE', true);  // false en production
define('DISPLAY_ERRORS', DEBUG_MODE);
ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(DEBUG_MODE ? E_ALL : E_ERROR);
```

### Erreurs Courantes

#### 1. "SMTP Error: data not accepted"
**Cause :** Formatage nombre avec espace (`number_format($val, 2, ',', ' ')`)  
**Solution :** Remplacer par `number_format($val, 2, ',', '')`

#### 2. "Vol doublon détecté" (faux positif)
**Cause :** Fonction `detecterDoublonVol()` incluait `note_du_vol`  
**Solution :** Retirer note des critères (déjà corrigé)

#### 3. "Call to undefined function calculerRevenuNetVol()"
**Cause :** `includes/calcul_cout.php` non inclus  
**Solution :** Ajouter `require_once __DIR__ . '/../includes/calcul_cout.php';`

#### 4. "Database connection failed"
**Cause :** Credentials incorrects dans `db_connect.php`  
**Solution :** Vérifier host, user, password, database name

### Outils de Debug

**Logs détaillés :**
```php
logMsg("[DEBUG] Variable $x = " . print_r($x, true), $logFile);
```

**Trace SQL :**
```php
$stmt->debugDumpParams();  // Affiche requête préparée
```

**Profiling :**
```php
$start = microtime(true);
// ... code ...
$duration = microtime(true) - $start;
logMsg("[PERF] Opération terminée en " . round($duration, 3) . "s", $logFile);
```

---

## ⚡ Performances

### Optimisations Base de Données

**Index critiques :**
```sql
-- PILOTES
CREATE INDEX idx_callsign ON PILOTES(callsign);
CREATE INDEX idx_email ON PILOTES(email);

-- CARNET_DE_VOL_GENERAL
CREATE INDEX idx_pilote ON CARNET_DE_VOL_GENERAL(pilote_id);
CREATE INDEX idx_date ON CARNET_DE_VOL_GENERAL(date_vol);
CREATE INDEX idx_appareil ON CARNET_DE_VOL_GENERAL(appareil_id);

-- FLOTTE
CREATE INDEX idx_immat ON FLOTTE(immat);
CREATE INDEX idx_localisation ON FLOTTE(localisation);

-- simaddon_tokens
CREATE INDEX idx_token ON simaddon_tokens(token);
```

**Requêtes optimisées :**
```php
// ✅ BON - Sélection colonnes nécessaires uniquement
SELECT id, callsign, email FROM PILOTES WHERE actif = 1;

// ❌ MAUVAIS - SELECT *
SELECT * FROM PILOTES;
```

### Cache PHP (opcache)

**Configuration recommandée (php.ini) :**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production uniquement
opcache.revalidate_freq=0
```

### Pagination

**Exemple (pages/flights.php) :**
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT * FROM CARNET_DE_VOL_GENERAL 
    ORDER BY date_vol DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
```

### Compression GZIP

**Apache (.htaccess) :**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/json
</IfModule>
```

---

## 📞 Support & Contributions

Pour toute question technique :
- **GitHub Issues :** https://github.com/Skall34/simweb/issues
- **Discord :** https://discord.gg/K52UfAnSdk

---

**Document créé le 22 décembre 2025**  
**Maintenu par la communauté**
