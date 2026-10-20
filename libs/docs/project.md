# Biankas Flechtglück & Flechtkunst Projekt

## ⚠️ STRIKTE ENTWICKLUNGS- & DESIGN-GOVERNANCE
- **ABSOLUTES VERBOT:** Kein Menü – weder im Frontend (Desktop-Header, Mobilnavigation, Slide-in-Menüs für Kontakt & Impressum) noch im Backend (Admin-Header, Reiter-Navigation) – darf **ohne vorherige ausdrückliche Zustimmung des Benutzers** geändert, verschoben, umbenannt oder entfernt werden!

---

Eine Webseite über Flechten, fertige und neue Projekte.
eine Scrollseite, wo alles auf einer Seite ist, die Sprunmarken geben mir Links um direkt zu einer Sektion zu springen.
web und mobile Desing
Kontakt und Impressum als CSS menü lassen wir von rechts einblenden
AdminSeite zum verwalten der Texte, Termine sollen hinzufügbar sein. Bild hochladen möglich sein.
KEIN login oder user system. ich möchte keine nutzer anlegen können.
externe Libraries möglich, aber bevorzugt eigene Codes verwenden.

Die Seite wird als fertiges Html paket ausgeliefert, wo alles enthalten ist. JS, CSS, Bilder.


Ich möchte auf zwei Webseiten zugreifen können. Eine für meine Flechtenprojekte und eine für meine Projekte. Ich möchte nicht immer angemeldet sein. Es sollen keine Cookies oder Sessions verwendet werden. Es soll keine externe Software oder Cloud verwendet werden.

Auf der Flechtenseite sind zwei Admin-Funktionen: Text ändern und Projekt hinzufügen. Der Login soll über die URL erfolgen, indem ein Token in der URL enthalten ist. ZB.: Flechten/admin?token=xyz123

In den Projects sind drei Admin-Funktionen: Projekte bearbeiten und neue Projekte hinzufügen, Kontaktseite bearbeiten, und eine Seite um neue Termine für die Kurse hinzufügen.

Auf allen Admin Seiten sollen alle Texte editierbar sein. Fotos hochgeladen werden können und Termine hinzugefügt werden können. In den Projekten sollen verschiedene Fotos hinzugefügt werden können und Bilder neu sortiert werden können. Im Falle eines Termins ist immer das aktuelle Jahr entscheidend. Die Termine sollen nicht über eine feste URL erreichbar sein, sondern nur über die Adminseite.

Auf der Startseite von Projects soll ein Feld für die neuesten Blogs in Markdown vorhanden sein. Dieses kann dann in Markdown über die Adminseite bearbeitet werden.

Die Webseite soll möglichst modern aussehen. Es sind keinerlei grafische Designs oder Layouts vorgegeben.

Auf allen Seiten sollen Bilder in einer angemessenen Größe angezeigt werden. Es soll keine unnötigen Animationen oder Effekte verwendet werden. Es sollen keine externen Server oder Dienste verwendet werden.

Die Webseite soll sowohl für Desktop, als auch für Mobiltelefone optimiert sein. Sie sollen möglichst schnell sein.

Inhaberin: Bianka Wischnewski, 19077 Rastow, Kraaker Straße 14, Telefon: +49 173 7602685, E-Mail: [EMAIL_ADDRESS]

Admin Seite:
bianka.wischnewski

Admin Token: [PASSWORD]

## Lokale Entwicklungs-Links (XAMPP)

- **Hauptportal (Auswahl):** http://localhost/flecht_bianka/
- **Flechtkunst (öffentlich):** http://localhost/flecht_bianka/flechten/
- **Flechtkunst (Admin):** http://localhost/flecht_bianka/flechten/admin/?token=xyz123
- **Kreative Projekte (öffentlich):** http://localhost/flecht_bianka/projekte/
- **Kreative Projekte (Admin):** http://localhost/flecht_bianka/projekte/admin/?token=xyz123

---

## 🎨 Machbarkeitsanalyse & Konzeption: Dynamisches Design- & Styling-System (Admin-Einstellungen)

### 1. Ausgangslage & Technische Grundlage
Durch die vorangegangene Modularisierung (`base.css`, `header.css`, `hero.css`, `gallery.css` etc.) basiert die gesamte Website bereits auf standardisierten **CSS Custom Properties (CSS Variables)** im `:root`-Bereich:
- **Farb-Tokens:** `--bg-dark`, `--bg-card`, `--border-color`, `--text-primary`, `--text-secondary`, `--primary`, `--accent`
- **Abmessungen & Layout:** Container-Breite (`.container`), Karten-Radien (`border-radius`), Raster-Gaps, typografische Skalierungen (`clamp()`)

**Technischer Vorteil:**
Es müssen keine externen CSS-Dateien dynamisch auf der Festplatte umgeschrieben oder komplexe Compiler bemüht werden. Die gewünschten Werte werden zentral in `data.json` unter `$data['settings']['design']` gespeichert und beim Laden von `index.php` im `<head>` als schlanker, dynamischer `:root`-Block injiziert.

---

### 2. Detaillierte Untersuchungsbereiche & Einstellungsoptionen

#### A. Seitenbreite & Inhaltsrahmen
- **Container Max-Width:**
  - *Kompakt (1050 px):* Sehr fokussierter Lesefluss, ideal für ruhige, handwerkliche Webseiten.
  - *Standard (1200 px - aktuell):* Bewährtes Gleichgewicht zwischen Bildwirkung und Lesbarkeit.
  - *Großzügig (1400 px):* Moderne, weite Studio-Ästhetik für hochauflösende Bildschirme.
- **Hero-Kartenbreite:**
  - *Kompakt (740 px)* | *Standard (820 px - aktuell)* | *Breit (960 px)*

#### B. CSS-Seitenfarben & Naturtöne
- **Kuratierte Natur-Paletten (1-Klick-Auswahl für harmonische Ästhetik):**
  - *Weidenglück (Standard):* Leinencreme (`#fbf9f6`), Waldgrün (`#2e6248`), Weidenton (`#b26c39`)
  - *Moor & Sand:* Sandcreme (`#f7f4ee`), Moosbraun (`#4a3f35`), Bernstein (`#d97706`)
  - *Nordisch Klar:* Hellgrau/Leinen (`#f4f6f5`), Salbeigrün (`#3a6351`), Schiefer (`#2c3e50`)
  - *Warmes Atelier:* Elfenbein (`#faf7f2`), Terrakotta (`#a85832`), Espresso (`#322822`)
- **Freie Farb-Picker (für individuelle Akzente):**
  - Seitenhintergrund (`--bg-dark`)
  - Haupt-/Markenfarbe (`--primary` - Buttons, Links, aktive Filter)
  - Sekundär-/Akzentfarbe (`--accent` - Highlights, Icons)
  - Kartenhintergrund (`--bg-card` - Zarter Farbton / Glassmorphism-Hintergrund)

#### C. Schriftgrößen & Typografie
- **Basis-Schriftgröße (skaliert fließend über das gesamte Layout):**
  - *Kompakt (95% / ~15px)* | *Standard (100% / 16px)* | *Komfortabel (108% / ~17.5px)*
- **Hero-Überschriften-Präsenz:**
  - *Dezent (etwas kleiner)* | *Standard (ausgewogen)* | *Plakativ (großzügig)*

#### D. Galerie-Feinabstimmung
- **Kachel-Eckenabrundung (Border-Radius):**
  - *Dezent (10 px)* | *Organisch abgerundet (20 px - aktuell)* | *Soft (28 px)*
- **Raster-Abstand (Spacing / Gap):**
  - *Kompakt (1.25 rem / 20 px)* | *Ausgewogen (2 rem / 32 px - aktuell)* | *Luftig (2.75 rem / 44 px)*
- **Karten-Stil:**
  - *Schwebend mit Schatten (aktuell)* | *Flach mit klarem Rahmen* | *Vollflächig minimalistisch*

---

### 3. Aufwandsschätzung & Vergleich der Umsetzungsvarianten

| Variante | Funktionsumfang | Zeitaufwand | Stabilität & Pflege |
| :--- | :--- | :---: | :--- |
| **Variante 1: Kuratierte Preset-Karten** | Vordefinierte Natur-Themes, 3 Breitenstufen, 3 Kachel-Radien per Klickkarte (analog zur Layout- & Logo-Auswahl). | **Gering**<br>*(ca. 1,5 – 2 Std.)* | **Sehr hoch (100% narrensicher):** Layout und Farbkontraste können niemals visuell „zerschossen“ werden. |
| **Variante 2: Freier Customizer** | Vollständige Colorpicker für alle Farbtoken, manuelle Pixel-/Rem-Zahleneingaben für Breiten und Abstände. | **Mittel**<br>*(ca. 3,5 – 4,5 Std.)* | **Mittel:** Maximale Freiheit, erfordert jedoch Gespür für Schriftgrößen und WCAG-Farbkontraste. |
| **Variante 3: Hybrid-Modell (Empfohlen)** | 1-Klick-Naturthemen & Breiten-Presets für schnelles Umschalten, ergänzt um optionale Detail-Farbwähler. | **Überschaubar**<br>*(ca. 2,5 – 3 Std.)* | **Optimal:** Vereint sofortige visuelle Harmonie mit gezielten Anpassungsmöglichkeiten. |

---

### 4. Durchgeführte Implementierung (Hybrid-Modell erfolgreich umgesetzt)
- **Sicherheits-Backups:** Vollständiges Vorab-Backup aller berührten Dateien in `backups/backup_design_hybrid/`.
- **Standard-Design erhalten:** Das bisherige Farbschema und Layout (Leinenweiß `#fbf9f6`, Waldgrün `#2e6248`, Weidenton `#b26c39`, 1200 px Breite, 20 px Radien) ist als Standardthema *„Weidenglück“* unverändert aktiv geblieben.
- **Backend Admin-Einstellungen (`admin/index.php` in `#tab-settings`):**
  1. *1-Klick Natur-Themes:* Weidenglück (Standard), Moor & Bernstein, Nordisch Klar, Warmes Atelier, Individuell.
  2. *Detailfarbwähler:* Hauptfarbe, Akzentfarbe, Hintergrundfarbe mit Live-Hex-Anzeige.
  3. *Seitenbreite:* Kompakt (1050 px), Standard (1200 px), Großzügig (1400 px).
  4. *Schriftgröße:* Kompakt (95%), Standard (100%), Komfortabel (108%).
  5. *Galerie-Kacheln:* Eckenabrundung (10 px / 20 px / 28 px) und Raster-Abstand (20 px / 32 px / 44 px).
- **Frontend-Injektion (`index.php`):** Dynamische Injektion des `<style id="dynamic-design-variables">:root { ... }</style>`-Blocks im `<head>`.
- **Menü-Governance:** Alle Front- und Backend-Menüs blieben zu 100% unverändert.

---

## 🚀 Roadmap zum vollwertigen Content Management System (CMS)

### Zielsetzung
Die Website soll ohne Änderung des bestehenden Seitenaufbaus, Layouts oder der Menüstruktur zu einem 100 % autarken, wartungsfreien Flat-File CMS ausgebaut werden. Alle Textinhalte, Metadaten und Datensicherungen sollen von Bianka ohne Code-Eingriffe im Adminbereich verwaltet werden können.

---

### Arbeitsablauf: Schrittweise Umsetzung (Punkte 1 bis 10)

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

### Punkt 1: Rechtliche Vollständigkeit & Dynamische Abschnittstexte (Priorität: Sehr hoch)
*Ziel: 100 % Text-Autonomie über `data.json` & rechtssichere DSGVO-Datenschutzerklärung.*

- [ ] **1.1 Datenmodell in `data.json` erweitern (`texts`):**
  - `gallery_title`: Titel der Galerie-Sektion (Standard: *"Galerie & Handarbeit"*)
  - `gallery_subtitle`: Einleitungssatz der Galerie (Standard: *"Ein Auszug aus meinen handgeflochtenen Korbwaren, Rankhilfen, Zäunen und dekorativer Naturflechterei."*)
  - `courses_title`: Titel der Kurs-Sektion (Standard: *"Flechtkurse & Workshops"*)
  - `courses_subtitle`: Einleitungssatz der Kurse (Standard: *"Lerne das traditionelle Korbflechten und die Weidenverarbeitung in gemütlicher Werkstatt-Atmosphäre selbst kennen."*)
  - `past_courses_title`: Titel des Rückblicks (Standard: *"Eindrücke aus vergangenen Kursen"*)
  - `contact_intro`: Einleitungstext im Kontakt-Slide-in (Standard: *"Hast du Fragen zu meinen Kursen, möchtest einen Gutschein erwerben oder ein individuelles Weidengeflecht anfragen?"*)
  - `privacy_policy`: Vollständiger Text der Datenschutzerklärung (DSGVO)
- [ ] **1.2 Adminbereich (`admin/index.php`):**
  - Im Reiter *„Startseite & Über mich“* Eingabefelder für Galerie-, Kurs- und Kontakt-Einleitungen ergänzen.
  - Im Reiter *„Impressum & Kontaktdaten“* ein mehrzeiliges Textfeld für die *Datenschutzerklärung (DSGVO)* integrieren.
  - Speichervorgang in `action === 'update_texts'` bzw. `update_impressum` erweitern.
- [ ] **1.3 Frontend-Ausgabe (`index.php`):**
  - Feste Textstellen in `index.php` durch dynamische PHP-Variablen `$data['texts'][...]` ersetzen.
  - Im Impressum-Slide-in einen separaten Unterabschnitt oder Reiter für die *Datenschutzerklärung* abbilden, ohne das Menü zu verändern.
- [ ] **1.4 Prüfung & Abnahme:**
  - Texte im Adminbereich ändern, speichern und im Frontend auf korrekte Darstellung prüfen.

---

### Punkt 2: Datensicherheit & Backup-System (Priorität: Hoch)
*Ziel: Schutz vor Datenverlust durch 1-Klick-Sicherung und einfache Wiederherstellung.*

- [ ] **2.1 Backend-Logik für Backup-Export (`admin/index.php`):**
  - Download-Aktion `action === 'export_backup'`: Erzeugt einen direkten Download der aktuellen `data.json` mit Zeitstempel im Dateinamen (z. B. `flecht_bianka_backup_2026-10-20.json`).
  - Optionaler Voll-Backup-Export: Erzeugt eine ZIP-Datei mit `data.json` + dem gesamten Ordner `images/uploads/`.
- [ ] **2.2 Backend-Logik für Backup-Import (Wiederherstellung):**
  - Upload-Feld für eine `.json`-Datei mit automatischer Schema- und JSON-Validierung.
  - Sicherheitsabfrage vor dem Überschreiben der aktuellen Daten.
- [ ] **2.3 Admin-UI Integration:**
  - Im bestehenden Reiter *„Sicherheit“* einen neuen Bereich *„Datensicherung & Backups“* anfügen.
  - Schaltflächen: *„Aktuelle Daten sichern (JSON herunterladen)“* und *„Sicherung wiederherstellen“*.
- [ ] **2.4 Prüfung & Abnahme:**
  - Backup herunterladen, Teständerung durchführen, Backup wiederherstellen und Datenintegrität verifizieren.

---

### Punkt 3: SEO & Social Sharing Metadaten (Priorität: Mittel)
*Ziel: Optimale Darstellung bei Google-Suchergebnissen und Link-Vorschauen in WhatsApp, Signal & Social Media.*

- [ ] **3.1 Datenmodell in `data.json` (`settings` / `seo`):**
  - `meta_title`: Seitentitel für Browser-Tab und Google
  - `meta_description`: Kurzbeschreibung für Suchmaschinen (ca. 150–160 Zeichen)
  - `meta_keywords`: Relevante Suchbegriffe
  - `og_image`: Vorschaubild-Pfad für Link-Teilen (Open Graph)
- [ ] **3.2 Adminbereich (`admin/index.php`):**
  - Im Reiter *„Darstellung“* oder *„Startseite“* einen kompakten Bereich *„Suchmaschinen & Social-Media-Vorschau (SEO)“* bereitstellen.
  - Inklusive Live-Snippet-Vorschau (wie der Link bei Google oder WhatsApp aussehen wird).
- [ ] **3.3 Frontend-Ausgabe (`index.php`):**
  - Dynamische `<title>`, `<meta name="description">`, `<meta name="keywords">` einbinden.
  - Open-Graph-Tags (`og:title`, `og:description`, `og:image`, `og:url`) im `<head>` integrieren.
- [ ] **3.4 Prüfung & Abnahme:**
  - Quelltext der Startseite prüfen und Validierung der Meta-Tags simulieren.

---

### Punkt 4: Lokales Kontaktanfragen-Protokoll (Priorität: Optional / Komfort)
*Ziel: Keine Kundenanfrage geht verloren – auch wenn Mailserver, Spam-Ordner oder SMTP streiken.*

- [ ] **4.1 Datenmodell für Anfragen:**
  - Struktur in `data.json` (oder separater `contacts.json` zum Schutz vor Datenaufblähung):
    `id`, `datum`, `name`, `email`, `phone`, `nachricht`, `typ` (Allgemein / Kursanfrage), `status` (neu / gelesen / beantwortet).
- [ ] **4.2 Speicherlogik bei Formularabsendung (`send_mail.php`):**
  - Jede erfolgreich validierte Kontaktanfrage wird mit Zeitstempel und IP-freiem Datenschutzstandard im JSON-Log abgelegt.
- [ ] **4.3 Admin-Ansicht (`admin/index.php`):**
  - Übersichtlicher Bereich mit Tabelle aller eingegangenen Nachrichten.
  - Filter-/Aktionsmöglichkeiten: *Gelesen markieren*, *Löschen*, *Direkt per Mail antworten*.
- [ ] **4.4 Prüfung & Abnahme:**
  - Testanfrage über das Kontaktformular absenden und Übernahme in die Admin-Tabelle prüfen.

---

### Punkt 5: Webspace-Installationsroutine & 1-Klick-Rollout (Priorität: Strategisch / CMS-Distributionsbasis)
*Ziel: Schmerzfreie, automatisierte Erstinstallation auf beliebigem Webhosting (Shared Hosting bei Strato, IONOS, Hetzner, All-Inkl etc.) mit minimalen, leicht ersetzbaren Muster-Beispieldaten.*

- [ ] **5.1 Vorlagen- & Beispieldaten-Architektur (`data.sample.json`):**
  - Erstellung einer sauberen Vorlagendatei `data.sample.json` als Installations-Master.
  - Befüllung mit minimalen, selbsterklärenden Beispieldaten (1 Muster-Werkstück in der Galerie, 1 Muster-Kurs, Standard-Farbschema *„Weidenglück“*, generische Platzhalter für Impressum & Kontakt).
  - Alle Beispielinhalte sind so angelegt, dass sie im Adminbereich mit einem Klick überschrieben oder gelöscht werden können.
- [ ] **5.2 Installationsassistent (`install.php`):**
  - **Schritt 1: System- & Umgebungsprüfung (Health Check):**
    - PHP-Version prüfen (mindestens PHP 8.0+ erforderlich).
    - Dateischreibrechte im Stammverzeichnis (für die Erstellung von `data.json`) prüfen.
    - PHP-Erweiterungen prüfen (`openssl` für SMTP, `gd` für Bildverarbeitung, `mbstring`, `json`, `fileinfo`).
  - **Schritt 2: Administrator-Ersteinrichtung:**
    - Festlegung des Master-Passworts für den Adminbereich (sicheres Bcrypt-Hashing).
    - Festlegung der 4-stelligen Sicherheits-PIN.
    - Eingabe der primären Betreiber-E-Mail-Adresse.
  - **Schritt 3: Basisdaten der Website:**
    - Eingabe von Websitetitel / Inhabername (z. B. *"Muster Flechtatelier"*).
    - Optionale Auswahl: *„Mit Beispielinhalten initialisieren“* (Empfohlen) oder *„Leere Website starten“*.
- [ ] **5.3 Automatische Verzeichnisstruktur & Sicherheits-Absicherung:**
  - Automatisches Anlegen des Upload-Ordners `images/uploads/` falls noch nicht vorhanden.
  - Platzierung einer `.htaccess`-Datei im Upload-Ordner, die jegliche Skriptausführung (`.php`, `.phtml`, `.cgi`) strikt unterbindet (effektiver Schutz gegen Webspace-Schadcode).
  - Erstellung von `data.json` mit den initialisierten Zugangsdaten und Vorlagentexten.
- [ ] **5.4 Installations-Abschluss & Selbstsicherung (Self-Locking):**
  - Sobald die Installation abgeschlossen ist, wird eine Sperrdatei `installed.lock` erzeugt.
  - Bei erneutem Aufruf von `install.php` wird der Zugriff verweigert und auf die Login-Seite verwiesen.
  - Optionale automatische Umbenennung/Deaktivierung von `install.php` zur Maximierung der Serversicherheit.
  - Direkte Weiterleitung zum fertigen Administrationsbereich.
- [ ] **5.5 Prüfung & Abnahme:**
  - Test-Rollout in einem frischen Unterverzeichnis oder Testumgebung ohne vorhandene `data.json`.
  - Durchlauf des Setup-Wizards von Schritt 1 bis Login und Verifizierung der erzeugten Konfiguration.

---

### Punkt 6: Modulare Seitenarchitektur, Sektions-Typen & Seitenreihenfolge (Priorität: CMS-Kernfunktion)
*Ziel: Die Reihenfolge der Abschnitte/Seiten frei verändern, Abschnitte ein-/ausblenden und Inhalte anhand vordefinierter Sektions-Typen flexibel zusammenstellen und befüllen.*

- [ ] **6.1 Datenmodell für modulare Sektionen (`sections` in `data.json`):**
  - Definition einer flexiblen Sektionsliste in `data.json`. Jede Sektion besitzt:
    - `id`: Eindeutiger Bezeichner / Sprungmarke (z. B. `hero`, `gallery`, `courses`, `past`, `about`, `custom_1`)
    - `type`: Art/Typ der Sektion (bestimmt das Ausgabe-Template und die Formularmaske)
    - `title`: Sektionsüberschrift
    - `nav_label`: Bezeichnung im Menü (z. B. *"Galerie"*, *"Flechtkurse"*, *"Über mich"*)
    - `visible`: `true` / `false` (Schalter zum schnellen Deaktivieren/Verbergen ohne Löschen)
    - `order`: Numerischer Sortierindex (1, 2, 3...)
  - **Katalog der Sektions-Typen:**
    1. **Typ `hero`:** Hero-Bühne (Große Überschrift, Untertitel, Call-to-Action, Logo-Einbindung)
    2. **Typ `gallery`:** Bildergalerie mit Kategoriefilter, Layoutvarianten und Lightbox
    3. **Typ `courses`:** Kurskalender mit Terminen, Preisen, freien Plätzen und Einladungslinks
    4. **Typ `past_courses`:** Foto-Impressionen / Bilder-Rückblick vergangener Workshops
    5. **Typ `text_about`:** Über-mich- / Vorstellungsprofil (Foto, Fließtext, persönliche Philosophie)
    6. **Typ `custom_content`:** Freie Inhaltsseite (beliebiger Textblock, Markdown, Zwischenüberschriften, z. B. *"Häufige Fragen (FAQ)"* oder *"Materialien & Weidenherkunft"*)
- [ ] **6.2 Sektions- & Reihenfolge-Manager im Adminbereich (`admin/index.php`):**
  - Visuelle Übersicht aller aktiven und inaktiven Sektionen der Website.
  - **Reihenfolge ändern:**
    - Schaltflächen *„Nach oben“* / *„Nach unten“* (oder Drag & Drop) zum Verschieben einzelner Abschnitte (z. B. *„Über mich“* vor die Galerie setzen oder *„Flechtkurse“* ganz nach oben).
  - **Sichtbarkeit steuern:**
    - Sofortiger Ein-/Aus-Kippschalter (z. B. Kursbereich außerhalb der Saison vorübergehend verbergen, ohne Daten zu löschen).
  - **Neue Sektionen hinzufügen:**
    - Dialog: Sektions-Typ auswählen (z. B. Neuer Freitext-Abschnitt), Menü-Namen vergeben und an gewünschter Position einhängen.
- [ ] **6.3 Typ-spezifische Inhalts-Editoren:**
  - Jede Sektion verweist auf ihre spezifischen Daten oder bietet ein passendes Bearbeitungsformular (Galerie-Uploads, Kurstermine oder Textfeld).
  - Konsistente Struktur: Redakteure sehen genau die Eingabefelder, die für den jeweiligen Sektions-Typ relevant sind.
- [ ] **6.4 Dynamisches Rendering im Frontend (`index.php`):**
  - `index.php` rendert die Inhaltsblöcke modular in einer Schleife anhand der in `data.json` definierten Reihenfolge:
    ```php
    foreach ($data['sections'] as $section) {
        if (!$section['visible']) continue;
        render_section($section['type'], $section);
    }
    ```
  - **Menü-Synchronisation:** Die Sprungmarken-Navigation im Desktop-Header und im Mobilmenü passt sich automatisch der definierten Reihenfolge und den sichtbaren Sektionen an (unter strikter Einhaltung des bestehenden Designs).
  - Fixe System-Overlays (Kontakt-Slide-in, Impressum/Datenschutz-Slide-in, Footer) bleiben wie gewohnt als universelle globale Elemente erhalten.
- [ ] **6.5 Prüfung & Abnahme:**
  - Sektionsreihenfolge im Admin ändern (z. B. Kurse nach oben schieben) und prüfen, ob Frontend und Menü-Scroll-Links synchron reagieren.
  - Eine Sektion ausblenden (`visible: false`) und prüfen, dass sie im Frontend verschwindet und im Menü nicht mehr verlinkt wird.
  - Neuen Sektions-Typ anlegen, mit Inhalt füllen und Darstellung verifizieren.

---

### Punkt 7: Flexibler Speicher-Treiber (Datenbankauswahl vs. Lokale JSON-Datei)
*Ziel: Wahlfreiheit zwischen einer SQL-Datenbank (MySQL / MariaDB / SQLite) für maximale Performance & Skalierbarkeit und einer lokalen JSON-Datei für einfache, datenbanklose Webspaces.*

- [ ] **7.1 Speicher-Abstraktionsschicht (Data Storage Interface):**
  - Definition eines universellen Storage-Interfaces in nativem PHP:
    `loadData(): array`, `saveData(array $data): bool`, `driverName(): string`, `ping(): bool`.
  - **Treiber A: `JsonStorageDriver` (Standard & Fallback):**
    - Liest und schreibt atomar in `data.json`.
    - Funktioniert out-of-the-box auf absolut jedem Webspace ohne Datenbankeinrichtung.
  - **Treiber B: `PdoSqlStorageDriver` (MySQL / MariaDB):**
    - Verbindung über PHP PDO zu MySQL oder MariaDB.
    - Strukturierte Speicherung in relationalen Tabellen mit konfigurierbarem Präfix (z. B. `fb_settings`, `fb_texts`, `fb_gallery`, `fb_courses`, `fb_past_courses`, `fb_sections`, `fb_contacts`).
  - **Treiber C: `SqliteStorageDriver` (Optionale lokale SQL-Datei):**
    - Lokale dateibasierte SQL-Datenbank (`data/app.sqlite`), vereint SQL-Syntax mit der Einfachheit einer Datei.
- [ ] **7.2 Auswahl im Installationsassistenten (`install.php`):**
  - Schritt zur Auswahl der gewünschten Speicher-Engine:
    - *Option 1: Dateibasiert (Flat-File JSON)* – Keine DB-Zugangsdaten nötig, sofort startklar.
    - *Option 2: MySQL / MariaDB* – Eingabemaske für Host, Port, Datenbankname, Benutzer, Passwort und Tabellenpräfix.
    - *Option 3: SQLite* – Lokale Datei mit Schreibrechteprüfung.
  - **Automatischer Verbindungstest (Live-Ping):**
    - Prüft Zugangsdaten sofort vor Abschluss der Installation.
  - **Intelligenter Notfall-Fallback:**
    - Wenn keine Datenbank vorhanden ist, falsche Zugangsdaten eingegeben werden oder der DB-Server ausfällt, schaltet das System transparent auf die lokale `data.json` zurück.
- [ ] **7.3 Konfiguration & Migrations-Werkzeug im Adminbereich:**
  - Speicherung der Verbindungsparameter in einer geschützten `config.php` außerhalb des Web-Roots oder mit Zugriffsschutz.
  - **1-Klick-Daten-Migration im Adminbereich:**
    - Assistent zum Übertragen bestehender JSON-Daten in eine frisch angelegte SQL-Datenbank (oder umgekehrt von SQL zurück nach JSON).
- [ ] **7.4 Prüfung & Abnahme:**
  - Installation mit JSON-Treiber testen.
  - Installation mit MySQL/MariaDB testen, Tabellen-Erstellung und Lese-/Schreibvorgänge verifizieren.
  - Fallback-Verhalten bei nicht erreichbarer Datenbank testen.

---

### Punkt 8: Mehrsprachigkeit (i18n, Sprachauswahl bei Installation & Frontend-Umschalter)
*Ziel: Mehrsprachiger Webauftritt mit Auswahl der primären Hauptsprache während der Installation und optionaler Aktivierung weiterer Sprachen inklusive dezentem Frontend-Sprachwechsler.*

- [ ] **8.1 Sprachauswahl bei der Erstinstallation (`install.php`):**
  - Dropdown zur Auswahl der gewünschten Website-Hauptsprache:
    - Deutsch (`de` - Standard)
    - Englisch (`en`)
    - Französisch (`fr`)
    - Weitere Sprachen erweiterbar.
  - Alle Standardtexte, Beispieldaten und System-Schaltflächen der Website sowie die Admin-Oberfläche werden direkt in der gewählten Hauptsprache initialisiert.
- [ ] **8.2 Sprach-Architektur & Datenmodell (i18n):**
  - **System-Texte (UI-Labels):**
    - Feste Beschriftungen (z. B. *"Jetzt anfragen"*, *"Galerie"*, *"Kontakt"*, *"Vollbild"*, *"Fehler beim Senden"*) werden in modulare Übersetzungsdateien ausgelagert (`libs/lang/de.json`, `libs/lang/en.json` etc.).
  - **Inhalts-Texte (Content-Übersetzungen):**
    - Erweiterung der Textmodelle in `data.json` bzw. DB:
      - Trennung nach Sprachschlüsseln, z. B. `$data['texts']['de']['hero_title']` und `$data['texts']['en']['hero_title']`.
    - **Intelligenter Fallback (Kaskade):**
      - Ist ein Text oder ein Kurs in der Zweitsprache noch nicht übersetzt, liefert das System automatisch den Inhalt der Hauptsprache aus (keine leeren Lücken auf der Website).
- [ ] **8.3 Sprachverwaltung im Adminbereich (`admin/index.php`):**
  - Einstellungsbereich *„Sprachen & Internationalisierung“*:
    - Festlegung / Wechsel der Hauptsprache.
    - Aktivieren / Deaktivieren zusätzlicher Fremdsprachen per Mausklick.
    - Übersichtliche Übersetzungsmaske: Direkter Side-by-Side-Vergleich (Originaltext links, Übersetzungsfeld rechts) für Startseitentexte, Galerieelemente und Kursbeschreibungen.
- [ ] **8.4 Frontend-Sprachumschalter & SEO-Optimierung:**
  - **Dezenter Sprachwechsler:**
    - Wird nur eingeblendet, wenn mehr als eine Sprache aktiv ist.
    - Platziert im Header neben der Navigation oder im Footer (z. B. als minimalistische Textschalter `DE | EN` oder mit Flaggen-Icons).
  - **Sprachsteuerung:**
    - Umschalten per URL-Parameter (z. B. `/?lang=en`) mit automatischer Speicherung in der Nutzersitzung/Cookie.
  - **Suchmaschinen-Optimierung (Internationales SEO):**
    - Dynamisches Setzen des HTML-Sprachattributs `<html lang="...">`.
    - Automatische Generierung von `<link rel="alternate" hreflang="..." href="...">`-Tags im `<head>`, damit Google jede Sprachversion separat und fehlerfrei indexiert.
- [ ] **8.5 Prüfung & Abnahme:**
  - Installation auf Englisch durchführen -> Frontend und Admin starten auf Englisch.
  - Zweitsprache Deutsch aktivieren, deutsche Übersetzungen einpflegen und Umschaltung im Frontend testen.
  - Google-`hreflang`-Tags und HTML-Sprachauszeichnung im Quelltext verifizieren.

---

### Punkt 9: Umfassende Sicherheits-Härtung & Schwachstellen-Prävention (Priorität: Sehr hoch / Infrastruktur)
*Ziel: Die gesamte Website und den Adminbereich gegen gängige Web-Angriffe (OWASP Top 10) absichern, Schwachstellen proaktiv schließen und automatisierte Angriffe wirksam unterbinden.*

- [ ] **9.1 HTTP Security Header & Browser-Schutz:**
  - Zentrale Auslieferung moderner Sicherheits-Header in PHP / `.htaccess`:
    - `X-Frame-Options: SAMEORIGIN` (Verhindert Clickjacking / Einbettung der Adminseite in fremde iFrames).
    - `X-Content-Type-Options: nosniff` (Verhindert MIME-Confusion-Angriffe).
    - `Referrer-Policy: strict-origin-when-cross-origin` (Schützt sensible URL-Parameter vor Leakage).
    - `Permissions-Policy: geolocation=(), camera=(), microphone=()` (Deaktiviert ungenutzte Browser-Sensoren).
    - `Content-Security-Policy (CSP)`: Kontrollierte Quellen für Google Fonts, Material Icons und eigene Skripte.
    - `Strict-Transport-Security (HSTS)`: Erzwingt verschlüsselte HTTPS-Verbindungen auf Live-Servern.
- [ ] **9.2 Brute-Force-Schutz & Intelligentes Rate-Limiting:**
  - **Login- & PIN-Schutz:**
    - Protokollierung fehlgeschlagener Anmeldeversuche (IP-basiert / Session-basiert).
    - Nach 5 Fehlversuchen: Automatische progressive Zeitverzögerung (Backoff: 3s, 5s, 10s) und temporäre Sperre für 15 Minuten.
  - **Kontaktformular-Schutz:**
    - Absicherung gegen E-Mail-Flooding und Spambots über Zeitlimit (z. B. maximal 3 Anfragen pro IP innerhalb von 10 Minuten).
- [ ] **9.3 Dateisicherheit & Verzeichnis-Abschirmung:**
  - **Direktzugriffs-Verbot für kritische Dateien:**
    - `.htaccess` / Webserver-Regeln zum strikten Blockieren des direkten Browser-Aufrufs von:
      `data.json`, `contacts.json`, `data.sample.json`, `installed.lock`, `config.php`, `.git/`, Logdateien und Backups (`*.json`, `*.zip`, `*.log`).
  - **Upload-Sicherheits-Audit:**
    - MIME-Type Magic-Byte-Prüfung via `finfo_file(FILEINFO_MIME_TYPE)` (nicht nur Dateiendung prüfen).
    - Sanitizing von Bilddateien (Entfernen von gefährlichem eingebettetem PHP-Code in EXIF-Headern durch Neukompilierung via GD).
    - Kryptografische Zufallsnamen für alle Uploads (`bin2hex(random_bytes(16))`).
    - Strikte Deaktivierung von Skriptausführung (`.php`, `.phtml`, `.cgi`) im Upload-Ordner via `.htaccess`.
- [ ] **9.4 XSS-, CSRF- & Injection-Prävention:**
  - Durchgängiges `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` bei allen Ausgaben im Frontend und Backend.
  - Striktes CSRF-Token-Prüfkonzept für jede zustandsändernde POST-Anfrage im Adminbereich und Kontaktformular.
  - Konsequente Nutzung von Prepared Statements mit gebundenen Parametern bei SQL-Datenbanktreibern (vollständiger SQL-Injection-Schutz).
- [ ] **9.5 Session-Sicherheit & Audit-Log:**
  - Gehärtete Session-Cookies: `session.cookie_httponly = true`, `session.cookie_secure = true` (bei HTTPS), `session.cookie_samesite = 'Strict'`.
  - Session-ID-Regeneration bei jedem Login (`session_regenerate_id(true)` gegen Session-Fixation).
  - Automatischer Logout bei 30 Minuten Inaktivität.
  - Internes Sicherheits-Logfile (`logs/security.log`, schreibgeschützt) für fehlgeschlagene Logins, PIN-Fehler und abgewehrte CSRF-Versuche.
- [ ] **9.6 Prüfung & Abnahme:**
  - Sicherheitsüberprüfung via SecurityHeaders-Test und OWASP ZAP / Burp Suite Testdurchlauf.
  - Test auf Direktdownload von `data.json` über den Browser -> muss HTTP 403 Forbidden liefern.
  - Brute-Force-Simulation an Login & PIN durchführen und Auslösen der Sperre prüfen.

---

### Punkt 10: Mehrbenutzersystem & Rollenbasierte Rechteverwaltung (RBAC) (Priorität: CMS-Kernfunktion)
*Ziel: Mehrere Benutzerkonten mit abgestuften Rechten (Administrator, Moderator, Content-User). Benutzeranlage und Rechtevergabe erfolgen ausschließlich im Backend durch den Administrator.*

- [ ] **10.1 Rollenmodell & Berechtigungsmatrix (Role-Based Access Control):**
  - **Rolle 1: Administrator (Superadmin)**
    - Vollzugriff auf alle Bereiche.
    - Systemverwaltung: Benutzerverwaltung, Rollenvergabe, Sicherheit & PIN, SMTP-Einstellungen, Backup & Restore, Speicher-/Datenbanktreiber, Theme-/Design-Parameter, Installations-Setup.
  - **Rolle 2: Moderator**
    - Redaktionelle Leitung:
    - Galerie (Hinzufügen, Bearbeiten, Löschen, Sortieren, Kategorien).
    - Flechtkurse & Workshops (Termine anlegen, Buchungsstatus, Teilnehmer).
    - Kursimpressionen / Rückblick.
    - Startseitentexte & Über-mich-Bereich.
    - Einsicht in eingegangene Kontaktanfragen (Postfach) und Statusbearbeitung.
    - *Kein Zugriff auf:* Sicherheit, PINs anderer Nutzer, Backups, SMTP, System-Settings, Benutzerverwaltung.
  - **Rolle 3: Content-User (Autor / Redakteur)**
    - Eingeschränkter Arbeitsbereich für reine Inhaltspflege:
    - Neue Galeriebilder hochladen und beschreiben.
    - Neue Kurstermine anlegen und bestehende Termine bearbeiten.
    - *Kein Zugriff auf:* Design-Code, Menü-Struktur, Löschen von Sektionen, Sicherheits- & Systemeinstellungen, Benutzerkonten.
- [ ] **10.2 Datenmodell für Benutzer (`users` in `data.json` bzw. DB-Tabelle `fb_users`):**
  - Jedes Benutzerkonto enthält:
    - `id`: Eindeutige Kennung (`usr_...`)
    - `username`: Login-Name
    - `email`: E-Mail-Adresse für Benachrichtigungen / Passwort-Reset
    - `password_hash`: Bcrypt-gehashtes Passwort
    - `role`: `'admin'` | `'moderator'` | `'content'`
    - `is_active`: `true` / `false` (Konto sperren / entsperren)
    - `created_at`: Erstellungsdatum
    - `last_login`: Zeitstempel der letzten Anmeldung
- [ ] **10.3 Backend-Benutzerverwaltung (`admin/index.php` -> Neuer geschützter Reiter):**
  - **Nur für Administratoren sichtbar.**
  - **Übersichtstabelle:** Liste aller hinterlegten Benutzer mit Rolle, Status und letzter Aktivität.
  - **Benutzer anlegen:** Formular zur Neuanlage mit Name, E-Mail, Initialpasswort und Rollenauswahl (Admin / Moderator / Content-User). *Keine Registrierung im Frontend!*
  - **Benutzer bearbeiten:** Rolle anpassen, Benutzer vorübergehend deaktivieren oder löschen.
  - **Passwort zurücksetzen:** Administrator kann Einmal-Passwort generieren oder PIN-geschützt neu vergeben.
  - **Eigenes Profil:** Jeder angemeldete Benutzer (egal welcher Rolle) kann in einem kompakten Profil sein eigenes Passwort ändern.
- [ ] **10.4 Serverseitiges Berechtigungs-Enforcement:**
  - Jede Backend-Aktion (`action=...`) validiert die Berechtigung strikt serverseitig:
    ```php
    function require_role(string $minimum_role): void {
        // Prüft, ob angemeldeter Nutzer mindestens $minimum_role besitzt
    }
    ```
  - **Dynamische UI-Anpassung:** Reiter und Schaltflächen, für die ein Nutzer keine Berechtigung hat, werden im Backend gar nicht erst gerendert (z. B. sieht der Content-User nur *„Galerie“* und *„Flechtkurse“*).
- [ ] **10.5 Prüfung & Abnahme:**
  - Als Administrator einen Moderator und einen Content-User anlegen.
  - Login als Content-User testen: Prüfen, dass Systemeinstellungen, Sicherheit und Benutzerverwaltung weder sichtbar noch per direktem POST-Request manipulierbar sind.
  - Login als Moderator testen: Galerie und Anfragen bearbeiten, Zugriff auf System-Tabs verweigert.
  - Eigenen Passwortwechsel für jeden Nutzertyp erfolgreich durchführen.







