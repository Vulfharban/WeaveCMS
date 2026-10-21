<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Controller
 * ============================================================================
 * Verantwortlich für die Aufbereitung aller Frontend-Daten, SEO-Metadaten,
 * Logo-Renderings und das modulare Rendern von Frontend-Views und Sektionen.
 *
 * @package WeaveCMS
 * @subpackage Core
 * @author WeaveCMS Team
 * @license Proprietary
 */

class FrontendController {

    /**
     * Bereitet alle benötigten Daten und abgeleiteten Variablen für das Frontend auf.
     *
     * @param array $data Die rohen Daten aus der Datenquelle (data.json oder DB)
     * @return array Vollständig aufbereitetes ViewModel mit allen View-Variablen
     */
    public static function prepareViewModel(array $data): array {
        // 1. Galerie sortieren
        $gallery = $data['gallery'] ?? [];
        usort($gallery, function($a, $b) {
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        // 2. Definierte Kategorien & sichtbare Galerie-Elemente
        $categories = $data['categories'] ?? ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
        $visible_gallery = array_values(array_filter($gallery, function($item) use ($categories) {
            return in_array($item['category'] ?? '', $categories, true);
        }));

        // 3. Basis-URL ermitteln
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_path = rtrim($script_dir, '/\\');
        $site_url = $proto . '://' . $host . $base_path . '/';

        // 4. Deep-Link-Parameter verarbeiten
        $selected_course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;

        // 5. Modulare Sektionen & Navigationsmenü
        $visible_sections = SectionManager::getVisibleSections($data);
        $nav_items = SectionManager::getNavItems($data);
        if (empty($nav_items)) {
            $nav_items = [
                ['id' => 'gallery', 'nav_label' => 'Galerie'],
                ['id' => 'courses', 'nav_label' => 'Workshops'],
                ['id' => 'about', 'nav_label' => 'Über mich']
            ];
        }

        // 6. Logo-Konfiguration vorbereiten
        $logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_beach';
        $logo_color = $data['settings']['hero_logo_color'] ?? '#d97706';
        $logo_pos = $data['settings']['logo_position'] ?? 'header_before';

        $header_logo_html = ($logo_pos === 'header_before' || $logo_pos === 'header_after')
            ? LogoRenderer::render($logo_motif, $logo_color, '1.2em', 'header-paw-logo')
            : '';

        $hero_logo_html = ($logo_pos === 'hero_before' || $logo_pos === 'hero_after')
            ? LogoRenderer::render($logo_motif, $logo_color, '1.15em', 'hero-paw-logo')
            : '';

        // 7. Dynamische Design- & Styling-Konfiguration
        $settings = $data['settings'] ?? [];
        $container_width = $settings['container_width'] ?? '1200px';
        $primary_color = $settings['primary_color'] ?? '#2e6248';
        $accent_color = $settings['accent_color'] ?? '#b26c39';
        $bg_color = $settings['bg_color'] ?? '#fbf9f6';
        $font_scale = $settings['font_scale'] ?? '100%';
        $gallery_gap = $settings['gallery_gap'] ?? '2rem';
        $card_radius = $settings['card_radius'] ?? '20px';

        // 8. Spracherkennung & mehrsprachige Text-Aufbereitung
        $cur_lang = LanguageManager::detectLanguage($data);
        $og_locale = LanguageManager::getLocale($cur_lang);
        $active_languages = LanguageManager::getActiveLanguages($data);
        $lang_switch_items = LanguageManager::getLanguageSwitchItems($data);

        // Dynamische Übersetzung der Inhaltstexte mit automatischer Fallback-Kaskade
        $content_keys = [
            'hero_title', 'hero_subtitle', 'about_title', 'about_text',
            'gallery_title', 'gallery_subtitle', 'courses_title', 'courses_subtitle',
            'past_courses_title', 'past_courses_subtitle', 'contact_intro_text', 'privacy_policy_text'
        ];
        foreach ($content_keys as $ck) {
            $translated_val = LanguageManager::getContentText($data, $ck, $cur_lang);
            if ($translated_val !== '') {
                $data['texts'][$ck] = $translated_val;
            }
        }

        // 9. SEO- & OpenGraph-Metadaten vorbereiten
        $seo = $data['seo'] ?? [];
        $texts = $data['texts'] ?? [];
        $hero_title = $texts['hero_title'] ?? 'Handgefertigte Unikate & Workshops';
        $contact_name = $texts['contact_name'] ?? 'Maxima Musterfrau';

        $meta_title = !empty(trim($seo['meta_title'] ?? ''))
            ? trim($seo['meta_title'])
            : ($hero_title . ' | ' . $contact_name);

        $meta_desc = !empty(trim($seo['meta_description'] ?? ''))
            ? trim($seo['meta_description'])
            : ($texts['hero_subtitle'] ?? 'Entdecke handgefertigte Naturgeflechte und Workshops.');

        $meta_keywords = !empty(trim($seo['meta_keywords'] ?? ''))
            ? trim($seo['meta_keywords'])
            : 'Handwerk, Naturgeflechte, Workshops, Kurse';

        $meta_robots = !empty(trim($seo['robots'] ?? ''))
            ? trim($seo['robots'])
            : 'index, follow';

        $canonical_url = !empty(trim($seo['canonical_url'] ?? ''))
            ? trim($seo['canonical_url'])
            : $site_url;

        $og_image_raw = !empty(trim($seo['og_image'] ?? ''))
            ? trim($seo['og_image'])
            : '/images/default_wicker1.png';

        $og_image_url = (preg_match('~^https?://~i', $og_image_raw))
            ? $og_image_raw
            : rtrim($site_url, '/') . '/' . ltrim($og_image_raw, '/');

        // 10. Captcha & SMTP Status
        $server_active_icons = CaptchaManager::getActiveIcons($data);
        $initial_keys = array_keys($server_active_icons);
        $initial_target_key = !empty($initial_keys) ? $initial_keys[array_rand($initial_keys)] : null;
        $initial_target = $initial_target_key ? $server_active_icons[$initial_target_key] : null;

        $smtp_active = !empty($settings['smtp']['enabled']) && !empty($settings['smtp']['host']);

        // 11. Datenschutz-Text
        $cur_privacy_text = !empty($settings['privacy_policy_text'])
            ? $settings['privacy_policy_text']
            : (!empty($texts['privacy_policy_text']) ? $texts['privacy_policy_text'] : MarkdownParser::getDefaultPrivacyPolicyText($data));

        return compact(
            'data',
            'cur_lang',
            'og_locale',
            'active_languages',
            'lang_switch_items',
            'categories',
            'visible_gallery',
            'site_url',
            'base_path',
            'selected_course_id',
            'visible_sections',
            'nav_items',
            'logo_motif',
            'logo_color',
            'logo_pos',
            'header_logo_html',
            'hero_logo_html',
            'container_width',
            'primary_color',
            'accent_color',
            'bg_color',
            'font_scale',
            'gallery_gap',
            'card_radius',
            'seo',
            'texts',
            'hero_title',
            'contact_name',
            'meta_title',
            'meta_desc',
            'meta_keywords',
            'meta_robots',
            'canonical_url',
            'og_image_raw',
            'og_image_url',
            'server_active_icons',
            'initial_target',
            'smtp_active',
            'cur_privacy_text'
        );
    }

    /**
     * Rendert ein Frontend-Partial aus dem Verzeichnis views/partials/.
     *
     * @param string $name Name der Partial-Datei (ohne .php)
     * @param array $vars Assoziatives Array mit Variablen für das Template
     * @return void
     */
    public static function renderPartial(string $name, array $vars = []): void {
        extract($vars, EXTR_SKIP);
        $filePath = dirname(__DIR__, 2) . '/views/partials/' . $name . '.php';
        if (file_exists($filePath)) {
            require $filePath;
        }
    }

    /**
     * Rendert eine Sektions-View aus dem Verzeichnis views/sections/.
     *
     * @param string $sectionId Bezeichner der Sektion (z. B. 'hero', 'gallery', 'courses', 'about')
     * @param array $vars Assoziatives Array mit Variablen für das Template
     * @return void
     */
    public static function renderSection(string $sectionId, array $vars = []): void {
        extract($vars, EXTR_SKIP);
        $filePath = dirname(__DIR__, 2) . '/views/sections/' . $sectionId . '.php';
        if (file_exists($filePath)) {
            require $filePath;
        }
    }

    /**
     * Rendert die gesamte Frontend-Seite.
     *
     * @param array $data Die rohen Konfigurations- und Inhaltsdaten
     * @return void
     */
    public static function renderPage(array $data): void {
        $vm = self::prepareViewModel($data);

        // 1. Header & Navigation
        self::renderPartial('head', $vm);
        self::renderPartial('header_nav', $vm);

        // 2. Hauptinhalte (Modulare Sektionen in konfigurierter Reihenfolge)
        echo '<main class="container">';
        foreach ($vm['visible_sections'] as $sec) {
            $secId = $sec['id'] ?? '';
            self::renderSection($secId, $vm);
        }
        echo '</main>';

        // 3. Footer, Slide-in-Menüs, Lightbox & Scripts
        self::renderPartial('footer', $vm);
        self::renderPartial('slide_contact', $vm);
        self::renderPartial('slide_impressum', $vm);
        self::renderPartial('slide_mobile_nav', $vm);
        self::renderPartial('scripts', $vm);
    }
}
