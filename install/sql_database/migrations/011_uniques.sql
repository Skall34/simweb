-- Migration 011 : contraintes UNIQUE (a poser APRES avoir verifie l'absence de doublons).
-- /!\ Si l'un des ALTER echoue pour cause de doublons, NE PAS forcer :
--     inspecter les lignes concernees avec les requetes ci-dessous, corriger
--     les donnees a la main, puis rejouer la migration.
-- Verifications prealables (chacune doit retourner 0 ligne) :
--   SELECT callsign, COUNT(*) c FROM PILOTES GROUP BY callsign HAVING c > 1;
--   SELECT libelle, COUNT(*) c FROM MISSIONS GROUP BY libelle HAVING c > 1;
--   SELECT token, COUNT(*) c FROM password_resets GROUP BY token HAVING c > 1;
--   SELECT immat, COUNT(*) c FROM FLOTTE GROUP BY immat HAVING c > 1;  -- /!\ aircraft : resoudre a la main
-- A executer sur base existante. Install neuve : deja dans 01_Main_Database.sql.

-- PILOTES.callsign : identifiant de connexion, doit etre unique
ALTER TABLE `PILOTES` ADD UNIQUE KEY `uq_callsign` (`callsign`);

-- MISSIONS.libelle : lookup par libelle dans le code PHP
ALTER TABLE `MISSIONS` ADD UNIQUE KEY `uq_libelle` (`libelle`);

-- password_resets.token : token de reinitialisation
ALTER TABLE `password_resets` ADD UNIQUE KEY `uq_token` (`token`);

-- FLOTTE.immat : une seule ligne par immatriculation
ALTER TABLE `FLOTTE` ADD UNIQUE KEY `uq_immat` (`immat`);

-- Verification :
--   SHOW INDEX FROM PILOTES; SHOW INDEX FROM FLOTTE; SHOW INDEX FROM MISSIONS; SHOW INDEX FROM password_resets;
-- Rollback :
--   ALTER TABLE `PILOTES` DROP INDEX `uq_callsign`;
--   ALTER TABLE `MISSIONS` DROP INDEX `uq_libelle`;
--   ALTER TABLE `password_resets` DROP INDEX `uq_token`;
--   ALTER TABLE `FLOTTE` DROP INDEX `uq_immat`;
