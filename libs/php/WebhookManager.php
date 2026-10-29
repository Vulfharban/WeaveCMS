<?php
/**
 * ============================================================================
 * WeaveCMS – WebhookManager (Phase 31)
 * ============================================================================
 * Event-Webhook-Dispatcher für externe Drittsysteme, Zapier, CRMs und Make.
 * Unterstützt HMAC-SHA256 Signatur (X-Weave-Signature), Delivery-Logging
 * und manuelle Wiederholungsversuche (Retry).
 * ============================================================================
 *
 * @package WeaveCMS
 * @subpackage Security
 */

class WebhookManager {
    private static ?string $storageFile = null;
    private static ?string $logsFile = null;

    public const EVENT_CONTACT_RECEIVED = 'contact.received';
    public const EVENT_ORDER_CREATED    = 'order.created';
    public const EVENT_POST_PUBLISHED   = 'post.published';
    public const EVENT_BACKUP_CREATED   = 'backup.created';
    public const EVENT_TEST_PING        = 'test.ping';

    public static function getSupportedEvents(): array {
        return [
            self::EVENT_CONTACT_RECEIVED => 'Kontaktanfrage empfangen (contact.received)',
            self::EVENT_ORDER_CREATED    => 'Shop-Bestellung eingegangen (order.created)',
            self::EVENT_POST_PUBLISHED   => 'Blogartikel veröffentlicht (post.published)',
            self::EVENT_BACKUP_CREATED   => 'System-Backup erstellt (backup.created)',
            '*'                          => 'Alle Ereignisse (*)'
        ];
    }

    private static function getStorageFile(): string {
        if (self::$storageFile === null) {
            self::$storageFile = dirname(__DIR__, 2) . '/data/webhooks.json';
        }
        return self::$storageFile;
    }

    private static function getLogsFile(): string {
        if (self::$logsFile === null) {
            self::$logsFile = dirname(__DIR__, 2) . '/data/webhook_logs.json';
        }
        return self::$logsFile;
    }

    /**
     * Lädt alle registrierten Webhooks
     */
    public static function getWebhooks(): array {
        $file = self::getStorageFile();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function saveWebhooks(array $webhooks): bool {
        $file = self::getStorageFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return (bool)@file_put_contents($file, json_encode($webhooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Fügt einen neuen Webhook hinzu
     */
    public static function addWebhook(string $name, string $url, string $secret = '', array $events = ['*']): array {
        $name = trim(strip_tags($name));
        $url = trim($url);
        if (empty($name)) {
            $name = 'Webhook ' . date('Y-m-d H:i');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'Ungültige Webhook-Ziel-URL.'];
        }

        $supported = array_keys(self::getSupportedEvents());
        $filteredEvents = array_values(array_intersect($events, $supported));
        if (empty($filteredEvents)) {
            $filteredEvents = ['*'];
        }

        $id = 'whk_' . bin2hex(random_bytes(8));
        $secret = !empty($secret) ? trim($secret) : bin2hex(random_bytes(16));

        $record = [
            'id'         => $id,
            'name'       => $name,
            'url'        => $url,
            'secret'     => $secret,
            'events'     => $filteredEvents,
            'is_active'  => true,
            'created_at' => date('c')
        ];

        $webhooks = self::getWebhooks();
        $webhooks[] = $record;
        self::saveWebhooks($webhooks);

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('WEBHOOK_CREATED', "Webhook '{$name}' ({$url}) angelegt.", 'INFO');
        }

        return ['success' => true, 'webhook' => $record];
    }

    /**
     * Löscht einen Webhook
     */
    public static function deleteWebhook(string $id): bool {
        $webhooks = self::getWebhooks();
        $initial = count($webhooks);
        $webhooks = array_values(array_filter($webhooks, fn($w) => ($w['id'] ?? '') !== $id));
        if (count($webhooks) !== $initial) {
            self::saveWebhooks($webhooks);
            return true;
        }
        return false;
    }

    /**
     * Schaltet einen Webhook aktiv/inaktiv
     */
    public static function toggleWebhook(string $id): bool {
        $webhooks = self::getWebhooks();
        foreach ($webhooks as &$w) {
            if (($w['id'] ?? '') === $id) {
                $w['is_active'] = empty($w['is_active']);
                self::saveWebhooks($webhooks);
                return true;
            }
        }
        return false;
    }

    /**
     * Event auslösen und an alle passenden aktiven Webhooks senden
     */
    public static function dispatch(string $eventName, array $payload): array {
        $webhooks = self::getWebhooks();
        $results = [];

        foreach ($webhooks as $wh) {
            if (empty($wh['is_active'])) {
                continue;
            }
            $events = $wh['events'] ?? [];
            if (!in_array('*', $events, true) && !in_array($eventName, $events, true)) {
                continue;
            }

            $res = self::sendDelivery($wh, $eventName, $payload);
            $results[] = $res;
        }

        return $results;
    }

    /**
     * Führt eine Webhook-Lieferung aus und protokolliert das Ergebnis
     */
    private static function sendDelivery(array $webhook, string $eventName, array $payload): array {
        $deliveryId = 'whd_' . bin2hex(random_bytes(10));
        $siteUrl = (class_exists('SecurityManager') && method_exists('SecurityManager', 'getBaseUrl'))
            ? SecurityManager::getBaseUrl()
            : (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        $envelope = [
            'event'       => $eventName,
            'delivery_id' => $deliveryId,
            'timestamp'   => date('c'),
            'site_url'    => $siteUrl,
            'data'        => $payload
        ];

        $jsonPayload = json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $secret = $webhook['secret'] ?? '';
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: WeaveCMS-Webhook/1.5.0',
            'X-Weave-Event: ' . $eventName,
            'X-Weave-Delivery: ' . $deliveryId,
            'X-Weave-Signature: ' . $signature
        ];

        $startTime = microtime(true);
        $responseCode = 0;
        $responseBody = '';
        $errorMsg = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($webhook['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $responseBody = curl_exec($ch);
            if ($responseBody === false) {
                $errorMsg = curl_error($ch);
            }
            $responseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80500 && function_exists('curl_close')) {
                @curl_close($ch);
            }
        } else {
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => implode("\r\n", $headers),
                    'content' => $jsonPayload,
                    'timeout' => 8,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            $context = stream_context_create($opts);
            $responseBody = @file_get_contents($webhook['url'], false, $context);
            $respHeaders = function_exists('http_get_last_response_headers') ? @http_get_last_response_headers() : ($http_response_header ?? null);
            if (!empty($respHeaders) && is_array($respHeaders)) {
                if (preg_match('#HTTP/\S+\s+(\d+)#', $respHeaders[0], $m)) {
                    $responseCode = (int)$m[1];
                }
            }
            if ($responseBody === false) {
                $errorMsg = 'HTTP Request fehlgeschlagen.';
            }
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);
        $isSuccess = ($responseCode >= 200 && $responseCode < 300);

        $logEntry = [
            'id'            => $deliveryId,
            'webhook_id'    => $webhook['id'],
            'webhook_name'  => $webhook['name'],
            'url'           => $webhook['url'],
            'event'         => $eventName,
            'payload'       => $envelope,
            'response_code' => $responseCode,
            'response_body' => is_string($responseBody) ? mb_substr(strip_tags($responseBody), 0, 500, 'UTF-8') : '',
            'error'         => $errorMsg,
            'duration_ms'   => $durationMs,
            'success'       => $isSuccess,
            'timestamp'     => date('c')
        ];

        self::appendLog($logEntry);

        return [
            'success'       => $isSuccess,
            'webhook_id'    => $webhook['id'],
            'delivery_id'   => $deliveryId,
            'response_code' => $responseCode,
            'duration_ms'   => $durationMs,
            'error'         => $errorMsg
        ];
    }

    /**
     * Fügt einen Log-Eintrag hinzu (rollierend auf 100 Einträge begrenzt)
     */
    private static function appendLog(array $entry): void {
        $logs = self::getLogs(150);
        array_unshift($logs, $entry);
        if (count($logs) > 100) {
            $logs = array_slice($logs, 0, 100);
        }
        $file = self::getLogsFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Ruft die Webhook-Lieferprotokolle ab
     */
    public static function getLogs(int $limit = 50): array {
        $file = self::getLogsFile();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        return array_slice($data, 0, $limit);
    }

    /**
     * Wiederholt eine vorherige Webhook-Lieferung
     */
    public static function retryDelivery(string $deliveryId): array {
        $logs = self::getLogs(100);
        $target = null;
        foreach ($logs as $l) {
            if (($l['id'] ?? '') === $deliveryId) {
                $target = $l;
                break;
            }
        }

        if (!$target) {
            return ['success' => false, 'error' => 'Log-Eintrag für Wiederholung nicht gefunden.'];
        }

        // Webhook suchen oder temporäres Objekt aus Log nutzen
        $webhooks = self::getWebhooks();
        $webhook = null;
        foreach ($webhooks as $wh) {
            if (($wh['id'] ?? '') === ($target['webhook_id'] ?? '')) {
                $webhook = $wh;
                break;
            }
        }

        if (!$webhook) {
            $webhook = [
                'id'     => $target['webhook_id'] ?? 'whk_unknown',
                'name'   => $target['webhook_name'] ?? 'Unbekannt',
                'url'    => $target['url'],
                'secret' => '',
                'events' => ['*']
            ];
        }

        $payloadData = $target['payload']['data'] ?? [];
        $eventName = $target['event'] ?? 'retry.event';

        return self::sendDelivery($webhook, $eventName, $payloadData);
    }

    /**
     * Sendet einen Ping/Test-Aufruf an einen Webhook
     */
    public static function testWebhook(string $webhookId): array {
        $webhooks = self::getWebhooks();
        foreach ($webhooks as $wh) {
            if (($wh['id'] ?? '') === $webhookId) {
                $testPayload = [
                    'message'    => 'Dies ist ein Test-Ping von WeaveCMS (Phase 31 Webhook-Dispatcher).',
                    'ping_time'  => date('c'),
                    'test_value' => 42
                ];
                return self::sendDelivery($wh, self::EVENT_TEST_PING, $testPayload);
            }
        }
        return ['success' => false, 'error' => 'Webhook nicht gefunden.'];
    }

    /**
     * Löscht alle Logs
     */
    public static function clearLogs(): bool {
        $file = self::getLogsFile();
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }
}
