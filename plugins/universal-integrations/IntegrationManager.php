<?php
/**
 * ============================================================================
 * WeaveCMS Universelle Integrationen – IntegrationManager
 * ============================================================================
 * Verwaltet konfigurierte Integrationen, Presets, URL-Interpolation,
 * SSRF-Sicherheitsprüfungen, Einbettungsmodi und die nahtlose Migration
 * des bisherigen Webtrees-Plugins.
 * ============================================================================
 */

class IntegrationManager {

    const MODE_IFRAME          = 'iframe';
    const MODE_SEAMLESS_IFRAME = 'seamless_iframe';
    const MODE_PREVIEW_CARD    = 'preview_card';
    const MODE_INLINE          = 'inline';
    const MODE_LINK            = 'link';

    const ACCESS_PUBLIC     = 'public';
    const ACCESS_FORUM_USER = 'forum_user';
    const ACCESS_ADMIN      = 'admin';

    public static function getStorageFile(): string {
        return dirname(__DIR__, 2) . '/data/integrations.json';
    }

    public static function getPresetsDir(): string {
        return __DIR__ . '/presets';
    }

    // =========================================================================
    // 1. PRESETS / VORLAGEN-KATALOG
    // =========================================================================

    public static function getPresets(): array {
        $presets = [];
        $dir = self::getPresetsDir();

        if (is_dir($dir)) {
            $files = glob($dir . '/*.json') ?: [];
            foreach ($files as $file) {
                $raw = @file_get_contents($file);
                $json = json_decode((string)$raw, true);
                if (is_array($json) && !empty($json['id'])) {
                    $presets[$json['id']] = $json;
                }
            }
        }

        if (class_exists('PluginManager')) {
            $presets = PluginManager::applyFilters('integration_register_presets', $presets);
        }

        return $presets;
    }

    public static function getPreset(string $id): ?array {
        $all = self::getPresets();
        return $all[$id] ?? null;
    }

    // =========================================================================
    // 2. INTEGRATIONEN SPEICHERN & LADEN
    // =========================================================================

    public static function getIntegrations(array $data = []): array {
        $file = self::getStorageFile();
        if (class_exists('StorageManager')) {
            $items = StorageManager::loadPluginData('universal_integrations', null, $file);
            if (!is_array($items)) {
                $migrated = self::migrateFromLegacyWebtrees($data);
                if (!empty($migrated)) {
                    self::saveIntegrations($migrated);
                    return $migrated;
                }
                return [];
            }
            return $items;
        }

        if (!file_exists($file)) {
            // Automatische Erst-Migration des Webtrees-Plugins falls vorhanden
            $migrated = self::migrateFromLegacyWebtrees($data);
            if (!empty($migrated)) {
                self::saveIntegrations($migrated);
                return $migrated;
            }
            return [];
        }

        $raw = @file_get_contents($file);
        $items = json_decode((string)$raw, true);
        return is_array($items) ? $items : [];
    }

    public static function getIntegration(string $slug, array $data = []): ?array {
        $items = self::getIntegrations($data);
        foreach ($items as $item) {
            if (($item['slug'] ?? '') === $slug) {
                return $item;
            }
        }
        return null;
    }

    public static function saveIntegrations(array $items): bool {
        $file = self::getStorageFile();
        if (class_exists('StorageManager')) {
            return StorageManager::savePluginData('universal_integrations', array_values($items), $file);
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    public static function saveIntegration(array $item, array $data = []): bool {
        if (empty($item['slug'])) {
            $item['slug'] = preg_replace('/[^a-z0-9_-]/', '', strtolower($item['name'] ?? 'app_' . bin2hex(random_bytes(3))));
        }

        $items = self::getIntegrations($data);
        $found = false;
        foreach ($items as &$existing) {
            if ($existing['slug'] === $item['slug']) {
                $existing = array_merge($existing, $item);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $item;
        }
        return self::saveIntegrations($items);
    }

    public static function deleteIntegration(string $slug, array $data = []): bool {
        $items = self::getIntegrations($data);
        $filtered = array_filter($items, fn($i) => ($i['slug'] ?? '') !== $slug);
        return self::saveIntegrations($filtered);
    }

    // =========================================================================
    // 3. MIGRATION DES BESTEHENDEN WEBTREES-PLUGINS
    // =========================================================================

    /**
     * Überführt bestehende Webtrees-Plugin-Einstellungen in ein Universal-Integrations-Profil.
     */
    public static function migrateFromLegacyWebtrees(array $data = []): array {
        if (empty($data)) {
            $data = $GLOBALS['data'] ?? [];
        }

        $webtreesConfig = $data['plugins']['configs']['webtrees-genealogy'] ?? null;
        if (!is_array($webtreesConfig)) {
            return [];
        }

        $targetUrl = $webtreesConfig['webtrees_path'] ?? ($webtreesConfig['webtrees_url'] ?? '/webtrees');
        $treeId = $webtreesConfig['tree_id'] ?? 'tree1';
        $defaultPerson = $webtreesConfig['default_person'] ?? 'X1';
        $embedToken = $webtreesConfig['embed_token'] ?? '';
        $embedMode = ($webtreesConfig['embed_mode'] ?? 'interactive_iframe') === 'family_preview' 
            ? self::MODE_PREVIEW_CARD 
            : self::MODE_IFRAME;

        $webtreesItem = [
            'slug' => 'webtrees',
            'name' => 'Webtrees Stammbaum',
            'preset_id' => 'webtrees',
            'category' => 'genealogy',
            'icon' => 'account_tree',
            'active' => true,
            'title' => $webtreesConfig['section_title'] ?? 'Familienstammbaum & Ahnenforschung',
            'subtitle' => $webtreesConfig['section_subtitle'] ?? 'Entdecke unsere Familiengeschichte interaktiv aus Webtrees.',
            'target_url' => $targetUrl,
            'url_pattern' => '{base}/tree/{tree}/individual/{person}?token={token}',
            'embed_mode' => $embedMode,
            'iframe_height' => (int)($webtreesConfig['iframe_height'] ?? 850),
            'access_level' => self::ACCESS_PUBLIC,
            'show_as_section' => !empty($webtreesConfig['section_enabled']),
            'show_as_page' => true,
            'params' => [
                'tree' => $treeId,
                'person' => $defaultPerson,
                'token' => $embedToken
            ],
            'sandbox_allow' => 'allow-scripts allow-same-origin allow-forms allow-popups',
            'created_at' => date('Y-m-d H:i:s'),
            'migrated_from_plugin' => true
        ];

        return [$webtreesItem];
    }

    // =========================================================================
    // 4. URL-AUFBAU & SICHERHEIT (SSRF & SANITIZATION)
    // =========================================================================

    /**
     * Erstellt die finale Ziel-URL durch Ersetzen aller Platzhalter.
     */
    public static function buildTargetUrl(array $item, array $params = []): string {
        $base = trim($item['target_url'] ?? '');
        $pattern = trim($item['url_pattern'] ?? '{base}');
        if (empty($pattern)) {
            $pattern = '{base}';
        }

        $mergedParams = array_merge($item['params'] ?? [], $params);
        $mergedParams['base'] = rtrim($base, '/');

        $replacements = [];
        foreach ($mergedParams as $k => $v) {
            $replacements['{' . $k . '}'] = rawurlencode((string)$v);
        }
        // {base} nicht URL-encoden
        $replacements['{base}'] = rtrim($base, '/');

        $url = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Aufräumen von leeren Parametern wie ?token= oder /individual/
        $url = preg_replace('/(\?|&)token=(&|$)/', '$1', $url);
        $url = rtrim($url, '?&/');

        return $url;
    }

    /**
     * Validiert eine Ziel-URL auf schädliche Schemata.
     */
    public static function validateTargetUrl(string $url): bool {
        if (empty($url)) return false;

        $lower = strtolower(trim($url));
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, 'vbscript:')) {
            return false;
        }

        // Relative Pfade auf demselben Server sind sicher (z. B. /webtrees)
        if (str_starts_with($url, '/')) {
            if (str_contains($url, '..')) return false; // Path Traversal verhindern
            return true;
        }

        // Externe URLs müssen valides http/https haben
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            return in_array($scheme, ['http', 'https'], true);
        }

        return false;
    }

    /**
     * Prüft eine URL auf SSRF-Gefahren für Inline-Serverabrufe.
     */
    public static function checkSsrfSafety(string $url): array {
        $isRelativeLocal = str_starts_with($url, '/');
        if ($isRelativeLocal) {
            if (str_contains($url, '..') || str_starts_with($url, '//')) {
                return ['safe' => false, 'message' => 'Ungültiger lokaler Pfad.'];
            }
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
            $url = $proto . '://' . $host . $url;
        }

        $parsed = parse_url($url);
        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        $host = strtolower((string)($parsed['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || empty($host)) {
            return ['safe' => false, 'message' => 'Ungültige URL oder Protokoll.'];
        }

        if ($isRelativeLocal) {
            return ['safe' => true, 'url' => $url];
        }

        // Externe URLs dürfen niemals auf localhost oder private/reservierte IPv4/IPv6-Bereiche zeigen
        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return ['safe' => false, 'message' => 'SSRF-Schutz: Externe Zugriffe auf localhost sind blockiert.'];
        }

        $ips = [];
        $cleanHost = trim($host, '[]');
        if (filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            $ips[] = $cleanHost;
        } else {
            $v4 = @gethostbynamel($cleanHost);
            if (is_array($v4)) {
                $ips = array_merge($ips, $v4);
            }
            if (function_exists('dns_get_record')) {
                $aaaa = @dns_get_record($cleanHost, DNS_AAAA);
                if (is_array($aaaa)) {
                    foreach ($aaaa as $rec) {
                        if (!empty($rec['ipv6'])) {
                            $ips[] = $rec['ipv6'];
                        }
                    }
                }
            }
        }

        if (empty($ips)) {
            return ['safe' => false, 'message' => "Host '{$host}' konnte nicht aufgelöst werden."];
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ['safe' => false, 'message' => 'SSRF-Schutz: Externe Zugriffe auf private oder reservierte IP-Bereiche sind blockiert.'];
            }
        }

        return ['safe' => true, 'url' => $url];
    }

    /**
     * Bereinigt per Inline-Embed abgerufenes HTML gegen XSS (Skripte, Event-Handler, unsichere URIs).
     */
    public static function sanitizeInlineHtml(string $html): string {
        if ($html === '') {
            return '';
        }
        $clean = preg_replace('/<\s*(script|style|iframe|object|embed|form|base|meta)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $clean = preg_replace('/<\s*(script|iframe|object|embed|base|meta|link)\b[^>]*\/?>/is', '', (string)$clean);
        $clean = preg_replace('/\s+on[a-z0-9_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', (string)$clean);
        $clean = preg_replace('/\b(href|src|action|formaction|poster|data)\s*=\s*(["\'])\s*(?:javascript|vbscript|data):.*?\2/is', '$1=$2#$2', (string)$clean);
        return (string)$clean;
    }

    // =========================================================================
    // 5. RENDERING-METHODEN FÜR DIE VERSCHIEDENEN MODI
    // =========================================================================

    /**
     * Prüft Zugriffsberechtigung des Besuchers für eine Integration.
     */
    public static function checkAccess(array $item): bool {
        $level = $item['access_level'] ?? self::ACCESS_PUBLIC;
        if ($level === self::ACCESS_PUBLIC) {
            return true;
        }

        if ($level === self::ACCESS_ADMIN) {
            return !empty($_SESSION['admin_logged_in']);
        }

        if ($level === self::ACCESS_FORUM_USER) {
            if (!empty($_SESSION['admin_logged_in'])) return true;
            if (class_exists('ForumUserManager') && ForumUserManager::isLoggedIn()) return true;
            return false;
        }

        return true;
    }

    /**
     * Haupt-Render-Methode für ein Integrations-Element.
     */
    public static function render(array $item, array $params = []): string {
        if (!self::checkAccess($item)) {
            return '
            <div class="card p-8 text-center max-w-lg mx-auto my-8 border">
                <span class="material-icons text-4xl text-warning mb-2">lock</span>
                <h3 class="text-xl font-bold mb-2">Zugriff geschützt</h3>
                <p class="text-sm text-muted mb-4">Dieser Bereich ist nur für registrierte Mitglieder zugänglich.</p>
                <a href="?page=forum&view=login" class="btn btn-primary btn-sm">Anmelden / Registrieren</a>
            </div>';
        }

        $mode = $params['mode'] ?? ($item['embed_mode'] ?? self::MODE_IFRAME);
        $targetUrl = self::buildTargetUrl($item, $params);
        if ($targetUrl !== '' && !self::validateTargetUrl($targetUrl)) {
            return '<div class="card p-6 text-center max-w-lg mx-auto my-6 border" style="color:#991b1b; background:#fef2f2; border-color:#fecaca;">Ungültige oder unsichere Ziel-URL blockiert.</div>';
        }

        ob_start();
        switch ($mode) {
            case self::MODE_PREVIEW_CARD:
                require __DIR__ . '/views/preview_card.php';
                break;
            case self::MODE_LINK:
                require __DIR__ . '/views/link_card.php';
                break;
            case self::MODE_INLINE:
                require __DIR__ . '/views/inline_embed.php';
                break;
            case self::MODE_SEAMLESS_IFRAME:
            case self::MODE_IFRAME:
            default:
                require __DIR__ . '/views/iframe_embed.php';
                break;
        }
        return (string)ob_get_clean();
    }
}
