<?php
/**
 * WeaveCMS – Admin Tab: Team & Mitarbeiter (Profilkarten)
 * 
 * Ermöglicht das Anlegen und Verwalten von Teammitgliedern,
 * Meistern, Kursleitern oder Mitarbeitern mit Profilbild, Rolle und Kurzvita.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$team = $data['team'] ?? [];
$total_team = count($team);
?>
        <!-- ──────────────── SECTION TEAM: TEAM & MEISTER ──────────────── -->
        <div id="tab-team" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">badge</span> Team &amp; Mitarbeiter
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Präsentiere dein Team, deine Kursleiterinnen oder Meister mit ansprechenden Profilkarten und Porträts.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openTeamEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neues Mitglied
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Mitglieder Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_team; ?></div>
                </div>
            </div>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="team-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="team-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">person_add</span> Neues Teammitglied anlegen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeTeamEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="team_form_action" value="add_team_member">
                    <input type="hidden" name="id" id="team_id" value="">

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="team_name" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Name *</label>
                            <input type="text" id="team_name" name="name" class="form-control" placeholder="z. B. Maxima Musterfrau" required>
                        </div>
                        <div class="form-group">
                            <label for="team_role" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Rolle / Position *</label>
                            <input type="text" id="team_role" name="role" class="form-control" placeholder="z. B. Inhaberin &amp; Korbmachermeisterin" required>
                        </div>
                    </div>

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="team_email" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">E-Mail-Adresse (Optional)</label>
                            <input type="email" id="team_email" name="email" class="form-control" placeholder="muster@atelier-beispiel.de">
                        </div>
                        <div class="form-group">
                            <label for="team_image" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Profilbild (Upload)</label>
                            <input type="file" id="team_image" name="image" class="form-control" accept="image/*">
                            <div id="team_current_img_box" style="display:none; margin-top:8px; align-items:center; gap:8px;">
                                <img id="team_current_img_preview" src="" alt="Vorschau" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                                <label style="font-size:0.85rem; color:#ef4444; cursor:pointer;">
                                    <input type="checkbox" name="remove_image" value="1"> Bild entfernen
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="team_bio" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Kurzbeschreibung / Vita</label>
                        <textarea id="team_bio" name="bio" class="form-control" rows="4" placeholder="Kurze Beschreibung zur Person, Spezialisierung oder Leidenschaft..."></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeTeamEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="team-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Mitglied speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── MITGLIEDER-LISTE ──────────────── -->
            <?php if (empty($team)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">groups</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine Teammitglieder hinterlegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Stelle dich selbst und deine Kursleiter oder Mitarbeiter sympathisch vor.</p>
                    <button type="button" class="btn btn-primary" onclick="openTeamEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Erstes Mitglied anlegen
                    </button>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
                    <?php foreach ($team as $m): ?>
                        <div class="card" style="padding:1.5rem; border-radius:14px; display:flex; flex-direction:column; justify-content:space-between; gap:1rem;">
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <?php if (!empty($m['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($m['image']); ?>" alt="<?php echo htmlspecialchars($m['name'] ?? ''); ?>" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid var(--primary); flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:64px; height:64px; border-radius:50%; background:rgba(46,98,72,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <span class="material-icons" style="font-size:32px;">person</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin:0 0 4px 0; font-size:1.1rem; color:var(--text-primary);">
                                        <?php echo htmlspecialchars($m['name'] ?? ''); ?>
                                    </h4>
                                    <span style="font-size:0.8rem; font-weight:700; color:var(--primary); background:rgba(46,98,72,0.08); padding:2px 8px; border-radius:999px;">
                                        <?php echo htmlspecialchars($m['role'] ?? ''); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($m['bio'])): ?>
                                <p style="font-size:0.88rem; color:var(--text-secondary); line-height:1.5; margin:0;">
                                    <?php echo htmlspecialchars(mb_strimwidth($m['bio'], 0, 140, '...')); ?>
                                </p>
                            <?php endif; ?>

                            <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid var(--border-color); padding-top:0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="editTeam(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du dieses Teammitglied wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_team_member">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Mitglied löschen">
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
        function openTeamEditor() {
            document.getElementById('team-editor-box').style.display = 'block';
            document.getElementById('team-editor-title').innerHTML = '<span class="material-icons">person_add</span> Neues Teammitglied anlegen';
            document.getElementById('team_form_action').value = 'add_team_member';
            document.getElementById('team_id').value = '';
            document.getElementById('team_name').value = '';
            document.getElementById('team_role').value = '';
            document.getElementById('team_email').value = '';
            document.getElementById('team_bio').value = '';
            document.getElementById('team_current_img_box').style.display = 'none';
            document.getElementById('team-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Mitglied anlegen';
            document.getElementById('team_name').focus();
        }

        function closeTeamEditor() {
            document.getElementById('team-editor-box').style.display = 'none';
        }

        function editTeam(memberData) {
            document.getElementById('team-editor-box').style.display = 'block';
            document.getElementById('team-editor-title').innerHTML = '<span class="material-icons">edit</span> Teammitglied bearbeiten';
            document.getElementById('team_form_action').value = 'edit_team_member';
            document.getElementById('team_id').value = memberData.id || '';
            document.getElementById('team_name').value = memberData.name || '';
            document.getElementById('team_role').value = memberData.role || '';
            document.getElementById('team_email').value = memberData.email || '';
            document.getElementById('team_bio').value = memberData.bio || '';
            if (memberData.image) {
                document.getElementById('team_current_img_preview').src = '../' + memberData.image;
                document.getElementById('team_current_img_box').style.display = 'flex';
            } else {
                document.getElementById('team_current_img_box').style.display = 'none';
            }
            document.getElementById('team-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('team-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>
