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
- **Two-Factor Authentication (2FA TOTP):** Native RFC 6238 TOTP authentication with offline SVG QR code generator and one-time emergency backup codes.
- **Media Focal Point Selector:** Interactive 2D visual focal point selector with persistent `object-position` CSS handling across responsive crops.
- **Headless REST API & Webhooks:** Bearer token authentication and HMAC-SHA256 signed event webhook dispatcher for automated integrations.
- **Security hardening:** CSRF protection, XSS prevention, brute-force & rate limiting, strict upload validation and security headers.
- **100 % plain PHP:** No heavy framework dependencies or build tools required on the server.

## Documentation & Roadmap

- **Feature catalog & update portal (Deutsch):** [`libs/docs/features.md`](libs/docs/features.md)
- **Feature catalog (English):** [`libs/docs/features_en.md`](libs/docs/features_en.md)
- **Plugin-Dokumentation & Referenz:** [`docs/PLUGINS.md`](docs/PLUGINS.md)
- **31-phase development roadmap:** [`libs/docs/roadmap.md`](libs/docs/roadmap.md)
- **Complete Changelog:** [`CHANGELOG.md`](CHANGELOG.md)

## Releases & Downloads (v1.3.4)

- 📦 **[WeaveCMS v1.3.3.1 Full Package](releases/weavecms-v1.3.3.1.zip)** (`releases/weavecms-v1.3.3.1.zip`) – Complete installation archive for new websites.
- 🔄 **[WeaveCMS v1.3.3.1 Update Package](releases/update-v1.3.3.1.zip)** (`releases/update-v1.3.3.1.zip`) – Core system files for upgrading existing installations.

## Changelog & Recent Updates

### v1.3.4 (9. November 2026)

- 🎮 **Weave Games Plugin (`plugins/weave-games/`):**
  - **Online-Spielerverwaltung & Authentifizierung:** Registrierung, Login, Rollen (`player`, `moderator`, `admin`), Whitelisting und API-Token-Generierung (`gtkn_...`).
  - **Client-Update-Rollout & Patch-Verteilung:** Release-Kanäle (`stable`, `beta`, `nightly`), Plattform-Filter (Windows x64, Linux x64, macOS ARM/Intel), semantische Versionsprüfung (`version_compare`), SHA256-Prüfsummen und Pflichtupdate-Flags.
  - **Server-Management & Remote-Steuerung:** Dedizierte Serverinstanzen für Fastadun (Rust Voxel Game) und externe Spiele, Live Socket-Ping mit Latenzanzeige (ms), lokale Prozesssteuerung (`proc_open`) sowie Remote-Execution via SSH (`ssh2_exec` / CLI SSH).
  - **Frontend-Portal & Web-Gaming:** Reaktives Dark Gaming Portal (`?page=games`), Fastadun Client-Download, Live-Servermonitor und direkt spielbares HTML5/WebGL Canvas-Arcade-Game ("Space Orbit").
  - **Headless REST API (`/api/games.php`):** Endpunkte für Versionsprüfung (`check_update`), Manifest (`get_manifest`), Auth (`login`, `register`, `verify_token`) und Server-Status (`server_status`).
- 🎧 **Weave Helpdesk Plugin (`plugins/weave-helpdesk/`):**
  - Vollwertiges Ticket- und Supportsystem mit Mitarbeiter- und Kundenrollen, SLA/Inklusivminuten-Vertragsauswertung, Zeiterfassung pro Tätigkeit und Abrechnungsstatus.
  - E-Mail-Benachrichtigungen bei Ticketerstellung und Statuswechsel, mobile-optimiertes Frontend (`?page=helpdesk`) und anpassbare Pflichtfelder.
- 📚 **Dedizierte Plugin-Dokumentation (`docs/PLUGINS.md`):**
  - Vollständige Feature-Übersicht aller 6 WeaveCMS-Plugins: *Weave Games*, *Weave Shop*, *Weave Inventory*, *Weave Helpdesk*, *Webtrees Genealogy*, *Universal Integrations*.
- 🌐 **Mehrsprachigkeits-Audit (i18n):**
  - Vollständige Prüfung aller 12 Sprachen (`de`, `en`, `da`, `el`, `es`, `fi`, `fr`, `it`, `nl`, `no`, `pt`, `sv`) auf BOM-Freiheit und Ergänzung fehlender Navigations- und Button-Schlüssel.

- 🛡️ **Sandbox-Modus & Session-Isolation:**
  - **Reale Admin-Session-Sicherung:** Nahtlose Sicherung bestehender Administrator-Sitzungen (`weave_real_admin_backup`) beim Betreten der Sandbox und automatische Wiederherstellung beim Verlassen oder Pausieren.
  - **Modus-Umschaltung:** Neuer Modus „Normaler Bereich“ (`?demo=pause` / `?demo=0`) zum Inspizieren der Produktiv-Website ohne Löschung der aktiven Demo-Sitzung.
  - **Status-Banner:** Elegantes Top-Banner im Frontend und Backend mit Optionen für *„Demo fortsetzen“* (`?demo=resume`) und *„Demo verwerfen“* (`?demo=stop`).
  - **Vorausgefüllter & Gesperrter Demo-Login:** Im Login (`admin/views/login.php`) sind Benutzername (`admin`), Passwort (`demo-weave-2026`) und PIN (`0000`) schreibgeschützt vorausgefüllt; 2FA wird im Demo-Modus automatisch übersprungen.
  - **Anonymisiertes Kontaktpostfach (Nachrichten):** Vollständiges Verbergen realer Kundenanfragen und persönlicher E-Mail-Adressen im Demo-Modus. Es wird strikt genau eine einzige anonyme Testnachricht mit `testmail@test.de` angezeigt; Statusänderungen und Löschungen bleiben rein flüchtig in der Sitzung.
  - **Vollständige Dateisystem-Bereinigung:** Physisches Löschen temporärer JSON-Dateien und aller in der Demo hochgeladenen Originalbilder inklusive abgeleiteter WebP/AVIF-Varianten (`_thumb`, `_medium`, `_large`).
- 🔒 **Sicherheits-Audit & Sandbox-Breakout-Prävention:**
  - **Kritische Backend-Aktionen gesperrt:** Backup-Restore (`restore_backup`, `upload_restore`), Backup-Erstellung/Löschung, Core-Updates (`CoreUpdater::run()`) und Datenbank-Migrationen sind im Sandbox-Modus hart blockiert.
  - **Schutz echter Produktivmedien:** Löschung in der Mediathek (`MediaManager::delete()`) auf Dateien der aktuellen Demo-Sitzung beschränkt; reale Medien sind geschützt.
  - **E-Mail-Schutz:** Kontaktformular (`send_mail.php`) und SMTP-Tests simulieren Erfolg ohne echten E-Mail-Versand.
- 🌐 **Behebung des Sprachkatalog-Bugs (UTF-8 BOM Hotfix):**
  - **BOM-Bereinigung:** UTF-8 BOM aus `libs/lang/admin/de.json`, `en.json`, `AuthManager.php` und `login.php` entfernt.
  - **LanguageManager gehärtet:** Automatisches Ausfiltern von BOMs; alle 1.248 Übersetzungsschlüssel im Admin-Dashboard laden wieder fehlerfrei.

### v1.3.3 (31. Oktober 2026)

- 🔐 **Zwei-Faktor-Authentifizierung & Backup-Codes (Phase 29):**
  - **Natives RFC 6238 TOTP:** Vollständig integrierte 2-Faktor-Authentifizierung (`TotpManager`) für Administratoren und Redakteure ohne externe Cloud-Abhängigkeiten.
  - **Autarker SVG-QR-Code-Generator:** Direkte Generierung des Einrichtungs-QR-Codes im Browser für Authenticator-Apps (Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden, Aegis) – 100 % datenschutzkonform ohne externe Google-Chart-APIs.
  - **Notfall-Backup-Codes:** Automatische Generierung von 8 kryptografisch sicheren Einmal-Codes (SHA-256 gehasht) mit 1-Klick-Download (`.txt`) und Zwischenablage-Kopieren für Notfall-Logins.
  - **2FA-Status & Verwaltung:** 2FA-Einrichtung direkt im eigenen Benutzerprofil (`tab_users.php`), visuelle Status-Badges in der Benutzerverwaltung und interaktiver 2FA-Bestätigungs-Screen beim Login (`login.php`).
- 🎯 **Visueller Medien-Fokuspunkt-Wähler (Phase 30):**
  - **Interaktiver 2D-Fokuspunkt-Editor (`MediaManager`):** Visuelles Fadenkreuz zur pixelgenauen Festlegung des Bildfokus (`focus_x`, `focus_y` von 0–100 %) direkt im Mediathek-Detaildialog (`tab_media.php`).
  - **Automatisches Responsive-Templating:** Intelligente Berechnung responsiver CSS-Regeln (`object-position: X% Y%`) via `MediaManager::getFocusPosition()` und `MediaManager::getFocusStyle()`.
  - **Nahtlose Template-Integration:** Automatische Ausrichtung in Galerie (`gallery.php`), Blog (`blog.php`), Team-Karten (`team.php`) und Sektions-Teasern verhindert ungewollten Beschnitt wichtiger Bildbereiche.
- 💬 **Foren-Erweiterung, Gast-Datenschutz & Registrierung (`weave-forum-users` & `weave-forum`):**
  - **Double-Opt-In E-Mail-Bestätigung:** Neue Mitglieder erhalten nach der Registrierung eine E-Mail mit kryptografischem Aktivierungs-Link; das Konto wird erst nach erfolgreicher Token-Verifizierung für den Login freigeschaltet.
  - **Rechtsverbindliche Richtlinien- & DSGVO-Zustimmung:** Pflicht-Zustimmung zu anpassbaren Foren-Richtlinien (im responsiven Modal-Dialog) sowie Datenschutz und DSGVO (Art. 6 Abs. 1 lit. a) bei der Kontoerstellung.
  - **Alias-System & Profil-Privatsphäre:** Öffentlicher Alias für Administratoren und Moderatoren; granulare Datenschutzeinstellungen für Mitglieder (Signatur, Beitragszähler, Registrierungsdatum, Online-Status).
  - **Strikter Gast-Schutz:** Automatische Maskierung persönlicher Profildaten und Benutzernamen für nicht angemeldete Besucher im gesamten Forum.
  - **Vollbreiter Themen-Editor:** Kunena-inspiriertes Formularlayout mit integrierter Markdown-Symbolleiste und Live-Vorschau.
- 📦 **Download-Manager, MySQL-Test & System-Härtung:**
  - **Mediathek-Auswahl in Downloads:** Vorhandene Dateien direkt aus der Mediathek für Software- und Dokumenten-Downloads auswählen mit automatischer Größen- und Formatermittlung.
  - **Mehrsprachige Shortcodes (`[downloads]`):** Zuverlässige Sprachfilterung für übersetzte Download-Titel und Beschreibungen.
  - **Live-MySQL-Verbindungstest:** Datenbankkonfigurations-Maske mit AJAX-Verbindungstest und automatischer Speicherung in `config.local.php`.

### v1.3.2 (29. Oktober 2026)

- 🏷️ **Globale Taxonomien & Modulübergreifendes Tagging (Phase 28.1):**
  - **Zentrale Schlagwort-Engine (`TaxonomyManager`):** Aggregiert und normalisiert Tags modulübergreifend aus Blogartikeln, Einzelseiten, Kursen, Galerie-Einträgen und Downloads mit konsistentem Slugify-Verfahren (`ä`->`ae`, `ö`->`oe`, `ü`->`ue`, `ß`->`ss`).
  - **Responsive Tag-Archivseiten im Frontend (`?tag={slug}`):** Strukturierte Archivansicht mit Trefferzähler, Modul-Kategorisierung (Blog, Einzelseiten, Kurse, Galerie, Downloads), Schnellfilter-Cloud und dynamischer Lokalisierung via `LanguageManager::t()`.
  - **Interaktives Tagging im Adminbereich:** HTML5 Datalists und Klick-Badges in Blog- und Einzelseiten-Formularen für blitzschnelle Schlagwort-Vorschläge und Autovervollständigung.
- ⚙️ **Custom Fields Manager (Phase 28.2 & 28.3):**
  - **Frei definierbare Zusatzfelder:** Konfiguration individueller Datenfelder für Seiten (`pages`), Blog (`blog`), Kurse (`courses`) und Downloads (`downloads`) direkt im Administrationsbereich unter *Darstellung & Design* (`tab_settings.php`).
  - **7 flexible Datentypen:** Einzeiliger Text (`text`), Mehrzeiliger Text (`textarea`), Zahl (`number`), Schalter/Checkbox (`boolean`), Webadresse (`url`), Datum (`date`) und Datei/Pfad (`file`) mit Pflichtfeld-Prüfung (`required`), Platzhaltern und Hilfetexten.
  - **Typsichere Validierung & Speicherung:** Automatische Validierung über `CustomFieldManager::extractAndValidate()` beim Speichern von Inhalten.
  - **Dynamisches Frontend-Templating:** Globale Helfer `get_custom_field($item, $key, $default)` und `render_custom_fields($item, $module)` für automatische Attributlisten sowie Anzeige klickbarer Tag-Badges auf Blog- und Einzelseiten.
- 📐 **Visueller Spalten- & Grid-Builder (Phase 27):**
  - **Grid-Shortcodes (`[grid]` / `[col]`):** Modulare Mehrspaltigkeit (50/50, 3-Spalten, 1/3-2/3, 2/3-1/3) im Einzelseiten-Editor auf Basis reiner, schlanker CSS-Grids ohne externe Frameworks mit automatischem Mobile-Stacking und Verschachtelungsschutz.
  - **Preset-Vorlagen & Shortcode-Boxen:** Hero-Teaser, Testimonial-Card, Call-to-Action und moderne `[box]`-Shortcodes.
  - **Responsive Breakpoint-Vorschau:** Interaktives Modal mit `<iframe>` zur Live-Vorschau in Desktop- (1200px), Tablet- (768px) und Smartphone-Ansicht (375px).
- 🌐 **Vollständige Mehrsprachigkeit (i18n):**
  - Sämtliche neuen Komponenten, Dialoge und Fehlermeldungen sind lückenlos in Deutsch und Englisch synchronisiert (`libs/lang/admin/de.json`, `libs/lang/admin/en.json`, `libs/lang/de.json`, `libs/lang/en.json`).


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

1. Download **[weavecms-v1.3.4.zip](releases/weavecms-v1.3.4.zip)** or copy the repository files to your web server (PHP 8.0+).
2. Open `install.php` in your browser and follow the guided setup wizard.

## License

Released under the [GNU General Public License v3.0](LICENSE).
