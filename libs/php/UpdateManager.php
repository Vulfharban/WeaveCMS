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
    public const REQUIRED_BASELINE_VERSION = '1.3.4.4';
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
        if (class_exists('CoreUpdater')) {
            $pendingMarker = CoreUpdater::getRootDir() . '/data/update_pending.json';
            if (is_file($pendingMarker)) {
                $pending = json_decode((string)@file_get_contents($pendingMarker), true);
                if (!empty($pending['to']) && version_compare((string)$pending['to'], $current, '>=')) {
                    $current = (string)$pending['to'];
                }
            }
        }
        $cacheFile = self::getCacheDir() . '/core_version.json';

        if (!$forceRefresh && file_exists($cacheFile)) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cached) && (time() - ($cached['checked_at'] ?? 0)) < self::CACHE_LIFETIME) {
                $cached['update_available'] = version_compare($current, $cached['latest_version'] ?? '1.0.0', '<');
                $cached['current_version'] = $current;
                $minReqCached = trim((string)($cached['min_core_version'] ?? ''));
                if ($minReqCached !== '' && version_compare($current, $minReqCached, '<')) {
                    $cached['prerequisite_blocked'] = true;
                }
                return $cached;
            }
        }

        // Standard-Rückgabe bei fehlgeschlagener Remote-Abfrage
        $result = [
            'update_available'      => false,
            'current_version'       => $current,
            'latest_version'        => $current,
            'release_url'           => 'https://github.com/' . self::DEFAULT_REPO . '/releases',
            'zip_url'               => '',
            'changelog'             => 'Keine Update-Informationen verfügbar.',
            'sha256'                => '',
            'min_core_version'      => '',
            'prerequisite_required' => false,
            'prerequisite_blocked'  => false,
            'checked_at'            => time()
        ];

        // 1. Prüfe auf optionale lokale/firmeneigene version.json im Root
        $localVersionFile = dirname(__DIR__, 2) . '/version.json';
        if (file_exists($localVersionFile)) {
            $localJson = json_decode((string)@file_get_contents($localVersionFile), true);
            if (is_array($localJson) && !empty($localJson['latest_version'])) {
                $latestVer = (string)$localJson['latest_version'];
                $minCore = trim((string)($localJson['min_core_version'] ?? $localJson['requires_core'] ?? ''));
                if ($minCore === '' && version_compare($latestVer, self::REQUIRED_BASELINE_VERSION, '>')) {
                    $minCore = self::REQUIRED_BASELINE_VERSION;
                }
                $result['latest_version'] = $latestVer;
                $result['release_url'] = $localJson['changelog_url'] ?? $localJson['download_url'] ?? $result['release_url'];
                $result['zip_url'] = $localJson['update_download_url'] ?? $localJson['update_url'] ?? $localJson['download_url'] ?? '';
                $result['changelog'] = $localJson['changelog'] ?? 'Release-Notizen in version.json verfügbar.';
                $rawSha = (string)($localJson['update_sha256'] ?? $localJson['sha256'] ?? $localJson['checksum'] ?? '');
                $result['sha256'] = self::extractSha256($rawSha, 'update');
                if ($result['sha256'] === '') {
                    $result['sha256'] = self::extractSha256($rawSha);
                }
                $result['min_core_version'] = $minCore;
                if ($minCore !== '' && version_compare($current, $minCore, '<')) {
                    $result['prerequisite_blocked'] = true;
                }
                $result['update_available'] = version_compare($current, $result['latest_version'], '<');
                @file_put_contents($cacheFile, json_encode($result));
                return $result;
            }
        }

        // 2. GitHub Releases API abfragen (mit Timeout 4 Sekunden)
        $remoteData = self::fetchGitHubLatestRelease(self::DEFAULT_REPO);
        if ($remoteData && !empty($remoteData['tag_name'])) {
            $latestVer = ltrim($remoteData['tag_name'], 'vV');
            $body = (string)($remoteData['body'] ?? '');
            $parsed = self::parseGitHubReleaseAsset($remoteData);

            $minCore = self::extractMinCoreVersion($body);
            if ($minCore === '' && version_compare($latestVer, self::REQUIRED_BASELINE_VERSION, '>')) {
                $minCore = self::REQUIRED_BASELINE_VERSION;
            }

            $result['latest_version'] = $latestVer;
            $result['release_url'] = $remoteData['html_url'] ?? $result['release_url'];
            $result['zip_url'] = $parsed['zip_url'];
            $result['sha256'] = $parsed['sha256'];
            $result['changelog'] = $body;
            $result['min_core_version'] = $minCore;
            $result['update_available'] = version_compare($current, $latestVer, '<');

            // Falls eine Voraussetzung (z. B. v1.3.4.4) noch nicht erfüllt ist, Zwischenschritt-Release anbieten
            if ($result['update_available'] && $minCore !== '' && version_compare($current, $minCore, '<')) {
                $prereqData = self::fetchGitHubLatestRelease(self::DEFAULT_REPO, 'v' . ltrim($minCore, 'vV'));
                if ($prereqData && !empty($prereqData['tag_name'])) {
                    $prereqParsed = self::parseGitHubReleaseAsset($prereqData);
                    if ($prereqParsed['zip_url'] !== '' && $prereqParsed['sha256'] !== '') {
                        $result['final_latest_version'] = $latestVer;
                        $result['prerequisite_required'] = true;
                        $result['prerequisite_target'] = $minCore;
                        $result['min_core_version'] = '';
                        $result['latest_version'] = ltrim($prereqData['tag_name'], 'vV');
                        $result['release_url'] = $prereqData['html_url'] ?? $result['release_url'];
                        $result['zip_url'] = $prereqParsed['zip_url'];
                        $result['sha256'] = $prereqParsed['sha256'];
                        $result['changelog'] = (string)($prereqData['body'] ?? $result['changelog']);
                    } else {
                        $result['prerequisite_blocked'] = true;
                    }
                } else {
                    $result['prerequisite_blocked'] = true;
                }
            }
        }

        @file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    /**
     * Ermittelt eine optionale Mindest-Core-Version aus den Release-Notes
     * (z. B. "Voraussetzung: v1.3.4.4", "Mindestversion: v1.3.4.4" oder "Requires-Core: 1.3.4.4").
     */
    public static function extractMinCoreVersion(string $text): string {
        if (preg_match('/(?:Voraussetzung|Mindestversion|Requires[-_\s]?Core|Min[-_\s]?Version)\s*[:\-]?\s*v?([0-9]+(?:\.[0-9]+){2,3})/i', $text, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Extrahiert Update-ZIP-URL und SHA-256-Prüfsumme aus einem GitHub-Release-Objekt.
     * Bevorzugt explizite Update-Pakete und autoritative Asset-Digests der GitHub API.
     */
    protected static function parseGitHubReleaseAsset(array $remoteData): array {
        $body = (string)($remoteData['body'] ?? '');
        $zipUrl = (string)($remoteData['zipball_url'] ?? '');
        $sha256 = self::extractSha256($body, 'update');

        $updateAsset = null;
        $updateDigest = '';
        $fallbackZip = null;
        $fallbackDigest = '';

        foreach ($remoteData['assets'] ?? [] as $asset) {
            if (!is_array($asset) || empty($asset['browser_download_url'])) {
                continue;
            }
            $assetName = (string)($asset['name'] ?? '');
            $assetDigest = '';
            if (!empty($asset['digest']) && preg_match('/(?:sha256:)?([a-f0-9]{64})/i', (string)$asset['digest'], $dm)) {
                $assetDigest = strtolower($dm[1]);
            }

            if (preg_match('/\.zip$/i', $assetName)) {
                if (preg_match('/(^|[\-_])update([\-_]|\.zip$)/i', $assetName)) {
                    $updateAsset = $asset['browser_download_url'];
                    $updateDigest = $assetDigest;
                    break;
                }
                if ($fallbackZip === null) {
                    $fallbackZip = $asset['browser_download_url'];
                    $fallbackDigest = $assetDigest;
                }
            }
        }

        if ($updateAsset !== null) {
            $zipUrl = $updateAsset;
            if ($updateDigest !== '') {
                $sha256 = $updateDigest;
            }
        } elseif ($fallbackZip !== null) {
            if ($fallbackDigest !== '') {
                $sha256 = $fallbackDigest;
            }
            if ($sha256 !== '') {
                $zipUrl = $fallbackZip;
            }
        }

        if ($sha256 === '') {
            $sha256 = self::extractSha256($body);
        }

        return ['zip_url' => $zipUrl, 'sha256' => $sha256];
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
     * Normalisiert ein GitHub-Repository (akzeptiert 'owner/repo', GitHub-URLs etc.).
     */
    public static function normalizeGitHubRepo(?string $repo): ?string {
        if (empty($repo) || !is_string($repo)) {
            return null;
        }
        $repo = trim($repo);
        if (preg_match('~github\.com[:/]([^/]+/[^/]+?)(?:\.git|/|#|$)~i', $repo, $m)) {
            $repo = $m[1];
        }
        $clean = trim($repo, '/');
        if (preg_match('/^[a-zA-Z0-9_\.-]+\/[a-zA-Z0-9_\.-]+$/', $clean)) {
            return $clean;
        }
        return null;
    }

    /**
     * Prüft ein einzelnes Plugin auf verfügbare Updates (GitHub Releases oder Update-URL).
     * @param string $slug Eindeutiger Slug des Plugins
     * @param array|null $plugin Optionale Manifestdaten des Plugins
     * @param bool $forceRefresh
     * @return array|null
     */
    public static function checkSinglePluginUpdate(string $slug, ?array $plugin = null, bool $forceRefresh = false): ?array {
        if ($plugin === null && class_exists('PluginManager')) {
            $plugin = PluginManager::getPlugin($slug);
        }
        if (!$plugin || !is_array($plugin)) {
            return null;
        }

        $repo = self::normalizeGitHubRepo($plugin['github_repo'] ?? $plugin['repository'] ?? null);
        $updateUrl = $plugin['update_url'] ?? null;
        $installedVer = (string)($plugin['version'] ?? '1.0.0');
        $cacheFile = self::getCacheDir() . '/plugins_version.json';
        $cachedAll = [];

        if (file_exists($cacheFile)) {
            $cachedRaw = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cachedRaw)) {
                $cachedAll = $cachedRaw;
            }
        }

        $now = time();

        // Cache prüfen
        if (!$forceRefresh && isset($cachedAll[$slug]) && ($now - ($cachedAll[$slug]['checked_at'] ?? 0)) < self::CACHE_LIFETIME) {
            $item = $cachedAll[$slug];
            $item['installed_version'] = $installedVer;
            $item['update_available'] = version_compare($installedVer, $item['latest_version'] ?? '1.0.0', '<');
            return $item;
        }

        $item = null;

        // 1. GitHub Releases abfragen
        if (!empty($repo)) {
            $remoteData = self::fetchGitHubLatestRelease($repo);
            if ($remoteData && !empty($remoteData['tag_name'])) {
                $latestVer = ltrim($remoteData['tag_name'], 'vV');

                // Asset-ZIP oder Zipball suchen
                $zipUrl = '';
                $sha256 = '';
                $updateAsset = null;
                $fallbackZip = null;

                foreach ($remoteData['assets'] ?? [] as $asset) {
                    if (!is_array($asset) || empty($asset['browser_download_url'])) {
                        continue;
                    }
                    $assetName = (string)($asset['name'] ?? '');

                    $assetDigest = '';
                    if (!empty($asset['digest']) && preg_match('/(?:sha256:)?([a-f0-9]{64})/i', (string)$asset['digest'], $dm)) {
                        $assetDigest = strtolower($dm[1]);
                    }

                    if (preg_match('/\.zip$/i', $assetName)) {
                        if (preg_match('/(^|[\-_])' . preg_quote($slug, '/') . '([\-_]|\.zip$)/i', $assetName) || preg_match('/(^|[\-_])plugin([\-_]|\.zip$)/i', $assetName) || preg_match('/(^|[\-_])update([\-_]|\.zip$)/i', $assetName)) {
                            $updateAsset = $asset['browser_download_url'];
                            if ($assetDigest !== '') $sha256 = $assetDigest;
                            break;
                        }
                        if ($fallbackZip === null) {
                            $fallbackZip = $asset['browser_download_url'];
                            if ($assetDigest !== '') $sha256 = $assetDigest;
                        }
                    }
                }

                if ($updateAsset !== null) {
                    $zipUrl = $updateAsset;
                } elseif ($fallbackZip !== null) {
                    $zipUrl = $fallbackZip;
                } elseif (!empty($remoteData['zipball_url'])) {
                    // Fallback: GitHub Source Code Zipball
                    $zipUrl = $remoteData['zipball_url'];
                }

                if ($sha256 === '') {
                    $sha256 = self::extractSha256((string)($remoteData['body'] ?? ''));
                }

                $item = [
                    'slug'              => $slug,
                    'name'              => $plugin['name'] ?? ucfirst($slug),
                    'installed_version' => $installedVer,
                    'latest_version'    => $latestVer,
                    'repo'              => $repo,
                    'release_url'       => $remoteData['html_url'] ?? "https://github.com/{$repo}/releases",
                    'zip_url'           => $zipUrl,
                    'sha256'            => $sha256,
                    'changelog'         => (string)($remoteData['body'] ?? ''),
                    'update_available'  => version_compare($installedVer, $latestVer, '<'),
                    'checked_at'        => $now
                ];
            }
        } elseif (!empty($updateUrl) && preg_match('~^https://~i', $updateUrl)) {
            // 2. Custom JSON Manifest abfragen
            $remoteData = self::fetchJsonFromUrl($updateUrl);
            if ($remoteData && !empty($remoteData['latest_version'])) {
                $latestVer = ltrim((string)$remoteData['latest_version'], 'vV');
                $item = [
                    'slug'              => $slug,
                    'name'              => $plugin['name'] ?? ucfirst($slug),
                    'installed_version' => $installedVer,
                    'latest_version'    => $latestVer,
                    'repo'              => '',
                    'release_url'       => $remoteData['changelog_url'] ?? $remoteData['release_url'] ?? $updateUrl,
                    'zip_url'           => $remoteData['download_url'] ?? $remoteData['zip_url'] ?? '',
                    'sha256'            => self::extractSha256((string)($remoteData['sha256'] ?? $remoteData['checksum'] ?? '')),
                    'changelog'         => (string)($remoteData['changelog'] ?? ''),
                    'update_available'  => version_compare($installedVer, $latestVer, '<'),
                    'checked_at'        => $now
                ];
            }
        }

        if ($item) {
            $cachedAll[$slug] = $item;
            @file_put_contents($cacheFile, json_encode($cachedAll));
            return $item;
        }

        return null;
    }

    /**
     * Leert den Plugin-Update-Cache (optional für ein bestimmtes Plugin).
     */
    public static function clearPluginUpdateCache(?string $slug = null): void {
        $cacheFile = self::getCacheDir() . '/plugins_version.json';
        if (!file_exists($cacheFile)) {
            return;
        }
        if ($slug === null) {
            @unlink($cacheFile);
            return;
        }
        $cached = json_decode((string)@file_get_contents($cacheFile), true);
        if (is_array($cached) && isset($cached[$slug])) {
            unset($cached[$slug]);
            @file_put_contents($cacheFile, json_encode($cached));
        }
    }

    /**
     * Prüft alle installierten Plugins auf verfügbare Git-Updates.
     * @param array $allPlugins Ergebnis aus PluginManager::getAllPlugins($data)
     * @param bool $forceRefresh
     * @return array [ slug => [ 'update_available' => bool, 'installed_version' => string, 'latest_version' => string, 'repo' => string, 'release_url' => string, 'zip_url' => string, 'sha256' => string ], ... ]
     */
    public static function checkPluginUpdates(array $allPlugins, bool $forceRefresh = false): array {
        $updates = [];

        foreach ($allPlugins as $slug => $plugin) {
            $item = self::checkSinglePluginUpdate($slug, $plugin, $forceRefresh);
            if ($item && !empty($item['update_available'])) {
                $updates[$slug] = $item;
            }
        }

        return $updates;
    }

    /**
     * Lädt JSON-Metadaten sicher von einer beliebigen HTTPS-URL.
     */
    protected static function fetchJsonFromUrl(string $url): ?array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $curlOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => 'WeaveCMS-PluginUpdateChecker/' . (defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0'),
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ];
            if (defined('CURLOPT_SSL_OPTIONS') && defined('CURLSSLOPT_NATIVE_CA')) {
                $curlOpts[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
            }
            curl_setopt_array($ch, $curlOpts);
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode === 200 && is_string($body)) {
                $parsed = json_decode($body, true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => "User-Agent: WeaveCMS-PluginUpdateChecker\r\n",
                    'timeout' => 4
                ],
                'ssl' => [
                    'verify_peer' => true
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
     * Führt eine sichere HTTP-Abfrage an die GitHub API durch (max. 4 Sek. Timeout).
     * Optional kann ein bestimmtes Release-Tag (z. B. 'v1.3.4.4') abgefragt werden.
     */
    protected static function fetchGitHubLatestRelease(string $repo, ?string $tag = null): ?array {
        $cleanRepo = trim($repo, '/');
        // Validierung des GitHub Repo-Formats: owner/repo
        if (!preg_match('/^[a-zA-Z0-9_\.-]+\/[a-zA-Z0-9_\.-]+$/', $cleanRepo)) {
            return null;
        }

        if ($tag !== null && trim($tag) !== '') {
            $url = "https://api.github.com/repos/{$cleanRepo}/releases/tags/" . rawurlencode(trim($tag));
        } else {
            $url = "https://api.github.com/repos/{$cleanRepo}/releases/latest";
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $curlOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => 'WeaveCMS-UpdateChecker/' . (defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0'),
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ];
            // Auf Windows / XAMPP das native Windows-Zertifikats-Store nutzen
            if (defined('CURLOPT_SSL_OPTIONS') && defined('CURLSSLOPT_NATIVE_CA')) {
                $curlOpts[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
            }
            curl_setopt_array($ch, $curlOpts);
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200 && is_string($body)) {
                $parsed = json_decode($body, true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        // Fallback: file_get_contents mit Stream-Kontext
        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => "User-Agent: WeaveCMS-UpdateChecker\r\n",
                    'timeout' => 4
                ],
                'ssl' => [
                    'verify_peer' => true
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
