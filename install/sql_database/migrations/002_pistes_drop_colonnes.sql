-- Migration 002 (étape 2/2, DESTRUCTIVE) : suppression des anciennes colonnes texte de AEROPORTS.
-- À N'EXÉCUTER QU'APRÈS avoir déployé le nouveau code PHP (admin_aeroport.php, api_getAirports.php),
-- sinon l'ancienne application fera des requêtes sur des colonnes qui n'existent plus.
-- Prérequis : 001_pistes_create_populate.sql exécuté et vérifié (PISTES peuplée).

ALTER TABLE `AEROPORTS`
  DROP COLUMN `Piste`,
  DROP COLUMN `Longueur_de_piste`,
  DROP COLUMN `Type_de_piste`;

-- Vérifications après exécution :
-- SHOW COLUMNS FROM AEROPORTS;   -- plus de Piste / Longueur_de_piste / Type_de_piste
-- SELECT COUNT(*) FROM PISTES;   -- toujours 28491
-- SELECT COUNT(*) FROM AEROPORTS; -- inchangé
-- Rollback : restaurer la sauvegarde de la table AEROPORTS faite avant la migration 001.
