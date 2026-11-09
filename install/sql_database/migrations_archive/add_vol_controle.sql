-- Migration : vol de contrôle post-maintenance + prime de risque
-- Date : 2026-11-08
-- Ajoute :
--  - FLOTTE.controle_a_faire : 1 = l'appareil doit effectuer un vol de contrôle avant d'être réutilisé
--  - MISSIONS.est_controle : 1 = mission spéciale "contrôle post-maintenance"
--  - MAINTENANCES_LOG.type_maintenance : nouveau type 'controle'
--  - VARIABLES_CONFIG.prime_controle_vol : prime versée au pilote par vol de contrôle effectué
--  - la mission "Mission De Contrôle" (si aucune mission de contrôle n'existe déjà)

ALTER TABLE `FLOTTE`
  ADD COLUMN `controle_a_faire` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = vol de contrôle post-maintenance obligatoire avant remise en service'
  AFTER `reservee`;

ALTER TABLE `MISSIONS`
  ADD COLUMN `est_controle` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = mission spéciale de contrôle post-maintenance'
  AFTER `Active`;

ALTER TABLE `MAINTENANCES_LOG`
  MODIFY `type_maintenance` ENUM('usure','crash','sortie','sortie_crash','controle') NOT NULL DEFAULT 'usure';

INSERT INTO `VARIABLES_CONFIG` (`nom`, `valeur`) VALUES
('prime_controle_vol', '500.00');

-- Si une mission "Mission De Contrôle" existe déjà (créée à la main), on la marque comme mission de contrôle
UPDATE `MISSIONS` SET `est_controle` = 1 WHERE `libelle` = 'Mission De Contrôle' AND `est_controle` = 0;

-- Sinon, on la crée
INSERT INTO `MISSIONS` (`libelle`, `majoration_mission`, `Active`, `est_controle`)
SELECT 'Mission De Contrôle', 1.00, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `MISSIONS` WHERE `est_controle` = 1);
