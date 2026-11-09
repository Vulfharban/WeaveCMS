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
?>

<div class="plugin-settings-wrapper" style="max-width: 900px; margin: 0 auto; padding: 15px;">
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
                        <input type="password" name="imap_pass" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($cfg['imap_pass'] ?? '') ?>" placeholder="••••••••">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success" style="background: #257985; border: none; padding: 10px 20px; font-size: 14px; color: #fff; font-weight: 700; border-radius: 4px; cursor: pointer;">
            💾 Helpdesk-Einstellungen speichern
        </button>
    </form>
</div>

