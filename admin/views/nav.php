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
        <!-- ======================================================================
             BACKEND-MENÜ (.admin-nav TAB-NAVIGATION)
             Bündelung unter "Inhalte / Content" gemäß Nutzeranforderung
             ====================================================================== -->
        <?php 
            $nav_user = UserManager::getCurrentUser($data ?? null);
            $nav_role = $nav_user['role'] ?? UserManager::ROLE_ADMIN;
        ?>
        <div class="admin-nav">
            <button class="admin-nav-link active" id="main-nav-dashboard" onclick="switchTab('tab-dashboard')">
                <span class="material-icons" style="font-size:18px;">dashboard</span> Dashboard
            </button>
            <button class="admin-nav-link" id="main-nav-content" onclick="switchTab('tab-content')">
                <span class="material-icons" style="font-size:18px;">folder_special</span> Inhalte &amp; Seiten
            </button>
            <?php if (UserManager::canAccessTab('tab-inbox', $nav_role)): ?>
                <button class="admin-nav-link" onclick="switchTab('tab-inbox')">
                    <span class="material-icons" style="font-size:18px;">inbox</span> Kontaktanfragen<?php if ($inbox_new_count > 0): ?> <span style="background:#ef4444; color:#fff; border-radius:999px; padding:1px 8px; font-size:0.72rem; font-weight:700; margin-left:4px;"><?php echo (int)$inbox_new_count; ?></span><?php endif; ?>
                </button>
            <?php endif; ?>
            <?php if ($nav_role === UserManager::ROLE_ADMIN): ?>
                <button class="admin-nav-link" onclick="switchTab('tab-users')">
                    <span class="material-icons" style="font-size:18px;">group</span> Benutzer
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-settings')">
                    <span class="material-icons" style="font-size:18px;">palette</span> Darstellung &amp; Design
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-seo')">
                    <span class="material-icons" style="font-size:18px;">travel_explore</span> SEO &amp; Sharing
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-security')">
                    <span class="material-icons" style="font-size:18px;">security</span> Sicherheit &amp; System
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-plugins')">
                    <span class="material-icons" style="font-size:18px;">extension</span> Plugins
                </button>
            <?php endif; ?>
        </div>

        <!-- Sub-Navigation für Inhalte & Seiten -->
        <div class="admin-subnav" id="admin-content-subnav" style="display:none;">
            <span class="admin-subnav-label"><span class="material-icons" style="font-size:16px;">tune</span> Bereich:</span>
            <button class="admin-subnav-link active" onclick="switchTab('tab-texts')">
                <span class="material-icons" style="font-size:17px;">edit_note</span> Startseite &amp; Sektionen
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-pages')">
                <span class="material-icons" style="font-size:17px;">article</span> Einzelseiten
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-blog')">
                <span class="material-icons" style="font-size:17px;">newspaper</span> Blog &amp; Aktuelles
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-faqs')">
                <span class="material-icons" style="font-size:17px;">quiz</span> FAQs
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-team')">
                <span class="material-icons" style="font-size:17px;">badge</span> Team
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-pricing')">
                <span class="material-icons" style="font-size:17px;">sell</span> Preise &amp; Pakete
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-downloads')">
                <span class="material-icons" style="font-size:17px;">folder_zip</span> Downloads
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-gallery')">
                <span class="material-icons" style="font-size:17px;">photo_library</span> Galerie &amp; Werkstücke
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-courses')">
                <span class="material-icons" style="font-size:17px;">event</span> Workshops &amp; Kurse
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-past')">
                <span class="material-icons" style="font-size:17px;">photo_camera</span> Kursimpressionen
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-impressum')">
                <span class="material-icons" style="font-size:17px;">policy</span> Impressum &amp; Rechtliches
            </button>
        </div>
