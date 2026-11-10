-- ==============================================================================
-- BASE DE DONNÉES : PORTAIL VOYAGEUR - CARRÉ D'HÔTES
-- Version MySQL : 8.4+ (Compatible MySQL 8.0 & 8.4)
-- ==============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ------------------------------------------------------------------------------
-- Table : properties (Logements)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Identifiant URL unique (ex: maisonnette-en-jouhet)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nom public du logement',
    `guide_fr_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret FR',
    `guide_en_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret EN',
    `wifi_ssid` VARCHAR(100) DEFAULT NULL COMMENT 'Nom du réseau WiFi (optionnel)',
    `wifi_password` VARCHAR(100) DEFAULT NULL COMMENT 'Clé de sécurité WiFi (optionnel)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse du logement (optionnel)',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = visible, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_properties_slug_active` (`slug`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : videos (Vidéos & Modes d'emploi des équipements)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers la table properties',
    `slug` VARCHAR(100) NOT NULL COMMENT 'Identifiant URL unique de la vidéo (ex: machine-a-laver)',
    `title_fr` VARCHAR(255) NOT NULL COMMENT 'Nom équipement en français (ex: Machine à laver)',
    `title_en` VARCHAR(255) DEFAULT NULL COMMENT 'Nom équipement en anglais (ex: Washing machine)',
    `icon` VARCHAR(50) NOT NULL DEFAULT '💡' COMMENT 'Emoji ou icône représentative (ex: 🫧, 🍳)',
    `icon_bg_color` VARCHAR(50) NOT NULL DEFAULT 'bg-blue-50' COMMENT 'Classe Tailwind pour fond icône',
    `icon_text_color` VARCHAR(50) NOT NULL DEFAULT 'text-blue-500' COMMENT 'Classe Tailwind pour couleur icône',
    `video_url` VARCHAR(1000) NOT NULL COMMENT 'Lien direct vers la vidéo (Google Drive, Cloudflare R2, etc.)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = inactif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_videos_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_videos_property_slug` (`property_id`, `slug`),
    INDEX `idx_videos_property_order` (`property_id`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : property_parkings (Règles générales et stationnement voirie)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `property_parkings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL UNIQUE COMMENT 'Référence vers la table properties',
    `intro_fr` TEXT DEFAULT NULL COMMENT 'Introduction en français',
    `intro_en` TEXT DEFAULT NULL COMMENT 'Introduction en anglais',
    `important_rule_fr` TEXT DEFAULT NULL COMMENT 'Règle impérative / avertissement en français',
    `important_rule_en` TEXT DEFAULT NULL COMMENT 'Règle impérative / avertissement en anglais',
    `free_parking_notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques sur le stationnement gratuit (FR)',
    `free_parking_notes_en` TEXT DEFAULT NULL COMMENT 'Remarques sur le stationnement gratuit (EN)',
    `street_parking_info_fr` TEXT DEFAULT NULL COMMENT 'Rues payantes et périmètres (FR)',
    `street_parking_info_en` TEXT DEFAULT NULL COMMENT 'Rues payantes et périmètres (EN)',
    `street_parking_schedule_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires payants sur voirie (FR)',
    `street_parking_schedule_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires payants sur voirie (EN)',
    `street_parking_rates_fr` TEXT DEFAULT NULL COMMENT 'Tarifs voirie par catégorie de véhicule (FR)',
    `street_parking_rates_en` TEXT DEFAULT NULL COMMENT 'Tarifs voirie par catégorie de véhicule (EN)',
    `street_parking_app` VARCHAR(100) NOT NULL DEFAULT 'EasyPark' COMMENT 'Application mobile recommandée',
    `tcl_conditions_fr` TEXT DEFAULT NULL COMMENT 'Conditions d''utilisation des P+R TCL (FR)',
    `tcl_conditions_en` TEXT DEFAULT NULL COMMENT 'Conditions d''utilisation des P+R TCL (EN)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_property_parkings_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_free_spots (Rues et dépose-minute gratuits)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_free_spots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers properties',
    `spot_type` ENUM('street', 'spot') NOT NULL DEFAULT 'street' COMMENT 'street = rue gratuite, spot = arrêt minute / court',
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue (EN)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise ou repère GPS',
    `walking_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (FR)',
    `walking_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (EN)',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (EN)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pfs_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_pfs_property_order` (`property_id`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_private_lots (Parkings privés et couverts)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_private_lots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers properties',
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du parking (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du parking (EN)',
    `operator` VARCHAR(100) DEFAULT NULL COMMENT 'Opérateur (Zenpark, LPA&CO, Q-Park, Indigo...)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise du parking',
    `walking_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (FR)',
    `walking_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (EN)',
    `max_height` VARCHAR(50) DEFAULT NULL COMMENT 'Hauteur max / gabarit (ex: 1.90 m)',
    `opening_hours_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (FR)',
    `opening_hours_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (EN)',
    `rates_fr` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (FR)',
    `rates_en` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (EN)',
    `suitable_vehicles_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (FR)',
    `suitable_vehicles_en` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (EN)',
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien de réservation directe',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (EN)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ppl_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_ppl_property_order` (`property_id`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_tcl_prs (Parcs Relais TCL - P+R)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_tcl_prs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers properties',
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du P+R (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du P+R (EN)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise du P+R',
    `transit_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports (FR)',
    `transit_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports (EN)',
    `capacity` VARCHAR(100) DEFAULT NULL COMMENT 'Capacité totale (ex: 416 places)',
    `opening_hours_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (FR)',
    `opening_hours_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (EN)',
    `how_to_reach_fr` TEXT DEFAULT NULL COMMENT 'Itinéraire retour vers le logement (FR)',
    `how_to_reach_en` TEXT DEFAULT NULL COMMENT 'Itinéraire retour vers le logement (EN)',
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien officiel TCL',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (EN)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ptp_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_ptp_property_order` (`property_id`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Vue : parking_locations (Rétrocompatibilité avec l'ancien schéma unifié)
-- ------------------------------------------------------------------------------
CREATE OR REPLACE VIEW `parking_locations` AS
SELECT 
    id, property_id,
    CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category,
    name_fr, name_en, address,
    walking_time_fr, walking_time_en,
    NULL AS walking_minutes,
    NULL AS transit_time_fr, NULL AS transit_time_en,
    NULL AS max_height, NULL AS capacity,
    NULL AS opening_hours_fr, NULL AS opening_hours_en,
    NULL AS rates_fr, NULL AS rates_en,
    NULL AS suitable_vehicles_fr, NULL AS suitable_vehicles_en,
    NULL AS how_to_reach_fr, NULL AS how_to_reach_en,
    NULL AS operator, NULL AS booking_url,
    notes_fr, notes_en, display_order, active, created_at, updated_at
FROM `parking_free_spots`
UNION ALL
SELECT 
    id, property_id,
    'private_parking' AS category,
    name_fr, name_en, address,
    walking_time_fr, walking_time_en,
    NULL AS walking_minutes,
    NULL AS transit_time_fr, NULL AS transit_time_en,
    max_height, NULL AS capacity,
    opening_hours_fr, opening_hours_en,
    rates_fr, rates_en,
    suitable_vehicles_fr, suitable_vehicles_en,
    NULL AS how_to_reach_fr, NULL AS how_to_reach_en,
    operator, booking_url,
    notes_fr, notes_en, display_order, active, created_at, updated_at
FROM `parking_private_lots`
UNION ALL
SELECT 
    id, property_id,
    'tcl_pr' AS category,
    name_fr, name_en, address,
    NULL AS walking_time_fr, NULL AS walking_time_en,
    NULL AS walking_minutes,
    transit_time_fr, transit_time_en,
    NULL AS max_height, capacity,
    opening_hours_fr, opening_hours_en,
    NULL AS rates_fr, NULL AS rates_en,
    NULL AS suitable_vehicles_fr, NULL AS suitable_vehicles_en,
    how_to_reach_fr, how_to_reach_en,
    'TCL' AS operator, booking_url,
    notes_fr, notes_en, display_order, active, created_at, updated_at
FROM `parking_tcl_prs`;

-- ------------------------------------------------------------------------------
SET foreign_key_checks = 1;

-- ======================================================================
-- DONNÉES INITIALES (SEED) - CARRÉ D'HÔTES
-- Import des 9 logements existants et des vidéos explicatives associées
-- ======================================================================
SET NAMES utf8mb4;

-- Nettoyage préalable si ré-exécution
DELETE FROM parking_free_spots;
DELETE FROM parking_private_lots;
DELETE FROM parking_tcl_prs;
DELETE FROM property_parkings;
DELETE FROM videos;
DELETE FROM properties;

-- ----------------------------------------------------------------------
-- Logement : Bataille (bataille)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('bataille', 'Bataille', 'https://drive.google.com/file/d/1x7yyx4NbnvjP4gigDsvjli4tOENQ98sU/view?usp=sharing', 'https://drive.google.com/file/d/1q5lbxNLdQQI-_rFBWjPHn6r-6IOiTLCI/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();
-- Vidéos pour Bataille
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-laver', 'Machine à laver', 'Washing machine', '🫧', 'bg-blue-50', 'text-blue-500', 'https://drive.google.com/file/d/1NMkCkVSiQxLAURf384V_safGuTCZOn8s/view?usp=drive_link', 1, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'plaque-induction', 'Plaque induction', 'Induction stove', '🍳', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1aY7199w2PtwY6X7Zr0GBgZggL0aDEfds/view?usp=drive_link', 2, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'micro-ondes', 'Micro-ondes', 'Microwave', '♨️', 'bg-gray-100', 'text-gray-600', 'https://drive.google.com/file/d/13du7fbTv0et0B_NHjOu62a3lsD6Odop3/view?usp=drive_link', 3, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'bouilloire', 'Bouilloire', 'Kettle', '🫖', 'bg-teal-50', 'text-teal-500', 'https://drive.google.com/file/d/1xuuLjmOh3LzsCEYKIrcTaWZURdMT9FDg/view?usp=drive_link', 4, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'television', 'Télévision', 'Television', '📺', 'bg-zinc-100', 'text-zinc-600', 'https://drive.google.com/file/d/1LezEzqApQWpYI_53IzFefZuABL_eJdkw/view?usp=drive_link', 5, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-cafe', 'Machine à café', 'Coffee maker', '☕', 'bg-amber-50', 'text-amber-600', 'https://drive.google.com/file/d/1HcMK0c2A7iMrOz8-c6kfS5wTSyY1tuSo/view?usp=drive_link', 6, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'canape-convertible', 'Canapé convertible', 'Sofa bed', '🛋️', 'bg-rose-50', 'text-rose-500', 'https://drive.google.com/file/d/1asJroLOSOiLJAthgJP_erkG9WxHniUa9/view?usp=drive_link', 7, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'radiateur', 'Radiateur', 'Radiator', '🌡️', 'bg-red-50', 'text-red-500', 'https://drive.google.com/file/d/1f3HOr5h4sFHwwDOsuh6WrjTtP4ikudx-/view?usp=sharing', 8, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'seche-serviette', 'Sèche-serviette', 'Heated towel rail', '🛁', 'bg-sky-50', 'text-sky-500', 'https://drive.google.com/file/d/1ImAKg96Utuh8mYU_926uKSwxGApzc3wL/view?usp=sharing', 9, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'volet-roulant', 'Volet roulant', 'Roller shutter', '🪟', 'bg-indigo-50', 'text-indigo-500', 'https://drive.google.com/file/d/1_0pSD1_heKwU3fuCektSkHboieVeYpLS/view?usp=sharing', 10, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'arrivee-d-eau', 'Arrivée d''eau', 'Water supply', '💧', 'bg-cyan-50', 'text-cyan-600', 'https://drive.google.com/file/d/1Ku-w2REVGjRhLwHeo8zhtRI6FCxQRMZG/view?usp=sharing', 11, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'compteur-electrique', 'Compteur électrique', 'Electric meter', '⚡', 'bg-yellow-50', 'text-yellow-500', 'https://drive.google.com/file/d/1OM1T0sdQxfdB6oetBgvhh2-5eriO-Qfm/view?usp=sharing', 12, 1);

-- ----------------------------------------------------------------------
-- Logement : Berthelot Design (berthelot-design)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('berthelot-design', 'Berthelot Design', 'https://drive.google.com/file/d/1zAdHHdK939qBsxs6niy96GhEoi5igaWA/view?usp=sharing', 'https://drive.google.com/file/d/19qN3w97vKEcfh-4_oo0SWAob9AoYfTb1/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Chez Denise Studio Cosy (chez-denise-studio-cosy)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('chez-denise-studio-cosy', 'Chez Denise Studio Cosy', 'https://drive.google.com/file/d/1jPpSFf6ZJ_pNWGhgeQ6yvA4d7v8GJ2Ks/view?usp=sharing', 'https://drive.google.com/file/d/14imaiZf6qakRhp1L-lkmFngWhca1TA9U/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Cosy et Design (cosy-et-design)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('cosy-et-design', 'Cosy et Design', 'https://drive.google.com/file/d/1ASzg5BjfSsfURyAXBcxZHSryOCvwQD8R/view?usp=sharing', 'https://drive.google.com/file/d/1OZG5-21emm2pyTbr1syqkCsM1K08a6pe/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : L’Atelier Cosy Félix (latelier-cosy-felix)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('latelier-cosy-felix', 'L’Atelier Cosy Félix', 'https://drive.google.com/file/d/1lnFUEqcgk4kqtYBTBP8X41TIczoLGOIM/view?usp=sharing', 'https://drive.google.com/file/d/1GWjlRxHxJ_OAL29Lr79XtaIigAAtwU4H/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Maisonnette en Jouhet (maisonnette-en-jouhet)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('maisonnette-en-jouhet', 'Maisonnette en Jouhet', 'https://drive.google.com/file/d/1gr3S2qp1ViC8DQpRMDSct68XuN95yiEV/view?usp=sharing', 'https://drive.google.com/file/d/1nif9dnZykNjFuMi_VnRtaS0ZHudNQGT2/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();
-- Vidéos pour Maisonnette en Jouhet
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-laver', 'Machine à laver', 'Washing machine', '🫧', 'bg-blue-50', 'text-blue-500', 'https://drive.google.com/file/d/1AMmACiyOT33mBu6jpC2GqiJ5Di4JzgwY/view?usp=drive_link', 1, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'plaque-a-induction', 'Plaque à induction', 'Induction hob', '🍳', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1PWxV0_N8eC51SmYEj41ewvemumiQ_ME-/view?usp=drive_link', 2, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'micro-ondes', 'Micro-ondes', 'Microwave', '♨️', 'bg-gray-100', 'text-gray-600', 'https://drive.google.com/file/d/1tqGn4vEC1fKPCqNxMuMcsmNqfh0MNZH2/view?usp=sharing', 3, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'bouilloire', 'Bouilloire', 'Kettle', '🫖', 'bg-teal-50', 'text-teal-500', 'https://drive.google.com/file/d/1nefezeosACVoJTYxtEuNKUc8CWZcuAI9/view?usp=drive_link', 4, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'television', 'Télévision', 'Television', '📺', 'bg-zinc-100', 'text-zinc-600', 'https://drive.google.com/file/d/1n5jEyHAm1rY8hlQcM_VImINSSLC7YPM5/view?usp=drive_link', 5, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-cafe', 'Machine à café', 'Coffee maker', '☕', 'bg-amber-50', 'text-amber-600', 'https://drive.google.com/file/d/10D-k9fEciB5_SB875CsE5UYGRNAmK7ov/view?usp=drive_link', 6, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'canape-convertible', 'Canapé convertible', 'Sofa bed', '🛋️', 'bg-rose-50', 'text-rose-500', 'https://drive.google.com/file/d/1pFyrtDEFNFt6xHlmir5cgSPIZk5eb6Ym/view?usp=drive_link', 7, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'radiateur', 'Radiateur', 'Heater / radiator', '🌡️', 'bg-red-50', 'text-red-500', 'https://drive.google.com/file/d/1rUFkmx_L_em1rvh59WjSanP92oi7pDW5/view?usp=drive_link', 8, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'seche-serviettes', 'Sèche-serviettes', 'Towel radiator', '🛁', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1A522PfPcQBgC_shcxkK8Y58WR1KxPKEL/view?usp=drive_link', 9, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'hotte-aspirante', 'Hotte aspirante', 'Extractor hood', '💨', 'bg-slate-100', 'text-slate-600', 'https://drive.google.com/file/d/1JuQUDcMUp0Z3UJCw8uLBcKriWn7xm6kV/view?usp=drive_link', 10, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'grille-pain', 'Grille-pain', 'Toaster', '🍞', 'bg-yellow-50', 'text-yellow-600', 'https://drive.google.com/file/d/1N37VZamdg3e-niSANO48Ap91GwUtwd_2/view?usp=drive_link', 11, 1);

-- ----------------------------------------------------------------------
-- Logement : Marietton Cosy (marietton-cosy)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('marietton-cosy', 'Marietton Cosy', 'https://drive.google.com/file/d/1Rtt8vDKmuxCxvkeqP5Pa_YeVrvIptAKT/view?usp=sharing', 'https://drive.google.com/file/d/13GuGwXIq--6wwWdrfhR17G6_F0lPTIsH/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Studio Cosy de Mel (studio-cosy-mel)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('studio-cosy-mel', 'Studio Cosy de Mel', 'https://drive.google.com/file/d/191L29MG_TkTZxRAeu5ONPDtZmSbFvVVy/view?usp=drive_link', 'https://drive.google.com/file/d/1HHUxCYmKHK0DG8sJnDNUhlUhcydHnH13/view?usp=drive_link', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Studio Cosy de Paul (studio-cosy-paul)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('studio-cosy-paul', 'Studio Cosy de Paul', 'https://drive.google.com/file/d/1Ux23AYhTgZSg_CebooeSHSsQIQXgwQ6G/view?usp=sharing', 'https://drive.google.com/file/d/1pcsmBsUj7K4v1T93hsbBY2d7bGVnOzUU/view?usp=drive_link', 1);
SET @last_prop_id = LAST_INSERT_ID();


-- ======================================================================
-- DONNÉES DE STATIONNEMENT (PARKING)
-- ======================================================================

SET @prop_id = (SELECT id FROM properties WHERE slug IN ('atelier-cosy-felix', 'latelier-cosy-felix') LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Les meilleures solutions pour vous garer sereinement autour du 234 avenue Félix Faure (Lyon 3ᵉ).', 'The best parking options around 234 avenue Félix Faure (Lyon 3rd arr.).', 'Respectez scrupuleusement les emplacements autorisés et les panneaux de signalisation. Le régime de stationnement peut varier selon le côté ou la portion de rue.', 'Please strictly follow authorized parking spots and local signs. Regulations can vary depending on the side or section of the street.', 'Vérifiez toujours la signalisation sur place avant de quitter votre véhicule. Les places gratuites ne sont pas réservables.', 'Always verify signs on-site before leaving your car. Free parking spots cannot be reserved.', 'L''avenue Félix Faure et les rues voisines sont payantes. Paiement conseillé via l''application EasyPark.', 'Avenue Félix Faure and surrounding streets have paid parking. We recommend using the EasyPark mobile app.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Stationnement gratuit sous condition d''effectuer un aller-retour sur le réseau TCL avec un titre validé le jour même (présentation obligatoire à la borne de sortie). Sans validation : forfait 20 €. Stationnement de nuit strictement interdit.', 'Free parking when completing a round-trip on the TCL transit network with a same-day validated ticket (scan required at exit gate). Without validation: €20 flat fee. Overnight parking is strictly prohibited.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue de l''Est', 'Rue de l''Est', 'Rue de l''Est, Lyon 3e', 'Environ 10 min à pied', 'Approx. 10 min walk', NULL, NULL, 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Jules Verne', 'Rue Jules Verne', 'Rue Jules Verne, Lyon 3e', 'Environ 12 min à pied', 'Approx. 12 min walk', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Avenue des Acacias', 'Avenue des Acacias', 'Avenue des Acacias, Lyon 3e', 'Environ 14 min à pied', 'Approx. 14 min walk', NULL, NULL, 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Bonnefond', 'Rue Bonnefond', 'Rue Bonnefond, Lyon 3e', 'Environ 16 min à pied', 'Approx. 16 min walk', NULL, NULL, 4, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Ferrandière / Rue de la Cité', 'Zenpark Car Park - Ferrandière / Rue de la Cité', 'Zenpark', '39 rue de la Cité, Lyon 3e', '10 min à pied (4 min vélo)', '10 min walk (4 min bike)', '2,00 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '20 € / journée', '€20 / day', 'Tous véhicules jusqu''à 2,00 m', 'All vehicles up to 2.00 m', 'https://zenpark.com', 'Parking couvert sécurisé. Réservation préalable requise sur l''app Zenpark.', 'Secured covered car park. Pre-booking required via the Zenpark app.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Sans Souci / Dauphiné', 'Zenpark Car Park - Sans Souci / Dauphiné', 'Zenpark', '34 rue Jeanne-Hachette, Lyon 3e', '16 min à pied (11 min en transports)', '16 min walk (11 min by transit)', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '22 € / journée', '€22 / day', 'Véhicules jusqu''à 1,90 m', 'Vehicles up to 1.90 m', 'https://zenpark.com', 'Parking souterrain sécurisé réservable sur Zenpark.', 'Secured underground parking bookable via Zenpark.', 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Mermoz-Pinel', 'TCL Park & Ride - Mermoz-Pinel', '3 rue Lionel-Terray, 69500 Bron', 'Environ 26 min en TCL', 'Approx. 26 min by transit', '416 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am', 'Prendre le Métro D jusqu''à Grange Blanche, puis le bus C13 jusqu''à l''arrêt Dauphiné-Lacassagne (~26 min).', 'Take Metro D to Grange Blanche, then bus C13 to Dauphiné-Lacassagne stop (~26 min).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, 69100 Villeurbanne', 'Environ 26 min en TCL', 'Approx. 26 min by transit', '655 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', 'Prendre le Métro A vers Vaulx-en-Velin La Soie, puis le Tram T3 jusqu''à Dauphiné-Lacassagne (~26 min).', 'Take Metro A towards Vaulx-en-Velin La Soie, then Tram T3 to Dauphiné-Lacassagne (~26 min).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);

-- ----------------------------------------------------------------------
-- Stationnement : berthelot-design
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug = 'berthelot-design' LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Guide des solutions de stationnement autour du 313 avenue Berthelot (Lyon 8ᵉ).', 'Parking solutions guide around 313 avenue Berthelot (Lyon 8th arr.).', 'Respectez les emplacements autorisés et la signalisation. Vérifiez toujours les panneaux et l''application EasyPark avant de stationner.', 'Please follow authorized parking bays and signs. Always double-check signs and the EasyPark app before parking.', 'Places non garanties, réglementation variable selon les tronçons de rue.', 'Spaces are not guaranteed, rules vary along street sections.', 'Rues payantes : Marius-Berliet et Paul-Cazeneuve (au nord de Berthelot). Paiement avec EasyPark.', 'Paid streets: Marius-Berliet and Paul-Cazeneuve (north of Berthelot). Payment via EasyPark app.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (titre à scanner à la borne de sortie). Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket (ticket scanned at exit gate). €20 if unvalidated. No overnight parking.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Paul-Cazeneuve (au sud de l''av. Berthelot)', 'Rue Paul-Cazeneuve (south of Av. Berthelot)', 'Rue Paul-Cazeneuve, Lyon 8e', 'Environ 6 min à pied', 'Approx. 6 min walk', NULL, NULL, 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Maryse-Bastié', 'Rue Maryse-Bastié', 'Rue Maryse-Bastié, Lyon 8e', 'Environ 13 min à pied', 'Approx. 13 min walk', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Bataille', 'Rue Bataille', 'Rue Bataille, Lyon 8e', 'Environ 15 min à pied', 'Approx. 15 min walk', NULL, NULL, 3, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Villon / Mairie du 8e', 'Zenpark Car Park - Villon / Town Hall 8th', 'Zenpark', '17 rue de la Rosière, Lyon 8e', '2 min à pied (1 min vélo)', '2 min walk (1 min bike)', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '2h : 4 € · 4h : 6 € · 12h-24h : 16 € · 48h : 26 € · 72h : 38 €', '2h: €4 · 4h: €6 · 12h-24h: €16 · 48h: €26 · 72h: €38', 'Véhicules jusqu''à 1,90 m', 'Vehicles up to 1.90 m', 'https://zenpark.com', 'Parking souterrain sécurisé à 2 pas du logement.', 'Secured underground car park just 2 steps from property.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Mairie Lyon 8', 'Zenpark Car Park - Town Hall Lyon 8', 'Zenpark', 'Voiture : 87 rue Villon / Piéton : 291 av. Berthelot, Lyon 8e', '3 min à pied (1 min vélo)', '3 min walk (1 min bike)', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '4h : 6 € · 12h-24h : 16 € · 48h : 24 € · 72h : 35 € (créneau min. 4h)', '4h: €6 · 12h-24h: €16 · 48h: €24 · 72h: €35 (min. 4h booking)', 'Petites et moyennes voitures (gabarit compact)', 'Small and medium cars (compact size)', 'https://zenpark.com', 'Souterrain sécurisé. Créneau minimum de 4h appliqué par l''application.', 'Secured underground. Minimum 4-hour booking applies.', 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Mermoz-Pinel', 'TCL Park & Ride - Mermoz-Pinel', '3 rue Lionel-Terray, 69500 Bron', 'Environ 15 min en TCL', 'Approx. 15 min by transit', '416 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h (Fermé 1er mai)', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed May 1)', 'Prendre le Bus C15 direction Bachut - Mairie du 8e, puis rejoindre le logement à pied (~15 min).', 'Take Bus C15 towards Bachut - Mairie du 8e, then walk to the apartment (~15 min).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);

-- ----------------------------------------------------------------------
-- Stationnement : chez-denise-studio-cosy
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug = 'chez-denise-studio-cosy' LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Guide du stationnement pratique autour du 75 rue du Dauphiné (Lyon 3ᵉ).', 'Practical parking guide around 75 rue du Dauphiné (Lyon 3rd arr.).', 'Respectez les emplacements autorisés et la signalisation. Vérifiez l''application EasyPark avant de quitter votre véhicule.', 'Please follow authorized parking bays and signs. Check the EasyPark app before leaving your car.', 'Places gratuites non garanties : vérifiez toujours les panneaux sur place.', 'Free spaces not guaranteed: always check street signs on site.', 'Rues au pied de l''immeuble et quartiers voisins payants (zones violettes sur EasyPark).', 'Streets in front of building and surrounding areas are paid (purple zones in EasyPark).', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (présentation du titre à la sortie). Sans titre : 20 €. Pas de stationnement nocturne.', 'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if not validated. No overnight parking.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Jules Verne', 'Rue Jules Verne', 'Rue Jules Verne, Lyon 3e', 'Environ 11 min à pied', 'Approx. 11 min walk', NULL, NULL, 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Bonnefond', 'Rue Bonnefond', 'Rue Bonnefond, Lyon 3e', 'Environ 13 min à pied', 'Approx. 13 min walk', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Avenue des Acacias', 'Avenue des Acacias', 'Avenue des Acacias, Lyon 3e', 'Environ 16 min à pied', 'Approx. 16 min walk', NULL, NULL, 3, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA&CO - Université Lyon 3 / Dauphiné', 'LPA&CO Car Park - Lyon 3 University / Dauphiné', 'LPA&CO', '15 rue du Dauphiné, Lyon 3e', '8 min à pied (5 min en bus)', '8 min walk (5 min bus)', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '2h : 2 € · 4h : 4 € · 12h : 8 € · 24h : 15 € · 48h : 21 € · 72h : 27 €', '2h: €2 · 4h: €4 · 12h: €8 · 24h: €15 · 48h: €21 · 72h: €27', 'Citadines, compactes, berlines et SUV', 'City cars, compacts, sedans and SUVs', 'https://www.lpa.fr', 'Parking couvert très proche et très économique. Réservation sur l''app LPA&CO.', 'Close and affordable covered car park. Book via LPA&CO app.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA&CO - Part-Dieu / Félix Faure', 'LPA&CO Car Park - Part-Dieu / Félix Faure', 'LPA&CO', '29 rue Danton, Lyon 3e', '15 min à pied (9 min en transports)', '15 min walk (9 min by transit)', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '2h : 3 € · 4h : 6 € · 12h : 12 € · 24h : 24 € · 48h : 30 € · 72h : 40 €', '2h: €3 · 4h: €6 · 12h: €12 · 24h: €24 · 48h: €30 · 72h: €40', 'Citadines, berlines, SUV, monospaces et vélos', 'City cars, sedans, SUVs, minivans and bicycles', 'https://www.lpa.fr', 'Parking couvert sécurisé. Réservation sur l''app LPA&CO.', 'Secured covered car park. Book via LPA&CO app.', 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Mermoz-Pinel', 'TCL Park & Ride - Mermoz-Pinel', '3 rue Lionel-Terray, 69500 Bron', 'Environ 25 min en TCL', 'Approx. 25 min by transit', '416 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am', 'Prendre le Métro D jusqu''à Grange Blanche, puis le bus C13 jusqu''à Dauphiné-Lacassagne (~25 min).', 'Take Metro D to Grange Blanche, then bus C13 to Dauphiné-Lacassagne (~25 min).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', 'Le niveau 3 est réservé aux abonnés Premium.', 'Level 3 is reserved for Premium subscribers.', 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, 69100 Villeurbanne', 'Environ 30 min en TCL', 'Approx. 30 min by transit', '655 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', 'Prendre le Métro A vers La Soie, puis le Tram T3 jusqu''à Dauphiné-Lacassagne (à 2 pas du 75 rue du Dauphiné).', 'Take Metro A to La Soie, then Tram T3 to Dauphiné-Lacassagne (steps from 75 rue du Dauphiné).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);

-- ----------------------------------------------------------------------
-- Stationnement : cosy-et-design
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug = 'cosy-et-design' LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Solutions de stationnement pour votre séjour au 12 petite rue de la Viabert (Lyon 6ᵉ).', 'Parking options for your stay at 12 petite rue de la Viabert (Lyon 6th arr.).', 'Respectez scrupuleusement la signalisation présente sur place et les emplacements balisés.', 'Please strictly adhere to local street signs and marked parking bays.', 'Utilisez EasyPark pour repérer les rues gratuites (zones non surlignées en violet). Places non garanties.', 'Use EasyPark to identify free streets (not shaded purple). Spots cannot be guaranteed.', 'Places payantes : petite rue de la Viabert, rues des Charmettes, Bellecombe, Inkermann, Pétrequin et cours Lafayette.', 'Paid spots: petite rue de la Viabert, rue des Charmettes, Bellecombe, Inkermann, Pétrequin and cours Lafayette.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Gratuit sous réserve d''effectuer un aller-retour TCL avec titre validé le jour même. Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket. Without validation: €20 fee. Overnight parking forbidden.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue du Général-Dayan', 'Rue du Général-Dayan', 'Rue du Général-Dayan, Villeurbanne', 'Environ 14 min à pied (1 km)', 'Approx. 14 min walk (1 km)', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Louis-Malle', 'Rue Louis-Malle', 'Rue Louis-Malle, Villeurbanne', 'Environ 14 min à pied (1 km)', 'Approx. 14 min walk (1 km)', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Louis-Guérin', 'Rue Louis-Guérin', 'Rue Louis-Guérin, Villeurbanne', 'Environ 18 min à pied (1,3 km)', 'Approx. 18 min walk (1.3 km)', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Secteur Quartier Montchat', 'Montchat District Area', 'Rues Jules Verne, Louise, Cours Dr Long, Lyon 3e', 'Environ 25 min en transports', 'Approx. 25 min by transit', 'Secteur résidentiel entier gratuit 7j/7 et 24h/24.', 'Entire residential area free 24/7.', 4, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Métro Charpennes (Zenpark)', 'Charpennes Metro Car Park (Zenpark)', 'Zenpark', '8 rue d''Inkermann, 69100 Villeurbanne', 'Environ 5 à 8 min à pied', 'Approx. 5 to 8 min walk', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '4h : 8 € · 24h : 20 € · Semaine : 50 €', '4h: €8 · 24h: €20 · Week: €50', '2 roues, petits et moyens véhicules (entrée étroite, déconseillé gros SUV)', '2 wheels, small and mid-size cars (narrow entrance, not suited for big SUVs)', 'https://zenpark.com', 'Parking couvert très proche. Réservation préalable requise sur Zenpark.', 'Very close covered parking. Advance reservation required on Zenpark.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking des Brotteaux (Q-Park)', 'Brotteaux Car Park (Q-Park)', 'Q-Park', 'Contre-allée Place Jules-Ferry, Lyon 6e', '11 min à pied (800 m)', '11 min walk (800 m)', '1,90 m', '24h/24, 7j/7 (accès direct ou réservation)', '24/7 (direct entry or pre-booking)', '1h : 3,60 € · 6h : 21,60 € · 24h : 47,70 € (tarifs web dégressifs)', '1h: €3.60 · 6h: €21.60 · 24h: €47.70 (discounted online rates available)', 'Tous véhicules · Bornes de recharge électrique', 'All vehicles · EV charging stations', 'https://www.q-park.fr/fr-fr/villes/lyon/brotteaux/', 'Grand parking public couvert moderne et sécurisé.', 'Large, modern, secure public underground car park.', 2, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parkings LPA&CO - Part-Dieu (Ney, Masséna, Vauban)', 'LPA&CO Car Parks - Part-Dieu (Ney, Masséna, Vauban)', 'LPA&CO', '94 rue Ney / 102 rue Masséna / 106 rue Vauban, Lyon 6e', 'Environ 15 min à pied', 'Approx. 15 min walk', '1,80 m à 1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '2h : 3 € · 4h : 6 € · 12h : 12 € · 24h : 24 € · 48h : 30 € · 72h : 40 €', '2h: €3 · 4h: €6 · 12h: €12 · 24h: €24 · 48h: €30 · 72h: €40', 'Tous types de véhicules selon parking', 'All vehicle types depending on lot', 'https://www.lpa.fr', '3 parkings couverts à tarifs très compétitifs via l''application LPA&CO.', '3 covered car parks with great competitive rates via LPA&CO app.', 3, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, Villeurbanne', 'Environ 15-20 min en TCL', 'Approx. 15-20 min by transit', '657 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sunday)', 'Métro A direct jusqu''à Charpennes - Charles-Hernu, puis 6 à 8 min à pied jusqu''au logement.', 'Direct Metro A to Charpennes - Charles-Hernu, then 6-8 min walk to the apartment.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Vaulx-en-Velin La Soie', 'TCL Park & Ride - Vaulx-en-Velin La Soie', '3 rue Maurice-Moissonnier, Vaulx-en-Velin', 'Environ 20 min en TCL', 'Approx. 20 min by transit', '474 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am', 'Métro A direct jusqu''à Charpennes, puis 6 à 8 min à pied.', 'Direct Metro A to Charpennes, then 6 to 8 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Feyssine', 'TCL Park & Ride - Feyssine', '62 boulevard Niels-Bohr, Villeurbanne', 'Environ 20 min en TCL', 'Approx. 20 min by transit', '80 places', 'Tous les jours de 4h30 à 1h', 'Every day from 4:30am to 1am', 'Tram T1 ou T4 jusqu''à Charpennes, puis 6 à 8 min à pied.', 'Tram T1 or T4 to Charpennes, then 6 to 8 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 3, 1);

-- ----------------------------------------------------------------------
-- Stationnement : bataille
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug = 'bataille' LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Solutions complètes de stationnement autour du 91 rue Bataille (Lyon 8ᵉ).', 'Complete parking solutions around 91 rue Bataille (Lyon 8th arr.).', 'Respectez les emplacements autorisés et la signalisation. La rue Bataille offre de nombreuses places gratuites directement au pied de l''immeuble.', 'Please respect authorized parking spots and signage. Rue Bataille offers many free spaces right outside the building.', 'Stationnement gratuit très avantageux dans ce quartier. Utilisez EasyPark pour vérifier la gratuité (rues non violettes).', 'Very convenient free parking in this area. Use EasyPark to confirm free status (streets not shaded purple).', 'Certaines rues des quartiers voisins sont payantes. Paiement via l''application EasyPark.', 'Some streets in neighboring quarters have paid parking. EasyPark mobile app accepted.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (présentation obligatoire à la sortie). Sans validation : 20 €. Pas de stationnement nocturne.', 'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if not validated. Overnight parking forbidden.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Bataille', 'Rue Bataille', 'Rue Bataille, Lyon 8e', 'Au pied direct de l''immeuble', 'Directly in front of the building', 'Stationnement gratuit dans la rue.', 'Free street parking.', 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Maryse-Bastié', 'Rue Maryse-Bastié', 'Rue Maryse-Bastié, Lyon 8e', 'Environ 2 min à pied (150 m)', 'Approx. 2 min walk (150 m)', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Jacqueline-Auriol', 'Rue Jacqueline-Auriol', 'Rue Jacqueline-Auriol, Lyon 8e', 'Environ 4 min à pied (280 m)', 'Approx. 4 min walk (280 m)', NULL, NULL, 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Jean-d''Esparnet (Desparmet)', 'Rue Jean-d''Esparnet (Desparmet)', 'Rue Jean-d''Esparnet, Lyon 8e', 'Environ 5 min à pied (400 m)', 'Approx. 5 min walk (400 m)', NULL, NULL, 4, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue du Professeur-Morat', 'Rue du Professeur-Morat', 'Rue du Professeur-Morat, Lyon 8e', 'Environ 8 min à pied (600 m)', 'Approx. 8 min walk (600 m)', NULL, NULL, 5, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Thomas-Blanchet', 'Rue Thomas-Blanchet', 'Rue Thomas-Blanchet, Lyon 8e', 'Environ 9 min à pied (650 m)', 'Approx. 9 min walk (650 m)', NULL, NULL, 6, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Hôpital Privé Jean-Mermoz', 'Jean-Mermoz Private Hospital Car Park', 'Privé / Indigo', 'Entrée rue Jacqueline-Auriol, Lyon 8e', 'Au pied du logement (1 min à pied)', 'At the foot of the building (1 min walk)', NULL, 'Ouvert 24h/24 et 7j/7', 'Open 24/7', '15 min gratuites · 3,20 €/h (8h-20h) · 3 €/h (20h-8h) · Journée max 37,60 €', '15 min free · €3.20/h (8am-8pm) · €3/h (8pm-8am) · Max day €37.60', 'Voitures, 2 roues, places PMR', 'Cars, 2-wheelers, PRM spaces', NULL, 'Idéal pour décharger les bagages ou arrêt minute grâce aux 15 premières minutes offertes.', 'Ideal for unloading luggage with the first 15 minutes free.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark/Yespark - 82 rue Feuillat', 'Zenpark/Yespark Car Park - 82 rue Feuillat', 'Zenpark / Yespark', '82 rue Feuillat, Lyon 8e', 'Environ 12 min à pied', 'Approx. 12 min walk', '1,90 m', '24h/24, 7j/7 (sur réservation)', '24/7 (reservation required)', '1h : 2,50 € · 24h : 16 € · Semaine : 81 €', '1h: €2.50 · 24h: €16 · Week: €81', NULL, NULL, 'https://zenpark.com', 'Parking couvert sécurisé, réservation à l''heure ou à la journée.', 'Secured covered car park, bookable by the hour or day.', 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Mermoz-Pinel', 'TCL Park & Ride - Mermoz-Pinel', '3 rue Lionel-Terray, 69500 Bron', 'Environ 15 min en TCL (ou 22 min à pied)', 'Approx. 15 min by transit (or 22 min walk)', '416 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h · Dim : 4h30-1h', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am · Sun: 4:30am-1am', 'Métro D jusqu''à Grange-Blanche, puis rejoindre le logement à pied ou en bus (~15 min).', 'Take Metro D to Grange-Blanche, then walk or take bus to apartment (~15 min).', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);

-- ----------------------------------------------------------------------
-- Stationnement : marietton-cosy
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug = 'marietton-cosy' LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Solutions de stationnement pour votre séjour au 16 rue Marietton (Lyon 9ᵉ).', 'Parking options for your stay at 16 rue Marietton (Lyon 9th arr.).', '⚠️ RÈGLE STRICTE : Stationnement formellement INTERDIT dans la cour de l''immeuble. Merci de stationner dans les rues gratuites ou parkings répertoriés ci-dessous.', '⚠️ STRICT RULE: Parking is strictly PROHIBITED inside the building courtyard. Please park in the free streets or car parks listed below.', 'Plusieurs rues à proximité directe offrent le stationnement gratuit 7j/7 et 24h/24.', 'Several nearby streets offer completely free parking 24/7.', 'Emplacements payants sur voirie autour du logement, gérables avec EasyPark.', 'Paid on-street bays around the apartment, manageable with EasyPark.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche et jours fériés)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)', '• Citadines & Électriques : 1 €/h · 35 €/10h
• Standards : 2 €/h · 55 €/10h
• Gros modèles & SUV : 3,50 €/h · 80 €/10h', '• City cars & EV: €1/h · €35/10h
• Standard: €2/h · €55/10h
• Large & SUV: €3.50/h · €80/10h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (présentation du titre à la sortie). Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket (scan at exit). €20 if not validated. Overnight parking forbidden.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Début de la rue de Bourgogne', 'Start of Rue de Bourgogne', 'Rue de Bourgogne, Lyon 9e', 'Environ 8 min à pied', 'Approx. 8 min walk', 'Gratuit tous les jours, toute la journée.', 'Free every day, all day.', 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue de la Gare', 'Rue de la Gare', 'Rue de la Gare, Lyon 9e', 'Environ 10 min à pied', 'Approx. 10 min walk', 'Gratuit tous les jours, toute la journée.', 'Free every day, all day.', 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Laure-Diebold', 'Rue Laure-Diebold', 'Rue Laure-Diebold, Lyon 9e', 'Environ 10 min à pied', 'Approx. 10 min walk', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Saint-Simon', 'Rue Saint-Simon', 'Rue Saint-Simon, Lyon 9e', 'Environ 11 min à pied', 'Approx. 11 min walk', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 4, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Berjon', 'Rue Berjon', 'Rue Berjon, Lyon 9e', 'Environ 11 min à pied', 'Approx. 11 min walk', 'Gratuit 7j/7 et 24h/24.', 'Free 24/7.', 5, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Public - Place Alfred Vanderpol', 'Public Car Park - Place Alfred Vanderpol', 'Public / EasyPark', 'Place Alfred Vanderpol, Lyon 9e', 'Environ 6 min à pied', 'Approx. 6 min walk', NULL, '24h/24, 7j/7', '24/7', NULL, NULL, NULL, NULL, NULL, 'Parking public en surface, gérable directement via l''application EasyPark.', 'Public surface car park, managed directly via EasyPark app.', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Privé Zenpark - Gare de Vaise / Studio Valmy', 'Zenpark Private Car Park - Gare de Vaise / Studio Valmy', 'Zenpark', '31 rue Tissot, 69009 Lyon', 'Environ 7 min à pied', 'Approx. 7 min walk', '1,90 m', NULL, NULL, '17 € / journée', '€17 / day', 'Réservé véhicules de petite ou moyenne taille (Twingo, 208, Golf). Gros SUV / monospaces interdits.', 'Restricted to small and mid-size cars (Twingo, 208, Golf). Large SUVs / minivans cannot enter.', 'https://zenpark.com', 'Parking souterrain sécurisé. Vérifier le gabarit avant de réserver.', 'Secured underground parking. Check vehicle dimensions before booking.', 2, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Gorge de Loup', 'TCL Park & Ride - Gorge de Loup', 'Rue de la Pépinière Royale, 69009 Lyon', NULL, NULL, '680 places (19 PMR)', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', 'Accès direct par le Métro D (station Gorge de Loup vers Valmy) ou 15 min de marche.', 'Direct access via Metro D (Gorge de Loup station to Valmy) or 15 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Vaise 2', 'TCL Park & Ride - Vaise 2', '53 rue de Saint-Cyr, 69009 Lyon', NULL, NULL, '748 places (18 PMR)', 'Lun-Jeu : 4h30-1h · Ven : 4h30-3h · Sam : 12h-3h (Fermé dim, fériés & été)', 'Mon-Thu: 4:30am-1am · Fri: 4:30am-3am · Sat: 12pm-3am (Closed Sun, holidays & summer)', 'Métro D (station Gare de Vaise vers Valmy) ou 15 min de marche.', 'Metro D (Gare de Vaise station to Valmy) or 15 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);

-- ----------------------------------------------------------------------
-- Stationnement : studio-cosy-de-mel
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug IN ('studio-cosy-de-mel', 'studio-cosy-mel') LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Guide stationnement pour votre séjour au 294 cours Émile-Zola (Villeurbanne). Fiche commune aux Studios Mel et Paul.', 'Parking guide for your stay at 294 cours Émile-Zola (Villeurbanne). Shared guide for Studios Mel and Paul.', 'Respectez les emplacements autorisés et la signalisation. Vérifiez l''application EasyPark avant de stationner.', 'Please follow authorized spots and signs. Check the EasyPark app before parking.', 'Places gratuites non garanties dans les rues adjacentes. Parking Super U disponible en face pour un court arrêt.', 'Free spaces not guaranteed in adjacent streets. Super U car park available opposite for short stops.', 'Stationnement payant à Villeurbanne réglementé dans le secteur.', 'Regulated on-street paid parking in Villeurbanne across the sector.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche, fériés ET tout le mois d''août ! 15 min gratuites matin et après-midi)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays, holidays AND throughout all August! 15 min free morning and afternoon)', '• Zone 1 : 2 € pour 1h, puis 4 €/h
• Zone 2 : 2,80 € pour 2h, puis 4,40 €/h', '• Zone 1: €2 for 1h, then €4/h
• Zone 2: €2.80 for 2h, then €4.40/h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (scan du titre à la sortie). Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if unvalidated. No overnight parking.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'spot', 'Parking du Super U (Arrêt ponctuel gratuit)', 'Super U Car Park (Short free stop)', '305 cours Émile-Zola, Villeurbanne', 'En face du logement (1 min à pied)', 'Opposite the apartment (1 min walk)', 'Gratuit pour un arrêt de courte durée ou déchargement de valises (selon conditions affichées).', 'Free for short stops or unloading luggage (subject to posted conditions).', 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard, Villeurbanne', 'Environ 5 min à pied', 'Approx. 5 min walk', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet, Villeurbanne', 'Environ 11 min à pied', 'Approx. 11 min walk', NULL, NULL, 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui, Villeurbanne', 'Environ 14 min à pied', 'Approx. 14 min walk', NULL, NULL, 4, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA - Hôtel de Ville Villeurbanne', 'LPA Car Park - Villeurbanne Town Hall', 'LPA', 'Voiture : 51 rue Racine / Piéton : 38 bis rue Michel-Servet, Villeurbanne', '18 min à pied (5 min vélo, 11 min métro)', '18 min walk (5 min bike, 11 min metro)', '1,90 m', 'Ouvert 24h/24 et 7j/7', 'Open 24/7', '0,80 € / 15 min · 2h : 6,40 € · 4h : 12,80 € · Plafond nuit (20h-9h) : 7,20 € · 24h : 49,60 €', '€0.80 / 15 min · 2h: €6.40 · 4h: €12.80 · Night cap (8pm-9am): €7.20 · 24h: €49.60', 'Tous véhicules jusqu''à 1,90 m', 'All vehicles up to 1.90 m', 'https://www.lpa.fr', 'Grand parking public couvert moderne. Plafond de nuit très avantageux (7,20 €).', 'Modern public covered car park. Great overnight capped rate (€7.20).', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA&CO - Rue Flachet', 'LPA&CO Car Park - Rue Flachet', 'LPA&CO', '30 rue Flachet, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', '1,90 m', NULL, NULL, 'Abonnement garage : 80 € / mois', 'Garage subscription: €80 / month', NULL, NULL, 'https://www.lpa.fr', 'Option intéressante pour les séjours de longue durée.', 'Great option for long-term stays.', 2, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Cusset', 'Zenpark Car Park - Cusset', 'Zenpark', '2 rue Victor-Subit, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', '1,90 m', '24h/24 (sur réservation)', '24/7 (reservation required)', 'Forfait mensuel : 101 € / mois', 'Monthly package: €101 / month', NULL, NULL, 'https://zenpark.com', 'Parking sécurisé avec accès 24h/24 via l''application Zenpark.', 'Secured parking with 24/7 smartphone access via Zenpark.', 3, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, 69100 Villeurbanne', 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', '655 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', 'Prendre le Métro A direction Perrache jusqu''à la station Flachet - Alain Gilles, puis 2 min à pied jusqu''au logement.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk to apartment.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Vaulx-en-Velin La Soie', 'TCL Park & Ride - Vaulx-en-Velin La Soie', '3 rue Maurice-Moissonner, 69120 Vaulx-en-Velin', 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', '474 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h (Fermé 1er mai)', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed May 1)', 'Prendre le Métro A direction Perrache jusqu''à Flachet - Alain Gilles, puis 2 min à pied.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);

-- ----------------------------------------------------------------------
-- Stationnement : studio-cosy-de-paul
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug IN ('studio-cosy-de-paul', 'studio-cosy-paul') LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en) VALUES (@prop_id, 'Guide stationnement pour votre séjour au 294 cours Émile-Zola (Villeurbanne). Fiche commune aux Studios Paul et Mel.', 'Parking guide for your stay at 294 cours Émile-Zola (Villeurbanne). Shared guide for Studios Paul and Mel.', 'Respectez les emplacements autorisés et la signalisation. Vérifiez l''application EasyPark avant de stationner.', 'Please follow authorized spots and signs. Check the EasyPark app before parking.', 'Places gratuites non garanties dans les rues adjacentes. Parking Super U disponible en face pour un court arrêt.', 'Free spaces not guaranteed in adjacent streets. Super U car park available opposite for short stops.', 'Stationnement payant à Villeurbanne réglementé dans le secteur.', 'Regulated on-street paid parking in Villeurbanne across the sector.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche, fériés ET tout le mois d''août ! 15 min gratuites matin et après-midi)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays, holidays AND throughout all August! 15 min free morning and afternoon)', '• Zone 1 : 2 € pour 1h, puis 4 €/h
• Zone 2 : 2,80 € pour 2h, puis 4,40 €/h', '• Zone 1: €2 for 1h, then €4/h
• Zone 2: €2.80 for 2h, then €4.40/h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (scan du titre à la sortie). Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if unvalidated. No overnight parking.');
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'spot', 'Parking du Super U (Arrêt ponctuel gratuit)', 'Super U Car Park (Short free stop)', '305 cours Émile-Zola, Villeurbanne', 'En face du logement (1 min à pied)', 'Opposite the apartment (1 min walk)', 'Gratuit pour un arrêt de courte durée ou déchargement de valises (selon conditions affichées).', 'Free for short stops or unloading luggage (subject to posted conditions).', 1, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard, Villeurbanne', 'Environ 5 min à pied', 'Approx. 5 min walk', NULL, NULL, 2, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet, Villeurbanne', 'Environ 11 min à pied', 'Approx. 11 min walk', NULL, NULL, 3, 1);
INSERT INTO parking_free_spots (property_id, spot_type, name_fr, name_en, address, walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'street', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui, Villeurbanne', 'Environ 14 min à pied', 'Approx. 14 min walk', NULL, NULL, 4, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA - Hôtel de Ville Villeurbanne', 'LPA Car Park - Villeurbanne Town Hall', 'LPA', 'Voiture : 51 rue Racine / Piéton : 38 bis rue Michel-Servet, Villeurbanne', '18 min à pied (5 min vélo, 11 min métro)', '18 min walk (5 min bike, 11 min metro)', '1,90 m', 'Ouvert 24h/24 et 7j/7', 'Open 24/7', '0,80 € / 15 min · 2h : 6,40 € · 4h : 12,80 € · Plafond nuit (20h-9h) : 7,20 € · 24h : 49,60 €', '€0.80 / 15 min · 2h: €6.40 · 4h: €12.80 · Night cap (8pm-9am): €7.20 · 24h: €49.60', 'Tous véhicules jusqu''à 1,90 m', 'All vehicles up to 1.90 m', 'https://www.lpa.fr', 'Grand parking public couvert moderne. Plafond de nuit très avantageux (7,20 €).', 'Modern public covered car park. Great overnight capped rate (€7.20).', 1, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking LPA&CO - Rue Flachet', 'LPA&CO Car Park - Rue Flachet', 'LPA&CO', '30 rue Flachet, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', '1,90 m', NULL, NULL, 'Abonnement garage : 80 € / mois', 'Garage subscription: €80 / month', NULL, NULL, 'https://www.lpa.fr', 'Option intéressante pour les séjours de longue durée.', 'Great option for long-term stays.', 2, 1);
INSERT INTO parking_private_lots (property_id, name_fr, name_en, operator, address, walking_time_fr, walking_time_en, max_height, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Zenpark - Cusset', 'Zenpark Car Park - Cusset', 'Zenpark', '2 rue Victor-Subit, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', '1,90 m', '24h/24 (sur réservation)', '24/7 (reservation required)', 'Forfait mensuel : 101 € / mois', 'Monthly package: €101 / month', NULL, NULL, 'https://zenpark.com', 'Parking sécurisé avec accès 24h/24 via l''application Zenpark.', 'Secured parking with 24/7 smartphone access via Zenpark.', 3, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, 69100 Villeurbanne', 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', '655 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', 'Prendre le Métro A direction Perrache jusqu''à la station Flachet - Alain Gilles, puis 2 min à pied jusqu''au logement.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk to apartment.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_tcl_prs (property_id, name_fr, name_en, address, transit_time_fr, transit_time_en, capacity, opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'Parking Relais TCL - Vaulx-en-Velin La Soie', 'TCL Park & Ride - Vaulx-en-Velin La Soie', '3 rue Maurice-Moissonner, 69120 Vaulx-en-Velin', 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', '474 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h (Fermé 1er mai)', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed May 1)', 'Prendre le Métro A direction Perrache jusqu''à Flachet - Alain Gilles, puis 2 min à pied.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk.', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);


-- ------------------------------------------------------------------------------
-- 3. TABLES D'ADMINISTRATION & SÉCURITÉ (BACK-OFFICE)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('superadmin', 'admin', 'editor') NOT NULL DEFAULT 'admin',
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_users_email_active` (`email`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    INDEX `idx_admin_sessions_token` (`session_token`),
    INDEX `idx_admin_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` ENUM('login', 'logout', 'create', 'update', 'delete', 'toggle') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `entity_label` VARCHAR(255) DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
    INDEX `idx_admin_logs_user_date` (`user_id`, `created_at`),
    INDEX `idx_admin_logs_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. COMPTE SUPERADMIN INITIAL
INSERT INTO `admin_users` (`email`, `password_hash`, `name`, `role`, `active`)
VALUES (
    'admin@carredhotes.fr',
    '$2y$12$9Ez4.NRo2swrilvYXlC9g.x/KObSyToWQcMe8xo/NXwp150kZjIhq',
    'Administrateur Carré d\'Hôtes',
    'superadmin',
    1
)
ON DUPLICATE KEY UPDATE `email` = `email`;

-- ----------------------------------------------------------------------
-- Stationnement : studio-cosy-de-paul
-- ----------------------------------------------------------------------
SET @prop_id = (SELECT id FROM properties WHERE slug IN ('studio-cosy-de-paul', 'studio-cosy-paul') LIMIT 1);
INSERT INTO property_parkings (property_id, intro_fr, intro_en, important_rule_fr, important_rule_en, free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en, street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en, street_parking_app, tcl_conditions_fr, tcl_conditions_en, tcl_notes_fr, tcl_notes_en) VALUES (@prop_id, 'Guide stationnement pour votre séjour au 294 cours Émile-Zola (Villeurbanne). Fiche commune aux Studios Paul et Mel.', 'Parking guide for your stay at 294 cours Émile-Zola (Villeurbanne). Shared guide for Studios Paul and Mel.', 'Respectez les emplacements autorisés et la signalisation. Vérifiez l''application EasyPark avant de stationner.', 'Please follow authorized spots and signs. Check the EasyPark app before parking.', 'Places gratuites non garanties dans les rues adjacentes. Parking Super U disponible en face pour un court arrêt.', 'Free spaces not guaranteed in adjacent streets. Super U car park available opposite for short stops.', 'Stationnement payant à Villeurbanne réglementé dans le secteur.', 'Regulated on-street paid parking in Villeurbanne across the sector.', 'Lun-Sam : 9h-19h (Gratuit nuit, dimanche, fériés ET tout le mois d''août ! 15 min gratuites matin et après-midi)', 'Mon-Sat: 9am-7pm (Free at night, on Sundays, holidays AND throughout all August! 15 min free morning and afternoon)', '• Zone 1 : 2 € pour 1h, puis 4 €/h
• Zone 2 : 2,80 € pour 2h, puis 4,40 €/h', '• Zone 1: €2 for 1h, then €4/h
• Zone 2: €2.80 for 2h, then €4.40/h', 'EasyPark', 'Gratuit avec aller-retour TCL validé le jour même (scan du titre à la sortie). Sans validation : 20 €. Stationnement nocturne interdit.', 'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if unvalidated. No overnight parking.', 'Parkings relais TCL Laurent Bonnevay et La Soie situés à 10-15 min en Métro A.', 'TCL Laurent Bonnevay and La Soie P+R lots reachable in 10-15 min via Metro A.');
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'free_spot', 'Parking du Super U (Arrêt ponctuel gratuit)', 'Super U Car Park (Short free stop)', '305 cours Émile-Zola, Villeurbanne', 'En face du logement (1 min à pied)', 'Opposite the apartment (1 min walk)', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Gratuit pour un arrêt de courte durée ou déchargement de valises (selon conditions affichées).', 'Free for short stops or unloading luggage (subject to posted conditions).', 1, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'free_street', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard', 'Rue Pierre-Cacard, Villeurbanne', 'Environ 5 min à pied', 'Approx. 5 min walk', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'free_street', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet', 'Rue du Docteur-Rollet, Villeurbanne', 'Environ 11 min à pied', 'Approx. 11 min walk', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'free_street', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui', 'Avenue Auguste-Blanqui, Villeurbanne', 'Environ 14 min à pied', 'Approx. 14 min walk', 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'private_parking', 'Parking LPA - Hôtel de Ville Villeurbanne', 'LPA Car Park - Villeurbanne Town Hall', 'Voiture : 51 rue Racine / Piéton : 38 bis rue Michel-Servet, Villeurbanne', '18 min à pied (5 min vélo, 11 min métro)', '18 min walk (5 min bike, 11 min metro)', 18, NULL, NULL, '1,90 m', NULL, 'Ouvert 24h/24 et 7j/7', 'Open 24/7', '0,80 € / 15 min · 2h : 6,40 € · 4h : 12,80 € · Plafond nuit (20h-9h) : 7,20 € · 24h : 49,60 €', '€0.80 / 15 min · 2h: €6.40 · 4h: €12.80 · Night cap (8pm-9am): €7.20 · 24h: €49.60', 'Tous véhicules jusqu''à 1,90 m', 'All vehicles up to 1.90 m', NULL, NULL, 'LPA', 'https://www.lpa.fr', 'Grand parking public couvert moderne. Plafond de nuit très avantageux (7,20 €).', 'Modern public covered car park. Great overnight capped rate (€7.20).', 1, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'private_parking', 'Parking LPA&CO - Rue Flachet', 'LPA&CO Car Park - Rue Flachet', '30 rue Flachet, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 5, NULL, NULL, '1,90 m', NULL, NULL, NULL, 'Abonnement garage : 80 € / mois', 'Garage subscription: €80 / month', NULL, NULL, NULL, NULL, 'LPA&CO', 'https://www.lpa.fr', 'Option intéressante pour les séjours de longue durée.', 'Great option for long-term stays.', 2, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'private_parking', 'Parking Zenpark - Cusset', 'Zenpark Car Park - Cusset', '2 rue Victor-Subit, 69100 Villeurbanne', 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 5, NULL, NULL, '1,90 m', NULL, '24h/24 (sur réservation)', '24/7 (reservation required)', 'Forfait mensuel : 101 € / mois', 'Monthly package: €101 / month', NULL, NULL, NULL, NULL, 'Zenpark', 'https://zenpark.com', 'Parking sécurisé avec accès 24h/24 via l''application Zenpark.', 'Secured parking with 24/7 smartphone access via Zenpark.', 3, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'tcl_pr', 'Parking Relais TCL - Laurent Bonnevay', 'TCL Park & Ride - Laurent Bonnevay', '419 cours Émile-Zola, 69100 Villeurbanne', NULL, NULL, NULL, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', NULL, '655 places', 'Lun-Jeu : 4h30-1h · Ven-Sam : 4h30-3h (Fermé dimanche & fériés)', 'Mon-Thu: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed Sun & holidays)', NULL, NULL, NULL, NULL, 'Prendre le Métro A direction Perrache jusqu''à la station Flachet - Alain Gilles, puis 2 min à pied jusqu''au logement.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk to apartment.', 'TCL', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 1, 1);
INSERT INTO parking_locations (property_id, category, name_fr, name_en, address, walking_time_fr, walking_time_en, walking_minutes, transit_time_fr, transit_time_en, max_height, capacity, opening_hours_fr, opening_hours_en, rates_fr, rates_en, suitable_vehicles_fr, suitable_vehicles_en, how_to_reach_fr, how_to_reach_en, operator, booking_url, notes_fr, notes_en, display_order, active) VALUES (@prop_id, 'tcl_pr', 'Parking Relais TCL - Vaulx-en-Velin La Soie', 'TCL Park & Ride - Vaulx-en-Velin La Soie', '3 rue Maurice-Moissonner, 69120 Vaulx-en-Velin', NULL, NULL, NULL, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', NULL, '474 places', 'Lun-Jeu & Dim : 4h30-1h · Ven-Sam : 4h30-3h (Fermé 1er mai)', 'Mon-Thu & Sun: 4:30am-1am · Fri-Sat: 4:30am-3am (Closed May 1)', NULL, NULL, NULL, NULL, 'Prendre le Métro A direction Perrache jusqu''à Flachet - Alain Gilles, puis 2 min à pied.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk.', 'TCL', 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures', NULL, NULL, 2, 1);

-- ------------------------------------------------------------------------------
-- 3. TABLES D'ADMINISTRATION & SÉCURITÉ (BACK-OFFICE)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('superadmin', 'admin', 'editor') NOT NULL DEFAULT 'admin',
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_users_email_active` (`email`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    INDEX `idx_admin_sessions_token` (`session_token`),
    INDEX `idx_admin_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` ENUM('login', 'logout', 'create', 'update', 'delete', 'toggle') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `entity_label` VARCHAR(255) DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
    INDEX `idx_admin_logs_user_date` (`user_id`, `created_at`),
    INDEX `idx_admin_logs_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. COMPTE SUPERADMIN INITIAL
INSERT INTO `admin_users` (`email`, `password_hash`, `name`, `role`, `active`)
VALUES (
    'admin@carredhotes.fr',
    '$2y$12$9Ez4.NRo2swrilvYXlC9g.x/KObSyToWQcMe8xo/NXwp150kZjIhq',
    'Administrateur Carré d\'Hôtes',
    'superadmin',
    1
)
ON DUPLICATE KEY UPDATE `email` = `email`;
