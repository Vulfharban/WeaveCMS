<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Header Navigation (Phase 19)
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

$is_subpage_view = isset($_GET['page']) || isset($_GET['blog']);
$home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
$logo_href = $is_subpage_view ? htmlspecialchars($home_url) : '#';

$menu_style = class_exists('ThemeManager') ? ThemeManager::getActiveMenuStyle($data) : 'horizontal_minimal';
$sidebar_pos = class_exists('ThemeManager') ? ThemeManager::getSidebarPosition($data) : 'left';

// Phase 19.3: Plugin Action-Hook 'frontend_render_navigation'
if (class_exists('PluginManager') && PluginManager::hasAction('frontend_render_navigation')) {
    ob_start();
    PluginManager::doAction('frontend_render_navigation', $nav_items, $data, [
        'menu_style'        => $menu_style,
        'sidebar_pos'       => $sidebar_pos,
        'logo_href'         => $logo_href,
        'home_url'          => $home_url,
        'is_subpage_view'   => $is_subpage_view,
        'lang_switch_items' => $lang_switch_items ?? []
    ]);
    $custom_nav_html = ob_get_clean();
    if (!empty(trim($custom_nav_html))) {
        echo $custom_nav_html;
        return;
    }
}
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
        // 0. Optionale Besucher-Barrierefreiheitsleiste (Phase 16)
        if (!empty($data['settings']['accessibility_bar_enabled'])) {
            include __DIR__ . '/accessibility_bar.php';
        }

        $contact_place = $data['settings']['contact_placement'] ?? 'both';
        $impressum_place = $data['settings']['impressum_placement'] ?? 'both';
    ?>

    <?php if ($menu_style === 'vertical_sidebar'): ?>
        <!-- ───── Phase 19: Vertikale Sidebar-Navigation (Desktop: min-width: 993px) ───── -->
        <aside class="header-nav sidebar-nav sidebar-<?php echo htmlspecialchars($sidebar_pos); ?>" id="main-sidebar-nav">
            <div class="sidebar-inner">
                <div class="sidebar-top">
                    <?php echo LogoRenderer::renderLogoLink($data, $logo_href); ?>
                </div>

                <nav class="sidebar-links">
                    <?php foreach ($nav_items as $item): ?>
                        <?php 
                            $href = !empty($item['url']) 
                                ? htmlspecialchars($item['url']) 
                                : ($is_subpage_view ? htmlspecialchars($home_url . '#' . $item['id']) : '#' . htmlspecialchars($item['id']));
                            $icon = !empty($item['icon']) ? $item['icon'] : SectionManager::getDefaultIcon($item['type'] ?? $item['id']);
                        ?>
                        <a href="<?php echo $href; ?>" class="sidebar-nav-item">
                            <span class="material-icons nav-icon"><?php echo htmlspecialchars($icon); ?></span>
                            <span class="nav-text"><?php echo htmlspecialchars($item['nav_label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="sidebar-bottom">
                    <?php if ($contact_place === 'header' || $contact_place === 'both'): ?>
                        <label for="menu-toggle" class="sidebar-nav-item sidebar-trigger" style="cursor: pointer;">
                            <span class="material-icons nav-icon">mail</span>
                            <span class="nav-text"><?php echo htmlspecialchars(__('nav.contact', 'Kontakt')); ?></span>
                        </label>
                    <?php endif; ?>
                    <?php if ($impressum_place === 'header' || $impressum_place === 'both'): ?>
                        <label for="menu-toggle-impressum" class="sidebar-nav-item sidebar-trigger" style="cursor: pointer;">
                            <span class="material-icons nav-icon">policy</span>
                            <span class="nav-text"><?php echo htmlspecialchars(__('nav.impressum', 'Impressum')); ?></span>
                        </label>
                    <?php endif; ?>

                    <?php if (!empty($lang_switch_items) && count($lang_switch_items) > 1): ?>
                        <div class="sidebar-lang-switch">
                            <?php foreach ($lang_switch_items as $l): ?>
                                <?php if ($l['active']): ?>
                                    <span class="sidebar-lang-badge active" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></span>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($l['url']); ?>" class="sidebar-lang-badge" title="<?php echo htmlspecialchars($l['name']); ?>"><?php echo strtoupper(htmlspecialchars($l['code'])); ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- ───── Mobile Top Bar für Sidebar-Modus (<= 992px) ───── -->
        <header class="header-nav sidebar-mobile-bar" style="display: none;">
            <div class="header-container">
                <?php echo LogoRenderer::renderLogoLink($data, $logo_href); ?>
                <!-- Mobile Menu Trigger (Hamburger) -->
                <label for="menu-toggle-mobile" class="btn btn-outline" style="display: inline-flex; padding: 6px 12px; font-size: 0.85rem; cursor: pointer;" id="mobile-nav-trigger">
                    <span class="material-icons" style="font-size: 18px; vertical-align: middle;">menu</span>
                </label>
            </div>
        </header>

    <?php else: ?>
        <!-- ───── Waagerechte Navigation (Minimal oder mit Icons) ───── -->
        <header class="header-nav <?php echo ($menu_style === 'horizontal_icons') ? 'nav-has-icons' : 'nav-minimal'; ?>">
            <div class="header-container">
                <?php echo LogoRenderer::renderLogoLink($data, $logo_href); ?>
                <nav class="nav-links">
                    <?php foreach ($nav_items as $item): ?>
                        <?php 
                            $href = !empty($item['url']) 
                                ? htmlspecialchars($item['url']) 
                                : ($is_subpage_view ? htmlspecialchars($home_url . '#' . $item['id']) : '#' . htmlspecialchars($item['id']));
                            $icon = !empty($item['icon']) ? $item['icon'] : SectionManager::getDefaultIcon($item['type'] ?? $item['id']);
                        ?>
                        <a href="<?php echo $href; ?>" class="nav-item <?php echo ($menu_style === 'horizontal_icons') ? 'has-icon' : ''; ?>">
                            <?php if ($menu_style === 'horizontal_icons'): ?>
                                <span class="material-icons nav-icon"><?php echo htmlspecialchars($icon); ?></span>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($item['nav_label']); ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($contact_place === 'header' || $contact_place === 'both'): ?>
                        <label for="menu-toggle" class="nav-item <?php echo ($menu_style === 'horizontal_icons') ? 'has-icon' : ''; ?>" style="cursor: pointer;">
                            <?php if ($menu_style === 'horizontal_icons'): ?>
                                <span class="material-icons nav-icon">mail</span>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars(__('nav.contact', 'Kontakt')); ?></span>
                        </label>
                    <?php endif; ?>

                    <?php if ($impressum_place === 'header' || $impressum_place === 'both'): ?>
                        <label for="menu-toggle-impressum" class="nav-item <?php echo ($menu_style === 'horizontal_icons') ? 'has-icon' : ''; ?>" style="cursor: pointer;">
                            <?php if ($menu_style === 'horizontal_icons'): ?>
                                <span class="material-icons nav-icon">policy</span>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars(__('nav.impressum', 'Impressum')); ?></span>
                        </label>
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
    <?php endif; ?>
