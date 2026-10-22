<?php
/**
 * ============================================================================
 * WeaveCMS Shop & E-Commerce – Plugin Einstiegspunkt
 * ============================================================================
 * Beinhaltet Hooks, Shortcodes, Admin-Aktionen und Frontend-Sektionen.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/ShopManager.php';

// -----------------------------------------------------------------------------
// 1. Admin POST-Aktionen für Produkte & Bestellungen abfangen
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Neues Produkt hinzufügen
    if ($action === 'shop_add_product') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            ShopManager::addProduct($_POST);
            header('Location: ?tab=tab-plugins&plugin_settings=shop&shop_subtab=products&msg=product_added#tab-plugins');
            exit;
        }
    }

    // Produkt bearbeiten
    if ($action === 'shop_edit_product') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $id = trim($_POST['product_id'] ?? '');
            if (!empty($id)) {
                ShopManager::updateProduct($id, $_POST);
            }
            header('Location: ?tab=tab-plugins&plugin_settings=shop&shop_subtab=products&msg=product_updated#tab-plugins');
            exit;
        }
    }

    // Produkt löschen
    if ($action === 'shop_delete_product') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $id = trim($_POST['product_id'] ?? '');
            if (!empty($id)) {
                ShopManager::deleteProduct($id);
            }
            header('Location: ?tab=tab-plugins&plugin_settings=shop&shop_subtab=products&msg=product_deleted#tab-plugins');
            exit;
        }
    }

    // Bestellstatus aktualisieren
    if ($action === 'shop_update_order_status') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $orderId = trim($_POST['order_id'] ?? '');
            $newStatus = trim($_POST['status'] ?? '');
            if (!empty($orderId) && !empty($newStatus)) {
                ShopManager::updateOrderStatus($orderId, $newStatus);
            }
            header('Location: ?tab=tab-plugins&plugin_settings=shop&shop_subtab=orders&msg=status_updated#tab-plugins');
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Einstellungsansicht für den Adminbereich registrieren
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_shop', function($data, $csrf_token) {
    $config = PluginManager::getPluginConfig('shop', $data);
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 3. Shortcode-Filter für Content: [shop] oder [shop category="..."]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[shop') === false) {
        return $content;
    }

    $config = PluginManager::getPluginConfig('shop', $data);

    return preg_replace_callback('/\[shop(?:\s+([^\]]+))?\]/i', function($matches) use ($config) {
        $rawAttrs = $matches[1] ?? '';
        $params = [];
        if (!empty($rawAttrs)) {
            preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $rawAttrs, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $am) {
                $params[$am[1]] = $am[2];
            }
        }

        ob_start();
        require __DIR__ . '/views/shop_section.php';
        return ob_get_clean();
    }, $content);
});

// -----------------------------------------------------------------------------
// 4. Eigene Sektion für den Sektionen-Manager & das Frontend registrieren
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('shop', $data)) {
        return $sections;
    }
    $config = PluginManager::getPluginConfig('shop', $data);
    $isEnabled = !empty($config['section_enabled']);
    $sections[] = [
        'id'        => 'shop',
        'type'      => 'shop',
        'title'     => !empty($config['section_title']) ? $config['section_title'] : 'Unser Webshop & Produkte',
        'nav_label' => 'Shop',
        'visible'   => $isEnabled,
        'in_nav'    => true,
        'plugin'    => 'shop',
        'can_delete'=> false
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('shop', $data)) {
        return $sections;
    }
    $config = PluginManager::getPluginConfig('shop', $data);
    if (!empty($config['section_enabled'])) {
        $hasShop = false;
        foreach ($sections as $s) {
            if (($s['id'] ?? '') === 'shop') {
                $hasShop = true;
                break;
            }
        }
        if (!$hasShop) {
            $sections[] = [
                'id'        => 'shop',
                'type'      => 'shop',
                'title'     => $config['section_title'] ?? 'Unser Webshop & Produkte',
                'nav_label' => 'Shop',
                'visible'   => true,
                'in_nav'    => true,
                'plugin'    => 'shop'
            ];
        }
    }
    return $sections;
});

PluginManager::addAction('plugin_config_saved_shop', function($config, &$data) {
    if (class_exists('SectionManager')) {
        SectionManager::ensureSections($data);
        $sectionEnabled = !empty($config['section_enabled']);
        $found = false;
        foreach ($data['sections'] as &$sec) {
            if (($sec['id'] ?? '') === 'shop') {
                $sec['visible'] = $sectionEnabled;
                if (!empty($config['section_title'])) {
                    $sec['title'] = $config['section_title'];
                }
                $found = true;
                break;
            }
        }
        if (!$found && $sectionEnabled) {
            $data['sections'][] = [
                'id'        => 'shop',
                'type'      => 'shop',
                'title'     => !empty($config['section_title']) ? $config['section_title'] : 'Unser Webshop & Produkte',
                'nav_label' => 'Shop',
                'visible'   => true,
                'in_nav'    => true,
                'plugin'    => 'shop',
                'can_delete'=> false
            ];
        }
    }
});

PluginManager::addAction('frontend_render_custom_section', function($sectionKey, $data) {
    if ($sectionKey !== 'shop') {
        return;
    }

    $config = PluginManager::getPluginConfig('shop', $data);
    if (empty($config['section_enabled'])) {
        return;
    }

    $params = [
        'title'    => $config['section_title'] ?? 'Unser Webshop & Produkte',
        'subtitle' => $config['section_subtitle'] ?? 'Handgefertigte Unikate, kreative DIY-Sets und Zubehör direkt online bestellen.'
    ];

    require __DIR__ . '/views/shop_section.php';
});

// -----------------------------------------------------------------------------
// 5. Validierung der Konfigurationsdaten vor dem Speichern
// -----------------------------------------------------------------------------
PluginManager::addFilter('plugin_validate_config_shop', function($submitted, $data) {
    $existing = PluginManager::getPluginConfig('shop', $data);
    $cleaned = $existing;
    $subtab = $submitted['active_subtab'] ?? '';

    // Zahlungsarten (Subtab: payments)
    if ($subtab === 'payments' || isset($submitted['payment_credit_card']) || isset($submitted['payment_paypal']) || isset($submitted['payment_wero'])) {
        $cleaned['payment_credit_card'] = [
            'enabled' => !empty($submitted['payment_credit_card']['enabled']),
            'sandbox' => !empty($submitted['payment_credit_card']['sandbox'])
        ];
        $cleaned['payment_paypal'] = [
            'enabled'   => !empty($submitted['payment_paypal']['enabled']),
            'sandbox'   => !empty($submitted['payment_paypal']['sandbox']),
            'client_id' => trim($submitted['payment_paypal']['client_id'] ?? '')
        ];
        $cleaned['payment_wero'] = [
            'enabled'       => !empty($submitted['payment_wero']['enabled']),
            'sandbox'       => !empty($submitted['payment_wero']['sandbox']),
            'merchant_name' => trim($submitted['payment_wero']['merchant_name'] ?? 'WeaveCMS Shop'),
            'iban'          => preg_replace('/\s+/', '', strtoupper($submitted['payment_wero']['iban'] ?? '')),
            'bic'           => preg_replace('/\s+/', '', strtoupper($submitted['payment_wero']['bic'] ?? ''))
        ];
    }

    // Allgemeine Shop-Einstellungen & Startseiten-Integration (Subtab: settings)
    if ($subtab === 'settings' || isset($submitted['currency']) || isset($submitted['section_title']) || isset($submitted['section_enabled'])) {
        if (isset($submitted['currency'])) {
            $cleaned['currency'] = in_array($submitted['currency'], ['EUR', 'CHF', 'USD'], true) ? $submitted['currency'] : 'EUR';
        }
        if (isset($submitted['shipping_cost'])) {
            $cleaned['shipping_cost'] = max(0.0, round((float)str_replace(',', '.', (string)$submitted['shipping_cost']), 2));
        }
        if (isset($submitted['free_shipping_min'])) {
            $cleaned['free_shipping_min'] = max(0.0, round((float)str_replace(',', '.', (string)$submitted['free_shipping_min']), 2));
        }
        // Checkboxen zuverlässig speichern (auch wenn unchecked)
        $cleaned['small_business'] = !empty($submitted['small_business']);
        $cleaned['section_enabled'] = !empty($submitted['section_enabled']);

        if (isset($submitted['section_title'])) {
            $cleaned['section_title'] = trim($submitted['section_title']);
        }
        if (isset($submitted['section_subtitle'])) {
            $cleaned['section_subtitle'] = trim($submitted['section_subtitle']);
        }
    }

    return $cleaned;
});
