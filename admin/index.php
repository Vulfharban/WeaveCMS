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

if (!defined('WEAVE_IN_ADMIN')) {
    define('WEAVE_IN_ADMIN', true);
}

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

// 1.1 Aktive Backend-Sprache initialisieren (DE & EN, strikter Fallback EN)
$admin_lang = LanguageManager::detectAdminLanguage($data);


// Benutzer-Datenmodell initialisieren (Superadmin-Seeding bei Bedarf)
UserManager::ensureInitialized($data);

// Aktive Plugins für Admin-Kontext initialisieren (Phase 13)
PluginManager::loadActivePlugins($data, 'admin');

// 2. Abmeldung (Logout) verarbeiten
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthManager::logout();
    header('Location: ./');
    exit;
}

// 3. Login-Verifikation bei Formularübermittlung
$login_error = '';
if (!empty($_SESSION['login_notice'])) {
    $login_error = $_SESSION['login_notice'];
    unset($_SESSION['login_notice']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!AuthManager::verifyCsrfToken($submitted_csrf)) {
        $login_error = 'Sicherheitswarnung (CSRF): Das Formular-Token ist abgelaufen. Bitte versuche es erneut.';
    } elseif (SecurityManager::isRateLimited('admin_login')) {
        $rem = SecurityManager::getLockoutRemaining('admin_login');
        $mins = max(1, ceil($rem / 60));
        $login_error = "Zu viele Fehlversuche! Der Zugang ist für {$mins} Minute(n) gesperrt ({$rem}s verbleibend).";
    } else {
        $username = trim($_POST['username'] ?? 'admin');
        $password = $_POST['password'] ?? '';
        $pin = $_POST['pin'] ?? '';
        if (AuthManager::login($password, $pin, $data, $username)) {
            save_data($data_file, $data);
            header('Location: ./');
            exit;
        } else {
            if (SecurityManager::isRateLimited('admin_login')) {
                $rem = SecurityManager::getLockoutRemaining('admin_login');
                $mins = max(1, ceil($rem / 60));
                $login_error = "Zu viele Fehlversuche! Der Zugang wurde für {$mins} Minute(n) gesperrt.";
            } else {
                $login_error = 'Ungültiges Passwort oder PIN-Code. Bitte versuche es erneut.';
            }
        }
    }
}

// 4. Authentifizierungs-Check
if (!AuthManager::isLoggedIn($data)) {
    require __DIR__ . '/views/login.php';
    exit;
}

// 4.1 Wenn Sprache im Header umgeschaltet wurde, als Standard für diesen Benutzer persistieren
if (!empty($_GET['admin_lang'])) {
    $paramLang = strtolower(trim($_GET['admin_lang']));
    if (in_array($paramLang, ['de', 'en'], true)) {
        $curUser = UserManager::getCurrentUser($data);
        if (!empty($curUser['id']) && ($curUser['admin_lang'] ?? '') !== $paramLang) {
            UserManager::updateUser($data, $curUser['id'], ['admin_lang' => $paramLang]);
            save_data($data_file, $data);
        }
    }
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

// 6.1 AJAX Action: Revisions & Entwurfsmodus (Phase 23)
if (isset($_GET['action']) && $_GET['action'] === 'get_revisions') {
    header('Content-Type: application/json');
    $type = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['item_type'] ?? '');
    $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['item_id'] ?? '');
    $revs = RevisionManager::getRevisions($type, $id);
    echo json_encode(['success' => true, 'revisions' => $revs]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_revision_diff') {
    header('Content-Type: application/json');
    $type = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['item_type'] ?? '');
    $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['item_id'] ?? '');
    $revId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['rev_id'] ?? '');

    $rev = RevisionManager::getRevision($type, $id, $revId);
    if (!$rev) {
        echo json_encode(['success' => false, 'error' => 'Revision nicht gefunden']);
        exit;
    }

    $targetKey = ($type === 'page') ? 'pages' : (($type === 'blog') ? 'blog' : '');
    $currentItem = null;
    if ($targetKey && isset($data[$targetKey])) {
        foreach ($data[$targetKey] as $entry) {
            if ((string)($entry['id'] ?? '') === (string)$id) {
                $currentItem = $entry;
                break;
            }
        }
    }

    $oldContent = (string)($rev['data']['content'] ?? ($rev['data']['text'] ?? json_encode($rev['data'], JSON_PRETTY_PRINT)));
    $currentContent = (string)($currentItem['content'] ?? ($currentItem['text'] ?? json_encode($currentItem, JSON_PRETTY_PRINT)));
    $diff = RevisionManager::computeDiff($oldContent, $currentContent);
    $diffHtml = RevisionManager::renderDiffHtml($diff);

    echo json_encode([
        'success' => true,
        'revision' => $rev,
        'diffHtml' => $diffHtml
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rollback_revision') {
    header('Content-Type: application/json');
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!AuthManager::verifyCsrfToken($submitted_csrf)) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token ungültig']);
        exit;
    }
    $type = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['item_type'] ?? '');
    $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['item_id'] ?? '');
    $revId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['rev_id'] ?? '');
    $author = $_SESSION['admin_user'] ?? 'Admin';

    $res = RevisionManager::rollbackRevision($type, $id, $revId, $data, $author);
    if ($res['success']) {
        save_data($data_file, $data);
    }
    echo json_encode($res);
    exit;
}

// 6.2 AJAX Actions: Zentrale Mediathek & Bild-Pipeline (Phase 22)
if (isset($_GET['action']) && $_GET['action'] === 'get_media') {
    header('Content-Type: application/json; charset=utf-8');
    if (!UserManager::canAccessTab('tab-media')) {
        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
        exit;
    }
    $filter = $_GET['filter'] ?? 'all';
    $sort = $_GET['sort'] ?? 'date_desc';
    $search = $_GET['search'] ?? null;
    $items = MediaManager::scanMedia($filter, $search);

    usort($items, function($a, $b) use ($sort) {
        if ($sort === 'date_asc') return $a['mtime'] <=> $b['mtime'];
        if ($sort === 'name_asc') return strcasecmp($a['filename'], $b['filename']);
        if ($sort === 'size_desc') return $b['size_bytes'] <=> $a['size_bytes'];
        return $b['mtime'] <=> $a['mtime'];
    });

    echo json_encode([
        'success' => true,
        'items'   => $items,
        'files'   => $items
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_media') {
    header('Content-Type: application/json; charset=utf-8');
    if (!UserManager::canAccessTab('tab-media')) {
        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
        exit;
    }
    if (!AuthManager::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token ungültig']);
        exit;
    }

    $uploaded = [];
    $errors = [];

    if (!empty($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
        $count = count($_FILES['files']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES['files']['name'][$i])) continue;
            $f = [
                'name'     => $_FILES['files']['name'][$i],
                'type'     => $_FILES['files']['type'][$i] ?? '',
                'tmp_name' => $_FILES['files']['tmp_name'][$i] ?? '',
                'error'    => $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $_FILES['files']['size'][$i] ?? 0,
            ];
            $res = MediaManager::upload($f);
            if ($res['success']) {
                $uploaded[] = $res['item'];
            } else {
                $errors[] = ($_FILES['files']['name'][$i] ?? 'Datei') . ': ' . ($res['error'] ?? 'Fehler');
            }
        }
    } elseif (!empty($_FILES['file'])) {
        $res = MediaManager::upload($_FILES['file']);
        if ($res['success']) {
            $uploaded[] = $res['item'];
        } else {
            $errors[] = $res['error'] ?? 'Fehler';
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Keine Dateien empfangen']);
        exit;
    }

    $isOk = count($uploaded) > 0;
    echo json_encode([
        'success'  => $isOk,
        'uploaded' => $uploaded,
        'files'    => $uploaded,
        'errors'   => $errors,
        'message'  => $isOk
            ? count($uploaded) . ' Datei(en) erfolgreich verarbeitet.' . (count($errors) > 0 ? ' (' . implode(', ', $errors) . ')' : '')
            : (count($errors) > 0 ? implode(', ', $errors) : 'Upload fehlgeschlagen.')
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_media') {
    header('Content-Type: application/json; charset=utf-8');
    if (!UserManager::canAccessTab('tab-media')) {
        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
        exit;
    }
    if (!AuthManager::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token ungültig']);
        exit;
    }
    $filename = basename($_POST['filename'] ?? '');
    $res = MediaManager::delete($filename);
    echo json_encode($res);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_media_meta') {
    header('Content-Type: application/json; charset=utf-8');
    if (!UserManager::canAccessTab('tab-media')) {
        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
        exit;
    }
    if (!AuthManager::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token ungültig']);
        exit;
    }
    $filename = basename($_POST['filename'] ?? '');
    $alt = trim($_POST['alt'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $ok = MediaManager::updateMeta($filename, ['alt' => $alt, 'title' => $title]);
    echo json_encode(['success' => $ok]);
    exit;
}

// 7. CSRF-Token für Sitzung generieren oder abrufen
$csrf_token = AuthManager::getCsrfToken();

// 8. Eingehende POST-Aktionen über AdminController verarbeiten
$controllerResult = AdminController::handlePost($data_file, $data, $csrf_token);
$success_msg = $controllerResult['success_msg'];
$error_msg = $controllerResult['error_msg'];
$active_tab = $controllerResult['active_tab'];

// Phase 25.2: Migrationen eines zuvor eingespielten Core-Updates (läuft mit dem neuen Code)
$pending_update_msg = CoreUpdater::finishPendingUpdate($data_file, $data);
if ($pending_update_msg !== '' && $success_msg === '') {
    $success_msg = $pending_update_msg;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_GET['tab'])) {
        $active_tab = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['tab']);
    } elseif (!empty($_GET['edit_lang'])) {
        $active_tab = 'tab-texts';
    }
}

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

// 14. Postfach- & Dashboard-Daten vorbereiten
$inbox_entries = ContactInbox::all();
$inbox_new_count = ContactInbox::countNew();
$inbox_total_count = count($inbox_entries);

$dashboard_stats = StatsManager::getDashboardSummary();
$cur_user = UserManager::getCurrentUser($data);
$all_users = UserManager::getUsers($data);
$storage_details = StorageManager::getDriverDetails();

// Phase 12: Update-Prüfung für CMS-Kern und installierte Plugins
$core_update_info = class_exists('UpdateManager') ? UpdateManager::checkCoreUpdate(!empty($_GET['check_updates'])) : ['update_available' => false];
$all_installed_plugins = class_exists('PluginManager') ? PluginManager::getAllPlugins($data) : [];
$plugin_updates = class_exists('UpdateManager') ? UpdateManager::checkPluginUpdates($all_installed_plugins, !empty($_GET['check_updates'])) : [];

$recent_security_logs = [];
$logPath = dirname(__DIR__) . '/logs/security.log';
if (file_exists($logPath)) {
    $logLines = array_filter(array_map('trim', file($logPath)));
    $lastLines = array_slice($logLines, -5);
    foreach (array_reverse($lastLines) as $line) {
        if (preg_match('~^\[(.*?)\] \[(.*?)\] \[IP: (.*?)\] \[(.*?)\] (.*?)(?: \| UA: .*)?$~', $line, $m)) {
            $recent_security_logs[] = [
                'time'    => date('H:i, d.m.', strtotime($m[1])),
                'level'   => $m[2],
                'ip'      => $m[3],
                'type'    => $m[4],
                'message' => $m[5]
            ];
        }
    }
}

// 15. Modulare Admin-Views rendern (mit rollenbasierten Zugriffsgrenzen)
require __DIR__ . '/views/header.php';
require __DIR__ . '/views/nav.php';

// Dashboard ist die neue Default-Startseite (Phase 10)
require __DIR__ . '/views/tab_dashboard.php';

// Inhalts-Tabs (für alle angemeldeten Rollen)
if (UserManager::canAccessTab('tab-content', $cur_user['role'])) {
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
}

// Mediathek (Phase 22 - für alle redaktionellen Rollen)
if (UserManager::canAccessTab('tab-media', $cur_user['role'])) {
    require __DIR__ . '/views/tab_media.php';
}

// Postfach (für Moderator und Administrator)
if (UserManager::canAccessTab('tab-inbox', $cur_user['role'])) {
    require __DIR__ . '/views/tab_inbox.php';
}

// System, Benutzer, Design & SEO (nur für Administratoren)
if ($cur_user['role'] === UserManager::ROLE_ADMIN) {
    require __DIR__ . '/views/tab_users.php';
    require __DIR__ . '/views/tab_display.php';
    require __DIR__ . '/views/tab_design.php';
    require __DIR__ . '/views/tab_seo.php';
    require __DIR__ . '/views/tab_security.php';
    require __DIR__ . '/views/tab_plugins.php';
}

require __DIR__ . '/views/modals.php';
require __DIR__ . '/views/footer.php';
