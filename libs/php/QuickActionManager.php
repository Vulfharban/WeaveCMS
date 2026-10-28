<?php
/**
 * ============================================================================
 * WeaveCMS – QuickActionManager (Phase 26)
 * ============================================================================
 * Benutzerdefinierte Dashboard-Schnellzugriffe & Quick-Actions (Custom Action-Hub):
 * - Strukturierter System-Aktionskatalog aller CMS-Funktionen
 * - Vordefinierte Workflow-Profile (Presets) für Redakteur, Admin, Allrounder & Minimalist
 * - Benutzerbezogene Persistenz und rollenbasierte Filterung (RBAC)
 * - Interaktives Konfigurations-Modal mit Live-Suche, Sortierung und 1-Klick-Presets
 * - Modernes responsives Kachel-Grid mit dynamischen Badges
 * ============================================================================
 */

require_once __DIR__ . '/UserManager.php';

class QuickActionManager {

    /**
     * Liefert alle thematischen Kategorien des Aktionskatalogs.
     * @return array
     */
    public static function getCategories(): array {
        return [
            'content' => [
                'id'    => 'content',
                'name'  => 'Inhalte & Redaktion',
                'icon'  => 'article',
                'color' => '#2563eb'
            ],
            'media' => [
                'id'    => 'media',
                'name'  => 'Medien & Assets',
                'icon'  => 'perm_media',
                'color' => '#7c3aed'
            ],
            'communication' => [
                'id'    => 'communication',
                'name'  => 'Kommunikation & Postfach',
                'icon'  => 'inbox',
                'color' => '#d97706'
            ],
            'design' => [
                'id'    => 'design',
                'name'  => 'Design & Layout',
                'icon'  => 'palette',
                'color' => '#db2777'
            ],
            'system' => [
                'id'    => 'system',
                'name'  => 'System, Sicherheit & Wartung',
                'icon'  => 'security',
                'color' => '#16a34a'
            ],
            'plugins' => [
                'id'    => 'plugins',
                'name'  => 'Plugins & Integrationen',
                'icon'  => 'extension',
                'color' => '#8b5cf6'
            ],
            'general' => [
                'id'    => 'general',
                'name'  => 'Allgemein & Navigation',
                'icon'  => 'public',
                'color' => '#2e6248'
            ]
        ];
    }

    /**
     * Liefert den vollständigen System-Aktionskatalog.
     * @return array
     */
    public static function getCatalog(): array {
        return [
            // ──────────────── INHALTE & REDAKTION ────────────────
            'new_blog_post' => [
                'id'          => 'new_blog_post',
                'title'       => 'Neuer Blogbeitrag',
                'description' => 'Neuen Artikel für das Journal verfassen',
                'category'    => 'content',
                'icon'        => 'article',
                'color'       => '#2563eb',
                'action_type' => 'tab',
                'target'      => 'tab-blog',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'new_page' => [
                'id'          => 'new_page',
                'title'       => 'Neue Einzelseite',
                'description' => 'Eigene Unterseite mit Seiteneditor erstellen',
                'category'    => 'content',
                'icon'        => 'post_add',
                'color'       => '#0d9488',
                'action_type' => 'tab',
                'target'      => 'tab-pages',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'edit_texts' => [
                'id'          => 'edit_texts',
                'title'       => 'Texte bearbeiten',
                'description' => 'Startseiten- und Sektionstexte pflegen',
                'category'    => 'content',
                'icon'        => 'edit_note',
                'color'       => '#16a34a',
                'action_type' => 'tab',
                'target'      => 'tab-texts',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_gallery' => [
                'id'          => 'manage_gallery',
                'title'       => 'Galerie pflegen',
                'description' => 'Galeriebilder, Filter und Kategorien verwalten',
                'category'    => 'content',
                'icon'        => 'add_photo_alternate',
                'color'       => '#8b5cf6',
                'action_type' => 'tab',
                'target'      => 'tab-gallery',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_courses' => [
                'id'          => 'manage_courses',
                'title'       => 'Kurse & Workshops',
                'description' => 'Termine, Kursdaten und Teilnehmer verwalten',
                'category'    => 'content',
                'icon'        => 'school',
                'color'       => '#ea580c',
                'action_type' => 'tab',
                'target'      => 'tab-courses',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_faqs' => [
                'id'          => 'manage_faqs',
                'title'       => 'FAQ-Einträge',
                'description' => 'Häufig gestellte Fragen und Antworten pflegen',
                'category'    => 'content',
                'icon'        => 'quiz',
                'color'       => '#0284c7',
                'action_type' => 'tab',
                'target'      => 'tab-faqs',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_team' => [
                'id'          => 'manage_team',
                'title'       => 'Team & Profile',
                'description' => 'Teammitglieder und Ansprechpartner verwalten',
                'category'    => 'content',
                'icon'        => 'badge',
                'color'       => '#4f46e5',
                'action_type' => 'tab',
                'target'      => 'tab-team',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_pricing' => [
                'id'          => 'manage_pricing',
                'title'       => 'Preistabellen',
                'description' => 'Leistungspakete und Konditionen konfigurieren',
                'category'    => 'content',
                'icon'        => 'payments',
                'color'       => '#059669',
                'action_type' => 'tab',
                'target'      => 'tab-pricing',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'manage_downloads' => [
                'id'          => 'manage_downloads',
                'title'       => 'Downloads & Software',
                'description' => 'Dateien, Versionen und Downloads verwalten',
                'category'    => 'content',
                'icon'        => 'file_download',
                'color'       => '#0891b2',
                'action_type' => 'tab',
                'target'      => 'tab-downloads',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'view_revisions' => [
                'id'          => 'view_revisions',
                'title'       => 'Revisionshistorie',
                'description' => 'Änderungsverlauf und Versionen einsehen',
                'category'    => 'content',
                'icon'        => 'history',
                'color'       => '#64748b',
                'action_type' => 'tab',
                'target'      => 'tab-texts',
                'min_role'    => UserManager::ROLE_EDITOR
            ],

            // ──────────────── MEDIEN & ASSETS ────────────────
            'upload_media' => [
                'id'          => 'upload_media',
                'title'       => 'Datei hochladen',
                'description' => 'Bilder, PDFs oder Archive direkt hochladen',
                'category'    => 'media',
                'icon'        => 'cloud_upload',
                'color'       => '#0284c7',
                'action_type' => 'tab',
                'target'      => 'tab-media',
                'trigger_js'  => 'focusMediaUpload',
                'min_role'    => UserManager::ROLE_EDITOR
            ],
            'media_library' => [
                'id'          => 'media_library',
                'title'       => 'Mediathek öffnen',
                'description' => 'Dateien organisieren, filtern und Alt-Texte pflegen',
                'category'    => 'media',
                'icon'        => 'perm_media',
                'color'       => '#7c3aed',
                'action_type' => 'tab',
                'target'      => 'tab-media',
                'min_role'    => UserManager::ROLE_EDITOR
            ],

            // ──────────────── KOMMUNIKATION & POSTFACH ────────────────
            'inbox' => [
                'id'          => 'inbox',
                'title'       => 'Kontaktpostfach',
                'description' => 'Eingehende Nachrichten und Anfragen einsehen',
                'category'    => 'communication',
                'icon'        => 'inbox',
                'color'       => '#d97706',
                'action_type' => 'tab',
                'target'      => 'tab-inbox',
                'min_role'    => UserManager::ROLE_MODERATOR,
                'badge_key'   => 'inbox_unread'
            ],
            'smtp_settings' => [
                'id'          => 'smtp_settings',
                'title'       => 'SMTP- & Mail-Setup',
                'description' => 'E-Mail-Server und Benachrichtigungen einrichten',
                'category'    => 'communication',
                'icon'        => 'mark_email_read',
                'color'       => '#2563eb',
                'action_type' => 'tab',
                'target'      => 'tab-settings',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'edit_impressum' => [
                'id'          => 'edit_impressum',
                'title'       => 'Impressum & Kontakt',
                'description' => 'Kontaktdaten, Anschrift und Rechtstexte pflegen',
                'category'    => 'communication',
                'icon'        => 'contact_mail',
                'color'       => '#4b5563',
                'action_type' => 'tab',
                'target'      => 'tab-impressum',
                'min_role'    => UserManager::ROLE_EDITOR
            ],

            // ──────────────── DESIGN & LAYOUT ────────────────
            'theme_colors' => [
                'id'          => 'theme_colors',
                'title'       => 'Design & Farben',
                'description' => 'Farben, Typografie und Farbmodus anpassen',
                'category'    => 'design',
                'icon'        => 'palette',
                'color'       => '#db2777',
                'action_type' => 'tab',
                'target'      => 'tab-design',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'menu_styles' => [
                'id'          => 'menu_styles',
                'title'       => 'Menüstil & Navigation',
                'description' => 'Navigationsleiste, Menüstil und Optionen wählen',
                'category'    => 'design',
                'icon'        => 'menu_open',
                'color'       => '#9333ea',
                'action_type' => 'tab',
                'target'      => 'tab-display',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'manage_logo' => [
                'id'          => 'manage_logo',
                'title'       => 'Logo & Favicon',
                'description' => 'Website-Logo, Motiv und Favicon austauschen',
                'category'    => 'design',
                'icon'        => 'image',
                'color'       => '#059669',
                'action_type' => 'tab',
                'target'      => 'tab-display',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'section_order' => [
                'id'          => 'section_order',
                'title'       => 'Sektionen-Reihenfolge',
                'description' => 'Sektionen der Startseite per Kanban ordnen',
                'category'    => 'design',
                'icon'        => 'view_kanban',
                'color'       => '#ea580c',
                'action_type' => 'tab',
                'target'      => 'tab-display',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'seo_settings' => [
                'id'          => 'seo_settings',
                'title'       => 'SEO & Social Media',
                'description' => 'Suchmaschinen-Titel, Meta-Tags und OpenGraph',
                'category'    => 'design',
                'icon'        => 'travel_explore',
                'color'       => '#0284c7',
                'action_type' => 'tab',
                'target'      => 'tab-seo',
                'min_role'    => UserManager::ROLE_ADMIN
            ],

            // ──────────────── SYSTEM, SICHERHEIT & WARTUNG ────────────────
            'create_backup' => [
                'id'          => 'create_backup',
                'title'       => '1-Klick-Sicherung',
                'description' => 'Vollständiges ZIP-Backup des Systems erstellen',
                'category'    => 'system',
                'icon'        => 'folder_zip',
                'color'       => '#16a34a',
                'action_type' => 'tab',
                'target'      => 'tab-security',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'system_logs' => [
                'id'          => 'system_logs',
                'title'       => 'Sicherheits-Logs',
                'description' => 'Ereignis- und Login-Protokolle einsehen',
                'category'    => 'system',
                'icon'        => 'receipt_long',
                'color'       => '#dc2626',
                'action_type' => 'tab',
                'target'      => 'tab-security',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'database_status' => [
                'id'          => 'database_status',
                'title'       => 'Datenbank & Storage',
                'description' => 'Speicher-Engine prüfen (JSON / MySQL)',
                'category'    => 'system',
                'icon'        => 'storage',
                'color'       => '#4f46e5',
                'action_type' => 'tab',
                'target'      => 'tab-settings',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'user_management' => [
                'id'          => 'user_management',
                'title'       => 'Benutzerverwaltung',
                'description' => 'Admin-, Moderator- und Redakteurkonten verwalten',
                'category'    => 'system',
                'icon'        => 'group',
                'color'       => '#6366f1',
                'action_type' => 'tab',
                'target'      => 'tab-users',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'check_updates' => [
                'id'          => 'check_updates',
                'title'       => 'Updates prüfen',
                'description' => 'Online auf neue Core- und Plugin-Updates prüfen',
                'category'    => 'system',
                'icon'        => 'system_update',
                'color'       => '#2563eb',
                'action_type' => 'url',
                'target'      => '?tab=tab-dashboard&check_updates=1',
                'min_role'    => UserManager::ROLE_ADMIN
            ],

            // ──────────────── PLUGINS & INTEGRATIONEN ────────────────
            'plugin_manager' => [
                'id'          => 'plugin_manager',
                'title'       => 'Plugin-Manager',
                'description' => 'Erweiterungen verwalten, aktivieren und updaten',
                'category'    => 'plugins',
                'icon'        => 'extension',
                'color'       => '#8b5cf6',
                'action_type' => 'tab',
                'target'      => 'tab-plugins',
                'min_role'    => UserManager::ROLE_ADMIN
            ],
            'forum_admin' => [
                'id'          => 'forum_admin',
                'title'       => 'Forum administrieren',
                'description' => 'Kategorien, Forenbeiträge und Moderation',
                'category'    => 'plugins',
                'icon'        => 'forum',
                'color'       => '#0d9488',
                'action_type' => 'tab',
                'target'      => 'tab-plugins',
                'min_role'    => UserManager::ROLE_ADMIN,
                'condition'   => 'plugin_forum'
            ],
            'shop_admin' => [
                'id'          => 'shop_admin',
                'title'       => 'Shop & Bestellungen',
                'description' => 'Artikel, Bestellungen und Zahlungen verwalten',
                'category'    => 'plugins',
                'icon'        => 'shopping_bag',
                'color'       => '#ea580c',
                'action_type' => 'tab',
                'target'      => 'tab-plugins',
                'min_role'    => UserManager::ROLE_ADMIN,
                'condition'   => 'plugin_shop'
            ],
            'inventory_admin' => [
                'id'          => 'inventory_admin',
                'title'       => 'Warenwirtschaft',
                'description' => 'Lagerbestände und Artikelkatalog öffnen',
                'category'    => 'plugins',
                'icon'        => 'inventory_2',
                'color'       => '#7c3aed',
                'action_type' => 'tab',
                'target'      => 'tab-plugins',
                'min_role'    => UserManager::ROLE_ADMIN,
                'condition'   => 'plugin_inventory'
            ],

            // ──────────────── ALLGEMEIN ────────────────
            'live_website' => [
                'id'          => 'live_website',
                'title'       => 'Live-Website',
                'description' => 'Öffentliche Website im neuen Browser-Tab öffnen',
                'category'    => 'general',
                'icon'        => 'open_in_new',
                'color'       => '#2e6248',
                'action_type' => 'url',
                'target'      => '../',
                'open_blank'  => true,
                'min_role'    => UserManager::ROLE_EDITOR
            ]
        ];
    }

    /**
     * Vordefinierte Workflow-Profile (Presets) für die 1-Klick-Auswahl.
     * @return array
     */
    public static function getPresets(): array {
        return [
            'preset_allrounder' => [
                'id'          => 'preset_allrounder',
                'name'        => 'Allrounder / Standard',
                'description' => 'Ausgewogene Mischung der wichtigsten täglichen Handgriffe für Admins & Teams.',
                'icon'        => 'tune',
                'actions'     => [
                    'new_blog_post', 'manage_gallery', 'upload_media', 'inbox',
                    'create_backup', 'theme_colors', 'live_website'
                ]
            ],
            'preset_editor' => [
                'id'          => 'preset_editor',
                'name'        => 'Redakteur / Content-Fokus',
                'description' => 'Fokus auf Beiträge, neue Seiten, Medien-Upload und Bildgalerien.',
                'icon'        => 'edit',
                'actions'     => [
                    'new_blog_post', 'new_page', 'edit_texts', 'manage_gallery',
                    'upload_media', 'media_library', 'edit_impressum', 'live_website'
                ]
            ],
            'preset_admin' => [
                'id'          => 'preset_admin',
                'name'        => 'Administrator / Wartung',
                'description' => 'Fokus auf Backups, Logs, User-Management, System-Audit und Updates.',
                'icon'        => 'admin_panel_settings',
                'actions'     => [
                    'create_backup', 'user_management', 'system_logs', 'database_status',
                    'check_updates', 'plugin_manager', 'seo_settings', 'live_website'
                ]
            ],
            'preset_minimal' => [
                'id'          => 'preset_minimal',
                'name'        => 'Minimalist',
                'description' => 'Nur 4 essenzielle Kern-Aktionen für maximale Übersicht und schnelles Arbeiten.',
                'icon'        => 'crop_portrait',
                'actions'     => [
                    'new_blog_post', 'manage_gallery', 'inbox', 'live_website'
                ]
            ]
        ];
    }

    /**
     * Ermittelt die Standard-Aktionen abhängig von der Rolle des Benutzers.
     * @param string|null $role
     * @return array
     */
    public static function getDefaultActionIds(?string $role = null): array {
        if ($role === UserManager::ROLE_EDITOR) {
            return self::getPresets()['preset_editor']['actions'];
        }
        return self::getPresets()['preset_allrounder']['actions'];
    }

    /**
     * Prüft, ob eine optionale Bedingung (z. B. Plugin aktiv) erfüllt ist.
     * @param string $condition
     * @return bool
     */
    protected static function checkCondition(string $condition): bool {
        $root = dirname(__DIR__, 2);
        switch ($condition) {
            case 'plugin_forum':
                return file_exists($root . '/plugins/weave-forum/plugin.php');
            case 'plugin_shop':
                return file_exists($root . '/plugins/shop/plugin.php');
            case 'plugin_inventory':
                return file_exists($root . '/plugins/inventory/plugin.php');
            default:
                return true;
        }
    }

    /**
     * Ermittelt die aktiven und berechtigten Schnellzugriffe für den aktuellen Benutzer.
     * @param array $curUser
     * @param array $data
     * @return array
     */
    public static function getUserQuickActions(array $curUser, array $data): array {
        $catalog = self::getCatalog();
        $userRole = $curUser['role'] ?? UserManager::ROLE_ADMIN;
        $userId = $curUser['id'] ?? 'usr_superadmin';

        // 1. Gespeicherte IDs ermitteln (Benutzerkonto -> Globale Settings -> Standard-Preset)
        $actionIds = [];
        if (!empty($curUser['quickactions']) && is_array($curUser['quickactions'])) {
            $actionIds = $curUser['quickactions'];
        } elseif (!empty($data['settings']['dashboard_quickactions'][$userId]) && is_array($data['settings']['dashboard_quickactions'][$userId])) {
            $actionIds = $data['settings']['dashboard_quickactions'][$userId];
        } elseif (!empty($data['settings']['dashboard_quickactions_default']) && is_array($data['settings']['dashboard_quickactions_default'])) {
            $actionIds = $data['settings']['dashboard_quickactions_default'];
        } else {
            $actionIds = self::getDefaultActionIds($userRole);
        }

        // 2. Aktionen validieren und RBAC-Prüfung anwenden
        $resolved = [];
        foreach ($actionIds as $id) {
            if (!isset($catalog[$id])) {
                continue;
            }
            $act = $catalog[$id];

            // Rollenprüfung (min_role)
            if (!self::hasRoleAccess($userRole, $act['min_role'])) {
                continue;
            }

            // Tab-Zugriffsprüfung
            if (!empty($act['target']) && strpos($act['target'], 'tab-') === 0) {
                if (!UserManager::canAccessTab($act['target'], $userRole)) {
                    continue;
                }
            }

            // Bedingungsprüfung (z. B. installierte Plugins)
            if (!empty($act['condition']) && !self::checkCondition($act['condition'])) {
                continue;
            }

            // Dynamische Badges ermitteln
            $act['badge'] = null;
            if (!empty($act['badge_key'])) {
                if ($act['badge_key'] === 'inbox_unread') {
                    $unread = class_exists('ContactInbox') ? ContactInbox::countNew() : 0;
                    if ($unread > 0) {
                        $act['badge'] = [
                            'text'  => (string)$unread,
                            'color' => '#d97706',
                            'title' => "{$unread} ungelesene Nachricht(en)"
                        ];
                    }
                }
            }

            $resolved[] = $act;
        }

        return $resolved;
    }

    /**
     * Prüft, ob eine Benutzerrolle die erforderliche Mindestrolle erfüllt.
     * Hierarchie: admin > moderator > editor
     * @param string $curRole
     * @param string $minRole
     * @return bool
     */
    public static function hasRoleAccess(string $curRole, string $minRole): bool {
        if ($curRole === UserManager::ROLE_ADMIN) {
            return true;
        }
        if ($curRole === UserManager::ROLE_MODERATOR) {
            return ($minRole === UserManager::ROLE_MODERATOR || $minRole === UserManager::ROLE_EDITOR);
        }
        if ($curRole === UserManager::ROLE_EDITOR) {
            return ($minRole === UserManager::ROLE_EDITOR);
        }
        return false;
    }

    /**
     * Speichert die ausgewählten Schnellzugriffe für den Benutzer.
     * @param string $userId
     * @param array $actionIds
     * @param string $dataFile
     * @param array &$data
     * @return bool
     */
    public static function saveUserQuickActions(string $userId, array $actionIds, string $dataFile, array &$data): bool {
        $catalog = self::getCatalog();
        $validIds = [];

        foreach ($actionIds as $id) {
            $cleanId = trim((string)$id);
            if (isset($catalog[$cleanId]) && !in_array($cleanId, $validIds, true)) {
                $validIds[] = $cleanId;
            }
        }

        if (empty($validIds)) {
            $validIds = self::getDefaultActionIds(UserManager::ROLE_ADMIN);
        }

        // Im Benutzerdatensatz speichern
        if (!empty($data['users']) && is_array($data['users'])) {
            foreach ($data['users'] as &$u) {
                if (($u['id'] ?? '') === $userId) {
                    $u['quickactions'] = $validIds;
                    break;
                }
            }
        }

        // Im Settings-Array speichern
        if (!isset($data['settings'])) {
            $data['settings'] = [];
        }
        if (!isset($data['settings']['dashboard_quickactions']) || !is_array($data['settings']['dashboard_quickactions'])) {
            $data['settings']['dashboard_quickactions'] = [];
        }
        $data['settings']['dashboard_quickactions'][$userId] = $validIds;

        // Falls Superadmin/Admin, auch als globaler Standard
        $curUser = UserManager::getCurrentUser($data);
        if (($curUser['role'] ?? '') === UserManager::ROLE_ADMIN) {
            $data['settings']['dashboard_quickactions_default'] = $validIds;
        }

        return (bool)save_data($dataFile, $data);
    }

    /**
     * Setzt die Schnellzugriffe für einen Benutzer auf den Rollen-Standard zurück.
     * @param string $userId
     * @param string $role
     * @param string $dataFile
     * @param array &$data
     * @return bool
     */
    public static function resetUserQuickActions(string $userId, string $role, string $dataFile, array &$data): bool {
        $defaultIds = self::getDefaultActionIds($role);
        return self::saveUserQuickActions($userId, $defaultIds, $dataFile, $data);
    }

    /**
     * Rendert das Kachel-Widget für das Dashboard.
     * @param array $curUser
     * @param array $data
     * @return void
     */
    public static function renderDashboardWidget(array $curUser, array $data): void {
        $actions = self::getUserQuickActions($curUser, $data);
        ?>
        <div class="quickactions-widget-container" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.5rem 1.75rem; margin-bottom: 2.25rem; box-shadow: 0 4px 20px rgba(0,0,0,0.02); position: relative;">
            
            <!-- Header-Bereich -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(46,98,72,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                        <span class="material-icons" style="font-size: 20px;">bolt</span>
                    </div>
                    <div>
                        <h4 style="margin:0; font-size: 1.05rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.2px;">
                            Schnellzugriffe &amp; Workflows
                        </h4>
                        <span style="font-size: 0.8rem; color: var(--text-secondary);">
                            Deine persönlichen Favoriten für den schnellen Arbeitsalltag (<?php echo count($actions); ?> aktiv)
                        </span>
                    </div>
                </div>

                <button type="button" class="btn btn-outline" onclick="openQuickActionsModal()" style="padding: 6px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; border-radius: 10px;" title="Schnellzugriffe anpassen">
                    <span class="material-icons" style="font-size: 16px; color: var(--primary);">tune</span> Anpassen
                </button>
            </div>

            <!-- Kachel-Grid -->
            <div class="quickactions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px;">
                <?php foreach ($actions as $act): ?>
                    <?php
                    $isUrl = ($act['action_type'] === 'url');
                    $targetTab = $act['target'] ?? 'tab-dashboard';
                    $openBlank = !empty($act['open_blank']);
                    $triggerJs = $act['trigger_js'] ?? '';
                    $badge = $act['badge'] ?? null;
                    ?>
                    <?php if ($isUrl): ?>
                        <a href="<?php echo htmlspecialchars($act['target']); ?>" <?php if ($openBlank): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?> class="quickaction-card" style="text-decoration:none;">
                    <?php else: ?>
                        <div class="quickaction-card" onclick="handleQuickActionClick('<?php echo htmlspecialchars($targetTab); ?>', '<?php echo htmlspecialchars($triggerJs); ?>')">
                    <?php endif; ?>
                            <div class="quickaction-icon-wrap" style="background: <?php echo htmlspecialchars($act['color']); ?>15; color: <?php echo htmlspecialchars($act['color']); ?>;">
                                <span class="material-icons" style="font-size: 24px;"><?php echo htmlspecialchars($act['icon']); ?></span>
                                <?php if ($badge): ?>
                                    <span class="quickaction-badge" title="<?php echo htmlspecialchars($badge['title']); ?>" style="background: <?php echo htmlspecialchars($badge['color']); ?>;">
                                        <?php echo htmlspecialchars($badge['text']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="quickaction-text-wrap">
                                <span class="quickaction-title"><?php echo htmlspecialchars($act['title']); ?></span>
                                <span class="quickaction-desc"><?php echo htmlspecialchars($act['description']); ?></span>
                            </div>
                            <span class="material-icons quickaction-arrow" style="font-size: 16px;">arrow_forward</span>
                    <?php if ($isUrl): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .quickaction-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            position: relative;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .quickaction-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
            background: #fff;
        }
        .quickaction-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            transition: transform 0.2s ease;
        }
        .quickaction-card:hover .quickaction-icon-wrap {
            transform: scale(1.08);
        }
        .quickaction-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid #fff;
            line-height: 1;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .quickaction-text-wrap {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }
        .quickaction-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .quickaction-desc {
            font-size: 0.76rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .quickaction-arrow {
            color: var(--text-secondary);
            opacity: 0.35;
            transition: all 0.2s ease;
            margin-left: auto;
            flex-shrink: 0;
        }
        .quickaction-card:hover .quickaction-arrow {
            opacity: 1;
            color: var(--primary);
            transform: translateX(3px);
        }
        </style>
        <?php
    }

    /**
     * Rendert das interaktive Konfigurations-Modal.
     * @param array $curUser
     * @param array $data
     * @param string $csrfToken
     * @return void
     */
    public static function renderConfigModal(array $curUser, array $data, string $csrfToken): void {
        $catalog = self::getCatalog();
        $categories = self::getCategories();
        $presets = self::getPresets();
        $currentActions = self::getUserQuickActions($curUser, $data);
        $currentIds = array_column($currentActions, 'id');
        $userRole = $curUser['role'] ?? UserManager::ROLE_ADMIN;
        ?>
        <div id="modal-quickactions-config" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:4000; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); padding:1rem;" onclick="if(event.target===this) closeQuickActionsModal();">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:24px; max-width:880px; width:100%; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); overflow:hidden;">
                
                <!-- Modal-Kopfzeile -->
                <div style="padding:1.5rem 1.75rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.015);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(46,98,72,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center;">
                            <span class="material-icons" style="font-size:24px;">tune</span>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.25rem; font-weight:800; color:var(--text-primary);">
                                Schnellzugriffe &amp; Workflows anpassen
                            </h3>
                            <span style="font-size:0.83rem; color:var(--text-secondary);">
                                Wähle deine persönlichen Favoriten aus dem Gesamtkatalog und bestimme die Reihenfolge.
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="closeQuickActionsModal()" style="width:36px; height:36px; padding:0; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <span class="material-icons" style="font-size:20px;">close</span>
                    </button>
                </div>

                <!-- Modal-Body (Zweigeteilt: Katalog & Auswahlliste) -->
                <div style="display:grid; grid-template-columns: 1.25fr 1fr; flex:1; min-height:0; overflow:hidden;">
                    
                    <!-- Linke Spalte: Gesamtkatalog mit Filter & Presets -->
                    <div style="padding:1.5rem; overflow-y:auto; border-right:1px solid var(--border-color); display:flex; flex-direction:column; gap:16px;">
                        
                        <!-- 1. Workflow-Presets -->
                        <div>
                            <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); display:block; margin-bottom:8px;">
                                ⚡ Schnell-Profile (Presets)
                            </span>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:8px;">
                                <?php foreach ($presets as $pKey => $preset): ?>
                                    <button type="button" class="preset-btn" onclick="applyQuickActionPreset('<?php echo htmlspecialchars($pKey); ?>')" style="padding:8px 10px; border:1px solid var(--border-color); border-radius:10px; background:rgba(255,255,255,0.7); text-align:left; cursor:pointer; transition:all 0.15s ease;">
                                        <div style="display:flex; align-items:center; gap:6px; font-weight:700; font-size:0.82rem; color:var(--text-primary); margin-bottom:2px;">
                                            <span class="material-icons" style="font-size:15px; color:var(--primary);"><?php echo htmlspecialchars($preset['icon']); ?></span>
                                            <?php echo htmlspecialchars($preset['name']); ?>
                                        </div>
                                        <span style="font-size:0.7rem; color:var(--text-secondary); display:block; line-height:1.2; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                            <?php echo htmlspecialchars($preset['description']); ?>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 2. Suchleiste & Kategorie-Pills -->
                        <div>
                            <div style="position:relative; margin-bottom:10px;">
                                <input type="text" id="quickactions-search-input" oninput="filterQuickActionsCatalog()" placeholder="Aktion suchen (z. B. Blog, Backup, Galerie)..." style="width:100%; padding:9px 12px 9px 38px; border:1px solid var(--border-color); border-radius:12px; font-size:0.88rem; outline:none; background:var(--bg-card); box-sizing:border-box;">
                                <span class="material-icons" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--text-secondary);">search</span>
                            </div>

                            <div style="display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;" class="category-pills">
                                <button type="button" class="cat-pill active" onclick="setQuickActionCategoryFilter('all', this)">Alle</button>
                                <?php foreach ($categories as $cat): ?>
                                    <button type="button" class="cat-pill" onclick="setQuickActionCategoryFilter('<?php echo htmlspecialchars($cat['id']); ?>', this)">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 3. Aktionskatalog-Liste -->
                        <div id="quickactions-catalog-items" style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($catalog as $act): ?>
                                <?php
                                $hasAccess = self::hasRoleAccess($userRole, $act['min_role']);
                                if (!empty($act['condition']) && !self::checkCondition($act['condition'])) {
                                    continue;
                                }
                                ?>
                                <label class="catalog-item-row <?php if (!$hasAccess) echo 'disabled-role'; ?>" data-id="<?php echo htmlspecialchars($act['id']); ?>" data-cat="<?php echo htmlspecialchars($act['category']); ?>" data-title="<?php echo strtolower(htmlspecialchars($act['title'])); ?>" data-desc="<?php echo strtolower(htmlspecialchars($act['description'])); ?>">
                                    <input type="checkbox" value="<?php echo htmlspecialchars($act['id']); ?>" <?php if (!$hasAccess): ?>disabled<?php else: ?>onchange="onQuickActionToggle(this)"<?php endif; ?> class="quickaction-checkbox">
                                    <div class="cat-icon-badge" style="background:<?php echo htmlspecialchars($act['color']); ?>15; color:<?php echo htmlspecialchars($act['color']); ?>;">
                                        <span class="material-icons" style="font-size:18px;"><?php echo htmlspecialchars($act['icon']); ?></span>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:700; font-size:0.86rem; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                                            <?php echo htmlspecialchars($act['title']); ?>
                                            <?php if (!$hasAccess): ?>
                                                <span style="font-size:0.68rem; background:rgba(239,68,68,0.1); color:#dc2626; padding:1px 6px; border-radius:4px; font-weight:700;">Nur Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?php echo htmlspecialchars($act['description']); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- Rechte Spalte: Aktuell ausgewählte Favoriten & Sortierung -->
                    <div style="padding:1.5rem; overflow-y:auto; display:flex; flex-direction:column; background:rgba(0,0,0,0.01);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <div>
                                <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); display:block;">
                                    📌 Ausgewählte Schnellzugriffe (<span id="qa-selected-counter">0</span>)
                                </span>
                                <span style="font-size:0.75rem; color:var(--text-secondary);">
                                    Reihenfolge mit den Pfeilen flexibel anpassen
                                </span>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="resetQuickActionsToDefault()" style="padding:4px 8px; font-size:0.75rem; display:inline-flex; align-items:center; gap:4px;" title="Auf Standard zurücksetzen">
                                <span class="material-icons" style="font-size:14px;">restart_alt</span> Reset
                            </button>
                        </div>

                        <!-- Drag/Drop / Pfeil-Liste der ausgewählten Aktionen -->
                        <div id="quickactions-selected-list" style="display:flex; flex-direction:column; gap:8px; flex:1; min-height:160px;">
                            <!-- Wird dynamisch per JS befüllt -->
                        </div>
                    </div>
                </div>

                <!-- Modal-Fußzeile mit Speichern-Formular -->
                <form id="form-quickactions-save" method="POST" action="" style="margin:0;">
                    <input type="hidden" name="action" value="save_quickactions">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="target_tab" value="tab-dashboard">
                    <input type="hidden" name="action_ids_json" id="qa-action-ids-input" value="">

                    <div style="padding:1.25rem 1.75rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.02);">
                        <span style="font-size:0.8rem; color:var(--text-secondary);">
                            Tipp: Die Auswahl wird benutzerspezifisch in deinem Profil gespeichert.
                        </span>
                        <div style="display:flex; gap:10px;">
                            <button type="button" class="btn btn-outline" onclick="closeQuickActionsModal()">Abbrechen</button>
                            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px;">
                                <span class="material-icons" style="font-size:18px;">save</span> Favoriten speichern
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <style>
        .preset-btn:hover {
            border-color: var(--primary) !important;
            background: rgba(46,98,72,0.06) !important;
        }
        .cat-pill {
            padding: 5px 12px;
            border-radius: 14px;
            font-size: 0.78rem;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.7);
            color: var(--text-secondary);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .cat-pill.active, .cat-pill:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .catalog-item-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.8);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .catalog-item-row:hover {
            border-color: var(--primary);
            background: #fff;
        }
        .catalog-item-row.disabled-role {
            opacity: 0.55;
            cursor: not-allowed;
            background: rgba(0,0,0,0.02);
        }
        .cat-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .selected-item-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            transition: transform 0.15s ease;
        }
        .selected-item-row:hover {
            border-color: var(--primary);
        }
        .selected-order-btn {
            background: none;
            border: none;
            padding: 2px;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .selected-order-btn:hover {
            color: var(--primary);
            background: rgba(46,98,72,0.1);
        }
        </style>

        <script>
        // Globaler Katalog & Initialdaten
        const QA_CATALOG = <?php echo json_encode($catalog); ?>;
        const QA_PRESETS = <?php echo json_encode($presets); ?>;
        const QA_DEFAULT_IDS = <?php echo json_encode(self::getDefaultActionIds($userRole)); ?>;
        let QA_CURRENT_SELECTED = <?php echo json_encode($currentIds); ?>;

        function openQuickActionsModal() {
            syncQuickActionsModalUI();
            const modal = document.getElementById('modal-quickactions-config');
            if (modal) {
                modal.style.display = 'flex';
                document.getElementById('quickactions-search-input').focus();
            }
        }

        function closeQuickActionsModal() {
            const modal = document.getElementById('modal-quickactions-config');
            if (modal) modal.style.display = 'none';
        }

        function syncQuickActionsModalUI() {
            // Checkboxen synchronisieren
            document.querySelectorAll('.quickaction-checkbox').forEach(cb => {
                cb.checked = QA_CURRENT_SELECTED.includes(cb.value);
            });

            // Auswahlliste rechts neu zeichnen
            renderSelectedQuickActionsList();

            // Hidden JSON Input aktualisieren
            const input = document.getElementById('qa-action-ids-input');
            if (input) {
                input.value = JSON.stringify(QA_CURRENT_SELECTED);
            }

            // Counter aktualisieren
            const counter = document.getElementById('qa-selected-counter');
            if (counter) {
                counter.textContent = QA_CURRENT_SELECTED.length;
            }
        }

        function onQuickActionToggle(cb) {
            const id = cb.value;
            if (cb.checked) {
                if (!QA_CURRENT_SELECTED.includes(id)) {
                    QA_CURRENT_SELECTED.push(id);
                }
            } else {
                QA_CURRENT_SELECTED = QA_CURRENT_SELECTED.filter(item => item !== id);
            }
            syncQuickActionsModalUI();
        }

        function renderSelectedQuickActionsList() {
            const listEl = document.getElementById('quickactions-selected-list');
            if (!listEl) return;
            listEl.innerHTML = '';

            if (QA_CURRENT_SELECTED.length === 0) {
                listEl.innerHTML = '<div style="text-align:center; padding:2rem 1rem; color:var(--text-secondary); font-size:0.85rem; border:2px dashed var(--border-color); border-radius:12px;">Keine Schnellzugriffe ausgewählt.<br>Wähle links Aktionen oder ein Preset aus.</div>';
                return;
            }

            QA_CURRENT_SELECTED.forEach((id, idx) => {
                const act = QA_CATALOG[id];
                if (!act) return;

                const row = document.createElement('div');
                row.className = 'selected-item-row';
                row.innerHTML = `
                    <div class="cat-icon-badge" style="background:${act.color}15; color:${act.color};">
                        <span class="material-icons" style="font-size:18px;">${act.icon}</span>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <strong style="font-size:0.86rem; color:var(--text-primary); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            ${act.title}
                        </strong>
                        <span style="font-size:0.73rem; color:var(--text-secondary); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            ${act.description}
                        </span>
                    </div>
                    <div style="display:flex; align-items:center; gap:2px;">
                        <button type="button" class="selected-order-btn" title="Nach oben" onclick="moveQuickAction(${idx}, -1)" ${idx === 0 ? 'disabled style="opacity:0.25; cursor:default;"' : ''}>
                            <span class="material-icons" style="font-size:18px;">arrow_upward</span>
                        </button>
                        <button type="button" class="selected-order-btn" title="Nach unten" onclick="moveQuickAction(${idx}, 1)" ${idx === QA_CURRENT_SELECTED.length - 1 ? 'disabled style="opacity:0.25; cursor:default;"' : ''}>
                            <span class="material-icons" style="font-size:18px;">arrow_downward</span>
                        </button>
                        <button type="button" class="selected-order-btn" title="Entfernen" onclick="removeQuickAction('${id}')" style="color:#ef4444; margin-left:4px;">
                            <span class="material-icons" style="font-size:18px;">close</span>
                        </button>
                    </div>
                `;
                listEl.appendChild(row);
            });
        }

        function moveQuickAction(idx, direction) {
            const newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= QA_CURRENT_SELECTED.length) return;
            const temp = QA_CURRENT_SELECTED[idx];
            QA_CURRENT_SELECTED[idx] = QA_CURRENT_SELECTED[newIdx];
            QA_CURRENT_SELECTED[newIdx] = temp;
            syncQuickActionsModalUI();
        }

        function removeQuickAction(id) {
            QA_CURRENT_SELECTED = QA_CURRENT_SELECTED.filter(item => item !== id);
            syncQuickActionsModalUI();
        }

        function applyQuickActionPreset(presetKey) {
            const preset = QA_PRESETS[presetKey];
            if (!preset || !preset.actions) return;
            QA_CURRENT_SELECTED = [...preset.actions];
            syncQuickActionsModalUI();
        }

        function resetQuickActionsToDefault() {
            QA_CURRENT_SELECTED = [...QA_DEFAULT_IDS];
            syncQuickActionsModalUI();
        }

        function filterQuickActionsCatalog() {
            const term = (document.getElementById('quickactions-search-input').value || '').toLowerCase().trim();
            const activeCatPill = document.querySelector('.cat-pill.active');
            const activeCat = activeCatPill ? activeCatPill.getAttribute('data-cat') || 'all' : 'all';

            document.querySelectorAll('.catalog-item-row').forEach(row => {
                const cat = row.getAttribute('data-cat');
                const title = row.getAttribute('data-title') || '';
                const desc = row.getAttribute('data-desc') || '';

                const matchesSearch = term === '' || title.includes(term) || desc.includes(term);
                const matchesCat = activeCat === 'all' || cat === activeCat;

                row.style.display = (matchesSearch && matchesCat) ? 'flex' : 'none';
            });
        }

        function setQuickActionCategoryFilter(catId, btn) {
            document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            btn.setAttribute('data-cat', catId);
            filterQuickActionsCatalog();
        }

        function handleQuickActionClick(targetTab, triggerJs) {
            if (typeof switchTab === 'function') {
                switchTab(targetTab);
            }
            if (triggerJs) {
                setTimeout(() => {
                    if (triggerJs === 'focusMediaUpload') {
                        const fileInput = document.getElementById('media-upload-input');
                        if (fileInput) fileInput.click();
                    }
                }, 150);
            }
        }
        </script>
        <?php
    }
}
