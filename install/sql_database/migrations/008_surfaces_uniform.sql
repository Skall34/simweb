-- Migration 008 : uniformise PISTES.surface sur le code canonique (surface_code).
-- Apres migration, la colonne `surface` ne contient plus que les codes de SURFACES
-- (ASPH, CONC, GRVL, TURF, SAND, WATER, ICE, SNOW, UNK) ou NULL.
-- Le formulaire admin propose desormais uniquement cette liste (select alimente par SURFACES).
-- Non destructif pour les pistes elles-memes (designation/longueur_m inchanges).
-- Install neuve : inutile (04_Pistes_data.sql uniformise deja).

-- Brut deja canonique ou reconnu -> on garde le code canonique
UPDATE `PISTES`
SET `surface` = `surface_code`
WHERE `surface_code` IS NOT NULL AND BINARY TRIM(`surface`) <> `surface_code`;

-- Brut inconnu -> UNK
UPDATE `PISTES`
SET `surface` = 'UNK', `surface_code` = 'UNK'
WHERE `surface` IS NOT NULL AND `surface_code` IS NULL;

-- Verification :
-- SELECT DISTINCT `surface` FROM PISTES ORDER BY 1;   -- attendu : 9 codes + NULL
-- SELECT surface, surface_code, COUNT(*) FROM PISTES GROUP BY surface, surface_code ORDER BY 3 DESC LIMIT 12;
