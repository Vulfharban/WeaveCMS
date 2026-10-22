<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Slide-in Impressum & Datenschutz
 * ============================================================================
 *
 * @var array $data
 * @var string $cur_privacy_text
 */
?>
    <!-- Slide-in Impressum & Datenschutz Menu (Right) -->
    <label for="menu-toggle-impressum" class="menu-overlay impressum-overlay"></label>
    <div class="slide-menu impressum-menu" id="legal-slide-menu">
        <label for="menu-toggle-impressum" class="close-menu-btn">&times;</label>
        
        <!-- Tab-Umschalter für Rechtliches: Impressum & Datenschutz -->
        <div class="legal-tab-nav" style="display:flex; gap:8px; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1rem; flex-wrap:wrap;">
            <button type="button" id="legal-tab-btn-impressum" class="btn btn-primary" style="padding:6px 14px; font-size:0.85rem;" onclick="switchLegalTab('impressum')">
                <?php echo htmlspecialchars(__('legal.impressum_tab', 'Impressum (§ 5 TMG)')); ?>
            </button>
            <button type="button" id="legal-tab-btn-datenschutz" class="btn btn-outline" style="padding:6px 14px; font-size:0.85rem;" onclick="switchLegalTab('datenschutz')">
                <?php echo htmlspecialchars(__('legal.privacy_tab', 'Datenschutz (DSGVO)')); ?>
            </button>
        </div>

        <!-- Inhaltsbereich: Impressum -->
        <div id="legal-panel-impressum" style="font-size: 0.9rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 1.5rem;">
            <h2 style="color: var(--primary); margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php echo htmlspecialchars(__('nav.impressum', 'Impressum')); ?></h2>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars(__('legal.tmg_notice', 'Angaben gemäß § 5 TMG')); ?></h4>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_name'] ?? ''); ?></p>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_street'] ?? ''); ?></p>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_city'] ?? ''); ?></p>
            </div>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars(__('legal.contact_headline', 'Kontakt')); ?></h4>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars(__('contact.phone', 'Telefon')); ?>: <?php echo htmlspecialchars($data['texts']['contact_phone'] ?? ''); ?></p>
                <p style="margin: 2px 0;">
                    <?php echo htmlspecialchars(__('contact.email', 'E-Mail')); ?>: 
                    <?php if (CaptchaManager::isImpressumEmailProtected($data)): ?>
                        <span id="impressum-email-wrapper">
                            <button type="button" class="btn btn-outline" onclick="openImpressumEmailCaptcha()" style="padding: 2px 8px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;">
                                <span class="material-icons" style="font-size: 14px;">lock</span> E-Mail anzeigen (Spamschutz)
                            </button>
                        </span>
                        <div id="impressum-email-captcha-box" style="display:none; margin-top:8px; padding:10px; background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:10px; max-width:280px;">
                            <div style="font-size:0.78rem; margin-bottom:4px; color:var(--text-secondary);">Bitte den 5-stelligen Code eingeben:</div>
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                <canvas id="impressum-captcha-canvas" width="140" height="38" style="background:#fff; border-radius:6px; border:1px solid var(--border-color);"></canvas>
                                <button type="button" class="btn btn-secondary" onclick="fetchImpressumCaptcha()" style="padding:4px 6px;" title="Neu laden">
                                    <span class="material-icons" style="font-size:15px;">refresh</span>
                                </button>
                            </div>
                            <div style="display:flex; gap:6px;">
                                <input type="text" id="impressum-captcha-input" maxlength="5" placeholder="Code..." style="width:85px; padding:4px 8px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-color);">
                                <button type="button" class="btn btn-primary" onclick="verifyImpressumCaptcha()" style="padding:4px 10px; font-size:0.8rem;">Freischalten</button>
                            </div>
                            <div id="impressum-captcha-feedback" style="display:none; color:#ef4444; font-size:0.75rem; margin-top:4px;"></div>
                        </div>
                    <?php else: ?>
                        <?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (!empty($data['texts']['impressum_supervisory'])): ?>
                <div>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars(__('legal.supervisory', 'Zuständige Aufsichtsbehörde / Kammer')); ?></h4>
                    <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_supervisory']); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($data['texts']['impressum_vat_id'])): ?>
                <div>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars(__('legal.vat_id', 'Umsatzsteuer / Steuerhinweis')); ?></h4>
                    <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_vat_id']); ?></p>
                </div>
            <?php endif; ?>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars(__('legal.disclaimer', 'Haftungsausschluss')); ?></h4>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_disclaimer'] ?? 'Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.'); ?></p>
            </div>
        </div>

        <!-- Inhaltsbereich: Datenschutzerklärung -->
        <div id="legal-panel-datenschutz" style="display:none; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
            <h2 style="color: var(--primary); margin: 0 0 1rem 0; font-size: 1.5rem;"><?php echo htmlspecialchars(__('legal.privacy_tab', 'Datenschutzerklärung (DSGVO)')); ?></h2>
            <div class="privacy-content" style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <?php 
                    echo MarkdownParser::parse($cur_privacy_text);
                ?>
            </div>
        </div>
    </div>
