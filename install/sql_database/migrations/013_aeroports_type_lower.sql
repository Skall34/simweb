-- Migration 013 : normalise la casse de AEROPORTS.type_aeroport
-- (ex: 'MEDIUM_AIRPORT' -> 'medium_airport'). La colonne reste varchar(50) :
-- la liste des types reste alimentee par SELECT DISTINCT type_aeroport (admin).
-- Non destructif (valeurs uniquement en minuscules, collation ci donc meme cle).
-- Install neuve : inutile (02_Airports_data.sql fournit deja des minuscules,
--   hormis 'MEDIUM_AIRPORT' corrige ici pour coherence).

-- BINARY obligatoire : la collation (ci) rendrait la comparaison insensible a la casse
UPDATE `AEROPORTS`
SET `type_aeroport` = LOWER(`type_aeroport`)
WHERE BINARY `type_aeroport` <> LOWER(`type_aeroport`);

-- Verification :
--   SELECT DISTINCT type_aeroport FROM AEROPORTS ORDER BY 1;  -- 7 valeurs, toutes en minuscules
--   SELECT COUNT(*) FROM AEROPORTS;                           -- inchange (28967)
-- Rollback : aucune donnee perdue (LOWER est idempotent), aucun rollback necessaire.
