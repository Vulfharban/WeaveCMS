<?php
/**
 * WeaveCMS – Admin Tab: Impressum, Kontaktdaten & DSGVO-Datenschutzerklärung
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION 2: IMPRESSUM & CONTACT DATA ──────────────── -->
        <div id="tab-impressum" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">📋 Impressum, Rechtliches &amp; Datenschutz verwalten</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem;">
                Hier kannst du alle Adress-, Kontakt-, Impressums- und DSGVO-Datenschutzdaten anpassen. Diese werden automatisch im Ausklappmenü (Kontakt &amp; Impressum/Datenschutz) sowie im Fußbereich der Website angezeigt.
            </p>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_impressum">
                
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    👤 Betreiber &amp; Kontaktdaten
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Vollständiger Name / Inhaberin</label>
                        <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Straße &amp; Hausnummer</label>
                        <input type="text" name="contact_street" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_street'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>PLZ &amp; Ort</label>
                        <input type="text" name="contact_city" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_city'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefonnummer</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_phone'] ?? ''); ?>" placeholder="+49 173 ...">
                    </div>
                    <div class="form-group">
                        <label>E-Mail-Adresse</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Instagram-Account Name</label>
                        <input type="text" name="contact_instagram" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_instagram'] ?? ''); ?>" placeholder="z. B. maxima.musterfrau">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    ⚖️ Rechtliche Angaben (§ 5 TMG Impressum)
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Zuständige Aufsichtsbehörde / Kammer</label>
                        <input type="text" name="impressum_supervisory" class="form-control" value="<?php echo htmlspecialchars($data['texts']['impressum_supervisory'] ?? 'Handwerkskammer Schwerin'); ?>" placeholder="z. B. Handwerkskammer Schwerin">
                    </div>
                    <div class="form-group">
                        <label>Umsatzsteuer-ID / Steuerhinweis</label>
                        <input type="text" name="impressum_vat_id" class="form-control" value="<?php echo htmlspecialchars($data['texts']['impressum_vat_id'] ?? 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).'); ?>" placeholder="z. B. Kleinunternehmer gem. § 19 UStG">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Haftungsausschluss / Rechtliche Hinweise</label>
                    <textarea name="impressum_disclaimer" class="form-control" rows="4"><?php echo htmlspecialchars($data['texts']['impressum_disclaimer'] ?? 'Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.'); ?></textarea>
                </div>

                <!-- ──────────────── DSGVO DATENSCHUTZERKLÄRUNG ──────────────── -->
                <div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                        <div>
                            <h3 style="font-size: 1.2rem; margin:0; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                                <span class="material-icons" style="color:var(--primary); font-size:22px;">shield</span>
                                Datenschutzerklärung (DSGVO Art. 13)
                            </h3>
                            <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 4px 0 0 0;">
                                Vollständig abmahnsicherer Text für deine Website. Unterstützt übersichtliche Markdown-Formatierung (z. B. <code>### Überschrift</code>, <code>**Fett**</code>, <code>- Listenpunkt</code>).
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.82rem; display:inline-flex; align-items:center; gap:6px;" onclick="loadDefaultPrivacyPolicy();" title="Ersetzt den Text durch die rechtssichere Standard-DSGVO-Vorlage">
                            <span class="material-icons" style="font-size:16px;">restore</span> Standard-DSGVO-Vorlage einfügen
                        </button>
                    </div>

                    <?php 
                        $cur_privacy = !empty($data['settings']['privacy_policy_text']) 
                            ? $data['settings']['privacy_policy_text'] 
                            : (!empty($data['texts']['privacy_policy_text']) ? $data['texts']['privacy_policy_text'] : get_default_privacy_policy_text($data));
                    ?>
                    <div class="form-group" style="margin-bottom: 0;">
                        <textarea id="privacy_policy_text" name="privacy_policy_text" class="form-control" rows="12" style="font-family: monospace, sans-serif; font-size: 0.88rem; line-height: 1.55; white-space: pre-wrap;"><?php echo htmlspecialchars($cur_privacy); ?></textarea>
                        <small style="color:var(--text-secondary); font-size:0.8rem; margin-top:6px; display:block;">
                            Wird im Slide-In-Menü "Impressum &amp; Datenschutz" dynamisch und sicher formatiert ausgegeben.
                        </small>
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    ✉️ E-Mail-Texte &amp; Vorlagen
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height: 1.4;">
                    Passe hier die vorgefertigten Texte an, die im Kontaktformular und bei Kursanfragen generiert werden.
                </p>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>E-Mail-Anrede / Begrüßung (z. B. "Hallo Maxima,")</label>
                    <input type="text" name="contact_mail_greeting" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_mail_greeting'] ?? 'Hallo Maxima,'); ?>" placeholder="z. B. Hallo Maxima,">
                    <small style="color:var(--text-secondary); font-size:0.8rem; margin-top:4px; display:block;">
                        Wird als persönliche Anrede an den Anfang der generierten Nachricht gesetzt.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Textvorlage für Kursanfragen (beim Klick auf "Kurs anfragen")</label>
                    <textarea name="course_request_template" class="form-control" rows="4"><?php echo htmlspecialchars($data['texts']['course_request_template'] ?? "Hallo Maxima,\n\nich interessiere mich für deinen Kurs \"{kurs}\" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\n\nViele Grüße"); ?></textarea>
                    <small style="color:var(--text-secondary); font-size:0.8rem; margin-top:4px; display:block;">
                        Verfügbare Platzhalter: <code>{kurs}</code>, <code>{datum}</code>, <code>{uhrzeit}</code>, <code>{ort}</code>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Impressum, Kontaktdaten &amp; Datenschutzerklärung speichern
                </button>
            </form>
        </div>
