-- Migration 005 : simplification drastique des surfaces de piste.
-- Codes conservés : ASPH (asphalte), CONC (béton), GRVL (gravier), TURF (herbe),
-- SAND (sable), WATER (eau), ICE (glace), SNOW (neige) + UNK (inconnu).
-- Tout le reste (DIRT, CLAY, CORAL, METAL, WOOD, PSP, ROOF, PAD...) -> UNK.
-- Le brut reste inchangé dans PISTES.surface ; seul surface_code est réaffecté.
-- Non destructif, ~1 s. Install neuve : inutile (03_Surfaces_data.sql est déjà simplifié).

UPDATE `SURFACES_MAP` SET `code` = 'UNK'
WHERE `code` NOT IN ('ASPH','CONC','GRVL','TURF','SAND','WATER','ICE','SNOW','UNK');

UPDATE `PISTES` SET `surface_code` = 'UNK'
WHERE `surface_code` NOT IN ('ASPH','CONC','GRVL','TURF','SAND','WATER','ICE','SNOW','UNK');

DELETE FROM `SURFACES`
WHERE `code` NOT IN ('ASPH','CONC','GRVL','TURF','SAND','WATER','ICE','SNOW','UNK');

-- Vérification :
-- SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;
--   attendu : ASPH ~12600, TURF ~6700, CONC ~3900, GRVL ~2100, UNK ~2400, puis SAND/WATER/ICE/SNOW faibles
-- SELECT COUNT(*) FROM SURFACES;  -- 9
