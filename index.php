<?php
/**
 * ==============================================================================
 * PORTAIL VOYAGEURS - CONCIERGERIE CARRÉ D'HÔTES
 * Point d'entrée dynamique SPA & Progressive Web App (PWA)
 * ==============================================================================
 * Injecte dynamiquement au niveau serveur (SSR) le manifeste PWA et les métadonnées
 * du logement demandé, garantissant une installation PWA instantanée et ciblée
 * sur chaque hébergement sans dépendre de l'exécution du JavaScript côté client.
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/database.php';

// Extraction et analyse du chemin URL demandé
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Détection automatique du préfixe de base (/portail/dev/ ou /portail/)
$baseUri = (strpos((string)$path, '/portail/dev') === 0) ? '/portail/dev/' : '/portail/';
$pathWithoutPrefix = preg_replace('#^' . preg_quote($baseUri, '#') . '#', '', (string)$path);
$parts = explode('/', trim($pathWithoutPrefix, '/'));
$slug = strtolower(trim($parts[0] ?? ''));

// Exclusion des fichiers et routes spéciales
if (in_array($slug, ['', 'index.html', 'index.php', 'reservation', 'api.php', 'sw.js', 'manifest.php', 'test-db.php'])) {
    $slug = '';
}

// Valeurs par défaut (Portail général)
$appName = "Carré d'Hôtes";
$pageTitle = "Portail Voyageurs | Conciergerie Carré d'Hôtes";
$manifestUrl = "manifest.php";
$canonicalUrl = $baseUri;

if (!empty($slug) && preg_match('/^[a-z0-9_\-]{1,100}$/', $slug)) {
    $manifestUrl = "manifest.php?slug=" . urlencode($slug);
    $canonicalUrl = $baseUri . urlencode($slug);
    $prettySlug = ucwords(str_replace(['-', '_'], ' ', $slug));
    $appName = $prettySlug;
    $pageTitle = "{$prettySlug} - Portail Voyageurs | Carré d'Hôtes";

    try {
        $property = get_property_by_slug($slug);
        if ($property && !empty($property['name'])) {
            $propName = htmlspecialchars_decode($property['name'], ENT_QUOTES);
            $appName = $propName;
            $pageTitle = "{$propName} - Portail Voyageurs | Carré d'Hôtes";
        }
    } catch (Exception $e) {
        // En cas d'indisponibilité DB, préserve le slug dans le manifest
    }
}

// Lecture du squelette HTML
$htmlPath = APP_ROOT . '/index.html';
if (!file_exists($htmlPath)) {
    http_response_code(500);
    exit("Erreur : Fichier index.html introuvable.");
}

$html = file_get_contents($htmlPath);

// Remplacement dynamique du manifeste PWA
$html = preg_replace(
    '/<base href="[^"]*">/i',
    '<base href="' . htmlspecialchars($baseUri, ENT_QUOTES, 'UTF-8') . '">',
    $html
);

$html = preg_replace(
    '/<link id="app-manifest-link" rel="manifest" href="[^"]*">/i',
    '<link id="app-manifest-link" rel="manifest" href="' . htmlspecialchars($manifestUrl, ENT_QUOTES, 'UTF-8') . '">',
    $html
);

// Remplacement dynamique du titre de l'application mobile iOS / Android
$html = preg_replace(
    '/<meta name="apple-mobile-web-app-title" content="[^"]*">/i',
    '<meta name="apple-mobile-web-app-title" content="' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '">',
    $html
);

// Remplacement dynamique de la balise <title>
$html = preg_replace(
    '/<title>[^<]*<\/title>/i',
    '<title>' . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . '</title>',
    $html
);

// Remplacement dynamique du lien canonique et URL de démarrage
$html = preg_replace(
    '/<link id="app-canonical-link" rel="canonical" href="[^"]*">/i',
    '<link id="app-canonical-link" rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">',
    $html
);

// Envoi des en-têtes et du HTML
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo $html;
