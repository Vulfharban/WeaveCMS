# WeaveCMS

> **Lightweight, modular section-based CMS with flat-file & SQL support.**

WeaveCMS is a fast, modern and dependency-free content management system (PHP 8.0+), built for landing pages, craft businesses, portfolio sites and modular web presences.

## Highlights & Core Features

- **Modular sections:** Flexible page layout built from section types (Hero, Gallery, Courses/Events, About me, Free text) with free ordering.
- **Dual storage driver:** Choose between file-based flat-file storage (`data.json`) and a relational database (MySQL / MariaDB / SQLite via PDO).
- **Guided setup wizard:** Painless installation with system health check and optional sample data.
- **Multilingual (i18n):** Primary language selectable at first install, optional secondary languages with a frontend switcher.
- **Role-based multi-user system (RBAC):** Graduated permissions for administrator, moderator and content user.
- **Logo management & responsive engine:** Flexible logo (SVG/image/text) and reliable mobile usage on all devices.
- **Release packaging & update check:** ZIP/7z generator for full and update packages plus automatic remote version comparison.
- **Security hardening:** CSRF protection, XSS prevention, brute-force & rate limiting, strict upload validation and security headers.
- **100 % plain PHP:** No heavy framework dependencies or build tools required on the server.

## Documentation & Roadmap

- **Feature catalog & update portal (Deutsch):** [`libs/docs/features.md`](libs/docs/features.md)
- **Feature catalog (English):** [`libs/docs/features_en.md`](libs/docs/features_en.md)
- **31-phase development roadmap:** [`libs/docs/roadmap.md`](libs/docs/roadmap.md)

## Releases & Downloads (v1.3.1)

- 📦 **[WeaveCMS v1.3.1 Full Package](releases/weavecms-v1.3.1.zip)** (`releases/weavecms-v1.3.1.zip`, 12.15 MB) – Complete installation archive for new websites.
- 🔄 **[WeaveCMS v1.3.1 Update Package](releases/update-v1.3.1.zip)** (`releases/update-v1.3.1.zip`, 0.71 MB) – Core system files for upgrading existing installations.

## Changelog & Recent Updates

### v1.3.1 (28. Oktober 2026)

- 🌐 **Vollständige Backend-Mehrsprachigkeit (DE & EN):**
  - **Umfassende Lokalisierungskataloge:** Lückenlose Übersetzung aller Administrationsbereiche (Header, Login-Screen, Haupt- und Subnavigation, Dashboard-KPIs, Benutzerverwaltung, Schnellzugriffe, Statusmeldungen, Sicherheits-Audit, Backup-System und Bestätigungsdialoge) in `libs/lang/admin/de.json` und `libs/lang/admin/en.json` mit über 1.050 synchronisierten Schlüsseln (100 % Parität aller 1.056 Schlüssel).
  - **Login-Sprachumschalter:** Eleganter Sprachschalter auf der Login-Maske mit automatischer Session- und Cookie-Persistenz.
  - **Intelligente Fallback-Engine:** `LanguageManager::adminT()` und `__a()` mit automatischer Browser-Erkennung, Rollen-Fallback und dynamischer Platzhalter-Ersetzung (`{name}`, `{count}`, `{version}`).
- 👤 **Benutzerdefinierte Default-Sprache im Backend:**
  - **Persönliche Spracheinstellung:** Jeder Benutzer (Administrator, Moderator, Redakteur) kann seine bevorzugte Backend-Sprache (`de` oder `en`) festlegen.
  - **Nahtlose Live-Synchronisation:** Beim Klick auf den Sprachumschalter im Header wird die gewählte Sprache sofort im Benutzerprofil (`admin_lang`) gespeichert und auf aktive Sitzungen und Cookies übertragen.
  - **Benutzerverwaltung mit Sprach-Indikator:** Tabellarische Übersicht der gewählten Sprache inklusive pixelgenauer SVG-Länderflaggen und direkter Sprachauswahl beim Anlegen oder Bearbeiten von Benutzern.
- ⚡ **Custom Action-Hub & Dashboard-Schnellzugriffe (Phase 26):**
  - **Personalisierte Workflow-Kacheln:** Frei konfigurierbare Schnellzugriffe auf dem Dashboard mit Rollenprüfung (RBAC) und dynamischen Zähler-Badges (z. B. ungelesene Nachrichten).
  - **Aktionskatalog & 1-Klick-Presets:** Über 25 kuratierte Aktionen aus den Bereichen Content, Medien, Kommunikation, Design, System und Plugins sowie Vorlagen (*Allrounder*, *Redakteur*, *Administrator*, *Minimal*).
  - **Schwebende Sprach- & Seitenauswahl:** Schwebende Toolbar im Einzelseiten-Editor für bequemes Wechseln zwischen Sprachen und Seiten ohne langes Scrollen.

### v1.3.0 (27. Oktober 2026)

- 🖼️ **Zentrale Medienverwaltung & Asset-Manager (Phase 22):**
  - **Neuer Hauptreiter „Mediathek“:** Schneller Zugriff im Admin-Panel auf alle hochgeladenen Bilder, Grafiken, Vektoren und Dokumente mit dynamischen Kennzahlen für Dateianzahl, Speicherplatzbelegung und Formatanteile.
  - **Umschaltbare Kachel- & Listenansicht:** Interaktive Asset-Cards mit 1-Klick-Kopieren für Web-URLs, Markdown-Tags (`![Alt](url)`) und HTML5 `<picture>`-Code sowie detaillierte Tabellenansicht mit Filterung (Bilder, Dokumente, Vektoren), Live-Suche und Sortierung.
  - **Detail- & Barrierefreiheits-Modal:** Pflege von Alt-Texten und Titeln für verbesserte Zugänglichkeit (Screenreader) und Google-Bildersuche, gespeichert in `data/media_meta.json`.
- ⚡ **Moderne Bild-Pipeline mit WebP & AVIF:**
  - **Automatische Next-Gen-Konvertierung:** Erzeugt beim Upload via PHP `gd` komprimierte WebP- und AVIF-Versionen für maximale Ladezeiten und beste Google PageSpeed-Werte.
  - **Responsive Standardgrößen:** Automatische Generierung von skalierten Varianten (`thumb`: 350px, `medium`: 800px, `large`: 1400px) mit voller Alpha-Transparenzerhaltung.
  - **HTML5 `<picture>`-Helfer:** `MediaManager::renderPictureTag()` für standardkonformes, adaptives Laden mit modernstem Format-Fallback.
  - **Smarte Varianten-Isolierung:** Abgeleitete Formate bleiben als Varianten des Master-Bildes organisiert und werden beim Löschen des Originals rückstandslos mit entfernt.
- 📤 **Drag & Drop Mehrfach-Upload & 7Z-Archivunterstützung:**
  - Komfortabler Multi-Upload mit visuellem Fortschrittsbalken und automatischer Dateinamensbereinigung.
  - **Dateiformat-Strategie:** Unterstützung für `JPG, PNG, WebP, GIF, SVG, PDF, ZIP, 7Z` mit strikter Magic-Bytes-Prüfung (inkl. 7z-Signatur `37 7A BC AF 27 1C`); proprietäre Office-Formate zugunsten von sauberem PDF-Export konsequent ausgeschlossen.
- 🕒 **Content-Revisions & Entwurfsmodus (Phase 23):**
  - **Revisions-Historie & Unified Diff-Viewer:** Bis zu 25 Snapshots je Einzelseite und Blogartikel in `data/revisions/` mit farblich visualisiertem Zeilenvergleich (`+` hinzugefügt, `-` entfernt).
  - **1-Klick-Rollback:** Beliebigen früheren Stand mit einem Klick wiederherstellen inkl. automatischem Sicherheits-Snapshot des aktuellen Stands.
  - **Status-Workflow & Zeitgesteuerte Veröffentlichung:** Unterscheidung zwischen `published`, `draft` und `scheduled` mit sicherem kryptografischem Vorschau-Token (`?preview=...`) und Frontend-Vorschau-Banner.
- 🌐 **Vollständige Mehrsprachigkeit (i18n) im Backend:** Sämtliche neuen Mediathek- und Revisions-Elemente sind für Deutsch und Englisch übersetzt (`libs/lang/admin/`).

### v1.2.5 (27. Oktober 2026)

- 🌙 **Dunkelmodus-Überarbeitung & Kontrastoptimierung:** 
  - **Hintergrund-Abdunkelung:** Farbsystem um ein sattes Kohleschwarz (`--bg-dark: #0f0d0b`, `--bg-card: rgba(22, 19, 17, 0.95)`) ergänzt für ein harmonisches und blendfreies Leseerlebnis.
  - **Barrierefreie Überschriften & Akzente:** Dynamischer Wechsel der primären Akzentfarbe im Dunkelmodus auf leuchtendes Smaragdgrün (`--primary: #4ade80`, 11:1 Kontrastverhältnis) und warmes Pfirsich-Bernstein (`--accent: #f09a5c`), sodass Überschriften (`h1`–`h4`), Icons und Navigationstexte auf dunklem Grund nicht mehr verschwinden oder schwer lesbar sind.
  - **Vollständige Dark-Mode-Unterstützung aller Bereiche:** Optimierte dunkle Farbgebung für Navigationen (`header-nav`, `sidebar-nav`), Menü-Drawers (Mobil-, Kontakt- und Impressum-Slide-in), Hero-Karten, Galerie-Filterbuttons, FAQ-Akkordeons, Formularelemente und Spamschutz-Buttons.
- 🌐 **Intelligenter Sprachumschalter (Inline & Dropdown):**
  - **Kompakte Direktansicht:** Bei bis zu 3 konfigurierten Sprachen (z. B. DE, EN, IT) werden die Sprachen platzsparend nebeneinander als direkte Badges angezeigt.
  - **Automatisches Dropdown bei > 3 Sprachen:** Sobald mehr als 3 Sprachen aktiv sind, transformiert sich die Sprachauswahl automatisch in ein interaktives Dropdown-Menü im Header, in der Sidebar und im Mobilmenü.
  - **Status-Anzeige:** Der Button zeigt stets die aktuell ausgewählte Sprache mit Landesflagge und Sprachcode (z. B. `🇩🇪 DE ▾`); das Aufklappmenü listet alle aktiven Sprachen mit nativer Bezeichnung und visuellem Häkchen für die aktive Auswahl.
  - **Klick-Erkennung & Barrierefreiheit:** Leichtgängiges Auf- und Zuklappen via ARIA-Attributen und automatischem Schließen beim Klick außerhalb.


### v1.2.4 (26. Oktober 2026)

- 🗄️ **Forum SQLite-Storage & Migration:** Die 1-Klick-Migration zu SQLite (`data/forum.sqlite`) in `weave-forum` und `weave-forum-users` initialisiert die Datenbankdatei sowie sämtliche Tabellen (`users`, `categories`, `boards`, `topics`, `posts`, `likes`, `reports`, `subscriptions`, `audit_log`) und Indizes nun auch dann zuverlässig, wenn WeaveCMS im Flat-File JSON-Modus läuft und noch keine Foren-Dateien existierten.
- 🛡️ **Fehler- und Status-Reporting:** Detaillierte visuelle Erfolgs- und Fehlermeldungen bei der Speichermigration (`fuser_err` / `forum_err`), Erkennung der PHP-Erweiterung `pdo_sqlite` und integrierte Informationsbox zur serverlosen Zero-Configuration-Architektur von SQLite.
- 🖥️ **Admin-Navigation & Tab-Initialisierung:** Behebung des `switchTab is not defined` JavaScript-Fehlers bei Neuinstallationen im Flat-File JSON-Modus durch zuverlässige Sprachprüfung (`getCurrentLanguage()`) und optimierte Skript-Reihenfolge im Head.
- 📦 **Automatisierte Release-Synchronisation:** Neues CLI-Tool `tools/set_version.php` zur lückenlosen Synchronisation von Systemversionen (`config.php`, `Installer.php`), Generierung der Release-Pakete (`Full` und `Update`) sowie automatischer Aktualisierung der `README.md`.

### v1.2.3 (26. Oktober 2026)

- 🗄️ **Relationales MySQL/MariaDB-Schema:** Statt eines JSON-Blobs je Schlüssel legt der SQL-Treiber nun eigene Tabellen je Inhaltsbereich an (`gallery`, `courses`, `pages`, `faqs`, `team`, `users` …) mit lesbaren Spalten (`item_id`, `title`, `status`, `sort_pos`), `config` für Einstellungen/Texte/SEO und `lists` für Textlisten. Unbekannte Schlüssel (z. B. Plugins) landen in `documents`. Die Daten bleiben 1:1 kompatibel zum JSON-Modus; Altbestände aus `documents` werden beim nächsten Speichern automatisch überführt.
- 🧩 **Fehlende vs. leere Bereiche:** Bei MySQL wird zwischen nie vorhandenen und bewusst leeren Bereichen unterschieden – identisches Verhalten wie bei JSON.
- 🛠️ **Installer (MySQL):** Konkrete SQL-Fehlermeldungen statt Pauschalfehler, die Option „Datenbank automatisch erzeugen“ wird berücksichtigt, `config.local.php` wird erst nach erfolgreicher Tabellenanlage geschrieben.
- 🌐 **Vollständige Mehrsprachigkeit der Beispieldaten & Frontend-Texte:** Sämtliche mitgelieferten Beispieldaten (`data.sample.json`) für Galerie, Workshops, Blog, FAQ, Team, Preise, Downloads, Sektionen und Rechtstexte liegen nun vollständig übersetzt für Deutsch (`de`), Englisch (`en`) und Italienisch (`it`) vor. Frontend-Controller und Template-Views übersetzen sämtliche UI-Strings, Kategorien, Captchas und Metadaten konsistent ohne unerwünschte Fallbacks auf Deutsch.
- 📊 **Statistik:** `data/stats.json` wird nicht mehr ausgeliefert; neue Installationen starten mit leerer Besucherstatistik.
- 🔐 **PIN-Eingabe:** Keine Beispiel-Vorbelegung mehr im Installer und Admin-Login.

### v1.2.2 (25. Oktober 2026)

- 🛒 **Shop-Erweiterungs-Architektur (Add-on Plugins):** Leistungsfähiges Hook- und Filtersystem für Erweiterungs-Plugins (`shop_payment_gateways`, `shop_process_payment`, `shop_calculate_cart`, `shop_product_data`, `shop_admin_subtabs`, `shop_api_action`, `shop_email_order_data`, etc.). Ermöglicht nahtlose Integration von Dritt-Zahlungsanbietern, Rabatt-/Gutscheinsystemen, individuellen Produktfeldern und eigenen Admin-Tabs.
- 📚 **Shop-Entwicklerdokumentation:** Vollständige Referenz und Quick-Start-Guide in `plugins/shop/README.md` inklusive lauffähigem Beispiel-Plugin für Gutscheincodes.
- 🛡️ **SecurityManager Fix:** Einführung von `SecurityManager::getBaseUrl()` zur zuverlässigen URL-Ermittlung bei System-E-Mails und Webhooks.
- 📦 **Plugin-Versionierung:** Anhebung des Shop-Plugins auf v1.2.0.

### v1.2.1 (25. Oktober 2026)

- 🎨 **Design-Toolbar für Einzelseiten:** Schnellauswahl und 1-Klick-Einfügen von vordefinierten Trennern, Farbboxen und Überschriftarten direkt im Admin-Seiteneditor.
- ✂️ **Optische Trenner (Visual Dividers):** Farbverlauf-Linien (`divider-gradient`), zentrierte Icon-Trennlinien (`divider-icon`), leuchtende Glow-Linien (`divider-glow`), gepunktete und Wellen-Trenner.
- 📦 **Einfärbbare Div-Boxen & Cards:** Sanfte Farbtöne (`box-soft-primary`, `box-soft-success`, `box-soft-amber`, `box-soft-purple`), Dark-/Gradient-Cards und frei einfärbbare Boxen mit Farbwähler.
- 🏷️ **Vordefinierte Überschrift-Stile:** Akzent-Balken links, Farbverlauf-Unterstrich, Eyebrow/Kicker-Badges, Gradient-Schriftzüge und Icon-Headlines.
- 📏 **Responsiver Einzelseiten-Container:** Automatische Skalierung bis zu `var(--container-max-width, 1100px)` für großzügige 2-Spalten-Grids und Projekt-Karten.
- 🛠️ **MarkdownParser Entity-Fix:** Deaktivierung der Doppelkodierung (`double_encode = false`), sodass sowohl `&` als auch `&amp;` fehlerfrei als `&` im Browser angezeigt werden.

### v1.2.0 (25. Oktober 2026)

- 📋 Modernisiertes Aktive/Inaktive Sektionen Kanban-Board im Admin-Dashboard.
- 🛡️ Optionaler Spamschutz (Captcha/Rechenaufgabe) für den Kontakt-Drawer analog zum Impressum.
- 🌐 Optimierter Sprachwechsel ohne Reset auf das Dashboard.

## Installation

1. Download **[weavecms-v1.3.1.zip](releases/weavecms-v1.3.1.zip)** or copy the repository files to your web server (PHP 8.0+).
2. Open `install.php` in your browser and follow the guided setup wizard.

## License

Released under the [GNU General Public License v3.0](LICENSE).