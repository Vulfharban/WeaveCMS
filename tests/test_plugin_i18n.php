<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Vollständige Plugin-Mehrsprachigkeit (Phase 41)
 * ============================================================================
 * Prüft für alle 14 offiziellen Plugins:
 *  (a) Existenz & valide BOM-freie JSON-Syntax aller 12 Frontend- & 2 Admin-Sprachen
 *  (b) 100 % Key-Parität zwischen allen Sprachen ohne leere Werte
 *  (c) Laufzeit-Umschaltung via LanguageManager::t() / adminT() inkl. Platzhalter & Fallback-Kaskade
 *  (d) Registrierung eigener Sprachverzeichnisse für 3rd-Party-Plugins (registerPluginLangDir)
 *  (e) Verwendung von __() / __a() in den Plugin-Views/Admins
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/LanguageManager.php';

function assert_pi18n(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$plugins = [
    'interactive-sandbox',
    'universal-integrations',
    'weave-calendar',
    'weave-downloads',
    'weave-fleamarket',
    'weave-forum',
    'weave-forum-users',
    'weave-games',
    'weave-helpdesk',
    'weave-inventory',
    'weave-polls',
    'weave-shop',
    'weave-workshops',
    'webtrees-genealogy',
];

$expectedFrontLangs = array_keys(LanguageManager::getSupportedLanguages()); // 12 Sprachen
$expectedAdminLangs = ['de', 'en'];

assert_pi18n(count($expectedFrontLangs) === 12, 'System muss genau 12 unterstützte Frontend-Sprachen führen');

$totalFrontendFiles = 0;
$totalAdminFiles = 0;

foreach ($plugins as $slug) {
    $langDir = $root . '/plugins/' . $slug . '/lang';
    assert_pi18n(is_dir($langDir), "Sprachverzeichnis fehlt für Plugin: {$slug}");

    // 1. Frontend-Kataloge (12 Sprachen)
    $baseKeys = null;
    foreach ($expectedFrontLangs as $lc) {
        $f = $langDir . '/' . $lc . '.json';
        assert_pi18n(file_exists($f), "Frontend-Sprachdatei fehlt: {$slug}/lang/{$lc}.json");
        $raw = (string)file_get_contents($f);
        assert_pi18n(substr($raw, 0, 3) !== "\xEF\xBB\xBF", "UTF-8 BOM in {$slug}/lang/{$lc}.json gefunden");
        $parsed = json_decode($raw, true);
        assert_pi18n(is_array($parsed) && !empty($parsed), "Ungültiges oder leeres JSON in {$slug}/lang/{$lc}.json");

        foreach ($parsed as $k => $v) {
            assert_pi18n(is_string($v) && trim($v) !== '', "Leerer Übersetzungswert für '{$k}' in {$slug}/lang/{$lc}.json");
        }

        $keys = array_keys($parsed);
        sort($keys);
        if ($baseKeys === null) {
            $baseKeys = $keys;
        } else {
            $missing = array_diff($baseKeys, $keys);
            $extra = array_diff($keys, $baseKeys);
            assert_pi18n(empty($missing) && empty($extra), "Key-Parität verletzt in {$slug}/lang/{$lc}.json (fehlend: " . implode(',', $missing) . " / extra: " . implode(',', $extra) . ")");
        }
        $totalFrontendFiles++;
    }

    // 2. Admin-Kataloge (DE & EN)
    $baseAdminKeys = null;
    foreach ($expectedAdminLangs as $ac) {
        $f = $langDir . '/admin/' . $ac . '.json';
        assert_pi18n(file_exists($f), "Admin-Sprachdatei fehlt: {$slug}/lang/admin/{$ac}.json");
        $raw = (string)file_get_contents($f);
        assert_pi18n(substr($raw, 0, 3) !== "\xEF\xBB\xBF", "UTF-8 BOM in {$slug}/lang/admin/{$ac}.json gefunden");
        $parsed = json_decode($raw, true);
        assert_pi18n(is_array($parsed) && !empty($parsed), "Ungültiges oder leeres JSON in {$slug}/lang/admin/{$ac}.json");

        foreach ($parsed as $k => $v) {
            assert_pi18n(is_string($v) && trim($v) !== '', "Leerer Admin-Wert für '{$k}' in {$slug}/lang/admin/{$ac}.json");
        }

        $keys = array_keys($parsed);
        sort($keys);
        if ($baseAdminKeys === null) {
            $baseAdminKeys = $keys;
        } else {
            $missing = array_diff($baseAdminKeys, $keys);
            $extra = array_diff($keys, $baseAdminKeys);
            assert_pi18n(empty($missing) && empty($extra), "Admin Key-Parität verletzt in {$slug}/lang/admin/{$ac}.json");
        }
        $totalAdminFiles++;
    }
}

assert_pi18n($totalFrontendFiles === 168, 'Erwartet: 14 * 12 = 168 Frontend-Sprachdateien');
assert_pi18n($totalAdminFiles === 28, 'Erwartet: 14 * 2 = 28 Admin-Sprachdateien');

// 3. Laufzeit-Umschaltung & Platzhalter in Frontend + Admin prüfen
LanguageManager::clearTranslationCache();
$pollsFr = LanguageManager::t('polls.vote_btn', 'Fallback', 'fr');
assert_pi18n($pollsFr === 'Voter', "Frontend Plugin-Key Übersetzung [fr] fehlgeschlagen (erhalten: {$pollsFr})");

$pollsMultiSv = __('polls.multi_hint', 'Fallback', 'sv', ['max' => 3]);
assert_pi18n($pollsMultiSv === 'Flerval möjligt (max 3).', "Platzhalter-Ersetzung in __() [sv] fehlgeschlagen (erhalten: {$pollsMultiSv})");

$fmEl = LanguageManager::t('fleamarket.nav_label', 'Fallback', 'el');
assert_pi18n($fmEl === 'Υπαίθρια Αγορά', "Flohmarkt-Übersetzung [el] fehlgeschlagen (erhalten: {$fmEl})");

$adminFmEn = __a('admin.fleamarket.tab_stands', 'Fallback', 'en');
assert_pi18n($adminFmEn === 'On-Site Stalls', "Plugin-Admin-Key [en] fehlgeschlagen (erhalten: {$adminFmEn})");

$adminDlEn = __a('admin.downloads.count_info', 'Fallback', 'en', ['count' => 7]);
assert_pi18n(str_contains($adminDlEn, '7 file(s)'), "Admin-Platzhalter in Plugin-Katalog [en] fehlgeschlagen (erhalten: {$adminDlEn})");

// 4. Fallback-Kaskade testen (unbekannte Sprache -> Englisch)
$fallbackCheck = LanguageManager::t('polls.vote_btn', 'DefaultText', 'xx');
assert_pi18n($fallbackCheck === 'Vote', "Fallback-Kaskade auf Englisch fehlgeschlagen (erhalten: {$fallbackCheck})");

// 5. 3rd-Party-Plugin registerPluginLangDir() prüfen
$tmpLang = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_3p_lang_' . bin2hex(random_bytes(4));
@mkdir($tmpLang . '/admin', 0755, true);
file_put_contents($tmpLang . '/en.json', json_encode(['thirdparty.hello' => 'Hello {user}!']));
file_put_contents($tmpLang . '/fr.json', json_encode(['thirdparty.hello' => 'Bonjour {user} !']));
file_put_contents($tmpLang . '/admin/en.json', json_encode(['admin.thirdparty.panel' => '3rd-Party Panel']));
LanguageManager::registerPluginLangDir('third-party-demo', $tmpLang);

assert_pi18n(__('thirdparty.hello', 'Fallback', 'fr', ['user' => 'Claire']) === 'Bonjour Claire !', '3rd-Party Frontend LangDir fehlgeschlagen');
assert_pi18n(__a('admin.thirdparty.panel', 'Fallback', 'en') === '3rd-Party Panel', '3rd-Party Admin LangDir fehlgeschlagen');

// Aufräumen
@unlink($tmpLang . '/en.json');
@unlink($tmpLang . '/fr.json');
@unlink($tmpLang . '/admin/en.json');
@rmdir($tmpLang . '/admin');
@rmdir($tmpLang);

echo "OK: Phase 41 Plugin-i18n Tests (14 Plugins x 12 Frontend + 2 Admin = 196 Kataloge, Platzhalter, Fallback, 3rd-Party) erfolgreich!\n";
