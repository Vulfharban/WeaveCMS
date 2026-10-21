<?php
/**
 * ============================================================================
 * WeaveCMS Flat-File JSON Storage Driver
 * ============================================================================
 * Atomarer, dateibasierter JSON-Speicher (data.json).
 * Funktioniert ohne Datenbank auf jedem Shared-Hosting-Webspace.
 */

require_once __DIR__ . '/StorageDriverInterface.php';

class JsonStorageDriver implements StorageDriverInterface {
    protected string $filePath;

    public function __construct(?string $filePath = null) {
        $this->filePath = $filePath ?: dirname(__DIR__, 3) . '/data.json';
    }

    public function init(): bool {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return is_writable($dir);
    }

    public function loadData(): array {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $raw = @file_get_contents($this->filePath);
        if ($raw === false || empty($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Schreibt Daten atomar via Temporärdatei und Rename.
     * Verhindert Dateibeschädigungen bei abrupten Server-Abbrüchen.
     */
    public function saveData(array $data): bool {
        $this->init();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $dir = dirname($this->filePath);
        $tempPath = $dir . '/.data_' . uniqid('weave_', true) . '.tmp';

        $bytes = @file_put_contents($tempPath, $json, LOCK_EX);
        if ($bytes === false) {
            // Direkter Fallback
            return @file_put_contents($this->filePath, $json, LOCK_EX) !== false;
        }

        // Unter Windows muss vor einem atomaren rename eine ggf. vorhandene Datei
        // manchmal zuerst gelöscht werden, oder copy+unlink verwendet werden.
        if (DIRECTORY_SEPARATOR === '\\' && file_exists($this->filePath)) {
            if (!@rename($tempPath, $this->filePath)) {
                @copy($tempPath, $this->filePath);
                @unlink($tempPath);
                return true;
            }
            return true;
        }

        if (!@rename($tempPath, $this->filePath)) {
            @unlink($tempPath);
            return @file_put_contents($this->filePath, $json, LOCK_EX) !== false;
        }

        return true;
    }

    public function ping(): bool {
        $dir = dirname($this->filePath);
        return is_dir($dir) && (file_exists($this->filePath) ? is_writable($this->filePath) : is_writable($dir));
    }

    public function getDriverName(): string {
        return 'Flat-File (JSON)';
    }

    public function getFilePath(): string {
        return $this->filePath;
    }
}
