<?php
/**
 * ============================================================================
 * WeaveCMS - Sicherer Markdown Parser (MarkdownParser)
 * ============================================================================
 * Konvertiert Markdown-Formatierungen in sicheren, validen HTML-Code für
 * Datenschutzerklärung, Einleitungstexte und Beschreibungen (XSS-geschützt).
 */

class MarkdownParser {

    /**
     * Konvertiert Markdown in sicheren HTML-Code.
     * @param string|null $text
     * @return string
     */
    public static function parse(?string $text): string {
        if (empty($text)) {
            return '';
        }

        // 1. HTML-Entities maskieren gegen XSS
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 2. Überschriften
        $safe = preg_replace('/^### (.*?)$/m', '<h4 style="color:var(--text-primary); margin-top:1.4rem; margin-bottom:0.4rem; font-size:1.08rem; font-weight:700;">$1</h4>', $safe);
        $safe = preg_replace('/^## (.*?)$/m', '<h3 style="color:var(--primary); margin-top:1.6rem; margin-bottom:0.6rem; font-size:1.22rem; font-weight:700;">$1</h3>', $safe);
        $safe = preg_replace('/^# (.*?)$/m', '<h2 style="color:var(--primary); margin-top:1.8rem; margin-bottom:0.8rem; font-size:1.35rem; font-weight:800;">$1</h2>', $safe);

        // 3. Trennlinie
        $safe = preg_replace('/^---$/m', '<hr style="border:none; border-top:1px solid var(--border-color); margin:1.25rem 0; opacity:0.6;">', $safe);

        // 4. Formatierungen: Fett & Kursiv
        $safe = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $safe);

        // 5. Listen verarbeiten
        $lines = explode("\n", $safe);
        $inList = false;
        $out = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (strpos($trimmed, '- ') === 0) {
                if (!$inList) {
                    $out[] = '<ul style="margin: 0.5rem 0 1rem 1.25rem; padding-left: 0.5rem; line-height: 1.6;">';
                    $inList = true;
                }
                $out[] = '<li>' . substr($trimmed, 2) . '</li>';
            } else {
                if ($inList) {
                    $out[] = '</ul>';
                    $inList = false;
                }
                $out[] = $line;
            }
        }
        if ($inList) {
            $out[] = '</ul>';
        }
        $safe = implode("\n", $out);

        // 6. Absätze erzeugen
        $paragraphs = preg_split('/\n\s*\n/', $safe);
        $rendered = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            if (preg_match('/^<(h[1-6]|hr|ul|div)/', $p)) {
                $rendered[] = $p;
            } else {
                $rendered[] = '<p style="margin-bottom: 0.85rem; line-height: 1.6;">' . nl2br($p) . '</p>';
            }
        }

        return implode("\n", $rendered);
    }

    /**
     * Erzeugt den abmahnsicheren DSGVO-Standardvorlagentext mit dynamischen Kontaktdaten.
     * @param array $data
     * @return string
     */
    public static function getDefaultPrivacyPolicyText(array $data = []): string {
        $name = !empty($data['texts']['contact_name']) ? $data['texts']['contact_name'] : 'Maxima Musterfrau';
        $street = !empty($data['texts']['contact_street']) ? $data['texts']['contact_street'] : 'Musterstraße 12';
        $city = !empty($data['texts']['contact_city']) ? $data['texts']['contact_city'] : '12345 Musterstadt';
        $email = !empty($data['texts']['contact_email']) ? $data['texts']['contact_email'] : 'kontakt@maxima-musterfrau.de';
        $phone = !empty($data['texts']['contact_phone']) ? $data['texts']['contact_phone'] : '';

        $contact_block = $name . "\n" . $street . "\n" . $city . "\nE-Mail: " . $email;
        if (!empty($phone)) {
            $contact_block .= "\nTelefon: " . $phone;
        }

        return "### 1. Datenschutz auf einen Blick

**Allgemeine Hinweise**
Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können. Ausführliche Informationen zum Thema Datenschutz entnehmen Sie unserer unter diesem Text aufgeführten Datenschutzerklärung.

**Verantwortliche Stelle für die Datenverarbeitung**
Die Datenverarbeitung auf dieser Website erfolgt durch:

" . $contact_block . "

Die verantwortliche Stelle entscheidet allein oder gemeinsam mit anderen über die Zwecke und Mittel der Verarbeitung von personenbezogenen Daten.

---

### 2. Datenerfassung auf dieser Website

**Server-Log-Dateien**
Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, die Ihr Browser automatisch an uns übermittelt. Dies sind:
- Browsertyp und Browserversion
- Verwendetes Betriebssystem
- Referrer URL (die zuvor besuchte Seite)
- Hostname des zugreifenden Rechners
- Uhrzeit der Serveranfrage
- IP-Adresse (in anonymisierter Form)

Eine Zusammenführung dieser Daten mit anderen Datenquellen wird nicht vorgenommen. Die Erfassung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Der Websitebetreiber hat ein berechtigtes Interesse an der technisch fehlerfreien Darstellung und der Optimierung seiner Website.

**Kontaktaufnahme per Kontaktformular oder E-Mail**
Wenn Sie uns per Kontaktformular oder E-Mail Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten (wie Name, E-Mail-Adresse, Telefonnummer und Ihre Nachricht) zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert. Diese Daten geben wir nicht ohne Ihre Einwilligung weiter.

Die Verarbeitung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO, sofern Ihre Anfrage mit der Erfüllung eines Vertrags zusammenhängt oder zur Durchführung vorvertraglicher Maßnahmen erforderlich ist. In allen übrigen Fällen beruht die Verarbeitung auf unserem berechtigten Interesse an der effektiven Bearbeitung der an uns gerichteten Anfragen (Art. 6 Abs. 1 lit. f DSGVO) oder auf Ihrer Einwilligung (Art. 6 Abs. 1 lit. a DSGVO), sofern diese abgefragt wurde.

**Speicherdauer**
Die von Ihnen im Kontaktformular eingegebenen Daten verbleiben bei uns, bis Sie uns zur Löschung auffordern, Ihre Einwilligung zur Speicherung widerrufen oder der Zweck für die Datenspeicherung entfällt (z. B. nach abgeschlossener Bearbeitung Ihrer Anfrage). Zwingende gesetzliche Bestimmungen – insbesondere Aufbewahrungsfristen – bleiben unberührt.

---

### 3. Keine Tracking-Cookies & keine Analyse-Dienste

Diese Website verwendet **keine Tracking-Cookies, keine Profiling-Mechanismen und keine externen Webanalyse-Dienste** (wie z. B. Google Analytics oder Meta Pixel). 

Im Administrationsbereich werden ausschließlich technisch notwendige Session-Cookies zur Authentifizierung eingesetzt. Es werden zu keinem Zeitpunkt Werbeprofile erstellt oder Ihr Surfverhalten an Dritte weitergegeben.

---

### 4. Ihre Rechte als betroffene Person

Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen (DSGVO) jederzeit folgende Rechte:
- **Recht auf Auskunft:** Sie können jederzeit unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten verlangen (Art. 15 DSGVO).
- **Recht auf Berichtigung:** Sie haben das Recht auf Berichtigung unrichtiger oder Vervollständigung unvollständiger personenbezogener Daten (Art. 16 DSGVO).
- **Recht auf Löschung:** Sie haben das Recht, die unverzügliche Löschung Ihrer bei uns gespeicherten personenbezogenen Daten zu verlangen (Art. 17 DSGVO).
- **Recht auf Einschränkung der Verarbeitung:** Unter bestimmten Voraussetzungen können Sie die Einschränkung der Verarbeitung Ihrer Daten verlangen (Art. 18 DSGVO).
- **Recht auf Datenübertragbarkeit:** Sie haben das Recht, Daten, die wir automatisiert verarbeiten, in einem gängigen, maschinenlesbaren Format an sich oder einen Dritten aushändigen zu lassen (Art. 20 DSGVO).
- **Widerrufsrecht bei Einwilligungen:** Bereits erteilte Einwilligungen zur Datenverarbeitung können Sie jederzeit für die Zukunft widerrufen (Art. 7 Abs. 3 DSGVO).
- **Widerspruchsrecht:** Wenn die Datenverarbeitung auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO erfolgt, haben Sie das Recht, aus Gründen, die sich aus Ihrer besonderen Situation ergeben, Widerspruch einzulegen (Art. 21 DSGVO).

**Beschwerderecht bei der zuständigen Aufsichtsbehörde**
Im Falle von Verstößen gegen die DSGVO steht den Betroffenen ein Beschwerderecht bei einer Aufsichtsbehörde, insbesondere in dem Mitgliedstaat ihres gewöhnlichen Aufenthalts, ihres Arbeitsplatzes oder des Orts des mutmaßlichen Verstoßes zu (Art. 77 DSGVO).";
    }
}
