-- Migration 010 : index de performance (NON uniques, non destructifs).
-- Poser des index simples est sans risque : si un doublon existait, la pose
-- d'un index UNIQUE echouerait, mais un index simple jamais.
-- Rollback : DROP INDEX (voir commentaires en bas).
-- A executer sur une base existante. Install neuve : deja dans 01_Main_Database.sql.
-- Compatibilite : MySQL 8.0+ / MariaDB 10.6+.

-- PILOTES : login et lookups par callsign (WHERE callsign = ...) -> full scan aujourd'hui
ALTER TABLE `PILOTES` ADD KEY `idx_callsign` (`callsign`);

-- FLOTTE : lookups par immat (dont ... FOR UPDATE), filtres actif/localisation/status
ALTER TABLE `FLOTTE`
  ADD KEY `idx_immat` (`immat`),
  ADD KEY `idx_actif` (`Actif`),
  ADD KEY `idx_localisation` (`localisation`),
  ADD KEY `idx_status` (`status`);

-- MISSIONS : lookup par libelle (missionCompatibleAvecType, getMajorationMission, ...)
ALTER TABLE `MISSIONS` ADD KEY `idx_libelle` (`libelle`);

-- password_resets : validation du token + nettoyage des expires
ALTER TABLE `password_resets`
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

-- RESERVATIONS : sweep d'expiration (statut + date_reservation) et lookup ACARS
ALTER TABLE `RESERVATIONS`
  ADD KEY `idx_statut_date` (`statut`, `date_reservation`),
  ADD KEY `idx_acars_cle` (`acars_cle`);

-- finances : rapports par periode / type / reference
ALTER TABLE `finances_depenses`
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_reference` (`reference_type`, `reference_id`);

ALTER TABLE `finances_recettes`
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_reference` (`reference_type`, `reference_id`);

-- CARNET_DE_VOL_GENERAL : filtres par date_vol / depart / destination
ALTER TABLE `CARNET_DE_VOL_GENERAL`
  ADD KEY `idx_date_vol` (`date_vol`),
  ADD KEY `idx_depart` (`depart`),
  ADD KEY `idx_destination` (`destination`);

-- simaddon_tokens : nettoyage des tokens expires
ALTER TABLE `simaddon_tokens` ADD KEY `idx_expires_at` (`expires_at`);

-- VOLS_REJETES : filtres admin par callsign / date
ALTER TABLE `VOLS_REJETES`
  ADD KEY `idx_callsign` (`callsign`),
  ADD KEY `idx_date_rejet` (`date_rejet`);

-- /!\ TRACE_GPS : la FK vers CARNET_DE_VOL_GENERAL n'est PAS dans ce fichier.
-- Sur la prod elle echoue (#1452) a cause de traces orphelines (vols supprimes).
-- Voir 010b_trace_gps_orphans_fk.sql : recenser, nettoyer, puis poser la FK.
-- (TRACE_GPS.id est deja cle primaire : les jointures sont deja indexees sans la FK.)

-- Verification :
--   SHOW INDEX FROM FLOTTE; SHOW INDEX FROM CARNET_DE_VOL_GENERAL;
-- Rollback (si besoin) :
--   ALTER TABLE `PILOTES` DROP INDEX `idx_callsign`;
--   ALTER TABLE `FLOTTE` DROP INDEX `idx_immat`, DROP INDEX `idx_actif`, DROP INDEX `idx_localisation`, DROP INDEX `idx_status`;
--   ALTER TABLE `MISSIONS` DROP INDEX `idx_libelle`;
--   ALTER TABLE `password_resets` DROP INDEX `idx_token`, DROP INDEX `idx_expires_at`;
--   ALTER TABLE `RESERVATIONS` DROP INDEX `idx_statut_date`, DROP INDEX `idx_acars_cle`;
--   ALTER TABLE `finances_depenses` DROP INDEX `idx_date`, DROP INDEX `idx_type`, DROP INDEX `idx_reference`;
--   ALTER TABLE `finances_recettes` DROP INDEX `idx_date`, DROP INDEX `idx_type`, DROP INDEX `idx_reference`;
--   ALTER TABLE `CARNET_DE_VOL_GENERAL` DROP INDEX `idx_date_vol`, DROP INDEX `idx_depart`, DROP INDEX `idx_destination`;
--   ALTER TABLE `simaddon_tokens` DROP INDEX `idx_expires_at`;
--   ALTER TABLE `VOLS_REJETES` DROP INDEX `idx_callsign`, DROP INDEX `idx_date_rejet`;
--   (la FK TRACE_GPS se rollback dans 010b : ALTER TABLE `TRACE_GPS` DROP FOREIGN KEY `fk_trace_vol`; )
