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

            <!-- Add gallery item -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neues Galerie-Element hinzufügen</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_gallery">
                    
                    <div class="form-group">
                        <label>Titel des Flechtwerks</label>
                        <input type="text" name="title" class="form-control" placeholder="z. B. Einkaufskorb Weide" required>
                    </div>

                    <div class="form-group">
                        <label>Beschreibung</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Details zur Herstellung oder Nutzung..."></textarea>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Kategorie</label>
                            <select name="category" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <?php foreach ($admin_categories as $catOption): ?>
                                    <option value="<?php echo htmlspecialchars($catOption); ?>"><?php echo htmlspecialchars($catOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto hochladen (JPG, PNG, WebP, GIF)</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">add_photo_alternate</span> Zur Galerie hinzufügen
                    </button>
                </form>
            </div>

            <!-- List and Reorder -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Galerie verwalten &amp; sortieren</h2>
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
                                        <h4 style="margin:0; font-size:1.1rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <span style="display:inline-block; font-size:0.75rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:2px 8px; border-radius:10px; margin-top:3px;"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <label style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Sortierung:</label>
                                        <input type="number" name="order[<?php echo $item['id']; ?>]" value="<?php echo htmlspecialchars($item['sort_order'] ?? 0); ?>" class="form-control" style="width:60px; padding:6px; margin:0;" min="0">
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
