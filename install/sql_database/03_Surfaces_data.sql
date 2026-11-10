-- Surfaces de piste : une seule entree par code canonique (SURFACES).
-- PISTES.surface ne contient que ces codes (FK vers SURFACES.code) ; valeur inconnue -> UNK (voir 04).
-- Genere depuis les donnees OurAirports.

INSERT INTO `SURFACES` (`code`, `libelle`) VALUES
('ASPH', 'Asphalte'),
('CONC', 'BÃ©ton'),
('GRVL', 'Gravier'),
('ICE', 'Glace'),
('SAND', 'Sable'),
('SNOW', 'Neige'),
('TURF', 'Herbe'),
('UNK', 'Inconnu'),
('WATER', 'Eau');
