-- Migration pour ajouter le champ seuil_heures à la table GRADES
-- Date: 2026-01-16
-- Description: Ajoute une colonne pour stocker le seuil d'heures requis pour chaque grade

USE `VA_Database`;

-- Ajout de la colonne seuil_heures
ALTER TABLE `GRADES` 
ADD COLUMN `seuil_heures` INT NOT NULL DEFAULT 0 COMMENT 'Nombre d heures de vol requis pour obtenir ce grade' AFTER `niveau`;

-- Mise à jour des seuils pour les grades existants
UPDATE `GRADES` SET `seuil_heures` = 0 WHERE `id` = 1;      -- Pilote Junior
UPDATE `GRADES` SET `seuil_heures` = 100 WHERE `id` = 2;    -- Pilote de brousse
UPDATE `GRADES` SET `seuil_heures` = 250 WHERE `id` = 3;    -- Pilote confirmé
UPDATE `GRADES` SET `seuil_heures` = 400 WHERE `id` = 6;    -- Copilote
UPDATE `GRADES` SET `seuil_heures` = 600 WHERE `id` = 4;    -- Commandant de bord
UPDATE `GRADES` SET `seuil_heures` = 800 WHERE `id` = 7;    -- Commandant de bord senior
UPDATE `GRADES` SET `seuil_heures` = 1000 WHERE `id` = 5;   -- Commandant de bord vétéran

-- Vérification
SELECT id, nom, niveau, seuil_heures, description FROM GRADES ORDER BY niveau ASC;
