<?php
/**
 * ============================================================================
 * Weave Games – Admin Einstellungs- & Verwaltungsbereich
 * ============================================================================
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Token
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once dirname(__DIR__) . '/GameUserManager.php';
require_once dirname(__DIR__) . '/GameRolloutManager.php';
require_once dirname(__DIR__) . '/GameServerManager.php';
require_once dirname(__DIR__) . '/GameConfigManager.php';

$activeSubtab = $_GET['game_subtab'] ?? 'servers';
$cfg = GameConfigManager::getConfig();
$servers = GameServerManager::getServers();
$games = GameRolloutManager::getGames();
$releases = GameRolloutManager::getReleases();
$players = GameUserManager::getPlayers();
$runtimeOptions = GameRolloutManager::getRuntimeOptions();
$categoryOptions = GameRolloutManager::getCategoryOptions();
$editSlug = trim((string)($_GET['edit_game'] ?? ''));
$editGame = $editSlug !== '' ? GameRolloutManager::getGame($editSlug) : null;
$wgAdminMsg = $_SESSION['wg_admin_msg'] ?? null;
unset($_SESSION['wg_admin_msg']);
$ta = static fn(string $key, string $default, array $params = []): string => function_exists('__a')
    ? __a($key, $default, null, $params)
    : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);

// KPI Berechnungen
$onlineServers = count(array_filter($servers, fn($s) => ($s['status'] ?? '') === 'online'));
$totalPlayers = count($players);
$totalReleases = count($releases);
?>

<div class="plugin-settings-wrapper" style="max-width: 1100px; margin: 0 auto; padding: 15px;">

    <!-- Header-Banner -->
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); color: #fff; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 50px; height: 50px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 26px;">
                🎮
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #fff;"><?= htmlspecialchars($ta('admin.games.header_title', 'Weave Games – Gaming-Plattform & Server-Manager')) ?></h3>
                <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 0.88rem;">
                    Serversteuerung (Start/Stop, Windows/Linux Parameter, SSH), Release-Rollouts, Client-Updates und Spieler-Accounts.
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="../?page=games" target="_blank" class="btn" style="background: #38bdf8; color: #0f172a; font-weight: 700; text-decoration: none; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                🚀 <?= htmlspecialchars($ta('admin.games.open_portal', 'Spieler-Portal öffnen')) ?>
            </a>
        </div>
    </div>

    <?php if (is_array($wgAdminMsg) && !empty($wgAdminMsg['text'])): ?>
        <div style="margin-bottom: 16px; padding: 11px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; background: <?= !empty($wgAdminMsg['ok']) ? '#f0fdf4' : '#fef2f2' ?>; border-left: 4px solid <?= !empty($wgAdminMsg['ok']) ? '#16a34a' : '#dc2626' ?>; color: <?= !empty($wgAdminMsg['ok']) ? '#166534' : '#991b1b' ?>;">
            <?= htmlspecialchars((string)$wgAdminMsg['text']) ?>
        </div>
    <?php endif; ?>

    <!-- Schnell-Kennzahlen -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.kpi_servers_active', 'Game-Server aktiv')) ?></div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= count($servers) ?> <span style="font-size: 12px; font-weight: 600; color: #10b981;">(<?= $onlineServers ?> online)</span></div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.kpi_players', 'Registrierte Spieler')) ?></div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= $totalPlayers ?></div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.kpi_catalog', 'Spiele im Katalog')) ?></div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= count($games) ?></div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.kpi_releases', 'Client-Releases')) ?></div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= $totalReleases ?></div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" 
           class="btn <?= ($activeSubtab === 'servers') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🖥️ <?= htmlspecialchars($ta('admin.games.subtab_servers', 'Server-Verwaltung & Status')) ?> (<?= count($servers) ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" 
           class="btn <?= ($activeSubtab === 'rollout') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🚀 <?= htmlspecialchars($ta('admin.games.subtab_rollout', 'Client-Rollout & Updates')) ?> (<?= $totalReleases ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=players#tab-plugins" 
           class="btn <?= ($activeSubtab === 'players') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            👥 <?= htmlspecialchars($ta('admin.games.subtab_players', 'Spieler-Accounts')) ?> (<?= $totalPlayers ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins" 
           class="btn <?= ($activeSubtab === 'games') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🎮 <?= htmlspecialchars($ta('admin.games.subtab_catalog', 'Spiele-Katalog')) ?> (<?= count($games) ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=settings#tab-plugins" 
           class="btn <?= ($activeSubtab === 'settings') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            ⚙️ <?= htmlspecialchars($ta('admin.games.subtab_settings', 'Globale Einstellungen')) ?>
        </a>
    </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 1: SERVER-VERWALTUNG & STATUS                                  -->
    <!-- ===================================================================== -->
    <?php if ($activeSubtab === 'servers'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.subtab_servers', 'Server-Verwaltung & Status')) ?></h4>
            <div style="display: flex; gap: 8px;">
                <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&action=ping_all#tab-plugins" class="btn btn-light" style="padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none;">
                    🔄 <?= htmlspecialchars($ta('admin.games.ping_all', 'Alle Server pingen')) ?>
                </a>
                <button type="button" onclick="document.getElementById('modalAddServer').style.display='flex';" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
                    <?= htmlspecialchars($ta('admin.games.add_server', '+ Server hinzufügen')) ?>
                </button>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 750px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_status', 'Status')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_server_game', 'Servername & Spiel')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_host_port', 'Host & Port')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_os_conn', 'OS / Anbindung')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_params', 'Parameter')) ?></th>
                        <th style="padding: 10px 14px; text-align: right;"><?= htmlspecialchars($ta('admin.games.col_actions', 'Aktionen')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($servers)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                <?= htmlspecialchars($ta('admin.games.no_servers', 'Noch keine Game-Server konfiguriert.')) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servers as $s): 
                            $status = $s['status'] ?? 'offline';
                            $statusBadge = ($status === 'online') 
                                ? '<span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">🟢 Online (' . ($s['last_ping_ms'] !== null ? $s['last_ping_ms'].'ms' : 'OK') . ')</span>'
                                : (($status === 'starting') 
                                    ? '<span style="background: #fef9c3; color: #854d0e; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">🟡 Startet...</span>'
                                    : '<span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">🔴 Offline</span>');
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <?= $statusBadge ?>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                                    <span style="font-size: 11px; color: #64748b;">Spiel: <?= htmlspecialchars($s['game_slug']) ?> | ID: <code><?= htmlspecialchars($s['id']) ?></code></span>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <code><?= htmlspecialchars($s['host']) ?>:<?= (int)$s['port'] ?></code>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <span style="font-weight: 600; text-transform: capitalize;"><?= htmlspecialchars($s['os_type']) ?></span>
                                    <span style="font-size: 11px; color: #64748b;">(<?= ($s['connection_type'] === 'ssh') ? 'SSH Remote' : 'Lokal' ?>)</span>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($s['custom_parameters']) ?>">
                                    <code style="font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($s['custom_parameters']) ?></code>
                                </td>
                                <td style="padding: 12px 14px; text-align: right; vertical-align: middle;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <!-- Start -->
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="game_server_start">
                                            <input type="hidden" name="server_id" value="<?= htmlspecialchars($s['id']) ?>">
                                            <button type="submit" class="btn" style="background: #10b981; color: #fff; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 4px;" title="<?= htmlspecialchars($ta('admin.games.btn_start', 'Start')) ?>">▶ <?= htmlspecialchars($ta('admin.games.btn_start', 'Start')) ?></button>
                                        </form>

                                        <!-- Stop -->
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="game_server_stop">
                                            <input type="hidden" name="server_id" value="<?= htmlspecialchars($s['id']) ?>">
                                            <button type="submit" class="btn" style="background: #ef4444; color: #fff; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 4px;" title="<?= htmlspecialchars($ta('admin.games.btn_stop', 'Stop')) ?>">⏹ <?= htmlspecialchars($ta('admin.games.btn_stop', 'Stop')) ?></button>
                                        </form>

                                        <!-- Löschen -->
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="margin: 0;" onsubmit="return confirm(<?= htmlspecialchars(json_encode($ta('admin.games.confirm_delete_server', 'Server wirklich löschen?')), ENT_QUOTES) ?>);">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="game_server_delete">
                                            <input type="hidden" name="server_id" value="<?= htmlspecialchars($s['id']) ?>">
                                            <button type="submit" class="btn" style="background: #cbd5e1; color: #334155; padding: 3px 7px; font-size: 11px; border-radius: 4px;" title="<?= htmlspecialchars($ta('admin.games.btn_delete', 'Löschen')) ?>">🗑</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: Server anlegen / bearbeiten -->
        <div id="modalAddServer" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: #fff; width: 650px; max-width: 95%; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.25); max-height: 90vh; display: flex; flex-direction: column;">
                <div style="background: #1e3a8a; color: #fff; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 15px;">🖥️ <?= htmlspecialchars($ta('admin.games.modal_add_server', 'Neuen Game-Server hinzufügen')) ?></strong>
                    <button type="button" onclick="document.getElementById('modalAddServer').style.display='none';" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="padding: 18px; overflow-y: auto;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="game_server_save">

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 2;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.col_server_game', 'Servername & Spiel')) ?> *</label>
                            <input type="text" name="name" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="Dedicated Server 01">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_game_req', 'Spiel *')) ?></label>
                            <select name="game_slug" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <?php foreach ($games as $g): ?>
                                    <option value="<?= htmlspecialchars($g['slug']) ?>"><?= htmlspecialchars($g['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 2;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Host / IP *</label>
                            <input type="text" name="host" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" value="127.0.0.1">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Game Port *</label>
                            <input type="number" name="port" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" value="7777">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Max Players</label>
                            <input type="number" name="max_players" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" value="32">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_server_os', 'Betriebssystem des Servers')) ?></label>
                            <select name="os_type" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="windows"><?= htmlspecialchars($ta('admin.games.opt_windows', 'Windows Server (.exe)')) ?></option>
                                <option value="linux"><?= htmlspecialchars($ta('admin.games.opt_linux', 'Linux Server (ELF binary / systemd)')) ?></option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_conn_type', 'Verbindungstyp')) ?></label>
                            <select name="connection_type" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="local"><?= htmlspecialchars($ta('admin.games.opt_local', 'Lokal auf diesem Host ausführen')) ?></option>
                                <option value="ssh"><?= htmlspecialchars($ta('admin.games.opt_ssh', 'Remote via SSH steuern')) ?></option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_start_params', 'Start-Parameter (Windows & Linux)')) ?></label>
                        <input type="text" name="custom_parameters" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="--port 7777 --world world_01 --max-players 32 --pvp false" value="--port 7777 --world world_01 --max-players 32">
                        <small style="color: #64748b; font-size: 11px;"><?= htmlspecialchars($ta('admin.games.hint_start_params', 'Diese Argumente werden beim Serverstart an das Binary übergeben.')) ?></small>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_exec_path', 'Pfad zur Server-Ausführungsdatei')) ?></label>
                        <input type="text" name="executable_path" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="C:\Games\server.exe / /opt/gameserver/server">
                    </div>

                    <fieldset style="border: 1px dashed #cbd5e1; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px;">
                        <legend style="font-size: 12px; font-weight: 700; color: #1e3a8a;"><?= htmlspecialchars($ta('admin.games.ssh_legend', 'SSH Remote-Zugangsdaten (optional für externe Server)')) ?></legend>
                        <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                            <div style="flex: 2;">
                                <input type="text" name="ssh_host" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="SSH Host / IP">
                            </div>
                            <div style="flex: 1;">
                                <input type="number" name="ssh_port" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="Port" value="22">
                            </div>
                            <div style="flex: 1;">
                                <input type="text" name="ssh_user" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="<?= htmlspecialchars($ta('admin.games.ssh_user_ph', 'Benutzer (root)')) ?>">
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <input type="password" name="ssh_pass" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="<?= htmlspecialchars($ta('admin.games.ssh_pass_ph', 'SSH Passwort (oder leer)')) ?>">
                            </div>
                            <div style="flex: 1;">
                                <input type="text" name="ssh_key" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="<?= htmlspecialchars($ta('admin.games.ssh_key_ph', 'Privater Schlüssel Pfad (~/.ssh/id_rsa)')) ?>">
                            </div>
                        </div>
                    </fieldset>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" onclick="document.getElementById('modalAddServer').style.display='none';" class="btn btn-light" style="padding: 6px 14px;"><?= htmlspecialchars($ta('admin.games.btn_cancel', 'Abbrechen')) ?></button>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 18px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.btn_save_server', 'Server speichern')) ?></button>
                    </div>
                </form>
            </div>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 2: CLIENT-ROLLOUT & UPDATES                                    -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'rollout'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.rollout_heading', 'Client-Releases & Update-Verteilung')) ?></h4>
            <button type="button" onclick="document.getElementById('modalAddRelease').style.display='flex';" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
                <?= htmlspecialchars($ta('admin.games.add_release', '+ Release / Update hinzufügen')) ?>
            </button>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 700px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_game', 'Spiel')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_version', 'Version')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_channel_platform', 'Channel / Plattform')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_download_size', 'Download & Größe')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_sha256', 'SHA256 Prüfsumme')) ?></th>
                        <th style="padding: 10px 14px; text-align: right;"><?= htmlspecialchars($ta('admin.games.col_action', 'Aktion')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($releases)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                <?= htmlspecialchars($ta('admin.games.no_releases', 'Noch keine Client-Releases veröffentlicht.')) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($releases as $r): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px; font-weight: 700;"><?= htmlspecialchars($r['game_slug']) ?></td>
                                <td style="padding: 12px 14px;">
                                    <span style="background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">v<?= htmlspecialchars($r['version']) ?></span>
                                    <?php if (!empty($r['mandatory'])): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.badge_mandatory', 'Pflicht')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <?= htmlspecialchars($r['channel']) ?> / <code><?= htmlspecialchars($r['platform']) ?></code>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <a href="../<?= htmlspecialchars($r['download_url']) ?>" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 600;"><?= htmlspecialchars($ta('admin.games.package_download', 'Paket-Download')) ?></a>
                                    <span style="color: #64748b; font-size: 11px;">(<?= htmlspecialchars($r['file_size']) ?>)</span>
                                </td>
                                <td style="padding: 12px 14px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($r['sha256']) ?>">
                                    <code style="font-size: 10px;"><?= htmlspecialchars($r['sha256']) ?></code>
                                </td>
                                <td style="padding: 12px 14px; text-align: right;">
                                    <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" style="margin: 0;" onsubmit="return confirm(<?= htmlspecialchars(json_encode($ta('admin.games.confirm_delete_release', 'Release löschen?')), ENT_QUOTES) ?>);">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="game_release_delete">
                                        <input type="hidden" name="release_id" value="<?= htmlspecialchars($r['id']) ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 11px; border-radius: 4px;"><?= htmlspecialchars($ta('admin.games.btn_delete', 'Löschen')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: Release anlegen -->
        <div id="modalAddRelease" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: #fff; width: 550px; max-width: 95%; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.25);">
                <div style="background: #1e3a8a; color: #fff; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 15px;">🚀 <?= htmlspecialchars($ta('admin.games.modal_add_release', 'Neues Client-Release veröffentlichen')) ?></strong>
                    <button type="button" onclick="document.getElementById('modalAddRelease').style.display='none';" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" style="padding: 18px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="game_release_save">

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_game_req', 'Spiel *')) ?></label>
                            <select name="game_slug" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <?php foreach ($games as $g): ?>
                                    <option value="<?= htmlspecialchars($g['slug']) ?>"><?= htmlspecialchars($g['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_version_req', 'Version (SemVer) *')) ?></label>
                            <input type="text" name="version" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="1.0.0">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_channel', 'Kanal')) ?></label>
                            <select name="channel" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="stable"><?= htmlspecialchars($ta('admin.games.opt_stable', 'Stable (Offiziell)')) ?></option>
                                <option value="beta"><?= htmlspecialchars($ta('admin.games.opt_beta', 'Beta (Testkanal)')) ?></option>
                                <option value="nightly"><?= htmlspecialchars($ta('admin.games.opt_nightly', 'Nightly (Entwicklungsstand)')) ?></option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_target_platform', 'Ziel-Plattform')) ?></label>
                            <select name="platform" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="windows_x64">Windows (x64 .zip / .exe)</option>
                                <option value="linux_x64">Linux (x64 tar.gz)</option>
                                <option value="macos">macOS</option>
                                <option value="all"><?= htmlspecialchars($ta('admin.games.opt_all_platforms', 'Alle Plattformen')) ?></option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_dl_url_req', 'Download-URL / Dateipfad *')) ?></label>
                        <input type="text" name="download_url" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="downloads/games/client-v1.0.0.zip">
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_file_size', 'Dateigröße')) ?></label>
                            <input type="text" name="file_size" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="52.4 MB">
                        </div>
                        <div style="flex: 2;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.col_sha256', 'SHA256 Prüfsumme')) ?></label>
                            <input type="text" name="sha256" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="SHA256">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_patch_notes', 'Patch-Notes / Changelog')) ?></label>
                        <textarea name="patch_notes" rows="3" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;"></textarea>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mandatory" value="1">
                            <strong><?= htmlspecialchars($ta('admin.games.label_mandatory_update', 'Pflicht-Update:')) ?></strong> <?= htmlspecialchars($ta('admin.games.hint_mandatory_update', 'Clients müssen diese Version laden, um sich mit dem Server verbinden zu dürfen.')) ?>
                        </label>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" onclick="document.getElementById('modalAddRelease').style.display='none';" class="btn btn-light" style="padding: 6px 14px;"><?= htmlspecialchars($ta('admin.games.btn_cancel', 'Abbrechen')) ?></button>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 18px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.btn_publish_release', 'Release veröffentlichen')) ?></button>
                    </div>
                </form>
            </div>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 3: SPIELER & ACCOUNTS                                          -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'players'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.players_heading', 'Spieler-Accounts & Online-Registrierungen')) ?></h4>
            <span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($ta('admin.games.players_total', 'Gesamt: {count} Spieler', ['count' => (string)count($players)])) ?></span>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 650px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_player_nick', 'Spieler / Nickname')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_email', 'E-Mail')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_role', 'Rolle')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_status', 'Status')) ?></th>
                        <th style="padding: 10px 14px;"><?= htmlspecialchars($ta('admin.games.col_last_login', 'Letztes Login')) ?></th>
                        <th style="padding: 10px 14px; text-align: right;"><?= htmlspecialchars($ta('admin.games.col_action', 'Aktion')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($players)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                <?= htmlspecialchars($ta('admin.games.no_players', 'Noch keine Spieler registriert.')) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($players as $p): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px;">
                                    <strong><?= htmlspecialchars($p['username']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;">Ingame: <?= htmlspecialchars($p['nickname'] ?? $p['username']) ?></div>
                                </td>
                                <td style="padding: 12px 14px;"><?= htmlspecialchars($p['email']) ?></td>
                                <td style="padding: 12px 14px;">
                                    <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?= htmlspecialchars($p['role']) ?></span>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <?php if (!empty($p['is_banned'])): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.status_banned', 'Gesperrt')) ?></span>
                                    <?php else: ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.status_active', 'Aktiv')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; font-size: 12px; color: #64748b;"><?= htmlspecialchars($p['last_login'] ?? $ta('admin.games.never', 'Nie')) ?></td>
                                <td style="padding: 12px 14px; text-align: right;">
                                    <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=players#tab-plugins" style="display: inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode($ta('admin.games.confirm_delete_player', 'Spieler wirklich löschen?')), ENT_QUOTES) ?>);">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="game_player_delete">
                                        <input type="hidden" name="player_id" value="<?= htmlspecialchars($p['id']) ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 11px; border-radius: 4px;"><?= htmlspecialchars($ta('admin.games.btn_delete', 'Löschen')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 4: SPIELE-KATALOG (JS, WASM, Rust, C++, Java & Web-Apps)       -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'games'): ?>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 14px;">
                <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">
                    <?= $editGame ? '✏️ ' . htmlspecialchars($ta('admin.games.edit_game_heading', 'Spiel / Anwendung bearbeiten: {title}', ['title' => (string)$editGame['title']])) : '➕ ' . htmlspecialchars($ta('admin.games.add_game_heading', 'Neues Spiel / neue Anwendung anlegen (JS, WASM, Rust, C++, Java)')) ?>
                </h4>
                <?php if ($editGame): ?>
                    <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins" class="btn btn-light" style="padding: 4px 10px; font-size: 12px; text-decoration: none;"><?= htmlspecialchars($ta('admin.games.btn_create_new', 'Neues anlegen')) ?></a>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="game_save">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_title_req', 'Titel *')) ?></label>
                        <input type="text" name="title" required class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['title'] ?? '')) ?>" placeholder="Rust Raytracer WASM">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_slug', 'Slug (Eindeutige ID)')) ?></label>
                        <input type="text" name="slug" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px; background: <?= $editGame ? '#f8fafc' : '#ffffff' ?>;" value="<?= htmlspecialchars((string)($editGame['slug'] ?? '')) ?>" <?= $editGame ? 'readonly' : '' ?> placeholder="<?= htmlspecialchars($ta('admin.games.ph_slug', 'auto aus Titel falls leer')) ?>">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_category', 'Kategorie')) ?></label>
                        <select name="category" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <?php foreach ($categoryOptions as $cKey => $cLabel): ?>
                                <option value="<?= htmlspecialchars($cKey) ?>" <?= (($editGame['category'] ?? 'browser') === $cKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_runtime', 'Technologie / Laufzeit')) ?></label>
                        <select name="runtime_type" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <?php foreach ($runtimeOptions as $rKey => $rInfo): ?>
                                <option value="<?= htmlspecialchars($rKey) ?>" <?= (($editGame['runtime_type'] ?? 'html5_js') === $rKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rInfo['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_genre', 'Genre / Untertitel')) ?></label>
                        <input type="text" name="genre" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['genre'] ?? '')) ?>" placeholder="3D Engine / Puzzle / Tool">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_tech_tags', 'Tech-Tags (kommagetrennt)')) ?></label>
                        <input type="text" name="tech_tags" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars(implode(', ', is_array($editGame['tech_tags'] ?? null) ? $editGame['tech_tags'] : [])) ?>" placeholder="Rust, WASM, WebGL2, Audio">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_icon', 'Icon (Emoji)')) ?></label>
                        <input type="text" name="icon" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['icon'] ?? '🎮')) ?>" placeholder="🎮">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_sort_order', 'Sortier-Reihenfolge')) ?></label>
                        <input type="number" name="sort_order" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= (int)($editGame['sort_order'] ?? 10) ?>">
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_description', 'Beschreibung')) ?></label>
                    <textarea name="description" rows="2" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="<?= htmlspecialchars($ta('admin.games.ph_description', 'Kurze Beschreibung für Startseite & Portal...')) ?>"><?= htmlspecialchars((string)($editGame['description'] ?? '')) ?></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_sys_req', 'Systemanforderungen / Hinweise (z. B. Java 21, WebGPU, Controller)')) ?></label>
                    <input type="text" name="system_requirements" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['system_requirements'] ?? '')) ?>" placeholder="WebGL2 / WASM / 64-Bit">
                </div>

                <!-- Browser-Einbindung & ZIP / Cover Upload -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 12px;">
                    <div style="font-weight: 700; font-size: 13px; color: #1e3a8a; margin-bottom: 10px;">
                        🌐 <?= htmlspecialchars($ta('admin.games.browser_embed_heading', 'Browser-Einbindung (JS, HTML5, WebAssembly / Rust / C++ Emscripten / CheerpJ Java) & Cover')) ?>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 10px;">
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_embed_url', 'Einbindungs-URL / Pfad zu index.html')) ?></label>
                            <input type="text" name="embed_url" class="form-control" style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['embed_url'] ?? '')) ?>" placeholder="uploads/games/my-game/index.html">
                        </div>
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_zip_upload', 'ODER ZIP-Paket hochladen (entpackt automatisch)')) ?></label>
                            <input type="file" name="game_zip" accept=".zip" class="form-control" style="width: 100%; padding: 4px; font-size: 12px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 10px;">
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_cover_url', 'Cover-Bild URL (optional)')) ?></label>
                            <input type="text" name="banner" class="form-control" style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($editGame['banner'] ?? '')) ?>" placeholder="uploads/games/covers/...">
                        </div>
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_cover_upload', 'ODER Cover-Bild hochladen (JPG/PNG/WebP)')) ?></label>
                            <input type="file" name="game_cover" accept="image/*" class="form-control" style="width: 100%; padding: 4px; font-size: 12px;">
                        </div>
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_start_mode', 'Startmodus')) ?></label>
                            <select name="embed_mode" class="form-control" style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="modal" <?= (($editGame['embed_mode'] ?? 'modal') === 'modal') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_mode_modal', 'Lightbox-Modal (wie Galerie)')) ?></option>
                                <option value="inline" <?= (($editGame['embed_mode'] ?? '') === 'inline') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_mode_inline', 'Inline im Portal einbetten')) ?></option>
                                <option value="fullscreen" <?= (($editGame['embed_mode'] ?? '') === 'fullscreen') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_mode_fullscreen', 'Isolierter Vollbild-Runner')) ?></option>
                                <option value="new_tab" <?= (($editGame['embed_mode'] ?? '') === 'new_tab') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_mode_new_tab', 'In neuem Browser-Tab öffnen')) ?></option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: 600; display: block; font-size: 11px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_aspect', 'Seitenverhältnis')) ?></label>
                            <select name="aspect_ratio" class="form-control" style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="16:9" <?= (($editGame['aspect_ratio'] ?? '16:9') === '16:9') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_aspect_16_9', '16:9 (Widescreen)')) ?></option>
                                <option value="4:3" <?= (($editGame['aspect_ratio'] ?? '') === '4:3') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_aspect_4_3', '4:3 (Klassik / Retro)')) ?></option>
                                <option value="1:1" <?= (($editGame['aspect_ratio'] ?? '') === '1:1') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_aspect_1_1', '1:1 (Quadrat)')) ?></option>
                                <option value="auto" <?= (($editGame['aspect_ratio'] ?? '') === 'auto') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_aspect_auto', 'Automatisch / Flexibel')) ?></option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 18px; padding-top: 6px; font-size: 12px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="enable_coop_coep" value="1" <?= !empty($editGame['enable_coop_coep']) ? 'checked' : '' ?>>
                            <span><strong><?= htmlspecialchars($ta('admin.games.label_coop_coep', 'COOP/COEP-Isolation aktivieren')) ?></strong> <?= htmlspecialchars($ta('admin.games.hint_coop_coep', '(nötig für WASM Multi-Threading / SharedArrayBuffer)')) ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="show_on_home" value="1" <?= (!$editGame || !isset($editGame['show_on_home']) || !empty($editGame['show_on_home'])) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($ta('admin.games.label_show_on_home', 'Auf Startseite anzeigen')) ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="featured" value="1" <?= !empty($editGame['featured']) ? 'checked' : '' ?>>
                            <span>★ <?= htmlspecialchars($ta('admin.games.label_featured', 'Als Highlight markieren')) ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="active" value="1" <?= (!$editGame || !empty($editGame['active'])) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($ta('admin.games.label_active', 'Aktiv')) ?></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-weight: 700;">
                    💾 <?= htmlspecialchars($editGame ? $ta('admin.games.btn_save_changes', 'Änderungen speichern') : $ta('admin.games.btn_add_game', 'Spiel / Anwendung hinzufügen')) ?>
                </button>
            </form>
        </div>

        <h4 style="margin: 0 0 14px 0; color: #1e293b; font-size: 16px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.managed_games_heading', 'Verwaltete Spiele & Titel ({count})', ['count' => (string)count($games)])) ?></h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px;">
            <?php foreach ($games as $g): ?>
                <?php
                $rtKey = (string)($g['runtime_type'] ?? 'html5_js');
                $rtInfo = $runtimeOptions[$rtKey] ?? ['badge' => $rtKey, 'color' => '#334155'];
                ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 24px;"><?= htmlspecialchars(strlen((string)($g['icon'] ?? '')) <= 4 ? (string)$g['icon'] : '🎮') ?></span>
                            <div>
                                <strong style="font-size: 15px; color: #1e3a8a;"><?= htmlspecialchars((string)$g['title']) ?></strong><br>
                                <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($categoryOptions[$g['category'] ?? ''] ?? ($g['category'] ?? 'Game')) ?></span>
                            </div>
                        </div>
                        <span style="background: <?= htmlspecialchars($rtInfo['color']) ?>; color: #fff; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;">
                            <?= htmlspecialchars($rtInfo['badge']) ?>
                        </span>
                    </div>
                    <?php if (!empty($g['banner'])): ?>
                        <?php $admCov = (str_starts_with((string)$g['banner'], 'http://') || str_starts_with((string)$g['banner'], 'https://')) ? (string)$g['banner'] : ('../' . ltrim((string)$g['banner'], '/')); ?>
                        <img src="<?= htmlspecialchars($admCov) ?>" alt="" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <?php endif; ?>
                    <p style="font-size: 12px; color: #475569; margin: 0; line-height: 1.4; flex: 1;">
                        <?= htmlspecialchars((string)$g['description']) ?>
                    </p>
                    <div style="font-size: 11px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span>Slug: <code><?= htmlspecialchars((string)$g['slug']) ?></code></span>
                        <div style="display: flex; gap: 6px;">
                            <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games&edit_game=<?= urlencode((string)$g['slug']) ?>#tab-plugins" class="btn btn-light" style="padding: 3px 9px; font-size: 11px; text-decoration: none;">✏️ <?= htmlspecialchars($ta('admin.games.btn_edit', 'Bearbeiten')) ?></a>
                            <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins" style="margin: 0;" onsubmit="return confirm(<?= htmlspecialchars(json_encode($ta('admin.games.confirm_delete_game', 'Spiel wirklich löschen?')), ENT_QUOTES) ?>);">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="action" value="game_delete">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars((string)$g['slug']) ?>">
                                <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 11px; border-radius: 4px;">🗑</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 5: GLOBALE EINSTELLUNGEN & STARTSEITEN-LAYOUT                  -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'settings'): ?>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
            <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=settings#tab-plugins">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="game_save_config">

                <h4 style="margin-top: 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">⚙️ <?= htmlspecialchars($ta('admin.games.settings_general', 'Allgemeine Gaming-Einstellungen')) ?></h4>

                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="show_in_main_nav" value="1" <?= !empty($cfg['show_in_main_nav']) ? 'checked' : '' ?>>
                        <strong><?= htmlspecialchars($ta('admin.games.opt_show_in_nav', 'Im Hauptmenü der Website anzeigen')) ?></strong>
                    </label>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="allow_self_registration" value="1" <?= !empty($cfg['allow_self_registration']) ? 'checked' : '' ?>>
                        <strong><?= htmlspecialchars($ta('admin.games.opt_allow_reg', 'Online-Registrierung für Spieler im Webportal & Client erlauben')) ?></strong>
                    </label>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="enable_public_server_status" value="1" <?= !empty($cfg['enable_public_server_status']) ? 'checked' : '' ?>>
                        <strong><?= htmlspecialchars($ta('admin.games.opt_public_server_status', 'Öffentlichen Server-Status im Spieler-Portal anzeigen')) ?></strong>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 20px;">
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_section_title', 'Sektionstitel')) ?></label>
                        <input type="text" name="section_title" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($cfg['section_title'] ?? '')) ?>">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_section_subtitle', 'Untertitel')) ?></label>
                        <input type="text" name="section_subtitle" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars((string)($cfg['section_subtitle'] ?? '')) ?>">
                    </div>
                </div>

                <h4 style="margin-top: 22px; color: #1e3a8a; font-size: 16px; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">🎨 <?= htmlspecialchars($ta('admin.games.settings_home_layout', 'Darstellung auf der Startseite (ähnlich Bildergalerie)')) ?></h4>
                <p style="font-size: 12px; color: #64748b; margin-top: 0;">
                    <?= htmlspecialchars($ta('admin.games.hint_home_layout', 'Bestimme hier, wie Spiele und Anwendungen auf der Startseite präsentiert werden.')) ?> (<code>[weave_games]</code>)
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_layout_style', 'Layout-Stil')) ?></label>
                        <select name="home_layout" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="ambient_blur" <?= (($cfg['home_layout'] ?? 'ambient_blur') === 'ambient_blur') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_layout_ambient', 'Ambient Blur (wie Bildergalerie)')) ?></option>
                            <option value="cards" <?= (($cfg['home_layout'] ?? '') === 'cards') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_layout_cards', 'Klassisches Karten-Raster')) ?></option>
                            <option value="showcase" <?= (($cfg['home_layout'] ?? '') === 'showcase') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_layout_showcase', 'Showcase (große Lead-Karte + Raster)')) ?></option>
                            <option value="list" <?= (($cfg['home_layout'] ?? '') === 'list') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_layout_list', 'Kompakte App-/Spieleliste')) ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_columns', 'Spaltenanzahl (Raster)')) ?></label>
                        <select name="home_columns" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="2" <?= ((int)($cfg['home_columns'] ?? 3) === 2) ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_cols_2', '2 Spalten')) ?></option>
                            <option value="3" <?= ((int)($cfg['home_columns'] ?? 3) === 3) ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_cols_3', '3 Spalten (Empfohlen)')) ?></option>
                            <option value="4" <?= ((int)($cfg['home_columns'] ?? 3) === 4) ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_cols_4', '4 Spalten')) ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_max_items', 'Max. Einträge auf Startseite (0 = alle)')) ?></label>
                        <input type="number" name="home_max_items" min="0" max="50" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= (int)($cfg['home_max_items'] ?? 6) ?>">
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_sort', 'Sortierung')) ?></label>
                        <select name="home_sort" class="form-control" style="width: 100%; padding: 7px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="manual" <?= (($cfg['home_sort'] ?? 'manual') === 'manual') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_sort_manual', 'Highlights & Sortiernummer')) ?></option>
                            <option value="newest" <?= (($cfg['home_sort'] ?? '') === 'newest') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_sort_newest', 'Neueste zuerst')) ?></option>
                            <option value="alpha" <?= (($cfg['home_sort'] ?? '') === 'alpha') ? 'selected' : '' ?>><?= htmlspecialchars($ta('admin.games.opt_sort_alpha', 'Alphabetisch (A–Z)')) ?></option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 20px; font-size: 12.5px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="home_show_filters" value="1" <?= !empty($cfg['home_show_filters']) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($ta('admin.games.opt_home_filters', 'Kategorie-Filterleiste anzeigen (wie Galerie)')) ?></span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="home_play_modal" value="1" <?= !empty($cfg['home_play_modal']) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($ta('admin.games.opt_home_modal', 'Browserspiele direkt per Lightbox-Modal starten')) ?></span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="home_show_server_status" value="1" <?= !empty($cfg['home_show_server_status']) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($ta('admin.games.opt_home_server_badge', 'Live-Server-Badge auf Karten anzeigen')) ?></span>
                    </label>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;"><?= htmlspecialchars($ta('admin.games.label_api_key', 'REST API-Schlüssel für Server-Kommunikation')) ?></label>
                    <input type="text" readonly class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; font-family: monospace;" value="<?= htmlspecialchars((string)($cfg['api_key'] ?? '')) ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-weight: 700;"><?= htmlspecialchars($ta('admin.games.btn_save_settings', 'Einstellungen speichern')) ?></button>
            </form>
        </div>
    <?php endif; ?>

</div>

