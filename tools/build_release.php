<?php
/**
 * ============================================================================
 * WeaveCMS – Release Packaging Generator (tools/build_release.php)
 * ============================================================================
 * Erzeugt distributionsfertige Release-Pakete:
 * - Typ 'full': Voll-Paket (weavecms-vX.Y.Z.zip) für Neuinstallationen
 * - Typ 'update': Update-Paket (update-vX.Y.Z.zip) für bestehende Websites
 * - Typ 'all': Erstellt beide Pakete nacheinander (Standard)
 * 
 * Synchronisiert automatisch:
 * - README.md (Versionsnummer, Download-Links, exakte Dateigrößen)
 * ============================================================================
 */

if (php_sapi_name() !== 'cli' && empty($_SESSION['admin_logged_in'])) {
    die("Zugriff verweigert: Bitte über die CLI oder als angemeldeter Administrator ausführen.\n");
}

$root = dirname(__DIR__);
require_once $root . '/config.php';

$version = defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0';
$targetType = $argv[1] ?? ($_GET['type'] ?? 'all'); // 'all', 'full', 'update'

if (!class_exists('ZipArchive')) {
    die("Fehler: PHP-Erweiterung 'zip' (ZipArchive) ist nicht aktiviert.\n");
}

$outputDir = $root . '/releases';
if (!is_dir($outputDir)) {
    @mkdir($outputDir, 0755, true);
}

/**
 * Erstellt ein Release-ZIP-Archiv (Typ 'full' oder 'update').
 */
function buildReleaseZip(string $root, string $version, string $buildType): array {
    $outputDir = $root . '/releases';
    $zip = new ZipArchive();
    $filename = ($buildType === 'update') 
        ? "update-v{$version}.zip" 
        : "weavecms-v{$version}.zip";
    $zipPath = $outputDir . '/' . $filename;

    if (file_exists($zipPath)) {
        @unlink($zipPath);
    }

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Fehler beim Erstellen der ZIP-Datei: {$zipPath}");
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
        '~^data/stats\.json$~',
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

    return [
        'type' => $buildType,
        'filename' => $filename,
        'path' => $zipPath,
        'files' => $fileCount,
        'sizeMb' => $sizeMb
    ];
}

/**
 * Synchronisiert die Versionsangaben und Dateigrößen automatisch mit der README.md.
 */
function syncReadmeVersion(string $root, string $version): array {
    $readmePath = $root . '/README.md';
    if (!file_exists($readmePath)) {
        return ['success' => false, 'error' => 'README.md nicht gefunden'];
    }

    $content = file_get_contents($readmePath);
    if ($content === false) {
        return ['success' => false, 'error' => 'README.md konnte nicht gelesen werden'];
    }

    $outputDir = $root . '/releases';
    $fullZip = $outputDir . "/weavecms-v{$version}.zip";
    $updateZip = $outputDir . "/update-v{$version}.zip";

    $fullMb = file_exists($fullZip) ? round(filesize($fullZip) / (1024 * 1024), 2) : null;
    $updateMb = file_exists($updateZip) ? round(filesize($updateZip) / (1024 * 1024), 2) : null;

    // 1. Überschrift: ## Releases & Downloads (vX.Y.Z)
    $content = preg_replace(
        '/^## Releases & Downloads \(v[^)]+\)/m',
        "## Releases & Downloads (v{$version})",
        $content
    );

    // 2. Full Package Download-Link & Größe
    $content = preg_replace_callback(
        '/^-\s*📦\s*\*\*\[WeaveCMS\s+v[^\s\]]+\s+Full\s+Package\]\([^)]+\)\*\*\s*\(`[^`]+`,\s*([0-9.]+)\s*MB\)/m',
        function ($matches) use ($version, $fullMb) {
            $mbStr = ($fullMb !== null) ? number_format($fullMb, 2, '.', '') : $matches[1];
            return "- 📦 **[WeaveCMS v{$version} Full Package](releases/weavecms-v{$version}.zip)** (`releases/weavecms-v{$version}.zip`, {$mbStr} MB)";
        },
        $content
    );

    // 3. Update Package Download-Link & Größe
    $content = preg_replace_callback(
        '/^-\s*🔄\s*\*\*\[WeaveCMS\s+v[^\s\]]+\s+Update\s+Package\]\([^)]+\)\*\*\s*\(`[^`]+`,\s*([0-9.]+)\s*MB\)/m',
        function ($matches) use ($version, $updateMb) {
            $mbStr = ($updateMb !== null) ? number_format($updateMb, 2, '.', '') : $matches[1];
            return "- 🔄 **[WeaveCMS v{$version} Update Package](releases/update-v{$version}.zip)** (`releases/update-v{$version}.zip`, {$mbStr} MB)";
        },
        $content
    );

    // 4. Download-Link im Installations-Abschnitt
    $content = preg_replace(
        '/Download \*\*\[weavecms-v[^\]]+\.zip\]\([^)]+\)\*\*/i',
        "Download **[weavecms-v{$version}.zip](releases/weavecms-v{$version}.zip)**",
        $content
    );

    file_put_contents($readmePath, $content);

    return [
        'success' => true,
        'version' => $version,
        'fullMb' => $fullMb,
        'updateMb' => $updateMb
    ];
}

// Ausführung je nach Zieltyp
$typesToBuild = ($targetType === 'all') ? ['full', 'update'] : [$targetType];
$results = [];

echo "========================================================\n";
echo "WeaveCMS Release Packaging (v{$version})\n";
echo "========================================================\n";

foreach ($typesToBuild as $type) {
    $res = buildReleaseZip($root, $version, $type);
    $results[] = $res;
    echo "✓ Paket (" . strtoupper($res['type']) . "): {$res['filename']}\n";
    echo "  Dateien: {$res['files']} | Größe: {$res['sizeMb']} MB\n";
}

// README automatisch synchronisieren
$sync = syncReadmeVersion($root, $version);
if ($sync['success']) {
    echo "--------------------------------------------------------\n";
    echo "✓ README.md automatisch auf v{$version} synchronisiert!\n";
    if ($sync['fullMb'] !== null) {
        echo "  - Full Package:   {$sync['fullMb']} MB\n";
    }
    if ($sync['updateMb'] !== null) {
        echo "  - Update Package: {$sync['updateMb']} MB\n";
    }
} else {
    echo "! Warnung: README.md Synchronisation fehlgeschlagen ({$sync['error']})\n";
}

echo "========================================================\n";
