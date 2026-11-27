<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Erinnerungs-Engine (Phase 36.4)
 * ============================================================================
 * Prüft fällige Termin-Erinnerungen (z. B. 24h, 1h, 15m vor Beginn), versendet
 * über SimpleMailer inkl. .ics-Kalenderanhang und protokolliert sent_reminders
 * gegen Doppelversand.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class CalendarReminderService {

    /**
     * Prüft und versendet alle aktuell fälligen Termin-Erinnerungen.
     *
     * @param int|null      $now        Referenz-Timestamp (für Tests überschreibbar)
     * @param callable|null $mailSender Optionales Versand-Callback ($to, $subject, $html, $text, $attachments) => ['success' => bool]
     * @param array|null    $smtpConfig Optionale SMTP-Konfiguration
     * @return array{checked: int, sent: int, skipped: int, errors: array}
     */
    public static function processDueReminders(?int $now = null, ?callable $mailSender = null, ?array $smtpConfig = null): array {
        $nowTs = $now ?? time();
        $rangeStart = date('Y-m-d H:i:s', $nowTs);
        $rangeEnd = date('Y-m-d H:i:s', $nowTs + 48 * 3600);

        $occurrences = CalendarManager::expandEventsForRange($rangeStart, $rangeEnd);
        $staffMap = [];
        foreach (CalendarManager::getStaff() as $st) {
            $staffMap[$st['id']] = $st;
        }

        $remindersStore = CalendarManager::getRemindersStore();
        $sentMap = $remindersStore['sent'] ?? [];

        $checked = 0;
        $sent = 0;
        $skipped = 0;
        $errors = [];
        $dirty = false;

        foreach ($occurrences as $occ) {
            if (($occ['status'] ?? 'confirmed') === 'cancelled') {
                continue;
            }
            $occStartTs = strtotime((string)($occ['occurrence_start'] ?? ''));
            if ($occStartTs === false || $occStartTs <= $nowTs) {
                continue;
            }

            $leadMinutesList = (array)($occ['reminders'] ?? []);
            if (empty($leadMinutesList)) {
                continue;
            }

            // Empfänger sammeln (zugewiesene Mitarbeiter + externe Teilnehmer)
            $recipients = [];
            foreach ((array)($occ['staff_ids'] ?? []) as $sid) {
                if (!empty($staffMap[$sid]['email']) && filter_var($staffMap[$sid]['email'], FILTER_VALIDATE_EMAIL)) {
                    $em = strtolower((string)$staffMap[$sid]['email']);
                    $recipients[$em] = (string)($staffMap[$sid]['name'] ?? $em);
                }
            }
            foreach ((array)($occ['attendees'] ?? []) as $att) {
                $em = strtolower(trim((string)($att['email'] ?? '')));
                if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                    $recipients[$em] = trim((string)($att['name'] ?? '')) ?: $em;
                }
            }
            if (empty($recipients)) {
                continue;
            }

            $icsPayload = CalendarIcs::exportEventsToIcs([$occ], 'WeaveCMS Termin', 'REQUEST');
            $attachment = [[
                'filename' => 'termin-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($occ['id'] ?? 'event')) . '.ics',
                'content'  => $icsPayload,
                'mime'     => 'text/calendar; method=REQUEST; charset=UTF-8',
            ]];

            foreach ($leadMinutesList as $rawLead) {
                $leadMin = (int)$rawLead;
                if ($leadMin <= 0) {
                    continue;
                }
                $triggerTs = $occStartTs - ($leadMin * 60);
                if ($nowTs < $triggerTs) {
                    continue;
                }

                foreach ($recipients as $email => $recName) {
                    $checked++;
                    $dedupeKey = md5($occ['id'] . '|' . $occ['occurrence_start'] . '|' . $leadMin . '|' . $email);
                    if (isset($sentMap[$dedupeKey])) {
                        $skipped++;
                        continue;
                    }

                    $whenLabel = date('d.m.Y \u\m H:i', $occStartTs) . ' Uhr';
                    $subject = 'Terminerinnerung: ' . (string)$occ['title'] . ' (' . $whenLabel . ')';
                    $textBody = "Hallo {$recName},\n\n"
                        . "Erinnerung an deinen bevorstehenden Termin:\n"
                        . "Termin: {$occ['title']}\n"
                        . "Beginn: {$whenLabel}\n"
                        . (!empty($occ['location']) ? "Ort: {$occ['location']}\n" : '')
                        . (!empty($occ['meeting_url']) ? "Online-Link: {$occ['meeting_url']}\n" : '')
                        . "\nIm Anhang findest du die passende .ics-Kalenderdatei.\n";

                    $htmlBody = '<div style="font-family:sans-serif;line-height:1.6;color:#1f2937;">'
                        . '<h2 style="color:#0f766e;">Terminerinnerung</h2>'
                        . '<p>Hallo ' . htmlspecialchars($recName, ENT_QUOTES, 'UTF-8') . ',</p>'
                        . '<p>dies ist eine automatische Erinnerung an folgenden Termin:</p>'
                        . '<ul>'
                        . '<li><strong>Termin:</strong> ' . htmlspecialchars((string)$occ['title'], ENT_QUOTES, 'UTF-8') . '</li>'
                        . '<li><strong>Beginn:</strong> ' . htmlspecialchars($whenLabel, ENT_QUOTES, 'UTF-8') . '</li>'
                        . (!empty($occ['location']) ? '<li><strong>Ort:</strong> ' . htmlspecialchars((string)$occ['location'], ENT_QUOTES, 'UTF-8') . '</li>' : '')
                        . (!empty($occ['meeting_url']) ? '<li><strong>Meeting:</strong> <a href="' . htmlspecialchars((string)$occ['meeting_url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$occ['meeting_url'], ENT_QUOTES, 'UTF-8') . '</a></li>' : '')
                        . '</ul>'
                        . '<p>Eine <code>.ics</code>-Kalenderdatei ist dieser E-Mail angehängt.</p>'
                        . '</div>';

                    if ($mailSender !== null) {
                        $res = $mailSender($email, $subject, $htmlBody, $textBody, $attachment);
                    } elseif (class_exists('SimpleMailer')) {
                        $res = SimpleMailer::send($email, $subject, $htmlBody, $textBody, '', '', $smtpConfig, $attachment);
                    } else {
                        $res = ['success' => false, 'error' => 'SimpleMailer nicht verfügbar.'];
                    }

                    if (!empty($res['success'])) {
                        $sent++;
                        $sentMap[$dedupeKey] = [
                            'event_id'   => $occ['id'],
                            'occ_start'  => $occ['occurrence_start'],
                            'lead_min'   => $leadMin,
                            'recipient'  => $email,
                            'sent_at'    => date('Y-m-d H:i:s', $nowTs),
                        ];
                        $dirty = true;
                    } else {
                        $errors[] = $email . ': ' . (string)($res['error'] ?? 'Versandfehler');
                    }
                }
            }
        }

        if ($dirty) {
            $remindersStore['sent'] = $sentMap;
            CalendarManager::saveRemindersStore($remindersStore);
        }

        return [
            'checked' => $checked,
            'sent'    => $sent,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }
}
