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

## Releases & Downloads (v1.2.1)

- 📦 **[WeaveCMS v1.2.1 Full Package](releases/weavecms-v1.2.1.zip)** (`releases/weavecms-v1.2.1.zip`, 11.88 MB) – Complete installation archive for new websites.
- 🔄 **[WeaveCMS v1.2.1 Update Package](releases/update-v1.2.1.zip)** (`releases/update-v1.2.1.zip`, 0.50 MB) – Core system files for upgrading existing installations.

## Changelog & Recent Updates

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

1. Download **[weavecms-v1.2.1.zip](releases/weavecms-v1.2.1.zip)** or copy the repository files to your web server (PHP 8.0+).
2. Open `install.php` in your browser and follow the guided setup wizard.

## License

Released under the [GNU General Public License v3.0](LICENSE).