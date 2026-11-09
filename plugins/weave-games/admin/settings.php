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
                <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #fff;">Weave Games – Gaming-Plattform & Server-Manager</h3>
                <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 0.88rem;">
                    Serversteuerung (Start/Stop, Windows/Linux Parameter, SSH), Release-Rollouts, Client-Updates und Spieler-Accounts.
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="../?page=games" target="_blank" class="btn" style="background: #38bdf8; color: #0f172a; font-weight: 700; text-decoration: none; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                🚀 Spieler-Portal öffnen
            </a>
        </div>
    </div>

    <!-- Schnell-Kennzahlen -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Game-Server aktiv</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= count($servers) ?> <span style="font-size: 12px; font-weight: 600; color: #10b981;">(<?= $onlineServers ?> online)</span></div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Registrierte Spieler</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= $totalPlayers ?> Accounts</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Spiele im Katalog</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= count($games) ?> Games</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Client-Releases</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a;"><?= $totalReleases ?> Rollouts</div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" 
           class="btn <?= ($activeSubtab === 'servers') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🖥️ Server-Verwaltung & Status (<?= count($servers) ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" 
           class="btn <?= ($activeSubtab === 'rollout') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🚀 Client-Rollout & Updates (<?= $totalReleases ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=players#tab-plugins" 
           class="btn <?= ($activeSubtab === 'players') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            👥 Spieler-Accounts (<?= $totalPlayers ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=games#tab-plugins" 
           class="btn <?= ($activeSubtab === 'games') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            🎮 Spiele-Katalog (<?= count($games) ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=settings#tab-plugins" 
           class="btn <?= ($activeSubtab === 'settings') ? 'btn-primary' : 'btn-light' ?>" 
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            ⚙️ Globale Einstellungen
        </a>
    </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 1: SERVER-VERWALTUNG & STATUS                                  -->
    <!-- ===================================================================== -->
    <?php if ($activeSubtab === 'servers'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">Eingerichtete Game-Server & Cluster</h4>
            <div style="display: flex; gap: 8px;">
                <a href="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers&action=ping_all#tab-plugins" class="btn btn-light" style="padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none;">
                    🔄 Alle anpingen
                </a>
                <button type="button" onclick="document.getElementById('modalAddServer').style.display='flex';" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
                    + Server hinzufügen
                </button>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 750px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;">Status</th>
                        <th style="padding: 10px 14px;">Servername & Spiel</th>
                        <th style="padding: 10px 14px;">Host & Port</th>
                        <th style="padding: 10px 14px;">OS / Anbindung</th>
                        <th style="padding: 10px 14px;">Parameter</th>
                        <th style="padding: 10px 14px; text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($servers)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                Noch keine Game-Server hinterlegt. Klicken Sie auf <strong>"+ Server hinzufügen"</strong>.
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
                                            <button type="submit" class="btn" style="background: #10b981; color: #fff; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 4px;" title="Server starten">▶ Start</button>
                                        </form>

                                        <!-- Stop -->
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="margin: 0;" onsubmit="return confirm('Server wirklich stoppen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="game_server_stop">
                                            <input type="hidden" name="server_id" value="<?= htmlspecialchars($s['id']) ?>">
                                            <button type="submit" class="btn" style="background: #ef4444; color: #fff; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 4px;" title="Server stoppen">⏹ Stop</button>
                                        </form>

                                        <!-- Löschen -->
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="margin: 0;" onsubmit="return confirm('Serverkonfiguration wirklich löschen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="game_server_delete">
                                            <input type="hidden" name="server_id" value="<?= htmlspecialchars($s['id']) ?>">
                                            <button type="submit" class="btn" style="background: #cbd5e1; color: #334155; padding: 3px 7px; font-size: 11px; border-radius: 4px;" title="Löschen">🗑</button>
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
                    <strong style="font-size: 15px;">🖥️ Game-Server hinzufügen / konfigurieren</strong>
                    <button type="button" onclick="document.getElementById('modalAddServer').style.display='none';" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=servers#tab-plugins" style="padding: 18px; overflow-y: auto;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="game_server_save">

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 2;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Servername *</label>
                            <input type="text" name="name" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="z. B. Dedicated Server 01">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Spiel *</label>
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
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Max Spieler</label>
                            <input type="number" name="max_players" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" value="32">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Betriebssystem des Servers</label>
                            <select name="os_type" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="windows">Windows Server (.exe)</option>
                                <option value="linux">Linux Server (ELF binary / systemd)</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Verbindungstyp</label>
                            <select name="connection_type" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="local">Lokal auf diesem Host ausführen</option>
                                <option value="ssh">Remote via SSH steuern</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Start-Parameter (Windows & Linux)</label>
                        <input type="text" name="custom_parameters" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="--port 7777 --world world_01 --max-players 32 --pvp false" value="--port 7777 --world world_01 --max-players 32">
                        <small style="color: #64748b; font-size: 11px;">Diese Argumente werden beim Serverstart an das Binary übergeben.</small>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Pfad zur Server-Ausführungsdatei</label>
                        <input type="text" name="executable_path" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="C:\Games\server.exe oder /opt/gameserver/server">
                    </div>

                    <fieldset style="border: 1px dashed #cbd5e1; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px;">
                        <legend style="font-size: 12px; font-weight: 700; color: #1e3a8a;">SSH Remote-Zugangsdaten (optional für externe Server)</legend>
                        <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                            <div style="flex: 2;">
                                <input type="text" name="ssh_host" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="SSH Host / IP">
                            </div>
                            <div style="flex: 1;">
                                <input type="number" name="ssh_port" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="Port" value="22">
                            </div>
                            <div style="flex: 1;">
                                <input type="text" name="ssh_user" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="Benutzer (root)">
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <input type="password" name="ssh_pass" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="SSH Passwort (oder leer)">
                            </div>
                            <div style="flex: 1;">
                                <input type="text" name="ssh_key" class="form-control" style="width: 100%; padding: 5px; font-size: 12px;" placeholder="Privater Schlüssel Pfad (~/.ssh/id_rsa)">
                            </div>
                        </div>
                    </fieldset>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" onclick="document.getElementById('modalAddServer').style.display='none';" class="btn btn-light" style="padding: 6px 14px;">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 18px; font-weight: 700;">Server speichern</button>
                    </div>
                </form>
            </div>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 2: CLIENT-ROLLOUT & UPDATES                                    -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'rollout'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">Client-Releases & Update-Verteilung</h4>
            <button type="button" onclick="document.getElementById('modalAddRelease').style.display='flex';" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
                + Release / Update hinzufügen
            </button>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 700px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;">Spiel</th>
                        <th style="padding: 10px 14px;">Version</th>
                        <th style="padding: 10px 14px;">Channel / Plattform</th>
                        <th style="padding: 10px 14px;">Download & Größe</th>
                        <th style="padding: 10px 14px;">SHA256 Prüfsumme</th>
                        <th style="padding: 10px 14px; text-align: right;">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($releases)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                Noch keine Client-Releases veröffentlicht.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($releases as $r): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px; font-weight: 700;"><?= htmlspecialchars($r['game_slug']) ?></td>
                                <td style="padding: 12px 14px;">
                                    <span style="background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">v<?= htmlspecialchars($r['version']) ?></span>
                                    <?php if (!empty($r['mandatory'])): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 700;">Pflicht</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <?= htmlspecialchars($r['channel']) ?> / <code><?= htmlspecialchars($r['platform']) ?></code>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <a href="../<?= htmlspecialchars($r['download_url']) ?>" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 600;">Paket-Download</a>
                                    <span style="color: #64748b; font-size: 11px;">(<?= htmlspecialchars($r['file_size']) ?>)</span>
                                </td>
                                <td style="padding: 12px 14px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($r['sha256']) ?>">
                                    <code style="font-size: 10px;"><?= htmlspecialchars($r['sha256']) ?></code>
                                </td>
                                <td style="padding: 12px 14px; text-align: right;">
                                    <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" style="margin: 0;" onsubmit="return confirm('Release löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="game_release_delete">
                                        <input type="hidden" name="release_id" value="<?= htmlspecialchars($r['id']) ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 11px; border-radius: 4px;">Löschen</button>
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
                    <strong style="font-size: 15px;">🚀 Neues Client-Release veröffentlichen</strong>
                    <button type="button" onclick="document.getElementById('modalAddRelease').style.display='none';" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=rollout#tab-plugins" style="padding: 18px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="game_release_save">

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Spiel *</label>
                            <select name="game_slug" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <?php foreach ($games as $g): ?>
                                    <option value="<?= htmlspecialchars($g['slug']) ?>"><?= htmlspecialchars($g['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Version (SemVer) *</label>
                            <input type="text" name="version" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="z. B. 1.0.0">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Kanal</label>
                            <select name="channel" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="stable">Stable (Offiziell)</option>
                                <option value="beta">Beta (Testkanal)</option>
                                <option value="nightly">Nightly (Entwicklungsstand)</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Ziel-Plattform</label>
                            <select name="platform" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                <option value="windows_x64">Windows (x64 .zip / .exe)</option>
                                <option value="linux_x64">Linux (x64 tar.gz)</option>
                                <option value="macos">macOS</option>
                                <option value="all">Alle Plattformen</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Download-URL / Dateipfad *</label>
                        <input type="text" name="download_url" required class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="downloads/games/client-v1.0.0.zip">
                    </div>

                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Dateigröße</label>
                            <input type="text" name="file_size" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="z. B. 52.4 MB">
                        </div>
                        <div style="flex: 2;">
                            <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">SHA256 Prüfsumme</label>
                            <input type="text" name="sha256" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="Prüfsumme für automatische Client-Verifikation">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Patch-Notes / Changelog</label>
                        <textarea name="patch_notes" rows="3" class="form-control" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="- Neue Unterreich Höhlen&#10;- Fehlerbehebung Licht/Fackeln"></textarea>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mandatory" value="1">
                            <strong>Pflicht-Update:</strong> Clients müssen diese Version laden, um sich mit dem Server verbinden zu dürfen.
                        </label>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" onclick="document.getElementById('modalAddRelease').style.display='none';" class="btn btn-light" style="padding: 6px 14px;">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 18px; font-weight: 700;">Release veröffentlichen</button>
                    </div>
                </form>
            </div>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 3: SPIELER & ACCOUNTS                                          -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'players'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h4 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">Spieler-Accounts & Online-Registrierungen</h4>
            <span style="font-size: 12px; color: #64748b;">Gesamt: <?= count($players) ?> Spieler</span>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; min-width: 650px;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th style="padding: 10px 14px;">Spieler / Nickname</th>
                        <th style="padding: 10px 14px;">E-Mail</th>
                        <th style="padding: 10px 14px;">Rolle</th>
                        <th style="padding: 10px 14px;">Status</th>
                        <th style="padding: 10px 14px;">Letztes Login</th>
                        <th style="padding: 10px 14px; text-align: right;">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($players)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                Noch keine Spieler registriert.
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
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;">Gesperrt</span>
                                    <?php else: ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;">Aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; font-size: 12px; color: #64748b;"><?= htmlspecialchars($p['last_login'] ?? 'Nie') ?></td>
                                <td style="padding: 12px 14px; text-align: right;">
                                    <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=players#tab-plugins" style="display: inline;" onsubmit="return confirm('Spieler wirklich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="game_player_delete">
                                        <input type="hidden" name="player_id" value="<?= htmlspecialchars($p['id']) ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; font-size: 11px; border-radius: 4px;">Löschen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 4: SPIELE-KATALOG                                              -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'games'): ?>
        <h4 style="margin: 0 0 14px 0; color: #1e293b; font-size: 16px; font-weight: 700;">Verwaltete Spiele & Titel</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
            <?php foreach ($games as $g): ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 24px;">🎮</span>
                        <div>
                            <strong style="font-size: 15px; color: #1e3a8a;"><?= htmlspecialchars($g['title']) ?></strong><br>
                            <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($g['genre'] ?? 'Game') ?> | Typ: <?= htmlspecialchars($g['category']) ?></span>
                        </div>
                    </div>
                    <p style="font-size: 12px; color: #475569; margin: 8px 0 12px 0; line-height: 1.4;">
                        <?= htmlspecialchars($g['description']) ?>
                    </p>
                    <div style="font-size: 11px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        Slug: <code><?= htmlspecialchars($g['slug']) ?></code>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 5: GLOBALE EINSTELLUNGEN                                       -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubtab === 'settings'): ?>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
            <h4 style="margin-top: 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">⚙️ Allgemeine Gaming-Einstellungen</h4>

            <form method="POST" action="?tab=tab-plugins&plugin_settings=weave-games&game_subtab=settings#tab-plugins">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="game_save_config">

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="show_in_main_nav" value="1" <?= !empty($cfg['show_in_main_nav']) ? 'checked' : '' ?>>
                        <strong>Im Hauptmenü der Website anzeigen</strong>
                    </label>
                    <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                        Steuert, ob der Menüpunkt „Gaming / Games“ in der Hauptnavigation angezeigt wird.
                    </p>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="allow_self_registration" value="1" <?= !empty($cfg['allow_self_registration']) ? 'checked' : '' ?>>
                        <strong>Online-Registrierung für Spieler im Webportal & Client erlauben</strong>
                    </label>
                    <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                        Ermöglicht neuen Spielern, sich direkt über die Website oder den Game Launcher zu registrieren.
                    </p>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="enable_public_server_status" value="1" <?= !empty($cfg['enable_public_server_status']) ? 'checked' : '' ?>>
                        <strong>Öffentlichen Server-Status im Spieler-Portal anzeigen</strong>
                    </label>
                    <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                        Zeigt Spielern live an, welche Server online sind und wie hoch die Latenz (Ping) ist.
                    </p>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Sektionstitel</label>
                    <input type="text" name="section_title" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars($cfg['section_title'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">REST API-Schlüssel für Server-Kommunikation</label>
                    <input type="text" readonly class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; font-family: monospace;" value="<?= htmlspecialchars($cfg['api_key'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-weight: 700;">Einstellungen speichern</button>
            </form>
        </div>
    <?php endif; ?>

</div>

