<?php
/**
 * Test-Suite zur Überprüfung der Mehrsprachigkeit im Frontend und Backend
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/LanguageManager.php';
require_once __DIR__ . '/../libs/php/SectionManager.php';

echo "========================================================\n";
echo "WEAVECMS MEHRSPRACHIGKEITS-PRÜFUNG (FRONTEND & BACKEND)\n";
echo "========================================================\n\n";

$langDir = dirname(__DIR__) . '/libs/lang';
$files = glob($langDir . '/*.json');

$supported = LanguageManager::getSupportedLanguages();
echo "1. Prüfung der Frontend-Sprachkataloge (" . count($files) . " Dateien):\n";

$allValid = true;
$catalogs = [];

foreach ($files as $file) {
    $code = basename($file, '.json');
    $raw = file_get_contents($file);

    // BOM Check
    $hasBom = (substr($raw, 0, 3) === "\xEF\xBB\xBF");
    if ($hasBom) {
        echo "   ❌ BOM FEHLER in {$file}!\n";
        $allValid = false;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo "   ❌ JSON SYNTAX-FEHLER in {$file}: " . json_last_error_msg() . "\n";
        $allValid = false;
        continue;
    }

    $catalogs[$code] = $json;
    echo "   ✓ [{$code}] " . count($json) . " Übersetzungsschlüssel geladen (BOM-frei)\n";
}

if (!$allValid) {
    echo "\nFEHLER: Nicht alle Sprachdateien sind valide!\n";
    exit(1);
}

echo "\n2. Prüfung der Backend-Sprachkataloge (Admin DE/EN):\n";
$adminFiles = [
    'de' => $langDir . '/admin/de.json',
    'en' => $langDir . '/admin/en.json'
];

foreach ($adminFiles as $aCode => $aPath) {
    $aRaw = file_get_contents($aPath);
    if (substr($aRaw, 0, 3) === "\xEF\xBB\xBF") {
        echo "   ❌ BOM FEHLER in Admin {$aCode}!\n";
        exit(1);
    }
    $aJson = json_decode($aRaw, true);
    if (!is_array($aJson)) {
        echo "   ❌ JSON SYNTAX-FEHLER in Admin {$aCode}: " . json_last_error_msg() . "\n";
        exit(1);
    }
    echo "   ✓ Admin [{$aCode}] " . count($aJson) . " Schlüssel fehlerfrei\n";
}

echo "\n3. Prüfung der Navigations-Übersetzungen für alle Hauptsektionen & Plugins:\n";
$navKeys = ['nav.gallery', 'nav.courses', 'nav.blog', 'nav.shop', 'nav.helpdesk', 'nav.games', 'nav.contact', 'nav.impressum'];

foreach ($supported as $sCode => $sInfo) {
    $missingKeys = [];
    foreach ($navKeys as $nk) {
        $trans = LanguageManager::t($nk, '', $sCode);
        if (empty($trans) || $trans === $nk) {
            $missingKeys[] = $nk;
        }
    }
    if (empty($missingKeys)) {
        echo "   ✓ [{$sCode}] Alle Nav-Keys vollständig übersetzt\n";
    } else {
        echo "   ⚠️ [{$sCode}] Fehlende Übersetzungen für: " . implode(', ', $missingKeys) . "\n";
    }
}

echo "\n========================================================\n";
echo "PRÜFUNG ABGESCHLOSSEN.\n";
echo "========================================================\n";
