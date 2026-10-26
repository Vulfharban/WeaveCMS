# WeaveCMS

> **Lightweight, modular section-based CMS with flat-file & SQL support.**

WeaveCMS is a fast, modern and dependency-free content management system (PHP 8.0+), built for landing pages, craft businesses, portfolio sites and modular web presences.

## Highlights & Core Features

- **Modular sections:** Flexible page layout built from section types (Hero, Gallery, Courses/Events, About me, Free text) with free ordering.
- **Dual storage driver:** Choose between file-based flat-file storage (`data.json`) and a relational database (MySQL / MariaDB / SQLite via PDO).
- **Guided setup wizard:** Painless installation with system health check and optional sample data.
- **Multilingual (i18n):** Primary language selectable at first install, optional secondary languages with a frontend switcher.
- **Role-based multi-user system (RBAC):** Graduated permissions for administrator, moderator and content user.
- **Logo management & responsive engine:** Flexible logo (SVG/image/text) and reliable mobile usage on all devices.
- **Release packaging & update check:** ZIP/7z generator for full and update packages plus automatic remote version comparison.
- **Security hardening:** CSRF protection, XSS prevention, brute-force & rate limiting, strict upload validation and security headers.
- **100 % plain PHP:** No heavy framework dependencies or build tools required on the server.

## Documentation & Roadmap

- **Feature catalog & update portal (Deutsch):** [`libs/docs/features.md`](libs/docs/features.md)
- **Feature catalog (English):** [`libs/docs/features_en.md`](libs/docs/features_en.md)
- **21-phase development roadmap:** [`libs/docs/roadmap.md`](libs/docs/roadmap.md)

## Releases & Downloads (v1.2.3)

- 📦 **[WeaveCMS v1.2.3 Full Package](releases/weavecms-v1.2.3.zip)** (`releases/weavecms-v1.2.3.zip`, 11.93 MB) – Complete installation archive for new websites.
- 🔄 **[WeaveCMS v1.2.3 Update Package](releases/update-v1.2.3.zip)** (`releases/update-v1.2.3.zip`, 0.52 MB) – Core system files for upgrading existing installations.

## Changelog & Recent Updates

### v1.2.3 (26. Oktober 2026)

- 🗄️ **Relationales MySQL/MariaDB-Schema:** Statt eines JSON-Blobs je Schlüssel legt der SQL-Treiber nun eigene Tabellen je Inhaltsbereich an (`gallery`, `courses`, `pages`, `faqs`, `team`, `users` …) mit lesbaren Spalten (`item_id`, `title`, `status`, `sort_pos`), `config` für Einstellungen/Texte/SEO und `lists` für Textlisten. Unbekannte Schlüssel (z. B. Plugins) landen in `documents`. Die Daten bleiben 1:1 kompatibel zum JSON-Modus; Altbestände aus `documents` werden beim nächsten Speichern automatisch überführt.
- 🧩 **Fehlende vs. leere Bereiche:** Bei MySQL wird zwischen nie vorhandenen und bewusst leeren Bereichen unterschieden – identisches Verhalten wie bei JSON.
- 🛠️ **Installer (MySQL):** Konkrete SQL-Fehlermeldungen statt Pauschalfehler, die Option „Datenbank automatisch erzeugen“ wird berücksichtigt, `config.local.php` wird erst nach erfolgreicher Tabellenanlage geschrieben.
- 🌐 **Vollständige Mehrsprachigkeit der Beispieldaten & Frontend-Texte:** Sämtliche mitgelieferten Beispieldaten (`data.sample.json`) für Galerie, Workshops, Blog, FAQ, Team, Preise, Downloads, Sektionen und Rechtstexte liegen nun vollständig übersetzt für Deutsch (`de`), Englisch (`en`) und Italienisch (`it`) vor. Frontend-Controller und Template-Views übersetzen sämtliche UI-Strings, Kategorien, Captchas und Metadaten konsistent ohne unerwünschte Fallbacks auf Deutsch.
- 📊 **Statistik:** `data/stats.json` wird nicht mehr ausgeliefert; neue Installationen starten mit leerer Besucherstatistik.
- 🔐 **PIN-Eingabe:** Keine Beispiel-Vorbelegung („1977“) mehr im Installer und Admin-Login.

### v1.2.2 (25. Oktober 2026)

- 🛒 **Shop-Erweiterungs-Architektur (Add-on Plugins):** Leistungsfähiges Hook- und Filtersystem für Erweiterungs-Plugins (`shop_payment_gateways`, `shop_process_payment`, `shop_calculate_cart`, `shop_product_data`, `shop_admin_subtabs`, `shop_api_action`, `shop_email_order_data`, etc.). Ermöglicht nahtlose Integration von Dritt-Zahlungsanbietern, Rabatt-/Gutscheinsystemen, individuellen Produktfeldern und eigenen Admin-Tabs.
- 📚 **Shop-Entwicklerdokumentation:** Vollständige Referenz und Quick-Start-Guide in `plugins/shop/README.md` inklusive lauffähigem Beispiel-Plugin für Gutscheincodes.
- 🛡️ **SecurityManager Fix:** Einführung von `SecurityManager::getBaseUrl()` zur zuverlässigen URL-Ermittlung bei System-E-Mails und Webhooks.
- 📦 **Plugin-Versionierung:** Anhebung des Shop-Plugins auf v1.2.0.

### v1.2.1 (25. Oktober 2026)

- 🎨 **Design-Toolbar für Einzelseiten:** Schnellauswahl und 1-Klick-Einfügen von vordefinierten Trennern, Farbboxen und Überschriftarten direkt im Admin-Seiteneditor.
- ✂️ **Optische Trenner (Visual Dividers):** Farbverlauf-Linien (`divider-gradient`), zentrierte Icon-Trennlinien (`divider-icon`), leuchtende Glow-Linien (`divider-glow`), gepunktete und Wellen-Trenner.
- 📦 **Einfärbbare Div-Boxen & Cards:** Sanfte Farbtöne (`box-soft-primary`, `box-soft-success`, `box-soft-amber`, `box-soft-purple`), Dark-/Gradient-Cards und frei einfärbbare Boxen mit Farbwähler.
- 🏷️ **Vordefinierte Überschrift-Stile:** Akzent-Balken links, Farbverlauf-Unterstrich, Eyebrow/Kicker-Badges, Gradient-Schriftzüge und Icon-Headlines.
- 📏 **Responsiver Einzelseiten-Container:** Automatische Skalierung bis zu `var(--container-max-width, 1100px)` für großzügige 2-Spalten-Grids und Projekt-Karten.
- 🛠️ **MarkdownParser Entity-Fix:** Deaktivierung der Doppelkodierung (`double_encode = false`), sodass sowohl `&` als auch `&amp;` fehlerfrei als `&` im Browser angezeigt werden.

### v1.2.0 (25. Oktober 2026)

- 📋 Modernisiertes Aktive/Inaktive Sektionen Kanban-Board im Admin-Dashboard.
- 🛡️ Optionaler Spamschutz (Captcha/Rechenaufgabe) für den Kontakt-Drawer analog zum Impressum.
- 🌐 Optimierter Sprachwechsel ohne Reset auf das Dashboard.

## Installation

1. Download **[weavecms-v1.2.3.zip](releases/weavecms-v1.2.3.zip)** or copy the repository files to your web server (PHP 8.0+).
2. Open `install.php` in your browser and follow the guided setup wizard.

## License

Released under the [GNU General Public License v3.0](LICENSE).