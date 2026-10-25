<?php
/**
 * ============================================================================
 * WeaveCMS Shop & E-Commerce API – Endpoint
 * ============================================================================
 * Behandelt AJAX-Anfragen für den Warenkorb, den Kassenbereich (Checkout),
 * Zahlungsautorisierungen (Kreditkarte, PayPal, WERO) und Produktabfragen.
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');

// WeaveCMS Umgebung laden
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/plugins/shop/ShopManager.php';

$dataFile = dirname(__DIR__) . '/data.json';
$data = load_data($dataFile);
$shopConfig = PluginManager::getPluginConfig('shop', $data, [
    'currency'           => 'EUR',
    'shipping_cost'      => 4.90,
    'free_shipping_min'  => 50.0,
    'small_business'     => false,
    'payment_credit_card'=> ['enabled' => true, 'sandbox' => true],
    'payment_paypal'     => ['enabled' => true, 'sandbox' => true],
    'payment_wero'       => [
        'enabled'        => true,
        'sandbox'        => true,
        'merchant_name'  => 'WeaveCMS Shop',
        'iban'           => 'DE89370400440532013000',
        'bic'            => 'BYLADEM1001'
    ],
    'payment_bank_transfer' => [
        'enabled'        => true,
        'bank_name'      => 'Hausbank',
        'account_holder' => 'WeaveCMS Shop',
        'iban'           => 'DE89370400440532013000',
        'bic'            => 'BYLADEM1001',
        'instruction'    => 'Bitte überweise den Gesamtbetrag unter Angabe deiner Bestellnummer.'
    ]
]);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
$jsonInput = json_decode($rawBody, true) ?: [];

switch ($action) {
    // -------------------------------------------------------------------------
    // 1. Warenkorb abfragen / berechnen
    // -------------------------------------------------------------------------
    case 'calculate_cart':
        $items = $jsonInput['items'] ?? $_POST['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }
        $calc = ShopManager::calculateCart($items, $shopConfig);
        echo json_encode(['success' => true, 'cart' => $calc], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // 2. Produkte abfragen
    // -------------------------------------------------------------------------
    case 'get_products':
        $products = ShopManager::getProducts();
        $activeProducts = array_values(array_filter($products, function($p) {
            return !empty($p['active']);
        }));
        echo json_encode(['success' => true, 'products' => $activeProducts], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // 3. Checkout & Bezahlung durchführen (Kreditkarte, PayPal, WERO)
    // -------------------------------------------------------------------------
    case 'checkout':
        $input = !empty($jsonInput) ? $jsonInput : $_POST;

        $items = $input['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Der Warenkorb ist leer.']);
            exit;
        }

        // Kundendaten validieren
        $customer = [
            'name'    => trim($input['name'] ?? ''),
            'email'   => trim($input['email'] ?? ''),
            'street'  => trim($input['street'] ?? ''),
            'city'    => trim($input['city'] ?? ''),
            'zip'     => trim($input['zip'] ?? ''),
            'country' => trim($input['country'] ?? 'Deutschland'),
            'notes'   => trim($input['notes'] ?? '')
        ];

        if (empty($customer['name']) || empty($customer['email']) || empty($customer['street']) || empty($customer['zip']) || empty($customer['city'])) {
            echo json_encode(['success' => false, 'message' => 'Bitte fülle alle Pflichtfelder der Liefer- und Rechnungsadresse aus.']);
            exit;
        }

        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Bitte gib eine gültige E-Mail-Adresse an.']);
            exit;
        }

        // Warenkorb erneut serverseitig berechnen (Manipulationsschutz)
        $cartCalc = ShopManager::calculateCart($items, $shopConfig);
        if (empty($cartCalc['items'])) {
            echo json_encode(['success' => false, 'message' => 'Keine verfügbaren Artikel im Warenkorb.']);
            exit;
        }

        $paymentMethod = trim($input['payment_method'] ?? 'credit_card');
        $paymentDetails = $input['payment_details'] ?? [];

        // Bezahlung über ShopManager abwickeln
        $payResult = ShopManager::processPayment($paymentMethod, $cartCalc['total'], $paymentDetails, $shopConfig);

        if (empty($payResult['success'])) {
            echo json_encode([
                'success' => false,
                'message' => $payResult['message'] ?? 'Zahlungsautorisierung fehlgeschlagen.'
            ]);
            exit;
        }

        // Bestellung anlegen
        $orderRecord = [
            'status'         => $payResult['status'] ?? ($paymentMethod === 'bank_transfer' ? 'pending' : 'paid'),
            'payment_method' => $paymentMethod,
            'payment_ref'    => $payResult['payment_ref'] ?? ('REF-' . time()),
            'customer'       => $customer,
            'items'          => $cartCalc['items'],
            'subtotal'       => $cartCalc['subtotal'],
            'shipping'       => $cartCalc['shipping'],
            'tax'            => $cartCalc['tax'],
            'total'          => $cartCalc['total'],
            'currency'       => $cartCalc['currency']
        ];

        $order = ShopManager::createOrder($orderRecord);

        // Sicherheits-Log schreiben
        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent(
                'SHOP_ORDER_CREATED',
                "Neue Bestellung {$order['order_id']} über {$order['total']} {$order['currency']} ({$paymentMethod})",
                'INFO'
            );
        }

        $successMsg = ($paymentMethod === 'bank_transfer')
            ? 'Vielen Dank! Deine Bestellung ist eingegangen. Bitte überweise den Betrag unter Angabe deiner Bestellnummer.'
            : 'Vielen Dank! Deine Bestellung wurde erfolgreich aufgegeben und bezahlt.';

        echo json_encode([
            'success'      => true,
            'order'        => $order,
            'payment_info' => $payResult,
            'message'      => $successMsg
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // 4. Bestellstatus abfragen
    // -------------------------------------------------------------------------
    case 'order_status':
        $orderId = trim($_GET['order_id'] ?? $jsonInput['order_id'] ?? '');
        $order = ShopManager::getOrder($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Bestellung nicht gefunden.']);
            exit;
        }
        echo json_encode(['success' => true, 'order' => $order], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Ungültige Shop-Aktion.']);
        exit;
}
