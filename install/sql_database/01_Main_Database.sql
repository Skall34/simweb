-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Généré le : dim. 16 nov. 2025 à 23:44
-- Version du serveur : 8.4.6-6
-- Version de PHP : 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `VA_Database`
--
CREATE DATABASE IF NOT EXISTS `VA_Database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `VA_Database`;

-- --------------------------------------------------------

--
-- Structure de la table `AEROPORTS`
--

DROP TABLE IF EXISTS `AEROPORTS`;
CREATE TABLE IF NOT EXISTS `AEROPORTS` (
  `ident` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `type_aeroport` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `municipality` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude_deg` decimal(15,6) DEFAULT NULL,
  `longitude_deg` decimal(15,6) DEFAULT NULL,
  `elevation_ft` int DEFAULT NULL,
  `Piste` text COLLATE utf8mb4_general_ci,
  `Longueur_de_piste` text COLLATE utf8mb4_general_ci,
  `Type_de_piste` text COLLATE utf8mb4_general_ci,
  `Observations` text COLLATE utf8mb4_general_ci,
  `wikipedia_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fret` int DEFAULT NULL,
  PRIMARY KEY (`ident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Structure de la table `AEROPORTS_LAST_ADMIN_UPDATE`
--

DROP TABLE IF EXISTS `AEROPORTS_LAST_ADMIN_UPDATE`;
CREATE TABLE IF NOT EXISTS `AEROPORTS_LAST_ADMIN_UPDATE` (
  `last_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `BALANCE_COMMERCIALE`
--

DROP TABLE IF EXISTS `BALANCE_COMMERCIALE`;
CREATE TABLE IF NOT EXISTS `BALANCE_COMMERCIALE` (
  `id` int NOT NULL AUTO_INCREMENT,
  `balance_actuelle` decimal(20,2) DEFAULT NULL,
  `derniere_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `commentaire` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `BALANCE_COMMERCIALE`
--

INSERT INTO `BALANCE_COMMERCIALE` (`id`, `balance_actuelle`, `derniere_maj`, `commentaire`) VALUES
(1, 0.00, NOW(), 'Initialisation');

-- --------------------------------------------------------

--
-- Structure de la table `CARNET_DE_VOL_GENERAL`
--

DROP TABLE IF EXISTS `CARNET_DE_VOL_GENERAL`;
CREATE TABLE IF NOT EXISTS `CARNET_DE_VOL_GENERAL` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_vol` date NOT NULL,
  `pilote_id` int NOT NULL,
  `appareil_id` int NOT NULL,
  `depart` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `destination` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `fuel_depart` decimal(10,2) NOT NULL,
  `fuel_arrivee` decimal(10,2) NOT NULL,
  `payload` int DEFAULT NULL,
  `heure_depart` time NOT NULL,
  `heure_arrivee` time NOT NULL,
  `temps_vol` time DEFAULT NULL,
  `note_du_vol` tinyint DEFAULT NULL,
  `mission_id` int DEFAULT NULL,
  `pirep_maintenance` text COLLATE utf8mb4_general_ci,
  `cout_vol` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pilote` (`pilote_id`),
  KEY `fk_appareil` (`appareil_id`),
  KEY `fk_mission` (`mission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `finances_depenses`
--

DROP TABLE IF EXISTS `finances_depenses`;
CREATE TABLE IF NOT EXISTS `finances_depenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `type` varchar(50) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `finances_recettes`
--

DROP TABLE IF EXISTS `finances_recettes`;
CREATE TABLE IF NOT EXISTS `finances_recettes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `type` varchar(50) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=616 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FLEET_TYPE`
--

DROP TABLE IF EXISTS `FLEET_TYPE`;
CREATE TABLE IF NOT EXISTS `FLEET_TYPE` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fleet_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cout_horaire` decimal(10,2) NOT NULL,
  `cout_appareil` decimal(15,2) NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Monomoteur',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FLOTTE`
--

DROP TABLE IF EXISTS `FLOTTE`;
CREATE TABLE IF NOT EXISTS `FLOTTE` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fleet_type` int DEFAULT NULL,
  `immat` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `localisation` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hub` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `etat` tinyint DEFAULT NULL,
  `dernier_utilisateur` int DEFAULT NULL,
  `fuel_restant` int DEFAULT NULL,
  `compteur_immo` tinyint DEFAULT NULL,
  `en_vol` tinyint DEFAULT NULL,
  `nb_maintenance` tinyint DEFAULT NULL,
  `Actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_achat` date DEFAULT NULL,
  `recettes` decimal(15,2) DEFAULT NULL,
  `nb_annees_credit` int DEFAULT NULL,
  `taux_percent` decimal(5,2) DEFAULT NULL,
  `remboursement` decimal(15,2) DEFAULT NULL,
  `traite_payee_cumulee` decimal(15,2) DEFAULT NULL,
  `reste_a_payer` decimal(15,2) DEFAULT NULL,
  `recette_vente` decimal(15,2) DEFAULT NULL,
  `date_vente` date DEFAULT NULL,
  `mode_achat` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'comptant',
  `reservee` tinyint(1) DEFAULT '0' COMMENT '1 = réservé (non disponible)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `GRADES`
--

DROP TABLE IF EXISTS `GRADES`;
CREATE TABLE IF NOT EXISTS `GRADES` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `description` text,
  `niveau` int NOT NULL DEFAULT '1',
  `seuil_heures` INT NOT NULL DEFAULT 0,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `taux_horaire` decimal(6,2) NOT NULL DEFAULT '10.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Données initiales pour la table `GRADES`
--

INSERT INTO `GRADES` (`id`, `nom`, `description`, `niveau`, `seuil_heures`, `taux_horaire`) VALUES
(1, 'Cadet', 'Grade de départ pour les nouveaux pilotes', 1, 0, 10.00);
--
-- Structure de la table `LIGNES_REGULIERES`
--

DROP TABLE IF EXISTS `LIGNES_REGULIERES`;
CREATE TABLE IF NOT EXISTS `LIGNES_REGULIERES` (
  `id` int NOT NULL AUTO_INCREMENT,
  `icao_dep` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `icao_arr` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `type_ligne` int NOT NULL DEFAULT '1',
  `distance` decimal(7,2) DEFAULT NULL COMMENT 'distance in nautical miles',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_icao_pair` (`icao_dep`,`icao_arr`),
  KEY `idx_icao_dep` (`icao_dep`),
  KEY `idx_icao_arr` (`icao_arr`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Live_FLIGHTS`
--

DROP TABLE IF EXISTS `Live_FLIGHTS`;
CREATE TABLE IF NOT EXISTS `Live_FLIGHTS` (
  `Callsign` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `ICAO_Dep` varchar(4) COLLATE utf8mb4_general_ci NOT NULL,
  `ICAO_Arr` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Avion` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  PRIMARY KEY (`Callsign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `MISSIONS`
--

DROP TABLE IF EXISTS `MISSIONS`;
CREATE TABLE IF NOT EXISTS `MISSIONS` (
  `id` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `majoration_mission` decimal(3,2) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `PILOTES`
--

DROP TABLE IF EXISTS `PILOTES`;
CREATE TABLE IF NOT EXISTS `PILOTES` (
  `id` int NOT NULL AUTO_INCREMENT,
  `callsign` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_id` int DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `revenus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `derniere_connexion` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int DEFAULT '1',
  `first_attempt` datetime NOT NULL,
  `last_attempt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip`,`action`),
  KEY `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `RESERVATIONS`
--

DROP TABLE IF EXISTS `RESERVATIONS`;
CREATE TABLE IF NOT EXISTS `RESERVATIONS` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ligne_id` int NOT NULL,
  `pilote_id` int NOT NULL,
  `immat` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `statut` enum('reserved','in_flight','completed','cancelled','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'reserved',
  `date_reservation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `acars_cle` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ligne_immat` (`ligne_id`,`immat`),
  KEY `idx_ligne_id` (`ligne_id`),
  KEY `idx_pilote_id` (`pilote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `SALAIRES`
--

DROP TABLE IF EXISTS `SALAIRES`;
CREATE TABLE IF NOT EXISTS `SALAIRES` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pilote` int NOT NULL,
  `date_de_paiement` date NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pilote` (`id_pilote`)
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `simaddon_tokens`
--

DROP TABLE IF EXISTS `simaddon_tokens`;
CREATE TABLE IF NOT EXISTS `simaddon_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `TRACE_GPS`
--

DROP TABLE IF EXISTS `TRACE_GPS`;
CREATE TABLE IF NOT EXISTS `TRACE_GPS` (
  `id` int NOT NULL,
  `path` json NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='table pour stocker les traces GPS des vols';

-- --------------------------------------------------------

--
-- Structure de la table `TYPE_LIGNE`
--

DROP TABLE IF EXISTS `TYPE_LIGNE`;
CREATE TABLE IF NOT EXISTS `TYPE_LIGNE` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Label` varchar(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `VARIABLES_CONFIG`
--

DROP TABLE IF EXISTS `VARIABLES_CONFIG`;
CREATE TABLE IF NOT EXISTS `VARIABLES_CONFIG` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(64) NOT NULL,
  `valeur` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
INSERT INTO VARIABLES_CONFIG (nom, valeur) VALUES
('prix_fret_kg_helico', '5.00'),
('prix_fret_kg_monomoteur', '5.00'),
('prix_fret_kg_bimoteur', '5.00'),
('prix_fret_kg_liner', '5.00'),
('bonus_fret_kg', '2.00'),
('prix_litre_essence', '0.88'),
('taux_assurance', '0.0200'),
('reservation_timeout_hours', '24');
--
-- Structure de la table `VOLS_REJETES`
--

DROP TABLE IF EXISTS `VOLS_REJETES`;
CREATE TABLE IF NOT EXISTS `VOLS_REJETES` (
  `id` int NOT NULL AUTO_INCREMENT,
  `acars_id` int NOT NULL,
  `horodateur` datetime DEFAULT NULL,
  `callsign` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `immatriculation` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `departure_icao` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arrival_icao` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `departure_fuel` float DEFAULT NULL,
  `arrival_fuel` float DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `payload` float DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `note_du_vol` int DEFAULT NULL,
  `mission` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motif_rejet` text COLLATE utf8mb4_general_ci,
  `date_rejet` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `CARNET_DE_VOL_GENERAL`
--
ALTER TABLE `CARNET_DE_VOL_GENERAL`
  ADD CONSTRAINT `fk_appareil` FOREIGN KEY (`appareil_id`) REFERENCES `FLOTTE` (`id`),
  ADD CONSTRAINT `fk_mission` FOREIGN KEY (`mission_id`) REFERENCES `MISSIONS` (`id`),
  ADD CONSTRAINT `fk_pilote` FOREIGN KEY (`pilote_id`) REFERENCES `PILOTES` (`id`);

--
-- Contraintes pour la table `SALAIRES`
--
ALTER TABLE `SALAIRES`
  ADD CONSTRAINT `SALAIRES_ibfk_1` FOREIGN KEY (`id_pilote`) REFERENCES `PILOTES` (`id`);

--
-- Contraintes pour la table `simaddon_tokens`
--
ALTER TABLE `simaddon_tokens`
  ADD CONSTRAINT `fk_simaddon_user` FOREIGN KEY (`user_id`) REFERENCES `PILOTES` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Compte administrateur par défaut
-- ⚠️ IMPORTANT : À supprimer après création de votre propre compte admin
--

INSERT INTO `PILOTES` (`callsign`, `password`, `email`, `nom`, `prenom`, `admin`, `actif`, `grade_id`, `revenus`) 
VALUES (
    'ADM0001', 
    '$2y$12$qFryMR2vrDxgvKvI.7PCv.qn22liKpV386Nh7XBMHr6CcsqQcu4k2', -- Mot de passe: "admin123"
    'admin@example.com',
    'Admin',
    'Système',
    1,
    1,
    1,
    0.00
);

-- --------------------------------------------------------

--
-- Structure de la table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `first_attempt` datetime NOT NULL,
  `last_attempt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_action` (`ip`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
