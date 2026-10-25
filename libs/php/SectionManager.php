<?php
/**
 * ============================================================================
 * WeaveCMS Section Manager (Phase 6: Modulare Sektionen & Seiten-Manager)
 * ============================================================================
 * Verwaltet modulare Seitenbereiche, Reihenfolge, Sichtbarkeit und Menü-Sync.
 */

class SectionManager {
    /**
     * Unterstützte Sektions-Typen.
     */
    const TYPE_HERO = 'hero';
    const TYPE_GALLERY = 'gallery';
    const TYPE_COURSES = 'courses';
    const TYPE_ABOUT = 'about';
    const TYPE_PAST_COURSES = 'past_courses';
    const TYPE_CUSTOM_TEXT = 'custom_text';
    const TYPE_BLOG = 'blog';
    const TYPE_FAQ = 'faq';
    const TYPE_TEAM = 'team';
    const TYPE_DOWNLOADS = 'downloads';
    const TYPE_PRICING = 'pricing';

    /**
     * Phase 18: Website-Architektur-Modi.
     */
    const ARCHITECTURE_ONEPAGE = 'onepage';
    const ARCHITECTURE_MULTIPAGE = 'multipage';
    const ARCHITECTURE_HYBRID = 'hybrid';

    /**
     * Phase 19: Menü-Stile & Positionen.
     */
    const MENU_STYLE_HORIZONTAL_MINIMAL = 'horizontal_minimal';
    const MENU_STYLE_HORIZONTAL_ICONS   = 'horizontal_icons';
    const MENU_STYLE_VERTICAL_SIDEBAR   = 'vertical_sidebar';
    const SIDEBAR_POS_LEFT  = 'left';
    const SIDEBAR_POS_RIGHT = 'right';

    /**
     * Sektions-Platzierung (Startseite vs. Unterseite vs. Beides).
     */
    const PLACEMENT_BOTH = 'both';
    const PLACEMENT_HOME = 'home';
    const PLACEMENT_PAGE = 'page';

    /**
     * Liefert das Standard-Material-Icon für einen Sektionstyp oder ein Element (Phase 19).
     * @param string $typeOrId
     * @return string
     */
    public static function getDefaultIcon(string $typeOrId): string {
        $map = [
            'hero' => 'flag',
            'gallery' => 'photo_library',
            'courses' => 'event',
            'past_courses' => 'photo_camera',
            'blog' => 'newspaper',
            'pricing' => 'sell',
            'faq' => 'quiz',
            'team' => 'groups',
            'downloads' => 'file_download',
            'about' => 'badge',
            'custom_text' => 'article',
            'webtrees' => 'account_tree',
            'shop' => 'storefront',
            'contact' => 'mail',
            'impressum' => 'policy',
            'page' => 'article'
        ];
        return $map[$typeOrId] ?? 'bookmark';
    }

    /**
     * Standard-Sektionen für WeaveCMS.
     * @return array
     */
    public static function getDefaultSections(): array {
        return [
            [
                'id' => 'hero',
                'type' => self::TYPE_HERO,
                'title' => 'Hero-Bereich & Hauptüberschrift',
                'nav_label' => 'Start',
                'visible' => true,
                'in_nav' => false,
                'can_delete' => false,
                'placement' => self::PLACEMENT_HOME
            ],
            [
                'id' => 'gallery',
                'type' => self::TYPE_GALLERY,
                'title' => 'Galerie & Portfolio',
                'nav_label' => 'Galerie',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'courses',
                'type' => self::TYPE_COURSES,
                'title' => 'Kurse & Workshops',
                'nav_label' => 'Workshops',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'past_courses',
                'type' => self::TYPE_PAST_COURSES,
                'title' => 'Impressionen vergangener Workshops',
                'nav_label' => 'Impressionen',
                'visible' => true,
                'in_nav' => false,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'blog',
                'type' => self::TYPE_BLOG,
                'title' => 'Aktuelles & Blog',
                'nav_label' => 'Blog',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'pricing',
                'type' => self::TYPE_PRICING,
                'title' => 'Leistungen & Preise',
                'nav_label' => 'Preise',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'faq',
                'type' => self::TYPE_FAQ,
                'title' => 'Häufige Fragen (FAQ)',
                'nav_label' => 'FAQ',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'team',
                'type' => self::TYPE_TEAM,
                'title' => 'Unser Team & Meister',
                'nav_label' => 'Team',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'downloads',
                'type' => self::TYPE_DOWNLOADS,
                'title' => 'Downloads & Dokumente',
                'nav_label' => 'Downloads',
                'visible' => true,
                'in_nav' => false,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ],
            [
                'id' => 'about',
                'type' => self::TYPE_ABOUT,
                'title' => 'Über mich & Atelier',
                'nav_label' => 'Über mich',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false,
                'placement' => self::PLACEMENT_BOTH
            ]
        ];
    }

    /**
     * Liefert den aktuellen Website-Architekturmodus (onepage, multipage, hybrid).
     * @param array $data
     * @return string
     */
    public static function getSiteArchitecture(array $data): string {
        $arch = $data['settings']['site_architecture'] ?? ($data['settings']['page_architecture'] ?? self::ARCHITECTURE_ONEPAGE);
        if (!in_array($arch, [self::ARCHITECTURE_ONEPAGE, self::ARCHITECTURE_MULTIPAGE, self::ARCHITECTURE_HYBRID], true)) {
            $arch = self::ARCHITECTURE_ONEPAGE;
        }
        return $arch;
    }

    /**
     * Stellt sicher, dass $data['sections'] existiert und alle Standardsektionen enthält.
     * @param array $data
     * @return array
     */
    public static function ensureSections(array &$data): array {
        if (!isset($data['sections']) || !is_array($data['sections']) || empty($data['sections'])) {
            $data['sections'] = self::getDefaultSections();
        } else {
            $defaults = self::getDefaultSections();
            $existing_types = [];
            foreach ($data['sections'] as $s) {
                $existing_types[] = $s['type'] ?? $s['id'];
            }

            foreach ($defaults as $def) {
                if (!in_array($def['type'], $existing_types, true)) {
                    $data['sections'][] = $def;
                }
            }
        }

        // Standard-Platzierung sicherstellen falls noch nicht vorhanden
        foreach ($data['sections'] as &$sec) {
            if (!isset($sec['placement'])) {
                $sec['placement'] = ($sec['id'] === 'hero') ? self::PLACEMENT_HOME : self::PLACEMENT_BOTH;
            }
        }
        unset($sec);

        // Plugin-registrierte Sektionen einbinden (z. B. Webtrees Stammbaum, Online-Shop)
        if (class_exists('PluginManager')) {
            $pluginSections = PluginManager::applyFilters('registered_sections', [], $data);
            if (!empty($pluginSections) && is_array($pluginSections)) {
                $existing_ids = [];
                foreach ($data['sections'] as $s) {
                    $existing_ids[] = $s['id'] ?? ($s['type'] ?? '');
                }
                foreach ($pluginSections as $pSec) {
                    $secId = $pSec['id'] ?? ($pSec['type'] ?? '');
                    if (!empty($secId) && !in_array($secId, $existing_ids, true)) {
                        if (!isset($pSec['placement'])) {
                            $pSec['placement'] = self::PLACEMENT_BOTH;
                        }
                        $data['sections'][] = $pSec;
                    }
                }
            }
        }

        return $data['sections'];
    }

    /**
     * Sucht eine Sektion anhand ihrer ID oder ihres Typs.
     * @param string $id
     * @param array $data
     * @return array|null
     */
    public static function getSectionById(string $id, array $data): ?array {
        $sections = self::ensureSections($data);
        foreach ($sections as $s) {
            if (($s['id'] ?? '') === $id || ($s['type'] ?? '') === $id) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Prüft, ob eine Sektion als Standalone-Einzelseite aufgerufen werden kann.
     * @param string $secId
     * @param array $data
     * @return bool
     */
    public static function isSectionStandalonePage(string $secId, array $data): bool {
        $sec = self::getSectionById($secId, $data);
        if (!$sec || empty($sec['visible'])) {
            return false;
        }
        // Hero ist reine Startseiten-Komponente
        if ($sec['id'] === 'hero') {
            return false;
        }
        return true;
    }

    /**
     * Liefert alle sichtbaren Sektionen in der konfigurierten Reihenfolge.
     * @param array $data
     * @return array
     */
    public static function getVisibleSections(array $data): array {
        $sections = self::ensureSections($data);
        $visible = array_values(array_filter($sections, function($sec) use ($data) {
            if (empty($sec['visible'])) {
                return false;
            }

            // Falls Sektion zu einem Plugin gehört, muss dieses Plugin auch aktiv und aktiviert sein
            $pluginSlug = $sec['plugin'] ?? '';
            if (empty($pluginSlug)) {
                if (($sec['type'] ?? '') === 'webtrees') $pluginSlug = 'webtrees-genealogy';
                if (($sec['type'] ?? '') === 'shop') $pluginSlug = 'shop';
            }
            if (!empty($pluginSlug) && class_exists('PluginManager')) {
                if (!PluginManager::isPluginActive($pluginSlug, $data)) {
                    return false;
                }
                $cfg = PluginManager::getPluginConfig($pluginSlug, $data);
                if (isset($cfg['section_enabled']) && empty($cfg['section_enabled'])) {
                    return false;
                }
            }

            return true;
        }));

        if (class_exists('PluginManager')) {
            $visible = PluginManager::applyFilters('visible_sections', $visible, $data);
        }

        if (class_exists('LanguageManager')) {
            $curLang = LanguageManager::detectLanguage($data);
            $primaryLang = LanguageManager::getPrimaryLanguage($data);
            if ($curLang !== $primaryLang) {
                foreach ($visible as &$sec) {
                    if (!empty($sec['translations'][$curLang]['title'])) {
                        $sec['title'] = $sec['translations'][$curLang]['title'];
                    } elseif (!empty($data['texts_i18n'][$curLang][$sec['id'] . '_title'])) {
                        $sec['title'] = $data['texts_i18n'][$curLang][$sec['id'] . '_title'];
                    }
                    if (!empty($sec['translations'][$curLang]['nav_label'])) {
                        $sec['nav_label'] = $sec['translations'][$curLang]['nav_label'];
                    }
                }
                unset($sec);
            }
        }

        return $visible;
    }

    /**
     * Liefert alle Sektionen, die auf der aktuellen Startseite dargestellt werden sollen.
     * Berücksichtigt den globalen Website-Architekturmodus (One-Pager vs. Multi-Page vs. Hybrid).
     * @param array $data
     * @return array
     */
    public static function getSectionsForHomePage(array $data): array {
        $visible = self::getVisibleSections($data);
        $arch = self::getSiteArchitecture($data);

        if ($arch === self::ARCHITECTURE_ONEPAGE) {
            return $visible;
        }

        return array_values(array_filter($visible, function($sec) use ($arch) {
            $secId = $sec['id'] ?? '';
            $placement = $sec['placement'] ?? self::PLACEMENT_BOTH;

            if ($arch === self::ARCHITECTURE_MULTIPAGE) {
                // Bei Multi-Page ist Hero immer auf der Startseite.
                if ($secId === 'hero') {
                    return true;
                }
                // Falls explizit 'home' konfiguriert ist, erscheint die Sektion auf der Startseite.
                if ($placement === self::PLACEMENT_HOME) {
                    return true;
                }
                // 'about' (Über mich) verbleibt bei 'both' auch auf der Startseite als Willkommens-Intro
                if ($secId === 'about' && $placement === self::PLACEMENT_BOTH) {
                    return true;
                }
                // Alle anderen Hauptsektionen (Galerie, Kurse, Blog, Downloads, Stammbaum, Shop)
                // laufen im Multi-Page-Modus als eigenständige Unterseiten.
                return false;
            }

            // Bei Hybrid: Sektionen mit Placement 'home' oder 'both' werden auf der Startseite gezeigt
            return ($placement === self::PLACEMENT_HOME || $placement === self::PLACEMENT_BOTH);
        }));
    }

    /**
     * Liefert alle Menüeinträge der sichtbaren Sektionen sowie sichtbare Einzelseiten.
     * Passt Links dynamisch an (Anker #id für One-Pager vs. ?page=id für Multi-Page).
     * @param array $data
     * @return array
     */
    public static function getNavItems(array $data): array {
        $sections = self::getVisibleSections($data);
        $items = array_values(array_filter($sections, function($sec) {
            return !empty($sec['in_nav']) && !empty($sec['nav_label']);
        }));

        $curLang = LanguageManager::detectLanguage($data);
        $primaryLang = LanguageManager::getPrimaryLanguage($data);
        $arch = self::getSiteArchitecture($data);

        // Dynamische Link-Generierung nach Architektur-Modus
        foreach ($items as &$item) {
            $secId = $item['id'] ?? '';
            $placement = $item['placement'] ?? self::PLACEMENT_BOTH;
            $isSeparatePage = false;

            // Phase 19: Standard- oder individuelles Icon auflösen
            $item['icon'] = !empty($item['icon']) ? trim((string)$item['icon']) : self::getDefaultIcon($item['type'] ?? $item['id']);

            if ($arch === self::ARCHITECTURE_MULTIPAGE) {
                // In Multi-Page haben Sektionen außer Hero/reinen Home-Sektionen eigene URLs
                if ($secId !== 'hero' && $placement !== self::PLACEMENT_HOME) {
                    $isSeparatePage = true;
                }
            } elseif ($arch === self::ARCHITECTURE_HYBRID) {
                if ($placement === self::PLACEMENT_PAGE) {
                    $isSeparatePage = true;
                }
            }

            if ($isSeparatePage) {
                $item['url'] = '?page=' . urlencode($secId) . ($curLang !== $primaryLang ? '&lang=' . urlencode($curLang) : '');
                $item['is_separate_page'] = true;
            }
        }
        unset($item);

        // Auch sichtbare Einzelseiten (Custom Subpages & Modulare Seiten) ins Menü aufnehmen
        if (!empty($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $page) {
                if (!empty($page['in_nav']) && ($page['status'] ?? 'published') === 'published') {
                    $pageNavLabel = !empty($page['nav_label']) ? $page['nav_label'] : ($page['title'] ?? 'Seite');
                    if ($curLang !== $primaryLang && !empty($page['translations'][$curLang]['nav_label'])) {
                        $pageNavLabel = $page['translations'][$curLang]['nav_label'];
                    } elseif ($curLang !== $primaryLang && !empty($page['translations'][$curLang]['title'])) {
                        $pageNavLabel = $page['translations'][$curLang]['title'];
                    }
                    $items[] = [
                        'id' => 'page-' . ($page['slug'] ?? ''),
                        'nav_label' => $pageNavLabel,
                        'url' => '?page=' . urlencode($page['slug'] ?? '') . ($curLang !== $primaryLang ? '&lang=' . urlencode($curLang) : ''),
                        'is_external_link' => true,
                        'is_custom_page' => true,
                        'page_type' => $page['page_type'] ?? 'content',
                        'icon' => !empty($page['icon']) ? trim((string)$page['icon']) : 'article'
                    ];
                }
            }
        }

        // Mehrsprachige Menübeschriftungen übersetzen
        foreach ($items as &$item) {
            if (!empty($item['is_custom_page'])) {
                continue;
            }
            if ($curLang !== $primaryLang) {
                if (!empty($item['translations'][$curLang]['nav_label'])) {
                    $item['nav_label'] = $item['translations'][$curLang]['nav_label'];
                } else {
                    $key = 'nav.' . ($item['type'] ?? $item['id']);
                    $item['nav_label'] = LanguageManager::t($key, $item['nav_label'], $curLang);
                }
                if (!empty($item['translations'][$curLang]['title'])) {
                    $item['title'] = $item['translations'][$curLang]['title'];
                }
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Aktualisiert Titel, Menü-Beschriftung, Menü-Sichtbarkeit, Platzierung, Icon und Übersetzungen einer Sektion.
     * @param array $data
     * @param string $id
     * @param array $attributes ['title' => ..., 'nav_label' => ..., 'in_nav' => ..., 'placement' => ..., 'icon' => ..., 'translations' => ...]
     * @return bool
     */
    public static function updateSection(array &$data, string $id, array $attributes): bool {
        self::ensureSections($data);
        foreach ($data['sections'] as &$sec) {
            if ($sec['id'] === $id) {
                if (isset($attributes['title'])) {
                    $sec['title'] = trim((string)$attributes['title']);
                    if ($id === 'webtrees' && isset($data['plugins']['configs']['webtrees-genealogy'])) {
                        $data['plugins']['configs']['webtrees-genealogy']['section_title'] = $sec['title'];
                    } elseif ($id === 'shop' && isset($data['plugins']['configs']['shop'])) {
                        $data['plugins']['configs']['shop']['section_title'] = $sec['title'];
                    }
                }
                if (isset($attributes['nav_label'])) {
                    $sec['nav_label'] = trim((string)$attributes['nav_label']);
                }
                if (isset($attributes['in_nav'])) {
                    $sec['in_nav'] = !empty($attributes['in_nav']);
                }
                if (isset($attributes['placement'])) {
                    $p = trim((string)$attributes['placement']);
                    if (in_array($p, [self::PLACEMENT_BOTH, self::PLACEMENT_HOME, self::PLACEMENT_PAGE], true)) {
                        $sec['placement'] = $p;
                    }
                }
                if (isset($attributes['icon'])) {
                    $sec['icon'] = trim((string)$attributes['icon']);
                }
                if (isset($attributes['translations']) && is_array($attributes['translations'])) {
                    $sec['translations'] = $attributes['translations'];
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Ändert die Reihenfolge der Sektionen anhand eines ID-Arrays.
     * @param array $data
     * @param array $orderedIds
     * @return bool
     */
    public static function reorderSections(array &$data, array $orderedIds): bool {
        self::ensureSections($data);
        $indexed = [];
        foreach ($data['sections'] as $sec) {
            $indexed[$sec['id']] = $sec;
        }

        $newSections = [];
        foreach ($orderedIds as $id) {
            if (isset($indexed[$id])) {
                $newSections[] = $indexed[$id];
                unset($indexed[$id]);
            }
        }
        // Übrige Sektionen anhängen
        foreach ($indexed as $sec) {
            $newSections[] = $sec;
        }

        $data['sections'] = $newSections;
        return true;
    }

    /**
     * Synchronisiert den Sektions-Pool (Aktiv vs. Inaktiv) und die Reihenfolge.
     * @param array $data
     * @param array $activeIds
     * @param array $inactiveIds
     * @return bool
     */
    public static function syncPoolAndOrder(array &$data, array $activeIds, array $inactiveIds = []): bool {
        self::ensureSections($data);
        $indexed = [];
        foreach ($data['sections'] as $sec) {
            $indexed[$sec['id']] = $sec;
        }

        $newSections = [];
        // 1. Zuerst alle aktiven Sektionen in der übergebenen Reihenfolge
        foreach ($activeIds as $id) {
            if (isset($indexed[$id])) {
                $indexed[$id]['visible'] = true;
                if (!empty($indexed[$id]['plugin']) && isset($data['plugins']['configs'][$indexed[$id]['plugin']])) {
                    $data['plugins']['configs'][$indexed[$id]['plugin']]['section_enabled'] = true;
                } elseif ($id === 'webtrees' && isset($data['plugins']['configs']['webtrees-genealogy'])) {
                    $data['plugins']['configs']['webtrees-genealogy']['section_enabled'] = true;
                } elseif ($id === 'shop' && isset($data['plugins']['configs']['shop'])) {
                    $data['plugins']['configs']['shop']['section_enabled'] = true;
                }
                $newSections[] = $indexed[$id];
                unset($indexed[$id]);
            }
        }

        // 2. Dann die inaktiven Sektionen im Pool
        foreach ($inactiveIds as $id) {
            if (isset($indexed[$id])) {
                $indexed[$id]['visible'] = false;
                if (!empty($indexed[$id]['plugin']) && isset($data['plugins']['configs'][$indexed[$id]['plugin']])) {
                    $data['plugins']['configs'][$indexed[$id]['plugin']]['section_enabled'] = false;
                } elseif ($id === 'webtrees' && isset($data['plugins']['configs']['webtrees-genealogy'])) {
                    $data['plugins']['configs']['webtrees-genealogy']['section_enabled'] = false;
                } elseif ($id === 'shop' && isset($data['plugins']['configs']['shop'])) {
                    $data['plugins']['configs']['shop']['section_enabled'] = false;
                }
                $newSections[] = $indexed[$id];
                unset($indexed[$id]);
            }
        }

        // 3. Etwaige übrige Sektionen hinten anfügen
        foreach ($indexed as $sec) {
            $newSections[] = $sec;
        }

        $data['sections'] = $newSections;
        return true;
    }

    /**
     * Liefert eine kurze Beschreibung für eine Sektion bzw. Kategorie im Modul-Pool.
     * @param string $typeOrId
     * @return string
     */
    public static function getSectionTypeDescription(string $typeOrId): string {
        $desc = [
            'hero' => 'Startseiten-Header mit großer Überschrift, Untertitel und Buttons.',
            'gallery' => 'Foto-Galerie & Portfolio mit Kategoriefilter und Lightbox-Zoom.',
            'courses' => 'Workshops & Termine mit Buchungsanfrage und Kursdetails.',
            'past_courses' => 'Foto-Rückblicke & Impressionen vergangener Kurse.',
            'blog' => 'Aktuelles & Blog mit Artikeln, Suchfunktion und Kategorien.',
            'pricing' => 'Leistungen, Paketpreise und übersichtliche Tarif-Karten.',
            'faq' => 'Häufig gestellte Fragen in interaktiven Akkordeon-Boxen.',
            'team' => 'Vorstellung von Team-Mitgliedern mit Fotos und Kontaktdaten.',
            'downloads' => 'Dokumente, Broschüren und PDF-Downloads mit Dateigröße.',
            'about' => 'Über-mich-Bereich mit Atelier-Vorstellung und Biografie.',
            'custom_text' => 'Freier Textabschnitt mit formatiertem Markdown-Inhalt.',
            'webtrees' => 'Genealogie & Ahnenforschung (Webtrees Stammbaum-Integration).',
            'shop' => 'Produktkatalog und Bestellsystem für Handwerk & Waren.'
        ];
        return $desc[$typeOrId] ?? 'Modulares Inhalts-Element für deine Website.';
    }

    /**
     * Liefert die ID des zuständigen Admin-Tabs für Inhalte dieser Sektion.
     * @param string $typeOrId
     * @return string|null
     */
    public static function getTargetTab(string $typeOrId): ?string {
        $map = [
            'hero' => 'tab-texts',
            'about' => 'tab-texts',
            'gallery' => 'tab-gallery',
            'courses' => 'tab-courses',
            'past_courses' => 'tab-past',
            'blog' => 'tab-blog',
            'pricing' => 'tab-pricing',
            'faq' => 'tab-faqs',
            'team' => 'tab-team',
            'downloads' => 'tab-downloads'
        ];
        return $map[$typeOrId] ?? null;
    }

    /**
     * Toggelt die Sichtbarkeit einer Sektion.
     * @param array $data
     * @param string $id
     * @param bool|null $visible (wenn null, wird umgeschaltet)
     * @return bool
     */
    public static function setVisibility(array &$data, string $id, ?bool $visible = null): bool {
        self::ensureSections($data);
        foreach ($data['sections'] as &$sec) {
            if ($sec['id'] === $id) {
                $newVis = ($visible !== null) ? $visible : empty($sec['visible']);
                $sec['visible'] = $newVis;

                // Plugin-Konfiguration synchron halten
                if (!empty($sec['plugin']) && isset($data['plugins']['configs'][$sec['plugin']])) {
                    $data['plugins']['configs'][$sec['plugin']]['section_enabled'] = $newVis;
                } elseif ($id === 'webtrees' && isset($data['plugins']['configs']['webtrees-genealogy'])) {
                    $data['plugins']['configs']['webtrees-genealogy']['section_enabled'] = $newVis;
                } elseif ($id === 'shop' && isset($data['plugins']['configs']['shop'])) {
                    $data['plugins']['configs']['shop']['section_enabled'] = $newVis;
                }

                return true;
            }
        }
        return false;
    }

    /**
     * Verschiebt eine Sektion um eine Position nach oben oder unten.
     * @param array $data
     * @param string $id
     * @param string $direction 'up' | 'down'
     * @return bool
     */
    public static function moveSection(array &$data, string $id, string $direction): bool {
        self::ensureSections($data);
        $sections = $data['sections'];
        $count = count($sections);
        $foundIdx = -1;

        for ($i = 0; $i < $count; $i++) {
            if ($sections[$i]['id'] === $id) {
                $foundIdx = $i;
                break;
            }
        }

        if ($foundIdx === -1) {
            return false;
        }

        $targetIdx = ($direction === 'up') ? $foundIdx - 1 : $foundIdx + 1;
        if ($targetIdx < 0 || $targetIdx >= $count) {
            return false; // Bereits am Anfang oder Ende
        }

        // Vertauschen
        $temp = $sections[$foundIdx];
        $sections[$foundIdx] = $sections[$targetIdx];
        $sections[$targetIdx] = $temp;

        $data['sections'] = $sections;
        return true;
    }
}
