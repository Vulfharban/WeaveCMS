<?php
/**
 * ============================================================================
 * Weave Flohmarkt – Einstiegspunkt (plugins/weave-fleamarket/plugin.php)
 * ============================================================================
 * Phase 38: Kernarchitektur, Eigener Menüpunkt (Opt-In), Anzeigenkatalog
 * und Artikel-Detailansicht.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

require_once __DIR__ . '/FleamarketImagePipeline.php';
require_once __DIR__ . '/FleamarketManager.php';
require_once __DIR__ . '/views/fleamarket_catalog.php';
require_once __DIR__ . '/views/fleamarket_detail.php';
require_once __DIR__ . '/views/fleamarket_submit.php';

// -----------------------------------------------------------------------------
// 1. Frontend-Aktionen (Einreichung, Anfrage, Selbstverwaltung, Mitglieder)
// -----------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['weave_fm_action'])) {
    $fmAction = (string)$_POST['weave_fm_action'];
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $safeRedirect = static function (): string {
        $raw = str_replace(["\r", "\n", "\0"], '', (string)($_SERVER['REQUEST_URI'] ?? '?page=flohmarkt'));
        if ($raw === '' || str_starts_with($raw, '//') || preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $raw)) {
            return '?page=flohmarkt';
        }
        return $raw;
    };

    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $_SESSION['weave_fm_frontend_flash'] = ['ok' => false, 'text' => 'Sicherheits-Token ungültig.'];
        header('Location: ' . $safeRedirect());
        exit;
    }

    if ($fmAction === 'frontend_submit' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
        $cfg = FleamarketManager::getConfig();
        $maxImages = max(1, (int)($cfg['max_images_per_item'] ?? 6));
        $uploadedImgs = [];
        if (!empty($_FILES['photos']['tmp_name']) && is_array($_FILES['photos']['tmp_name'])) {
            foreach ($_FILES['photos']['tmp_name'] as $idx => $tmpPath) {
                if (count($uploadedImgs) >= $maxImages) {
                    break;
                }
                if (is_string($tmpPath) && $tmpPath !== '' && is_uploaded_file($tmpPath)) {
                    $imgRes = FleamarketImagePipeline::processUploadedFile($tmpPath);
                    if (!empty($imgRes['success'])) {
                        $uploadedImgs[] = [
                            'url'       => $imgRes['url'],
                            'thumb_url' => $imgRes['thumb_url'],
                            'focal_x'   => 50,
                            'focal_y'   => 50,
                        ];
                    }
                }
            }
        }
        $input = $_POST;
        if (!empty($uploadedImgs)) {
            $input['images'] = $uploadedImgs;
        }
        $res = FleamarketManager::submitFrontendItem($input, FleamarketManager::getCurrentMember());
        $_SESSION['weave_fm_frontend_flash'] = ['ok' => $res['success'], 'text' => $res['message']];
        header('Location: ?page=flohmarkt');
        exit;
    }

    if ($fmAction === 'send_inquiry') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        global $data;
        $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
        $res = FleamarketManager::sendItemInquiry($itemId, $_POST, $smtp);
        $_SESSION['weave_fm_inq_flash'][$itemId] = ['ok' => $res['success'], 'text' => $res['message']];
        header('Location: ' . $safeRedirect());
        exit;
    }

    if ($fmAction === 'self_service' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
        $tok = trim((string)($_POST['manage_token'] ?? ''));
        $op = trim((string)($_POST['fm_op'] ?? ''));
        $res = FleamarketManager::applySelfServiceAction($tok, $op);
        $_SESSION['weave_fm_frontend_flash'] = ['ok' => $res['success'], 'text' => $res['message']];
        header('Location: ' . (!empty($res['deleted']) ? '?page=flohmarkt' : ('?page=flohmarkt&manage=' . urlencode($tok))));
        exit;
    }

    if ($fmAction === 'member_item_action' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
        $member = FleamarketManager::getCurrentMember();
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $op = trim((string)($_POST['fm_op'] ?? ''));
        $allowedOps = ['active', 'reserved', 'sold', 'archived', 'delete'];
        $it = FleamarketManager::getItem($itemId);
        if ($member && $it && in_array($op, $allowedOps, true) && ($member['is_admin'] || ($it['seller_id'] ?? '') === $member['id'])) {
            if ($op === 'active' && ($it['status'] ?? '') === 'pending' && empty($member['is_admin'])) {
                $_SESSION['weave_fm_frontend_flash'] = ['ok' => false, 'text' => 'Diese Anzeige wartet noch auf Moderations-Freigabe.'];
            } elseif ($op === 'delete') {
                FleamarketManager::deleteItem($itemId);
                $_SESSION['weave_fm_frontend_flash'] = ['ok' => true, 'text' => 'Anzeige gelöscht.'];
            } else {
                FleamarketManager::setItemStatus($itemId, $op);
                $_SESSION['weave_fm_frontend_flash'] = ['ok' => true, 'text' => 'Anzeige aktualisiert.'];
            }
        }
        header('Location: ?page=flohmarkt&view=my_items');
        exit;
    }

    if ($fmAction === 'report_item') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $res = FleamarketManager::reportItem($itemId, $_POST);
        $_SESSION['weave_fm_report_flash'][$itemId] = ['ok' => $res['success'], 'text' => $res['message']];
        header('Location: ' . $safeRedirect());
        exit;
    }

    if ($fmAction === 'register_stand' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
        global $data;
        $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
        $res = FleamarketManager::registerStand($_POST, $smtp);
        $_SESSION['weave_fm_frontend_flash'] = ['ok' => $res['success'], 'text' => $res['message']];
        header('Location: ?page=flohmarkt&view=stands');
        exit;
    }
}

// -----------------------------------------------------------------------------
// 1b. Admin-Aktionen (Exporte, CRUD, Moderation, Stände & Bereinigung)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (in_array($action, ['fm_export_json', 'fm_export_csv'], true) && hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        if ($action === 'fm_export_json') {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="fleamarket-export-' . date('Y-m-d') . '.json"');
            echo FleamarketManager::exportJson();
            exit;
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="fleamarket-export-' . date('Y-m-d') . '.csv"');
        echo FleamarketManager::exportCsv();
        exit;
    }
}

if (!empty($_SESSION['admin_logged_in']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    $action = (string)($_POST['action'] ?? '');
    $fmActions = [
        'fm_save_item', 'fm_delete_item', 'fm_set_status', 'fm_save_category', 'fm_delete_category',
        'fm_save_config', 'fm_resolve_report', 'fm_unblock_seller', 'fm_stand_status',
        'fm_delete_stand', 'fm_run_maintenance', 'fm_import_json',
    ];
    if (in_array($action, $fmActions, true)) {
        $csrf = (string)($_POST['csrf_token'] ?? '');
        if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $sub = 'items';
            if ($action === 'fm_save_item') {
                $it = FleamarketManager::saveItem($_POST);
                $_SESSION['weave_fm_admin_msg'] = ['ok' => true, 'text' => 'Anzeige „' . $it['title'] . '“ (' . $it['item_number'] . ') gespeichert.'];
            } elseif ($action === 'fm_delete_item') {
                $ok = FleamarketManager::deleteItem(trim((string)($_POST['item_id'] ?? '')));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Anzeige gelöscht.' : 'Anzeige nicht gefunden.'];
            } elseif ($action === 'fm_set_status') {
                $ok = FleamarketManager::setItemStatus(trim((string)($_POST['item_id'] ?? '')), trim((string)($_POST['status'] ?? '')));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Status aktualisiert.' : 'Ungültiger Status.'];
            } elseif ($action === 'fm_save_category') {
                $c = FleamarketManager::saveCategory($_POST);
                $_SESSION['weave_fm_admin_msg'] = ['ok' => true, 'text' => 'Kategorie „' . $c['name'] . '“ gespeichert.'];
                $sub = 'categories';
            } elseif ($action === 'fm_delete_category') {
                FleamarketManager::deleteCategory(trim((string)($_POST['cat_id'] ?? '')));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => true, 'text' => 'Kategorie entfernt.'];
                $sub = 'categories';
            } elseif ($action === 'fm_save_config') {
                FleamarketManager::saveConfig($_POST);
                $_SESSION['weave_fm_admin_msg'] = ['ok' => true, 'text' => 'Flohmarkt-Einstellungen gespeichert.'];
                $sub = 'settings';
            } elseif ($action === 'fm_resolve_report') {
                $res = FleamarketManager::resolveReport(trim((string)($_POST['report_id'] ?? '')), trim((string)($_POST['resolution'] ?? '')), (string)($_SESSION['admin_username'] ?? 'admin'));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $res['success'], 'text' => $res['message']];
                $sub = 'inquiries';
            } elseif ($action === 'fm_unblock_seller') {
                $ok = FleamarketManager::unblockSeller(trim((string)($_POST['identifier'] ?? '')));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Anbieter entsperrt.' : 'Eintrag nicht gefunden.'];
                $sub = 'inquiries';
            } elseif ($action === 'fm_stand_status') {
                $ok = FleamarketManager::updateStandStatus(trim((string)($_POST['stand_id'] ?? '')), trim((string)($_POST['status'] ?? '')), $_POST['stand_number'] ?? null);
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Standstatus aktualisiert.' : 'Stand nicht gefunden.'];
                $sub = 'stands';
            } elseif ($action === 'fm_delete_stand') {
                $ok = FleamarketManager::deleteStand(trim((string)($_POST['stand_id'] ?? '')));
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $ok, 'text' => $ok ? 'Standanmeldung entfernt.' : 'Stand nicht gefunden.'];
                $sub = 'stands';
            } elseif ($action === 'fm_run_maintenance') {
                global $data;
                $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
                $stats = FleamarketManager::runLifecycleMaintenance(null, $smtp);
                $_SESSION['weave_fm_admin_msg'] = [
                    'ok'   => true,
                    'text' => "Wartung abgeschlossen: {$stats['expired_items']} abgelaufen, {$stats['reminders_sent']} Erinnerungen, {$stats['purged_items']} Gast-Anzeigen / {$stats['purged_inquiries']} Anfragen / {$stats['removed_images']} verwaiste Bilder bereinigt.",
                ];
                $sub = 'dashboard';
            } elseif ($action === 'fm_import_json') {
                $rawJson = '';
                if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
                    $rawJson = (string)@file_get_contents($_FILES['import_file']['tmp_name']);
                }
                $res = FleamarketManager::importJson($rawJson);
                $_SESSION['weave_fm_admin_msg'] = ['ok' => $res['success'], 'text' => $res['message']];
                $sub = 'dashboard';
            }
            header('Location: ?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=' . urlencode($sub) . '#tab-plugins');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Admin Settings Hook
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-fleamarket', function ($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 3. Shortcodes: [fleamarket], [weave_fleamarket ...], [fleamarket_item id="..."]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function ($content, $data) {
    if (empty($content) || !is_string($content) || (strpos($content, '[fleamarket') === false && strpos($content, '[weave_fleamarket') === false)) {
        return $content;
    }

    $protected = [];
    $content = preg_replace_callback(
        '~<(code|pre|textarea|script)\b[^>]*>.*?</\1>|<[^>]+>~is',
        static function ($m) use (&$protected) {
            $key = '%%WFM_PROTECTED_' . count($protected) . '%%';
            $protected[$key] = $m[0];
            return $key;
        },
        $content
    );

    // [fleamarket_item id="..."]
    $content = preg_replace_callback(
        '/\[fleamarket_item\s+id=["\']?([a-zA-Z0-9_-]+)["\']?\s*\]/i',
        static function ($m): string {
            $it = FleamarketManager::getItem($m[1]);
            if (!$it || in_array((string)($it['status'] ?? ''), ['draft', 'archived', 'pending'], true)) {
                return '';
            }
            return weave_fleamarket_render_detail($it);
        },
        $content
    );

    // [fleamarket ...] / [weave_fleamarket ...]
    $content = preg_replace_callback(
        '/\[(?:weave_)?fleamarket(?:\s+([^\]]+))?\]/i',
        static function ($m): string {
            $opts = [];
            $attrStr = trim((string)($m[1] ?? ''));
            if ($attrStr !== '') {
                preg_match_all('/([a-zA-Z_]+)=["\']([^"\']*)["\']/', $attrStr, $pairs, PREG_SET_ORDER);
                foreach ($pairs as $p) {
                    $opts[strtolower($p[1])] = $p[2];
                }
            }
            return weave_fleamarket_render_catalog($opts);
        },
        $content
    );

    if (!empty($protected)) {
        $content = strtr($content, $protected);
    }
    return $content;
});

// -----------------------------------------------------------------------------
// 4. Sektions-Integration & Eigener Menüpunkt (Opt-In gemäß Menü-Governance)
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-fleamarket', $data)) {
        return $sections;
    }
    $cfg = FleamarketManager::getConfig();
    $placement = (string)($cfg['placement'] ?? 'page');
    $sections[] = [
        'id'         => 'flohmarkt',
        'type'       => 'flohmarkt',
        'title'      => $cfg['section_title'] ?? 'Flohmarkt & Kleinanzeigen',
        'nav_label'  => $cfg['nav_label'] ?? 'Flohmarkt',
        'url'        => $placement === 'home' ? '#flohmarkt' : '?page=flohmarkt',
        'visible'    => true,
        'in_nav'     => !empty($cfg['show_in_main_nav']),
        'plugin'     => 'weave-fleamarket',
        'can_delete' => false,
        'placement'  => $placement,
        'icon'       => $cfg['nav_icon'] ?? 'local_offer',
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function ($sections, $data) {
    if (!PluginManager::isPluginActive('weave-fleamarket', $data)) {
        return $sections;
    }
    $cfg = FleamarketManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    foreach ($sections as &$s) {
        if (($s['id'] ?? '') === 'flohmarkt') {
            $s['in_nav'] = $showInNav;
            $s['nav_label'] = $cfg['nav_label'] ?? 'Flohmarkt';
            $s['icon'] = $cfg['nav_icon'] ?? 'local_offer';
            $s['placement'] = $cfg['placement'] ?? 'page';
            break;
        }
    }
    unset($s);
    return $sections;
});

PluginManager::addAction('frontend_render_custom_section', function ($sectionKey, $data) {
    if ($sectionKey !== 'flohmarkt') {
        return;
    }
    FleamarketManager::maybeRunLazyLifecycle();
    $cfg = FleamarketManager::getConfig();

    $flash = $_SESSION['weave_fm_frontend_flash'] ?? null;
    unset($_SESSION['weave_fm_frontend_flash']);
    if ($flash && !empty($flash['text'])) {
        echo '<div style="max-width:960px; margin:1rem auto; padding:11px 16px; border-radius:10px; font-weight:600; background:'
            . (!empty($flash['ok']) ? '#dcfce7; color:#166534' : '#fef2f2; color:#991b1b') . ';">'
            . htmlspecialchars((string)$flash['text'], ENT_QUOTES, 'UTF-8') . '</div>';
    }

    // 1. Double-Opt-In E-Mail-Bestätigung für Gast-Anzeigen
    if (!empty($_GET['confirm_ad'])) {
        $confRes = FleamarketManager::confirmGuestItem(trim((string)$_GET['confirm_ad']));
        echo '<div class="card" style="max-width:720px; margin:2rem auto; padding:1.5rem; text-align:center;">'
            . '<p style="font-weight:700; color:' . (!empty($confRes['success']) ? '#166534' : '#991b1b') . ';">'
            . htmlspecialchars((string)$confRes['message'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a href="?page=flohmarkt" class="btn btn-primary" style="text-decoration:none;">Zum Flohmarkt-Katalog</a></div>';
        return;
    }

    // 1b. 1-Klick-Verlängerung aus Ablauf-Erinnerung
    if (!empty($_GET['extend_ad'])) {
        $extRes = FleamarketManager::extendItemByToken(trim((string)$_GET['extend_ad']));
        echo '<div class="card" style="max-width:720px; margin:2rem auto; padding:1.5rem; text-align:center;">'
            . '<p style="font-weight:700; color:' . (!empty($extRes['success']) ? '#166534' : '#991b1b') . ';">'
            . htmlspecialchars((string)$extRes['message'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a href="?page=flohmarkt" class="btn btn-primary" style="text-decoration:none;">Zum Flohmarkt-Katalog</a></div>';
        return;
    }

    // 2. Kryptografisch signierter Self-Service-Verwaltungslink
    if (!empty($_GET['manage'])) {
        $tok = trim((string)$_GET['manage']);
        $managed = FleamarketManager::findItemByManageToken($tok);
        if ($managed) {
            echo weave_fleamarket_render_manage_view($managed, $tok);
        } else {
            echo '<div class="card" style="max-width:680px; margin:2rem auto; padding:1.5rem; text-align:center;">Der Verwaltungs-Link ist ungültig oder abgelaufen.</div>';
        }
        return;
    }

    // 3. Frontend-Einreichung / Meine Anzeigen / Vor-Ort-Stände
    $view = trim((string)($_GET['view'] ?? ''));
    if ($view === 'submit') {
        echo weave_fleamarket_render_submit_form(FleamarketManager::getCurrentMember());
        return;
    }
    if ($view === 'stands') {
        echo weave_fleamarket_render_stands_view();
        return;
    }
    if ($view === 'my_items') {
        $member = FleamarketManager::getCurrentMember();
        if ($member) {
            echo weave_fleamarket_render_my_items($member);
            return;
        }
    }

    // 4. Artikel-Detailansicht & druckbares QR-Preisschild (?page=flohmarkt&item=slug)
    $slug = trim((string)($_GET['item'] ?? ''));
    if ($slug !== '') {
        $item = FleamarketManager::getItem($slug);
        if ($item && !in_array((string)($item['status'] ?? ''), ['draft', 'archived', 'pending'], true)) {
            if (!empty($_GET['print_tag'])) {
                $baseUrl = class_exists('SecurityManager') ? rtrim(SecurityManager::getBaseUrl(), '/') : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $itemUrl = $baseUrl . '/?page=flohmarkt&item=' . urlencode((string)$item['slug']);
                echo FleamarketManager::renderQrPriceTagHtml($item, $itemUrl) . weave_fleamarket_detail_script_once();
                return;
            }
            FleamarketManager::incrementViews((string)$item['id']);
            echo weave_fleamarket_render_detail(FleamarketManager::getItem((string)$item['id']) ?? $item);
            return;
        }
    }
    echo '<section id="flohmarkt" style="padding: 2.5rem 0;">';
    echo '<h2 style="text-align:center; color:var(--accent, #2e6248); margin-bottom:0.5rem;">' . htmlspecialchars((string)($cfg['section_title'] ?? 'Flohmarkt & Kleinanzeigen')) . '</h2>';
    if (!empty($cfg['section_subtitle'])) {
        echo '<p style="text-align:center; color:var(--text-secondary); max-width:660px; margin:0 auto 1.75rem auto;">' . htmlspecialchars((string)$cfg['section_subtitle']) . '</p>';
    }
    echo weave_fleamarket_render_catalog();
    echo '</section>';
});
