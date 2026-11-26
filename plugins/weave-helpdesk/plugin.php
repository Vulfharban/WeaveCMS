<?php
/**
 * ============================================================================
 * Weave Helpdesk – Plugin Haupteinstiegspunkt (plugin.php)
 * ============================================================================
 * Registriert Hooks für Admin-Einstellungen, Benutzerverwaltung, Frontend-Routing,
 * Shortcodes und eigenständige Vollbild-Darstellung des Helpdesk-Portals.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/HelpdeskUserManager.php';
require_once __DIR__ . '/HelpdeskManager.php';
require_once __DIR__ . '/HelpdeskMailer.php';
require_once __DIR__ . '/HelpdeskAlertManager.php';

// Sitzung initialisieren
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (empty($_SESSION['csrf_token'])) {
    if (class_exists('AuthManager')) {
        $_SESSION['csrf_token'] = AuthManager::getCsrfToken();
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// -----------------------------------------------------------------------------
// 1. Backend-Aktionen (Einstellungen & Benutzerverwaltung)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        // Einstellungen speichern
        if ($action === 'helpdesk_save_settings') {
            $cfg = HelpdeskManager::getConfig();
            $cfg['service_provider_name']      = trim($_POST['service_provider_name'] ?? '');
            $cfg['service_provider_address']   = trim($_POST['service_provider_address'] ?? '');
            $cfg['service_provider_phone']     = trim($_POST['service_provider_phone'] ?? '');
            $cfg['service_provider_email']     = trim($_POST['service_provider_email'] ?? '');
            $cfg['show_in_main_nav']           = !empty($_POST['show_in_main_nav']);
            $cfg['allow_self_registration']    = !empty($_POST['allow_self_registration']);
            $cfg['enable_contracts']           = !empty($_POST['enable_contracts']);
            $cfg['enable_email_notifications'] = !empty($_POST['enable_email_notifications']);
            $cfg['enable_imap_fetcher']        = !empty($_POST['enable_imap_fetcher']);
            $cfg['imap_host']                  = trim($_POST['imap_host'] ?? '');
            $cfg['imap_port']                  = (int)($_POST['imap_port'] ?? 993);
            $cfg['imap_user']                  = trim($_POST['imap_user'] ?? '');
            if (!empty($_POST['imap_pass'])) {
                $cfg['imap_pass']              = $_POST['imap_pass'];
            }

            // Sofort-Alarmierung bei Priorität "Kritisch" (E-Mail, SMS, WhatsApp)
            $cfg['critical_alerts_enabled']  = !empty($_POST['critical_alerts_enabled']);
            $cfg['critical_email_enabled']   = !empty($_POST['critical_email_enabled']);
            $cfg['critical_emails']          = trim((string)($_POST['critical_emails'] ?? ''));

            $cfg['critical_sms_enabled']     = !empty($_POST['critical_sms_enabled']);
            $cfg['critical_sms_provider']    = trim((string)($_POST['critical_sms_provider'] ?? 'webhook'));
            $cfg['critical_sms_numbers']     = trim((string)($_POST['critical_sms_numbers'] ?? ''));
            $cfg['critical_sms_from']        = trim((string)($_POST['critical_sms_from'] ?? 'Helpdesk'));
            $cfg['critical_sms_api_url']     = trim((string)($_POST['critical_sms_api_url'] ?? ''));
            $cfg['critical_sms_account_sid'] = trim((string)($_POST['critical_sms_account_sid'] ?? ''));
            if (isset($_POST['critical_sms_api_key']) && $_POST['critical_sms_api_key'] !== '') {
                $cfg['critical_sms_api_key'] = trim((string)$_POST['critical_sms_api_key']);
            }

            $cfg['critical_wa_enabled']      = !empty($_POST['critical_wa_enabled']);
            $cfg['critical_wa_provider']     = trim((string)($_POST['critical_wa_provider'] ?? 'webhook'));
            $cfg['critical_wa_numbers']      = trim((string)($_POST['critical_wa_numbers'] ?? ''));
            $cfg['critical_wa_api_url']      = trim((string)($_POST['critical_wa_api_url'] ?? ''));
            $cfg['critical_wa_phone_id']     = trim((string)($_POST['critical_wa_phone_id'] ?? ''));
            $cfg['critical_wa_from']         = trim((string)($_POST['critical_wa_from'] ?? ''));
            if (isset($_POST['critical_wa_token']) && $_POST['critical_wa_token'] !== '') {
                $cfg['critical_wa_token']    = trim((string)$_POST['critical_wa_token']);
            }

            HelpdeskManager::saveConfig($cfg);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=settings&msg=settings_saved#plugin-settings-drawer-weave-helpdesk');
            exit;
        }

        // Test-Alarm für kritische Priorität auslösen
        if ($action === 'helpdesk_test_critical_alert') {
            $dummyTicket = [
                'id'               => date('y') . '999999',
                'subject'          => 'Test-Alarmierung (Priorität Kritisch)',
                'type'             => 'Störung',
                'priority'         => 'Kritisch',
                'company_name'     => 'WeaveCMS Testsystem',
                'contact_person'   => 'Systemadministrator',
                'alt_phone'        => '+49 000 000000',
                'created_by_email' => HelpdeskManager::getServiceProviderDetails()['email'] ?? '',
                'content'          => 'Dies ist ein Test der automatischen Sofort-Alarmierung (E-Mail / SMS / WhatsApp) bei kritischen Tickets.'
            ];
            $alertRes = HelpdeskAlertManager::sendCriticalAlert($dummyTicket, [], true);
            $detailErrors = [];
            foreach (($alertRes['channels'] ?? []) as $chName => $chInfo) {
                foreach (($chInfo['errors'] ?? []) as $err) {
                    $detailErrors[] = strtoupper($chName) . ': ' . $err;
                }
            }
            $_SESSION['hd_admin_alert_msg'] = [
                'ok'   => !empty($alertRes['triggered']) && empty($detailErrors),
                'text' => ($alertRes['summary'] ?? 'Keine Kanäle ausgelöst.')
                        . (!empty($detailErrors) ? ' | Fehler: ' . implode(' ; ', $detailErrors) : '')
            ];
            header('Location: ?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=settings#plugin-settings-drawer-weave-helpdesk');
            exit;
        }

        // Benutzer anlegen oder bearbeiten
        if ($action === 'helpdesk_save_user') {
            $userId = trim($_POST['user_id'] ?? '');
            $userData = [
                'name'                      => trim($_POST['name'] ?? ''),
                'email'                     => trim($_POST['email'] ?? ''),
                'password'                  => $_POST['password'] ?? '',
                'role'                      => trim($_POST['role'] ?? 'customer'),
                'customer_type'             => trim($_POST['customer_type'] ?? 'company'),
                'company_name'              => trim($_POST['company_name'] ?? ''),
                'address'                   => trim($_POST['address'] ?? ''),
                'phone'                     => trim($_POST['phone'] ?? ''),
                'has_contract'              => !empty($_POST['has_contract']),
                'contract_number'           => trim($_POST['contract_number'] ?? ''),
                'contract_title'            => trim($_POST['contract_title'] ?? ''),
                'contract_minutes_included' => (int)($_POST['contract_minutes_included'] ?? 0),
                'contract_period_from'      => trim($_POST['contract_period_from'] ?? ''),
                'contract_period_to'        => trim($_POST['contract_period_to'] ?? ''),
                'contract_next_billing'     => trim($_POST['contract_next_billing'] ?? ''),
                'active'                    => true
            ];

            $error = null;
            if (!empty($userId)) {
                $res = HelpdeskUserManager::updateUser($userId, $userData, $error);
            } else {
                $res = HelpdeskUserManager::createUser($userData, $error);
            }

            if ($res) {
                header('Location: ?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users&msg=user_saved#plugin-settings-drawer-weave-helpdesk');
            } else {
                $err = urlencode($error ?? 'Fehler beim Speichern');
                header("Location: ?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users&err={$err}#plugin-settings-drawer-weave-helpdesk");
            }
            exit;
        }

        // Benutzer löschen
        if ($action === 'helpdesk_delete_user') {
            $userId = trim($_POST['user_id'] ?? '');
            if (!empty($userId)) {
                HelpdeskUserManager::deleteUser($userId);
            }
            header('Location: ?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users&msg=user_deleted#plugin-settings-drawer-weave-helpdesk');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend-Aktionen (Login, Logout, Ticket erstellen, Tätigkeiten, Status)
// -----------------------------------------------------------------------------
if (!empty($_GET['page']) && $_GET['page'] === 'helpdesk' && !empty($_GET['action'])) {
    $action = $_GET['action'];

    // Logout
    if ($action === 'logout') {
        HelpdeskUserManager::logout();
        header('Location: ?page=helpdesk');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            
            // Login
            if ($action === 'login') {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $user = HelpdeskUserManager::authenticate($email, $password);
                if ($user) {
                    HelpdeskUserManager::login($user);
                    header('Location: ?page=helpdesk');
                    exit;
                }
                $_SESSION['hd_login_error'] = 'Ungültige E-Mail-Adresse oder falsches Passwort.';
                header('Location: ?page=helpdesk&mode=login');
                exit;
            }

            // Registrierung (Rolle und Vertragsdaten strikt auf Kunde beschränken)
            if ($action === 'register') {
                if (!HelpdeskUserManager::isSelfRegistrationAllowed()) {
                    $_SESSION['hd_login_error'] = 'Die Selbstregistrierung ist derzeit deaktiviert.';
                    header('Location: ?page=helpdesk&mode=login');
                    exit;
                }
                $error = null;
                $regData = [
                    'name'          => $_POST['name'] ?? '',
                    'email'         => $_POST['email'] ?? '',
                    'password'      => $_POST['password'] ?? '',
                    'role'          => HelpdeskUserManager::ROLE_CUSTOMER,
                    'customer_type' => $_POST['customer_type'] ?? HelpdeskUserManager::TYPE_COMPANY,
                    'company_name'  => $_POST['company_name'] ?? '',
                    'address'       => $_POST['address'] ?? '',
                    'phone'         => $_POST['phone'] ?? '',
                    'has_contract'  => false,
                ];
                $newUser = HelpdeskUserManager::createUser($regData, $error);
                if ($newUser) {
                    HelpdeskUserManager::login($newUser);
                    header('Location: ?page=helpdesk');
                    exit;
                }
                $_SESSION['hd_login_error'] = $error ?? 'Registrierung fehlgeschlagen.';
                header('Location: ?page=helpdesk&mode=register');
                exit;
            }

            // Ab hier: Nur für authentifizierte Benutzer
            $currentUser = HelpdeskUserManager::getCurrentUser();
            if (!$currentUser) {
                header('Location: ?page=helpdesk');
                exit;
            }

            // Neues Ticket erstellen
            if ($action === 'create_ticket') {
                $res = HelpdeskManager::createTicket($_POST, $currentUser);
                if ($res['success']) {
                    $_SESSION['hd_app_success'] = 'Aufgabe #' . $res['ticket_id'] . ' wurde erfolgreich erstellt.';
                    header('Location: ?page=helpdesk&view=detail&id=' . urlencode($res['ticket_id']));
                    exit;
                }
                $_SESSION['hd_app_error'] = $res['error'] ?? 'Fehler beim Erstellen der Aufgabe.';
                header('Location: ?page=helpdesk&view=create');
                exit;
            }

            // Tätigkeit hinzufügen
            if ($action === 'add_activity') {
                $ticketId = trim($_POST['ticket_id'] ?? '');
                $res = HelpdeskManager::addActivity($ticketId, $_POST, $currentUser);
                if (!empty($res['success'])) {
                    if (!empty($res['is_internal'])) {
                        $_SESSION['hd_app_success'] = 'Interne Notiz gespeichert (für Einsender nicht sichtbar, keine E-Mail versendet).';
                    } elseif (HelpdeskUserManager::canManage($currentUser)) {
                        $_SESSION['hd_app_success'] = !empty($res['notify_customer'])
                            ? 'Antwort gespeichert und Einsender per E-Mail informiert.'
                            : 'Antwort gespeichert (ohne E-Mail-Benachrichtigung an den Einsender).';
                    } else {
                        $_SESSION['hd_app_success'] = 'Deine Rückmeldung wurde hinzugefügt.';
                    }
                } else {
                    $_SESSION['hd_app_error'] = $res['error'] ?? 'Tätigkeit konnte nicht gespeichert werden.';
                }
                header('Location: ?page=helpdesk&view=detail&id=' . urlencode($ticketId));
                exit;
            }

            // Status ändern (z. B. In Bearbeitung, Warten, Geschlossen oder Abgerechnet)
            if ($action === 'change_status') {
                $ticketId = trim($_POST['ticket_id'] ?? '');
                $newStatus = trim($_POST['status'] ?? '');
                $notifyCustomer = !isset($_POST['notify_customer_present']) || !empty($_POST['notify_customer']);
                $res = HelpdeskManager::changeStatus($ticketId, $newStatus, $currentUser, $notifyCustomer);
                if (!empty($res['success'])) {
                    if (empty($res['unchanged'])) {
                        $_SESSION['hd_app_success'] = 'Status wurde auf „' . $newStatus . '“ geändert' . ($notifyCustomer ? ' (E-Mail-Info aktiv).' : ' (ohne E-Mail-Benachrichtigung).');
                    }
                } else {
                    $_SESSION['hd_app_error'] = $res['error'] ?? 'Status konnte nicht geändert werden.';
                }
                header('Location: ?page=helpdesk&view=detail&id=' . urlencode($ticketId));
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Eigenständige Vollbild-Route für das Helpdesk-Portal
// -----------------------------------------------------------------------------
if (!empty($_GET['page']) && $_GET['page'] === 'helpdesk') {
    // Rendert die komplette Webanwendung aus views/app.php
    require __DIR__ . '/views/app.php';
    exit;
}

// -----------------------------------------------------------------------------
// 4. WeaveCMS Backend Einstellungs-Tab Hook
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-helpdesk', function($data, $csrf_token) {
    $subtab = $_GET['subtab'] ?? 'settings';
    
    // Sub-Navigation im Backend
    echo '<div style="margin-bottom: 16px; display: flex; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">';
    echo '<a href="?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=settings#plugin-settings-drawer-weave-helpdesk" class="btn ' . ($subtab === 'settings' ? 'btn-primary' : 'btn-light') . '" style="padding: 6px 14px; text-decoration: none; font-size: 13px;">⚙️ Einstellungen & Firmierung</a>';
    echo '<a href="?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users#plugin-settings-drawer-weave-helpdesk" class="btn ' . ($subtab === 'users' ? 'btn-primary' : 'btn-light') . '" style="padding: 6px 14px; text-decoration: none; font-size: 13px;">👥 Benutzer & Verträge</a>';
    echo '</div>';

    if ($subtab === 'users') {
        require __DIR__ . '/admin/users.php';
    } else {
        require __DIR__ . '/admin/settings.php';
    }
});

// -----------------------------------------------------------------------------
// 5. Shortcode-Filter für Content-Integration
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (strpos($content, '[weave_helpdesk]') !== false || strpos($content, '[helpdesk]') !== false) {
        $provider = HelpdeskManager::getServiceProviderDetails();
        $portalUrl = '?page=helpdesk';
        $html = "
            <div style='background: #f4f8f9; border: 2px solid #257985; border-radius: 8px; padding: 24px; text-align: center; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #257985;'>🛡️ " . htmlspecialchars($provider['name']) . " Helpdesk</h3>
                <p>Greifen Sie auf Ihre Service-Aufgaben, Störungsmeldungen und Inklusivminuten zu.</p>
                <a href='{$portalUrl}' class='btn' style='background: #257985; color: #fff; padding: 10px 20px; font-weight: 700; text-decoration: none; border-radius: 4px; display: inline-block;'>
                    Zum Helpdesk-Portal
                </a>
            </div>
        ";
        $content = str_replace(['[weave_helpdesk]', '[helpdesk]'], $html, $content);
    }
    return $content;
});

// -----------------------------------------------------------------------------
// 6. Registrierung als optionale Sektion im WeaveCMS-Menü & Einzelseiten-Routing
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-helpdesk', $data)) {
        return $sections;
    }
    $cfg = HelpdeskManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    $provider = HelpdeskManager::getServiceProviderDetails();
    $sections[] = [
        'id'         => 'helpdesk',
        'type'       => 'helpdesk',
        'title'      => 'Service Helpdesk',
        'nav_label'  => 'Helpdesk',
        'url'        => '?page=helpdesk',
        'visible'    => true,
        'in_nav'     => $showInNav,
        'plugin'     => 'weave-helpdesk',
        'can_delete' => false,
        'placement'  => 'page',
        'icon'       => 'support_agent'
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-helpdesk', $data)) {
        return $sections;
    }
    $cfg = HelpdeskManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    $hasHelpdesk = false;
    foreach ($sections as &$s) {
        if (($s['id'] ?? '') === 'helpdesk') {
            $s['in_nav'] = $showInNav;
            $s['url'] = '?page=helpdesk';
            $hasHelpdesk = true;
            break;
        }
    }
    unset($s);
    if (!$hasHelpdesk && $showInNav) {
        $sections[] = [
            'id'        => 'helpdesk',
            'type'      => 'helpdesk',
            'title'     => 'Service Helpdesk',
            'nav_label' => 'Helpdesk',
            'url'       => '?page=helpdesk',
            'visible'   => true,
            'in_nav'    => true,
            'plugin'    => 'weave-helpdesk',
            'placement' => 'page',
            'icon'      => 'support_agent'
        ];
    }
    return $sections;
});

PluginManager::addAction('frontend_render_custom_section', function($sectionId, $data) {
    if ($sectionId === 'helpdesk') {
        $provider = HelpdeskManager::getServiceProviderDetails();
        echo "
            <div class='container' style='padding: 40px 15px; text-align: center;'>
                <h2 style='color: #257985;'>🛡️ " . htmlspecialchars($provider['name']) . " Helpdesk & Support</h2>
                <p style='max-width: 600px; margin: 0 auto 20px auto; color: #64748b;'>
                    Über unser Online-Ticketsystem können Sie Störungen melden, Aufgaben verfolgen und auf Ihre Serviceverträge zugreifen.
                </p>
                <a href='?page=helpdesk' class='btn' style='background: #257985; color: #fff; padding: 12px 28px; font-weight: 700; text-decoration: none; border-radius: 4px; display: inline-block;'>
                    🚀 Helpdesk-Portal öffnen
                </a>
            </div>
        ";
    }
});

