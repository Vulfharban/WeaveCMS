<?php
/**
 * ============================================================================
 * WeaveCMS - Bild- & Dateiupload-Manager (ImageUploader) - Phase 9.3
 * ============================================================================
 * Behandelt:
 * - Streng typisierte Uploads von Bildern und Dokumenten
 * - Echte MIME-Type Magic-Byte Prüfung via finfo_file
 * - GD-Neukompilierung zur Neutralisierung versteckter PHP-Payloads in EXIF-Daten
 * - Kryptografisch sichere, kollisionsfreie Zufallsnamen
 * - Audit-Logging verdächtiger Upload-Versuche
 * ============================================================================
 */

class ImageUploader {

    /**
     * Erlaubte Dateiendungen für Bilduploads.
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Erlaubte MIME-Types für Bilder (Magic Bytes Prüfung).
     */
    public const ALLOWED_IMAGE_MIMES = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif']
    ];

    /**
     * Erlaubte Dateiendungen für Dokumenten-Downloads.
     */
    public const ALLOWED_DOC_EXTENSIONS = ['pdf', 'zip', 'docx', 'doc', 'odt', 'txt', 'csv', 'xlsx', 'xls'];

    /**
     * Erlaubte MIME-Types für Dokumente (Magic Bytes Prüfung).
     */
    public const ALLOWED_DOC_MIMES = [
        'pdf'  => ['application/pdf'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'doc'  => ['application/msword'],
        'odt'  => ['application/vnd.oasis.opendocument.text'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel']
    ];

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
     * Prüft den echten MIME-Type einer Datei via Fileinfo (Magic Bytes) - Phase 9.3.
     * @param string $filePath
     * @param array $allowedMimes
     * @return bool
     */
    public static function verifyMimeType(string $filePath, array $allowedMimes): bool {
        if (!file_exists($filePath) || !function_exists('finfo_open')) {
            return true; // Fallback, falls finfo auf speziellen Shared-Hostern fehlt
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return true;
        }

        $mime = finfo_file($finfo, $filePath);
        if (PHP_VERSION_ID < 80500) {
            @finfo_close($finfo);
        }

        if (!$mime) {
            return false;
        }

        return in_array(strtolower((string)$mime), $allowedMimes, true);
    }

    /**
     * Kompiliert Bilddateien via GD neu, um versteckte PHP-Payloads in EXIF-Daten zu neutralisieren (Phase 9.3).
     * @param string $filePath
     * @param string $ext
     * @return bool
     */
    public static function sanitizeImageWithGd(string $filePath, string $ext): bool {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return true;
        }

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($filePath);
                if ($img !== false) {
                    @imagejpeg($img, $filePath, 90);
                    return true;
                }
                break;

            case 'png':
                $img = @imagecreatefrompng($filePath);
                if ($img !== false) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    @imagepng($img, $filePath, 8);
                    return true;
                }
                break;

            case 'webp':
                if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
                    $img = @imagecreatefromwebp($filePath);
                    if ($img !== false) {
                        @imagewebp($img, $filePath, 85);
                        return true;
                    }
                }
                return true;

            case 'gif':
                $img = @imagecreatefromgif($filePath);
                if ($img !== false) {
                    @imagegif($img, $filePath);
                    return true;
                }
                break;
        }

        // Falls Bild-Erzeugung fehlschlägt: Verdächtige oder korrupte Datei
        return false;
    }

    /**
     * Führt einen sicheren Bildupload mit MIME-Type-Prüfung, GD-Bereinigung und Zufallsnamen durch.
     * @param string $fileInputName Name des POST-File-Inputs
     * @param string|null $uploadRelDir Optionaler individueller Zielordner relativ zum Root
     * @return string|null Relativer Webpfad zur gespeicherten Datei oder null bei Fehler
     */
    public static function upload(string $fileInputName, ?string $uploadRelDir = null): ?string {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Phase 15: Sandbox-Upload-Beschränkung & Abfang-Hook
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            if (!SandboxManager::canUploadFile()) {
                SecurityManager::logEvent('SANDBOX_UPLOAD_LIMIT', "Sandbox-Upload-Limit erreicht (max. 5 Bilder je Sitzung).", 'WARNING');
                return null;
            }
        }

        $file = $_FILES[$fileInputName];
        $origName = $file['name'] ?? 'unknown';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // 1. Dateiendung validieren
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            SecurityManager::logEvent('UPLOAD_EXT_BLOCKED', "Unerlaubte Bildendung blockiert: {$origName} (.{$ext})", 'WARNING');
            return null;
        }

        // 2. MIME-Type Magic Bytes prüfen
        $expectedMimes = self::ALLOWED_IMAGE_MIMES[$ext] ?? [];
        if (!empty($expectedMimes) && !self::verifyMimeType($file['tmp_name'], $expectedMimes)) {
            SecurityManager::logEvent('UPLOAD_MIME_BLOCKED', "Manipulierter MIME-Type für Bild blockiert: {$origName}", 'WARNING');
            return null;
        }

        $root = dirname(__DIR__, 2);

        if ($uploadRelDir === null) {
            $dataFile = $root . '/data.json';
            $data = file_exists($dataFile) ? json_decode((string)file_get_contents($dataFile), true) : null;
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

        // 3. Kryptografisch sicheren Dateinamen generieren
        $newFilename = 'img_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $dest = rtrim($uploadDir, '/\\') . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // 4. GD-Neukompilierung (EXIF-PHP-Bereinigung)
            if (!self::sanitizeImageWithGd($dest, $ext)) {
                @unlink($dest);
                SecurityManager::logEvent('UPLOAD_GD_FAILED', "Bilddatei konnte nicht sicher kompiliert werden: {$origName}", 'WARNING');
                return null;
            }

            SecurityManager::logEvent('UPLOAD_IMAGE_OK', "Bild erfolgreich hochgeladen: {$newFilename} (Original: {$origName})", 'INFO');
            $uploadedRel = rtrim($webPrefix, '/') . '/' . $newFilename;
            if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
                SandboxManager::recordUploadedFile($dest, $uploadedRel);
            }
            return $uploadedRel;
        }

        return null;
    }

    /**
     * Führt einen sicheren Dokumentenupload für PDFs und Dateien durch (Phase 9.3).
     * @param string $fileInputName
     * @param string|null $uploadRelDir
     * @return array|null ['path' => string, 'size' => string, 'orig_name' => string]
     */
    public static function uploadDocument(string $fileInputName, ?string $uploadRelDir = 'downloads'): ?array {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fileInputName];
        $origName = $file['name'] ?? 'document';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // 1. Dateiendung prüfen
        if (!in_array($ext, self::ALLOWED_DOC_EXTENSIONS, true)) {
            SecurityManager::logEvent('UPLOAD_DOC_EXT_BLOCKED', "Unerlaubte Dokumenten-Endung blockiert: {$origName} (.{$ext})", 'WARNING');
            return null;
        }

        // 2. MIME-Type Magic Bytes prüfen
        $expectedMimes = self::ALLOWED_DOC_MIMES[$ext] ?? [];
        if (!empty($expectedMimes) && !self::verifyMimeType($file['tmp_name'], $expectedMimes)) {
            SecurityManager::logEvent('UPLOAD_DOC_MIME_BLOCKED', "Manipulierter Dokument-MIME-Type blockiert: {$origName}", 'WARNING');
            return null;
        }

        $root = dirname(__DIR__, 2);
        $cleanRel = trim($uploadRelDir ?? 'downloads', '/\\');
        $uploadDir = $root . '/' . $cleanRel . '/';
        $webPrefix = $cleanRel;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // 3. Sicheren, sauberen Dateinamen mit Zufallstoken generieren
        $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $newFilename = $cleanBase . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = rtrim($uploadDir, '/\\') . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $bytes = filesize($dest);
            $sizeStr = ($bytes >= 1048576) ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024, 0) . ' KB';

            SecurityManager::logEvent('UPLOAD_DOC_OK', "Dokument erfolgreich hochgeladen: {$newFilename} (Original: {$origName})", 'INFO');

            return [
                'path' => $webPrefix . '/' . $newFilename,
                'size' => $sizeStr,
                'orig_name' => $origName
            ];
        }

        return null;
    }

    /**
     * Erlaubte Dateiendungen für Logos.
     */
    public const ALLOWED_LOGO_EXTENSIONS = ['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'];

    /**
     * Erlaubte Dateiendungen für Favicons.
     */
    public const ALLOWED_FAVICON_EXTENSIONS = ['ico', 'svg', 'png'];

    /**
     * Bereinigt und validiert SVG-Inhalte gegen XSS-Injektionen.
     * @param string $filePath
     * @return bool
     */
    public static function sanitizeSvg(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        $content = (string)file_get_contents($filePath);
        if (empty($content)) {
            return false;
        }

        // Grundlegende Prüfung auf SVG-Wurzelelement
        if (stripos($content, '<svg') === false) {
            return false;
        }

        // Bösartige Skripte, Event-Handler und Remote-Scripts erkennen & ablehnen
        $dangerousPatterns = [
            '/<script\b[^>]*>/i',
            '/<\/script>/i',
            '/on\w+\s*=/i',
            '/javascript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/<iframe\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<object\b[^>]*>/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Lädt ein Logo hoch (unterstützt SVG, PNG, WebP, JPG, GIF).
     * @param string $fileInputName
     * @return string|null Relativer Webpfad (z. B. '/images/branding/logo_xyz.svg')
     */
    public static function uploadLogo(string $fileInputName): ?string {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fileInputName];
        $origName = $file['name'] ?? 'logo';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_LOGO_EXTENSIONS, true)) {
            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('UPLOAD_LOGO_EXT_BLOCKED', "Unerlaubte Logo-Endung: {$origName} (.{$ext})", 'WARNING');
            }
            return null;
        }

        // SVG gesondert prüfen & desinfizieren
        if ($ext === 'svg') {
            if (!self::sanitizeSvg($file['tmp_name'])) {
                if (class_exists('SecurityManager')) {
                    SecurityManager::logEvent('UPLOAD_SVG_REJECTED', "Unsichere oder korrupte SVG-Datei abgelehnt: {$origName}", 'WARNING');
                }
                return null;
            }
        } else {
            // Standard-Bilder via Magic Bytes & GD prüfen
            $expectedMimes = self::ALLOWED_IMAGE_MIMES[$ext] ?? [];
            if (!empty($expectedMimes) && !self::verifyMimeType($file['tmp_name'], $expectedMimes)) {
                if (class_exists('SecurityManager')) {
                    SecurityManager::logEvent('UPLOAD_MIME_BLOCKED', "Ungültiger MIME-Type für Logo: {$origName}", 'WARNING');
                }
                return null;
            }
            if (!self::sanitizeImageWithGd($file['tmp_name'], $ext)) {
                return null;
            }
        }

        $root = dirname(__DIR__, 2);
        $brandDir = $root . '/images/branding/';
        if (!is_dir($brandDir)) {
            @mkdir($brandDir, 0755, true);
        }

        $newFilename = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $brandDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('UPLOAD_LOGO_OK', "Neues Logo hochgeladen: {$newFilename}", 'INFO');
            }
            return '/images/branding/' . $newFilename;
        }

        return null;
    }

    /**
     * Lädt ein Favicon hoch (unterstützt ICO, SVG, PNG).
     * @param string $fileInputName
     * @return string|null Relativer Webpfad (z. B. '/images/branding/favicon_xyz.ico')
     */
    public static function uploadFavicon(string $fileInputName): ?string {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fileInputName];
        $origName = $file['name'] ?? 'favicon';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_FAVICON_EXTENSIONS, true)) {
            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('UPLOAD_FAVICON_EXT_BLOCKED', "Unerlaubte Favicon-Endung: {$origName} (.{$ext})", 'WARNING');
            }
            return null;
        }

        if ($ext === 'svg') {
            if (!self::sanitizeSvg($file['tmp_name'])) {
                return null;
            }
        }

        $root = dirname(__DIR__, 2);
        $brandDir = $root . '/images/branding/';
        if (!is_dir($brandDir)) {
            @mkdir($brandDir, 0755, true);
        }

        $newFilename = 'favicon_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $brandDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('UPLOAD_FAVICON_OK', "Neues Favicon hochgeladen: {$newFilename}", 'INFO');
            }
            return '/images/branding/' . $newFilename;
        }

        return null;
    }
}
