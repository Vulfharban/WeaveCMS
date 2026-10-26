<?php
/**
 * ============================================================================
 * WeaveCMS – Version Management & Release Synchronizer (tools/set_version.php)
 * ============================================================================
 * Aktualisiert die Versionsnummer systemweit an allen relevanten Stellen:
 * 1. config.php (WEAVE_VERSION)
 * 2. libs/php/Installer.php (Installer::VERSION)
 * 3. Erzeugt die neuen Release-ZIPs (tools/build_release.php all)
 * 4. Aktualisiert automatisch die README.md (Downloads, Dateigrößen, Changelog-Header)
 * 
 * Benutzung (CLI):
 * php tools/set_version.php 1.2.4
 * ============================================================================
 */

if (php_sapi_name() !== 'cli' && empty($_SESSION['admin_logged_in'])) {
    die("Zugriff verweigert: Bitte über die CLI oder als angemeldeter Administrator ausführen.\n");
}

$root = dirname(__DIR__);
$newVersion = trim($argv[1] ?? ($_GET['version'] ?? ''));

if (empty($newVersion)) {
    die("Fehler: Bitte gib eine Versionsnummer an. Beispiel:\n  php tools/set_version.php 1.2.4\n");
}

// SemVer Format prüfen (z. B. 1.2.4 oder 1.2.4-rc1)
if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?$/', $newVersion)) {
    die("Fehler: Ungültiges Versionsformat '{$newVersion}'. Erwartet wird SemVer (z.B. 1.2.4 oder 1.3.0).\n");
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

// 3. Optional: Changelog-Eintrag in README.md vorbereiten, falls noch nicht existent
$readmePath = $root . '/README.md';
if (file_exists($readmePath)) {
    $readmeContent = file_get_contents($readmePath);
    if ($readmeContent !== false && !str_contains($readmeContent, "### v{$newVersion}")) {
        $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        $germanDate = date('j') . '. ' . $months[(int)date('n') - 1] . ' ' . date('Y');
        $newHeader = "### v{$newVersion} ({$germanDate})\n\n- 🚀 Update auf Version v{$newVersion}.\n";
        
        $readmeContent = preg_replace(
            '/(## Changelog & Recent Updates\s*\n\s*\n)/m',
            "$1{$newHeader}\n",
            $readmeContent,
            1
        );
        file_put_contents($readmePath, $readmeContent);
        echo "✓ README.md: Neuer Changelog-Abschnitt '### v{$newVersion}' vorbereitet.\n";
    }
}

// 4. Release-Pakete neu bauen und README synchronisieren
echo "--------------------------------------------------------\n";
echo "Erzeuge Release-Pakete und synchronisiere README.md...\n";
echo "--------------------------------------------------------\n";

// Temporär Server-Variable oder Argumente anpassen und build_release.php einbinden
$argv = ['build_release.php', 'all'];
require $root . '/tools/build_release.php';
