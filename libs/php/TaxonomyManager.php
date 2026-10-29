<?php
/**
 * ============================================================================
 * WeaveCMS - Globaler Taxonomie-Manager (TaxonomyManager)
 * ============================================================================
 * Verwaltet sektionsübergreifende Schlagwörter (Tags) für Blogartikel, Einzelseiten,
 * Kurse, Galerie-Objekte und Downloads (Phase 28.1).
 *
 * @package WeaveCMS\Core
 */

class TaxonomyManager {

    /**
     * Erzeugt aus einem beliebigen Tag-String einen URL-sicheren Slug.
     * Umlaute werden sauber transliteriert.
     *
     * @param string $tag
     * @return string
     */
    public static function slugify(string $tag): string {
        $map = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
            'ß' => 'ss'
        ];
        $s = strtr(trim($tag), $map);
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^a-z0-9_-]+/u', '-', $s);
        $s = trim($s, '-_');
        return !empty($s) ? $s : 'tag';
    }

    /**
     * Bereinigt eine Tag-Eingabe (Array oder kommagetrennter String) in ein
     * dedupliziertes Array von nicht-leeren Tag-Strings.
     *
     * @param mixed $input
     * @return array
     */
    public static function sanitizeTagList(mixed $input): array {
        if (is_string($input)) {
            $parts = explode(',', $input);
        } elseif (is_array($input)) {
            $parts = $input;
        } else {
            return [];
        }

        $clean = [];
        foreach ($parts as $p) {
            $t = trim((string)$p);
            $t = ltrim($t, '#'); // Führendes Hash entfernen, falls vom Nutzer getippt
            $t = trim($t);
            if ($t !== '' && !in_array($t, $clean, true)) {
                $clean[] = $t;
            }
        }
        return $clean;
    }

    /**
     * Ermittelt alle im gesamten CMS vergebenen Tags modulübergreifend.
     * Gibt ein Array von Tag-Informationen zurück, sortiert nach Vorkommenshäufigkeit.
     *
     * @param array $data
     * @return array Array mit Elementen: ['name' => ..., 'slug' => ..., 'count' => ..., 'modules' => [...]]
     */
    public static function getAllTags(array $data): array {
        $registry = [];

        $registerTag = function(string $rawTag, string $module) use (&$registry) {
            $t = trim($rawTag);
            if ($t === '') return;
            $slug = self::slugify($t);
            if (!isset($registry[$slug])) {
                $registry[$slug] = [
                    'name' => $t,
                    'slug' => $slug,
                    'count' => 0,
                    'modules' => []
                ];
            }
            $registry[$slug]['count']++;
            if (!in_array($module, $registry[$slug]['modules'], true)) {
                $registry[$slug]['modules'][] = $module;
            }
        };

        // 1. Blogartikel
        if (!empty($data['blog']) && is_array($data['blog'])) {
            foreach ($data['blog'] as $post) {
                $tags = self::sanitizeTagList($post['tags'] ?? []);
                foreach ($tags as $tag) {
                    $registerTag($tag, 'blog');
                }
            }
        }

        // 2. Einzelseiten
        if (!empty($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $page) {
                $tags = self::sanitizeTagList($page['tags'] ?? []);
                foreach ($tags as $tag) {
                    $registerTag($tag, 'pages');
                }
            }
        }

        // 3. Kurse & Workshops
        if (!empty($data['courses']) && is_array($data['courses'])) {
            foreach ($data['courses'] as $course) {
                $tags = self::sanitizeTagList($course['tags'] ?? []);
                foreach ($tags as $tag) {
                    $registerTag($tag, 'courses');
                }
            }
        }

        // 4. Galerie
        if (!empty($data['gallery']) && is_array($data['gallery'])) {
            foreach ($data['gallery'] as $img) {
                $tags = self::sanitizeTagList($img['tags'] ?? []);
                foreach ($tags as $tag) {
                    $registerTag($tag, 'gallery');
                }
            }
        }

        // 5. Downloads
        if (!empty($data['downloads']) && is_array($data['downloads'])) {
            foreach ($data['downloads'] as $dl) {
                $tags = self::sanitizeTagList($dl['tags'] ?? []);
                foreach ($tags as $tag) {
                    $registerTag($tag, 'downloads');
                }
            }
        }

        // Nach Häufigkeit absteigend sortieren
        uasort($registry, function($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $b['count'] <=> $a['count'];
        });

        return array_values($registry);
    }

    /**
     * Findet alle sichtbaren Inhalte zu einem bestimmten Tag-Slug,
     * gruppiert nach Inhaltsmodul.
     *
     * @param array $data
     * @param string $tagSlug
     * @param string|null $previewToken
     * @return array ['tag' => [...], 'blog' => [...], 'pages' => [...], 'courses' => [...], 'gallery' => [...], 'downloads' => [...], 'total' => int]
     */
    public static function getItemsByTag(array $data, string $tagSlug, ?string $previewToken = null): array {
        $slug = self::slugify($tagSlug);
        $result = [
            'tag' => null,
            'blog' => [],
            'pages' => [],
            'courses' => [],
            'gallery' => [],
            'downloads' => [],
            'total' => 0
        ];

        $hasTag = function(array $item) use ($slug, &$matchedTagName) {
            $tags = self::sanitizeTagList($item['tags'] ?? []);
            foreach ($tags as $t) {
                if (self::slugify($t) === $slug) {
                    if (empty($matchedTagName)) {
                        $matchedTagName = $t;
                    }
                    return true;
                }
            }
            return false;
        };

        $matchedTagName = '';

        // 1. Blog
        if (!empty($data['blog']) && is_array($data['blog'])) {
            foreach ($data['blog'] as $post) {
                if ($hasTag($post) && RevisionManager::isItemVisible($post, $previewToken)) {
                    $result['blog'][] = $post;
                }
            }
        }

        // 2. Einzelseiten
        if (!empty($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $page) {
                if ($hasTag($page) && RevisionManager::isItemVisible($page, $previewToken)) {
                    $result['pages'][] = $page;
                }
            }
        }

        // 3. Kurse & Workshops
        if (!empty($data['courses']) && is_array($data['courses'])) {
            foreach ($data['courses'] as $course) {
                if ($hasTag($course)) {
                    $result['courses'][] = $course;
                }
            }
        }

        // 4. Galerie
        if (!empty($data['gallery']) && is_array($data['gallery'])) {
            foreach ($data['gallery'] as $img) {
                if ($hasTag($img)) {
                    $result['gallery'][] = $img;
                }
            }
        }

        // 5. Downloads
        if (!empty($data['downloads']) && is_array($data['downloads'])) {
            foreach ($data['downloads'] as $dl) {
                if ($hasTag($dl)) {
                    $result['downloads'][] = $dl;
                }
            }
        }

        $result['total'] = count($result['blog']) + count($result['pages']) + count($result['courses']) + count($result['gallery']) + count($result['downloads']);
        $result['tag'] = [
            'name' => !empty($matchedTagName) ? $matchedTagName : ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug
        ];

        return $result;
    }

    /**
     * Rendert eine HTML-Leiste klickbarer Tag-Badges für das Frontend.
     *
     * @param array $tags
     * @param string $baseUrl
     * @return string
     */
    public static function renderTagBadges(array $tags, string $baseUrl = './'): string {
        $clean = self::sanitizeTagList($tags);
        if (empty($clean)) {
            return '';
        }
        $html = '<div class="weave-tag-list" style="display:inline-flex; gap:6px; flex-wrap:wrap; margin:0.35rem 0;">';
        foreach ($clean as $t) {
            $slug = self::slugify($t);
            $url = htmlspecialchars($baseUrl . '?tag=' . urlencode($slug));
            $html .= '<a href="' . $url . '" class="weave-tag-badge" style="display:inline-flex; align-items:center; font-size:0.78rem; font-weight:600; padding:2px 10px; border-radius:999px; background:rgba(46,98,72,0.09); color:var(--primary, #2e6248); text-decoration:none; transition:all 0.18s ease;" onmouseover="this.style.background=\'var(--primary)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'rgba(46,98,72,0.09)\'; this.style.color=\'var(--primary, #2e6248)\';">';
            $html .= '#' . htmlspecialchars($t);
            $html .= '</a>';
        }
        $html .= '</div>';
        return $html;
    }
}
