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
                'can_delete' => false
            ],
            [
                'id' => 'gallery',
                'type' => self::TYPE_GALLERY,
                'title' => 'Galerie & Portfolio',
                'nav_label' => 'Galerie',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'courses',
                'type' => self::TYPE_COURSES,
                'title' => 'Kurse & Workshops',
                'nav_label' => 'Workshops',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'past_courses',
                'type' => self::TYPE_PAST_COURSES,
                'title' => 'Impressionen vergangener Workshops',
                'nav_label' => 'Impressionen',
                'visible' => true,
                'in_nav' => false,
                'can_delete' => false
            ],
            [
                'id' => 'blog',
                'type' => self::TYPE_BLOG,
                'title' => 'Aktuelles & Blog',
                'nav_label' => 'Blog',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'pricing',
                'type' => self::TYPE_PRICING,
                'title' => 'Leistungen & Preise',
                'nav_label' => 'Preise',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'faq',
                'type' => self::TYPE_FAQ,
                'title' => 'Häufige Fragen (FAQ)',
                'nav_label' => 'FAQ',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'team',
                'type' => self::TYPE_TEAM,
                'title' => 'Unser Team & Meister',
                'nav_label' => 'Team',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ],
            [
                'id' => 'downloads',
                'type' => self::TYPE_DOWNLOADS,
                'title' => 'Downloads & Dokumente',
                'nav_label' => 'Downloads',
                'visible' => true,
                'in_nav' => false,
                'can_delete' => false
            ],
            [
                'id' => 'about',
                'type' => self::TYPE_ABOUT,
                'title' => 'Über mich & Atelier',
                'nav_label' => 'Über mich',
                'visible' => true,
                'in_nav' => true,
                'can_delete' => false
            ]
        ];
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
        return $data['sections'];
    }

    /**
     * Liefert alle sichtbaren Sektionen in der konfigurierten Reihenfolge.
     * @param array $data
     * @return array
     */
    public static function getVisibleSections(array $data): array {
        $sections = self::ensureSections($data);
        return array_values(array_filter($sections, function($sec) {
            return !empty($sec['visible']);
        }));
    }

    /**
     * Liefert alle Menüeinträge der sichtbaren Sektionen sowie sichtbare Einzelseiten.
     * @param array $data
     * @return array
     */
    public static function getNavItems(array $data): array {
        $sections = self::getVisibleSections($data);
        $items = array_values(array_filter($sections, function($sec) {
            return !empty($sec['in_nav']) && !empty($sec['nav_label']);
        }));

        // Auch sichtbare Einzelseiten (Custom Subpages) ins Menü aufnehmen
        if (!empty($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $page) {
                if (!empty($page['in_nav']) && ($page['status'] ?? 'published') === 'published') {
                    $items[] = [
                        'id' => 'page-' . ($page['slug'] ?? ''),
                        'nav_label' => $page['nav_label'] ?? $page['title'],
                        'url' => '?page=' . urlencode($page['slug'] ?? ''),
                        'is_external_link' => true
                    ];
                }
            }
        }

        // Mehrsprachige Menübeschriftungen übersetzen
        $curLang = LanguageManager::detectLanguage($data);
        foreach ($items as &$item) {
            $key = 'nav.' . ($item['type'] ?? $item['id']);
            $item['nav_label'] = LanguageManager::t($key, $item['nav_label'], $curLang);
        }

        return $items;
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
                $sec['visible'] = ($visible !== null) ? $visible : empty($sec['visible']);
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
