<?php
/**
 * Frontend: Inline Fragment View with Fallback (Universal Integrations)
 * 
 * @var array $item
 * @var array $params
 * @var string $targetUrl
 */

$ssrfCheck = IntegrationManager::checkSsrfSafety($targetUrl);
$inlineContent = null;

if ($ssrfCheck['safe']) {
    $fetchUrl = $ssrfCheck['url'];
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 3,
            'max_redirects' => 2,
            'header' => "User-Agent: WeaveCMS-Integration/1.0\r\nAccept: text/html,application/xhtml+xml\r\n"
        ]
    ]);

    // Begrenzung auf max. 512 KB
    $raw = @file_get_contents($fetchUrl, false, $ctx, 0, 524288);
    if ($raw !== false && !empty($raw)) {
        // Falls vollständiges HTML-Dokument: Body extrahieren
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $raw, $matches)) {
            $inlineContent = $matches[1];
        } else {
            $inlineContent = $raw;
        }
    }
}

// Fallback auf Standard-iFrame wenn Inline-Abruf fehlschlägt
if ($inlineContent === null) {
    require __DIR__ . '/iframe_embed.php';
    return;
}
?>

<div class="universal-inline-embed card p-6 border shadow-sm my-6 bg-white dark:bg-gray-900">
    <?= $inlineContent ?>
</div>
