<?php
declare(strict_types=1);

namespace Cdh\Portal\Admin;

use PDO;
use RuntimeException;
use Throwable;

/**
 * ==============================================================================
 * SERVICE DE TÉLÉVERSEMENT DE MÉDIAS (GUIDES PDF & VIDÉOS)
 * Conciergerie Carré d'Hôtes - Administration
 * ==============================================================================
 * Téléversement exclusif vers Cloudflare R2 (API S3 SigV4) avec arborescence :
 * - Guides PDF : carredhotes-guides/[slug-logement]/guide-fr.pdf (ou guide-en.pdf)
 * - Vidéos     : carredhotes-videos/[slug-logement]/[slug-vidéo].mp4 (ou .webm / .mov)
 */
class MediaUploader
{
    public const MAX_GUIDE_SIZE_BYTES = 52428800;  // 50 Mo
    public const MAX_VIDEO_SIZE_BYTES = 262144000; // 250 Mo

    public const ALLOWED_GUIDE_MIMES = [
        'application/pdf',
        'application/x-pdf',
    ];

    public const ALLOWED_VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/quicktime', // .mov
        'video/x-m4v',
    ];

    /**
     * Traite un fichier téléversé et l'envoie sur Cloudflare R2
     *
     * @param array $file Élément de $_FILES
     * @param string $category 'guide' ou 'video'
     * @param array $params Paramètres contextuels (property_slug, video_slug, lang)
     * @return array Résultat complet du téléversement
     */
    public static function handleUpload(array $file, string $category, array $params = []): array
    {
        // 1. Validation de l'erreur d'upload native de PHP
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException("Paramètres de fichier invalides.");
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $maxServer = ini_get('upload_max_filesize');
                throw new RuntimeException("Le fichier dépasse la taille maximale autorisée par le serveur ({$maxServer}).");
            case UPLOAD_ERR_PARTIAL:
                throw new RuntimeException("Le téléversement du fichier a été interrompu.");
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException("Aucun fichier n'a été envoyé.");
            default:
                throw new RuntimeException("Erreur lors de la réception du fichier (code {$file['error']}).");
        }

        $tmpPath = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            throw new RuntimeException("Fichier téléversé invalide ou suspect.");
        }

        $fileSize = (int)$file['size'];
        $originalName = (string)($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // 2. Détection du type MIME réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : mime_content_type($tmpPath);
        if ($finfo) {
            finfo_close($finfo);
        }

        // 3. Validation selon la catégorie
        if ($category === 'guide') {
            if ($fileSize > self::MAX_GUIDE_SIZE_BYTES) {
                throw new RuntimeException("Le livret PDF ne doit pas dépasser 50 Mo.");
            }
            if ($ext !== 'pdf' || !in_array($mimeType, self::ALLOWED_GUIDE_MIMES, true)) {
                throw new RuntimeException("Format non autorisé. Seuls les fichiers PDF (.pdf) sont acceptés pour les livrets d'accueil.");
            }
        } elseif ($category === 'video') {
            if ($fileSize > self::MAX_VIDEO_SIZE_BYTES) {
                throw new RuntimeException("La vidéo ne doit pas dépasser 250 Mo.");
            }
            $allowedExts = ['mp4', 'webm', 'mov', 'm4v'];
            if (!in_array($ext, $allowedExts, true) || !in_array($mimeType, self::ALLOWED_VIDEO_MIMES, true)) {
                throw new RuntimeException("Format non autorisé. Seules les vidéos MP4, WebM ou MOV sont acceptées.");
            }
        } else {
            throw new RuntimeException("Catégorie de média inconnue : {$category}.");
        }

        // 4. Validation impérative des slugs et détermination de l'arborescence
        $propSlug = self::sanitizeSlug((string)($params['property_slug'] ?? ''));
        if ($propSlug === '') {
            throw new RuntimeException("Le slug du logement est obligatoire pour définir son dossier sur Cloudflare R2.");
        }

        if ($category === 'guide') {
            $lang = (!empty($params['lang']) && strtolower((string)$params['lang']) === 'en') ? 'en' : 'fr';
            // Destination R2 : carredhotes-guides/[slug-logement]/guide-fr.pdf (ou guide-en.pdf)
            $folder = $propSlug;
            $filename = "guide-{$lang}.pdf";
        } elseif ($category === 'video') {
            $videoSlug = self::sanitizeSlug((string)($params['video_slug'] ?? ''));
            if ($videoSlug === '') {
                throw new RuntimeException("Le slug de la vidéo est obligatoire pour nommer le fichier sur Cloudflare R2.");
            }
            // Destination R2 : carredhotes-videos/[slug-logement]/[slug-vidéo].ext
            $folder = $propSlug;
            $filename = "{$videoSlug}.{$ext}";
        }

        // 5. Vérifier la configuration Cloudflare R2
        $r2Config = self::getR2Config();
        if (empty($r2Config['account_id']) || empty($r2Config['access_key_id']) || empty($r2Config['secret_access_key'])) {
            throw new RuntimeException(
                "Cloudflare R2 n'est pas encore configuré. " .
                "Veuillez renseigner votre Account ID, Access Key ID et Secret Key dans les paramètres " .
                "de Configuration (onglet Stockage Cloudflare R2) avant de téléverser un fichier."
            );
        }

        // 6. Téléversement exclusif vers Cloudflare R2 (avec création automatique du dossier si inexistant)
        return self::uploadToCloudflareR2($tmpPath, $category, $folder, $filename, $mimeType, $fileSize, $r2Config);
    }

    /**
     * Nettoie et normalise un slug (minuscules, chiffres, tirets uniquement)
     */
    public static function sanitizeSlug(string $str): string
    {
        $str = preg_replace('~[^\pL\d]+~u', '-', $str);
        $str = iconv('utf-8', 'us-ascii//TRANSLIT', (string)$str);
        $str = preg_replace('~[^-\w]+~', '', (string)$str);
        $str = trim((string)$str, '-');
        $str = preg_replace('~-+~', '-', (string)$str);
        return strtolower((string)$str);
    }

    /**
     * S'assure que le dossier de l'hébergement existe dans le bucket Cloudflare R2
     * S'il n'existe pas, crée un marqueur de dossier explicite ([slug-logement]/)
     */
    public static function ensureR2FolderExists(
        string $accountId,
        string $accessKey,
        string $secretKey,
        string $bucket,
        string $folder
    ): void {
        $folder = trim($folder, '/');
        if ($folder === '') {
            return;
        }

        $folderKey = "{$folder}/";

        // 1. Vérifie si le marqueur de dossier existe déjà (HEAD /bucket/folder/)
        $headRes = self::sendR2Request('HEAD', $accountId, $accessKey, $secretKey, $bucket, $folderKey);
        if ($headRes['http_code'] === 200) {
            return; // Dossier déjà existant
        }

        // 2. Si non trouvé (HTTP 404), on crée le dossier via un PUT de 0 octet
        self::sendR2Request(
            'PUT',
            $accountId,
            $accessKey,
            $secretKey,
            $bucket,
            $folderKey,
            '',
            '',
            'application/x-directory'
        );
    }

    /**
     * Envoie le fichier dans Cloudflare R2 dans son dossier dédié ([slug-logement]/[filename])
     */
    public static function uploadToCloudflareR2(
        string $tmpPath,
        string $category,
        string $folder,
        string $filename,
        string $mimeType,
        int $fileSize,
        array $config
    ): array {
        $accountId = trim((string)$config['account_id']);
        $accessKey = trim((string)$config['access_key_id']);
        $secretKey = trim((string)$config['secret_access_key']);

        $bucket = ($category === 'guide')
            ? (!empty($config['bucket_guides']) ? $config['bucket_guides'] : 'carredhotes-guides')
            : (!empty($config['bucket_videos']) ? $config['bucket_videos'] : 'carredhotes-videos');

        $customDomain = ($category === 'guide')
            ? (!empty($config['domain_guides']) ? $config['domain_guides'] : 'https://guides.carredhotes.fr')
            : (!empty($config['domain_videos']) ? $config['domain_videos'] : 'https://videos.carredhotes.fr');

        // Création du dossier [slug-logement]/ dans Cloudflare R2 s'il n'existe pas encore
        self::ensureR2FolderExists($accountId, $accessKey, $secretKey, $bucket, $folder);

        // Clé S3 de destination : [slug-logement]/[filename]
        $objectKey = rtrim($folder, '/') . '/' . ltrim($filename, '/');

        $body = file_get_contents($tmpPath);
        if ($body === false) {
            throw new RuntimeException("Impossible de lire le fichier temporaire avant envoi vers Cloudflare R2.");
        }

        $res = self::sendR2Request('PUT', $accountId, $accessKey, $secretKey, $bucket, $objectKey, '', $body, $mimeType);

        if ($res['error']) {
            throw new RuntimeException("Erreur réseau cURL lors de l'envoi vers Cloudflare R2 : {$res['error']}");
        }

        if ($res['http_code'] !== 200 && $res['http_code'] !== 204) {
            $detail = self::parseR2ErrorDetail($res['response']);
            $errMsg = "Cloudflare R2 a renvoyé l'erreur HTTP {$res['http_code']}";
            if ($detail !== '') {
                $errMsg .= " ({$detail})";
            }
            if ($res['http_code'] === 403) {
                $errMsg .= " — Vérifiez vos clés API S3 R2 et leurs permissions sur le bucket « {$bucket} ».";
            } elseif ($res['http_code'] === 404) {
                $errMsg .= " — Le bucket « {$bucket} » n'existe pas dans votre compte Cloudflare R2.";
            }
            throw new RuntimeException($errMsg);
        }

        // Construction de l'URL publique finale via le sous-domaine Cloudflare configuré
        $publicUrl = rtrim($customDomain, '/') . '/' . $objectKey;

        return [
            'ok'             => true,
            'url'            => $publicUrl,
            'relative_url'   => $publicUrl,
            'object_key'     => $objectKey,
            'folder'         => $folder,
            'filename'       => $filename,
            'size'           => $fileSize,
            'size_formatted' => self::formatBytes($fileSize),
            'mime_type'      => $mimeType,
            'storage'        => 'r2',
            'storage_label'  => 'Cloudflare R2',
        ];
    }

    /**
     * Teste la connexion à Cloudflare R2 (vérification de bucket sans transfert lourd)
     */
    public static function testR2Connection(array $config): array
    {
        $accountId = trim((string)($config['account_id'] ?? ''));
        $accessKey = trim((string)($config['access_key_id'] ?? ''));
        $secretKey = trim((string)($config['secret_access_key'] ?? ''));
        $bucketGuides = trim((string)($config['bucket_guides'] ?? ''));
        $bucketVideos = trim((string)($config['bucket_videos'] ?? ''));

        if (empty($accountId) || empty($accessKey) || empty($secretKey)) {
            return [
                'ok'    => false,
                'error' => 'Identifiants R2 incomplets : l\'Account ID, l\'Access Key ID et la Secret Access Key sont obligatoires.'
            ];
        }

        // Liste des buckets à tester
        $bucketsToTest = [];
        if (!empty($bucketGuides)) {
            $bucketsToTest[] = $bucketGuides;
        }
        if (!empty($bucketVideos) && !in_array($bucketVideos, $bucketsToTest, true)) {
            $bucketsToTest[] = $bucketVideos;
        }
        if (empty($bucketsToTest)) {
            $bucketsToTest[] = 'carredhotes-guides';
        }

        $testedBuckets = [];
        foreach ($bucketsToTest as $b) {
            $res = self::sendR2Request('GET', $accountId, $accessKey, $secretKey, $b, '', 'max-keys=1');

            if ($res['error']) {
                return [
                    'ok'    => false,
                    'error' => "Erreur réseau cURL : {$res['error']}"
                ];
            }

            if ($res['http_code'] !== 200) {
                $detail = self::parseR2ErrorDetail($res['response']);
                $errMsg = "Erreur HTTP {$res['http_code']}";
                if ($detail !== '') {
                    $errMsg .= " ({$detail})";
                }

                if ($res['http_code'] === 403) {
                    $errMsg .= " — Vérifiez vos identifiants Cloudflare R2 et les autorisations du token sur le bucket « {$b} ».";
                } elseif ($res['http_code'] === 404) {
                    $errMsg .= " — Le bucket « {$b} » n'existe pas sur votre compte Cloudflare R2.";
                }

                return [
                    'ok'    => false,
                    'error' => $errMsg
                ];
            }

            $testedBuckets[] = $b;
        }

        $count = count($testedBuckets);
        $msg = ($count > 1)
            ? "Connexion Cloudflare R2 réussie ! Les buckets « " . implode(' » et « ', $testedBuckets) . " » sont parfaitement accessibles."
            : "Connexion Cloudflare R2 réussie ! Le bucket « {$testedBuckets[0]} » est parfaitement accessible.";

        return [
            'ok'      => true,
            'message' => $msg
        ];
    }

    /**
     * Lit la configuration Cloudflare R2 enregistrée en base de données
     */
    public static function getR2Config(): array
    {
        try {
            $db = \Cdh\Portal\Database::getConnection();
            $stmt = $db->query('SELECT setting_key, setting_value FROM admin_settings WHERE setting_key LIKE "r2_%"');
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            return [
                'account_id'        => (string)($settings['r2_account_id'] ?? ''),
                'access_key_id'     => (string)($settings['r2_access_key_id'] ?? ''),
                'secret_access_key' => (string)($settings['r2_secret_access_key'] ?? ''),
                'bucket_guides'     => (string)($settings['r2_bucket_guides'] ?? 'carredhotes-guides'),
                'domain_guides'     => (string)($settings['r2_domain_guides'] ?? 'https://guides.carredhotes.fr'),
                'bucket_videos'     => (string)($settings['r2_bucket_videos'] ?? 'carredhotes-videos'),
                'domain_videos'     => (string)($settings['r2_domain_videos'] ?? 'https://videos.carredhotes.fr'),
            ];
        } catch (Throwable $e) {
            return [
                'account_id'        => '',
                'access_key_id'     => '',
                'secret_access_key' => '',
                'bucket_guides'     => 'carredhotes-guides',
                'domain_guides'     => 'https://guides.carredhotes.fr',
                'bucket_videos'     => 'carredhotes-videos',
                'domain_videos'     => 'https://videos.carredhotes.fr',
            ];
        }
    }

    /**
     * Effectue une requête S3 SigV4 vers Cloudflare R2
     */
    private static function sendR2Request(
        string $method,
        string $accountId,
        string $accessKey,
        string $secretKey,
        string $bucket,
        string $objectKey = '',
        string $queryString = '',
        string $body = '',
        string $contentType = ''
    ): array {
        $host = "{$accountId}.r2.cloudflarestorage.com";
        $path = '/' . $bucket;
        if ($objectKey !== '') {
            $path .= '/' . ltrim($objectKey, '/');
        }

        $endpoint = "https://{$host}{$path}";
        if ($queryString !== '') {
            $endpoint .= '?' . $queryString;
        }

        $now = time();
        $isoDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $payloadHash = hash('sha256', $body);
        $contentLength = strlen($body);

        // En-têtes à signer (triés obligatoirement par ordre alphabétique pour AWS SigV4)
        $headersMap = [];
        if ($contentLength > 0 || $method === 'PUT') {
            $headersMap['content-length'] = (string)$contentLength;
        }
        if ($contentType !== '') {
            $headersMap['content-type'] = $contentType;
        }
        $headersMap['host'] = $host;
        $headersMap['x-amz-content-sha256'] = $payloadHash;
        $headersMap['x-amz-date'] = $isoDate;

        ksort($headersMap);

        $canonicalHeaders = '';
        $signedHeadersList = [];
        $curlHeaders = [];

        foreach ($headersMap as $k => $v) {
            $canonicalHeaders .= "{$k}:{$v}\n";
            $signedHeadersList[] = $k;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalRequest = "{$method}\n"
                          . "{$path}\n"
                          . "{$queryString}\n"
                          . $canonicalHeaders
                          . "\n"
                          . $signedHeaders . "\n"
                          . $payloadHash;

        $stringToSign = "AWS4-HMAC-SHA256\n"
                      . "{$isoDate}\n"
                      . "{$dateStamp}/auto/s3/aws4_request\n"
                      . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$dateStamp}/auto/s3/aws4_request, SignedHeaders={$signedHeaders}, Signature={$signature}";

        foreach ($headersMap as $k => $v) {
            $curlHeaders[] = "{$k}: {$v}";
        }
        $curlHeaders[] = "Authorization: {$authorization}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => ($method === 'PUT' ? 120 : 20),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response'  => (string)$response,
            'error'     => $curlError,
        ];
    }

    /**
     * Extrait le message d'erreur d'une réponse XML Cloudflare R2
     */
    private static function parseR2ErrorDetail(string $xml): string
    {
        if (empty($xml)) {
            return '';
        }

        if (preg_match('/<Message>(.*?)<\/Message>/s', $xml, $m)) {
            return htmlspecialchars_decode(trim($m[1]));
        }

        if (preg_match('/<Code>(.*?)<\/Code>/s', $xml, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Formate les octets en Ko / Mo lisibles
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', ' ') . ' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', ' ') . ' Ko';
        }
        return $bytes . ' o';
    }
}
