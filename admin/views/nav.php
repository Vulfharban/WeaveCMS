<?php
/**
 * WeaveCMS – Admin Navigation & Alerts View
 * 
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 * Dies gilt im Backend insbesondere für:
 * - Admin-Header & Abmelde-Menü
 * - Reiter-Navigation (.admin-nav) inklusive aller Reiter, Benennungen und Reihenfolgen
 * ============================================================================
 * 
 * @var string $success_msg Erfolgsmeldung
 * @var string $error_msg Fehlermeldung
 * @var int $inbox_new_count Anzahl ungelesener Kontaktanfragen
 */
?>
        <!-- Toast Feedback -->
        <div id="toast"><span class="material-icons" style="font-size:18px;">check</span> <span id="toast-text">Link kopiert!</span></div>

        <!-- Success & Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--primary); color: #34d399; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display:flex; align-items:center; gap: 8px;">
                <span class="material-icons">check_circle</span> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; color: #f87171; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display:flex; align-items:center; gap: 8px;">
                <span class="material-icons">error</span> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- ======================================================================
             BACKEND-MENÜ (.admin-nav TAB-NAVIGATION)
             STRIKTE VORGABE: KEIN MENÜPUNKT ODER REITER DARF OHNE VORHERIGE
             AUSDRÜCKLICHE ZUSTIMMUNG DES USERS GEÄNDERT, VERSCHOBEN ODER ENTFERNT WERDEN!
             ====================================================================== -->
        <div class="admin-nav">
            <button class="admin-nav-link active" onclick="switchTab('tab-texts')"><span class="material-icons" style="font-size:18px;">edit_note</span> Startseite &amp; Über mich</button>
            <button class="admin-nav-link" onclick="switchTab('tab-impressum')"><span class="material-icons" style="font-size:18px;">contact_page</span> Impressum &amp; Kontaktdaten</button>
            <button class="admin-nav-link" onclick="switchTab('tab-gallery')"><span class="material-icons" style="font-size:18px;">photo_library</span> Galerie</button>
            <button class="admin-nav-link" onclick="switchTab('tab-past')"><span class="material-icons" style="font-size:18px;">photo_camera</span> Kursimpressionen</button>
            <button class="admin-nav-link" onclick="switchTab('tab-courses')"><span class="material-icons" style="font-size:18px;">event</span> Workshops</button>
            <button class="admin-nav-link" onclick="switchTab('tab-settings')"><span class="material-icons" style="font-size:18px;">palette</span> Darstellung</button>
            <button class="admin-nav-link" onclick="switchTab('tab-seo')"><span class="material-icons" style="font-size:18px;">travel_explore</span> SEO &amp; Sharing</button>
            <button class="admin-nav-link" onclick="switchTab('tab-inbox')"><span class="material-icons" style="font-size:18px;">inbox</span> Kontaktanfragen<?php if ($inbox_new_count > 0): ?> <span style="background:#ef4444; color:#fff; border-radius:999px; padding:1px 8px; font-size:0.72rem; font-weight:700; margin-left:4px;"><?php echo (int)$inbox_new_count; ?></span><?php endif; ?></button>
            <button class="admin-nav-link" onclick="switchTab('tab-security')"><span class="material-icons" style="font-size:18px;">security</span> Sicherheit</button>
        </div>
