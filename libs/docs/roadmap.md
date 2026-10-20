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

## 🚀 Arbeitsablauf: Der 10-Phasen-Entwicklungsplan

```
[Phase 1: Recht & Texte] ──▶ [Phase 2: Datensicherheit] ──▶ [Phase 3: SEO & Sharing] ──▶ [Phase 4: Anfragen-Log]
  (DSGVO & 4 Sektionen)        (1-Klick Backup/Restore)       (Meta-Tags & OpenGraph)      (Lokales Postfach)
         │
         ▼
[Phase 5: Installer & Seed] ──▶ [Phase 6: Seiten & Typen] ──▶ [Phase 7: DB vs. JSON] ──▶ [Phase 8: Mehrsprachigkeit]
  (Setup-Wizard & Rollout)       (Reihenfolge & Builder)       (Dualer Storage-Treiber)      (i18n & Sprachumschalter)
         │
         ▼
[Phase 9: Security Hardening] ──▶ [Phase 10: Multi-User & RBAC]
  (Schwachstellen & Schutz)          (Admin / Moderator / Content)
```

---

### Phase 1: Rechtliche Vollständigkeit & Universelle Sektionstexte
*Ziel: 100 % Text-Autonomie über das CMS & rechtssichere DSGVO-Datenschutzerklärung.*

- [ ] **1.1 DSGVO-Datenschutzerklärung im Backend verwalten:**
  - Neuer Unterbereich im Adminbereich (z. B. unter *„Rechtliches & Impressum“*).
  - Mehrzeiliges Markdown-/HTML-Textfeld für die Datenschutzerklärung.
  - Speicherung in `data.json` unter `$data['settings']['privacy_policy_text']`.
  - Bereitstellung einer rechtssicheren, abmahnsicheren DSGVO-Standardvorlage (Server-Logs, Hosting, E-Mail-Kontakt, Speicherdauer, Betroffenenrechte, keine Tracking-Cookies).
  - Dynamisches Ausspielen im Datenschutz-Modal/Slide-In im Frontend.
- [ ] **1.2 Dynamische Untertitel & Einleitungstexte für Hauptsektionen:**
  - Konfigurierbare Überschriften und Fließtexte für alle Inhaltsblöcke:
    - Galerie/Portfolio: Titel, Untertitel und Beschreibungstext.
    - Kurse/Workshops/Events: Infotext zur Anmeldung, Stornierung oder Teilnahme.
    - Rückblick/Impressionen: Beschreibungstext.
    - Kontaktbereich: Einleitungstext über dem Formular.
- [ ] **1.3 Prüfung & Abnahme:**
  - Texte im Backend ändern und sofortige Aktualisierung im Frontend verifizieren.
  - Datenschutzerklärung im Frontend öffnen und vollständige Anzeige prüfen.

---

### Phase 2: Integriertes Datensicherungs- & Wiederherstellungssystem (Backup/Restore)
*Ziel: Schutz vor Datenverlust durch 1-Klick-Sicherungen und vollständige Wiederherstellbarkeit.*

- [ ] **2.1 Backend-Verwaltung für Backups:**
  - Eigener Bereich im Reiter *„Sicherheit & System“*.
  - Tabelle aller vorhandenen Backups mit Zeitstempel, Dateigröße und Typ.
- [ ] **2.2 Backup-Funktionalität:**
  - **1-Klick-Backup erstellen:**
    - Option A: *Nur Inhaltsdaten & Konfiguration* (`data.json` / SQL-Dump).
    - Option B: *Vollständiges Archiv* (Daten + `images/uploads/` als ZIP-Archiv).
  - Sicherer Speicherort im geschützten Verzeichnis `backups/` mit Zugriffsschutz (`.htaccess: deny from all`).
  - Direkter ZIP-/JSON-Download über das Backend.
- [ ] **2.3 Restore-Funktionalität:**
  - **Wiederherstellung aus vorhandenem Backup:** Auswahl eines gelisteten Backups mit Sicherheitsabfrage (PIN-bestätigt).
  - **Backup-Upload:** Hochladen einer zuvor exportierten ZIP-/JSON-Datei zur Wiederherstellung.
- [ ] **2.4 Prüfung & Abnahme:**
  - Backup erstellen, Daten manipulieren, Restore ausführen und Intaktheit verifizieren.

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
