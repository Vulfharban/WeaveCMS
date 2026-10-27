<?php
/**
 * ============================================================================
 * WeaveCMS – Zentraler Medien-Manager & Bild-Pipeline (MediaManager)
 * ============================================================================
 * Phase 22:
 * - 22.1 Zentraler Medien-Manager: Übersicht, Filter nach Typ, Suche, 1-Klick Copy
 * - 22.2 Moderne Bild-Pipeline: Automatische WebP- & AVIF-Konvertierung,
 *        Standard-Größen (thumb: 350px, medium: 800px, large: 1400px),
 *        <picture>- & srcset-Helfer
 * - 22.3 Drag & Drop Multi-Upload & Dateinamen-Sanitizing
 *
 * @package WeaveCMS\Media
 * @author WeaveCMS Team
 */

require_once __DIR__ . '/ImageUploader.php';
require_once __DIR__ . '/SecurityManager.php';

class MediaManager {

    /**
     * Vordefinierte responsive Bildgrößen (maximale Breite/Höhe).
     */
    public const SIZES = [
        'thumb'  => 350,
        'medium' => 800,
        'large'  => 1400
    ];

    /**
     * Ermittelt den absoluten Speicherpfad für Medien-Uploads.
     * @return string
     */
    public static function getUploadDir(): string {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/images/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Ermittelt den Pfad zur Metadaten-Datei (Alt-Texte, Bezeichnungen).
     * @return string
     */
    public static function getMetaFile(): string {
        $root = dirname(__DIR__, 2);
        $dataDir = $root . '/data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        return $dataDir . '/media_meta.json';
    }

    /**
     * Lädt gespeicherte Metadaten (Alt-Texte, Titel).
     * @return array<string, array>
     */
    public static function loadMeta(): array {
        $file = self::getMetaFile();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert Metadaten persistent.
     * @param array $meta
     * @return bool
     */
    public static function saveMeta(array $meta): bool {
        $file = self::getMetaFile();
        return (bool)@file_put_contents($file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Formatiert Dateigrößen lesbar in B, KB oder MB.
     * @param int $bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Prüft, ob ein Dateiname ein abgeleitetes Thumbnail/Responsive-Variant ist.
     * @param string $filename
     * @return bool
     */
    public static function isVariantFile(string $filename, ?string $dir = null): bool {
        if (preg_match('/_(thumb|medium|large)\.(jpg|jpeg|png|webp|avif)$/i', $filename)) {
            return true;
        }

        $dir = $dir ?: self::getUploadDir();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $base = pathinfo($filename, PATHINFO_FILENAME);

        if ($ext === 'avif') {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $masterExt) {
                if (file_exists($dir . '/' . $base . '.' . $masterExt)) {
                    return true;
                }
            }
        }

        if ($ext === 'webp') {
            foreach (['jpg', 'jpeg', 'png'] as $masterExt) {
                if (file_exists($dir . '/' . $base . '.' . $masterExt)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Scannt das Upload-Verzeichnis und liefert alle Medien inklusive Metadaten.
     *
     * @param string|null $filterType 'all', 'images', 'documents', 'vectors'
     * @param string|null $search Suchbegriff im Dateinamen oder Alt-Text
     * @param string $sort 'date_desc', 'date_asc', 'name_asc', 'size_desc'
     * @return array<int, array>
     */
    public static function scanMedia(?string $filterType = 'all', ?string $search = null, string $sort = 'date_desc'): array {
        $uploadDir = self::getUploadDir();
        $meta = self::loadMeta();
        $files = @scandir($uploadDir) ?: [];
        $items = [];

        $imageExts = ImageUploader::ALLOWED_EXTENSIONS;
        $docExts = ImageUploader::ALLOWED_DOC_EXTENSIONS;
        $vectorExts = ['svg'];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === '.gitkeep' || $file === 'index.html') {
                continue;
            }

            // Varianten aus der Hauptansicht ausblenden
            if (self::isVariantFile($file)) {
                continue;
            }

            $absPath = $uploadDir . '/' . $file;
            if (!is_file($absPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $baseName = pathinfo($file, PATHINFO_FILENAME);

            // Typ ermitteln
            $type = 'other';
            if (in_array($ext, $imageExts, true)) {
                $type = 'image';
            } elseif (in_array($ext, $vectorExts, true)) {
                $type = 'vector';
            } elseif (in_array($ext, $docExts, true)) {
                $type = 'document';
            }

            // Filter anwenden
            if ($filterType && $filterType !== 'all') {
                if ($filterType === 'images' && $type !== 'image') continue;
                if ($filterType === 'documents' && $type !== 'document') continue;
                if ($filterType === 'vectors' && $type !== 'vector') continue;
            }

            $sizeBytes = filesize($absPath) ?: 0;
            $modTime = filemtime($absPath) ?: time();
            $fileMeta = $meta[$file] ?? [];
            $altText = (string)($fileMeta['alt'] ?? '');
            $title = (string)($fileMeta['title'] ?? $file);

            // Suchfilter
            if ($search !== null && $search !== '') {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $match = (mb_stripos($file, $searchLower, 0, 'UTF-8') !== false) ||
                         (mb_stripos($altText, $searchLower, 0, 'UTF-8') !== false) ||
                         (mb_stripos($title, $searchLower, 0, 'UTF-8') !== false);
                if (!$match) {
                    continue;
                }
            }

            // Bilddimensionen ermitteln
            $dimensions = null;
            $variants = [];
            if ($type === 'image') {
                $imgInfo = @getimagesize($absPath);
                if ($imgInfo) {
                    $dimensions = [
                        'width'  => $imgInfo[0],
                        'height' => $imgInfo[1],
                        'ratio'  => round($imgInfo[0] / max(1, $imgInfo[1]), 2)
                    ];
                }

                // Vorhandene Varianten erkennen
                foreach (self::SIZES as $sizeKey => $maxDim) {
                    $varFile = $baseName . '_' . $sizeKey . '.' . $ext;
                    if (file_exists($uploadDir . '/' . $varFile)) {
                        $variants[$sizeKey] = 'images/uploads/' . $varFile;
                    }
                    $varWebp = $baseName . '_' . $sizeKey . '.webp';
                    if (file_exists($uploadDir . '/' . $varWebp)) {
                        $variants[$sizeKey . '_webp'] = 'images/uploads/' . $varWebp;
                    }
                }

                // Standalone WebP / AVIF Version
                if ($ext !== 'webp' && file_exists($uploadDir . '/' . $baseName . '.webp')) {
                    $variants['webp'] = 'images/uploads/' . $baseName . '.webp';
                }
                if (file_exists($uploadDir . '/' . $baseName . '.avif')) {
                    $variants['avif'] = 'images/uploads/' . $baseName . '.avif';
                }
            }

            $items[] = [
                'filename'       => $file,
                'basename'       => $baseName,
                'extension'      => $ext,
                'type'           => $type,
                'rel_url'        => 'images/uploads/' . $file,
                'web_url'        => '/' . ltrim('images/uploads/' . $file, '/'),
                'size_bytes'     => $sizeBytes,
                'size_formatted' => self::formatBytes($sizeBytes),
                'mtime'          => $modTime,
                'date_formatted' => date('d.m.Y H:i', $modTime),
                'dimensions'     => $dimensions,
                'variants'       => $variants,
                'alt'            => $altText,
                'title'          => $title
            ];
        }

        // Sortierung
        usort($items, function($a, $b) use ($sort) {
            switch ($sort) {
                case 'date_asc':
                    return $a['mtime'] <=> $b['mtime'];
                case 'name_asc':
                    return strnatcasecmp($a['filename'], $b['filename']);
                case 'name_desc':
                    return strnatcasecmp($b['filename'], $a['filename']);
                case 'size_desc':
                    return $b['size_bytes'] <=> $a['size_bytes'];
                case 'size_asc':
                    return $a['size_bytes'] <=> $b['size_bytes'];
                case 'date_desc':
                default:
                    return $b['mtime'] <=> $a['mtime'];
            }
        });

        return $items;
    }

    /**
     * Erzeugt eine GD-Ressource aus einer Datei.
     * @param string $path
     * @param string $ext
     * @return \GdImage|resource|false
     */
    protected static function createGdImage(string $path, string $ext) {
        if (!extension_loaded('gd') || !file_exists($path)) {
            return false;
        }
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                return @imagecreatefromjpeg($path);
            case 'png':
                return @imagecreatefrompng($path);
            case 'webp':
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            case 'gif':
                return @imagecreatefromgif($path);
            default:
                return false;
        }
    }

    /**
     * Führt die automatische Bild-Pipeline für eine Bilddatei aus:
     * - WebP & AVIF Konvertierung des Originals
     * - Erzeugung von Standardgrößen (thumb: 350px, medium: 800px, large: 1400px) in WebP und Originalformat.
     *
     * @param string $absOriginalPath
     * @param string $ext
     * @return array<string, string> Liste der generierten Varianten
     */
    public static function processImagePipeline(string $absOriginalPath, string $ext): array {
        if (!extension_loaded('gd') || !file_exists($absOriginalPath)) {
            return [];
        }

        $imageExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $imageExts, true)) {
            return [];
        }

        $srcImg = self::createGdImage($absOriginalPath, $ext);
        if ($srcImg === false) {
            return [];
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);
        if ($origW <= 0 || $origH <= 0) {
            if (is_resource($srcImg) || (is_object($srcImg) && $srcImg instanceof \GdImage)) {
                imagedestroy($srcImg);
            }
            return [];
        }

        $dir = dirname($absOriginalPath);
        $baseName = pathinfo($absOriginalPath, PATHINFO_FILENAME);
        $generated = [];

        // 1. Standalone WebP-Version des Originals erzeugen (falls Original nicht schon WebP ist)
        if ($ext !== 'webp' && function_exists('imagewebp')) {
            $webpPath = $dir . '/' . $baseName . '.webp';
            @imagewebp($srcImg, $webpPath, 85);
            if (file_exists($webpPath)) {
                $generated['webp'] = $webpPath;
            }
        }

        // 2. Standalone AVIF-Version des Originals (wenn Server unterstützt)
        if (function_exists('imageavif')) {
            $avifPath = $dir . '/' . $baseName . '.avif';
            @imageavif($srcImg, $avifPath, 80);
            if (file_exists($avifPath)) {
                $generated['avif'] = $avifPath;
            }
        }

        // 3. Responsive Größen berechnen und speichern
        foreach (self::SIZES as $sizeKey => $maxDim) {
            // Nur verkleinern, wenn das Original größer ist als die Zielgröße
            if ($origW <= $maxDim && $origH <= $maxDim && $sizeKey !== 'thumb') {
                continue;
            }

            $ratio = min($maxDim / $origW, $maxDim / $origH);
            if ($ratio >= 1.0 && $sizeKey !== 'thumb') {
                continue;
            }

            $newW = max(1, (int)round($origW * $ratio));
            $newH = max(1, (int)round($origH * $ratio));

            $dstImg = imagecreatetruecolor($newW, $newH);
            if ($dstImg === false) {
                continue;
            }

            // Alpha-Kanal für Transparenz erhalten
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);

            // Hochwertige Resampling-Skalierung
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            // WebP-Variante erzeugen
            if (function_exists('imagewebp')) {
                $variantWebp = $dir . '/' . $baseName . '_' . $sizeKey . '.webp';
                @imagewebp($dstImg, $variantWebp, 85);
                if (file_exists($variantWebp)) {
                    $generated[$sizeKey . '_webp'] = $variantWebp;
                }
            }

            // Original-Format-Variante erzeugen
            $variantOriginal = $dir . '/' . $baseName . '_' . $sizeKey . '.' . $ext;
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    @imagejpeg($dstImg, $variantOriginal, 88);
                    break;
                case 'png':
                    @imagepng($dstImg, $variantOriginal, 8);
                    break;
                case 'webp':
                    @imagewebp($dstImg, $variantOriginal, 85);
                    break;
            }
            if (file_exists($variantOriginal)) {
                $generated[$sizeKey] = $variantOriginal;
            }

            @imagedestroy($dstImg);
        }

        @imagedestroy($srcImg);
        return $generated;
    }

    /**
     * Lädt eine einzelne Datei in die Mediathek hoch und stößt die Bild-Pipeline an.
     *
     * @param array $file Das PHP $_FILES['...'] Array-Element
     * @param string|null $customAlt Optionaler Alt-Text
     * @return array ['success' => bool, 'item' => array|null, 'error' => string]
     */
    public static function upload(array $file, ?string $customAlt = ''): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            return ['success' => false, 'item' => null, 'error' => 'Upload-Fehler (Code ' . $errCode . ')'];
        }

        $origName = $file['name'] ?? 'unbenannt';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowedImages = ImageUploader::ALLOWED_EXTENSIONS;
        $allowedDocs = ImageUploader::ALLOWED_DOC_EXTENSIONS;
        $allowedVectors = ['svg'];
        $allAllowed = array_merge($allowedImages, $allowedDocs, $allowedVectors);

        if (!in_array($ext, $allAllowed, true)) {
            SecurityManager::logEvent('MEDIA_EXT_BLOCKED', "Unerlaubte Medien-Endung: {$origName} (.{$ext})", 'WARNING');
            return ['success' => false, 'item' => null, 'error' => "Dateityp .{$ext} ist nicht erlaubt."];
        }

        $uploadDir = self::getUploadDir();

        // Magic Bytes Prüfung für Bilder & Dokumente
        if (in_array($ext, $allowedImages, true)) {
            $expected = ImageUploader::ALLOWED_IMAGE_MIMES[$ext] ?? [];
            if (!empty($expected) && !ImageUploader::verifyMimeType($file['tmp_name'], $expected)) {
                SecurityManager::logEvent('MEDIA_MIME_BLOCKED', "MIME-Type Magic Bytes ungültig für: {$origName}", 'WARNING');
                return ['success' => false, 'item' => null, 'error' => 'Dateiformat entspricht nicht dem erwarteten Bildtyp.'];
            }
        } elseif (in_array($ext, $allowedDocs, true)) {
            $expected = ImageUploader::ALLOWED_DOC_MIMES[$ext] ?? [];
            if (!empty($expected) && !ImageUploader::verifyMimeType($file['tmp_name'], $expected)) {
                SecurityManager::logEvent('MEDIA_MIME_BLOCKED', "MIME-Type Magic Bytes ungültig für Dokument: {$origName}", 'WARNING');
                return ['success' => false, 'item' => null, 'error' => 'Dateiformat entspricht nicht dem Dokumententyp.'];
            }
        } elseif ($ext === 'svg') {
            if (!ImageUploader::sanitizeSvg($file['tmp_name'])) {
                SecurityManager::logEvent('MEDIA_SVG_REJECTED', "Unsicheres SVG abgelehnt: {$origName}", 'WARNING');
                return ['success' => false, 'item' => null, 'error' => 'SVG-Datei enthält potenziell unsichere Skripte oder Tags.'];
            }
        }

        // Bereinigter, kollisionsfreier Dateiname
        $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        if (empty($cleanBase)) {
            $cleanBase = 'file';
        }
        $token = bin2hex(random_bytes(4));
        $newFilename = $cleanBase . '_' . $token . '.' . $ext;
        $destPath = $uploadDir . '/' . $newFilename;

        $moved = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $moved = move_uploaded_file($file['tmp_name'], $destPath);
        } else {
            $moved = copy($file['tmp_name'], $destPath);
        }

        if (!$moved) {
            return ['success' => false, 'item' => null, 'error' => 'Datei konnte nicht im Upload-Ordner gespeichert werden.'];
        }

        // GD-Bereinigung für Bilddateien (Neutralisierung versteckter EXIF-Payloads)
        if (in_array($ext, $allowedImages, true)) {
            ImageUploader::sanitizeImageWithGd($destPath, $ext);
            // Automatische Bild-Pipeline: WebP, AVIF & responsive Varianten
            self::processImagePipeline($destPath, $ext);
        }

        // Metadaten speichern
        $meta = self::loadMeta();
        $meta[$newFilename] = [
            'orig_name' => $origName,
            'title'     => pathinfo($origName, PATHINFO_FILENAME),
            'alt'       => trim((string)$customAlt),
            'uploaded'  => date('Y-m-d H:i:s')
        ];
        self::saveMeta($meta);

        SecurityManager::logEvent('MEDIA_UPLOAD_OK', "Datei erfolgreich hochgeladen: {$newFilename} (Original: {$origName})", 'INFO');

        // Erstelltes Item abrufen
        $items = self::scanMedia('all', $newFilename);
        $createdItem = $items[0] ?? [
            'filename'       => $newFilename,
            'rel_url'        => 'images/uploads/' . $newFilename,
            'size_formatted' => self::formatBytes(filesize($destPath) ?: 0),
            'alt'            => $customAlt
        ];

        return [
            'success' => true,
            'item'    => $createdItem,
            'error'   => ''
        ];
    }

    /**
     * Löscht ein Medium und alle seine erzeugten Varianten.
     *
     * @param string $filename
     * @return bool
     */
    public static function delete(string $filename): array {
        $safeName = basename($filename);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            return ['success' => false, 'message' => 'Ungültiger Dateiname'];
        }

        $uploadDir = self::getUploadDir();
        $target = $uploadDir . '/' . $safeName;

        if (!file_exists($target)) {
            return ['success' => false, 'message' => 'Datei existiert nicht'];
        }

        @unlink($target);

        // Abgeleitete Varianten löschen
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $files = @scandir($uploadDir) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (strpos($f, $baseName . '_') === 0 || $f === $baseName . '.webp' || $f === $baseName . '.avif') {
                @unlink($uploadDir . '/' . $f);
            }
        }

        // Metadaten entfernen
        $meta = self::loadMeta();
        if (isset($meta[$safeName])) {
            unset($meta[$safeName]);
            self::saveMeta($meta);
        }

        SecurityManager::logEvent('MEDIA_DELETED', "Medium gelöscht: {$safeName}", 'INFO');
        return ['success' => true, 'message' => 'Datei und alle Varianten erfolgreich gelöscht'];
    }

    /**
     * Aktualisiert Alt-Text und Titel für ein Medium.
     *
     * @param string $filename
     * @param string $alt
     * @param string $title
     * @return bool
     */
    public static function updateMeta(string $filename, $alt, string $title = ''): bool {
        $safeName = basename($filename);
        $meta = self::loadMeta();
        if (!isset($meta[$safeName])) {
            $meta[$safeName] = [];
        }

        if (is_array($alt)) {
            $meta[$safeName]['alt'] = trim((string)($alt['alt'] ?? ''));
            if (isset($alt['title'])) {
                $meta[$safeName]['title'] = trim((string)$alt['title']);
            }
        } else {
            $meta[$safeName]['alt'] = trim((string)$alt);
            if ($title !== '') {
                $meta[$safeName]['title'] = trim($title);
            }
        }
        return self::saveMeta($meta);
    }

    /**
     * Rendert ein performantes, barrierefreies HTML5 <picture>-Tag
     * mit AVIF-, WebP- und Fallback-Quellen für maximale Ladegeschwindigkeit.
     *
     * @param string $imagePath Relativer Webpfad (z. B. 'images/uploads/foto_abc.jpg')
     * @param string $alt Alt-Text für Barrierefreiheit
     * @param string $class CSS-Klasse(n)
     * @param string $loading 'lazy' oder 'eager'
     * @param string $sizes Responsive sizes-Attribut
     * @return string HTML-String
     */
    public static function renderPictureTag(string $imagePath, string $alt = '', string $class = '', string $loading = 'lazy', string $sizes = '100vw'): string {
        $root = dirname(__DIR__, 2);
        $cleanRel = ltrim($imagePath, '/\\');
        $absPath = $root . '/' . $cleanRel;

        if (!file_exists($absPath)) {
            if (file_exists($root . '/images/uploads/' . $cleanRel)) {
                $cleanRel = 'images/uploads/' . $cleanRel;
                $absPath = $root . '/' . $cleanRel;
            }
        }

        if (!file_exists($absPath)) {
            // Fallback auf einfaches img-Tag falls Datei nicht existiert
            return sprintf(
                '<img src="%s" alt="%s" class="%s" loading="%s">',
                htmlspecialchars('/' . $cleanRel, ENT_QUOTES),
                htmlspecialchars($alt, ENT_QUOTES),
                htmlspecialchars($class, ENT_QUOTES),
                htmlspecialchars($loading, ENT_QUOTES)
            );
        }

        $dir = pathinfo($cleanRel, PATHINFO_DIRNAME);
        $baseName = pathinfo($cleanRel, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($cleanRel, PATHINFO_EXTENSION));

        $avifSrc = null;
        $webpSrc = null;

        // Prüfen, ob AVIF-Version existiert
        if (file_exists($root . '/' . $dir . '/' . $baseName . '.avif')) {
            $avifSrc = '/' . $dir . '/' . $baseName . '.avif';
        }

        // Prüfen, ob WebP-Version existiert
        if ($ext !== 'webp' && file_exists($root . '/' . $dir . '/' . $baseName . '.webp')) {
            $webpSrc = '/' . $dir . '/' . $baseName . '.webp';
        }

        $fallbackSrc = '/' . $cleanRel;

        $html = '<picture' . ($class ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '') . '>';
        if ($avifSrc) {
            $html .= '<source type="image/avif" srcset="' . htmlspecialchars($avifSrc, ENT_QUOTES) . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '">';
        }
        if ($webpSrc) {
            $html .= '<source type="image/webp" srcset="' . htmlspecialchars($webpSrc, ENT_QUOTES) . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '">';
        }
        $html .= '<img src="' . htmlspecialchars($fallbackSrc, ENT_QUOTES) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" loading="' . htmlspecialchars($loading, ENT_QUOTES) . '">';
        $html .= '</picture>';

        return $html;
    }
}
