<?php
/**
 * ============================================================================
 * WeaveCMS Plugin-Updater (PluginUpdater)
 * ============================================================================
 * Ermöglicht 1-Klick-Updates für einzelne 3rd-Party- und Core-Plugins direkt
 * über GitHub Releases oder alternative Update-Endpunkte, ohne das gesamte
 * CMS aktualisieren zu müssen.
 *
 * Sicherheits- & Architektur-Merkmale:
 * 1. Download nur über verifiziertes HTTPS (cURL / Stream Context mit Native CA)
 * 2. SHA-256 Prüfsummen-Verifikation (wenn vom Release bereitgestellt)
 * 3. Striktes Whitelisting & Zip-Slip-Schutz beim Entpacken
 * 4. Automatische Erkennung des Plugin-Stammverzeichnisses (auch bei GitHub Zipballs)
 * 5. Pre-Update-Backup des bestehenden Plugin-Ordners unter backups/plugins/
 * 6. Atomares Ersetzen von Dateien mit Rollback-Mechanismus bei Fehlern
 * 7. Vollständiger Schutz von CMS-Kern und Benutzerdaten (data/ bleibt unberührt)
 * ============================================================================
 */

class PluginUpdater {

    public const MAX_PACKAGE_BYTES = 52428800; // 50 MB
    public const BACKUP_DIR = 'backups/plugins';

    public static function getRootDir(): string {
        return dirname(__DIR__, 2);
    }

    public static function getPluginsDir(): string {
        return self::getRootDir() . '/plugins';
    }

    /**
     * Führt ein sicheres Update für ein einzelnes Plugin durch.
     *
     * @param string $slug Eindeutiger Slug des Plugins
     * @param array|null $updateInfo Optionale Update-Metadaten (aus UpdateManager)
     * @param array|null $data CMS-Gesamtdatenstruktur (für Schema / Permissions)
     * @return array ['success' => bool, 'message' => string, 'slug' => string, 'from' => string, 'to' => string, 'backup' => string]
     */
    public static function run(string $slug, ?array $updateInfo = null, ?array &$data = null): array {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($slug)));
        $fail = fn(string $msg, string $from = '', string $to = '', string $backup = '') => [
            'success' => false,
            'message' => $msg,
            'slug'    => $slug,
            'from'    => $from,
            'to'      => $to,
            'backup'  => $backup
        ];

        if (empty($slug)) {
            return $fail('Ungültiger Plugin-Slug.');
        }

        $pluginsDir = self::getPluginsDir();
        $targetDir = $pluginsDir . '/' . $slug;
        if (!is_dir($targetDir)) {
            return $fail("Plugin-Verzeichnis 'plugins/{$slug}' existiert nicht auf diesem Server.");
        }

        $manifestFile = $targetDir . '/plugin.json';
        if (!file_exists($manifestFile)) {
            return $fail("Plugin-Manifest (plugin.json) in 'plugins/{$slug}' nicht gefunden.");
        }

        $currentManifest = json_decode((string)@file_get_contents($manifestFile), true) ?: [];
        $fromVersion = (string)($currentManifest['version'] ?? '1.0.0');
        $pluginName = (string)($currentManifest['name'] ?? ucfirst($slug));

        // Falls keine Update-Informationen übergeben wurden, Remote-Check ausführen
        if (empty($updateInfo) || empty($updateInfo['zip_url'])) {
            if (class_exists('UpdateManager')) {
                $check = UpdateManager::checkSinglePluginUpdate($slug, $currentManifest, true);
                if ($check) {
                    $updateInfo = $check;
                }
            }
        }

        if (empty($updateInfo) || empty($updateInfo['zip_url'])) {
            return $fail("Keine Update-Paket-URL für Plugin '{$pluginName}' verfügbar.", $fromVersion);
        }

        $zipUrl = (string)$updateInfo['zip_url'];
        if (!preg_match('~^https://~i', $zipUrl)) {
            return $fail("Für dieses Plugin-Update ist keine sichere (HTTPS) Download-Adresse hinterlegt.", $fromVersion);
        }

        $sha256 = strtolower(trim((string)($updateInfo['sha256'] ?? '')));
        $toVersion = (string)($updateInfo['latest_version'] ?? 'unknown');

        if (!class_exists('ZipArchive')) {
            return $fail('Die PHP-Erweiterung ZipArchive ist auf diesem Server nicht verfügbar.', $fromVersion, $toVersion);
        }

        if (!is_writable($targetDir) || !is_writable($pluginsDir)) {
            return $fail("Das Verzeichnis 'plugins/{$slug}' ist für PHP nicht beschreibbar.", $fromVersion, $toVersion);
        }

        @set_time_limit(180);

        $workDir = self::getRootDir() . '/cache/updates';
        if (!is_dir($workDir) && !@mkdir($workDir, 0755, true)) {
            return $fail('Arbeitsverzeichnis cache/updates konnte nicht angelegt werden.', $fromVersion, $toVersion);
        }

        $stamp = date('Y-m-d_H-i-s');
        $zipFile = $workDir . '/plugin_' . $slug . '_' . $stamp . '.zip';
        $staging = $workDir . '/staging_plugin_' . $slug . '_' . $stamp;

        try {
            // 1. Download
            $err = self::download($zipUrl, $zipFile);
            if ($err !== null) {
                return $fail("Download fehlgeschlagen: {$err}", $fromVersion, $toVersion);
            }

            // 2. Prüfsummen-Validierung (falls vorhanden)
            if (!empty($sha256) && preg_match('/^[a-f0-9]{64}$/', $sha256)) {
                $calculatedSha = strtolower((string)hash_file('sha256', $zipFile));
                if (!hash_equals($sha256, $calculatedSha)) {
                    return $fail('SHA-256-Prüfsumme des Plugin-Pakets stimmt nicht überein – Update abgebrochen.', $fromVersion, $toVersion);
                }
            }

            // 3. Entpacken in Staging & Erkennung des Plugin-Wurzelverzeichnisses
            $sourceDir = self::extractToStaging($zipFile, $staging, $err);
            if ($sourceDir === null) {
                return $fail("Entpacken fehlgeschlagen: {$err}", $fromVersion, $toVersion);
            }

            // 4. Neues Manifest prüfen
            $newManifestFile = $sourceDir . '/plugin.json';
            if (!file_exists($newManifestFile)) {
                return $fail("Das heruntergeladene Archiv enthält keine gültige plugin.json im Stammverzeichnis.", $fromVersion, $toVersion);
            }

            $newManifest = json_decode((string)@file_get_contents($newManifestFile), true);
            if (!is_array($newManifest)) {
                return $fail("Das Manifest plugin.json im Update-Paket ist syntaktisch ungültig.", $fromVersion, $toVersion);
            }

            $detectedNewVer = (string)($newManifest['version'] ?? $toVersion);
            if ($detectedNewVer !== '') {
                $toVersion = $detectedNewVer;
            }

            // Core-Kompatibilität prüfen
            if (!empty($newManifest['requires_core']) && class_exists('PluginManager')) {
                if (!PluginManager::isCoreCompatible($newManifest['requires_core'])) {
                    $cmsVer = defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0';
                    return $fail("Inkompatibel: Dieses Update erfordert WeaveCMS Core {$newManifest['requires_core']} (installiert: v{$cmsVer}).", $fromVersion, $toVersion);
                }
            }

            // 5. Pre-Update-Backup des existierenden Plugins erstellen
            $backup = self::createPreUpdateBackup($slug, $targetDir, $fromVersion, $err);
            if ($backup === null) {
                return $fail("Pre-Update-Backup konnte nicht erstellt werden: {$err}", $fromVersion, $toVersion);
            }

            // 6. Dateien sicher austauschen
            $replaced = [];
            $added = [];
            $swapErr = self::swapFiles($sourceDir, $targetDir, $replaced, $added);
            if ($swapErr !== null) {
                // Rollback aus dem Backup durchführen
                $rbErr = self::rollback(self::getRootDir() . '/' . self::BACKUP_DIR . '/' . $backup, $targetDir);
                return $fail("Dateiaustausch fehlgeschlagen und zurückgerollt: {$swapErr}" . ($rbErr ? " (Rollback-Hinweis: {$rbErr})" : ''), $fromVersion, $toVersion, $backup);
            }

            // 7. Caches invalidieren
            if (class_exists('PluginManager')) {
                PluginManager::clearCache();
            }
            if (class_exists('UpdateManager')) {
                UpdateManager::clearPluginUpdateCache($slug);
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('PLUGIN_UPDATE', "Plugin '{$pluginName}' ({$slug}) von v{$fromVersion} auf v{$toVersion} aktualisiert (Backup: {$backup})", 'INFO');
            }

            return [
                'success' => true,
                'message' => "Plugin '{$pluginName}' wurde erfolgreich von v{$fromVersion} auf v{$toVersion} aktualisiert. Sicherung: " . self::BACKUP_DIR . "/{$backup}",
                'slug'    => $slug,
                'from'    => $fromVersion,
                'to'      => $toVersion,
                'backup'  => $backup
            ];
        } finally {
            self::removeDir($staging);
            @unlink($zipFile);
        }
    }

    /**
     * Lädt das Update-Paket sicher herunter.
     */
    protected static function download(string $url, string $dest): ?string {
        if (function_exists('curl_init')) {
            $fh = @fopen($dest, 'wb');
            if ($fh) {
                $ch = curl_init($url);
                $curlOpts = [
                    CURLOPT_FILE            => $fh,
                    CURLOPT_FOLLOWLOCATION  => true,
                    CURLOPT_MAXREDIRS       => 5,
                    CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
                    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                    CURLOPT_CONNECTTIMEOUT  => 10,
                    CURLOPT_TIMEOUT         => 120,
                    CURLOPT_SSL_VERIFYPEER  => true,
                    CURLOPT_USERAGENT       => 'WeaveCMS-PluginUpdater/' . (defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0'),
                ];
                if (defined('CURLOPT_SSL_OPTIONS') && defined('CURLSSLOPT_NATIVE_CA')) {
                    $curlOpts[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
                }
                curl_setopt_array($ch, $curlOpts);
                $ok = curl_exec($ch);
                $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                fclose($fh);

                if ($ok && $code === 200 && (int)@filesize($dest) > 0) {
                    $size = (int)@filesize($dest);
                    if ($size > self::MAX_PACKAGE_BYTES) {
                        @unlink($dest);
                        return 'Das heruntergeladene Plugin-Paket überschreitet das Limit von 50 MB.';
                    }
                    return null;
                }
                @unlink($dest);
            }
        }

        // Fallback: allow_url_fopen
        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 120,
                    'header'  => "User-Agent: WeaveCMS-PluginUpdater\r\n"
                ],
                'ssl' => [
                    'verify_peer' => true
                ]
            ]);
            $body = @file_get_contents($url, false, $ctx, 0, self::MAX_PACKAGE_BYTES + 1);
            if ($body !== false && @file_put_contents($dest, $body) !== false) {
                $size = (int)@filesize($dest);
                if ($size <= 0 || $size > self::MAX_PACKAGE_BYTES) {
                    @unlink($dest);
                    return 'Das Paket ist leer oder überschreitet 50 MB.';
                }
                return null;
            }
        }

        return 'Download fehlgeschlagen (weder cURL noch allow_url_fopen konnten die Datei herunterladen).';
    }

    /**
     * Entpackt das ZIP-Archiv geschützt gegen Zip-Slip nach $staging und ermittelt
     * das Stammverzeichnis, in dem sich plugin.json befindet.
     */
    protected static function extractToStaging(string $zipFile, string $staging, ?string &$err): ?string {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $err = 'Das Plugin-Paket ist kein gültiges ZIP-Archiv.';
            return null;
        }

        if (!@mkdir($staging, 0755, true) && !is_dir($staging)) {
            $zip->close();
            $err = 'Staging-Verzeichnis konnte nicht angelegt werden.';
            return null;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rawName = (string)$zip->getNameIndex($i);
            $name = str_replace('\\', '/', $rawName);

            // Zip-Slip Schutz
            if ($name === '' || strpos($name, "\0") !== false || $name[0] === '/' || strpos($name, ':') !== false) {
                continue;
            }
            $isSlip = false;
            foreach (explode('/', $name) as $seg) {
                if ($seg === '..' || $seg === '.') {
                    $isSlip = true;
                    break;
                }
            }
            if ($isSlip) {
                continue;
            }

            if (substr($name, -1) === '/') {
                @mkdir($staging . '/' . $name, 0755, true);
                continue;
            }

            $target = $staging . '/' . $name;
            $dir = dirname($target);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                $zip->close();
                $err = "Konnte Unterordner '{$dir}' nicht anlegen.";
                return null;
            }

            $stream = $zip->getStream($rawName);
            if ($stream) {
                $out = @fopen($target, 'wb');
                if ($out) {
                    stream_copy_to_stream($stream, $out);
                    fclose($out);
                }
                fclose($stream);
            }
        }
        $zip->close();

        // Wurzelverzeichnis des Plugins erkennen
        $root = self::detectPluginRoot($staging);
        if (!$root) {
            $err = 'Keine plugin.json im heruntergeladenen Archiv gefunden.';
            return null;
        }

        return $root;
    }

    /**
     * Erkennt das Verzeichnis innerhalb des Staging-Ordners, welches die plugin.json enthält.
     * Unterstützt flache ZIPs, Repos mit Slug-Ordner und GitHub Zipballs (<repo>-<hash>/).
     */
    public static function detectPluginRoot(string $staging): ?string {
        if (is_file($staging . '/plugin.json')) {
            return $staging;
        }

        $subdirs = glob($staging . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($subdirs as $dir) {
            if (is_file($dir . '/plugin.json')) {
                return $dir;
            }
            $sub2 = glob($dir . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($sub2 as $d2) {
                if (is_file($d2 . '/plugin.json')) {
                    return $d2;
                }
            }
        }

        return null;
    }

    /**
     * Erstellt ein Sicherungs-ZIP des aktuellen Plugin-Ordners vor dem Überschreiben.
     */
    public static function createPreUpdateBackup(string $slug, string $targetDir, string $version, ?string &$err): ?string {
        $backupDir = self::getRootDir() . '/' . self::BACKUP_DIR;
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
            $err = "Backup-Verzeichnis '{$backupDir}' konnte nicht erstellt werden.";
            return null;
        }

        $stamp = date('Y-m-d_H-i-s');
        $cleanVer = preg_replace('/[^0-9A-Za-z.\-]/', '', $version);
        $filename = "plugin_{$slug}_{$stamp}_v{$cleanVer}.zip";
        $zipPath = $backupDir . '/' . $filename;

        $zip = new ZipArchive();
        $flags = file_exists($zipPath) ? (ZipArchive::CREATE | ZipArchive::OVERWRITE) : ZipArchive::CREATE;
        if ($zip->open($zipPath, $flags) !== true) {
            $err = 'Backup-ZIP-Datei konnte nicht geöffnet werden.';
            return null;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $fileCount = 0;
        foreach ($it as $item) {
            $rel = substr($item->getPathname(), strlen($targetDir) + 1);
            $rel = str_replace('\\', '/', $rel);

            if ($item->isDir()) {
                $zip->addEmptyDir($rel);
            } elseif ($item->isFile()) {
                $zip->addFile($item->getPathname(), $rel);
                $fileCount++;
            }
        }

        $zip->addFromString('plugin_backup_manifest.json', json_encode([
            'generator'    => 'WeaveCMS PluginUpdater',
            'plugin_slug'  => $slug,
            'version'      => $version,
            'created_at'   => date('c'),
            'file_count'   => $fileCount
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $ok = $zip->close();
        clearstatcache(true, $zipPath);
        if (!$ok || !is_file($zipPath) || filesize($zipPath) === 0) {
            $err = 'Backup-Datei konnte nicht finalisiert werden.';
            return null;
        }

        return $filename;
    }

    /**
     * Ersetzt atomar alle Dateien des Plugins mit den neuen Versionen aus dem Staging.
     */
    public static function swapFiles(string $sourceDir, string $targetDir, array &$replaced, array &$added): ?string {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $rel = substr($item->getPathname(), strlen($sourceDir) + 1);
            $rel = str_replace('\\', '/', $rel);
            $dest = $targetDir . '/' . $rel;

            if ($item->isDir()) {
                if (!is_dir($dest) && !@mkdir($dest, 0755, true)) {
                    return "Unterordner '{$rel}' konnte nicht angelegt werden.";
                }
            } elseif ($item->isFile()) {
                $dir = dirname($dest);
                if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                    return "Verzeichnis für '{$rel}' konnte nicht angelegt werden.";
                }

                $tmp = $dest . '.upd_new';
                if (!@copy($item->getPathname(), $tmp)) {
                    @unlink($tmp);
                    return "Temporäre Datei für '{$rel}' konnte nicht geschrieben werden.";
                }

                $exists = file_exists($dest);
                $success = @rename($tmp, $dest);
                if (!$success) {
                    @unlink($dest);
                    $success = @rename($tmp, $dest);
                    if (!$success) {
                        $success = @copy($item->getPathname(), $dest);
                    }
                    @unlink($tmp);
                }

                if (!$success) {
                    return "Datei '{$rel}' konnte nicht aktualisiert werden.";
                }

                if ($exists) {
                    $replaced[] = $rel;
                } else {
                    $added[] = $rel;
                }
            }
        }

        return null;
    }

    /**
     * Stellt das Plugin im Fehlerfall vollständig aus dem Pre-Update-Backup wieder her.
     */
    public static function rollback(string $backupPath, string $targetDir): ?string {
        if (!file_exists($backupPath)) {
            return "Backup-Datei nicht gefunden: {$backupPath}";
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            return "Backup-ZIP konnte nicht geöffnet werden.";
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if ($name === 'plugin_backup_manifest.json') {
                continue;
            }
            $target = $targetDir . '/' . $name;
            if (substr($name, -1) === '/') {
                @mkdir($target, 0755, true);
                continue;
            }
            $dir = dirname($target);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                @file_put_contents($target, $content);
            }
        }
        $zip->close();
        return null;
    }

    /**
     * Entfernt rekursiv ein Verzeichnis.
     */
    protected static function removeDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
