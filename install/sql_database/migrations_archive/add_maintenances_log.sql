-- ============================================================
-- Migration : Table MAINTENANCES_LOG pour historique maintenances
-- À exécuter sur les bases existantes avant d'utiliser le carnet avion
-- ============================================================

-- 1. Créer la table MAINTENANCES_LOG
CREATE TABLE IF NOT EXISTS MAINTENANCES_LOG (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appareil_id INT NOT NULL,
    date_maintenance DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_maintenance ENUM('usure', 'crash', 'sortie', 'sortie_crash') NOT NULL DEFAULT 'usure',
    etat_avant TINYINT UNSIGNED DEFAULT NULL,
    etat_apres TINYINT UNSIGNED DEFAULT NULL,
    cout DECIMAL(10,2) DEFAULT NULL,
    commentaire VARCHAR(255) DEFAULT NULL,
    INDEX idx_appareil (appareil_id),
    INDEX idx_date (date_maintenance),
    FOREIGN KEY (appareil_id) REFERENCES FLOTTE(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- IMPORTANT : Après cette migration, les nouvelles maintenances
-- seront automatiquement loguées. Pour récupérer l'historique
-- passé, exécuter :
--   php scripts/migration_maintenances_log.php
-- ============================================================
