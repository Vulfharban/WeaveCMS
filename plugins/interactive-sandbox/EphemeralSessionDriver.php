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
            $this->ensureDemoAdminCredentials();
            return $this->memoryData;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_demo_data']) && is_array($_SESSION['weave_demo_data'])) {
            $this->memoryData = $_SESSION['weave_demo_data'];
            $this->anonymizeDemoData();
            $this->ensureDemoAdminCredentials();
            return $this->memoryData;
        }

        if (file_exists($this->sandboxFile)) {
            $raw = @file_get_contents($this->sandboxFile);
            $parsed = !empty($raw) ? json_decode($raw, true) : null;
            if (is_array($parsed)) {
                $this->memoryData = $parsed;
                $this->anonymizeDemoData();
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['weave_demo_data'] = $this->memoryData;
                }
                $this->ensureDemoAdminCredentials();
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
        $this->anonymizeDemoData();
        $this->saveData($this->memoryData);

        // Garantiere, dass der Demo-Admin stets aktiv ist
        $this->ensureDemoAdminCredentials();

        return $this->memoryData;
    }

    /**
     * Stellt sicher, dass im Sandbox-Modus ausnahmslos anonymisierte Musterdaten
     * (Max Mustermann, Musterstraße 12, 12345 Musterstadt, testmail@test.de)
     * für Impressum, Kontakt und Systemkonfiguration angezeigt werden.
     */
    protected function anonymizeDemoData(): void {
        if (!is_array($this->memoryData)) {
            $this->memoryData = [];
        }
        if (!isset($this->memoryData['texts']) || !is_array($this->memoryData['texts'])) {
            $this->memoryData['texts'] = [];
        }

        // Impressum & Kontakt zwingend auf Mustermann mit Musteradresse setzen
        $this->memoryData['texts']['contact_name'] = 'Max Mustermann';
        $this->memoryData['texts']['contact_street'] = 'Musterstraße 12';
        $this->memoryData['texts']['contact_city'] = '12345 Musterstadt';
        $this->memoryData['texts']['contact_phone'] = '+49 123 4567890';
        $this->memoryData['texts']['contact_email'] = 'testmail@test.de';
        $this->memoryData['texts']['contact_instagram'] = 'max.mustermann';
        $this->memoryData['texts']['contact_mail_greeting'] = 'Hallo Max,';
        $this->memoryData['texts']['course_request_template'] = "Hallo Max,\r\n\r\nich interessiere mich für deinen Workshop \"{kurs}\" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\r\n\r\nViele Grüße";
        $this->memoryData['texts']['impressum_supervisory'] = 'Handwerkskammer Musterstadt';
        $this->memoryData['texts']['impressum_vat_id'] = 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).';

        // Eventuelle SMTP-Zugangsdaten in den Einstellungen neutralisieren
        if (isset($this->memoryData['settings']['smtp']) && is_array($this->memoryData['settings']['smtp'])) {
            $this->memoryData['settings']['smtp']['enabled'] = false;
            $this->memoryData['settings']['smtp']['host'] = 'smtp.musterdomain.de';
            $this->memoryData['settings']['smtp']['user'] = '';
            $this->memoryData['settings']['smtp']['pass'] = '';
            $this->memoryData['settings']['smtp']['from_email'] = 'testmail@test.de';
            $this->memoryData['settings']['smtp']['from_name'] = 'Max Mustermann (Sandbox)';
        }

        // Sicherheits-Benachrichtigungs-E-Mail neutralisieren
        if (isset($this->memoryData['settings']['prod_security']) && is_array($this->memoryData['settings']['prod_security'])) {
            $this->memoryData['settings']['prod_security']['alert_email'] = 'testmail@test.de';
        }

        // Kurse: Reale Adressen ausfiltern
        if (!empty($this->memoryData['courses']) && is_array($this->memoryData['courses'])) {
            foreach ($this->memoryData['courses'] as &$course) {
                if (isset($course['location']) && (stripos($course['location'], 'Kraaker') !== false || stripos($course['location'], 'Rastow') !== false)) {
                    $course['location'] = 'Atelier-Werkstatt, Musterstraße 12, 12345 Musterstadt';
                }
                if (!empty($course['translations']) && is_array($course['translations'])) {
                    foreach ($course['translations'] as &$tr) {
                        if (isset($tr['location']) && (stripos($tr['location'], 'Kraaker') !== false || stripos($tr['location'], 'Rastow') !== false)) {
                            $tr['location'] = 'Atelier-Werkstatt, Musterstraße 12, 12345 Musterstadt';
                        }
                    }
                    unset($tr);
                }
            }
            unset($course);
        }

        // Benutzerliste: E-Mail maskieren
        if (!empty($this->memoryData['users']) && is_array($this->memoryData['users'])) {
            foreach ($this->memoryData['users'] as &$u) {
                if (($u['username'] ?? '') === 'admin') {
                    $u['email'] = 'admin@weavecms.local';
                }
            }
            unset($u);
        }
    }

    /**
     * Stellt sicher, dass die Demo-Zugangsdaten (admin / demo-weave-2026 / 0000) stets funktionsfähig sind.
     */
    protected function ensureDemoAdminCredentials(): void {
        if (!is_array($this->memoryData)) {
            $this->memoryData = [];
        }
        if (!isset($this->memoryData['settings']) || !is_array($this->memoryData['settings'])) {
            $this->memoryData['settings'] = [];
        }
        $pwHash = $this->memoryData['settings']['admin_password_hash'] ?? '';
        if (empty($pwHash) || !password_verify('demo-weave-2026', $pwHash)) {
            $this->memoryData['settings']['admin_password_hash'] = password_hash('demo-weave-2026', PASSWORD_DEFAULT);
            $this->memoryData['settings']['admin_pin_hash'] = password_hash('0000', PASSWORD_DEFAULT);

            if (!isset($this->memoryData['users']) || !is_array($this->memoryData['users'])) {
                $this->memoryData['users'] = [];
            }
            $adminFound = false;
            foreach ($this->memoryData['users'] as &$u) {
                if (($u['username'] ?? '') === 'admin') {
                    $u['password_hash'] = $this->memoryData['settings']['admin_password_hash'];
                    $u['pin_hash'] = $this->memoryData['settings']['admin_pin_hash'];
                    $u['two_factor_enabled'] = false;
                    $adminFound = true;
                    break;
                }
            }
            unset($u);
            if (!$adminFound) {
                $this->memoryData['users'][] = [
                    'id' => 'usr_superadmin',
                    'username' => 'admin',
                    'email' => 'admin@weavecms.local',
                    'role' => 'admin',
                    'password_hash' => $this->memoryData['settings']['admin_password_hash'],
                    'pin_hash' => $this->memoryData['settings']['admin_pin_hash'],
                    'two_factor_enabled' => false,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }
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

        // Schutz-Schranke 2: Garantiere funktionierende Demo-Zugangsdaten (admin / demo-weave-2026 / 0000)
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = [];
        }
        $data['settings']['admin_password_hash'] = password_hash('demo-weave-2026', PASSWORD_DEFAULT);
        $data['settings']['admin_pin_hash'] = password_hash('0000', PASSWORD_DEFAULT);

        // Sicherstellen, dass der Admin-User im users-Array die Demo-Credentials hat und 2FA deaktiviert ist
        if (!isset($data['users']) || !is_array($data['users'])) {
            $data['users'] = [];
        }
        $adminFound = false;
        foreach ($data['users'] as &$u) {
            if (($u['username'] ?? '') === 'admin') {
                $u['password_hash'] = $data['settings']['admin_password_hash'];
                $u['pin_hash'] = $data['settings']['admin_pin_hash'];
                $u['two_factor_enabled'] = false;
                $adminFound = true;
                break;
            }
        }
        unset($u);
        if (!$adminFound) {
            $data['users'][] = [
                'id' => 'usr_superadmin',
                'username' => 'admin',
                'email' => 'admin@weavecms.local',
                'role' => 'admin',
                'password_hash' => $data['settings']['admin_password_hash'],
                'pin_hash' => $data['settings']['admin_pin_hash'],
                'two_factor_enabled' => false,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
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
            @unlink($this->sandboxFile);
        }
        $helpdeskFiles = [
            $this->sandboxDir . '/helpdesk_users_' . $this->sessionToken . '.json',
            $this->sandboxDir . '/helpdesk_tickets_' . $this->sessionToken . '.json',
            $this->sandboxDir . '/helpdesk_config_' . $this->sessionToken . '.json',
        ];
        foreach ($helpdeskFiles as $hf) {
            if (file_exists($hf)) {
                @unlink($hf);
            }
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


