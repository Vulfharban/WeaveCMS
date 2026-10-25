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
                    'custom_text' => 'article',
                    'pricing' => 'sell',
                    'faq' => 'quiz',
                    'team' => 'groups',
                    'downloads' => 'file_download',
                    'webtrees' => 'account_tree',
                    'shop' => 'storefront'
                ];

                $active_langs = LanguageManager::getActiveLanguages($data);
                $primary_lang = LanguageManager::getPrimaryLanguage($data);
                $supported = LanguageManager::getSupportedLanguages();

                $active_sections = [];
                $pool_sections = [];
                foreach ($all_sections as $sec) {
                    if (!empty($sec['visible'])) {
                        $active_sections[] = $sec;
                    } else {
                        $pool_sections[] = $sec;
                    }
                }

                $render_sec_card = function($sec, $is_active, $idx) use ($type_icons, $active_langs, $primary_lang) {
                    $sec_id = htmlspecialchars($sec['id']);
                    $sec_type = htmlspecialchars($sec['type']);
                    $icon = !empty($sec['icon']) ? $sec['icon'] : ($type_icons[$sec['type']] ?? 'dashboard');
                    $title = htmlspecialchars($sec['title']);
                    $nav_label = htmlspecialchars($sec['nav_label'] ?? $sec['title']);
                    $desc = htmlspecialchars(SectionManager::getSectionTypeDescription($sec['type']));
                    $target_tab = SectionManager::getTargetTab($sec['type']);
                    $is_plugin = !empty($sec['plugin']) || in_array($sec['type'], ['webtrees', 'shop'], true);
                    $sec_place = $sec['placement'] ?? 'both';
                    $place_badge = [
                        'both' => ['label' => 'Start & Unterseite', 'bg' => 'rgba(16,185,129,0.1)', 'color' => '#10b981'],
                        'home' => ['label' => 'Nur Startseite', 'bg' => 'rgba(245,158,11,0.1)', 'color' => '#d97706'],
                        'page' => ['label' => 'Nur Unterseite', 'bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1']
                    ][$sec_place] ?? ['label' => 'Standard', 'bg' => 'rgba(0,0,0,0.05)', 'color' => 'var(--text-secondary)'];
                    $sec_json = htmlspecialchars(json_encode($sec), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="sec-card" 
                     id="sec-card-<?php echo $sec_id; ?>" 
                     data-sec-id="<?php echo $sec_id; ?>" 
                     data-sec-type="<?php echo $sec_type; ?>" 
                     data-is-active="<?php echo $is_active ? '1' : '0'; ?>"
                     draggable="true" 
                     ondragstart="handleSecDragStart(event)" 
                     ondragend="handleSecDragEnd(event)">
                    <div class="sec-card-header">
                        <div class="sec-card-left">
                            <span class="material-icons sec-drag-handle" title="Gedrückt halten &amp; ziehen">drag_indicator</span>
                            <span class="sec-badge-pos" style="font-weight:700; color:var(--text-secondary); font-size:0.82rem; min-width:20px; <?php if (!$is_active) echo 'display:none;'; ?>">#<?php echo ($idx + 1); ?></span>
                            <span class="material-icons" style="color:var(--primary); font-size:22px; flex-shrink:0;"><?php echo htmlspecialchars($icon); ?></span>
                            <div style="min-width:0; flex:1;">
                                <div class="sec-card-title">
                                    <span><?php echo $title; ?></span>
                                    <?php if ($is_plugin): ?>
                                        <span style="font-size:0.68rem; font-weight:700; color:#059669; background:rgba(5,150,105,0.1); padding:1px 6px; border-radius:999px;">Plugin</span>
                                    <?php endif; ?>
                                    <?php if (!empty($sec['in_nav'])): ?>
                                        <span style="font-size:0.68rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:1px 6px; border-radius:999px;">
                                            Menü: <?php echo $nav_label; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span style="font-size:0.68rem; font-weight:600; color:<?php echo $place_badge['color']; ?>; background:<?php echo $place_badge['bg']; ?>; padding:1px 6px; border-radius:999px;">
                                        <?php echo htmlspecialchars($place_badge['label']); ?>
                                    </span>
                                </div>
                                <div class="sec-card-desc"><?php echo $desc; ?></div>
                            </div>
                        </div>

                        <div class="sec-card-actions">
                            <!-- Schnell-Button für Inaktiv (Pool) -->
                            <button type="button" class="sec-card-btn btn-activate btn-activate-card" onclick="activateSectionFromPool('<?php echo $sec_id; ?>')" title="Aktivieren &amp; auf Website einbinden" style="<?php if ($is_active) echo 'display:none;'; ?>">
                                <span class="material-icons" style="font-size:15px;">add_circle_outline</span>
                                <span>Aktivieren</span>
                            </button>

                            <!-- Steuerung für Aktive Sektionen -->
                            <div class="sec-active-controls" style="display:<?php echo $is_active ? 'inline-flex' : 'none'; ?>; align-items:center; gap:4px;">
                                <!-- Pfeil hoch -->
                                <button type="button" class="sec-card-btn" onclick="moveActiveSectionCard('<?php echo $sec_id; ?>', 'up')" title="Nach oben verschieben">
                                    <span class="material-icons" style="font-size:15px;">arrow_upward</span>
                                </button>
                                <!-- Pfeil runter -->
                                <button type="button" class="sec-card-btn" onclick="moveActiveSectionCard('<?php echo $sec_id; ?>', 'down')" title="Nach unten verschieben">
                                    <span class="material-icons" style="font-size:15px;">arrow_downward</span>
                                </button>
                                <!-- Einstellungen -->
                                <button type="button" class="sec-card-btn" onclick="openSectionEditor(<?php echo $sec_json; ?>)" title="Titel &amp; Einstellungen anpassen">
                                    <span class="material-icons" style="font-size:15px;">settings</span>
                                </button>
                                <!-- Direktsprung zu Inhalten falls Subtab existiert -->
                                <?php if ($target_tab && $target_tab !== 'tab-texts'): ?>
                                    <button type="button" class="sec-card-btn" onclick="switchTab('<?php echo $target_tab; ?>')" title="Inhalte verwalten">
                                        <span class="material-icons" style="font-size:15px;">edit_note</span>
                                    </button>
                                <?php endif; ?>
                                <!-- Deaktivieren / In Pool ablegen -->
                                <button type="button" class="sec-card-btn btn-deactivate" onclick="deactivateSectionToPool('<?php echo $sec_id; ?>')" title="Deaktivieren &amp; in Pool ablegen">
                                    <span class="material-icons" style="font-size:15px;">remove_circle_outline</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }; ?>

            <div class="sec-pool-container">
                <script>
                    window.WEAVE_CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
                </script>

                <!-- Responsive Switcher für Tablets & Smartphones (<= 900px) -->
                <div class="sec-pool-mobile-switch">
                    <button type="button" class="btn btn-primary sec-view-tab-btn active" id="btn-show-active" onclick="switchPoolMobileTab('active')">
                        <span class="material-icons" style="font-size:16px;">view_stream</span> Aktive Sektionen (<span id="active-sec-badge-count"><?php echo count($active_sections); ?></span>)
                    </button>
                    <button type="button" class="btn btn-outline sec-view-tab-btn" id="btn-show-pool" onclick="switchPoolMobileTab('pool')">
                        <span class="material-icons" style="font-size:16px;">widgets</span> Modul-Pool (<span id="pool-sec-badge-count"><?php echo count($pool_sections); ?></span>)
                    </button>
                </div>

                <!-- 2-Spalten Modul-Board (Desktop: nebeneinander, Mobil/Tablet: umschaltbar) -->
                <div class="sec-pool-grid view-active" id="sec-pool-grid">
                    <!-- 1. SPALTE: VERFÜGBARER MODUL-POOL (INAKTIV) -->
                    <div class="sec-pool-col sec-col-pool" id="sec-col-pool">
                        <div class="sec-col-header">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="color:var(--primary); font-size:20px;">widgets</span>
                                <h3 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Modul-Pool (Inaktiv)</h3>
                            </div>
                            <span class="sec-count-badge" id="pool-count-label"><?php echo count($pool_sections); ?> im Pool</span>
                        </div>
                        <p class="sec-col-desc">
                            Nicht genutzte Seitenkategorien. Ziehe sie nach rechts oder tippe auf <strong>+ Aktivieren</strong>.
                        </p>
                        
                        <div class="sec-dropzone" id="dropzone-pool" ondragover="handleSecDragOver(event)" ondragleave="handleSecDragLeave(event)" ondrop="handleSecDrop(event, 'pool')">
                            <?php foreach ($pool_sections as $idx => $sec): ?>
                                <?php $render_sec_card($sec, false, $idx); ?>
                            <?php endforeach; ?>

                            <div class="sec-empty-state" id="pool-empty-state" style="<?php echo empty($pool_sections) ? 'display:flex;' : 'display:none;'; ?>">
                                <span class="material-icons" style="font-size:28px; opacity:0.4;">inventory_2</span>
                                <span>Alle Module sind aktuell aktiv auf deiner Website eingebunden.</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SPALTE: AKTIVE WEBSITE-INHALTE (LIVE) -->
                    <div class="sec-pool-col sec-col-active" id="sec-col-active">
                        <div class="sec-col-header">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="color:#10b981; font-size:20px;">check_circle</span>
                                <h3 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Aktive Seiten &amp; Sektionen (Live)</h3>
                            </div>
                            <span class="sec-count-badge active" id="active-count-label"><?php echo count($active_sections); ?> aktiv</span>
                        </div>
                        <p class="sec-col-desc">
                            Reihenfolge auf Startseite &amp; Menü. Ziehe Module nach oben/unten oder zurück in den Pool.
                        </p>

                        <div class="sec-dropzone" id="dropzone-active" ondragover="handleSecDragOver(event)" ondragleave="handleSecDragLeave(event)" ondrop="handleSecDrop(event, 'active')">
                            <?php foreach ($active_sections as $idx => $sec): ?>
                                <?php $render_sec_card($sec, true, $idx); ?>
                            <?php endforeach; ?>

                            <div class="sec-empty-state" id="active-empty-state" style="<?php echo empty($active_sections) ? 'display:flex;' : 'display:none;'; ?>">
                                <span class="material-icons" style="font-size:28px; opacity:0.4;">playlist_add</span>
                                <span>Keine aktiven Module. Ziehe Kategorien aus dem Pool hierher.</span>
                            </div>
                        </div>
                    </div>
                </div>
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

                        <div class="grid-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:1.5rem; align-items:start;">
                            <div class="form-group">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:var(--text-primary); font-size:0.95rem; margin-top:1.8rem;">
                                    <input type="checkbox" id="sec_edit_in_nav" name="in_nav" value="1">
                                    <span>Im Hauptmenü verlinken</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block; font-size:0.92rem;">
                                    Platzierung &amp; Anzeige
                                </label>
                                <select id="sec_edit_placement" name="placement" class="form-control" style="background:#ffffff; border:1px solid rgba(62,50,40,0.22); color:var(--text-primary); border-radius:10px; padding:9px 12px; font-size:0.95rem;">
                                    <option value="both">Startseite &amp; Eigene Einzelseite (Beides)</option>
                                    <option value="home">Nur auf Startseite</option>
                                    <option value="page">Nur als eigene Einzelseite (/?page=slug)</option>
                                </select>
                                <small style="color:var(--text-secondary); font-size:0.82rem; display:block; margin-top:4px;">Steuert, wo diese Sektion im Multi-Page- oder Hybrid-Modus ausgegeben wird.</small>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block; font-size:0.92rem;">
                                    Material-Icon (Symbol)
                                </label>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="text" id="sec_edit_icon" name="icon" class="form-control" placeholder="z. B. photo_library" style="background:#ffffff; border:1px solid rgba(62,50,40,0.22); color:var(--text-primary); border-radius:10px; padding:9px 12px; font-size:0.95rem;" oninput="updateSecIconPreview(this.value)">
                                    <span id="sec_edit_icon_preview" class="material-icons" style="font-size:24px; color:var(--primary); min-width:32px; text-align:center;">bookmark</span>
                                </div>
                                <small style="color:var(--text-secondary); font-size:0.82rem; display:block; margin-top:4px;">Google Material-Icon Name. Leer lassen für Standard.</small>
                            </div>
                        </div>

                        <?php if (count($active_langs) > 1): ?>
                            <div style="border:1px solid var(--border-color); background:rgba(251, 249, 246, 0.7); border-radius:12px; padding:1.25rem; margin-top:1.25rem; margin-bottom:1.5rem;">
                                <h5 style="font-size:0.92rem; font-weight:700; color:var(--text-primary); margin:0 0 1rem 0; display:flex; align-items:center; gap:6px;">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary);">translate</span> Übersetzungen für weitere Sprachen (optional):
                                </h5>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                        <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                                            <div style="font-weight:700; font-size:0.88rem; color:var(--primary); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                                                <span><?php echo $supported[$al]['flag']; ?></span>
                                                <span><?php echo htmlspecialchars($supported[$al]['name']); ?></span>
                                            </div>
                                            <div class="form-group" style="margin-bottom:8px;">
                                                <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:3px;">Titel fürs Menü:</label>
                                                <input type="text" id="sec_trans_<?php echo $al; ?>_nav_label" name="trans_<?php echo $al; ?>_nav_label" class="form-control" placeholder="Menü-Titel auf <?php echo htmlspecialchars($supported[$al]['name']); ?>" style="padding:7px 10px; font-size:0.88rem;">
                                            </div>
                                            <div class="form-group" style="margin-bottom:0;">
                                                <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:3px;">Sektions-Überschrift:</label>
                                                <input type="text" id="sec_trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="Überschrift auf <?php echo htmlspecialchars($supported[$al]['name']); ?>" style="padding:7px 10px; font-size:0.88rem;">
                                            </div>
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
                $primary_texts = $data['texts'] ?? [];
                $texts_i18n = $data['texts_i18n'] ?? [];
            ?>

            <?php if (count($active_langs) > 1): ?>
                <!-- Sprachumschalter für Redakteure im Text-Tab (Client-Side Tabs ohne Reload) -->
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap; background:rgba(255,255,255,0.02); padding:12px 16px; border-radius:12px; border:1px solid var(--border-color);">
                    <span style="font-size:0.9rem; font-weight:700; color:var(--text-secondary); margin-right:6px; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-icons" style="font-size:18px;">translate</span> Sprache für Texte:
                    </span>
                    <button type="button" class="btn btn-primary texts-lang-tab-btn" id="btn-texts-lang-<?php echo $primary_lang; ?>" onclick="switchTextsLangTab('<?php echo $primary_lang; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                        <span><?php echo $supported[$primary_lang]['flag']; ?></span>
                        <span><?php echo htmlspecialchars($supported[$primary_lang]['native'] ?? $supported[$primary_lang]['name']); ?></span>
                        <small style="opacity:0.85; font-size:0.75rem;">(Hauptsprache)</small>
                    </button>
                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                        <button type="button" class="btn btn-outline texts-lang-tab-btn" id="btn-texts-lang-<?php echo $al; ?>" onclick="switchTextsLangTab('<?php echo $al; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                            <span><?php echo $supported[$al]['flag']; ?></span>
                            <span><?php echo htmlspecialchars($supported[$al]['native'] ?? $supported[$al]['name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_texts">
                <input type="hidden" name="target_tab" value="tab-texts">

                <!-- Panel: Hauptsprache -->
                <div id="texts-lang-panel-<?php echo $primary_lang; ?>" class="texts-lang-panel">
                    <h3 style="font-size: 1.2rem; margin-top: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        🌿 Startseite &amp; Hero-Bereich
                    </h3>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label>Hero-Titel (große Hauptüberschrift)</label>
                        <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['hero_title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Hero-Untertitel</label>
                        <input type="text" name="hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['hero_subtitle'] ?? ''); ?>" required>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        👤 Über Mich &amp; Meine Arbeit
                    </h3>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label>Über Mich - Überschrift</label>
                        <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['about_title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Über Mich - Beschreibungstext</label>
                        <textarea name="about_text" class="form-control" rows="6" required><?php echo htmlspecialchars($primary_texts['about_text'] ?? ''); ?></textarea>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        🖼️ Galerie &amp; Handarbeit
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Galerie - Titel</label>
                            <input type="text" name="gallery_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['gallery_title'] ?? 'Galerie & Handarbeit'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Galerie - Untertitel / Beschreibung</label>
                            <input type="text" name="gallery_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['gallery_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        📅 Kurse &amp; Workshops
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Kurse - Titel</label>
                            <input type="text" name="courses_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['courses_title'] ?? 'Kurse & Workshops'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kurse - Untertitel / Infotext</label>
                            <input type="text" name="courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['courses_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        📸 Impressionen vergangener Kurse
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Impressionen - Titel</label>
                            <input type="text" name="past_courses_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['past_courses_title'] ?? 'Impressionen vergangener Kurse'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Impressionen - Untertitel (optional)</label>
                            <input type="text" name="past_courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['past_courses_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        📰 Blog &amp; Aktuelles
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Blog - Titel</label>
                            <input type="text" name="blog_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['blog_title'] ?? 'Aktuelles & Geschichten'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Blog - Untertitel / Beschreibung</label>
                            <input type="text" name="blog_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['blog_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        ❓ Häufige Fragen &amp; Antworten (FAQ)
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>FAQ - Titel</label>
                            <input type="text" name="faq_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['faq_title'] ?? 'Häufige Fragen (FAQ)'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>FAQ - Untertitel / Beschreibung</label>
                            <input type="text" name="faq_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['faq_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        👥 Team &amp; Mitarbeiter
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Team - Titel</label>
                            <input type="text" name="team_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['team_title'] ?? 'Team & Mitarbeiter'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Team - Untertitel / Beschreibung</label>
                            <input type="text" name="team_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['team_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        💾 Downloads &amp; Software
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Downloads - Titel</label>
                            <input type="text" name="downloads_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['downloads_title'] ?? 'Downloads & Software'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Downloads - Untertitel / Beschreibung</label>
                            <input type="text" name="downloads_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['downloads_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        🏷️ Leistungen &amp; Preise
                    </h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Preise - Titel</label>
                            <input type="text" name="pricing_title" class="form-control" value="<?php echo htmlspecialchars($primary_texts['pricing_title'] ?? 'Leistungen & Preise'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Preise - Untertitel / Beschreibung</label>
                            <input type="text" name="pricing_subtitle" class="form-control" value="<?php echo htmlspecialchars($primary_texts['pricing_subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                        💬 Kontaktbereich (Seitenleiste &amp; Formular)
                    </h3>
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label>Kontakt - Einleitungstext über dem Formular</label>
                        <textarea name="contact_intro_text" class="form-control" rows="3"><?php echo htmlspecialchars($primary_texts['contact_intro_text'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Panels für weitere aktive Sprachen -->
                <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                    <?php $cur_trans = $texts_i18n[$al] ?? []; ?>
                    <div id="texts-lang-panel-<?php echo $al; ?>" class="texts-lang-panel" style="display:none;">
                        <div style="background:rgba(59, 130, 246, 0.08); border:1px solid #3b82f6; border-radius:10px; padding:12px 16px; margin-bottom:1.5rem; font-size:0.88rem; color:#1e40af; display:flex; align-items:center; gap:8px;">
                            <span class="material-icons" style="font-size:20px; color:#2563eb;">translate</span>
                            <span>Übersetzung für <strong><?php echo $supported[$al]['flag'] . ' ' . htmlspecialchars($supported[$al]['name']); ?></strong>. Leere Felder greifen im Frontend automatisch auf die Hauptsprache (<?php echo htmlspecialchars($supported[$primary_lang]['name']); ?>) zurück.</span>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            🌿 Startseite &amp; Hero-Bereich
                        </h3>
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label>Hero-Titel (große Hauptüberschrift)</label>
                            <input type="text" name="trans_<?php echo $al; ?>_hero_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['hero_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['hero_title'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Hero-Untertitel</label>
                            <input type="text" name="trans_<?php echo $al; ?>_hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['hero_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['hero_subtitle'] ?? ''); ?>">
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            👤 Über Mich &amp; Meine Arbeit
                        </h3>
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label>Über Mich - Überschrift</label>
                            <input type="text" name="trans_<?php echo $al; ?>_about_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['about_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['about_title'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Über Mich - Beschreibungstext</label>
                            <textarea name="trans_<?php echo $al; ?>_about_text" class="form-control" rows="6" placeholder="<?php echo htmlspecialchars($primary_texts['about_text'] ?? ''); ?>"><?php echo htmlspecialchars($cur_trans['about_text'] ?? ''); ?></textarea>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            🖼️ Galerie &amp; Handarbeit
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Galerie - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_gallery_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['gallery_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['gallery_title'] ?? 'Galerie & Handarbeit'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Galerie - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_gallery_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['gallery_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['gallery_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            📅 Kurse &amp; Workshops
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Kurse - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_courses_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['courses_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['courses_title'] ?? 'Kurse & Workshops'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Kurse - Untertitel / Infotext</label>
                                <input type="text" name="trans_<?php echo $al; ?>_courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['courses_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['courses_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            📸 Impressionen vergangener Kurse
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Impressionen - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_past_courses_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['past_courses_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['past_courses_title'] ?? 'Impressionen vergangener Kurse'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Impressionen - Untertitel (optional)</label>
                                <input type="text" name="trans_<?php echo $al; ?>_past_courses_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['past_courses_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['past_courses_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            📰 Blog &amp; Aktuelles
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Blog - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_blog_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['blog_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['blog_title'] ?? 'Aktuelles & Geschichten'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Blog - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_blog_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['blog_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['blog_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            ❓ Häufige Fragen &amp; Antworten (FAQ)
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>FAQ - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_faq_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['faq_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['faq_title'] ?? 'Häufige Fragen (FAQ)'); ?>">
                            </div>
                            <div class="form-group">
                                <label>FAQ - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_faq_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['faq_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['faq_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            👥 Team &amp; Mitarbeiter
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Team - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_team_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['team_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['team_title'] ?? 'Team & Mitarbeiter'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Team - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_team_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['team_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['team_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            💾 Downloads &amp; Software
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Downloads - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_downloads_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['downloads_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['downloads_title'] ?? 'Downloads & Software'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Downloads - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_downloads_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['downloads_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['downloads_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            🏷️ Leistungen &amp; Preise
                        </h3>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Preise - Titel</label>
                                <input type="text" name="trans_<?php echo $al; ?>_pricing_title" class="form-control" value="<?php echo htmlspecialchars($cur_trans['pricing_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['pricing_title'] ?? 'Leistungen & Preise'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Preise - Untertitel / Beschreibung</label>
                                <input type="text" name="trans_<?php echo $al; ?>_pricing_subtitle" class="form-control" value="<?php echo htmlspecialchars($cur_trans['pricing_subtitle'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($primary_texts['pricing_subtitle'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                            💬 Kontaktbereich (Seitenleiste &amp; Formular)
                        </h3>
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label>Kontakt - Einleitungstext über dem Formular</label>
                            <textarea name="trans_<?php echo $al; ?>_contact_intro_text" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars($primary_texts['contact_intro_text'] ?? ''); ?>"><?php echo htmlspecialchars($cur_trans['contact_intro_text'] ?? ''); ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 2rem; display:flex; align-items:center; gap:12px;">
                    <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:0.95rem; display:inline-flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:18px;">save</span> Alle Sektionstexte &amp; Übersetzungen speichern
                    </button>
                </div>
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
            document.getElementById('sec_edit_placement').value = sec.placement || 'both';
            document.getElementById('sec_edit_icon').value = sec.icon || '';
            updateSecIconPreview(sec.icon || '');
            document.getElementById('sec-editor-heading').innerHTML = '<span class="material-icons">edit</span> Menü-Titel anpassen: ' + (sec.title || sec.id);

            const trans = sec.translations || {};
            SEC_ACTIVE_LANGS.forEach(lang => {
                if (lang === SEC_PRIMARY_LANG) return;
                const fieldNav = document.getElementById('sec_trans_' + lang + '_nav_label');
                if (fieldNav) {
                    fieldNav.value = (trans[lang] && trans[lang].nav_label) ? trans[lang].nav_label : '';
                }
                const fieldTitle = document.getElementById('sec_trans_' + lang + '_title');
                if (fieldTitle) {
                    fieldTitle.value = (trans[lang] && trans[lang].title) ? trans[lang].title : '';
                }
            });

            document.getElementById('section-editor-box').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('sec_edit_nav_label').focus();
        }

        function updateSecIconPreview(val) {
            const prev = document.getElementById('sec_edit_icon_preview');
            if (prev) {
                prev.textContent = val && val.trim() ? val.trim() : 'bookmark';
            }
        }

        function switchTextsLangTab(lang) {
            document.querySelectorAll('.texts-lang-panel').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.texts-lang-tab-btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline');
            });
            const panel = document.getElementById('texts-lang-panel-' + lang);
            const btn = document.getElementById('btn-texts-lang-' + lang);
            if (panel) panel.style.display = 'block';
            if (btn) {
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary');
            }
        }

        function closeSectionEditor() {
            document.getElementById('section-editor-box').style.display = 'none';
        }
        </script>
