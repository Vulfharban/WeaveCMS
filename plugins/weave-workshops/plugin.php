<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Workshops & Kursbuchungen (weave-workshops)
 * ============================================================================
 * Ermöglicht Terminverwaltung, Buchungsstatus, Teilnehmerlimits und
 * Impressionen vergangener Kurse als modulares Plugin.
 */

if (!defined('WEAVE_VERSION')) {
    exit;
}

// 1. Sektionen im SectionManager registrieren
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-workshops', $data)) {
        return $sections;
    }

    $existing_ids = [];
    foreach ($sections as $s) {
        $existing_ids[] = $s['id'] ?? ($s['type'] ?? '');
    }

    if (!in_array('courses', $existing_ids, true)) {
        $sections[] = [
            'id'        => 'courses',
            'type'      => 'courses',
            'title'     => 'Kurse & Workshops',
            'nav_label' => 'Workshops',
            'visible'   => true,
            'in_nav'    => true,
            'can_delete'=> true,
            'plugin'    => 'weave-workshops'
        ];
    }

    if (!in_array('past_courses', $existing_ids, true)) {
        $sections[] = [
            'id'        => 'past_courses',
            'type'      => 'past_courses',
            'title'     => 'Impressionen vergangener Workshops',
            'nav_label' => 'Impressionen',
            'visible'   => true,
            'in_nav'    => false,
            'can_delete'=> true,
            'plugin'    => 'weave-workshops'
        ];
    }

    return $sections;
});

// 2. Plugin-Settings Hook für den Admin-Reiter
PluginManager::addAction('admin_render_plugin_settings_weave-workshops', function($data, $csrf_token) {
    $courseCount = count($data['courses'] ?? []);
    $pastCount = count($data['past_courses'] ?? []);
    ?>
    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-top: 1rem;">
        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary); display: flex; align-items: center; gap: 8px;">
            <span class="material-icons">event</span> Workshops &amp; Kursbuchungen aktiv
        </h4>
        <p style="color: var(--text-secondary); margin: 0 0 1rem 0; font-size: 0.92rem;">
            Aktuell sind <strong><?php echo $courseCount; ?> Kurs(e)</strong> und <strong><?php echo $pastCount; ?> Impression(en)</strong> hinterlegt.
        </p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="switchTab('tab-courses')" style="padding: 6px 14px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 16px;">edit_calendar</span> Kurse verwalten
            </button>
            <button type="button" class="btn btn-outline" onclick="switchTab('tab-past')" style="padding: 6px 14px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-icons" style="font-size: 16px;">photo_camera</span> Impressionen verwalten
            </button>
        </div>
    </div>
    <?php
});
