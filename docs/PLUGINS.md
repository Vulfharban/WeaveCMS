# WeaveCMS – Plugin-Dokumentation & Erweiterungs-Ökosystem

> **Version:** 1.3.4  
> **Architektur:** Modulares Hook- & Micro-Kernel-System ([`PluginManager`](../libs/php/PluginManager.php))  
> **Verzeichnis:** `/plugins/<plugin-slug>/`  

Diese Dokumentation bietet eine strukturierte Übersicht über alle offiziellen WeaveCMS-Plugins, deren Kernfunktionen, Schnittstellen, REST-APIs und Konfigurationsmöglichkeiten.

---

## Inhaltsverzeichnis

1. [Plugin-Architektur & Manifest-Standard](#plugin-architektur--manifest-standard)
2. [Weave Games (`weave-games`)](#1-weave-games-weave-games)
3. [Weave Shop (`weave-shop`)](#2-weave-shop-weave-shop)
4. [Weave Inventory (`weave-inventory`)](#3-weave-inventory-weave-inventory)
5. [Weave Helpdesk (`weave-helpdesk`)](#4-weave-helpdesk-weave-helpdesk)
6. [webtrees Genealogie (`webtrees-genealogy`)](#5-webtrees-genealogie-webtrees-genealogy)
7. [Weave Universal Integrations (`universal-integrations`)](#6-weave-universal-integrations-universal-integrations)

---

## Plugin-Architektur & Manifest-Standard

Jedes WeaveCMS-Plugin ist als eigenständiger Ordner in `/plugins/<slug>/` gekapselt und enthält zwingend eine `plugin.json`:

```json
{
    "name": "Weave Plugin Name",
    "slug": "weave-plugin-slug",
    "version": "1.0.0",
    "author": "Entwickler / Team",
    "description": "Kurzbeschreibung der Funktionalität",
    "category": "e-commerce | gaming | service | tools",
    "icon": "material_icon_name",
    "entry_file": "plugin.php",
    "requires_core": ">=1.0.0",
    "settings_tab": "weave-plugin-slug"
}
```

### Der `PluginManager`
* **Ereignis-Hooks (Actions):** `PluginManager::addAction('hook_name', callable $callback, int $priority = 10)`
* **Filter-Hooks (Filters):** `PluginManager::addFilter('filter_name', callable $callback, int $priority = 10)`
* **Konfiguration:** Gespeichert in `data.json` unter `plugins.configs.<slug>` oder in separaten JSON-Dateien im Verzeichnis `/data/`.

---

## 1. Weave Games (`weave-games`)

Das **Weave Games**-Plugin bildet das zentrale Bindeglied zwischen WeaveCMS und externen Spiele-Ökosystemen. Es eignet sich gleichermaßen für komplexe Server/Client-Projekte (wie das Rust-basierte Voxel-RPG **Fastadun**), universelle Dedicated Server (Windows/Linux) als auch reine HTML5/WebGL-Browsergames.

### Kernfeatures:
* **Online-Registrierung & Spieler-Accounts:**
  * Selbstregistrierung für Spieler im Webportal (`?page=games`) oder via REST-API.
  * Automatische Generierung von kryptografischen Client-Authentifizierungs-Tokens (`auth_token`).
  * Rollenverteilung (`player`, `moderator`, `admin`), Whitelist-System und Bann-Verwaltung.
  * Automatische Superadmin-Erkennung für angemeldete WeaveCMS-Administratoren.
* **Client-Update-Rollout & Patch-Verteilung:**
  * Versionsverwaltung für Desktop-Clients (z. B. Fastadun Voxel Launcher/Client).
  * Kanäle: `stable`, `beta`, `nightly`.
  * Plattformen: `windows_x64`, `linux_x64`, `macos`, `browser_webgl`, `all`.
  * SHA256-Prüfsummen-Validierung zum Schutz vor manipulierten Client-Binaries.
  * Markierung von **Pflicht-Updates** (`mandatory = true`), die vor dem Server-Login zwingend installiert werden müssen.
* **Game-Server-Verwaltung & Live-Cluster:**
  * **Start / Stop / Restart:** Prozesssteuerung lokal (Windows Task / Linux Daemon) oder remote.
  * **Parameterübergabe:** Übergabe von Startargumenten an Server (z. B. `--port 7777 --world unterreich_ebene_01 --max-players 32 --drow-cities true`).
  * **SSH Remote Login:** Sichere Fernsteuerung externer Linux- oder Windows-Rootserver über SSH (Passwort- oder Key-Authentifizierung).
  * **Live-Status:** Socket-Ping zur Latenz- und Online-Prüfung (`pingServer`).
* **Browsergames-Hosting:**
  * Direktes Einbetten und Spielen von 2D/3D-Webspielen (z. B. Space Orbit Canvas Arcade) ohne vorherigen Download.

### Endpunkte & Routen:
* **Web-Portal:** `?page=games`
* **REST API:** `/api/games.php`
  * `?action=check_update&game=fastadun&version=0.9.0&platform=windows_x64`
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
* **API & Daten:** REST API unter `/api/shop.php`, JSON-Im- und Export für Katalog und Bestellungen.

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

## 5. webtrees Genealogie (`webtrees-genealogy`)

Integration genealogischer Stammbäume und Familiendatenarchive.

### Kernfeatures:
* **GEDCOM 5.5.1 & 7.0 Parser:** Einlesen und Rendern genealogischer Daten.
* **Datenschutzfilter:** Automatische Anonymisierung von lebenden Personen.
* **Visualisierung:** Stammbaum-Sektion, Ahnentafeln und Personen-Detailkarten.

---

## 6. Weave Universal Integrations (`universal-integrations`)

Universelle Schnittstelle für externe Services und Web-Einbindungen.

### Kernfeatures:
* **Widget- & Iframe-Embedding:** Einbindung von Kalendern, Buchungs-Tools, Google Maps oder OpenStreetMap.
* **Content Security:** Automatische Bereinigung und Validierung externer Embed-Codes.

---

*Dokumentation gepflegt vom WeaveCMS Core Team.*
