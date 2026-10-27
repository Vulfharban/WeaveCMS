<?php
/**
 * ============================================================================
 * WeaveCMS Language & Internationalization Manager (LanguageManager)
 * ============================================================================
 * Verwaltet Mehrsprachigkeit (i18n), unterstützte Sprachkataloge für West- und
 * Südeuropa sowie Skandinavien, automatische Spracherkennung, Übersetzungsdateien
 * und mehrsprachige Inhaltstexte mit robuster Fallback-Kaskade.
 *
 * @package WeaveCMS\I18n
 * @author WeaveCMS Team
 * @license Proprietary
 */

class LanguageManager {

    /**
     * Cookie-Name für die gespeicherte Benutzersprache (Frontend).
     */
    public const COOKIE_NAME = 'weave_lang';

    /**
     * Cookie-Name für die gespeicherte Sprache im Administrationsbereich (Backend).
     */
    public const ADMIN_COOKIE_NAME = 'weave_admin_lang';

    /**
     * Standard- und Fallback-Sprache für das Backend (strikt Englisch).
     */
    public const DEFAULT_ADMIN_LANG = 'en';

    /**
     * Cache für bereits geladene Übersetzungsdateien (Frontend).
     * @var array<string, array>
     */
    protected static array $translationCache = [];

    /**
     * Cache für geladene Backend-Übersetzungen.
     * @var array<string, array>
     */
    protected static array $adminTranslationCache = [];

    /**
     * Vollständiger Katalog aller 12 unterstützten Sprachen (West- & Südeuropa + Skandinavien).
     *
     * @return array<string, array{name: string, native: string, locale: string, region: string, flag: string}>
     */
    public static function getSupportedLanguages(): array {
        return [
            'de' => [
                'name' => 'Deutsch',
                'native' => 'Deutsch',
                'locale' => 'de_DE',
                'region' => 'DACH',
                'flag' => '🇩🇪'
            ],
            'en' => [
                'name' => 'Englisch',
                'native' => 'English',
                'locale' => 'en_US',
                'region' => 'International',
                'flag' => '🇬🇧'
            ],
            'fr' => [
                'name' => 'Französisch',
                'native' => 'Français',
                'locale' => 'fr_FR',
                'region' => 'Westeuropa',
                'flag' => '🇫🇷'
            ],
            'it' => [
                'name' => 'Italienisch',
                'native' => 'Italiano',
                'locale' => 'it_IT',
                'region' => 'Südeuropa',
                'flag' => '🇮🇹'
            ],
            'nl' => [
                'name' => 'Niederländisch',
                'native' => 'Nederlands',
                'locale' => 'nl_NL',
                'region' => 'Westeuropa',
                'flag' => '🇳🇱'
            ],
            'es' => [
                'name' => 'Spanisch',
                'native' => 'Español',
                'locale' => 'es_ES',
                'region' => 'Südeuropa',
                'flag' => '🇪🇸'
            ],
            'pt' => [
                'name' => 'Portugiesisch',
                'native' => 'Português',
                'locale' => 'pt_PT',
                'region' => 'Südeuropa',
                'flag' => '🇵🇹'
            ],
            'el' => [
                'name' => 'Griechisch',
                'native' => 'Ελληνικά',
                'locale' => 'el_GR',
                'region' => 'Südeuropa',
                'flag' => '🇬🇷'
            ],
            'da' => [
                'name' => 'Dänisch',
                'native' => 'Dansk',
                'locale' => 'da_DK',
                'region' => 'Skandinavien',
                'flag' => '🇩🇰'
            ],
            'no' => [
                'name' => 'Norwegisch',
                'native' => 'Norsk',
                'locale' => 'no_NO',
                'region' => 'Skandinavien',
                'flag' => '🇳🇴'
            ],
            'sv' => [
                'name' => 'Schwedisch',
                'native' => 'Svenska',
                'locale' => 'sv_SE',
                'region' => 'Skandinavien',
                'flag' => '🇸🇪'
            ],
            'fi' => [
                'name' => 'Finnisch',
                'native' => 'Suomi',
                'locale' => 'fi_FI',
                'region' => 'Skandinavien',
                'flag' => '🇫🇮'
            ]
        ];
    }

    /**
     * Liefert eine pixelgenaue, browserübergreifende SVG-Flagge für einen Ländercode.
     * Löst das bekannte Windows/Chromium-Problem (Chrome & Edge auf Windows unterstützen keine Länder-Flaggen-Emojis
     * im Systemfont 'Segoe UI Emoji' und zeigen stattdessen Buchstabenkombinationen wie 'DE DE' an).
     *
     * @param string $code Sprachcode (z. B. 'de', 'en', 'fr', etc.)
     * @param int $width Breite in Pixeln (Standard: 20)
     * @param int $height Höhe in Pixeln (Standard: 14)
     * @param string $extraClass Zusätzliche CSS-Klassen
     * @return string Inline-SVG
     */
    public static function getFlagSvg(string $code, int $width = 20, int $height = 14, string $extraClass = ''): string {
        $c = strtolower(trim($code));
        $classAttr = 'weave-flag-svg' . ($extraClass !== '' ? ' ' . htmlspecialchars($extraClass) : '');
        $styleAttr = sprintf('width:%dpx; height:%dpx; display:inline-block; vertical-align:-2px; border-radius:2px; box-shadow:0 0 1px rgba(0,0,0,0.4), 0 1px 2px rgba(0,0,0,0.1); overflow:hidden; flex-shrink:0;', $width, $height);

        switch ($c) {
            case 'de':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Deutsch"><rect width="60" height="13.33" fill="#000"/><rect y="13.33" width="60" height="13.34" fill="#d00"/><rect y="26.67" width="60" height="13.33" fill="#ffce00"/></svg>';

            case 'en':
            case 'gb':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="English"><rect width="60" height="40" fill="#012169"/><path d="M0 0 L60 40 M60 0 L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0 L30 20 M60 0 L30 20 M60 40 L30 20 M0 40 L30 20" stroke="#c8102e" stroke-width="4"/><path d="M30 0 v40 M0 20 h60" stroke="#fff" stroke-width="12"/><path d="M30 0 v40 M0 20 h60" stroke="#c8102e" stroke-width="7"/></svg>';

            case 'fr':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Français"><rect width="20" height="40" fill="#002654"/><rect x="20" width="20" height="40" fill="#fff"/><rect x="40" width="20" height="40" fill="#ce1126"/></svg>';

            case 'it':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Italiano"><rect width="20" height="40" fill="#009246"/><rect x="20" width="20" height="40" fill="#fff"/><rect x="40" width="20" height="40" fill="#ce2b37"/></svg>';

            case 'nl':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Nederlands"><rect width="60" height="13.33" fill="#ae1c28"/><rect y="13.33" width="60" height="13.34" fill="#fff"/><rect y="26.67" width="60" height="13.33" fill="#21468b"/></svg>';

            case 'es':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Español"><rect width="60" height="40" fill="#aa151b"/><rect y="10" width="60" height="20" fill="#f1bf00"/><circle cx="18" cy="20" r="4" fill="#c60b1e"/></svg>';

            case 'pt':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Português"><rect width="24" height="40" fill="#006600"/><rect x="24" width="36" height="40" fill="#ff0000"/><circle cx="24" cy="20" r="6" fill="#ffcc00"/><circle cx="24" cy="20" r="3.5" fill="#fff" stroke="#003399" stroke-width="1"/></svg>';

            case 'el':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Ελληνικά"><rect width="60" height="40" fill="#0d5eaf"/><path d="M0 6.67h60 M0 15.56h60 M0 24.44h60 M0 33.33h60" stroke="#fff" stroke-width="4.44"/><rect width="20" height="20" fill="#0d5eaf"/><path d="M10 0 v20 M0 10 h20" stroke="#fff" stroke-width="4.44"/></svg>';

            case 'da':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Dansk"><rect width="60" height="40" fill="#c8102e"/><path d="M20 0 v40 M0 20 h60" stroke="#fff" stroke-width="6"/></svg>';

            case 'no':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Norsk"><rect width="60" height="40" fill="#ba0c2f"/><path d="M20 0 v40 M0 20 h60" stroke="#fff" stroke-width="8"/><path d="M20 0 v40 M0 20 h60" stroke="#00205b" stroke-width="4"/></svg>';

            case 'sv':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Svenska"><rect width="60" height="40" fill="#005293"/><path d="M20 0 v40 M0 20 h60" stroke="#fecc00" stroke-width="6"/></svg>';

            case 'fi':
                return '<svg class="' . $classAttr . '" style="' . $styleAttr . '" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" aria-label="Suomi"><rect width="60" height="40" fill="#fff"/><path d="M20 0 v40 M0 20 h60" stroke="#002f6c" stroke-width="8"/></svg>';

            default:
                return '<span class="' . $classAttr . '" style="display:inline-flex; align-items:center; justify-content:center; width:' . $width . 'px; height:' . $height . 'px; font-size:10px; background:#e2e8f0; border-radius:2px; font-weight:700;">' . strtoupper(substr($c, 0, 2)) . '</span>';
        }
    }

    /**
     * Cache für die aktiven Konfigurationsdaten.
     * @var array|null
     */
    protected static ?array $activeData = null;

    /**
     * Cache für die für den aktuellen Request ermittelte Frontend-Sprache.
     * @var string|null
     */
    protected static ?string $detectedLang = null;

    /**
     * Cache für die für das Backend ermittelte Sprache.
     * @var string|null
     */
    protected static ?string $detectedAdminLang = null;

    /**
     * Hinterlegt die globalen Datenstrukturen für den LanguageManager.
     * @param array $data
     * @return void
     */
    public static function setData(array $data): void {
        self::$activeData = $data;
        self::$detectedLang = null;
        self::$detectedAdminLang = null;
    }

    /**
     * Lädt bei Bedarf die Basisdaten aus data.json, falls nicht übergeben.
     * @return array
     */
    protected static function resolveData(?array $data = null): array {
        if ($data !== null) {
            self::$activeData = $data;
            return $data;
        }
        if (self::$activeData !== null) {
            return self::$activeData;
        }
        $root = dirname(__DIR__, 2);
        $dataFile = $root . '/data.json';
        if (file_exists($dataFile)) {
            $parsed = json_decode((string)file_get_contents($dataFile), true);
            if (is_array($parsed)) {
                self::$activeData = $parsed;
                return $parsed;
            }
        }
        return [];
    }

    /**
     * Ermittelt die konfigurierte Haupt-/Primärsprache des Systems.
     *
     * @param array|null $data Konfigurationsdaten
     * @return string Sprachcode (z. B. 'de')
     */
    public static function getPrimaryLanguage(?array $data = null): string {
        $resolved = self::resolveData($data);
        $primary = $resolved['settings']['primary_language'] ?? 'de';
        $supported = self::getSupportedLanguages();
        return isset($supported[$primary]) ? $primary : 'de';
    }

    /**
     * Ermittelt alle aktuell im System aktivierten Sprachen.
     *
     * @param array|null $data Konfigurationsdaten
     * @return array<string> Liste aktiver Sprachcodes (z. B. ['de', 'en'])
     */
    public static function getActiveLanguages(?array $data = null): array {
        $resolved = self::resolveData($data);
        $primary = self::getPrimaryLanguage($resolved);
        $configured = $resolved['settings']['active_languages'] ?? null;
        $supported = self::getSupportedLanguages();

        $active = [];
        if (is_array($configured) && !empty($configured)) {
            foreach ($configured as $code) {
                if (is_string($code) && isset($supported[$code]) && !in_array($code, $active, true)) {
                    $active[] = $code;
                }
            }
        }

        // Hauptsprache muss immer in der Liste sein
        if (!in_array($primary, $active, true)) {
            array_unshift($active, $primary);
        }

        return $active;
    }

    /**
     * Erkennt die aktuell für den Besucher wirksame Sprache mit Cookie-Persistenz.
     * Priorität:
     * 1. URL-Parameter ?lang=XX
     * 2. Session / Cookie weave_lang
     * 3. Browser-Header HTTP_ACCEPT_LANGUAGE
     * 4. Primärsprache des Systems
     *
     * @param array|null $data
     * @return string Gültiger Sprachcode
     */
    public static function detectLanguage(?array $data = null): string {
        if ($data !== null) {
            self::$activeData = $data;
        }

        $active = self::getActiveLanguages($data);
        $primary = self::getPrimaryLanguage($data);

        // 1. URL-Parameter prüfen (Höchste Priorität bei Klick auf Sprachumschalter)
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $reqLang = strtolower(trim($_GET['lang']));
            if (in_array($reqLang, $active, true)) {
                if (!headers_sent()) {
                    setcookie(self::COOKIE_NAME, $reqLang, time() + 86400 * 365, '/');
                }
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION[self::COOKIE_NAME] = $reqLang;
                }
                self::$detectedLang = $reqLang;
                return $reqLang;
            }
        }

        // 2. Session prüfen
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[self::COOKIE_NAME])) {
            $sessLang = strtolower(trim((string)$_SESSION[self::COOKIE_NAME]));
            if (in_array($sessLang, $active, true)) {
                self::$detectedLang = $sessLang;
                return $sessLang;
            }
        }

        // 3. Cookie prüfen
        if (isset($_COOKIE[self::COOKIE_NAME]) && is_string($_COOKIE[self::COOKIE_NAME])) {
            $cookieLang = strtolower(trim($_COOKIE[self::COOKIE_NAME]));
            if (in_array($cookieLang, $active, true)) {
                self::$detectedLang = $cookieLang;
                return $cookieLang;
            }
        }

        // 4. Browser-Header Accept-Language auswerten
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($active as $candidate) {
                if (strpos($header, $candidate) !== false) {
                    self::$detectedLang = $candidate;
                    return $candidate;
                }
            }
        }

        // 5. Fallback auf Primärsprache
        self::$detectedLang = $primary;
        return $primary;
    }

    /**
     * Ermittelt die aktuell aktive Sprache für den Besucher/Request (Alias für detectLanguage).
     *
     * @param array|null $data
     * @return string Sprachcode (z. B. 'de', 'en')
     */
    public static function getCurrentLanguage(?array $data = null): string {
        return self::detectLanguage($data);
    }

    /**
     * Ermittelt die OpenGraph-Locale (z. B. 'de_DE', 'en_US', 'fr_FR') für eine Sprache.
     *
     * @param string $langCode
     * @return string
     */
    public static function getLocale(string $langCode): string {
        $supported = self::getSupportedLanguages();
        return $supported[$langCode]['locale'] ?? 'de_DE';
    }

    /**
     * Lädt die Übersetzungs-JSON-Datei für eine bestimmte Sprache.
     *
     * @param string $langCode
     * @return array Assoziatives Array mit Übersetzungsschlüsseln
     */
    public static function loadTranslationFile(string $langCode): array {
        if (isset(self::$translationCache[$langCode])) {
            return self::$translationCache[$langCode];
        }

        $filePath = dirname(__DIR__, 2) . '/libs/lang/' . $langCode . '.json';
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $parsed = json_decode((string)$content, true);
            if (is_array($parsed)) {
                self::$translationCache[$langCode] = $parsed;
                return $parsed;
            }
        }

        self::$translationCache[$langCode] = [];
        return [];
    }

    /**
     * Übersetzt einen System-Textbaustein anhand seines Schlüssels.
     * Sucht zuerst in der Zielsprache, dann in der Hauptsprache, danach im Standard-Fallback.
     *
     * @param string $key Übersetzungsschlüssel (z. B. 'nav.contact')
     * @param string $default Fallback-Text, falls kein Eintrag existiert
     * @param string|null $langCode Zielsprache (null = automatisch ermitteln)
     * @param array|null $data
     * @return string
     */
    public static function t(string $key, string $default = '', ?string $langCode = null, ?array $data = null): string {
        if (str_starts_with($key, 'admin.')) {
            return self::adminT($key, $default, $langCode);
        }

        if ($langCode === null) {
            $langCode = self::detectLanguage($data);
        }

        // 1. Zielsprache abfragen
        $trans = self::loadTranslationFile($langCode);
        if (isset($trans[$key]) && is_string($trans[$key]) && trim($trans[$key]) !== '') {
            return $trans[$key];
        }

        // 2. Primärsprache abfragen, falls Zielsprache fremd ist
        $primary = self::getPrimaryLanguage($data);
        if ($langCode !== $primary) {
            $primaryTrans = self::loadTranslationFile($primary);
            if (isset($primaryTrans[$key]) && is_string($primaryTrans[$key]) && trim($primaryTrans[$key]) !== '') {
                return $primaryTrans[$key];
            }
        }

        // 3. Fallback auf Default oder Key
        return ($default !== '') ? $default : $key;
    }

    /**
     * Ermittelt einen übersetzten Inhaltstext (z. B. 'hero_title', 'about_text') aus der data.json.
     * Bietet eine automatische Fallback-Kaskade auf den Standardinhalt der Hauptsprache.
     *
     * @param array $data Gesamtdatenstruktur
     * @param string $key Textschlüssel (z. B. 'hero_title', 'hero_subtitle')
     * @param string|null $langCode Zielsprache (null = automatisch ermitteln)
     * @return string
     */
    public static function getContentText(array $data, string $key, ?string $langCode = null): string {
        if ($langCode === null) {
            $langCode = self::detectLanguage($data);
        }

        $primary = self::getPrimaryLanguage($data);

        // Falls Fremdsprache gewählt: Prüfen, ob in texts_i18n vorhanden
        if ($langCode !== $primary && isset($data['texts_i18n'][$langCode][$key])) {
            $val = trim((string)$data['texts_i18n'][$langCode][$key]);
            if ($val !== '') {
                return $val;
            }
        }

        // Standardinhalt der Hauptsprache
        return $data['texts'][$key] ?? '';
    }

    /**
     * Baut die Sprachumschalter-Links für die aktuelle URL zusammen.
     *
     * @param array|null $data
     * @return array<int, array{code: string, name: string, native: string, flag: string, active: bool, url: string}>
     */
    public static function getLanguageSwitchItems(?array $data = null): array {
        $activeCodes = self::getActiveLanguages($data);
        $current = self::detectLanguage($data);
        $supported = self::getSupportedLanguages();

        // Aktuelle Query-Parameter beibehalten, nur 'lang' anpassen
        $queryParams = $_GET;

        $items = [];
        foreach ($activeCodes as $code) {
            if (!isset($supported[$code])) {
                continue;
            }
            $queryParams['lang'] = $code;
            $items[] = [
                'code' => $code,
                'name' => $supported[$code]['name'],
                'native' => $supported[$code]['native'],
                'flag' => self::getFlagSvg($code, 20, 14),
                'flag_emoji' => $supported[$code]['flag'] ?? '',
                'active' => ($code === $current),
                'url' => '?' . http_build_query($queryParams)
            ];
        }

        return $items;
    }

    /**
     * Rendert das HTML für die Sprachauswahl.
     * Bei bis zu 3 Sprachen werden die Sprachen als direkte Links nebeneinander/untereinander gerendert.
     * Bei mehr als 3 Sprachen (inkl. DE) wird die Auswahl zu einem Dropdown, das die aktive Sprache anzeigt.
     *
     * @param array<int, array> $items Liste der Sprachumschalter-Elemente
     * @param string $context 'header' (waagerecht), 'sidebar' (Sidebar) oder 'mobile' (Slide-in)
     * @return string Generiertes HTML
     */
    public static function renderLanguageSwitcher(array $items, string $context = 'header'): string {
        if (empty($items) || count($items) <= 1) {
            return '';
        }

        $activeItem = null;
        foreach ($items as $it) {
            if (!empty($it['active'])) {
                $activeItem = $it;
                break;
            }
        }
        if (!$activeItem) {
            $activeItem = $items[0];
        }

        $totalCount = count($items);
        $isDropdown = ($totalCount > 3);

        ob_start();

        if ($isDropdown) {
            // Dropdown-Variante für mehr als 3 Sprachen
            if ($context === 'mobile') {
                ?>
                <div class="mobile-lang-switch">
                    <span class="mobile-lang-title"><?php echo htmlspecialchars(function_exists('__') ? __('nav.language', 'Sprache') : 'Sprache'); ?></span>
                    <div class="lang-dropdown lang-dropdown-mobile">
                        <button type="button" class="lang-dropdown-btn" onclick="toggleWeaveLangDropdown(this, event)" aria-haspopup="true" aria-expanded="false">
                            <span class="lang-dropdown-flag"><?php echo (!empty($activeItem['code'])) ? self::getFlagSvg($activeItem['code'], 20, 14) : ($activeItem['flag'] ?? ''); ?></span>
                            <span class="lang-dropdown-name"><?php echo htmlspecialchars($activeItem['native'] ?? ($activeItem['name'] ?? $activeItem['code'])); ?></span>
                            <span class="lang-dropdown-code"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>">
                                    <span class="lang-dropdown-flag"><?php echo (!empty($l['code'])) ? self::getFlagSvg($l['code'], 20, 14) : ($l['flag'] ?? ''); ?></span>
                                    <span class="lang-dropdown-name"><?php echo htmlspecialchars($l['native'] ?? ($l['name'] ?? $l['code'])); ?></span>
                                    <span class="lang-dropdown-code"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></span>
                                    <?php if (!empty($l['active'])): ?>
                                        <span class="material-icons lang-dropdown-check">check</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php
            } elseif ($context === 'sidebar') {
                ?>
                <div class="sidebar-lang-switch lang-dropdown-wrap">
                    <div class="lang-dropdown lang-dropdown-sidebar">
                        <button type="button" class="lang-dropdown-btn" onclick="toggleWeaveLangDropdown(this, event)" aria-haspopup="true" aria-expanded="false" title="<?php echo htmlspecialchars($activeItem['name'] ?? ''); ?>">
                            <span class="lang-dropdown-flag"><?php echo (!empty($activeItem['code'])) ? self::getFlagSvg($activeItem['code'], 20, 14) : ($activeItem['flag'] ?? ''); ?></span>
                            <span class="lang-dropdown-cur"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>">
                                    <span class="lang-dropdown-flag"><?php echo (!empty($l['code'])) ? self::getFlagSvg($l['code'], 20, 14) : ($l['flag'] ?? ''); ?></span>
                                    <span class="lang-dropdown-name"><?php echo htmlspecialchars($l['native'] ?? ($l['name'] ?? $l['code'])); ?></span>
                                    <span class="lang-dropdown-code"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></span>
                                    <?php if (!empty($l['active'])): ?>
                                        <span class="material-icons lang-dropdown-check">check</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                // Header (waagerecht)
                ?>
                <div class="header-lang-switch lang-dropdown-wrap">
                    <div class="lang-dropdown lang-dropdown-header">
                        <button type="button" class="lang-dropdown-btn" onclick="toggleWeaveLangDropdown(this, event)" aria-haspopup="true" aria-expanded="false" title="<?php echo htmlspecialchars($activeItem['name'] ?? ''); ?>">
                            <span class="lang-dropdown-flag"><?php echo (!empty($activeItem['code'])) ? self::getFlagSvg($activeItem['code'], 20, 14) : ($activeItem['flag'] ?? ''); ?></span>
                            <span class="lang-dropdown-cur"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>">
                                    <span class="lang-dropdown-flag"><?php echo (!empty($l['code'])) ? self::getFlagSvg($l['code'], 20, 14) : ($l['flag'] ?? ''); ?></span>
                                    <span class="lang-dropdown-name"><?php echo htmlspecialchars($l['native'] ?? ($l['name'] ?? $l['code'])); ?></span>
                                    <span class="lang-dropdown-code"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></span>
                                    <?php if (!empty($l['active'])): ?>
                                        <span class="material-icons lang-dropdown-check">check</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            // Inline-Variante für bis zu 3 Sprachen
            if ($context === 'mobile') {
                ?>
                <div class="mobile-lang-switch">
                    <span class="mobile-lang-title"><?php echo htmlspecialchars(function_exists('__') ? __('nav.language', 'Sprache') : 'Sprache'); ?></span>
                    <div class="mobile-lang-list">
                        <?php foreach ($items as $l): ?>
                            <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="mobile-lang-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>">
                                <span class="mobile-lang-flag"><?php echo (!empty($l['code'])) ? self::getFlagSvg($l['code'], 20, 14) : ($l['flag'] ?? ''); ?></span>
                                <span><?php echo htmlspecialchars($l['native'] ?? ($l['name'] ?? $l['code'])); ?></span>
                                <?php if (!empty($l['active'])): ?>
                                    <span class="material-icons mobile-lang-check">check</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            } elseif ($context === 'sidebar') {
                ?>
                <div class="sidebar-lang-switch">
                    <?php foreach ($items as $l): ?>
                        <?php if (!empty($l['active'])): ?>
                            <span class="sidebar-lang-badge active" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="sidebar-lang-badge" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php
            } else {
                // Header (waagerecht)
                ?>
                <div class="header-lang-switch">
                    <?php foreach ($items as $l): ?>
                        <?php if (!empty($l['active'])): ?>
                            <span class="header-lang-badge active" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="header-lang-badge" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>"><?php echo strtoupper(htmlspecialchars($l['code'] ?? '')); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }

        return ob_get_clean();
    }

    // =========================================================================
    // BACKEND-MEHRSPRACHIGKEIT (ADMIN i18n)
    // =========================================================================

    /**
     * Unterstützte Sprachen für das WeaveCMS Backend (vorerst Englisch und Deutsch).
     *
     * @return array<string, array{name: string, native: string, locale: string, flag: string}>
     */
    public static function getSupportedAdminLanguages(): array {
        return [
            'en' => [
                'name'   => 'English',
                'native' => 'English',
                'locale' => 'en_US',
                'flag'   => '🇬🇧'
            ],
            'de' => [
                'name'   => 'German',
                'native' => 'Deutsch',
                'locale' => 'de_DE',
                'flag'   => '🇩🇪'
            ]
        ];
    }

    /**
     * Erkennt die wirksame Sprache für das Backend.
     * Fallback ist immer Englisch ('en').
     *
     * @param array|null $data
     * @return string 'en' oder 'de'
     */
    public static function detectAdminLanguage(?array $data = null): string {
        if (self::$detectedAdminLang !== null && empty($_GET['admin_lang']) && empty($_GET['lang'])) {
            return self::$detectedAdminLang;
        }

        $supported = array_keys(self::getSupportedAdminLanguages());

        // 1. URL-Parameter ?admin_lang=xx oder ?lang=xx
        $paramLang = $_GET['admin_lang'] ?? null;
        if ($paramLang === null && defined('WEAVE_IN_ADMIN') && !empty($_GET['lang'])) {
            $paramLang = $_GET['lang'];
        }
        if (is_string($paramLang)) {
            $code = strtolower(trim($paramLang));
            if (in_array($code, $supported, true)) {
                if (!headers_sent()) {
                    setcookie(self::ADMIN_COOKIE_NAME, $code, time() + 86400 * 365, '/');
                }
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION[self::ADMIN_COOKIE_NAME] = $code;
                }
                self::$detectedAdminLang = $code;
                return $code;
            }
        }

        // 2. Session
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[self::ADMIN_COOKIE_NAME])) {
            $sessLang = strtolower(trim((string)$_SESSION[self::ADMIN_COOKIE_NAME]));
            if (in_array($sessLang, $supported, true)) {
                self::$detectedAdminLang = $sessLang;
                return $sessLang;
            }
        }

        // 3. Cookie
        if (isset($_COOKIE[self::ADMIN_COOKIE_NAME]) && is_string($_COOKIE[self::ADMIN_COOKIE_NAME])) {
            $cookieLang = strtolower(trim($_COOKIE[self::ADMIN_COOKIE_NAME]));
            if (in_array($cookieLang, $supported, true)) {
                self::$detectedAdminLang = $cookieLang;
                return $cookieLang;
            }
        }

        // 4. Benutzer-Einstellung (falls angemeldet)
        if (class_exists('UserManager')) {
            $user = UserManager::getCurrentUser($data);
            if (!empty($user['admin_lang']) && in_array($user['admin_lang'], $supported, true)) {
                self::$detectedAdminLang = $user['admin_lang'];
                return $user['admin_lang'];
            }
        }

        // 5. Fallback auf Englisch (DEFAULT_ADMIN_LANG)
        self::$detectedAdminLang = self::DEFAULT_ADMIN_LANG;
        return self::DEFAULT_ADMIN_LANG;
    }

    /**
     * Liefert die aktuelle Backend-Sprache.
     * @param array|null $data
     * @return string
     */
    public static function getAdminLanguage(?array $data = null): string {
        return self::detectAdminLanguage($data);
    }

    /**
     * Lädt die Übersetzungs-JSON-Datei für das Backend.
     *
     * @param string $langCode
     * @return array
     */
    public static function loadAdminTranslationFile(string $langCode): array {
        if (isset(self::$adminTranslationCache[$langCode])) {
            return self::$adminTranslationCache[$langCode];
        }

        $filePath = dirname(__DIR__, 2) . '/libs/lang/admin/' . $langCode . '.json';
        if (file_exists($filePath)) {
            $content = @file_get_contents($filePath);
            $parsed = json_decode((string)$content, true);
            if (is_array($parsed)) {
                self::$adminTranslationCache[$langCode] = $parsed;
                return $parsed;
            }
        }

        self::$adminTranslationCache[$langCode] = [];
        return [];
    }

    /**
     * Übersetzt einen System-Textbaustein für das Backend mit striktem Englisch-Fallback.
     *
     * @param string $key Übersetzungsschlüssel (z. B. 'admin.nav.dashboard')
     * @param string $default Fallback-Text
     * @param string|null $langCode Zielsprache (null = automatisch für Backend ermitteln)
     * @param array $params Platzhalter-Ersetzungen ['name' => 'Max'] für '{name}'
     * @return string
     */
    public static function adminT(string $key, string $default = '', ?string $langCode = null, array $params = []): string {
        if ($langCode === null) {
            $langCode = self::detectAdminLanguage();
        }

        $translation = null;

        // 1. In der Zielsprache suchen
        $catalog = self::loadAdminTranslationFile($langCode);
        if (isset($catalog[$key]) && is_string($catalog[$key]) && trim($catalog[$key]) !== '') {
            $translation = $catalog[$key];
        }

        // 2. Fallback auf Englisch (DEFAULT_ADMIN_LANG), falls Zielsprache nicht Englisch ist
        if ($translation === null && $langCode !== self::DEFAULT_ADMIN_LANG) {
            $enCatalog = self::loadAdminTranslationFile(self::DEFAULT_ADMIN_LANG);
            if (isset($enCatalog[$key]) && is_string($enCatalog[$key]) && trim($enCatalog[$key]) !== '') {
                $translation = $enCatalog[$key];
            }
        }

        // 3. Fallback auf allgemeinen Frontend-Katalog (libs/lang/)
        if ($translation === null) {
            $general = self::loadTranslationFile($langCode);
            if (isset($general[$key]) && is_string($general[$key]) && trim($general[$key]) !== '') {
                $translation = $general[$key];
            } elseif ($langCode !== 'en') {
                $generalEn = self::loadTranslationFile('en');
                if (isset($generalEn[$key]) && is_string($generalEn[$key]) && trim($generalEn[$key]) !== '') {
                    $translation = $generalEn[$key];
                }
            }
        }

        // 4. Letzter Fallback auf $default oder $key
        if ($translation === null) {
            $translation = ($default !== '') ? $default : $key;
        }

        // Platzhalter ersetzen
        if (!empty($params)) {
            foreach ($params as $pKey => $pVal) {
                $translation = str_replace('{' . $pKey . '}', (string)$pVal, $translation);
            }
        }

        return $translation;
    }

    /**
     * Rendert einen schlanken, eleganten Sprachumschalter für den Admin-Header.
     *
     * @return string
     */
    public static function renderAdminLanguageSwitcher(): string {
        $supported = self::getSupportedAdminLanguages();
        $current = self::detectAdminLanguage();

        // Bestehende Query-Parameter beibehalten, nur admin_lang anpassen
        $params = $_GET;

        ob_start();
        ?>
        <div class="admin-lang-switcher" style="display:inline-flex; align-items:center; background:rgba(0,0,0,0.04); border:1px solid var(--border-color); border-radius:20px; padding:2px; font-size:0.75rem; font-weight:700;">
            <?php foreach ($supported as $code => $info): 
                $isActive = ($code === $current);
                $params['admin_lang'] = $code;
                $url = '?' . http_build_query($params);
            ?>
                <a href="<?php echo htmlspecialchars($url); ?>" 
                   style="padding:3px 9px; border-radius:16px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:all 0.15s ease; <?php echo $isActive ? 'background:var(--primary); color:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.15);' : 'color:var(--text-secondary);'; ?>"
                   title="<?php echo htmlspecialchars($info['name']); ?>">
                    <span style="display:inline-flex; align-items:center;"><?php echo self::getFlagSvg($code, 16, 12); ?></span>
                    <span><?php echo strtoupper($code); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
