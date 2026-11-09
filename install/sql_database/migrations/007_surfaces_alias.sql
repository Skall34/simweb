-- Migration 007 : SURFACES_MAP fusionnee dans SURFACES.alias (une seule entree par surface).
-- - SURFACES gagne une colonne `alias` (valeurs brutes reconnues, separees par '|')
-- - la map est consolidee dans alias, PISTES.surface_code est recalcule avec la
--   nouvelle logique (normalisation du brut : majuscules + premier element avant
--   espace/tiret/slash, lookup dans code/alias), puis SURFACES_MAP est supprimee.
-- Le brut reste inchange dans PISTES.surface. Non destructif pour les donnees.
-- Install neuve : inutile (01 + 03_Surfaces_data.sql sont deja en modele alias).

SET SESSION group_concat_max_len = 1000000;

ALTER TABLE `SURFACES`
  ADD COLUMN `alias` text COLLATE utf8mb4_general_ci COMMENT 'valeurs brutes reconnues, separees par |' AFTER `libelle`;

UPDATE `SURFACES` s
SET s.`alias` = COALESCE(
  (SELECT GROUP_CONCAT(m.brut ORDER BY m.brut SEPARATOR '|') FROM `SURFACES_MAP` m WHERE m.code = s.code),
  s.`code`
);

-- Recalcule surface_code : normalise le brut (majuscules, 1er element avant ' ', '-', '/')
-- puis cherche dans SURFACES (code ou alias). Sans correspondance -> UNK.
UPDATE `PISTES` p
LEFT JOIN `SURFACES` s
  ON s.`code` = SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(UPPER(TRIM(p.`surface`)), ' ', 1), '-', 1), '/', 1)
  OR FIND_IN_SET(UPPER(TRIM(p.`surface`)), REPLACE(s.`alias`, '|', ',')) > 0
  OR FIND_IN_SET(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(UPPER(TRIM(p.`surface`)), ' ', 1), '-', 1), '/', 1), REPLACE(s.`alias`, '|', ',')) > 0
SET p.`surface_code` = COALESCE(s.`code`, IF(p.`surface` IS NULL, NULL, 'UNK'));

DROP TABLE `SURFACES_MAP`;

-- Verifications :
-- SELECT code, LEFT(alias, 80) FROM SURFACES ORDER BY code;       -- 9 lignes, alias remplis
-- SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;
--   (identique qu'avant : ASPH ~12600, TURF ~6700, CONC ~3900, UNK ~2660, GRVL ~2100, ...)
-- SHOW TABLES LIKE 'SURFACES_MAP';                                  -- vide (table supprimee)
