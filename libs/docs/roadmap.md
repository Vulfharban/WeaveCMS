# WeaveCMS – Produkt- & Entwicklungs-Roadmap

> **Leichtgewichtiges, modulares Sektions-CMS mit Flat-File- (JSON) & SQL-Unterstützung.**

---

## 🏛️ Produkt-Philosophie & Architektur

WeaveCMS verbindet die unkomplizierte Leichtigkeit eines Flat-File-Systems mit der Skalierbarkeit und Struktur moderner Content-Management-Systeme. Es ist speziell konzipiert für Handwerksbetriebe, Dienstleister, Portfolio-Seiten und modulare Landingpages.

### Die 5 Grundpfeiler:
1. **Zero-Bloat & Speed:** 100 % Standard-PHP (8.0+) – Läuft out-of-the-box auf jedem Standard-Webspace ohne Node.js, Composer-Zwang oder schwere Enterprise-Frameworks.
2. **Modulare Sektionen:** Websites werden wie ein flexibles Gewebe (*Weave*) aus wiederverwendbaren Sektionen (Hero, Portfolio/Galerie, Events/Termine, Über uns/Profil, Freitext) aufgebaut.
3. **Dualer Speicher-Treiber (Hybrid):** Vollständige Wahlfreiheit zwischen einer unkomplizierten lokalen JSON-Datei (`data.json`) und relationalen SQL-Datenbanken (MySQL / MariaDB / SQLite).
4. **Schmerzfreie Installation:** Ein geführter 1-Klick-Setup-Wizard prüft die Serverumgebung und initialisiert das CMS binnen weniger Sekunden.
5. **Security by Design & Mehrbenutzer:** Gehärtet gegen gängige Web-Schwachstellen (OWASP Top 10) mit integriertem, rollenbasiertem Mehrbenutzersystem (RBAC).

---

## 🎨 Bereits integrierte Basis-Funktionen (Stand v0.1.0-alpha)

* **Dynamische Design-Engine:**
  * Vollständig tokenbasiertes Design über CSS-Variablen (`:root`).
  * Kuratierte Theme-Paletten (z. B. Weidenglück, Sand & Bernstein, Nordisch Klar, Warmes Atelier) plus freier Farbwähler.
  * Einstellbare Seitenbreiten (Kompakt 1050 px, Standard 1200 px, Großzügig 1400 px), Typografie-Skalierung und Kachel-Radien.
* **Responsive Frontend & Modernes Backend:**
  * Schnelle, responsive Benutzeroberfläche mit Desktop-Navigation und mobilen Slide-In-Menüs.
  * Übersichtliches Administrations-Backend mit Live-Vorschau und strukturierten Einstellungs-Tabs.
* **Hybrid-Kontaktformular:**
  * Unterstützt sowohl authentifizierten SMTP-Versand via PHPMailer als auch direkten Fallback auf den Mailto-Client des Nutzers.
  * DSGVO-konformer, barrierefreier Symbol-Spamschutz (Captcha ohne externe Tracker).

---

## 🚀 Arbeitsablauf: Der 16-Phasen-Entwicklungsplan

```
[Phase 1: Recht & Texte] ──▶ [Phase 2: Datensicherheit] ──▶ [Phase 3: SEO & Sharing] ──▶ [Phase 4: Anfragen-Log]
  (DSGVO & 4 Sektionen)        (1-Klick Backup/Restore)       (Meta-Tags & OpenGraph)      (Lokales Postfach)
         │
         ▼
[Phase 5: Installer & Seed] ──▶ [Phase 6: Seiten & Typen] ──▶ [Phase 7: DB vs. JSON] ──▶ [Phase 8: Mehrsprachigkeit]
  (Setup-Wizard & Rollout)       (Reihenfolge & Builder)       (Dualer Storage-Treiber)      (i18n & Sprachumschalter)
         │
         ▼
[Phase 9: Security Hardening] ──▶ [Phase 10: Multi-User & RBAC] ──▶ [Phase 11: Logo & Mobile] ──▶ [Phase 12: Release & Updates]
  (Schwachstellen & Schutz)          (Admin / Moderator / Content)     (SVG/Upload & Audit)         (ZIP/7z & Remote Version-Check)
         │
         ▼
[Phase 13: Plugin-System] ────▶ [Phase 14: Demo-Bilddaten] ────▶ [Phase 15: Sandbox- & Demo-Modus]
  (Hooks & Webtrees Stammbäume)   (Neutrale Multi-Ratio Assets)     (Live-Tryout ohne Speichern & Auto-Reset)
         │
         ▼
[Phase 16: Downloads & Security]
  (Download-Portal, Klick-Tracking, Captcha-Engine & Accessibility-Bar)
```

---

### Phase 1: Rechtliche Vollständigkeit & Universelle Sektionstexte
*Ziel: 100 % Text-Autonomie über das CMS & rechtssichere DSGVO-Datenschutzerklärung.*

- [x] **1.1 DSGVO-Datenschutzerklärung im Backend verwalten:**
  - Neuer Unterbereich im Adminbereich (unter *„Impressum & Kontaktdaten“*).
  - Mehrzeiliges Markdown-/HTML-Textfeld für die Datenschutzerklärung inklusive *„Standard-DSGVO-Vorlage einfügen“*-Aktion.
  - Speicherung in `data.json` unter `$data['settings']['privacy_policy_text']` (sowie `$data['texts']['privacy_policy_text']`).
  - Bereitstellung einer rechtssicheren, abmahnsicheren DSGVO-Standardvorlage (Server-Logs, Hosting, E-Mail-Kontakt, Speicherdauer, Betroffenenrechte, keine Tracking-Cookies).
  - Dynamisches Ausspielen im Datenschutz-Modal/Slide-In im Frontend mit Umschalter zwischen Impressum und Datenschutzerklärung.
- [x] **1.2 Dynamische Untertitel & Einleitungstexte für Hauptsektionen:**
  - Konfigurierbare Überschriften und Fließtexte für alle Inhaltsblöcke im Adminbereich (*„Startseite & Universelle Sektionstexte“*):
    - Galerie/Portfolio: Titel (`gallery_title`) und Untertitel/Beschreibungstext (`gallery_subtitle`).
    - Kurse/Workshops/Events: Titel (`courses_title`) und Untertitel/Infotext (`courses_subtitle`).
    - Rückblick/Impressionen: Titel (`past_courses_title`) und Beschreibungstext (`past_courses_subtitle`).
    - Kontaktbereich: Einleitungstext über dem Formular (`contact_intro_text`).
- [x] **1.3 Prüfung & Abnahme:**
  - Texte im Backend geändert und sofortige Aktualisierung im Frontend verifiziert.
  - Datenschutzerklärung im Frontend über das Slide-In-Menü getestet und saubere Formatierung geprüft.

---

### Phase 2: Integriertes Datensicherungs- & Wiederherstellungssystem (Backup/Restore)
*Ziel: Schutz vor Datenverlust durch 1-Klick-Sicherungen und vollständige Wiederherstellbarkeit.*

- [x] **2.1 Backend-Verwaltung für Backups:**
  - Eigener Bereich im Reiter *„Sicherheit & System“*.
  - Tabelle aller vorhandenen Backups mit Typ-Badges, Zeitstempel, Dateigröße und Direktaktionen (Download, Restore, Löschen).
- [x] **2.2 Backup-Funktionalität:**
  - **1-Klick-Backup erstellen:**
    - Option A: *Nur Inhaltsdaten & Konfiguration* (`data.json`).
    - Option B: *Vollständiges Archiv* (Daten + `images/uploads/` als ZIP-Archiv inklusive `backup_manifest.json`).
  - Sicherer Speicherort im geschützten Verzeichnis `backups/` mit Zugriffsschutz (`.htaccess: Require all denied` / `Deny from all`).
  - Direkter ZIP-/JSON-Download über das Backend mit Authentifizierungsprüfung.
- [x] **2.3 Restore-Funktionalität:**
  - **Wiederherstellung aus vorhandenem Backup:** Auswahl eines gelisteten Backups mit Sicherheitsabfrage (PIN-bestätigt) und automatischem Notfall-Snapshot vorab.
  - **Backup-Upload:** Hochladen einer zuvor exportierten ZIP-/JSON-Datei zur Wiederherstellung (PIN-geschützt).
- [x] **2.4 Prüfung & Abnahme:**
  - Backup als ZIP und JSON erstellt, Tabelle und Größen geprüft.
  - PIN-Autorisierung getestet (Fehleingabe blockiert, korrekte PIN stellt Daten erfolgreich wieder her).
  - Zugriffsschutz des Verzeichnisses `backups/` im Browser verifiziert (HTTP 403 Forbidden).

---

### Phase 3: Universelles SEO- & Social-Sharing-Management
*Ziel: Optimale Auffindbarkeit bei Google und ansprechende Darstellung beim Teilen in sozialen Netzwerken.*

- [x] **3.1 Globales Meta- & Social-Sharing im Adminbereich:**
  - Einstellungsfelder unter *„SEO & Sharing“* (neuer Reiter in `.admin-nav`):
    - Meta Title (Seitentitel mit Vorschau & Zeichenzähler auf 60-Zeichen-Länge).
    - Meta Description (Beschreibung mit Vorschau & Zeichenzähler auf 155-Zeichen-Länge).
    - Meta Keywords (kommagetrennt).
    - OpenGraph Sharing Image (Bild-Upload für Facebook, WhatsApp, LinkedIn, X/Twitter mit Vorschau).
    - Canonical URL & Roboter-Steuerung (`index, follow`).
    - Live SERP & Social Card Preview in Echtzeit.
- [x] **3.2 Dynamische Head-Generierung im Frontend (`index.php`):**
  - Saubere Ausgabe aller Standard-Tags und OpenGraph-Tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:site_name`, `og:locale`).
  - Twitter Card Tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`).
- [x] **3.3 Prüfung & Abnahme:**
  - Quelltextprüfung der Meta-Tags und Live-Vorschau im Browser verifiziert.

---

### Phase 4: Integriertes Anfragen- & Kontaktpostfach
*Ziel: Zentrale Erfassung und Verwaltung von Kundenanfragen direkt im CMS-Backend.*

- [x] **4.1 Lokale Protokollierung von Kontaktanfragen:**
  - Beim Absenden des Kontaktformulars wird die Anfrage unabhängig vom Mailversand strukturiert in `data/contacts.json` (oder SQL-Tabelle `fb_contacts`) archiviert.
  - Gespeicherte Felder: Datum, Uhrzeit, Name, E-Mail, Telefon, Nachricht, Kurs-Referenz (falls vorausgewählt), Status (`neu`, `in_bearbeitung`, `erledigt`).
- [x] **4.2 Postfach-Reiter im Adminbereich:**
  - Neuer Reiter *„Kontaktanfragen“* mit Kennzeichnung neuer/ungelesener Nachrichten.
  - Tabellenansicht mit Schnellansicht, Volltext-Modal und Status-Umschaltung.
  - Schnellaktionen: *„Als E-Mail antworten“* (`mailto:`-Link mit vorausgefülltem Betreff) und *„Löschen“*.
- [x] **4.3 Prüfung & Abnahme:**
  - Testanfrage über das Frontend absenden und prüfen, dass sie sofort im Postfach auftaucht.

---

### Phase 5: Webspace-Installer & Setup-Assistent (`install.php`)
*Ziel: Vollautomatischer Installations-Assistent für das schnelle Ausrollen auf beliebigen Webspaces.*

- [x] **5.1 Installations-Erkennung & Sperre:**
  - Befindet sich noch keine `installed.lock` auf dem Server, leitet das CMS automatisch auf `install.php` um.
  - Nach erfolgreichem Setup wird `installed.lock` erzeugt; `install.php` ist danach gesperrt.
- [x] **5.2 Installationsschritte:**
  - **Schritt 1: System-Check (Health Check):**
    - PHP-Version (>= 8.0), Dateirechte (Schreibrechte für `data/`, `images/uploads/`, `backups/`), PHP-Module (`pdo`, `gd`, `mbstring`, `json`, `curl`).
  - **Schritt 2: Website-Grunddaten:**
    - Website-Name, Slogan, Inhaber/Betreiber, E-Mail-Adresse.
  - **Schritt 3: Administrator-Konto:**
    - Admin-Benutzername, Administrator-Passwort, Sicherheits-PIN.
  - **Schritt 4: Beispieldaten (Seed Content):**
    - Checkbox: *„Muster-Inhalte installieren (Empfohlen für neue Websites)“*.
  - **Schritt 5: Willkommensnachricht nach der Installation:**
    - Nach erfolgreichem Setup sieht der User beim ersten Admin-Login eine einmalige Willkommensnachricht (z. B. Modal/Banner).
    - Inhalt: Dank für die Installation, Kurzanleitung der wichtigsten Reiter, Hinweis auf Sicherheit (Passwort/PIN ändern, `install.php` gesperrt, Backups).
    - Wird nach dem Schließen per Flag in `data.json` (`settings.welcome_shown`) nicht mehr angezeigt.
- [x] **5.3 Prüfung & Abnahme:**
  - Testinstallation auf frischem Server durchführen und automatische Weiterleitung ins Admin-Dashboard verifizieren.

---

### Phase 6: Modulare Sektionen & Universeller Seiten-Manager
*Ziel: Absolute Freiheit beim Zusammenstellen, Sortieren und Ein-/Ausblenden von Inhaltsbereichen.*

- [x] **6.1 Sektions-Datenmodell (`sections` in `data.json`):**
  - Jede Sektion besitzt eine ID, einen Typ, Titel, Menü-Bezeichnung, Sichtbarkeit (`visible: true/false`) und Position.
  - Unterstützte Sektions-Typen:
    - `hero`: Header-Banner mit Überschrift, Call-to-Action und Logo.
    - `gallery`: Raster-Portfolio mit Kategorien und Vollbild-Lightbox.
    - `courses`: Termine / Workshops / Kurse mit Buchungs-Trigger.
    - `past_courses`: Rückblick / Impressionen vergangener Workshops.
    - `about`: Über mich / Atelierprofil mit Portrait und Biografie.
  - Zentral verwaltet durch `SectionManager.php` mit automatischem Fallback und Defaults.
- [x] **6.2 Sektions-Manager im Adminbereich:**
  - Visuelle Übersicht aller Sektionen im Reiter *„Startseite & Über mich“* unter Wahrung der Tab-Struktur.
  - Verschieben der Reihenfolge via *„Nach oben“* / *„Nach unten“* (AJAX mit Toast-Feedback und Auto-Reload).
  - Sofortiger Ein-/Aus-Schalter (`Sichtbar` / `Versteckt`) zur dynamischen Steuerung der Sichtbarkeit.
- [x] **6.3 Dynamisches Rendering im Frontend (`index.php`):**
  - Rendert die Sektionen flexibel anhand der konfigurierten Reihenfolge und Sichtbarkeit in `<main class="container">`.
  - Automatische Synchronisation der Navigationsmenüs (Desktop-Header & Mobile Slide-in) mit den sichtbaren Sektionen.
- [x] **6.4 Prüfung & Abnahme:**
  - Reihenfolge und Sichtbarkeit im Backend und Frontend browsergestützt verifiziert.

---

### Phase 7: Dualer Speicher-Treiber (Datenbankauswahl vs. Lokale JSON-Datei)
*Ziel: Wahlfreiheit zwischen einer SQL-Datenbank (MySQL / MariaDB / SQLite) und einer lokalen JSON-Datei.*

- [x] **7.1 Speicher-Abstraktionsschicht (Data Access Layer - DAL):**
  - Universelles PHP-Interface: `loadData(): array`, `saveData(array $data): bool`, `ping(): bool`.
  - **Treiber A: `JsonStorageDriver` (Standard & Fallback):**
    - Liest und schreibt atomar in `data.json`. Funktioniert ohne Datenbank auf jedem Webspace.
  - **Treiber B: `PdoSqlStorageDriver` (MySQL / MariaDB):**
    - Speichert Entitäten in relationalen Tabellen via PDO mit Transaktionssicherheit.
  - **Treiber C: `SqliteStorageDriver` (Lokale SQL-Datei):**
    - Dateibasierte SQL-Datenbank (`data/app.sqlite`), vereint SQL-Features mit Datei-Einfachheit.
  - **Treiber-Manager (`StorageManager`):**
    - Automatische Treiber-Initialisierung anhand der Konfiguration mit transparentem Notfall-Fallback auf JSON bei Verbindungsproblemen.
- [x] **7.2 Auswahl im Installationsassistenten (`install.php`):**
  - Wählbare Speicher-Engine (Flat-File JSON vs. MySQL / MariaDB) mit Live-Verbindungstest via AJAX.
  - Automatischer Notfall-Fallback auf die lokale JSON-Datei bei Datenbankproblemen.
  - Sichere Ablage von DB-Zugangsdaten in `config.local.php`.
- [x] **7.3 1-Klick-Daten-Migration im Adminbereich:**
  - Assistent zum Übertragen bestehender Daten von JSON nach SQL oder umgekehrt im Reiter *„Sicherheit“* (PIN-geschützt).
  - Automatisches Sicherheits-Backup vor jeder Datenmigration.
  - Live-Verbindungstest für MySQL via AJAX direkt im Dialog.
  - Dynamischer Status und automatisches Umschalten des aktiven Treibers.
- [x] **7.4 Prüfung & Abnahme:**
  - Zwei-Wege-Migration (JSON -> MySQL -> JSON) inkl. Datenkonsistenz und Table-Schema verifiziert.

---

### Phase 8: Mehrsprachigkeit (i18n & Sprachumschalter)
*Ziel: Mehrsprachiger Webauftritt mit Sprachauswahl bei der Installation und optionaler Aktivierung weiterer Sprachen.*

- [x] **8.1 Sprachauswahl bei der Erstinstallation:**
  - Auswahl der Hauptsprache (12 Sprachen für West-/Südeuropa & Skandinavien: DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI) in Schritt 3 des Assistenten (`install.php`).
  - Standardtexte und Admin-Oberfläche starten in der gewählten Primärsprache.
- [x] **8.2 Sprach-Architektur & Datenmodell:**
  - System-Labels (Buttons, Formulartexte, Navigation) in modularen JSON-Sprachdateien (`libs/lang/de.json`, `en.json`, `fr.json`, `it.json`, `nl.json`, `es.json`, `pt.json`, `el.json`, `da.json`, `no.json`, `sv.json`, `fi.json`).
  - Zentrale Klasse `LanguageManager` mit Cookie-Persistenz (`weave_lang`), GET-Param-Wechsler (`?lang=xx`), Browser-Locale-Erkennung und Fallback-Kaskade (Content -> Primärsprache -> Fallback-Text).
- [x] **8.3 Sprachverwaltung im Adminbereich:**
  - Aktivieren/Deaktivieren von Sprachen per Checkbox unter *„Darstellung & Design“*.
  - Dynamischer Sprach-Tab-Umschalter im Reiter *„Texte“* mit Fallback-Platzhaltern für sekundäre Sprachen.
- [x] **8.4 Frontend-Sprachwechsler & Internationales SEO:**
  - Dezenter Sprachschalter in der Desktop-Navigation neben den Menü-Triggern sowie im mobilen Drawer (`DE | EN`, etc.), Footer bleibt clean.
  - Dynamische `<html lang="...">`, OpenGraph Locale (`og:locale`) und `<link rel="alternate" hreflang="...">` Tags für alle aktiven Sprachen.
- [x] **8.5 Prüfung & Abnahme:**
  - Mehrsprachiger Betrieb im Browser erfolgreich verifiziert (Sprachwechsel in Admin und Frontend, saubere Darstellung).

---

### Meilenstein v0.8.5: Content-Hub, Blog-System & Flexible Trigger-Platzierung
*Ziel: Modulare Bündelung aller Inhaltsbereiche, vollwertiges Blog-System und freie Platzierung von Kontakt & Impressum.*

- [x] **Flexible Trigger-Platzierung (Kontakt & Impressum):**
  - Einstellung unter *„Darstellung & Design“* mit den Optionen: `Nur Header`, `Nur Footer`, `Header & Footer (Beides)`.
  - Frontend passt Desktop-Navigation, mobilen Drawer und Footer automatisch an.
- [x] **Backend-Menü Reorganisation (Content-Hub):**
  - Menüleiste aufgeräumt: Bündelung aller Inhalts-Tabs unter *„Inhalte & Seiten“* (`.admin-nav`).
  - Kontextsensitive Sub-Navigation (`.admin-subnav`) für schnelles Umschalten zwischen Startseite, Blog, Galerie, Workshops, Impressionen und Impressum.
- [x] **Vollwertiges Blog- & Magazin-Modul:**
  - Artikel-Verwaltung im Admin-Bereich (`tab-blog`) mit Titel, Slug, Datum, Autor, Status (`Veröffentlicht` / `Entwurf`), Beitragsbild, Teaser und Markdown-Inhalt.
  - Eigene Sektion auf der Startseite mit automatischer Sortierung und responsiven Artikelkarten.
  - Vollständige Einzelartikel-Leseansicht (`?blog=slug`) mit Social-Sharing (WhatsApp, Facebook, Link kopieren) und optimierten OpenGraph-Metadaten.
- [x] **Typo3/Drupal/Joomla Content-Typen Erweiterung:**
  - **Freie Einzelseiten (Custom Subpages):** Standalone-Unterseiten (`?page=slug`) mit Markdown, SEO-Description und optionaler direkter Einbindung ins Hauptmenü.
  - **FAQ / Häufige Fragen:** Modernes Akkordeon mit automatischer Generierung strukturierter Daten (`application/ld+json` Schema.org `FAQPage`) für Google Rich Snippets.
  - **Team & Meister:** Profilkarten mit Porträtbild, Spezialisierung/Rolle, Kurzvita und direktem Kontakt-Trigger.
  - **Download-Manager:** Upload und Bereitstellung von PDFs, Flyern und Dokumenten mit Dateigrößen- und Format-Anzeige.
  - **Leistungen & Preise:** Flexible Preistabellen / Servicekarten mit Bestseller-Badges, Feature-Checklisten und direktem Anfrage-Link.
- [x] **Sprachdateien-Audit aller 12 Sprachen (`libs/lang/*.json`):**
  - Vollständige Ergänzung aller neuen Systemschlüssel (`btn.read_more`, `btn.download`, `btn.back_home`, `btn.back_overview`, `btn.contact_person`, `blog.no_posts`, `blog.share_post`, `faq.no_items`, `team.no_items`, `downloads.no_items`, `pricing.no_items`) in allen 12 unterstützten Sprachen (DE, EN, FR, IT, ES, PT, NL, DA, NO, SV, FI, EL).

---

### Phase 9: Anwendungs-Sicherheits-Härtung & Schwachstellen-Prävention (Core & Testsystem)
*Ziel: Fundierte Absicherung gegen Web-Angriffe (OWASP Top 10), Upload-Exploits und Brute-Force – vollständig lauffähig und testbar im lokalen Entwicklungssystem ohne Aussperrungsrisiko.*

- [x] **9.1 Adaptive HTTP Security Header:**
  - Auslieferung moderner Sicherheits-Header im Core: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.
  - Dynamische Anpassung: HSTS und HTTPS-spezifische Header werden nur bei echter HTTPS-Verbindung aktiviert (verhindert Verbindungsfehler auf lokalem `http://localhost`).
- [x] **9.2 Brute-Force-Schutz & Rate-Limiting mit Dev-Sicherheitsnetz:**
  - Login & PIN: Nach 5 Fehlversuchen progressive Zeitverzögerung und 15-minütige Sperre mit strukturierter Tracking-Ablage (`data/rate_limits.json`).
  - Dev-Sicherheitsmechanismus: Lokale IP-Adressen (`127.0.0.1`, `::1`) können im Testsystem im Notfall schnell entriegelt werden.
  - Kontaktformular: Intelligentes Rate-Limiting gegen automatisiertes Spambot-Flooding.
- [x] **9.3 Dateisicherheit & Upload-Audit:**
  - Vollständige `.htaccess`-Abschirmung: Direkter Aufruf von `data.json`, `contacts.json`, `config*.php`, `.git/`, `backups/`, `logs/` und `libs/` strikt blockiert (`403 Forbidden`).
  - Upload-Härtung in `ImageUploader`: Echte MIME-Type Magic-Byte-Prüfung (`finfo_file`), Neukompilierung via GD (Bereinigung versteckter PHP-Payloads in EXIF-Metadaten), kryptografische Zufallsnamen, Ausführungsverbot für Skripte im Upload-Ordner.
- [x] **9.4 XSS-, CSRF- & Injection-Prävention:**
  - Durchgängiges `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  - Konsequenter CSRF-Token-Schutz für alle Backend-POST-Aktionen und Formular-Endpoints.
  - Verifizierung von Prepared Statements bei allen Datenbanktreibern.
- [x] **9.5 Session-Härtung & Audit-Log:**
  - Cookies mit `HttpOnly`, `SameSite=Strict` und dynamischem `Secure`-Flag (aktiviert bei HTTPS).
  - Session-ID-Regeneration bei Login & PIN-Authentifizierung (`session_regenerate_id(true)`).
  - Inaktivitäts-Timeout (automatischer Logout nach konfigurierbarer Leerlaufzeit).
  - Internes Sicherheits-Audit-Log (`logs/security.log`) zur Nachvollziehbarkeit sicherheitsrelevanter Aktionen.
- [x] **9.6 Prüfung & Abnahme:**
  - Sicherheitsaudit durchführen: Fehllogins testen, Rate-Limiting verifizieren, Upload-Sicherheitscheck durchführen.

---

### Phase 10: Backend-Dashboard (Default-Startseite) & Mehrbenutzersystem (RBAC)
*Ziel: Ein zentrales, informatives Backend-Dashboard als Standard-Startseite nach dem Login (Frontend-Zugriffe, Sicherheits-Monitor, Benutzerübersicht, Quick-Actions) sowie ein vollständiges Mehrbenutzersystem mit rollenbasierter Rechteverwaltung (Administrator, Moderator, Content-User).*
*(Hinweis Workflow: Auf Kundenwunsch wird direkt im Anschluss Phase 13: Modulares Plugin-System vorgezogen).*

- [x] **10.1 Backend-Dashboard als zentrale Übersichts-Startseite (`tab-dashboard`):**
  - Neuer Standard-Reiter ganz links in `.admin-nav`, auf dem Administratoren und Redakteure nach dem Login landen.
  - **KPI-Statistikkarten:**
    - 👁️ **Frontend-Zugriffe & Traffic:** Leichtgewichtiger, DSGVO-konformer Aufruf-Zähler (Aufrufe heute & gesamt).
    - 🛡️ **Sicherheits-Status:** Aktive Schutzmodule, Rate-Limit-Status und 0/X blockierte Angriffe.
    - 📥 **Kontaktpostfach:** Status ungelesener Anfragen mit Direktlink.
    - 👥 **Benutzerkonten:** Anzahl aktiver Nutzer aufgeteilt nach Rollen.
  - **Zwei-Spalten-Monitor:**
    - **Aktivitäts- & Zugriffs-Verlauf:** Übersicht der letzten Tage und neueste Postfacheingänge.
    - **Sicherheits-Live-Monitor:** Auszug der letzten 5 Einträge aus `logs/security.log` mit Schweregrad-Badges.
  - **Quick-Actions:** Schnellzugriffe für häufige Aktionen („Neuer Blogbeitrag“, „Bild hochladen“, „Benutzer anlegen“, „1-Klick-Backup“).
- [x] **10.2 Rollenmodell & Berechtigungsmatrix (`UserManager`):**
  - **Administrator (Superadmin):** Vollzugriff auf alle Bereiche (Dashboard, Inhalte, Postfach, Benutzer, Design, SEO, Sicherheit & Backups).
  - **Moderator:** Redaktionelle Leitung (Dashboard, Inhalte & Seiten, Galerie, Blog, Termine, Postfach). Kein Zugriff auf System, Design oder Benutzerverwaltung.
  - **Content-User (Redakteur):** Reine Inhaltspflege (Dashboard, Texte, Blog, Galerie). Kein Zugriff auf Struktur, Postfach, System oder Benutzer.
- [x] **10.3 Datenmodell für Benutzer (`users` in `data.json` bzw. SQL-Tabelle `fb_users`):**
  - Felder: `id`, `username`, `email`, `password_hash`, `role`, `active`, `created_at`, `last_login`.
  - Automatische Migration / Seeding: Bisheriger Einzeladmin wird beim ersten Start nahtlos als initialer Superadmin registriert.
- [x] **10.4 Backend-Benutzerverwaltung (Reiter `tab-users` für Administratoren):**
  - Übersichtstabelle aller Benutzer mit Rollen-Badge, Status und letztem Login.
  - Neuanlage mit Rollenvergabe (*keine öffentliche Registrierung im Frontend!*).
  - Benutzer sperren/aktivieren, bearbeiten und Passwörter PIN-geschützt zurücksetzen.
  - Eigenes Profil für jeden angemeldeten Benutzer zur Passwortänderung.
- [x] **10.5 Serverseitiges Berechtigungs-Enforcement:**
  - Jede Backend-Aktion validiert die Rolle strikt auf Serverebene (`UserManager::canAccessAction($action, $currentUser)`).
  - Backend-Navigation rendert nur erlaubte Reiter für den jeweiligen Benutzer.
- [x] **10.6 Prüfung & Abnahme:**
  - Test aller 3 Rollen (Admin, Moderator, Redakteur) bezüglich Sichtbarkeit und Aktionsgrenzen.
  - Dashboard-KPIs und Zugriffs-Tracking im Browser verifizieren.

---

### Phase 11: Eigenes Logo (Upload, Skalierung & Favicon) & Umfassendes Mobile-Responsiveness-Audit
*Ziel: Volle visuelle Identität für jedes Projekt (flexibles Logo & Favicon) sowie perfekte, fehlerfreie Bedienung und Darstellung auf allen mobilen Geräten (Smartphones & Tablets).*

- [ ] **11.1 Logo- & Branding-Verwaltung im Adminbereich:**
  - Konfigurierbar im Bereich *„Design & Logo“*:
    - **Logo-Typ wählbar:**
      - *Reines Text-Logo mit Slogan* (Standard)
      - *Bild- / SVG-Logo*
      - *Kombination aus Bild-Icon + Text*
    - **Logo-Upload:** Unterstützt SVG (vektorbasiert, randscharf), PNG (transparent), WebP und JPG.
    - **Logo-Dimensionierung:** Schieberegler / Eingabefeld für maximale Höhe (z. B. 32px bis 100px) und automatische Breiten-Skalierung, damit der Header nicht verzerrt wird.
    - **Favicon-Upload:** Hochladen von `.ico`, `.svg` oder `.png` für Browser-Tabs und Smartphone-Home-Screen (`apple-touch-icon`).
    - **Direkte Live-Vorschau:** Sofortige Ansicht des hochgeladenen Logos auf hellem und dunklem Header-Hintergrund.
- [ ] **11.2 Frontend-Integration & Logo-Rendering (`index.php`):**
  - Dynamisches Ausspielen des Bild- bzw. Text-Logos im Desktop-Header und mobilen Header.
  - Saubere Einbindung im `<head>` als Favicon und App-Icon.
  - Automatisches Fallback auf den Projektnamen, falls kein Bild hochgeladen wurde.
- [ ] **11.3 Vollständiges Mobile-Responsiveness-Audit (Alle Breakpoints):**
  - **Header & Navigation:**
    - Hamburger-Button und Slide-In-Menü mit Touch-freundlichen Abständen (Mindesthöhe / Touch-Target >= 44x44px nach WCAG).
    - Kein horizontales Scrollen / Layout-Brechen auf kleinen Displays (ab 360px Breite).
  - **Typografie & Formulare:**
    - Fließende Schriftgrößen via `clamp()`, lesbar ohne manuelles Zoomen.
    - Formulareingaben mit `font-size: 16px` auf mobilen Geräten (verhindert das automatische, störende Hineinzoomen auf iPhones/iOS Safari).
  - **Galerie & Lightbox auf Touch-Geräten:**
    - Touch-/Swipe-Gestenunterstützung oder intuitive Schließen- und Weiter-Buttons in der Lightbox.
  - **Testmatrix:**
    - Smartphones (360px, 390px, 414px – iPhone SE, iPhone 14/15, Samsung Galaxy).
    - Tablets (768px, 820px, 1024px – iPad Portrait & Landscape).
- [ ] **11.4 Prüfung & Abnahme:**
  - Eigenes SVG- und PNG-Logo hochladen, Größe anpassen und Responsiveness im Browser mit Device-Emulation überprüfen.
  - Formulareingaben und Menübedienung auf Mobile-Viewports testen.

---

### Phase 12: Release-Packaging, Update-Prüfung & Versionskontrolle
*Ziel: Automatisierte Erstellung von distributionsfertigen Release-Archiven (ZIP/7z) und ein Update-Prüfsystem im CMS, das neue Versionen erkennt, Unterschiede anzeigt und die Aktualisierung ermöglicht.*

- [ ] **12.1 CMS-Versionskontrolle & Remote Version-Check:**
  - Zentrale Versionskonstante im Core: `define('WEAVE_VERSION', '1.0.0');`
  - Konfigurierbare Update-Server-URL im Adminbereich (oder Vorgabe in `config.php`, z. B. `https://updates.weavecms.de/version.json` oder GitHub Releases API).
  - **Automatischer/manueller Version-Check:**
    - Admin ruft die Remote-URL via `file_get_contents` oder `cURL` ab (mit Timeout von 3 Sekunden und Caching von 12 Stunden).
    - Server liefert Metadaten:
      ```json
      {
        "latest_version": "1.1.0",
        "release_date": "2026-11-01",
        "changelog_url": "https://weavecms.de/changelog/1.1.0",
        "download_url": "https://weavecms.de/downloads/weavecms-1.1.0.zip",
        "update_package_url": "https://weavecms.de/downloads/update-1.0-to-1.1.zip",
        "min_php": "8.0",
        "requires_db_migration": false
      }
      ```
    - Visueller Indikator im Admin-Header / Dashboard:
      - *System ist aktuell (v1.0.0)* oder
      - *Update verfügbar: Version 1.1.0 [Changelog ansehen | Herunterladen]*
- [ ] **12.2 Release-Packaging-Generator (Build-Skript für ZIP / 7z):**
  - Bereitstellung eines internen CLI- oder Admin-Skripts (`tools/build_release.php`):
    - **Typ A: Voll-Paket (`weavecms-vX.Y.Z.zip`):**
      - Für Neuinstallationen auf Webspaces.
      - Enthält den gesamten CMS-Kern, `install.php`, Standard-Dateien, neutrale Beispieldaten (`data.sample.json`), `.htaccess`, Dokumentation.
      - Schließt strikt aus: `installed.lock`, `data.json`, `contacts.json`, `config.local.php`, `backups/`, echte Kundenuploads in `images/uploads/*`.
    - **Typ B: Update-Paket (`update-vX.to.vY.zip`):**
      - Für bestehende Live-Websites.
      - Enthält **ausschließlich** die aktualisierten System- und Core-Dateien (`index.php`, `admin/`, `libs/`, `config.php`).
      - Schließt ausnahmslos alle Nutzerdaten, Einstellungen, Datenbanken und Upload-Ordner aus, damit niemals Daten überschrieben werden!
- [ ] **12.3 Intelligente Update-Strategie nach Datenspeicher-Typ:**
  - **Bei Flat-File (JSON-Modus):**
    - Systemdateien werden aktualisiert.
    - Automatische Datenstruktur-Prüfung (`SchemaMigration`): Ein Upgrader prüft beim ersten Login nach dem Update, ob in `data.json` neue Konfigurations-Keys fehlen, und ergänzt diese rückwärtskompatibel mit Standardwerten.
  - **Bei relationaler Datenbank (SQL-Modus / SQLite / MySQL):**
    - Ausführung versionierter SQL-Migrationsskripte (z. B. `migrations/update_1.1.0.sql`), um Tabellen, Indizes oder neue Spalten gefahrlos zu erweitern.
- [ ] **12.4 Vorab-Sicherungs-Pflicht vor jedem Update:**
  - Vor dem Entpacken oder Anwenden von Updates legt das System automatisch einen vollständigen Snapshot in `backups/` an (Pre-Update-Backup).
- [ ] **12.5 Prüfung & Abnahme:**
  - Build-Skript testen: Erstellen eines sauberen ZIP-Archivs und Entpacken auf einer Testinstanz.
  - Test-Versionsabfrage mit lokaler `version.json` durchführen und Update-Hinweis im Adminbereich verifizieren.

---

### Phase 13: Modulares Plugin- & Erweiterungssystem (Plugins/Module)
*Ziel: Ein offenes, leichtgewichtiges Plugin- und Modulsystem für WeaveCMS, mit dem eigene Themes, Contentseiten, Berechtigungserweiterungen und Drittsystem-Integrationen (wie Webtrees Stammbäume) nahtlos integriert, im Adminbereich verwaltet und aktiviert/deaktiviert werden können.*

- [ ] **13.1 Zentraler Plugin-Ordner (`plugins/`) & Manifest-Struktur:**
  - Einrichtung des zentralen Verzeichnisses `plugins/` im Web-Root mit `.htaccess`-Schutz.
  - Jedes Plugin erhält einen eigenen Unterordner (`plugins/<plugin-slug>/`) mit einheitlichem Aufbau:
    ```text
    plugins/
    └── <plugin-slug>/
        ├── plugin.json        # Metadaten-Manifest (Name, Version, Autor, Beschreibung, Hooks)
        ├── plugin.php         # Haupteinstiegspunkt & Registrierung (Lifecycle-Callbacks)
        ├── admin/             # Eigene Admin-Views, Einstellungs-Tabs oder Subnav-Punkte
        ├── views/             # Frontend-Templates, Shortcodes oder Sektions-Renderings
        └── assets/            # Lokale Assets (CSS, JS, Icons)
    ```
  - Standardisiertes JSON-Manifest (`plugin.json`):
    ```json
    {
      "name": "Webtrees Stammbaum-Integration",
      "slug": "webtrees-genealogy",
      "version": "1.0.0",
      "author": "WeaveCMS Team",
      "description": "Bindet Stammbäume, Ahnentafeln und genealogische Personenübersichten aus Webtrees nahtlos in WeaveCMS ein.",
      "category": "content",
      "entry_file": "plugin.php",
      "requires_core": ">=0.9.0",
      "settings_tab": "webtrees",
      "enabled": true
    }
    ```

- [ ] **13.2 Plugin-Manager & Hook-System (`libs/php/PluginManager.php`):**
  - Zentrale Core-Klasse `PluginManager` zur automatischen Entdeckung (`discover()`), Validierung, Aktivierung/Deaktivierung und Ausführung von Plugins.
  - Ereignis- und Hook-Architektur für nahtlose Erweiterbarkeit ohne Core-Modifikationen:
    - **Actions:** `PluginManager::addAction('hook_name', $callback)` / `PluginManager::doAction('hook_name', ...$args)`
    - **Filters:** `PluginManager::addFilter('filter_name', $callback)` / `PluginManager::applyFilters('filter_name', $value, ...$args)`
  - Standard-Hooks in WeaveCMS:
    - `admin_nav_items`: Registrierung neuer Admin-Tabs oder Subnav-Buttons.
    - `admin_tab_render`: Rendern modulspezifischer Einstellungsmasken im Adminbereich.
    - `frontend_nav_items`: Hinzufügen dynamischer Menüeinträge im Frontend.
    - `frontend_render_section`: Eigene Sektions-Typen im `SectionManager` für das Frontend registrieren.
    - `auth_permissions`: Eigene Berechtigungsstufen und RBAC-Rollen definieren (z. B. Moderator für Spezial-Content).

- [ ] **13.3 Modul- & Plugin-Übersicht im Adminbereich:**
  - Neuer Bereich bzw. Subnav-Tab unter *„Sicherheit & System“* (oder eigener Reiter *„Plugins & Module“*).
  - Übersichtliche Kachel-/Tabellenansicht aller im Ordner `plugins/` gefundenen Erweiterungen:
    - Modul-Name, Icon, Versionsnummer, Autor und Kategorie (*Theme*, *Content*, *Sicherheit/Berechtigung*, *Integration*).
    - Status-Badge (*Aktiv* / *Inaktiv*) und 1-Klick-Toggle zur Aktivierung/Deaktivierung.
    - Direktlink zu den modulspezifischen Einstellungen.
    - Kompatibilitäts-Hinweis bei inkompatibler CMS-Core-Version.

- [ ] **13.4 Anleitung zur Einbindung & Entwicklung eigener Module (Entwickler-Guide):**
  - Schritt-für-Schritt-Leitfaden in `plugins/README.md` und `libs/docs/plugin-guide.md`:
    1. **Theme- & Design-Module:** Eigene CSS-Variablen, Typografie-Paletten und Layout-Vorlagen bereitstellen.
    2. **Content- & Sektions-Module:** Eigene modulare Sektionen im Sektionen-Manager einhängen (z. B. Rechner, Timelines, Kalender).
    3. **Berechtigungs-Module:** Erweiterung des RBAC-Systems um granulare Rollen und Beschränkungen.
    4. **Drittsystem-Module:** Anbindung externer Datenquellen und APIs.
  - Vorlage für ein Starter-Plugin (`plugins/_starter-example/`).

- [ ] **13.5 Pilot-Projekt: Webtrees Stammbaum-Integration (`plugins/webtrees-genealogy`):**
  - Erstes natives WeaveCMS-Plugin zur Einbindung von Webtrees Stammbäumen:
    - Anbindung an Webtrees (über direkte DB-Verbindung, REST-API oder GEDCOM-Import).
    - Interaktive Frontend-Darstellung von Stammbaum-Diagrammen, Ahnenreihen und Personen-Steckbriefen.
    - Datenschutzfilter: Automatische Anonymisierung / Ausblendung lebender Personen gemäß DSGVO.
    - Eigener Konfigurations-Tab im WeaveCMS-Backend zur Auswahl des Standard-Stammbaums und Darstellungsparameter.

- [ ] **13.6 Prüfung & Abnahme:**
  - Plugin-Erkennung und Validierung des `plugin.json`-Manifests im Adminbereich testen.
  - Aktivierung und Deaktivierung ohne Seiteneffekte verifizieren.
  - Fehlerisolation (Graceful Degradation): Fehler in einem Plugin dürfen das restliche CMS nicht zum Absturz bringen.

---

### Phase 14: Bilddaten-Bereinigung & Demo-Asset-Paket (Galerie-Erlebnis)
*Ziel: Vollständiges Bereinigen themenspezifischer Altlasten (frühere Flecht- und Korbwarenbilder) und Ersetzen durch ein kuratiertes, neutrales Demo-Bildset verschiedener Formate und Auflösungen, um alle vier Galerie-Layouts (Ambient-Blur, Masonry, Editorial, Classic 4:3) im Live-Betrieb demonstrieren und testen zu können.*

- [ ] **14.1 Bereinigung der Altlasten:**
  - Identifikation und Bereinigung aller projektspezifischen Altbilder aus dem früheren Flechtbereich in `images/uploads/` und `images/`.
  - Aktualisierung der Bildreferenzen in `data.json` und `data.sample.json` auf neutrale, universelle Bildnamen.
- [ ] **14.2 Kuratiertes Demo-Bildset mit Multi-Ratio-Formaten:**
  - Erstellung/Bereitstellung hochwertiger, royalty-freier Demo-Bilder für Handwerks-, Atelier- und Design-Betriebe:
    - **Hochformate (3:4, 2:3):** Zur perfekten Demonstration des dynamischen **Masonry-Grids** (Pinterest-Effekt).
    - **Querformate & Breitbilder (16:9, 3:2):** Zur Demonstration des **Smart Ambient-Blur** Modus (weicher Farbverlauf an den Rändern ohne Beschnitt).
    - **Quadratische Formate (1:1):** Für ausgewogene Karten und Magazin-Layouts im **Editorial-Grid**.
    - **Klassische Formate (4:3):** Zur Verifizierung des **Classic-Grids** mit gleichmäßigem Zuschnitt.
- [ ] **14.3 Neutrale Themensetzung & Kategorien:**
  - Überarbeitung der Galerie-Kategorien auf universelle Gewerke:
    - z. B. *Design & Unikate*, *Naturmaterialien*, *Wohnaccessoires*, *Atelier & Werkstatt*.
  - Aussagekräftige Werkstück-Titel und barrierefreie Alternativtexte (`alt`-Tags) für alle Demo-Bilder.
- [ ] **14.4 Performance- & Format-Optimierung:**
  - Bereitstellung in optimiertem WebP-Format mit sanfter Kompression (< 120 KB pro Bild) für maximale PageSpeed-Werte.
  - Generierung passender Vorschaubilder (Thumbnails) für schnelle Ladezeiten.
- [ ] **14.5 Prüfung & Abnahme:**
  - Umschalten zwischen allen 4 Galerie-Modi unter *„Darstellung & Design“* und visuelle Prüfung der korrekten Ausrichtung auf Desktop-, Tablet- und Smartphone-Breakpoints.

---

### Phase 15: Interaktiver Sandbox- & Demo-Modus (Live-Tryout ohne Persistenz)
*Ziel: Ein sicherer, temporärer Demo-Zugang für Interessenten und Tester, in dem das gesamte WeaveCMS interaktiv ausprobiert werden kann (Seiten erstellen, Texte anpassen, Designs testen, Sektionen verschieben), ohne dass Änderungen dauerhaft gespeichert werden – mit automatischem Reset.*

- [ ] **15.1 Machbarkeits- & Architekturkonzept (Sandbox-Engine):**
  - **Architektur:** Die modulare Speicherabstraktion (`StorageManager` / `JsonStorageDriver`) wird um einen flüchtigen Sandbox-Treiber (`EphemeralSessionDriver`) erweitert.
  - **Funktionsweise:**
    - Ein Demo-Nutzer startet über einen Demo-Link (z. B. `admin/?demo=start`).
    - Das System initialisiert eine isolierte Session mit dem Inhalt aus `data.sample.json`.
    - Alle Schreibzugriffe (Seiten anlegen, Menü anpassen, Blog-Artikel verfassen, Farbschemata ändern) werden ausschließlich im flüchtigen Session-Speicher des Nutzers (`$_SESSION['weave_demo_data']` bzw. einer temporären Datei `data/sandbox/<session_token>.json`) abgelegt.
    - Das Frontend zeigt für diesen Nutzer live das modifizierte Ergebnis an (`?demo=1`), während reguläre Besucher weiterhin die unveränderte Website sehen.
- [ ] **15.2 Sicherheits- & Schreibschutz-Schranken im Demo-Modus:**
  - Schutz vor Missbrauch: Kritische Aktionen sind im Demo-Modus strikt gesperrt:
    - Ändern des Administrator-Passworts und der PIN blockiert.
    - E-Mail-Versand (SMTP-Test / Kontaktformular) deaktiviert oder als lokaler Toast simuliert.
    - Upload-Möglichkeiten auf temporäre Dateien begrenzt (max. 5 Bilder je Sitzung).
    - Datenbank-Migrationen und `.htaccess`-Modifikationen deaktiviert.
- [ ] **15.3 Visueller Sandbox-Banner & Steuerung:**
  - Fixes, elegantes Status-Banner am oberen Bildschirmrand (im Admin und Frontend):
    - *„🌿 WeaveCMS Live-Demo: Du kannst alle Funktionen ausprobieren. Änderungen werden nicht dauerhaft gespeichert.“*
    - Button: *„Demo zurücksetzen“* (wirft alle Änderungen sofort weg und stellt die Standard-Demo wieder her).
    - Button: *„Demo beenden“* (beendet die Sandbox-Sitzung und leert den Cache).
- [ ] **15.4 Automatischer Bereinigungs-Zyklus (Garbage Collection):**
  - Temporäre Sandbox-Dateien werden nach 30 Minuten Inaktivität automatisch gelöscht.
  - Die Stammdateien `data.json`, `config.php` und das Hauptdateisystem bleiben zu 100 % read-only und unberührt.
- [ ] **15.5 Prüfung & Abnahme:**
  - Demo-Sitzung starten: Neue Unterseite anlegen, Menütitel ändern, Farbschema wechseln.
  - Gegenprobe im Inkognito-Fenster: Echte Website bleibt unverändert.
  - Klick auf *„Demo zurücksetzen“*: Sitzung kehrt fehlerfrei zum Ursprungszustand zurück.

---

### Phase 16: Modularer Download- & Software-Bereich, Captcha-Engine & Besucher-Accessibility
*Ziel: Ein vollwertiges, flexibles Content-Modul für Software, Dokumente und Downloads inklusive optionaler Profi-Metadaten (Version, Plattform, Dateigröße, Changelog-Drawer, Screenshot-Lightbox, Klick-Tracking/Counter), wählbare Multi-Captcha-Systeme im Sicherheits-Tab sowie eine im Backend optional aktivierbare Besucher-Barrierefreiheitsleiste (Dark-Mode & Font-Zoom).*

- [ ] **16.1 Modularer Content-Bereich „Downloads & Software“:**
  - **Sektions- & Content-Architektur:**
    - Neuer konfigurierbarer Sektionstyp `downloads` (analog zu Galerie, Kursen, Blog).
    - Einbindung in den Sektionen-Manager sowie Schnell-Leiste (Sektionstitel, Menübezeichnung und Sichtbarkeit).
  - **Flexible Download-Karten mit optionalen Feldern:**
    - Basisfelder: Titel, Kurzbeschreibung, Kategorie, Download-Datei (Upload via Media-Manager) oder externer Link (z. B. GitHub Release, Cloud-Speicher).
    - Optionale Profi-Felder (je Eintrag frei zuschaltbar):
      - **Versions-Badge** (z. B. `v1.2.0`, `v0.6.0`).
      - **Plattform- / OS-Kennzeichnung** (z. B. *Windows 10/11*, *macOS*, *Linux*, *Portable*, *Web-Modul*).
      - **Dateigröße & Lizenz** (z. B. `14.2 MB`, *Freeware*, *MIT / Open Source*).
      - **Feature-Bulletpoints** (strukturierte Liste der Kernfunktionen).
      - **Screenshot-Galerie:** Optionale Vorschaubilder mit interaktivem Lightbox-Zoom.
      - **Changelog-Drawer / Modal:** Integriertes Markdown-Feld für die Versionshistorie, das im Frontend per Klick auf einen dezenten *„📜 Changelog“*-Button als flüssiges Modal/Drawer geöffnet wird.
- [ ] **16.2 Integriertes Download-Tracking & Klick-Counter:**
  - Direkt in das Download-Modul integrierte Zähl-Engine (flüsterleise und ohne externe Tracking-Dienste).
  - Sicherer, manipulationsgeschützter Download-Proxy (`api/download.php?id=...`), der Downloads ausliefert und atomar im Speichertreiber (Flat-File JSON oder SQL) zählt.
  - Integrierter Spamschutz / Bot-Filter (Crawler und Suchmaschinen erhöhen den Zähler nicht).
  - **Frontend-Option:** Optionales Badge je Karte (*„X Downloads“*), im Admin pro Download-Item ein- oder ausschaltbar sowie Zähler manuell zurücksetzbar.
  - **Admin-Übersicht:** Statistik-Spalte mit aktuellen Download-Zahlen im Download-Tab.
- [ ] **16.3 Modulare Captcha-Auswahl-Engine (Sicherheits- & Einstellungs-Tab):**
  - Erweiterung des Sicherheitsbereichs im Backend um ein zentrales Auswahlfeld: *„Aktives Captcha-Verfahren für Formulare & Bot-Schutz“*:
    1. **Symbol-Captcha (Standard):** Das bewährte, datenschutzfreundliche Klick-Symbol (Pfote, Stern, Herz).
    2. **Mathe- / Rechen-Captcha:** Dynamisch generierte, zufällige Rechenaufgabe (z. B. *„Wie viel ist 4 + 3?“*).
    3. **HTML5 Canvas-Zahlen-Captcha:** Clientseitig/serverseitig generierter 4- bis 6-stelliger Zahlencode direkt auf einem Bild-Canvas (wie in `ownsite` erprobt) gegen automatisierte Text-Scraper.
    4. **Unsichtbarer Honeypot + Time-Trap:** Völlig barrierefreie Formulare ohne Nutzerinteraktion für Standard-Websites mit stillem Bot-Schutz.
  - **Zusatzfunktion:** Option *„E-Mail-Adresse im Impressum mit aktiver Captcha-Prüfung schützen“* (Anti-Scraping E-Mail-Freischaltung wie in `ownsite`).
- [ ] **16.4 Optionale Besucher-Barrierefreiheitsleiste (Darstellung & Themes):**
  - Neuer Schalter in den Design-/Theme-Einstellungen des Adminbereichs:
    - *„[ ] Besucher-Barrierefreiheitsleiste im Frontend anzeigen“*
  - Wenn aktiviert, wird am oberen Seitenrand eine elegante, dezente Leiste eingeblendet:
    - **Live Hell-/Dunkelmodus (🌙/☀️):** Sofortiges Umschalten zwischen Light & Dark Mode über CSS-Variablen (`data-theme="dark"`) mit lokaler Speicherung im `localStorage`.
    - **Schriftgrößen-Zoom (A- / A+):** Stufenloses Verkleinern / Vergrößern der Basisschriftart (`font-size: 90 % .. 115 %`) für barrierefreies Lesen.
    - **Sprachwechsler:** Nahtlose Integration des WeaveCMS Sprachumschalters in die Barrierefreiheitsleiste.
- [ ] **16.5 Prüfung & Abnahme:**
  - Download-Eintrag mit Version, Screenshot und Changelog anlegen und prüfen, ob Modal im Frontend flüssig öffnet.
  - Klick auf Download durchführen: Datei lädt herunter und Zähler inkrementiert atomar.
  - Captcha-Verfahren im Admin umschalten (Symbol ➔ Mathe ➔ Canvas ➔ Honeypot) und Kontaktformular auf Funktionsfähigkeit prüfen.
  - Besucher-Barrierefreiheitsleiste im Theme-Manager aktivieren: Umschalten von Dark Mode und Schriftzoom im Frontend testen.

---

### Phase 17: Live-Server-Deployment, SSL/HSTS-Härtung & Produktions-Sicherheit
*Ziel: Spezialisierte Server- und Domain-Härtung für den produktiven Produktivbetrieb im Internet (Linux/Apache/Nginx mit echtem SSL-Zertifikat und Reverse-Proxy-Schutz).*

- [ ] **17.1 Strikte HSTS-Policy & SSL-Preloading:**
  - Konfigurierbare Aktivierung von `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` ausschließlich für bestätigte Live-Domains mit aktivem SSL-Zertifikat.
- [ ] **17.2 Production-CSP (Content Security Policy) & Subresource Integrity (SRI):**
  - Schärfung der CSP-Direktiven für Produktivumgebungen gegen Inline-Script-Injektionen und unbefugte Drittquellen.
  - Hash-/Integrity-Prüfung (`integrity="sha384-..."`) für extern bezogene Ressourcen.
- [ ] **17.3 Linux-Dateisystem-Berechtigungen & Permissions-Audit:**
  - Audit-Prüfung der POSIX-Dateirechte im Dashboard (`chmod 0640` für Konfigurationen und `.env`, `chmod 0750` für Systemordner).
  - Warnhinweis im Dashboard bei ungesicherten Rechten auf Linux-Webspaces.
- [ ] **17.4 Reverse-Proxy & Trusted-Proxy IP-Filter:**
  - Schutz vor IP-Spoofing: Nur definierte Upstream-Proxies (z. B. Cloudflare IPs oder lokaler Nginx Reverse-Proxy) dürfen den `X-Forwarded-For` bzw. `CF-Connecting-IP` Header für das Rate-Limiting setzen.
- [ ] **17.5 Automatisierte Admin-E-Mail-Alarmierung:**
  - Benachrichtigung des Systemadministrators bei wiederholten, koordinierten Brute-Force-Angriffen oder unbefugten Manipulationsversuchen im Live-Betrieb.
- [ ] **17.6 Prüfung & Abnahme:**
  - Live-Deployment-Checkliste auf echtem Webspace durchlaufen (SSL-Score A+, Header-Analyse via securityheaders.com).