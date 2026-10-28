<?php
/**
 * Test-Suite: Phase 26 - Benutzerdefinierte Dashboard-Schnellzugriffe (Custom Action-Hub)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/QuickActionManager.php';
require_once __DIR__ . '/../libs/php/UserManager.php';
require_once __DIR__ . '/../libs/php/AdminController.php';

echo "========================================================\n";
echo "WeaveCMS Test: Phase 26 Custom Action-Hub\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assert_test(string $name, bool $cond) {
    global $passCount, $failCount;
    if ($cond) {
        echo "  [PASS] {$name}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$name}\n";
        $failCount++;
    }
}

// 1. Katalog & Kategorien
echo "1. Aktionskatalog & Kategorien:\n";
$catalog = QuickActionManager::getCatalog();
$categories = QuickActionManager::getCategories();
assert_test("Katalog enthält mindestens 25 Aktionen", count($catalog) >= 25);
assert_test("Kategorien sind vollständig (mindestens 6)", count($categories) >= 6);

$allValid = true;
foreach ($catalog as $act) {
    if (empty($act['id']) || empty($act['title']) || empty($act['category']) || empty($act['icon']) || empty($act['min_role'])) {
        $allValid = false;
        break;
    }
    if (!isset($categories[$act['category']])) {
        $allValid = false;
        break;
    }
}
assert_test("Alle Aktionen im Katalog besitzen Pflichtfelder und gültige Kategorien", $allValid);

// 2. Presets
echo "\n2. Workflow-Presets:\n";
$presets = QuickActionManager::getPresets();
assert_test("Presets enthalten allrounder, editor, admin, minimal", 
    isset($presets['preset_allrounder']) && 
    isset($presets['preset_editor']) && 
    isset($presets['preset_admin']) && 
    isset($presets['preset_minimal'])
);

$presetsValid = true;
foreach ($presets as $p) {
    if (empty($p['name']) || empty($p['actions']) || !is_array($p['actions'])) {
        $presetsValid = false;
        break;
    }
    foreach ($p['actions'] as $actId) {
        if (!isset($catalog[$actId])) {
            $presetsValid = false;
            break 2;
        }
    }
}
assert_test("Alle Preset-Aktionen referenzieren existierende Katalog-IDs", $presetsValid);

// 3. RBAC-Rollenfilterung
echo "\n3. Rollenfilterung & RBAC-Prüfung:\n";
$mockData = [
    'settings' => [],
    'users' => [
        [
            'id' => 'usr_admin',
            'username' => 'admin_tester',
            'role' => UserManager::ROLE_ADMIN
        ],
        [
            'id' => 'usr_editor',
            'username' => 'editor_tester',
            'role' => UserManager::ROLE_EDITOR
        ],
        [
            'id' => 'usr_mod',
            'username' => 'mod_tester',
            'role' => UserManager::ROLE_MODERATOR
        ]
    ]
];

$adminActions = QuickActionManager::getUserQuickActions($mockData['users'][0], $mockData);
$editorActions = QuickActionManager::getUserQuickActions($mockData['users'][1], $mockData);
$modActions = QuickActionManager::getUserQuickActions($mockData['users'][2], $mockData);

$editorHasAdminAction = false;
foreach ($editorActions as $act) {
    if ($act['min_role'] === UserManager::ROLE_ADMIN) {
        $editorHasAdminAction = true;
        break;
    }
}
assert_test("Redakteur erhält KEINE Admin-gesperrten Aktionen (create_backup, user_management etc.)", !$editorHasAdminAction);

$adminHasBackup = false;
foreach ($adminActions as $act) {
    if ($act['id'] === 'create_backup') {
        $adminHasBackup = true;
        break;
    }
}
assert_test("Administrator hat Zugriff auf Wartungs- und Backup-Aktionen", $adminHasBackup);

// 4. Speichern & Zurücksetzen
echo "\n4. Speichern & Zurücksetzen von Benutzer-Schnellzugriffen:\n";
$tempDataFile = sys_get_temp_dir() . '/test_weave_qa_data_' . uniqid() . '.json';
$customSelection = ['new_blog_post', 'manage_gallery', 'upload_media'];
$saveOk = QuickActionManager::saveUserQuickActions('usr_editor', $customSelection, $tempDataFile, $mockData);
assert_test("Speichern von benutzerdefinierten Schnellzugriffen erfolgreich", $saveOk);

$savedActions = QuickActionManager::getUserQuickActions($mockData['users'][1], $mockData);
$savedIds = array_column($savedActions, 'id');
assert_test("Geladene Aktionen entsprechen der individuellen Auswahl", $savedIds === $customSelection);

$resetOk = QuickActionManager::resetUserQuickActions('usr_editor', UserManager::ROLE_EDITOR, $tempDataFile, $mockData);
assert_test("Zurücksetzen auf Rollen-Standard erfolgreich", $resetOk);

$resetActions = QuickActionManager::getUserQuickActions($mockData['users'][1], $mockData);
$resetIds = array_column($resetActions, 'id');
assert_test("Nach Reset greift wieder das Editor-Preset", $resetIds === QuickActionManager::getDefaultActionIds(UserManager::ROLE_EDITOR));

// 5. AdminController Dispatching
echo "\n5. AdminController Dispatching:\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$csrf = AuthManager::getCsrfToken();
$_POST['csrf_token'] = $csrf;
$_POST['action'] = 'save_quickactions';
$_POST['action_ids_json'] = json_encode(['new_blog_post', 'manage_gallery', 'inbox']);

// Session simulieren
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = 'usr_admin';
$_SESSION['admin_role'] = UserManager::ROLE_ADMIN;

$res = AdminController::handlePost($tempDataFile, $mockData, $csrf);
assert_test("AdminController verarbeitet save_quickactions erfolgreich", !empty($res['success_msg']));
assert_test("Ziel-Tab nach save_quickactions ist tab-dashboard", $res['active_tab'] === 'tab-dashboard');

$_POST['action'] = 'reset_quickactions';
$resReset = AdminController::handlePost($tempDataFile, $mockData, $csrf);
assert_test("AdminController verarbeitet reset_quickactions erfolgreich", !empty($resReset['success_msg']));

// Cleanup temp file
if (file_exists($tempDataFile)) {
    @unlink($tempDataFile);
}

// 6. UI-Widget & Modal Rendering
echo "\n6. UI-Widget & Modal Rendering:\n";
ob_start();
QuickActionManager::renderDashboardWidget($mockData['users'][0], $mockData);
$widgetHtml = ob_get_clean();
assert_test("Dashboard-Widget rendert Schnellzugriffe-Container und Kacheln", 
    strpos($widgetHtml, 'quickactions-widget-container') !== false &&
    strpos($widgetHtml, 'quickactions-grid') !== false &&
    strpos($widgetHtml, 'openQuickActionsModal()') !== false
);

ob_start();
QuickActionManager::renderConfigModal($mockData['users'][0], $mockData, $csrf);
$modalHtml = ob_get_clean();
assert_test("Konfigurations-Modal rendert Suchfeld, Presets und Formular", 
    strpos($modalHtml, 'modal-quickactions-config') !== false &&
    strpos($modalHtml, 'quickactions-search-input') !== false &&
    strpos($modalHtml, 'applyQuickActionPreset') !== false &&
    strpos($modalHtml, 'qa-action-ids-input') !== false
);

echo "\n========================================================\n";
echo "Testergebnis: {$passCount} von " . ($passCount + $failCount) . " Tests bestanden\n";
echo "========================================================\n";
