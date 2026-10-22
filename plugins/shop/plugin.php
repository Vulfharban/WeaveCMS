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
// 4. Eigene Sektion für das Frontend registrieren (falls im Plugin aktiviert)
// -----------------------------------------------------------------------------
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

    // Zahlungsarten
    if (isset($submitted['payment_credit_card'])) {
        $cleaned['payment_credit_card'] = [
            'enabled' => !empty($submitted['payment_credit_card']['enabled']),
            'sandbox' => !empty($submitted['payment_credit_card']['sandbox'])
        ];
    }
    if (isset($submitted['payment_paypal'])) {
        $cleaned['payment_paypal'] = [
            'enabled'   => !empty($submitted['payment_paypal']['enabled']),
            'sandbox'   => !empty($submitted['payment_paypal']['sandbox']),
            'client_id' => trim($submitted['payment_paypal']['client_id'] ?? '')
        ];
    }
    if (isset($submitted['payment_wero'])) {
        $cleaned['payment_wero'] = [
            'enabled'       => !empty($submitted['payment_wero']['enabled']),
            'sandbox'       => !empty($submitted['payment_wero']['sandbox']),
            'merchant_name' => trim($submitted['payment_wero']['merchant_name'] ?? 'WeaveCMS Shop'),
            'iban'          => preg_replace('/\s+/', '', strtoupper($submitted['payment_wero']['iban'] ?? '')),
            'bic'           => preg_replace('/\s+/', '', strtoupper($submitted['payment_wero']['bic'] ?? ''))
        ];
    }

    // Allgemeine Shop-Einstellungen
    if (isset($submitted['currency'])) {
        $cleaned['currency'] = in_array($submitted['currency'], ['EUR', 'CHF', 'USD'], true) ? $submitted['currency'] : 'EUR';
    }
    if (isset($submitted['shipping_cost'])) {
        $cleaned['shipping_cost'] = max(0.0, round((float)str_replace(',', '.', (string)$submitted['shipping_cost']), 2));
    }
    if (isset($submitted['free_shipping_min'])) {
        $cleaned['free_shipping_min'] = max(0.0, round((float)str_replace(',', '.', (string)$submitted['free_shipping_min']), 2));
    }
    if (isset($submitted['small_business'])) {
        $cleaned['small_business'] = !empty($submitted['small_business']);
    }

    if (isset($submitted['section_enabled'])) {
        $cleaned['section_enabled'] = !empty($submitted['section_enabled']);
    }
    if (isset($submitted['section_title'])) {
        $cleaned['section_title'] = trim($submitted['section_title']);
    }
    if (isset($submitted['section_subtitle'])) {
        $cleaned['section_subtitle'] = trim($submitted['section_subtitle']);
    }

    return $cleaned;
});
