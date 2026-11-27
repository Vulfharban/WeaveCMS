<?php
/**
 * ============================================================================
 * WeaveCMS Shop – InvoiceManager (Phase 37)
 * ============================================================================
 * Verwaltet GoBD-konforme Nummernkreise, PDF/ZUGFeRD/XRechnung-Generierung,
 * Storno-Belege, unveränderliches Online-Archiv (SHA-256), lokales ZIP-Archiv
 * inkl. CSV/DATEV-Journal, E-Mail-Versand (BCC) & WebDAV/Nextcloud-Sync.
 * Nutzt StorageManager (MySQL mit Prefix / JSON-Fallback).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class InvoiceManager {

    protected static ?string $testStorageDir = null;
    protected static ?array $storeCache = null;

    public static function clearCache(): void {
        self::$storeCache = null;
    }

    public static function setTestStorageDir(?string $dir): void {
        self::$testStorageDir = $dir !== null ? rtrim($dir, '/\\') : null;
        self::clearCache();
    }

    protected static function getDataDir(): string {
        return self::$testStorageDir ?? (dirname(__DIR__, 2) . '/data');
    }

    public static function getArchiveBaseDir(): string {
        return self::getDataDir() . DIRECTORY_SEPARATOR . 'invoices';
    }

    public static function getDefaultConfig(bool $generateKey = false): array {
        return [
            'enabled'                      => true,
            'checkout_billing_fields'      => true,
            'allow_customer_format_choice' => true,
            'default_format'               => 'zugferd', // pdf | zugferd | xrechnung
            'auto_generate_on_order'       => false,
            'auto_email_customer'          => false,
            'number_pattern'               => 'RE-{YYYY}-{NNNN}',
            'credit_pattern'               => 'ST-{YYYY}-{NNNN}',
            'seller_name'                  => 'WeaveCMS Musterbetrieb',
            'seller_contact_name'          => '',
            'seller_street'                => 'Musterstraße 1',
            'seller_zip'                   => '12345',
            'seller_city'                  => 'Musterstadt',
            'seller_country_code'          => 'DE',
            'seller_tax_number'            => '123/456/78901',
            'seller_vat_id'                => '',
            'seller_register'              => '',
            'seller_email'                 => 'buchhaltung@example.com',
            'seller_bank_name'             => '',
            'seller_iban'                  => '',
            'seller_bic'                   => '',
            'payment_terms'                => 'Zahlbar sofort nach Rechnungserhalt ohne Abzug.',
            'accounting_bcc_email'         => '',
            'webdav_enabled'               => false,
            'webdav_url'                   => '',
            'webdav_user'                  => '',
            'webdav_password_enc'          => '',
            'app_key'                      => $generateKey ? bin2hex(random_bytes(32)) : '',
        ];
    }

    public static function getStore(): array {
        if (self::$storeCache !== null) {
            return self::$storeCache;
        }
        $file = self::getDataDir() . DIRECTORY_SEPARATOR . 'shop_invoices.json';
        $loaded = null;

        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            $loaded = StorageManager::loadPluginData('shop_invoices', null, $file);
        } elseif (file_exists($file)) {
            $loaded = json_decode((string)@file_get_contents($file), true);
        }

        $defaults = self::getDefaultConfig(false);
        if (!is_array($loaded)) {
            $store = [
                'config'    => self::getDefaultConfig(true),
                'counters'  => [],
                'invoices'  => [],
            ];
            self::saveStore($store);
            return self::$storeCache ?? $store;
        }

        $loaded['config'] = array_merge($defaults, is_array($loaded['config'] ?? null) ? $loaded['config'] : []);
        $needsSave = false;
        if (empty($loaded['config']['app_key'])) {
            $loaded['config']['app_key'] = bin2hex(random_bytes(32));
            $needsSave = true;
        }
        if (!isset($loaded['counters']) || !is_array($loaded['counters'])) {
            $loaded['counters'] = [];
        }
        if (!isset($loaded['invoices']) || !is_array($loaded['invoices'])) {
            $loaded['invoices'] = [];
        }
        self::$storeCache = $loaded;
        if ($needsSave) {
            self::saveStore($loaded);
        }
        return self::$storeCache;
    }

    public static function saveStore(array $store): bool {
        $file = self::getDataDir() . DIRECTORY_SEPARATOR . 'shop_invoices.json';
        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            $ok = StorageManager::savePluginData('shop_invoices', $store, $file);
        } else {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }
            $tmp = $file . '.tmp.' . bin2hex(random_bytes(5));
            if (@file_put_contents($tmp, $json) === false) {
                return false;
            }
            $ok = @rename($tmp, $file);
        }
        if ($ok) {
            self::$storeCache = $store;
        }
        return $ok;
    }

    public static function getConfig(): array {
        return self::getStore()['config'];
    }

    public static function encryptSecret(string $plain, string $key): string {
        if ($plain === '') return '';
        $k = hash('sha256', $key ?: 'weave-inv-key', true);
        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(12);
            $tag = '';
            $ct = @openssl_encrypt($plain, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
            if ($ct !== false) {
                return 'enc:v1:gcm:' . base64_encode($iv . $tag . $ct);
            }
        }
        return 'enc:v1:b64:' . base64_encode($plain);
    }

    public static function decryptSecret(string $cipher, string $key): string {
        if ($cipher === '') return '';
        $k = hash('sha256', $key ?: 'weave-inv-key', true);
        if (strpos($cipher, 'enc:v1:gcm:') === 0 && function_exists('openssl_decrypt')) {
            $raw = base64_decode(substr($cipher, 11), true);
            if ($raw && strlen($raw) > 28) {
                $pt = @openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $k, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
                return $pt !== false ? $pt : '';
            }
        }
        if (strpos($cipher, 'enc:v1:b64:') === 0) {
            return (string)(base64_decode(substr($cipher, 11), true) ?: '');
        }
        return $cipher;
    }

    public static function saveConfig(array $input): bool {
        $store = self::getStore();
        $cfg = $store['config'];
        $allowedFormats = ['pdf', 'zugferd', 'xrechnung'];
        $rawFmt = (string)($input['default_format'] ?? 'zugferd');
        $defFmt = in_array($rawFmt, $allowedFormats, true) ? $rawFmt : 'zugferd';

        $updated = [
            'enabled'                      => !empty($input['enabled']),
            'checkout_billing_fields'      => !empty($input['checkout_billing_fields']),
            'allow_customer_format_choice' => !empty($input['allow_customer_format_choice']),
            'default_format'               => $defFmt,
            'auto_generate_on_order'       => !empty($input['auto_generate_on_order']),
            'auto_email_customer'          => !empty($input['auto_email_customer']),
            'number_pattern'               => trim((string)($input['number_pattern'] ?? 'RE-{YYYY}-{NNNN}')) ?: 'RE-{YYYY}-{NNNN}',
            'credit_pattern'               => trim((string)($input['credit_pattern'] ?? 'ST-{YYYY}-{NNNN}')) ?: 'ST-{YYYY}-{NNNN}',
            'seller_name'                  => trim((string)($input['seller_name'] ?? '')),
            'seller_contact_name'          => trim((string)($input['seller_contact_name'] ?? '')),
            'seller_street'                => trim((string)($input['seller_street'] ?? '')),
            'seller_zip'                   => trim((string)($input['seller_zip'] ?? '')),
            'seller_city'                  => trim((string)($input['seller_city'] ?? '')),
            'seller_country_code'          => strtoupper(trim((string)($input['seller_country_code'] ?? 'DE'))) ?: 'DE',
            'seller_tax_number'            => trim((string)($input['seller_tax_number'] ?? '')),
            'seller_vat_id'                => trim((string)($input['seller_vat_id'] ?? '')),
            'seller_register'              => trim((string)($input['seller_register'] ?? '')),
            'seller_email'                 => trim((string)($input['seller_email'] ?? '')),
            'seller_bank_name'             => trim((string)($input['seller_bank_name'] ?? '')),
            'seller_iban'                  => preg_replace('/\s+/', '', strtoupper(trim((string)($input['seller_iban'] ?? '')))),
            'seller_bic'                   => preg_replace('/\s+/', '', strtoupper(trim((string)($input['seller_bic'] ?? '')))),
            'payment_terms'                => trim((string)($input['payment_terms'] ?? 'Zahlbar sofort nach Rechnungserhalt ohne Abzug.')),
            'accounting_bcc_email'         => trim((string)($input['accounting_bcc_email'] ?? '')),
            'webdav_enabled'               => !empty($input['webdav_enabled']),
            'webdav_url'                   => trim((string)($input['webdav_url'] ?? '')),
            'webdav_user'                  => trim((string)($input['webdav_user'] ?? '')),
        ];

        $rawPass = trim((string)($input['webdav_password'] ?? ''));
        if ($rawPass !== '') {
            $updated['webdav_password_enc'] = self::encryptSecret($rawPass, (string)$cfg['app_key']);
        }

        $store['config'] = array_merge($cfg, $updated);
        return self::saveStore($store);
    }

    /**
     * Erzeugt lückenlose fortlaufende Rechnungs-/Storno-Nummern je Jahr.
     */
    public static function generateNextNumber(string $type = 'invoice', ?int $year = null): string {
        $store = self::getStore();
        $y = $year ?? (int)date('Y');
        $key = ($type === 'credit_note' ? 'credit_' : 'invoice_') . $y;
        $nextSeq = ((int)($store['counters'][$key] ?? 0)) + 1;
        $store['counters'][$key] = $nextSeq;
        self::saveStore($store);

        $pattern = $type === 'credit_note'
            ? (string)($store['config']['credit_pattern'] ?? 'ST-{YYYY}-{NNNN}')
            : (string)($store['config']['number_pattern'] ?? 'RE-{YYYY}-{NNNN}');

        $seqStr = str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
        return str_replace(['{YYYY}', '{YY}', '{NNNN}'], [(string)$y, substr((string)$y, -2), $seqStr], $pattern);
    }

    protected static function ensureProtectedArchiveDir(string $subDir): string {
        $base = self::getArchiveBaseDir();
        if (!is_dir($base)) {
            @mkdir($base, 0750, true);
        }
        $ht = $base . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Require all denied\nDeny from all\n");
        }
        $idx = $base . DIRECTORY_SEPARATOR . 'index.html';
        if (!file_exists($idx)) {
            @file_put_contents($idx, '');
        }
        $target = $base . DIRECTORY_SEPARATOR . $subDir;
        if (!is_dir($target)) {
            @mkdir($target, 0750, true);
        }
        return $target;
    }

    public static function getInvoices(): array {
        return array_values(self::getStore()['invoices'] ?? []);
    }

    public static function getInvoice(string $invoiceId): ?array {
        foreach (self::getInvoices() as $inv) {
            if (($inv['id'] ?? '') === $invoiceId || ($inv['invoice_number'] ?? '') === $invoiceId) {
                return $inv;
            }
        }
        return null;
    }

    public static function getInvoicesForOrder(string $orderId): array {
        return array_values(array_filter(self::getInvoices(), static fn(array $i): bool => ($i['order_id'] ?? '') === $orderId));
    }

    public static function mapCountryCode(string $countryOrCode): string {
        $raw = trim($countryOrCode);
        if (strlen($raw) === 2 && ctype_alpha($raw)) {
            return strtoupper($raw);
        }
        $lower = mb_strtolower($raw, 'UTF-8');
        $map = [
            'deutschland' => 'DE',
            'germany'     => 'DE',
            'österreich'  => 'AT',
            'oesterreich' => 'AT',
            'austria'     => 'AT',
            'schweiz'     => 'CH',
            'switzerland' => 'CH',
            'frankreich'  => 'FR',
            'france'      => 'FR',
            'niederlande' => 'NL',
            'netherlands' => 'NL',
        ];
        return $map[$lower] ?? 'DE';
    }

    /**
     * Erstellt GoBD-konform PDF / ZUGFeRD / XRechnung inkl. unveränderlicher Archivierung + SHA-256.
     */
    public static function createInvoiceForOrder(array $order, array $shopConfig = [], ?string $formatOverride = null, string $docType = 'invoice', ?string $refInvoiceNumber = null): array {
        $cfg = self::getConfig();
        $allowedFormats = ['pdf', 'zugferd', 'xrechnung'];
        $rawFmt = (string)($formatOverride ?: ($order['invoice_format'] ?? $cfg['default_format'] ?? 'zugferd'));
        $format = in_array($rawFmt, $allowedFormats, true) ? $rawFmt : 'zugferd';

        $billing = is_array($order['billing_address'] ?? null) ? $order['billing_address'] : ($order['customer'] ?? []);
        $nowStr = date('Y-m-d H:i:s');
        $yearMonth = date('Y') . DIRECTORY_SEPARATOR . date('m');

        $invoiceData = [
            'id'                       => 'inv_' . bin2hex(random_bytes(5)),
            'doc_type'                 => $docType,
            'invoice_number'           => '__PENDING__',
            'reference_invoice_number' => $refInvoiceNumber ?? '',
            'order_id'                 => (string)($order['order_id'] ?? ''),
            'format'                   => $format,
            'issued_at'                => $nowStr,
            'delivery_date'            => (string)($order['created_at'] ?? $nowStr),
            'currency'                 => (string)($order['currency'] ?? $shopConfig['currency'] ?? 'EUR'),
            'small_business'           => !empty($shopConfig['small_business']),
            'items'                    => (array)($order['items'] ?? []),
            'shipping'                 => (float)($order['shipping'] ?? 0),
            'payment_method'           => (string)($order['payment_method'] ?? ''),
            'payment_terms'            => (string)$cfg['payment_terms'],
            'seller' => [
                'name'         => (string)$cfg['seller_name'],
                'contact_name' => (string)$cfg['seller_contact_name'],
                'street'       => (string)$cfg['seller_street'],
                'zip'          => (string)$cfg['seller_zip'],
                'city'         => (string)$cfg['seller_city'],
                'country_code' => (string)$cfg['seller_country_code'],
                'tax_number'   => (string)$cfg['seller_tax_number'],
                'vat_id'       => (string)$cfg['seller_vat_id'],
                'register'     => (string)$cfg['seller_register'],
                'email'        => (string)$cfg['seller_email'],
                'bank_name'    => (string)$cfg['seller_bank_name'],
                'iban'         => (string)$cfg['seller_iban'],
                'bic'          => (string)$cfg['seller_bic'],
            ],
            'buyer' => [
                'company'      => trim((string)($billing['company'] ?? '')),
                'name'         => trim((string)($billing['name'] ?? ($order['customer']['name'] ?? ''))),
                'email'        => trim((string)($billing['email'] ?? ($order['customer']['email'] ?? ''))),
                'street'       => trim((string)($billing['street'] ?? ($order['customer']['street'] ?? ''))),
                'zip'          => trim((string)($billing['zip'] ?? ($order['customer']['zip'] ?? ''))),
                'city'         => trim((string)($billing['city'] ?? ($order['customer']['city'] ?? ''))),
                'country_code' => self::mapCountryCode((string)($billing['country_code'] ?? ($billing['country'] ?? ($order['customer']['country'] ?? 'DE')))),
                'vat_id'       => trim((string)($billing['vat_id'] ?? '')),
                'leitweg_id'   => trim((string)($billing['leitweg_id'] ?? '')),
            ],
        ];

        $check = InvoiceXmlBuilder::validateCompliance($invoiceData, $format);
        if (!$check['valid']) {
            return ['success' => false, 'message' => implode(' ', $check['errors']), 'errors' => $check['errors']];
        }

        // GoBD: Fortlaufende Nummer erst nach bestandener Pflichtfeld-Prüfung vergeben (keine Nummern-Lücken)
        $invNumber = self::generateNextNumber($docType);
        $invoiceData['invoice_number'] = $invNumber;

        $xmlContent = null;
        if ($format === 'zugferd' || $format === 'xrechnung') {
            $xmlContent = InvoiceXmlBuilder::buildCiiXml($invoiceData, $format);
            if (!InvoiceXmlBuilder::validateXmlWellFormed($xmlContent)) {
                return ['success' => false, 'message' => 'Generierte E-Rechnungs-XML konnte nicht validiert werden.'];
            }
        }

        $pdfBytes = InvoicePdfBuilder::buildPdf($invoiceData, $format === 'zugferd' ? $xmlContent : null);
        $breakdown = InvoiceXmlBuilder::calculateBreakdown($invoiceData);

        $dir = self::ensureProtectedArchiveDir($yearMonth);
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $invNumber);
        $pdfRel = str_replace(DIRECTORY_SEPARATOR, '/', $yearMonth) . '/' . $safeBase . '.pdf';
        $pdfAbs = self::getArchiveBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pdfRel);
        if (@file_put_contents($pdfAbs, $pdfBytes) === false) {
            return ['success' => false, 'message' => 'PDF-Archivdatei konnte nicht geschrieben werden.'];
        }
        $pdfSha = hash('sha256', $pdfBytes);

        $xmlRel = '';
        $xmlSha = '';
        if ($format === 'xrechnung' && $xmlContent !== null) {
            $xmlRel = str_replace(DIRECTORY_SEPARATOR, '/', $yearMonth) . '/' . $safeBase . '.xml';
            $xmlAbs = self::getArchiveBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $xmlRel);
            if (@file_put_contents($xmlAbs, $xmlContent) === false) {
                return ['success' => false, 'message' => 'XRechnung-XML konnte nicht archiviert werden.'];
            }
            $xmlSha = hash('sha256', $xmlContent);
        }

        $record = [
            'id'                       => $invoiceData['id'],
            'doc_type'                 => $docType,
            'invoice_number'           => $invNumber,
            'reference_invoice_number' => $refInvoiceNumber ?? '',
            'order_id'                 => $invoiceData['order_id'],
            'format'                   => $format,
            'issued_at'                => $nowStr,
            'buyer_name'               => $invoiceData['buyer']['company'] !== '' ? $invoiceData['buyer']['company'] : $invoiceData['buyer']['name'],
            'buyer_email'              => $invoiceData['buyer']['email'],
            'buyer_vat_id'             => $invoiceData['buyer']['vat_id'],
            'currency'                 => $invoiceData['currency'],
            'net_total'                => $breakdown['net_total'],
            'tax_total'                => $breakdown['tax_total'],
            'gross_total'              => $breakdown['gross_total'],
            'pdf_rel_path'             => $pdfRel,
            'pdf_sha256'               => $pdfSha,
            'xml_rel_path'             => $xmlRel,
            'xml_sha256'               => $xmlSha,
            'emailed_at'               => '',
            'webdav_synced_at'         => '',
        ];

        $store = self::getStore();
        array_unshift($store['invoices'], $record);
        self::saveStore($store);

        return ['success' => true, 'invoice' => $record, 'message' => "Beleg {$invNumber} ({$format}) erstellt & GoBD-archiviert."];
    }

    public static function createCreditNote(string $invoiceId, string $reason = ''): array {
        $orig = self::getInvoice($invoiceId);
        if (!$orig) {
            return ['success' => false, 'message' => 'Ursprungsrechnung nicht gefunden.'];
        }
        $order = class_exists('ShopManager') ? ShopManager::getOrder((string)$orig['order_id']) : null;
        if (!$order) {
            return ['success' => false, 'message' => 'Zugehörige Bestellung für Storno nicht gefunden.'];
        }
        $shopConfig = [];
        if (class_exists('PluginManager') && !empty($GLOBALS['data'])) {
            $shopConfig = PluginManager::getPluginConfig('weave-shop', $GLOBALS['data']) ?: [];
        }
        return self::createInvoiceForOrder($order, $shopConfig, (string)$orig['format'], 'credit_note', (string)$orig['invoice_number']);
    }

    public static function getArchiveFilePath(string $relPath): string {
        $cleanRel = ltrim(str_replace(['..', '\\'], ['', '/'], $relPath), '/');
        return self::getArchiveBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);
    }

    public static function verifyArchiveIntegrity(string $invoiceId): array {
        $inv = self::getInvoice($invoiceId);
        if (!$inv) {
            return ['ok' => false, 'message' => 'Beleg nicht gefunden.'];
        }
        $pdfFile = self::getArchiveFilePath((string)$inv['pdf_rel_path']);
        if (!file_exists($pdfFile)) {
            return ['ok' => false, 'message' => 'PDF-Datei fehlt im Online-Archiv.'];
        }
        $actualPdf = hash_file('sha256', $pdfFile);
        if (!hash_equals((string)$inv['pdf_sha256'], (string)$actualPdf)) {
            return ['ok' => false, 'message' => 'SHA-256 Integritätsprüfung der PDF fehlgeschlagen!'];
        }
        if (!empty($inv['xml_rel_path'])) {
            $xmlFile = self::getArchiveFilePath((string)$inv['xml_rel_path']);
            if (!file_exists($xmlFile) || !hash_equals((string)$inv['xml_sha256'], (string)hash_file('sha256', $xmlFile))) {
                return ['ok' => false, 'message' => 'SHA-256 Integritätsprüfung der XML fehlgeschlagen!'];
            }
        }
        return ['ok' => true, 'message' => 'SHA-256 Integrität bestätigt.'];
    }

    public static function sendInvoiceEmail(string $invoiceId, ?array $smtpConfig = null, ?callable $mailSender = null): array {
        $inv = self::getInvoice($invoiceId);
        if (!$inv) {
            return ['success' => false, 'message' => 'Beleg nicht gefunden.'];
        }
        $recipient = trim((string)($inv['buyer_email'] ?? ''));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Keine gültige Kunden-E-Mail hinterlegt.'];
        }

        $attachments = [];
        $pdfPath = self::getArchiveFilePath((string)$inv['pdf_rel_path']);
        if (file_exists($pdfPath)) {
            $attachments[] = [
                'filename' => basename($pdfPath),
                'content'  => (string)file_get_contents($pdfPath),
                'mime'     => 'application/pdf',
            ];
        }
        if (!empty($inv['xml_rel_path'])) {
            $xmlPath = self::getArchiveFilePath((string)$inv['xml_rel_path']);
            if (file_exists($xmlPath)) {
                $attachments[] = [
                    'filename' => basename($xmlPath),
                    'content'  => (string)file_get_contents($xmlPath),
                    'mime'     => 'application/xml',
                ];
            }
        }

        $subject = (($inv['doc_type'] ?? 'invoice') === 'credit_note' ? 'Ihre Stornorechnung ' : 'Ihre Rechnung ') . $inv['invoice_number'];
        $text = "Guten Tag {$inv['buyer_name']},\n\nanbei erhalten Sie Ihren Beleg {$inv['invoice_number']} (Format: {$inv['format']}) über "
            . number_format((float)$inv['gross_total'], 2, ',', '.') . " {$inv['currency']}.\n";
        $html = '<p>Guten Tag ' . htmlspecialchars((string)$inv['buyer_name'], ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>anbei erhalten Sie Ihren Beleg <strong>' . htmlspecialchars((string)$inv['invoice_number'], ENT_QUOTES, 'UTF-8')
            . '</strong> über <strong>' . number_format((float)$inv['gross_total'], 2, ',', '.') . ' ' . htmlspecialchars((string)$inv['currency'], ENT_QUOTES, 'UTF-8') . '</strong>.</p>';

        $sendFn = $mailSender ?? static function (string $to, string $subj, string $h, string $t, array $att) use ($smtpConfig): array {
            return class_exists('SimpleMailer')
                ? SimpleMailer::send($to, $subj, $h, $t, '', '', $smtpConfig, $att)
                : ['success' => false, 'error' => 'SimpleMailer nicht verfügbar.'];
        };

        $res = $sendFn($recipient, $subject, $html, $text, $attachments);
        $bcc = trim((string)(self::getConfig()['accounting_bcc_email'] ?? ''));
        if ($bcc !== '' && filter_var($bcc, FILTER_VALIDATE_EMAIL) && strtolower($bcc) !== strtolower($recipient)) {
            $sendFn($bcc, '[Buchhaltung-Kopie] ' . $subject, $html, $text, $attachments);
        }

        if (!empty($res['success'])) {
            $store = self::getStore();
            foreach ($store['invoices'] as &$row) {
                if (($row['id'] ?? '') === $inv['id']) {
                    $row['emailed_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($row);
            self::saveStore($store);
            return ['success' => true, 'message' => "Beleg {$inv['invoice_number']} versendet."];
        }
        return ['success' => false, 'message' => (string)($res['error'] ?? 'E-Mail-Versand fehlgeschlagen.')];
    }

    public static function exportAccountingCsv(?string $year = null, ?string $month = null): string {
        $invoices = self::getInvoices();
        $prefix = ($year !== null && $year !== '') ? ($year . ($month !== null && $month !== '' ? '-' . str_pad($month, 2, '0', STR_PAD_LEFT) : '')) : '';

        $sanitize = static function (string $v): string {
            return ($v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) ? "'" . $v : $v;
        };

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Belegnummer', 'Typ', 'Belegdatum', 'Bestell-ID', 'Kunde', 'USt-IdNr.', 'Format', 'Netto', 'MwSt', 'Brutto', 'Währung', 'PDF-SHA256'], ';', '"', '');
        foreach ($invoices as $inv) {
            if ($prefix !== '' && strpos((string)$inv['issued_at'], $prefix) !== 0) {
                continue;
            }
            fputcsv($out, array_map($sanitize, [
                (string)$inv['invoice_number'],
                (string)$inv['doc_type'],
                substr((string)$inv['issued_at'], 0, 10),
                (string)$inv['order_id'],
                (string)$inv['buyer_name'],
                (string)($inv['buyer_vat_id'] ?? ''),
                (string)$inv['format'],
                number_format((float)$inv['net_total'], 2, ',', ''),
                number_format((float)$inv['tax_total'], 2, ',', ''),
                number_format((float)$inv['gross_total'], 2, ',', ''),
                (string)$inv['currency'],
                (string)$inv['pdf_sha256'],
            ]), ';', '"', '');
        }
        rewind($out);
        $csv = (string)stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    public static function createArchiveZip(?string $year = null, ?string $month = null): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'PHP ZipArchive-Erweiterung ist auf diesem Server nicht aktiviert.'];
        }
        $prefix = ($year !== null && $year !== '') ? ($year . ($month !== null && $month !== '' ? '-' . str_pad($month, 2, '0', STR_PAD_LEFT) : '')) : '';
        $tmpZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_invoices_' . bin2hex(random_bytes(5)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'message' => 'ZIP-Archiv konnte nicht erstellt werden.'];
        }

        $count = 0;
        foreach (self::getInvoices() as $inv) {
            if ($prefix !== '' && strpos((string)$inv['issued_at'], $prefix) !== 0) {
                continue;
            }
            $pdf = self::getArchiveFilePath((string)$inv['pdf_rel_path']);
            if (file_exists($pdf)) {
                $zip->addFile($pdf, 'belege/' . (string)$inv['pdf_rel_path']);
                $count++;
            }
            if (!empty($inv['xml_rel_path'])) {
                $xml = self::getArchiveFilePath((string)$inv['xml_rel_path']);
                if (file_exists($xml)) {
                    $zip->addFile($xml, 'belege/' . (string)$inv['xml_rel_path']);
                }
            }
        }
        $zip->addFromString('buchungsjournal.csv', "\xEF\xBB\xBF" . self::exportAccountingCsv($year, $month));
        $zip->close();

        return ['success' => true, 'zip_path' => $tmpZip, 'count' => $count];
    }

    public static function validateExternalUrl(string $url): bool {
        $parts = parse_url(trim($url));
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return false;
        }
        $host = strtolower(trim((string)$parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }

    public static function syncInvoiceToWebDav(string $invoiceId): array {
        $cfg = self::getConfig();
        if (empty($cfg['webdav_enabled']) || empty($cfg['webdav_url']) || empty($cfg['webdav_user'])) {
            return ['success' => false, 'message' => 'WebDAV / Nextcloud-Archiv ist nicht konfiguriert.'];
        }
        if (!self::validateExternalUrl((string)$cfg['webdav_url'])) {
            return ['success' => false, 'message' => 'WebDAV-URL muss eine gültige öffentliche HTTPS-Adresse sein.'];
        }
        $inv = self::getInvoice($invoiceId);
        if (!$inv) {
            return ['success' => false, 'message' => 'Beleg nicht gefunden.'];
        }
        $pass = self::decryptSecret((string)($cfg['webdav_password_enc'] ?? ''), (string)($cfg['app_key'] ?? ''));
        $baseUrl = rtrim((string)$cfg['webdav_url'], '/');
        $pdfPath = self::getArchiveFilePath((string)$inv['pdf_rel_path']);
        if (!file_exists($pdfPath) || !function_exists('curl_init')) {
            return ['success' => false, 'message' => 'Datei oder cURL fehlt für WebDAV-Upload.'];
        }

        $upload = static function (string $localFile, string $targetName) use ($baseUrl, $cfg, $pass): bool {
            $ch = curl_init($baseUrl . '/' . rawurlencode($targetName));
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_POSTFIELDS     => (string)file_get_contents($localFile),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $cfg['webdav_user'] . ':' . $pass,
                CURLOPT_TIMEOUT        => 15,
            ]);
            curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code >= 200 && $code < 300;
        };

        if (!$upload($pdfPath, basename($pdfPath))) {
            return ['success' => false, 'message' => 'WebDAV-Upload fehlgeschlagen.'];
        }
        if (!empty($inv['xml_rel_path'])) {
            $xmlPath = self::getArchiveFilePath((string)$inv['xml_rel_path']);
            if (file_exists($xmlPath)) {
                $upload($xmlPath, basename($xmlPath));
            }
        }

        $store = self::getStore();
        foreach ($store['invoices'] as &$row) {
            if (($row['id'] ?? '') === $inv['id']) {
                $row['webdav_synced_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($row);
        self::saveStore($store);
        return ['success' => true, 'message' => "Beleg {$inv['invoice_number']} per WebDAV archiviert."];
    }
}
