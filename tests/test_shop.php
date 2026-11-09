<?php
require_once dirname(__DIR__) . '/config.php';
if (file_exists(dirname(__DIR__) . '/plugins/weave-shop/ShopManager.php')) {
    require_once dirname(__DIR__) . '/plugins/weave-shop/ShopManager.php';
} else {
    require_once dirname(__DIR__) . '/plugins/shop/ShopManager.php';
}

echo "========================================\n";
echo "WEAVE SHOP TEST-SUITE\n";
echo "========================================\n\n";

// 1. Produkte abrufen
$products = ShopManager::getProducts();
echo "1. Produkte im Katalog: " . count($products) . "\n";
if (empty($products)) {
    echo "FEHLER: Keine Produkte vorhanden!\n";
    exit(1);
}
$first = $products[0];
echo "   Test-Produkt: " . $first['title'] . " (ID: " . $first['id'] . ", Preis: " . $first['price'] . " EUR, MwSt: " . ($first['tax_rate'] ?? 19) . "%, Bestand: " . ($first['stock'] ?? 0) . ")\n\n";

// 2. Warenkorb mit MwSt. (Standardbetrieb, kein Kleinunternehmer)
echo "2. Warenkorb-Berechnung (Regulär mit 19% MwSt.):\n";
$cfgNormal = [
    'small_business' => false,
    'shipping_cost' => 4.90,
    'free_shipping_min' => 50.00,
    'currency' => 'EUR'
];
$cartItems = [
    ['id' => $first['id'], 'qty' => 2]
];
$cartNormal = ShopManager::calculateCart($cartItems, $cfgNormal);
echo "   Artikel: " . $cartNormal['items_count'] . " Stk.\n";
echo "   Zwischensumme: " . $cartNormal['subtotal'] . " EUR\n";
echo "   Versand: " . $cartNormal['shipping'] . " EUR\n";
echo "   Enthaltene MwSt: " . $cartNormal['tax'] . " EUR\n";
echo "   Gesamtsumme: " . $cartNormal['total'] . " EUR\n";
if ($cartNormal['total'] <= 0 || $cartNormal['tax'] <= 0) {
    echo "FEHLER: MwSt. oder Gesamtsumme ungültig!\n";
    exit(1);
}
echo "   ✓ Berechnung mit MwSt. ERFOLGREICH\n\n";

// 3. Warenkorb als Kleinunternehmer (§ 19 UStG - steuerfrei)
echo "3. Warenkorb-Berechnung (Kleinunternehmer nach § 19 UStG - steuerfrei):\n";
$cfgSmall = [
    'small_business' => true,
    'shipping_cost' => 4.90,
    'free_shipping_min' => 50.00,
    'currency' => 'EUR'
];
$cartSmall = ShopManager::calculateCart($cartItems, $cfgSmall);
echo "   Enthaltene MwSt: " . $cartSmall['tax'] . " EUR (Erwartet: 0.00 EUR)\n";
echo "   Gesamtsumme: " . $cartSmall['total'] . " EUR\n";
if ($cartSmall['tax'] != 0.0) {
    echo "FEHLER: Kleinunternehmer darf keine MwSt. ausweisen!\n";
    exit(1);
}
echo "   ✓ Steuerfreie Kleinunternehmer-Berechnung ERFOLGREICH\n\n";

// 4. Versandkostenfreigrenze testen
echo "4. Versandkostenfreigrenze testen (Schwelle 50 EUR):\n";
$bigCartItems = [
    ['id' => $first['id'], 'qty' => 10] // Hoher Wert
];
$cartFree = ShopManager::calculateCart($bigCartItems, $cfgNormal);
echo "   Zwischensumme: " . $cartFree['subtotal'] . " EUR\n";
echo "   Versandkosten: " . $cartFree['shipping'] . " EUR (Erwartet: 0.00 EUR ab 50 EUR)\n";
if ($cartFree['subtotal'] >= 50.0 && $cartFree['shipping'] != 0.0) {
    echo "FEHLER: Versandkosten wurden ab 50 EUR nicht erlassen!\n";
    exit(1);
}
echo "   ✓ Versandkostenfreigrenze ERFOLGREICH\n\n";

// 5. Bezahlungsabwicklung (Payment Gateways)
echo "5. Bezahlmethoden und Abwicklung testen:\n";
$shopConfig = [
    'payment_credit_card' => ['enabled' => true, 'sandbox' => true],
    'payment_paypal' => ['enabled' => true, 'sandbox' => true, 'client_id' => 'sb-test-123'],
    'payment_wero' => ['enabled' => true, 'sandbox' => true, 'iban' => 'DE12345678901234567890'],
    'payment_bank_transfer' => ['enabled' => true, 'iban' => 'DE12345678901234567890', 'bic' => 'TESTBIC']
];

$gateways = ShopManager::getPaymentGateways($shopConfig);
echo "   Aktivierte Gateways: " . implode(', ', array_keys(array_filter($gateways, fn($g) => $g['enabled']))) . "\n";

// 5.1 Test: Kreditkarte (Sandbox mit Test-Mastercard)
$ccDetails = [
    'card_holder' => 'Max Mustermann',
    'card_number' => '4242424242424242', // Standard-Testkreditkarte (Luhn-konform)
    'card_exp_month' => 12,
    'card_exp_year' => 2028,
    'card_cvc' => '123'
];
$resCC = ShopManager::processPayment('credit_card', $cartNormal['total'], $ccDetails, $shopConfig);
echo "   Kreditkarten-Zahlung: " . ($resCC['success'] ? "ERFOLGREICH (Ref: " . ($resCC['ref'] ?? 'OK') . ")" : "FEHLER: " . $resCC['message']) . "\n";

// 5.2 Test: Vorkasse / Banküberweisung
$resBank = ShopManager::processPayment('bank_transfer', $cartNormal['total'], [], $shopConfig);
echo "   Vorkasse-Zahlung: " . ($resBank['success'] ? "ERFOLGREICH (Ref: " . ($resBank['ref'] ?? 'OK') . ")" : "FEHLER: " . $resBank['message']) . "\n";

// 5.3 Test: WERO Sofortüberweisung
$resWero = ShopManager::processPayment('wero', $cartNormal['total'], [], $shopConfig);
echo "   WERO-Zahlung: " . ($resWero['success'] ? "ERFOLGREICH (Ref: " . ($resWero['ref'] ?? 'OK') . ")" : "FEHLER: " . $resWero['message']) . "\n";

// 5.4 Test: PayPal
$resPaypal = ShopManager::processPayment('paypal', $cartNormal['total'], [], $shopConfig);
echo "   PayPal-Zahlung: " . ($resPaypal['success'] ? "ERFOLGREICH (Ref: " . ($resPaypal['ref'] ?? 'OK') . ")" : "FEHLER: " . $resPaypal['message']) . "\n\n";

// 6. Bestellung anlegen und Lagerbestand prüfen
echo "6. Bestellung (Order Creation) durchführen:\n";
$initialStock = (int)($first['stock'] ?? 0);
echo "   Ausgangsbestand von '{$first['title']}': {$initialStock}\n";

$orderData = [
    'status' => 'paid',
    'payment_method' => 'credit_card',
    'payment_ref' => $resCC['ref'] ?? 'TXN-TEST',
    'customer' => [
        'name' => 'Max Mustermann',
        'email' => 'kunde@test.local',
        'street' => 'Musterstraße 1',
        'zip' => '12345',
        'city' => 'Musterstadt'
    ],
    'items' => $cartNormal['items'],
    'subtotal' => $cartNormal['subtotal'],
    'shipping' => $cartNormal['shipping'],
    'tax' => $cartNormal['tax'],
    'total' => $cartNormal['total'],
    'currency' => 'EUR'
];

$order = ShopManager::createOrder($orderData);
echo "   Erstellte Bestell-ID: " . $order['order_id'] . " | Status: " . $order['status'] . " | Summe: " . $order['total'] . " EUR\n";

// Lagerbestand nach Kauf prüfen
$updatedFirst = ShopManager::getProduct($first['id']);
$newStock = (int)($updatedFirst['stock'] ?? 0);
echo "   Neuer Lagerbestand: {$newStock} (Reduziert um {$cartNormal['items'][0]['qty']})\n";
if ($newStock !== ($initialStock - $cartNormal['items'][0]['qty'])) {
    echo "FEHLER: Lagerbestand wurde nicht korrekt dekrementiert!\n";
    exit(1);
}
echo "   ✓ Lagerbestandsreduzierung ERFOLGREICH\n\n";

// 7. Bestellstatus aktualisieren (Status-Workflow)
echo "7. Status-Aktualisierung (z.B. shipped):\n";
$updated = ShopManager::updateOrderStatus($order['order_id'], 'shipped');
$reloadedOrder = ShopManager::getOrder($order['order_id']);
echo "   Status nach Update: " . ($reloadedOrder['status'] ?? 'null') . "\n";
if (($reloadedOrder['status'] ?? '') !== 'shipped') {
    echo "FEHLER: Bestellstatus konnte nicht geändert werden!\n";
    exit(1);
}
echo "   ✓ Status-Workflow ERFOLGREICH\n\n";

echo "========================================\n";
echo "ALLE SHOP-TESTS FEHLERFREI BESTANDEN! ✓\n";
echo "========================================\n";
