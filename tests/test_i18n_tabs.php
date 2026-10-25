<?php
/**
 * Test für Sprachumschaltung & Multi-Language Speicherung
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/LanguageManager.php';
require_once __DIR__ . '/../libs/php/AdminController.php';

function run_test($name, $condition) {
    if ($condition) {
        echo "  [PASS] $name\n";
    } else {
        echo "  [FAIL] $name\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "WeaveCMS Test: i18n & Language Switcher Tabs\n";
echo "========================================================\n\n";

$tmpDataFile = __DIR__ . '/temp_data_test.json';
$sampleData = [
    'settings' => [
        'language' => 'de',
        'active_languages' => ['de', 'en', 'fr']
    ],
    'texts' => [
        'hero_title' => 'Deutscher Haupttitel',
        'hero_subtitle' => 'Deutscher Untertitel'
    ],
    'texts_i18n' => [
        'en' => [
            'hero_title' => 'English Main Title'
        ]
    ]
];
file_put_contents($tmpDataFile, json_encode($sampleData));

// Test 1: LanguageManager
$t1 = LanguageManager::getContentText($sampleData, 'hero_title', 'en');
run_test("LanguageManager liefert englische Übersetzung für hero_title", $t1 === 'English Main Title');

$t2 = LanguageManager::getContentText($sampleData, 'hero_subtitle', 'en');
run_test("LanguageManager fällt auf deutschen Text zurück wenn englische Übersetzung fehlt", $t2 === 'Deutscher Untertitel');

// Test 2: AdminController::handleTexts mit trans_en_ & trans_fr_
$_POST = [
    'action' => 'update_texts',
    'target_tab' => 'tab-texts',
    'hero_title' => 'Aktualisierter DE Titel',
    'hero_subtitle' => 'Aktualisierter DE Untertitel',
    'trans_en_hero_title' => 'Updated EN Title',
    'trans_en_hero_subtitle' => 'Updated EN Subtitle',
    'trans_fr_hero_title' => 'Titre FR',
    'trans_fr_hero_subtitle' => '' // leer -> sollte fallback sein
];

$success_msg = '';
$error_msg = '';
$class = new ReflectionClass('AdminController');
$method = $class->getMethod('handleTexts');
$method->invokeArgs(null, [$tmpDataFile, &$sampleData, &$success_msg, &$error_msg]);

run_test("Speichern ohne Fehler", empty($error_msg) && !empty($success_msg));
run_test("DE Titel aktualisiert", $sampleData['texts']['hero_title'] === 'Aktualisierter DE Titel');
run_test("EN Titel gespeichert", ($sampleData['texts_i18n']['en']['hero_title'] ?? '') === 'Updated EN Title');
run_test("EN Untertitel gespeichert", ($sampleData['texts_i18n']['en']['hero_subtitle'] ?? '') === 'Updated EN Subtitle');
run_test("FR Titel gespeichert", ($sampleData['texts_i18n']['fr']['hero_title'] ?? '') === 'Titre FR');
run_test("Leeres FR Feld nicht in texts_i18n eingetragen (Fallback)", !isset($sampleData['texts_i18n']['fr']['hero_subtitle']));

// Cleanup
if (file_exists($tmpDataFile)) {
    unlink($tmpDataFile);
}

echo "\n========================================================\n";
echo "Testergebnis: Alle 8 i18n Tests erfolgreich bestanden!\n";
echo "========================================================\n";
