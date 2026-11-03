<?php
/**
 * ==============================================================================
 * CONFIGURATION & PONT DE COMPATIBILITÉ BASE DE DONNÉES
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 * Charge les modules du backend (src/) et fournit les fonctions globales
 * de compatibilité pour l'ensemble du projet.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Chargement des composants modulaires
require_once APP_ROOT . '/src/Env.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Repository/PropertyRepository.php';

use Cdh\Portal\Env;
use Cdh\Portal\Database;
use Cdh\Portal\Repository\PropertyRepository;

// Initialisation précoce des variables d'environnement
Env::load();

/**
 * Récupère une variable d'environnement avec valeur de repli
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        return Env::get($key, $default);
    }
}

/**
 * Renvoie l'instance de connexion PDO MySQL
 *
 * @throws PDOException
 */
if (!function_exists('get_db_connection')) {
    function get_db_connection(): PDO {
        return Database::getConnection();
    }
}

/**
 * Détecte si la table s'appelle 'videos' ou 'tutorials'
 */
if (!function_exists('get_videos_table_name')) {
    function get_videos_table_name(?PDO $pdo = null): string {
        return Database::getVideoTableName();
    }
}

/**
 * Recherche un logement par son slug
 */
if (!function_exists('get_property_by_slug')) {
    function get_property_by_slug(string $slug): ?array {
        return PropertyRepository::findBySlug($slug);
    }
}

/**
 * Récupère les vidéos explicatives d'un hébergement
 */
if (!function_exists('get_videos_by_property_id')) {
    function get_videos_by_property_id(int $propertyId): array {
        return PropertyRepository::findVideosByPropertyId($propertyId);
    }
}

// Alias de rétrocompatibilité
if (!function_exists('get_tutorials_by_property_id')) {
    function get_tutorials_by_property_id(int $propertyId): array {
        return get_videos_by_property_id($propertyId);
    }
}

/**
 * Liste l'ensemble des logements actifs
 */
if (!function_exists('get_all_active_properties')) {
    function get_all_active_properties(): array {
        return PropertyRepository::findAllActive();
    }
}
