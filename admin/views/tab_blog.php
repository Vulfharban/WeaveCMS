<?php
/**
 * WeaveCMS – Admin Tab: Blog & Magazin
 * 
 * Ermöglicht das Verfassen, Bearbeiten und Verwalten zeitbasierter
 * Artikel, Werkstattberichte und Neuigkeiten mit Markdown-Unterstützung.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$blog_posts = $data['blog'] ?? [];
$total_posts = count($blog_posts);
$published_posts = 0;
$draft_posts = 0;
foreach ($blog_posts as $bp) {
    if (($bp['status'] ?? 'published') === 'published') {
        $published_posts++;
    } else {
        $draft_posts++;
    }
}
$author_default = $data['texts']['contact_name'] ?? 'Maxima Musterfrau';
?>
        <!-- ──────────────── SECTION BLOG: ARTIKEL & AKTUELLES ──────────────── -->
        <div id="tab-blog" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">newspaper</span> Blog &amp; Aktuelles
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Verfasse Werkstattberichte, Neuigkeiten und Ankündigungen für deine Besucher.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openBlogEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neuer Artikel
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Artikel Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_posts; ?></div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Veröffentlicht</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top:4px;"><?php echo $published_posts; ?></div>
                </div>
                <div style="background: rgba(245, 158, 11, 0.05); border:1px solid rgba(245, 158, 11, 0.3); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: #f59e0b; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Entwürfe (Privat)</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-top:4px;"><?php echo $draft_posts; ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'blog';
                $current_tab = 'tab-blog';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="blog-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="blog-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">edit_note</span> Neuer Blog-Artikel
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeBlogEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="blog-post-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="blog_form_action" value="add_blog_post">
                    <input type="hidden" name="id" id="blog_post_id" value="">

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="blog_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Artikel-Titel *</label>
                            <input type="text" id="blog_title" name="title" class="form-control" placeholder="z. B. Neuer Brennofen &amp; Herbst-Kollektion" required oninput="autoGenerateBlogSlug(this.value)">
                        </div>
                        <div class="form-group">
                            <label for="blog_slug" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">URL-Slug (Webadresse) *</label>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="color:var(--text-secondary); font-size:0.85rem;">?blog=</span>
                                <input type="text" id="blog_slug" name="slug" class="form-control" placeholder="neuer-brennofen-herbst-kollektion" required>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="blog_date" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Veröffentlichungsdatum</label>
                            <input type="date" id="blog_date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="blog_author" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Autor / Verfasser</label>
                            <input type="text" id="blog_author" name="author" class="form-control" value="<?php echo htmlspecialchars($author_default); ?>">
                        </div>
                        <div class="form-group">
                            <label for="blog_status" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Sichtbarkeit / Status</label>
                            <select id="blog_status" name="status" class="form-control" onchange="toggleBlogScheduledBox(this.value)">
                                <option value="published">🟢 Veröffentlicht (Sofort sichtbar)</option>
                                <option value="draft">🟡 Entwurf (Nur per Token / Admin)</option>
                                <option value="scheduled">⏱️ Zeitgesteuert (Geplant)</option>
                            </select>
                            <div id="blog_scheduled_box" style="display:none; margin-top:8px;">
                                <label for="blog_publish_at" style="font-size:0.78rem; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;">Veröffentlichungszeitpunkt</label>
                                <input type="datetime-local" id="blog_publish_at" name="publish_at" class="form-control" style="font-size:0.85rem; padding:4px 8px;">
                            </div>
                        </div>
                    </div>

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="blog_image" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Beitragsbild (Foto/Titelbild)</label>
                            <input type="file" id="blog_image" name="image" accept="image/*" class="form-control" style="padding:7px;">
                            <div id="blog_current_image_preview" style="display:none; margin-top:8px; align-items:center; gap:10px;">
                                <img id="blog_preview_img" src="" style="width:64px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--border-color);">
                                <label style="display:flex; align-items:center; gap:6px; font-size:0.82rem; color:var(--text-secondary); cursor:pointer;">
                                    <input type="checkbox" name="remove_image" value="1" id="blog_remove_image"> Bild entfernen
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="blog_tags" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Schlagwörter / Tags (Kommagetrennt)</label>
                            <input type="text" id="blog_tags" name="tags" class="form-control" placeholder="z. B. Werkstatt, Kurse, Impressionen">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="blog_teaser" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                            Kurzer Anreißertext (Teaser)
                        </label>
                        <textarea id="blog_teaser" name="teaser" class="form-control" rows="2" placeholder="Ein kurzer, ansprechender Einführungssatz für die Übersichtskarte und Social-Media Vorschau..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                            <label for="blog_content" style="font-weight:700; color:var(--text-primary); margin:0;">
                                Ausführlicher Artikel-Inhalt (Markdown unterstützt)
                            </label>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                Formatierung: <code>## Überschrift</code>, <code>**fett**</code>, <code>*kursiv*</code>, <code>- Liste</code>
                            </span>
                        </div>
                        <textarea id="blog_content" name="content" class="form-control" rows="10" placeholder="Schreibe hier deinen Artikel...&#10;&#10;## Eine neue Etappe&#10;In den vergangenen Wochen ist viel passiert...&#10;&#10;- Punkt eins&#10;- Punkt zwei"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="blog_revision_note" style="font-weight:600; font-size:0.85rem; color:var(--text-secondary); display:block; margin-bottom:0.25rem;">
                            <span class="material-icons" style="font-size:15px; vertical-align:middle;">history_edu</span> Revisions-Notiz (optional):
                        </label>
                        <input type="text" id="blog_revision_note" name="revision_note" class="form-control" placeholder="z. B. Rechtschreibung korrigiert oder neuen Abschnitt ergänzt" style="font-size:0.85rem;">
                    </div>

                    <div style="display:flex; align-items:center; gap:12px;">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons" style="font-size:18px;">save</span> Artikel speichern
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeBlogEditor()">
                            Abbrechen
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── ARTIKEL-LISTE ──────────────── -->
            <?php if (empty($blog_posts)): ?>
                <div style="text-align:center; padding:3.5rem 1.5rem; background:rgba(0,0,0,0.02); border:2px dashed var(--border-color); border-radius:16px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); opacity:0.5; margin-bottom:0.75rem;">article</span>
                    <h3 style="font-size:1.2rem; color:var(--text-primary); margin-bottom:0.5rem;">Noch keine Blog-Artikel vorhanden</h3>
                    <p style="color:var(--text-secondary); max-width:440px; margin:0 auto 1.5rem; font-size:0.9rem;">
                        Halte deine Website lebendig und teile spannende Einblicke, neue Kurse oder Fotos direkt aus deiner Werkstatt mit deinen Besuchern.
                    </p>
                    <button type="button" class="btn btn-primary" onclick="openBlogEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Ersten Artikel verfassen
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php foreach ($blog_posts as $post): ?>
                        <?php 
                            $pid = htmlspecialchars($post['id'] ?? '');
                            $p_title = htmlspecialchars($post['title'] ?? 'Ohne Titel');
                            $p_slug = htmlspecialchars($post['slug'] ?? '');
                            $p_date = htmlspecialchars($post['date'] ?? '');
                            $p_author = htmlspecialchars($post['author'] ?? '');
                            $raw_status = $post['status'] ?? 'published';
                            $p_publish_at = $post['publish_at'] ?? '';
                            $p_token = $post['preview_token'] ?? '';
                            $preview_url = '../?blog=' . urlencode($post['slug'] ?? '') . ($p_token ? '&preview=' . urlencode($p_token) : '');
                            $p_img = !empty($post['image']) ? '../' . ltrim($post['image'], '/') : '';
                            $p_tags = $post['tags'] ?? [];
                        ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.25rem; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:14px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                                <?php if (!empty($p_img)): ?>
                                    <img src="<?php echo $p_img; ?>" alt="<?php echo $p_title; ?>" style="width:72px; height:52px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:72px; height:52px; background:rgba(0,0,0,0.05); border:1px solid var(--border-color); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--text-secondary);">
                                        <span class="material-icons" style="font-size:24px; opacity:0.6;">article</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary); font-weight:700;">
                                            <?php echo $p_title; ?>
                                        </h4>
                                        <?php if ($raw_status === 'published'): ?>
                                            <span style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(16,185,129,0.15); color:#10b981;">
                                                Veröffentlicht
                                            </span>
                                        <?php elseif ($raw_status === 'scheduled'): ?>
                                            <span style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(59,130,246,0.15); color:#3b82f6;" title="Geplant für: <?php echo htmlspecialchars($p_publish_at); ?>">
                                                ⏱️ Geplant (<?php echo date('d.m.Y H:i', strtotime($p_publish_at ?: 'now')); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(245,158,11,0.15); color:#f59e0b;">
                                                Entwurf
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.83rem; color:var(--text-secondary); display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                        <span><span class="material-icons" style="font-size:14px; vertical-align:middle;">event</span> <?php echo $p_date; ?></span>
                                        <span><span class="material-icons" style="font-size:14px; vertical-align:middle;">person</span> <?php echo $p_author; ?></span>
                                        <code style="font-size:0.78rem; color:var(--primary); font-family:monospace;">?blog=<?php echo $p_slug; ?></code>
                                        <?php if (!empty($p_tags)): ?>
                                            <span>
                                                <?php foreach ($p_tags as $tag): ?>
                                                    <span style="background:rgba(0,0,0,0.06); padding:1px 6px; border-radius:4px; font-size:0.74rem; margin-right:4px;">#<?php echo htmlspecialchars($tag); ?></span>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <a href="<?php echo htmlspecialchars($preview_url); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem;" title="Vorschau inklusive sicherem Token im Frontend ansehen">
                                    <span class="material-icons" style="font-size:16px;">visibility</span> Vorschau
                                </a>

                                <button type="button" class="btn btn-secondary" onclick="openRevisionsModal('blog', '<?php echo htmlspecialchars($post['id'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES); ?>')" style="padding:6px 12px; font-size:0.82rem;" title="Revisionshistorie &amp; Rollback ansehen">
                                    <span class="material-icons" style="font-size:16px;">history</span> Revisions
                                </button>

                                <button type="button" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem;" onclick="editBlogPost(<?php echo htmlspecialchars(json_encode($post), ENT_QUOTES, 'UTF-8'); ?>)">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>

                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="toggle_blog_status">
                                    <input type="hidden" name="id" value="<?php echo $pid; ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; font-size:0.82rem;" title="<?php echo $p_status ? 'Als Entwurf verstecken' : 'Jetzt veröffentlichen'; ?>">
                                        <span class="material-icons" style="font-size:16px; color:<?php echo $p_status ? '#f59e0b' : '#10b981'; ?>;"><?php echo $p_status ? 'visibility_off' : 'visibility'; ?></span>
                                    </button>
                                </form>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du diesen Blog-Artikel wirklich unwiderruflich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_blog_post">
                                    <input type="hidden" name="id" value="<?php echo $pid; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:6px 10px; font-size:0.82rem; background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);" title="Artikel löschen">
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
            function toggleBlogScheduledBox(status) {
                const box = document.getElementById('blog_scheduled_box');
                if (box) {
                    box.style.display = (status === 'scheduled') ? 'block' : 'none';
                }
            }

            function openBlogEditor() {
                document.getElementById('blog_form_action').value = 'add_blog_post';
                document.getElementById('blog_post_id').value = '';
                document.getElementById('blog-editor-title').innerHTML = '<span class="material-icons">add_circle</span> Neuer Blog-Artikel';
                document.getElementById('blog_title').value = '';
                document.getElementById('blog_slug').value = '';
                document.getElementById('blog_date').value = new Date().toISOString().split('T')[0];
                document.getElementById('blog_author').value = <?php echo json_encode($author_default); ?>;
                document.getElementById('blog_status').value = 'published';
                if (document.getElementById('blog_publish_at')) document.getElementById('blog_publish_at').value = '';
                if (document.getElementById('blog_revision_note')) document.getElementById('blog_revision_note').value = '';
                toggleBlogScheduledBox('published');
                document.getElementById('blog_tags').value = '';
                document.getElementById('blog_teaser').value = '';
                document.getElementById('blog_content').value = '';
                document.getElementById('blog_image').value = '';
                document.getElementById('blog_current_image_preview').style.display = 'none';

                const box = document.getElementById('blog-editor-box');
                box.style.display = 'block';
                box.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function editBlogPost(post) {
                document.getElementById('blog_form_action').value = 'edit_blog_post';
                document.getElementById('blog_post_id').value = post.id || '';
                document.getElementById('blog-editor-title').innerHTML = '<span class="material-icons">edit</span> Artikel bearbeiten: ' + escapeBlogHtml(post.title || '');
                document.getElementById('blog_title').value = post.title || '';
                document.getElementById('blog_slug').value = post.slug || '';
                document.getElementById('blog_date').value = post.date || new Date().toISOString().split('T')[0];
                document.getElementById('blog_author').value = post.author || '';
                const bStatus = post.status || 'published';
                document.getElementById('blog_status').value = bStatus;
                if (document.getElementById('blog_publish_at')) document.getElementById('blog_publish_at').value = post.publish_at || '';
                if (document.getElementById('blog_revision_note')) document.getElementById('blog_revision_note').value = '';
                toggleBlogScheduledBox(bStatus);
                document.getElementById('blog_tags').value = Array.isArray(post.tags) ? post.tags.join(', ') : (post.tags || '');
                document.getElementById('blog_teaser').value = post.teaser || '';
                document.getElementById('blog_content').value = post.content || '';
                document.getElementById('blog_image').value = '';

                const prevBox = document.getElementById('blog_current_image_preview');
                const prevImg = document.getElementById('blog_preview_img');
                const removeCb = document.getElementById('blog_remove_image');
                if (removeCb) removeCb.checked = false;

                if (post.image) {
                    prevImg.src = '../' + post.image.replace(/^\/+/, '');
                    prevBox.style.display = 'flex';
                } else {
                    prevBox.style.display = 'none';
                }

                const box = document.getElementById('blog-editor-box');
                box.style.display = 'block';
                box.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function closeBlogEditor() {
                document.getElementById('blog-editor-box').style.display = 'none';
            }

            function autoGenerateBlogSlug(val) {
                // Nur automatisch generieren, wenn es ein neuer Artikel ist
                if (document.getElementById('blog_form_action').value !== 'add_blog_post') return;
                let s = val.toLowerCase();
                s = s.replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss');
                s = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                document.getElementById('blog_slug').value = s;
            }

            function escapeBlogHtml(str) {
                return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        </script>
