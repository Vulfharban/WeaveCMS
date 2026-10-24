<?php
/**
 * ============================================================================
 * WeaveCMS – Modulares Plugin- & Erweiterungssystem (PluginManager) - Phase 13
 * ============================================================================
 * Verwaltet:
 * - Automatische Entdeckung von Erweiterungen im Ordner /plugins
 * - Ereignis- & Filter-Hook-System (Actions & Filters)
 * - Plugin-Lebenszyklus (Aktivierung, Deaktivierung, Konfiguration)
 * - Sichere Isolation & Dependency-/Versionsprüfungen
 * ============================================================================
 */

class PluginManager {

    /**
     * Registrierte Action-Hooks: [ $hook => [ $priority => [ ['callback' => callable, 'args' => int], ... ] ] ]
     */
    protected static array $actions = [];

    /**
     * Registrierte Filter-Hooks: [ $hook => [ $priority => [ ['callback' => callable, 'args' => int], ... ] ] ]
     */
    protected static array $filters = [];

    /**
     * Bereits geladene Plugin-Slugs
     */
    protected static array $loadedPlugins = [];

    /**
     * Cache aller im Verzeichnis gefundenen Plugins
     */
    protected static ?array $discoveredCache = null;

    /**
     * Ermittelt den absoluten Pfad zum Plugins-Verzeichnis.
     * @return string
     */
    public static function getPluginsDir(): string {
        return dirname(__DIR__, 2) . '/plugins';
    }

    // =========================================================================
    // HOOK SYSTEM (ACTIONS & FILTERS)
    // =========================================================================

    /**
     * Registriert eine Action für ein bestimmtes Ereignis.
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @param int $acceptedArgs
     * @return void
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): void {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        if (!isset(self::$actions[$hook][$priority])) {
            self::$actions[$hook][$priority] = [];
        }
        if ($acceptedArgs === null) {
            try {
                if (is_array($callback)) {
                    $ref = new ReflectionMethod($callback[0], $callback[1]);
                } elseif (is_string($callback) && strpos($callback, '::') !== false) {
                    $ref = new ReflectionMethod($callback);
                } else {
                    $ref = new ReflectionFunction($callback);
                }
                $acceptedArgs = $ref->getNumberOfParameters();
            } catch (Throwable $e) {
                $acceptedArgs = 1;
            }
        }
        self::$actions[$hook][$priority][] = [
            'callback' => $callback,
            'args'     => $acceptedArgs
        ];
    }

    /**
     * Führt alle für einen Action-Hook registrierten Callbacks der Priorität nach aus.
     * @param string $hook
     * @param mixed ...$args
     * @return void
     */
    public static function doAction(string $hook, ...$args): void {
        if (empty(self::$actions[$hook])) {
            return;
        }

        $priorities = self::$actions[$hook];
        ksort($priorities, SORT_NUMERIC);

        foreach ($priorities as $callbacks) {
            foreach ($callbacks as $item) {
                $callable = $item['callback'];
                $accepted = $item['args'];
                $passedArgs = array_slice($args, 0, $accepted);
                call_user_func_array($callable, $passedArgs);
            }
        }
    }

    /**
     * Prüft, ob für einen Action-Hook mindestens ein Callback registriert ist.
     * @param string $hook
     * @return bool
     */
    public static function hasAction(string $hook): bool {
        return !empty(self::$actions[$hook]);
    }

    /**
     * Registriert einen Filter zur Transformation von Daten oder Inhalten.
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @param int|null $acceptedArgs
     * @return void
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): void {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        if (!isset(self::$filters[$hook][$priority])) {
            self::$filters[$hook][$priority] = [];
        }
        if ($acceptedArgs === null) {
            try {
                if (is_array($callback)) {
                    $ref = new ReflectionMethod($callback[0], $callback[1]);
                } elseif (is_string($callback) && strpos($callback, '::') !== false) {
                    $ref = new ReflectionMethod($callback);
                } else {
                    $ref = new ReflectionFunction($callback);
                }
                $acceptedArgs = $ref->getNumberOfParameters();
            } catch (Throwable $e) {
                $acceptedArgs = 1;
            }
        }
        self::$filters[$hook][$priority][] = [
            'callback' => $callback,
            'args'     => $acceptedArgs
        ];
    }

    /**
     * Wendet alle für einen Filter registrierten Transformationen sequentiell an.
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public static function applyFilters(string $hook, mixed $value, ...$args): mixed {
        if (empty(self::$filters[$hook])) {
            return $value;
        }

        $priorities = self::$filters[$hook];
        ksort($priorities, SORT_NUMERIC);

        foreach ($priorities as $callbacks) {
            foreach ($callbacks as $item) {
                $callable = $item['callback'];
                $accepted = $item['args'];
                $allArgs = array_merge([$value], $args);
                $passedArgs = array_slice($allArgs, 0, $accepted);
                $value = call_user_func_array($callable, $passedArgs);
            }
        }

        return $value;
    }

    /**
     * Prüft, ob für einen Filter-Hook mindestens ein Callback registriert ist.
     * @param string $hook
     * @return bool
     */
    public static function hasFilter(string $hook): bool {
        return !empty(self::$filters[$hook]);
    }

    // =========================================================================
    // PLUGIN-ENTDECKUNG & METADATEN (DISCOVERY)
    // =========================================================================

    /**
     * Scannt das Verzeichnis /plugins und liefert Metadaten aller validen Erweiterungen.
     * @param bool $forceRefresh
     * @return array [ slug => manifest_array, ... ]
     */
    public static function discover(bool $forceRefresh = false): array {
        if (self::$discoveredCache !== null && !$forceRefresh) {
            return self::$discoveredCache;
        }

        $baseDir = self::getPluginsDir();
        $plugins = [];

        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $items = @scandir($baseDir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }

            $pluginDir = $baseDir . '/' . $item;
            if (!is_dir($pluginDir)) {
                continue;
            }

            $manifestFile = $pluginDir . '/plugin.json';
            if (!file_exists($manifestFile)) {
                continue;
            }

            $rawContent = file_get_contents($manifestFile);
            $manifest = json_decode((string)$rawContent, true);

            if (!is_array($manifest)) {
                continue;
            }

            // Pflichtfelder und Fallbacks absichern
            $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($manifest['slug'] ?? $item));
            if (empty($slug)) {
                $slug = $item;
            }

            $manifest['slug'] = $slug;
            $manifest['name'] = $manifest['name'] ?? ucfirst($slug);
            $manifest['version'] = $manifest['version'] ?? '1.0.0';
            $manifest['author'] = $manifest['author'] ?? 'Unbekannt';
            $manifest['description'] = $manifest['description'] ?? '';
            $manifest['category'] = $manifest['category'] ?? 'general';
            $manifest['icon'] = $manifest['icon'] ?? 'extension';
            $manifest['entry_file'] = $manifest['entry_file'] ?? 'plugin.php';
            $manifest['settings_tab'] = $manifest['settings_tab'] ?? null;
            $manifest['requires_core'] = $manifest['requires_core'] ?? '>=0.4.0';
            $manifest['requires_plugins'] = is_array($manifest['requires_plugins'] ?? null) ? $manifest['requires_plugins'] : [];
            $manifest['plugin_dir'] = $pluginDir;
            $manifest['entry_path'] = $pluginDir . '/' . $manifest['entry_file'];
            $manifest['is_valid'] = file_exists($manifest['entry_path']);

            $plugins[$slug] = $manifest;
        }

        self::$discoveredCache = $plugins;
        return $plugins;
    }

    /**
     * Ermittelt die Daten eines einzelnen Plugins.
     * @param string $slug
     * @return array|null
     */
    public static function getPlugin(string $slug): ?array {
        $all = self::discover();
        return $all[$slug] ?? null;
    }

    /**
     * Liefert eine vollständige Liste aller installierten Plugins inklusive Aktivierungsstatus und Konfiguration.
     * @param array $data Gesamtdatenstruktur
     * @return array
     */
    public static function getAllPlugins(array $data): array {
        $discovered = self::discover();
        $activeList = self::getActiveSlugs($data);
        $configs = $data['plugins']['configs'] ?? [];

        $result = [];
        foreach ($discovered as $slug => $meta) {
            $item = $meta;
            $item['active'] = in_array($slug, $activeList, true);
            $item['config'] = $configs[$slug] ?? [];
            $result[$slug] = $item;
        }

        return $result;
    }

    // =========================================================================
    // LEBENSZYKLUS (AKTIVIERUNG / DEAKTIVIERUNG)
    // =========================================================================

    /**
     * Liefert die Liste aller aktiven Plugin-Slugs.
     * @param array $data
     * @return array
     */
    public static function getActiveSlugs(array $data): array {
        $active = $data['plugins']['active'] ?? [];
        return is_array($active) ? $active : [];
    }

    /**
     * Prüft, ob ein bestimmtes Plugin aktuell aktiv ist.
     * @param string $slug
     * @param array $data
     * @return bool
     */
    public static function isPluginActive(string $slug, array $data): bool {
        return in_array($slug, self::getActiveSlugs($data), true);
    }

    /**
     * Lädt und initialisiert alle aktiven Plugins für den aktuellen Seitenaufruf.
     * @param array &$data
     * @param string $context 'admin', 'frontend' oder 'all'
     * @return void
     */
    public static function loadActivePlugins(array &$data, string $context = 'all'): void {
        $discovered = self::discover();
        $activeSlugs = self::getActiveSlugs($data);

        foreach ($activeSlugs as $slug) {
            if (isset(self::$loadedPlugins[$slug])) {
                continue;
            }

            if (!isset($discovered[$slug])) {
                continue; // Plugin-Ordner existiert nicht mehr
            }

            $meta = $discovered[$slug];
            if (empty($meta['is_valid'])) {
                continue; // Einstiegsdatei fehlt
            }

            // Plugin-Datei einbinden
            try {
                require_once $meta['entry_path'];
                self::$loadedPlugins[$slug] = true;
                self::doAction('plugin_loaded', $slug, $meta, $context);
            } catch (Throwable $e) {
                if (class_exists('SecurityManager')) {
                    SecurityManager::logEvent('PLUGIN_ERROR', "Fehler beim Laden von Plugin '{$slug}': " . $e->getMessage(), 'ERROR');
                }
            }
        }

        self::doAction('plugins_loaded', $context);
    }

    /**
     * Aktiviert ein Plugin.
     * @param string $slug
     * @param array &$data
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function activatePlugin(string $slug, array &$data): array {
        $plugin = self::getPlugin($slug);
        if (!$plugin) {
            return ['success' => false, 'message' => "Plugin '{$slug}' nicht gefunden."];
        }
        if (!$plugin['is_valid']) {
            return ['success' => false, 'message' => "Plugin '{$slug}' besitzt keine gültige Einstiegsdatei ({$plugin['entry_file']})."];
        }

        if (!isset($data['plugins']) || !is_array($data['plugins'])) {
            $data['plugins'] = ['active' => [], 'configs' => []];
        }
        if (!isset($data['plugins']['active']) || !is_array($data['plugins']['active'])) {
            $data['plugins']['active'] = [];
        }

        // Abhängigkeitsprüfung (requires_plugins)
        if (!empty($plugin['requires_plugins'])) {
            foreach ($plugin['requires_plugins'] as $reqSlug) {
                if (!in_array($reqSlug, $data['plugins']['active'], true)) {
                    $reqPlugin = self::getPlugin($reqSlug);
                    $reqName = $reqPlugin ? $reqPlugin['name'] : $reqSlug;
                    return [
                        'success' => false,
                        'message' => "Das Plugin '{$plugin['name']}' setzt voraus, dass das Plugin '{$reqName}' ({$reqSlug}) zuvor aktiviert wird."
                    ];
                }
            }
        }

        if (!in_array($slug, $data['plugins']['active'], true)) {
            $data['plugins']['active'][] = $slug;
        }

        // Plugin sofort laden und Aktivierungs-Hook ausführen
        try {
            require_once $plugin['entry_path'];
            self::$loadedPlugins[$slug] = true;
            self::doAction("plugin_activated_{$slug}", $data);
            self::doAction('plugin_activated', $slug, $plugin);
        } catch (Throwable $e) {
            // Bei fatalem Fehler Rollback der Aktivierung
            $data['plugins']['active'] = array_values(array_diff($data['plugins']['active'], [$slug]));
            return ['success' => false, 'message' => "Aktivierung fehlgeschlagen: " . $e->getMessage()];
        }

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('PLUGIN_ACTIVATED', "Plugin '{$slug}' ({$plugin['name']}) erfolgreich aktiviert", 'INFO');
        }

        return ['success' => true, 'message' => "Plugin '{$plugin['name']}' wurde erfolgreich aktiviert!"];
    }

    /**
     * Deaktiviert ein Plugin.
     * @param string $slug
     * @param array &$data
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function deactivatePlugin(string $slug, array &$data): array {
        if (!isset($data['plugins']['active']) || !is_array($data['plugins']['active'])) {
            return ['success' => true, 'message' => 'Plugin war nicht aktiv.'];
        }

        $plugin = self::getPlugin($slug);
        $name = $plugin ? $plugin['name'] : $slug;

        // Prüfen, ob andere aktive Plugins von diesem abhängen
        foreach ($data['plugins']['active'] as $actSlug) {
            if ($actSlug === $slug) continue;
            $other = self::getPlugin($actSlug);
            if ($other && !empty($other['requires_plugins']) && in_array($slug, $other['requires_plugins'], true)) {
                return [
                    'success' => false,
                    'message' => "Plugin '{$name}' kann nicht deaktiviert werden, da das aktive Plugin '{$other['name']}' davon abhängt. Bitte zuerst '{$other['name']}' deaktivieren."
                ];
            }
        }

        self::doAction("plugin_deactivated_{$slug}", $data);
        self::doAction('plugin_deactivated', $slug);

        $data['plugins']['active'] = array_values(array_diff($data['plugins']['active'], [$slug]));

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('PLUGIN_DEACTIVATED', "Plugin '{$slug}' ({$name}) deaktiviert", 'INFO');
        }

        return ['success' => true, 'message' => "Plugin '{$name}' wurde deaktiviert."];
    }

    // =========================================================================
    // PLUGIN-KONFIGURATION & EINSTELLUNGEN
    // =========================================================================

    /**
     * Speichert die Konfiguration eines bestimmten Plugins.
     * @param string $slug
     * @param array $config
     * @param array &$data
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function savePluginConfig(string $slug, array $config, array &$data): array {
        if (!isset($data['plugins']) || !is_array($data['plugins'])) {
            $data['plugins'] = ['active' => [], 'configs' => []];
        }
        if (!isset($data['plugins']['configs']) || !is_array($data['plugins']['configs'])) {
            $data['plugins']['configs'] = [];
        }

        // Vor dem Speichern Filter für Datenvalidierung anbieten
        $validatedConfig = self::applyFilters("plugin_validate_config_{$slug}", $config, $data);
        $data['plugins']['configs'][$slug] = $validatedConfig;

        // Automatische Synchronisation für registrierte Sektionen in SectionManager sicherstellen
        if (class_exists('SectionManager')) {
            SectionManager::ensureSections($data);
            $secId = ($slug === 'webtrees-genealogy') ? 'webtrees' : $slug;
            if (isset($validatedConfig['section_enabled'])) {
                SectionManager::setVisibility($data, $secId, (bool)$validatedConfig['section_enabled']);
            }
            if (!empty($validatedConfig['section_title'])) {
                SectionManager::updateSection($data, $secId, ['title' => $validatedConfig['section_title']]);
            }
        }

        self::doAction("plugin_config_saved_{$slug}", $validatedConfig, $data);

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('PLUGIN_CONFIG_SAVED', "Einstellungen für Plugin '{$slug}' gespeichert", 'INFO');
        }

        return ['success' => true, 'message' => "Einstellungen für '{$slug}' erfolgreich gespeichert!"];
    }

    /**
     * Liest die Konfiguration eines bestimmten Plugins aus.
     * @param string $slug
     * @param array $data
     * @param array $defaults
     * @return array
     */
    public static function getPluginConfig(string $slug, array $data, array $defaults = []): array {
        $configs = $data['plugins']['configs'] ?? [];
        $cur = $configs[$slug] ?? [];
        return array_merge($defaults, is_array($cur) ? $cur : []);
    }
}
