<?php
/**
 * WeaveCMS – Admin Tab: Plugins & Module (Phase 13)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Token
 */

$all_plugins = PluginManager::getAllPlugins($data);
$active_settings_slug = $_GET['plugin_settings'] ?? null;
?>
<!-- ──────────────── SECTION: PLUGINS & ERWEITERUNGEN ──────────────── -->
<div id="tab-plugins" class="admin-section card" style="padding: 2.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h2 style="color: var(--primary); margin:0 0 0.5rem 0; display:flex; align-items:center; gap: 10px;">
                <span class="material-icons" style="font-size:32px;">extension</span> Modul- &amp; Plugin-System
            </h2>
            <p style="color: var(--text-secondary); margin:0; font-size: 0.95rem;">
                Erweitere WeaveCMS modular um zusätzliche Funktionen, Drittanbieter-Anbindungen (wie Webtrees Stammbäume) und Inhaltstypen.
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="background: rgba(46,98,72,0.1); color: var(--primary); font-size: 0.85rem; font-weight: 700; padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(46,98,72,0.2);">
                <?php 
                    $active_count = count(array_filter($all_plugins, fn($p) => !empty($p['active'])));
                    echo "{$active_count} von " . count($all_plugins) . " Plugins aktiv";
                ?>
            </span>
        </div>
    </div>

    <?php if (empty($all_plugins)): ?>
        <div style="background: rgba(0,0,0,0.02); border: 2px dashed var(--border-color); border-radius: 16px; padding: 3rem 2rem; text-align:center;">
            <span class="material-icons" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 1rem;">extension_off</span>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Keine Plugins gefunden</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; max-width: 520px; margin: 0 auto; line-height: 1.5;">
                Es wurden bisher keine Erweiterungen im Verzeichnis <code>plugins/</code> hinterlegt. Lege einfach einen Unterordner mit einer <code>plugin.json</code> an, um eine neue Erweiterung zu registrieren.
            </p>
        </div>
    <?php else: ?>
        <div class="plugin-grid" style="display:grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($all_plugins as $slug => $plugin): 
                $isActive = !empty($plugin['active']);
                $hasSettings = !empty($plugin['settings_tab']) || PluginManager::hasAction("admin_render_plugin_settings_{$slug}");
                $isConfigOpen = ($active_settings_slug === $slug);
            ?>
                <div class="plugin-card" style="border: 1px solid <?php echo $isActive ? 'rgba(46,98,72,0.3)' : 'var(--border-color)'; ?>; border-radius: 16px; background: <?php echo $isActive ? 'rgba(46,98,72,0.02)' : 'var(--card-bg, #fff)'; ?>; padding: 1.75rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: all 0.2s;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display:flex; align-items:flex-start; gap: 16px;">
                            <div style="width: 54px; height: 54px; border-radius: 14px; background: <?php echo $isActive ? 'rgba(46,98,72,0.12)' : 'rgba(0,0,0,0.05)'; ?>; color: <?php echo $isActive ? 'var(--primary)' : 'var(--text-secondary)'; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <span class="material-icons" style="font-size: 32px;"><?php echo htmlspecialchars($plugin['icon'] ?? 'extension'); ?></span>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap: 8px; flex-wrap:wrap;">
                                    <h3 style="margin:0; font-size: 1.25rem;"><?php echo htmlspecialchars($plugin['name']); ?></h3>
                                    <span style="font-size:0.75rem; font-weight:700; background:rgba(0,0,0,0.06); color:var(--text-secondary); padding:2px 8px; border-radius:6px;">
                                        v<?php echo htmlspecialchars($plugin['version']); ?>
                                    </span>
                                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; background:rgba(178,108,57,0.1); color:var(--accent); padding:2px 8px; border-radius:6px;">
                                        <?php echo htmlspecialchars($plugin['category']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 3px;">
                                    Von <strong><?php echo htmlspecialchars($plugin['author']); ?></strong> &bull; Slug: <code><?php echo htmlspecialchars($slug); ?></code>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Toggle -->
                        <div style="display:flex; align-items:center; gap: 10px;">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="toggle_plugin">
                                <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                <input type="hidden" name="target_state" value="<?php echo $isActive ? '0' : '1'; ?>">

                                <?php if ($isActive): ?>
                                    <button type="submit" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; font-size:0.88rem; padding: 7px 14px; border-color:#f87171; color:#ef4444;" title="Plugin deaktivieren">
                                        <span class="material-icons" style="font-size:16px;">power_settings_new</span> Deaktivieren
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; font-size:0.88rem; padding: 7px 16px;" title="Plugin aktivieren">
                                        <span class="material-icons" style="font-size:16px;">bolt</span> Aktivieren
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php if ($isActive && $hasSettings): ?>
                                <button type="button" class="btn btn-secondary" onclick="togglePluginSettings('<?php echo htmlspecialchars($slug); ?>')" style="display:inline-flex; align-items:center; gap:6px; font-size:0.88rem; padding: 7px 14px;">
                                    <span class="material-icons" style="font-size:16px;">tune</span> Einstellungen
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.5; margin: 0 0 1rem 0;">
                        <?php echo htmlspecialchars($plugin['description']); ?>
                    </p>

                    <div style="display:flex; align-items:center; justify-content:space-between; font-size: 0.82rem; color: var(--text-secondary); padding-top: 0.75rem; border-top: 1px solid rgba(0,0,0,0.06);">
                        <div style="display:flex; align-items:center; gap: 6px;">
                            <span class="material-icons" style="font-size: 16px; color: <?php echo $isActive ? '#10b981' : '#94a3b8'; ?>;">
                                <?php echo $isActive ? 'check_circle' : 'radio_button_unchecked'; ?>
                            </span>
                            <span>Status: <strong><?php echo $isActive ? 'Aktiv' : 'Deaktiviert'; ?></strong></span>
                        </div>
                        <div>
                            <span>Core-Kompatibilität: <code><?php echo htmlspecialchars($plugin['requires_core']); ?></code></span>
                        </div>
                    </div>

                    <!-- Einstellungen (Drawer) -->
                    <?php if ($isActive && $hasSettings): ?>
                        <div id="plugin-settings-drawer-<?php echo htmlspecialchars($slug); ?>" style="display: <?php echo $isConfigOpen ? 'block' : 'none'; ?>; margin-top: 1.5rem; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
                            <?php 
                                PluginManager::doAction("admin_render_plugin_settings_{$slug}", $data, $csrf_token); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePluginSettings(slug) {
    const el = document.getElementById('plugin-settings-drawer-' + slug);
    if (!el) return;
    const isHidden = (el.style.display === 'none' || el.style.display === '');
    el.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
