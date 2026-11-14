<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * CLIENT D'INTÉGRATION API SUPERHOTE (RÉSERVATIONS & DISPONIBILITÉS)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 * Communique de manière sécurisée (côté serveur uniquement) avec l'API SuperHote V2.
 * Ne divulgue AUCUNE donnée financière ou confidentielle (commissions, reversements,
 * détails de tarification interne, etc.).
 */

namespace Cdh\Portal\Service;

use Cdh\Portal\Env;

class SuperhoteClient
{
    private string $baseUrl;
    private string $apiToken;
    private int $cacheTtlSeconds;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, int $cacheTtlSeconds = 60)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string)Env::get('SUPERHOTE_API_URL', 'https://connect.superhote.com/api/v2/public'), '/');
        $this->apiToken = trim($apiToken ?? (string)Env::get('SUPERHOTE_API_TOKEN', '95594|8wHpZfIFTbwRd5MWghlbXH7ZHGk85JI1KPII3STkf6381f17'));
        $this->cacheTtlSeconds = $cacheTtlSeconds;
    }

    /**
     * Récupère et vérifie une réservation depuis l'API SuperHote
     *
     * @param string|int $reservationId Identifiant de réservation
     * @return array Résultat standardisé :
     *               ['ok' => true, 'status' => 'CONFIRMED', 'rental_id' => '...', 'reservation' => [...]]
     *               ou ['ok' => false, 'reason' => 'not_found'|'unconfirmed'|'api_error', 'message' => '...', 'reservation' => [...]]
     */
    public function getReservation(string|int $reservationId): array
    {
        $idStr = trim((string)$reservationId);
        if (!preg_match('/^[0-9]{1,20}$/', $idStr)) {
            return [
                'ok'      => false,
                'reason'  => 'invalid_id',
                'message' => 'Identifiant de réservation invalide (doit être numérique).'
            ];
        }

        if (empty($this->apiToken)) {
            return [
                'ok'      => false,
                'reason'  => 'config_error',
                'message' => 'Clé API SuperHote non configurée dans le fichier .env.'
            ];
        }

        // Cache transitoire pour éviter d'épuiser le quota de 120 req/min
        $cached = $this->getFromCache($idStr);
        if ($cached !== null) {
            return $cached;
        }

        $url = $this->baseUrl . '/reservations/' . urlencode($idStr);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiToken,
                'Accept: application/json',
                'User-Agent: CarreDHotes-Portal/1.0 (+https://carredhotes.fr)'
            ]
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false || !empty($curlError)) {
            return [
                'ok'      => false,
                'reason'  => 'api_error',
                'message' => 'Impossible de contacter les serveurs SuperHote (' . ($curlError ?: 'délai dépassé') . ').'
            ];
        }

        $json = json_decode((string)$rawResponse, true);

        // 1. Réservation introuvable (404)
        if ($httpCode === 404 || (is_array($json) && isset($json['success']) && $json['success'] === false && ($json['error_code'] ?? 0) === 1)) {
            $res = [
                'ok'             => false,
                'reason'         => 'not_found',
                'reservation_id' => $idStr,
                'message'        => 'Réservation introuvable ou inaccessible.'
            ];
            $this->saveToCache($idStr, $res, 30);
            return $res;
        }

        // 2. Erreur de quota API (429)
        if ($httpCode === 429) {
            return [
                'ok'      => false,
                'reason'  => 'rate_limit',
                'message' => 'Limite de requêtes atteinte auprès de SuperHote. Veuillez patienter un instant.'
            ];
        }

        // 3. Autre code HTTP d'erreur
        if ($httpCode !== 200 || !is_array($json) || empty($json['success']) || !isset($json['result'])) {
            $msg = is_array($json) && !empty($json['message']) ? $json['message'] : "Erreur HTTP {$httpCode}";
            return [
                'ok'      => false,
                'reason'  => 'api_error',
                'message' => 'Erreur lors de la récupération de la réservation : ' . $msg
            ];
        }

        $data = $json['result'];
        $rawStatus = (int)($data['status'] ?? 0);
        $statusLabel = strtoupper(trim((string)($data['status_label'] ?? '')));
        $rentalId = (string)($data['rental_id'] ?? '');

        // Extraction STRICTEMENT SÉCURISÉE des données utiles pour le voyageur (zéro donnée financière exposée)
        $safeReservation = [
            'id'               => (string)($data['id'] ?? $idStr),
            'rental_id'        => $rentalId,
            'status'           => $rawStatus,
            'status_label'     => $statusLabel,
            'platform'         => $data['platform'] ?? null,
            'platform_name'    => !empty($data['platform_name']) ? (string)$data['platform_name'] : 'Réservation Directe',
            'checkin'          => $data['checkin'] ?? null,
            'checkout'         => $data['checkout'] ?? null,
            'checkin_time'     => !empty($data['checkin_time']) ? (string)$data['checkin_time'] : '15:00',
            'checkout_time'    => !empty($data['checkout_time']) ? (string)$data['checkout_time'] : '10:00',
            'nights'           => isset($data['nights']) ? (int)$data['nights'] : null,
            'guests_count'     => isset($data['guests_count']) ? (int)$data['guests_count'] : 1,
            'adults_count'     => isset($data['adults_count']) ? (int)$data['adults_count'] : 1,
            'children_count'   => isset($data['children_count']) ? (int)$data['children_count'] : 0,
            'guest_first_name' => trim((string)($data['guest_first_name'] ?? '')),
            'guest_last_name'  => trim((string)($data['guest_last_name'] ?? ''))
        ];

        // 4. Vérification du statut de la réservation
        // Seul le statut CONFIRMED (status = 1) donne accès au portail complet du logement
        $isConfirmed = ($rawStatus === 1 || $statusLabel === 'CONFIRMED');

        if (!$isConfirmed) {
            $res = [
                'ok'             => false,
                'reason'         => 'unconfirmed',
                'status_code'    => $rawStatus,
                'status_label'   => $statusLabel,
                'reservation_id' => $idStr,
                'rental_id'      => $rentalId,
                'reservation'    => $safeReservation,
                'message'        => 'Réservation non confirmée.'
            ];
            $this->saveToCache($idStr, $res, 30);
            return $res;
        }

        // 5. Réservation confirmée
        $res = [
            'ok'             => true,
            'reason'         => 'confirmed',
            'status_code'    => $rawStatus,
            'status_label'   => $statusLabel,
            'rental_id'      => $rentalId,
            'reservation_id' => $idStr,
            'reservation'    => $safeReservation
        ];

        $this->saveToCache($idStr, $res, $this->cacheTtlSeconds);
        return $res;
    }

    /**
     * Récupération du cache fichier transitoire
     */
    private function getFromCache(string $reservationId): ?array
    {
        if ($this->cacheTtlSeconds <= 0) {
            return null;
        }

        $cacheFile = sys_get_temp_dir() . '/cdh_sh_res_' . md5($reservationId) . '.json';
        if (file_exists($cacheFile)) {
            $mtime = @filemtime($cacheFile);
            if ($mtime && (time() - $mtime) < $this->cacheTtlSeconds) {
                $content = @file_get_contents($cacheFile);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Écriture dans le cache fichier transitoire
     */
    private function saveToCache(string $reservationId, array $data, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        $cacheFile = sys_get_temp_dir() . '/cdh_sh_res_' . md5($reservationId) . '.json';
        @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
