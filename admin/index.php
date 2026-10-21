<?php
/**
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 * Dies gilt im Backend insbesondere für:
 * - Admin-Header & Abmelde-Menü
 * - Reiter-Navigation (.admin-nav) inklusive aller Reiter, Benennungen und Reihenfolgen
 * ============================================================================
 * 
 * WeaveCMS – Administration Hauptmodul
 * 
 * Orchestriert Authentifizierung (AuthManager), Aktions-Dispatching (AdminController)
 * und das Rendern der modularen Backend-Views.
 *
 * @package WeaveCMS\Admin
 */

require_once '../config.php';

// 1. Zentrale Datenbasis (data.json oder SQL) laden
$data_file = '../data.json';
$data = load_data($data_file, [
    'settings' => [
        'upload_dir' => 'images/uploads',
        'admin_password_hash' => '',
        'admin_pin_hash' => ''
    ],
    'texts' => [],
    'gallery' => [],
    'courses' => [],
    'past_courses' => []
]);

// 2. Abmeldung (Logout) verarbeiten
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthManager::logout();
    header('Location: ./');
    exit;
}

// 3. Login-Verifikation bei Formularübermittlung
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    $pin = $_POST['pin'] ?? '';
    if (AuthManager::login($password, $pin, $data)) {
        header('Location: ./');
        exit;
    } else {
        $login_error = 'Ungültiges Passwort oder PIN-Code. Bitte versuche es erneut.';
    }
}

// 4. Authentifizierungs-Check
if (!AuthManager::isLoggedIn($data)) {
    require __DIR__ . '/views/login.php';
    exit;
}

// 5. Download von Backups verarbeiten (nur autorisiert)
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $file = $_GET['file'] ?? '';
    BackupManager::downloadBackup($file);
    exit;
}

// 6. AJAX Action: test_db_migration (Live-Prüfung der MySQL-Verbindung im Adminbereich)
if (isset($_GET['action']) && $_GET['action'] === 'test_db_migration') {
    header('Content-Type: application/json');
    $res = StorageManager::testDbConnection([
        'host' => $_POST['host'] ?? '127.0.0.1',
        'port' => (int)($_POST['port'] ?? 3306),
        'database' => $_POST['database'] ?? '',
        'username' => $_POST['username'] ?? 'root',
        'password' => $_POST['password'] ?? '',
        'create_db' => !empty($_POST['create_db'])
    ]);
    echo json_encode($res);
    exit;
}

// 7. CSRF-Token für Sitzung generieren oder abrufen
$csrf_token = AuthManager::getCsrfToken();

// 8. Eingehende POST-Aktionen über AdminController verarbeiten
$controllerResult = AdminController::handlePost($data_file, $data, $csrf_token);
$success_msg = $controllerResult['success_msg'];
$error_msg = $controllerResult['error_msg'];
$active_tab = $controllerResult['active_tab'];

// 9. Galerie-Elemente im Arbeitsspeicher nach sort_order aufsteigend sortieren
usort($data['gallery'], function($a, $b) {
    return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
});

// 10. Basis-URL für dynamische Einladungslinks ermitteln (Protokoll + Host + Pfad)
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$site_url = $proto . '://' . $host . $base_path . '/';

// 11. Admin-Header Logo vorbereiten
$admin_logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_beach';
$admin_logo_color = $data['settings']['hero_logo_color'] ?? '#d97706';
$admin_logo_pos = $data['settings']['logo_position'] ?? 'header_before';
$admin_paw = LogoRenderer::render($admin_logo_motif, $admin_logo_color, '1.2em', 'header-paw-logo');

// 12. Galerie-Kategorien & Zähler für Admin vorbereiten
$admin_categories = $data['categories'] ?? ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
$cat_counts = [];
foreach ($admin_categories as $c) {
    $cat_counts[$c] = 0;
}
foreach ($data['gallery'] as $item) {
    $cat = $item['category'] ?? '';
    if (isset($cat_counts[$cat])) {
        $cat_counts[$cat]++;
    } else {
        $cat_counts[$cat] = ($cat_counts[$cat] ?? 0) + 1;
    }
}

// 13. SEO- & Metadaten für Admin vorbereiten
$cur_seo = $data['seo'] ?? [];
$cur_meta_title = $cur_seo['meta_title'] ?? '';
$cur_meta_desc = $cur_seo['meta_description'] ?? '';
$cur_meta_keywords = $cur_seo['meta_keywords'] ?? '';
$cur_robots = $cur_seo['robots'] ?? 'index, follow';
$cur_canonical = $cur_seo['canonical_url'] ?? '';
$cur_og_image = $cur_seo['og_image'] ?? '';

// 14. Postfach-Zähler vorbereiten
$inbox_entries = ContactInbox::all();
$inbox_new_count = ContactInbox::countNew();

// 15. Modulare Admin-Views rendern
require __DIR__ . '/views/header.php';
require __DIR__ . '/views/nav.php';
require __DIR__ . '/views/tab_texts.php';
require __DIR__ . '/views/tab_pages.php';
require __DIR__ . '/views/tab_blog.php';
require __DIR__ . '/views/tab_faqs.php';
require __DIR__ . '/views/tab_team.php';
require __DIR__ . '/views/tab_downloads.php';
require __DIR__ . '/views/tab_pricing.php';
require __DIR__ . '/views/tab_impressum.php';
require __DIR__ . '/views/tab_gallery.php';
require __DIR__ . '/views/tab_past.php';
require __DIR__ . '/views/tab_courses.php';
require __DIR__ . '/views/tab_settings.php';
require __DIR__ . '/views/tab_seo.php';
require __DIR__ . '/views/tab_inbox.php';
require __DIR__ . '/views/tab_security.php';
require __DIR__ . '/views/modals.php';
require __DIR__ . '/views/footer.php';
