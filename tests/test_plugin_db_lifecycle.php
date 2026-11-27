<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Plugin-Datenbank-Lebenszyklus & Schema-Engine (Phase 42)
 * ============================================================================
 * Prüft:
 *  1. Alle 14 offiziellen Plugins in PluginManager::OFFICIAL_PLUGINS + valide storage-Manifeste
 *  2. PluginSchemaManager im JSON-Modus (plugin_migrations.json) & SQLite-PDO-Modus
 *  3. Sofortige Core-Tabellen-Anlage ({prefix}plugin_data, {prefix}plugin_migrations) + JSON-Erstübernahme
 *  4. Idempotente SQL-Tabellenanlage für weave-forum (10 Tabellen) & weave-forum-users (2 Tabellen)
 *  5. 3rd-Party-Plugin Standard: versionierte Migrationen (v1.0.0.php -> v1.1.0.php),
 *     Update-Erkennung, json_only-Modus & strikte Datenerhaltung bei Deaktivierung
 *  6. Explizites Reset-Verbot ohne Bestätigung
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginSchemaManager.php';
require_once dirname(__DIR__) . '/libs/php/storage/StorageManager.php';

function assert_p42(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$tmpRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_p42_test_' . bin2hex(random_bytes(4));
@mkdir($tmpRoot, 0755, true);

try {
    // -------------------------------------------------------------------------
    // 1. Alle 14 offiziellen Plugins und deren Manifest-'storage'-Deklaration prüfen
    // -------------------------------------------------------------------------
    $official = PluginManager::getOfficialPluginSlugs();
    assert_p42(count($official) === 14, 'PluginManager::OFFICIAL_PLUGINS muss exakt 14 Plugins enthalten');
    assert_p42(empty(PluginManager::getMissingOfficialPlugins()), 'Alle 14 offiziellen Plugins müssen im /plugins-Verzeichnis vorhanden sein');

    $discovered = PluginManager::discover(true);
    foreach ($official as $slug) {
        assert_p42(isset($discovered[$slug]), "Offizielles Plugin '{$slug}' nicht entdeckt");
        $st = $discovered[$slug]['storage'] ?? null;
        assert_p42(is_array($st) && in_array($st['mode'] ?? '', ['plugin_data', 'sql_tables', 'json_only', 'none'], true), "Plugin '{$slug}' hat keinen gültigen storage.mode");
    }

    // -------------------------------------------------------------------------
    // 2. Relationale SQL-Plugins (weave-forum + weave-forum-users) via SQLite PDO prüfen
    // -------------------------------------------------------------------------
    $sqliteDb = $tmpRoot . DIRECTORY_SEPARATOR . 'test_db.sqlite';
    $pdo = new PDO('sqlite:' . $sqliteDb, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    StorageManager::ensureCorePluginTables($pdo, 'weave_');
    assert_p42(PluginSchemaManager::tableExists($pdo, 'weave_plugin_data'), 'Tabelle weave_plugin_data muss sofort existieren');
    assert_p42(PluginSchemaManager::tableExists($pdo, 'weave_plugin_migrations'), 'Tabelle weave_plugin_migrations muss sofort existieren');

    require_once dirname(__DIR__) . '/plugins/weave-forum/ForumManager.php';
    require_once dirname(__DIR__) . '/plugins/weave-forum-users/ForumUserManager.php';
    require_once dirname(__DIR__) . '/plugins/weave-forum-users/ForumMessageManager.php';

    $fakeData = ['plugins' => ['active' => ['weave-forum', 'weave-forum-users'], 'configs' => []]];
    ForumManager::ensureSchema($pdo);
    ForumUserManager::ensureSchema($pdo);

    foreach ($discovered['weave-forum']['storage']['tables'] as $tName) {
        $resolved = PluginSchemaManager::resolvePluginTableName($tName, 'weave_');
        assert_p42(PluginSchemaManager::tableExists($pdo, $resolved), "Forum-Tabelle '{$resolved}' muss existieren");
    }
    foreach ($discovered['weave-forum-users']['storage']['tables'] as $tName) {
        $resolved = PluginSchemaManager::resolvePluginTableName($tName, 'weave_');
        assert_p42(PluginSchemaManager::tableExists($pdo, $resolved), "Forum-Users-Tabelle '{$resolved}' muss existieren");
    }

    // -------------------------------------------------------------------------
    // 3. JSON -> plugin_data Erstübernahme (ohne Überschreiben bestehender DB-Daten)
    // -------------------------------------------------------------------------
    $sampleJson = $tmpRoot . DIRECTORY_SEPARATOR . 'sample_plugin.json';
    file_put_contents($sampleJson, json_encode(['hello' => 'from_json', 'count' => 42]));
    $impCount = StorageManager::importPluginJsonFilesToMysql($pdo, 'weave_', ['sample_key' => $sampleJson]);
    assert_p42($impCount === 1, 'Erstübernahme einer vorhandenen JSON-Datei muss 1 Import zählen');

    // Bestehenden DB-Wert ändern und erneut importieren -> darf NICHT überschrieben werden!
    $pdo->prepare("UPDATE weave_plugin_data SET payload = :p WHERE plugin_key = 'sample_key'")
        ->execute([':p' => json_encode(['hello' => 'from_db', 'count' => 99])]);
    $impCount2 = StorageManager::importPluginJsonFilesToMysql($pdo, 'weave_', ['sample_key' => $sampleJson]);
    assert_p42($impCount2 === 0, 'Zweiter Import darf bestehende DB-Daten niemals überschreiben');
    $valAfter = json_decode((string)$pdo->query("SELECT payload FROM weave_plugin_data WHERE plugin_key = 'sample_key'")->fetchColumn(), true);
    assert_p42(($valAfter['hello'] ?? '') === 'from_db', 'DB-Wert muss unverändert erhalten bleiben');

    // -------------------------------------------------------------------------
    // 4. 3rd-Party-Plugin mit versionierten Migrationen (v1.0.0 -> v1.1.0) & Datenerhaltung
    // -------------------------------------------------------------------------
    $tpDir = $tmpRoot . DIRECTORY_SEPARATOR . 'plugins';
    $p3Dir = $tpDir . DIRECTORY_SEPARATOR . 'acme-notes';
    @mkdir($p3Dir . DIRECTORY_SEPARATOR . 'migrations', 0755, true);

    file_put_contents($p3Dir . '/plugin.php', "<?php\n// Dummy 3rd-party entry\n");
    file_put_contents($p3Dir . '/migrations/v1.0.0.php', <<<'PHP'
<?php
return static function (array &$ctx): void {
    if ($ctx['pdo'] instanceof PDO) {
        $tbl = $ctx['prefix'] . 'acme_notes';
        $ctx['pdo']->exec("CREATE TABLE IF NOT EXISTS `{$tbl}` (id TEXT PRIMARY KEY, body TEXT NOT NULL)");
        $ctx['pdo']->exec("INSERT OR IGNORE INTO `{$tbl}` (id, body) VALUES ('n1', 'Initial Note')");
    }
};
PHP
    );
    file_put_contents($p3Dir . '/migrations/v1.1.0.php', <<<'PHP'
<?php
return static function (array &$ctx): void {
    if ($ctx['pdo'] instanceof PDO) {
        $tbl = $ctx['prefix'] . 'acme_notes';
        $ctx['pdo']->exec("ALTER TABLE `{$tbl}` ADD COLUMN priority INTEGER NOT NULL DEFAULT 1");
    }
};
PHP
    );
    file_put_contents($p3Dir . '/plugin.json', json_encode([
        'name' => 'Acme Notes',
        'slug' => 'acme-notes',
        'version' => '1.0.0',
        'entry_file' => 'plugin.php',
        'storage' => [
            'mode' => 'sql_tables',
            'schema_version' => '1.0.0',
            'tables' => ['{prefix}acme_notes'],
            'migrations_dir' => 'migrations',
        ],
    ], JSON_PRETTY_PRINT));

    PluginManager::setPluginsDirOverride($tpDir);
    $tpData = ['plugins' => ['active' => [], 'configs' => []]];

    // Status vor Aktivierung: not_initialized / missing_tables
    $repBefore = PluginSchemaManager::getSchemaStatusReport($tpData, $pdo, 'weave_');
    assert_p42(($repBefore['acme-notes']['status'] ?? '') === 'missing_tables', 'Vor Initialisierung muss acme-notes als missing_tables erkannt werden');

    // Initiale Schema-Ausführung für v1.0.0
    $res1 = PluginSchemaManager::ensurePluginSchema('acme-notes', $tpData, $pdo, 'weave_');
    assert_p42($res1['success'] && $res1['applied_migrations'] === ['v1.0.0'], 'v1.0.0 Migration muss einmalig ausgeführt werden');
    assert_p42(PluginSchemaManager::tableExists($pdo, 'weave_acme_notes'), '3rd-Party-Tabelle weave_acme_notes muss angelegt sein');

    // Upgrade auf v1.1.0 simulieren -> Report zeigt needs_migration
    file_put_contents($p3Dir . '/plugin.json', json_encode([
        'name' => 'Acme Notes',
        'slug' => 'acme-notes',
        'version' => '1.1.0',
        'entry_file' => 'plugin.php',
        'storage' => [
            'mode' => 'sql_tables',
            'schema_version' => '1.1.0',
            'tables' => ['{prefix}acme_notes'],
            'migrations_dir' => 'migrations',
        ],
    ], JSON_PRETTY_PRINT));
    PluginManager::clearCache();

    $repUpgrade = PluginSchemaManager::getSchemaStatusReport($tpData, $pdo, 'weave_');
    assert_p42(($repUpgrade['acme-notes']['status'] ?? '') === 'needs_migration', 'Nach Plugin-Update muss Status needs_migration gemeldet werden');

    $res2 = PluginSchemaManager::ensurePluginSchema('acme-notes', $tpData, $pdo, 'weave_');
    assert_p42($res2['success'] && $res2['applied_migrations'] === ['v1.1.0'], 'Nur v1.1.0 Migration darf nachgezogen werden');

    // Idempotenz prüfen: Zweiter Aufruf führt keine Migrationen erneut aus
    $res3 = PluginSchemaManager::ensurePluginSchema('acme-notes', $tpData, $pdo, 'weave_');
    assert_p42($res3['success'] && empty($res3['applied_migrations']), 'Wiederholtes ensurePluginSchema muss idempotent sein');

    // Datenerhaltungs-Garantie beim Deaktivieren & Reaktivieren (über JSON-Registry-Pfad für activatePlugin)
    $regJson = $tmpRoot . DIRECTORY_SEPARATOR . 'plugin_migrations.json';
    PluginSchemaManager::setTestRegistryPath($regJson);

    $tpData['plugins']['active'][] = 'acme-notes';
    $deactRes = PluginManager::deactivatePlugin('acme-notes', $tpData);
    assert_p42(!empty($deactRes['success']) && !in_array('acme-notes', $tpData['plugins']['active'], true), 'Deaktivierung muss erfolgreich sein');
    assert_p42(PluginSchemaManager::tableExists($pdo, 'weave_acme_notes'), 'Tabelle darf beim Deaktivieren NIEMALS gelöscht werden');
    $noteBody = $pdo->query("SELECT body FROM weave_acme_notes WHERE id = 'n1'")->fetchColumn();
    assert_p42($noteBody === 'Initial Note', 'Daten in Plugin-Tabellen müssen beim Deaktivieren vollständig erhalten bleiben');

    $actRes = PluginManager::activatePlugin('acme-notes', $tpData);
    assert_p42(!empty($actRes['success']) && in_array('acme-notes', $tpData['plugins']['active'], true), 'Reaktivierung muss erfolgreich sein');
    assert_p42(is_file($regJson), 'Schema-Registry (JSON-Fallback) muss beim Aktivieren geschrieben werden');

    // Explizite Reset-Schutzprüfung
    $noConf = PluginSchemaManager::resetPluginData('acme-notes', $tpData, false, $pdo, 'weave_');
    assert_p42(empty($noConf['success']), 'resetPluginData ohne confirmed=true muss verweigert werden');

} finally {
    PluginManager::setPluginsDirOverride(null);
    PluginSchemaManager::setTestRegistryPath(null);
    PluginSchemaManager::clearCache();
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        if ($f->isDir()) @rmdir($f->getPathname());
        else @unlink($f->getPathname());
    }
    @rmdir($tmpRoot);
}

echo "OK: Phase 42 Plugin-DB-Lebenszyklus, Schema-Registry, 3rd-Party-Migrationen & Datenerhaltung erfolgreich!\n";
