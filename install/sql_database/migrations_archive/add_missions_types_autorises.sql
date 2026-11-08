-- Migration : restriction des missions par type d'appareil
-- Date : 2026-11-08
-- Ajoute MISSIONS.types_autorises : CSV des catégories FLEET_TYPE.type autorisées
-- (Monomoteur,Bimoteur,Liner,Helico). '*' = tous les types (comportement par défaut, rétrocompatible).

ALTER TABLE `MISSIONS`
  ADD COLUMN `types_autorises` VARCHAR(255) NOT NULL DEFAULT '*'
  COMMENT 'CSV des types FLEET_TYPE.type autorisés, * = tous'
  AFTER `est_controle`;
