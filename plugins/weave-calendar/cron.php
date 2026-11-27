<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Token-geschützter Cron-Endpunkt (Phase 36.4)
 * ============================================================================
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (file_exists($root . '/libs/php/StorageManager.php')) {
    require_once $root . '/libs/php/StorageManager.php';
}
if (file_exists($root . '/libs/php/Mailer.php')) {
    require_once $root . '/libs/php/Mailer.php';
}
require_once __DIR__ . '/CalendarCrypto.php';
require_once __DIR__ . '/CalendarIcs.php';
require_once __DIR__ . '/CalendarManager.php';
require_once __DIR__ . '/CalendarReminderService.php';

$cfg = CalendarManager::getConfig();
$expected = (string)($cfg['cron_token'] ?? '');
$provided = php_sapi_name() === 'cli'
    ? (string)($argv[1] ?? $expected)
    : trim((string)($_GET['token'] ?? ''));

if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Ungültiger Cron-Token.']);
    exit;
}

$smtpConfig = null;
$dataJsonPath = $root . '/data/data.json';
if (file_exists($dataJsonPath)) {
    $siteData = json_decode((string)@file_get_contents($dataJsonPath), true);
    if (is_array($siteData) && isset($siteData['settings']['smtp']) && is_array($siteData['settings']['smtp'])) {
        $smtpConfig = $siteData['settings']['smtp'];
    }
}

$result = CalendarReminderService::processDueReminders(null, null, $smtpConfig);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=UTF-8');
}
echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE);
