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
?>
<div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 18px; margin-bottom: 2rem;">
    <form method="POST" action="" style="margin:0; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="action" value="update_section">
        <input type="hidden" name="target_tab" value="<?php echo htmlspecialchars($current_tab); ?>">
        <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id); ?>">

        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <span class="material-icons" style="font-size:20px; color:var(--primary);">link</span>
            <span style="font-size:0.9rem; font-weight:700; color:var(--text-primary);">Titel &amp; Menü:</span>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex:1; flex-wrap:wrap; min-width:280px;">
            <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                <label style="font-size:0.8rem; color:var(--text-secondary); white-space:nowrap;">Titel fürs Menü:</label>
                <input type="text" name="nav_label" class="form-control" value="<?php echo htmlspecialchars($sec_nav_label); ?>" placeholder="z. B. <?php echo htmlspecialchars($sec_title); ?>" required style="padding:5px 10px; font-size:0.85rem; height:auto;">
            </div>

            <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:180px;">
                <label style="font-size:0.8rem; color:var(--text-secondary); white-space:nowrap;">Sektions-Titel:</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($sec_title); ?>" placeholder="Titel der Sektion" style="padding:5px 10px; font-size:0.85rem; height:auto;">
            </div>

            <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--text-primary); cursor:pointer; white-space:nowrap;">
                <input type="checkbox" name="in_nav" value="1" <?php if ($sec_in_nav) echo 'checked'; ?>>
                <span>Im Menü anzeigen</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:6px 14px; font-size:0.82rem; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
            <span class="material-icons" style="font-size:16px;">save</span> Menü-Titel speichern
        </button>
    </form>
</div>
<?php endif; ?>
