<?php
/**
 * ============================================================================
 * Weave Polls – Einstiegspunkt (plugins/weave-polls/plugin.php) – Phase 34
 * ============================================================================
 * Eigenständiges CMS-Umfrage-Plugin (mehrere parallele Umfragen in Seiten &
 * Sektionen). Vollständig getrennt von weave-forum.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/PollsManager.php';
require_once __DIR__ . '/views/poll_widget.php';

// -----------------------------------------------------------------------------
// 1. Admin-Aktionen (CRUD, Duplizieren, Status, Reset, CSV/JSON-Export)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    if (isset($_GET['action']) && in_array($_GET['action'], ['poll_export_csv', 'poll_export_json'], true)) {
        $csrf = (string)($_GET['csrf_token'] ?? '');
        $pollId = trim((string)($_GET['poll_id'] ?? ''));
        if ($pollId !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $safeName = PollsManager::sanitizeId($pollId) ?: 'poll';
            if ($_GET['action'] === 'poll_export_csv') {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="poll-' . $safeName . '.csv"');
                echo "\xEF\xBB\xBF" . PollsManager::exportResultsCsv($pollId);
            } else {
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="poll-' . $safeName . '.json"');
                echo PollsManager::exportResultsJson($pollId);
            }
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = (string)$_POST['action'];
        $pollAdminActions = ['poll_save', 'poll_delete', 'poll_duplicate', 'poll_set_status', 'poll_reset_votes', 'poll_save_config'];
        if (in_array($action, $pollAdminActions, true)) {
            $csrf = (string)($_POST['csrf_token'] ?? '');
            if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
                $redirect = '?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=list#tab-plugins';
                if ($action === 'poll_save') {
                    $saved = PollsManager::savePoll($_POST);
                    $_SESSION['weave_polls_admin_msg'] = [
                        'ok'   => true,
                        'text' => 'Umfrage „' . ($saved['title'] ?? '') . '“ gespeichert (ID: ' . ($saved['id'] ?? '') . ').',
                    ];
                } elseif ($action === 'poll_delete') {
                    $ok = PollsManager::deletePoll((string)($_POST['poll_id'] ?? ''));
                    $_SESSION['weave_polls_admin_msg'] = [
                        'ok'   => $ok,
                        'text' => $ok ? 'Umfrage gelöscht.' : 'Umfrage nicht gefunden.',
                    ];
                } elseif ($action === 'poll_duplicate') {
                    $copy = PollsManager::duplicatePoll((string)($_POST['poll_id'] ?? ''));
                    $_SESSION['weave_polls_admin_msg'] = [
                        'ok'   => $copy !== null,
                        'text' => $copy ? 'Umfrage dupliziert (Entwurf).' : 'Duplizieren fehlgeschlagen.',
                    ];
                } elseif ($action === 'poll_set_status') {
                    $ok = PollsManager::setPollStatus((string)($_POST['poll_id'] ?? ''), (string)($_POST['status'] ?? ''));
                    $_SESSION['weave_polls_admin_msg'] = [
                        'ok'   => $ok,
                        'text' => $ok ? 'Status aktualisiert.' : 'Ungültiger Status.',
                    ];
                } elseif ($action === 'poll_reset_votes') {
                    $ok = PollsManager::resetPollVotes((string)($_POST['poll_id'] ?? ''));
                    $_SESSION['weave_polls_admin_msg'] = [
                        'ok'   => $ok,
                        'text' => $ok ? 'Stimmen dieser Umfrage wurden zurückgesetzt.' : 'Fehler beim Zurücksetzen.',
                    ];
                } elseif ($action === 'poll_save_config') {
                    PollsManager::saveConfig($_POST);
                    $_SESSION['weave_polls_admin_msg'] = ['ok' => true, 'text' => 'Umfrage-Einstellungen gespeichert.'];
                    $redirect = '?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=settings#tab-plugins';
                }
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend Abstimmung (AJAX & Progressiver POST-Fallback)
// -----------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['weave_poll_action'] ?? '') === 'vote') {
    $isAjax = !empty($_POST['weave_poll_ajax']) || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    $pollId = trim((string)($_POST['poll_id'] ?? ''));
    $csrf = (string)($_POST['csrf_token'] ?? '');

    $safeRedirectTarget = static function (): string {
        $raw = str_replace(["\r", "\n", "\0"], '', (string)($_SERVER['REQUEST_URI'] ?? './'));
        if ($raw === '' || str_starts_with($raw, '//') || preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $raw)) {
            return './';
        }
        return $raw;
    };

    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $errMsg = 'Sicherheits-Token ungültig oder abgelaufen. Bitte Seite neu laden.';
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => $errMsg]);
            exit;
        }
        $_SESSION['weave_poll_flash'][$pollId] = ['ok' => false, 'text' => $errMsg];
        header('Location: ' . $safeRedirectTarget());
        exit;
    }

    if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
        $demoMsg = 'Demo-Modus: Abstimmung nur simuliert.';
        $poll = PollsManager::getPoll($pollId);
        $html = $poll ? weave_polls_render_single_poll($poll, ['has_voted' => true]) : '';
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => true, 'message' => $demoMsg, 'html' => $html]);
            exit;
        }
        $_SESSION['weave_poll_flash'][$pollId] = ['ok' => true, 'text' => $demoMsg];
        header('Location: ' . $safeRedirectTarget());
        exit;
    }

    $selected = (array)($_POST['option_ids'] ?? []);
    $res = PollsManager::castVote($pollId, $selected);
    $poll = PollsManager::getPoll($pollId);
    $_SESSION['weave_poll_flash'][$pollId] = ['ok' => $res['success'], 'text' => $res['message']];

    if ($isAjax) {
        if (!$res['success']) {
            $statusCode = !empty($res['http_status']) ? (int)$res['http_status'] : 400;
            http_response_code($statusCode);
        }
        $canSeeResults = $poll ? PollsManager::shouldShowResults($poll, PollsManager::hasVoted($pollId)) : false;
        $html = $poll ? weave_polls_render_single_poll($poll) : '';
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $res['success'],
            'message' => $res['message'],
            'results' => $canSeeResults ? $res['results'] : null,
            'html'    => $html,
        ]);
        exit;
    }

    header('Location: ' . $safeRedirectTarget());
    exit;
}

// -----------------------------------------------------------------------------
// 3. Admin-Einstellungs-Hook
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-polls', function ($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 4. Shortcodes: [poll id="..."], [polls], [polls ids="1,2,3"]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function ($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }
    if (strpos($content, '[poll') === false) {
        return $content;
    }

    $protected = [];
    $content = preg_replace_callback(
        '~<(code|pre|textarea|script)\b[^>]*>.*?</\1>|<[^>]+>~is',
        static function ($m) use (&$protected) {
            $key = '%%WP_PROTECTED_' . count($protected) . '%%';
            $protected[$key] = $m[0];
            return $key;
        },
        $content
    );

    // [poll id="..."]
    $content = preg_replace_callback(
        '/\[poll\s+id=["\']?([a-zA-Z0-9_-]+)["\']?\s*\]/i',
        static function ($m): string {
            $poll = PollsManager::getPoll($m[1]);
            if (!$poll || in_array((string)($poll['status'] ?? ''), ['draft', 'archived'], true)) {
                return '';
            }
            if (PollsManager::getEffectiveStatus($poll) === 'scheduled') {
                return '';
            }
            return weave_polls_render_single_poll($poll);
        },
        $content
    );

    // [polls ids="a,b,c"]
    $content = preg_replace_callback(
        '/\[polls\s+ids=["\']([^"\']+)["\']\s*\]/i',
        static function ($m): string {
            $ids = array_filter(array_map('trim', explode(',', $m[1])));
            $polls = PollsManager::getPublicPolls($ids);
            return weave_polls_render_poll_group($polls);
        },
        $content
    );

    // [polls]
    $content = preg_replace_callback(
        '/\[polls\s*\]/i',
        static function (): string {
            $polls = PollsManager::getPublicPolls();
            return weave_polls_render_poll_group($polls);
        },
        $content
    );

    if (!empty($protected)) {
        $content = strtr($content, $protected);
    }
    return $content;
});

// -----------------------------------------------------------------------------
// 5. Optionale Sektions-Integration (kein Menü-Eingriff ohne Opt-In)
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-polls', $data)) {
        return $sections;
    }
    $cfg = PollsManager::getConfig();
    $sections[] = [
        'id'         => 'polls',
        'type'       => 'polls',
        'title'      => $cfg['section_title'] ?? 'Aktuelle Umfragen',
        'nav_label'  => 'Umfragen',
        'url'        => '#polls',
        'visible'    => true,
        'in_nav'     => !empty($cfg['show_in_main_nav']),
        'plugin'     => 'weave-polls',
        'can_delete' => false,
        'placement'  => 'home',
        'icon'       => 'poll',
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-polls', $data)) {
        return $sections;
    }
    $cfg = PollsManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    foreach ($sections as &$s) {
        if (($s['id'] ?? '') === 'polls') {
            $s['in_nav'] = $showInNav;
            break;
        }
    }
    unset($s);
    return $sections;
});

PluginManager::addAction('frontend_render_custom_section', function ($sectionKey, $data) {
    if ($sectionKey !== 'polls') {
        return;
    }
    $cfg = PollsManager::getConfig();
    $polls = PollsManager::getPublicPolls();
    if (empty($polls)) {
        return;
    }
    echo '<section id="polls" style="padding: 3rem 0;">';
    echo '<h2 style="text-align:center; color:var(--accent, #2e6248); margin-bottom:0.75rem;">' . htmlspecialchars((string)($cfg['section_title'] ?? 'Aktuelle Umfragen')) . '</h2>';
    if (!empty($cfg['section_subtitle'])) {
        echo '<p style="text-align:center; color:var(--text-secondary); max-width:650px; margin:0 auto 2rem auto;">' . htmlspecialchars((string)$cfg['section_subtitle']) . '</p>';
    }
    echo weave_polls_render_poll_group($polls);
    echo '</section>';
});
