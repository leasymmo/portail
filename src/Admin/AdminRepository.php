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
                SELECT COUNT(*) FROM property_parking_associations WHERE active = 1
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
                   (SELECT COUNT(*) FROM property_parking_associations ppa WHERE ppa.property_id = p.id) as parking_locations_count,
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
            INSERT INTO properties (name, slug, rental_id, guide_fr_url, guide_en_url, wifi_ssid, wifi_password, address, active)
            VALUES (:name, :slug, :rental_id, :guide_fr, :guide_en, :wifi_ssid, :wifi_password, :address, :active)
        ');
        $stmt->execute([
            'name'          => trim($data['name']),
            'slug'          => trim(strtolower($data['slug'])),
            'rental_id'     => !empty($data['rental_id']) ? trim((string)$data['rental_id']) : null,
            'guide_fr'      => !empty($data['guide_fr_url']) ? trim($data['guide_fr_url']) : null,
            'guide_en'      => !empty($data['guide_en_url']) ? trim($data['guide_en_url']) : null,
            'wifi_ssid'     => !empty($data['wifi_ssid']) ? trim($data['wifi_ssid']) : null,
            'wifi_password' => !empty($data['wifi_password']) ? trim($data['wifi_password']) : null,
            'address'       => !empty($data['address']) ? trim($data['address']) : null,
            'active'        => isset($data['active']) ? (int)$data['active'] : 1
        ]);
        $id = (int)$this->db->lastInsertId();

        ActivityLogger::log('create', 'property', $id, $data['name'], [
            'slug'      => $data['slug'],
            'rental_id' => $data['rental_id'] ?? null
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
                rental_id = :rental_id,
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
            'rental_id'     => !empty($data['rental_id']) ? trim((string)$data['rental_id']) : null,
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
    /**
     * Récupère les emplacements sous forme structurée en 3 sections distinctes
     * (Architecture V3 : jointure entre catalogue physique et associations du logement)
     */
    public function getParkingSectionsByProperty(int $propertyId): array
    {
        try {
            $stmtFree = $this->db->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.spot_type,
                    c.name_fr,
                    c.name_en,
                    c.address,
                    a.walking_time_fr,
                    a.walking_time_en,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    CASE WHEN c.spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category
                FROM property_parking_associations a
                JOIN parking_free_spots c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid 
                ORDER BY a.display_order ASC, a.id ASC
            ");
            $stmtFree->execute(['pid' => $propertyId]);
            $free = $stmtFree->fetchAll();

            $stmtPriv = $this->db->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.name_fr,
                    c.name_en,
                    c.operator,
                    c.address,
                    a.walking_time_fr,
                    a.walking_time_en,
                    c.max_height,
                    c.opening_hours_fr,
                    c.opening_hours_en,
                    c.rates_fr,
                    c.rates_en,
                    c.suitable_vehicles_fr,
                    c.suitable_vehicles_en,
                    c.booking_url,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    'private_parking' AS category
                FROM property_parking_associations a
                JOIN parking_private_lots c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid 
                ORDER BY a.display_order ASC, a.id ASC
            ");
            $stmtPriv->execute(['pid' => $propertyId]);
            $priv = $stmtPriv->fetchAll();

            $stmtTcl = $this->db->prepare("
                SELECT 
                    c.id,
                    a.id AS association_id,
                    a.property_id,
                    c.name_fr,
                    c.name_en,
                    c.capacity,
                    c.address,
                    a.transit_time_fr,
                    a.transit_time_en,
                    c.opening_hours_fr,
                    c.opening_hours_en,
                    a.how_to_reach_fr,
                    a.how_to_reach_en,
                    c.booking_url,
                    COALESCE(a.notes_fr, c.notes_fr) AS notes_fr,
                    COALESCE(a.notes_en, c.notes_en) AS notes_en,
                    a.display_order,
                    a.active,
                    'tcl_pr' AS category
                FROM property_parking_associations a
                JOIN parking_tcl_prs c ON c.id = a.parking_item_id
                WHERE a.property_id = :pid 
                ORDER BY a.display_order ASC, a.id ASC
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
     * Récupère la liste de tous les emplacements associés à un logement
     */
    public function getParkingLocationsByProperty(int $propertyId): array
    {
        $sections = $this->getParkingSectionsByProperty($propertyId);
        return array_merge($sections['free_spots'] ?? [], $sections['private_lots'] ?? [], $sections['tcl_prs'] ?? []);
    }

    /**
     * Récupère tous les parkings du catalogue centralisé avec leurs logements liés
     */
    public function getAllCatalogParkingItems(?string $categoryFilter = null): array
    {
        $items = [];

        // 1. Rues gratuites & Dépose
        if (!$categoryFilter || in_array($categoryFilter, ['free_street', 'free_spot', 'free'])) {
            $stmt = $this->db->query("
                SELECT *, CASE WHEN spot_type = 'spot' THEN 'free_spot' ELSE 'free_street' END AS category 
                FROM parking_free_spots 
                ORDER BY name_fr ASC
            ");
            $free = $stmt->fetchAll();
            foreach ($free as &$row) {
                $row['category_group'] = 'free';
            }
            $items = array_merge($items, $free);
        }

        // 2. Parkings privés
        if (!$categoryFilter || $categoryFilter === 'private_parking') {
            $stmt = $this->db->query("
                SELECT *, 'private_parking' AS category 
                FROM parking_private_lots 
                ORDER BY name_fr ASC
            ");
            $priv = $stmt->fetchAll();
            foreach ($priv as &$row) {
                $row['category_group'] = 'private_parking';
            }
            $items = array_merge($items, $priv);
        }

        // 3. Parcs Relais TCL
        if (!$categoryFilter || $categoryFilter === 'tcl_pr') {
            $stmt = $this->db->query("
                SELECT *, 'tcl_pr' AS category 
                FROM parking_tcl_prs 
                ORDER BY name_fr ASC
            ");
            $tcl = $stmt->fetchAll();
            foreach ($tcl as &$row) {
                $row['category_group'] = 'tcl_pr';
            }
            $items = array_merge($items, $tcl);
        }

        // Récupérer les liaisons pour chaque parking du catalogue
        $stmtLinks = $this->db->prepare("
            SELECT 
                a.id AS association_id,
                a.property_id,
                p.name AS property_name,
                p.slug AS property_slug,
                a.walking_time_fr,
                a.walking_time_en,
                a.transit_time_fr,
                a.transit_time_en,
                a.how_to_reach_fr,
                a.how_to_reach_en,
                a.notes_fr,
                a.notes_en,
                a.display_order,
                a.active
            FROM property_parking_associations a
            JOIN properties p ON p.id = a.property_id
            WHERE (a.category = :cat OR (a.category IN ('free_street', 'free_spot') AND :cat2 IN ('free_street', 'free_spot')))
              AND a.parking_item_id = :item_id
            ORDER BY a.display_order ASC, p.name ASC
        ");

        foreach ($items as &$item) {
            $cat = $item['category'];
            $stmtLinks->execute(['cat' => $cat, 'cat2' => $cat, 'item_id' => $item['id']]);
            $item['linked_properties'] = $stmtLinks->fetchAll();
            $item['links_count'] = count($item['linked_properties']);
        }

        return $items;
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
     * Recherche un emplacement au catalogue par son ID et sa catégorie
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
     * Enregistre un parking directement dans le catalogue physique (sans lier obligatoirement à un logement)
     */
    public function saveParkingCatalogItem(array $data): int
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $category = $data['category'] ?? 'free_street';

        // Traduction automatique intelligente
        $data = AiTranslator::translateParkingLocation($this->db, $data);

        if (in_array($category, ['free_street', 'free_spot'])) {
            $spotType = ($category === 'free_spot') ? 'spot' : 'street';
            if ($id > 0) {
                $stmt = $this->db->prepare("
                    UPDATE parking_free_spots SET
                        spot_type = :spot_type,
                        name_fr = :name_fr,
                        name_en = :name_en,
                        address = :address,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'        => $id,
                    'spot_type' => $spotType,
                    'name_fr'   => trim($data['name_fr']),
                    'name_en'   => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'   => !empty($data['address']) ? trim($data['address']) : null,
                    'notes_fr'  => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'  => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                ]);
                ActivityLogger::log('update', 'parking_free_spots', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO parking_free_spots (
                        spot_type, name_fr, name_en, address, notes_fr, notes_en
                    ) VALUES (
                        :spot_type, :name_fr, :name_en, :address, :notes_fr, :notes_en
                    )
                ");
                $stmt->execute([
                    'spot_type' => $spotType,
                    'name_fr'   => trim($data['name_fr']),
                    'name_en'   => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'   => !empty($data['address']) ? trim($data['address']) : null,
                    'notes_fr'  => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'  => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_free_spots', $newId, $data['name_fr']);
                return $newId;
            }
        } elseif ($category === 'private_parking') {
            if ($id > 0) {
                $stmt = $this->db->prepare("
                    UPDATE parking_private_lots SET
                        name_fr = :name_fr,
                        name_en = :name_en,
                        operator = :operator,
                        address = :address,
                        max_height = :max_height,
                        opening_hours_fr = :hours_fr,
                        opening_hours_en = :hours_en,
                        rates_fr = :rates_fr,
                        rates_en = :rates_en,
                        suitable_vehicles_fr = :vehicles_fr,
                        suitable_vehicles_en = :vehicles_en,
                        booking_url = :booking_url,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'          => $id,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'operator'    => !empty($data['operator']) ? trim($data['operator']) : null,
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
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
                ]);
                ActivityLogger::log('update', 'parking_private_lots', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO parking_private_lots (
                        name_fr, name_en, operator, address, max_height,
                        opening_hours_fr, opening_hours_en, rates_fr, rates_en,
                        suitable_vehicles_fr, suitable_vehicles_en, booking_url,
                        notes_fr, notes_en
                    ) VALUES (
                        :name_fr, :name_en, :operator, :address, :max_height,
                        :hours_fr, :hours_en, :rates_fr, :rates_en,
                        :vehicles_fr, :vehicles_en, :booking_url,
                        :notes_fr, :notes_en
                    )
                ");
                $stmt->execute([
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'operator'    => !empty($data['operator']) ? trim($data['operator']) : null,
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
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
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_private_lots', $newId, $data['name_fr']);
                return $newId;
            }
        } elseif ($category === 'tcl_pr') {
            if ($id > 0) {
                $stmt = $this->db->prepare("
                    UPDATE parking_tcl_prs SET
                        name_fr = :name_fr,
                        name_en = :name_en,
                        address = :address,
                        capacity = :capacity,
                        opening_hours_fr = :hours_fr,
                        opening_hours_en = :hours_en,
                        booking_url = :booking_url,
                        notes_fr = :notes_fr,
                        notes_en = :notes_en
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'          => $id,
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'capacity'    => !empty($data['capacity']) ? trim($data['capacity']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                ]);
                ActivityLogger::log('update', 'parking_tcl_prs', $id, $data['name_fr']);
                return $id;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO parking_tcl_prs (
                        name_fr, name_en, address, capacity,
                        opening_hours_fr, opening_hours_en,
                        booking_url, notes_fr, notes_en
                    ) VALUES (
                        :name_fr, :name_en, :address, :capacity,
                        :hours_fr, :hours_en,
                        :booking_url, :notes_fr, :notes_en
                    )
                ");
                $stmt->execute([
                    'name_fr'     => trim($data['name_fr']),
                    'name_en'     => !empty($data['name_en']) ? trim($data['name_en']) : trim($data['name_fr']),
                    'address'     => !empty($data['address']) ? trim($data['address']) : null,
                    'capacity'    => !empty($data['capacity']) ? trim($data['capacity']) : null,
                    'hours_fr'    => !empty($data['opening_hours_fr']) ? trim($data['opening_hours_fr']) : null,
                    'hours_en'    => !empty($data['opening_hours_en']) ? trim($data['opening_hours_en']) : null,
                    'booking_url' => !empty($data['booking_url']) ? trim($data['booking_url']) : null,
                    'notes_fr'    => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                    'notes_en'    => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                ]);
                $newId = (int)$this->db->lastInsertId();
                ActivityLogger::log('create', 'parking_tcl_prs', $newId, $data['name_fr']);
                return $newId;
            }
        }

        return 0;
    }

    /**
     * Enregistre ou met à jour la liaison entre un logement et un parking
     */
    public function linkParkingToProperty(int $propertyId, string $category, int $parkingItemId, array $connectionData = []): int
    {
        // Traduction automatique IA de sécurité si champs anglais non renseignés
        if (!empty($connectionData)) {
            try {
                require_once __DIR__ . '/AiTranslator.php';
                $connectionData = AiTranslator::translateParkingConnection($this->db, $connectionData);
            } catch (\Throwable $e) {
                error_log("[AdminRepository::linkParkingToProperty] Traduction IA : " . $e->getMessage());
            }
        }

        $order = isset($connectionData['display_order']) ? (int)$connectionData['display_order'] : 0;
        if ($order <= 0) {
            $stmtOrder = $this->db->prepare("
                SELECT COALESCE(MAX(display_order), 0) + 1 
                FROM property_parking_associations 
                WHERE property_id = :pid
            ");
            $stmtOrder->execute(['pid' => $propertyId]);
            $order = (int)$stmtOrder->fetchColumn();
        }

        $active = isset($connectionData['active']) ? (int)$connectionData['active'] : 1;

        $stmt = $this->db->prepare("
            INSERT INTO property_parking_associations (
                property_id, category, parking_item_id,
                walking_time_fr, walking_time_en, transit_time_fr, transit_time_en,
                how_to_reach_fr, how_to_reach_en, notes_fr, notes_en,
                display_order, active
            ) VALUES (
                :pid, :cat, :item_id,
                :walking_fr, :walking_en, :transit_fr, :transit_en,
                :reach_fr, :reach_en, :notes_fr, :notes_en,
                :order, :active
            ) ON DUPLICATE KEY UPDATE
                walking_time_fr = VALUES(walking_time_fr),
                walking_time_en = VALUES(walking_time_en),
                transit_time_fr = VALUES(transit_time_fr),
                transit_time_en = VALUES(transit_time_en),
                how_to_reach_fr = VALUES(how_to_reach_fr),
                how_to_reach_en = VALUES(how_to_reach_en),
                notes_fr = VALUES(notes_fr),
                notes_en = VALUES(notes_en),
                display_order = VALUES(display_order),
                active = VALUES(active)
        ");

        $stmt->execute([
            'pid'        => $propertyId,
            'cat'        => $category,
            'item_id'    => $parkingItemId,
            'walking_fr' => !empty($connectionData['walking_time_fr']) ? trim($connectionData['walking_time_fr']) : null,
            'walking_en' => !empty($connectionData['walking_time_en']) ? trim($connectionData['walking_time_en']) : null,
            'transit_fr' => !empty($connectionData['transit_time_fr']) ? trim($connectionData['transit_time_fr']) : null,
            'transit_en' => !empty($connectionData['transit_time_en']) ? trim($connectionData['transit_time_en']) : null,
            'reach_fr'   => !empty($connectionData['how_to_reach_fr']) ? trim($connectionData['how_to_reach_fr']) : null,
            'reach_en'   => !empty($connectionData['how_to_reach_en']) ? trim($connectionData['how_to_reach_en']) : null,
            'notes_fr'   => !empty($connectionData['notes_fr']) ? trim($connectionData['notes_fr']) : null,
            'notes_en'   => !empty($connectionData['notes_en']) ? trim($connectionData['notes_en']) : null,
            'order'      => $order > 0 ? $order : 1,
            'active'     => $active
        ]);

        $assocId = (int)$this->db->lastInsertId();
        if ($assocId === 0) {
            $stmtGet = $this->db->prepare("SELECT id FROM property_parking_associations WHERE property_id = :pid AND category = :cat AND parking_item_id = :item_id LIMIT 1");
            $stmtGet->execute(['pid' => $propertyId, 'cat' => $category, 'item_id' => $parkingItemId]);
            $assocId = (int)$stmtGet->fetchColumn();
        }

        ActivityLogger::log('link', 'property_parking_associations', $assocId, "Liaison Parking #{$parkingItemId} -> Logement #{$propertyId}");
        return $assocId;
    }

    /**
     * Dissocie un parking d'un logement (retire l'association sans supprimer le parking du catalogue)
     */
    public function unlinkParkingFromProperty(int $associationId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM property_parking_associations WHERE id = :id");
            $ok = $stmt->execute(['id' => $associationId]);
            if ($ok) {
                ActivityLogger::log('unlink', 'property_parking_associations', $associationId, "Dissociation parking");
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Met à jour les informations de liaison d'un parking pour un logement précis
     */
    public function updateParkingAssociation(int $associationId, array $data): bool
    {
        try {
            // Traduction automatique IA de sécurité si champs anglais non renseignés
            if (!empty($data)) {
                try {
                    require_once __DIR__ . '/AiTranslator.php';
                    $data = AiTranslator::translateParkingConnection($this->db, $data);
                } catch (\Throwable $e) {
                    error_log("[AdminRepository::updateParkingAssociation] Traduction IA : " . $e->getMessage());
                }
            }

            $stmt = $this->db->prepare("
                UPDATE property_parking_associations SET
                    walking_time_fr = :walking_fr,
                    walking_time_en = :walking_en,
                    transit_time_fr = :transit_fr,
                    transit_time_en = :transit_en,
                    how_to_reach_fr = :reach_fr,
                    how_to_reach_en = :reach_en,
                    notes_fr = :notes_fr,
                    notes_en = :notes_en,
                    display_order = :order,
                    active = :active
                WHERE id = :id
            ");
            return $stmt->execute([
                'id'         => $associationId,
                'walking_fr' => !empty($data['walking_time_fr']) ? trim($data['walking_time_fr']) : null,
                'walking_en' => !empty($data['walking_time_en']) ? trim($data['walking_time_en']) : null,
                'transit_fr' => !empty($data['transit_time_fr']) ? trim($data['transit_time_fr']) : null,
                'transit_en' => !empty($data['transit_time_en']) ? trim($data['transit_time_en']) : null,
                'reach_fr'   => !empty($data['how_to_reach_fr']) ? trim($data['how_to_reach_fr']) : null,
                'reach_en'   => !empty($data['how_to_reach_en']) ? trim($data['how_to_reach_en']) : null,
                'notes_fr'   => !empty($data['notes_fr']) ? trim($data['notes_fr']) : null,
                'notes_en'   => !empty($data['notes_en']) ? trim($data['notes_en']) : null,
                'order'      => isset($data['display_order']) ? (int)$data['display_order'] : 1,
                'active'     => isset($data['active']) ? (int)$data['active'] : 1
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Synchronise l'ensemble des logements reliés à un parking (association inverse depuis le catalogue)
     */
    public function saveParkingItemPropertiesLinks(string $category, int $parkingItemId, array $links): bool
    {
        $this->db->beginTransaction();
        try {
            // Logements autorisés à conserver
            $keptPropertyIds = [];
            foreach ($links as $link) {
                $pid = (int)($link['property_id'] ?? 0);
                if ($pid <= 0) continue;
                $keptPropertyIds[] = $pid;

                $this->linkParkingToProperty($pid, $category, $parkingItemId, $link);
            }

            // Supprimer les liaisons qui ont été décochées
            $catFilter = ($category === 'free_street' || $category === 'free_spot') 
                ? "category IN ('free_street', 'free_spot')" 
                : "category = " . $this->db->quote($category);

            if (empty($keptPropertyIds)) {
                $stmtDel = $this->db->prepare("DELETE FROM property_parking_associations WHERE {$catFilter} AND parking_item_id = :item_id");
                $stmtDel->execute(['item_id' => $parkingItemId]);
            } else {
                $inClause = implode(',', array_map('intval', $keptPropertyIds));
                $stmtDel = $this->db->prepare("
                    DELETE FROM property_parking_associations 
                    WHERE {$catFilter} AND parking_item_id = :item_id AND property_id NOT IN ({$inClause})
                ");
                $stmtDel->execute(['item_id' => $parkingItemId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Enregistre un parking et l'associe immédiatement à un logement (création / édition à la volée)
     */
    public function saveParkingItem(array $data): int
    {
        $propertyId = (int)($data['property_id'] ?? 0);
        $category = $data['category'] ?? 'free_street';
        $parkingItemId = isset($data['parking_item_id']) ? (int)$data['parking_item_id'] : (isset($data['id']) ? (int)$data['id'] : 0);

        // 1. Enregistre au catalogue
        $data['id'] = $parkingItemId;
        $savedCatalogId = $this->saveParkingCatalogItem($data);
        if ($savedCatalogId <= 0) {
            return 0;
        }

        // 2. Si un logement est ciblé ET qu'il s'agit d'une création à la volée, crée la liaison initiale
        if ($parkingItemId === 0 && $propertyId > 0) {
            $this->linkParkingToProperty($propertyId, $category, $savedCatalogId, $data);
        }

        return $savedCatalogId;
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

    /**
     * Réordonne les liaisons pour un logement donné
     */
    public function reorderParkingItems(string $category, array $orderList, int $propertyId = 0): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE property_parking_associations SET display_order = :order WHERE id = :assoc_id");
            foreach ($orderList as $order => $assocId) {
                $stmt->execute(['order' => (int)$order + 1, 'assoc_id' => (int)$assocId]);
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
        return $this->reorderParkingItems('all', $orderList);
    }

    /**
     * Bascule la visibilité (actif/masqué) d'une association de parking
     */
    public function toggleParkingLocationActive(int $associationId, ?string $category = null): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT active FROM property_parking_associations WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $associationId]);
            $current = $stmt->fetchColumn();
            if ($current === false) return false;

            $newStatus = (int)$current === 1 ? 0 : 1;
            $stmtUp = $this->db->prepare("UPDATE property_parking_associations SET active = :status WHERE id = :id");
            $ok = $stmtUp->execute(['status' => $newStatus, 'id' => $associationId]);
            if ($ok) {
                ActivityLogger::log('toggle', 'property_parking_associations', $associationId, "Visibilité liaison parking", ['active' => $newStatus]);
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Supprime définitivement un parking du catalogue physique et toutes ses liaisons associées
     */
    public function deleteParkingCatalogItem(string $category, int $parkingItemId): bool
    {
        $table = 'parking_free_spots';
        if ($category === 'private_parking') {
            $table = 'parking_private_lots';
        } elseif ($category === 'tcl_pr') {
            $table = 'parking_tcl_prs';
        }

        $this->db->beginTransaction();
        try {
            // 1. Supprime les liaisons (supporte la polymorphie free_street / free_spot)
            if ($table === 'parking_free_spots') {
                $stmtAssoc = $this->db->prepare("DELETE FROM property_parking_associations WHERE category IN ('free_street', 'free_spot') AND parking_item_id = :item_id");
                $stmtAssoc->execute(['item_id' => $parkingItemId]);
            } else {
                $stmtAssoc = $this->db->prepare("DELETE FROM property_parking_associations WHERE category = :cat AND parking_item_id = :item_id");
                $stmtAssoc->execute(['cat' => $category, 'item_id' => $parkingItemId]);
            }

            // 2. Supprime l'entrée du catalogue
            $stmtCat = $this->db->prepare("DELETE FROM `{$table}` WHERE id = :id");
            $stmtCat->execute(['id' => $parkingItemId]);

            $this->db->commit();
            ActivityLogger::log('delete', $table, $parkingItemId, "Suppression complète catalogue #{$parkingItemId}");
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteParkingLocation(int $id, ?string $category = null): bool
    {
        // Si l'ID passé correspond à une liaison d'association
        $stmtCheck = $this->db->prepare("SELECT category, parking_item_id FROM property_parking_associations WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $id]);
        $row = $stmtCheck->fetch();
        if ($row) {
            return $this->unlinkParkingFromProperty($id);
        }

        // Sinon supprime du catalogue
        $cat = $category ?? 'free_street';
        return $this->deleteParkingCatalogItem($cat, $id);
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
