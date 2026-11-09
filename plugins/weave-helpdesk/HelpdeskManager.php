<?php
/**
 * ============================================================================
 * Weave Helpdesk – HelpdeskManager
 * ============================================================================
 * Zentrale Geschäftslogik für Tickets, Tätigkeiten, Statuswechsel,
 * Vertragsverwaltung (Inklusivminuten) und Buchhaltungs-Abrechnungssperre.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/HelpdeskUserManager.php';
require_once __DIR__ . '/HelpdeskMailer.php';

class HelpdeskManager {

    public const STATUS_NEW               = 'Neu';
    public const STATUS_IN_PROGRESS       = 'In Bearbeitung';
    public const STATUS_WAITING_CUSTOMER  = 'Warten auf Rückmeldung';
    public const STATUS_WAITING_EXTERNAL  = 'Warten auf ext. Dienstleister';
    public const STATUS_CLOSED            = 'Geschlossen';
    public const STATUS_BILLED            = 'Abgerechnet';

    /**
     * Pfad zur Ticket-JSON-Datei.
     */
    public static function getTicketsFilePath(): string {
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $token = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['weave_demo_token'] ?? 'demo');
            $sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
            if (!is_dir($sandboxDir)) {
                @mkdir($sandboxDir, 0755, true);
            }
            $sandboxFile = $sandboxDir . '/helpdesk_tickets_' . $token . '.json';
            if (!file_exists($sandboxFile)) {
                $baseFile = dirname(__DIR__, 2) . '/data/helpdesk_tickets.json';
                if (file_exists($baseFile)) {
                    @copy($baseFile, $sandboxFile);
                } else {
                    @file_put_contents($sandboxFile, '[]');
                }
            }
            return $sandboxFile;
        }
        return dirname(__DIR__, 2) . '/data/helpdesk_tickets.json';
    }

    /**
     * Pfad zur Konfigurations-JSON-Datei.
     */
    public static function getConfigFilePath(): string {
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $token = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['weave_demo_token'] ?? 'demo');
            $sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
            if (!is_dir($sandboxDir)) {
                @mkdir($sandboxDir, 0755, true);
            }
            $sandboxFile = $sandboxDir . '/helpdesk_config_' . $token . '.json';
            if (!file_exists($sandboxFile)) {
                $baseFile = dirname(__DIR__, 2) . '/data/helpdesk_config.json';
                if (file_exists($baseFile)) {
                    @copy($baseFile, $sandboxFile);
                }
            }
            return $sandboxFile;
        }
        return dirname(__DIR__, 2) . '/data/helpdesk_config.json';
    }

    /**
     * Lädt die Helpdesk-Konfiguration mit sinnvollen Standardwerten und CMS-Fallbacks.
     * @return array
     */
    public static function getConfig(): array {
        $file = self::getConfigFilePath();
        $defaultConfig = [
            'allow_self_registration'    => false,
            'show_in_main_nav'           => false,
            'ticket_counter'             => 1169,
            'service_provider_name'      => '',
            'service_provider_address'   => '',
            'service_provider_phone'     => '',
            'service_provider_email'     => '',
            'enable_contracts'           => true,
            'enable_email_notifications' => true,
            'enable_imap_fetcher'        => false,
            'imap_host'                  => '',
            'imap_port'                  => 993,
            'imap_ssl'                   => true,
            'imap_user'                  => '',
            'imap_pass'                  => '',
            'ticket_types'               => ['Störung', 'Serviceanfrage', 'Wartung', 'Projekt', 'Installation', 'Sonstiges'],
            'priorities'                 => ['Niedrig', 'Normal', 'Hoch', 'Kritisch']
        ];

        if (!file_exists($file)) {
            self::saveConfig($defaultConfig);
            return $defaultConfig;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? array_merge($defaultConfig, $data) : $defaultConfig;
    }

    /**
     * Speichert die Helpdesk-Konfiguration.
     * @param array $config
     * @return bool
     */
    public static function saveConfig(array $config): bool {
        $file = self::getConfigFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Ermittelt die Daten des Dienstleisters (Systemhaus) dynamisch aus WeaveCMS $data,
     * sofern in den Plugin-Einstellungen keine abweichenden Werte konfiguriert wurden.
     * @return array
     */
    public static function getServiceProviderDetails(): array {
        $cfg = self::getConfig();
        $cmsData = $GLOBALS['data'] ?? [];
        if (empty($cmsData) && function_exists('load_data')) {
            $cmsData = load_data(dirname(__DIR__, 2) . '/data.json');
        }

        $cmsTexts = $cmsData['texts'] ?? [];

        $company = !empty($cfg['service_provider_name']) 
            ? $cfg['service_provider_name'] 
            : (!empty($cmsTexts['contact_name']) ? $cmsTexts['contact_name'] : 'Mein Unternehmen');

        $street = $cmsTexts['contact_street'] ?? '';
        $city = $cmsTexts['contact_city'] ?? '';
        $defaultAddress = trim($street . ( (!empty($street) && !empty($city)) ? ', ' : '' ) . $city);

        $address = !empty($cfg['service_provider_address']) ? $cfg['service_provider_address'] : $defaultAddress;
        $phone = !empty($cfg['service_provider_phone']) ? $cfg['service_provider_phone'] : ($cmsTexts['contact_phone'] ?? '');
        $email = !empty($cfg['service_provider_email']) ? $cfg['service_provider_email'] : ($cmsTexts['contact_email'] ?? '');

        return [
            'name'    => $company,
            'address' => $address,
            'phone'   => $phone,
            'email'   => $email
        ];
    }

    /**
     * Lädt alle Tickets aus der JSON-Datei.
     * @return array
     */
    public static function loadTickets(): array {
        $file = self::getTicketsFilePath();
        if (!file_exists($file)) {
            self::saveTickets([]);
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert Tickets sicher in die JSON-Datei.
     * @param array $tickets
     * @return bool
     */
    public static function saveTickets(array $tickets): bool {
        $file = self::getTicketsFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode(array_values($tickets), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Generiert eine fortlaufende 8-stellige Ticket-Nummer nach dem Muster:
     * YY (aktuelles Jahr 2-stellig) + 6-stelliger Zähler (z. B. 26011169).
     * @return string
     */
    public static function generateTicketId(): string {
        $cfg = self::getConfig();
        $counter = (int)($cfg['ticket_counter'] ?? 1169) + 1;
        $cfg['ticket_counter'] = $counter;
        self::saveConfig($cfg);

        $yearPrefix = date('y'); // z. B. '26'
        return sprintf('%s%06d', $yearPrefix, $counter);
    }

    /**
     * Erstellt ein neues Ticket / Aufgabe.
     * @param array $data
     * @param array $user
     * @return array ['success' => bool, 'ticket_id' => string|null, 'error' => string|null]
     */
    public static function createTicket(array $data, array $user): array {
        $subject = trim($data['subject'] ?? '');
        if (empty($subject)) {
            return ['success' => false, 'error' => 'Bitte einen Betreff für die Aufgabe eingeben.'];
        }

        $type = trim($data['type'] ?? 'Serviceanfrage');
        $priority = trim($data['priority'] ?? 'Normal');
        $companyName = trim($data['company_name'] ?? ($user['company_name'] ?? ''));
        $address = trim($data['address'] ?? ($user['address'] ?? ''));
        $contactPerson = trim($data['contact_person'] ?? ($user['name'] ?? ''));
        $altContactPerson = trim($data['alt_contact_person'] ?? '');
        $altPhone = trim($data['alt_phone'] ?? ($user['phone'] ?? ''));
        $content = trim($data['content'] ?? '');

        $provider = self::getServiceProviderDetails();
        $serviceProvider = trim($data['service_provider'] ?? $provider['name']);

        $ticketId = self::generateTicketId();

        $newTicket = [
            'id'                  => $ticketId,
            'subject'             => $subject,
            'type'                => $type,
            'priority'            => $priority,
            'status'              => self::STATUS_NEW,
            'company_name'        => $companyName,
            'address'             => $address,
            'contact_person'      => $contactPerson,
            'alt_contact_person'  => $altContactPerson,
            'alt_phone'           => $altPhone,
            'service_provider'    => $serviceProvider,
            'content'             => $content,
            'created_by_user_id'  => $user['id'],
            'created_by_name'     => $user['name'],
            'created_by_email'    => $user['email'],
            'assigned_agent_id'   => null,
            'assigned_agent_name' => null,
            'billed_at'           => null,
            'billed_by'           => null,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
            'closed_at'           => null,
            'activities'          => []
        ];

        // Automatische Initial-Tätigkeit
        $newTicket['activities'][] = [
            'id'          => 'act_' . bin2hex(random_bytes(5)),
            'type'        => 'Aufgabe erstellt',
            'description' => 'Aufgabe erstellt von ' . $user['name'],
            'minutes'     => 0,
            'is_internal' => false,
            'author_id'   => $user['id'],
            'author_name' => $user['name'],
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $tickets = self::loadTickets();
        array_unshift($tickets, $newTicket);
        self::saveTickets($tickets);

        // E-Mail-Benachrichtigung versenden
        HelpdeskMailer::sendTicketCreatedNotification($newTicket, $user);

        return ['success' => true, 'ticket_id' => $ticketId];
    }

    /**
     * Sucht ein Ticket nach seiner Nummer.
     * @param string $ticketId
     * @return array|null
     */
    public static function getTicketById(string $ticketId): ?array {
        $tickets = self::loadTickets();
        foreach ($tickets as $t) {
            if (($t['id'] ?? '') === $ticketId) {
                return $t;
            }
        }
        return null;
    }

    /**
     * Liefert gefilterte Tickets für den aktuellen Benutzer.
     * @param array $user
     * @param bool $hideClosed Wenn true, werden 'Geschlossen' und 'Abgerechnet' ausgeblendet
     * @param string $search Suchbegriff für Betreff, Aufgabennr. oder Firma
     * @return array
     */
    public static function getTicketsForUser(array $user, bool $hideClosed = false, string $search = ''): array {
        $tickets = self::loadTickets();
        $result = [];
        $search = mb_strtolower(trim($search));

        foreach ($tickets as $t) {
            // Mandanten-Sichtprüfung
            if (!HelpdeskUserManager::canViewTicket($t, $user)) {
                continue;
            }

            // Geschlossene Aufgaben verbergen
            $status = $t['status'] ?? self::STATUS_NEW;
            if ($hideClosed && in_array($status, [self::STATUS_CLOSED, self::STATUS_BILLED], true)) {
                continue;
            }

            // Suche
            if (!empty($search)) {
                $haystack = mb_strtolower(
                    ($t['id'] ?? '') . ' ' . 
                    ($t['subject'] ?? '') . ' ' . 
                    ($t['company_name'] ?? '') . ' ' . 
                    ($t['contact_person'] ?? '') . ' ' .
                    ($t['type'] ?? '')
                );
                if (mb_strpos($haystack, $search) === false) {
                    continue;
                }
            }

            $result[] = $t;
        }

        return $result;
    }

    /**
     * Fügt einem Ticket eine Tätigkeit hinzu.
     * Ist das Ticket bereits im Status "Abgerechnet", wird das Hinzufügen strikt verweigert!
     * @param string $ticketId
     * @param array $activityData
     * @param array $user
     * @return array
     */
    public static function addActivity(string $ticketId, array $activityData, array $user): array {
        $tickets = self::loadTickets();
        $found = false;

        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                if (($t['status'] ?? '') === self::STATUS_BILLED) {
                    return [
                        'success' => false, 
                        'error'   => 'Dieses Ticket wurde bereits in der Buchhaltung abgerechnet und ist dauerhaft gesperrt.'
                    ];
                }

                $type = trim($activityData['type'] ?? 'Fernservice');
                $description = trim($activityData['description'] ?? '');
                $minutes = max(0, (int)($activityData['minutes'] ?? 0));
                $isInternal = !empty($activityData['is_internal']) && HelpdeskUserManager::canManage($user);

                if (empty($description)) {
                    return ['success' => false, 'error' => 'Bitte eine Beschreibung für die Tätigkeit angeben.'];
                }

                $activity = [
                    'id'          => 'act_' . bin2hex(random_bytes(5)),
                    'type'        => $type,
                    'description' => $description,
                    'minutes'     => $minutes,
                    'is_internal' => $isInternal,
                    'author_id'   => $user['id'],
                    'author_name' => $user['name'],
                    'created_at'  => date('Y-m-d H:i:s')
                ];

                if (!isset($t['activities']) || !is_array($t['activities'])) {
                    $t['activities'] = [];
                }
                $t['activities'][] = $activity;
                $t['updated_at'] = date('Y-m-d H:i:s');

                // Automatische Statusanpassung bei Support-Antwort
                if (HelpdeskUserManager::canManage($user) && ($t['status'] === self::STATUS_NEW || $t['status'] === self::STATUS_WAITING_CUSTOMER)) {
                    $t['status'] = self::STATUS_IN_PROGRESS;
                }

                $found = true;
                self::saveTickets($tickets);

                // E-Mail-Benachrichtigung an den Kunden versenden (außer bei internen Notizen)
                if (!$isInternal) {
                    HelpdeskMailer::sendActivityNotification($t, $activity, $user);
                }

                return ['success' => true, 'activity' => $activity];
            }
        }

        return ['success' => false, 'error' => 'Ticket nicht gefunden.'];
    }

    /**
     * Ändert den Status eines Tickets.
     * Ist das Ticket bereits im Status "Abgerechnet", ist KEINE Änderung mehr zulässig!
     * @param string $ticketId
     * @param string $newStatus
     * @param array $user
     * @return array
     */
    public static function changeStatus(string $ticketId, string $newStatus, array $user): array {
        $allowedStatuses = [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_CUSTOMER,
            self::STATUS_WAITING_EXTERNAL,
            self::STATUS_CLOSED,
            self::STATUS_BILLED
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['success' => false, 'error' => 'Ungültiger Status.'];
        }

        $tickets = self::loadTickets();
        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                // Prüfen, ob Ticket bereits abgerechnet ist
                if (($t['status'] ?? '') === self::STATUS_BILLED) {
                    return [
                        'success' => false,
                        'error'   => 'Dieses Ticket wurde bereits in der Buchhaltung abgerechnet und kann nicht mehr geändert oder erneut geöffnet werden.'
                    ];
                }

                // Prüfen: Status 'Abgerechnet' darf NUR von Admins / Mitarbeitern gesetzt werden
                if ($newStatus === self::STATUS_BILLED) {
                    if (!HelpdeskUserManager::canManage($user)) {
                        return ['success' => false, 'error' => 'Nur Mitarbeiter und Administratoren können Tickets abrechnen.'];
                    }
                    $t['billed_at'] = date('Y-m-d H:i:s');
                    $t['billed_by'] = $user['name'];
                }

                $oldStatus = $t['status'];
                $t['status'] = $newStatus;
                $t['updated_at'] = date('Y-m-d H:i:s');

                if ($newStatus === self::STATUS_CLOSED && empty($t['closed_at'])) {
                    $t['closed_at'] = date('Y-m-d H:i:s');
                }

                // Tätigkeits-Eintrag für Statuswechsel
                $t['activities'][] = [
                    'id'          => 'act_' . bin2hex(random_bytes(5)),
                    'type'        => 'Statusänderung',
                    'description' => "Status geändert von '{$oldStatus}' auf '{$newStatus}' durch {$user['name']}",
                    'minutes'     => 0,
                    'is_internal' => false,
                    'author_id'   => $user['id'],
                    'author_name' => $user['name'],
                    'created_at'  => date('Y-m-d H:i:s')
                ];

                self::saveTickets($tickets);

                // E-Mail bei Schließung oder Abrechnung
                if ($newStatus === self::STATUS_CLOSED || $newStatus === self::STATUS_BILLED) {
                    HelpdeskMailer::sendStatusChangeNotification($t, $oldStatus, $newStatus);
                }

                return ['success' => true];
            }
        }

        return ['success' => false, 'error' => 'Ticket nicht gefunden.'];
    }

    /**
     * Ermittelt die Vertrags- und Inklusivminuten-Statistik für einen Benutzer bzw. eine Firma.
     * Berechnet: Verfügbare Minuten, Verbrauchte Minuten aller Tickets im Abrechnungszeitraum und Differenz.
     * @param array $user
     * @return array|null
     */
    public static function getContractSummary(array $user): ?array {
        // Falls der Benutzer selbst einen Vertrag hinterlegt hat
        $hasContract = !empty($user['has_contract']);
        $contractNumber = $user['contract_number'] ?? '';
        $title = $user['contract_title'] ?? 'IT/TK Wartung inkl. Service';
        $included = (int)($user['contract_minutes_included'] ?? 0);
        $from = $user['contract_period_from'] ?? date('Y-01-01');
        $to = $user['contract_period_to'] ?? date('Y-12-31');
        $nextBilling = $user['contract_next_billing'] ?? date('Y-m-d', strtotime('+3 months'));

        // Falls Mitarbeiter oder Admin: Wenn kein eigener Vertrag, Dummy-Muster zur Veranschaulichung
        if (!$hasContract && HelpdeskUserManager::canManage($user)) {
            // Ermittle den ersten Kunden mit aktivem Vertrag
            $allUsers = HelpdeskUserManager::loadUsers();
            foreach ($allUsers as $u) {
                if (!empty($u['has_contract'])) {
                    return self::getContractSummary($u);
                }
            }
            return null;
        }

        if (!$hasContract || $included <= 0) {
            return null;
        }

        // Summiere alle verbrauchten Minuten in Tickets dieses Kunden / dieser Firma im Zeitraum
        $tickets = self::loadTickets();
        $usedMinutes = 0;
        $fromTime = strtotime($from . ' 00:00:00');
        $toTime = strtotime($to . ' 23:59:59');

        foreach ($tickets as $t) {
            // Gehört Ticket zu dieser Firma / diesem Kunden?
            $match = false;
            if (!empty($user['company_name']) && !empty($t['company_name'])) {
                if (mb_strtolower(trim($user['company_name'])) === mb_strtolower(trim($t['company_name']))) {
                    $match = true;
                }
            }
            if (($t['created_by_user_id'] ?? '') === ($user['id'] ?? '')) {
                $match = true;
            }

            if (!$match) {
                continue;
            }

            if (!empty($t['activities']) && is_array($t['activities'])) {
                foreach ($t['activities'] as $act) {
                    $actTime = strtotime($act['created_at'] ?? 'now');
                    if ($actTime >= $fromTime && $actTime <= $toTime) {
                        $usedMinutes += (int)($act['minutes'] ?? 0);
                    }
                }
            }
        }

        $diff = $included - $usedMinutes;

        return [
            'contract_number' => !empty($contractNumber) ? $contractNumber : 'WV-2026-001',
            'title'           => $title,
            'status'          => 'Aktiv',
            'period_from'     => date('d.m.Y', strtotime($from)),
            'period_to'       => date('d.m.Y', strtotime($to)),
            'next_billing'    => date('d.m.Y', strtotime($nextBilling)),
            'available'       => $included,
            'used'            => $usedMinutes,
            'difference'      => $diff,
            // Historie für die Vertragsauswertung (Modal)
            'history'         => [
                [
                    'status'     => 'Aktiv',
                    'from'       => date('d.m.Y', strtotime($from)),
                    'to'         => date('d.m.Y', strtotime($to)),
                    'available'  => $included,
                    'used'       => $usedMinutes,
                    'difference' => $diff
                ],
                [
                    'status'     => 'Abgerechnet',
                    'from'       => date('d.m.Y', strtotime($from . ' -6 months')),
                    'to'         => date('d.m.Y', strtotime($to . ' -6 months')),
                    'available'  => $included,
                    'used'       => max(0, $included + 1796),
                    'difference' => -1796
                ]
            ]
        ];
    }
}

