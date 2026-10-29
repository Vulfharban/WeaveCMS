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
        // 1. Spracherkennung & mehrsprachige Text-Aufbereitung
        LanguageManager::setData($data);
        $cur_lang = LanguageManager::detectLanguage($data);
        $primary_lang = LanguageManager::getPrimaryLanguage($data);
        $og_locale = LanguageManager::getLocale($cur_lang);
        $active_languages = LanguageManager::getActiveLanguages($data);
        $lang_switch_items = LanguageManager::getLanguageSwitchItems($data);

        // Dynamische Übersetzung der Inhaltstexte mit automatischer Fallback-Kaskade
        $all_text_keys = array_unique(array_merge(
            array_keys($data['texts'] ?? []),
            array_keys($data['texts_i18n'][$cur_lang] ?? []),
            array_keys($data['texts_i18n'][$primary_lang] ?? [])
        ));
        foreach ($all_text_keys as $ck) {
            $translated_val = LanguageManager::getContentText($data, $ck, $cur_lang);
            if ($translated_val !== '') {
                $data['texts'][$ck] = $translated_val;
            }
        }

        // 2. Galerie sortieren
        $gallery = $data['gallery'] ?? [];
        usort($gallery, function($a, $b) {
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        // 3. Definierte Kategorien & sichtbare Galerie-Elemente
        $categories = $data['categories'] ?? ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
        $visible_gallery = array_values(array_filter($gallery, function($item) use ($categories) {
            return in_array($item['category'] ?? '', $categories, true);
        }));

        // 4. Basis-URL ermitteln
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_path = rtrim($script_dir, '/\\');
        $site_url = $proto . '://' . $host . $base_path . '/';

        // 5. Deep-Link-Parameter verarbeiten
        $selected_course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;

        // 6. Modulare Sektionen & Navigationsmenü
        $visible_sections = SectionManager::getVisibleSections($data);
        $home_sections = SectionManager::getSectionsForHomePage($data);
        $site_architecture = SectionManager::getSiteArchitecture($data);
        $nav_items = SectionManager::getNavItems($data);
        if (empty($nav_items)) {
            $nav_items = [
                ['id' => 'gallery', 'nav_label' => 'Galerie'],
                ['id' => 'courses', 'nav_label' => 'Workshops'],
                ['id' => 'about', 'nav_label' => 'Über mich']
            ];
        }

        // 7. Logo-Konfiguration vorbereiten
        $logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_beach';
        $logo_color = $data['settings']['hero_logo_color'] ?? '#d97706';
        $logo_pos = $data['settings']['logo_position'] ?? 'header_before';

        $header_logo_html = ($logo_pos === 'header_before' || $logo_pos === 'header_after')
            ? LogoRenderer::render($logo_motif, $logo_color, '1.2em', 'header-paw-logo')
            : '';

        $hero_logo_html = ($logo_pos === 'hero_before' || $logo_pos === 'hero_after')
            ? LogoRenderer::render($logo_motif, $logo_color, '1.15em', 'hero-paw-logo')
            : '';

        // 8. Dynamische Design- & Styling-Konfiguration
        $settings = $data['settings'] ?? [];
        $container_width = $settings['container_width'] ?? '1200px';
        $primary_color = $settings['primary_color'] ?? '#2e6248';
        $accent_color = $settings['accent_color'] ?? '#b26c39';
        $bg_color = $settings['bg_color'] ?? '#fbf9f6';
        $font_scale = $settings['font_scale'] ?? '100%';
        $gallery_gap = $settings['gallery_gap'] ?? '2rem';
        $card_radius = $settings['card_radius'] ?? '20px';

        // 9. SEO- & OpenGraph-Metadaten vorbereiten
        $seo = $data['seo'] ?? [];
        $texts = $data['texts'] ?? [];
        $hero_title = $texts['hero_title'] ?? 'Handgefertigte Unikate & Workshops';
        $contact_name = $texts['contact_name'] ?? 'Maxima Musterfrau';

        $i18n_meta_title = $data['texts_i18n'][$cur_lang]['meta_title'] ?? null;
        $i18n_meta_desc = $data['texts_i18n'][$cur_lang]['meta_description'] ?? null;

        // Falls in der Übersetzung noch der alte Entwicklungs-Dummy vorhanden ist, verwerfen
        if (!empty($i18n_meta_title) && (str_contains($i18n_meta_title, 'Atelier Musterfrau') || str_contains($i18n_meta_title, 'Handcrafted Unique Pieces'))) {
            $i18n_meta_title = null;
        }
        if (!empty($i18n_meta_desc) && (str_contains($i18n_meta_desc, 'Handcrafted unique pieces') || str_contains($i18n_meta_desc, 'Musterfrau'))) {
            $i18n_meta_desc = null;
        }

        // Bei Primärsprache hat der zentral konfigurierte SEO-Titel Vorrang
        if ($cur_lang === $primary_lang && !empty(trim($seo['meta_title'] ?? ''))) {
            $meta_title = trim($seo['meta_title']);
        } else {
            $meta_title = !empty(trim($i18n_meta_title ?? ''))
                ? trim($i18n_meta_title)
                : (!empty(trim($seo['meta_title'] ?? ''))
                    ? trim($seo['meta_title'])
                    : ($hero_title . ' | ' . $contact_name));
        }

        if ($cur_lang === $primary_lang && !empty(trim($seo['meta_description'] ?? ''))) {
            $meta_desc = trim($seo['meta_description']);
        } else {
            $meta_desc = !empty(trim($i18n_meta_desc ?? ''))
                ? trim($i18n_meta_desc)
                : (!empty(trim($seo['meta_description'] ?? ''))
                    ? trim($seo['meta_description'])
                    : ($texts['hero_subtitle'] ?? 'Entdecke handgefertigte Naturgeflechte und Workshops.'));
        }

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
        $server_active_icons = CaptchaManager::getActiveIcons($data, $cur_lang);
        $initial_keys = array_keys($server_active_icons);
        $initial_target_key = !empty($initial_keys) ? $initial_keys[array_rand($initial_keys)] : null;
        $initial_target = $initial_target_key ? $server_active_icons[$initial_target_key] : null;

        $smtp_active = !empty($settings['smtp']['enabled']) && !empty($settings['smtp']['host']);

        // 11. Datenschutz-Text
        $cur_privacy_text = !empty($data['texts_i18n'][$cur_lang]['privacy_policy_text'])
            ? $data['texts_i18n'][$cur_lang]['privacy_policy_text']
            : (!empty($settings['privacy_policy_text'])
                ? $settings['privacy_policy_text']
                : (!empty($texts['privacy_policy_text']) ? $texts['privacy_policy_text'] : MarkdownParser::getDefaultPrivacyPolicyText($data)));

        return compact(
            'data',
            'cur_lang',
            'primary_lang',
            'og_locale',
            'active_languages',
            'lang_switch_items',
            'categories',
            'visible_gallery',
            'site_url',
            'base_path',
            'selected_course_id',
            'visible_sections',
            'home_sections',
            'site_architecture',
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

        $customTypes = class_exists('SectionManager') ? SectionManager::getRegisteredSectionTypes() : [];
        if (isset($customTypes[$sectionId]['render_callback']) && is_callable($customTypes[$sectionId]['render_callback'])) {
            call_user_func($customTypes[$sectionId]['render_callback'], $vars);
            return;
        }

        $filePath = dirname(__DIR__, 2) . '/views/sections/' . $sectionId . '.php';
        if (file_exists($filePath)) {
            require $filePath;
        } else {
            // Hook für Plugins anbieten, eigene Sektionen zu rendern (Phase 13)
            if (class_exists('PluginManager')) {
                if (PluginManager::hasAction('frontend_render_section_' . $sectionId)) {
                    PluginManager::doAction('frontend_render_section_' . $sectionId, $vars);
                } else {
                    PluginManager::doAction('frontend_render_custom_section', $sectionId, $vars['data'] ?? []);
                }
            }
        }
    }

    /**
     * Rendert die gesamte Frontend-Seite.
     *
     * @param array $data Die rohen Konfigurations- und Inhaltsdaten
     * @return void
     */
    public static function renderPage(array $data): void {
        StatsManager::recordHit();
        PluginManager::loadActivePlugins($data, 'frontend');
        $vm = self::prepareViewModel($data);
        $preview_token = trim((string)($_GET['preview'] ?? ''));

        // Prüfung auf Einzelartikel-Aufruf: ?blog=slug
        $requested_slug = trim($_GET['blog'] ?? '');
        $active_post = null;
        if (!empty($requested_slug) && !empty($data['blog'])) {
            foreach ($data['blog'] as $p) {
                if (($p['slug'] ?? '') === $requested_slug && RevisionManager::isItemVisible($p, $preview_token)) {
                    $active_post = $p;
                    break;
                }
            }
        }

        if ($active_post) {
            $is_preview = RevisionManager::isPreviewMode($active_post, $preview_token);
            $vm['is_preview_mode'] = $is_preview;
            $vm['preview_status'] = $active_post['status'] ?? 'published';
            $vm['publish_at'] = $active_post['publish_at'] ?? '';

            // Spezifische SEO-Metadaten für den Artikel überschreiben
            $vm['meta_title'] = htmlspecialchars($active_post['title']) . ' | ' . ($data['texts']['contact_name'] ?? 'WeaveCMS');
            if (!empty($active_post['teaser'])) {
                $vm['meta_desc'] = htmlspecialchars($active_post['teaser']);
            }
            if (!empty($active_post['image'])) {
                $vm['og_image_url'] = (preg_match('~^https?://~i', $active_post['image'])) 
                    ? $active_post['image'] 
                    : rtrim($vm['site_url'], '/') . '/' . ltrim($active_post['image'], '/');
            }
            $vm['active_post'] = $active_post;

            self::renderPartial('head', $vm);
            self::renderPreviewBanner($vm);
            self::renderPartial('header_nav', $vm);
            self::renderPartial('blog_single', $vm);
            self::renderPartial('footer', $vm);
            self::renderPartial('slide_contact', $vm);
            self::renderPartial('slide_impressum', $vm);
            self::renderPartial('slide_mobile_nav', $vm);
            self::renderPartial('scripts', $vm);
            return;
        }

        // Phase 28.1: Prüfung auf Taxonomie-Archivseite: ?tag=slug
        $requested_tag_slug = trim((string)($_GET['tag'] ?? ''));
        if (!empty($requested_tag_slug)) {
            $tag_data = TaxonomyManager::getItemsByTag($data, $requested_tag_slug, $preview_token);
            $vm['tag_data'] = $tag_data;
            $vm['meta_title'] = '#' . htmlspecialchars($tag_data['tag']['name']) . ' | ' . ($data['texts']['contact_name'] ?? 'WeaveCMS');
            $vm['meta_desc'] = 'Inhalte zum Schlagwort #' . htmlspecialchars($tag_data['tag']['name']) . ' auf ' . ($data['texts']['contact_name'] ?? 'WeaveCMS');

            self::renderPartial('head', $vm);
            self::renderPartial('header_nav', $vm);
            self::renderPartial('taxonomy_archive', $vm);
            self::renderPartial('footer', $vm);
            self::renderPartial('slide_contact', $vm);
            self::renderPartial('slide_impressum', $vm);
            self::renderPartial('slide_mobile_nav', $vm);
            self::renderPartial('scripts', $vm);
            return;
        }

        // Prüfung auf Freie Einzelseite oder Modulare Landingpage: ?page=slug
        $requested_page_slug = trim($_GET['page'] ?? '');
        $active_page = null;
        if (!empty($requested_page_slug) && !empty($data['pages'])) {
            foreach ($data['pages'] as $pg) {
                if (($pg['slug'] ?? '') === $requested_page_slug && RevisionManager::isItemVisible($pg, $preview_token)) {
                    $active_page = $pg;
                    break;
                }
            }
        }

        if ($active_page) {
            $is_preview = RevisionManager::isPreviewMode($active_page, $preview_token);
            $vm['is_preview_mode'] = $is_preview;
            $vm['preview_status'] = $active_page['status'] ?? 'published';
            $vm['publish_at'] = $active_page['publish_at'] ?? '';

            $cur_lang = $vm['cur_lang'] ?? LanguageManager::detectLanguage($data);
            $primary_lang = LanguageManager::getPrimaryLanguage($data);
            if ($cur_lang !== $primary_lang && !empty($active_page['translations'][$cur_lang]) && is_array($active_page['translations'][$cur_lang])) {
                $trans = $active_page['translations'][$cur_lang];
                if (!empty($trans['title'])) {
                    $active_page['title'] = $trans['title'];
                }
                if (!empty($trans['meta_description'])) {
                    $active_page['meta_description'] = $trans['meta_description'];
                }
                if (!empty($trans['content'])) {
                    $active_page['content'] = $trans['content'];
                }
                if (!empty($trans['nav_label'])) {
                    $active_page['nav_label'] = $trans['nav_label'];
                }
            }

            $vm['meta_title'] = htmlspecialchars($active_page['title']) . ' | ' . ($data['texts']['contact_name'] ?? 'WeaveCMS');
            if (!empty($active_page['meta_description'])) {
                $vm['meta_desc'] = htmlspecialchars($active_page['meta_description']);
            }
            $vm['active_page'] = $active_page;

            self::renderPartial('head', $vm);
            self::renderPreviewBanner($vm);
            self::renderPartial('header_nav', $vm);
            
            // Phase 18.2: Modulare Sektions-Landingpage vs. Standard-Content-Seite
            if (($active_page['page_type'] ?? 'content') === 'modular') {
                self::renderPartial('page_modular', $vm);
            } else {
                self::renderPartial('page_single', $vm);
            }

            self::renderPartial('footer', $vm);
            self::renderPartial('slide_contact', $vm);
            self::renderPartial('slide_impressum', $vm);
            self::renderPartial('slide_mobile_nav', $vm);
            self::renderPartial('scripts', $vm);
            return;
        }

        // Phase 18.1 / 18.3: Prüfung auf Standalone-Sektion (Multi-Page Website & Hybrid-Routing)
        if (!empty($requested_page_slug)) {
            $sec = SectionManager::getSectionById($requested_page_slug, $data);
            if ($sec && SectionManager::isSectionStandalonePage($requested_page_slug, $data)) {
                $vm['meta_title'] = htmlspecialchars($sec['title']) . ' | ' . ($data['texts']['contact_name'] ?? 'WeaveCMS');
                $vm['active_section'] = $sec;

                self::renderPartial('head', $vm);
                self::renderPartial('header_nav', $vm);
                self::renderPartial('section_single', $vm);
                self::renderPartial('footer', $vm);
                self::renderPartial('slide_contact', $vm);
                self::renderPartial('slide_impressum', $vm);
                self::renderPartial('slide_mobile_nav', $vm);
                self::renderPartial('scripts', $vm);
                return;
            }
        }

        // 1. Header & Navigation (Startseite)
        self::renderPartial('head', $vm);
        self::renderPartial('header_nav', $vm);

        // 2. Hauptinhalte (Startseiten-Sektionen nach gewähltem Architektur-Modus)
        echo '<main class="container">';
        $renderSections = !empty($vm['home_sections']) ? $vm['home_sections'] : $vm['visible_sections'];
        foreach ($renderSections as $sec) {
            $secType = !empty($sec['type']) ? $sec['type'] : ($sec['id'] ?? '');
            $renderTarget = file_exists(dirname(__DIR__, 2) . '/views/sections/' . $secType . '.php') ? $secType : ($sec['id'] ?? $secType);
            self::renderSection($renderTarget, $vm);
        }
        echo '</main>';

        // 3. Footer, Slide-in-Menüs, Lightbox & Scripts
        self::renderPartial('footer', $vm);
        self::renderPartial('slide_contact', $vm);
        self::renderPartial('slide_impressum', $vm);
        self::renderPartial('slide_mobile_nav', $vm);
        self::renderPartial('scripts', $vm);
    }

    /**
     * Rendert ein diskretes Vorschau-Banner am oberen Seitenrand, falls ein Entwurf betrachtet wird.
     *
     * @param array $vm
     * @return void
     */
    public static function renderPreviewBanner(array $vm): void {
        if (empty($vm['is_preview_mode'])) {
            return;
        }
        $status = $vm['preview_status'] ?? 'draft';
        $statusLabel = ($status === 'scheduled') 
            ? '⏱️ Geplant für ' . htmlspecialchars($vm['publish_at'] ?? '')
            : '🟡 Entwurf (Nicht öffentlich)';
        ?>
        <div class="weave-preview-banner" style="background:#0f172a; color:#f8fafc; padding:9px 18px; font-size:0.85rem; font-weight:600; display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #eab308; position:sticky; top:0; z-index:99999; box-shadow:0 4px 14px rgba(0,0,0,0.25);">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:18px; color:#eab308;">visibility</span>
                <span>Vorschau-Modus: <strong style="color:#fde047;"><?php echo $statusLabel; ?></strong></span>
                <span style="font-size:0.75rem; color:#94a3b8; margin-left:8px;">(Diese Seite ist für reguläre Besucher ausgeblendet)</span>
            </div>
            <div>
                <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                    <a href="./admin/" style="color:#60a5fa; text-decoration:none; font-size:0.8rem; background:rgba(255,255,255,0.08); padding:4px 12px; border-radius:12px; transition:background 0.2s;">&larr; Zurück zur Administration</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
