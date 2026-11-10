<?php
/**
 * ============================================================================
 * WeaveCMS Test Suite – CoreUpdater & Plugin Packaging Test
 * ============================================================================
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/CoreUpdater.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';

echo "========================================================\n";
echo "WEAVECMS CORE UPDATER TEST-SUITE\n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsTotal = 0;

function assertTest(bool $condition, string $label): void {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  ✓ [PASS] {$label}\n";
    } else {
        echo "  ✗ [FAIL] {$label}\n";
    }
}

// 1. CORE_PATHS Validierung
echo "1. CORE_PATHS Validierung:\n";
assertTest(in_array('plugins/', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält 'plugins/'");
assertTest(in_array('.htaccess', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält '.htaccess'");
assertTest(in_array('libs/', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält 'libs/'");
assertTest(in_array('admin/', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält 'admin/'");
assertTest(in_array('views/', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält 'views/'");
assertTest(in_array('api/', CoreUpdater::CORE_PATHS, true), "CORE_PATHS enthält 'api/'");

// 2. isAllowedPath Whitelisting
echo "\n2. isAllowedPath Whitelisting:\n";
assertTest(CoreUpdater::isAllowedPath('plugins/weave-games/plugin.json'), "plugins/weave-games/plugin.json ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('plugins/weave-games/views/portal.php'), "plugins/weave-games/views/portal.php ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('plugins/weave-helpdesk/plugin.json'), "plugins/weave-helpdesk/plugin.json ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('plugins/weave-helpdesk/admin/users.php'), "plugins/weave-helpdesk/admin/users.php ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('.htaccess'), ".htaccess ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('plugins/.htaccess'), "plugins/.htaccess ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('libs/php/CoreUpdater.php'), "libs/php/CoreUpdater.php ist erlaubt");
assertTest(CoreUpdater::isAllowedPath('config.php'), "config.php ist erlaubt");

// 3. isAllowedPath Schutz von Benutzerdaten (FORBIDDEN_PATHS)
echo "\n3. isAllowedPath Schutz von Benutzerdaten (FORBIDDEN_PATHS):\n";
assertTest(!CoreUpdater::isAllowedPath('install.php'), "install.php ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('installed.lock'), "installed.lock ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('data.json'), "data.json ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('data.sample.json'), "data.sample.json ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('config.local.php'), "config.local.php ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('data/helpdesk_users.json'), "data/helpdesk_users.json ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('data/helpdesk_tickets.json'), "data/helpdesk_tickets.json ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('data/games_servers.json'), "data/games_servers.json ist gesperrt");
assertTest(!CoreUpdater::isAllowedPath('uploads/avatar.png'), "uploads/avatar.png ist gesperrt");

// 4. PluginManager Offizielle Plugins
echo "\n4. PluginManager Offizielle Plugins:\n";
$official = PluginManager::getOfficialPluginSlugs();
assertTest(in_array('weave-games', $official, true), "weave-games ist in offiziellen Plugins registriert");
assertTest(in_array('weave-helpdesk', $official, true), "weave-helpdesk ist in offiziellen Plugins registriert");
assertTest(count($official) >= 11, "Mindestens 11 offizielle Plugins registriert (" . count($official) . " gefunden)");

$missing = PluginManager::getMissingOfficialPlugins();
assertTest(is_array($missing), "getMissingOfficialPlugins liefert ein Array");

echo "\n--------------------------------------------------------\n";
echo "Testergebnis: {$testsPassed} / {$testsTotal} Tests bestanden.\n";
if ($testsPassed === $testsTotal) {
    echo "ALLE TESTS ERFOLGREICH BESTANDEN! ✓\n";
    exit(0);
} else {
    echo "EINIGE TESTS SIND FEHLGESCHLAGEN! ✗\n";
    exit(1);
}
