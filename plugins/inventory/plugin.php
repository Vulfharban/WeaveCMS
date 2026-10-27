<?php
/**
 * ============================================================================
 * WeaveCMS Lager- & Artikelverwaltung – Plugin Einstiegspunkt
 * ============================================================================
 * Verwaltet Artikel, Bestände, Wareneingänge, Inventur und das Audit-Protokoll.
 * Vollständig kompatibel und gekoppelt mit dem WeaveCMS Shop-Plugin.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/InventoryManager.php';

// -----------------------------------------------------------------------------
// 1. Admin-Aktionen für Artikel- & Lagerverwaltung abfangen
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in'])) {

    // CSV-Export sofort ausliefern
    if (isset($_GET['action']) && $_GET['action'] === 'inv_export_csv') {
        $csv = InventoryManager::exportCsv();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="weavecms_lagerbestand_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit;
    }

    // JSON-Export Lagerbestand
    if (isset($_GET['action']) && $_GET['action'] === 'inv_export_json') {
        $json = InventoryManager::exportArticlesJson();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="weavecms_lagerbestand_' . date('Y-m-d') . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $json;
        exit;
    }

    // JSON-Export Lagerbewegungs-Protokoll (Audit Log)
    if (isset($_GET['action']) && $_GET['action'] === 'inv_export_movements_json') {
        $json = InventoryManager::exportMovementsJson();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="weavecms_lagerbewegungen_' . date('Y-m-d') . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $json;
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        // JSON-Import für Artikel & Bestände
        if ($action === 'inv_import_json') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $rawJson = '';
                if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
                    $rawJson = (string)@file_get_contents($_FILES['import_file']['tmp_name']);
                } elseif (!empty($_POST['json_text'])) {
                    $rawJson = trim($_POST['json_text']);
                }
                $mode = ($_POST['import_mode'] ?? 'merge') === 'replace' ? 'replace' : 'merge';
                $user = $_SESSION['admin_username'] ?? 'admin';
                $res = InventoryManager::importArticlesJson($rawJson, $mode, $user);
                $msg = $res['success'] ? 'inventory_imported' : 'import_error';
                header("Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings&msg={$msg}&imported=" . (int)$res['imported'] . "&updated=" . (int)$res['updated'] . "#tab-plugins");
                exit;
            }
        }

        // API-Key für Lagerverwaltung neu generieren
        if ($action === 'inv_generate_api_key') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $dataFile = dirname(__DIR__, 2) . '/data.json';
                $d = load_data($dataFile);
                $c = PluginManager::getPluginConfig('inventory', $d);
                $c['api_key'] = InventoryManager::generateApiKey();
                $c['api_enabled'] = true;
                PluginManager::savePluginConfig('inventory', $c, $d);
                save_data($dataFile, $d);
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings&msg=api_key_generated#tab-plugins');
                exit;
            }
        }

        // A: Artikel anlegen oder aktualisieren
        if ($action === 'inv_save_article') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $user = $_SESSION['admin_username'] ?? 'admin';
                InventoryManager::saveArticle($_POST, $user);
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles&msg=article_saved#tab-plugins');
                exit;
            }
        }

        // B: Artikel löschen
        if ($action === 'inv_delete_article') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $id = trim($_POST['article_id'] ?? '');
                $user = $_SESSION['admin_username'] ?? 'admin';
                if (!empty($id)) {
                    InventoryManager::deleteArticle($id, $user);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles&msg=article_deleted#tab-plugins');
                exit;
            }
        }

        // C: Wareneingang / Zubuchung verbuchen
        if ($action === 'inv_receive_stock') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $id = trim($_POST['article_id'] ?? '');
                $qty = max(1, (int)($_POST['quantity'] ?? 1));
                $costPrice = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? (float)str_replace(',', '.', $_POST['cost_price']) : null;
                $deliveryNote = trim($_POST['delivery_note'] ?? '');
                $supplier = trim($_POST['supplier'] ?? '');
                $note = trim($_POST['note'] ?? '');
                $user = $_SESSION['admin_username'] ?? 'admin';

                if (!empty($id)) {
                    InventoryManager::receiveStock($id, $qty, $costPrice, $deliveryNote, $supplier, $note, $user);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=receipt&msg=stock_received#tab-plugins');
                exit;
            }
        }

        // D: Einzel-Bestandskorrektur
        if ($action === 'inv_adjust_stock') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $id = trim($_POST['article_id'] ?? '');
                $newStock = max(0, (int)($_POST['new_stock'] ?? 0));
                $reason = trim($_POST['reason'] ?? 'Manuelle Korrektur');
                $user = $_SESSION['admin_username'] ?? 'admin';

                if (!empty($id)) {
                    InventoryManager::adjustStock($id, $newStock, $reason, $user);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles&msg=stock_adjusted#tab-plugins');
                exit;
            }
        }

        // E: Inventur-Sammelbuchung (Bestandsabgleich)
        if ($action === 'inv_batch_stocktaking') {
            if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $counts = $_POST['actual_stock'] ?? [];
                $user = $_SESSION['admin_username'] ?? 'admin';
                $notes = trim($_POST['inventory_note'] ?? 'Stichtags-Inventur');

                if (is_array($counts)) {
                    foreach ($counts as $id => $val) {
                        if ($val !== '' && is_numeric($val)) {
                            InventoryManager::adjustStock($id, (int)$val, $notes, $user);
                        }
                    }
                }
                header('Location: ?tab=tab-plugins&plugin_settings=inventory&inv_subtab=stocktaking&msg=inventory_completed#tab-plugins');
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Automatischer Hook: Bei Shop-Bestellung Warenausgang im Log protokollieren
// -----------------------------------------------------------------------------
PluginManager::addAction('shop_order_created', function($order) {
    if (empty($order['items']) || !is_array($order['items'])) {
        return;
    }

    $ref = $order['order_number'] ?? ($order['order_id'] ?? 'SHOP-BESTELLUNG');
    $customerName = is_array($order['customer'] ?? null)
        ? trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ($order['customer']['name'] ?? '')))
        : 'Kunde';
    if (empty($customerName)) {
        $customerName = 'Kunde';
    }
    $method = strtoupper($order['payment_method'] ?? 'Online');

    foreach ($order['items'] as $item) {
        $id = $item['id'] ?? '';
        $qty = (int)($item['qty'] ?? ($item['quantity'] ?? 1));
        if (!empty($id) && $qty > 0) {
            // Loggt die Bestandsreduzierung revisionssicher im Lager-Audit-Trail
            InventoryManager::logMovement(
                $id,
                'shop_sale',
                -$qty,
                $ref,
                "Webshop-Verkauf an {$customerName} (Zahlung via {$method})",
                'Webshop-Engine'
            );

            // Prüfung auf Mindestbestand & E-Mail-Warnung
            InventoryManager::checkAndNotifyLowStock($id);
        }
    }
});

// -----------------------------------------------------------------------------
// 3. Einstellungsansicht für den Adminbereich registrieren
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_inventory', function($data, $csrf_token) {
    $config = PluginManager::getPluginConfig('inventory', $data);
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 4. Validierung der Konfigurationsdaten
// -----------------------------------------------------------------------------
PluginManager::addFilter('plugin_validate_config_inventory', function($submitted, $data) {
    $existing = PluginManager::getPluginConfig('inventory', $data);
    $cleaned = $existing;

    if (isset($submitted['default_min_stock'])) {
        $cleaned['default_min_stock'] = max(0, (int)$submitted['default_min_stock']);
    }
    if (isset($submitted['default_location'])) {
        $cleaned['default_location'] = trim($submitted['default_location']);
    }
    if (isset($submitted['locations_list'])) {
        $raw = explode(',', (string)$submitted['locations_list']);
        $cleaned['locations'] = array_values(array_filter(array_map('trim', $raw)));
    }
    $cleaned['notify_low_stock'] = !empty($submitted['notify_low_stock']);
    if (isset($submitted['notify_email'])) {
        $cleaned['notify_email'] = trim($submitted['notify_email']);
    }
    if (isset($submitted['api_enabled'])) {
        $cleaned['api_enabled'] = !empty($submitted['api_enabled']);
    }
    if (!empty($submitted['api_key']) && !str_starts_with(trim($submitted['api_key']), 'Noch kein')) {
        $cleaned['api_key'] = trim($submitted['api_key']);
    } elseif (!empty($existing['api_key']) && !str_starts_with(trim($existing['api_key']), 'Noch kein')) {
        $cleaned['api_key'] = $existing['api_key'];
    } elseif (!empty($cleaned['api_enabled'])) {
        $cleaned['api_key'] = InventoryManager::generateApiKey();
    }

    return $cleaned;
});
