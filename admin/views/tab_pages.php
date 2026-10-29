<?php
/**
 * WeaveCMS – Admin Tab: Freie Einzelseiten (Custom Subpages)
 * 
 * Ermöglicht das Anlegen und Verwalten eigenständiger Inhaltsseiten
 * (z. B. Über die Werkstatt, Anfahrt & Parken, Nachhaltigkeit) mit
 * Markdown-Formatierung, eigenem Slug, Menü-Verknüpfung und Mehrsprachigkeit.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$pages = $data['pages'] ?? [];
$total_pages = count($pages);
$published_pages = 0;
$in_nav_count = 0;
foreach ($pages as $pg) {
    if (($pg['status'] ?? 'published') === 'published') {
        $published_pages++;
    }
    if (!empty($pg['in_nav'])) {
        $in_nav_count++;
    }
}

$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$supported_langs = LanguageManager::getSupportedLanguages();
?>
        <!-- ──────────────── SECTION PAGES: FREIE EINZELSEITEN ──────────────── -->
        <div id="tab-pages" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">article</span> <?php echo __a('admin.pages.title'); ?>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        <?php echo __a('admin.pages.subtitle'); ?>
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openPageEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.pages.btn_new'); ?>
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.pages.kpi_total'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_pages; ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.pages.kpi_published'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top:4px;"><?php echo $published_pages; ?></div>
                </div>
                <div style="background: rgba(59, 130, 246, 0.05); border:1px solid rgba(59, 130, 246, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #3b82f6; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.pages.kpi_in_nav'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #3b82f6; margin-top:4px;"><?php echo $in_nav_count; ?></div>
                </div>
            </div>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <style>
                .page-editor-sticky-bar {
                    position: sticky;
                    top: 192px;
                    z-index: 480;
                    background: rgba(251, 249, 246, 0.96);
                    backdrop-filter: blur(14px);
                    -webkit-backdrop-filter: blur(14px);
                    border: 1px solid var(--border-color);
                    border-radius: 14px;
                    padding: 8px 14px;
                    margin-bottom: 1.5rem;
                    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    flex-wrap: wrap;
                    transition: box-shadow 0.2s ease, top 0.2s ease;
                }
                [data-theme="dark"] .page-editor-sticky-bar {
                    background: rgba(26, 32, 44, 0.96);
                    border-color: rgba(255, 255, 255, 0.12);
                    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.35);
                }
                .sticky-lang-pill {
                    transition: all 0.15s ease;
                }
                .sticky-lang-pill:hover {
                    transform: translateY(-1px);
                }
                .sticky-status-dot {
                    transition: background-color 0.2s ease;
                }
            </style>

            <div id="page-editor-box" style="display:none; scroll-margin-top: 150px; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="page-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit_note</span> <?php echo __a('admin.pages.new_page'); ?>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closePageEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> <?php echo __a('admin.pages.drawer_close'); ?>
                    </button>
                </div>

                <form method="POST" action="" id="page-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="page_form_action" value="add_page">
                    <input type="hidden" name="target_tab" value="tab-pages">
                    <input type="hidden" name="id" id="page_id" value="">

                    <!-- ──────────────── SCHWEBENDE / STICKY TOOLBAR ──────────────── -->
                    <div id="page-editor-sticky-bar" class="page-editor-sticky-bar">
                        <!-- 1. Seitenwechsler Dropdown -->
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            <span class="material-icons" style="font-size:18px; color:var(--primary);">menu_book</span>
                            <label for="sticky_page_selector" style="font-size:0.8rem; font-weight:700; color:var(--text-secondary); margin:0; text-transform:uppercase; letter-spacing:0.5px;"><?php echo __a('admin.pages.page_switcher'); ?></label>
                            <select id="sticky_page_selector" onchange="onStickyPageSelect(this.value)" class="form-control" style="font-size:0.85rem; padding:4px 8px; width:auto; max-width:210px; font-weight:600; cursor:pointer; height:auto; border-radius:8px;">
                                <option value=""><?php echo __a('admin.pages.new_page_option'); ?></option>
                                <?php foreach ($pages as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['id'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($p['title'] ?? 'Unbenannt'); ?> (<?php echo htmlspecialchars($p['slug'] ?? ''); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 2. Schwebende Sprach-Pills mit Statusanzeige -->
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; flex:1; justify-content:center; min-width:220px;">
                            <span style="font-size:0.8rem; font-weight:700; color:var(--text-secondary); display:inline-flex; align-items:center; gap:4px; margin-right:4px;">
                                <span class="material-icons" style="font-size:15px;">translate</span> <?php echo __a('admin.pages.sticky_lang'); ?>
                            </span>
                            <button type="button" class="btn btn-primary sticky-lang-pill" id="sticky-lang-btn-<?php echo $primary_lang; ?>" onclick="switchPageLangTab('<?php echo $primary_lang; ?>')" style="padding:4px 10px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px; border-radius:20px;">
                                <span><?php echo $supported_langs[$primary_lang]['flag']; ?></span>
                                <span><?php echo strtoupper($primary_lang); ?></span>
                                <span class="sticky-status-dot" id="sticky-dot-<?php echo $primary_lang; ?>" title="<?php echo htmlspecialchars(__a('admin.pages.status_dot_ok')); ?>" style="width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                            </button>
                            <?php if (count($active_langs) > 1): ?>
                                <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                    <button type="button" class="btn btn-outline sticky-lang-pill" id="sticky-lang-btn-<?php echo $al; ?>" onclick="switchPageLangTab('<?php echo $al; ?>')" style="padding:4px 10px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px; border-radius:20px;">
                                        <span><?php echo $supported_langs[$al]['flag']; ?></span>
                                        <span><?php echo strtoupper($al); ?></span>
                                        <span class="sticky-status-dot" id="sticky-dot-<?php echo $al; ?>" title="<?php echo htmlspecialchars(__a('admin.pages.status_dot_empty')); ?>" style="width:7px; height:7px; border-radius:50%; background:#94a3b8; display:inline-block;"></span>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- 3. Schnell-Aktionen (Vorschau, Schnell-Speichern & Schließen) -->
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            <button type="button" class="btn btn-secondary" onclick="openPageLivePreview()" style="padding:5px 12px; font-size:0.84rem; display:inline-flex; align-items:center; gap:6px; border-radius:8px;" title="<?php echo htmlspecialchars(__a('admin.pages.preview_title')); ?>">
                                <span class="material-icons" style="font-size:16px;">devices</span> <?php echo __a('admin.pages.btn_live_preview'); ?>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="submitPageFormQuick()" style="padding:5px 12px; font-size:0.84rem; display:inline-flex; align-items:center; gap:6px; border-radius:8px;" title="<?php echo htmlspecialchars(__a('admin.pages.sticky_quick_save_title')); ?>">
                                <span class="material-icons" style="font-size:16px;">save</span> <?php echo __a('admin.pages.quick_save'); ?>
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closePageEditor()" style="padding:5px 10px; font-size:0.84rem; display:inline-flex; align-items:center; border-radius:8px;" title="<?php echo htmlspecialchars(__a('admin.pages.drawer_close')); ?>">
                                <span class="material-icons" style="font-size:16px;">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Gemeinsame Einstellungen: Status, Seitentyp, Hauptmenü-Einbindung, Basis-Slug, Icon -->
                    <div class="grid-5" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background:rgba(255,255,255,0.02); padding:1rem; border-radius:10px; border:1px solid var(--border-color);">
                        <div class="form-group">
                            <label for="page_slug" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.pages.field_slug'); ?>
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(z. B. aktion)</span>
                            </label>
                            <input type="text" id="page_slug" name="slug" class="form-control" placeholder="aktion" required pattern="[a-z0-9_-]+">
                        </div>
                        <div class="form-group">
                            <label for="page_type" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.pages.field_type'); ?></label>
                            <select id="page_type" name="page_type" class="form-control" onchange="togglePageTypeMode(this.value)">
                                <option value="content"><?php echo __a('admin.pages.type_content'); ?></option>
                                <option value="modular"><?php echo __a('admin.pages.type_modular'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="page_status" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.pages.field_status'); ?></label>
                            <select id="page_status" name="status" class="form-control" onchange="togglePageScheduledBox(this.value)">
                                <option value="published"><?php echo __a('admin.pages.status_published'); ?></option>
                                <option value="draft"><?php echo __a('admin.pages.status_draft'); ?></option>
                                <option value="scheduled"><?php echo __a('admin.pages.status_scheduled'); ?></option>
                            </select>
                            <div id="page_scheduled_box" style="display:none; margin-top:8px;">
                                <label for="page_publish_at" style="font-size:0.78rem; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo __a('admin.pages.field_publish_at'); ?></label>
                                <input type="datetime-local" id="page_publish_at" name="publish_at" class="form-control" style="font-size:0.85rem; padding:4px 8px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="page_icon" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.pages.field_icon'); ?>
                            </label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="text" id="page_icon" name="icon" class="form-control" placeholder="article" oninput="updatePageIconPreview(this.value)">
                                <span id="page_icon_preview" class="material-icons" style="font-size:22px; color:var(--primary); min-width:28px; text-align:center;">article</span>
                            </div>
                        </div>
                        <div class="form-group" style="display:flex; flex-direction:column; justify-content:center;">
                            <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.pages.field_in_nav'); ?></label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                                <input type="checkbox" id="page_in_nav" name="in_nav" value="1">
                                <span><?php echo __a('admin.pages.in_nav_checkbox'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Modulare Sektionen-Auswahl (Landingpage-Stack) -->
                    <div id="modular-sections-box" style="display:none; background:rgba(46,98,72,0.06); border:1px solid rgba(46,98,72,0.3); border-radius:14px; padding:1.5rem; margin-bottom:1.5rem;">
                        <h4 style="color:var(--primary); margin:0 0 0.4rem 0; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                            <span class="material-icons">view_quilt</span> <?php echo __a('admin.pages.modular_heading'); ?>
                        </h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0 0 1.25rem 0; line-height:1.5;">
                            <?php echo __a('admin.pages.modular_desc'); ?>
                        </p>
                        <div id="modular-sections-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px;">
                            <?php 
                            $avail_sections = SectionManager::ensureSections($data);
                            $sec_type_icons = [
                                'hero' => 'flag', 'gallery' => 'photo_library', 'courses' => 'event',
                                'past_courses' => 'photo_camera', 'blog' => 'newspaper', 'about' => 'badge',
                                'custom_text' => 'article', 'pricing' => 'sell', 'faq' => 'quiz',
                                'team' => 'groups', 'downloads' => 'file_download', 'webtrees' => 'account_tree', 'shop' => 'storefront'
                            ];
                            foreach ($avail_sections as $asec):
                                $aicon = $sec_type_icons[$asec['type']] ?? 'dashboard';
                            ?>
                                <div class="modular-sec-card" id="mod-card-<?php echo htmlspecialchars($asec['id']); ?>" data-sec-id="<?php echo htmlspecialchars($asec['id']); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; background:#ffffff; border:1px solid var(--border-color); border-radius:12px; transition:all 0.2s ease;">
                                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; flex:1; margin:0; min-width:0;">
                                        <input type="checkbox" name="page_sections[]" value="<?php echo htmlspecialchars($asec['id']); ?>" class="modular-sec-checkbox" onchange="toggleModularSecCard(this)" style="width:17px; height:17px; accent-color:var(--primary); cursor:pointer;">
                                        <span class="material-icons" style="font-size:20px; color:var(--primary);"><?php echo $aicon; ?></span>
                                        <span style="font-weight:600; font-size:0.92rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($asec['title']); ?></span>
                                    </label>
                                    <div class="modular-sec-layout-wrap" id="mod-layout-wrap-<?php echo htmlspecialchars($asec['id']); ?>" style="display:none; align-items:center; gap:6px;">
                                        <span style="font-size:0.75rem; color:var(--text-secondary); font-weight:600;"><?php echo __a('admin.pages.modular_col_layout', 'Breite:'); ?></span>
                                        <select name="page_section_layouts[<?php echo htmlspecialchars($asec['id']); ?>]" id="mod-layout-select-<?php echo htmlspecialchars($asec['id']); ?>" class="form-control modular-sec-layout-select" style="width:auto; padding:3px 8px; font-size:0.8rem; border-radius:6px; cursor:pointer;">
                                            <option value="full"><?php echo __a('admin.pages.layout_full', '█ 100% Vollbreite'); ?></option>
                                            <option value="2"><?php echo __a('admin.pages.layout_half', '▌▌ 50% (2 Spalten)'); ?></option>
                                            <option value="3"><?php echo __a('admin.pages.layout_three', '▌▌▌ 33% (3 Spalten)'); ?></option>
                                            <option value="1-2"><?php echo __a('admin.pages.layout_third', '▍▋ 1/3 Spalte'); ?></option>
                                            <option value="2-1"><?php echo __a('admin.pages.layout_two_thirds', '▋▍ 2/3 Spalte'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Panel: Hauptsprache -->
                    <div id="page-lang-panel-<?php echo $primary_lang; ?>" class="page-lang-panel">
                        <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="page_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo str_replace('{lang}', htmlspecialchars($supported_langs[$primary_lang]['name']), __a('admin.pages.field_title')); ?> *
                                </label>
                                <input type="text" id="page_title" name="title" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_title')); ?>" required oninput="autoGeneratePageSlug(this.value)">
                            </div>
                            <div class="form-group">
                                <label for="page_nav_label" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.pages.field_nav_label'); ?>
                                </label>
                                <input type="text" id="page_nav_label" name="nav_label" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_nav_label')); ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="page_meta_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.pages.field_meta_desc'); ?>
                            </label>
                            <input type="text" id="page_meta_desc" name="meta_description" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_meta_desc')); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="page_content" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                <span><?php echo __a('admin.pages.field_content'); ?> *</span>
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);"><?php echo __a('admin.pages.content_hint'); ?></span>
                            </label>

                            <!-- Design- & Layout-Toolbar für Einzelseiten (in Slot für alle Sprach-Panels) -->
                            <div class="page-toolbar-slot">
                                <div id="page-editor-design-toolbar" class="page-editor-design-toolbar" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:10px 14px; margin-bottom:0.75rem; display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
                                <span style="font-size:0.85rem; font-weight:700; color:var(--primary); display:inline-flex; align-items:center; gap:5px;">
                                    <span class="material-icons" style="font-size:18px;">auto_awesome</span> <?php echo __a('admin.pages.toolbar_template'); ?>
                                </span>

                                <!-- Überschriften-Stile -->
                                <select onchange="insertPageSnippet(this, 'heading')" class="form-control" style="width:auto; padding:5px 10px; font-size:0.83rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_heading_title')); ?>">
                                    <option value=""><?php echo __a('admin.pages.toolbar_heading_style'); ?></option>
                                    <option value="left_bar"><?php echo __a('admin.pages.heading_left_bar'); ?></option>
                                    <option value="underline"><?php echo __a('admin.pages.heading_underline'); ?></option>
                                    <option value="eyebrow"><?php echo __a('admin.pages.heading_eyebrow'); ?></option>
                                    <option value="gradient"><?php echo __a('admin.pages.heading_gradient'); ?></option>
                                    <option value="icon"><?php echo __a('admin.pages.heading_icon'); ?></option>
                                    <option value="display"><?php echo __a('admin.pages.heading_display'); ?></option>
                                </select>

                                <!-- Einfärbbare Div-Boxen -->
                                <select onchange="insertPageSnippet(this, 'box')" class="form-control" style="width:auto; padding:5px 10px; font-size:0.83rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_box_title')); ?>">
                                    <option value=""><?php echo __a('admin.pages.toolbar_box_style'); ?></option>
                                    <option value="soft_primary"><?php echo __a('admin.pages.box_soft_primary'); ?></option>
                                    <option value="soft_success"><?php echo __a('admin.pages.box_soft_success'); ?></option>
                                    <option value="soft_amber"><?php echo __a('admin.pages.box_soft_amber'); ?></option>
                                    <option value="soft_purple"><?php echo __a('admin.pages.box_soft_purple'); ?></option>
                                    <option value="gradient"><?php echo __a('admin.pages.box_gradient'); ?></option>
                                    <option value="dark"><?php echo __a('admin.pages.box_dark'); ?></option>
                                    <option value="tintable"><?php echo __a('admin.pages.box_tintable'); ?></option>
                                </select>

                                <!-- Farbwähler für Box-Hintergrund -->
                                <label title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_color_title')); ?>" style="display:inline-flex; align-items:center; gap:4px; font-size:0.8rem; color:var(--text-secondary); cursor:pointer;">
                                    <input type="color" id="picker_box_color" value="#e0f2fe" style="width:26px; height:26px; border:none; border-radius:6px; cursor:pointer; padding:0; background:none;">
                                    <span><?php echo __a('admin.pages.toolbar_color_label'); ?></span>
                                </label>

                                <!-- Optische Trenner -->
                                <select onchange="insertPageSnippet(this, 'divider')" class="form-control" style="width:auto; padding:5px 10px; font-size:0.83rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_divider_title')); ?>">
                                    <option value=""><?php echo __a('admin.pages.toolbar_divider_style'); ?></option>
                                    <option value="gradient"><?php echo __a('admin.pages.divider_gradient'); ?></option>
                                    <option value="icon_leaf"><?php echo __a('admin.pages.divider_icon_leaf'); ?></option>
                                    <option value="icon_star"><?php echo __a('admin.pages.divider_icon_star'); ?></option>
                                    <option value="glow"><?php echo __a('admin.pages.divider_glow'); ?></option>
                                    <option value="dots"><?php echo __a('admin.pages.divider_dots'); ?></option>
                                    <option value="dashed"><?php echo __a('admin.pages.divider_dashed'); ?></option>
                                    <option value="wave"><?php echo __a('admin.pages.divider_wave'); ?></option>
                                </select>

                                <!-- Spalten-Layouts (Grid-Shortcodes) als Icon-Leiste -->
                                <?php
                                // Spaltenpositionen im 36x24-Icon: [x, Breite]; 'text' = Zeilen-Symbol statt Fläche
                                $grid_icon_layouts = [
                                    '2'        => ['label' => 'admin.pages.grid_2_equal', 'cols' => [[0, 17], [19, 17]]],
                                    '3'        => ['label' => 'admin.pages.grid_3_equal', 'cols' => [[0, 11], [12.5, 11], [25, 11]]],
                                    '1-2'      => ['label' => 'admin.pages.grid_1_2',     'cols' => [[0, 11], [13, 23]]],
                                    '2-1'      => ['label' => 'admin.pages.grid_2_1',     'cols' => [[0, 23], [25, 11]]],
                                    'img-text' => ['label' => 'admin.pages.grid_img_text', 'cols' => [[0, 17], [19, 17, 'text']]],
                                ];
                                ?>
                                <span class="page-grid-picker" role="group" aria-label="<?php echo htmlspecialchars(__a('admin.pages.toolbar_grid_style')); ?>" style="display:inline-flex; align-items:center; gap:4px;">
                                    <span style="font-size:0.8rem; color:var(--text-secondary); font-weight:600;"><?php echo __a('admin.pages.toolbar_grid_label'); ?></span>
                                    <?php foreach ($grid_icon_layouts as $g_val => $g_def): $g_label = __a($g_def['label']); ?>
                                        <button type="button" value="<?php echo $g_val; ?>" onclick="insertPageSnippet(this, 'grid')" class="btn btn-secondary page-grid-btn" title="<?php echo htmlspecialchars($g_label); ?>" aria-label="<?php echo htmlspecialchars($g_label); ?>" style="padding:3px 5px; line-height:0; border-radius:8px;">
                                            <svg width="36" height="24" viewBox="0 0 36 24" aria-hidden="true" focusable="false">
                                                <?php foreach ($g_def['cols'] as $g_col): ?>
                                                    <?php if (!empty($g_col[2])): ?>
                                                        <rect x="<?php echo $g_col[0]; ?>" y="4" width="<?php echo $g_col[1]; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                                                        <rect x="<?php echo $g_col[0]; ?>" y="10" width="<?php echo $g_col[1]; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                                                        <rect x="<?php echo $g_col[0]; ?>" y="16" width="<?php echo $g_col[1] * 0.6; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                                                    <?php else: ?>
                                                        <rect x="<?php echo $g_col[0]; ?>" y="2" width="<?php echo $g_col[1]; ?>" height="20" rx="3" fill="currentColor" opacity="0.75"/>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </svg>
                                        </button>
                                    <?php endforeach; ?>
                                </span>

                                <!-- Block-Vorlagen (Presets aus zentraler Registry) -->
                                <select onchange="insertPageSnippet(this, 'preset')" class="form-control" style="width:auto; padding:5px 10px; font-size:0.83rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_preset_style')); ?>">
                                    <option value=""><?php echo __a('admin.pages.toolbar_preset_style'); ?></option>
                                    <option value="hero"><?php echo __a('admin.pages.preset_hero'); ?></option>
                                    <option value="testimonial"><?php echo __a('admin.pages.preset_testimonial'); ?></option>
                                    <option value="cta"><?php echo __a('admin.pages.preset_cta'); ?></option>
                                    <option value="features"><?php echo __a('admin.pages.preset_features'); ?></option>
                                </select>
                            </div>
                            </div>

                            <textarea id="page_content" name="content" class="form-control" rows="12" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_content')); ?>" required style="font-family: monospace; font-size:0.95rem; line-height:1.6;"></textarea>
                        </div>
                    </div>

                    <!-- Panels für weitere aktive Sprachen -->
                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                        <div id="page-lang-panel-<?php echo $al; ?>" class="page-lang-panel" style="display:none;">
                            <div style="background:rgba(59, 130, 246, 0.08); border:1px solid #3b82f6; border-radius:10px; padding:10px 14px; margin-bottom:1.25rem; font-size:0.88rem; color:#93c5fd; display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="font-size:18px; color:#60a5fa;">translate</span>
                                <span><?php echo str_replace(['{lang}', '{primary}'], ['<strong>' . $supported_langs[$al]['flag'] . ' ' . htmlspecialchars($supported_langs[$al]['name']) . '</strong>', htmlspecialchars($supported_langs[$primary_lang]['name'])], __a('admin.pages.trans_info')); ?></span>
                            </div>

                            <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                                <div class="form-group">
                                    <label for="trans_<?php echo $al; ?>_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        <?php echo str_replace('{lang}', htmlspecialchars($supported_langs[$al]['name']), __a('admin.pages.field_title')); ?>
                                    </label>
                                    <input type="text" id="trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="<?php echo htmlspecialchars(str_replace('{lang}', $supported_langs[$al]['name'], __a('admin.pages.placeholder_trans_title'))); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="trans_<?php echo $al; ?>_nav_label" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        <?php echo __a('admin.pages.field_nav_label'); ?>
                                    </label>
                                    <input type="text" id="trans_<?php echo $al; ?>_nav_label" name="trans_<?php echo $al; ?>_nav_label" class="form-control" placeholder="<?php echo htmlspecialchars(str_replace('{lang}', $supported_langs[$al]['name'], __a('admin.pages.placeholder_trans_nav_label'))); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="trans_<?php echo $al; ?>_meta_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.pages.field_meta_desc'); ?>
                                </label>
                                <input type="text" id="trans_<?php echo $al; ?>_meta_desc" name="trans_<?php echo $al; ?>_meta_description" class="form-control" placeholder="<?php echo htmlspecialchars(str_replace('{lang}', $supported_langs[$al]['name'], __a('admin.pages.placeholder_trans_meta_desc'))); ?>">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="trans_<?php echo $al; ?>_content" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                    <span><?php echo __a('admin.pages.field_content'); ?> (<?php echo htmlspecialchars($supported_langs[$al]['name']); ?>)</span>
                                </label>
                                <div class="page-toolbar-slot"></div>
                                <textarea id="trans_<?php echo $al; ?>_content" name="trans_<?php echo $al; ?>_content" class="form-control" rows="12" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_trans_content')); ?>" style="font-family: monospace; font-size:0.95rem; line-height:1.6;"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Schlagwörter / Tags (Phase 28.1) -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="page_tags" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                            <span><span class="material-icons" style="font-size:16px; vertical-align:middle; color:var(--primary);">local_offer</span> <?php echo __a('admin.pages.field_tags', 'Schlagwörter (Tags)'); ?></span>
                            <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);"><?php echo __a('admin.pages.tags_hint', 'Kommagetrennt, z. B. Natur, Handwerk, Deko'); ?></span>
                        </label>
                        <input type="text" id="page_tags" name="tags" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_tags', 'Handwerk, Natur, Deko')); ?>" list="all_tags_datalist">
                        <?php 
                        $existing_tags = TaxonomyManager::getAllTags($data);
                        if (!empty($existing_tags)): 
                        ?>
                            <datalist id="all_tags_datalist">
                                <?php foreach ($existing_tags as $etag): ?>
                                    <option value="<?php echo htmlspecialchars($etag['name']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                <span style="font-size:0.75rem; color:var(--text-secondary);"><?php echo __a('admin.pages.existing_tags', 'Bestehende Tags:'); ?></span>
                                <?php foreach (array_slice($existing_tags, 0, 10) as $etag): ?>
                                    <button type="button" class="btn btn-secondary" onclick="appendTagToField('page_tags', <?php echo json_encode($etag['name']); ?>)" style="padding:2px 8px; font-size:0.75rem; border-radius:999px; line-height:1.2;">
                                        + <?php echo htmlspecialchars($etag['name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Zusatzfelder / Custom Fields (Phase 28.2) -->
                    <div id="page_custom_fields_container">
                        <?php echo CustomFieldManager::renderAdminFields('pages', [], $data); ?>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); padding:0.75rem 1rem; border-radius:10px;">
                        <label for="page_revision_note" style="font-weight:600; font-size:0.85rem; color:var(--text-secondary); display:block; margin-bottom:0.25rem;">
                            <span class="material-icons" style="font-size:15px; vertical-align:middle;">history_edu</span> <?php echo __a('admin.pages.field_revision_note'); ?>
                        </label>
                        <input type="text" id="page_revision_note" name="revision_note" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.pages.placeholder_revision')); ?>" style="font-size:0.85rem;">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="openPageLivePreview()" style="display:inline-flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:18px;">devices</span> <?php echo __a('admin.pages.btn_live_preview'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closePageEditor()"><?php echo __a('admin.pages.btn_cancel'); ?></button>
                        <button type="submit" class="btn btn-primary" id="page-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> <?php echo __a('admin.pages.btn_save'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── SEITEN-LISTE / TABELLE ──────────────── -->
            <?php if (empty($pages)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">article</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);"><?php echo __a('admin.pages.empty_title'); ?></h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;"><?php echo __a('admin.pages.empty_desc'); ?></p>
                    <button type="button" class="btn btn-primary" onclick="openPageEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.pages.empty_btn'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($pages as $p): ?>
                        <?php 
                            $p_status = $p['status'] ?? 'published';
                            $p_publish_at = $p['publish_at'] ?? '';
                            $p_token = $p['preview_token'] ?? '';
                            $p_slug = $p['slug'] ?? '';
                            $p_url = '../?page=' . urlencode($p_slug);
                            $p_preview_url = '../?page=' . urlencode($p_slug) . ($p_token ? '&preview=' . urlencode($p_token) : '');
                            $p_nav = !empty($p['nav_label']) ? $p['nav_label'] : ($p['title'] ?? '');
                        ?>
                        <div class="card" style="padding:1.25rem 1.5rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                                <div style="width:42px; height:42px; border-radius:8px; background:rgba(46,98,72,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <span class="material-icons"><?php echo htmlspecialchars(!empty($p['icon']) ? $p['icon'] : 'article'); ?></span>
                                </div>
                                <div>
                                    <h4 style="margin:0 0 4px 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span><?php echo htmlspecialchars($p['title'] ?? 'Unbenannt'); ?></span>
                                        <?php 
                                            $is_modular = ($p['page_type'] ?? 'content') === 'modular';
                                            $sec_count = count($p['page_sections'] ?? []);
                                        ?>
                                        <?php if ($is_modular): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#8b5cf6; background:rgba(139,92,246,0.1); padding:2px 8px; border-radius:999px;">
                                                <?php echo str_replace('{count}', $sec_count, __a('admin.pages.badge_modular')); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); background:rgba(255,255,255,0.06); padding:2px 8px; border-radius:999px;">
                                                <?php echo __a('admin.pages.badge_standard'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($p_status === 'published'): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#10b981; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:999px;"><?php echo __a('admin.pages.badge_published'); ?></span>
                                        <?php elseif ($p_status === 'scheduled'): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:2px 8px; border-radius:999px;" title="<?php echo htmlspecialchars(str_replace('{date}', $p_publish_at, __a('admin.pages.badge_scheduled'))); ?>">
                                                ⏱️ <?php echo htmlspecialchars(str_replace('{date}', date('d.m.Y H:i', strtotime($p_publish_at ?: 'now')), __a('admin.pages.badge_scheduled'))); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#f59e0b; background:rgba(245,158,11,0.1); padding:2px 8px; border-radius:999px;"><?php echo __a('admin.pages.badge_draft'); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['in_nav'])): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:2px 8px; border-radius:999px;" title="Titel im Menü: <?php echo htmlspecialchars($p_nav); ?>">
                                                <?php echo str_replace('{label}', '<strong>' . htmlspecialchars($p_nav) . '</strong>', __a('admin.pages.badge_in_nav')); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:var(--text-secondary); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:999px;"><?php echo __a('admin.pages.badge_not_in_nav'); ?></span>
                                        <?php endif; ?>
                                    </h4>
                                    
                                    <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; gap:14px; flex-wrap:wrap; align-items:center; margin-top:4px;">
                                        <span><?php echo __a('admin.pages.path_label'); ?> <code>?page=<?php echo htmlspecialchars($p_slug); ?></code></span>
                                        
                                        <!-- Sprach-Badges (Klickbar für Direktsprung zur Sprache) -->
                                        <span style="display:inline-flex; align-items:center; gap:4px;">
                                            <span style="font-size:0.75rem; color:var(--text-secondary);"><?php echo __a('admin.pages.languages_label'); ?></span>
                                            <span onclick="editPage(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo $primary_lang; ?>')" style="font-size:0.72rem; font-weight:700; background:rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:4px; cursor:pointer;" title="<?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?> bearbeiten">
                                                <?php echo $supported_langs[$primary_lang]['flag'] . ' ' . strtoupper($primary_lang); ?>
                                            </span>
                                            <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                                <?php 
                                                    $has_trans = !empty($p['translations'][$al]['title']) || !empty($p['translations'][$al]['content']) || !empty($p['translations'][$al]['nav_label']); 
                                                ?>
                                                <span onclick="editPage(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo $al; ?>')" style="font-size:0.72rem; font-weight:700; background:<?php echo $has_trans ? 'rgba(16,185,129,0.15)' : 'rgba(255,255,255,0.05)'; ?>; color:<?php echo $has_trans ? '#10b981' : 'var(--text-secondary)'; ?>; padding:2px 8px; border-radius:4px; border:1px solid <?php echo $has_trans ? 'rgba(16,185,129,0.3)' : 'var(--border-color)'; ?>; cursor:pointer;" title="<?php echo $has_trans ? 'Übersetzung für ' . htmlspecialchars($supported_langs[$al]['name']) . ' bearbeiten' : 'Neue Übersetzung für ' . htmlspecialchars($supported_langs[$al]['name']) . ' anlegen'; ?>">
                                                    <?php echo $supported_langs[$al]['flag'] . ' ' . strtoupper($al); ?> <?php echo $has_trans ? '✓' : '+'; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <a href="<?php echo htmlspecialchars($p_preview_url); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem; text-decoration:none;" title="<?php echo htmlspecialchars(__a('admin.pages.btn_preview')); ?>">
                                    <span class="material-icons" style="font-size:16px;">visibility</span> <?php echo __a('admin.pages.btn_preview'); ?>
                                </a>
                                <button type="button" class="btn btn-secondary" onclick="openRevisionsModal('page', '<?php echo htmlspecialchars($p['id'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['title'] ?? '', ENT_QUOTES); ?>')" style="padding:6px 12px; font-size:0.82rem;" title="<?php echo htmlspecialchars(__a('admin.pages.btn_revisions')); ?>">
                                    <span class="material-icons" style="font-size:16px;">history</span> <?php echo __a('admin.pages.btn_revisions'); ?>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="editPage(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> <?php echo __a('admin.pages.btn_edit'); ?>
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm(<?php echo htmlspecialchars(json_encode(__a('admin.pages.confirm_delete'))); ?>);">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_page">
                                    <input type="hidden" name="target_tab" value="tab-pages">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="<?php echo htmlspecialchars(__a('admin.pages.btn_delete')); ?>">
                                        <span class="material-icons" style="font-size:16px;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ======================================================================
             MODAL: RESPONSIVE BREAKPOINT-VORSCHAU (PHASE 27.3)
             ====================================================================== -->
        <div id="page-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.82); z-index:4000; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); padding:1rem;" onclick="if(event.target===this) closePageLivePreview();">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; width:100%; max-width:1300px; height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.5); overflow:hidden;">
                
                <!-- Modal-Header: Titel, Breakpoint-Buttons & Schließen -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1.25rem; border-bottom:1px solid var(--border-color); background:rgba(255,255,255,0.02); flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:22px; color:var(--primary);">devices</span>
                        <h3 style="margin:0; font-size:1.1rem; color:var(--text-primary); font-weight:700;">
                            <?php echo __a('admin.pages.preview_title'); ?>
                        </h3>
                    </div>

                    <!-- Breakpoint-Buttons: Desktop (1200px), Tablet (768px), Smartphone (375px) -->
                    <div role="group" aria-label="Breakpoints" style="display:inline-flex; align-items:center; gap:6px; background:rgba(0,0,0,0.2); padding:4px 6px; border-radius:12px; border:1px solid var(--border-color);">
                        <button type="button" class="btn btn-primary page-breakpoint-btn" id="bp-btn-desktop" onclick="setPreviewBreakpoint('1200px')" style="padding:4px 10px; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px; border-radius:8px;">
                            <span class="material-icons" style="font-size:16px;">desktop_windows</span>
                            <span><?php echo __a('admin.pages.preview_desktop'); ?></span>
                        </button>
                        <button type="button" class="btn btn-secondary page-breakpoint-btn" id="bp-btn-tablet" onclick="setPreviewBreakpoint('768px')" style="padding:4px 10px; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px; border-radius:8px;">
                            <span class="material-icons" style="font-size:16px;">tablet_mac</span>
                            <span><?php echo __a('admin.pages.preview_tablet'); ?></span>
                        </button>
                        <button type="button" class="btn btn-secondary page-breakpoint-btn" id="bp-btn-mobile" onclick="setPreviewBreakpoint('375px')" style="padding:4px 10px; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px; border-radius:8px;">
                            <span class="material-icons" style="font-size:16px;">smartphone</span>
                            <span><?php echo __a('admin.pages.preview_mobile'); ?></span>
                        </button>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="closePageLivePreview()" style="padding:5px 10px; border-radius:8px;" title="<?php echo htmlspecialchars(__a('admin.pages.preview_close')); ?>">
                        <span class="material-icons" style="font-size:18px;">close</span>
                    </button>
                </div>

                <!-- Iframe-Bühne -->
                <div style="flex:1; background:#0b0f17; display:flex; align-items:center; justify-content:center; padding:1rem; overflow:auto; position:relative;">
                    <div id="page-preview-loading" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); color:#94a3b8; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="animation:spin 1s linear infinite;">sync</span>
                        <span><?php echo __a('admin.pages.preview_loading'); ?></span>
                    </div>
                    <iframe id="page-preview-iframe" style="width:1200px; max-width:100%; height:100%; border:1px solid rgba(255,255,255,0.1); border-radius:12px; background:#fcfbf9; transition:width 0.25s ease; box-shadow:0 10px 30px rgba(0,0,0,0.5);"></iframe>
                </div>
            </div>
        </div>

        <script>
        const PAGE_ACTIVE_LANGS = <?php echo json_encode($active_langs); ?>;
        const PAGE_PRIMARY_LANG = <?php echo json_encode($primary_lang); ?>;
        const PAGE_GRID_I18N = <?php echo json_encode([
            'col'   => __a('admin.pages.grid_placeholder_col'),
            'image' => __a('admin.pages.grid_placeholder_image'),
            'text'  => __a('admin.pages.grid_placeholder_text'),
            'nested' => __a('admin.pages.grid_nested_warning'),
        ], JSON_UNESCAPED_UNICODE); ?>;
        const PAGE_HEADING_I18N = <?php echo json_encode([
            'left_bar'  => __a('admin.pages.heading_text_left_bar'),
            'underline' => __a('admin.pages.heading_text_underline'),
            'eyebrow'   => __a('admin.pages.heading_text_eyebrow'),
            'eyebrow_title' => __a('admin.pages.heading_text_eyebrow_title'),
            'gradient'  => __a('admin.pages.heading_text_gradient'),
            'icon'      => __a('admin.pages.heading_text_icon'),
            'display'   => __a('admin.pages.heading_text_display'),
        ], JSON_UNESCAPED_UNICODE); ?>;
        const PAGE_BOX_I18N = <?php echo json_encode([
            'title' => __a('admin.pages.box_default_title'),
            'text'  => __a('admin.pages.box_default_text'),
        ], JSON_UNESCAPED_UNICODE); ?>;
        <?php
        // Zentrale Preset-Registry: fertige Shortcode-Blöcke mit übersetzten Platzhaltertexten
        $tr = function ($key) { return __a('admin.pages.' . $key); };
        $block_presets = [
            'hero' => "\n[box style=\"gradient\"]\n## " . $tr('preset_hero_title') . "\n" . $tr('preset_hero_text') . "\n\n[" . $tr('preset_button') . "](#)\n[/box]\n",
            'testimonial' => "\n[box style=\"soft_primary\"]\n" . $tr('preset_testimonial_quote') . "\n\n**" . $tr('preset_testimonial_author') . "**\n[/box]\n",
            'cta' => "\n[box style=\"dark\"]\n### " . $tr('preset_cta_title') . "\n" . $tr('preset_cta_text') . "\n\n[" . $tr('preset_button') . "](#)\n[/box]\n",
            'features' => "\n[grid layout=\"3\"]\n" . implode('', array_map(function ($n) use ($tr) {
                return "[col]\n### " . str_replace('{n}', $n, $tr('preset_feature_title')) . "\n" . $tr('preset_feature_text') . "\n[/col]\n";
            }, [1, 2, 3])) . "[/grid]\n",
        ];
        ?>
        const PAGE_BLOCK_PRESETS = <?php echo json_encode($block_presets, JSON_UNESCAPED_UNICODE); ?>;
        const ALL_PAGES_DATA = <?php echo json_encode(array_values($pages)); ?>;
        let CURRENT_ACTIVE_PAGE_LANG = PAGE_PRIMARY_LANG;

        function switchPageLangTab(lang) {
            CURRENT_ACTIVE_PAGE_LANG = lang;
            document.querySelectorAll('.page-lang-panel').forEach(el => el.style.display = 'none');
            const targetPanel = document.getElementById('page-lang-panel-' + lang);
            if (targetPanel) {
                targetPanel.style.display = 'block';

                // Design-Toolbar in den Slot des aktiven Panels verschieben
                const toolbar = document.getElementById('page-editor-design-toolbar');
                if (toolbar) {
                    const slot = targetPanel.querySelector('.page-toolbar-slot');
                    if (slot && !slot.contains(toolbar)) {
                        slot.appendChild(toolbar);
                    }
                }
            }

            // Sticky-Pills synchronisieren
            document.querySelectorAll('.sticky-lang-pill').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            });
            const activeStickyBtn = document.getElementById('sticky-lang-btn-' + lang);
            if (activeStickyBtn) {
                activeStickyBtn.classList.remove('btn-outline');
                activeStickyBtn.classList.add('btn-primary');
            }

            // Fallback für Standard-Buttons (falls vorhanden)
            document.querySelectorAll('.page-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            });
            const activeBtn = document.getElementById('btn-page-lang-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-outline');
                activeBtn.classList.add('btn-primary');
            }
        }

        function updateStickyTranslationDots() {
            const primTitle = (document.getElementById('page_title') ? document.getElementById('page_title').value.trim() : '');
            const primContent = (document.getElementById('page_content') ? document.getElementById('page_content').value.trim() : '');
            const primDot = document.getElementById('sticky-dot-' + PAGE_PRIMARY_LANG);
            if (primDot) {
                if (primTitle || primContent) {
                    primDot.style.background = '#10b981';
                    primDot.title = <?php echo json_encode(__a('admin.pages.status_dot_ok')); ?>;
                } else {
                    primDot.style.background = '#f59e0b';
                    primDot.title = <?php echo json_encode(__a('admin.pages.status_dot_empty')); ?>;
                }
            }

            PAGE_ACTIVE_LANGS.forEach(lang => {
                if (lang === PAGE_PRIMARY_LANG) return;
                const tTitle = document.getElementById('trans_' + lang + '_title');
                const tContent = document.getElementById('trans_' + lang + '_content');
                const tNav = document.getElementById('trans_' + lang + '_nav_label');
                const hasText = (tTitle && tTitle.value.trim().length > 0) || 
                                (tContent && tContent.value.trim().length > 0) || 
                                (tNav && tNav.value.trim().length > 0);
                const dot = document.getElementById('sticky-dot-' + lang);
                if (dot) {
                    if (hasText) {
                        dot.style.background = '#10b981';
                        dot.title = <?php echo json_encode(__a('admin.pages.status_dot_ok')); ?>;
                    } else {
                        dot.style.background = '#94a3b8';
                        dot.title = <?php echo json_encode(__a('admin.pages.status_dot_empty')); ?>;
                    }
                }
            });
        }

        function onStickyPageSelect(pageId) {
            if (!pageId) {
                openPageEditor();
                return;
            }
            const page = ALL_PAGES_DATA.find(p => String(p.id) === String(pageId));
            if (page) {
                editPage(page, CURRENT_ACTIVE_PAGE_LANG);
            }
        }

        function submitPageFormQuick() {
            const form = document.getElementById('page-form');
            if (form) {
                if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                    return;
                }
                form.submit();
            }
        }

        function toggleModularSecCard(cb) {
            const card = cb.closest('.modular-sec-card');
            if (!card) return;
            const wrap = card.querySelector('.modular-sec-layout-wrap');
            if (wrap) {
                wrap.style.display = cb.checked ? 'flex' : 'none';
            }
            if (cb.checked) {
                card.style.borderColor = 'var(--primary)';
                card.style.background = 'rgba(46,98,72,0.04)';
            } else {
                card.style.borderColor = 'var(--border-color)';
                card.style.background = '#ffffff';
            }
        }

        function togglePageTypeMode(type) {
            const modBox = document.getElementById('modular-sections-box');
            if (modBox) {
                modBox.style.display = (type === 'modular') ? 'block' : 'none';
            }
        }

        function togglePageScheduledBox(status) {
            const box = document.getElementById('page_scheduled_box');
            if (box) {
                box.style.display = (status === 'scheduled') ? 'block' : 'none';
            }
        }

        function openPageEditor() {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">add_circle</span> ' + <?php echo json_encode(__a('admin.pages.new_page')); ?>;
            document.getElementById('page_form_action').value = 'add_page';
            document.getElementById('page_id').value = '';
            document.getElementById('page_title').value = '';
            document.getElementById('page_slug').value = '';
            document.getElementById('page_type').value = 'content';
            document.getElementById('page_icon').value = 'article';
            updatePageIconPreview('article');
            document.getElementById('page_nav_label').value = '';
            document.getElementById('page_status').value = 'published';
            if (document.getElementById('page_publish_at')) document.getElementById('page_publish_at').value = '';
            if (document.getElementById('page_revision_note')) document.getElementById('page_revision_note').value = '';
            togglePageScheduledBox('published');
            document.getElementById('page_in_nav').checked = false;
            document.getElementById('page_meta_desc').value = '';
            document.getElementById('page_content').value = '';

            document.querySelectorAll('.modular-sec-checkbox').forEach(cb => {
                cb.checked = false;
                toggleModularSecCard(cb);
            });
            document.querySelectorAll('.modular-sec-layout-select').forEach(sel => {
                sel.value = 'full';
            });
            togglePageTypeMode('content');

            PAGE_ACTIVE_LANGS.forEach(lang => {
                if (lang === PAGE_PRIMARY_LANG) return;
                const tTitle = document.getElementById('trans_' + lang + '_title');
                const tNav = document.getElementById('trans_' + lang + '_nav_label');
                const tMeta = document.getElementById('trans_' + lang + '_meta_desc');
                const tContent = document.getElementById('trans_' + lang + '_content');
                if (tTitle) tTitle.value = '';
                if (tNav) tNav.value = '';
                if (tMeta) tMeta.value = '';
                if (tContent) tContent.value = '';
            });

            const pTags = document.getElementById('page_tags');
            if (pTags) pTags.value = '';
            const cfCont = document.getElementById('page_custom_fields_container');
            if (cfCont) {
                cfCont.querySelectorAll('input, textarea, select').forEach(el => {
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                });
            }

            const sel = document.getElementById('sticky_page_selector');
            if (sel) sel.value = '';

            switchPageLangTab(PAGE_PRIMARY_LANG);
            updateStickyTranslationDots();
            document.getElementById('page-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.pages.btn_create')); ?>;
            document.getElementById('page_title').focus();
        }

        function closePageEditor() {
            document.getElementById('page-editor-box').style.display = 'none';
        }

        function updatePageIconPreview(val) {
            const prev = document.getElementById('page_icon_preview');
            if (prev) {
                prev.textContent = val && val.trim() ? val.trim() : 'article';
            }
        }

        function appendTagToField(fieldId, tag) {
            const input = document.getElementById(fieldId);
            if (!input) return;
            let parts = input.value.split(',').map(s => s.trim()).filter(s => s.length > 0);
            if (!parts.includes(tag)) {
                parts.push(tag);
                input.value = parts.join(', ');
            }
            input.focus();
        }

        function editPage(pageData, targetLang = null) {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">edit</span> ' + <?php echo json_encode(__a('admin.pages.edit_page')); ?>;
            document.getElementById('page_form_action').value = 'edit_page';
            document.getElementById('page_id').value = pageData.id || '';
            document.getElementById('page_title').value = pageData.title || '';
            document.getElementById('page_slug').value = pageData.slug || '';
            const pType = pageData.page_type || 'content';
            document.getElementById('page_type').value = pType;
            document.getElementById('page_icon').value = pageData.icon || 'article';
            updatePageIconPreview(pageData.icon || 'article');
            document.getElementById('page_nav_label').value = pageData.nav_label || '';
            const pStatus = pageData.status || 'published';
            document.getElementById('page_status').value = pStatus;
            if (document.getElementById('page_publish_at')) document.getElementById('page_publish_at').value = pageData.publish_at || '';
            if (document.getElementById('page_revision_note')) document.getElementById('page_revision_note').value = '';
            togglePageScheduledBox(pStatus);
            document.getElementById('page_in_nav').checked = !!pageData.in_nav;
            document.getElementById('page_meta_desc').value = pageData.meta_description || '';
            document.getElementById('page_content').value = pageData.content || '';

            const pTags = document.getElementById('page_tags');
            if (pTags) {
                pTags.value = Array.isArray(pageData.tags) ? pageData.tags.join(', ') : (pageData.tags || '');
            }

            const cfCont = document.getElementById('page_custom_fields_container');
            if (cfCont) {
                const cfValues = pageData.custom_fields || {};
                cfCont.querySelectorAll('input, textarea, select').forEach(el => {
                    const m = el.name && el.name.match(/^custom_fields\[([^\]]+)\]$/);
                    if (m) {
                        const k = m[1];
                        const val = cfValues[k] !== undefined ? cfValues[k] : '';
                        if (el.type === 'checkbox') {
                            el.checked = !!val && val !== '0';
                        } else {
                            el.value = val;
                        }
                    }
                });
            }

            const chosenSecs = Array.isArray(pageData.page_sections) ? pageData.page_sections : [];
            const secLayouts = pageData.page_section_layouts || {};
            document.querySelectorAll('.modular-sec-checkbox').forEach(cb => {
                cb.checked = chosenSecs.includes(cb.value);
                toggleModularSecCard(cb);
                const sel = document.getElementById('mod-layout-select-' + cb.value);
                if (sel) {
                    sel.value = secLayouts[cb.value] || 'full';
                }
            });
            togglePageTypeMode(pType);

            const trans = pageData.translations || {};
            PAGE_ACTIVE_LANGS.forEach(lang => {
                if (lang === PAGE_PRIMARY_LANG) return;
                const langTrans = trans[lang] || {};
                const tTitle = document.getElementById('trans_' + lang + '_title');
                const tNav = document.getElementById('trans_' + lang + '_nav_label');
                const tMeta = document.getElementById('trans_' + lang + '_meta_desc');
                const tContent = document.getElementById('trans_' + lang + '_content');
                if (tTitle) tTitle.value = langTrans.title || '';
                if (tNav) tNav.value = langTrans.nav_label || '';
                if (tMeta) tMeta.value = langTrans.meta_description || '';
                if (tContent) tContent.value = langTrans.content || '';
            });

            const sel = document.getElementById('sticky_page_selector');
            if (sel) sel.value = pageData.id || '';

            const switchLang = targetLang && PAGE_ACTIVE_LANGS.includes(targetLang) ? targetLang : PAGE_PRIMARY_LANG;
            switchPageLangTab(switchLang);
            updateStickyTranslationDots();
            document.getElementById('page-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.pages.btn_save')); ?>;
            document.getElementById('page-editor-box').scrollIntoView({ behavior: 'smooth' });
        }

        // Live-Aktualisierung der Statuspunkte bei Eingabe
        document.addEventListener('DOMContentLoaded', function() {
            const watchFields = ['page_title', 'page_content', 'page_nav_label'];
            PAGE_ACTIVE_LANGS.forEach(function(lang) {
                if (lang === PAGE_PRIMARY_LANG) return;
                watchFields.push('trans_' + lang + '_title', 'trans_' + lang + '_content', 'trans_' + lang + '_nav_label');
            });
            watchFields.forEach(function(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateStickyTranslationDots);
                }
            });
        });

        function autoGeneratePageSlug(title) {
            const slugInput = document.getElementById('page_slug');
            if (document.getElementById('page_form_action').value === 'add_page') {
                const map = {'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss'};
                let s = title.toLowerCase();
                for (let k in map) { s = s.split(k).join(map[k]); }
                s = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                slugInput.value = s;
            }
        }

        // Prüft, ob der Cursor innerhalb eines [grid]...[/grid]-Blocks liegt (Verschachtelung nicht unterstützt)
        function isCursorInsideGrid(target) {
            if (!target) return false;
            const beforeCursor = target.value.substring(0, target.selectionStart || target.value.length);
            const opened = (beforeCursor.match(/\[grid[\s\]]/gi) || []).length;
            const closed = (beforeCursor.match(/\[\/grid\]/gi) || []).length;
            return opened > closed;
        }

        function insertPageSnippet(selectElem, type) {
            const val = selectElem.value;
            if (!val) return;

            let target = document.activeElement;
            if (!target || target.tagName !== 'TEXTAREA') {
                const activeLangPanel = document.querySelector('.page-lang-panel:not([style*="display: none"])');
                target = activeLangPanel ? activeLangPanel.querySelector('textarea') : document.getElementById('page_content');
            }
            if (!target) {
                target = document.getElementById('page_content');
            }

            let snippet = '';
            const colorPicker = document.getElementById('picker_box_color');
            const chosenColor = colorPicker ? colorPicker.value : '#e0f2fe';

            if (type === 'heading') {
                const h = PAGE_HEADING_I18N;
                switch(val) {
                    case 'left_bar':
                        snippet = '\n<h3 class="heading-left-bar">' + h.left_bar + '</h3>\n';
                        break;
                    case 'underline':
                        snippet = '\n<h3 class="heading-underline">' + h.underline + '</h3>\n';
                        break;
                    case 'eyebrow':
                        snippet = '\n<div class="heading-eyebrow">' + h.eyebrow + '</div>\n<h2 class="heading-title">' + h.eyebrow_title + '</h2>\n';
                        break;
                    case 'gradient':
                        snippet = '\n<h2 class="heading-gradient">' + h.gradient + '</h2>\n';
                        break;
                    case 'icon':
                        snippet = '\n<h3 class="heading-icon"><span class="heading-icon-badge">✨</span> ' + h.icon + '</h3>\n';
                        break;
                    case 'display':
                        snippet = '\n<h1 class="heading-display">' + h.display + '</h1>\n';
                        break;
                }
            } else if (type === 'box') {
                // Shortcode-Variante: [box style="..."] bzw. [box color="#hex"]
                const b = PAGE_BOX_I18N;
                const attr = (val === 'tintable') ? 'color="' + chosenColor + '"' : 'style="' + val + '"';
                snippet = '\n[box ' + attr + ']\n### ' + b.title + '\n' + b.text + '\n[/box]\n';
            } else if (type === 'preset') {
                const preset = PAGE_BLOCK_PRESETS[val];
                if (preset) {
                    if (preset.indexOf('[grid') !== -1 && isCursorInsideGrid(target)) {
                        alert(PAGE_GRID_I18N.nested);
                        if (selectElem && selectElem.tagName === 'SELECT') {
                            selectElem.selectedIndex = 0;
                        }
                        return;
                    }
                    snippet = preset;
                }
            } else if (type === 'divider') {
                switch(val) {
                    case 'gradient':
                        snippet = '\n<div class="divider-gradient"></div>\n';
                        break;
                    case 'icon_leaf':
                        snippet = '\n<div class="divider-icon"><span>🌿</span></div>\n';
                        break;
                    case 'icon_star':
                        snippet = '\n<div class="divider-icon"><span>⭐</span></div>\n';
                        break;
                    case 'glow':
                        snippet = '\n<div class="divider-glow"></div>\n';
                        break;
                    case 'dots':
                        snippet = '\n<div class="divider-dots"></div>\n';
                        break;
                    case 'dashed':
                        snippet = '\n<div class="divider-dashed"></div>\n';
                        break;
                    case 'wave':
                        snippet = '\n<div class="divider-wave"></div>\n';
                        break;
                }
            } else if (type === 'grid') {
                const t = PAGE_GRID_I18N;

                // Verschachtelungsschutz: Cursor liegt bereits innerhalb eines [grid]...[/grid]?
                if (isCursorInsideGrid(target)) {
                    alert(t.nested);
                    if (selectElem && selectElem.tagName === 'SELECT') {
                        selectElem.selectedIndex = 0;
                    }
                    return;
                }
                const col = (n) => '[col]\n' + t.col.replace('{n}', n) + '\n[/col]\n';
                if (val === 'img-text') {
                    snippet = '\n[grid layout="img-text"]\n[col]\n' + t.image + '\n[/col]\n[col]\n' + t.text + '\n[/col]\n[/grid]\n';
                } else if (val === '3') {
                    snippet = '\n[grid layout="3"]\n' + col(1) + col(2) + col(3) + '[/grid]\n';
                } else if (['2', '1-2', '2-1'].includes(val)) {
                    snippet = '\n[grid layout="' + val + '"]\n' + col(1) + col(2) + '[/grid]\n';
                }
            }

            if (snippet && target) {
                const start = target.selectionStart || target.value.length;
                const end = target.selectionEnd || target.value.length;
                const text = target.value;
                target.value = text.substring(0, start) + snippet + text.substring(end);
                target.focus();
                const newPos = start + snippet.length;
                target.setSelectionRange(newPos, newPos);
            }

            if (selectElem && selectElem.tagName === 'SELECT') {
                selectElem.selectedIndex = 0;
            }
        }

        // ======================================================================
        // RESPONSIVE BREAKPOINT-VORSCHAU (PHASE 27.3)
        // ======================================================================
        function setPreviewBreakpoint(width) {
            const iframe = document.getElementById('page-preview-iframe');
            if (iframe) {
                iframe.style.width = width;
            }
            ['desktop', 'tablet', 'mobile'].forEach(bp => {
                const btn = document.getElementById('bp-btn-' + bp);
                if (btn) {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                }
            });
            if (width === '1200px') {
                const b = document.getElementById('bp-btn-desktop');
                if (b) { b.classList.add('btn-primary'); b.classList.remove('btn-secondary'); }
            } else if (width === '768px') {
                const b = document.getElementById('bp-btn-tablet');
                if (b) { b.classList.add('btn-primary'); b.classList.remove('btn-secondary'); }
            } else if (width === '375px') {
                const b = document.getElementById('bp-btn-mobile');
                if (b) { b.classList.add('btn-primary'); b.classList.remove('btn-secondary'); }
            }
        }

        function openPageLivePreview() {
            const modal = document.getElementById('page-preview-modal');
            const iframe = document.getElementById('page-preview-iframe');
            const loading = document.getElementById('page-preview-loading');
            if (!modal || !iframe) return;

            // Aktuellen Textarea-Inhalt ermitteln (je nach aktiver Sprache)
            let currentContent = '';
            let currentTitle = '';
            if (CURRENT_ACTIVE_PAGE_LANG === PAGE_PRIMARY_LANG) {
                currentContent = document.getElementById('page_content') ? document.getElementById('page_content').value : '';
                currentTitle = document.getElementById('page_title') ? document.getElementById('page_title').value : '';
            } else {
                const tContent = document.getElementById('trans_' + CURRENT_ACTIVE_PAGE_LANG + '_content');
                const tTitle = document.getElementById('trans_' + CURRENT_ACTIVE_PAGE_LANG + '_title');
                currentContent = (tContent && tContent.value.trim().length > 0) ? tContent.value : (document.getElementById('page_content') ? document.getElementById('page_content').value : '');
                currentTitle = (tTitle && tTitle.value.trim().length > 0) ? tTitle.value : (document.getElementById('page_title') ? document.getElementById('page_title').value : '');
            }

            modal.style.display = 'flex';
            if (loading) loading.style.display = 'flex';
            setPreviewBreakpoint('1200px');

            const formData = new URLSearchParams();
            formData.append('action', 'preview_page_content');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            formData.append('content', currentContent);
            formData.append('title', currentTitle);

            const pType = document.getElementById('page_type') ? document.getElementById('page_type').value : 'content';
            formData.append('page_type', pType);
            if (pType === 'modular') {
                document.querySelectorAll('.modular-sec-checkbox:checked').forEach(cb => {
                    formData.append('page_sections[]', cb.value);
                    const sel = document.getElementById('mod-layout-select-' + cb.value);
                    if (sel) {
                        formData.append('page_section_layouts[' + cb.value + ']', sel.value);
                    }
                });
            }

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(res => res.text())
            .then(html => {
                if (loading) loading.style.display = 'none';
                iframe.srcdoc = html;
            })
            .catch(err => {
                if (loading) loading.style.display = 'none';
                iframe.srcdoc = '<p style="color:red; font-family:sans-serif; padding:1.5rem;">' + <?php echo json_encode(__a('admin.pages.preview_error')); ?> + '</p>';
            });
        }

        function closePageLivePreview() {
            const modal = document.getElementById('page-preview-modal');
            const iframe = document.getElementById('page-preview-iframe');
            if (modal) modal.style.display = 'none';
            if (iframe) iframe.srcdoc = '';
        }
        </script>
