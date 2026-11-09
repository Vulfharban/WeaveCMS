<?php
/**
 * ============================================================================
 * Weave Games – Plugin Einstiegspunkt (plugin.php)
 * ============================================================================
 * Verwaltet:
 * - Admin-Aktionen für Game-Server (Start/Stop, Windows/Linux, SSH)
 * - Client-Update-Rollout & Patch-Verteilung (Fastadun / Rust Voxel RPG)
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
if (!empty($_SESSION['admin_logged_in'])) {

    // Server-Ping-Aktion via GET
    if (isset($_GET['action']) && $_GET['action'] === 'ping_all') {
        GameServerManager::refreshAllServerStatuses();
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

            // H: Konfiguration speichern
            if ($action === 'game_save_config') {
                $cfg = GameConfigManager::getConfig();
                $cfg['show_in_main_nav'] = !empty($_POST['show_in_main_nav']);
                $cfg['allow_self_registration'] = !empty($_POST['allow_self_registration']);
                $cfg['enable_public_server_status'] = !empty($_POST['enable_public_server_status']);
                if (isset($_POST['section_title'])) {
                    $cfg['section_title'] = trim($_POST['section_title']);
                }
                GameConfigManager::saveConfig($cfg);
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

            // Registrierung
            if ($action === 'register') {
                $cfg = GameConfigManager::getConfig();
                if (!empty($cfg['allow_self_registration'])) {
                    $error = null;
                    $newPlayer = GameUserManager::createPlayer($_POST, $error);
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
// 3. Eigenständige Vollbild-Route für das Gaming-Portal (?page=games)
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
        $portalUrl = '?page=games';
        $cfg = GameConfigManager::getConfig();
        $servers = GameServerManager::getServers();
        $online = count(array_filter($servers, fn($s) => ($s['status'] ?? '') === 'online'));
        $title = htmlspecialchars($cfg['section_title']);

        $html = "
            <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border-radius: 10px; padding: 24px; text-align: center; margin: 24px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.15);'>
                <h3 style='margin: 0 0 8px 0; color: #38bdf8;'>🎮 {$title}</h3>
                <p style='color: #cbd5e1; max-width: 600px; margin: 0 auto 16px auto; font-size: 14px;'>
                    Live Fastadun Voxel-Server ({$online} online), Client-Updates und Browsergames direkt im Web.
                </p>
                <a href='{$portalUrl}' class='btn' style='background: #38bdf8; color: #0f172a; padding: 10px 22px; font-weight: 800; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    🚀 Zum Gaming-Portal
                </a>
            </div>
        ";
        $content = str_replace(['[weave_games]', '[games]'], $html, $content);
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
        'title'      => $cfg['section_title'] ?? 'Gaming & Fastadun',
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
            'title'     => $cfg['section_title'] ?? 'Gaming & Fastadun',
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
        $cfg = GameConfigManager::getConfig();
        $servers = GameServerManager::getServers();
        $online = count(array_filter($servers, fn($s) => ($s['status'] ?? '') === 'online'));
        $title = htmlspecialchars($cfg['section_title']);
        $sub = htmlspecialchars($cfg['section_subtitle']);
        echo "
            <div class='container' style='padding: 40px 15px; text-align: center;'>
                <h2 style='color: #1e3a8a;'>🎮 {$title}</h2>
                <p style='max-width: 650px; margin: 0 auto 20px auto; color: #64748b;'>
                    {$sub} ({$online} Server aktuell erreichbar).
                </p>
                <a href='?page=games' class='btn' style='background: #1e3a8a; color: #fff; padding: 12px 28px; font-weight: 700; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    🚀 Gaming-Portal & Serverübersicht öffnen
                </a>
            </div>
        ";
    }
});
