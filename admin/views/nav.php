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
        <div id="toast"><span class="material-icons" style="font-size:18px;">check</span> <span id="toast-text"><?php echo __a('admin.msg.link_copied', 'Link kopiert!'); ?></span></div>

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
                <span class="material-icons" style="font-size:18px;">dashboard</span> <?php echo __a('admin.nav.dashboard', 'Dashboard'); ?>
            </button>
            <button class="admin-nav-link" id="main-nav-content" onclick="switchTab('tab-content')">
                <span class="material-icons" style="font-size:18px;">folder_special</span> <?php echo __a('admin.nav.content', 'Inhalte &amp; Seiten'); ?>
            </button>
            <button class="admin-nav-link" id="main-nav-media" onclick="switchTab('tab-media')">
                <span class="material-icons" style="font-size:18px;">perm_media</span> <?php echo __a('admin.nav.media', 'Mediathek'); ?>
            </button>
            <?php if ($nav_role === UserManager::ROLE_ADMIN): ?>
                <button class="admin-nav-link" id="main-nav-display" onclick="switchTab('tab-display')">
                    <span class="material-icons" style="font-size:18px;">visibility</span> <?php echo __a('admin.nav.display', 'Darstellung'); ?></button>
                <button class="admin-nav-link" id="main-nav-design" onclick="switchTab('tab-design')">
                    <span class="material-icons" style="font-size:18px;">brush</span> <?php echo __a('admin.nav.design', 'Design'); ?></button>
                <button class="admin-nav-link" onclick="switchTab('tab-users')">
                    <span class="material-icons" style="font-size:18px;">group</span> <?php echo __a('admin.nav.users', 'Benutzer'); ?>
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-plugins')">
                    <span class="material-icons" style="font-size:18px;">extension</span> <?php echo __a('admin.nav.plugins', 'Plugins'); ?>
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-seo')">
                    <span class="material-icons" style="font-size:18px;">travel_explore</span> <?php echo __a('admin.nav.seo', 'SEO &amp; Sharing'); ?>
                </button>
                <button class="admin-nav-link" onclick="switchTab('tab-security')">
                    <span class="material-icons" style="font-size:18px;">security</span> <?php echo __a('admin.nav.security', 'Sicherheit &amp; System'); ?>
                </button>
            <?php endif; ?>
            <?php if (UserManager::canAccessTab('tab-inbox', $nav_role)): ?>
                <button class="admin-nav-link" onclick="switchTab('tab-inbox')">
                    <span class="material-icons" style="font-size:18px;">inbox</span> <?php echo __a('admin.nav.inbox', 'Kontaktanfragen'); ?><?php if ($inbox_new_count > 0): ?> <span style="background:#ef4444; color:#fff; border-radius:999px; padding:1px 8px; font-size:0.72rem; font-weight:700; margin-left:4px;"><?php echo (int)$inbox_new_count; ?></span><?php endif; ?>
                </button>
            <?php endif; ?>
        </div>

        <?php
            $nav_sections = SectionManager::ensureSections($data);
            $active_sec_types = [];
            foreach ($nav_sections as $ns) {
                if (!empty($ns['visible'])) {
                    $active_sec_types[] = $ns['type'] ?? $ns['id'];
                }
            }
        ?>
        <!-- Sub-Navigation für Inhalte & Seiten (dynamisch gefiltert nach aktiven Modulen) -->
        <div class="admin-subnav" id="admin-content-subnav" style="display:none;">
            <span class="admin-subnav-label"><span class="material-icons" style="font-size:16px;">tune</span> <?php echo __a('admin.subnav.label', 'Bereich:'); ?></span>
            <button class="admin-subnav-link active" onclick="switchTab('tab-texts')">
                <span class="material-icons" style="font-size:17px;">dashboard_customize</span> <?php echo __a('admin.subnav.home_pool', 'Startseite &amp; Modul-Pool'); ?>
            </button>
            <button class="admin-subnav-link" onclick="switchTab('tab-pages')">
                <span class="material-icons" style="font-size:17px;">article</span> <?php echo __a('admin.subnav.pages', 'Einzelseiten'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="blog" onclick="switchTab('tab-blog')" style="<?php echo in_array('blog', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">newspaper</span> <?php echo __a('admin.subnav.blog', 'Blog &amp; Aktuelles'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="faq" onclick="switchTab('tab-faqs')" style="<?php echo in_array('faq', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">quiz</span> <?php echo __a('admin.subnav.faq', 'FAQs'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="team" onclick="switchTab('tab-team')" style="<?php echo in_array('team', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">badge</span> <?php echo __a('admin.subnav.team', 'Team'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="pricing" onclick="switchTab('tab-pricing')" style="<?php echo in_array('pricing', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">sell</span> <?php echo __a('admin.subnav.pricing', 'Preise &amp; Pakete'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="downloads" onclick="switchTab('tab-downloads')" style="<?php echo in_array('downloads', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">folder_zip</span> <?php echo __a('admin.subnav.downloads', 'Downloads'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="gallery" onclick="switchTab('tab-gallery')" style="<?php echo in_array('gallery', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">photo_library</span> <?php echo __a('admin.subnav.gallery', 'Galerie &amp; Werkstücke'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="courses" onclick="switchTab('tab-courses')" style="<?php echo in_array('courses', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">event</span> <?php echo __a('admin.subnav.courses', 'Workshops &amp; Kurse'); ?>
            </button>
            <button class="admin-subnav-link" data-sec-type="past_courses" onclick="switchTab('tab-past')" style="<?php echo in_array('past_courses', $active_sec_types, true) ? '' : 'display:none;'; ?>">
                <span class="material-icons" style="font-size:17px;">photo_camera</span> <?php echo __a('admin.subnav.past_courses', 'Kursimpressionen'); ?>
            </button>
            <?php 
                // Dynamisch registrierte Subnav-Einträge von Plugins (Phase 21)
                $custom_section_types = SectionManager::getRegisteredSectionTypes();
                foreach ($custom_section_types as $cType => $cDef):
                    if (in_array($cType, ['courses', 'past_courses', 'downloads'], true)) {
                        continue; // Bereits oben im Kernmenü vorhanden
                    }
                    if (empty($cDef['admin_tab']) || !in_array($cType, $active_sec_types, true)) {
                        continue;
                    }
            ?>
                <button class="admin-subnav-link" data-sec-type="<?php echo htmlspecialchars($cType); ?>" onclick="switchTab('<?php echo htmlspecialchars($cDef['admin_tab']); ?>')">
                    <span class="material-icons" style="font-size:17px;"><?php echo htmlspecialchars($cDef['icon'] ?? 'extension'); ?></span> <?php echo htmlspecialchars($cDef['title'] ?? ucfirst($cType)); ?>
                </button>
            <?php endforeach; ?>
            <button class="admin-subnav-link" onclick="switchTab('tab-impressum')">
                <span class="material-icons" style="font-size:17px;">policy</span> <?php echo __a('admin.subnav.impressum', 'Impressum &amp; Rechtliches'); ?>
            </button>
        </div>
