<?php
/**
 * ============================================================================
 * Weave Games – GameConfigManager
 * ============================================================================
 * Verwaltet globale Einstellungen für das Weave Games Plugin.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class GameConfigManager {

    public static function getConfigFilePath(): string {
        return dirname(__DIR__, 2) . '/data/games_config.json';
    }

    public static function getConfig(): array {
        $file = self::getConfigFilePath();
        $default = [
            'allow_self_registration'     => true,
            'show_in_main_nav'            => true,
            'section_title'               => 'Gaming & Spieleserver',
            'section_subtitle'            => 'Online-Registrierung, Server-Status, Client-Updates und Web-Browsergames.',
            'enable_public_server_status' => true,
            'default_ssh_port'            => 22,
            'api_enabled'                 => true,
            'api_key'                     => 'wgk_' . bin2hex(random_bytes(16)),
            'home_layout'                 => 'ambient_blur',
            'home_columns'                => 3,
            'home_max_items'              => 6,
            'home_show_filters'           => true,
            'home_sort'                   => 'manual',
            'home_play_modal'             => true,
            'home_show_server_status'     => true
        ];

        if (class_exists('StorageManager')) {
            $data = StorageManager::loadPluginData('games_config', null, $file);
            return is_array($data) ? array_merge($default, $data) : $default;
        }

        if (!file_exists($file)) {
            return $default;
        }

        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? array_merge($default, $data) : $default;
    }

    public static function saveConfig(array $config): bool {
        $file = self::getConfigFilePath();
        if (class_exists('StorageManager')) {
            return StorageManager::savePluginData('games_config', $config, $file);
        }
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return (file_put_contents($file, $json) !== false);
    }
}

