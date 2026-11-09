<?php
/**
 * ============================================================================
 * Weave Games – Spieler- & Gaming-Portal (views/portal.php)
 * ============================================================================
 */

$player = GameUserManager::getCurrentPlayer();
$cfg = GameConfigManager::getConfig();
$games = GameRolloutManager::getGames();
$servers = GameServerManager::getServers();
$mode = $_GET['mode'] ?? '';
$authError = $_SESSION['game_auth_error'] ?? null;
unset($_SESSION['game_auth_error']);

// Server statuses auffrischen
GameServerManager::refreshAllServerStatuses();
$servers = GameServerManager::getServers();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cfg['section_title']) ?> – Weave Games</title>
    <link rel="stylesheet" href="plugins/weave-games/assets/css/games.css">
    <script src="plugins/weave-games/assets/js/games.js" defer></script>
</head>
<body class="wg-portal-body">

<!-- Header -->
<header class="wg-header">
    <div class="wg-container wg-header-inner">
        <a href="?page=games" class="wg-brand">
            <span class="wg-brand-icon">🎮</span>
            <span>Weave Games</span>
        </a>

        <div class="wg-header-nav">
            <a href="./" class="wg-nav-link">🌐 Zurück zur Website</a>
            
            <?php if ($player): ?>
                <div class="wg-user-badge">
                    <span>👤</span>
                    <strong><?= htmlspecialchars($player['nickname'] ?? $player['username']) ?></strong>
                    <span class="wg-role-tag"><?= htmlspecialchars($player['role']) ?></span>
                    <a href="?page=games&action=logout" class="wg-logout-btn" title="Abmelden">🚪</a>
                </div>
            <?php else: ?>
                <a href="?page=games&mode=login" class="wg-btn wg-btn-outline">Anmelden</a>
                <?php if (!empty($cfg['allow_self_registration'])): ?>
                    <a href="?page=games&mode=register" class="wg-btn wg-btn-primary">Registrieren</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Auth Modal (Login / Register) -->
<?php if ($mode === 'login' || $mode === 'register'): ?>
    <div class="wg-modal-overlay">
        <div class="wg-modal-card">
            <div class="wg-modal-header">
                <h3><?= ($mode === 'login') ? '🔐 Spieler-Login' : '✨ Neuer Spieler-Account' ?></h3>
                <a href="?page=games" class="wg-close-btn">&times;</a>
            </div>

            <?php if ($authError): ?>
                <div class="wg-alert-error"><?= htmlspecialchars($authError) ?></div>
            <?php endif; ?>

            <?php if ($mode === 'login'): ?>
                <form method="POST" action="?page=games&action=login" class="wg-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="wg-form-group">
                        <label>Benutzername oder E-Mail</label>
                        <input type="text" name="username" required autofocus placeholder="Dein Spielername">
                    </div>
                    <div class="wg-form-group">
                        <label>Passwort</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="wg-btn wg-btn-primary wg-btn-full">Einloggen</button>
                    <?php if (!empty($cfg['allow_self_registration'])): ?>
                        <div class="wg-auth-switch">Noch kein Account? <a href="?page=games&mode=register">Jetzt registrieren</a></div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <form method="POST" action="?page=games&action=register" class="wg-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="wg-form-group">
                        <label>Benutzername *</label>
                        <input type="text" name="username" required autofocus placeholder="z. B. VoxelHero">
                    </div>
                    <div class="wg-form-group">
                        <label>In-Game Nickname</label>
                        <input type="text" name="nickname" placeholder="Dein Anzeigename im Spiel">
                    </div>
                    <div class="wg-form-group">
                        <label>E-Mail-Adresse *</label>
                        <input type="email" name="email" required placeholder="spieler@example.com">
                    </div>
                    <div class="wg-form-group">
                        <label>Passwort * (mind. 6 Zeichen)</label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••••">
                    </div>
                    <button type="submit" class="wg-btn wg-btn-primary wg-btn-full">Account erstellen</button>
                    <div class="wg-auth-switch">Bereits registriert? <a href="?page=games&mode=login">Zum Login</a></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content -->
<main class="wg-container wg-main">

    <!-- Hero Banner -->
    <section class="wg-hero">
        <div class="wg-hero-content">
            <span class="wg-badge">Server / Client Gaming Platform</span>
            <h1><?= htmlspecialchars($cfg['section_title']) ?></h1>
            <p><?= htmlspecialchars($cfg['section_subtitle']) ?></p>
        </div>
    </section>

    <!-- Spieler-Token Karte (falls eingeloggt) -->
    <?php if ($player): ?>
        <section class="wg-player-panel">
            <div class="wg-player-panel-inner">
                <div>
                    <h3 style="margin: 0; color: #38bdf8;">🎮 Angemeldet als: <?= htmlspecialchars($player['nickname']) ?></h3>
                    <p style="margin: 4px 0 0 0; color: #94a3b8; font-size: 13px;">
                        Verwende diesen Token in deinem Game-Client (Game Launcher / Config) für automatisches Login:
                    </p>
                </div>
                <div class="wg-token-box">
                    <code id="wgPlayerToken"><?= htmlspecialchars($player['auth_token']) ?></code>
                    <button type="button" class="wg-btn-copy" onclick="navigator.clipboard.writeText(document.getElementById('wgPlayerToken').innerText); alert('Token kopiert!');">Kopieren</button>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Spiele-Sektion 1: Multiplayer Voxel World (Server / Client) -->
    <section class="wg-section">
        <h2 class="wg-section-title">
            <span>🏰</span>
            <span>Voxel Adventure (Server/Client)</span>
        </h2>

        <div class="wg-game-card">
            <div class="wg-game-body">
                <div class="wg-game-details">
                    <h3>Voxel Adventure World</h3>
                    <p class="wg-game-desc">
                        Multiplayer Voxel Sandbox RPG mit vollständiger Server/Client-Architektur, prozedural generierten Welten und optimiertem Netzwerkprotokoll.
                    </p>

                    <!-- Client-Download Info -->
                    <?php 
                    $latestRel = GameRolloutManager::getLatestRelease('voxel-adventure', 'windows_x64', 'stable');
                    ?>
                    <div class="wg-release-info">
                        <strong>Neueste Client-Version:</strong>
                        <?php if ($latestRel): ?>
                            <span class="wg-version-pill">v<?= htmlspecialchars($latestRel['version']) ?></span>
                            <span class="wg-file-size">(<?= htmlspecialchars($latestRel['file_size']) ?>)</span>
                        <?php else: ?>
                            <span>Kein Release hinterlegt</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($latestRel): ?>
                        <div class="wg-game-actions">
                            <a href="<?= htmlspecialchars($latestRel['download_url']) ?>" class="wg-btn wg-btn-primary wg-btn-large">
                                ⬇️ Game-Client herunterladen (<?= htmlspecialchars($latestRel['version']) ?>)
                            </a>
                            <button type="button" class="wg-btn wg-btn-outline" onclick="alert(<?= json_encode($latestRel['patch_notes']) ?>)">
                                📋 Patch-Notes
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Server-Status für Voxel Adventure -->
                <div class="wg-server-box">
                    <h4 style="margin: 0 0 10px 0; color: #f8fafc; font-size: 14px;">📡 Live Server-Status</h4>
                    <?php 
                    $voxelServers = array_filter($servers, fn($s) => $s['game_slug'] === 'voxel-adventure' && !empty($s['active']));
                    ?>
                    <?php if (empty($voxelServers)): ?>
                        <div style="color: #94a3b8; font-size: 12px;">Keine aktiven Game-Server eingetragen.</div>
                    <?php else: ?>
                        <?php foreach ($voxelServers as $fs): 
                            $isOnline = (($fs['status'] ?? '') === 'online');
                        ?>
                            <div class="wg-server-item">
                                <div class="wg-server-info">
                                    <span class="wg-status-dot <?= $isOnline ? 'dot-online' : 'dot-offline' ?>"></span>
                                    <strong><?= htmlspecialchars($fs['name']) ?></strong>
                                </div>
                                <div class="wg-server-meta">
                                    <code><?= htmlspecialchars($fs['host']) ?>:<?= (int)$fs['port'] ?></code>
                                    <span class="wg-ping-badge"><?= $isOnline ? ($fs['last_ping_ms'] . ' ms') : 'Offline' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Spiele-Sektion 2: Browsergames (HTML5 / WebGL) -->
    <section class="wg-section">
        <h2 class="wg-section-title">
            <span>🚀</span>
            <span>Browsergames (Direkt im Web spielbar)</span>
        </h2>

        <div class="wg-browser-game-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div>
                    <h3 style="margin: 0; color: #38bdf8;">Space Orbit – 2D Arcade Space Shooter</h3>
                    <p style="margin: 4px 0 0 0; color: #94a3b8; font-size: 13px;">Kein Download erforderlich – läuft nativ im Browser per HTML5 Canvas.</p>
                </div>
                <div>
                    <button type="button" class="wg-btn wg-btn-primary" onclick="initSpaceOrbitGame()">▶ Spiel starten</button>
                </div>
            </div>

            <!-- Canvas Container -->
            <div id="spaceOrbitContainer" style="width: 100%; height: 360px; background: #020617; border-radius: 8px; border: 1px solid #334155; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                <canvas id="spaceOrbitCanvas" width="800" height="360" style="display: none; width: 100%; height: 100%; cursor: crosshair;"></canvas>
                <div id="spaceOrbitIntro" style="text-align: center; color: #94a3b8;">
                    <span style="font-size: 48px; display: block; margin-bottom: 8px;">🛸</span>
                    <strong style="color: #f8fafc; font-size: 16px;">Space Orbit Arcade</strong><br>
                    <span style="font-size: 13px;">Steuerung: Maus / Cursor (Bewegen & Zielen), Klick oder Leertaste (Feuern) &bull; Alternativ: Pfeiltasten / A & D</span><br><br>
                    <button type="button" class="wg-btn wg-btn-primary" onclick="initSpaceOrbitGame()">Spiel jetzt laden & starten</button>
                </div>
            </div>
        </div>
    </section>

</main>

<footer class="wg-footer">
    <div class="wg-container">
        <p>&copy; <?= date('Y') ?> Weave Games &bull; Angetrieben von WeaveCMS</p>
    </div>
</footer>

</body>
</html>

