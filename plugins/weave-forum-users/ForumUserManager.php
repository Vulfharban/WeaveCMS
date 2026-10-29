<?php
/**
 * ============================================================================
 * WeaveCMS Forum-Benutzerverwaltung – ForumUserManager
 * ============================================================================
 * Verwaltet Foren-Benutzer, Authentifizierung, Rollen, Berechtigungen, Alias-Namen,
 * Privatsphäre-Einstellungen und DSGVO.
 * Unterstützt wahlweise Flat-File JSON (data/forum_users.json), SQLite (data/forum.sqlite)
 * oder MySQL/MariaDB mit 1-Klick-Migration in beide Richtungen.
 * ============================================================================
 */

class ForumUserManager {

    const ROLE_ADMIN     = 'forum_admin';
    const ROLE_MODERATOR = 'forum_moderator';
    const ROLE_USER      = 'forum_user';

    const STATUS_ACTIVE  = 'active';
    const STATUS_PENDING = 'pending_approval';
    const STATUS_BANNED  = 'banned';

    protected static ?PDO $sqlitePdo = null;
    protected static ?PDO $dbPdo = null;

    /**
     * Ermittelt den Speicherpfad zur JSON-Datei.
     */
    public static function getJsonFilePath(): string {
        return dirname(__DIR__, 2) . '/data/forum_users.json';
    }

    /**
     * Ermittelt den Speicherpfad zur SQLite-Datei.
     */
    public static function getSqliteFilePath(): string {
        return dirname(__DIR__, 2) . '/data/forum.sqlite';
    }

    /**
     * Ermittelt den konfigurierten Speichertreiber ('mysql', 'sqlite' oder 'json').
     */
    public static function getStorageDriver(): string {
        if (class_exists('PluginManager')) {
            $data = $GLOBALS['data'] ?? [];
            if (!empty($data)) {
                $cfg = PluginManager::getPluginConfig('weave-forum-users', $data);
                $driver = $cfg['storage_driver'] ?? '';
                if (empty($driver)) {
                    $forumCfg = PluginManager::getPluginConfig('weave-forum', $data);
                    $driver = $forumCfg['storage_driver'] ?? 'json';
                }
                if ($driver === 'mysql' && self::isMysqlSupported()) {
                    return 'mysql';
                }
                if ($driver === 'sqlite' && self::isSqliteSupported()) {
                    return 'sqlite';
                }
            }
        }
        return 'json';
    }

    /**
     * Prüft, ob ein relationaler Datenbanktreiber aktiv ist.
     */
    public static function isDatabase(): bool {
        return in_array(self::getStorageDriver(), ['mysql', 'sqlite'], true);
    }

    /**
     * Prüft, ob die MySQL-Erweiterung in PHP geladen ist.
     */
    public static function isMysqlSupported(): bool {
        return extension_loaded('pdo_mysql');
    }

    /**
     * Prüft, ob die SQLite-Erweiterung in PHP geladen ist.
     */
    public static function isSqliteSupported(): bool {
        return extension_loaded('pdo_sqlite');
    }

    /**
     * Liefert die aktive PDO-Verbindung.
     */
    public static function getPdo(): PDO {
        if (self::$dbPdo !== null) {
            return self::$dbPdo;
        }

        $driver = self::getStorageDriver();
        if ($driver === 'mysql') {
            self::$dbPdo = self::getMysqlPdo();
        } else {
            self::$dbPdo = self::getSqlitePdo();
        }
        return self::$dbPdo;
    }

    /**
     * Stellt sicher, dass neue Spalten (alias, use_alias_only, privacy_settings) in bestehenden DB-Tabellen existieren.
     */
    protected static function ensureSchemaColumns(PDO $pdo, string $driver): void {
        try {
            if ($driver === 'mysql') {
                $cols = [];
                $stmt = $pdo->query("SHOW COLUMNS FROM weave_forum_users");
                foreach ($stmt->fetchAll() as $row) {
                    $cols[$row['Field']] = true;
                }
                if (!isset($cols['alias'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN alias VARCHAR(64) DEFAULT ''");
                }
                if (!isset($cols['use_alias_only'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN use_alias_only TINYINT(1) DEFAULT 0");
                }
                if (!isset($cols['privacy_settings'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN privacy_settings TEXT");
                }
            } else {
                $cols = [];
                $stmt = $pdo->query("PRAGMA table_info(weave_forum_users)");
                foreach ($stmt->fetchAll() as $row) {
                    $cols[$row['name']] = true;
                }
                if (!isset($cols['alias'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN alias TEXT DEFAULT ''");
                }
                if (!isset($cols['use_alias_only'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN use_alias_only INTEGER DEFAULT 0");
                }
                if (!isset($cols['privacy_settings'])) {
                    $pdo->exec("ALTER TABLE weave_forum_users ADD COLUMN privacy_settings TEXT DEFAULT ''");
                }
            }
        } catch (Exception $e) {
            // Ignorieren falls Spalte bereits existiert
        }
    }

    /**
     * Stellt eine MySQL-Verbindung her und initialisiert die Benutzertabelle.
     */
    public static function getMysqlPdo(): PDO {
        if (!self::isMysqlSupported()) {
            throw new RuntimeException('Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht aktiviert.');
        }

        if (class_exists('ForumManager')) {
            $c = ForumManager::getMysqlConfig();
        } else {
            $localConfig = dirname(__DIR__, 2) . '/config.local.php';
            if (file_exists($localConfig)) {
                require_once $localConfig;
            }
            $cfg = [];
            if (class_exists('PluginManager') && !empty($GLOBALS['data'])) {
                $cfg = PluginManager::getPluginConfig('weave-forum', $GLOBALS['data']);
            }
            $c = [
                'host' => !empty($cfg['mysql_host']) ? $cfg['mysql_host'] : (defined('WEAVE_DB_HOST') ? WEAVE_DB_HOST : '127.0.0.1'),
                'port' => !empty($cfg['mysql_port']) ? (int)$cfg['mysql_port'] : (defined('WEAVE_DB_PORT') ? (int)WEAVE_DB_PORT : 3306),
                'database' => !empty($cfg['mysql_database']) ? $cfg['mysql_database'] : (defined('WEAVE_DB_NAME') ? WEAVE_DB_NAME : 'WeaveCMS'),
                'user' => !empty($cfg['mysql_user']) ? $cfg['mysql_user'] : (defined('WEAVE_DB_USER') ? WEAVE_DB_USER : 'root'),
                'password' => isset($cfg['mysql_password']) ? (string)$cfg['mysql_password'] : (defined('WEAVE_DB_PASS') ? (string)WEAVE_DB_PASS : ''),
                'charset' => !empty($cfg['mysql_charset']) ? $cfg['mysql_charset'] : 'utf8mb4'
            ];
        }

        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS weave_forum_users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(64) UNIQUE NOT NULL,
                email VARCHAR(191) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL DEFAULT 'forum_user',
                status VARCHAR(32) NOT NULL DEFAULT 'active',
                avatar VARCHAR(255) DEFAULT '',
                signature TEXT,
                bio TEXT,
                created_at VARCHAR(32) NOT NULL,
                last_login VARCHAR(32) DEFAULT '',
                post_count INT DEFAULT 0,
                topic_count INT DEFAULT 0,
                banned_until VARCHAR(32) DEFAULT NULL,
                ban_reason TEXT,
                notification_email TINYINT(1) DEFAULT 1,
                alias VARCHAR(64) DEFAULT '',
                use_alias_only TINYINT(1) DEFAULT 0,
                privacy_settings TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        self::ensureSchemaColumns($pdo, 'mysql');

        return $pdo;
    }

    /**
     * Stellt eine SQLite-Verbindung her und initialisiert die Tabelle.
     */
    public static function getSqlitePdo(): PDO {
        if (self::getStorageDriver() === 'mysql') {
            return self::getPdo();
        }
        if (self::$sqlitePdo !== null) {
            return self::$sqlitePdo;
        }

        if (!self::isSqliteSupported()) {
            throw new RuntimeException('Die PHP-Erweiterung pdo_sqlite ist auf diesem Server nicht aktiviert. Bitte in php.ini aktivieren (extension=pdo_sqlite).');
        }

        $dbFile = self::getSqliteFilePath();
        $dir = dirname($dbFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS weave_forum_users (
                id TEXT PRIMARY KEY,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'forum_user',
                status TEXT NOT NULL DEFAULT 'active',
                avatar TEXT DEFAULT '',
                signature TEXT DEFAULT '',
                bio TEXT DEFAULT '',
                created_at TEXT NOT NULL,
                last_login TEXT DEFAULT '',
                post_count INTEGER DEFAULT 0,
                topic_count INTEGER DEFAULT 0,
                banned_until TEXT DEFAULT NULL,
                ban_reason TEXT DEFAULT '',
                notification_email INTEGER DEFAULT 1,
                alias TEXT DEFAULT '',
                use_alias_only INTEGER DEFAULT 0,
                privacy_settings TEXT DEFAULT ''
            );
            CREATE INDEX IF NOT EXISTS idx_fusers_username ON weave_forum_users(username);
            CREATE INDEX IF NOT EXISTS idx_fusers_email ON weave_forum_users(email);
        ");

        self::ensureSchemaColumns($pdo, 'sqlite');

        // Wenn ForumManager existiert, auch die Foren-Inhaltstabellen sicherstellen
        if (class_exists('ForumManager')) {
            ForumManager::initSqliteTables($pdo);
        }

        self::$sqlitePdo = $pdo;
        return $pdo;
    }

    /**
     * Normalisiert ein Benutzer-Array (Typen, Alias, Privacy-Settings).
     */
    public static function normalizeUser(array $u): array {
        $u['post_count'] = (int)($u['post_count'] ?? 0);
        $u['topic_count'] = (int)($u['topic_count'] ?? 0);
        $u['notification_email'] = !empty($u['notification_email']) ? 1 : 0;
        $u['alias'] = trim((string)($u['alias'] ?? ''));
        $u['use_alias_only'] = !empty($u['use_alias_only']) ? 1 : 0;

        $defaultPrivacy = self::getDefaultPrivacySettings();
        $rawPriv = $u['privacy_settings'] ?? [];
        if (is_string($rawPriv) && $rawPriv !== '') {
            $decoded = json_decode($rawPriv, true);
            $rawPriv = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($rawPriv)) {
            $rawPriv = [];
        }
        $mergedPriv = $defaultPrivacy;
        foreach ($defaultPrivacy as $k => $defVal) {
            if (array_key_exists($k, $rawPriv)) {
                $mergedPriv[$k] = !empty($rawPriv[$k]);
            }
        }
        $u['privacy_settings'] = $mergedPriv;
        return $u;
    }

    /**
     * Standard-Privatsphäre-Einstellungen für andere angemeldete Benutzer.
     */
    public static function getDefaultPrivacySettings(): array {
        return [
            'show_role'          => true,
            'show_post_count'    => true,
            'show_joined_date'   => true,
            'show_bio'           => true,
            'show_signature'     => true,
            'show_online_status' => true,
        ];
    }

    /**
     * Liefert die aktiven Privatsphäre-Einstellungen eines Benutzers.
     */
    public static function getPrivacySettings(?array $user): array {
        $defaults = self::getDefaultPrivacySettings();
        if (!$user) {
            return $defaults;
        }
        $raw = $user['privacy_settings'] ?? [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return $defaults;
        }
        $res = $defaults;
        foreach ($defaults as $k => $v) {
            if (array_key_exists($k, $raw)) {
                $res[$k] = !empty($raw[$k]);
            }
        }
        return $res;
    }

    /**
     * Prüft, ob ein Benutzer berechtigt ist, ein Alias zu führen (Admin & Moderator).
     */
    public static function canUseAlias(?array $user): bool {
        if (!$user) return false;
        return in_array($user['role'] ?? '', [self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    /**
     * Ermittelt den im Forum anzuzeigenden Namen eines Benutzers.
     * Wenn Admin/Moderator einen Alias vergeben hat und "use_alias_only" aktiv ist (oder Alias gesetzt),
     * wird ausschließlich der Alias zurückgegeben.
     */
    public static function getDisplayName(?array $user, string $fallback = 'Mitglied'): string {
        if (!$user) {
            return $fallback;
        }
        if (self::canUseAlias($user) && !empty(trim((string)($user['alias'] ?? ''))) && !empty($user['use_alias_only'])) {
            return trim((string)$user['alias']);
        }
        return !empty($user['username']) ? (string)$user['username'] : $fallback;
    }

    // =========================================================================
    // CRUD & SPEICHER-METHODEN
    // =========================================================================

    /**
     * Liefert alle Forum-Benutzer als assoziatives Array.
     */
    public static function getAllUsers(): array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->query("SELECT * FROM weave_forum_users ORDER BY created_at DESC");
                $users = $stmt->fetchAll();
                foreach ($users as &$u) {
                    $u = self::normalizeUser($u);
                }
                return $users;
            } catch (Exception $e) {
                error_log('ForumUserManager DB getAllUsers error: ' . $e->getMessage());
                return [];
            }
        }

        $file = self::getJsonFilePath();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return [];
        }
        foreach ($data as &$u) {
            $u = self::normalizeUser($u);
        }
        return $data;
    }

    /**
     * Speichert die Benutzerliste im JSON-Speicher atomar.
     */
    public static function saveAllUsersJson(array $users): bool {
        $file = self::getJsonFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $normalized = [];
        foreach ($users as $u) {
            $normalized[] = self::normalizeUser($u);
        }

        $json = json_encode(array_values($normalized), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    /**
     * Findet einen Benutzer anhand seiner ID.
     */
    public static function getUserById(string $id): ?array {
        if (empty($id)) return null;

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_users WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $u = $stmt->fetch();
                if ($u) {
                    return self::normalizeUser($u);
                }
                return null;
            } catch (Exception $e) {
                return null;
            }
        }

        $users = self::getAllUsers();
        foreach ($users as $u) {
            if (($u['id'] ?? '') === $id) {
                return self::normalizeUser($u);
            }
        }
        return null;
    }

    /**
     * Findet einen Benutzer anhand von Benutzername, Alias oder E-Mail.
     */
    public static function findUser(string $login): ?array {
        $loginLower = mb_strtolower(trim($login));
        if (empty($loginLower)) return null;

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    SELECT * FROM weave_forum_users 
                    WHERE LOWER(username) = ? OR LOWER(email) = ? OR (alias != '' AND LOWER(alias) = ?)
                    LIMIT 1
                ");
                $stmt->execute([$loginLower, $loginLower, $loginLower]);
                $u = $stmt->fetch();
                if ($u) {
                    return self::normalizeUser($u);
                }
                return null;
            } catch (Exception $e) {
                return null;
            }
        }

        $users = self::getAllUsers();
        foreach ($users as $u) {
            $uName = mb_strtolower($u['username'] ?? '');
            $uMail = mb_strtolower($u['email'] ?? '');
            $uAlias = mb_strtolower($u['alias'] ?? '');
            if ($uName === $loginLower || $uMail === $loginLower || ($uAlias !== '' && $uAlias === $loginLower)) {
                return self::normalizeUser($u);
            }
        }
        return null;
    }

    /**
     * Speichert oder aktualisiert einen Benutzer.
     */
    public static function saveUser(array $user): bool {
        if (empty($user['id'])) return false;
        $user = self::normalizeUser($user);

        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $stmt = $pdo->prepare("
                    REPLACE INTO weave_forum_users (
                        id, username, email, password_hash, role, status, avatar, signature,
                        bio, created_at, last_login, post_count, topic_count, banned_until, ban_reason, notification_email,
                        alias, use_alias_only, privacy_settings
                    ) VALUES (
                        :id, :username, :email, :password_hash, :role, :status, :avatar, :signature,
                        :bio, :created_at, :last_login, :post_count, :topic_count, :banned_until, :ban_reason, :notification_email,
                        :alias, :use_alias_only, :privacy_settings
                    )
                ");
                return $stmt->execute([
                    ':id' => $user['id'],
                    ':username' => $user['username'],
                    ':email' => mb_strtolower($user['email']),
                    ':password_hash' => $user['password_hash'],
                    ':role' => $user['role'] ?? self::ROLE_USER,
                    ':status' => $user['status'] ?? self::STATUS_ACTIVE,
                    ':avatar' => $user['avatar'] ?? '',
                    ':signature' => $user['signature'] ?? '',
                    ':bio' => $user['bio'] ?? '',
                    ':created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                    ':last_login' => $user['last_login'] ?? '',
                    ':post_count' => (int)($user['post_count'] ?? 0),
                    ':topic_count' => (int)($user['topic_count'] ?? 0),
                    ':banned_until' => $user['banned_until'] ?? null,
                    ':ban_reason' => $user['ban_reason'] ?? '',
                    ':notification_email' => !empty($user['notification_email']) ? 1 : 0,
                    ':alias' => $user['alias'] ?? '',
                    ':use_alias_only' => !empty($user['use_alias_only']) ? 1 : 0,
                    ':privacy_settings' => json_encode($user['privacy_settings'] ?? self::getDefaultPrivacySettings(), JSON_UNESCAPED_UNICODE)
                ]);
            } catch (Exception $e) {
                error_log('ForumUserManager DB saveUser error: ' . $e->getMessage());
                return false;
            }
        }

        $users = self::getAllUsers();
        $found = false;
        foreach ($users as &$u) {
            if ($u['id'] === $user['id']) {
                $u = array_merge($u, $user);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $users[] = $user;
        }
        return self::saveAllUsersJson($users);
    }

    /**
     * Löscht einen Benutzer.
     */
    public static function deleteUser(string $id): bool {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("DELETE FROM weave_forum_users WHERE id = ?");
                return $stmt->execute([$id]);
            } catch (Exception $e) {
                return false;
            }
        }

        $users = self::getAllUsers();
        $filtered = array_filter($users, fn($u) => ($u['id'] ?? '') !== $id);
        return self::saveAllUsersJson($filtered);
    }

    // =========================================================================
    // AUTHENTIFIZIERUNG & SESSION
    // =========================================================================

    /**
     * Registriert einen neuen Forum-Benutzer.
     */
    public static function register(array $data, string $registrationMode = 'open'): array {
        $username = trim($data['username'] ?? '');
        $email = trim(mb_strtolower($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $passwordConfirm = (string)($data['password_confirm'] ?? '');

        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'message' => 'Der Benutzername muss zwischen 3 und 30 Zeichen lang sein.'];
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/u', $username)) {
            return ['success' => false, 'message' => 'Der Benutzername darf nur Buchstaben, Zahlen, Punkte, Binde- und Unterstriche enthalten.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Bitte gib eine gültige E-Mail-Adresse ein.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Das Passwort muss mindestens 8 Zeichen lang sein.'];
        }
        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Die beiden Passwörter stimmen nicht überein.'];
        }

        // Prüfung auf Duplikate (Username, Alias oder E-Mail)
        if (self::findUser($username)) {
            return ['success' => false, 'message' => 'Dieser Benutzername ist bereits vergeben.'];
        }
        if (self::findUser($email)) {
            return ['success' => false, 'message' => 'Diese E-Mail-Adresse ist bereits registriert.'];
        }

        if ($registrationMode === 'closed') {
            return ['success' => false, 'message' => 'Die Registrierung für neue Benutzer ist derzeit deaktiviert.'];
        }

        $status = ($registrationMode === 'approval') ? self::STATUS_PENDING : self::STATUS_ACTIVE;

        // Wenn noch überhaupt kein Forum-User existiert, erhält der Erste die Admin-Rolle
        $existing = self::getAllUsers();
        $role = empty($existing) ? self::ROLE_ADMIN : self::ROLE_USER;

        $newUser = [
            'id' => 'fusr_' . bin2hex(random_bytes(6)),
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => $status,
            'avatar' => '',
            'signature' => '',
            'bio' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => '',
            'post_count' => 0,
            'topic_count' => 0,
            'banned_until' => null,
            'ban_reason' => '',
            'notification_email' => 1,
            'alias' => '',
            'use_alias_only' => 0,
            'privacy_settings' => self::getDefaultPrivacySettings()
        ];

        if (!self::saveUser($newUser)) {
            return ['success' => false, 'message' => 'Fehler beim Speichern des Benutzerkontos.'];
        }

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('FORUM_USER_REGISTERED', "Neuer Foren-Benutzer registriert: {$username} ({$email})", 'INFO');
        }

        if ($status === self::STATUS_PENDING) {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Dein Benutzerkonto wurde erstellt und wartet auf Freischaltung durch einen Administrator.'
            ];
        }

        // Automatisch einloggen
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['weave_forum_user_id'] = $newUser['id'];

        return [
            'success' => true,
            'status' => 'active',
            'user' => $newUser,
            'message' => 'Willkommen im Forum! Dein Benutzerkonto wurde erfolgreich aktiviert.'
        ];
    }

    /**
     * Authentifiziert einen Forum-Benutzer und setzt die Session.
     */
    public static function login(string $login, string $password): array {
        $user = self::findUser($login);
        if (!$user) {
            return ['success' => false, 'message' => 'Benutzername oder Passwort ist nicht korrekt.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Benutzername oder Passwort ist nicht korrekt.'];
        }

        if ($user['status'] === self::STATUS_BANNED) {
            $reason = !empty($user['ban_reason']) ? ' Grund: ' . htmlspecialchars($user['ban_reason']) : '';
            $until = !empty($user['banned_until']) ? ' (Gesperrt bis ' . htmlspecialchars($user['banned_until']) . ')' : ' (Dauerhaft gesperrt)';
            return ['success' => false, 'message' => "Dieses Forum-Konto wurde gesperrt.{$until}{$reason}"];
        }

        if ($user['status'] === self::STATUS_PENDING) {
            return ['success' => false, 'message' => 'Dein Konto wurde noch nicht von der Moderation freigeschaltet.'];
        }

        // Login erfolgreich
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['weave_forum_user_id'] = $user['id'];

        // Last Login aktualisieren
        $user['last_login'] = date('Y-m-d H:i:s');
        self::saveUser($user);

        return ['success' => true, 'user' => $user, 'message' => 'Erfolgreich angemeldet.'];
    }

    /**
     * Meldet den aktuellen Forum-Benutzer ab.
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset($_SESSION['weave_forum_user_id']);
    }

    /**
     * Liefert das Benutzer-Array des aktuell angemeldeten Forum-Nutzers oder null.
     */
    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $id = $_SESSION['weave_forum_user_id'] ?? null;
        if (!empty($id)) {
            $user = self::getUserById((string)$id);
            if ($user) {
                return $user;
            }
        }

        // Automatische Erkennung einer aktiven CMS-Administrator-Sitzung
        if (!empty($_SESSION['admin_logged_in'])) {
            $siteAdmin = self::getSiteAdminUser();
            if ($siteAdmin) {
                return $siteAdmin;
            }
        }

        return null;
    }

    /**
     * Ermittelt das Forum-Benutzerkonto des WeaveCMS-Seiten-Administrators.
     */
    public static function getSiteAdminUser(?array $data = null): ?array {
        if ($data === null) {
            $data = $GLOBALS['data'] ?? [];
            if (empty($data)) {
                $root = dirname(__DIR__, 2);
                $df = $root . '/data.json';
                if (file_exists($df)) {
                    $data = json_decode((string)file_get_contents($df), true) ?: [];
                }
            }
        }

        $contactName = !empty($data['texts']['contact_name']) ? trim($data['texts']['contact_name']) : 'Administrator';
        $contactEmail = !empty($data['texts']['contact_email']) ? trim($data['texts']['contact_email']) : '';

        $users = self::getAllUsers();
        // 1. Suche nach Email oder Name mit Rolle Admin
        foreach ($users as $u) {
            if (($u['role'] ?? '') === self::ROLE_ADMIN) {
                if (!empty($contactEmail) && strcasecmp($u['email'] ?? '', $contactEmail) === 0) {
                    return $u;
                }
                if (strcasecmp($u['username'] ?? '', $contactName) === 0) {
                    return $u;
                }
            }
        }
        // 2. Fallback: Erster Admin
        foreach ($users as $u) {
            if (($u['role'] ?? '') === self::ROLE_ADMIN) {
                return $u;
            }
        }
        return null;
    }

    /**
     * Übernimmt den WeaveCMS-Seitenadministrator als offiziellen Forum-Administrator (forum_admin)
     * in die aktive Speicher-Engine (JSON und/oder relationale Datenbank).
     */
    public static function syncSiteAdminAsForumAdmin(?array $data = null): array {
        if ($data === null) {
            $data = $GLOBALS['data'] ?? [];
            if (empty($data)) {
                $root = dirname(__DIR__, 2);
                $df = $root . '/data.json';
                if (file_exists($df)) {
                    $data = json_decode((string)file_get_contents($df), true) ?: [];
                }
            }
        }

        $username = !empty($data['texts']['contact_name']) ? trim($data['texts']['contact_name']) : 'Administrator';
        $email = !empty($data['texts']['contact_email']) ? trim($data['texts']['contact_email']) : 'admin@weavecms.local';

        // Passwort-Hash ermitteln
        $pwHash = $data['settings']['admin_password_hash'] ?? null;
        if (empty($pwHash)) {
            $defaultPw = defined('DEFAULT_ADMIN_PASSWORD') ? DEFAULT_ADMIN_PASSWORD : bin2hex(random_bytes(8));
            $pwHash = password_hash($defaultPw, PASSWORD_DEFAULT);
        }

        // Benutzer suchen (per Email oder Username)
        $existing = self::findUser($email) ?? self::findUser($username);

        if ($existing) {
            $existing['role'] = self::ROLE_ADMIN;
            $existing['status'] = self::STATUS_ACTIVE;
            $existing['username'] = $username;
            $existing['email'] = $email;
            if (!empty($pwHash) && empty($existing['password_hash'])) {
                $existing['password_hash'] = $pwHash;
            }
            if (empty($existing['signature'])) {
                $existing['signature'] = 'WeaveCMS Site Administrator';
            }
            self::saveUser($existing);
            return [
                'success' => true,
                'username' => $username,
                'email' => $email,
                'is_new' => false,
                'message' => "Seiten-Administrator '{$username}' ({$email}) wurde erfolgreich als Forum-Administrator (forum_admin) aktualisiert und freigeschaltet."
            ];
        }

        $newUser = [
            'id' => 'user_admin_' . substr(md5($email . $username), 0, 8),
            'username' => $username,
            'email' => $email,
            'password_hash' => $pwHash,
            'role' => self::ROLE_ADMIN,
            'status' => self::STATUS_ACTIVE,
            'avatar' => '',
            'signature' => 'WeaveCMS Site Administrator',
            'bio' => 'Offizieller Seiten-Administrator der WeaveCMS-Instanz.',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'post_count' => 0,
            'topic_count' => 0,
            'banned_until' => null,
            'ban_reason' => '',
            'notification_email' => 1,
            'alias' => '',
            'use_alias_only' => 0,
            'privacy_settings' => self::getDefaultPrivacySettings()
        ];

        self::saveUser($newUser);

        return [
            'success' => true,
            'username' => $username,
            'email' => $email,
            'is_new' => true,
            'message' => "Seiten-Administrator '{$username}' ({$email}) wurde erfolgreich als neuer Forum-Administrator (forum_admin) in die Datenbank/JSON eingetragen."
        ];
    }

    /**
     * Prüft, ob aktuell ein Forum-Benutzer angemeldet ist.
     */
    public static function isLoggedIn(): bool {
        return self::getCurrentUser() !== null;
    }

    // =========================================================================
    // RECHTE- & ROLLENPRÜFUNG
    // =========================================================================

    /**
     * Prüft die Berechtigung für eine Aktion.
     * @param string $action z. B. 'create_topic', 'reply', 'moderate', 'admin'
     * @param array|null $user
     * @param array|null $context Board- oder Topic-Array
     * @return bool
     */
    public static function userCan(string $action, ?array $user = null, ?array $context = null): bool {
        if ($user === null) {
            $user = self::getCurrentUser();
        }

        // Nicht eingeloggte Gäste
        if (!$user) {
            if ($action === 'view_public') {
                return true;
            }
            return false;
        }

        if (($user['status'] ?? self::STATUS_ACTIVE) !== self::STATUS_ACTIVE) {
            return false; // Gesperrte oder wartende Benutzer dürfen nichts
        }

        $role = $user['role'] ?? self::ROLE_USER;

        // Forum-Admins dürfen ausnahmslos alles
        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        // Forum-Moderatoren
        if ($role === self::ROLE_MODERATOR) {
            if (in_array($action, ['moderate', 'lock_topic', 'pin_topic', 'move_topic', 'delete_post', 'view_reports', 'create_topic', 'reply', 'edit_own_post', 'delete_own_post', 'view_public'], true)) {
                return true;
            }
            // Moderatoren dürfen keine Systemeinstellungen ändern
            if ($action === 'admin' || $action === 'manage_settings' || $action === 'manage_roles') {
                return false;
            }
        }

        // Reguläre Foren-Benutzer
        if ($action === 'create_topic' || $action === 'reply' || $action === 'view_public') {
            if ($context && !empty($context['min_role_write'])) {
                if ($context['min_role_write'] === self::ROLE_ADMIN && $role !== self::ROLE_ADMIN) return false;
                if ($context['min_role_write'] === self::ROLE_MODERATOR && !in_array($role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true)) return false;
            }
            return true;
        }

        if ($action === 'edit_own_post') {
            return true; // Eigene Posts bearbeiten
        }

        return false;
    }

    // =========================================================================
    // DSGVO & DATENSCHUTZ
    // =========================================================================

    /**
     * DSGVO-Datenexport aller gespeicherten Informationen eines Nutzers.
     */
    public static function exportUserData(string $userId): ?array {
        $user = self::getUserById($userId);
        if (!$user) return null;

        $export = $user;
        unset($export['password_hash']);
        return $export;
    }

    /**
     * Anonymisiert ein Benutzerkonto (DSGVO Recht auf Vergessenwerden).
     */
    public static function anonymizeUser(string $userId): bool {
        $user = self::getUserById($userId);
        if (!$user) return false;

        $user['username'] = 'Gelöschter_Benutzer_' . substr($userId, -4);
        $user['email'] = 'deleted_' . $userId . '@anonymized.local';
        $user['password_hash'] = '';
        $user['status'] = self::STATUS_BANNED;
        $user['avatar'] = '';
        $user['signature'] = '';
        $user['bio'] = '';
        $user['alias'] = '';
        $user['use_alias_only'] = 0;
        $user['ban_reason'] = 'Konto auf Wunsch des Benutzers gelöscht/anonymisiert.';
        $user['notification_email'] = 0;

        return self::saveUser($user);
    }

    // =========================================================================
    // SPEICHER-MIGRATION (JSON ➔ SQLite/MySQL und SQLite/MySQL ➔ JSON)
    // =========================================================================

    /**
     * Migriert alle Foren-Benutzer von JSON nach SQLite.
     * Erstellt die SQLite-Datenbank und Tabellen auch dann sauber, wenn noch keine JSON-Datei vorhanden ist.
     */
    public static function migrateToJsonToSqlite(): array {
        if (!self::isSqliteSupported()) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_sqlite ist auf diesem Server nicht geladen. Bitte in php.ini "extension=pdo_sqlite" aktivieren.'
            ];
        }

        try {
            $pdo = self::getSqlitePdo();
            $jsonFile = self::getJsonFilePath();
            $users = [];

            if (file_exists($jsonFile)) {
                $raw = @file_get_contents($jsonFile);
                $decoded = json_decode((string)$raw, true);
                if (is_array($decoded)) {
                    $users = $decoded;
                }
            }

            $count = 0;
            if (!empty($users)) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT OR REPLACE INTO weave_forum_users (
                        id, username, email, password_hash, role, status, avatar, signature,
                        bio, created_at, last_login, post_count, topic_count, banned_until, ban_reason, notification_email,
                        alias, use_alias_only, privacy_settings
                    ) VALUES (
                        :id, :username, :email, :password_hash, :role, :status, :avatar, :signature,
                        :bio, :created_at, :last_login, :post_count, :topic_count, :banned_until, :ban_reason, :notification_email,
                        :alias, :use_alias_only, :privacy_settings
                    )
                ");

                foreach ($users as $u) {
                    if (empty($u['id'])) continue;
                    $u = self::normalizeUser($u);
                    $stmt->execute([
                        ':id' => $u['id'],
                        ':username' => $u['username'],
                        ':email' => mb_strtolower($u['email']),
                        ':password_hash' => $u['password_hash'] ?? '',
                        ':role' => $u['role'] ?? self::ROLE_USER,
                        ':status' => $u['status'] ?? self::STATUS_ACTIVE,
                        ':avatar' => $u['avatar'] ?? '',
                        ':signature' => $u['signature'] ?? '',
                        ':bio' => $u['bio'] ?? '',
                        ':created_at' => $u['created_at'] ?? date('Y-m-d H:i:s'),
                        ':last_login' => $u['last_login'] ?? '',
                        ':post_count' => (int)($u['post_count'] ?? 0),
                        ':topic_count' => (int)($u['topic_count'] ?? 0),
                        ':banned_until' => $u['banned_until'] ?? null,
                        ':ban_reason' => $u['ban_reason'] ?? '',
                        ':notification_email' => !empty($u['notification_email']) ? 1 : 0,
                        ':alias' => $u['alias'] ?? '',
                        ':use_alias_only' => !empty($u['use_alias_only']) ? 1 : 0,
                        ':privacy_settings' => json_encode($u['privacy_settings'] ?? self::getDefaultPrivacySettings(), JSON_UNESCAPED_UNICODE)
                    ]);
                    $count++;
                }
                $pdo->commit();
            }

            $msg = $count > 0
                ? "Erfolgreich {$count} Benutzer von JSON in die SQLite-Datenbank übertragen!"
                : "SQLite-Datenbank (data/forum.sqlite) und Tabellen wurden erfolgreich initialisiert und aktiviert!";

            return [
                'success' => true,
                'count' => $count,
                'message' => $msg
            ];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    /**
     * Migriert alle Foren-Benutzer von JSON nach MySQL.
     */
    public static function migrateToJsonToMysql(): array {
        if (!self::isMysqlSupported()) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht geladen.'
            ];
        }

        try {
            $pdo = self::getMysqlPdo();
            $jsonFile = self::getJsonFilePath();
            $users = [];

            if (file_exists($jsonFile)) {
                $raw = @file_get_contents($jsonFile);
                $decoded = json_decode((string)$raw, true);
                if (is_array($decoded)) {
                    $users = $decoded;
                }
            }

            $count = 0;
            if (!empty($users)) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    REPLACE INTO weave_forum_users (
                        id, username, email, password_hash, role, status, avatar, signature,
                        bio, created_at, last_login, post_count, topic_count, banned_until, ban_reason, notification_email,
                        alias, use_alias_only, privacy_settings
                    ) VALUES (
                        :id, :username, :email, :password_hash, :role, :status, :avatar, :signature,
                        :bio, :created_at, :last_login, :post_count, :topic_count, :banned_until, :ban_reason, :notification_email,
                        :alias, :use_alias_only, :privacy_settings
                    )
                ");

                foreach ($users as $u) {
                    if (empty($u['id'])) continue;
                    $u = self::normalizeUser($u);
                    $stmt->execute([
                        ':id' => $u['id'],
                        ':username' => $u['username'],
                        ':email' => mb_strtolower($u['email']),
                        ':password_hash' => $u['password_hash'] ?? '',
                        ':role' => $u['role'] ?? self::ROLE_USER,
                        ':status' => $u['status'] ?? self::STATUS_ACTIVE,
                        ':avatar' => $u['avatar'] ?? '',
                        ':signature' => $u['signature'] ?? '',
                        ':bio' => $u['bio'] ?? '',
                        ':created_at' => $u['created_at'] ?? date('Y-m-d H:i:s'),
                        ':last_login' => $u['last_login'] ?? '',
                        ':post_count' => (int)($u['post_count'] ?? 0),
                        ':topic_count' => (int)($u['topic_count'] ?? 0),
                        ':banned_until' => $u['banned_until'] ?? null,
                        ':ban_reason' => $u['ban_reason'] ?? '',
                        ':notification_email' => !empty($u['notification_email']) ? 1 : 0,
                        ':alias' => $u['alias'] ?? '',
                        ':use_alias_only' => !empty($u['use_alias_only']) ? 1 : 0,
                        ':privacy_settings' => json_encode($u['privacy_settings'] ?? self::getDefaultPrivacySettings(), JSON_UNESCAPED_UNICODE)
                    ]);
                    $count++;
                }
                $pdo->commit();
            }

            return [
                'success' => true,
                'count' => $count,
                'message' => "MySQL-Benutzertabelle erfolgreich initialisiert!"
            ];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    /**
     * Migriert alle Foren-Benutzer von SQLite nach JSON.
     */
    public static function migrateSqliteToJson(): array {
        try {
            $pdo = self::getSqlitePdo();
            $stmt = $pdo->query("SELECT * FROM weave_forum_users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();

            foreach ($users as &$u) {
                $u = self::normalizeUser($u);
            }

            if (!self::saveAllUsersJson($users)) {
                return ['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei.'];
            }

            return [
                'success' => true,
                'count' => count($users),
                'message' => "Erfolgreich " . count($users) . " Benutzer von SQLite nach data/forum_users.json exportiert!"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    /**
     * Migriert alle Foren-Benutzer von MySQL nach JSON.
     */
    public static function migrateMysqlToJson(): array {
        try {
            $pdo = self::getMysqlPdo();
            $stmt = $pdo->query("SELECT * FROM weave_forum_users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();

            foreach ($users as &$u) {
                $u = self::normalizeUser($u);
            }

            if (!self::saveAllUsersJson($users)) {
                return ['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei.'];
            }

            return [
                'success' => true,
                'count' => count($users),
                'message' => "Erfolgreich " . count($users) . " Benutzer von MySQL nach data/forum_users.json exportiert!"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    public static function getRoleColor(string $role): string {
        switch ($role) {
            case self::ROLE_ADMIN:
                return '#dc2626'; // Rot (Site Administrator)
            case self::ROLE_MODERATOR:
                return '#2563eb'; // Blau (Moderator)
            case self::ROLE_USER:
                return '#475569'; // Schiefer (Mitglied)
            default:
                return '#64748b'; // Grau (Gast)
        }
    }

    public static function getRoleLabel(string $role): string {
        switch ($role) {
            case self::ROLE_ADMIN:
                return 'Site Administrator';
            case self::ROLE_MODERATOR:
                return 'Moderator';
            case self::ROLE_USER:
                return 'Mitglied';
            default:
                return 'Gast';
        }
    }

    public static function getOnlineUsersStats(): array {
        $curUser = self::getCurrentUser();
        $onlineMembers = [];
        if ($curUser) {
            $priv = self::getPrivacySettings($curUser);
            if (!empty($priv['show_online_status'])) {
                $role = $curUser['role'] ?? self::ROLE_USER;
                $showRole = !empty($priv['show_role']);
                $onlineMembers[] = [
                    'username' => self::getDisplayName($curUser),
                    'role' => $showRole ? $role : self::ROLE_USER,
                    'color' => $showRole ? self::getRoleColor($role) : '#475569'
                ];
            }
        }

        if (empty($_SESSION['forum_guests_simulated'])) {
            $_SESSION['forum_guests_simulated'] = rand(8, 24);
        }
        $guestCount = (int)$_SESSION['forum_guests_simulated'];
        $memberCount = $curUser ? max(1, count($onlineMembers)) : 0;

        return [
            'members' => $onlineMembers,
            'member_count' => $memberCount,
            'guest_count' => $guestCount,
            'total_online' => $memberCount + $guestCount
        ];
    }
}
