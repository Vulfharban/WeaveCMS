<?php
/**
 * ============================================================================
 * WeaveCMS – Theme- & Menü-Manager (Phase 19: Menü-Aufbau & Theme-Erweiterbarkeit)
 * ============================================================================
 * Verwaltet Menüstile, Sidebar-Positionen, Theme-Layouts und Plugin-Hooks.
 */

class ThemeManager {

    const MENU_HORIZONTAL_MINIMAL = 'horizontal_minimal';
    const MENU_HORIZONTAL_ICONS   = 'horizontal_icons';
    const MENU_VERTICAL_SIDEBAR   = 'vertical_sidebar';

    const SIDEBAR_LEFT  = 'left';
    const SIDEBAR_RIGHT = 'right';

    /**
     * Liefert alle registrierten Menüstile inklusive Plugin-Erweiterungen.
     * Filter-Hook: theme_register_menu_types
     * @param array $data
     * @return array
     */
    public static function getMenuTypes(array $data = []): array {
        $defaultTypes = [
            self::MENU_HORIZONTAL_MINIMAL => [
                'id' => self::MENU_HORIZONTAL_MINIMAL,
                'title' => 'Waagerecht Minimal (Standard)',
                'description' => 'Schlanke Kopfzeilen-Navigation mit Logo und Text-Links.',
                'badge' => 'Standard',
                'icon' => 'view_stream'
            ],
            self::MENU_HORIZONTAL_ICONS => [
                'id' => self::MENU_HORIZONTAL_ICONS,
                'title' => 'Waagerecht mit Icons / Symbolen',
                'description' => 'Horizontale Menüleiste mit passenden Material-Icons vor jedem Menüpunkt für intuitive visuelle Orientierung.',
                'badge' => 'Visuell',
                'icon' => 'view_module'
            ],
            self::MENU_VERTICAL_SIDEBAR => [
                'id' => self::MENU_VERTICAL_SIDEBAR,
                'title' => 'Senkrecht (Sidebar-Navigation)',
                'description' => 'Vertikale Seitenleiste (wahlweise links oder rechts fixiert) mit Branding oben, Links im Zentrum und mobiler Drawer-Einklappung.',
                'badge' => 'Modern',
                'icon' => 'view_sidebar'
            ]
        ];

        if (class_exists('PluginManager')) {
            return PluginManager::applyFilters('theme_register_menu_types', $defaultTypes, $data);
        }

        return $defaultTypes;
    }

    /**
     * Liefert den aktuellen Menüstil.
     * @param array $data
     * @return string
     */
    public static function getActiveMenuStyle(array $data): string {
        $style = $data['settings']['menu_style'] ?? self::MENU_HORIZONTAL_MINIMAL;
        $available = self::getMenuTypes($data);
        if (!isset($available[$style])) {
            $style = self::MENU_HORIZONTAL_MINIMAL;
        }
        return $style;
    }

    /**
     * Liefert die Ausrichtung der Sidebar (left oder right).
     * @param array $data
     * @return string
     */
    public static function getSidebarPosition(array $data): string {
        $pos = $data['settings']['sidebar_position'] ?? self::SIDEBAR_LEFT;
        return in_array($pos, [self::SIDEBAR_LEFT, self::SIDEBAR_RIGHT], true) ? $pos : self::SIDEBAR_LEFT;
    }

    /**
     * Liefert alle registrierten Page-Layouts / Templates inklusive Plugin-Erweiterungen.
     * Filter-Hook: theme_register_layouts
     * @param array $data
     * @return array
     */
    public static function getAvailableLayouts(array $data = []): array {
        $defaultLayouts = [
            'default' => [
                'id' => 'default',
                'name' => 'Standard-Layout',
                'description' => 'Klassischer Seitenaufbau mit Header, zentriertem Inhaltscontainer und Footer.'
            ],
            'contained' => [
                'id' => 'contained',
                'name' => 'Kompakt / Zentriert',
                'description' => 'Schmales zentriertes Leselayout für redaktionelle Beiträge und Texte.'
            ],
            'landingpage' => [
                'id' => 'landingpage',
                'name' => 'Modulare Landingpage',
                'description' => 'Endlos-Scrollbare Sektionen-Kombination ohne Zwischenabstände.'
            ]
        ];

        if (class_exists('PluginManager')) {
            return PluginManager::applyFilters('theme_register_layouts', $defaultLayouts, $data);
        }

        return $defaultLayouts;
    }
}
