# WeaveCMS vs. WordPress, Joomla, Drupal & TYPO3
## Umfassende Funktionsanalyse, Alleinstellungsmerkmale (USPs) & Feature-Gap-Roadmap

> **Dokumenten-ID:** `DOC-ANALYSIS-2026-10-CMS-BENCHMARK`  
> **Status:** Arbeitsdokument zur Ableitung konkreter Entwicklungs- & Feature-Tasks  
> **Zielsystem:** WeaveCMS (v1.2.5+)  
> **Vergleichssysteme:** WordPress 6.x, Joomla 5.x, Drupal 10/11, TYPO3 v12/13  

---

## 1. Executive Summary & Positionierung

Im modernen Web-Ökosystem existiert eine wachsende Kluft zwischen gigantischen, oft überdimensionierten Enterprise-/Massen-Systemen (**WordPress, Drupal, TYPO3**) und modernen, leichtgewichtigen Lösungen.

WeaveCMS positioniert sich bewusst im Segment der **hochperformanten, wartungsarmen und datenschutzkonformen All-in-One CMS für KMU, Handwerk, Dienstleister und Portfolio-Websites**:

```
Enterprise / Behoerden            Agenturen / Publishing            Mittelstand / KMU & Handwerk
┌───────────────────────┐        ┌───────────────────────┐        ┌───────────────────────────────┐
│   TYPO3 / Drupal      │        │       WordPress       │        │          WeaveCMS             │
│  - Extreme Komplexitaet│        │  - 60.000+ Plugins    │        │  - Zero-Bloat, Pure PHP       │
│  - Symfony / Composer │        │  - Hohe Angriffsflaeche│       │  - Flat-File + SQL Hybrid     │
│  - Hoher Pflegeaufwand│        │  - Wartungsintensiv   │        │  - All-in-One ohne Plugin-Flut│
└───────────────────────┘        └───────────────────────┘        └───────────────────────────────┘
```

---

## 2. Detaillierter Feature-Matrix-Vergleich

| Funktionsbereich | **WeaveCMS** | **WordPress** | **Joomla** | **Drupal** | **TYPO3** |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Architektur & Laufzeit** |
| Zero-Dependency PHP (ohne Composer-Zwang) | **Ja (100 % Pure PHP)** | Nein (Composer/Vendor) | Nein (Composer) | Nein (Composer/Symfony) | Nein (Composer/Symfony) |
| Speicher-Engine (Dual-Storage) | **Hybrid (JSON & SQL)** | Nur SQL (MySQL) | Nur SQL (MySQL/PgSQL) | Nur SQL | Nur SQL |
| Server-Ressourcenverbrauch | **Minimal (< 15 MB RAM)** | Mittel bis Hoch | Mittel | Hoch | Sehr Hoch |
| Installationszeit | **< 30 Sekunden** | ~ 3–5 Min. | ~ 5–10 Min. | ~ 10–20 Min. | ~ 15–30 Min. |
| **Content-Management & Authoring** |
| Sektions-/Baukasten-Prinzip | **Nativ im Kern** | Via Plugins / FSE | Eingeschränkt | Via Layout Builder | Via Gridelements/Fluid |
| Visueller WYSIWYG / Block-Editor | Markdown + Toolbar | Gutenberg (Blocks) | TinyMCE | CKEditor 5 | CKEditor / RTE |
| Entwürfe / Versionierung / Revisions | *Teilweise / Geplant* | Nativ | Nativ | Nativ (Sehr stark) | Nativ (Workspaces) |
| Zeitgesteuertes Veröffentlichen | *Fehlt aktuell* | Nativ | Nativ | Nativ | Nativ |
| Globale Schlagworte (Tags/Taxonomie) | *Sektionsbezogen* | Nativ | Nativ | Extrem mächtig | Nativ |
| **Medien & Asset-Management** |
| Zentrale Mediathek (Media Library) | *Sektions-/Ordnerbezogen* | Nativ (Exzellent) | Nativ | Nativ | Nativ (FAL) |
| Automatische Bildkonvertierung (WebP/AVIF)| *Manuell / Upload* | Nativ / Plugins | Nativ | Image Styles | Nativ |
| Drag & Drop Mehrfach-Upload | *Teilweise* | Nativ | Nativ | Nativ | Nativ |
| **Mehrsprachigkeit & Internationalisierung** |
| Native Mehrsprachigkeit (i18n) | **Nativ (12 Sprachen + SVG)** | Nur via Plugins (WPML/Polylang)| Nativ | Nativ | Nativ (Kompakt & tief) |
| Backend-Mehrsprachigkeit | **Nativ (DE/EN mit Fallback)**| Nativ (Translations) | Nativ | Nativ | Nativ |
| Fallback-Kaskade bei fehlenden Texten | **Automatisch** | Plugin-abhängig | Teils | Konfigurierbar | Konfigurierbar |
| **E-Commerce & Geschäftsprozesse** |
| Integrierter Shop & Lagerverwaltung | **Nativ als Kern-Plugin** | Nur via WooCommerce | Nur via HikaShop etc. | Nur via Drupal Commerce| Nur via Aimeos/Shop |
| REST-APIs für POS/Kasse/WaWi | **Schlank & Token-basiert** | REST-API (überladen) | Nativ (v4+) | JSON:API / REST | Nativ |
| Buchungs-/Kurssystem | **Nativ im Kern** | Nur via Dritt-Plugins | Nur via Dritt-Plugins | Komplex nachzubauen | Komplex nachzubauen |
| **Sicherheit & Datenschutz (DSGVO)** |
| 100 % DSGVO ohne externe Tracker | **Vollständig nativ** | Oft Drittanbieter nötig | Gut | Sehr gut | Exzellent |
| Trackerfreier Spamschutz (Icon-Captcha) | **Nativ integriert** | Meist Google reCAPTCHA | Core/Captcha | Core/Captcha | Core/Form |
| Integrierte Brute-Force-Sperre & Headers | **Nativ im Core** | Meist Security-Plugin | Core | Core | Core |
| Angriffsfläche & Wartungsaufwand | **Sehr gering** | Sehr hoch (Target #1) | Mittel | Gering (Enterprise) | Sehr gering |

---

## 3. Was hebt WeaveCMS ab? (Unsere Alleinstellungsmerkmale / USPs)

### 1. Dualer Hybrid-Speicher (Flat-File JSON & relationale SQL-Datenbanken)
- **Der Clou:** WeaveCMS läuft völlig autark auf jedem USB-Stick, Testserver oder Shared-Webspace ganz ohne Datenbankserver via `data.json`.
- Bei steigenden Anforderungen reicht ein Klick im Backend, um alle Daten verlustfrei in eine relationale **MySQL/MariaDB**- oder **SQLite**-Datenbank zu überführen.
- *Keines* der vier Vergleichssysteme beherrscht diese Dualität – dort ist eine SQL-Datenbank zwingend Voraussetzung, was lokale Backups und Serverumzüge erschwert.

### 2. Zero-Bloat, Null externe Laufzeit-Abhängigkeiten
- Keine 250 MB schweren `node_modules` oder `vendor`-Ordner.
- Reines, modernes PHP (8.0+). Dadurch erreicht WeaveCMS Ladezeiten von unter 100 ms und Höchstwerte bei Google Lighthouse (Core Web Vitals) ohne teure Caching-Plugins.

### 3. All-in-One Ökosystem statt Plugin-Hölle
- Bei WordPress benötigt ein typischer Handwerksbetrieb mindestens 15–25 Plugins (WooCommerce, Formular-Plugin, Cookie-Banner, Caching, Backup, SEO, Translation, Security, Terminkalender). Jedes Update birgt das Risiko von Inkompatibilitäten und Sicherheitslücken.
- In WeaveCMS sind **Shop, Lagerbestand, Kurs-/Eventbuchung, Galerie, FAQs, Blog, Forum, Postfach und Backup** maßgeschneidert und interoperabel im Kern integriert.

### 4. Radikaler Datenschutz (DSGVO by Design)
- Keine standardmäßigen CDNs für Fonts (lokal gehostet).
- Eigenes grafisches Symbol-Captcha ohne Datenübertragung an Google oder Cloudflare.
- Integrierter DSGVO-Mustergenerator und lokales Kontaktanfragen-Postfach.

### 5. Native, browserunabhängige Mehrsprachigkeit
- Vollständige Übersetzung für 12 europäische Sprachen mit automatischer Fallback-Kaskade.
- Gestochen scharfe Inline-SVG-Flaggen verhindern Darstellungsfehler auf Windows/Chromium-Plattformen.

---

## 4. Was fehlt WeaveCMS im Vergleich? (Identifizierte Feature-Gaps)

Um WeaveCMS auf das nächste Level zu heben und für anspruchsvollere Agenturkunden attraktiv zu machen, wurden folgende Lücken gegenüber den etablierten Systemen identifiziert:

### Lücke A: Medien- & Asset-Management (Priorität: HOCH)
- **Ist-Zustand:** Bilder werden sektionsweise oder manuell hochgeladen und in `images/uploads/` gespeichert.
- **Soll-Zustand (wie bei WP / TYPO3 FAL):**
  - Eine zentrale Mediathek im Adminbereich (Filter nach Typ, Suchfunktion, Vorschau).
  - Automatische Generierung von modernen Web-Formaten (`.webp`, `.avif`) und responsiven Größen (`thumb`, `medium`, `large`).
  - Fokuspunkt-Auswahl für responsiven Zuschnitt.

### Lücke B: Content-Versionierung & Entwurfsmodus (Priorität: HOCH)
- **Ist-Zustand:** Änderungen werden sofort live in `data.json` oder SQL gespeichert.
- **Soll-Zustand (wie bei Drupal / TYPO3 Workspaces):**
  - Entwurfsstatus (`draft`, `review`, `published`).
  - Revisionshistorie: „Wer hat wann was geändert?“ mit 1-Klick-Wiederherstellung.
  - Zeitgesteuerte Veröffentlichung (z. B. Kurs oder Rabattaktion startet automatisch am 1. des Monats).

### Lücke C: Visueller Block-Editor & Freiform-Layouts (Priorität: MITTEL)
- **Ist-Zustand:** Strukturierte Sektionen mit Markdown + Toolbar-Badges.
- **Soll-Zustand (wie bei Gutenberg / Neos):**
  - Flexibler Drag & Drop Grid-Builder für Einzelseiten (z. B. 2-Spalten, 3-Spalten, Bild links / Text rechts).
  - Visuelle Live-Vorschau direkt im Editor ohne Tab-Wechsel.

### Lücke D: Globale Taxonomien & Custom Fields (Priorität: MITTEL)
- **Ist-Zustand:** Kategorien sind pro Modul getrennt gepflegt (z. B. Shop-Kategorien vs. Galerie-Kategorien).
- **Soll-Zustand (wie bei WP ACF / Drupal Taxonomy):**
  - Zentrale Tag- & Kategorien-Verwaltung.
  - Möglichkeit, Artikeln oder Seiten individuelle Zusatzfelder hinzuzufügen (z. B. Checkbox „Öko-zertifiziert“, Textfeld „Hersteller-Garantie“).

### Lücke E: 1-Klick-Updates im Backend (Priorität: MITTEL)
- **Ist-Zustand:** Version-Check vorhanden; Update-Pakete werden manuell entpackt oder via Skript übertragen.
- **Soll-Zustand (wie bei WordPress):**
  - Administrator klickt auf „Jetzt auf v1.2.6 aktualisieren“ -> WeaveCMS erstellt automatisches Backup und installiert die Core-Dateien direkt im laufenden Betrieb.

### Lücke F: Erweiterte SEO-Automatisierung (Priorität: GERING bis MITTEL)
- **Ist-Zustand:** Meta-Tags, OpenGraph und Favicon konfigurierbar.
- **Soll-Zustand:**
  - Automatische Generierung einer `sitemap.xml` und `robots.txt` im Root.
  - Automatische JSON-LD Structured Data Schema für Google (z. B. Schema `LocalBusiness`, `Product`, `Event`).

---

## 5. Konkrete Aufgaben-Roadmap (Task-Backlog)

Aus dieser Analyse lassen sich folgende strukturierte Arbeitspakete für die nächsten Entwicklungszyklen ableiten:

### 🎯 Paket 1: Medienverwaltung & Bild-Pipeline (v1.3.0)
- [ ] **Task 1.1:** Neuer Admin-Tab `tab-media` (Zentrale Mediathek mit Kachelansicht, Dateigröße, Suche).
- [ ] **Task 1.2:** Integrierte WebP-Konvertierung beim Upload via PHP `gd` / `imagewebp()`.
- [ ] **Task 1.3:** Drag & Drop Multi-Upload für Medien.

### 🎯 Paket 2: Content-Revisions & Entwurfsmodus (v1.4.0)
- [ ] **Task 2.1:** Status-Flag `status` (`draft` | `published` | `scheduled`) für Einzelseiten und Blogartikel.
- [ ] **Task 2.2:** Revisions-Historie in `data/history/` oder Tabelle `revisions` mit Diff-Ansicht und Rollback.
- [ ] **Task 2.3:** Vorschau-Modus für nicht veröffentlichte Entwürfe (über temporären Vorschau-Token).

### 🎯 Paket 3: SEO-Autopilot & Schema.org (v1.4.5)
- [ ] **Task 3.1:** Dynamischer Controller für `/sitemap.xml` mit Berücksichtigung aller aktiven Sektionen und Sprachen.
- [ ] **Task 3.2:** Automatisches Rendering von `<script type="application/ld+json">` für Produkte, Events und Handwerksbetriebe.

### 🎯 Paket 4: 1-Klick-Core-Updater (v1.5.0)
- [ ] **Task 4.1:** Endpunkt `action=apply_core_update` im UpdateManager (Download, Hash-Prüfung, Auto-Backup, Extraktion).
- [ ] **Task 4.2:** Wartungsmodus-Banner während des Update-Vorgangs.

---

## 6. Fazit

WeaveCMS muss und soll **kein** zweites Drupal oder TYPO3 werden. Die immense Stärke von WeaveCMS liegt in seiner **kompakten Eleganz, Wartungsfreiheit und enormen Ausführungsgeschwindigkeit**.

Indem wir gezielt die wenigen echten Schwachstellen (Zentrale Mediathek, Entwurfsstatus, XML-Sitemap) schließen, ohne den Kern mit Dritt-Abhängigkeiten aufzublähen, wird WeaveCMS zur **überlegenen Alternative für kleine und mittelständische Unternehmen**, die von überladenen WordPress-Installationen überfordert sind.
