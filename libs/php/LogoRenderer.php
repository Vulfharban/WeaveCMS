<?php
/**
 * ============================================================================
 * WeaveCMS - Logo & Branding Renderer (LogoRenderer)
 * ============================================================================
 * Erzeugt dynamische SVG-Hundetatzen-Motive in konfigurierbarer Farbe,
 * Größe und Position (Header oder Hero-Zeile).
 */

class LogoRenderer {

    /**
     * Gibt alle verfügbaren Logo-Motive zurück.
     * @return array
     */
    public static function getAllMotifs(): array {
        return [
            'paw_simple' => 'Klassische Hundetatze',
            'paw_beach' => 'Hundetatze & Naturzweig',
            'paw_willow' => 'Hundetatze & Eleganter Weidenzweig',
            'none' => 'Kein Logo'
        ];
    }

    /**
     * Alias für renderPawLogo.
     */
    public static function render(string $motif = 'paw_simple', string $color = '#b26c39', $size = '1.15em', string $extraClass = ''): string {
        return self::renderPawLogo($motif, $color, $size, $extraClass);
    }

    /**
     * Rendert das anpassbare SVG-Hundetatzen-Logo für den Header oder die Hero-Zeile.
     * @param string $motif Motiv: 'paw_simple', 'paw_beach', 'paw_willow', 'none'
     * @param string $color Hex-Farbcode (z. B. '#b26c39')
     * @param string|int $size CSS-Größenangabe (z. B. '1.15em', '1.2em' oder 48)
     * @param string $extraClass Zusätzliche CSS-Klassen
     * @return string Generierter HTML/SVG-Code
     */
    public static function renderPawLogo(string $motif = 'paw_simple', string $color = '#b26c39', $size = '1.15em', string $extraClass = ''): string {
        if ($motif === 'none') {
            return '';
        }

        $cleanColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $color)) {
            $cleanColor = '#b26c39';
        }

        $sizeVal = is_numeric($size) ? ($size . 'px') : $size;
        $cleanSize = htmlspecialchars($sizeVal, ENT_QUOTES, 'UTF-8');
        $sizeCss = 'width:' . $cleanSize . '; height:' . $cleanSize . ';';

        $svg = '<span class="logo-paw-wrapper ' . htmlspecialchars($extraClass) . '" style="color:' . $cleanColor . '; ' . $sizeCss . '">';
        $svg .= '<svg viewBox="0 0 100 100" class="paw-logo-svg" role="img" aria-label="Logo Hundetatze" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%; display:block;">';

        if ($motif === 'paw_simple') {
            // Klassische Hundetatze
            $svg .= '<path class="paw-pad" d="M 50,44 C 38,44 32,59 40,68 C 45,72 55,72 60,68 C 68,59 62,44 50,44 Z" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="40" rx="6.5" ry="9" transform="rotate(-25 28 40)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="27" rx="6.5" ry="9" transform="rotate(-8 42 27)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="27" rx="6.5" ry="9" transform="rotate(8 58 27)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="40" rx="6.5" ry="9" transform="rotate(25 72 40)" fill="currentColor"/>';
        } elseif ($motif === 'paw_beach') {
            // Hundetatze & Naturzweig
            $svg .= '<path class="paw-pad" d="M 50,38 C 38,38 32,53 40,62 C 45,66 55,66 60,62 C 68,53 62,38 50,38 Z" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="35" rx="6.5" ry="9" transform="rotate(-25 28 35)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="23" rx="6.5" ry="9" transform="rotate(-8 42 23)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="23" rx="6.5" ry="9" transform="rotate(8 58 23)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="35" rx="6.5" ry="9" transform="rotate(25 72 35)" fill="currentColor"/>';
            $svg .= '<path d="M 14,80 Q 50,71 86,79" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>';
            $svg .= '<path d="M 26,78 Q 28,72 34,75 Q 31,79 26,78 Z" fill="currentColor"/>';
            $svg .= '<path d="M 43,74 Q 47,68 52,71 Q 48,76 43,74 Z" fill="currentColor"/>';
            $svg .= '<path d="M 61,74 Q 65,68 70,71 Q 66,76 61,74 Z" fill="currentColor"/>';
            $svg .= '<path d="M 77,78 Q 81,72 86,75 Q 82,79 77,78 Z" fill="currentColor"/>';
        } elseif ($motif === 'paw_willow') {
            // Hundetatze & Eleganter Weidenzweig
            $svg .= '<path class="paw-pad" d="M 50,36 C 38,36 32,51 40,60 C 45,64 55,64 60,60 C 68,51 62,36 50,36 Z" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="33" rx="6.5" ry="9" transform="rotate(-25 28 33)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="21" rx="6.5" ry="9" transform="rotate(-8 42 21)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="21" rx="6.5" ry="9" transform="rotate(8 58 21)" fill="currentColor"/>';
            $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="33" rx="6.5" ry="9" transform="rotate(25 72 33)" fill="currentColor"/>';
            $svg .= '<path d="M 10,81 C 28,68 44,73 50,72 C 58,70 72,73 90,81" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>';
            $svg .= '<path d="M 21,75 C 19,66 27,64 34,72 C 28,73 24,74 21,75 Z" fill="currentColor"/>';
            $svg .= '<path d="M 36,71 C 37,60 47,61 49,69 C 43,69 38,70 36,71 Z" fill="currentColor"/>';
            $svg .= '<path d="M 64,69 C 66,61 76,60 77,71 C 72,70 67,69 64,69 Z" fill="currentColor"/>';
            $svg .= '<path d="M 79,72 C 86,64 94,66 92,75 C 88,74 84,73 79,72 Z" fill="currentColor"/>';
        }

        $svg .= '</svg>';
        $svg .= '</span>';

        return $svg;
    }
}
