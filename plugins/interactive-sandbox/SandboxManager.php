<?php
/**
 * ============================================================================
 * WeaveCMS – SandboxManager (Phase 15 & 15.4 Gehärtet)
 * ============================================================================
 * Verwaltet den Lebenszyklus des interaktiven Demo-Modus:
 * - Starten, Pausieren, Fortsetzen, Zurücksetzen und Beenden von Sandbox-Sitzungen
 * - Saubere Session-Isolation: Reale Admin-Sessions werden gesichert und beim
 *   Verlassen der Sandbox nahtlos wiederhergestellt.
 * - Vollständiges Zurücksetzen: Ephemeres JSON und alle hochgeladenen Bilder/Varianten
 *   werden beim Beenden/Reset physisch restlos bereinigt.
 * - Garbage Collection für inaktive Sitzungen (> 30 Minuten)
 * - Upload-Zählung (max. 5 Bilder je Sitzung)
 * - Bereitstellung des EphemeralSessionDriver
 * ============================================================================
 */

require_once __DIR__ . '/EphemeralSessionDriver.php';

class SandboxManager {

    public const MAX_UPLOADS = 5;
    public const LIFETIME_SECONDS = 1800; // 30 Minuten

    protected static ?EphemeralSessionDriver $driverInstance = null;

    /**
     * Prüft, ob für den aktuellen Aufruf oder die Session der Demo-Modus aktiv ist.
     */
    public static function isDemoActive(): bool {
        // Explizites Abschalten per URL-Parameter
        $demoParam = $_GET['demo'] ?? '';
        if (in_array($demoParam, ['0', 'stop', 'pause', 'exit', 'off', 'false', 'normal'], true)) {
            return false;
        }

        // Falls die Demo pausiert wurde, ist der Demo-Modus inaktiv
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_demo_paused'])) {
            return false;
        }

        // Falls die Demo in der Session aktiv ist
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_demo_active'])) {
            return true;
        }

        // URL-Trigger prüfen (?demo=start, ?demo=1, ?demo=reset, ?demo=true, ?demo=resume)
        if (in_array($demoParam, ['start', '1', 'reset', 'true', 'resume'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Prüft, ob eine Demo-Sitzung existiert, aktuell aber pausiert ist.
     */
    public static function isDemoPaused(): bool {
        return session_status() === PHP_SESSION_ACTIVE 
            && !empty($_SESSION['weave_demo_paused']) 
            && !empty($_SESSION['weave_demo_token']);
    }

    /**
     * Startet eine neue Sandbox-Sitzung oder reaktiviert die bestehende.
     */
    public static function startDemoSession(?string $seedFile = null): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Reale Admin-Session sichern, falls vorhanden und nicht bereits Demo-Admin
        if (!empty($_SESSION['admin_logged_in']) && ($_SESSION['admin_username'] ?? '') !== 'demo_admin') {
            $_SESSION['weave_real_admin_backup'] = [
                'admin_logged_in'  => $_SESSION['admin_logged_in'],
                'admin_username'   => $_SESSION['admin_username'] ?? 'admin',
                'admin_role'       => $_SESSION['admin_role'] ?? 'admin',
                'admin_user_id'    => $_SESSION['admin_user_id'] ?? null,
                'admin_user'       => $_SESSION['admin_user'] ?? null,
                'admin_csrf_token' => $_SESSION['admin_csrf_token'] ?? null,
            ];
        }

        if (empty($_SESSION['weave_demo_token'])) {
            $_SESSION['weave_demo_token'] = bin2hex(random_bytes(16));
        }

        $token = $_SESSION['weave_demo_token'];
        $_SESSION['weave_demo_active'] = true;
        unset($_SESSION['weave_demo_paused']);
        $_SESSION['weave_demo_started'] = time();
        $_SESSION['weave_demo_last_active'] = time();
        $_SESSION['weave_demo_uploads'] = $_SESSION['weave_demo_uploads'] ?? [];

        // Automatische Anmeldung als Demo-Admin ermöglichen
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'demo_admin';
        $_SESSION['admin_role'] = 'admin';
        $_SESSION['admin_user_id'] = 'demo_superadmin';

        // Ephemeral Driver instanziieren und Daten initialisieren
        $driver = self::getDriver();
        $driver->loadData();

        // Expired Sessions bereinigen (Lazy Garbage Collection)
        self::cleanupExpired();

        return $token;
    }

    /**
     * Pausiert die Demo-Sitzung und wechselt zur regulären Seite/Admin.
     * Flüchtige Daten bleiben im Speicher/Sandbox, reale Admin-Session wird reaktiviert.
     */
    public static function pauseDemoSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION['weave_demo_active'] = false;
        $_SESSION['weave_demo_paused'] = true;
        self::$driverInstance = null;

        // Reale Admin-Session wiederherstellen, falls vorhanden
        if (!empty($_SESSION['weave_real_admin_backup']) && is_array($_SESSION['weave_real_admin_backup'])) {
            foreach ($_SESSION['weave_real_admin_backup'] as $k => $v) {
                $_SESSION[$k] = $v;
            }
            unset($_SESSION['weave_real_admin_backup']);
        } else {
            // Kein realer Admin: Demo-Admin abmelden, damit kein unbefugter Zugriff auf echten Admin besteht
            if (($_SESSION['admin_username'] ?? '') === 'demo_admin') {
                unset(
                    $_SESSION['admin_logged_in'],
                    $_SESSION['admin_username'],
                    $_SESSION['admin_role'],
                    $_SESSION['admin_user_id'],
                    $_SESSION['admin_user']
                );
            }
        }
    }

    /**
     * Setzt eine pausierte Demo-Sitzung fort.
     */
    public static function resumeDemoSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['weave_demo_token'])) {
            self::startDemoSession();
            return;
        }

        // Reale Admin-Session sichern, falls vorhanden
        if (!empty($_SESSION['admin_logged_in']) && ($_SESSION['admin_username'] ?? '') !== 'demo_admin') {
            $_SESSION['weave_real_admin_backup'] = [
                'admin_logged_in'  => $_SESSION['admin_logged_in'],
                'admin_username'   => $_SESSION['admin_username'] ?? 'admin',
                'admin_role'       => $_SESSION['admin_role'] ?? 'admin',
                'admin_user_id'    => $_SESSION['admin_user_id'] ?? null,
                'admin_user'       => $_SESSION['admin_user'] ?? null,
                'admin_csrf_token' => $_SESSION['admin_csrf_token'] ?? null,
            ];
        }

        $_SESSION['weave_demo_active'] = true;
        unset($_SESSION['weave_demo_paused']);
        $_SESSION['weave_demo_last_active'] = time();

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'demo_admin';
        $_SESSION['admin_role'] = 'admin';
        $_SESSION['admin_user_id'] = 'demo_superadmin';

        self::$driverInstance = null;
        self::getDriver();
    }

    /**
     * Setzt die aktuelle Demo-Sitzung auf den Auslieferungszustand (data.sample.json) zurück.
     */
    public static function resetDemoSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $token = $_SESSION['weave_demo_token'] ?? null;
        if ($token) {
            $driver = new EphemeralSessionDriver($token);
            $driver->destroy();
        }

        // Hochgeladene Demo-Bilder der Sitzung physisch entfernen
        $uploads = $_SESSION['weave_demo_uploads'] ?? [];
        self::deleteFilesAndVariants($uploads);

        $_SESSION['weave_demo_uploads'] = [];
        unset($_SESSION['weave_demo_data']);
        self::$driverInstance = null;

        // Neu starten
        self::startDemoSession();
    }

    /**
     * Beendet die Demo-Sitzung, löscht flüchtige Daten und meldet den Demo-Nutzer ab.
     */
    public static function stopDemoSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $token = $_SESSION['weave_demo_token'] ?? null;
        if ($token) {
            $driver = new EphemeralSessionDriver($token);
            $driver->destroy();
        }

        // Hochgeladene Demo-Bilder physisch entfernen (inkl. Varianten)
        $uploads = $_SESSION['weave_demo_uploads'] ?? [];
        self::deleteFilesAndVariants($uploads);

        // Reale Admin-Session wiederherstellen, falls vorhanden
        if (!empty($_SESSION['weave_real_admin_backup']) && is_array($_SESSION['weave_real_admin_backup'])) {
            foreach ($_SESSION['weave_real_admin_backup'] as $k => $v) {
                $_SESSION[$k] = $v;
            }
            unset($_SESSION['weave_real_admin_backup']);
        } else {
            if (($_SESSION['admin_username'] ?? '') === 'demo_admin') {
                unset(
                    $_SESSION['admin_logged_in'],
                    $_SESSION['admin_username'],
                    $_SESSION['admin_role'],
                    $_SESSION['admin_user_id'],
                    $_SESSION['admin_user']
                );
            }
        }

        unset(
            $_SESSION['weave_demo_active'],
            $_SESSION['weave_demo_paused'],
            $_SESSION['weave_demo_token'],
            $_SESSION['weave_demo_started'],
            $_SESSION['weave_demo_last_active'],
            $_SESSION['weave_demo_data'],
            $_SESSION['weave_demo_uploads']
        );

        self::$driverInstance = null;
    }

    /**
     * Löscht eine Liste von Dateien und deren generierte Bildvarianten physisch vom Dateisystem.
     * @param array $filePaths
     */
    public static function deleteFilesAndVariants(array $filePaths): void {
        foreach ($filePaths as $path) {
            if (!empty($path) && is_string($path) && file_exists($path) && is_file($path)) {
                $dir = dirname($path);
                $filename = basename($path);
                $base = pathinfo($filename, PATHINFO_FILENAME);

                // Hauptdatei löschen
                @unlink($path);

                // Abgeleitete Varianten löschen (_thumb, _medium, _large, .webp, .avif)
                $files = @scandir($dir) ?: [];
                foreach ($files as $f) {
                    if ($f === '.' || $f === '..') {
                        continue;
                    }
                    if (str_starts_with($f, $base . '_') || $f === $base . '.webp' || $f === $base . '.avif') {
                        @unlink($dir . '/' . $f);
                    }
                }
            }
        }
    }

    /**
     * Liefert den Singleton-Treiber für die aktuelle Sandbox.
     */
    public static function getDriver(): EphemeralSessionDriver {
        if (self::$driverInstance !== null) {
            return self::$driverInstance;
        }

        $token = $_SESSION['weave_demo_token'] ?? null;
        if (!$token) {
            $token = bin2hex(random_bytes(16));
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['weave_demo_token'] = $token;
                $_SESSION['weave_demo_active'] = true;
            }
        }

        self::$driverInstance = new EphemeralSessionDriver($token);
        return self::$driverInstance;
    }

    /**
     * Prüft, ob der Nutzer im Demo-Modus noch Bilder hochladen darf.
     */
    public static function canUploadFile(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $count = count($_SESSION['weave_demo_uploads'] ?? []);
        return $count < self::MAX_UPLOADS;
    }

    /**
     * Registriert eine hochgeladene Datei in der Demo-Sitzung.
     */
    public static function recordUploadedFile(string $absolutePath, string $webRelPath): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['weave_demo_uploads']) || !is_array($_SESSION['weave_demo_uploads'])) {
            $_SESSION['weave_demo_uploads'] = [];
        }
        if (!in_array($absolutePath, $_SESSION['weave_demo_uploads'], true)) {
            $_SESSION['weave_demo_uploads'][] = $absolutePath;
        }
    }

    /**
     * Liefert die verbleibende Restzeit der Sandbox-Sitzung in Minuten.
     */
    public static function getRemainingMinutes(): int {
        $lastActive = $_SESSION['weave_demo_last_active'] ?? time();
        $elapsed = time() - $lastActive;
        $remaining = max(0, self::LIFETIME_SECONDS - $elapsed);
        return (int)ceil($remaining / 60);
    }

    /**
     * Garbage Collection: Löscht Sandbox-Dateien, die älter als 30 Minuten sind.
     * @return int Anzahl bereinigter Dateien
     */
    public static function cleanupExpired(): int {
        $sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
        if (!is_dir($sandboxDir)) {
            return 0;
        }

        $deletedCount = 0;
        $files = @scandir($sandboxDir) ?: [];
        $now = time();

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.json')) {
                continue;
            }

            $filePath = $sandboxDir . '/' . $file;
            $mtime = @filemtime($filePath);

            if ($mtime !== false && ($now - $mtime) > self::LIFETIME_SECONDS) {
                if (@unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Rendert das visuelle Top-Banner (im Frontend und Admin).
     */
    public static function renderBanner(string $context = 'frontend'): void {
        $bannerFile = __DIR__ . '/views/banner.php';
        if (file_exists($bannerFile)) {
            include $bannerFile;
        }
    }
}
