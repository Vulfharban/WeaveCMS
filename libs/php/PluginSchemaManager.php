<?php
/**
 * ============================================================================
 * WeaveCMS Plugin Schema & Database Lifecycle Manager (Phase 42)
 * ============================================================================
 * Verwaltet zentrale Schema-Registrierung ({prefix}plugin_migrations bzw.
 * data/plugin_migrations.json), idempotente Tabellen- & JSON-Initialisierung,
 * versionierte Upgrade-Migrationen (inkl. 3rd-Party-Standard) sowie strikte
 * Datenerhaltung beim Deaktivieren von Plugins.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class PluginSchemaManager {

    protected static ?string $testRegistryPath = null;
    protected static array $verifiedTables = [];

    /**
     * Erlaubt isolierte Test-Registrierungsdatei ohne Produktivdaten zu berühren.
     */
    public static function setTestRegistryPath(?string $path): void {
        self::$testRegistryPath = $path;
        self::$verifiedTables = [];
    }

    public static function clearCache(): void {
        self::$verifiedTables = [];
    }

    public static function getJsonRegistryPath(): string {
        if (self::$testRegistryPath !== null) {
            return self::$testRegistryPath;
        }
        return dirname(__DIR__, 2) . '/data/plugin_migrations.json';
    }

    /**
     * Ermittelt das effektive Tabellen-Prefix.
     */
    public static function getEffectivePrefix(?string $prefix = null): string {
        if ($prefix !== null && $prefix !== '') {
            return $prefix;
        }
        return defined('WEAVE_DB_PREFIX') ? (string)WEAVE_DB_PREFIX : 'weave_';
    }

    /**
     * Ermittelt die aktive PDO-Verbindung (MySQL), sofern DB-Modus aktiv ist.
     */
    protected static function resolveActivePdo(?PDO $pdo = null): ?PDO {
        if ($pdo !== null) {
            return $pdo;
        }
        if (class_exists('StorageManager') && StorageManager::isPluginDatabaseActive()) {
            $driver = StorageManager::getDriver();
            if ($driver instanceof PdoSqlStorageDriver) {
                try {
                    return $driver->getPdo();
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }
        return null;
    }

    protected static function quoteIdent(string $name): string {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Stellt die zentrale Migrations-Registry-Tabelle {prefix}plugin_migrations bereit.
     */
    public static function ensureRegistryTable(PDO $pdo, ?string $prefix = null): void {
        $pref = self::getEffectivePrefix($prefix);
        $tableName = $pref . 'plugin_migrations';
        $cacheKey = spl_object_id($pdo) . ':' . $tableName;
        if (isset(self::$verifiedTables[$cacheKey])) {
            return;
        }

        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS " . self::quoteIdent($tableName) . " (
                plugin_slug TEXT PRIMARY KEY,
                schema_version TEXT NOT NULL DEFAULT '1.0.0',
                storage_mode TEXT NOT NULL DEFAULT 'none',
                checksum TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'current',
                details TEXT NOT NULL DEFAULT '{}',
                applied_at TEXT NOT NULL
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS " . self::quoteIdent($tableName) . " (
                `plugin_slug` VARCHAR(64) NOT NULL PRIMARY KEY,
                `schema_version` VARCHAR(32) NOT NULL DEFAULT '1.0.0',
                `storage_mode` VARCHAR(32) NOT NULL DEFAULT 'none',
                `checksum` VARCHAR(64) NOT NULL DEFAULT '',
                `status` VARCHAR(32) NOT NULL DEFAULT 'current',
                `details` LONGTEXT NULL,
                `applied_at` VARCHAR(32) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        self::$verifiedTables[$cacheKey] = true;
    }

    /**
     * Berechnet eine deterministische Prüfsumme über die Speicher-Metadaten eines Plugins.
     */
    public static function computeManifestChecksum(array $pluginMeta): string {
        $storage = is_array($pluginMeta['storage'] ?? null) ? $pluginMeta['storage'] : [];
        $payload = [
            'version'        => (string)($pluginMeta['version'] ?? '1.0.0'),
            'mode'           => (string)($storage['mode'] ?? 'none'),
            'schema_version' => (string)($storage['schema_version'] ?? ($pluginMeta['version'] ?? '1.0.0')),
            'tables'         => array_values((array)($storage['tables'] ?? [])),
            'plugin_data_keys' => (array)($storage['plugin_data_keys'] ?? []),
            'json_files'     => array_values((array)($storage['json_files'] ?? [])),
        ];
        return hash('sha256', (string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Liest alle gespeicherten Schema-Einträge aus DB oder JSON-Registry.
     */
    public static function getInstalledSchemas(?PDO $pdo = null, ?string $prefix = null): array {
        $activePdo = self::resolveActivePdo($pdo);
        $pref = self::getEffectivePrefix($prefix);

        if ($activePdo !== null) {
            try {
                self::ensureRegistryTable($activePdo, $pref);
                $table = self::quoteIdent($pref . 'plugin_migrations');
                $rows = $activePdo->query("SELECT plugin_slug, schema_version, storage_mode, checksum, status, details, applied_at FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
                $out = [];
                foreach ($rows as $row) {
                    $slug = (string)$row['plugin_slug'];
                    $details = json_decode((string)($row['details'] ?? '{}'), true);
                    $out[$slug] = [
                        'plugin_slug'    => $slug,
                        'schema_version' => (string)$row['schema_version'],
                        'storage_mode'   => (string)$row['storage_mode'],
                        'checksum'       => (string)$row['checksum'],
                        'status'         => (string)$row['status'],
                        'details'        => is_array($details) ? $details : [],
                        'applied_at'     => (string)$row['applied_at'],
                    ];
                }
                return $out;
            } catch (\Throwable $e) {
                error_log('PluginSchemaManager getInstalledSchemas DB error: ' . $e->getMessage());
            }
        }

        $file = self::getJsonRegistryPath();
        if (is_file($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Speichert den Migrations-Status eines Plugins in DB oder JSON-Registry.
     */
    public static function recordSchema(
        string $slug,
        string $version,
        string $mode,
        string $checksum,
        string $status = 'current',
        array $details = [],
        ?PDO $pdo = null,
        ?string $prefix = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $detailsJson = (string)json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $activePdo = self::resolveActivePdo($pdo);
        $pref = self::getEffectivePrefix($prefix);

        if ($activePdo !== null) {
            try {
                self::ensureRegistryTable($activePdo, $pref);
                $table = self::quoteIdent($pref . 'plugin_migrations');
                $driver = (string)$activePdo->getAttribute(PDO::ATTR_DRIVER_NAME);

                if ($driver === 'sqlite') {
                    $stmt = $activePdo->prepare("
                        INSERT INTO {$table} (plugin_slug, schema_version, storage_mode, checksum, status, details, applied_at)
                        VALUES (:slug, :ver, :mode, :chk, :st, :det, :at)
                        ON CONFLICT(plugin_slug) DO UPDATE SET
                            schema_version = excluded.schema_version,
                            storage_mode   = excluded.storage_mode,
                            checksum       = excluded.checksum,
                            status         = excluded.status,
                            details        = excluded.details,
                            applied_at     = excluded.applied_at
                    ");
                } else {
                    $stmt = $activePdo->prepare("
                        INSERT INTO {$table} (`plugin_slug`, `schema_version`, `storage_mode`, `checksum`, `status`, `details`, `applied_at`)
                        VALUES (:slug, :ver, :mode, :chk, :st, :det, :at)
                        ON DUPLICATE KEY UPDATE
                            `schema_version` = VALUES(`schema_version`),
                            `storage_mode`   = VALUES(`storage_mode`),
                            `checksum`       = VALUES(`checksum`),
                            `status`         = VALUES(`status`),
                            `details`        = VALUES(`details`),
                            `applied_at`     = VALUES(`applied_at`)
                    ");
                }
                return $stmt->execute([
                    ':slug' => $slug,
                    ':ver'  => $version,
                    ':mode' => $mode,
                    ':chk'  => $checksum,
                    ':st'   => $status,
                    ':det'  => $detailsJson,
                    ':at'   => $now,
                ]);
            } catch (\Throwable $e) {
                error_log("PluginSchemaManager recordSchema ({$slug}) error: " . $e->getMessage());
                return false;
            }
        }

        $all = self::getInstalledSchemas(null, $pref);
        $all[$slug] = [
            'plugin_slug'    => $slug,
            'schema_version' => $version,
            'storage_mode'   => $mode,
            'checksum'       => $checksum,
            'status'         => $status,
            'details'        => $details,
            'applied_at'     => $now,
        ];
        return self::writeJsonRegistry($all);
    }

    protected static function writeJsonRegistry(array $all): bool {
        $file = self::getJsonRegistryPath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(5));
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        return @rename($tmp, $file);
    }

    /**
     * Prüft, ob eine SQL-Tabelle existiert (kompatibel mit MySQL und SQLite).
     */
    public static function tableExists(PDO $pdo, string $tableName): bool {
        try {
            $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :t LIMIT 1");
                $stmt->execute([':t' => $tableName]);
                return $stmt->fetchColumn() !== false;
            }
            $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
            $stmt->execute([':t' => $tableName]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Löst Tabellennamen eines Plugins gegen das Prefix auf.
     * Unterstützt '{prefix}foo' und automatisches Mapping von 'weave_forum...' auf das aktive Prefix.
     */
    public static function resolvePluginTableName(string $rawName, string $prefix): string {
        if (str_contains($rawName, '{prefix}')) {
            return str_replace('{prefix}', $prefix, $rawName);
        }
        if ($prefix !== 'weave_' && str_starts_with($rawName, 'weave_')) {
            return $prefix . substr($rawName, 6);
        }
        if (!str_starts_with($rawName, $prefix)) {
            return $prefix . $rawName;
        }
        return $rawName;
    }

    /**
     * Führt alle dateibasierten Migrationsskripte (v1.0.0.php, v1.1.0.php, ...) in SemVer-Reihenfolge aus.
     *
     * @return array{applied: string[], errors: string[]}
     */
    public static function runMigrationsDir(
        string $pluginDir,
        string $migrationsRelDir,
        ?string $fromVersion,
        string $targetVersion,
        array &$context
    ): array {
        $applied = [];
        $errors = [];

        $cleanRel = trim(str_replace(['..', '\\'], ['', '/'], $migrationsRelDir), '/');
        if ($cleanRel === '') {
            return ['applied' => [], 'errors' => []];
        }
        $fullDir = rtrim($pluginDir, '/\\') . '/' . $cleanRel;
        if (!is_dir($fullDir)) {
            return ['applied' => [], 'errors' => []];
        }

        $files = glob($fullDir . '/*.php') ?: [];
        $candidates = [];
        foreach ($files as $file) {
            $base = basename($file, '.php');
            // Erlaubt v1.php, v1.2.php, v1.2.3.php, 1.2.0.php
            $verRaw = ltrim($base, 'vV');
            if (!preg_match('/^\d+(?:\.\d+){0,3}$/', $verRaw)) {
                continue;
            }
            $parts = explode('.', $verRaw);
            while (count($parts) < 3) {
                $parts[] = '0';
            }
            $ver = implode('.', array_slice($parts, 0, 3));
            $candidates[] = ['version' => $ver, 'file' => $file, 'label' => $base];
        }

        usort($candidates, static fn(array $a, array $b): int => version_compare($a['version'], $b['version']));

        foreach ($candidates as $cand) {
            $v = $cand['version'];
            if ($fromVersion !== null && $fromVersion !== '' && version_compare($v, $fromVersion, '<=')) {
                continue;
            }
            if (version_compare($v, $targetVersion, '>')) {
                continue;
            }
            try {
                $migrationContext = &$context;
                $res = require $cand['file'];
                if (is_callable($res)) {
                    $res($migrationContext);
                }
                $applied[] = $cand['label'];
            } catch (\Throwable $e) {
                $errors[] = $cand['label'] . ': ' . $e->getMessage();
            }
        }

        return ['applied' => $applied, 'errors' => $errors];
    }

    /**
     * Prüft, initialisiert und migriert bei Bedarf das Datenbank-/JSON-Schema eines einzelnen Plugins.
     * Wird sowohl beim Installer, bei der JSON->MySQL-Migration als auch beim Plugin-Aktivieren ausgeführt.
     */
    public static function ensurePluginSchema(string $slug, array &$data = [], ?PDO $pdo = null, ?string $prefix = null): array {
        if (!class_exists('PluginManager')) {
            return ['success' => false, 'slug' => $slug, 'message' => 'PluginManager nicht geladen.'];
        }

        if ($pdo === null && self::$testRegistryPath === null && class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            return [
                'success' => true,
                'slug' => $slug,
                'mode' => 'sandbox',
                'from_version' => null,
                'schema_version' => 'sandbox',
                'imported_keys' => 0,
                'applied_migrations' => [],
                'errors' => [],
            ];
        }

        $plugin = PluginManager::getPlugin($slug);
        if (!$plugin) {
            return ['success' => false, 'slug' => $slug, 'message' => "Plugin '{$slug}' nicht gefunden."];
        }

        $root = dirname(__DIR__, 2);
        $pref = self::getEffectivePrefix($prefix);
        $activePdo = self::resolveActivePdo($pdo);

        $storage = is_array($plugin['storage'] ?? null) ? $plugin['storage'] : [];
        $mode = (string)($storage['mode'] ?? 'none');
        if (!in_array($mode, ['plugin_data', 'sql_tables', 'json_only', 'none'], true)) {
            $mode = 'none';
        }
        $targetVersion = (string)($storage['schema_version'] ?? ($plugin['version'] ?? '1.0.0'));
        $checksum = self::computeManifestChecksum($plugin);

        $installedAll = self::getInstalledSchemas($activePdo, $pref);
        $installed = $installedAll[$slug] ?? null;
        $fromVersion = $installed['schema_version'] ?? null;

        // Plugin laden, damit eventuelle Ensure-/Migrate-Hooks verfügbar sind (nur falls gültig)
        if (!empty($plugin['is_valid']) && file_exists($plugin['entry_path'])) {
            try {
                require_once $plugin['entry_path'];
            } catch (\Throwable $e) {
                return ['success' => false, 'slug' => $slug, 'message' => "Fehler beim Laden von '{$slug}': " . $e->getMessage()];
            }
        }

        $importedKeys = 0;
        $appliedMigrations = [];
        $errors = [];

        // 1. plugin_data-Modus: Zentrale Tabelle + automatische JSON-Erstübernahme
        if ($mode === 'plugin_data' && $activePdo !== null && class_exists('StorageManager')) {
            try {
                StorageManager::ensureCorePluginTables($activePdo, $pref);
                $keysMap = is_array($storage['plugin_data_keys'] ?? null) ? $storage['plugin_data_keys'] : [];
                $resolvedMap = [];
                foreach ($keysMap as $k => $relPath) {
                    if (is_string($k) && $k !== '' && is_string($relPath) && $relPath !== '') {
                        $cleanRel = ltrim(str_replace(['..', '\\'], ['', '/'], $relPath), '/');
                        $resolvedMap[$k] = $root . '/' . $cleanRel;
                    }
                }
                if (!empty($resolvedMap)) {
                    $importedKeys = StorageManager::importPluginJsonFilesToMysql($activePdo, $pref, $resolvedMap);
                }
            } catch (\Throwable $e) {
                $errors[] = 'plugin_data init: ' . $e->getMessage();
            }
        }

        // 2. json_only-Modus: Zielverzeichnisse absichern, vorhandene JSON-Dateien niemals überschreiben
        if ($mode === 'json_only') {
            $jsonFiles = is_array($storage['json_files'] ?? null) ? $storage['json_files'] : [];
            foreach ($jsonFiles as $relPath) {
                if (is_string($relPath) && $relPath !== '') {
                    $cleanRel = ltrim(str_replace(['..', '\\'], ['', '/'], $relPath), '/');
                    $dir = dirname($root . '/' . $cleanRel);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                }
            }
        }

        $schemaContext = [
            'slug'         => $slug,
            'plugin'       => $plugin,
            'storage'      => $storage,
            'mode'         => $mode,
            'pdo'          => $activePdo,
            'prefix'       => $pref,
            'from_version' => $fromVersion,
            'to_version'   => $targetVersion,
            'plugin_dir'   => $plugin['plugin_dir'],
        ];

        // 3. Dateibasierte Migrationen aus migrations_dir (3rd-Party- & Core-Standard)
        if (!empty($storage['migrations_dir']) && is_string($storage['migrations_dir'])) {
            $migRes = self::runMigrationsDir(
                (string)$plugin['plugin_dir'],
                $storage['migrations_dir'],
                $fromVersion,
                $targetVersion,
                $schemaContext
            );
            $appliedMigrations = $migRes['applied'];
            if (!empty($migRes['errors'])) {
                $errors = array_merge($errors, $migRes['errors']);
            }
        }

        // 4. Plugin-Hooks für idempotente Schema-Sicherstellung und Versions-Migration
        try {
            PluginManager::doAction("plugin_ensure_schema_{$slug}", $data, $schemaContext);
            if ($fromVersion !== null && version_compare($fromVersion, $targetVersion, '<')) {
                PluginManager::doAction("plugin_migrate_schema_{$slug}", $fromVersion, $targetVersion, $data, $schemaContext);
            }
        } catch (\Throwable $e) {
            $errors[] = 'schema hook: ' . $e->getMessage();
        }

        $status = empty($errors) ? 'current' : 'error';
        self::recordSchema($slug, $targetVersion, $mode, $checksum, $status, [
            'from_version'       => $fromVersion,
            'imported_keys'      => $importedKeys,
            'applied_migrations' => $appliedMigrations,
            'errors'             => $errors,
        ], $activePdo, $pref);

        return [
            'success'            => empty($errors),
            'slug'               => $slug,
            'mode'               => $mode,
            'from_version'       => $fromVersion,
            'schema_version'     => $targetVersion,
            'imported_keys'      => $importedKeys,
            'applied_migrations' => $appliedMigrations,
            'errors'             => $errors,
        ];
    }

    /**
     * Initialisiert und prüft alle entdeckten (oder nur aktive) Plugins.
     */
    public static function ensureAllPluginSchemas(array &$data = [], bool $onlyActive = false, ?PDO $pdo = null, ?string $prefix = null): array {
        if (!class_exists('PluginManager')) {
            return [];
        }
        $plugins = PluginManager::discover();
        $activeSlugs = PluginManager::getActiveSlugs($data);
        $results = [];

        foreach ($plugins as $slug => $meta) {
            if ($onlyActive && !in_array($slug, $activeSlugs, true)) {
                continue;
            }
            $results[$slug] = self::ensurePluginSchema($slug, $data, $pdo, $prefix);
        }
        return $results;
    }

    /**
     * Erstellt einen vollständigen Diagnose- & Statusbericht für alle entdeckten Plugins (offiziell & 3rd-Party).
     */
    public static function getSchemaStatusReport(array $data = [], ?PDO $pdo = null, ?string $prefix = null): array {
        if (!class_exists('PluginManager')) {
            return [];
        }

        $plugins = PluginManager::discover();
        $activeSlugs = PluginManager::getActiveSlugs($data);
        $officialSlugs = PluginManager::getOfficialPluginSlugs();
        $pref = self::getEffectivePrefix($prefix);
        $activePdo = self::resolveActivePdo($pdo);
        $installed = self::getInstalledSchemas($activePdo, $pref);

        $report = [];
        foreach ($plugins as $slug => $meta) {
            $storage = is_array($meta['storage'] ?? null) ? $meta['storage'] : [];
            $mode = (string)($storage['mode'] ?? 'none');
            $targetVer = (string)($storage['schema_version'] ?? ($meta['version'] ?? '1.0.0'));
            $rec = $installed[$slug] ?? null;
            $installedVer = $rec['schema_version'] ?? null;

            $expectedTables = [];
            $missingTables = [];
            if ($mode === 'sql_tables') {
                foreach ((array)($storage['tables'] ?? []) as $t) {
                    if (!is_string($t) || $t === '') continue;
                    $resolvedT = self::resolvePluginTableName($t, $pref);
                    $expectedTables[] = $resolvedT;
                    if ($activePdo !== null && !self::tableExists($activePdo, $resolvedT)) {
                        $missingTables[] = $resolvedT;
                    }
                }
            } elseif ($mode === 'plugin_data') {
                $expectedTables[] = $pref . 'plugin_data';
                if ($activePdo !== null && !self::tableExists($activePdo, $pref . 'plugin_data')) {
                    $missingTables[] = $pref . 'plugin_data';
                }
            }

            $status = 'current';
            if (!empty($missingTables)) {
                $status = 'missing_tables';
            } elseif ($rec === null) {
                $status = 'not_initialized';
            } elseif (version_compare((string)$installedVer, $targetVer, '<') || ($rec['status'] ?? '') === 'error') {
                $status = 'needs_migration';
            }

            $report[$slug] = [
                'slug'              => $slug,
                'name'              => (string)($meta['name'] ?? $slug),
                'is_official'       => in_array($slug, $officialSlugs, true),
                'is_active'         => in_array($slug, $activeSlugs, true),
                'storage_mode'      => $mode,
                'manifest_version'  => $targetVer,
                'installed_version' => $installedVer,
                'tables_expected'   => $expectedTables,
                'tables_missing'    => $missingTables,
                'status'            => $status,
                'applied_at'        => $rec['applied_at'] ?? null,
            ];
        }

        return $report;
    }

    /**
     * Explizite, strikt bestätigungspflichtige Bereinigung eines Plugins.
     * Wird NIEMALS bei normaler Deaktivierung aufgerufen.
     */
    public static function resetPluginData(string $slug, array &$data, bool $confirmed = false, ?PDO $pdo = null, ?string $prefix = null): array {
        if (!$confirmed) {
            return ['success' => false, 'message' => 'Bestätigung erforderlich: Plugin-Daten werden beim Deaktivieren standardmäßig erhalten.'];
        }
        if (class_exists('BackupManager')) {
            BackupManager::createBackup('pre_plugin_reset_' . $slug, $data);
        }

        $pref = self::getEffectivePrefix($prefix);
        $activePdo = self::resolveActivePdo($pdo);
        $plugin = class_exists('PluginManager') ? PluginManager::getPlugin($slug) : null;
        $storage = is_array($plugin['storage'] ?? null) ? $plugin['storage'] : [];

        // plugin_data-Keys entfernen
        if ($activePdo !== null && !empty($storage['plugin_data_keys']) && is_array($storage['plugin_data_keys'])) {
            $table = self::quoteIdent($pref . 'plugin_data');
            if (self::tableExists($activePdo, $pref . 'plugin_data')) {
                $stmt = $activePdo->prepare("DELETE FROM {$table} WHERE plugin_key = :k");
                foreach (array_keys($storage['plugin_data_keys']) as $k) {
                    $stmt->execute([':k' => (string)$k]);
                }
            }
        }

        // Registry-Eintrag entfernen
        if ($activePdo !== null && self::tableExists($activePdo, $pref . 'plugin_migrations')) {
            $migTable = self::quoteIdent($pref . 'plugin_migrations');
            $activePdo->prepare("DELETE FROM {$migTable} WHERE plugin_slug = :s")->execute([':s' => $slug]);
        } else {
            $all = self::getInstalledSchemas(null, $pref);
            unset($all[$slug]);
            self::writeJsonRegistry($all);
        }

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('PLUGIN_DATA_RESET', "Daten für Plugin '{$slug}' explizit zurückgesetzt (mit Vorab-Backup)", 'WARNING');
        }

        return ['success' => true, 'message' => "Daten für Plugin '{$slug}' nach Vorab-Backup zurückgesetzt."];
    }
}
