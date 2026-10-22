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
     * Liefert die IP-Adresse des anfragenden Clients.
     * @return string
     */
    public static function getClientIp(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
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
     * Sendet empfohlene HTTP-Sicherheits-Header aus (Phase 9.1).
     * @return void
     */
    public static function applySecurityHeaders(): void {
        if (headers_sent() || php_sapi_name() === 'cli') {
            return;
        }

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

        // 6. HSTS (nur wenn Verbindung tatsächlich über HTTPS erfolgt, niemals auf unverschlüsseltem localhost!)
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
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

        self::saveRateLimits($limits);
        return $limits[$key]['attempts'];
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
}
