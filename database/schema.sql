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
    `rental_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID du logement sur SuperHote',
    `guide_fr_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret FR',
    `guide_en_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret EN',
    `wifi_ssid` VARCHAR(100) DEFAULT NULL COMMENT 'Nom du réseau WiFi (optionnel)',
    `wifi_password` VARCHAR(100) DEFAULT NULL COMMENT 'Clé de sécurité WiFi (optionnel)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse du logement (optionnel)',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = visible, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_properties_slug_active` (`slug`, `active`),
    INDEX `idx_properties_rental_id` (`rental_id`)
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
-- Table : parking_free_spots (Catalogue Rues et dépose-minute gratuits)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_free_spots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `spot_type` ENUM('street', 'spot') NOT NULL DEFAULT 'street' COMMENT 'street = rue gratuite, spot = arrêt minute / court',
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du lieu / rue (EN)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise ou repère GPS',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (EN)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pfs_type_name` (`spot_type`, `name_fr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_private_lots (Catalogue Parkings privés et couverts)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_private_lots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du parking (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du parking (EN)',
    `operator` VARCHAR(100) DEFAULT NULL COMMENT 'Opérateur (Zenpark, LPA&CO, Q-Park, Indigo...)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise du parking',
    `max_height` VARCHAR(50) DEFAULT NULL COMMENT 'Hauteur max / gabarit (ex: 1.90 m)',
    `opening_hours_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (FR)',
    `opening_hours_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (EN)',
    `rates_fr` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (FR)',
    `rates_en` TEXT DEFAULT NULL COMMENT 'Grille tarifaire (EN)',
    `suitable_vehicles_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (FR)',
    `suitable_vehicles_en` VARCHAR(255) DEFAULT NULL COMMENT 'Véhicules adaptés (EN)',
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien de réservation directe',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (EN)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ppl_operator` (`operator`),
    INDEX `idx_ppl_name` (`name_fr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : parking_tcl_prs (Catalogue Parcs Relais TCL - P+R)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parking_tcl_prs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name_fr` VARCHAR(255) NOT NULL COMMENT 'Nom du P+R (FR)',
    `name_en` VARCHAR(255) NOT NULL COMMENT 'Nom du P+R (EN)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse précise du P+R',
    `capacity` VARCHAR(100) DEFAULT NULL COMMENT 'Capacité totale (ex: 416 places)',
    `opening_hours_fr` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (FR)',
    `opening_hours_en` VARCHAR(255) DEFAULT NULL COMMENT 'Horaires d''accès (EN)',
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien officiel TCL',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Remarques pratiques générales (EN)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ptp_name` (`name_fr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : property_parking_associations (Relations N-N Logements <-> Parkings)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `property_parking_associations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers properties',
    `category` ENUM('free_street', 'free_spot', 'private_parking', 'tcl_pr') NOT NULL COMMENT 'Type de parking ciblé',
    `parking_item_id` INT UNSIGNED NOT NULL COMMENT 'ID dans parking_free_spots, parking_private_lots ou parking_tcl_prs',
    `walking_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied spécifique au logement (FR)',
    `walking_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès à pied spécifique au logement (EN)',
    `transit_time_fr` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports spécifique au logement (FR)',
    `transit_time_en` VARCHAR(100) DEFAULT NULL COMMENT 'Temps d''accès en transports spécifique au logement (EN)',
    `how_to_reach_fr` TEXT DEFAULT NULL COMMENT 'Itinéraire retour spécifique au logement (FR)',
    `how_to_reach_en` TEXT DEFAULT NULL COMMENT 'Itinéraire retour spécifique au logement (EN)',
    `notes_fr` TEXT DEFAULT NULL COMMENT 'Note d''accès spécifique à l''association logement-parking (FR)',
    `notes_en` TEXT DEFAULT NULL COMMENT 'Note d''accès spécifique à l''association logement-parking (EN)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage au sein de la catégorie pour ce logement',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif pour ce logement, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ppa_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_prop_cat_item` (`property_id`, `category`, `parking_item_id`),
    INDEX `idx_ppa_lookup` (`property_id`, `category`, `display_order`, `active`),
    INDEX `idx_ppa_reverse` (`category`, `parking_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : admin_users (Comptes administrateurs du back-office)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE COMMENT 'Identifiant unique de connexion',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hachage sécurisé (bcrypt/Argon2id)',
    `name` VARCHAR(100) NOT NULL COMMENT 'Nom et prénom',
    `role` ENUM('superadmin', 'admin', 'editor') NOT NULL DEFAULT 'admin' COMMENT 'Rôles de privilèges',
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

-- ------------------------------------------------------------------------------
-- Table : admin_sessions (Sessions actives et jetons sécurisés)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) NOT NULL UNIQUE COMMENT 'Jeton de session cryptographique aléatoire',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    INDEX `idx_admin_sessions_token` (`session_token`),
    INDEX `idx_admin_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : admin_activity_logs (Journal d'audit et traçabilité)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` ENUM('login', 'logout', 'create', 'update', 'delete', 'toggle') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'property, video, parking, location, user',
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `entity_label` VARCHAR(255) DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
    INDEX `idx_admin_logs_user_date` (`user_id`, `created_at`),
    INDEX `idx_admin_logs_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : admin_settings (Paramètres système et clés API pour l'IA)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
