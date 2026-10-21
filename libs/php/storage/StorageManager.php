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

    /**
     * Ermittelt den konfigurierten Speicher-Treiber.
     * @return StorageDriverInterface
     */
    public static function getDriver(): StorageDriverInterface {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $localConfig = dirname(__DIR__, 2) . '/config.local.php';
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
                    return [
                        'success' => true,
                        'message' => "Verbindung erfolgreich! Datenbank '{$dbname}' wurde neu angelegt (Server: {$version}).",
                        'server_version' => $version
                    ];
                } else {
                    return [
                        'success' => true,
                        'db_missing' => true,
                        'message' => "Verbindung zum Server erfolgreich (Server: {$version}), aber Datenbank '{$dbname}' existiert noch nicht.",
                        'server_version' => $version
                    ];
                }
            }

            return [
                'success' => true,
                'message' => "Verbindung zur Datenbank '{$dbname}' erfolgreich hergestellt! (Server: {$version})",
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
}
