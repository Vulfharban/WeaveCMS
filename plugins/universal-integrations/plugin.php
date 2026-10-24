<?php
/**
 * ============================================================================
 * WeaveCMS Universelle Integrationen – Plugin Einstiegspunkt
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/IntegrationManager.php';

// -----------------------------------------------------------------------------
// 1. Admin Aktionen (POST)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        
        // Integration anlegen / bearbeiten
        if ($action === 'integrations_save') {
            global $data;
            $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['slug'] ?? '')));
            if (empty($slug)) {
                $slug = 'app_' . bin2hex(random_bytes(3));
            }

            $item = [
                'slug' => $slug,
                'name' => trim($_POST['name'] ?? ucfirst($slug)),
                'target_url' => trim($_POST['target_url'] ?? ''),
                'url_pattern' => trim($_POST['url_pattern'] ?? '{base}'),
                'embed_mode' => trim($_POST['embed_mode'] ?? 'iframe'),
                'iframe_height' => max(300, (int)($_POST['iframe_height'] ?? 800)),
                'icon' => trim($_POST['icon'] ?? 'apps'),
                'access_level' => trim($_POST['access_level'] ?? 'public'),
                'show_as_section' => !empty($_POST['show_as_section']),
                'active' => !empty($_POST['active']),
                'params' => []
            ];

            IntegrationManager::saveIntegration($item, $data);
            header('Location: ?tab=tab-plugins&plugin_settings=universal_integrations&integ_msg=saved#tab-plugins');
            exit;
        }

        // Integration löschen
        if ($action === 'integrations_delete') {
            global $data;
            $slug = trim($_POST['slug'] ?? '');
            IntegrationManager::deleteIntegration($slug, $data);
            header('Location: ?tab=tab-plugins&plugin_settings=universal_integrations&integ_msg=deleted#tab-plugins');
            exit;
        }

        // Migration des Webtrees-Plugins
        if ($action === 'integrations_migrate_webtrees') {
            global $data;
            $migrated = IntegrationManager::migrateFromLegacyWebtrees($data);
            if (!empty($migrated)) {
                foreach ($migrated as $m) {
                    IntegrationManager::saveIntegration($m, $data);
                }
            }

            // Webtrees-Plugin ablösen (deaktivieren)
            if (isset($data['plugins']['active'])) {
                $data['plugins']['active'] = array_values(array_diff($data['plugins']['active'], ['webtrees-genealogy']));
                if (!in_array('universal-integrations', $data['plugins']['active'], true)) {
                    $data['plugins']['active'][] = 'universal-integrations';
                }
            }

            save_data(dirname(__DIR__, 2) . '/data.json', $data);
            header('Location: ?tab=tab-plugins&plugin_settings=universal_integrations&integ_msg=migrated_webtrees#tab-plugins');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Admin Settings Ansicht
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_universal_integrations', function($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 3. Shortcodes: [integration name="..."] und abwärtskompatibles [webtrees ...]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    // 1. [integration name="slug" ...]
    if (strpos($content, '[integration') !== false) {
        $content = preg_replace_callback('/\[integration(?:\s+([^\]]+))?\]/i', function($matches) use ($data) {
            $rawAttrs = $matches[1] ?? '';
            $params = [];
            if (!empty($rawAttrs)) {
                preg_match_all('/([a-zA-Z0-9_-]+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/', $rawAttrs, $attrMatches, PREG_SET_ORDER);
                foreach ($attrMatches as $am) {
                    $val = $am[2] !== '' ? $am[2] : ($am[3] !== '' ? $am[3] : ($am[4] ?? ''));
                    $params[$am[1]] = $val;
                }
            }

            $slug = $params['name'] ?? ($params['slug'] ?? '');
            if (empty($slug)) {
                return '<div class="alert alert-warning">Fehlendes Attribut <code>name="slug"</code> im Shortcode.</div>';
            }

            $item = IntegrationManager::getIntegration($slug, $data);
            if (!$item || empty($item['active'])) {
                return '<div class="alert alert-warning">Die Integration <code>' . htmlspecialchars($slug) . '</code> ist nicht aktiv.</div>';
            }

            return IntegrationManager::render($item, $params);
        }, $content);
    }

    // 2. Abwärtskompatibilität: [webtrees ...]
    if (strpos($content, '[webtrees') !== false) {
        $content = preg_replace_callback('/\[webtrees(?:\s+([^\]]+))?\]/i', function($matches) use ($data) {
            $rawAttrs = $matches[1] ?? '';
            $params = [];
            if (!empty($rawAttrs)) {
                preg_match_all('/([a-zA-Z0-9_-]+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/', $rawAttrs, $attrMatches, PREG_SET_ORDER);
                foreach ($attrMatches as $am) {
                    $val = $am[2] !== '' ? $am[2] : ($am[3] !== '' ? $am[3] : ($am[4] ?? ''));
                    $params[$am[1]] = $val;
                }
            }

            $item = IntegrationManager::getIntegration('webtrees', $data);
            if (!$item) {
                // Falls noch nicht vorhanden, Ad-Hoc-Migration durchführen
                $mig = IntegrationManager::migrateFromLegacyWebtrees($data);
                $item = $mig[0] ?? null;
            }

            if ($item) {
                return IntegrationManager::render($item, $params);
            }

            return '<div class="alert alert-warning">Webtrees-Integration nicht konfiguriert.</div>';
        }, $content);
    }

    return $content;
});

// -----------------------------------------------------------------------------
// 4. Sektionen- & Multi-Page Registrierung für aktive Integrationen
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('universal-integrations', $data)) {
        return $sections;
    }

    $integrations = IntegrationManager::getIntegrations($data);
    foreach ($integrations as $item) {
        if (!empty($item['active']) && !empty($item['show_as_section'])) {
            $sections[] = [
                'id' => 'integration_' . $item['slug'],
                'type' => 'universal_integration',
                'title' => !empty($item['title']) ? $item['title'] : $item['name'],
                'nav_label' => $item['name'],
                'visible' => true,
                'in_nav' => true,
                'plugin' => 'universal-integrations',
                'integration_slug' => $item['slug'],
                'can_delete' => false,
                'placement' => 'page',
                'icon' => $item['icon'] ?? 'hub'
            ];
            // Legacy-Alias für webtrees
            if ($item['slug'] === 'webtrees') {
                $sections[] = [
                    'id' => 'webtrees',
                    'type' => 'universal_integration',
                    'title' => !empty($item['title']) ? $item['title'] : $item['name'],
                    'nav_label' => $item['name'],
                    'visible' => true,
                    'in_nav' => false,
                    'plugin' => 'universal-integrations',
                    'integration_slug' => 'webtrees',
                    'can_delete' => false,
                    'placement' => 'page',
                    'icon' => $item['icon'] ?? 'hub'
                ];
            }
        }
    }
    return $sections;
});

PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('universal-integrations', $data)) {
        return $sections;
    }

    $integrations = IntegrationManager::getIntegrations($data);
    foreach ($integrations as $item) {
        if (!empty($item['active']) && !empty($item['show_as_section'])) {
            $secId = 'integration_' . $item['slug'];
            $exists = false;
            foreach ($sections as $s) {
                if (($s['id'] ?? '') === $secId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $sections[] = [
                    'id' => $secId,
                    'type' => 'universal_integration',
                    'title' => !empty($item['title']) ? $item['title'] : $item['name'],
                    'nav_label' => $item['name'],
                    'visible' => true,
                    'in_nav' => true,
                    'plugin' => 'universal-integrations',
                    'integration_slug' => $item['slug'],
                    'placement' => 'page',
                    'icon' => $item['icon'] ?? 'hub'
                ];
            }

            // Legacy-Alias
            if ($item['slug'] === 'webtrees') {
                $hasWebtrees = false;
                foreach ($sections as $s) {
                    if (($s['id'] ?? '') === 'webtrees') {
                        $hasWebtrees = true;
                        break;
                    }
                }
                if (!$hasWebtrees) {
                    $sections[] = [
                        'id' => 'webtrees',
                        'type' => 'universal_integration',
                        'title' => !empty($item['title']) ? $item['title'] : $item['name'],
                        'nav_label' => $item['name'],
                        'visible' => true,
                        'in_nav' => false,
                        'plugin' => 'universal-integrations',
                        'integration_slug' => 'webtrees',
                        'placement' => 'page',
                        'icon' => $item['icon'] ?? 'hub'
                    ];
                }
            }
        }
    }
    return $sections;
});

// Sektions-Rendering Hook
PluginManager::addAction('frontend_render_custom_section', function($sectionId, $data) {
    if (str_starts_with($sectionId, 'integration_')) {
        $slug = substr($sectionId, 12);
        $item = IntegrationManager::getIntegration($slug, $data);
        if ($item && !empty($item['active'])) {
            echo IntegrationManager::render($item, ['is_section' => true]);
        }
    } elseif ($sectionId === 'webtrees') {
        $item = IntegrationManager::getIntegration('webtrees', $data);
        if ($item && !empty($item['active'])) {
            echo IntegrationManager::render($item, ['is_section' => true]);
        }
    }
});
