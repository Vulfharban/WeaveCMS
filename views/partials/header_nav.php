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
    <header class="header-nav">
        <div class="header-container">
            <a href="#" class="logo"><?php if ($logo_pos === 'header_before'): echo $header_logo_html; endif; ?><span><?php echo htmlspecialchars($data['texts']['contact_name'] ?? 'Atelier Musterfrau'); ?></span><?php if ($logo_pos === 'header_after'): echo $header_logo_html; endif; ?></a>
            <nav class="nav-links">
                <?php foreach ($nav_items as $item): ?>
                    <a href="#<?php echo htmlspecialchars($item['id']); ?>" class="nav-item"><?php echo htmlspecialchars($item['nav_label']); ?></a>
                <?php endforeach; ?>
                <label for="menu-toggle" class="nav-item" style="cursor: pointer;">Kontakt</label>
                <label for="menu-toggle-impressum" class="nav-item" style="cursor: pointer;">Impressum</label>
            </nav>
            <!-- Mobile Menu Trigger (Hamburger) -->
            <label for="menu-toggle-mobile" class="btn btn-outline" style="display: none; padding: 6px 12px; font-size: 0.85rem; cursor: pointer;" id="mobile-nav-trigger">
                <span class="material-icons" style="font-size: 18px; vertical-align: middle;">menu</span>
            </label>
        </div>
    </header>
