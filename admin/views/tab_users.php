<?php
/**
 * WeaveCMS – Admin Tab: Benutzerverwaltung & Rollen (Phase 10)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var array $all_users Alle registrierten Benutzer
 * @var array $cur_user Aktuell angemeldeter Benutzer
 * @var string $csrf_token CSRF-Sicherheitstoken
 */
?>
        <!-- ──────────────── SECTION 7: BENUTZER & ROLLEN ──────────────── -->
        <div id="tab-users" class="admin-section card" style="padding: 2.5rem;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin:0 0 0.35rem 0; font-size: 1.6rem; display:flex; align-items:center; gap:10px;">
                        <span class="material-icons" style="font-size:30px;">group</span> <?php echo __a('admin.users.title', 'Benutzerverwaltung &amp; Rollen (RBAC)'); ?>
                    </h2>
                    <p style="color: var(--text-secondary); margin:0; font-size: 0.95rem;">
                        <?php echo __a('admin.users.subtitle', 'Verwalte Benutzerkonten und weise abgestufte Berechtigungen (Administrator, Moderator, Redakteur) zu.'); ?>
                    </p>
                </div>
                <div>
                    <span style="background:rgba(99,102,241,0.12); color:#6366f1; border:1px solid rgba(99,102,241,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">verified_user</span> <?php echo __a('admin.users.count_badge', '{count} Benutzerkonto(-en)', null, ['count' => count($all_users)]); ?>
                    </span>
                </div>
            </div>

            <!-- ROLLEN-ERLÄUTERUNG (INFO-BOX) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem; margin-bottom: 2.25rem;">
                <h4 style="margin:0 0 0.75rem 0; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="color:var(--primary); font-size:18px;">info</span> <?php echo __a('admin.users.role_model_title', 'Rollenmodell &amp; Zugriffsstufen'); ?>
                </h4>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                    <div>
                        <strong style="color:var(--text-primary);">👑 <?php echo __a('admin.users.role_admin', 'Administrator: Vollzugriff auf alle Bereiche (Dashboard, Inhalte, Postfach, Benutzer, Design, SEO, Sicherheit &amp; Backups).'); ?></strong>
                    </div>
                    <div>
                        <strong style="color:var(--text-primary);">🛡️ <?php echo __a('admin.users.role_moderator', 'Moderator: Redaktionelle Leitung (Dashboard, Seiten, Galerie, Blog, Termine und Kontaktpostfach). Kein Systemzugriff.'); ?></strong>
                    </div>
                    <div>
                        <strong style="color:var(--text-primary);">✏️ <?php echo __a('admin.users.role_editor', 'Redakteur: Reine Inhaltspflege (Dashboard, Texte, Blogbeiträge, Galerie). Kein Zugriff auf Einstellungen oder Postfach.'); ?></strong>
                    </div>
                </div>
            </div>

            <!-- 1. BENUTZER-TABELLE -->
            <div style="margin-bottom: 3rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--text-primary); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">list</span> <?php echo __a('admin.users.table_title', 'Registrierte Benutzer'); ?>
                </h3>

                <div style="overflow-x: auto; background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 12px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.03); color: var(--text-primary);">
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_username', 'Benutzername'); ?></th>
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_email', 'E-Mail'); ?></th>
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_role', 'Rolle'); ?></th>
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_status', 'Status'); ?></th>
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_language', 'Sprache'); ?></th>
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_last_login', 'Letzter Login'); ?></th>
                                <th style="padding: 12px 14px; text-align: right;"><?php echo __a('admin.users.table_actions', 'Aktionen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): 
                                $isSelf = ($cur_user['id'] === $u['id']);
                                $uLang = $u['admin_lang'] ?? 'de';
                            ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 14px; font-weight: 700; color: var(--text-primary);">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span class="material-icons" style="color:var(--primary); font-size:20px;">account_circle</span>
                                            <span><?php echo htmlspecialchars($u['username']); ?></span>
                                            <?php if ($isSelf): ?>
                                                <span style="font-size:0.7rem; background:rgba(46,98,72,0.15); color:var(--primary); padding:1px 6px; border-radius:6px;"><?php echo __a('admin.users.you_badge', 'Du'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 14px; color: var(--text-secondary);">
                                        <?php echo !empty($u['email']) ? htmlspecialchars($u['email']) : '<em style="opacity:0.6;">-</em>'; ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <?php echo UserManager::getRoleBadge($u['role']); ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <?php if (!empty($u['active'])): ?>
                                            <span style="color:#10b981; font-weight:600; font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-icons" style="font-size:14px;">check_circle</span> <?php echo __a('admin.status.active', 'Aktiv'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#ef4444; font-weight:600; font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-icons" style="font-size:14px;">cancel</span> <?php echo __a('admin.status.locked', 'Gesperrt'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <span style="font-size:0.78rem; font-weight:700; display:inline-flex; align-items:center; gap:6px; background:rgba(0,0,0,0.03); padding:3px 8px; border-radius:12px; border:1px solid var(--border-color);" title="<?php echo ($uLang === 'en') ? 'English' : 'Deutsch'; ?>">
                                            <?php echo LanguageManager::getFlagSvg($uLang, 16, 12); ?>
                                            <span><?php echo strtoupper($uLang); ?></span>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 14px; color: var(--text-secondary); font-size: 0.82rem;">
                                        <?php echo !empty($u['last_login']) ? htmlspecialchars($u['last_login']) : '<em style="opacity:0.6;">' . __a('admin.users.never_logged_in', 'Noch nie') . '</em>'; ?>
                                    </td>
                                    <td style="padding: 12px 14px; text-align: right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <!-- Bearbeiten Trigger -->
                                            <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.78rem;" onclick="toggleEditUser('<?php echo htmlspecialchars($u['id']); ?>')" title="<?php echo __a('admin.btn.edit', 'Bearbeiten'); ?>">
                                                <span class="material-icons" style="font-size:15px;">edit</span>
                                            </button>

                                            <!-- Löschen (falls nicht das eigene Konto) -->
                                            <?php if (!$isSelf): ?>
                                                <form method="POST" action="" style="display:inline; margin:0;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.msg.confirm_delete', 'Möchtest du diesen Eintrag wirklich unwiderruflich löschen?'), ENT_QUOTES); ?>');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">
                                                    <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.78rem; color:#ef4444; border-color:#fca5a5;" title="<?php echo __a('admin.btn.delete', 'Löschen'); ?>">
                                                        <span class="material-icons" style="font-size:15px;">delete</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Inline-Bearbeitungszeile (Standardmäßig verborgen) -->
                                <tr id="edit-user-row-<?php echo htmlspecialchars($u['id']); ?>" style="display:none; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                                    <td colspan="7" style="padding: 1.25rem 1.5rem;">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="update_user">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">

                                            <div style="font-weight:700; margin-bottom:1rem; color:var(--primary); font-size:0.95rem;">
                                                <?php echo __a('admin.users.edit_title', 'Benutzer bearbeiten: {name}', null, ['name' => $u['username']]); ?>
                                            </div>

                                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;"><?php echo __a('admin.users.table_email', 'E-Mail-Adresse'); ?></label>
                                                    <input type="email" name="edit_email" class="form-control" value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>" style="font-size:0.88rem;">
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;"><?php echo __a('admin.users.table_role', 'Rolle'); ?></label>
                                                    <select name="edit_role" class="form-control" style="font-size:0.88rem;">
                                                        <option value="admin" <?php echo ($u['role'] === 'admin') ? 'selected' : ''; ?>>Administrator (Superadmin)</option>
                                                        <option value="moderator" <?php echo ($u['role'] === 'moderator') ? 'selected' : ''; ?>>Moderator (Redaktionsleitung)</option>
                                                        <option value="editor" <?php echo ($u['role'] === 'editor') ? 'selected' : ''; ?>>Redakteur (Inhaltspflege)</option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;"><?php echo __a('admin.users.field_language', 'Standard Backend-Sprache'); ?></label>
                                                    <select name="edit_admin_lang" class="form-control" style="font-size:0.88rem;">
                                                        <option value="de" <?php echo ($uLang === 'de') ? 'selected' : ''; ?>><?php echo __a('admin.users.lang_de', 'Deutsch (DE)'); ?></option>
                                                        <option value="en" <?php echo ($uLang === 'en') ? 'selected' : ''; ?>><?php echo __a('admin.users.lang_en', 'English (EN)'); ?></option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;"><?php echo __a('admin.users.table_status', 'Status'); ?></label>
                                                    <select name="edit_active" class="form-control" style="font-size:0.88rem;">
                                                        <option value="1" <?php echo (!empty($u['active'])) ? 'selected' : ''; ?>><?php echo __a('admin.status.active', 'Aktiv'); ?></option>
                                                        <option value="0" <?php echo (empty($u['active'])) ? 'selected' : ''; ?>><?php echo __a('admin.status.locked', 'Gesperrt'); ?></option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;">Neues Passwort (optional)</label>
                                                    <input type="password" name="edit_password" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.users.edit_password_placeholder', 'Nur bei Änderung ausfüllen')); ?>" style="font-size:0.88rem;">
                                                </div>
                                            </div>

                                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                                <button type="button" class="btn btn-outline" style="padding:6px 14px; font-size:0.84rem;" onclick="toggleEditUser('<?php echo htmlspecialchars($u['id']); ?>')"><?php echo __a('admin.btn.cancel', 'Abbrechen'); ?></button>
                                                <button type="submit" class="btn btn-primary" style="padding:6px 14px; font-size:0.84rem;"><?php echo __a('admin.btn.save', 'Änderungen speichern'); ?></button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. NEUEN BENUTZER ANLEGEN (FORMULAR) -->
            <div style="background: rgba(46,98,72,0.04); border: 1px solid rgba(46,98,72,0.2); border-radius: 16px; padding: 1.75rem;">
                <h3 style="font-size: 1.2rem; margin:0 0 0.5rem 0; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">person_add</span> <?php echo __a('admin.users.create_title', 'Neuen Benutzer anlegen'); ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.5rem;">
                    <?php echo __a('admin.users.create_subtitle', 'Erstelle ein neues Benutzerkonto mit individuellem Passwort und weise die passende Rolle zu.'); ?>
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="create_user">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label for="new_username"><?php echo __a('admin.users.table_username', 'Benutzername'); ?> <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="new_username" name="new_username" class="form-control" placeholder="z. B. redakteur1" required autocomplete="off">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Mindestens 3 Zeichen, eindeutig.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_email"><?php echo __a('admin.users.table_email', 'E-Mail-Adresse'); ?></label>
                            <input type="email" id="new_email" name="new_email" class="form-control" placeholder="name@domain.de">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Optional für Benachrichtigungen.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Passwort <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Mindestens 6 Zeichen" required autocomplete="new-password">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Sicheres Passwort wählen.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_role"><?php echo __a('admin.users.table_role', 'Rolle'); ?> <span style="color:#ef4444;">*</span></label>
                            <select id="new_role" name="new_role" class="form-control" required>
                                <option value="editor" selected>Redakteur (Inhaltspflege)</option>
                                <option value="moderator">Moderator (Redaktionsleitung &amp; Postfach)</option>
                                <option value="admin">Administrator (Vollzugriff)</option>
                            </select>
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Bestimmt den Zugriff im Backend.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_admin_lang"><?php echo __a('admin.users.field_language', 'Standard Backend-Sprache'); ?></label>
                            <select id="new_admin_lang" name="new_admin_lang" class="form-control">
                                <option value="de" selected><?php echo __a('admin.users.lang_de', 'Deutsch (DE)'); ?></option>
                                <option value="en"><?php echo __a('admin.users.lang_en', 'English (EN)'); ?></option>
                            </select>
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Sprache nach der Anmeldung.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px;">
                        <span class="material-icons" style="font-size:18px;">person_add</span> <?php echo __a('admin.users.create_title', 'Benutzerkonto anlegen'); ?>
                    </button>
                </form>
            </div>

        </div>

        <script>
        function toggleEditUser(userId) {
            var row = document.getElementById('edit-user-row-' + userId);
            if (row) {
                row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
            }
        }
        </script>
