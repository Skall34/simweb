# Checklist de livraison PROD — Vol de contrôle post-maintenance + Missions filtrées par type d'avion

Date : 2026-11-08
Branche de livraison : `livraison/vol-controle-missions-types` (à merger dans `main` au déploiement)

## Contexte

Deux évolutions, testées en docker local (`docker-test/`, non commité) :

1. **Vol de contrôle post-maintenance** : après une maintenance, l'avion a `FLOTTE.controle_a_faire = 1`
   et n'est réutilisable qu'avec la mission « Mission De Contrôle » (`MISSIONS.est_controle = 1`),
   qui exige une limite de décrochage (`stall_ok`) et, pour un bimoteur, un arrêt moteur (`engine_cut_ok`).
   Le pilote gagne une prime de risque (`VARIABLES_CONFIG.prime_controle_vol`) à la paie mensuelle.
2. **Missions filtrées par type d'avion** : `MISSIONS.types_autorises` (CSV, `*` = tous) ;
   `api_getMissions.php?type=...` filtre ; rejet serveur si mission incompatible avec le type de l'avion.

**Rétrocompatibilité confirmée** : un ancien SimAddon contre le nouveau serveur (et inversement)
continue de fonctionner normalement ; il ne bénéficie juste pas des évolutions.
Seul point de vigilance : un avion bloqué (`controle_a_faire=1`) ne peut être débloqué que par un
vol de contrôle (nouveau SimAddon) ou manuellement par l'admin (`admin_flotte.php` → « ✅ Contrôle effectué »).

## 0. Avant (J-1)

- [ ] Backup BDD OVH : `mysqldump -h <host> -u <user> -p VA_Database > backup_VA_Database_YYYYMMDD.sql`
- [ ] Backup des fichiers du site (ou tag git de l'état courant de `main`)
- [ ] Noter la version du SimAddon distribué (`assets/acars/simaddon_setup.zip`)

## 1. Base de données (fenêtre de maintenance, personne ne vole)

- [ ] Appliquer `install/sql_database/migrations_archive/add_vol_controle.sql`
- [ ] Appliquer `install/sql_database/migrations_archive/add_missions_types_autorises.sql`
- [ ] Vérifier :
  - `DESCRIBE FLOTTE controle_a_faire;`
  - `DESCRIBE MISSIONS est_controle; DESCRIBE MISSIONS types_autorises;`
  - `SELECT libelle, est_controle FROM MISSIONS WHERE est_controle = 1;` → doit renvoyer « Mission De Contrôle »
  - `SELECT nom, valeur FROM VARIABLES_CONFIG WHERE nom = 'prime_controle_vol';`

## 2. Déployer les fichiers simweb (contenu de la branche de livraison)

- [ ] `config.ini` prod **inchangé** (ne JAMAIS écraser avec celui du docker de test)
- [ ] Les crons `scripts/maintenance.php` et `scripts/paiement_salaires_pilotes.php` seront pris en compte au prochain run

## 3. Configuration post-déploiement (UI admin)

- [ ] `admin_variables.php` : régler `prime_controle_vol` (défaut 500 €/vol de contrôle)
- [ ] `admin_missions.php` :
  - vérifier que « Mission De Contrôle » est cochée « mission de contrôle »
  - régler les `types_autorises` de chaque mission (ex. Op Liner → Liner seul). Défaut `*` = aucun changement
- [ ] `admin_flotte.php` : repérer les avions avec le badge « Contrôle à faire » et les traiter
      (vol de contrôle par un pilote équipé du nouvel addon, ou déblocage manuel « ✅ Contrôle effectué »)

## 4. SimAddon

- [ ] Builder la solution en Release
- [ ] Mettre à jour `assets/acars/simaddon_setup.zip` sur le serveur (fichier servi par `lien_acars` ;
      les `*.zip` sont gitignorés → à pousser manuellement)
- [ ] Annoncer aux pilotes : nouvelle version recommandée, l'ancienne continue de fonctionner

## 5. Smoke tests en prod (après déploiement)

- [ ] `GET /api/api_getMissions.php` → toutes les missions ; `?type=Liner` → filtré
- [ ] `GET /api/api_getFlotte.php` → champ `controle_a_faire` présent
- [ ] Login + `pages/saisie_manuelle.php` + `pages/fleet.php` (badge) + `admin_missions.php` (cases) OK
- [ ] Un vol test via saisie manuelle (mission `*`) accepté
- [ ] Laisser tourner un cycle de `maintenance.php`, vérifier `MAINTENANCES_LOG` et `FLOTTE.controle_a_faire`

## 6. Rollback si besoin

- [ ] Restaurer les fichiers de l'étape 0. Les nouvelles colonnes BDD ne gênent pas l'ancien code :
      ne pas les supprimer, restaurer seulement les fichiers.

## Procédure git (règles de l'art)

- `main` = prod. Ne jamais committer directement dessus.
- Branche de livraison : `livraison/vol-controle-missions-types` (commits faits, testés en docker).
- Au déploiement : merger la branche dans `main` (merge commit ou fast-forward), puis pousser `main`.
- Après déploiement réussi : taguer `main` (ex. `v2.x-vol-controle`) pour pouvoir revenir en arrière.
