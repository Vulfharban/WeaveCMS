<?php
/**
 * ============================================================================
 * WeaveCMS - Zentraler Sicherheitsmanager (SecurityManager) - Phase 9
 * ============================================================================
 * Behandelt:
 * - Adaptive HTTP-Sicherheitsheader (X-Frame-Options, CSP, nosniff, Referrer, Permissions, HTTPS-aware HSTS)
 * - Gehärtete Session-Initialisierung & Inaktivitäts-Timeout
 * - Rate-Limiting & Brute-Force-Schutz mit Dev-Sicherheitsnetz
 * - Sicheres Audit-Logging in logs/security.log
 * - Universelle XSS-Bereinigung
 * ============================================================================
 */

class SecurityManager {

    /**
     * Standard-Lockout-Dauer in Sekunden (15 Minuten).
     */
    public const DEFAULT_LOCKOUT_SECONDS = 900;

    /**
     * Standard-Maximalversuche für Login / PIN.
     */
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * Standard-Inaktivitäts-Timeout für Admin-Sitzungen (60 Minuten).
     */
    public const SESSION_INACTIVITY_TIMEOUT = 3600;

    /**
     * Ermittelt den absoluten WeaveCMS-Root-Pfad.
     * @return string
     */
    public static function rootPath(): string {
        return dirname(__DIR__, 2);
    }

    /**
     * Prüft, ob die aktuelle Anfrage über eine verschlüsselte HTTPS-Verbindung eingeht.
     * @return bool
     */
    public static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string)$_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }

    /**
     * Ermittelt die Basis-URL der WeaveCMS-Installation.
     * @return string
     */
    public static function getBaseUrl(): string {
        $proto = self::isHttps() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace(['/api', '\\api', '/admin', '\\admin'], '', $scriptDir), '/\\');
        return $proto . '://' . $host . $basePath;
    }

    /**
     * Cache für geladene Produktions-Sicherheits-Einstellungen.
     */
    protected static ?array $productionSettingsCache = null;

    /**
     * Lädt die Produktions-Sicherheits-Einstellungen (Phase 17).
     * @return array
     */
    public static function getProductionSettings(): array {
        if (self::$productionSettingsCache !== null) {
            return self::$productionSettingsCache;
        }

        $default = [
            'hsts_preload' => false,
            'production_csp' => false,
            'trusted_proxies' => '127.0.0.1, ::1',
            'alert_email' => '',
            'alert_on_bruteforce' => false,
            'alert_cooldown_minutes' => 15
        ];

        $candidates = [
            self::rootPath() . '/data/data.json',
            self::rootPath() . '/data.json'
        ];

        foreach ($candidates as $f) {
            if (file_exists($f)) {
                $content = @file_get_contents($f);
                if ($content) {
                    $json = json_decode($content, true);
                    if (is_array($json) && isset($json['settings']['prod_security']) && is_array($json['settings']['prod_security'])) {
                        self::$productionSettingsCache = array_merge($default, $json['settings']['prod_security']);
                        return self::$productionSettingsCache;
                    }
                }
                break;
            }
        }

        self::$productionSettingsCache = $default;
        return self::$productionSettingsCache;
    }

    /**
     * Setzt den internen Settings-Cache zurück oder aktualisiert ihn.
     * @param array|null $settings
     * @return void
     */
    public static function setProductionSettings(?array $settings): void {
        self::$productionSettingsCache = $settings;
    }

    /**
     * Prüft, ob eine IP in einem CIDR-Netzbereich liegt (Phase 17.4).
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    public static function ipMatchesCidr(string $ip, string $cidr): bool {
        $cidr = trim($cidr);
        if (empty($cidr)) {
            return false;
        }

        if (strpos($cidr, '/') === false) {
            return ($ip === $cidr);
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int)$mask;

        // IPv4 Check
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($mask < 0 || $mask > 32) return false;
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) return false;
            $bitMask = $mask === 0 ? 0 : (~0 << (32 - $mask));
            return (($ipLong & $bitMask) === ($subnetLong & $bitMask));
        }

        // IPv6 Check
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($mask < 0 || $mask > 128) return false;
            $ipBin = @inet_pton($ip);
            $subnetBin = @inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false) return false;

            $bytes = (int)($mask / 8);
            $bits = $mask % 8;

            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }

            if ($bits > 0) {
                $ipChar = ord($ipBin[$bytes]);
                $subnetChar = ord($subnetBin[$bytes]);
                $bitMask = (0xFF << (8 - $bits)) & 0xFF;
                if (($ipChar & $bitMask) !== ($subnetChar & $bitMask)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Prüft, ob eine Upstream-IP als vertrauenswürdiger Reverse-Proxy konfiguriert ist (Phase 17.4).
     * @param string $ip
     * @param array|string|null $customTrusted
     * @return bool
     */
    public static function isTrustedProxy(string $ip, $customTrusted = null): bool {
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
            return true;
        }

        if ($customTrusted === null) {
            $prod = self::getProductionSettings();
            $customTrusted = $prod['trusted_proxies'] ?? '';
        }

        $list = is_array($customTrusted) ? $customTrusted : preg_split('/[\r\n,]+/', (string)$customTrusted);
        foreach ($list as $entry) {
            $entry = trim($entry);
            if ($entry === '') continue;
            if (self::ipMatchesCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert die IP-Adresse des anfragenden Clients mit Anti-Spoofing-Schutz (Phase 17.4).
     * Forwarded-Header werden AUSSCHLIESSLICH akzeptiert, wenn die direkte Remote-IP ein vertrauenswürdiger Proxy ist.
     * @return string
     */
    public static function getClientIp(): string {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!filter_var($remote, FILTER_VALIDATE_IP)) {
            $remote = '127.0.0.1';
        }

        // Anti-Spoofing: Nur wenn REMOTE_ADDR ein bestätigter Proxy ist, werten wir Header aus
        if (self::isTrustedProxy($remote)) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $cfIp = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
                if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                    return $cfIp;
                }
            }

            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $realIp = trim($_SERVER['HTTP_X_REAL_IP']);
                if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                    return $realIp;
                }
            }

            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($parts as $part) {
                    $candidate = trim($part);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        return $remote;
    }

    /**
     * Prüft, ob es sich um eine lokale Entwicklungs-IP handelt.
     * @param string|null $ip
     * @return bool
     */
    public static function isLocalIp(?string $ip = null): bool {
        $targetIp = $ip ?? self::getClientIp();
        return ($targetIp === '127.0.0.1' || $targetIp === '::1' || $targetIp === 'localhost');
    }

    /**
     * Sendet empfohlene HTTP-Sicherheits-Header aus (Phase 9.1 & Phase 17.1/17.2).
     * @param array|null $customSettings Optionale Einstellungen zur Laufzeit
     * @return void
     */
    public static function applySecurityHeaders(?array $customSettings = null): void {
        if (headers_sent() || php_sapi_name() === 'cli') {
            return;
        }

        $prod = $customSettings ?? self::getProductionSettings();

        // 1. Clickjacking-Schutz
        header('X-Frame-Options: SAMEORIGIN');

        // 2. MIME-Sniffing unterbinden
        header('X-Content-Type-Options: nosniff');

        // 3. Referrer-Datenschutz
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // 4. Zugriff auf Hardware-Sensoren einschränken
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // 5. XSS-Auditor für ältere Browser
        header('X-XSS-Protection: 1; mode=block');

        // 6. HSTS & SSL-Preloading (Phase 17.1)
        if (self::isHttps()) {
            if (!empty($prod['hsts_preload'])) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            } else {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        // 7. Production Content Security Policy (CSP - Phase 17.2)
        if (!empty($prod['production_csp'])) {
            $csp = "default-src 'self'; "
                 . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
                 . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
                 . "font-src 'self' https://fonts.gstatic.com data:; "
                 . "img-src 'self' data: https: blob:; "
                 . "connect-src 'self'; "
                 . "frame-src 'self' https:; "
                 . "media-src 'self'; "
                 . "object-src 'none'; "
                 . "base-uri 'self'; "
                 . "form-action 'self';";
            header("Content-Security-Policy: {$csp}");
        }
    }

    /**
     * Initialisiert eine gehärtete PHP-Sitzung (Phase 9.5).
     * @return void
     */
    public static function initSecureSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::checkInactivityTimeout();
            return;
        }

        // Gehärtete Cookie-Parameter vor dem Session-Start setzen
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        $isSecure = self::isHttps();

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();

        self::checkInactivityTimeout();
    }

    /**
     * Prüft das Inaktivitäts-Timeout für angemeldete Administratoren.
     * @return void
     */
    protected static function checkInactivityTimeout(): void {
        if (empty($_SESSION['admin_logged_in'])) {
            return;
        }

        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity'] > self::SESSION_INACTIVITY_TIMEOUT)) {
            self::logEvent('SESSION_TIMEOUT', 'Administratorsitzung wegen 60 Min. Inaktivität beendet', 'INFO');
            $_SESSION['admin_logged_in'] = false;
            unset($_SESSION['admin_logged_in'], $_SESSION['last_activity']);
            $_SESSION['login_notice'] = 'Deine Sitzung wurde wegen Inaktivität automatisch beendet. Bitte melde dich erneut an.';
            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    /**
     * Generiert die Session-ID kryptografisch neu (z. B. nach Login/PIN-Prüfung).
     * @param bool $deleteOldSession
     * @return void
     */
    public static function regenerateSession(bool $deleteOldSession = true): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Pfad zur Rate-Limits-Speicherdatei.
     * @return string
     */
    protected static function getRateLimitFile(): string {
        $dir = self::rootPath() . '/data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/rate_limits.json';
    }

    /**
     * Lädt gespeicherte Rate-Limit-Einträge und bereinigt abgelaufene Zeitfenster.
     * @return array
     */
    protected static function loadRateLimits(): array {
        $file = self::getRateLimitFile();
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) {
            return [];
        }

        // Bereinigung abgelaufener Einträge
        $now = time();
        $cleaned = [];
        foreach ($data as $key => $entry) {
            if (isset($entry['expires_at']) && $entry['expires_at'] > $now) {
                $cleaned[$key] = $entry;
            }
        }
        return $cleaned;
    }

    /**
     * Speichert die Rate-Limit-Einträge atomar.
     * @param array $data
     * @return void
     */
    protected static function saveRateLimits(array $data): void {
        $file = self::getRateLimitFile();
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Ermittelt den Speicher-Key für eine IP und Aktion.
     * @param string $action
     * @param string|null $identifier
     * @return string
     */
    protected static function getLimitKey(string $action, ?string $identifier = null): string {
        $ip = $identifier ?? self::getClientIp();
        return md5($action . ':' . $ip);
    }

    /**
     * Prüft, ob eine Aktion aktuell gesperrt ist (Phase 9.2).
     * Dev-Sicherheitsnetz: Lokale IPs können via ?reset_rate_limit=1 jederzeit entriegelt werden.
     *
     * @param string $action Name der Aktion (z. B. 'admin_login', 'admin_pin', 'contact_form')
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @param string|null $identifier
     * @return bool True wenn blockiert, sonst False
     */
    public static function isRateLimited(string $action, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $decaySeconds = self::DEFAULT_LOCKOUT_SECONDS, ?string $identifier = null): bool {
        // Dev-Sicherheitsnetz für localhost
        if (isset($_GET['reset_rate_limit']) && self::isLocalIp()) {
            self::clearRateLimit($action, $identifier);
            return false;
        }

        $limits = self::loadRateLimits();
        $key = self::getLimitKey($action, $identifier);

        if (!isset($limits[$key])) {
            return false;
        }

        $entry = $limits[$key];
        $now = time();

        if ($entry['expires_at'] <= $now) {
            return false;
        }

        return ($entry['attempts'] >= $maxAttempts);
    }

    /**
     * Liefert die verbleibende Sperrdauer in Sekunden.
     * @param string $action
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @param string|null $identifier
     * @return int Verbleibende Sekunden (0 falls nicht gesperrt)
     */
    public static function getLockoutRemaining(string $action, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $decaySeconds = self::DEFAULT_LOCKOUT_SECONDS, ?string $identifier = null): int {
        $limits = self::loadRateLimits();
        $key = self::getLimitKey($action, $identifier);

        if (!isset($limits[$key])) {
            return 0;
        }

        $entry = $limits[$key];
        $now = time();

        if ($entry['expires_at'] <= $now || $entry['attempts'] < $maxAttempts) {
            return 0;
        }

        return max(0, $entry['expires_at'] - $now);
    }

    /**
     * Registriert einen fehlgeschlagenen Versuch und liefert die aktuelle Anzahl zurück.
     * @param string $action
     * @param int $decaySeconds
     * @param string|null $identifier
     * @return int Gesamtzahl der Fehlversuche
     */
    public static function recordFailedAttempt(string $action, int $decaySeconds = self::DEFAULT_LOCKOUT_SECONDS, ?string $identifier = null): int {
        $limits = self::loadRateLimits();
        $key = self::getLimitKey($action, $identifier);
        $now = time();

        if (isset($limits[$key]) && $limits[$key]['expires_at'] > $now) {
            $limits[$key]['attempts']++;
            $limits[$key]['expires_at'] = $now + $decaySeconds;
        } else {
            $limits[$key] = [
                'action'     => $action,
                'ip'         => $identifier ?? self::getClientIp(),
                'attempts'   => 1,
                'first_fail' => $now,
                'expires_at' => $now + $decaySeconds
            ];
        }

        $currentAttempts = $limits[$key]['attempts'];
        self::saveRateLimits($limits);

        // Phase 17.5: Automatisierte Admin-E-Mail-Alarmierung bei Lockout
        if ($currentAttempts === self::DEFAULT_MAX_ATTEMPTS) {
            self::checkAndTriggerSecurityAlert($action, $identifier ?? self::getClientIp(), $currentAttempts);
        }

        return $currentAttempts;
    }

    /**
     * Löscht Fehlversuche nach erfolgreicher Authentifizierung.
     * @param string $action
     * @param string|null $identifier
     * @return void
     */
    public static function clearRateLimit(string $action, ?string $identifier = null): void {
        $limits = self::loadRateLimits();
        $key = self::getLimitKey($action, $identifier);
        if (isset($limits[$key])) {
            unset($limits[$key]);
            self::saveRateLimits($limits);
        }
    }

    /**
     * Setzt alle Rate-Limits zurück (z. B. im Notfall per Admin oder CLI).
     * @return void
     */
    public static function resetAllRateLimits(): void {
        $file = self::getRateLimitFile();
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Schreibt ein Ereignis in das Sicherheits-Audit-Log (Phase 9.5).
     * @param string $type Ereignistyp (z. B. 'AUTH_LOGIN_FAIL', 'AUTH_LOGIN_SUCCESS', 'UPLOAD_BLOCKED')
     * @param string $message Beschreibung
     * @param string $level 'INFO', 'WARNING', 'DANGER'
     * @return void
     */
    public static function logEvent(string $type, string $message, string $level = 'INFO'): void {
        $root = self::rootPath();
        $logsDir = $root . '/logs';

        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        // Sicherstellen, dass logs/.htaccess existiert
        $htaccess = $logsDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "# Direkten HTTP-Zugriff auf Logs sperren\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n");
        }

        $logFile = $logsDir . '/security.log';
        $ip = self::getClientIp();
        $time = date('Y-m-d H:i:s');
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 150);

        $line = sprintf(
            "[%s] [%s] [IP: %s] [%s] %s | UA: %s\n",
            $time,
            strtoupper($level),
            $ip,
            strtoupper($type),
            $message,
            $userAgent
        );

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Universelle HTML-Entwertung zur XSS-Prävention (Phase 9.4).
     * @param string|null $input
     * @return string
     */
    public static function escape(?string $input): string {
        return htmlspecialchars($input ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Prüft und versendet einen Sicherheitsalarm per E-Mail an den Administrator (Phase 17.5).
     * @param string $action
     * @param string $ip
     * @param int $attempts
     * @return bool
     */
    public static function checkAndTriggerSecurityAlert(string $action, string $ip, int $attempts): bool {
        $prod = self::getProductionSettings();
        if (empty($prod['alert_on_bruteforce'])) {
            return false;
        }

        $recipient = trim($prod['alert_email'] ?? '');
        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Cooldown-Prüfung via data/security_alerts.json
        $cooldownMinutes = max(5, (int)($prod['alert_cooldown_minutes'] ?? 15));
        $alertFile = self::rootPath() . '/data/security_alerts.json';
        $alerts = [];
        if (file_exists($alertFile)) {
            $alerts = (array)json_decode((string)@file_get_contents($alertFile), true);
        }

        $now = time();
        $cooldownKey = md5($action . ':' . $ip);
        if (isset($alerts[$cooldownKey]) && ($now - (int)$alerts[$cooldownKey]) < ($cooldownMinutes * 60)) {
            // Noch in Cooldown-Phase, keine E-Mail-Flut erzeugen
            return false;
        }

        $alerts[$cooldownKey] = $now;
        // Bereinigung alter Einträge
        foreach ($alerts as $k => $ts) {
            if ($now - $ts > 86400) {
                unset($alerts[$k]);
            }
        }
        @file_put_contents($alertFile, json_encode($alerts, JSON_PRETTY_PRINT), LOCK_EX);

        // SMTP-Konfiguration laden falls vorhanden
        $smtpConfig = null;
        $dataFile = self::rootPath() . '/data/data.json';
        if (!file_exists($dataFile)) {
            $dataFile = self::rootPath() . '/data.json';
        }
        if (file_exists($dataFile)) {
            $d = json_decode((string)@file_get_contents($dataFile), true);
            if (!empty($d['settings']['smtp'])) {
                $smtpConfig = $d['settings']['smtp'];
            }
        }

        $timeStr = date('d.m.Y H:i:s');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unbekannt';
        $subject = "🚨 [WeaveCMS Alarm] Brute-Force Sperre für IP {$ip} ({$action})";

        $htmlBody = '<div style="font-family:sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#fff; border:1px solid #e5e7eb; border-radius:12px;">'
                  . '<div style="background:#dc2626; color:#fff; padding:12px 18px; border-radius:8px; margin-bottom:16px;">'
                  . '<h2 style="margin:0; font-size:1.2rem;">🚨 Sicherheitswarnung: Angriffsversuch abgewehrt</h2>'
                  . '</div>'
                  . '<p style="color:#374151; font-size:0.95rem; line-height:1.5;">'
                  . 'Das WeaveCMS-Schutzsystem hat eine IP-Adresse wegen wiederholter Fehlversuche temporär gesperrt:'
                  . '</p>'
                  . '<table style="width:100%; border-collapse:collapse; margin-bottom:16px; font-size:0.9rem;">'
                  . '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px; font-weight:bold; color:#4b5563;">Blockierte IP:</td><td style="padding:8px; font-family:monospace; color:#dc2626;">' . htmlspecialchars($ip) . '</td></tr>'
                  . '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px; font-weight:bold; color:#4b5563;">Ziel-Aktion:</td><td style="padding:8px; font-family:monospace;">' . htmlspecialchars($action) . '</td></tr>'
                  . '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px; font-weight:bold; color:#4b5563;">Fehlversuche:</td><td style="padding:8px;">' . (int)$attempts . ' Fehlversuche</td></tr>'
                  . '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px; font-weight:bold; color:#4b5563;">Sperrdauer:</td><td style="padding:8px;">15 Minuten</td></tr>'
                  . '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px; font-weight:bold; color:#4b5563;">Zeitpunkt:</td><td style="padding:8px;">' . htmlspecialchars($timeStr) . '</td></tr>'
                  . '<tr><td style="padding:8px; font-weight:bold; color:#4b5563;">User-Agent:</td><td style="padding:8px; font-size:0.8rem; color:#6b7280;">' . htmlspecialchars($ua) . '</td></tr>'
                  . '</table>'
                  . '<p style="color:#6b7280; font-size:0.85rem;">'
                  . 'Diese Benachrichtigung wurde automatisch generiert. Du kannst den Vorfall im Admin-Dashboard unter <i>Sicherheits-Live-Monitor</i> einsehen.'
                  . '</p>'
                  . '</div>';

        $textBody = "WeaveCMS Sicherheitswarnung:\n\n"
                  . "IP: {$ip}\n"
                  . "Aktion: {$action}\n"
                  . "Fehlversuche: {$attempts}\n"
                  . "Zeitpunkt: {$timeStr}\n"
                  . "User-Agent: {$ua}\n"
                  . "Sperrdauer: 15 Minuten\n";

        if (class_exists('SimpleMailer')) {
            $res = SimpleMailer::send($recipient, $subject, $htmlBody, $textBody, '', '', $smtpConfig);
            if ($res['success']) {
                self::logEvent('SECURITY_ALERT_SENT', "Sicherheits-Alarm an {$recipient} versendet für IP {$ip}", 'INFO');
                return true;
            }
        }

        return false;
    }

    /**
     * Erzeugt ein HTML-Tag mit Subresource Integrity (SRI) Hash (Phase 17.2).
     * @param string $type 'script' oder 'css'
     * @param string $url
     * @param string $hash SRI-Hash (z. B. 'sha384-...')
     * @param array $attrs Zusätzliche HTML-Attribute
     * @return string
     */
    public static function renderSriTag(string $type, string $url, string $hash, array $attrs = []): string {
        $attrsStr = '';
        foreach ($attrs as $k => $v) {
            $attrsStr .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
        }

        if ($type === 'script') {
            return '<script src="' . htmlspecialchars($url) . '" integrity="' . htmlspecialchars($hash) . '" crossorigin="anonymous"' . $attrsStr . '></script>';
        }

        return '<link rel="stylesheet" href="' . htmlspecialchars($url) . '" integrity="' . htmlspecialchars($hash) . '" crossorigin="anonymous"' . $attrsStr . '>';
    }

    /**
     * Führt ein POSIX- / Linux-Dateisystem-Berechtigungs-Audit durch (Phase 17.3).
     * Prüft sensible Konfigurationsdateien, Ordner und .htaccess-Abschirmungen.
     * @return array
     */
    public static function auditPermissions(): array {
        $root = self::rootPath();
        $isLinux = (DIRECTORY_SEPARATOR === '/' || PHP_OS_FAMILY === 'Linux');

        $targets = [
            ['path' => 'config.php', 'type' => 'file', 'required' => true, 'rec_file' => '0640', 'rec_text' => '0640 oder 0644'],
            ['path' => 'config.local.php', 'type' => 'file', 'required' => false, 'rec_file' => '0640', 'rec_text' => '0640'],
            ['path' => 'installed.lock', 'type' => 'file', 'required' => false, 'rec_file' => '0640', 'rec_text' => '0640'],
            ['path' => 'data/data.json', 'type' => 'file', 'required' => false, 'rec_file' => '0640', 'rec_text' => '0640 oder 0644'],
            ['path' => 'data', 'type' => 'dir', 'required' => true, 'rec_file' => '0750', 'rec_text' => '0750 oder 0755'],
            ['path' => 'backups', 'type' => 'dir', 'required' => true, 'rec_file' => '0750', 'rec_text' => '0750 oder 0755'],
            ['path' => 'logs', 'type' => 'dir', 'required' => true, 'rec_file' => '0750', 'rec_text' => '0750 oder 0755'],
            ['path' => 'images/uploads', 'type' => 'dir', 'required' => true, 'rec_file' => '0755', 'rec_text' => '0755']
        ];

        $results = [];
        $hasIssues = false;
        $hasCritical = false;

        foreach ($targets as $t) {
            $abs = $root . '/' . $t['path'];
            $exists = file_exists($abs);

            if (!$exists) {
                if ($t['required']) {
                    $results[] = [
                        'path' => $t['path'],
                        'type' => $t['type'],
                        'exists' => false,
                        'status' => 'warning',
                        'perms' => '---',
                        'recommended' => $t['rec_text'],
                        'message' => 'Pfad existiert noch nicht',
                        'fix_cmd' => ($t['type'] === 'dir' ? "mkdir -p {$t['path']}" : "touch {$t['path']}")
                    ];
                }
                continue;
            }

            $perms = fileperms($abs);
            $octal = substr(sprintf('%o', $perms), -4);
            $isWorldWritable = (bool)($perms & 0002);

            $status = 'ok';
            $msg = 'Sicher konfiguriert';

            if ($isLinux) {
                if ($isWorldWritable) {
                    $status = 'danger';
                    $msg = 'Kritisch: Datei/Ordner ist für alle Benutzer beschreibbar (World-Writable)!';
                    $hasCritical = true;
                    $hasIssues = true;
                } elseif ($t['type'] === 'file' && (int)$octal > 0644) {
                    $status = 'warning';
                    $msg = 'Zu freizügige Dateirechte auf Produktivserver.';
                    $hasIssues = true;
                }
            } else {
                $status = 'ok';
                $msg = 'Windows-Umgebung: Rechteverwaltung über Windows-ACLs aktiv.';
            }

            $recOctal = ($t['type'] === 'dir') ? '0750' : '0640';
            $fixCmd = "chmod {$recOctal} " . escapeshellarg($t['path']);

            $results[] = [
                'path' => $t['path'],
                'type' => $t['type'],
                'exists' => true,
                'status' => $status,
                'perms' => $octal,
                'recommended' => $t['rec_text'],
                'is_world_writable' => $isWorldWritable,
                'message' => $msg,
                'fix_cmd' => $fixCmd
            ];
        }

        // Prüfung der .htaccess Abschirmungen
        $htaccessShields = ['data/.htaccess', 'backups/.htaccess', 'logs/.htaccess'];
        foreach ($htaccessShields as $hPath) {
            $hAbs = $root . '/' . $hPath;
            $hExists = file_exists($hAbs);
            $results[] = [
                'path' => $hPath,
                'type' => 'shield',
                'exists' => $hExists,
                'status' => $hExists ? 'ok' : 'danger',
                'perms' => $hExists ? substr(sprintf('%o', fileperms($hAbs)), -4) : '---',
                'recommended' => 'Require all denied',
                'is_world_writable' => false,
                'message' => $hExists ? 'Verzeichnisschutz aktiv (.htaccess)' : 'Schutz fehlt: Verzeichnis ist ungeschützt!',
                'fix_cmd' => $hExists ? '' : "echo 'Require all denied' > {$hPath}"
            ];
            if (!$hExists) {
                $hasCritical = true;
                $hasIssues = true;
            }
        }

        return [
            'is_linux' => $isLinux,
            'os' => PHP_OS_FAMILY,
            'overall_status' => $hasCritical ? 'danger' : ($hasIssues ? 'warning' : 'ok'),
            'has_issues' => $hasIssues,
            'items' => $results
        ];
    }
}
