<?php
/**
 * WeaveCMS – Admin Tab: Startseite, Sektionen-Manager & Texte
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION 1: HOMEPAGE & ABOUT TEXTS ──────────────── -->
        <div id="tab-texts" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 0.5rem;">Startseite &amp; Universelle Sektionstexte</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem;">
                Hier kannst du alle Überschriften, Untertitel und Einleitungstexte für die Hauptbereiche deiner Website zentral verwalten.
            </p>

            <!-- ==========================================================
                 SEKTIONS-MANAGER: REIHENFOLGE & SICHTBARKEIT (PHASE 6)
                 ========================================================== -->
            <?php 
                $all_sections = SectionManager::ensureSections($data);
                $sec_count = count($all_sections);
                $type_icons = [
                    'hero' => 'flag',
                    'gallery' => 'photo_library',
                    'courses' => 'event',
                    'past_courses' => 'photo_camera',
                    'blog' => 'newspaper',
                    'about' => 'badge',
                    'custom_text' => 'article'
                ];

                $active_langs = LanguageManager::getActiveLanguages($data);
                $primary_lang = LanguageManager::getPrimaryLanguage($data);
                $supported = LanguageManager::getSupportedLanguages();
            ?>
            <div style="background: rgba(255, 255, 255, 0.6); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h3 style="font-size: 1.15rem; color: var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                            <span class="material-icons">view_stream</span> Sektionen-Manager &amp; Seiten-Aufbau
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">
                            Passe die Anordnung, Sichtbarkeit und den <strong>Titel fürs Menü</strong> jeder Contentart an.
                        </p>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap: 10px;">
                    <?php foreach ($all_sections as $idx => $sec): ?>
                        <?php 
                            $is_vis = !empty($sec['visible']);
                            $icon = $type_icons[$sec['type']] ?? 'dashboard';
                        ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; background: #ffffff; border: 1px solid <?php echo $is_vis ? 'var(--border-color)' : 'rgba(239,68,68,0.2)'; ?>; border-radius: 12px; padding: 10px 16px; gap: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.02); <?php if (!$is_vis) echo 'opacity: 0.65;'; ?>">
                            <div style="display:flex; align-items:center; gap: 14px;">
                                <span style="font-weight:700; color:var(--text-secondary); font-size:0.85rem; width:20px; text-align:center;">#<?php echo ($idx + 1); ?></span>
                                <span class="material-icons" style="color:var(--primary); font-size:20px;"><?php echo $icon; ?></span>
                                <div>
                                    <div style="font-weight:600; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span><?php echo htmlspecialchars($sec['title']); ?></span>
                                        <?php if (!empty($sec['in_nav'])): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:2px 8px; border-radius:999px;">
                                                Menü: <strong><?php echo htmlspecialchars($sec['nav_label']); ?></strong>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:var(--text-secondary); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:999px;">
                                                Kein Menü-Link
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:var(--text-secondary);">
                                        Typ: <code style="font-family:monospace;"><?php echo htmlspecialchars($sec['type']); ?></code>
                                        <?php if (count($active_langs) > 1): ?>
                                            &bull; Übersetzungen:
                                            <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                                <?php $has_t = !empty($sec['translations'][$al]['nav_label']); ?>
                                                <span style="font-size:0.72rem; color:<?php echo $has_t ? '#10b981' : 'var(--text-secondary)'; ?>; margin-left:4px;">
                                                    <?php echo strtoupper($al) . ($has_t ? ' (✓)' : ''); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap: 8px;">
                                <!-- Bearbeiten Button -->
                                <button type="button" class="btn btn-outline" onclick="openSectionEditor(<?php echo htmlspecialchars(json_encode($sec), ENT_QUOTES, 'UTF-8'); ?>)" style="padding: 5px 10px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px;" title="Titel &amp; Menü-Beschriftung bearbeiten">
                                    <span class="material-icons" style="font-size:16px;">edit</span>
                                    <span>Bearbeiten</span>
                                </button>

                                <!-- Sichtbarkeit Toggle Form -->
                                <form method="POST" action="" style="display:inline; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="toggle_section_visibility">
                                    <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($sec['id']); ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:5px; <?php echo $is_vis ? 'color:#34d399; border-color:#34d399;' : 'color:var(--text-secondary);'; ?>" title="<?php echo $is_vis ? 'Sektion verbergen' : 'Sektion einblenden'; ?>">
                                        <span class="material-icons" style="font-size:16px;"><?php echo $is_vis ? 'visibility' : 'visibility_off'; ?></span>
                                        <?php echo $is_vis ? 'Sichtbar' : 'Ausgeblendet'; ?>
                                    </button>
                                </form>

                                <!-- Nach oben -->
                                <form method="POST" action="" style="display:inline; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="move_section">
                                    <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($sec['id']); ?>">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 8px; font-size:0.8rem;" <?php if ($idx === 0) echo 'disabled style="opacity:0.3; cursor:not-allowed;"'; ?> title="Nach oben verschieben">
                                        <span class="material-icons" style="font-size:16px;">arrow_upward</span>
                                    </button>
                                </form>

                                <!-- Nach unten -->
                                <form method="POST" action="" style="display:inline; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="move_section">
                                    <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($sec['id']); ?>">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 8px; font-size:0.8rem;" <?php if ($idx === $sec_count - 1) echo 'disabled style="opacity:0.3; cursor:not-allowed;"'; ?> title="Nach unten verschieben">
                                        <span class="material-icons" style="font-size:16px;">arrow_downward</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ──────────────── SEKTIONS- & MENÜTITEL-EDITOR ──────────────── -->
                <div id="section-editor-box" style="display:none; scroll-margin-top: 150px; background:#ffffff; border:2px solid var(--primary); border-radius:16px; padding:2rem; margin-top:1.5rem; box-shadow:0 12px 36px rgba(0,0,0,0.08); animation:fadeInTab 0.25s ease;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                        <h4 id="sec-editor-heading" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px; font-size:1.2rem; font-weight:700;">
                            <span class="material-icons">edit</span> Sektion &amp; Menü-Titel anpassen
                        </h4>
                        <button type="button" class="btn btn-outline" onclick="closeSectionEditor()" style="padding:4px 8px; font-size:0.8rem; border-radius:8px; display:inline-flex; align-items:center;" title="Schließen">
                            <span class="material-icons" style="font-size:16px;">close</span>
                        </button>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="update_section">
                        <input type="hidden" name="target_tab" value="tab-texts">
                        <input type="hidden" name="section_id" id="sec_edit_id" value="">

                        <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem; margin-bottom:1.25rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block; font-size:0.92rem;">
                                    Titel fürs Menü (Menü-Beschriftung) *
                                </label>
                                <input type="text" id="sec_edit_nav_label" name="nav_label" class="form-control" placeholder="z. B. Galerie" required style="background:#ffffff; border:1px solid rgba(62,50,40,0.22); color:var(--text-primary); border-radius:10px; padding:9px 12px; font-size:0.95rem;">
                                <small style="color:var(--text-secondary); font-size:0.82rem; display:block; margin-top:4px;">Erscheint in der Hauptnavigation auf Desktop &amp; Mobil.</small>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block; font-size:0.92rem;">
                                    Sektions-Titel / Überschrift
                                </label>
                                <input type="text" id="sec_edit_title" name="title" class="form-control" placeholder="z. B. Galerie &amp; Portfolio" style="background:#ffffff; border:1px solid rgba(62,50,40,0.22); color:var(--text-primary); border-radius:10px; padding:9px 12px; font-size:0.95rem;">
                                <small style="color:var(--text-secondary); font-size:0.82rem; display:block; margin-top:4px;">Bezeichnung der Sektion im CMS und auf der Website.</small>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:var(--text-primary); font-size:0.95rem;">
                                <input type="checkbox" id="sec_edit_in_nav" name="in_nav" value="1">
                                <span>Im Hauptmenü verlinken</span>
                            </label>
                        </div>

                        <?php if (count($active_langs) > 1): ?>
                            <div style="border:1px solid var(--border-color); background:rgba(251, 249, 246, 0.7); border-radius:12px; padding:1.25rem; margin-top:1.25rem; margin-bottom:1.5rem;">
                                <h5 style="font-size:0.92rem; font-weight:700; color:var(--text-primary); margin:0 0 1rem 0; display:flex; align-items:center; gap:6px;">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary);">translate</span> Menü-Titel für weitere aktive Sprachen (optional):
                                </h5>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
                                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                        <div class="form-group">
                                            <label style="font-size:0.85rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                                <span><?php echo $supported[$al]['flag']; ?></span>
                                                <span>Menü-Titel auf <?php echo htmlspecialchars($supported[$al]['name']); ?>:</span>
                                            </label>
                                            <input type="text" id="sec_trans_<?php echo $al; ?>_nav_label" name="trans_<?php echo $al; ?>_nav_label" class="form-control" placeholder="Menü-Titel auf <?php echo htmlspecialchars($supported[$al]['name']); ?>" style="background:#ffffff; border:1px solid rgba(62,50,40,0.22); color:var(--text-primary); border-radius:10px; padding:8px 12px;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button type="button" class="btn btn-outline" onclick="closeSectionEditor()" style="padding:8px 18px; font-size:0.9rem;">Abbrechen</button>
                            <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:0.9rem; display:inline-flex; align-items:center; gap:6px;">
                                <span class="material-icons" style="font-size:18px;">save</span> Sektion speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php 
                $edit_lang = isset($_GET['edit_lang']) && in_array($_GET['edit_lang'], $active_langs, true) ? $_GET['edit_lang'] : $primary_lang;
                $is_primary_edit = ($edit_lang === $primary_lang);
                $current_edit_texts = $is_primary_edit ? ($data['texts'] ?? []) : ($data['texts_i18n'][$edit_lang] ?? []);
                $primary_texts = $data['texts'] ?? [];
            ?>

            <?php if (count($active_langs) > 1): ?>
                <!-- Sprachumschalter für Redakteure im Text-Tab -->
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap; background:rgba(255,255,255,0.02); padding:12px 16px; border-radius:12px; border:1px solid var(--border-color);">
                    <span style="font-size:0.9rem; font-weight:700; color:var(--text-secondary); margin-right:6px; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-icons" style="font-size:18px;">translate</span> Sprache für Texte:
                    </span>
                    <?php foreach ($active_langs as $al): ?>
                        <?php $isActiveTab = ($al === $edit_lang); ?>
                        <a href="?edit_lang=<?php echo urlencode($al); ?>#tab-texts" class="btn <?php echo $isActiveTab ? 'btn-primary' : 'btn-outline'; ?>" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                            <span><?php echo $supported[$al]['flag']; ?></span>
                            <span><?php echo htmlspecialchars($supported[$al]['native']); ?></span>
                            <?php if ($al === $primary_lang): ?><small style="opacity:0.85; font-size:0.75rem;">(Standard)</small><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!$is_primary_edit): ?>
                    <div style="background:rgba(59, 130, 246, 0.08); border:1px solid #3b82f6; border-radius:10px; padding:10px 14px; margin-bottom:1.5rem; font-size:0.88rem; color:#1e40af; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:18px; color:#2563eb;">info</span>
                        <span>Du bearbeitest die Übersetzung für <strong><?php echo $supported[$edit_lang]['flag'] . ' ' . htmlspecialchars($supported[$edit_lang]['name']); ?></strong>. Leere Felder greifen automatisch auf den Text der Hauptsprache (<?php echo htmlspecialchars($supported[$primary_lang]['name']); ?>) zurück.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_texts">
                <input type="hidden" name="target_lang" value="<?php echo htmlspecialchars($edit_lang); ?>">
                
                <h3 style="font-size: 1.2rem; margin-top: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    🌿 Startseite &amp; Hero-Bereich
                </h3>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Hero-Titel (große Hauptüberschrift)</label>
                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['hero_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['hero_title'] ?? ''); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Hero-Untertitel</label>
                    <input type="text" name="hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['hero_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['hero_subtitle'] ?? ''); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    👤 Über Mich &amp; Meine Arbeit
                </h3>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Über Mich - Überschrift</label>
                    <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['about_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['about_title'] ?? ''); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Über Mich - Beschreibungstext</label>
                    <textarea name="about_text" class="form-control" rows="6" placeholder="<?php echo htmlspecialchars($primary_texts['about_text'] ?? ''); ?>" <?php if ($is_primary_edit) echo 'required'; ?>><?php echo htmlspecialchars($current_edit_texts['about_text'] ?? ''); ?></textarea>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    🖼️ Galerie &amp; Handarbeit
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Galerie - Titel</label>
                        <input type="text" name="gallery_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['gallery_title'] ?? ($is_primary_edit ? 'Galerie & Handarbeit' : '')); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['gallery_title'] ?? 'Galerie & Handarbeit'); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label>Galerie - Untertitel / Beschreibung</label>
                        <input type="text" name="gallery_subtitle" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['gallery_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['gallery_subtitle'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    📅 Kurse &amp; Workshops
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Kurse - Titel</label>
                        <input type="text" name="courses_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['courses_title'] ?? ($is_primary_edit ? 'Kurse & Workshops' : '')); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['courses_title'] ?? 'Kurse & Workshops'); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label>Kurse - Untertitel / Infotext</label>
                        <input type="text" name="courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['courses_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['courses_subtitle'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    📸 Impressionen vergangener Kurse
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Impressionen - Titel</label>
                        <input type="text" name="past_courses_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['past_courses_title'] ?? ($is_primary_edit ? 'Impressionen vergangener Kurse' : '')); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['past_courses_title'] ?? 'Impressionen vergangener Kurse'); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label>Impressionen - Untertitel (optional)</label>
                        <input type="text" name="past_courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['past_courses_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['past_courses_subtitle'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    📰 Blog &amp; Aktuelles
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Blog - Titel</label>
                        <input type="text" name="blog_title" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['blog_title'] ?? ($is_primary_edit ? 'Aktuelles & Geschichten' : '')); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['blog_title'] ?? 'Aktuelles & Geschichten'); ?>" <?php if ($is_primary_edit) echo 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label>Blog - Untertitel / Beschreibung</label>
                        <input type="text" name="blog_subtitle" class="form-control" value="<?php echo htmlspecialchars($current_edit_texts['blog_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['blog_subtitle'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    💬 Kontaktbereich (Seitenleiste &amp; Formular)
                </h3>
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Kontakt - Einleitungstext über dem Formular</label>
                    <textarea name="contact_intro_text" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars($primary_texts['contact_intro_text'] ?? ''); ?>"><?php echo htmlspecialchars($current_edit_texts['contact_intro_text'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> <?php echo $is_primary_edit ? 'Sektionstexte speichern' : 'Übersetzung (' . strtoupper($edit_lang) . ') speichern'; ?>
                </button>
            </form>
        </div>

        <script>
        const SEC_ACTIVE_LANGS = <?php echo json_encode($active_langs); ?>;
        const SEC_PRIMARY_LANG = <?php echo json_encode($primary_lang); ?>;

        function openSectionEditor(sec) {
            document.getElementById('section-editor-box').style.display = 'block';
            document.getElementById('sec_edit_id').value = sec.id || '';
            document.getElementById('sec_edit_title').value = sec.title || '';
            document.getElementById('sec_edit_nav_label').value = sec.nav_label || '';
            document.getElementById('sec_edit_in_nav').checked = !!sec.in_nav;
            document.getElementById('sec-editor-heading').innerHTML = '<span class="material-icons">edit</span> Menü-Titel anpassen: ' + (sec.title || sec.id);

            const trans = sec.translations || {};
            SEC_ACTIVE_LANGS.forEach(lang => {
                if (lang === SEC_PRIMARY_LANG) return;
                const field = document.getElementById('sec_trans_' + lang + '_nav_label');
                if (field) {
                    field.value = (trans[lang] && trans[lang].nav_label) ? trans[lang].nav_label : '';
                }
            });

            document.getElementById('section-editor-box').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('sec_edit_nav_label').focus();
        }

        function closeSectionEditor() {
            document.getElementById('section-editor-box').style.display = 'none';
        }
        </script>
