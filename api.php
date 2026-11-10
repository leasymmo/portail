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
    if (isset($_GET['apply_villa_parking']) && $_GET['apply_villa_parking'] === 'cdh_2026_villa') {
        $pdo = get_db_connection();
        $pdo->beginTransaction();

        try {
            // 1. Adresse dans properties
            $pdo->prepare("UPDATE properties SET address = :addr WHERE id = 19")->execute([
                ':addr' => '82 avenue du Carreau, 69330 Meyzieu'
            ]);

            // 2. Règles générales property_parkings
            $stmtPp = $pdo->prepare("
                INSERT INTO property_parkings (
                    property_id, intro_fr, intro_en, important_rule_fr, important_rule_en,
                    free_parking_notes_fr, free_parking_notes_en, street_parking_info_fr, street_parking_info_en,
                    street_parking_schedule_fr, street_parking_schedule_en, street_parking_rates_fr, street_parking_rates_en,
                    street_parking_app, tcl_conditions_fr, tcl_conditions_en
                ) VALUES (
                    19, :intro_fr, :intro_en, :rule_fr, :rule_en,
                    :free_notes_fr, :free_notes_en, :street_info_fr, :street_info_en,
                    :sched_fr, :sched_en, :rates_fr, :rates_en,
                    NULL, :tcl_fr, :tcl_en
                ) ON DUPLICATE KEY UPDATE
                    intro_fr = VALUES(intro_fr), intro_en = VALUES(intro_en),
                    important_rule_fr = VALUES(important_rule_fr), important_rule_en = VALUES(important_rule_en),
                    free_parking_notes_fr = VALUES(free_parking_notes_fr), free_parking_notes_en = VALUES(free_parking_notes_en),
                    street_parking_info_fr = VALUES(street_parking_info_fr), street_parking_info_en = VALUES(street_parking_info_en),
                    street_parking_schedule_fr = VALUES(street_parking_schedule_fr), street_parking_schedule_en = VALUES(street_parking_schedule_en),
                    street_parking_rates_fr = VALUES(street_parking_rates_fr), street_parking_rates_en = VALUES(street_parking_rates_en),
                    tcl_conditions_fr = VALUES(tcl_conditions_fr), tcl_conditions_en = VALUES(tcl_conditions_en)
            ");
            $stmtPp->execute([
                ':intro_fr' => 'Solutions de stationnement pour votre séjour à la Villa Soleil Printemps au 82 avenue du Carreau (Meyzieu).',
                ':intro_en' => 'Parking solutions for your stay at Villa Soleil Printemps at 82 avenue du Carreau (Meyzieu).',
                ':rule_fr' => "La villa dispose de deux places de stationnement privatives et sécurisées à l'intérieur de la propriété (accès par le portail automatique), équipées de deux bornes de recharge pour véhicules électriques. Le stationnement est également entièrement gratuit dans la rue.",
                ':rule_en' => 'The villa features two private and secure on-site parking spaces inside the property (access via automatic gate), equipped with two electric vehicle charging stations. Free on-street parking is also available.',
                ':free_notes_fr' => "Le stationnement est entièrement gratuit à l'intérieur de la villa comme sur la voie publique (Avenue du Carreau et rues adjacentes, sans limitation de durée).",
                ':free_notes_en' => 'Parking is completely free both inside the villa premises and on public streets (Avenue du Carreau and surrounding streets, no time limits).',
                ':street_info_fr' => 'Le stationnement sur voirie à Meyzieu est gratuit et non payant dans tout ce secteur résidentiel.',
                ':street_info_en' => 'On-street parking in Meyzieu is free and unmetered throughout this residential area.',
                ':sched_fr' => 'Gratuit 24h/24 et 7j/7',
                ':sched_en' => 'Free 24/7',
                ':rates_fr' => 'Gratuit (zone non payante)',
                ':rates_en' => 'Free (unmetered zone)',
                ':tcl_fr' => 'Pour vous rendre facilement au centre de Lyon en Tramway T3 (Part-Dieu en 20 min), le Parc Relais TCL de Meyzieu Gare est gratuit avec un titre de transport TCL aller-retour validé dans la journée.',
                ':tcl_en' => 'To easily reach downtown Lyon via Tramway T3 (Part-Dieu in 20 min), the TCL Meyzieu Gare Park & Ride is free with a same-day validated round-trip TCL ticket.'
            ]);

            // 3. Spots gratuits
            $stmtSpot = $pdo->prepare("
                INSERT INTO parking_free_spots (spot_type, name_fr, name_en, address, notes_fr, notes_en)
                VALUES (:type, :n_fr, :n_en, :addr, :notes_fr, :notes_en)
            ");

            // Spot privatif
            $stmtSpot->execute([
                ':type' => 'spot',
                ':n_fr' => '2 places privatives dans la propriété (avec 2 bornes de recharge électrique)',
                ':n_en' => '2 private on-site parking spaces (with 2 EV charging stations)',
                ':addr' => '82 avenue du Carreau, 69330 Meyzieu',
                ':notes_fr' => 'Stationnement sécurisé dans la cour de la villa derrière le portail motorisé. Deux bornes de recharge pour véhicules électriques sont gracieusement mises à votre disposition.',
                ':notes_en' => 'Secure parking inside the villa courtyard behind the automated gate. Two EV charging stations are provided free of charge for your convenience.'
            ]);
            $spotVillaId = (int)$pdo->lastInsertId();

            // Avenue du Carreau
            $stmtSpot->execute([
                ':type' => 'street',
                ':n_fr' => 'Avenue du Carreau',
                ':n_en' => 'Avenue du Carreau',
                ':addr' => 'Avenue du Carreau, 69330 Meyzieu',
                ':notes_fr' => "Gratuit 7j/7 et 24h/24 le long de l'avenue.",
                ':notes_en' => 'Free 24/7 along the avenue.'
            ]);
            $streetCarreauId = (int)$pdo->lastInsertId();

            // Chemin de Pommier
            $stmtSpot->execute([
                ':type' => 'street',
                ':n_fr' => 'Chemin de Pommier',
                ':n_en' => 'Chemin de Pommier',
                ':addr' => 'Chemin de Pommier, 69330 Meyzieu',
                ':notes_fr' => 'Gratuit 7j/7 et 24h/24.',
                ':notes_en' => 'Free 24/7.'
            ]);
            $streetPommierId = (int)$pdo->lastInsertId();

            // Rue Pierre-Brossolette
            $stmtSpot->execute([
                ':type' => 'street',
                ':n_fr' => 'Rue Pierre-Brossolette',
                ':n_en' => 'Rue Pierre-Brossolette',
                ':addr' => 'Rue Pierre-Brossolette, 69330 Meyzieu',
                ':notes_fr' => 'Gratuit 7j/7 et 24h/24.',
                ':notes_en' => 'Free 24/7.'
            ]);
            $streetBrossoletteId = (int)$pdo->lastInsertId();

            // 4. Parc Relais TCL Meyzieu Gare
            $stmtTcl = $pdo->prepare("
                INSERT INTO parking_tcl_prs (name_fr, name_en, address, capacity, opening_hours_fr, opening_hours_en, booking_url, notes_fr, notes_en)
                VALUES (:n_fr, :n_en, :addr, :cap, :hours_fr, :hours_en, :url, :notes_fr, :notes_en)
            ");
            $stmtTcl->execute([
                ':n_fr' => 'Parking Relais TCL - Meyzieu Gare',
                ':n_en' => 'TCL Park & Ride - Meyzieu Gare',
                ':addr' => '10 rue de la Gare, 69330 Meyzieu',
                ':cap' => '104 places',
                ':hours_fr' => 'Tous les jours de 4h30 à 1h',
                ':hours_en' => 'Every day from 4:30am to 1am',
                ':url' => 'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures',
                ':notes_fr' => "Réservé aux usagers du réseau TCL munis d'un titre de transport aller-retour validé dans la journée. Connexion directe Tramway T3 vers Part-Dieu.",
                ':notes_en' => 'Reserved for TCL network users holding a validated same-day round-trip transit ticket. Direct Tram T3 to Part-Dieu.'
            ]);
            $tclMeyzieuId = (int)$pdo->lastInsertId();

            // 5. Associations
            $stmtAssoc = $pdo->prepare("
                INSERT INTO property_parking_associations (
                    property_id, category, parking_item_id, walking_time_fr, walking_time_en,
                    transit_time_fr, transit_time_en, how_to_reach_fr, how_to_reach_en, display_order, active
                ) VALUES (
                    19, :cat, :item_id, :w_fr, :w_en,
                    :t_fr, :t_en, :reach_fr, :reach_en, :ord, 1
                )
            ");

            // Assoc spot privatif
            $stmtAssoc->execute([
                ':cat' => 'free_spot',
                ':item_id' => $spotVillaId,
                ':w_fr' => 'Sur place (dans la propriété)',
                ':w_en' => 'On site (inside the property)',
                ':t_fr' => null,
                ':t_en' => null,
                ':reach_fr' => null,
                ':reach_en' => null,
                ':ord' => 1
            ]);

            // Assoc Avenue du Carreau
            $stmtAssoc->execute([
                ':cat' => 'free_street',
                ':item_id' => $streetCarreauId,
                ':w_fr' => 'Au pied de la villa',
                ':w_en' => 'Right outside the villa',
                ':t_fr' => null,
                ':t_en' => null,
                ':reach_fr' => null,
                ':reach_en' => null,
                ':ord' => 2
            ]);

            // Assoc Chemin de Pommier
            $stmtAssoc->execute([
                ':cat' => 'free_street',
                ':item_id' => $streetPommierId,
                ':w_fr' => 'Environ 2 min à pied (150 m)',
                ':w_en' => 'Approx. 2 min walk (150 m)',
                ':t_fr' => null,
                ':t_en' => null,
                ':reach_fr' => null,
                ':reach_en' => null,
                ':ord' => 3
            ]);

            // Assoc Rue Pierre-Brossolette
            $stmtAssoc->execute([
                ':cat' => 'free_street',
                ':item_id' => $streetBrossoletteId,
                ':w_fr' => 'Environ 2 min à pied (150 m)',
                ':w_en' => 'Approx. 2 min walk (150 m)',
                ':t_fr' => null,
                ':t_en' => null,
                ':reach_fr' => null,
                ':reach_en' => null,
                ':ord' => 4
            ]);

            // Assoc TCL Meyzieu Gare
            $stmtAssoc->execute([
                ':cat' => 'tcl_pr',
                ':item_id' => $tclMeyzieuId,
                ':w_fr' => 'Environ 9 min à pied (700 m)',
                ':w_en' => 'Approx. 9 min walk (700 m)',
                ':t_fr' => '3 min en bus 85 ou 9 min à pied',
                ':t_en' => '3 min by bus 85 or 9 min walk',
                ':reach_fr' => "Prendre l'Avenue du Carreau puis la Rue de la Gare jusqu'à la station Tramway T3 Meyzieu Gare.",
                ':reach_en' => 'Follow Avenue du Carreau then Rue de la Gare to Tramway T3 Meyzieu Gare station.',
                ':ord' => 1
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'type' => 'villa_parking_applied']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

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
        $parkingStructured = \Cdh\Portal\Repository\PropertyRepository::findParkingStructuredByPropertyId($property['id']);

        $parkingData = null;
        if ($parkingGeneral !== null || !empty($parkingStructured['all'])) {
            $parkingData = [
                'general' => $parkingGeneral,
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
