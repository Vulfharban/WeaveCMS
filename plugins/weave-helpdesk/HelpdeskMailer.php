<?php
/**
 * ============================================================================
 * Weave Helpdesk – HelpdeskMailer
 * ============================================================================
 * Versendet E-Mail-Benachrichtigungen bei Ticketerstellung, neuen Tätigkeiten,
 * Antworten und Statuswechseln über die WeaveCMS-Kernklasse SimpleMailer.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class HelpdeskMailer {

    /**
     * Ermittelt die SMTP-Konfiguration aus WeaveCMS.
     */
    protected static function getSmtpConfig(): ?array {
        $cmsData = $GLOBALS['data'] ?? [];
        if (empty($cmsData) && function_exists('load_data')) {
            $cmsData = load_data(dirname(__DIR__, 2) . '/data.json');
        }
        return $cmsData['settings']['smtp'] ?? null;
    }

    /**
     * Prüft, ob der E-Mail-Versand im Helpdesk aktiviert ist.
     */
    public static function isEnabled(): bool {
        $cfg = HelpdeskManager::getConfig();
        return !empty($cfg['enable_email_notifications']);
    }

    /**
     * Sendet eine Benachrichtigung über eine neu erstellte Aufgabe.
     * @param array $ticket
     * @param array $creator
     * @return void
     */
    public static function sendTicketCreatedNotification(array $ticket, array $creator): void {
        if (!self::isEnabled()) {
            return;
        }

        require_once dirname(__DIR__, 2) . '/libs/php/Mailer.php';
        $provider = HelpdeskManager::getServiceProviderDetails();
        $smtp = self::getSmtpConfig();

        $ticketId = $ticket['id'];
        $subject = "[Aufgabe: {$ticketId}] {$ticket['subject']}";
        $providerName = $provider['name'];

        // 1. E-Mail an den Kunden / Ersteller
        $customerEmail = $ticket['created_by_email'] ?? '';
        if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $html = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                    <div style='background: #257985; color: #fff; padding: 18px 24px;'>
                        <h2 style='margin: 0; font-size: 20px;'>Ihre Service-Aufgabe wurde empfangen</h2>
                    </div>
                    <div style='padding: 24px;'>
                        <p>Sehr geehrte(r) <strong>" . htmlspecialchars($ticket['contact_person']) . "</strong>,</p>
                        <p>vielen Dank für Ihre Anfrage. Ihre Aufgabe wurde unter folgender Referenz erfasst:</p>
                        <div style='background: #f4f8f8; border-left: 4px solid #257985; padding: 12px 16px; margin: 16px 0;'>
                            <strong style='font-size: 16px; color: #257985;'>Aufgabe: #{$ticketId}</strong><br>
                            <strong>Betreff:</strong> " . htmlspecialchars($ticket['subject']) . "<br>
                            <strong>Typ:</strong> " . htmlspecialchars($ticket['type']) . "<br>
                            <strong>Priorität:</strong> " . htmlspecialchars($ticket['priority']) . "<br>
                            <strong>Status:</strong> " . htmlspecialchars($ticket['status']) . "
                        </div>
                        <p>Unser Support-Team wird Ihre Anfrage schnellstmöglich bearbeiten. Sie können den Status jederzeit in unserem Kundenportal einsehen.</p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 13px; color: #777;'>{$providerName}<br>" . nl2br(htmlspecialchars($provider['address'])) . "</p>
                    </div>
                </div>
            ";
            $text = "Ihre Aufgabe #{$ticketId} ({$ticket['subject']}) wurde erfasst. Status: {$ticket['status']}.";

            SimpleMailer::send(
                $customerEmail,
                $subject,
                $html,
                $text,
                $provider['email'],
                $providerName,
                $smtp
            );
        }

        // 2. Benachrichtigung an den Dienstleister / Admin
        $providerEmail = $provider['email'];
        if (!empty($providerEmail) && filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
            $adminSubject = "[Neue Aufgabe #{$ticketId}] " . ($ticket['company_name'] ? "{$ticket['company_name']} - " : "") . $ticket['subject'];
            $adminHtml = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; padding: 20px; border: 1px solid #e0e0e0;'>
                    <h3 style='color: #257985; margin-top: 0;'>Neue Support-Aufgabe #{$ticketId} eingegangen</h3>
                    <p><strong>Unternehmen:</strong> " . htmlspecialchars($ticket['company_name']) . "<br>
                    <strong>Kontaktperson:</strong> " . htmlspecialchars($ticket['contact_person']) . " (" . htmlspecialchars($ticket['alt_phone']) . ")<br>
                    <strong>Betreff:</strong> " . htmlspecialchars($ticket['subject']) . "<br>
                    <strong>Priorität:</strong> " . htmlspecialchars($ticket['priority']) . "</p>
                    <div style='background: #f9f9f9; padding: 12px; border-radius: 4px; margin: 15px 0;'>
                        " . nl2br(htmlspecialchars($ticket['content'])) . "
                    </div>
                </div>
            ";
            $adminText = "Neue Aufgabe #{$ticketId}: {$ticket['subject']} von {$ticket['company_name']}.";

            SimpleMailer::send(
                $providerEmail,
                $adminSubject,
                $adminHtml,
                $adminText,
                $customerEmail,
                $ticket['contact_person'],
                $smtp
            );
        }
    }

    /**
     * Sendet eine Benachrichtigung über eine neue Tätigkeit oder Antwort.
     * @param array $ticket
     * @param array $activity
     * @param array $author
     * @return void
     */
    public static function sendActivityNotification(array $ticket, array $activity, array $author): void {
        if (!self::isEnabled()) {
            return;
        }

        require_once dirname(__DIR__, 2) . '/libs/php/Mailer.php';
        $provider = HelpdeskManager::getServiceProviderDetails();
        $smtp = self::getSmtpConfig();

        $ticketId = $ticket['id'];
        $subject = "[Aktualisierung: #{$ticketId}] {$ticket['subject']}";

        // Falls die Tätigkeit von einem Support-Mitarbeiter stammt -> Benachrichtigung an Kunde
        $recipient = $ticket['created_by_email'] ?? '';
        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $html = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                <div style='background: #257985; color: #fff; padding: 16px 20px;'>
                    <h3 style='margin: 0;'>Neue Aktivität zu Aufgabe #{$ticketId}</h3>
                </div>
                <div style='padding: 20px;'>
                    <p>Hallo <strong>" . htmlspecialchars($ticket['contact_person']) . "</strong>,</p>
                    <p>zu Ihrer Aufgabe liegt ein neuer Eintrag vor:</p>
                    <div style='background: #f4f8f8; border-left: 4px solid #257985; padding: 12px 16px; margin: 15px 0;'>
                        <strong>" . htmlspecialchars($activity['type']) . "</strong> durch <em>" . htmlspecialchars($activity['author_name']) . "</em> (" . htmlspecialchars($activity['created_at']) . "):<br><br>
                        " . nl2br(htmlspecialchars($activity['description'])) . "
                    </div>
                    <p>Status der Aufgabe: <strong>" . htmlspecialchars($ticket['status']) . "</strong></p>
                    <p style='font-size: 13px; color: #777;'>{$provider['name']}</p>
                </div>
            </div>
        ";
        $text = "Neue Aktivität zu Aufgabe #{$ticketId} ({$activity['type']}):\n" . $activity['description'];

        SimpleMailer::send(
            $recipient,
            $subject,
            $html,
            $text,
            $provider['email'],
            $provider['name'],
            $smtp
        );
    }

    /**
     * Sendet eine Benachrichtigung bei Statuswechsel (z. B. Geschlossen oder Abgerechnet).
     * @param array $ticket
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public static function sendStatusChangeNotification(array $ticket, string $oldStatus, string $newStatus): void {
        if (!self::isEnabled()) {
            return;
        }

        require_once dirname(__DIR__, 2) . '/libs/php/Mailer.php';
        $provider = HelpdeskManager::getServiceProviderDetails();
        $smtp = self::getSmtpConfig();

        $ticketId = $ticket['id'];
        $subject = "[Status: {$newStatus}] Aufgabe #{$ticketId} - {$ticket['subject']}";

        $recipient = $ticket['created_by_email'] ?? '';
        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $html = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                <div style='background: " . ($newStatus === HelpdeskManager::STATUS_BILLED ? "#2b6cb0" : "#28a745") . "; color: #fff; padding: 16px 20px;'>
                    <h3 style='margin: 0;'>Aufgabe #{$ticketId} ist jetzt: {$newStatus}</h3>
                </div>
                <div style='padding: 20px;'>
                    <p>Hallo <strong>" . htmlspecialchars($ticket['contact_person']) . "</strong>,</p>
                    <p>der Status Ihrer Aufgabe <strong>\"" . htmlspecialchars($ticket['subject']) . "\"</strong> wurde geändert:</p>
                    <p>Bisheriger Status: <strike>" . htmlspecialchars($oldStatus) . "</strike><br>
                    Neuer Status: <strong>" . htmlspecialchars($newStatus) . "</strong></p>
                    " . ($newStatus === HelpdeskManager::STATUS_BILLED ? "<p><em>Hinweis: Diese Aufgabe wurde in der Buchhaltung final abgerechnet und archiviert.</em></p>" : "") . "
                    <p style='font-size: 13px; color: #777;'>{$provider['name']}</p>
                </div>
            </div>
        ";
        $text = "Statusänderung zu Aufgabe #{$ticketId}: {$newStatus}.";

        SimpleMailer::send(
            $recipient,
            $subject,
            $html,
            $text,
            $provider['email'],
            $provider['name'],
            $smtp
        );
    }
}

