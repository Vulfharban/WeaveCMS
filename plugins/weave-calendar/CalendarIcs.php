<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – iCalendar RFC 5545 Parser & Generator
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class CalendarIcs {

    public static function escapeText(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\n", ';', ','], ['\n', '\;', '\,'], $text);
        return $text;
    }

    public static function unescapeText(string $text): string {
        $text = str_replace(['\n', '\N'], "\n", $text);
        $text = str_replace(['\,', '\;', '\\\\'], [',', ';', '\\'], $text);
        return $text;
    }

    protected static function foldLine(string $line): string {
        if (strlen($line) <= 74) {
            return $line;
        }
        $chunks = [];
        $first = true;
        while ($line !== '') {
            $limit = $first ? 74 : 73;
            $part = function_exists('mb_strcut') ? mb_strcut($line, 0, $limit, 'UTF-8') : substr($line, 0, $limit);
            if ($part === '') {
                break;
            }
            $chunks[] = ($first ? '' : ' ') . $part;
            $line = substr($line, strlen($part));
            $first = false;
        }
        return implode("\r\n", $chunks);
    }

    protected static function formatUtcStamp(string $dt): string {
        $ts = strtotime($dt);
        if ($ts === false) {
            $ts = time();
        }
        return gmdate('Ymd\THis\Z', $ts);
    }

    public static function exportEventsToIcs(array $events, string $calName = 'WeaveCMS Kalender', string $method = 'PUBLISH'): string {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//WeaveCMS//Weave Calendar 1.0//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:' . strtoupper(preg_replace('/[^A-Z]/', '', $method) ?: 'PUBLISH'),
            self::foldLine('X-WR-CALNAME:' . self::escapeText($calName)),
        ];

        $nowStamp = gmdate('Ymd\THis\Z');

        foreach ($events as $ev) {
            $uid = trim((string)($ev['uid'] ?? ($ev['id'] ?? bin2hex(random_bytes(8))) . '@weavecms.local'));
            $allDay = !empty($ev['all_day']);
            $startTs = strtotime((string)($ev['start_at'] ?? 'now')) ?: time();
            $endTs = strtotime((string)($ev['end_at'] ?? date('Y-m-d H:i:s', $startTs + 3600))) ?: ($startTs + 3600);
            if ($endTs <= $startTs) {
                $endTs = $startTs + ($allDay ? 86400 : 3600);
            }

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = self::foldLine('UID:' . $uid);
            $lines[] = 'DTSTAMP:' . $nowStamp;

            if ($allDay) {
                $lines[] = 'DTSTART;VALUE=DATE:' . date('Ymd', $startTs);
                $lines[] = 'DTEND;VALUE=DATE:' . date('Ymd', $endTs);
            } else {
                $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $startTs);
                $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $endTs);
            }

            $lines[] = self::foldLine('SUMMARY:' . self::escapeText((string)($ev['title'] ?? 'Termin')));

            $desc = (string)($ev['description'] ?? '');
            if (!empty($ev['meeting_url'])) {
                $desc .= ($desc !== '' ? "\n\n" : '') . 'Online-Meeting: ' . (string)$ev['meeting_url'];
            }
            if ($desc !== '') {
                $lines[] = self::foldLine('DESCRIPTION:' . self::escapeText($desc));
            }
            if (!empty($ev['location'])) {
                $lines[] = self::foldLine('LOCATION:' . self::escapeText((string)$ev['location']));
            }
            if (!empty($ev['meeting_url'])) {
                $lines[] = self::foldLine('URL:' . str_replace(["\r", "\n"], '', (string)$ev['meeting_url']));
            }

            $statusMap = [
                'confirmed' => 'CONFIRMED',
                'tentative' => 'TENTATIVE',
                'cancelled' => 'CANCELLED',
            ];
            $icsStatus = $statusMap[(string)($ev['status'] ?? 'confirmed')] ?? 'CONFIRMED';
            $lines[] = 'STATUS:' . $icsStatus;

            if (!empty($ev['rrule'])) {
                $rrule = preg_replace('/^RRULE:/i', '', trim((string)$ev['rrule']));
                if ($rrule !== '') {
                    $lines[] = self::foldLine('RRULE:' . $rrule);
                }
            }

            if (!empty($ev['exdates']) && is_array($ev['exdates'])) {
                $exParts = [];
                foreach ($ev['exdates'] as $ex) {
                    $exTs = strtotime((string)$ex);
                    if ($exTs !== false) {
                        $exParts[] = $allDay ? date('Ymd', $exTs) : gmdate('Ymd\THis\Z', $exTs);
                    }
                }
                if (!empty($exParts)) {
                    $lines[] = self::foldLine('EXDATE' . ($allDay ? ';VALUE=DATE:' : ':') . implode(',', $exParts));
                }
            }

            foreach ((array)($ev['attendees'] ?? []) as $att) {
                $email = trim((string)($att['email'] ?? ''));
                $name = str_replace(['"', "\r", "\n", ';', ':'], '', trim((string)($att['name'] ?? '')));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $cn = $name !== '' ? ';CN="' . $name . '"' : '';
                    $lines[] = self::foldLine("ATTENDEE{$cn}:mailto:{$email}");
                }
            }

            if (!empty($ev['updated_at'])) {
                $lines[] = 'LAST-MODIFIED:' . self::formatUtcStamp((string)$ev['updated_at']);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }

    public static function parseIcsToEvents(string $icsContent): array {
        $unfolded = preg_replace("/\r?\n[ \t]/", '', str_replace("\r\n", "\n", $icsContent));
        $rawLines = explode("\n", (string)$unfolded);

        $events = [];
        $current = null;

        foreach ($rawLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === 'BEGIN:VEVENT') {
                $current = [
                    'uid'         => '',
                    'title'       => 'Termin',
                    'description' => '',
                    'location'    => '',
                    'meeting_url' => '',
                    'start_at'    => '',
                    'end_at'      => '',
                    'all_day'     => false,
                    'rrule'       => '',
                    'status'      => 'confirmed',
                    'updated_at'  => '',
                ];
                continue;
            }
            if ($trimmed === 'END:VEVENT') {
                if (is_array($current) && $current['start_at'] !== '') {
                    if ($current['end_at'] === '') {
                        $st = strtotime($current['start_at']);
                        $current['end_at'] = $st ? date('Y-m-d H:i:s', $st + ($current['all_day'] ? 86400 : 3600)) : $current['start_at'];
                    }
                    $events[] = $current;
                }
                $current = null;
                continue;
            }
            if (!is_array($current)) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }
            $propFull = substr($line, 0, $colonPos);
            $val = substr($line, $colonPos + 1);
            $propParts = explode(';', $propFull);
            $propName = strtoupper(trim($propParts[0]));

            if ($propName === 'UID') {
                $current['uid'] = trim($val);
            } elseif ($propName === 'SUMMARY') {
                $current['title'] = self::unescapeText(trim($val));
            } elseif ($propName === 'DESCRIPTION') {
                $current['description'] = self::unescapeText($val);
            } elseif ($propName === 'LOCATION') {
                $current['location'] = self::unescapeText(trim($val));
            } elseif ($propName === 'URL') {
                $current['meeting_url'] = trim($val);
            } elseif ($propName === 'RRULE') {
                $current['rrule'] = strtoupper(trim($val));
            } elseif ($propName === 'STATUS') {
                $s = strtolower(trim($val));
                if (in_array($s, ['confirmed', 'tentative', 'cancelled'], true)) {
                    $current['status'] = $s;
                }
            } elseif ($propName === 'LAST-MODIFIED') {
                $ts = strtotime(trim($val));
                if ($ts !== false) {
                    $current['updated_at'] = date('Y-m-d H:i:s', $ts);
                }
            } elseif ($propName === 'DTSTART' || $propName === 'DTEND') {
                $rawVal = trim($val);
                $isDateOnly = (strlen($rawVal) === 8 && ctype_digit($rawVal)) || stripos($propFull, 'VALUE=DATE') !== false;
                $ts = strtotime($rawVal);
                if ($ts !== false) {
                    $formatted = $isDateOnly
                        ? date('Y-m-d', $ts) . ' 00:00:00'
                        : date('Y-m-d H:i:s', $ts);
                    if ($propName === 'DTSTART') {
                        $current['start_at'] = $formatted;
                        $current['all_day'] = $isDateOnly;
                    } else {
                        $current['end_at'] = $formatted;
                    }
                }
            }
        }

        return $events;
    }
}
