<?php
/**
 * WeaveCMS – Admin Tab: Design (Logo, Branding, Favicon, Motive, Farbthemen, Seitenbreite, Typografie)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION: DESIGN ──────────────── -->
        <div id="tab-design" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:28px;">brush</span> <?php echo __a('admin.design.tab_title', 'Design &amp; Erscheinungsbild'); ?>
            </h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_design">
                <input type="hidden" name="target_tab" value="tab-design">

                <!-- 1. Website Logo & Branding Section (Phase 11) -->
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
                <h3 style="font-size: 1.25rem; margin-top: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">branding_watermark</span> <?php echo __a('admin.design.logo_section_title', 'Website-Logo, Branding &amp; Favicon'); ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    <?php echo __a('admin.design.logo_section_desc', 'Gestalte die visuelle Identität deines Webauftritts. Wähle deinen bevorzugten Logo-Typ, lade ein eigenes Bild- oder SVG-Logo hoch, skaliere die Höhe stufenlos und hinterlege ein Favicon für Browser-Tabs und Smartphone-Homescreens.'); ?>
                </p>

                <!-- Logo-Typ Auswahl -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block;">
                    <?php echo __a('admin.design.logo_type_label', '🏷️ Logo-Typ auswählen'); ?>
                </label>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 1.5rem;">
                    <!-- Typ 1: Reines Text-Logo -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'text') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'text')">
                        <input type="radio" name="logo_type" value="text" <?php echo ($cur_logo_type === 'text') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">title</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.type_text', 'Reines Text-Logo'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.type_text_desc', 'Klassische typografische Darstellung deines Namens/Ateliers ohne Grafik.'); ?>
                        </p>
                    </label>

                    <!-- Typ 2: Eigenes Bild- / SVG-Logo -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'image') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'image')">
                        <input type="radio" name="logo_type" value="image" <?php echo ($cur_logo_type === 'image') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">image</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.type_image', 'Eigenes Bild-Logo'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.type_image_desc', 'Vollständiges Firmen- oder Vereinslogo (SVG, PNG, WebP) als Header-Grafik.'); ?>
                        </p>
                    </label>

                    <!-- Typ 3: Icon + Text kombiniert -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'icon_text') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'icon_text')">
                        <input type="radio" name="logo_type" value="icon_text" <?php echo ($cur_logo_type === 'icon_text') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">loyalty</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.type_icon_text', 'Icon &amp; Text kombiniert'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.type_icon_text_desc', 'Kompaktes Logo-Icon links neben deinem Firmen- oder Ateliernamen.'); ?>
                        </p>
                    </label>

                    <!-- Typ 4: SVG-Tatze -->
                    <label class="layout-card <?php echo ($cur_logo_type === 'motif') ? 'selected' : ''; ?>" onclick="selectLogoType(this, 'motif')">
                        <input type="radio" name="logo_type" value="motif" <?php echo ($cur_logo_type === 'motif') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 12px 0; font-size: 1.5rem; color: var(--primary);">
                            <span class="material-icons" style="font-size: 32px;">pets</span>
                        </div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.type_motif', 'SVG-Naturmotiv'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.type_motif_desc', 'WeaveCMS Hundetatzen- und Naturmotive mit anpassbarer Farbe.'); ?>
                        </p>
                    </label>
                </div>

                <!-- Eigenes Logo Upload & Skalierung -->
                <div id="logo-image-settings-box" style="display: <?php echo in_array($cur_logo_type, ['image', 'icon_text'], true) ? 'block' : 'none'; ?>; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">upload_file</span> <?php echo __a('admin.design.upload_box_title', 'Eigenes Logo hochladen &amp; skalieren'); ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        <?php echo __a('admin.design.upload_box_desc', 'Unterstützte Dateiformate: SVG (empfohlen für gestochenscharfe Vektoren), PNG (mit Transparenz), WebP oder JPG.'); ?>
                    </p>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; align-items:start;">
                        <!-- Upload & Slider -->
                        <div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:6px;"><?php echo __a('admin.design.upload_input_label', 'Neue Logo-Datei auswählen:'); ?></label>
                                <input type="file" name="logo_file" id="logo_file_input" accept=".svg,.png,.webp,.jpg,.jpeg,.gif" class="form-control" onchange="previewLogoFile(this)" style="background:#fff; padding:8px 12px;">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <label style="font-weight:600; font-size:0.88rem; margin:0;"><?php echo __a('admin.design.logo_height_label', 'Maximale Logo-Höhe im Header:'); ?></label>
                                    <span id="logo_height_display" style="font-family:monospace; font-weight:700; color:var(--primary); font-size:0.95rem;"><?php echo $cur_logo_max_height; ?> px</span>
                                </div>
                                <input type="range" name="logo_max_height" id="logo_max_height_range" min="24" max="100" value="<?php echo $cur_logo_max_height; ?>" class="form-control" oninput="updateLogoMaxHeight(this.value)" style="padding:0; height:auto; cursor:pointer;">
                                <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-top:4px;"><?php echo __a('admin.design.logo_height_hint', 'Die Breite skaliert automatisch proportional mit, sodass dein Logo niemals verzerrt wird.'); ?></small>
                            </div>

                            <?php if (!empty($cur_logo_image)): ?>
                                <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:10px 14px; margin-top:1rem;">
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; color:#ef4444; font-weight:600; font-size:0.88rem; margin:0;">
                                        <input type="checkbox" name="delete_logo" value="1">
                                        <span><?php echo __a('admin.design.delete_logo', 'Aktuelles Logo entfernen / löschen'); ?></span>
                                    </label>
                                    <small style="display:block; color:var(--text-secondary); font-size:0.78rem; margin-top:3px; margin-left:24px;"><?php echo __a('admin.design.path_label', 'Pfad: {path}', ['path' => htmlspecialchars($cur_logo_image)]); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Live-Vorschau Hell & Dunkel -->
                        <div>
                            <label style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:8px;"><?php echo __a('admin.design.preview_label', 'Live-Vorschau (Hell- &amp; Dunkelmodus):'); ?></label>
                            
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
                                <span style="margin-left:auto; font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase;"><?php echo __a('admin.design.preview_light', 'Hell'); ?></span>
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
                                <span style="margin-left:auto; font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;"><?php echo __a('admin.design.preview_dark', 'Dunkel'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Favicon-Verwaltung -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">tab</span> <?php echo __a('admin.design.favicon_title', 'Favicon &amp; App-Icon (Browser-Reiter)'); ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        <?php echo __a('admin.design.favicon_desc', 'Das Favicon erscheint im Browser-Tab, in Lesezeichen und als Touch-Icon auf Mobilgeräten. Unterstützt werden .ico, .svg oder .png.'); ?>
                    </p>

                    <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
                        <div style="flex:1; min-width:260px;">
                            <input type="file" name="favicon_file" accept=".ico,.svg,.png" class="form-control" style="background:#fff; padding:8px 12px;">
                            <?php if (!empty($cur_favicon)): ?>
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; color:#ef4444; font-weight:600; font-size:0.85rem; margin-top:10px;">
                                    <input type="checkbox" name="delete_favicon" value="1">
                                    <span><?php echo __a('admin.design.delete_favicon', 'Favicon entfernen / zurücksetzen'); ?></span>
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

                <!-- SVG-Tatze & Farb-Einstellungen (nur bei Motif aktiv) -->
                <div id="logo-motif-settings-box" style="display: <?php echo ($cur_logo_type === 'motif') ? 'block' : 'none'; ?>; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 20px;">pets</span> <?php echo __a('admin.design.motif_title', 'SVG-Hundetatzen-Motiv auswählen'); ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        <?php echo __a('admin.design.motif_desc', 'Wähle das passende SVG-Naturmotiv für deine Website:'); ?>
                    </p>

                    <!-- Motif Selection Grid -->
                    <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
                        <!-- Motif 1: Paw Simple -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_simple') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_simple')">
                            <input type="radio" name="hero_logo_motif" value="paw_simple" <?php echo ($cur_logo_motif === 'paw_simple') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_simple', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.motif_paw_simple', 'Klassische Hundetatze'); ?></h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                <?php echo __a('admin.design.motif_paw_simple_desc', 'Klassischer Pfotenabdruck mit 4 Zehen.'); ?>
                            </p>
                        </label>

                        <!-- Motif 2: Paw Branch (Naturzweig) -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_beach') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_beach')">
                            <input type="radio" name="hero_logo_motif" value="paw_beach" <?php echo ($cur_logo_motif === 'paw_beach') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_beach', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.motif_paw_beach', 'Tatze &amp; Naturzweig'); ?></h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                <?php echo __a('admin.design.motif_paw_beach_desc', 'Dezenter, geschwungener Zweig darunter.'); ?>
                            </p>
                        </label>

                        <!-- Motif 3: Paw Willow (Eleganter Weidenzweig) -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'paw_willow') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_willow')">
                            <input type="radio" name="hero_logo_motif" value="paw_willow" <?php echo ($cur_logo_motif === 'paw_willow') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                                <?php echo render_paw_logo('paw_willow', $cur_logo_color, 44); ?>
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.motif_paw_willow', 'Tatze &amp; Weidenast'); ?></h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                <?php echo __a('admin.design.motif_paw_willow_desc', 'Kunstvoller Weidenast mit Blättern.'); ?>
                            </p>
                        </label>

                        <!-- Motif 4: None -->
                        <label class="layout-card <?php echo ($cur_logo_motif === 'none') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'none')">
                            <input type="radio" name="hero_logo_motif" value="none" <?php echo ($cur_logo_motif === 'none') ? 'checked' : ''; ?>>
                            <div style="display:flex; justify-content:center; align-items:center; height: 44px; font-size:1.6rem; color:var(--text-secondary); opacity:0.5;">
                                🚫
                            </div>
                            <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.motif_none', 'Kein Motiv'); ?></h4>
                            <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                                <?php echo __a('admin.design.motif_none_desc', 'Kein SVG-Motiv verwenden.'); ?>
                            </p>
                        </label>
                    </div>

                    <!-- Color Selection & Chips -->
                    <div style="background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:1rem;">
                        <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block; font-size:0.88rem;">
                            <?php echo __a('admin.design.motif_color_label', '🎨 Farbe des SVG-Motivs'); ?>
                        </label>
                        <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="color" id="hero_logo_color" name="hero_logo_color" value="<?php echo htmlspecialchars($cur_logo_color); ?>" oninput="updateLogoColor(this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="color_code_display" style="font-family:monospace; font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?php echo htmlspecialchars($cur_logo_color); ?></span>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(178,108,57,0.3); color:#b26c39; font-weight:600;" onclick="setColorPreset('#b26c39')"><?php echo __a('admin.design.color_preset_willow', '🌿 Weiden-Braun'); ?></button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(46,98,72,0.3); color:#2e6248; font-weight:600;" onclick="setColorPreset('#2e6248')"><?php echo __a('admin.design.color_preset_forest', '🌲 Wald-Grün'); ?></button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(217,119,6,0.3); color:#d97706; font-weight:600;" onclick="setColorPreset('#d97706')"><?php echo __a('admin.design.color_preset_amber', '☀️ Bernstein'); ?></button>
                                <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color:rgba(60,52,46,0.3); color:#3c342e; font-weight:600;" onclick="setColorPreset('#3c342e')"><?php echo __a('admin.design.color_preset_charcoal', '☕ Anthrazit'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo Position Section -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    <?php echo __a('admin.design.position_label', '📍 Position des Logos / Icons'); ?>
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height:1.4;">
                    <?php echo __a('admin.design.position_desc', 'Bestimme, wo das Logo bzw. das Icon platziert werden soll:'); ?>
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2.5rem;">
                    <!-- Pos 1: Header Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_before')">
                        <input type="radio" name="logo_position" value="header_before" <?php echo ($cur_logo_pos === 'header_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_left</span> <span><?php echo __a('admin.design.pos_header_before', 'Header: Links vor Titel'); ?></span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.pos_header_before_title', 'Header: Vor Seitentitel'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.pos_header_before_desc', 'Klassisch links vor dem Website-Namen in der Kopfzeile.'); ?>
                        </p>
                    </label>

                    <!-- Pos 2: Header After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_after')">
                        <input type="radio" name="logo_position" value="header_after" <?php echo ($cur_logo_pos === 'header_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span><?php echo __a('admin.design.pos_header_after', 'Header: Rechts nach Titel'); ?></span> <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_right</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.pos_header_after_title', 'Header: Nach Seitentitel'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.pos_header_after_desc', 'Rechts direkt hinter dem Website-Namen in der Kopfzeile.'); ?>
                        </p>
                    </label>

                    <!-- Pos 3: Hero Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_before')">
                        <input type="radio" name="logo_position" value="hero_before" <?php echo ($cur_logo_pos === 'hero_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span class="material-icons" style="font-size:18px; color:var(--accent);">flag</span> <span><?php echo __a('admin.design.pos_hero_before', 'Hero: Vor Hauptüberschrift'); ?></span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.pos_hero_before_title', 'Hero: Vor dem Titel'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.pos_hero_before_desc', 'Zentriert vor der großen Hauptüberschrift auf der Startseite.'); ?>
                        </p>
                    </label>

                    <!-- Pos 4: Hero After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_after')">
                        <input type="radio" name="logo_position" value="hero_after" <?php echo ($cur_logo_pos === 'hero_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span><?php echo __a('admin.design.pos_hero_after', 'Hero: Nach Hauptüberschrift'); ?></span> <span class="material-icons" style="font-size:18px; color:var(--accent);">flag</span>
                        </div>
                        <h4 style="margin:0; font-size:0.95rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.pos_hero_after_title', 'Hero: Nach dem Titel'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.pos_hero_after_desc', 'Zentriert direkt hinter der großen Hauptüberschrift.'); ?>
                        </p>
                    </label>
                </div>

                <!-- 2. Seiten-Layout & Design-System (Hybrid-Modell) -->
                <h3 style="font-size: 1.25rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">palette</span> <?php echo __a('admin.design.system_title', 'Seiten-Layout &amp; Design-System'); ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    <?php echo __a('admin.design.system_desc', 'Passe das optische Erscheinungsbild deiner Website an. Wähle eines der harmonischen Natur-Farbthemen (1-Klick) oder stimme Farben, Seitenbreite und Schriftgrößen ganz nach deinen Wünschen fein ab.'); ?>
                </p>

                <?php
                    $cur_theme = $data['settings']['design_theme'] ?? 'nature_classic';
                    $cur_width = $data['settings']['container_width'] ?? '1200px';
                    $cur_primary = $data['settings']['primary_color'] ?? '#2e6248';
                    $cur_accent = $data['settings']['accent_color'] ?? '#b26c39';
                    $cur_bg = $data['settings']['bg_color'] ?? '#fbf9f6';
                    $cur_font_scale = $data['settings']['font_scale'] ?? '100%';
                ?>

                <!-- 1-Klick Natur-Themes -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block;">
                    <?php echo __a('admin.design.themes_label', '🌿 1. Natur-Farbthemen (1-Klick Vorlagen)'); ?>
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    <?php echo __a('admin.design.themes_desc', 'Beim Klick auf ein Farbthema werden die passenden Primär-, Akzent- und Hintergrundfarben automatisch vorausgewählt.'); ?>
                </p>

                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- Theme 1: Weidenglück (Standard) -->
                    <label class="layout-card <?php echo ($cur_theme === 'nature_classic') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nature_classic')">
                        <input type="radio" name="design_theme" value="nature_classic" <?php echo ($cur_theme === 'nature_classic') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#2e6248;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_forest', 'Primär: Waldgrün')); ?>"></span>
                            <span class="theme-swatch" style="background:#b26c39;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_willow', 'Akzent: Weidenton')); ?>"></span>
                            <span class="theme-swatch" style="background:#fbf9f6; border:1px solid rgba(0,0,0,0.15);" title="<?php echo htmlspecialchars(__a('admin.design.swatch_linen', 'Hintergrund: Leinenweiß')); ?>"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);"><?php echo __a('admin.design.theme_classic', 'Weidenglück'); ?> <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;"><?php echo __a('admin.design.badge_default', 'Standard'); ?></span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            <?php echo __a('admin.design.theme_classic_desc', 'Das bewährte Originaldesign: Sanftes Naturleinen, Waldgrün und warmer Weidenton.'); ?>
                        </p>
                    </label>

                    <!-- Theme 2: Moor & Bernstein -->
                    <label class="layout-card <?php echo ($cur_theme === 'moor_amber') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'moor_amber')">
                        <input type="radio" name="design_theme" value="moor_amber" <?php echo ($cur_theme === 'moor_amber') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#4a3f35;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_moss', 'Primär: Moosbraun')); ?>"></span>
                            <span class="theme-swatch" style="background:#d97706;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_amber', 'Akzent: Bernstein')); ?>"></span>
                            <span class="theme-swatch" style="background:#f7f4ee; border:1px solid rgba(0,0,0,0.15);" title="<?php echo htmlspecialchars(__a('admin.design.swatch_sand', 'Hintergrund: Sandcreme')); ?>"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);"><?php echo __a('admin.design.theme_moor', 'Moor &amp; Bernstein'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            <?php echo __a('admin.design.theme_moor_desc', 'Warme Erdtöne: Sanfter Sandstrand, tiefes Moosbraun und sonniges Bernstein.'); ?>
                        </p>
                    </label>

                    <!-- Theme 3: Nordisch Klar -->
                    <label class="layout-card <?php echo ($cur_theme === 'nordic_sage') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nordic_sage')">
                        <input type="radio" name="design_theme" value="nordic_sage" <?php echo ($cur_theme === 'nordic_sage') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#3a6351;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_sage', 'Primär: Salbeigrün')); ?>"></span>
                            <span class="theme-swatch" style="background:#2c3e50;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_slate', 'Akzent: Schieferblau')); ?>"></span>
                            <span class="theme-swatch" style="background:#f4f6f5; border:1px solid rgba(0,0,0,0.15);" title="<?php echo htmlspecialchars(__a('admin.design.swatch_lightgrey', 'Hintergrund: Hellgrau')); ?>"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);"><?php echo __a('admin.design.theme_nordic', 'Nordisch Klar'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            <?php echo __a('admin.design.theme_nordic_desc', 'Ruhig und dezent: Skandinavisches Hellgrau, zartes Salbeigrün und Schiefer.'); ?>
                        </p>
                    </label>

                    <!-- Theme 4: Warmes Atelier -->
                    <label class="layout-card <?php echo ($cur_theme === 'terracotta_atelier') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'terracotta_atelier')">
                        <input type="radio" name="design_theme" value="terracotta_atelier" <?php echo ($cur_theme === 'terracotta_atelier') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#a85832;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_terracotta', 'Primär: Terrakotta')); ?>"></span>
                            <span class="theme-swatch" style="background:#322822;" title="<?php echo htmlspecialchars(__a('admin.design.swatch_espresso', 'Akzent: Espresso')); ?>"></span>
                            <span class="theme-swatch" style="background:#faf7f2; border:1px solid rgba(0,0,0,0.15);" title="<?php echo htmlspecialchars(__a('admin.design.swatch_ivory', 'Hintergrund: Elfenbein')); ?>"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);"><?php echo __a('admin.design.theme_terracotta', 'Warmes Atelier'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            <?php echo __a('admin.design.theme_terracotta_desc', 'Künstlerisch &amp; behaglich: Elfenbein, gebrannte Terrakotta und dunkles Holz.'); ?>
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
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);"><?php echo __a('admin.design.theme_custom', 'Individuell'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            <?php echo __a('admin.design.theme_custom_desc', 'Freie eigene Farbkomposition über die Farbwähler unten.'); ?>
                        </p>
                    </label>
                </div>

                <!-- 3. Freie Detailfarben -->
                <div class="form-group" style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem;">
                    <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                        <?php echo __a('admin.design.finetune_label', '🎨 2. Farb-Feinabstimmung (Farbwähler)'); ?>
                    </label>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        <?php echo __a('admin.design.finetune_desc', 'Hier kannst du die Einzelfarben bei Bedarf punktuell anpassen. Sobald eine Farbe manuell geändert wird, wird das Thema automatisch auf „Individuell“ gesetzt.'); ?>
                    </p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:1.25rem;">
                        <!-- Primary Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                <?php echo __a('admin.design.primary_color_title', 'Haupt- &amp; Markenfarbe'); ?>
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;"><?php echo __a('admin.design.primary_color_desc', 'Buttons, Links, aktive Filter'); ?></small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($cur_primary); ?>" oninput="onCustomColorChange('primary', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_primary_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_primary); ?></span>
                            </div>
                        </div>

                        <!-- Accent Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                <?php echo __a('admin.design.accent_color_title', 'Akzent- &amp; Highlightfarbe'); ?>
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;"><?php echo __a('admin.design.accent_color_desc', 'Kursüberschrift, Badges, Highlights'); ?></small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($cur_accent); ?>" oninput="onCustomColorChange('accent', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_accent_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_accent); ?></span>
                            </div>
                        </div>

                        <!-- Background Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                <?php echo __a('admin.design.bg_color_title', 'Seiten-Hintergrund'); ?>
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;"><?php echo __a('admin.design.bg_color_desc', 'Grundton der gesamten Website'); ?></small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($cur_bg); ?>" oninput="onCustomColorChange('bg', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_bg_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_bg); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Seitenbreite (Container Max-Width) -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    <?php echo __a('admin.design.width_label', '📐 3. Seitenbreite &amp; Inhaltsrahmen'); ?>
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    <?php echo __a('admin.design.width_desc', 'Legt fest, wie weit sich der Inhaltsbereich auf großen Bildschirmen maximal ausdehnen darf:'); ?>
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 1050px -->
                    <label class="layout-card <?php echo ($cur_width === '1050px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1050px" <?php echo ($cur_width === '1050px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">📖</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.width_compact_title', 'Kompakt (1050 px)'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.width_compact_desc', 'Fokussierter Lesefluss mit ruhiger, zentrierter Ästhetik.'); ?>
                        </p>
                    </label>

                    <!-- 1200px (Standard) -->
                    <label class="layout-card <?php echo ($cur_width === '1200px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1200px" <?php echo ($cur_width === '1200px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">✨</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.width_default_title', 'Standard (1200 px)'); ?> <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;"><?php echo __a('admin.design.badge_current', 'Aktuell'); ?></span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.width_default_desc', 'Ausgewogenes Verhältnis zwischen Bildwirkung und Lesbarkeit.'); ?>
                        </p>
                    </label>

                    <!-- 1400px -->
                    <label class="layout-card <?php echo ($cur_width === '1400px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1400px" <?php echo ($cur_width === '1400px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">🖼️</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.width_wide_title', 'Großzügig (1400 px)'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.width_wide_desc', 'Weitläufiges Studio-Layout für moderne, hochauflösende Displays.'); ?>
                        </p>
                    </label>
                </div>

                <!-- 5. Schriftgrößen & Typografie -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    <?php echo __a('admin.design.scale_label', '🔤 4. Schriftgrößen-Skalierung'); ?>
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    <?php echo __a('admin.design.scale_desc', 'Skaliert alle Texte, Überschriften und Zeilenhöhen fließend über das gesamte Layout.'); ?>
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 95% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '95%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="95%" <?php echo ($cur_font_scale === '95%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.15rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (95%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.scale_compact_title', 'Kompakt (~15 px)'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.scale_compact_desc', 'Etwas dezentere Typografie für zartere Optik.'); ?>
                        </p>
                    </label>

                    <!-- 100% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '100%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="100%" <?php echo ($cur_font_scale === '100%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.35rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (100%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.scale_default_title', 'Standard (16 px)'); ?> <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;"><?php echo __a('admin.design.badge_current', 'Aktuell'); ?></span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.scale_default_desc', 'Perfekt ausbalancierte Lesegröße nach Web-Standards.'); ?>
                        </p>
                    </label>

                    <!-- 108% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '108%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="108%" <?php echo ($cur_font_scale === '108%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.55rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (108%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);"><?php echo __a('admin.design.scale_large_title', 'Komfortabel (~17.5 px)'); ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            <?php echo __a('admin.design.scale_large_desc', 'Besonders augenschonend und leicht lesbar.'); ?>
                        </p>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> <?php echo __a('admin.design.btn_save', 'Design speichern'); ?>
                </button>
            </form>
        </div>
