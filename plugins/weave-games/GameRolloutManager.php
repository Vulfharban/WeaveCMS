<?php
/**
 * ============================================================================
 * Weave Games – GameRolloutManager
 * ============================================================================
 * Verwaltet Spiele-Kataloge, Client-Versionen, Update-Rollouts
 * und Patch-Verteilungen für Desktop-Clients (Voxel & Multiplayer RPGs),
 * Browsergames (WebGL/HTML5) und universelle Spiel-Engines.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class GameRolloutManager {

    public static function getRolloutFilePath(): string {
        return dirname(__DIR__, 2) . '/data/games_rollout.json';
    }

    public static function getData(): array {
        $file = self::getRolloutFilePath();
        if (class_exists('StorageManager')) {
            $data = StorageManager::loadPluginData('games_rollout', null, $file);
            return is_array($data) ? $data : self::getDefaultData();
        }
        if (!file_exists($file)) {
            return self::getDefaultData();
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : self::getDefaultData();
    }

    public static function saveData(array $data): bool {
        $file = self::getRolloutFilePath();
        if (class_exists('StorageManager')) {
            return StorageManager::savePluginData('games_rollout', $data, $file);
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    /**
     * Liefert alle unterstützten Laufzeit-/Technologie-Typen für Spiele & Anwendungen.
     */
    public static function getRuntimeOptions(): array {
        return [
            'html5_js'    => ['label' => 'HTML5 / JavaScript / Canvas', 'badge' => 'JS / HTML5', 'color' => '#d97706'],
            'wasm'        => ['label' => 'WebAssembly (WASM)', 'badge' => 'WASM', 'color' => '#7c3aed'],
            'rust_wasm'   => ['label' => 'Rust → WebAssembly (WASM)', 'badge' => 'Rust + WASM', 'color' => '#ea580c'],
            'cpp_wasm'    => ['label' => 'C / C++ → Emscripten / WASM', 'badge' => 'C++ / WASM', 'color' => '#2563eb'],
            'java_web'    => ['label' => 'Java → Web (CheerpJ / TeaVM)', 'badge' => 'Java Web', 'color' => '#dc2626'],
            'native_rust' => ['label' => 'Rust (Desktop-Client / Server)', 'badge' => 'Rust Native', 'color' => '#c2410c'],
            'native_cpp'  => ['label' => 'C / C++ (Desktop-Client / Binary)', 'badge' => 'C++ Native', 'color' => '#1d4ed8'],
            'native_java' => ['label' => 'Java (Desktop .jar / Installer)', 'badge' => 'Java Desktop', 'color' => '#b91c1c'],
            'web_app'     => ['label' => 'Externe Web-App / Streaming', 'badge' => 'Web-App', 'color' => '#0d9488'],
        ];
    }

    /**
     * Liefert alle Kategorien für Spiele & Anwendungen.
     */
    public static function getCategoryOptions(): array {
        return [
            'browser'       => 'Browsergame (JS / HTML5)',
            'wasm'          => 'WASM / Rust / C++ (Browser)',
            'client_server' => 'Desktop-Client & Server',
            'desktop_app'   => 'Desktop-Spiel / Anwendung',
            'web_app'       => 'Web-Anwendung / Tool',
        ];
    }

    /**
     * Prüft, ob ein Spiel direkt im Browser gestartet werden kann.
     */
    public static function isPlayableInBrowser(array $game): bool {
        return trim((string)($game['embed_url'] ?? '')) !== '' || (($game['slug'] ?? '') === 'space-orbit');
    }

    /**
     * Prüft, ob eine Embed-URL für Iframe/Links zulässig ist (relativer Pfad oder http/https).
     */
    public static function isSafeGameUrl(string $url): bool {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        $lower = strtolower($url);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, 'vbscript:')) {
            return false;
        }
        if (str_contains($url, '..')) {
            return false;
        }
        if (str_starts_with($url, 'uploads/') || str_starts_with($url, 'downloads/') || str_starts_with($url, './') || str_starts_with($url, '/')) {
            return true;
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
            return in_array($scheme, ['http', 'https'], true);
        }
        return false;
    }

    public static function getDefaultData(): array {
        return [
            'games' => [
                [
                    'slug'                => 'voxel-adventure',
                    'title'               => 'Voxel Adventure (Server/Client)',
                    'category'            => 'client_server',
                    'runtime_type'        => 'native_rust',
                    'tech_tags'           => ['Rust', '3D Voxel', 'Multiplayer', 'Client/Server'],
                    'genre'               => 'Multiplayer Voxel Sandbox / RPG',
                    'description'         => 'Voxel-Projekt mit nahtloser Server/Client-Architektur, dynamischen Welten und modularem Netzwerkprotokoll.',
                    'system_requirements' => 'Windows 10/11 (x64) oder Linux (x64), OpenGL 3.3 / Vulkan',
                    'icon'                => 'view_in_ar',
                    'banner'              => '',
                    'embed_url'           => '',
                    'embed_mode'          => 'modal',
                    'aspect_ratio'        => '16:9',
                    'enable_coop_coep'    => false,
                    'show_on_home'        => true,
                    'featured'            => true,
                    'sort_order'          => 1,
                    'active'              => true,
                    'created_at'          => date('Y-m-d H:i:s')
                ],
                [
                    'slug'                => 'space-orbit',
                    'title'               => 'Space Orbit (Browsergame)',
                    'category'            => 'browser',
                    'runtime_type'        => 'html5_js',
                    'tech_tags'           => ['HTML5', 'Canvas', 'JavaScript'],
                    'genre'               => '2D Arcade Space Shooter (HTML5/Canvas)',
                    'description'         => 'Reines Browsergame: Direkt im Webbrowser spielbar ohne Download. 5 Kampagnen-Level mit Waffen-Upgrades und Sektor-Bossen.',
                    'system_requirements' => 'Jeder moderne Webbrowser (Desktop & Touch)',
                    'icon'                => 'rocket_launch',
                    'banner'              => '',
                    'embed_url'           => '',
                    'embed_mode'          => 'modal',
                    'aspect_ratio'        => '16:9',
                    'enable_coop_coep'    => false,
                    'show_on_home'        => true,
                    'featured'            => false,
                    'sort_order'          => 2,
                    'active'              => true,
                    'created_at'          => date('Y-m-d H:i:s')
                ]
            ],
            'releases' => [
                [
                    'id'           => 'rel_voxel_0_9_0',
                    'game_slug'    => 'voxel-adventure',
                    'version'      => '0.9.0',
                    'channel'      => 'stable', // stable, beta, nightly
                    'platform'     => 'windows_x64', // windows_x64, linux_x64, macos, browser
                    'download_url' => 'downloads/games/voxel-adventure-v0.9.0-windows-x64.zip',
                    'file_size'    => '45.2 MB',
                    'sha256'       => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                    'mandatory'    => false,
                    'patch_notes'  => "- Initialer Client-Rollout mit prozeduraler Weltengenerierung\n- Server/Client Netzwerkprotokoll v1\n- Performance- und Render-Optimierungen",
                    'released_at'  => date('Y-m-d H:i:s')
                ]
            ]
        ];
    }

    public static function getGames(): array {
        $d = self::getData();
        return $d['games'] ?? [];
    }

    public static function getGame(string $slug): ?array {
        $games = self::getGames();
        foreach ($games as $g) {
            if (($g['slug'] ?? '') === $slug) {
                return $g;
            }
        }
        return null;
    }

    public static function saveGame(array $gameData): bool {
        $data = self::getData();
        $rawSlug = trim((string)($gameData['slug'] ?? ''));
        if ($rawSlug === '') {
            $rawSlug = trim((string)($gameData['title'] ?? ''));
        }
        $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($rawSlug));
        $slug = trim((string)preg_replace('/-+/', '-', $slug), '-_');
        if (empty($slug)) {
            return false;
        }

        // Tech-Tags normalisieren
        $rawTags = $gameData['tech_tags'] ?? [];
        $tags = [];
        if (is_string($rawTags)) {
            foreach (explode(',', $rawTags) as $t) {
                $t = trim($t);
                if ($t !== '' && !in_array($t, $tags, true)) {
                    $tags[] = $t;
                }
            }
        } elseif (is_array($rawTags)) {
            foreach ($rawTags as $t) {
                $t = trim((string)$t);
                if ($t !== '' && !in_array($t, $tags, true)) {
                    $tags[] = $t;
                }
            }
        }

        $embedMode = trim((string)($gameData['embed_mode'] ?? 'modal'));
        if (!in_array($embedMode, ['modal', 'inline', 'fullscreen', 'new_tab'], true)) {
            $embedMode = 'modal';
        }
        $aspectRatio = trim((string)($gameData['aspect_ratio'] ?? '16:9'));
        if (!in_array($aspectRatio, ['16:9', '4:3', '1:1', 'auto'], true)) {
            $aspectRatio = '16:9';
        }
        $runtimeType = trim((string)($gameData['runtime_type'] ?? 'html5_js'));
        $runtimes = self::getRuntimeOptions();
        if (!isset($runtimes[$runtimeType])) {
            $runtimeType = 'html5_js';
        }

        $embedUrl = trim((string)($gameData['embed_url'] ?? ''));
        if ($embedUrl !== '' && !self::isSafeGameUrl($embedUrl)) {
            $embedUrl = '';
        }

        $existing = false;
        foreach ($data['games'] as &$g) {
            if (($g['slug'] ?? '') === $slug) {
                $g['title'] = trim((string)($gameData['title'] ?? $g['title']));
                $g['category'] = trim((string)($gameData['category'] ?? ($g['category'] ?? 'browser')));
                $g['runtime_type'] = $runtimeType;
                $g['tech_tags'] = $tags;
                $g['genre'] = trim((string)($gameData['genre'] ?? ($g['genre'] ?? '')));
                $g['description'] = trim((string)($gameData['description'] ?? ''));
                $g['system_requirements'] = trim((string)($gameData['system_requirements'] ?? ($g['system_requirements'] ?? '')));
                $g['icon'] = trim((string)($gameData['icon'] ?? ($g['icon'] ?? '🎮'))) ?: '🎮';
                if (isset($gameData['banner'])) {
                    $g['banner'] = trim((string)$gameData['banner']);
                }
                if (isset($gameData['embed_url'])) {
                    $g['embed_url'] = $embedUrl;
                }
                $g['embed_mode'] = $embedMode;
                $g['aspect_ratio'] = $aspectRatio;
                $g['enable_coop_coep'] = !empty($gameData['enable_coop_coep']);
                $g['show_on_home'] = !empty($gameData['show_on_home']);
                $g['featured'] = !empty($gameData['featured']);
                $g['sort_order'] = (int)($gameData['sort_order'] ?? ($g['sort_order'] ?? 50));
                $g['active'] = !empty($gameData['active']);
                $g['updated_at'] = date('Y-m-d H:i:s');
                $existing = true;
                break;
            }
        }
        unset($g);

        if (!$existing) {
            $data['games'][] = [
                'slug'                => $slug,
                'title'               => trim((string)($gameData['title'] ?? ucfirst($slug))),
                'category'            => trim((string)($gameData['category'] ?? 'browser')),
                'runtime_type'        => $runtimeType,
                'tech_tags'           => $tags,
                'genre'               => trim((string)($gameData['genre'] ?? '')),
                'description'         => trim((string)($gameData['description'] ?? '')),
                'system_requirements' => trim((string)($gameData['system_requirements'] ?? '')),
                'icon'                => trim((string)($gameData['icon'] ?? '🎮')) ?: '🎮',
                'banner'              => trim((string)($gameData['banner'] ?? '')),
                'embed_url'           => $embedUrl,
                'embed_mode'          => $embedMode,
                'aspect_ratio'        => $aspectRatio,
                'enable_coop_coep'    => !empty($gameData['enable_coop_coep']),
                'show_on_home'        => !empty($gameData['show_on_home']),
                'featured'            => !empty($gameData['featured']),
                'sort_order'          => (int)($gameData['sort_order'] ?? 50),
                'active'              => !empty($gameData['active']),
                'created_at'          => date('Y-m-d H:i:s')
            ];
        }

        return self::saveData($data);
    }

    /**
     * Lädt ein ZIP-Paket für ein JS-/HTML5-/WASM-/Java-Web-Spiel hoch und entpackt
     * ausschließlich zulässige statische Web-Assets sicher nach uploads/games/<slug>/.
     */
    public static function uploadGameZip(string $slug, array $fileInfo, ?string &$error = null): ?string {
        if (empty($fileInfo['tmp_name']) || ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!class_exists('ZipArchive')) {
            $error = 'Die PHP-Erweiterung ZipArchive ist nicht verfügbar.';
            return null;
        }
        if (($fileInfo['size'] ?? 0) > 100 * 1024 * 1024) {
            $error = 'Das ZIP-Archiv darf maximal 100 MB groß sein.';
            return null;
        }
        $ext = strtolower(pathinfo((string)($fileInfo['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            $error = 'Bitte eine gültige .zip-Datei hochladen.';
            return null;
        }

        $safeSlug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($slug));
        $safeSlug = trim((string)$safeSlug, '-_') ?: 'game';

        $allowedExt = [
            'html', 'htm', 'js', 'mjs', 'cjs', 'css', 'wasm', 'json', 'map',
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
            'mp3', 'ogg', 'wav', 'm4a', 'mp4', 'webm',
            'woff', 'woff2', 'ttf', 'otf', 'bin', 'data', 'pck',
            'glsl', 'wgsl', 'txt', 'xml', 'csv', 'jar', 'class', 'properties',
            'atlas', 'fnt', 'obj', 'mtl', 'glb', 'gltf'
        ];
        $blockedExt = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'cgi', 'pl', 'sh', 'bat', 'cmd', 'exe', 'htaccess'];

        $zip = new ZipArchive();
        if ($zip->open($fileInfo['tmp_name']) !== true) {
            $error = 'Das ZIP-Archiv konnte nicht geöffnet werden.';
            return null;
        }

        $root = dirname(__DIR__, 2);
        $gamesBaseDir = $root . '/uploads/games';
        if (!is_dir($gamesBaseDir)) {
            @mkdir($gamesBaseDir, 0755, true);
        }
        // PHP-Ausführung in uploads/games/ serverseitig unterbinden
        $htaccess = "# PHP-Ausführung in hochgeladenen Spielen deaktivieren\n<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|php8|phps|phar|cgi|pl|exe)$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n    <IfModule !mod_authz_core.c>\n        Order Allow,Deny\n        Deny from all\n    </IfModule>\n</FilesMatch>\n";
        @file_put_contents($gamesBaseDir . '/.htaccess', $htaccess);

        $targetDir = $gamesBaseDir . '/' . $safeSlug;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $indexCandidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if ($entryName === '' || str_ends_with($entryName, '/')) {
                continue;
            }
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/')) {
                continue;
            }
            $baseName = basename($entryName);
            $entryExt = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
            if (in_array($entryExt, $blockedExt, true) || ($entryExt !== '' && !in_array($entryExt, $allowedExt, true))) {
                continue;
            }

            $destPath = $targetDir . '/' . ltrim($entryName, '/');
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $stream = $zip->getStream($zip->getNameIndex($i));
            if ($stream) {
                $out = @fopen($destPath, 'wb');
                if ($out) {
                    stream_copy_to_stream($stream, $out);
                    fclose($out);
                }
                fclose($stream);
                if (strtolower($baseName) === 'index.html' || strtolower($baseName) === 'index.htm') {
                    $indexCandidates[] = ltrim($entryName, '/');
                }
            }
        }
        $zip->close();

        if (empty($indexCandidates)) {
            $error = 'ZIP entpackt, aber keine index.html gefunden. Bitte Startpfad manuell als Einbindungs-URL angeben.';
            return 'uploads/games/' . $safeSlug . '/index.html';
        }

        // Kürzesten Pfad (z. B. Root index.html vor Unterordner) bevorzugen
        usort($indexCandidates, static fn($a, $b) => strlen($a) <=> strlen($b));
        return 'uploads/games/' . $safeSlug . '/' . $indexCandidates[0];
    }

    /**
     * Lädt ein Cover-/Vorschaubild für ein Spiel hoch (uploads/games/covers/).
     */
    public static function uploadCoverImage(string $slug, array $fileInfo, ?string &$error = null): ?string {
        if (empty($fileInfo['tmp_name']) || ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($fileInfo['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = 'Das Cover-Bild darf maximal 5 MB groß sein.';
            return null;
        }
        $ext = strtolower(pathinfo((string)($fileInfo['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $error = 'Erlaubte Cover-Formate: JPG, PNG, WebP oder GIF.';
            return null;
        }
        $imgInfo = @getimagesize($fileInfo['tmp_name']);
        if (!$imgInfo) {
            $error = 'Die hochgeladene Datei ist kein gültiges Bild.';
            return null;
        }

        $safeSlug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($slug));
        $safeSlug = trim((string)$safeSlug, '-_') ?: 'game';

        $root = dirname(__DIR__, 2);
        $coverDir = $root . '/uploads/games/covers';
        if (!is_dir($coverDir)) {
            @mkdir($coverDir, 0755, true);
        }

        $filename = $safeSlug . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetFile = $coverDir . '/' . $filename;
        if (!@move_uploaded_file($fileInfo['tmp_name'], $targetFile)) {
            if (!@copy($fileInfo['tmp_name'], $targetFile)) {
                $error = 'Cover-Bild konnte nicht gespeichert werden.';
                return null;
            }
        }
        return 'uploads/games/covers/' . $filename;
    }

    public static function deleteGame(string $slug): bool {
        $data = self::getData();
        $data['games'] = array_values(array_filter($data['games'] ?? [], fn($g) => ($g['slug'] ?? '') !== $slug));
        // Auch zugehörige Releases entfernen
        $data['releases'] = array_values(array_filter($data['releases'] ?? [], fn($r) => ($r['game_slug'] ?? '') !== $slug));
        return self::saveData($data);
    }

    public static function getReleases(?string $gameSlug = null): array {
        $data = self::getData();
        $releases = $data['releases'] ?? [];
        if ($gameSlug !== null) {
            $releases = array_values(array_filter($releases, fn($r) => ($r['game_slug'] ?? '') === $gameSlug));
        }
        return $releases;
    }

    public static function getLatestRelease(string $gameSlug, string $platform = 'windows_x64', string $channel = 'stable'): ?array {
        $releases = self::getReleases($gameSlug);
        $matches = array_filter($releases, function($r) use ($platform, $channel) {
            $platMatch = ($r['platform'] === $platform || $r['platform'] === 'all');
            $chanMatch = ($r['channel'] === $channel);
            return $platMatch && $chanMatch;
        });

        if (empty($matches)) {
            // Fallback auf stable channel mit beliebiger Plattform
            $matches = array_filter($releases, fn($r) => ($r['channel'] === $channel));
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, function($a, $b) {
            return version_compare($b['version'] ?? '0.0.0', $a['version'] ?? '0.0.0');
        });

        return reset($matches);
    }

    public static function checkUpdate(string $gameSlug, string $clientVersion, string $platform = 'windows_x64', string $channel = 'stable'): array {
        $latest = self::getLatestRelease($gameSlug, $platform, $channel);
        if (!$latest) {
            return [
                'update_available' => false,
                'message'          => "Kein Release für {$gameSlug} ({$platform}/{$channel}) hinterlegt."
            ];
        }

        $latestVer = $latest['version'] ?? '0.0.0';
        $hasUpdate = version_compare($latestVer, $clientVersion, '>');

        return [
            'update_available' => $hasUpdate,
            'current_version'  => $clientVersion,
            'latest_version'   => $latestVer,
            'mandatory'        => !empty($latest['mandatory']),
            'download_url'     => $latest['download_url'] ?? '',
            'file_size'        => $latest['file_size'] ?? '',
            'sha256'           => $latest['sha256'] ?? '',
            'patch_notes'      => $latest['patch_notes'] ?? '',
            'released_at'      => $latest['released_at'] ?? ''
        ];
    }

    public static function addRelease(array $data): ?array {
        $gameSlug = trim($data['game_slug'] ?? '');
        $version = trim($data['version'] ?? '');
        if (empty($gameSlug) || empty($version)) {
            return null;
        }

        $all = self::getData();
        $id = 'rel_' . $gameSlug . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $version) . '_' . bin2hex(random_bytes(2));

        $newRel = [
            'id'           => $id,
            'game_slug'    => $gameSlug,
            'version'      => $version,
            'channel'      => $data['channel'] ?? 'stable',
            'platform'     => $data['platform'] ?? 'windows_x64',
            'download_url' => trim($data['download_url'] ?? ''),
            'file_size'    => trim($data['file_size'] ?? ''),
            'sha256'       => trim($data['sha256'] ?? ''),
            'mandatory'    => !empty($data['mandatory']),
            'patch_notes'  => trim($data['patch_notes'] ?? ''),
            'released_at'  => date('Y-m-d H:i:s')
        ];

        $all['releases'][] = $newRel;
        self::saveData($all);

        return $newRel;
    }

    public static function deleteRelease(string $id): bool {
        $all = self::getData();
        $initial = count($all['releases'] ?? []);
        $all['releases'] = array_values(array_filter($all['releases'] ?? [], fn($r) => ($r['id'] ?? '') !== $id));
        if (count($all['releases']) < $initial) {
            return self::saveData($all);
        }
        return false;
    }
}

