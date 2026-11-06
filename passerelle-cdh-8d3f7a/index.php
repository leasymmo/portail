<?php
declare(strict_types=1);

/*
 * Passerelle base de données — Carré d'Hôtes (v1)
 * Point d'accès HTTPS unique, protégé par clé API, entre l'agent et les
 * bases MySQL mutualisées OVH (injoignables depuis l'extérieur).
 *
 * Actions (GET ou POST, réponse JSON) :
 *   ping                            -> état + version MySQL
 *   tables                          -> liste des tables de la base
 *   schema&table=xxx                -> colonnes, index, CREATE TABLE
 *   query   {sql, params[], limit}  -> SELECT/SHOW/DESCRIBE/EXPLAIN uniquement
 *   insert  {table, rows[]}         -> insertions préparées (tables autorisées)
 *   update  {table, set{}, where{}} -> mise à jour préparée (where obligatoire)
 *   delete  {table, where{}}        -> seulement si allow_delete = true en config
 *
 * Le paramètre db (défaut : "portail") choisit la base déclarée en config.
 */

$config = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $msg, int $code = 400): void
{
    respond(['ok' => false, 'error' => $msg], $code);
}

function logline(array $config, string $db, string $action, string $detail = ''): void
{
    $file = $config['log_file'] ?? (__DIR__ . '/gateway-log.jsonl');
    $line = json_encode([
        't'      => date('c'),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
        'db'     => $db,
        'action' => $action,
        'detail' => $detail,
    ]);
    @file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);
}

/* ---------- Authentification par clé API ---------- */
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey === '' && isset($_SERVER['HTTP_AUTHORIZATION'])
    && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
    $apiKey = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
}
if (empty($config['api_key']) || !is_string($apiKey) || !hash_equals((string) $config['api_key'], $apiKey)) {
    fail('Non autorisé', 401);
}

/* ---------- Entrées ---------- */
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw !== false && strlen($raw) > 1000000) {
        fail('Requête trop volumineuse', 413);
    }
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }
}
$dbId = isset($body['db']) ? (string) $body['db'] : (isset($_GET['db']) ? (string) $_GET['db'] : 'portail');
if (!isset($config['databases'][$dbId]) || !is_array($config['databases'][$dbId])) {
    fail('Base inconnue');
}
$dbc = $config['databases'][$dbId];

/* ---------- Connexion PDO ---------- */
try {
    $pdo = new PDO(
        'mysql:host=' . $dbc['host'] . ';dbname=' . $dbc['name'] . ';charset=utf8mb4',
        $dbc['user'],
        $dbc['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    logline($config, $dbId, $action, 'connexion base impossible');
    fail('Connexion à la base impossible', 502);
}

/* ---------- Helpers de validation ---------- */
function listTables(PDO $pdo): array
{
    return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
}

function requireTable(PDO $pdo, string $table): void
{
    if (!in_array($table, listTables($pdo), true)) {
        fail('Table inconnue : ' . $table);
    }
}

function listColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
    return array_column($stmt->fetchAll(), 'Field');
}

function requireColumns(array $allowed, array $cols): void
{
    foreach ($cols as $c) {
        if (!in_array($c, $allowed, true)) {
            fail('Colonne inconnue : ' . $c);
        }
    }
}

function requireWritable(array $config, array $dbc, string $table): void
{
    if (empty($config['writes_enabled'])) {
        fail('Écritures désactivées dans la configuration', 403);
    }
    $allowed = $dbc['write_tables'] ?? [];
    if (!in_array($table, $allowed, true)) {
        fail('Écriture non autorisée sur la table : ' . $table, 403);
    }
}

/* ---------- Actions ---------- */
try {
    switch ($action) {

        case 'ping':
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            logline($config, $dbId, 'ping');
            respond(['ok' => true, 'db' => $dbId, 'mysql_version' => $version, 'time' => date('c')]);

        case 'tables':
            respond(['ok' => true, 'db' => $dbId, 'tables' => listTables($pdo)]);

        case 'schema':
            $table = isset($_GET['table']) ? (string) $_GET['table'] : (string) ($body['table'] ?? '');
            requireTable($pdo, $table);
            $cols   = $pdo->query('SHOW FULL COLUMNS FROM `' . $table . '`')->fetchAll();
            $index  = $pdo->query('SHOW INDEX FROM `' . $table . '`')->fetchAll();
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
            respond([
                'ok'           => true,
                'table'        => $table,
                'columns'      => $cols,
                'indexes'      => $index,
                'create_table' => $create['Create Table'] ?? null,
            ]);

        case 'query':
            $sql = trim((string) ($body['sql'] ?? ''));
            $params = $body['params'] ?? [];
            if (!is_array($params)) {
                fail('params doit être un tableau');
            }
            if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $sql)) {
                fail('Lecture seule : SELECT, SHOW, DESCRIBE ou EXPLAIN uniquement');
            }
            $stripped = rtrim($sql, "; \t\n\r");
            if (strpos($stripped, ';') !== false) {
                fail('Une seule requête à la fois');
            }
            $limit = isset($body['limit']) ? (int) $body['limit'] : 500;
            if ($limit < 1) { $limit = 1; }
            if ($limit > 1000) { $limit = 1000; }
            $stmt = $pdo->prepare($stripped);
            $stmt->execute(array_values($params));
            $rows = [];
            $truncated = false;
            while ($row = $stmt->fetch()) {
                if (count($rows) >= $limit) { $truncated = true; break; }
                $rows[] = $row;
            }
            logline($config, $dbId, 'query', count($rows) . ' lignes');
            respond(['ok' => true, 'rows' => $rows, 'count' => count($rows), 'truncated' => $truncated]);

        case 'insert':
            $table = (string) ($body['table'] ?? '');
            requireTable($pdo, $table);
            requireWritable($config, $dbc, $table);
            $rowsIn = $body['rows'] ?? [];
            if (!is_array($rowsIn) || $rowsIn === []) {
                fail('rows doit être un tableau non vide');
            }
            $allowedCols = listColumns($pdo, $table);
            $ids = [];
            $pdo->beginTransaction();
            foreach ($rowsIn as $row) {
                if (!is_array($row) || $row === []) {
                    $pdo->rollBack();
                    fail('Chaque ligne doit être un objet non vide');
                }
                requireColumns($allowedCols, array_keys($row));
                $cols = array_keys($row);
                $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $cols) . '`) VALUES ('
                     . implode(', ', array_fill(0, count($cols), '?')) . ')';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($row));
                $ids[] = (int) $pdo->lastInsertId();
            }
            $pdo->commit();
            logline($config, $dbId, 'insert', $table . ' x' . count($ids));
            respond(['ok' => true, 'inserted' => count($ids), 'ids' => $ids]);

        case 'update':
            $table = (string) ($body['table'] ?? '');
            requireTable($pdo, $table);
            requireWritable($config, $dbc, $table);
            $set   = $body['set']   ?? [];
            $where = $body['where'] ?? [];
            if (!is_array($set) || $set === []) {
                fail('set doit être un objet non vide');
            }
            if (!is_array($where) || $where === []) {
                fail('where est obligatoire et non vide (sécurité)');
            }
            $allowedCols = listColumns($pdo, $table);
            requireColumns($allowedCols, array_keys($set));
            requireColumns($allowedCols, array_keys($where));
            $setSql = implode(', ', array_map(function ($c) { return '`' . $c . '` = ?'; }, array_keys($set)));
            $whereSql = implode(' AND ', array_map(function ($c) { return '`' . $c . '` = ?'; }, array_keys($where)));
            $stmt = $pdo->prepare('UPDATE `' . $table . '` SET ' . $setSql . ' WHERE ' . $whereSql);
            $stmt->execute(array_merge(array_values($set), array_values($where)));
            $n = $stmt->rowCount();
            logline($config, $dbId, 'update', $table . ' x' . $n);
            respond(['ok' => true, 'updated' => $n]);

        case 'delete':
            if (empty($config['allow_delete'])) {
                fail('Suppression désactivée dans la configuration', 403);
            }
            $table = (string) ($body['table'] ?? '');
            requireTable($pdo, $table);
            requireWritable($config, $dbc, $table);
            $where = $body['where'] ?? [];
            if (!is_array($where) || $where === []) {
                fail('where est obligatoire et non vide (sécurité)');
            }
            $allowedCols = listColumns($pdo, $table);
            requireColumns($allowedCols, array_keys($where));
            $whereSql = implode(' AND ', array_map(function ($c) { return '`' . $c . '` = ?'; }, array_keys($where)));
            $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE ' . $whereSql);
            $stmt->execute(array_values($where));
            $n = $stmt->rowCount();
            logline($config, $dbId, 'delete', $table . ' x' . $n);
            respond(['ok' => true, 'deleted' => $n]);

        case 'migrate':
            if (empty($config['writes_enabled'])) {
                fail('Écritures désactivées dans la configuration', 403);
            }
            $sql = trim((string) ($body['sql'] ?? ''));
            if ($sql === '') {
                fail('sql est obligatoire');
            }
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                logline($config, $dbId, 'migrate_error', $e->getMessage());
                fail('Erreur migration: ' . $e->getMessage(), 500);
            }
            logline($config, $dbId, 'migrate', 'migration executee avec succes');
            respond(['ok' => true, 'message' => 'Migration exécutée avec succès']);

        default:
            fail('Action inconnue : ' . $action);
    }
} catch (PDOException $e) {
    logline($config, $dbId, $action, 'erreur SQL');
    fail('Erreur SQL (détail journalisé côté serveur)', 500);
} catch (Throwable $e) {
    logline($config, $dbId, $action, 'erreur interne');
    fail('Erreur interne (détail journalisé côté serveur)', 500);
}
