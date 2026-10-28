<?php
/**
 * ============================================================================
 * WeaveCMS SEO-Manager (SeoManager) – Phase 24
 * ============================================================================
 * Erzeugt dynamische XML-Sitemaps (inkl. hreflang), die robots.txt sowie
 * strukturierte Daten (Schema.org / JSON-LD) für LocalBusiness, Product,
 * Event/Course und BlogPosting.
 *
 * @package WeaveCMS\Seo
 * @author WeaveCMS Team
 * @license Proprietary
 */

class SeoManager {

    /**
     * Ermittelt die Basis-URL (inkl. abschließendem Slash) aus dem aktuellen Request.
     */
    public static function getBaseUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_path = rtrim(str_replace('\\', '/', $script_dir), '/');
        return $proto . '://' . $host . $base_path . '/';
    }

    /**
     * Prüft, ob ein Inhaltselement öffentlich indexierbar ist (unabhängig von Admin-Sessions).
     * Entwürfe, noch nicht erreichte Zeitplanungen und als noindex markierte Seiten werden ausgeschlossen.
     */
    public static function isPubliclyIndexable(array $item): bool {
        $status = $item['status'] ?? 'published';
        if ($status === 'scheduled') {
            $ts = strtotime((string)($item['publish_at'] ?? ''));
            if ($ts === false || time() < $ts) {
                return false;
            }
        } elseif ($status !== 'published') {
            return false;
        }
        if (!empty($item['noindex']) || !empty($item['internal'])) {
            return false;
        }
        return trim((string)($item['slug'] ?? '')) !== '';
    }

    /**
     * Baut die Liste aller Sitemap-Einträge.
     *
     * @return array<int, array{loc:string, lastmod:?string, changefreq:string, priority:string, alternates:array<string,string>}>
     */
    public static function buildSitemapEntries(array $data, string $baseUrl): array {
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $langs = class_exists('LanguageManager') ? LanguageManager::getActiveLanguages($data) : [];
        $primary = class_exists('LanguageManager') ? LanguageManager::getPrimaryLanguage($data) : 'de';
        $multilang = count($langs) > 1;

        $alternates = function (string $url, ?array $translations = null) use ($langs, $multilang, $primary): array {
            if (!$multilang) {
                return [];
            }
            $out = [];
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            foreach ($langs as $l) {
                if ($translations !== null && $l !== $primary && empty($translations[$l])) {
                    continue; // Keine Übersetzung vorhanden
                }
                $out[$l] = $url . $sep . 'lang=' . rawurlencode($l);
            }
            if (count($out) > 1) {
                $out['x-default'] = $url;
                return $out;
            }
            return [];
        };

        $entries = [];
        $add = function (string $loc, ?string $lastmod, string $freq, string $prio, array $alts) use (&$entries) {
            $entries[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => $freq,
                'priority' => $prio,
                'alternates' => $alts,
            ];
        };

        $fmtDate = function ($value): ?string {
            $ts = strtotime((string)$value);
            return $ts ? date('Y-m-d', $ts) : null;
        };

        // 1. Startseite
        $add($baseUrl, null, 'weekly', '1.0', $alternates($baseUrl));

        // 2. Standalone-Sektionen (nur bei Multi-Page / Hybrid)
        $arch = class_exists('SectionManager') ? SectionManager::getSiteArchitecture($data) : 'onepage';
        if (class_exists('SectionManager') && $arch !== SectionManager::ARCHITECTURE_ONEPAGE) {
            $pageSlugs = array_column($data['pages'] ?? [], 'slug');
            foreach (SectionManager::getVisibleSections($data) as $sec) {
                $id = (string)($sec['id'] ?? '');
                if ($id === '' || $id === 'hero' || in_array($id, $pageSlugs, true)) {
                    continue;
                }
                if (($sec['placement'] ?? '') === SectionManager::PLACEMENT_HOME) {
                    continue;
                }
                if (!SectionManager::isSectionStandalonePage($id, $data)) {
                    continue;
                }
                $url = $baseUrl . '?page=' . rawurlencode($id);
                $add($url, null, 'weekly', '0.7', $alternates($url));
            }
        }

        // 3. Freie Einzelseiten
        foreach ($data['pages'] ?? [] as $pg) {
            if (!is_array($pg) || !self::isPubliclyIndexable($pg)) {
                continue;
            }
            $url = $baseUrl . '?page=' . rawurlencode($pg['slug']);
            $add(
                $url,
                $fmtDate($pg['updated_at'] ?? $pg['created_at'] ?? ''),
                'monthly',
                '0.6',
                $alternates($url, is_array($pg['translations'] ?? null) ? $pg['translations'] : [])
            );
        }

        // 4. Blogbeiträge
        foreach ($data['blog'] ?? [] as $post) {
            if (!is_array($post) || !self::isPubliclyIndexable($post)) {
                continue;
            }
            $url = $baseUrl . '?blog=' . rawurlencode($post['slug']);
            $add(
                $url,
                $fmtDate($post['updated_at'] ?? $post['date'] ?? $post['created_at'] ?? ''),
                'monthly',
                '0.5',
                $alternates($url)
            );
        }

        if (class_exists('PluginManager') && method_exists('PluginManager', 'applyFilters')) {
            $entries = PluginManager::applyFilters('seo_sitemap_entries', $entries, $data, $baseUrl);
        }
        return is_array($entries) ? $entries : [];
    }

    /**
     * Rendert eine valide sitemap.xml (inkl. xhtml:link hreflang-Alternativen).
     */
    public static function renderSitemapXml(array $data, string $baseUrl): string {
        $x = fn($s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach (self::buildSitemapEntries($data, $baseUrl) as $e) {
            $xml .= "  <url>\n    <loc>" . $x($e['loc']) . "</loc>\n";
            if (!empty($e['lastmod'])) {
                $xml .= '    <lastmod>' . $x($e['lastmod']) . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . $x($e['changefreq'] ?? 'monthly') . "</changefreq>\n";
            $xml .= '    <priority>' . $x($e['priority'] ?? '0.5') . "</priority>\n";
            foreach ($e['alternates'] ?? [] as $lang => $href) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $x($lang) . '" href="' . $x($href) . '"/>' . "\n";
            }
            $xml .= "  </url>\n";
        }
        return $xml . "</urlset>\n";
    }

    /**
     * Erzeugt den Inhalt der robots.txt dynamisch (inkl. absolutem Sitemap-Verweis).
     */
    public static function renderRobotsTxt(array $data, string $baseUrl): string {
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $robots = strtolower((string)($data['seo']['robots'] ?? 'index, follow'));
        $lines = ['User-agent: *'];
        if (strpos($robots, 'noindex') !== false) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Allow: /images/';
            $lines[] = 'Allow: /uploads/';
            $lines[] = 'Allow: /libs/';
            $lines[] = '';
            $lines[] = '# Privater Administrationsbereich nicht crawlen';
            $lines[] = 'Disallow: /admin/';
            $lines[] = 'Disallow: /data/';
            $lines[] = 'Disallow: /backups/';
            $lines[] = 'Disallow: /logs/';
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $baseUrl . 'sitemap.xml';
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Parst ein deutsches/ISO-Datum aus Freitext (z. B. "12.05.2026" oder "2026-05-12").
     */
    public static function parseDate(string $text, string $time = ''): ?string {
        if (preg_match('~(\d{1,2})\.(\d{1,2})\.(\d{4})~', $text, $m)) {
            $date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } elseif (preg_match('~(\d{4})-(\d{2})-(\d{2})~', $text, $m)) {
            $date = $m[0];
        } else {
            return null;
        }
        if (!checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            return null;
        }
        if (preg_match('~(\d{1,2})[:.](\d{2})~', $time, $t)) {
            return $date . sprintf('T%02d:%02d:00', $t[1], $t[2]);
        }
        return $date;
    }

    /**
     * Baut den JSON-LD @graph für die aktuelle Seite.
     *
     * @param array  $data    Gesamtdaten
     * @param string $baseUrl Basis-URL
     * @param array  $context Optional: 'active_post' (Blogartikel), 'active_page' (Einzelseite), 'is_home' (bool), 'og_image_url'
     * @return array Schema.org-Graph-Objekte
     */
    public static function buildJsonLd(array $data, string $baseUrl, array $context = []): array {
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $texts = $data['texts'] ?? [];
        $name = trim((string)($texts['contact_name'] ?? ''));
        $graph = [];

        // LocalBusiness / Craftsman aus Inhaber- und Impressumsdaten
        if ($name !== '') {
            $biz = [
                '@type' => 'LocalBusiness',
                '@id' => $baseUrl . '#business',
                'name' => $name,
                'url' => $baseUrl,
            ];
            if (!empty($context['og_image_url'])) {
                $biz['image'] = $context['og_image_url'];
            }
            if (!empty($texts['contact_phone'])) {
                $biz['telephone'] = $texts['contact_phone'];
            }
            if (!empty($texts['contact_email'])) {
                $biz['email'] = $texts['contact_email'];
            }
            $address = [];
            if (!empty($texts['contact_street'])) {
                $address['streetAddress'] = $texts['contact_street'];
            }
            if (!empty($texts['contact_city'])) {
                if (preg_match('~^\s*(\d{4,5})\s+(.+)$~u', $texts['contact_city'], $m)) {
                    $address['postalCode'] = $m[1];
                    $address['addressLocality'] = trim($m[2]);
                } else {
                    $address['addressLocality'] = $texts['contact_city'];
                }
            }
            if ($address) {
                $biz['address'] = ['@type' => 'PostalAddress'] + $address + ['addressCountry' => 'DE'];
            }
            if (!empty($texts['contact_instagram'])) {
                $insta = trim((string)$texts['contact_instagram']);
                $biz['sameAs'] = [preg_match('~^https?://~i', $insta) ? $insta : 'https://www.instagram.com/' . ltrim($insta, '@/')];
            }
            $graph[] = $biz;
        }

        $provider = $name !== '' ? ['@id' => $baseUrl . '#business'] : null;

        // Blogartikel
        if (!empty($context['active_post']) && is_array($context['active_post'])) {
            $p = $context['active_post'];
            $post = [
                '@type' => 'BlogPosting',
                'headline' => (string)($p['title'] ?? ''),
                'url' => $baseUrl . '?blog=' . rawurlencode((string)($p['slug'] ?? '')),
            ];
            if (!empty($p['teaser'])) {
                $post['description'] = $p['teaser'];
            }
            if (!empty($p['date']) && strtotime((string)$p['date'])) {
                $post['datePublished'] = date('c', strtotime((string)$p['date']));
            }
            if (!empty($p['updated_at']) && strtotime((string)$p['updated_at'])) {
                $post['dateModified'] = date('c', strtotime((string)$p['updated_at']));
            }
            if (!empty($p['author'])) {
                $post['author'] = ['@type' => 'Person', 'name' => $p['author']];
            }
            if (!empty($p['image'])) {
                $post['image'] = preg_match('~^https?://~i', $p['image']) ? $p['image'] : $baseUrl . ltrim($p['image'], '/');
            }
            $graph[] = $post;
        }

        // Freie Einzelseite
        if (!empty($context['active_page']) && is_array($context['active_page'])) {
            $pg = $context['active_page'];
            $webPage = [
                '@type' => 'WebPage',
                'name' => (string)($pg['title'] ?? ''),
                'url' => $baseUrl . '?page=' . rawurlencode((string)($pg['slug'] ?? '')),
            ];
            if (!empty($pg['meta_description'])) {
                $webPage['description'] = $pg['meta_description'];
            }
            if (!empty($pg['updated_at']) && strtotime((string)$pg['updated_at'])) {
                $webPage['dateModified'] = date('c', strtotime((string)$pg['updated_at']));
            }
            $graph[] = $webPage;
        }

        // Startseite: Kurse (Event / Course) und Webshop-Produkte
        if (!empty($context['is_home'])) {
            foreach ($data['courses'] ?? [] as $c) {
                if (!is_array($c) || empty($c['title'])) {
                    continue;
                }
                $start = self::parseDate((string)($c['date_string'] ?? ''), (string)($c['time'] ?? ''));
                $price = isset($c['price']) ? self::extractPrice((string)$c['price']) : null;
                $item = [
                    '@type' => $start ? 'Event' : 'Course',
                    'name' => $c['title'],
                ];
                if (!empty($c['description'])) {
                    $item['description'] = mb_substr(trim(strip_tags((string)$c['description'])), 0, 300);
                }
                if ($start) {
                    $item['startDate'] = $start;
                    $item['eventStatus'] = 'https://schema.org/EventScheduled';
                    $item['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
                    if (!empty($c['location'])) {
                        $item['location'] = ['@type' => 'Place', 'name' => $c['location']];
                    }
                    if ($provider) {
                        $item['organizer'] = $provider;
                    }
                } elseif ($provider) {
                    $item['provider'] = $provider;
                }
                if ($price !== null) {
                    $item['offers'] = [
                        '@type' => 'Offer',
                        'price' => number_format($price, 2, '.', ''),
                        'priceCurrency' => 'EUR',
                        'availability' => (!isset($c['available']) || $c['available']) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                        'url' => $baseUrl . (!empty($c['id']) ? '?course=' . (int)$c['id'] : ''),
                    ];
                }
                $graph[] = $item;
            }

            if (class_exists('ShopManager') && class_exists('PluginManager') && PluginManager::isPluginActive('shop', $data)) {
                foreach (ShopManager::getProducts() as $prod) {
                    if (!is_array($prod) || empty($prod['active']) || empty($prod['title'])) {
                        continue;
                    }
                    $item = ['@type' => 'Product', 'name' => $prod['title']];
                    if (!empty($prod['description'])) {
                        $item['description'] = $prod['description'];
                    }
                    if (!empty($prod['sku'])) {
                        $item['sku'] = $prod['sku'];
                    }
                    if (!empty($prod['image'])) {
                        $item['image'] = preg_match('~^https?://~i', $prod['image']) ? $prod['image'] : $baseUrl . ltrim($prod['image'], '/');
                    }
                    $inStock = !empty($prod['unlimited']) || (int)($prod['stock'] ?? 0) > 0;
                    $item['offers'] = [
                        '@type' => 'Offer',
                        'price' => number_format((float)($prod['price'] ?? 0), 2, '.', ''),
                        'priceCurrency' => 'EUR',
                        'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url' => $baseUrl . '?page=shop',
                    ];
                    $graph[] = $item;
                }
            }
        }

        if (class_exists('PluginManager') && method_exists('PluginManager', 'applyFilters')) {
            $graph = PluginManager::applyFilters('seo_jsonld_graph', $graph, $data, $context);
        }
        return is_array($graph) ? $graph : [];
    }

    /**
     * Rendert den <script type="application/ld+json">-Block (leer, wenn nichts zu melden ist).
     */
    public static function renderJsonLd(array $data, string $baseUrl, array $context = []): string {
        $graph = self::buildJsonLd($data, $baseUrl, $context);
        if (!$graph) {
            return '';
        }
        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
        );
        return $json === false ? '' : '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * Extrahiert einen numerischen Preis aus Texten wie "49,90 €".
     */
    protected static function extractPrice(string $text): ?float {
        if (!preg_match('~\d+(?:[.,]\d{1,2})?~', $text, $m)) {
            return null;
        }
        return (float)str_replace(',', '.', $m[0]);
    }
}
