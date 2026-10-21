# WeaveCMS – Feature-Übersicht & Funktionskatalog

> **Das leichtgewichtige, modulare Flat-File & SQL Content Management System für Handwerksbetriebe, Dienstleister & kreative Köpfe.**  
> *Offizielle Dokumentation für Produkt-Website, Download-Portal und Versions-Updates.*

---

## 🧭 Leitphilosophie: Das WeaveCMS-Versprechen

WeaveCMS wurde entwickelt, um die Lücke zwischen überladenen Standard-CMS (wie WordPress) und starren statischen HTML-Websites zu schließen. Es bietet volle Inhaltskontrolle über eine moderne Admin-Oberfläche, benötigt jedoch **weder Datenbank-Zwang noch Terminal-Befehle** und läuft sofort auf jedem Standard-Webspace.

* **Zero-Bloat:** 100 % natives PHP (ab 8.0) – kein Node.js, kein Webpack, kein Composer-Zwang auf dem Server.
* **Datenschutz First:** Vollständig DSGVO-konform ohne Cookies zu Werbezwecken, ohne externe CDN-Abhängigkeiten oder Tracking-Tracker.
* **Schutz vor Datenverlust:** Integriertes 1-Klick-Sicherungssystem mit PIN-Schutz.

---

## 📦 Funktions-Übersicht nach Kategorien

### 1. Kern-Architektur & Speicher-Engines
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Flat-File JSON-Speicher** | Funktioniert out-of-the-box ohne Datenbankeinrichtung (`data.json`). Ideal für kleine und mittlere Websites. | ✅ Integriert (v0.2.0) |
| **SQL-Speicher-Abstraktion** | Wahlfreiheit zwischen lokaler JSON-Datei und relationaler Datenbank (MySQL / MariaDB / SQLite). | ✅ Integriert (v0.5.0) |
| **1-Klick-Daten-Migration** | Nahtloses Konvertieren und Verschieben aller Inhalte zwischen Flat-File JSON und MySQL/MariaDB direkt im Adminbereich. | ✅ Integriert (v0.7.0) |
| **Atomare Datenspeicherung** | Schutz vor Schreibkonflikten bei gleichzeitigen Bearbeitungen. | ✅ Integriert |
| **Keine Build-Tools erforderlich** | Dateien hochladen und sofort loslegen. Kein `npm install` oder Build-Schritt nötig. | ✅ Integriert |
| **Minimaler Ressourcenbedarf** | Lädt in wenigen Millisekunden, optimal für Shared Hosting und kostengünstige Server. | ✅ Integriert |

---

### 2. Design-, Theme- & Darstellungs-Engine
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Dynamisches Theme-System** | Vollständig tokenbasiert über CSS-Variablen (`:root`). | ✅ Integriert |
| **Kuratierte Farbpaletten** | Vordefinierte Designstile (*Weidenglück*, *Moor & Bernstein*, *Nordisch Klar*, *Warmes Atelier*) plus freier Custom-Color-Picker. | ✅ Integriert |
| **Layout-Maße einstellbar** | Seitenbreite flexibel wählbar: Kompakt (1050 px), Standard (1200 px), Großzügig (1400 px). | ✅ Integriert |
| **Feinjustierung Typografie & Ecken** | Schriftgrößen-Skalierung (95 %, 100 %, 108 %) und Kachel-Eckenabrundung (10 px, 20 px, 28 px). | ✅ Integriert |
| **4 Galerie-Layouts wählbar** | **Smart Ambient-Blur** (weicher Bildhintergrund), **Masonry-Grid** (Pinterest-Stil), **Editorial-Grid** (Magazin-Look), **Classic 4:3** (fester Zuschnitt). | ✅ Integriert |
| **Vollbild-Lightbox** | Reaktionsschnelle Lightbox mit Tastaturnavigation (Pfeiltasten, Esc) und Bildzähler. | ✅ Integriert |
| **Flexibles Logo-System** | Einstellbare Hundetatzen-Motive und freie Platzierung (im Header oder Hero-Bereich). | ✅ Integriert |
| **Erweitertes Logo- & Favicon-Management** | Eigener Upload für SVG-, PNG- und Favicon-Dateien inkl. Höhen-Skalierung. | 🟡 Geplant (Phase 11) |

---

### 3. Inhaltsverwaltung & Sektionstexte
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **100 % Text-Autonomie** | Alle Überschriften, Untertitel und Infotexte aller Sektionen sind direkt im Adminbereich editierbar. | ✅ Integriert (v0.2.0) |
| **Galerie-Manager** | Bilder hochladen, Werkstücke benennen, Beschreibungen anlegen und Kategorien zuweisen. | ✅ Integriert |
| **Dynamische Kategorien** | Beliebige eigene Kategorien anlegen, filtern und löschen. | ✅ Integriert |
| **Manuelle Galerie-Sortierung** | Reihenfolge der Werkstücke numerisch festlegen und sortieren. | ✅ Integriert |
| **Kurs- & Termin-Manager** | Kurse mit Datum, Uhrzeit, Ort, Preis, Teilnehmerbegrenzung und Status (Plätze frei / Ausgebucht) verwalten. | ✅ Integriert |
| **Direkte Kurslinks & Teilen** | 1-Klick-Kopieren von Einladungslinks und systemweites Teilen via Web Share API. | ✅ Integriert |
| **Kursimpressionen / Rückblick** | Upload von Impressionen vergangener Termine und Workshops. | ✅ Integriert |
| **Vollwertiges Blog- & Magazin-Modul** | Zeitbasierte Artikel, Status (Entwurf/Veröffentlicht), Teaser, Markdown, Beitragsbild und Einzelartikel-Ansicht (`?blog=slug`). | ✅ Integriert (v0.8.0) |
| **Freie Einzelseiten (Custom Subpages)** | Beliebig viele Unterseiten (`?page=slug`) mit Markdown, SEO-Metadaten, Menü-Einbindung und vollständiger Mehrsprachigkeit (Sprachreiter für jede aktive Sprache im Drawer-Editor). | ✅ Integriert (v0.8.5) |
| **Individuelle Menü- & Sektionstitel** | Direkte Anpassung des Menü-Titels (`nav_label`), Sektions-Titels und der Menü-Sichtbarkeit für jede Inhaltsart (Schnell-Leiste in jedem Content-Tab sowie zentral im Sektionen-Manager inkl. Mehrsprachigkeit). | ✅ Integriert (v0.8.5) |
| **Subpage Header-Clearance & Navigation** | Optimierter Freiraum für Standalone-Seiten (Blog-Artikel & Einzelseiten) unterhalb des Sticky-Headers sowie sprachbewusster Zurück-Button mit automatischer Sprachübergabe. | ✅ Integriert (v0.8.5) |
| **FAQ / Häufige Fragen** | Akkordeon mit automatischer Google `FAQPage` Schema.org JSON-LD Einbettung. | ✅ Integriert (v0.8.0) |
| **Team & Mitarbeiter** | Profilkarten mit Foto-Upload, Rolle/Spezialisierung, Kurzvita und Mail-Trigger. | ✅ Integriert (v0.8.0) |
| **Download-Manager** | Bereitstellung von PDFs, Flyern und Dokumenten mit Dateigrößen- und Format-Anzeige. | ✅ Integriert (v0.8.0) |
| **Leistungen & Preise** | Preistabellen / Servicekarten mit Bestseller-Badges, Feature-Checklisten und Anfrage-Button. | ✅ Integriert (v0.8.0) |
| **Modulare Sektionen & Seiten-Manager** | Sektionen beliebig anordnen, ein- oder ausblenden mit automatischer Menü-Synchronisation. | ✅ Integriert (v0.6.0) |
| **Inhalte-Hub im Backend** | Übersichtliche Sub-Navigation (`.admin-subnav`) unter „Inhalte & Seiten“ für alle Content-Bereiche. | ✅ Integriert (v0.8.0) |

---

### 4. Rechtssicherheit & DSGVO
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **DSGVO-Datenschutzerklärung** | Integrierter Editor für die Datenschutzerklärung nach Art. 13 DSGVO mit Markdown-Formatierung. | ✅ Integriert (v0.2.0) |
| **Rechtssichere Standardvorlage** | 1-Klick-Vorlage für Server-Logs, Kontaktformular, Speicherdauer, Betroffenenrechte und Cookie-Verzicht. | ✅ Integriert (v0.2.0) |
| **Slide-In-Rechtsmenü** | Nahtlose Umschaltung zwischen **Impressum (§ 5 TMG)** und **Datenschutz (DSGVO)** im Frontend. | ✅ Integriert (v0.2.0) |
| **XSS-sicherer Markdown-Parser** | Sichere Formatierung von Überschriften, Listen und Textauszeichnungen ohne Script-Injections. | ✅ Integriert (v0.2.0) |
| **Keine Werbe- oder Tracking-Cookies** | Vollständig cookie-banner-frei im Frontend, da keine nutzerbezogenen Daten erhoben werden. | ✅ Integriert |

---

### 5. Datensicherung & Wiederherstellung (Backup/Restore)
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **1-Klick-Datensicherung (JSON)** | Sichert alle Website-Texte, Einstellungen und Kurse in einer kompakten `.json`-Datei. | ✅ Integriert (v0.2.0) |
| **1-Klick-Vollarchiv (ZIP)** | Erstellt ein komplettes Archiv aller Daten, hochgeladener Bilder (`images/uploads/`) und Manifests. | ✅ Integriert (v0.2.0) |
| **Geschützter Speicherort** | Serververzeichnis `backups/` mit `.htaccess`-Sperre (`Require all denied` / 403 Forbidden). | ✅ Integriert (v0.2.0) |
| **Sicherheits-Snapshot (Pre-Restore)** | Automatisches Notfall-Backup vor jedem Wiederherstellungsvorgang. | ✅ Integriert (v0.2.0) |
| **PIN-geschützte Wiederherstellung** | Restore erfordert zwingend die Eingabe der Administrator-PIN zum Schutz vor Manipulation. | ✅ Integriert (v0.2.0) |
| **Externe Sicherung hochladen** | Upload und Einspielen von zuvor exportierten `.zip`- oder `.json`-Dateien. | ✅ Integriert (v0.2.0) |
| **Direkter Download & Bereinigung** | Sicherungen direkt aus dem Backend herunterladen oder alte Backups löschen. | ✅ Integriert (v0.2.0) |

---

### 6. Kommunikation, E-Mail & Spamschutz
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Authentifizierter SMTP-Versand** | Zuverlässiger E-Mail-Versand via PHPMailer über eigene Mailserver (TLS/SSL, Ports 587, 465, 25). | ✅ Integriert |
| **Live SMTP-Verbindungstest** | Test-E-Mail direkt aus dem Adminbereich senden, um Postfachdaten sofort zu prüfen. | ✅ Integriert |
| **Mailto-Fallback-Modus** | Wenn SMTP deaktiviert ist, öffnet das Formular das lokale E-Mail-Programm des Besuchers. | ✅ Integriert |
| **Symbol-Spamschutz (Captcha)** | DSGVO-konforme Bildauswahl (4 aus 12 Natur- und Alltagssymbolen) ohne externe Tracker. | ✅ Integriert |
| **Honeypot-Spamfalle** | Unsichtbares Formularfeld fängt automatisierte Spambots geräuschlos ab. | ✅ Integriert |
| **Anpassbare Mailvorlagen** | Persönliche Begrüßung und vorgefertigte Textvorlagen für Kursanfragen. | ✅ Integriert |
| **Integriertes Kontaktpostfach** | Speicherung und Verwaltung von Kundenanfragen direkt im Backend-Postfach. | ✅ Integriert (v0.3.0) |

---

### 7. Sicherheit & Administration
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Zweistufige Authentifizierung** | Login mit sicherem Passwort plus zusätzlichem 4- bis 6-stelligem PIN-Code. | ✅ Integriert |
| **PIN-Schutz für sensible Aktionen** | Passwort-, PIN-Änderungen und Datenwiederherstellungen erfordern die aktuelle PIN. | ✅ Integriert |
| **CSRF-Schutz** | Kryptografische Tokens auf allen POST-Aktionen gegen Cross-Site-Request-Forgery. | ✅ Integriert |
| **Zugriffssperren für Systemdateien** | `data.json`, `config.php`, `.git` und Backups sind vor direktem Browser-Zugriff geschützt. | ✅ Integriert |
| **Sichere Sessions** | Session-Cookies mit `HttpOnly`, `SameSite` und automatischer Session-Zerstörung bei Abmeldung. | ✅ Integriert |
| **Mehrbenutzersystem (RBAC)** | Benutzerrollen: Administrator, Moderator, Redakteur (Content-User). | 🟡 Geplant (Phase 10) |
| **Brute-Force-Schutz & Rate-Limiting** | Progressive Verzögerung und temporäre Sperre nach wiederholten Fehlversuchen. | 🟡 Geplant (Phase 9) |

---

### 8. Installation, Rollout & Updates
| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Geführter Webspace-Installer** | Automatischer Installations-Assistent (`install.php`) mit Server-Check, Seed-Daten und Auto-Locking. | ✅ Integriert (v0.4.0) |
| **SEO- & OpenGraph-Engine** | Eigener Reiter *„SEO & Sharing“*, Meta-Title & Description mit Zeichenzählern, Social-Sharing-Bild-Upload, Roboter-Steuerung und Live SERP/Social-Card-Vorschau. | ✅ Integriert (v0.3.0) |
| **Mehrsprachigkeit & Internationalisierung (i18n)** | 12 europäische Sprachen out-of-the-box (DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI), Sprachwechsler im Frontend, Cookie-Persistenz, Fallback-Kaskade, dynamische SEO hreflang- & OpenGraph-Tags. | ✅ Integriert (v0.8.0) |
| **Automatischer Remote Version-Check** | Erkennung neuer Releases gegen konfigurierbare Update-URL mit Hinweisanzeige. | 🟡 Geplant (Phase 12) |
| **Plugin- & Modulsystem (Erweiterungen)** | Leichtgewichtiges Hook- & Event-System zur Einbindung eigener Module (Themes, Custom Content, Berechtigungen, Webtrees Stammbäume). | 🟡 Geplant (Phase 13) |
| **Universelle Multi-Ratio Demo-Assets** | Kuratiertes Set neutraler Demo-Bilder verschiedener Seitenverhältnisse (3:4, 16:9, 1:1, 4:3) für optimale Galerie-Modi-Tests. | 🟡 Geplant (Phase 14) |
| **Interaktiver Sandbox- & Demo-Modus** | Live-Ausprobieren von WeaveCMS ohne Speicherung (flüchtige Session-Sandbox, Banner & Auto-Reset). | 🟡 Geplant (Phase 15) |

---

## 📈 Release-Historie & Meilensteine

### v0.8.5 (Aktueller Entwicklungsstand)
* **Individuelle Menü- & Sektionstitel für alle Inhaltsarten:**
  * **Modulare Schnell-Leiste (`section_nav_bar.php`):** Direkte Bearbeitung von Titel fürs Menü (`nav_label`), Sektions-Titel (`title`) und Menü-Sichtbarkeit (`in_nav`) direkt im jeweiligen Inhalts-Tab (Galerie, Workshops, Blog, FAQs, Team, Preise, Downloads, Impressionen) ohne Tab-Wechsel.
  * **Zentraler Sektionen-Manager:** Dediziertes Verwaltungsmodal unter *„Texte & Übersetzungen“* (`tab_texts.php`) zur Bearbeitung von Menü-Titeln aller Hauptsektionen inklusive mehrsprachiger Übersetzungen (`translations`) für alle aktiven Sprachen.
  * **Automatischer Tab-Redirect:** Formular-Handling via `target_tab` im `AdminController` leitet Administratoren nach dem Speichern nahtlos zum ursprünglichen Content-Tab zurück.
* **Freie Einzelseiten (Custom Subpages) mit nativer Mehrsprachigkeit:**
  * **Mehrsprachiger Drawer-Editor:** Tab-Leiste für alle im System aktivierten Sprachen (DE, EN, FR, IT, etc.) im Einzelseiten-Editor (`tab_pages.php`).
  * **Vollständige Inhaltsübersetzung:** Getrennte Pflege von Seitentitel, Menü-Bezeichnung (`nav_label`), SEO-Meta-Description und Markdown-Inhalt je Zielsprache.
  * **Dynamische Frontend-Kaskade:** `FrontendController` liest automatisch die passende Sprachversion aus und aktualisiert Seitentitel, Meta-Tags, Menü-Label und Inhalt mit Fallback auf die Hauptsprache.
  * **Sprach-Badges im Backend:** Übersichtliche Kennzeichnung gepflegter Sprachvarianten direkt in den Seitenkarten.
* **Frontend Subpage-Optimierung & Sprachkontinuität:**
  * **Header-Clearance & Sichtbarkeit:** Beseitigung von Überdeckungen durch den Sticky-Header auf Einzelseiten (`?page=slug`) und Blog-Leseansichten (`?blog=slug`) durch optimalen 100px Abstand (`margin-top`).
  * **High-Contrast Zurück-Button:** Modernes, barrierefreies Button-Design mit Hover-Transformation, dezentem Schatten und klarem Icon.
  * **Sprachpersistente Navigation:** Zurück-Links (`btn.back_home`, `btn.back_overview`) und Header-Navigations-Anker behalten den aktiven Sprachparameter (`?lang=xx`) auch bei Subpage-Aufrufen lückenlos bei.
* **Content-Hub, Blog-System & Flexible Menü-Platzierung:**
  * **Backend-Menü Reorganisation (Content-Hub):** Bündelung aller Inhaltsbereiche unter einem zentralen Menüpunkt *„Inhalte & Seiten“* (`.admin-nav`) mit kontextsensitiver Sub-Navigationsleiste (`.admin-subnav`) für blitzschnellen Wechsel zwischen Startseite, Blog, Galerie, Workshops, Impressionen und Impressum.
  * **Vollwertiges Blog- & Magazin-Modul:** Artikel-Management im Admin-Panel (`tab-blog`) mit Beitragsbildern, SEO-Slugs, Markdown-Inhalten, Datumsangaben, Autoren und Veröffentlichungsstatus (`Veröffentlicht` / `Entwurf`).
  * **Frontend Blog-Integration & Leseansicht:** Eigene Blog-Sektion auf der Startseite (über den Sektions-Manager aktivier- und verschiebbar) sowie ansprechende Leseansicht (`?blog=slug`) mit Social-Share-Aktionen (WhatsApp, Facebook, Link kopieren) und optimierten OpenGraph-Metadaten.
  * **Freie Trigger-Platzierung:** Flexible Steuerung der Kontakt- und Impressum-Trigger im Frontend unter *„Darstellung & Design“* (`Nur Header`, `Nur Footer`, `Header & Footer (Beides)`).

### v0.8.0
* **Mehrsprachigkeit & Internationalisierung (Phase 8):**
  * **12 europäische Sprachen out-of-the-box:** Deutsch, Englisch, Französisch, Italienisch, Niederländisch, Spanisch, Portugiesisch, Griechisch, Dänisch, Norwegisch, Schwedisch und Finnisch (West-/Südeuropa & Skandinavien).
  * **Modulare Sprachdateien & i18n-Engine:** Dedizierte `libs/php/LanguageManager.php` mit Cookie-Persistenz (`weave_lang`), GET-Param-Wechsler (`?lang=xx`), Browser-Locale-Erkennung und Fallback-Kaskade (Content-Übersetzung -> Primärsprache -> System-Default).
  * **System-Labels & Section-Translations:** Modulare System-Übersetzungen in `libs/lang/*.json` sowie dynamische Übersetzungsmasken im Admin-Reiter *„Texte“* mit Fallback-Platzhaltern.
  * **Dezenter Frontend-Sprachwechsler & SEO:** Header-Navigation mit kompaktem Sprachschalter (`DE | EN`, etc.) neben den Menü-Triggern und im mobilen Drawer (Footer bleibt unberührt); dynamische Generierung von `<html lang="...">`, OpenGraph Locale und `<link rel="alternate" hreflang="...">` Tags für internationale Suchmaschinen.
  * **Integration im Installations-Assistenten:** Auswahl der Primärsprache und Vorab-Aktivierung von Zusatzsprachen in Schritt 3 von `install.php`.
* **OOP-Refactoring, View-Modularisierung & Systemhärtung:**
  * **Modulare Architektur im Backend:** Reduktion von `admin/index.php` (von ca. 3.370 Zeilen auf 142 Zeilen) durch Aufteilung in wartbare Teilansichten (`admin/views/`) und dedizierte OOP-Klassen (`AdminController`, `AuthManager`, `ImageUploader`, `CaptchaManager`, `LogoRenderer`, `MarkdownParser`).
  * **Modulare Architektur im Frontend:** Reduktion von `index.php` (von ca. 760 Zeilen auf einen sauberen Entry-Point) mit Aufteilung in Partials (`views/partials/`) und Sektions-Templates (`views/sections/`) via `FrontendController`.
  * **Umfassende PHPDoc-Dokumentation:** Vollständige Typ- und Methoden-Dokumentation aller Klassen, Methoden und Template-Variablen.
  * **.htaccess-Systemhärtung:** Erweiterte Zugriffssperren (`403 Forbidden`) für `config.php`, `config.local.php`, `installed.lock`, alle `.json`-, `.sql`-, `.sqlite`-, `.db`-, `.log`-, `.env`- und `.ini`-Dateien sowie die `views/`- und `admin/views/`-Verzeichnisse.
  * **Volle Kompatibilität mit dem Installer:** `install.php` und `Installer.php` bleiben vollständig nahtlos kompatibel mit den neuen Speicher- und View-Strukturen.

### v0.7.0
* **1-Klick-Daten-Migration & Dualer Speicher-Treiber (Phase 7 vollständig):**
  * Integriertes Migrationswerkzeug im Adminbereich unter *„Sicherheit & System“*.
  * Nahtlose Zwei-Wege-Migration: Flat-File JSON (`data.json`) ⇄ MySQL/MariaDB (`weave_documents`).
  * Integrierter Live-Verbindungstest für MySQL via AJAX direkt im Dialog.
  * PIN-geschützte Ausführung zur Vermeidung unbeabsichtigter Umkonfigurationen.
  * Automatischer Sicherheits-Snapshot in `backups/` vor jeder Migration.
  * Dynamische Status-Badge-Anzeige (`Aktiv & Bereit` vs. `Notfall-Fallback`).

### v0.6.0
* **Modulare Sektionen & Universeller Seiten-Manager (Phase 6):**
  * Datenmodell für flexible Sektionen (`sections` in `data.json`) mit `id`, `type`, `title`, `nav_label`, `visible` und `sort_order`.
  * `SectionManager.php` zur automatischen Normalisierung, Sichtbarkeitsprüfung und Bereitstellung dynamischer Navigationspunkte.
  * Administrations-UI im Reiter *„Startseite & Über mich“* mit Reihenfolge-Verschiebung (*Nach oben* / *Nach unten*) und Sichtbarkeitsumschaltung (*Sichtbar* / *Versteckt*) per AJAX und Toast-Feedback.
  * Dynamisches Rendering im Frontend ([index.php](file:///c:/xampp/htdocs/WeaveCMS/index.php)) in konfigurierter Reihenfolge inklusive getrennter `past_courses`-Sektion.
  * Automatische Synchronisation der Menüeinträge in Desktop- und Mobil-Navigation.

### v0.5.0
* **Dualer Speicher-Treiber (Phase 7 vorbereitet):**
  * Data Access Layer mit `StorageDriverInterface`, atomarem `JsonStorageDriver` (Schreibschutz via `.tmp` und Rename), transaktionssicherem `PdoSqlStorageDriver` (MySQL/MariaDB via PDO) und `StorageManager` Factory mit automatischem Notfall-Fallback auf JSON.
  * Transparente Weiternutzung von `load_data()` und `save_data()` in [config.php](file:///c:/xampp/htdocs/WeaveCMS/config.php).
  * Geschützte Umgebungskonfiguration für Datenbankdaten in [config.local.php](file:///c:/xampp/htdocs/WeaveCMS/config.local.php).
* **Erweiterter Webspace-Installer (5 Schritte):**
  * Neuer Schritt 2: Intuitive Wahl zwischen **Flat-File JSON (`data.json`)** und **MySQL / MariaDB**.
  * Konfigurations-Drawer für MySQL mit Live-Verbindungstest via AJAX (`install.php?action=test_db`) und optionaler automatischer Datenbankanlage (`CREATE DATABASE IF NOT EXISTS`).
* **Universelle Workshop-Benennung:** Bereinigung veralteter themenspezifischer Bezeichnungen auf *„Kurse & Workshops“* im Frontend, Backend und in den Datenvorlagen.

### v0.4.0
* **Webspace-Installer & Setup-Assistent (Phase 5):**
  * Geführter 4-Schritte-Assistent in [install.php](file:///c:/xampp/htdocs/WeaveCMS/install.php) mit modernem Step-Progress-Design.
  * Schritt 1 (Health-Check): Automatische Prüfung von PHP-Version (>= 8.0), Schreibrechten für `data/`, `images/uploads/`, `backups/` sowie PHP-Modulen (`pdo`, `pdo_mysql`, `pdo_sqlite`, `gd`, `mbstring`, `json`, `curl`).
  * Schritt 2 & 3: Erfassung von Website-Grunddaten, Inhaber, E-Mail sowie 2-Stufen-Admin-Zugangsdaten (Bcrypt-Passwort und PIN).
  * Schritt 4: Beispieldaten-Seed (aus [data.sample.json](file:///c:/xampp/htdocs/WeaveCMS/data.sample.json)) und Farbschema-Vorauswahl.
  * Automatische Sperre via [installed.lock](file:///c:/xampp/htdocs/WeaveCMS/installed.lock) und Umleitungslogik bei Erstaufruf.
  * Einmaliges interaktives Willkommens-Modal im Adminbereich mit Funktionsüberblick.

### v0.3.0
* **SEO & Social-Sharing (Phase 3):** Eigener Administrations-Reiter *„SEO & Sharing“* mit Echtzeit-Vorschau (Google SERP Snippet & WhatsApp/Facebook Card), Zeichenzählern (60 / 155 Zeichen), Roboter-Steuerung, Canonical URL und Sharing-Bild Upload.
* **Dynamische Head-Generierung:** Vollständige Ausgabe von Title, Description, Keywords, Robots, Canonical, OpenGraph (`og:*`) und Twitter Cards (`twitter:*`) im Frontend.
* **Kontaktanfragen-Postfach (Phase 4):** Lokale Protokollierung von Formularanfragen in `data/contacts.json`, Backend-Reiter *„Kontaktanfragen“* mit Zähler-Badge, Status-Management und Schnellaktionen.

### v0.2.0
* **Datensicherung & Restore (Phase 2):** Integriertes Backup-System mit 1-Klick JSON/ZIP-Export, PIN-gesichertem Restore und Verzeichnisschutz.
* **DSGVO & Rechtstexte (Phase 1):** Vollständige Verwaltung der Datenschutzerklärung im Backend mit rechtssicherer Standardvorlage und Markdown-Rendering im Frontend.
* **Sektionstexte (Phase 1):** 100 % dynamische Titel, Untertitel und Einleitungstexte für alle Hauptsektionen (Hero, Galerie, Kurse, Impressionen, Kontakt).

### v0.1.0-alpha
* **Design-Engine:** Tokenbasiertes Theme-System mit kuratierten Farbpaletten, flexibler Galerie (Ambient-Blur, Masonry, Editorial, Classic) und Lightbox.
* **Hybrid-Kommunikation:** SMTP-Versand via PHPMailer, Mailto-Fallback und barrierefreier Symbol-Spamschutz.
* **Inhaltsmodule:** Kurse, Impressionen, Galerie-Kategorien und responsive Navigation.
