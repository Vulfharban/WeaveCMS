<?php
/**
 * ============================================================================
 * WeaveCMS – UpdateManager & Versionskontrolle (Phase 12)
 * ============================================================================
 * Verwaltet:
 * - CMS-Kern-Versionskontrolle (Abgleich mit GitHub Releases / version.json)
 * - Plugin-Versionskontrolle (GitHub-Tags / Repositories)
 * - Caching von Update-Checks (12 Stunden) & Timeout-Schutz
 * - Automatische Schema-Migrationen für data.json bei Versionssprüngen
 * ============================================================================
 */

class UpdateManager {

    public const DEFAULT_REPO = 'Vulfharban/WeaveCMS';
    public const CACHE_LIFETIME = 43200; // 12 Stunden

    /**
     * Ermittelt den Cache-Pfad für Update-Prüfungen.
     */
    protected static function getCacheDir(): string {
        $dir = dirname(__DIR__, 2) . '/cache/updates';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Prüft, ob ein neues Core-Update von WeaveCMS verfügbar ist.
     * @param bool $forceRefresh
     * @return array [ 'update_available' => bool, 'current_version' => string, 'latest_version' => string, 'release_url' => string, 'zip_url' => string, 'changelog' => string, 'checked_at' => int ]
     */
    public static function checkCoreUpdate(bool $forceRefresh = false): array {
        $current = defined('WEAVE_VERSION') ? WEAVE_VERSION : (class_exists('Installer') ? Installer::getVersion() : '1.0.0');
        $cacheFile = self::getCacheDir() . '/core_version.json';

        if (!$forceRefresh && file_exists($cacheFile)) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cached) && (time() - ($cached['checked_at'] ?? 0)) < self::CACHE_LIFETIME) {
                $cached['update_available'] = version_compare($current, $cached['latest_version'] ?? '1.0.0', '<');
                $cached['current_version'] = $current;
                return $cached;
            }
        }

        // Standard-Rückgabe bei fehlgeschlagener Remote-Abfrage
        $result = [
            'update_available' => false,
            'current_version'  => $current,
            'latest_version'   => $current,
            'release_url'      => 'https://github.com/' . self::DEFAULT_REPO . '/releases',
            'zip_url'          => '',
            'changelog'        => 'Keine Update-Informationen verfügbar.',
            'sha256'           => '',
            'checked_at'       => time()
        ];

        // 1. Prüfe auf optionale lokale/firmeneigene version.json im Root
        $localVersionFile = dirname(__DIR__, 2) . '/version.json';
        if (file_exists($localVersionFile)) {
            $localJson = json_decode((string)@file_get_contents($localVersionFile), true);
            if (is_array($localJson) && !empty($localJson['latest_version'])) {
                $result['latest_version'] = $localJson['latest_version'];
                $result['release_url'] = $localJson['changelog_url'] ?? $localJson['download_url'] ?? $result['release_url'];
                $result['zip_url'] = $localJson['update_download_url'] ?? $localJson['update_url'] ?? $localJson['download_url'] ?? '';
                $result['changelog'] = $localJson['changelog'] ?? 'Release-Notizen in version.json verfügbar.';
                $result['sha256'] = self::extractSha256((string)($localJson['update_sha256'] ?? $localJson['sha256'] ?? $localJson['checksum'] ?? ''), 'update');
                $result['update_available'] = version_compare($current, $result['latest_version'], '<');
                @file_put_contents($cacheFile, json_encode($result));
                return $result;
            }
        }

        // 2. GitHub Releases API abfragen (mit Timeout 3 Sekunden)
        $remoteData = self::fetchGitHubLatestRelease(self::DEFAULT_REPO);
        if ($remoteData && !empty($remoteData['tag_name'])) {
            $latestVer = ltrim($remoteData['tag_name'], 'vV');
            $result['latest_version'] = $latestVer;
            $result['release_url'] = $remoteData['html_url'] ?? $result['release_url'];
            $result['zip_url'] = $remoteData['zipball_url'] ?? '';
            $result['changelog'] = $remoteData['body'] ?? '';
            $result['sha256'] = self::extractSha256((string)($remoteData['body'] ?? ''), 'update');

            // Bevorzuge explizite Update-Pakete (z. B. update-vX.Y.Z.zip) vor Vollinstallations-Paketen
            $updateAsset = null;
            $fallbackZip = null;
            foreach ($remoteData['assets'] ?? [] as $asset) {
                if (!is_array($asset) || empty($asset['browser_download_url'])) {
                    continue;
                }
                $assetName = (string)($asset['name'] ?? '');
                if (preg_match('/\.zip$/i', $assetName)) {
                    // Update-Paket erkennen
                    if (preg_match('/(^|[\-_])update([\-_]|\.zip$)/i', $assetName)) {
                        $updateAsset = $asset['browser_download_url'];
                        break;
                    }
                    if ($fallbackZip === null) {
                        $fallbackZip = $asset['browser_download_url'];
                    }
                }
            }

            if ($updateAsset !== null) {
                $result['zip_url'] = $updateAsset;
            } elseif ($fallbackZip !== null && $result['sha256'] !== '') {
                $result['zip_url'] = $fallbackZip;
            }

            $result['update_available'] = version_compare($current, $latestVer, '<');
        }

        @file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    /**
     * Extrahiert eine SHA-256-Prüfsumme (64 Hex-Zeichen) aus einem Text, z. B. "SHA256: abc...".
     * Falls $targetFilter angegeben ist (z. B. 'update'), wird bevorzugt die Prüfsumme
     * dieser spezifischen Zeile ermittelt.
     */
    public static function extractSha256(string $text, ?string $targetFilter = null): string {
        if (!empty($targetFilter)) {
            $q = preg_quote($targetFilter, '/');
            // Suche Zeilen, in denen das Filterwort und ein 64-Hex-Hash vorkommen
            if (preg_match('/(?:^|[^\w])' . $q . '[^\r\n]*?([a-f0-9]{64})/im', $text, $m)) {
                return strtolower($m[1]);
            }
            if (preg_match('/([a-f0-9]{64})[^\r\n]*?' . $q . '/im', $text, $m)) {
                return strtolower($m[1]);
            }
        }
        return preg_match('/(?<![a-f0-9])([a-f0-9]{64})(?![a-f0-9])/i', $text, $m) ? strtolower($m[1]) : '';
    }

    /**
     * Prüft alle installierten Plugins auf verfügbare Git-Updates.
     * @param array $allPlugins Ergebnis aus PluginManager::getAllPlugins($data)
     * @param bool $forceRefresh
     * @return array [ slug => [ 'update_available' => bool, 'installed_version' => string, 'latest_version' => string, 'repo' => string, 'release_url' => string ], ... ]
     */
    public static function checkPluginUpdates(array $allPlugins, bool $forceRefresh = false): array {
        $updates = [];
        $cacheFile = self::getCacheDir() . '/plugins_version.json';
        $cachedAll = [];

        if (!$forceRefresh && file_exists($cacheFile)) {
            $cachedRaw = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cachedRaw)) {
                $cachedAll = $cachedRaw;
            }
        }

        $now = time();
        $cacheModified = false;

        foreach ($allPlugins as $slug => $plugin) {
            $repo = $plugin['github_repo'] ?? $plugin['repository'] ?? null;
            $installedVer = $plugin['version'] ?? '1.0.0';

            if (empty($repo) || !is_string($repo)) {
                continue;
            }

            // Prüfen ob Cache für dieses Plugin noch gültig ist
            if (!$forceRefresh && isset($cachedAll[$slug]) && ($now - ($cachedAll[$slug]['checked_at'] ?? 0)) < self::CACHE_LIFETIME) {
                $item = $cachedAll[$slug];
                $item['update_available'] = version_compare($installedVer, $item['latest_version'] ?? '1.0.0', '<');
                $item['installed_version'] = $installedVer;
                if ($item['update_available']) {
                    $updates[$slug] = $item;
                }
                continue;
            }

            // Remote prüfen
            $remoteData = self::fetchGitHubLatestRelease($repo);
            if ($remoteData && !empty($remoteData['tag_name'])) {
                $latestVer = ltrim($remoteData['tag_name'], 'vV');
                $item = [
                    'slug'              => $slug,
                    'name'              => $plugin['name'] ?? ucfirst($slug),
                    'installed_version' => $installedVer,
                    'latest_version'    => $latestVer,
                    'repo'              => $repo,
                    'release_url'       => $remoteData['html_url'] ?? "https://github.com/{$repo}/releases",
                    'update_available'  => version_compare($installedVer, $latestVer, '<'),
                    'checked_at'        => $now
                ];
                $cachedAll[$slug] = $item;
                $cacheModified = true;

                if ($item['update_available']) {
                    $updates[$slug] = $item;
                }
            }
        }

        if ($cacheModified) {
            @file_put_contents($cacheFile, json_encode($cachedAll));
        }

        return $updates;
    }

    /**
     * Führt eine sichere HTTP-Abfrage an die GitHub API durch (max. 3 Sek. Timeout).
     */
    protected static function fetchGitHubLatestRelease(string $repo): ?array {
        $cleanRepo = trim($repo, '/');
        // Validierung des GitHub Repo-Formats: owner/repo
        if (!preg_match('/^[a-zA-Z0-9_\.-]+\/[a-zA-Z0-9_\.-]+$/', $cleanRepo)) {
            return null;
        }

        $url = "https://api.github.com/repos/{$cleanRepo}/releases/latest";

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => 'WeaveCMS-UpdateChecker/' . (defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0'),
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // In PHP 8.0+ wird cURL automatisch bei GC geschlossen

            if ($httpCode === 200 && is_string($body)) {
                $parsed = json_decode($body, true);
                return is_array($parsed) ? $parsed : null;
            }
        } elseif (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => "User-Agent: WeaveCMS-UpdateChecker\r\n",
                    'timeout' => 3
                ]
            ]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body !== false) {
                $parsed = json_decode($body, true);
                return is_array($parsed) ? $parsed : null;
            }
        }

        return null;
    }

    /**
     * Stellt sicher, dass nach einem Core-Update alle erforderlichen Datenstruktur-Felder in data.json existieren.
     * @param array &$data
     * @return bool Wurde etwas ergänzt?
     */
    public static function ensureSchemaCompatibility(array &$data): bool {
        $modified = false;

        $defaults = [
            'settings' => [
                'upload_dir'          => 'images/uploads',
                'gallery_layout'      => 'ambient_blur',
                'gallery_lightbox'    => true,
                'menu_style'          => 'horizontal_minimal',
                'captcha_type'        => 'symbol',
                'accessibility_bar_enabled' => false
            ],
            'categories' => ['Design & Unikate', 'Naturmaterialien', 'Wohnaccessoires', 'Atelier & Werkstatt'],
            'gallery'    => [],
            'courses'    => [],
            'past_courses' => [],
            'sections'   => [],
            'plugins'    => ['active' => [], 'configs' => []],
            'seo'        => []
        ];

        foreach ($defaults as $k => $v) {
            if (!isset($data[$k])) {
                $data[$k] = $v;
                $modified = true;
            } elseif (is_array($v) && is_array($data[$k])) {
                foreach ($v as $subK => $subV) {
                    if (!isset($data[$k][$subK])) {
                        $data[$k][$subK] = $subV;
                        $modified = true;
                    }
                }
            }
        }

        return $modified;
    }
}
