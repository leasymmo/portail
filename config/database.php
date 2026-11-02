<?php
/**
 * Configuration & Connexion Base de Données
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 */

// Empêcher l'accès direct si nécessaire
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Polyfills de compatibilité pour PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || $needle === substr($haystack, -strlen($needle));
    }
}

/**
 * Chargeur de fichier .env ultra-léger et autonome (sans Composer)
 */
function load_env($filePath = null) {
    if ($filePath === null) {
        $filePath = APP_ROOT . '/.env';
    }

    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    return true;
}

load_env();

/**
 * Récupère une variable d'environnement avec valeur par défaut
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    if (is_string($value)) {
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;
    }

    return $value;
}

/**
 * Établit et renvoie la connexion PDO MySQL
 *
 * @return PDO
 * @throws PDOException
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $dbname = env('DB_NAME', '');
    $user = env('DB_USER', '');
    $pass = env('DB_PASS', '');

    if (empty($dbname) || empty($user) || empty($pass) || $pass === 'METTEZ_VOTRE_MOT_DE_PASSE_ICI' || $pass === 'VOTRE_MOT_DE_PASSE') {
        throw new PDOException("Configuration requise : Veuillez renseigner vos identifiants MySQL complets dans le fichier .env (variables DB_NAME, DB_USER, DB_PASS).");
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

/**
 * Détecte si la table s'appelle 'videos' ou 'tutorials' pour une compatibilité fluide
 */
function get_videos_table_name($pdo) {
    static $tableName = null;
    if ($tableName === null) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'videos'");
        $tableName = ($stmt->rowCount() > 0) ? 'videos' : 'tutorials';
    }
    return $tableName;
}

/**
 * Recherche un logement par son slug
 */
function get_property_by_slug($slug) {
    $pdo = get_db_connection();
    
    $normalizedSlug = str_replace('_', '-', strtolower(trim($slug)));
    $underscoreSlug = str_replace('-', '_', strtolower(trim($slug)));

    $stmt = $pdo->prepare("
        SELECT * FROM properties 
        WHERE (slug = :slug1 OR slug = :slug2) AND active = 1 
        LIMIT 1
    ");
    $stmt->execute([
        ':slug1' => $normalizedSlug,
        ':slug2' => $underscoreSlug
    ]);
    
    return $stmt->fetch();
}

/**
 * Récupère toutes les vidéos d'un logement ordonnées (table videos ou tutorials)
 */
function get_videos_by_property_id($propertyId) {
    $pdo = get_db_connection();
    $table = get_videos_table_name($pdo);

    $stmt = $pdo->prepare("
        SELECT * FROM {$table} 
        WHERE property_id = :property_id AND active = 1 
        ORDER BY display_order ASC, id ASC
    ");
    $stmt->execute([':property_id' => $propertyId]);
    return $stmt->fetchAll();
}

// Alias de rétrocompatibilité
function get_tutorials_by_property_id($propertyId) {
    return get_videos_by_property_id($propertyId);
}

/**
 * Liste l'ensemble des logements actifs
 */
function get_all_active_properties() {
    $pdo = get_db_connection();
    $table = get_videos_table_name($pdo);

    $stmt = $pdo->query("
        SELECT id, slug, name, subtitle, guide_fr_url, guide_en_url,
               (SELECT COUNT(*) FROM {$table} WHERE {$table}.property_id = properties.id AND {$table}.active = 1) AS videos_count,
               (SELECT COUNT(*) FROM {$table} WHERE {$table}.property_id = properties.id AND {$table}.active = 1) AS tutorials_count
        FROM properties 
        WHERE active = 1 
        ORDER BY name ASC
    ");
    return $stmt->fetchAll();
}
