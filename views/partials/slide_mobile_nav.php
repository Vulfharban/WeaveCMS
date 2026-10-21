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
            <?php foreach ($nav_items as $item): ?>
                <a href="#<?php echo htmlspecialchars($item['id']); ?>" onclick="closeMobileMenu()" class="nav-item" style="font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block;"><?php echo htmlspecialchars($item['nav_label']); ?></a>
            <?php endforeach; ?>
            <label for="menu-toggle" onclick="closeMobileMenuAndOpenContact()" class="nav-item" style="cursor: pointer; font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block; color: var(--primary);">Kontakt</label>
            <label for="menu-toggle-impressum" onclick="closeMobileMenuAndOpenImpressum()" class="nav-item" style="cursor: pointer; font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block; color: var(--primary);">Impressum</label>
        </nav>
    </div>
