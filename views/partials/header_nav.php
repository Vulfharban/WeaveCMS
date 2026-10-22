<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Header Navigation
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 *
 * @var array $data
 * @var array $nav_items
 * @var string $logo_pos
 * @var string $header_logo_html
 */
?>
    <!-- Toast Feedback -->
    <div id="toast"><span class="material-icons" style="font-size:18px;">check</span> <span id="toast-text">Link kopiert!</span></div>

    <!-- CSS Slide-in Menu Trigger Checkboxes -->
    <input type="checkbox" id="menu-toggle">
    <input type="checkbox" id="menu-toggle-impressum">
    <input type="checkbox" id="menu-toggle-mobile">

    <!-- ======================================================================
         FRONTEND-MENÜ (HEADER NAVIGATION & MENÜ-TRIGGER)
         ====================================================================== -->
    <?php 
        $is_subpage_view = isset($_GET['page']) || isset($_GET['blog']);
        $home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
        $logo_href = $is_subpage_view ? htmlspecialchars($home_url) : '#';

        // 0. Optionale Besucher-Barrierefreiheitsleiste (Phase 16)
        if (!empty($data['settings']['accessibility_bar_enabled'])) {
            include __DIR__ . '/accessibility_bar.php';
        }
    ?>
    <header class="header-nav">
        <div class="header-container">
            <a href="<?php echo $logo_href; ?>" class="logo"><?php if ($logo_pos === 'header_before'): echo $header_logo_html; endif; ?><span><?php echo htmlspecialchars($data['texts']['contact_name'] ?? 'Atelier Musterfrau'); ?></span><?php if ($logo_pos === 'header_after'): echo $header_logo_html; endif; ?></a>
            <nav class="nav-links">
                <?php foreach ($nav_items as $item): ?>
                    <?php 
                        $href = !empty($item['url']) 
                            ? htmlspecialchars($item['url']) 
                            : ($is_subpage_view ? htmlspecialchars($home_url . '#' . $item['id']) : '#' . htmlspecialchars($item['id']));
                    ?>
                    <a href="<?php echo $href; ?>" class="nav-item"><?php echo htmlspecialchars($item['nav_label']); ?></a>
                <?php endforeach; ?>
                <?php 
                    $contact_place = $data['settings']['contact_placement'] ?? 'both';
                    $impressum_place = $data['settings']['impressum_placement'] ?? 'both';
                ?>
                <?php if ($contact_place === 'header' || $contact_place === 'both'): ?>
                    <label for="menu-toggle" class="nav-item" style="cursor: pointer;"><?php echo htmlspecialchars(__('nav.contact', 'Kontakt')); ?></label>
                <?php endif; ?>
                <?php if ($impressum_place === 'header' || $impressum_place === 'both'): ?>
                    <label for="menu-toggle-impressum" class="nav-item" style="cursor: pointer;"><?php echo htmlspecialchars(__('nav.impressum', 'Impressum')); ?></label>
                <?php endif; ?>
                <?php if (!empty($lang_switch_items) && count($lang_switch_items) > 1): ?>
                    <div class="header-lang-switch" style="display:inline-flex; align-items:center; gap:6px; margin-left:12px; padding-left:12px; border-left:1px solid var(--border-color); font-size:0.82rem; font-weight:700;">
                        <?php foreach ($lang_switch_items as $l): ?>
                            <?php if ($l['active']): ?>
                                <span style="color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:1px;" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></span>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($l['url']); ?>" style="color:var(--text-secondary); text-decoration:none; opacity:0.65; transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.65'" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </nav>
            <!-- Mobile Menu Trigger (Hamburger) -->
            <label for="menu-toggle-mobile" class="btn btn-outline" style="display: none; padding: 6px 12px; font-size: 0.85rem; cursor: pointer;" id="mobile-nav-trigger">
                <span class="material-icons" style="font-size: 18px; vertical-align: middle;">menu</span>
            </label>
        </div>
    </header>
