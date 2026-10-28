<?php
/**
 * ============================================================================
 * WeaveCMS Core-Updater (CoreUpdater) – Phase 25
 * ============================================================================
 * 1-Klick-Aktualisierung des CMS-Kerns aus dem Administrationsbereich:
 *
 *  1. Update-ZIP laden (nur HTTPS) und SHA-256-Prüfsumme verifizieren
 *  2. Paket in ein Staging-Verzeichnis entpacken und validieren
 *     (nur freigegebene Kernpfade, Zip-Slip-Schutz, kein Downgrade)
 *  3. Pre-Update-Backup der zu ersetzenden Dateien + data.json
 *     (backups/pre_update_<Zeitstempel>_v<Version>.zip)
 *  4. Wartungsmodus (maintenance.flag) aktivieren, Dateien einzeln ersetzen
 *     (temporäre Datei + rename); bei Fehler automatischer Rollback aus dem Backup
 *  5. Wartungsmodus beenden; Migrationen laufen beim nächsten Admin-Aufruf
 *     (dann mit dem neuen Code) – siehe finishPendingUpdate()
 *
 * Nutzerdaten (data.json, data/, images/, backups/, plugins/, config.local.php,
 * installed.lock) werden nie überschrieben.
 *
 * @package WeaveCMS\Update
 * @author WeaveCMS Team
 * @license Proprietary
 */

class CoreUpdater {

    public const FLAG_FILE = 'maintenance.flag';
    public const FLAG_TTL = 900;                 // Verwaiste Flags laufen nach 15 Min. ab
    public const MAX_PACKAGE_BYTES = 104857600;  // 100 MB

    /** Freigegebene Pfade des Kerns (Verzeichnisse mit Slash am Ende, sonst exakte Dateien). */
    public const CORE_PATHS = [
        'libs/', 'admin/', 'views/', 'api/',
        'config.php', 'index.php', 'sitemap.php', 'robots.php', 'send_mail.php',
    ];

    /** Niemals überschreibbare Pfade & Dateien (Schutz von Nutzerdaten und Installer-Sperre). */
    public const FORBIDDEN_PATHS = [
        'install.php',
        'installed.lock',
        'data.json',
        'data.sample.json',
        'config.local.php',
        'data/',
        'backups/',
        'images/',
        'uploads/',
        'logs/',
        'cache/',
        'scratch/',
    ];

    /** Wird im aktuellen Request gesetzt, damit Migrationen erst mit dem neuen Code laufen. */
    protected static bool $updatedThisRequest = false;

    public static function getRootDir(): string {
        return dirname(__DIR__, 2);
    }

    // ------------------------------------------------------------------
    // Wartungsmodus
    // ------------------------------------------------------------------

    public static function isMaintenanceActive(): bool {
        $flag = self::getRootDir() . '/' . self::FLAG_FILE;
        return is_file($flag) && (time() - (int)@filemtime($flag)) < self::FLAG_TTL;
    }

    protected static function setMaintenance(bool $on): void {
        $flag = self::getRootDir() . '/' . self::FLAG_FILE;
        if ($on) {
            @file_put_contents($flag, json_encode(['since' => date('c')]));
        } elseif (is_file($flag)) {
            @unlink($flag);
        }
    }

    /**
     * Gibt die Wartungsseite (HTTP 503) aus und beendet die Ausführung,
     * falls ein Update läuft. Für das Frontend gedacht (index.php).
     */
    public static function enforceMaintenanceForVisitors(): void {
        if (!self::isMaintenanceActive()) {
            return;
        }
        http_response_code(503);
        header('Retry-After: 120');
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<meta name="robots" content="noindex"><title>Wartungsarbeiten</title></head>'
            . '<body style="font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#fbf9f6;color:#3e3228;text-align:center;">'
            . '<div><h1>Kurze Wartungspause</h1><p>Die Website wird gerade aktualisiert und ist in wenigen Augenblicken wieder erreichbar.</p></div>'
            . '</body></html>';
        exit;
    }

    // ------------------------------------------------------------------
    // Update-Ablauf
    // ------------------------------------------------------------------

    /**
     * Führt das Core-Update aus.
     *
     * @param array $info Ergebnis von UpdateManager::checkCoreUpdate(true)
     * @return array ['success'=>bool, 'message'=>string, 'from'=>string, 'to'=>string, 'backup'=>string]
     */
    public static function run(array $info): array {
        $from = class_exists('Installer') ? Installer::getVersion() : '0.0.0';
        $fail = fn(string $msg) => ['success' => false, 'message' => $msg, 'from' => $from, 'to' => '', 'backup' => ''];

        // 1. Voraussetzungen
        if (empty($info['update_available'])) {
            return $fail('Es ist kein neueres Update verfügbar.');
        }
        if (!class_exists('ZipArchive')) {
            return $fail('Die PHP-Erweiterung ZipArchive ist auf diesem Server nicht verfügbar.');
        }
        $zipUrl = (string)($info['zip_url'] ?? '');
        if (!preg_match('~^https://~i', $zipUrl)) {
            return $fail('Für dieses Update ist keine sichere (HTTPS) Download-Adresse hinterlegt.');
        }
        $sha256 = strtolower(trim((string)($info['sha256'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            return $fail('Für dieses Update liegt keine SHA-256-Prüfsumme vor – aus Sicherheitsgründen wird nicht automatisch installiert.');
        }
        if (self::isMaintenanceActive()) {
            return $fail('Es läuft bereits ein Update (Wartungsmodus aktiv). Bitte kurz warten.');
        }
        $root = self::getRootDir();
        if (!is_writable($root) || !is_writable($root . '/libs')) {
            return $fail('Das CMS-Verzeichnis ist für PHP nicht beschreibbar.');
        }
        @set_time_limit(300);

        $workDir = $root . '/cache/updates';
        if (!is_dir($workDir) && !@mkdir($workDir, 0755, true)) {
            return $fail('Arbeitsverzeichnis cache/updates konnte nicht angelegt werden.');
        }
        $stamp = date('Y-m-d_H-i-s');
        $zipFile = $workDir . '/core_download_' . $stamp . '.zip';
        $staging = $workDir . '/staging_' . $stamp;

        try {
            // 2. Download + Prüfsumme
            $err = self::download($zipUrl, $zipFile);
            if ($err !== null) {
                return $fail($err);
            }
            if (!hash_equals($sha256, strtolower((string)hash_file('sha256', $zipFile)))) {
                return $fail('SHA-256-Prüfsumme stimmt nicht überein – Update abgebrochen, es wurde nichts verändert.');
            }

            // 3. Entpacken in Staging + Validierung
            $files = self::extractToStaging($zipFile, $staging, $err);
            if ($files === null) {
                return $fail($err);
            }
            $to = self::readPackageVersion($staging) ?? (string)($info['latest_version'] ?? '');
            if ($to !== '' && version_compare($to, $from, '<=')) {
                return $fail("Das Paket (v{$to}) ist nicht neuer als die installierte Version (v{$from}).");
            }

            // 4. Pre-Update-Backup
            $backup = self::createPreUpdateBackup($files, $from, $stamp, $added, $err);
            if ($backup === null) {
                return $fail($err);
            }

            // 5. Austausch im Wartungsmodus
            self::setMaintenance(true);
            $replaced = [];
            $swapErr = self::swapFiles($staging, $files, $replaced);
            if ($swapErr !== null) {
                $rbErr = self::rollback($root . '/backups/' . $backup, $replaced, $added);
                self::setMaintenance(false);
                return $fail('Update fehlgeschlagen und zurückgerollt: ' . $swapErr . ($rbErr ? ' (Rollback-Warnung: ' . $rbErr . ')' : ''));
            }

            // 6. Migrationen vormerken, Caches leeren, Wartungsmodus beenden
            self::writePendingMarker($from, $to);
            @unlink($workDir . '/core_version.json');
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            self::setMaintenance(false);
            self::$updatedThisRequest = true;

            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('CORE_UPDATE', "WeaveCMS von v{$from} auf v{$to} aktualisiert (Backup: {$backup})", 'INFO');
            }

            return [
                'success' => true,
                'message' => "WeaveCMS wurde von v{$from} auf v{$to} aktualisiert. Sicherung: backups/{$backup}",
                'from' => $from,
                'to' => $to,
                'backup' => $backup,
            ];
        } finally {
            self::removeDir($staging);
            @unlink($zipFile);
        }
    }

    /**
     * Schließt ein vorgemerktes Update ab (Schema- & Konfigurations-Migrationen).
     * Läuft im ersten Request nach dem Update und damit mit dem neuen Code.
     *
     * @return string Statusmeldung (leer, wenn nichts zu tun war)
     */
    public static function finishPendingUpdate(string $dataFile, array &$data): string {
        $marker = self::getMarkerPath();
        if (self::$updatedThisRequest || !is_file($marker)) {
            return '';
        }
        $info = json_decode((string)@file_get_contents($marker), true);
        @unlink($marker);
        $from = (string)($info['from'] ?? '0.0.0');
        $to = (string)($info['to'] ?? Installer::getVersion());

        $changed = UpdateManager::ensureSchemaCompatibility($data);

        // Versionierte Migrationen: libs/migrations/<version>.php liefert function(array &$data): void
        $dir = self::getRootDir() . '/libs/migrations';
        $ran = 0;
        if (is_dir($dir)) {
            $migs = glob($dir . '/*.php') ?: [];
            usort($migs, fn($a, $b) => version_compare(basename($a, '.php'), basename($b, '.php')));
            foreach ($migs as $file) {
                $ver = basename($file, '.php');
                if (version_compare($ver, $from, '>') && version_compare($ver, $to, '<=')) {
                    $fn = require $file;
                    if (is_callable($fn)) {
                        $fn($data);
                        $ran++;
                        $changed = true;
                    }
                }
            }
        }

        if (class_exists('PluginManager') && method_exists('PluginManager', 'doAction')) {
            PluginManager::doAction('core_updated', $from, $to, $data);
        }
        if ($changed) {
            save_data($dataFile, $data);
        }
        return "✓ Update auf v{$to} abgeschlossen (Schema-Prüfung durchgeführt, versionierte Migrationen: {$ran}).";
    }

    // ------------------------------------------------------------------
    // Hilfsfunktionen
    // ------------------------------------------------------------------

    protected static function getMarkerPath(): string {
        $dir = self::getRootDir() . '/data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/update_pending.json';
    }

    protected static function writePendingMarker(string $from, string $to): void {
        @file_put_contents(self::getMarkerPath(), json_encode(['from' => $from, 'to' => $to, 'at' => date('c')]));
    }

    /** Prüft, ob ein relativer Pfad aus dem Paket installiert werden darf. */
    public static function isAllowedPath(string $rel): bool {
        $rel = str_replace('\\', '/', trim($rel));
        if ($rel === '' || strpos($rel, "\0") !== false || $rel[0] === '/' || strpos($rel, ':') !== false) {
            return false;
        }
        foreach (explode('/', $rel) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                return false;
            }
        }
        // Striktes Verbot von Benutzerdaten, Konfigurationen und Installationsdateien
        foreach (self::FORBIDDEN_PATHS as $forbidden) {
            if (substr($forbidden, -1) === '/') {
                if (strpos($rel, $forbidden) === 0) {
                    return false;
                }
            } elseif ($rel === $forbidden) {
                return false;
            }
        }
        foreach (self::CORE_PATHS as $allowed) {
            if (substr($allowed, -1) === '/') {
                if (strpos($rel, $allowed) === 0) {
                    return true;
                }
            } elseif ($rel === $allowed) {
                return true;
            }
        }
        return false;
    }

    /** @return string|null Fehlermeldung oder null bei Erfolg */
    protected static function download(string $url, string $dest): ?string {
        if (function_exists('curl_init')) {
            $fh = @fopen($dest, 'wb');
            if (!$fh) {
                return 'Download-Datei konnte nicht angelegt werden.';
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fh,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'WeaveCMS-CoreUpdater/' . Installer::getVersion(),
            ]);
            $ok = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            fclose($fh);
            if ($ok === false || $code !== 200) {
                @unlink($dest);
                return 'Download fehlgeschlagen (HTTP ' . $code . ').';
            }
        } elseif (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => ['timeout' => 180, 'header' => "User-Agent: WeaveCMS-CoreUpdater\r\n"]]);
            $body = @file_get_contents($url, false, $ctx, 0, self::MAX_PACKAGE_BYTES + 1);
            if ($body === false || @file_put_contents($dest, $body) === false) {
                return 'Download fehlgeschlagen.';
            }
        } else {
            return 'Weder cURL noch allow_url_fopen sind verfügbar – Download nicht möglich.';
        }
        $size = (int)@filesize($dest);
        if ($size <= 0 || $size > self::MAX_PACKAGE_BYTES) {
            @unlink($dest);
            return 'Das heruntergeladene Paket ist leer oder zu groß.';
        }
        return null;
    }

    /**
     * Entpackt nur freigegebene Pfade nach $staging.
     *
     * @return string[]|null Liste relativer Pfade oder null (Fehler in $err)
     */
    protected static function extractToStaging(string $zipFile, string $staging, ?string &$err): ?array {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $err = 'Das Update-Paket ist kein gültiges ZIP-Archiv.';
            return null;
        }

        // Gemeinsames Wurzelverzeichnis (z. B. bei GitHub-Zipball) erkennen
        $prefix = '';
        $first = $zip->getNameIndex(0);
        if ($first !== false) {
            $firstNorm = str_replace('\\', '/', (string)$first);
            if (strpos($firstNorm, '/') !== false) {
                $cand = substr($firstNorm, 0, strpos($firstNorm, '/') + 1);
                $isCoreTop = false;
                foreach (self::CORE_PATHS as $p) {
                    if (strpos($p, $cand) === 0) {
                        $isCoreTop = true;
                    }
                }
                if (!$isCoreTop) {
                    $all = true;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = str_replace('\\', '/', (string)$zip->getNameIndex($i));
                        if (strpos($entryName, $cand) !== 0) {
                            $all = false;
                            break;
                        }
                    }
                    if ($all) {
                        $prefix = $cand;
                    }
                }
            }
        }

        if (!@mkdir($staging, 0755, true) && !is_dir($staging)) {
            $zip->close();
            $err = 'Staging-Verzeichnis konnte nicht angelegt werden.';
            return null;
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rawName = (string)$zip->getNameIndex($i);
            $name = str_replace('\\', '/', $rawName);
            if (substr($name, -1) === '/') {
                continue;
            }
            $rel = substr($name, strlen($prefix));
            if (!self::isAllowedPath($rel)) {
                continue; // Nicht freigegebene Pfade ignorieren (inkl. install.php, data.json etc.)
            }
            $target = $staging . '/' . $rel;
            if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
                $zip->close();
                $err = 'Konnte Verzeichnis für ' . $rel . ' nicht anlegen.';
                return null;
            }
            $stream = $zip->getStream($rawName);
            $out = $stream ? @fopen($target, 'wb') : false;
            if (!$stream || !$out) {
                $zip->close();
                $err = 'Konnte ' . $rel . ' nicht entpacken.';
                return null;
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            $files[] = $rel;
        }
        $zip->close();

        if (!in_array('libs/php/Installer.php', $files, true) || !in_array('config.php', $files, true) || !in_array('index.php', $files, true)) {
            $err = 'Das Paket ist unvollständig oder kein WeaveCMS-Kernpaket (Kerndateien fehlen).';
            return null;
        }
        return $files;
    }

    protected static function readPackageVersion(string $staging): ?string {
        $srcInstaller = (string)@file_get_contents($staging . '/libs/php/Installer.php');
        if (preg_match("~const\\s+VERSION\\s*=\\s*'([^']+)'~", $srcInstaller, $m)) {
            return $m[1];
        }
        $srcConfig = (string)@file_get_contents($staging . '/config.php');
        if (preg_match("~define\\(\\s*['\"]WEAVE_VERSION['\"]\\s*,\\s*['\"]([^'\"]+)['\"]\\s*\\)~", $srcConfig, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Sichert alle zu ersetzenden Dateien sowie data.json.
     *
     * @param string[] $files
     * @param string[]|null $added Out: Dateien, die bisher nicht existierten
     * @return string|null Dateiname relativ zu backups/ oder null (Fehler in $err)
     */
    protected static function createPreUpdateBackup(array $files, string $from, string $stamp, ?array &$added, ?string &$err): ?string {
        $added = [];
        $root = self::getRootDir();
        $dir = class_exists('BackupManager') ? BackupManager::getBackupDir() : $root . '/backups/';
        $name = 'pre_update_' . $stamp . '_v' . preg_replace('/[^0-9A-Za-z.\-]/', '', $from) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($dir . $name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $err = 'Pre-Update-Backup konnte nicht erstellt werden – Update abgebrochen.';
            return null;
        }
        $count = 0;
        foreach ($files as $rel) {
            if (is_file($root . '/' . $rel)) {
                $zip->addFile($root . '/' . $rel, $rel);
                $count++;
            } else {
                $added[] = $rel;
            }
        }
        if (is_file($root . '/data.json')) {
            $zip->addFile($root . '/data.json', 'data.json');
        }
        $zip->addFromString('update_manifest.json', json_encode([
            'generator' => 'WeaveCMS CoreUpdater',
            'from_version' => $from,
            'created_at' => date('c'),
            'replaced_files' => $count,
            'added_files' => $added,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $ok = $zip->close();
        if (!$ok || !is_file($dir . $name) || filesize($dir . $name) === 0) {
            $err = 'Pre-Update-Backup konnte nicht geschrieben werden – Update abgebrochen.';
            return null;
        }
        return $name;
    }

    /**
     * Ersetzt Dateien einzeln (temporäre Datei + rename).
     *
     * @param string[] $files
     * @param string[] $replaced Out: bereits ersetzte Dateien (für Rollback)
     * @return string|null Fehlermeldung oder null
     */
    protected static function swapFiles(string $staging, array $files, array &$replaced): ?string {
        $root = self::getRootDir();
        foreach ($files as $rel) {
            $target = $root . '/' . $rel;
            if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
                return 'Verzeichnis für ' . $rel . ' nicht anlegbar.';
            }
            $tmp = $target . '.upd_new';
            if (!@copy($staging . '/' . $rel, $tmp)) {
                @unlink($tmp);
                return $rel . ' konnte nicht geschrieben werden.';
            }
            // Zuerst atomaren Rename versuchen
            $success = @rename($tmp, $target);
            if (!$success) {
                // Windows-Fallback: falls rename fehlschlägt weil Zieldatei gelockt/vorhanden
                @unlink($target);
                $success = @rename($tmp, $target);
                if (!$success) {
                    $success = @copy($staging . '/' . $rel, $target);
                }
                @unlink($tmp);
            }
            if (!$success) {
                return $rel . ' konnte nicht ersetzt werden.';
            }
            $replaced[] = $rel; // Erfolgreich ersetzt
        }
        return null;
    }

    /**
     * Stellt ersetzte Dateien aus dem Pre-Update-Backup wieder her und entfernt neu hinzugekommene.
     *
     * @return string|null Warnung bei Teilfehlern
     */
    protected static function rollback(string $backupPath, array $replaced, array $added): ?string {
        $root = self::getRootDir();
        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            return 'Backup-Archiv nicht lesbar: ' . basename($backupPath);
        }
        $problems = [];
        foreach ($replaced as $rel) {
            if (in_array($rel, $added, true)) {
                @unlink($root . '/' . $rel);
                continue;
            }
            $content = $zip->getFromName($rel);
            if ($content === false || @file_put_contents($root . '/' . $rel, $content) === false) {
                $problems[] = $rel;
            }
            @unlink($root . '/' . $rel . '.upd_new');
        }
        $zip->close();
        return $problems ? 'nicht wiederhergestellt: ' . implode(', ', $problems) : null;
    }

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
