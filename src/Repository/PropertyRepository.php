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

            // Tables spécialisées V2
            $stmtFree = $pdo->prepare("
                SELECT *, CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category 
                FROM parking_free_spots 
                WHERE property_id = :pid AND active = 1 
                ORDER BY display_order ASC, id ASC
            ");
            $stmtFree->execute([':pid' => $propertyId]);
            $free = $stmtFree->fetchAll();

            $stmtPriv = $pdo->prepare("
                SELECT *, 'private_parking' AS category 
                FROM parking_private_lots 
                WHERE property_id = :pid AND active = 1 
                ORDER BY display_order ASC, id ASC
            ");
            $stmtPriv->execute([':pid' => $propertyId]);
            $priv = $stmtPriv->fetchAll();

            $stmtTcl = $pdo->prepare("
                SELECT *, 'tcl_pr' AS category 
                FROM parking_tcl_prs 
                WHERE property_id = :pid AND active = 1 
                ORDER BY display_order ASC, id ASC
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
        } catch (\PDOException $e) {
            // Repli sur l'ancienne table ou vue parking_locations
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("
                    SELECT * FROM parking_locations 
                    WHERE property_id = :property_id AND active = 1 
                    ORDER BY display_order ASC, id ASC
                ");
                $stmt->execute([':property_id' => $propertyId]);
                $all = $stmt->fetchAll();

                return [
                    'free_spots'      => array_values(array_filter($all, fn($l) => in_array($l['category'] ?? '', ['free_street', 'free_spot']))),
                    'private_lots'    => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'private_parking')),
                    'tcl_prs'         => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'tcl_pr')),
                    'free_street'     => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'free_street')),
                    'free_spot'       => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'free_spot')),
                    'private_parking' => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'private_parking')),
                    'tcl_pr'          => array_values(array_filter($all, fn($l) => ($l['category'] ?? '') === 'tcl_pr')),
                    'all'             => $all
                ];
            } catch (\PDOException $e2) {
                return [
                    'free_spots' => [], 'private_lots' => [], 'tcl_prs' => [],
                    'free_street' => [], 'free_spot' => [], 'private_parking' => [], 'tcl_pr' => [],
                    'all' => []
                ];
            }
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
