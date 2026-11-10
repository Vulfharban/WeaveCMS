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
