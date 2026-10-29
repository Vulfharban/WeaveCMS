<?php
/**
 * ============================================================================
 * WeaveCMS – Headless REST-API v1 Entry Point (Phase 31)
 * ============================================================================
 * Strukturierte Lese-Endpunkte für Seiten, Blogartikel, Produkte und Kurse.
 * Authentifizierung via Bearer-Token (ApiTokenManager).
 *
 * Endpunkte:
 * - GET /api/v1/pages    (oder ?endpoint=pages)
 * - GET /api/v1/posts    (oder ?endpoint=posts)
 * - GET /api/v1/products (oder ?endpoint=products)
 * - GET /api/v1/courses  (oder ?endpoint=courses)
 * ============================================================================
 *
 * @package WeaveCMS
 * @subpackage API
 */

// Fehler nicht direkt ausgeben, um sauberes JSON zu gewährleisten
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');

// Preflight CORS-Handling
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Nur GET-Anfragen für Lese-Endpunkte
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error'   => 'Method Not Allowed. Nur GET-Anfragen werden unterstützt.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/libs/php/ApiTokenManager.php';
require_once dirname(__DIR__, 2) . '/libs/php/SectionManager.php';

// Datenbasis laden
$dataFile = dirname(__DIR__, 2) . '/data.json';
$data = load_data($dataFile);

// -----------------------------------------------------------------------------
// 1. Bearer-Token ermitteln
// -----------------------------------------------------------------------------
$rawAuthHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $rawAuthHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $rawAuthHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $rawAuthHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $rawAuthHeader = $headers['authorization'];
    }
}

// Fallback: Query-Parameter ?api_token=...
$apiToken = $rawAuthHeader ?: ($_GET['api_token'] ?? null);

// -----------------------------------------------------------------------------
// 2. Endpunkt-Routing auflösen
// -----------------------------------------------------------------------------
$endpoint = trim($_GET['endpoint'] ?? '');
// Falls z. B. /api/v1/pages über URL-Pfad übergeben wurde
if (empty($endpoint) && !empty($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/api/v1/([^/?]+)#', $path, $m)) {
        $endpoint = trim($m[1]);
    }
}

// Sub-Ressource z. B. /api/v1/pages/meine-seite
$subParam = '';
if (strpos($endpoint, '/') !== false) {
    $parts = explode('/', $endpoint, 2);
    $endpoint = $parts[0];
    $subParam = $parts[1] ?? '';
}

// -----------------------------------------------------------------------------
// 3. API-Endpunkte behandeln
// -----------------------------------------------------------------------------
switch ($endpoint) {
    // =========================================================================
    // SEITEN: /api/v1/pages
    // =========================================================================
    case 'pages':
        $auth = ApiTokenManager::validateToken($apiToken, 'pages:read');
        if (!$auth['valid']) {
            http_response_code(empty($apiToken) ? 401 : 403);
            echo json_encode(['success' => false, 'error' => $auth['error']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pages = $data['pages'] ?? [];
        $slugFilter = trim($_GET['slug'] ?? $subParam);
        $typeFilter = trim($_GET['type'] ?? '');

        $resultList = [];
        foreach ($pages as $p) {
            // Status-Filter: Standardmäßig nur sichtbare/aktive Seiten
            if (!empty($_GET['all']) && $_GET['all'] === 'true') {
                // alle
            } else {
                if (isset($p['status']) && $p['status'] !== 'published') {
                    continue;
                }
            }

            if (!empty($slugFilter) && strcasecmp($p['slug'] ?? '', $slugFilter) !== 0 && ($p['id'] ?? '') !== $slugFilter) {
                continue;
            }
            if (!empty($typeFilter) && ($p['type'] ?? '') !== $typeFilter) {
                continue;
            }

            $resultList[] = [
                'id'               => $p['id'] ?? '',
                'title'            => $p['title'] ?? '',
                'slug'             => $p['slug'] ?? '',
                'type'             => $p['type'] ?? 'standard',
                'status'           => $p['status'] ?? 'published',
                'meta_description' => $p['meta_description'] ?? '',
                'content'          => $p['content'] ?? '',
                'sections'         => $p['sections'] ?? [],
                'updated_at'       => $p['updated_at'] ?? null,
                'created_at'       => $p['created_at'] ?? null
            ];
        }

        // Falls Einzelseite angefragt wurde
        if (!empty($slugFilter)) {
            if (empty($resultList)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Seite '{$slugFilter}' nicht gefunden."], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode([
                'success' => true,
                'page'    => $resultList[0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'endpoint' => 'pages',
            'meta'     => ['count' => count($resultList), 'timestamp' => date('c')],
            'data'     => $resultList
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // =========================================================================
    // BLOG: /api/v1/posts
    // =========================================================================
    case 'posts':
        $auth = ApiTokenManager::validateToken($apiToken, 'posts:read');
        if (!$auth['valid']) {
            http_response_code(empty($apiToken) ? 401 : 403);
            echo json_encode(['success' => false, 'error' => $auth['error']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $posts = $data['blog'] ?? [];
        $slugFilter = trim($_GET['slug'] ?? $subParam);
        $statusFilter = trim($_GET['status'] ?? 'published');

        $resultList = [];
        foreach ($posts as $bp) {
            $status = $bp['status'] ?? 'published';
            if ($statusFilter !== 'all' && $status !== $statusFilter) {
                continue;
            }

            if (!empty($slugFilter) && strcasecmp($bp['slug'] ?? '', $slugFilter) !== 0 && ($bp['id'] ?? '') !== $slugFilter) {
                continue;
            }

            $resultList[] = [
                'id'         => $bp['id'] ?? '',
                'title'      => $bp['title'] ?? '',
                'slug'       => $bp['slug'] ?? '',
                'date'       => $bp['date'] ?? '',
                'author'     => $bp['author'] ?? '',
                'status'     => $status,
                'tags'       => is_array($bp['tags'] ?? null) ? $bp['tags'] : array_filter(array_map('trim', explode(',', $bp['tags'] ?? ''))),
                'teaser'     => $bp['teaser'] ?? '',
                'content'    => $bp['content'] ?? '',
                'image'      => $bp['image'] ?? '',
                'custom_fields' => $bp['custom_fields'] ?? []
            ];
        }

        if (!empty($slugFilter)) {
            if (empty($resultList)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Blog-Artikel '{$slugFilter}' nicht gefunden."], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode([
                'success' => true,
                'post'    => $resultList[0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'endpoint' => 'posts',
            'meta'     => ['count' => count($resultList), 'timestamp' => date('c')],
            'data'     => $resultList
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // =========================================================================
    // PRODUKTE: /api/v1/products
    // =========================================================================
    case 'products':
        $auth = ApiTokenManager::validateToken($apiToken, 'products:read');
        if (!$auth['valid']) {
            http_response_code(empty($apiToken) ? 401 : 403);
            echo json_encode(['success' => false, 'error' => $auth['error']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $products = [];
        $shopManagerFile = dirname(__DIR__, 2) . '/plugins/shop/ShopManager.php';
        if (file_exists($shopManagerFile)) {
            require_once $shopManagerFile;
            $products = ShopManager::getProducts();
        } elseif (!empty($data['shop']['products'])) {
            $products = $data['shop']['products'];
        }

        $idFilter = trim($_GET['id'] ?? $subParam);
        $skuFilter = trim($_GET['sku'] ?? '');

        $resultList = [];
        foreach ($products as $p) {
            if (!empty($idFilter) && ($p['id'] ?? '') !== $idFilter) {
                continue;
            }
            if (!empty($skuFilter) && strcasecmp($p['sku'] ?? '', $skuFilter) !== 0) {
                continue;
            }
            $resultList[] = $p;
        }

        if (!empty($idFilter) || !empty($skuFilter)) {
            if (empty($resultList)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Produkt nicht gefunden."], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode([
                'success' => true,
                'product' => $resultList[0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'endpoint' => 'products',
            'meta'     => ['count' => count($resultList), 'timestamp' => date('c')],
            'data'     => $resultList
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // =========================================================================
    // EVENTS / KURSE: /api/v1/courses
    // =========================================================================
    case 'courses':
        $auth = ApiTokenManager::validateToken($apiToken, 'courses:read');
        if (!$auth['valid']) {
            http_response_code(empty($apiToken) ? 401 : 403);
            echo json_encode(['success' => false, 'error' => $auth['error']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $courses = $data['courses'] ?? [];
        $idFilter = trim($_GET['id'] ?? $subParam);

        $resultList = [];
        foreach ($courses as $c) {
            if (!empty($idFilter) && ($c['id'] ?? '') !== $idFilter) {
                continue;
            }
            $resultList[] = $c;
        }

        if (!empty($idFilter)) {
            if (empty($resultList)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Kurs/Event '{$idFilter}' nicht gefunden."], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode([
                'success' => true,
                'course'  => $resultList[0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'endpoint' => 'courses',
            'meta'     => ['count' => count($resultList), 'timestamp' => date('c')],
            'data'     => $resultList
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // =========================================================================
    // API ROOT / ÜBERSICHT
    // =========================================================================
    case '':
    case 'info':
        echo json_encode([
            'name'        => 'WeaveCMS Headless REST-API',
            'version'     => 'v1.5.0',
            'status'      => 'online',
            'endpoints'   => [
                '/api/v1/pages'    => 'Seiten abfragen (erfordert Scope: pages:read)',
                '/api/v1/posts'    => 'Blog-Artikel abfragen (erfordert Scope: posts:read)',
                '/api/v1/products' => 'Shop-Produkte abfragen (erfordert Scope: products:read)',
                '/api/v1/courses'  => 'Events & Termine abfragen (erfordert Scope: courses:read)'
            ],
            'auth'        => 'Authorization: Bearer <wct_token>',
            'docs'        => 'https://github.com/Vulfharban/WeaveCMS'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => "Unbekannter Endpunkt '{$endpoint}'."
        ], JSON_UNESCAPED_UNICODE);
        exit;
}
