<?php
/**
 * ============================================================================
 * WeaveCMS - Authentifizierungs- & Sicherheitsmanager (AuthManager)
 * ============================================================================
 * Verwaltet Login, Logout, Session-Verifizierung, Passwort- und PIN-Prüfungen
 * sowie kryptografische CSRF-Token für Formulare.
 */

class AuthManager {

    /**
     * Startet eine sichere Session, falls noch keine aktiv ist.
     * @return void
     */
    public static function initSession(): void {
        SecurityManager::initSecureSession();
    }

    /**
     * Erzeugt oder liefert das aktive CSRF-Token der aktuellen Sitzung.
     * @return string
     */
    public static function getCsrfToken(): string {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Überprüft ein übergebenes CSRF-Token gegen die aktive Sitzung.
     * @param string|null $submittedToken
     * @return bool
     */
    public static function verifyCsrfToken(?string $submittedToken): bool {
        self::initSession();
        if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submittedToken);
    }

    /**
     * Ermittelt den Authentifizierungs-Hash (z. B. für "Angemeldet bleiben"-Cookies).
     * @param array|null $data Konfigurationsdaten mit Passwort- & PIN-Hashes
     * @return string
     */
    public static function getAuthHash(?array $data = null): string {
        $pw_hash = $data['settings']['admin_password_hash'] ?? (defined('DEFAULT_ADMIN_PASSWORD') ? md5(DEFAULT_ADMIN_PASSWORD) : '');
        $pin_hash = $data['settings']['admin_pin_hash'] ?? (defined('DEFAULT_ADMIN_PIN') ? md5(DEFAULT_ADMIN_PIN) : '');
        return md5($pw_hash . ':' . $pin_hash);
    }

    /**
     * Prüft, ob aktuell eine gültige Administratorsitzung vorliegt.
     * @param array|null $data
     * @return bool
     */
    public static function isLoggedIn(?array $data = null): bool {
        self::initSession();

        // 1. Session-Prüfung
        if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return true;
        }

        // 2. Cookie-Prüfung ("Angemeldet bleiben")
        $auth_hash = self::getAuthHash($data);
        if (isset($_COOKIE['admin_auth']) && hash_equals($auth_hash, $_COOKIE['admin_auth'])) {
            $_SESSION['admin_logged_in'] = true;
            SecurityManager::regenerateSession(true);
            return true;
        }

        // 3. Optionaler Zugriff via URL-Token (nur aktiv, falls in config.local.php explizit definiert)
        if (defined('ADMIN_TOKEN') && ADMIN_TOKEN !== '' && isset($_GET['token']) && hash_equals(ADMIN_TOKEN, (string)$_GET['token'])) {
            $_SESSION['admin_logged_in'] = true;
            SecurityManager::regenerateSession(true);
            setcookie('admin_auth', $auth_hash, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'domain'   => '',
                'secure'   => SecurityManager::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Verifiziert Login-Zugangsdaten (Passwort + PIN).
     * @param string $password
     * @param string $pin
     * @param array|null $data
     * @return bool
     */
    public static function verifyAdminAuth(string $password, string $pin, ?array $data = null): bool {
        $pw_hash = $data['settings']['admin_password_hash'] ?? null;
        $pin_hash = $data['settings']['admin_pin_hash'] ?? null;

        // Passwort prüfen
        $pw_ok = false;
        if (!empty($pw_hash)) {
            $pw_ok = password_verify($password, $pw_hash);
        } elseif (defined('DEFAULT_ADMIN_PASSWORD') && DEFAULT_ADMIN_PASSWORD !== '') {
            $pw_ok = ($password === DEFAULT_ADMIN_PASSWORD);
        }

        // PIN prüfen
        $pin_ok = false;
        if (!empty($pin_hash)) {
            $pin_ok = password_verify($pin, $pin_hash);
        } elseif (defined('DEFAULT_ADMIN_PIN') && DEFAULT_ADMIN_PIN !== '') {
            $pin_ok = ($pin === DEFAULT_ADMIN_PIN);
        }

        return ($pw_ok && $pin_ok);
    }

    /**
     * Verifiziert ausschließlich die Sicherheits-PIN für geschützte Aktionen (z. B. Restore, Migration).
     * Mit automatischem Brute-Force-Schutz (Phase 9.2).
     * @param string $pin
     * @param array|null $data
     * @return bool
     */
    public static function verifyAdminPin(string $pin, ?array $data = null): bool {
        if (empty($pin)) {
            return false;
        }

        // Brute-Force-Schutz für PIN
        if (SecurityManager::isRateLimited('admin_pin')) {
            SecurityManager::logEvent('AUTH_PIN_BLOCKED', 'PIN-Verifizierung abgewiesen wegen Rate-Limit-Sperre', 'WARNING');
            return false;
        }

        $pin_hash = $data['settings']['admin_pin_hash'] ?? null;
        $is_valid = false;

        if (!empty($pin_hash)) {
            $is_valid = password_verify($pin, $pin_hash);
        } elseif (defined('DEFAULT_ADMIN_PIN') && DEFAULT_ADMIN_PIN !== '') {
            $is_valid = ($pin === DEFAULT_ADMIN_PIN);
        }

        if ($is_valid) {
            SecurityManager::clearRateLimit('admin_pin');
            SecurityManager::logEvent('AUTH_PIN_SUCCESS', 'Sicherheits-PIN erfolgreich verifiziert', 'INFO');
            return true;
        }

        $fails = SecurityManager::recordFailedAttempt('admin_pin');
        SecurityManager::logEvent('AUTH_PIN_FAIL', "Fehlgeschlagene PIN-Verifizierung (Fehlversuch {$fails})", 'WARNING');
        return false;
    }

    /**
     * Meldet den Administrator an und setzt Sitzungs-Flags und Cookies (Phase 9.2 & 9.5).
     * @param string $password
     * @param string $pin
     * @param array|null $data
     * @return bool
     */
    public static function login(string $password, string $pin, ?array &$data = null, ?string $usernameOrEmail = null): bool {
        self::initSession();

        // Brute-Force-Schutz für Login
        if (SecurityManager::isRateLimited('admin_login')) {
            SecurityManager::logEvent('AUTH_LOGIN_BLOCKED', 'Anmeldeversuch abgewiesen wegen Rate-Limit-Sperre', 'WARNING');
            return false;
        }

        $authenticatedUser = null;
        if ($data !== null) {
            $identifier = !empty($usernameOrEmail) ? $usernameOrEmail : 'admin';
            $authenticatedUser = UserManager::authenticate($identifier, $password, $pin, $data);
        }

        if ($authenticatedUser !== null) {
            SecurityManager::clearRateLimit('admin_login');
            SecurityManager::regenerateSession(true);

            $_SESSION['admin_logged_in'] = true;
            UserManager::setCurrentUser($authenticatedUser);
            $auth_hash = self::getAuthHash($data);

            setcookie('admin_auth', $auth_hash, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'domain'   => '',
                'secure'   => SecurityManager::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            SecurityManager::logEvent('AUTH_LOGIN_SUCCESS', "Erfolgreicher Login für Benutzer '{$authenticatedUser['username']}' (Rolle: {$authenticatedUser['role']})", 'INFO');
            return true;
        }

        if (self::verifyAdminAuth($password, $pin, $data)) {
            SecurityManager::clearRateLimit('admin_login');
            SecurityManager::regenerateSession(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = UserManager::ROLE_ADMIN;
            $_SESSION['admin_username'] = 'admin';
            $auth_hash = self::getAuthHash($data);

            setcookie('admin_auth', $auth_hash, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'domain'   => '',
                'secure'   => SecurityManager::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            SecurityManager::logEvent('AUTH_LOGIN_SUCCESS', 'Erfolgreicher Administrator-Login', 'INFO');
            return true;
        }

        $fails = SecurityManager::recordFailedAttempt('admin_login');
        SecurityManager::logEvent('AUTH_LOGIN_FAIL', "Fehlgeschlagener Administrator-Login (Fehlversuch {$fails})", 'WARNING');
        return false;
    }

    /**
     * Meldet den Administrator ab und löscht Sitzung und Authentifizierungs-Cookies.
     * @return void
     */
    public static function logout(): void {
        self::initSession();
        SecurityManager::logEvent('AUTH_LOGOUT', 'Administrator abgemeldet', 'INFO');
        unset($_SESSION['admin_user_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['admin_logged_in']);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]);
        }
        session_destroy();
        setcookie('admin_auth', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => SecurityManager::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
