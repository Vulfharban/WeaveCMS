<?php
/**
 * ============================================================================
 * SimpleMailer: Autarker E-Mail-Versand für WeaveCMS
 * Unterstützt sowohl authentifiziertes SMTP (SSL/TLS) als auch PHP mail()
 * Ohne externe Composer-Abhängigkeiten, läuft sofort auf jedem PHP 8+ Server.
 * ============================================================================
 */

class SimpleMailer {
    
    /**
     * Versendet eine E-Mail entweder via SMTP oder via PHP mail().
     * 
     * @param string $to              Empfänger-E-Mail
     * @param string $subject         Betreffzeile
     * @param string $htmlBody        Inhalt als formatierte HTML-E-Mail
     * @param string $textBody        Inhalt als reiner Plaintext (Fallback)
     * @param string $replyToEmail    E-Mail-Adresse des Besuchers für Direktantwort
     * @param string $replyToName     Name des Besuchers
     * @param array|null $smtpConfig  Optionale SMTP-Konfiguration
     * @return array                  ['success' => bool, 'error' => string|null]
     */
    public static function send($to, $subject, $htmlBody, $textBody, $replyToEmail = '', $replyToName = '', $smtpConfig = null) {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Ungültige Empfänger-E-Mail-Adresse.'];
        }

        // Falls SMTP aktiviert und Host angegeben ist -> SMTP nutzen
        if (!empty($smtpConfig) && !empty($smtpConfig['enabled']) && !empty($smtpConfig['host'])) {
            return self::sendViaSmtp($to, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName, $smtpConfig);
        }

        // Andernfalls: PHP mail() als Standard-Fallback
        return self::sendViaPhpMail($to, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName, $smtpConfig);
    }

    /**
     * Kodiert Headerzeilen (Betreff, Name) sicher nach RFC 2047 ohne unerwünschte Zeilenumbrüche.
     */
    public static function encodeHeader($text) {
        $text = str_replace(["\r", "\n"], ' ', trim((string)$text));
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($text, 'UTF-8', 'B', '');
        }
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    /**
     * Versendet die E-Mail über einen direkten SMTP-Socket (SSL / STARTTLS / Klartext).
     */
    public static function sendViaSmtp($to, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName, $smtp) {
        $host = trim($smtp['host'] ?? 'localhost');
        $port = (int)($smtp['port'] ?? 587);
        $secure = strtolower(trim($smtp['secure'] ?? 'tls')); // 'tls', 'ssl', 'none'
        $user = trim($smtp['user'] ?? '');
        $pass = $smtp['pass'] ?? '';

        // Absenderadresse ermitteln und bereinigen
        $rawFrom = !empty($smtp['from_email']) ? trim($smtp['from_email']) : (!empty($user) ? $user : $to);
        if (preg_match('/<([^>]+)>/', $rawFrom, $matches)) {
            $rawFrom = trim($matches[1]);
        }
        $fromEmail = filter_var($rawFrom, FILTER_VALIDATE_EMAIL) ? $rawFrom : (filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : $to);
        $fromName = !empty($smtp['from_name']) ? trim($smtp['from_name']) : 'Atelier Musterfrau';

        // Prüfen, ob für verschlüsselte Verbindung (TLS/SSL) das PHP-Modul openssl vorhanden ist
        if (($secure === 'ssl' || $secure === 'tls') && !extension_loaded('openssl')) {
            return [
                'success' => false,
                'error' => "Die PHP-Erweiterung 'openssl' ist auf diesem Server (XAMPP) nicht aktiviert. Für eine sichere SMTP-Verbindung ($secure) muss 'extension=openssl' in der php.ini aktiviert sein."
            ];
        }

        $timeout = 15;
        $scheme = ($secure === 'ssl') ? 'ssl://' : 'tcp://';
        $remoteSocket = $scheme . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'peer_name' => $host
            ]
        ]);

        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $err = !empty($errstr) ? "$errstr ($errno)" : "Server nicht erreichbar oder Verbindung abgelehnt";
            return ['success' => false, 'error' => "SMTP-Verbindung fehlgeschlagen ($host:$port): $err"];
        }

        stream_set_timeout($socket, $timeout);

        // Sicherer Schreib-Helper: Sendet Daten vollständig auch über segmentierte Socket-Buffer
        $socketWrite = function($sock, $data) {
            $totalWritten = 0;
            $length = strlen($data);
            while ($totalWritten < $length) {
                $written = @fwrite($sock, substr($data, $totalWritten));
                if ($written === false || $written === 0) {
                    return false;
                }
                $totalWritten += $written;
            }
            return true;
        };

        // Antwort-Leser mit Unterstützung für mehrzeilige RFC 5321 Antworten
        $readResponse = function($expectedCode) use ($socket) {
            $response = '';
            while ($line = fgets($socket, 2048)) {
                $response .= $line;
                // RFC 5321: Mehrzeilige Antworten haben einen Bindestrich nach dem 3-stelligen Code (z.B. '250-')
                // Die letzte Zeile hat ein Leerzeichen oder keinen Bindestrich mehr
                if (strlen($line) >= 3 && (!isset($line[3]) || $line[3] !== '-')) {
                    break;
                }
            }
            if ($response === '') {
                return [false, "Keine Antwort vom SMTP-Server erhalten (Verbindungsabbruch oder Timeout)."];
            }
            $code = substr(trim($response), 0, 3);
            $expectedCodes = is_array($expectedCode) ? $expectedCode : [(string)$expectedCode];
            if (!in_array($code, $expectedCodes, true)) {
                return [false, "Unerwartete SMTP-Antwort (erwartet " . implode('/', $expectedCodes) . "): " . trim($response)];
            }
            return [true, $response];
        };

        // 1. Begrüßung lesen (220)
        list($ok, $err) = $readResponse(220);
        if (!$ok) { fclose($socket); return ['success' => false, 'error' => $err]; }

        // RFC-konformer EHLO-Clientname (keine Ports, IP in eckigen Klammern)
        $clientHost = !empty($_SERVER['SERVER_NAME']) ? preg_replace('/:[0-9]+$/', '', $_SERVER['SERVER_NAME']) : 'localhost';
        if (filter_var($clientHost, FILTER_VALIDATE_IP)) {
            $clientHost = '[' . $clientHost . ']';
        } elseif (!preg_match('/^[a-zA-Z0-9.-]+$/', $clientHost)) {
            $clientHost = 'localhost';
        }

        // 2. EHLO senden (Fallback auf HELO)
        $socketWrite($socket, "EHLO $clientHost\r\n");
        list($ok, $err) = $readResponse(250);
        if (!$ok) {
            $socketWrite($socket, "HELO $clientHost\r\n");
            list($ok, $err) = $readResponse(250);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => $err]; }
        }

        // 3. STARTTLS falls gefordert
        if ($secure === 'tls') {
            $socketWrite($socket, "STARTTLS\r\n");
            list($ok, $err) = $readResponse(220);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => "STARTTLS nicht unterstützt: $err"]; }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                fclose($socket);
                return ['success' => false, 'error' => 'TLS-Verschlüsselung mit dem SMTP-Server konnte nicht ausgehandelt werden.'];
            }

            // Nach TLS erneut EHLO
            $socketWrite($socket, "EHLO $clientHost\r\n");
            list($ok, $err) = $readResponse(250);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => $err]; }
        }

        // 4. Authentifizierung (AUTH LOGIN)
        if ($user !== '' && $pass !== '') {
            $socketWrite($socket, "AUTH LOGIN\r\n");
            list($ok, $err) = $readResponse(334);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => "AUTH LOGIN abgelehnt: $err"]; }

            $socketWrite($socket, base64_encode($user) . "\r\n");
            list($ok, $err) = $readResponse(334);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => "Benutzername abgelehnt: $err"]; }

            $socketWrite($socket, base64_encode($pass) . "\r\n");
            list($ok, $err) = $readResponse(235);
            if (!$ok) { fclose($socket); return ['success' => false, 'error' => "Passwort abgelehnt (Authentifizierungsfehler): $err"]; }
        }

        // 5. MAIL FROM
        $socketWrite($socket, "MAIL FROM:<$fromEmail>\r\n");
        list($ok, $err) = $readResponse(250);
        if (!$ok) { fclose($socket); return ['success' => false, 'error' => "MAIL FROM abgelehnt: $err"]; }

        // 6. RCPT TO (erlaubt RFC 250 und 251)
        $socketWrite($socket, "RCPT TO:<$to>\r\n");
        list($ok, $err) = $readResponse(['250', '251']);
        if (!$ok) { fclose($socket); return ['success' => false, 'error' => "RCPT TO ($to) abgelehnt: $err"]; }

        // 7. DATA
        $socketWrite($socket, "DATA\r\n");
        list($ok, $err) = $readResponse(354);
        if (!$ok) { fclose($socket); return ['success' => false, 'error' => "DATA abgelehnt: $err"]; }

        // 8. E-Mail-Header & Multipart-Body aufbauen
        $boundary = 'b1_' . md5(uniqid((string)microtime(true), true));
        $cleanHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $clientHost);
        $messageId = '<' . md5(uniqid((string)microtime(true), true)) . '@' . (!empty($cleanHost) ? $cleanHost : 'localhost') . '>';

        $encodedSubject = self::encodeHeader($subject);
        $encodedFromName = self::encodeHeader($fromName);
        $cleanReplyEmail = str_replace(["\r", "\n"], '', $replyToEmail);
        $cleanReplyName = str_replace(["\r", "\n"], ' ', $replyToName);
        $encodedReplyName = !empty($cleanReplyName) ? self::encodeHeader($cleanReplyName) : '';

        // Zeilenumbrüche normalisieren & Quoted-Printable Kodierung
        // Garantiert RFC-Konformität (keine Zeile über 76 Zeichen) und fehlerfreie Umlaute
        $normTextBody = preg_replace('/(?<!\r)\n/', "\r\n", $textBody);
        $normHtmlBody = preg_replace('/(?<!\r)\n/', "\r\n", $htmlBody);
        $qpTextBody = quoted_printable_encode($normTextBody);
        $qpHtmlBody = quoted_printable_encode($normHtmlBody);

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: $messageId";
        $headers[] = "From: $encodedFromName <$fromEmail>";
        $headers[] = "To: <$to>";
        if (!empty($cleanReplyEmail)) {
            $headers[] = "Reply-To: " . ($encodedReplyName ? "$encodedReplyName <$cleanReplyEmail>" : "<$cleanReplyEmail>");
        }
        $headers[] = "Subject: $encodedSubject";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $headers[] = "X-Mailer: WeaveCMS/1.0";

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Plaintext Teil
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $qpTextBody . "\r\n\r\n";

        // HTML Teil
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $qpHtmlBody . "\r\n\r\n";

        $body .= "--$boundary--\r\n";

        // Dot-Stuffing für SMTP (Zeilen, die mit . beginnen, müssen .. haben)
        $body = preg_replace('/^\./m', '..', $body);

        if (!$socketWrite($socket, rtrim($body, "\r\n") . "\r\n.\r\n")) {
            fclose($socket);
            return ['success' => false, 'error' => 'Fehler beim Übertragen der Nachricht an den SMTP-Server.'];
        }

        list($ok, $err) = $readResponse(250);
        if (!$ok) { fclose($socket); return ['success' => false, 'error' => "Nachrichtenversand fehlgeschlagen: $err"]; }

        // 9. QUIT
        $socketWrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'error' => null];
    }

    /**
     * Versendet die E-Mail über die native PHP mail()-Funktion.
     */
    public static function sendViaPhpMail($to, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName, $smtp = null) {
        $boundary = 'b1_' . md5(uniqid((string)microtime(true), true));
        $rawFrom = !empty($smtp['from_email']) ? trim($smtp['from_email']) : 'noreply@' . (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
        if (preg_match('/<([^>]+)>/', $rawFrom, $matches)) {
            $rawFrom = trim($matches[1]);
        }
        $fromEmail = filter_var($rawFrom, FILTER_VALIDATE_EMAIL) ? $rawFrom : 'kontakt@maxima-musterfrau.de';
        $fromName = !empty($smtp['from_name']) ? trim($smtp['from_name']) : 'Atelier Musterfrau';

        $clientHost = !empty($_SERVER['SERVER_NAME']) ? preg_replace('/:[0-9]+$/', '', $_SERVER['SERVER_NAME']) : 'localhost';
        $cleanHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $clientHost);
        $messageId = '<' . md5(uniqid((string)microtime(true), true)) . '@' . (!empty($cleanHost) ? $cleanHost : 'localhost') . '>';

        $encodedSubject = self::encodeHeader($subject);
        $encodedFromName = self::encodeHeader($fromName);
        $cleanReplyEmail = str_replace(["\r", "\n"], '', $replyToEmail);
        $cleanReplyName = str_replace(["\r", "\n"], ' ', $replyToName);
        $encodedReplyName = !empty($cleanReplyName) ? self::encodeHeader($cleanReplyName) : '';

        // Zeilenumbrüche normalisieren & Quoted-Printable Kodierung
        $normTextBody = preg_replace('/(?<!\r)\n/', "\r\n", $textBody);
        $normHtmlBody = preg_replace('/(?<!\r)\n/', "\r\n", $htmlBody);
        $qpTextBody = quoted_printable_encode($normTextBody);
        $qpHtmlBody = quoted_printable_encode($normHtmlBody);

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: $messageId";
        $headers[] = "From: $encodedFromName <$fromEmail>";
        if (!empty($cleanReplyEmail)) {
            $headers[] = "Reply-To: " . ($encodedReplyName ? "$encodedReplyName <$cleanReplyEmail>" : "<$cleanReplyEmail>");
        }
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $qpTextBody . "\r\n\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $qpHtmlBody . "\r\n\r\n";
        $body .= "--$boundary--";

        $headersStr = implode("\r\n", $headers);
        
        $extraParams = '';
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $extraParams = '-f' . $fromEmail;
        }

        $sent = !empty($extraParams) 
            ? @mail($to, $encodedSubject, $body, $headersStr, $extraParams)
            : @mail($to, $encodedSubject, $body, $headersStr);

        if ($sent) {
            return ['success' => true, 'error' => null];
        }

        return [
            'success' => false, 
            'error' => 'Der lokale Server konnte die E-Mail nicht per PHP mail() senden (oft in XAMPP ohne Mailserver). Bitte konfiguriere die SMTP-Zugangsdaten im Adminbereich unter Sicherheit & System.'
        ];
    }
}
