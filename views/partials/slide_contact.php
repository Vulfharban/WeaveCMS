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
            
            <?php if (!empty(trim($data['texts']['contact_intro_text'] ?? ''))): ?>
            <p><?php echo htmlspecialchars($data['texts']['contact_intro_text']); ?></p>
            <?php endif; ?>

            <?php 
            $c_name = trim($data['texts']['contact_name'] ?? '');
            $c_street = trim($data['texts']['contact_street'] ?? '');
            $c_city = trim($data['texts']['contact_city'] ?? '');
            if ($c_name !== '' || $c_street !== '' || $c_city !== ''): 
            ?>
            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">person</span>
                    <?php echo htmlspecialchars(__('contact.owner', 'Inhaberin')); ?>
                </h4>
                <?php if ($c_name !== ''): ?><p><?php echo htmlspecialchars($c_name); ?></p><?php endif; ?>
                <?php if ($c_street !== ''): ?><p><?php echo htmlspecialchars($c_street); ?></p><?php endif; ?>
                <?php if ($c_city !== ''): ?><p><?php echo htmlspecialchars($c_city); ?></p><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php 
            $c_phone = trim($data['texts']['contact_phone'] ?? '');
            if ($c_phone !== ''): 
            ?>
            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">phone</span>
                    <?php echo htmlspecialchars(__('contact.phone', 'Telefon')); ?>
                </h4>
                <p><a href="tel:<?php echo str_replace(' ', '', $c_phone); ?>" style="color: var(--text-primary);"><?php echo htmlspecialchars($c_phone); ?></a></p>
            </div>
            <?php endif; ?>

            <?php 
            $c_email = trim($data['texts']['contact_email'] ?? '');
            if ($c_email !== ''): 
            ?>
            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">email</span>
                    <?php echo htmlspecialchars(__('contact.email', 'E-Mail')); ?>
                </h4>
                <p style="margin: 2px 0;">
                    <?php if (CaptchaManager::isContactEmailProtected($data)): ?>
                        <span id="contact-email-wrapper">
                            <button type="button" class="btn btn-outline" onclick="openContactEmailCaptcha()" style="padding: 2px 8px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;">
                                <span class="material-icons" style="font-size: 14px;">lock</span> <?php echo htmlspecialchars(__('contact.show_email', 'E-Mail anzeigen (Spamschutz)')); ?>
                            </button>
                        </span>
                        <div id="contact-email-captcha-box" style="display:none; margin-top:8px; padding:10px; background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:10px; max-width:280px;">
                            <div style="font-size:0.78rem; margin-bottom:4px; color:var(--text-secondary);"><?php echo htmlspecialchars(__('contact.enter_code', 'Bitte den 5-stelligen Code eingeben:')); ?></div>
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                <canvas id="contact-direct-captcha-canvas" width="140" height="38" style="background:#fff; border-radius:6px; border:1px solid var(--border-color);"></canvas>
                                <button type="button" class="btn btn-secondary" onclick="fetchContactCaptcha()" style="padding:4px 6px;" title="Neu laden">
                                    <span class="material-icons" style="font-size:15px;">refresh</span>
                                </button>
                            </div>
                            <div style="display:flex; gap:6px;">
                                <input type="text" id="contact-direct-captcha-input" maxlength="5" placeholder="<?php echo htmlspecialchars(__('contact.code_placeholder', 'Code...')); ?>" style="width:85px; padding:4px 8px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-color);">
                                <button type="button" class="btn btn-primary" onclick="verifyContactCaptcha()" style="padding:4px 10px; font-size:0.8rem;"><?php echo htmlspecialchars(__('contact.unlock', 'Freischalten')); ?></button>
                            </div>
                            <div id="contact-direct-captcha-feedback" style="display:none; color:#ef4444; font-size:0.75rem; margin-top:4px;"></div>
                        </div>
                    <?php else: ?>
                        <a href="mailto:<?php echo htmlspecialchars($c_email); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($c_email); ?></a>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

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
                <form onsubmit="sendRequestEmail(event, '<?php echo CaptchaManager::isContactEmailProtected($data) ? '' : htmlspecialchars($data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de', ENT_QUOTES, 'UTF-8'); ?>')">
                    
                    <!-- Honeypot-Falle für Spam-Roboter (für menschliche Besucher unsichtbar) -->
                    <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <label for="req-website"><?php echo htmlspecialchars(__('contact.leave_blank', 'Bitte dieses Feld freilassen')); ?></label>
                        <input type="text" id="req-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="req-name"><?php echo htmlspecialchars(__('contact.name', 'Dein Name')); ?> <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="req-name" class="form-control" style="padding:8px 12px;" placeholder="<?php echo htmlspecialchars(__('contact.placeholder_name', 'z. B. Maria Müller')); ?>" required>
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

                    <!-- Spamschutz: Modulares Captcha-Verfahren (Phase 16) -->
                    <?php 
                        $cur_mode = CaptchaManager::getMode($data);
                    ?>
                    <input type="hidden" id="req-captcha-mode" value="<?php echo htmlspecialchars($cur_mode); ?>">

                    <?php if ($cur_mode === CaptchaManager::MODE_ICONS): ?>
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label id="spam-protection-label" style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 0.9rem;">
                                <?php 
                                    $spam_label = __('contact.spam_label', 'Sicherheitsprüfung: Klicke auf {prompt}');
                                    $target_str = '<strong>' . htmlspecialchars($initial_target['prompt'] ?? 'den Weidenzweig 🌿') . '</strong>';
                                    echo str_replace('{prompt}', $target_str, htmlspecialchars($spam_label, ENT_NOQUOTES, 'UTF-8'));
                                ?>
                            </label>
                            <div id="spam-icon-grid" class="spam-icon-grid">
                                <?php foreach ($server_active_icons as $srv_key => $srv_icon): 
                                    $srv_id = $srv_icon['id'] ?? $srv_key;
                                ?>
                                    <button type="button" class="spam-icon-btn" title="<?php echo htmlspecialchars($srv_icon['name']); ?>" data-icon-id="<?php echo htmlspecialchars($srv_id); ?>" onclick="selectSpamIcon('<?php echo htmlspecialchars($srv_id); ?>', this);">
                                        <span class="spam-icon-symbol"><?php echo $srv_icon['symbol']; ?></span>
                                        <span class="spam-icon-label"><?php echo htmlspecialchars($srv_icon['name']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="req-selected-icon" value="">
                            <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                        </div>

                    <?php elseif ($cur_mode === CaptchaManager::MODE_MATH): ?>
                        <?php $mathChallenge = CaptchaManager::generateMathChallenge($cur_lang ?? null); ?>
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="req-math-answer" style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 0.9rem;">
                                Sicherheitsprüfung: <strong><?php echo htmlspecialchars($mathChallenge['question']); ?></strong> <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="number" id="req-math-answer" name="captcha_math_answer" class="form-control" placeholder="Ergebnis..." required style="max-width: 140px; padding: 8px 12px;">
                            <input type="hidden" id="req-math-hash" value="<?php echo htmlspecialchars($mathChallenge['hash']); ?>">
                            <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                        </div>

                    <?php elseif ($cur_mode === CaptchaManager::MODE_CANVAS): ?>
                        <?php $canvasChallenge = CaptchaManager::generateCanvasChallenge('contact'); ?>
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 0.9rem;">
                                Sicherheitsprüfung: Code eingeben <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <canvas id="contact-captcha-canvas" width="160" height="42" data-code="<?php echo htmlspecialchars($canvasChallenge['code']); ?>" style="background:#fff; border-radius:8px; border:1px solid var(--border-color);"></canvas>
                                <button type="button" class="btn btn-secondary" onclick="renderContactCanvasCaptcha(true)" style="padding:6px 10px;" title="Neuen Code generieren">
                                    <span class="material-icons" style="font-size:16px;">refresh</span>
                                </button>
                            </div>
                            <input type="text" id="req-canvas-answer" name="captcha_canvas_answer" class="form-control" maxlength="5" placeholder="5-stelliger Code" required style="max-width: 160px; padding: 8px 12px; font-weight:700; letter-spacing:2px;">
                            <input type="hidden" id="req-canvas-hash" value="<?php echo htmlspecialchars($canvasChallenge['hash']); ?>">
                            <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                        </div>

                    <?php elseif ($cur_mode === CaptchaManager::MODE_HONEYPOT): ?>
                        <!-- Stille Sicherheitsprüfung (Honeypot + Time-Trap aktiv) -->
                        <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                    <?php endif; ?>

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
