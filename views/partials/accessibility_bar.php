<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Besucher-Barrierefreiheitsleiste (Phase 16)
 * ============================================================================
 * Ermöglicht Besuchern das Umschalten von Live-Darkmode, Schriftzoom (A-/A+)
 * und Sprachauswahl mit lokaler Speicherung im Browser (localStorage).
 *
 * @var array $data
 * @var array $lang_switch_items
 */

if (empty($data['settings']['accessibility_bar_enabled'])) {
    return;
}
?>
<!-- ──────────────── BESUCHER-BARRIEREFREIHEITSLEISTE ──────────────── -->
<aside class="accessibility-bar" aria-label="Barrierefreiheit und Ansichtseinstellungen">
    <div class="accessibility-container">
        <!-- Linke Seite: Label / Info -->
        <div style="display:flex; align-items:center; gap:8px; color:var(--text-secondary); font-size:0.8rem; font-weight:600;">
            <span class="material-icons" style="font-size:16px; color:var(--primary);">accessibility_new</span>
            <span class="a11y-label-text">Ansicht &amp; Barrierefreiheit</span>
        </div>

        <!-- Rechte Seite: Steuerung (Theme, Schriftgröße, Sprache) -->
        <div class="a11y-controls">
            <!-- 1. Live Dark-Mode Umschalter -->
            <button type="button" class="a11y-btn" id="a11y-theme-btn" onclick="toggleWeaveTheme()" title="Zwischen Hell- und Dunkelmodus wechseln">
                <span id="a11y-theme-icon">🌙</span>
                <span id="a11y-theme-label">Dunkelmodus</span>
            </button>

            <!-- 2. Schriftgrößen-Zoom (A- / A / A+) -->
            <div style="display:inline-flex; align-items:center; gap:2px; background:rgba(0,0,0,0.03); border-radius:6px; padding:2px;">
                <button type="button" class="a11y-btn" onclick="adjustWeaveFontSize(-5)" title="Schrift verkleinern (A-)">A-</button>
                <button type="button" class="a11y-btn" onclick="resetWeaveFontSize()" title="Standard-Schriftgröße (100%)">A</button>
                <button type="button" class="a11y-btn" onclick="adjustWeaveFontSize(5)" title="Schrift vergrößern (A+)">A+</button>
            </div>

            <!-- 3. Integrierter Sprachumschalter (falls aktiv) -->
            <?php if (!empty($lang_switch_items) && count($lang_switch_items) > 1): ?>
                <div style="display:inline-flex; align-items:center; gap:5px; margin-left:8px; padding-left:8px; border-left:1px solid var(--border-color); font-size:0.78rem; font-weight:700;">
                    <?php foreach ($lang_switch_items as $l): ?>
                        <?php if ($l['active']): ?>
                            <span style="color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:1px;"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($l['url']); ?>" style="color:var(--text-secondary); text-decoration:none; opacity:0.7;" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</aside>

<script>
// Live-Theme Umschaltung mit Speicherung im localStorage
function toggleWeaveTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const target = isDark ? 'light' : 'dark';
    if (target === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('weave_user_theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('weave_user_theme', 'light');
    }
    updateA11yThemeButtons();
}

function updateA11yThemeButtons() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const icon = document.getElementById('a11y-theme-icon');
    const label = document.getElementById('a11y-theme-label');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    if (label) label.textContent = isDark ? 'Hellmodus' : 'Dunkelmodus';
}

// Schriftgrößen-Zoom (stufenlos zwischen 85% und 125%)
let currentZoomPercent = parseInt(localStorage.getItem('weave_user_zoom') || '100', 10);

function applyWeaveFontSize(percent) {
    currentZoomPercent = Math.max(85, Math.min(125, percent));
    document.documentElement.style.fontSize = currentZoomPercent + '%';
    localStorage.setItem('weave_user_zoom', currentZoomPercent.toString());
}

function adjustWeaveFontSize(delta) {
    applyWeaveFontSize(currentZoomPercent + delta);
}

function resetWeaveFontSize() {
    applyWeaveFontSize(100);
}

// Sofortige Initialisierung des Buttons
document.addEventListener('DOMContentLoaded', function() {
    updateA11yThemeButtons();
});
</script>
