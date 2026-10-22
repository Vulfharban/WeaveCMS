<?php
/**
 * WeaveCMS – Webtrees Stammbaum-Integration (Frontend Embed View)
 * 
 * @var array $config Plugin-Konfiguration
 * @var array $params Zusätzliche Parameter aus Shortcode oder Sektion
 */
$config = $config ?? [];
$params = $params ?? [];

$url = rtrim($params['url'] ?? ($config['webtrees_url'] ?? ''), '/');
$tree = $params['tree'] ?? ($config['tree_id'] ?? 'tree1');
$person = $params['person'] ?? ($config['default_person'] ?? '');
$mode = $params['mode'] ?? ($config['embed_mode'] ?? 'interactive_iframe');
$height = (int)($params['height'] ?? ($config['iframe_height'] ?? 750));
if ($height < 400 || $height > 1400) {
    $height = 750;
}
$token = $params['token'] ?? ($config['embed_token'] ?? '');

$title = $params['title'] ?? ($config['section_title'] ?? 'Familienstammbaum & Ahnenforschung');
$subtitle = $params['subtitle'] ?? ($config['section_subtitle'] ?? 'Entdecke unsere Familiengeschichte interaktiv aus Webtrees.');

// URL zum Stammbaum zusammenbauen
$treeUrl = $url;
if (!empty($url) && !empty($tree)) {
    // Webtrees URL Schema
    $query = ['ged' => $tree];
    if (!empty($person)) {
        $query['rootid'] = $person;
    }
    if (!empty($token)) {
        $query['token'] = $token;
    }
    $treeUrl = $url . '/tree/' . rawurlencode($tree);
    if (!empty($person)) {
        $treeUrl .= '/individual/' . rawurlencode($person);
    }
    if (!empty($token)) {
        $treeUrl .= '?token=' . rawurlencode($token);
    }
}
?>

<div class="webtrees-embed-wrapper" style="margin: 2.5rem 0;">
    <?php if (empty($url)): ?>
        <!-- Hinweis wenn noch nicht eingerichtet -->
        <div style="background: rgba(0,0,0,0.02); border: 2px dashed var(--border-color); border-radius: 16px; padding: 2.5rem 1.5rem; text-align: center; max-width: 650px; margin: 0 auto;">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">🌳</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--text-primary);"><?php echo htmlspecialchars($title); ?></h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">
                Die Webtrees Stammbaum-Integration ist aktiv, jedoch wurde noch keine Webtrees Server-URL hinterlegt. Bitte trage die URL deines Stammbaums in den Plugin-Einstellungen im Adminbereich ein.
            </p>
            <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                <a href="admin/?tab=tab-plugins" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px;">tune</span> Webtrees jetzt konfigurieren
                </a>
            <?php endif; ?>
        </div>

    <?php elseif ($mode === 'family_preview'): ?>
        <!-- Vorschaukarte mit Direkt-Sprung -->
        <div class="webtrees-card" style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 18px; padding: 2.5rem 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.04); max-width: 800px; margin: 0 auto; text-align: center;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(46,98,72,0.1); color: var(--primary); display:flex; align-items:center; justify-content:center; margin: 0 auto 1.25rem;">
                <span class="material-icons" style="font-size: 38px;">account_tree</span>
            </div>
            <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem; color: var(--text-primary);"><?php echo htmlspecialchars($title); ?></h3>
            <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.6; max-width: 600px; margin: 0 auto 2rem;">
                <?php echo htmlspecialchars($subtitle); ?>
            </p>

            <div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
                <a href="<?php echo htmlspecialchars($treeUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding: 12px 26px; font-size: 1rem; text-decoration:none;">
                    <span class="material-icons">lan</span> Interaktiven Stammbaum öffnen
                </a>
                <a href="<?php echo htmlspecialchars($url . '/tree/' . rawurlencode($tree) . '/individual-list'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:8px; padding: 12px 22px; font-size: 1rem; text-decoration:none;">
                    <span class="material-icons">people</span> Namensregister durchsuchen
                </a>
            </div>

            <div style="margin-top: 1.5rem; font-size: 0.82rem; color: var(--text-secondary);">
                <span>Bereitgestellt via Webtrees &bull; Genealogische Datenbank</span>
            </div>
        </div>

    <?php else: ?>
        <!-- Interaktiver Vollbild-Embed (iFrame) -->
        <div class="webtrees-iframe-container" style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 18px; overflow: hidden; box-shadow: 0 12px 35px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; justify-content:space-between; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.015); flex-wrap:wrap; gap: 10px;">
                <div style="display:flex; align-items:center; gap: 10px;">
                    <span class="material-icons" style="color: var(--primary); font-size: 24px;">account_tree</span>
                    <div>
                        <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary);"><?php echo htmlspecialchars($title); ?></div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo htmlspecialchars($subtitle); ?></div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap: 8px;">
                    <a href="<?php echo htmlspecialchars($treeUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; padding: 6px 14px; text-decoration:none;">
                        <span class="material-icons" style="font-size:16px;">open_in_new</span> Im Vollbild öffnen
                    </a>
                </div>
            </div>

            <div style="position:relative; width: 100%; height: <?php echo (int)$height; ?>px; background: #fff;">
                <iframe 
                    src="<?php echo htmlspecialchars($treeUrl); ?>" 
                    style="width: 100%; height: 100%; border: none; display: block;" 
                    loading="lazy" 
                    title="<?php echo htmlspecialchars($title); ?>"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals">
                </iframe>
            </div>
        </div>
    <?php endif; ?>
</div>
