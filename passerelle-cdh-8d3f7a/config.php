<?php
/*
 * Configuration de la passerelle — Carré d'Hôtes
 *
 * Charge automatiquement les identifiants depuis le fichier .env du portail
 * avec secours (fallback) sur les valeurs configurées ci-dessous.
 */

// Recherche et chargement automatique du fichier .env
$envCandidates = [
    dirname(__DIR__) . '/.env',
    dirname(dirname(__DIR__)) . '/.env',
    dirname(__DIR__) . '/portail/.env',
    __DIR__ . '/.env'
];

$envVars = [];
foreach ($envCandidates as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if ((substr($val, 0, 1) === '"' && substr($val, -1) === '"') ||
                    (substr($val, 0, 1) === "'" && substr($val, -1) === "'")) {
                    $val = substr($val, 1, -1);
                }
                $envVars[$key] = $val;
            }
        }
        break;
    }
}

return [
    // Clé d'accès à la passerelle (chargée depuis le .env via GATEWAY_API_KEY).
    'api_key' => $envVars['GATEWAY_API_KEY'] ?? '',

    // Écritures autorisées (INSERT/UPDATE) sur les tables listées par base.
    'writes_enabled' => true,

    // Autoriser la suppression DELETE (sécurisée par clause WHERE obligatoire).
    'allow_delete' => true,

    // Journal des appels (protégé par le .htaccess).
    'log_file' => __DIR__ . '/gateway-log.jsonl',

    'databases' => [
        'portail' => [
            'host'         => $envVars['DB_HOST'] ?? 'localhost',
            'name'         => $envVars['DB_NAME'] ?? '',
            'user'         => $envVars['DB_USER'] ?? '',
            'pass'         => $envVars['DB_PASS'] ?? '',
            'write_tables' => ['properties', 'videos', 'property_parkings', 'parking_free_spots', 'parking_private_lots', 'parking_tcl_prs', 'admin_users', 'admin_sessions', 'admin_activity_logs', 'admin_settings'],
        ],
    ],
];
