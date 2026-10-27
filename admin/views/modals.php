<?php
/**
 * WeaveCMS – Admin Modals (Restore Modal & Welcome Modal)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ======================================================================
             MODAL: WIEDERHERSTELLUNG BESTÄTIGEN (PIN-GESCHÜTZT)
             ====================================================================== -->
        <div id="restore-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:3000; align-items:center; justify-content:center; backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px);" onclick="if(event.target===this) closeRestoreModal();">
            <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:18px; max-width:480px; width:calc(100% - 2rem); padding:2rem; box-shadow:0 25px 60px rgba(0,0,0,0.35); position:relative;">
                <h3 style="margin:0 0 0.75rem 0; color:var(--primary); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:24px;">settings_backup_restore</span> Sicherung wiederherstellen
                </h3>
                <p style="color:var(--text-secondary); font-size:0.9rem; line-height:1.5; margin-bottom:1.25rem;">
                    Möchtest du die Sicherung <strong id="modal-backup-filename" style="color:var(--text-primary); font-family:monospace; word-break:break-all;"></strong> wirklich einspielen?<br>
                    Alle aktuellen Website-Daten werden durch den Stand dieser Sicherung ersetzt. Zur Sicherheit legt das System vorab automatisch einen Notfall-Snapshot an.
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" id="modal-backup-file-input" name="backup_file" value="">

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label style="font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem; font-size:0.9rem;">
                            Administrator-PIN zur Autorisierung <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="password" id="modal-restore-pin" name="restore_pin" class="form-control" placeholder="Aktuelle PIN eingeben" maxlength="10" required style="padding-right:40px; letter-spacing:2px;">
                            <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('modal-restore-pin')">visibility</span>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-outline" onclick="closeRestoreModal()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:16px;">check_circle</span> Jetzt wiederherstellen
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================================
             MODAL: WILLKOMMEN NACH INSTALLATION (PHASE 5 - SCHRITT 5)
             ====================================================================== -->
        <?php 
            $show_welcome = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && (isset($_GET['installed']) || empty($data['settings']['welcome_shown']));
        ?>
        <?php if ($show_welcome): ?>
        <div id="welcome-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:3500; align-items:center; justify-content:center; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); padding: 1.5rem;">
            <div class="card" style="max-width: 680px; width:100%; border:1px solid #34d399; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6); padding: 2.2rem; background: var(--bg-card); border-radius: 20px;">
                <div style="display:flex; align-items:center; gap: 14px; margin-bottom: 1.25rem;">
                    <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(52, 211, 153, 0.15); border: 1px solid #34d399; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                        🌿
                    </div>
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin:0;">Herzlich willkommen bei WeaveCMS!</h2>
                        <p style="color: var(--text-secondary); font-size: 0.92rem; margin-top: 2px;">Dein Website-System ist erfolgreich eingerichtet und bereit für deine Inhalte.</p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">edit_note</span> 1. Texte &amp; Über mich
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Passe Titel, Slogans und deinen persönlichen Biografie-Text an.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">palette</span> 2. Farben &amp; Themes
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Wähle aus 4 kuratierten Natur-Farbthemen und Galerie-Layouts.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">photo_library</span> 3. Galerie &amp; Workshops
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Lade Fotos hoch, vergebe Kategorien und trage Kurstermine ein.
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 12px 14px; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); display:flex; align-items:center; gap:6px; margin-bottom: 4px;">
                            <span class="material-icons" style="font-size: 18px;">security</span> 4. Schutz &amp; Backups
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                            Setup ist gesperrt (<code style="font-family:monospace; color:#34d399;">installed.lock</code>). Lege hier 1-Klick-Sicherungen an.
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap: 12px; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                    <button type="button" class="btn btn-primary" style="padding: 10px 22px; font-weight: 600; display:inline-flex; align-items:center; gap:8px;" onclick="dismissWelcomeModal()">
                        <span class="material-icons" style="font-size: 18px;">check</span> Loslegen &amp; Administrationsbereich erkunden
                    </button>
                </div>
            </div>
        </div>
        <script>
        function dismissWelcomeModal() {
            const modal = document.getElementById('welcome-modal');
            if (modal) {
                modal.style.transition = 'opacity 0.25s ease';
                modal.style.opacity = '0';
                setTimeout(() => { modal.style.display = 'none'; }, 250);
            }
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'dismiss_welcome',
                    csrf_token: <?php echo json_encode($csrf_token); ?>,
                    ajax: '1'
                })
            }).catch(e => console.error(e));
        }
        </script>
        <?php endif; ?>

        <!-- ======================================================================
             MODAL: REVISIONSHISTORIE, DIFF & ROLLBACK (PHASE 23)
             ====================================================================== -->
        <div id="modal-revisions" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:3200; align-items:center; justify-content:center; backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px);" onclick="if(event.target===this) closeRevisionsModal();">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; max-width:850px; width:calc(100% - 2rem); max-height:90vh; display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.45); overflow:hidden;">
                <!-- Header -->
                <div style="padding:1.25rem 1.75rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
                    <h3 style="margin:0; color:var(--primary); font-size:1.2rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:24px;">history</span>
                        <span><?php echo __a('admin.revisions.title', 'Revisionshistorie'); ?>: <span id="rev-modal-item-title" style="color:var(--text-primary); font-weight:700;"></span></span>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeRevisionsModal()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>

                <!-- Body (Scrollable) -->
                <div style="padding:1.5rem 1.75rem; overflow-y:auto; flex:1;">
                    <p style="color:var(--text-secondary); font-size:0.88rem; margin:0 0 1.25rem 0;">
                        <?php echo __a('admin.revisions.desc', 'Hier findest du alle automatisch gespeicherten Versionen dieses Inhalts. Vergleiche Änderungen per Diff-Ansicht oder setze frühere Stände mit einem Klick zurück.'); ?>
                    </p>

                    <!-- Revisions-Liste -->
                    <div id="rev-modal-list-box" style="margin-bottom:1.5rem;">
                        <div id="rev-modal-loading" style="text-align:center; padding:2rem; color:var(--text-secondary);">
                            <span class="material-icons" style="font-size:28px; animation:spin 1s linear infinite;">sync</span>
                            <div style="margin-top:6px; font-size:0.9rem;"><?php echo __a('admin.revisions.loading', 'Revisionen werden geladen...'); ?></div>
                        </div>
                        <div id="rev-modal-list" style="display:none; display:flex; flex-direction:column; gap:10px;"></div>
                    </div>

                    <!-- Diff-Ansicht Box -->
                    <div id="rev-modal-diff-box" style="display:none; border-top:1px solid var(--border-color); padding-top:1.25rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                            <h4 style="margin:0; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                                <span class="material-icons" style="font-size:18px; color:var(--primary);">difference</span>
                                <span><?php echo __a('admin.revisions.diff_title', 'Änderungsvergleich (Diff gegen aktuellen Stand):'); ?></span>
                            </h4>
                            <button type="button" class="btn btn-secondary" onclick="closeRevisionDiff()" style="padding:3px 8px; font-size:0.75rem;">
                                <?php echo __a('admin.revisions.diff_close', 'Diff schließen'); ?>
                            </button>
                        </div>
                        <div id="rev-modal-diff-container"></div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding:1rem 1.75rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:10px; background:rgba(255,255,255,0.02);">
                    <button type="button" class="btn btn-secondary" onclick="closeRevisionsModal()"><?php echo __a('admin.btn.close', 'Schließen'); ?></button>
                </div>
            </div>
        </div>

        <script>
        let REV_CURRENT_TYPE = '';
        let REV_CURRENT_ID = '';

        function openRevisionsModal(type, id, title) {
            REV_CURRENT_TYPE = type;
            REV_CURRENT_ID = id;
            document.getElementById('rev-modal-item-title').textContent = title || id;
            document.getElementById('rev-modal-loading').style.display = 'block';
            document.getElementById('rev-modal-list').style.display = 'none';
            document.getElementById('rev-modal-diff-box').style.display = 'none';

            const modal = document.getElementById('modal-revisions');
            modal.style.display = 'flex';

            fetch('index.php?action=get_revisions&item_type=' + encodeURIComponent(type) + '&item_id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(res => {
                    document.getElementById('rev-modal-loading').style.display = 'none';
                    const listEl = document.getElementById('rev-modal-list');
                    listEl.style.display = 'flex';
                    listEl.innerHTML = '';

                    if (!res.success || !res.revisions || res.revisions.length === 0) {
                        listEl.innerHTML = '<div style="text-align:center; padding:1.5rem; color:var(--text-secondary); background:rgba(255,255,255,0.02); border-radius:8px;">Noch keine gespeicherten Revisionsstände für dieses Element vorhanden. Revisionen werden automatisch bei jeder Speicherung angelegt.</div>';
                        return;
                    }

                    res.revisions.forEach((rev, idx) => {
                        const isLatest = (idx === 0);
                        const card = document.createElement('div');
                        card.style.cssText = 'background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;';

                        const info = document.createElement('div');
                        info.style.cssText = 'flex:1; min-width:260px;';
                        info.innerHTML = `
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                <strong style="color:var(--text-primary); font-size:0.92rem;">${rev.created_at || 'Unbekannt'}</strong>
                                ${isLatest ? '<span style="font-size:0.7rem; font-weight:700; color:#10b981; background:rgba(16,185,129,0.15); padding:1px 6px; border-radius:4px;">Aktuellster Stand</span>' : ''}
                                <span style="font-size:0.75rem; color:var(--text-secondary); background:rgba(255,255,255,0.06); padding:1px 6px; border-radius:4px;">${rev.status || 'published'}</span>
                            </div>
                            <div style="font-size:0.82rem; color:var(--text-secondary); display:flex; gap:12px; align-items:center;">
                                <span><span class="material-icons" style="font-size:14px; vertical-align:middle;">person</span> ${rev.author || 'System'}</span>
                                ${rev.note ? `<span><span class="material-icons" style="font-size:14px; vertical-align:middle;">notes</span> <em>${escapeRevHtml(rev.note)}</em></span>` : ''}
                            </div>
                        `;

                        const actions = document.createElement('div');
                        actions.style.cssText = 'display:flex; align-items:center; gap:8px;';
                        actions.innerHTML = `
                            <button type="button" class="btn btn-secondary" onclick="viewRevisionDiff('${rev.rev_id}')" style="padding:5px 10px; font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                <span class="material-icons" style="font-size:15px;">difference</span> Diff
                            </button>
                            ${!isLatest ? `
                            <button type="button" class="btn btn-outline" onclick="performRollback('${rev.rev_id}', '${rev.created_at}')" style="padding:5px 10px; font-size:0.8rem; color:#3b82f6; border-color:rgba(59,130,246,0.3); display:inline-flex; align-items:center; gap:4px;">
                                <span class="material-icons" style="font-size:15px;">settings_backup_restore</span> Wiederherstellen
                            </button>` : ''}
                        `;

                        card.appendChild(info);
                        card.appendChild(actions);
                        listEl.appendChild(card);
                    });
                })
                .catch(err => {
                    document.getElementById('rev-modal-loading').style.display = 'none';
                    document.getElementById('rev-modal-list').style.display = 'block';
                    document.getElementById('rev-modal-list').innerHTML = '<div style="color:#ef4444; padding:1rem;">Fehler beim Laden der Revisionen: ' + err + '</div>';
                });
        }

        function closeRevisionsModal() {
            document.getElementById('modal-revisions').style.display = 'none';
        }

        function viewRevisionDiff(revId) {
            const diffBox = document.getElementById('rev-modal-diff-box');
            const diffContainer = document.getElementById('rev-modal-diff-container');
            diffBox.style.display = 'block';
            diffContainer.innerHTML = '<div style="text-align:center; padding:1.5rem; color:var(--text-secondary);"><span class="material-icons" style="font-size:24px; animation:spin 1s linear infinite;">sync</span> Berechne Diff...</div>';
            diffBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            fetch('index.php?action=get_revision_diff&item_type=' + encodeURIComponent(REV_CURRENT_TYPE) + '&item_id=' + encodeURIComponent(REV_CURRENT_ID) + '&rev_id=' + encodeURIComponent(revId))
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.diffHtml) {
                        diffContainer.innerHTML = res.diffHtml;
                    } else {
                        diffContainer.innerHTML = '<div style="color:#ef4444; padding:1rem;">Diff konnte nicht generiert werden: ' + (res.error || 'Unbekannter Fehler') + '</div>';
                    }
                })
                .catch(err => {
                    diffContainer.innerHTML = '<div style="color:#ef4444; padding:1rem;">Fehler: ' + err + '</div>';
                });
        }

        function closeRevisionDiff() {
            document.getElementById('rev-modal-diff-box').style.display = 'none';
        }

        function performRollback(revId, createdAt) {
            if (!confirm('Möchtest du den Revisionsstand vom ' + (createdAt || revId) + ' wirklich wiederherstellen?\n\nDein aktueller Stand wird vorab automatisch als Notfall-Snapshot gesichert.')) {
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'rollback_revision');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            formData.append('item_type', REV_CURRENT_TYPE);
            formData.append('item_id', REV_CURRENT_ID);
            formData.append('rev_id', revId);

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Erfolg: ' + (res.message || 'Stand wiederhergestellt!'));
                    window.location.reload();
                } else {
                    alert('Fehler: ' + (res.message || 'Wiederherstellung fehlgeschlagen'));
                }
            })
            .catch(err => {
                alert('Netzwerk- oder Serverfehler: ' + err);
            });
        }

        function escapeRevHtml(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        </script>

