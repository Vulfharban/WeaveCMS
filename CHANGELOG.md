# WeaveCMS Changelog

Alle wichtigen Änderungen, Neuerungen und Sicherheits-Updates von WeaveCMS werden in dieser Datei chronologisch dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/) und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

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

