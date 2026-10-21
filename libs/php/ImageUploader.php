<?php
/**
 * ============================================================================
 * WeaveCMS - Bild- & Dateiupload-Manager (ImageUploader)
 * ============================================================================
 * Behandelt sichere Uploads von Bildern, Typprüfung, eindeutige Namensvergabe
 * und Bereitstellung von Zielpfaden.
 */

class ImageUploader {

    /**
     * Erlaubte Dateiendungen für Bilduploads.
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Ermittelt die konfigurierten Upload-Pfade (relativer Webpfad und absoluter Serverpfad).
     * @param array|null $data Geladene Gesamtdaten aus data.json
     * @return array ['rel' => string, 'abs' => string]
     */
    public static function getUploadConfig(?array $data = null): array {
        $root = dirname(__DIR__, 2);
        $relPath = 'images/uploads';
        if ($data && isset($data['settings']['upload_dir']) && !empty(trim($data['settings']['upload_dir']))) {
            $relPath = trim($data['settings']['upload_dir'], '/\\');
        }

        $absPath = $root . '/' . $relPath . '/';
        if (!is_dir($absPath)) {
            @mkdir($absPath, 0755, true);
        }

        return [
            'rel' => '/' . $relPath,
            'abs' => $absPath
        ];
    }

    /**
     * Führt einen sicheren Bildupload mit Dateiendungsprüfung und eindeutiger Namensvergabe durch.
     * @param string $fileInputName Name des POST-File-Inputs
     * @param string|null $uploadRelDir Optionaler individueller Zielordner relativ zum Root
     * @return string|null Relativer Webpfad zur gespeicherten Datei oder null bei Fehler
     */
    public static function upload(string $fileInputName, ?string $uploadRelDir = null): ?string {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $root = dirname(__DIR__, 2);

        if ($uploadRelDir === null) {
            $dataFile = $root . '/data.json';
            $data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : null;
            $conf = self::getUploadConfig($data);
            $uploadDir = $conf['abs'];
            $webPrefix = $conf['rel'];
        } else {
            $cleanRel = trim($uploadRelDir, '/\\');
            $uploadDir = $root . '/' . $cleanRel . '/';
            $webPrefix = '/' . $cleanRel;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
        }

        $file = $_FILES[$fileInputName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        // Eindeutigen Dateinamen generieren
        $newFilename = uniqid('img_', true) . '.' . $ext;
        $dest = rtrim($uploadDir, '/\\') . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return rtrim($webPrefix, '/') . '/' . $newFilename;
        }

        return null;
    }

    /**
     * Erlaubte Dateiendungen für Dokumenten-Downloads.
     */
    public const ALLOWED_DOC_EXTENSIONS = ['pdf', 'zip', 'docx', 'doc', 'odt', 'txt', 'csv', 'xlsx', 'xls'];

    /**
     * Führt einen sicheren Dokumentenupload für PDFs und Dateien durch.
     * @param string $fileInputName
     * @param string|null $uploadRelDir
     * @return array|null ['path' => string, 'size' => string, 'orig_name' => string]
     */
    public static function uploadDocument(string $fileInputName, ?string $uploadRelDir = 'downloads'): ?array {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $root = dirname(__DIR__, 2);
        $cleanRel = trim($uploadRelDir ?? 'downloads', '/\\');
        $uploadDir = $root . '/' . $cleanRel . '/';
        $webPrefix = $cleanRel;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES[$fileInputName];
        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_DOC_EXTENSIONS, true)) {
            return null;
        }

        $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $newFilename = $cleanBase . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
        $dest = rtrim($uploadDir, '/\\') . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $bytes = filesize($dest);
            $sizeStr = ($bytes >= 1048576) ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024, 0) . ' KB';
            return [
                'path' => $webPrefix . '/' . $newFilename,
                'size' => $sizeStr,
                'orig_name' => $origName
            ];
        }

        return null;
    }
}

