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

    // -------------------------------------------------------------------------
    // 5. REST API: Artikel- & Produktverwaltung (Token-authentifiziert)
    // -------------------------------------------------------------------------
    case 'api_list_products':
    case 'list_products':
        requireShopApiAuth($shopConfig);
        $products = ShopManager::getProducts();
        $category = trim($_GET['category'] ?? '');
        if (!empty($category)) {
            $products = array_values(array_filter($products, fn($p) => ($p['category'] ?? '') === $category));
        }
        echo json_encode([
            'success'  => true,
            'count'    => count($products),
            'products' => $products
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'api_get_product':
    case 'get_product':
        requireShopApiAuth($shopConfig);
        $id = trim($_GET['id'] ?? $jsonInput['id'] ?? '');
        $sku = trim($_GET['sku'] ?? $jsonInput['sku'] ?? '');
        $product = null;
        if (!empty($id)) {
            $product = ShopManager::getProduct($id);
        } elseif (!empty($sku)) {
            foreach (ShopManager::getProducts() as $p) {
                if (strcasecmp($p['sku'] ?? '', $sku) === 0) {
                    $product = $p;
                    break;
                }
            }
        }
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produkt nicht gefunden.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => true, 'product' => $product], JSON_UNESCAPED_UNICODE);
        exit;

    case 'api_save_product':
    case 'save_product':
        requireShopApiAuth($shopConfig);
        $input = !empty($jsonInput) ? $jsonInput : array_merge($_GET, $_POST);
        $id = trim($input['id'] ?? $input['product_id'] ?? '');
        try {
            if (!empty($id) && ShopManager::getProduct($id)) {
                $res = ShopManager::updateProduct($id, $input);
                echo json_encode(['success' => true, 'message' => 'Produkt aktualisiert.', 'product' => $res], JSON_UNESCAPED_UNICODE);
            } else {
                $res = ShopManager::addProduct($input);
                echo json_encode(['success' => true, 'message' => 'Produkt angelegt.', 'product' => $res], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'api_delete_product':
    case 'delete_product':
        requireShopApiAuth($shopConfig);
        $input = !empty($jsonInput) ? $jsonInput : array_merge($_GET, $_POST);
        $id = trim($_GET['id'] ?? $input['id'] ?? $input['product_id'] ?? '');
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Produkt-ID fehlt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ok = ShopManager::deleteProduct($id);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Produkt gelöscht.' : 'Produkt nicht gefunden.'], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // 6. REST API: Bestellungsverwaltung (WaWi / Buchhaltungs-Sync)
    // -------------------------------------------------------------------------
    case 'api_list_orders':
    case 'list_orders':
        requireShopApiAuth($shopConfig);
        $orders = ShopManager::getOrders();
        $statusFilter = trim($_GET['status'] ?? '');
        if (!empty($statusFilter)) {
            $orders = array_values(array_filter($orders, fn($o) => ($o['status'] ?? '') === $statusFilter));
        }
        echo json_encode([
            'success' => true,
            'count'   => count($orders),
            'orders'  => $orders
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'api_update_order_status':
    case 'update_order_status':
        requireShopApiAuth($shopConfig);
        $input = !empty($jsonInput) ? $jsonInput : array_merge($_GET, $_POST);
        $orderId = trim($input['order_id'] ?? $input['id'] ?? '');
        $newStatus = trim($input['status'] ?? '');
        if (empty($orderId) || empty($newStatus)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'order_id und status sind erforderlich.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ok = ShopManager::updateOrderStatus($orderId, $newStatus);
        echo json_encode(['success' => $ok, 'message' => $ok ? "Status auf '{$newStatus}' aktualisiert." : 'Bestellung nicht gefunden.'], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // 7. REST API: JSON Im- und Export
    // -------------------------------------------------------------------------
    case 'api_export_products':
    case 'export_products':
        requireShopApiAuth($shopConfig);
        echo ShopManager::exportProductsJson();
        exit;

    case 'api_export_orders':
    case 'export_orders':
        requireShopApiAuth($shopConfig);
        echo ShopManager::exportOrdersJson();
        exit;

    case 'api_import_products':
    case 'import_products':
        requireShopApiAuth($shopConfig);
        $raw = !empty($rawBody) ? $rawBody : ($_POST['json_data'] ?? '');
        $mode = in_array($_POST['mode'] ?? ($jsonInput['mode'] ?? 'merge'), ['merge', 'replace'], true) ? ($_POST['mode'] ?? ($jsonInput['mode'] ?? 'merge')) : 'merge';
        $res = ShopManager::importProductsJson($raw, $mode);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    default:
        // Filter-Hook für Addon-Plugins, um eigene API-Aktionen bereitzustellen
        if (class_exists('PluginManager')) {
            $handled = PluginManager::applyFilters('shop_api_action', false, $action, $jsonInput, $shopConfig);
            if ($handled) {
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Ungültige Shop-Aktion.']);
        exit;
}

/**
 * Validiert den API-Zugriff für geschützte Shop-Endpunkte.
 */
function requireShopApiAuth(array $shopConfig): void {
    $token = '';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if (empty($authHeader) && function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        foreach ($allHeaders as $hKey => $hVal) {
            if (strcasecmp($hKey, 'Authorization') === 0) {
                $authHeader = $hVal;
            } elseif (strcasecmp($hKey, 'X-API-KEY') === 0 && empty($_SERVER['HTTP_X_API_KEY'])) {
                $_SERVER['HTTP_X_API_KEY'] = $hVal;
            }
        }
    }
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    } elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $token = trim($_SERVER['HTTP_X_API_KEY']);
    } elseif (!empty($_REQUEST['api_key'])) {
        $token = trim((string)$_REQUEST['api_key']);
    }

    $isAdminSession = !empty($_SESSION['admin_logged_in']);
    $isValidApiKey = !empty($shopConfig['api_enabled']) && ShopManager::verifyApiKey($token, $shopConfig);

    if (!$isAdminSession && !$isValidApiKey) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'Unauthorized',
            'message' => 'Ungültiger oder fehlender API-Schlüssel für den Webshop.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

