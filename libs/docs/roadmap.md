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

## 🚀 Arbeitsablauf: Der 12-Phasen-Entwicklungsplan

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

- [ ] **3.1 Globales Meta- & Social-Sharing im Adminbereich:**
  - Einstellungsfelder unter *„SEO & Metadaten“*:
    - Meta Title (Seitentitel mit Vorschau auf 60-Zeichen-Länge).
    - Meta Description (Beschreibung mit Vorschau auf 155-Zeichen-Länge).
    - Meta Keywords (kommagetrennt).
    - OpenGraph Sharing Image (Bild-Upload für Facebook, WhatsApp, LinkedIn, X/Twitter).
    - Canonical URL & Roboter-Steuerung (`index, follow`).
- [ ] **3.2 Dynamische Head-Generierung im Frontend (`index.php`):**
  - Saubere Ausgabe aller Standard-Tags und OpenGraph-Tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`).
  - Twitter Card Tags (`twitter:card`, `twitter:title`, `twitter:image`).
- [ ] **3.3 Prüfung & Abnahme:**
  - Quelltextprüfung der Meta-Tags und Validierung mit Sharing-Debuggern (z. B. OpenGraph.xyz).

---

### Phase 4: Integriertes Anfragen- & Kontaktpostfach
*Ziel: Zentrale Erfassung und Verwaltung von Kundenanfragen direkt im CMS-Backend.*

- [ ] **4.1 Lokale Protokollierung von Kontaktanfragen:**
  - Beim Absenden des Kontaktformulars wird die Anfrage unabhängig vom Mailversand strukturiert in `data/contacts.json` (oder SQL-Tabelle `fb_contacts`) archiviert.
  - Gespeicherte Felder: Datum, Uhrzeit, Name, E-Mail, Telefon, Nachricht, Kurs-Referenz (falls vorausgewählt), Status (`neu`, `in_bearbeitung`, `erledigt`).
- [ ] **4.2 Postfach-Reiter im Adminbereich:**
  - Neuer Reiter *„Kontaktanfragen“* mit Kennzeichnung neuer/ungelesener Nachrichten.
  - Tabellenansicht mit Schnellansicht, Volltext-Modal und Status-Umschaltung.
  - Schnellaktionen: *„Als E-Mail antworten“* (`mailto:`-Link mit vorausgefülltem Betreff) und *„Löschen“*.
- [ ] **4.3 Prüfung & Abnahme:**
  - Testanfrage über das Frontend absenden und prüfen, dass sie sofort im Postfach auftaucht.

---

### Phase 5: Webspace-Installer & Setup-Assistent (`install.php`)
*Ziel: Vollautomatischer Installations-Assistent für das schnelle Ausrollen auf beliebigen Webspaces.*

- [ ] **5.1 Installations-Erkennung & Sperre:**
  - Befindet sich noch keine `installed.lock` auf dem Server, leitet das CMS automatisch auf `install.php` um.
  - Nach erfolgreichem Setup wird `installed.lock` erzeugt; `install.php` ist danach gesperrt.
- [ ] **5.2 Installationsschritte:**
  - **Schritt 1: System-Check (Health Check):**
    - PHP-Version (>= 8.0), Dateirechte (Schreibrechte für `data/`, `images/uploads/`, `backups/`), PHP-Module (`pdo`, `gd`, `mbstring`, `json`, `curl`).
  - **Schritt 2: Website-Grunddaten:**
    - Website-Name, Slogan, Inhaber/Betreiber, E-Mail-Adresse.
  - **Schritt 3: Administrator-Konto:**
    - Admin-Benutzername, Administrator-Passwort, Sicherheits-PIN.
  - **Schritt 4: Beispieldaten (Seed Content):**
    - Checkbox: *„Muster-Inhalte installieren (Empfohlen für neue Websites)“*.
- [ ] **5.3 Prüfung & Abnahme:**
  - Testinstallation auf frischem Server durchführen und automatische Weiterleitung ins Admin-Dashboard verifizieren.

---

### Phase 6: Modulare Sektionen & Universeller Seiten-Manager
*Ziel: Absolute Freiheit beim Zusammenstellen, Sortieren und Ein-/Ausblenden von Inhaltsbereichen.*

- [ ] **6.1 Sektions-Datenmodell (`sections` in `data.json`):**
  - Jede Sektion besitzt eine ID, einen Typ, Titel, Menü-Bezeichnung, Sichtbarkeit (`visible: true/false`) und Position.
  - Unterstützte Sektions-Typen:
    - `hero`: Header-Banner mit Überschrift, Call-to-Action und Hintergrundbild.
    - `gallery`: Raster-Portfolio mit Kategorien und Vollbild-Lightbox.
    - `courses`: Termine / Veranstaltungen / Leistungsangebote mit Buchungs-Trigger.
    - `past_courses`: Rückblick / Impressionen / Erfolge.
    - `about`: Über mich / Unternehmensprofil mit Portrait und Biografie.
    - `custom_text`: Freier Textbereich (Markdown / HTML) für individuelle Inhalte.
- [ ] **6.2 Sektions-Manager im Adminbereich:**
  - Visuelle Übersicht aller Sektionen der Website.
  - Verschieben der Reihenfolge via *„Nach oben“* / *„Nach unten“* oder Drag & Drop.
  - Sofortiger Ein-/Aus-Schalter zur Steuerung der Sichtbarkeit.
  - Neue Sektionen anlegen und benennen.
- [ ] **6.3 Dynamisches Rendering im Frontend (`index.php`):**
  - Rendert die Sektionen flexibel anhand der konfigurierten Reihenfolge.
  - Automatische Synchronisation der Navigationsmenüs (Desktop & Mobil) mit den sichtbaren Sektionen.
- [ ] **6.4 Prüfung & Abnahme:**
  - Reihenfolge im Backend verändern und synchrone Anpassung im Frontend verifizieren.
  - Einzelne Sektion verbergen und Verschwinden im Menü und Layout testen.

---

### Phase 7: Dualer Speicher-Treiber (Datenbankauswahl vs. Lokale JSON-Datei)
*Ziel: Wahlfreiheit zwischen einer SQL-Datenbank (MySQL / MariaDB / SQLite) und einer lokalen JSON-Datei.*

- [ ] **7.1 Speicher-Abstraktionsschicht (Data Access Layer - DAL):**
  - Universelles PHP-Interface: `loadData(): array`, `saveData(array $data): bool`, `ping(): bool`.
  - **Treiber A: `JsonStorageDriver` (Standard & Fallback):**
    - Liest und schreibt atomar in `data.json`. Funktioniert ohne Datenbank auf jedem Webspace.
  - **Treiber B: `PdoSqlStorageDriver` (MySQL / MariaDB):**
    - Speichert Entitäten in relationalen Tabellen mit konfigurierbarem Präfix (`fb_settings`, `fb_texts`, `fb_gallery`, `fb_courses`, `fb_sections`).
  - **Treiber C: `SqliteStorageDriver` (Lokale SQL-Datei):**
    - Dateibasierte SQL-Datenbank (`data/app.sqlite`), vereint SQL-Features mit Datei-Einfachheit.
- [ ] **7.2 Auswahl im Installationsassistenten (`install.php`):**
  - Wählbare Speicher-Engine mit Live-Verbindungstest.
  - Automatischer Notfall-Fallback auf die lokale JSON-Datei bei Datenbankproblemen.
- [ ] **7.3 1-Klick-Daten-Migration im Adminbereich:**
  - Assistent zum Übertragen bestehender Daten von JSON nach SQL oder umgekehrt.
- [ ] **7.4 Prüfung & Abnahme:**
  - Test mit JSON-Treiber und MySQL/SQLite-Treiber durchführen.

---

### Phase 8: Mehrsprachigkeit (i18n & Sprachumschalter)
*Ziel: Mehrsprachiger Webauftritt mit Sprachauswahl bei der Installation und optionaler Aktivierung weiterer Sprachen.*

- [ ] **8.1 Sprachauswahl bei der Erstinstallation:**
  - Auswahl der Hauptsprache (Deutsch, Englisch, Französisch, etc.).
  - Standardtexte und Admin-Oberfläche starten in der gewählten Primärsprache.
- [ ] **8.2 Sprach-Architektur & Datenmodell:**
  - System-Labels (Buttons, Formulartexte) in modularen Sprachdateien (`libs/lang/de.json`, `libs/lang/en.json`).
  - Inhalts-Texte sprachbezogen gespeichert mit intelligenter Fallback-Kaskade (keine Lücken bei fehlender Übersetzung).
- [ ] **8.3 Sprachverwaltung im Adminbereich:**
  - Aktivieren/Deaktivieren zusätzlicher Fremdsprachen per Mausklick.
  - Übersichtliche Side-by-Side-Übersetzungsmaske.
- [ ] **8.4 Frontend-Sprachwechsler & Internationales SEO:**
  - Dezenter Sprachschalter im Header/Footer (`DE | EN`).
  - Korrekte `<html lang="...">` und `<link rel="alternate" hreflang="...">` Tags für Google.
- [ ] **8.5 Prüfung & Abnahme:**
  - Mehrsprachigen Betrieb einrichten und Frontend-Umschaltung testen.

---

### Phase 9: Umfassende Sicherheits-Härtung & Schwachstellen-Prävention
*Ziel: Absicherung gegen gängige Web-Angriffe (OWASP Top 10) und Schutz vor automatisierten Exploits.*

- [ ] **9.1 HTTP Security Header:**
  - Auslieferung moderner Header: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy` und `HSTS`.
- [ ] **9.2 Brute-Force-Schutz & Rate-Limiting:**
  - Login & PIN: Nach 5 Fehlversuchen progressive Zeitverzögerung und 15-minütige Sperre.
  - Kontaktformular: Rate-Limiting gegen Spambots und Flooding.
- [ ] **9.3 Dateisicherheit & Verzeichnis-Abschirmung:**
  - Direkter Browser-Aufruf von `data.json`, `contacts.json`, `config.php`, `.git/`, Backups und Logs via `.htaccess` strikt blockiert (`403 Forbidden`).
  - Upload-Audit: MIME-Type Magic-Byte-Prüfung (`finfo_file`), Neukompilierung via GD (EXIF-PHP-Bereinigung), kryptografische Zufallsnamen, Ausführungsverbot für Skripte im Upload-Ordner.
- [ ] **9.4 XSS-, CSRF- & Injection-Prävention:**
  - Durchgängiges `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  - Konsequenter CSRF-Token-Schutz für alle POST-Aktionen.
  - Prepared Statements bei allen Datenbanktreibern.
- [ ] **9.5 Session-Härtung & Audit-Log:**
  - Cookies mit `HttpOnly`, `Secure` und `SameSite=Strict`. Session-ID-Regeneration bei Login, Inaktivitäts-Timeout und internes Sicherheits-Log (`logs/security.log`).
- [ ] **9.6 Prüfung & Abnahme:**
  - Sicherheitsaudit durchführen und Schutzmechanismen validieren.

---

### Phase 10: Mehrbenutzersystem & Rollenbasierte Rechteverwaltung (RBAC)
*Ziel: Mehrere Benutzerkonten mit abgestuften Rechten (Administrator, Moderator, Content-User).*

- [ ] **10.1 Rollenmodell & Berechtigungsmatrix:**
  - **Administrator (Superadmin):** Vollzugriff auf alle Bereiche (System, Sicherheit, Benutzer, Backups, SMTP, Design, Speicher).
  - **Moderator:** Redaktionelle Leitung (Inhalte, Galerie, Termine, Impressionen, Postfach). Kein Zugriff auf System & Sicherheit.
  - **Content-User (Redakteur):** Reine Inhaltspflege (Bilder hochladen, Termine eintragen). Kein Zugriff auf Struktur, Design oder System.
- [ ] **10.2 Datenmodell für Benutzer (`users` in `data.json` bzw. DB-Tabelle `fb_users`):**
  - Eindeutige ID, Benutzername, E-Mail, Bcrypt-Passworthash, Rolle, Aktiv-Status, Timestamps.
- [ ] **10.3 Backend-Benutzerverwaltung (nur für Administratoren):**
  - Übersichtstabelle aller Benutzer.
  - Neuanlage mit Rollenvergabe (*keine öffentliche Registrierung im Frontend!*).
  - Benutzer sperren/bearbeiten und Passwörter PIN-geschützt zurücksetzen.
  - Eigenes Profil für jeden angemeldeten Benutzer zur Passwortänderung.
- [ ] **10.4 Serverseitiges Berechtigungs-Enforcement:**
  - Jede Backend-Aktion validiert die Rolle strikt auf Serverebene (`require_role(...)`).
  - Backend-Navigation rendert nur erlaubte Reiter für den jeweiligen Benutzer.
- [ ] **10.5 Prüfung & Abnahme:**
  - Benutzer für alle 3 Rollen anlegen und Berechtigungsgrenzen testen.

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