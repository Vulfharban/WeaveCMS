<?php
/**
 * ============================================================================
 * WeaveCMS Diskussionsforum – ForumManager
 * ============================================================================
 * Verwaltet Kategorien, Unterforen, Threads, Beiträge, Moderationsfunktionen,
 * Meldungen, Volltextsuche, Formatierung und duale Speicherung (JSON/SQLite).
 * ============================================================================
 */

class ForumManager {

    protected static ?PDO $dbPdo = null;
    protected static ?PDO $sqlitePdo = null;

    /**
     * Pfad zur JSON-Speicherdatei.
     */
    public static function getJsonFilePath(): string {
        return dirname(__DIR__, 2) . '/data/forum_data.json';
    }

    /**
     * Pfad zur SQLite-Speicherdatei.
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
                $cfg = PluginManager::getPluginConfig('weave-forum', $data);
                $driver = $cfg['storage_driver'] ?? 'json';
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
     * Prüft, ob ein relationaler Datenbanktreiber (MySQL oder SQLite) aktiv ist.
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
     * Liefert die aktive PDO-Verbindung (MySQL oder SQLite).
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
     * Ermittelt die effektive MySQL-Konfiguration (Priorität: Plugin-Config > config.local.php > Fallbacks).
     */
    public static function getMysqlConfig(): array {
        $cfg = [];
        if (class_exists('PluginManager') && !empty($GLOBALS['data'])) {
            $cfg = PluginManager::getPluginConfig('weave-forum', $GLOBALS['data']);
        }

        $localConfig = dirname(__DIR__, 2) . '/config.local.php';
        if (file_exists($localConfig)) {
            require_once $localConfig;
        }

        $host = !empty($cfg['mysql_host']) ? $cfg['mysql_host'] : (defined('WEAVE_DB_HOST') ? WEAVE_DB_HOST : '127.0.0.1');
        $port = !empty($cfg['mysql_port']) ? (int)$cfg['mysql_port'] : (defined('WEAVE_DB_PORT') ? (int)WEAVE_DB_PORT : 3306);
        $dbname = !empty($cfg['mysql_database']) ? $cfg['mysql_database'] : (defined('WEAVE_DB_NAME') ? WEAVE_DB_NAME : 'WeaveCMS');
        $user = !empty($cfg['mysql_user']) ? $cfg['mysql_user'] : (defined('WEAVE_DB_USER') ? WEAVE_DB_USER : 'root');
        $pass = isset($cfg['mysql_password']) ? (string)$cfg['mysql_password'] : (defined('WEAVE_DB_PASS') ? (string)WEAVE_DB_PASS : '');
        $charset = !empty($cfg['mysql_charset']) ? $cfg['mysql_charset'] : 'utf8mb4';

        $isFromLocalConfig = empty($cfg['mysql_host']) && defined('WEAVE_DB_HOST');

        return [
            'host' => $host,
            'port' => $port,
            'database' => $dbname,
            'user' => $user,
            'password' => $pass,
            'charset' => $charset,
            'from_local_config' => $isFromLocalConfig,
            'has_custom_config' => !empty($cfg['mysql_host']) || !empty($cfg['mysql_database'])
        ];
    }

    /**
     * Prüft eine MySQL-Verbindung live mit gegebenen oder gespeicherten Parametern.
     * @param array $params [mysql_host, mysql_port, mysql_database, mysql_user, mysql_password, mysql_charset]
     * @return array [success => bool, message => string, server_version => string|null]
     */
    public static function testMysqlConnection(array $params = []): array {
        if (!self::isMysqlSupported()) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht aktiviert.'
            ];
        }

        $default = self::getMysqlConfig();
        $host = !empty($params['mysql_host']) ? trim($params['mysql_host']) : $default['host'];
        $port = !empty($params['mysql_port']) ? (int)$params['mysql_port'] : $default['port'];
        $dbname = !empty($params['mysql_database']) ? trim($params['mysql_database']) : $default['database'];
        $user = !empty($params['mysql_user']) ? trim($params['mysql_user']) : $default['user'];
        $pass = isset($params['mysql_password']) ? (string)$params['mysql_password'] : $default['password'];
        $charset = !empty($params['mysql_charset']) ? trim($params['mysql_charset']) : $default['charset'];

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4
            ]);
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            return [
                'success' => true,
                'message' => "Verbindung erfolgreich! (Server: MySQL {$version})",
                'server_version' => $version
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Stellt eine MySQL-Verbindung her und initialisiert Tabellen.
     */
    public static function getMysqlPdo(): PDO {
        if (!self::isMysqlSupported()) {
            throw new RuntimeException('Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht aktiviert.');
        }

        $c = self::getMysqlConfig();
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        self::initMysqlTables($pdo);
        return $pdo;
    }

    /**
     * Initialisiert alle Forentabellen in der MySQL-Datenbank.
     */
    public static function initMysqlTables(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS weave_forum_categories (
                id VARCHAR(64) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                icon VARCHAR(64) DEFAULT 'folder',
                sort_order INT DEFAULT 0,
                min_role VARCHAR(32) DEFAULT 'public'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_boards (
                id VARCHAR(64) PRIMARY KEY,
                category_id VARCHAR(64) NOT NULL,
                parent_id VARCHAR(64) DEFAULT '',
                title VARCHAR(255) NOT NULL,
                description TEXT,
                icon VARCHAR(64) DEFAULT 'forum',
                sort_order INT DEFAULT 0,
                is_locked TINYINT(1) DEFAULT 0,
                min_role_read VARCHAR(32) DEFAULT 'public',
                min_role_write VARCHAR(32) DEFAULT 'forum_user',
                INDEX idx_fboards_cat (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_topics (
                id VARCHAR(64) PRIMARY KEY,
                board_id VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                author_id VARCHAR(64) NOT NULL,
                author_name VARCHAR(128) NOT NULL,
                created_at VARCHAR(32) NOT NULL,
                updated_at VARCHAR(32) NOT NULL,
                views INT DEFAULT 0,
                is_pinned TINYINT(1) DEFAULT 0,
                is_locked TINYINT(1) DEFAULT 0,
                is_solved TINYINT(1) DEFAULT 0,
                status VARCHAR(32) DEFAULT 'published',
                last_post_at VARCHAR(32) NOT NULL,
                last_post_user VARCHAR(128) NOT NULL,
                INDEX idx_ftopics_board (board_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_posts (
                id VARCHAR(64) PRIMARY KEY,
                topic_id VARCHAR(64) NOT NULL,
                author_id VARCHAR(64) NOT NULL,
                author_name VARCHAR(128) NOT NULL,
                content LONGTEXT NOT NULL,
                created_at VARCHAR(32) NOT NULL,
                updated_at VARCHAR(32) DEFAULT '',
                edited_by VARCHAR(128) DEFAULT '',
                status VARCHAR(32) DEFAULT 'published',
                INDEX idx_fposts_topic (topic_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_likes (
                post_id VARCHAR(64) NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                PRIMARY KEY (post_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_reports (
                id VARCHAR(64) PRIMARY KEY,
                post_id VARCHAR(64) NOT NULL,
                reporter_id VARCHAR(64) NOT NULL,
                reporter_name VARCHAR(128) NOT NULL,
                reason TEXT NOT NULL,
                status VARCHAR(32) DEFAULT 'open',
                created_at VARCHAR(32) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_subscriptions (
                user_id VARCHAR(64) NOT NULL,
                topic_id VARCHAR(64) NOT NULL,
                PRIMARY KEY (user_id, topic_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS weave_forum_audit_log (
                id VARCHAR(64) PRIMARY KEY,
                moderator_id VARCHAR(64) NOT NULL,
                moderator_name VARCHAR(128) NOT NULL,
                action VARCHAR(64) NOT NULL,
                target_type VARCHAR(64) NOT NULL,
                target_id VARCHAR(64) NOT NULL,
                reason TEXT,
                timestamp VARCHAR(32) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /**
     * Stellt eine SQLite-Verbindung her und initialisiert Tabellen.
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

        self::initSqliteTables($pdo);

        self::$sqlitePdo = $pdo;
        return $pdo;
    }

    /**
     * Initialisiert alle Forentabellen und Indizes in der SQLite-Datenbank.
     */
    public static function initSqliteTables(?PDO $pdo = null): PDO {
        if ($pdo === null) {
            return self::getSqlitePdo();
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS weave_forum_categories (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                description TEXT DEFAULT '',
                icon TEXT DEFAULT 'folder',
                sort_order INTEGER DEFAULT 0,
                min_role TEXT DEFAULT 'public'
            );

            CREATE TABLE IF NOT EXISTS weave_forum_boards (
                id TEXT PRIMARY KEY,
                category_id TEXT NOT NULL,
                parent_id TEXT DEFAULT '',
                title TEXT NOT NULL,
                description TEXT DEFAULT '',
                icon TEXT DEFAULT 'forum',
                sort_order INTEGER DEFAULT 0,
                is_locked INTEGER DEFAULT 0,
                min_role_read TEXT DEFAULT 'public',
                min_role_write TEXT DEFAULT 'forum_user'
            );

            CREATE TABLE IF NOT EXISTS weave_forum_topics (
                id TEXT PRIMARY KEY,
                board_id TEXT NOT NULL,
                title TEXT NOT NULL,
                author_id TEXT NOT NULL,
                author_name TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                views INTEGER DEFAULT 0,
                is_pinned INTEGER DEFAULT 0,
                is_locked INTEGER DEFAULT 0,
                is_solved INTEGER DEFAULT 0,
                status TEXT DEFAULT 'published',
                last_post_at TEXT NOT NULL,
                last_post_user TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS weave_forum_posts (
                id TEXT PRIMARY KEY,
                topic_id TEXT NOT NULL,
                author_id TEXT NOT NULL,
                author_name TEXT NOT NULL,
                content TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT DEFAULT '',
                edited_by TEXT DEFAULT '',
                status TEXT DEFAULT 'published'
            );

            CREATE TABLE IF NOT EXISTS weave_forum_likes (
                post_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                PRIMARY KEY (post_id, user_id)
            );

            CREATE TABLE IF NOT EXISTS weave_forum_reports (
                id TEXT PRIMARY KEY,
                post_id TEXT NOT NULL,
                reporter_id TEXT NOT NULL,
                reporter_name TEXT NOT NULL,
                reason TEXT NOT NULL,
                status TEXT DEFAULT 'open',
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS weave_forum_subscriptions (
                user_id TEXT NOT NULL,
                topic_id TEXT NOT NULL,
                PRIMARY KEY (user_id, topic_id)
            );

            CREATE TABLE IF NOT EXISTS weave_forum_audit_log (
                id TEXT PRIMARY KEY,
                moderator_id TEXT NOT NULL,
                moderator_name TEXT NOT NULL,
                action TEXT NOT NULL,
                target_type TEXT NOT NULL,
                target_id TEXT NOT NULL,
                reason TEXT DEFAULT '',
                timestamp TEXT NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_fboards_cat ON weave_forum_boards(category_id);
            CREATE INDEX IF NOT EXISTS idx_ftopics_board ON weave_forum_topics(board_id);
            CREATE INDEX IF NOT EXISTS idx_fposts_topic ON weave_forum_posts(topic_id);
            CREATE INDEX IF NOT EXISTS idx_flikes_post ON weave_forum_likes(post_id);
        ");

        try {
            $pdo->exec("ALTER TABLE weave_forum_topics ADD COLUMN is_solved INTEGER DEFAULT 0");
        } catch (Exception $e) {}

        return $pdo;
    }

    // =========================================================================
    // STANDARD-DATEN & INITIALISIERUNG
    // =========================================================================

    public static function getDefaultData(): array {
        return [
            'categories' => [
                [
                    'id' => 'cat_general',
                    'title' => 'Allgemeine Diskussionen',
                    'description' => 'Austausch rund um Gemeinschaft, Ankündigungen und Vorstellungen',
                    'icon' => 'chat',
                    'sort_order' => 10,
                    'min_role' => 'public'
                ],
                [
                    'id' => 'cat_craft',
                    'title' => 'Handwerk & Techniken',
                    'description' => 'Materialien, Werkzeuge, eigene Werkstücke und Inspirationen',
                    'icon' => 'handyman',
                    'sort_order' => 20,
                    'min_role' => 'public'
                ],
                [
                    'id' => 'cat_support',
                    'title' => 'Hilfe & Feedback',
                    'description' => 'Fragen zur Website, Bedienung und Community-Vorschläge',
                    'icon' => 'help_outline',
                    'sort_order' => 30,
                    'min_role' => 'public'
                ]
            ],
            'boards' => [
                [
                    'id' => 'board_welcome',
                    'category_id' => 'cat_general',
                    'parent_id' => '',
                    'title' => 'Vorstellungsrunde',
                    'description' => 'Neue Mitglieder stellen sich und ihre Interessen kurz vor.',
                    'icon' => 'waving_hand',
                    'sort_order' => 10,
                    'is_locked' => 0,
                    'min_role_read' => 'public',
                    'min_role_write' => 'forum_user'
                ],
                [
                    'id' => 'board_offtopic',
                    'category_id' => 'cat_general',
                    'parent_id' => '',
                    'title' => 'Plauderecke & Off-Topic',
                    'description' => 'Alles, was sonst nirgendwo reinpasst – lockerer Austausch.',
                    'icon' => 'coffee',
                    'sort_order' => 20,
                    'is_locked' => 0,
                    'min_role_read' => 'public',
                    'min_role_write' => 'forum_user'
                ],
                [
                    'id' => 'board_materials',
                    'category_id' => 'cat_craft',
                    'parent_id' => '',
                    'title' => 'Materialien & Werkzeuge',
                    'description' => 'Diskussionen über Weiden, Naturmaterialien, Pflegeöle und Flechtwerkzeuge.',
                    'icon' => 'construction',
                    'sort_order' => 10,
                    'is_locked' => 0,
                    'min_role_read' => 'public',
                    'min_role_write' => 'forum_user'
                ],
                [
                    'id' => 'board_showcase',
                    'category_id' => 'cat_craft',
                    'parent_id' => '',
                    'title' => 'Eigene Werkstücke & Galerie',
                    'description' => 'Zeigt eure fertigen Arbeiten, Körbe und Designobjekte.',
                    'icon' => 'photo_library',
                    'sort_order' => 20,
                    'is_locked' => 0,
                    'min_role_read' => 'public',
                    'min_role_write' => 'forum_user'
                ],
                [
                    'id' => 'board_feedback',
                    'category_id' => 'cat_support',
                    'parent_id' => '',
                    'title' => 'Vorschläge & Website-Feedback',
                    'description' => 'Anregungen, Wünsche und Fehlerberichte für WeaveCMS.',
                    'icon' => 'feedback',
                    'sort_order' => 10,
                    'is_locked' => 0,
                    'min_role_read' => 'public',
                    'min_role_write' => 'forum_user'
                ]
            ],
            'topics' => [
                [
                    'id' => 'topic_welcome',
                    'board_id' => 'board_welcome',
                    'title' => 'Herzlich willkommen im WeaveCMS Community-Forum!',
                    'author_id' => 'fusr_system',
                    'author_name' => 'Community Team',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'views' => 12,
                    'is_pinned' => 1,
                    'is_locked' => 0,
                    'is_solved' => 0,
                    'status' => 'published',
                    'last_post_at' => date('Y-m-d H:i:s'),
                    'last_post_user' => 'Community Team'
                ]
            ],
            'posts' => [
                [
                    'id' => 'post_welcome_1',
                    'topic_id' => 'topic_welcome',
                    'author_id' => 'fusr_system',
                    'author_name' => 'Community Team',
                    'content' => "Herzlich willkommen in unserem neuen Diskussionsforum!\n\nHier könnt ihr euch austauschen, Fragen stellen, eigene Projekte präsentieren und Gleichgesinnte kennenlernen.\n\nWir wünschen allen viel Freude beim Mitgestalten!",
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => '',
                    'edited_by' => '',
                    'likes' => [],
                    'status' => 'published'
                ]
            ],
            'reports' => [],
            'subscriptions' => [],
            'audit_log' => []
        ];
    }

    /**
     * Lädt den gesamten Forum-Datensatz aus dem JSON-Speicher.
     */
    public static function loadJsonData(): array {
        $file = self::getJsonFilePath();
        if (!file_exists($file)) {
            $default = self::getDefaultData();
            self::saveJsonData($default);
            return $default;
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return self::getDefaultData();
        }

        return $data;
    }

    /**
     * Speichert den gesamten Forum-Datensatz atomar im JSON-Speicher.
     */
    public static function saveJsonData(array $data): bool {
        $file = self::getJsonFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    // =========================================================================
    // KATEGORIEN & UNTERFOREN (BOARDS)
    // =========================================================================

    public static function getCategories(): array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->query("SELECT * FROM weave_forum_categories ORDER BY sort_order ASC, title ASC");
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        $cats = $data['categories'] ?? [];
        usort($cats, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        return $cats;
    }

    public static function getCategory(string $id): ?array {
        $cats = self::getCategories();
        foreach ($cats as $c) {
            if ($c['id'] === $id) return $c;
        }
        return null;
    }

    public static function saveCategory(array $category): bool {
        if (empty($category['id'])) {
            $category['id'] = 'cat_' . bin2hex(random_bytes(4));
        }

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    REPLACE INTO weave_forum_categories (id, title, description, icon, sort_order, min_role)
                    VALUES (:id, :title, :description, :icon, :sort_order, :min_role)
                ");
                return $stmt->execute([
                    ':id' => $category['id'],
                    ':title' => $category['title'],
                    ':description' => $category['description'] ?? '',
                    ':icon' => $category['icon'] ?? 'folder',
                    ':sort_order' => (int)($category['sort_order'] ?? 0),
                    ':min_role' => $category['min_role'] ?? 'public'
                ]);
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        $found = false;
        foreach ($data['categories'] as &$c) {
            if ($c['id'] === $category['id']) {
                $c = array_merge($c, $category);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $data['categories'][] = $category;
        }
        return self::saveJsonData($data);
    }

    public static function deleteCategory(string $id): bool {
        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->prepare("DELETE FROM weave_forum_categories WHERE id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM weave_forum_boards WHERE category_id = ?")->execute([$id]);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        $data['categories'] = array_values(array_filter($data['categories'], fn($c) => $c['id'] !== $id));
        $data['boards'] = array_values(array_filter($data['boards'], fn($b) => $b['category_id'] !== $id));
        return self::saveJsonData($data);
    }

    public static function getBoards(?string $categoryId = null): array {
        if (self::isDatabase()) {
            try {
                if ($categoryId) {
                    $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_boards WHERE category_id = ? ORDER BY sort_order ASC, title ASC");
                    $stmt->execute([$categoryId]);
                } else {
                    $stmt = self::getSqlitePdo()->query("SELECT * FROM weave_forum_boards ORDER BY sort_order ASC, title ASC");
                }
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        $boards = $data['boards'] ?? [];
        if ($categoryId) {
            $boards = array_filter($boards, fn($b) => ($b['category_id'] ?? '') === $categoryId);
        }
        usort($boards, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        return array_values($boards);
    }

    public static function getBoard(string $id): ?array {
        $boards = self::getBoards();
        foreach ($boards as $b) {
            if ($b['id'] === $id) return $b;
        }
        return null;
    }

    /**
     * Lädt die aktive Forum-Konfiguration aus data.json via PluginManager.
     */
    public static function getConfig(): array {
        $data = $GLOBALS['data'] ?? null;
        if (!is_array($data) || empty($data)) {
            $dataPath = dirname(__DIR__, 2) . '/data.json';
            if (file_exists($dataPath)) {
                $json = @file_get_contents($dataPath);
                $data = @json_decode($json, true) ?: [];
            }
        }
        if (is_array($data)) {
            if (class_exists('PluginManager')) {
                return PluginManager::getPluginConfig('weave-forum', $data);
            }
            $configs = $data['plugins']['configs'] ?? [];
            return $configs['weave-forum'] ?? [];
        }
        return [];
    }

    /**
     * Ermittelt den globalen Zugangsmodus: 'public' (alles frei lesbar) oder 'members_only' (Registrierung erforderlich).
     */
    public static function getAccessMode(): string {
        $cfg = self::getConfig();
        return $cfg['guest_access_mode'] ?? 'public';
    }

    /**
     * Liefert die Liste aller Unterforen (Board-IDs), die Gäste auch bei 'members_only' frei lesen dürfen.
     */
    public static function getGuestAllowedBoards(): array {
        $cfg = self::getConfig();
        $allowed = $cfg['guest_allowed_boards'] ?? [];
        return is_array($allowed) ? $allowed : [];
    }

    /**
     * Prüft, ob ein Benutzer (oder Gast) das angegebene Board lesen darf.
     */
    public static function canUserReadBoard($boardOrId, ?array $user = null): bool {
        if ($user === null && class_exists('ForumUserManager')) {
            $user = ForumUserManager::getCurrentUser();
        }

        // Angemeldete, aktive Benutzer dürfen regulär lesen
        if ($user && ($user['status'] ?? 'active') === 'active') {
            return true;
        }

        // Benutzer ist Gast: Modus prüfen
        $boardId = is_array($boardOrId) ? ($boardOrId['id'] ?? '') : (string)$boardOrId;
        $accessMode = self::getAccessMode();

        if ($accessMode === 'members_only') {
            $allowedBoards = self::getGuestAllowedBoards();
            return in_array($boardId, $allowedBoards, true);
        }

        // Standard 'public': prüfen, ob das einzelne Board explizit beschränkt ist
        $board = is_array($boardOrId) ? $boardOrId : self::getBoard($boardId);
        if ($board && !empty($board['min_role_read']) && $board['min_role_read'] !== 'public') {
            return false;
        }

        return true;
    }

    /**
     * Prüft, ob ein Thema für den Benutzer (oder Gast) lesbar ist.
     */
    public static function canUserReadTopic($topicOrId, ?array $user = null): bool {
        $topic = is_array($topicOrId) ? $topicOrId : self::getTopic((string)$topicOrId);
        if (!$topic) return false;
        return self::canUserReadBoard($topic['board_id'] ?? '', $user);
    }

    public static function saveBoard(array $board): bool {
        if (empty($board['id'])) {
            $board['id'] = 'board_' . bin2hex(random_bytes(4));
        }

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    REPLACE INTO weave_forum_boards (
                        id, category_id, parent_id, title, description, icon, sort_order, is_locked, min_role_read, min_role_write
                    ) VALUES (
                        :id, :category_id, :parent_id, :title, :description, :icon, :sort_order, :is_locked, :min_role_read, :min_role_write
                    )
                ");
                return $stmt->execute([
                    ':id' => $board['id'],
                    ':category_id' => $board['category_id'],
                    ':parent_id' => $board['parent_id'] ?? '',
                    ':title' => $board['title'],
                    ':description' => $board['description'] ?? '',
                    ':icon' => $board['icon'] ?? 'forum',
                    ':sort_order' => (int)($board['sort_order'] ?? 0),
                    ':is_locked' => !empty($board['is_locked']) ? 1 : 0,
                    ':min_role_read' => $board['min_role_read'] ?? 'public',
                    ':min_role_write' => $board['min_role_write'] ?? 'forum_user'
                ]);
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        $found = false;
        foreach ($data['boards'] as &$b) {
            if ($b['id'] === $board['id']) {
                $b = array_merge($b, $board);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $data['boards'][] = $board;
        }
        return self::saveJsonData($data);
    }

    public static function deleteBoard(string $id): bool {
        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->prepare("DELETE FROM weave_forum_boards WHERE id = ?")->execute([$id]);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        $data['boards'] = array_values(array_filter($data['boards'], fn($b) => $b['id'] !== $id));
        return self::saveJsonData($data);
    }

    // =========================================================================
    // THEMEN (TOPICS) & BEITRÄGE (POSTS)
    // =========================================================================

    public static function getTopics(string $boardId, int $page = 1, int $perPage = 25): array {
        if (self::isDatabase()) {
            try {
                $offset = ($page - 1) * $perPage;
                $stmt = self::getSqlitePdo()->prepare("
                    SELECT * FROM weave_forum_topics 
                    WHERE board_id = ? AND status = 'published'
                    ORDER BY is_pinned DESC, last_post_at DESC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$boardId, $perPage, $offset]);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        $topics = array_filter($data['topics'] ?? [], fn($t) => ($t['board_id'] ?? '') === $boardId && ($t['status'] ?? 'published') === 'published');
        
        usort($topics, function($a, $b) {
            if (($b['is_pinned'] ?? 0) !== ($a['is_pinned'] ?? 0)) {
                return ($b['is_pinned'] ?? 0) <=> ($a['is_pinned'] ?? 0);
            }
            return strcmp($b['last_post_at'] ?? '', $a['last_post_at'] ?? '');
        });

        $offset = ($page - 1) * $perPage;
        return array_slice($topics, $offset, $perPage);
    }

    public static function countTopics(string $boardId): int {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT COUNT(*) FROM weave_forum_topics WHERE board_id = ? AND status = 'published'");
                $stmt->execute([$boardId]);
                return (int)$stmt->fetchColumn();
            } catch (Exception $e) {
                return 0;
            }
        }

        $data = self::loadJsonData();
        $topics = array_filter($data['topics'] ?? [], fn($t) => ($t['board_id'] ?? '') === $boardId && ($t['status'] ?? 'published') === 'published');
        return count($topics);
    }

    public static function getTopic(string $id): ?array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_topics WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $t = $stmt->fetch();
                return $t ?: null;
            } catch (Exception $e) {
                return null;
            }
        }

        $data = self::loadJsonData();
        foreach ($data['topics'] ?? [] as $t) {
            if ($t['id'] === $id) return $t;
        }
        return null;
    }

    public static function incrementTopicViews(string $id): void {
        if (self::isDatabase()) {
            try {
                self::getSqlitePdo()->prepare("UPDATE weave_forum_topics SET views = views + 1 WHERE id = ?")->execute([$id]);
            } catch (Exception $e) {}
            return;
        }

        $data = self::loadJsonData();
        foreach ($data['topics'] as &$t) {
            if ($t['id'] === $id) {
                $t['views'] = ($t['views'] ?? 0) + 1;
                break;
            }
        }
        self::saveJsonData($data);
    }

    public static function getPosts(string $topicId, int $page = 1, int $perPage = 20): array {
        if (self::isDatabase()) {
            try {
                $offset = ($page - 1) * $perPage;
                $stmt = self::getSqlitePdo()->prepare("
                    SELECT * FROM weave_forum_posts 
                    WHERE topic_id = ? AND status = 'published'
                    ORDER BY created_at ASC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$topicId, $perPage, $offset]);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        $posts = array_filter($data['posts'] ?? [], fn($p) => ($p['topic_id'] ?? '') === $topicId && ($p['status'] ?? 'published') === 'published');
        usort($posts, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));

        $offset = ($page - 1) * $perPage;
        return array_slice($posts, $offset, $perPage);
    }

    public static function countPosts(string $topicId): int {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT COUNT(*) FROM weave_forum_posts WHERE topic_id = ? AND status = 'published'");
                $stmt->execute([$topicId]);
                return (int)$stmt->fetchColumn();
            } catch (Exception $e) {
                return 0;
            }
        }

        $data = self::loadJsonData();
        $posts = array_filter($data['posts'] ?? [], fn($p) => ($p['topic_id'] ?? '') === $topicId && ($p['status'] ?? 'published') === 'published');
        return count($posts);
    }

    public static function getPost(string $id): ?array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_posts WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $p = $stmt->fetch();
                return $p ?: null;
            } catch (Exception $e) {
                return null;
            }
        }

        $data = self::loadJsonData();
        foreach ($data['posts'] ?? [] as $p) {
            if ($p['id'] === $id) return $p;
        }
        return null;
    }

    /**
     * Erstellt ein neues Thema inklusive des ersten Beitrags.
     */
    public static function createTopic(string $boardId, string $title, string $content, array $user): array {
        $title = trim($title);
        $content = trim($content);

        if (strlen($title) < 3) {
            return ['success' => false, 'message' => 'Der Thementitel muss mindestens 3 Zeichen lang sein.'];
        }
        if (strlen($content) < 5) {
            return ['success' => false, 'message' => 'Der Beitragsinhalt muss mindestens 5 Zeichen lang sein.'];
        }

        // Bad Words Filter
        $title = self::filterBadWords($title);
        $content = self::filterBadWords($content);

        $now = date('Y-m-d H:i:s');
        $topicId = 'topic_' . bin2hex(random_bytes(6));
        $postId = 'post_' . bin2hex(random_bytes(6));

        $topic = [
            'id' => $topicId,
            'board_id' => $boardId,
            'title' => $title,
            'author_id' => $user['id'],
            'author_name' => $user['username'],
            'created_at' => $now,
            'updated_at' => $now,
            'views' => 0,
            'is_pinned' => 0,
            'is_locked' => 0,
            'is_solved' => 0,
            'status' => 'published',
            'last_post_at' => $now,
            'last_post_user' => $user['username']
        ];

        $post = [
            'id' => $postId,
            'topic_id' => $topicId,
            'author_id' => $user['id'],
            'author_name' => $user['username'],
            'content' => $content,
            'created_at' => $now,
            'updated_at' => '',
            'edited_by' => '',
            'likes' => [],
            'status' => 'published'
        ];

        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->beginTransaction();

                $stmtT = $pdo->prepare("
                    INSERT INTO weave_forum_topics (id, board_id, title, author_id, author_name, created_at, updated_at, views, is_pinned, is_locked, is_solved, status, last_post_at, last_post_user)
                    VALUES (:id, :board_id, :title, :author_id, :author_name, :created_at, :updated_at, 0, 0, 0, 0, 'published', :last_post_at, :last_post_user)
                ");
                $stmtT->execute([
                    ':id' => $topicId,
                    ':board_id' => $boardId,
                    ':title' => $title,
                    ':author_id' => $user['id'],
                    ':author_name' => $user['username'],
                    ':created_at' => $now,
                    ':updated_at' => $now,
                    ':last_post_at' => $now,
                    ':last_post_user' => $user['username']
                ]);

                $stmtP = $pdo->prepare("
                    INSERT INTO weave_forum_posts (id, topic_id, author_id, author_name, content, created_at, updated_at, edited_by, status)
                    VALUES (:id, :topic_id, :author_id, :author_name, :content, :created_at, '', '', 'published')
                ");
                $stmtP->execute([
                    ':id' => $postId,
                    ':topic_id' => $topicId,
                    ':author_id' => $user['id'],
                    ':author_name' => $user['username'],
                    ':content' => $content,
                    ':created_at' => $now
                ]);

                $pdo->commit();
            } catch (Exception $e) {
                if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
            }
        } else {
            $data = self::loadJsonData();
            $data['topics'][] = $topic;
            $data['posts'][] = $post;
            self::saveJsonData($data);
        }

        // Benutzerstatistiken aktualisieren
        if (class_exists('ForumUserManager')) {
            $u = ForumUserManager::getUserById($user['id']);
            if ($u) {
                $u['topic_count'] = ($u['topic_count'] ?? 0) + 1;
                $u['post_count'] = ($u['post_count'] ?? 0) + 1;
                ForumUserManager::saveUser($u);
            }
        }

        return ['success' => true, 'topic_id' => $topicId, 'post_id' => $postId];
    }

    /**
     * Fügt eine Antwort zu einem existierenden Thema hinzu.
     */
    public static function addPost(string $topicId, string $content, array $user): array {
        $content = trim($content);
        if (strlen($content) < 2) {
            return ['success' => false, 'message' => 'Der Beitrag ist zu kurz.'];
        }

        $topic = self::getTopic($topicId);
        if (!$topic) {
            return ['success' => false, 'message' => 'Thema nicht gefunden.'];
        }
        if (!empty($topic['is_locked'])) {
            return ['success' => false, 'message' => 'Dieses Thema ist geschlossen. Es können keine weiteren Antworten verfasst werden.'];
        }

        $content = self::filterBadWords($content);
        $now = date('Y-m-d H:i:s');
        $postId = 'post_' . bin2hex(random_bytes(6));

        $post = [
            'id' => $postId,
            'topic_id' => $topicId,
            'author_id' => $user['id'],
            'author_name' => $user['username'],
            'content' => $content,
            'created_at' => $now,
            'updated_at' => '',
            'edited_by' => '',
            'status' => 'published'
        ];

        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->beginTransaction();

                $stmtP = $pdo->prepare("
                    INSERT INTO weave_forum_posts (id, topic_id, author_id, author_name, content, created_at, updated_at, edited_by, status)
                    VALUES (:id, :topic_id, :author_id, :author_name, :content, :created_at, '', '', 'published')
                ");
                $stmtP->execute([
                    ':id' => $postId,
                    ':topic_id' => $topicId,
                    ':author_id' => $user['id'],
                    ':author_name' => $user['username'],
                    ':content' => $content,
                    ':created_at' => $now
                ]);

                $stmtT = $pdo->prepare("
                    UPDATE weave_forum_topics 
                    SET last_post_at = :last_post_at, last_post_user = :last_post_user, updated_at = :now
                    WHERE id = :id
                ");
                $stmtT->execute([
                    ':last_post_at' => $now,
                    ':last_post_user' => $user['username'],
                    ':now' => $now,
                    ':id' => $topicId
                ]);

                $pdo->commit();
            } catch (Exception $e) {
                if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
            }
        } else {
            $data = self::loadJsonData();
            $data['posts'][] = $post;
            foreach ($data['topics'] as &$t) {
                if ($t['id'] === $topicId) {
                    $t['last_post_at'] = $now;
                    $t['last_post_user'] = $user['username'];
                    $t['updated_at'] = $now;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        // Benutzer-Postcount
        if (class_exists('ForumUserManager')) {
            $u = ForumUserManager::getUserById($user['id']);
            if ($u) {
                $u['post_count'] = ($u['post_count'] ?? 0) + 1;
                ForumUserManager::saveUser($u);
            }
        }

        // Benachrichtigungen an Abonnenten senden
        self::notifySubscribers($topicId, $topic['title'], $user['username']);

        return ['success' => true, 'post_id' => $postId];
    }

    public static function updatePost(string $postId, string $content, array $editorUser): bool {
        $content = self::filterBadWords(trim($content));
        $now = date('Y-m-d H:i:s');

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    UPDATE weave_forum_posts 
                    SET content = :content, updated_at = :now, edited_by = :editor 
                    WHERE id = :id
                ");
                return $stmt->execute([
                    ':content' => $content,
                    ':now' => $now,
                    ':editor' => $editorUser['username'],
                    ':id' => $postId
                ]);
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        foreach ($data['posts'] as &$p) {
            if ($p['id'] === $postId) {
                $p['content'] = $content;
                $p['updated_at'] = $now;
                $p['edited_by'] = $editorUser['username'];
                return self::saveJsonData($data);
            }
        }
        return false;
    }

    public static function deletePost(string $postId, array $moderator): bool {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("DELETE FROM weave_forum_posts WHERE id = ?");
                $res = $stmt->execute([$postId]);
                self::logModeration('delete_post', $moderator, 'post', $postId, 'Beitrag gelöscht');
                return $res;
            } catch (Exception $e) {
                return false;
            }
        }

        $data = self::loadJsonData();
        $data['posts'] = array_values(array_filter($data['posts'], fn($p) => $p['id'] !== $postId));
        self::logModeration('delete_post', $moderator, 'post', $postId, 'Beitrag gelöscht');
        return self::saveJsonData($data);
    }

    // =========================================================================
    // MODERATIONSFUNKTIONEN (PIN, LOCK, MOVE, DELETE TOPIC, REPORT)
    // =========================================================================

    public static function lockTopic(string $topicId, bool $locked, array $moderator): bool {
        $val = $locked ? 1 : 0;
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("UPDATE weave_forum_topics SET is_locked = ? WHERE id = ?");
                $stmt->execute([$val, $topicId]);
            } catch (Exception $e) { return false; }
        } else {
            $data = self::loadJsonData();
            foreach ($data['topics'] as &$t) {
                if ($t['id'] === $topicId) {
                    $t['is_locked'] = $val;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        self::logModeration($locked ? 'lock_topic' : 'unlock_topic', $moderator, 'topic', $topicId, $locked ? 'Thema gesperrt' : 'Thema entsperrt');
        return true;
    }

    public static function pinTopic(string $topicId, bool $pinned, array $moderator): bool {
        $val = $pinned ? 1 : 0;
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("UPDATE weave_forum_topics SET is_pinned = ? WHERE id = ?");
                $stmt->execute([$val, $topicId]);
            } catch (Exception $e) { return false; }
        } else {
            $data = self::loadJsonData();
            foreach ($data['topics'] as &$t) {
                if ($t['id'] === $topicId) {
                    $t['is_pinned'] = $val;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        self::logModeration($pinned ? 'pin_topic' : 'unpin_topic', $moderator, 'topic', $topicId, $pinned ? 'Thema angepinnt' : 'Thema gelöst');
        return true;
    }

    public static function moveTopic(string $topicId, string $newBoardId, array $moderator): bool {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("UPDATE weave_forum_topics SET board_id = ? WHERE id = ?");
                $stmt->execute([$newBoardId, $topicId]);
            } catch (Exception $e) { return false; }
        } else {
            $data = self::loadJsonData();
            foreach ($data['topics'] as &$t) {
                if ($t['id'] === $topicId) {
                    $t['board_id'] = $newBoardId;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        self::logModeration('move_topic', $moderator, 'topic', $topicId, "Thema verschoben nach Board {$newBoardId}");
        return true;
    }

    public static function deleteTopic(string $topicId, array $moderator): bool {
        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->prepare("DELETE FROM weave_forum_topics WHERE id = ?")->execute([$topicId]);
                $pdo->prepare("DELETE FROM weave_forum_posts WHERE topic_id = ?")->execute([$topicId]);
                $pdo->prepare("DELETE FROM weave_forum_subscriptions WHERE topic_id = ?")->execute([$topicId]);
            } catch (Exception $e) { return false; }
        } else {
            $data = self::loadJsonData();
            $data['topics'] = array_values(array_filter($data['topics'], fn($t) => $t['id'] !== $topicId));
            $data['posts'] = array_values(array_filter($data['posts'], fn($p) => $p['topic_id'] !== $topicId));
            self::saveJsonData($data);
        }

        self::logModeration('delete_topic', $moderator, 'topic', $topicId, 'Thema samt allen Beiträgen gelöscht');
        return true;
    }

    // =========================================================================
    // STATUS [GELÖST] (SOLVED TOPICS)
    // =========================================================================

    public static function toggleSolved(string $topicId, array $user): array {
        $topic = self::getTopic($topicId);
        if (!$topic) {
            return ['success' => false, 'message' => 'Thema nicht gefunden.'];
        }

        $isAuthor = (($topic['author_id'] ?? '') === ($user['id'] ?? ''));
        $isMod = class_exists('ForumUserManager') && ForumUserManager::userCan('moderate', $user);

        if (!$isAuthor && !$isMod) {
            return ['success' => false, 'message' => 'Keine Berechtigung, den Status dieses Themas zu ändern.'];
        }

        $newVal = empty($topic['is_solved']) ? 1 : 0;

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("UPDATE weave_forum_topics SET is_solved = ? WHERE id = ?");
                $stmt->execute([$newVal, $topicId]);
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
            }
        } else {
            $data = self::loadJsonData();
            foreach ($data['topics'] as &$t) {
                if ($t['id'] === $topicId) {
                    $t['is_solved'] = $newVal;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        self::logModeration(
            $newVal ? 'mark_solved' : 'mark_unsolved',
            $user,
            'topic',
            $topicId,
            $newVal ? 'Thema als gelöst markiert' : 'Thema als ungelöst markiert'
        );

        return ['success' => true, 'is_solved' => (bool)$newVal];
    }

    // =========================================================================
    // KUDOS & BEITRAGS-LIKES ("DANKE / HILFREICH")
    // =========================================================================

    public static function getPostLikes(string $postId): array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT user_id FROM weave_forum_likes WHERE post_id = ?");
                $stmt->execute([$postId]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        foreach ($data['posts'] ?? [] as $p) {
            if ($p['id'] === $postId) {
                return is_array($p['likes'] ?? null) ? $p['likes'] : [];
            }
        }
        return [];
    }

    public static function toggleLike(string $postId, string $userId): array {
        if (empty($postId) || empty($userId)) {
            return ['success' => false, 'message' => 'Ungültige Anfrage.'];
        }

        $likes = self::getPostLikes($postId);
        $hasLiked = in_array($userId, $likes, true);

        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                if ($hasLiked) {
                    $stmt = $pdo->prepare("DELETE FROM weave_forum_likes WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$postId, $userId]);
                    $liked = false;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO weave_forum_likes (post_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$postId, $userId]);
                    $liked = true;
                }
                $newLikes = self::getPostLikes($postId);
                return ['success' => true, 'liked' => $liked, 'count' => count($newLikes)];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
            }
        }

        $data = self::loadJsonData();
        $liked = false;
        foreach ($data['posts'] as &$p) {
            if ($p['id'] === $postId) {
                if (!isset($p['likes']) || !is_array($p['likes'])) {
                    $p['likes'] = [];
                }
                if ($hasLiked) {
                    $p['likes'] = array_values(array_diff($p['likes'], [$userId]));
                    $liked = false;
                } else {
                    $p['likes'][] = $userId;
                    $liked = true;
                }
                $count = count($p['likes']);
                self::saveJsonData($data);
                return ['success' => true, 'liked' => $liked, 'count' => $count];
            }
        }

        return ['success' => false, 'message' => 'Beitrag nicht gefunden.'];
    }

    // =========================================================================
    // VOLLTEXT- & SCHNELLSUCHE
    // =========================================================================

    public static function search(string $query, ?string $boardId = null, string $searchIn = 'all', string $sortBy = 'latest', int $limit = 50): array {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return ['topics' => [], 'query' => $q, 'count' => 0];
        }

        $driver = self::getStorageDriver();
        $matchedTopicIds = [];
        $snippets = [];
        $boardFilter = !empty($boardId) ? trim($boardId) : null;

        if ($driver === 'sqlite' || $driver === 'mysql') {
            try {
                $pdo = self::getPdo();
                // 1. Suche in Thementiteln
                $sqlT = "SELECT id, board_id FROM weave_forum_topics WHERE title LIKE :q AND status = 'published'";
                if ($boardFilter) {
                    $sqlT .= " AND board_id = :bid";
                }
                $stmtT = $pdo->prepare($sqlT);
                $paramsT = [':q' => '%' . $q . '%'];
                if ($boardFilter) $paramsT[':bid'] = $boardFilter;
                $stmtT->execute($paramsT);
                foreach ($stmtT->fetchAll() as $row) {
                    $matchedTopicIds[$row['id']] = true;
                }

                // 2. Suche in Beiträgen
                if ($searchIn !== 'titles') {
                    $sqlP = "SELECT p.topic_id, p.content FROM weave_forum_posts p
                             JOIN weave_forum_topics t ON t.id = p.topic_id
                             WHERE p.content LIKE :q AND p.status = 'published' AND t.status = 'published'";
                    if ($boardFilter) {
                        $sqlP .= " AND t.board_id = :bid";
                    }
                    $sqlP .= " LIMIT 150";
                    $stmtP = $pdo->prepare($sqlP);
                    $paramsP = [':q' => '%' . $q . '%'];
                    if ($boardFilter) $paramsP[':bid'] = $boardFilter;
                    $stmtP->execute($paramsP);
                    foreach ($stmtP->fetchAll() as $row) {
                        $tid = $row['topic_id'];
                        $matchedTopicIds[$tid] = true;
                        if (!isset($snippets[$tid])) {
                            $snippets[$tid] = self::extractSnippet($row['content'], $q);
                        }
                    }
                }
            } catch (Exception $e) {
                return ['topics' => [], 'query' => $q, 'count' => 0];
            }
        } else {
            $data = self::loadJsonData();
            foreach ($data['topics'] ?? [] as $t) {
                if (($t['status'] ?? 'published') === 'published' && mb_stripos($t['title'] ?? '', $q) !== false) {
                    if (!$boardFilter || ($t['board_id'] ?? '') === $boardFilter) {
                        $matchedTopicIds[$t['id']] = true;
                    }
                }
            }

            if ($searchIn !== 'titles') {
                $topicBoards = [];
                foreach ($data['topics'] ?? [] as $t) {
                    $topicBoards[$t['id']] = $t['board_id'] ?? '';
                }

                foreach ($data['posts'] ?? [] as $p) {
                    if (($p['status'] ?? 'published') === 'published' && mb_stripos($p['content'] ?? '', $q) !== false) {
                        $tid = $p['topic_id'];
                        if (!$boardFilter || ($topicBoards[$tid] ?? '') === $boardFilter) {
                            $matchedTopicIds[$tid] = true;
                            if (!isset($snippets[$tid])) {
                                $snippets[$tid] = self::extractSnippet($p['content'], $q);
                            }
                        }
                    }
                }
            }
        }

        $results = [];
        foreach (array_keys($matchedTopicIds) as $tid) {
            $topic = self::getTopic($tid);
            if ($topic && ($topic['status'] ?? 'published') === 'published') {
                if ($boardFilter && ($topic['board_id'] ?? '') !== $boardFilter) {
                    continue;
                }
                $board = self::getBoard($topic['board_id']);
                $topic['board_title'] = $board['title'] ?? 'Forum';
                $topic['board_icon'] = $board['icon'] ?? 'forum';
                $topic['reply_count'] = max(0, self::countPosts($tid) - 1);
                $topic['snippet'] = $snippets[$tid] ?? '';
                $results[] = $topic;
            }
        }

        // Sortierung
        if ($sortBy === 'replies') {
            usort($results, fn($a, $b) => ($b['reply_count'] ?? 0) <=> ($a['reply_count'] ?? 0));
        } elseif ($sortBy === 'views') {
            usort($results, fn($a, $b) => ($b['views'] ?? 0) <=> ($a['views'] ?? 0));
        } elseif ($sortBy === 'created') {
            usort($results, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        } else {
            // default 'latest'
            usort($results, fn($a, $b) => strcmp($b['last_post_at'] ?? '', $a['last_post_at'] ?? ''));
        }

        return [
            'topics' => array_slice($results, 0, $limit),
            'query' => $q,
            'board_id' => $boardFilter,
            'count' => count($results)
        ];
    }

    protected static function extractSnippet(string $content, string $query, int $radius = 80): string {
        $pos = mb_stripos($content, $query);
        if ($pos === false) {
            return mb_substr($content, 0, 160) . '...';
        }
        $start = max(0, $pos - $radius);
        $len = mb_strlen($query) + ($radius * 2);
        $snip = mb_substr($content, $start, $len);
        $snip = htmlspecialchars($snip);
        $snip = preg_replace('/(' . preg_quote(htmlspecialchars($query), '/') . ')/iu', '<mark style="background:#fef08a; color:#854d0e; padding:1px 4px; border-radius:3px; font-weight:700;">$1</mark>', $snip);
        return ($start > 0 ? '...' : '') . $snip . '...';
    }

    // =========================================================================
    // MELDUNGEN (REPORTS)
    // =========================================================================

    public static function reportPost(string $postId, array $reporter, string $reason): bool {
        $reason = trim($reason);
        if (empty($reason)) return false;

        $reportId = 'rep_' . bin2hex(random_bytes(6));
        $now = date('Y-m-d H:i:s');

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    INSERT INTO weave_forum_reports (id, post_id, reporter_id, reporter_name, reason, status, created_at)
                    VALUES (:id, :post_id, :reporter_id, :reporter_name, :reason, 'open', :created_at)
                ");
                return $stmt->execute([
                    ':id' => $reportId,
                    ':post_id' => $postId,
                    ':reporter_id' => $reporter['id'],
                    ':reporter_name' => $reporter['username'],
                    ':reason' => $reason,
                    ':created_at' => $now
                ]);
            } catch (Exception $e) { return false; }
        }

        $data = self::loadJsonData();
        $data['reports'][] = [
            'id' => $reportId,
            'post_id' => $postId,
            'reporter_id' => $reporter['id'],
            'reporter_name' => $reporter['username'],
            'reason' => $reason,
            'status' => 'open',
            'created_at' => $now
        ];
        return self::saveJsonData($data);
    }

    public static function getReports(string $status = 'open'): array {
        if (self::isDatabase()) {
            try {
                if ($status === 'all') {
                    $stmt = self::getSqlitePdo()->query("SELECT * FROM weave_forum_reports ORDER BY created_at DESC");
                } else {
                    $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_reports WHERE status = ? ORDER BY created_at DESC");
                    $stmt->execute([$status]);
                }
                return $stmt->fetchAll();
            } catch (Exception $e) { return []; }
        }

        $data = self::loadJsonData();
        $reports = $data['reports'] ?? [];
        if ($status !== 'all') {
            $reports = array_filter($reports, fn($r) => ($r['status'] ?? 'open') === $status);
        }
        return array_values($reports);
    }

    public static function resolveReport(string $reportId, string $status, array $moderator): bool {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("UPDATE weave_forum_reports SET status = ? WHERE id = ?");
                $stmt->execute([$status, $reportId]);
            } catch (Exception $e) { return false; }
        } else {
            $data = self::loadJsonData();
            foreach ($data['reports'] as &$r) {
                if ($r['id'] === $reportId) {
                    $r['status'] = $status;
                    break;
                }
            }
            self::saveJsonData($data);
        }

        self::logModeration('resolve_report', $moderator, 'report', $reportId, "Meldung als '{$status}' markiert");
        return true;
    }

    // =========================================================================
    // AUDIT LOG & MODERATIONS-PROTOKOLL
    // =========================================================================

    public static function logModeration(string $action, array $moderator, string $targetType, string $targetId, string $reason = ''): void {
        $id = 'log_' . bin2hex(random_bytes(6));
        $now = date('Y-m-d H:i:s');

        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    INSERT INTO weave_forum_audit_log (id, moderator_id, moderator_name, action, target_type, target_id, reason, timestamp)
                    VALUES (:id, :mod_id, :mod_name, :action, :type, :target, :reason, :now)
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':mod_id' => $moderator['id'] ?? 'sys',
                    ':mod_name' => $moderator['username'] ?? 'System',
                    ':action' => $action,
                    ':type' => $targetType,
                    ':target' => $targetId,
                    ':reason' => $reason,
                    ':now' => $now
                ]);
            } catch (Exception $e) {}
            return;
        }

        $data = self::loadJsonData();
        if (!isset($data['audit_log'])) $data['audit_log'] = [];
        $data['audit_log'][] = [
            'id' => $id,
            'moderator_id' => $moderator['id'] ?? 'sys',
            'moderator_name' => $moderator['username'] ?? 'System',
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'timestamp' => $now
        ];
        // Begrenzung auf letzte 200 Logs
        if (count($data['audit_log']) > 200) {
            $data['audit_log'] = array_slice($data['audit_log'], -200);
        }
        self::saveJsonData($data);
    }

    public static function getAuditLogs(int $limit = 50): array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT * FROM weave_forum_audit_log ORDER BY timestamp DESC LIMIT ?");
                $stmt->execute([$limit]);
                return $stmt->fetchAll();
            } catch (Exception $e) { return []; }
        }

        $data = self::loadJsonData();
        $logs = $data['audit_log'] ?? [];
        usort($logs, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));
        return array_slice($logs, 0, $limit);
    }

    // =========================================================================
    // ABONNEMENTS & BENACHRICHTIGUNGEN
    // =========================================================================

    public static function isSubscribed(string $userId, string $topicId): bool {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT COUNT(*) FROM weave_forum_subscriptions WHERE user_id = ? AND topic_id = ?");
                $stmt->execute([$userId, $topicId]);
                return ((int)$stmt->fetchColumn()) > 0;
            } catch (Exception $e) { return false; }
        }

        $data = self::loadJsonData();
        foreach ($data['subscriptions'] ?? [] as $s) {
            if (($s['user_id'] ?? '') === $userId && ($s['topic_id'] ?? '') === $topicId) {
                return true;
            }
        }
        return false;
    }

    public static function toggleSubscription(string $userId, string $topicId): bool {
        $sub = self::isSubscribed($userId, $topicId);
        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                if ($sub) {
                    $pdo->prepare("DELETE FROM weave_forum_subscriptions WHERE user_id = ? AND topic_id = ?")->execute([$userId, $topicId]);
                    return false;
                } else {
                    $pdo->prepare("INSERT INTO weave_forum_subscriptions (user_id, topic_id) VALUES (?, ?)")->execute([$userId, $topicId]);
                    return true;
                }
            } catch (Exception $e) { return false; }
        }

        $data = self::loadJsonData();
        if (!isset($data['subscriptions'])) $data['subscriptions'] = [];
        if ($sub) {
            $data['subscriptions'] = array_values(array_filter($data['subscriptions'], fn($s) => !($s['user_id'] === $userId && $s['topic_id'] === $topicId)));
            self::saveJsonData($data);
            return false;
        } else {
            $data['subscriptions'][] = ['user_id' => $userId, 'topic_id' => $topicId];
            self::saveJsonData($data);
            return true;
        }
    }

    protected static function notifySubscribers(string $topicId, string $topicTitle, string $replyAuthor): void {
        if (!class_exists('Mailer') || !class_exists('ForumUserManager')) return;

        $subscriberIds = [];
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("SELECT user_id FROM weave_forum_subscriptions WHERE topic_id = ?");
                $stmt->execute([$topicId]);
                $subscriberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {}
        } else {
            $data = self::loadJsonData();
            foreach ($data['subscriptions'] ?? [] as $s) {
                if (($s['topic_id'] ?? '') === $topicId) {
                    $subscriberIds[] = $s['user_id'];
                }
            }
        }

        foreach ($subscriberIds as $uid) {
            $u = ForumUserManager::getUserById($uid);
            if ($u && !empty($u['notification_email']) && !empty($u['email']) && $u['username'] !== $replyAuthor) {
                $subject = "Neue Antwort im Thema: {$topicTitle}";
                $body = "Hallo {$u['username']},\n\n{$replyAuthor} hat eine neue Antwort im Thema '{$topicTitle}' verfasst.\n\nDu erhältst diese E-Mail, da du dieses Thema abonniert hast.";
                @Mailer::sendMail($u['email'], $subject, $body);
            }
        }
    }

    // =========================================================================
    // TEXT- & SICHERHEITSFILTER (BAD WORDS, FORMATIERUNG)
    // =========================================================================

    public static function filterBadWords(string $text): string {
        $badwords = ['spamlink', 'viagra', 'casino777', 'warez'];
        if (class_exists('PluginManager')) {
            $data = $GLOBALS['data'] ?? [];
            $cfg = PluginManager::getPluginConfig('weave-forum', $data);
            if (!empty($cfg['badwords'])) {
                $customWords = preg_split('/[\r\n,]+/', (string)$cfg['badwords']);
                foreach ($customWords as $cw) {
                    $cw = trim($cw);
                    if ($cw !== '' && !in_array($cw, $badwords, true)) {
                        $badwords[] = $cw;
                    }
                }
            }
        }
        foreach ($badwords as $bw) {
            $text = preg_replace('/\b' . preg_quote($bw, '/') . '\b/iu', '***', $text);
        }
        return $text;
    }

    /**
     * Konvertiert Markdown, mehrzeilige Code-Blöcke und Zitate (Markdown '>' sowie BBCode '[quote]')
     * in sicheren, validen und ansprechend gestalteten HTML-Code.
     */
    public static function renderFormattedContent(string $raw): string {
        if (trim($raw) === '') {
            return '';
        }

        $tokens = [];
        $tokenIdx = 0;

        // 1. Fenced Code Blocks: ```lang ... ``` VOR ALLEM ANDEREN extrahieren
        $text = preg_replace_callback('/```([a-zA-Z0-9_\-\+]*)\r?\n([\s\S]*?)```/m', function($m) use (&$tokens, &$tokenIdx) {
            $lang = !empty(trim($m[1])) ? strtoupper(trim($m[1])) : 'CODE';
            $code = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $ph = "%%%FORUM_CODE_BLOCK_{$tokenIdx}%%%";
            $tokens[$ph] = '<div class="forum-code-wrapper">'
                         . '<div class="forum-code-header">'
                         . '<span class="forum-code-lang">' . htmlspecialchars($lang) . '</span>'
                         . '<button type="button" class="forum-code-copy" onclick="copyForumCode(this)">'
                         . '<span class="material-icons" style="font-size:13px; vertical-align:middle;">content_copy</span> Kopieren'
                         . '</button>'
                         . '</div>'
                         . '<pre class="forum-code-block"><code class="language-' . strtolower($lang) . '">' . $code . '</code></pre>'
                         . '</div>';
            $tokenIdx++;
            return $ph;
        }, $raw);

        // 2. BBCode-Zitate: [quote="User"]Text[/quote]
        $text = preg_replace_callback('/\[quote(?:="([^"]*)")?\]([\s\S]*?)\[\/quote\]/i', function($m) use (&$tokens, &$tokenIdx) {
            $author = !empty($m[1]) ? trim($m[1]) : '';
            $authorHtml = $author !== '' 
                ? '<div class="quote-author"><span class="material-icons text-xs" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>' . htmlspecialchars($author) . '</strong> schrieb:</div>' 
                : '';
            $body = self::renderFormattedContent($m[2]);
            $ph = "%%%FORUM_QUOTE_BLOCK_{$tokenIdx}%%%";
            $tokens[$ph] = '<blockquote class="forum-quote">' . $authorHtml . '<div class="quote-body">' . $body . '</div></blockquote>';
            $tokenIdx++;
            return $ph;
        }, $text);

        // 3. Markdown-Zitate: > Zitatzeile (Blockquotes)
        $text = preg_replace_callback('/((?:^|\r?\n)>[^\r\n]*(?:\r?\n>[^\r\n]*)*)/m', function($m) use (&$tokens, &$tokenIdx) {
            $lines = preg_split('/\r?\n/', trim($m[1]));
            $cleanLines = [];
            $author = '';
            foreach ($lines as $l) {
                $clean = preg_replace('/^>\s?/', '', $l);
                if (empty($author) && preg_match('/^\*\*([^*]+)\*\*\s+schrieb:?$/i', trim($clean), $am)) {
                    $author = $am[1];
                    continue;
                }
                $cleanLines[] = $clean;
            }
            $authorHtml = $author !== '' 
                ? '<div class="quote-author"><span class="material-icons text-xs" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>' . htmlspecialchars($author) . '</strong> schrieb:</div>' 
                : '';
            $bodyText = implode("\n", $cleanLines);
            $bodyHtml = self::renderFormattedContent($bodyText);
            $ph = "%%%FORUM_QUOTE_BLOCK_{$tokenIdx}%%%";
            $tokens[$ph] = '<blockquote class="forum-quote">' . $authorHtml . '<div class="quote-body">' . $bodyHtml . '</div></blockquote>';
            $tokenIdx++;
            return $ph;
        }, $text);

        // 4. HTML maskieren für restlichen Text (XSS Schutz)
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        // 5. Markdown Links: [Linktext](https://example.org)
        $safe = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)\"\']+)(?:\s+"([^"]*)")?\)/i', function($m) {
            $label = $m[1];
            $url = $m[2];
            $title = !empty($m[3]) ? ' title="' . htmlspecialchars($m[3]) . '"' : '';
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer"' . $title . ' style="color:var(--primary, #2e6248); font-weight:600; text-decoration:underline;">' . $label . '</a>';
        }, $safe);

        // 6. Inline Code: `inline code`
        $safe = preg_replace('/`([^`]+)`/', '<code style="background:#f1f5f9; color:#0f172a; padding:2px 6px; border-radius:4px; font-size:0.9em; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; border:1px solid #e2e8f0;">$1</code>', $safe);

        // 7. Formatierungen: Fett & Kursiv
        $safe = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $safe);

        // 8. Zeilenumbrüche
        $safe = nl2br($safe);

        // 9. Tokens wieder einsetzen
        if (!empty($tokens)) {
            $safe = str_replace(array_keys($tokens), array_values($tokens), $safe);
        }

        return $safe;
    }

    // =========================================================================
    // SPEICHER-MIGRATION (JSON ➔ SQLite und SQLite ➔ JSON)
    // =========================================================================

    public static function migrateToJsonToSqlite(): array {
        if (!self::isSqliteSupported()) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_sqlite ist auf diesem Server nicht geladen. Bitte in php.ini "extension=pdo_sqlite" aktivieren.'
            ];
        }

        $data = self::loadJsonData();
        try {
            $pdo = self::getSqlitePdo();

            // Falls das Benutzerverwaltungs-Plugin existiert, auch die Benutzertabelle initialisieren
            if (class_exists('ForumUserManager')) {
                ForumUserManager::getSqlitePdo();
            }

            $pdo->beginTransaction();

            // 1. Kategorien
            $stmtC = $pdo->prepare("REPLACE INTO weave_forum_categories (id, title, description, icon, sort_order, min_role) VALUES (:id, :title, :description, :icon, :sort_order, :min_role)");
            foreach ($data['categories'] ?? [] as $c) {
                $stmtC->execute([
                    ':id' => $c['id'],
                    ':title' => $c['title'],
                    ':description' => $c['description'] ?? '',
                    ':icon' => $c['icon'] ?? 'folder',
                    ':sort_order' => (int)($c['sort_order'] ?? 0),
                    ':min_role' => $c['min_role'] ?? 'public'
                ]);
            }

            // 2. Boards
            $stmtB = $pdo->prepare("REPLACE INTO weave_forum_boards (id, category_id, parent_id, title, description, icon, sort_order, is_locked, min_role_read, min_role_write) VALUES (:id, :category_id, :parent_id, :title, :description, :icon, :sort_order, :is_locked, :min_role_read, :min_role_write)");
            foreach ($data['boards'] ?? [] as $b) {
                $stmtB->execute([
                    ':id' => $b['id'],
                    ':category_id' => $b['category_id'],
                    ':parent_id' => $b['parent_id'] ?? '',
                    ':title' => $b['title'],
                    ':description' => $b['description'] ?? '',
                    ':icon' => $b['icon'] ?? 'forum',
                    ':sort_order' => (int)($b['sort_order'] ?? 0),
                    ':is_locked' => !empty($b['is_locked']) ? 1 : 0,
                    ':min_role_read' => $b['min_role_read'] ?? 'public',
                    ':min_role_write' => $b['min_role_write'] ?? 'forum_user'
                ]);
            }

            // 3. Topics
            $stmtT = $pdo->prepare("REPLACE INTO weave_forum_topics (id, board_id, title, author_id, author_name, created_at, updated_at, views, is_pinned, is_locked, is_solved, status, last_post_at, last_post_user) VALUES (:id, :board_id, :title, :author_id, :author_name, :created_at, :updated_at, :views, :is_pinned, :is_locked, :is_solved, :status, :last_post_at, :last_post_user)");
            foreach ($data['topics'] ?? [] as $t) {
                $stmtT->execute([
                    ':id' => $t['id'],
                    ':board_id' => $t['board_id'],
                    ':title' => $t['title'],
                    ':author_id' => $t['author_id'],
                    ':author_name' => $t['author_name'],
                    ':created_at' => $t['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated_at' => $t['updated_at'] ?? date('Y-m-d H:i:s'),
                    ':views' => (int)($t['views'] ?? 0),
                    ':is_pinned' => !empty($t['is_pinned']) ? 1 : 0,
                    ':is_locked' => !empty($t['is_locked']) ? 1 : 0,
                    ':is_solved' => !empty($t['is_solved']) ? 1 : 0,
                    ':status' => $t['status'] ?? 'published',
                    ':last_post_at' => $t['last_post_at'] ?? date('Y-m-d H:i:s'),
                    ':last_post_user' => $t['last_post_user'] ?? ''
                ]);
            }

            // 4. Posts & Likes
            $stmtP = $pdo->prepare("REPLACE INTO weave_forum_posts (id, topic_id, author_id, author_name, content, created_at, updated_at, edited_by, status) VALUES (:id, :topic_id, :author_id, :author_name, :content, :created_at, :updated_at, :edited_by, :status)");
            $stmtL = $pdo->prepare("INSERT OR IGNORE INTO weave_forum_likes (post_id, user_id) VALUES (:post_id, :user_id)");
            foreach ($data['posts'] ?? [] as $p) {
                $stmtP->execute([
                    ':id' => $p['id'],
                    ':topic_id' => $p['topic_id'],
                    ':author_id' => $p['author_id'],
                    ':author_name' => $p['author_name'],
                    ':content' => $p['content'],
                    ':created_at' => $p['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated_at' => $p['updated_at'] ?? '',
                    ':edited_by' => $p['edited_by'] ?? '',
                    ':status' => $p['status'] ?? 'published'
                ]);

                if (!empty($p['likes']) && is_array($p['likes'])) {
                    foreach ($p['likes'] as $uid) {
                        $stmtL->execute([':post_id' => $p['id'], ':user_id' => $uid]);
                    }
                }
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Foreninhalte (Kategorien, Boards, Themen, Beiträge, Likes) erfolgreich nach SQLite migriert!'];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    public static function migrateToJsonToMysql(): array {
        if (!self::isMysqlSupported()) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht geladen.'
            ];
        }

        $data = self::loadJsonData();
        try {
            $pdo = self::getMysqlPdo();

            // Falls das Benutzerverwaltungs-Plugin existiert, auch die Benutzertabelle initialisieren & migrieren
            if (class_exists('ForumUserManager')) {
                ForumUserManager::getMysqlPdo();
                if (method_exists('ForumUserManager', 'migrateToJsonToMysql')) {
                    ForumUserManager::migrateToJsonToMysql();
                }
            }

            $pdo->beginTransaction();

            // 1. Kategorien
            $stmtC = $pdo->prepare("REPLACE INTO weave_forum_categories (id, title, description, icon, sort_order, min_role) VALUES (:id, :title, :description, :icon, :sort_order, :min_role)");
            foreach ($data['categories'] ?? [] as $c) {
                $stmtC->execute([
                    ':id' => $c['id'],
                    ':title' => $c['title'],
                    ':description' => $c['description'] ?? '',
                    ':icon' => $c['icon'] ?? 'folder',
                    ':sort_order' => (int)($c['sort_order'] ?? 0),
                    ':min_role' => $c['min_role'] ?? 'public'
                ]);
            }

            // 2. Boards
            $stmtB = $pdo->prepare("REPLACE INTO weave_forum_boards (id, category_id, parent_id, title, description, icon, sort_order, is_locked, min_role_read, min_role_write) VALUES (:id, :category_id, :parent_id, :title, :description, :icon, :sort_order, :is_locked, :min_role_read, :min_role_write)");
            foreach ($data['boards'] ?? [] as $b) {
                $stmtB->execute([
                    ':id' => $b['id'],
                    ':category_id' => $b['category_id'],
                    ':parent_id' => $b['parent_id'] ?? '',
                    ':title' => $b['title'],
                    ':description' => $b['description'] ?? '',
                    ':icon' => $b['icon'] ?? 'forum',
                    ':sort_order' => (int)($b['sort_order'] ?? 0),
                    ':is_locked' => !empty($b['is_locked']) ? 1 : 0,
                    ':min_role_read' => $b['min_role_read'] ?? 'public',
                    ':min_role_write' => $b['min_role_write'] ?? 'forum_user'
                ]);
            }

            // 3. Topics
            $stmtT = $pdo->prepare("REPLACE INTO weave_forum_topics (id, board_id, title, author_id, author_name, created_at, updated_at, views, is_pinned, is_locked, is_solved, status, last_post_at, last_post_user) VALUES (:id, :board_id, :title, :author_id, :author_name, :created_at, :updated_at, :views, :is_pinned, :is_locked, :is_solved, :status, :last_post_at, :last_post_user)");
            foreach ($data['topics'] ?? [] as $t) {
                $stmtT->execute([
                    ':id' => $t['id'],
                    ':board_id' => $t['board_id'],
                    ':title' => $t['title'],
                    ':author_id' => $t['author_id'],
                    ':author_name' => $t['author_name'],
                    ':created_at' => $t['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated_at' => $t['updated_at'] ?? date('Y-m-d H:i:s'),
                    ':views' => (int)($t['views'] ?? 0),
                    ':is_pinned' => !empty($t['is_pinned']) ? 1 : 0,
                    ':is_locked' => !empty($t['is_locked']) ? 1 : 0,
                    ':is_solved' => !empty($t['is_solved']) ? 1 : 0,
                    ':status' => $t['status'] ?? 'published',
                    ':last_post_at' => $t['last_post_at'] ?? date('Y-m-d H:i:s'),
                    ':last_post_user' => $t['last_post_user'] ?? ''
                ]);
            }

            // 4. Posts & Likes
            $stmtP = $pdo->prepare("REPLACE INTO weave_forum_posts (id, topic_id, author_id, author_name, content, created_at, updated_at, edited_by, status) VALUES (:id, :topic_id, :author_id, :author_name, :content, :created_at, :updated_at, :edited_by, :status)");
            $stmtL = $pdo->prepare("INSERT IGNORE INTO weave_forum_likes (post_id, user_id) VALUES (:post_id, :user_id)");
            foreach ($data['posts'] ?? [] as $p) {
                $stmtP->execute([
                    ':id' => $p['id'],
                    ':topic_id' => $p['topic_id'],
                    ':author_id' => $p['author_id'],
                    ':author_name' => $p['author_name'],
                    ':content' => $p['content'],
                    ':created_at' => $p['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated_at' => $p['updated_at'] ?? '',
                    ':edited_by' => $p['edited_by'] ?? '',
                    ':status' => $p['status'] ?? 'published'
                ]);

                if (!empty($p['likes']) && is_array($p['likes'])) {
                    foreach ($p['likes'] as $uid) {
                        $stmtL->execute([':post_id' => $p['id'], ':user_id' => $uid]);
                    }
                }
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Foreninhalte (Kategorien, Boards, Themen, Beiträge, Likes) erfolgreich nach MySQL migriert!'];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    public static function migrateMysqlToJson(): array {
        try {
            $pdo = self::getMysqlPdo();
            $posts = $pdo->query("SELECT * FROM weave_forum_posts ORDER BY created_at ASC")->fetchAll();
            $likesMap = [];
            try {
                $likes = $pdo->query("SELECT post_id, user_id FROM weave_forum_likes")->fetchAll();
                foreach ($likes as $l) {
                    $likesMap[$l['post_id']][] = $l['user_id'];
                }
            } catch (Exception $e) {}

            foreach ($posts as &$p) {
                $p['likes'] = $likesMap[$p['id']] ?? [];
            }

            $data = [
                'categories' => $pdo->query("SELECT * FROM weave_forum_categories ORDER BY sort_order ASC")->fetchAll(),
                'boards' => $pdo->query("SELECT * FROM weave_forum_boards ORDER BY sort_order ASC")->fetchAll(),
                'topics' => $pdo->query("SELECT * FROM weave_forum_topics ORDER BY last_post_at DESC")->fetchAll(),
                'posts' => $posts,
                'reports' => $pdo->query("SELECT * FROM weave_forum_reports ORDER BY created_at DESC")->fetchAll(),
                'subscriptions' => $pdo->query("SELECT * FROM weave_forum_subscriptions")->fetchAll(),
                'audit_log' => $pdo->query("SELECT * FROM weave_forum_audit_log ORDER BY timestamp DESC LIMIT 200")->fetchAll()
            ];

            if (!self::saveJsonData($data)) {
                return ['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei data/forum_data.json.'];
            }

            return ['success' => true, 'message' => 'Foreninhalte erfolgreich nach data/forum_data.json exportiert!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    /**
     * Formatiert einen Zeitstempel in eine lesbare relative Zeitangabe (z. B. "vor 3 Stunden").
     */
    public static function formatRelativeTime(?string $datetime): string {
        if (empty($datetime)) return 'nie';
        $ts = strtotime($datetime);
        if (!$ts) return (string)$datetime;
        $diff = time() - $ts;
        if ($diff < 0) $diff = 0;

        if ($diff < 60) {
            return 'gerade eben';
        }
        if ($diff < 3600) {
            $mins = max(1, (int)floor($diff / 60));
            return 'vor ' . $mins . ' Min.';
        }
        if ($diff < 86400) {
            $hrs = (int)floor($diff / 3600);
            return 'vor ' . $hrs . ' Std.';
        }
        if ($diff < 172800) {
            return 'gestern um ' . date('H:i', $ts);
        }
        if ($diff < 604800) {
            $days = (int)floor($diff / 86400);
            return 'vor ' . $days . ' Tagen';
        }
        if ($diff < 2592000) {
            $weeks = (int)floor($diff / 604800);
            return 'vor ' . $weeks . ' ' . ($weeks === 1 ? 'Woche' : 'Wochen');
        }
        if ($diff < 31536000) {
            $months = (int)floor($diff / 2592000);
            return 'vor ' . $months . ' ' . ($months === 1 ? 'Monat' : 'Monaten');
        }
        $years = (int)floor($diff / 31536000);
        return 'vor ' . $years . ' ' . ($years === 1 ? 'Jahr' : 'Jahren');
    }

    /**
     * Zählt alle Beiträge innerhalb eines Boards.
     */
    public static function countBoardPosts(string $boardId): int {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    SELECT COUNT(p.id) 
                    FROM weave_forum_posts p 
                    INNER JOIN weave_forum_topics t ON p.topic_id = t.id 
                    WHERE t.board_id = ? AND p.status = 'published'
                ");
                $stmt->execute([$boardId]);
                return (int)$stmt->fetchColumn();
            } catch (Exception $e) {
                return 0;
            }
        }

        $data = self::loadJsonData();
        $boardTopicIds = [];
        foreach ($data['topics'] ?? [] as $t) {
            if (($t['board_id'] ?? '') === $boardId && ($t['status'] ?? 'published') === 'published') {
                $boardTopicIds[$t['id']] = true;
            }
        }
        if (empty($boardTopicIds)) return 0;

        $count = 0;
        foreach ($data['posts'] ?? [] as $p) {
            if (isset($boardTopicIds[$p['topic_id'] ?? '']) && ($p['status'] ?? 'published') === 'published') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Zählt alle Themen innerhalb einer gesamten Kategorie.
     */
    public static function countCategoryTopics(string $categoryId): int {
        $boards = self::getBoards();
        $total = 0;
        foreach ($boards as $b) {
            if (($b['category_id'] ?? '') === $categoryId) {
                $total += self::countTopics($b['id']);
            }
        }
        return $total;
    }

    /**
     * Zählt alle Beiträge innerhalb einer gesamten Kategorie.
     */
    public static function countCategoryPosts(string $categoryId): int {
        $boards = self::getBoards();
        $total = 0;
        foreach ($boards as $b) {
            if (($b['category_id'] ?? '') === $categoryId) {
                $total += self::countBoardPosts($b['id']);
            }
        }
        return $total;
    }

    /**
     * Liefert die globalen Forenstatistiken für das Statistik-Widget.
     */
    public static function getGlobalStats(): array {
        $todayStart = date('Y-m-d 00:00:00');
        $yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));

        $stats = [
            'total_categories' => count(self::getCategories()),
            'total_boards'     => count(self::getBoards()),
            'total_topics'     => 0,
            'total_posts'      => 0,
            'topics_today'     => 0,
            'posts_today'      => 0,
            'topics_yesterday' => 0,
            'posts_yesterday'  => 0,
            'total_members'    => 0,
            'latest_member'    => 'Keines'
        ];

        if (self::isDatabase()) {
            try {
                $pdo = self::getSqlitePdo();
                $stats['total_topics'] = (int)$pdo->query("SELECT COUNT(*) FROM weave_forum_topics WHERE status = 'published'")->fetchColumn();
                $stats['total_posts'] = (int)$pdo->query("SELECT COUNT(*) FROM weave_forum_posts WHERE status = 'published'")->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM weave_forum_topics WHERE status = 'published' AND created_at >= ?");
                $stmt->execute([$todayStart]);
                $stats['topics_today'] = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM weave_forum_posts WHERE status = 'published' AND created_at >= ?");
                $stmt->execute([$todayStart]);
                $stats['posts_today'] = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM weave_forum_topics WHERE status = 'published' AND created_at >= ? AND created_at <= ?");
                $stmt->execute([$yesterdayStart, $yesterdayEnd]);
                $stats['topics_yesterday'] = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM weave_forum_posts WHERE status = 'published' AND created_at >= ? AND created_at <= ?");
                $stmt->execute([$yesterdayStart, $yesterdayEnd]);
                $stats['posts_yesterday'] = (int)$stmt->fetchColumn();
            } catch (Exception $e) {}
        } else {
            $data = self::loadJsonData();
            $topics = array_filter($data['topics'] ?? [], fn($t) => ($t['status'] ?? 'published') === 'published');
            $posts = array_filter($data['posts'] ?? [], fn($p) => ($p['status'] ?? 'published') === 'published');

            $stats['total_topics'] = count($topics);
            $stats['total_posts'] = count($posts);

            foreach ($topics as $t) {
                $cat = $t['created_at'] ?? '';
                if ($cat >= $todayStart) {
                    $stats['topics_today']++;
                } elseif ($cat >= $yesterdayStart && $cat <= $yesterdayEnd) {
                    $stats['topics_yesterday']++;
                }
            }

            foreach ($posts as $p) {
                $cat = $p['created_at'] ?? '';
                if ($cat >= $todayStart) {
                    $stats['posts_today']++;
                } elseif ($cat >= $yesterdayStart && $cat <= $yesterdayEnd) {
                    $stats['posts_yesterday']++;
                }
            }
        }

        if (class_exists('ForumUserManager')) {
            $users = ForumUserManager::getAllUsers();
            $stats['total_members'] = count($users);
            if (!empty($users)) {
                $sorted = $users;
                usort($sorted, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
                $stats['latest_member'] = $sorted[0]['username'] ?? 'Keines';
            }
        }

        return $stats;
    }

    /**
     * Liefert die neuesten aktiven Themen für den Reiter "Aktuelle Themen".
     */
    public static function getRecentTopics(int $limit = 25): array {
        if (self::isDatabase()) {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    SELECT * FROM weave_forum_topics 
                    WHERE status = 'published' 
                    ORDER BY is_pinned DESC, last_post_at DESC 
                    LIMIT ?
                ");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        $data = self::loadJsonData();
        $topics = array_filter($data['topics'] ?? [], fn($t) => ($t['status'] ?? 'published') === 'published');
        usort($topics, function($a, $b) {
            $pinnedA = !empty($a['is_pinned']) ? 1 : 0;
            $pinnedB = !empty($b['is_pinned']) ? 1 : 0;
            if ($pinnedA !== $pinnedB) {
                return $pinnedB <=> $pinnedA;
            }
            return strcmp($b['last_post_at'] ?? '', $a['last_post_at'] ?? '');
        });
        return array_slice($topics, 0, $limit);
    }
}
