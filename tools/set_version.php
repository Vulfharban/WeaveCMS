<?php
/**
 * ============================================================================
 * WeaveCMS – Version Management & Release Synchronizer (tools/set_version.php)
 * ============================================================================
 * Aktualisiert die Versionsnummer systemweit an allen relevanten Stellen:
 * 1. config.php (WEAVE_VERSION)
 * 2. libs/php/Installer.php (Installer::VERSION)
 * 3. version.json (latest_version, URLs, sha256)
 * 4. Erzeugt die neuen Release-ZIPs (tools/build_release.php all)
 * 5. Aktualisiert automatisch die README.md (Downloads, Dateigrößen, Changelog-Header)
 * 
 * Benutzung (CLI):
 * php tools/set_version.php 1.3.3.1
 * ============================================================================
 */

if (php_sapi_name() !== 'cli' && empty($_SESSION['admin_logged_in'])) {
    die("Zugriff verweigert: Bitte über die CLI oder als angemeldeter Administrator ausführen.\n");
}

$root = dirname(__DIR__);
$newVersion = trim($argv[1] ?? ($_GET['version'] ?? ''));

if (empty($newVersion)) {
    die("Fehler: Bitte gib eine Versionsnummer an. Beispiel:\n  php tools/set_version.php 1.3.3.1\n");
}

// SemVer Format prüfen (z. B. 1.2.4 oder 1.3.3.1 oder 1.2.4-rc1)
if (!preg_match('/^[0-9]+(\.[0-9]+){2,3}(-[a-zA-Z0-9.]+)?$/', $newVersion)) {
    die("Fehler: Ungültiges Versionsformat '{$newVersion}'. Erwartet wird SemVer (z.B. 1.3.3 oder 1.3.3.1).\n");
}

echo "========================================================\n";
echo "WeaveCMS Versions-Aktualisierung auf v{$newVersion}\n";
echo "========================================================\n";

// 1. config.php aktualisieren
$configPath = $root . '/config.php';
$configContent = file_get_contents($configPath);
if ($configContent === false) {
    die("Fehler: {$configPath} konnte nicht gelesen werden.\n");
}
$configUpdated = preg_replace(
    "/define\('WEAVE_VERSION',\s*'[^']+'\);/",
    "define('WEAVE_VERSION', '{$newVersion}');",
    $configContent
);
file_put_contents($configPath, $configUpdated);
echo "✓ config.php: WEAVE_VERSION -> {$newVersion}\n";

// 2. libs/php/Installer.php aktualisieren
$installerPath = $root . '/libs/php/Installer.php';
if (file_exists($installerPath)) {
    $installerContent = file_get_contents($installerPath);
    if ($installerContent !== false) {
        $installerUpdated = preg_replace(
            "/const VERSION = '[^']+';/",
            "const VERSION = '{$newVersion}';",
            $installerContent
        );
        file_put_contents($installerPath, $installerUpdated);
        echo "✓ libs/php/Installer.php: Installer::VERSION -> {$newVersion}\n";
    }
}

// 3. version.json aktualisieren
$versionJsonPath = $root . '/version.json';
if (file_exists($versionJsonPath)) {
    $vData = json_decode(file_get_contents($versionJsonPath), true) ?: [];
    $vData['latest_version'] = $newVersion;
    $vData['download_url'] = "https://github.com/Vulfharban/WeaveCMS/releases/tag/v{$newVersion}";
    $vData['update_download_url'] = "https://github.com/Vulfharban/WeaveCMS/releases/download/v{$newVersion}/update-v{$newVersion}.zip";
    $vData['changelog'] = "WeaveCMS v{$newVersion} – Sandbox Isolation, Security Hardening & Admin Translations (UTF-8 BOM Hotfix)";
    file_put_contents($versionJsonPath, json_encode($vData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo "✓ version.json: Aktualisiert auf v{$newVersion}\n";
}

// 4. Release-Pakete neu bauen und README synchronisieren
echo "--------------------------------------------------------\n";
echo "Erzeuge Release-Pakete und synchronisiere README.md...\n";
echo "--------------------------------------------------------\n";

$argv = ['build_release.php', 'all'];
require $root . '/tools/build_release.php';

// 5. SHA256 des Update-Pakets in version.json nachtragen
$updateZipPath = $root . "/releases/update-v{$newVersion}.zip";
if (file_exists($updateZipPath) && file_exists($versionJsonPath)) {
    $vData = json_decode(file_get_contents($versionJsonPath), true) ?: [];
    $vData['update_sha256'] = "update: " . hash_file('sha256', $updateZipPath);
    file_put_contents($versionJsonPath, json_encode($vData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo "✓ version.json: SHA-256 Prüfsumme aktualisiert ({$vData['update_sha256']})\n";
}
