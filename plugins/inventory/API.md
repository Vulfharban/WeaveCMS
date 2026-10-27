# WeaveCMS Lager- & Artikelverwaltung (Inventory) – REST-API & Schnittstellendokumentation

> **Offizielle Integrations- und Entwicklerdokumentation für Kassensysteme (POS), Handheld-Barcode-Scanner, ERP/WaWi-Kopplung und automatisierten Bestandsabgleich.**

---

## Inhaltsverzeichnis

1. [Übersicht & Einsatzbereiche](#1-übersicht--einsatzbereiche)
2. [Authentifizierung & Sicherheit](#2-authentifizierung--sicherheit)
3. [Basis-URL & Datenformat](#3-basis-url--datenformat)
4. [Kassen- & Bestands-Endpunkte (POS / Cash Desk)](#4-kassen---bestands-endpunkte)
   - [4.1 Schneller Bestandsabgleich (`stock_adjust`)](#41-schneller-bestandsabgleich-stock_adjust)
   - [4.2 Wareneingang & Zubuchung (`receive_stock`)](#42-wareneingang--zubuchung-receive_stock)
   - [4.3 Artikel abrufen / Barcode-Suche (`articles` & `get_article`)](#43-artikel-abrufen--barcode-suche)
   - [4.4 Meldebestand & Nachbestell-Alarm (`low_stock`)](#44-meldebestand--nachbestell-alarm-low_stock)
5. [Audit-Trail & Lagerbewegungs-Protokoll (`movements`)](#5-audit-trail--lagerbewegungs-protokoll)
6. [Massenabgleich: JSON & CSV Im-/Export](#6-massenabgleich-json--csv-im-export)
   - [6.1 JSON-Export der Bestände](#61-json-export-der-bestände)
   - [6.2 Revisionssicherer Audit-Export (JSON)](#62-revisionssicherer-audit-export-json)
   - [6.3 CSV-Export für Excel & Buchhaltung](#63-csv-export-für-excel--buchhaltung)
   - [6.4 JSON-Import mit automatischem Log-Eintrag](#64-json-import-mit-automatischem-log-eintrag)
7. [Automatischer Abgleich mit dem Webshop](#7-automatischer-abgleich-mit-dem-webshop)
8. [Code-Beispiele (cURL, JavaScript, Python, Barcode-Scanner-App)](#8-code-beispiele)
9. [Fehlerbehandlung & Statuscodes](#9-fehlerbehandlung--statuscodes)

---

## 1. Übersicht & Einsatzbereiche

Das Lager- und Artikelverwaltungs-Plugin (`inventory`) von WeaveCMS erweitert das CMS um ein autarkes, revisionssicheres Warenwirtschaftsmodul. Es ist direkt mit dem Online-Shop synchronisiert und ermöglicht den Echtzeitabgleich für:

* **Atelier- & Ladenkassen (POS):** Vor-Ort-Verkäufe per Tablet oder Bondrucker reduzieren sofort den Bestand im Webshop.
* **Handheld-Barcode-Scanner:** Erfassung von Barcodes/EANs bei Inventur oder Wareneingang.
* **Automatisierte Meldebestand-Alarme:** Sofortige E-Mail-Warnung und Security-Logging bei Unterschreiten des Mindestbestands.
* **Audit-Trail:** Jede Buchung (Verkauf, Wareneingang, Inventurkorrektur, Import) wird unveränderlich mit Zeitstempel, Benutzer und Beleg im `data/inventory_log.json` protokolliert.

---

## 2. Authentifizierung & Sicherheit

Zugriffe auf geschützte Lager-Endpunkte erfordern einen gültigen **Lager-API-Schlüssel (Bearer Token)**.

### 2.1 API-Schlüssel einrichten
1. Im WeaveCMS Backend anmelden: `Administration -> Plugins -> Lager- & Artikelverwaltung`.
2. Auf den Reiter **„Einstellungen & Export“** wechseln.
3. Im Bereich **„REST-API & Kassensystem (POS)“** die Checkbox **„Lager-REST-API aktivieren“** einschalten.
4. Den Schlüssel (Format: `wciv_...`) kopieren oder neu generieren.

### 2.2 Authentifizierungsmethoden
Der API-Schlüssel kann über folgende Wege übergeben werden:

| Methode | Syntax | Typische Verwendung |
| :--- | :--- | :--- |
| **HTTP Authorization Header** | `Authorization: Bearer wciv_1a2b3c4d5e...` | **Standard (Kassen, Apps, Server)** |
| **Custom Header** | `X-API-KEY: wciv_1a2b3c4d5e...` | Kassen-Hardware ohne Bearer-Support |
| **Query-Parameter** | `?api_key=wciv_1a2b3c4d5e...` | Schnelle Browser- & Scanner-Tests |

*Hinweis:* Bei einer aktiven Administratorsitzung im Browser greift die Autorisierung automatisch.

---

## 3. Basis-URL & Datenformat

* **Endpunkt-URL:** `http(s)://<deine-domain>/api/inventory.php`
* **Content-Type:** `application/json; charset=utf-8`
* **HTTP-Methoden:** `GET` für reine Datenabfragen, `POST` für Bestandsänderungen und Wareneingänge.

---

## 4. Kassen- & Bestands-Endpunkte

### 4.1 Schneller Bestandsabgleich (`stock_adjust`)
Wird bei einem Vor-Ort-Verkauf (z. B. Atelier-Kasse, Marktstand) oder einer manuellen Korrektur aufgerufen. Der Endpunkt unterstützt sowohl relative Mengenänderungen (`change: -1`) als auch absolute Zielbestände (`new_stock: 5`).

* **Methode:** `POST` (oder `GET`)
* **Aktion:** `action=stock_adjust`
* **Parameter (JSON oder Form-POST):**
  * `article_id` *oder* `sku`: Artikel-Identifikator (z. B. `prod_korb01` oder `KORB-01`)
  * `change` *(int, optional)*: Relative Bestandsänderung (z. B. `-1` für Verkauf, `+5` für Fund)
  * `new_stock` *(int, optional)*: Neuer absoluter Soll-Bestand (z. B. `12`)
  * `reason` *(string, optional)*: Buchungsgrund (z. B. `Kassenverkauf Atelier`, `Bruch / Schwund`)
  * `user` *(string, optional)*: Name der Kasse oder des Mitarbeiters (Standard: `REST-API`)

**Beispiel-Anfrage (Vor-Ort-Verkauf über cURL):**
```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "sku": "KORB-01",
       "change": -1,
       "reason": "Atelier Kasse 1 Verkauf",
       "user": "Kasse-Atelier"
     }' \
     "https://example.com/api/inventory.php?action=stock_adjust"
```

**Erfolgsantwort (200 OK):**
```json
{
  "success": true,
  "article_id": "prod_korb01",
  "old_stock": 4,
  "new_stock": 3,
  "diff": -1,
  "message": "Bestand erfolgreich auf 3 aktualisiert."
}
```

---

### 4.2 Wareneingang & Zubuchung (`receive_stock`)
Bucht neu eingetroffene Ware ein, aktualisiert optional den Einkaufspreis (EK) sowie den Lieferanten und erstellt automatisch einen Wareneingangs-Eintrag im Audit-Log.

* **Methode:** `POST`
* **Aktion:** `action=receive_stock`
* **Parameter:**
  * `article_id` *(string, Pflicht)*: ID des Artikels
  * `quantity` *(int, Pflicht)*: Gelieferte Stückzahl (mindestens `1`)
  * `cost_price` *(float, optional)*: Neuer Einkaufspreis netto pro Stück
  * `delivery_note` *(string, optional)*: Lieferscheinnummer
  * `supplier` *(string, optional)*: Lieferant
  * `note` *(string, optional)*: Anmerkung zur Lieferung
  * `user` *(string, optional)*: Name des Mitarbeiters

**Beispiel:**
```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "article_id": "prod_korb01",
       "quantity": 10,
       "cost_price": 21.50,
       "delivery_note": "LS-2026-9912",
       "supplier": "Weidenhof Nord",
       "note": "Erste Charge Frühlingsernte",
       "user": "Lagerleitung"
     }' \
     "https://example.com/api/inventory.php?action=receive_stock"
```

**Antwort:**
```json
{
  "success": true,
  "message": "Wareneingang von 10 Stück erfolgreich verbucht."
}
```

---

### 4.3 Artikel abrufen & Barcode-Suche (`articles` & `get_article`)

#### A: Alle Artikel mit Lagerdetails abrufen
* **Endpunkt:** `GET /api/inventory.php?action=articles`
* **Filter:**
  * `category`: Filterung nach Kategorie (z. B. `Flechtkunst`)
  * `q`: Volltextsuche über Titel, SKU oder Barcode

#### B: Einzelnen Artikel abfragen (z. B. via Barcode-Scanner)
* **Endpunkt:** `GET /api/inventory.php?action=get_article`
* **Parameter:** `barcode`, `sku` oder `id`

**Beispiel (Barcode Scan):**
```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/inventory.php?action=get_article&barcode=4260123456789"
```

**Rückgabe:**
```json
{
  "success": true,
  "article": {
    "id": "prod_korb01",
    "title": "Handgefertigter Weidenkorb „Klassik“",
    "sku": "KORB-01",
    "stock": 4,
    "min_stock": 3,
    "location": "Hauptlager A",
    "price": 49.90,
    "cost_price": 22.46,
    "margin_euro": 27.44,
    "margin_percent": 55,
    "is_low_stock": false,
    "barcode": "4260123456789"
  }
}
```

---

### 4.4 Meldebestand & Nachbestell-Alarm (`low_stock`)
Liefert alle Artikel, deren Lagerbestand den Mindestbestand erreicht oder unterschritten hat (`stock <= min_stock`).

* **Methode:** `GET`
* **Aktion:** `action=low_stock`

```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/inventory.php?action=low_stock"
```

---

## 5. Audit-Trail & Lagerbewegungs-Protokoll (`movements`)

Jede Bestandsveränderung wird mit Vorher-/Nachher-Zustand, Delta, Buchungsart und Verfasser festgehalten.

* **Methode:** `GET`
* **Aktion:** `action=movements`
* **Parameter:** `limit` *(int, optional, Standard: 100, Max: 1000)*

**Beispiel:**
```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/inventory.php?action=movements&limit=5"
```

**Rückgabe (Auszug):**
```json
{
  "success": true,
  "count": 1,
  "movements": [
    {
      "id": "mov_36f775d18e",
      "timestamp": "2026-10-27 12:05:24",
      "article_id": "prod_korb01",
      "article_title": "Handgefertigter Weidenkorb „Klassik“",
      "sku": "KORB-01",
      "type": "shop_sale",
      "delta": -1,
      "unit": "Stück",
      "stock_before": 4,
      "stock_after": 3,
      "reference": "ORD-20261027-A9F1C2",
      "note": "Webshop-Verkauf an Maria Musterfrau (Zahlung via WERO)",
      "user": "Webshop-Engine"
    }
  ]
}
```

---

## 6. Massenabgleich: JSON & CSV Im-/Export

### 6.1 JSON-Export der Bestände
* `GET /api/inventory.php?action=export_json`
* Liefert den gesamten Warenbestand inklusive Einkaufspreisen, Margen, Lagerplätzen und Barcodes.

### 6.2 Revisionssicherer Audit-Export (JSON)
* `GET /admin/?action=inv_export_movements_json`
* Vollständiger Export des Buchungsjournals für Wirtschaftsprüfungen oder Finanzämter.

### 6.3 CSV-Export für Excel & Buchhaltung
* `GET /admin/?action=inv_export_csv`
* Tabellarischer CSV-Download (UTF-8) für Tabellenkalkulationen.

### 6.4 JSON-Import mit automatischem Log-Eintrag
* `POST /api/inventory.php?action=import_json`
* **Parameter:**
  * `mode`: `'merge'` (Bestände abgleichen, bestehende Stammdaten aktualisieren, neue anfügen) oder `'replace'` (vollständig ersetzen).
  * `user`: Ausführender Benutzer (z. B. `ERP-Import-Bot`).

---

## 7. Automatischer Abgleich mit dem Webshop

Das Lager-Plugin klinkt sich über den Hook `shop_order_created` automatisch in den Bezahlzyklus des Webshops ein:
1. Ein Kunde bestellt im Webshop.
2. Der Lagerbestand wird sofort im zentralen Katalog reduziert.
3. Im Lager-Audit-Trail wird eine Buchung vom Typ `shop_sale` vermerkt.
4. Unterschreitet der Artikel den Mindestbestand, wird eine automatisierte E-Mail-Warnung an die hinterlegte Melde-Adresse versandt.

---

## 8. Code-Beispiele

### JavaScript / Fetch: Kassen-Verkauf (Vor-Ort-Terminal)
```javascript
async function registerPosSale(sku, quantity = 1, cashierName = "Kasse 1") {
    const response = await fetch("https://example.com/api/inventory.php?action=stock_adjust", {
        method: "POST",
        headers: {
            "Authorization": "Bearer wciv_dein_api_schluessel",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            sku: sku,
            change: -quantity,
            reason: "Vor-Ort-Barverkauf",
            user: cashierName
        })
    });
    const result = await response.json();
    if (result.success) {
        console.log(`Neuer Bestand für ${sku}: ${result.new_stock}`);
    } else {
        alert("Fehler beim Bestandsabgleich: " + result.message);
    }
}
```

### Python: Automatischer Abruf kritischer Bestände (Cronjob / Monitoring)
```python
import requests

API_KEY = "wciv_dein_api_schluessel"
URL = "https://example.com/api/inventory.php?action=low_stock"

res = requests.get(URL, headers={"Authorization": f"Bearer {API_KEY}"})
data = res.json()

if data.get("count", 0) > 0:
    print(f"WARNUNG: {data['count']} Artikel unter Meldebestand!")
    for art in data["articles"]:
        print(f" - {art['title']} (SKU: {art['sku']}): Nur noch {art['stock']} {art['unit']} auf Lager!")
```

---

## 9. Fehlerbehandlung & Statuscodes

| Statuscode | Fehlerursache | Lösung |
| :--- | :--- | :--- |
| **`400 Bad Request`** | Fehlende Pflichtfelder (z. B. `article_id` fehlt) | Parameter prüfen und übergeben |
| **`401 Unauthorized`** | Fehlender oder falscher API-Schlüssel | Schlüssel in den Einstellungen abgleichen |
| **`404 Not Found`** | Artikel nicht gefunden | SKU, Barcode oder interne ID überprüfen |
| **`500 Server Error`** | Dateisystem-Schreibschutz | Schreibrechte auf `data/` und `data/shop_products.json` prüfen |
