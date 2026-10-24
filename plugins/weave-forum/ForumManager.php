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
     * Ermittelt den konfigurierten Speichertreiber ('json' oder 'sqlite').
     */
    public static function getStorageDriver(): string {
        if (class_exists('PluginManager')) {
            $data = $GLOBALS['data'] ?? [];
            if (!empty($data)) {
                $cfg = PluginManager::getPluginConfig('weave-forum', $data);
                if (!empty($cfg['storage_driver'])) {
                    return $cfg['storage_driver'] === 'sqlite' ? 'sqlite' : 'json';
                }
            }
        }
        return 'json';
    }

    /**
     * Prüft, ob die SQLite-Erweiterung in PHP geladen ist.
     */
    public static function isSqliteSupported(): bool {
        return extension_loaded('pdo_sqlite');
    }

    /**
     * Stellt eine SQLite-Verbindung her und initialisiert Tabellen.
     */
    protected static function getSqlitePdo(): PDO {
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
        ");

        self::$sqlitePdo = $pdo;
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
        if (!is_array($data) || empty($data['categories'])) {
            $default = self::getDefaultData();
            self::saveJsonData($default);
            return $default;
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
        if (self::getStorageDriver() === 'sqlite') {
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

        if (self::getStorageDriver() === 'sqlite') {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    INSERT OR REPLACE INTO weave_forum_categories (id, title, description, icon, sort_order, min_role)
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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

    public static function saveBoard(array $board): bool {
        if (empty($board['id'])) {
            $board['id'] = 'board_' . bin2hex(random_bytes(4));
        }

        if (self::getStorageDriver() === 'sqlite') {
            try {
                $stmt = self::getSqlitePdo()->prepare("
                    INSERT OR REPLACE INTO weave_forum_boards (
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
            'status' => 'published'
        ];

        if (self::getStorageDriver() === 'sqlite') {
            try {
                $pdo = self::getSqlitePdo();
                $pdo->beginTransaction();

                $stmtT = $pdo->prepare("
                    INSERT INTO weave_forum_topics (id, board_id, title, author_id, author_name, created_at, updated_at, views, is_pinned, is_locked, status, last_post_at, last_post_user)
                    VALUES (:id, :board_id, :title, :author_id, :author_name, :created_at, :updated_at, 0, 0, 0, 'published', :last_post_at, :last_post_user)
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

        if (self::getStorageDriver() === 'sqlite') {
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

        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
    // MELDUNGEN (REPORTS)
    // =========================================================================

    public static function reportPost(string $postId, array $reporter, string $reason): bool {
        $reason = trim($reason);
        if (empty($reason)) return false;

        $reportId = 'rep_' . bin2hex(random_bytes(6));
        $now = date('Y-m-d H:i:s');

        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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

        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        if (self::getStorageDriver() === 'sqlite') {
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
        foreach ($badwords as $bw) {
            $text = preg_replace('/\b' . preg_quote($bw, '/') . '\b/iu', '***', $text);
        }
        return $text;
    }

    /**
     * Konvertiert Markdown und BBCode-Zitate für die sichere HTML-Ausgabe.
     */
    public static function renderFormattedContent(string $raw): string {
        // BBCode Zitate: [quote="User"]Text[/quote]
        $content = preg_replace_callback('/\[quote(?:="([^"]*)")?\](.*?)\[\/quote\]/is', function($m) {
            $author = !empty($m[1]) ? htmlspecialchars($m[1]) : 'Zitat';
            $quoteText = htmlspecialchars($m[2]);
            return "<blockquote class=\"forum-quote\"><div class=\"quote-author\"><span class=\"material-icons text-xs\">format_quote</span> <strong>{$author}</strong> schrieb:</div><div class=\"quote-body\">" . nl2br($quoteText) . "</div></blockquote>";
        }, $raw);

        // WeaveCMS Markdown-Parser nutzen falls verfügbar
        if (class_exists('MarkdownParser')) {
            return MarkdownParser::parse($content);
        }

        return nl2br(htmlspecialchars($content));
    }

    // =========================================================================
    // SPEICHER-MIGRATION (JSON ➔ SQLite und SQLite ➔ JSON)
    // =========================================================================

    public static function migrateToJsonToSqlite(): array {
        $data = self::loadJsonData();
        try {
            $pdo = self::getSqlitePdo();
            $pdo->beginTransaction();

            // 1. Kategorien
            $stmtC = $pdo->prepare("INSERT OR REPLACE INTO weave_forum_categories (id, title, description, icon, sort_order, min_role) VALUES (:id, :title, :description, :icon, :sort_order, :min_role)");
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
            $stmtB = $pdo->prepare("INSERT OR REPLACE INTO weave_forum_boards (id, category_id, parent_id, title, description, icon, sort_order, is_locked, min_role_read, min_role_write) VALUES (:id, :category_id, :parent_id, :title, :description, :icon, :sort_order, :is_locked, :min_role_read, :min_role_write)");
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
            $stmtT = $pdo->prepare("INSERT OR REPLACE INTO weave_forum_topics (id, board_id, title, author_id, author_name, created_at, updated_at, views, is_pinned, is_locked, status, last_post_at, last_post_user) VALUES (:id, :board_id, :title, :author_id, :author_name, :created_at, :updated_at, :views, :is_pinned, :is_locked, :status, :last_post_at, :last_post_user)");
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
                    ':status' => $t['status'] ?? 'published',
                    ':last_post_at' => $t['last_post_at'] ?? date('Y-m-d H:i:s'),
                    ':last_post_user' => $t['last_post_user'] ?? ''
                ]);
            }

            // 4. Posts
            $stmtP = $pdo->prepare("INSERT OR REPLACE INTO weave_forum_posts (id, topic_id, author_id, author_name, content, created_at, updated_at, edited_by, status) VALUES (:id, :topic_id, :author_id, :author_name, :content, :created_at, :updated_at, :edited_by, :status)");
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
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Foreninhalte (Kategorien, Boards, Themen, Beiträge) erfolgreich nach SQLite migriert!'];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }

    public static function migrateSqliteToJson(): array {
        try {
            $pdo = self::getSqlitePdo();
            $data = [
                'categories' => $pdo->query("SELECT * FROM weave_forum_categories ORDER BY sort_order ASC")->fetchAll(),
                'boards' => $pdo->query("SELECT * FROM weave_forum_boards ORDER BY sort_order ASC")->fetchAll(),
                'topics' => $pdo->query("SELECT * FROM weave_forum_topics ORDER BY last_post_at DESC")->fetchAll(),
                'posts' => $pdo->query("SELECT * FROM weave_forum_posts ORDER BY created_at ASC")->fetchAll(),
                'reports' => $pdo->query("SELECT * FROM weave_forum_reports ORDER BY created_at DESC")->fetchAll(),
                'subscriptions' => $pdo->query("SELECT * FROM weave_forum_subscriptions")->fetchAll(),
                'audit_log' => $pdo->query("SELECT * FROM weave_forum_audit_log ORDER BY timestamp DESC LIMIT 200")->fetchAll()
            ];

            if (!self::saveJsonData($data)) {
                return ['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei data/forum_data.json.'];
            }

            return ['success' => true, 'message' => 'Foreninhalte erfolgreich nach data/forum_data.json migriert!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Migrationsfehler: ' . $e->getMessage()];
        }
    }
}
