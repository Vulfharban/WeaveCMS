<?php
/**
 * WeaveCMS – Admin Tab: Freie Einzelseiten (Custom Subpages)
 * 
 * Ermöglicht das Anlegen und Verwalten eigenständiger Inhaltsseiten
 * (z. B. Über die Werkstatt, Anfahrt & Parken, Nachhaltigkeit) mit
 * Markdown-Formatierung, eigenem Slug und Menü-Verknüpfung.
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
?>
        <!-- ──────────────── SECTION PAGES: FREIE EINZELSEITEN ──────────────── -->
        <div id="tab-pages" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">article</span> Freie Einzelseiten &amp; Unterseiten
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Erstelle beliebig viele Inhaltsseiten mit eigenem Link und binde sie optional direkt ins Hauptmenü ein.
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
            <div id="page-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
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
                    <input type="hidden" name="id" id="page_id" value="">

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="page_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Seitentitel *</label>
                            <input type="text" id="page_title" name="title" class="form-control" placeholder="z. B. Nachhaltigkeit &amp; Weidenanbau" required oninput="autoGeneratePageSlug(this.value)">
                        </div>
                        <div class="form-group">
                            <label for="page_slug" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                URL-Slug (Pfad) *
                                <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(z. B. nachhaltigkeit)</span>
                            </label>
                            <input type="text" id="page_slug" name="slug" class="form-control" placeholder="nachhaltigkeit" required pattern="[a-z0-9_-]+">
                        </div>
                    </div>

                    <div class="grid-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="page_nav_label" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Menü-Beschriftung</label>
                            <input type="text" id="page_nav_label" name="nav_label" class="form-control" placeholder="z. B. Nachhaltigkeit">
                        </div>
                        <div class="form-group">
                            <label for="page_status" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Status</label>
                            <select id="page_status" name="status" class="form-control">
                                <option value="published">Öffentlich (Veröffentlicht)</option>
                                <option value="draft">Entwurf (Nur im Admin sichtbar)</option>
                            </select>
                        </div>
                        <div class="form-group" style="display:flex; flex-direction:column; justify-content:center;">
                            <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Hauptmenü-Einbindung</label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                                <input type="checkbox" id="page_in_nav" name="in_nav" value="1">
                                Im Hauptmenü anzeigen
                            </label>
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
                        ?>
                        <div class="card" style="padding:1.25rem 1.5rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                                <div style="width:42px; height:42px; border-radius:8px; background:rgba(46,98,72,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <span class="material-icons">article</span>
                                </div>
                                <div>
                                    <h4 style="margin:0 0 4px 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                                        <?php echo htmlspecialchars($p['title'] ?? 'Unbenannt'); ?>
                                        <?php if ($is_published): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#10b981; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:999px;">Veröffentlicht</span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#f59e0b; background:rgba(245,158,11,0.1); padding:2px 8px; border-radius:999px;">Entwurf</span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['in_nav'])): ?>
                                            <span style="font-size:0.7rem; font-weight:700; color:#3b82f6; background:rgba(59,130,246,0.1); padding:2px 8px; border-radius:999px;">Im Menü</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; gap:12px; flex-wrap:wrap;">
                                        <span>Link: <code>?page=<?php echo htmlspecialchars($p_slug); ?></code></span>
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
        function openPageEditor() {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">add_circle</span> Neue Einzelseite anlegen';
            document.getElementById('page_form_action').value = 'add_page';
            document.getElementById('page_id').value = '';
            document.getElementById('page_title').value = '';
            document.getElementById('page_slug').value = '';
            document.getElementById('page_nav_label').value = '';
            document.getElementById('page_status').value = 'published';
            document.getElementById('page_in_nav').checked = false;
            document.getElementById('page_meta_desc').value = '';
            document.getElementById('page_content').value = '';
            document.getElementById('page-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Seite anlegen';
            document.getElementById('page_title').focus();
        }

        function closePageEditor() {
            document.getElementById('page-editor-box').style.display = 'none';
        }

        function editPage(pageData) {
            document.getElementById('page-editor-box').style.display = 'block';
            document.getElementById('page-editor-title').innerHTML = '<span class="material-icons">edit</span> Einzelseite bearbeiten';
            document.getElementById('page_form_action').value = 'edit_page';
            document.getElementById('page_id').value = pageData.id || '';
            document.getElementById('page_title').value = pageData.title || '';
            document.getElementById('page_slug').value = pageData.slug || '';
            document.getElementById('page_nav_label').value = pageData.nav_label || '';
            document.getElementById('page_status').value = pageData.status || 'published';
            document.getElementById('page_in_nav').checked = !!pageData.in_nav;
            document.getElementById('page_meta_desc').value = pageData.meta_description || '';
            document.getElementById('page_content').value = pageData.content || '';
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
