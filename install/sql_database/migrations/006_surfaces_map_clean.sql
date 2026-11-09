-- Migration 006 : nettoyage de SURFACES_MAP.
-- Les entrées pointant vers UNK sont inutiles : sans correspondance, le recalcul
-- de PISTES.surface_code met UNK par défaut (COALESCE(..., 'UNK')).
-- On ne garde que les vraies correspondances vers les 8 codes de surface.
-- Non destructif pour les données (surface_code inchangé), ~0,1 s.
-- Install neuve : inutile (03_Surfaces_data.sql est déjà nettoyé).

DELETE FROM `SURFACES_MAP` WHERE `code` = 'UNK';

-- Recalcule pour être sûr (les bruts sans correspondance retombent sur UNK)
UPDATE `PISTES` p LEFT JOIN `SURFACES_MAP` m ON m.brut = p.surface
SET p.surface_code = COALESCE(m.code, IF(p.surface IS NULL, NULL, 'UNK'));

-- Vérifications :
-- SELECT COUNT(*) FROM SURFACES_MAP;                                   -- attendu ~257
-- SELECT surface_code, COUNT(*) FROM PISTES GROUP BY surface_code ORDER BY COUNT(*) DESC;
--   (distribution inchangée : ASPH ~12600, TURF ~6700, CONC ~3900, UNK ~2660, GRVL ~2100, ...)
