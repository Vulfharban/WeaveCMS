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
     * Führt den vollständigen System-Check (Health Check) durch.
     * Prüft: PHP-Version (>= 8.0), Dateirechte (data/, images/uploads/, backups/),
     * PHP-Erweiterungen (pdo, gd, mbstring, json, curl).
     * @return array
     */
    public static function runHealthCheck() {
        $root = self::rootPath();
        $results = [
            'can_install' => true,
            'php_version' => [
                'name' => 'PHP-Version (Mindestens 8.0)',
                'current' => PHP_VERSION,
                'min' => '8.0.0',
                'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
                'required' => true,
                'message' => version_compare(PHP_VERSION, '8.0.0', '>=') 
                    ? 'PHP ' . PHP_VERSION . ' erfüllt alle Voraussetzungen.' 
                    : 'PHP 8.0 oder höher ist zwingend erforderlich (Aktuell: ' . PHP_VERSION . ').'
            ],
            'directories' => [],
            'extensions' => []
        ];

        // Verzeichnisse prüfen und bei Bedarf sicher anlegen
        $dirs_to_check = [
            'data' => [
                'path' => $root . '/data',
                'label' => 'Datenverzeichnis (data/)',
                'protected' => true
            ],
            'uploads' => [
                'path' => $root . '/images/uploads',
                'label' => 'Upload-Verzeichnis (images/uploads/)',
                'protected' => false
            ],
            'backups' => [
                'path' => $root . '/backups',
                'label' => 'Backup-Archiv (backups/)',
                'protected' => true
            ]
        ];

        foreach ($dirs_to_check as $key => $info) {
            $p = $info['path'];
            if (!is_dir($p)) {
                @mkdir($p, 0755, true);
            }
            $writable = is_dir($p) && is_writable($p);
            if (!$writable) {
                // Testdatei schreiben, falls is_writable unter Windows täuscht
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
                'required' => true,
                'message' => $writable 
                    ? 'Beschreibbar und einsatzbereit.' 
                    : 'Keine Schreibrechte vorhanden. Bitte Zugriffsrechte (z.B. chmod 755/775) anpassen.'
            ];

            if (!$writable) {
                $results['can_install'] = false;
            }
        }

        // PHP-Module prüfen
        $extensions_to_check = [
            'pdo' => ['name' => 'PDO (Datenbank-Treiber-Basis)', 'required' => true],
            'pdo_mysql' => ['name' => 'PDO MySQL (MySQL / MariaDB Unterstützung)', 'required' => false],
            'pdo_sqlite' => ['name' => 'PDO SQLite (Lokale SQLite SQL-Dateien)', 'required' => false],
            'gd' => ['name' => 'GD Library (Bildverarbeitung & Thumbnails)', 'required' => true],
            'mbstring' => ['name' => 'mbstring (Multibyte-Zeichenketten & UTF-8)', 'required' => true],
            'json' => ['name' => 'JSON (Datenverarbeitung)', 'required' => true],
            'curl' => ['name' => 'cURL (Netzwerk- & Update-Prüfung)', 'required' => false]
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
                    ? 'Modul aktiv.' 
                    : ($info['required'] ? 'Fehlt! Bitte in php.ini aktivieren.' : 'Optional, wird empfohlen.')
            ];

            if ($info['required'] && !$loaded) {
                $results['can_install'] = false;
            }
        }

        if (!$results['php_version']['ok']) {
            $results['can_install'] = false;
        }

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
        if (empty($admin_user)) {
            $admin_user = 'admin';
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
        $password_hash = password_hash($admin_pass, PASSWORD_BCRYPT);
        $pin_hash = password_hash($admin_pin, PASSWORD_BCRYPT);

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

            // Verbindungstest durchführen und DB bei Bedarf anlegen
            $test = StorageManager::testDbConnection([
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'username' => $db_user,
                'password' => $db_pass,
                'create_db' => true
            ]);

            if (!$test['success']) {
                return ['success' => false, 'error' => 'MySQL-Verbindungsfehler: ' . $test['message']];
            }

            // config.local.php schreiben
            $local_conf = "<?php\n" .
                "// WeaveCMS Lokale Umgebungskonfiguration (wird nicht ins Git-Repository übertragen)\n" .
                "define('WEAVE_STORAGE_DRIVER', 'mysql');\n" .
                "define('WEAVE_DB_HOST', " . var_export($db_host, true) . ");\n" .
                "define('WEAVE_DB_PORT', " . $db_port . ");\n" .
                "define('WEAVE_DB_NAME', " . var_export($db_name, true) . ");\n" .
                "define('WEAVE_DB_USER', " . var_export($db_user, true) . ");\n" .
                "define('WEAVE_DB_PASS', " . var_export($db_pass, true) . ");\n" .
                "define('WEAVE_DB_PREFIX', " . var_export($db_prefix, true) . ");\n";
            @file_put_contents($root . '/config.local.php', $local_conf);

            // Tabellen anlegen und Daten sichern
            $sqlDriver = new PdoSqlStorageDriver([
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'username' => $db_user,
                'password' => $db_pass,
                'prefix' => $db_prefix
            ]);

            if (!$sqlDriver->init() || !$sqlDriver->saveData($base_data)) {
                return ['success' => false, 'error' => 'Fehler beim Initialisieren der MySQL-Tabellen.'];
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

        // Admin-Session initialisieren
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_logged_in'] = true;
        $auth_hash = md5($password_hash . ':' . $pin_hash);
        setcookie('admin_auth', $auth_hash, time() + 86400 * 30, '/');

        return ['success' => true, 'site_name' => $site_name];
    }
}
