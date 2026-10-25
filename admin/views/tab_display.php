<?php
/**
 * WeaveCMS – Admin Tab: Darstellung (Galerie-Layout, Kacheln, Menü-Platzierung, Website-Architektur, Menü-Stil, Barrierefreiheit, Sprachen)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION: DARSTELLUNG ──────────────── -->
        <div id="tab-display" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:28px;">visibility</span> Darstellung
            </h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_display">
                <input type="hidden" name="target_tab" value="tab-display">

                <!-- 1. Galerie-Layout & Bilddarstellung -->
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

                <!-- 2. Galerie-Feinabstimmung (Ecken & Abstände) -->
                <?php
                    $cur_card_radius = $data['settings']['card_radius'] ?? '20px';
                    $cur_gallery_gap = $data['settings']['gallery_gap'] ?? '2rem';
                ?>
                <h3 style="font-size: 1.25rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">crop_free</span> Galerie-Kacheln: Eckenabrundung &amp; Raster-Abstand
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Stimme die Geometrie der Galerie-Karten und Werkstatt-Kacheln individuell ab:
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

                <!-- 3. Menü- & Trigger-Platzierung: Kontakt & Impressum -->
                <h3 style="font-size: 1.25rem; margin-top: 2.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">tune</span> Menü-Platzierung (Kontakt &amp; Impressum)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Bestimme hier, wo die Trigger für das Kontaktformular und das Impressum/Datenschutz-Slide-in auf deiner Website angezeigt werden sollen:
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

                <!-- 4. Website-Architektur & Seiten-Struktur (Phase 18) -->
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

                <!-- 5. Menü-Stil, Navigation & Position (Phase 19) -->
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

                <!-- 6. Besucher-Barrierefreiheitsleiste (Phase 16) -->
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

                <!-- 7. Mehrsprachigkeit (i18n) & Sprachenauswahl (Phase 8) -->
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

                <!-- Hauptsprache -->
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

                <!-- Aktivierte Sprachen -->
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
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Darstellung speichern
                </button>
            </form>
        </div>
