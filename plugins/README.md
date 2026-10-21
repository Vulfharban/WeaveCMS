# WeaveCMS – Plugin- & Modulsystem

Willkommen im Plugin-Verzeichnis von WeaveCMS! Hier werden alle Erweiterungen, Custom-Module und Integrationen für WeaveCMS abgelegt.

---

## 🏛️ Architektur-Konzept & Philosophie

Das WeaveCMS Plugin-System folgt denselben Kernwerten wie das CMS selbst:
* **Zero-Bloat:** Keine schweren Composer- oder Node.js-Abhängigkeiten – native, saubere PHP-Module.
* **Isolation & Stabilität:** Jedes Plugin liegt in seinem eigenen isolierten Unterordner (`plugins/<plugin-slug>/`).
* **Sicherheit First:** Direkte Web-Aufrufe sensibler Dateien sind per `.htaccess` unterbunden.
* **1-Klick-Verwaltung:** Plugins können im WeaveCMS-Adminbereich übersichtlich eingesehen, aktiviert und deaktiviert werden.

---

## 📁 Ordner- & Dateistruktur eines Plugins

Jedes Plugin besitzt ein eindeutiges Verzeichnis mit seinem Slug (z. B. `webtrees-genealogy`):

```text
plugins/
└── mein-modul/
    ├── plugin.json        # Pflicht: Metadaten-Manifest des Plugins
    ├── plugin.php         # Pflicht: Haupteinstiegspunkt & Registrierung
    ├── admin/             # Optional: Eigene Backend-Views, Einstellungs-Tabs oder Formulare
    │   └── settings.php
    ├── views/             # Optional: Frontend-Partials, Sektions-Templates oder Shortcodes
    │   └── section.php
    └── assets/            # Optional: Eigene CSS-Stylesheets, Icons oder JS-Skripte
        ├── style.css
        └── script.js
```

---

## 📋 Das Manifest: `plugin.json`

Jedes Plugin muss im Wurzelverzeichnis eine gültige `plugin.json` bereitstellen:

```json
{
  "name": "Webtrees Stammbaum-Integration",
  "slug": "webtrees-genealogy",
  "version": "1.0.0",
  "author": "WeaveCMS Team",
  "description": "Bindet interaktive Stammbäume, Ahnentafeln und genealogische Personenübersichten aus Webtrees nahtlos in WeaveCMS ein.",
  "category": "integration",
  "entry_file": "plugin.php",
  "requires_core": ">=0.9.0",
  "settings_tab": "webtrees",
  "enabled": true
}
```

### Manifest-Felder im Detail:
| Feld | Typ | Beschreibung |
| :--- | :--- | :--- |
| `name` | String | Lesbarer Anzeigename des Moduls im Adminbereich |
| `slug` | String | Eindeutige Kennung (Kleinbuchstaben, Ziffern, Bindestriche) |
| `version` | String | Semantische Versionsnummer (z. B. `1.0.0`) |
| `author` | String | Entwickler oder Organisation |
| `description`| String | Kurzbeschreibung der Funktionsweise |
| `category` | String | `theme` (Design/Layout), `content` (Inhalte/Sektionen), `auth` (Rechte/Rollen) oder `integration` (Drittsysteme) |
| `entry_file` | String | PHP-Startdatei relativ zum Plugin-Ordner (Standard: `plugin.php`) |
| `requires_core`| String | Mindestversion von WeaveCMS (z. B. `>=0.9.0`) |
| `settings_tab`| String | Optionaler Bezeichner für einen eigenen Admin-Reiter |
| `enabled` | Bool | Standard-Aktivierungsstatus |

---

## 🚀 Schritt-für-Schritt-Anleitung: Eigenes Modul einbinden

### Schritt 1: Ordner anlegen
Erstelle im Verzeichnis `plugins/` einen neuen Ordner mit dem gewünschten Slug:
```bash
plugins/mein-modul/
```

### Schritt 2: Manifest `plugin.json` anlegen
Erstelle die Datei `plugins/mein-modul/plugin.json` nach dem obigen Schema.

### Schritt 3: Einstiegsdatei `plugin.php` erstellen
In `plugin.php` definierst du die Logik und klinkst dich in die WeaveCMS-Hooks ein:

```php
<?php
/**
 * Plugin Name: Mein Erstes WeaveCMS Modul
 * Plugin Slug: mein-modul
 */

// Direkten Aufruf verhindern
if (!defined('WEAVE_VERSION') && !defined('WEAVECMS_ROOT')) {
    exit;
}

// 1. Aktion beim Initialisieren registrieren
PluginManager::addAction('init', function() {
    // Initialisierungs-Logik
});

// 2. Eigene Frontend-Sektion für den Sektionen-Manager bereitstellen
PluginManager::addFilter('register_sections', function(array $sections) {
    $sections[] = [
        'id' => 'mein_modul_section',
        'type' => 'custom_module',
        'title' => 'Mein Modul-Bereich',
        'nav_label' => 'Modul',
        'visible' => true,
        'in_nav' => true,
        'render_callback' => function($vm) {
            echo '<div class="custom-module-box">Inhalt meines Moduls</div>';
        }
    ];
    return $sections;
});
```

---

## 🔌 Typische Einsatzbereiche für Plugins

1. **Themen & Layout-Erweiterungen:**
   * Bereitstellung alternativer CSS-Farbpaletten, Typografien und Header-Varianten.
2. **Contentseiten & Modulare Sektionen:**
   * Individuelle Inhaltsbausteine wie Kalender, Rechner, Buchungstools oder interaktive Karten.
3. **Berechtigungs- & Benutzererweiterungen:**
   * Eigene Rollen (z. B. Moderator, Redakteur, Vereinsmitglied) und individuelle Zugriffsregeln für das RBAC-System.
4. **Drittsystem-Integrationen & Datenbrücken:**
   * Anbindung von externen Datenbanken, Cloud-Diensten oder Fachanwendungen.

---

## 🌳 Geplantes Pilot-Plugin: Webtrees Stammbäume (`webtrees-genealogy`)

Als erstes offizielles Pilot-Plugin wird die **Webtrees Stammbaum-Integration** entwickelt:
* **Zweck:** Nahtlose Einbindung von Stammbäumen und Ahnenforschung aus einer bestehenden Webtrees-Installation.
* **Funktionen:**
  * Direkte Schnittstelle zur Webtrees-Datenbank (MySQL/MariaDB) oder GEDCOM-Exporten.
  * Interaktive Darstellung von Vorfahrentafeln, Nachkommenbäumen und Familienübersichten im WeaveCMS-Frontend.
  * Automatische DSGVO-konforme Ausblendung / Anonymisierung von Daten lebender Personen.
  * Komfortable Einstellungsmaske im WeaveCMS-Adminpanel zur Auswahl von Stammbäumen und Sichtbarkeitsfiltern.
