<?php
/**
 * Test-Suite für Weave Games Plugin
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/PluginManager.php';
require_once __DIR__ . '/../plugins/weave-games/GameUserManager.php';
require_once __DIR__ . '/../plugins/weave-games/GameRolloutManager.php';
require_once __DIR__ . '/../plugins/weave-games/GameServerManager.php';
require_once __DIR__ . '/../plugins/weave-games/GameConfigManager.php';

echo "========================================================\n";
echo "WEAVE GAMES TEST-SUITE\n";
echo "========================================================\n\n";

function run_game_test(string $name, bool $cond): void {
    if ($cond) {
        echo "  ✓ [PASS] {$name}\n";
    } else {
        echo "  ❌ [FAIL] {$name}\n";
        exit(1);
    }
}

// 1. Spiele-Katalog & Rollout
echo "1. Rollout & Release Management:\n";
$games = GameRolloutManager::getGames();
run_game_test("Spiele-Katalog nicht leer", count($games) >= 2);

$voxelGame = GameRolloutManager::getGame('voxel-adventure');
run_game_test("Voxel Adventure Game registriert", $voxelGame !== null && $voxelGame['category'] === 'client_server');

// Update-Prüfung
$checkOld = GameRolloutManager::checkUpdate('voxel-adventure', '0.8.0', 'windows_x64');
run_game_test("Client mit v0.8.0 erhält Update-Meldung", $checkOld['update_available'] === true);

$checkCurrent = GameRolloutManager::checkUpdate('voxel-adventure', '0.9.0', 'windows_x64');
run_game_test("Client mit v0.9.0 ist auf dem neuesten Stand", $checkCurrent['update_available'] === false);

// 2. Spieler-Accounts & Registrierung
echo "\n2. Spieler-Accounts & Authentifizierung:\n";
$testUser = 'testplayer_' . bin2hex(random_bytes(2));
$testEmail = $testUser . '@gameportal.local';
$err = null;

$newPlayer = GameUserManager::createPlayer([
    'username' => $testUser,
    'email'    => $testEmail,
    'password' => 'secret123',
    'nickname' => 'Voxel Hero'
], $err);

run_game_test("Neuen Spieler-Account erstellt", $newPlayer !== null && empty($err));
run_game_test("Spieler-Token generiert", !empty($newPlayer['auth_token']));

// Auth prüfen
$authSuccess = GameUserManager::authenticate($testUser, 'secret123');
run_game_test("Login mit korrektem Passwort", $authSuccess !== null);

$authFail = GameUserManager::authenticate($testUser, 'wrongpass');
run_game_test("Login mit falschem Passwort abgewiesen", $authFail === null);

// Token prüfen
$verified = GameUserManager::verifyToken($newPlayer['auth_token']);
run_game_test("Auth-Token für Game-Client verifiziert", $verified !== null && $verified['id'] === $newPlayer['id']);

// Aufräumen
GameUserManager::deletePlayer($newPlayer['id']);
run_game_test("Test-Spieler wieder aufgeräumt", GameUserManager::getPlayerById($newPlayer['id']) === null);

// 3. Server-Management & Parameter
echo "\n3. Server-Verwaltung & Parameter:\n";
$servers = GameServerManager::getServers();
run_game_test("Game-Server registriert", count($servers) >= 2);

$winServer = $servers[0];
run_game_test("Windows Server Parameter vorhanden", !empty($winServer['custom_parameters']));

$ping = GameServerManager::pingServer('127.0.0.1', 80, 0.5);
run_game_test("Socket-Ping-Mechanismus funktioniert", isset($ping['online']));

echo "\n========================================================\n";
echo "ALLE WEAVE GAMES TESTS FEHLERFREI BESTANDEN! ✓\n";
echo "========================================================\n";

