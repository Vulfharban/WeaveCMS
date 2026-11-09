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
        if (!file_exists($file)) {
            return self::getDefaultData();
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : self::getDefaultData();
    }

    public static function saveData(array $data): bool {
        $file = self::getRolloutFilePath();
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

    public static function getDefaultData(): array {
        return [
            'games' => [
                [
                    'slug'        => 'voxel-adventure',
                    'title'       => 'Voxel Adventure (Server/Client)',
                    'category'    => 'client_server', // client_server, browser, generic
                    'genre'       => 'Multiplayer Voxel Sandbox / RPG',
                    'description' => 'Voxel-Projekt mit nahtloser Server/Client-Architektur, dynamischen Welten und modularem Netzwerkprotokoll.',
                    'icon'        => 'view_in_ar',
                    'banner'      => '',
                    'active'      => true,
                    'created_at'  => date('Y-m-d H:i:s')
                ],
                [
                    'slug'        => 'space-orbit',
                    'title'       => 'Space Orbit (Browsergame)',
                    'category'    => 'browser',
                    'genre'       => '2D Arcade Space Shooter (HTML5/Canvas)',
                    'description' => 'Reines Browsergame: Direkt im Webbrowser spielbar ohne Download. Mit Highscore-Synchronisation.',
                    'icon'        => 'rocket_launch',
                    'banner'      => '',
                    'embed_url'   => '',
                    'active'      => true,
                    'created_at'  => date('Y-m-d H:i:s')
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
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($gameData['slug'] ?? ''));
        if (empty($slug)) {
            return false;
        }

        $existing = false;
        foreach ($data['games'] as &$g) {
            if (($g['slug'] ?? '') === $slug) {
                $g['title'] = trim($gameData['title'] ?? $g['title']);
                $g['category'] = $gameData['category'] ?? $g['category'];
                $g['genre'] = trim($gameData['genre'] ?? ($g['genre'] ?? ''));
                $g['description'] = trim($gameData['description'] ?? '');
                $g['icon'] = trim($gameData['icon'] ?? 'sports_esports');
                $g['active'] = !empty($gameData['active']);
                if (isset($gameData['embed_url'])) {
                    $g['embed_url'] = trim($gameData['embed_url']);
                }
                $existing = true;
                break;
            }
        }
        unset($g);

        if (!$existing) {
            $data['games'][] = [
                'slug'        => $slug,
                'title'       => trim($gameData['title'] ?? ucfirst($slug)),
                'category'    => $gameData['category'] ?? 'client_server',
                'genre'       => trim($gameData['genre'] ?? ''),
                'description' => trim($gameData['description'] ?? ''),
                'icon'        => trim($gameData['icon'] ?? 'sports_esports'),
                'embed_url'   => trim($gameData['embed_url'] ?? ''),
                'active'      => !empty($gameData['active']),
                'created_at'  => date('Y-m-d H:i:s')
            ];
        }

        return self::saveData($data);
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

