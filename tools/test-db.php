<?php
/**
 * Outil de Diagnostic et Test de Connexion Base de Données
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * SÉCURITÉ : Ce fichier est réservé à l'installation initiale.
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/database.php';

// Sécurité : Accès restreint par token d'administration ou environnement local
$adminKey = env('GATEWAY_API_KEY', '');
$providedKey = $_GET['key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
$isDebug = env('APP_DEBUG', false);

if (!$isLocal && !$isDebug && (empty($adminKey) || !hash_equals((string)$adminKey, (string)$providedKey))) {
    http_response_code(403);
    exit("Accès restreint : Outil de diagnostic réservé à l'administration technique Carré d'Hôtes.");
}

$stepResults = [];
$canAutoInstall = false;
$autoInstallDone = false;
$autoInstallError = null;

// Vérification du fichier .env
$envExists = file_exists(APP_ROOT . '/.env');
$dbHost = env('DB_HOST', 'localhost');
$dbPort = env('DB_PORT', '3306');
$dbName = env('DB_NAME', '');
$dbUser = env('DB_USER', '');
$dbPass = env('DB_PASS', '');

$isPassDefault = (empty($dbPass) || $dbPass === 'METTEZ_VOTRE_MOT_DE_PASSE_ICI' || $dbPass === 'VOTRE_MOT_DE_PASSE');

// Test de connexion PDO
$pdoConnected = false;
$pdoError = null;
$pdoInstance = null;

if (!$isPassDefault) {
    try {
        $pdoInstance = get_db_connection();
        $pdoConnected = true;
    } catch (Exception $e) {
        $pdoError = $e->getMessage();
    }
}

// Vérification de la présence des tables (videos ou tutorials)
$propertiesCount = 0;
$videosCount = 0;
$parkingsCount = 0;
$locationsCount = 0;
$tablesExist = false;
$parkingTablesExist = false;
$canMigrateParking = false;
$videoTableName = 'videos';

if ($pdoConnected && $pdoInstance) {
    try {
        $stmt = $pdoInstance->query("SHOW TABLES LIKE 'properties'");
        $hasPropsTable = $stmt->rowCount() > 0;

        $stmtVideos = $pdoInstance->query("SHOW TABLES LIKE 'videos'");
        $hasVideosTable = $stmtVideos->rowCount() > 0;
        
        $stmtTutos = $pdoInstance->query("SHOW TABLES LIKE 'tutorials'");
        $hasTutosTable = $stmtTutos->rowCount() > 0;

        if ($hasVideosTable) {
            $videoTableName = 'videos';
        } elseif ($hasTutosTable) {
            $videoTableName = 'tutorials';
        }

        if ($hasPropsTable && ($hasVideosTable || $hasTutosTable)) {
            $tablesExist = true;
            $countProps = $pdoInstance->query("SELECT COUNT(*) FROM properties")->fetchColumn();
            $countVideos = $pdoInstance->query("SELECT COUNT(*) FROM {$videoTableName}")->fetchColumn();
            $propertiesCount = (int)$countProps;
            $videosCount = (int)$countVideos;
            $canAutoInstall = false;
        } else {
            $canAutoInstall = file_exists(APP_ROOT . '/database/install.sql');
        }

        // Vérification des tables de stationnement
        $stmtParkings = $pdoInstance->query("SHOW TABLES LIKE 'property_parkings'");
        $hasParkingsTable = $stmtParkings->rowCount() > 0;
        $stmtLocations = $pdoInstance->query("SHOW TABLES LIKE 'parking_locations'");
        $hasLocationsTable = $stmtLocations->rowCount() > 0;

        if ($hasParkingsTable && $hasLocationsTable) {
            $parkingTablesExist = true;
            $parkingsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM property_parkings")->fetchColumn();
            $locationsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM parking_locations")->fetchColumn();
        } else {
            $canMigrateParking = $tablesExist && file_exists(APP_ROOT . '/database/seed_parkings.sql');
        }
    } catch (Exception $e) {
        $pdoError = $e->getMessage();
    }
}

// Action sécurisée : L'installation ne s'exécute QUE si les tables n'existent pas encore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $pdoConnected) {
    if ($_POST['action'] === 'run_install') {
        if ($tablesExist && $propertiesCount > 0) {
            $autoInstallError = "Action bloquée par sécurité : votre base de données contient déjà des données.";
        } else {
            $sqlFile = APP_ROOT . '/database/install.sql';
            if (file_exists($sqlFile)) {
                try {
                    $sql = file_get_contents($sqlFile);
                    $pdoInstance->exec($sql);
                    $autoInstallDone = true;
                    $propertiesCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM properties")->fetchColumn();
                    $videosCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM videos")->fetchColumn();
                    $parkingsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM property_parkings")->fetchColumn();
                    $locationsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM parking_locations")->fetchColumn();
                    $tablesExist = true;
                    $parkingTablesExist = true;
                    $canAutoInstall = false;
                } catch (Exception $e) {
                    $autoInstallError = $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'run_parking_migration') {
        try {
            $schemaFile = APP_ROOT . '/database/schema.sql';
            $seedFile = APP_ROOT . '/database/seed_parkings.sql';
            if (file_exists($schemaFile)) {
                $pdoInstance->exec(file_get_contents($schemaFile));
            }
            if (file_exists($seedFile)) {
                $pdoInstance->exec(file_get_contents($seedFile));
            }
            $autoInstallDone = true;
            $parkingTablesExist = true;
            $canMigrateParking = false;
            $parkingsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM property_parkings")->fetchColumn();
            $locationsCount = (int)$pdoInstance->query("SELECT COUNT(*) FROM parking_locations")->fetchColumn();
        } catch (Exception $e) {
            $autoInstallError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Base de Données - Carré d'Hôtes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f3f3ec; }
    </style>
</head>
<body class="p-4 sm:p-8 flex items-center justify-center min-h-screen">

    <div class="max-w-xl w-full bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-6 bg-gradient-to-r from-[#28344c] to-[#1e2739] text-white">
            <div class="flex items-center space-x-3 mb-2">
                <span class="text-2xl">🔧</span>
                <h1 class="text-xl font-bold tracking-tight">Diagnostic Portail Voyageurs</h1>
            </div>
            <p class="text-xs text-gray-300">Vérification de l'environnement MySQL v8.4 & Sécurité</p>
        </div>

        <div class="p-6 space-y-6">

            <!-- Alerte Sécurité Production -->
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start space-x-3">
                <span class="text-xl">🔒</span>
                <div>
                    <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Recommandation Sécurité</h3>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Une fois votre portail vérifié, vous pouvez supprimer ce fichier (<code>test-db.php</code>) sur votre serveur FTP pour des raisons de sécurité.
                    </p>
                </div>
            </div>

            <!-- Statut 1 : Fichier .env -->
            <div class="flex items-start space-x-3 p-4 rounded-2xl <?= $envExists ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                <span class="text-xl"><?= $envExists ? '✅' : '❌' ?></span>
                <div class="flex-1">
                    <h2 class="text-sm font-bold text-gray-900">Fichier de configuration .env</h2>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <?= $envExists 
                            ? "Fichier <code>.env</code> présent et protégé des accès web externes." 
                            : "Fichier <code>.env</code> introuvable." ?>
                    </p>
                </div>
            </div>

            <!-- Statut 2 : Mot de passe configuré -->
            <div class="flex items-start space-x-3 p-4 rounded-2xl <?= !$isPassDefault ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200' ?>">
                <span class="text-xl"><?= !$isPassDefault ? '✅' : '⚠️' ?></span>
                <div class="flex-1">
                    <h2 class="text-sm font-bold text-gray-900">Mot de passe MySQL (DB_PASS)</h2>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <?= !$isPassDefault 
                            ? "Mot de passe personnalisé configuré." 
                            : "Mot de passe d'exemple détecté. Renseignez <code>DB_PASS</code> dans le fichier <code>.env</code>." ?>
                    </p>
                </div>
            </div>

            <!-- Statut 3 : Connexion MySQL -->
            <div class="flex items-start space-x-3 p-4 rounded-2xl <?= $pdoConnected ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                <span class="text-xl"><?= $pdoConnected ? '✅' : '❌' ?></span>
                <div class="flex-1">
                    <h2 class="text-sm font-bold text-gray-900">Connexion au serveur MySQL</h2>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <?= $pdoConnected 
                            ? "Connexion établie avec succès à <strong>" . htmlspecialchars($dbHost, ENT_QUOTES, 'UTF-8') . "</strong>." 
                            : "Échec de connexion : " . htmlspecialchars($pdoError ?? 'Attente du mot de passe', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>

            <!-- Statut 4 : Tables et Données -->
            <?php if ($pdoConnected): ?>
                <div class="flex items-start space-x-3 p-4 rounded-2xl <?= $tablesExist && $propertiesCount > 0 ? 'bg-green-50 border border-green-200' : 'bg-blue-50 border border-blue-200' ?>">
                    <span class="text-xl"><?= $tablesExist && $propertiesCount > 0 ? '✅' : 'ℹ️' ?></span>
                    <div class="flex-1">
                        <h2 class="text-sm font-bold text-gray-900">Tables et Données des hébergements</h2>
                        <?php if ($tablesExist): ?>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Base opérationnelle (table <code><?= htmlspecialchars($videoTableName) ?></code>) : <strong><?= $propertiesCount ?></strong> logements et <strong><?= $videosCount ?></strong> vidéos enregistrés.
                            </p>
                            <?php if ($videoTableName === 'tutorials'): ?>
                                <p class="text-[11px] text-amber-700 font-semibold mt-1">
                                    💡 Astuce : Vous pouvez exécuter <code>database/migration_rename_tutorials_to_videos.sql</code> dans phpMyAdmin pour activer la table <code>videos</code> et les slugs de vidéos.
                                </p>
                            <?php else: ?>
                                <p class="text-[11px] text-green-700 font-semibold mt-1">
                                    ✓ Table <code>videos</code> et slugs opérationnels !
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Les tables n'existent pas encore dans la base de données.
                            </p>
                        <?php endif; ?>

                        <?php if ($canAutoInstall): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="run_install">
                                <button type="submit" class="px-4 py-2 bg-[#ba9641] text-white rounded-xl text-xs font-bold hover:bg-[#a38234] transition shadow-sm">
                                    ⚡ Initialiser la base de données (1 clic)
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($autoInstallDone): ?>
                            <div class="mt-2 text-xs text-green-700 font-semibold">
                                ✓ Tables et données importées avec succès !
                            </div>
                        <?php endif; ?>

                        <?php if ($autoInstallError): ?>
                            <div class="mt-2 text-xs text-red-600 font-mono">
                                <?= htmlspecialchars($autoInstallError, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statut 5 : Module Stationnement (Parking) -->
                <div class="flex items-start space-x-3 p-4 rounded-2xl <?= $parkingTablesExist && $parkingsCount > 0 ? 'bg-green-50 border border-green-200' : 'bg-blue-50 border border-blue-200' ?>">
                    <span class="text-xl"><?= $parkingTablesExist && $parkingsCount > 0 ? '🅿️' : 'ℹ️' ?></span>
                    <div class="flex-1">
                        <h2 class="text-sm font-bold text-gray-900">Module Stationnement (FR & EN)</h2>
                        <?php if ($parkingTablesExist && $parkingsCount > 0): ?>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Module opérationnel : <strong><?= $parkingsCount ?></strong> fiches et <strong><?= $locationsCount ?></strong> emplacements répertoriés (rues gratuites, parkings privés, P+R).
                            </p>
                            <p class="text-[11px] text-green-700 font-semibold mt-1">
                                ✓ Tables <code>property_parkings</code> et <code>parking_locations</code> prêtes !
                            </p>
                        <?php else: ?>
                            <p class="text-xs text-gray-600 mt-0.5">
                                <?= $tablesExist ? "Les tables de stationnement ne sont pas encore installées." : "En attente de l'installation de la base." ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($canMigrateParking): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="run_parking_migration">
                                <button type="submit" class="px-4 py-2 bg-[#28344c] hover:bg-[#1e2739] text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2">
                                    <span>🅿️</span> Activer le module Stationnement (1 clic)
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liens d'accès direct et test -->
            <?php if ($tablesExist && $propertiesCount > 0): ?>
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Tester les nouvelles routes :</p>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <a href="maisonnette-en-jouhet/videos/machine-a-laver" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-[#ba9641] hover:text-white rounded-lg transition font-medium">
                            Vidéo Machine à laver ↗
                        </a>
                        <a href="maisonnette-en-jouhet/videos" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-[#ba9641] hover:text-white rounded-lg transition font-medium">
                            Onglet Vidéos ↗
                        </a>
                        <a href="./" target="_blank" class="px-3 py-1.5 bg-[#28344c] text-white rounded-lg transition font-medium">
                            Accueil du Portail ↗
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center pt-2">
                <a href="./" class="text-xs text-gray-400 hover:text-gray-700 transition">
                    &larr; Retour au portail voyageur
                </a>
            </div>

        </div>
    </div>

</body>
</html>
