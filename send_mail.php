<?php
/**
 * ============================================================================
 * send_mail.php: Sicherer Server-Mailversand für das Kontaktformular
 * Nimmt AJAX-Anfragen entgegen, prüft Spamschutz & Honeypot und versendet
 * die Nachricht zuverlässig an die hinterlegte E-Mail-Adresse.
 * ============================================================================
 */

// Verhindern, dass PHP-Warnungen die JSON-Ausgabe korrumpieren
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json; charset=UTF-8');

function send_json_response(array $payload): void {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'error' => 'Ungültige Anfragemethode.']);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/php/Mailer.php';
require_once __DIR__ . '/libs/php/ContactInbox.php';

// 1. Rate-Limiting gegen Spambots & Flooding (Phase 9.2: max. 5 Anfragen je 5 Minuten)
if (SecurityManager::isRateLimited('contact_form', 5, 300)) {
    $rem = SecurityManager::getLockoutRemaining('contact_form', 5, 300);
    $mins = max(1, ceil($rem / 60));
    SecurityManager::logEvent('CONTACT_RATELIMIT', 'Kontaktformular-Anfrage abgewiesen wegen Rate-Limit-Sperre', 'WARNING');
    send_json_response([
        'success' => false,
        'error' => "Zu viele Anfragen in kurzer Zeit. Bitte warte {$mins} Minute(n) vor dem nächsten Absenden."
    ]);
}

// Datenbasis laden
$data_file = __DIR__ . '/data.json';
$data = load_data($data_file);

// 2. Honeypot-Prüfung (Unsichtbares Feld für Bots)
$honeypot = trim($_POST['website'] ?? '');
if (!empty($honeypot)) {
    SecurityManager::logEvent('SPAMBOT_CAUGHT', 'Spambot im Honeypot-Feld abgefangen', 'WARNING');
    // Spambot gefangen: Wir täuschen Erfolg vor, versenden aber nichts (stilles Verwerfen)
    send_json_response(['success' => true, 'message' => 'Vielen Dank! Deine Nachricht wurde empfangen.']);
}

// 2. Eingaben bereinigen & validieren
$name = str_replace(["\r", "\n"], ' ', trim(strip_tags($_POST['name'] ?? '')));
$name = mb_substr($name, 0, 100, 'UTF-8');

$email = str_replace(["\r", "\n"], '', trim($_POST['email'] ?? ''));
$email = mb_substr($email, 0, 150, 'UTF-8');

$phone = str_replace(["\r", "\n"], ' ', trim(strip_tags($_POST['phone'] ?? '')));
$phone = mb_substr($phone, 0, 50, 'UTF-8');

$message = trim(strip_tags($_POST['message'] ?? ''));
$message = mb_substr($message, 0, 5000, 'UTF-8');

$selected_icon = trim($_POST['selected_icon'] ?? '');
$target_icon = trim($_POST['target_icon'] ?? '');

if (empty($name)) {
    send_json_response(['success' => false, 'error' => 'Bitte gib deinen Namen ein.']);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(['success' => false, 'error' => 'Bitte gib eine gültige E-Mail-Adresse ein, damit wir antworten können.']);
}

if (empty($message) || mb_strlen($message, 'UTF-8') < 5) {
    send_json_response(['success' => false, 'error' => 'Bitte gib eine Nachricht mit mindestens 5 Zeichen ein.']);
}

// 3. Multi-Captcha Sicherheitsprüfung (Phase 16)
$captcha_mode = CaptchaManager::getMode($data);

if ($captcha_mode === CaptchaManager::MODE_ICONS) {
    $selected_icon = trim($_POST['selected_icon'] ?? '');
    $target_icon = trim($_POST['target_icon'] ?? '');
    $allowed_icons = array_keys(get_active_captcha_icons($data));
    if (empty($selected_icon) || empty($target_icon) || $selected_icon !== $target_icon || !in_array($selected_icon, $allowed_icons, true)) {
        send_json_response(['success' => false, 'error' => 'Die Sicherheitsprüfung (Symbol-Auswahl) war nicht korrekt. Bitte versuche es erneut.']);
    }
} elseif ($captcha_mode === CaptchaManager::MODE_MATH) {
    $math_answer = trim($_POST['captcha_math_answer'] ?? '');
    $math_hash = trim($_POST['captcha_math_hash'] ?? '');
    if (!CaptchaManager::verifyMathChallenge($math_answer, $math_hash)) {
        send_json_response(['success' => false, 'error' => 'Die Sicherheitsprüfung (Rechenaufgabe) war nicht korrekt. Bitte versuche es erneut.']);
    }
} elseif ($captcha_mode === CaptchaManager::MODE_CANVAS) {
    $canvas_answer = trim($_POST['captcha_canvas_answer'] ?? '');
    $canvas_hash = trim($_POST['captcha_canvas_hash'] ?? '');
    if (!CaptchaManager::verifyCanvasChallenge($canvas_answer, 'contact', $canvas_hash)) {
        send_json_response(['success' => false, 'error' => 'Der eingegebene Bestätigungscode war nicht korrekt. Bitte versuche es erneut.']);
    }
} elseif ($captcha_mode === CaptchaManager::MODE_HONEYPOT) {
    // Honeypot & Time-Trap sind aktiv
}

// 3b. Anfrage lokal im Admin-Postfach archivieren (unabhängig vom Mailversand)
$course_ref = mb_substr(str_replace(["\r", "\n"], ' ', trim(strip_tags($_POST['course_ref'] ?? ''))), 0, 200, 'UTF-8');
ContactInbox::add([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'course_ref' => $course_ref,
]);

// Phase 31: Event-Webhook contact.received auslösen
require_once __DIR__ . '/libs/php/WebhookManager.php';
WebhookManager::dispatch(WebhookManager::EVENT_CONTACT_RECEIVED, [
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'message'    => $message,
    'course_ref' => $course_ref
]);

// Rate-Limit-Zähler aktualisieren und Sicherheits-Event protokollieren
SecurityManager::recordFailedAttempt('contact_form', 300);
SecurityManager::logEvent('CONTACT_RECEIVED', 'Kontaktanfrage empfangen von ' . $email, 'INFO');

// Reiner Archivierungsmodus (Besucher sendet per Mailto-Programm)
if (!empty($_POST['archive_only'])) {
    send_json_response(['success' => true, 'message' => 'Anfrage archiviert.']);
}

// 4. Empfänger und Konfiguration ermitteln
$to_email = trim($data['texts']['contact_email'] ?? ('info@' . ($_SERVER['SERVER_NAME'] ?? 'example.org')));
if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    $to_email = 'info@' . ($_SERVER['SERVER_NAME'] ?? 'example.org');
}

$greeting = trim($data['texts']['contact_mail_greeting'] ?? 'Hallo,');
$mail_body_text = $message;
if (!empty($greeting) && stripos($mail_body_text, $greeting) !== 0) {
    $mail_body_text = $greeting . "\n\n" . $mail_body_text;
}
$mail_body_text .= "\n\nViele Grüße\n" . $name . "\nE-Mail: " . $email . "\nTelefon: " . (!empty($phone) ? $phone : 'Keine Angabe');
$fallback_mailto = "mailto:" . $to_email . "?subject=" . rawurlencode("Kontaktanfrage von " . $name) . "&body=" . rawurlencode($mail_body_text);

// Falls SMTP nicht aktiviert ist: Kein PHP-Mailer-Versand, sondern Fallback-Mailto zurückgeben
if (empty($smtp_config['enabled']) || empty($smtp_config['host'])) {
    send_json_response([
        'success' => false,
        'error' => 'SMTP-Versand ist in den Einstellungen nicht aktiviert.',
        'fallback_mailto' => $fallback_mailto
    ]);
}

// 5. E-Mail-Inhalte aufbauen (HTML & Plaintext)
$site_brand = !empty($data['texts']['contact_name']) ? $data['texts']['contact_name'] : 'WeaveCMS';
$subject = "Neue Kontaktanfrage von " . $name . " | " . $site_brand;

$now_formatted = date('d.m.Y \u\m H:i \U\h\r');
$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_phone = !empty($phone) ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : '<em>Keine Telefonnummer angegeben</em>';
$safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$html_body = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neue Kontaktanfrage</title>
</head>
<body style="font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #fbf9f6; margin: 0; padding: 25px; color: #3e3228;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e7dfd5; border-radius: 18px; padding: 30px; box-shadow: 0 4px 20px rgba(62,50,40,0.06);">
        <div style="border-bottom: 2px solid #2e6248; padding-bottom: 15px; margin-bottom: 25px;">
            <h2 style="color: #2e6248; margin: 0 0 6px 0; font-size: 22px;">🌿 Neue Kontaktanfrage erhalten</h2>
            <p style="color: #78695d; font-size: 13px; margin: 0;">Eingegangen über das Kontaktformular am {$now_formatted}</p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <tr>
                <td style="padding: 8px 0; width: 130px; color: #78695d; font-weight: 600;">Absender / Name:</td>
                <td style="padding: 8px 0; font-weight: bold; color: #3e3228;">{$safe_name}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #78695d; font-weight: 600;">E-Mail-Adresse:</td>
                <td style="padding: 8px 0;"><a href="mailto:{$safe_email}" style="color: #2e6248; text-decoration: none; font-weight: 600;">{$safe_email}</a></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #78695d; font-weight: 600;">Telefon:</td>
                <td style="padding: 8px 0; color: #3e3228;">{$safe_phone}</td>
            </tr>
        </table>

        <div style="background: #fbf9f6; border: 1px solid #e7dfd5; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
            <h4 style="margin: 0 0 10px 0; color: #2e6248; font-size: 15px;">Nachricht:</h4>
            <div style="line-height: 1.6; color: #3e3228; font-size: 14.5px;">
                {$safe_message}
            </div>
        </div>

        <div style="border-top: 1px solid #e7dfd5; padding-top: 15px; text-align: center; font-size: 12px; color: #a19388;">
            Tipp: Du kannst einfach auf diese E-Mail antworten, um direkt an <b>{$safe_name}</b> zu schreiben.
        </div>
    </div>
</body>
</html>
HTML;

$text_body = "Neue Kontaktanfrage über {$site_brand} ({$now_formatted})\n\n"
           . "Name:    {$name}\n"
           . "E-Mail:  {$email}\n"
           . "Telefon: " . (!empty($phone) ? $phone : 'Keine Angabe') . "\n\n"
           . "Nachricht:\n"
           . "--------------------------------------------------------\n"
           . "{$message}\n"
           . "--------------------------------------------------------\n";

// 6. E-Mail versenden (Reply-To ist der Besucher)
$send_result = SimpleMailer::send(
    $to_email,
    $subject,
    $html_body,
    $text_body,
    $email,
    $name,
    $smtp_config
);

if ($send_result['success']) {
    send_json_response([
        'success' => true,
        'message' => 'Vielen Dank, ' . $name . '! Deine Nachricht wurde erfolgreich gesendet. Wir melden uns zeitnah bei dir.'
    ]);
} else {
    // Falls Serverversand fehlschlägt (z. B. auf lokalem XAMPP ohne SMTP):
    // Fallback-Mailto bereitstellen, damit der Besucher trotzdem senden kann
    send_json_response([
        'success' => false,
        'error' => $send_result['error'],
        'fallback_mailto' => $fallback_mailto
    ]);
}
