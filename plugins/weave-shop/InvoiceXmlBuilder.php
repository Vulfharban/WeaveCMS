<?php
/**
 * ============================================================================
 * WeaveCMS Shop – EN 16931 CII XML Generator (ZUGFeRD 2.2 & XRechnung 3.0)
 * ============================================================================
 * Erzeugt UN/CEFACT CrossIndustryInvoice XML nach EN 16931 für:
 * - ZUGFeRD / Factur-X (urn:cen.eu:en16931:2017)
 * - XRechnung (urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0)
 * Inklusive Pflichtfeld- und DOMDocument-Schema-Prüfung.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class InvoiceXmlBuilder {

    protected static function xmlEsc(string $val): string {
        return htmlspecialchars($val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected static function fmtMoney(float $amount): string {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Ermittelt Netto-/MwSt.-Aufschlüsselung aus Brutto-Bestellpositionen + Versandkosten.
     *
     * @return array{lines: array, tax_groups: array, net_total: float, tax_total: float, gross_total: float, sign: int}
     */
    public static function calculateBreakdown(array $invoiceData): array {
        $isCredit = (($invoiceData['doc_type'] ?? 'invoice') === 'credit_note');
        $sign = $isCredit ? -1 : 1;
        $isSmallBusiness = !empty($invoiceData['small_business']);
        $currency = (string)($invoiceData['currency'] ?? 'EUR');

        $lines = [];
        $taxGroups = [];
        $netSum = 0.0;
        $taxSum = 0.0;
        $grossSum = 0.0;

        $lineNo = 0;
        foreach ((array)($invoiceData['items'] ?? []) as $item) {
            $lineNo++;
            $qty = max(1, (int)($item['qty'] ?? 1));
            $grossUnit = (float)($item['price'] ?? 0);
            $grossLine = round(abs($grossUnit) * $qty, 2) * $sign;
            $rate = $isSmallBusiness ? 0.0 : (float)($item['tax_rate'] ?? 19.0);
            $catCode = $isSmallBusiness ? 'E' : ($rate > 0 ? 'S' : 'Z');

            $netLine = $rate > 0 ? round($grossLine / (1 + ($rate / 100)), 2) : $grossLine;
            $vatLine = round($grossLine - $netLine, 2);
            $netUnit = $qty > 0 ? round(abs($netLine) / $qty, 2) : 0.0;

            $netSum += $netLine;
            $taxSum += $vatLine;
            $grossSum += $grossLine;

            $groupKey = $catCode . ':' . number_format($rate, 2, '.', '');
            if (!isset($taxGroups[$groupKey])) {
                $taxGroups[$groupKey] = [
                    'category_code' => $catCode,
                    'rate'          => $rate,
                    'basis_amount'  => 0.0,
                    'tax_amount'    => 0.0,
                ];
            }
            $taxGroups[$groupKey]['basis_amount'] += $netLine;
            $taxGroups[$groupKey]['tax_amount'] += $vatLine;

            $lines[] = [
                'line_id'      => (string)$lineNo,
                'sku'          => (string)($item['sku'] ?? 'ART-' . $lineNo),
                'title'        => (string)($item['title'] ?? 'Artikel'),
                'qty'          => $qty * $sign,
                'net_unit'     => $netUnit,
                'gross_unit'   => round(abs($grossUnit), 2),
                'net_total'    => $netLine,
                'vat_amount'   => $vatLine,
                'gross_total'  => $grossLine,
                'tax_rate'     => $rate,
                'tax_cat_code' => $catCode,
                'currency'     => $currency,
            ];
        }

        // Versandkosten als eigene Position
        $shippingGross = round(abs((float)($invoiceData['shipping'] ?? 0)), 2) * $sign;
        if (abs($shippingGross) > 0.0001) {
            $lineNo++;
            $shipRate = $isSmallBusiness ? 0.0 : 19.0;
            $shipCat = $isSmallBusiness ? 'E' : 'S';
            $shipNet = $shipRate > 0 ? round($shippingGross / (1 + ($shipRate / 100)), 2) : $shippingGross;
            $shipVat = round($shippingGross - $shipNet, 2);

            $netSum += $shipNet;
            $taxSum += $shipVat;
            $grossSum += $shippingGross;

            $groupKey = $shipCat . ':' . number_format($shipRate, 2, '.', '');
            if (!isset($taxGroups[$groupKey])) {
                $taxGroups[$groupKey] = [
                    'category_code' => $shipCat,
                    'rate'          => $shipRate,
                    'basis_amount'  => 0.0,
                    'tax_amount'    => 0.0,
                ];
            }
            $taxGroups[$groupKey]['basis_amount'] += $shipNet;
            $taxGroups[$groupKey]['tax_amount'] += $shipVat;

            $lines[] = [
                'line_id'      => (string)$lineNo,
                'sku'          => 'VERSAND',
                'title'        => 'Versandkosten',
                'qty'          => 1 * $sign,
                'net_unit'     => abs($shipNet),
                'gross_unit'   => abs($shippingGross),
                'net_total'    => $shipNet,
                'vat_amount'   => $shipVat,
                'gross_total'  => $shippingGross,
                'tax_rate'     => $shipRate,
                'tax_cat_code' => $shipCat,
                'currency'     => $currency,
            ];
        }

        return [
            'lines'       => $lines,
            'tax_groups'  => array_values($taxGroups),
            'net_total'   => round($netSum, 2),
            'tax_total'   => round($taxSum, 2),
            'gross_total' => round($grossSum, 2),
            'sign'        => $sign,
        ];
    }

    /**
     * Prüft gesetzliche § 14 UStG und EN-16931-Pflichtfelder vor E-Rechnungserstellung.
     *
     * @return array{valid: bool, errors: array<string>}
     */
    public static function validateCompliance(array $invoiceData, string $format = 'pdf'): array {
        $errors = [];
        $seller = (array)($invoiceData['seller'] ?? []);
        $buyer = (array)($invoiceData['buyer'] ?? []);

        if (trim((string)($invoiceData['invoice_number'] ?? '')) === '') {
            $errors[] = 'Rechnungsnummer fehlt.';
        }
        if (trim((string)($seller['name'] ?? '')) === '') {
            $errors[] = 'Verkäufername / Firmenname (BG-4) fehlt in den Rechnungseinstellungen.';
        }
        if (trim((string)($seller['street'] ?? '')) === '' || trim((string)($seller['zip'] ?? '')) === '' || trim((string)($seller['city'] ?? '')) === '') {
            $errors[] = 'Vollständige Verkäufer-Anschrift (Straße, PLZ, Ort) erforderlich (§ 14 UStG).';
        }
        if (trim((string)($seller['tax_number'] ?? '')) === '' && trim((string)($seller['vat_id'] ?? '')) === '') {
            $errors[] = 'Steuernummer oder USt-IdNr. des Verkäufers erforderlich (§ 14 UStG / BT-31/32).';
        }
        if (trim((string)($buyer['name'] ?? '')) === '' && trim((string)($buyer['company'] ?? '')) === '') {
            $errors[] = 'Rechnungsempfänger (Name oder Firma) fehlt.';
        }
        if (trim((string)($buyer['street'] ?? '')) === '' || trim((string)($buyer['zip'] ?? '')) === '' || trim((string)($buyer['city'] ?? '')) === '') {
            $errors[] = 'Vollständige Rechnungsadresse des Käufers fehlt.';
        }
        if (empty($invoiceData['items']) || !is_array($invoiceData['items'])) {
            $errors[] = 'Die Rechnung enthält keine Rechnungspositionen.';
        }
        if ($format === 'xrechnung') {
            $leitweg = trim((string)($buyer['leitweg_id'] ?? ''));
            $orderRef = trim((string)($invoiceData['order_id'] ?? ''));
            if ($leitweg === '' && $orderRef === '') {
                $errors[] = 'Für XRechnung ist eine BuyerReference (Leitweg-ID oder Bestellreferenz, BT-10) erforderlich.';
            }
            if (trim((string)($seller['email'] ?? '')) === '') {
                $errors[] = 'Für XRechnung ist die Verkäufer-E-Mail-Adresse (BT-43) erforderlich.';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Erzeugt valides UN/CEFACT CrossIndustryInvoice XML (EN 16931) für ZUGFeRD oder XRechnung.
     */
    public static function buildCiiXml(array $invoiceData, string $format = 'zugferd'): string {
        $breakdown = self::calculateBreakdown($invoiceData);
        $isXRechnung = ($format === 'xrechnung');
        $isCredit = (($invoiceData['doc_type'] ?? 'invoice') === 'credit_note');
        $isSmallBusiness = !empty($invoiceData['small_business']);

        $guidelineId = $isXRechnung
            ? 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0'
            : 'urn:cen.eu:en16931:2017';

        $typeCode = $isCredit ? '381' : '380';
        $invNumber = (string)($invoiceData['invoice_number'] ?? '');
        $issueTs = strtotime((string)($invoiceData['issued_at'] ?? 'now')) ?: time();
        $deliveryTs = strtotime((string)($invoiceData['delivery_date'] ?? ($invoiceData['issued_at'] ?? 'now'))) ?: $issueTs;
        $issue102 = date('Ymd', $issueTs);
        $delivery102 = date('Ymd', $deliveryTs);
        $currency = (string)($invoiceData['currency'] ?? 'EUR');

        $seller = (array)($invoiceData['seller'] ?? []);
        $buyer = (array)($invoiceData['buyer'] ?? []);
        $buyerName = trim((string)($buyer['company'] ?? '')) !== '' ? trim((string)$buyer['company']) : trim((string)($buyer['name'] ?? 'Kunde'));
        $buyerRef = trim((string)($buyer['leitweg_id'] ?? '')) ?: trim((string)($invoiceData['order_id'] ?? $invNumber));
        $sellerCountry = strtoupper(trim((string)($seller['country_code'] ?? 'DE'))) ?: 'DE';
        $buyerCountry = strtoupper(trim((string)($buyer['country_code'] ?? 'DE'))) ?: 'DE';

        $noteText = $isSmallBusiness
            ? 'Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG.'
            : (string)($invoiceData['note'] ?? 'Vielen Dank für Ihre Bestellung.');

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">';
        $xml[] = '  <rsm:ExchangedDocumentContext>';
        if ($isXRechnung) {
            $xml[] = '    <ram:BusinessProcessSpecifiedDocumentContextParameter><ram:ID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</ram:ID></ram:BusinessProcessSpecifiedDocumentContextParameter>';
        }
        $xml[] = '    <ram:GuidelineSpecifiedDocumentContextParameter><ram:ID>' . self::xmlEsc($guidelineId) . '</ram:ID></ram:GuidelineSpecifiedDocumentContextParameter>';
        $xml[] = '  </rsm:ExchangedDocumentContext>';

        $xml[] = '  <rsm:ExchangedDocument>';
        $xml[] = '    <ram:ID>' . self::xmlEsc($invNumber) . '</ram:ID>';
        $xml[] = '    <ram:TypeCode>' . $typeCode . '</ram:TypeCode>';
        $xml[] = '    <ram:IssueDateTime><udt:DateTimeString format="102">' . $issue102 . '</udt:DateTimeString></ram:IssueDateTime>';
        $xml[] = '    <ram:IncludedNote><ram:Content>' . self::xmlEsc($noteText) . '</ram:Content></ram:IncludedNote>';
        $xml[] = '  </rsm:ExchangedDocument>';

        $xml[] = '  <rsm:SupplyChainTradeTransaction>';
        foreach ($breakdown['lines'] as $ln) {
            $xml[] = '    <ram:IncludedSupplyChainTradeLineItem>';
            $xml[] = '      <ram:AssociatedDocumentLineDocument><ram:LineID>' . self::xmlEsc($ln['line_id']) . '</ram:LineID></ram:AssociatedDocumentLineDocument>';
            $xml[] = '      <ram:SpecifiedTradeProduct>';
            if ($ln['sku'] !== '') {
                $xml[] = '        <ram:SellerAssignedID>' . self::xmlEsc($ln['sku']) . '</ram:SellerAssignedID>';
            }
            $xml[] = '        <ram:Name>' . self::xmlEsc($ln['title']) . '</ram:Name>';
            $xml[] = '      </ram:SpecifiedTradeProduct>';
            $xml[] = '      <ram:SpecifiedLineTradeAgreement><ram:NetPriceProductTradePrice><ram:ChargeAmount>' . self::fmtMoney($ln['net_unit']) . '</ram:ChargeAmount></ram:NetPriceProductTradePrice></ram:SpecifiedLineTradeAgreement>';
            $xml[] = '      <ram:SpecifiedLineTradeDelivery><ram:BilledQuantity unitCode="C62">' . number_format($ln['qty'], 2, '.', '') . '</ram:BilledQuantity></ram:SpecifiedLineTradeDelivery>';
            $xml[] = '      <ram:SpecifiedLineTradeSettlement>';
            $xml[] = '        <ram:ApplicableTradeTax>';
            $xml[] = '          <ram:TypeCode>VAT</ram:TypeCode>';
            $xml[] = '          <ram:CategoryCode>' . $ln['tax_cat_code'] . '</ram:CategoryCode>';
            $xml[] = '          <ram:RateApplicablePercent>' . number_format($ln['tax_rate'], 2, '.', '') . '</ram:RateApplicablePercent>';
            $xml[] = '        </ram:ApplicableTradeTax>';
            $xml[] = '        <ram:SpecifiedTradeSettlementLineMonetarySummation><ram:LineTotalAmount>' . self::fmtMoney($ln['net_total']) . '</ram:LineTotalAmount></ram:SpecifiedTradeSettlementLineMonetarySummation>';
            $xml[] = '      </ram:SpecifiedLineTradeSettlement>';
            $xml[] = '    </ram:IncludedSupplyChainTradeLineItem>';
        }

        $xml[] = '    <ram:ApplicableHeaderTradeAgreement>';
        $xml[] = '      <ram:BuyerReference>' . self::xmlEsc($buyerRef) . '</ram:BuyerReference>';
        $xml[] = '      <ram:SellerTradeParty>';
        $xml[] = '        <ram:Name>' . self::xmlEsc((string)($seller['name'] ?? '')) . '</ram:Name>';
        if (!empty($seller['contact_name']) || !empty($seller['email'])) {
            $xml[] = '        <ram:DefinedTradeContact>';
            if (!empty($seller['contact_name'])) {
                $xml[] = '          <ram:PersonName>' . self::xmlEsc((string)$seller['contact_name']) . '</ram:PersonName>';
            }
            if (!empty($seller['email'])) {
                $xml[] = '          <ram:EmailURIUniversalCommunication><ram:URIID>' . self::xmlEsc((string)$seller['email']) . '</ram:URIID></ram:EmailURIUniversalCommunication>';
            }
            $xml[] = '        </ram:DefinedTradeContact>';
        }
        $xml[] = '        <ram:PostalTradeAddress>';
        $xml[] = '          <ram:PostcodeCode>' . self::xmlEsc((string)($seller['zip'] ?? '')) . '</ram:PostcodeCode>';
        $xml[] = '          <ram:LineOne>' . self::xmlEsc((string)($seller['street'] ?? '')) . '</ram:LineOne>';
        $xml[] = '          <ram:CityName>' . self::xmlEsc((string)($seller['city'] ?? '')) . '</ram:CityName>';
        $xml[] = '          <ram:CountryID>' . self::xmlEsc($sellerCountry) . '</ram:CountryID>';
        $xml[] = '        </ram:PostalTradeAddress>';
        if (!empty($seller['email'])) {
            $xml[] = '        <ram:URIUniversalCommunication><ram:URIID schemeID="EM">' . self::xmlEsc((string)$seller['email']) . '</ram:URIID></ram:URIUniversalCommunication>';
        }
        if (!empty($seller['tax_number'])) {
            $xml[] = '        <ram:SpecifiedTaxRegistration><ram:ID schemeID="FC">' . self::xmlEsc((string)$seller['tax_number']) . '</ram:ID></ram:SpecifiedTaxRegistration>';
        }
        if (!empty($seller['vat_id'])) {
            $xml[] = '        <ram:SpecifiedTaxRegistration><ram:ID schemeID="VA">' . self::xmlEsc((string)$seller['vat_id']) . '</ram:ID></ram:SpecifiedTaxRegistration>';
        }
        $xml[] = '      </ram:SellerTradeParty>';

        $xml[] = '      <ram:BuyerTradeParty>';
        $xml[] = '        <ram:Name>' . self::xmlEsc($buyerName) . '</ram:Name>';
        $xml[] = '        <ram:PostalTradeAddress>';
        $xml[] = '          <ram:PostcodeCode>' . self::xmlEsc((string)($buyer['zip'] ?? '')) . '</ram:PostcodeCode>';
        $xml[] = '          <ram:LineOne>' . self::xmlEsc((string)($buyer['street'] ?? '')) . '</ram:LineOne>';
        $xml[] = '          <ram:CityName>' . self::xmlEsc((string)($buyer['city'] ?? '')) . '</ram:CityName>';
        $xml[] = '          <ram:CountryID>' . self::xmlEsc($buyerCountry) . '</ram:CountryID>';
        $xml[] = '        </ram:PostalTradeAddress>';
        if (!empty($buyer['email'])) {
            $xml[] = '        <ram:URIUniversalCommunication><ram:URIID schemeID="EM">' . self::xmlEsc((string)$buyer['email']) . '</ram:URIID></ram:URIUniversalCommunication>';
        }
        if (!empty($buyer['vat_id'])) {
            $xml[] = '        <ram:SpecifiedTaxRegistration><ram:ID schemeID="VA">' . self::xmlEsc((string)$buyer['vat_id']) . '</ram:ID></ram:SpecifiedTaxRegistration>';
        }
        $xml[] = '      </ram:BuyerTradeParty>';
        $xml[] = '    </ram:ApplicableHeaderTradeAgreement>';

        $xml[] = '    <ram:ApplicableHeaderTradeDelivery>';
        $xml[] = '      <ram:ActualDeliverySupplyChainEvent><ram:OccurrenceDateTime><udt:DateTimeString format="102">' . $delivery102 . '</udt:DateTimeString></ram:OccurrenceDateTime></ram:ActualDeliverySupplyChainEvent>';
        $xml[] = '    </ram:ApplicableHeaderTradeDelivery>';

        $xml[] = '    <ram:ApplicableHeaderTradeSettlement>';
        if (!empty($invoiceData['order_id'])) {
            $xml[] = '      <ram:PaymentReference>' . self::xmlEsc((string)$invoiceData['order_id']) . '</ram:PaymentReference>';
        }
        $xml[] = '      <ram:InvoiceCurrencyCode>' . self::xmlEsc($currency) . '</ram:InvoiceCurrencyCode>';

        if (!empty($seller['iban'])) {
            $xml[] = '      <ram:SpecifiedTradeSettlementPaymentMeans>';
            $xml[] = '        <ram:TypeCode>58</ram:TypeCode>';
            $xml[] = '        <ram:PayeePartyCreditorFinancialAccount>';
            $xml[] = '          <ram:IBANID>' . self::xmlEsc(preg_replace('/\s+/', '', (string)$seller['iban'])) . '</ram:IBANID>';
            $xml[] = '          <ram:AccountName>' . self::xmlEsc((string)($seller['name'] ?? '')) . '</ram:AccountName>';
            $xml[] = '        </ram:PayeePartyCreditorFinancialAccount>';
            if (!empty($seller['bic'])) {
                $xml[] = '        <ram:PayeeSpecifiedCreditorFinancialInstitution><ram:BICID>' . self::xmlEsc(preg_replace('/\s+/', '', (string)$seller['bic'])) . '</ram:BICID></ram:PayeeSpecifiedCreditorFinancialInstitution>';
            }
            $xml[] = '      </ram:SpecifiedTradeSettlementPaymentMeans>';
        }

        foreach ($breakdown['tax_groups'] as $tg) {
            $xml[] = '      <ram:ApplicableTradeTax>';
            $xml[] = '        <ram:CalculatedAmount>' . self::fmtMoney($tg['tax_amount']) . '</ram:CalculatedAmount>';
            $xml[] = '        <ram:TypeCode>VAT</ram:TypeCode>';
            if ($tg['category_code'] === 'E') {
                $xml[] = '        <ram:ExemptionReason>Kein Ausweis von Umsatzsteuer gemäß § 19 UStG (Kleinunternehmer).</ram:ExemptionReason>';
            }
            $xml[] = '        <ram:BasisAmount>' . self::fmtMoney($tg['basis_amount']) . '</ram:BasisAmount>';
            $xml[] = '        <ram:CategoryCode>' . $tg['category_code'] . '</ram:CategoryCode>';
            $xml[] = '        <ram:RateApplicablePercent>' . number_format($tg['rate'], 2, '.', '') . '</ram:RateApplicablePercent>';
            $xml[] = '      </ram:ApplicableTradeTax>';
        }

        if (!empty($invoiceData['reference_invoice_number'])) {
            $xml[] = '      <ram:InvoiceReferencedDocument><ram:IssuerAssignedID>' . self::xmlEsc((string)$invoiceData['reference_invoice_number']) . '</ram:IssuerAssignedID></ram:InvoiceReferencedDocument>';
        }

        $xml[] = '      <ram:SpecifiedTradePaymentTerms><ram:Description>' . self::xmlEsc((string)($invoiceData['payment_terms'] ?? 'Zahlbar sofort nach Erhalt ohne Abzug.')) . '</ram:Description></ram:SpecifiedTradePaymentTerms>';
        $xml[] = '      <ram:SpecifiedTradeSettlementHeaderMonetarySummation>';
        $xml[] = '        <ram:LineTotalAmount>' . self::fmtMoney($breakdown['net_total']) . '</ram:LineTotalAmount>';
        $xml[] = '        <ram:TaxBasisTotalAmount>' . self::fmtMoney($breakdown['net_total']) . '</ram:TaxBasisTotalAmount>';
        $xml[] = '        <ram:TaxTotalAmount currencyID="' . self::xmlEsc($currency) . '">' . self::fmtMoney($breakdown['tax_total']) . '</ram:TaxTotalAmount>';
        $xml[] = '        <ram:GrandTotalAmount>' . self::fmtMoney($breakdown['gross_total']) . '</ram:GrandTotalAmount>';
        $xml[] = '        <ram:DuePayableAmount>' . self::fmtMoney($breakdown['gross_total']) . '</ram:DuePayableAmount>';
        $xml[] = '      </ram:SpecifiedTradeSettlementHeaderMonetarySummation>';
        $xml[] = '    </ram:ApplicableHeaderTradeSettlement>';
        $xml[] = '  </rsm:SupplyChainTradeTransaction>';
        $xml[] = '</rsm:CrossIndustryInvoice>';

        return implode("\n", $xml) . "\n";
    }

    public static function validateXmlWellFormed(string $xml): bool {
        if (!class_exists('DOMDocument')) {
            return strpos($xml, '<rsm:CrossIndustryInvoice') !== false;
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $ok && $dom->documentElement && $dom->documentElement->localName === 'CrossIndustryInvoice';
    }
}
