<?php
/**
 * ============================================================================
 * Webtrees Stammbaum-Integration – Haupteinstiegspunkt
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

// 1. Einstellungsansicht für den Adminbereich registrieren
PluginManager::addAction('admin_render_plugin_settings_webtrees-genealogy', function($data, $csrf_token) {
    $config = PluginManager::getPluginConfig('webtrees-genealogy', $data);
    require __DIR__ . '/admin/settings.php';
});

// 2. Shortcode-Filter für Content (Markdown / HTML / Einzelseiten / Blog)
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[webtrees') === false) {
        return $content;
    }

    $config = PluginManager::getPluginConfig('webtrees-genealogy', $data);

    // [webtrees] oder [webtrees key="val"...] auflösen
    return preg_replace_callback('/\[webtrees(?:\s+([^\]]+))?\]/i', function($matches) use ($config) {
        $rawAttrs = $matches[1] ?? '';
        $params = [];
        if (!empty($rawAttrs)) {
            preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $rawAttrs, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $am) {
                $params[$am[1]] = $am[2];
            }
        }

        ob_start();
        require __DIR__ . '/views/tree_embed.php';
        return ob_get_clean();
    }, $content);
});

// 3. Eigene Sektion für den Sektionen-Manager & das Frontend registrieren
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('webtrees-genealogy', $data)) {
        return $sections;
    }
    $config = PluginManager::getPluginConfig('webtrees-genealogy', $data);
    $isEnabled = !empty($config['section_enabled']);
    $sections[] = [
        'id' => 'webtrees',
        'type' => 'webtrees',
        'title' => !empty($config['section_title']) ? $config['section_title'] : 'Familienstammbaum & Ahnenforschung',
        'nav_label' => 'Stammbaum',
        'visible' => $isEnabled,
        'in_nav' => true,
        'plugin' => 'webtrees-genealogy',
        'can_delete' => false
    ];
    return $sections;
});

// Fallback für getVisibleSections
PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('webtrees-genealogy', $data)) {
        return $sections;
    }
    $config = PluginManager::getPluginConfig('webtrees-genealogy', $data);
    if (!empty($config['section_enabled'])) {
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
                'type' => 'webtrees',
                'title' => !empty($config['section_title']) ? $config['section_title'] : 'Familienstammbaum & Ahnenforschung',
                'nav_label' => 'Stammbaum',
                'visible' => true,
                'in_nav' => true,
                'plugin' => 'webtrees-genealogy'
            ];
        }
    }
    return $sections;
});

// Synchronisation mit data['sections'] beim Speichern der Plugin-Einstellungen
PluginManager::addAction('plugin_config_saved_webtrees-genealogy', function($config, &$data) {
    if (class_exists('SectionManager')) {
        SectionManager::ensureSections($data);
        $sectionEnabled = !empty($config['section_enabled']);
        $found = false;
        foreach ($data['sections'] as &$sec) {
            if (($sec['id'] ?? '') === 'webtrees') {
                $sec['visible'] = $sectionEnabled;
                if (!empty($config['section_title'])) {
                    $sec['title'] = $config['section_title'];
                }
                $found = true;
                break;
            }
        }
        if (!$found && $sectionEnabled) {
            $data['sections'][] = [
                'id' => 'webtrees',
                'type' => 'webtrees',
                'title' => !empty($config['section_title']) ? $config['section_title'] : 'Familienstammbaum & Ahnenforschung',
                'nav_label' => 'Stammbaum',
                'visible' => true,
                'in_nav' => true,
                'plugin' => 'webtrees-genealogy',
                'can_delete' => false
            ];
        }
    }
});

// 4. Eigene Sektion für das Frontend rendern
PluginManager::addAction('frontend_render_custom_section', function($sectionKey, $data) {
    if ($sectionKey !== 'webtrees') {
        return;
    }
    $config = PluginManager::getPluginConfig('webtrees-genealogy', $data);
    if (empty($config['section_enabled'])) {
        return;
    }

    $params = [
        'title' => $config['section_title'] ?? 'Familienstammbaum & Ahnenforschung',
        'subtitle' => $config['section_subtitle'] ?? 'Entdecke unsere Familiengeschichte interaktiv aus Webtrees.',
        'is_section' => true
    ];

    require __DIR__ . '/views/tree_embed.php';
});

// 4. Validierung der Konfigurationsdaten vor dem Speichern (Strikte Beschränkung auf lokalen Server-Pfad)
PluginManager::addFilter('plugin_validate_config_webtrees-genealogy', function($submitted, $data) {
    $rawPath = trim($submitted['webtrees_path'] ?? $submitted['webtrees_url'] ?? '');

    // Externe URLs strikt verbieten (kein http://, https://, // oder sonstige Schemata)
    if (preg_match('~^(https?:)?//~i', $rawPath)) {
        // Domain/Protokoll entfernen und nur den Pfadteil extrahieren
        $parsed = parse_url($rawPath);
        $rawPath = $parsed['path'] ?? '';
        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('WEBTREES_URL_REJECTED', "Externe Webtrees-URL abgefangen. Webtrees muss auf demselben Server liegen.", 'WARNING');
        }
    }

    // Pfad bereinigen & normalisieren: muss mit / beginnen und darf keine unbefugten Zeichen enthalten
    $cleanPath = '/' . ltrim(preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $rawPath), '/');
    if ($cleanPath === '/' && !empty($rawPath)) {
        $cleanPath = '/webtrees';
    }

    $cleaned = [];
    $cleaned['webtrees_path'] = $cleanPath;
    // Kompatibilitätsfeld für ältere Aufrufe
    $cleaned['webtrees_url'] = $cleanPath;

    $cleaned['tree_id'] = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', trim($submitted['tree_id'] ?? 'tree1'));
    $cleaned['embed_mode'] = in_array($submitted['embed_mode'] ?? '', ['interactive_iframe', 'family_preview']) ? $submitted['embed_mode'] : 'interactive_iframe';
    $cleaned['default_person'] = trim($submitted['default_person'] ?? '');
    $cleaned['iframe_height'] = max(400, min(1400, (int)($submitted['iframe_height'] ?? 750)));
    $cleaned['section_enabled'] = !empty($submitted['section_enabled']);
    $cleaned['section_title'] = trim($submitted['section_title'] ?? 'Familienstammbaum & Ahnenforschung');
    $cleaned['section_subtitle'] = trim($submitted['section_subtitle'] ?? '');
    $cleaned['embed_token'] = trim($submitted['embed_token'] ?? '');
    return $cleaned;
});
