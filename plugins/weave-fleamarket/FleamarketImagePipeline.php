<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – Datenschutz-Foto-Pipeline (Phase 39.2)
 * ============================================================================
 * Validiert hochgeladene Bilder per Magic-Bytes (finfo_file), skaliert auf
 * sichere Dimensionen, kodiert per PHP GD neu (entfernt automatisch alle
 * EXIF-/GPS-Metadaten!) und erzeugt WebP-/JPEG-Thumbnails.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class FleamarketImagePipeline {

    public const MAX_FILE_BYTES = 10485760; // 10 MB
    public const MAX_DIM = 1600;
    public const THUMB_DIM = 480;

    protected static array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public static function getDefaultUploadDir(): string {
        return dirname(__DIR__, 2) . '/images/uploads/fleamarket';
    }

    /**
     * Prüft, skaliert und speichert eine hochgeladene Bilddatei EXIF-/GPS-frei via GD.
     */
    public static function processUploadedFile(string $tmpPath, ?string $destDir = null, string $publicPrefix = 'images/uploads/fleamarket'): array {
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            return ['success' => false, 'message' => 'Hochgeladene Bilddatei nicht gefunden.'];
        }
        $size = (int)@filesize($tmpPath);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            return ['success' => false, 'message' => 'Bilddatei überschreitet das 10-MB-Limit oder ist leer.'];
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string)finfo_file($fi, $tmpPath);
                unset($fi);
            }
        } elseif (function_exists('getimagesize')) {
            $info = @getimagesize($tmpPath);
            $mime = (string)($info['mime'] ?? '');
        }

        if (!isset(self::$allowedMimes[$mime])) {
            return ['success' => false, 'message' => 'Nicht unterstütztes Bildformat (nur JPG, PNG, WebP oder GIF erlaubt).'];
        }

        if (!extension_loaded('gd')) {
            return ['success' => false, 'message' => 'PHP GD-Erweiterung ist auf diesem Server nicht aktiviert.'];
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
            'image/gif'  => @imagecreatefromgif($tmpPath),
            default      => false,
        };
        if (!$src) {
            return ['success' => false, 'message' => 'Bild konnte nicht dekodiert werden (beschädigte Datei).'];
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        if ($origW < 1 || $origH < 1) {
            unset($src);
            return ['success' => false, 'message' => 'Ungültige Bildabmessungen.'];
        }

        $targetDir = $destDir !== null ? rtrim($destDir, '/\\') : self::getDefaultUploadDir();
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $ht = $targetDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "php_flag engine off\nRemoveHandler .php .phtml\n");
        }

        $randBase = 'fm_' . date('Ymd') . '_' . bin2hex(random_bytes(8));
        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : 'jpg';

        $mainImg = self::resizeGd($src, $origW, $origH, self::MAX_DIM);
        $mainFile = $randBase . '.' . $ext;
        $mainAbs = $targetDir . DIRECTORY_SEPARATOR . $mainFile;
        $savedMain = $useWebp
            ? @imagewebp($mainImg, $mainAbs, 84)
            : @imagejpeg($mainImg, $mainAbs, 86);
        $finalW = imagesx($mainImg);
        $finalH = imagesy($mainImg);
        if ($mainImg !== $src) {
            unset($mainImg);
        }

        $thumbImg = self::resizeGd($src, $origW, $origH, self::THUMB_DIM);
        $thumbFile = $randBase . '_thumb.' . $ext;
        $thumbAbs = $targetDir . DIRECTORY_SEPARATOR . $thumbFile;
        $savedThumb = $useWebp
            ? @imagewebp($thumbImg, $thumbAbs, 80)
            : @imagejpeg($thumbImg, $thumbAbs, 82);
        if ($thumbImg !== $src) {
            unset($thumbImg);
        }
        unset($src);

        if (!$savedMain) {
            return ['success' => false, 'message' => 'Verarbeitetes Bild konnte nicht gespeichert werden.'];
        }

        $prefix = rtrim(str_replace('\\', '/', $publicPrefix), '/');
        return [
            'success'      => true,
            'url'          => $prefix . '/' . $mainFile,
            'thumb_url'    => $prefix . '/' . ($savedThumb ? $thumbFile : $mainFile),
            'abs_path'     => $mainAbs,
            'thumb_path'   => $savedThumb ? $thumbAbs : $mainAbs,
            'width'        => $finalW,
            'height'       => $finalH,
            'exif_stripped'=> true,
        ];
    }

    /**
     * Verarbeitet vorab im Browser via <canvas> skalierte Base64-Data-URLs.
     */
    public static function processBase64Image(string $dataUrl, ?string $destDir = null, string $publicPrefix = 'images/uploads/fleamarket'): array {
        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/is', trim($dataUrl), $m)) {
            return ['success' => false, 'message' => 'Ungültiges Base64-Bildformat.'];
        }
        $raw = base64_decode($m[2], true);
        if ($raw === false || strlen($raw) > self::MAX_FILE_BYTES) {
            return ['success' => false, 'message' => 'Bilddaten ungültig oder zu groß.'];
        }
        $tmpFile = tempnam(sys_get_temp_dir(), 'wfm_');
        if ($tmpFile === false) {
            return ['success' => false, 'message' => 'Temporäre Datei konnte nicht angelegt werden.'];
        }
        file_put_contents($tmpFile, $raw);
        $res = self::processUploadedFile($tmpFile, $destDir, $publicPrefix);
        @unlink($tmpFile);
        return $res;
    }

    protected static function resizeGd($src, int $w, int $h, int $maxSide) {
        $nw = $w;
        $nh = $h;
        if ($w > $maxSide || $h > $maxSide) {
            $ratio = min($maxSide / $w, $maxSide / $h);
            $nw = max(1, (int)round($w * $ratio));
            $nh = max(1, (int)round($h * $ratio));
        }
        $dst = imagecreatetruecolor($nw, $nh);
        if (!function_exists('imagewebp')) {
            // JPEG-Fallback unterstützt keine Transparenz: weißen Hintergrund füllen
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
            imagealphablending($dst, true);
        } else {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }
}
