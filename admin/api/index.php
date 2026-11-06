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

// Détection dépassement de taille maximale d'upload par PHP (uniquement pour les requêtes multipart/form-data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($method === 'POST'
    && stripos($contentType, 'multipart/form-data') !== false
    && empty($_POST)
    && empty($_FILES)
    && isset($_SERVER['CONTENT_LENGTH'])
    && (int)$_SERVER['CONTENT_LENGTH'] > 0
) {
    http_response_code(413);
    echo json_encode([
        'ok'    => false,
        'error' => 'Le fichier envoyé est trop volumineux pour la configuration actuelle du serveur (limite: ' . ini_get('post_max_size') . ').'
    ], JSON_UNESCAPED_UNICODE);
    exit;
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
            $settings = $repo->getSettings();
            echo json_encode([
                'ok'            => true,
                'user'          => $currentUser,
                'csrf_token'    => Auth::getCsrfToken(),
                'stats'         => $stats,
                'properties'    => $properties,
                'recent_logs'   => $recentLogs,
                'settings'      => $settings,
                'upload_limits' => [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size'       => ini_get('post_max_size'),
                    'max_guide_mb'        => 50,
                    'max_video_mb'        => 250,
                ]
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

            $name = trim((string)($data['name'] ?? ''));
            $slug = trim((string)($data['slug'] ?? ''));

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Le nom du logement est obligatoire.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (empty($slug)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Le slug unique du logement est obligatoire pour valider le formulaire.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            require_once dirname(__DIR__, 2) . '/src/Admin/MediaUploader.php';
            $data['slug'] = \Cdh\Portal\Admin\MediaUploader::sanitizeSlug($slug);
            if (empty($data['slug'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Le format du slug est invalide (doit contenir des caractères alphanumériques ou tirets).'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Vérification de l'unicité du slug
            $existing = $repo->findPropertyBySlug($data['slug']);
            if ($existing && (int)$existing['id'] !== $id) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => "Le slug '{$data['slug']}' est déjà utilisé par un autre hébergement."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($id > 0) {
                $ok = $repo->updateProperty($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Hébergement mis à jour avec succès.'], JSON_UNESCAPED_UNICODE);
            } else {
                $newId = $repo->createProperty($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Hébergement créé avec succès.'], JSON_UNESCAPED_UNICODE);
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

            $propertyId = (int)($data['property_id'] ?? 0);
            $titleFr = trim((string)($data['title_fr'] ?? ''));
            $slug = trim((string)($data['slug'] ?? ''));
            $videoUrl = trim((string)($data['video_url'] ?? ''));

            if ($propertyId <= 0 || empty($titleFr)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Veuillez sélectionner un hébergement et renseigner le titre du tutoriel.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (empty($slug)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Le slug unique de la vidéo est obligatoire pour valider le formulaire.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            require_once dirname(__DIR__, 2) . '/src/Admin/MediaUploader.php';
            $data['slug'] = \Cdh\Portal\Admin\MediaUploader::sanitizeSlug($slug);
            if (empty($data['slug'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Le format du slug est invalide (doit contenir des caractères alphanumériques ou tirets).'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (empty($videoUrl)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'L\'URL ou le fichier de la vidéo est obligatoire.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($id > 0) {
                $ok = $repo->updateVideo($id, $data);
                echo json_encode(['ok' => $ok, 'id' => $id, 'message' => 'Tutoriel vidéo mis à jour avec succès.'], JSON_UNESCAPED_UNICODE);
            } else {
                $newId = $repo->createVideo($data);
                echo json_encode(['ok' => true, 'id' => $newId, 'message' => 'Tutoriel vidéo ajouté avec succès.'], JSON_UNESCAPED_UNICODE);
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

        // ----------------------------------------------------------------------
        // PARAMÈTRES IA & CONFIGURATION
        // ----------------------------------------------------------------------
        case 'settings_get':
            echo json_encode([
                'ok' => true,
                'settings' => $repo->getSettings()
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'settings_save':
            Auth::requireSuperAdmin(true);
            $settingsData = $body['settings'] ?? $_POST;
            $ok = $repo->saveSettings($settingsData);
            echo json_encode([
                'ok' => $ok,
                'settings' => $repo->getSettings(),
                'message' => 'Configuration IA mise à jour avec succès.'
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'test_ai':
            $provider = $body['provider'] ?? ($_POST['provider'] ?? 'gemini');
            $apiKey = trim((string)($body['api_key'] ?? ($_POST['api_key'] ?? '')));
            $model = !empty($body['model']) ? trim((string)$body['model']) : null;

            if (empty($apiKey) || strpos($apiKey, '•') !== false) {
                $db = \Cdh\Portal\Database::getConnection();
                $stmt = $db->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :k LIMIT 1');
                $stmt->execute(['k' => 'ai_api_key_' . $provider]);
                $apiKey = (string)$stmt->fetchColumn();
            }

            if (empty($apiKey)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Veuillez saisir une clé API pour tester la connexion.']);
                exit;
            }

            require_once dirname(__DIR__, 2) . '/src/Admin/AiTranslator.php';
            $testRes = \Cdh\Portal\Admin\AiTranslator::testConnection($provider, $apiKey, $model);
            if (!$testRes['ok']) {
                http_response_code(400);
            }
            echo json_encode($testRes, JSON_UNESCAPED_UNICODE);
            exit;

        case 'models_list':
            $provider = $body['provider'] ?? ($_GET['provider'] ?? ($_POST['provider'] ?? 'gemini'));
            $apiKey = trim((string)($body['api_key'] ?? ($_GET['api_key'] ?? ($_POST['api_key'] ?? ''))));

            if (empty($apiKey) || strpos($apiKey, '•') !== false) {
                $db = \Cdh\Portal\Database::getConnection();
                $stmt = $db->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :k LIMIT 1');
                $stmt->execute(['k' => 'ai_api_key_' . $provider]);
                $apiKey = (string)$stmt->fetchColumn();
            }

            require_once dirname(__DIR__, 2) . '/src/Admin/AiTranslator.php';
            $models = \Cdh\Portal\Admin\AiTranslator::listAvailableModels($provider, $apiKey);
            $settings = $repo->getSettings();
            $activeModel = $settings['ai_model_' . $provider] ?? (\Cdh\Portal\Admin\AiTranslator::DEFAULT_MODELS[$provider] ?? '');

            echo json_encode([
                'ok'           => true,
                'provider'     => $provider,
                'active_model' => $activeModel,
                'models'       => $models
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'translate_preview':
            $type = $body['type'] ?? ($_POST['type'] ?? '');
            $inputData = $body['data'] ?? ($_POST['data'] ?? []);
            if (!is_array($inputData)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Données de formulaire invalides.']);
                exit;
            }

            $db = \Cdh\Portal\Database::getConnection();
            require_once dirname(__DIR__, 2) . '/src/Admin/AiTranslator.php';

            if ($type === 'parking_general') {
                $result = \Cdh\Portal\Admin\AiTranslator::translateParkingGeneral($db, $inputData, true, true);
            } elseif ($type === 'parking_location') {
                $result = \Cdh\Portal\Admin\AiTranslator::translateParkingLocation($db, $inputData, true, true);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Type de traduction non supporté : ' . $type]);
                exit;
            }

            echo json_encode([
                'ok'   => true,
                'type' => $type,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ----------------------------------------------------------------------
        // TÉLÉVERSEMENT DE MÉDIAS (GUIDES PDF & VIDÉOS)
        // ----------------------------------------------------------------------
        case 'upload_media':
            if (empty($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Aucun fichier reçu dans la requête.']);
                exit;
            }

            $category = (string)($_POST['category'] ?? 'guide');
            $params = [
                'property_slug' => $_POST['property_slug'] ?? '',
                'video_slug'    => $_POST['video_slug'] ?? '',
                'lang'          => $_POST['lang'] ?? 'fr',
            ];

            require_once dirname(__DIR__, 2) . '/src/Admin/MediaUploader.php';
            try {
                $uploadResult = \Cdh\Portal\Admin\MediaUploader::handleUpload($_FILES['file'], $category, $params);
            } catch (RuntimeException $e) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            \Cdh\Portal\Admin\ActivityLogger::log(
                'upload', 
                $category, 
                null, 
                "Téléversement {$category} vers Cloudflare R2 : {$uploadResult['object_key']} ({$uploadResult['size_formatted']})"
            );

            echo json_encode($uploadResult, JSON_UNESCAPED_UNICODE);
            exit;

        case 'r2_test':
            require_once dirname(__DIR__, 2) . '/src/Admin/MediaUploader.php';
            $savedConfig = \Cdh\Portal\Admin\MediaUploader::getR2Config();

            $config = [
                'account_id'        => trim((string)($body['account_id'] ?? ($_POST['account_id'] ?? ''))),
                'access_key_id'     => trim((string)($body['access_key_id'] ?? ($_POST['access_key_id'] ?? ''))),
                'secret_access_key' => trim((string)($body['secret_access_key'] ?? ($_POST['secret_access_key'] ?? ''))),
                'bucket_guides'     => trim((string)($body['bucket_guides'] ?? ($_POST['bucket_guides'] ?? ''))),
                'bucket_videos'     => trim((string)($body['bucket_videos'] ?? ($_POST['bucket_videos'] ?? ''))),
            ];

            // Repli sur la configuration enregistrée si un champ n'est pas fourni ou masqué
            if (empty($config['account_id'])) {
                $config['account_id'] = $savedConfig['account_id'] ?? '';
            }
            if (empty($config['access_key_id'])) {
                $config['access_key_id'] = $savedConfig['access_key_id'] ?? '';
            }
            if (empty($config['secret_access_key']) || strpos($config['secret_access_key'], '•') !== false) {
                $config['secret_access_key'] = $savedConfig['secret_access_key'] ?? '';
            }
            if (empty($config['bucket_guides'])) {
                $config['bucket_guides'] = $savedConfig['bucket_guides'] ?? 'carredhotes-guides';
            }
            if (empty($config['bucket_videos'])) {
                $config['bucket_videos'] = $savedConfig['bucket_videos'] ?? 'carredhotes-videos';
            }

            $testRes = \Cdh\Portal\Admin\MediaUploader::testR2Connection($config);
            if (!$testRes['ok']) {
                http_response_code(400);
            }
            echo json_encode($testRes, JSON_UNESCAPED_UNICODE);
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
