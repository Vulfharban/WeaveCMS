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
                        <span class="material-icons" style="font-size:30px;">group</span> Benutzerverwaltung &amp; Rollen (RBAC)
                    </h2>
                    <p style="color: var(--text-secondary); margin:0; font-size: 0.95rem;">
                        Verwalte Benutzerkonten und weise abgestufte Berechtigungen (Administrator, Moderator, Redakteur) zu.
                    </p>
                </div>
                <div>
                    <span style="background:rgba(99,102,241,0.12); color:#6366f1; border:1px solid rgba(99,102,241,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">verified_user</span> <?php echo count($all_users); ?> Benutzerkonto(-en)
                    </span>
                </div>
            </div>

            <!-- ROLLEN-ERLÄUTERUNG (INFO-BOX) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem; margin-bottom: 2.25rem;">
                <h4 style="margin:0 0 0.75rem 0; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="color:var(--primary); font-size:18px;">info</span> Rollenmodell &amp; Zugriffsstufen
                </h4>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                    <div>
                        <strong style="color:var(--text-primary);">👑 Administrator:</strong>
                        Vollzugriff auf alle Bereiche (Dashboard, Inhalte, Postfach, Benutzer, Design, SEO, Sicherheit &amp; Backups).
                    </div>
                    <div>
                        <strong style="color:var(--text-primary);">🛡️ Moderator:</strong>
                        Redaktionelle Leitung (Dashboard, Seiten, Galerie, Blog, Termine und Kontaktpostfach). Kein Systemzugriff.
                    </div>
                    <div>
                        <strong style="color:var(--text-primary);">✏️ Redakteur:</strong>
                        Reine Inhaltspflege (Dashboard, Texte, Blogbeiträge, Galerie). Kein Zugriff auf Einstellungen oder Postfach.
                    </div>
                </div>
            </div>

            <!-- 1. BENUTZER-TABELLE -->
            <div style="margin-bottom: 3rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--text-primary); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">list</span> Registrierte Benutzer
                </h3>

                <div style="overflow-x: auto; background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 12px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.03); color: var(--text-primary);">
                                <th style="padding: 12px 14px;">Benutzername</th>
                                <th style="padding: 12px 14px;">E-Mail</th>
                                <th style="padding: 12px 14px;">Rolle</th>
                                <th style="padding: 12px 14px;">Status</th>
                                <th style="padding: 12px 14px;">Letzter Login</th>
                                <th style="padding: 12px 14px; text-align: right;">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): 
                                $isSelf = ($cur_user['id'] === $u['id']);
                            ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 14px; font-weight: 700; color: var(--text-primary);">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span class="material-icons" style="color:var(--primary); font-size:20px;">account_circle</span>
                                            <span><?php echo htmlspecialchars($u['username']); ?></span>
                                            <?php if ($isSelf): ?>
                                                <span style="font-size:0.7rem; background:rgba(46,98,72,0.15); color:var(--primary); padding:1px 6px; border-radius:6px;">Du</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 14px; color: var(--text-secondary);">
                                        <?php echo !empty($u['email']) ? htmlspecialchars($u['email']) : '<em style="opacity:0.6;">Keine Angabe</em>'; ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <?php echo UserManager::getRoleBadge($u['role']); ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <?php if (!empty($u['active'])): ?>
                                            <span style="color:#10b981; font-weight:600; font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-icons" style="font-size:14px;">check_circle</span> Aktiv
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#ef4444; font-weight:600; font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-icons" style="font-size:14px;">cancel</span> Gesperrt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 14px; color: var(--text-secondary); font-size: 0.82rem;">
                                        <?php echo !empty($u['last_login']) ? htmlspecialchars($u['last_login']) : '<em style="opacity:0.6;">Noch nie</em>'; ?>
                                    </td>
                                    <td style="padding: 12px 14px; text-align: right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <!-- Bearbeiten Trigger -->
                                            <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.78rem;" onclick="toggleEditUser('<?php echo htmlspecialchars($u['id']); ?>')" title="Bearbeiten">
                                                <span class="material-icons" style="font-size:15px;">edit</span>
                                            </button>

                                            <!-- Löschen (falls nicht das eigene Konto) -->
                                            <?php if (!$isSelf): ?>
                                                <form method="POST" action="" style="display:inline; margin:0;" onsubmit="return confirm('Möchtest du den Benutzer \'<?php echo htmlspecialchars($u['username']); ?>\' wirklich unwiderruflich löschen?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">
                                                    <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.78rem; color:#ef4444; border-color:#fca5a5;" title="Benutzer löschen">
                                                        <span class="material-icons" style="font-size:15px;">delete</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Inline-Bearbeitungszeile (Standardmäßig verborgen) -->
                                <tr id="edit-user-row-<?php echo htmlspecialchars($u['id']); ?>" style="display:none; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                                    <td colspan="6" style="padding: 1.25rem 1.5rem;">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="update_user">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">

                                            <div style="font-weight:700; margin-bottom:1rem; color:var(--primary); font-size:0.95rem;">
                                                Benutzer bearbeiten: <?php echo htmlspecialchars($u['username']); ?>
                                            </div>

                                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;">E-Mail-Adresse</label>
                                                    <input type="email" name="edit_email" class="form-control" value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>" style="font-size:0.88rem;">
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;">Rolle</label>
                                                    <select name="edit_role" class="form-control" style="font-size:0.88rem;">
                                                        <option value="admin" <?php echo ($u['role'] === 'admin') ? 'selected' : ''; ?>>Administrator (Superadmin)</option>
                                                        <option value="moderator" <?php echo ($u['role'] === 'moderator') ? 'selected' : ''; ?>>Moderator (Redaktionsleitung)</option>
                                                        <option value="editor" <?php echo ($u['role'] === 'editor') ? 'selected' : ''; ?>>Redakteur (Inhaltspflege)</option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;">Status</label>
                                                    <select name="edit_active" class="form-control" style="font-size:0.88rem;">
                                                        <option value="1" <?php echo (!empty($u['active'])) ? 'selected' : ''; ?>>Aktiv (Zugang erlaubt)</option>
                                                        <option value="0" <?php echo (empty($u['active'])) ? 'selected' : ''; ?>>Gesperrt (Login blockiert)</option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label style="font-size:0.82rem;">Neues Passwort (optional)</label>
                                                    <input type="password" name="edit_password" class="form-control" placeholder="Nur bei Änderung ausfüllen" style="font-size:0.88rem;">
                                                </div>
                                            </div>

                                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                                <button type="button" class="btn btn-outline" style="padding:6px 14px; font-size:0.84rem;" onclick="toggleEditUser('<?php echo htmlspecialchars($u['id']); ?>')">Abbrechen</button>
                                                <button type="submit" class="btn btn-primary" style="padding:6px 14px; font-size:0.84rem;">Änderungen speichern</button>
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
                    <span class="material-icons" style="color:var(--primary);">person_add</span> Neuen Benutzer anlegen
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.5rem;">
                    Erstelle ein neues Benutzerkonto mit individuellem Passwort und weise die passende Rolle zu.
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="create_user">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label for="new_username">Benutzername <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="new_username" name="new_username" class="form-control" placeholder="z. B. redakteur1" required autocomplete="off">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Mindestens 3 Zeichen, eindeutig.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_email">E-Mail-Adresse</label>
                            <input type="email" id="new_email" name="new_email" class="form-control" placeholder="name@domain.de">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Optional für Benachrichtigungen.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Passwort <span style="color:#ef4444;">*</span></label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Mindestens 6 Zeichen" required autocomplete="new-password">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Sicheres Passwort wählen.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_role">Rolle / Berechtigung <span style="color:#ef4444;">*</span></label>
                            <select id="new_role" name="new_role" class="form-control" required>
                                <option value="editor" selected>Redakteur (Inhaltspflege)</option>
                                <option value="moderator">Moderator (Redaktionsleitung &amp; Postfach)</option>
                                <option value="admin">Administrator (Vollzugriff)</option>
                            </select>
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Bestimmt den Zugriff im Backend.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px;">
                        <span class="material-icons" style="font-size:18px;">person_add</span> Benutzerkonto anlegen
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
