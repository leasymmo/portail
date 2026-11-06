<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * API CENTRALE DU BACK-OFFICE (CRUD & ACTIONS)
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/Admin/Auth.php';
require_once dirname(__DIR__, 2) . '/src/Admin/ActivityLogger.php';
require_once dirname(__DIR__, 2) . '/src/Admin/AdminRepository.php';

use Cdh\Portal\Admin\Auth;
use Cdh\Portal\Admin\ActivityLogger;
use Cdh\Portal\Admin\AdminRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Authentification requise pour tout appel à cette API
$currentUser = Auth::requireAuth(true);
$repo = new AdminRepository();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// Lecture du corps JSON si présent
$rawInput = file_get_contents('php://input');
$body = [];
if ($rawInput !== false && trim($rawInput) !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

// Vérification CSRF sur toutes les actions modifiantes
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    Auth::checkCsrf();
}

try {
    switch ($action) {

        // ----------------------------------------------------------------------
        // INITIALISATION GLOBALE DU DASHBOARD (Charge tout en 1 requête rapide)
        // ----------------------------------------------------------------------
        case 'init':
            $stats = $repo->getDashboardStats();
            $properties = $repo->getAllProperties();
            $recentLogs = ActivityLogger::getRecent(15);
            echo json_encode([
                'ok'         => true,
                'user'       => $currentUser,
                'csrf_token' => Auth::getCsrfToken(),
                'stats'      => $stats,
                'properties' => $properties,
                'recent_logs'=> $recentLogs
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ----------------------------------------------------------------------
        // STATISTIQUES & LOGS
        // ----------------------------------------------------------------------
        case 'stats':
            echo json_encode([
                'ok'    => true,
                'stats' => $repo->getDashboardStats()
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'logs':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            echo json_encode([
                'ok'   => true,
                'logs' => ActivityLogger::getRecent($limit)
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ----------------------------------------------------------------------
        // HÉBERGEMENTS (PROPERTIES)
        // ----------------------------------------------------------------------
        case 'properties':
            echo json_encode([
                'ok'         => true,
                'properties' => $repo->getAllProperties()
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'property_get':
            $id = (int)($_GET['id'] ?? ($body['id'] ?? 0));
            $property = $repo->findPropertyById($id);
            if (!$property) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Logement introuvable']);
                exit;
            }
            $videos = $repo->getVideosByProperty($id);
            $parkingGeneral = $repo->getParkingByProperty($id);
            $parkingLocations = $repo->getParkingLocationsByProperty($id);

            echo json_encode([
                'ok'                => true,
                'property'          => $property,
                'videos'            => $videos,
                'parking_general'   => $parkingGeneral,
                'parking_locations' => $parkingLocations
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'property_save':
            $data = $body['property'] ?? $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if (empty($data['name']) || empty($data['slug'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Le nom et le slug sont obligatoires.']);
                exit;
            }

            // Vérification de l'unicité du slug
            $existing = $repo->findPropertyBySlug($data['slug']);
            if ($existing && (int)$existing['id'] !== $id) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => "Le slug '{$data['slug']}' est déjà utilisé par un autre hébergement."]);
                exit;
            }

            if ($id > 0) {
                $ok = $repo->updateProperty($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Hébergement mis à jour avec succès.']);
            } else {
                $newId = $repo->createProperty($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Hébergement créé avec succès.']);
            }
            exit;

        case 'property_toggle':
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->togglePropertyActive($id);
            echo json_encode(['ok' => $ok]);
            exit;

        case 'property_delete':
            if ($currentUser['role'] === 'editor') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Suppression non autorisée pour le rôle éditeur.']);
                exit;
            }
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->deleteProperty($id);
            echo json_encode(['ok' => $ok, 'message' => 'Hébergement supprimé.']);
            exit;

        // ----------------------------------------------------------------------
        // VIDÉOS TUTORIELS
        // ----------------------------------------------------------------------
        case 'videos_get':
            $propertyId = (int)($_GET['property_id'] ?? 0);
            echo json_encode([
                'ok'     => true,
                'videos' => $repo->getVideosByProperty($propertyId)
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'video_save':
            $data = $body['video'] ?? $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if (empty($data['property_id']) || empty($data['title_fr']) || empty($data['video_url'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Veuillez renseigner le logement, le titre français et l\'URL de la vidéo.']);
                exit;
            }

            // Génération automatique du slug s'il n'est pas fourni
            if (empty($data['slug'])) {
                $data['slug'] = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $data['title_fr']))));
            }

            if ($id > 0) {
                $ok = $repo->updateVideo($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Vidéo mise à jour.']);
            } else {
                $newId = $repo->createVideo($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Vidéo ajoutée.']);
            }
            exit;

        case 'video_reorder':
            $orderList = $body['order'] ?? [];
            if (!is_array($orderList)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Données d\'ordre invalides.']);
                exit;
            }
            $ok = $repo->reorderVideos($orderList);
            echo json_encode(['ok' => $ok]);
            exit;

        case 'video_toggle':
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->toggleVideoActive($id);
            echo json_encode(['ok' => $ok]);
            exit;

        case 'video_delete':
            if ($currentUser['role'] === 'editor') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Suppression non autorisée pour le rôle éditeur.']);
                exit;
            }
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->deleteVideo($id);
            echo json_encode(['ok' => $ok, 'message' => 'Vidéo supprimée.']);
            exit;

        // ----------------------------------------------------------------------
        // STATIONNEMENT (RÈGLES GÉNÉRALES & EMPLACEMENTS)
        // ----------------------------------------------------------------------
        case 'parking_get':
            $propertyId = (int)($_GET['property_id'] ?? 0);
            echo json_encode([
                'ok'        => true,
                'general'   => $repo->getParkingByProperty($propertyId),
                'locations' => $repo->getParkingLocationsByProperty($propertyId)
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'parking_general_save':
            $propertyId = (int)($body['property_id'] ?? ($_POST['property_id'] ?? 0));
            $data = $body['general'] ?? $_POST;
            if ($propertyId <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Identifiant de logement requis.']);
                exit;
            }
            $ok = $repo->saveParkingGeneral($propertyId, $data);
            echo json_encode(['ok' => $ok, 'message' => 'Informations générales de stationnement enregistrées.']);
            exit;

        case 'parking_location_save':
            $data = $body['location'] ?? $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if (empty($data['property_id']) || empty($data['name_fr']) || empty($data['category'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Le logement, le nom du parking et la catégorie sont obligatoires.']);
                exit;
            }

            if ($id > 0) {
                $ok = $repo->updateParkingLocation($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Emplacement mis à jour.']);
            } else {
                $newId = $repo->createParkingLocation($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Emplacement ajouté.']);
            }
            exit;

        case 'parking_location_reorder':
            $orderList = $body['order'] ?? [];
            if (!is_array($orderList)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Données d\'ordre invalides.']);
                exit;
            }
            $ok = $repo->reorderParkingLocations($orderList);
            echo json_encode(['ok' => $ok]);
            exit;

        case 'parking_location_toggle':
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->toggleParkingLocationActive($id);
            echo json_encode(['ok' => $ok]);
            exit;

        case 'parking_location_delete':
            if ($currentUser['role'] === 'editor') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Suppression non autorisée pour le rôle éditeur.']);
                exit;
            }
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            $ok = $repo->deleteParkingLocation($id);
            echo json_encode(['ok' => $ok, 'message' => 'Emplacement supprimé.']);
            exit;

        // ----------------------------------------------------------------------
        // UTILISATEURS ADMINISTRATEURS
        // ----------------------------------------------------------------------
        case 'users':
            Auth::requireSuperAdmin(true);
            echo json_encode([
                'ok'    => true,
                'users' => $repo->getAllUsers()
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'user_save':
            Auth::requireSuperAdmin(true);
            $data = $body['user'] ?? $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if (empty($data['email']) || empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'L\'email et le nom sont requis.']);
                exit;
            }

            if ($id === 0 && empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Un mot de passe initial est requis pour créer un utilisateur.']);
                exit;
            }

            if ($id > 0) {
                $ok = $repo->updateUser($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Utilisateur mis à jour.']);
            } else {
                $newId = $repo->createUser($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Utilisateur créé.']);
            }
            exit;

        case 'user_delete':
            Auth::requireSuperAdmin(true);
            $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
            if ($id === $currentUser['id']) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Impossible de supprimer votre propre compte administrateur.']);
                exit;
            }
            $ok = $repo->deleteUser($id);
            echo json_encode(['ok' => $ok, 'message' => 'Utilisateur supprimé.']);
            exit;

        case 'change_password':
            $oldPassword = (string)($body['old_password'] ?? ($_POST['old_password'] ?? ''));
            $newPassword = (string)($body['new_password'] ?? ($_POST['new_password'] ?? ''));
            $res = $repo->updateSelfPassword($currentUser['id'], $oldPassword, $newPassword);
            if ($res['ok']) {
                echo json_encode(['ok' => true, 'message' => 'Votre mot de passe a été modifié avec succès.']);
            } else {
                http_response_code(400);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            }
            exit;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Action inconnue : ' . $action]);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Une erreur interne est survenue : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
