<?php
/**
 * WeaveCMS – Admin Tab: Sicherheit & System (Captcha, SMTP, Uploads, Backups, Zugangsdaten, Speicher-Migration)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION 6: SICHERHEIT & SYSTEM ──────────────── -->
        <div id="tab-security" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">🔒 Sicherheit &amp; System</h2>
            
            <!-- FORMULAR 1: SPAMSCHUTZ, SMTP & SYSTEMEINSTELLUNGEN (KEINE PIN ERFORDERLICH) -->
            <form method="POST" action="" onsubmit="return validateSystemForm(this);">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_security">

                <!-- 1. SPAMSCHUTZ / CAPTCHA-SYMBOLE -->
                <h3 style="font-size: 1.2rem; margin-top: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="color:var(--primary);">verified_user</span> Spamschutz-Bilder für das Kontaktformular (Captcha)
                    </span>
                    <span id="captcha-counter-badge" style="font-size:0.85rem; padding:4px 12px; border-radius:20px; font-weight:600; background:rgba(46,98,72,0.12); color:var(--primary);">
                        Ausgewählt: <span id="captcha-count"><?php echo count(get_active_captcha_icons($data)); ?></span> von 4 Symbolen
                    </span>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Wähle hier genau <strong>4 der 12 zur Verfügung stehenden Symbole</strong> aus. Diese 4 Symbole werden deinen Besuchern im Kontaktformular angezeigt, wobei bei jeder Anfrage eines davon per Zufall als Sicherheitsaufgabe gelöst werden muss.
                </p>

                <?php 
                    $all_icons = get_all_captcha_icons();
                    $active_icons_keys = array_keys(get_active_captcha_icons($data));
                ?>

                <div class="captcha-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 2rem;">
                    <?php foreach ($all_icons as $icon_id => $icon_meta): 
                        $is_checked = in_array($icon_id, $active_icons_keys, true);
                    ?>
                        <label class="captcha-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 14px 10px; border: 2px solid <?php echo $is_checked ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius: 14px; background: <?php echo $is_checked ? 'rgba(46,98,72,0.08)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer; user-select:none; transition: all 0.2s ease; position:relative; text-align:center;">
                            <input type="checkbox" name="captcha_icons[]" value="<?php echo htmlspecialchars($icon_id); ?>" <?php echo $is_checked ? 'checked' : ''; ?> style="position:absolute; top:8px; right:8px; width:17px; height:17px; accent-color:var(--primary); cursor:pointer;" onchange="updateCaptchaSelection(this);">
                            <span style="font-size: 2.2rem; line-height: 1.2; margin-bottom: 6px;"><?php echo $icon_meta['symbol']; ?></span>
                            <span style="font-weight: 600; font-size: 0.86rem; color: var(--text-primary); margin-bottom: 2px;"><?php echo htmlspecialchars($icon_meta['name']); ?></span>
                            <span style="font-size: 0.72rem; color: var(--text-secondary);"><?php echo htmlspecialchars($icon_meta['prompt']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- 2. E-MAIL & SMTP VERSAND -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">mail</span> E-Mail- &amp; SMTP-Versandeinstellungen (Kontaktformular)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Konfiguriere hier den Server-Mailversand für dein Kontaktformular. Wenn SMTP aktiviert ist, werden E-Mails authentifiziert über dein Postfach versendet (höchste Zuverlässigkeit gegen Spamfilter). Andernfalls wird standardmäßig die PHP-Mailfunktion deines Webhosters genutzt.
                </p>

                <?php 
                    $cur_smtp = $data['settings']['smtp'] ?? [];
                    $smtp_is_enabled = !empty($cur_smtp['enabled']);
                ?>

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.95rem;">
                            <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtp_is_enabled ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            Authentifizierten SMTP-Versand aktivieren (Empfohlen für Live-Server &amp; Mailpostfächer)
                        </label>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label>SMTP-Server (Host)</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['host'] ?? ''); ?>" placeholder="z. B. smtp.strato.de oder mail.ihredomain.de">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['port'] ?? 587); ?>" placeholder="587">
                        </div>
                        <div class="form-group">
                            <label>Verschlüsselung</label>
                            <select name="smtp_secure" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <option value="tls" <?php echo (($cur_smtp['secure'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS / STARTTLS (Port 587, Standard)</option>
                                <option value="ssl" <?php echo (($cur_smtp['secure'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo (($cur_smtp['secure'] ?? '') === 'none') ? 'selected' : ''; ?>>Keine Verschlüsselung (Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label>SMTP-Benutzername / E-Mail</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['user'] ?? ''); ?>" placeholder="z. B. kontakt@maxima-musterfrau.de">
                        </div>
                        <div class="form-group">
                            <label>SMTP-Passwort</label>
                            <div style="position:relative;">
                                <input type="password" id="smtp_pass" name="smtp_pass" class="form-control" placeholder="<?php echo !empty($cur_smtp['pass']) ? '•••••••• (bereits hinterlegt)' : 'Postfach-Passwort'; ?>" style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('smtp_pass')">visibility</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                        <div class="form-group">
                            <label>Absender-E-Mail-Adresse (From)</label>
                            <input type="email" name="smtp_from_email" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_email'] ?? ''); ?>" placeholder="z. B. kontakt@maxima-musterfrau.de">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Leer lassen, um die Benutzername-E-Mail zu verwenden.</small>
                        </div>
                        <div class="form-group">
                            <label>Absender-Name (From Name)</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_name'] ?? 'Atelier Musterfrau'); ?>" placeholder="Atelier Musterfrau">
                        </div>
                    </div>
                </div>

                <!-- 3. BILDUPLOAD-VERZEICHNIS -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    📁 Bildupload-Verzeichnis &amp; Speicherort
                </h3>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Speicherordner für hochgeladene Bilder (relativ zum Web-Root)</label>
                    <input type="text" name="upload_dir" class="form-control" value="<?php echo htmlspecialchars($data['settings']['upload_dir'] ?? 'images/uploads'); ?>" placeholder="images/uploads" required>
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        Standard: <code>images/uploads</code> oder <code>uploads</code>. Verzeichnisse werden bei Bedarf automatisch erzeugt.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:18px;">save</span> Spamschutz-, SMTP- &amp; Upload-Einstellungen speichern
                </button>
            </form>

            <!-- SMTP Test Formular (separates Formular mit eigenem CSRF-Token) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem; margin-top: 2rem;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px; color:var(--primary);">send</span> SMTP-Verbindung jetzt testen
                </h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem;">
                    Sende eine Test-E-Mail, um deine hinterlegten SMTP-Zugangsdaten sofort auf Funktion zu prüfen (vorher bitte oben speichern).
                </p>
                <form method="POST" action="" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="test_smtp">
                    <div class="form-group" style="flex:1; min-width:240px; margin-bottom:0;">
                        <label style="font-size:0.85rem; margin-bottom:4px; display:block;">Test-E-Mail senden an:</label>
                        <input type="email" name="test_to_email" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de'); ?>" required style="height:42px;">
                    </div>
                    <button type="submit" class="btn btn-outline" style="height:42px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">mark_email_read</span> Test-E-Mail jetzt senden
                    </button>
                </form>
            </div>

            <!-- 4. INTEGRIERTES BACKUP- & WIEDERHERSTELLUNGSSYSTEM (PHASE 2) -->
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color);">
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px; color:var(--primary);">
                    <span class="material-icons" style="color:var(--primary);">save</span> Datensicherung &amp; Wiederherstellung (Backup / Restore)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Sichere alle Website-Inhalte und hochgeladenen Bilder mit einem Klick. Alle Sicherungen werden im geschützten Verzeichnis <code>backups/</code> auf dem Server archiviert (geschützt vor Direktaufrufen via <code>.htaccess</code>) und können jederzeit heruntergeladen oder wiederhergestellt werden.
                </p>

                <!-- 1-Klick Backup Aktionen -->
                <div style="background: rgba(16, 185, 129, 0.04); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="margin:0 0 0.75rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="color:var(--primary); font-size:20px;">add_circle</span> 1-Klick-Sicherung jetzt erstellen
                    </h4>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <form method="POST" action="" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="create_backup">
                            <input type="hidden" name="backup_type" value="data">
                            <button type="submit" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px;">
                                <span class="material-icons" style="font-size:18px;">description</span> Option A: Nur Daten &amp; Einstellungen (.json)
                            </button>
                        </form>

                        <form method="POST" action="" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="create_backup">
                            <input type="hidden" name="backup_type" value="full">
                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px;">
                                <span class="material-icons" style="font-size:18px;">folder_zip</span> Option B: Vollständiges Archiv inkl. Bilder (.zip)
                            </button>
                        </form>
                    </div>
                    <small style="color:var(--text-secondary); display:block; margin-top:8px;">
                        💡 <em>Empfehlung:</em> Erstelle regelmäßig ein vollständiges ZIP-Archiv vor größeren inhaltlichen Änderungen oder Updates.
                    </small>
                </div>

                <!-- Vorhandene Sicherungen (Tabelle) -->
                <?php $backups_list = BackupManager::listBackups(); ?>
                <div style="margin-bottom: 2.5rem;">
                    <h4 style="margin:0 0 1rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">history</span> Vorhandene Sicherungen auf dem Server
                        </span>
                        <span style="font-size:0.82rem; color:var(--text-secondary); background:rgba(0,0,0,0.04); padding:3px 10px; border-radius:12px;">
                            Gesamt: <?php echo count($backups_list); ?> Datei(en)
                        </span>
                    </h4>

                    <?php if (empty($backups_list)): ?>
                        <div style="background: rgba(0,0,0,0.02); border: 1px dashed var(--border-color); border-radius: 12px; padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <span class="material-icons" style="font-size:36px; opacity:0.4; display:block; margin-bottom:8px;">inventory_2</span>
                            Es sind noch keine Sicherungsdateien im Verzeichnis <code>backups/</code> vorhanden.<br>
                            Klicke oben auf <strong>1-Klick-Sicherung</strong>, um dein erstes Backup zu erstellen.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto; background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 12px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.03); color: var(--text-primary);">
                                        <th style="padding: 10px 14px;">Typ</th>
                                        <th style="padding: 10px 14px;">Dateiname</th>
                                        <th style="padding: 10px 14px;">Erstellt am</th>
                                        <th style="padding: 10px 14px;">Größe</th>
                                        <th style="padding: 10px 14px; text-align: right;">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups_list as $b): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                             <td style="padding: 10px 14px;">
                                                <?php if ($b['type'] === 'full'): ?>
                                                    <span style="background: rgba(16,185,129,0.12); color: var(--primary); padding: 3px 8px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;">
                                                        <span class="material-icons" style="font-size:14px;">folder_zip</span> ZIP-Vollarchiv
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: rgba(217,119,6,0.12); color: var(--accent); padding: 3px 8px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;">
                                                        <span class="material-icons" style="font-size:14px;">description</span> JSON-Daten
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 10px 14px; font-family: monospace; font-size: 0.82rem; font-weight:600;">
                                                <?php echo htmlspecialchars($b['filename']); ?>
                                            </td>
                                            <td style="padding: 10px 14px; color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($b['date_formatted']); ?>
                                            </td>
                                            <td style="padding: 10px 14px; font-weight: 600;">
                                                <?php echo htmlspecialchars($b['size_formatted']); ?>
                                            </td>
                                            <td style="padding: 10px 14px; text-align: right;">
                                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                                    <!-- Download -->
                                                    <a href="?action=download_backup&file=<?php echo urlencode($b['filename']); ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px;" title="Herunterladen">
                                                        <span class="material-icons" style="font-size:15px;">download</span> Download
                                                    </a>

                                                    <!-- Wiederherstellen (Trigger Modal) -->
                                                    <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px; color:var(--primary); border-color:var(--primary);" onclick="openRestoreModal('<?php echo htmlspecialchars($b['filename'], ENT_QUOTES); ?>', '<?php echo $b['type']; ?>')" title="Wiederherstellen">
                                                        <span class="material-icons" style="font-size:15px;">settings_backup_restore</span> Restore
                                                    </button>

                                                    <!-- Löschen -->
                                                    <form method="POST" action="" style="display:inline; margin:0;" onsubmit="return confirm('Möchtest du diese Sicherungsdatei wirklich unwiderruflich löschen?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                        <input type="hidden" name="action" value="delete_backup">
                                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($b['filename']); ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem; background:#dc2626;" title="Löschen">
                                                            <span class="material-icons" style="font-size:15px;">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Backup hochladen & wiederherstellen (Upload-Formular) -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem;">
                    <h4 style="margin:0 0 0.5rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="color:var(--primary); font-size:20px;">upload_file</span> Externe Sicherung hochladen &amp; wiederherstellen
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                        Hier kannst du eine zuvor heruntergeladene ZIP- oder JSON-Sicherungsdatei von deinem Computer hochladen. Zum Schutz vor versehentlichem Überschreiben ist die Bestätigung mit deiner Administrator-PIN erforderlich. Vor dem Einspielen wird automatisch ein Sicherheits-Snapshot angelegt.
                    </p>

                    <form method="POST" action="" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.25rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="upload_restore">

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Sicherungsdatei auswählen (.zip oder .json)</label>
                                <input type="file" name="backup_upload_file" accept=".zip,.json" class="form-control" required style="padding: 8px;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Administrator-PIN zur Bestätigung <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="upload_restore_pin" name="restore_pin" class="form-control" placeholder="Aktuelle PIN eingeben" maxlength="10" required style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('upload_restore_pin')">visibility</span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:flex-start;">
                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px;" onclick="return confirm('Achtung: Das Einspielen überschreibt alle aktuellen Daten mit dem Inhalt der Datei. Möchtest du fortfahren?');">
                                <span class="material-icons" style="font-size:18px;">cloud_upload</span> Hochladen &amp; Wiederherstellung starten
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- FORMULAR 2: ADMIN-ZUGANGSDATEN ÄNDERN (NUR HIER IST DIE AKTUELLE PIN PFLICHT) -->
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color);">
                <form method="POST" action="" onsubmit="return validateCredentialsForm(this);" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_credentials">

                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px; color:var(--primary);">
                        <span class="material-icons" style="color:var(--primary);">vpn_key</span> Admin-Zugangsdaten ändern (Passwort &amp; PIN)
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Hier kannst du dein persönliches Passwort und den PIN-Code für die Anmeldung ändern. Lasse die Felder leer, wenn du sie nicht ändern möchtest. Zum Schutz deines Adminbereichs ist vor der Übernahme die Eingabe deiner aktuell gültigen PIN zwingend erforderlich.
                    </p>

                    <!-- Sicherheitsabfrage Box (Erfordert aktuelle PIN, NIEMALS durch Cache/Cookies vorbelegt) -->
                    <div style="background: rgba(178, 108, 57, 0.08); border: 1px solid rgba(178, 108, 57, 0.3); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.75rem;">
                        <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:1rem;">
                            <span class="material-icons" style="color:var(--accent); font-size:22px; margin-top:2px;">shield</span>
                            <div style="font-size:0.88rem; color:var(--text-primary); line-height:1.45;">
                                <strong>Sicherheitsabfrage:</strong> Aus Sicherheitsgründen ist vor jeder Änderung von Passwort oder PIN die Eingabe deiner <u>aktuell noch gültigen PIN</u> erforderlich! Ohne korrekte aktuelle PIN werden neue Eingaben abgewiesen.
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 320px; margin-bottom: 0;">
                            <label for="current_pin" style="font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem; font-size:0.9rem;">
                                Aktuelle PIN <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="current_pin" 
                                       name="current_pin" 
                                       class="form-control" 
                                       placeholder="Aktuell gültige PIN eingeben" 
                                       maxlength="10" 
                                       autocomplete="new-password" 
                                       data-lpignore="true"
                                       data-form-type="other"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px; letter-spacing:2px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('current_pin')">visibility</span>
                            </div>
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Zwingend erforderlich, um neues Passwort oder neue PIN zu aktivieren.</small>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label for="new_password">Neues Passwort (optional)</label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       placeholder="Neues Passwort" 
                                       autocomplete="new-password" 
                                       data-lpignore="true"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_password')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;">Nur ausfüllen, wenn du ein neues Passwort setzen möchtest.</small>
                        </div>
                        <div class="form-group">
                            <label for="new_pin">Neuer PIN-Code (optional)</label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_pin" 
                                       name="new_pin" 
                                       class="form-control" 
                                       placeholder="z. B. 4-6 Ziffern" 
                                       maxlength="10" 
                                       autocomplete="new-password" 
                                       data-lpignore="true"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px; letter-spacing:2px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_pin')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;">Nur ausfüllen, wenn du eine neue PIN setzen möchtest.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:18px;">vpn_key</span> Neue Zugangsdaten speichern
                    </button>
                </form>
            </div>

            <!-- 5. SPEICHER-ENGINE & 1-KLICK-DATEN-MIGRATION (PHASE 7.3) -->
            <?php 
                $storage_details = StorageManager::getDriverDetails();
                $cur_engine = $storage_details['type'];
                $is_db_active = ($cur_engine === 'mysql');
            ?>
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color);">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom: 0.75rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.25rem; display:flex; align-items:center; gap:8px; color:var(--primary);">
                            <span class="material-icons" style="color:var(--primary);">storage</span> Speicher-Engine &amp; 1-Klick-Daten-Migration (Phase 7.3)
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0;">
                            Wahlfreiheit zwischen Flat-File JSON und relationaler MySQL/MariaDB-Datenbank mit nahtloser 1-Klick-Datenübertragung.
                        </p>
                    </div>
                    <div>
                        <?php if ($storage_details['ping_ok']): ?>
                            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700;">
                                <span class="material-icons" style="font-size:16px;">check_circle</span> <?php echo htmlspecialchars($storage_details['name']); ?> (Aktiv &amp; Bereit)
                            </span>
                        <?php else: ?>
                            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700;">
                                <span class="material-icons" style="font-size:16px;">error</span> Verbindung gestört
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Box Aktiver Treiber -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display:flex; align-items:center; gap:12px;">
                    <span class="material-icons" style="color:var(--primary); font-size:24px;">info</span>
                    <div style="font-size:0.88rem; color:var(--text-secondary);">
                        <strong>Aktiver Speicherort:</strong> <code style="color:var(--text-primary); font-weight:600;"><?php echo htmlspecialchars($storage_details['location']); ?></code>
                        <?php if ($storage_details['is_fallback']): ?>
                            <br><span style="color:#f59e0b; font-weight:600;">⚠️ Notfall-Fallback aktiv: Die konfigurierte Datenbank war nicht erreichbar; WeaveCMS nutzt temporär die lokale data.json.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$is_db_active): ?>
                    <!-- MIGRATION ZU MYSQL -->
                    <div style="background: rgba(46,98,72,0.04); border: 1px solid rgba(46,98,72,0.2); border-radius: 14px; padding: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">cloud_upload</span> Von Flat-File JSON zu MySQL / MariaDB migrieren
                        </h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                            Überträgt alle aktuellen Inhalte, Einstellungen und Termine in eine relationale MySQL-Datenbank. Vor der Migration wird automatisch ein Sicherheits-Backup deiner <code>data.json</code> angelegt.
                        </p>

                        <form method="POST" action="" onsubmit="return confirm('Möchtest du alle Daten jetzt in die MySQL-Datenbank migrieren und MySQL aktivieren?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="migrate_storage_engine">
                            <input type="hidden" name="target_engine" value="mysql">

                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Datenbank-Host <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_host" name="db_host" class="form-control" value="127.0.0.1" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Port</label>
                                    <input type="number" id="mig_db_port" name="db_port" class="form-control" value="3306" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Datenbank-Name <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_name" name="db_name" class="form-control" value="weavecms" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Benutzername <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_user" name="db_user" class="form-control" value="root" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Passwort</label>
                                    <input type="password" id="mig_db_pass" name="db_pass" class="form-control" value="" placeholder="Falls vorhanden">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;">Tabellen-Präfix</label>
                                    <input type="text" id="mig_db_prefix" name="db_prefix" class="form-control" value="weave_">
                                </div>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.88rem; color:var(--text-secondary);">
                                    <input type="checkbox" id="mig_create_db" name="create_db" value="1" checked style="accent-color:var(--primary);">
                                    Datenbank automatisch neu anlegen, falls noch nicht vorhanden
                                </label>
                            </div>

                            <div style="display:flex; align-items:center; gap:12px; margin-bottom: 1.25rem; flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline" id="mig_test_btn" onclick="testDbMigration();" style="display:inline-flex; align-items:center; gap:6px; font-size:0.88rem; padding:8px 14px;">
                                    <span class="material-icons" style="font-size:16px;">bolt</span> Verbindung testen
                                </button>
                                <span id="mig_test_feedback" style="display:none; font-size:0.85rem; font-weight:600; align-items:center; gap:5px;"></span>
                            </div>

                            <div style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display:flex; flex-direction:column; gap:0.75rem; max-width: 420px; margin-bottom: 1.25rem;">
                                <label style="font-size:0.85rem; font-weight:700;">Administrator-PIN zur Bestätigung <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="mig_pin_mysql" name="migrate_pin" class="form-control" placeholder="Aktuelle PIN eingeben" required maxlength="10" style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('mig_pin_mysql')">visibility</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;">
                                <span class="material-icons" style="font-size:18px;">sync_alt</span> Zu MySQL migrieren &amp; aktivieren
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- MIGRATION ZU JSON -->
                    <div style="background: rgba(178,108,57,0.04); border: 1px solid rgba(178,108,57,0.25); border-radius: 14px; padding: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--accent); font-size:20px;">folder</span> Zu Flat-File JSON (data.json) wechseln &amp; exportieren
                        </h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                            Exportiert den gesamten aktuellen Datenbestand aus der MySQL-Datenbank vollständig in die lokale Datei <code>data/data.json</code> und entfernt die SQL-Konfiguration. WeaveCMS läuft danach wieder autark ohne Datenbank.
                        </p>

                        <form method="POST" action="" onsubmit="return confirm('Möchtest du alle Inhalte aus MySQL in data.json sichern und auf Flat-File-Betrieb umschalten?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="migrate_storage_engine">
                            <input type="hidden" name="target_engine" value="json">

                            <div style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display:flex; flex-direction:column; gap:0.75rem; max-width: 420px; margin-bottom: 1.25rem;">
                                <label style="font-size:0.85rem; font-weight:700;">Administrator-PIN zur Bestätigung <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="mig_pin_json" name="migrate_pin" class="form-control" placeholder="Aktuelle PIN eingeben" required maxlength="10" style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('mig_pin_json')">visibility</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;">
                                <span class="material-icons" style="font-size:18px;">download_for_offline</span> Zu Flat-File JSON migrieren &amp; aktivieren
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
