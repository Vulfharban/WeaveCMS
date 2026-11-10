<?php
/**
 * ============================================================================
 * WeaveCMS Test Suite – PluginUpdater & 3rd-Party Plugin Update System
 * ============================================================================
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/libs/php/UpdateManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginUpdater.php';

echo "========================================================\n";
echo "WEAVECMS PLUGIN UPDATER TEST-SUITE\n";
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

// 1. UpdateManager::normalizeGitHubRepo
echo "1. GitHub-Repository URL Normalisierung:\n";
assertTest(UpdateManager::normalizeGitHubRepo('owner/repo') === 'owner/repo', "owner/repo bleibt owner/repo");
assertTest(UpdateManager::normalizeGitHubRepo('https://github.com/owner/repo') === 'owner/repo', "https://github.com/owner/repo -> owner/repo");
assertTest(UpdateManager::normalizeGitHubRepo('https://github.com/owner/repo.git') === 'owner/repo', "https URL mit .git Suffix -> owner/repo");
assertTest(UpdateManager::normalizeGitHubRepo('git@github.com:owner/repo.git') === 'owner/repo', "SSH Format -> owner/repo");
assertTest(UpdateManager::normalizeGitHubRepo('https://github.com/owner/repo/') === 'owner/repo', "Trailing Slash -> owner/repo");
assertTest(UpdateManager::normalizeGitHubRepo('invalid') === null, "Ungültiges Format wird abgewiesen (null)");
assertTest(UpdateManager::normalizeGitHubRepo(null) === null, "null bleibt null");

// 2. PluginUpdater::detectPluginRoot
echo "\n2. Erkennung des Plugin-Wurzelverzeichnisses (detectPluginRoot):\n";
$tempTestDir = sys_get_temp_dir() . '/test_plugin_root_' . uniqid();
@mkdir($tempTestDir, 0777, true);

// Fall A: Flaches Verzeichnis (plugin.json direkt im Root)
$flatDir = $tempTestDir . '/flat';
@mkdir($flatDir, 0755, true);
file_put_contents($flatDir . '/plugin.json', json_encode(['name' => 'Flat Plugin', 'version' => '1.0.0']));
assertTest(PluginUpdater::detectPluginRoot($flatDir) === $flatDir, "Flaches Verzeichnis wird direkt erkannt");

// Fall B: GitHub Zipball Format (<repo>-<hash>/plugin.json)
$zipballDir = $tempTestDir . '/zipball';
$zipballSub = $zipballDir . '/my-plugin-3a8f9c1';
@mkdir($zipballSub, 0755, true);
file_put_contents($zipballSub . '/plugin.json', json_encode(['name' => 'Zipball Plugin', 'version' => '1.1.0']));
assertTest(PluginUpdater::detectPluginRoot($zipballDir) === $zipballSub, "GitHub Zipball Verzeichnis (<repo>-<hash>/) wird automatisch aufgelöst");

// Fall C: Zweistufig verschachteltes Verzeichnis
$nestedDir = $tempTestDir . '/nested';
$nestedSub1 = $nestedDir . '/outer';
$nestedSub2 = $nestedSub1 . '/inner_plugin';
@mkdir($nestedSub2, 0755, true);
file_put_contents($nestedSub2 . '/plugin.json', json_encode(['name' => 'Nested Plugin', 'version' => '2.0.0']));
assertTest(PluginUpdater::detectPluginRoot($nestedDir) === $nestedSub2, "Tief verschachtelte plugin.json wird erkannt");

// Fall D: Verzeichnis ohne plugin.json
$emptyDir = $tempTestDir . '/empty';
@mkdir($emptyDir, 0755, true);
assertTest(PluginUpdater::detectPluginRoot($emptyDir) === null, "Verzeichnis ohne plugin.json liefert null");

// 3. Pre-Update-Backup Erstellung & Integrität
echo "\n3. Pre-Update-Backup Erstellung & Validierung:\n";
$samplePluginDir = $tempTestDir . '/sample_plugin';
@mkdir($samplePluginDir . '/admin', 0755, true);
file_put_contents($samplePluginDir . '/plugin.json', json_encode([
    'name' => 'Test Plugin',
    'version' => '1.2.0',
    'requires_core' => '>=1.3.0'
]));
file_put_contents($samplePluginDir . '/admin/index.php', '<?php echo "v1.2.0";');

$backupErr = null;
$backupFile = PluginUpdater::createPreUpdateBackup('test_plugin', $samplePluginDir, '1.2.0', $backupErr);
assertTest($backupFile !== null, "Pre-Update Backup wurde erfolgreich erzeugt: {$backupFile}");
assertTest($backupErr === null, "Kein Fehler beim Backup");

$fullBackupPath = dirname(__DIR__) . '/' . PluginUpdater::BACKUP_DIR . '/' . $backupFile;
assertTest(is_file($fullBackupPath), "Backup-ZIP liegt physisch unter backups/plugins/");

$zip = new ZipArchive();
if ($zip->open($fullBackupPath) === true) {
    assertTest($zip->locateName('plugin.json') !== false, "Backup enthält plugin.json");
    assertTest($zip->locateName('admin/index.php') !== false, "Backup enthält admin/index.php");
    assertTest($zip->locateName('plugin_backup_manifest.json') !== false, "Backup enthält plugin_backup_manifest.json Metadaten");
    $manifestData = json_decode($zip->getFromName('plugin_backup_manifest.json'), true);
    assertTest(($manifestData['version'] ?? '') === '1.2.0', "Backup-Manifest enthält Ursprungsversion 1.2.0");
    $zip->close();
}

// 4. Dateiaustausch (swapFiles) & Rollback
echo "\n4. Dateiaustausch (swapFiles) & Rollback:\n";
$sourceUpdateDir = $tempTestDir . '/new_version';
@mkdir($sourceUpdateDir . '/admin', 0755, true);
file_put_contents($sourceUpdateDir . '/plugin.json', json_encode([
    'name' => 'Test Plugin',
    'version' => '1.3.0',
    'requires_core' => '>=1.3.0'
]));
file_put_contents($sourceUpdateDir . '/admin/index.php', '<?php echo "v1.3.0";');
file_put_contents($sourceUpdateDir . '/new_file.txt', 'New feature file');

$replaced = [];
$added = [];
$swapErr = PluginUpdater::swapFiles($sourceUpdateDir, $samplePluginDir, $replaced, $added);
assertTest($swapErr === null, "swapFiles ohne Fehler ausgeführt");
assertTest(in_array('admin/index.php', $replaced, true), "admin/index.php wurde als ersetzt markiert");
assertTest(in_array('new_file.txt', $added, true), "new_file.txt wurde als hinzugefügt markiert");

$updatedContent = file_get_contents($samplePluginDir . '/admin/index.php');
assertTest($updatedContent === '<?php echo "v1.3.0";', "Zieldatei admin/index.php enthält nun den aktualisierten Code");
assertTest(file_exists($samplePluginDir . '/new_file.txt'), "Neue Datei new_file.txt existiert im Zielverzeichnis");

// Test Rollback
$rollbackErr = PluginUpdater::rollback($fullBackupPath, $samplePluginDir);
assertTest($rollbackErr === null, "Rollback ohne Fehler ausgeführt");
$rolledBackContent = file_get_contents($samplePluginDir . '/admin/index.php');
assertTest($rolledBackContent === '<?php echo "v1.2.0";', "Rollback hat den ursprünglichen Stand (v1.2.0) erfolgreich wiederhergestellt");

// Aufräumen der temporären Testdateien
function cleanTestDir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}
cleanTestDir($tempTestDir);
@unlink($fullBackupPath);

// 5. Core-Kompatibilitätsprüfung
echo "\n5. Core-Kompatibilitätsprüfung:\n";
assertTest(PluginManager::isCoreCompatible('>=1.0.0'), ">=1.0.0 ist kompatibel mit aktuellem Core");
assertTest(PluginManager::isCoreCompatible('*'), "* (alle Versionen) ist kompatibel");
assertTest(!PluginManager::isCoreCompatible('>=99.0.0'), ">=99.0.0 wird als inkompatibel erkannt");

// Zusammenfassung
echo "\n========================================================\n";
echo "ERGEBNIS: {$testsPassed} von {$testsTotal} Tests erfolgreich bestanden.\n";
echo "========================================================\n";

if ($testsPassed === $testsTotal) {
    exit(0);
} else {
    exit(1);
}
