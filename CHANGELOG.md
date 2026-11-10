# WeaveCMS Changelog

Alle wichtigen Änderungen, Neuerungen und Sicherheits-Updates von WeaveCMS werden in dieser Datei chronologisch dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/) und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

---

## [v1.3.4.1] - 10. November 2026

### 🚀 Autonomes 3rd-Party Plugin-Update-System & CoreUpdater-Fix

- 🧩 **Autonomes 1-Klick-Update-System für 3rd-Party Plugins (`PluginUpdater`):**
  - Jedes Plugin kann unabhängig vom CMS-Kern nach Updates suchen und sich per 1-Klick aktualisieren, ohne das gesamte CMS updaten zu müssen.
  - Deklaration via `"github_repo": "owner/repo"` oder `"update_url": "https://..."` in `plugin.json`.
  - **Sicherheit & Integrität:** HTTPS-Download (Native CA), Zip-Slip-Schutz, automatische Erkennung verschachtelter GitHub-Zipball-Verzeichnisse (`<owner>-<repo>-<sha>/`), optionale SHA-256 Validierung aus dem GitHub-Release-Body (`SHA256: <hash>`) und Core-Kompatibilitätsprüfung via `requires_core` (`PluginManager::isCoreCompatible`).
  - **Pre-Update-Backup & Rollback:** Automatisches ZIP-Backup unter `backups/plugins/plugin_<slug>_<datum>_v<alt>.zip` vor jedem Überschreiben mit sofortigem automatischem Rollback im Fehlerfall.
  - **Admin-UI:** Globaler Button *„Updates prüfen“*, *„Update prüfen“* je Plugin, Update-Banner mit Versionssprung, Changelog-Link und 1-Klick-Installationsbutton.
- 📚 **Umfassende Dokumentation für Plugin-Entwickler:**
  - Ausführlicher Leitfaden in [`libs/docs/PLUGINS.md`](libs/docs/PLUGINS.md) und [`plugins/README.md`](plugins/README.md) zu Manifest-Konfiguration, GitHub Releases, Assets, Prüfsummen und Update-Lifecycle.
- 🧪 **Neue Test-Suite für Plugin-Updates:**
  - Automatisierte Test-Suite [`tests/test_plugin_updater.php`](tests/test_plugin_updater.php) (28 von 28 Tests bestanden) für GitHub-Repo-Normalisierung, Zipball-Erkennung, Backup-Integrität, atomaren Dateiaustausch, Rollback und Core-Kompatibilität.
- 🐛 **CoreUpdater Pfad-Freigabe (`CORE_PATHS`):**
  - `'plugins/'` und `'.htaccess'` wurden in `CoreUpdater::CORE_PATHS` aufgenommen. Bei automatischen 1-Klick-Core-Updates wurden bisher fälschlicherweise alle Dateien im Ordner `plugins/` herausgefiltert, wodurch neue Plugins (wie `weave-games` und `weave-helpdesk`) nicht auf dem Server entpackt wurden.
- 🔄 **Automatische Synchronisation fehlender Plugins (`syncMissingOfficialPlugins`):**
  - Neue Methode `CoreUpdater::syncMissingOfficialPlugins()` stellt fehlende offizielle Plugins direkt aus dem Update-Paket wieder her.
  - Automatische Ausführung in `finishPendingUpdate()` nach jedem Core-Update.
  - Neuer Hinweis-Banner in der Plugin-Verwaltung (`admin/views/tab_plugins.php`) mit 1-Klick-Aktion *„Fehlende Plugins jetzt nachinstallieren“*.
- 🧪 **Test-Suite für Core-Updates:**
  - Automatisierte Test-Suite [`tests/test_core_updater.php`](tests/test_core_updater.php) mit 27 Prüfungen für Pfad-Whitelisting, Zip-Slip-Schutz und Schutz aller Nutzerdaten.

---

## [v1.3.4] - 09. November 2026

### 🎮 Weave Games, Weave Helpdesk, Plugin-Refactoring & Mehrsprachigkeits-Prüfung

- 🎮 **Neues Plugin: Weave Games (`weave-games`):**
  - **Online-Registrierung & Spieler-Accounts:** Webportal (`?page=games`) und REST-API (`api/games.php`) zur Registrierung und Anmeldung von Spielern mit kryptografischen Client-Authentifizierungs-Tokens (`auth_token`).
  - **Client-Update-Rollout & Patch-Verteilung:** Automatische Versionsprüfung für Spiel-Clients (Desktop Launcher & Binaries), SHA256-Prüfsummen-Verifikation, Pflicht-Updates (`mandatory = true`) und Plattform-Unterstützung (Windows x64, Linux x64, macOS, WebGL/Browser).
  - **Server-Verwaltung & Live-Cluster-Monitor:** Starten, Stoppen und Neustarten von Spieleservern lokal (Windows Task / Linux Daemon) oder remote via SSH (Passwort- und Key-Authentifizierung).
  - **Parameterübergabe:** Flexible Übergabe von Startargumenten für Windows und Linux (Ports, Welten, max. Spieler, Tickrate).
  - **Multi-Game-Support:** Konzipiert für Server/Client-Spiele (wie Voxel- und Multiplayer-RPGs), Dedicated Game-Servers sowie direkte Browsergames (inkl. nativ spielbarem Space Orbit HTML5 Canvas Mini-Game).

- 🛡️ **Neues Plugin: Weave Helpdesk (`weave-helpdesk`):**
  - **Autonome Web-App & Portal (`?page=games`):** Vollständiges Service-Desk- und Ticketsystem im responsiven Petrol/Teal-Design mit Rücksprungmöglichkeit zur Hauptwebsite.
  - **Ticket- und Aufgabenmanagement:** Formatierte Ticketnummern (`YY` + 6 Ziffern), Status-Workflow und Tätigkeitserfassung mit Minutenprotokoll.
  - **Wartungsverträge & Inklusivminuten:** Automatische Verrechnung von Arbeitszeiten gegen vertragliche Inklusivminuten (`Verfügbar - Verbraucht = Differenz`).
  - **Buchhaltungssperre:** Der Status *„Abgerechnet“* sperrt Tickets revisionssicher gegen erneutes Öffnen oder Bearbeiten.
  - **Menüsteuerung & Mobile UI:** Im Backend frei wählbare Menüsichtbarkeit und optimierte Ansichten für Smartphones und Tablets.

- 🛍️ **Plugin-Refactoring & Umbenennung:**
  - `shop` umbenannt in **Weave Shop** (`weave-shop`).
  - `inventory` umbenannt in **Weave Inventory** (`weave-inventory`).
  - Vollständige Aktualisierung aller Hooks, REST-APIs und Konfigurationsschlüssel unter Beibehaltung nahtloser Abwärtskompatibilität.
  - Neue automatisierte Test-Suite [`tests/test_shop.php`](tests/test_shop.php) prüft Katalog, Warenkorb mit 19 % MwSt., steuerfreie Kleinunternehmer (§ 19 UStG), Versandkostenfreigrenzen und Zahlungs-Gateways (Kreditkarte mit Luhn-Prüfung, PayPal, WERO, Vorkasse).

- 🌐 **Mehrsprachigkeits-Audit (Frontend & Backend i18n):**
  - Vollständige Prüfung aller 12 europäischen Sprachkataloge (DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI) auf JSON-Validität und strikte UTF-8-Codierung (ohne BOM).
  - Sämtliche Navigations- und Interaktions-Schlüssel für alle neuen und refaktorierten Plugins (`nav.shop`, `nav.helpdesk`, `nav.games`, `btn.play_now`, `btn.download_client`) in allen 12 Sprachen nachgepflegt.

- 📚 **Dedizierte Plugin-Dokumentation:**
  - Neues Handbuch [`docs/PLUGINS.md`](docs/PLUGINS.md) mit detaillierter Übersicht über alle WeaveCMS-Plugins, Kernfeatures, REST-APIs, Parameter und Hook-Events.

---

## [v1.3.3.1] - 03. November 2026

### 🛡️ Sicherheits-Härtung, Sandbox-Isolation & Fehlerbehebungen

- 🌿 **Sandbox-Modus & Session-Isolation:**
  - **Reale Admin-Session-Sicherung:** Bei Beginn einer Sandbox-Sitzung (`?demo=start` / `?demo=1`) wird eine bestehende Administrator-Sitzung in `$_SESSION['weave_real_admin_backup']` gesichert. Beim Pausieren oder Beenden der Demo wird der reale Admin-Login nahtlos und ohne Datenverlust wiederhergestellt.
  - **Modus-Umschaltung & Pausieren:** Neuer Modus „Normaler Bereich“ (`?demo=pause` / `?demo=0`) erlaubt das Inspizieren der echten Website und des regulären Administrationsbereichs, ohne die temporäre Demo-Sitzung verwerfen zu müssen.
  - **Zustands-Banner:** Elegantes Top-Banner im Frontend und Backend zeigt im Normalmodus bei pausierter Demo Optionen für *„Demo fortsetzen“* (`?demo=resume`) und *„Demo verwerfen“* (`?demo=stop`).
  - **Vorausgefüllter & Gesperrter Demo-Login:** Im Anmeldedialog (`admin/views/login.php`) sind Benutzername (`admin`), Passwort (`demo-weave-2026`) und PIN (`0000`) schreibgeschützt vorausgefüllt (`readonly`). 2FA-Eingaben werden im Demo-Modus automatisch übersprungen.
  - **Anonymisiertes Kontaktpostfach (Nachrichten):** Reale Kundenanfragen und persönliche E-Mail-Adressen in `data/contacts.json` werden im Demo-Modus vollständig verborgen. Es wird strikt genau eine einzige anonyme Testnachricht mit der Absenderadresse `testmail@test.de` angezeigt, selbst wenn mehrere reale Nachrichten im Postfach existieren. Statusänderungen und Löschungen bleiben rein flüchtig in der Session.
  - **Vollständige Anonymisierung personenbezogener Daten (Impressum, Kontakt & Kurse):** Der `EphemeralSessionDriver` erzwingt im Sandbox-Modus zwingend neutralisierte Musterdaten (*Max Mustermann*, *Musterstraße 12, 12345 Musterstadt*, E-Mail `testmail@test.de`, Telefon `+49 123 4567890`) für Impressum, Kontaktbereich, Begrüßungen, System-Platzhalter und Kursorte. Sämtliche SMTP-Zugangsdaten und Sicherheits-Alert-E-Mails werden im Demo-Modus sicher neutralisiert.
  - **Vollständiges Zurücksetzen:** Beim Beenden der Demo werden nicht nur flüchtige JSON-Daten (`data/sandbox/*.json`), sondern auch sämtliche während der Sitzung hochgeladenen Originalbilder und abgeleiteten WebP/AVIF-Varianten (`_thumb`, `_medium`, `_large`) restlos vom Dateisystem entfernt.

- 🔒 **Sicherheits-Audit & Sandbox-Breakout-Prävention:**
  - **Backup & Restore gesperrt:** Wiederherstellung von ZIP-Backups (`restore_backup`, `upload_restore`), Erstellung und Löschung von Backups sowie Release-Packaging sind im Demo-Modus hart blockiert.
  - **Core-Updates & Migrationen gesperrt:** `CoreUpdater::run()` und Datenbank-Migrationen (SQLite, MySQL, PostgreSQL) können im Demo-Modus nicht ausgeführt werden.
  - **Schutz echter Produktivmedien:** `MediaManager::delete()` verweigert das Löschen von Produktivdateien im Demo-Modus; nur innerhalb der aktuellen Demo-Sitzung hochgeladene Dateien dürfen entfernt werden.
  - **E-Mail-Schutz:** Kontaktformular (`send_mail.php`) und SMTP-Tests simulieren den Erfolg im Demo-Modus, ohne echte E-Mails über das Netzwerk zu versenden.

- 🌐 **Behebung des Sprachkatalog-Bugs (UTF-8 BOM Hotfix):**
  - **BOM-Bereinigung:** Unsichtbare UTF-8 Byte Order Marks (`\xEF\xBB\xBF`) aus `libs/lang/admin/de.json` und `libs/lang/admin/en.json` entfernt, die zuvor `json_decode()` fehlschlagen ließen und dazu führten, dass im Dashboard nur Rohschlüssel angezeigt wurden.
  - **PHP-Header-Warnungen behoben:** BOMs aus `libs/php/AuthManager.php` und `admin/views/login.php` entfernt, die zu vorzeitigen HTTP-Headern führten.
  - **LanguageManager gehärtet:** `LanguageManager::loadTranslationFile()` und `loadAdminTranslationFile()` filtern BOM-Sequenzen am Dateianfang nun automatisch und transparent heraus. Alle 1.248 Übersetzungsschlüssel laden wieder zu 100 %.

---

## [v1.3.3] - 31. Oktober 2026

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

---

## [v1.3.2] - 29. Oktober 2026

- 🏷️ **Globale Taxonomien & Modulübergreifendes Tagging (Phase 28.1):**
  - **Zentrale Schlagwort-Engine (`TaxonomyManager`):** Aggregiert und normalisiert Tags modulübergreifend aus Blogartikeln, Einzelseiten, Kursen, Galerie-Einträgen und Downloads mit konsistentem Slugify-Verfahren.
  - **Responsive Tag-Archivseiten im Frontend (`?tag={slug}`):** Strukturierte Archivansicht mit Trefferzähler, Modul-Kategorisierung, Schnellfilter-Cloud und dynamischer Lokalisierung.
  - **Interaktives Tagging im Adminbereich:** HTML5 Datalists und Klick-Badges in Blog- und Einzelseiten-Formularen für Vorschläge und Autovervollständigung.
- ⚙️ **Custom Fields Manager (Phase 28.2 & 28.3):**
  - **Frei definierbare Zusatzfelder:** Konfiguration individueller Datenfelder für Seiten, Blog, Kurse und Downloads direkt im Administrationsbereich unter *Darstellung & Design*.
  - **7 flexible Datentypen:** `text`, `textarea`, `number`, `boolean`, `url`, `date`, `file`.
- 📐 **Visueller Spalten- & Grid-Builder (Phase 27):**
  - Grid-Shortcodes (`[grid]` / `[col]`), Preset-Vorlagen & Shortcode-Boxen sowie Responsive Breakpoint-Vorschau.

---

## [v1.3.1] - 28. Oktober 2026

- 🌐 **Vollständige Backend-Mehrsprachigkeit (DE & EN):**
  - Lückenlose Lokalisierungskataloge mit über 1.050 synchronisierten Schlüsseln, Login-Sprachumschalter und intelligenter Fallback-Engine.
  - Benutzerdefinierte Default-Sprache im Backend (`admin_lang`).
- ⚡ **Custom Action-Hub & Dashboard-Schnellzugriffe (Phase 26):**
  - Frei konfigurierbare Schnellzugriffe mit Rollenprüfung (RBAC) und Zähler-Badges.

---

## [v1.3.0] - 27. Oktober 2026

- 🖼️ **Zentrale Medienverwaltung & Asset-Manager (Phase 22):**
  - Neuer Hauptreiter „Mediathek“, Kachel- und Listenansicht, Detail- und Barrierefreiheits-Modal.
- ⚡ **Moderne Bild-Pipeline mit WebP & AVIF:**
  - Automatische Next-Gen-Konvertierung, responsive Standardgrößen und HTML5 `<picture>`-Helfer.

