-- Migration 001 (étape 1/2, NON destructive) : création de la table PISTES et peuplement
-- depuis AEROPORTS.Piste / Longueur_de_piste (ft) / Type_de_piste.
-- Les 3 anciennes colonnes sont CONSERVÉES : l'application continue de fonctionner normalement.
-- L'étape 2 (002_pistes_drop_colonnes.sql) ne se fait qu'après déploiement du nouveau code PHP.

-- 1) Création de la table
CREATE TABLE IF NOT EXISTS `PISTES` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `aeroport_ident` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `designation` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ex: 6-24, 7W-25W, H1',
  `longueur_m` int unsigned DEFAULT NULL COMMENT 'longueur en mètres',
  `surface` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ex: ASPH, GRVL, WATER',
  PRIMARY KEY (`id`),
  KEY `idx_pistes_aeroport` (`aeroport_ident`),
  CONSTRAINT `fk_pistes_aeroport` FOREIGN KEY (`aeroport_ident`) REFERENCES `AEROPORTS` (`ident`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Peuplement : une ligne de PISTES par longueur trouvée dans Longueur_de_piste
--    (1 ft = 0,3048 m ; virgule décimale acceptée ; listes 'a/b/c' splittées ;
--     designation et surface alignées par position, NULL si absentes ;
--     longueur > 10000 m = donnée source aberrante -> NULL)
--    Compatible MySQL 8.0.4+ et MariaDB 10.6+ (JSON_TABLE). ~0,5 s sur 29 000 aéroports.
INSERT INTO `PISTES` (`aeroport_ident`, `designation`, `longueur_m`, `surface`)
SELECT src.`ident`,
       NULLIF(TRIM(TRAILING '-' FROM TRIM(jp.designation)), ''),
       NULLIF(CAST(ROUND(LEAST(CAST(REPLACE(jl.val, ',', '.') AS DECIMAL(20,6)) * 0.3048, 10000), 0) AS UNSIGNED), 10000),
       NULLIF(TRIM(js.val), '')
FROM `AEROPORTS` src
JOIN JSON_TABLE(
       CONCAT('["', REPLACE(src.`Longueur_de_piste`, '/', '","'), '"]'),
       '$[*]' COLUMNS (ordl FOR ORDINALITY, val VARCHAR(64) PATH '$')
     ) jl
LEFT JOIN JSON_TABLE(
       CONCAT('["', REPLACE(REPLACE(TRIM(src.`Piste`), '  ', ' '), ' ', '","'), '"]'),
       '$[*]' COLUMNS (ordp FOR ORDINALITY, designation VARCHAR(32) PATH '$')
     ) jp ON jp.ordp = jl.ordl
LEFT JOIN JSON_TABLE(
       CONCAT('["', REPLACE(src.`Type_de_piste`, '/', '","'), '"]'),
       '$[*]' COLUMNS (ords FOR ORDINALITY, val VARCHAR(64) PATH '$')
     ) js ON js.ords = jl.ordl
WHERE src.`Longueur_de_piste` REGEXP '^[0-9]+([.,][0-9]+)?(/+[0-9]+([.,][0-9]+)?)*/?$'
  AND jl.val <> '';

-- 3) Vérifications à faire après exécution (résultats attendus entre parenthèses) :
-- SELECT COUNT(*) FROM PISTES;                                  -- (28491)
-- SELECT COUNT(*) FROM PISTES WHERE longueur_m IS NULL;         -- (15 : données source aberrantes > 10 km)
-- SELECT COUNT(*) FROM AEROPORTS;                               -- (inchangé, ex 28967)
-- SHOW COLUMNS FROM AEROPORTS;                                  -- Piste/Longueur_de_piste/Type_de_piste encore présentes
-- SELECT * FROM PISTES WHERE aeroport_ident IN ('AA00','AA06','BGMV','LKVM') ORDER BY aeroport_ident, id;
--   AA00 -> 2-20 / 198 / TURF ; AA06 -> 6-24/488/GRVL + 7W-25W/1219/WATER ; BGMV -> 1835 ; LKVM -> NULL
-- Rollback à ce stade : DROP TABLE `PISTES`;  (base strictement identique à avant)
