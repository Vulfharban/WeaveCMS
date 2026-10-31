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
                                <th style="padding: 12px 14px;"><?php echo __a('admin.users.table_2fa', '2FA'); ?></th>
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
                                        <?php if (!empty($u['two_factor_enabled'])): ?>
                                            <span style="color:#10b981; font-weight:700; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:10px;">
                                                <span class="material-icons" style="font-size:13px;">shield</span> <?php echo __a('admin.users.2fa_active', 'Aktiv'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#64748b; font-size:0.78rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-icons" style="font-size:13px;">shield_outlined</span> <?php echo __a('admin.users.2fa_inactive', 'Aus'); ?>
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

        <!-- ======================================================================
             MODAL: 2FA EINRICHTUNGS-ASSISTENT (PHASE 29.2)
             ====================================================================== -->
        <div id="modal-2fa-setup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:3300; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);" onclick="if(event.target===this) close2faModal('modal-2fa-setup');">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; max-width:540px; width:calc(100% - 2rem); max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.45); overflow:hidden;">
                <!-- Header -->
                <div style="padding:1.2rem 1.6rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
                    <h3 style="margin:0; color:var(--primary); font-size:1.15rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:22px;">security</span>
                        <span><?php echo __a('admin.2fa.modal_setup_title', '2FA-Einrichtungs-Assistent'); ?></span>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="close2faModal('modal-2fa-setup')" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div style="padding:1.6rem; overflow-y:auto; flex:1;">
                    <!-- Phase A: Setup & QR Code -->
                    <div id="setup-2fa-step-container">
                        <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem; margin-bottom:4px;">
                            <?php echo __a('admin.2fa.step1', '1. QR-Code mit Authenticator-App scannen'); ?>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.45;">
                            <?php echo __a('admin.2fa.step1_desc', 'Scanne diesen QR-Code mit deiner Authenticator-App (z. B. Google Authenticator, Bitwarden) oder gib den TextschlÃ¼ssel manuell ein:'); ?>
                        </p>

                        <!-- QR Code Container -->
                        <div style="text-align:center; margin-bottom:1.25rem;">
                            <div id="setup-2fa-qr-box" style="display:inline-block; padding:14px; background:#ffffff; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.1); border:1px solid #e2e8f0; min-height:180px; min-width:180px;">
                                <div id="setup-2fa-loading" style="padding:2rem; color:#64748b; font-size:0.85rem;">QR-Code wird erzeugt...</div>
                            </div>
                        </div>

                        <!-- Manueller SchlÃ¼ssel -->
                        <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:10px; padding:10px 14px; margin-bottom:1.5rem;">
                            <label style="font-size:0.75rem; color:var(--text-secondary); display:block; margin-bottom:3px;"><?php echo __a('admin.2fa.manual_key', 'Geheimer TextschlÃ¼ssel:'); ?></label>
                            <div style="display:flex; gap:6px;">
                                <input type="text" id="setup-2fa-secret-text" class="form-control" readonly style="font-family:monospace; font-size:0.88rem; font-weight:700; letter-spacing:1.5px; background:rgba(0,0,0,0.04);">
                                <button type="button" class="btn btn-secondary" onclick="copy2faSecret()" style="padding:4px 10px; font-size:0.78rem;">
                                    <span class="material-icons" style="font-size:14px; vertical-align:middle;">content_copy</span> Kopieren
                                </button>
                            </div>
                        </div>

                        <!-- Phase B: BestÃ¤tigungscode -->
                        <div style="border-top:1px solid var(--border-color); padding-top:1.25rem;">
                            <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem; margin-bottom:4px;">
                                <?php echo __a('admin.2fa.step2', '2. Einmalcode zur BestÃ¤tigung eingeben'); ?>
                            </div>
                            <p style="font-size:0.84rem; color:var(--text-secondary); margin-bottom:1rem; line-height:1.45;">
                                <?php echo __a('admin.2fa.step2_desc', 'Gib den aktuellen 6-stelligen Code aus deiner App ein, um die Aktivierung abzuschlieÃŸen:'); ?>
                            </p>

                            <div style="display:flex; gap:10px; align-items:center; margin-bottom:0.75rem;">
                                <input type="text" id="setup-2fa-code-input" class="form-control" maxlength="8" placeholder="<?php echo htmlspecialchars(__a('admin.2fa.code_ph', '123456')); ?>" style="font-size:1.25rem; font-family:monospace; letter-spacing:4px; text-align:center; max-width:180px;" onkeyup="if(event.key==='Enter') confirm2faSetup();">
                                <button type="button" id="setup-2fa-submit-btn" class="btn btn-primary" onclick="confirm2faSetup()" style="padding:10px 18px; font-size:0.88rem; flex:1;">
                                    <?php echo __a('admin.2fa.btn_verify_enable', 'Code bestÃ¤tigen & 2FA aktivieren'); ?>
                                </button>
                            </div>
                            <div id="setup-2fa-error" style="display:none; color:#dc2626; font-size:0.82rem; font-weight:600; margin-top:6px;"></div>
                        </div>
                    </div>

                    <!-- Phase C: Notfall-Codes Anzeige nach erfolgreicher Aktivierung (Phase 29.3) -->
                    <div id="setup-2fa-backup-container" style="display:none;">
                        <div style="text-align:center; margin-bottom:1.25rem;">
                            <div style="width:52px; height:52px; border-radius:50%; background:rgba(16,185,129,0.15); color:#10b981; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.5rem;">
                                <span class="material-icons" style="font-size:32px;">check_circle</span>
                            </div>
                            <h4 style="margin:0 0 0.25rem 0; color:var(--text-primary); font-size:1.15rem;">
                                <?php echo __a('admin.2fa.backup_title', 'ðŸ›¡ï¸ Deine Notfall-Wiederherstellungscodes'); ?>
                            </h4>
                            <p style="margin:0; font-size:0.84rem; color:var(--text-secondary); line-height:1.45;">
                                <?php echo __a('admin.2fa.backup_desc', 'Falls du keinen Zugriff mehr auf deine Authenticator-App hast, kannst du dich mit einem dieser 8 Notfall-Codes anmelden. Jeder Code kann genau einmal verwendet werden.'); ?>
                            </p>
                        </div>

                        <!-- 8 Codes Grid -->
                        <div id="setup-2fa-codes-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:8px; background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:12px; padding:14px; margin-bottom:1.25rem; font-family:monospace; font-size:1.05rem; font-weight:700; text-align:center;">
                            <!-- Dynamisch eingefÃ¼gt -->
                        </div>

                        <div style="display:flex; gap:10px; margin-bottom:1rem; flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline" onclick="copyBackupCodes()" style="flex:1; padding:9px 14px; font-size:0.84rem; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                <span class="material-icons" style="font-size:16px;">content_copy</span> <?php echo __a('admin.2fa.btn_copy_backup', 'Alle Codes kopieren'); ?>
                            </button>
                            <button type="button" class="btn btn-outline" onclick="downloadBackupCodes()" style="flex:1; padding:9px 14px; font-size:0.84rem; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                <span class="material-icons" style="font-size:16px;">file_download</span> <?php echo __a('admin.2fa.btn_download_backup', 'Codes herunterladen (.txt)'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding:0.9rem 1.6rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; background:rgba(255,255,255,0.02);">
                    <button type="button" id="setup-2fa-done-btn" class="btn btn-primary" onclick="finish2faSetup()" style="display:none;">
                        <?php echo __a('admin.2fa.btn_done', 'Fertiggestellt'); ?>
                    </button>
                    <button type="button" id="setup-2fa-cancel-btn" class="btn btn-secondary" onclick="close2faModal('modal-2fa-setup')">
                        <?php echo __a('admin.btn.cancel', 'Abbrechen'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================================================================
             MODAL: 2FA DEAKTIVIEREN
             ====================================================================== -->
        <div id="modal-2fa-disable" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:3300; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);" onclick="if(event.target===this) close2faModal('modal-2fa-disable');">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; max-width:440px; width:calc(100% - 2rem); display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.45); overflow:hidden;">
                <div style="padding:1.2rem 1.6rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; color:#ef4444; font-size:1.15rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">lock_open</span>
                        <span><?php echo __a('admin.2fa.disable_modal_title', 'Zwei-Faktor-Authentifizierung deaktivieren'); ?></span>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="close2faModal('modal-2fa-disable')" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>
                <div style="padding:1.6rem;">
                    <p style="font-size:0.86rem; color:var(--text-secondary); margin-bottom:1.25rem;">
                        <?php echo __a('admin.2fa.disable_desc', 'Bitte gib dein aktuelles Admin-Passwort oder die PIN ein, um 2FA fÃ¼r dein Konto zu deaktivieren:'); ?>
                    </p>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="font-size:0.82rem; font-weight:700;">Passwort oder PIN:</label>
                        <input type="password" id="disable-2fa-auth" class="form-control" placeholder="Passwort oder PIN" onkeyup="if(event.key==='Enter') confirm2faDisable();">
                    </div>
                    <div id="disable-2fa-error" style="display:none; color:#dc2626; font-size:0.82rem; font-weight:600; margin-bottom:0.75rem;"></div>
                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" class="btn btn-secondary" onclick="close2faModal('modal-2fa-disable')"><?php echo __a('admin.btn.cancel', 'Abbrechen'); ?></button>
                        <button type="button" class="btn btn-danger" onclick="confirm2faDisable()" style="background:#ef4444; color:#fff; border:none; padding:8px 16px;">
                            <?php echo __a('admin.2fa.btn_disable', '2FA deaktivieren'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================================
             MODAL: 2FA BACKUP-CODES VERWALTEN (PHASE 29.3)
             ====================================================================== -->
        <div id="modal-2fa-backup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:3300; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);" onclick="if(event.target===this) close2faModal('modal-2fa-backup');">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; max-width:480px; width:calc(100% - 2rem); display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.45); overflow:hidden;">
                <div style="padding:1.2rem 1.6rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; color:var(--primary); font-size:1.15rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">key</span>
                        <span><?php echo __a('admin.2fa.backup_title', 'Notfall-Wiederherstellungscodes'); ?></span>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="close2faModal('modal-2fa-backup')" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>
                <div style="padding:1.6rem;">
                    <?php 
                        $curCodes = $cur_user['two_factor_backup_codes'] ?? [];
                    ?>
                    <p style="font-size:0.86rem; color:var(--text-secondary); margin-bottom:1.25rem;">
                        <?php echo __a('admin.2fa.backup_desc', 'Jeder Code kann genau einmal zur Notfall-Anmeldung verwendet werden:'); ?>
                    </p>
                    
                    <div id="view-backup-codes-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:8px; background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:12px; padding:14px; margin-bottom:1.25rem; font-family:monospace; font-size:1.05rem; font-weight:700; text-align:center;">
                        <?php if (!empty($curCodes)): ?>
                            <?php foreach ($curCodes as $bc): ?>
                                <div style="padding:6px; background:rgba(255,255,255,0.04); border-radius:6px;"><?php echo htmlspecialchars($bc); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="grid-column:1/-1; color:var(--text-secondary); font-size:0.85rem; padding:10px;">Keine ungenutzten Codes mehr vorhanden. Bitte generiere neue Codes.</div>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; gap:10px; margin-bottom:1.25rem; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline" onclick="copyExistingBackupCodes()" style="flex:1; padding:8px 12px; font-size:0.82rem; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <span class="material-icons" style="font-size:15px;">content_copy</span> Kopieren
                        </button>
                        <button type="button" class="btn btn-outline" onclick="downloadExistingBackupCodes()" style="flex:1; padding:8px 12px; font-size:0.82rem; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <span class="material-icons" style="font-size:15px;">file_download</span> Herunterladen (.txt)
                        </button>
                    </div>

                    <!-- Neue Codes generieren -->
                    <div style="border-top:1px solid var(--border-color); padding-top:1.25rem;">
                        <div style="font-size:0.86rem; font-weight:700; color:var(--text-primary); margin-bottom:6px;">Neue Notfall-Codes erzeugen:</div>
                        <p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:8px;">
                            Erzeugt 8 neue Codes. Alle bisherigen ungenutzten Codes werden dabei sofort ungÃ¼ltig.
                        </p>
                        <div style="display:flex; gap:8px;">
                            <input type="password" id="regen-backup-auth" class="form-control" placeholder="Passwort oder PIN" style="font-size:0.85rem;">
                            <button type="button" class="btn btn-secondary" onclick="regenerate2faBackupCodes()" style="font-size:0.82rem; padding:8px 14px; white-space:nowrap;">
                                Neu generieren
                            </button>
                        </div>
                        <div id="regen-backup-error" style="display:none; color:#dc2626; font-size:0.8rem; font-weight:600; margin-top:6px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let LATEST_GENERATED_BACKUP_CODES = [];
        let EXISTING_BACKUP_CODES = <?php echo json_encode($cur_user['two_factor_backup_codes'] ?? []); ?>;

        function toggleEditUser(userId) {
            var row = document.getElementById('edit-user-row-' + userId);
            if (row) {
                row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
            }
        }

        function close2faModal(id) {
            const m = document.getElementById(id);
            if (m) m.style.display = 'none';
        }

        function start2faSetup() {
            const modal = document.getElementById('modal-2fa-setup');
            document.getElementById('setup-2fa-step-container').style.display = 'block';
            document.getElementById('setup-2fa-backup-container').style.display = 'none';
            document.getElementById('setup-2fa-done-btn').style.display = 'none';
            document.getElementById('setup-2fa-cancel-btn').style.display = 'inline-block';
            document.getElementById('setup-2fa-code-input').value = '';
            document.getElementById('setup-2fa-error').style.display = 'none';

            modal.style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'setup_2fa_init');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('setup-2fa-qr-box').innerHTML = res.qr_svg;
                    document.getElementById('setup-2fa-secret-text').value = res.secret;
                } else {
                    alert('Fehler: ' + (res.error || 'Initialisierung fehlgeschlagen'));
                    close2faModal('modal-2fa-setup');
                }
            })
            .catch(err => {
                alert('Netzwerkfehler: ' + err);
                close2faModal('modal-2fa-setup');
            });
        }

        function copy2faSecret() {
            const txt = document.getElementById('setup-2fa-secret-text');
            if (!txt) return;
            navigator.clipboard.writeText(txt.value).then(() => {
                showToast('SchlÃ¼ssel kopiert!');
            });
        }

        function confirm2faSetup() {
            const code = document.getElementById('setup-2fa-code-input').value.trim();
            const errEl = document.getElementById('setup-2fa-error');
            errEl.style.display = 'none';

            if (!code) {
                errEl.textContent = 'Bitte gib den 6-stelligen BestÃ¤tigungscode ein.';
                errEl.style.display = 'block';
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'setup_2fa_confirm');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            formData.append('code', code);

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    LATEST_GENERATED_BACKUP_CODES = res.backup_codes || [];
                    EXISTING_BACKUP_CODES = res.backup_codes || [];

                    // Notfall-Codes Grid befÃ¼llen
                    const grid = document.getElementById('setup-2fa-codes-grid');
                    grid.innerHTML = '';
                    LATEST_GENERATED_BACKUP_CODES.forEach(c => {
                        const d = document.createElement('div');
                        d.style.cssText = 'padding:6px; background:rgba(255,255,255,0.04); border-radius:6px;';
                        d.textContent = c;
                        grid.appendChild(d);
                    });

                    document.getElementById('setup-2fa-step-container').style.display = 'none';
                    document.getElementById('setup-2fa-backup-container').style.display = 'block';
                    document.getElementById('setup-2fa-done-btn').style.display = 'inline-block';
                    document.getElementById('setup-2fa-cancel-btn').style.display = 'none';
                    showToast(res.message || '2FA erfolgreich aktiviert!');
                } else {
                    errEl.textContent = res.error || 'BestÃ¤tigungscode ungÃ¼ltig.';
                    errEl.style.display = 'block';
                }
            })
            .catch(err => {
                errEl.textContent = 'Fehler: ' + err;
                errEl.style.display = 'block';
            });
        }

        function finish2faSetup() {
            close2faModal('modal-2fa-setup');
            window.location.reload();
        }

        function copyBackupCodes() {
            if (!LATEST_GENERATED_BACKUP_CODES.length) return;
            navigator.clipboard.writeText(LATEST_GENERATED_BACKUP_CODES.join("\n")).then(() => {
                showToast('Notfall-Codes in die Zwischenablage kopiert!');
            });
        }

        function downloadBackupCodes() {
            if (!LATEST_GENERATED_BACKUP_CODES.length) return;
            const text = "WeaveCMS â€“ Notfall-Wiederherstellungscodes (2FA)\nErzeugt am: " + new Date().toLocaleString() + "\n\n" + LATEST_GENERATED_BACKUP_CODES.join("\n") + "\n\nHinweis: Jeder Code kann genau einmal verwendet werden.";
            const blob = new Blob([text], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'weavecms-2fa-backup-codes.txt';
            a.click();
        }

        function open2faDisableModal() {
            document.getElementById('disable-2fa-auth').value = '';
            document.getElementById('disable-2fa-error').style.display = 'none';
            document.getElementById('modal-2fa-disable').style.display = 'flex';
        }

        function confirm2faDisable() {
            const authVal = document.getElementById('disable-2fa-auth').value.trim();
            const errEl = document.getElementById('disable-2fa-error');
            errEl.style.display = 'none';

            if (!authVal) {
                errEl.textContent = 'Bitte gib dein Passwort oder die PIN ein.';
                errEl.style.display = 'block';
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'disable_2fa');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            formData.append('pin', authVal);
            formData.append('password', authVal);

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message || '2FA deaktiviert');
                    close2faModal('modal-2fa-disable');
                    window.location.reload();
                } else {
                    errEl.textContent = res.error || 'Deaktivierung fehlgeschlagen.';
                    errEl.style.display = 'block';
                }
            })
            .catch(err => {
                errEl.textContent = 'Fehler: ' + err;
                errEl.style.display = 'block';
            });
        }

        function open2faBackupModal() {
            document.getElementById('regen-backup-auth').value = '';
            document.getElementById('regen-backup-error').style.display = 'none';
            document.getElementById('modal-2fa-backup').style.display = 'flex';
        }

        function copyExistingBackupCodes() {
            if (!EXISTING_BACKUP_CODES.length) {
                alert('Keine Backup-Codes vorhanden.');
                return;
            }
            navigator.clipboard.writeText(EXISTING_BACKUP_CODES.join("\n")).then(() => {
                showToast('Codes kopiert!');
            });
        }

        function downloadExistingBackupCodes() {
            if (!EXISTING_BACKUP_CODES.length) {
                alert('Keine Backup-Codes vorhanden.');
                return;
            }
            const text = "WeaveCMS â€“ Notfall-Wiederherstellungscodes (2FA)\n\n" + EXISTING_BACKUP_CODES.join("\n");
            const blob = new Blob([text], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'weavecms-2fa-backup-codes.txt';
            a.click();
        }

        function regenerate2faBackupCodes() {
            const authVal = document.getElementById('regen-backup-auth').value.trim();
            const errEl = document.getElementById('regen-backup-error');
            errEl.style.display = 'none';

            if (!authVal) {
                errEl.textContent = 'Bitte Passwort oder PIN eingeben.';
                errEl.style.display = 'block';
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'regenerate_2fa_backup_codes');
            formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            formData.append('pin', authVal);
            formData.append('password', authVal);

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    EXISTING_BACKUP_CODES = res.backup_codes || [];
                    const grid = document.getElementById('view-backup-codes-grid');
                    grid.innerHTML = '';
                    EXISTING_BACKUP_CODES.forEach(c => {
                        const d = document.createElement('div');
                        d.style.cssText = 'padding:6px; background:rgba(255,255,255,0.04); border-radius:6px;';
                        d.textContent = c;
                        grid.appendChild(d);
                    });
                    document.getElementById('regen-backup-auth').value = '';
                    showToast('Neue Backup-Codes erfolgreich generiert!');
                } else {
                    errEl.textContent = res.error || 'Fehler beim Generieren neuer Codes.';
                    errEl.style.display = 'block';
                }
            })
            .catch(err => {
                errEl.textContent = 'Fehler: ' + err;
                errEl.style.display = 'block';
            });
        }
        </script>
