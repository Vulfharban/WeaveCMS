<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Slide-in Contact Drawer
 * ============================================================================
 *
 * @var array $data
 * @var array $server_active_icons
 * @var array|null $initial_target
 * @var bool $smtp_active
 */
?>
    <!-- Slide-in Contact Menu (Right) -->
    <label for="menu-toggle" class="menu-overlay contact-overlay"></label>
    <div class="slide-menu contact-menu">
        <label for="menu-toggle" class="close-menu-btn">&times;</label>
        <h2 style="color: var(--primary); margin-bottom: 2rem;"><?php echo htmlspecialchars(__('contact.title', 'Kontakt & Kursanfrage')); ?></h2>
        <div style="display: flex; flex-direction: column; gap: 1.5rem; color: var(--text-secondary);">
            
            <p><?php echo htmlspecialchars($data['texts']['contact_intro_text'] ?? 'Hast du Fragen zu meinen Kursen, möchtest einen Gutschein erwerben oder ein individuelles Weidengeflecht anfragen?'); ?></p>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">person</span>
                    <?php echo htmlspecialchars(__('contact.owner', 'Inhaberin')); ?>
                </h4>
                <p><?php echo htmlspecialchars($data['texts']['contact_name'] ?? ''); ?></p>
                <p><?php echo htmlspecialchars($data['texts']['contact_street'] ?? ''); ?></p>
                <p><?php echo htmlspecialchars($data['texts']['contact_city'] ?? ''); ?></p>
            </div>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">phone</span>
                    <?php echo htmlspecialchars(__('contact.phone', 'Telefon')); ?>
                </h4>
                <p><a href="tel:<?php echo str_replace(' ', '', $data['texts']['contact_phone'] ?? ''); ?>" style="color: var(--text-primary);"><?php echo htmlspecialchars($data['texts']['contact_phone'] ?? ''); ?></a></p>
            </div>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">email</span>
                    <?php echo htmlspecialchars(__('contact.email', 'E-Mail')); ?>
                </h4>
                <p><a href="mailto:<?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?></a></p>
            </div>

            <?php if (!empty($data['texts']['contact_instagram'])): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                    <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons" style="color: #db2777;">photo_camera</span>
                        Instagram
                    </h4>
                    <p>
                        <a href="https://www.instagram.com/<?php echo urlencode($data['texts']['contact_instagram']); ?>/" target="_blank" style="color: #f472b6; font-weight: 600; text-decoration: underline;">
                            @<?php echo htmlspecialchars($data['texts']['contact_instagram']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Interactive Request form inside sidebar -->
            <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <h4 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo htmlspecialchars(__('contact.write_message', 'Nachricht schreiben')); ?></h4>
                <form onsubmit="sendRequestEmail(event, '<?php echo htmlspecialchars($data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de', ENT_QUOTES, 'UTF-8'); ?>')">
                    
                    <!-- Honeypot-Falle für Spam-Roboter (für menschliche Besucher unsichtbar) -->
                    <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <label for="req-website">Bitte dieses Feld freilassen</label>
                        <input type="text" id="req-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="req-name"><?php echo htmlspecialchars(__('contact.name', 'Dein Name')); ?> <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="req-name" class="form-control" style="padding:8px 12px;" placeholder="z. B. Maria Müller" required>
                    </div>

                    <div class="form-group">
                        <label for="req-email"><?php echo htmlspecialchars(__('contact.email_field', 'Deine E-Mail-Adresse')); ?> <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="req-email" class="form-control" style="padding:8px 12px;" placeholder="z. B. maria@beispiel.de" required>
                    </div>

                    <div class="form-group">
                        <label for="req-phone"><?php echo htmlspecialchars(__('contact.phone_field', 'Telefonnummer')); ?> <small style="color:var(--text-secondary);"><?php echo htmlspecialchars(__('contact.phone_hint', '(optional für Rückfragen)')); ?></small></label>
                        <input type="tel" id="req-phone" class="form-control" style="padding:8px 12px;" placeholder="z. B. 0173 1234567">
                    </div>

                    <div class="form-group">
                        <label for="req-msg"><?php echo htmlspecialchars(__('contact.message', 'Nachricht')); ?> <span style="color:#ef4444;">*</span></label>
                        <textarea id="req-msg" class="form-control" rows="5" style="padding:8px 12px;" placeholder="Deine Frage zu Kursen, Sonderanfertigungen oder Körben..." required></textarea>
                    </div>

                    <!-- Spamschutz: Natur- & Flecht-Icon-Auswahl (1 Klick) -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label id="spam-protection-label" style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 0.9rem;">
                            Sicherheitsprüfung: Klicke auf <strong><?php echo htmlspecialchars($initial_target['prompt'] ?? 'den Weidenzweig 🌿'); ?></strong>
                        </label>
                        <div id="spam-icon-grid" class="spam-icon-grid">
                            <?php foreach ($server_active_icons as $srv_icon): ?>
                                <button type="button" class="spam-icon-btn" title="<?php echo htmlspecialchars($srv_icon['name']); ?>" data-icon-id="<?php echo htmlspecialchars($srv_icon['id']); ?>" onclick="selectSpamIcon('<?php echo htmlspecialchars($srv_icon['id']); ?>', this);">
                                    <span class="spam-icon-symbol"><?php echo $srv_icon['symbol']; ?></span>
                                    <span class="spam-icon-label"><?php echo htmlspecialchars($srv_icon['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="req-selected-icon" value="">
                        <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                    </div>

                    <!-- Status-Meldungsbox für Feedback nach dem Senden -->
                    <div id="contact-status-box" style="display:none; margin-bottom: 1rem; padding: 12px 14px; border-radius: 10px; font-size: 0.88rem; line-height: 1.45;"></div>

                    <button type="submit" id="contact-submit-btn" class="btn btn-primary" style="width: 100%; padding: 11px; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                        <span class="material-icons" id="contact-submit-icon" style="font-size:18px;"><?php echo $smtp_active ? 'send' : 'mail'; ?></span>
                        <span id="contact-submit-text"><?php echo $smtp_active ? htmlspecialchars(__('btn.send_message', 'Anfrage senden')) : htmlspecialchars(__('btn.open_in_mail', 'Im Mailprogramm öffnen')); ?></span>
                    </button>
                    <?php if (!$smtp_active): ?>
                        <small style="color:var(--text-secondary); display:block; text-align:center; margin-top:6px; font-size:0.78rem;">
                            <?php echo htmlspecialchars(__('contact.mail_client_hint', 'Öffnet dein E-Mail-Programm mit der fertigen Nachricht.')); ?>
                        </small>
                    <?php endif; ?>
                </form>
            </div>

        </div>
    </div>
