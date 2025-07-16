-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : skywinjsky0707.mysql.db
-- Généré le : mer. 16 juil. 2025 à 10:42
-- Version du serveur : 8.4.5-5
-- Version de PHP : 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `skywinjsky0707`
--
CREATE DATABASE IF NOT EXISTS `skywinjsky0707` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `skywinjsky0707`;

-- --------------------------------------------------------

--
-- Structure de la table `AEROPORTS`
--

DROP TABLE IF EXISTS `AEROPORTS`;
CREATE TABLE `AEROPORTS` (
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
  `fret` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `AEROPORTS_LAST_ADMIN_UPDATE`
--

DROP TABLE IF EXISTS `AEROPORTS_LAST_ADMIN_UPDATE`;
CREATE TABLE `AEROPORTS_LAST_ADMIN_UPDATE` (
  `last_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `BALANCE_COMMERCIALE`
--

DROP TABLE IF EXISTS `BALANCE_COMMERCIALE`;
CREATE TABLE `BALANCE_COMMERCIALE` (
  `id` int NOT NULL,
  `balance_actuelle` decimal(20,2) DEFAULT NULL,
  `recettes` decimal(20,2) DEFAULT NULL,
  `cout_avions` decimal(20,2) DEFAULT NULL,
  `apport_initial` decimal(20,2) DEFAULT NULL,
  `date_maj` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assurance` decimal(20,2) NOT NULL DEFAULT '0.00',
  `paiement_salaires` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recettes_ventes_appareils` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `CARNET_DE_VOL_GENERAL`
--

DROP TABLE IF EXISTS `CARNET_DE_VOL_GENERAL`;
CREATE TABLE `CARNET_DE_VOL_GENERAL` (
  `id` int NOT NULL,
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
  `cout_vol` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FINANCES`
--

DROP TABLE IF EXISTS `FINANCES`;
CREATE TABLE `FINANCES` (
  `id` int NOT NULL,
  `avion_id` int NOT NULL,
  `date_achat` date NOT NULL,
  `recettes` decimal(15,2) DEFAULT NULL,
  `nb_annees_credit` int DEFAULT NULL,
  `taux_percent` decimal(5,2) DEFAULT NULL,
  `remboursement` decimal(15,2) DEFAULT NULL,
  `traite_payee_cumulee` decimal(15,2) DEFAULT NULL,
  `reste_a_payer` decimal(15,2) DEFAULT NULL,
  `recette_vente` decimal(15,2) DEFAULT NULL,
  `date_vente` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FLEET_TYPE`
--

DROP TABLE IF EXISTS `FLEET_TYPE`;
CREATE TABLE `FLEET_TYPE` (
  `id` int NOT NULL,
  `fleet_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cout_horaire` decimal(10,2) NOT NULL,
  `cout_appareil` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FLOTTE`
--

DROP TABLE IF EXISTS `FLOTTE`;
CREATE TABLE `FLOTTE` (
  `id` int NOT NULL,
  `fleet_type` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `Actif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FROM_ACARS`
--

DROP TABLE IF EXISTS `FROM_ACARS`;
CREATE TABLE `FROM_ACARS` (
  `id` int NOT NULL,
  `horodateur` datetime NOT NULL,
  `callsign` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `immatriculation` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `departure_icao` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `departure_fuel` float DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_icao` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `arrival_fuel` float DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `payload` float DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `note_du_vol` tinyint UNSIGNED DEFAULT NULL,
  `mission` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `processed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `GRADES`
--

DROP TABLE IF EXISTS `GRADES`;
CREATE TABLE `GRADES` (
  `id` int NOT NULL,
  `nom` varchar(50) NOT NULL,
  `description` text,
  `niveau` int NOT NULL DEFAULT '1',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `taux_horaire` decimal(6,2) NOT NULL DEFAULT '10.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Live_FLIGHTS`
--

DROP TABLE IF EXISTS `Live_FLIGHTS`;
CREATE TABLE `Live_FLIGHTS` (
  `Callsign` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `ICAO_Dep` varchar(4) COLLATE utf8mb4_general_ci NOT NULL,
  `ICAO_Arr` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Avion` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `MISSIONS`
--

DROP TABLE IF EXISTS `MISSIONS`;
CREATE TABLE `MISSIONS` (
  `id` int NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `majoration_mission` decimal(3,2) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `PILOTES`
--

DROP TABLE IF EXISTS `PILOTES`;
CREATE TABLE `PILOTES` (
  `id` int NOT NULL,
  `callsign` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_id` int DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `revenus` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `SALAIRES`
--

DROP TABLE IF EXISTS `SALAIRES`;
CREATE TABLE `SALAIRES` (
  `id` int NOT NULL,
  `id_pilote` int NOT NULL,
  `date_de_paiement` date NOT NULL,
  `montant` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `VOLS_REJETES`
--

DROP TABLE IF EXISTS `VOLS_REJETES`;
CREATE TABLE `VOLS_REJETES` (
  `id` int NOT NULL,
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
  `date_rejet` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `AEROPORTS`
--
ALTER TABLE `AEROPORTS`
  ADD PRIMARY KEY (`ident`);

--
-- Index pour la table `BALANCE_COMMERCIALE`
--
ALTER TABLE `BALANCE_COMMERCIALE`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `CARNET_DE_VOL_GENERAL`
--
ALTER TABLE `CARNET_DE_VOL_GENERAL`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pilote` (`pilote_id`),
  ADD KEY `fk_appareil` (`appareil_id`),
  ADD KEY `fk_mission` (`mission_id`);

--
-- Index pour la table `FINANCES`
--
ALTER TABLE `FINANCES`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avion_id` (`avion_id`);

--
-- Index pour la table `FLEET_TYPE`
--
ALTER TABLE `FLEET_TYPE`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `FLOTTE`
--
ALTER TABLE `FLOTTE`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `FROM_ACARS`
--
ALTER TABLE `FROM_ACARS`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `GRADES`
--
ALTER TABLE `GRADES`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Live_FLIGHTS`
--
ALTER TABLE `Live_FLIGHTS`
  ADD PRIMARY KEY (`Callsign`);

--
-- Index pour la table `MISSIONS`
--
ALTER TABLE `MISSIONS`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `PILOTES`
--
ALTER TABLE `PILOTES`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `SALAIRES`
--
ALTER TABLE `SALAIRES`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pilote` (`id_pilote`);

--
-- Index pour la table `VOLS_REJETES`
--
ALTER TABLE `VOLS_REJETES`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `BALANCE_COMMERCIALE`
--
ALTER TABLE `BALANCE_COMMERCIALE`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `CARNET_DE_VOL_GENERAL`
--
ALTER TABLE `CARNET_DE_VOL_GENERAL`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `FINANCES`
--
ALTER TABLE `FINANCES`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `FLEET_TYPE`
--
ALTER TABLE `FLEET_TYPE`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `FLOTTE`
--
ALTER TABLE `FLOTTE`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `FROM_ACARS`
--
ALTER TABLE `FROM_ACARS`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `GRADES`
--
ALTER TABLE `GRADES`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `MISSIONS`
--
ALTER TABLE `MISSIONS`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `PILOTES`
--
ALTER TABLE `PILOTES`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `SALAIRES`
--
ALTER TABLE `SALAIRES`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `VOLS_REJETES`
--
ALTER TABLE `VOLS_REJETES`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- Contraintes pour la table `FINANCES`
--
ALTER TABLE `FINANCES`
  ADD CONSTRAINT `FINANCES_ibfk_1` FOREIGN KEY (`avion_id`) REFERENCES `FLOTTE` (`id`);

--
-- Contraintes pour la table `SALAIRES`
--
ALTER TABLE `SALAIRES`
  ADD CONSTRAINT `SALAIRES_ibfk_1` FOREIGN KEY (`id_pilote`) REFERENCES `PILOTES` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
