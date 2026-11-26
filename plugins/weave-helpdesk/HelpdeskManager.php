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
require_once __DIR__ . '/HelpdeskAlertManager.php';

class HelpdeskManager {

    public const STATUS_NEW               = 'Neu';
    public const STATUS_IN_PROGRESS       = 'In Bearbeitung';
    public const STATUS_SCHEDULED         = 'Termin gesetzt';
    public const STATUS_WAITING_CUSTOMER  = 'Warten auf Rückmeldung';
    public const STATUS_WAITING_EXTERNAL  = 'Warten auf ext. Dienstleister';
    public const STATUS_CLOSED            = 'Geschlossen';
    public const STATUS_BILLED            = 'Abgerechnet';

    /**
     * Vergleicht deutsche Texte alphabetisch (inkl. Umlauten).
     */
    protected static function compareGerman(string $a, string $b): int {
        $norm = static function (string $s): string {
            return strtr(mb_strtolower(trim($s)), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        };
        return strcmp($norm($a), $norm($b));
    }

    /**
     * Liefert alle zulässigen Ticket-Status mit Beschreibung und UI-Klasse.
     * @param bool $alphabetical Wenn true, werden die Status alphabetisch sortiert
     * @return array
     */
    public static function getStatuses(bool $alphabetical = false): array {
        $statuses = [
            self::STATUS_NEW => [
                'label' => self::STATUS_NEW,
                'class' => 'status-new',
                'desc'  => 'Neu eingegangenes Ticket, noch nicht in Bearbeitung'
            ],
            self::STATUS_IN_PROGRESS => [
                'label' => self::STATUS_IN_PROGRESS,
                'class' => 'status-progress',
                'desc'  => 'Ticket wird aktuell bearbeitet'
            ],
            self::STATUS_SCHEDULED => [
                'label' => self::STATUS_SCHEDULED,
                'class' => 'status-scheduled',
                'desc'  => 'Ein Termin zur Bearbeitung oder ein Vor-Ort-Einsatz wurde vereinbart'
            ],
            self::STATUS_WAITING_CUSTOMER => [
                'label' => self::STATUS_WAITING_CUSTOMER,
                'class' => 'status-waiting',
                'desc'  => 'Rückfrage an den Einsender gestellt'
            ],
            self::STATUS_WAITING_EXTERNAL => [
                'label' => self::STATUS_WAITING_EXTERNAL,
                'class' => 'status-external',
                'desc'  => 'Abhängig von externem Dienstleister / Hersteller'
            ],
            self::STATUS_CLOSED => [
                'label' => self::STATUS_CLOSED,
                'class' => 'status-closed',
                'desc'  => 'Erledigt / abgeschlossen (kann bei Bedarf wieder geöffnet werden)'
            ],
            self::STATUS_BILLED => [
                'label' => self::STATUS_BILLED,
                'class' => 'status-billed',
                'desc'  => 'Final abgerechnet und dauerhaft gesperrt (nur Mitarbeiter/Admins)'
            ],
        ];

        if ($alphabetical) {
            uasort($statuses, static fn($a, $b) => self::compareGerman((string)$a['label'], (string)$b['label']));
        }
        return $statuses;
    }

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
            'ticket_types'               => ['Bestellung', 'Installation', 'Projekt', 'Reklamation', 'Rückruf', 'Serviceanfrage', 'Sonstiges', 'Störung', 'Wartung'],
            'priorities'                 => ['Niedrig', 'Normal', 'Hoch', 'Kritisch'],
            'critical_alerts_enabled'    => false,
            'critical_email_enabled'     => true,
            'critical_emails'            => '',
            'critical_sms_enabled'       => false,
            'critical_sms_provider'      => 'webhook',
            'critical_sms_numbers'       => '',
            'critical_sms_api_url'       => '',
            'critical_sms_account_sid'   => '',
            'critical_sms_api_key'       => '',
            'critical_sms_from'          => 'Helpdesk',
            'critical_wa_enabled'        => false,
            'critical_wa_provider'       => 'webhook',
            'critical_wa_numbers'        => '',
            'critical_wa_api_url'        => '',
            'critical_wa_phone_id'       => '',
            'critical_wa_from'           => '',
            'critical_wa_token'          => ''
        ];

        if (class_exists('StorageManager')) {
            $data = StorageManager::loadPluginData('helpdesk_config', null, $file);
            if (!is_array($data)) {
                self::saveConfig($defaultConfig);
                return $defaultConfig;
            }
            return array_merge($defaultConfig, $data);
        }

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
        if (class_exists('StorageManager')) {
            return StorageManager::savePluginData('helpdesk_config', $config, $file);
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Liefert alle verfügbaren Aufgabentypen (inkl. Bestellung, Rückruf, Reklamation) alphabetisch sortiert.
     * @return array
     */
    public static function getTicketTypes(): array {
        $defaults = ['Bestellung', 'Installation', 'Projekt', 'Reklamation', 'Rückruf', 'Serviceanfrage', 'Sonstiges', 'Störung', 'Wartung'];
        $cfg = self::getConfig();
        $configured = is_array($cfg['ticket_types'] ?? null) ? $cfg['ticket_types'] : [];
        $merged = array_values(array_unique(array_merge($defaults, $configured)));
        usort($merged, [self::class, 'compareGerman']);
        return $merged;
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
     * Lädt alle Tickets aus der aktiven Speicher-Engine (Datenbank oder JSON-Datei).
     * @return array
     */
    public static function loadTickets(): array {
        $file = self::getTicketsFilePath();
        if (class_exists('StorageManager')) {
            $data = StorageManager::loadPluginData('helpdesk_tickets', null, $file);
            if (!is_array($data)) {
                self::saveTickets([]);
                return [];
            }
            return $data;
        }
        if (!file_exists($file)) {
            self::saveTickets([]);
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert Tickets sicher in der aktiven Speicher-Engine (Datenbank oder JSON-Datei).
     * @param array $tickets
     * @return bool
     */
    public static function saveTickets(array $tickets): bool {
        $file = self::getTicketsFilePath();
        if (class_exists('StorageManager')) {
            return StorageManager::savePluginData('helpdesk_tickets', array_values($tickets), $file);
        }
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

        // Sofort-Alarmierung bei Priorität "Kritisch" (dringende E-Mail, SMS, WhatsApp)
        if (mb_strtolower($priority) === 'kritisch' && class_exists('HelpdeskAlertManager')) {
            $alertRes = HelpdeskAlertManager::sendCriticalAlert($newTicket, $user, false);
            if (!empty($alertRes['triggered']) && !empty($alertRes['summary'])) {
                $tickets = self::loadTickets();
                foreach ($tickets as &$tk) {
                    if (($tk['id'] ?? '') === $ticketId) {
                        $tk['activities'][] = [
                            'id'              => 'act_' . bin2hex(random_bytes(5)),
                            'type'            => 'Kritisch-Alarm',
                            'description'     => $alertRes['summary'],
                            'minutes'         => 0,
                            'is_internal'     => true,
                            'notify_customer' => false,
                            'author_id'       => 'system',
                            'author_name'     => 'System (Sofort-Alarm)',
                            'created_at'      => date('Y-m-d H:i:s')
                        ];
                        break;
                    }
                }
                unset($tk);
                self::saveTickets($tickets);
            }
        }

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
     * Liefert gefilterte und sortierte Tickets für den aktuellen Benutzer.
     * Standardmäßig werden geschlossene/abgerechnete Tickets ausgeblendet ($hideClosed = true)
     * und Tickets chronologisch nach Eingangsreihenfolge ($sortOrder = 'asc') sortiert.
     *
     * @param array $user
     * @param bool $hideClosed Wenn true (und $onlyClosed false), werden 'Geschlossen' und 'Abgerechnet' ausgeblendet
     * @param string $search Suchbegriff für Betreff, Aufgabennr., Firma oder Kontaktperson
     * @param bool $onlyClosed Wenn true, werden ausschließlich 'Geschlossen' und 'Abgerechnet' zurückgegeben
     * @param string $sortOrder 'asc' = Reihenfolge des Eingangs (älteste zuerst), 'desc' = neueste zuerst
     * @return array
     */
    public static function getTicketsForUser(array $user, bool $hideClosed = true, string $search = '', bool $onlyClosed = false, string $sortOrder = 'asc'): array {
        $tickets = self::loadTickets();
        $result = [];
        $search = mb_strtolower(trim($search));

        foreach ($tickets as $t) {
            // Mandanten-Sichtprüfung
            if (!HelpdeskUserManager::canViewTicket($t, $user)) {
                continue;
            }

            $status = $t['status'] ?? self::STATUS_NEW;
            $isDone = in_array($status, [self::STATUS_CLOSED, self::STATUS_BILLED], true);

            if ($onlyClosed) {
                if (!$isDone) {
                    continue;
                }
            } elseif ($hideClosed && $isDone) {
                continue;
            }

            // Suche
            if (!empty($search)) {
                $haystack = mb_strtolower(
                    ($t['id'] ?? '') . ' ' .
                    ($t['subject'] ?? '') . ' ' .
                    ($t['company_name'] ?? '') . ' ' .
                    ($t['contact_person'] ?? '') . ' ' .
                    ($t['created_by_name'] ?? '') . ' ' .
                    ($t['type'] ?? '')
                );
                if (mb_strpos($haystack, $search) === false) {
                    continue;
                }
            }

            $result[] = $t;
        }

        // Nach Eingangszeitpunkt (created_at) sortieren
        $desc = ($sortOrder === 'desc');
        usort($result, static function ($a, $b) use ($desc) {
            $ta = strtotime((string)($a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['created_at'] ?? '')) ?: 0;
            if ($ta === $tb) {
                $cmp = strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
                return $desc ? -$cmp : $cmp;
            }
            return $desc ? ($tb <=> $ta) : ($ta <=> $tb);
        });

        return $result;
    }

    /**
     * Gruppiert eine Ticketliste nach Firma + Einsender ('company_user'), nach Einsender ('user')
     * oder belässt sie ungruppiert ('none'), unter Beibehaltung der inneren Eingangs-Sortierung.
     *
     * @param array $tickets
     * @param string $mode 'company_user' | 'user' | 'none'
     * @return array Liste von Gruppen: [['id' => ..., 'label' => ..., 'company' => ..., 'user' => ..., 'tickets' => [...]]]
     */
    public static function groupTickets(array $tickets, string $mode = 'company_user'): array {
        if ($mode === 'none') {
            return [[
                'id'      => 'all',
                'label'   => 'Alle Aufgaben',
                'company' => '',
                'user'    => '',
                'tickets' => array_values($tickets)
            ]];
        }

        $groups = [];
        foreach ($tickets as $t) {
            $company = trim((string)($t['company_name'] ?? ''));
            $submitter = trim((string)($t['created_by_name'] ?? ''));
            if ($submitter === '') {
                $submitter = trim((string)($t['contact_person'] ?? 'Unbekannt'));
            }

            if ($mode === 'user') {
                $key = mb_strtolower($submitter);
                $label = $submitter;
            } else {
                $companyLabel = $company !== '' ? $company : 'Privatkunde / Ohne Firma';
                $key = mb_strtolower($companyLabel) . '||' . mb_strtolower($submitter);
                $label = $companyLabel . ' — ' . $submitter;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'id'      => 'grp_' . substr(md5($key), 0, 10),
                    'label'   => $label,
                    'company' => $company !== '' ? $company : 'Privatkunde / Ohne Firma',
                    'user'    => $submitter,
                    'tickets' => []
                ];
            }
            $groups[$key]['tickets'][] = $t;
        }

        uasort($groups, static function ($a, $b) {
            return strnatcasecmp((string)$a['label'], (string)$b['label']);
        });

        return array_values($groups);
    }

    /**
     * Fügt einem Ticket eine Tätigkeit oder Antwort hinzu.
     * Ist das Ticket bereits im Status "Abgerechnet", wird das Hinzufügen strikt verweigert!
     * Firmeninterne Helpdesk-User können je Antwort wählen, ob der Einsender per E-Mail informiert wird.
     * @param string $ticketId
     * @param array $activityData
     * @param array $user
     * @return array
     */
    public static function addActivity(string $ticketId, array $activityData, array $user): array {
        $tickets = self::loadTickets();
        $isStaff = HelpdeskUserManager::canManage($user);

        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                if (!HelpdeskUserManager::canViewTicket($t, $user)) {
                    return [
                        'success' => false,
                        'error'   => 'Keine Zugriffsberechtigung für dieses Ticket.'
                    ];
                }

                if (($t['status'] ?? '') === self::STATUS_BILLED) {
                    return [
                        'success' => false,
                        'error'   => 'Dieses Ticket wurde bereits in der Buchhaltung abgerechnet und ist dauerhaft gesperrt.'
                    ];
                }

                $type = trim((string)($activityData['type'] ?? ($isStaff ? 'Fernservice' : 'Kundenrückmeldung')));
                $description = trim((string)($activityData['description'] ?? ''));
                $minutes = $isStaff ? max(0, (int)($activityData['minutes'] ?? 0)) : 0;
                $isInternal = !empty($activityData['is_internal']) && $isStaff;

                if (empty($description)) {
                    return ['success' => false, 'error' => 'Bitte eine Beschreibung für die Tätigkeit angeben.'];
                }

                // Auswahloption für firmeninterne Helpdesk-User: Einsender per E-Mail informieren (Standard: Ja, bei internen Notizen: Nein)
                if ($isInternal) {
                    $notifyCustomer = false;
                } elseif ($isStaff) {
                    $notifyCustomer = !array_key_exists('notify_customer_present', $activityData) || !empty($activityData['notify_customer']);
                } else {
                    $notifyCustomer = false;
                }

                $activity = [
                    'id'              => 'act_' . bin2hex(random_bytes(5)),
                    'type'            => $type,
                    'description'     => $description,
                    'minutes'         => $minutes,
                    'is_internal'     => $isInternal,
                    'notify_customer' => $notifyCustomer,
                    'author_id'       => $user['id'],
                    'author_name'     => $user['name'],
                    'author_role'     => $user['role'] ?? HelpdeskUserManager::ROLE_CUSTOMER,
                    'created_at'      => date('Y-m-d H:i:s')
                ];

                if (!isset($t['activities']) || !is_array($t['activities'])) {
                    $t['activities'] = [];
                }
                $t['activities'][] = $activity;
                $t['updated_at'] = date('Y-m-d H:i:s');

                // Automatische Statusanpassung bei Support-Antwort bzw. Kundenantwort
                if ($isStaff && !$isInternal && in_array($t['status'] ?? '', [self::STATUS_NEW, self::STATUS_WAITING_CUSTOMER], true)) {
                    $t['status'] = self::STATUS_IN_PROGRESS;
                } elseif (!$isStaff && ($t['status'] ?? '') === self::STATUS_WAITING_CUSTOMER) {
                    $t['status'] = self::STATUS_IN_PROGRESS;
                }

                self::saveTickets($tickets);

                $mailResult = null;
                if ($isStaff) {
                    if (!$isInternal && $notifyCustomer) {
                        $mailResult = HelpdeskMailer::sendActivityNotification($t, $activity, $user, 'customer');
                    }
                } else {
                    // Kunde hat geantwortet -> Dienstleister / Support informieren
                    $mailResult = HelpdeskMailer::sendActivityNotification($t, $activity, $user, 'provider');
                }

                return [
                    'success'         => true,
                    'activity'        => $activity,
                    'notify_customer' => $notifyCustomer,
                    'is_internal'     => $isInternal,
                    'mail_result'     => $mailResult
                ];
            }
        }
        unset($t);

        return ['success' => false, 'error' => 'Ticket nicht gefunden.'];
    }

    /**
     * Ändert den Status eines Tickets.
     * Ist das Ticket bereits im Status "Abgerechnet", ist KEINE Änderung mehr zulässig!
     * @param string $ticketId
     * @param string $newStatus
     * @param array $user
     * @param bool $notifyCustomer
     * @return array
     */
    public static function changeStatus(string $ticketId, string $newStatus, array $user, bool $notifyCustomer = true): array {
        $allowedStatuses = [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_SCHEDULED,
            self::STATUS_WAITING_CUSTOMER,
            self::STATUS_WAITING_EXTERNAL,
            self::STATUS_CLOSED,
            self::STATUS_BILLED
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['success' => false, 'error' => 'Ungültiger Status.'];
        }

        $isStaff = HelpdeskUserManager::canManage($user);
        $tickets = self::loadTickets();
        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') === $ticketId) {
                if (!HelpdeskUserManager::canViewTicket($t, $user)) {
                    return ['success' => false, 'error' => 'Keine Zugriffsberechtigung für dieses Ticket.'];
                }

                // Prüfen, ob Ticket bereits abgerechnet ist
                if (($t['status'] ?? '') === self::STATUS_BILLED) {
                    return [
                        'success' => false,
                        'error'   => 'Dieses Ticket wurde bereits in der Buchhaltung abgerechnet und kann nicht mehr geändert oder erneut geöffnet werden.'
                    ];
                }

                // Kunden dürfen Tickets nur schließen oder selbst wieder in Bearbeitung setzen
                if (!$isStaff && !in_array($newStatus, [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS], true)) {
                    return ['success' => false, 'error' => 'Nur Mitarbeiter und Administratoren können diesen Status setzen.'];
                }

                // Prüfen: Status 'Abgerechnet' darf NUR von Admins / Mitarbeitern gesetzt werden
                if ($newStatus === self::STATUS_BILLED) {
                    if (!$isStaff) {
                        return ['success' => false, 'error' => 'Nur Mitarbeiter und Administratoren können Tickets abrechnen.'];
                    }
                    $t['billed_at'] = date('Y-m-d H:i:s');
                    $t['billed_by'] = $user['name'];
                }

                $oldStatus = $t['status'] ?? self::STATUS_NEW;
                if ($oldStatus === $newStatus) {
                    return ['success' => true, 'unchanged' => true];
                }

                $t['status'] = $newStatus;
                $t['updated_at'] = date('Y-m-d H:i:s');

                if ($newStatus === self::STATUS_CLOSED && empty($t['closed_at'])) {
                    $t['closed_at'] = date('Y-m-d H:i:s');
                } elseif ($newStatus !== self::STATUS_CLOSED && $newStatus !== self::STATUS_BILLED) {
                    $t['closed_at'] = null;
                }

                // Tätigkeits-Eintrag für Statuswechsel
                $t['activities'][] = [
                    'id'              => 'act_' . bin2hex(random_bytes(5)),
                    'type'            => 'Statusänderung',
                    'description'     => "Status geändert von '{$oldStatus}' auf '{$newStatus}' durch {$user['name']}",
                    'minutes'         => 0,
                    'is_internal'     => false,
                    'notify_customer' => $notifyCustomer,
                    'author_id'       => $user['id'],
                    'author_name'     => $user['name'],
                    'created_at'      => date('Y-m-d H:i:s')
                ];

                self::saveTickets($tickets);

                // E-Mail an Einsender senden, sofern gewünscht (oder bei Schließen/Abrechnen)
                if ($notifyCustomer) {
                    HelpdeskMailer::sendStatusChangeNotification($t, $oldStatus, $newStatus);
                }

                return ['success' => true];
            }
        }
        unset($t);

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
                ]
            ]
        ];
    }
}

