# WeaveCMS

> **Leichtgewichtiges, modulares Sektions-CMS mit Flat-File- & SQL-Unterstützung.**

WeaveCMS ist ein schnelles, modernes und abhängigkeitsfreies Content-Management-System (PHP 8.0+), das für Landingpages, Handwerksbetriebe, Portfolio-Seiten und modulare Webauftritte entwickelt wurde.

## Highlights & Kernfunktionen
- **Modulare Sektionen:** Flexibler Seitenaufbau aus Sektions-Typen (Hero, Galerie, Kurse/Events, Über mich, Freitext) mit freier Sortierung.
- **Dualer Speicher-Treiber:** Wahlfreiheit zwischen dateibasiertem Flat-File (`data.json`) und relationaler Datenbank (MySQL / MariaDB / SQLite via PDO).
- **Geführter Setup-Wizard:** Schmerzfreie Installation mit System-Health-Check und optionalen Beispieldaten.
- **Mehrsprachigkeit (i18n):** Hauptsprache bei Erstinstallation wählbar, optionale Sekundärsprachen mit Frontend-Umschalter.
- **Rollenbasiertes Mehrbenutzersystem (RBAC):** Abgestufte Rechte für Administrator, Moderator und Content-User.
- **Sicherheits-Härtung:** CSRF-Schutz, XSS-Prävention, Brute-Force- & Rate-Limiting, strikte Upload-Absicherung und Security-Header.
- **100 % Standard-PHP:** Keine schweren Framework-Abhängigkeiten oder Build-Tools für den Serverbetrieb erforderlich.

## Entwicklungs-Roadmap
Die vollständige 10-Punkte-Entwicklungs-Roadmap befindet sich in [`libs/docs/project.md`](libs/docs/project.md).
