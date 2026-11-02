<?php
/**
 * ==============================================================================
 * MANIFESTE PWA DYNAMIQUE PAR LOGEMENT
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 * Génère un manifeste Web App sur-mesure pour chaque hébergement.
 * Permet au voyageur d'installer l'application directement sur son logement :
 * - Titre personnalisé (ex: "Maisonnette en Jouhet | Carré d'Hôtes")
 * - URL de démarrage pointant directement vers /portail/{slug}
 * - Identifiant PWA unique id: "/portail/{slug}"
 * - Icônes haute résolution dérivées du logo officiel Carré d'Hôtes
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/database.php';

// 1. Extraction du slug depuis GET
$slug = $_GET['slug'] ?? '';
$slug = strtolower(trim($slug));

// 2. Repli automatique sur le Referer HTTP si le slug n'a pas été passé en paramètre
if (empty($slug) && !empty($_SERVER['HTTP_REFERER'])) {
    $refererPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    $pathWithoutPrefix = preg_replace('#^/portail/?#', '', (string)$refererPath);
    $parts = explode('/', trim($pathWithoutPrefix, '/'));
    $candidate = strtolower(trim($parts[0] ?? ''));
    if (!empty($candidate) && !in_array($candidate, ['', 'index.html', 'index.php', 'reservation', 'api.php', 'sw.js', 'manifest.php'])) {
        $slug = $candidate;
    }
}

// 3. Valeurs par défaut (Portail général)
$appName = "Portail Voyageurs | Carré d'Hôtes";
$appShortName = "Carré d'Hôtes";
$appDesc = "Portail d'accueil, livrets de bienvenue et vidéos explicatives pour les voyageurs de la conciergerie Carré d'Hôtes à Lyon.";
$startUrl = "/portail/";

if (!empty($slug) && preg_match('/^[a-z0-9_\-]{1,100}$/', $slug)) {
    $prettySlug = ucwords(str_replace(['-', '_'], ' ', $slug));
    $appName = "{$prettySlug} | Carré d'Hôtes";
    $appShortName = $prettySlug;
    $startUrl = "/portail/{$slug}";
    $appDesc = "Livret d'accueil et vidéos des équipements pour votre séjour à {$prettySlug}.";

    try {
        $property = get_property_by_slug($slug);
        if ($property && !empty($property['name'])) {
            $propName = htmlspecialchars_decode($property['name'], ENT_QUOTES);
            $appName = "{$propName} | Carré d'Hôtes";
            $appShortName = $propName;
            $appDesc = !empty($property['subtitle']) 
                ? "{$propName} - {$property['subtitle']}" 
                : "Livret d'accueil et vidéos des équipements pour votre séjour à {$propName}.";
        }
    } catch (Exception $e) {
        // En cas d'indisponibilité DB, le repli sur $prettySlug garantit que l'app pointe vers le logement
    }
}

$manifest = [
    "id" => $startUrl,
    "name" => $appName,
    "short_name" => $appShortName,
    "description" => $appDesc,
    "start_url" => $startUrl,
    "scope" => "/portail/",
    "display" => "standalone",
    "display_override" => ["standalone", "minimal-ui"],
    "orientation" => "portrait-primary",
    "background_color" => "#28344c",
    "theme_color" => "#28344c",
    "lang" => "fr-FR",
    "dir" => "ltr",
    "categories" => ["travel", "lifestyle", "utilities"],
    "icons" => [
        [
            "src" => "assets/icons/icon-192.png?v=3",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/icons/icon-512.png?v=3",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/icons/icon-maskable-192.png?v=3",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "maskable"
        ],
        [
            "src" => "assets/icons/icon-maskable-512.png?v=3",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
