<?php
/**
 * Weave Helpdesk – Admin Settings View (für WeaveCMS Backend)
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

$cfg = HelpdeskManager::getConfig();
$provider = HelpdeskManager::getServiceProviderDetails();
$cmsData = $GLOBALS['data'] ?? [];
$cmsTexts = $cmsData['texts'] ?? [];
$adminAlertMsg = $_SESSION['hd_admin_alert_msg'] ?? null;
unset($_SESSION['hd_admin_alert_msg']);
?>

<div class="plugin-settings-wrapper" style="max-width: 900px; margin: 0 auto; padding: 15px;">
    <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'settings_saved'): ?>
        <div style="background: #f0fdf4; border-left: 4px solid #16a34a; color: #166534; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-weight: 600;">
            ✅ Die Helpdesk-Einstellungen wurden erfolgreich gespeichert.
        </div>
    <?php endif; ?>
    <?php if (is_array($adminAlertMsg) && !empty($adminAlertMsg['text'])): ?>
        <div style="background: <?= !empty($adminAlertMsg['ok']) ? '#f0fdf4' : '#fef2f2' ?>; border-left: 4px solid <?= !empty($adminAlertMsg['ok']) ? '#16a34a' : '#dc2626' ?>; color: <?= !empty($adminAlertMsg['ok']) ? '#166534' : '#991b1b' ?>; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-weight: 600;">
            <?= htmlspecialchars((string)$adminAlertMsg['text']) ?>
        </div>
    <?php endif; ?>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #257985; padding-bottom: 12px;">
        <div>
            <h2 style="margin: 0; color: #257985; font-size: 22px;">🛡️ Weave Helpdesk – Konfiguration</h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Verwalten Sie Dienstleister-Stammdaten, Registrierungsregeln, Inklusivminuten und Benachrichtigungen.</p>
        </div>
        <a href="../?page=helpdesk" target="_blank" class="btn btn-primary" style="background: #257985; border: none; padding: 8px 14px; text-decoration: none; color: #fff; border-radius: 4px; font-weight: 600;">
            🚀 Helpdesk-Portal öffnen
        </a>
    </div>

    <form action="?tab=tab-plugins&plugin_settings=weave-helpdesk&subaction=save_settings#plugin-settings-drawer-weave-helpdesk" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="helpdesk_save_settings">

        <!-- 1. Dienstleister / Systemhaus-Stammdaten -->
        <div class="card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">🏢 Dienstleister-Firmierung (Auftragnehmer)</h4>
            <p style="font-size: 12px; color: #64748b;">
                Diese Angaben erscheinen auf allen Tickets im Feld <strong>"An Dienstleister *"</strong>. Bleiben die Felder leer, werden automatisch die globalen Kontaktdaten aus den CMS-Einstellungen (<em>Impressum/Kontakt</em>) herangezogen.
            </p>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 600; display: block; margin-bottom: 4px;">Dienstleister Firmenname</label>
                <input type="text" name="service_provider_name" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars($cfg['service_provider_name'] ?? '') ?>" placeholder="CMS-Standard: <?= htmlspecialchars($cmsTexts['contact_name'] ?? 'Mein Unternehmen') ?>">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 600; display: block; margin-bottom: 4px;">Dienstleister Anschrift</label>
                <input type="text" name="service_provider_address" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars($cfg['service_provider_address'] ?? '') ?>" placeholder="CMS-Standard: <?= htmlspecialchars(($cmsTexts['contact_street'] ?? '') . ', ' . ($cmsTexts['contact_city'] ?? '')) ?>">
            </div>

            <div class="form-row" style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Support-Telefonnummer</label>
                    <input type="text" name="service_provider_phone" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars($cfg['service_provider_phone'] ?? '') ?>" placeholder="CMS-Standard: <?= htmlspecialchars($cmsTexts['contact_phone'] ?? '') ?>">
                </div>
                <div style="flex: 1;">
                    <label style="font-weight: 600; display: block; margin-bottom: 4px;">Support E-Mail-Adresse</label>
                    <input type="email" name="service_provider_email" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" value="<?= htmlspecialchars($cfg['service_provider_email'] ?? '') ?>" placeholder="CMS-Standard: <?= htmlspecialchars($cmsTexts['contact_email'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- 2. Zugangs- & Registrierungsregeln -->
        <div class="card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">🔐 Benutzer-Zugang & Registrierung</h4>
            
            <div style="margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="show_in_main_nav" value="1" <?= !empty($cfg['show_in_main_nav']) ? 'checked' : '' ?>>
                    <strong>Im Hauptmenü der Website anzeigen</strong>
                </label>
                <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                    Legt fest, ob der Menüpunkt „Helpdesk“ in der Hauptnavigation der Website sichtbar ist. Ist die Option deaktiviert, bleibt das Ticketsystem über die Einzelseite (<code>?page=helpdesk</code>) erreichbar, erscheint jedoch nicht im Hauptmenü.
                </p>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="allow_self_registration" value="1" <?= !empty($cfg['allow_self_registration']) ? 'checked' : '' ?>>
                    <strong>Selbstregistrierung für Kunden im Frontend erlauben</strong>
                </label>
                <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                    Ist dieser Haken deaktiviert, können sich Kunden nicht eigenständig registrieren. Benutzerkonten müssen dann vorab manuell durch Administratoren im Reiter <em>"Benutzerverwaltung"</em> angelegt werden.
                </p>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="enable_contracts" value="1" <?= !empty($cfg['enable_contracts']) ? 'checked' : '' ?>>
                    <strong>Vertragsverwaltung & Inklusivminuten-Runterrechnung aktivieren</strong>
                </label>
                <p style="margin: 4px 0 0 25px; font-size: 12px; color: #64748b;">
                    Aktiviert die Erfassung von Minuten bei Tätigkeiten sowie die Anzeige von Inklusivminuten-Restguthaben für Kunden mit Serviceverträgen.
                </p>
            </div>
        </div>

        <!-- 3. E-Mail- & Rückkanal-Einstellungen -->
        <div class="card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">✉️ E-Mail-Benachrichtigungen & Rückkanal</h4>

            <div style="margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="enable_email_notifications" value="1" <?= !empty($cfg['enable_email_notifications']) ? 'checked' : '' ?>>
                    <strong>Automatische E-Mail-Benachrichtigungen bei Aufgaben & Tätigkeiten aktivieren</strong>
                </label>
            </div>

            <div style="margin-top: 16px; padding-top: 14px; border-top: 1px dashed #e2e8f0;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="enable_imap_fetcher" value="1" <?= !empty($cfg['enable_imap_fetcher']) ? 'checked' : '' ?>>
                    <strong>E-Mail-Rückkanal (IMAP-Ticket-Import) aktivieren</strong>
                </label>
                <p style="margin: 4px 0 10px 25px; font-size: 12px; color: #64748b;">
                    Erlaubt es Kunden, direkt per E-Mail auf Benachrichtigungen zu antworten. Die Antwort wird automatisch als Tätigkeit zum Ticket hinzugefügt.
                </p>

                <div class="form-row" style="display: flex; gap: 15px; margin-top: 10px;">
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 600;">IMAP Server (Host)</label>
                        <input type="text" name="imap_host" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['imap_host'] ?? '') ?>" placeholder="imap.domain.de">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 11px; font-weight: 600;">Port</label>
                        <input type="number" name="imap_port" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= (int)($cfg['imap_port'] ?? 993) ?>">
                    </div>
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 600;">Benutzer / E-Mail</label>
                        <input type="text" name="imap_user" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['imap_user'] ?? '') ?>" placeholder="support@domain.de">
                    </div>
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 600;">Passwort</label>
                        <input type="password" name="imap_pass" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="" placeholder="<?= !empty($cfg['imap_pass']) ? '•••••• (gespeichert)' : '••••••••' ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Sofort-Alarmierung bei Priorität "Kritisch" (E-Mail, SMS & WhatsApp) -->
        <div class="card" style="background: #fff; border: 1px solid #fecaca; border-left: 4px solid #dc2626; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #991b1b; border-bottom: 1px solid #fee2e2; padding-bottom: 8px;">
                🚨 Sofort-Alarmierung bei Priorität „Kritisch“ (E-Mail, SMS &amp; WhatsApp)
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-top: 0;">
                Wird ein Ticket mit Priorität <strong>Kritisch</strong> erstellt, können automatisch dringende E-Mails sowie SMS- oder WhatsApp-Benachrichtigungen an Ihre Bereitschaftsnummern ausgelöst werden.
            </p>

            <div style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; color: #0f172a;">
                    <input type="checkbox" name="critical_alerts_enabled" value="1" <?= !empty($cfg['critical_alerts_enabled']) ? 'checked' : '' ?>>
                    <span>Sofort-Alarmierung bei Priorität „Kritisch“ aktivieren</span>
                </label>
            </div>

            <!-- 4a. Dringende E-Mails -->
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px; margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; color: #991b1b; margin-bottom: 8px;">
                    <input type="checkbox" name="critical_email_enabled" value="1" <?= !empty($cfg['critical_email_enabled']) ? 'checked' : '' ?>>
                    <span>📧 Dringende Alarm-E-Mails versenden</span>
                </label>
                <label style="display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px;">
                    Empfänger-E-Mail-Adressen (komma- oder zeilengetrennt; leer = Support-E-Mail aus Abschnitt 1)
                </label>
                <textarea name="critical_emails" rows="2" class="form-control" style="width: 100%; padding: 6px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="bereitschaft@firma.de, leitung@firma.de"><?= htmlspecialchars($cfg['critical_emails'] ?? '') ?></textarea>
            </div>

            <!-- 4b. SMS-Alarmierung -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; color: #1e293b; margin-bottom: 10px;">
                    <input type="checkbox" name="critical_sms_enabled" value="1" <?= !empty($cfg['critical_sms_enabled']) ? 'checked' : '' ?>>
                    <span>📱 SMS-Alarmierung an Handynummern aktivieren</span>
                </label>

                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">SMS-Anbieter / Gateway</label>
                        <select name="critical_sms_provider" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;">
                            <option value="webhook" <?= (($cfg['critical_sms_provider'] ?? 'webhook') === 'webhook') ? 'selected' : '' ?>>Eigener Webhook / HTTP-API</option>
                            <option value="twilio" <?= (($cfg['critical_sms_provider'] ?? '') === 'twilio') ? 'selected' : '' ?>>Twilio SMS API</option>
                            <option value="seven" <?= (($cfg['critical_sms_provider'] ?? '') === 'seven') ? 'selected' : '' ?>>seven.io (sms77)</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Absenderkennung / Rufnummer</label>
                        <input type="text" name="critical_sms_from" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_sms_from'] ?? 'Helpdesk') ?>" placeholder="Helpdesk oder +49...">
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px; font-weight: 600;">Ziel-Handynummern (internationales Format, komma- oder zeilengetrennt)</label>
                    <input type="text" name="critical_sms_numbers" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_sms_numbers'] ?? '') ?>" placeholder="+491711234567, +491729876543">
                </div>

                <div class="form-row" style="display: flex; gap: 12px;">
                    <div style="flex: 2;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Webhook-URL (nur bei Anbieter „Eigener Webhook“)</label>
                        <input type="url" name="critical_sms_api_url" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_sms_api_url'] ?? '') ?>" placeholder="https://gateway.example.org/sms">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Account SID (nur Twilio)</label>
                        <input type="text" name="critical_sms_account_sid" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_sms_account_sid'] ?? '') ?>" placeholder="AC...">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">API-Key / Auth-Token</label>
                        <input type="password" name="critical_sms_api_key" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="" placeholder="<?= !empty($cfg['critical_sms_api_key']) ? '•••••• (gespeichert)' : 'Token / API-Key' ?>">
                    </div>
                </div>
            </div>

            <!-- 4c. WhatsApp-Alarmierung -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; color: #166534; margin-bottom: 10px;">
                    <input type="checkbox" name="critical_wa_enabled" value="1" <?= !empty($cfg['critical_wa_enabled']) ? 'checked' : '' ?>>
                    <span>💬 WhatsApp-Nachrichten bei „Kritisch“ aktivieren</span>
                </label>

                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">WhatsApp-Anbieter / API</label>
                        <select name="critical_wa_provider" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;">
                            <option value="webhook" <?= (($cfg['critical_wa_provider'] ?? 'webhook') === 'webhook') ? 'selected' : '' ?>>Webhook / n8n / WAHA / Evolution API</option>
                            <option value="meta_cloud" <?= (($cfg['critical_wa_provider'] ?? '') === 'meta_cloud') ? 'selected' : '' ?>>WhatsApp Cloud API (Meta)</option>
                            <option value="twilio_wa" <?= (($cfg['critical_wa_provider'] ?? '') === 'twilio_wa') ? 'selected' : '' ?>>Twilio WhatsApp API</option>
                            <option value="callmebot" <?= (($cfg['critical_wa_provider'] ?? '') === 'callmebot') ? 'selected' : '' ?>>CallMeBot WhatsApp API</option>
                        </select>
                    </div>
                    <div style="flex: 2;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Ziel-WhatsApp-Nummern (komma- oder zeilengetrennt)</label>
                        <input type="text" name="critical_wa_numbers" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_wa_numbers'] ?? '') ?>" placeholder="+491711234567, +491729876543">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 12px;">
                    <div style="flex: 1.5;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Webhook-URL (bei Webhook-Modus)</label>
                        <input type="url" name="critical_wa_api_url" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_wa_api_url'] ?? '') ?>" placeholder="https://n8n.example.org/webhook/wa">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Phone-Number-ID / Twilio SID</label>
                        <input type="text" name="critical_wa_phone_id" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_wa_phone_id'] ?? '') ?>" placeholder="Meta Phone-ID / AC...">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Absender-WA (nur Twilio WA)</label>
                        <input type="text" name="critical_wa_from" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['critical_wa_from'] ?? '') ?>" placeholder="+14155238886">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600;">Bearer Token / API-Key</label>
                        <input type="password" name="critical_wa_token" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="" placeholder="<?= !empty($cfg['critical_wa_token']) ? '•••••• (gespeichert)' : 'Token / API-Key' ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success" style="background: #257985; border: none; padding: 10px 20px; font-size: 14px; color: #fff; font-weight: 700; border-radius: 4px; cursor: pointer;">
            💾 Helpdesk-Einstellungen speichern
        </button>
    </form>

    <form action="?tab=tab-plugins&plugin_settings=weave-helpdesk&subaction=test_alert#plugin-settings-drawer-weave-helpdesk" method="POST" style="margin-top: 12px;" onsubmit="return confirm('Test-Alarm über alle aktuell gespeicherten Kritisch-Kanäle (E-Mail / SMS / WhatsApp) senden?');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="helpdesk_test_critical_alert">
        <button type="submit" class="btn btn-outline-danger" style="padding: 8px 16px; font-size: 13px; border: 1px solid #dc2626; color: #dc2626; background: #fff; border-radius: 4px; font-weight: 600; cursor: pointer;">
            🧪 Test-Alarm (Kritisch) mit gespeicherten Einstellungen senden
        </button>
    </form>
</div>

