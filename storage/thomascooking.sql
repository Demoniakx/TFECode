-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 20 oct. 2025 à 19:53
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `thomascooking`
--

-- --------------------------------------------------------

--
-- Structure de la table `allergenes`
--

DROP TABLE IF EXISTS `allergenes`;
CREATE TABLE IF NOT EXISTS `allergenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `allergenes`
--

INSERT INTO `allergenes` (`id`, `nom`) VALUES
(1, 'Gluten (blé, seigle, orge, avoine, épeautre, kamut, etc.)'),
(2, 'Crustacés'),
(3, 'Œufs'),
(4, 'Poissons'),
(5, 'Arachides'),
(6, 'Soja'),
(7, 'Lait'),
(8, 'Amandes'),
(9, 'Noisettes'),
(10, 'Noix'),
(11, 'Noix de cajou'),
(12, 'Noix de pécan'),
(13, 'Noix du Brésil'),
(14, 'Pistaches'),
(15, 'Noix de macadamia'),
(16, 'Céleri'),
(17, 'Moutarde'),
(18, 'Graines de sésame'),
(19, 'Anhydride sulfureux et sulfites (>10 mg/kg)'),
(20, 'Lupin'),
(21, 'Mollusques');

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

DROP TABLE IF EXISTS `evenements`;
CREATE TABLE IF NOT EXISTS `evenements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `date_event` datetime NOT NULL,
  `description` text,
  `nb_places` int NOT NULL,
  `prix_place` decimal(10,2) NOT NULL,
  `infos_complementaires` text,
  `duree` varchar(50) DEFAULT NULL,
  `date_fin_inscription` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `nom`, `date_event`, `description`, `nb_places`, `prix_place`, `infos_complementaires`, `duree`, `date_fin_inscription`, `created_at`) VALUES
(1, 'Cours de patisserie', '2025-11-01 16:47:00', 'Cours de patisserie qui consistera a vous apprendre comme réaliser des éclairs au chocolat.', 19, 15.00, 'Prévoir vos ustensiles de cuisine', '3h', '2025-10-31 11:47:00', '2025-10-19 09:47:50');

-- --------------------------------------------------------

--
-- Structure de la table `paniers_repas`
--

DROP TABLE IF EXISTS `paniers_repas`;
CREATE TABLE IF NOT EXISTS `paniers_repas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
 NOT NULL,
  `nb_personnes` int NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `disponible` int NOT NULL,
  `ingredients` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `paniers_repas`
--

INSERT INTO `paniers_repas` (`id`, `nom`, `nb_personnes`, `prix`, `disponible`, `ingredients`, `created_at`) VALUES
(1, 'Panier de la mer', 4, 30.00, 18, 'Saumon, Saint, jacques, moules.', '2025-10-05 15:07:39');

-- --------------------------------------------------------

--
-- Structure de la table `panier_allergenes`
--

DROP TABLE IF EXISTS `panier_allergenes`;
CREATE TABLE IF NOT EXISTS `panier_allergenes` (
  `panier_id` int NOT NULL,
  `allergene_id` int NOT NULL,
  PRIMARY KEY (`panier_id`,`allergene_id`),
  KEY `allergene_id` (`allergene_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `panier_allergenes`
--

INSERT INTO `panier_allergenes` (`panier_id`, `allergene_id`) VALUES
(1, 2),
(1, 4),
(1, 21);

-- --------------------------------------------------------

--
-- Structure de la table `planches_apero`
--

DROP TABLE IF EXISTS `planches_apero`;
CREATE TABLE IF NOT EXISTS `planches_apero` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `nb_personnes` int NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `disponible` int NOT NULL,
  `ingredients` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `planches_apero`
--

INSERT INTO `planches_apero` (`id`, `nom`, `nb_personnes`, `prix`, `disponible`, `ingredients`, `created_at`) VALUES
(1, 'Planche Gourmande', 3, 15.50, 9, 'Fromages, Charcuterie, Olives', '2025-10-02 22:08:49');

-- --------------------------------------------------------

--
-- Structure de la table `planche_allergenes`
--

DROP TABLE IF EXISTS `planche_allergenes`;
CREATE TABLE IF NOT EXISTS `planche_allergenes` (
  `planche_id` int NOT NULL,
  `allergene_id` int NOT NULL,
  PRIMARY KEY (`planche_id`,`allergene_id`),
  KEY `allergene_id` (`allergene_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `planche_allergenes`
--

INSERT INTO `planche_allergenes` (`planche_id`, `allergene_id`) VALUES
(1, 5),
(1, 7);

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('panier','planche','evenement','salle') NOT NULL,
  `entity_id` int NOT NULL,
  `nom_client` varchar(255) NOT NULL,
  `email_client` varchar(255) DEFAULT NULL,
  `tel_client` varchar(20) DEFAULT NULL,
  `adresse_client` text,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantite` int DEFAULT '1',
  `info_complementaire` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `type`, `entity_id`, `nom_client`, `email_client`, `tel_client`, `adresse_client`, `date`, `quantite`, `info_complementaire`) VALUES
(4, 'salle', 0, 'De Campenaere Thomas ', 'thomas-dec@hotmail.com', '', NULL, '2025-10-31 09:00:00', 50, ''),
(3, 'panier', 1, 'Thomas', 'thomas-dec@hotmail.com', '', 'test', '2025-10-19 21:18:00', 1, ''),
(5, 'salle', 0, 'Test Priv', 'priv@test.local', '000000000', NULL, '2025-10-19 09:00:00', 1, 'Test insertion pour privatisation\nService traiteur: Non'),
(9, 'panier', 1, 'De Campenaere Thomas ', 'thomas-dec@hotmail.com', '+32477943189', 'test', '2025-10-30 03:43:00', 1, 'Service traiteur: Non'),
(19, 'planche', 1, 'Thomas', 'thomas-dec@hotmail.com', '00 32 477 94 31 89', '6 Rue du Delta, 75009 Paris, France', '2025-10-24 00:00:00', 1, 'Service traiteur: Non'),
(20, 'evenement', 1, 'Thomas', 'thomas-dec@hotmail.com', '00 32 477 94 31 89', '6 Rue du Delta, 75009 Paris, France', '2025-10-24 00:00:00', 1, 'Service traiteur: Non');

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `capacite` int NOT NULL,
  `description` text,
  `prix_location` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `description`, `prix_location`, `created_at`) VALUES
(1, 'Le Palais du Temps', 200, 'Salle principale pour événements ou privatisations.', 1500.00, '2025-10-02 19:49:21');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'admindespapilles@exemple.com', '$2y$16$HMD6dqMH.CK73.uvt6ESSOJiAzQk.i.kajrXCY96ZQoKbF5OP8SRq', 'admin', '2025-10-20 21:24:01');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
