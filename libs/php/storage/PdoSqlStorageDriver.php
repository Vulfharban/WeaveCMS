<?php
/**
 * ============================================================================
 * WeaveCMS Relational SQL Storage Driver (MySQL / MariaDB)
 * ============================================================================
 * Speichert Datenstrukturen in relationalen Tabellen via PDO mit Transaktionsschutz.
 *
 * Schema (Hybrid, verlustfrei zum JSON-Format kompatibel):
 *  - {prefix}config      Schluessel/Wert-Bereiche: settings, texts, seo, texts_i18n
 *  - {prefix}<bereich>   Eine Tabelle je Inhaltsbereich (gallery, courses, pages, ...)
 *                        mit lesbaren Spalten (item_id, title, status, sort_pos)
 *                        und der kompletten Datensatz-Struktur in "data" (JSON)
 *  - {prefix}lists       Einfache Textlisten (categories, past_courses)
 *  - {prefix}documents   Auffangtabelle fuer unbekannte Schluessel (z. B. Plugins)
 *                        sowie Altbestand aelterer Versionen (wird beim Speichern
 *                        automatisch in die neuen Tabellen uebernommen)
 *
 * Die Schnittstelle (loadData/saveData) liefert exakt dasselbe Array wie der
 * JSON-Treiber, der restliche CMS-Code muss daher nicht zwischen Speicherarten
 * unterscheiden.
 */

require_once __DIR__ . '/StorageDriverInterface.php';

class PdoSqlStorageDriver implements StorageDriverInterface {
    /** Assoziative Bereiche -> Tabelle {prefix}config */
    protected const CONFIG_SECTIONS = ['settings', 'texts', 'seo', 'texts_i18n'];

    /** Listen von Datensaetzen -> eigene Tabelle je Bereich */
    protected const COLLECTIONS = [
        'gallery', 'courses', 'sections', 'blog', 'pages',
        'faqs', 'team', 'downloads', 'pricing', 'users'
    ];

    /** Listen einfacher Textwerte -> Tabelle {prefix}lists */
    protected const SCALAR_LISTS = ['categories', 'past_courses'];

    protected ?PDO $pdo = null;
    protected string $host;
    protected int $port;
    protected string $database;
    protected string $username;
    protected string $password;
    protected string $prefix;
    protected string $charset;
    protected ?string $lastError = null;
    protected bool $initialized = false;

    public function __construct(array $config = []) {
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = (int)($config['port'] ?? 3306);
        $this->database = $config['database'] ?? 'weavecms';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';
        $this->prefix = $config['prefix'] ?? 'weave_';
        $this->charset = $config['charset'] ?? 'utf8mb4';
    }

    /**
     * Stellt die PDO-Verbindung her.
     * @return PDO
     * @throws PDOException
     */
    public function getPdo(): PDO {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        }
        return $this->pdo;
    }

    protected function t(string $name): string {
        return '`' . $this->prefix . $name . '`';
    }

    public function init(): bool {
        if ($this->initialized) {
            return true;
        }
        try {
            $pdo = $this->getPdo();
            $engine = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->t('documents')} (
                `doc_key` VARCHAR(64) NOT NULL PRIMARY KEY,
                `doc_value` LONGTEXT NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) {$engine}");

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->t('config')} (
                `section` VARCHAR(32) NOT NULL,
                `cfg_key` VARCHAR(128) NOT NULL,
                `cfg_value` LONGTEXT NOT NULL,
                `sort_pos` INT NOT NULL DEFAULT 0,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`section`, `cfg_key`)
            ) {$engine}");

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->t('lists')} (
                `list_name` VARCHAR(32) NOT NULL,
                `sort_pos` INT NOT NULL,
                `item_value` TEXT NOT NULL,
                PRIMARY KEY (`list_name`, `sort_pos`)
            ) {$engine}");

            foreach (self::COLLECTIONS as $name) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->t($name)} (
                    `row_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `sort_pos` INT NOT NULL DEFAULT 0,
                    `item_id` VARCHAR(64) NULL,
                    `title` VARCHAR(255) NULL,
                    `status` VARCHAR(32) NULL,
                    `data` LONGTEXT NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    KEY `idx_item_id` (`item_id`),
                    KEY `idx_sort_pos` (`sort_pos`)
                ) {$engine}");
            }

            $this->initialized = true;
            return true;
        } catch (Exception $e) {
            $this->lastError = 'SQL-Fehler bei der Initialisierung: ' . $e->getMessage();
            error_log('PdoSqlStorageDriver init error: ' . $e->getMessage());
            return false;
        }
    }

    protected function encode($value): string {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function loadData(): array {
        try {
            if (!$this->init()) {
                return [];
            }
            $pdo = $this->getPdo();
            $data = [];

            // 1. Auffangtabelle / Altbestand
            $docs = [];
            foreach ($pdo->query("SELECT `doc_key`, `doc_value` FROM {$this->t('documents')}")->fetchAll() as $row) {
                $val = json_decode($row['doc_value'], true);
                $docs[$row['doc_key']] = ($val !== null) ? $val : $row['doc_value'];
            }

            // 2. Konfigurationsbereiche
            $config = [];
            foreach ($pdo->query("SELECT `section`, `cfg_key`, `cfg_value` FROM {$this->t('config')} ORDER BY `section`, `sort_pos`")->fetchAll() as $row) {
                $config[$row['section']][$row['cfg_key']] = json_decode($row['cfg_value'], true);
            }
            // Marker: Schluessel, die bewusst leer gespeichert wurden (leere Liste statt fehlendem Schluessel)
            $empty = $config['_empty'] ?? [];
            unset($config['_empty']);
            foreach (self::CONFIG_SECTIONS as $section) {
                if (isset($config[$section])) {
                    $data[$section] = $config[$section];
                    unset($docs[$section]);
                } elseif (isset($empty[$section]) && !array_key_exists($section, $docs)) {
                    $data[$section] = [];
                }
            }

            // 3. Textlisten
            $lists = [];
            foreach ($pdo->query("SELECT `list_name`, `item_value` FROM {$this->t('lists')} ORDER BY `list_name`, `sort_pos`")->fetchAll() as $row) {
                $lists[$row['list_name']][] = $row['item_value'];
            }
            foreach (self::SCALAR_LISTS as $name) {
                if (isset($lists[$name])) {
                    $data[$name] = $lists[$name];
                    unset($docs[$name]);
                } elseif (isset($empty[$name]) && !array_key_exists($name, $docs)) {
                    $data[$name] = [];
                }
            }

            // 4. Datensatz-Tabellen
            foreach (self::COLLECTIONS as $name) {
                $items = [];
                foreach ($pdo->query("SELECT `data` FROM {$this->t($name)} ORDER BY `sort_pos`, `row_id`")->fetchAll() as $row) {
                    $items[] = json_decode($row['data'], true);
                }
                if (!empty($items)) {
                    $data[$name] = $items;
                    unset($docs[$name]);
                } elseif (isset($empty[$name]) && !array_key_exists($name, $docs)) {
                    $data[$name] = [];
                }
            }

            // 5. Uebrige Schluessel (Plugins, Altbestand)
            foreach ($docs as $key => $value) {
                $data[$key] = $value;
            }

            return $data;
        } catch (Exception $e) {
            $this->lastError = 'SQL-Fehler beim Laden: ' . $e->getMessage();
            error_log('PdoSqlStorageDriver loadData error: ' . $e->getMessage());
            return [];
        }
    }

    public function saveData(array $data): bool {
        try {
            if (!$this->init()) {
                return false;
            }
            $pdo = $this->getPdo();
            $pdo->beginTransaction();

            foreach ($data as $key => $value) {
                $key = (string)$key;
                // Bisherigen Speicherort des Schluessels bereinigen
                $this->clearKey($key);

                if (in_array($key, self::CONFIG_SECTIONS, true) && $this->isStringKeyMap($value)) {
                    $this->saveConfig($key, $value);
                } elseif (in_array($key, self::SCALAR_LISTS, true) && $this->isScalarList($value)) {
                    $this->saveScalarList($key, $value);
                } elseif (in_array($key, self::COLLECTIONS, true) && $this->isRecordList($value)) {
                    $this->saveCollection($key, $value);
                } else {
                    $this->saveDocument($key, $value);
                }

                // Leeren, bekannten Bereich als "vorhanden" markieren
                if (is_array($value) && empty($value) && $this->isKnownKey($key)) {
                    $this->getPdo()->prepare("INSERT INTO {$this->t('config')} (`section`, `cfg_key`, `cfg_value`) VALUES ('_empty', :k, 'true')")
                        ->execute([':k' => $key]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->lastError = 'SQL-Fehler beim Speichern: ' . $e->getMessage();
            error_log('PdoSqlStorageDriver saveData error: ' . $e->getMessage());
            return false;
        }
    }

    protected function isKnownKey(string $key): bool {
        return in_array($key, self::CONFIG_SECTIONS, true)
            || in_array($key, self::SCALAR_LISTS, true)
            || in_array($key, self::COLLECTIONS, true);
    }

    protected function isStringKeyMap($value): bool {
        if (!is_array($value) || empty($value)) {
            return is_array($value);
        }
        foreach (array_keys($value) as $k) {
            if (!is_string($k) || $k === '' || strlen($k) > 128) {
                return false;
            }
        }
        return true;
    }

    protected function isScalarList($value): bool {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $v) {
            if (!is_string($v)) {
                return false;
            }
        }
        return true;
    }

    protected function isRecordList($value): bool {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $v) {
            if (!is_array($v)) {
                return false;
            }
        }
        return true;
    }

    protected function clearKey(string $key): void {
        $pdo = $this->getPdo();
        $pdo->prepare("DELETE FROM {$this->t('documents')} WHERE `doc_key` = :k")->execute([':k' => $key]);
        $pdo->prepare("DELETE FROM {$this->t('config')} WHERE `section` = '_empty' AND `cfg_key` = :k")->execute([':k' => $key]);
        if (in_array($key, self::CONFIG_SECTIONS, true)) {
            $pdo->prepare("DELETE FROM {$this->t('config')} WHERE `section` = :k")->execute([':k' => $key]);
        }
        if (in_array($key, self::SCALAR_LISTS, true)) {
            $pdo->prepare("DELETE FROM {$this->t('lists')} WHERE `list_name` = :k")->execute([':k' => $key]);
        }
        if (in_array($key, self::COLLECTIONS, true)) {
            $pdo->exec("DELETE FROM {$this->t($key)}");
        }
    }

    protected function saveDocument(string $key, $value): void {
        $this->getPdo()->prepare("INSERT INTO {$this->t('documents')} (`doc_key`, `doc_value`) VALUES (:k, :v)")
            ->execute([':k' => $key, ':v' => $this->encode($value)]);
    }

    protected function saveConfig(string $section, array $map): void {
        $stmt = $this->getPdo()->prepare("INSERT INTO {$this->t('config')} (`section`, `cfg_key`, `cfg_value`, `sort_pos`) VALUES (:s, :k, :v, :p)");
        $pos = 0;
        foreach ($map as $k => $v) {
            $stmt->execute([':s' => $section, ':k' => (string)$k, ':v' => $this->encode($v), ':p' => $pos++]);
        }
    }

    protected function saveScalarList(string $name, array $list): void {
        $stmt = $this->getPdo()->prepare("INSERT INTO {$this->t('lists')} (`list_name`, `sort_pos`, `item_value`) VALUES (:n, :p, :v)");
        foreach ($list as $pos => $v) {
            $stmt->execute([':n' => $name, ':p' => $pos, ':v' => $v]);
        }
    }

    protected function saveCollection(string $name, array $items): void {
        $stmt = $this->getPdo()->prepare("INSERT INTO {$this->t($name)} (`sort_pos`, `item_id`, `title`, `status`, `data`) VALUES (:p, :i, :t, :s, :d)");
        foreach ($items as $pos => $item) {
            $stmt->execute([
                ':p' => $pos,
                ':i' => isset($item['id']) && is_scalar($item['id']) ? mb_substr((string)$item['id'], 0, 64) : null,
                ':t' => $this->extractTitle($item),
                ':s' => $this->extractStatus($item),
                ':d' => $this->encode($item)
            ]);
        }
    }

    protected function extractTitle(array $item): ?string {
        foreach (['title', 'name', 'question', 'username'] as $field) {
            if (isset($item[$field]) && is_scalar($item[$field]) && $item[$field] !== '') {
                return mb_substr((string)$item[$field], 0, 255);
            }
        }
        return null;
    }

    protected function extractStatus(array $item): ?string {
        if (isset($item['status']) && is_scalar($item['status'])) {
            return mb_substr((string)$item['status'], 0, 32);
        }
        foreach (['active', 'visible'] as $field) {
            if (isset($item[$field]) && is_bool($item[$field])) {
                return $item[$field] ? 'active' : 'inactive';
            }
        }
        return null;
    }

    public function ping(): bool {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getDriverName(): string {
        return 'MySQL / MariaDB (Relational SQL)';
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }
}
