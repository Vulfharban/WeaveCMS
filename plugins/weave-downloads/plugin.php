<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Download-Center & Dokumente (weave-downloads)
 * ============================================================================
 * Verwaltet Download-Dateien, Sicherheitsbeschränkungen, Download-Zähler
 * und modulare Download-Listen für Kunden und Besucher.
 */

if (!defined('WEAVE_VERSION')) {
    exit;
}

// 1. Sektion im SectionManager registrieren
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-downloads', $data)) {
        return $sections;
    }

    $existing_ids = [];
    foreach ($sections as $s) {
        $existing_ids[] = $s['id'] ?? ($s['type'] ?? '');
    }

    if (!in_array('downloads', $existing_ids, true)) {
        $sections[] = [
            'id'        => 'downloads',
            'type'      => 'downloads',
            'title'     => 'Downloads & Dokumente',
            'nav_label' => 'Downloads',
            'visible'   => true,
            'in_nav'    => true,
            'can_delete'=> true,
            'plugin'    => 'weave-downloads'
        ];
    }

    return $sections;
});

// 2. Plugin-Settings Hook für den Admin-Reiter
PluginManager::addAction('admin_render_plugin_settings_weave-downloads', function($data, $csrf_token) {
    $downloadCount = count($data['downloads'] ?? []);
    ?>
    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-top: 1rem;">
        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary); display: flex; align-items: center; gap: 8px;">
            <span class="material-icons">file_download</span> Download-Center aktiv
        </h4>
        <p style="color: var(--text-secondary); margin: 0 0 1rem 0; font-size: 0.92rem;">
            Aktuell sind <strong><?php echo $downloadCount; ?> Datei(en) / Dokument(e)</strong> im Download-Bereich verfügbar.
        </p>
        <button type="button" class="btn btn-primary" onclick="switchTab('tab-downloads')" style="padding: 6px 14px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 16px;">folder_zip</span> Downloads verwalten
        </button>
    </div>
    <?php
});

// 3. Shortcode [downloads]
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[downloads]') !== false) {
        ob_start();
        $cur_lang = LanguageManager::detectLanguage($data);
        $downloads = $data['downloads'] ?? [];
        $dl_title = !empty($data['texts']['downloads_title']) ? $data['texts']['downloads_title'] : 'Downloads &amp; Software';
        $dl_subtitle = !empty($data['texts']['downloads_subtitle']) ? $data['texts']['downloads_subtitle'] : '';
        require dirname(__DIR__, 2) . '/views/sections/downloads.php';
        $dlHtml = ob_get_clean();
        $content = str_replace('[downloads]', $dlHtml, $content);
    }

    return $content;
});
