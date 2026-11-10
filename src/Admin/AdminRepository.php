<?php
declare(strict_types=1);

namespace Cdh\Portal\Admin;

use PDO;
use Throwable;
use Cdh\Portal\Database;

require_once __DIR__ . '/AiTranslator.php';
require_once __DIR__ . '/ActivityLogger.php';
require_once __DIR__ . '/ParkingMigration.php';

/**
 * ==============================================================================
 * RÉPERTOIRE D'ADMINISTRATION & OPÉRATIONS CRUD
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 */
class AdminRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --------------------------------------------------------------------------
    // DASHBOARD & STATISTIQUES
    // --------------------------------------------------------------------------

    public function getDashboardStats(): array
    {
        $stats = [
            'total_properties'  => (int)$this->db->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
            'active_properties' => (int)$this->db->query('SELECT COUNT(*) FROM properties WHERE active = 1')->fetchColumn(),
            'total_videos'      => (int)$this->db->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
            'active_videos'     => (int)$this->db->query('SELECT COUNT(*) FROM videos WHERE active = 1')->fetchColumn(),
            'total_parkings'    => (int)$this->db->query('SELECT COUNT(*) FROM property_parkings')->fetchColumn(),
            'total_locations'   => (int)$this->db->query('
                SELECT (SELECT COUNT(*) FROM parking_free_spots) + 
                       (SELECT COUNT(*) FROM parking_private_lots) + 
                       (SELECT COUNT(*) FROM parking_tcl_prs)
            ')->fetchColumn(),
            'active_locations'  => (int)$this->db->query('
                SELECT (SELECT COUNT(*) FROM parking_free_spots WHERE active = 1) + 
                       (SELECT COUNT(*) FROM parking_private_lots WHERE active = 1) + 
                       (SELECT COUNT(*) FROM parking_tcl_prs WHERE active = 1)
            ')->fetchColumn(),
            'total_admins'      => (int)$this->db->query('SELECT COUNT(*) FROM admin_users WHERE active = 1')->fetchColumn(),
        ];
        return $stats;
    }

    // --------------------------------------------------------------------------
    // HÉBERGEMENTS (PROPERTIES)
    // --------------------------------------------------------------------------

    public function getAllProperties(): array
    {
        $stmt = $this->db->query('
            SELECT p.*,
                   (SELECT COUNT(*) FROM videos v WHERE v.property_id = p.id) as videos_count,
                   (
                       (SELECT COUNT(*) FROM parking_free_spots pfs WHERE pfs.property_id = p.id) +
                       (SELECT COUNT(*) FROM parking_private_lots ppl WHERE ppl.property_id = p.id) +
                       (SELECT COUNT(*) FROM parking_tcl_prs ptp WHERE ptp.property_id = p.id)
                   ) as parking_locations_count,
                   (SELECT id FROM property_parkings pp WHERE pp.property_id = p.id LIMIT 1) as parking_id
            FROM properties p
            ORDER BY p.name ASC
        ');
        return $stmt->fetchAll();
    }

    public function findPropertyById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM properties WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPropertyBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM properties WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createProperty(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO properties (name, slug, guide_fr_url, guide_en_url, wifi_ssid, wifi_password, address, active)
            VALUES (:name, :slug, :guide_fr, :guide_en, :wifi_ssid, :wifi_password, :address, :active)
        ');
        $stmt->execute([
            'name'          => trim($data['name']),
            'slug'          => trim(strtolower($data['slug'])),
            'guide_fr'      => !empty($data['guide_fr_url']) ? trim($data['guide_fr_url']) : null,
            'guide_en'      => !empty($data['guide_en_url']) ? trim($data['guide_en_url']) : null,
            'wifi_ssid'     => !empty($data['wifi_ssid']) ? trim($data['wifi_ssid']) : null,
            'wifi_password' => !empty($data['wifi_password']) ? trim($data['wifi_password']) : null,
            'address'       => !empty($data['address']) ? trim($data['address']) : null,
            'active'        => isset($data['active']) ? (int)$data['active'] : 1
        ]);
        $id = (int)$this->db->lastInsertId();

        ActivityLogger::log('create', 'property', $id, $data['name'], [
            'slug' => $data['slug']
        ]);

        return $id;
    }

    public function updateProperty(int $id, array $data): bool
    {
        $old = $this->findPropertyById($id);
        if (!$old) return false;

        $stmt = $this->db->prepare('
            UPDATE properties
            SET name = :name,
                slug = :slug,
                guide_fr_url = :guide_fr,
                guide_en_url = :guide_en,
                wifi_ssid = :wifi_ssid,
                wifi_password = :wifi_password,
                address = :address,
                active = :active
            WHERE id = :id
        ');
        $ok = $stmt->execute([
            'id'            => $id,
            'name'          => trim($data['name']),
            'slug'          => trim(strtolower($data['slug'])),
            'guide_fr'      => !empty($data['guide_fr_url']) ? trim($data['guide_fr_url']) : null,
            'guide_en'      => !empty($data['guide_en_url']) ? trim($data['guide_en_url']) : null,
            'wifi_ssid'     => !empty($data['wifi_ssid']) ? trim($data['wifi_ssid']) : null,
            'wifi_password' => !empty($data['wifi_password']) ? trim($data['wifi_password']) : null,
            'address'       => !empty($data['address']) ? trim($data['address']) : null,
            'active'        => isset($data['active']) ? (int)$data['active'] : 1
        ]);

        if ($ok) {
            ActivityLogger::log('update', 'property', $id, $data['name'], [
                'before' => $old,
                'after'  => $data
            ]);
        }
        return $ok;
    }

    public function togglePropertyActive(int $id): bool
    {
        $prop = $this->findPropertyById($id);
        if (!$prop) return false;

        $newStatus = (int)$prop['active'] === 1 ? 0 : 1;
        $stmt = $this->db->prepare('UPDATE properties SET active = :status WHERE id = :id');
        $ok = $stmt->execute(['status' => $newStatus, 'id' => $id]);

        if ($ok) {
            ActivityLogger::log('toggle', 'property', $id, $prop['name'], [
                'active' => $newStatus
            ]);
        }
        return $ok;
    }

    public function deleteProperty(int $id): bool
    {
        $prop = $this->findPropertyById($id);
        if (!$prop) return false;

        $stmt = $this->db->prepare('DELETE FROM properties WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);

        if ($ok) {
            ActivityLogger::log('delete', 'property', $id, $prop['name'], [
                'deleted_record' => $prop
            ]);
        }
        return $ok;
    }

    // --------------------------------------------------------------------------
    // VIDÉOS TUTORIELS (VIDEOS)
    // --------------------------------------------------------------------------

    public function getVideosByProperty(int $propertyId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM videos
            WHERE property_id = :property_id
            ORDER BY display_order ASC, id ASC
        ');
        $stmt->execute(['property_id' => $propertyId]);
        return $stmt->fetchAll();
    }

    public function findVideoById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createVideo(array $data): int
    {
        $order = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        if ($order <= 0) {
            $stmtOrder = $this->db->prepare('SELECT COALESCE(MAX(display_order), 0) + 1 FROM videos WHERE property_id = :pid');
            $stmtOrder->execute(['pid' => $data['property_id']]);
            $order = (int)$stmtOrder->fetchColumn();
        }

        $stmt = $this->db->prepare('
            INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active)
            VALUES (:pid, :slug, :title_fr, :title_en, :icon, :bg, :text, :url, :order, :active)
        ');
        $stmt->execute([
            'pid'      => (int)$data['property_id'],
            'slug'     => trim(strtolower($data['slug'])),
            'title_fr' => trim($data['title_fr']),
            'title_en' => !empty($data['title_en']) ? trim($data['title_en']) : trim($data['title_fr']),
            'icon'     => !empty($data['icon']) ? trim($data['icon']) : '🎬',
            'bg'       => !empty($data['icon_bg_color']) ? trim($data['icon_bg_color']) : 'bg-blue-50',
            'text'     => !empty($data['icon_text_color']) ? trim($data['icon_text_color']) : 'text-blue-500',
            'url'      => trim($data['video_url']),
            'order'    => $order,
            'active'   => isset($data['active']) ? (int)$data['active'] : 1
        ]);
        $id = (int)$this->db->lastInsertId();

        ActivityLogger::log('create', 'video', $id, $data['title_fr'], [
            'property_id' => $data['property_id'],
            'url'         => $data['video_url']
        ]);

        return $id;
    }

    public function updateVideo(int $id, array $data): bool
    {
        $old = $this->findVideoById($id);
        if (!$old) return false;

        $stmt = $this->db->prepare('
            UPDATE videos
            SET slug = :slug,
                title_fr = :title_fr,
                title_en = :title_en,
                icon = :icon,
                icon_bg_color = :bg,
                icon_text_color = :text,
                video_url = :url,
                display_order = :order,
                active = :active
            WHERE id = :id
        ');
        $ok = $stmt->execute([
            'id'       => $id,
            'slug'     => trim(strtolower($data['slug'])),
            'title_fr' => trim($data['title_fr']),
            'title_en' => !empty($data['title_en']) ? trim($data['title_en']) : trim($data['title_fr']),
            'icon'     => !empty($data['icon']) ? trim($data['icon']) : '🎬',
            'bg'       => !empty($data['icon_bg_color']) ? trim($data['icon_bg_color']) : 'bg-blue-50',
            'text'     => !empty($data['icon_text_color']) ? trim($data['icon_text_color']) : 'text-blue-500',
            'url'      => trim($data['video_url']),
            'order'    => (int)($data['display_order'] ?? $old['display_order']),
            'active'   => isset($data['active']) ? (int)$data['active'] : (int)$old['active']
        ]);

        if ($ok) {
            ActivityLogger::log('update', 'video', $id, $data['title_fr'], [
                'before' => $old,
                'after'  => $data
            ]);
        }
        return $ok;
    }

    public function reorderVideos(array $orderList): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE videos SET display_order = :order WHERE id = :id');
            foreach ($orderList as $order => $id) {
                $stmt->execute(['order' => (int)$order + 1, 'id' => (int)$id]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function toggleVideoActive(int $id): bool
    {
        $video = $this->findVideoById($id);
        if (!$video) return false;

        $newStatus = (int)$video['active'] === 1 ? 0 : 1;
        $stmt = $this->db->prepare('UPDATE videos SET active = :status WHERE id = :id');
        $ok = $stmt->execute(['status' => $newStatus, 'id' => $id]);

        if ($ok) {
            ActivityLogger::log('toggle', 'video', $id, $video['title_fr'], [
                'active' => $newStatus
            ]);
        }
        return $ok;
    }

    public function deleteVideo(int $id): bool
    {
        $video = $this->findVideoById($id);
        if (!$video) return false;

        $stmt = $this->db->prepare('DELETE FROM videos WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);

        if ($ok) {
            ActivityLogger::log('delete', 'video', $id, $video['title_fr']);
        }
        return $ok;
    }

    // --------------------------------------------------------------------------
    // STATIONNEMENT (RÈGLES GÉNÉRALES & EMPLACEMENTS NORMALISÉS V2)
    // --------------------------------------------------------------------------

    public function getParkingByProperty(int $propertyId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM property_parkings WHERE property_id = :pid LIMIT 1');
        $stmt->execute(['pid' => $propertyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Vérifie si les tables V2 spécialisées existent en base de données
     */
    public function isParkingV2Migrated(): bool
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'parking_free_spots'");
            $row = $stmt->fetch();
            return !empty($row);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Exécute le script de migration V2 de façon idempotente et sans perte
     */
    public function runParkingV2Migration(): array
    {
        return ParkingMigration::run($this->db);
    }

    /**
     * Nettoie définitivement les anciennes tables et vues obsolètes
     */
    public function cleanOldParkingTables(): array
    {
        return ParkingMigration::dropOldTables($this->db);
    }

    /**
     * Récupère les emplacements sous forme structurée en 3 sections distinctes
     */
    public function getParkingSectionsByProperty(int $propertyId): array
    {
        try {
            $stmtFree = $this->db->prepare("
                SELECT *, CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category 
                FROM parking_free_spots 
                WHERE property_id = :pid 
                ORDER BY display_order ASC, id ASC
            ");
            $stmtFree->execute(['pid' => $propertyId]);
            $free = $stmtFree->fetchAll();

            $stmtPriv = $this->db->prepare("
                SELECT *, 'private_parking' AS category 
                FROM parking_private_lots 
                WHERE property_id = :pid 
                ORDER BY display_order ASC, id ASC
            ");
            $stmtPriv->execute(['pid' => $propertyId]);
            $priv = $stmtPriv->fetchAll();

            $stmtTcl = $this->db->prepare("
                SELECT *, 'tcl_pr' AS category 
                FROM parking_tcl_prs 
                WHERE property_id = :pid 
                ORDER BY display_order ASC, id ASC
            ");
            $stmtTcl->execute(['pid' => $propertyId]);
            $tcl = $stmtTcl->fetchAll();

            return [
                'free_spots'   => $free,
                'private_lots' => $priv,
                'tcl_prs'      => $tcl,
            ];
        } catch (Throwable $e) {
            return [
                'free_spots'   => [],
                'private_lots' => [],
                'tcl_prs'      => [],
            ];
        }
    }

    /**
     * Récupère la liste de tous les emplacements (avec catégorie) pour le logement
     */
    public function getParkingLocationsByProperty(int $propertyId): array
    {
        $sections = $this->getParkingSectionsByProperty($propertyId);
        return array_merge($sections['free_spots'] ?? [], $sections['private_lots'] ?? [], $sections['tcl_prs'] ?? []);
    }

    public function saveParkingGeneral(int $propertyId, array $data): bool
    {
        $existing = $this->getParkingByProperty($propertyId);

        // Traduction automatique intelligente via IA des champs en français vers l'anglais
        $data = AiTranslator::translateParkingGeneral($this->db, $data);

        $fields = [
            'intro_fr', 'intro_en',
            'important_rule_fr', 'important_rule_en',
            'free_parking_notes_fr', 'free_parking_notes_en',
            'street_parking_info_fr', 'street_parking_info_en',
            'street_parking_schedule_fr', 'street_parking_schedule_en',
            'street_parking_rates_fr', 'street_parking_rates_en',
            'street_parking_app',
            'tcl_conditions_fr', 'tcl_conditions_en'
        ];

        if ($existing) {
            $setParts = [];
            $params = ['pid' => $propertyId];
            foreach ($fields as $f) {
                $setParts[] = "`{$f}` = :{$f}";
                $params[$f] = isset($data[$f]) && trim((string)$data[$f]) !== '' ? trim((string)$data[$f]) : null;
            }
            if (empty($params['street_parking_app'])) {
                $params['street_parking_app'] = 'EasyPark';
            }
            $sql = 'UPDATE property_parkings SET ' . implode(', ', $setParts) . ' WHERE property_id = :pid';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute($params);

            ActivityLogger::log('update', 'parking_general', (int)$existing['id'], "Stationnement Général (Logement #{$propertyId})");
            return $ok;
        } else {
            $cols = ['property_id'];
            $placeholders = [':pid'];
            $params = ['pid' => $propertyId];
            foreach ($fields as $f) {
                $cols[] = "`{$f}`";
                $placeholders[] = ":{$f}";
                $params[$f] = isset($data[$f]) && trim((string)$data[$f]) !== '' ? trim((string)$data[$f]) : null;
            }
            if (empty($params['street_parking_app'])) {
                $params['street_parking_app'] = 'EasyPark';
            }
            $sql = 'INSERT INTO property_parkings (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute($params);
            $newId = (int)$this->db->lastInsertId();

            ActivityLogger::log('create', 'parking_general', $newId, "Stationnement Général (Logement #{$propertyId})");
            return $ok;
        }
    }

    /**
     * Recherche un emplacement par son identifiant et optionnellement sa catégorie
     */
    public function findParkingItemById(int $id, ?string $category = null): ?array
    {
        if ($category === 'private_parking') {
            $stmt = $this->db->prepare('SELECT *, \'private_parking\' AS category FROM parking_private_lots WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;
        } elseif ($category === 'tcl_pr') {
            $stmt = $this->db->prepare('SELECT *, \'tcl_pr\' AS category FROM parking_tcl_prs WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;
        } elseif (in_array($category, ['free_street', 'free_spot'])) {
            $stmt = $this->db->prepare('SELECT *, CASE WHEN spot_type = \'spot\' THEN \'free_spot\' ELSE \'free_street\' END AS category FROM parking_free_spots WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        // Si catégorie inconnue ou non trouvée, recherche dans les 3 tables
        try {
            $stmt = $this->db->prepare('SELECT *, CASE WHEN spot_type = \'spot\' THEN \'free_spot\' ELSE \'free_street\' END AS category FROM parking_free_spots WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;

            $stmt = $this->db->prepare('SELECT *, \'private_parking\' AS category FROM parking_private_lots WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;

            $stmt = $this->db->prepare('SELECT *, \'tcl_pr\' AS category FROM parking_tcl_prs WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) return $row;
        } catch (Throwable $e) {}

        return null;
    }

    public function findParkingLocationById(int $id): ?array
    {
        return $this->findParkingItemById($id);
    }

    /**
     * Enregistre (crée ou met à jour) un emplacement dans sa table dédiée selon sa catégorie
     */
    public function saveParkingItem(array $data): int
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $category = $data['category'] ?? 'free_street';
        $propertyId = (int)($data['property_id'] ?? 0);

        // Détection et gestion propre du changement de catégorie (migration entre tables dédiées)
        $originalCategory = !empty($data['original_category']) ? $data['original_category'] : null;
        if ($id > 0) {
            $isOldFree = in_array($originalCategory, ['free_street', 'free_spot'], true);
            $isNewFree = in_array($category, ['free_street', 'free_spot'], true);

            $targetTableChanged = false;
            if ($originalCategory) {
                if ($isOldFree !== $isNewFree || (!$isOldFree && !$isNewFree && $originalCategory !== $category)) {
                    $targetTableChanged = true;
                }
            } else {
                $destTable = $isNewFree ? 'parking_free_spots' : ($category === 'private_parking' ? 'parking_private_lots' : 'parking_tcl_prs');
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM `{$destTable}` WHERE id = :id");
                $stmtCheck->execute(['id' => $id]);
                if ((int)$stmtCheck->fetchColumn() === 0) {
                    $targetTableChanged = true;
                }
            }

            if ($targetTableChanged) {
                if ($originalCategory) {
                    $this->deleteParkingLocation($id, $originalCategory);
                } else {
                    foreach (['parking_free_spots', 'parking_private_lots', 'parking_tcl_prs'] as $t) {
                        $destTbl = $isNewFree ? 'parking_free_spots' : ($category === 'private_parking' ? 'parking_private_lots' : 'parking_tcl_prs');
                        if ($t !== $destTbl) {
                            $this->db->prepare("DELETE FROM `{$t}` WHERE id = :id")->execute(['id' => $id]);
                        }
                    }
                }
                $id = 0;
            }
        }

        // Traduction automatique intelligente ciblée par type
        $data = AiTranslator::translateParkingLocation($this->db, $data);

        if (in_array($category, ['free_street', 'free_spot'])) {
            // Table : parking_free_spots
            $spotType = ($category === 'free_spot') ? 'spot' : 'street';
            $order = isset($data['display_order']) ? (int)$data['display_order'] : 0;
            if ($order <= 0 && $id <= 0) {
                $stmtOrder = $this->db->prepare('SELECT COALESCE(MAX(display_order), 0) + 1 FROM parking_free_spots WHERE property_id = :pid');
                $stmtOrder->execute(['pid' => $propertyId]);
                $order = (int)$stmtOrder->fetchColumn();
            }

            if ($id > 0) {
                $old = $this->findParkingItemById($id, $category);
                $stmt = $this->db->prepare('
                    UPDATE parking_free_spots SET
                        spot_type = :spot_type,
                        name_fr = :name_fr,
                        name_en = :name_en,
                        address = :address,
                        walking_time_fr = :walking_fr,
                        walking_time_en = :walking_en,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en,
                        display_order = :order,
                        active = :active
                    WHERE id = :id
                ');
                $stmt->execute([
                    'id'          => $id,
                    'spot_type'   => $spotType,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'walking_fr'  => !empty($data['walking_time_fr']) ? trim($data['walking_time_fr']) : null,
                    'walking_en'  => !empty($data['walking_time_en']) ? trim($data['walking_time_en']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : (int)($old['display_order'] ?? 1),
                    'active'      => isset($data['active']) ? (int)$data['active'] : (int)($old['active'] ?? 1)
                ]);
                ActivityLogger::log('update', 'parking_free_spot', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare('
                    INSERT INTO parking_free_spots (
                        property_id, spot_type, name_fr, name_en, address,
                        walking_time_fr, walking_time_en, notes_fr, notes_en, display_order, active
                    ) VALUES (
                        :pid, :spot_type, :name_fr, :name_en, :address,
                        :walking_fr, :walking_en, :notes_fr, :notes_en, :order, :active
                    )
                ');
                $stmt->execute([
                    'pid'         => $propertyId,
                    'spot_type'   => $spotType,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'walking_fr'  => !empty($data['walking_time_fr']) ? trim($data['walking_time_fr']) : null,
                    'walking_en'  => !empty($data['walking_time_en']) ? trim($data['walking_time_en']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : 1,
                    'active'      => isset($data['active']) ? (int)$data['active'] : 1
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_free_spot', $newId, $data['name_fr']);
                return $newId;
            }
        } elseif ($category === 'private_parking') {
            // Table : parking_private_lots
            $order = isset($data['display_order']) ? (int)$data['display_order'] : 0;
            if ($order <= 0 && $id <= 0) {
                $stmtOrder = $this->db->prepare('SELECT COALESCE(MAX(display_order), 0) + 1 FROM parking_private_lots WHERE property_id = :pid');
                $stmtOrder->execute(['pid' => $propertyId]);
                $order = (int)$stmtOrder->fetchColumn();
            }

            if ($id > 0) {
                $old = $this->findParkingItemById($id, $category);
                $stmt = $this->db->prepare('
                    UPDATE parking_private_lots SET
                        name_fr = :name_fr,
                        name_en = :name_en,
                        operator = :operator,
                        address = :address,
                        walking_time_fr = :walking_fr,
                        walking_time_en = :walking_en,
                        max_height = :max_height,
                        opening_hours_fr = :hours_fr,
                        opening_hours_en = :hours_en,
                        rates_fr = :rates_fr,
                        rates_en = :rates_en,
                        suitable_vehicles_fr = :vehicles_fr,
                        suitable_vehicles_en = :vehicles_en,
                        booking_url = :booking_url,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en,
                        display_order = :order,
                        active = :active
                    WHERE id = :id
                ');
                $stmt->execute([
                    'id'          => $id,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'operator'    => !empty($data['operator']) ? trim($data['operator']) : null,
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'walking_fr'  => !empty($data['walking_time_fr']) ? trim($data['walking_time_fr']) : null,
                    'walking_en'  => !empty($data['walking_time_en']) ? trim($data['walking_time_en']) : null,
                    'max_height'  => !empty($data['max_height']) ? trim($data['max_height']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'rates_fr'    => !empty($data['rates_fr']) ? trim($data['rates_fr']) : null,
                    'rates_en'    => !empty($data['rates_en']) ? trim($data['rates_en']) : null,
                    'vehicles_fr' => !empty($data['suitable_vehicles_fr']) ? trim($data['suitable_vehicles_fr']) : null,
                    'vehicles_en' => !empty($data['suitable_vehicles_en']) ? trim($data['suitable_vehicles_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : (int)($old['display_order'] ?? 1),
                    'active'      => isset($data['active']) ? (int)$data['active'] : (int)($old['active'] ?? 1)
                ]);
                ActivityLogger::log('update', 'parking_private_lot', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare('
                    INSERT INTO parking_private_lots (
                        property_id, name_fr, name_en, operator, address,
                        walking_time_fr, walking_time_en, max_height,
                        opening_hours_fr, opening_hours_en, rates_fr, rates_en,
                        suitable_vehicles_fr, suitable_vehicles_en, booking_url,
                        notes_fr, notes_en, display_order, active
                    ) VALUES (
                        :pid, :name_fr, :name_en, :operator, :address,
                        :walking_fr, :walking_en, :max_height,
                        :hours_fr, :hours_en, :rates_fr, :rates_en,
                        :vehicles_fr, :vehicles_en, :booking_url,
                        :notes_fr, :notes_en, :order, :active
                    )
                ');
                $stmt->execute([
                    'pid'         => $propertyId,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'operator'    => !empty($data['operator']) ? trim($data['operator']) : null,
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'walking_fr'  => !empty($data['walking_time_fr']) ? trim($data['walking_time_fr']) : null,
                    'walking_en'  => !empty($data['walking_time_en']) ? trim($data['walking_time_en']) : null,
                    'max_height'  => !empty($data['max_height']) ? trim($data['max_height']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'rates_fr'    => !empty($data['rates_fr']) ? trim($data['rates_fr']) : null,
                    'rates_en'    => !empty($data['rates_en']) ? trim($data['rates_en']) : null,
                    'vehicles_fr' => !empty($data['suitable_vehicles_fr']) ? trim($data['suitable_vehicles_fr']) : null,
                    'vehicles_en' => !empty($data['suitable_vehicles_en']) ? trim($data['suitable_vehicles_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : 1,
                    'active'      => isset($data['active']) ? (int)$data['active'] : 1
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_private_lot', $newId, $data['name_fr']);
                return $newId;
            }
        } elseif ($category === 'tcl_pr') {
            // Table : parking_tcl_prs
            $order = isset($data['display_order']) ? (int)$data['display_order'] : 0;
            if ($order <= 0 && $id <= 0) {
                $stmtOrder = $this->db->prepare('SELECT COALESCE(MAX(display_order), 0) + 1 FROM parking_tcl_prs WHERE property_id = :pid');
                $stmtOrder->execute(['pid' => $propertyId]);
                $order = (int)$stmtOrder->fetchColumn();
            }

            if ($id > 0) {
                $old = $this->findParkingItemById($id, $category);
                $stmt = $this->db->prepare('
                    UPDATE parking_tcl_prs SET
                        name_fr = :name_fr,
                        name_en = :name_en,
                        address = :address,
                        transit_time_fr = :transit_fr,
                        transit_time_en = :transit_en,
                        capacity = :capacity,
                        opening_hours_fr = :hours_fr,
                        opening_hours_en = :hours_en,
                        how_to_reach_fr = :reach_fr,
                        how_to_reach_en = :reach_en,
                        booking_url = :booking_url,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en,
                        display_order = :order,
                        active = :active
                    WHERE id = :id
                ');
                $stmt->execute([
                    'id'          => $id,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'transit_fr'  => !empty($data['transit_time_fr']) ? trim($data['transit_time_fr']) : null,
                    'transit_en'  => !empty($data['transit_time_en']) ? trim($data['transit_time_en']) : null,
                    'capacity'    => !empty($data['capacity']) ? trim($data['capacity']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'reach_fr'    => !empty($data['how_to_reach_fr']) ? trim($data['how_to_reach_fr']) : null,
                    'reach_en'    => !empty($data['how_to_reach_en']) ? trim($data['how_to_reach_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : (int)($old['display_order'] ?? 1),
                    'active'      => isset($data['active']) ? (int)$data['active'] : (int)($old['active'] ?? 1)
                ]);
                ActivityLogger::log('update', 'parking_tcl_pr', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare('
                    INSERT INTO parking_tcl_prs (
                        property_id, name_fr, name_en, address,
                        transit_time_fr, transit_time_en, capacity,
                        opening_hours_fr, opening_hours_en, how_to_reach_fr, how_to_reach_en,
                        booking_url, notes_fr, notes_en, display_order, active
                    ) VALUES (
                        :pid, :name_fr, :name_en, :address,
                        :transit_fr, :transit_en, :capacity,
                        :hours_fr, :hours_en, :reach_fr, :reach_en,
                        :booking_url, :notes_fr, :notes_en, :order, :active
                    )
                ');
                $stmt->execute([
                    'pid'         => $propertyId,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'transit_fr'  => !empty($data['transit_time_fr']) ? trim($data['transit_time_fr']) : null,
                    'transit_en'  => !empty($data['transit_time_en']) ? trim($data['transit_time_en']) : null,
                    'capacity'    => !empty($data['capacity']) ? trim($data['capacity']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'reach_fr'    => !empty($data['how_to_reach_fr']) ? trim($data['how_to_reach_fr']) : null,
                    'reach_en'    => !empty($data['how_to_reach_en']) ? trim($data['how_to_reach_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                    'order'       => $order > 0 ? $order : 1,
                    'active'      => isset($data['active']) ? (int)$data['active'] : 1
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_tcl_pr', $newId, $data['name_fr']);
                return $newId;
            }
        }

        // Repli si catégorie non reconnue
        return 0;
    }

    public function createParkingLocation(array $data): int
    {
        return $this->saveParkingItem($data);
    }

    public function updateParkingLocation(int $id, array $data): bool
    {
        $data['id'] = $id;
        $savedId = $this->saveParkingItem($data);
        return $savedId > 0;
    }

    public function reorderParkingItems(string $category, array $orderList): bool
    {
        $table = 'parking_free_spots';
        if ($category === 'private_parking') {
            $table = 'parking_private_lots';
        } elseif ($category === 'tcl_pr') {
            $table = 'parking_tcl_prs';
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE `{$table}` SET display_order = :order WHERE id = :id");
            foreach ($orderList as $order => $id) {
                $stmt->execute(['order' => (int)$order + 1, 'id' => (int)$id]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function reorderParkingLocations(array $orderList): bool
    {
        // Réordonne de façon sécurisée par catégorie si possible
        $this->db->beginTransaction();
        try {
            foreach ($orderList as $order => $id) {
                // Tente la mise à jour sur les 3 tables
                $stmt1 = $this->db->prepare("UPDATE parking_free_spots SET display_order = :order WHERE id = :id");
                $stmt1->execute(['order' => (int)$order + 1, 'id' => (int)$id]);

                $stmt2 = $this->db->prepare("UPDATE parking_private_lots SET display_order = :order WHERE id = :id");
                $stmt2->execute(['order' => (int)$order + 1, 'id' => (int)$id]);

                $stmt3 = $this->db->prepare("UPDATE parking_tcl_prs SET display_order = :order WHERE id = :id");
                $stmt3->execute(['order' => (int)$order + 1, 'id' => (int)$id]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function toggleParkingLocationActive(int $id, ?string $category = null): bool
    {
        $item = $this->findParkingItemById($id, $category);
        if (!$item) return false;

        $cat = $item['category'] ?? $category ?? 'free_street';
        $newStatus = (int)$item['active'] === 1 ? 0 : 1;

        $table = 'parking_free_spots';
        if ($cat === 'private_parking') {
            $table = 'parking_private_lots';
        } elseif ($cat === 'tcl_pr') {
            $table = 'parking_tcl_prs';
        }

        try {
            $stmt = $this->db->prepare("UPDATE `{$table}` SET active = :status WHERE id = :id");
            $ok = $stmt->execute(['status' => $newStatus, 'id' => $id]);
            if ($ok) {
                ActivityLogger::log('toggle', $table, $id, $item['name_fr'], ['active' => $newStatus]);
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteParkingLocation(int $id, ?string $category = null): bool
    {
        $item = $this->findParkingItemById($id, $category);
        if (!$item) return false;

        $cat = $item['category'] ?? $category ?? 'free_street';
        $table = 'parking_free_spots';
        if ($cat === 'private_parking') {
            $table = 'parking_private_lots';
        } elseif ($cat === 'tcl_pr') {
            $table = 'parking_tcl_prs';
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM `{$table}` WHERE id = :id");
            $ok = $stmt->execute(['id' => $id]);
            if ($ok) {
                ActivityLogger::log('delete', $table, $id, $item['name_fr']);
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    // --------------------------------------------------------------------------
    // UTILISATEURS ADMINISTRATEURS (ADMIN_USERS)
    // --------------------------------------------------------------------------

    public function getAllUsers(): array
    {
        $stmt = $this->db->query('
            SELECT id, email, name, role, active, last_login_at, last_login_ip, created_at
            FROM admin_users
            ORDER BY id ASC
        ');
        return $stmt->fetchAll();
    }

    public function findUserById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, email, name, role, active, last_login_at, last_login_ip, created_at FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO admin_users (email, password_hash, name, role, active)
            VALUES (:email, :hash, :name, :role, :active)
        ');
        $stmt->execute([
            'email'  => trim(strtolower($data['email'])),
            'hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'   => trim($data['name']),
            'role'   => in_array($data['role'], ['superadmin', 'admin', 'editor'], true) ? $data['role'] : 'admin',
            'active' => isset($data['active']) ? (int)$data['active'] : 1
        ]);
        $id = (int)$this->db->lastInsertId();

        ActivityLogger::log('create', 'user', $id, $data['email'], [
            'name' => $data['name'],
            'role' => $data['role']
        ]);

        return $id;
    }

    public function updateUser(int $id, array $data): bool
    {
        $user = $this->findUserById($id);
        if (!$user) return false;

        $updates = [
            'name = :name',
            'role = :role',
            'active = :active'
        ];
        $params = [
            'id'     => $id,
            'name'   => trim($data['name']),
            'role'   => in_array($data['role'], ['superadmin', 'admin', 'editor'], true) ? $data['role'] : $user['role'],
            'active' => isset($data['active']) ? (int)$data['active'] : (int)$user['active']
        ];

        if (!empty($data['password'])) {
            $updates[] = 'password_hash = :hash';
            $params['hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE admin_users SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            ActivityLogger::log('update', 'user', $id, $user['email'], [
                'name' => $data['name'],
                'role' => $data['role']
            ]);
        }
        return $ok;
    }

    public function updateSelfPassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($oldPassword, $hash)) {
            return ['ok' => false, 'error' => 'Ancien mot de passe incorrect.'];
        }

        if (strlen($newPassword) < 8) {
            return ['ok' => false, 'error' => 'Le nouveau mot de passe doit comporter au moins 8 caractères.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id')
                 ->execute(['hash' => $newHash, 'id' => $userId]);

        ActivityLogger::log('update', 'user', $userId, 'Changement de mot de passe personnel');

        return ['ok' => true];
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->findUserById($id);
        if (!$user) return false;

        $stmt = $this->db->prepare('DELETE FROM admin_users WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);

        if ($ok) {
            ActivityLogger::log('delete', 'user', $id, $user['email']);
        }
        return $ok;
    }

    // --------------------------------------------------------------------------
    // CONFIGURATION & IA (admin_settings)
    // --------------------------------------------------------------------------

    public function getSettings(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM admin_settings');
        $raw = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $raw[$row['setting_key']] = $row['setting_value'];
        }

        $provider = $raw['ai_provider'] ?? 'gemini';

        $maskKey = function (?string $key): string {
            if (empty($key)) return '';
            $len = strlen($key);
            if ($len <= 8) return '••••••••';
            return substr($key, 0, 6) . str_repeat('•', max(4, $len - 10)) . substr($key, -4);
        };

        return [
            'ai_provider' => $provider,
            'ai_model_gemini' => $raw['ai_model_gemini'] ?? 'gemini-3.5-flash',
            'ai_model_openai' => $raw['ai_model_openai'] ?? 'gpt-4o-mini',
            'ai_model_claude' => $raw['ai_model_claude'] ?? 'claude-3-5-haiku-latest',
            'has_key_gemini' => !empty($raw['ai_api_key_gemini']),
            'has_key_openai' => !empty($raw['ai_api_key_openai']),
            'has_key_claude' => !empty($raw['ai_api_key_claude']),
            'masked_key_gemini' => $maskKey($raw['ai_api_key_gemini'] ?? null),
            'masked_key_openai' => $maskKey($raw['ai_api_key_openai'] ?? null),
            'masked_key_claude' => $maskKey($raw['ai_api_key_claude'] ?? null),
            // Stockage Cloudflare R2 (exclusif)
            'storage_driver' => 'r2',
            'r2_account_id' => $raw['r2_account_id'] ?? '',
            'r2_access_key_id' => $raw['r2_access_key_id'] ?? '',
            'has_r2_secret' => !empty($raw['r2_secret_access_key']),
            'masked_r2_secret' => $maskKey($raw['r2_secret_access_key'] ?? null),
            'r2_bucket_guides' => $raw['r2_bucket_guides'] ?? 'carredhotes-guides',
            'r2_domain_guides' => $raw['r2_domain_guides'] ?? 'https://guides.carredhotes.fr',
            'r2_bucket_videos' => $raw['r2_bucket_videos'] ?? 'carredhotes-videos',
            'r2_domain_videos' => $raw['r2_domain_videos'] ?? 'https://videos.carredhotes.fr',
        ];
    }

    public function saveSettings(array $settings): bool
    {
        $allowedProviders = ['gemini', 'openai', 'claude'];
        $provider = in_array($settings['ai_provider'] ?? '', $allowedProviders, true) ? $settings['ai_provider'] : 'gemini';

        $stmt = $this->db->prepare('
            INSERT INTO admin_settings (setting_key, setting_value) 
            VALUES (:k, :v) 
            ON DUPLICATE KEY UPDATE setting_value = :v2
        ');

        // Fournisseur actif
        $stmt->execute(['k' => 'ai_provider', 'v' => $provider, 'v2' => $provider]);

        // Mise à jour conditionnelle des clés IA (si non vide et non masquée '••••')
        $keyFields = ['gemini', 'openai', 'claude'];
        foreach ($keyFields as $kf) {
            $keyInput = trim((string)($settings['ai_api_key_' . $kf] ?? ''));
            if ($keyInput !== '' && strpos($keyInput, '•') === false) {
                $stmt->execute(['k' => 'ai_api_key_' . $kf, 'v' => $keyInput, 'v2' => $keyInput]);
            }
            if (!empty($settings['ai_model_' . $kf])) {
                $m = trim((string)$settings['ai_model_' . $kf]);
                $stmt->execute(['k' => 'ai_model_' . $kf, 'v' => $m, 'v2' => $m]);
            }
        }

        // Stockage Cloudflare R2 exclusif
        $stmt->execute(['k' => 'storage_driver', 'v' => 'r2', 'v2' => 'r2']);

        if (isset($settings['r2_account_id'])) {
            $v = trim((string)$settings['r2_account_id']);
            $stmt->execute(['k' => 'r2_account_id', 'v' => $v, 'v2' => $v]);
        }
        if (isset($settings['r2_access_key_id'])) {
            $v = trim((string)$settings['r2_access_key_id']);
            $stmt->execute(['k' => 'r2_access_key_id', 'v' => $v, 'v2' => $v]);
        }
        if (isset($settings['r2_secret_access_key'])) {
            $sec = trim((string)$settings['r2_secret_access_key']);
            if ($sec !== '' && strpos($sec, '•') === false) {
                $stmt->execute(['k' => 'r2_secret_access_key', 'v' => $sec, 'v2' => $sec]);
            }
        }
        if (isset($settings['r2_bucket_guides'])) {
            $v = trim((string)$settings['r2_bucket_guides']);
            $stmt->execute(['k' => 'r2_bucket_guides', 'v' => $v, 'v2' => $v]);
        }
        if (isset($settings['r2_domain_guides'])) {
            $v = trim((string)$settings['r2_domain_guides']);
            $stmt->execute(['k' => 'r2_domain_guides', 'v' => $v, 'v2' => $v]);
        }
        if (isset($settings['r2_bucket_videos'])) {
            $v = trim((string)$settings['r2_bucket_videos']);
            $stmt->execute(['k' => 'r2_bucket_videos', 'v' => $v, 'v2' => $v]);
        }
        if (isset($settings['r2_domain_videos'])) {
            $v = trim((string)$settings['r2_domain_videos']);
            $stmt->execute(['k' => 'r2_domain_videos', 'v' => $v, 'v2' => $v]);
        }

        ActivityLogger::log('update', 'settings', null, "Configuration système mise à jour (IA: {$provider}, Stockage: Cloudflare R2)");
        return true;
    }
}
