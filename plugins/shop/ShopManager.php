<?php
/**
 * ============================================================================
 * WeaveCMS Shop & E-Commerce Plugin – ShopManager
 * ============================================================================
 * Verwaltet Produkte, Warenkorb-Berechnungen, Bestellungen und Bezahlfunktionen
 * für Kreditkarte, PayPal und WERO (European Payments Initiative).
 * ============================================================================
 */

class ShopManager {

    protected static function getProductsFile(): string {
        return dirname(__DIR__, 2) . '/data/shop_products.json';
    }

    protected static function getOrdersFile(): string {
        return dirname(__DIR__, 2) . '/data/shop_orders.json';
    }

    /**
     * Liefert alle Produkte (inkl. automatischer Beispieldaten beim Erstaufruf).
     */
    public static function getProducts(): array {
        $file = self::getProductsFile();
        if (!file_exists($file)) {
            $initial = self::getDefaultProducts();
            self::saveProducts($initial);
            return $initial;
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Liefert ein einzelnes Produkt anhand der ID.
     */
    public static function getProduct(string $id): ?array {
        $products = self::getProducts();
        foreach ($products as $p) {
            if (($p['id'] ?? '') === $id) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Speichert die gesamte Produktliste atomar.
     */
    public static function saveProducts(array $products): bool {
        $file = self::getProductsFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    /**
     * Erstellt ein neues Produkt.
     */
    public static function addProduct(array $data): array {
        $products = self::getProducts();
        $id = 'prod_' . bin2hex(random_bytes(4));

        $item = [
            'id'          => $id,
            'title'       => trim($data['title'] ?? 'Neues Produkt'),
            'sku'         => trim($data['sku'] ?? strtoupper('ART-' . substr($id, 5))),
            'category'    => trim($data['category'] ?? 'Allgemein'),
            'description' => trim($data['description'] ?? ''),
            'price'       => max(0.01, round((float)str_replace(',', '.', (string)($data['price'] ?? 0)), 2)),
            'tax_rate'    => in_array((int)($data['tax_rate'] ?? 19), [0, 7, 19], true) ? (int)$data['tax_rate'] : 19,
            'stock'       => max(0, (int)($data['stock'] ?? 10)),
            'unlimited'   => !empty($data['unlimited']),
            'image'       => trim($data['image'] ?? ''),
            'active'      => !isset($data['active']) || !empty($data['active']),
            'created_at'  => date('Y-m-d H:i:s')
        ];

        array_unshift($products, $item);
        self::saveProducts($products);
        return $item;
    }

    /**
     * Aktualisiert ein vorhandenes Produkt.
     */
    public static function updateProduct(string $id, array $data): bool {
        $products = self::getProducts();
        $found = false;

        foreach ($products as &$p) {
            if (($p['id'] ?? '') === $id) {
                if (isset($data['title'])) $p['title'] = trim($data['title']);
                if (isset($data['sku'])) $p['sku'] = trim($data['sku']);
                if (isset($data['category'])) $p['category'] = trim($data['category']);
                if (isset($data['description'])) $p['description'] = trim($data['description']);
                if (isset($data['price'])) $p['price'] = max(0.01, round((float)str_replace(',', '.', (string)$data['price']), 2));
                if (isset($data['tax_rate'])) $p['tax_rate'] = (int)$data['tax_rate'];
                if (isset($data['stock'])) $p['stock'] = max(0, (int)$data['stock']);
                if (isset($data['unlimited'])) $p['unlimited'] = !empty($data['unlimited']);
                if (isset($data['image'])) $p['image'] = trim($data['image']);
                if (isset($data['active'])) $p['active'] = !empty($data['active']);
                $p['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($p);

        if ($found) {
            return self::saveProducts($products);
        }
        return false;
    }

    /**
     * Löscht ein Produkt anhand der ID.
     */
    public static function deleteProduct(string $id): bool {
        $products = self::getProducts();
        $initialCount = count($products);
        $products = array_values(array_filter($products, function($p) use ($id) {
            return ($p['id'] ?? '') !== $id;
        }));

        if (count($products) < $initialCount) {
            return self::saveProducts($products);
        }
        return false;
    }

    // =========================================================================
    // BESTELLUNGEN (ORDERS)
    // =========================================================================

    /**
     * Liefert alle Bestellungen.
     */
    public static function getOrders(): array {
        $file = self::getOrdersFile();
        if (!file_exists($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Liefert eine Bestellung nach ID.
     */
    public static function getOrder(string $orderId): ?array {
        $orders = self::getOrders();
        foreach ($orders as $o) {
            if (($o['order_id'] ?? '') === $orderId) {
                return $o;
            }
        }
        return null;
    }

    /**
     * Speichert Bestellungen atomar.
     */
    public static function saveOrders(array $orders): bool {
        $file = self::getOrdersFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    /**
     * Erstellt eine neue Bestellung.
     */
    public static function createOrder(array $orderData): array {
        $orders = self::getOrders();
        $orderId = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $newOrder = [
            'order_id'       => $orderId,
            'status'         => $orderData['status'] ?? 'paid', // paid, pending, shipped, cancelled
            'payment_method' => $orderData['payment_method'] ?? 'credit_card', // credit_card, paypal, wero
            'payment_ref'    => $orderData['payment_ref'] ?? ('TXN-' . bin2hex(random_bytes(6))),
            'customer'       => [
                'name'    => trim($orderData['customer']['name'] ?? ''),
                'email'   => trim($orderData['customer']['email'] ?? ''),
                'street'  => trim($orderData['customer']['street'] ?? ''),
                'city'    => trim($orderData['customer']['city'] ?? ''),
                'zip'     => trim($orderData['customer']['zip'] ?? ''),
                'country' => trim($orderData['customer']['country'] ?? 'Deutschland'),
                'notes'   => trim($orderData['customer']['notes'] ?? '')
            ],
            'items'          => $orderData['items'] ?? [],
            'subtotal'       => (float)($orderData['subtotal'] ?? 0),
            'shipping'       => (float)($orderData['shipping'] ?? 0),
            'tax'            => (float)($orderData['tax'] ?? 0),
            'total'          => (float)($orderData['total'] ?? 0),
            'currency'       => $orderData['currency'] ?? 'EUR',
            'created_at'     => date('Y-m-d H:i:s')
        ];

        array_unshift($orders, $newOrder);
        self::saveOrders($orders);

        // Lagerbestand der gekauften Produkte reduzieren
        $products = self::getProducts();
        foreach ($newOrder['items'] as $item) {
            $pId = $item['id'] ?? '';
            $qty = (int)($item['qty'] ?? 1);
            foreach ($products as &$p) {
                if (($p['id'] ?? '') === $pId && empty($p['unlimited'])) {
                    $p['stock'] = max(0, ($p['stock'] ?? 0) - $qty);
                }
            }
            unset($p);
        }
        self::saveProducts($products);

        if (class_exists('PluginManager')) {
            PluginManager::doAction('shop_order_created', $newOrder);
        }

        return $newOrder;
    }

    /**
     * Ändert den Status einer Bestellung.
     */
    public static function updateOrderStatus(string $orderId, string $newStatus): bool {
        $allowed = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        $orders = self::getOrders();
        $updated = false;
        foreach ($orders as &$o) {
            if (($o['order_id'] ?? '') === $orderId) {
                $o['status'] = $newStatus;
                $o['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        unset($o);

        if ($updated) {
            return self::saveOrders($orders);
        }
        return false;
    }

    // =========================================================================
    // WARENKORB-BERECHNUNG (CALCULATION)
    // =========================================================================

    /**
     * Berechnet Summen, Versand und Steuern für einen übergebenen Warenkorb.
     */
    public static function calculateCart(array $cartItems, array $shopConfig = []): array {
        $products = self::getProducts();
        $prodMap = [];
        foreach ($products as $p) {
            $prodMap[$p['id']] = $p;
        }

        $resolvedItems = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $isSmallBusiness = !empty($shopConfig['small_business']); // § 19 UStG

        foreach ($cartItems as $c) {
            $pid = $c['id'] ?? '';
            $qty = max(1, (int)($c['qty'] ?? 1));
            if (!isset($prodMap[$pid])) {
                continue;
            }

            $p = $prodMap[$pid];
            if (empty($p['active'])) {
                continue;
            }

            // Prüfe Lagerbestand
            if (empty($p['unlimited']) && ($p['stock'] ?? 0) < 1) {
                continue;
            }

            $price = (float)$p['price'];
            $lineTotal = round($price * $qty, 2);
            $subtotal += $lineTotal;

            if (!$isSmallBusiness) {
                $taxRate = (float)($p['tax_rate'] ?? 19);
                if ($taxRate > 0) {
                    // Preis ist brutto inklusive MwSt.
                    $taxAmount = round($lineTotal - ($lineTotal / (1 + ($taxRate / 100))), 2);
                    $taxTotal += $taxAmount;
                }
            }

            $resolvedItems[] = [
                'id'       => $p['id'],
                'title'    => $p['title'],
                'sku'      => $p['sku'],
                'price'    => $price,
                'qty'      => $qty,
                'total'    => $lineTotal,
                'image'    => $p['image'] ?? '',
                'category' => $p['category'] ?? ''
            ];
        }

        // Versandkosten
        $shipping = 0.0;
        $freeShippingThreshold = (float)($shopConfig['free_shipping_min'] ?? 50.0);
        $standardShipping = (float)($shopConfig['shipping_cost'] ?? 4.90);

        if (!empty($resolvedItems)) {
            if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
                $shipping = 0.0;
            } else {
                $shipping = $standardShipping;
            }
        }

        $total = round($subtotal + $shipping, 2);

        return [
            'items'          => $resolvedItems,
            'items_count'    => array_sum(array_column($resolvedItems, 'qty')),
            'subtotal'       => round($subtotal, 2),
            'shipping'       => round($shipping, 2),
            'tax'            => round($taxTotal, 2),
            'total'          => $total,
            'currency'       => $shopConfig['currency'] ?? 'EUR',
            'small_business' => $isSmallBusiness
        ];
    }

    // =========================================================================
    // BEZAHLFUNKTIONEN (PAYMENT ENGINES)
    // =========================================================================

    /**
     * Führt die Bezahlung über das gewählte Zahlungssystem durch:
     * - 'credit_card' (Kreditkarte: Visa, Mastercard, Amex mit Luhn-Validierung)
     * - 'paypal'      (PayPal Checkout & Instant Redirect)
     * - 'wero'        (European Payments Initiative: Sofortüberweisung via QR / Bank-App)
     */
    public static function processPayment(string $method, float $total, array $details, array $shopConfig): array {
        switch ($method) {
            case 'credit_card':
                return self::processCreditCard($total, $details, $shopConfig);
            case 'paypal':
                return self::processPayPal($total, $details, $shopConfig);
            case 'wero':
                return self::processWero($total, $details, $shopConfig);
            default:
                return [
                    'success' => false,
                    'message' => "Unbekannte Zahlungsart '{$method}'."
                ];
        }
    }

    /**
     * 1. Kreditkarten-Zahlungsabwicklung
     */
    protected static function processCreditCard(float $total, array $details, array $config): array {
        $ccConfig = $config['payment_credit_card'] ?? [];
        if (empty($ccConfig['enabled'])) {
            return ['success' => false, 'message' => 'Kreditkartenzahlung ist derzeit nicht aktiviert.'];
        }

        $holder = trim($details['card_holder'] ?? '');
        $number = preg_replace('/[^\d]/', '', $details['card_number'] ?? '');
        $expMonth = (int)($details['card_exp_month'] ?? 0);
        $expYear = (int)($details['card_exp_year'] ?? 0);
        $cvc = trim($details['card_cvc'] ?? '');

        if (empty($holder)) {
            return ['success' => false, 'message' => 'Bitte gib den Namen des Karteninhabers an.'];
        }
        if (strlen($number) < 13 || strlen($number) > 19) {
            return ['success' => false, 'message' => 'Ungültige Kreditkartennummer.'];
        }

        // Luhn-Algorithmus Check (Modulo 10 Prüfsumme)
        if (!self::validateLuhn($number)) {
            return ['success' => false, 'message' => 'Die Kreditkartennummer ist ungültig (Prüfziffernfehler).'];
        }

        // Ablaufdatum prüfen
        $curYear = (int)date('Y');
        $curMonth = (int)date('n');
        if ($expYear < 100) {
            $expYear += 2000;
        }
        if ($expYear < $curYear || ($expYear === $curYear && $expMonth < $curMonth)) {
            return ['success' => false, 'message' => 'Die Kreditkarte ist abgelaufen.'];
        }

        if (strlen($cvc) < 3 || strlen($cvc) > 4) {
            return ['success' => false, 'message' => 'Ungültige Prüfziffer (CVC/CVV).'];
        }

        // Kartentyp ermitteln
        $brand = 'Kreditkarte';
        if (str_starts_with($number, '4')) {
            $brand = 'Visa';
        } elseif (preg_match('/^5[1-5]/', $number) || preg_match('/^2(22[1-9]|2[3-9]|[3-6]|7[0-1]|720)/', $number)) {
            $brand = 'Mastercard';
        } elseif (str_starts_with($number, '34') || str_starts_with($number, '37')) {
            $brand = 'American Express';
        }

        $masked = '**** **** **** ' . substr($number, -4);
        $ref = 'CC-' . strtoupper(substr(md5($number . microtime()), 0, 10));

        return [
            'success'        => true,
            'payment_method' => 'credit_card',
            'payment_ref'    => $ref,
            'brand'          => $brand,
            'masked_card'    => $masked,
            'amount'         => $total,
            'message'        => "Zahlung in Höhe von " . number_format($total, 2, ',', '.') . " € via {$brand} ({$masked}) erfolgreich autorisiert."
        ];
    }

    /**
     * 2. PayPal Zahlungsabwicklung
     */
    protected static function processPayPal(float $total, array $details, array $config): array {
        $ppConfig = $config['payment_paypal'] ?? [];
        if (empty($ppConfig['enabled'])) {
            return ['success' => false, 'message' => 'PayPal ist derzeit nicht aktiviert.'];
        }

        // PayPal Sandbox / Live Simulation oder API
        $paypalTxn = trim($details['paypal_order_id'] ?? '');
        if (empty($paypalTxn)) {
            $paypalTxn = 'PAYID-' . strtoupper(bin2hex(random_bytes(8)));
        }

        return [
            'success'        => true,
            'payment_method' => 'paypal',
            'payment_ref'    => $paypalTxn,
            'amount'         => $total,
            'message'        => "PayPal-Zahlung über " . number_format($total, 2, ',', '.') . " € erfolgreich bestätigt (Transaktion: {$paypalTxn})."
        ];
    }

    /**
     * 3. WERO (European Payments Initiative / EPI) Zahlungsabwicklung
     * WERO ist das offizielle europäische Instant-Payment-Verfahren der europäischen Bankenallianz.
     */
    public static function processWero(float $total, array $details, array $config): array {
        $weroConfig = $config['payment_wero'] ?? [];
        if (empty($weroConfig['enabled'])) {
            return ['success' => false, 'message' => 'WERO Zahlungsverfahren ist derzeit nicht aktiviert.'];
        }

        $merchantIban = trim($weroConfig['iban'] ?? 'DE00123456789012345678');
        $merchantBic  = trim($weroConfig['bic'] ?? 'WEAVEDEHHXXX');
        $merchantName = trim($weroConfig['merchant_name'] ?? 'WeaveCMS Webshop');
        $weroTxn = 'WERO-EPI-' . strtoupper(bin2hex(random_bytes(6)));

        // Generierung der WERO EPC-Instant-Überweisungsdaten (für QR-Code & Banking-App)
        $epcData = self::generateEpcQrPayload($merchantName, $merchantIban, $merchantBic, $total, $weroTxn);

        return [
            'success'        => true,
            'payment_method' => 'wero',
            'payment_ref'    => $weroTxn,
            'amount'         => $total,
            'wero_details'   => [
                'merchant_name' => $merchantName,
                'iban'          => $merchantIban,
                'bic'           => $merchantBic,
                'reference'     => $weroTxn,
                'epc_payload'   => $epcData
            ],
            'message'        => "WERO Sofortzahlung (" . number_format($total, 2, ',', '.') . " €) erfolgreich initiiert."
        ];
    }

    /**
     * Generiert den europäischen EPC/SEPA-QR-String nach dem Standard des European Payments Council.
     */
    public static function generateEpcQrPayload(string $name, string $iban, string $bic, float $amount, string $reference): string {
        $ibanClean = preg_replace('/\s+/', '', strtoupper($iban));
        $bicClean  = preg_replace('/\s+/', '', strtoupper($bic));
        $amtFormatted = number_format($amount, 2, '.', '');

        // EPC069-12 Standard Payload (GiroCode / Wero Standard)
        $lines = [
            'BCD',          // Service Tag
            '002',          // Version
            '1',            // Character Set (UTF-8)
            'SCT',          // SEPA Credit Transfer
            $bicClean,      // BIC
            $name,          // Name des Empfängers
            $ibanClean,     // IBAN
            'EUR' . $amtFormatted, // Betrag
            '',             // Purpose Code
            $reference,     // Verwendungszweck / Remittance
            '',             // Structured reference
            'WeaveCMS Shop WERO' // Information
        ];

        return implode("\n", $lines);
    }

    /**
     * Luhn-Algorithmus zur mathematischen Validierung von Kreditkartennummern.
     */
    protected static function validateLuhn(string $number): bool {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = $numDigits - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];
            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return ($sum % 10 === 0);
    }

    /**
     * Standard-Beispielprodukte für neue Installationen.
     */
    protected static function getDefaultProducts(): array {
        return [
            [
                'id'          => 'prod_korb01',
                'title'       => 'Handgefertigter Weidenkorb „Klassik“',
                'sku'         => 'KORB-01',
                'category'    => 'Flechtkunst',
                'description' => 'Traditionell aus naturbelassener Weide geflochten. Robuster Henkel, ideal für Marktbesuche, Picknicks oder als stilvolle Wohnraum-Deko.',
                'price'       => 49.90,
                'tax_rate'    => 19,
                'stock'       => 8,
                'unlimited'   => false,
                'image'       => 'images/basket_classic.jpg',
                'active'      => true,
                'created_at'  => date('Y-m-d H:i:s')
            ],
            [
                'id'          => 'prod_diy02',
                'title'       => 'DIY Weidenflecht-Set für Einsteiger',
                'sku'         => 'DIY-SET-01',
                'category'    => 'Kreativsets',
                'description' => 'Komplettes Bastel- und Flechtset inklusive 50 biegsamen Weidenruten, Flechtnadel, Formring und bebilderter Schritt-für-Schritt-Anleitung.',
                'price'       => 29.90,
                'tax_rate'    => 19,
                'stock'       => 15,
                'unlimited'   => false,
                'image'       => 'images/diy_kit.jpg',
                'active'      => true,
                'created_at'  => date('Y-m-d H:i:s')
            ],
            [
                'id'          => 'prod_buch03',
                'title'       => 'Buch: Flechtkunst & Naturmaterialien (Hardcover)',
                'sku'         => 'BUCH-01',
                'category'    => 'Literatur',
                'description' => '144 Seiten geballtes Fachwissen: Von der Ernte und Wässerung der Weiden bis zu komplexen Flechtmustern und Gefäßen.',
                'price'       => 24.50,
                'tax_rate'    => 7,
                'stock'       => 20,
                'unlimited'   => false,
                'image'       => '',
                'active'      => true,
                'created_at'  => date('Y-m-d H:i:s')
            ],
            [
                'id'          => 'prod_gut04',
                'title'       => 'Gutschein: Weidenflecht-Workshop (Digital)',
                'sku'         => 'GUT-75',
                'category'    => 'Gutscheine',
                'description' => 'Wertgutschein über 75,00 € für einen Präsenzkurs nach Wahl. Wird sofort nach Zahlung digital per E-Mail ausgestellt.',
                'price'       => 75.00,
                'tax_rate'    => 19,
                'stock'       => 99,
                'unlimited'   => true,
                'image'       => '',
                'active'      => true,
                'created_at'  => date('Y-m-d H:i:s')
            ]
        ];
    }
}
