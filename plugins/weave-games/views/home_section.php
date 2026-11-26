<?php
/**
 * ============================================================================
 * Weave Games – Startseiten- & Sektions-Darstellung (views/home_section.php)
 * ============================================================================
 * Konfigurierbares Layout im Stil der Bildergalerie (ambient_blur, cards,
 * showcase, list), mit Kategorie-Filtern, Runtime/Tech-Badges und
 * integriertem Lightbox-Spieleplayer für JS / HTML5 / WASM / Web-Apps.
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

$cfg = GameConfigManager::getConfig();
$allGames = GameRolloutManager::getGames();
$servers = GameServerManager::getServers();
$runtimes = GameRolloutManager::getRuntimeOptions();
$categoriesMap = GameRolloutManager::getCategoryOptions();

// Nur aktive & für die Startseite vorgesehene Titel auswählen
$homeGames = [];
foreach ($allGames as $g) {
    if (empty($g['active'])) {
        continue;
    }
    if (array_key_exists('show_on_home', $g) && empty($g['show_on_home'])) {
        continue;
    }
    $homeGames[] = $g;
}

// Sortierung anwenden (manual / newest / alpha)
$sortMode = $cfg['home_sort'] ?? 'manual';
if ($sortMode === 'alpha') {
    usort($homeGames, static fn($a, $b) => strnatcasecmp((string)($a['title'] ?? ''), (string)($b['title'] ?? '')));
} elseif ($sortMode === 'newest') {
    usort($homeGames, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
} else {
    usort($homeGames, static function ($a, $b) {
        $fa = !empty($a['featured']) ? 0 : 1;
        $fb = !empty($b['featured']) ? 0 : 1;
        if ($fa !== $fb) {
            return $fa <=> $fb;
        }
        return ((int)($a['sort_order'] ?? 50)) <=> ((int)($b['sort_order'] ?? 50));
    });
}

$maxItems = (int)($cfg['home_max_items'] ?? 6);
if ($maxItems > 0 && count($homeGames) > $maxItems) {
    $homeGames = array_slice($homeGames, 0, $maxItems);
}

$layout = $cfg['home_layout'] ?? 'ambient_blur';
if (!in_array($layout, ['ambient_blur', 'cards', 'showcase', 'list'], true)) {
    $layout = 'ambient_blur';
}
$cols = (int)($cfg['home_columns'] ?? 3);
if (!in_array($cols, [2, 3, 4], true)) {
    $cols = 3;
}
$showFilters = !empty($cfg['home_show_filters']);
$usePlayModal = !empty($cfg['home_play_modal']);
$showServerStatus = !empty($cfg['home_show_server_status']) && !empty($cfg['enable_public_server_status']);

// Vorhandene Kategorien für Filter-Buttons sammeln
$presentCategories = [];
foreach ($homeGames as $g) {
    $c = (string)($g['category'] ?? 'browser');
    if (!in_array($c, $presentCategories, true)) {
        $presentCategories[] = $c;
    }
}

$onlineServerCount = 0;
foreach ($servers as $srv) {
    if (($srv['status'] ?? '') === 'online' && !empty($srv['active'])) {
        $onlineServerCount++;
    }
}
?>

<style>
.wg-home-section {
    padding: 2.5rem 1rem;
    max-width: var(--container-max-width, 1200px);
    margin: 0 auto;
}
.wg-home-header {
    text-align: center;
    margin-bottom: 1.75rem;
}
.wg-home-header h2 {
    margin: 0 0 0.5rem 0;
    color: var(--primary, #2e6248);
    font-size: 2rem;
    font-weight: 800;
}
.wg-home-header p {
    margin: 0 auto;
    max-width: 680px;
    color: var(--text-secondary, #64748b);
    font-size: 1rem;
}
.wg-home-filters {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 1.75rem;
}
.wg-home-filter-btn {
    border: 1px solid var(--border-color, rgba(62,50,40,0.16));
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #1e293b);
    padding: 7px 15px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.wg-home-filter-btn:hover,
.wg-home-filter-btn.active {
    background: var(--primary, #2e6248);
    border-color: var(--primary, #2e6248);
    color: #ffffff;
}
.wg-home-grid {
    display: grid;
    grid-template-columns: repeat(<?= $cols ?>, minmax(0, 1fr));
    gap: 1.4rem;
}
.wg-home-grid.layout-list {
    grid-template-columns: 1fr;
    gap: 1rem;
}
@media (max-width: 960px) {
    .wg-home-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 640px) {
    .wg-home-grid {
        grid-template-columns: 1fr;
    }
}
.wg-home-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, rgba(62,50,40,0.14));
    border-radius: var(--card-radius, 16px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.wg-home-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.09);
}
.wg-home-grid.layout-showcase .wg-home-card.is-showcase-lead {
    grid-column: 1 / -1;
    flex-direction: row;
}
@media (max-width: 780px) {
    .wg-home-grid.layout-showcase .wg-home-card.is-showcase-lead {
        flex-direction: column;
    }
}
.wg-home-grid.layout-list .wg-home-card {
    flex-direction: row;
    align-items: stretch;
}
@media (max-width: 680px) {
    .wg-home-grid.layout-list .wg-home-card {
        flex-direction: column;
    }
}
.wg-home-media {
    position: relative;
    height: 210px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.wg-home-grid.layout-showcase .wg-home-card.is-showcase-lead .wg-home-media {
    width: 45%;
    min-height: 270px;
    height: auto;
}
.wg-home-grid.layout-list .wg-home-media {
    width: 220px;
    height: auto;
    min-height: 155px;
}
@media (max-width: 780px) {
    .wg-home-grid.layout-showcase .wg-home-card.is-showcase-lead .wg-home-media,
    .wg-home-grid.layout-list .wg-home-media {
        width: 100%;
        height: 190px;
    }
}
.wg-home-media img.wg-bg-blur {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: blur(18px) brightness(0.65);
    transform: scale(1.15);
}
.wg-home-media img.wg-fg-img {
    position: relative;
    z-index: 2;
    max-width: 100%;
    max-height: 100%;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.wg-home-media.is-ambient img.wg-fg-img {
    object-fit: contain;
    padding: 8px;
}
.wg-home-media-fallback {
    position: relative;
    z-index: 2;
    text-align: center;
    color: #e2e8f0;
    padding: 16px;
}
.wg-home-badges-top {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    z-index: 3;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
    pointer-events: none;
}
.wg-runtime-pill {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}
.wg-home-body {
    padding: 1.15rem 1.25rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 0.65rem;
}
.wg-home-title {
    margin: 0;
    font-size: 1.18rem;
    font-weight: 800;
    color: var(--text-primary, #1e293b);
}
.wg-home-desc {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary, #64748b);
    line-height: 1.5;
    flex: 1;
}
.wg-home-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.wg-home-tag {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(100, 116, 139, 0.12);
    color: var(--text-secondary, #475569);
}
.wg-home-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--border-color, rgba(62,50,40,0.1));
}
.wg-home-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 13px;
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
}
.wg-home-btn-play {
    background: var(--primary, #2e6248);
    color: #ffffff;
}
.wg-home-btn-dl {
    background: var(--accent, #b26c39);
    color: #ffffff;
}
.wg-home-btn-sec {
    background: transparent;
    border-color: var(--border-color, rgba(62,50,40,0.2));
    color: var(--text-primary, #1e293b);
}
/* Modal Player */
.wg-home-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.85);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 18px;
}
.wg-home-modal-backdrop.show {
    display: flex;
}
.wg-home-modal-box {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 14px;
    width: 100%;
    max-width: 1060px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.6);
    display: flex;
    flex-direction: column;
}
.wg-home-modal-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #1e293b;
    color: #f8fafc;
    gap: 10px;
}
</style>

<section id="games" class="wg-home-section">
    <div class="wg-home-header">
        <h2>🎮 <?= htmlspecialchars((string)($cfg['section_title'] ?? 'Spiele & Anwendungen')) ?></h2>
        <?php if (!empty($cfg['section_subtitle'])): ?>
            <p><?= htmlspecialchars((string)$cfg['section_subtitle']) ?></p>
        <?php endif; ?>
        <?php if ($showServerStatus && $onlineServerCount > 0): ?>
            <div style="margin-top: 10px;">
                <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:700; background:#dcfce7; color:#166534;">
                    <span style="width:8px; height:8px; border-radius:50%; background:#16a34a; display:inline-block;"></span>
                    <?= (int)$onlineServerCount ?> Live Game-Server online
                </span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($showFilters && count($presentCategories) > 1): ?>
        <div class="wg-home-filters" id="wgHomeFilters">
            <button type="button" class="wg-home-filter-btn active" onclick="wgFilterHomeGames('all', this)">Alle (<?= count($homeGames) ?>)</button>
            <?php foreach ($presentCategories as $catKey): ?>
                <button type="button" class="wg-home-filter-btn" onclick="wgFilterHomeGames('<?= htmlspecialchars($catKey, ENT_QUOTES) ?>', this)">
                    <?= htmlspecialchars($categoriesMap[$catKey] ?? ucfirst($catKey)) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($homeGames)): ?>
        <p style="text-align:center; color: var(--text-secondary, #64748b);">
            Derzeit sind noch keine Spiele oder Anwendungen freigegeben.
        </p>
    <?php else: ?>
        <div class="wg-home-grid layout-<?= htmlspecialchars($layout) ?>" id="wgHomeGrid">
            <?php foreach ($homeGames as $idx => $g): ?>
                <?php
                $slug = (string)($g['slug'] ?? '');
                $cat = (string)($g['category'] ?? 'browser');
                $rtKey = (string)($g['runtime_type'] ?? 'html5_js');
                $rtInfo = $runtimes[$rtKey] ?? ['badge' => strtoupper($rtKey), 'color' => '#334155'];
                $cover = trim((string)($g['banner'] ?? ''));
                $hasEmbed = GameRolloutManager::isPlayableInBrowser($g);
                $embedUrl = trim((string)($g['embed_url'] ?? ''));
                $runnerHref = '';
                if ($embedUrl !== '') {
                    $runnerHref = !empty($g['enable_coop_coep'])
                        ? ('?page=games&runner=' . urlencode($slug))
                        : $embedUrl;
                } elseif ($slug === 'space-orbit') {
                    $runnerHref = '?page=games&play=space-orbit';
                }

                $latestRel = GameRolloutManager::getLatestRelease($slug, 'windows_x64', 'stable');
                $gameServers = array_filter($servers, fn($s) => ($s['game_slug'] ?? '') === $slug && !empty($s['active']));
                $gameOnline = count(array_filter($gameServers, fn($s) => ($s['status'] ?? '') === 'online'));
                $isLead = ($layout === 'showcase' && $idx === 0);
                $tags = is_array($g['tech_tags'] ?? null) ? $g['tech_tags'] : [];
                $aspect = (string)($g['aspect_ratio'] ?? '16:9');
                ?>
                <article class="wg-home-card <?= $isLead ? 'is-showcase-lead' : '' ?>" data-wg-category="<?= htmlspecialchars($cat) ?>">
                    <div class="wg-home-media <?= ($layout === 'ambient_blur' && $cover !== '') ? 'is-ambient' : '' ?>">
                        <div class="wg-home-badges-top">
                            <span class="wg-runtime-pill" style="background: <?= htmlspecialchars($rtInfo['color']) ?>;">
                                <?= htmlspecialchars($rtInfo['badge']) ?>
                            </span>
                            <?php if (!empty($g['featured'])): ?>
                                <span class="wg-runtime-pill" style="background: #f59e0b; color: #111827;">★ Highlight</span>
                            <?php elseif ($showServerStatus && !empty($gameServers)): ?>
                                <span class="wg-runtime-pill" style="background: <?= $gameOnline > 0 ? '#16a34a' : '#475569' ?>;">
                                    <?= $gameOnline > 0 ? "● {$gameOnline} Server Online" : '○ Server Offline' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($cover !== ''): ?>
                            <?php if ($layout === 'ambient_blur'): ?>
                                <img src="<?= htmlspecialchars($cover) ?>" class="wg-bg-blur" alt="" aria-hidden="true" loading="lazy">
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($cover) ?>" class="wg-fg-img" alt="<?= htmlspecialchars((string)($g['title'] ?? '')) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="wg-home-media-fallback">
                                <div style="font-size: 44px; line-height: 1; margin-bottom: 6px;">
                                    <?= htmlspecialchars((strlen((string)($g['icon'] ?? '')) <= 4 ? (string)$g['icon'] : '🎮')) ?>
                                </div>
                                <div style="font-size: 12px; opacity: 0.85; font-weight: 600;">
                                    <?= htmlspecialchars((string)($g['genre'] ?? ($categoriesMap[$cat] ?? 'Game / App'))) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wg-home-body">
                        <h3 class="wg-home-title"><?= htmlspecialchars((string)($g['title'] ?? '')) ?></h3>
                        <p class="wg-home-desc"><?= htmlspecialchars((string)($g['description'] ?? '')) ?></p>

                        <?php if (!empty($tags)): ?>
                            <div class="wg-home-tags">
                                <?php foreach (array_slice($tags, 0, 5) as $tg): ?>
                                    <span class="wg-home-tag"><?= htmlspecialchars((string)$tg) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="wg-home-actions">
                            <?php if ($hasEmbed && $runnerHref !== ''): ?>
                                <?php if ($usePlayModal && $embedUrl !== '' && ($g['embed_mode'] ?? 'modal') !== 'new_tab'): ?>
                                    <button type="button" class="wg-home-btn wg-home-btn-play"
                                            onclick="wgOpenHomeGameModal(<?= htmlspecialchars(json_encode((string)$g['title']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($runnerHref), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($aspect), ENT_QUOTES) ?>)">
                                        ▶ Direkt spielen
                                    </button>
                                <?php elseif (($g['embed_mode'] ?? '') === 'new_tab' && $embedUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($runnerHref) ?>" target="_blank" rel="noopener noreferrer" class="wg-home-btn wg-home-btn-play">
                                        ▶ Im Tab starten
                                    </a>
                                <?php else: ?>
                                    <a href="?page=games&play=<?= urlencode($slug) ?>" class="wg-home-btn wg-home-btn-play">
                                        ▶ Jetzt spielen
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($latestRel && !empty($latestRel['download_url'])): ?>
                                <a href="<?= htmlspecialchars((string)$latestRel['download_url']) ?>" class="wg-home-btn wg-home-btn-dl">
                                    ⬇️ Download (v<?= htmlspecialchars((string)$latestRel['version']) ?>)
                                </a>
                            <?php endif; ?>

                            <a href="?page=games&play=<?= urlencode($slug) ?>" class="wg-home-btn wg-home-btn-sec">
                                Portal &amp; Details
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 1.75rem;">
        <a href="?page=games" class="wg-home-btn wg-home-btn-sec" style="padding: 10px 22px; font-size: 0.92rem;">
            🚀 Alle Spiele, Server &amp; Downloads im Portal öffnen
        </a>
    </div>
</section>

<!-- Lightbox Game-Player Modal für Startseite -->
<div id="wgHomeGameModal" class="wg-home-modal-backdrop" onclick="if(event.target===this) wgCloseHomeGameModal();">
    <div class="wg-home-modal-box">
        <div class="wg-home-modal-bar">
            <strong id="wgHomeModalTitle" style="font-size: 14px;">Spiel</strong>
            <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" onclick="wgFullscreenHomeModal()" style="background:#334155; color:#fff; border:none; border-radius:6px; padding:5px 10px; font-size:12px; cursor:pointer;">⛶ Vollbild</button>
                <button type="button" onclick="wgCloseHomeGameModal()" style="background:#ef4444; color:#fff; border:none; border-radius:6px; padding:5px 11px; font-size:12px; font-weight:700; cursor:pointer;">&times; Schließen</button>
            </div>
        </div>
        <div id="wgHomeModalStage" style="width:100%; height:72vh; background:#020617;">
            <iframe id="wgHomeModalFrame" src="about:blank" style="width:100%; height:100%; border:0; display:block;" allow="fullscreen; gamepad; pointer-lock; autoplay" sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-popups allow-forms" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
function wgFilterHomeGames(cat, btn) {
    document.querySelectorAll('#wgHomeFilters .wg-home-filter-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');
    document.querySelectorAll('#wgHomeGrid .wg-home-card').forEach(function(card) {
        card.style.display = (cat === 'all' || card.getAttribute('data-wg-category') === cat) ? '' : 'none';
    });
}
function wgOpenHomeGameModal(title, url, aspect) {
    var modal = document.getElementById('wgHomeGameModal');
    var frame = document.getElementById('wgHomeModalFrame');
    var ttl = document.getElementById('wgHomeModalTitle');
    var stage = document.getElementById('wgHomeModalStage');
    if (!modal || !frame) return;
    if (ttl) ttl.textContent = title || 'Spiel';
    if (stage) {
        stage.style.height = (aspect === '1:1') ? 'min(76vh, 720px)' : '74vh';
    }
    frame.src = url;
    modal.classList.add('show');
}
function wgCloseHomeGameModal() {
    var modal = document.getElementById('wgHomeGameModal');
    var frame = document.getElementById('wgHomeModalFrame');
    if (frame) frame.src = 'about:blank';
    if (modal) modal.classList.remove('show');
}
function wgFullscreenHomeModal() {
    var frame = document.getElementById('wgHomeModalFrame');
    if (frame && frame.requestFullscreen) {
        frame.requestFullscreen();
    }
}
</script>
