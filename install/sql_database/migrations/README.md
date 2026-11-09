# Migrations de schéma (bases existantes)

À exécuter **à la main** sur une base de production déjà installée, dans l'ordre.
Les installations neuves n'en ont pas besoin : l'installeur importe directement
`01_Main_Database.sql` + `02_Airports_data.sql` + `03_Pistes_data.sql` (schéma final).

Compatibilité testée : MySQL 8.0.4+ et MariaDB 10.6+ (usage de `JSON_TABLE`).

## Procédure recommandée (phpMyAdmin), pour la migration PISTES

1. **Sauvegarde** : onglet *Exporter* → format SQL → exporter au minimum la table
   `AEROPORTS` (ou toute la base). La garder jusqu'au bout.
2. **Étape 1 non destructive** : onglet *Importer* → `001_pistes_create_populate.sql`
   (3,3 Ko, ~0,5 s). Crée `PISTES` et la peuple en mètres depuis les anciennes colonnes,
   **qui sont conservées** : l'application continue de tourner normalement.
   Vérifier ensuite dans l'onglet SQL :
   ```sql
   SELECT COUNT(*) FROM PISTES;                        -- attendu 28491
   SELECT COUNT(*) FROM PISTES WHERE longueur_m IS NULL; -- attendu 15 (données aberrantes)
   SELECT COUNT(*) FROM AEROPORTS;                     -- inchangé (ex 28967)
   SHOW COLUMNS FROM AEROPORTS LIKE 'Longueur%';       -- colonne encore présente
   SELECT * FROM PISTES WHERE aeroport_ident IN ('AA00','AA06','BGMV','LKVM');
   ```
   Rollback à ce stade : `DROP TABLE PISTES;` → base strictement identique.
3. **Déployer le nouveau code PHP** (pages admin + `api_getAirports.php` lisent désormais
   `PISTES`). Tant que l'étape 2 n'est pas faite, l'ancien code marche encore.
4. **Étape 2 destructive** : importer `002_pistes_drop_colonnes.sql` (0,8 Ko, ~0,2 s) :
   supprime `AEROPORTS.Piste` / `Longueur_de_piste` / `Type_de_piste`.
   ⚠️ Ne la faire qu'après l'étape 3, sinon l'ancien code plante.
   Vérifier : `SHOW COLUMNS FROM AEROPORTS;` (plus les 3 colonnes),
   `SELECT COUNT(*) FROM PISTES;` (toujours 28491).
   Rollback : `003_pistes_rollback.sql` (recrée les 3 colonnes depuis PISTES),
   ou restaurer la sauvegarde de `AEROPORTS`.
5. Tester la page admin des aéroports et `api_getAirports.php`.

**Impact SimAddon** : le client C# consomme les API REST (et peut se synchroniser en
MySQL direct sur `AEROPORTS`). Aucun endpoint utilisé par SimAddon ne dépend des 3
colonnes (`api_getAirportCoords`, `api_getFretByIcao`, `api_getLastAirportUpdate` ne les
lisent pas). `api_getAirports.php` continue d'exposer `Piste` / `Longueur_de_piste` /
`Type_de_piste` reconstruits depuis `PISTES` (longueur en ft), donc son JSON est
identique avant/après le drop, plus un champ `pistes` en mètres. En accès MySQL direct,
un `SELECT *` reste fonctionnel ; seules des requêtes nommant explicitement ces 3
colonnes seraient affectées (aucun cas documenté) — d'où `003_pistes_rollback.sql`.

## Fichiers

- `001_pistes_create_populate.sql` — étape 1/2 (non destructive) : crée `PISTES`, la peuple
  (1 ft = 0,3048 m, arrondi au mètre ; listes `a/b/c` splittées ; `??`/vide → aucune ligne ;
  > 10 000 m → NULL).
- `002_pistes_drop_colonnes.sql` — étape 2/2 (destructive) : drop des 3 anciennes colonnes.
- `003_pistes_rollback.sql` — rollback d'urgence de l'étape 2 (recrée les 3 colonnes depuis PISTES).
- `004_surfaces.sql` — normalisation des surfaces : crée `SURFACES` et `SURFACES_MAP`
  (423 valeurs brutes → code), ajoute `PISTES.surface_code` (FK vers SURFACES) et le remplit.
  Non destructif.
- `005_surfaces_simplify.sql` — simplification drastique : seuls les codes
  ASPH, CONC, GRVL, TURF, SAND, WATER, ICE, SNOW (+ UNK) sont conservés ; tout le reste
  (DIRT, CLAY, CORAL, METAL, WOOD, PSP, ROOF, PAD) passe en UNK dans `SURFACES_MAP` et
  `PISTES.surface_code`, puis les codes retirés sont supprimés de `SURFACES`.
  Le brut reste inchangé dans `PISTES.surface`. Non destructif, ~1 s.
  Vérification : `SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;`
  et `SELECT COUNT(*) FROM SURFACES;` (attendu : 9).
- `006_surfaces_map_clean.sql` — nettoyage de `SURFACES_MAP` : supprime les entrées `UNK`
  (inutiles, le recalcul met UNK par défaut sans correspondance), il reste ~257 vraies
  correspondances. Recalcule `surface_code` (inchangé). Non destructif, ~0,2 s.
  Vérification : `SELECT COUNT(*) FROM SURFACES_MAP;` (attendu ~257).
- `007_surfaces_alias.sql` — **modèle final « une entrée par surface »** : consolide
  `SURFACES_MAP` dans une nouvelle colonne `SURFACES.alias` (variantes séparées par `|`,
  ex. ASPH a pour alias `ASP|ASPHALT|BIT|...`), recalcule `PISTES.surface_code` avec la
  logique normalisée (majuscules + 1er élément du brut, lookup dans code/alias), puis
  supprime `SURFACES_MAP`. Non destructif pour les données.
  Vérifications : `SELECT code, LEFT(alias,80) FROM SURFACES ORDER BY code;` (9 lignes),
  `SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;`
  (inchangé), `SHOW TABLES LIKE 'SURFACES_MAP';` (vide).

> Ordre si tu pars d'une base ancienne : 004 → 005 → 006 → 007 (007 supprime SURFACES_MAP).
> `03_Surfaces_data.sql` (install neuve) contient directement les 9 codes avec leur `alias`.
