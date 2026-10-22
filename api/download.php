<?php
/**
 * WeaveCMS – Sicherer Download-Proxy & Klick-Counter (Phase 16)
 * 
 * Liefert Downloads manipulationsgeschützt aus, filtert Bots/Crawler
 * und inkrementiert atomar den Download-Zähler im Speichertreiber.
 */

require_once dirname(__DIR__) . '/config.php';

$downloadId = trim($_GET['id'] ?? '');
if (empty($downloadId)) {
    http_response_code(400);
    die('Fehlende Download-ID.');
}

$dataFile = dirname(__DIR__) . '/data.json';
$data = load_data($dataFile, ['downloads' => []]);
$downloads = $data['downloads'] ?? [];

$targetItem = null;
$targetIndex = -1;

foreach ($downloads as $idx => $item) {
    if ((string)($item['id'] ?? '') === (string)$downloadId) {
        $targetItem = $item;
        $targetIndex = $idx;
        break;
    }
}

if (!$targetItem) {
    http_response_code(404);
    die('Download-Eintrag nicht gefunden.');
}

// 1. Crawler- & Bot-Erkennung (Verhindert künstliches Hochzählen durch Suchmaschinen-Bots)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$crawlerRegex = '/(bot|crawl|slurp|spider|mediapartners|google|yandex|bing|duckduck|baidu|teoma|yahoo|facebookexternalhit|whatsapp|preview)/i';
$isCrawler = (bool)preg_match($crawlerRegex, $userAgent);

// 2. Atomare Inkrementierung des Zählers bei echten Besuchern
if (!$isCrawler && $targetIndex !== -1) {
    $currentCount = (int)($data['downloads'][$targetIndex]['download_count'] ?? 0);
    $data['downloads'][$targetIndex]['download_count'] = $currentCount + 1;
    save_data($dataFile, $data);

    SecurityManager::logEvent(
        'DOWNLOAD',
        'Download ausgeliefert: ' . ($targetItem['title'] ?? $downloadId) . ' (Neuer Zählerstand: ' . ($currentCount + 1) . ')',
        'INFO'
    );
}

// 3. Auslieferung: Externer Link vs. Lokale Datei
$fileSource = $targetItem['file_source'] ?? (!empty($targetItem['external_url']) ? 'external' : 'upload');
$externalUrl = trim($targetItem['external_url'] ?? '');

if ($fileSource === 'external' || (!empty($externalUrl) && preg_match('~^https?://~i', $externalUrl))) {
    // Sicherheitsvalidierung der Ziel-URL gegen Header Injection
    $safeUrl = filter_var($externalUrl, FILTER_VALIDATE_URL);
    if (!$safeUrl) {
        http_response_code(400);
        die('Ungültige externe Download-URL.');
    }
    header('Location: ' . $safeUrl, true, 302);
    exit;
}

// Lokale Datei ausliefern
$filePathRaw = $targetItem['file_path'] ?? '';
if (empty($filePathRaw)) {
    http_response_code(404);
    die('Keine Download-Datei hinterlegt.');
}

$rootPath = realpath(dirname(__DIR__));
$fullPath = realpath($rootPath . '/' . ltrim($filePathRaw, '/\\'));

// Strikter Directory-Traversal-Schutz: Datei muss zwingend innerhalb des CMS-Wurzelverzeichnisses liegen
if (!$fullPath || !file_exists($fullPath) || !is_file($fullPath) || strpos($fullPath, $rootPath) !== 0) {
    SecurityManager::logEvent(
        'SECURITY_TRAVERSAL_BLOCKED',
        'Unbefugter Pfadzugriff im Download-Proxy: ' . $filePathRaw,
        'WARNING'
    );
    http_response_code(404);
    die('Datei nicht gefunden oder unzulässiger Zugriffspfad.');
}

// Dateiname & MIME-Typ aufbereiten
$filename = basename($fullPath);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $fullPath) : 'application/octet-stream';
if ($finfo) {
    finfo_close($finfo);
}
if (!$mimeType) {
    $mimeType = 'application/octet-stream';
}

// Output-Puffer leeren, damit keine unerwünschten Leerzeichen die Binärdatei beschädigen
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;
