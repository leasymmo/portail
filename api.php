<?php
/**
 * API JSON Sécurisée - Portail Voyageurs Carré d'Hôtes
 * Passerelle read-only entre la base MySQL et le front-end HTML/JS
 */

// En-têtes de sécurité et JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/database.php';

// Extraction et validation stricte du slug (anti-injection & anti-DoS)
$slug = $_GET['slug'] ?? '';
$slug = strtolower(trim($slug));

// Validation du format du slug : uniquement lettres, chiffres, tirets et underscores (max 100 caractères)
if (!empty($slug) && !preg_match('/^[a-z0-9_\-]{1,100}$/', $slug)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Format d\'identifiant invalide'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!empty($slug)) {
        // Requête préparée PDO
        $property = get_property_by_slug($slug);
        
        if (!$property) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Logement introuvable',
                'slug' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Flux direct PDF (Passerelle même origine pour affichage vectoriel PDF.js sans blocage CORS)
        if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
            $lang = ($_GET['lang'] ?? 'fr') === 'en' ? 'en' : 'fr';
            $targetPdfUrl = ($lang === 'en') ? ($property['guide_en_url'] ?? '') : ($property['guide_fr_url'] ?? '');
            
            $scheme = strtolower((string)parse_url($targetPdfUrl, PHP_URL_SCHEME));
            $host = strtolower((string)parse_url($targetPdfUrl, PHP_URL_HOST));
            $isAllowedScheme = in_array($scheme, ['https', 'http'], true);
            $isBlockedHost = in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '169.254.169.254'], true)
                || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $host);

            if (!empty($targetPdfUrl) && filter_var($targetPdfUrl, FILTER_VALIDATE_URL) && $isAllowedScheme && !$isBlockedHost) {
                header_remove('Content-Type');
                header_remove('X-Frame-Options');
                header('Content-Type: application/pdf');
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
                header('Cache-Control: public, max-age=14400');

                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)\r\n",
                        'timeout' => 12,
                        'follow_location' => 1
                    ],
                    'ssl' => [
                        'verify_peer' => true
                    ]
                ]);

                $stream = @fopen($targetPdfUrl, 'rb', false, $ctx);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                    exit;
                }
            }
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Guide PDF indisponible']);
            exit;
        }

        $videos = get_videos_by_property_id($property['id']);
        $parkingGeneral = get_parking_by_property_id($property['id']);
        $parkingLocations = get_parking_locations_by_property_id($property['id']);

        $parkingData = null;
        if ($parkingGeneral !== null || !empty($parkingLocations)) {
            $parkingData = [
                'general' => $parkingGeneral,
                'locations' => [
                    'free_street' => array_values(array_filter($parkingLocations, fn($l) => $l['category'] === 'free_street')),
                    'free_spot' => array_values(array_filter($parkingLocations, fn($l) => $l['category'] === 'free_spot')),
                    'private_parking' => array_values(array_filter($parkingLocations, fn($l) => $l['category'] === 'private_parking')),
                    'tcl_pr' => array_values(array_filter($parkingLocations, fn($l) => $l['category'] === 'tcl_pr')),
                    'all' => $parkingLocations
                ]
            ];
        }
        $property['parking'] = $parkingData;

        echo json_encode([
            'success' => true,
            'type' => 'property',
            'data' => [
                'property' => $property,
                'videos' => $videos,
                'tutorials' => $videos, // Rétrocompatibilité
                'parking' => $parkingData
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // Liste de tous les logements actifs
        $properties = get_all_active_properties();
        echo json_encode([
            'success' => true,
            'type' => 'list',
            'data' => [
                'properties' => $properties
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    $isDebug = env('APP_DEBUG', false);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données',
        'details' => $isDebug ? $e->getMessage() : 'Impossible de récupérer les données.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    $isDebug = env('APP_DEBUG', false);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'details' => $isDebug ? $e->getMessage() : 'Une erreur inattendue est survenue.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
