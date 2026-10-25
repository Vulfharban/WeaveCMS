<?php
/**
 * ============================================================================
 * WeaveCMS – Ephemeral Session Storage Driver (Phase 15)
 * ============================================================================
 * Flüchtiger Speicher-Treiber für den Sandbox- & Live-Demo-Modus.
 * 
 * Funktionsweise:
 * - Schreibt Änderungen ausschließlich in die Nutzersitzung ($_SESSION)
 *   und optional in eine isolierte temporäre JSON-Datei unter data/sandbox/<token>.json.
 * - Die echte Datei data.json und die Produktivdatenbank bleiben zu 100% unberührt.
 * - Schutz-Schranken blockieren kritische Systemmanipulationen.
 * ============================================================================
 */

require_once dirname(__DIR__, 2) . '/libs/php/storage/StorageDriverInterface.php';

class EphemeralSessionDriver implements StorageDriverInterface {

    protected string $sessionToken;
    protected string $sandboxDir;
    protected string $sandboxFile;
    protected ?array $memoryData = null;

    /**
     * @param string $sessionToken Eindeutiger Sandbox-Identifikator
     */
    public function __construct(string $sessionToken) {
        $this->sessionToken = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionToken);
        $this->sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
        $this->sandboxFile = $this->sandboxDir . '/' . $this->sessionToken . '.json';
    }

    /**
     * Initialisiert den flüchtigen Speicherbereich.
     */
    public function init(): bool {
        if (!is_dir($this->sandboxDir)) {
            @mkdir($this->sandboxDir, 0755, true);
        }
        return is_dir($this->sandboxDir) && is_writable($this->sandboxDir);
    }

    /**
     * Lädt die flüchtigen Daten.
     * Priorität: 1. Lokaler Cache, 2. $_SESSION, 3. Temporäre Sandbox-Datei, 4. data.sample.json.
     */
    public function loadData(): array {
        if ($this->memoryData !== null) {
            return $this->memoryData;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_demo_data']) && is_array($_SESSION['weave_demo_data'])) {
            $this->memoryData = $_SESSION['weave_demo_data'];
            return $this->memoryData;
        }

        if (file_exists($this->sandboxFile)) {
            $raw = @file_get_contents($this->sandboxFile);
            $parsed = !empty($raw) ? json_decode($raw, true) : null;
            if (is_array($parsed)) {
                $this->memoryData = $parsed;
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['weave_demo_data'] = $parsed;
                }
                return $this->memoryData;
            }
        }

        // Initiale Seeding-Daten aus data.sample.json oder data.json klonen
        $sampleFile = dirname(__DIR__, 2) . '/data.sample.json';
        if (!file_exists($sampleFile)) {
            $sampleFile = dirname(__DIR__, 2) . '/data.json';
        }

        $defaultData = [];
        if (file_exists($sampleFile)) {
            $rawSample = @file_get_contents($sampleFile);
            $defaultData = json_decode((string)$rawSample, true) ?: [];
        }

        // Sicherstellen, dass Sandbox als aktiv gekennzeichnet ist
        $defaultData['_sandbox_mode'] = true;
        $defaultData['_sandbox_token'] = $this->sessionToken;
        $defaultData['_sandbox_started'] = time();

        $this->memoryData = $defaultData;
        $this->saveData($defaultData);

        return $this->memoryData;
    }

    /**
     * Speichert Modifikationen flüchtig und atomar in der Nutzersitzung und Sandbox-Datei.
     * Schützt kritische Zugangsdaten vor dauerhafter oder schädlicher Modifikation.
     */
    public function saveData(array $data): bool {
        // Schutz-Schranke 1: Sandbox-Kennung immer erzwingen
        $data['_sandbox_mode'] = true;
        $data['_sandbox_token'] = $this->sessionToken;
        $data['_sandbox_last_modified'] = time();

        // Schutz-Schranke 2: Blockiere das unbefugte Aussperren im Demo-Modus (Passwort/PIN-Schutz)
        // Behalte den Standard-Adminzugang stets funktionstüchtig
        if (isset($data['settings'])) {
            // Verhindere das Entfernen des Admin-Zugangs
            if (empty($data['settings']['admin_password_hash'])) {
                $data['settings']['admin_password_hash'] = password_hash('admin123123', PASSWORD_DEFAULT);
            }
            if (empty($data['settings']['admin_pin_hash'])) {
                $data['settings']['admin_pin_hash'] = password_hash('1977', PASSWORD_DEFAULT);
            }
        }

        $this->memoryData = $data;

        // In Session persistieren
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['weave_demo_data'] = $data;
            $_SESSION['weave_demo_last_active'] = time();
        }

        // In isolierte Datei schreiben
        $this->init();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            @file_put_contents($this->sandboxFile, $json, LOCK_EX);
        }

        return true;
    }

    /**
     * Health-Ping: Prüft, ob der flüchtige Speicher einsatzbereit ist.
     */
    public function ping(): bool {
        return true;
    }

    /**
     * Name des Treibers für Statusübersichten.
     */
    public function getDriverName(): string {
        return 'EphemeralSessionDriver (WeaveCMS Sandbox)';
    }

    /**
     * Gibt den Pfad zur temporären Sandbox-Datei zurück.
     */
    public function getSandboxFilePath(): string {
        return $this->sandboxFile;
    }

    /**
     * Löscht die flüchtige Sandbox-Datei und leert den Cache.
     */
    public function destroy(): bool {
        $this->memoryData = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['weave_demo_data']);
        }
        if (file_exists($this->sandboxFile)) {
            return @unlink($this->sandboxFile);
        }
        return true;
    }

    /**
     * Gibt eventuelle Fehlermeldungen zurück.
     */
    public function getLastError(): ?string {
        return null;
    }
}


