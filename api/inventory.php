<?php
/**
 * ============================================================================
 * WeaveCMS Lager- & Artikelverwaltung (Inventory) – REST API Endpoint
 * ============================================================================
 * Ermöglicht die tokenbasierte Anbindung von Kassensystemen (POS),
 * Warenwirtschaftssystemen (WaWi), Barcode-Scanner-Apps und externen Diensten.
 *
 * Authentifizierung:
 *   - Header: 'Authorization: Bearer <API_KEY>'
 *   - Header: 'X-API-KEY: <API_KEY>'
 *   - Query-Parameter: '?api_key=<API_KEY>'
 *   - Alternativ: Aktive Admin-Sitzung im Browser
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');

// WeaveCMS Umgebung laden
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/plugins/inventory/InventoryManager.php';

$dataFile = dirname(__DIR__) . '/data.json';
$data = load_data($dataFile);
$invConfig = PluginManager::getPluginConfig('inventory', $data, [
    'api_enabled' => true,
    'api_key'     => ''
]);

// 1. Authentifizierung prüfen
$token = '';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (empty($authHeader) && function_exists('getallheaders')) {
    $allHeaders = getallheaders();
    foreach ($allHeaders as $hKey => $hVal) {
        if (strcasecmp($hKey, 'Authorization') === 0) {
            $authHeader = $hVal;
        } elseif (strcasecmp($hKey, 'X-API-KEY') === 0 && empty($_SERVER['HTTP_X_API_KEY'])) {
            $_SERVER['HTTP_X_API_KEY'] = $hVal;
        }
    }
}
if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
} elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $token = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (!empty($_REQUEST['api_key'])) {
    $token = trim((string)$_REQUEST['api_key']);
}

$isAdminSession = !empty($_SESSION['admin_logged_in']);
$isValidApiKey = !empty($invConfig['api_enabled']) && InventoryManager::verifyApiKey($token, $invConfig);

if (!$isAdminSession && !$isValidApiKey) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized',
        'message' => 'Ungültiger oder fehlender API-Schlüssel für die Lagerverwaltung.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Request parsen
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'articles';
$rawBody = file_get_contents('php://input');
$jsonInput = json_decode($rawBody, true) ?: [];

// 3. API-Routing
switch ($action) {

    // -------------------------------------------------------------------------
    // A: Alle Artikel abrufen (mit optionalem Filter nach Kategorie/Suche)
    // -------------------------------------------------------------------------
    case 'articles':
    case 'list_articles':
        $articles = InventoryManager::getArticles();
        $category = trim($_GET['category'] ?? '');
        $search = trim($_GET['q'] ?? '');

        if (!empty($category)) {
            $articles = array_values(array_filter($articles, fn($a) => ($a['category'] ?? '') === $category));
        }
        if (!empty($search)) {
            $sLower = mb_strtolower($search);
            $articles = array_values(array_filter($articles, function($a) use ($sLower) {
                return (strpos(mb_strtolower($a['title'] ?? ''), $sLower) !== false) ||
                       (strpos(mb_strtolower($a['sku'] ?? ''), $sLower) !== false) ||
                       (strpos(mb_strtolower($a['barcode'] ?? ''), $sLower) !== false);
            }));
        }

        echo json_encode([
            'success' => true,
            'count'   => count($articles),
            'articles'=> $articles
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // B: Einzelnen Artikel anhand ID, SKU oder Barcode abrufen
    // -------------------------------------------------------------------------
    case 'get_article':
        $id = trim($_GET['id'] ?? $jsonInput['id'] ?? '');
        $sku = trim($_GET['sku'] ?? $jsonInput['sku'] ?? '');
        $barcode = trim($_GET['barcode'] ?? $jsonInput['barcode'] ?? '');

        $articles = InventoryManager::getArticles();
        $found = null;
        foreach ($articles as $a) {
            if (!empty($id) && ($a['id'] ?? '') === $id) { $found = $a; break; }
            if (!empty($sku) && strcasecmp($a['sku'] ?? '', $sku) === 0) { $found = $a; break; }
            if (!empty($barcode) && ($a['barcode'] ?? '') === $barcode) { $found = $a; break; }
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Artikel nicht gefunden.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => true, 'article' => $found], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // C: Schneller Bestandsabgleich (z. B. Vor-Ort-Kasse, Barcode-Scanner)
    // -------------------------------------------------------------------------
    case 'stock_adjust':
        $input = !empty($jsonInput) ? $jsonInput : array_merge($_GET, $_POST);
        $id = trim($input['article_id'] ?? $input['id'] ?? '');
        $sku = trim($input['sku'] ?? '');
        $change = isset($input['change']) ? (int)$input['change'] : null;
        $newStock = isset($input['new_stock']) ? (int)$input['new_stock'] : null;
        $reason = trim($input['reason'] ?? 'REST-API Bestandsänderung');
        $user = trim($input['user'] ?? 'REST-API');

        $articles = InventoryManager::getArticles();
        $targetId = '';
        $currentStock = 0;
        foreach ($articles as $a) {
            if ((!empty($id) && ($a['id'] ?? '') === $id) || (!empty($sku) && strcasecmp($a['sku'] ?? '', $sku) === 0)) {
                $targetId = $a['id'];
                $currentStock = (int)($a['stock'] ?? 0);
                break;
            }
        }

        if (empty($targetId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Zielartikel für Bestandsabgleich nicht gefunden.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $calculatedNewStock = ($newStock !== null) ? max(0, $newStock) : max(0, $currentStock + ($change ?? 0));
        $ok = InventoryManager::adjustStock($targetId, $calculatedNewStock, $reason, $user);

        echo json_encode([
            'success'   => $ok,
            'article_id'=> $targetId,
            'old_stock' => $currentStock,
            'new_stock' => $calculatedNewStock,
            'diff'      => ($calculatedNewStock - $currentStock),
            'message'   => "Bestand erfolgreich auf {$calculatedNewStock} aktualisiert."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // D: Wareneingang buchen
    // -------------------------------------------------------------------------
    case 'receive_stock':
        $input = !empty($jsonInput) ? $jsonInput : array_merge($_GET, $_POST);
        $id = trim($input['article_id'] ?? $input['id'] ?? '');
        $qty = max(1, (int)($input['quantity'] ?? $input['qty'] ?? 1));
        $costPrice = isset($input['cost_price']) ? (float)$input['cost_price'] : null;
        $deliveryNote = trim($input['delivery_note'] ?? '');
        $supplier = trim($input['supplier'] ?? '');
        $note = trim($input['note'] ?? 'REST-API Wareneingang');
        $user = trim($input['user'] ?? 'REST-API');

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'article_id ist erforderlich.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = InventoryManager::receiveStock($id, $qty, $costPrice, $deliveryNote, $supplier, $note, $user);
        echo json_encode([
            'success'   => $ok,
            'message'   => $ok ? "Wareneingang von {$qty} Stück erfolgreich verbucht." : "Wareneingang konnte nicht verbucht werden (Artikel nicht gefunden)."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // E: Mindestbestand-Warnungen abfragen
    // -------------------------------------------------------------------------
    case 'low_stock':
        $articles = InventoryManager::getArticles();
        $low = array_values(array_filter($articles, fn($a) => !empty($a['is_low_stock'])));
        echo json_encode([
            'success'   => true,
            'count'     => count($low),
            'articles'  => $low
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // F: Audit-Trail / Lagerbewegungen abrufen
    // -------------------------------------------------------------------------
    case 'movements':
        $limit = max(1, min(1000, (int)($_GET['limit'] ?? 100)));
        $movements = InventoryManager::getMovements($limit);
        echo json_encode([
            'success'   => true,
            'count'     => count($movements),
            'movements' => $movements
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // -------------------------------------------------------------------------
    // G: JSON-Export abrufen
    // -------------------------------------------------------------------------
    case 'export_json':
        echo InventoryManager::exportArticlesJson();
        exit;

    // -------------------------------------------------------------------------
    // H: JSON-Import durchführen
    // -------------------------------------------------------------------------
    case 'import_json':
        $raw = !empty($rawBody) ? $rawBody : ($_POST['json_data'] ?? '');
        $mode = in_array($_POST['mode'] ?? ($jsonInput['mode'] ?? 'merge'), ['merge', 'replace'], true) ? ($_POST['mode'] ?? ($jsonInput['mode'] ?? 'merge')) : 'merge';
        $user = trim($_POST['user'] ?? ($jsonInput['user'] ?? 'REST-API'));

        $res = InventoryManager::importArticlesJson($raw, $mode, $user);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Unbekannte Aktion '{$action}'."], JSON_UNESCAPED_UNICODE);
        exit;
}
