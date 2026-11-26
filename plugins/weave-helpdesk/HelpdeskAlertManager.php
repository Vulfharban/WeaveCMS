<?php
/**
 * ============================================================================
 * Weave Helpdesk – HelpdeskAlertManager
 * ============================================================================
 * Versendet bei kritischen Tickets (Priorität "Kritisch") sofortige Alarme
 * über konfigurierbare Kanäle:
 *   1. Dringende E-Mails (SimpleMailer)
 *   2. SMS (Webhook / Twilio / seven.io)
 *   3. WhatsApp-Nachrichten (Webhook / Meta Cloud API / Twilio WA / CallMeBot)
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class HelpdeskAlertManager {

    /**
     * Zerlegt kommagetrennte / zeilengetrennte Empfängerlisten und entfernt Duplikate.
     * @return string[]
     */
    public static function parseRecipientList(string $raw): array {
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $val = trim($p);
            if ($val !== '' && !in_array($val, $out, true)) {
                $out[] = $val;
            }
        }
        return $out;
    }

    /**
     * Bereinigt eine Mobilnummer (behält führendes '+' und Ziffern).
     */
    public static function normalizePhone(string $phone): string {
        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return ($hasPlus ? '+' : '') . $digits;
    }

    /**
     * Validiert Webhook-/API-URLs (ausschließlich http:// oder https://).
     */
    protected static function isValidHttpUrl(string $url): bool {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Hilfsmethode für HTTP-Requests (cURL mit Stream-Fallback).
     */
    protected static function httpRequest(string $method, string $url, array $headers = [], ?string $body = null): array {
        if (!self::isValidHttpUrl($url)) {
            return ['ok' => false, 'status' => 0, 'error' => 'Ungültige HTTP/HTTPS-URL für Alarmierungs-Gateway.'];
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($resp === false) {
                return ['ok' => false, 'status' => 0, 'error' => $err ?: 'cURL-Fehler'];
            }
            return [
                'ok'     => ($code >= 200 && $code < 300),
                'status' => $code,
                'body'   => (string)$resp,
                'error'  => ($code >= 200 && $code < 300) ? null : "HTTP {$code}: " . mb_substr(strip_tags((string)$resp), 0, 180)
            ];
        }

        $opts = [
            'http' => [
                'method'        => strtoupper($method),
                'timeout'       => 10,
                'ignore_errors' => true,
                'header'        => implode("\r\n", $headers),
                'content'       => $body ?? '',
            ]
        ];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            return ['ok' => false, 'status' => 0, 'error' => 'HTTP-Verbindung fehlgeschlagen (weder cURL noch allow_url_fopen erfolgreich).'];
        }
        return ['ok' => true, 'status' => 200, 'body' => (string)$resp, 'error' => null];
    }

    /**
     * Löst bei Priorität "Kritisch" (oder im Testmodus) alle konfigurierten Sofort-Alarme aus.
     *
     * @param array $ticket
     * @param array $creator
     * @param bool $isTest
     * @return array ['triggered' => bool, 'summary' => string, 'channels' => array]
     */
    public static function sendCriticalAlert(array $ticket, array $creator = [], bool $isTest = false): array {
        $cfg = HelpdeskManager::getConfig();
        if (!$isTest && empty($cfg['critical_alerts_enabled'])) {
            return ['triggered' => false, 'summary' => '', 'channels' => []];
        }

        $ticketId = (string)($ticket['id'] ?? 'TEST');
        $subject = (string)($ticket['subject'] ?? 'Kritisches Ticket');
        $company = trim((string)($ticket['company_name'] ?? 'Privat / Keine Firma'));
        $contact = trim((string)($ticket['contact_person'] ?? ($creator['name'] ?? 'Unbekannt')));
        $phone = trim((string)($ticket['alt_phone'] ?? '-'));
        $contentPreview = mb_substr(trim((string)($ticket['content'] ?? '')), 0, 350);

        $prefix = $isTest ? '[TEST-ALARM] ' : '';
        $shortText = "{$prefix}🚨 KRITISCHES TICKET #{$ticketId}: {$subject} | Firma: {$company} | Kontakt: {$contact} ({$phone})";

        $channels = [];
        $summaryParts = [];

        // ---------------------------------------------------------------------
        // 1. Dringende E-Mails versenden
        // ---------------------------------------------------------------------
        if (!empty($cfg['critical_email_enabled'])) {
            $provider = HelpdeskManager::getServiceProviderDetails();
            $rawEmails = trim((string)($cfg['critical_emails'] ?? ''));
            $emailList = self::parseRecipientList($rawEmails);
            if (empty($emailList) && !empty($provider['email'])) {
                $emailList = [$provider['email']];
            }

            if (!empty($emailList)) {
                require_once dirname(__DIR__, 2) . '/libs/php/Mailer.php';
                $cmsData = $GLOBALS['data'] ?? [];
                if (empty($cmsData) && function_exists('load_data')) {
                    $cmsData = load_data(dirname(__DIR__, 2) . '/data.json');
                }
                $smtp = $cmsData['settings']['smtp'] ?? null;

                $mailSubject = "{$prefix}🚨 [DRINGEND / KRITISCH] Aufgabe #{$ticketId}: {$subject}";
                $mailHtml = "
                    <div style='font-family: Arial, sans-serif; color: #1e293b; max-width: 620px; margin: 0 auto; border: 2px solid #dc2626; border-radius: 8px; overflow: hidden;'>
                        <div style='background: #dc2626; color: #fff; padding: 16px 22px;'>
                            <h2 style='margin: 0; font-size: 18px;'>{$prefix}🚨 KRITISCHE SUPPORT-AUFGABE #{$ticketId}</h2>
                            <div style='font-size: 12px; opacity: 0.92; margin-top: 4px;'>Priorität: KRITISCH &bull; Sofortige Reaktion erforderlich</div>
                        </div>
                        <div style='padding: 20px; background: #fff;'>
                            <p style='margin-top: 0;'><strong>Betreff:</strong> " . htmlspecialchars($subject) . "<br>
                            <strong>Unternehmen:</strong> " . htmlspecialchars($company) . "<br>
                            <strong>Kontaktperson:</strong> " . htmlspecialchars($contact) . "<br>
                            <strong>Telefon / Rückruf:</strong> " . htmlspecialchars($phone) . "<br>
                            <strong>Aufgabentyp:</strong> " . htmlspecialchars((string)($ticket['type'] ?? '-')) . "</p>
                            <div style='background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px 14px; border-radius: 4px; margin: 14px 0;'>
                                " . nl2br(htmlspecialchars((string)($ticket['content'] ?? ''))) . "
                            </div>
                        </div>
                    </div>
                ";
                $mailText = $shortText . "\n\nBeschreibung:\n" . (string)($ticket['content'] ?? '');
                $replyTo = filter_var($ticket['created_by_email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$ticket['created_by_email'] : '';

                $sentCount = 0;
                $errors = [];
                foreach ($emailList as $em) {
                    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Ungültige E-Mail: {$em}";
                        continue;
                    }
                    $res = SimpleMailer::send($em, $mailSubject, $mailHtml, $mailText, $replyTo, $contact, $smtp);
                    if (!empty($res['success'])) {
                        $sentCount++;
                    } else {
                        $errors[] = $em . ': ' . ($res['error'] ?? 'Fehler');
                    }
                }
                $channels['email'] = ['sent' => $sentCount, 'errors' => $errors];
                $summaryParts[] = "Dringende E-Mail: {$sentCount}/" . count($emailList) . " zugestellt";
            }
        }

        // ---------------------------------------------------------------------
        // 2. SMS versenden
        // ---------------------------------------------------------------------
        if (!empty($cfg['critical_sms_enabled'])) {
            $numbers = self::parseRecipientList((string)($cfg['critical_sms_numbers'] ?? ''));
            $smsProvider = trim((string)($cfg['critical_sms_provider'] ?? 'webhook'));
            $smsUrl = trim((string)($cfg['critical_sms_api_url'] ?? ''));
            $smsSid = trim((string)($cfg['critical_sms_account_sid'] ?? ''));
            $smsKey = trim((string)($cfg['critical_sms_api_key'] ?? ''));
            $smsFrom = trim((string)($cfg['critical_sms_from'] ?? 'Helpdesk')) ?: 'Helpdesk';

            if (!empty($numbers)) {
                $sentCount = 0;
                $errors = [];
                foreach ($numbers as $rawNum) {
                    $num = self::normalizePhone($rawNum);
                    if ($num === '') continue;

                    if ($smsProvider === 'twilio') {
                        if ($smsSid === '' || $smsKey === '') {
                            $errors[] = 'Twilio Account SID oder Auth-Token fehlt.';
                            break;
                        }
                        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . rawurlencode($smsSid) . "/Messages.json";
                        $headers = [
                            'Authorization: Basic ' . base64_encode($smsSid . ':' . $smsKey),
                            'Content-Type: application/x-www-form-urlencoded'
                        ];
                        $payload = http_build_query(['To' => $num, 'From' => $smsFrom, 'Body' => $shortText]);
                        $res = self::httpRequest('POST', $endpoint, $headers, $payload);
                    } elseif ($smsProvider === 'seven') {
                        if ($smsKey === '') {
                            $errors[] = 'seven.io API-Key fehlt.';
                            break;
                        }
                        $headers = [
                            'X-Api-Key: ' . $smsKey,
                            'Content-Type: application/x-www-form-urlencoded'
                        ];
                        $payload = http_build_query(['to' => $num, 'from' => mb_substr($smsFrom, 0, 11), 'text' => $shortText]);
                        $res = self::httpRequest('POST', 'https://gateway.seven.io/api/sms', $headers, $payload);
                    } else {
                        if (!self::isValidHttpUrl($smsUrl)) {
                            $errors[] = 'Keine gültige SMS-Webhook-URL (http/https) konfiguriert.';
                            break;
                        }
                        $headers = ['Content-Type: application/json'];
                        if ($smsKey !== '') {
                            $headers[] = 'Authorization: Bearer ' . $smsKey;
                        }
                        $payload = json_encode([
                            'channel'   => 'sms',
                            'to'        => $num,
                            'from'      => $smsFrom,
                            'message'   => $shortText,
                            'ticket_id' => $ticketId,
                            'subject'   => $subject,
                            'company'   => $company,
                            'contact'   => $contact,
                            'phone'     => $phone,
                            'priority'  => 'Kritisch',
                            'preview'   => $contentPreview,
                            'is_test'   => $isTest,
                        ], JSON_UNESCAPED_UNICODE);
                        $res = self::httpRequest('POST', $smsUrl, $headers, $payload);
                    }

                    if (!empty($res['ok'])) {
                        $sentCount++;
                    } else {
                        $errors[] = $num . ': ' . ($res['error'] ?? 'Fehler');
                    }
                }
                $channels['sms'] = ['sent' => $sentCount, 'errors' => $errors];
                $summaryParts[] = "SMS ({$smsProvider}): {$sentCount}/" . count($numbers) . " gesendet";
            }
        }

        // ---------------------------------------------------------------------
        // 3. WhatsApp-Nachrichten versenden
        // ---------------------------------------------------------------------
        if (!empty($cfg['critical_wa_enabled'])) {
            $numbers = self::parseRecipientList((string)($cfg['critical_wa_numbers'] ?? ''));
            $waProvider = trim((string)($cfg['critical_wa_provider'] ?? 'webhook'));
            $waUrl = trim((string)($cfg['critical_wa_api_url'] ?? ''));
            $waPhoneId = trim((string)($cfg['critical_wa_phone_id'] ?? ''));
            $waToken = trim((string)($cfg['critical_wa_token'] ?? ''));
            $waFrom = trim((string)($cfg['critical_wa_from'] ?? ''));

            if (!empty($numbers)) {
                $waText = "{$shortText}\n\n{$contentPreview}";
                $sentCount = 0;
                $errors = [];

                foreach ($numbers as $rawNum) {
                    $num = self::normalizePhone($rawNum);
                    if ($num === '') continue;

                    if ($waProvider === 'meta_cloud') {
                        if ($waPhoneId === '' || $waToken === '') {
                            $errors[] = 'Meta Cloud Phone-Number-ID oder Bearer Token fehlt.';
                            break;
                        }
                        $endpoint = "https://graph.facebook.com/v19.0/" . rawurlencode($waPhoneId) . "/messages";
                        $headers = [
                            'Authorization: Bearer ' . $waToken,
                            'Content-Type: application/json'
                        ];
                        $payload = json_encode([
                            'messaging_product' => 'whatsapp',
                            'to'                => ltrim($num, '+'),
                            'type'              => 'text',
                            'text'              => ['body' => $waText]
                        ], JSON_UNESCAPED_UNICODE);
                        $res = self::httpRequest('POST', $endpoint, $headers, $payload);
                    } elseif ($waProvider === 'twilio_wa') {
                        if ($waPhoneId === '' || $waToken === '' || $waFrom === '') {
                            $errors[] = 'Twilio SID, Auth-Token oder Absender-WhatsApp-Nummer fehlt.';
                            break;
                        }
                        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . rawurlencode($waPhoneId) . "/Messages.json";
                        $headers = [
                            'Authorization: Basic ' . base64_encode($waPhoneId . ':' . $waToken),
                            'Content-Type: application/x-www-form-urlencoded'
                        ];
                        $fromAddr = str_starts_with($waFrom, 'whatsapp:') ? $waFrom : ('whatsapp:' . $waFrom);
                        $toAddr = str_starts_with($num, 'whatsapp:') ? $num : ('whatsapp:' . $num);
                        $payload = http_build_query(['To' => $toAddr, 'From' => $fromAddr, 'Body' => $waText]);
                        $res = self::httpRequest('POST', $endpoint, $headers, $payload);
                    } elseif ($waProvider === 'callmebot') {
                        if ($waToken === '') {
                            $errors[] = 'CallMeBot API-Key fehlt.';
                            break;
                        }
                        $endpoint = "https://api.callmebot.com/whatsapp.php?phone=" . rawurlencode($num)
                                  . "&text=" . rawurlencode($waText)
                                  . "&apikey=" . rawurlencode($waToken);
                        $res = self::httpRequest('GET', $endpoint);
                    } else {
                        if (!self::isValidHttpUrl($waUrl)) {
                            $errors[] = 'Keine gültige WhatsApp-Webhook-URL (http/https) konfiguriert.';
                            break;
                        }
                        $headers = ['Content-Type: application/json'];
                        if ($waToken !== '') {
                            $headers[] = 'Authorization: Bearer ' . $waToken;
                        }
                        $payload = json_encode([
                            'channel'   => 'whatsapp',
                            'to'        => $num,
                            'message'   => $waText,
                            'ticket_id' => $ticketId,
                            'subject'   => $subject,
                            'company'   => $company,
                            'contact'   => $contact,
                            'phone'     => $phone,
                            'priority'  => 'Kritisch',
                            'is_test'   => $isTest,
                        ], JSON_UNESCAPED_UNICODE);
                        $res = self::httpRequest('POST', $waUrl, $headers, $payload);
                    }

                    if (!empty($res['ok'])) {
                        $sentCount++;
                    } else {
                        $errors[] = $num . ': ' . ($res['error'] ?? 'Fehler');
                    }
                }
                $channels['whatsapp'] = ['sent' => $sentCount, 'errors' => $errors];
                $summaryParts[] = "WhatsApp ({$waProvider}): {$sentCount}/" . count($numbers) . " gesendet";
            }
        }

        $triggered = !empty($channels);
        $summary = $triggered
            ? 'Sofort-Alarmierung ausgelöst: ' . implode(' | ', $summaryParts)
            : 'Sofort-Alarmierung ist aktiv, aber es sind keine Empfänger/Kanäle konfiguriert.';

        return [
            'triggered' => $triggered,
            'summary'   => $summary,
            'channels'  => $channels,
        ];
    }
}
