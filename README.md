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
- **Feature catalog & update portal:** [`libs/docs/features.md`](libs/docs/features.md)
- **12-phase development roadmap:** [`libs/docs/roadmap.md`](libs/docs/roadmap.md)

## Installation
1. Copy the files to your web server (PHP 8.0+).
2. Open `install.php` in your browser and follow the setup wizard.

## License
Released under the [GNU General Public License v3.0](LICENSE).