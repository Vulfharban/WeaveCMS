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

// CMS Design-Variablen aus data.json übernehmen (helles/dunkles Theme & Primärfarben)
$cmsData = $GLOBALS['data'] ?? ($data ?? []);
$designSettings = $cmsData['settings']['design'] ?? [];
$cmsBgColor = $designSettings['bg_color'] ?? '#fbf9f6';
$cmsPrimaryColor = $designSettings['primary_color'] ?? '#2e6248';
$cmsAccentColor = $designSettings['accent_color'] ?? '#b26c39';
$cmsContainerWidth = $designSettings['container_width'] ?? '1200px';
$cmsFontScale = $designSettings['font_scale'] ?? '100%';
$cmsCardRadius = $designSettings['card_radius'] ?? '16px';
$cmsThemeMode = $cmsData['settings']['theme_mode'] ?? 'light';
$t = static fn(string $key, string $default, array $params = []): string => function_exists('__')
    ? __($key, $default, null, $params)
    : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);
$htmlLang = function_exists('getCurrentLang') ? (string)getCurrentLang() : 'de';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>"<?= ($cmsThemeMode === 'dark') ? ' data-theme="dark"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cfg['section_title']) ?> – Weave Games</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="libs/css/styles.css?v=<?= file_exists(dirname(__DIR__, 3) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 3) . '/libs/css/styles.css') : time() ?>">
    <style id="dynamic-design-variables">
        :root {
            --bg-dark: <?= htmlspecialchars($cmsBgColor, ENT_QUOTES, 'UTF-8') ?>;
            --primary: <?= htmlspecialchars($cmsPrimaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --accent: <?= htmlspecialchars($cmsAccentColor, ENT_QUOTES, 'UTF-8') ?>;
            --container-max-width: <?= htmlspecialchars($cmsContainerWidth, ENT_QUOTES, 'UTF-8') ?>;
            --font-scale: <?= htmlspecialchars($cmsFontScale, ENT_QUOTES, 'UTF-8') ?>;
            --card-radius: <?= htmlspecialchars($cmsCardRadius, ENT_QUOTES, 'UTF-8') ?>;
        }
        [data-theme="dark"] {
            --bg-dark: #0f0d0b;
            --bg-card: rgba(22, 19, 17, 0.95);
            --border-color: rgba(255, 255, 255, 0.12);
            --text-primary: #f8f6f4;
            --text-secondary: #c5b8ac;
            --primary: #4ade80;
            --accent: #f09a5c;
            --header-bg: rgba(15, 13, 11, 0.92);
        }
    </style>
    <script>
        (function() {
            try {
                var t = localStorage.getItem('weave_user_theme');
                if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
                else if (t === 'light') document.documentElement.removeAttribute('data-theme');
                var z = localStorage.getItem('weave_user_zoom');
                if (z && parseInt(z, 10) >= 85 && parseInt(z, 10) <= 125) {
                    document.documentElement.style.fontSize = z + '%';
                }
            } catch(e) {}
        })();
    </script>
    <link rel="stylesheet" href="plugins/weave-games/assets/css/games.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/games.css') ?>">
    <script src="plugins/weave-games/assets/js/games.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/games.js') ?>" defer></script>
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
            <a href="./" class="wg-nav-link">🌐 <?= htmlspecialchars($t('games.back_to_site', 'Zurück zur Website')) ?></a>
            
            <?php if ($player): ?>
                <div class="wg-user-badge">
                    <span>👤</span>
                    <strong><?= htmlspecialchars($player['nickname'] ?? $player['username']) ?></strong>
                    <span class="wg-role-tag"><?= htmlspecialchars($player['role']) ?></span>
                    <a href="?page=games&action=logout" class="wg-logout-btn" title="<?= htmlspecialchars($t('games.logout', 'Abmelden')) ?>">🚪</a>
                </div>
            <?php else: ?>
                <a href="?page=games&mode=login" class="wg-btn wg-btn-outline"><?= htmlspecialchars($t('games.login_btn', 'Anmelden')) ?></a>
                <?php if (!empty($cfg['allow_self_registration'])): ?>
                    <a href="?page=games&mode=register" class="wg-btn wg-btn-primary"><?= htmlspecialchars($t('games.register_title', 'Registrieren')) ?></a>
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
                <h3><?= ($mode === 'login') ? '🔐 ' . htmlspecialchars($t('games.player_login', 'Spieler-Login')) : '✨ ' . htmlspecialchars($t('games.new_account', 'Neuer Spieler-Account')) ?></h3>
                <a href="?page=games" class="wg-close-btn">&times;</a>
            </div>

            <?php if ($authError): ?>
                <div class="wg-alert-error"><?= htmlspecialchars($authError) ?></div>
            <?php endif; ?>

            <?php if ($mode === 'login'): ?>
                <form method="POST" action="?page=games&action=login" class="wg-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.username_or_email', 'Benutzername oder E-Mail')) ?></label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.password', 'Passwort')) ?></label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="wg-btn wg-btn-primary wg-btn-full"><?= htmlspecialchars($t('games.login_btn', 'Einloggen')) ?></button>
                    <?php if (!empty($cfg['allow_self_registration'])): ?>
                        <div class="wg-auth-switch"><?= htmlspecialchars($t('games.no_account', 'Noch kein Account?')) ?> <a href="?page=games&mode=register"><?= htmlspecialchars($t('games.register_now', 'Jetzt registrieren')) ?></a></div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <form method="POST" action="?page=games&action=register" class="wg-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.username', 'Benutzername')) ?> *</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.ingame_nickname', 'In-Game Nickname')) ?></label>
                        <input type="text" name="nickname">
                    </div>
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.email', 'E-Mail')) ?> *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="wg-form-group">
                        <label><?= htmlspecialchars($t('games.password_min', 'Passwort (min. 6 Zeichen)')) ?> *</label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••••">
                    </div>
                    <button type="submit" class="wg-btn wg-btn-primary wg-btn-full"><?= htmlspecialchars($t('games.create_account', 'Account erstellen')) ?></button>
                    <div class="wg-auth-switch"><?= htmlspecialchars($t('games.already_registered', 'Bereits registriert?')) ?> <a href="?page=games&mode=login"><?= htmlspecialchars($t('games.login_btn', 'Einloggen')) ?></a></div>
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
                    <h3 style="margin: 0; color: var(--wg-primary);">🎮 <?= htmlspecialchars($t('games.logged_in_as', 'Angemeldet als:')) ?> <?= htmlspecialchars($player['nickname']) ?></h3>
                    <p style="margin: 4px 0 0 0; color: var(--wg-text-muted); font-size: 13px;">
                        <?= htmlspecialchars($t('games.auth_token_hint', 'Verwende diesen Auth-Token im Client oder für Multiplayer-Login:')) ?>
                    </p>
                </div>
                <div class="wg-token-box">
                    <code id="wgPlayerToken"><?= htmlspecialchars($player['auth_token']) ?></code>
                    <button type="button" class="wg-btn-copy" onclick="navigator.clipboard.writeText(document.getElementById('wgPlayerToken').innerText); alert(<?= htmlspecialchars(json_encode($t('games.token_copied', 'Token kopiert!')), ENT_QUOTES) ?>);"><?= htmlspecialchars($t('games.copy_btn', 'Kopieren')) ?></button>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $activeGames = array_values(array_filter($games, fn($g) => !empty($g['active'])));
    usort($activeGames, static fn($a, $b) => ((int)($a['sort_order'] ?? 50)) <=> ((int)($b['sort_order'] ?? 50)));
    $runtimeOptions = GameRolloutManager::getRuntimeOptions();
    $playSlug = trim((string)($_GET['play'] ?? ''));
    ?>

    <?php foreach ($activeGames as $g): ?>
        <?php
        $slug = (string)($g['slug'] ?? '');
        $rtKey = (string)($g['runtime_type'] ?? 'html5_js');
        $rtInfo = $runtimeOptions[$rtKey] ?? ['badge' => strtoupper($rtKey), 'color' => '#334155'];
        $tags = is_array($g['tech_tags'] ?? null) ? $g['tech_tags'] : [];
        $embedUrl = trim((string)($g['embed_url'] ?? ''));
        $runnerUrl = $embedUrl !== '' ? (!empty($g['enable_coop_coep']) ? ('?page=games&runner=' . urlencode($slug)) : $embedUrl) : '';
        $latestRel = GameRolloutManager::getLatestRelease($slug, 'windows_x64', 'stable');
        $gameServers = array_filter($servers, fn($s) => ($s['game_slug'] ?? '') === $slug && !empty($s['active']));
        $autoStart = ($playSlug !== '' && $playSlug === $slug);
        ?>
        <section class="wg-section" id="game-<?= htmlspecialchars($slug) ?>">
            <h2 class="wg-section-title">
                <span><?= htmlspecialchars(strlen((string)($g['icon'] ?? '')) <= 4 ? (string)$g['icon'] : '🎮') ?></span>
                <span><?= htmlspecialchars((string)$g['title']) ?></span>
                <span style="margin-left: 8px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; color: #fff; background: <?= htmlspecialchars($rtInfo['color']) ?>;">
                    <?= htmlspecialchars($rtInfo['badge']) ?>
                </span>
            </h2>

            <div class="wg-game-card" style="padding: 20px;">
                <?php if (!empty($g['banner'])): ?>
                    <div style="margin-bottom: 16px; border-radius: 10px; overflow: hidden; max-height: 280px;">
                        <img src="<?= htmlspecialchars((string)$g['banner']) ?>" alt="<?= htmlspecialchars((string)$g['title']) ?>" style="width: 100%; height: 240px; object-fit: cover; display: block;">
                    </div>
                <?php endif; ?>

                <div class="wg-game-body" style="<?= (empty($gameServers) && $embedUrl === '' && $slug !== 'space-orbit') ? 'display:block;' : '' ?>">
                    <div class="wg-game-details">
                        <?php if (!empty($g['genre'])): ?>
                            <div style="font-size: 12px; font-weight: 700; color: var(--primary, #2e6248); margin-bottom: 4px;"><?= htmlspecialchars((string)$g['genre']) ?></div>
                        <?php endif; ?>
                        <p class="wg-game-desc"><?= htmlspecialchars((string)$g['description']) ?></p>

                        <?php if (!empty($tags)): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                                <?php foreach ($tags as $tg): ?>
                                    <span style="padding: 2px 9px; border-radius: 6px; font-size: 11px; font-weight: 600; background: rgba(100,116,139,0.14);"><?= htmlspecialchars((string)$tg) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($g['system_requirements'])): ?>
                            <div style="font-size: 12px; color: var(--wg-text-muted); margin-bottom: 12px;">
                                <strong><?= htmlspecialchars($t('games.system_requirements', 'Systemanforderungen:')) ?></strong> <?= htmlspecialchars((string)$g['system_requirements']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($latestRel): ?>
                            <div class="wg-release-info">
                                <strong><?= htmlspecialchars($t('games.latest_client_version', 'Neueste Client-Version:')) ?></strong>
                                <span class="wg-version-pill">v<?= htmlspecialchars((string)$latestRel['version']) ?></span>
                                <?php if (!empty($latestRel['file_size'])): ?>
                                    <span class="wg-file-size">(<?= htmlspecialchars((string)$latestRel['file_size']) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="wg-game-actions">
                                <a href="<?= htmlspecialchars((string)$latestRel['download_url']) ?>" class="wg-btn wg-btn-primary wg-btn-large">
                                    ⬇️ <?= htmlspecialchars($t('games.download_client', 'Client herunterladen')) ?> (<?= htmlspecialchars((string)$latestRel['version']) ?>)
                                </a>
                                <?php if (!empty($latestRel['patch_notes'])): ?>
                                    <button type="button" class="wg-btn wg-btn-outline" onclick="alert(<?= htmlspecialchars(json_encode((string)$latestRel['patch_notes']), ENT_QUOTES) ?>)">
                                        📋 <?= htmlspecialchars($t('games.patch_notes', 'Patch-Notes')) ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($gameServers) && !empty($cfg['enable_public_server_status'])): ?>
                        <div class="wg-server-box">
                            <h4 style="margin: 0 0 10px 0; color: var(--wg-text); font-size: 14px;">📡 <?= htmlspecialchars($t('games.live_server_status', 'Live Server-Status')) ?></h4>
                            <?php foreach ($gameServers as $fs):
                                $isOnline = (($fs['status'] ?? '') === 'online');
                            ?>
                                <div class="wg-server-item">
                                    <div class="wg-server-info">
                                        <span class="wg-status-dot <?= $isOnline ? 'dot-online' : 'dot-offline' ?>"></span>
                                        <strong><?= htmlspecialchars((string)$fs['name']) ?></strong>
                                    </div>
                                    <div class="wg-server-meta">
                                        <code><?= htmlspecialchars((string)$fs['host']) ?>:<?= (int)$fs['port'] ?></code>
                                        <span class="wg-ping-badge"><?= $isOnline ? ($fs['last_ping_ms'] . ' ms') : htmlspecialchars($t('games.offline', 'Offline')) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($embedUrl !== ''): ?>
                    <?php $frameId = 'wgFrame_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $slug); ?>
                    <div style="margin-top: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px; flex-wrap: wrap;">
                            <span style="font-size: 12px; color: var(--wg-text-muted);">
                                <?= htmlspecialchars($t('games.playable_in_browser', 'Direkt im Browser ausführbar')) ?> (<?= htmlspecialchars($rtInfo['label']) ?><?= !empty($g['enable_coop_coep']) ? ' • COOP/COEP Multi-Thread' : '' ?>)
                            </span>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="wg-btn wg-btn-primary" onclick="var f=document.getElementById('<?= $frameId ?>'); f.src=<?= htmlspecialchars(json_encode($runnerUrl), ENT_QUOTES) ?>; f.style.display='block';">
                                    ▶ <?= htmlspecialchars($t('games.start_reload', 'Jetzt starten / Neu laden')) ?>
                                </button>
                                <button type="button" class="wg-btn wg-btn-outline" onclick="var f=document.getElementById('<?= $frameId ?>'); if(f && f.requestFullscreen) f.requestFullscreen();">
                                    ⛶ <?= htmlspecialchars($t('games.fullscreen', 'Vollbild')) ?>
                                </button>
                                <a href="<?= htmlspecialchars($runnerUrl) ?>" target="_blank" rel="noopener noreferrer" class="wg-btn wg-btn-outline">↗ Tab</a>
                            </div>
                        </div>
                        <iframe id="<?= $frameId ?>"
                                src="<?= $autoStart ? htmlspecialchars($runnerUrl) : 'about:blank' ?>"
                                style="width: 100%; height: 540px; border: 1px solid var(--wg-border); border-radius: 10px; background: #020617; display: <?= $autoStart ? 'block' : 'none' ?>;"
                                allow="fullscreen; gamepad; pointer-lock; autoplay"
                                sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-popups allow-forms"
                                allowfullscreen></iframe>
                    </div>
                <?php elseif ($slug === 'space-orbit'): ?>
                    <div class="wg-browser-game-card" style="margin-top: 16px; border: none; padding: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="color: var(--wg-text-muted); font-size: 13px;">5 Kampagnen-Level mit zerstörbaren Rand-Geschützen, Waffen-Upgrades und 2-Phasen-Bossen.</span>
                            <button type="button" class="wg-btn wg-btn-primary" onclick="initSpaceOrbitGame()">▶ Kampagne starten</button>
                        </div>
                        <div id="spaceOrbitContainer" style="width: 100%; height: 460px; background: #020617; border-radius: 8px; border: 1px solid var(--wg-border); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                            <canvas id="spaceOrbitCanvas" width="800" height="460" style="display: none; width: 100%; height: 100%; cursor: crosshair;"></canvas>
                            <div id="spaceOrbitIntro" style="text-align: center; color: #94a3b8; padding: 16px;">
                                <span style="font-size: 48px; display: block; margin-bottom: 8px;">🛸</span>
                                <strong style="color: #f8fafc; font-size: 16px;">Space Orbit – 5-Level Boss-Kampagne</strong><br>
                                <span style="font-size: 13px;">Steuerung: Maus / Touch oder WASD / Pfeiltasten &bull; Schießen: Klick oder Leertaste</span><br><br>
                                <button type="button" class="wg-btn wg-btn-primary" onclick="initSpaceOrbitGame()">Kampagne jetzt starten</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>

</main>

<footer class="wg-footer">
    <div class="wg-container">
        <p>&copy; <?= date('Y') ?> Weave Games &bull; <?= htmlspecialchars($t('games.powered_by', 'Angetrieben von WeaveCMS')) ?></p>
    </div>
</footer>

</body>
</html>

