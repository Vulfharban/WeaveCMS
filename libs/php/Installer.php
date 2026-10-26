<?php
/**
 * ============================================================================
 * WeaveCMS Installer & Health-Check Engine (Phase 5)
 * ============================================================================
 * Verwaltet Systemvoraussetzungen, Dateirechte, Initialisierungs-Seed
 * und den Sperrstatus (installed.lock).
 */

require_once __DIR__ . '/storage/StorageManager.php';

class Installer {
    const LOCK_FILE = 'installed.lock';
    const VERSION = '0.4.0';

    /**
     * Ermittelt den absoluten Pfad zur Wurzel von WeaveCMS.
     * @return string
     */
    public static function rootPath() {
        return dirname(__DIR__, 2);
    }

    /**
     * Ermittelt den Pfad zur Lock-Datei.
     * @return string
     */
    public static function lockFilePath() {
        return self::rootPath() . '/' . self::LOCK_FILE;
    }

    /**
     * Prüft, ob das System bereits installiert und gesperrt ist.
     * @return bool
     */
    public static function isInstalled() {
        return file_exists(self::lockFilePath());
    }

    /**
     * Gibt Metadaten der Installation zurück (falls vorhanden).
     * @return array|null
     */
    public static function getLockData() {
        $path = self::lockFilePath();
        if (!file_exists($path)) {
            return null;
        }
        $content = file_get_contents($path);
        $data = json_decode((string)$content, true);
        return is_array($data) ? $data : ['installed_at' => date('Y-m-d H:i:s'), 'version' => self::VERSION];
    }

    /**
     * Konvertiert Größenangaben aus php.ini (z. B. 128M, 2G, 64K) in Bytes.
     * @param string $size
     * @return int
     */
    public static function parseSize(string $size): int {
        $size = trim($size);
        if ($size === '' || $size === '-1') return -1;
        $unit = strtolower(substr($size, -1));
        $val = (float)$size;
        switch ($unit) {
            case 'g': $val *= 1024 * 1024 * 1024; break;
            case 'm': $val *= 1024 * 1024; break;
            case 'k': $val *= 1024; break;
        }
        return (int)round($val);
    }

    /**
     * Führt den vollständigen System-Check (Health Check) vor der Installation durch.
     * Prüft:
     * - PHP-Laufzeitumgebung (Version >= 8.0, memory_limit, upload_max_filesize, post_max_size, max_execution_time, Zeitzone)
     * - Dateisystem & Schreibrechte (Root /, data/, images/uploads/, images/branding/, backups/, data.sample.json)
     * - PHP-Module & Treiber (pdo, pdo_mysql, pdo_sqlite, gd, mbstring, json, curl, fileinfo, zip)
     * - Webserver-Umgebung & URL-Rewrite (Apache mod_rewrite, .htaccess)
     * - Empfohlene Einstellungen für php.ini und Apache
     * @return array
     */
    public static function runHealthCheck() {
        $root = self::rootPath();

        $results = [
            'can_install' => true,
            'summary' => [
                'passed' => 0,
                'warnings' => 0,
                'errors' => 0
            ],
            'php_version' => [],
            'php_env' => [],
            'directories' => [],
            'extensions' => [],
            'webserver' => [],
            'recommendations' => []
        ];

        // ──────────────── 1. PHP-Laufzeitumgebung ────────────────
        $phpVerOk = version_compare(PHP_VERSION, '8.0.0', '>=');
        $phpVerWarn = version_compare(PHP_VERSION, '8.1.0', '<');
        $results['php_version'] = [
            'name' => 'PHP-Version (Mindestens 8.0)',
            'current' => PHP_VERSION,
            'min' => '8.0.0',
            'ok' => $phpVerOk,
            'is_warn' => $phpVerOk && $phpVerWarn,
            'required' => true,
            'message' => $phpVerOk 
                ? ($phpVerWarn ? 'PHP ' . PHP_VERSION . ' erfüllt die Mindestanforderung (>= 8.0). PHP 8.1+ wird empfohlen.' : 'PHP ' . PHP_VERSION . ' erfüllt alle modernen Anforderungen.') 
                : 'PHP 8.0 oder höher ist zwingend erforderlich (Aktuell: ' . PHP_VERSION . ').'
        ];
        if (!$phpVerOk) {
            $results['can_install'] = false;
            $results['summary']['errors']++;
        } else {
            $results['summary']['passed']++;
        }

        $memStr = ini_get('memory_limit') ?: '128M';
        $memBytes = self::parseSize($memStr);
        $memOk = ($memBytes === -1 || $memBytes >= 64 * 1024 * 1024);
        $memWarn = ($memBytes > 0 && $memBytes < 128 * 1024 * 1024);
        $results['php_env']['memory_limit'] = [
            'name' => 'Arbeitsspeicher (memory_limit)',
            'current' => $memStr,
            'ok' => $memOk,
            'is_warn' => $memWarn,
            'required' => true,
            'message' => ($memBytes === -1 || $memBytes >= 128 * 1024 * 1024)
                ? 'Ausreichend Arbeitsspeicher für Bildverarbeitung und Caching verfügbar.'
                : ($memOk ? 'Mindestanforderung erfüllt (>= 64M). Empfohlen werden mindestens 128M.' : 'Zu wenig Arbeitsspeicher! Mindestens 64M in php.ini erforderlich.')
        ];
        if (!$memOk) {
            $results['can_install'] = false;
            $results['summary']['errors']++;
        } elseif ($memWarn) {
            $results['summary']['warnings']++;
        } else {
            $results['summary']['passed']++;
        }

        $upMaxStr = ini_get('upload_max_filesize') ?: '2M';
        $upMaxBytes = self::parseSize($upMaxStr);
        $upWarn = ($upMaxBytes < 8 * 1024 * 1024);
        $results['php_env']['upload_max_filesize'] = [
            'name' => 'Max. Upload-Dateigröße (upload_max_filesize)',
            'current' => $upMaxStr,
            'ok' => true,
            'is_warn' => $upWarn,
            'required' => false,
            'message' => $upWarn 
                ? 'Aktuell ' . $upMaxStr . '. Für hochauflösende Fotos und Medien werden >= 8M empfohlen (in php.ini).' 
                : 'Ausreichend für Medien- und Foto-Uploads.'
        ];
        if ($upWarn) $results['summary']['warnings']++; else $results['summary']['passed']++;

        $postMaxStr = ini_get('post_max_size') ?: '8M';
        $postMaxBytes = self::parseSize($postMaxStr);
        $postWarn = ($postMaxBytes < $upMaxBytes);
        $results['php_env']['post_max_size'] = [
            'name' => 'Max. POST-Datengröße (post_max_size)',
            'current' => $postMaxStr,
            'ok' => !$postWarn,
            'is_warn' => $postWarn,
            'required' => false,
            'message' => $postWarn 
                ? 'post_max_size (' . $postMaxStr . ') ist kleiner als upload_max_filesize (' . $upMaxStr . '). Bitte in php.ini anpassen!' 
                : 'POST-Größe ist passend zur Upload-Größe konfiguriert.'
        ];
        if ($postWarn) $results['summary']['warnings']++; else $results['summary']['passed']++;

        $maxExecTime = (int)(ini_get('max_execution_time') ?: 0);
        $execWarn = ($maxExecTime > 0 && $maxExecTime < 30);
        $results['php_env']['max_execution_time'] = [
            'name' => 'Skript-Laufzeit (max_execution_time)',
            'current' => ($maxExecTime === 0 ? 'Unbegrenzt' : $maxExecTime . 's'),
            'ok' => true,
            'is_warn' => $execWarn,
            'required' => false,
            'message' => $execWarn 
                ? 'Mit ' . $maxExecTime . 's recht kurz bemessen. Empfohlen werden mindestens 30s oder 60s für Backups.' 
                : 'Ausreichende Laufzeit für Dateioperationen und Backups.'
        ];
        if ($execWarn) $results['summary']['warnings']++; else $results['summary']['passed']++;

        $tz = date_default_timezone_get();
        $results['php_env']['timezone'] = [
            'name' => 'Server-Zeitzone (date.timezone)',
            'current' => $tz ?: 'Nicht gesetzt',
            'ok' => !empty($tz),
            'is_warn' => false,
            'required' => false,
            'message' => !empty($tz) ? 'Zeitzone aktiv gesetzt (' . $tz . ').' : 'Keine Standard-Zeitzone definiert.'
        ];
        $results['summary']['passed']++;

        // ──────────────── 2. Dateisystem & Schreibrechte ────────────────
        // Root-Verzeichnis prüfen
        $rootWritable = is_dir($root) && is_writable($root);
        if (!$rootWritable) {
            $testFile = $root . '/.write_test_' . uniqid();
            if (@file_put_contents($testFile, 'test') !== false) {
                $rootWritable = true;
                @unlink($testFile);
            }
        }
        $results['directories']['root'] = [
            'name' => 'Hauptverzeichnis (/)',
            'path' => $root,
            'exists' => true,
            'writable' => $rootWritable,
            'ok' => $rootWritable,
            'required' => true,
            'message' => $rootWritable 
                ? 'Beschreibbar (erforderlich für data.json, installed.lock, config.local.php).' 
                : 'Hauptverzeichnis nicht beschreibbar! Schreibrechte erforderlich (z. B. chmod 755 / 775).'
        ];
        if (!$rootWritable) {
            $results['can_install'] = false;
            $results['summary']['errors']++;
        } else {
            $results['summary']['passed']++;
        }

        $dirs_to_check = [
            'data' => [
                'path' => $root . '/data',
                'label' => 'Datenverzeichnis (data/)',
                'required' => true
            ],
            'uploads' => [
                'path' => $root . '/images/uploads',
                'label' => 'Upload-Verzeichnis (images/uploads/)',
                'required' => true
            ],
            'branding' => [
                'path' => $root . '/images/branding',
                'label' => 'Branding-Verzeichnis (images/branding/)',
                'required' => true
            ],
            'backups' => [
                'path' => $root . '/backups',
                'label' => 'Backup-Archiv (backups/)',
                'required' => true
            ]
        ];

        foreach ($dirs_to_check as $key => $info) {
            $p = $info['path'];
            if (!is_dir($p)) {
                @mkdir($p, 0755, true);
            }
            $writable = is_dir($p) && is_writable($p);
            if (!$writable) {
                $test_file = $p . '/.write_test_' . uniqid();
                if (@file_put_contents($test_file, 'test') !== false) {
                    $writable = true;
                    @unlink($test_file);
                }
            }

            $results['directories'][$key] = [
                'name' => $info['label'],
                'path' => $p,
                'exists' => is_dir($p),
                'writable' => $writable,
                'ok' => $writable,
                'required' => $info['required'],
                'message' => $writable 
                    ? 'Beschreibbar und einsatzbereit.' 
                    : 'Keine Schreibrechte vorhanden. Bitte Zugriffsrechte (z.B. chmod 755/775) anpassen.'
            ];

            if (!$writable && $info['required']) {
                $results['can_install'] = false;
                $results['summary']['errors']++;
            } else {
                $results['summary']['passed']++;
            }
        }

        // Beispieldaten-Vorlage prüfen
        $sampleFile = $root . '/data.sample.json';
        $sampleExists = file_exists($sampleFile) && is_readable($sampleFile);
        $results['directories']['sample_seed'] = [
            'name' => 'Beispieldaten-Vorlage (data.sample.json)',
            'path' => $sampleFile,
            'exists' => $sampleExists,
            'writable' => true,
            'ok' => $sampleExists,
            'required' => false,
            'message' => $sampleExists 
                ? 'Lesbar und bereit für optionale Beispieldaten-Einrichtung.' 
                : 'Nicht gefunden (Installation startet mit sauberem Minimal-Datensatz).'
        ];
        $results['summary']['passed']++;

        // ──────────────── 3. PHP-Module & Erweiterungen ────────────────
        $extensions_to_check = [
            'pdo' => [
                'name' => 'PDO (Datenbank-Treiber-Basis)',
                'required' => true,
                'desc' => 'Zwingend für sichere SQL-Verbindungen & Abstraktionsschicht.'
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL (MySQL & MariaDB Treiber)',
                'required' => false,
                'desc' => 'Erforderlich für den relationalen SQL-Datenbankmodus.'
            ],
            'pdo_sqlite' => [
                'name' => 'PDO SQLite (SQLite-Treiber)',
                'required' => false,
                'desc' => 'Optional für lokale SQLite-Datenbankdateien.'
            ],
            'gd' => [
                'name' => 'GD Library (Bildverarbeitung & Captchas)',
                'required' => true,
                'desc' => 'Erforderlich für Thumbnail-Generierung, Bildskalierung & Anti-Spam.'
            ],
            'mbstring' => [
                'name' => 'mbstring (Multibyte UTF-8)',
                'required' => true,
                'desc' => 'Erforderlich für saubere UTF-8 Textverarbeitung & Umlaute.'
            ],
            'json' => [
                'name' => 'JSON (Daten- & Konfigurations-Engine)',
                'required' => true,
                'desc' => 'Erforderlich für Flat-File Speicherung und AJAX-Schnittstellen.'
            ],
            'curl' => [
                'name' => 'cURL (Netzwerk- & Update-Prüfung)',
                'required' => false,
                'desc' => 'Empfohlen für automatisierte Update-Checks und Webhooks.'
            ],
            'fileinfo' => [
                'name' => 'fileinfo (MIME-Typ-Validierung)',
                'required' => false,
                'desc' => 'Dringend empfohlen für sichere Datei-Upload-Prüfung.'
            ],
            'zip' => [
                'name' => 'ZipArchive (ZIP-Komprimierung)',
                'required' => false,
                'desc' => 'Empfohlen für 1-Klick-Sicherungen und Theme-/Plugin-Pakete.'
            ]
        ];

        foreach ($extensions_to_check as $ext => $info) {
            $loaded = extension_loaded($ext);
            $results['extensions'][$ext] = [
                'name' => $info['name'],
                'extension' => $ext,
                'loaded' => $loaded,
                'required' => $info['required'],
                'ok' => $loaded || !$info['required'],
                'message' => $loaded 
                    ? 'Modul aktiv und einsatzbereit.' 
                    : ($info['required'] ? 'Fehlt! Bitte in php.ini aktivieren (extension=' . $ext . ').' : 'Optional (' . $info['desc'] . ').')
            ];

            if ($info['required'] && !$loaded) {
                $results['can_install'] = false;
                $results['summary']['errors']++;
            } elseif (!$loaded && !$info['required']) {
                $results['summary']['warnings']++;
            } else {
                $results['summary']['passed']++;
            }
        }

        // ──────────────── 4. Webserver & URL-Rewrite (.htaccess) ────────────────
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? (PHP_SAPI === 'cli' ? 'PHP CLI / Terminal' : 'Webserver');
        $isApache = (stripos($serverSoftware, 'apache') !== false) || function_exists('apache_get_modules');
        $isNginx = stripos($serverSoftware, 'nginx') !== false;
        $isIis = stripos($serverSoftware, 'iis') !== false;
        $htaccessExists = file_exists($root . '/.htaccess');

        $rewriteStatus = [
            'name' => 'URL-Rewrite & .htaccess Schutz',
            'server' => $serverSoftware,
            'is_apache' => $isApache,
            'htaccess' => $htaccessExists,
            'ok' => true,
            'badge' => 'Aktiviert',
            'message' => ''
        ];

        if (function_exists('apache_get_modules')) {
            $apacheMods = apache_get_modules();
            if (in_array('mod_rewrite', $apacheMods, true)) {
                $rewriteStatus['ok'] = true;
                $rewriteStatus['badge'] = 'mod_rewrite aktiv';
                $rewriteStatus['message'] = 'Apache mod_rewrite ist aktiv und .htaccess wird unterstützt.';
            } else {
                $rewriteStatus['ok'] = false;
                $rewriteStatus['badge'] = 'mod_rewrite fehlt';
                $rewriteStatus['message'] = 'mod_rewrite ist deaktiviert! Bitte in Apache (httpd.conf) aktivieren (LoadModule rewrite_module).';
                $results['summary']['warnings']++;
            }
        } elseif ($isApache) {
            $rewriteStatus['ok'] = $htaccessExists;
            $rewriteStatus['badge'] = $htaccessExists ? 'Apache (.htaccess aktiv)' : 'Keine .htaccess';
            $rewriteStatus['message'] = $htaccessExists 
                ? 'Apache-Umgebung erkannt. Bitte sicherstellen, dass AllowOverride All in der VirtualHost-Konfiguration aktiv ist.' 
                : 'Keine .htaccess-Datei im Hauptverzeichnis gefunden!';
            if (!$htaccessExists) $results['summary']['warnings']++; else $results['summary']['passed']++;
        } elseif ($isNginx) {
            $rewriteStatus['ok'] = true;
            $rewriteStatus['badge'] = 'Nginx VHost';
            $rewriteStatus['message'] = 'Nginx erkannt. URL-Rewrites und Verzeichnisschutz bitte in der nginx.conf des VHosts pflegen.';
            $results['summary']['passed']++;
        } elseif ($isIis) {
            $webConfig = file_exists($root . '/web.config');
            $rewriteStatus['ok'] = $webConfig;
            $rewriteStatus['badge'] = $webConfig ? 'web.config aktiv' : 'web.config fehlt';
            $rewriteStatus['message'] = $webConfig ? 'Microsoft IIS mit web.config erkannt.' : 'Microsoft IIS erkannt. web.config für URL-Rewrites empfohlen.';
            if (!$webConfig) $results['summary']['warnings']++; else $results['summary']['passed']++;
        } else {
            $rewriteStatus['ok'] = true;
            $rewriteStatus['badge'] = 'Aktiv';
            $rewriteStatus['message'] = 'Webserver ' . $serverSoftware . ' unterstützt Standard-Routing.';
            $results['summary']['passed']++;
        }
        $results['webserver'] = $rewriteStatus;

        // ──────────────── 5. Konfigurations-Empfehlungen (php.ini & Apache) ────────────────
        $results['recommendations'] = [
            [
                'directive' => 'upload_max_filesize',
                'current' => $upMaxStr,
                'recommended' => '16M',
                'description' => 'Maximale Upload-Größe für hochauflösende Fotos & Dokumente.'
            ],
            [
                'directive' => 'post_max_size',
                'current' => $postMaxStr,
                'recommended' => '18M',
                'description' => 'Muss mindestens so groß oder etwas größer als upload_max_filesize sein.'
            ],
            [
                'directive' => 'memory_limit',
                'current' => $memStr,
                'recommended' => '128M',
                'description' => 'Arbeitsspeicher für Bildbearbeitung, Thumbnails und Caching.'
            ],
            [
                'directive' => 'max_execution_time',
                'current' => ($maxExecTime === 0 ? 'Unbegrenzt' : $maxExecTime . 's'),
                'recommended' => '60s',
                'description' => 'Skript-Timeout für große Datei-Uploads und Backup-Erstellung.'
            ],
            [
                'directive' => 'date.timezone',
                'current' => $tz ?: 'Nicht gesetzt',
                'recommended' => 'Europe/Berlin',
                'description' => 'Standard-Zeitzone für Logeinträge, Kurse und Blogartikel.'
            ],
            [
                'directive' => 'opcache.enable',
                'current' => ini_get('opcache.enable') ? '1 (Aktiv)' : '0 (Inaktiv)',
                'recommended' => '1',
                'description' => 'PHP Bytecode-Beschleuniger für 3-5x schnellere Ladezeiten.'
            ],
            [
                'directive' => 'Apache AllowOverride',
                'current' => $htaccessExists ? 'Aktiviert (.htaccess vorhanden)' : 'Prüfen',
                'recommended' => 'AllowOverride All',
                'description' => 'In httpd.conf / VirtualHost, damit Verzeichnisschutz und Rewrites greifen.'
            ]
        ];

        return $results;
    }

    /**
     * Stellt den Schutz für sensible Serververzeichnisse via .htaccess sicher.
     */
    public static function secureDirectories() {
        $root = self::rootPath();

        // 1. data/ schützen
        $data_dir = $root . '/data';
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0755, true);
        }
        $htaccess_deny = "# Direkten HTTP-Zugriff sperren\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n";
        @file_put_contents($data_dir . '/.htaccess', $htaccess_deny);

        // 2. backups/ schützen
        $backups_dir = $root . '/backups';
        if (!is_dir($backups_dir)) {
            @mkdir($backups_dir, 0755, true);
        }
        @file_put_contents($backups_dir . '/.htaccess', $htaccess_deny);

        // 3. images/uploads/ schützen vor PHP-Code-Ausführung
        $uploads_dir = $root . '/images/uploads';
        if (!is_dir($uploads_dir)) {
            @mkdir($uploads_dir, 0755, true);
        }
        $uploads_htaccess = "# PHP-Skript-Ausführung in Uploads unterbinden\n<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|exe)$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n    <IfModule !mod_authz_core.c>\n        Order Allow,Deny\n        Deny from all\n    </IfModule>\n</FilesMatch>\n";
        @file_put_contents($uploads_dir . '/.htaccess', $uploads_htaccess);

        // 4. images/branding/ schützen vor PHP-Code-Ausführung
        $branding_dir = $root . '/images/branding';
        if (!is_dir($branding_dir)) {
            @mkdir($branding_dir, 0755, true);
        }
        @file_put_contents($branding_dir . '/.htaccess', $uploads_htaccess);
    }

    /**
     * Führt die Installation durch und speichert die Konfiguration.
     * @param array $params
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function install(array $params) {
        $root = self::rootPath();

        // Health-Check nochmals validieren
        $health = self::runHealthCheck();
        if (!$health['can_install']) {
            return ['success' => false, 'error' => 'Die Systemanforderungen sind nicht vollständig erfüllt. Bitte prüfe PHP-Version und Schreibrechte.'];
        }

        // Formular-Validierung
        $site_name = trim($params['site_name'] ?? '');
        $site_slogan = trim($params['site_slogan'] ?? '');
        $owner_name = trim($params['owner_name'] ?? '');
        $contact_email = trim($params['contact_email'] ?? '');
        $admin_user = trim($params['admin_user'] ?? 'admin');
        $admin_pass = $params['admin_pass'] ?? '';
        $admin_pass_confirm = $params['admin_pass_confirm'] ?? '';
        $admin_pin = trim($params['admin_pin'] ?? '');
        $admin_pin_confirm = trim($params['admin_pin_confirm'] ?? '');
        $install_seed = !empty($params['install_seed']);
        $theme_palette = trim($params['theme_palette'] ?? 'nature_classic');

        if (empty($site_name)) {
            return ['success' => false, 'error' => 'Bitte gib einen Namen für deine Website / dein Atelier ein.'];
        }
        if (empty($owner_name)) {
            return ['success' => false, 'error' => 'Bitte gib den Namen des Inhabers / Betreibers an.'];
        }
        if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Bitte gib eine gültige E-Mail-Adresse für Kontaktanfragen ein.'];
        }
        if (empty($admin_user) || strlen($admin_user) < 3) {
            return ['success' => false, 'error' => 'Der Administrator-Benutzername muss mindestens 3 Zeichen lang sein.'];
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,32}$/', $admin_user)) {
            return ['success' => false, 'error' => 'Der Administrator-Benutzername darf nur aus 3 bis 32 Buchstaben, Ziffern, Punkten, Unter- oder Bindestrichen bestehen.'];
        }
        if (strlen($admin_pass) < 8) {
            return ['success' => false, 'error' => 'Das Administrator-Passwort muss mindestens 8 Zeichen lang sein.'];
        }
        if ($admin_pass !== $admin_pass_confirm) {
            return ['success' => false, 'error' => 'Die eingegebenen Administrator-Passwörter stimmen nicht überein.'];
        }
        if (!preg_match('/^[0-9]{4,6}$/', $admin_pin)) {
            return ['success' => false, 'error' => 'Die Sicherheits-PIN muss aus genau 4 bis 6 Ziffern bestehen.'];
        }
        if ($admin_pin !== $admin_pin_confirm) {
            return ['success' => false, 'error' => 'Die eingegebenen Sicherheits-PINs stimmen nicht überein.'];
        }

        // Theme-Paletten auflösen
        $palettes = [
            'nature_classic' => ['primary' => '#2e6248', 'accent' => '#b26c39', 'bg' => '#fbf9f6'],
            'moor_amber' => ['primary' => '#4a3f35', 'accent' => '#d97706', 'bg' => '#f7f4ee'],
            'nordic_sage' => ['primary' => '#3a6351', 'accent' => '#2c3e50', 'bg' => '#f4f6f5'],
            'terracotta_atelier' => ['primary' => '#a85832', 'accent' => '#322822', 'bg' => '#faf7f2']
        ];
        if (!isset($palettes[$theme_palette])) {
            $theme_palette = 'nature_classic';
        }
        $chosen_colors = $palettes[$theme_palette];

        // Seed-Vorlage oder leere Struktur laden
        $sample_file = $root . '/data.sample.json';
        $base_data = [];
        if (file_exists($sample_file)) {
            $base_data = json_decode((string)file_get_contents($sample_file), true);
        }

        if (!is_array($base_data) || empty($base_data)) {
            $base_data = [
                'settings' => [],
                'seo' => [],
                'categories' => ['Wohnaccessoires', 'Gartendeko', 'Auftragsarbeiten', 'Kurse'],
                'texts' => [],
                'gallery' => [],
                'courses' => [],
                'past_courses' => [],
                'blog' => []
            ];
        }

        // Wenn Seed NICHT gewünscht ist, Listen leeren
        if (!$install_seed) {
            $base_data['gallery'] = [];
            $base_data['courses'] = [];
            $base_data['past_courses'] = [];
            $base_data['blog'] = [];
        }

        // Hashes erzeugen
        $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $pin_hash = password_hash($admin_pin, PASSWORD_DEFAULT);

        // Einstellungen konfigurieren
        $base_data['settings']['upload_dir'] = 'images/uploads';
        $base_data['settings']['admin_username'] = $admin_user;
        $base_data['settings']['admin_password_hash'] = $password_hash;
        $base_data['settings']['admin_pin_hash'] = $pin_hash;
        $base_data['settings']['design_theme'] = $theme_palette;
        $base_data['settings']['primary_color'] = $chosen_colors['primary'];
        $base_data['settings']['accent_color'] = $chosen_colors['accent'];
        $base_data['settings']['bg_color'] = $chosen_colors['bg'];
        $base_data['settings']['welcome_shown'] = false; // Schritt 5: Willkommens-Modal triggern

        // Phase 10: Initialen Administrator-Benutzer mit höchster Berechtigungsstufe (Superadmin / ROLE_ADMIN) anlegen
        require_once __DIR__ . '/UserManager.php';
        $base_data['users'] = [
            [
                'id'            => 'usr_superadmin',
                'username'      => $admin_user,
                'email'         => $contact_email,
                'password_hash' => $password_hash,
                'pin_hash'      => $pin_hash,
                'role'          => UserManager::ROLE_ADMIN,
                'active'        => true,
                'created_at'    => date('Y-m-d H:i:s'),
                'last_login'    => date('Y-m-d H:i:s')
            ]
        ];

        // Mehrsprachigkeit (Phase 8): Primärsprache & aktive Sprachen
        require_once __DIR__ . '/LanguageManager.php';
        $supported_langs = LanguageManager::getSupportedLanguages();
        $primary_lang = trim($params['primary_language'] ?? 'de');
        if (!isset($supported_langs[$primary_lang])) {
            $primary_lang = 'de';
        }
        $base_data['settings']['primary_language'] = $primary_lang;
        $posted_active = $params['active_languages'] ?? [$primary_lang];
        $active_langs = [];
        if (is_array($posted_active)) {
            foreach ($posted_active as $code) {
                if (isset($supported_langs[$code]) && !in_array($code, $active_langs, true)) {
                    $active_langs[] = $code;
                }
            }
        }
        if (!in_array($primary_lang, $active_langs, true)) {
            array_unshift($active_langs, $primary_lang);
        }
        $base_data['settings']['active_languages'] = $active_langs;

        // Texte aktualisieren
        if (!isset($base_data['texts']) || !is_array($base_data['texts'])) {
            $base_data['texts'] = [];
        }
        $base_data['texts']['hero_title'] = $site_name;
        $base_data['texts']['hero_subtitle'] = !empty($site_slogan) ? $site_slogan : 'Traditionelles Handwerk, moderne Unikate und kreative Kurse.';
        $base_data['texts']['contact_name'] = $owner_name;
        $base_data['texts']['contact_email'] = $contact_email;
        $base_data['texts']['contact_street'] = trim($params['contact_street'] ?? ($base_data['texts']['contact_street'] ?? ''));
        $base_data['texts']['contact_city'] = trim($params['contact_city'] ?? ($base_data['texts']['contact_city'] ?? ''));
        $base_data['texts']['contact_phone'] = trim($params['contact_phone'] ?? ($base_data['texts']['contact_phone'] ?? ''));
        
        $first_name = explode(' ', $owner_name)[0] ?? 'Hallo';
        $base_data['texts']['contact_mail_greeting'] = 'Hallo ' . $first_name . ',';
        $greetings = [
            'en' => 'Hello %s,',
            'fr' => 'Bonjour %s,',
            'it' => 'Ciao %s,',
            'nl' => 'Hallo %s,',
            'es' => 'Hola %s,',
            'pt' => 'Olá %s,',
            'el' => 'Γεια σας %s,',
            'da' => 'Hej %s,',
            'no' => 'Hei %s,',
            'sv' => 'Hej %s,',
            'fi' => 'Hei %s,'
        ];
        foreach ($greetings as $glang => $gpattern) {
            if (isset($base_data['texts_i18n'][$glang])) {
                $base_data['texts_i18n'][$glang]['contact_mail_greeting'] = sprintf($gpattern, $first_name);
            }
        }

        // SEO aktualisieren
        if (!isset($base_data['seo']) || !is_array($base_data['seo'])) {
            $base_data['seo'] = [];
        }
        $base_data['seo']['meta_title'] = $site_name . ($site_slogan ? ' | ' . $site_slogan : '');
        $base_data['seo']['meta_description'] = $site_slogan ? $site_slogan : 'Willkommen auf der offiziellen Website von ' . $site_name . '.';
        $base_data['seo']['meta_keywords'] = $site_name . ', Handwerk, Unikate, Workshops, Atelier, Design';
        $base_data['seo']['robots'] = 'index, follow';

        // Verzeichnisse schützen
        self::secureDirectories();

        // Speichertreiber verarbeiten (JSON vs. MySQL)
        $storage_driver = trim($params['storage_driver'] ?? 'json');
        if (!in_array($storage_driver, ['json', 'mysql'])) {
            $storage_driver = 'json';
        }

        if ($storage_driver === 'mysql') {
            $db_host = trim($params['db_host'] ?? '127.0.0.1');
            $db_port = (int)($params['db_port'] ?? 3306);
            $db_name = trim($params['db_name'] ?? 'weavecms');
            $db_user = trim($params['db_user'] ?? 'root');
            $db_pass = (string)($params['db_pass'] ?? '');
            $db_prefix = trim($params['db_prefix'] ?? 'weave_');

            if (empty($db_host) || empty($db_name) || empty($db_user)) {
                return ['success' => false, 'error' => 'Für MySQL müssen Host, Datenbankname und Benutzer angegeben werden.'];
            }

            // Verbindungstest durchführen und DB nur anlegen, wenn gewünscht (Standard: ja)
            $create_db = !empty($params['db_create_if_missing']);
            $test = StorageManager::testDbConnection([
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'username' => $db_user,
                'password' => $db_pass,
                'create_db' => $create_db
            ]);

            if (!$test['success']) {
                return ['success' => false, 'error' => 'MySQL-Verbindungsfehler: ' . $test['message']];
            }
            if (!empty($test['db_missing'])) {
                return ['success' => false, 'error' => "Die Datenbank '{$db_name}' existiert nicht. Bitte lege sie an oder aktiviere die Option zum automatischen Erzeugen."];
            }

            // Tabellen anlegen und Daten sichern
            $sqlDriver = new PdoSqlStorageDriver([
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'username' => $db_user,
                'password' => $db_pass,
                'prefix' => $db_prefix
            ]);

            if (!$sqlDriver->init()) {
                return ['success' => false, 'error' => 'Fehler beim Anlegen der MySQL-Tabellen: ' . ($sqlDriver->getLastError() ?? 'Unbekannter Fehler')];
            }
            if (!$sqlDriver->saveData($base_data)) {
                return ['success' => false, 'error' => 'Fehler beim Speichern der Daten in MySQL: ' . ($sqlDriver->getLastError() ?? 'Unbekannter Fehler')];
            }

            // config.local.php erst nach erfolgreicher Einrichtung schreiben
            $local_conf = "<?php\n" .
                "// WeaveCMS Lokale Umgebungskonfiguration (wird nicht ins Git-Repository übertragen)\n" .
                "define('WEAVE_STORAGE_DRIVER', 'mysql');\n" .
                "define('WEAVE_DB_HOST', " . var_export($db_host, true) . ");\n" .
                "define('WEAVE_DB_PORT', " . $db_port . ");\n" .
                "define('WEAVE_DB_NAME', " . var_export($db_name, true) . ");\n" .
                "define('WEAVE_DB_USER', " . var_export($db_user, true) . ");\n" .
                "define('WEAVE_DB_PASS', " . var_export($db_pass, true) . ");\n" .
                "define('WEAVE_DB_PREFIX', " . var_export($db_prefix, true) . ");\n";
            if (@file_put_contents($root . '/config.local.php', $local_conf) === false) {
                return ['success' => false, 'error' => 'Konnte config.local.php nicht schreiben. Bitte prüfe die Schreibrechte im Hauptverzeichnis.'];
            }
        } else {
            // JSON Modus: config.local.php entfernen, falls vorhanden
            if (file_exists($root . '/config.local.php')) {
                @unlink($root . '/config.local.php');
            }
        }

        // data.json stets atomar als Snapshot oder Hauptspeicher sichern
        $jsonDriver = new JsonStorageDriver($root . '/data.json');
        if (!$jsonDriver->saveData($base_data)) {
            return ['success' => false, 'error' => 'Konnte data.json nicht schreiben. Bitte prüfe die Schreibrechte im Hauptverzeichnis.'];
        }

        // Lock-Datei schreiben
        $lock_payload = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => self::VERSION,
            'admin_user' => $admin_user,
            'php_version' => PHP_VERSION,
            'site_name' => $site_name,
            'storage' => $storage_driver
        ];
        if ($storage_driver === 'mysql' && isset($db_name)) {
            $lock_payload['database'] = $db_name;
        }
        $lock_saved = file_put_contents(self::lockFilePath(), json_encode($lock_payload, JSON_PRETTY_PRINT));
        if ($lock_saved === false) {
            return ['success' => false, 'error' => 'Konnte installed.lock nicht erstellen.'];
        }

        // Admin-Session initialisieren mit höchster Berechtigungsstufe (Superadmin)
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = 'usr_superadmin';
        $_SESSION['admin_username'] = $admin_user;
        $_SESSION['admin_role'] = UserManager::ROLE_ADMIN;

        $auth_hash = md5($password_hash . ':' . $pin_hash);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        if (!headers_sent()) {
            setcookie('admin_auth', $auth_hash, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        return ['success' => true, 'site_name' => $site_name];
    }
}
