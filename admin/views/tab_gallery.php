<?php
/**
 * WeaveCMS – Admin Tab: Galerie & Kategorien
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 * @var array $admin_categories Liste aller aktiven Galerie-Kategorien
 * @var array $cat_counts Zuordnung Kategorie => Anzahl Werkstücke
 */
?>
        <!-- ──────────────── SECTION 2: GALLERY ──────────────── -->
        <div id="tab-gallery" class="admin-section">
            <?php 
                $section_id = 'gallery';
                $current_tab = 'tab-gallery';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>
            <!-- Active Layout Info Banner -->
            <?php 
                $active_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur';
                $layout_names = [
                    'ambient_blur' => 'Smart Ambient-Blur (Ruhiges Raster & voller Bildinhalt)',
                    'masonry' => 'Masonry-Grid (Pinterest-Stil / Natürliche Bildhöhen)',
                    'editorial' => 'Magazin-Raster (Editorial Grid / Hochkant über 2 Zeilen)',
                    'classic' => 'Klassisches 4:3 Raster (Fester Zuschnitt)'
                ];
                $layout_hints = [
                    'ambient_blur' => 'Ideal für gemischte Formate. Querformat: 1200×900 px (4:3) oder 1000×1000 px. Hochkant-Bilder (z. B. 3:4, 9:16 ab 1000 px) werden ohne Beschnitt mit weichgezeichnetem Bildrahmen dargestellt.',
                    'masonry' => 'Empfohlene Bildbreite: ca. 800–1400 px. Höhe beliebig – jedes Bild wird 100% vollständig in seiner natürlichen Proportion abgebildet.',
                    'editorial' => 'Querformat: 1200×900 px (4:3). Hochformat: 900×1200 px (spannen automatisch über 2 Rasterzeilen).',
                    'classic' => 'Exakt 1200×900 px (4:3 Querformat). Wichtige Bildmotive müssen mittig liegen, da Außenränder beschnitten werden.'
                ];
            ?>
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 14px; padding: 1.25rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                        <span class="material-icons" style="color:var(--primary); font-size:22px;">view_quilt</span>
                        <strong style="color:var(--text-primary); font-size:1.05rem;">Aktives Galerie-Layout:</strong>
                        <span class="layout-badge"><?php echo htmlspecialchars($layout_names[$active_layout] ?? $active_layout); ?></span>
                    </div>
                    <div style="color:var(--text-secondary); font-size:0.88rem; line-height:1.45; margin-top:4px;">
                        💡 <b>Empfohlene Bildgröße:</b> <?php echo htmlspecialchars($layout_hints[$active_layout] ?? ''); ?>
                    </div>
                </div>
                <button type="button" class="btn btn-outline" onclick="switchTab('tab-display')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:16px;">tune</span> Layout &amp; Formate ändern
                </button>
            </div>

            <!-- Galerie-Kategorien verwalten (Definition & Löschen) -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">category</span> Galerie-Kategorien verwalten
                </h2>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height: 1.5;">
                    Lege hier eigene Kategorien für deine Flechtwerke fest oder entferne nicht mehr benötigte Kategorien. 
                    <br><em>Hinweis:</em> Beim Löschen einer Kategorie bleiben die zugeordneten Werkstücke erhalten, werden aber im Galerie-Filter auf der Website nicht mehr angezeigt.
                </p>

                <!-- Neue Kategorie hinzufügen -->
                <form method="POST" action="" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin-bottom: 1.75rem; background: rgba(0,0,0,0.02); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color);">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group" style="flex:1; min-width:240px; margin-bottom:0;">
                        <label style="font-weight:600; margin-bottom:6px; display:block;">Neue Kategorie anlegen</label>
                        <input type="text" name="category_name" class="form-control" placeholder="z. B. Rankhilfen, Weidentaschen..." required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height:44px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:18px;">add</span> Kategorie hinzufügen
                    </button>
                </form>

                <!-- Vorhandene Kategorien auflisten -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                    Vorhandene Kategorien (<?php echo count($admin_categories); ?>)
                </label>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($admin_categories as $catItem): 
                        $cCount = $cat_counts[$catItem] ?? 0;
                    ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); padding:0.75rem 1.25rem; border-radius:10px; border:1px solid var(--border-color); flex-wrap:wrap; gap:0.75rem;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="material-icons" style="color:var(--primary); font-size:20px;">label</span>
                                <strong style="font-size:1.02rem;"><?php echo htmlspecialchars($catItem); ?></strong>
                                <span style="font-size:0.8rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:2px 8px; border-radius:12px; font-weight:600;">
                                    <?php echo $cCount; ?> <?php echo ($cCount === 1) ? 'Werkstück' : 'Werkstücke'; ?>
                                </span>
                            </div>
                            <form method="POST" action="" onsubmit="return confirmCategoryDelete(<?php echo htmlspecialchars(json_encode($catItem)); ?>, <?php echo (int)$cCount; ?>);" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($catItem); ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 14px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px; background:#dc2626;">
                                    <span class="material-icons" style="font-size:16px;">delete</span> Löschen
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

<?php
$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$all_languages = LanguageManager::getSupportedLanguages();
?>
            <!-- Add gallery item -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neues Galerie-Element hinzufügen</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_gallery">

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs Upload -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;">Inhaltssprachen:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="add_gal-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="add_gal-btn-tab-<?php echo $al; ?>"
                                            onclick="switchGalLangTab('add_gal', '<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' (Hauptsprache)' : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache Neuer Eintrag -->
                    <div id="add_gal-lang-content-<?php echo $primary_lang; ?>" class="add_gal-lang-content">
                        <div class="form-group">
                            <label>Titel des Flechtwerks * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <input type="text" name="title" class="form-control" placeholder="z. B. Einkaufskorb Weide" required>
                        </div>

                        <div class="form-group">
                            <label>Beschreibung <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Details zur Herstellung oder Nutzung..."></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="add_gal-lang-content-<?php echo $al; ?>" class="add_gal-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ Übersetze Titel und Beschreibung für Besucher mit Sprache <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>. Falls leer, wird die Hauptsprache verwendet.
                            </div>
                            <div class="form-group">
                                <label>Titel des Flechtwerks (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="z. B. Willow Basket (EN)">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                <textarea name="trans_<?php echo $al; ?>_description" class="form-control" rows="2" placeholder="Description in <?php echo htmlspecialchars($lang_info['name']); ?>..."></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-top: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Kategorie</label>
                            <select name="category" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <?php foreach ($admin_categories as $catOption): ?>
                                    <option value="<?php echo htmlspecialchars($catOption); ?>"><?php echo htmlspecialchars($catOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto hochladen (JPG, PNG, WebP, GIF) *</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">add_photo_alternate</span> Zur Galerie hinzufügen
                    </button>
                </form>
            </div>

            <!-- ──────────────── EDIT DRAWER ──────────────── -->
            <div id="gallery-edit-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit</span> Galerie-Element bearbeiten &amp; übersetzen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeGalleryEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="gallery-edit-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="edit_gallery">
                    <input type="hidden" name="id" id="edit_gal_id" value="">

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs Edit -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;">Inhaltssprachen:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="edit_gal-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="edit_gal-btn-tab-<?php echo $al; ?>"
                                            onclick="switchGalLangTab('edit_gal', '<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' (Hauptsprache)' : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache Edit -->
                    <div id="edit_gal-lang-content-<?php echo $primary_lang; ?>" class="edit_gal-lang-content">
                        <div class="form-group">
                            <label>Titel des Flechtwerks * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <input type="text" id="edit_gal_title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Beschreibung <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <textarea id="edit_gal_desc" name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="edit_gal-lang-content-<?php echo $al; ?>" class="edit_gal-lang-content" style="display:none;">
                            <div class="form-group">
                                <label>Titel (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                <input type="text" id="edit_gal_trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                <textarea id="edit_gal_trans_<?php echo $al; ?>_desc" name="trans_<?php echo $al; ?>_description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-top: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Kategorie</label>
                            <select id="edit_gal_category" name="category" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <?php foreach ($admin_categories as $catOption): ?>
                                    <option value="<?php echo htmlspecialchars($catOption); ?>"><?php echo htmlspecialchars($catOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Neues Foto (Optional zum Ersetzen)</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeGalleryEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- List and Reorder -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Galerie verwalten, bearbeiten &amp; sortieren</h2>
                <?php if (empty($data['gallery'])): ?>
                    <p style="color: var(--text-secondary);">Keine Arbeiten in der Galerie.</p>
                <?php else: ?>
                    <form method="POST" action="" style="margin-bottom: 2rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="reorder_gallery">
                        <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom: 1.5rem;">
                            <?php foreach ($data['gallery'] as $item): ?>
                                <div style="display:flex; align-items:center; gap:1rem; background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px; border:1px solid var(--border-color); flex-wrap:wrap;">
                                    <img src="..<?php echo htmlspecialchars($item['image']); ?>" class="preview-img" alt="">
                                    <div style="flex:1; min-width:200px;">
                                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <h4 style="margin:0; font-size:1.1rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                            <?php if (count($active_langs) > 1): ?>
                                                <?php foreach ($active_langs as $al): ?>
                                                    <?php if ($al === $primary_lang) continue; ?>
                                                    <?php if (!empty($item['translations'][$al]['title']) || !empty($item['translations'][$al]['description'])): 
                                                        $li = $all_languages[$al] ?? ['flag' => '🌐'];
                                                    ?>
                                                        <span title="Übersetzung für <?php echo strtoupper($al); ?> gepflegt" style="font-size:0.75rem; background:rgba(16,185,129,0.12); color:var(--primary); border:1px solid rgba(16,185,129,0.25); padding:1px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:2px;">
                                                            <?php echo $li['flag']; ?> <?php echo strtoupper($al); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <span style="display:inline-block; font-size:0.75rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:2px 8px; border-radius:10px; margin-top:3px;"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <button type="button" class="btn btn-secondary" onclick="openGalleryEditor(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                            <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                        </button>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <label style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Sortierung:</label>
                                            <input type="number" name="order[<?php echo $item['id']; ?>]" value="<?php echo htmlspecialchars($item['sort_order'] ?? 0); ?>" class="form-control" style="width:60px; padding:6px; margin:0;" min="0">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-outline" style="width:100%;">
                            <span class="material-icons" style="font-size:18px; vertical-align:middle;">sort</span> Reihenfolge aktualisieren
                        </button>
                    </form>

                    <h3 style="font-size:1.2rem; margin-top:2rem; margin-bottom:1rem; border-top:1px solid var(--border-color); padding-top:1.5rem;">Galerie-Elemente löschen</h3>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach ($data['gallery'] as $item): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(239, 68, 68, 0.02); padding:0.75rem 1rem; border-radius:12px; border:1px solid rgba(239,68,68,0.15); flex-wrap:wrap; gap:1rem;">
                                <div style="display:flex; align-items:center; gap:1rem;">
                                    <img src="..<?php echo htmlspecialchars($item['image']); ?>" style="width:40px; height:40px; object-fit:cover; border-radius:6px;" alt="">
                                    <span style="font-weight:600;"><?php echo htmlspecialchars($item['title']); ?></span>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Möchtest du dieses Bild wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_gallery">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:6px 12px; font-size:0.85rem;">Löschen</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        const activeGalLangs = <?php echo json_encode($active_langs); ?>;
        const primaryGalLang = <?php echo json_encode($primary_lang); ?>;

        function switchGalLangTab(prefix, lang) {
            document.querySelectorAll('.' + prefix + '-lang-content').forEach(el => el.style.display = 'none');
            const target = document.getElementById(prefix + '-lang-content-' + lang);
            if (target) target.style.display = 'block';

            document.querySelectorAll('.' + prefix + '-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            const activeBtn = document.getElementById(prefix + '-btn-tab-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-secondary');
                activeBtn.classList.add('btn-primary');
            }
        }

        function openGalleryEditor(item) {
            document.getElementById('gallery-edit-box').style.display = 'block';
            document.getElementById('edit_gal_id').value = item.id || '';
            document.getElementById('edit_gal_title').value = item.title || '';
            document.getElementById('edit_gal_desc').value = item.description || '';
            document.getElementById('edit_gal_category').value = item.category || 'Sonstiges';

            const tr = item.translations || {};
            activeGalLangs.forEach(al => {
                if (al !== primaryGalLang) {
                    const tEl = document.getElementById('edit_gal_trans_' + al + '_title');
                    const dEl = document.getElementById('edit_gal_trans_' + al + '_desc');
                    if (tEl) tEl.value = tr[al]?.title || '';
                    if (dEl) dEl.value = tr[al]?.description || '';
                }
            });

            switchGalLangTab('edit_gal', primaryGalLang);
            document.getElementById('gallery-edit-box').scrollIntoView({ behavior: 'smooth' });
        }

        function closeGalleryEditor() {
            document.getElementById('gallery-edit-box').style.display = 'none';
        }
        </script>
