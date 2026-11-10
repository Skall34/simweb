-- Migration 012 : PISTES "propre".
--   1) supprime la redondance surface / surface_code (100 % identiques depuis la
--      migration 008) : une seule colonne `surface`, FK vers SURFACES(code)
--   2) deduplique (aeroport_ident, designation) puis pose une contrainte UNIQUE
-- Ordre de deploiement recommande : deployer d'abord le code PHP compatible
--      (api_getAirports.php, admin_aeroport.php, admin_surfaces.php lisent `surface`
--      et exposent `surface_code` en alias), puis executer cette migration.
-- Verifications prealables :
--   SELECT COUNT(*) FROM PISTES WHERE surface IS NOT NULL AND surface <> surface_code;  -- attendu 0
--   SELECT aeroport_ident, designation, COUNT(*) c FROM PISTES GROUP BY 1, 2 HAVING c > 1;  -- 9 doublons connus
--   SELECT COUNT(*) FROM PISTES WHERE surface IS NOT NULL AND surface NOT IN (SELECT code FROM SURFACES);  -- attendu 0
-- Rollback : restaurer la sauvegarde, ou recreer surface_code :
--   ALTER TABLE PISTES ADD COLUMN surface_code varchar(10) NULL AFTER surface;
--   UPDATE PISTES SET surface_code = surface;
--   ALTER TABLE PISTES ADD KEY idx_pistes_surface (surface_code),
--     ADD CONSTRAINT fk_pistes_surface FOREIGN KEY (surface_code) REFERENCES SURFACES(code);

-- 1a) securite : `surface` prend le code canonique quand ils different
UPDATE `PISTES`
SET `surface` = `surface_code`
WHERE `surface_code` IS NOT NULL AND BINARY TRIM(`surface`) <> `surface_code`;

-- 1b) securite FK : toute valeur hors SURFACES passe a 'UNK' (comportement historique du recalc)
UPDATE `PISTES` p
LEFT JOIN `SURFACES` s ON s.`code` = p.`surface`
SET p.`surface` = 'UNK'
WHERE p.`surface` IS NOT NULL AND s.`code` IS NULL;

-- 2) dedup (aeroport_ident, designation) : on garde la piste la plus longue,
--    a egalite l'id le plus petit. Les designation NULL ne sont pas concernees
--    (MySQL autorise plusieurs NULL dans un index UNIQUE).
DELETE p1 FROM `PISTES` p1
JOIN `PISTES` p2
  ON p1.`aeroport_ident` = p2.`aeroport_ident`
 AND p1.`designation` = p2.`designation`
 AND (   COALESCE(p1.`longueur_m`, -1) < COALESCE(p2.`longueur_m`, -1)
      OR (COALESCE(p1.`longueur_m`, -1) = COALESCE(p2.`longueur_m`, -1) AND p1.`id` > p2.`id`));

-- 3) schema final : surface seule, FK, index, unicite
ALTER TABLE `PISTES` DROP FOREIGN KEY `fk_pistes_surface`;
ALTER TABLE `PISTES` DROP INDEX `idx_pistes_surface`;
ALTER TABLE `PISTES` DROP COLUMN `surface_code`;
ALTER TABLE `PISTES`
  MODIFY `surface` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'code canonique (table SURFACES)';
ALTER TABLE `PISTES`
  ADD KEY `idx_pistes_surface` (`surface`),
  ADD UNIQUE KEY `uq_piste_designation` (`aeroport_ident`, `designation`),
  ADD CONSTRAINT `fk_pistes_surface` FOREIGN KEY (`surface`) REFERENCES `SURFACES` (`code`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- Verification :
--   SHOW COLUMNS FROM PISTES;                                  -- plus de surface_code
--   SELECT COUNT(*) FROM PISTES;                               -- attendu 28491 - (nb doublons supprimes)
--   SELECT surface, COUNT(*) FROM PISTES GROUP BY surface ORDER BY 2 DESC;
--   SELECT aeroport_ident, designation, COUNT(*) c FROM PISTES GROUP BY 1,2 HAVING c>1;  -- 0 ligne
--   SHOW INDEX FROM PISTES;
