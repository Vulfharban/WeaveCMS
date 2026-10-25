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
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_settings">
                <input type="hidden" name="target_tab" value="tab-settings">

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

                <!-- Website Logo & Branding Section (Phase 11) -->
                <?php 
                    $cur_logo_type = LogoRenderer::getLogoType($data);
                    $cur_logo_image = $data['settings']['logo_image'] ?? '';
                    $cur_logo_max_height = max(24, min(120, (int)($data['settings']['logo_max_height'] ?? 44)));
                    $cur_favicon = $data['settings']['favicon'] ?? '';
                    $cur_logo_pos = $data['settings']['logo_position'] ?? 'header_before';
                    $cur_logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_simple';
                    $cur_logo_color = $data['settings']['hero_logo_color'] ?? '#b26c39';
                    $site_title = $data['texts']['contact_name'] ?? 'Atelier Musterfrau';
                ?>
                <h3 style="font-size: 1.25rem; margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">branding_watermark</span> Website-Logo, Branding &amp; Favicon
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Gestalte die visuelle Identität deines Webauftritts. Wähle deinen bevorzugten Logo-Typ, lade ein eigenes Bild- oder SVG-Logo hoch, skaliere die Höhe stufenlos und hinterlege ein Favicon für Browser-Tabs und Smartphone-Homescreens.
                </p>

                <!-- 1. Logo-Typ Auswahl -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block;">
                    🏷️ Logo-Typ auswählen
                </label>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 1.5rem;">
                    <!-- Typ 1: Reines Text-Logo -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'text') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'text')">
                        <input type="radio" name="logo_type" value="text" <?php echo ($cur_logo_type === 'text') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">title</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Reines Text-Logo</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Klassische typografische Darstellung deines Namens/Ateliers ohne Grafik.
                        </p>
                    </label>

                    <!-- Typ 2: Eigenes Bild- / SVG-Logo -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'image') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'image')">
                        <input type="radio" name="logo_type" value="image" <?php echo ($cur_logo_type === 'image') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">image</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Eigenes Bild-Logo</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Vollständiges Firmen- oder Vereinslogo (SVG, PNG, WebP) als Header-Grafik.
                        </p>
                    </label>

                    <!-- Typ 3: Icon + Text kombiniert -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'icon_text') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'icon_text')">
                        <input type="radio" name="logo_type" value="icon_text" <?php echo ($cur_logo_type === 'icon_text') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">loyalty</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Icon &amp; Text kombiniert</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Kompaktes Logo-Icon links neben deinem Firmen- oder Ateliernamen.
                        </p>
                    </label>

                    <!-- Typ 4: SVG-Tatze -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'motif') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'motif')">
                        <input type="radio" name="logo_type" value="motif" <?php echo ($cur_logo_type === 'motif') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">pets</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">SVG-Naturmotiv</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            WeaveCMS Hundetatzen- und Naturmotive mit anpassbarer Farbe.
                        </p>
                    </label>
                </div>

                <!-- 2. Eigenes Logo Upload & Skalierung -->
                <div id="logo-image-settings-box" style="display: <?php echo in_array($cur_logo_type, ['image', 'icon_text'], true) ? 'block' : 'none'; ?>; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">upload_file</span> Eigenes Logo hochladen &amp; skalieren
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Unterstützte Dateiformate: <strong>SVG</strong> (empfohlen für gestochenscharfe Vektoren), <strong>PNG</strong> (mit Transparenz), <strong>WebP</strong> oder <strong>JPG</strong>.
                    </p>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; align-items:start;">
                        <!-- Upload & Slider -->
                        <div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:6px;">Neue Logo-Datei auswählen:</label>
                                <input type="file" name="logo_file" id="logo_file_input" accept=".svg,.png,.webp,.jpg,.jpeg,.gif" class="form-control" onchange="previewLogoFile(this)" style="background:#fff; padding:8px 12px;">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <label style="font-weight:600; font-size:0.88rem; margin:0;">Maximale Logo-Höhe im Header:</label>
                                    <span id="logo_height_display" style="font-family:monospace; font-weight:700; color:var(--primary); font-size:0.95rem;"><?php echo $cur_logo_max_height; ?> px</span>
                                </div>
                                <input type="range" name="logo_max_height" id="logo_max_height_range" min="24" max="100" value="<?php echo $cur_logo_max_height; ?>" class="form-control" oninput="updateLogoMaxHeight(this.value)" style="padding:0; height:auto; cursor:pointer;">
                                <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-top:4px;">Die Breite skaliert automatisch proportional mit, sodass dein Logo niemals verzerrt wird.</small>
                            </div>

                            <?php if (!empty($cur_logo_image)): ?>
                                <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:10px 14px; margin-top:1rem;">
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; color:#ef4444; font-weight:600; font-size:0.88rem; margin:0;">
                                        <input type="checkbox" name="delete_logo" value="1">
                                        <span>Aktuelles Logo entfernen / löschen</span>
                                    </label>
                                    <small style="display:block; color:var(--text-secondary); font-size:0.78rem; margin-top:3px; margin-left:24px;">Pfad: <?php echo htmlspecialchars($cur_logo_image); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Live-Vorschau Hell & Dunkel -->
                        <div>
                            <label style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:8px;">Live-Vorschau (Hell- &amp; Dunkelmodus):</label>
                            
                            <!-- Helle Box -->
                            <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:12px; padding:14px 18px; margin-bottom:10px; display:flex; align-items:center; gap:12px; min-height:70px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                                <div id="preview-logo-container-light" style="display:flex; align-items:center; gap:10px;">
                                    <?php if (!empty($cur_logo_image)): ?>
                                        <img src="<?php echo htmlspecialchars($cur_logo_image); ?>" id="preview-logo-img-light" alt="Logo" style="max-height:<?php echo $cur_logo_max_height; ?>px; width:auto; object-fit:contain;">
                                    <?php else: ?>
                                        <div id="preview-logo-placeholder-light" style="width:40px; height:40px; background:rgba(0,0,0,0.05); border:1px dashed var(--border-color); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-size:1.2rem;">🖼️</div>
                                    <?php endif; ?>
                                    <span id="preview-logo-text-light" style="font-weight:700; font-size:1.1rem; color:#3c342e; display:<?php echo ($cur_logo_type === 'image') ? 'none' : 'inline'; ?>;"><?php echo htmlspecialchars($site_title); ?></span>
                                </div>
                                <span style="margin-left:auto; font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase;">Hell</span>
                            </div>

                            <!-- Dunkle Box -->
                            <div style="background:#161311; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:14px 18px; display:flex; align-items:center; gap:12px; min-height:70px; box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                                <div id="preview-logo-container-dark" style="display:flex; align-items:center; gap:10px;">
                                    <?php if (!empty($cur_logo_image)): ?>
                                        <img src="<?php echo htmlspecialchars($cur_logo_image); ?>" id="preview-logo-img-dark" alt="Logo" style="max-height:<?php echo $cur_logo_max_height; ?>px; width:auto; object-fit:contain;">
                                    <?php else: ?>
                                        <div id="preview-logo-placeholder-dark" style="width:40px; height:40px; background:rgba(255,255,255,0.1); border:1px dashed rgba(255,255,255,0.2); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#beb1a6; font-size:1.2rem;">🖼️</div>
                                    <?php endif; ?>
                                    <span id="preview-logo-text-dark" style="font-weight:700; font-size:1.1rem; color:#f5eee8; display:<?php echo ($cur_logo_type === 'image') ? 'none' : 'inline'; ?>;"><?php echo htmlspecialchars($site_title); ?></span>
                                </div>
                                <span style="margin-left:auto; font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Dunkel</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Favicon-Verwaltung -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">tab</span> Favicon &amp; App-Icon (Browser-Reiter)
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Das Favicon erscheint im Browser-Tab, in Lesezeichen und als Touch-Icon auf Mobilgeräten. Unterstützt werden <strong>.ico</strong>, <strong>.svg</strong> oder <strong>.png</strong>.
                    </p>

                    <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
                        <div style="flex:1; min-width:260px;">
                            <input type="file" name="favicon_file" accept=".ico,.svg,.png" class="form-control" style="background:#fff; padding:8px 12px;">
                            <?php if (!empty($cur_favicon)): ?>
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; color:#ef4444; font-weight:600; font-size:0.85rem; margin-top:10px;">
                                    <input type="checkbox" name="delete_favicon" value="1">
                                    <span>Favicon entfernen / zurücksetzen</span>
                                </label>
                            <?php endif; ?>
                        </div>

                        <!-- Browser Tab Mockup -->
                        <div style="background:#e2e8f0; border-radius:12px 12px 0 0; padding:8px 12px 0 12px; display:inline-flex; align-items:flex-end;">
                            <div style="background:#ffffff; border-radius:8px 8px 0 0; padding:7px 16px; display:flex; align-items:center; gap:8px; box-shadow:0 -1px 3px rgba(0,0,0,0.05);">
                                <?php if (!empty($cur_favicon)): ?>
                                    <img src="<?php echo htmlspecialchars($cur_favicon); ?>" alt="Favicon" style="width:16px; height:16px; object-fit:contain;">
                                <?php else: ?>
                                    <span style="font-size:14px; line-height:1;">🌿</span>
                                <?php endif; ?>
                                <span style="font-size:0.82rem; font-weight:600; color:#1e293b; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($site_title); ?></span>
                                <span class="material-icons" style="font-size:14px; color:#94a3b8; margin-left:4px;">close</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. SVG-Tatze & Farb-Einstellungen (nur bei Motif aktiv) -->
                <div id="logo-motif-settings-box" style="display: <?php echo ($cur_logo_type === 'motif') ? 'block' : 'none'; ?>; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">pets</span> SVG-Hundetatzen-Motiv auswählen
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Wähle das passende SVG-Naturmotiv für deine Website:
                    </p>

                    <!-- Motif Selection Grid -->
                    <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
                        <!-- Motif 1: Paw Simple -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_simple') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_simple')">
                            <input type="radio" name="hero_logo_motif" value="paw_simple" <?php echo ($cur_logo_motif === 'paw_simple') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_simple', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Klassische Hundetatze</h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                Klassischer Pfotenabdruck mit 4 Zehen.
                            </p>
                        </label>

                        <!-- Motif 2: Paw Branch (Naturzweig) -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_beach') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_beach')">
                            <input type="radio" name="hero_logo_motif" value="paw_beach" <?php echo ($cur_logo_motif === 'paw_beach') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_beach', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Tatze &amp; Naturzweig</h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                Dezenter, geschwungener Zweig darunter.
                            </p>
                        </label>

                        <!-- Motif 3: Paw Willow (Eleganter Weidenzweig) -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_willow') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_willow')">
                            <input type="radio" name="hero_logo_motif" value="paw_willow" <?php echo ($cur_logo_motif === 'paw_willow') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_willow', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Tatze &amp; Weidenast</h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                Kunstvoller Weidenast mit Blättern.
                            </p>
                        </label>

                        <!-- Motif 4: None -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'none') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'none')">
                            <input type="radio" name="hero_logo_motif" value="none" <?php echo ($cur_logo_motif === 'none') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; height: 44px; font-size:1.6rem; color:var(--text-secondary); opacity:0.5;">
                                🚫
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Kein Motiv</h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                Kein SVG-Motiv verwenden.
                            </p>
                        </label>
                    </div>

                    <!-- Color Selection & Chips -->
                    <div style="background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:1rem;">
                        <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block; font-size:0.88rem;">
                            🎨 Farbe des SVG-Motivs
                        </label>
                        <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="color" id="hero_logo_color" name="hero_logo_color" value="<?php echo htmlspecialchars($cur_logo_color); ?>" oninput="updateLogoColor(this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="color_code_display" style="font-family:monospace; font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?php echo htmlspecialchars($cur_logo_color); ?></span>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(178,108,57,0.3); color:#b26c39; font-weight:600;" onclick="setColorPreset('#b26c39')">🌿 Weiden-Braun</button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(46,98,72,0.3); color:#2e6248; font-weight:600;" onclick="setColorPreset('#2e6248')">🌲 Wald-Grün</button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(217,119,6,0.3); color:#d97706; font-weight:600;" onclick="setColorPreset('#d97706')">☀️ Bernstein</button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(60,52,46,0.3); color:#3c342e; font-weight:600;" onclick="setColorPreset('#3c342e')">☕ Anthrazit</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Logo Position Section -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    📍 Position des Logos / Icons
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height:1.4;">
                    Bestimme, wo das Logo bzw. das Icon platziert werden soll:
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2.5rem;">
                    <!-- Pos 1: Header Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_before')">
                        <input type="radio" name="logo_position" value="header_before" <?php echo ($cur_logo_pos === 'header_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_left</span> <span>Header: Links vor Titel</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Header: Vor Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Klassisch links vor dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 2: Header After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_after')">
                        <input type="radio" name="logo_position" value="header_after" <?php echo ($cur_logo_pos === 'header_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span>Header: Rechts nach Titel</span> <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_right</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Header: Nach Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Rechts direkt hinter dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 3: Hero Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_before')">
                        <input type="radio" name="logo_position" value="hero_before" <?php echo ($cur_logo_pos === 'hero_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span class="material-icons" style="font-size:18px; color:var(--accent);">flag</span> <span>Hero: Vor Hauptüberschrift</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Hero: Vor dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Zentriert vor der großen Hauptüberschrift auf der Startseite.
                        </p>
                    </label>

                    <!-- Pos 4: Hero After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_after')">
                        <input type="radio" name="logo_position" value="hero_after" <?php echo ($cur_logo_pos === 'hero_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span>Hero: Nach Hauptüberschrift</span> <span class="material-icons" style="font-size:18px; color:var(--accent);">flag</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);">Hero: Nach dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Zentriert direkt hinter der großen Hauptüberschrift.
                        </p>
                    </label>
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
                     WEBSITE-ARCHITEKTUR & SEITEN-STRUKTUR (PHASE 18)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">account_tree</span> Website-Architektur &amp; Seiten-Struktur
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Bestimme hier, wie deine Website strukturiert ist. Du kannst jederzeit nahtlos zwischen modernem One-Pager und einer klassischen Multi-Page Website wechseln:
                </p>

                <?php 
                    $cur_arch = SectionManager::getSiteArchitecture($data);
                ?>

                <div class="layout-options-grid" style="margin-bottom: 2rem;">
                    <!-- Option 1: One-Pager (Default) -->
                    <label class="layout-card <?php echo ($cur_arch === 'onepage') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="site_architecture" value="onepage" <?php echo ($cur_arch === 'onepage') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="layout-badge">⭐ Standard (One-Pager)</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">One-Pager</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Alle aktiven Inhaltsbereiche (Galerie, Workshops, Blog, Downloads etc.) liegen fortlaufend auf der Startseite. Die Menüpunkte springen sanft per Anker (#sektion) dorthin.
                        </p>
                    </label>

                    <!-- Option 2: Multi-Page -->
                    <label class="layout-card <?php echo ($cur_arch === 'multipage') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="site_architecture" value="multipage" <?php echo ($cur_arch === 'multipage') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(59, 130, 246, 0.15); color:#2563eb;">Klassisch</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Klassische Multi-Page Website</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Startseite als kompakte Begrüßung / Teaser. Hauptbereiche (Galerie, Workshops, Blog, Downloads, Shop, Stammbaum) erhalten eigene URLs (/?page=galerie etc.) und werden als eigenständige Unterseiten aufgerufen.
                        </p>
                    </label>

                    <!-- Option 3: Hybrid -->
                    <label class="layout-card <?php echo ($cur_arch === 'hybrid') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="site_architecture" value="hybrid" <?php echo ($cur_arch === 'hybrid') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(217, 119, 6, 0.15); color:var(--accent);">Flexibel</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Individuell / Hybrid</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Volle Freiheit: Im Sektionen-Manager kann für jede Sektion einzeln festgelegt werden, ob sie auf der Startseite, als Einzelseite oder auf beiden erscheinen soll.
                        </p>
                    </label>
                </div>

                <!-- ==========================================================
                     MENÜ-STIL, NAVIGATION & POSITION (PHASE 19)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">menu_open</span> Menü-Aufbau &amp; Navigations-Stil
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Wähle hier die Gestaltungsart und Position deiner Website-Navigation. Bei der vertikalen Sidebar-Navigation klappt das Menü auf Mobilgeräten (&lt;= 992px) automatisch als kompakte Kopfzeile mit Slide-in-Drawer ein.
                </p>

                <?php 
                    $cur_menu_style = ThemeManager::getActiveMenuStyle($data);
                    $cur_sidebar_pos = ThemeManager::getSidebarPosition($data);
                    $available_menu_types = ThemeManager::getMenuTypes($data);
                ?>

                <div class="layout-options-grid" style="margin-bottom: 1.5rem;">
                    <?php foreach ($available_menu_types as $mKey => $mInfo): ?>
                        <label class="layout-card <?php echo ($cur_menu_style === $mKey) ? 'selected' : ''; ?>" onclick="selectMenuStyleCard(this, '<?php echo htmlspecialchars($mKey); ?>')">
                            <input type="radio" name="menu_style" value="<?php echo htmlspecialchars($mKey); ?>" <?php echo ($cur_menu_style === $mKey) ? 'checked' : ''; ?>>
                            <div>
                                <span class="layout-badge" <?php if (!empty($mInfo['badge']) && $mInfo['badge'] === 'Standard') echo 'style="background:rgba(46,98,72,0.15); color:var(--primary);"'; ?>>
                                    <?php echo htmlspecialchars($mInfo['badge'] ?? 'Layout'); ?>
                                </span>
                            </div>
                            <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="color:var(--primary); font-size:22px;"><?php echo htmlspecialchars($mInfo['icon'] ?? 'menu'); ?></span>
                                <span><?php echo htmlspecialchars($mInfo['title']); ?></span>
                            </h4>
                            <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                                <?php echo htmlspecialchars($mInfo['description']); ?>
                            </p>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Zusätzliche Sidebar-Optionen (erscheinen wenn vertikale Sidebar gewählt ist) -->
                <div id="sidebar-position-options" style="background:rgba(46,98,72,0.04); border:1px solid rgba(46,98,72,0.22); border-radius:12px; padding:1.25rem; margin-bottom:2rem; <?php echo ($cur_menu_style === 'vertical_sidebar') ? 'display:block;' : 'display:none;'; ?>">
                    <span style="font-weight:700; font-size:0.98rem; color:var(--text-primary); display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span class="material-icons" style="font-size:18px; color:var(--primary);">vertical_split</span> Ausrichtung der vertikalen Sidebar:
                    </span>
                    <p style="font-size:0.86rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.4;">
                        Lege fest, an welchem Bildschirmrand die feste Seitenleiste auf Desktop-Displays andocken soll:
                    </p>
                    <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; font-weight:600;">
                            <input type="radio" name="sidebar_position" value="left" <?php echo ($cur_sidebar_pos === 'left') ? 'checked' : ''; ?>>
                            <span>Links fixiert (Standard)</span>
                        </label>
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; font-weight:600;">
                            <input type="radio" name="sidebar_position" value="right" <?php echo ($cur_sidebar_pos === 'right') ? 'checked' : ''; ?>>
                            <span>Rechts fixiert</span>
                        </label>
                    </div>
                </div>

                <!-- ==========================================================
                     BESUCHER-BARRIEREFREIHEITSLEISTE (PHASE 16)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">accessibility_new</span> Besucher-Barrierefreiheit &amp; Ansicht
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Ermögliche deinen Website-Besuchern maximalen Lesekomfort durch dezente Bedienelemente direkt in der Navigation.
                </p>

                <?php 
                    $cur_a11y_bar = !empty($data['settings']['accessibility_bar_enabled']);
                ?>
                <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem;">
                    <label style="display:inline-flex; align-items:flex-start; gap:12px; cursor:pointer;">
                        <input type="checkbox" name="accessibility_bar_enabled" value="1" <?php echo $cur_a11y_bar ? 'checked' : ''; ?> style="width:20px; height:20px; margin-top:2px; accent-color:var(--primary);">
                        <div>
                            <span style="font-weight:700; font-size:1rem; color:var(--text-primary); display:block;">
                                Besucher-Barrierefreiheit &amp; Ansicht im Menü anzeigen
                            </span>
                            <span style="font-size:0.86rem; color:var(--text-secondary); line-height:1.45; display:block; margin-top:4px;">
                                Bindet dezent in die Navigation (neben der Sprachauswahl) Bedienelemente für Live Hell-/Dunkelmodus (🌙/☀️ mit Tooltip) und stufenlosen Schriftgrößen-Zoom (A- / A+) ein. Einstellungen der Besucher werden automatisch im Browser gespeichert.
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
