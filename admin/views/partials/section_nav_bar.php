<?php
/**
 * WeaveCMS – Partial: Menü- & Sektionseinstellungen Schnell-Leiste
 * 
 * Ermöglicht die direkte Anpassung von Titel fürs Menü (nav_label), Sektions-Titel
 * und Menü-Sichtbarkeit direkt im jeweiligen Content-Tab.
 * 
 * @var array $data
 * @var string $section_id
 * @var string $current_tab
 * @var string $csrf_token
 */

$sec_item = null;
foreach ($data['sections'] ?? [] as $s) {
    if (($s['id'] ?? '') === $section_id) {
        $sec_item = $s;
        break;
    }
}

if ($sec_item):
    $sec_nav_label = $sec_item['nav_label'] ?? '';
    $sec_title = $sec_item['title'] ?? '';
    $sec_in_nav = !empty($sec_item['in_nav']);
    $sec_trans = $sec_item['translations'] ?? [];
    $active_langs = $active_langs ?? (class_exists('LanguageManager') ? LanguageManager::getActiveLanguages($data) : ['de']);
    $primary_lang = $primary_lang ?? (class_exists('LanguageManager') ? LanguageManager::getPrimaryLanguage($data) : 'de');
    $supported = $supported ?? (class_exists('LanguageManager') ? LanguageManager::getSupportedLanguages() : []);
    $has_multiple_langs = count($active_langs) > 1;
?>
<div style="background: rgba(255, 255, 255, 0.65); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 18px; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
    <form method="POST" action="" style="margin:0;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="action" value="update_section">
        <input type="hidden" name="target_tab" value="<?php echo htmlspecialchars($current_tab); ?>">
        <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id); ?>">

        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:<?php echo $has_multiple_langs ? '12px' : '0'; ?>; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:20px; color:var(--primary);">link</span>
                <span style="font-size:0.92rem; font-weight:700; color:var(--text-primary);">Titel &amp; Menü:</span>
            </div>

            <?php if ($has_multiple_langs): ?>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); margin-right:4px;">
                        <span class="material-icons" style="font-size:16px; vertical-align:middle;">translate</span> Sprache:
                    </span>
                    <button type="button" class="btn btn-primary sec-bar-lang-btn-<?php echo htmlspecialchars($section_id); ?>" id="btn-secbar-<?php echo htmlspecialchars($section_id); ?>-<?php echo $primary_lang; ?>" onclick="switchSecBarLang('<?php echo htmlspecialchars($section_id); ?>', '<?php echo $primary_lang; ?>')" style="padding:3px 10px; font-size:0.8rem; border-radius:8px;">
                        <?php echo $supported[$primary_lang]['flag'] ?? ''; ?> <?php echo htmlspecialchars($supported[$primary_lang]['name'] ?? $primary_lang); ?> (Standard)
                    </button>
                    <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; ?>
                        <button type="button" class="btn btn-outline sec-bar-lang-btn-<?php echo htmlspecialchars($section_id); ?>" id="btn-secbar-<?php echo htmlspecialchars($section_id); ?>-<?php echo $al; ?>" onclick="switchSecBarLang('<?php echo htmlspecialchars($section_id); ?>', '<?php echo $al; ?>')" style="padding:3px 10px; font-size:0.8rem; border-radius:8px;">
                            <?php echo $supported[$al]['flag'] ?? ''; ?> <?php echo htmlspecialchars($supported[$al]['name'] ?? $al); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
            <!-- Hauptsprache Felder -->
            <div id="secbar-panel-<?php echo htmlspecialchars($section_id); ?>-<?php echo $primary_lang; ?>" class="secbar-lang-panel-<?php echo htmlspecialchars($section_id); ?>" style="display:flex; align-items:center; gap:12px; flex:1; flex-wrap:wrap; min-width:280px;">
                <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); white-space:nowrap;">Titel fürs Menü:</label>
                    <input type="text" name="nav_label" class="form-control" value="<?php echo htmlspecialchars($sec_nav_label); ?>" placeholder="z. B. <?php echo htmlspecialchars($sec_title); ?>" required style="background:#ffffff; border:1px solid rgba(62,50,40,0.2); color:var(--text-primary); padding:5px 10px; font-size:0.85rem; height:auto; border-radius:8px;">
                </div>

                <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                    <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); white-space:nowrap;">Sektions-Titel:</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($sec_title); ?>" placeholder="Titel der Sektion" style="background:#ffffff; border:1px solid rgba(62,50,40,0.2); color:var(--text-primary); padding:5px 10px; font-size:0.85rem; height:auto; border-radius:8px;">
                </div>
            </div>

            <!-- Übersetzungsfelder für weitere aktive Sprachen -->
            <?php if ($has_multiple_langs): ?>
                <?php foreach ($active_langs as $al): if ($al === $primary_lang) continue; 
                    $al_nav = $sec_trans[$al]['nav_label'] ?? '';
                    $al_title = $sec_trans[$al]['title'] ?? '';
                ?>
                    <div id="secbar-panel-<?php echo htmlspecialchars($section_id); ?>-<?php echo $al; ?>" class="secbar-lang-panel-<?php echo htmlspecialchars($section_id); ?>" style="display:none; align-items:center; gap:12px; flex:1; flex-wrap:wrap; min-width:280px;">
                        <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                            <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); white-space:nowrap;">Menü (<?php echo strtoupper($al); ?>):</label>
                            <input type="text" name="trans_<?php echo $al; ?>_nav_label" class="form-control" value="<?php echo htmlspecialchars($al_nav); ?>" placeholder="<?php echo htmlspecialchars($sec_nav_label); ?>" style="background:#ffffff; border:1px solid rgba(62,50,40,0.2); color:var(--text-primary); padding:5px 10px; font-size:0.85rem; height:auto; border-radius:8px;">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                            <label style="font-size:0.8rem; font-weight:600; color:var(--text-secondary); white-space:nowrap;">Titel (<?php echo strtoupper($al); ?>):</label>
                            <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" value="<?php echo htmlspecialchars($al_title); ?>" placeholder="<?php echo htmlspecialchars($sec_title); ?>" style="background:#ffffff; border:1px solid rgba(62,50,40,0.2); color:var(--text-primary); padding:5px 10px; font-size:0.85rem; height:auto; border-radius:8px;">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--text-primary); cursor:pointer; white-space:nowrap;">
                    <input type="checkbox" name="in_nav" value="1" <?php if ($sec_in_nav) echo 'checked'; ?>>
                    <span>Im Menü anzeigen</span>
                </label>

                <button type="submit" class="btn btn-primary" style="padding:6px 14px; font-size:0.82rem; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                    <span class="material-icons" style="font-size:16px;">save</span> Speichern
                </button>
            </div>
        </div>
    </form>
</div>

<script>
if (typeof switchSecBarLang !== 'function') {
    function switchSecBarLang(secId, lang) {
        document.querySelectorAll('.secbar-lang-panel-' + secId).forEach(function(el) {
            el.style.display = 'none';
        });
        const activePanel = document.getElementById('secbar-panel-' + secId + '-' + lang);
        if (activePanel) {
            activePanel.style.display = 'flex';
        }
        document.querySelectorAll('.sec-bar-lang-btn-' + secId).forEach(function(btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline');
        });
        const activeBtn = document.getElementById('btn-secbar-' + secId + '-' + lang);
        if (activeBtn) {
            activeBtn.classList.remove('btn-outline');
            activeBtn.classList.add('btn-primary');
        }
    }
}
</script>
<?php endif; ?>
