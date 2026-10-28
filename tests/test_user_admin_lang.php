<?php
/**
 * WeaveCMS - Test Suite: Backend Internationalization & User Default Language
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/LanguageManager.php';
require_once __DIR__ . '/../libs/php/UserManager.php';
require_once __DIR__ . '/../libs/php/SecurityManager.php';
require_once __DIR__ . '/../libs/php/AdminController.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$testDataFile = sys_get_temp_dir() . '/weave_test_i18n_' . uniqid() . '.json';

function assertTrue($cond, string $msg): void {
    if (!$cond) {
        echo "❌ FAIL: $msg\n";
        exit(1);
    }
    echo "✅ PASS: $msg\n";
}

echo "=== 1. TEST TRANSLATION CATALOGS (DE / EN) ===\n";

$deJsonPath = __DIR__ . '/../libs/lang/admin/de.json';
$enJsonPath = __DIR__ . '/../libs/lang/admin/en.json';

assertTrue(file_exists($deJsonPath), "de.json exists");
assertTrue(file_exists($enJsonPath), "en.json exists");

$deData = json_decode(file_get_contents($deJsonPath), true);
$enData = json_decode(file_get_contents($enJsonPath), true);

assertTrue(is_array($deData) && count($deData) > 0, "de.json valid and non-empty (" . count($deData) . " keys)");
assertTrue(is_array($enData) && count($enData) > 0, "en.json valid and non-empty (" . count($enData) . " keys)");

// Check key alignment
$missingInEn = array_diff_key($deData, $enData);
$missingInDe = array_diff_key($enData, $deData);

assertTrue(empty($missingInEn), "All DE keys exist in EN catalog. Missing: " . implode(', ', array_keys($missingInEn)));
assertTrue(empty($missingInDe), "All EN keys exist in DE catalog. Missing: " . implode(', ', array_keys($missingInDe)));

echo "Total matching admin translation keys: " . count($deData) . "\n";

echo "\n=== 2. TEST LANGUAGE HELPER __a() WITH FALLBACK & PARAMS ===\n";

$t1 = __a('admin.header.title', '', 'de');
assertTrue($t1 === 'Verwaltungspanel', "admin.header.title in DE returns 'Verwaltungspanel'");

$t2 = __a('admin.header.title', '', 'en');
assertTrue($t2 === 'Administration Panel', "admin.header.title in EN returns 'Administration Panel'");

$t3 = __a('admin.dashboard.welcome', '', 'de', ['name' => 'Alice']);
assertTrue(str_contains($t3, 'Alice'), "Placeholder substitution works in DE: $t3");

$t4 = __a('admin.dashboard.welcome', '', 'en', ['name' => 'Bob']);
assertTrue(str_contains($t4, 'Bob') && str_contains($t4, 'Welcome back'), "Placeholder substitution works in EN: $t4");

echo "\n=== 3. TEST USER DEFAULT LANGUAGE LOGIC IN USERMANAGER ===\n";

// Ensure initialized with temp file
$data = ['settings' => ['admin_username' => 'admin']];
UserManager::ensureInitialized($data);
file_put_contents($testDataFile, json_encode($data));

$admin = $data['users'][0] ?? null;
assertTrue($admin !== null, "Default admin user exists");
assertTrue(($admin['admin_lang'] ?? '') === 'de', "Default admin user has admin_lang = 'de'");

// Create user with specific admin language 'en'
$createRes = UserManager::createUser(
    $data,
    'testeditor',
    'editor@example.com',
    'EditorPass123!',
    UserManager::ROLE_EDITOR,
    null,
    'en'
);
assertTrue($createRes['success'] === true, "Created user testeditor with EN language");

$editorUser = UserManager::getUserByIdentifier('testeditor', $data);
assertTrue($editorUser !== null && ($editorUser['admin_lang'] ?? '') === 'en', "Stored editor user has admin_lang = 'en'");

// Update user language
$updateRes = UserManager::updateUserLanguage($data, $editorUser['id'], 'de');
assertTrue($updateRes === true, "updateUserLanguage switched editor to 'de'");

$editorUserUpdated = UserManager::getUserById($editorUser['id'], $data);
assertTrue(($editorUserUpdated['admin_lang'] ?? '') === 'de', "Editor user now has admin_lang = 'de'");

echo "\n=== 4. TEST SESSION AND COOKIE SYNC ON LOGIN ===\n";

UserManager::setCurrentUser($editorUserUpdated);
assertTrue(
    isset($_SESSION[LanguageManager::ADMIN_COOKIE_NAME]) && 
    $_SESSION[LanguageManager::ADMIN_COOKIE_NAME] === 'de',
    "setCurrentUser synchronized session language to user preference ('de')"
);

// Switch user language to 'en' while logged in
$updateResEn = UserManager::updateUser(
    $data,
    $editorUser['id'],
    ['admin_lang' => 'en']
);
assertTrue($updateResEn['success'] === true, "updateUser switched language to 'en'");
assertTrue(
    $_SESSION[LanguageManager::ADMIN_COOKIE_NAME] === 'en',
    "Active session automatically synced to new language 'en'"
);

// Cleanup
@unlink($testDataFile);
echo "\n🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
