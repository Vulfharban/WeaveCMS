<?php
/**
 * WeaveCMS – Admin Tab: Darstellung & Design (Themes, Farben, Maße, Logo, Galerie-Layout)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION 5: DARSTELLUNG & DESIGN ──────────────── -->
        <div id="tab-settings" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">🎨 Darstellung &amp; Design</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_settings">

                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">view_quilt</span> Galerie-Layout &amp; Bilddarstellung
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Wähle hier, wie deine Fotos in der Galerie auf der Startseite dargestellt werden sollen. Du kannst das Layout jederzeit wechseln und ausprobieren. Beachte die jeweils empfohlenen Bildgrößen bei der Vorbereitung deiner Fotos:
                </p>

                <?php 
                    $cur_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur'; 
                    $cur_lightbox = !isset($data['settings']['gallery_lightbox']) || !empty($data['settings']['gallery_lightbox']);
                ?>

                <div class="layout-options-grid">
                    <!-- Option B: Smart Ambient-Blur -->
                    <label class="layout-card <?php echo ($cur_layout === 'ambient_blur') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="ambient_blur" <?php echo ($cur_layout === 'ambient_blur') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="layout-badge">⭐ Empfohlen (Kombiniert)</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Smart Ambient-Blur</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Ruhiges, gleichmäßiges Raster mit 100% Motiv-Sichtbarkeit. Das Foto wird vollständig ohne Beschnitt eingepasst und durch eine weichgezeichnete Farbaura des Originalbildes edel gerahmt.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Querformat: <b>1200 × 900 px</b> (4:3) oder Quadrat <b>1000 × 1000 px</b>.<br>
                            Hochkant (z. B. 3:4 oder 9:16): mind. <b>1000 px</b> an der längsten Kante.
                        </div>
                    </label>

                    <!-- Option A: Masonry-Grid -->
                    <label class="layout-card <?php echo ($cur_layout === 'masonry') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="masonry" <?php echo ($cur_layout === 'masonry') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(59, 130, 246, 0.15); color:#2563eb;">Pinterest-Stil</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Masonry-Grid</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Mehrspaltig versetzt mit natürlichen Höhen. Jedes Bild behält seine originale Proportion. Hochkant-Bilder werden hoch und schlank dargestellt, Querformat-Bilder kompakt. Kein Beschnitt.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Jedes Seitenverhältnis möglich.<br>
                            Empfohlene Bildbreite: <b>800 – 1400 px</b> (Höhe passt sich automatisch an).
                        </div>
                    </label>

                    <!-- Option C: Magazin / Editorial -->
                    <label class="layout-card <?php echo ($cur_layout === 'editorial') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="editorial" <?php echo ($cur_layout === 'editorial') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(217, 119, 6, 0.15); color:var(--accent);">Magazin-Look</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Magazin-Raster</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Dynamisches Editorial-Grid. Hochkant-Bilder (wie Personen mit Körben) spannen automatisch über 2 Rasterzeilen und werden zu Blickfängen. Querformat-Bilder füllen normale Kacheln.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Querformat: <b>1200 × 900 px</b> (4:3)<br>
                            Hochformat: <b>900 × 1200 px</b> (3:4 oder 2:3).
                        </div>
                    </label>

                    <!-- Option D: Classic 4:3 -->
                    <label class="layout-card <?php echo ($cur_layout === 'classic') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="classic" <?php echo ($cur_layout === 'classic') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(0, 0, 0, 0.08); color:var(--text-secondary);">Klassisch</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Klassisch 4:3</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Strenges, einheitliches Raster. Alle Bilder werden passgenau auf das 4:3-Format zugeschnitten.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Exakt <b>1200 × 900 px</b> (4:3 Querformat).<br>
                            <em>Hinweis:</em> Wichtige Motive müssen zentriert sein, da Ränder beschnitten werden.
                        </div>
                    </label>
                </div>

                <!-- Lightbox Toggle -->
                <div style="background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2.5rem; display:flex; align-items:flex-start; gap:12px;">
                    <input type="checkbox" name="gallery_lightbox" id="gallery_lightbox" value="1" <?php echo $cur_lightbox ? 'checked' : ''; ?> style="width:20px; height:20px; margin-top:2px; accent-color:var(--primary); cursor:pointer;">
                    <label for="gallery_lightbox" style="cursor:pointer; margin:0;">
                        <span style="font-weight:700; color:var(--text-primary); font-size:1rem; display:block; margin-bottom:3px;">
                            🔍 Interaktive Lightbox / Vollbild-Zoom aktivieren
                        </span>
                        <span style="color:var(--text-secondary); font-size:0.88rem; line-height:1.4; display:block;">
                            Erlaubt Besuchern, auf ein beliebiges Galerie-Bild zu klicken, um es im Vollbild mit hochauflösender Großansicht, Titel, Beschreibung und Weiterschalt-Pfeilen zu betrachten.
                        </span>
                    </label>
                </div>

                <!-- Website Logo Section -->
                <h3 style="font-size: 1.25rem; margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">pets</span> Website-Logo (Hundetatze im Header)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Wähle das SVG-Motiv und die Farbe der Hundetatze, die oben im Header neben dem Seitentitel angezeigt wird.
                </p>

                <?php 
                    $cur_logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_simple';
                    $cur_logo_color = $data['settings']['hero_logo_color'] ?? '#b26c39';
                ?>

                <!-- Motif Selection Grid -->
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 1.5rem;">
                    <!-- Motif 1: Paw Simple -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_simple') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_simple')">
                        <input type="radio" name="hero_logo_motif" value="paw_simple" <?php echo ($cur_logo_motif === 'paw_simple') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_simple', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Klassische Hundetatze</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Klassischer Pfotenabdruck mit 4 einzeln positionierten Zehen.
                        </p>
                    </label>

                    <!-- Motif 2: Paw Branch (Naturzweig) -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_beach') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_beach')">
                        <input type="radio" name="hero_logo_motif" value="paw_beach" <?php echo ($cur_logo_motif === 'paw_beach') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_beach', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Hundetatze &amp; Naturzweig</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Klassische Tatze mit einem dezenten, geschwungenen Naturzweig darunter.
                        </p>
                    </label>

                    <!-- Motif 3: Paw Willow (Eleganter Weidenzweig) -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_willow') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_willow')">
                        <input type="radio" name="hero_logo_motif" value="paw_willow" <?php echo ($cur_logo_motif === 'paw_willow') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_willow', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Hundetatze &amp; Weidenzweig</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Tatze eingefasst von einem kunstvoll geschwungenen Weidenast mit Weidenblättern.
                        </p>
                    </label>

                    <!-- Motif 4: None -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'none') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'none')">
                        <input type="radio" name="hero_logo_motif" value="none" <?php echo ($cur_logo_motif === 'none') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; height: 50px; font-size:1.8rem; color:var(--text-secondary); opacity:0.5;">
                            🚫
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Kein Logo</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Nur Text im Header ohne Pfotengrafik anzeigen.
                        </p>
                    </label>
                </div>

                <!-- Logo Position Section -->
                <?php 
                    $cur_logo_pos = $data['settings']['logo_position'] ?? 'header_before';
                ?>
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    📍 Position des Logos
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height:1.4;">
                    Bestimme, wo das Logo platziert werden soll. Die Größe passt sich automatisch mittig an die Schrifthöhe daneben an.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- Pos 1: Header Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_before')">
                        <input type="radio" name="logo_position" value="header_before" <?php echo ($cur_logo_pos === 'header_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.2em'); ?> <span>Titel</span>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Header: Vor Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Klassisch links vor dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 2: Header After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_after')">
                        <input type="radio" name="logo_position" value="header_after" <?php echo ($cur_logo_pos === 'header_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span>Titel</span> <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.2em'); ?>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Header: Nach Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Rechts direkt hinter dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 3: Hero Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_before')">
                        <input type="radio" name="logo_position" value="hero_before" <?php echo ($cur_logo_pos === 'hero_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.15em'); ?> <span>Hero-Zeile</span>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Hero: Vor dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            In der gleichen Zeile zentriert links vor der Hauptüberschrift.
                        </p>
                    </label>

                    <!-- Pos 4: Hero After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_after')">
                        <input type="radio" name="logo_position" value="hero_after" <?php echo ($cur_logo_pos === 'hero_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span>Hero-Zeile</span> <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.15em'); ?>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Hero: Nach dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            In der gleichen Zeile zentriert rechts hinter der Hauptüberschrift.
                        </p>
                    </label>
                </div>

                <!-- Color Selection & Chips -->
                <div class="form-group" style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2.5rem;">
                    <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                        🎨 Farbe der Hundetatze
                    </label>
                    <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="hero_logo_color" name="hero_logo_color" value="<?php echo htmlspecialchars($cur_logo_color); ?>" oninput="updateLogoColor(this.value)" style="width:44px; height:44px; border:1px solid var(--border-color); border-radius:10px; cursor:pointer; background:transparent;">
                            <span id="color_code_display" style="font-family:monospace; font-size:0.95rem; font-weight:600; color:var(--text-primary);"><?php echo htmlspecialchars($cur_logo_color); ?></span>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(178,108,57,0.3); color:#b26c39; font-weight:600;" onclick="setColorPreset('#b26c39')">🌿 Weiden-Braun (#b26c39)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(46,98,72,0.3); color:#2e6248; font-weight:600;" onclick="setColorPreset('#2e6248')">🌲 Wald-Grün (#2e6248)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(217,119,6,0.3); color:#d97706; font-weight:600;" onclick="setColorPreset('#d97706')">☀️ Bernstein (#d97706)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(60,52,46,0.3); color:#3c342e; font-weight:600;" onclick="setColorPreset('#3c342e')">☕ Anthrazit (#3c342e)</button>
                        </div>
                    </div>
                </div>

                <!-- ==========================================================
                     🎨 SEITEN-LAYOUT & DESIGN-SYSTEM (HYBRID-MODELL)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">palette</span> Seiten-Layout &amp; Design-System
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Passe das optische Erscheinungsbild deiner Website an. Wähle eines der harmonischen Natur-Farbthemen (1-Klick) oder stimme Farben, Seitenbreite, Schriftgrößen und Galerie-Abstände ganz nach deinen Wünschen fein ab.
                </p>

                <?php
                    $cur_theme = $data['settings']['design_theme'] ?? 'nature_classic';
                    $cur_width = $data['settings']['container_width'] ?? '1200px';
                    $cur_primary = $data['settings']['primary_color'] ?? '#2e6248';
                    $cur_accent = $data['settings']['accent_color'] ?? '#b26c39';
                    $cur_bg = $data['settings']['bg_color'] ?? '#fbf9f6';
                    $cur_font_scale = $data['settings']['font_scale'] ?? '100%';
                    $cur_card_radius = $data['settings']['card_radius'] ?? '20px';
                    $cur_gallery_gap = $data['settings']['gallery_gap'] ?? '2rem';
                ?>

                <!-- 1. Kuratierte 1-Klick-Natur-Themes -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block;">
                    🌿 1. Natur-Farbthemen (1-Klick Vorlagen)
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Beim Klick auf ein Farbthema werden die passenden Primär-, Akzent- und Hintergrundfarben automatisch vorausgewählt.
                </p>

                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- Theme 1: Weidenglück (Standard) -->
                    <label class="layout-card <?php echo ($cur_theme === 'nature_classic') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nature_classic')">
                        <input type="radio" name="design_theme" value="nature_classic" <?php echo ($cur_theme === 'nature_classic') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#2e6248;" title="Primär: Waldgrün"></span>
                            <span class="theme-swatch" style="background:#b26c39;" title="Akzent: Weidenton"></span>
                            <span class="theme-swatch" style="background:#fbf9f6; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Leinenweiß"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Weidenglück <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Standard</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Das bewährte Originaldesign: Sanftes Naturleinen, Waldgrün und warmer Weidenton.
                        </p>
                    </label>

                    <!-- Theme 2: Moor & Bernstein -->
                    <label class="layout-card <?php echo ($cur_theme === 'moor_amber') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'moor_amber')">
                        <input type="radio" name="design_theme" value="moor_amber" <?php echo ($cur_theme === 'moor_amber') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#4a3f35;" title="Primär: Moosbraun"></span>
                            <span class="theme-swatch" style="background:#d97706;" title="Akzent: Bernstein"></span>
                            <span class="theme-swatch" style="background:#f7f4ee; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Sandcreme"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Moor &amp; Bernstein</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Warme Erdtöne: Sanfter Sandstrand, tiefes Moosbraun und sonniges Bernstein.
                        </p>
                    </label>

                    <!-- Theme 3: Nordisch Klar -->
                    <label class="layout-card <?php echo ($cur_theme === 'nordic_sage') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nordic_sage')">
                        <input type="radio" name="design_theme" value="nordic_sage" <?php echo ($cur_theme === 'nordic_sage') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#3a6351;" title="Primär: Salbeigrün"></span>
                            <span class="theme-swatch" style="background:#2c3e50;" title="Akzent: Schieferblau"></span>
                            <span class="theme-swatch" style="background:#f4f6f5; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Hellgrau"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Nordisch Klar</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Ruhig und dezent: Skandinavisches Hellgrau, zartes Salbeigrün und Schiefer.
                        </p>
                    </label>

                    <!-- Theme 4: Warmes Atelier -->
                    <label class="layout-card <?php echo ($cur_theme === 'terracotta_atelier') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'terracotta_atelier')">
                        <input type="radio" name="design_theme" value="terracotta_atelier" <?php echo ($cur_theme === 'terracotta_atelier') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#a85832;" title="Primär: Terrakotta"></span>
                            <span class="theme-swatch" style="background:#322822;" title="Akzent: Espresso"></span>
                            <span class="theme-swatch" style="background:#faf7f2; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Elfenbein"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Warmes Atelier</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Künstlerisch &amp; behaglich: Elfenbein, gebrannte Terrakotta und dunkles Holz.
                        </p>
                    </label>

                    <!-- Theme 5: Custom (Individuell) -->
                    <label class="layout-card <?php echo ($cur_theme === 'custom') ? 'selected' : ''; ?>" id="theme_card_custom" onclick="selectDesignTheme(this, 'custom')">
                        <input type="radio" name="design_theme" value="custom" <?php echo ($cur_theme === 'custom') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch custom-swatch-primary" style="background:<?php echo htmlspecialchars($cur_primary); ?>;"></span>
                            <span class="theme-swatch custom-swatch-accent" style="background:<?php echo htmlspecialchars($cur_accent); ?>;"></span>
                            <span class="theme-swatch custom-swatch-bg" style="background:<?php echo htmlspecialchars($cur_bg); ?>; border:1px solid rgba(0,0,0,0.15);"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Individuell</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Freie eigene Farbkomposition über die Farbwähler unten.
                        </p>
                    </label>
                </div>

                <!-- 2. Freie Detailfarben -->
                <div class="form-group" style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem;">
                    <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                        🎨 2. Farb-Feinabstimmung (Farbwähler)
                    </label>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Hier kannst du die Einzelfarben bei Bedarf punktuell anpassen. Sobald eine Farbe manuell geändert wird, wird das Thema automatisch auf „Individuell“ gesetzt.
                    </p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:1.25rem;">
                        <!-- Primary Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Haupt- &amp; Markenfarbe
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Buttons, Links, aktive Filter</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($cur_primary); ?>" oninput="onCustomColorChange('primary', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_primary_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_primary); ?></span>
                            </div>
                        </div>

                        <!-- Accent Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Akzent- &amp; Highlightfarbe
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Kursüberschrift, Badges, Highlights</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($cur_accent); ?>" oninput="onCustomColorChange('accent', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_accent_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_accent); ?></span>
                            </div>
                        </div>

                        <!-- Background Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Seiten-Hintergrund
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Grundton der gesamten Website</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($cur_bg); ?>" oninput="onCustomColorChange('bg', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_bg_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_bg); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Seitenbreite (Container Max-Width) -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    📐 3. Seitenbreite &amp; Inhaltsrahmen
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Legt fest, wie weit sich der Inhaltsbereich auf großen Bildschirmen maximal ausdehnen darf.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 1050px -->
                    <label class="layout-card <?php echo ($cur_width === '1050px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1050px" <?php echo ($cur_width === '1050px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">📖</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Kompakt (1050 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Fokussierter Lesefluss mit ruhiger, zentrierter Ästhetik.
                        </p>
                    </label>

                    <!-- 1200px (Standard) -->
                    <label class="layout-card <?php echo ($cur_width === '1200px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1200px" <?php echo ($cur_width === '1200px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">✨</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Standard (1200 px) <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Aktuell</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Ausgewogenes Verhältnis zwischen Bildwirkung und Lesbarkeit.
                        </p>
                    </label>

                    <!-- 1400px -->
                    <label class="layout-card <?php echo ($cur_width === '1400px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1400px" <?php echo ($cur_width === '1400px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">🖼️</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Großzügig (1400 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Weitläufiges Studio-Layout für moderne, hochauflösende Displays.
                        </p>
                    </label>
                </div>

                <!-- 4. Schriftgrößen & Typografie -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    🔤 4. Schriftgrößen-Skalierung
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Skaliert alle Texte, Überschriften und Zeilenhöhen fließend über das gesamte Layout.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 95% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '95%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="95%" <?php echo ($cur_font_scale === '95%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.15rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (95%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Kompakt (~15 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Etwas dezentere Typografie für zartere Optik.
                        </p>
                    </label>

                    <!-- 100% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '100%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="100%" <?php echo ($cur_font_scale === '100%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.35rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (100%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Standard (16 px) <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Aktuell</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Perfekt ausbalancierte Lesegröße nach Web-Standards.
                        </p>
                    </label>

                    <!-- 108% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '108%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="108%" <?php echo ($cur_font_scale === '108%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.55rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (108%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Komfortabel (~17.5 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Besonders augenschonend und leicht lesbar.
                        </p>
                    </label>
                </div>

                <!-- 5. Galerie-Feinabstimmung (Ecken & Abstände) -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    🖼️ 5. Galerie-Kacheln: Eckenabrundung &amp; Raster-Abstand
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Stimme die Geometrie der Galerie-Karten und Werkstatt-Kacheln individuell ab.
                </p>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; margin-bottom:2.5rem;">
                    <!-- Kachel-Ecken (Border-Radius) -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); display:block; margin-bottom:8px;">
                            Eckenabrundung (Border-Radius)
                        </span>
                        <div class="layout-options-grid" style="grid-template-columns: repeat(3, 1fr); gap:10px;">
                            <label class="layout-card <?php echo ($cur_card_radius === '10px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="10px" <?php echo ($cur_card_radius === '10px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:6px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">10 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Dezent</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_card_radius === '20px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="20px" <?php echo ($cur_card_radius === '20px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:12px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">20 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Organisch</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_card_radius === '28px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="28px" <?php echo ($cur_card_radius === '28px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:18px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">28 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Soft</small>
                            </label>
                        </div>
                    </div>

                    <!-- Raster-Abstand (Gap) -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); display:block; margin-bottom:8px;">
                            Raster-Abstand (Kachel-Zwischenraum)
                        </span>
                        <div class="layout-options-grid" style="grid-template-columns: repeat(3, 1fr); gap:10px;">
                            <label class="layout-card <?php echo ($cur_gallery_gap === '1.25rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="1.25rem" <?php echo ($cur_gallery_gap === '1.25rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:3px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">20 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Kompakt</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_gallery_gap === '2rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="2rem" <?php echo ($cur_gallery_gap === '2rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:8px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">32 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Standard</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_gallery_gap === '2.75rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="2.75rem" <?php echo ($cur_gallery_gap === '2.75rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:14px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">44 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Luftig</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ==========================================================
                     MENÜ- & TRIGGER-PLATZIERUNG: KONTAKT & IMPRESSUM
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">tune</span> Menü-Platzierung (Kontakt &amp; Impressum)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Bestimme hier, wo die Trigger für das Kontaktformular und das Impressum/Datenschutz-Slide-in auf deiner Website angezeigt werden sollen.
                </p>

                <?php 
                    $cur_contact_placement = $data['settings']['contact_placement'] ?? 'both';
                    $cur_impressum_placement = $data['settings']['impressum_placement'] ?? 'both';
                ?>

                <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Kontakt Trigger -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.98rem; color:var(--text-primary); display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <span class="material-icons" style="font-size:18px; color:var(--primary);">mail</span> Kontakt-Button anzeigen in:
                        </span>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="contact_placement" value="header" <?php echo ($cur_contact_placement === 'header') ? 'checked' : ''; ?>>
                                <span>Nur im Header (Navigation oben)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="contact_placement" value="footer" <?php echo ($cur_contact_placement === 'footer') ? 'checked' : ''; ?>>
                                <span>Nur im Footer (Seitenende unten)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="contact_placement" value="both" <?php echo ($cur_contact_placement === 'both') ? 'checked' : ''; ?>>
                                <span><b>Header &amp; Footer (Beides)</b> <small style="color:var(--text-secondary);">(Standard)</small></span>
                            </label>
                        </div>
                    </div>

                    <!-- Impressum Trigger -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.98rem; color:var(--text-primary); display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <span class="material-icons" style="font-size:18px; color:var(--primary);">policy</span> Impressum &amp; Datenschutz anzeigen in:
                        </span>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="impressum_placement" value="header" <?php echo ($cur_impressum_placement === 'header') ? 'checked' : ''; ?>>
                                <span>Nur im Header (Navigation oben)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="impressum_placement" value="footer" <?php echo ($cur_impressum_placement === 'footer') ? 'checked' : ''; ?>>
                                <span>Nur im Footer (Seitenende unten)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="impressum_placement" value="both" <?php echo ($cur_impressum_placement === 'both') ? 'checked' : ''; ?>>
                                <span><b>Header &amp; Footer (Beides)</b> <small style="color:var(--text-secondary);">(Standard)</small></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ==========================================================
                     BESUCHER-BARRIEREFREIHEITSLEISTE (PHASE 16)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">accessibility_new</span> Besucher-Barrierefreiheit &amp; Ansicht
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Ermögliche deinen Website-Besuchern maximalen Lesekomfort durch eine dezente Steuerleiste am oberen Seitenrand.
                </p>

                <?php 
                    $cur_a11y_bar = !empty($data['settings']['accessibility_bar_enabled']);
                ?>
                <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem;">
                    <label style="display:inline-flex; align-items:flex-start; gap:12px; cursor:pointer;">
                        <input type="checkbox" name="accessibility_bar_enabled" value="1" <?php echo $cur_a11y_bar ? 'checked' : ''; ?> style="width:20px; height:20px; margin-top:2px; accent-color:var(--primary);">
                        <div>
                            <span style="font-weight:700; font-size:1rem; color:var(--text-primary); display:block;">
                                Besucher-Barrierefreiheitsleiste im Frontend anzeigen
                            </span>
                            <span style="font-size:0.86rem; color:var(--text-secondary); line-height:1.45; display:block; margin-top:4px;">
                                Blendet am oberen Bildschirmrand eine Leiste mit Live Hell-/Dunkelmodus (🌙/☀️), stufenlosem Schriftgrößen-Zoom (A- / A+) und Sprachwechsler ein. Einstellungen der Besucher werden automatisch im Browser gespeichert.
                            </span>
                        </div>
                    </label>
                </div>

                <!-- ==========================================================
                     MEHRSPRACHIGKEIT (i18n) & SPRACHENAUSWAHL (PHASE 8)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">language</span> Mehrsprachigkeit &amp; Sprachenauswahl (West-/Südeuropa &amp; Skandinavien)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Aktiviere hier zusätzliche Sprachen für deine Website. Sobald mehr als eine Sprache aktiv ist, erscheint im Frontend automatisch ein dezenter Sprachumschalter im Header und im Mobilmenü.
                </p>

                <?php 
                    $all_supported_languages = LanguageManager::getSupportedLanguages();
                    $cur_primary_lang = LanguageManager::getPrimaryLanguage($data);
                    $cur_active_langs = LanguageManager::getActiveLanguages($data);
                ?>

                <!-- 1. Hauptsprache -->
                <div class="form-group" style="margin-bottom: 1.5rem; max-width: 480px;">
                    <label style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem; display:block;">
                        Primäre Hauptsprache (Standard)
                    </label>
                    <select name="primary_language" class="form-control" style="padding: 10px 12px; font-size: 0.95rem;">
                        <?php foreach ($all_supported_languages as $code => $info): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code === $cur_primary_lang) ? 'selected' : ''; ?>>
                                <?php echo $info['flag'] . ' ' . htmlspecialchars($info['name']) . ' (' . htmlspecialchars($info['native']) . ') [' . strtoupper($code) . ']'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--text-secondary); display:block; margin-top: 5px; font-size: 0.82rem;">
                        Die Hauptsprache dient als Basis-Inhalt und automatischer Fallback für unvollständige Übersetzungen.
                    </small>
                </div>

                <!-- 2. Aktivierte Sprachen (Katalog) -->
                <div style="margin-bottom: 2rem;">
                    <label style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.75rem; display:block;">
                        Aktivierte Sprachen für Besucher
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px;">
                        <?php foreach ($all_supported_languages as $code => $info): ?>
                            <?php 
                                $is_active = in_array($code, $cur_active_langs, true);
                                $is_primary = ($code === $cur_primary_lang);
                            ?>
                            <label style="display:flex; align-items:center; gap: 10px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.02); cursor: pointer;">
                                <input type="checkbox" name="active_languages[]" value="<?php echo htmlspecialchars($code); ?>" <?php echo ($is_active || $is_primary) ? 'checked' : ''; ?> <?php if ($is_primary) echo 'disabled title="Die Hauptsprache ist stets aktiv"'; ?>>
                                <span style="font-size: 1.3rem;"><?php echo $info['flag']; ?></span>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($info['native']); ?>
                                        <small style="color:var(--text-secondary); font-weight:normal;">(<?php echo strtoupper($code); ?>)</small>
                                    </div>
                                    <small style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($info['region']); ?></small>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Darstellung &amp; Design speichern
                </button>
            </form>
        </div>
