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
            'section_title'               => 'Gaming, Fastadun & Server',
            'section_subtitle'            => 'Online-Registrierung, Server-Status, Client-Updates und Web-Browsergames.',
            'enable_public_server_status' => true,
            'default_ssh_port'            => 22,
            'api_enabled'                 => true,
            'api_key'                     => 'wgk_' . bin2hex(random_bytes(16))
        ];

        if (!file_exists($file)) {
            return $default;
        }

        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? array_merge($default, $data) : $default;
    }

    public static function saveConfig(array $config): bool {
        $file = self::getConfigFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return (file_put_contents($file, $json) !== false);
    }
}
