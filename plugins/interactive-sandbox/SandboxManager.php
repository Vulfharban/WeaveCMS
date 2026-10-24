<?php
/**
 * ============================================================================
 * WeaveCMS – SandboxManager (Phase 15)
 * ============================================================================
 * Verwaltet den Lebenszyklus des interaktiven Demo-Modus:
 * - Starten, Zurücksetzen und Beenden von Sandbox-Sitzungen
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
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_demo_active'])) {
            return true;
        }

        // URL-Trigger prüfen (?demo=start, ?demo=1, ?demo=reset, ?demo=stop)
        $demoParam = $_GET['demo'] ?? '';
        if (in_array($demoParam, ['start', '1', 'reset', 'true'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Startet eine neue Sandbox-Sitzung oder reaktiviert die bestehende.
     */
    public static function startDemoSession(?string $seedFile = null): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['weave_demo_token'])) {
            $_SESSION['weave_demo_token'] = bin2hex(random_bytes(16));
        }

        $token = $_SESSION['weave_demo_token'];
        $_SESSION['weave_demo_active'] = true;
        $_SESSION['weave_demo_started'] = time();
        $_SESSION['weave_demo_last_active'] = time();
        $_SESSION['weave_demo_uploads'] = $_SESSION['weave_demo_uploads'] ?? [];

        // Automatische Anmeldung als Demo-Admin ermöglichen (falls gewünscht oder im Admin-Bereich gestartet)
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
        if (is_array($uploads)) {
            foreach ($uploads as $path) {
                if (file_exists($path) && is_file($path)) {
                    @unlink($path);
                }
            }
        }

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

        // Hochgeladene Demo-Bilder entfernen
        $uploads = $_SESSION['weave_demo_uploads'] ?? [];
        if (is_array($uploads)) {
            foreach ($uploads as $path) {
                if (file_exists($path) && is_file($path)) {
                    @unlink($path);
                }
            }
        }

        unset(
            $_SESSION['weave_demo_active'],
            $_SESSION['weave_demo_token'],
            $_SESSION['weave_demo_started'],
            $_SESSION['weave_demo_last_active'],
            $_SESSION['weave_demo_data'],
            $_SESSION['weave_demo_uploads']
        );

        if (($_SESSION['admin_username'] ?? '') === 'demo_admin') {
            unset(
                $_SESSION['admin_logged_in'],
                $_SESSION['admin_username'],
                $_SESSION['admin_role'],
                $_SESSION['admin_user_id']
            );
        }

        self::$driverInstance = null;
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
        $_SESSION['weave_demo_uploads'][] = $absolutePath;
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
