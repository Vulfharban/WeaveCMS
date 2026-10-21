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
}
