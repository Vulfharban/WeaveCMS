<?php
/**
 * ============================================================================
 * Weave Games – REST API Endpoint (api/games.php)
 * ============================================================================
 * Zentrale Programmierschnittstelle für:
 * - Spiel-Clients (z. B. Fastadun Voxel Launcher & Client)
 * - Online-Registrierung & Login via REST
 * - Versionsprüfung & Update-Rollout (Check Update, Manifest, Patch-Download)
 * - Live Server-Statusabfragen & Heartbeats
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/plugins/weave-games/GameUserManager.php';
require_once dirname(__DIR__) . '/plugins/weave-games/GameRolloutManager.php';
require_once dirname(__DIR__) . '/plugins/weave-games/GameServerManager.php';
require_once dirname(__DIR__) . '/plugins/weave-games/GameConfigManager.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
$jsonBody = json_decode((string)$rawBody, true) ?: [];

switch ($action) {

    // -------------------------------------------------------------------------
    // 1. UPDATE ROLLOUT: Client-Versionsprüfung
    // -------------------------------------------------------------------------
    case 'check_update':
        $game = trim($_GET['game'] ?? ($jsonBody['game'] ?? 'fastadun'));
        $version = trim($_GET['version'] ?? ($jsonBody['version'] ?? '0.0.0'));
        $platform = trim($_GET['platform'] ?? ($jsonBody['platform'] ?? 'windows_x64'));
        $channel = trim($_GET['channel'] ?? ($jsonBody['channel'] ?? 'stable'));

        $res = GameRolloutManager::checkUpdate($game, $version, $platform, $channel);
        echo json_encode(array_merge(['success' => true], $res), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    // -------------------------------------------------------------------------
    // 2. MANIFEST: Alle Releases eines Spiels abrufen
    // -------------------------------------------------------------------------
    case 'get_manifest':
        $game = trim($_GET['game'] ?? 'fastadun');
        $gameInfo = GameRolloutManager::getGame($game);
        if (!$gameInfo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Spiel '{$game}' nicht gefunden."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $releases = GameRolloutManager::getReleases($game);
        echo json_encode([
            'success'   => true,
            'game'      => $gameInfo,
            'releases'  => $releases,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    // -------------------------------------------------------------------------
    // 3. LOGIN: Spieler-Authentifizierung für Spiel-Clients
    // -------------------------------------------------------------------------
    case 'login':
        $userOrEmail = trim($_POST['username'] ?? ($jsonBody['username'] ?? ''));
        $password = $_POST['password'] ?? ($jsonBody['password'] ?? '');

        if (empty($userOrEmail) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Benutzername und Passwort sind erforderlich.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $player = GameUserManager::authenticate($userOrEmail, $password);
        if (!$player) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Ungültige Anmeldedaten oder Account gesperrt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'    => true,
            'message'    => 'Login erfolgreich',
            'player_id'  => $player['id'],
            'username'   => $player['username'],
            'nickname'   => $player['nickname'],
            'role'       => $player['role'],
            'auth_token' => $player['auth_token']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    // -------------------------------------------------------------------------
    // 4. REGISTRIERUNG: Neuer Spieler-Account via Client
    // -------------------------------------------------------------------------
    case 'register':
        $cfg = GameConfigManager::getConfig();
        if (empty($cfg['allow_self_registration'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Die Online-Registrierung ist derzeit deaktiviert.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $payload = !empty($jsonBody) ? $jsonBody : $_POST;
        $error = null;
        $newPlayer = GameUserManager::createPlayer($payload, $error);

        if (!$newPlayer) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $error ?: 'Registrierung fehlgeschlagen.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'    => true,
            'message'    => 'Account erfolgreich erstellt',
            'player_id'  => $newPlayer['id'],
            'username'   => $newPlayer['username'],
            'nickname'   => $newPlayer['nickname'],
            'auth_token' => $newPlayer['auth_token']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    // -------------------------------------------------------------------------
    // 5. TOKEN-VERIFIKATION: Für Game-Server beim Verbindungsaufbau des Clients
    // -------------------------------------------------------------------------
    case 'verify_token':
        $token = trim($_GET['token'] ?? ($_POST['token'] ?? ($jsonBody['token'] ?? '')));
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($token) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
            $token = $m[1];
        }

        $player = GameUserManager::verifyToken($token);
        if (!$player) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token ungültig oder abgelaufen.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'    => true,
            'valid'      => true,
            'player_id'  => $player['id'],
            'username'   => $player['username'],
            'nickname'   => $player['nickname'],
            'role'       => $player['role']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    // -------------------------------------------------------------------------
    // 6. SERVER STATUS: Live-Status der Game-Server
    // -------------------------------------------------------------------------
    case 'server_status':
        $serverId = trim($_GET['id'] ?? '');
        if (!empty($serverId)) {
            $srv = GameServerManager::getServerById($serverId);
            if (!$srv) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Server nicht gefunden.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $pingRes = GameServerManager::pingServer($srv['host'], (int)$srv['port']);
            $srv['status'] = $pingRes['status'];
            $srv['ping_ms'] = $pingRes['ping_ms'];
            echo json_encode(['success' => true, 'server' => $srv], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        $all = GameServerManager::refreshAllServerStatuses();
        echo json_encode(['success' => true, 'servers' => $all], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    default:
        http_response_code(400);
        echo json_encode([
            'success'   => false,
            'error'     => 'Unbekannte API-Aktion',
            'supported' => ['check_update', 'get_manifest', 'login', 'register', 'verify_token', 'server_status']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
}
