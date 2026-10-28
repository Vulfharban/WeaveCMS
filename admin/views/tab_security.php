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
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;"><?php echo __a('admin.security.tab_title', '🔒 Sicherheit & System'); ?></h2>
            
            <!-- FORMULAR 1: SPAMSCHUTZ, SMTP & SYSTEMEINSTELLUNGEN (KEINE PIN ERFORDERLICH) -->
            <form method="POST" action="" onsubmit="return validateSystemForm(this);">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_security">

                <!-- 1. SPAMSCHUTZ / MODULARE CAPTCHA-ENGINE (PHASE 16) -->
                <?php
                    $cur_captcha_mode = CaptchaManager::getMode($data);
                    $impressum_email_protected = CaptchaManager::isImpressumEmailProtected($data);
                    $contact_email_protected = CaptchaManager::isContactEmailProtected($data);
                    $prod_sec = SecurityManager::getProductionSettings();
                    $is_https = SecurityManager::isHttps();
                    $perms_audit = SecurityManager::auditPermissions();
                ?>
                <h3 style="font-size: 1.2rem; margin-top: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="color:var(--primary);">verified_user</span> <?php echo __a('admin.security.captcha_title', 'Aktives Captcha-Verfahren & Bot-Schutz'); ?>
                    </span>
                    <span id="captcha-counter-badge" style="font-size:0.85rem; padding:4px 12px; border-radius:20px; font-weight:600; background:rgba(46,98,72,0.12); color:var(--primary); <?php echo ($cur_captcha_mode !== 'icons') ? 'display:none;' : ''; ?>">
                        <?php echo __a('admin.security.captcha_count_badge', 'Ausgewählt: {count} von 4 Symbolen', null, ['count' => '<span id="captcha-count">' . count(get_active_captcha_icons($data)) . '</span>']); ?>
                    </span>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    <?php echo __a('admin.security.captcha_desc', 'Wähle hier das gewünschte Spamschutz-Verfahren für dein Kontaktformular aus. Alle Verfahren sind 100% DSGVO-konform ohne externe Tracker.'); ?>
                </p>

                <!-- Captcha-Modus Auswahl-Kacheln -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 1.5rem;">
                    <label class="captcha-mode-card" style="display:flex; flex-direction:column; padding:14px; border:2px solid <?php echo ($cur_captcha_mode === 'icons') ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius:12px; background:<?php echo ($cur_captcha_mode === 'icons') ? 'rgba(46,98,72,0.06)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem;"><?php echo __a('admin.security.mode_icons_title', '🌿 Symbol-Captcha'); ?></span>
                            <input type="radio" name="captcha_mode" value="icons" <?php echo ($cur_captcha_mode === 'icons') ? 'checked' : ''; ?> onchange="onCaptchaModeChange(this.value)">
                        </div>
                        <span style="font-size:0.8rem; color:var(--text-secondary); line-height:1.35;">
                            <?php echo __a('admin.security.mode_icons_desc', 'Klick-Auswahl aus 4 Natur- und Flechtsymbolen. Der bewährte WeaveCMS Standard.'); ?>
                        </span>
                    </label>

                    <label class="captcha-mode-card" style="display:flex; flex-direction:column; padding:14px; border:2px solid <?php echo ($cur_captcha_mode === 'math') ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius:12px; background:<?php echo ($cur_captcha_mode === 'math') ? 'rgba(46,98,72,0.06)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem;"><?php echo __a('admin.security.mode_math_title', '🔢 Mathe-Aufgabe'); ?></span>
                            <input type="radio" name="captcha_mode" value="math" <?php echo ($cur_captcha_mode === 'math') ? 'checked' : ''; ?> onchange="onCaptchaModeChange(this.value)">
                        </div>
                        <span style="font-size:0.8rem; color:var(--text-secondary); line-height:1.35;">
                            <?php echo __a('admin.security.mode_math_desc', 'Dynamische Zufallsaufgabe (z. B. „4 + 3 = ?“). Einfach und hochgradig barrierefrei.'); ?>
                        </span>
                    </label>

                    <label class="captcha-mode-card" style="display:flex; flex-direction:column; padding:14px; border:2px solid <?php echo ($cur_captcha_mode === 'canvas') ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius:12px; background:<?php echo ($cur_captcha_mode === 'canvas') ? 'rgba(46,98,72,0.06)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem;"><?php echo __a('admin.security.mode_canvas_title', '🎨 Canvas-Zahlen'); ?></span>
                            <input type="radio" name="captcha_mode" value="canvas" <?php echo ($cur_captcha_mode === 'canvas') ? 'checked' : ''; ?> onchange="onCaptchaModeChange(this.value)">
                        </div>
                        <span style="font-size:0.8rem; color:var(--text-secondary); line-height:1.35;">
                            <?php echo __a('admin.security.mode_canvas_desc', '5-stelliger Code im Canvas mit Rauschlinien. Immun gegen Text-Scraper.'); ?>
                        </span>
                    </label>

                    <label class="captcha-mode-card" style="display:flex; flex-direction:column; padding:14px; border:2px solid <?php echo ($cur_captcha_mode === 'honeypot') ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius:12px; background:<?php echo ($cur_captcha_mode === 'honeypot') ? 'rgba(46,98,72,0.06)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem;"><?php echo __a('admin.security.mode_honeypot_title', '🥷 Unsichtbarer Bot-Schutz'); ?></span>
                            <input type="radio" name="captcha_mode" value="honeypot" <?php echo ($cur_captcha_mode === 'honeypot') ? 'checked' : ''; ?> onchange="onCaptchaModeChange(this.value)">
                        </div>
                        <span style="font-size:0.8rem; color:var(--text-secondary); line-height:1.35;">
                            <?php echo __a('admin.security.mode_honeypot_desc', 'Honeypot-Falle + Time-Trap ohne sichtbare Frage. Höchster Komfort für Besucher.'); ?>
                        </span>
                    </label>
                </div>

                <!-- Symbol-Auswahl (nur sichtbar bei Modus 'icons') -->
                <div id="captcha-icons-section" style="<?php echo ($cur_captcha_mode !== 'icons') ? 'display:none;' : ''; ?> margin-bottom: 2rem;">
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 0.75rem;">
                        <?php echo __a('admin.security.icons_selection_desc', 'Wähle hier genau 4 der 12 zur Verfügung stehenden Symbole aus:'); ?>
                    </p>
                    <?php 
                        $all_icons = get_all_captcha_icons();
                        $active_icons_keys = array_keys(get_active_captcha_icons($data));
                    ?>
                    <div class="captcha-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;">
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
                </div>

                <!-- Anti-Scraping E-Mail-Schutz (Impressum & Kontaktbereich) -->
                <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem; display:flex; flex-direction:column; gap:1rem;">
                    <label style="display:inline-flex; align-items:flex-start; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="impressum_email_captcha" value="1" <?php echo $impressum_email_protected ? 'checked' : ''; ?> style="width:18px; height:18px; margin-top:2px;">
                        <div>
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block;">
                                <?php echo __a('admin.security.impressum_email_title', '🛡️ E-Mail-Adresse im Impressum mit aktiver Captcha-Prüfung schützen (Anti-Scraping)'); ?>
                            </span>
                            <span style="font-size:0.84rem; color:var(--text-secondary); line-height:1.4; display:block; margin-top:2px;">
                                <?php echo __a('admin.security.impressum_email_desc', 'Verhindert, dass automatisierte Adress-Harvester und Spam-Crawler deine E-Mail-Adresse im Impressum im Klartext auslesen. Besucher schalten die Adresse per Klick und 5-stelligem Canvas-Code frei.'); ?>
                            </span>
                        </div>
                    </label>

                    <label style="display:inline-flex; align-items:flex-start; gap:10px; cursor:pointer; padding-top:0.75rem; border-top:1px solid rgba(0,0,0,0.06);">
                        <input type="checkbox" name="contact_email_captcha" value="1" <?php echo $contact_email_protected ? 'checked' : ''; ?> style="width:18px; height:18px; margin-top:2px;">
                        <div>
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block;">
                                <?php echo __a('admin.security.contact_email_title', '🛡️ E-Mail-Adresse im Kontaktbereich mit aktiver Captcha-Prüfung schützen (Anti-Scraping)'); ?>
                            </span>
                            <span style="font-size:0.84rem; color:var(--text-secondary); line-height:1.4; display:block; margin-top:2px;">
                                <?php echo __a('admin.security.contact_email_desc', 'Schützt die direkte Kontakt-E-Mail-Adresse im Kontakt-Menü vor Spam-Harvestern. Besucher schalten die Adresse per Klick und 5-stelligem Canvas-Code frei (das Kontaktformular bleibt weiterhin uneingeschränkt nutzbar).'); ?>
                            </span>
                        </div>
                    </label>
                </div>

                <script>
                function onCaptchaModeChange(mode) {
                    const iconsSec = document.getElementById('captcha-icons-section');
                    const badge = document.getElementById('captcha-counter-badge');
                    if (iconsSec) iconsSec.style.display = (mode === 'icons') ? 'block' : 'none';
                    if (badge) badge.style.display = (mode === 'icons') ? 'inline-block' : 'none';

                    document.querySelectorAll('.captcha-mode-card').forEach(card => {
                        const input = card.querySelector('input[type="radio"]');
                        if (input && input.checked) {
                            card.style.borderColor = 'var(--primary)';
                            card.style.background = 'rgba(46,98,72,0.06)';
                        } else {
                            card.style.borderColor = 'var(--border-color)';
                            card.style.background = 'rgba(0,0,0,0.015)';
                        }
                    });
                }
                </script>

                <!-- 2. E-MAIL & SMTP VERSAND -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">mail</span> <?php echo __a('admin.security.smtp_title', 'E-Mail- & SMTP-Versandeinstellungen (Kontaktformular)'); ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    <?php echo __a('admin.security.smtp_desc', 'Konfiguriere hier den Server-Mailversand für dein Kontaktformular. Wenn SMTP aktiviert ist, werden E-Mails authentifiziert über dein Postfach versendet (höchste Zuverlässigkeit gegen Spamfilter). Andernfalls wird standardmäßig die PHP-Mailfunktion deines Webhosters genutzt.'); ?>
                </p>

                <?php 
                    $cur_smtp = $data['settings']['smtp'] ?? [];
                    $smtp_is_enabled = !empty($cur_smtp['enabled']);
                ?>

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.95rem;">
                            <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtp_is_enabled ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            <?php echo __a('admin.security.smtp_enable_label', 'Authentifizierten SMTP-Versand aktivieren (Empfohlen für Live-Server & Mailpostfächer)'); ?>
                        </label>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_host', 'SMTP-Server (Host)'); ?></label>
                            <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['host'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(__a('admin.security.smtp_host_ph', 'z. B. smtp.strato.de oder mail.ihredomain.de')); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_port', 'Port'); ?></label>
                            <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['port'] ?? 587); ?>" placeholder="587">
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_encryption', 'Verschlüsselung'); ?></label>
                            <select name="smtp_secure" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <option value="tls" <?php echo (($cur_smtp['secure'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>><?php echo __a('admin.security.smtp_enc_tls', 'TLS / STARTTLS (Port 587, Standard)'); ?></option>
                                <option value="ssl" <?php echo (($cur_smtp['secure'] ?? '') === 'ssl') ? 'selected' : ''; ?>><?php echo __a('admin.security.smtp_enc_ssl', 'SSL (Port 465)'); ?></option>
                                <option value="none" <?php echo (($cur_smtp['secure'] ?? '') === 'none') ? 'selected' : ''; ?>><?php echo __a('admin.security.smtp_enc_none', 'Keine Verschlüsselung (Port 25)'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_user', 'SMTP-Benutzername / E-Mail'); ?></label>
                            <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['user'] ?? ''); ?>" placeholder="z. B. kontakt@maxima-musterfrau.de">
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_pass', 'SMTP-Passwort'); ?></label>
                            <div style="position:relative;">
                                <input type="password" id="smtp_pass" name="smtp_pass" class="form-control" placeholder="<?php echo !empty($cur_smtp['pass']) ? htmlspecialchars(__a('admin.security.smtp_pass_placeholder_saved', '•••••••• (bereits hinterlegt)')) : htmlspecialchars(__a('admin.security.smtp_pass_placeholder_empty', 'Postfach-Passwort')); ?>" style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('smtp_pass')">visibility</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_from_email', 'Absender-E-Mail-Adresse (From)'); ?></label>
                            <input type="email" name="smtp_from_email" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_email'] ?? ''); ?>" placeholder="z. B. kontakt@maxima-musterfrau.de">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;"><?php echo __a('admin.security.smtp_from_email_hint', 'Leer lassen, um die Benutzername-E-Mail zu verwenden.'); ?></small>
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.security.smtp_from_name', 'Absender-Name (From Name)'); ?></label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_name'] ?? 'Atelier Musterfrau'); ?>" placeholder="Atelier Musterfrau">
                        </div>
                    </div>
                </div>

                <!-- 3. BILDUPLOAD-VERZEICHNIS -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    <?php echo __a('admin.security.upload_dir_title', '📁 Bildupload-Verzeichnis & Speicherort'); ?>
                </h3>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label><?php echo __a('admin.security.upload_dir_label', 'Speicherordner für hochgeladene Bilder (relativ zum Web-Root)'); ?></label>
                    <input type="text" name="upload_dir" class="form-control" value="<?php echo htmlspecialchars($data['settings']['upload_dir'] ?? 'images/uploads'); ?>" placeholder="images/uploads" required>
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        <?php echo __a('admin.security.upload_dir_hint', 'Standard: images/uploads oder uploads. Verzeichnisse werden bei Bedarf automatisch erzeugt.'); ?>
                    </small>
                </div>

                <!-- 4. LIVE-SERVER-DEPLOYMENT, SSL/HSTS-HÄRTUNG & PRODUKTIONS-SICHERHEIT (PHASE 17) -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="color:var(--primary);">shield</span> <?php echo __a('admin.security.prod_title', 'Live-Server-Deployment & Produktions-Härtung (Phase 17)'); ?>
                    </span>
                    <?php if ($is_https): ?>
                        <span style="font-size:0.8rem; background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); border-radius:20px; padding:3px 10px; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:14px;">https</span> <?php echo __a('admin.security.ssl_active', 'SSL Aktiv (HTTPS)'); ?>
                        </span>
                    <?php else: ?>
                        <span style="font-size:0.8rem; background:rgba(217,119,6,0.12); color:#d97706; border:1px solid rgba(217,119,6,0.25); border-radius:20px; padding:3px 10px; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:14px;">http</span> <?php echo __a('admin.security.ssl_inactive', 'Lokaler Dev-Modus (HTTP)'); ?>
                        </span>
                    <?php endif; ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    <?php echo __a('admin.security.prod_desc', 'Erweiterte Sicherheits-Optionen für den Live-Betrieb im Internet (Apache / Nginx mit SSL-Zertifikat und Reverse-Proxies).'); ?>
                </p>

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                    <!-- 17.1 HSTS Preload -->
                    <label style="display:inline-flex; align-items:flex-start; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="prod_hsts_preload" value="1" <?php echo !empty($prod_sec['hsts_preload']) ? 'checked' : ''; ?> style="width:18px; height:18px; margin-top:2px; accent-color:var(--primary);">
                        <div>
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block;">
                                <?php echo __a('admin.security.hsts_title', '🔒 Strikte HSTS-Policy & SSL-Preloading aktivieren (Phase 17.1)'); ?>
                            </span>
                            <span style="font-size:0.84rem; color:var(--text-secondary); line-height:1.4; display:block; margin-top:2px;">
                                <?php echo __a('admin.security.hsts_desc', 'Sendet den Header Strict-Transport-Security: max-age=31536000; includeSubDomains; preload (nur aktiv bei bestehender HTTPS-Verbindung). Erzwingt moderne Browser auf 100 % verschlüsselte Verbindungen.'); ?>
                            </span>
                        </div>
                    </label>

                    <!-- 17.2 Production CSP -->
                    <label style="display:inline-flex; align-items:flex-start; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="prod_production_csp" value="1" <?php echo !empty($prod_sec['production_csp']) ? 'checked' : ''; ?> style="width:18px; height:18px; margin-top:2px; accent-color:var(--primary);">
                        <div>
                            <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block;">
                                <?php echo __a('admin.security.csp_title', '🛡️ Gehärtete Production-CSP (Content Security Policy) aktivieren (Phase 17.2)'); ?>
                            </span>
                            <span style="font-size:0.84rem; color:var(--text-secondary); line-height:1.4; display:block; margin-top:2px;">
                                <?php echo __a('admin.security.csp_desc', 'Schützt deine Website vor Cross-Site-Scripting (XSS) und unbefugtem Nachladen externer Ressourcen durch strikte Restriktion auf sichere eigene Domains und kuratierte Google-Fonts.'); ?>
                            </span>
                        </div>
                    </label>

                    <!-- 17.4 Trusted Proxies -->
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block; margin-bottom:4px;">
                            <?php echo __a('admin.security.proxies_title', '🌐 Vertrauenswürdige Reverse-Proxies & Cloudflare IPs (Anti-Spoofing - Phase 17.4)'); ?>
                        </label>
                        <input type="text" name="prod_trusted_proxies" class="form-control" value="<?php echo htmlspecialchars($prod_sec['trusted_proxies'] ?? '127.0.0.1, ::1'); ?>" placeholder="127.0.0.1, ::1, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16">
                        <small style="color:var(--text-secondary); display:block; margin-top:4px;">
                            <?php echo __a('admin.security.proxies_hint', 'Kommagetrennte Liste von Upstream-Proxy-IPs oder CIDR-Bereichen. X-Forwarded-For und CF-Connecting-IP werden nur von diesen IP-Adressen akzeptiert, um IP-Spoofing beim Rate-Limiting und Audit-Log zu verhindern.'); ?>
                        </small>
                    </div>

                    <!-- 17.5 Automatisierte Admin-Alarmierung -->
                    <div style="border-top:1px solid var(--border-color); padding-top:1.25rem;">
                        <label style="display:inline-flex; align-items:flex-start; gap:10px; cursor:pointer; margin-bottom:0.75rem;">
                            <input type="checkbox" name="prod_alert_on_bruteforce" value="1" <?php echo !empty($prod_sec['alert_on_bruteforce']) ? 'checked' : ''; ?> style="width:18px; height:18px; margin-top:2px; accent-color:var(--primary);">
                            <div>
                                <span style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:block;">
                                    <?php echo __a('admin.security.alert_title', '🚨 Sofortige E-Mail-Alarmierung bei Brute-Force-Sperren (Phase 17.5)'); ?>
                                </span>
                                <span style="font-size:0.84rem; color:var(--text-secondary); line-height:1.4; display:block; margin-top:2px;">
                                    <?php echo __a('admin.security.alert_desc', 'Sendet automatisch eine Alarm-E-Mail an den Administrator, sobald eine IP-Adresse wegen wiederholter Fehlversuche gesperrt wird.'); ?>
                                </span>
                            </div>
                        </label>

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:0.85rem;"><?php echo __a('admin.security.alert_email_label', 'Alarm-Empfänger-E-Mail'); ?></label>
                                <input type="email" name="prod_alert_email" class="form-control" value="<?php echo htmlspecialchars($prod_sec['alert_email'] ?? ($data['texts']['contact_email'] ?? '')); ?>" placeholder="admin@domain.de">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:0.85rem;"><?php echo __a('admin.security.alert_cooldown_label', 'E-Mail-Cooldown (Minuten)'); ?></label>
                                <input type="number" name="prod_alert_cooldown_minutes" class="form-control" value="<?php echo (int)($prod_sec['alert_cooldown_minutes'] ?? 15); ?>" min="5" max="1440">
                                <small style="color:var(--text-secondary); display:block; margin-top:3px;"><?php echo __a('admin.security.alert_cooldown_hint', 'Verhindert E-Mail-Spam bei anhaltenden Angriffen.'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:18px;">save</span> <?php echo __a('admin.security.btn_save_system', 'Einstellungen speichern'); ?>
                </button>
            </form>

            <!-- SMTP Test Formular (separates Formular mit eigenem CSRF-Token) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem; margin-top: 2rem;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px; color:var(--primary);">send</span> <?php echo __a('admin.security.smtp_test_title', 'SMTP-Verbindung jetzt testen'); ?>
                </h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem;">
                    <?php echo __a('admin.security.smtp_test_desc', 'Sende eine Test-E-Mail, um deine hinterlegten SMTP-Zugangsdaten sofort auf Funktion zu prüfen (vorher bitte oben speichern).'); ?>
                </p>
                <form method="POST" action="" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="test_smtp">
                    <div class="form-group" style="flex:1; min-width:240px; margin-bottom:0;">
                        <label style="font-size:0.85rem; margin-bottom:4px; display:block;"><?php echo __a('admin.security.smtp_test_to_label', 'Test-E-Mail senden an:'); ?></label>
                        <input type="email" name="test_to_email" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de'); ?>" required style="height:42px;">
                    </div>
                    <button type="submit" class="btn btn-outline" style="height:42px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">mark_email_read</span> <?php echo __a('admin.security.btn_send_test', 'Test-E-Mail jetzt senden'); ?>
                    </button>
                </form>
            </div>

            <!-- 5. DATEISYSTEM-BERECHTIGUNGS-AUDIT (POSIX/LINUX) & DEPLOYMENT-CHECKLISTE (PHASE 17.3 & 17.6) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-top: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem;">
                    <h4 style="margin:0; font-size:1.1rem; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:20px; color:var(--primary);">verified</span> <?php echo __a('admin.security.audit_title', 'Dateisystem-Berechtigungen & POSIX-Audit (Phase 17.3)'); ?>
                    </h4>
                    <span style="font-size:0.82rem; padding:4px 12px; border-radius:12px; font-weight:700; background:<?php echo ($perms_audit['overall_status'] === 'ok') ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)'; ?>; color:<?php echo ($perms_audit['overall_status'] === 'ok') ? '#10b981' : '#dc2626'; ?>;">
                        <?php echo __a('admin.security.audit_os_badge', 'Betriebssystem: {os} ({type})', null, [
                            'os' => htmlspecialchars($perms_audit['os']),
                            'type' => $perms_audit['is_linux'] ? __a('admin.security.audit_type_linux', 'Linux/POSIX') : __a('admin.security.audit_type_windows', 'Windows-Server')
                        ]); ?>
                    </span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                    <?php echo __a('admin.security.audit_desc', 'Überprüft die Dateizugriffsrechte für sensible Konfigurationsdateien, Datenspeicher und Ordner gegen unbefugtes Lesen oder Schreiben.'); ?>
                </p>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.85rem; text-align:left;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02);">
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_path', 'Pfad / Komponente'); ?></th>
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_type', 'Typ'); ?></th>
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_status', 'Status'); ?></th>
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_actual_perms', 'Ist-Rechte'); ?></th>
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_rec_perms', 'Empfohlen'); ?></th>
                                <th style="padding:8px 10px;"><?php echo __a('admin.security.col_finding', 'Befund / Empfohlene Maßnahme'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perms_audit['items'] as $item): ?>
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:8px 10px; font-family:monospace; font-weight:600;"><?php echo htmlspecialchars($item['path']); ?></td>
                                    <td style="padding:8px 10px; color:var(--text-secondary);"><?php echo htmlspecialchars($item['type']); ?></td>
                                    <td style="padding:8px 10px;">
                                        <?php if ($item['status'] === 'ok'): ?>
                                            <span style="color:#10b981; font-weight:700;"><?php echo __a('admin.security.status_ok', '✓ Sicher'); ?></span>
                                        <?php elseif ($item['status'] === 'warning'): ?>
                                            <span style="color:#d97706; font-weight:700;"><?php echo __a('admin.security.status_warning', '⚠ Prüfen'); ?></span>
                                        <?php else: ?>
                                            <span style="color:#dc2626; font-weight:700;"><?php echo __a('admin.security.status_critical', '✕ Kritisch'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:8px 10px; font-family:monospace;"><?php echo htmlspecialchars($item['perms']); ?></td>
                                    <td style="padding:8px 10px; color:var(--text-secondary);"><?php echo htmlspecialchars($item['recommended']); ?></td>
                                    <td style="padding:8px 10px;">
                                        <span><?php echo htmlspecialchars($item['message']); ?></span>
                                        <?php if (!empty($item['fix_cmd']) && $item['status'] !== 'ok'): ?>
                                            <code style="display:block; margin-top:2px; font-size:0.75rem; background:rgba(0,0,0,0.05); padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($item['fix_cmd']); ?></code>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 17.6 Live-Deployment Checkliste -->
                <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px dashed var(--border-color);">
                    <h5 style="margin:0 0 0.75rem 0; font-size:0.95rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:18px; color:var(--primary);">checklist</span> <?php echo __a('admin.security.checklist_title', 'Live-Deployment Checkliste & Audit-Tools (Phase 17.6)'); ?>
                    </h5>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:10px; margin-bottom:1rem;">
                        <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                            <span class="material-icons" style="color:<?php echo $is_https ? '#10b981' : '#d97706'; ?>; font-size:18px;"><?php echo $is_https ? 'check_circle' : 'help'; ?></span>
                            <span><?php echo __a('admin.security.check_ssl', '1. Gültiges SSL-Zertifikat (HTTPS)'); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                            <span class="material-icons" style="color:<?php echo !empty($prod_sec['hsts_preload']) ? '#10b981' : '#9ca3af'; ?>; font-size:18px;"><?php echo !empty($prod_sec['hsts_preload']) ? 'check_circle' : 'radio_button_unchecked'; ?></span>
                            <span><?php echo __a('admin.security.check_hsts', '2. Strikte HSTS-Policy mit Preload'); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                            <span class="material-icons" style="color:<?php echo !empty($prod_sec['production_csp']) ? '#10b981' : '#9ca3af'; ?>; font-size:18px;"><?php echo !empty($prod_sec['production_csp']) ? 'check_circle' : 'radio_button_unchecked'; ?></span>
                            <span><?php echo __a('admin.security.check_csp', '3. Gehärtete Content Security Policy'); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                            <span class="material-icons" style="color:<?php echo ($perms_audit['overall_status'] === 'ok') ? '#10b981' : '#d97706'; ?>; font-size:18px;"><?php echo ($perms_audit['overall_status'] === 'ok') ? 'check_circle' : 'warning'; ?></span>
                            <span><?php echo __a('admin.security.check_perms', '4. Dateirechte & .htaccess-Abschirmung'); ?></span>
                        </div>
                    </div>
                    <?php 
                        $curHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    ?>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="https://securityheaders.com/?q=<?php echo urlencode($curHost); ?>&followRedirects=on" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:15px;">open_in_new</span> <?php echo __a('admin.security.btn_test_sec_headers', 'SecurityHeaders.com Testen'); ?>
                        </a>
                        <a href="https://www.ssllabs.com/ssltest/analyze.html?d=<?php echo urlencode($curHost); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:15px;">open_in_new</span> <?php echo __a('admin.security.btn_test_ssllabs', 'Qualys SSL Labs Analyse'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 6. INTEGRIERTES BACKUP- & WIEDERHERSTELLUNGSSYSTEM (PHASE 2) -->
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color);">
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px; color:var(--primary);">
                    <span class="material-icons" style="color:var(--primary);">save</span> <?php echo __a('admin.security.backup_title', 'Datensicherung & Wiederherstellung (Backup / Restore)'); ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <?php echo __a('admin.security.backup_desc', 'Sichere alle Website-Inhalte und hochgeladenen Bilder mit einem Klick. Alle Sicherungen werden im geschützten Verzeichnis backups/ auf dem Server archiviert (geschützt vor Direktaufrufen via .htaccess) und können jederzeit heruntergeladen oder wiederhergestellt werden.'); ?>
                </p>

                <!-- 1-Klick Backup Aktionen -->
                <div style="background: rgba(16, 185, 129, 0.04); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="margin:0 0 0.75rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="color:var(--primary); font-size:20px;">add_circle</span> <?php echo __a('admin.security.create_backup_title', '1-Klick-Sicherung jetzt erstellen'); ?>
                    </h4>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <form method="POST" action="" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="create_backup">
                            <input type="hidden" name="backup_type" value="data">
                            <button type="submit" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px;">
                                <span class="material-icons" style="font-size:18px;">description</span> <?php echo __a('admin.security.opt_backup_data', 'Option A: Nur Daten & Einstellungen (.json)'); ?>
                            </button>
                        </form>

                        <form method="POST" action="" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="create_backup">
                            <input type="hidden" name="backup_type" value="full">
                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px;">
                                <span class="material-icons" style="font-size:18px;">folder_zip</span> <?php echo __a('admin.security.opt_backup_full', 'Option B: Vollständiges Archiv inkl. Bilder (.zip)'); ?>
                            </button>
                        </form>
                    </div>
                    <small style="color:var(--text-secondary); display:block; margin-top:8px;">
                        <?php echo __a('admin.security.backup_recommendation', '💡 {rec} Erstelle regelmäßig ein vollständiges ZIP-Archiv vor größeren inhaltlichen Änderungen oder Updates.', null, [
                            'rec' => '<em>' . __a('admin.security.recommendation_word', 'Empfehlung:') . '</em>'
                        ]); ?>
                    </small>

                    <!-- Phase 12.3: Release-Packaging für Deployment & Übertragung -->
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div>
                            <strong style="font-size:0.9rem; color:var(--text-primary); display:block;"><?php echo __a('admin.security.pkg_title', '📦 Distributions- & Release-Paket generieren (Phase 12)'); ?></strong>
                            <span style="font-size:0.8rem; color:var(--text-secondary);"><?php echo __a('admin.security.pkg_desc', 'Erstellt saubere ZIP-Pakete unter releases/ (ohne Kundendaten, Logs und Git-Verzeichnisse) für Server-Übertragungen (z. B. WeaveCMS_ownsite).'); ?></span>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <form method="POST" action="" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="build_release_package">
                                <input type="hidden" name="package_type" value="full">
                                <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;">
                                    <span class="material-icons" style="font-size:15px;">archive</span> <?php echo __a('admin.security.pkg_full_btn', 'Voll-Paket (v{ver})', null, ['ver' => defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0']); ?>
                                </button>
                            </form>
                            <form method="POST" action="" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="build_release_package">
                                <input type="hidden" name="package_type" value="update">
                                <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;">
                                    <span class="material-icons" style="font-size:15px;">update</span> <?php echo __a('admin.security.pkg_update_btn', 'Update-Paket (v{ver})', null, ['ver' => defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0']); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Vorhandene Sicherungen (Tabelle) -->
                <?php $backups_list = BackupManager::listBackups(); ?>
                <div style="margin-bottom: 2.5rem;">
                    <h4 style="margin:0 0 1rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">history</span> <?php echo __a('admin.security.server_backups_title', 'Vorhandene Sicherungen auf dem Server'); ?>
                        </span>
                        <span style="font-size:0.82rem; color:var(--text-secondary); background:rgba(0,0,0,0.04); padding:3px 10px; border-radius:12px;">
                            <?php echo __a('admin.security.server_backups_count', 'Gesamt: {count} Datei(en)', null, ['count' => count($backups_list)]); ?>
                        </span>
                    </h4>

                    <?php if (empty($backups_list)): ?>
                        <div style="background: rgba(0,0,0,0.02); border: 1px dashed var(--border-color); border-radius: 12px; padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <span class="material-icons" style="font-size:36px; opacity:0.4; display:block; margin-bottom:8px;">inventory_2</span>
                            <?php echo __a('admin.security.backups_empty', 'Es sind noch keine Sicherungsdateien im Verzeichnis backups/ vorhanden.<br>Klicke oben auf 1-Klick-Sicherung, um dein erstes Backup zu erstellen.'); ?>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto; background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 12px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.03); color: var(--text-primary);">
                                        <th style="padding: 10px 14px;"><?php echo __a('admin.security.col_backup_type', 'Typ'); ?></th>
                                        <th style="padding: 10px 14px;"><?php echo __a('admin.security.col_backup_filename', 'Dateiname'); ?></th>
                                        <th style="padding: 10px 14px;"><?php echo __a('admin.security.col_backup_created', 'Erstellt am'); ?></th>
                                        <th style="padding: 10px 14px;"><?php echo __a('admin.security.col_backup_size', 'Größe'); ?></th>
                                        <th style="padding: 10px 14px; text-align: right;"><?php echo __a('admin.security.col_backup_actions', 'Aktionen'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups_list as $b): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                             <td style="padding: 10px 14px;">
                                                <?php if ($b['type'] === 'full'): ?>
                                                    <span style="background: rgba(16,185,129,0.12); color: var(--primary); padding: 3px 8px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;">
                                                        <span class="material-icons" style="font-size:14px;">folder_zip</span> <?php echo __a('admin.security.type_full_zip', 'ZIP-Vollarchiv'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: rgba(217,119,6,0.12); color: var(--accent); padding: 3px 8px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;">
                                                        <span class="material-icons" style="font-size:14px;">description</span> <?php echo __a('admin.security.type_json_data', 'JSON-Daten'); ?>
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
                                                    <a href="?action=download_backup&file=<?php echo urlencode($b['filename']); ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px;" title="<?php echo htmlspecialchars(__a('admin.security.title_download', 'Herunterladen')); ?>">
                                                        <span class="material-icons" style="font-size:15px;">download</span> <?php echo __a('admin.security.action_download', 'Download'); ?>
                                                    </a>

                                                    <!-- Wiederherstellen (Trigger Modal) -->
                                                    <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px; color:var(--primary); border-color:var(--primary);" onclick="openRestoreModal('<?php echo htmlspecialchars($b['filename'], ENT_QUOTES); ?>', '<?php echo $b['type']; ?>')" title="<?php echo htmlspecialchars(__a('admin.security.title_restore', 'Wiederherstellen')); ?>">
                                                        <span class="material-icons" style="font-size:15px;">settings_backup_restore</span> <?php echo __a('admin.security.action_restore', 'Restore'); ?>
                                                    </button>

                                                    <!-- Löschen -->
                                                    <form method="POST" action="" style="display:inline; margin:0;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.security.confirm_delete_backup', 'Möchtest du diese Sicherungsdatei wirklich unwiderruflich löschen?'), ENT_QUOTES); ?>');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                        <input type="hidden" name="action" value="delete_backup">
                                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($b['filename']); ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem; background:#dc2626;" title="<?php echo htmlspecialchars(__a('admin.security.title_delete', 'Löschen')); ?>">
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
                        <span class="material-icons" style="color:var(--primary); font-size:20px;">upload_file</span> <?php echo __a('admin.security.upload_restore_title', 'Externe Sicherung hochladen & wiederherstellen'); ?>
                    </h4>
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                        <?php echo __a('admin.security.upload_restore_desc', 'Hier kannst du eine zuvor heruntergeladene ZIP- oder JSON-Sicherungsdatei von deinem Computer hochladen. Zum Schutz vor versehentlichem Überschreiben ist die Bestätigung mit deiner Administrator-PIN erforderlich. Vor dem Einspielen wird automatisch ein Sicherheits-Snapshot angelegt.'); ?>
                    </p>

                    <form method="POST" action="" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.25rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="upload_restore">

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?php echo __a('admin.security.select_backup_file', 'Sicherungsdatei auswählen (.zip oder .json)'); ?></label>
                                <input type="file" name="backup_upload_file" accept=".zip,.json" class="form-control" required style="padding: 8px;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?php echo __a('admin.security.admin_pin_confirm', 'Administrator-PIN zur Bestätigung'); ?> <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="upload_restore_pin" name="restore_pin" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.security.enter_pin_ph', 'Aktuelle PIN eingeben')); ?>" maxlength="10" required style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('upload_restore_pin')">visibility</span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:flex-start;">
                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px;" onclick="return confirm('<?php echo htmlspecialchars(__a('admin.security.confirm_restore_upload', 'Achtung: Das Einspielen überschreibt alle aktuellen Daten mit dem Inhalt der Datei. Möchtest du fortfahren?'), ENT_QUOTES); ?>');">
                                <span class="material-icons" style="font-size:18px;">cloud_upload</span> <?php echo __a('admin.security.btn_upload_restore', 'Hochladen & Wiederherstellung starten'); ?>
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
                        <span class="material-icons" style="color:var(--primary);">vpn_key</span> <?php echo __a('admin.security.cred_title', 'Admin-Zugangsdaten ändern (Passwort & PIN)'); ?>
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        <?php echo __a('admin.security.cred_desc', 'Hier kannst du dein persönliches Passwort und den PIN-Code für die Anmeldung ändern. Lasse die Felder leer, wenn du sie nicht ändern möchtest. Zum Schutz deines Adminbereichs ist vor der Übernahme die Eingabe deiner aktuell gültigen PIN zwingend erforderlich.'); ?>
                    </p>

                    <!-- Sicherheitsabfrage Box (Erfordert aktuelle PIN, NIEMALS durch Cache/Cookies vorbelegt) -->
                    <div style="background: rgba(178, 108, 57, 0.08); border: 1px solid rgba(178, 108, 57, 0.3); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.75rem;">
                        <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:1rem;">
                            <span class="material-icons" style="color:var(--accent); font-size:22px; margin-top:2px;">shield</span>
                            <div style="font-size:0.88rem; color:var(--text-primary); line-height:1.45;">
                                <strong><?php echo __a('admin.security.cred_warning_title', 'Sicherheitsabfrage:'); ?></strong> <?php echo __a('admin.security.cred_warning_text', 'Aus Sicherheitsgründen ist vor jeder Änderung von Passwort oder PIN die Eingabe deiner aktuell noch gültigen PIN erforderlich! Ohne korrekte aktuelle PIN werden neue Eingaben abgewiesen.'); ?>
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 320px; margin-bottom: 0;">
                            <label for="current_pin" style="font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem; font-size:0.9rem;">
                                <?php echo __a('admin.security.current_pin_label', 'Aktuelle PIN'); ?> <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="current_pin" 
                                       name="current_pin" 
                                       class="form-control" 
                                       placeholder="<?php echo htmlspecialchars(__a('admin.security.current_pin_ph', 'Aktuell gültige PIN eingeben')); ?>" 
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
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;"><?php echo __a('admin.security.current_pin_hint', 'Zwingend erforderlich, um neues Passwort oder neue PIN zu aktivieren.'); ?></small>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label for="new_password"><?php echo __a('admin.security.new_password_label', 'Neues Passwort (optional)'); ?></label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       placeholder="<?php echo htmlspecialchars(__a('admin.security.new_password_ph', 'Neues Passwort')); ?>" 
                                       autocomplete="new-password" 
                                       data-lpignore="true" 
                                       value="" 
                                       readonly 
                                       onfocus="this.removeAttribute('readonly');" 
                                       style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_password')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;"><?php echo __a('admin.security.new_password_hint', 'Nur ausfüllen, wenn du ein neues Passwort setzen möchtest.'); ?></small>
                        </div>
                        <div class="form-group">
                            <label for="new_pin"><?php echo __a('admin.security.new_pin_label', 'Neuer PIN-Code (optional)'); ?></label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_pin" 
                                       name="new_pin" 
                                       class="form-control" 
                                       placeholder="<?php echo htmlspecialchars(__a('admin.security.new_pin_ph', 'z. B. 4-6 Ziffern')); ?>" 
                                       maxlength="10" 
                                       autocomplete="new-password" 
                                       data-lpignore="true" 
                                       value="" 
                                       readonly 
                                       onfocus="this.removeAttribute('readonly');" 
                                       style="padding-right:40px; letter-spacing:2px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_pin')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;"><?php echo __a('admin.security.new_pin_hint', 'Nur ausfüllen, wenn du eine neue PIN setzen möchtest.'); ?></small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:18px;">vpn_key</span> <?php echo __a('admin.security.btn_save_cred', 'Neue Zugangsdaten speichern'); ?>
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
                            <span class="material-icons" style="color:var(--primary);">storage</span> <?php echo __a('admin.security.storage_title', 'Speicher-Engine & 1-Klick-Daten-Migration'); ?>
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0;">
                            <?php echo __a('admin.security.storage_desc', 'Wahlfreiheit zwischen Flat-File JSON und relationaler MySQL/MariaDB-Datenbank mit nahtloser 1-Klick-Datenübertragung.'); ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($storage_details['ping_ok']): ?>
                            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700;">
                                <span class="material-icons" style="font-size:16px;">check_circle</span> <?php echo __a('admin.security.driver_active', '{name} (Aktiv & Bereit)', null, ['name' => htmlspecialchars($storage_details['name'])]); ?>
                            </span>
                        <?php else: ?>
                            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.3); border-radius:20px; padding:6px 14px; font-size:0.85rem; font-weight:700;">
                                <span class="material-icons" style="font-size:16px;">error</span> <?php echo __a('admin.security.driver_fault', 'Verbindung gestört'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Box Aktiver Treiber -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display:flex; align-items:center; gap:12px;">
                    <span class="material-icons" style="color:var(--primary); font-size:24px;">info</span>
                    <div style="font-size:0.88rem; color:var(--text-secondary);">
                        <strong><?php echo __a('admin.security.storage_active_loc', 'Aktiver Speicherort:'); ?></strong> <code style="color:var(--text-primary); font-weight:600;"><?php echo htmlspecialchars($storage_details['location']); ?></code>
                        <?php if ($storage_details['is_fallback']): ?>
                            <br><span style="color:#f59e0b; font-weight:600;"><?php echo __a('admin.security.fallback_warning', '⚠️ Notfall-Fallback aktiv: Die konfigurierte Datenbank war nicht erreichbar; WeaveCMS nutzt temporär die lokale data.json.'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$is_db_active): ?>
                    <!-- MIGRATION ZU MYSQL -->
                    <div style="background: rgba(46,98,72,0.04); border: 1px solid rgba(46,98,72,0.2); border-radius: 14px; padding: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">cloud_upload</span> <?php echo __a('admin.security.mig_to_mysql_title', 'Von Flat-File JSON zu MySQL / MariaDB migrieren'); ?>
                        </h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                            <?php echo __a('admin.security.mig_to_mysql_desc', 'Überträgt alle aktuellen Inhalte, Einstellungen und Termine in eine relationale MySQL-Datenbank. Vor der Migration wird automatisch ein Sicherheits-Backup deiner data.json angelegt.'); ?>
                        </p>

                        <form method="POST" action="" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.security.confirm_mig_mysql', 'Möchtest du alle Daten jetzt in die MySQL-Datenbank migrieren und MySQL aktivieren?'), ENT_QUOTES); ?>');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="migrate_storage_engine">
                            <input type="hidden" name="target_engine" value="mysql">

                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_host', 'Datenbank-Host'); ?> <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_host" name="db_host" class="form-control" value="127.0.0.1" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_port', 'Port'); ?></label>
                                    <input type="number" id="mig_db_port" name="db_port" class="form-control" value="3306" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_name', 'Datenbank-Name'); ?> <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_name" name="db_name" class="form-control" value="weavecms" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_user', 'Benutzername'); ?> <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="mig_db_user" name="db_user" class="form-control" value="root" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_pass', 'Passwort'); ?></label>
                                    <input type="password" id="mig_db_pass" name="db_pass" class="form-control" value="" placeholder="<?php echo htmlspecialchars(__a('admin.security.db_pass_ph', 'Falls vorhanden')); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.85rem;"><?php echo __a('admin.security.db_prefix', 'Tabellen-Präfix'); ?></label>
                                    <input type="text" id="mig_db_prefix" name="db_prefix" class="form-control" value="weave_">
                                </div>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.88rem; color:var(--text-secondary);">
                                    <input type="checkbox" id="mig_create_db" name="create_db" value="1" checked style="accent-color:var(--primary);">
                                    <?php echo __a('admin.security.create_db_label', 'Datenbank automatisch neu anlegen, falls noch nicht vorhanden'); ?>
                                </label>
                            </div>

                            <div style="display:flex; align-items:center; gap:12px; margin-bottom: 1.25rem; flex-wrap:wrap;">
                                <button type="button" class="btn btn-outline" id="mig_test_btn" onclick="testDbMigration();" style="display:inline-flex; align-items:center; gap:6px; font-size:0.88rem; padding:8px 14px;">
                                    <span class="material-icons" style="font-size:16px;">bolt</span> <?php echo __a('admin.security.btn_test_conn', 'Verbindung testen'); ?>
                                </button>
                                <span id="mig_test_feedback" style="display:none; font-size:0.85rem; font-weight:600; align-items:center; gap:5px;"></span>
                            </div>

                            <div style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display:flex; flex-direction:column; gap:0.75rem; max-width: 420px; margin-bottom: 1.25rem;">
                                <label style="font-size:0.85rem; font-weight:700;"><?php echo __a('admin.security.admin_pin_confirm', 'Administrator-PIN zur Bestätigung'); ?> <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="mig_pin_mysql" name="migrate_pin" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.security.enter_pin_ph', 'Aktuelle PIN eingeben')); ?>" required maxlength="10" style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('mig_pin_mysql')">visibility</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;">
                                <span class="material-icons" style="font-size:18px;">sync_alt</span> <?php echo __a('admin.security.btn_migrate_mysql', 'Zu MySQL migrieren & aktivieren'); ?>
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- MIGRATION ZU JSON -->
                    <div style="background: rgba(178,108,57,0.04); border: 1px solid rgba(178,108,57,0.25); border-radius: 14px; padding: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="color:var(--accent); font-size:20px;">folder</span> <?php echo __a('admin.security.mig_to_json_title', 'Zu Flat-File JSON (data.json) wechseln & exportieren'); ?>
                        </h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem;">
                            <?php echo __a('admin.security.mig_to_json_desc', 'Exportiert den gesamten aktuellen Datenbestand aus der MySQL-Datenbank vollständig in die lokale Datei data/data.json und entfernt die SQL-Konfiguration. WeaveCMS läuft danach wieder autark ohne Datenbank.'); ?>
                        </p>

                        <form method="POST" action="" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.security.confirm_mig_json', 'Möchtest du alle Inhalte aus MySQL in data.json sichern und auf Flat-File-Betrieb umschalten?'), ENT_QUOTES); ?>');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="migrate_storage_engine">
                            <input type="hidden" name="target_engine" value="json">

                            <div style="background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display:flex; flex-direction:column; gap:0.75rem; max-width: 420px; margin-bottom: 1.25rem;">
                                <label style="font-size:0.85rem; font-weight:700;"><?php echo __a('admin.security.admin_pin_confirm', 'Administrator-PIN zur Bestätigung'); ?> <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <input type="password" id="mig_pin_json" name="migrate_pin" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.security.enter_pin_ph', 'Aktuelle PIN eingeben')); ?>" required maxlength="10" style="padding-right:40px; letter-spacing:2px;">
                                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('mig_pin_json')">visibility</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;">
                                <span class="material-icons" style="font-size:18px;">download_for_offline</span> <?php echo __a('admin.security.btn_migrate_json', 'Zu Flat-File JSON migrieren & aktivieren'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
