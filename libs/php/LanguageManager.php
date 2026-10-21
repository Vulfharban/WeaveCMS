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
     * Ermittelt die konfigurierte Haupt-/Primärsprache des Systems.
     *
     * @param array|null $data Konfigurationsdaten
     * @return string Sprachcode (z. B. 'de')
     */
    public static function getPrimaryLanguage(?array $data = null): string {
        $primary = $data['settings']['primary_language'] ?? 'de';
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
        $primary = self::getPrimaryLanguage($data);
        $configured = $data['settings']['active_languages'] ?? null;
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
        $active = self::getActiveLanguages($data);
        $primary = self::getPrimaryLanguage($data);

        // 1. URL-Parameter prüfen
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $reqLang = strtolower(trim($_GET['lang']));
            if (in_array($reqLang, $active, true)) {
                if (!headers_sent()) {
                    setcookie(self::COOKIE_NAME, $reqLang, time() + 86400 * 365, '/');
                }
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION[self::COOKIE_NAME] = $reqLang;
                }
                return $reqLang;
            }
        }

        // 2. Cookie prüfen
        if (isset($_COOKIE[self::COOKIE_NAME]) && is_string($_COOKIE[self::COOKIE_NAME])) {
            $cookieLang = strtolower(trim($_COOKIE[self::COOKIE_NAME]));
            if (in_array($cookieLang, $active, true)) {
                return $cookieLang;
            }
        }

        // 3. Browser-Header Accept-Language auswerten
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($active as $candidate) {
                if (strpos($header, $candidate) !== false) {
                    return $candidate;
                }
            }
        }

        // 4. Fallback auf Primärsprache
        return $primary;
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
}
