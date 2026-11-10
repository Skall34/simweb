-- Migration 014 : remplace la table a 1 ligne AEROPORTS_LAST_ADMIN_UPDATE
-- par une cle dans VARIABLES_CONFIG (nom = 'aeroports_last_admin_update').
-- Ordre : deployer D'ABORD le code PHP qui lit/ecrit VARIABLES_CONFIG
--   (api_getLastAirportUpdate.php lit la variable avec repli sur l'ancienne table,
--    admin_aeroport.php ecrit via INSERT ... ON DUPLICATE KEY UPDATE),
--   puis executer cette migration.
-- A executer sur base existante uniquement (install neuve : la cle est cree
--   directement dans 01_Main_Database.sql).

INSERT INTO `VARIABLES_CONFIG` (`nom`, `valeur`)
SELECT 'aeroports_last_admin_update', COALESCE((SELECT `last_update` FROM `AEROPORTS_LAST_ADMIN_UPDATE` LIMIT 1), NOW())
WHERE NOT EXISTS (SELECT 1 FROM `VARIABLES_CONFIG` WHERE `nom` = 'aeroports_last_admin_update');

DROP TABLE IF EXISTS `AEROPORTS_LAST_ADMIN_UPDATE`;

-- Verification :
--   SELECT * FROM VARIABLES_CONFIG WHERE nom = 'aeroports_last_admin_update';
--   SHOW TABLES LIKE 'AEROPORTS_LAST_ADMIN_UPDATE';  -- vide
-- Rollback :
--   CREATE TABLE `AEROPORTS_LAST_ADMIN_UPDATE` (`last_update` datetime NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--   INSERT INTO `AEROPORTS_LAST_ADMIN_UPDATE` SELECT valeur FROM `VARIABLES_CONFIG` WHERE nom = 'aeroports_last_admin_update';
