<?php
/**
 * ============================================================================
 * WeaveCMS Diskussionsforum – Plugin Einstiegspunkt
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/ForumManager.php';

// Sitzung und CSRF-Schutz initialisieren
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
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        
        // Kategorie speichern
        if ($action === 'forum_save_category') {
            ForumManager::saveCategory([
                'id' => trim($_POST['category_id'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'folder'),
                'sort_order' => (int)($_POST['sort_order'] ?? 10)
            ]);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=cat_saved#tab-plugins');
            exit;
        }

        // Kategorie löschen
        if ($action === 'forum_delete_category') {
            $catId = trim($_POST['category_id'] ?? '');
            ForumManager::deleteCategory($catId);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=cat_deleted#tab-plugins');
            exit;
        }

        // Forum/Board speichern
        if ($action === 'forum_save_board') {
            ForumManager::saveBoard([
                'id' => trim($_POST['board_id'] ?? ''),
                'category_id' => trim($_POST['category_id'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'forum'),
                'sort_order' => (int)($_POST['sort_order'] ?? 10),
                'min_role_write' => trim($_POST['min_role_write'] ?? 'forum_user')
            ]);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=board_saved#tab-plugins');
            exit;
        }

        // Forum/Board löschen
        if ($action === 'forum_delete_board') {
            $boardId = trim($_POST['board_id'] ?? '');
            ForumManager::deleteBoard($boardId);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=board_deleted#tab-plugins');
            exit;
        }

        // Globale Einstellungen speichern
        if ($action === 'forum_save_global_settings') {
            global $data;
            $cfg = PluginManager::getPluginConfig('weave-forum', $data);
            $cfg['forum_title'] = trim($_POST['forum_title'] ?? 'WeaveCMS Community Forum');
            $cfg['topics_per_page'] = max(5, (int)($_POST['topics_per_page'] ?? 20));
            $cfg['posts_per_page'] = max(5, (int)($_POST['posts_per_page'] ?? 15));
            $cfg['badwords'] = trim($_POST['badwords'] ?? '');
            $cfg['rate_limit_seconds'] = max(0, (int)($_POST['rate_limit_seconds'] ?? 15));
            $cfg['announcement_enabled'] = !empty($_POST['announcement_enabled']);
            $cfg['announcement_type'] = trim($_POST['announcement_type'] ?? 'info');
            $cfg['announcement_text'] = trim($_POST['announcement_text'] ?? '');

            // Zugriffs- & Lesemodus (Öffentlich vs. Registrierungspflicht)
            $guestMode = trim($_POST['guest_access_mode'] ?? 'public');
            $cfg['guest_access_mode'] = in_array($guestMode, ['public', 'members_only'], true) ? $guestMode : 'public';
            $allowedBoards = $_POST['guest_allowed_boards'] ?? [];
            if (!is_array($allowedBoards)) {
                $allowedBoards = [];
            }
            $cfg['guest_allowed_boards'] = array_values(array_filter(array_map('trim', $allowedBoards)));

            PluginManager::savePluginConfig('weave-forum', $cfg, $data);
            save_data(dirname(__DIR__, 2) . '/data.json', $data);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=settings_saved#tab-plugins');
            exit;
        }

        // Meldung bearbeiten
        if ($action === 'forum_resolve_report') {
            $repId = trim($_POST['report_id'] ?? '');
            $res = trim($_POST['resolution'] ?? 'dismissed');
            ForumManager::resolveReport($repId, $res, ['username' => 'Administrator', 'id' => 'admin']);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=report_resolved#tab-plugins');
            exit;
        }

        // Gemeldeten Beitrag löschen
        if ($action === 'forum_delete_reported_post') {
            $repId = trim($_POST['report_id'] ?? '');
            $postId = trim($_POST['post_id'] ?? '');
            ForumManager::deletePost($postId, ['username' => 'Administrator', 'id' => 'admin']);
            ForumManager::resolveReport($repId, 'resolved_deleted', ['username' => 'Administrator', 'id' => 'admin']);
            header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=report_resolved#tab-plugins');
            exit;
        }

        // Speicher-Migration (JSON ➔ MySQL / JSON ➔ SQLite / SQLite/MySQL ➔ JSON)
        if ($action === 'forum_migrate_data_storage') {
            $target = trim($_POST['target_driver'] ?? 'json');
            global $data;
            $config = PluginManager::getPluginConfig('weave-forum', $data);

            if ($target === 'mysql') {
                $res = ForumManager::migrateToJsonToMysql();
                if ($res['success']) {
                    $config['storage_driver'] = 'mysql';
                    PluginManager::savePluginConfig('weave-forum', $config, $data);
                    $userCfg = PluginManager::getPluginConfig('weave-forum-users', $data);
                    $userCfg['storage_driver'] = 'mysql';
                    PluginManager::savePluginConfig('weave-forum-users', $userCfg, $data);
                    save_data(dirname(__DIR__, 2) . '/data.json', $data);
                    header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=migrated_mysql#tab-plugins');
                    exit;
                } else {
                    $err = urlencode($res['message'] ?? 'Migration nach MySQL fehlgeschlagen.');
                    header("Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_err={$err}#tab-plugins");
                    exit;
                }
            } elseif ($target === 'sqlite') {
                $res = ForumManager::migrateToJsonToSqlite();
                if ($res['success']) {
                    $config['storage_driver'] = 'sqlite';
                    PluginManager::savePluginConfig('weave-forum', $config, $data);
                    $userCfg = PluginManager::getPluginConfig('weave-forum-users', $data);
                    $userCfg['storage_driver'] = 'sqlite';
                    PluginManager::savePluginConfig('weave-forum-users', $userCfg, $data);
                    save_data(dirname(__DIR__, 2) . '/data.json', $data);
                    header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=migrated_sqlite#tab-plugins');
                    exit;
                } else {
                    $err = urlencode($res['message'] ?? 'Migration nach SQLite fehlgeschlagen.');
                    header("Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_err={$err}#tab-plugins");
                    exit;
                }
            } else {
                $driver = ForumManager::getStorageDriver();
                $res = ($driver === 'mysql') ? ForumManager::migrateMysqlToJson() : ForumManager::migrateSqliteToJson();
                if ($res['success']) {
                    $config['storage_driver'] = 'json';
                    PluginManager::savePluginConfig('weave-forum', $config, $data);
                    $userCfg = PluginManager::getPluginConfig('weave-forum-users', $data);
                    $userCfg['storage_driver'] = 'json';
                    PluginManager::savePluginConfig('weave-forum-users', $userCfg, $data);
                    save_data(dirname(__DIR__, 2) . '/data.json', $data);
                    header('Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_msg=migrated_json#tab-plugins');
                    exit;
                } else {
                    $err = urlencode($res['message'] ?? 'Migration nach JSON fehlgeschlagen.');
                    header("Location: ?tab=tab-plugins&plugin_settings=weave-forum&forum_err={$err}#tab-plugins");
                    exit;
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Frontend Forum Aktionen (Thema erstellen, Antworten, Moderieren)
// -----------------------------------------------------------------------------
if (!empty($_GET['forum_action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $action = $_GET['forum_action'];
        $currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;

        // Thema erstellen
        if ($action === 'create_topic' && $currentUser) {
            $boardId = trim($_POST['board_id'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');

            // Rate Limit
            global $data;
            $cfg = PluginManager::getPluginConfig('weave-forum', $data ?? []);
            $rateLimit = (int)($cfg['rate_limit_seconds'] ?? 0);
            $lastPost = $_SESSION['forum_last_post_time'] ?? 0;
            if ($rateLimit > 0 && (time() - $lastPost < $rateLimit) && !ForumUserManager::userCan('moderate', $currentUser)) {
                $wait = $rateLimit - (time() - $lastPost);
                $_SESSION['forum_error'] = "Bitte warte noch {$wait} Sekunden, bevor du einen weiteren Beitrag verfasst.";
                header('Location: ?page=forum&view=new_topic&board=' . urlencode($boardId));
                exit;
            }

            $res = ForumManager::createTopic($boardId, $title, $content, $currentUser);
            if ($res['success']) {
                $_SESSION['forum_last_post_time'] = time();
                header('Location: ?page=forum&view=topic&id=' . urlencode($res['topic_id']));
                exit;
            }
            $_SESSION['forum_error'] = $res['message'];
            header('Location: ?page=forum&view=new_topic&board=' . urlencode($boardId));
            exit;
        }

        // Antwort verfassen
        if ($action === 'add_post' && $currentUser) {
            $topicId = trim($_POST['topic_id'] ?? '');
            $content = trim($_POST['content'] ?? '');

            // Rate Limit
            global $data;
            $cfg = PluginManager::getPluginConfig('weave-forum', $data ?? []);
            $rateLimit = (int)($cfg['rate_limit_seconds'] ?? 0);
            $lastPost = $_SESSION['forum_last_post_time'] ?? 0;
            if ($rateLimit > 0 && (time() - $lastPost < $rateLimit) && !ForumUserManager::userCan('moderate', $currentUser)) {
                $wait = $rateLimit - (time() - $lastPost);
                $_SESSION['forum_reply_error'] = "Bitte warte noch {$wait} Sekunden, bevor du einen weiteren Beitrag verfasst.";
                header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
                exit;
            }

            $res = ForumManager::addPost($topicId, $content, $currentUser);
            if ($res['success']) {
                $_SESSION['forum_last_post_time'] = time();
                header('Location: ?page=forum&view=topic&id=' . urlencode($topicId) . '#post-' . urlencode($res['post_id']));
                exit;
            }
            $_SESSION['forum_reply_error'] = $res['message'] ?? 'Fehler beim Antworten.';
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Beitrag bearbeiten
        if ($action === 'edit_post' && $currentUser) {
            $postId = trim($_POST['post_id'] ?? '');
            $topicId = trim($_POST['topic_id'] ?? '');
            $content = trim($_POST['content'] ?? '');

            $p = ForumManager::getPost($postId);
            $isMod = ForumUserManager::userCan('moderate', $currentUser);
            if ($p && ($p['author_id'] === $currentUser['id'] || $isMod)) {
                ForumManager::updatePost($postId, $content, $currentUser);
            }
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId) . '#post-' . urlencode($postId));
            exit;
        }

        // Beitrag melden
        if ($action === 'report_post' && $currentUser) {
            $postId = trim($_POST['post_id'] ?? '');
            $topicId = trim($_POST['topic_id'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            ForumManager::reportPost($postId, $currentUser, $reason);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Thema abonnieren / abbestellen
        if ($action === 'toggle_subscription' && $currentUser) {
            $topicId = trim($_POST['topic_id'] ?? '');
            ForumManager::toggleSubscription($currentUser['id'], $topicId);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Moderation: Thema anpinnen / lösen
        if ($action === 'mod_pin_topic' && $currentUser && ForumUserManager::userCan('moderate', $currentUser)) {
            $topicId = trim($_POST['topic_id'] ?? '');
            $pinned = !empty($_POST['pinned']);
            ForumManager::pinTopic($topicId, $pinned, $currentUser);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Moderation: Thema sperren / öffnen
        if ($action === 'mod_lock_topic' && $currentUser && ForumUserManager::userCan('moderate', $currentUser)) {
            $topicId = trim($_POST['topic_id'] ?? '');
            $locked = !empty($_POST['locked']);
            ForumManager::lockTopic($topicId, $locked, $currentUser);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Moderation: Thema verschieben
        if ($action === 'mod_move_topic' && $currentUser && ForumUserManager::userCan('moderate', $currentUser)) {
            $topicId = trim($_POST['topic_id'] ?? '');
            $newBoardId = trim($_POST['new_board_id'] ?? '');
            ForumManager::moveTopic($topicId, $newBoardId, $currentUser);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Moderation: Thema löschen
        if ($action === 'mod_delete_topic' && $currentUser && ForumUserManager::userCan('moderate', $currentUser)) {
            $topicId = trim($_POST['topic_id'] ?? '');
            $boardId = trim($_POST['board_id'] ?? '');
            ForumManager::deleteTopic($topicId, $currentUser);
            header('Location: ?page=forum&view=board&id=' . urlencode($boardId));
            exit;
        }

        // Moderation: Einzelnen Beitrag löschen
        if ($action === 'mod_delete_post' && $currentUser && ForumUserManager::userCan('moderate', $currentUser)) {
            $postId = trim($_POST['post_id'] ?? '');
            $topicId = trim($_POST['topic_id'] ?? '');
            ForumManager::deletePost($postId, $currentUser);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Thema als gelöst / ungelöst umschalten (Autor oder Moderation)
        if ($action === 'toggle_solved' && $currentUser) {
            $topicId = trim($_POST['topic_id'] ?? '');
            ForumManager::toggleSolved($topicId, $currentUser);
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId));
            exit;
        }

        // Beitrag mit "Danke / Hilfreich" (Kudos / Like) markieren
        if ($action === 'toggle_like' && $currentUser) {
            $postId = trim($_POST['post_id'] ?? '');
            $topicId = trim($_POST['topic_id'] ?? '');
            $res = ForumManager::toggleLike($postId, $currentUser['id']);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($res);
                exit;
            }
            header('Location: ?page=forum&view=topic&id=' . urlencode($topicId) . '#post-' . urlencode($postId));
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Admin Settings Ansicht
// -----------------------------------------------------------------------------
PluginManager::addAction('admin_render_plugin_settings_weave-forum', function($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});
PluginManager::addAction('admin_render_plugin_settings_forum', function($data, $csrf_token) {
    require __DIR__ . '/admin/settings.php';
});

// -----------------------------------------------------------------------------
// 4. Shortcode [forum]
// -----------------------------------------------------------------------------
PluginManager::addFilter('content_shortcodes', function($content, $data) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    if (strpos($content, '[forum]') !== false) {
        ob_start();
        require __DIR__ . '/views/forum_main.php';
        $forumHtml = ob_get_clean();
        $content = str_replace('[forum]', $forumHtml, $content);
    }

    return $content;
});

// -----------------------------------------------------------------------------
// 5. Registrierung als Sektion für das WeaveCMS Multi-Page & Sektionssystem
// -----------------------------------------------------------------------------
PluginManager::addFilter('registered_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-forum', $data)) {
        return $sections;
    }
    $cfg = PluginManager::getPluginConfig('weave-forum', $data);
    $sections[] = [
        'id' => 'forum',
        'type' => 'forum',
        'title' => !empty($cfg['forum_title']) ? $cfg['forum_title'] : 'Community-Diskussionsforum',
        'nav_label' => 'Forum',
        'visible' => true,
        'in_nav' => true,
        'plugin' => 'weave-forum',
        'can_delete' => false,
        'placement' => 'page',
        'icon' => 'forum'
    ];
    return $sections;
});

PluginManager::addFilter('visible_sections', function($sections, $data) {
    if (!PluginManager::isPluginActive('weave-forum', $data)) {
        return $sections;
    }
    $hasForum = false;
    foreach ($sections as $s) {
        if (($s['id'] ?? '') === 'forum') {
            $hasForum = true;
            break;
        }
    }
    if (!$hasForum) {
        $cfg = PluginManager::getPluginConfig('weave-forum', $data);
        $sections[] = [
            'id' => 'forum',
            'type' => 'forum',
            'title' => !empty($cfg['forum_title']) ? $cfg['forum_title'] : 'Community-Diskussionsforum',
            'nav_label' => 'Forum',
            'visible' => true,
            'in_nav' => true,
            'plugin' => 'weave-forum',
            'placement' => 'page',
            'icon' => 'forum'
        ];
    }
    return $sections;
});

// Custom Section Rendering Hook
PluginManager::addAction('frontend_render_custom_section', function($sectionId, $data) {
    if ($sectionId === 'forum') {
        require __DIR__ . '/views/forum_main.php';
    }
});
