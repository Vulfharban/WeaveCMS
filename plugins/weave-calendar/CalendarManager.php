<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – CalendarManager (Phase 36)
 * ============================================================================
 * Kern-Engine für Termine, Serientermine (RRULE), Konfliktprüfung,
 * Mitarbeiter-Ressourcen, Kategorien, Buchungsanfragen, RBAC und DSGVO.
 * Nutzt StorageManager (MySQL mit Prefix oder atomares JSON).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class CalendarManager {

    protected static ?string $testStorageDir = null;
    protected static ?array $dataCache = null;
    protected static ?array $secretsCache = null;
    protected static ?array $remindersCache = null;

    public static function clearCache(): void {
        self::$dataCache = null;
        self::$secretsCache = null;
        self::$remindersCache = null;
    }

    public static function setTestStorageDir(?string $dir): void {
        self::$testStorageDir = $dir !== null ? rtrim($dir, '/\\') : null;
        self::clearCache();
    }

    protected static function getDataDir(): string {
        return self::$testStorageDir ?? (dirname(__DIR__, 2) . '/data');
    }

    protected static function loadPluginStore(string $key, string $filename, array $default): array {
        $file = self::getDataDir() . DIRECTORY_SEPARATOR . $filename;
        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            $loaded = StorageManager::loadPluginData($key, null, $file);
            if (is_array($loaded)) {
                return $loaded;
            }
        }
        if (file_exists($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $default;
    }

    protected static function savePluginStore(string $key, string $filename, array $payload): bool {
        $file = self::getDataDir() . DIRECTORY_SEPARATOR . $filename;
        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            return StorageManager::savePluginData($key, $payload, $file);
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(5));
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        return @rename($tmp, $file);
    }

    public static function getDefaultConfig(bool $generateTokens = false): array {
        return [
            'section_title'              => 'Kalender & Termine',
            'section_subtitle'           => 'Alle anstehenden Kurse, Termine und Team-Veranstaltungen im Überblick.',
            'show_in_main_nav'           => false,
            'default_view'               => 'month',
            'timezone'                   => 'Europe/Berlin',
            'allow_public_booking'       => true,
            'hide_private_staff_details' => true,
            'default_reminders'          => [1440, 60, 15],
            'conflict_resolution'        => 'newest',
            'auto_pseudo_cron'           => true,
            'feed_token'                 => $generateTokens ? bin2hex(random_bytes(16)) : '',
            'cron_token'                 => $generateTokens ? bin2hex(random_bytes(16)) : '',
        ];
    }

    public static function getDefaultData(): array {
        return [
            'config'     => self::getDefaultConfig(true),
            'categories' => [
                ['id' => 'cat_public',   'name' => 'Öffentliche Termine', 'color' => '#0f766e', 'is_public' => true],
                ['id' => 'cat_course',   'name' => 'Kurse & Workshops',   'color' => '#2563eb', 'is_public' => true],
                ['id' => 'cat_customer', 'name' => 'Kundentermine',       'color' => '#d97706', 'is_public' => false],
                ['id' => 'cat_vacation', 'name' => 'Urlaub / Abwesenheit','color' => '#dc2626', 'is_public' => false],
            ],
            'staff'      => [
                ['id' => 'staff_admin', 'name' => 'Praxisleitung / Admin', 'email' => '', 'role' => 'admin', 'color' => '#0f766e', 'status' => 'available', 'active' => true],
            ],
            'events'     => [],
            'bookings'   => [],
        ];
    }

    public static function getData(): array {
        if (self::$dataCache !== null) {
            return self::$dataCache;
        }
        $default = self::getDefaultData();
        $loaded = self::loadPluginStore('calendar_data', 'calendar_data.json', []);
        if (empty($loaded)) {
            self::saveData($default);
            return self::$dataCache ?? $default;
        }

        $cfgDefaults = self::getDefaultConfig(false);
        $loaded['config'] = array_merge($cfgDefaults, is_array($loaded['config'] ?? null) ? $loaded['config'] : []);
        $needsSave = false;
        if (empty($loaded['config']['feed_token'])) {
            $loaded['config']['feed_token'] = bin2hex(random_bytes(16));
            $needsSave = true;
        }
        if (empty($loaded['config']['cron_token'])) {
            $loaded['config']['cron_token'] = bin2hex(random_bytes(16));
            $needsSave = true;
        }
        foreach (['categories', 'staff', 'events', 'bookings'] as $listKey) {
            if (!isset($loaded[$listKey]) || !is_array($loaded[$listKey])) {
                $loaded[$listKey] = $default[$listKey];
            }
        }
        self::$dataCache = $loaded;
        if ($needsSave) {
            self::saveData($loaded);
        }
        return self::$dataCache;
    }

    public static function saveData(array $data): bool {
        $ok = self::savePluginStore('calendar_data', 'calendar_data.json', $data);
        if ($ok) {
            self::$dataCache = $data;
        }
        return $ok;
    }

    public static function getSecrets(): array {
        if (self::$secretsCache !== null) {
            return self::$secretsCache;
        }
        $loaded = self::loadPluginStore('calendar_secrets', 'calendar_secrets.json', []);
        $needsSave = false;
        if (empty($loaded['app_key'])) {
            $loaded['app_key'] = bin2hex(random_bytes(32));
            $needsSave = true;
        }
        if (!isset($loaded['providers']) || !is_array($loaded['providers'])) {
            $loaded['providers'] = [];
        }
        if (!isset($loaded['sync_log']) || !is_array($loaded['sync_log'])) {
            $loaded['sync_log'] = [];
        }
        self::$secretsCache = $loaded;
        if ($needsSave) {
            self::saveSecrets($loaded);
        }
        return self::$secretsCache;
    }

    public static function saveSecrets(array $secrets): bool {
        $ok = self::savePluginStore('calendar_secrets', 'calendar_secrets.json', $secrets);
        if ($ok) {
            self::$secretsCache = $secrets;
        }
        return $ok;
    }

    public static function getRemindersStore(): array {
        if (self::$remindersCache !== null) {
            return self::$remindersCache;
        }
        $loaded = self::loadPluginStore('calendar_reminders', 'calendar_reminders.json', ['sent' => []]);
        if (!isset($loaded['sent']) || !is_array($loaded['sent'])) {
            $loaded['sent'] = [];
        }
        self::$remindersCache = $loaded;
        return self::$remindersCache;
    }

    public static function saveRemindersStore(array $store): bool {
        if (isset($store['sent']) && count($store['sent']) > 5000) {
            $store['sent'] = array_slice($store['sent'], -3000, null, true);
        }
        $ok = self::savePluginStore('calendar_reminders', 'calendar_reminders.json', $store);
        if ($ok) {
            self::$remindersCache = $store;
        }
        return $ok;
    }

    public static function getConfig(): array {
        $data = self::getData();
        return $data['config'] ?? self::getDefaultConfig(false);
    }

    public static function saveConfig(array $input): bool {
        $data = self::getData();
        $allowedViews = ['day', 'week', 'month', 'agenda'];
        $rawView = (string)($input['default_view'] ?? 'month');
        $defView = in_array($rawView, $allowedViews, true) ? $rawView : 'month';
        $allowedRes = ['newest', 'local', 'remote'];
        $rawRes = (string)($input['conflict_resolution'] ?? 'newest');
        $resMode = in_array($rawRes, $allowedRes, true) ? $rawRes : 'newest';

        $reminders = [];
        if (isset($input['default_reminders'])) {
            $rawList = is_array($input['default_reminders'])
                ? $input['default_reminders']
                : preg_split('/[\s,;]+/', (string)$input['default_reminders']);
            foreach ($rawList as $m) {
                $min = (int)$m;
                if ($min > 0 && $min <= 43200 && !in_array($min, $reminders, true)) {
                    $reminders[] = $min;
                }
            }
        } else {
            $reminders = $data['config']['default_reminders'] ?? [1440, 60, 15];
        }
        rsort($reminders);

        $data['config'] = array_merge($data['config'], [
            'section_title'              => trim((string)($input['section_title'] ?? 'Kalender & Termine')),
            'section_subtitle'           => trim((string)($input['section_subtitle'] ?? '')),
            'show_in_main_nav'           => !empty($input['show_in_main_nav']),
            'default_view'               => $defView,
            'timezone'                   => trim((string)($input['timezone'] ?? 'Europe/Berlin')) ?: 'Europe/Berlin',
            'allow_public_booking'       => !empty($input['allow_public_booking']),
            'hide_private_staff_details' => !empty($input['hide_private_staff_details']),
            'conflict_resolution'        => $resMode,
            'auto_pseudo_cron'           => !empty($input['auto_pseudo_cron']),
            'default_reminders'          => $reminders,
        ]);

        return self::saveData($data);
    }

    public static function sanitizeId(string $raw): string {
        $s = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower(trim($raw)));
        return substr(trim((string)preg_replace('/-+/', '-', (string)$s), '-_'), 0, 64);
    }

    // =========================================================================
    // KATEGORIEN & MITARBEITER
    // =========================================================================

    public static function getCategories(): array {
        return array_values(self::getData()['categories'] ?? []);
    }

    public static function saveCategory(array $input): array {
        $data = self::getData();
        $id = self::sanitizeId((string)($input['id'] ?? ''));
        $name = trim((string)($input['name'] ?? 'Kategorie'));
        if ($id === '') {
            $id = 'cat_' . bin2hex(random_bytes(4));
        }
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($input['color'] ?? '')) ? (string)$input['color'] : '#0f766e';
        $cat = [
            'id'        => $id,
            'name'      => $name !== '' ? $name : 'Kategorie',
            'color'     => $color,
            'is_public' => !empty($input['is_public']),
        ];

        $updated = false;
        foreach ($data['categories'] as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c = $cat;
                $updated = true;
                break;
            }
        }
        unset($c);
        if (!$updated) {
            $data['categories'][] = $cat;
        }
        self::saveData($data);
        return $cat;
    }

    public static function deleteCategory(string $id): bool {
        $data = self::getData();
        $before = count($data['categories']);
        $data['categories'] = array_values(array_filter($data['categories'], static fn(array $c): bool => ($c['id'] ?? '') !== $id));
        if (count($data['categories']) === $before) {
            return false;
        }
        return self::saveData($data);
    }

    public static function getStaff(bool $activeOnly = false): array {
        $list = array_values(self::getData()['staff'] ?? []);
        if ($activeOnly) {
            $list = array_values(array_filter($list, static fn(array $s): bool => !empty($s['active'])));
        }
        return $list;
    }

    public static function saveStaff(array $input): array {
        $data = self::getData();
        $id = self::sanitizeId((string)($input['id'] ?? ''));
        if ($id === '') {
            $id = 'staff_' . bin2hex(random_bytes(4));
        }
        $allowedRoles = ['admin', 'coordinator', 'member', 'viewer'];
        $rawRole = (string)($input['role'] ?? 'member');
        $role = in_array($rawRole, $allowedRoles, true) ? $rawRole : 'member';
        $allowedStatuses = ['available', 'vacation', 'sick', 'busy'];
        $rawStatus = (string)($input['status'] ?? 'available');
        $status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'available';
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($input['color'] ?? '')) ? (string)$input['color'] : '#2563eb';
        $email = trim((string)($input['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        $staff = [
            'id'     => $id,
            'name'   => trim((string)($input['name'] ?? 'Mitarbeiter')) ?: 'Mitarbeiter',
            'email'  => $email,
            'role'   => $role,
            'color'  => $color,
            'status' => $status,
            'active' => array_key_exists('active', $input) ? !empty($input['active']) : true,
        ];

        $updated = false;
        foreach ($data['staff'] as &$s) {
            if (($s['id'] ?? '') === $id) {
                $s = $staff;
                $updated = true;
                break;
            }
        }
        unset($s);
        if (!$updated) {
            $data['staff'][] = $staff;
        }
        self::saveData($data);
        return $staff;
    }

    public static function deleteStaff(string $id): bool {
        $data = self::getData();
        $before = count($data['staff']);
        $data['staff'] = array_values(array_filter($data['staff'], static fn(array $s): bool => ($s['id'] ?? '') !== $id));
        if (count($data['staff']) === $before) {
            return false;
        }
        return self::saveData($data);
    }

    // =========================================================================
    // TERMINE & CRUD
    // =========================================================================

    public static function getEvents(): array {
        $events = array_values(self::getData()['events'] ?? []);
        usort($events, static fn(array $a, array $b): int => strcmp((string)($a['start_at'] ?? ''), (string)($b['start_at'] ?? '')));
        return $events;
    }

    public static function getEvent(string $id): ?array {
        foreach (self::getData()['events'] ?? [] as $ev) {
            if (($ev['id'] ?? '') === $id) {
                return $ev;
            }
        }
        return null;
    }

    public static function getEventByUid(string $uid): ?array {
        $uid = trim($uid);
        if ($uid === '') {
            return null;
        }
        foreach (self::getData()['events'] ?? [] as $ev) {
            if (($ev['uid'] ?? '') === $uid || ($ev['external_id'] ?? '') === $uid) {
                return $ev;
            }
        }
        return null;
    }

    protected static function parseAttendeesInput($raw): array {
        if (is_array($raw)) {
            $clean = [];
            foreach ($raw as $item) {
                if (is_array($item) && !empty($item['email']) && filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                    $clean[] = [
                        'name'  => trim((string)($item['name'] ?? '')),
                        'email' => strtolower(trim((string)$item['email'])),
                    ];
                }
            }
            return $clean;
        }
        $lines = preg_split('/[\r\n,;]+/', (string)$raw);
        $clean = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(.*?)<([^>]+)>$/', $line, $m)) {
                $email = strtolower(trim($m[2]));
                $name = trim($m[1], " \t\"'");
            } else {
                $email = strtolower($line);
                $name = '';
            }
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $clean[] = ['name' => $name, 'email' => $email];
            }
        }
        return $clean;
    }

    public static function normalizeRrule(array $input): string {
        if (!empty($input['rrule']) && is_string($input['rrule'])) {
            return strtoupper(preg_replace('/^RRULE:/i', '', trim($input['rrule'])));
        }
        $freq = strtoupper(trim((string)($input['recur_freq'] ?? '')));
        if (!in_array($freq, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
            return '';
        }
        $parts = ['FREQ=' . $freq];
        $interval = max(1, (int)($input['recur_interval'] ?? 1));
        if ($interval > 1) {
            $parts[] = 'INTERVAL=' . $interval;
        }
        $count = (int)($input['recur_count'] ?? 0);
        if ($count > 0 && $count <= 500) {
            $parts[] = 'COUNT=' . $count;
        }
        $untilRaw = trim((string)($input['recur_until'] ?? ''));
        if ($untilRaw !== '') {
            $ts = strtotime($untilRaw . ' 23:59:59');
            if ($ts !== false) {
                $parts[] = 'UNTIL=' . gmdate('Ymd\THis\Z', $ts);
            }
        }
        return implode(';', $parts);
    }

    public static function saveEvent(array $input): array {
        $data = self::getData();
        $id = trim((string)($input['id'] ?? ''));
        $existingIdx = null;
        if ($id !== '') {
            foreach ($data['events'] as $idx => $ev) {
                if (($ev['id'] ?? '') === $id) {
                    $existingIdx = $idx;
                    break;
                }
            }
        } else {
            $id = 'evt_' . bin2hex(random_bytes(6));
        }

        $cfg = $data['config'] ?? self::getDefaultConfig(false);
        $existing = $existingIdx !== null ? $data['events'][$existingIdx] : [];

        $allDay = !empty($input['all_day']);
        $startTs = strtotime((string)($input['start_at'] ?? 'now')) ?: time();
        $endTs = strtotime((string)($input['end_at'] ?? '')) ?: ($startTs + ($allDay ? 86400 : 3600));
        if ($allDay) {
            $startDay = strtotime(date('Y-m-d', $startTs) . ' 00:00:00') ?: $startTs;
            $endDay = strtotime(date('Y-m-d', $endTs) . ' 00:00:00') ?: $startDay;
            if ($endDay <= $startDay) {
                $endDay = strtotime('+1 day', $startDay);
            }
            $startTs = $startDay;
            $endTs = $endDay;
        } elseif ($endTs <= $startTs) {
            $endTs = $startTs + 3600;
        }

        $allowedStatuses = ['confirmed', 'tentative', 'cancelled'];
        $rawStatus = (string)($input['status'] ?? 'confirmed');
        $status = in_array($rawStatus, $allowedStatuses, true)
            ? $rawStatus
            : 'confirmed';

        $allowedVis = ['public', 'internal', 'private'];
        $rawVis = (string)($input['visibility'] ?? 'public');
        $visibility = in_array($rawVis, $allowedVis, true)
            ? $rawVis
            : 'public';

        $staffIds = array_values(array_filter(array_map('trim', (array)($input['staff_ids'] ?? []))));

        $reminders = [];
        if (array_key_exists('reminders', $input)) {
            $rawRem = is_array($input['reminders'])
                ? $input['reminders']
                : preg_split('/[\s,;]+/', (string)$input['reminders']);
            foreach ($rawRem as $rm) {
                $m = (int)$rm;
                if ($m > 0 && $m <= 43200 && !in_array($m, $reminders, true)) {
                    $reminders[] = $m;
                }
            }
        } elseif ($existingIdx !== null) {
            $reminders = $existing['reminders'] ?? ($cfg['default_reminders'] ?? [1440, 60]);
        } else {
            $reminders = $cfg['default_reminders'] ?? [1440, 60];
        }
        rsort($reminders);

        $exdates = [];
        $rawEx = $input['exdates'] ?? ($existing['exdates'] ?? []);
        if (is_string($rawEx)) {
            $rawEx = preg_split('/[\r\n,;]+/', $rawEx);
        }
        foreach ((array)$rawEx as $ex) {
            $ex = trim((string)$ex);
            if ($ex !== '' && ($ts = strtotime($ex)) !== false) {
                $d = date('Y-m-d', $ts);
                if (!in_array($d, $exdates, true)) {
                    $exdates[] = $d;
                }
            }
        }

        $nowStr = date('Y-m-d H:i:s');
        $uid = trim((string)($input['uid'] ?? ($existing['uid'] ?? '')));
        if ($uid === '') {
            $uid = $id . '@weavecms.local';
        }

        $event = [
            'id'              => $id,
            'uid'             => $uid,
            'title'           => trim((string)($input['title'] ?? 'Neuer Termin')) ?: 'Neuer Termin',
            'description'     => trim((string)($input['description'] ?? '')),
            'location'        => trim((string)($input['location'] ?? '')),
            'meeting_url'     => trim((string)($input['meeting_url'] ?? '')),
            'start_at'        => $allDay ? date('Y-m-d', $startTs) . ' 00:00:00' : date('Y-m-d H:i:s', $startTs),
            'end_at'          => $allDay ? date('Y-m-d', $endTs) . ' 00:00:00' : date('Y-m-d H:i:s', $endTs),
            'timezone'        => trim((string)($input['timezone'] ?? ($cfg['timezone'] ?? 'Europe/Berlin'))),
            'all_day'         => $allDay,
            'category_id'     => trim((string)($input['category_id'] ?? 'cat_public')),
            'status'          => $status,
            'visibility'      => $visibility,
            'staff_ids'       => $staffIds,
            'attendees'       => self::parseAttendeesInput($input['attendees'] ?? ($existing['attendees'] ?? [])),
            'rrule'           => self::normalizeRrule($input),
            'exdates'         => $exdates,
            'reminders'       => $reminders,
            'external_source' => trim((string)($input['external_source'] ?? ($existing['external_source'] ?? 'local'))),
            'external_id'     => trim((string)($input['external_id'] ?? ($existing['external_id'] ?? ''))),
            'etag'            => trim((string)($input['etag'] ?? ($existing['etag'] ?? ''))),
            'created_by'      => trim((string)($input['created_by'] ?? ($existing['created_by'] ?? 'admin'))),
            'created_at'      => (string)($existing['created_at'] ?? $nowStr),
            'updated_at'      => trim((string)($input['updated_at'] ?? $nowStr)),
        ];

        $conflicts = self::detectStaffConflicts($event, $id);

        if ($existingIdx !== null) {
            $data['events'][$existingIdx] = $event;
        } else {
            $data['events'][] = $event;
        }
        self::saveData($data);

        return ['event' => $event, 'conflicts' => $conflicts];
    }

    public static function deleteEvent(string $id): bool {
        $data = self::getData();
        $before = count($data['events']);
        $data['events'] = array_values(array_filter($data['events'], static fn(array $ev): bool => ($ev['id'] ?? '') !== $id));
        if (count($data['events']) === $before) {
            return false;
        }
        return self::saveData($data);
    }

    // =========================================================================
    // SERIENTERMINE (RRULE RFC 5545) & KONFLIKTPRÜFUNG
    // =========================================================================

    public static function parseRrule(string $rrule): array {
        $out = [];
        $rrule = strtoupper(preg_replace('/^RRULE:/i', '', trim($rrule)));
        if ($rrule === '') {
            return $out;
        }
        foreach (explode(';', $rrule) as $kv) {
            if (strpos($kv, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $kv, 2);
            $out[trim($k)] = trim($v);
        }
        return $out;
    }

    public static function expandSingleEvent(array $ev, int $rangeStartTs, int $rangeEndTs): array {
        $startTs = strtotime((string)($ev['start_at'] ?? ''));
        $endTs = strtotime((string)($ev['end_at'] ?? ''));
        if ($startTs === false) {
            return [];
        }
        if ($endTs === false || $endTs <= $startTs) {
            $endTs = $startTs + (!empty($ev['all_day']) ? 86400 : 3600);
        }
        $duration = $endTs - $startTs;
        $exdates = array_flip((array)($ev['exdates'] ?? []));
        $rrule = self::parseRrule((string)($ev['rrule'] ?? ''));

        if (empty($rrule['FREQ'])) {
            if ($endTs >= $rangeStartTs && $startTs <= $rangeEndTs && !isset($exdates[date('Y-m-d', $startTs)])) {
                $inst = $ev;
                $inst['occurrence_start'] = date('Y-m-d H:i:s', $startTs);
                $inst['occurrence_end'] = date('Y-m-d H:i:s', $endTs);
                $inst['occurrence_ts'] = $startTs;
                return [$inst];
            }
            return [];
        }

        $freq = $rrule['FREQ'];
        $interval = max(1, (int)($rrule['INTERVAL'] ?? 1));
        $maxCount = isset($rrule['COUNT']) ? max(1, min(500, (int)$rrule['COUNT'])) : 500;
        $untilTs = isset($rrule['UNTIL']) ? strtotime($rrule['UNTIL']) : false;

        $instances = [];
        $dt = new DateTimeImmutable(date('Y-m-d H:i:s', $startTs));
        $specMap = [
            'DAILY'   => "+{$interval} days",
            'WEEKLY'  => "+{$interval} weeks",
            'MONTHLY' => "+{$interval} months",
            'YEARLY'  => "+{$interval} years",
        ];
        if (!isset($specMap[$freq])) {
            return [];
        }

        if (!isset($rrule['COUNT']) && $startTs < $rangeStartTs && in_array($freq, ['DAILY', 'WEEKLY'], true)) {
            $stepSec = ($freq === 'DAILY' ? 86400 : 604800) * $interval;
            $skipSteps = (int)floor(($rangeStartTs - $duration - $startTs) / $stepSec);
            if ($skipSteps > 0) {
                $daysToAdd = $skipSteps * $interval * ($freq === 'DAILY' ? 1 : 7);
                $dt = $dt->modify("+{$daysToAdd} days");
            }
        }

        for ($i = 0; $i < $maxCount; $i++) {
            $occStartTs = $dt->getTimestamp();
            if ($untilTs !== false && $occStartTs > $untilTs) {
                break;
            }
            if ($occStartTs > $rangeEndTs) {
                break;
            }
            $occEndTs = $occStartTs + $duration;
            $occDate = date('Y-m-d', $occStartTs);

            if ($occEndTs >= $rangeStartTs && !isset($exdates[$occDate])) {
                $inst = $ev;
                $inst['occurrence_start'] = date('Y-m-d H:i:s', $occStartTs);
                $inst['occurrence_end'] = date('Y-m-d H:i:s', $occEndTs);
                $inst['occurrence_ts'] = $occStartTs;
                $instances[] = $inst;
            }
            $dt = $dt->modify($specMap[$freq]);
        }

        return $instances;
    }

    public static function expandEventsForRange(string $rangeStart, string $rangeEnd, array $filters = []): array {
        $startTs = strtotime($rangeStart) ?: strtotime('-1 month');
        $endTs = strtotime($rangeEnd) ?: strtotime('+1 month');
        $cats = [];
        foreach (self::getCategories() as $c) {
            $cats[$c['id']] = $c;
        }

        $publicOnly = !empty($filters['public_only']);
        $catFilter = trim((string)($filters['category_id'] ?? ''));
        $staffFilter = trim((string)($filters['staff_id'] ?? ''));
        $search = mb_strtolower(trim((string)($filters['search'] ?? '')));

        $cfg = self::getConfig();
        $out = [];

        foreach (self::getEvents() as $ev) {
            if (($ev['status'] ?? 'confirmed') === 'cancelled' && empty($filters['include_cancelled'])) {
                continue;
            }
            $catId = (string)($ev['category_id'] ?? '');
            $catPublic = $cats[$catId]['is_public'] ?? true;
            if ($publicOnly && ((string)($ev['visibility'] ?? 'public') !== 'public' || !$catPublic)) {
                continue;
            }
            if ($catFilter !== '' && $catId !== $catFilter) {
                continue;
            }
            if ($staffFilter !== '' && !in_array($staffFilter, (array)($ev['staff_ids'] ?? []), true)) {
                continue;
            }
            if ($search !== '') {
                $hay = mb_strtolower((string)($ev['title'] ?? '') . ' ' . (string)($ev['description'] ?? '') . ' ' . (string)($ev['location'] ?? ''));
                if (mb_strpos($hay, $search) === false) {
                    continue;
                }
            }

            foreach (self::expandSingleEvent($ev, $startTs, $endTs) as $occ) {
                if ($publicOnly) {
                    $occ = self::sanitizeEventForPublic($occ, $cfg);
                }
                $occ['category_color'] = $cats[$catId]['color'] ?? '#0f766e';
                $occ['category_name'] = $cats[$catId]['name'] ?? 'Termin';
                $out[] = $occ;
            }
        }

        usort($out, static fn(array $a, array $b): int => ($a['occurrence_ts'] ?? 0) <=> ($b['occurrence_ts'] ?? 0));
        return $out;
    }

    /**
     * Erkennt Doppelbelegungen derselben Mitarbeiter in überschneidenden Zeiträumen.
     */
    public static function detectStaffConflicts(array $eventInput, ?string $ignoreEventId = null): array {
        $staffIds = array_values(array_filter((array)($eventInput['staff_ids'] ?? [])));
        if (empty($staffIds) || (($eventInput['status'] ?? 'confirmed') === 'cancelled')) {
            return [];
        }

        $startTs = strtotime((string)($eventInput['start_at'] ?? ''));
        $endTs = strtotime((string)($eventInput['end_at'] ?? ''));
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            return [];
        }

        $staffNames = [];
        foreach (self::getStaff() as $s) {
            $staffNames[$s['id']] = $s['name'];
        }

        $windowStart = date('Y-m-d H:i:s', $startTs - 86400);
        $windowEnd = date('Y-m-d H:i:s', $endTs + 86400);
        $existingOccs = self::expandEventsForRange($windowStart, $windowEnd);

        $conflicts = [];
        foreach ($existingOccs as $occ) {
            if ($ignoreEventId !== null && ($occ['id'] ?? '') === $ignoreEventId) {
                continue;
            }
            $occStart = strtotime((string)$occ['occurrence_start']);
            $occEnd = strtotime((string)$occ['occurrence_end']);
            if ($occStart === false || $occEnd === false) {
                continue;
            }
            if ($startTs < $occEnd && $endTs > $occStart) {
                $shared = array_intersect($staffIds, (array)($occ['staff_ids'] ?? []));
                foreach ($shared as $sid) {
                    $conflicts[] = [
                        'staff_id'    => $sid,
                        'staff_name'  => $staffNames[$sid] ?? $sid,
                        'event_id'    => $occ['id'],
                        'event_title' => $occ['title'],
                        'start_at'    => $occ['occurrence_start'],
                        'end_at'      => $occ['occurrence_end'],
                    ];
                }
            }
        }
        return $conflicts;
    }

    // =========================================================================
    // BUCHUNGSANFRAGEN, RBAC & DSGVO
    // =========================================================================

    public static function getBookings(): array {
        return array_values(self::getData()['bookings'] ?? []);
    }

    public static function createBookingRequest(array $input): array {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $subject = trim((string)($input['subject'] ?? ''));
        $startAt = trim((string)($input['preferred_start'] ?? ''));
        $notes = trim((string)($input['notes'] ?? ''));
        $staffId = trim((string)($input['staff_id'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || strtotime($startAt) === false) {
            return ['success' => false, 'message' => 'Bitte fülle Name, gültige E-Mail-Adresse, Thema und Wunschtermin aus.'];
        }

        $data = self::getData();
        $booking = [
            'id'              => 'bk_' . bin2hex(random_bytes(5)),
            'name'            => $name,
            'email'           => strtolower($email),
            'subject'         => $subject,
            'preferred_start' => date('Y-m-d H:i:s', (int)strtotime($startAt)),
            'duration_min'    => max(15, min(480, (int)($input['duration_min'] ?? 60))),
            'staff_id'        => $staffId,
            'notes'           => $notes,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
        ];
        $data['bookings'][] = $booking;
        self::saveData($data);
        return ['success' => true, 'booking' => $booking, 'message' => 'Deine Terminanfrage wurde übermittelt.'];
    }

    public static function updateBookingStatus(string $bookingId, string $status): ?array {
        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            return null;
        }
        $data = self::getData();
        $target = null;
        foreach ($data['bookings'] as &$b) {
            if (($b['id'] ?? '') === $bookingId) {
                $b['status'] = $status;
                $target = $b;
                break;
            }
        }
        unset($b);
        if (!$target) {
            return null;
        }
        self::saveData($data);

        if ($status === 'approved') {
            $stTs = strtotime((string)$target['preferred_start']) ?: time();
            $endTs = $stTs + (((int)($target['duration_min'] ?? 60)) * 60);
            self::saveEvent([
                'title'       => 'Termin: ' . $target['subject'] . ' (' . $target['name'] . ')',
                'description' => (string)($target['notes'] ?? ''),
                'start_at'    => date('Y-m-d H:i:s', $stTs),
                'end_at'      => date('Y-m-d H:i:s', $endTs),
                'category_id' => 'cat_customer',
                'status'      => 'confirmed',
                'visibility'  => 'internal',
                'staff_ids'   => !empty($target['staff_id']) ? [$target['staff_id']] : [],
                'attendees'   => [['name' => $target['name'], 'email' => $target['email']]],
            ]);
        }
        return $target;
    }

    public static function canUser(string $capability, ?array $user = null, ?array $event = null): bool {
        $role = $user['role'] ?? (!empty($_SESSION['admin_logged_in']) ? 'admin' : 'guest');
        if ($role === 'admin' || $role === 'coordinator') {
            return true;
        }
        if ($role === 'member') {
            if ($capability === 'view_team' || $capability === 'create_event') {
                return true;
            }
            if ($capability === 'edit_event' || $capability === 'delete_event') {
                return $event !== null && (($event['created_by'] ?? '') === ($user['id'] ?? ''));
            }
        }
        if ($role === 'viewer') {
            return $capability === 'view_team' || $capability === 'view_public';
        }
        return $capability === 'view_public';
    }

    public static function sanitizeEventForPublic(array $event, ?array $cfg = null): array {
        $cfg = $cfg ?? self::getConfig();
        if (!empty($cfg['hide_private_staff_details'])) {
            $event['attendees'] = [];
            $event['staff_ids'] = [];
            $event['external_id'] = '';
            $event['created_by'] = '';
        }
        return $event;
    }
}
