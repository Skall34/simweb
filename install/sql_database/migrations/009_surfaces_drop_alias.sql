-- Migration 009 : suppression de la colonne SURFACES.alias (devenue inutile).
-- Depuis la migration 008, PISTES.surface ne contient plus que les codes canoniques :
-- le recalc de surface_code se fait par simple lookup du code dans SURFACES.
-- A jouer APRES 008 et APRES avoir déployé le code PHP sans référence à `alias`
-- (l'ancien recalc faisait FIND_IN_SET sur alias et planterait sinon).
-- Non destructif pour les pistes. ~0,1 s. Install neuve : inutile (01/03 sans alias).

ALTER TABLE `SURFACES` DROP COLUMN `alias`;

-- Recalcule surface_code par simple correspondance de code (fiabilisation)
UPDATE `PISTES` p
LEFT JOIN `SURFACES` s ON s.`code` = p.`surface`
SET p.`surface_code` = COALESCE(s.`code`, IF(p.`surface` IS NULL, NULL, 'UNK'));

-- Vérifications :
-- SHOW COLUMNS FROM SURFACES;                                          -- plus de colonne alias
-- SELECT DISTINCT surface FROM PISTES ORDER BY 1;                       -- 9 codes + NULL
-- SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;
--   (ASPH ~12600, TURF ~6700, CONC ~3900, UNK ~2660, GRVL ~2140, ...)
