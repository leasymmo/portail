<?php
declare(strict_types=1);

namespace Cdh\Portal\Admin;

use PDO;
use Throwable;
use Cdh\Portal\Database;

/**
 * ==============================================================================
 * JOURNAL D'ACTIVITÉ & AUDIT TRAIL
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 */
class ActivityLogger
{
    /**
     * Enregistre une entrée dans le journal d'activité
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityLabel = null,
        array|string|null $details = null
    ): void {
        try {
            $db = Database::getConnection();
            $userId = null;
            $user = Auth::user();
            if ($user) {
                $userId = (int)$user['id'];
            }

            if (is_string($details)) {
                $details = ['message' => $details];
            }

            $stmt = $db->prepare('
                INSERT INTO admin_activity_logs (user_id, action, entity_type, entity_id, entity_label, details, ip_address)
                VALUES (:user_id, :action, :type, :id, :label, :details, :ip)
            ');

            $stmt->execute([
                'user_id' => $userId,
                'action'  => $action,
                'type'    => $entityType,
                'id'      => $entityId,
                'label'   => $entityLabel !== null ? substr($entityLabel, 0, 255) : null,
                'details' => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                'ip'      => Auth::getClientIp()
            ]);
        } catch (Throwable $e) {
            // Silencieux pour ne pas bloquer les opérations principales
        }
    }

    /**
     * Récupère les logs récents avec les noms d'utilisateurs
     */
    public static function getRecent(int $limit = 40): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT l.*, u.name as user_name, u.email as user_email
                FROM admin_activity_logs l
                LEFT JOIN admin_users u ON l.user_id = u.id
                ORDER BY l.id DESC
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            foreach ($rows as &$r) {
                if (!empty($r['details'])) {
                    $r['details'] = json_decode($r['details'], true);
                }
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }
}
