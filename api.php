<?php
/**
 * API JSON Sécurisée - Portail Voyageurs Carré d'Hôtes
 * Passerelle read-only entre la base MySQL, l'API SuperHote et le front-end HTML/JS
 */

// En-têtes de sécurité et JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/database.php';

// Extraction et normalisation des paramètres
$reservationId = trim((string)($_GET['reservation_id'] ?? ($_GET['reservation'] ?? '')));
$slug = strtolower(trim((string)($_GET['slug'] ?? '')));

// Détection automatique si un slug numérique correspond en réalité à un reservation_id
if (empty($reservationId) && !empty($slug) && preg_match('/^[0-9]{5,20}$/', $slug)) {
    $reservationId = $slug;
    $slug = '';
}

// Validation stricte du format du reservation_id (chiffres uniquement, 1 à 20 caractères)
if (!empty($reservationId) && !preg_match('/^[0-9]{1,20}$/', $reservationId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Format d\'identifiant de réservation invalide'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validation du format du slug classique : uniquement lettres, chiffres, tirets et underscores (max 100 caractères)
if (!empty($slug) && !preg_match('/^[a-z0-9_\-]{1,100}$/', $slug)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Format d\'identifiant de logement invalide'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // =========================================================================
    // CAS 1 : ROUTE PERSONNALISÉE PAR RÉSERVATION (SUPERHOTE API)
    // =========================================================================
    if (!empty($reservationId)) {
        $superhoteClient = new \Cdh\Portal\Service\SuperhoteClient();
        $shResult = $superhoteClient->getReservation($reservationId);

        // 1.1 Réservation introuvable ou inaccessible
        if (!$shResult['ok']) {
            if ($shResult['reason'] === 'not_found') {
                http_response_code(404);
                echo json_encode([
                    'success'        => false,
                    'type'           => 'reservation_not_found',
                    'error'          => 'not_found',
                    'reservation_id' => $reservationId,
                    'message'        => 'Réservation introuvable sur les serveurs SuperHote.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($shResult['reason'] === 'unconfirmed') {
                // Réservation trouvée mais non confirmée (UNCONFIRMED, CANCELED, NO_SHOW, etc.)
                http_response_code(200);
                echo json_encode([
                    'success'        => false,
                    'type'           => 'reservation_unconfirmed',
                    'error'          => 'unconfirmed',
                    'status_code'    => $shResult['status_code'],
                    'status_label'   => $shResult['status_label'],
                    'reservation_id' => $reservationId,
                    'reservation'    => $shResult['reservation'] ?? null,
                    'message'        => 'Cette réservation n\'est pas confirmée.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($shResult['reason'] === 'rate_limit') {
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'error'   => 'rate_limit',
                    'message' => $shResult['message']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(502);
            echo json_encode([
                'success' => false,
                'error'   => 'api_error',
                'message' => $shResult['message']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1.2 Réservation confirmée : Recherche du logement par rental_id
        $rentalId = $shResult['rental_id'];
        $property = get_property_by_rental_id($rentalId);

        if (!$property) {
            http_response_code(404);
            echo json_encode([
                'success'        => false,
                'type'           => 'property_not_found_for_rental',
                'error'          => 'property_not_found',
                'rental_id'      => $rentalId,
                'reservation_id' => $reservationId,
                'message'        => "Aucun hébergement actif du portail n'est associé à l'identifiant SuperHote '{$rentalId}'."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Flux direct PDF (Passerelle même origine pour affichage vectoriel PDF.js)
        if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
            serve_pdf_guide($property);
            exit;
        }

        $videos = get_videos_by_property_id((int)$property['id']);
        $parkingGeneral = get_parking_by_property_id((int)$property['id']);
        $parkingStructured = \Cdh\Portal\Repository\PropertyRepository::findParkingStructuredByPropertyId((int)$property['id']);

        $parkingData = null;
        if ($parkingGeneral !== null || !empty($parkingStructured['all'])) {
            $parkingData = [
                'general'   => $parkingGeneral,
                'locations' => [
                    'free_spots'      => $parkingStructured['free_spots'] ?? [],
                    'private_lots'    => $parkingStructured['private_lots'] ?? [],
                    'tcl_prs'         => $parkingStructured['tcl_prs'] ?? [],
                    'free_street'     => $parkingStructured['free_street'] ?? [],
                    'free_spot'       => $parkingStructured['free_spot'] ?? [],
                    'private_parking' => $parkingStructured['private_parking'] ?? [],
                    'tcl_pr'          => $parkingStructured['tcl_pr'] ?? [],
                    'all'             => $parkingStructured['all'] ?? []
                ]
            ];
        }
        $property['parking'] = $parkingData;

        echo json_encode([
            'success' => true,
            'type'    => 'property',
            'mode'    => 'reservation',
            'data'    => [
                'reservation' => $shResult['reservation'],
                'property'    => $property,
                'videos'      => $videos,
                'tutorials'   => $videos, // Rétrocompatibilité
                'parking'     => $parkingData
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // CAS 2 : ROUTE STANDARD PAR SLOGAN DE LOGEMENT (VERSION NON PERSONNALISÉE)
    // =========================================================================
    if (!empty($slug)) {
        $property = get_property_by_slug($slug);
        
        if (!$property) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error'   => 'Logement introuvable',
                'slug'    => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Flux direct PDF (Passerelle même origine pour affichage vectoriel PDF.js sans blocage CORS)
        if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
            serve_pdf_guide($property);
            exit;
        }

        $videos = get_videos_by_property_id((int)$property['id']);
        $parkingGeneral = get_parking_by_property_id((int)$property['id']);
        $parkingStructured = \Cdh\Portal\Repository\PropertyRepository::findParkingStructuredByPropertyId((int)$property['id']);

        $parkingData = null;
        if ($parkingGeneral !== null || !empty($parkingStructured['all'])) {
            $parkingData = [
                'general'   => $parkingGeneral,
                'locations' => [
                    'free_spots'      => $parkingStructured['free_spots'] ?? [],
                    'private_lots'    => $parkingStructured['private_lots'] ?? [],
                    'tcl_prs'         => $parkingStructured['tcl_prs'] ?? [],
                    'free_street'     => $parkingStructured['free_street'] ?? [],
                    'free_spot'       => $parkingStructured['free_spot'] ?? [],
                    'private_parking' => $parkingStructured['private_parking'] ?? [],
                    'tcl_pr'          => $parkingStructured['tcl_pr'] ?? [],
                    'all'             => $parkingStructured['all'] ?? []
                ]
            ];
        }
        $property['parking'] = $parkingData;

        echo json_encode([
            'success' => true,
            'type'    => 'property',
            'mode'    => 'standard',
            'data'    => [
                'property'  => $property,
                'videos'    => $videos,
                'tutorials' => $videos, // Rétrocompatibilité
                'parking'   => $parkingData
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // CAS 3 : LISTE DE TOUS LES LOGEMENTS ACTIFS (PAGE D'ACCUEIL PORTAIL)
    // =========================================================================
    $properties = get_all_active_properties();
    echo json_encode([
        'success' => true,
        'type'    => 'list',
        'data'    => [
            'properties' => $properties
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    $isDebug = env('APP_DEBUG', false);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur de base de données',
        'details' => $isDebug ? $e->getMessage() : 'Impossible de récupérer les données.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    $isDebug = env('APP_DEBUG', false);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur serveur',
        'details' => $isDebug ? $e->getMessage() : 'Une erreur inattendue est survenue.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Passerelle sécurisée d'affichage PDF pour PDF.js (Anti-SSRF & Anti-CORS)
 */
function serve_pdf_guide(array $property): void {
    $lang = ($_GET['lang'] ?? 'fr') === 'en' ? 'en' : 'fr';
    $guideFr = $property['guide_fr_url'] ?? '';
    $guideEn = $property['guide_en_url'] ?? '';
    $targetPdfUrl = ($lang === 'en') ? ($guideEn ?: $guideFr) : ($guideFr ?: $guideEn);
    
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
                'method'          => 'GET',
                'header'          => "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)\r\n",
                'timeout'         => 12,
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
