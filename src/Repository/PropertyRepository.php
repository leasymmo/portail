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
