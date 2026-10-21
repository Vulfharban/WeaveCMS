<?php
/**
 * ============================================================================
 * WeaveCMS – Administrations-Controller & POST-Aktions-Dispatcher
 * ============================================================================
 * Kapselt alle Verwaltungsoperationen des Backends:
 * - Design- und Darstellungseinstellungen
 * - Sektionsverwaltung und Sortierung
 * - Postfachverwaltung (Kontaktanfragen)
 * - Speicher-Migration (JSON <-> MySQL)
 * - SEO- und Social-Sharing-Konfiguration
 * - Admin-Sicherheit (Passwort, PIN, Captcha, SMTP)
 * - 1-Klick-Backups und Systemwiederherstellung
 * - Galerie, Kategorien und Sortierung
 * - Kurse, Termine und Impressionen
 *
 * @package WeaveCMS\Admin
 */

require_once __DIR__ . '/ContactInbox.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/BackupManager.php';
require_once __DIR__ . '/SectionManager.php';
require_once __DIR__ . '/storage/StorageManager.php';

class AdminController {

    /**
     * Ermittelt den absoluten Pfad zur WeaveCMS-Wurzel.
     * @return string
     */
    public static function rootPath(): string {
        return dirname(__DIR__, 2);
    }

    /**
     * Hauptmethode zur Verarbeitung aller eingehenden POST-Aktionen im Admin-Bereich.
     *
     * @param string $dataFile Pfad zur data.json
     * @param array &$data Referenz auf das globale Datenarray
     * @param string $csrfToken Aktives CSRF-Sitzungstoken
     * @return array ['success_msg' => string, 'error_msg' => string, 'active_tab' => string]
     */
    public static function handlePost(string $dataFile, array &$data, string $csrfToken): array {
        $success_msg = '';
        $error_msg = '';
        $active_tab = 'tab-texts';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success_msg' => $success_msg,
                'error_msg' => $error_msg,
                'active_tab' => $active_tab
            ];
        }

        $action = $_POST['action'] ?? '';

        // CSRF-Schutz: Token validieren
        if (!isset($_POST['login'])) {
            $submitted_csrf = $_POST['csrf_token'] ?? '';
            if (empty($submitted_csrf) || !hash_equals($csrfToken, $submitted_csrf)) {
                return [
                    'success_msg' => '',
                    'error_msg' => 'Sicherheitswarnung (CSRF): Ungültiges oder abgelaufenes Sicherheits-Token. Die Aktion wurde abgebrochen.',
                    'active_tab' => $active_tab
                ];
            }
        }

        // Automatische Reiter-Ermittlung anhand des Aktionsnamens
        $active_tab = self::determineActiveTab($action);

        switch ($action) {
            case 'update_settings':
            case 'update_design':
                self::handleSettings($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'inbox_set_status':
            case 'inbox_delete':
                self::handleInbox($action, $success_msg, $error_msg);
                break;

            case 'dismiss_welcome':
                self::handleDismissWelcome($dataFile, $data);
                break;

            case 'move_section':
            case 'toggle_section_visibility':
                self::handleSectionAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'migrate_storage_engine':
                self::handleStorageMigration($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_seo':
                self::handleSeo($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_credentials':
                self::handleCredentials($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_security':
                self::handleSecurity($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'test_smtp':
                self::handleSmtpTest($data, $success_msg, $error_msg);
                break;

            case 'create_backup':
            case 'restore_backup':
            case 'upload_restore':
            case 'delete_backup':
                self::handleBackupAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_category':
            case 'delete_category':
                self::handleCategoryAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_texts':
                self::handleTexts($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'update_impressum':
                self::handleImpressum($dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_gallery':
            case 'delete_gallery':
            case 'reorder_gallery':
                self::handleGalleryAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_course':
            case 'edit_course':
            case 'delete_course':
                self::handleCourseAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;

            case 'add_past_course_image':
            case 'delete_past_course_image':
                self::handlePastCourseAction($action, $dataFile, $data, $success_msg, $error_msg);
                break;
        }

        return [
            'success_msg' => $success_msg,
            'error_msg' => $error_msg,
            'active_tab' => $active_tab
        ];
    }

    /**
     * Ermittelt den Ziel-Tab basierend auf dem Aktions-Schlüsselwort.
     *
     * @param string $action
     * @return string
     */
    public static function determineActiveTab(string $action): string {
        if (strpos($action, 'inbox') !== false) {
            return 'tab-inbox';
        } elseif (strpos($action, 'gallery') !== false || strpos($action, 'category') !== false) {
            return 'tab-gallery';
        } elseif (strpos($action, 'course') !== false) {
            return 'tab-courses';
        } elseif (strpos($action, 'past') !== false) {
            return 'tab-past';
        } elseif (strpos($action, 'seo') !== false) {
            return 'tab-seo';
        } elseif (
            strpos($action, 'security') !== false || 
            strpos($action, 'smtp') !== false || 
            strpos($action, 'credentials') !== false || 
            strpos($action, 'backup') !== false || 
            strpos($action, 'restore') !== false || 
            strpos($action, 'migrate') !== false
        ) {
            return 'tab-security';
        } elseif (strpos($action, 'settings') !== false || strpos($action, 'design') !== false) {
            return 'tab-settings';
        } elseif (strpos($action, 'contact') !== false || strpos($action, 'impressum') !== false) {
            return 'tab-impressum';
        } elseif (strpos($action, 'texts') !== false || strpos($action, 'section') !== false) {
            return 'tab-texts';
        }
        return 'tab-texts';
    }

    /**
     * Verarbeitet Design-, Farbschema- und Darstellungsaktualisierungen.
     */
    protected static function handleSettings(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $gallery_layout = $_POST['gallery_layout'] ?? 'ambient_blur';
        if (!in_array($gallery_layout, ['ambient_blur', 'masonry', 'editorial', 'classic'])) {
            $gallery_layout = 'ambient_blur';
        }
        $data['settings']['gallery_layout'] = $gallery_layout;
        $data['settings']['gallery_lightbox'] = !empty($_POST['gallery_lightbox']);

        $hero_logo_motif = $_POST['hero_logo_motif'] ?? 'paw_simple';
        if (!in_array($hero_logo_motif, ['paw_simple', 'paw_beach', 'paw_willow', 'none'])) {
            $hero_logo_motif = 'paw_simple';
        }
        $data['settings']['hero_logo_motif'] = $hero_logo_motif;

        $hero_logo_color = trim($_POST['hero_logo_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $hero_logo_color)) {
            $hero_logo_color = '#b26c39';
        }
        $data['settings']['hero_logo_color'] = $hero_logo_color;

        $logo_position = $_POST['logo_position'] ?? 'header_before';
        if (!in_array($logo_position, ['header_before', 'header_after', 'hero_before', 'hero_after'])) {
            $logo_position = 'header_before';
        }
        $data['settings']['logo_position'] = $logo_position;

        $design_theme = $_POST['design_theme'] ?? 'nature_classic';
        if (!in_array($design_theme, ['nature_classic', 'moor_amber', 'nordic_sage', 'terracotta_atelier', 'custom'])) {
            $design_theme = 'nature_classic';
        }
        $data['settings']['design_theme'] = $design_theme;

        $container_width = $_POST['container_width'] ?? '1200px';
        if (!in_array($container_width, ['1050px', '1200px', '1400px'])) {
            $container_width = '1200px';
        }
        $data['settings']['container_width'] = $container_width;

        $primary_color = trim($_POST['primary_color'] ?? '#2e6248');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $primary_color)) {
            $primary_color = '#2e6248';
        }
        $data['settings']['primary_color'] = $primary_color;

        $accent_color = trim($_POST['accent_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $accent_color)) {
            $accent_color = '#b26c39';
        }
        $data['settings']['accent_color'] = $accent_color;

        $bg_color = trim($_POST['bg_color'] ?? '#fbf9f6');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $bg_color)) {
            $bg_color = '#fbf9f6';
        }
        $data['settings']['bg_color'] = $bg_color;

        $font_scale = $_POST['font_scale'] ?? '100%';
        if (!in_array($font_scale, ['95%', '100%', '108%'])) {
            $font_scale = '100%';
        }
        $data['settings']['font_scale'] = $font_scale;

        $card_radius = $_POST['card_radius'] ?? '20px';
        if (!in_array($card_radius, ['10px', '20px', '28px'])) {
            $card_radius = '20px';
        }
        $data['settings']['card_radius'] = $card_radius;

        $gallery_gap = $_POST['gallery_gap'] ?? '2rem';
        if (!in_array($gallery_gap, ['1.25rem', '2rem', '2.75rem'])) {
            $gallery_gap = '2rem';
        }
        $data['settings']['gallery_gap'] = $gallery_gap;

        if (save_data($dataFile, $data)) {
            $success_msg = 'Darstellung &amp; Design-Einstellungen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Darstellungseinstellungen.';
        }
    }

    /**
     * Verarbeitet Kontaktanfragen im Postfach (Status ändern, löschen).
     */
    protected static function handleInbox(string $action, string &$success_msg, string &$error_msg): void {
        if ($action === 'inbox_set_status') {
            if (ContactInbox::setStatus($_POST['id'] ?? '', $_POST['status'] ?? '')) {
                $success_msg = 'Status der Anfrage aktualisiert.';
            } else {
                $error_msg = 'Status konnte nicht aktualisiert werden.';
            }
        } elseif ($action === 'inbox_delete') {
            if (ContactInbox::delete($_POST['id'] ?? '')) {
                $success_msg = 'Anfrage gelöscht.';
            } else {
                $error_msg = 'Anfrage nicht gefunden.';
            }
        }
    }

    /**
     * Schließt das Willkommens-Modal nach der Installation.
     */
    protected static function handleDismissWelcome(string $dataFile, array &$data): void {
        $data['settings']['welcome_shown'] = true;
        save_data($dataFile, $data);
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: ./');
        exit;
    }

    /**
     * Verarbeitet Sektions-Reihenfolge und Sichtbarkeiten.
     */
    protected static function handleSectionAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $sec_id = $_POST['section_id'] ?? '';
        if ($action === 'move_section') {
            $dir = $_POST['direction'] ?? 'up';
            if (SectionManager::moveSection($data, $sec_id, $dir)) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Sektions-Reihenfolge erfolgreich aktualisiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Reihenfolge.';
                }
            } else {
                $error_msg = 'Sektion konnte nicht verschoben werden.';
            }
        } elseif ($action === 'toggle_section_visibility') {
            if (SectionManager::setVisibility($data, $sec_id)) {
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Sektions-Sichtbarkeit erfolgreich aktualisiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Sichtbarkeit.';
                }
            } else {
                $error_msg = 'Sektion nicht gefunden.';
            }
        }
    }

    /**
     * Führt eine 1-Klick-Speicher-Migration durch (JSON <-> MySQL).
     */
    protected static function handleStorageMigration(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $target_engine = trim($_POST['target_engine'] ?? 'json');
        $migrate_pin = trim($_POST['migrate_pin'] ?? '');

        if ($target_engine === 'json') {
            $res = StorageManager::migrateToJson($migrate_pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ ' . $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } elseif ($target_engine === 'mysql') {
            $dbParams = [
                'host' => trim($_POST['db_host'] ?? '127.0.0.1'),
                'port' => (int)($_POST['db_port'] ?? 3306),
                'database' => trim($_POST['db_name'] ?? 'weavecms'),
                'username' => trim($_POST['db_user'] ?? 'root'),
                'password' => (string)($_POST['db_pass'] ?? ''),
                'prefix' => trim($_POST['db_prefix'] ?? 'weave_'),
                'create_db' => !empty($_POST['create_db'])
            ];
            $res = StorageManager::migrateToMysql($migrate_pin, $dbParams, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ ' . $res['message'];
            } else {
                $error_msg = $res['message'];
            }
        } else {
            $error_msg = 'Ungültige Ziel-Engine ausgewählt.';
        }
    }

    /**
     * Verarbeitet SEO- und Social-Sharing-Einstellungen inklusive OG-Bild.
     */
    protected static function handleSeo(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['seo']) || !is_array($data['seo'])) {
            $data['seo'] = [];
        }

        $data['seo']['meta_title'] = trim($_POST['meta_title'] ?? '');
        $data['seo']['meta_description'] = trim($_POST['meta_description'] ?? '');
        $data['seo']['meta_keywords'] = trim($_POST['meta_keywords'] ?? '');

        $robots = $_POST['robots'] ?? 'index, follow';
        $valid_robots = ['index, follow', 'noindex, follow', 'noindex, nofollow', 'index, nofollow'];
        $data['seo']['robots'] = in_array($robots, $valid_robots) ? $robots : 'index, follow';

        $data['seo']['canonical_url'] = trim($_POST['canonical_url'] ?? '');

        $uploaded_og = ImageUploader::upload('og_image_file', $data);
        if ($uploaded_og) {
            $data['seo']['og_image'] = $uploaded_og;
        } elseif (!empty($_POST['remove_og_image'])) {
            $data['seo']['og_image'] = '';
        }

        if (save_data($dataFile, $data)) {
            $success_msg = 'SEO- &amp; Social-Sharing-Einstellungen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der SEO-Einstellungen.';
        }
    }

    /**
     * Aktualisiert Administrator-Zugangsdaten (Passwort und PIN mit PIN-Bestätigung).
     */
    protected static function handleCredentials(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $current_pin = trim($_POST['current_pin'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $new_pin = trim($_POST['new_pin'] ?? '');

        if (empty($new_password) && empty($new_pin)) {
            $error_msg = 'Bitte gib ein neues Passwort oder eine neue PIN ein, die du setzen möchtest.';
        } elseif (empty($current_pin)) {
            $error_msg = 'Sicherheitsabfrage erforderlich: Bitte gib deine aktuell noch gültige PIN ein, um Passwort oder PIN zu ändern!';
        } elseif (!AuthManager::verifyAdminPin($current_pin, $data)) {
            $error_msg = 'Sicherheitsabfrage fehlgeschlagen: Die eingegebene aktuelle PIN ist nicht korrekt! Die Zugangsdaten wurden NICHT geändert.';
        } else {
            $credentials_changed = false;
            if (!empty($new_password)) {
                $data['settings']['admin_password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                $credentials_changed = true;
            }
            if (!empty($new_pin)) {
                $data['settings']['admin_pin_hash'] = password_hash($new_pin, PASSWORD_DEFAULT);
                $credentials_changed = true;
            }

            if ($credentials_changed && save_data($dataFile, $data)) {
                $auth_hash = AuthManager::getAuthHash($data);
                setcookie('admin_auth', $auth_hash, time() + 86400 * 30, '/');
                $success_msg = '✓ Admin-Zugangsdaten (Passwort / PIN) wurden erfolgreich geändert!';
            } else {
                $error_msg = 'Fehler beim Speichern der neuen Zugangsdaten.';
            }
        }
    }

    /**
     * Verarbeitet Sicherheits- und E-Mail-Konfigurationen (Upload-Pfad, SMTP, Captcha-Icons).
     */
    protected static function handleSecurity(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $upload_dir = trim($_POST['upload_dir'] ?? 'images/uploads');
        if (empty($upload_dir)) {
            $upload_dir = 'images/uploads';
        }
        $data['settings']['upload_dir'] = $upload_dir;

        $target_abs = self::rootPath() . '/' . trim($upload_dir, '/\\');
        if (!is_dir($target_abs)) {
            @mkdir($target_abs, 0755, true);
        }

        $smtp_enabled = !empty($_POST['smtp_enabled']);
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = (int)($_POST['smtp_port'] ?? 587);
        $smtp_secure = trim($_POST['smtp_secure'] ?? 'tls');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass_new = $_POST['smtp_pass'] ?? '';
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'Atelier Musterfrau');

        if (!isset($data['settings']['smtp']) || !is_array($data['settings']['smtp'])) {
            $data['settings']['smtp'] = [];
        }

        $data['settings']['smtp']['enabled'] = $smtp_enabled;
        $data['settings']['smtp']['host'] = $smtp_host;
        $data['settings']['smtp']['port'] = ($smtp_port > 0) ? $smtp_port : 587;
        $data['settings']['smtp']['secure'] = in_array($smtp_secure, ['tls', 'ssl', 'none']) ? $smtp_secure : 'tls';
        $data['settings']['smtp']['user'] = $smtp_user;
        if (!empty($smtp_pass_new)) {
            $data['settings']['smtp']['pass'] = $smtp_pass_new;
        }
        $data['settings']['smtp']['from_email'] = $smtp_from_email;
        $data['settings']['smtp']['from_name'] = $smtp_from_name;

        $submitted_captcha = $_POST['captcha_icons'] ?? [];
        $captcha_error = false;
        if (is_array($submitted_captcha)) {
            $all_available_icons = CaptchaManager::getAllIcons();
            $valid_captcha_icons = [];
            foreach ($submitted_captcha as $c_id) {
                if (isset($all_available_icons[$c_id]) && !in_array($c_id, $valid_captcha_icons, true)) {
                    $valid_captcha_icons[] = $c_id;
                }
            }
            if (count($valid_captcha_icons) === 4) {
                $data['settings']['captcha_icons'] = $valid_captcha_icons;
            } elseif (count($valid_captcha_icons) !== 4) {
                $captcha_error = true;
                $error_msg = 'Bitte wähle genau 4 Symbole für den Spamschutz im Kontaktformular aus (aktuell ausgewählt: ' . count($valid_captcha_icons) . ').';
            }
        }

        if (!$captcha_error) {
            if (save_data($dataFile, $data)) {
                $success_msg = '✓ Spamschutz-, SMTP- und Upload-Einstellungen erfolgreich gespeichert.';
            } else {
                $error_msg = 'Fehler beim Speichern der Einstellungen.';
            }
        }
    }

    /**
     * Sendet eine Test-E-Mail über den konfigurierten SMTP-Server.
     */
    protected static function handleSmtpTest(array $data, string &$success_msg, string &$error_msg): void {
        $test_to = trim($_POST['test_to_email'] ?? ($data['texts']['contact_email'] ?? ''));
        if (empty($test_to) || !filter_var($test_to, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Bitte gib eine gültige Ziel-E-Mail-Adresse für den SMTP-Test an.';
            return;
        }

        $cur_smtp = $data['settings']['smtp'] ?? [];
        if (empty($cur_smtp['enabled']) || empty($cur_smtp['host'])) {
            $error_msg = 'SMTP ist nicht aktiviert oder es ist kein SMTP-Host eingetragen. Bitte aktiviere SMTP und speichere die Einstellungen zuerst.';
            return;
        }

        $test_subject = 'SMTP-Verbindungstest | WeaveCMS';
        $test_html = '<div style="font-family:sans-serif; padding:20px; color:#2e6248; background:#fbf9f6; border:1px solid #e7dfd5; border-radius:12px;">'
                   . '<h2>🌿 SMTP-Verbindungstest erfolgreich!</h2>'
                   . '<p>Die E-Mail-Konfiguration für das Kontaktformular auf deiner Website funktioniert einwandfrei.</p>'
                   . '<p><b>Zeitpunkt:</b> ' . date('d.m.Y H:i:s') . '</p>'
                   . '<p><b>Host:</b> ' . htmlspecialchars($cur_smtp['host']) . ':' . (int)($cur_smtp['port'] ?? 587) . ' (' . htmlspecialchars($cur_smtp['secure'] ?? 'tls') . ')</p>'
                   . '</div>';
        $test_text = "SMTP-Test erfolgreich!\nZeitpunkt: " . date('d.m.Y H:i:s') . "\nServer: " . ($cur_smtp['host'] ?? '');

        $test_res = SimpleMailer::sendViaSmtp(
            $test_to,
            $test_subject,
            $test_html,
            $test_text,
            $test_to,
            'Admin Test',
            $cur_smtp
        );

        if ($test_res['success']) {
            $success_msg = '✓ SMTP-Verbindungstest erfolgreich! Eine Test-E-Mail wurde an ' . htmlspecialchars($test_to) . ' gesendet.';
        } else {
            $error_msg = 'SMTP-Verbindungstest fehlgeschlagen: ' . htmlspecialchars($test_res['error']);
        }
    }

    /**
     * Verarbeitet Backup-Erstellung, -Wiederherstellung, -Upload und -Löschung.
     */
    protected static function handleBackupAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'create_backup') {
            $backup_type = $_POST['backup_type'] ?? 'full';
            $res = BackupManager::createBackup($backup_type, $data);
            if ($res['success']) {
                $success_msg = '✓ Sicherung "' . htmlspecialchars($res['filename']) . '" wurde erfolgreich im geschützten Ordner backups/ erstellt!';
            } else {
                $error_msg = 'Fehler beim Erstellen der Sicherung: ' . htmlspecialchars($res['error']);
            }
        } elseif ($action === 'restore_backup') {
            $file = $_POST['backup_file'] ?? '';
            $pin = trim($_POST['restore_pin'] ?? '');
            $res = BackupManager::restoreExistingBackup($file, $pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ Sicherung "' . htmlspecialchars($file) . '" wurde erfolgreich wiederhergestellt! (Ein Notfall-Snapshot wurde vorab angelegt).';
            } else {
                $error_msg = htmlspecialchars($res['error']);
            }
        } elseif ($action === 'upload_restore') {
            $pin = trim($_POST['restore_pin'] ?? '');
            $res = BackupManager::restoreUploadedBackup($_FILES['backup_upload_file'] ?? null, $pin, $data);
            if ($res['success']) {
                $data = load_data($dataFile);
                $success_msg = '✓ Die hochgeladene Sicherung wurde erfolgreich wiederhergestellt! (Ein Notfall-Snapshot wurde vorab angelegt).';
            } else {
                $error_msg = htmlspecialchars($res['error']);
            }
        } elseif ($action === 'delete_backup') {
            $file = $_POST['backup_file'] ?? '';
            if (BackupManager::deleteBackup($file)) {
                $success_msg = 'Sicherungsdatei "' . htmlspecialchars($file) . '" wurde erfolgreich gelöscht.';
            } else {
                $error_msg = 'Fehler beim Löschen der Sicherungsdatei.';
            }
        }
    }

    /**
     * Verarbeitet Hinzufügen und Entfernen von Galerie-Kategorien.
     */
    protected static function handleCategoryAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if (!isset($data['categories']) || !is_array($data['categories'])) {
            $data['categories'] = ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
        }

        if ($action === 'add_category') {
            $new_cat = trim($_POST['category_name'] ?? '');
            if (empty($new_cat)) {
                $error_msg = 'Bitte gib einen Namen für die Kategorie ein.';
                return;
            }
            $exists = false;
            foreach ($data['categories'] as $c) {
                if (mb_strtolower($c, 'UTF-8') === mb_strtolower($new_cat, 'UTF-8')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $data['categories'][] = $new_cat;
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Kategorie "' . htmlspecialchars($new_cat) . '" erfolgreich angelegt!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Kategorie.';
                }
            } else {
                $error_msg = 'Diese Kategorie existiert bereits.';
            }
        } elseif ($action === 'delete_category') {
            $del_cat = trim($_POST['category_name'] ?? '');
            if (!empty($del_cat)) {
                $pos = array_search($del_cat, $data['categories']);
                if ($pos !== false) {
                    array_splice($data['categories'], $pos, 1);
                    if (save_data($dataFile, $data)) {
                        $success_msg = 'Kategorie "' . htmlspecialchars($del_cat) . '" gelöscht. Zugeordnete Galerie-Elemente bleiben erhalten, werden aber im Frontend nicht mehr angezeigt.';
                    } else {
                        $error_msg = 'Fehler beim Löschen der Kategorie.';
                    }
                } else {
                    $error_msg = 'Kategorie nicht gefunden.';
                }
            }
        }
    }

    /**
     * Aktualisiert die Textinhalte aller Frontend-Sektionen.
     */
    protected static function handleTexts(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $data['texts']['hero_title'] = $_POST['hero_title'] ?? '';
        $data['texts']['hero_subtitle'] = $_POST['hero_subtitle'] ?? '';
        $data['texts']['about_title'] = $_POST['about_title'] ?? '';
        $data['texts']['about_text'] = $_POST['about_text'] ?? '';
        $data['texts']['gallery_title'] = $_POST['gallery_title'] ?? 'Galerie & Handarbeit';
        $data['texts']['gallery_subtitle'] = $_POST['gallery_subtitle'] ?? '';
        $data['texts']['courses_title'] = $_POST['courses_title'] ?? 'Kurse & Workshops';
        $data['texts']['courses_subtitle'] = $_POST['courses_subtitle'] ?? '';
        $data['texts']['past_courses_title'] = $_POST['past_courses_title'] ?? 'Impressionen vergangener Kurse';
        $data['texts']['past_courses_subtitle'] = $_POST['past_courses_subtitle'] ?? '';
        $data['texts']['contact_intro_text'] = $_POST['contact_intro_text'] ?? '';

        if (save_data($dataFile, $data)) {
            $success_msg = 'Inhaltstexte aller Sektionen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Texte.';
        }
    }

    /**
     * Aktualisiert Impressum, Kontaktdaten und DSGVO-Datenschutzerklärung.
     */
    protected static function handleImpressum(string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        $data['texts']['contact_name'] = $_POST['contact_name'] ?? '';
        $data['texts']['contact_street'] = $_POST['contact_street'] ?? '';
        $data['texts']['contact_city'] = $_POST['contact_city'] ?? '';
        $data['texts']['contact_phone'] = $_POST['contact_phone'] ?? '';
        $data['texts']['contact_email'] = $_POST['contact_email'] ?? '';
        $data['texts']['contact_instagram'] = $_POST['contact_instagram'] ?? '';
        $data['texts']['impressum_supervisory'] = $_POST['impressum_supervisory'] ?? '';
        $data['texts']['impressum_vat_id'] = $_POST['impressum_vat_id'] ?? '';
        $data['texts']['impressum_disclaimer'] = $_POST['impressum_disclaimer'] ?? '';
        $data['texts']['contact_mail_greeting'] = trim($_POST['contact_mail_greeting'] ?? 'Hallo Maxima,');
        $data['texts']['course_request_template'] = trim($_POST['course_request_template'] ?? '');

        $privacy_text = trim($_POST['privacy_policy_text'] ?? '');
        $data['settings']['privacy_policy_text'] = $privacy_text;
        $data['texts']['privacy_policy_text'] = $privacy_text;

        if (save_data($dataFile, $data)) {
            $success_msg = 'Impressum, Kontaktdaten &amp; Datenschutzerklärung erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern des Impressums.';
        }
    }

    /**
     * Verarbeitet Galerie-Operationen (Hinzufügen, Löschen, Sortieren).
     */
    protected static function handleGalleryAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_gallery') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $image_path = ImageUploader::upload('gallery_image', $data);

            if (empty($title)) {
                $error_msg = 'Bitte gib einen Titel für das Galerie-Element an.';
            } elseif (empty($image_path)) {
                $error_msg = 'Fehler beim Upload des Bildes (nur JPG, PNG, WebP und GIF erlaubt).';
            } else {
                $next_id = 1;
                $next_sort = 1;
                foreach ($data['gallery'] as $item) {
                    if ($item['id'] >= $next_id) $next_id = $item['id'] + 1;
                    if (($item['sort_order'] ?? 0) >= $next_sort) $next_sort = ($item['sort_order'] ?? 0) + 1;
                }

                $data['gallery'][] = [
                    'id' => $next_id,
                    'title' => $title,
                    'description' => $description,
                    'image' => $image_path,
                    'category' => $category ?: 'Sonstiges',
                    'sort_order' => $next_sort
                ];

                if (save_data($dataFile, $data)) {
                    $success_msg = 'Galerie-Element erfolgreich hinzugefügt!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Galerie-Daten.';
                }
            }
        } elseif ($action === 'delete_gallery') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['gallery'] as $idx => $item) {
                if ($item['id'] === $id) {
                    $upload_cfg = ImageUploader::getConfig($data);
                    $rel_prefix = $upload_cfg['rel'] . '/';
                    if (!empty($item['image']) && strpos($item['image'], $rel_prefix) === 0) {
                        $filepath = self::rootPath() . '/' . ltrim($item['image'], '/\\');
                        if (file_exists($filepath)) {
                            @unlink($filepath);
                        }
                    }
                    unset($data['gallery'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['gallery'] = array_values($data['gallery']);
                save_data($dataFile, $data);
                $success_msg = 'Galerie-Element erfolgreich gelöscht!';
            } else {
                $error_msg = 'Galerie-Element nicht gefunden.';
            }
        } elseif ($action === 'reorder_gallery') {
            $orders = $_POST['order'] ?? [];
            foreach ($data['gallery'] as &$item) {
                $iid = $item['id'];
                if (isset($orders[$iid])) {
                    $item['sort_order'] = (int)$orders[$iid];
                }
            }

            usort($data['gallery'], function($a, $b) {
                return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            });

            if (save_data($dataFile, $data)) {
                $success_msg = 'Galerie-Reihenfolge erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Speichern der Reihenfolge.';
            }
        }
    }

    /**
     * Verarbeitet Flechtkurse (Hinzufügen, Bearbeiten, Löschen).
     */
    protected static function handleCourseAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_course') {
            $title = $_POST['title'] ?? '';
            $date_string = $_POST['date_string'] ?? '';
            $time = $_POST['time'] ?? '10:00 - 16:00 Uhr';
            $location = $_POST['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? '85 €';
            $max_participants = (int)($_POST['max_participants'] ?? 10);
            $current_participants = (int)($_POST['current_participants'] ?? 0);
            $available = isset($_POST['available']);

            if (empty($title) || empty($date_string)) {
                $error_msg = 'Bitte gib mindestens einen Kurstitel und ein Datum an.';
            } else {
                $next_id = 1;
                foreach ($data['courses'] as $c) {
                    if ($c['id'] >= $next_id) $next_id = $c['id'] + 1;
                }

                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                $invite_token = 'kurs-' . $next_id . '-' . trim($slug, '-');

                $data['courses'][] = [
                    'id' => $next_id,
                    'title' => $title,
                    'date_string' => $date_string,
                    'time' => $time,
                    'location' => $location,
                    'description' => $description,
                    'price' => $price,
                    'max_participants' => $max_participants,
                    'current_participants' => $current_participants,
                    'available' => $available,
                    'invite_token' => $invite_token
                ];

                if (save_data($dataFile, $data)) {
                    $success_msg = 'Flechtkurs erfolgreich angelegt und Einladungslink generiert!';
                } else {
                    $error_msg = 'Fehler beim Speichern des Kurses.';
                }
            }
        } elseif ($action === 'edit_course') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['courses'] as &$c) {
                if ($c['id'] === $id) {
                    $c['title'] = $_POST['title'] ?? '';
                    $c['date_string'] = $_POST['date_string'] ?? '';
                    $c['time'] = $_POST['time'] ?? '10:00 - 16:00 Uhr';
                    $c['location'] = $_POST['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow';
                    $c['description'] = $_POST['description'] ?? '';
                    $c['price'] = $_POST['price'] ?? '';
                    $c['max_participants'] = (int)($_POST['max_participants'] ?? 10);
                    $c['current_participants'] = (int)($_POST['current_participants'] ?? 0);
                    $c['available'] = isset($_POST['available']);
                    if (empty($c['invite_token'])) {
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $c['title']));
                        $c['invite_token'] = 'kurs-' . $id . '-' . trim($slug, '-');
                    }
                    $found = true;
                    break;
                }
            }

            if ($found && save_data($dataFile, $data)) {
                $success_msg = 'Flechtkurs erfolgreich aktualisiert!';
            } else {
                $error_msg = 'Fehler beim Aktualisieren des Kurses.';
            }
        } elseif ($action === 'delete_course') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($data['courses'] as $idx => $c) {
                if ($c['id'] === $id) {
                    unset($data['courses'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['courses'] = array_values($data['courses']);
                save_data($dataFile, $data);
                $success_msg = 'Flechtkurs erfolgreich gelöscht!';
            } else {
                $error_msg = 'Flechtkurs nicht gefunden.';
            }
        }
    }

    /**
     * Verarbeitet Impressionen vergangener Workshops (Foto-Upload und Löschung).
     */
    protected static function handlePastCourseAction(string $action, string $dataFile, array &$data, string &$success_msg, string &$error_msg): void {
        if ($action === 'add_past_course_image') {
            $image_path = ImageUploader::upload('past_image', $data);
            if (!empty($image_path)) {
                $data['past_courses'][] = $image_path;
                if (save_data($dataFile, $data)) {
                    $success_msg = 'Bild vergangener Kurse erfolgreich hochgeladen!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Kursbilder.';
                }
            } else {
                $error_msg = 'Fehler beim Bildupload.';
            }
        } elseif ($action === 'delete_past_course_image') {
            $img_path = $_POST['image_path'] ?? '';
            $found = false;
            foreach ($data['past_courses'] as $idx => $path) {
                if ($path === $img_path) {
                    $upload_cfg = ImageUploader::getConfig($data);
                    $rel_prefix = $upload_cfg['rel'] . '/';
                    if (!empty($path) && strpos($path, $rel_prefix) === 0) {
                        $filepath = self::rootPath() . '/' . ltrim($path, '/\\');
                        if (file_exists($filepath)) {
                            @unlink($filepath);
                        }
                    }
                    unset($data['past_courses'][$idx]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $data['past_courses'] = array_values($data['past_courses']);
                save_data($dataFile, $data);
                $success_msg = 'Kursbild erfolgreich gelöscht!';
            } else {
                $error_msg = 'Bild nicht in der Liste gefunden.';
            }
        }
    }
}
