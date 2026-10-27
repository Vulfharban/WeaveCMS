# WeaveCMS Shop & E-Commerce – REST-API & Schnittstellendokumentation

> **Offizielle Schnittstellen- und Integrationsdokumentation für externe Systeme, Warenwirtschaften (WaWi/ERP), Marktplätze und Entwickler.**

---

## Inhaltsverzeichnis

1. [Übersicht & Architektur](#1-übersicht--architektur)
2. [Authentifizierung & Sicherheit](#2-authentifizierung--sicherheit)
3. [Basis-URL & Datenformat](#3-basis-url--datenformat)
4. [Produktverwaltung (Catalog API)](#4-produktverwaltung-catalog-api)
   - [4.1 Alle Produkte abrufen](#41-alle-produkte-abrufen)
   - [4.2 Einzelnes Produkt abrufen](#42-einzelnes-produkt-abrufen)
   - [4.3 Produkt anlegen oder aktualisieren](#43-produkt-anlegen-oder-aktualisieren)
   - [4.4 Produkt löschen](#44-produkt-löschen)
5. [Bestellverwaltung (Orders API)](#5-bestellverwaltung-orders-api)
   - [5.1 Bestellungen abrufen / filtern](#51-bestellungen-abrufen--filtern)
   - [5.2 Bestellstatus aktualisieren](#52-bestellstatus-aktualisieren)
6. [Massenabgleich: JSON Im- & Export](#6-massenabgleich-json-im--export)
   - [6.1 Produkte als JSON exportieren](#61-produkte-als-json-exportieren)
   - [6.2 Bestellungen als JSON exportieren](#62-bestellungen-als-json-exportieren)
   - [6.3 Produkte via JSON importieren (Merge/Replace)](#63-produkte-via-json-importieren-mergereplace)
7. [Fehlerbehandlung & HTTP-Statuscodes](#7-fehlerbehandlung--http-statuscodes)
8. [Code-Beispiele (cURL, JavaScript / Node.js, Python, PHP)](#8-code-beispiele)
9. [Erweiterung via PHP-Hooks (Custom API Actions)](#9-erweiterung-via-php-hooks)

---

## 1. Übersicht & Architektur

Die WeaveCMS Shop-REST-API ermöglicht die nahtlose Kopplung des integrierten Onlineshops mit externen Drittsystemen wie:
* **Warenwirtschaftssystemen (ERP / WaWi):** z. B. JTL, Lexware, SevDesk, FastBill, Billbee.
* **POS-Kassensystemen:** Vor-Ort-Verkauf mit zentralem Katalogabgleich.
* **Automatisierungs-Pipelines:** CI/CD-Skripte, Lager- und Bestands-Synchronisation.

Alle Endpunkte werden über ein einziges, performantes PHP-Gateway bereitgestellt, ohne dass externe Frameworks oder Composer-Abhängigkeiten erforderlich sind.

---

## 2. Authentifizierung & Sicherheit

Geschützte Management-Endpunkte erfordern einen gültigen **API-Schlüssel (Bearer Token)**.

### 2.1 API-Schlüssel einrichten
1. Öffne das WeaveCMS Backend: `Administration -> Plugins -> Shop & E-Commerce`.
2. Klicke auf den Unterreiter **„Schnittstellen & Im/Export“**.
3. Aktiviere die Checkbox **„REST-API für diesen Webshop aktivieren“**.
4. Kopiere den Schlüssel (Format: `wcsv_...`) oder generiere bei Bedarf einen neuen.

### 2.2 Übertragungsmöglichkeiten des Tokens
Die API unterstützt drei gleichberechtigte Authentifizierungsmethoden:

| Methode | Beispiel | Empfohlen für |
| :--- | :--- | :--- |
| **HTTP Authorization Header** | `Authorization: Bearer wcsv_7a8b9c0d1e2f3a...` | **Standard (Empfohlen)** |
| **Benutzerdefinierter Header** | `X-API-KEY: wcsv_7a8b9c0d1e2f3a...` | Systeme ohne Bearer-Support |
| **Query-Parameter** | `?api_key=wcsv_7a8b9c0d1e2f3a...` | Webhooks / Schnelle Tests |

*Hinweis:* Wenn im selben Browser eine aktive WeaveCMS-Administratorsitzung besteht, werden administrative Anfragen auch ohne Token-Header autorisiert.

---

## 3. Basis-URL & Datenformat

* **Endpunkt-URL:** `http(s)://<deine-domain>/api/shop.php`
* **Content-Type:** `application/json; charset=utf-8` (für Anfragen mit Body und alle Antworten)
* **Datumsformat:** ISO 8601 / `Y-m-d H:i:s` (z. B. `2026-10-27 12:00:00`)
* **Währung:** ISO 4217 (Standard: `EUR`)

---

## 4. Produktverwaltung (Catalog API)

### 4.1 Alle Produkte abrufen
Gibt alle Produkte des Katalogs zurück. Optional kann nach Kategorie gefiltert werden.

* **Methode:** `GET`
* **Aktion:** `action=list_products` (oder `action=api_list_products`)
* **Parameter:**
  * `category` *(optional)*: Filtert nach exaktem Kategorienamen.

**Beispiel-Anfrage (cURL):**
```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/shop.php?action=list_products&category=Flechtkunst"
```

**Erfolgsantwort (200 OK):**
```json
{
  "success": true,
  "count": 1,
  "products": [
    {
      "id": "prod_korb01",
      "title": "Handgefertigter Weidenkorb „Klassik“",
      "sku": "KORB-01",
      "category": "Flechtkunst",
      "description": "Traditionell aus naturbelassener Weide geflochten.",
      "price": 49.90,
      "tax_rate": 19,
      "stock": 4,
      "unlimited": false,
      "image": "images/basket_classic.jpg",
      "active": true,
      "cost_price": 22.46,
      "location": "Hauptlager A",
      "min_stock": 3,
      "supplier": "Eigenfertigung / Atelier",
      "supplier_sku": "",
      "unit": "Stück",
      "barcode": "4260123456789",
      "created_at": "2026-10-22 07:51:58",
      "updated_at": "2026-10-27 12:05:24"
    }
  ]
}
```

---

### 4.2 Einzelnes Produkt abrufen
Ruft ein Produkt anhand seiner eindeutigen ID oder SKU ab.

* **Methode:** `GET`
* **Aktion:** `action=get_product`
* **Parameter:**
  * `id`: Eindeutige interne Produkt-ID (z. B. `prod_korb01`)
  * *oder* `sku`: Artikelnummer / SKU (z. B. `KORB-01`)

**Beispiel-Anfrage:**
```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/shop.php?action=get_product&sku=KORB-01"
```

---

### 4.3 Produkt anlegen oder aktualisieren
Legt ein neues Produkt an oder überschreibt ein vorhandenes, wenn `id` existiert.

* **Methode:** `POST`
* **Aktion:** `action=save_product`
* **Payload (JSON):**
  * `title` *(string, Pflicht)*: Artikelbezeichnung
  * `sku` *(string, optional)*: Artikelnummer (wird sonst generiert)
  * `price` *(float, Pflicht)*: Brutto-Verkaufspreis
  * `category` *(string, optional)*: Kategorie (Standard: `Allgemein`)
  * `description` *(string, optional)*: Beschreibungstext
  * `stock` *(int, optional)*: Aktueller Lagerbestand (Standard: `0`)
  * `cost_price` *(float, optional)*: Einkaufspreis (EK)
  * `tax_rate` *(int, optional)*: MwSt.-Satz (`19`, `7` oder `0`)
  * `location` *(string, optional)*: Lagerplatz / Regal
  * `min_stock` *(int, optional)*: Meldebestand (Standard: `3`)
  * `active` *(bool, optional)*: Im Shop sichtbar (Standard: `true`)
  * `unlimited` *(bool, optional)*: Unbegrenzt verfügbar (z. B. digitale Gutscheine)

**Beispiel-Anfrage:**
```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "Neuer Weidenkorb Oval",
       "sku": "KORB-02",
       "category": "Flechtkunst",
       "price": 54.90,
       "cost_price": 24.00,
       "stock": 8,
       "min_stock": 2,
       "location": "Regal B-01",
       "tax_rate": 19,
       "active": true
     }' \
     "https://example.com/api/shop.php?action=save_product"
```

**Erfolgsantwort (200 OK):**
```json
{
  "success": true,
  "message": "Produkt angelegt.",
  "product": {
    "id": "prod_a1b2c3d4",
    "title": "Neuer Weidenkorb Oval",
    "sku": "KORB-02",
    "price": 54.9,
    "stock": 8,
    "active": true
  }
}
```

---

### 4.4 Produkt löschen
Entfernt ein Produkt vollständig aus dem Katalog.

* **Methode:** `POST` oder `DELETE`
* **Aktion:** `action=delete_product`
* **Parameter:** `id` (Produkt-ID)

```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -d "id=prod_a1b2c3d4" \
     "https://example.com/api/shop.php?action=delete_product"
```

---

## 5. Bestellverwaltung (Orders API)

### 5.1 Bestellungen abrufen / filtern
Ruft alle im Shop eingegangenen Kundenbestellungen ab.

* **Methode:** `GET`
* **Aktion:** `action=list_orders`
* **Parameter:**
  * `status` *(optional)*: Filtert nach Status (`pending`, `paid`, `processing`, `shipped`, `completed`, `cancelled`).

**Beispiel-Anfrage:**
```bash
curl -H "Authorization: Bearer DEIN_API_KEY" \
     "https://example.com/api/shop.php?action=list_orders&status=paid"
```

**Erfolgsantwort (200 OK):**
```json
{
  "success": true,
  "count": 1,
  "orders": [
    {
      "order_id": "ORD-20261027-A9F1C2",
      "status": "paid",
      "payment_method": "wero",
      "payment_ref": "WERO-INSTANT-482910",
      "customer": {
        "name": "Maria Musterfrau",
        "email": "maria@example.com",
        "street": "Musterweg 12",
        "zip": "10115",
        "city": "Berlin",
        "country": "Deutschland"
      },
      "items": [
        {
          "id": "prod_korb01",
          "title": "Handgefertigter Weidenkorb „Klassik“",
          "price": 49.90,
          "qty": 1,
          "total": 49.90
        }
      ],
      "subtotal": 49.90,
      "shipping": 4.90,
      "tax": 8.75,
      "total": 54.80,
      "currency": "EUR",
      "created_at": "2026-10-27 10:14:22"
    }
  ]
}
```

---

### 5.2 Bestellstatus aktualisieren
Wird typischerweise von Warenwirtschaften aufgerufen, wenn eine Sendung verpackt oder verschickt wurde (`shipped`).

* **Methode:** `POST`
* **Aktion:** `action=update_order_status`
* **Payload (JSON oder POST):**
  * `order_id`: Bestellnummer (z. B. `ORD-20261027-A9F1C2`)
  * `status`: Neuer Status (`pending`, `paid`, `processing`, `shipped`, `completed`, `cancelled`)

**Beispiel-Anfrage:**
```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"order_id":"ORD-20261027-A9F1C2", "status":"shipped"}' \
     "https://example.com/api/shop.php?action=update_order_status"
```

---

## 6. Massenabgleich: JSON Im- & Export

Für Backups, Migrationen oder zyklische Synchronisationen stehen strukturierte Im- und Export-Endpunkte zur Verfügung.

### 6.1 Produkte als JSON exportieren
* **Endpunkt:** `GET /api/shop.php?action=export_products`
* Liefert den gesamten Katalog im normierten JSON-Schema Version 1.0.

### 6.2 Bestellungen als JSON exportieren
* **Endpunkt:** `GET /api/shop.php?action=export_orders`
* Liefert den vollständigen Bestellhistorien-Baum für Buchhaltung und Steuerprüfungen.

### 6.3 Produkte via JSON importieren (Merge/Replace)
* **Endpunkt:** `POST /api/shop.php?action=import_products`
* **Parameter:**
  * `mode`: `'merge'` (bestehende Artikel anhand SKU/ID updaten, neue anfügen) oder `'replace'` (Katalog vollständig überschreiben).
  * Request-Body: Rohes JSON-Array oder Objekt mit `{ "products": [ ... ] }`.

**Beispiel:**
```bash
curl -X POST \
     -H "Authorization: Bearer DEIN_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "mode": "merge",
       "products": [
         {"sku": "KORB-01", "stock": 10, "price": 49.90},
         {"title": "Neuer Artikel", "sku": "ART-99", "price": 19.00, "stock": 25}
       ]
     }' \
     "https://example.com/api/shop.php?action=import_products"
```

**Rückgabe:**
```json
{
  "success": true,
  "imported": 1,
  "updated": 1,
  "total": 5,
  "errors": []
}
```

---

## 7. Fehlerbehandlung & HTTP-Statuscodes

Alle API-Antworten liefern ein einheitliches JSON-Format mit dem Schlüssel `success` (`true` oder `false`).

| HTTP-Code | Bedeutung | Ursache / Lösung |
| :--- | :--- | :--- |
| **`200 OK`** | Erfolgreich | Anfrage wurde regulär ausgeführt. |
| **`400 Bad Request`** | Ungültige Eingabe | Pflichtparameter (z. B. `title`, `price` oder `order_id`) fehlen oder sind fehlerhaft. |
| **`401 Unauthorized`** | Fehlende Authentifizierung | API-Schlüssel fehlt, ist ungültig oder API ist im Backend deaktiviert. |
| **`404 Not Found`** | Nicht gefunden | Produkt oder Bestellung mit angegebener ID/SKU existiert nicht. |
| **`500 Server Error`** | Interner Serverfehler | Dateizugriffsfehler oder unvorhergesehene Exception (JSON enthält Fehlermeldung). |

**Beispiel-Fehlerantwort (401):**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Ungültiger oder fehlender API-Schlüssel für den Webshop."
}
```

---

## 8. Code-Beispiele

### JavaScript / Fetch (Browser & Node.js)
```javascript
const API_KEY = 'wcsv_dein_api_schluessel';
const BASE_URL = 'https://example.com/api/shop.php';

async function updateProductStock(sku, newStock) {
    const res = await fetch(`${BASE_URL}?action=save_product`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${API_KEY}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ sku: sku, stock: newStock })
    });
    const data = await res.json();
    console.log('Update Result:', data);
}
```

### Python (3.8+)
```python
import requests

API_KEY = "wcsv_dein_api_schluessel"
BASE_URL = "https://example.com/api/shop.php"
headers = {"Authorization": f"Bearer {API_KEY}"}

# Alle offenen Bestellungen abrufen
response = requests.get(f"{BASE_URL}?action=list_orders&status=paid", headers=headers)
orders = response.json().get("orders", [])

print(f"Gefundene bezahlte Bestellungen: {len(orders)}")
for order in orders:
    print(f"- {order['order_id']}: {order['total']} EUR von {order['customer']['name']}")
```

---

## 9. Erweiterung via PHP-Hooks

Entwickler können die `api/shop.php` um eigene Aktionen erweitern, ohne Kern-Dateien zu modifizieren:

```php
// In deinem eigenen Plugin (plugin.php):
PluginManager::addFilter('shop_api_action', function($handled, $action, $input, $shopConfig) {
    if ($action === 'my_custom_sync') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'custom_message' => 'Eigene Schnittstellenlogik erfolgreich ausgeführt.'
        ]);
        return true; // Markiert die Aktion als behandelt
    }
    return $handled;
});
```
