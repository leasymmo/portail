-- ==============================================================================
-- MIGRATION V2 & REFONTE DU MODULE STATIONNEMENT - CARRÉ D'HÔTES
-- Version MySQL : 8.0 & 8.4+
-- 
-- Ce script :
-- 1. Crée les 3 tables spécialisées et normalisées :
--    - `parking_free_spots` (rues gratuites et dépose-minute)
--    - `parking_private_lots` (parkings couverts et privés)
--    - `parking_tcl_prs` (parcs relais TCL)
-- 2. Migre 100% des données depuis l'ancienne table `parking_locations`
-- 3. Archive l'ancienne table `parking_locations` sous `_archive_parking_locations`
-- 4. Crée une vue SQL `parking_locations` pour une rétrocompatibilité totale
-- 5. Nettoie les colonnes obsolètes de `property_parkings`
-- ==============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ------------------------------------------------------------------------------
-- 1. CRÉATION DES TABLES SPÉCIALISÉES
-- ------------------------------------------------------------------------------

-- Table 1 : Rues et dépose-minute gratuits
CREATE TABLE IF NOT EXISTS `parking_free_spots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers properties',
    `spot_type` ENUM('street', 'spot') NOT NULL DEFAULT 'street' COMMENT 'street = rue gratuite, spot = arrêt court / dépose-minute',
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

-- Table 2 : Parkings privés et couverts
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

-- Table 3 : Parcs Relais TCL (P+R)
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
    `booking_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien officiel du parc relais TCL',
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
-- 2. MIGRATION DES DONNÉES DEPUIS PARKING_LOCATIONS (SI PRÉSENTE)
-- ------------------------------------------------------------------------------
-- Migration vers parking_free_spots (Rues et arrêts minute)
SET @has_table = (
    SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'parking_locations' AND table_type = 'BASE TABLE'
);

SET @sql_migrate_free = IF(@has_table > 0, 
    'INSERT INTO parking_free_spots (
        id, property_id, spot_type, name_fr, name_en, address,
        walking_time_fr, walking_time_en, notes_fr, notes_en,
        display_order, active, created_at, updated_at
    )
    SELECT 
        id, property_id,
        CASE WHEN category = ''free_spot'' THEN ''spot'' ELSE ''street'' END,
        name_fr, name_en, address,
        COALESCE(walking_time_fr, transit_time_fr),
        COALESCE(walking_time_en, transit_time_en),
        notes_fr, notes_en,
        display_order, active, created_at, updated_at
    FROM parking_locations
    WHERE category IN (''free_street'', ''free_spot'')
    ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)',
    'SELECT "No base parking_locations table to migrate from for free spots"'
);
PREPARE stmt_mig_free FROM @sql_migrate_free;
EXECUTE stmt_mig_free;
DEALLOCATE PREPARE stmt_mig_free;

-- Migration vers parking_private_lots (Parkings couverts / privés)
SET @sql_migrate_priv = IF(@has_table > 0, 
    'INSERT INTO parking_private_lots (
        id, property_id, name_fr, name_en, operator, address,
        walking_time_fr, walking_time_en, max_height,
        opening_hours_fr, opening_hours_en, rates_fr, rates_en,
        suitable_vehicles_fr, suitable_vehicles_en, booking_url,
        notes_fr, notes_en, display_order, active, created_at, updated_at
    )
    SELECT 
        id, property_id, name_fr, name_en, operator, address,
        walking_time_fr, walking_time_en, max_height,
        opening_hours_fr, opening_hours_en, rates_fr, rates_en,
        suitable_vehicles_fr, suitable_vehicles_en, booking_url,
        notes_fr, notes_en, display_order, active, created_at, updated_at
    FROM parking_locations
    WHERE category = ''private_parking''
    ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)',
    'SELECT "No base parking_locations table to migrate from for private lots"'
);
PREPARE stmt_mig_priv FROM @sql_migrate_priv;
EXECUTE stmt_mig_priv;
DEALLOCATE PREPARE stmt_mig_priv;

-- Migration vers parking_tcl_prs (Parcs Relais TCL)
SET @sql_migrate_tcl = IF(@has_table > 0, 
    'INSERT INTO parking_tcl_prs (
        id, property_id, name_fr, name_en, address,
        transit_time_fr, transit_time_en, capacity,
        opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en,
        booking_url, notes_fr, notes_en, display_order, active, created_at, updated_at
    )
    SELECT 
        id, property_id, name_fr, name_en, address,
        transit_time_fr, transit_time_en, capacity,
        opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en,
        booking_url, notes_fr, notes_en, display_order, active, created_at, updated_at
    FROM parking_locations
    WHERE category = ''tcl_pr''
    ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)',
    'SELECT "No base parking_locations table to migrate from for TCL P+R"'
);
PREPARE stmt_mig_tcl FROM @sql_migrate_tcl;
EXECUTE stmt_mig_tcl;
DEALLOCATE PREPARE stmt_mig_tcl;

-- ------------------------------------------------------------------------------
-- 3. ARCHIVAGE DE L'ANCIENNE TABLE ET CRÉATION DE LA VUE RÉTROCOMPATIBLE
-- ------------------------------------------------------------------------------
SET @sql_archive = IF(@has_table > 0, 
    'RENAME TABLE parking_locations TO _archive_parking_locations',
    'SELECT "No base parking_locations table to archive"'
);
PREPARE stmt_arch FROM @sql_archive;
EXECUTE stmt_arch;
DEALLOCATE PREPARE stmt_arch;

-- Création de la VUE SQL rétrocompatible `parking_locations`
CREATE OR REPLACE VIEW `parking_locations` AS
SELECT 
    id,
    property_id,
    CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category,
    name_fr,
    name_en,
    address,
    walking_time_fr,
    walking_time_en,
    NULL AS walking_minutes,
    NULL AS transit_time_fr,
    NULL AS transit_time_en,
    NULL AS max_height,
    NULL AS capacity,
    NULL AS opening_hours_fr,
    NULL AS opening_hours_en,
    NULL AS rates_fr,
    NULL AS rates_en,
    NULL AS suitable_vehicles_fr,
    NULL AS suitable_vehicles_en,
    NULL AS how_to_reach_fr,
    NULL AS how_to_reach_en,
    NULL AS operator,
    NULL AS booking_url,
    notes_fr,
    notes_en,
    display_order,
    active,
    created_at,
    updated_at
FROM `parking_free_spots`
UNION ALL
SELECT 
    id,
    property_id,
    'private_parking' AS category,
    name_fr,
    name_en,
    address,
    walking_time_fr,
    walking_time_en,
    NULL AS walking_minutes,
    NULL AS transit_time_fr,
    NULL AS transit_time_en,
    max_height,
    NULL AS capacity,
    opening_hours_fr,
    opening_hours_en,
    rates_fr,
    rates_en,
    suitable_vehicles_fr,
    suitable_vehicles_en,
    NULL AS how_to_reach_fr,
    NULL AS how_to_reach_en,
    operator,
    booking_url,
    notes_fr,
    notes_en,
    display_order,
    active,
    created_at,
    updated_at
FROM `parking_private_lots`
UNION ALL
SELECT 
    id,
    property_id,
    'tcl_pr' AS category,
    name_fr,
    name_en,
    address,
    NULL AS walking_time_fr,
    NULL AS walking_time_en,
    NULL AS walking_minutes,
    transit_time_fr,
    transit_time_en,
    NULL AS max_height,
    capacity,
    opening_hours_fr,
    opening_hours_en,
    NULL AS rates_fr,
    NULL AS rates_en,
    NULL AS suitable_vehicles_fr,
    NULL AS suitable_vehicles_en,
    how_to_reach_fr,
    how_to_reach_en,
    'TCL' AS operator,
    booking_url,
    notes_fr,
    notes_en,
    display_order,
    active,
    created_at,
    updated_at
FROM `parking_tcl_prs`;

-- ------------------------------------------------------------------------------
-- 4. NETTOYAGE DES COLONNES MORTES DANS PROPERTY_PARKINGS (SI EXISTANTES)
-- ------------------------------------------------------------------------------
-- Suppression conditionnelle sécurisée des colonnes mortes
SET @drop_pdf_fr = (SELECT IF(
    EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_parkings' AND COLUMN_NAME = 'pdf_fr_url'),
    'ALTER TABLE `property_parkings` DROP COLUMN `pdf_fr_url`',
    'SELECT 1'
));
PREPARE s1 FROM @drop_pdf_fr; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @drop_pdf_en = (SELECT IF(
    EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_parkings' AND COLUMN_NAME = 'pdf_en_url'),
    'ALTER TABLE `property_parkings` DROP COLUMN `pdf_en_url`',
    'SELECT 1'
));
PREPARE s2 FROM @drop_pdf_en; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @drop_tcl_notes_fr = (SELECT IF(
    EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_parkings' AND COLUMN_NAME = 'tcl_notes_fr'),
    'ALTER TABLE `property_parkings` DROP COLUMN `tcl_notes_fr`',
    'SELECT 1'
));
PREPARE s3 FROM @drop_tcl_notes_fr; EXECUTE s3; DEALLOCATE PREPARE s3;

SET @drop_tcl_notes_en = (SELECT IF(
    EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_parkings' AND COLUMN_NAME = 'tcl_notes_en'),
    'ALTER TABLE `property_parkings` DROP COLUMN `tcl_notes_en`',
    'SELECT 1'
));
PREPARE s4 FROM @drop_tcl_notes_en; EXECUTE s4; DEALLOCATE PREPARE s4;

SET foreign_key_checks = 1;
