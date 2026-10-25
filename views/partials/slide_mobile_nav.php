<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Slide-in Mobile Navigation
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 *
 * @var array $nav_items
 */
?>
    <!-- ======================================================================
         MOBIL-MENÜ (SLIDE-IN NAVIGATION)
         ====================================================================== -->
    <label for="menu-toggle-mobile" class="menu-overlay mobile-overlay"></label>
    <div class="slide-menu mobile-menu">
        <label for="menu-toggle-mobile" class="close-menu-btn">&times;</label>
        <h2 style="color: var(--primary); margin-bottom: 2rem;">Navigation</h2>
        <nav style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php 
                $is_subpage_view = isset($_GET['page']) || isset($_GET['blog']);
                $home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
            ?>
            <?php foreach ($nav_items as $item): ?>
                <?php 
                    $href = !empty($item['url']) 
                        ? htmlspecialchars($item['url']) 
                        : ($is_subpage_view ? htmlspecialchars($home_url . '#' . $item['id']) : '#' . htmlspecialchars($item['id']));
                    $icon = !empty($item['icon']) ? $item['icon'] : SectionManager::getDefaultIcon($item['type'] ?? $item['id']);
                ?>
                <a href="<?php echo $href; ?>" onclick="closeMobileMenu()" class="nav-item" style="font-size: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:flex; align-items:center; gap:12px;">
                    <span class="material-icons" style="font-size:22px; color:var(--primary);"><?php echo htmlspecialchars($icon); ?></span>
                    <span><?php echo htmlspecialchars($item['nav_label']); ?></span>
                </a>
            <?php endforeach; ?>
            <?php 
                $contact_place = $data['settings']['contact_placement'] ?? 'both';
                $impressum_place = $data['settings']['impressum_placement'] ?? 'both';
            ?>
            <?php if ($contact_place === 'header' || $contact_place === 'both'): ?>
                <label for="menu-toggle" onclick="closeMobileMenuAndOpenContact()" class="nav-item" style="cursor: pointer; font-size: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:flex; align-items:center; gap:12px; color: var(--primary);">
                    <span class="material-icons" style="font-size:22px;">mail</span>
                    <span><?php echo htmlspecialchars(__('nav.contact', 'Kontakt')); ?></span>
                </label>
            <?php endif; ?>
            <?php if ($impressum_place === 'header' || $impressum_place === 'both'): ?>
                <label for="menu-toggle-impressum" onclick="closeMobileMenuAndOpenImpressum()" class="nav-item" style="cursor: pointer; font-size: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:flex; align-items:center; gap:12px; color: var(--primary);">
                    <span class="material-icons" style="font-size:22px;">policy</span>
                    <span><?php echo htmlspecialchars(__('nav.impressum', 'Impressum')); ?></span>
                </label>
            <?php endif; ?>
        </nav>
        <?php if (!empty($data['settings']['accessibility_bar_enabled'])): ?>
            <div class="mobile-a11y-controls" style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <span style="font-size:0.85rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:1px;"><?php echo htmlspecialchars(__('nav.appearance', 'Ansicht')); ?></span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="a11y-nav-btn a11y-theme-btn" onclick="toggleWeaveTheme()" title="Dunkelmodus" aria-label="Farbschema wechseln">
                        <span class="a11y-theme-icon">🌙</span>
                    </button>
                    <div class="a11y-nav-zoom-group">
                        <button type="button" class="a11y-nav-btn a11y-zoom-btn" onclick="adjustWeaveFontSize(-5)" title="Schrift verkleinern (A-)">A-</button>
                        <button type="button" class="a11y-nav-btn a11y-zoom-btn" onclick="resetWeaveFontSize()" title="Standard-Schriftgröße (100%)">A</button>
                        <button type="button" class="a11y-nav-btn a11y-zoom-btn" onclick="adjustWeaveFontSize(5)" title="Schrift vergrößern (A+)">A+</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($lang_switch_items) && count($lang_switch_items) > 1): ?>
            <div class="mobile-lang-switch" style="<?php echo empty($data['settings']['accessibility_bar_enabled']) ? 'margin-top:2rem; padding-top:1.5rem; border-top:1px solid var(--border-color);' : 'margin-top:1.25rem; padding-top:1.25rem; border-top:1px dashed var(--border-color);'; ?>">
                <span style="display:block; font-size:0.85rem; color:var(--text-secondary); margin-bottom:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:1px;"><?php echo htmlspecialchars(__('nav.language', 'Sprache')); ?></span>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($lang_switch_items as $l): ?>
                        <a href="<?php echo htmlspecialchars($l['url']); ?>" style="display:flex; align-items:center; gap:10px; font-size:1.05rem; padding:6px 0; text-decoration:none; color:<?php echo $l['active'] ? 'var(--primary)' : 'var(--text-primary)'; ?>; font-weight:<?php echo $l['active'] ? '700' : '400'; ?>;">
                            <span style="font-size:1.3rem;"><?php echo $l['flag']; ?></span>
                            <span><?php echo htmlspecialchars($l['native']); ?></span>
                            <?php if ($l['active']): ?>
                                <span class="material-icons" style="font-size:18px; margin-left:auto; color:var(--primary);">check</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
