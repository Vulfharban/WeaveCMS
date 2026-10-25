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
                $p['unlimited'] = !empty($data['unlimited']);
                if (isset($data['image'])) $p['image'] = trim($data['image']);
                $p['active'] = !empty($data['active']);
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

        // Automatische Bestellbestätigungs-E-Mails an Kunde & Shop-Betreiber senden
        self::sendOrderConfirmationEmails($newOrder);

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
            case 'bank_transfer':
                return self::processBankTransfer($total, $details, $shopConfig);
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
     * 4. Vorkasse / Banküberweisung
     */
    public static function processBankTransfer(float $total, array $details, array $config): array {
        $btConfig = $config['payment_bank_transfer'] ?? [];
        if (empty($btConfig['enabled'])) {
            return ['success' => false, 'message' => 'Zahlung per Vorkasse / Banküberweisung ist derzeit nicht aktiviert.'];
        }

        $bankName = trim($btConfig['bank_name'] ?? '');
        $holder   = trim($btConfig['account_holder'] ?? ($config['payment_wero']['merchant_name'] ?? 'WeaveCMS Shop'));
        $iban     = trim($btConfig['iban'] ?? ($config['payment_wero']['iban'] ?? ''));
        $bic      = trim($btConfig['bic'] ?? ($config['payment_wero']['bic'] ?? ''));
        $note     = trim($btConfig['instruction'] ?? 'Bitte überweise den Gesamtbetrag unter Angabe deiner Bestellnummer auf unser Bankkonto.');
        $ref      = 'VORKASSE-' . strtoupper(bin2hex(random_bytes(5)));

        return [
            'success'        => true,
            'payment_method' => 'bank_transfer',
            'payment_ref'    => $ref,
            'amount'         => $total,
            'status'         => 'pending',
            'bank_details'   => [
                'account_holder' => $holder,
                'bank_name'      => $bankName,
                'iban'           => $iban,
                'bic'            => $bic,
                'instruction'    => $note
            ],
            'message'        => 'Bestellung erfolgreich aufgegeben! Bitte überweise den Rechnungsbetrag auf unser Bankkonto.'
        ];
    }

    /**
     * Versendet Bestellbestätigungs-E-Mails an den Kunden und den Shop-Betreiber.
     * @param array $order
     * @return array ['customer' => bool, 'admin' => bool]
     */
    public static function sendOrderConfirmationEmails(array $order): array {
        $result = ['customer' => false, 'admin' => false];

        $mailerFile = dirname(__DIR__, 2) . '/libs/php/Mailer.php';
        if (!file_exists($mailerFile)) {
            return $result;
        }
        require_once $mailerFile;

        // Globale CMS-Daten für SMTP & Kontaktdaten laden
        $dataFile = dirname(__DIR__, 2) . '/data.json';
        $data = file_exists($dataFile) ? (json_decode((string)@file_get_contents($dataFile), true) ?: []) : [];

        $smtpConfig = $data['settings']['smtp'] ?? null;
        $shopOwnerEmail = trim($data['texts']['contact_email'] ?? '');
        $shopOwnerName = trim($data['texts']['contact_name'] ?? 'WeaveCMS Webshop');
        $shopTitle = trim($data['texts']['hero_title'] ?? $shopOwnerName);
        $customerEmail = trim($order['customer']['email'] ?? '');
        $customerName = trim($order['customer']['name'] ?? 'Kunde');
        $orderId = $order['order_id'] ?? 'ORD';
        $orderDate = $order['created_at'] ?? date('d.m.Y H:i');
        $currency = $order['currency'] ?? 'EUR';
        $currencySymbol = ($currency === 'EUR') ? '€' : $currency;

        $itemsHtml = '';
        $itemsText = '';
        foreach ($order['items'] ?? [] as $item) {
            $qty = (int)($item['qty'] ?? 1);
            $title = htmlspecialchars($item['title'] ?? 'Artikel');
            $price = number_format((float)($item['price'] ?? 0), 2, ',', '.') . ' ' . $currencySymbol;
            $lineTotal = number_format((float)($item['total'] ?? 0), 2, ',', '.') . ' ' . $currencySymbol;

            $itemsHtml .= '<tr>'
                        . '<td style="padding:8px; border-bottom:1px solid #eee;">' . $qty . 'x ' . $title . '</td>'
                        . '<td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">' . $price . '</td>'
                        . '<td style="padding:8px; border-bottom:1px solid #eee; text-align:right; font-weight:600;">' . $lineTotal . '</td>'
                        . '</tr>';

            $itemsText .= "• {$qty}x {$item['title']} - Einzelpreis: {$price} | Gesamt: {$lineTotal}\n";
        }

        $subtotalFmt = number_format((float)($order['subtotal'] ?? 0), 2, ',', '.') . ' ' . $currencySymbol;
        $shippingFmt = number_format((float)($order['shipping'] ?? 0), 2, ',', '.') . ' ' . $currencySymbol;
        $totalFmt = number_format((float)($order['total'] ?? 0), 2, ',', '.') . ' ' . $currencySymbol;

        // Zahlungsart Text
        $payMethodName = match($order['payment_method'] ?? '') {
            'credit_card'   => 'Kreditkarte (autorisiert)',
            'paypal'        => 'PayPal',
            'wero'          => 'WERO (European Instant Payment)',
            'bank_transfer' => 'Vorkasse / Banküberweisung',
            default         => strtoupper($order['payment_method'] ?? 'Online-Zahlung')
        };

        // Banküberweisungs-Zusatztext für E-Mails
        $bankDetailsBlockHtml = '';
        $bankDetailsBlockText = '';
        if (($order['payment_method'] ?? '') === 'bank_transfer') {
            $shopPluginConf = PluginManager::getPluginConfig('shop', $data);
            $bt = $shopPluginConf['payment_bank_transfer'] ?? [];
            $holder = htmlspecialchars($bt['account_holder'] ?? ($shopPluginConf['payment_wero']['merchant_name'] ?? $shopOwnerName));
            $bank = htmlspecialchars($bt['bank_name'] ?? '');
            $iban = htmlspecialchars($bt['iban'] ?? ($shopPluginConf['payment_wero']['iban'] ?? ''));
            $bic  = htmlspecialchars($bt['bic'] ?? ($shopPluginConf['payment_wero']['bic'] ?? ''));

            $bankDetailsBlockHtml = '
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:16px; margin:20px 0;">
                <h4 style="margin:0 0 8px; color:#1e3a8a;">Bankverbindung für deine Überweisung:</h4>
                <div>Kontoinhaber: <strong>' . $holder . '</strong></div>
                ' . (!empty($bank) ? '<div>Bank: <strong>' . $bank . '</strong></div>' : '') . '
                <div>IBAN: <strong>' . $iban . '</strong></div>
                <div>BIC: <strong>' . $bic . '</strong></div>
                <div style="margin-top:6px; font-weight:600; color:#1e3a8a;">Verwendungszweck: ' . htmlspecialchars($orderId) . '</div>
            </div>';

            $bankDetailsBlockText = "\nBankverbindung für deine Überweisung:\n"
                                  . "Kontoinhaber: " . ($bt['account_holder'] ?? $shopOwnerName) . "\n"
                                  . (!empty($bt['bank_name']) ? "Bank: {$bt['bank_name']}\n" : '')
                                  . "IBAN: " . ($bt['iban'] ?? '') . "\n"
                                  . "BIC: " . ($bt['bic'] ?? '') . "\n"
                                  . "Verwendungszweck: {$orderId}\n";
        }

        // 1. E-Mail an den KUNDEN
        if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $subjectCustomer = "Bestellbestätigung: {$orderId} | {$shopTitle}";

            $htmlCustomer = '
            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif; max-width:600px; margin:0 auto; padding:24px; color:#2c3e50; line-height:1.5;">
                <div style="text-align:center; margin-bottom:24px; border-bottom:2px solid #2e6248; padding-bottom:16px;">
                    <h2 style="color:#2e6248; margin:0 0 6px;">Vielen Dank für deine Bestellung!</h2>
                    <div style="font-size:0.9rem; color:#666;">Bestellnummer: <strong>' . htmlspecialchars($orderId) . '</strong> am ' . htmlspecialchars($orderDate) . '</div>
                </div>

                <p>Hallo ' . htmlspecialchars($customerName) . ',</p>
                <p>wir haben deine Bestellung erhalten und bereiten sie schnellstmöglich für dich vor.</p>

                <div style="background:#f9f9f9; border-radius:8px; padding:16px; margin:20px 0;">
                    <h4 style="margin:0 0 10px; color:#2e6248; border-bottom:1px solid #ddd; padding-bottom:6px;">Lieferanschrift</h4>
                    <div>' . htmlspecialchars($order['customer']['name'] ?? '') . '</div>
                    <div>' . htmlspecialchars($order['customer']['street'] ?? '') . '</div>
                    <div>' . htmlspecialchars(($order['customer']['zip'] ?? '') . ' ' . ($order['customer']['city'] ?? '')) . '</div>
                    <div>' . htmlspecialchars($order['customer']['country'] ?? 'Deutschland') . '</div>
                </div>

                <table style="width:100%; border-collapse:collapse; margin:20px 0;">
                    <thead>
                        <tr style="background:#f1f5f9; text-align:left;">
                            <th style="padding:8px;">Artikel</th>
                            <th style="padding:8px; text-align:right;">Einzelpreis</th>
                            <th style="padding:8px; text-align:right;">Gesamt</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $itemsHtml . '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="padding:8px; text-align:right;">Zwischensumme:</td>
                            <td style="padding:8px; text-align:right;">' . $subtotalFmt . '</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:8px; text-align:right;">Versandkosten:</td>
                            <td style="padding:8px; text-align:right;">' . $shippingFmt . '</td>
                        </tr>
                        <tr style="font-size:1.15rem; font-weight:700; color:#2e6248;">
                            <td colspan="2" style="padding:10px; text-align:right; border-top:2px solid #2e6248;">Gesamtsumme:</td>
                            <td style="padding:10px; text-align:right; border-top:2px solid #2e6248;">' . $totalFmt . '</td>
                        </tr>
                    </tfoot>
                </table>

                <div style="background:#f0fdf4; border-left:4px solid #2e6248; padding:12px; margin:20px 0; font-size:0.9rem;">
                    <strong>Zahlung:</strong> ' . htmlspecialchars($payMethodName) . '<br>
                    <strong>Referenz:</strong> ' . htmlspecialchars($order['payment_ref'] ?? '-') . '
                </div>

                ' . $bankDetailsBlockHtml . '

                <p style="margin-top:24px; font-size:0.85rem; color:#666;">
                    Bei Fragen zu deiner Bestellung antworte einfach direkt auf diese E-Mail.<br><br>
                    Herzliche Grüße,<br>
                    <strong>' . htmlspecialchars($shopOwnerName) . '</strong>
                </p>
            </div>';

            $textCustomer = "Vielen Dank für deine Bestellung!\n\n"
                          . "Bestellnummer: {$orderId} am {$orderDate}\n"
                          . "Kunde: {$customerName}\n\n"
                          . "Bestellte Artikel:\n" . $itemsText . "\n"
                          . "Zwischensumme: {$subtotalFmt}\n"
                          . "Versandkosten: {$shippingFmt}\n"
                          . "Gesamtsumme: {$totalFmt}\n\n"
                          . "Zahlungsart: {$payMethodName} (Ref: " . ($order['payment_ref'] ?? '-') . ")\n"
                          . $bankDetailsBlockText . "\n"
                          . "Lieferanschrift:\n"
                          . ($order['customer']['name'] ?? '') . "\n"
                          . ($order['customer']['street'] ?? '') . "\n"
                          . ($order['customer']['zip'] ?? '') . " " . ($order['customer']['city'] ?? '') . "\n"
                          . ($order['customer']['country'] ?? '') . "\n\n"
                          . "Herzliche Grüße,\n{$shopOwnerName}";

            $replyToEmail = !empty($shopOwnerEmail) ? $shopOwnerEmail : '';
            $replyToName = !empty($shopOwnerName) ? $shopOwnerName : '';

            $resCustomer = SimpleMailer::send($customerEmail, $subjectCustomer, $htmlCustomer, $textCustomer, $replyToEmail, $replyToName, $smtpConfig);
            $result['customer'] = !empty($resCustomer['success']);
        }

        // 2. Benachrichtigungs-E-Mail an den SHOP-BETREIBER (Admin)
        if (!empty($shopOwnerEmail) && filter_var($shopOwnerEmail, FILTER_VALIDATE_EMAIL)) {
            $subjectAdmin = "🔔 Neue Shop-Bestellung: {$orderId} ({$totalFmt}) von {$customerName}";

            $htmlAdmin = '
            <div style="font-family:sans-serif; max-width:600px; padding:20px; color:#333;">
                <h3 style="color:#2e6248; margin-top:0;">🔔 Neue Bestellung im Webshop eingegangen!</h3>
                <p>Es wurde soeben eine neue Bestellung im Shop aufgegeben:</p>
                <ul>
                    <li><strong>Bestellnummer:</strong> ' . htmlspecialchars($orderId) . '</li>
                    <li><strong>Kunde:</strong> ' . htmlspecialchars($customerName) . ' (' . htmlspecialchars($customerEmail) . ')</li>
                    <li><strong>Gesamtbetrag:</strong> ' . $totalFmt . '</li>
                    <li><strong>Zahlungsart:</strong> ' . htmlspecialchars($payMethodName) . ' (' . htmlspecialchars($order['payment_ref'] ?? '-') . ')</li>
                </ul>
                <div style="margin-top:16px;">
                    <strong>Bestellte Positionen:</strong>
                    <table style="width:100%; border-collapse:collapse; margin-top:8px;">
                        ' . $itemsHtml . '
                    </table>
                </div>
                <div style="margin-top:16px;">
                    <a href="' . htmlspecialchars(SecurityManager::getBaseUrl() . '/admin/?tab=tab-plugins&plugin_settings=shop&shop_subtab=orders#tab-plugins') . '" style="display:inline-block; padding:8px 16px; background:#2e6248; color:#fff; text-decoration:none; border-radius:6px; font-weight:600;">
                        Bestellung im Adminbereich aufrufen &rarr;
                    </a>
                </div>
            </div>';

            $textAdmin = "Neue Shop-Bestellung eingegangen!\n\n"
                       . "Bestellnummer: {$orderId}\n"
                       . "Kunde: {$customerName} ({$customerEmail})\n"
                       . "Gesamtbetrag: {$totalFmt}\n"
                       . "Zahlungsart: {$payMethodName}\n\n"
                       . "Artikel:\n" . $itemsText;

            $resAdmin = SimpleMailer::send($shopOwnerEmail, $subjectAdmin, $htmlAdmin, $textAdmin, $customerEmail, $customerName, $smtpConfig);
            $result['admin'] = !empty($resAdmin['success']);
        }

        return $result;
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
