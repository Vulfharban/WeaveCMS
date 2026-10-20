<?php
/**
 * WeaveCMS - Integriertes Backup- & Wiederherstellungssystem (Backup/Restore)
 * 
 * Unterstützt:
 * - 1-Klick-Sicherung von Daten (JSON) und Vollarchiv inkl. Bilder (ZIP)
 * - Geschütztes Verzeichnis backups/ (.htaccess Schutz)
 * - PIN-geschützte Wiederherstellung (Restore) aus bestehendem Backup oder Dateiupload
 * - Automatischer Notfall-Snapshot vor jedem Restore
 * - Download und Bereinigung von Sicherungen
 */

class BackupManager {

    /**
     * Ermittelt den absoluten Pfad zum Backup-Verzeichnis und stellt dessen Schutz sicher.
     * @return string Absoluter Pfad mit abschließendem Slash
     */
    public static function getBackupDir() {
        $dir = __DIR__ . '/../../backups/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // .htaccess Zugriffsschutz sicherstellen
        $htaccess = $dir . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Apache 2.4\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n# Apache 2.2\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $content);
        }

        // index.html Schutz gegen Directory Listing
        $indexHtml = $dir . 'index.html';
        if (!file_exists($indexHtml)) {
            @file_put_contents($indexHtml, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>');
        }

        return $dir;
    }

    /**
     * Formatiert Dateigrößen in lesbare Einheiten (B, KB, MB, GB).
     * @param int $bytes Dateigröße in Bytes
     * @param int $decimals Nachkommastellen
     * @return string Formatierte Dateigröße
     */
    public static function formatBytes($bytes, $decimals = 2) {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        return round($bytes / pow(1024, $power), $decimals) . ' ' . $units[$power];
    }

    /**
     * Erstellt ein Backup (Typ: 'data' oder 'full').
     * @param string $type 'data' (nur data.json) oder 'full' (data.json + images/uploads/ als ZIP)
     * @param array|null $data Geladene Daten aus data.json
     * @return array ['success' => bool, 'filename' => string, 'error' => string|null]
     */
    public static function createBackup($type = 'full', $data = null) {
        $backupDir = self::getBackupDir();
        $dateStr = date('Y-m-d_H-i-s');
        $dataFile = __DIR__ . '/../../data.json';

        if (!file_exists($dataFile)) {
            return ['success' => false, 'filename' => '', 'error' => 'Die zentrale Datendatei data.json wurde nicht gefunden.'];
        }

        if ($type === 'data') {
            $filename = 'backup_data_' . $dateStr . '.json';
            $targetPath = $backupDir . $filename;
            
            $content = file_get_contents($dataFile);
            if ($content === false || file_put_contents($targetPath, $content) === false) {
                return ['success' => false, 'filename' => '', 'error' => 'Konnte Datensicherung nicht in ' . $filename . ' schreiben.'];
            }

            return ['success' => true, 'filename' => $filename, 'error' => null];
        }

        // Vollständiges Backup als ZIP
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'filename' => '', 'error' => 'PHP ZipArchive-Erweiterung ist auf diesem Server nicht verfügbar.'];
        }

        $filename = 'backup_full_' . $dateStr . '.zip';
        $targetZip = $backupDir . $filename;

        $zip = new ZipArchive();
        if ($zip->open($targetZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'filename' => '', 'error' => 'Konnte ZIP-Archiv ' . $filename . ' nicht erstellen.'];
        }

        // 1. data.json hinzufügen
        $zip->addFile($dataFile, 'data.json');

        // 2. Upload-Dateien hinzufügen
        $uploadCfg = get_upload_config($data);
        $uploadAbs = $uploadCfg['abs'];
        $uploadRel = trim($uploadCfg['rel'], '/\\');
        $filesCount = 0;

        if (is_dir($uploadAbs)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadAbs, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $subPath = substr($item->getPathname(), strlen($uploadAbs));
                $zipPath = $uploadRel . '/' . str_replace('\\', '/', ltrim($subPath, '/\\'));
                if ($item->isDir()) {
                    $zip->addEmptyDir($zipPath);
                } elseif ($item->isFile()) {
                    $zip->addFile($item->getPathname(), $zipPath);
                    $filesCount++;
                }
            }
        }

        // 3. Manifest hinzufügen
        $manifest = [
            'generator' => 'WeaveCMS Backup System',
            'version' => '0.2.0',
            'created_at' => date('c'),
            'type' => 'full',
            'files_count' => $filesCount + 1,
            'upload_dir' => $uploadRel
        ];
        $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->close();

        return ['success' => true, 'filename' => $filename, 'error' => null];
    }

    /**
     * Listet alle vorhandenen Backups auf, sortiert vom neuesten zum ältesten.
     * @return array Liste von Backup-Metadaten
     */
    public static function listBackups() {
        $backupDir = self::getBackupDir();
        $files = scandir($backupDir);
        $backups = [];

        if (!$files) return [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === 'index.html') {
                continue;
            }

            $path = $backupDir . $f;
            if (!is_file($path)) continue;

            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['zip', 'json'])) continue;

            $isFull = ($ext === 'zip' || strpos($f, 'backup_full_') === 0);
            $type = $isFull ? 'full' : 'data';
            $size = filesize($path);
            $mtime = filemtime($path);

            $backups[] = [
                'filename' => $f,
                'type' => $type,
                'extension' => $ext,
                'size_bytes' => $size,
                'size_formatted' => self::formatBytes($size),
                'mtime' => $mtime,
                'date_formatted' => date('d.m.Y H:i:s', $mtime)
            ];
        }

        // Nach Datum absteigend sortieren
        usort($backups, function($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        return $backups;
    }

    /**
     * Stellt eine vorhandene Backup-Datei aus dem backups/-Ordner wieder her.
     * @param string $filename Name der Datei im backups/-Ordner
     * @param string $pin Eingegebene Administrator-PIN zur Sicherheitsüberprüfung
     * @param array|null $data Aktuelle Konfigurationsdaten
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function restoreExistingBackup($filename, $pin, $data = null) {
        if (!verify_admin_pin($pin, $data)) {
            return ['success' => false, 'error' => 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene PIN ist nicht korrekt. Die Wiederherstellung wurde verweigert.'];
        }

        $backupDir = self::getBackupDir();
        $safeFilename = basename($filename);
        $filePath = $backupDir . $safeFilename;

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'Die ausgewählte Backup-Datei existiert nicht.'];
        }

        return self::applyRestore($filePath, $data);
    }

    /**
     * Stellt eine hochgeladene Backup-Datei (ZIP oder JSON) wieder her.
     * @param array $fileInput $_FILES-Array der hochgeladenen Datei
     * @param string $pin Eingegebene Administrator-PIN zur Sicherheitsüberprüfung
     * @param array|null $data Aktuelle Konfigurationsdaten
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function restoreUploadedBackup($fileInput, $pin, $data = null) {
        if (!verify_admin_pin($pin, $data)) {
            return ['success' => false, 'error' => 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene PIN ist nicht korrekt. Die Wiederherstellung wurde verweigert.'];
        }

        if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Fehler beim Datei-Upload oder keine Datei ausgewählt.'];
        }

        $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'json'])) {
            return ['success' => false, 'error' => 'Ungültiges Dateiformat. Es werden nur .zip (Vollarchiv) und .json (Daten) akzeptiert.'];
        }

        // Datei temporär ins Backup-Verzeichnis schieben für die Ausführung
        $backupDir = self::getBackupDir();
        $tempFilename = 'upload_restore_' . date('Y-m-d_H-i-s') . '.' . $ext;
        $tempPath = $backupDir . $tempFilename;

        if (!move_uploaded_file($fileInput['tmp_name'], $tempPath)) {
            return ['success' => false, 'error' => 'Konnte die hochgeladene Sicherung nicht verarbeiten.'];
        }

        $result = self::applyRestore($tempPath, $data);

        // Temporäre Upload-Datei nach erfolgreicher oder fehlgeschlagener Wiederherstellung behalten oder aufräumen
        return $result;
    }

    /**
     * Interne Routine zur Durchführung der eigentlichen Wiederherstellung.
     * @param string $sourcePath Absoluter Pfad zur Backup-Datei (JSON oder ZIP)
     * @param array|null $data Aktuelle Konfiguration
     * @return array ['success' => bool, 'error' => string|null]
     */
    private static function applyRestore($sourcePath, $data = null) {
        $dataFile = __DIR__ . '/../../data.json';
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        // 1. Zwingender Notfall-Snapshot vor dem Überschreiben!
        self::createBackup('data', $data);

        if ($ext === 'json') {
            $content = file_get_contents($sourcePath);
            $decoded = json_decode($content, true);

            if (!is_array($decoded) || !isset($decoded['settings']) || !isset($decoded['texts'])) {
                return ['success' => false, 'error' => 'Die JSON-Sicherung ist beschädigt oder enthält kein gültiges WeaveCMS-Datenformat.'];
            }

            if (file_put_contents($dataFile, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                return ['success' => false, 'error' => 'Konnte data.json nicht mit den Daten der Sicherung überschreiben.'];
            }

            return ['success' => true, 'error' => null];
        }

        if ($ext === 'zip') {
            if (!class_exists('ZipArchive')) {
                return ['success' => false, 'error' => 'PHP ZipArchive-Erweiterung ist auf diesem Server nicht verfügbar.'];
            }

            $zip = new ZipArchive();
            if ($zip->open($sourcePath) !== true) {
                return ['success' => false, 'error' => 'Konnte das ZIP-Archiv nicht öffnen oder die Datei ist beschädigt.'];
            }

            // 1. Prüfen ob data.json im ZIP enthalten ist
            $dataJsonContent = $zip->getFromName('data.json');
            if ($dataJsonContent === false) {
                $zip->close();
                return ['success' => false, 'error' => 'Ungültiges Archiv: Im ZIP-Archiv befindet sich keine data.json.'];
            }

            $decoded = json_decode($dataJsonContent, true);
            if (!is_array($decoded) || !isset($decoded['settings'])) {
                $zip->close();
                return ['success' => false, 'error' => 'Ungültige Datendatei: Die im Archiv enthaltene data.json ist beschädigt.'];
            }

            // 2. data.json wiederherstellen
            file_put_contents($dataFile, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // 3. Bilder / Uploads aus dem Archiv entpacken
            $rootDir = realpath(__DIR__ . '/../../') . '/';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                
                // Sicherheit: Path Traversal verhindern
                if (strpos($entryName, '..') !== false) continue;
                if ($entryName === 'data.json' || $entryName === 'backup_manifest.json') continue;

                // Nur in images/ oder uploads/ entpacken
                if (strpos($entryName, 'images/') === 0 || strpos($entryName, 'uploads/') === 0) {
                    $targetPath = $rootDir . $entryName;
                    
                    if (substr($entryName, -1) === '/') {
                        if (!is_dir($targetPath)) @mkdir($targetPath, 0755, true);
                    } else {
                        $parentDir = dirname($targetPath);
                        if (!is_dir($parentDir)) @mkdir($parentDir, 0755, true);
                        file_put_contents($targetPath, $zip->getFromIndex($i));
                    }
                }
            }

            $zip->close();
            return ['success' => true, 'error' => null];
        }

        return ['success' => false, 'error' => 'Unbekanntes Sicherungsformat.'];
    }

    /**
     * Löscht eine Sicherungsdatei aus dem backups/-Verzeichnis.
     * @param string $filename Name der Datei
     * @return bool true bei Erfolg
     */
    public static function deleteBackup($filename) {
        $backupDir = self::getBackupDir();
        $safeFilename = basename($filename);
        $filePath = $backupDir . $safeFilename;

        if (file_exists($filePath) && is_file($filePath)) {
            return @unlink($filePath);
        }
        return false;
    }

    /**
     * Sendet eine Sicherungsdatei als HTTP-Download an den Browser.
     * @param string $filename Name der Datei
     */
    public static function downloadBackup($filename) {
        $backupDir = self::getBackupDir();
        $safeFilename = basename($filename);
        $filePath = $backupDir . $safeFilename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            http_response_code(404);
            die('Backup-Datei nicht gefunden.');
        }

        $ext = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));
        $contentType = ($ext === 'zip') ? 'application/zip' : 'application/json';

        // Puffer leeren
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
