# WeaveCMS – Plugin-Dokumentation & Erweiterungs-Ökosystem

> **Version:** 1.3.4  
> **Architektur:** Modulares Hook- & Micro-Kernel-System ([`PluginManager`](../php/PluginManager.php))  
> **Verzeichnis:** `/plugins/<plugin-slug>/`  

Diese Dokumentation bietet eine strukturierte Übersicht über alle offiziellen WeaveCMS-Plugins, deren Kernfunktionen, Datenhaltungsmodelle, Schnittstellen, REST-APIs und Konfigurationsmöglichkeiten.

---

## Inhaltsverzeichnis

1. [Plugin-Architektur & Manifest-Standard](#plugin-architektur--manifest-standard)
2. [Automatische Updates via GitHub & 3rd-Party-Repositories](#automatische-updates-via-github--3rd-party-repositories)
3. [Weave Games (`weave-games`)](#1-weave-games-weave-games)
4. [Weave Shop (`weave-shop`)](#2-weave-shop-weave-shop)
5. [Weave Inventory (`weave-inventory`)](#3-weave-inventory-weave-inventory)
6. [Weave Helpdesk (`weave-helpdesk`)](#4-weave-helpdesk-weave-helpdesk)
7. [Weave Forum (`weave-forum`)](#5-weave-forum-weave-forum)
8. [Weave Forum-Benutzerverwaltung (`weave-forum-users`)](#6-weave-forum-benutzerverwaltung-weave-forum-users)
9. [Weave Downloads & Dokumente (`weave-downloads`)](#7-weave-downloads--dokumente-weave-downloads)
10. [Weave Workshops & Kursbuchungen (`weave-workshops`)](#8-weave-workshops--kursbuchungen-weave-workshops)
11. [webtrees Genealogie (`webtrees-genealogy`)](#9-webtrees-genealogie-webtrees-genealogy)
12. [Universal Integrations (`universal-integrations`)](#10-universal-integrations-universal-integrations)
13. [Interaktive Sandbox & Live-Demo (`interactive-sandbox`)](#11-interaktive-sandbox--live-demo-interactive-sandbox)

---

## Plugin-Architektur & Manifest-Standard

Jedes WeaveCMS-Plugin ist als eigenständiger Ordner in `/plugins/<slug>/` gekapselt und enthält zwingend eine Manifest-Datei `plugin.json`:

```json
{
    "name": "Weave Plugin Name",
    "slug": "weave-plugin-slug",
    "version": "1.0.0",
    "author": "Entwickler / Team",
    "description": "Kurzbeschreibung der Funktionalität",
    "category": "e-commerce | gaming | service | community | tools | media | events",
    "icon": "material_icon_name",
    "entry_file": "plugin.php",
    "requires_core": ">=1.3.0",
    "requires_plugins": [],
    "settings_tab": "weave-plugin-slug",
    "github_repo": "owner/repository",
    "update_url": "https://example.com/plugin_manifest.json"
}
```

### Der `PluginManager`
* **Ereignis-Hooks (Actions):** `PluginManager::addAction('hook_name', callable $callback, int $priority = 10)`
* **Filter-Hooks (Filters):** `PluginManager::addFilter('filter_name', callable $callback, int $priority = 10)`
* **Aktivierung & Status:** Verwaltet in `data.json` unter `plugins.active` und `plugins.configs.<slug>`.
* **Core-Kompatibilitätsprüfung:** `PluginManager::isCoreCompatible($requirement)` prüft SemVer-Bedingungen (`>=1.3.0`, `^1.3`, `*`).

---

## Automatische Updates via GitHub & 3rd-Party-Repositories

Jedes Plugin kann unabhängig vom CMS-Core aktualisiert werden. Die Update-Logik orientiert sich am GitHub-Release-Standard des Core-Updaters und wird vom [`PluginUpdater`](../php/PluginUpdater.php) und [`UpdateManager`](../php/UpdateManager.php) gesteuert.

### 1. Update-Quelle in `plugin.json` deklarieren

In `plugin.json` wird entweder ein GitHub-Repository oder eine benutzerdefinierte JSON-Manifest-URL angegeben:

```json
{
    "github_repo": "acme-corp/weave-my-plugin"
}
```
*Unterstützte Formate:*
* `owner/repository`
* `https://github.com/owner/repository`
* `https://github.com/owner/repository.git`

Alternativ für eigene Update-Server:
```json
{
    "update_url": "https://updates.example.com/weave-my-plugin.json"
}
```

Format des Custom JSON-Manifests:
```json
{
    "version": "1.4.0",
    "zip_url": "https://updates.example.com/packages/weave-my-plugin-1.4.0.zip",
    "sha256": "4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945",
    "requires_core": ">=1.3.0",
    "release_url": "https://updates.example.com/releases/v1.4.0.html",
    "changelog": "Neue Zahlungs-Schnittstelle und Leistungsoptimierung."
}
```

### 2. GitHub-Releases veröffentlichen (Best Practices)

1. **Semantische Versions-Tags:**  
   Erstelle ein GitHub Release mit einem semantischen Tag, z. B. `v1.4.0` oder `1.4.0`.
2. **Release-Paket (ZIP):**
   * **Bevorzugt:** Lade als Release-Asset ein ZIP-Archiv hoch (z. B. `weave-my-plugin-v1.4.0.zip`), das den Plugin-Inhalt enthält.
   * **Fallback:** Falls kein Asset angehängt wird, nutzt WeaveCMS automatisch den GitHub-Quellcode-Zipball (`zipball_url`). Der `PluginUpdater` erkennt automatisch die verschachtelte Verzeichnisstruktur `<owner>-<repo>-<sha>/` und navigiert zum Stammverzeichnis der `plugin.json`.
3. **SHA-256 Prüfsumme (Optional, dringend empfohlen):**  
   Füge in den Beschreibungstext (Release Body) des GitHub Releases die SHA-256 Prüfsumme des ZIP-Archivs ein:
   ```markdown
   ## Änderungen in v1.4.0
   - Feature X hinzugefügt
   - Bugfix Y behoben

   SHA256: 4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945
   ```
   WeaveCMS parst diese Prüfsumme automatisch und verifiziert das heruntergeladene Paket vor der Installation.
4. **Mindestversion des CMS angeben (`requires_core`):**  
   Stelle sicher, dass die neue `plugin.json` im Release-Archiv das Feld `"requires_core"` pflegt (z. B. `">=1.3.0"`). WeaveCMS bricht das Update vorab sicher ab, falls der Server noch eine veraltete CMS-Core-Version nutzt.

### 3. Der Update-Ablauf im WeaveCMS-Backend

1. **Prüfung:** Der Administrator klickt im Adminbereich unter *Plugins* auf **Updates prüfen** (oder gezielt neben dem Plugin). WeaveCMS fragt die GitHub API (`/repos/{owner}/{repo}/releases/latest`) ab und cacht das Ergebnis.
2. **Benachrichtigung:** Wenn eine neuere Version verfügbar ist, erscheint ein gelbes Update-Banner mit Versionssprung, Link zum Changelog und 1-Klick-Button **Jetzt auf v{version} aktualisieren**.
3. **Automatisches Pre-Update-Backup:** Vor dem Überschreiben archiviert `PluginUpdater` den aktuellen Plugin-Ordner vollständig unter `backups/plugins/plugin_<slug>_<datum>_v<alt>.zip`.
4. **Atomarer Dateiaustausch:** Die Dateien werden temporär bereitgestellt und atomar ersetzt.
5. **Rollback im Fehlerfall:** Sollte während des Austauschs ein Schreibfehler auftreten, wird das Plugin automatisch aus dem Pre-Update-Backup wiederhergestellt.
6. **Cache-Invalidierung & Audit-Log:** `PluginManager::clearCache()` und OPCache werden zurückgesetzt und ein Audit-Event (`PLUGIN_UPDATE`) wird protokolliert.

---

## 1. Weave Games (`weave-games`)

Das **Weave Games**-Plugin bildet das zentrale Bindeglied zwischen WeaveCMS und externen Spiele-Ökosystemen. Es eignet sich gleichermaßen für komplexe Server/Client-Projekte (wie Desktop- und Multiplayer-RPGs), universelle Dedicated Server (Windows/Linux) als auch reine HTML5/WebGL-Browsergames.

### Kernfeatures:
* **Online-Registrierung & Spieler-Accounts:**
  * Selbstregistrierung für Spieler im Webportal (`?page=games`) oder via REST-API.
  * Automatische Generierung von kryptografischen Client-Authentifizierungs-Tokens (`gtkn_...`).
  * Rollenverteilung (`player`, `moderator`, `admin`), Whitelist-System und Bann-Verwaltung.
  * Automatische Superadmin-Erkennung für angemeldete WeaveCMS-Administratoren.
* **Client-Update-Rollout & Patch-Verteilung:**
  * Versionsverwaltung für Desktop-Clients (Launcher, Game-Binaries und Patches).
  * Kanäle: `stable`, `beta`, `nightly`.
  * Plattformen: `windows-x64`, `linux-x64`, `macos`, `browser-webgl`, `all`.
  * SHA256-Prüfsummen-Validierung zum Schutz vor manipulierten Client-Binaries.
  * Markierung von **Pflicht-Updates** (`is_mandatory = true`), die vor dem Server-Login zwingend installiert werden müssen.
* **Game-Server-Verwaltung & Live-Cluster:**
  * **Start / Stop / Restart:** Prozesssteuerung lokal (Windows Task / Linux Daemon) oder remote.
  * **Parameterübergabe:** Übergabe flexibler Startargumente an Server-Binaries für Windows (`.exe`) und Linux.
  * **SSH Remote Login:** Sichere Fernsteuerung externer Linux- oder Windows-Rootserver über SSH (Passwort- oder Key-Authentifizierung via `ssh2` / CLI).
  * **Live-Status:** Socket-Ping zur Latenz- und Online-Prüfung (`fsockopen`).
* **Browsergames-Hosting:**
  * Direktes Einbetten und Spielen von 2D/3D-Webspielen (z. B. *Space Orbit Canvas Arcade*) ohne vorherigen Download.

### Endpunkte & Routen:
* **Web-Portal:** `?page=games`
* **REST API:** `/api/games.php`
  * `?action=check_update&game=voxel-adventure&version=0.9.0&platform=windows-x64`
  * `?action=login` (POST: username, password)
  * `?action=register` (POST: username, email, password, nickname)
  * `?action=verify_token` (POST/GET: token)
  * `?action=server_status` (GET: server_id)

---

## 2. Weave Shop (`weave-shop`)

Vollwertiges, leichtgewichtiges E-Commerce-System für physische Güter, digitale Downloads und Service-Voucher.

### Kernfeatures:
* **Warenkorb & Checkout:** Interaktiver AJAX-Warenkorb und digitaler Kassenschritt.
* **Steuerberechnung:**
  * Regulärer Ausweis der Mehrwertsteuer (19 %, 7 %).
  * Steuerfreie Kleinunternehmerregelung nach **§ 19 UStG** (0,00 € Steuerausweis mit legalem Hinweistext).
* **Bezahldienstleister (Gateways):**
  * **Kreditkarte:** Integrierte Kartenprüfung inklusive Luhn-Algorithmus (Modulo 10 Prüfziffer).
  * **PayPal Express:** Direkte Übergabe und Autorisierung.
  * **WERO:** Europäische Sofortüberweisung (EPI) via IBAN/BIC.
  * **Vorkasse / Banküberweisung:** Überweisungsdaten mit Bestellreferenz.
* **Versandkostenlogik:**
  * Konfigurierbare Standard-Versandkosten.
  * Versandkostenfreigrenze (z. B. portofrei ab 50,00 €).
* **Lager-Kopplung:** Automatischer Bestandsabzug beim Kauf über das Event `shop_order_created`.
* **API & Daten:** REST API unter `/api/shop.php`, JSON-Import und -Export für Katalog und Bestellungen.

---

## 3. Weave Inventory (`weave-inventory`)

Professionelle Lager- & Artikelverwaltung, voll synchronisiert mit dem Weave Shop.

### Kernfeatures:
* **Artikelstamm:** Bestände, SKU, Lagerplätze, Einkaufspreise (EK), Verkaufspreise (VK) und Margenanzeige.
* **Bestandswarnungen:** Automatische E-Mail-Warnung bei Unterschreiten des Mindestbestands.
* **Wareneingang:** Zubuchung mit Lieferscheinnummer, Lieferant und Notizen.
* **Inventur:** Schneller Soll/Ist-Abgleich für Stichtagsinventuren.
* **Lagerbewegungs-Audit-Log:** Revisionssicheres Protokoll sämtlicher Ein- und Ausgänge (inkl. Verkäufen im Shop).
* **REST API:** Tokenbasierte Anbindung für Barcode-Scanner und Kassensysteme unter `/api/inventory.php`.

---

## 4. Weave Helpdesk (`weave-helpdesk`)

Internes und externes Service-Desk- und Ticketsystem im responsiven Petrol/Teal-Design.

### Kernfeatures:
* **Ticketsystem:**
  * Ticket-Nummernkreis im Standardformat `YY` + 6 Ziffern (z. B. `26011169`).
  * Status-Workflow: *Neu*, *In Bearbeitung*, *Warten auf Rückmeldung*, *Warten auf ext. Dienstleister*, *Geschlossen*, *Abgerechnet*.
* **Revisionssichere Buchhaltungssperre:**
  * Der Status **„Abgerechnet“** sperrt Tickets dauerhaft gegen Bearbeitung, Wiedereröffnung oder das Hinzufügen von Tätigkeiten.
* **Serviceverträge & Inklusivminuten:**
  * Minutengenaue Erfassung von Tätigkeiten.
  * Automatische Gegenrechnung gegen vertragliche Inklusivminuten (`Verfügbar - Verbraucht = Differenz`).
* **Firmendaten-Fallback:** Automatische Übernahme der Unternehmens- und Kontaktdaten aus dem WeaveCMS-Impressum.
* **Menü-Steuerung:** Im Backend wahlweise im Website-Hauptmenü ein- oder ausblendbar.
* **Mobile Responsiveness:** Für Smartphones und Tablets optimiert (horizontale Touch-Tabellen, vertikal stapelbare Eingabemasken).

---

## 5. Weave Forum (`weave-forum`)

Vollwertiges Diskussions- und Community-Forum mit hierarchischer Struktur und flexibler Speicher-Engine.

### Kernfeatures:
* **Hierarchische Forenstruktur:**
  * Kategorien, Foren und Unterforen mit Sortierreihenfolge und Icons.
  * Threads (Themen) mit Pinnadel-Funktion (*Sticky*), Sperrstatus (*Locked*) und Beitragszählern.
* **Beitrags-Editor & Interaktion:**
  * Formatierung über Markdown und BBCode.
  * Zitate mit Referenzierung des Vorautors.
  * Antwort-Benachrichtigungen per E-Mail.
* **Duale Speicher-Engine:**
  * Umschaltbar zwischen **JSON Flat-File** (`data/forum_data.json`) und performanter **SQLite-Datenbank** (`data/forum.sqlite`).
* **Sicherheit & Moderation:**
  * CSRF-Schutz auf allen Aktionen.
  * Moderations-Queue zur Freischaltung neuer Themen bei sensiblen Boards.
  * Beitrags-Meldungen und Audit-Log.

---

## 6. Weave Forum-Benutzerverwaltung (`weave-forum-users`)

Dedizierte Benutzerverwaltung für Forenmitglieder – strikt isoliert von CMS-Administratoren.

### Kernfeatures:
* **Strikte Isolation:**
  * Foren-Benutzerkonten sind getrennt von den Administrator-Zugängen des CMS-Kerns gespeichert.
* **Registrierung & E-Mail-Aktivierung:**
  * Selbstregistrierung im Frontend mit Bestätigungs-Token (`verification_token`).
  * Konfigurierbare manuelle Freischaltung durch Moderatoren.
* **Rollen & Berechtigungen:**
  * Rollen: `Forum-Admin`, `Moderator`, `User`.
  * Status: `Aktiv`, `Ausstehend`, `Gesperrt` (mit optionalem Sperrgrund).
  * Moderator-Aliase für geschützte Team-Präsenzen.
* **DSGVO-Konformität:**
  * Personenbezogener Datenexport (JSON) für Profil und verfasste Beiträge.
  * Recht auf Vergessenwerden: Anonymisierung von Beiträgen bei Kontolöschung.

---

## 7. Weave Downloads & Dokumente (`weave-downloads`)

Zentrales Download-Center zur Verwaltung und sicheren Bereitstellung digitaler Dokumente, Handbücher und Softwarepakete.

### Kernfeatures:
* **Kategoriensystem:** Ordnerstruktur für Handbücher, Treiber, Kataloge und Firmware.
* **Zugriffsschutz:**
  * Öffentliche Downloads für alle Besucher.
  * Geschützte Downloads ausschließlich für authentifizierte Benutzer oder Kunden.
* **Download-Statistiken:** Revisionssicherer Zähler für getätigte Dateiabrufe und Traffic-Überwachung.
* **Direkt-Schutz:** `.htaccess`-Schutz zur Unterbindung von unautorisiertem Hotlinking.

---

## 8. Weave Workshops & Kursbuchungen (`weave-workshops`)

Veranstaltungs- und Buchungsmodul für Akademien, Kursanbieter, Schulungen und Events.

### Kernfeatures:
* **Terminverwaltung:** Einzeltermine, wiederkehrende Kurse und Mehrtages-Workshops mit Dozentenangabe.
* **Teilnehmerkontingente:** Maximale Teilnehmeranzahl, Restplatzanzeige und automatische Warteliste.
* **Buchungsablauf:** Buchungsformular im Frontend mit automatischer Bestätigungs-E-Mail und Kalendereinladung (.ics).
* **Preise & Rabatte:** Frühbucher-Konditionen und Kopplung an den Weave Shop für Bezahlvorgänge.

---

## 9. webtrees Genealogie (`webtrees-genealogy`)

Integration genealogischer Stammbäume und Familiendatenarchive.

### Kernfeatures:
* **GEDCOM 5.5.1 & 7.0 Parser:** Einlesen und Rendern genealogischer Standard-Datensätze.
* **Datenschutzfilter:** Automatische Anonymisierung von lebenden Personen nach DSGVO-Richtlinien.
* **Visualisierung:** Interaktive Stammbaum-Sektion, Ahnentafeln und Personen-Detailkarten.

---

## 10. Universal Integrations (`universal-integrations`)

Universelle Schnittstelle für externe Services und Web-Einbindungen.

### Kernfeatures:
* **Widget- & Iframe-Embedding:** Sichere Einbindung von Terminkalendern (Calendly, Google Calendar), interaktiven Karten (Google Maps, OpenStreetMap) oder Tracking-Badges.
* **Content Security Policy (CSP):** Automatische Bereinigung und Validierung externer Embed-Codes zum Schutz vor XSS.

---

## 11. Interaktive Sandbox & Live-Demo (`interactive-sandbox`)

Sicherer, temporärer Demo-Zugang (Tryout-Modus) ohne permanente Speicherung.

### Kernfeatures:
* **Flüchtige Session-Speicherung:** Alle Änderungen an Inhalten, Medien und Optionen bleiben isoliert im Sitzungsspeicher.
* **Automatischer Bereinigungs-Zyklus:** Automatisches Löschen aller temporären Testdaten und Upload-Bilder nach Sitzungsende.
* **Sicherheits-Barrieren:** Hartes Blockieren von System-Updates, Backup-Restores und kritischen Systembefehlen im Demo-Modus.

---

*Dokumentation gepflegt vom WeaveCMS Core Team.*
