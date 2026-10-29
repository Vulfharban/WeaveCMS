# WeaveCMS vs. WordPress, Joomla, Drupal & TYPO3
## Umfassende Funktionsanalyse, Alleinstellungsmerkmale (USPs) & Feature-Gap-Roadmap

> **Dokumenten-ID:** `DOC-ANALYSIS-2026-10-CMS-BENCHMARK`  
> **Status:** Aktualisiert nach Abschluss der Phasen 22–28 (Release v1.3.2)  
> **Zielsystem:** WeaveCMS (v1.3.2+)  
> **Vergleichssysteme:** WordPress 6.x, Joomla 5.x, Drupal 10/11, TYPO3 v12/13  
> **Letzte Prüfung:** 29. Oktober 2026  

---

## 1. Executive Summary & Positionierung

Im modernen Web-Ökosystem existiert eine wachsende Kluft zwischen gigantischen, oft überdimensionierten Enterprise-/Massen-Systemen (**WordPress, Drupal, TYPO3**) und modernen, leichtgewichtigen Lösungen.

WeaveCMS positioniert sich bewusst im Segment der **hochperformanten, wartungsarmen und datenschutzkonformen All-in-One CMS für KMU, Handwerk, Dienstleister und Portfolio-Websites**:

```
Enterprise / Behörden            Agenturen / Publishing            Mittelstand / KMU & Handwerk
┌───────────────────────┐        ┌───────────────────────┐        ┌───────────────────────────────┐
│   TYPO3 / Drupal      │        │       WordPress       │        │          WeaveCMS             │
│  - Extreme Komplexität│        │  - 60.000+ Plugins    │        │  - Zero-Bloat, Pure PHP       │
│  - Symfony / Composer │        │  - Hohe Angriffsfläche│        │  - Flat-File + SQL Hybrid     │
│  - Hoher Pflegeaufwand│        │  - Wartungsintensiv   │        │  - All-in-One ohne Plugin-Flut│
└───────────────────────┘        └───────────────────────┘        └───────────────────────────────┘
```

Mit den Veröffentlichungen von **v1.3.0**, **v1.3.1** und **v1.3.2** wurden die bisher gravierendsten funktionalen Lücken gegenüber den etablierten Systemen (zentrale Mediathek, Content-Versionierung, Entwurfsmodus, SEO-Autopilot mit Sitemaps & JSON-LD, 1-Klick-Core-Updater, lückenlose Backend-Mehrsprachigkeit, visueller Grid-Builder sowie globale Taxonomien & Custom Fields) vollständig geschlossen.

---

## 2. Detaillierter Feature-Matrix-Vergleich (Stand: v1.3.2)

| Funktionsbereich | **WeaveCMS (v1.3.2)** | **WordPress** | **Joomla** | **Drupal** | **TYPO3** |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Architektur & Laufzeit** |
| Zero-Dependency PHP (ohne Composer-Zwang) | **Ja (100 % Pure PHP)** | Nein (Composer/Vendor) | Nein (Composer) | Nein (Composer/Symfony) | Nein (Composer/Symfony) |
| Speicher-Engine (Dual-Storage) | **Hybrid (JSON & SQL)** | Nur SQL (MySQL) | Nur SQL (MySQL/PgSQL) | Nur SQL | Nur SQL |
| Server-Ressourcenverbrauch | **Minimal (< 15 MB RAM)** | Mittel bis Hoch | Mittel | Hoch | Sehr Hoch |
| Installationszeit | **< 30 Sekunden** | ~ 3–5 Min. | ~ 5–10 Min. | ~ 10–20 Min. | ~ 15–30 Min. |
| Integrierter 1-Klick Core-Updater | **Nativ (v1.3.0 / Phase 25)** | Nativ | Nativ | Via Composer / UI | Via Composer |
| Pre-Update Backup & Auto-Rollback | **Nativ im Updater** | Nur via Dritt-Plugins | Eingeschränkt | Via CLI / Skripte | Via CLI / Skripte |
| **Content-Management & Authoring** |
| Sektions-/Baukasten-Prinzip | **Nativ im Kern (Kanban)** | Via Plugins / FSE | Eingeschränkt | Via Layout Builder | Via Gridelements/Fluid |
| Visueller Grid-Builder & Spalten-Layouts | **Nativ CSS-Grid (v1.3.2 / Phase 27)** | Gutenberg Grids | Bootstrap / Grids | Layout Builder | Grids / DCE |
| Visueller WYSIWYG / Block-Editor | Markdown + Design-Toolbar | Gutenberg (Blocks) | TinyMCE | CKEditor 5 | CKEditor / RTE |
| Entwürfe / Versionierung / Revisions | **Nativ (v1.3.0 / Phase 23)** | Nativ | Nativ | Nativ (Sehr stark) | Nativ (Workspaces) |
| Zeitgesteuertes Veröffentlichen (`scheduled`)| **Nativ (v1.3.0 / Phase 23)** | Nativ | Nativ | Nativ | Nativ |
| Entwurfs-Vorschau mit Secure-Token | **Nativ (v1.3.0 / Phase 23)** | Nativ | Eingeschränkt | Nativ | Nativ |
| Globale Schlagworte (Tags/Taxonomie) | **Nativ (v1.3.2 / Phase 28)** | Nativ | Nativ | Extrem mächtig | Nativ |
| Custom Fields & Strukturierte Attribute | **Nativ (v1.3.2 / Phase 28)** | Plugins (ACF) / Nativ | Nativ (Custom Fields) | Nativ (Entity Fields) | Nativ |
| Personalisierbarer Action-Hub (Dashboard) | **Nativ (v1.3.1 / Phase 26)** | Eingeschränkt (Widgets)| Eingeschränkt | Dashboard-Module | Dashboard-Module |
| **Medien & Asset-Management** |
| Zentrale Mediathek (Media Library) | **Nativ (v1.3.0 / Phase 22)** | Nativ (Exzellent) | Nativ | Nativ | Nativ (FAL) |
| Automatische Bildkonvertierung (WebP/AVIF)| **Nativ (v1.3.0 / Phase 22)** | Nativ / Plugins | Nativ | Image Styles | Nativ |
| Responsive Standardgrößen (`picture`-Tag)| **Nativ (thumb, med, lrg)** | Nativ | Nativ | Nativ | Nativ |
| Drag & Drop Mehrfach-Upload | **Nativ (v1.3.0 / Phase 22)** | Nativ | Nativ | Nativ | Nativ |
| Barrierefreiheits-Metadaten (Alt/Titel) | **Nativ (v1.3.0 / Phase 22)** | Nativ | Nativ | Nativ | Nativ |
| 7Z-Archiv-Unterstützung mit Magic-Bytes | **Nativ (v1.3.0 / Phase 22)** | Nur ZIP | Nur ZIP | Nur ZIP | Nur ZIP |
| **Mehrsprachigkeit & Internationalisierung** |
| Native Frontend-Mehrsprachigkeit (i18n) | **Nativ (12 Sprachen + SVG)** | Nur via Plugins (WPML) | Nativ | Nativ | Nativ (Kompakt & tief) |
| Vollständige Backend-Mehrsprachigkeit | **Nativ (DE/EN, v1.3.1)** | Nativ (Translations) | Nativ | Nativ | Nativ |
| Benutzerspezifische Standardsprache | **Nativ im Profil (v1.3.1)** | Nativ | Nativ | Nativ | Nativ |
| Login-Sprachwechsler | **Nativ (v1.3.1)** | Dropdown ab WP 5.9 | Nativ | Nativ | Nativ |
| Fallback-Kaskade bei fehlenden Texten | **Automatisch im Core** | Plugin-abhängig | Teils | Konfigurierbar | Konfigurierbar |
| **SEO & Webstandards** |
| Dynamische XML-Sitemap mit `hreflang` | **Nativ (`/sitemap.xml`)** | Nativ (ab 5.5, simpel)| Nativ | Nativ / Modul | Nativ / Extension |
| Dynamische `robots.txt` | **Nativ (`/robots.txt`)** | Virtuell nativ | Nativ | Nativ | Nativ |
| Strukturierte Daten (Schema.org / JSON-LD)| **Nativ (v1.3.0 / Phase 24)** | Meist SEO-Plugin nötig | Teils | Modul nötig | Extension nötig |
| OpenGraph, Twitter-Cards, Favicon-Tools | **Nativ im Kern** | Meist SEO-Plugin nötig | Nativ | Modul nötig | Extension nötig |
| **E-Commerce & Geschäftsprozesse** |
| Integrierter Shop & Lagerverwaltung | **Nativ als Kern-Plugin** | Nur via WooCommerce | Nur via HikaShop etc. | Nur via Drupal Commerce| Nur via Aimeos/Shop |
| Multi-Payment (Kreditkarte, PayPal, WERO) | **Nativ integriert** | Nur via Payment-Plugins| Nur via Payment-Plugins| Nur via Payment-Plugins| Nur via Payment-Plugins|
| Revisionssicheres WaWi-/Lager-Audit-Log | **Nativ im Core-Plugin** | Nur via ERP-Plugins | Nur via Dritt-Plugins | Nur via Enterprise-Mod.| Nur via Enterprise-Ext.|
| Buchungs-/Kurssystem | **Nativ im Kern** | Nur via Dritt-Plugins | Nur via Dritt-Plugins | Komplex nachzubauen | Komplex nachzubauen |
| Download-Portal & Klick-Counter | **Nativ im Kern** | Nur via Download-Plugin| Nur via Download-Plugin| Komplex nachzubauen | Komplex nachzubauen |
| Integriertes Community-Forum | **Nativ (`weave-forum`)** | Nur via bbPress / Forum | Nur via Kunena | Nativ (Core Forum) | Nur via Extension |
| **Sicherheit & Datenschutz (DSGVO)** |
| 100 % DSGVO ohne externe Tracker / CDNs | **Vollständig nativ** | Oft Drittanbieter nötig | Gut | Sehr gut | Exzellent |
| Trackerfreier Spamschutz (4 Verfahren) | **Nativ integriert** | Meist Google reCAPTCHA | Core/Captcha | Core/Captcha | Core/Form |
| Integrierte Brute-Force-Sperre & Headers | **Nativ im Core** | Meist Security-Plugin | Core | Core | Core |
| Strict HSTS, CSP & POSIX-Berechtigungs-Audit| **Nativ im Core (Phase 17)**| Meist Security-Plugin | Core | Core | Core |
| Trusted-Proxy & Anti-IP-Spoofing | **Nativ im Core (Phase 17)**| Meist Reverse-Proxy-Pl.| Teils | Nativ | Nativ |
| Rollenbasierte Rechteverwaltung (RBAC) | **Nativ (Admin/Mod/Edit)** | Nativ (5 Rollen) | Nativ (Sehr mächtig) | Nativ (Sehr mächtig) | Nativ (Enterprise) |
| Angriffsfläche & Wartungsaufwand | **Extrem gering** | Sehr hoch (Target #1) | Mittel | Gering (Enterprise) | Sehr gering |

---

## 3. Was hebt WeaveCMS ab? (Unsere Alleinstellungsmerkmale / USPs)

### 1. Dualer Hybrid-Speicher (Flat-File JSON & relationale SQL-Datenbanken)
- **Der Clou:** WeaveCMS läuft völlig autark auf jedem USB-Stick, Testserver oder Shared-Webspace ganz ohne Datenbankserver via `data.json`.
- Bei steigenden Anforderungen reicht ein Klick im Backend, um alle Daten verlustfrei in eine relationale **MySQL/MariaDB**- oder **SQLite**-Datenbank zu überführen.
- *Keines* der vier Vergleichssysteme beherrscht diese Dualität – dort ist eine relationale SQL-Datenbank zwingend Voraussetzung, was lokale Entwicklung, Backups und Serverumzüge verkompliziert.

### 2. Zero-Bloat, Null externe Laufzeit-Abhängigkeiten
- Keine 250 MB schweren `node_modules`- oder `vendor`-Ordner auf dem Produktionsserver.
- Reines, natives PHP (8.0+). Dadurch erreicht WeaveCMS Ladezeiten von unter 100 ms und Höchstwerte bei Google Lighthouse (Core Web Vitals 98–100) ganz ohne komplexe Caching-Layer.

### 3. All-in-One Ökosystem statt Plugin-Hölle
- Bei WordPress benötigt ein typischer Handwerksbetrieb mindestens 15–25 Plugins (WooCommerce, Formular-Plugin, Cookie-Banner, Caching, Backup, SEO, Translation, Security, Terminkalender, Mediathek-Tools). Jedes Update birgt das Risiko von Inkompatibilitäten und Sicherheitslücken.
- In WeaveCMS sind **Shop mit europäischem WERO-Zahlungssystem, Lagerbestand, Kurs-/Eventbuchung, Galerie, FAQs, Blog, Community-Forum, Postfach, Mediathek, SEO-Autopilot und 1-Klick-Backup** maßgeschneidert und interoperabel im Kern integriert.

### 4. Radikaler Datenschutz (DSGVO by Design)
- Keine standardmäßigen CDNs für Schriften oder Icons (alles 100 % lokal gehostet).
- Eigenes grafisches Symbol- & Canvas-Captcha ohne Datenübertragung an Google, Cloudflare oder andere Drittanbieter.
- Integrierter DSGVO-Mustergenerator und lokales Kontaktanfragen-Postfach mit E-Mail-Scraping-Schutz.

### 5. Native, browserunabhängige Mehrsprachigkeit (Frontend & Backend)
- Vollständige Frontend-Übersetzung für 12 europäische Sprachen mit automatischer Kaskade.
- Gestochen scharfe Inline-SVG-Flaggen verhindern fehlerhafte Buchstaben-Renderings (`DE DE`) auf Windows/Chromium-Plattformen.
- **Neu in v1.3.1:** Vollständige Lokalisierung aller Backend-Bereiche (DE/EN) und frei wählbare Standardsprache je Benutzerkonto.

### 6. Ausfallsicherer 1-Klick Core-Updater mit Auto-Rollback
- Automatischer Download, SHA-256-Verifikation und Integritätsprüfung direkt im Administrationsbereich.
- Pre-Update-Sicherungsarchiv (`backups/pre_update_...zip`) und atomarer Dateiaustausch unter temporärem Wartungsmodus; bei jedem Fehler greift der automatische Rollback.
- Absolute Datensicherheit: Nutzerdaten (`data.json`, `data/`, `images/`, `plugins/`, `config.local.php`) sind strikt geschützt und werden niemals überschrieben.

---

## 4. Neuprüfung der Feature-Gaps (Was wurde gelöst? Was steht noch an?)

### ✅ Erfolgreich geschlossene Lücken (Stand: v1.3.0 & v1.3.1)

1. **Lücke A: Zentrales Medien- & Asset-Management (GELÖST in v1.3.0 / Phase 22)**
   - Eigener Hauptreiter *„Mediathek“* mit Kachel- und Listenansicht, Suche, MIME-Filterung und Dateigrößenanzeige.
   - Automatische Next-Gen-Bildpipeline (`.webp`, `.avif`) und Skalierung (`thumb`, `medium`, `large`) via PHP `gd`.
   - Drag & Drop Multi-Upload, responsive `<picture>`-Code-Generierung und Barrierefreiheits-Metadaten (Alt-Texte, Bildtitel).
   - Unterstützung für 7Z-Archive mit strikter Magic-Bytes-Prüfung.

2. **Lücke B: Content-Versionierung & Entwurfsmodus (GELÖST in v1.3.0 / Phase 23)**
   - Granulares Status-Management (`published`, `draft`, `scheduled`) für Einzelseiten und Blogartikel.
   - Revisionshistorie mit bis zu 25 Snapshots in `data/revisions/`, farbcodiertem Unified-Diff-Viewer (`+` / `-`) und 1-Klick-Rollback.
   - Sichere Vorschau-Tokens (`?preview=...`) mit visuellem Frontend-Hinweisbanner für unfertige Entwürfe.

3. **Lücke C: SEO-Autopilot & Schema.org (GELÖST in v1.3.0 / Phase 24)**
   - Dynamische XML-Sitemap unter `/sitemap.xml` inklusive `hreflang`-Verknüpfung aller aktiven Sprachen.
   - Dynamische `/robots.txt` mit automatischer Sitemap-Referenz.
   - Vollständige strukturierte Daten nach Schema.org (JSON-LD) für `LocalBusiness`, `Product`, `Event/Course` und `BlogPosting`.

4. **Lücke D: 1-Klick Core-Updater im Backend (GELÖST in v1.3.0 / Phase 25)**
   - Direkte Aktualisierung per Knopfdruck aus dem Admin-Dashboard.
   - Integriertes Pre-Update-Backup, automatische Wartungsmodus-Steuerung und Rollback-Sicherheit.

5. **Lücke E: Personalisierter Action-Hub (GELÖST in v1.3.1 / Phase 26)**
   - Frei konfigurierbare Schnellzugriff-Kacheln auf dem Dashboard mit Rollenprüfung (RBAC) und Zähler-Badges.
   - 25+ kuratierte Aktionen und 1-Klick-Workflow-Presets (*Allrounder*, *Redakteur*, *Administrator*, *Minimal*).

6. **Lücke F: Vollständige Backend-Mehrsprachigkeit (GELÖST in v1.3.1)**
   - Lückenlose Lokalisierungskataloge für Deutsch und Englisch (`libs/lang/admin/de.json` und `en.json`).
   - Login-Sprachwechsler und benutzerspezifische Standardsprache (`admin_lang`) im Profil.
   - Schwebende Toolbar im Einzelseiten-Editor für bequeme Sprach-Tabs ohne Scrollaufwand.

---

### 🔍 Verbleibende & neu identifizierte Feature-Gaps (Fokus für v1.4.0+)

Auf Basis des aktuellen Wettbewerbsumfelds und Rückmeldungen aus Agenturprojekten verbleiben folgende wesentliche Handlungsfelder:

#### Lücke 1: Visueller Spalten- & Grid-Builder für Einzelseiten (Priorität: HOCH)
- **Ist-Zustand:** Einzelseiten und Blogartikel nutzen Markdown mit einer integrierten Design-Toolbar (Trennlinien, Info-Boxen, Überschriftstile).
- **Soll-Zustand (wie bei Gutenberg / Neos):**
  - Modularer Spalten-Builder direkt im Seiteneditor (z. B. 2-spaltig 50/50, 3-spaltig, 1/3 zu 2/3, Bild links / Text rechts).
  - Reine CSS-Grid/Flexbox-Struktur ohne externe CSS-Frameworks (z. B. `<div class="weave-grid-2">`).
  - Schnellauswahl vorgefertigter Content-Blöcke (Hero-Teaser, Testimonial-Card, Call-to-Action Box).

#### Lücke 2: Globale Taxonomien & Benutzerdefinierte Zusatzfelder / Custom Fields (Priorität: MITTEL)
- **Ist-Zustand:** Kategorien werden pro Inhaltsmodul separat verwaltet (Galerie-Kategorien vs. Shop-Kategorien vs. Blog-Tags).
- **Soll-Zustand (wie bei WP ACF / Drupal Fields):**
  - Modulübergreifendes Tagging-System für sektionsübergreifende Filter.
  - Dynamische Zusatzfelder für Seiten und Produkte (z. B. Checkbox „Bio-Zertifiziert“, Textfeld „Hersteller-Garantie“, Dateianhang „Technisches Datenblatt-PDF“).

#### Lücke 3: Zwei-Faktor-Authentifizierung (2FA via TOTP / App) (Priorität: MITTEL bis HOCH)
- **Ist-Zustand:** 2-Stufen-Login über Administrator-Passwort und 4-stelligen PIN-Code mit Brute-Force-Sperre und E-Mail-Alarmierung.
- **Soll-Zustand:**
  - Standardkonforme TOTP-Authentifizierung (RFC 6238) für Smartphone-Apps (Google Authenticator, Bitwarden, 1Password, Aegis).
  - QR-Code-Generator im Admin-Profil mit Notfall-Wiederherstellungscodes (Backup-Codes).

#### Lücke 4: Interaktiver Fokuspunkt-Selector für Medien (Priorität: GERING bis MITTEL)
- **Ist-Zustand:** Bilder werden proportional skaliert und als WebP/AVIF erzeugt.
- **Soll-Zustand:**
  - Visueller Klick-Fokuspunkt im Mediathek-Detailmodal (z. B. Gesicht oder Produkt im Bildzentrum fixieren).
  - Übergabe von CSS-Eigenschaften `object-position: X% Y%` an Bildkomponenten zur Verhinderung ungünstiger Beschnitte auf Smartphones.

#### Lücke 5: Headless REST-API Endpunkte & Webhook-Dispatcher (Priorität: GERING)
- **Ist-Zustand:** API existiert punktuell für Shop, Downloads, Captchas und AJAX-Aktionen.
- **Soll-Zustand:**
  - Strukturierte, token-authentifizierte REST-Read-Endpunkte (`/api/v1/pages`, `/api/v1/posts`, `/api/v1/products`) für Headless-Nutzung oder mobile App-Anbindungen.
  - Ausgehende Webhooks bei definierten Systemereignissen (z. B. `order.created`, `contact.received`).

---

## 5. Aktualisierte Aufgaben-Roadmap (Task-Backlog)

### 🎯 Abgeschlossene Meilensteine

- [x] **Paket 1: Medienverwaltung & Bild-Pipeline (v1.3.0 / Phase 22)**
  - [x] Zentraler Admin-Tab `tab-media` (Kachel-/Listenansicht, Dateigröße, Suche, Filter).
  - [x] Automatische WebP- und AVIF-Konvertierung beim Upload via PHP `gd`.
  - [x] Drag & Drop Multi-Upload für Medien mit Fortschrittsbalken.
  - [x] 7Z-Archiv-Unterstützung mit strikter Magic-Bytes-Signaturprüfung.
- [x] **Paket 2: Content-Revisions & Entwurfsmodus (v1.3.0 / Phase 23)**
  - [x] Status-Flag `status` (`draft` | `published` | `scheduled`) für Einzelseiten und Blogartikel.
  - [x] Revisionshistorie in `data/revisions/` mit farbigem Diff-Viewer und 1-Klick-Rollback.
  - [x] Vorschau-Modus für nicht veröffentlichte Entwürfe mit kryptografischem Preview-Token.
- [x] **Paket 3: SEO-Autopilot & Schema.org (v1.3.0 / Phase 24)**
  - [x] Dynamischer Controller `/sitemap.xml` mit automatischer `hreflang`-Verknüpfung aller Sprachen.
  - [x] Dynamische `/robots.txt` mit automatischer Sitemap-Referenz.
  - [x] Automatisches Rendering von `<script type="application/ld+json">` für Unternehmen, Produkte, Events und Blogbeiträge.
- [x] **Paket 4: 1-Klick-Core-Updater (v1.3.0 / Phase 25)**
  - [x] Endpunkt `action=run_core_update` im Admin-Dashboard (Download, SHA-256-Prüfung, Staging, Rollback-Sicherheit).
  - [x] Automatisches Pre-Update-Backup und Wartungsmodus-Banner während des Vorgangs.
- [x] **Paket 5: Action-Hub & Backend-Mehrsprachigkeit (v1.3.1 / Phase 26)**
  - [x] Dashboard Schnellzugriffe & Workflows mit RBAC-Rollenprüfung und 1-Klick-Presets.
  - [x] Vollständige DE/EN Lokalisierungskataloge mit über 1.050 synchronen Schlüsseln (100 % Parität aller 1.056 Schlüssel).
  - [x] Benutzerspezifische Standardsprache (`admin_lang`) im Profil mit automatischer Sitzungssynchronisation.
  - [x] Schwebende Toolbar im Einzelseiten-Editor für bequemen Sprachwechsel.
- [x] **Paket 6: Visueller Spalten- & Grid-Builder (v1.3.2 / Phase 27)**
  - [x] Grid-Layout-Komponente im Einzelseiten-Editor (Auswahl von 1- bis 3-spaltigen Layouts wie 50/50, 33/33/33, 1/3-2/3 per Klick).
  - [x] Vordefinierte Block-Vorlagen (Hero-Callout, Bild-Text-Split, Feature-Boxen) auf Basis reiner, schlanker CSS-Grids ohne externe Frameworks.
  - [x] Live-Vorschau-Umschaltung im Editor mit Responsive-Breakpoints (Desktop 1200px / Tablet 768px / Smartphone 375px).
- [x] **Paket 7: Globale Taxonomien & Custom Fields (v1.3.2 / Phase 28)**
  - [x] Sektionsübergreifendes Tagging-System für globale Filter und Taxonomie-Seiten (`/tag/{slug}`).
  - [x] Custom-Fields-Manager: Anlegen frei definierbarer Zusatzfelder (Text, Link, Zahl, Checkbox, Datei) für Einzelseiten und Produkte.
  - [x] Dynamische Ausgabe im Frontend und Schema.org-Mapping.

---

### 🚀 Zukünftige Entwicklungs-Pakete (Roadmap v1.4.0 – v1.5.0)

### 🎯 Paket 8: Zwei-Faktor-Authentifizierung (2FA via TOTP / App) (v1.4.0 / Phase 29)
- [ ] **Task 29.1:** Standardkonformer RFC 6238 TOTP-Generator (Pure PHP, ohne externe Bloat-Abhängigkeiten) für Authenticator-Apps (Google Authenticator, Bitwarden, 1Password, Aegis).
- [ ] **Task 29.2:** QR-Code-Generator & Einrichtungs-Assistent im Admin-Profil mit Validierung vor Aktivierung.
- [ ] **Task 29.3:** 8 Notfall-Wiederherstellungscodes (Backup-Codes) mit Einmal-Entwertung bei Geräteverlust.

### 🎯 Paket 9: Interaktiver Fokuspunkt-Selector für Medien (v1.5.0 / Phase 30)
- [ ] **Task 30.1:** Interaktiver Fokuspunkt-Selector im Mediathek-Modal mit Klick-Koordinaten (`focus_x`, `focus_y`).
- [ ] **Task 30.2:** Übergabe von `object-position: X% Y%` an Bildkomponenten zur Vermeidung ungünstiger Beschnitte auf Smartphones.
- [ ] **Task 30.3:** Live-Vorschau in verschiedenen Aspect-Ratios (16:9, 1:1, 9:16).

### 🎯 Paket 10: Headless REST-API Endpunkte & Webhooks (v1.5.0 / Phase 31)
- [ ] **Task 31.1:** Strukturierte, token-authentifizierte REST-Read-Endpunkte (`/api/v1/pages`, `/api/v1/posts`, `/api/v1/products`) für Headless-Nutzung.
- [ ] **Task 31.2:** Ausgehende Event-Webhooks mit HMAC-Signatur bei definierten Systemereignissen (`contact.received`, `order.created`, `post.published`).
- [ ] **Task 31.3:** Webhook-Ereignisprotokoll mit Retry-Funktion im Administrationsbereich.

---

## 6. Fazit

WeaveCMS hat mit dem Sprung auf **v1.3.0** und **v1.3.1** einen entscheidenden Reifegrad erreicht: Nahezu alle klassischen Schwachpunkte gegenüber etablierten CMS (fehlende Mediathek, fehlende Entwürfe/Revisions, manuelle Updates, fehlende XML-Sitemap) wurden systematisch und nativ im Kern gelöst.

Gleichzeitig bleibt WeaveCMS seiner Kernphilosophie treu: **Zero-Bloat, Pure PHP, minimale Serveranforderungen und kompromissloser Datenschutz**. Für KMU, Handwerker und Dienstleister stellt WeaveCMS damit heute eine der modernsten, sichersten und wartungsärmsten Alternativen am Markt dar.
