<?php
/**
 * ==============================================================================
 * DÉPÔT DE DONNÉES LOGEMENTS & VIDÉOS (REPOSITORY)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

namespace Cdh\Portal\Repository;

use Cdh\Portal\Database;
use PDO;

class PropertyRepository
{
    /**
     * Recherche un logement actif par son slug (support tirets et underscores)
     */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();

        $cleanSlug = strtolower(trim($slug));
        $normalizedSlug = str_replace('_', '-', $cleanSlug);
        $underscoreSlug = str_replace('-', '_', $cleanSlug);

        $stmt = $pdo->prepare("
            SELECT * FROM properties 
            WHERE (slug = :slug1 OR slug = :slug2) AND active = 1 
            LIMIT 1
        ");
        $stmt->execute([
            ':slug1' => $normalizedSlug,
            ':slug2' => $underscoreSlug
        ]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère la liste ordonnée des vidéos explicatives d'un logement
     */
    public static function findVideosByPropertyId(int $propertyId): array
    {
        $pdo = Database::getConnection();
        $table = Database::getVideoTableName();

        $stmt = $pdo->prepare("
            SELECT * FROM {$table} 
            WHERE property_id = :property_id AND active = 1 
            ORDER BY display_order ASC, id ASC
        ");
        $stmt->execute([':property_id' => $propertyId]);

        return $stmt->fetchAll();
    }

    /**
     * Récupère la configuration générale du stationnement d'un logement
     */
    public static function findParkingByPropertyId(int $propertyId): ?array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT * FROM property_parkings 
                WHERE property_id = :property_id 
                LIMIT 1
            ");
            $stmt->execute([':property_id' => $propertyId]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Récupère la liste structurée des emplacements de stationnement
     * (Rues gratuites, parkings privés/couverts et Parcs Relais TCL)
     */
    public static function findParkingStructuredByPropertyId(int $propertyId): array
    {
        try {
            $pdo = Database::getConnection();

            // Architecture V3 : Many-to-Many via property_parking_associations
            $stmtFree = $pdo->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.spot_type,
                    c.name_fr,
                    c.name_en,
                    c.address,
                    a.walking_time_fr,
                    a.walking_time_en,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    CASE WHEN c.spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category
                FROM property_parking_associations a
                JOIN parking_free_spots c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid AND a.active = 1 AND a.category IN ('free_street', 'free_spot')
                ORDER BY a.display_order ASC, a.id ASC
            ");
            $stmtFree->execute([':pid' => $propertyId]);
            $free = $stmtFree->fetchAll();

            $stmtPriv = $pdo->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.name_fr,
                    c.name_en,
                    c.operator,
                    c.address,
                    a.walking_time_fr,
                    a.walking_time_en,
                    c.max_height,
                    c.opening_hours_fr,
                    c.opening_hours_en,
                    c.rates_fr,
                    c.rates_en,
                    c.suitable_vehicles_fr,
                    c.suitable_vehicles_en,
                    c.booking_url,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    'private_parking' AS category
                FROM property_parking_associations a
                JOIN parking_private_lots c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid AND a.active = 1 AND a.category = 'private_parking'
                ORDER BY a.display_order ASC, a.id ASC
            ");
            $stmtPriv->execute([':pid' => $propertyId]);
            $priv = $stmtPriv->fetchAll();

            $stmtTcl = $pdo->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.name_fr,
                    c.name_en,
                    c.capacity,
                    c.address,
                    a.transit_time_fr,
                    a.transit_time_en,
                    a.walking_time_fr,
                    a.walking_time_en,
                    c.opening_hours_fr,
                    c.opening_hours_en,
                    a.how_to_reach_fr,
                    a.how_to_reach_en,
                    c.booking_url,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    'tcl_pr' AS category
                FROM property_parking_associations a
                JOIN parking_tcl_prs c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid AND a.active = 1 AND a.category = 'tcl_pr'
                ORDER BY a.display_order ASC, a.id ASC
            ");
            $stmtTcl->execute([':pid' => $propertyId]);
            $tcl = $stmtTcl->fetchAll();

            $freeStreets = array_values(array_filter($free, fn($s) => ($s['spot_type'] ?? '') !== 'spot'));
            $freeSpots = array_values(array_filter($free, fn($s) => ($s['spot_type'] ?? '') === 'spot'));

            return [
                'free_spots'      => $free,
                'private_lots'    => $priv,
                'tcl_prs'         => $tcl,
                'free_street'     => $freeStreets,
                'free_spot'       => $freeSpots,
                'private_parking' => $priv,
                'tcl_pr'          => $tcl,
                'all'             => array_merge($free, $priv, $tcl)
            ];
        } catch (\Throwable $e) {
            return [
                'free_spots'      => [],
                'private_lots'    => [],
                'tcl_prs'         => [],
                'free_street'     => [],
                'free_spot'       => [],
                'private_parking' => [],
                'tcl_pr'          => [],
                'all'             => []
            ];
        }
    }

    /**
     * Récupère la liste de tous les emplacements de stationnement actifs
     */
    public static function findParkingLocationsByPropertyId(int $propertyId): array
    {
        $structured = self::findParkingStructuredByPropertyId($propertyId);
        return $structured['all'] ?? [];
    }

    /**
     * Récupère la liste de tous les logements actifs avec décompte des tutoriels
     */
    public static function findAllActive(): array
    {
        $pdo = Database::getConnection();
        $table = Database::getVideoTableName();

        $stmt = $pdo->query("
            SELECT id, slug, name, guide_fr_url, guide_en_url,
                   (SELECT COUNT(*) FROM {$table} WHERE {$table}.property_id = properties.id AND {$table}.active = 1) AS videos_count,
                   (SELECT COUNT(*) FROM {$table} WHERE {$table}.property_id = properties.id AND {$table}.active = 1) AS tutorials_count
            FROM properties 
            WHERE active = 1 
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }
}
