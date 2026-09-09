-- ============================================================
-- Migration : Annulation logique des vols
-- A executer sur les bases existantes avant d'utiliser
-- l'annulation de vol par le super-admin.
-- ============================================================

ALTER TABLE CARNET_DE_VOL_GENERAL
    ADD COLUMN annule TINYINT(1) NOT NULL DEFAULT 0 AFTER cout_vol,
    ADD COLUMN date_annulation DATETIME NULL AFTER annule,
    ADD COLUMN annule_par VARCHAR(7) NULL AFTER date_annulation,
    ADD COLUMN motif_annulation TEXT NULL AFTER annule_par,
    ADD INDEX idx_carnet_annule (annule);