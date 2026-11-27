<?php
/**
 * ============================================================================
 * WeaveCMS Storage Manager & Factory (Phase 7 Vorbereitung)
 * ============================================================================
 * Verwaltet den aktiven Speicher-Treiber (JSON vs. SQL), Notfall-Fallbacks
 * und Verbindungstests.
 */

require_once __DIR__ . '/StorageDriverInterface.php';
require_once __DIR__ . '/JsonStorageDriver.php';
require_once __DIR__ . '/PdoSqlStorageDriver.php';

class StorageManager {
    protected static ?StorageDriverInterface $instance = null;
    protected static bool $pluginTableReady = false;

    /**
     * Ermittelt den konfigurierten Speicher-Treiber.
     * @return StorageDriverInterface
     */
    public static function getDriver(): StorageDriverInterface {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Phase 15: Flüchtiger Sandbox-Treiber bei aktiver Demo-Sitzung
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            $sandboxDriver = SandboxManager::getDriver();
            if ($sandboxDriver instanceof StorageDriverInterface) {
                self::$instance = $sandboxDriver;
                return self::$instance;
            }
        }

        // Hook für alternative Storage-Treiber via PluginManager
        if (class_exists('PluginManager')) {
            $filteredDriver = PluginManager::applyFilters('storage_get_driver', null);
            if ($filteredDriver instanceof StorageDriverInterface) {
                self::$instance = $filteredDriver;
                return self::$instance;
            }
        }

        $localConfig = dirname(__DIR__, 3) . '/config.local.php';
        if (file_exists($localConfig)) {
            require_once $localConfig;
        }

        $driverType = defined('WEAVE_STORAGE_DRIVER') ? WEAVE_STORAGE_DRIVER : 'json';

        if ($driverType === 'mysql' && extension_loaded('pdo_mysql')) {
            try {
                $driver = new PdoSqlStorageDriver([
                    'host' => defined('WEAVE_DB_HOST') ? WEAVE_DB_HOST : '127.0.0.1',
                    'port' => defined('WEAVE_DB_PORT') ? WEAVE_DB_PORT : 3306,
                    'database' => defined('WEAVE_DB_NAME') ? WEAVE_DB_NAME : 'weavecms',
                    'username' => defined('WEAVE_DB_USER') ? WEAVE_DB_USER : 'root',
                    'password' => defined('WEAVE_DB_PASS') ? WEAVE_DB_PASS : '',
                    'prefix' => defined('WEAVE_DB_PREFIX') ? WEAVE_DB_PREFIX : 'weave_',
                ]);

                if ($driver->ping()) {
                    self::$instance = $driver;
                    return self::$instance;
                }
            } catch (Exception $e) {
                error_log('StorageManager: MySQL connection failed, falling back to JSON: ' . $e->getMessage());
            }
        }

        // Standard-Fallback: JsonStorageDriver
        self::$instance = new JsonStorageDriver();
        return self::$instance;
    }

    /**
     * Ermöglicht das explizite Setzen eines Treibers (z. B. für Tests oder Migrationen).
     * @param StorageDriverInterface|null $driver
     */
    public static function setDriver(?StorageDriverInterface $driver): void {
        self::$instance = $driver;
    }

    /**
     * Prüft eine MySQL-Datenbankverbindung live (z. B. für den Installationsassistenten).
     * @param array $params [host, port, database, username, password, create_db]
     * @return array [success => bool, message => string, server_version => string|null]
     */
    public static function testDbConnection(array $params): array {
        if (!extension_loaded('pdo_mysql')) {
            return [
                'success' => false,
                'message' => 'Die PHP-Erweiterung pdo_mysql ist auf diesem Server nicht aktiviert.',
                'server_version' => null
            ];
        }

        $host = trim($params['host'] ?? '127.0.0.1');
        $port = (int)($params['port'] ?? 3306);
        $dbname = trim($params['database'] ?? '');
        $user = trim($params['username'] ?? 'root');
        $pass = (string)($params['password'] ?? '');
        $createIfMissing = !empty($params['create_db']);

        if (empty($host)) {
            return ['success' => false, 'message' => 'Bitte gib einen Datenbank-Host ein.', 'server_version' => null];
        }
        if (empty($dbname)) {
            return ['success' => false, 'message' => 'Bitte gib einen Datenbank-Namen ein.', 'server_version' => null];
        }

        try {
            // Zuerst Verbindung zum MySQL-Server (ohne zwingende DB) testen
            $dsnServer = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsnServer, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            // Prüfen, ob die gewünschte Datenbank existiert
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db");
            $stmt->execute([':db' => $dbname]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                if ($createIfMissing) {
                    $cleanDb = str_replace('`', '``', $dbname);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cleanDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } else {
                    return [
                        'success' => true,
                        'db_missing' => true,
                        'message' => "Verbindung zum Server erfolgreich (Server: {$version}), aber Datenbank '{$dbname}' existiert noch nicht.",
                        'server_version' => $version
                    ];
                }
            }

            // Benutzerrechte für Tabellen-Operationen (CREATE, INSERT, SELECT, DROP) verifizieren
            try {
                $dsnDb = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                $pdoDb = new PDO($dsnDb, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3
                ]);

                $testTable = '_weave_perm_test_' . substr(md5((string)uniqid()), 0, 8);
                $pdoDb->exec("CREATE TABLE `{$testTable}` (`id` INT PRIMARY KEY AUTO_INCREMENT, `chk` VARCHAR(16)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdoDb->exec("INSERT INTO `{$testTable}` (`chk`) VALUES ('ok')");
                $stmtChk = $pdoDb->query("SELECT `chk` FROM `{$testTable}` LIMIT 1");
                $chkVal = $stmtChk ? $stmtChk->fetchColumn() : null;
                $pdoDb->exec("DROP TABLE IF EXISTS `{$testTable}`");

                if ($chkVal !== 'ok') {
                    return [
                        'success' => false,
                        'message' => "Verbindung zur Datenbank '{$dbname}' aufgebaut, aber Lese-/Schreibtest fehlgeschlagen.",
                        'server_version' => $version
                    ];
                }
            } catch (PDOException $permEx) {
                return [
                    'success' => false,
                    'message' => "Verbindung hergestellt, aber Benutzer '{$user}' fehlen nötige Rechte (CREATE/INSERT/DROP) in '{$dbname}': " . $permEx->getMessage(),
                    'server_version' => $version
                ];
            }

            return [
                'success' => true,
                'message' => "Verbindung zur Datenbank '{$dbname}' erfolgreich! Benutzerrechte (CREATE, INSERT, SELECT, DROP) vollständig verifiziert (Server: {$version}).",
                'server_version' => $version
            ];
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Access denied')) {
                $msg = 'Zugriff verweigert: Benutzername oder Passwort ist falsch.';
            } elseif (str_contains($msg, 'Connection refused') || str_contains($msg, 'getaddrinfo failed')) {
                $msg = 'Server nicht erreichbar. Bitte Host und Port prüfen.';
            }
            return [
                'success' => false,
                'message' => 'Datenbankfehler: ' . $msg,
                'server_version' => null
            ];
        }
    }

    /**
     * Ermittelt den Typ des aktuellen Treibers ('json' oder 'mysql').
     */
    public static function getCurrentDriverType(): string {
        $driver = self::getDriver();
        return ($driver instanceof PdoSqlStorageDriver) ? 'mysql' : 'json';
    }

    /**
     * Gibt Detailinformationen zur aktuell aktiven Speicher-Engine zurück.
     * @return array [type => string, name => string, location => string, ping_ok => bool]
     */
    public static function getDriverDetails(): array {
        $driver = self::getDriver();
        $isMysql = ($driver instanceof PdoSqlStorageDriver);
        $ping = $driver->ping();

        if ($isMysql) {
            $host = defined('WEAVE_DB_HOST') ? WEAVE_DB_HOST : '127.0.0.1';
            $db = defined('WEAVE_DB_NAME') ? WEAVE_DB_NAME : 'weavecms';
            $prefix = defined('WEAVE_DB_PREFIX') ? WEAVE_DB_PREFIX : 'weave_';
            return [
                'type' => 'mysql',
                'name' => 'MySQL / MariaDB',
                'location' => "Datenbank: {$db} auf {$host} (Tabellen: {$prefix}*)",
                'ping_ok' => $ping,
                'is_fallback' => false
            ];
        }

        $root = dirname(__DIR__, 3);
        $hasLocalConfig = file_exists($root . '/config.local.php');
        $requestedMysql = (defined('WEAVE_STORAGE_DRIVER') && WEAVE_STORAGE_DRIVER === 'mysql');
        $isFallback = ($hasLocalConfig && $requestedMysql && !$ping);

        return [
            'type' => 'json',
            'name' => 'Flat-File JSON',
            'location' => 'Lokale Datei: data/data.json',
            'ping_ok' => $ping,
            'is_fallback' => $isFallback
        ];
    }

    /**
     * Migriert Daten von MySQL nach JSON und stellt den Standardtreiber auf JSON um.
     * @param string $pin Administrator-PIN
     * @param array $currentData Aktuelle Daten
     * @return array [success => bool, message => string]
     */
    public static function migrateToJson(string $pin, array $currentData): array {
        if (!verify_admin_pin($pin, $currentData)) {
            return ['success' => false, 'message' => 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene PIN ist nicht korrekt.'];
        }

        $root = dirname(__DIR__, 3);
        $dataFile = $root . '/data.json';

        // 1. JSON-Treiber initialisieren und Daten atomar schreiben
        $jsonDriver = new JsonStorageDriver($dataFile);
        if (!$jsonDriver->saveData($currentData)) {
            return ['success' => false, 'message' => 'Fehler beim Schreiben der Datei data.json.'];
        }

        // 2. Automatisches Sicherheits-Snapshot-Backup anlegen
        if (class_exists('BackupManager')) {
            BackupManager::createBackup('data', $currentData);
        }

        // 3. config.local.php entfernen
        $localConfig = $root . '/config.local.php';
        if (file_exists($localConfig)) {
            @unlink($localConfig);
        }

        // 4. Instanz zurücksetzen
        self::$instance = $jsonDriver;

        return [
            'success' => true,
            'message' => 'Erfolgreich auf Flat-File JSON umgestellt! Alle Daten wurden in data.json gesichert und die Umgebungskonfiguration bereinigt.'
        ];
    }

    /**
     * Migriert Daten von JSON nach MySQL und aktiviert MySQL als primäre Speicher-Engine.
     * @param string $pin Administrator-PIN
     * @param array $dbParams [host, port, database, username, password, prefix, create_db]
     * @param array $currentData Aktuelle Daten
     * @return array [success => bool, message => string]
     */
    public static function migrateToMysql(string $pin, array $dbParams, array $currentData): array {
        if (!verify_admin_pin($pin, $currentData)) {
            return ['success' => false, 'message' => 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene PIN ist nicht korrekt.'];
        }

        $test = self::testDbConnection($dbParams);
        if (!$test['success']) {
            return ['success' => false, 'message' => 'Datenbankverbindung fehlgeschlagen: ' . ($test['message'] ?? 'Unbekannter Fehler')];
        }

        $root = dirname(__DIR__, 3);

        // 1. Snapshot der JSON-Daten anlegen
        if (class_exists('BackupManager')) {
            BackupManager::createBackup('data', $currentData);
        }

        // 2. SQL-Treiber initialisieren und Tabelle anlegen
        $dbHost = trim($dbParams['host'] ?? '127.0.0.1');
        $dbPort = (int)($dbParams['port'] ?? 3306);
        $dbName = trim($dbParams['database'] ?? '');
        $dbUser = trim($dbParams['username'] ?? 'root');
        $dbPass = (string)($dbParams['password'] ?? '');
        $dbPrefix = trim($dbParams['prefix'] ?? 'weave_');

        $sqlDriver = new PdoSqlStorageDriver([
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'prefix' => $dbPrefix
        ]);

        if (!$sqlDriver->init()) {
            return ['success' => false, 'message' => 'Die MySQL-Tabelle konnte nicht initialisiert werden.'];
        }

        if (!$sqlDriver->saveData($currentData)) {
            return ['success' => false, 'message' => 'Fehler beim Übertragen der Daten in die MySQL-Datenbank.'];
        }

        // 3. config.local.php schreiben
        $localConf = "<?php\n" .
            "// WeaveCMS Lokale Umgebungskonfiguration (wird nicht ins Git-Repository übertragen)\n" .
            "define('WEAVE_STORAGE_DRIVER', 'mysql');\n" .
            "define('WEAVE_DB_HOST', " . var_export($dbHost, true) . ");\n" .
            "define('WEAVE_DB_PORT', " . $dbPort . ");\n" .
            "define('WEAVE_DB_NAME', " . var_export($dbName, true) . ");\n" .
            "define('WEAVE_DB_USER', " . var_export($dbUser, true) . ");\n" .
            "define('WEAVE_DB_PASS', " . var_export($dbPass, true) . ");\n" .
            "define('WEAVE_DB_PREFIX', " . var_export($dbPrefix, true) . ");\n";

        if (@file_put_contents($root . '/config.local.php', $localConf) === false) {
            return ['success' => false, 'message' => 'Die Datei config.local.php konnte nicht geschrieben werden. Bitte Dateirechte prüfen.'];
        }

        // 4. Instanz aktualisieren
        self::$instance = $sqlDriver;

        // 5. Phase 42: Core-Plugin-Tabellen ({prefix}plugin_data & {prefix}plugin_migrations) sofort anlegen
        // und alle Plugin-Schemas sowie bestehende JSON-Dateien in die Datenbank übernehmen
        try {
            $sqlPdo = $sqlDriver->getPdo();
            self::ensureCorePluginTables($sqlPdo, $dbPrefix);
            if (class_exists('PluginSchemaManager')) {
                PluginSchemaManager::ensureAllPluginSchemas($currentData, false, $sqlPdo, $dbPrefix);
            }
        } catch (\Throwable $e) {
            error_log('StorageManager migrateToMysql plugin schemas warning: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => "Erfolgreich zu MySQL ('{$dbName}') migriert! Die Datenbank ist nun als primäre Speicher-Engine aktiv."
        ];
    }

    /**
     * Prüft, ob Plugins aktuell den globalen relationalen Datenbankspeicher (MySQL) nutzen sollen.
     * In einer aktiven Demo-Sandbox wird bewusst dateibasiert in isolierten Sandbox-Dateien gespeichert.
     */
    public static function isPluginDatabaseActive(): bool {
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            return false;
        }
        return self::getCurrentDriverType() === 'mysql';
    }

    /**
     * Stellt die zentrale Tabelle {prefix}plugin_data für alle Plugin-Daten bereit.
     */
    protected static function ensurePluginDataTable(PDO $pdo, string $table): void {
        if (self::$pluginTableReady) {
            return;
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
            `plugin_key` VARCHAR(128) NOT NULL PRIMARY KEY,
            `payload` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$pluginTableReady = true;
    }

    /**
     * Legt {prefix}plugin_data und {prefix}plugin_migrations sofort bei Installation/Migration an (Phase 42).
     */
    public static function ensureCorePluginTables(PDO $pdo, string $prefix = 'weave_'): void {
        $cleanPrefix = $prefix !== '' ? $prefix : 'weave_';
        $dataTable = '`' . str_replace('`', '``', $cleanPrefix . 'plugin_data') . '`';
        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$dataTable} (
                plugin_key TEXT PRIMARY KEY,
                payload TEXT NOT NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$dataTable} (
                `plugin_key` VARCHAR(128) NOT NULL PRIMARY KEY,
                `payload` LONGTEXT NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            self::$pluginTableReady = true;
        }
        if (class_exists('PluginSchemaManager')) {
            PluginSchemaManager::ensureRegistryTable($pdo, $cleanPrefix);
        }
    }

    /**
     * Übernimmt vorhandene lokale JSON-Dateien einmalig in {prefix}plugin_data,
     * ohne bereits existierende Datenbank-Einträge zu überschreiben (Phase 42).
     *
     * @param array<string, string> $keyToPathMap ['plugin_key' => '/abs/path/to/file.json']
     * @return int Anzahl neu importierter Datensätze
     */
    public static function importPluginJsonFilesToMysql(PDO $pdo, string $prefix, array $keyToPathMap): int {
        if (empty($keyToPathMap)) {
            return 0;
        }
        $cleanPrefix = $prefix !== '' ? $prefix : 'weave_';
        self::ensureCorePluginTables($pdo, $cleanPrefix);
        $table = '`' . str_replace('`', '``', $cleanPrefix . 'plugin_data') . '`';
        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $imported = 0;

        foreach ($keyToPathMap as $key => $path) {
            if (!is_string($key) || $key === '' || !is_string($path) || !is_file($path)) {
                continue;
            }
            $chk = $pdo->prepare("SELECT 1 FROM {$table} WHERE plugin_key = :k LIMIT 1");
            $chk->execute([':k' => $key]);
            if ($chk->fetchColumn() !== false) {
                continue; // Bestehende DB-Daten niemals überschreiben
            }
            $raw = @file_get_contents($path);
            if ($raw === false || trim($raw) === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            $normalizedJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($normalizedJson === false) {
                continue;
            }
            if ($driver === 'sqlite') {
                $ins = $pdo->prepare("INSERT OR IGNORE INTO {$table} (plugin_key, payload) VALUES (:k, :p)");
            } else {
                $ins = $pdo->prepare("INSERT IGNORE INTO {$table} (`plugin_key`, `payload`) VALUES (:k, :p)");
            }
            if ($ins->execute([':k' => $key, ':p' => $normalizedJson])) {
                $imported++;
            }
        }
        return $imported;
    }

    /**
     * Lädt einen beliebigen Plugin-Datensatz automatisch aus der gewählten Datenbank (MySQL)
     * oder aus der JSON-Fallback-Datei. Falls in MySQL noch kein Eintrag existiert, wird eine
     * vorhandene JSON-Datei beim ersten Zugriff automatisch in die Datenbank übernommen.
     *
     * @param string $key Eindeutiger Schlüssel in {prefix}plugin_data
     * @param mixed $default Rückgabewert, wenn noch keine Daten vorhanden sind
     * @param string|null $jsonFallbackPath Pfad zur JSON-Datei für JSON-Modus / Erstübernahme
     * @return mixed
     */
    public static function loadPluginData(string $key, $default = [], ?string $jsonFallbackPath = null) {
        if (self::isPluginDatabaseActive()) {
            try {
                $driver = self::getDriver();
                if ($driver instanceof PdoSqlStorageDriver) {
                    $pdo = $driver->getPdo();
                    $prefix = defined('WEAVE_DB_PREFIX') ? WEAVE_DB_PREFIX : 'weave_';
                    $table = '`' . str_replace('`', '``', $prefix . 'plugin_data') . '`';
                    self::ensurePluginDataTable($pdo, $table);

                    $stmt = $pdo->prepare("SELECT `payload` FROM {$table} WHERE `plugin_key` = :k LIMIT 1");
                    $stmt->execute([':k' => $key]);
                    $payload = $stmt->fetchColumn();

                    if ($payload !== false && $payload !== null) {
                        $decoded = json_decode((string)$payload, true);
                        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
                    }

                    // Erstübernahme: bestehende JSON-Datei automatisch in die Datenbank importieren
                    if ($jsonFallbackPath !== null && file_exists($jsonFallbackPath)) {
                        $raw = @file_get_contents($jsonFallbackPath);
                        $decoded = json_decode((string)$raw, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            self::savePluginData($key, $decoded, null);
                            return $decoded;
                        }
                    }

                    return $default;
                }
            } catch (Exception $e) {
                error_log('StorageManager loadPluginData (' . $key . ') error: ' . $e->getMessage());
            }
        }

        if ($jsonFallbackPath !== null && file_exists($jsonFallbackPath)) {
            $raw = @file_get_contents($jsonFallbackPath);
            $decoded = json_decode((string)$raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $default;
    }

    /**
     * Speichert einen Plugin-Datensatz automatisch in der aktiven Datenbank (MySQL)
     * oder atomar in die angegebene JSON-Datei.
     *
     * @param string $key Eindeutiger Schlüssel in {prefix}plugin_data
     * @param mixed $value Beliebige JSON-serialisierbare Datenstruktur
     * @param string|null $jsonFallbackPath Zielpfad im JSON-/Sandbox-Modus
     * @return bool
     */
    public static function savePluginData(string $key, $value, ?string $jsonFallbackPath = null): bool {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        if (self::isPluginDatabaseActive()) {
            try {
                $driver = self::getDriver();
                if ($driver instanceof PdoSqlStorageDriver) {
                    $pdo = $driver->getPdo();
                    $prefix = defined('WEAVE_DB_PREFIX') ? WEAVE_DB_PREFIX : 'weave_';
                    $table = '`' . str_replace('`', '``', $prefix . 'plugin_data') . '`';
                    self::ensurePluginDataTable($pdo, $table);

                    $stmt = $pdo->prepare("
                        INSERT INTO {$table} (`plugin_key`, `payload`) VALUES (:k, :p)
                        ON DUPLICATE KEY UPDATE `payload` = VALUES(`payload`)
                    ");
                    return $stmt->execute([':k' => $key, ':p' => $json]);
                }
            } catch (Exception $e) {
                error_log('StorageManager savePluginData (' . $key . ') error: ' . $e->getMessage());
            }
        }

        if ($jsonFallbackPath === null) {
            return false;
        }

        $dir = dirname($jsonFallbackPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $tmp = $jsonFallbackPath . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        return @rename($tmp, $jsonFallbackPath);
    }

    /**
     * Gibt den genauen Grund des letzten Speicherfehlers zurück.
     * @return string
     */
    public static function getLastError(): string {
        $driver = self::getDriver();
        if (method_exists($driver, 'getLastError')) {
            $err = $driver->getLastError();
            if (!empty($err)) {
                return $err;
            }
        }
        $last = error_get_last();
        if (!empty($last['message'])) {
            return $last['message'];
        }
        return 'Dateiberechtigungen (chmod/chown) prüfen. data.json oder das Serververzeichnis ist für den PHP-Prozess schreibgeschützt.';
    }
}

