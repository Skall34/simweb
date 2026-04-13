-- ============================================================
-- Migration : Ajout du coût de maintenance par type d'appareil
-- À exécuter sur les bases existantes
-- ============================================================

-- 1. Ajouter la colonne cout_maintenance à FLEET_TYPE
ALTER TABLE FLEET_TYPE
  ADD COLUMN cout_maintenance DECIMAL(10,2) NOT NULL DEFAULT 0
  AFTER cout_appareil;

-- 2. Ajouter la variable multiplicateur_crash dans VARIABLES_CONFIG
INSERT IGNORE INTO VARIABLES_CONFIG (nom, valeur) VALUES ('multiplicateur_crash', '3');

-- ============================================================
-- IMPORTANT : Après cette migration, exécuter le script PHP
--   php scripts/retroactivite_maintenance.php
-- pour calculer et enregistrer les coûts de maintenance
-- rétroactifs (basés sur nb_maintenance de chaque appareil).
-- ⚠ Ne l'exécuter qu'UNE SEULE FOIS, après avoir défini
--   les valeurs cout_maintenance dans FLEET_TYPE.
-- ============================================================
