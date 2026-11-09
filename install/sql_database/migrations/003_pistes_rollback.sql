-- Rollback de la migration 002 : recrée les 3 anciennes colonnes de AEROPORTS
-- et les repeuple depuis PISTES (longueurs reconverties en ft).
-- À n'utiliser qu'en cas de problème constaté après 002 (ex: client SimAddon en
-- accès MySQL direct qui nommerait explicitement ces colonnes).
-- Ne PAS exécuter en temps normal.

ALTER TABLE `AEROPORTS`
  ADD COLUMN `Piste` text COLLATE utf8mb4_general_ci AFTER `elevation_ft`,
  ADD COLUMN `Longueur_de_piste` text COLLATE utf8mb4_general_ci AFTER `Piste`,
  ADD COLUMN `Type_de_piste` text COLLATE utf8mb4_general_ci AFTER `Longueur_de_piste`;

UPDATE `AEROPORTS` a
SET a.`Piste` = (SELECT GROUP_CONCAT(p.designation ORDER BY p.id SEPARATOR ' ')
                 FROM `PISTES` p WHERE p.aeroport_ident = a.ident),
    a.`Longueur_de_piste` = (SELECT GROUP_CONCAT(CAST(ROUND(p.longueur_m / 0.3048, 0) AS UNSIGNED) ORDER BY p.id SEPARATOR '/')
                            FROM `PISTES` p WHERE p.aeroport_ident = a.ident AND p.longueur_m IS NOT NULL),
    a.`Type_de_piste` = (SELECT GROUP_CONCAT(p.surface ORDER BY p.id SEPARATOR '/')
                         FROM `PISTES` p WHERE p.aeroport_ident = a.ident AND p.surface IS NOT NULL)
WHERE EXISTS (SELECT 1 FROM `PISTES` p WHERE p.aeroport_ident = a.ident);

-- Vérification : SHOW COLUMNS FROM AEROPORTS LIKE 'Longueur%'; (colonne de retour)
