<?php
/**
 * ============================================================================
 * WeaveCMS Forum-Benutzerverwaltung – Plugin Einstiegspunkt
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/ForumUserManager.php';

// Sitzung und CSRF-Schutz initialisieren falls noch nicht erfolgt
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (empty($_SESSION['csrf_token'])) {
    if (class_exists('AuthManager')) {
        $_SESSION['csrf_token'] = AuthManager::getCsrfToken();
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
if (!empty($_SESSION['admin_logged_in'])) {
    
    // POST-Aktionen
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            
            // Benutzer bearbeiten (Rolle / Status / Alias)
            if ($action === 'forum_user_edit') {
                $uid = trim($_POST['user_id'] ?? '');
                $user = ForumUserManager::getUserById($uid);
                if ($user) {
                    $user['role'] = trim($_POST['role'] ?? $user['role']);
                    $user['status'] = trim($_POST['status'] ?? $user['status']);
                    if ($user['status'] === ForumUserManager::STATUS_ACTIVE) {
                        $user['verification_token'] = '';
                    }
                    $user['ban_reason'] = trim($_POST['ban_reason'] ?? '');

                    if (in_array($user['role'], [ForumUserManager::ROLE_ADMIN, ForumUserManager::ROLE_MODERATOR], true)) {
                        $newAlias = trim($_POST['alias'] ?? '');
                        if ($newAlias !== '') {
                            $existingAlias = ForumUserManager::findUser($newAlias);
                            if (!$existingAlias || $existingAlias['id'] === $user['id']) {
                                $user['alias'] = $newAlias;
                            }
                        } else {
                            $user['alias'] = '';
                        }
                        $user['use_alias_only'] = !empty($_POST['use_alias_only']) ? 1 : 0;
                    } else {
                        $user['alias'] = '';
                        $user['use_alias_only'] = 0;
                    }

                    ForumUserManager::saveUser($user);
                }
                header('Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=user_updated#tab-plugins');
                exit;
            }

            // Benutzer löschen / anonymisieren
            if ($action === 'forum_user_delete') {
                $uid = trim($_POST['user_id'] ?? '');
                ForumUserManager::anonymizeUser($uid);
                header('Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=user_anonymized#tab-plugins');
                exit;
            }

            // Seiten-Administrator als Forum-Administrator übernehmen / synchronisieren
            if ($action === 'forum_users_sync_site_admin') {
                global $data;
                $res = ForumUserManager::syncSiteAdminAsForumAdmin($data);
                $msgCode = $res['success'] ? 'admin_synced' : 'sync_err';
                $extra = $res['success'] 
                    ? '&fuser_name=' . urlencode($res['username']) . '&fuser_status=' . (!empty($res['is_new']) ? 'created' : 'updated')
                    : '&fuser_err=' . urlencode($res['message']);
                header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg={$msgCode}{$extra}#tab-plugins");
                exit;
            }

            // Speicher-Migration (JSON ➔ MySQL / JSON ➔ SQLite / SQLite/MySQL ➔ JSON)
            if ($action === 'forum_migrate_storage') {
                $target = trim($_POST['target_driver'] ?? 'json');
                global $data;
                $config = PluginManager::getPluginConfig('weave-forum-users', $data);

                if ($target === 'mysql') {
                    $res = ForumUserManager::migrateToJsonToMysql();
                    if ($res['success']) {
                        $config['storage_driver'] = 'mysql';
                        PluginManager::savePluginConfig('weave-forum-users', $config, $data);
                        save_data(dirname(__DIR__, 2) . '/data.json', $data);
                        $count = (int)($res['count'] ?? 0);
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=migrated_mysql&fuser_count={$count}#tab-plugins");
                        exit;
                    } else {
                        $err = urlencode($res['message'] ?? 'Migration nach MySQL fehlgeschlagen.');
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_err={$err}#tab-plugins");
                        exit;
                    }
                } elseif ($target === 'sqlite') {
                    $res = ForumUserManager::migrateToJsonToSqlite();
                    if ($res['success']) {
                        $config['storage_driver'] = 'sqlite';
                        PluginManager::savePluginConfig('weave-forum-users', $config, $data);
                        save_data(dirname(__DIR__, 2) . '/data.json', $data);
                        $count = (int)($res['count'] ?? 0);
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=migrated_sqlite&fuser_count={$count}#tab-plugins");
                        exit;
                    } else {
                        $err = urlencode($res['message'] ?? 'Migration nach SQLite fehlgeschlagen.');
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_err={$err}#tab-plugins");
                        exit;
                    }
                } else {
                    $driver = ForumUserManager::getStorageDriver();
                    $res = ($driver === 'mysql') ? ForumUserManager::migrateMysqlToJson() : ForumUserManager::migrateSqliteToJson();
                    if ($res['success']) {
                        $config['storage_driver'] = 'json';
                        PluginManager::savePluginConfig('weave-forum-users', $config, $data);
                        save_data(dirname(__DIR__, 2) . '/data.json', $data);
                        $count = (int)($res['count'] ?? 0);
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=migrated_json&fuser_count={$count}#tab-plugins");
                        exit;
                    } else {
                        $err = urlencode($res['message'] ?? 'Migration nach JSON fehlgeschlagen.');
                        header("Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_err={$err}#tab-plugins");
                        exit;
                    }
                }
            }

            // Konfiguration speichern
            if ($action === 'forum_users_save_config') {
                global $data;
                $config = PluginManager::getPluginConfig('weave-forum-users', $data);
                $config['registration_mode'] = trim($_POST['registration_mode'] ?? 'email_verify');
                $config['min_password_len'] = max(6, (int)($_POST['min_password_len'] ?? 8));
                $config['allow_avatars'] = !empty($_POST['allow_avatars']) ? 1 : 0;
                $config['forum_guidelines'] = trim($_POST['forum_guidelines'] ?? '');
                
                PluginManager::savePluginConfig('weave-forum-users', $config, $data);
                save_data(dirname(__DIR__, 2) . '/data.json', $data);
                header('Location: ?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_msg=user_updated#tab-plugins');
                exit;
            }
        }
    }

    // GET-Aktion: Admin DSGVO-Export
    if (isset($_GET['fuser_action']) && $_GET['fuser_action'] === 'export' && !empty($_GET['id'])) {
        $exportData = ForumUserManager::exportUserData(trim($_GET['id']));
        if ($exportData) {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="forum_user_' . urlencode($exportData['username']) . '_dsgvo.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend Authentifizierungs-Handler
// -----------------------------------------------------------------------------
if (!empty($_GET['forum_auth'])) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $authAction = $_GET['forum_auth'];

    // E-Mail-Verifizierung via Token-Link (Double-Opt-In)
    if ($authAction === 'verify_email') {
        global $data;
        $cfg = PluginManager::getPluginConfig('weave-forum-users', $data ?? []);
        $mode = $cfg['registration_mode'] ?? 'email_verify';
        $token = trim($_GET['token'] ?? '');
        $res = ForumUserManager::verifyEmailToken($token, $mode);
        if ($res['success']) {
            $_SESSION['forum_auth_success'] = $res['message'];
        } else {
            $_SESSION['forum_auth_error'] = $res['message'];
        }
        header('Location: ?page=forum&view=login');
        exit;
    }

    // Logout
    if ($authAction === 'logout') {
        ForumUserManager::logout();
        $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?page=forum';
        header('Location: ' . $redirect);
        exit;
    }

    // DSGVO User Self-Export
    if ($authAction === 'export_data') {
        $cur = ForumUserManager::getCurrentUser();
        if ($cur) {
            $export = ForumUserManager::exportUserData($cur['id']);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="forum_profil_' . urlencode($cur['username']) . '_dsgvo.json"');
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // POST Requests: Login, Register, Profile Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            
            // LOGIN
            if ($authAction === 'login') {
                $res = ForumUserManager::login(trim($_POST['login'] ?? ''), (string)($_POST['password'] ?? ''));
                if ($res['success']) {
                    header('Location: ?page=forum');
                    exit;
                }
                $_SESSION['forum_auth_error'] = $res['message'];
                header('Location: ?page=forum&view=login');
                exit;
            }

            // REGISTER
            if ($authAction === 'register') {
                global $data;
                $cfg = PluginManager::getPluginConfig('weave-forum-users', $data ?? []);
                $mode = $cfg['registration_mode'] ?? 'email_verify';

                $res = ForumUserManager::register($_POST, $mode);
                if ($res['success']) {
                    if (in_array($res['status'] ?? '', ['pending', 'pending_verification'], true)) {
                        $_SESSION['forum_auth_success'] = $res['message'];
                        header('Location: ?page=forum&view=login');
                        exit;
                    }
                    header('Location: ?page=forum');
                    exit;
                }
                $_SESSION['forum_auth_error'] = $res['message'];
                header('Location: ?page=forum&view=register');
                exit;
            }

            // PROFILE UPDATE
            if ($authAction === 'update_profile') {
                $cur = ForumUserManager::getCurrentUser();
                if ($cur) {
                    $newEmail = trim(mb_strtolower($_POST['email'] ?? $cur['email']));
                    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['forum_profile_msg'] = 'invalid_email';
                        header('Location: ?page=forum&view=profile');
                        exit;
                    }
                    $existing = ForumUserManager::findUser($newEmail);
                    if ($existing && $existing['id'] !== $cur['id']) {
                        $_SESSION['forum_profile_msg'] = 'email_taken';
                        header('Location: ?page=forum&view=profile');
                        exit;
                    }

                    // Alias-Verwaltung (für Admins und Moderatoren)
                    if (ForumUserManager::canUseAlias($cur)) {
                        $newAlias = trim($_POST['alias'] ?? '');
                        if ($newAlias !== '') {
                            if (mb_strlen($newAlias) < 2 || mb_strlen($newAlias) > 40) {
                                $_SESSION['forum_profile_msg'] = 'invalid_alias';
                                header('Location: ?page=forum&view=profile');
                                exit;
                            }
                            $existingAlias = ForumUserManager::findUser($newAlias);
                            if ($existingAlias && $existingAlias['id'] !== $cur['id']) {
                                $_SESSION['forum_profile_msg'] = 'alias_taken';
                                header('Location: ?page=forum&view=profile');
                                exit;
                            }
                            $cur['alias'] = $newAlias;
                        } else {
                            $cur['alias'] = '';
                        }
                        $cur['use_alias_only'] = !empty($_POST['use_alias_only']) ? 1 : 0;
                    }

                    // Privatsphäre-Einstellungen (welche Inhalte anderen Benutzern angezeigt werden)
                    $cur['privacy_settings'] = [
                        'show_role'          => !empty($_POST['priv_show_role']),
                        'show_post_count'    => !empty($_POST['priv_show_post_count']),
                        'show_joined_date'   => !empty($_POST['priv_show_joined_date']),
                        'show_bio'           => !empty($_POST['priv_show_bio']),
                        'show_signature'     => !empty($_POST['priv_show_signature']),
                        'show_online_status' => !empty($_POST['priv_show_online_status']),
                    ];

                    $cur['email'] = $newEmail;
                    $cur['bio'] = trim($_POST['bio'] ?? '');
                    $cur['signature'] = trim($_POST['signature'] ?? '');
                    $cur['notification_email'] = !empty($_POST['notification_email']) ? 1 : 0;
                    ForumUserManager::saveUser($cur);
                    $_SESSION['forum_profile_msg'] = 'saved';
                }
                header('Location: ?page=forum&view=profile');
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Einstellungsansicht für WeaveCMS Admin registrieren
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-forum-users', function($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 4. Filter-Hooks für andere Plugins bereitstellen
// -----------------------------------------------------------------------------
PluginManager::addFilter('forum_get_current_user', function($user) {
    return ForumUserManager::getCurrentUser();
});

PluginManager::addFilter('forum_user_can', function($can, $action, $user = null, $context = null) {
    return ForumUserManager::userCan($action, $user, $context);
});

// -----------------------------------------------------------------------------
// 5. Shortcodes für Authentifizierung & Profil
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[forum_') === false) {
        return $content;
    }

    if (strpos($content, '[forum_login]') !== false) {
        ob_start();
        require __DIR__ . '/views/login.php';
        $loginHtml = ob_get_clean();
        $content = str_replace('[forum_login]', $loginHtml, $content);
    }

    if (strpos($content, '[forum_register]') !== false) {
        ob_start();
        require __DIR__ . '/views/register.php';
        $regHtml = ob_get_clean();
        $content = str_replace('[forum_register]', $regHtml, $content);
    }

    if (strpos($content, '[forum_profile]') !== false) {
        ob_start();
        require __DIR__ . '/views/profile.php';
        $profHtml = ob_get_clean();
        $content = str_replace('[forum_profile]', $profHtml, $content);
    }

    return $content;
});
