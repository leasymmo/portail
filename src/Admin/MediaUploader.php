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
 * Gère le téléversement sécurisé de fichiers avec double mode :
 * 1. Stockage Local OVH (par défaut, zéro configuration)
 * 2. Cloudflare R2 Object Storage (S3 SigV4 sans dépendances externes)
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
     * Traite un fichier téléversé et retourne l'URL publique générée
     *
     * @param array $file Élément de $_FILES
     * @param string $category 'guide' ou 'video'
     * @param array $params Paramètres contextuels (property_slug, lang, video_slug)
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
                throw new RuntimeException("Format non autorisé. Seuls les fichiers PDF (.pdf) sont acceptés pour les guides.");
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

        // 4. Génération d'un nom de fichier propre et sécurisé
        $filename = self::generateFilename($category, $ext, $params, $originalName);

        // 5. Vérifier la configuration du stockage (Cloudflare R2 ou Serveur Local)
        $r2Config = self::getR2Config();
        $useR2 = ($r2Config['enabled'] && !empty($r2Config['account_id']) && !empty($r2Config['access_key_id']) && !empty($r2Config['secret_access_key']));

        if ($useR2) {
            try {
                return self::uploadToCloudflareR2($tmpPath, $category, $filename, $mimeType, $fileSize, $r2Config);
            } catch (Throwable $e) {
                // En cas d'erreur R2 spécifique, enregistrer le log et lever l'exception avec détails clairs
                throw new RuntimeException("Échec de l'envoi vers Cloudflare R2 : " . $e->getMessage());
            }
        }

        // Stockage local standard
        return self::uploadToLocal($tmpPath, $category, $filename, $mimeType, $fileSize);
    }

    /**
     * Génère un nom de fichier standardisé et sécurisé
     */
    private static function generateFilename(string $category, string $ext, array $params, string $originalName): string
    {
        $cleanSlug = function (string $str): string {
            $str = preg_replace('~[^\pL\d]+~u', '-', $str);
            $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
            $str = preg_replace('~[^-\w]+~', '', $str);
            $str = trim($str, '-');
            $str = preg_replace('~-+~', '-', $str);
            return strtolower($str);
        };

        if ($category === 'guide') {
            $propSlug = !empty($params['property_slug']) ? $cleanSlug((string)$params['property_slug']) : 'logement';
            $lang = !empty($params['lang']) && strtolower((string)$params['lang']) === 'en' ? 'en' : 'fr';
            // Ex: bataille-guide-fr.pdf
            return "{$propSlug}-guide-{$lang}.pdf";
        }

        if ($category === 'video') {
            $propSlug = !empty($params['property_slug']) ? $cleanSlug((string)$params['property_slug']) : 'general';
            $videoSlug = !empty($params['video_slug']) ? $cleanSlug((string)$params['video_slug']) : '';
            if (empty($videoSlug)) {
                $base = pathinfo($originalName, PATHINFO_FILENAME);
                $videoSlug = $cleanSlug($base);
                if (empty($videoSlug)) {
                    $videoSlug = 'tutoriel-' . time();
                }
            }
            // Ex: bataille-machine-a-cafe.mp4
            return "{$propSlug}-{$videoSlug}.{$ext}";
        }

        return time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    }

    /**
     * Stocke le fichier sur le serveur local OVH
     */
    private static function uploadToLocal(string $tmpPath, string $category, string $filename, string $mimeType, int $fileSize): array
    {
        $rootDir = dirname(__DIR__, 2);
        $subDir = ($category === 'guide') ? 'guides' : 'videos';
        $targetDir = $rootDir . '/uploads/' . $subDir;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Impossible de créer le dossier de destination : /uploads/{$subDir}");
            }
        }

        // Création/mise à jour du .htaccess de sécurité si manquant
        $htaccessPath = $rootDir . '/uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = <<<HTACCESS
<FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|pl|py|cgi|asp|sh|bash|exe|html|htm|js)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
<FilesMatch "(?i)\.(pdf|mp4|webm|mov|m4v|jpg|jpeg|png|webp|svg)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
<IfModule mod_headers.c>
    Header set Accept-Ranges bytes
    Header set Access-Control-Allow-Origin "*"
</IfModule>
Options -Indexes -ExecCGI
HTACCESS;
            @file_put_contents($htaccessPath, $htaccessContent);
        }

        $destPath = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            // Repli sur copy si move_uploaded_file échoue (ex: restrictions open_basedir)
            if (!copy($tmpPath, $destPath)) {
                throw new RuntimeException("Échec de l'enregistrement du fichier dans {$targetDir}.");
            }
            @unlink($tmpPath);
        }

        @chmod($destPath, 0644);

        // Détection de l'URL publique
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.carredhotes.fr';

        // Base directory (ex: /portail/dev ou /portail)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/api/index.php');
        $portalBasePath = rtrim(dirname($scriptDir, 2), '/');
        if ($portalBasePath === '\\') {
            $portalBasePath = '';
        }

        $relativeUrl = $portalBasePath . '/uploads/' . $subDir . '/' . $filename;
        $absoluteUrl = $protocol . $host . $relativeUrl;

        return [
            'ok'             => true,
            'url'            => $absoluteUrl,
            'relative_url'   => $relativeUrl,
            'filename'       => $filename,
            'size'           => $fileSize,
            'size_formatted' => self::formatBytes($fileSize),
            'mime_type'      => $mimeType,
            'storage'        => 'local',
            'storage_label'  => 'Serveur Carré d\'Hôtes',
        ];
    }

    /**
     * Envoie le fichier directement dans Cloudflare R2 via AWS S3 SigV4
     */
    public static function uploadToCloudflareR2(
        string $tmpPath,
        string $category,
        string $filename,
        string $mimeType,
        int $fileSize,
        array $config
    ): array {
        $accountId = $config['account_id'];
        $accessKey = $config['access_key_id'];
        $secretKey = $config['secret_access_key'];

        $bucket = ($category === 'guide') 
            ? ($config['bucket_guides'] ?: 'carredhotes-guides') 
            : ($config['bucket_videos'] ?: 'carredhotes-videos');

        $customDomain = ($category === 'guide')
            ? ($config['domain_guides'] ?: 'https://guides.carredhotes.fr')
            : ($config['domain_videos'] ?: 'https://videos.carredhotes.fr');

        // Préparation du chemin objet (clé R2)
        // Les sous-domaines R2 attendent souvent le fichier sous le slug ou à la racine
        $objectKey = $filename;

        $host = "{$accountId}.r2.cloudflarestorage.com";
        $endpoint = "https://{$host}/{$bucket}/{$objectKey}";

        $body = file_get_contents($tmpPath);
        if ($body === false) {
            throw new RuntimeException("Impossible de lire le fichier temporaire.");
        }
        $payloadHash = hash('sha256', $body);

        $now = time();
        $isoDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        // SigV4 Canonical Headers
        $canonicalHeaders = "content-length:{$fileSize}\n"
                          . "content-type:{$mimeType}\n"
                          . "host:{$host}\n"
                          . "x-amz-content-sha256:{$payloadHash}\n"
                          . "x-amz-date:{$isoDate}\n";

        $signedHeaders = "content-length;content-type;host;x-amz-content-sha256;x-amz-date";

        $canonicalRequest = "PUT\n"
                          . "/{$bucket}/{$objectKey}\n"
                          . "\n"
                          . $canonicalHeaders
                          . "\n"
                          . $signedHeaders . "\n"
                          . $payloadHash;

        $stringToSign = "AWS4-HMAC-SHA256\n"
                      . "{$isoDate}\n"
                      . "{$dateStamp}/auto/s3/aws4_request\n"
                      . hash('sha256', $canonicalRequest);

        // Calcul de la clé de signature dérivée
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$dateStamp}/auto/s3/aws4_request, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $headers = [
            "Host: {$host}",
            "x-amz-date: {$isoDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Content-Type: {$mimeType}",
            "Content-Length: {$fileSize}",
            "Authorization: {$authorization}",
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Erreur réseau cURL lors de l'upload R2 : {$curlError}");
        }

        if ($httpCode !== 200 && $httpCode !== 204) {
            throw new RuntimeException("Cloudflare R2 a renvoyé le statut HTTP {$httpCode}. " . strip_tags((string)$response));
        }

        // Construction de l'URL finale via le sous-domaine personnalisé
        $publicUrl = rtrim($customDomain, '/') . '/' . $objectKey;

        return [
            'ok'             => true,
            'url'            => $publicUrl,
            'relative_url'   => $publicUrl,
            'filename'       => $filename,
            'size'           => $fileSize,
            'size_formatted' => self::formatBytes($fileSize),
            'mime_type'      => $mimeType,
            'storage'        => 'r2',
            'storage_label'  => 'Cloudflare R2',
        ];
    }

    /**
     * Teste la connexion à Cloudflare R2
     */
    public static function testR2Connection(array $config): array
    {
        $accountId = trim((string)($config['account_id'] ?? ''));
        $accessKey = trim((string)($config['access_key_id'] ?? ''));
        $secretKey = trim((string)($config['secret_access_key'] ?? ''));
        $bucket = trim((string)($config['bucket_guides'] ?? ($config['bucket_videos'] ?? '')));

        if (empty($accountId) || empty($accessKey) || empty($secretKey)) {
            return [
                'ok'    => false,
                'error' => 'Identifiants R2 incomplets (ID de compte, Access Key ID et Secret Key sont obligatoires).'
            ];
        }

        if (empty($bucket)) {
            $bucket = 'carredhotes-guides';
        }

        $host = "{$accountId}.r2.cloudflarestorage.com";
        $endpoint = "https://{$host}/{$bucket}?max-keys=1";

        $now = time();
        $isoDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $payloadHash = hash('sha256', '');

        $canonicalHeaders = "host:{$host}\n"
                          . "x-amz-content-sha256:{$payloadHash}\n"
                          . "x-amz-date:{$isoDate}\n";

        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";

        $canonicalRequest = "GET\n"
                          . "/{$bucket}\n"
                          . "max-keys=1\n"
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

        $headers = [
            "Host: {$host}",
            "x-amz-date: {$isoDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authorization}",
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'ok'    => false,
                'error' => "Erreur de connexion cURL : {$curlError}"
            ];
        }

        if ($httpCode === 200) {
            return [
                'ok'      => true,
                'message' => "Connexion Cloudflare R2 réussie ! Le bucket « {$bucket} » est accessible."
            ];
        }

        return [
            'ok'    => false,
            'error' => "Cloudflare R2 a renvoyé l'erreur HTTP {$httpCode}. Vérifiez vos clés et le nom du bucket."
        ];
    }

    /**
     * Lit la configuration R2 stockée en base de données
     */
    public static function getR2Config(): array
    {
        try {
            $db = \Cdh\Portal\Database::getConnection();
            $stmt = $db->query('SELECT setting_key, setting_value FROM admin_settings WHERE setting_key LIKE "r2_%" OR setting_key = "storage_driver"');
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            return [
                'driver'            => $settings['storage_driver'] ?? 'local',
                'enabled'           => ($settings['storage_driver'] ?? 'local') === 'r2',
                'account_id'        => $settings['r2_account_id'] ?? '',
                'access_key_id'     => $settings['r2_access_key_id'] ?? '',
                'secret_access_key' => $settings['r2_secret_access_key'] ?? '',
                'bucket_guides'     => $settings['r2_bucket_guides'] ?? 'carredhotes-guides',
                'domain_guides'     => $settings['r2_domain_guides'] ?? 'https://guides.carredhotes.fr',
                'bucket_videos'     => $settings['r2_bucket_videos'] ?? 'carredhotes-videos',
                'domain_videos'     => $settings['r2_domain_videos'] ?? 'https://videos.carredhotes.fr',
            ];
        } catch (Throwable $e) {
            return [
                'driver'  => 'local',
                'enabled' => false,
            ];
        }
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
