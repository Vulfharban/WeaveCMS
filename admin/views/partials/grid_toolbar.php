<?php
/**
 * ============================================================================
 * WeaveCMS Admin Partial: Universelle Grid- & Design-Toolbar (Phase 27 & 32.2)
 * ============================================================================
 * Bietet Spalten-Layouts ([grid]/[col]), farbige Boxen ([box]), Überschriften,
 * Trenner und Vorlagen für beliebige Textareas (Seiten, Freitext, About usw.).
 *
 * @var string|null $toolbar_target_id ID der Ziel-Textarea (optional, sonst aktives Element)
 */
$tb_id = !empty($toolbar_target_id) ? htmlspecialchars($toolbar_target_id, ENT_QUOTES, 'UTF-8') : '';

$grid_icon_layouts = [
    '2'        => ['label' => 'admin.pages.grid_2_equal', 'cols' => [[0, 17], [19, 17]]],
    '3'        => ['label' => 'admin.pages.grid_3_equal', 'cols' => [[0, 11], [12.5, 11], [25, 11]]],
    '1-2'      => ['label' => 'admin.pages.grid_1_2',     'cols' => [[0, 11], [13, 23]]],
    '2-1'      => ['label' => 'admin.pages.grid_2_1',     'cols' => [[0, 23], [25, 11]]],
    'img-text' => ['label' => 'admin.pages.grid_img_text', 'cols' => [[0, 17], [19, 17, 'text']]],
];
?>
<div class="weave-editor-design-toolbar" data-target-id="<?php echo $tb_id; ?>" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:8px 12px; margin-bottom:0.75rem; display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
    <span style="font-size:0.82rem; font-weight:700; color:var(--primary); display:inline-flex; align-items:center; gap:4px;">
        <span class="material-icons" style="font-size:16px;">auto_awesome</span> <?php echo __a('admin.pages.toolbar_template', 'Vorlagen:'); ?>
    </span>

    <!-- Überschriften-Stile -->
    <select onchange="insertUniversalSnippet(this, 'heading', '<?php echo $tb_id; ?>')" class="form-control" style="width:auto; padding:4px 8px; font-size:0.8rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_heading_title', 'Überschriften-Stil einfügen')); ?>">
        <option value=""><?php echo __a('admin.pages.toolbar_heading_style', 'Überschrift...'); ?></option>
        <option value="left_bar"><?php echo __a('admin.pages.heading_left_bar', 'Balken links'); ?></option>
        <option value="underline"><?php echo __a('admin.pages.heading_underline', 'Farbige Unterstreichung'); ?></option>
        <option value="eyebrow"><?php echo __a('admin.pages.heading_eyebrow', 'Dachzeile (Eyebrow)'); ?></option>
        <option value="gradient"><?php echo __a('admin.pages.heading_gradient', 'Farbverlauf (Gradient)'); ?></option>
        <option value="icon"><?php echo __a('admin.pages.heading_icon', 'Mit Icon-Badge'); ?></option>
        <option value="display"><?php echo __a('admin.pages.heading_display', 'Große Display-Schrift'); ?></option>
    </select>

    <!-- Einfärbbare Boxen -->
    <select onchange="insertUniversalSnippet(this, 'box', '<?php echo $tb_id; ?>')" class="form-control" style="width:auto; padding:4px 8px; font-size:0.8rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_box_title', 'Farbige Box einfügen')); ?>">
        <option value=""><?php echo __a('admin.pages.toolbar_box_style', 'Box...'); ?></option>
        <option value="soft_primary"><?php echo __a('admin.pages.box_soft_primary', 'Dezent Primär'); ?></option>
        <option value="soft_success"><?php echo __a('admin.pages.box_soft_success', 'Dezent Grün'); ?></option>
        <option value="soft_amber"><?php echo __a('admin.pages.box_soft_amber', 'Dezent Bernstein / Warm'); ?></option>
        <option value="soft_purple"><?php echo __a('admin.pages.box_soft_purple', 'Dezent Violett'); ?></option>
        <option value="gradient"><?php echo __a('admin.pages.box_gradient', 'Sanfter Farbverlauf'); ?></option>
        <option value="dark"><?php echo __a('admin.pages.box_dark', 'Dunkel / Kontrast'); ?></option>
        <option value="tintable"><?php echo __a('admin.pages.box_tintable', 'Eigene Farbe (Farbwähler)'); ?></option>
    </select>

    <!-- Farbwähler für Box-Hintergrund -->
    <label title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_color_title', 'Farbe für eigene Box wählen')); ?>" style="display:inline-flex; align-items:center; gap:4px; font-size:0.78rem; color:var(--text-secondary); cursor:pointer;">
        <input type="color" class="picker_box_color_field" value="#e0f2fe" style="width:24px; height:24px; border:none; border-radius:6px; cursor:pointer; padding:0; background:none;">
        <span><?php echo __a('admin.pages.toolbar_color_label', 'Farbe'); ?></span>
    </label>

    <!-- Optische Trenner -->
    <select onchange="insertUniversalSnippet(this, 'divider', '<?php echo $tb_id; ?>')" class="form-control" style="width:auto; padding:4px 8px; font-size:0.8rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_divider_title', 'Optischen Trenner einfügen')); ?>">
        <option value=""><?php echo __a('admin.pages.toolbar_divider_style', 'Trenner...'); ?></option>
        <option value="gradient"><?php echo __a('admin.pages.divider_gradient', 'Farbverlauf'); ?></option>
        <option value="icon_leaf"><?php echo __a('admin.pages.divider_icon_leaf', 'Blatt-Icon'); ?></option>
        <option value="icon_star"><?php echo __a('admin.pages.divider_icon_star', 'Stern-Icon'); ?></option>
        <option value="glow"><?php echo __a('admin.pages.divider_glow', 'Leuchtend'); ?></option>
        <option value="dots"><?php echo __a('admin.pages.divider_dots', 'Punkte-Reihe'); ?></option>
        <option value="dashed"><?php echo __a('admin.pages.divider_dashed', 'Gestrichelt'); ?></option>
        <option value="wave"><?php echo __a('admin.pages.divider_wave', 'Welle'); ?></option>
    </select>

    <!-- Spalten-Layouts (Grid-Shortcodes) als Icon-Leiste -->
    <span class="page-grid-picker" role="group" aria-label="<?php echo htmlspecialchars(__a('admin.pages.toolbar_grid_style', 'Spalten-Layout')); ?>" style="display:inline-flex; align-items:center; gap:3px;">
        <span style="font-size:0.78rem; color:var(--text-secondary); font-weight:600; margin-right:2px;"><?php echo __a('admin.pages.toolbar_grid_label', 'Spalten:'); ?></span>
        <?php foreach ($grid_icon_layouts as $g_val => $g_def): $g_label = __a($g_def['label']); ?>
            <button type="button" value="<?php echo $g_val; ?>" onclick="insertUniversalSnippet(this, 'grid', '<?php echo $tb_id; ?>')" class="btn btn-secondary" title="<?php echo htmlspecialchars($g_label); ?>" aria-label="<?php echo htmlspecialchars($g_label); ?>" style="padding:2px 4px; line-height:0; border-radius:6px;">
                <svg width="30" height="20" viewBox="0 0 36 24" aria-hidden="true" focusable="false">
                    <?php foreach ($g_def['cols'] as $g_col): ?>
                        <?php if (!empty($g_col[2])): ?>
                            <rect x="<?php echo $g_col[0]; ?>" y="4" width="<?php echo $g_col[1]; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                            <rect x="<?php echo $g_col[0]; ?>" y="10" width="<?php echo $g_col[1]; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                            <rect x="<?php echo $g_col[0]; ?>" y="16" width="<?php echo $g_col[1] * 0.6; ?>" height="3" rx="1.5" fill="currentColor" opacity="0.55"/>
                        <?php else: ?>
                            <rect x="<?php echo $g_col[0]; ?>" y="2" width="<?php echo $g_col[1]; ?>" height="20" rx="3" fill="currentColor" opacity="0.75"/>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </svg>
            </button>
        <?php endforeach; ?>
    </span>

    <!-- Block-Vorlagen -->
    <select onchange="insertUniversalSnippet(this, 'preset', '<?php echo $tb_id; ?>')" class="form-control" style="width:auto; padding:4px 8px; font-size:0.8rem; border-radius:8px; cursor:pointer;" title="<?php echo htmlspecialchars(__a('admin.pages.toolbar_preset_style', 'Fertige Vorlage einfügen')); ?>">
        <option value=""><?php echo __a('admin.pages.toolbar_preset_style', 'Vorlage...'); ?></option>
        <option value="hero"><?php echo __a('admin.pages.preset_hero', 'Hero-Header mit Buttons'); ?></option>
        <option value="testimonial"><?php echo __a('admin.pages.preset_testimonial', 'Kundenstimme / Zitat'); ?></option>
        <option value="cta"><?php echo __a('admin.pages.preset_cta', 'Call-to-Action Box'); ?></option>
        <option value="features"><?php echo __a('admin.pages.preset_features', '3-Spalten Vorteile'); ?></option>
    </select>
</div>
