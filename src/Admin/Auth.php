<?php
declare(strict_types=1);

namespace Cdh\Portal\Admin;

use PDO;
use Throwable;
use Cdh\Portal\Database;

/**
 * ==============================================================================
 * SERVICE D'AUTHENTIFICATION & SÉCURITÉ DU BACK-OFFICE
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 */
class Auth
{
    private const SESSION_COOKIE_NAME = 'cdh_admin_token';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const SESSION_LIFETIME_DAYS = 14;

    private static ?array $currentUser = null;

    /**
     * Tente de connecter un administrateur
     */
    public static function login(string $email, string $password, bool $remember = false): array
    {
        $db = Database::getConnection();
        $email = trim(strtolower($email));

        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Veuillez saisir votre email et votre mot de passe.'];
        }

        // 1. Recherche de l'utilisateur
        $stmt = $db->prepare('SELECT * FROM admin_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Temporisation anti-timing attack
            password_verify($password, '$2y$12$e0V.exampleDummyHashForTimingAttackProtectionxxxxxx');
            return ['ok' => false, 'error' => 'Identifiants incorrects.'];
        }

        // 2. Vérification du verrouillage temporaire (Anti Brute-Force)
        if ($user['locked_until'] !== null) {
            $lockTime = strtotime($user['locked_until']);
            if ($lockTime > time()) {
                $waitMinutes = ceil(($lockTime - time()) / 60);
                return [
                    'ok' => false,
                    'error' => "Compte temporairement verrouillé suite à plusieurs tentatives infructueuses. Réessayez dans {$waitMinutes} minute(s)."
                ];
            } else {
                // Verrouillage expiré, réinitialisation
                $db->prepare('UPDATE admin_users SET locked_until = NULL, failed_login_attempts = 0 WHERE id = :id')
                   ->execute(['id' => $user['id']]);
                $user['failed_login_attempts'] = 0;
            }
        }

        // 3. Vérification du statut actif
        if ((int)$user['active'] !== 1) {
            return ['ok' => false, 'error' => 'Ce compte administrateur a été désactivé. Veuillez contacter un super-administrateur.'];
        }

        // 4. Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)$user['failed_login_attempts'] + 1;
            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
                $db->prepare('UPDATE admin_users SET failed_login_attempts = :attempts, locked_until = :locked WHERE id = :id')
                   ->execute(['attempts' => $attempts, 'locked' => $lockedUntil, 'id' => $user['id']]);

                ActivityLogger::log('login', 'user', (int)$user['id'], $user['email'], [
                    'status' => 'locked',
                    'reason' => 'max_failed_attempts',
                    'ip' => self::getClientIp()
                ]);

                return [
                    'ok' => false,
                    'error' => 'Trop de tentatives échouées. Compte verrouillé pour 15 minutes.'
                ];
            } else {
                $db->prepare('UPDATE admin_users SET failed_login_attempts = :attempts WHERE id = :id')
                   ->execute(['attempts' => $attempts, 'id' => $user['id']]);

                $remaining = self::MAX_ATTEMPTS - $attempts;
                return [
                    'ok' => false,
                    'error' => "Mot de passe incorrect. ({$remaining} tentative(s) restante(s))"
                ];
            }
        }

        // 5. Réinitialisation des tentatives et mise à jour de la dernière connexion
        $clientIp = self::getClientIp();
        $db->prepare('UPDATE admin_users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = :ip WHERE id = :id')
           ->execute(['ip' => $clientIp, 'id' => $user['id']]);

        // 6. Création du jeton de session cryptographique aléatoire
        $token = bin2hex(random_bytes(32));
        $days = $remember ? 30 : self::SESSION_LIFETIME_DAYS;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $stmtSession = $db->prepare('
            INSERT INTO admin_sessions (user_id, session_token, ip_address, user_agent, expires_at)
            VALUES (:user_id, :token, :ip, :ua, :expires)
        ');
        $stmtSession->execute([
            'user_id' => $user['id'],
            'token'   => hash('sha256', $token),
            'ip'      => $clientIp,
            'ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'expires' => $expiresAt
        ]);

        // 7. Envoi du cookie sécurisé
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $cookieExpire = $remember ? (time() + ($days * 86400)) : 0; // Session ou 30 jours
        
        // Calcul du path du cookie (/portail/ ou /portail/dev/)
        $basePath = '/portail/';
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/portail/dev') !== false) {
            $basePath = '/portail/dev/';
        }

        setcookie(self::SESSION_COOKIE_NAME, $token, [
            'expires'  => $cookieExpire,
            'path'     => $basePath,
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Initialisation de la session PHP pour le token CSRF
        self::ensurePhpSession();
        $_SESSION['cdh_admin_logged'] = true;
        $_SESSION['cdh_admin_uid'] = (int)$user['id'];

        ActivityLogger::log('login', 'user', (int)$user['id'], $user['email'], [
            'status' => 'success',
            'ip' => $clientIp
        ]);

        return [
            'ok' => true,
            'user' => [
                'id' => (int)$user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ];
    }

    /**
     * Déconnecte l'utilisateur actuel
     */
    public static function logout(): void
    {
        $rawToken = $_COOKIE[self::SESSION_COOKIE_NAME] ?? '';
        if ($rawToken !== '') {
            $db = Database::getConnection();
            $hashToken = hash('sha256', $rawToken);
            $db->prepare('DELETE FROM admin_sessions WHERE session_token = :token')
               ->execute(['token' => $hashToken]);
        }

        $basePath = '/portail/';
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/portail/dev') !== false) {
            $basePath = '/portail/dev/';
        }

        setcookie(self::SESSION_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => $basePath,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        self::ensurePhpSession();
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
        self::$currentUser = null;
    }

    /**
     * Renvoie l'utilisateur actuellement connecté, ou null
     */
    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $rawToken = $_COOKIE[self::SESSION_COOKIE_NAME] ?? '';
        if ($rawToken === '') {
            return null;
        }

        $db = Database::getConnection();
        $hashToken = hash('sha256', $rawToken);

        $stmt = $db->prepare('
            SELECT u.id, u.email, u.name, u.role, u.active, u.created_at, u.last_login_at, s.expires_at
            FROM admin_sessions s
            JOIN admin_users u ON s.user_id = u.id
            WHERE s.session_token = :token AND s.expires_at > NOW() AND u.active = 1
            LIMIT 1
        ');
        $stmt->execute(['token' => $hashToken]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        self::$currentUser = [
            'id'    => (int)$row['id'],
            'email' => $row['email'],
            'name'  => $row['name'],
            'role'  => $row['role'],
            'created_at' => $row['created_at'],
            'last_login_at' => $row['last_login_at']
        ];

        return self::$currentUser;
    }

    /**
     * Exige une authentification valide. Renvoie l'utilisateur ou coupe avec 401 JSON / redirection.
     */
    public static function requireAuth(bool $jsonResponse = true): array
    {
        $user = self::user();
        if ($user !== null) {
            return $user;
        }

        if ($jsonResponse) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Non authentifié. Session expirée.', 'unauthorized' => true]);
            exit;
        }

        // Redirection vers login
        $loginUrl = 'login.php';
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Exige le rôle superadmin
     */
    public static function requireSuperAdmin(bool $jsonResponse = true): array
    {
        $user = self::requireAuth($jsonResponse);
        if ($user['role'] !== 'superadmin') {
            if ($jsonResponse) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Accès refusé. Privilèges super-administrateur requis.']);
                exit;
            }
            die('Accès refusé.');
        }
        return $user;
    }

    /**
     * Génère ou récupère le jeton CSRF de session
     */
    public static function getCsrfToken(): string
    {
        self::ensurePhpSession();
        if (empty($_SESSION['cdh_csrf_token'])) {
            $_SESSION['cdh_csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['cdh_csrf_token'];
    }

    /**
     * Valide un jeton CSRF soumis
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::ensurePhpSession();
        $stored = $_SESSION['cdh_csrf_token'] ?? '';
        if ($stored === '' || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Vérification CSRF obligatoire pour les mutations
     */
    public static function checkCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!self::validateCsrfToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Jeton CSRF invalide ou expiré. Veuillez recharger la page.']);
            exit;
        }
    }

    /**
     * Démarre la session PHP si non active avec configuration durcie
     */
    private static function ensurePhpSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            if ($isHttps) {
                ini_set('session.cookie_secure', '1');
            }
            @session_start();
        }
    }

    /**
     * Récupère l'IP réelle du client
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
