<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Footer
 * ============================================================================
 *
 * @var array $data
 */
?>
    <!-- Footer -->
    <footer style="margin-top: 5rem; padding: 4rem 0; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
        <div class="container" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['texts']['contact_name'] ?? 'Maxima Musterfrau'); ?>. Alle Rechte vorbehalten.</p>
            <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; align-items: center;">
                <label for="menu-toggle" style="cursor: pointer; color: var(--primary); font-weight:600;">Kontakt</label>
                <label for="menu-toggle-impressum" style="cursor: pointer; color: var(--primary); font-weight:600;">Impressum &amp; Datenschutz</label>
                <a href="admin/" style="color: var(--text-secondary); opacity: 0.5; font-size:0.8rem;">Admin-Bereich</a>
            </div>
        </div>
    </footer>
