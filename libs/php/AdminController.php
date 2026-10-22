<?php
/**
 * ============================================================================
 * WeaveCMS – Administrations-Controller & POST-Aktions-Dispatcher
 * ============================================================================
 * Kapselt alle Verwaltungsoperationen des Backends:
 * - Design- und Darstellungseinstellungen
 * - Sektionsverwaltung und Sortierung
 * - Postfachverwaltung (Kontaktanfragen)
 * - Speicher-Migration (JSON <-> MySQL)
 * - SEO- und Social-Sharing-Konfiguration
 * - Admin-Sicherheit (Passwort, PIN, Captcha, SMTP)
 * - 1-Klick-Backups und Systemwiederherstellung
 * - Galerie, Kategorien und Sortierung
 * - Kurse, Termine und Impressionen
 *
 * @package WeaveCMS\Admin
 */

require_once __DIR__ . '/ContactInbox.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/BackupManager.php';
require_once __DIR__ . '/SectionManager.php';
require_once __DIR__ . '/storage/StorageManager.php';

class AdminController {

    /**
     * Ermittelt den absoluten Pfad zur WeaveCMS-Wurzel.
     * @return string
     */
    public static function rootPath(): string {
        return dirname(__DIR__, 2);
    }

    /**
     * Hauptmethode zur Verarbeitung aller eingehenden POST-Aktionen im Admin-Bereich.
     *
     * @param string $dataFile Pfad zur data.json
     * @param array &$data Referenz auf das globale Datenarray
     * @param string $csrfToken Aktives CSRF-Sitzungstoken
     * @return array ['success_msg' => string, 'error_msg' => string, 'active_tab' => string]
     */
    public static function handlePost(string $dataFile, array &$data, string $csrfToken): array {
        $success_msg = '';
        $error_msg = '';
        $active_tab = !empty($_GET['tab']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['tab']) : 'tab-dashboard';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success_msg' => $success_msg,
                'error_msg' => $error_msg,
                'active_tab' => $active_tab
            ];
        }

        $action = $_POST['action'] ?? '';

        // CSRF-Schutz: Token validieren
        if (!isset($_POST['login'])) {
            $submitted_csrf = $_POST['csrf_token'] ?? '';
            if (empty($submitted_csrf) || !hash_equals($csrfToken, $submitted_csrf)) {
                return [
                    'success_msg' => '',
                    'error_msg' => 'Sicherheitswarnung (CSRF): Ungültiges oder abgelaufenes Sicherheits-Token. Die Aktion wurde abgebrochen.',
                    'active_tab' => $active_tab
                ];
            }
        }

        // Automatische Reiter-Ermittlung anhand des Aktionsnamens
        $active_tab = self::determineActiveTab($action);

        // RBAC-Berechtigungsprüfung auf Serverebene (Phase 10.5)
        $cur_user = UserManager::getCurrentUser($data);
        if (!UserManager::canExecuteAction($action, $cur_user['role'])) {
            SecurityManager::logEvent('AUTH_UNAUTHORIZED', "Unbefugter Aufruf von '{$action}' durch '{$cur_user['username']}' (Rolle: {$cur_user['role']})", 'WARNING');
            return [
                'success_msg' => '',
                'error_msg' => 'Fehlende Berechtigung: Deine Benutzerrolle erlaubt diese Aktion nicht.',
                'active_tab' => $active_tab
            ];
        }

        switch ($action) {
            case 'create_user':
            case 'update_user':
            case 'delete_user':
                self::handleUserAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'toggle_plugin':
            case 'save_plugin_settings':
                self::handlePluginAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_settings':
            case 'update_design':
                self::handleSettings($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'inbox_set_status':
            case 'inbox_delete':
                self::handleInbox($action, $success_msg, $error_msg);
                break;

            case 'dismiss_welcome':
                self::handleDismissWelcome($dataFile, $data);
                break;

            case 'move_section':
            case 'toggle_section_visibility':
            case 'update_section':
                self::handleSectionAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'migrate_storage_engine':
                self::handleStorageMigration($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_seo':
                self::handleSeo($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_credentials':
                self::handleCredentials($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_security':
                self::handleSecurity($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'test_smtp':
                self::handleSmtpTest($data, $success_msg, $error_msg);
                break;

            case 'create_backup':
            case 'restore_backup':
            case 'upload_restore':
            case 'delete_backup':
                self::handleBackupAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_category':
            case 'delete_category':
                self::handleCategoryAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_texts':
                self::handleTexts($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_impressum':
                self::handleImpressum($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_gallery':
            case 'delete_gallery':
            case 'reorder_gallery':
                self::handleGalleryAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_course':
            case 'edit_course':
            case 'delete_course':
                self::handleCourseAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_past_course_image':
            case 'delete_past_course_image':
                self::handlePastCourseAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_blog_post':
            case 'edit_blog_post':
            case 'delete_blog_post':
            case 'toggle_blog_status':
                self::handleBlogAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_page':
            case 'edit_page':
            case 'delete_page':
                self::handlePageAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_faq':
            case 'edit_faq':
            case 'delete_faq':
                self::handleFaqAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_team_member':
            case 'edit_team_member':
            case 'delete_team_member':
                self::handleTeamAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_download':
            case 'edit_download':
            case 'delete_download':
            case 'reset_download_counter':
                self::handleDownloadAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_pricing_plan':
            case 'edit_pricing_plan':
            case 'delete_pricing_plan':
                self::handlePricingAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;
        }

        return [
            'success_msg' => $success_msg,
            'error_msg' => $error_msg,
            'active_tab' => $active_tab
        ];
    }

    /**
     * Ermittelt den Ziel-Tab basierend auf dem Aktions-Schlüsselwort.
     *
     * @param string $action
     * @return string
     */
    public static function determineActiveTab(string $action): string {
        if (!empty($_POST['target_tab']) && is_string($_POST['target_tab'])) {
            return trim($_POST['target_tab']);
        }
        if (strpos($action, 'shop') !== false || strpos($action, 'webtrees') !== false || strpos($action, 'inventory') !== false || strpos($action, 'inv_') !== false) {
            return 'tab-plugins';
        }
        if (strpos($action, 'blog') !== false) {
            return 'tab-blog';
        } elseif (strpos($action, 'page') !== false) {
            return 'tab-pages';
        } elseif (strpos($action, 'faq') !== false) {
            return 'tab-faqs';
        } elseif (strpos($action, 'team') !== false) {
            return 'tab-team';
        } elseif (strpos($action, 'download') !== false) {
            return 'tab-downloads';
        } elseif (strpos($action, 'pricing') !== false) {
            return 'tab-pricing';
        } elseif (strpos($action, 'inbox') !== false) {
            return 'tab-inbox';
        } elseif (strpos($action, 'gallery') !== false || strpos($action, 'category') !== false) {
            return 'tab-gallery';
        } elseif (strpos($action, 'course') !== false) {
            return 'tab-courses';
        } elseif (strpos($action, 'past') !== false) {
            return 'tab-past';
        } elseif (strpos($action, 'seo') !== false) {
            return 'tab-seo';
        } elseif (
            strpos($action, 'security') !== false || 
            strpos($action, 'smtp') !== false || 
            strpos($action, 'credentials') !== false || 
            strpos($action, 'backup') !== false || 
            strpos($action, 'restore') !== false || 
            strpos($action, 'migrate') !== false
        ) {
            return 'tab-security';
        } elseif (strpos($action, 'plugin') !== false) {
            return 'tab-plugins';
        } elseif (strpos($action, 'settings') !== false || strpos($action, 'design') !== false) {
            return 'tab-settings';
        } elseif (strpos($action, 'contact') !== false || strpos($action, 'impressum') !== false) {
            return 'tab-impressum';
        } elseif (strpos($action, 'user') !== false) {
            return 'tab-users';
        } elseif (strpos($action, 'texts') !== false || strpos($action, 'section') !== false) {
            return 'tab-texts';
        }

        if (!empty($_GET['tab']) && is_string($_GET['tab'])) {
            return trim($_GET['tab']);
        }
        return 'tab-dashboard';
    }

    /**
     * Verarbeitet Plugin-Aktionen (Aktivieren, Deaktivieren, Konfiguration speichern) - Phase 13.
     */
    protected static function handlePluginAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($_POST['plugin_slug'] ?? ''));
        if (empty($slug)) {
            $error_msg = 'Ungültige Plugin-Kennung.';
            return;
        }

        if ($action === 'toggle_plugin') {
            $targetState = (int)($_POST['target_state'] ?? 0);
            if ($targetState === 1) {
                $res = PluginManager::activatePlugin($slug, $data);
            } else {
                $res = PluginManager::deactivatePlugin($slug, $data);
            }

            if ($res['success']) {
                save_data($dataFile, $data);
                $success_msg = $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } elseif ($action === 'save_plugin_settings') {
            $res = PluginManager::savePluginConfig($slug, $_POST, $data);
            if ($res['success']) {
                save_data($dataFile, $data);
                $success_msg = $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        }
    }

    /**
     * Verarbeitet Benutzer-Aktionen (Anlegen, Aktualisieren, Löschen) - Phase 10.
     */
    protected static function handleUserAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'create_user') {
            $username = trim($_POST['new_username'] ?? '');
            $email = trim($_POST['new_email'] ?? '');
            $password = trim($_POST['new_password'] ?? '');
            $role = trim($_POST['new_role'] ?? UserManager::ROLE_EDITOR);
            $pin = trim($_POST['new_pin'] ?? '');

            $res = UserManager::createUser($data, $username, $email, $password, $role, $pin);
            if ($res['success']) {
                save_data($dataFile, $data);
                $success_msg = $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } elseif ($action === 'update_user') {
            $id = trim($_POST['user_id'] ?? '');
            $fields = [
                'email'  => trim($_POST['edit_email'] ?? ''),
                'role'   => trim($_POST['edit_role'] ?? ''),
                'active' => isset($_POST['edit_active']) ? (int)$_POST['edit_active'] : 1
            ];
            if (!empty($_POST['edit_password'])) {
                $fields['new_password'] = trim($_POST['edit_password']);
            }
            if (isset($_POST['edit_pin'])) {
                $fields['new_pin'] = trim($_POST['edit_pin']);
            }

            $res = UserManager::updateUser($data, $id, $fields);
            if ($res['success']) {
                save_data($dataFile, $data);
                $success_msg = $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } elseif ($action === 'delete_user') {
            $id = trim($_POST['user_id'] ?? '');
            $res = UserManager::deleteUser($data, $id);
            if ($res['success']) {
                save_data($dataFile, $data);
                $success_msg = $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        }
    }

    /**
     * Verarbeitet Design-, Farbschema- und Darstellungsaktualisierungen.
     */
    protected static function handleSettings(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $gallery_layout = $_POST['gallery_layout'] ?? 'ambient_blur';
        if (!in_array($gallery_layout, ['ambient_blur', 'masonry', 'editorial', 'classic'])) {
            $gallery_layout = 'ambient_blur';
        }
        $data['settings']['gallery_layout'] = $gallery_layout;
        $data['settings']['gallery_lightbox'] = !empty($_POST['gallery_lightbox']);

        // Phase 11: Logo- & Branding-Verwaltung
        $logo_type = $_POST['logo_type'] ?? 'text';
        if (!in_array($logo_type, ['text', 'image', 'icon_text', 'motif'], true)) {
            $logo_type = 'text';
        }
        $data['settings']['logo_type'] = $logo_type;

        $logo_max_height = (int)($_POST['logo_max_height'] ?? 44);
        if ($logo_max_height < 24 || $logo_max_height > 120) {
            $logo_max_height = 44;
        }
        $data['settings']['logo_max_height'] = $logo_max_height;

        // Logo Upload
        if (!empty($_FILES['logo_file']['name'])) {
            $uploadedLogo = ImageUploader::uploadLogo('logo_file');
            if ($uploadedLogo) {
                $data['settings']['logo_image'] = $uploadedLogo;
            } else {
                $error_msg = 'Fehler beim Hochladen des Logos (nur SVG, PNG, WebP, JPG erlaubt).';
            }
        } elseif (!empty($_POST['delete_logo'])) {
            $data['settings']['logo_image'] = '';
        }

        // Favicon Upload
        if (!empty($_FILES['favicon_file']['name'])) {
            $uploadedFav = ImageUploader::uploadFavicon('favicon_file');
            if ($uploadedFav) {
                $data['settings']['favicon'] = $uploadedFav;
            } else {
                $error_msg = 'Fehler beim Hochladen des Favicons (nur ICO, SVG, PNG erlaubt).';
            }
        } elseif (!empty($_POST['delete_favicon'])) {
            $data['settings']['favicon'] = '';
        }

        $hero_logo_motif = $_POST['hero_logo_motif'] ?? 'paw_simple';
        if (!in_array($hero_logo_motif, ['paw_simple', 'paw_beach', 'paw_willow', 'none'])) {
            $hero_logo_motif = 'paw_simple';
        }
        $data['settings']['hero_logo_motif'] = $hero_logo_motif;

        $hero_logo_color = trim($_POST['hero_logo_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $hero_logo_color)) {
            $hero_logo_color = '#b26c39';
        }
        $data['settings']['hero_logo_color'] = $hero_logo_color;

        $logo_position = $_POST['logo_position'] ?? 'header_before';
        if (!in_array($logo_position, ['header_before', 'header_after', 'hero_before', 'hero_after'])) {
            $logo_position = 'header_before';
        }
        $data['settings']['logo_position'] = $logo_position;

        $design_theme = $_POST['design_theme'] ?? 'nature_classic';
        if (!in_array($design_theme, ['nature_classic', 'moor_amber', 'nordic_sage', 'terracotta_atelier', 'custom'])) {
            $design_theme = 'nature_classic';
        }
        $data['settings']['design_theme'] = $design_theme;

        $container_width = $_POST['container_width'] ?? '1200px';
        if (!in_array($container_width, ['1050px', '1200px', '1400px'])) {
            $container_width = '1200px';
        }
        $data['settings']['container_width'] = $container_width;

        $primary_color = trim($_POST['primary_color'] ?? '#2e6248');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $primary_color)) {
            $primary_color = '#2e6248';
        }
        $data['settings']['primary_color'] = $primary_color;

        $accent_color = trim($_POST['accent_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $accent_color)) {
            $accent_color = '#b26c39';
        }
        $data['settings']['accent_color'] = $accent_color;

        $bg_color = trim($_POST['bg_color'] ?? '#fbf9f6');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $bg_color)) {
            $bg_color = '#fbf9f6';
        }
        $data['settings']['bg_color'] = $bg_color;

        $font_scale = $_POST['font_scale'] ?? '100%';
        if (!in_array($font_scale, ['95%', '100%', '108%'])) {
            $font_scale = '100%';
        }
        $data['settings']['font_scale'] = $font_scale;

        $card_radius = $_POST['card_radius'] ?? '20px';
        if (!in_array($card_radius, ['10px', '20px', '28px'])) {
            $card_radius = '20px';
        }
        $data['settings']['card_radius'] = $card_radius;

        $gallery_gap = $_POST['gallery_gap'] ?? '2rem';
        if (!in_array($gallery_gap, ['1.25rem', '2rem', '2.75rem'])) {
            $gallery_gap = '2rem';
        }
        $data['settings']['gallery_gap'] = $gallery_gap;

        // Platzierung von Kontakt und Impressum (Header / Footer / Beides)
        $contact_placement = $_POST['contact_placement'] ?? 'both';
        if (!in_array($contact_placement, ['header', 'footer', 'both'], true)) {
            $contact_placement = 'both';
        }
        $data['settings']['contact_placement'] = $contact_placement;

        $impressum_placement = $_POST['impressum_placement'] ?? 'both';
        if (!in_array($impressum_placement, ['header', 'footer', 'both'], true)) {
            $impressum_placement = 'both';
        }
        $data['settings']['impressum_placement'] = $impressum_placement;

        // Phase 18: Globaler Website-Architektur-Wahlschalter
        $site_architecture = $_POST['site_architecture'] ?? SectionManager::ARCHITECTURE_ONEPAGE;
        if (!in_array($site_architecture, [SectionManager::ARCHITECTURE_ONEPAGE, SectionManager::ARCHITECTURE_MULTIPAGE, SectionManager::ARCHITECTURE_HYBRID], true)) {
            $site_architecture = SectionManager::ARCHITECTURE_ONEPAGE;
        }
        $data['settings']['site_architecture'] = $site_architecture;

        // Phase 19: Menü-Stil & Sidebar-Position
        $cur_menu_types = ThemeManager::getMenuTypes($data);
        $menu_style = $_POST['menu_style'] ?? ThemeManager::MENU_HORIZONTAL_MINIMAL;
        if (!isset($cur_menu_types[$menu_style])) {
            $menu_style = ThemeManager::MENU_HORIZONTAL_MINIMAL;
        }
        $data['settings']['menu_style'] = $menu_style;

        $sidebar_position = $_POST['sidebar_position'] ?? ThemeManager::SIDEBAR_LEFT;
        if (!in_array($sidebar_position, [ThemeManager::SIDEBAR_LEFT, ThemeManager::SIDEBAR_RIGHT], true)) {
            $sidebar_position = ThemeManager::SIDEBAR_LEFT;
        }
        $data['settings']['sidebar_position'] = $sidebar_position;

        // Besucher-Barrierefreiheitsleiste (Phase 16)
        $data['settings']['accessibility_bar_enabled'] = !empty($_POST['accessibility_bar_enabled']);

        // Mehrsprachigkeit (Phase 8): Primärsprache & aktive Sprachen
        $supported = LanguageManager::getSupportedLanguages();
        $primary_lang = trim($_POST['primary_language'] ?? ($data['settings']['primary_language'] ?? 'de'));
        if (!isset($supported[$primary_lang])) {
            $primary_lang = 'de';
        }
        $data['settings']['primary_language'] = $primary_lang;

        $posted_active = $_POST['active_languages'] ?? null;
        $active_langs = [];
        if (is_array($posted_active)) {
            foreach ($posted_active as $code) {
                if (isset($supported[$code]) && !in_array($code, $active_langs, true)) {
                    $active_langs[] = $code;
                }
            }
        }
        if (!in_array($primary_lang, $active_langs, true)) {
            array_unshift($active_langs, $primary_lang);
        }
        $data['settings']['active_languages'] = $active_langs;

        if (save_data($dataFile, $data)) {
            $success_msg = 'Darstellung, Design &amp; Spracheinstellungen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Darstellungseinstellungen.';
        }
    }

    /**
     * Verarbeitet Kontaktanfragen im Postfach (Status ändern, löschen).
     */
    protected static function handleInbox(string $action, string &$success_msg, string &$error_msg): void {
        if ($action === 'inbox_set_status') {
            if (ContactInbox::setStatus($_POST['id'] ?? '', $_POST['status'] ?? '')) {
                $success_msg = 'Status der Anfrage aktualisiert.';
            } else {
                $error_msg = 'Status konnte nicht aktualisiert werden.';
            }
        } elseif ($action === 'inbox_delete') {
            if (ContactInbox::delete($_POST['id'] ?? '')) {
                $success_msg = 'Anfrage gelöscht.';
            } else {
                $error_msg = 'Anfrage nicht gefunden.';
            }
        }
    }

    /**
     * Schließt das Willkommens-Modal nach der Installation.
     */
    protected static function handleDismissWelcome(string $dataFile, array &$data): void {
        $data['settings']['welcome_shown'] = true;
        save_data($dataFile, $data);
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: ./');
        exit;
    }

    /**
     * Verarbeitet Sektions-Reihenfolge und Sichtbarkeiten.
     */
    protected static function handleSectionAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $sec_id = $_POST['section_id'] ?? '';
        if ($action === 'move_section') {
            $dir = $_POST['direction'] ?? 'up';
            if (SectionManager::moveSection($data, $sec_id, $dir)) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Sektions-Reihenfolge erfolgreich aktualisiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Reihenfolge.';
                }
            } else {
                $error_msg = 'Sektion konnte nicht verschoben werden.';
            }
        } elseif ($action === 'toggle_section_visibility') {
            if (SectionManager::setVisibility($data, $sec_id)) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Sektions-Sichtbarkeit erfolgreich aktualisiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Sichtbarkeit.';
                }
            } else {
                $error_msg = 'Sektion nicht gefunden.';
            }
        } elseif ($action === 'update_section') {
            $title = trim($_POST['title'] ?? '');
            $nav_label = trim($_POST['nav_label'] ?? '');
            $in_nav = !empty($_POST['in_nav']);

            // Mehrsprachige Menü- & Titelübersetzungen sammeln
            $active_langs = LanguageManager::getActiveLanguages($data);
            $primary_lang = LanguageManager::getPrimaryLanguage($data);
            $translations = [];

            // Bestehende Übersetzungen dieser Sektion erhalten
            if (!empty($data['sections']) && is_array($data['sections'])) {
                foreach ($data['sections'] as $s) {
                    if ($s['id'] === $sec_id && !empty($s['translations']) && is_array($s['translations'])) {
                        $translations = $s['translations'];
                        break;
                    }
                }
            }

            foreach ($active_langs as $lang) {
                if ($lang === $primary_lang) continue;
                $t_nav = trim($_POST['trans_' . $lang . '_nav_label'] ?? '');
                $t_title = trim($_POST['trans_' . $lang . '_title'] ?? '');
                if ($t_nav !== '' || $t_title !== '') {
                    $translations[$lang] = [
                        'nav_label' => $t_nav,
                        'title' => $t_title
                    ];
                } elseif (isset($_POST['trans_' . $lang . '_nav_label'])) {
                    unset($translations[$lang]);
                }
            }

            $placement = $_POST['placement'] ?? 'both';
            if (!in_array($placement, ['both', 'home', 'page'], true)) {
                $placement = 'both';
            }

            // Phase 19: Material-Icon
            $icon = trim($_POST['icon'] ?? '');

            if (SectionManager::updateSection($data, $sec_id, [
                'title' => $title,
                'nav_label' => $nav_label,
                'in_nav' => $in_nav,
                'placement' => $placement,
                'icon' => $icon,
                'translations' => $translations
            ])) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Sektion & Menü-Titel „' . htmlspecialchars($nav_label ?: $title) . '“ erfolgreich aktualisiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Sektion.';
                }
            } else {
                $error_msg = 'Sektion nicht gefunden.';
            }
        }
    }

    /**
     * Führt eine 1-Klick-Speicher-Migration durch (JSON <-> MySQL).
     */
    protected static function handleStorageMigration(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $target_engine = trim($_POST['target_engine'] ?? 'json');
        $migrate_pin = trim($_POST['migrate_pin'] ?? '');

        if ($target_engine === 'json') {
            $res = StorageManager::migrateToJson($migrate_pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ ' . $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } elseif ($target_engine === 'mysql') {
            $dbParams = [
                'host' => trim($_POST['db_host'] ?? '127.0.0.1'),
                'port' => (int)($_POST['db_port'] ?? 3306),
                'database' => trim($_POST['db_name'] ?? 'weavecms'),
                'username' => trim($_POST['db_user'] ?? 'root'),
                'password' => (string)($_POST['db_pass'] ?? ''),
                'prefix' => trim($_POST['db_prefix'] ?? 'weave_'),
                'create_db' => !empty($_POST['create_db'])
            ];
            $res = StorageManager::migrateToMysql($migrate_pin, $dbParams, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ ' . $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } else {
            $error_msg = 'Ungültige Ziel-Engine ausgewählt.';
        }
    }

    /**
     * Verarbeitet SEO- und Social-Sharing-Einstellungen inklusive OG-Bild.
     */
    protected static function handleSeo(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['seo']) || !is_array($data['seo'])) {
            $data['seo'] = [];
        }

        $data['seo']['meta_title'] = trim($_POST['meta_title'] ?? '');
        $data['seo']['meta_description'] = trim($_POST['meta_description'] ?? '');
        $data['seo']['meta_keywords'] = trim($_POST['meta_keywords'] ?? '');

        $robots = $_POST['robots'] ?? 'index, follow';
        $valid_robots = ['index, follow', 'noindex, follow', 'noindex, nofollow', 'index, nofollow'];
        $data['seo']['robots'] = in_array($robots, $valid_robots) ? $robots : 'index, follow';

        $data['seo']['canonical_url'] = trim($_POST['canonical_url'] ?? '');

        $uploaded_og = ImageUploader::upload('og_image_file', $data);
        if ($uploaded_og) {
            $data['seo']['og_image'] = $uploaded_og;
        } elseif (!empty($_POST['remove_og_image'])) {
            $data['seo']['og_image'] = '';
        }

        if (save_data($dataFile, $data)) {
            $success_msg = 'SEO- &amp; Social-Sharing-Einstellungen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der SEO-Einstellungen.';
        }
    }

    /**
     * Aktualisiert Administrator-Zugangsdaten (Passwort und PIN mit PIN-Bestätigung).
     */
    protected static function handleCredentials(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $current_pin = trim($_POST['current_pin'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $new_pin = trim($_POST['new_pin'] ?? '');

        if (empty($new_password) && empty($new_pin)) {
            $error_msg = 'Bitte gib ein neues Passwort oder eine neue PIN ein, die du setzen möchtest.';
        } elseif (empty($current_pin)) {
            $error_msg = 'Sicherheitsabfrage erforderlich: Bitte gib deine aktuell noch gültige PIN ein, um Passwort oder PIN zu ändern!';
        } elseif (!AuthManager::verifyAdminPin($current_pin, $data)) {
            $error_msg = 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene aktuelle PIN ist nicht korrekt! Die Zugangsdaten wurden NICHT geändert.';
        } else {
            $credentials_changed = false;
            if (!empty($new_password)) {
                $data['settings']['admin_password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                $credentials_changed = true;
            }
            if (!empty($new_pin)) {
                $data['settings']['admin_pin_hash'] = password_hash($new_pin, PASSWORD_DEFAULT);
                $credentials_changed = true;
            }

            if ($credentials_changed && save_data($dataFile, $data)) {
                $auth_hash = AuthManager::getAuthHash($data);
                setcookie('admin_auth', $auth_hash, time() + 86400 * 30, '/');
                $success_msg = '✓ Admin-Zugangsdaten (Passwort / PIN) wurden erfolgreich geändert!';
            } else {
                $error_msg = 'Fehler beim Speichern der neuen Zugangsdaten.';
            }
        }
    }

    /**
     * Verarbeitet Sicherheits- und E-Mail-Konfigurationen (Upload-Pfad, SMTP, Captcha-Icons).
     */
    protected static function handleSecurity(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $upload_dir = trim($_POST['upload_dir'] ?? 'images/uploads');
        if (empty($upload_dir)) {
            $upload_dir = 'images/uploads';
        }
        $data['settings']['upload_dir'] = $upload_dir;

        $target_abs = self::rootPath() . '/' . trim($upload_dir, '/\\');
        if (!is_dir($target_abs)) {
            @mkdir($target_abs, 0755, true);
        }

        $smtp_enabled = !empty($_POST['smtp_enabled']);
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = (int)($_POST['smtp_port'] ?? 587);
        $smtp_secure = trim($_POST['smtp_secure'] ?? 'tls');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass_new = $_POST['smtp_pass'] ?? '';
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'Atelier Musterfrau');

        if (!isset($data['settings']['smtp']) || !is_array($data['settings']['smtp'])) {
            $data['settings']['smtp'] = [];
        }

        $data['settings']['smtp']['enabled'] = $smtp_enabled;
        $data['settings']['smtp']['host'] = $smtp_host;
        $data['settings']['smtp']['port'] = ($smtp_port > 0) ? $smtp_port : 587;
        $data['settings']['smtp']['secure'] = in_array($smtp_secure, ['tls', 'ssl', 'none']) ? $smtp_secure : 'tls';
        $data['settings']['smtp']['user'] = $smtp_user;
        if (!empty($smtp_pass_new)) {
            $data['settings']['smtp']['pass'] = $smtp_pass_new;
        }
        $data['settings']['smtp']['from_email'] = $smtp_from_email;
        $data['settings']['smtp']['from_name'] = $smtp_from_name;

        // Modulare Captcha-Auswahl-Engine (Phase 16)
        $captcha_mode = trim($_POST['captcha_mode'] ?? CaptchaManager::MODE_ICONS);
        if (in_array($captcha_mode, [CaptchaManager::MODE_ICONS, CaptchaManager::MODE_MATH, CaptchaManager::MODE_CANVAS, CaptchaManager::MODE_HONEYPOT], true)) {
            $data['settings']['captcha_mode'] = $captcha_mode;
        }

        $data['settings']['impressum_email_captcha'] = !empty($_POST['impressum_email_captcha']);

        $submitted_captcha = $_POST['captcha_icons'] ?? [];
        $captcha_error = false;
        if (is_array($submitted_captcha) && !empty($submitted_captcha)) {
            $all_available_icons = CaptchaManager::getAllIcons();
            $valid_captcha_icons = [];
            foreach ($submitted_captcha as $c_id) {
                if (isset($all_available_icons[$c_id]) && !in_array($c_id, $valid_captcha_icons, true)) {
                    $valid_captcha_icons[] = $c_id;
                }
            }
            if (count($valid_captcha_icons) === 4) {
                $data['settings']['captcha_icons'] = $valid_captcha_icons;
            } elseif ($captcha_mode === CaptchaManager::MODE_ICONS) {
                $captcha_error = true;
                $error_msg = 'Bitte wähle genau 4 Symbole für den Spamschutz im Kontaktformular aus (aktuell ausgewählt: ' . count($valid_captcha_icons) . ').';
            }
        } elseif ($captcha_mode === CaptchaManager::MODE_ICONS && empty($data['settings']['captcha_icons'])) {
            $data['settings']['captcha_icons'] = ['basket', 'branch', 'paw', 'sun'];
        }

        if (!$captcha_error) {
            if (save_data($dataFile, $data)) {
                $success_msg = '✓ Spamschutz-, SMTP- und Upload-Einstellungen erfolgreich gespeichert.';
            } else {
                $error_msg = 'Fehler beim Speichern der Einstellungen.';
            }
        }
    }

    /**
     * Sendet eine Test-E-Mail über den konfigurierten SMTP-Server.
     */
    protected static function handleSmtpTest(array $data, string &$success_msg, string &$error_msg): void {
        $test_to = trim($_POST['test_to_email'] ?? ($data['texts']['contact_email'] ?? ''));
        if (empty($test_to) || !filter_var($test_to, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Bitte gib eine gültige Ziel-E-Mail-Adresse für den SMTP-Test an.';
            return;
        }

        $cur_smtp = $data['settings']['smtp'] ?? [];
        if (empty($cur_smtp['enabled']) || empty($cur_smtp['host'])) {
            $error_msg = 'SMTP ist nicht aktiviert oder es ist kein SMTP-Host eingetragen. Bitte aktiviere SMTP und speichere die Einstellungen zuerst.';
            return;
        }

        $test_subject = 'SMTP-Verbindungstest | WeaveCMS';
        $test_html = '<div style="font-family:sans-serif; padding:20px; color:#2e6248; background:#fbf9f6; border:1px solid #e7dfd5; border-radius:12px;">'
                   . '<h2>🌿 SMTP-Verbindungstest erfolgreich!</h2>'
                   . '<p>Die E-Mail-Konfiguration für das Kontaktformular auf deiner Website funktioniert einwandfrei.</p>'
                   . '<p><b>Zeitpunkt:</b> ' . date('d.m.Y H:i:s') . '</p>'
                   . '<p><b>Host:</b> ' . htmlspecialchars($cur_smtp['host']) . ':' . (int)($cur_smtp['port'] ?? 587) . ' (' . htmlspecialchars($cur_smtp['secure'] ?? 'tls') . ')</p>'
                   . '</div>';
        $test_text = "SMTP-Test erfolgreich!\nZeitpunkt: " . date('d.m.Y H:i:s') . "\nServer: " . ($cur_smtp['host'] ?? '');

        $test_res = SimpleMailer::sendViaSmtp(
            $test_to,
            $test_subject,
            $test_html,
            $test_text,
            $test_to,
            'Admin Test',
            $cur_smtp
        );

        if ($test_res['success']) {
            $success_msg = '✓ SMTP-Verbindungstest erfolgreich! Eine Test-E-Mail wurde an ' . htmlspecialchars($test_to) . ' gesendet.';
        } else {
            $error_msg = 'SMTP-Verbindungstest fehlgeschlagen: ' . htmlspecialchars($test_res['error']);
        }
    }

    /**
     * Verarbeitet Backup-Erstellung, -Wiederherstellung, -Upload und -Löschung.
     */
    protected static function handleBackupAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'create_backup') {
            $backup_type = $_POST['backup_type'] ?? 'full';
            $res = BackupManager::createBackup($backup_type, $data);
            if ($res['success']) {
                $success_msg = '✓ Sicherung "' . htmlspecialchars($res['filename']) . '" wurde erfolgreich im geschützten Ordner backups/ erstellt!';
            } else {
                $error_msg = 'Fehler beim Erstellen der Sicherung: ' . htmlspecialchars($res['error']);
            }
        } elseif ($action === 'restore_backup') {
            $file = $_POST['backup_file'] ?? '';
            $pin = trim($_POST['restore_pin'] ?? '');
            $res = BackupManager::restoreExistingBackup($file, $pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ Sicherung "' . htmlspecialchars($file) . '" wurde erfolgreich wiederhergestellt! (Ein Notfall-Snapshot wurde vorab angelegt).';
            } else {
                $error_msg = htmlspecialchars($res['error']);
            }
        } elseif ($action === 'upload_restore') {
            $pin = trim($_POST['restore_pin'] ?? '');
            $res = BackupManager::restoreUploadedBackup($_FILES['backup_upload_file'] ?? null, $pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ Die hochgeladene Sicherung wurde erfolgreich wiederhergestellt! (Ein Notfall-Snapshot wurde vorab angelegt).';
            } else {
                $error_msg = htmlspecialchars($res['error']);
            }
        } elseif ($action === 'delete_backup') {
            $file = $_POST['backup_file'] ?? '';
            if (BackupManager::deleteBackup($file)) {
                $success_msg = 'Sicherungsdatei "' . htmlspecialchars($file) . '" wurde erfolgreich gelöscht.';
            } else {
                $error_msg = 'Fehler beim Löschen der Sicherungsdatei.';
            }
        }
    }

    /**
     * Verarbeitet Hinzufügen und Entfernen von Galerie-Kategorien.
     */
    protected static function handleCategoryAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['categories']) || !is_array($data['categories'])) {
            $data['categories'] = ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
        }

        if ($action === 'add_category') {
            $new_cat = trim($_POST['category_name'] ?? '');
            if (empty($new_cat)) {
                $error_msg = 'Bitte gib einen Namen für die Kategorie ein.';
                return;
            }
            $exists = false;
            foreach ($data['categories'] as $c) {
                if (mb_strtolower($c, 'UTF-8') === mb_strtolower($new_cat, 'UTF-8')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $data['categories'][] = $new_cat;
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Kategorie "' . htmlspecialchars($new_cat) . '" erfolgreich angelegt!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Kategorie.';
                }
            } else {
                $error_msg = 'Diese Kategorie existiert bereits.';
            }
        } elseif ($action === 'delete_category') {
            $del_cat = trim($_POST['category_name'] ?? '');
            if (!empty($del_cat)) {
                $pos = array_search($del_cat, $data['categories']);
                if ($pos !== false) {
                    array_splice($data['categories'], $pos, 1);
                    if (save_data($dataFile, $data)) {
                        $success_msg = 'Kategorie "' . htmlspecialchars($del_cat) . '" gelöscht. Zugeordnete Galerie-Elemente bleiben erhalten, werden aber im Frontend nicht mehr angezeigt.';
                    } else {
                        $error_msg = 'Fehler beim Löschen der Kategorie.';
                    }
                } else {
                    $error_msg = 'Kategorie nicht gefunden.';
                }
            }
        }
    }

    /**
     * Aktualisiert die Textinhalte aller Frontend-Sektionen (Hauptsprache oder Übersetzungen).
     */
    protected static function handleTexts(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $primary_lang = LanguageManager::getPrimaryLanguage($data);
        $target_lang = trim($_POST['target_lang'] ?? $primary_lang);
        $supported = LanguageManager::getSupportedLanguages();
        if (!isset($supported[$target_lang])) {
            $target_lang = $primary_lang;
        }

        $fields = [
            'hero_title' => $_POST['hero_title'] ?? '',
            'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
            'about_title' => $_POST['about_title'] ?? '',
            'about_text' => $_POST['about_text'] ?? '',
            'gallery_title' => $_POST['gallery_title'] ?? 'Galerie & Handarbeit',
            'gallery_subtitle' => $_POST['gallery_subtitle'] ?? '',
            'courses_title' => $_POST['courses_title'] ?? 'Kurse & Workshops',
            'courses_subtitle' => $_POST['courses_subtitle'] ?? '',
            'past_courses_title' => $_POST['past_courses_title'] ?? 'Impressionen vergangener Kurse',
            'past_courses_subtitle' => $_POST['past_courses_subtitle'] ?? '',
            'blog_title' => $_POST['blog_title'] ?? 'Aktuelles & Geschichten',
            'blog_subtitle' => $_POST['blog_subtitle'] ?? '',
            'contact_intro_text' => $_POST['contact_intro_text'] ?? ''
        ];

        if ($target_lang === $primary_lang) {
            foreach ($fields as $k => $v) {
                $data['texts'][$k] = $v;
            }
            $msg = 'Inhaltstexte der Hauptsprache (' . $supported[$primary_lang]['name'] . ') erfolgreich gespeichert!';
        } else {
            if (!isset($data['texts_i18n']) || !is_array($data['texts_i18n'])) {
                $data['texts_i18n'] = [];
            }
            if (!isset($data['texts_i18n'][$target_lang]) || !is_array($data['texts_i18n'][$target_lang])) {
                $data['texts_i18n'][$target_lang] = [];
            }
            foreach ($fields as $k => $v) {
                $data['texts_i18n'][$target_lang][$k] = $v;
            }
            $msg = 'Übersetzung für ' . $supported[$target_lang]['name'] . ' erfolgreich gespeichert!';
        }

        if (save_data($dataFile, $data)) {
            $success_msg = $msg;
        } else {
            $error_msg = 'Fehler beim Speichern der Texte.';
        }
    }

    /**
     * Aktualisiert Impressum, Kontaktdaten und DSGVO-Datenschutzerklärung.
     */
    protected static function handleImpressum(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $data['texts']['contact_name'] = $_POST['contact_name'] ?? '';
        $data['texts']['contact_street'] = $_POST['contact_street'] ?? '';
        $data['texts']['contact_city'] = $_POST['contact_city'] ?? '';
        $data['texts']['contact_phone'] = $_POST['contact_phone'] ?? '';
        $data['texts']['contact_email'] = $_POST['contact_email'] ?? '';
        $data['texts']['contact_instagram'] = $_POST['contact_instagram'] ?? '';
        $data['texts']['impressum_supervisory'] = $_POST['impressum_supervisory'] ?? '';
        $data['texts']['impressum_vat_id'] = $_POST['impressum_vat_id'] ?? '';
        $data['texts']['impressum_disclaimer'] = $_POST['impressum_disclaimer'] ?? '';
        $data['texts']['contact_mail_greeting'] = trim($_POST['contact_mail_greeting'] ?? 'Hallo Maxima,');
        $data['texts']['course_request_template'] = trim($_POST['course_request_template'] ?? '');

        $privacy_text = trim($_POST['privacy_policy_text'] ?? '');
        $data['settings']['privacy_policy_text'] = $privacy_text;
        $data['texts']['privacy_policy_text'] = $privacy_text;

        if (save_data($dataFile, $data)) {
            $success_msg = 'Impressum, Kontaktdaten &amp; Datenschutzerklärung erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern des Impressums.';
        }
    }

    /**
     * Verarbeitet Galerie-Operationen (Hinzufügen, Löschen, Sortieren).
     */
    protected static function handleGalleryAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_gallery') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $image_path = ImageUploader::upload('gallery_image', $data);

            if (empty($title)) {
                $error_msg = 'Bitte gib einen Titel für das Galerie-Element an.';
            } elseif (empty($image_path)) {
                $error_msg = 'Fehler beim Upload des Bildes (nur JPG, PNG, WebP und GIF erlaubt).';
            } else {
                $next_id = 1;
                $next_sort = 1;
                foreach ($data['gallery'] as $item) {
                    if ($item['id'] >= $next_id) $next_id = $item['id'] + 1;
                    if (($item['sort_order'] ?? 0) >= $next_sort) $next_sort = ($item['sort_order'] ?? 0) + 1;
                }

                $data['gallery'][] = [
                    'id' => $next_id,
                    'title' => $title,
                    'description' => $description,
                    'image' => $image_path,
                    'category' => $category ?: 'Sonstiges',
                    'sort_order' => $next_sort
                ];

                if (save_data($dataFile, $data)) {
                    $success_msg = 'Galerie-Element erfolgreich hinzugefügt!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Galerie-Daten.';
                }
            }
        } elseif ($action === 'delete_gallery') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['gallery'] as $idx => $item) {
                if ($item['id'] === $id) {
                    $upload_cfg = ImageUploader::getConfig($data);
                    $rel_prefix = $upload_cfg['rel'] . '/';
                    if (!empty($item['image']) && strpos($item['image'], $rel_prefix) === 0) {
                        $filepath = self::rootPath() . '/' . ltrim($item['image'], '/\\');
                        if (file_exists($filepath)) {
                            @unlink($filepath);
                        }
                    }
                    unset($data['gallery'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['gallery'] = array_values($data['gallery']);
                save_data($dataFile, $data);
                $success_msg = 'Galerie-Element erfolgreich gelöscht!';
            } else {
                $error_msg = 'Galerie-Element nicht gefunden.';
            }
        } elseif ($action === 'reorder_gallery') {
            $orders = $_POST['order'] ?? [];
            foreach ($data['gallery'] as &$item) {
                $iid = $item['id'];
                if (isset($orders[$iid])) {
                    $item['sort_order'] = (int)$orders[$iid];
                }
            }

            usort($data['gallery'], function($a, $b) {
                return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            });

            if (save_data($dataFile, $data)) {
                $success_msg = 'Galerie-Reihenfolge erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern der Reihenfolge.';
            }
        }
    }

    /**
     * Verarbeitet Flechtkurse (Hinzufügen, Bearbeiten, Löschen).
     */
    protected static function handleCourseAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_course') {
            $title = $_POST['title'] ?? '';
            $date_string = $_POST['date_string'] ?? '';
            $time = $_POST['time'] ?? '10:00 - 16:00 Uhr';
            $location = $_POST['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? '85 €';
            $max_participants = (int)($_POST['max_participants'] ?? 10);
            $current_participants = (int)($_POST['current_participants'] ?? 0);
            $available = isset($_POST['available']);

            if (empty($title) || empty($date_string)) {
                $error_msg = 'Bitte gib mindestens einen Kurstitel und ein Datum an.';
            } else {
                $next_id = 1;
                foreach ($data['courses'] as $c) {
                    if ($c['id'] >= $next_id) $next_id = $c['id'] + 1;
                }

                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                $invite_token = 'kurs-' . $next_id . '-' . trim($slug, '-');

                $data['courses'][] = [
                    'id' => $next_id,
                    'title' => $title,
                    'date_string' => $date_string,
                    'time' => $time,
                    'location' => $location,
                    'description' => $description,
                    'price' => $price,
                    'max_participants' => $max_participants,
                    'current_participants' => $current_participants,
                    'available' => $available,
                    'invite_token' => $invite_token
                ];

                if (save_data($dataFile, $data)) {
                    $success_msg = 'Flechtkurs erfolgreich angelegt und Einladungslink generiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern des Kurses.';
                }
            }
        } elseif ($action === 'edit_course') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['courses'] as &$c) {
                if ($c['id'] === $id) {
                    $c['title'] = $_POST['title'] ?? '';
                    $c['date_string'] = $_POST['date_string'] ?? '';
                    $c['time'] = $_POST['time'] ?? '10:00 - 16:00 Uhr';
                    $c['location'] = $_POST['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow';
                    $c['description'] = $_POST['description'] ?? '';
                    $c['price'] = $_POST['price'] ?? '';
                    $c['max_participants'] = (int)($_POST['max_participants'] ?? 10);
                    $c['current_participants'] = (int)($_POST['current_participants'] ?? 0);
                    $c['available'] = isset($_POST['available']);
                    if (empty($c['invite_token'])) {
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $c['title']));
                        $c['invite_token'] = 'kurs-' . $id . '-' . trim($slug, '-');
                    }
                    $found = true;
                    break;
                }
            }

            if ($found && save_data($dataFile, $data)) {
                $success_msg = 'Flechtkurs erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Aktualisieren des Kurses.';
            }
        } elseif ($action === 'delete_course') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['courses'] as $idx => $c) {
                if ($c['id'] === $id) {
                    unset($data['courses'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['courses'] = array_values($data['courses']);
                save_data($dataFile, $data);
                $success_msg = 'Flechtkurs erfolgreich gelöscht!';
            } else {
                $error_msg = 'Flechtkurs nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Impressionen vergangener Workshops (Foto-Upload und Löschung).
     */
    protected static function handlePastCourseAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_past_course_image') {
            $image_path = ImageUploader::upload('past_image', $data);
            if (!empty($image_path)) {
                $data['past_courses'][] = $image_path;
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Bild vergangener Kurse erfolgreich hochgeladen!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Kursbilder.';
                }
            } else {
                $error_msg = 'Fehler beim Bildupload.';
            }
        } elseif ($action === 'delete_past_course_image') {
            $img_path = $_POST['image_path'] ?? '';
            $found = false;
            foreach ($data['past_courses'] as $idx => $path) {
                if ($path === $img_path) {
                    $upload_cfg = ImageUploader::getConfig($data);
                    $rel_prefix = $upload_cfg['rel'] . '/';
                    if (!empty($path) && strpos($path, $rel_prefix) === 0) {
                        $filepath = self::rootPath() . '/' . ltrim($path, '/\\');
                        if (file_exists($filepath)) {
                            @unlink($filepath);
                        }
                    }
                    unset($data['past_courses'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['past_courses'] = array_values($data['past_courses']);
                save_data($dataFile, $data);
                $success_msg = 'Kursbild erfolgreich gelöscht!';
            } else {
                $error_msg = 'Bild nicht in der Liste gefunden.';
            }
        }
    }

    /**
     * Erstellt einen SEO-freundlichen URL-Slug aus einem Titel.
     */
    public static function slugify(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        $text = trim($text, '-');
        return !empty($text) ? $text : 'artikel';
    }

    /**
     * Prüft, ob ein URL-Slug bereits in der Artikelliste existiert.
     */
    protected static function slugExists(array $posts, string $slug): bool {
        foreach ($posts as $p) {
            if (($p['slug'] ?? '') === $slug) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verarbeitet Blog-Aktionen (Artikel anlegen, bearbeiten, löschen, Status umschalten).
     */
    protected static function handleBlogAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['blog']) || !is_array($data['blog'])) {
            $data['blog'] = [];
        }

        if ($action === 'add_blog_post') {
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Titel für den Blog-Artikel ein.';
                return;
            }

            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) {
                $slug = self::slugify($title);
            } else {
                $slug = self::slugify($slug);
            }

            // Eindeutigen Slug sicherstellen
            $base_slug = $slug;
            $counter = 1;
            while (self::slugExists($data['blog'], $slug)) {
                $slug = $base_slug . '-' . $counter++;
            }

            $image_path = '';
            if (!empty($_FILES['image']['name'])) {
                $upload_cfg = ImageUploader::getConfig($data);
                $uploaded = ImageUploader::uploadSingle($_FILES['image'], $upload_cfg['abs'], 'blog');
                if ($uploaded) {
                    $image_path = $upload_cfg['rel'] . '/' . $uploaded;
                } else {
                    $error_msg = 'Das Beitragsbild konnte nicht hochgeladen werden.';
                    return;
                }
            } elseif (!empty($_POST['image_url'])) {
                $image_path = trim($_POST['image_url']);
            }

            $date = trim($_POST['date'] ?? date('Y-m-d'));
            $author = trim($_POST['author'] ?? ($data['texts']['contact_name'] ?? 'Redaktion'));
            $teaser = trim($_POST['teaser'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
            $tags_raw = trim($_POST['tags'] ?? '');
            $tags = !empty($tags_raw) ? array_map('trim', explode(',', $tags_raw)) : [];

            $new_post = [
                'id' => time() . '_' . mt_rand(100, 999),
                'title' => $title,
                'slug' => $slug,
                'date' => $date,
                'author' => $author,
                'image' => $image_path,
                'teaser' => $teaser,
                'content' => $content,
                'status' => $status,
                'tags' => $tags,
                'created_at' => date('Y-m-d H:i:s')
            ];

            array_unshift($data['blog'], $new_post);

            if (save_data($dataFile, $data)) {
                $success_msg = 'Blog-Artikel „' . htmlspecialchars($title) . '“ erfolgreich erstellt!';
            } else {
                $error_msg = 'Fehler beim Speichern des Artikels.';
            }

        } elseif ($action === 'edit_blog_post') {
            $post_id = $_POST['id'] ?? '';
            $found_idx = -1;
            foreach ($data['blog'] as $idx => $p) {
                if ((string)($p['id'] ?? '') === (string)$post_id) {
                    $found_idx = $idx;
                    break;
                }
            }

            if ($found_idx === -1) {
                $error_msg = 'Artikel nicht gefunden.';
                return;
            }

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Titel ein.';
                return;
            }

            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) {
                $slug = self::slugify($title);
            } else {
                $slug = self::slugify($slug);
            }

            // Prüfen, ob Slug bei einem anderen Artikel existiert
            foreach ($data['blog'] as $idx => $p) {
                if ($idx !== $found_idx && ($p['slug'] ?? '') === $slug) {
                    $slug .= '-' . time();
                    break;
                }
            }

            $image_path = $data['blog'][$found_idx]['image'] ?? '';
            if (!empty($_FILES['image']['name'])) {
                $upload_cfg = ImageUploader::getConfig($data);
                $uploaded = ImageUploader::uploadSingle($_FILES['image'], $upload_cfg['abs'], 'blog');
                if ($uploaded) {
                    $image_path = $upload_cfg['rel'] . '/' . $uploaded;
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                $image_path = '';
            }

            $date = trim($_POST['date'] ?? $data['blog'][$found_idx]['date']);
            $author = trim($_POST['author'] ?? $data['blog'][$found_idx]['author']);
            $teaser = trim($_POST['teaser'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
            $tags_raw = trim($_POST['tags'] ?? '');
            $tags = !empty($tags_raw) ? array_map('trim', explode(',', $tags_raw)) : [];

            $data['blog'][$found_idx]['title'] = $title;
            $data['blog'][$found_idx]['slug'] = $slug;
            $data['blog'][$found_idx]['date'] = $date;
            $data['blog'][$found_idx]['author'] = $author;
            $data['blog'][$found_idx]['image'] = $image_path;
            $data['blog'][$found_idx]['teaser'] = $teaser;
            $data['blog'][$found_idx]['content'] = $content;
            $data['blog'][$found_idx]['status'] = $status;
            $data['blog'][$found_idx]['tags'] = $tags;
            $data['blog'][$found_idx]['updated_at'] = date('Y-m-d H:i:s');

            if (save_data($dataFile, $data)) {
                $success_msg = 'Blog-Artikel „' . htmlspecialchars($title) . '“ erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern der Änderungen.';
            }

        } elseif ($action === 'delete_blog_post') {
            $post_id = $_POST['id'] ?? '';
            $original_count = count($data['blog']);
            $data['blog'] = array_values(array_filter($data['blog'], function($p) use ($post_id) {
                return (string)($p['id'] ?? '') !== (string)$post_id;
            }));

            if (count($data['blog']) < $original_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Blog-Artikel gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'Artikel nicht gefunden.';
            }

        } elseif ($action === 'toggle_blog_status') {
            $post_id = $_POST['id'] ?? '';
            $found = false;
            foreach ($data['blog'] as &$p) {
                if ((string)($p['id'] ?? '') === (string)$post_id) {
                    $p['status'] = ($p['status'] === 'published') ? 'draft' : 'published';
                    $found = true;
                    break;
                }
            }
            if ($found && save_data($dataFile, $data)) {
                $success_msg = 'Veröffentlichungsstatus geändert.';
            } else {
                $error_msg = 'Artikel nicht gefunden oder Fehler beim Speichern.';
            }
        }
    }

    /**
     * Verarbeitet Aktionen für Freie Einzelseiten (Custom Subpages).
     */
    public static function handlePageAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['pages']) || !is_array($data['pages'])) {
            $data['pages'] = [];
        }

        if ($action === 'add_page') {
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Seitentitel an.';
                return;
            }
            $slug = trim($_POST['slug'] ?? '');
            $slug = empty($slug) ? self::slugify($title) : self::slugify($slug);

            // Prüfen auf Duplikate
            foreach ($data['pages'] as $pg) {
                if (($pg['slug'] ?? '') === $slug) {
                    $slug .= '-' . time();
                    break;
                }
            }

            $active_langs = LanguageManager::getActiveLanguages($data);
            $primary_lang = LanguageManager::getPrimaryLanguage($data);
            $translations = [];
            foreach ($active_langs as $lang) {
                if ($lang === $primary_lang) continue;
                $t_title = trim($_POST['trans_' . $lang . '_title'] ?? '');
                $t_nav = trim($_POST['trans_' . $lang . '_nav_label'] ?? '');
                $t_meta = trim($_POST['trans_' . $lang . '_meta_description'] ?? '');
                $t_content = trim($_POST['trans_' . $lang . '_content'] ?? '');
                if ($t_title !== '' || $t_nav !== '' || $t_meta !== '' || $t_content !== '') {
                    $translations[$lang] = [
                        'title' => $t_title,
                        'nav_label' => $t_nav,
                        'meta_description' => $t_meta,
                        'content' => $t_content
                    ];
                }
            }

            $page_type = in_array($_POST['page_type'] ?? '', ['content', 'modular'], true) ? $_POST['page_type'] : 'content';
            $page_sections = [];
            if ($page_type === 'modular' && isset($_POST['page_sections']) && is_array($_POST['page_sections'])) {
                $page_sections = array_values(array_filter($_POST['page_sections'], 'is_string'));
            }

            $newPage = [
                'id' => 'page_' . uniqid(),
                'title' => $title,
                'slug' => $slug,
                'page_type' => $page_type,
                'page_sections' => $page_sections,
                'nav_label' => trim($_POST['nav_label'] ?? ''),
                'icon' => trim($_POST['icon'] ?? ''),
                'status' => in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published',
                'in_nav' => !empty($_POST['in_nav']),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'translations' => $translations,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $data['pages'][] = $newPage;
            if (save_data($dataFile, $data)) {
                $success_msg = 'Einzelseite „' . htmlspecialchars($title) . '“ erfolgreich erstellt!';
            } else {
                $error_msg = 'Fehler beim Speichern der Einzelseite.';
            }

        } elseif ($action === 'edit_page') {
            $page_id = $_POST['id'] ?? '';
            $found_idx = -1;
            foreach ($data['pages'] as $idx => $pg) {
                if ((string)($pg['id'] ?? '') === (string)$page_id) {
                    $found_idx = $idx;
                    break;
                }
            }
            if ($found_idx === -1) {
                $error_msg = 'Einzelseite nicht gefunden.';
                return;
            }

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Seitentitel an.';
                return;
            }

            $slug = trim($_POST['slug'] ?? '');
            $slug = empty($slug) ? self::slugify($title) : self::slugify($slug);

            foreach ($data['pages'] as $idx => $pg) {
                if ($idx !== $found_idx && ($pg['slug'] ?? '') === $slug) {
                    $slug .= '-' . time();
                    break;
                }
            }

            $active_langs = LanguageManager::getActiveLanguages($data);
            $primary_lang = LanguageManager::getPrimaryLanguage($data);
            $translations = $data['pages'][$found_idx]['translations'] ?? [];
            foreach ($active_langs as $lang) {
                if ($lang === $primary_lang) continue;
                $t_title = trim($_POST['trans_' . $lang . '_title'] ?? '');
                $t_nav = trim($_POST['trans_' . $lang . '_nav_label'] ?? '');
                $t_meta = trim($_POST['trans_' . $lang . '_meta_description'] ?? '');
                $t_content = trim($_POST['trans_' . $lang . '_content'] ?? '');
                if ($t_title !== '' || $t_nav !== '' || $t_meta !== '' || $t_content !== '') {
                    $translations[$lang] = [
                        'title' => $t_title,
                        'nav_label' => $t_nav,
                        'meta_description' => $t_meta,
                        'content' => $t_content
                    ];
                } else {
                    unset($translations[$lang]);
                }
            }

            $page_type = in_array($_POST['page_type'] ?? '', ['content', 'modular'], true) ? $_POST['page_type'] : 'content';
            $page_sections = [];
            if ($page_type === 'modular' && isset($_POST['page_sections']) && is_array($_POST['page_sections'])) {
                $page_sections = array_values(array_filter($_POST['page_sections'], 'is_string'));
            }

            $data['pages'][$found_idx]['title'] = $title;
            $data['pages'][$found_idx]['slug'] = $slug;
            $data['pages'][$found_idx]['page_type'] = $page_type;
            $data['pages'][$found_idx]['page_sections'] = $page_sections;
            $data['pages'][$found_idx]['nav_label'] = trim($_POST['nav_label'] ?? '');
            $data['pages'][$found_idx]['icon'] = trim($_POST['icon'] ?? '');
            $data['pages'][$found_idx]['status'] = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
            $data['pages'][$found_idx]['in_nav'] = !empty($_POST['in_nav']);
            $data['pages'][$found_idx]['meta_description'] = trim($_POST['meta_description'] ?? '');
            $data['pages'][$found_idx]['content'] = trim($_POST['content'] ?? '');
            $data['pages'][$found_idx]['translations'] = $translations;
            $data['pages'][$found_idx]['updated_at'] = date('Y-m-d H:i:s');

            if (save_data($dataFile, $data)) {
                $success_msg = 'Einzelseite „' . htmlspecialchars($title) . '“ erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern der Einzelseite.';
            }

        } elseif ($action === 'delete_page') {
            $page_id = $_POST['id'] ?? '';
            $orig_count = count($data['pages']);
            $data['pages'] = array_values(array_filter($data['pages'], function($pg) use ($page_id) {
                return (string)($pg['id'] ?? '') !== (string)$page_id;
            }));

            if (count($data['pages']) < $orig_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Einzelseite gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'Seite nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Aktionen für Häufige Fragen & Antworten (FAQ Akkordeon).
     */
    public static function handleFaqAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['faqs']) || !is_array($data['faqs'])) {
            $data['faqs'] = [];
        }

        if ($action === 'add_faq') {
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            if (empty($question) || empty($answer)) {
                $error_msg = 'Bitte gib sowohl eine Frage als auch eine Antwort an.';
                return;
            }

            $newFaq = [
                'id' => 'faq_' . uniqid(),
                'question' => $question,
                'answer' => $answer,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data['faqs'][] = $newFaq;
            if (save_data($dataFile, $data)) {
                $success_msg = 'FAQ-Frage erfolgreich hinzugefügt!';
            } else {
                $error_msg = 'Fehler beim Speichern der FAQ-Frage.';
            }

        } elseif ($action === 'edit_faq') {
            $faq_id = $_POST['id'] ?? '';
            $found = false;
            foreach ($data['faqs'] as &$f) {
                if ((string)($f['id'] ?? '') === (string)$faq_id) {
                    $f['question'] = trim($_POST['question'] ?? '');
                    $f['answer'] = trim($_POST['answer'] ?? '');
                    $found = true;
                    break;
                }
            }
            if ($found && save_data($dataFile, $data)) {
                $success_msg = 'FAQ-Frage erfolgreich aktualisiert!';
            } else {
                $error_msg = 'FAQ-Frage nicht gefunden oder Fehler beim Speichern.';
            }

        } elseif ($action === 'delete_faq') {
            $faq_id = $_POST['id'] ?? '';
            $orig_count = count($data['faqs']);
            $data['faqs'] = array_values(array_filter($data['faqs'], function($f) use ($faq_id) {
                return (string)($f['id'] ?? '') !== (string)$faq_id;
            }));

            if (count($data['faqs']) < $orig_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'FAQ-Frage gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'FAQ-Frage nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Aktionen für Team & Mitarbeiter.
     */
    public static function handleTeamAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['team']) || !is_array($data['team'])) {
            $data['team'] = [];
        }

        if ($action === 'add_team_member') {
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            if (empty($name) || empty($role)) {
                $error_msg = 'Bitte gib Name und Rolle des Teammitglieds an.';
                return;
            }

            $image_path = '';
            if (!empty($_FILES['image']['name'])) {
                $uploaded = ImageUploader::upload('image');
                if ($uploaded) {
                    $image_path = ltrim($uploaded, '/');
                }
            }

            $newMember = [
                'id' => 'team_' . uniqid(),
                'name' => $name,
                'role' => $role,
                'email' => trim($_POST['email'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'image' => $image_path,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data['team'][] = $newMember;
            if (save_data($dataFile, $data)) {
                $success_msg = 'Teammitglied „' . htmlspecialchars($name) . '“ erfolgreich hinzugefügt!';
            } else {
                $error_msg = 'Fehler beim Speichern des Teammitglieds.';
            }

        } elseif ($action === 'edit_team_member') {
            $member_id = $_POST['id'] ?? '';
            $found_idx = -1;
            foreach ($data['team'] as $idx => $m) {
                if ((string)($m['id'] ?? '') === (string)$member_id) {
                    $found_idx = $idx;
                    break;
                }
            }
            if ($found_idx === -1) {
                $error_msg = 'Teammitglied nicht gefunden.';
                return;
            }

            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            if (empty($name) || empty($role)) {
                $error_msg = 'Bitte gib Name und Rolle an.';
                return;
            }

            $image_path = $data['team'][$found_idx]['image'] ?? '';
            if (!empty($_FILES['image']['name'])) {
                $uploaded = ImageUploader::upload('image');
                if ($uploaded) {
                    $image_path = ltrim($uploaded, '/');
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                $image_path = '';
            }

            $data['team'][$found_idx]['name'] = $name;
            $data['team'][$found_idx]['role'] = $role;
            $data['team'][$found_idx]['email'] = trim($_POST['email'] ?? '');
            $data['team'][$found_idx]['bio'] = trim($_POST['bio'] ?? '');
            $data['team'][$found_idx]['image'] = $image_path;

            if (save_data($dataFile, $data)) {
                $success_msg = 'Teammitglied „' . htmlspecialchars($name) . '“ erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern.';
            }

        } elseif ($action === 'delete_team_member') {
            $member_id = $_POST['id'] ?? '';
            $orig_count = count($data['team']);
            $data['team'] = array_values(array_filter($data['team'], function($m) use ($member_id) {
                return (string)($m['id'] ?? '') !== (string)$member_id;
            }));

            if (count($data['team']) < $orig_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Teammitglied gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'Mitglied nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Aktionen für Downloads & Software (Phase 16).
     */
    public static function handleDownloadAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['downloads']) || !is_array($data['downloads'])) {
            $data['downloads'] = [];
        }

        // Hilfsfunktion zum Parsen von Feature-Bulletpoints
        $parseFeatures = function($rawInput): array {
            if (is_array($rawInput)) {
                return array_values(array_filter(array_map('trim', $rawInput)));
            }
            $lines = explode("\n", str_replace("\r", '', (string)$rawInput));
            return array_values(array_filter(array_map('trim', $lines)));
        };

        // Hilfsfunktion zum Parsen von Screenshot-Pfaden
        $parseScreenshots = function($rawInput): array {
            if (is_array($rawInput)) {
                return array_values(array_filter(array_map('trim', $rawInput)));
            }
            $items = preg_split('/[\r\n,]+/', (string)$rawInput);
            return array_values(array_filter(array_map('trim', $items)));
        };

        if ($action === 'add_download') {
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Dokumenttitel oder Software-Namen an.';
                return;
            }

            $fileSource = ($_POST['file_source'] ?? 'upload') === 'external' ? 'external' : 'upload';
            $externalUrl = trim($_POST['external_url'] ?? '');
            $filePath = '';
            $fileSize = trim($_POST['file_size'] ?? '');

            if ($fileSource === 'external') {
                if (empty($externalUrl) || !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                    $error_msg = 'Bitte gib eine gültige externe Download-URL an (z. B. GitHub Release oder Cloud-Speicher).';
                    return;
                }
            } else {
                $docInfo = ImageUploader::uploadDocument('download_file');
                if (!$docInfo) {
                    $error_msg = 'Bitte wähle eine gültige Datei aus (Erlaubt: PDF, ZIP, DOCX, TXT etc.).';
                    return;
                }
                $filePath = $docInfo['path'];
                if (empty($fileSize)) {
                    $fileSize = $docInfo['size'];
                }
            }

            $newDl = [
                'id' => 'dl_' . uniqid(),
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? 'Software'),
                'file_source' => $fileSource,
                'file_path' => $filePath,
                'external_url' => $externalUrl,
                'file_size' => $fileSize,
                'version' => trim($_POST['version'] ?? ''),
                'platform' => trim($_POST['platform'] ?? ''),
                'license' => trim($_POST['license'] ?? ''),
                'features' => $parseFeatures($_POST['features'] ?? ''),
                'screenshots' => $parseScreenshots($_POST['screenshots'] ?? ''),
                'changelog' => trim($_POST['changelog'] ?? ''),
                'show_counter' => !empty($_POST['show_counter']),
                'download_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data['downloads'][] = $newDl;
            if (save_data($dataFile, $data)) {
                $success_msg = 'Download-Eintrag „' . htmlspecialchars($title) . '“ erfolgreich angelegt!';
            } else {
                $error_msg = 'Fehler beim Speichern.';
            }

        } elseif ($action === 'edit_download') {
            $dl_id = $_POST['id'] ?? '';
            $found_idx = -1;
            foreach ($data['downloads'] as $idx => $d) {
                if ((string)($d['id'] ?? '') === (string)$dl_id) {
                    $found_idx = $idx;
                    break;
                }
            }
            if ($found_idx === -1) {
                $error_msg = 'Download-Eintrag nicht gefunden.';
                return;
            }

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $error_msg = 'Bitte gib einen Dokumenttitel oder Software-Namen an.';
                return;
            }

            $fileSource = ($_POST['file_source'] ?? 'upload') === 'external' ? 'external' : 'upload';
            $externalUrl = trim($_POST['external_url'] ?? '');
            $fileSize = trim($_POST['file_size'] ?? ($data['downloads'][$found_idx]['file_size'] ?? ''));

            $data['downloads'][$found_idx]['title'] = $title;
            $data['downloads'][$found_idx]['description'] = trim($_POST['description'] ?? '');
            $data['downloads'][$found_idx]['category'] = trim($_POST['category'] ?? 'Software');
            $data['downloads'][$found_idx]['file_source'] = $fileSource;
            $data['downloads'][$found_idx]['external_url'] = $externalUrl;
            $data['downloads'][$found_idx]['version'] = trim($_POST['version'] ?? '');
            $data['downloads'][$found_idx]['platform'] = trim($_POST['platform'] ?? '');
            $data['downloads'][$found_idx]['license'] = trim($_POST['license'] ?? '');
            $data['downloads'][$found_idx]['features'] = $parseFeatures($_POST['features'] ?? '');
            $data['downloads'][$found_idx]['screenshots'] = $parseScreenshots($_POST['screenshots'] ?? '');
            $data['downloads'][$found_idx]['changelog'] = trim($_POST['changelog'] ?? '');
            $data['downloads'][$found_idx]['show_counter'] = !empty($_POST['show_counter']);

            if (!empty($_FILES['download_file']['name'])) {
                $docInfo = ImageUploader::uploadDocument('download_file');
                if ($docInfo) {
                    $data['downloads'][$found_idx]['file_path'] = $docInfo['path'];
                    $data['downloads'][$found_idx]['file_size'] = $docInfo['size'];
                }
            } elseif (!empty($_POST['existing_file']) && $fileSource === 'upload') {
                $data['downloads'][$found_idx]['file_path'] = trim($_POST['existing_file']);
                $data['downloads'][$found_idx]['file_size'] = $fileSize;
            }

            if (save_data($dataFile, $data)) {
                $success_msg = 'Download-Eintrag „' . htmlspecialchars($title) . '“ erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern.';
            }

        } elseif ($action === 'reset_download_counter') {
            $dl_id = $_POST['id'] ?? '';
            $found = false;
            foreach ($data['downloads'] as $idx => $d) {
                if ((string)($d['id'] ?? '') === (string)$dl_id) {
                    $data['downloads'][$idx]['download_count'] = 0;
                    $found = true;
                    break;
                }
            }
            if ($found && save_data($dataFile, $data)) {
                $success_msg = 'Download-Zähler erfolgreich auf 0 zurückgesetzt.';
            } else {
                $error_msg = 'Eintrag nicht gefunden.';
            }

        } elseif ($action === 'delete_download') {
            $dl_id = $_POST['id'] ?? '';
            $orig_count = count($data['downloads']);
            $data['downloads'] = array_values(array_filter($data['downloads'], function($d) use ($dl_id) {
                return (string)($d['id'] ?? '') !== (string)$dl_id;
            }));

            if (count($data['downloads']) < $orig_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Download-Eintrag gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'Download-Eintrag nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Aktionen für Leistungen & Preise (Pricing Tables).
     */
    public static function handlePricingAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['pricing']) || !is_array($data['pricing'])) {
            $data['pricing'] = [];
        }

        if ($action === 'add_pricing_plan') {
            $title = trim($_POST['title'] ?? '');
            $price = trim($_POST['price'] ?? '');
            if (empty($title) || empty($price)) {
                $error_msg = 'Bitte gib Paketname und Preis an.';
                return;
            }

            $feats_raw = trim($_POST['features'] ?? '');
            $features = !empty($feats_raw) ? array_values(array_filter(array_map('trim', explode("\n", $feats_raw)))) : [];

            $newPlan = [
                'id' => 'pricing_' . uniqid(),
                'title' => $title,
                'price' => $price,
                'period' => trim($_POST['period'] ?? ''),
                'badge' => trim($_POST['badge'] ?? ''),
                'highlighted' => !empty($_POST['highlighted']),
                'button_text' => trim($_POST['button_text'] ?? 'Jetzt anfragen'),
                'button_link' => trim($_POST['button_link'] ?? '#contact'),
                'description' => trim($_POST['description'] ?? ''),
                'features' => $features,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data['pricing'][] = $newPlan;
            if (save_data($dataFile, $data)) {
                $success_msg = 'Preispaket „' . htmlspecialchars($title) . '“ erfolgreich erstellt!';
            } else {
                $error_msg = 'Fehler beim Speichern des Preispakets.';
            }

        } elseif ($action === 'edit_pricing_plan') {
            $plan_id = $_POST['id'] ?? '';
            $found_idx = -1;
            foreach ($data['pricing'] as $idx => $p) {
                if ((string)($p['id'] ?? '') === (string)$plan_id) {
                    $found_idx = $idx;
                    break;
                }
            }
            if ($found_idx === -1) {
                $error_msg = 'Preispaket nicht gefunden.';
                return;
            }

            $title = trim($_POST['title'] ?? '');
            $price = trim($_POST['price'] ?? '');
            if (empty($title) || empty($price)) {
                $error_msg = 'Bitte gib Paketname und Preis an.';
                return;
            }

            $feats_raw = trim($_POST['features'] ?? '');
            $features = !empty($feats_raw) ? array_values(array_filter(array_map('trim', explode("\n", $feats_raw)))) : [];

            $data['pricing'][$found_idx]['title'] = $title;
            $data['pricing'][$found_idx]['price'] = $price;
            $data['pricing'][$found_idx]['period'] = trim($_POST['period'] ?? '');
            $data['pricing'][$found_idx]['badge'] = trim($_POST['badge'] ?? '');
            $data['pricing'][$found_idx]['highlighted'] = !empty($_POST['highlighted']);
            $data['pricing'][$found_idx]['button_text'] = trim($_POST['button_text'] ?? 'Jetzt anfragen');
            $data['pricing'][$found_idx]['button_link'] = trim($_POST['button_link'] ?? '#contact');
            $data['pricing'][$found_idx]['description'] = trim($_POST['description'] ?? '');
            $data['pricing'][$found_idx]['features'] = $features;

            if (save_data($dataFile, $data)) {
                $success_msg = 'Preispaket „' . htmlspecialchars($title) . '“ erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern des Preispakets.';
            }

        } elseif ($action === 'delete_pricing_plan') {
            $plan_id = $_POST['id'] ?? '';
            $orig_count = count($data['pricing']);
            $data['pricing'] = array_values(array_filter($data['pricing'], function($p) use ($plan_id) {
                return (string)($p['id'] ?? '') !== (string)$plan_id;
            }));

            if (count($data['pricing']) < $orig_count) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Preispaket gelöscht.';
                } else {
                    $error_msg = 'Fehler beim Speichern.';
                }
            } else {
                $error_msg = 'Preispaket nicht gefunden.';
            }
        }
    }
}

