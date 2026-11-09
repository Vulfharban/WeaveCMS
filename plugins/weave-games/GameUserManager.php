<?php
/**
 * ============================================================================
 * Weave Games – GameUserManager
 * ============================================================================
 * Verwaltet Spieler-Accounts, Online-Registrierung/Anmeldung,
 * Authentifizierungs-Tokens für Spiel-Clients (z. B. Fastadun Launcher/Client)
 * sowie Whitelist- und Bann-Status.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class GameUserManager {

    public const ROLE_PLAYER = 'player';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_ADMIN = 'admin';

    public static function getPlayersFilePath(): string {
        return dirname(__DIR__, 2) . '/data/games_players.json';
    }

    public static function getPlayers(): array {
        $file = self::getPlayersFilePath();
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    public static function savePlayers(array $players): bool {
        $file = self::getPlayersFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($players, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    public static function getPlayerById(string $id): ?array {
        $players = self::getPlayers();
        foreach ($players as $p) {
            if (($p['id'] ?? '') === $id) {
                return $p;
            }
        }
        return null;
    }

    public static function getPlayerByEmail(string $email): ?array {
        $email = strtolower(trim($email));
        $players = self::getPlayers();
        foreach ($players as $p) {
            if (strtolower(trim($p['email'] ?? '')) === $email) {
                return $p;
            }
        }
        return null;
    }

    public static function getPlayerByUsername(string $username): ?array {
        $username = strtolower(trim($username));
        $players = self::getPlayers();
        foreach ($players as $p) {
            if (strtolower(trim($p['username'] ?? '')) === $username) {
                return $p;
            }
        }
        return null;
    }

    public static function authenticate(string $userOrEmail, string $password): ?array {
        $userOrEmail = trim($userOrEmail);
        $player = self::getPlayerByUsername($userOrEmail) ?: self::getPlayerByEmail($userOrEmail);
        if (!$player) {
            return null;
        }

        if (!empty($player['is_banned'])) {
            return null;
        }

        if (!password_verify($password, $player['password_hash'] ?? '')) {
            return null;
        }

        // Letztes Login aktualisieren
        $player['last_login'] = date('Y-m-d H:i:s');
        self::updatePlayer($player['id'], ['last_login' => $player['last_login']]);

        return $player;
    }

    public static function createPlayer(array $data, ?string &$error = null): ?array {
        $username = trim($data['username'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $nickname = trim($data['nickname'] ?? $username);

        if (strlen($username) < 3) {
            $error = 'Der Benutzername muss mindestens 3 Zeichen lang sein.';
            return null;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $error = 'Der Benutzername darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.';
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte gib eine gültige E-Mail-Adresse an.';
            return null;
        }

        if (strlen($password) < 6) {
            $error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
            return null;
        }

        if (self::getPlayerByUsername($username)) {
            $error = "Der Benutzername '{$username}' ist bereits vergeben.";
            return null;
        }

        if (self::getPlayerByEmail($email)) {
            $error = "Die E-Mail-Adresse '{$email}' ist bereits registriert.";
            return null;
        }

        $id = 'plyr_' . bin2hex(random_bytes(6));
        $token = 'gtkn_' . bin2hex(random_bytes(24));

        $newPlayer = [
            'id'            => $id,
            'username'      => $username,
            'email'         => $email,
            'nickname'      => !empty($nickname) ? $nickname : $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $data['role'] ?? self::ROLE_PLAYER,
            'auth_token'    => $token,
            'is_banned'     => false,
            'ban_reason'    => '',
            'is_whitelisted'=> !empty($data['is_whitelisted']),
            'avatar'        => $data['avatar'] ?? '',
            'games_played'  => [],
            'created_at'    => date('Y-m-d H:i:s'),
            'last_login'    => date('Y-m-d H:i:s')
        ];

        $players = self::getPlayers();
        $players[] = $newPlayer;
        self::savePlayers($players);

        return $newPlayer;
    }

    public static function updatePlayer(string $id, array $data, ?string &$error = null): bool {
        $players = self::getPlayers();
        $updated = false;

        foreach ($players as &$p) {
            if (($p['id'] ?? '') === $id) {
                if (isset($data['nickname'])) {
                    $p['nickname'] = trim($data['nickname']);
                }
                if (isset($data['email'])) {
                    $newEmail = strtolower(trim($data['email']));
                    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Ungültige E-Mail-Adresse.';
                        return false;
                    }
                    $existing = self::getPlayerByEmail($newEmail);
                    if ($existing && $existing['id'] !== $id) {
                        $error = 'Diese E-Mail-Adresse ist bereits vergeben.';
                        return false;
                    }
                    $p['email'] = $newEmail;
                }
                if (!empty($data['password'])) {
                    if (strlen($data['password']) < 6) {
                        $error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
                        return false;
                    }
                    $p['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if (isset($data['role']) && in_array($data['role'], [self::ROLE_PLAYER, self::ROLE_MODERATOR, self::ROLE_ADMIN], true)) {
                    $p['role'] = $data['role'];
                }
                if (isset($data['is_banned'])) {
                    $p['is_banned'] = (bool)$data['is_banned'];
                    $p['ban_reason'] = trim($data['ban_reason'] ?? '');
                }
                if (isset($data['is_whitelisted'])) {
                    $p['is_whitelisted'] = (bool)$data['is_whitelisted'];
                }
                if (isset($data['auth_token'])) {
                    $p['auth_token'] = trim($data['auth_token']);
                }
                if (isset($data['last_login'])) {
                    $p['last_login'] = $data['last_login'];
                }

                $updated = true;
                break;
            }
        }
        unset($p);

        if ($updated) {
            return self::savePlayers($players);
        }
        return false;
    }

    public static function deletePlayer(string $id): bool {
        $players = self::getPlayers();
        $initial = count($players);
        $players = array_values(array_filter($players, fn($p) => ($p['id'] ?? '') !== $id));
        if (count($players) < $initial) {
            return self::savePlayers($players);
        }
        return false;
    }

    public static function createTokenForPlayer(string $playerId): ?string {
        $token = 'gtkn_' . bin2hex(random_bytes(24));
        if (self::updatePlayer($playerId, ['auth_token' => $token])) {
            return $token;
        }
        return null;
    }

    public static function verifyToken(string $token): ?array {
        $token = trim($token);
        if (empty($token)) {
            return null;
        }
        $players = self::getPlayers();
        foreach ($players as $p) {
            if (($p['auth_token'] ?? '') === $token) {
                if (!empty($p['is_banned'])) {
                    return null;
                }
                return $p;
            }
        }
        return null;
    }

    public static function login(array $player): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['game_player_logged_in'] = true;
        $_SESSION['game_player_id'] = $player['id'];
        $_SESSION['game_player_username'] = $player['username'];
        $_SESSION['game_player_nickname'] = $player['nickname'];
        $_SESSION['game_player_role'] = $player['role'];
        $_SESSION['game_player_token'] = $player['auth_token'];
    }

    public static function logout(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        unset(
            $_SESSION['game_player_logged_in'],
            $_SESSION['game_player_id'],
            $_SESSION['game_player_username'],
            $_SESSION['game_player_nickname'],
            $_SESSION['game_player_role'],
            $_SESSION['game_player_token']
        );
    }

    public static function getCurrentPlayer(): ?array {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // Falls CMS-Administrator eingeloggt ist, erhält dieser automatisch Admin-Gaming-Rechte
        if (class_exists('AuthManager') && AuthManager::isLoggedIn()) {
            return [
                'id'            => 'usr_admin',
                'username'      => $_SESSION['admin_username'] ?? 'admin',
                'email'         => $_SESSION['admin_email'] ?? 'admin@weavecms.local',
                'nickname'      => 'Admin (' . ($_SESSION['admin_username'] ?? 'Administrator') . ')',
                'role'          => self::ROLE_ADMIN,
                'auth_token'    => $_SESSION['csrf_token'] ?? 'admin_session_token',
                'is_banned'     => false,
                'is_cms_admin'  => true
            ];
        }

        $playerId = $_SESSION['game_player_id'] ?? null;
        if ($playerId) {
            return self::getPlayerById($playerId);
        }
        return null;
    }
}

