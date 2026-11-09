<?php
/**
 * ============================================================================
 * Weave Helpdesk – HelpdeskUserManager
 * ============================================================================
 * Verwaltet Helpdesk-Benutzer (Mitarbeiter, Agenten, Kunden), Authentifizierung,
 * Session-State, Rollen und die automatische Erkennung des WeaveCMS-Admins.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class HelpdeskUserManager {

    public const ROLE_ADMIN    = 'admin';
    public const ROLE_AGENT    = 'agent';
    public const ROLE_CUSTOMER = 'customer';

    public const TYPE_COMPANY  = 'company';
    public const TYPE_PRIVATE  = 'private';

    /**
     * Pfad zur Benutzer-JSON-Datei.
     */
    public static function getUsersFilePath(): string {
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $token = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['weave_demo_token'] ?? 'demo');
            $sandboxDir = dirname(__DIR__, 2) . '/data/sandbox';
            if (!is_dir($sandboxDir)) {
                @mkdir($sandboxDir, 0755, true);
            }
            $sandboxFile = $sandboxDir . '/helpdesk_users_' . $token . '.json';
            if (!file_exists($sandboxFile)) {
                $baseFile = dirname(__DIR__, 2) . '/data/helpdesk_users.json';
                if (file_exists($baseFile)) {
                    @copy($baseFile, $sandboxFile);
                } else {
                    @file_put_contents($sandboxFile, '[]');
                }
            }
            return $sandboxFile;
        }
        return dirname(__DIR__, 2) . '/data/helpdesk_users.json';
    }

    /**
     * Lädt alle registrierten Helpdesk-Benutzer.
     * @return array
     */
    public static function loadUsers(): array {
        $file = self::getUsersFilePath();
        if (!file_exists($file)) {
            // Initialdatei anlegen, falls noch nicht existent
            self::saveUsers([]);
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert die Benutzerliste sicher in die JSON-Datei.
     * @param array $users
     * @return bool
     */
    public static function saveUsers(array $users): bool {
        $file = self::getUsersFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Ermittelt den aktuell angemeldeten Helpdesk-Benutzer.
     * Falls der WeaveCMS-Hauptadmin im Backend eingeloggt ist, wird er automatisch als Superadmin erkannt.
     * @return array|null
     */
    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 1. Priorität: Ist der WeaveCMS-Backend-Administrator eingeloggt?
        $cmsAdminLoggedIn = (!empty($_SESSION['admin_logged_in']) && class_exists('AuthManager') && AuthManager::isLoggedIn());
        if ($cmsAdminLoggedIn) {
            $cmsData = $GLOBALS['data'] ?? [];
            if (empty($cmsData) && function_exists('load_data')) {
                $cmsData = load_data(dirname(__DIR__, 2) . '/data.json');
            }
            $adminUser = $cmsData['settings']['admin_username'] ?? 'admin';
            $adminEmail = $cmsData['texts']['contact_email'] ?? 'admin@weavecms.local';
            $company = $cmsData['texts']['contact_name'] ?? 'Systemhaus / Dienstleister';

            return [
                'id'            => 'cms_superadmin',
                'username'      => $adminUser,
                'name'          => 'Administrator (' . $adminUser . ')',
                'email'         => $adminEmail,
                'role'          => self::ROLE_ADMIN,
                'customer_type' => self::TYPE_COMPANY,
                'company_name'  => $company,
                'address'       => ($cmsData['texts']['contact_street'] ?? '') . ', ' . ($cmsData['texts']['contact_city'] ?? ''),
                'phone'         => $cmsData['texts']['contact_phone'] ?? '',
                'is_cms_admin'  => true,
                'active'        => true
            ];
        }

        // 2. Normaler Helpdesk-Login über Sitzung
        $userId = $_SESSION['helpdesk_user_id'] ?? null;
        if (!empty($userId)) {
            $users = self::loadUsers();
            foreach ($users as $u) {
                if (($u['id'] ?? '') === $userId) {
                    if (!empty($u['active'])) {
                        return $u;
                    }
                    // Deaktivierter Benutzer -> Abmelden
                    self::logout();
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Authentifiziert einen Benutzer per E-Mail und Passwort.
     * @param string $email
     * @param string $password
     * @return array|null Benutzer-Array bei Erfolg, sonst null
     */
    public static function authenticate(string $email, string $password): ?array {
        $email = strtolower(trim($email));
        if (empty($email) || empty($password)) {
            return null;
        }

        $users = self::loadUsers();
        foreach ($users as $user) {
            if (strtolower($user['email'] ?? '') === $email) {
                if (!empty($user['active']) && password_verify($password, $user['password_hash'] ?? '')) {
                    return $user;
                }
                return null;
            }
        }

        return null;
    }

    /**
     * Startet die Benutzersitzung.
     * @param array $user
     * @return void
     */
    public static function login(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['helpdesk_user_id'] = $user['id'];
        $_SESSION['helpdesk_user_role'] = $user['role'];
        $_SESSION['helpdesk_user_name'] = $user['name'];
        $_SESSION['helpdesk_user_company'] = $user['company_name'] ?? '';
    }

    /**
     * Beendet die Benutzersitzung.
     * @return void
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset(
            $_SESSION['helpdesk_user_id'],
            $_SESSION['helpdesk_user_role'],
            $_SESSION['helpdesk_user_name'],
            $_SESSION['helpdesk_user_company']
        );
    }

    /**
     * Erstellt einen neuen Benutzer.
     * @param array $userData
     * @param string|null &$error
     * @return array|null
     */
    public static function createUser(array $userData, ?string &$error = null): ?array {
        $name = trim($userData['name'] ?? '');
        $email = strtolower(trim($userData['email'] ?? ''));
        $password = $userData['password'] ?? '';
        $role = trim($userData['role'] ?? self::ROLE_CUSTOMER);
        $customerType = trim($userData['customer_type'] ?? self::TYPE_COMPANY);
        $companyName = trim($userData['company_name'] ?? '');
        $address = trim($userData['address'] ?? '');
        $phone = trim($userData['phone'] ?? '');

        if (empty($name)) {
            $error = 'Bitte einen vollständigen Namen oder Ansprechpartner angeben.';
            return null;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben.';
            return null;
        }
        if (empty($password) || mb_strlen($password) < 6) {
            $error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
            return null;
        }

        $users = self::loadUsers();
        foreach ($users as $u) {
            if (strtolower($u['email'] ?? '') === $email) {
                $error = 'Diese E-Mail-Adresse ist bereits für einen Account registriert.';
                return null;
            }
        }

        $newUser = [
            'id'                        => 'hsu_' . bin2hex(random_bytes(6)),
            'name'                      => $name,
            'email'                     => $email,
            'password_hash'             => password_hash($password, PASSWORD_DEFAULT),
            'role'                      => in_array($role, [self::ROLE_ADMIN, self::ROLE_AGENT, self::ROLE_CUSTOMER], true) ? $role : self::ROLE_CUSTOMER,
            'customer_type'             => in_array($customerType, [self::TYPE_COMPANY, self::TYPE_PRIVATE], true) ? $customerType : self::TYPE_COMPANY,
            'company_name'              => $companyName,
            'address'                   => $address,
            'phone'                     => $phone,
            'has_contract'              => !empty($userData['has_contract']),
            'contract_number'           => trim($userData['contract_number'] ?? ''),
            'contract_title'            => trim($userData['contract_title'] ?? 'IT/TK Wartung inkl. Service'),
            'contract_minutes_included' => max(0, (int)($userData['contract_minutes_included'] ?? 0)),
            'contract_period_from'      => trim($userData['contract_period_from'] ?? date('Y-01-01')),
            'contract_period_to'        => trim($userData['contract_period_to'] ?? date('Y-12-31')),
            'contract_next_billing'     => trim($userData['contract_next_billing'] ?? date('Y-m-d', strtotime('+6 months'))),
            'active'                    => isset($userData['active']) ? (bool)$userData['active'] : true,
            'created_at'                => date('Y-m-d H:i:s'),
            'last_login'                => null
        ];

        $users[] = $newUser;
        if (!self::saveUsers($users)) {
            $error = 'Fehler beim Speichern der Benutzerdaten.';
            return null;
        }

        return $newUser;
    }

    /**
     * Aktualisiert die Benutzerdaten.
     * @param string $id
     * @param array $userData
     * @param string|null &$error
     * @return bool
     */
    public static function updateUser(string $id, array $userData, ?string &$error = null): bool {
        $users = self::loadUsers();
        $found = false;

        foreach ($users as &$u) {
            if (($u['id'] ?? '') === $id) {
                if (isset($userData['name'])) {
                    $u['name'] = trim($userData['name']);
                }
                if (!empty($userData['email'])) {
                    $newEmail = strtolower(trim($userData['email']));
                    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Ungültige E-Mail-Adresse.';
                        return false;
                    }
                    // Duplikatprüfung
                    foreach ($users as $other) {
                        if (($other['id'] ?? '') !== $id && strtolower($other['email'] ?? '') === $newEmail) {
                            $error = 'Diese E-Mail wird bereits von einem anderen Benutzer verwendet.';
                            return false;
                        }
                    }
                    $u['email'] = $newEmail;
                }
                if (!empty($userData['password'])) {
                    if (mb_strlen($userData['password']) < 6) {
                        $error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
                        return false;
                    }
                    $u['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
                }
                if (isset($userData['role']) && in_array($userData['role'], [self::ROLE_ADMIN, self::ROLE_AGENT, self::ROLE_CUSTOMER], true)) {
                    $u['role'] = $userData['role'];
                }
                if (isset($userData['customer_type'])) {
                    $u['customer_type'] = $userData['customer_type'];
                }
                if (isset($userData['company_name'])) {
                    $u['company_name'] = trim($userData['company_name']);
                }
                if (isset($userData['address'])) {
                    $u['address'] = trim($userData['address']);
                }
                if (isset($userData['phone'])) {
                    $u['phone'] = trim($userData['phone']);
                }
                if (isset($userData['has_contract'])) {
                    $u['has_contract'] = (bool)$userData['has_contract'];
                }
                if (isset($userData['contract_number'])) {
                    $u['contract_number'] = trim($userData['contract_number']);
                }
                if (isset($userData['contract_title'])) {
                    $u['contract_title'] = trim($userData['contract_title']);
                }
                if (isset($userData['contract_minutes_included'])) {
                    $u['contract_minutes_included'] = max(0, (int)$userData['contract_minutes_included']);
                }
                if (isset($userData['contract_period_from'])) {
                    $u['contract_period_from'] = trim($userData['contract_period_from']);
                }
                if (isset($userData['contract_period_to'])) {
                    $u['contract_period_to'] = trim($userData['contract_period_to']);
                }
                if (isset($userData['contract_next_billing'])) {
                    $u['contract_next_billing'] = trim($userData['contract_next_billing']);
                }
                if (isset($userData['active'])) {
                    $u['active'] = (bool)$userData['active'];
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            $error = 'Benutzer nicht gefunden.';
            return false;
        }

        return self::saveUsers($users);
    }

    /**
     * Löscht einen Benutzer anhand der ID.
     * @param string $id
     * @return bool
     */
    public static function deleteUser(string $id): bool {
        $users = self::loadUsers();
        $filtered = array_filter($users, fn($u) => ($u['id'] ?? '') !== $id);
        if (count($filtered) === count($users)) {
            return false;
        }
        return self::saveUsers($filtered);
    }

    /**
     * Sucht einen Benutzer nach ID.
     * @param string $id
     * @return array|null
     */
    public static function getUserById(string $id): ?array {
        $users = self::loadUsers();
        foreach ($users as $u) {
            if (($u['id'] ?? '') === $id) {
                return $u;
            }
        }
        return null;
    }

    /**
     * Prüft, ob der Benutzer administrative Rechte hat (Admin oder Mitarbeiter/Agent).
     * @param array|null $user
     * @return bool
     */
    public static function canManage(?array $user = null): bool {
        if (!$user) {
            $user = self::getCurrentUser();
        }
        if (!$user) {
            return false;
        }
        return in_array($user['role'] ?? '', [self::ROLE_ADMIN, self::ROLE_AGENT], true);
    }

    /**
     * Prüft, ob der Benutzer Superadmin ist.
     * @param array|null $user
     * @return bool
     */
    public static function isAdmin(?array $user = null): bool {
        if (!$user) {
            $user = self::getCurrentUser();
        }
        if (!$user) {
            return false;
        }
        return ($user['role'] ?? '') === self::ROLE_ADMIN;
    }

    /**
     * Prüft die Sichtbarkeit eines Tickets für den Benutzer:
     * - Admins und Mitarbeiter sehen alle Tickets.
     * - Firmenkunden sehen alle Tickets ihrer Firma.
     * - Privatkunden sehen nur ihre eigenen Tickets.
     * @param array $ticket
     * @param array|null $user
     * @return bool
     */
    public static function canViewTicket(array $ticket, ?array $user = null): bool {
        if (!$user) {
            $user = self::getCurrentUser();
        }
        if (!$user) {
            return false;
        }
        if (self::canManage($user)) {
            return true;
        }

        // Firmenkunden-Prüfung
        if (!empty($user['company_name']) && !empty($ticket['company_name'])) {
            if (mb_strtolower(trim($user['company_name'])) === mb_strtolower(trim($ticket['company_name']))) {
                return true;
            }
        }

        // Ersteller-Prüfung
        return (($ticket['created_by_user_id'] ?? '') === ($user['id'] ?? ''));
    }

    /**
     * Prüft, ob die Selbstregistrierung in den Einstellungen freigegeben ist.
     * @return bool
     */
    public static function isSelfRegistrationAllowed(): bool {
        $cfg = HelpdeskManager::getConfig();
        return !empty($cfg['allow_self_registration']);
    }
}
