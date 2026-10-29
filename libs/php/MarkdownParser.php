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
     * Wickelt in einem gemischten Block (der mit einer Überschrift, einem Trenner oder einem
     * anderen Block-Element beginnt) nachfolgende reine Textzeilen und Inline-Elemente
     * in Absätze. Block-Elemente (Listen, Tabellen, Divs, Trennlinien, weitere Überschriften)
     * bleiben dabei als eigenständige Blöcke erhalten.
     * @param string $block
     * @return string
     */
    private static function wrapMixedBlock(string $block): string {
        $out = [];
        $buffer = [];
        $flush = function() use (&$out, &$buffer) {
            if (!empty($buffer)) {
                $out[] = '<p style="margin-bottom: 1rem; line-height: 1.7;">' . implode('<br />' . "\n", $buffer) . '</p>';
                $buffer = [];
            }
        };
        foreach (explode("\n", $block) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/^(<\/?(h[1-6]|hr|ul|ol|li|table|div|blockquote|pre)\b|%%%WEAVE_BLOCK_HTML)/', $trimmed)) {
                $flush();
                $out[] = $trimmed;
            } else {
                $buffer[] = $trimmed;
            }
        }
        $flush();
        return implode("\n", $out);
    }

    /**
     * Wandelt Box-Shortcodes in die bestehenden Box-Klassen um (Phase 27.2):
     * [box style="soft_primary"]...[/box] oder [box color="#e0f2fe"]...[/box]
     * Der Boxinhalt bleibt reguläres Markdown. Verschachtelte Boxen werden nicht unterstützt.
     * @param string $text
     * @return string
     */
    private static function parseBoxShortcodes(string $text): string {
        if (stripos($text, '[box') === false) {
            return $text;
        }
        $styleMap = [
            'soft_primary' => 'box-soft box-soft-primary',
            'soft_success' => 'box-soft box-soft-success',
            'soft_amber'   => 'box-soft box-soft-amber',
            'soft_purple'  => 'box-soft box-soft-purple',
            'gradient'     => 'box-card box-gradient-primary',
            'dark'         => 'box-card box-dark',
        ];

        return preg_replace_callback('/\[box((?:\s+(?:style|color)\s*=\s*["\']?[^"\'\]\s]*["\']?)*)\s*\](.*?)\[\/box\s*\]/is', function($m) use ($styleMap) {
            $attrs = $m[1];
            $style = null;
            $color = null;
            if (preg_match('/style\s*=\s*["\']?([a-z_]+)["\']?/i', $attrs, $sm)) {
                $style = strtolower($sm[1]);
            }
            if (preg_match('/color\s*=\s*["\']?(#[0-9a-fA-F]{3,8})["\']?/', $attrs, $cm)) {
                $color = $cm[1];
            }

            if ($color !== null) {
                $open = '<div class="box-tintable" style="background: ' . $color . ';">';
            } else {
                $class = $styleMap[$style ?? ''] ?? $styleMap['soft_primary'];
                $open = '<div class="' . $class . '">';
            }
            return "\n\n" . $open . "\n\n" . $m[2] . "\n\n</div>\n\n";
        }, $text);
    }

    /**
     * Wandelt Spalten-Shortcodes in CSS-Grid-Markup um (Phase 27.1):
     * [grid layout="1-2"] [col]...[/col] [col]...[/col] [/grid]
     * Die Wrapper-Divs werden durch Leerzeilen vom Inhalt getrennt, sodass der Spalteninhalt
     * anschließend regulär als Markdown verarbeitet wird (inkl. Plugin-Shortcodes).
     * @param string $text
     * @return string
     */
    private static function parseGridShortcodes(string $text): string {
        if (stripos($text, '[grid') === false) {
            return $text;
        }
        $allowedLayouts = ['2', '3', '1-2', '2-1', 'img-text'];

        return preg_replace_callback('/\[grid(?:\s+layout\s*=\s*["\']?([a-z0-9-]+)["\']?)?\s*\](.*?)\[\/grid\s*\]/is', function($m) use ($allowedLayouts) {
            $layout = in_array($m[1], $allowedLayouts, true) ? $m[1] : '2';
            $inner = preg_replace('/\[col\s*\]/i', "\n\n<div class=\"weave-col\">\n\n", $m[2]);
            $inner = preg_replace('/\[\/col\s*\]/i', "\n\n</div>\n\n", $inner);
            return "\n\n<div class=\"weave-grid weave-grid-" . $layout . "\">\n\n" . $inner . "\n\n</div>\n\n";
        }, $text);
    }

    /**
     * Konvertiert Markdown in sicheren HTML-Code.
     * @param string|null $text
     * @return string
     */
    public static function parse(?string $text): string {
        if (empty($text)) {
            return '';
        }

        // 0. HTML-Kommentare entfernen (<!-- ... --> werden im Frontend ausgeblendet)
        $text = preg_replace('/<!--.*?-->/s', '', $text);

        // 0b. Spalten-Shortcodes [grid]/[col] in Grid-Markup umwandeln
        $text = self::parseGridShortcodes($text);

        // 0c. Box-Shortcodes [box] in Box-Markup umwandeln
        $text = self::parseBoxShortcodes($text);

        // 1. Sichere HTML-Tags extrahieren und durch typisierte Platzhalter schützen (Block vs. Inline)
        $htmlTokens = [];
        $tokenIndex = 0;
        
        $blockTags = 'div|p|hr|h[1-6]|table|thead|tbody|tfoot|tr|th|td|ul|ol|li|blockquote|pre|svg|path|symbol|use';
        $inlineTags = 'span|a|button|br|img|strong|b|em|i|u|s|small|code';
        
        $text = preg_replace_callback('/<(\/?(?:' . $blockTags . '|' . $inlineTags . ')\b[^>]*)>/is', function($matches) use (&$htmlTokens, &$tokenIndex, $blockTags) {
            $tag = $matches[0];
            $tagName = strtolower(preg_replace('/^<\/?([a-z0-9]+)\b.*/is', '$1', $tag));
            // XSS-Schutz: Keine Script-Events oder unsichere Protokolle
            if (preg_match('/\bon\w+\s*=/i', $tag) || preg_match('/(?:href|src)\s*=\s*["\']?\s*(?:javascript|vbscript|data:text\/html):/i', $tag)) {
                return htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
            }
            $isBlock = (bool)preg_match('/^(' . $blockTags . ')$/i', $tagName);
            $prefix = $isBlock ? '%%%WEAVE_BLOCK_HTML_' : '%%%WEAVE_INLINE_HTML_';
            $placeholder = "{$prefix}{$tokenIndex}%%%";
            $htmlTokens[$placeholder] = $tag;
            $tokenIndex++;
            return $placeholder;
        }, $text);

        // 2. Verbleibenden Text gegen XSS maskieren (bestehende Entities wie &amp; nicht doppelt kodieren)
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        // 3. Tabellen verarbeiten (bevor Zeilen umgebrochen werden)
        $safe = self::parseMarkdownTables($safe);

        // 4. Überschriften
        $safe = preg_replace('/^### (.*?)$/m', '<h4 style="color:var(--text-primary); margin-top:1.4rem; margin-bottom:0.4rem; font-size:1.08rem; font-weight:700;">$1</h4>', $safe);
        $safe = preg_replace('/^## (.*?)$/m', '<h3 style="color:var(--primary); margin-top:1.6rem; margin-bottom:0.6rem; font-size:1.22rem; font-weight:700;">$1</h3>', $safe);
        $safe = preg_replace('/^# (.*?)$/m', '<h2 style="color:var(--primary); margin-top:1.8rem; margin-bottom:0.8rem; font-size:1.35rem; font-weight:800;">$1</h2>', $safe);

        // 5. Trennlinie
        $safe = preg_replace('/^---$/m', '<hr style="border:none; border-top:1px solid var(--border-color); margin:1.5rem 0; opacity:0.6;">', $safe);

        // 6. Markdown-Bilder: ![Alt](url)
        $safe = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"([^"]+)")?\)/', function($m) {
            $alt = $m[1];
            $url = $m[2];
            $title = !empty($m[3]) ? ' title="' . htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8') . '"' : '';
            return '<img src="' . $url . '" alt="' . $alt . '" class="img-fluid" style="max-width:100%; border-radius:12px; margin:1rem 0;"' . $title . '>';
        }, $safe);

        // 7. Markdown-Links: [Text](url)
        $safe = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]+)")?\)/', function($m) {
            $label = $m[1];
            $url = $m[2];
            $title = !empty($m[3]) ? ' title="' . htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8') . '"' : '';
            $isExternal = (bool)preg_match('~^https?://~i', $url);
            $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $url . '"' . $target . $title . ' style="color:var(--primary); font-weight:600; text-decoration:underline;">' . $label . '</a>';
        }, $safe);

        // 8. Formatierungen: Fett, Kursiv & Code
        $safe = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $safe);
        $safe = preg_replace('/`([^`]+)`/', '<code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.9em;">$1</code>', $safe);

        // 9. Listen verarbeiten
        $lines = explode("\n", $safe);
        $inList = false;
        $out = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (strpos($trimmed, '- ') === 0 || strpos($trimmed, '* ') === 0) {
                if (!$inList) {
                    $out[] = '<ul style="margin: 0.5rem 0 1.25rem 1.25rem; padding-left: 0.5rem; line-height: 1.7;">';
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

        // 10. Absätze erzeugen (Block-Elemente nicht fälschlich in <p> packen, Mischblöcke sauber trennen)
        $paragraphs = preg_split('/\n\s*\n/', $safe);
        $rendered = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            $hasNewline = (strpos($p, "\n") !== false);
            $startsWithBlock = (bool)preg_match('/^(<(h[1-6]|hr|ul|ol|table|div|blockquote|pre)\b|%%%WEAVE_BLOCK_HTML)/', $p);

            if ($hasNewline && ($startsWithBlock || preg_match('/^<h[1-6]\b/', $p))) {
                // Block-Element mit folgendem Text im selben Absatz: Fließtextzeilen in <p> wickeln
                $rendered[] = self::wrapMixedBlock($p);
            } elseif ($startsWithBlock) {
                $rendered[] = $p;
            } else {
                $rendered[] = '<p style="margin-bottom: 1rem; line-height: 1.7;">' . nl2br($p) . '</p>';
            }
        }
        $safe = implode("\n", $rendered);

        // 11. Geschützte HTML-Tags wiederherstellen
        if (!empty($htmlTokens)) {
            $safe = str_replace(array_keys($htmlTokens), array_values($htmlTokens), $safe);
        }

        return $safe;
    }

    /**
     * Erkennt und konvertiert Markdown-Tabellen in valides, responsives HTML.
     * @param string $text
     * @return string
     */
    private static function parseMarkdownTables(string $text): string {
        $lines = explode("\n", $text);
        $inTable = false;
        $tableLines = [];
        $result = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && str_contains($trimmed, '|') && preg_match('/^\|?.*\|.*\|?$/', $trimmed)) {
                $tableLines[] = $trimmed;
                $inTable = true;
            } else {
                if ($inTable) {
                    $result[] = self::renderTableHtml($tableLines);
                    $tableLines = [];
                    $inTable = false;
                }
                $result[] = $line;
            }
        }
        if ($inTable) {
            $result[] = self::renderTableHtml($tableLines);
        }

        return implode("\n", $result);
    }

    /**
     * Erzeugt das HTML-Markup für eine Markdown-Tabelle.
     * @param array $lines
     * @return string
     */
    private static function renderTableHtml(array $lines): string {
        if (count($lines) < 2) {
            return implode("\n", $lines);
        }

        $headerLine = array_shift($lines);
        $separatorLine = array_shift($lines);

        if (!preg_match('/^\|?(\s*:?-+:?\s*\|)+\s*:?-+:?\s*\|?$/', $separatorLine)) {
            array_unshift($lines, $separatorLine);
            array_unshift($lines, $headerLine);
            return implode("\n", $lines);
        }

        $alignments = [];
        $sepCols = explode('|', trim($separatorLine, '|'));
        foreach ($sepCols as $col) {
            $col = trim($col);
            $left = str_starts_with($col, ':');
            $right = str_ends_with($col, ':');
            if ($left && $right) {
                $alignments[] = 'center';
            } elseif ($right) {
                $alignments[] = 'right';
            } else {
                $alignments[] = 'left';
            }
        }

        $headerCols = explode('|', trim($headerLine, '|'));
        $html = '<div class="table-responsive mb-4">';
        $html .= '<table class="table table-hover">';
        $html .= '<thead><tr>';
        foreach ($headerCols as $idx => $h) {
            $align = $alignments[$idx] ?? 'left';
            $html .= '<th style="text-align:' . $align . ';">' . trim($h) . '</th>';
        }
        $html .= '</tr></thead>';

        $html .= '<tbody>';
        foreach ($lines as $rowLine) {
            $rowCols = explode('|', trim($rowLine, '|'));
            $html .= '<tr>';
            foreach ($rowCols as $idx => $cell) {
                $align = $alignments[$idx] ?? 'left';
                $html .= '<td style="text-align:' . $align . ';">' . trim($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table></div>';

        return $html;
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
