<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Weave Shop Rechnungen & E-Rechnung (Phase 37)
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/libs/php/Mailer.php';
require_once dirname(__DIR__) . '/plugins/weave-shop/ShopManager.php';
require_once dirname(__DIR__) . '/plugins/weave-shop/InvoiceXmlBuilder.php';
require_once dirname(__DIR__) . '/plugins/weave-shop/InvoicePdfBuilder.php';
require_once dirname(__DIR__) . '/plugins/weave-shop/InvoiceManager.php';

function assert_inv(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_inv_test_' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0755, true);
InvoiceManager::setTestStorageDir($tmpDir);

try {
    // 1. Konfiguration & Pflichtfeld-Validierung (§ 14 UStG / EN 16931)
    InvoiceManager::saveConfig([
        'enabled'                 => true,
        'checkout_billing_fields' => true,
        'default_format'          => 'zugferd',
        'seller_name'             => 'WeaveCraft GmbH',
        'seller_street'           => 'Werkstattweg 7',
        'seller_zip'              => '80331',
        'seller_city'             => 'München',
        'seller_tax_number'       => '', // Fehlend!
        'seller_vat_id'           => '',
        'seller_email'            => 'rechnungen@weavecraft.de',
        'seller_iban'             => 'DE89370400440532013000',
        'seller_bic'              => 'COBADEFFXXX',
        'accounting_bcc_email'    => 'steuerberater@weavecraft.de',
        'webdav_enabled'          => true,
        'webdav_url'              => 'https://cloud.weavecraft.de/remote.php/dav/files/inv/',
        'webdav_user'             => 'archive_bot',
        'webdav_password'         => 'test_dav_' . bin2hex(random_bytes(6)),
    ]);

    $sampleOrder = [
        'order_id'       => 'ORD-20300501-ABC123',
        'payment_method' => 'bank_transfer',
        'created_at'     => '2030-05-01 10:30:00',
        'currency'       => 'EUR',
        'shipping'       => 4.90,
        'customer'       => [
            'name'    => 'Max Mustermann',
            'email'   => 'max@kunde.de',
            'street'  => 'Lieferweg 1',
            'zip'     => '10115',
            'city'    => 'Berlin',
            'country' => 'Deutschland',
        ],
        'billing_address' => [
            'company'    => 'Behörde / Firma GmbH',
            'name'       => 'Erika Einkauf',
            'email'      => 'einkauf@behoerde.de',
            'street'     => 'Rathausplatz 1',
            'zip'        => '50667',
            'city'       => 'Köln',
            'vat_id'     => 'DE999888777',
            'leitweg_id' => '04011000-12345-67',
        ],
        'items' => [
            ['id' => 'p1', 'sku' => 'ART-01', 'title' => 'Handgefertigter Korb', 'price' => 59.50, 'tax_rate' => 19, 'qty' => 2],
            ['id' => 'p2', 'sku' => 'ART-02', 'title' => 'Buch Flechtkunst', 'price' => 10.70, 'tax_rate' => 7, 'qty' => 1],
        ],
    ];

    $failNoTax = InvoiceManager::createInvoiceForOrder($sampleOrder, [], 'zugferd');
    assert_inv(!$failNoTax['success'], 'Fehlende Steuernummer/USt-IdNr. muss von EN-16931-Validierung gestoppt werden');

    // Steuernummer ergänzen
    $curCfg = InvoiceManager::getConfig();
    InvoiceManager::saveConfig(array_merge($curCfg, ['seller_tax_number' => '143/123/45678']));

    // 2. ZUGFeRD (PDF/A-3 + eingebettete factur-x.xml) erstellen & lückenlosen Nummernkreis testen
    $zugRes = InvoiceManager::createInvoiceForOrder($sampleOrder, ['currency' => 'EUR'], 'zugferd');
    assert_inv($zugRes['success'], 'ZUGFeRD-Rechnung muss erfolgreich erstellt werden');
    $zugInv = $zugRes['invoice'];
    assert_inv($zugInv['invoice_number'] === 'RE-' . date('Y') . '-0001', 'Fehlgeschlagene Validierung darf keine Rechnungsnummer verbrauchen (GoBD-Lückenlosigkeit)');

    $zugPdfPath = InvoiceManager::getArchiveFilePath($zugInv['pdf_rel_path']);
    assert_inv(file_exists($zugPdfPath), 'ZUGFeRD PDF muss im Online-Archiv liegen');
    $zugPdfBytes = (string)file_get_contents($zugPdfPath);
    assert_inv(strpos($zugPdfBytes, '%PDF-1.7') === 0, 'PDF-Signatur fehlt');
    assert_inv(strpos($zugPdfBytes, '/AFRelationship /Alternative') !== false && strpos($zugPdfBytes, 'factur-x.xml') !== false && strpos($zugPdfBytes, 'pdfaid:part="3"') !== false, 'ZUGFeRD PDF/A-3 / EmbeddedFile / XMP Struktur fehlt');

    // .htaccess Online-Archivschutz prüfen
    assert_inv(file_exists(InvoiceManager::getArchiveBaseDir() . DIRECTORY_SEPARATOR . '.htaccess'), 'Online-Archiv muss durch .htaccess geschützt sein');

    // 3. XRechnung (PDF + separates EN 16931 CII XML mit Leitweg-ID)
    $xrRes = InvoiceManager::createInvoiceForOrder($sampleOrder, ['currency' => 'EUR'], 'xrechnung');
    assert_inv($xrRes['success'], 'XRechnung muss erfolgreich erstellt werden');
    $xrInv = $xrRes['invoice'];
    assert_inv(!empty($xrInv['xml_rel_path']), 'XRechnung muss separate XML-Datei erzeugen');
    $xrXml = (string)file_get_contents(InvoiceManager::getArchiveFilePath($xrInv['xml_rel_path']));
    assert_inv(InvoiceXmlBuilder::validateXmlWellFormed($xrXml), 'XRechnung XML muss wohlgeformtes CrossIndustryInvoice XML sein');
    assert_inv(strpos($xrXml, '04011000-12345-67') !== false && strpos($xrXml, 'kosit:xrechnung_3.0') !== false, 'XRechnung muss Leitweg-ID und KoSIT Customization-ID enthalten');

    // 4. Stornobeleg (TypeCode 381 / negative Beträge) & Kleinunternehmer § 19 UStG
    $stornoXml = InvoiceXmlBuilder::buildCiiXml([
        'doc_type'                 => 'credit_note',
        'invoice_number'           => 'ST-' . date('Y') . '-0001',
        'reference_invoice_number' => $zugInv['invoice_number'],
        'order_id'                 => $sampleOrder['order_id'],
        'small_business'           => true,
        'seller'                   => ['name' => 'WeaveCraft', 'street' => 'S 1', 'zip' => '80331', 'city' => 'München', 'tax_number' => '123', 'email' => 'a@b.de'],
        'buyer'                    => $sampleOrder['billing_address'],
        'items'                    => $sampleOrder['items'],
    ], 'zugferd');
    assert_inv(strpos($stornoXml, '<ram:TypeCode>381</ram:TypeCode>') !== false && strpos($stornoXml, '§ 19 UStG') !== false, 'Storno-XML muss TypeCode 381 und § 19 UStG führen');

    // 5. SHA-256 Integrität, CSV-Journal, lokales ZIP-Archiv & WebDAV-SSRF
    $integrity = InvoiceManager::verifyArchiveIntegrity($xrInv['id']);
    assert_inv($integrity['ok'], 'SHA-256 Integritätsprüfung fehlgeschlagen');

    $csv = InvoiceManager::exportAccountingCsv();
    assert_inv(strpos($csv, $zugInv['invoice_number']) !== false && strpos($csv, $xrInv['invoice_number']) !== false, 'Buchungsjournal CSV unvollständig');

    $zipRes = InvoiceManager::createArchiveZip();
    assert_inv($zipRes['success'] && file_exists($zipRes['zip_path']), 'Lokales ZIP-Archiv muss erstellt werden');
    @unlink($zipRes['zip_path']);

    assert_inv(!InvoiceManager::validateExternalUrl('http://127.0.0.1/dav'), 'Unsichere WebDAV-URL muss blockiert werden');
    assert_inv(InvoiceManager::validateExternalUrl('https://cloud.weavecraft.de/remote.php/dav/files/inv/'), 'Gültige HTTPS-WebDAV-URL muss akzeptiert werden');

    // 6. E-Mail-Versand inkl. Anhang + Buchhaltung BCC
    $dispatched = [];
    $mockMail = static function (string $to, string $subj, string $html, string $text, array $att) use (&$dispatched): array {
        $dispatched[] = ['to' => $to, 'subj' => $subj, 'attachments' => count($att)];
        return ['success' => true];
    };
    $mailRes = InvoiceManager::sendInvoiceEmail($xrInv['id'], null, $mockMail);
    assert_inv($mailRes['success'] && count($dispatched) === 2 && $dispatched[0]['attachments'] === 2, 'XRechnung-Mail muss 2 Anhänge (PDF + XML) und Buchhaltungs-BCC versenden');

    // 7. Zahlungs-Hook (shop_process_payment) erhält billing_address & shipping_address
    $hookCaptured = null;
    PluginManager::addFilter('shop_process_payment', static function ($prev, $method, $total, $details) use (&$hookCaptured) {
        $hookCaptured = $details;
        return ['success' => true, 'status' => 'paid', 'payment_ref' => 'HOOK-OK'];
    }, 10, 4);
    $payRes = ShopManager::processPayment('stripe_mock', 100.0, [
        'billing_address'  => $sampleOrder['billing_address'],
        'shipping_address' => $sampleOrder['customer'],
    ], []);
    assert_inv($payRes['success'] && ($hookCaptured['billing_address']['leitweg_id'] ?? '') === '04011000-12345-67', 'shop_process_payment Hook muss billing_address empfangen');

    echo "ALL SHOP-INVOICE TESTS PASSED (7/7)\n";
} finally {
    InvoiceManager::setTestStorageDir(null);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($tmpDir);
}
