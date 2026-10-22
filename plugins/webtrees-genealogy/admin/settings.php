<?php
/**
 * WeaveCMS – Webtrees Stammbaum-Integration (Admin Einstellungsansicht)
 * 
 * @var array $config Aktuelle Plugin-Konfiguration
 * @var string $csrf_token CSRF-Token
 */
$config = $config ?? [];
$webtrees_path = $config['webtrees_path'] ?? $config['webtrees_url'] ?? '/webtrees';
$tree_id = $config['tree_id'] ?? 'tree1';
$embed_mode = $config['embed_mode'] ?? 'interactive_iframe';
$default_person = $config['default_person'] ?? 'I1';
$iframe_height = $config['iframe_height'] ?? '750';
$section_enabled = !empty($config['section_enabled']);
$section_title = $config['section_title'] ?? 'Familienstammbaum & Ahnenforschung';
$section_subtitle = $config['section_subtitle'] ?? 'Entdecke unsere Familiengeschichte, Vorfahren und historische Dokumente interaktiv aus Webtrees.';
$embed_token = $config['embed_token'] ?? '';

// Lokale Server-Dateiprüfung
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$testFsPath = $docRoot . '/' . ltrim($webtrees_path, '/');
$localExists = (!empty($docRoot) && (is_dir($testFsPath) || file_exists($testFsPath)));
?>
<div class="webtrees-settings-panel" style="padding: 1.5rem; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; margin-top: 1rem;">
    <div style="display:flex; align-items:center; gap: 12px; margin-bottom: 1.5rem;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(46,98,72,0.12); color: var(--primary); display:flex; align-items:center; justify-content:center;">
            <span class="material-icons" style="font-size: 28px;">account_tree</span>
        </div>
        <div>
            <h3 style="margin:0; font-size: 1.3rem;">🌳 Webtrees Stammbaum-Integration (Same-Server)</h3>
            <p style="margin:2px 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                Bindet deine lokale Webtrees-Installation auf demselben Webserver sicher und datenschutzkonform ein. Externe Server-URLs sind nicht gestattet.
            </p>
        </div>
    </div>

    <form method="POST" action="?tab=tab-plugins&plugin_settings=webtrees-genealogy#tab-plugins">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="action" value="save_plugin_settings">
        <input type="hidden" name="plugin_slug" value="webtrees-genealogy">
        <input type="hidden" name="plugin_settings" value="webtrees-genealogy">
        <input type="hidden" name="target_tab" value="tab-plugins">

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">
                    Lokaler Server-Pfad zur Webtrees-Startseite <span style="color:#ef4444;">*</span>
                </label>
                <div style="position:relative;">
                    <input type="text" id="webtrees_path_input" name="webtrees_path" class="form-control" 
                           placeholder="/webtrees oder /webtrees/index.php" 
                           value="<?php echo htmlspecialchars($webtrees_path); ?>" 
                           oninput="if(this.value.match(/^https?:\/\//i)){ alert('Externe URLs sind nicht erlaubt. Die Webtrees-Installation muss auf demselben Server liegen.'); this.value = '/' + this.value.replace(/^https?:\/\/[^\/]+/i, '').replace(/^\/+/, ''); }" 
                           required>
                </div>
                <div style="font-size:0.8rem; margin-top:6px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                    <span style="color:var(--text-secondary);">Pfad im Webroot dieses Servers (z. B. <code>/webtrees</code>). Keine externe Domain.</span>
                    <?php if ($localExists): ?>
                        <span style="color:#15803d; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:16px;">check_circle</span> Pfad auf Server gefunden
                        </span>
                    <?php else: ?>
                        <span style="color:var(--text-secondary); font-size:0.78rem;">
                            (Server-Prüfpfad: <?php echo htmlspecialchars($webtrees_path); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Stammbaum-Kürzel / GEDCOM ID <span style="color:#ef4444;">*</span></label>
                <input type="text" name="tree_id" class="form-control" placeholder="z. B. family_tree oder baum1" value="<?php echo htmlspecialchars($tree_id); ?>" required>
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">Der interne Baumname in Webtrees (z. B. aus der Webtrees URL: <code>?ged=baum1</code>).</div>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Einbettungs-Modus</label>
                <select name="embed_mode" class="form-control" style="background:var(--card-bg, #fff);">
                    <option value="interactive_iframe" <?php echo $embed_mode === 'interactive_iframe' ? 'selected' : ''; ?>>
                        🖥️ Interaktiver Stammbaum-Viewer (Responsive iFrame)
                    </option>
                    <option value="family_preview" <?php echo $embed_mode === 'family_preview' ? 'selected' : ''; ?>>
                        📜 Stilvolle Chronik-Vorschaukarte mit Webtrees-Direktlink
                    </option>
                </select>
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">Steuert, wie Besucher den Stammbaum im Frontend erleben.</div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Startperson / GEDCOM-ID (optional)</label>
                <input type="text" name="default_person" class="form-control" placeholder="z. B. I1 oder I123" value="<?php echo htmlspecialchars($default_person); ?>">
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">ID der zentralen Person, die standardmäßig im Diagramm geöffnet wird.</div>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Höhe des interaktiven Viewers (Pixel)</label>
                <input type="number" name="iframe_height" class="form-control" min="400" max="1400" step="50" value="<?php echo htmlspecialchars($iframe_height); ?>">
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">Empfohlen: 650 bis 850 Pixel für optimale Übersicht.</div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Zugangs-Token / Gast-Code (optional)</label>
                <input type="text" name="embed_token" class="form-control" placeholder="Falls Webtrees-Zugriff geschützt ist" value="<?php echo htmlspecialchars($embed_token); ?>">
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">Wird bei Bedarf als Authentifizierungs-Parameter an Webtrees übergeben.</div>
            </div>
        </div>

        <!-- Sektions-Steuerung für die Startseite -->
        <div style="background: rgba(46,98,72,0.05); border: 1px solid rgba(46,98,72,0.2); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <label style="display:flex; align-items:center; gap: 10px; cursor:pointer; font-weight: 600; font-size: 0.95rem; margin-bottom: 1rem;">
                <input type="checkbox" name="section_enabled" value="1" <?php echo $section_enabled ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                <span>🌿 Eigenständige Stammbaum-Sektion auf der Startseite aktivieren</span>
            </label>

            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Sektions-Überschrift</label>
                    <input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars($section_title); ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Sektions-Untertitel / Teaser</label>
                    <input type="text" name="section_subtitle" class="form-control" value="<?php echo htmlspecialchars($section_subtitle); ?>">
                </div>
            </div>
        </div>

        <!-- Shortcode-Info -->
        <div style="background: rgba(0,0,0,0.03); border: 1px dashed var(--border-color); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.88rem; color: var(--text-secondary);">
            <strong style="color:var(--text-primary);"><span class="material-icons" style="font-size:16px; vertical-align:text-bottom;">code</span> Shortcode-Nutzung:</strong>
            Du kannst den Stammbaum auch jederzeit in Blogbeiträgen oder Einzelseiten einbetten mit:<br>
            <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-weight:600; color:var(--primary);">[webtrees]</code> oder 
            <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-weight:600; color:var(--primary);">[webtrees tree="<?php echo htmlspecialchars($tree_id); ?>" person="<?php echo htmlspecialchars($default_person); ?>"]</code>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding: 10px 22px;">
                <span class="material-icons">save</span> Webtrees-Einstellungen speichern
            </button>

            <?php if (!empty($webtrees_path)): ?>
                <a href="<?php echo htmlspecialchars(rtrim($webtrees_path, '/') . '/tree/' . urlencode($tree_id)); ?>" target="_blank" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; font-size:0.9rem;">
                    <span class="material-icons" style="font-size:18px;">open_in_new</span> Webtrees auf Server aufrufen
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>
