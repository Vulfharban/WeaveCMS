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
                        <span class="material-icons" style="font-size:28px;">article</span> Freie Einzelseiten &amp; Unterseiten
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Erstelle beliebig viele Inhaltsseiten in mehreren Sprachen mit eigenem Link und binde sie optional direkt ins Hauptmenü ein.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openPageEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neue Einzelseite
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Seiten Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_pages; ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Veröffentlicht</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top:4px;"><?php echo $published_pages; ?></div>
                </div>
                <div style="background: rgba(59, 130, 246, 0.05); border:1px solid rgba(59, 130, 246, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #3b82f6; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Im Hauptmenü verlinkt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #3b82f6; margin-top:4px;"><?php echo $in_nav_count; ?></div>
                </div>
            </div>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="page-editor-box" style="display:none; scroll-margin-top: 150px; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="page-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit_note</span> Neue Einzelseite anlegen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closePageEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" id="page-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="page_form_action" value="add_page">
                    <input type="hidden" name="target_tab" value="tab-pages">
                    <input type="hidden" name="id" id="page_id" value="">

                    <!-- Sprach-Reiter im Seiteneditor -->
                    <?php if (count($active_langs) > 1): ?>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap; background:rgba(255,255,255,0.03); padding:10px 14px; border-radius:10px; border:1px solid var(--border-color);">
                            <span style="font-size:0.88rem; font-weight:700; color:var(--text-secondary); margin-right:4px;">
                                <span class="material-icons" style="font-size:16px; vertical-align:middle;">translate</span> Inhaltssprache:
                            </span>
                            <button type="button" class="btn btn-primary page-lang-tab-btn" id="btn-page-lang-<?php echo $primary_lang; ?>" onclick="switchPageLangTab('<?php echo $primary_lang; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                <span><?php echo $supported_langs[$primary_lang]['flag']; ?></span>
                                <span><?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?> (Hauptsprache)</span>
                            </button>
                            <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                <button type="button" class="btn btn-outline page-lang-tab-btn" id="btn-page-lang-<?php echo $al; ?>" onclick="switchPageLangTab('<?php echo $al; ?>')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                    <span><?php echo $supported_langs[$al]['flag']; ?></span>
                                    <span><?php echo htmlspecialchars($supported_langs[$al]['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Gemeinsame Einstellungen: Status, Seitentyp, Hauptmenü-Einbindung, Basis-Slug, Icon -->
                    <div class="grid-5" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background:rgba(255,255,255,0.02); padding:1rem; border-radius:10px; border:1px solid var(--border-color);">
                        <div class="form-group">
                            <label for="page_slug" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                URL-Slug (Pfad) *
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(z. B. aktion)</span>
                            </label>
                            <input type="text" id="page_slug" name="slug" class="form-control" placeholder="aktion" required pattern="[a-z0-9_-]+">
                        </div>
                        <div class="form-group">
                            <label for="page_type" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Seitentyp</label>
                            <select id="page_type" name="page_type" class="form-control" onchange="togglePageTypeMode(this.value)">
                                <option value="content">Standard-Inhalt (Text/Markdown)</option>
                                <option value="modular">Modulare Sektions-Landingpage</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="page_status" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Status</label>
                            <select id="page_status" name="status" class="form-control">
                                <option value="published">Öffentlich (Veröffentlicht)</option>
                                <option value="draft">Entwurf (Nur im Admin sichtbar)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="page_icon" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                Menü-Icon
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(Material-Icon)</span>
                            </label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <input type="text" id="page_icon" name="icon" class="form-control" placeholder="article" oninput="updatePageIconPreview(this.value)">
                                <span id="page_icon_preview" class="material-icons" style="font-size:22px; color:var(--primary); min-width:28px; text-align:center;">article</span>
                            </div>
                        </div>
                        <div class="form-group" style="display:flex; flex-direction:column; justify-content:center;">
                            <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Hauptmenü-Einbindung</label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                                <input type="checkbox" id="page_in_nav" name="in_nav" value="1">
                                <span>Im Menü verlinken</span>
                            </label>
                        </div>
                    </div>

                    <!-- Phase 18.2: Modulare Sektionen-Auswahl (Landingpage-Stack) -->
                    <div id="modular-sections-box" style="display:none; background:rgba(46,98,72,0.06); border:1px solid rgba(46,98,72,0.3); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <h4 style="color:var(--primary); margin:0 0 0.4rem 0; font-size:1.05rem; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons">view_quilt</span> Sektionen für diese modulare Landingpage zusammenstellen
                        </h4>
                        <p style="color:var(--text-secondary); font-size:0.85rem; margin:0 0 1rem 0;">
                            Wähle die gewünschten Inhaltsbereiche aus, die auf dieser Seite als fortlaufender Sektionen-Stack gerendert werden:
                        </p>
                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap:10px;">
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
                                <label style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:#ffffff; border:1px solid var(--border-color); border-radius:10px; cursor:pointer; font-size:0.9rem;">
                                    <input type="checkbox" name="page_sections[]" value="<?php echo htmlspecialchars($asec['id']); ?>" class="modular-sec-checkbox" style="width:16px; height:16px; accent-color:var(--primary);">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary);"><?php echo $aicon; ?></span>
                                    <span style="font-weight:600; color:var(--text-primary);"><?php echo htmlspecialchars($asec['title']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Panel: Hauptsprache (z. B. Deutsch) -->
                    <div id="page-lang-panel-<?php echo $primary_lang; ?>" class="page-lang-panel">
                        <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="page_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    Seitentitel (<?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?>) *
                                </label>
                                <input type="text" id="page_title" name="title" class="form-control" placeholder="z. B. Nachhaltigkeit &amp; Weidenanbau" required oninput="autoGeneratePageSlug(this.value)">
                            </div>
                            <div class="form-group">
                                <label for="page_nav_label" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    Titel fürs Menü (Menü-Beschriftung)
                                </label>
                                <input type="text" id="page_nav_label" name="nav_label" class="form-control" placeholder="z. B. Nachhaltigkeit (leer = Seitentitel)">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="page_meta_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                Meta-Description für Suchmaschinen (SEO)
                            </label>
                            <input type="text" id="page_meta_desc" name="meta_description" class="form-control" placeholder="Kurze Beschreibung dieser Seite für Google &amp; Social Media">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="page_content" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                <span>Seiteninhalt (Markdown) *</span>
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">Überschriften mit #, Absätze, **Fett**, [Links](url)</span>
                            </label>
                            <textarea id="page_content" name="content" class="form-control" rows="12" placeholder="Schreibe hier den vollständigen Text deiner Seite..." required style="font-family: monospace; font-size:0.95rem; line-height:1.6;"></textarea>
                        </div>
                    </div>

                    <!-- Panels für weitere aktive Sprachen -->
                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                        <div id="page-lang-panel-<?php echo $al; ?>" class="page-lang-panel" style="display:none;">
                            <div style="background:rgba(59, 130, 246, 0.08); border:1px solid #3b82f6; border-radius:10px; padding:10px 14px; margin-bottom:1.25rem; font-size:0.88rem; color:#93c5fd; display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="font-size:18px; color:#60a5fa;">translate</span>
                                <span>Übersetzung für <strong><?php echo $supported_langs[$al]['flag'] . ' ' . htmlspecialchars($supported_langs[$al]['name']); ?></strong>. Bleibt ein Feld leer, greift das System automatisch auf die Hauptsprache (<?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?>) zurück.</span>
                            </div>

                            <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                                <div class="form-group">
                                    <label for="trans_<?php echo $al; ?>_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Seitentitel (<?php echo htmlspecialchars($supported_langs[$al]['name']); ?>)
                                    </label>
                                    <input type="text" id="trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="Titel auf <?php echo htmlspecialchars($supported_langs[$al]['name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="trans_<?php echo $al; ?>_nav_label" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                        Titel fürs Menü auf <?php echo htmlspecialchars($supported_langs[$al]['name']); ?>
                                    </label>
                                    <input type="text" id="trans_<?php echo $al; ?>_nav_label" name="trans_<?php echo $al; ?>_nav_label" class="form-control" placeholder="Menü-Titel auf <?php echo htmlspecialchars($supported_langs[$al]['name']); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="trans_<?php echo $al; ?>_meta_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    Meta-Description (<?php echo htmlspecialchars($supported_langs[$al]['name']); ?>)
                                </label>
                                <input type="text" id="trans_<?php echo $al; ?>_meta_desc" name="trans_<?php echo $al; ?>_meta_description" class="form-control" placeholder="SEO-Beschreibung auf <?php echo htmlspecialchars($supported_langs[$al]['name']); ?>">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="trans_<?php echo $al; ?>_content" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                    <span>Seiteninhalt (Markdown auf <?php echo htmlspecialchars($supported_langs[$al]['name']); ?>)</span>
                                </label>
                                <textarea id="trans_<?php echo $al; ?>_content" name="trans_<?php echo $al; ?>_content" class="form-control" rows="12" placeholder="Übersetzter Markdown-Inhalt..." style="font-family: monospace; font-size:0.95rem; line-height:1.6;"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closePageEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="page-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Seite speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── SEITEN-LISTE / TABELLE ──────────────── -->
            <?php if (empty($pages)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">article</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine freien Einzelseiten angelegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Erstelle separate Unterseiten für ausführliche Firmenporträts, Anfahrtspläne oder spezielle Kooperationen.</p>
                    <button type="button" class="btn btn-primary" onclick="openPageEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Erste Einzelseite anlegen
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($pages as $p): ?>
                        <?php 
                            $is_published = ($p['status'] ?? 'published') === 'published';
                            $p_slug = $p['slug'] ?? '';
                            $p_url = '../?page=' . urlencode($p_slug);
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
                                                Modulare Landingpage (<?php echo $sec_count; ?> Sektionen)
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; font-weight:600; color:var(--text-secondary); background:rgba(255,255,255,0.06); padding:2px 8px; border-radius:999px;">
                                                Standard-Inhalt
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_published): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#10b981; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:999px;">Veröffentlicht</span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#f59e0b; background:rgba(245,158,11,0.1); padding:2px 8px; border-radius:999px;">Entwurf</span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['in_nav'])): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:2px 8px; border-radius:999px;" title="Titel im Menü: <?php echo htmlspecialchars($p_nav); ?>">
                                                Menü: <strong><?php echo htmlspecialchars($p_nav); ?></strong>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:var(--text-secondary); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:999px;">Nicht im Menü</span>
                                        <?php endif; ?>
                                    </h4>
                                    
                                    <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; gap:14px; flex-wrap:wrap; align-items:center; margin-top:4px;">
                                        <span>Pfad: <code>?page=<?php echo htmlspecialchars($p_slug); ?></code></span>
                                        
                                        <!-- Sprach-Badges -->
                                        <span style="display:inline-flex; align-items:center; gap:4px;">
                                            <span style="font-size:0.75rem; color:var(--text-secondary);">Sprachen:</span>
                                            <span style="font-size:0.72rem; font-weight:700; background:rgba(16,185,129,0.15); color:#10b981; padding:1px 6px; border-radius:4px;" title="<?php echo htmlspecialchars($supported_langs[$primary_lang]['name']); ?> (Hauptsprache)">
                                                <?php echo $supported_langs[$primary_lang]['flag'] . ' ' . strtoupper($primary_lang); ?>
                                            </span>
                                            <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                                                <?php 
                                                    $has_trans = !empty($p['translations'][$al]['title']) || !empty($p['translations'][$al]['content']) || !empty($p['translations'][$al]['nav_label']); 
                                                ?>
                                                <span style="font-size:0.72rem; font-weight:700; background:<?php echo $has_trans ? 'rgba(16,185,129,0.15)' : 'rgba(255,255,255,0.05)'; ?>; color:<?php echo $has_trans ? '#10b981' : 'var(--text-secondary)'; ?>; padding:1px 6px; border-radius:4px; border:1px solid <?php echo $has_trans ? 'rgba(16,185,129,0.3)' : 'var(--border-color)'; ?>;" title="<?php echo $has_trans ? 'Übersetzung für ' . htmlspecialchars($supported_langs[$al]['name']) . ' vorhanden' : 'Keine Übersetzung (greift auf ' . htmlspecialchars($supported_langs[$primary_lang]['name']) . ' zurück)'; ?>">
                                                    <?php echo $supported_langs[$al]['flag'] . ' ' . strtoupper($al); ?> <?php echo $has_trans ? '✓' : '—'; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <a href="<?php echo htmlspecialchars($p_url); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem; text-decoration:none;" title="Seite in neuem Tab ansehen">
                                    <span class="material-icons" style="font-size:16px;">visibility</span> Ansehen
                                </a>
                                <button type="button" class="btn btn-secondary" onclick="editPage(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du diese Einzelseite wirklich unwiderruflich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_page">
                                    <input type="hidden" name="target_tab" value="tab-pages">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Seite löschen">
                                        <span class="material-icons" style="font-size:16px;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        const PAGE_ACTIVE_LANGS = <?php echo json_encode($active_langs); ?>;
        const PAGE_PRIMARY_LANG = <?php echo json_encode($primary_lang); ?>;

        function switchPageLangTab(lang) {
            document.querySelectorAll('.page-lang-panel').forEach(el => el.style.display = 'none');
            const targetPanel = document.getElementById('page-lang-panel-' + lang);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }

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

        function togglePageTypeMode(type) {
            const modBox = document.getElementById('modular-sections-box');
            if (modBox) {
                modBox.style.display = (type === 'modular') ? 'block' : 'none';
            }
        }

        function openPageEditor() {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">add_circle</span> Neue Einzelseite anlegen';
            document.getElementById('page_form_action').value = 'add_page';
            document.getElementById('page_id').value = '';
            document.getElementById('page_title').value = '';
            document.getElementById('page_slug').value = '';
            document.getElementById('page_type').value = 'content';
            document.getElementById('page_icon').value = 'article';
            updatePageIconPreview('article');
            document.getElementById('page_nav_label').value = '';
            document.getElementById('page_status').value = 'published';
            document.getElementById('page_in_nav').checked = false;
            document.getElementById('page_meta_desc').value = '';
            document.getElementById('page_content').value = '';

            document.querySelectorAll('.modular-sec-checkbox').forEach(cb => {
                cb.checked = false;
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

            switchPageLangTab(PAGE_PRIMARY_LANG);
            document.getElementById('page-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Seite anlegen';
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

        function editPage(pageData) {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">edit</span> Einzelseite bearbeiten';
            document.getElementById('page_form_action').value = 'edit_page';
            document.getElementById('page_id').value = pageData.id || '';
            document.getElementById('page_title').value = pageData.title || '';
            document.getElementById('page_slug').value = pageData.slug || '';
            const pType = pageData.page_type || 'content';
            document.getElementById('page_type').value = pType;
            document.getElementById('page_icon').value = pageData.icon || 'article';
            updatePageIconPreview(pageData.icon || 'article');
            document.getElementById('page_nav_label').value = pageData.nav_label || '';
            document.getElementById('page_status').value = pageData.status || 'published';
            document.getElementById('page_in_nav').checked = !!pageData.in_nav;
            document.getElementById('page_meta_desc').value = pageData.meta_description || '';
            document.getElementById('page_content').value = pageData.content || '';

            const chosenSecs = Array.isArray(pageData.page_sections) ? pageData.page_sections : [];
            document.querySelectorAll('.modular-sec-checkbox').forEach(cb => {
                cb.checked = chosenSecs.includes(cb.value);
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

            switchPageLangTab(PAGE_PRIMARY_LANG);
            document.getElementById('page-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('page-editor-box').scrollIntoView({ behavior: 'smooth' });
        }

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
        </script>
