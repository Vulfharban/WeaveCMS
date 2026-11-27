# WeaveCMS – Modulares Plugin- & Erweiterungssystem (Phase 13)

Das Plugin-System von WeaveCMS erlaubt es Entwicklern und Betreibern, die Funktionalität des CMS modular zu erweitern, ohne den Core-Code zu verändern.

---

## Verzeichnisstruktur eines Plugins

Jedes Plugin liegt in einem eigenen Unterverzeichnis unterhalb von `plugins/<plugin-slug>/`:

```text
plugins/
└── <plugin-slug>/
    ├── plugin.json        # Pflicht: Metadaten-Manifest
    ├── plugin.php         # Pflicht: Haupteinstiegspunkt & Hook-Registrierungen
    ├── admin/             # Optional: Eigene Backend-Views und Einstellungsmasken
    ├── views/             # Optional: Frontend-Templates & Sektionen
    └── assets/            # Optional: Statische Dateien (CSS, JS, Icons)
```

---

## 1. Das Manifest (`plugin.json`)

Jedes Plugin muss eine gültige `plugin.json` enthalten:

```json
{
  "name": "Mein Plugin Name",
  "slug": "mein-plugin",
  "version": "1.0.0",
  "author": "Entwickler Name",
  "description": "Kurze Beschreibung des Funktionsumfangs.",
  "category": "integration",
  "icon": "extension",
  "entry_file": "plugin.php",
  "requires_core": ">=1.3.0",
  "settings_tab": "mein_plugin_tab",
  "github_repo": "owner/repository",
  "update_url": "https://example.com/plugin_manifest.json"
}
```

### Kategorien:
- `content`: Eigene Sektionen, Markdown-Filter, Blog-Erweiterungen
- `integration`: Externe APIs, Stammbaum-Systeme (z. B. Webtrees), Social Media
- `theme`: Design-Vorlagen, CSS-Erweiterungen, Farbpaletten
- `security`: Erweiterte Berechtigungsstufen, zusätzliche 2FA

---

## 2. Der Haupteinstiegspunkt (`plugin.php`)

Die `plugin.php` wird geladen, wenn das Plugin im Backend aktiviert wurde. Hier werden Hooks registriert:

```php
<?php
// Direkten Zugriff verhindern
if (!class_exists('PluginManager')) {
    exit;
}

// Action Hook: Eigenen Einstellungsbereich im Adminbereich einhängen
PluginManager::addAction('admin_render_plugin_settings_mein-plugin', function($data, $csrf_token) {
    $config = PluginManager::getPluginConfig('mein-plugin', $data);
    require __DIR__ . '/admin/settings.php';
});

// Filter Hook: Shortcodes im Content auflösen
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (strpos($content, '[mein_shortcode]') !== false) {
        return str_replace('[mein_shortcode]', '<div class="alert alert-info">Hallo Welt!</div>', $content);
    }
    return $content;
});

// Filter Hook: Konfiguration vor dem Speichern validieren
PluginManager::addFilter('plugin_validate_config_mein-plugin', function($submitted, $data) {
    return [
        'api_key' => trim($submitted['api_key'] ?? '')
    ];
});
```

---

## 3. Standard-Hooks in WeaveCMS

### Action Hooks
| Hook | Beschreibung | Parameter |
|------|--------------|-----------|
| `plugins_loaded` | Alle aktiven Plugins wurden geladen | `$context` ('admin', 'frontend', 'all') |
| `plugin_loaded` | Ein einzelnes Plugin wurde geladen | `$slug`, `$manifest`, `$context` |
| `plugin_activated` | Ein Plugin wurde aktiviert | `$slug`, `$manifest` |
| `plugin_deactivated` | Ein Plugin wurde deaktiviert | `$slug` |
| `admin_render_plugin_settings_{slug}` | Rendert die Einstellungen des Plugins | `$data`, `$csrf_token` |
| `frontend_render_custom_section` | Rendert eine eigene Sektion im Frontend | `$sectionId`, `$data` |

### Filter Hooks
| Hook | Beschreibung | Parameter |
|------|--------------|-----------|
| `content_shortcodes` | Transformation von Text & Markdown | `$content`, `$data` |
| `plugin_validate_config_{slug}` | Validierung der Einstellungen vor Speicherung | `$submittedData`, `$data` |

---

## 4. Konfiguration lesen & schreiben

```php
// Konfiguration auslesen (mit Defaults)
$config = PluginManager::getPluginConfig('mein-plugin', $data, ['default_key' => 'default_val']);

// Konfiguration speichern (im Admin-Controller oder Plugin-Action)
PluginManager::savePluginConfig('mein-plugin', $newConfig, $data);
save_data($dataFile, $data);
```

---

## 5. Automatische Updates via GitHub & Remote-Endpunkte

Jedes 3rd-Party Plugin kann sich unabhängig vom CMS-Core über GitHub Releases oder alternative JSON-Manifeste aktualisieren:

### Deklaration in `plugin.json`
```json
{
  "github_repo": "mein-user/mein-plugin"
}
```
*Optional für eigene Update-Server:* `"update_url": "https://mein-server.de/update.json"`

### GitHub Release Richtlinien
1. **Tag & Release:** Erstelle ein GitHub Release mit SemVer-Tag (z. B. `v1.1.0`).
2. **Release ZIP:** Lade entweder ein Release-Asset ZIP hoch oder nutze den automatischen Quellcode-Zipball von GitHub. WeaveCMS erkennt das Verzeichnis der `plugin.json` automatisch und schützt vor Zip-Slip.
3. **Integritätsprüfung:** Gib im Release-Text optional `SHA256: <hash>` an – WeaveCMS prüft die Prüfsumme vor dem Entpacken.
4. **Core-Kompatibilität:** Definiere `"requires_core": ">=1.3.0"`, um inkompatible CMS-Installationen vor Fehlern zu schützen.
5. **Pre-Update-Backup:** WeaveCMS sichert das bestehende Plugin vor jedem Update automatisch unter `backups/plugins/` und führt bei Fehlern ein sofortiges Rollback durch.

---

## 6. Mehrsprachigkeit (i18n Standard – Phase 41)

Alle Plugins sollten sowohl im Frontend als auch im Backend vollständig mehrsprachig aufgebaut werden:

```text
plugins/<plugin-slug>/lang/
├── de.json        # Frontend-Übersetzungen (insgesamt alle 12 CMS-Sprachen:
├── en.json        # da, de, el, en, es, fi, fr, it, nl, no, pt, sv)
├── fr.json
└── admin/
    ├── de.json    # Backend-Übersetzungen (__a())
    └── en.json
```

- `LanguageManager` lädt automatisch alle `plugins/*/lang/{code}.json` sowie `plugins/*/lang/admin/{code}.json`.
- Abweichende Verzeichnisse können optional über `"lang_dir": "custom_lang"` in `plugin.json` oder via `LanguageManager::registerPluginLangDir($slug, __DIR__ . '/custom_lang')` eingebunden werden.
- Im Code stets `__()` (Frontend) bzw. `__a()` (Admin) mit benannten Platzhaltern `{param}` verwenden.

---

## 7. Datenbank-Lebenszyklus, Schema-Versionierung & 3rd-Party-Standard (Phase 42)

Jedes offizielle und 3rd-Party-Plugin deklariert seine Datenhaltung verbindlich im `storage`-Block der `plugin.json`. `PluginSchemaManager` prüft und initialisiert diese Strukturen automatisch bei **Erstinstallation**, **JSON→MySQL-Migration**, **Plugin-Aktivierung** sowie bei jedem **Plugin-Update**.

### Unterstützte `storage.mode`-Varianten

1. **`plugin_data`** *(empfohlener Standard für neue Plugins)*
   Nutzt `StorageManager::loadPluginData()` / `savePluginData()`. Dadurch erbt das Plugin automatisch MySQL (`{prefix}plugin_data`) oder Flat-File JSON sowie die Sandbox-Isolation.
   ```json
   "storage": {
     "mode": "plugin_data",
     "schema_version": "1.0.0",
     "plugin_data_keys": {
       "mein_plugin_items": "data/mein_plugin_items.json"
     },
     "migrations_dir": "migrations"
   }
   ```
2. **`sql_tables`** *(eigene relationale Tabellen)*
   Für Plugins mit eigenen SQL-Tabellen (MySQL & SQLite). Alle DDL-Skripte müssen `CREATE TABLE IF NOT EXISTS` / idempotente Spaltenprüfungen verwenden.
   ```json
   "storage": {
     "mode": "sql_tables",
     "schema_version": "1.1.0",
     "tables": ["mein_plugin_items"],
     "migrations_dir": "migrations"
   }
   ```
3. **`json_only`** *(reine JSON-Datenhaltung)*
   Deklariert JSON-Dateien (`"json_files": ["data/mein_plugin.json"]`) inklusive `schema_version`-Prüfung und optionale JSON-Migrationen über `migrations_dir`.
4. **`none`** *(tabellenfrei / Core-gebunden)*
   Für Plugins ohne eigenen Tabellenspeicher (nutzen nur Core-Konfiguration oder flüchtige Sessions).

### Dateibasierte Update-Migrationen (`migrations/v1.0.0.php`, `v1.1.0.php`)

Dateien im `migrations_dir` werden in SemVer-Reihenfolge einmalig für Versionen `> installierter Stand` bis `<= schema_version` ausgeführt:

```php
<?php
// plugins/mein-plugin/migrations/v1.0.0.php
return static function (array &$ctx): void {
    if ($ctx['pdo'] instanceof PDO) {
        $pref = $ctx['prefix'];
        $ctx['pdo']->exec("CREATE TABLE IF NOT EXISTS `{$pref}mein_plugin_items` (
            id VARCHAR(64) PRIMARY KEY,
            title VARCHAR(255) NOT NULL
        )");
    }
};
```

### Verbindliche Sicherheitsregeln für alle Plugins

- **Striktes `DROP TABLE`-Verbot beim Deaktivieren (`plugin_deactivated_{slug}`):** Das Deaktivieren eines Plugins darf **niemals** Tabellen droppen, `{prefix}plugin_data`-Zeilen löschen oder JSON-Dateien entfernen. Alle Daten müssen bei Reaktivierung vollständig erhalten bleiben.
- **Idempotenz:** `plugin_ensure_schema_{slug}` und alle Migrationsskripte müssen mehrfach ausführbar sein, ohne bestehende Daten zu überschreiben.
- **Sandbox-Isolation:** Bei `SandboxManager::isDemoActive()` dürfen Produktiv-Tabellen nicht verändert werden.
