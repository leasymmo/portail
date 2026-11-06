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
    `tcl_notes_fr` TEXT DEFAULT NULL COMMENT 'Notes complémentaires P+R TCL (FR)',
    `tcl_notes_en` TEXT DEFAULT NULL COMMENT 'Notes complémentaires P+R TCL (EN)',
    `pdf_fr_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien vers la fiche PDF FR',
    `pdf_en_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien vers la fiche PDF EN',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_property_parkings_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_locations (Lieux détaillés : rues gratuites, garages, P+R)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_locations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers la table properties',
    `category` ENUM('free_street', 'free_spot', 'private_parking', 'tcl_pr') NOT NULL COMMENT 'Type d''emplacement',
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue / parking (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue / parking (EN)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise du parking',
    `walking_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (FR)',
    `walking_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied (EN)',
    `walking_minutes` INT UNSIGNED DEFAULT NULL COMMENT 'Minutes à pied pour le tri',
    `transit_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports (FR)',
    `transit_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports (EN)',
    `max_height` VARCHAR(50) DEFAULT NULL COMMENT 'Gabarit / hauteur max (ex: 1.90 m)',
    `capacity` VARCHAR(100) DEFAULT NULL COMMENT 'Capacité totale (ex: 416 places)',
    `opening_hours_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (FR)',
    `opening_hours_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (EN)',
    `rates_fr` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (FR)',
    `rates_en` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (EN)',
    `suitable_vehicles_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (FR)',
    `suitable_vehicles_en` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (EN)',
    `how_to_reach_fr` TEXT DEFAULT NULL COMMENT 'Itinéraire retour vers le logement (FR)',
    `how_to_reach_en` TEXT DEFAULT NULL COMMENT 'Itinéraire retour vers le logement (EN)',
    `operator` VARCHAR(100) DEFAULT NULL COMMENT 'Opérateur (Zenpark, LPA&CO, Q-Park, TCL, etc.)',
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien de réservation directe',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques (EN)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_parking_locations_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_parking_locations_prop_cat` (`property_id`, `category`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
