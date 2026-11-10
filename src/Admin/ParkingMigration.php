<?php

namespace Cdh\Portal\Admin;

use PDO;
use Throwable;

class ParkingMigration
{
    /**
     * Exécute la migration complète et idempotente vers la structure V2
     */
    public static function run(PDO $db): array
    {
        try {
            // 1. Créer les 3 tables spécialisées
            self::createTables($db);

            // 2. Détecter l'état de la table parking_locations existante
            $status = self::detectTableStatus($db);

            // 3. Migrer les données si la table de base existe
            if ($status['is_base_table']) {
                self::migrateFromTable($db, 'parking_locations');
                self::archiveBaseTable($db);
            } elseif ($status['has_archive']) {
                // Si la table est déjà archivée mais les nouvelles tables sont vides, re-migrer depuis l'archive
                $totalNew = (int)$db->query("SELECT (SELECT COUNT(*) FROM parking_free_spots) + (SELECT COUNT(*) FROM parking_private_lots) + (SELECT COUNT(*) FROM parking_tcl_prs)")->fetchColumn();
                if ($totalNew === 0) {
                    self::migrateFromTable($db, '_archive_parking_locations');
                }
            }

            // 4. Créer ou remplacer la vue rétrocompatible
            self::createView($db);

            // 5. Nettoyer les colonnes obsolètes de property_parkings
            self::cleanDeadColumns($db);

            // 6. Récupérer les métriques
            $cntFree = (int)$db->query("SELECT COUNT(*) FROM parking_free_spots")->fetchColumn();
            $cntPriv = (int)$db->query("SELECT COUNT(*) FROM parking_private_lots")->fetchColumn();
            $cntTcl  = (int)$db->query("SELECT COUNT(*) FROM parking_tcl_prs")->fetchColumn();

            return [
                'ok'      => true,
                'message' => 'Migration V2 appliquée avec succès ! Les 3 tables et la vue sont opérationnelles.',
                'stats'   => [
                    'free_spots'   => $cntFree,
                    'private_lots' => $cntPriv,
                    'tcl_prs'      => $cntTcl,
                    'total'        => $cntFree + $cntPriv + $cntTcl
                ]
            ];
        } catch (Throwable $e) {
            return [
                'ok'    => false,
                'error' => 'Erreur lors de la migration : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crée les 3 tables spécialisées si elles n'existent pas
     */
    public static function createTables(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `parking_free_spots` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `property_id` INT UNSIGNED NOT NULL,
                `spot_type` ENUM('street', 'spot') NOT NULL DEFAULT 'street',
                `name_fr` VARCHAR(255) NOT NULL,
                `name_en` VARCHAR(255) NOT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `walking_time_fr` VARCHAR(100) DEFAULT NULL,
                `walking_time_en` VARCHAR(100) DEFAULT NULL,
                `notes_fr` TEXT DEFAULT NULL,
                `notes_en` TEXT DEFAULT NULL,
                `display_order` INT NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_pfs_property_order` (`property_id`, `display_order`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `parking_private_lots` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `property_id` INT UNSIGNED NOT NULL,
                `name_fr` VARCHAR(255) NOT NULL,
                `name_en` VARCHAR(255) NOT NULL,
                `operator` VARCHAR(100) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `walking_time_fr` VARCHAR(100) DEFAULT NULL,
                `walking_time_en` VARCHAR(100) DEFAULT NULL,
                `max_height` VARCHAR(50) DEFAULT NULL,
                `opening_hours_fr` VARCHAR(255) DEFAULT NULL,
                `opening_hours_en` VARCHAR(255) DEFAULT NULL,
                `rates_fr` TEXT DEFAULT NULL,
                `rates_en` TEXT DEFAULT NULL,
                `suitable_vehicles_fr` VARCHAR(255) DEFAULT NULL,
                `suitable_vehicles_en` VARCHAR(255) DEFAULT NULL,
                `booking_url` VARCHAR(1000) DEFAULT NULL,
                `notes_fr` TEXT DEFAULT NULL,
                `notes_en` TEXT DEFAULT NULL,
                `display_order` INT NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_ppl_property_order` (`property_id`, `display_order`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `parking_tcl_prs` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `property_id` INT UNSIGNED NOT NULL,
                `name_fr` VARCHAR(255) NOT NULL,
                `name_en` VARCHAR(255) NOT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `transit_time_fr` VARCHAR(100) DEFAULT NULL,
                `transit_time_en` VARCHAR(100) DEFAULT NULL,
                `capacity` VARCHAR(100) DEFAULT NULL,
                `opening_hours_fr` VARCHAR(255) DEFAULT NULL,
                `opening_hours_en` VARCHAR(255) DEFAULT NULL,
                `how_to_reach_fr` TEXT DEFAULT NULL,
                `how_to_reach_en` TEXT DEFAULT NULL,
                `booking_url` VARCHAR(1000) DEFAULT NULL,
                `notes_fr` TEXT DEFAULT NULL,
                `notes_en` TEXT DEFAULT NULL,
                `display_order` INT NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_ptp_property_order` (`property_id`, `display_order`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Détecte si parking_locations est une table de base ou une vue
     */
    public static function detectTableStatus(PDO $db): array
    {
        $isBase = false;
        $hasArchive = false;
        try {
            $stmt = $db->query("SELECT TABLE_TYPE FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parking_locations'");
            $type = $stmt ? $stmt->fetchColumn() : null;
            $isBase = ($type === 'BASE TABLE');
        } catch (Throwable $e) {}

        try {
            $stmt2 = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '_archive_parking_locations'");
            $hasArchive = $stmt2 && (int)$stmt2->fetchColumn() > 0;
        } catch (Throwable $e) {}

        return ['is_base_table' => $isBase, 'has_archive' => $hasArchive];
    }

    /**
     * Migre les lignes depuis la table source vers les 3 tables spécialisées
     */
    public static function migrateFromTable(PDO $db, string $sourceTable): void
    {
        // 1. Rues gratuites & Dépose minute
        $db->exec("
            INSERT INTO `parking_free_spots` (
                id, property_id, spot_type, name_fr, name_en, address,
                walking_time_fr, walking_time_en, notes_fr, notes_en,
                display_order, active, created_at, updated_at
            )
            SELECT 
                id, property_id,
                CASE WHEN category = 'free_spot' THEN 'spot' ELSE 'street' END,
                name_fr, COALESCE(name_en, name_fr), address,
                COALESCE(walking_time_fr, transit_time_fr),
                COALESCE(walking_time_en, transit_time_en),
                notes_fr, notes_en,
                display_order, active, created_at, updated_at
            FROM `{$sourceTable}`
            WHERE category IN ('free_street', 'free_spot')
            ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)
        ");

        // 2. Parkings privés / couverts
        $db->exec("
            INSERT INTO `parking_private_lots` (
                id, property_id, name_fr, name_en, operator, address,
                walking_time_fr, walking_time_en, max_height,
                opening_hours_fr, opening_hours_en, rates_fr, rates_en,
                suitable_vehicles_fr, suitable_vehicles_en, booking_url,
                notes_fr, notes_en, display_order, active, created_at, updated_at
            )
            SELECT 
                id, property_id, name_fr, COALESCE(name_en, name_fr), operator, address,
                walking_time_fr, walking_time_en, max_height,
                opening_hours_fr, opening_hours_en, rates_fr, rates_en,
                suitable_vehicles_fr, suitable_vehicles_en, booking_url,
                notes_fr, notes_en, display_order, active, created_at, updated_at
            FROM `{$sourceTable}`
            WHERE category = 'private_parking'
            ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)
        ");

        // 3. Parcs Relais TCL
        $db->exec("
            INSERT INTO `parking_tcl_prs` (
                id, property_id, name_fr, name_en, address,
                transit_time_fr, transit_time_en, capacity,
                opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en,
                booking_url, notes_fr, notes_en, display_order, active, created_at, updated_at
            )
            SELECT 
                id, property_id, name_fr, COALESCE(name_en, name_fr), address,
                transit_time_fr, transit_time_en, capacity,
                opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en,
                booking_url, notes_fr, notes_en, display_order, active, created_at, updated_at
            FROM `{$sourceTable}`
            WHERE category = 'tcl_pr'
            ON DUPLICATE KEY UPDATE name_fr = VALUES(name_fr)
        ");
    }

    /**
     * Archive la table de base d'origine
     */
    public static function archiveBaseTable(PDO $db): void
    {
        try {
            $db->exec("DROP TABLE IF EXISTS `_archive_parking_locations`");
            $db->exec("RENAME TABLE `parking_locations` TO `_archive_parking_locations`");
        } catch (Throwable $e) {
            // Ignorer si la table est déjà renommée
        }
    }

    /**
     * Crée ou remplace la vue SQL unifiée rétrocompatible
     */
    public static function createView(PDO $db): void
    {
        $db->exec("
            CREATE OR REPLACE VIEW `parking_locations` AS
            SELECT 
                id, property_id,
                CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category,
                name_fr, name_en, address,
                walking_time_fr, walking_time_en,
                NULL AS walking_minutes, NULL AS transit_time_fr, NULL AS transit_time_en,
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
                NULL AS walking_minutes, NULL AS transit_time_fr, NULL AS transit_time_en,
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
                NULL AS walking_minutes, transit_time_fr, transit_time_en,
                NULL AS max_height, capacity,
                opening_hours_fr, opening_hours_en,
                NULL AS rates_fr, NULL AS rates_en,
                NULL AS suitable_vehicles_fr, NULL AS suitable_vehicles_en,
                how_to_reach_fr, how_to_reach_en,
                'TCL' AS operator, booking_url,
                notes_fr, notes_en, display_order, active, created_at, updated_at
            FROM `parking_tcl_prs`
        ");
    }

    /**
     * Supprime les colonnes mortes de property_parkings
     */
    public static function cleanDeadColumns(PDO $db): void
    {
        $deadCols = ['pdf_fr_url', 'pdf_en_url', 'tcl_notes_fr', 'tcl_notes_en'];
        foreach ($deadCols as $col) {
            try {
                $stmt = $db->query("
                    SELECT COUNT(*) FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_parkings' AND COLUMN_NAME = '{$col}'
                ");
                if ($stmt && (int)$stmt->fetchColumn() > 0) {
                    $db->exec("ALTER TABLE `property_parkings` DROP COLUMN `{$col}`");
                }
            } catch (Throwable $e) {
                // Ignore if already dropped
            }
        }
    }
}
