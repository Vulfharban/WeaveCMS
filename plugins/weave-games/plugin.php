<?php
/**
 * ============================================================================
 * Weave Games – Plugin Einstiegspunkt (plugin.php)
 * ============================================================================
 * Verwaltet:
 * - Admin-Aktionen für Game-Server (Start/Stop, Windows/Linux, SSH)
 * - Client-Update-Rollout & Patch-Verteilung (Desktop Games / Voxel RPG)
 * - Spieler-Registrierung & Session-Management
 * - Eigenständige Frontend-Portal-Route (?page=games)
 * - Modulare Sektions- & Shortcode-Integration
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/GameUserManager.php';
require_once __DIR__ . '/GameRolloutManager.php';
require_once __DIR__ . '/GameServerManager.php';
require_once __DIR__ . '/GameConfigManager.php';

// -----------------------------------------------------------------------------
// 1. Admin-Aktionen (Server, Releases, Spieler, Konfiguration)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {

    // Server-Ping-Aktion via GET
    if (isset($_GET['action']) && $_GET['action'] === 'ping_all') {
        GameServerManager::refreshAllServerStatuses(true);
        header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {

            // A: Server anlegen / bearbeiten
            if ($action === 'game_server_save') {
                GameServerManager::saveServer($_POST);
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&msg=server_saved#tab-plugins');
                exit;
            }

            // B: Server löschen
            if ($action === 'game_server_delete') {
                $id = trim($_POST['server_id'] ?? '');
                if (!empty($id)) {
                    GameServerManager::deleteServer($id);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&msg=server_deleted#tab-plugins');
                exit;
            }

            // C: Server starten
            if ($action === 'game_server_start') {
                $id = trim($_POST['server_id'] ?? '');
                $log = '';
                $ok = GameServerManager::startServer($id, $log);
                $msg = $ok ? 'server_started' : 'server_error';
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&msg=' . $msg . '#tab-plugins');
                exit;
            }

            // D: Server stoppen
            if ($action === 'game_server_stop') {
                $id = trim($_POST['server_id'] ?? '');
                $log = '';
                $ok = GameServerManager::stopServer($id, $log);
                $msg = $ok ? 'server_stopped' : 'server_error';
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&msg=' . $msg . '#tab-plugins');
                exit;
            }

            // E: Release hinzufügen
            if ($action === 'game_release_save') {
                GameRolloutManager::addRelease($_POST);
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout&msg=release_added#tab-plugins');
                exit;
            }

            // F: Release löschen
            if ($action === 'game_release_delete') {
                $id = trim($_POST['release_id'] ?? '');
                if (!empty($id)) {
                    GameRolloutManager::deleteRelease($id);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout&msg=release_deleted#tab-plugins');
                exit;
            }

            // G: Spieler löschen
            if ($action === 'game_player_delete') {
                $id = trim($_POST['player_id'] ?? '');
                if (!empty($id)) {
                    GameUserManager::deletePlayer($id);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=players&msg=player_deleted#tab-plugins');
                exit;
            }

            // E-2: Spiel / Anwendung im Katalog anlegen oder aktualisieren (inkl. ZIP- & Cover-Upload)
            if ($action === 'game_save') {
                $slugInput = trim((string)($_POST['slug'] ?? $_POST['title'] ?? ''));
                $safeSlug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($slugInput));
                $safeSlug = trim((string)preg_replace('/-+/', '-', $safeSlug), '-_');

                $gamePayload = [
                    'slug'                => $safeSlug,
                    'title'               => trim((string)($_POST['title'] ?? '')),
                    'category'            => trim((string)($_POST['category'] ?? 'browser')),
                    'runtime_type'        => trim((string)($_POST['runtime_type'] ?? 'html5_js')),
                    'tech_tags'           => (string)($_POST['tech_tags'] ?? ''),
                    'genre'               => trim((string)($_POST['genre'] ?? '')),
                    'description'         => trim((string)($_POST['description'] ?? '')),
                    'system_requirements' => trim((string)($_POST['system_requirements'] ?? '')),
                    'icon'                => trim((string)($_POST['icon'] ?? '🎮')),
                    'banner'              => trim((string)($_POST['banner'] ?? '')),
                    'embed_url'           => trim((string)($_POST['embed_url'] ?? '')),
                    'embed_mode'          => trim((string)($_POST['embed_mode'] ?? 'modal')),
                    'aspect_ratio'        => trim((string)($_POST['aspect_ratio'] ?? '16:9')),
                    'enable_coop_coep'    => !empty($_POST['enable_coop_coep']),
                    'show_on_home'        => !empty($_POST['show_on_home']),
                    'featured'            => !empty($_POST['featured']),
                    'sort_order'          => (int)($_POST['sort_order'] ?? 50),
                    'active'              => !empty($_POST['active']),
                ];

                $uploadNotes = [];
                if (!empty($_FILES['game_cover']) && ($_FILES['game_cover']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $covErr = null;
                    $covPath = GameRolloutManager::uploadCoverImage($safeSlug, $_FILES['game_cover'], $covErr);
                    if ($covPath) {
                        $gamePayload['banner'] = $covPath;
                    } elseif ($covErr) {
                        $uploadNotes[] = $covErr;
                    }
                }
                if (!empty($_FILES['game_zip']) && ($_FILES['game_zip']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $zipErr = null;
                    $zipPath = GameRolloutManager::uploadGameZip($safeSlug, $_FILES['game_zip'], $zipErr);
                    if ($zipPath) {
                        $gamePayload['embed_url'] = $zipPath;
                    }
                    if ($zipErr) {
                        $uploadNotes[] = $zipErr;
                    }
                }

                $ok = GameRolloutManager::saveGame($gamePayload);
                $_SESSION['wg_admin_msg'] = [
                    'ok'   => $ok && empty($uploadNotes),
                    'text' => $ok
                        ? ('Spiel/Anwendung „' . ($gamePayload['title'] ?: $safeSlug) . '“ gespeichert.' . (!empty($uploadNotes) ? ' Hinweis: ' . implode(' | ', $uploadNotes) : ''))
                        : 'Spiel konnte nicht gespeichert werden (Slug/Titel prüfen).'
                ];
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins');
                exit;
            }

            // E-3: Spiel aus Katalog löschen
            if ($action === 'game_delete') {
                $slug = trim((string)($_POST['slug'] ?? ''));
                if ($slug !== '') {
                    GameRolloutManager::deleteGame($slug);
                    $_SESSION['wg_admin_msg'] = ['ok' => true, 'text' => 'Eintrag wurde aus dem Spiele-Katalog entfernt.'];
                }
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins');
                exit;
            }

            // H: Konfiguration speichern
            if ($action === 'game_save_config') {
                $cfg = GameConfigManager::getConfig();
                $cfg['show_in_main_nav'] = !empty($_POST['show_in_main_nav']);
                $cfg['allow_self_registration'] = !empty($_POST['allow_self_registration']);
                $cfg['enable_public_server_status'] = !empty($_POST['enable_public_server_status']);
                if (isset($_POST['section_title'])) {
                    $cfg['section_title'] = trim((string)$_POST['section_title']);
                }
                if (isset($_POST['section_subtitle'])) {
                    $cfg['section_subtitle'] = trim((string)$_POST['section_subtitle']);
                }
                $homeLayout = trim((string)($_POST['home_layout'] ?? 'ambient_blur'));
                if (in_array($homeLayout, ['ambient_blur', 'cards', 'showcase', 'list'], true)) {
                    $cfg['home_layout'] = $homeLayout;
                }
                $homeCols = (int)($_POST['home_columns'] ?? 3);
                $cfg['home_columns'] = in_array($homeCols, [2, 3, 4], true) ? $homeCols : 3;
                $cfg['home_max_items'] = max(0, (int)($_POST['home_max_items'] ?? 6));
                $cfg['home_show_filters'] = !empty($_POST['home_show_filters']);
                $homeSort = trim((string)($_POST['home_sort'] ?? 'manual'));
                $cfg['home_sort'] = in_array($homeSort, ['manual', 'newest', 'alpha'], true) ? $homeSort : 'manual';
                $cfg['home_play_modal'] = !empty($_POST['home_play_modal']);
                $cfg['home_show_server_status'] = !empty($_POST['home_show_server_status']);

                GameConfigManager::saveConfig($cfg);
                $_SESSION['wg_admin_msg'] = ['ok' => true, 'text' => 'Gaming- & Darstellungs-Einstellungen gespeichert.'];
                header('Location: ?tab=tab-plugins&plugin_settings=weave-games&game_subtab=settings&msg=config_saved#tab-plugins');
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend-Aktionen für das Spieler-Portal (Login, Registrierung, Logout)
// -----------------------------------------------------------------------------
if (!empty($_GET['page']) && $_GET['page'] === 'games' && !empty($_GET['action'])) {
    $action = $_GET['action'];

    // Logout
    if ($action === 'logout') {
        GameUserManager::logout();
        header('Location: ?page=games');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {

            // Login
            if ($action === 'login') {
                $u = trim($_POST['username'] ?? '');
                $p = $_POST['password'] ?? '';
                $player = GameUserManager::authenticate($u, $p);
                if ($player) {
                    GameUserManager::login($player);
                    header('Location: ?page=games');
                    exit;
                }
                $_SESSION['game_auth_error'] = 'Ungültige Anmeldedaten oder Account gesperrt.';
                header('Location: ?page=games&mode=login');
                exit;
            }

            // Registrierung (Rolle strikt auf Spieler begrenzen)
            if ($action === 'register') {
                $cfg = GameConfigManager::getConfig();
                if (!empty($cfg['allow_self_registration'])) {
                    $error = null;
                    $playerData = [
                        'username' => $_POST['username'] ?? '',
                        'email'    => $_POST['email'] ?? '',
                        'password' => $_POST['password'] ?? '',
                        'nickname' => $_POST['nickname'] ?? '',
                        'role'     => GameUserManager::ROLE_PLAYER,
                    ];
                    $newPlayer = GameUserManager::createPlayer($playerData, $error);
                    if ($newPlayer) {
                        GameUserManager::login($newPlayer);
                        header('Location: ?page=games');
                        exit;
                    }
                    $_SESSION['game_auth_error'] = $error ?: 'Registrierung fehlgeschlagen.';
                } else {
                    $_SESSION['game_auth_error'] = 'Die Registrierung ist derzeit deaktiviert.';
                }
                header('Location: ?page=games&mode=register');
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Isolierter WebAssembly/JS Runner (?page=games&runner=<slug>) inkl. COOP/COEP
// -----------------------------------------------------------------------------
if (!empty($_GET['page']) && $_GET['page'] === 'games' && !empty($_GET['runner'])) {
    $runnerSlug = trim((string)$_GET['runner']);
    $runnerGame = GameRolloutManager::getGame($runnerSlug);
    if (!$runnerGame || empty($runnerGame['active']) || empty($runnerGame['embed_url']) || !GameRolloutManager::isSafeGameUrl((string)$runnerGame['embed_url'])) {
        http_response_code(404);
        echo 'Spiel nicht gefunden oder nicht im Browser ausführbar.';
        exit;
    }

    if (!empty($runnerGame['enable_coop_coep'])) {
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Embedder-Policy: require-corp');
    }

    $safeTitle = htmlspecialchars((string)($runnerGame['title'] ?? 'Spiel'), ENT_QUOTES, 'UTF-8');
    $safeSrc = htmlspecialchars((string)$runnerGame['embed_url'], ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $safeTitle ?> – Weave Games Runner</title>
        <style>
            html, body { margin:0; padding:0; width:100%; height:100%; background:#020617; color:#f8fafc; font-family: system-ui, sans-serif; display:flex; flex-direction:column; overflow:hidden; }
            .wg-runner-bar { display:flex; justify-content:space-between; align-items:center; padding:8px 14px; background:#0f172a; border-bottom:1px solid #1e293b; font-size:13px; }
            .wg-runner-btn { background:#1e293b; color:#f8fafc; border:1px solid #334155; border-radius:6px; padding:4px 10px; font-size:12px; cursor:pointer; text-decoration:none; }
            iframe { flex:1; width:100%; height:100%; border:0; background:#000; }
        </style>
    </head>
    <body>
        <div class="wg-runner-bar">
            <strong>🎮 <?= $safeTitle ?></strong>
            <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="wg-runner-btn" onclick="var f=document.getElementById('wgRunFrame'); if(f && f.requestFullscreen) f.requestFullscreen();">⛶ Vollbild</button>
                <a href="?page=games&play=<?= urlencode($runnerSlug) ?>" class="wg-runner-btn" target="_top">← Portal</a>
            </div>
        </div>
        <iframe id="wgRunFrame" src="<?= $safeSrc ?>" allow="fullscreen; gamepad; pointer-lock; autoplay" sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-popups allow-forms" allowfullscreen></iframe>
    </body>
    </html>
    <?php
    exit;
}

// -----------------------------------------------------------------------------
// 3b. Eigenständige Vollbild-Route für das Gaming-Portal (?page=games)
// -----------------------------------------------------------------------------
if (!empty($_GET['page']) && $_GET['page'] === 'games') {
    require __DIR__ . '/views/portal.php';
    exit;
}

// -----------------------------------------------------------------------------
// 4. WeaveCMS Backend Einstellungs-Tab Hook
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-games', function($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 5. Shortcode-Filter für Inhaltsseiten: [weave_games], [game_server]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[weave_games]') !== false || strpos($content, '[games]') !== false) {
        ob_start();
        require __DIR__ . '/views/home_section.php';
        $html = (string)ob_get_clean();
        // Shortcodes innerhalb von <code>, <pre>, <textarea>, <script>, HTML-Tags/Attributen oder Forum-Beiträgen nicht ersetzen
        $protected = [];
        $content = preg_replace_callback(
            '~<(code|pre|textarea|script)\b[^>]*>.*?</\1>|<[^>]+>~is',
            function ($m) use (&$protected) {
                $key = '%%WG_PROTECTED_' . count($protected) . '%%';
                $protected[$key] = $m[0];
                return $key;
            },
            $content
        );
        $content = str_replace(['[weave_games]', '[games]'], $html, $content);
        if (!empty($protected)) {
            $content = strtr($content, $protected);
        }
    }

    return $content;
});

// -----------------------------------------------------------------------------
// 6. Registrierung als optionale Sektion im WeaveCMS-Menü & Einzelseite
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-games', $data)) {
        return $sections;
    }
    $cfg = GameConfigManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    $sections[] = [
        'id'         => 'games',
        'type'       => 'games',
        'title'      => $cfg['section_title'] ?? 'Gaming & Spieleserver',
        'nav_label'  => 'Games',
        'url'        => '?page=games',
        'visible'    => true,
        'in_nav'     => $showInNav,
        'plugin'     => 'weave-games',
        'can_delete' => false,
        'placement'  => 'page',
        'icon'       => 'sports_esports'
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-games', $data)) {
        return $sections;
    }
    $cfg = GameConfigManager::getConfig();
    $showInNav = !empty($cfg['show_in_main_nav']);
    $hasGames = false;
    foreach ($sections as &$s) {
        if (($s['id'] ?? '') === 'games') {
            $s['in_nav'] = $showInNav;
            $s['url'] = '?page=games';
            $hasGames = true;
            break;
        }
    }
    unset($s);
    if (!$hasGames && $showInNav) {
        $sections[] = [
            'id'        => 'games',
            'type'      => 'games',
            'title'     => $cfg['section_title'] ?? 'Gaming & Spieleserver',
            'nav_label' => 'Games',
            'url'       => '?page=games',
            'visible'   => true,
            'in_nav'    => true,
            'plugin'    => 'weave-games',
            'placement' => 'page',
            'icon'      => 'sports_esports'
        ];
    }
    return $sections;
});

PluginManager::addAction('frontend_render_custom_section', function($sectionKey, $data) {
    if ($sectionKey === 'games') {
        require __DIR__ . '/views/home_section.php';
    }
});

