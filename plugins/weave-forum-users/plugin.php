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
require_once __DIR__ . '/ForumMessageManager.php';

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
if (!empty($_SESSION['admin_logged_in']) && !(class_exists('SandboxManager') && SandboxManager::isDemoActive())) {
    
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
if (!function_exists('weave_forum_read_post_field')) {
    /**
     * Liest ein per URL-safe Base64 (WAF-Schutz) oder regulär übermitteltes POST-Feld.
     */
    function weave_forum_read_post_field(string $field, string $default = ''): string {
        $b64Key = $field . '_b64';
        if (isset($_POST[$b64Key]) && is_string($_POST[$b64Key])) {
            $b64 = trim($_POST[$b64Key]);
            if ($b64 === '') {
                return '';
            }
            $std = strtr($b64, '-_', '+/');
            $pad = strlen($std) % 4;
            if ($pad > 0) {
                $std .= str_repeat('=', 4 - $pad);
            }
            $decoded = base64_decode($std, true);
            if ($decoded !== false) {
                return $decoded;
            }
        }
        return isset($_POST[$field]) && is_string($_POST[$field]) ? $_POST[$field] : $default;
    }
}

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

                    // Privatsphäre-Einstellungen & optionale einfache Profilfelder
                    $isStaff = ForumUserManager::canUseAlias($cur);
                    $hideOnline = $isStaff && !empty($_POST['hide_online_status']);
                    $showOnline = $isStaff ? !$hideOnline : !empty($_POST['priv_show_online_status']);

                    $profileLocation = mb_substr(trim((string)($_POST['profile_location'] ?? '')), 0, 80);
                    $profileInterests = mb_substr(trim((string)($_POST['profile_interests'] ?? '')), 0, 200);
                    $rawWebsite = trim((string)($_POST['profile_website'] ?? ''));
                    $profileWebsite = '';
                    if ($rawWebsite !== '' && filter_var($rawWebsite, FILTER_VALIDATE_URL)) {
                        $scheme = strtolower((string)parse_url($rawWebsite, PHP_URL_SCHEME));
                        if (in_array($scheme, ['http', 'https'], true)) {
                            $profileWebsite = mb_substr($rawWebsite, 0, 160);
                        }
                    }

                    $cur['privacy_settings'] = [
                        'show_role'           => !empty($_POST['priv_show_role']),
                        'show_post_count'     => !empty($_POST['priv_show_post_count']),
                        'show_joined_date'    => !empty($_POST['priv_show_joined_date']),
                        'show_bio'            => !empty($_POST['priv_show_bio']),
                        'show_signature'      => !empty($_POST['priv_show_signature']),
                        'show_online_status'  => $showOnline,
                        'hide_online_status'  => $hideOnline,
                        'show_email'          => !empty($_POST['priv_show_email']),
                        'show_location'       => !empty($_POST['priv_show_location']),
                        'show_interests'      => !empty($_POST['priv_show_interests']),
                        'show_website'        => !empty($_POST['priv_show_website']),
                        'allow_pm'            => !empty($_POST['priv_allow_pm']),
                        'allow_email_contact' => !empty($_POST['priv_allow_email_contact']),
                        'profile_location'    => $profileLocation,
                        'profile_interests'   => $profileInterests,
                        'profile_website'     => $profileWebsite,
                    ];

                    $cur['email'] = $newEmail;
                    $cur['bio'] = trim($_POST['bio'] ?? '');
                    $cur['signature'] = trim($_POST['signature'] ?? '');
                    $cur['notification_email'] = !empty($_POST['notification_email']) ? 1 : 0;
                    ForumUserManager::saveUser($cur);
                    ForumUserManager::touchOnlineActivity($cur);
                    $_SESSION['forum_profile_msg'] = 'saved';
                }
                header('Location: ?page=forum&view=profile');
                exit;
            }

            // PRIVATE MESSAGE SENDEN (PN)
            if ($authAction === 'send_pm') {
                $cur = ForumUserManager::getCurrentUser();
                $rawRedirect = trim((string)($_POST['redirect_to'] ?? ''));
                $redirectUrl = str_starts_with($rawRedirect, '?page=forum') ? $rawRedirect : '?page=forum&view=messages&box=sent';
                if (!$cur) {
                    header('Location: ?page=forum&view=login');
                    exit;
                }

                $now = time();
                $lastPm = (int)($_SESSION['forum_last_pm_ts'] ?? 0);
                if (($now - $lastPm) < 15) {
                    $_SESSION['forum_pm_flash'] = [
                        'type' => 'error',
                        'text' => 'Bitte warte einen kurzen Moment, bevor du die nächste private Nachricht sendest.'
                    ];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                $recipientId = trim((string)($_POST['recipient_id'] ?? ''));
                $subject = trim(weave_forum_read_post_field('pm_subject'));
                $body = trim(weave_forum_read_post_field('pm_body'));

                $res = ForumMessageManager::sendMessage((string)$cur['id'], $recipientId, $subject, $body);
                if (!empty($res['success'])) {
                    $_SESSION['forum_last_pm_ts'] = $now;
                    $_SESSION['forum_pm_flash'] = ['type' => 'success', 'text' => $res['message']];
                } else {
                    $_SESSION['forum_pm_flash'] = ['type' => 'error', 'text' => $res['message']];
                    if (str_contains($redirectUrl, 'view=messages')) {
                        $redirectUrl = '?page=forum&view=messages&box=compose&to=' . urlencode($recipientId);
                    }
                }
                header('Location: ' . $redirectUrl);
                exit;
            }

            // PRIVATE MESSAGE LÖSCHEN
            if ($authAction === 'delete_pm') {
                $cur = ForumUserManager::getCurrentUser();
                $box = in_array($_POST['box'] ?? 'inbox', ['inbox', 'sent'], true) ? $_POST['box'] : 'inbox';
                if (!$cur) {
                    header('Location: ?page=forum&view=login');
                    exit;
                }
                $msgId = trim((string)($_POST['msg_id'] ?? ''));
                if ($msgId !== '' && ForumMessageManager::deleteMessage($msgId, (string)$cur['id'])) {
                    $_SESSION['forum_pm_flash'] = ['type' => 'success', 'text' => 'Die private Nachricht wurde aus deinem Postfach entfernt.'];
                } else {
                    $_SESSION['forum_pm_flash'] = ['type' => 'error', 'text' => 'Die Nachricht konnte nicht gelöscht werden.'];
                }
                header('Location: ?page=forum&view=messages&box=' . urlencode($box));
                exit;
            }

            // KONTAKTAUFNAHME PER E-MAIL AN MITGLIED
            if ($authAction === 'send_member_email') {
                $cur = ForumUserManager::getCurrentUser();
                $recipientId = trim((string)($_POST['recipient_id'] ?? ''));
                $redirectUrl = '?page=forum&view=user&id=' . urlencode($recipientId);
                if (!$cur) {
                    header('Location: ?page=forum&view=login');
                    exit;
                }

                $now = time();
                $lastMail = (int)($_SESSION['forum_last_mail_ts'] ?? 0);
                if (($now - $lastMail) < 60) {
                    $_SESSION['forum_contact_flash'] = [
                        'type' => 'error',
                        'text' => 'Bitte warte mindestens 60 Sekunden zwischen zwei Kontakt-E-Mails.'
                    ];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                $recipient = $recipientId !== '' ? ForumUserManager::getUserById($recipientId) : null;
                if (!$recipient || ($recipient['status'] ?? ForumUserManager::STATUS_ACTIVE) !== ForumUserManager::STATUS_ACTIVE || $recipient['id'] === $cur['id']) {
                    $_SESSION['forum_contact_flash'] = ['type' => 'error', 'text' => 'Empfänger nicht gefunden oder nicht erreichbar.'];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                $recPriv = ForumUserManager::getPrivacySettings($recipient);
                if (empty($recPriv['allow_email_contact']) || !filter_var($recipient['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['forum_contact_flash'] = ['type' => 'error', 'text' => 'Dieses Mitglied hat den E-Mail-Kontakt deaktiviert.'];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                $subject = trim(preg_replace('/\s+/u', ' ', weave_forum_read_post_field('mail_subject')));
                $body = trim(weave_forum_read_post_field('mail_body'));
                if (mb_strlen($subject) < 3 || mb_strlen($subject) > 120) {
                    $_SESSION['forum_contact_flash'] = ['type' => 'error', 'text' => 'Der Betreff muss zwischen 3 und 120 Zeichen lang sein.'];
                    header('Location: ' . $redirectUrl);
                    exit;
                }
                if (mb_strlen($body) < 5 || mb_strlen($body) > 3000) {
                    $_SESSION['forum_contact_flash'] = ['type' => 'error', 'text' => 'Die Nachricht muss zwischen 5 und 3000 Zeichen lang sein.'];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                $mailerFile = dirname(__DIR__, 2) . '/libs/php/Mailer.php';
                if (file_exists($mailerFile)) {
                    require_once $mailerFile;
                }
                if (!class_exists('SimpleMailer')) {
                    $_SESSION['forum_contact_flash'] = ['type' => 'error', 'text' => 'Der E-Mail-Versand ist derzeit serverseitig nicht verfügbar.'];
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                global $data;
                $siteData = $data ?? ($GLOBALS['data'] ?? []);
                if (empty($siteData) && function_exists('load_data')) {
                    $siteData = load_data(dirname(__DIR__, 2) . '/data.json');
                }
                $smtpConfig = $siteData['settings']['smtp'] ?? null;
                $siteBrand = !empty($siteData['texts']['hero_title']) ? trim((string)$siteData['texts']['hero_title']) : 'WeaveCMS Forum';

                $senderName = ForumUserManager::getDisplayName($cur);
                $recipientName = ForumUserManager::getDisplayName($recipient);
                $safeSender = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
                $safeRecipient = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
                $safeBrand = htmlspecialchars($siteBrand, ENT_QUOTES, 'UTF-8');
                $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
                $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

                $htmlBody = "<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#ffffff;border:1px solid #e2e8f0;border-top:4px solid #2e6248;border-radius:10px;\">"
                          . "<h2 style=\"margin:0 0 10px 0;color:#0f172a;font-size:18px;\">Neue Foren-Kontaktanfrage</h2>"
                          . "<p style=\"font-size:14px;color:#334155;\">Hallo <strong>{$safeRecipient}</strong>,<br>das Mitglied <strong>{$safeSender}</strong> hat dir über {$safeBrand} folgende Nachricht gesendet:</p>"
                          . "<div style=\"background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:16px 0;\">"
                          . "<div style=\"font-weight:700;color:#0f172a;margin-bottom:8px;\">{$safeSubject}</div>"
                          . "<div style=\"font-size:14px;line-height:1.6;color:#1e293b;\">{$safeBody}</div>"
                          . "</div>"
                          . "<p style=\"font-size:12px;color:#64748b;margin:0;\">Du kannst direkt auf diese E-Mail antworten (Reply-To: {$safeSender}) oder eine private Nachricht im Forum senden.</p>"
                          . "</div>";

                $textBody = "Hallo {$recipientName},\n\ndas Mitglied {$senderName} hat dir über {$siteBrand} eine Nachricht gesendet:\n\n"
                          . "Betreff: {$subject}\n\n{$body}\n\n"
                          . "Du kannst direkt auf diese E-Mail antworten.";

                $replyEmail = filter_var($cur['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$cur['email'] : '';
                $mailRes = SimpleMailer::send(
                    (string)$recipient['email'],
                    "[{$siteBrand}] {$subject}",
                    $htmlBody,
                    $textBody,
                    $replyEmail,
                    $senderName,
                    $smtpConfig
                );

                if (!empty($mailRes['success'])) {
                    $_SESSION['forum_last_mail_ts'] = $now;
                    $_SESSION['forum_contact_flash'] = [
                        'type' => 'success',
                        'text' => 'Deine E-Mail wurde erfolgreich an ' . $recipientName . ' versendet.'
                    ];
                } else {
                    $err = !empty($mailRes['error']) ? ' (' . $mailRes['error'] . ')' : '';
                    $_SESSION['forum_contact_flash'] = [
                        'type' => 'error',
                        'text' => 'Die E-Mail konnte nicht versendet werden' . $err . '. Alternativ kannst du eine Private Nachricht (PN) nutzen.'
                    ];
                }
                header('Location: ' . $redirectUrl);
                exit;
            }
        } else {
            $_SESSION['forum_auth_error'] = 'Deine Sitzung ist abgelaufen oder das Sicherheitstoken war ungültig. Bitte versuche es erneut.';
            $targetView = in_array($authAction, ['login', 'register', 'profile', 'messages'], true) ? $authAction : 'login';
            if (in_array($authAction, ['send_pm', 'delete_pm'], true)) {
                $targetView = 'messages';
            } elseif ($authAction === 'send_member_email' && !empty($_POST['recipient_id'])) {
                header('Location: ?page=forum&view=user&id=' . urlencode((string)$_POST['recipient_id']));
                exit;
            }
            header('Location: ?page=forum&view=' . $targetView);
            exit;
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
