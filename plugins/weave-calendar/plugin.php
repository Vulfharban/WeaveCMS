<?php
/**
 * ============================================================================
 * Weave Calendar – Einstiegspunkt (plugins/weave-calendar/plugin.php) – Phase 36
 * ============================================================================
 * Hybrides Kalender- & Terminverwaltungs-Plugin (Frontend-Einbettung +
 * eigenständige Backend-Teamansicht, Sync & Erinnerungen).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

require_once __DIR__ . '/CalendarCrypto.php';
require_once __DIR__ . '/CalendarIcs.php';
require_once __DIR__ . '/CalendarManager.php';
require_once __DIR__ . '/CalendarSync.php';
require_once __DIR__ . '/CalendarReminderService.php';
require_once __DIR__ . '/views/calendar_frontend.php';

// -----------------------------------------------------------------------------
// 1. Öffentliche .ics Feeds (Abo / Einzeltermin-Download)
// -----------------------------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    if (!empty($_GET['weave_cal_ics'])) {
        $cfg = CalendarManager::getConfig();
        $provided = trim((string)($_GET['token'] ?? ''));
        $expected = (string)($cfg['feed_token'] ?? '');
        if ($expected === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            exit('Ungültiges Kalender-Abo-Token.');
        }
        $events = CalendarManager::expandEventsForRange(
            date('Y-m-d 00:00:00', strtotime('-1 month')),
            date('Y-m-d 23:59:59', strtotime('+12 months')),
            ['public_only' => true]
        );
        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: attachment; filename="weavecms-kalender.ics"');
        echo CalendarIcs::exportEventsToIcs($events, (string)($cfg['section_title'] ?? 'WeaveCMS Kalender'));
        exit;
    }

    if (!empty($_GET['weave_cal_ics_event'])) {
        $ev = CalendarManager::getEvent(trim((string)$_GET['weave_cal_ics_event']));
        $isAdmin = !empty($_SESSION['admin_logged_in']);
        if ($ev && ($isAdmin || ($ev['visibility'] ?? 'public') === 'public')) {
            if (!$isAdmin) {
                $ev = CalendarManager::sanitizeEventForPublic($ev);
            }
            header('Content-Type: text/calendar; charset=UTF-8');
            header('Content-Disposition: attachment; filename="termin-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $ev['id']) . '.ics"');
            echo CalendarIcs::exportEventsToIcs([$ev], (string)$ev['title']);
            exit;
        }
        http_response_code(404);
        exit('Termin nicht gefunden.');
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend Terminanfrage (POST mit CSRF)
// -----------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['weave_cal_action'] ?? '') === 'booking_request') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $safeRedirect = static function (): string {
        $raw = str_replace(["\r", "\n", "\0"], '', (string)($_SERVER['REQUEST_URI'] ?? './'));
        if ($raw === '' || str_starts_with($raw, '//') || preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $raw)) {
            return './';
        }
        return $raw;
    };

    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $_SESSION['weave_cal_frontend_flash'] = ['ok' => false, 'text' => 'Sicherheits-Token ungültig. Bitte Seite neu laden.'];
    } else {
        $cfg = CalendarManager::getConfig();
        if (empty($cfg['allow_public_booking'])) {
            $_SESSION['weave_cal_frontend_flash'] = ['ok' => false, 'text' => 'Öffentliche Terminanfragen sind derzeit deaktiviert.'];
        } else {
            $res = CalendarManager::createBookingRequest($_POST);
            $_SESSION['weave_cal_frontend_flash'] = ['ok' => $res['success'], 'text' => $res['message']];
        }
    }
    header('Location: ' . $safeRedirect());
    exit;
}

// -----------------------------------------------------------------------------
// 3. Admin-Aktionen (CRUD, Sync, Erinnerungen, Einstellungen)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    $action = (string)($_POST['action'] ?? '');
    $calAdminActions = [
        'cal_save_event', 'cal_delete_event', 'cal_save_staff', 'cal_delete_staff',
        'cal_save_category', 'cal_delete_category', 'cal_booking_status',
        'cal_save_sync_provider', 'cal_trigger_sync', 'cal_import_ics',
        'cal_save_config', 'cal_run_reminders',
    ];
    if (in_array($action, $calAdminActions, true)) {
        $csrf = (string)($_POST['csrf_token'] ?? '');
        if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $sub = 'team';
            if ($action === 'cal_save_event') {
                $res = CalendarManager::saveEvent($_POST);
                $_SESSION['weave_cal_conflicts'] = $res['conflicts'] ?? [];
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Termin „' . ($res['event']['title'] ?? '') . '“ gespeichert.'];
            } elseif ($action === 'cal_delete_event') {
                $ok = CalendarManager::deleteEvent(trim((string)($_POST['event_id'] ?? '')));
                $_SESSION['weave_cal_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Termin gelöscht.' : 'Termin nicht gefunden.'];
            } elseif ($action === 'cal_save_staff') {
                $st = CalendarManager::saveStaff($_POST);
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Mitarbeiter „' . $st['name'] . '“ gespeichert.'];
                $sub = 'staff';
            } elseif ($action === 'cal_delete_staff') {
                CalendarManager::deleteStaff(trim((string)($_POST['staff_id'] ?? '')));
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Mitarbeiter entfernt.'];
                $sub = 'staff';
            } elseif ($action === 'cal_save_category') {
                $c = CalendarManager::saveCategory($_POST);
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Kategorie „' . $c['name'] . '“ gespeichert.'];
                $sub = 'staff';
            } elseif ($action === 'cal_delete_category') {
                CalendarManager::deleteCategory(trim((string)($_POST['cat_id'] ?? '')));
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Kategorie entfernt.'];
                $sub = 'staff';
            } elseif ($action === 'cal_booking_status') {
                $res = CalendarManager::updateBookingStatus(trim((string)($_POST['booking_id'] ?? '')), trim((string)($_POST['status'] ?? '')));
                $_SESSION['weave_cal_admin_msg'] = ['ok' => $res !== null, 'text' => $res ? 'Terminanfrage aktualisiert.' : 'Anfrage nicht gefunden.'];
                $sub = 'bookings';
            } elseif ($action === 'cal_save_sync_provider') {
                $ok = CalendarSync::saveProviderConfig(trim((string)($_POST['provider'] ?? '')), $_POST);
                $_SESSION['weave_cal_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Sync-Zugangsdaten verschlüsselt gespeichert.' : 'Ungültiger Anbieter.'];
                $sub = 'sync';
            } elseif ($action === 'cal_trigger_sync') {
                $prov = trim((string)($_POST['provider'] ?? ''));
                if ($prov === 'google') {
                    $sr = CalendarSync::syncGoogle();
                } elseif ($prov === 'microsoft') {
                    $sr = CalendarSync::syncMicrosoft();
                } elseif ($prov === 'caldav') {
                    $sr = CalendarSync::syncCalDav();
                } else {
                    $sr = ['success' => false, 'message' => 'Unbekannter Anbieter.'];
                }
                $_SESSION['weave_cal_admin_msg'] = ['ok' => $sr['success'], 'text' => $sr['message']];
                $sub = 'sync';
            } elseif ($action === 'cal_import_ics') {
                $raw = (string)($_POST['ics_raw'] ?? '');
                $ir = CalendarSync::importIcsContent($raw, 'ics_import');
                $_SESSION['weave_cal_admin_msg'] = ['ok' => $ir['success'], 'text' => "Import: {$ir['imported']} neu, {$ir['updated']} aktualisiert."];
                $sub = 'sync';
            } elseif ($action === 'cal_save_config') {
                CalendarManager::saveConfig($_POST);
                $_SESSION['weave_cal_admin_msg'] = ['ok' => true, 'text' => 'Kalender-Einstellungen gespeichert.'];
                $sub = 'settings';
            } elseif ($action === 'cal_run_reminders') {
                global $data;
                $smtp = $data['settings']['smtp'] ?? null;
                $rr = CalendarReminderService::processDueReminders(null, null, is_array($smtp) ? $smtp : null);
                $_SESSION['weave_cal_admin_msg'] = ['ok' => empty($rr['errors']), 'text' => "Erinnerungen geprüft ({$rr['checked']}), gesendet: {$rr['sent']}, übersprungen: {$rr['skipped']}."];
                $sub = 'settings';
            }

            header('Location: ?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=' . urlencode($sub) . '#tab-plugins');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 4. Gedrosselter Pseudo-Cron Fallback (max. alle 5 Minuten)
// -----------------------------------------------------------------------------
if (php_sapi_name() !== 'cli' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    $calCfg = CalendarManager::getConfig();
    if (!empty($calCfg['auto_pseudo_cron'])) {
        $stampFile = dirname(__DIR__, 2) . '/data/calendar_cron.stamp';
        $lastRun = file_exists($stampFile) ? (int)@filemtime($stampFile) : 0;
        if (time() - $lastRun >= 300) {
            @touch($stampFile);
            global $data;
            $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
            CalendarReminderService::processDueReminders(null, null, $smtp);
        }
    }
}

// -----------------------------------------------------------------------------
// 5. Admin-Settings Hook
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-calendar', function ($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 6. Shortcode [calendar ...] mit <code>/<pre>/<textarea>/<script>-Schutz
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function ($content, $data) {
    if (empty($content) || !is_string($content) || strpos($content, '[calendar') === false) {
        return $content;
    }

    $protected = [];
    $content = preg_replace_callback(
        '~<(code|pre|textarea|script)\b[^>]*>.*?</\1>|<[^>]+>~is',
        static function ($m) use (&$protected) {
            $key = '%%WCAL_PROTECTED_' . count($protected) . '%%';
            $protected[$key] = $m[0];
            return $key;
        },
        $content
    );

    $content = preg_replace_callback(
        '/\[calendar(?:\s+([^\]]+))?\]/i',
        static function ($m): string {
            $opts = [];
            $attrStr = trim((string)($m[1] ?? ''));
            if ($attrStr !== '') {
                preg_match_all('/([a-zA-Z_]+)=["\']([^"\']*)["\']/', $attrStr, $pairs, PREG_SET_ORDER);
                foreach ($pairs as $p) {
                    $opts[strtolower($p[1])] = $p[2];
                }
            }
            return weave_calendar_render_frontend($opts);
        },
        $content
    );

    if (!empty($protected)) {
        $content = strtr($content, $protected);
    }
    return $content;
});

// -----------------------------------------------------------------------------
// 7. Sektions-Integration (Menü-Governance: in_nav standardmäßig false)
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-calendar', $data)) {
        return $sections;
    }
    $cfg = CalendarManager::getConfig();
    $sections[] = [
        'id'         => 'calendar',
        'type'       => 'calendar',
        'title'      => $cfg['section_title'] ?? 'Kalender & Termine',
        'nav_label'  => 'Kalender',
        'url'        => '?page=calendar',
        'visible'    => true,
        'in_nav'     => !empty($cfg['show_in_main_nav']),
        'plugin'     => 'weave-calendar',
        'can_delete' => false,
        'placement'  => 'page',
        'icon'       => 'calendar_month',
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-calendar', $data)) {
        return $sections;
    }
    $cfg = CalendarManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    foreach ($sections as &$s) {
        if (($s['id'] ?? '') === 'calendar') {
            $s['in_nav'] = $showInNav;
            break;
        }
    }
    unset($s);
    return $sections;
});

PluginManager::addAction('frontend_render_custom_section', function ($sectionKey, $data) {
    if ($sectionKey !== 'calendar') {
        return;
    }
    $cfg = CalendarManager::getConfig();
    echo '<section id="calendar" style="padding: 2.5rem 0;">';
    echo '<h2 style="text-align:center; color:var(--accent, #0f766e); margin-bottom:0.5rem;">' . htmlspecialchars((string)($cfg['section_title'] ?? 'Kalender & Termine')) . '</h2>';
    if (!empty($cfg['section_subtitle'])) {
        echo '<p style="text-align:center; color:var(--text-secondary); max-width:660px; margin:0 auto 1.75rem auto;">' . htmlspecialchars((string)$cfg['section_subtitle']) . '</p>';
    }
    echo weave_calendar_render_frontend();
    echo '</section>';
});
