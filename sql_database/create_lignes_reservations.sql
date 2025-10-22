-- Création de la table LIGNES_REGULIERES
CREATE TABLE IF NOT EXISTS LIGNES_REGULIERES (
  id INT AUTO_INCREMENT PRIMARY KEY,
  icao_dep VARCHAR(8) NOT NULL,
  icao_arr VARCHAR(8) NOT NULL,
  pilote_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_icao_dep (icao_dep),
  INDEX idx_icao_arr (icao_arr),
  INDEX idx_pilote_id (pilote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS RESERVATIONS (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ligne_id INT NOT NULL,
  pilote_id INT NOT NULL,
  immat VARCHAR(32) DEFAULT NULL,
  statut ENUM('reserved','in_flight','completed','cancelled') DEFAULT 'reserved',
  date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_debut DATETIME DEFAULT NULL,
  date_fin DATETIME DEFAULT NULL,
  acars_cle VARCHAR(64) DEFAULT NULL,
  INDEX idx_ligne_id (ligne_id),
  INDEX idx_pilote_id (pilote_id),
  UNIQUE KEY uniq_ligne_immat (ligne_id, immat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajouter champ réservé dans FLOTTE
ALTER TABLE FLOTTE
  ADD COLUMN reservee TINYINT(1) DEFAULT 0 COMMENT '1 = réservé (non disponible)';

-- Quelques exemples d'insertion dans LIGNES_REGULIERES
INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr) VALUES
('LFPG','LFPO'),
('LFPG','LFMN'),
('LFPO','LFLC'),
('LFLC','LFMN');

-- Exemple de réservation
-- INSERT INTO RESERVATIONS (ligne_id, pilote_id, immat, statut) VALUES (1, 10, 'F-ABCD', 'reserved');
