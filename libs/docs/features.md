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
| **Flexibles Logo-System** | Einstellbare Hundetatzen-Motive und freie Platzierung (im Header oder Hero-Bereich). | ✅ Integriert |
| **Erweitertes Logo- & Favicon-Management** | Eigener Upload für SVG-, PNG-, WebP- und Favicon-Dateien (.ico, .svg, .png), stufenlose Höhenskalierung (24–100 px), Side-by-Side Live-Vorschau (Hell/Dunkel), XSS-Sanitizing für SVGs und dynamische Favicon- & Touch-Icon-Einbindung. | ✅ Integriert (Phase 11) |
| **Mobile Responsiveness & WCAG-Audit** | WCAG 2.5.5 / 2.5.8 Touch-Targets (>= 44 × 44 px) für Navigation, Close-Trigger und Lightbox; iOS Safari Auto-Zoom-Prävention (16px Input-Schutz) und `overflow-x: clip` Schutz für 360–414 px Displays. | ✅ Integriert (Phase 11) |
| **3 Menüstile & Position-Selector (Phase 19)** | Wählbar unter *Darstellung & Design*: `Waagerecht Minimal` (Standard), `Waagerecht mit Icons` und `Senkrecht / Sidebar` (wahlweise links oder rechts fixiert mit responsiver Mobilansicht <= 992px). | ✅ Integriert (v0.9.7) |
| **Icon- & Symbol-Zuweisung für Menüpunkte (Phase 19)** | Automatisches Fallback auf Standard-Material-Icons (`photo_library`, `event`, `newspaper`, etc.) sowie individuelles Icon-Feld im Sektionen- und Seiteneditor. | ✅ Integriert (v0.9.7) |
| **Plugin-Theme- & Navigations-Hooks (Phase 19)** | Filter-Hooks `theme_register_menu_types` und `theme_register_layouts` sowie Action-Hook `frontend_render_navigation` zum Überschreiben der Navigation durch Erweiterungen. | ✅ Integriert (v0.9.7) |
| **In-Navbar Barrierefreiheit (Phase 20)** | Nahtlose Integration von Dark/Light-Icon (🌙/☀️ mit Tooltip) und Schriftgrößen-Zoom (A-/A/A+) direkt in die Hauptnavigation & den Mobil-Drawer ohne doppelte Sprachauswahl oder störende Top-Bar. | ✅ Integriert (v1.0.0) |

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
| **Globaler Architektur-Wahlschalter (Phase 18)** | Umschalten zwischen **One-Pager** (Standard), **Klassischer Multi-Page Website** (Startseite + separate Unterseiten `?page=slug` für Galerie, Kurse, Shop, Blog, Downloads, Stammbaum etc.) und **Hybrid-Modus** (freie Zuweisung je Sektion: Startseite / Einzelseite / Beides). | ✅ Integriert (Phase 18) |
| **Modulare Sektions-Landingpages (Landingpage-Builder)** | Erstellung unbegrenzter zusätzlicher Endlos-Scroll-Seiten mit frei wählbaren, gestackten Inhalts-Sektionen, eigenem Intro, individuellem URL-Slug und SEO-Metadaten. | ✅ Integriert (Phase 18) |
| **Intelligente Routing- & Menü-Engine** | Vollautomatische Navigation: Anker-Links (`#sektion`) für Sektionen der aktuellen Seite, direkte Seitenlinks (`?page=slug`) für ausgelagerte Einzelseiten, sprachbewusster Logo-Home-Link. | ✅ Integriert (Phase 18) |
| **Webtrees Stammbaum-Sektion im Sektionen-Manager** | Nahtlose Steuerung der Stammbaum-Sektion über den zentralen Sektionen-Manager (Verschieben, Ein-/Ausblenden und automatische Menü-Synchronisation). | ✅ Integriert (v0.9.5) |
| **Inhalte-Hub im Backend** | Übersichtliche Sub-Navigation (`.admin-subnav`) unter „Inhalte & Seiten“ für alle Content-Bereiche. | ✅ Integriert (v0.8.0) |
| **Modul-Pool & Seiten-Aufbau Board (Phase 20)** | Interaktives 2-Zonen Drag & Drop Board (Modul-Pool für inaktive Kategorien vs. Aktive Sektionen) zur intuitiven Strukturierung der Website-Architektur. | ✅ Integriert (v1.0.0) |
| **Dynamische Inhalts-Subnavigation (Phase 20)** | Backend-Subreiter unter „Inhalte & Seiten“ werden dynamisch gefiltert und erscheinen nur bei aktiven Kategorien. | ✅ Integriert (v1.0.0) |
| **Responsive Touch-Switcher für Board (Phase 20)** | Optimierte Mobile- & Tablet-Bedienung (<= 900px) mit Schnell-Umschaltung (`view-active` / `view-pool`) und 1-Tap-Aktionen (+ Aktivieren, Verschieben, Settings). | ✅ Integriert (v1.0.0) |
| **Content-Revisions & Versionierung (Phase 23)** | Automatisches Anlegen von Revisionsständen bei jeder Speicherung (max. 25 Stände je Element in geschütztem `data/revisions/`). | ✅ Integriert (Phase 23) |
| **Zeilenweiser Diff-Viewer & 1-Klick-Rollback (Phase 23)** | Visuelles Modal mit farblicher Hervorhebung hinzugefügter (`+`) und entfernter (`-`) Textzeilen sowie sichere Wiederherstellung früherer Stände inkl. Notfall-Snapshot. | ✅ Integriert (Phase 23) |
| **Status-Workflow: Draft, Published & Scheduled (Phase 23)** | Dreistufiges Status-Management (`published`, `draft`, `scheduled`) für Freie Einzelseiten und Blog-Artikel mit Datum/Uhrzeit-Steuerung (`publish_at`). | ✅ Integriert (Phase 23) |
| **Sicherer Vorschau-Modus & Preview-Tokens (Phase 23)** | Kryptographisch zufällige 32-Zeichen-Tokens (`?preview=...`) für Entwürfe und zeitgesteuerte Inhalte mit sticky Frontend-Hinweisbanner. | ✅ Integriert (Phase 23) |
| **Zentrale Medienverwaltung & Asset-Manager (Phase 22)** | Neuer Hauptreiter *„Mediathek“* mit Kachel- und Listenansicht, Kennzahlen, Typ-Filterung (Bilder, Dokumente, Vektoren), Suche und Sortierung. | ✅ Integriert (v1.3.0) |
| **Automatische WebP- & AVIF-Bildpipeline (Phase 22)** | Vollautomatische Konvertierung aller hochgeladenen Bilder via PHP `gd` in komprimierte WebP- und AVIF-Formate sowie Generierung responsiver Größen (`thumb`: 350px, `medium`: 800px, `large`: 1400px). | ✅ Integriert (v1.3.0) |
| **HTML5 &lt;picture&gt;- &amp; srcset-Helfer (Phase 22)** | Automatische Bereitstellung von performantem `<picture>`-Markup (`MediaManager::renderPictureTag`) mit modernstem Format-Fallback für maximale Ladezeiten. | ✅ Integriert (v1.3.0) |
| **Multi-Upload &amp; 7Z-Archiv-Unterstützung (Phase 22)** | Drag &amp; Drop Mehrfach-Upload mit Fortschrittsbalken und automatischer Dateinamens-Bereinigung. Unterstützung für JPG, PNG, WebP, GIF, SVG, PDF, ZIP, 7Z (proprietäre Office-Formate zugunsten von sauberem PDF-Export ausgeschlossen). | ✅ Integriert (v1.3.0) |

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
| **Mehrbenutzersystem (RBAC)** | Benutzerrollen: Administrator, Moderator, Redakteur (Content-User) mit granularen Rechten. | ✅ Integriert (Phase 10) |
| **Brute-Force-Schutz & Rate-Limiting** | Progressive Verzögerung und temporäre IP-Sperre nach wiederholten Fehlversuchen. | ✅ Integriert (Phase 9) |
| **Audit- & Security-Logging** | Manipulationssicheres Protokollieren aller sicherheitsrelevanter Aktionen in `data/security.log`. | ✅ Integriert (Phase 9) |
| **Strikte HSTS-Policy & SSL-Preload** | Erzwingt verschlüsselte HTTPS-Verbindungen mit optionalem Preload-Header für moderne Browser. | ✅ Integriert (Phase 17.1) |
| **Production-CSP & SRI** | Gehärtete Content Security Policy gegen XSS und Subresource Integrity Hash-Prüfung (`renderSriTag`). | ✅ Integriert (Phase 17.2) |
| **Dateisystem-Audit (POSIX/Linux)** | Automatischer Check der Berechtigungen (`chmod 0640/0750`) im Admin-Sicherheits-Tab und Dashboard. | ✅ Integriert (Phase 17.3) |
| **Reverse-Proxy Anti-Spoofing** | Striktes IP-Filtering für Upstream-Proxies & Cloudflare (`X-Forwarded-For` Schutz). | ✅ Integriert (Phase 17.4) |
| **Admin-E-Mail-Alarmierung** | Automatische Alarm-E-Mails bei Brute-Force-Lockouts mit Cooldown-Schutz gegen E-Mail-Fluten. | ✅ Integriert (Phase 17.5) |
| **Live-Deployment Checkliste** | Integrierte Status-Matrix und Direkt-Links zu SecurityHeaders.com und Qualys SSL Labs. | ✅ Integriert (Phase 17.6) |

---

### 8. Installation, Rollout & Updates

| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **Erweiterter Webspace-Installer & Pre-Flight Check** | Automatischer Installations-Assistent (`install.php`) mit 5-teiliger Vorab-Systemprüfung (PHP-Umgebung, Dateirechte, PHP-Module, Webserver & URL-Rewrite, php.ini Empfehlungen) und Auto-Locking. | ✅ Integriert (v0.9.5) |
| **Datenbank-Preflight & Rechte-Verifikation** | Umfassender Vorab-Test für MySQL/MariaDB mit Validierung der Benutzerrechte (`CREATE TABLE`, `INSERT`, `SELECT`, `DROP`), Auto-Create und erzwungener Verifikation vor Schritt 3. | ✅ Integriert (v0.9.5) |
| **SEO- & OpenGraph-Engine** | Eigener Reiter *„SEO & Sharing“*, Meta-Title & Description mit Zeichenzählern, Social-Sharing-Bild-Upload, Roboter-Steuerung und Live SERP/Social-Card-Vorschau. | ✅ Integriert (v0.3.0) |
| **Mehrsprachigkeit & Internationalisierung (i18n)** | 12 europäische Sprachen out-of-the-box (DE, EN, FR, IT, NL, ES, PT, EL, DA, NO, SV, FI), Sprachwechsler im Frontend, Cookie-Persistenz, Fallback-Kaskade, dynamische SEO hreflang- & OpenGraph-Tags. | ✅ Integriert (v0.8.0) |
| **Automatischer Remote Version-Check** | Erkennung neuer Releases gegen konfigurierbare Update-URL mit Hinweisanzeige im Dashboard. | ✅ Integriert (Phase 12) |
| **Modulares Plugin-System** | Vollwertiges Hook- & Event-System (`PluginManager`) für Erweiterungen im Ordner `/plugins` mit automatischer Entdeckung, Lebenszyklus und Admin-Einstellungen. | ✅ Integriert (Phase 13) |
| **Universelle Multi-Ratio Demo-Assets** | Kuratiertes Set neutraler Demo-Bilder verschiedener Seitenverhältnisse (3:4, 16:9, 1:1, 4:3) für optimale Theme-Tests. | ✅ Integriert (Phase 14) |
| **Interaktiver Sandbox- & Demo-Modus** | Live-Ausprobieren von WeaveCMS ohne Speicherung (flüchtige Session-Sandbox, Banner & Auto-Reset). | ✅ Integriert (Phase 15) |
| **Modularer Download- & Software-Bereich** | Vollwertige Download-Karten mit Versions-Badges, OS/Plattform-Tags, Changelog-Drawer, Screenshot-Lightbox und manipulationsgeschütztem Klick-Counter mit Spambot-Filter. | ✅ Integriert (Phase 16) |
| **Modulare Captcha-Auswahl-Engine** | Freie Wahl im Sicherheits-Tab zwischen Symbol-Captcha, Mathe-Rechen-Captcha, HTML5 Canvas-Zahlen-Captcha und unsichtbarem Honeypot sowie Anti-Scraping E-Mail-Schutz im Impressum. | ✅ Integriert (Phase 16) |
| **Besucher-Barrierefreiheitsleiste** | Optionale Leiste am oberen Bildschirmrand mit Live Dark/Light-Mode (🌙/☀️), Schriftgrößen-Zoom (85 %–125 %) und Sprachwechsler. | ✅ Integriert (Phase 16) |

---

### 9. E-Commerce, Shop & Erweiterungen (Plugins)

| Feature | Beschreibung | Status |
| :--- | :--- | :---: |
| **WeaveCMS Shop-Plugin (`shop`)** | Vollwertiges E-Commerce-System mit Produktkatalog, digitalem Warenkorb-Drawer (`#shop-cart-drawer`), Floating-Cart-Badge und Bestandsprüfung. | ✅ Integriert (v0.9.0) |
| **Lager- & Artikelverwaltung (`inventory`)** | Vollständiges Warenwirtschafts- & Inventursystem mit SKU, Lagerorten/Fächern, EK/VK-Preisen, Margenanalyse (€ / %), Meldebeständen mit Warn-Badges, Wareneingangsbuchung, Inventurabgleich, lückenlosem Audit-Trail (`inventory_log.json`) und CSV-Export für die Buchhaltung. 100 % kompatibel mit dem Shop-Plugin. | ✅ Integriert (v0.9.0) |
| **Bidirektionale Shop-Kopplung & Auto-Deduction** | Nahtlose Synchronisation: Artikel werden als Shop-Produkte geteilt; Webshop-Bestellungen lösen über den `shop_order_created`-Hook sofortige Bestandsabzüge und revisionssichere Audit-Log-Einträge aus. | ✅ Integriert (v0.9.0) |
| **Kreditkarten-Bezahlung** | Native Autorisierung von Visa, Mastercard und American Express mit mathematischer Luhn-10-Prüfsummenvalidierung, CVC- & Ablaufdatum-Check sowie Sandbox-Modus. | ✅ Integriert (v0.9.0) |
| **PayPal Express-Integration** | Schnelle und sichere Bezahlung mit PayPal inklusive Transaktions-ID-Protokollierung. | ✅ Integriert (v0.9.0) |
| **WERO European Instant Payment** | Das offizielle europäische Banken-Zahlverfahren der European Payments Initiative (EPI). Direkte Konto-zu-Konto Sofortzahlung via dynamic EPC069-12 SEPA-QR-Code (GiroCode) und Banking-App-Freigabe. | ✅ Integriert (v0.9.0) |
| **Bestellverwaltung im Backend** | Übersicht aller eingegangenen Bestellungen, Kundendaten, Status-Workflow (`Bezahlt`, `In Bearbeitung`, `Versendet`, `Abgeschlossen`, `Storniert`) und Umsatz-Statistiken. | ✅ Integriert (v0.9.0) |
| **Webtrees Stammbaum-Integration (Same-Server)** | Bindet Webtrees Stammbäume und Genealogie-Diagramme direkt in WeaveCMS ein. Aus Sicherheits- und Datenschutzgründen strikt auf denselben Server beschränkt (externe URLs werden unterbunden). | ✅ Integriert (v0.9.0) |
| **Community-Diskussionsforum (`weave-forum`)** | responsives Diskussionsforum mit 3-Spalten-Layout (Forum, Statistiken, Letzter Beitrag), 1440px Breiten-Support, modularen Bereichen, Sticky-Themen, Gelöst-Markierung und Beitragsreaktionen (Kudos). | ✅ Integriert (v1.3.1) |
| **Dualer Foren-Speicher & Migration** | Wahlfreiheit für Foreninhalte zwischen Flat-File JSON (`data/forum_data.json`) und indizierter relationaler Datenbank (SQLite `data/forum.sqlite` / MySQL) mit integrierter 1-Klick-Datenmigration. | ✅ Integriert (v1.3.1) |
| **Foren-Benutzerverwaltung & RBAC (`weave-forum-users`)** | Mitgliedersystem mit Rollen (`forum_admin`, `moderator`, `expert`, `forum_user`), Profilverwaltung, DSGVO-Export/Löschung, 1-Klick-Übernahme des WeaveCMS-Admins und automatischer Backend-Session-Erkennung. | ✅ Integriert (v1.3.1) |
| **Granulare Zugangs- & Lesesteuerung** | Konfigurierbarer Foren-Zugangsmodus (*Öffentlich: alles frei lesbar* vs. *Geschützt: Registrierung zum Lesen erforderlich*) mit dynamischer Whitelist für frei lesbare Ausnahmen (z. B. Ankündigungen, Willkommen). | ✅ Integriert (v1.3.1) |
| **Frontend-Moderationszentrale** | Eigener Frontend-Reiter *„🛡️ Moderation“* (`?page=forum&view=moderation`) für Administratoren und Moderatoren mit Beitragsmeldungs-Queue, Schnellaktionen und Audit-Log. | ✅ Integriert (v1.3.1) |
| **Erweiterte Forensuche mit Bereichsfilter** | Schnelle Volltextsuche mit Dropdown-Filterung nach Unterforen (inkl. Optgroups nach Kategorien), Wahl des Suchbereichs (*Beiträge & Titel* vs. *Nur Titel*), Sortierung und `<mark>`-Hervorhebung. | ✅ Integriert (v1.3.1) |
| **Foren-Markdown- & Code-Engine** | Umfassender Markdown-Parser mit Unterstützung für Fett (`**`), Kursiv (`*`), Hyperlinks (`[Text](url)`), Inline-Code (`code`), mehrzeilige Fenced Code-Blöcke (`php ...`) mit Sprach-Badge & 1-Klick-Kopieren sowie Markdown- und BBCode-Zitate (`[quote]`) inkl. Live-Vorschau. | ✅ Integriert (v1.3.1) |

---

### v1.3.1 (Community-Diskussionsforum, Zugangssteuerung, Kunena-UI & Markdown-Engine)

* **Diskussionsforum (`weave-forum`):**
  * **3-Spalten-Layout & 1440px-Breite:** Vollständige Ausnutzung breiter Displays (Container bis 1440px), farbcodierte Kategoriekarten mit einklappbaren Bereichen, separate Info-, Statistik- und Letzter-Beitrag-Spalten sowie Online-User- und Foren-Statistik-Widgets am Seitenende.
  * **Dualer Speicher & 1-Klick-Migration:** Volle Unterstützung von Flat-File JSON (`forum_data.json`) und indizierter relationaler Datenbank (SQLite `forum.sqlite` oder MySQL) inklusive unterbrechungsfreier Datenmigration direkt im Backend.
* **Foren-Benutzerverwaltung & Berechtigungen (`weave-forum-users`):**
  * **Rollenbasiertes Rechtesystem:** Differenzierte Rechte für Administratoren, Moderatoren, Experten und reguläre Forenmitglieder mit farbigen Autoren-Badges.
  * **1-Klick-Übernahme des Seiten-Administrators:** Automatischer Abgleich der WeaveCMS-Inhaberdaten (`contact_name`, `contact_email`) in die Forendatenbank als `forum_admin` plus nahtlose automatische Frontend-Authentifizierung für aktive CMS-Admin-Sessions.
  * **Dedizierte Frontend-Moderationszentrale:** Neuer Navigations-Reiter *„🛡️ Moderation“* mit dynamischem Zähler-Badge für offene Beitragsmeldungen, 1-Klick-Aktionen (*Erledigen* / *Löschen*) und Schnellübersichten für angepinnte und geschlossene Themen.
* **Granulare Zugangs- & Lesesteuerung:**
  * **Foren-Zugangsmodus:** Im Backend wählbar zwischen *„🌐 Öffentlich: Alle Bereiche frei lesbar“* und *„🔒 Geschützt: Registrierung zum Lesen erforderlich“*.
  * **Dynamische Ausnahme-Whitelist:** Bei geschlossener Community können beliebige Unterforen (z. B. *Ankündigungen & Releases*, *Willkommen & Vorstellung*) per Checkbox-Auswahl für Gäste ohne Registrierung dauerhaft frei lesbar geschaltet werden.
  * **Informative Gast-Barrieren:** Gäste sehen auf geschützten Bereichen eine freundliche Registrierungs-Aufforderung; in Übersichten werden geschützte Foren mit dem Badge `🔒 Nur für Mitglieder` versehen und sensible Inhalte ausgeblendet.
  * **Eindeutiger Antwort-Hinweis:** Unmissverständliche Trennung zwischen freiem Leserecht und schreibgeschützter Quick-Reply (*„Dieses Thema ist für alle Besucher frei lesbar. Um eine Antwort zu verfassen...“*).
* **Erweiterte Forensuche mit Bereichsfilter:**
  * **Unterforen-Eingrenzung:** Neues Dropdown-Menü zur gezielten Durchsuchung einzelner Forenbereiche (mit optischer Kategorien-Gruppierung und automatischer Berücksichtigung von Gast-Leseberechtigungen).
  * **Filter & Sortierung:** Umschaltung zwischen Volltextsuche (*In Beiträgen & Titeln*) und Thementitelsuche (*Nur in Thementiteln*); Sortierung nach neuester Aktivität, Erstelldatum, meisten Antworten oder meisten Aufrufen.
  * **Hervorhebung:** Automatische optische Markierung (`<mark>`) des gesuchten Begriffs in Ergebnis-Snippets.
* **Vollwertige Markdown-, Code- & Zitat-Engine:**
  * **Reiche Formatierung:** Vollständige Unterstützung für Fett (`**text**`), Kursiv (`*text*`), Hyperlinks (`[Text](url)`) und Inline-Code (`code`).
  * **Fenced Code-Blocks mit Sprach-Badge & Kopier-Button:** Mehrzeilige Code-Blöcke mit Monospace-Schriftart, Sprach-Badge und 1-Klick-Kopieren in die Zwischenablage.
  * **Zitate:** Unterstützung von Markdown-Blockquotes (`>`) und BBCode-Zitaten (`[quote="Autor"]...[/quote]`) mit automatischer Autoren-Kennzeichnung.
  * **WYSIWYG-Live-Vorschau:** Tab *„Vorschau“* im Editor spiegelt alle Formatierungen und Zitate 1:1 wieder.
* **System- & Upload-Härtung:**
  * **ImageUploader::upload():** Abwärtskompatible Parameter-Signatur (`string|array|null $uploadRelDir`), die sowohl relative Pfad-Strings als auch das WeaveCMS `$data`-Array transparent verarbeitet und fatale Typfehler verhindert.

---

### v1.3.0 (Zentrale Medienverwaltung, WebP/AVIF-Bildpipeline & Content-Revisions)

* **Zentraler Medien-Manager & Asset-Hub (Phase 22):**
  * Neuer Hauptreiter *„Mediathek“* in der oberen Navigation zwischen *Inhalte & Seiten* und *Darstellung*.
  * Umschaltbare Kachel- und Listenansicht aller hochgeladenen Bilder, Grafiken, Vektoren und Dokumente mit Dateigrößenanzeige, Filter nach Medientyp, Live-Suche und Sortierung.
  * 1-Klick-Kopieren von Bild-URLs, Markdown-Tags (`![Alt](url)`) und responsivem HTML5 `<picture>`-Code.
  * Barrierefreiheits-Modal für Alt-Texte und Bildtitel mit Speicherung in `data/media_meta.json`.
* **Moderne Bild-Pipeline mit WebP, AVIF & Resizing (Phase 22):**
  * Automatische Erzeugung komprimierter WebP- und AVIF-Varianten beim Upload via PHP `gd`.
  * Automatische Generierung von Standardgrößen (`thumb`: 350px, `medium`: 800px, `large`: 1400px).
  * Smarte Varianten-Isolierung: Kind-Varianten belasten nicht die Hauptübersicht und werden beim Löschen des Masters restlos entfernt.
* **Drag & Drop Multi-Upload & 7Z-Support (Phase 22):**
  * Gleichzeitiger Mehrfach-Upload mit visuellem Fortschrittsbalken und Dateinamensbereinigung.
  * Strikte Magic-Bytes-Prüfung (`37 7A BC AF 27 1C` für 7z) für JPG, PNG, WebP, GIF, SVG, PDF, ZIP und 7Z (Ausschluss proprietärer Office-Formate zugunsten von standardisiertem PDF-Export).
* **Content-Revisions & Entwurfsmodus (Phase 23):**
  * Automatisches Versionieren bei jeder Speicherung in `data/revisions/` (bis zu 25 Stände).
  * Interaktives Revisions-Modal mit Unified-Diff-Viewer (`+` / `-`) und 1-Klick-Rollback.
  * Status-Management (`published`, `draft`, `scheduled`) mit zeitgesteuerter Veröffentlichung und Preview-Token (`?preview=...`) mit Frontend-Banner.
* **Vollständige Mehrsprachigkeit (i18n):**
  * Sämtliche neuen Komponenten sind lückenlos über `libs/lang/admin/` für Deutsch und Englisch übersetzt.

---

### v1.2.0 (Modernes Sektionen-Kanban, Kontakt-Spamschutz & UI-Härtung)

* **Modernisiertes Kanban-Board für Sektionen & Modul-Pool (Inhalte & Seiten):**
  * **Logische Kanban-Architektur:** Aktive Seiten & Sektionen (Live) stehen links (1.25× Breite mit Primärakzent) als Hauptarbeitsbereich; inaktiver Modul-Pool rechts als Baustein-Vorrat.
  * **Entfernung der Wireframe-Rahmen:** Sanfte, abgerundete Oberflächenkarten (`border-radius: 14px`) anstelle unfertiger gestrichelter Linien; dynamische Drop-Indikation bei Drag-Over.
  * **Squircle Icon-Tiles & Positions-Badges:** Jedes Modul verfügt über ein 38×38px Squircle-Icon (`.sec-icon-tile`), Positions-Pills (`#1`, `#2`...) sowie aufgeräumte Status-Tags (`Plugin`, `Menü`, Platzierung).
  * **Ergonomische Aktionsleiste:** Prominenter Button `+ Aktivieren` im Pool sowie dezente Icon-Toolbar für Sortierung, Einstellungen, Inhaltsverwaltung und Deaktivierung.
* **Anti-Scraping E-Mail-Schutz für das Kontakt-Menü (`contact_email_captcha`):**
  * **Direktschutz vor Harvestern:** Optionale Maskierung der direkten Kontakt-E-Mail-Adresse im Kontakt-Drawer vor automatisierten E-Mail-Scrapern.
  * **HTML5 Canvas-Captcha:** Schnelle, barrierefreie Freischaltung per 5-stelligem Code direkt im Drawer mit automatischer AJAX-Verifikation.
  * **Unabhängige Konfiguration:** Getrennte Schalter im Sicherheits-Tab für Impressum und Kontaktbereich.
* **Layout- & Container-Härtung:**
  * **HTML-Validierung & Fehlerbehebung:** Beseitigung eines vorzeitigen `</div>`-Tags in `tab_texts.php`, das Sektionstexte versehentlich auf allen Tabs angezeigt hatte.
  * **Nahtlose Sprachumschaltung:** Robuster Wechsel zwischen Sprach-Tabs in Text- und Seiteneditoren ohne ungewollte Dashboard-Redirects.

---

### v1.1.0 (Live-Server-Deployment, Produktions-Härtung & Universelle Sprach-Tabs / Phase 17)

* **Live-Server-Deployment, SSL/HSTS-Härtung & Produktions-Sicherheit (Phase 17):**
  * **Strikte HSTS-Policy & SSL-Preloading (17.1):** Konfigurierbare Aktivierung von `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` exklusiv für bestätigte Live-Domains mit aktivem SSL-Zertifikat.
  * **Gehärtete Production-CSP & Subresource Integrity (17.2):** Schärfung der HTTP Content Security Policy (`script-src`, `style-src`, `object-src 'none'`, `form-action 'self'`) sowie SRI-Tag-Generator `SecurityManager::renderSriTag`.
  * **Dateisystem-Berechtigungen & POSIX-Audit (17.3):** Live-Audit-Prüfung im Sicherheitsbereich und Dashboard für sensible Dateien (`config.php`, `config.local.php`, `data/data.json`) und Ordner (`data/`, `backups/`, `logs/`, `images/uploads/`) inklusive `.htaccess`-Schutz.
  * **Reverse-Proxy & Trusted-Proxy IP-Filter (17.4):** Robuster Anti-IP-Spoofing-Schutz; Header wie `CF-Connecting-IP` und `X-Forwarded-For` werden nur noch von explizit autorisierten Upstream-Proxies oder CIDR-Subnetzen akzeptiert.
  * **Automatisierte Admin-E-Mail-Alarmierung (17.5):** Automatische Warnung per E-Mail bei Brute-Force-Lockouts mit integriertem Cooldown-Schutz (verhindert E-Mail-Fluten).
  * **Live-Deployment Checkliste (17.6):** 4-Punkte-Checkliste und Direkt-Prüflinks zu SecurityHeaders.com und Qualys SSL Labs direkt im Backend. 27/27 Tests in `tests/test_phase17_production_security.php` bestanden.
* **Übersichtliche Mehrsprachigkeit mit Sprach-Tabs (Option A & B):**
  * **Sprach-Tabs je Sektion & Modul:** Umschaltung (`[🇩🇪 Hauptsprache] [🇬🇧 English] ...`) in allen Editoren und Drawers (Sektionsleisten, FAQ, Team, Downloads, Preise, Kurse, Galerie) ohne langes Scrollen.
  * **Sprach-Badges:** Visuelle Indikatoren in den Admin-Listenansichten aller Content-Module über gepflegte Übersetzungen.
  * **Automatische Frontend-Kaskade:** Dynamische Textauflösung mit Fallback auf die Hauptsprache.

### v1.0.0 (Offizielles Release 1.0.0 / Phase 20)

* **In-Navbar Barrierefreiheit & UX-Bereinigung:**
  * **Direkte Menü-Integration:** Dunkel-/Hell-Modus-Umschalter (🌙/☀️ mit dynamischem Tooltip) und 3-stufiger Basisschriftarten-Zoom (A- / A / A+) nahtlos in die Kopfzeilennavigation (`header_nav.php`) und den mobilen Slide-in-Drawer (`slide_mobile_nav.php`) integriert.
  * **Beseitigung von Redundanzen:** Wegfall der separaten 38px-Top-Bar (`accessibility_bar.php`) und Beseitigung doppelter Sprachwechsler bei aktivierter Barrierefreiheit.
  * **Optimaler Seitenfluss:** Kein störender vertikaler Layout-Shift oder Header-Versatz im Frontend.
* **Modul-Pool, Drag & Drop Seiten-Aufbau & Dynamische Subnavigation:**
  * **Interaktiver Modul-Pool & 2-Zonen Board:** Trennung in inaktive Kategorien („Modul-Pool“) und aktive Sektionen („Aktive Sektionen / Seiten-Aufbau“) mit visueller Indikation von Typ, Icon und Beschreibung im Administrationsbereich (`tab_texts.php`).
  * **Drag & Drop Sortierung:** Intuitives Hineinziehen inaktiver Module in die aktive Seitenstruktur sowie freie Umsortierung per Drag & Drop mit HTML5 Drag-API und visueller Drop-Zone.
  * **Dynamische Admin-Subnavigation:** Die Sub-Reiter unter *„Inhalte & Seiten“* (`.admin-subnav` für Galerie, Blog, Kurse, FAQs, Team, Preise, Downloads, Stammbaum) passen sich automatisch in Echtzeit an – inaktive Module werden aus der Navigationsleiste ausgeblendet.
  * **Mobile & Tablet Optimierung (<= 900px):** Responsive 2-Phasen-Umschaltung (*Aktive Sektionen* vs. *Modul-Pool*) für Touchscreens mit 1-Tap-Aktivierung (`+ Aktivieren`), Positionsverschiebung (`▲` / `▼`), Quick-Settings (`⚙️`), Content-Sprung (`📝`) und Zurücklegen in den Pool (`✕`).
  * **Asynchrone AJAX-Synchronisation:** Blitzschnelles Speichern von Reihenfolge und Modul-Sichtbarkeit ohne Seiten-Reload (`AdminController::sync_sections_pool`) inklusive animiertem Toast-Feedback.

### v0.9.7

* **Menü-Aufbau, Position-Selector im Design-Bereich & Plugin-Theme-Erweiterbarkeit (Phase 19):**
  * **Menü-Position & Typ-Selector im Design-Tab (19.1):**
    * Konfigurationsfeld im Adminbereich unter *„Darstellung & Design“* (`tab_settings.php`) mit interaktiven Kacheln:
      1. `Waagerecht Minimal (Standard)`: Bewährte schlanke Kopfzeilen-Navigation mit Logo und Text-Links.
      2. `Waagerecht mit Icons`: Horizontale Menüleiste mit passenden Google Material-Icons vor jedem Menüpunkt für visuelle Orientierung.
      3. `Senkrecht (Sidebar-Navigation)`: Feste vertikale Seitenleiste links oder rechts mit Branding oben, Links im Zentrum und Trigger/Sprachwechsler unten. Auf kleineren Displays (&lt;= 992px) automatische, barrierefreie Einklappung in eine schlanke Kopfzeile mit Hamburger-Drawer.
    * Zusätzlicher Ausrichtungs-Selector (`sidebar_position`): Wahlweise links (`left`, Standard) oder rechts (`right`) fixiert.
  * **Icon- & Symbol-Zuweisung für Menüpunkte (19.2):**
    * Intelligentes Standard-Icon-Mapping in `SectionManager::getDefaultIcon`: Automatische Symbole für alle Sektionstypen (`photo_library`, `event`, `newspaper`, `storefront`, `account_tree`, `file_download`, `quiz`, `groups`, `sell`, `badge`, `flag`, `article`).
    * Optionales Icon-Eingabefeld mit Live-Vorschau im Sektionen-Manager (`tab_texts.php`) und im Freie-Einzelseiten-Editor (`tab_pages.php`).
    * Bereitstellung der Symbole über `SectionManager::getNavItems` für Frontend-Header, vertikale Sidebar und mobile Slide-in-Navigation.
  * **Plugin-Schnittstelle für Menüarten & Themes (19.3):**
    * Neuer Filter-Hook `theme_register_menu_types`: Ermöglicht Plugins das Registrieren individueller Menüarten (z. B. macOS Floating Dock, Fullscreen Overlay).
    * Neuer Action-Hook `frontend_render_navigation`: Erlaubt Plugins das vollständige Rendern und Überschreiben benutzerdefinierter Navigationsleisten.
    * Neuer Filter-Hook `theme_register_layouts`: Ermöglicht Plugins die Registrierung eigener Page-Templates (`ThemeManager::getAvailableLayouts`).
  * **100 % Testabdeckung:** 28/28 automatisierte Tests (`test_phase19.php`) validieren Menüstil-Umschaltung, Fallbacks, Icon-Resolution und Hook-Ausführung.

### v0.9.6

* **Flexible Website-Architektur & Modulare Landingpages (Phase 18):**
  * **Globaler Architektur-Wahlschalter (18.1):**
    * Konfigurierbar im Reiter *„Darstellung & Design“* (`tab_settings.php`) mit 3 wählbaren Kacheln:
      1. `One-Pager (Standard)`: Alle aktiven Inhaltsbereiche liegen fortlaufend auf der Startseite mit weicher Anker-Navigation (`#sektion`).
      2. `Klassische Multi-Page Website`: Startseite als kompakte Begrüßung / Teaser; Hauptsektionen (Galerie, Workshops, Shop, Blog, Downloads, Stammbaum) erhalten eigene URLs (`?page=sektion_id`) und laufen als vollwertige Einzelseiten.
      3. `Individuell / Hybrid`: Freie Zuweisung pro Sektion im Sektionen-Manager (`Startseite & Einzelseite [Beides]`, `Nur auf Startseite`, `Nur als eigene Einzelseite`).
  * **Zusätzliche modulare Endlos-Scroll-Seiten / Landingpage-Builder (18.2):**
    * Erweiterung der Seiten-Verwaltung (`tab_pages.php`) um den Seitentyp:
      * `Standard-Inhalt`: Freier Text, Markdown, HTML, Bilder.
      * `Modulare Sektions-Landingpage`: Interaktive Zusammenstellung beliebiger Sektionen (Hero, Galerie, Workshops, Blog, Preise, Downloads, Stammbaum, Shop) in frei wählbarer Reihenfolge.
    * Eigene URLs (`?page=slug`), eigene SEO-Metadaten (Title, Description) und dediziertes Template `views/partials/page_modular.php` mit optionalem Einleitungs-Banner.
  * **Intelligente Routing- & Menü-Engine (18.3):**
    * `FrontendController::renderPage`: Automatisches Erkennen und Rendern von Modularen Landingpages (`page_modular.php`) sowie Standalone-Sektionen (`section_single.php`) mit barrierefreiem Breadcrumb-Zurück-Button.
    * `SectionManager::getNavItems`: Dynamische Generierung von Anker-Links (`#sektion`) für Sektionen der aktuellen Seite und direkten Subpage-Links (`?page=sektion_id`) für ausgelagerte Bereiche.
    * Barrierefreie Logo-Rückkehr: Klick auf das Logo führt von Einzelseiten zuverlässig zur Startseite (`./`) zurück.
* **Audit & Härtung Plugin-System (Phase 13) & Barrierefreiheit (Phase 16):**
  * **Behebung von Closure-Referenz-Warnungen:** Bereinigung des `plugin_config_saved_webtrees-genealogy` Action-Handlers zur Verhinderung von PHP-Warnungen bei Variadic-Aufrufen in `PluginManager::doAction`.
  * **Bidirektionale Sektions-Synchronisation:** `PluginManager::savePluginConfig` synchronisiert Sichtbarkeit (`section_enabled`) und Titel (`section_title`) nun automatisch mit `SectionManager`.
  * **Spam-Captcha Icon-Key Härtung:** Behebung der `Undefined array key "id"` PHP-Warnung in `slide_contact.php` durch robusten Fallback auf den assoziativen Array-Key.
  * **100 % Testabdeckung:** 25/25 Tests für Phase 13 und 7/7 Tests für Phase 18 laufen fehlerfrei durch.

### v0.9.5

* **Logo- & Branding-Verwaltung (Phase 11):**
  * **4 flexible Logo-Typen:** Wählbar zwischen *Reiner Text* (Typografie aus Einstellungen), *Eigenes Bild-Logo* (vollflächige Header-Grafik), *Icon & Text kombiniert* sowie *SVG-Naturmotiven* (Hundetatzen).
  * **Sicherer Datei-Upload & XSS-Schutz:** Upload von SVG, PNG, WebP und JPG mit automatischer Vektorbereinigung (`ImageUploader::sanitizeSvg`) gegen schädliche Skripte, Event-Handler (`onload=`, `onerror=`) oder Javascript-URIs.
  * **Stufenlose Höhenskalierung (24 px bis 100 px):** Präziser Schieberegler mit Pixel-Anzeige und automatischer proportionaler Breitenanpassung (keine Verzerrung des Headers).
  * **Side-by-Side Live-Vorschau:** Sofortige Ansicht des hochgeladenen Logos auf hellem und dunklem Hintergrund im Adminbereich via HTML5 `FileReader`.
  * **Favicon- & Touch-Icon-Management:** Eigener Upload für `.ico`, `.svg` und `.png` mit integriertem Browser-Tab-Mockup und dynamischer Head-Generierung via `LogoRenderer::renderFaviconTags()`.
* **Mobile Responsiveness & WCAG-Audit (Phase 11):**
  * **WCAG 2.5.5 / 2.5.8 Touch-Targets:** Hamburger-Button (`#mobile-nav-trigger`), Slide-in-Close-Buttons und Vollbild-Lightbox-Schaltflächen (Vor, Zurück, Schließen) auf mindestens 44 × 44 px vergrößert.
  * **iOS Safari Auto-Zoom-Prävention:** Alle Formularfelder (`.form-control`, `input`, `select`, `textarea`) erhalten unterhalb von 768px Viewport-Breite standardmäßig `font-size: 16px !important`, wodurch das automatische Hineinzoomen auf iPhones unterbunden wird.
  * **Horizontaler Überlauf-Schutz:** Konsequenter Einsatz von `overflow-x: clip` und `overflow-x: hidden` gegen seitliches Scrollen auf Mobilgeräten (360 px – 414 px).
* **Vorab-Systemprüfung (Pre-Flight Check) & erweiterte DB-Prüfung im Installer:**
  * **Strukturierter 5-teiliger Pre-Flight Health Check (`install.php` & `Installer.php`):**
    * 🚀 **PHP-Laufzeitumgebung:** Mindestanforderung (PHP >= 8.0), `memory_limit` (>= 64M Pflicht, >= 128M empfohlen), `upload_max_filesize`, `post_max_size`, `max_execution_time` und Server-Zeitzone.
    * 📁 **Dateisystem & Schreibrechte:** Hauptverzeichnis (`/`), `data/`, `images/uploads/`, `images/branding/`, `backups/` und `data.sample.json`.
    * 🧩 **PHP-Erweiterungen:** Prüfung auf `pdo`, `pdo_mysql`, `pdo_sqlite`, `gd`, `mbstring`, `json`, `curl`, `fileinfo` und `zip`.
    * 🌐 **Webserver & URL-Rewrite:** Automatische Erkennung (Apache, Nginx, IIS), Überprüfung von `mod_rewrite` und `.htaccess`.
    * 💡 **Empfohlene Server-Einstellungen:** Aufklappbarer Ratgeber mit Ist- vs. Empfohlen-Tabelle und kopierfertigen Code-Blöcken für `php.ini` und Apache VirtualHost (`AllowOverride All`).
  * **Datenbank-Vorab-Check mit Benutzerrechte-Test (`StorageManager::testDbConnection`):**
    * Automatische Verifikation von Serververbindung, Login, Datenbank-Existenz und Auto-Create.
    * Echter Funktionstest der Benutzerrechte durch Erzeugen, Beschreiben, Lesen und Löschen einer temporären Testtabelle (`CREATE TABLE`, `INSERT`, `SELECT`, `DROP`).
    * Erzwungene Prüfung im Installations-Assistenten: Bei Auswahl von MySQL wird der DB-Test beim Klick auf *„Weiter zu Schritt 3“* automatisch erzwungen und ein Weiterschalten bei fehlerhaften Zugangsdaten blockiert.
* **Webtrees Stammbaum- & Sektionen-Manager Synchronisation:**
  * Nahtlose Einbindung der Stammbaum-Sektion in den Sektionen-Manager zur freien Verschiebung und Ein-/Ausblendung.
  * Behebung des Tab-Redirects beim Ein- und Ausblenden von Seiten (stabiler Verbleib im Inhalts-Tab `tab-pages`).

### v0.9.0

* **WeaveCMS Lager- & Artikelverwaltung (`plugins/inventory`):**
  * **Zentrale Artikelstammdaten:** Verwaltung von Artikeln mit Artikelnummer (SKU), Barcode/EAN, Kategorie, Einheit (Stück, kg, m, etc.), Lagerort/Regalfach (z. B. Regal A-03), Lieferant und Lieferanten-Artikelnr.
  * **Kalkulation, Margen & Lagerwert:** Erfassung von Netto-Einkaufspreisen (EK) und Verkaufspreisen (VK); automatische Berechnung von Rohgewinn/Marge in Euro und Prozent sowie dynamische Gesamtwert-Ermittlung (Gesamtlagerwert EK, Verkaufswert VK und potenzieller Rohgewinn).
  * **Meldebestände & Frühwarnsystem:** Konfigurierbare Mindestbestände je Artikel mit visuellen Warn-Badges (*Knapp*, *Ausverkauft*, *Optimal*) und Schnellfiltern nach Handlungsbedarf.
  * **Wareneingang (Zubuchung):** Schnelle und geführte Zubuchung gelieferter Ware mit Lieferschein-Referenz, Lieferanten-Zuordnung, EK-Preisanpassung und optionaler Notiz.
  * **Revisionssicherer Audit-Trail (`data/inventory_log.json`):** Lückenlose Protokollierung aller Bestandsveränderungen (Erstanlage, Wareneingang, Webshop-Verkauf, manuelle Korrektur, Inventurabgleich) mit Vorher-/Nachher-Bestand, Zeitstempel, Referenz und ausführendem Benutzer.
  * **Inventur & Stichtags-Abgleich:** Komfortable Zählliste zur schnellen Erfassung physischer Ist-Bestände mit automatischer Delta-Berechnung und Korrekturbuchung im Protokoll.
  * **Buchhaltungs- & DATEV-tauglicher CSV-Export:** 1-Klick-Export des gesamten Lagerbestands inklusive aller Stammdaten, Lagerorte, EK/VK-Werte und Margen im UTF-8 BOM Excel/CSV-Format.
  * **100 % Shop-Kompatibilität & Hook-Automation:** Teilt die Datenbasis `data/shop_products.json` mit dem WeaveCMS Shop-Plugin; bei erfolgreichen Shop-Bestellungen bucht der Hook `shop_order_created` die verkauften Mengen automatisch aus und hinterlegt die Bestellnummer im Lagerprotokoll.
* **WeaveCMS Shop- & E-Commerce-Plugin (`plugins/shop`):**
  * **Vollwertiger Produktkatalog & Lagerverwaltung:** Produkte mit Titel, SKU/Artikelnummer, Kategorien, Preis, MwSt.-Satz oder Kleinunternehmerregelung (§ 19 UStG), Lagerbestand (inkl. Option für unbegrenzte/digitale Artikel), Produktbildern und Kurzbeschreibungen.
  * **Reaktiver Warenkorb & Slide-Out Drawer:** Schwebender Floating-Cart-Button mit Zähler-Badge (`#shop-cart-floating`), animierter Off-Canvas-Warenkorb (`#shop-cart-drawer`), Mengensteuerung (`+` / `-`), Artikelentfernung sowie automatische Berechnung von Zwischensumme, Versandkosten und Steuern via dedizierten AJAX-Endpunkt (`api/shop.php`).
  * **SSL-gesichertes Multi-Step Checkout-Modal:** Kundendatenerfassung (Liefer- & Rechnungsadresse), Pflichtfeldprüfung und interaktiver Bezahlbereich.
  * **Integrierte Multi-Payment-Engine:**
    * 💳 **Kreditkarte:** Native Autorisierung von Visa, Mastercard und American Express mit mathematischer Luhn-10-Prüfsummenprüfung, CVC- & Ablaufdatum-Validierung sowie zuschaltbarem Sandbox-/Testmodus.
    * 🅿️ **PayPal:** Express-Checkout-Option mit automatischer Generierung und Archivierung von Transaktions-Referenzen (`PAYID-...`).
    * 🇪🇺 **WERO (European Payments Initiative / EPI):** Das neue europäische Zahlverfahren der europäischen Bankenallianz (Sparkassen, Volksbanken, Deutsche Bank, BNP Paribas uvm.). Bietet sofortige Konto-zu-Konto-Gutschriften über dynamisch generierte EPC069-12 SEPA-QR-Codes (GiroCode) zum Scannen mit jeder kompatiblen Banking-App.
  * **Admin-Bestellverwaltung & KPI-Dashboard:** KPI-Kacheln für Gesamtumsatz, eingegangene Bestellungen und aktive Artikel; tabellarische Bestellübersicht mit Filter nach Zahlungsart (`Kreditkarte`, `PayPal`, `WERO`), editierbarem Status (`Bezahlt`, `In Bearbeitung`, `Versendet`, `Abgeschlossen`, `Storniert`) und Auftragsdetail-Modal.
  * **Shortcode & Sektions-Integration:** Flexibel einbettbar über den Shortcode `[shop]` (mit optionalem Kategorie-Filter) sowie als eigene Sektion auf der Startseite aktivierbar.
* **Webtrees Stammbaum-Integration (Same-Server Härtung):**
  * **Strikte Same-Server-Bindung:** Webtrees-Installationen müssen zwingend auf demselben Server betrieben werden; externe Domains und Remote-URLs (`http://`, `https://`) werden aus Sicherheits- und Datenschutzgründen serverseitig unterbunden und bereinigt.
  * **Lokale Server-Pfade:** Unterstützung flexibler Webroot-Pfade (z. B. `/webtrees` oder `/webtrees/index.php`) mit automatischer serverseitiger Verzeichnis- und Dateiprüfung im Adminbereich.
* **Downloads, Software-Portal & Klick-Counter (Phase 16):**
  * Download-Karten mit Versions-Badges (`v1.2.0`), Plattform-Kennzeichnung (Windows, macOS, Linux), Feature-Checklisten, Screenshot-Galerie mit Lightbox und responsivem Changelog-Modal.
  * Manipulationssicherer Download-Proxy (`api/download.php?id=...`) mit Bot-Filter gegen Web-Crawler und atomarer Zähler-Aktualisierung.
* **Modulare Captcha-Auswahl-Engine & Anti-Scraping E-Mail-Schutz:**
  * Zentrale Wahl im Sicherheitsbereich zwischen 4 Verfahren: Symbol-Captcha, Mathe-Rechen-Captcha, dynamisches HTML5 Canvas-Zahlen-Captcha und unsichtbarer Honeypot.
  * Optionaler E-Mail-Schutz im Impressum: E-Mail-Adresse wird erst nach Lösung eines interaktiven Canvas-Zahlencodes über `api/verify_email_captcha.php` freigeschaltet.
* **Besucher-Barrierefreiheitsleiste:**
  * Zuschaltbare Top-Bar mit Live Dark/Light-Mode (🌙/☀️ via `data-theme="dark"` und `localStorage`), 3-stufigem Basisschriftarten-Zoom (85 % bis 125 %) und integriertem Sprachwechsler.

### v0.8.5

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
