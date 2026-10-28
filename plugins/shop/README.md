# WeaveCMS Shop & E-Commerce Plugin

> **Integrierte E-Commerce-Engine mit digitaler Kasse, Mehrfach-Zahlungsanbindung und offener Plugin-Architektur.**

Das Shop-Modul für WeaveCMS stellt einen vollständigen, performanten Online-Shop ohne externe Abhängigkeiten bereit. Neben integrierter Zahlungsabwicklung für **Kreditkarte**, **PayPal**, **WERO (European Instant Payment)** und **Vorkasse / Banküberweisung** verfügt das Modul über ein modulares Hook-System, über das Dritt- und Erweiterungs-Plugins nahtlos aufsetzen und neue Funktionen bereitstellen können.

---

## Inhaltsverzeichnis

1. [Architektur & Konzept](#1-architektur--konzept)
2. [Hooks & Filter: Übersicht](#2-hooks--filter-übersicht)
3. [Zahlungsarten erweitern (Payment Gateways)](#3-zahlungsarten-erweitern-payment-gateways)
4. [Warenkorb, Rabatte & Versandkosten (Cart Engine)](#4-warenkorb-rabatte--versandkosten-cart-engine)
5. [Produkte & Lagerverwaltung (Catalog & Inventory)](#5-produkte--lagerverwaltung-catalog--inventory)
6. [Bestell-Lifecycle & Status-Workflows](#6-bestell-lifecycle--status-workflows)
7. [E-Mail-Vorlagen individualisieren](#7-e-mail-vorlagen-individualisieren)
8. [Admin-Erweiterung & Menü-Governance](#8-admin-erweiterung--menü-governance)
9. [API-Erweiterung (AJAX / REST)](#9-api-erweiterung-ajax--rest)
10. [Entwickler-Beispiel: Gutschein-Plugin](#10-entwickler-beispiel-gutschein-plugin)

---

## 1. Architektur & Konzept

Das Modul ist nach strengen WeaveCMS-Architekturprinzipien strukturiert:

* **Geschäftslogik & Datenverwaltung:** `ShopManager.php` verwaltet Produkte (`data/shop_products.json`) und Bestellungen (`data/shop_orders.json`), führt Preis- und Steuerberechnungen (§ 19 UStG / Standard-MwSt.) durch und wickelt Bezahlvorgänge ab.
* **Frontend-Präsentation:** `views/shop_section.php` bietet den modernen Produktkatalog, Filterleisten, schwebenden Warenkorb-Drawer und das SSL-Checkout-Modal.
* **REST-/AJAX-Endpunkt:** `api/shop.php` verarbeitet asynchrone Berechnungen, Produktlisten und Bestellabschlüsse.
* **Administration:** `admin/settings.php` bietet Dashboards, KPI-Statistiken, Produkt- und Bestellverwaltung sowie Konfiguration der Gateways.
* **Menü-Governance:** Addon-Plugins verändern nicht die Hauptnavigation des CMS, sondern klinken sich über den Filter `shop_admin_subtabs` sauber in die Unterreiter des Shop-Bereichs ein.

---

## 2. Hooks & Filter: Übersicht

Alle Hooks werden über den nativen `PluginManager` von WeaveCMS ausgeführt:

| Hook-Name | Typ | Parameter / Rückgabe | Zweck |
| :--- | :--- | :--- | :--- |
| `shop_payment_gateways` | **Filter** | `(array $gateways, array $shopConfig) -> array` | Registriert neue Bezahlmethoden für den Checkout |
| `shop_process_payment` | **Filter** | `(null\|array $response, string $method, float $total, array $details, array $config) -> array` | Führt Bezahlung für benutzerdefinierte Gateways durch |
| `shop_checkout_payment_fields` | **Action** | `(array $shopConfig)` | Rendert benutzerdefinierte Formularfelder im Kassenmodal |
| `shop_calculate_cart` | **Filter** | `(array $cartResult, array $cartItems, array $shopConfig) -> array` | Modifiziert Zwischensumme, Rabatte, Steuern oder Versand |
| `shop_cart_after_items` | **Action** | `(array $shopConfig)` | Rendert UI-Elemente im Warenkorb-Drawer (z. B. Gutscheinfeld) |
| `shop_cart_summary_rows` | **Action** | `(array $shopConfig)` | Rendert Summenzeilen im Drawer (z. B. Rabatt-Abzug) |
| `shop_product_data` | **Filter** | `(array $item, array $rawData) -> array` | Erweitert Produktattribute beim Anlegen/Bearbeiten |
| `shop_product_added` | **Action** | `(array $newProduct)` | Feuert nach Erstellen eines neuen Produkts |
| `shop_product_updated` | **Action** | `(string $productId, array $data)` | Feuert nach Aktualisierung eines Produkts |
| `shop_product_deleted` | **Action** | `(string $productId)` | Feuert nach Löschen eines Produkts |
| `shop_admin_product_modal_fields` | **Action** | `(array $shopConfig)` | Fügt Eingabefelder in das Produktmodal im Adminbereich ein |
| `shop_before_create_order` | **Filter** | `(array $orderData) -> array` | Modifiziert Bestelldaten unmittelbar vor dem Speichern |
| `shop_order_created` | **Action** | `(array $newOrder)` | Feuert nach Anlage einer neuen Bestellung |
| `shop_allowed_order_statuses` | **Filter** | `(array $statuses) -> array` | Registriert zusätzliche Bestellstatus |
| `shop_order_status_updated` | **Action** | `(string $orderId, string $newStatus, string $oldStatus, array $order)` | Feuert nach Statuswechsel einer Bestellung |
| `shop_order_customer_email_subject` | **Filter** | `(string $subject, array $order) -> string` | Passt Betreff der Kunden-Bestellbestätigung an |
| `shop_order_customer_email_html` | **Filter** | `(string $html, array $order) -> string` | Passt HTML-Body der Kundenbestätigung an |
| `shop_order_customer_email_text` | **Filter** | `(string $text, array $order) -> string` | Passt Text-Body der Kundenbestätigung an |
| `shop_order_admin_email_html` | **Filter** | `(string $html, array $order) -> string` | Passt HTML-Body der Admin-Bestellbenachrichtigung an |
| `shop_admin_subtabs` | **Filter** | `(array $subTabs, array $config) -> array` | Registriert eigene Unterreiter im Shop-Adminbereich |
| `shop_render_admin_subtab_{key}` | **Action** | `(array $config, string $csrf_token, array $products, array $orders)` | Rendert Ansicht des benutzerdefinierten Admin-Subtabs |
| `shop_api_action` | **Filter** | `(bool $handled, string $action, array $input, array $shopConfig) -> bool` | Ermöglicht eigene API-Routen in `api/shop.php` |

---

## 3. Zahlungsarten erweitern (Payment Gateways)

Eigene Bezahlmethoden (z. B. Barzahlung bei Abholung, Rechnungskauf oder Stripe) können mit zwei Filtern vollständig integriert werden:

```php
// 1. Gateway registrieren
PluginManager::addFilter('shop_payment_gateways', function(array $gateways, array $config) {
    $gateways['invoice'] = [
        'id'         => 'invoice',
        'title'      => 'Kauf auf Rechnung',
        'subtitle'   => 'Zahlbar innerhalb von 14 Tagen',
        'icon'       => '📄',
        'badge'      => 'Rechnung',
        'enabled'    => true,
        'has_fields' => false
    ];
    return $gateways;
});

// 2. Bezahlung abwickeln
PluginManager::addFilter('shop_process_payment', function($prev, $method, $total, $details, $config) {
    if ($method !== 'invoice') {
        return $prev;
    }

    return [
        'success'        => true,
        'payment_method' => 'invoice',
        'payment_ref'    => 'RECH-' . strtoupper(bin2hex(random_bytes(4))),
        'amount'         => $total,
        'status'         => 'pending',
        'message'        => 'Bestellung auf Rechnung erfolgreich aufgegeben. Rechnung liegt der Lieferung bei.'
    ];
});
```

---

## 4. Warenkorb, Rabatte & Versandkosten (Cart Engine)

Über den Filter `shop_calculate_cart` können Rabatt-Codes, Mengenrabatte oder Frachtkosten berechnet werden:

```php
PluginManager::addFilter('shop_calculate_cart', function(array $cartResult, array $cartItems, array $shopConfig) {
    // Beispiel: 10 % Rabatt ab 100 € Warenwert
    if ($cartResult['subtotal'] >= 100.0) {
        $discount = round($cartResult['subtotal'] * 0.10, 2);
        $cartResult['discount'] = $discount;
        $cartResult['total'] = max(0, round($cartResult['total'] - $discount, 2));
    }
    return $cartResult;
});
```

---

## 5. Produkte & Lagerverwaltung (Catalog & Inventory)

Zusätzliche Produktattribute (z. B. Download-Dateipfade oder Gewichtsangaben für Versand) können via `shop_product_data` persistiert werden:

```php
// Zusätzliche Felder abspeichern
PluginManager::addFilter('shop_product_data', function(array $item, array $rawData) {
    $item['download_url'] = trim($rawData['download_url'] ?? '');
    $item['weight_kg'] = (float)($rawData['weight_kg'] ?? 0);
    return $item;
});

// Eigenes Formularfeld im Produkt-Modal des Adminbereichs bereitstellen
PluginManager::addAction('shop_admin_product_modal_fields', function(array $config) {
    echo '
    <div style="margin-bottom:12px;">
        <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Download-Datei URL (für digitale Produkte)</label>
        <input type="text" name="download_url" class="form-control" placeholder="downloads/mein-ebook.pdf">
    </div>';
});
```

---

## 6. Bestell-Lifecycle & Status-Workflows

Addon-Plugins können eigene Bestellstatus einführen und auf Statuswechsel reagieren (z. B. Trackingnummer per E-Mail versenden, sobald Status auf `shipped` gesetzt wird):

```php
// Erlaubte Status erweitern
PluginManager::addFilter('shop_allowed_order_statuses', function(array $statuses) {
    $statuses[] = 'refunded';
    $statuses[] = 'awaiting_pickup';
    return $statuses;
});

// Auf Statuswechsel reagieren
PluginManager::addAction('shop_order_status_updated', function(string $orderId, string $newStatus, string $oldStatus, array $order) {
    if ($newStatus === 'shipped') {
        // Logik für automatische Versandbenachrichtigung
    }
});
```

---

## 7. E-Mail-Vorlagen individualisieren

Die automatischen Bestellbestätigungen an Kunden und Shop-Betreiber können gefiltert und mit individuellen Hinweisen angereichert werden:

```php
PluginManager::addFilter('shop_order_customer_email_html', function(string $html, array $order) {
    $extraNotice = '<div style="background:#fef3c7; padding:12px; border-radius:6px; margin:16px 0;">
        <strong>Hinweis:</strong> Vielen Dank für deine Unterstützung unseres lokalen Handwerksbetriebs!
    </div>';
    return str_replace('</table>', '</table>' . $extraNotice, $html);
});
```

---

## 8. Admin-Erweiterung & Menü-Governance

Um die Benutzeroberfläche des CMS sauber zu halten, klinken sich Shop-Erweiterungen direkt als Unterreiter im Shop-Adminmenü ein:

```php
// Unterreiter registrieren
PluginManager::addFilter('shop_admin_subtabs', function(array $subTabs, array $config) {
    $subTabs['coupons'] = [
        'title' => 'Gutscheine & Rabatte',
        'icon'  => 'local_offer'
    ];
    return $subTabs;
});

// Ansicht für den Unterreiter rendern
PluginManager::addAction('shop_render_admin_subtab_coupons', function($config, $csrf_token, $products, $orders) {
    echo '<div style="background:var(--card-bg, #fff); padding:1.5rem; border-radius:14px; border:1px solid var(--border-color);">';
    echo '<h4>🎟️ Gutschein-Verwaltung</h4>';
    echo '<p>Hier können Gutscheincodes und Rabattstaffeln konfiguriert werden.</p>';
    echo '</div>';
});
```

---

## 9. API-Erweiterung (AJAX / REST)

> 📘 **Vollständige REST-API Dokumentation:**  
> Für die Integration mit externen Warenwirtschaften (ERP), POS-Kassensystemen, Produktabgleichen und JSON-Im/Export siehe die dedizierte Dokumentation:  
> **👉 [WeaveCMS Shop REST-API & Schnittstellendokumentation (API.md)](API.md)**

Der zentrale Shop-Endpunkt `api/shop.php` fängt darüber hinaus anwendungsspezifische Aktionen ab und übergibt sie an den Filter `shop_api_action`:

```php
PluginManager::addFilter('shop_api_action', function($handled, $action, $input, $shopConfig) {
    if ($action === 'check_coupon') {
        $code = strtoupper(trim($input['code'] ?? ''));
        $isValid = ($code === 'WELCOME10');
        
        echo json_encode([
            'success'  => $isValid,
            'discount' => $isValid ? 10.0 : 0.0,
            'message'  => $isValid ? 'Gutschein gültig (10 € Rabatt)' : 'Ungültiger Code'
        ]);
        return true; // Markiert Aktion als erfolgreich behandelt
    }
    return $handled;
});
```

---

## 10. Entwickler-Beispiel: Gutschein-Plugin

Ein minimales Addon-Plugin unter `plugins/shop-coupons/plugin.php` benötigt lediglich folgenden Code:

```php
<?php
/**
 * Plugin Name: Shop Coupons & Discounts
 * Slug: shop-coupons
 */

// 1. Gutschein-Eingabe im Warenkorb anzeigen
PluginManager::addAction('shop_cart_after_items', function() {
    echo '
    <div style="margin-top:12px; display:flex; gap:6px;">
        <input type="text" id="coupon_code" class="form-control" placeholder="Gutscheincode eingeben..." style="font-size:0.85rem;">
        <button type="button" class="btn btn-outline" onclick="applyCoupon()" style="padding:6px 12px; font-size:0.85rem;">Einlösen</button>
    </div>';
});

// 2. Rabatt in der Warenkorbberechnung anrechnen
PluginManager::addFilter('shop_calculate_cart', function(array $cart) {
    if (!empty($_SESSION['active_coupon']) && $_SESSION['active_coupon'] === 'HERBST10') {
        $rabatt = 10.00;
        $cart['total'] = max(0.0, $cart['total'] - $rabatt);
    }
    return $cart;
});
```
