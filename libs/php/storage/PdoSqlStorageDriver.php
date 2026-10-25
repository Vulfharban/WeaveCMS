<?php
/**
 * ============================================================================
 * WeaveCMS Relational SQL Storage Driver (MySQL / MariaDB)
 * ============================================================================
 * Speichert Datenstrukturen in relationalen Tabellen via PDO mit Transaktionsschutz.
 */

require_once __DIR__ . '/StorageDriverInterface.php';

class PdoSqlStorageDriver implements StorageDriverInterface {
    protected ?PDO $pdo = null;
    protected string $host;
    protected int $port;
    protected string $database;
    protected string $username;
    protected string $password;
    protected string $prefix;
    protected string $charset;
    protected ?string $lastError = null;

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

    public function init(): bool {
        try {
            $pdo = $this->getPdo();
            $table = $this->prefix . 'documents';
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                `doc_key` VARCHAR(64) NOT NULL PRIMARY KEY,
                `doc_value` LONGTEXT NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log('PdoSqlStorageDriver init error: ' . $e->getMessage());
            return false;
        }
    }

    public function loadData(): array {
        try {
            $this->init();
            $pdo = $this->getPdo();
            $table = $this->prefix . 'documents';
            $stmt = $pdo->query("SELECT `doc_key`, `doc_value` FROM `{$table}`");
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                return [];
            }

            $data = [];
            foreach ($rows as $row) {
                $val = json_decode($row['doc_value'], true);
                $data[$row['doc_key']] = ($val !== null) ? $val : $row['doc_value'];
            }
            return $data;
        } catch (Exception $e) {
            error_log('PdoSqlStorageDriver loadData error: ' . $e->getMessage());
            return [];
        }
    }

    public function saveData(array $data): bool {
        try {
            $this->init();
            $pdo = $this->getPdo();
            $table = $this->prefix . 'documents';

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO `{$table}` (`doc_key`, `doc_value`) VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE `doc_value` = VALUES(`doc_value`)");

            foreach ($data as $key => $value) {
                $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $stmt->execute([
                    ':key' => (string)$key,
                    ':value' => $json
                ]);
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

