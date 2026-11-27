<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Cloud-Sync Engine (CalendarSync)
 * ============================================================================
 * Unterstützt Google Calendar REST API v3, Microsoft Graph Calendar API,
 * Apple iCloud / universelles CalDAV sowie .ics-Import mit Konfliktauflösung.
 * Zugangsdaten werden verschlüsselt in calendar_secrets gespeichert.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class CalendarSync {

    protected static array $secretFields = [
        'client_secret',
        'refresh_token',
        'access_token',
        'app_password',
    ];

    public static function getProviderConfig(string $provider, bool $decrypt = true): array {
        $secrets = CalendarManager::getSecrets();
        $appKey = (string)($secrets['app_key'] ?? '');
        $cfg = $secrets['providers'][$provider] ?? [];
        if (!is_array($cfg)) {
            return [];
        }
        if ($decrypt) {
            foreach (self::$secretFields as $field) {
                if (!empty($cfg[$field])) {
                    $cfg[$field] = CalendarCrypto::decrypt((string)$cfg[$field], $appKey);
                }
            }
        }
        return $cfg;
    }

    public static function saveProviderConfig(string $provider, array $input): bool {
        $allowed = ['google', 'microsoft', 'caldav'];
        if (!in_array($provider, $allowed, true)) {
            return false;
        }
        $secrets = CalendarManager::getSecrets();
        $appKey = (string)($secrets['app_key'] ?? '');
        $existing = $secrets['providers'][$provider] ?? [];

        $merged = array_merge($existing, [
            'enabled'      => !empty($input['enabled']),
            'client_id'    => trim((string)($input['client_id'] ?? ($existing['client_id'] ?? ''))),
            'tenant_id'    => trim((string)($input['tenant_id'] ?? ($existing['tenant_id'] ?? 'common'))),
            'calendar_id'  => trim((string)($input['calendar_id'] ?? ($existing['calendar_id'] ?? 'primary'))),
            'caldav_url'   => trim((string)($input['caldav_url'] ?? ($existing['caldav_url'] ?? ''))),
            'username'     => trim((string)($input['username'] ?? ($existing['username'] ?? ''))),
            'direction'    => in_array((string)($input['direction'] ?? 'two_way'), ['two_way', 'import_only', 'export_only'], true)
                ? (string)$input['direction']
                : 'two_way',
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        foreach (self::$secretFields as $field) {
            if (isset($input[$field]) && trim((string)$input[$field]) !== '') {
                $merged[$field] = CalendarCrypto::encrypt(trim((string)$input[$field]), $appKey);
            }
        }

        $secrets['providers'][$provider] = $merged;
        return CalendarManager::saveSecrets($secrets);
    }

    public static function addSyncLog(string $provider, string $status, string $message, array $stats = []): void {
        $secrets = CalendarManager::getSecrets();
        $secrets['sync_log'][] = [
            'time'     => date('Y-m-d H:i:s'),
            'provider' => $provider,
            'status'   => $status,
            'message'  => $message,
            'stats'    => $stats,
        ];
        if (count($secrets['sync_log']) > 100) {
            $secrets['sync_log'] = array_slice($secrets['sync_log'], -100);
        }
        CalendarManager::saveSecrets($secrets);
    }

    public static function getSyncLog(int $limit = 30): array {
        $logs = CalendarManager::getSecrets()['sync_log'] ?? [];
        return array_slice(array_reverse($logs), 0, $limit);
    }

    public static function getGoogleAuthUrl(array $cfg, string $redirectUri, string $state): string {
        $params = [
            'client_id'     => (string)($cfg['client_id'] ?? ''),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public static function getMicrosoftAuthUrl(array $cfg, string $redirectUri, string $state): string {
        $tenant = trim((string)($cfg['tenant_id'] ?? 'common')) ?: 'common';
        $params = [
            'client_id'     => (string)($cfg['client_id'] ?? ''),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'offline_access Calendars.ReadWrite',
            'state'         => $state,
        ];
        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    protected static function httpRequest(string $method, string $url, array $headers = [], ?string $body = null): array {
        $hdrLines = [];
        foreach ($headers as $k => $v) {
            $hdrLines[] = "{$k}: {$v}";
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => strtoupper($method),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => $hdrLines,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            return ['status' => $code, 'body' => (string)$resp, 'error' => $err];
        }

        $ctx = stream_context_create([
            'http' => [
                'method'        => strtoupper($method),
                'header'        => implode("\r\n", $hdrLines),
                'content'       => $body ?? '',
                'timeout'       => 15,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        return ['status' => $resp !== false ? 200 : 0, 'body' => (string)$resp, 'error' => $resp === false ? 'HTTP-Fehler' : ''];
    }

    /**
     * Entscheidet gemäß Konfliktauflösung, ob ein eingehender externer Termin übernommen wird.
     */
    public static function shouldAcceptRemote(?array $localEvent, string $remoteUpdatedAt): bool {
        if ($localEvent === null) {
            return true;
        }
        $mode = (string)(CalendarManager::getConfig()['conflict_resolution'] ?? 'newest');
        if ($mode === 'local') {
            return false;
        }
        if ($mode === 'remote') {
            return true;
        }
        $localTs = strtotime((string)($localEvent['updated_at'] ?? '')) ?: 0;
        $remoteTs = strtotime($remoteUpdatedAt) ?: time();
        return $remoteTs >= $localTs;
    }

    /**
     * Importiert .ics-Termine mit UID-Upsert und Konfliktauflösung.
     */
    public static function importIcsContent(string $icsContent, string $source = 'ics_import'): array {
        $parsed = CalendarIcs::parseIcsToEvents($icsContent);
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($parsed as $item) {
            $uid = trim((string)($item['uid'] ?? ''));
            $existing = $uid !== '' ? CalendarManager::getEventByUid($uid) : null;
            if ($existing !== null) {
                if (!self::shouldAcceptRemote($existing, (string)($item['updated_at'] ?? ''))) {
                    $skipped++;
                    continue;
                }
                $item['id'] = $existing['id'];
                $item['category_id'] = $existing['category_id'] ?? 'cat_public';
                $updated++;
            } else {
                $imported++;
            }
            $item['external_source'] = $source;
            $item['external_id'] = $uid;
            CalendarManager::saveEvent($item);
        }

        self::addSyncLog($source, 'ok', "Importiert: {$imported}, aktualisiert: {$updated}, übersprungen: {$skipped}", compact('imported', 'updated', 'skipped'));
        return ['success' => true, 'imported' => $imported, 'updated' => $updated, 'skipped' => $skipped];
    }

    public static function syncGoogle(?array $overrideCfg = null): array {
        $cfg = $overrideCfg ?? self::getProviderConfig('google', true);
        if (empty($cfg['enabled']) || empty($cfg['refresh_token']) || empty($cfg['client_id'])) {
            return ['success' => false, 'message' => 'Google Calendar ist nicht vollständig konfiguriert (Client-ID / Refresh-Token fehlt).'];
        }

        $tokenResp = self::httpRequest('POST', 'https://oauth2.googleapis.com/token', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query([
            'client_id'     => $cfg['client_id'],
            'client_secret' => (string)($cfg['client_secret'] ?? ''),
            'refresh_token' => $cfg['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]));
        $tokJson = json_decode($tokenResp['body'], true);
        $accessToken = (string)($tokJson['access_token'] ?? '');
        if ($accessToken === '') {
            self::addSyncLog('google', 'error', 'Google OAuth Token-Refresh fehlgeschlagen.');
            return ['success' => false, 'message' => 'Google OAuth Token-Refresh fehlgeschlagen.'];
        }

        $calId = rawurlencode((string)($cfg['calendar_id'] ?: 'primary'));
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calId}/events?singleEvents=false&maxResults=100";
        if (!empty($cfg['sync_token'])) {
            $url .= '&syncToken=' . rawurlencode((string)$cfg['sync_token']);
        }
        $listResp = self::httpRequest('GET', $url, ['Authorization' => 'Bearer ' . $accessToken]);
        $listJson = json_decode($listResp['body'], true);
        if ($listResp['status'] >= 400 || !is_array($listJson)) {
            self::addSyncLog('google', 'error', 'Google API Fehler (HTTP ' . $listResp['status'] . ').');
            return ['success' => false, 'message' => 'Google Calendar API-Abruf fehlgeschlagen.'];
        }

        $imported = 0;
        foreach ((array)($listJson['items'] ?? []) as $gEv) {
            $gId = (string)($gEv['id'] ?? '');
            if ($gId === '') {
                continue;
            }
            $existing = CalendarManager::getEventByUid($gId);
            if (!self::shouldAcceptRemote($existing, (string)($gEv['updated'] ?? ''))) {
                continue;
            }
            $isAllDay = !empty($gEv['start']['date']);
            $startRaw = (string)($gEv['start']['dateTime'] ?? $gEv['start']['date'] ?? '');
            $endRaw = (string)($gEv['end']['dateTime'] ?? $gEv['end']['date'] ?? '');
            if ($startRaw === '') {
                continue;
            }
            CalendarManager::saveEvent([
                'id'              => $existing['id'] ?? '',
                'uid'             => (string)($gEv['iCalUID'] ?? $gId),
                'title'           => (string)($gEv['summary'] ?? 'Google Termin'),
                'description'     => (string)($gEv['description'] ?? ''),
                'location'        => (string)($gEv['location'] ?? ''),
                'start_at'        => $startRaw,
                'end_at'          => $endRaw ?: $startRaw,
                'all_day'         => $isAllDay,
                'status'          => (($gEv['status'] ?? 'confirmed') === 'cancelled') ? 'cancelled' : 'confirmed',
                'external_source' => 'google',
                'external_id'     => $gId,
                'updated_at'      => date('Y-m-d H:i:s', strtotime((string)($gEv['updated'] ?? 'now')) ?: time()),
            ]);
            $imported++;
        }

        if (!empty($listJson['nextSyncToken'])) {
            $secrets = CalendarManager::getSecrets();
            $secrets['providers']['google']['sync_token'] = $listJson['nextSyncToken'];
            CalendarManager::saveSecrets($secrets);
        }

        self::addSyncLog('google', 'ok', "Google Sync abgeschlossen ({$imported} Termine verarbeitet).");
        return ['success' => true, 'message' => "Google Sync erfolgreich ({$imported} Termine)."];
    }

    public static function syncMicrosoft(?array $overrideCfg = null): array {
        $cfg = $overrideCfg ?? self::getProviderConfig('microsoft', true);
        if (empty($cfg['enabled']) || empty($cfg['refresh_token']) || empty($cfg['client_id'])) {
            return ['success' => false, 'message' => 'Microsoft 365 / Outlook ist nicht vollständig konfiguriert.'];
        }
        $tenant = trim((string)($cfg['tenant_id'] ?? 'common')) ?: 'common';
        $tokResp = self::httpRequest('POST', "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query([
            'client_id'     => $cfg['client_id'],
            'client_secret' => (string)($cfg['client_secret'] ?? ''),
            'refresh_token' => $cfg['refresh_token'],
            'grant_type'    => 'refresh_token',
            'scope'         => 'offline_access Calendars.ReadWrite',
        ]));
        $tokJson = json_decode($tokResp['body'], true);
        $accessToken = (string)($tokJson['access_token'] ?? '');
        if ($accessToken === '') {
            self::addSyncLog('microsoft', 'error', 'Microsoft Graph Token-Refresh fehlgeschlagen.');
            return ['success' => false, 'message' => 'Microsoft Graph Authentifizierung fehlgeschlagen.'];
        }

        $url = 'https://graph.microsoft.com/v1.0/me/events?$top=50&$select=id,subject,bodyPreview,start,end,location,isAllDay,isCancelled,lastModifiedDateTime,iCalUId';
        $resp = self::httpRequest('GET', $url, ['Authorization' => 'Bearer ' . $accessToken]);
        $json = json_decode($resp['body'], true);
        if ($resp['status'] >= 400 || !is_array($json)) {
            self::addSyncLog('microsoft', 'error', 'Microsoft Graph Abruf fehlgeschlagen (HTTP ' . $resp['status'] . ').');
            return ['success' => false, 'message' => 'Microsoft Graph Kalenderabruf fehlgeschlagen.'];
        }

        $imported = 0;
        foreach ((array)($json['value'] ?? []) as $mEv) {
            $mId = (string)($mEv['id'] ?? '');
            if ($mId === '') {
                continue;
            }
            $existing = CalendarManager::getEventByUid($mId);
            if (!self::shouldAcceptRemote($existing, (string)($mEv['lastModifiedDateTime'] ?? ''))) {
                continue;
            }
            CalendarManager::saveEvent([
                'id'              => $existing['id'] ?? '',
                'uid'             => (string)($mEv['iCalUId'] ?? $mId),
                'title'           => (string)($mEv['subject'] ?? 'Outlook Termin'),
                'description'     => (string)($mEv['bodyPreview'] ?? ''),
                'location'        => (string)($mEv['location']['displayName'] ?? ''),
                'start_at'        => (string)($mEv['start']['dateTime'] ?? ''),
                'end_at'          => (string)($mEv['end']['dateTime'] ?? ''),
                'all_day'         => !empty($mEv['isAllDay']),
                'status'          => !empty($mEv['isCancelled']) ? 'cancelled' : 'confirmed',
                'external_source' => 'microsoft',
                'external_id'     => $mId,
                'updated_at'      => date('Y-m-d H:i:s', strtotime((string)($mEv['lastModifiedDateTime'] ?? 'now')) ?: time()),
            ]);
            $imported++;
        }

        self::addSyncLog('microsoft', 'ok', "Microsoft 365 Sync abgeschlossen ({$imported} Termine).");
        return ['success' => true, 'message' => "Microsoft 365 Sync erfolgreich ({$imported} Termine)."];
    }

    /**
     * Prüft externe Sync-URLs auf sicheres HTTPS-Schema und blockiert private/lokale Netze (SSRF-Schutz).
     */
    public static function validateExternalUrl(string $url): bool {
        $parts = parse_url(trim($url));
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return false;
        }
        $host = strtolower(trim((string)$parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }
        return true;
    }

    public static function syncCalDav(?array $overrideCfg = null): array {
        $cfg = $overrideCfg ?? self::getProviderConfig('caldav', true);
        $url = trim((string)($cfg['caldav_url'] ?? ''));
        $user = trim((string)($cfg['username'] ?? ''));
        $pass = (string)($cfg['app_password'] ?? '');
        if (empty($cfg['enabled']) || $url === '' || $user === '' || $pass === '') {
            return ['success' => false, 'message' => 'CalDAV / Apple iCloud ist nicht vollständig konfiguriert.'];
        }
        if (!self::validateExternalUrl($url)) {
            self::addSyncLog('caldav', 'error', 'Ungültige oder unsichere CalDAV-URL abgelehnt.');
            return ['success' => false, 'message' => 'CalDAV-URL muss eine gültige öffentliche HTTPS-Adresse sein.'];
        }

        $reportBody = '<?xml version="1.0" encoding="utf-8" ?>'
            . '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">'
            . '<d:prop><d:getetag/><c:calendar-data/></d:prop>'
            . '<c:filter><c:comp-filter name="VCALENDAR"><c:comp-filter name="VEVENT"/></c:comp-filter></c:filter>'
            . '</c:calendar-query>';

        $authHeader = 'Basic ' . base64_encode($user . ':' . $pass);
        $resp = self::httpRequest('REPORT', $url, [
            'Authorization' => $authHeader,
            'Depth'         => '1',
            'Content-Type'  => 'application/xml; charset=utf-8',
        ], $reportBody);

        if ($resp['status'] >= 400 || $resp['body'] === '') {
            self::addSyncLog('caldav', 'error', 'CalDAV REPORT fehlgeschlagen (HTTP ' . $resp['status'] . ').');
            return ['success' => false, 'message' => 'CalDAV-Abruf fehlgeschlagen (HTTP ' . $resp['status'] . ').'];
        }

        preg_match_all('~<(?:[a-zA-Z0-9_-]+:)?calendar-data\b[^>]*>(.*?)</(?:[a-zA-Z0-9_-]+:)?calendar-data>~is', $resp['body'], $matches);
        $imported = 0;
        foreach ((array)($matches[1] ?? []) as $rawXmlData) {
            $icsText = html_entity_decode($rawXmlData, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $res = self::importIcsContent($icsText, 'caldav');
            $imported += ($res['imported'] ?? 0) + ($res['updated'] ?? 0);
        }

        self::addSyncLog('caldav', 'ok', "CalDAV / iCloud Sync abgeschlossen ({$imported} Termine).");
        return ['success' => true, 'message' => "CalDAV Sync erfolgreich ({$imported} Termine)."];
    }
}
