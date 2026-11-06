<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * ENDPOINT D'AUTHENTIFICATION API (LOGIN / LOGOUT / SESSION)
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/Admin/Auth.php';
require_once dirname(__DIR__, 2) . '/src/Admin/ActivityLogger.php';

use Cdh\Portal\Admin\Auth;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$rawInput = file_get_contents('php://input');
$body = [];
if ($rawInput !== false && trim($rawInput) !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

switch ($action) {
    case 'login':
        $email = (string)($body['email'] ?? ($_POST['email'] ?? ''));
        $password = (string)($body['password'] ?? ($_POST['password'] ?? ''));
        $remember = !empty($body['remember']) || !empty($_POST['remember']);

        $result = Auth::login($email, $password, $remember);
        if ($result['ok']) {
            $result['csrf_token'] = Auth::getCsrfToken();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'logout':
        Auth::logout();
        echo json_encode(['ok' => true]);
        exit;

    case 'check':
        $user = Auth::user();
        if ($user) {
            echo json_encode([
                'ok' => true,
                'logged_in' => true,
                'user' => $user,
                'csrf_token' => Auth::getCsrfToken()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'ok' => true,
                'logged_in' => false
            ]);
        }
        exit;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Action inconnue']);
        exit;
}
