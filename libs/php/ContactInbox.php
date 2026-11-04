<?php
/**
 * ============================================================================
 * ContactInbox: Lokale Archivierung & Verwaltung von Kontaktanfragen (Phase 4)
 * Speichert Anfragen unabhängig vom Mailversand in data/contacts.json.
 * Das Verzeichnis data/ wird per .htaccess vor direktem HTTP-Zugriff geschützt.
 * 
 * Phase 15.4 Sandbox-Isolation:
 * Im Demo-Modus (Sandbox) werden reale Nachrichten und E-Mail-Adressen
 * vollständig verborgen. Es wird ausschließlich genau eine anonyme
 * Testnachricht mit der E-Mail-Adresse "testmail@test.de" bereitgestellt.
 * ============================================================================
 */
class ContactInbox {
    const STATUS_NEW = 'neu';
    const STATUS_PROGRESS = 'in_bearbeitung';
    const STATUS_DONE = 'erledigt';

    public const DEMO_MSG_ID = 'demo_msg_001';
    public const DEMO_EMAIL = 'testmail@test.de';

    /**
     * Liefert die gültigen Status-Werte.
     * @return array
     */
    public static function statuses() {
        return [self::STATUS_NEW, self::STATUS_PROGRESS, self::STATUS_DONE];
    }

    /**
     * Prüft, ob aktuell der Demo-/Sandbox-Modus aktiv ist.
     * @return bool
     */
    protected static function isDemoMode(): bool {
        return class_exists('SandboxManager') && SandboxManager::isDemoActive();
    }

    /**
     * Liefert die isolierte Demo-Nachricht für den Sandbox-Modus.
     * Strikte Vorgabe: Zeigt im Demo-Modus ausschließlich genau EINE Testnachricht an.
     * Echte Kontaktdaten und E-Mail-Adressen werden vollständig verborgen.
     *
     * @return array
     */
    public static function getDemoInbox(): array {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $deleted = !empty($_SESSION['weave_demo_inbox_deleted']);
        if ($deleted) {
            return [];
        }

        $status = $_SESSION['weave_demo_inbox_status'] ?? self::STATUS_NEW;

        return [
            [
                'id'         => self::DEMO_MSG_ID,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'name'       => 'Interessent (Demo-Gast)',
                'email'      => self::DEMO_EMAIL,
                'phone'      => '+49 123 4567890',
                'message'    => 'Hallo WeaveCMS-Team, dies ist eine unverbindliche Testanfrage zur Demonstration des Kontaktpostfachs im Demo-Modus. Reale Kontaktdaten bleiben geschützt!',
                'course_ref' => '',
                'status'     => $status,
            ]
        ];
    }

    /**
     * Absoluter Pfad zum Datenverzeichnis (wird bei Bedarf angelegt & geschützt).
     * @return string
     */
    private static function dir() {
        $dir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "# Direkten HTTP-Zugriff sperren\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n");
        }
        return $dir;
    }

    /**
     * Pfad zur Anfragen-Datei.
     * @return string
     */
    private static function file() {
        return self::dir() . '/contacts.json';
    }

    /**
     * Lädt alle Anfragen (neueste zuerst).
     * Im Demo-Modus wird strikt nur die eine anonyme Testnachricht geliefert.
     * @return array
     */
    public static function all() {
        if (self::isDemoMode()) {
            return self::getDemoInbox();
        }

        $file = self::file();
        if (!file_exists($file)) {
            return [];
        }
        $list = json_decode((string)file_get_contents($file), true);
        if (!is_array($list)) {
            return [];
        }
        usort($list, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        return $list;
    }

    /**
     * Zählt Anfragen mit Status "neu".
     * @return int
     */
    public static function countNew() {
        if (self::isDemoMode()) {
            $inbox = self::getDemoInbox();
            $n = 0;
            foreach ($inbox as $e) {
                if (($e['status'] ?? '') === self::STATUS_NEW) {
                    $n++;
                }
            }
            return $n;
        }

        $n = 0;
        foreach (self::all() as $e) {
            if (($e['status'] ?? '') === self::STATUS_NEW) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Schreibt die Liste atomar mit Dateisperre.
     * @param array $list
     * @return bool
     */
    private static function write(array $list) {
        return file_put_contents(
            self::file(),
            json_encode(array_values($list), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }

    /**
     * Archiviert eine neue Anfrage.
     * Im Demo-Modus wird die echte contacts.json nicht verändert.
     * @param array $entry Felder: name, email, phone, message, course_ref
     * @return string|false ID des Eintrags oder false bei Fehler
     */
    public static function add(array $entry) {
        if (self::isDemoMode()) {
            return 'demo_msg_' . bin2hex(random_bytes(3));
        }

        $list = self::all();
        $id = 'c_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
        $list[] = [
            'id' => $id,
            'created_at' => date('Y-m-d H:i:s'),
            'name' => (string)($entry['name'] ?? ''),
            'email' => (string)($entry['email'] ?? ''),
            'phone' => (string)($entry['phone'] ?? ''),
            'message' => (string)($entry['message'] ?? ''),
            'course_ref' => (string)($entry['course_ref'] ?? ''),
            'status' => self::STATUS_NEW,
        ];
        return self::write($list) ? $id : false;
    }

    /**
     * Setzt den Status einer Anfrage.
     * Im Demo-Modus wird der Status der Testnachricht in der Session isoliert gepflegt.
     * @param string $id
     * @param string $status
     * @return bool
     */
    public static function setStatus($id, $status) {
        if (!in_array($status, self::statuses(), true)) {
            return false;
        }

        if (self::isDemoMode()) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if ($id === self::DEMO_MSG_ID) {
                $_SESSION['weave_demo_inbox_status'] = $status;
                return true;
            }
            return false;
        }

        $list = self::all();
        $found = false;
        foreach ($list as &$e) {
            if (($e['id'] ?? '') === $id) {
                $e['status'] = $status;
                $found = true;
            }
        }
        unset($e);
        return $found && self::write($list);
    }

    /**
     * Löscht eine Anfrage unwiderruflich.
     * Im Demo-Modus wird die Löschung in der Session isoliert vermerkt.
     * @param string $id
     * @return bool
     */
    public static function delete($id) {
        if (self::isDemoMode()) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if ($id === self::DEMO_MSG_ID) {
                $_SESSION['weave_demo_inbox_deleted'] = true;
                return true;
            }
            return false;
        }

        $list = self::all();
        $new = array_values(array_filter($list, function ($e) use ($id) {
            return ($e['id'] ?? '') !== $id;
        }));
        return count($new) !== count($list) && self::write($new);
    }
}
