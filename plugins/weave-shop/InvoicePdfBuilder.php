<?php
/**
 * ============================================================================
 * WeaveCMS Shop – GoBD-konformer PDF & PDF/A-3B Generator (ZUGFeRD)
 * ============================================================================
 * Autarker PHP PDF 1.7 / PDF/A-3B Generator ohne externe Abhängigkeiten.
 * Bettet bei ZUGFeRD optional `factur-x.xml` als EmbeddedFile + /AF + XMP ein.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class InvoicePdfBuilder {

    protected static function encodeWinAnsi(string $text): string {
        $text = str_replace('€', 'EUR', $text);
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($conv !== false) {
                return $conv;
            }
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    protected static function pdfStr(string $text): string {
        $win = self::encodeWinAnsi($text);
        $win = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $win);
        return '(' . $win . ')';
    }

    /**
     * Erzeugt das fertige PDF-Dokument (optional inkl. eingebettetem ZUGFeRD factur-x.xml für PDF/A-3).
     */
    public static function buildPdf(array $invoiceData, ?string $embeddedXml = null): string {
        $breakdown = InvoiceXmlBuilder::calculateBreakdown($invoiceData);
        $isCredit = (($invoiceData['doc_type'] ?? 'invoice') === 'credit_note');
        $isSmallBusiness = !empty($invoiceData['small_business']);
        $seller = (array)($invoiceData['seller'] ?? []);
        $buyer = (array)($invoiceData['buyer'] ?? []);
        $currency = (string)($invoiceData['currency'] ?? 'EUR');

        $issueDate = date('d.m.Y', strtotime((string)($invoiceData['issued_at'] ?? 'now')) ?: time());
        $deliveryDate = date('d.m.Y', strtotime((string)($invoiceData['delivery_date'] ?? ($invoiceData['issued_at'] ?? 'now'))) ?: time());

        $streamLines = [];
        $addText = static function (float $x, float $y, string $text, int $size = 10, bool $bold = false) use (&$streamLines): void {
            $font = $bold ? 'F2' : 'F1';
            $streamLines[] = "BT /{$font} {$size} Tf " . number_format($x, 2, '.', '') . " " . number_format($y, 2, '.', '') . " Td " . self::pdfStr($text) . " Tj ET";
        };
        $addLine = static function (float $x1, float $y1, float $x2, float $y2) use (&$streamLines): void {
            $streamLines[] = "0.6 w " . number_format($x1, 2, '.', '') . " " . number_format($y1, 2, '.', '') . " m " . number_format($x2, 2, '.', '') . " " . number_format($y2, 2, '.', '') . " l S";
        };

        // Briefkopf & Verkäufer
        $addText(50, 785, (string)($seller['name'] ?? 'WeaveCMS Shop'), 16, true);
        $addText(50, 768, trim(($seller['street'] ?? '') . ' | ' . ($seller['zip'] ?? '') . ' ' . ($seller['city'] ?? '')), 9);

        // Absenderzeile + Empfänger
        $addText(50, 725, trim(($seller['name'] ?? '') . ' - ' . ($seller['street'] ?? '') . ' - ' . ($seller['zip'] ?? '') . ' ' . ($seller['city'] ?? '')), 7);
        $yBuyer = 708;
        if (!empty($buyer['company'])) {
            $addText(50, $yBuyer, (string)$buyer['company'], 10, true);
            $yBuyer -= 13;
        }
        $addText(50, $yBuyer, (string)($buyer['name'] ?? ''), 10);
        $yBuyer -= 13;
        $addText(50, $yBuyer, (string)($buyer['street'] ?? ''), 10);
        $yBuyer -= 13;
        $addText(50, $yBuyer, trim(($buyer['zip'] ?? '') . ' ' . ($buyer['city'] ?? '')), 10);
        $yBuyer -= 13;
        if (!empty($buyer['vat_id'])) {
            $addText(50, $yBuyer, 'USt-IdNr.: ' . $buyer['vat_id'], 8);
            $yBuyer -= 11;
        }
        if (!empty($buyer['leitweg_id'])) {
            $addText(50, $yBuyer, 'Leitweg-ID: ' . $buyer['leitweg_id'], 8);
        }

        // Rechte Infospalte
        $addText(360, 725, ($isCredit ? 'Gutschrift / Storno-Nr.: ' : 'Rechnungsnummer: ') . (string)$invoiceData['invoice_number'], 10, true);
        $addText(360, 710, 'Rechnungsdatum: ' . $issueDate, 9);
        $addText(360, 696, 'Leistungsdatum: ' . $deliveryDate, 9);
        $addText(360, 682, 'Bestellnummer: ' . (string)($invoiceData['order_id'] ?? '-'), 9);
        if (!empty($invoiceData['reference_invoice_number'])) {
            $addText(360, 668, 'Zu Rechnung: ' . (string)$invoiceData['reference_invoice_number'], 9);
        }

        // Titel
        $docTitle = $isCredit
            ? ('Stornorechnung / Gutschrift ' . $invoiceData['invoice_number'])
            : ('Rechnung ' . $invoiceData['invoice_number']);
        $addText(50, 620, $docTitle, 14, true);

        // Positionstabelle Kopf
        $y = 592;
        $addLine(50, $y + 12, 545, $y + 12);
        $addText(50, $y, 'Pos.', 9, true);
        $addText(85, $y, 'Artikel / Leistung', 9, true);
        $addText(315, $y, 'Menge', 9, true);
        $addText(365, $y, 'MwSt.', 9, true);
        $addText(415, $y, 'Netto', 9, true);
        $addText(485, $y, 'Brutto', 9, true);
        $addLine(50, $y - 6, 545, $y - 6);
        $y -= 22;

        foreach (array_slice($breakdown['lines'], 0, 22) as $ln) {
            $addText(50, $y, $ln['line_id'], 9);
            $addText(85, $y, mb_substr($ln['title'], 0, 42), 9);
            $addText(320, $y, (string)$ln['qty'], 9);
            $addText(365, $y, number_format($ln['tax_rate'], 0) . ' %', 9);
            $addText(415, $y, number_format($ln['net_total'], 2, ',', '.') . ' ' . $currency, 9);
            $addText(485, $y, number_format($ln['gross_total'], 2, ',', '.') . ' ' . $currency, 9);
            $y -= 16;
        }
        if (count($breakdown['lines']) > 22) {
            $addText(50, $y, '... und ' . (count($breakdown['lines']) - 22) . ' weitere Positionen (siehe E-Rechnungs-XML).', 8);
            $y -= 14;
        }

        $addLine(50, $y + 4, 545, $y + 4);
        $y -= 18;

        // Summenblock
        $addText(350, $y, 'Nettobetrag Gesamt:', 10);
        $addText(485, $y, number_format($breakdown['net_total'], 2, ',', '.') . ' ' . $currency, 10);
        $y -= 15;

        if ($isSmallBusiness) {
            $addText(50, $y, 'Hinweis: Kein Ausweis der Umsatzsteuer gemäß § 19 UStG (Kleinunternehmer).', 9);
            $y -= 15;
        } else {
            foreach ($breakdown['tax_groups'] as $tg) {
                $addText(350, $y, 'zzgl. MwSt. ' . number_format($tg['rate'], 0) . '%:', 10);
                $addText(485, $y, number_format($tg['tax_amount'], 2, ',', '.') . ' ' . $currency, 10);
                $y -= 15;
            }
        }

        $addLine(345, $y + 6, 545, $y + 6);
        $y -= 10;
        $addText(350, $y, 'Gesamtbetrag:', 11, true);
        $addText(485, $y, number_format($breakdown['gross_total'], 2, ',', '.') . ' ' . $currency, 11, true);

        // Fußbereich (§ 14 UStG Pflichtangaben & Bankdaten)
        $addLine(50, 110, 545, 110);
        $addText(50, 95, (string)($invoiceData['payment_terms'] ?? 'Zahlbar sofort nach Erhalt ohne Abzug.'), 8);
        $bankInfo = trim(($seller['bank_name'] ?? '') . ' | IBAN: ' . ($seller['iban'] ?? '-') . ' | BIC: ' . ($seller['bic'] ?? '-'));
        $addText(50, 82, 'Bankverbindung: ' . $bankInfo, 8);
        $taxInfo = trim('Steuernr.: ' . ($seller['tax_number'] ?? '-') . ' | USt-IdNr.: ' . ($seller['vat_id'] ?? '-') . ' | ' . ($seller['register'] ?? ''));
        $addText(50, 69, $taxInfo, 8);
        if ($embeddedXml !== null) {
            $addText(50, 56, 'Format: Hybride E-Rechnung nach EN 16931 (ZUGFeRD / Factur-X XML eingebettet).', 7);
        }

        $contentStream = implode("\n", $streamLines);

        // PDF-Objekte aufbauen
        $objects = [];
        $nextId = 1;
        $alloc = static function () use (&$nextId): int {
            return $nextId++;
        };

        $catId = $alloc();
        $pagesId = $alloc();
        $pageId = $alloc();
        $font1Id = $alloc();
        $font2Id = $alloc();
        $contentId = $alloc();

        $embedStreamId = null;
        $fileSpecId = null;
        $namesId = null;
        $xmpId = null;

        if ($embeddedXml !== null) {
            $embedStreamId = $alloc();
            $fileSpecId = $alloc();
            $namesId = $alloc();
            $xmpId = $alloc();
        }

        $catExtras = '';
        if ($embeddedXml !== null) {
            $catExtras = " /Names {$namesId} 0 R /Metadata {$xmpId} 0 R /AF [{$fileSpecId} 0 R]";
        }
        $objects[$catId] = "<< /Type /Catalog /Pages {$pagesId} 0 R{$catExtras} >>";
        $objects[$pagesId] = "<< /Type /Pages /Kids [{$pageId} 0 R] /Count 1 >>";
        $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$font1Id} 0 R /F2 {$font2Id} 0 R >> >> /Contents {$contentId} 0 R >>";
        $objects[$font1Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$font2Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[$contentId] = "<< /Length " . strlen($contentStream) . " >>\nstream\n{$contentStream}\nendstream";

        if ($embeddedXml !== null) {
            $xmlLen = strlen($embeddedXml);
            $objects[$embedStreamId] = "<< /Type /EmbeddedFile /Subtype /text#2Fxml /Params << /Size {$xmlLen} >> /Length {$xmlLen} >>\nstream\n{$embeddedXml}\nendstream";
            $objects[$fileSpecId] = "<< /Type /Filespec /F (factur-x.xml) /UF (factur-x.xml) /Desc (ZUGFeRD EN 16931 Invoice XML) /AFRelationship /Alternative /EF << /F {$embedStreamId} 0 R /UF {$embedStreamId} 0 R >> >>";
            $objects[$namesId] = "<< /EmbeddedFiles << /Names [(factur-x.xml) {$fileSpecId} 0 R] >> >>";

            $xmp = '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>'
                . '<x:xmpmeta xmlns:x="adobe:ns:meta/">'
                . '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
                . '<rdf:Description rdf:about="" xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" pdfaid:part="3" pdfaid:conformance="B"/>'
                . '<rdf:Description rdf:about="" xmlns:fx="urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#" fx:DocumentType="INVOICE" fx:DocumentFileName="factur-x.xml" fx:Version="1.0" fx:ConformanceLevel="EN 16931"/>'
                . '</rdf:RDF></x:xmpmeta><?xpacket end="w"?>';
            $objects[$xmpId] = "<< /Type /Metadata /Subtype /XML /Length " . strlen($xmp) . " >>\nstream\n{$xmp}\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root {$catId} 0 R >>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
