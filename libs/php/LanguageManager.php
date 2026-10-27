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
     * Cookie-Name für die gespeicherte Benutzersprache.
     */
    public const COOKIE_NAME = 'weave_lang';

    /**
     * Cache für bereits geladene Übersetzungsdateien.
     * @var array<string, array>
     */
    protected static array $translationCache = [];

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
     * Cache für die aktiven Konfigurationsdaten.
     * @var array|null
     */
    protected static ?array $activeData = null;

    /**
     * Cache für die für den aktuellen Request ermittelte Sprache.
     * @var string|null
     */
    protected static ?string $detectedLang = null;

    /**
     * Hinterlegt die globalen Datenstrukturen für den LanguageManager.
     * @param array $data
     * @return void
     */
    public static function setData(array $data): void {
        self::$activeData = $data;
        self::$detectedLang = null;
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
                'flag' => $supported[$code]['flag'],
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
                            <span class="lang-dropdown-flag"><?php echo $activeItem['flag'] ?? ''; ?></span>
                            <span class="lang-dropdown-name"><?php echo htmlspecialchars($activeItem['native'] ?? ($activeItem['name'] ?? $activeItem['code'])); ?></span>
                            <span class="lang-dropdown-code"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>">
                                    <span class="lang-dropdown-flag"><?php echo $l['flag'] ?? ''; ?></span>
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
                            <span class="lang-dropdown-flag"><?php echo $activeItem['flag'] ?? ''; ?></span>
                            <span class="lang-dropdown-cur"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>">
                                    <span class="lang-dropdown-flag"><?php echo $l['flag'] ?? ''; ?></span>
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
                            <span class="lang-dropdown-flag"><?php echo $activeItem['flag'] ?? ''; ?></span>
                            <span class="lang-dropdown-cur"><?php echo strtoupper(htmlspecialchars($activeItem['code'] ?? '')); ?></span>
                            <span class="material-icons lang-dropdown-caret">expand_more</span>
                        </button>
                        <div class="lang-dropdown-menu">
                            <?php foreach ($items as $l): ?>
                                <a href="<?php echo htmlspecialchars($l['url'] ?? '#'); ?>" class="lang-dropdown-item <?php echo (!empty($l['active'])) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($l['name'] ?? ''); ?>">
                                    <span class="lang-dropdown-flag"><?php echo $l['flag'] ?? ''; ?></span>
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
                                <span class="mobile-lang-flag"><?php echo $l['flag'] ?? ''; ?></span>
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
}
