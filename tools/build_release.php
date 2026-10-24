<?php
/**
 * ============================================================================
 * WeaveCMS – Release Packaging Generator (tools/build_release.php) - Phase 12.3
 * ============================================================================
 * Erzeugt distributionsfertige Release-Pakete:
 * - Typ A: Voll-Paket (weavecms-vX.Y.Z.zip) für Neuinstallationen
 * - Typ B: Update-Paket (update-vX.to.vY.zip) für bestehende Websites
 * 
 * Schließt strikt aus:
 * - Kundendaten (data.json, installed.lock, config.local.php, contacts.json)
 * - Git-Ordner (.git, .gitignore, .github, .agents)
 * - Logdateien (logs/*), Cache (cache/*), Temporäre Dateien (scratch/*)
 * - Backups (backups/*) und Sandbox-Daten (data/sandbox/*)
 * - Kundenuploads (images/uploads/*)
 * ============================================================================
 */

if (php_sapi_name() !== 'cli' && empty($_SESSION['admin_logged_in'])) {
    die("Zugriff verweigert: Bitte über die CLI oder als angemeldeter Administrator ausführen.\n");
}

$root = dirname(__DIR__);
require_once $root . '/config.php';

$version = defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0';
$buildType = $argv[1] ?? ($_GET['type'] ?? 'full'); // 'full' oder 'update'
$outputDir = $root . '/releases';

if (!is_dir($outputDir)) {
    @mkdir($outputDir, 0755, true);
}

if (!class_exists('ZipArchive')) {
    die("Fehler: PHP-Erweiterung 'zip' (ZipArchive) ist nicht aktiviert.\n");
}

$zip = new ZipArchive();
$filename = ($buildType === 'update') 
    ? "update-v{$version}.zip" 
    : "weavecms-v{$version}.zip";
$zipPath = $outputDir . '/' . $filename;

if (file_exists($zipPath)) {
    @unlink($zipPath);
}

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Fehler beim Erstellen der ZIP-Datei: {$zipPath}\n");
}

// Ausschluss-Muster (Reguläre Ausdrücke)
$excludePatterns = [
    '~^\.git~',
    '~^\.agents~',
    '~^\.system_generated~',
    '~^releases~',
    '~^backups~',
    '~^logs/.*\.log$~',
    '~^cache/.*~',
    '~^scratch/.*~',
    '~^installed\.lock$~',
    '~^config\.local\.php$~',
    '~^data\.json$~',
    '~^contacts\.json$~',
    '~^data/sandbox/.*~',
    '~^data/.*\.sqlite$~',
    '~^images/uploads/(?!(\.gitkeep|\.htaccess)$).*~',
    '~^downloads/(?!(\.gitkeep|\.htaccess)$).*~'
];

// Für Update-Pakete werden ausschließlich Kern-Dateien aufgenommen
$updateAllowedRoots = [
    'admin',
    'libs',
    'views',
    'plugins',
    'index.php',
    'config.php',
    '.htaccess'
];

$fileCount = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $filePath = str_replace('\\', '/', $item->getPathname());
    $relPath = ltrim(substr($filePath, strlen(str_replace('\\', '/', $root))), '/');

    // Root-Ordner nicht hinzufügen
    if (empty($relPath)) {
        continue;
    }

    // Ausschluss-Filter prüfen
    $excluded = false;
    foreach ($excludePatterns as $pattern) {
        if (preg_match($pattern, $relPath)) {
            $excluded = true;
            break;
        }
    }
    if ($excluded) {
        continue;
    }

    // Update-Paket Filterung
    if ($buildType === 'update') {
        $allowed = false;
        foreach ($updateAllowedRoots as $prefix) {
            if ($relPath === $prefix || str_starts_with($relPath, $prefix . '/')) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            continue;
        }
    }

    if ($item->isDir()) {
        $zip->addEmptyDir($relPath);
    } elseif ($item->isFile()) {
        $zip->addFile($item->getPathname(), $relPath);
        $fileCount++;
    }
}

$zip->close();
$sizeMb = round(filesize($zipPath) / (1024 * 1024), 2);

echo "========================================================\n";
echo "WeaveCMS Release Packaging Erfolgreich!\n";
echo "Typ:       " . strtoupper($buildType) . "\n";
echo "Version:   v{$version}\n";
echo "Dateien:   {$fileCount}\n";
echo "Größe:     {$sizeMb} MB\n";
echo "Ausgabe:   {$zipPath}\n";
echo "========================================================\n";
