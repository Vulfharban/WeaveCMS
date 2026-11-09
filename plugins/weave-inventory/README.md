# WeaveCMS Lager- & Artikelverwaltung (Inventory)

> **Integrierte Bestands-, Lagerplatz- und Margenverwaltung mit Barcode-Scanner-Unterstützung, automatischem Meldebestand-Alarm und Kassen-Schnittstelle.**

---

## Übersicht

Das Lager- und Artikelverwaltungs-Plugin (`inventory`) für WeaveCMS rüstet den integrierten Online-Shop und das CMS zu einem vollwertigen Warenwirtschafts- und Kassensystem auf.

### Kern-Features
* **Lagerplatz- & Bestandsmanagement:** Verwaltung von Lagerorten (z. B. `Hauptlager A`, `Regal B-02`, `Werkstatt`, `Laden`).
* **Kaufmännische Kennzahlen:** Einkaufspreise (EK), Verkaufspreise (VK), Margenbetrag in Euro und Margenanteil in Prozent.
* **Meldebestand-Alarme:** Automatische Erkennung kritischer Bestände mit optionalem E-Mail-Alarm und Security-Log-Einträgen.
* **Wareneingang & Zubuchung:** Dialog zur schnellen Erfassung von Neuanlieferungen mit automatischer Dokumentation von Lieferschein und Lieferant.
* **Revisionssicherer Audit-Trail:** Lückenlose Protokollierung sämtlicher Bestandsveränderungen (Verkäufe, Inventurkorrekturen, Wareneingänge, Löschungen) in `data/inventory_log.json`.
* **Stichtags-Inventur:** Schneller Abgleich von Soll- und Ist-Beständen mit automatischer Differenzberechnung.
* **REST-API & Kassenanbindung:** Tokenbasierte Schnittstelle für POS-Kassensysteme, Vor-Ort-Verkäufe und Barcode-Scanner.
* **JSON- & CSV-Exporte:** Volle Datensouveränität für Tabellenkalkulationen, Steuerberater und Backups.

---

## Dokumentation & Schnittstellen

* **[Schnittstellendokumentation & REST-API Guide (API.md)](API.md):** Ausführliche Dokumentation aller Kassen-, Barcode- und Synchronisations-Endpunkte mit Code-Beispielen in cURL, JavaScript und Python.

---

## Architektur & Datenhaltung

* **Logik & Berechnungen:** `InventoryManager.php`
* **Admin-Dashboard & Subtabs:** `admin/settings.php`
* **REST-API Gateway:** `api/inventory.php`
* **Stammdaten:** Synchronisiert in `data/shop_products.json`
* **Buchungsjournal:** `data/inventory_log.json`
