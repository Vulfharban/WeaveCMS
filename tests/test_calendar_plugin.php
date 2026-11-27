<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Weave Calendar (Phase 36)
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/php/SecurityManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/libs/php/Mailer.php';
require_once dirname(__DIR__) . '/plugins/weave-calendar/plugin.php';

function assert_cal(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_cal_test_' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0755, true);
CalendarManager::setTestStorageDir($tmpDir);

try {
    // 1. Staff, Categories & Events CRUD + DSGVO-Bereinigung
    $staff1 = CalendarManager::saveStaff([
        'id'     => 'staff_anna',
        'name'   => 'Anna Weber',
        'email'  => 'anna@example.com',
        'role'   => 'coordinator',
        'status' => 'available',
    ]);
    $catPub = CalendarManager::saveCategory(['id' => 'cat_workshop', 'name' => 'Workshops', 'color' => '#2563eb', 'is_public' => true]);
    $catPriv = CalendarManager::saveCategory(['id' => 'cat_intern', 'name' => 'Intern', 'color' => '#64748b', 'is_public' => false]);

    $pubSave = CalendarManager::saveEvent([
        'title'       => 'Frühlings-Workshop',
        'start_at'    => '2030-05-10 10:00:00',
        'end_at'      => '2030-05-10 12:00:00',
        'category_id' => $catPub['id'],
        'visibility'  => 'public',
        'staff_ids'   => [$staff1['id']],
        'attendees'   => [['name' => 'Gast', 'email' => 'gast@example.com']],
        'location'    => 'Atelier Nord',
    ]);
    $privSave = CalendarManager::saveEvent([
        'title'       => 'Vertrauliches Mitarbeitergespräch',
        'start_at'    => '2030-05-10 14:00:00',
        'end_at'      => '2030-05-10 15:00:00',
        'category_id' => $catPriv['id'],
        'visibility'  => 'internal',
        'staff_ids'   => [$staff1['id']],
    ]);
    assert_cal(empty($pubSave['conflicts']) && empty($privSave['conflicts']), 'Nicht überlappende Termine dürfen keinen Konflikt erzeugen');

    $publicRange = CalendarManager::expandEventsForRange('2030-05-01 00:00:00', '2030-05-31 23:59:59', ['public_only' => true]);
    assert_cal(count($publicRange) === 1 && $publicRange[0]['title'] === 'Frühlings-Workshop', 'DSGVO public_only Filter fehlgeschlagen');
    assert_cal(empty($publicRange[0]['attendees']) && empty($publicRange[0]['staff_ids']), 'DSGVO Sanitizer muss Teilnehmer & Mitarbeiter-IDs im Frontend entfernen');

    // 2. Konfliktwarnung bei Mitarbeiter-Doppelbelegung
    $conflictSave = CalendarManager::saveEvent([
        'title'       => 'Paralleler Kundentermin',
        'start_at'    => '2030-05-10 11:00:00',
        'end_at'      => '2030-05-10 12:30:00',
        'category_id' => $catPub['id'],
        'staff_ids'   => [$staff1['id']],
    ]);
    assert_cal(count($conflictSave['conflicts']) === 1 && $conflictSave['conflicts'][0]['staff_id'] === 'staff_anna', 'Mitarbeiter-Doppelbelegung muss erkannt werden');

    // 3. RRULE-Serientermine + EXDATE
    CalendarManager::saveEvent([
        'id'          => 'evt_series',
        'title'       => 'Wöchentlicher Kurs',
        'start_at'    => '2030-06-03 18:00:00',
        'end_at'      => '2030-06-03 19:30:00',
        'rrule'       => 'FREQ=WEEKLY;COUNT=4',
        'exdates'     => ['2030-06-10'],
        'category_id' => $catPub['id'],
        'visibility'  => 'public',
    ]);
    $seriesOcc = CalendarManager::expandEventsForRange('2030-06-01 00:00:00', '2030-06-30 23:59:59', ['search' => 'Wöchentlicher']);
    assert_cal(count($seriesOcc) === 3, 'RRULE COUNT=4 minus 1 EXDATE muss genau 3 Termine ergeben');

    // 3b. Ganztägiger Termin (All-Day Normalisierung) & Langlaufende RRULE (>2 Jahre)
    $allDayRes = CalendarManager::saveEvent([
        'title'    => 'Ganztägiger Feiertag',
        'start_at' => '2030-08-15 00:00:00',
        'end_at'   => '2030-08-15 00:00:00',
        'all_day'  => true,
    ]);
    assert_cal($allDayRes['event']['start_at'] === '2030-08-15 00:00:00' && $allDayRes['event']['end_at'] === '2030-08-16 00:00:00', 'All-Day Terminende muss auf nächsten Tag 00:00:00 normalisiert werden');

    CalendarManager::saveEvent([
        'id'       => 'evt_daily_long',
        'title'    => 'Tägliche Sprechstunde',
        'start_at' => '2028-01-01 09:00:00',
        'end_at'   => '2028-01-01 09:30:00',
        'rrule'    => 'FREQ=DAILY',
    ]);
    $longOcc = CalendarManager::expandEventsForRange('2030-09-01 00:00:00', '2030-09-03 23:59:59', ['search' => 'Sprechstunde']);
    assert_cal(count($longOcc) === 3, 'Langlaufende unbefristete RRULE muss per Fast-Forward auch nach >2 Jahren sauber expandieren');

    // 4. .ics Export & Round-Trip Import + UTF-8 Falten + SSRF-Validierung
    $utf8LongEvent = [
        'id'       => 'evt_utf8',
        'title'    => 'Große Jubiläums-Übersichtsveranstaltung für Bürgerinnen und Bürger mit Überlänge äöüßÄÖÜ',
        'start_at' => '2030-05-10 10:00:00',
        'end_at'   => '2030-05-10 12:00:00',
    ];
    $icsUtf8 = CalendarIcs::exportEventsToIcs([$utf8LongEvent], 'UTF8-Kalender');
    assert_cal(mb_check_encoding($icsUtf8, 'UTF-8'), '.ics Line-Folding darf keine UTF-8 Umlaute zerschneiden');

    $ics = CalendarIcs::exportEventsToIcs([$pubSave['event']], 'Test-Kalender');
    assert_cal(strpos($ics, 'BEGIN:VCALENDAR') !== false && strpos($ics, 'BEGIN:VEVENT') !== false && strpos($ics, 'Frühlings-Workshop') !== false, '.ics Export ungültig');
    $parsedIcs = CalendarIcs::parseIcsToEvents($ics);
    assert_cal(count($parsedIcs) === 1 && $parsedIcs[0]['title'] === 'Frühlings-Workshop', '.ics Parser fehlgeschlagen');

    $importRes = CalendarSync::importIcsContent($ics, 'ical_feed');
    assert_cal($importRes['success'] && ($importRes['updated'] + $importRes['imported']) === 1, '.ics UID-Upsert fehlgeschlagen');

    assert_cal(!CalendarSync::validateExternalUrl('http://127.0.0.1/cal'), 'HTTP / Loopback CalDAV-URL muss wegen SSRF abgelehnt werden');
    assert_cal(!CalendarSync::validateExternalUrl('https://169.254.169.254/latest'), 'Link-Local Metadata-URL muss abgelehnt werden');
    assert_cal(CalendarSync::validateExternalUrl('https://caldav.icloud.com/123/calendars/'), 'Gültige iCloud-HTTPS-URL muss akzeptiert werden');

    // 5. CalendarCrypto Verschlüsselung & Entschlüsselung (AES-256)
    $secret = 'test_key_' . bin2hex(random_bytes(12));
    $cipher = CalendarCrypto::encrypt('refresh_tok_12345', $secret);
    assert_cal($cipher !== 'refresh_tok_12345' && strpos($cipher, 'enc:v1:') === 0, 'Token muss verschlüsselt sein');
    assert_cal(CalendarCrypto::decrypt($cipher, $secret) === 'refresh_tok_12345', 'Entschlüsselung muss Originalwert liefern');

    // 6. SimpleMailer MIME mit .ics-Anhang + CalendarReminderService Deduplizierung
    $mime = SimpleMailer::buildMimeMessage('<p>Test</p>', 'Test', [['filename' => 'invite.ics', 'content' => $ics, 'mime' => 'text/calendar']]);
    assert_cal(strpos($mime['content_type'], 'multipart/mixed') !== false && strpos($mime['body'], 'invite.ics') !== false, 'SimpleMailer Anhangs-MIME fehlgeschlagen');

    $remNow = strtotime('2030-07-01 09:30:00');
    CalendarManager::saveEvent([
        'id'          => 'evt_rem',
        'title'       => 'Erinnerungstest',
        'start_at'    => '2030-07-01 10:00:00',
        'end_at'      => '2030-07-01 11:00:00',
        'staff_ids'   => [$staff1['id']],
        'attendees'   => [['name' => 'Kunde', 'email' => 'kunde@example.com']],
        'reminders'   => [60], // Fällig ab 09:00
        'visibility'  => 'internal',
    ]);
    $capturedMails = [];
    $sender = static function (string $to, string $subj, string $html, string $text, array $att) use (&$capturedMails): array {
        $capturedMails[] = ['to' => $to, 'subj' => $subj, 'has_ics' => !empty($att[0]['content'])];
        return ['success' => true];
    };
    $run1 = CalendarReminderService::processDueReminders($remNow, $sender);
    assert_cal($run1['sent'] === 2 && count($capturedMails) === 2 && $capturedMails[0]['has_ics'], 'Es müssen 2 Erinnerungen mit .ics-Anhang versendet werden');
    $run2 = CalendarReminderService::processDueReminders($remNow + 60, $sender);
    assert_cal($run2['sent'] === 0 && $run2['skipped'] === 2, 'Zweiter Durchlauf darf keine Duplikate versenden');

    // 7. Frontend Shortcode [calendar] mit <code>-Schutz & CSP Nonce
    $raw = '<p>Termine:</p>[calendar view="month"]<code>[calendar view="week"]</code>';
    $rendered = PluginManager::applyFilters('content_shortcodes', $raw, []);
    assert_cal(strpos($rendered, '<code>[calendar view="week"]</code>') !== false, '[calendar] im <code> Block muss unverändert bleiben');
    assert_cal(strpos($rendered, 'class="card weave-calendar-widget"') !== false, 'Frontend Kalender-Widget fehlt');
    assert_cal(strpos($rendered, 'Frühlings-Workshop') !== false || strpos($rendered, 'Kalender') !== false, 'Frontend-Output unvollständig');
    assert_cal(preg_match('/<script\s+nonce="[^"]+"/', $rendered) === 1, 'Kalender-Script muss CSP-Nonce tragen');
    assert_cal(!preg_match('/\son(click|submit|change)=/i', $rendered), 'Frontend-Widget darf keine Inline-Handler enthalten');

    echo "ALL WEAVE-CALENDAR TESTS PASSED (7/7)\n";
} finally {
    CalendarManager::setTestStorageDir(null);
    $files = @scandir($tmpDir) ?: [];
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            @unlink($tmpDir . DIRECTORY_SEPARATOR . $f);
        }
    }
    @rmdir($tmpDir);
}
