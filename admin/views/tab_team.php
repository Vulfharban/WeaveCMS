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
                        <span class="material-icons" style="font-size:28px;">badge</span> <?php echo __a('admin.team.title', 'Team- & Dozentenprofile verwalten'); ?>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        <?php echo __a('admin.team.desc', 'Präsentiere dein Team, Dozenten oder Kooperationspartner mit Bild, Rolle und Kurzbiografie.'); ?>
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openTeamEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.team.btn_new', '+ Neues Profil anlegen'); ?>
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.team.kpi_total', 'Teammitglieder gesamt'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_team; ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'team';
                $current_tab = 'tab-team';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

<?php
$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$all_languages = LanguageManager::getSupportedLanguages();
?>
            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="team-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="team-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">person_add</span> <?php echo __a('admin.team.drawer_new', 'Neues Teammitglied anlegen'); ?>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeTeamEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> <?php echo __a('admin.team.drawer_close', 'Schließen'); ?>
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="team_form_action" value="add_team_member">
                    <input type="hidden" name="id" id="team_id" value="">

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="team_name" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.team.field_name', 'Vollständiger Name:'); ?> *</label>
                            <input type="text" id="team_name" name="name" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_name', 'z. B. Bianka Wolfrum')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="team_email" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.team.field_email', 'E-Mail (optional für Kontaktanfragen):'); ?></label>
                            <input type="email" id="team_email" name="email" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_email', 'z. B. kontakt@example.de')); ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="team_image" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.team.field_image', 'Profilfoto (JPG, PNG, WebP):'); ?></label>
                        <input type="file" id="team_image" name="image" class="form-control" accept="image/*">
                        <div id="team_current_img_box" style="display:none; margin-top:8px; align-items:center; gap:8px;">
                            <img id="team_current_img_preview" src="" alt="Vorschau" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                            <label style="font-size:0.85rem; color:#ef4444; cursor:pointer;">
                                <input type="checkbox" name="remove_image" value="1"> <?php echo __a('admin.team.btn_remove_image', 'Aktuelles Bild entfernen'); ?>
                            </label>
                        </div>
                    </div>

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs im Editor -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;"><?php echo __a('admin.team.lang_content', 'Inhalte auf'); ?>:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;" id="team-lang-tab-bar">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="team-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="team-btn-tab-<?php echo $al; ?>"
                                            onclick="switchTeamLangTab('<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' ' . __a('admin.team.primary_lang_suffix', '(Hauptsprache)') : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache -->
                    <div id="team-lang-content-<?php echo $primary_lang; ?>" class="team-lang-content">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="team_role" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.team.field_role', 'Rolle / Berufsbezeichnung:'); ?> * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span>
                            </label>
                            <input type="text" id="team_role" name="role" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_role', 'z. B. Korbflechterin & Kursleiterin')); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="team_bio" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.team.field_bio', 'Kurzbiografie / Über die Person:'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span>
                            </label>
                            <textarea id="team_bio" name="bio" class="form-control" rows="4" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_bio', 'Ein paar persönliche Worte zum Werdegang, Leidenschaft und Qualifikation...')); ?>"></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="team-lang-content-<?php echo $al; ?>" class="team-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ <?php echo __a('admin.team.trans_info', 'Übersetzungen für aktivierte Sprachen:'); ?> <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>.
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="team_trans_<?php echo $al; ?>_role" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.team.field_role_trans', 'Rolle ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <input type="text" id="team_trans_<?php echo $al; ?>_role" name="trans_<?php echo $al; ?>_role" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_role_trans', 'Rolle in {lang}...', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="team_trans_<?php echo $al; ?>_bio" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.team.field_bio_trans', 'Biografie ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <textarea id="team_trans_<?php echo $al; ?>_bio" name="trans_<?php echo $al; ?>_bio" class="form-control" rows="4" placeholder="<?php echo htmlspecialchars(__a('admin.team.placeholder_bio_trans', 'Biografie in {lang}...', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeTeamEditor()"><?php echo __a('admin.team.btn_cancel', 'Abbrechen'); ?></button>
                        <button type="submit" class="btn btn-primary" id="team-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> <?php echo __a('admin.team.btn_save_new', 'Profil anlegen'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── MITGLIEDER-LISTE ──────────────── -->
            <?php if (empty($team)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">groups</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);"><?php echo __a('admin.team.empty_title', 'Noch keine Teammitglieder angelegt'); ?></h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;"><?php echo __a('admin.team.empty_desc', 'Stelle dich und dein Team persönlich vor. Das schafft Vertrauen bei deinen Besuchern und Kursteilnehmern.'); ?></p>
                    <button type="button" class="btn btn-primary" onclick="openTeamEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.team.empty_btn', 'Erstes Teammitglied anlegen'); ?>
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
                                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                        <span style="font-size:0.8rem; font-weight:700; color:var(--primary); background:rgba(46,98,72,0.08); padding:2px 8px; border-radius:999px;">
                                            <?php echo htmlspecialchars($m['role'] ?? ''); ?>
                                        </span>
                                        <?php if (count($active_langs) > 1): ?>
                                            <?php foreach ($active_langs as $al): ?>
                                                <?php if ($al === $primary_lang) continue; ?>
                                                <?php if (!empty($m['translations'][$al]['role']) || !empty($m['translations'][$al]['bio'])): 
                                                    $li = $all_languages[$al] ?? ['flag' => '🌐'];
                                                ?>
                                                    <span title="<?php echo htmlspecialchars(__a('admin.team.trans_badge_title', 'Übersetzung vorhanden für: ') . strtoupper($al)); ?>" style="font-size:0.75rem; background:rgba(16,185,129,0.12); color:var(--primary); border:1px solid rgba(16,185,129,0.25); padding:1px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:2px;">
                                                        <?php echo $li['flag']; ?> <?php echo strtoupper($al); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($m['bio'])): ?>
                                <p style="font-size:0.88rem; color:var(--text-secondary); line-height:1.5; margin:0;">
                                    <?php echo htmlspecialchars(mb_strimwidth($m['bio'], 0, 140, '...')); ?>
                                </p>
                            <?php endif; ?>

                            <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid var(--border-color); padding-top:0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="editTeam(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> <?php echo __a('admin.team.btn_edit', 'Bearbeiten'); ?>
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.team.confirm_delete', 'Möchtest du dieses Teammitglied wirklich löschen?'), ENT_QUOTES, 'UTF-8'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_team_member">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($m['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="<?php echo htmlspecialchars(__a('admin.team.btn_delete_title', 'Profil löschen')); ?>">
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
        const activeTeamLangs = <?php echo json_encode($active_langs); ?>;
        const primaryTeamLang = <?php echo json_encode($primary_lang); ?>;

        function switchTeamLangTab(lang) {
            document.querySelectorAll('.team-lang-content').forEach(el => el.style.display = 'none');
            const target = document.getElementById('team-lang-content-' + lang);
            if (target) target.style.display = 'block';

            document.querySelectorAll('.team-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            const activeBtn = document.getElementById('team-btn-tab-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-secondary');
                activeBtn.classList.add('btn-primary');
            }
        }

        function openTeamEditor() {
            document.getElementById('team-editor-box').style.display = 'block';
            document.getElementById('team-editor-title').innerHTML = '<span class="material-icons">person_add</span> ' + <?php echo json_encode(__a('admin.team.drawer_new', 'Neues Teammitglied anlegen')); ?>;
            document.getElementById('team_form_action').value = 'add_team_member';
            document.getElementById('team_id').value = '';
            document.getElementById('team_name').value = '';
            document.getElementById('team_role').value = '';
            document.getElementById('team_email').value = '';
            document.getElementById('team_bio').value = '';
            document.getElementById('team_current_img_box').style.display = 'none';

            activeTeamLangs.forEach(al => {
                if (al !== primaryTeamLang) {
                    const rEl = document.getElementById('team_trans_' + al + '_role');
                    const bEl = document.getElementById('team_trans_' + al + '_bio');
                    if (rEl) rEl.value = '';
                    if (bEl) bEl.value = '';
                }
            });

            switchTeamLangTab(primaryTeamLang);
            document.getElementById('team-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.team.btn_save_new', 'Profil anlegen')); ?>;
            document.getElementById('team_name').focus();
        }

        function closeTeamEditor() {
            document.getElementById('team-editor-box').style.display = 'none';
        }

        function editTeam(memberData) {
            document.getElementById('team-editor-box').style.display = 'block';
            document.getElementById('team-editor-title').innerHTML = '<span class="material-icons">edit</span> ' + <?php echo json_encode(__a('admin.team.drawer_edit', 'Teammitglied bearbeiten')); ?>;
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

            const tr = memberData.translations || {};
            activeTeamLangs.forEach(al => {
                if (al !== primaryTeamLang) {
                    const rEl = document.getElementById('team_trans_' + al + '_role');
                    const bEl = document.getElementById('team_trans_' + al + '_bio');
                    if (rEl) rEl.value = tr[al]?.role || '';
                    if (bEl) bEl.value = tr[al]?.bio || '';
                }
            });

            switchTeamLangTab(primaryTeamLang);
            document.getElementById('team-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.team.btn_save_changes', 'Änderungen speichern')); ?>;
            document.getElementById('team-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>

