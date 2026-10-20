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
 */
require_once '../config.php';

// 1. Zentrale Datenbasis (data.json) laden
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

// 2. Abmeldung (Logout) verarbeiten: Session vernichten und Auth-Cookie löschen
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION['admin_logged_in'] = false;
    unset($_SESSION['admin_logged_in']);
    setcookie('admin_auth', '', time() - 3600, '/'); // Auth-Cookie verfallen lassen
    session_destroy();
    header('Location: ./');
    exit;
}

// 3. Fallback-Login über geheimen URL-Token (z. B. ?token=xyz123)
if (isset($_GET['token']) && $_GET['token'] === ADMIN_TOKEN) {
    $_SESSION['admin_logged_in'] = true;
}

// 4. Automatischer Login über persistentes "Remember-Me"-Cookie (30 Tage Gültigkeit)
$auth_hash = md5(($data['settings']['admin_password_hash'] ?? DEFAULT_ADMIN_PASSWORD) . ':' . ($data['settings']['admin_pin_hash'] ?? DEFAULT_ADMIN_PIN));
if (isset($_COOKIE['admin_auth']) && $_COOKIE['admin_auth'] === $auth_hash) {
    $_SESSION['admin_logged_in'] = true;
}

// 5. Verifikation des Login-Formulars (Passwort + PIN-Code Kombination)
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    $pin = $_POST['pin'] ?? '';
    
    if (verify_admin_auth($password, $pin, $data)) {
        $_SESSION['admin_logged_in'] = true;
        setcookie('admin_auth', $auth_hash, time() + 86400 * 30, '/');
        header('Location: ./');
        exit;
    } else {
        $login_error = 'Ungültiges Passwort oder PIN-Code. Bitte versuche es erneut.';
    }
}

// 6. Login-Maske ausgeben, falls keine aktive Admin-Sitzung vorliegt
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin-Login | Bianka Wischnewski</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="../libs/css/styles.css?v=<?php echo file_exists('../libs/css/styles.css') ? filemtime('../libs/css/styles.css') : time(); ?>" rel="stylesheet">
    </head>
    <body class="admin-login-body">
        <div class="login-container">
            <div class="login-box">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <span style="font-size: 3rem;">🌿</span>
                    <h1 style="font-size: 1.8rem; margin-top: 1rem; margin-bottom: 0.5rem;">Admin-Bereich</h1>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">Bitte Passwort und PIN-Code eingeben.</p>
                </div>

                <?php if (!empty($login_error)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; color: #f87171; padding: 0.85rem; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1.5rem; text-align: center;">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="password" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">Passwort</label>
                        <div style="position:relative;">
                            <input type="password" id="password" name="password" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px;" placeholder="Passwort eingeben" required autofocus>
                            <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('password')">visibility</span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.75rem;">
                        <label for="pin" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">PIN-Code</label>
                        <div style="position:relative;">
                            <input type="password" id="pin" name="pin" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px; letter-spacing: 3px;" placeholder="z. B. 1977" maxlength="10" required>
                            <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('pin')">pin</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; border-radius: 10px; font-weight: 600; font-size: 1rem;">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">lock_open</span> Einloggen
                    </button>
                </form>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="../" style="font-size: 0.9rem; color: var(--text-secondary); text-decoration: none;">&larr; Zurück zur Website</a>
                </div>
            </div>
        </div>
        <script src="../libs/js/admin.js?v=<?php echo file_exists('../libs/js/admin.js') ? filemtime('../libs/js/admin.js') : time(); ?>"></script>
    </body>
    </html>
    <?php
    exit;
}

$success_msg = '';
$error_msg = '';
$active_tab = 'tab-texts';

// 7. CSRF-Token für Sitzung generieren oder abrufen
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF-Schutz: Token zwingend validieren (verhindert fremde/automatisierte Manipulationen)
    if (!isset($_POST['login'])) {
        $submitted_csrf = $_POST['csrf_token'] ?? '';
        if (empty($submitted_csrf) || !hash_equals($_SESSION['csrf_token'], $submitted_csrf)) {
            $error_msg = 'Sicherheitswarnung (CSRF): Ungültiges oder abgelaufenes Sicherheits-Token. Die Aktion wurde abgebrochen.';
            $action = ''; // Manipulation blockieren
        }
    }

    if (strpos($action, 'gallery') !== false || strpos($action, 'category') !== false) {
        $active_tab = 'tab-gallery';
    } elseif (strpos($action, 'course') !== false) {
        $active_tab = 'tab-courses';
    } elseif (strpos($action, 'past') !== false) {
        $active_tab = 'tab-past';
    } elseif (strpos($action, 'security') !== false || strpos($action, 'smtp') !== false || strpos($action, 'credentials') !== false) {
        $active_tab = 'tab-security';
    } elseif (strpos($action, 'settings') !== false || strpos($action, 'design') !== false) {
        $active_tab = 'tab-settings';
    } elseif (strpos($action, 'contact') !== false || strpos($action, 'impressum') !== false) {
        $active_tab = 'tab-impressum';
    } elseif (strpos($action, 'texts') !== false) {
        $active_tab = 'tab-texts';
    }

    /**
     * AKTION: Darstellung & Design (Galerie-Layout, Logo, Farbthemen, Maße) aktualisieren
     */
    if ($action === 'update_settings' || $action === 'update_design') {
        // Galerie-Layout prüfen & abspeichern
        $gallery_layout = $_POST['gallery_layout'] ?? 'ambient_blur';
        if (!in_array($gallery_layout, ['ambient_blur', 'masonry', 'editorial', 'classic'])) {
            $gallery_layout = 'ambient_blur';
        }
        $data['settings']['gallery_layout'] = $gallery_layout;
        $data['settings']['gallery_lightbox'] = !empty($_POST['gallery_lightbox']);

        // Hundetatzen-Motiv prüfen & abspeichern
        $hero_logo_motif = $_POST['hero_logo_motif'] ?? 'paw_simple';
        if (!in_array($hero_logo_motif, ['paw_simple', 'paw_beach', 'paw_willow', 'none'])) {
            $hero_logo_motif = 'paw_simple';
        }
        $data['settings']['hero_logo_motif'] = $hero_logo_motif;

        // Logo-Akzentfarbe validieren & speichern
        $hero_logo_color = trim($_POST['hero_logo_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $hero_logo_color)) {
            $hero_logo_color = '#b26c39';
        }
        $data['settings']['hero_logo_color'] = $hero_logo_color;

        // Logoposition validieren & speichern (Header vor/nach oder Hero vor/nach)
        $logo_position = $_POST['logo_position'] ?? 'header_before';
        if (!in_array($logo_position, ['header_before', 'header_after', 'hero_before', 'hero_after'])) {
            $logo_position = 'header_before';
        }
        $data['settings']['logo_position'] = $logo_position;

        // Dynamisches Design- & Styling-System (Hybrid-Modell)
        $design_theme = $_POST['design_theme'] ?? 'nature_classic';
        if (!in_array($design_theme, ['nature_classic', 'moor_amber', 'nordic_sage', 'terracotta_atelier', 'custom'])) {
            $design_theme = 'nature_classic';
        }
        $data['settings']['design_theme'] = $design_theme;

        // Container-Breite (Kompakt 1050px / Standard 1200px / Großzügig 1400px)
        $container_width = $_POST['container_width'] ?? '1200px';
        if (!in_array($container_width, ['1050px', '1200px', '1400px'])) {
            $container_width = '1200px';
        }
        $data['settings']['container_width'] = $container_width;

        // Primärfarbe (Buttons, Links, aktive Filter)
        $primary_color = trim($_POST['primary_color'] ?? '#2e6248');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $primary_color)) {
            $primary_color = '#2e6248';
        }
        $data['settings']['primary_color'] = $primary_color;

        // Akzentfarbe (Highlights, Badges, Kursüberschrift)
        $accent_color = trim($_POST['accent_color'] ?? '#b26c39');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $accent_color)) {
            $accent_color = '#b26c39';
        }
        $data['settings']['accent_color'] = $accent_color;

        // Hintergrundfarbe
        $bg_color = trim($_POST['bg_color'] ?? '#fbf9f6');
        if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $bg_color)) {
            $bg_color = '#fbf9f6';
        }
        $data['settings']['bg_color'] = $bg_color;

        // Typografie-Skalierung
        $font_scale = $_POST['font_scale'] ?? '100%';
        if (!in_array($font_scale, ['95%', '100%', '108%'])) {
            $font_scale = '100%';
        }
        $data['settings']['font_scale'] = $font_scale;

        // Galerie Kachel-Eckenabrundung (Border Radius)
        $card_radius = $_POST['card_radius'] ?? '20px';
        if (!in_array($card_radius, ['10px', '20px', '28px'])) {
            $card_radius = '20px';
        }
        $data['settings']['card_radius'] = $card_radius;

        // Galerie Kachel-Abstand (Gap)
        $gallery_gap = $_POST['gallery_gap'] ?? '2rem';
        if (!in_array($gallery_gap, ['1.25rem', '2rem', '2.75rem'])) {
            $gallery_gap = '2rem';
        }
        $data['settings']['gallery_gap'] = $gallery_gap;

        if (save_data($data_file, $data)) {
            $success_msg = 'Darstellung &amp; Design-Einstellungen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Darstellungseinstellungen.';
        }
    }

    /**
     * AKTION: Admin-Zugangsdaten (Passwort & PIN) ändern (gesichert durch aktuelle PIN)
     */
    if ($action === 'update_credentials') {
        $current_pin = trim($_POST['current_pin'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $new_pin = trim($_POST['new_pin'] ?? '');

        if (empty($new_password) && empty($new_pin)) {
            $error_msg = 'Bitte gib ein neues Passwort oder eine neue PIN ein, die du setzen möchtest.';
        } elseif (empty($current_pin)) {
            $error_msg = 'Sicherheitsabfrage erforderlich: Bitte gib deine aktuell noch gültige PIN ein, um Passwort oder PIN zu ändern!';
        } elseif (!verify_admin_pin($current_pin, $data)) {
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

            if ($credentials_changed && save_data($data_file, $data)) {
                $auth_hash = md5(($data['settings']['admin_password_hash'] ?? DEFAULT_ADMIN_PASSWORD) . ':' . ($data['settings']['admin_pin_hash'] ?? DEFAULT_ADMIN_PIN));
                setcookie('admin_auth', $auth_hash, time() + 86400 * 30, '/');
                $success_msg = '✓ Admin-Zugangsdaten (Passwort / PIN) wurden erfolgreich geändert!';
            } else {
                $error_msg = 'Fehler beim Speichern der neuen Zugangsdaten.';
            }
        }
    }

    /**
     * AKTION: Spamschutz-, SMTP- & Upload-Einstellungen speichern (OHNE PIN-Abfrage)
     */
    if ($action === 'update_security') {
        $upload_dir = trim($_POST['upload_dir'] ?? 'images/uploads');
        if (empty($upload_dir)) {
            $upload_dir = 'images/uploads';
        }
        $data['settings']['upload_dir'] = $upload_dir;

        // Physisches Upload-Verzeichnis sicherstellen
        $target_abs = __DIR__ . '/../' . trim($upload_dir, '/\\');
        if (!is_dir($target_abs)) {
            mkdir($target_abs, 0755, true);
        }

        // SMTP- & E-Mail-Versandeinstellungen sichern
        $smtp_enabled = !empty($_POST['smtp_enabled']);
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = (int)($_POST['smtp_port'] ?? 587);
        $smtp_secure = trim($_POST['smtp_secure'] ?? 'tls');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass_new = $_POST['smtp_pass'] ?? '';
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'Biankas Flechtglück');

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

        // Captcha-Symbole für Spamschutz im Kontaktformular sichern
        $submitted_captcha = $_POST['captcha_icons'] ?? [];
        $captcha_error = false;
        if (is_array($submitted_captcha)) {
            $all_available_icons = get_all_captcha_icons();
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
            if (save_data($data_file, $data)) {
                $success_msg = '✓ Spamschutz-, SMTP- und Upload-Einstellungen erfolgreich gespeichert.';
            } else {
                $error_msg = 'Fehler beim Speichern der Einstellungen.';
            }
        }
    }

    /**
     * AKTION: SMTP-Verbindungstest durchführen
     */
    if ($action === 'test_smtp') {
        require_once __DIR__ . '/../libs/php/Mailer.php';
        $test_to = trim($_POST['test_to_email'] ?? ($data['texts']['contact_email'] ?? ''));
        if (empty($test_to) || !filter_var($test_to, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Bitte gib eine gültige Ziel-E-Mail-Adresse für den SMTP-Test an.';
        } else {
            $cur_smtp = $data['settings']['smtp'] ?? [];
            if (empty($cur_smtp['enabled']) || empty($cur_smtp['host'])) {
                $error_msg = 'SMTP ist nicht aktiviert oder es ist kein SMTP-Host eingetragen. Bitte aktiviere SMTP und speichere die Einstellungen zuerst.';
            } else {
                $test_subject = 'SMTP-Verbindungstest | Biankas Flechtglück';
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
        }
    }

    /**
     * AKTION: Neue Galerie-Kategorie definieren
     */
    if ($action === 'add_category') {
        $new_cat = trim($_POST['category_name'] ?? '');
        if (!empty($new_cat)) {
            if (!isset($data['categories']) || !is_array($data['categories'])) {
                $data['categories'] = ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
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
                if (save_data($data_file, $data)) {
                    $success_msg = 'Kategorie "' . htmlspecialchars($new_cat) . '" erfolgreich angelegt!';
                } else {
                    $error_msg = 'Fehler beim Speichern der Kategorie.';
                }
            } else {
                $error_msg = 'Diese Kategorie existiert bereits.';
            }
        } else {
            $error_msg = 'Bitte gib einen Namen für die Kategorie ein.';
        }
    }

    /**
     * AKTION: Galerie-Kategorie löschen
     * Hinweis: Elemente werden NICHT gelöscht, sondern lediglich auf der Website nicht mehr angezeigt.
     */
    if ($action === 'delete_category') {
        $del_cat = trim($_POST['category_name'] ?? '');
        if (!empty($del_cat)) {
            if (!isset($data['categories']) || !is_array($data['categories'])) {
                $data['categories'] = ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];
            }
            $pos = array_search($del_cat, $data['categories']);
            if ($pos !== false) {
                array_splice($data['categories'], $pos, 1);
                if (save_data($data_file, $data)) {
                    $success_msg = 'Kategorie "' . htmlspecialchars($del_cat) . '" gelöscht. Zugeordnete Galerie-Elemente bleiben erhalten, werden aber im Frontend nicht mehr angezeigt.';
                } else {
                    $error_msg = 'Fehler beim Löschen der Kategorie.';
                }
            } else {
                $error_msg = 'Kategorie nicht gefunden.';
            }
        }
    }

    /**
     * AKTION: Textinhalte für Startseite (Hero) und Über-mich-Bereich aktualisieren
     */
    if ($action === 'update_texts') {
        $data['texts']['hero_title'] = $_POST['hero_title'] ?? '';
        $data['texts']['hero_subtitle'] = $_POST['hero_subtitle'] ?? '';
        $data['texts']['about_title'] = $_POST['about_title'] ?? '';
        $data['texts']['about_text'] = $_POST['about_text'] ?? '';

        if (save_data($data_file, $data)) {
            $success_msg = 'Startseiten- & Über-mich-Texte erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Texte.';
        }
    }

    /**
     * AKTION: Impressum & Kontaktdaten (Adresse, E-Mail, Telefon, Instagram, etc.) aktualisieren
     */
    if ($action === 'update_impressum') {
        $data['texts']['contact_name'] = $_POST['contact_name'] ?? '';
        $data['texts']['contact_street'] = $_POST['contact_street'] ?? '';
        $data['texts']['contact_city'] = $_POST['contact_city'] ?? '';
        $data['texts']['contact_phone'] = $_POST['contact_phone'] ?? '';
        $data['texts']['contact_email'] = $_POST['contact_email'] ?? '';
        $data['texts']['contact_instagram'] = $_POST['contact_instagram'] ?? '';
        $data['texts']['impressum_supervisory'] = $_POST['impressum_supervisory'] ?? '';
        $data['texts']['impressum_vat_id'] = $_POST['impressum_vat_id'] ?? '';
        $data['texts']['impressum_disclaimer'] = $_POST['impressum_disclaimer'] ?? '';
        $data['texts']['contact_mail_greeting'] = trim($_POST['contact_mail_greeting'] ?? 'Hallo Bianka,');
        $data['texts']['course_request_template'] = trim($_POST['course_request_template'] ?? '');

        if (save_data($data_file, $data)) {
            $success_msg = 'Impressum, Kontaktdaten & Mail-Vorlagen erfolgreich gespeichert!';
        } else {
            $error_msg = 'Fehler beim Speichern des Impressums.';
        }
    }

    /**
     * AKTION: Neues Galerie-Werkstück mit Bildupload und Metadaten hinzufügen
     */
    if ($action === 'add_gallery') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $image_path = handle_image_upload('gallery_image');

        if (empty($title)) {
            $error_msg = 'Bitte gib einen Titel für das Galerie-Element an.';
        } elseif (empty($image_path)) {
            $error_msg = 'Fehler beim Upload des Bildes (nur JPG, PNG, WebP und GIF erlaubt).';
        } else {
            // Nächste freie ID & Sortiernummer ermitteln
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

            if (save_data($data_file, $data)) {
                $success_msg = 'Galerie-Element erfolgreich hinzugefügt!';
            } else {
                $error_msg = 'Fehler beim Speichern der Galerie-Daten.';
            }
        }
    }

    /**
     * AKTION: Galerie-Werkstück und ggf. hochgeladene Originaldatei löschen
     */
    if ($action === 'delete_gallery') {
        $id = (int)($_POST['id'] ?? 0);
        $found = false;
        foreach ($data['gallery'] as $idx => $item) {
            if ($item['id'] === $id) {
                // Physische Bilddatei löschen, falls sie im benutzerdefinierten Uploadordner liegt
                $upload_cfg = get_upload_config($data);
                $rel_prefix = $upload_cfg['rel'] . '/';
                if (!empty($item['image']) && strpos($item['image'], $rel_prefix) === 0) {
                    $filepath = __DIR__ . '/..' . $item['image'];
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
            save_data($data_file, $data);
            $success_msg = 'Galerie-Element erfolgreich gelöscht!';
        } else {
            $error_msg = 'Galerie-Element nicht gefunden.';
        }
    }

    /**
     * AKTION: Sortierreihenfolge der Galerie-Elemente aktualisieren
     */
    if ($action === 'reorder_gallery') {
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

        if (save_data($data_file, $data)) {
            $success_msg = 'Galerie-Reihenfolge erfolgreich aktualisiert!';
        } else {
            $error_msg = 'Fehler beim Speichern der Reihenfolge.';
        }
    }

    /**
     * AKTION: Neuen Flechtkurs anlegen und eindeutigen Einladungslink generieren
     */
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

            // URL-freundlichen Slug für den Einladungstoken erzeugen
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

            if (save_data($data_file, $data)) {
                $success_msg = 'Flechtkurs erfolgreich angelegt und Einladungslink generiert!';
            } else {
                $error_msg = 'Fehler beim Speichern des Kurses.';
            }
        }
    }

    /**
     * AKTION: Bestehenden Flechtkurs aktualisieren (Termin, Ort, Teilnehmer, Status)
     */
    if ($action === 'edit_course') {
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

        if ($found && save_data($data_file, $data)) {
            $success_msg = 'Flechtkurs erfolgreich aktualisiert!';
        } else {
            $error_msg = 'Fehler beim Aktualisieren des Kurses.';
        }
    }

    /**
     * AKTION: Flechtkurs unwiderruflich löschen
     */
    if ($action === 'delete_course') {
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
            save_data($data_file, $data);
            $success_msg = 'Flechtkurs erfolgreich gelöscht!';
        } else {
            $error_msg = 'Flechtkurs nicht gefunden.';
        }
    }

    /**
     * AKTION: Neues Foto für die Galerie vergangener Kurse hochladen
     */
    if ($action === 'add_past_course_image') {
        $image_path = handle_image_upload('past_image');
        if (!empty($image_path)) {
            $data['past_courses'][] = $image_path;
            if (save_data($data_file, $data)) {
                $success_msg = 'Bild vergangener Kurse erfolgreich hochgeladen!';
            } else {
                $error_msg = 'Fehler beim Speichern der Kursbilder.';
            }
        } else {
            $error_msg = 'Fehler beim Bildupload.';
        }
    }

    /**
     * AKTION: Foto vergangener Kurse entfernen und physische Datei löschen
     */
    if ($action === 'delete_past_course_image') {
        $img_path = $_POST['image_path'] ?? '';
        $found = false;
        foreach ($data['past_courses'] as $idx => $path) {
            if ($path === $img_path) {
                // Physische Datei löschen
                $upload_cfg = get_upload_config($data);
                $rel_prefix = $upload_cfg['rel'] . '/';
                if (!empty($path) && strpos($path, $rel_prefix) === 0) {
                    $filepath = __DIR__ . '/..' . $path;
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
            save_data($data_file, $data);
            $success_msg = 'Kursbild erfolgreich gelöscht!';
        } else {
            $error_msg = 'Bild nicht in der Liste gefunden.';
        }
    }
}

// 7. Galerie-Elemente im Arbeitsspeicher nach sort_order aufsteigend sortieren
usort($data['gallery'], function($a, $b) {
    return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
});

// 8. Basis-URL für dynamische Einladungslinks ermitteln (Protokoll + Host + Pfad)
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$site_url = $proto . '://' . $host . $base_path . '/';

// 9. Admin-Header Logo vorbereiten
$admin_logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_beach';
$admin_logo_color = $data['settings']['hero_logo_color'] ?? '#d97706';
$admin_logo_pos = $data['settings']['logo_position'] ?? 'header_before';
$admin_paw = render_paw_logo($admin_logo_motif, $admin_logo_color, '1.2em', 'header-paw-logo');

// 10. Galerie-Kategorien & Zähler für Admin vorbereiten
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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration | Bianka Wischnewski</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../libs/css/styles.css?v=<?php echo file_exists('../libs/css/styles.css') ? filemtime('../libs/css/styles.css') : time(); ?>" rel="stylesheet">
</head>
<body class="admin-body">

    <!-- Sticky Header -->
    <header class="header-nav">
        <div class="header-container">
            <span class="logo"><?php if ($admin_logo_pos !== 'header_after'): echo $admin_paw; endif; ?><span>Biankas Flechtglück Admin</span><?php if ($admin_logo_pos === 'header_after'): echo $admin_paw; endif; ?></span>
            <div style="display:flex; align-items:center; gap: 1rem;">
                <a href="../" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Website anzeigen &rarr;</a>
                <a href="?action=logout" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; background:#dc2626;">Abmelden</a>
            </div>
        </div>
    </header>

    <main class="container" style="max-width: 1050px;">
        
        <h1 style="margin-bottom: 2rem;">Verwaltungspanel</h1>

        <!-- Toast Feedback -->
        <div id="toast"><span class="material-icons" style="font-size:18px;">check</span> <span id="toast-text">Link kopiert!</span></div>

        <!-- Success & Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--primary); color: #34d399; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display:flex; align-items:center; gap: 8px;">
                <span class="material-icons">check_circle</span> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; color: #f87171; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display:flex; align-items:center; gap: 8px;">
                <span class="material-icons">error</span> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- ======================================================================
             BACKEND-MENÜ (.admin-nav TAB-NAVIGATION)
             STRIKTE VORGABE: KEIN MENÜPUNKT ODER REITER DARF OHNE VORHERIGE
             AUSDRÜCKLICHE ZUSTIMMUNG DES USERS GEÄNDERT, VERSCHOBEN ODER ENTFERNT WERDEN!
             ====================================================================== -->
        <div class="admin-nav">
            <button class="admin-nav-link active" onclick="switchTab('tab-texts')"><span class="material-icons" style="font-size:18px;">edit_note</span> Startseite &amp; Über mich</button>
            <button class="admin-nav-link" onclick="switchTab('tab-impressum')"><span class="material-icons" style="font-size:18px;">contact_page</span> Impressum &amp; Kontaktdaten</button>
            <button class="admin-nav-link" onclick="switchTab('tab-gallery')"><span class="material-icons" style="font-size:18px;">photo_library</span> Galerie</button>
            <button class="admin-nav-link" onclick="switchTab('tab-past')"><span class="material-icons" style="font-size:18px;">photo_camera</span> Kursimpressionen</button>
            <button class="admin-nav-link" onclick="switchTab('tab-courses')"><span class="material-icons" style="font-size:18px;">event</span> Flechtkurse</button>
            <button class="admin-nav-link" onclick="switchTab('tab-settings')"><span class="material-icons" style="font-size:18px;">palette</span> Darstellung</button>
            <button class="admin-nav-link" onclick="switchTab('tab-security')"><span class="material-icons" style="font-size:18px;">security</span> Sicherheit</button>
        </div>

        <!-- ──────────────── SECTION 1: HOMEPAGE & ABOUT TEXTS ──────────────── -->
        <div id="tab-texts" class="admin-section active card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Startseite &amp; Über-mich-Texte</h2>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_texts">
                
                <h3 style="font-size: 1.2rem; margin-top: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">Startseite &amp; Hero-Bereich</h3>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Hero-Titel (große Hauptüberschrift)</label>
                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($data['texts']['hero_title'] ?? ''); ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Hero-Untertitel</label>
                    <input type="text" name="hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($data['texts']['hero_subtitle'] ?? ''); ?>" required>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">Über Mich &amp; Meine Flechtkunst</h3>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Über Mich - Überschrift</label>
                    <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars($data['texts']['about_title'] ?? ''); ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Über Mich - Beschreibungstext</label>
                    <textarea name="about_text" class="form-control" rows="7" required><?php echo htmlspecialchars($data['texts']['about_text'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Startseiten-Texte speichern
                </button>
            </form>
        </div>

        <!-- ──────────────── SECTION 2: IMPRESSUM & CONTACT DATA ──────────────── -->
        <div id="tab-impressum" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">📋 Impressum &amp; Kontaktdaten verwalten</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem;">
                Hier kannst du alle Adress-, Kontakt- und Impressumsdaten anpassen. Diese werden automatisch im Ausklappmenü (Kontakt &amp; Impressum) sowie im Fußbereich der Website angezeigt.
            </p>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_impressum">
                
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    👤 Betreiber &amp; Kontaktdaten
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Vollständiger Name / Inhaberin</label>
                        <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Straße &amp; Hausnummer</label>
                        <input type="text" name="contact_street" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_street'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>PLZ &amp; Ort</label>
                        <input type="text" name="contact_city" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_city'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefonnummer</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_phone'] ?? ''); ?>" placeholder="+49 173 ...">
                    </div>
                    <div class="form-group">
                        <label>E-Mail-Adresse</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Instagram-Account Name</label>
                        <input type="text" name="contact_instagram" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_instagram'] ?? ''); ?>" placeholder="z. B. biankas.flechtglueck">
                    </div>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    ⚖️ Rechtliche Angaben (§ 5 TMG Impressum)
                </h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Zuständige Aufsichtsbehörde / Kammer</label>
                        <input type="text" name="impressum_supervisory" class="form-control" value="<?php echo htmlspecialchars($data['texts']['impressum_supervisory'] ?? 'Handwerkskammer Schwerin'); ?>" placeholder="z. B. Handwerkskammer Schwerin">
                    </div>
                    <div class="form-group">
                        <label>Umsatzsteuer-ID / Steuerhinweis</label>
                        <input type="text" name="impressum_vat_id" class="form-control" value="<?php echo htmlspecialchars($data['texts']['impressum_vat_id'] ?? 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmerregelung).'); ?>" placeholder="z. B. Kleinunternehmer gem. § 19 UStG">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Haftungsausschluss / Rechtliche Hinweise</label>
                    <textarea name="impressum_disclaimer" class="form-control" rows="4"><?php echo htmlspecialchars($data['texts']['impressum_disclaimer'] ?? 'Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.'); ?></textarea>
                </div>

                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    ✉️ E-Mail-Texte &amp; Vorlagen
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height: 1.4;">
                    Passe hier die vorgefertigten Texte an, die im Kontaktformular und bei Kursanfragen generiert werden.
                </p>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>E-Mail-Anrede / Begrüßung (z. B. "Hallo Bianka,")</label>
                    <input type="text" name="contact_mail_greeting" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_mail_greeting'] ?? 'Hallo Bianka,'); ?>" placeholder="z. B. Hallo Bianka,">
                    <small style="color:var(--text-secondary); font-size:0.8rem; margin-top:4px; display:block;">
                        Wird als persönliche Anrede an den Anfang der generierten Nachricht gesetzt.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Textvorlage für Kursanfragen (beim Klick auf "Kurs anfragen")</label>
                    <textarea name="course_request_template" class="form-control" rows="4"><?php echo htmlspecialchars($data['texts']['course_request_template'] ?? "Hallo Bianka,\n\nich interessiere mich für deinen Kurs \"{kurs}\" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\n\nViele Grüße"); ?></textarea>
                    <small style="color:var(--text-secondary); font-size:0.8rem; margin-top:4px; display:block;">
                        Verfügbare Platzhalter: <code>{kurs}</code>, <code>{datum}</code>, <code>{uhrzeit}</code>, <code>{ort}</code>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Impressum, Kontaktdaten &amp; Mail-Vorlagen speichern
                </button>
            </form>
        </div>

        <!-- ──────────────── SECTION 2: GALLERY ──────────────── -->
        <div id="tab-gallery" class="admin-section">
            <!-- Active Layout Info Banner -->
            <?php 
                $active_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur';
                $layout_names = [
                    'ambient_blur' => 'Smart Ambient-Blur (Ruhiges Raster & voller Bildinhalt)',
                    'masonry' => 'Masonry-Grid (Pinterest-Stil / Natürliche Bildhöhen)',
                    'editorial' => 'Magazin-Raster (Editorial Grid / Hochkant über 2 Zeilen)',
                    'classic' => 'Klassisches 4:3 Raster (Fester Zuschnitt)'
                ];
                $layout_hints = [
                    'ambient_blur' => 'Ideal für gemischte Formate. Querformat: 1200×900 px (4:3) oder 1000×1000 px. Hochkant-Bilder (z. B. 3:4, 9:16 ab 1000 px) werden ohne Beschnitt mit weichgezeichnetem Bildrahmen dargestellt.',
                    'masonry' => 'Empfohlene Bildbreite: ca. 800–1400 px. Höhe beliebig – jedes Bild wird 100% vollständig in seiner natürlichen Proportion abgebildet.',
                    'editorial' => 'Querformat: 1200×900 px (4:3). Hochformat: 900×1200 px (spannen automatisch über 2 Rasterzeilen).',
                    'classic' => 'Exakt 1200×900 px (4:3 Querformat). Wichtige Bildmotive müssen mittig liegen, da Außenränder beschnitten werden.'
                ];
            ?>
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 14px; padding: 1.25rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                        <span class="material-icons" style="color:var(--primary); font-size:22px;">view_quilt</span>
                        <strong style="color:var(--text-primary); font-size:1.05rem;">Aktives Galerie-Layout:</strong>
                        <span class="layout-badge"><?php echo htmlspecialchars($layout_names[$active_layout] ?? $active_layout); ?></span>
                    </div>
                    <div style="color:var(--text-secondary); font-size:0.88rem; line-height:1.45; margin-top:4px;">
                        💡 <b>Empfohlene Bildgröße:</b> <?php echo htmlspecialchars($layout_hints[$active_layout] ?? ''); ?>
                    </div>
                </div>
                <button type="button" class="btn btn-outline" onclick="switchTab('tab-settings')" style="padding:6px 14px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:16px;">tune</span> Layout &amp; Formate ändern
                </button>
            </div>

            <!-- Galerie-Kategorien verwalten (Definition & Löschen) -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">category</span> Galerie-Kategorien verwalten
                </h2>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height: 1.5;">
                    Lege hier eigene Kategorien für deine Flechtwerke fest oder entferne nicht mehr benötigte Kategorien. 
                    <br><em>Hinweis:</em> Beim Löschen einer Kategorie bleiben die zugeordneten Werkstücke erhalten, werden aber im Galerie-Filter auf der Website nicht mehr angezeigt.
                </p>

                <!-- Neue Kategorie hinzufügen -->
                <form method="POST" action="" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin-bottom: 1.75rem; background: rgba(0,0,0,0.02); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color);">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group" style="flex:1; min-width:240px; margin-bottom:0;">
                        <label style="font-weight:600; margin-bottom:6px; display:block;">Neue Kategorie anlegen</label>
                        <input type="text" name="category_name" class="form-control" placeholder="z. B. Rankhilfen, Weidentaschen..." required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height:44px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:18px;">add</span> Kategorie hinzufügen
                    </button>
                </form>

                <!-- Vorhandene Kategorien auflisten -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                    Vorhandene Kategorien (<?php echo count($admin_categories); ?>)
                </label>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($admin_categories as $catItem): 
                        $cCount = $cat_counts[$catItem] ?? 0;
                    ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); padding:0.75rem 1.25rem; border-radius:10px; border:1px solid var(--border-color); flex-wrap:wrap; gap:0.75rem;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="material-icons" style="color:var(--primary); font-size:20px;">label</span>
                                <strong style="font-size:1.02rem;"><?php echo htmlspecialchars($catItem); ?></strong>
                                <span style="font-size:0.8rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:2px 8px; border-radius:12px; font-weight:600;">
                                    <?php echo $cCount; ?> <?php echo ($cCount === 1) ? 'Werkstück' : 'Werkstücke'; ?>
                                </span>
                            </div>
                            <form method="POST" action="" onsubmit="return confirmCategoryDelete(<?php echo htmlspecialchars(json_encode($catItem)); ?>, <?php echo (int)$cCount; ?>);" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($catItem); ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 14px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px; background:#dc2626;">
                                    <span class="material-icons" style="font-size:16px;">delete</span> Löschen
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Add gallery item -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neues Galerie-Element hinzufügen</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_gallery">
                    
                    <div class="form-group">
                        <label>Titel des Flechtwerks</label>
                        <input type="text" name="title" class="form-control" placeholder="z. B. Einkaufskorb Weide" required>
                    </div>

                    <div class="form-group">
                        <label>Beschreibung</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Details zur Herstellung oder Nutzung..."></textarea>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Kategorie</label>
                            <select name="category" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <?php foreach ($admin_categories as $catOption): ?>
                                    <option value="<?php echo htmlspecialchars($catOption); ?>"><?php echo htmlspecialchars($catOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto hochladen (JPG, PNG, WebP, GIF)</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">add_photo_alternate</span> Zur Galerie hinzufügen
                    </button>
                </form>
            </div>

            <!-- List and Reorder -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Galerie verwalten &amp; sortieren</h2>
                <?php if (empty($data['gallery'])): ?>
                    <p style="color: var(--text-secondary);">Keine Arbeiten in der Galerie.</p>
                <?php else: ?>
                    <form method="POST" action="" style="margin-bottom: 2rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="reorder_gallery">
                        <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom: 1.5rem;">
                            <?php foreach ($data['gallery'] as $item): ?>
                                <div style="display:flex; align-items:center; gap:1rem; background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px; border:1px solid var(--border-color); flex-wrap:wrap;">
                                    <img src="..<?php echo htmlspecialchars($item['image']); ?>" class="preview-img" alt="">
                                    <div style="flex:1; min-width:200px;">
                                        <h4 style="margin:0; font-size:1.1rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <span style="display:inline-block; font-size:0.75rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:2px 8px; border-radius:10px; margin-top:3px;"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <label style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Sortierung:</label>
                                        <input type="number" name="order[<?php echo $item['id']; ?>]" value="<?php echo htmlspecialchars($item['sort_order'] ?? 0); ?>" class="form-control" style="width:60px; padding:6px; margin:0;" min="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-outline" style="width:100%;">
                            <span class="material-icons" style="font-size:18px; vertical-align:middle;">sort</span> Reihenfolge aktualisieren
                        </button>
                    </form>

                    <h3 style="font-size:1.2rem; margin-top:2rem; margin-bottom:1rem; border-top:1px solid var(--border-color); padding-top:1.5rem;">Galerie-Elemente löschen</h3>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach ($data['gallery'] as $item): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(239, 68, 68, 0.02); padding:0.75rem 1rem; border-radius:12px; border:1px solid rgba(239,68,68,0.15); flex-wrap:wrap; gap:1rem;">
                                <div style="display:flex; align-items:center; gap:1rem;">
                                    <img src="..<?php echo htmlspecialchars($item['image']); ?>" style="width:40px; height:40px; object-fit:cover; border-radius:6px;" alt="">
                                    <span style="font-weight:600;"><?php echo htmlspecialchars($item['title']); ?></span>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Möchtest du dieses Bild wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_gallery">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:6px 12px; font-size:0.85rem;">Löschen</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ──────────────── SECTION 3: WORKSHOPS/COURSES ──────────────── -->
        <div id="tab-courses" class="admin-section">
            <!-- Add new Course -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neuen Flechtkurs planen &amp; Einladungslink generieren</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_course">
                    
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label>Kurstitel</label>
                            <input type="text" name="title" class="form-control" placeholder="z. B. Grundkurs Weidenflechten" required>
                        </div>
                        <div class="form-group">
                            <label>Datum</label>
                            <input type="text" name="date_string" class="form-control" placeholder="z. B. Samstag, 15. Oktober 2026" required>
                        </div>
                        <div class="form-group">
                            <label>Uhrzeit</label>
                            <input type="text" name="time" class="form-control" placeholder="z. B. 10:00 - 16:00 Uhr" value="10:00 - 16:00 Uhr" required>
                        </div>
                        <div class="form-group">
                            <label>Veranstaltungsort</label>
                            <input type="text" name="location" class="form-control" placeholder="z. B. Kraaker Straße 14, 19077 Rastow" value="Werkstatt Kraaker Straße 14, 19077 Rastow" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Kursbeschreibung &amp; Inhalte</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Was lernen die Teilnehmer? Welche Materialien und Pausengetränke sind inklusive?"></textarea>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Preis (z. B. 85 €)</label>
                            <input type="text" name="price" class="form-control" placeholder="85 €" value="85 €">
                        </div>
                        <div class="form-group">
                            <label>Max. Teilnehmer</label>
                            <input type="number" name="max_participants" class="form-control" value="8" min="1">
                        </div>
                        <div class="form-group">
                            <label>Bereits gebucht</label>
                            <input type="number" name="current_participants" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="available" checked style="width:18px; height:18px;">
                                Kurs ist buchbar (aktiv)
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">event_note</span> Kurs anlegen &amp; Einladungslink erstellen
                    </button>
                </form>
            </div>

            <!-- List, Edit & Delete Courses -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Geplante Flechtkurse verwalten &amp; teilen</h2>
                <?php if (empty($data['courses'])): ?>
                    <p style="color: var(--text-secondary);">Aktuell sind keine Kurse eingetragen.</p>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:2rem;">
                        <?php foreach ($data['courses'] as $c): ?>
                            <?php 
                                $course_link = $site_url . '?course=' . $c['id'];
                                $wa_text = urlencode("Hallo! Schau dir mal den Flechtkurs \"" . $c['title'] . "\" bei Bianka an: " . $c['date_string'] . " (" . ($c['time'] ?? '') . ") in " . ($c['location'] ?? 'Rastow') . " für " . $c['price'] . ". Hier buchen: " . $course_link);
                            ?>
                            <div style="background:rgba(255,255,255,0.01); border:1px solid var(--border-color); padding:1.5rem; border-radius:16px;">
                                <form method="POST" action="" style="margin-bottom:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="edit_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    
                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                        <div class="form-group">
                                            <label>Kurstitel</label>
                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Datum</label>
                                            <input type="text" name="date_string" class="form-control" value="<?php echo htmlspecialchars($c['date_string']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Uhrzeit</label>
                                            <input type="text" name="time" class="form-control" value="<?php echo htmlspecialchars($c['time'] ?? '10:00 - 16:00 Uhr'); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Veranstaltungsort</label>
                                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($c['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow'); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Kursbeschreibung</label>
                                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($c['description']); ?></textarea>
                                    </div>

                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                        <div class="form-group">
                                            <label>Preis</label>
                                            <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($c['price']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Max. Teilnehmer</label>
                                            <input type="number" name="max_participants" class="form-control" value="<?php echo (int)$c['max_participants']; ?>" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label>Bereits gebucht</label>
                                            <input type="number" name="current_participants" class="form-control" value="<?php echo (int)$c['current_participants']; ?>" min="0">
                                        </div>
                                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                                <input type="checkbox" name="available" <?php echo $c['available'] ? 'checked' : ''; ?> style="width:16px; height:16px;">
                                                Buchbar (Aktiv)
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Einladungslink Box -->
                                    <div class="invite-box">
                                        <div style="display:flex; align-items:center; gap:6px; font-weight:600; font-size:0.85rem; color:var(--primary);">
                                            <span class="material-icons" style="font-size:18px;">link</span> Einladungslink:
                                        </div>
                                        <input type="text" readonly class="invite-link-input" id="link-<?php echo $c['id']; ?>" value="<?php echo htmlspecialchars($course_link); ?>">
                                        <div style="display:flex; gap:6px;">
                                            <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem;" onclick="copyLink('<?php echo htmlspecialchars($course_link); ?>')">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">content_copy</span> Kopieren
                                            </button>
                                            <a href="https://api.whatsapp.com/send?text=<?php echo $wa_text; ?>" target="_blank" class="btn btn-primary" style="padding:4px 10px; font-size:0.8rem; background:#25D366; border-color:#25D366; color:#fff;" title="Per WhatsApp teilen">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">share</span> WhatsApp
                                            </a>
                                        </div>
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:1.5rem; border-top:1px solid var(--border-color); padding-top:1rem;">
                                        <span style="font-size:0.85rem; color:var(--text-secondary);">
                                             Verfügbare Plätze: <strong><?php echo max(0, $c['max_participants'] - $c['current_participants']); ?></strong> von <?php echo $c['max_participants']; ?>
                                        </span>
                                        <div style="display:flex; gap:10px;">
                                            <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.85rem;">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">save</span> Speichern
                                            </button>
                                            <button type="button" class="btn btn-danger" style="padding:6px 12px; font-size:0.85rem;" onclick="deleteCourse(<?php echo $c['id']; ?>)">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">delete</span> Kurs Löschen
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Hidden Delete form for course -->
                                <form id="delete-form-<?php echo $c['id']; ?>" method="POST" action="" style="display:none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ──────────────── SECTION 4: PAST COURSE IMAGES ──────────────── -->
        <div id="tab-past" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Impressionen vergangener Kurse verwalten</h2>
            
            <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add_past_course_image">
                <div class="form-group">
                    <label>Neues Bild hochladen (JPG, PNG, WebP)</label>
                    <input type="file" name="past_image" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">cloud_upload</span> Foto hochladen
                </button>
            </form>

            <h3 style="font-size:1.2rem; border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:2rem;">Hochgeladene Fotos</h3>
            <?php if (empty($data['past_courses'])): ?>
                <p style="color: var(--text-secondary);">Noch keine Bilder hochgeladen.</p>
            <?php else: ?>
                <div class="grid-past-images">
                    <?php foreach ($data['past_courses'] as $img_path): ?>
                        <div class="past-image-card">
                            <img src="..<?php echo htmlspecialchars($img_path); ?>" alt="">
                            <form method="POST" action="" onsubmit="return confirm('Möchtest du dieses Bild löschen?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="delete_past_course_image">
                                <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($img_path); ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 4px; border-radius: 50%; min-width:0; width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:#dc2626; border:none;" title="Bild löschen">
                                    <span class="material-icons" style="font-size:16px;">close</span>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ──────────────── SECTION 5: DARSTELLUNG & DESIGN ──────────────── -->
        <div id="tab-settings" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">🎨 Darstellung &amp; Design</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_settings">

                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">view_quilt</span> Galerie-Layout &amp; Bilddarstellung
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Wähle hier, wie deine Fotos in der Galerie auf der Startseite dargestellt werden sollen. Du kannst das Layout jederzeit wechseln und ausprobieren. Beachte die jeweils empfohlenen Bildgrößen bei der Vorbereitung deiner Fotos:
                </p>

                <?php 
                    $cur_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur'; 
                    $cur_lightbox = !isset($data['settings']['gallery_lightbox']) || !empty($data['settings']['gallery_lightbox']);
                ?>

                <div class="layout-options-grid">
                    <!-- Option B: Smart Ambient-Blur -->
                    <label class="layout-card <?php echo ($cur_layout === 'ambient_blur') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="ambient_blur" <?php echo ($cur_layout === 'ambient_blur') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="layout-badge">⭐ Empfohlen (Kombiniert)</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Smart Ambient-Blur</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Ruhiges, gleichmäßiges Raster mit 100% Motiv-Sichtbarkeit. Das Foto wird vollständig ohne Beschnitt eingepasst und durch eine weichgezeichnete Farbaura des Originalbildes edel gerahmt.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Querformat: <b>1200 × 900 px</b> (4:3) oder Quadrat <b>1000 × 1000 px</b>.<br>
                            Hochkant (z. B. 3:4 oder 9:16): mind. <b>1000 px</b> an der längsten Kante.
                        </div>
                    </label>

                    <!-- Option A: Masonry-Grid -->
                    <label class="layout-card <?php echo ($cur_layout === 'masonry') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="masonry" <?php echo ($cur_layout === 'masonry') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(59, 130, 246, 0.15); color:#2563eb;">Pinterest-Stil</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Masonry-Grid</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Mehrspaltig versetzt mit natürlichen Höhen. Jedes Bild behält seine originale Proportion. Hochkant-Bilder werden hoch und schlank dargestellt, Querformat-Bilder kompakt. Kein Beschnitt.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Jedes Seitenverhältnis möglich.<br>
                            Empfohlene Bildbreite: <b>800 – 1400 px</b> (Höhe passt sich automatisch an).
                        </div>
                    </label>

                    <!-- Option C: Magazin / Editorial -->
                    <label class="layout-card <?php echo ($cur_layout === 'editorial') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="editorial" <?php echo ($cur_layout === 'editorial') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(217, 119, 6, 0.15); color:var(--accent);">Magazin-Look</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Magazin-Raster</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Dynamisches Editorial-Grid. Hochkant-Bilder (wie Personen mit Körben) spannen automatisch über 2 Rasterzeilen und werden zu Blickfängen. Querformat-Bilder füllen normale Kacheln.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Querformat: <b>1200 × 900 px</b> (4:3)<br>
                            Hochformat: <b>900 × 1200 px</b> (3:4 oder 2:3).
                        </div>
                    </label>

                    <!-- Option D: Classic 4:3 -->
                    <label class="layout-card <?php echo ($cur_layout === 'classic') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="gallery_layout" value="classic" <?php echo ($cur_layout === 'classic') ? 'checked' : ''; ?>>
                        <div>
                            <span class="layout-badge" style="background:rgba(0, 0, 0, 0.08); color:var(--text-secondary);">Klassisch</span>
                        </div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);">Klassisch 4:3</h4>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin:0; line-height:1.4;">
                            Strenges, einheitliches Raster. Alle Bilder werden passgenau auf das 4:3-Format zugeschnitten.
                        </p>
                        <div class="layout-rec-box">
                            <strong>📐 Empfohlene Bildgröße:</strong>
                            Exakt <b>1200 × 900 px</b> (4:3 Querformat).<br>
                            <em>Hinweis:</em> Wichtige Motive müssen zentriert sein, da Ränder beschnitten werden.
                        </div>
                    </label>
                </div>

                <!-- Lightbox Toggle -->
                <div style="background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2.5rem; display:flex; align-items:flex-start; gap:12px;">
                    <input type="checkbox" name="gallery_lightbox" id="gallery_lightbox" value="1" <?php echo $cur_lightbox ? 'checked' : ''; ?> style="width:20px; height:20px; margin-top:2px; accent-color:var(--primary); cursor:pointer;">
                    <label for="gallery_lightbox" style="cursor:pointer; margin:0;">
                        <span style="font-weight:700; color:var(--text-primary); font-size:1rem; display:block; margin-bottom:3px;">
                            🔍 Interaktive Lightbox / Vollbild-Zoom aktivieren
                        </span>
                        <span style="color:var(--text-secondary); font-size:0.88rem; line-height:1.4; display:block;">
                            Erlaubt Besuchern, auf ein beliebiges Galerie-Bild zu klicken, um es im Vollbild mit hochauflösender Großansicht, Titel, Beschreibung und Weiterschalt-Pfeilen zu betrachten.
                        </span>
                    </label>
                </div>

                <!-- Website Logo Section -->
                <h3 style="font-size: 1.25rem; margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">pets</span> Website-Logo (Hundetatze im Header)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Wähle das SVG-Motiv und die Farbe der Hundetatze, die oben im Header neben dem Seitentitel angezeigt wird.
                </p>

                <?php 
                    $cur_logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_simple';
                    $cur_logo_color = $data['settings']['hero_logo_color'] ?? '#b26c39';
                ?>

                <!-- Motif Selection Grid -->
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 1.5rem;">
                    <!-- Motif 1: Paw Simple -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_simple') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_simple')">
                        <input type="radio" name="hero_logo_motif" value="paw_simple" <?php echo ($cur_logo_motif === 'paw_simple') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_simple', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Klassische Hundetatze</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Klassischer Pfotenabdruck mit 4 einzeln positionierten Zehen.
                        </p>
                    </label>

                    <!-- Motif 2: Paw Branch (Naturzweig) -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_beach') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_beach')">
                        <input type="radio" name="hero_logo_motif" value="paw_beach" <?php echo ($cur_logo_motif === 'paw_beach') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_beach', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Hundetatze &amp; Naturzweig</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Klassische Tatze mit einem dezenten, geschwungenen Naturzweig darunter.
                        </p>
                    </label>

                    <!-- Motif 3: Paw Willow (Eleganter Weidenzweig) -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'paw_willow') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'paw_willow')">
                        <input type="radio" name="hero_logo_motif" value="paw_willow" <?php echo ($cur_logo_motif === 'paw_willow') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; padding: 10px 0;">
                            <?php echo render_paw_logo('paw_willow', $cur_logo_color, 48); ?>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Hundetatze &amp; Weidenzweig</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Tatze eingefasst von einem kunstvoll geschwungenen Weidenast mit Weidenblättern.
                        </p>
                    </label>

                    <!-- Motif 4: None -->
                    <label class="layout-card <?php echo ($cur_logo_motif === 'none') ? 'selected' : ''; ?>" onclick="selectLogoMotif(this, 'none')">
                        <input type="radio" name="hero_logo_motif" value="none" <?php echo ($cur_logo_motif === 'none') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; height: 50px; font-size:1.8rem; color:var(--text-secondary); opacity:0.5;">
                            🚫
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; text-align:center; color:var(--text-primary);">Kein Logo</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Nur Text im Header ohne Pfotengrafik anzeigen.
                        </p>
                    </label>
                </div>

                <!-- Logo Position Section -->
                <?php 
                    $cur_logo_pos = $data['settings']['logo_position'] ?? 'header_before';
                ?>
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    📍 Position des Logos
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.25rem; line-height:1.4;">
                    Bestimme, wo das Logo platziert werden soll. Die Größe passt sich automatisch mittig an die Schrifthöhe daneben an.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- Pos 1: Header Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_before')">
                        <input type="radio" name="logo_position" value="header_before" <?php echo ($cur_logo_pos === 'header_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.2em'); ?> <span>Titel</span>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Header: Vor Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Klassisch links vor dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 2: Header After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'header_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'header_after')">
                        <input type="radio" name="logo_position" value="header_after" <?php echo ($cur_logo_pos === 'header_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:800; font-size:1.1rem; color:var(--text-primary); padding:10px 0;">
                            <span>Titel</span> <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.2em'); ?>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Header: Nach Seitentitel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            Rechts direkt hinter dem Website-Namen in der Kopfzeile.
                        </p>
                    </label>

                    <!-- Pos 3: Hero Before -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_before') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_before')">
                        <input type="radio" name="logo_position" value="hero_before" <?php echo ($cur_logo_pos === 'hero_before') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.15em'); ?> <span>Hero-Zeile</span>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Hero: Vor dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            In der gleichen Zeile zentriert links vor der Hauptüberschrift.
                        </p>
                    </label>

                    <!-- Pos 4: Hero After -->
                    <label class="layout-card <?php echo ($cur_logo_pos === 'hero_after') ? 'selected' : ''; ?>" onclick="selectLogoPosition(this, 'hero_after')">
                        <input type="radio" name="logo_position" value="hero_after" <?php echo ($cur_logo_pos === 'hero_after') ? 'checked' : ''; ?>>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; font-weight:700; font-size:1.05rem; color:var(--text-primary); padding:10px 0;">
                            <span>Hero-Zeile</span> <?php echo render_paw_logo($cur_logo_motif, $cur_logo_color, '1.15em'); ?>
                        </div>
                        <h4 style="margin:0; font-size:0.98rem; text-align:center; color:var(--text-primary);">Hero: Nach dem Titel</h4>
                        <p style="color:var(--text-secondary); font-size:0.8rem; margin:0; text-align:center; line-height:1.3;">
                            In der gleichen Zeile zentriert rechts hinter der Hauptüberschrift.
                        </p>
                    </label>
                </div>

                <!-- Color Selection & Chips -->
                <div class="form-group" style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2.5rem;">
                    <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                        🎨 Farbe der Hundetatze
                    </label>
                    <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="hero_logo_color" name="hero_logo_color" value="<?php echo htmlspecialchars($cur_logo_color); ?>" oninput="updateLogoColor(this.value)" style="width:44px; height:44px; border:1px solid var(--border-color); border-radius:10px; cursor:pointer; background:transparent;">
                            <span id="color_code_display" style="font-family:monospace; font-size:0.95rem; font-weight:600; color:var(--text-primary);"><?php echo htmlspecialchars($cur_logo_color); ?></span>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(178,108,57,0.3); color:#b26c39; font-weight:600;" onclick="setColorPreset('#b26c39')">🌿 Weiden-Braun (#b26c39)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(46,98,72,0.3); color:#2e6248; font-weight:600;" onclick="setColorPreset('#2e6248')">🌲 Wald-Grün (#2e6248)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(217,119,6,0.3); color:#d97706; font-weight:600;" onclick="setColorPreset('#d97706')">☀️ Bernstein (#d97706)</button>
                            <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.82rem; border-color:rgba(60,52,46,0.3); color:#3c342e; font-weight:600;" onclick="setColorPreset('#3c342e')">☕ Anthrazit (#3c342e)</button>
                        </div>
                    </div>
                </div>

                <!-- ==========================================================
                     🎨 SEITEN-LAYOUT & DESIGN-SYSTEM (HYBRID-MODELL)
                     ========================================================== -->
                <h3 style="font-size: 1.25rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">palette</span> Seiten-Layout &amp; Design-System
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 1.5rem; line-height:1.5;">
                    Passe das optische Erscheinungsbild deiner Website an. Wähle eines der harmonischen Natur-Farbthemen (1-Klick) oder stimme Farben, Seitenbreite, Schriftgrößen und Galerie-Abstände ganz nach deinen Wünschen fein ab.
                </p>

                <?php
                    $cur_theme = $data['settings']['design_theme'] ?? 'nature_classic';
                    $cur_width = $data['settings']['container_width'] ?? '1200px';
                    $cur_primary = $data['settings']['primary_color'] ?? '#2e6248';
                    $cur_accent = $data['settings']['accent_color'] ?? '#b26c39';
                    $cur_bg = $data['settings']['bg_color'] ?? '#fbf9f6';
                    $cur_font_scale = $data['settings']['font_scale'] ?? '100%';
                    $cur_card_radius = $data['settings']['card_radius'] ?? '20px';
                    $cur_gallery_gap = $data['settings']['gallery_gap'] ?? '2rem';
                ?>

                <!-- 1. Kuratierte 1-Klick-Natur-Themes -->
                <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:block;">
                    🌿 1. Natur-Farbthemen (1-Klick Vorlagen)
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Beim Klick auf ein Farbthema werden die passenden Primär-, Akzent- und Hintergrundfarben automatisch vorausgewählt.
                </p>

                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- Theme 1: Weidenglück (Standard) -->
                    <label class="layout-card <?php echo ($cur_theme === 'nature_classic') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nature_classic')">
                        <input type="radio" name="design_theme" value="nature_classic" <?php echo ($cur_theme === 'nature_classic') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#2e6248;" title="Primär: Waldgrün"></span>
                            <span class="theme-swatch" style="background:#b26c39;" title="Akzent: Weidenton"></span>
                            <span class="theme-swatch" style="background:#fbf9f6; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Leinenweiß"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Weidenglück <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Standard</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Das bewährte Originaldesign: Sanftes Naturleinen, Waldgrün und warmer Weidenton.
                        </p>
                    </label>

                    <!-- Theme 2: Moor & Bernstein -->
                    <label class="layout-card <?php echo ($cur_theme === 'moor_amber') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'moor_amber')">
                        <input type="radio" name="design_theme" value="moor_amber" <?php echo ($cur_theme === 'moor_amber') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#4a3f35;" title="Primär: Moosbraun"></span>
                            <span class="theme-swatch" style="background:#d97706;" title="Akzent: Bernstein"></span>
                            <span class="theme-swatch" style="background:#f7f4ee; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Sandcreme"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Moor &amp; Bernstein</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Warme Erdtöne: Sanfter Sandstrand, tiefes Moosbraun und sonniges Bernstein.
                        </p>
                    </label>

                    <!-- Theme 3: Nordisch Klar -->
                    <label class="layout-card <?php echo ($cur_theme === 'nordic_sage') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'nordic_sage')">
                        <input type="radio" name="design_theme" value="nordic_sage" <?php echo ($cur_theme === 'nordic_sage') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#3a6351;" title="Primär: Salbeigrün"></span>
                            <span class="theme-swatch" style="background:#2c3e50;" title="Akzent: Schieferblau"></span>
                            <span class="theme-swatch" style="background:#f4f6f5; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Hellgrau"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Nordisch Klar</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Ruhig und dezent: Skandinavisches Hellgrau, zartes Salbeigrün und Schiefer.
                        </p>
                    </label>

                    <!-- Theme 4: Warmes Atelier -->
                    <label class="layout-card <?php echo ($cur_theme === 'terracotta_atelier') ? 'selected' : ''; ?>" onclick="selectDesignTheme(this, 'terracotta_atelier')">
                        <input type="radio" name="design_theme" value="terracotta_atelier" <?php echo ($cur_theme === 'terracotta_atelier') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch" style="background:#a85832;" title="Primär: Terrakotta"></span>
                            <span class="theme-swatch" style="background:#322822;" title="Akzent: Espresso"></span>
                            <span class="theme-swatch" style="background:#faf7f2; border:1px solid rgba(0,0,0,0.15);" title="Hintergrund: Elfenbein"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Warmes Atelier</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Künstlerisch &amp; behaglich: Elfenbein, gebrannte Terrakotta und dunkles Holz.
                        </p>
                    </label>

                    <!-- Theme 5: Custom (Individuell) -->
                    <label class="layout-card <?php echo ($cur_theme === 'custom') ? 'selected' : ''; ?>" id="theme_card_custom" onclick="selectDesignTheme(this, 'custom')">
                        <input type="radio" name="design_theme" value="custom" <?php echo ($cur_theme === 'custom') ? 'checked' : ''; ?>>
                        <div style="display:flex; gap:6px; margin: 6px 0 12px 0;">
                            <span class="theme-swatch custom-swatch-primary" style="background:<?php echo htmlspecialchars($cur_primary); ?>;"></span>
                            <span class="theme-swatch custom-swatch-accent" style="background:<?php echo htmlspecialchars($cur_accent); ?>;"></span>
                            <span class="theme-swatch custom-swatch-bg" style="background:<?php echo htmlspecialchars($cur_bg); ?>; border:1px solid rgba(0,0,0,0.15);"></span>
                        </div>
                        <h4 style="margin:0; font-size:1.05rem; color:var(--text-primary);">Individuell</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; line-height:1.35;">
                            Freie eigene Farbkomposition über die Farbwähler unten.
                        </p>
                    </label>
                </div>

                <!-- 2. Freie Detailfarben -->
                <div class="form-group" style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:2rem;">
                    <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; display:block;">
                        🎨 2. Farb-Feinabstimmung (Farbwähler)
                    </label>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                        Hier kannst du die Einzelfarben bei Bedarf punktuell anpassen. Sobald eine Farbe manuell geändert wird, wird das Thema automatisch auf „Individuell“ gesetzt.
                    </p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:1.25rem;">
                        <!-- Primary Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Haupt- &amp; Markenfarbe
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Buttons, Links, aktive Filter</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($cur_primary); ?>" oninput="onCustomColorChange('primary', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_primary_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_primary); ?></span>
                            </div>
                        </div>

                        <!-- Accent Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Akzent- &amp; Highlightfarbe
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Kursüberschrift, Badges, Highlights</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($cur_accent); ?>" oninput="onCustomColorChange('accent', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_accent_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_accent); ?></span>
                            </div>
                        </div>

                        <!-- Background Color -->
                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:12px;">
                            <label style="font-size:0.88rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:6px;">
                                Seiten-Hintergrund
                            </label>
                            <small style="color:var(--text-secondary); font-size:0.8rem; display:block; margin-bottom:8px;">Grundton der gesamten Website</small>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($cur_bg); ?>" oninput="onCustomColorChange('bg', this.value)" style="width:40px; height:40px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; background:transparent;">
                                <span id="display_bg_color" style="font-family:monospace; font-weight:600; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($cur_bg); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Seitenbreite (Container Max-Width) -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    📐 3. Seitenbreite &amp; Inhaltsrahmen
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Legt fest, wie weit sich der Inhaltsbereich auf großen Bildschirmen maximal ausdehnen darf.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 1050px -->
                    <label class="layout-card <?php echo ($cur_width === '1050px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1050px" <?php echo ($cur_width === '1050px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">📖</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Kompakt (1050 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Fokussierter Lesefluss mit ruhiger, zentrierter Ästhetik.
                        </p>
                    </label>

                    <!-- 1200px (Standard) -->
                    <label class="layout-card <?php echo ($cur_width === '1200px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1200px" <?php echo ($cur_width === '1200px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">✨</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Standard (1200 px) <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Aktuell</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Ausgewogenes Verhältnis zwischen Bildwirkung und Lesbarkeit.
                        </p>
                    </label>

                    <!-- 1400px -->
                    <label class="layout-card <?php echo ($cur_width === '1400px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="container_width" value="1400px" <?php echo ($cur_width === '1400px') ? 'checked' : ''; ?>>
                        <div style="font-size:1.6rem; text-align:center; padding:8px 0;">🖼️</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Großzügig (1400 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Weitläufiges Studio-Layout für moderne, hochauflösende Displays.
                        </p>
                    </label>
                </div>

                <!-- 4. Schriftgrößen & Typografie -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    🔤 4. Schriftgrößen-Skalierung
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Skaliert alle Texte, Überschriften und Zeilenhöhen fließend über das gesamte Layout.
                </p>
                <div class="layout-options-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
                    <!-- 95% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '95%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="95%" <?php echo ($cur_font_scale === '95%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.15rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (95%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Kompakt (~15 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Etwas dezentere Typografie für zartere Optik.
                        </p>
                    </label>

                    <!-- 100% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '100%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="100%" <?php echo ($cur_font_scale === '100%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.35rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (100%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Standard (16 px) <span class="layout-badge" style="font-size:0.7rem; vertical-align:middle;">Aktuell</span></h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Perfekt ausbalancierte Lesegröße nach Web-Standards.
                        </p>
                    </label>

                    <!-- 108% -->
                    <label class="layout-card <?php echo ($cur_font_scale === '108%') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)">
                        <input type="radio" name="font_scale" value="108%" <?php echo ($cur_font_scale === '108%') ? 'checked' : ''; ?>>
                        <div style="font-size:1.55rem; font-weight:700; text-align:center; padding:8px 0; color:var(--text-primary);">Aa (108%)</div>
                        <h4 style="margin:0; font-size:1.02rem; text-align:center; color:var(--text-primary);">Komfortabel (~17.5 px)</h4>
                        <p style="color:var(--text-secondary); font-size:0.82rem; margin:0; text-align:center; line-height:1.3;">
                            Besonders augenschonend und leicht lesbar.
                        </p>
                    </label>
                </div>

                <!-- 5. Galerie-Feinabstimmung (Ecken & Abstände) -->
                <label style="font-weight:700; color:var(--text-primary); margin-top:1.5rem; margin-bottom:0.5rem; display:block;">
                    🖼️ 5. Galerie-Kacheln: Eckenabrundung &amp; Raster-Abstand
                </label>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem; line-height:1.4;">
                    Stimme die Geometrie der Galerie-Karten und Werkstatt-Kacheln individuell ab.
                </p>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; margin-bottom:2.5rem;">
                    <!-- Kachel-Ecken (Border-Radius) -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); display:block; margin-bottom:8px;">
                            Eckenabrundung (Border-Radius)
                        </span>
                        <div class="layout-options-grid" style="grid-template-columns: repeat(3, 1fr); gap:10px;">
                            <label class="layout-card <?php echo ($cur_card_radius === '10px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="10px" <?php echo ($cur_card_radius === '10px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:6px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">10 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Dezent</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_card_radius === '20px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="20px" <?php echo ($cur_card_radius === '20px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:12px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">20 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Organisch</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_card_radius === '28px') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="card_radius" value="28px" <?php echo ($cur_card_radius === '28px') ? 'checked' : ''; ?>>
                                <div style="width:28px; height:28px; border:2px solid var(--primary); border-radius:18px; margin:0 auto 6px auto;"></div>
                                <b style="font-size:0.85rem; display:block;">28 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Soft</small>
                            </label>
                        </div>
                    </div>

                    <!-- Raster-Abstand (Gap) -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem;">
                        <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); display:block; margin-bottom:8px;">
                            Raster-Abstand (Kachel-Zwischenraum)
                        </span>
                        <div class="layout-options-grid" style="grid-template-columns: repeat(3, 1fr); gap:10px;">
                            <label class="layout-card <?php echo ($cur_gallery_gap === '1.25rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="1.25rem" <?php echo ($cur_gallery_gap === '1.25rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:3px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">20 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Kompakt</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_gallery_gap === '2rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="2rem" <?php echo ($cur_gallery_gap === '2rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:8px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">32 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Standard</small>
                            </label>

                            <label class="layout-card <?php echo ($cur_gallery_gap === '2.75rem') ? 'selected' : ''; ?>" onclick="selectLayoutCard(this)" style="padding:12px 8px; text-align:center;">
                                <input type="radio" name="gallery_gap" value="2.75rem" <?php echo ($cur_gallery_gap === '2.75rem') ? 'checked' : ''; ?>>
                                <div style="display:flex; justify-content:center; gap:14px; margin-bottom:6px;">
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                    <div style="width:12px; height:24px; background:var(--primary); border-radius:3px;"></div>
                                </div>
                                <b style="font-size:0.85rem; display:block;">44 px</b>
                                <small style="font-size:0.75rem; color:var(--text-secondary);">Luftig</small>
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">save</span> Darstellung &amp; Design speichern
                </button>
            </form>
        </div>

        <!-- ──────────────── SECTION 6: SICHERHEIT & SYSTEM ──────────────── -->
        <div id="tab-security" class="admin-section card" style="padding: 2.5rem;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">🔒 Sicherheit &amp; System</h2>
            
            <!-- FORMULAR 1: SPAMSCHUTZ, SMTP & SYSTEMEINSTELLUNGEN (KEINE PIN ERFORDERLICH) -->
            <form method="POST" action="" onsubmit="return validateSystemForm(this);">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_security">

                <!-- 1. SPAMSCHUTZ / CAPTCHA-SYMBOLE -->
                <h3 style="font-size: 1.2rem; margin-top: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="color:var(--primary);">verified_user</span> Spamschutz-Bilder für das Kontaktformular (Captcha)
                    </span>
                    <span id="captcha-counter-badge" style="font-size:0.85rem; padding:4px 12px; border-radius:20px; font-weight:600; background:rgba(46,98,72,0.12); color:var(--primary);">
                        Ausgewählt: <span id="captcha-count"><?php echo count(get_active_captcha_icons($data)); ?></span> von 4 Symbolen
                    </span>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Wähle hier genau <strong>4 der 12 zur Verfügung stehenden Symbole</strong> aus. Diese 4 Symbole werden deinen Besuchern im Kontaktformular angezeigt, wobei bei jeder Anfrage eines davon per Zufall als Sicherheitsaufgabe gelöst werden muss.
                </p>

                <?php 
                    $all_icons = get_all_captcha_icons();
                    $active_icons_keys = array_keys(get_active_captcha_icons($data));
                ?>

                <div class="captcha-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 2rem;">
                    <?php foreach ($all_icons as $icon_id => $icon_meta): 
                        $is_checked = in_array($icon_id, $active_icons_keys, true);
                    ?>
                        <label class="captcha-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 14px 10px; border: 2px solid <?php echo $is_checked ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius: 14px; background: <?php echo $is_checked ? 'rgba(46,98,72,0.08)' : 'rgba(0,0,0,0.015)'; ?>; cursor:pointer; user-select:none; transition: all 0.2s ease; position:relative; text-align:center;">
                            <input type="checkbox" name="captcha_icons[]" value="<?php echo htmlspecialchars($icon_id); ?>" <?php echo $is_checked ? 'checked' : ''; ?> style="position:absolute; top:8px; right:8px; width:17px; height:17px; accent-color:var(--primary); cursor:pointer;" onchange="updateCaptchaSelection(this);">
                            <span style="font-size: 2.2rem; line-height: 1.2; margin-bottom: 6px;"><?php echo $icon_meta['symbol']; ?></span>
                            <span style="font-weight: 600; font-size: 0.86rem; color: var(--text-primary); margin-bottom: 2px;"><?php echo htmlspecialchars($icon_meta['name']); ?></span>
                            <span style="font-size: 0.72rem; color: var(--text-secondary);"><?php echo htmlspecialchars($icon_meta['prompt']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- 2. E-MAIL & SMTP VERSAND -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary);">mail</span> E-Mail- &amp; SMTP-Versandeinstellungen (Kontaktformular)
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Konfiguriere hier den Server-Mailversand für dein Kontaktformular. Wenn SMTP aktiviert ist, werden E-Mails authentifiziert über dein Postfach versendet (höchste Zuverlässigkeit gegen Spamfilter). Andernfalls wird standardmäßig die PHP-Mailfunktion deines Webhosters genutzt.
                </p>

                <?php 
                    $cur_smtp = $data['settings']['smtp'] ?? [];
                    $smtp_is_enabled = !empty($cur_smtp['enabled']);
                ?>

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.95rem;">
                            <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtp_is_enabled ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            Authentifizierten SMTP-Versand aktivieren (Empfohlen für Live-Server &amp; Mailpostfächer)
                        </label>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label>SMTP-Server (Host)</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['host'] ?? ''); ?>" placeholder="z. B. smtp.strato.de oder mail.ihredomain.de">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['port'] ?? 587); ?>" placeholder="587">
                        </div>
                        <div class="form-group">
                            <label>Verschlüsselung</label>
                            <select name="smtp_secure" class="form-control" style="width:100%; height:44px; padding: 0 10px;">
                                <option value="tls" <?php echo (($cur_smtp['secure'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS / STARTTLS (Port 587, Standard)</option>
                                <option value="ssl" <?php echo (($cur_smtp['secure'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo (($cur_smtp['secure'] ?? '') === 'none') ? 'selected' : ''; ?>>Keine Verschlüsselung (Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label>SMTP-Benutzername / E-Mail</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['user'] ?? ''); ?>" placeholder="z. B. info@bianka-wischnewski.de">
                        </div>
                        <div class="form-group">
                            <label>SMTP-Passwort</label>
                            <div style="position:relative;">
                                <input type="password" id="smtp_pass" name="smtp_pass" class="form-control" placeholder="<?php echo !empty($cur_smtp['pass']) ? '•••••••• (bereits hinterlegt)' : 'Postfach-Passwort'; ?>" style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('smtp_pass')">visibility</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                        <div class="form-group">
                            <label>Absender-E-Mail-Adresse (From)</label>
                            <input type="email" name="smtp_from_email" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_email'] ?? ''); ?>" placeholder="z. B. info@bianka-wischnewski.de">
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Leer lassen, um die Benutzername-E-Mail zu verwenden.</small>
                        </div>
                        <div class="form-group">
                            <label>Absender-Name (From Name)</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($cur_smtp['from_name'] ?? 'Biankas Flechtglück'); ?>" placeholder="Biankas Flechtglück">
                        </div>
                    </div>
                </div>

                <!-- 3. BILDUPLOAD-VERZEICHNIS -->
                <h3 style="font-size: 1.2rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">
                    📁 Bildupload-Verzeichnis &amp; Speicherort
                </h3>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Speicherordner für hochgeladene Bilder (relativ zum Web-Root)</label>
                    <input type="text" name="upload_dir" class="form-control" value="<?php echo htmlspecialchars($data['settings']['upload_dir'] ?? 'images/uploads'); ?>" placeholder="images/uploads" required>
                    <small style="color: var(--text-secondary); display:block; margin-top:4px;">
                        Standard: <code>images/uploads</code> oder <code>uploads</code>. Verzeichnisse werden bei Bedarf automatisch erzeugt.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:18px;">save</span> Spamschutz-, SMTP- &amp; Upload-Einstellungen speichern
                </button>
            </form>

            <!-- SMTP Test Formular (separates Formular mit eigenem CSRF-Token) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem; margin-top: 2rem;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--text-primary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px; color:var(--primary);">send</span> SMTP-Verbindung jetzt testen
                </h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1rem;">
                    Sende eine Test-E-Mail, um deine hinterlegten SMTP-Zugangsdaten sofort auf Funktion zu prüfen (vorher bitte oben speichern).
                </p>
                <form method="POST" action="" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="test_smtp">
                    <div class="form-group" style="flex:1; min-width:240px; margin-bottom:0;">
                        <label style="font-size:0.85rem; margin-bottom:4px; display:block;">Test-E-Mail senden an:</label>
                        <input type="email" name="test_to_email" class="form-control" value="<?php echo htmlspecialchars($data['texts']['contact_email'] ?? 'info@bianka-wischnewski.de'); ?>" required style="height:42px;">
                    </div>
                    <button type="submit" class="btn btn-outline" style="height:42px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">mark_email_read</span> Test-E-Mail jetzt senden
                    </button>
                </form>
            </div>

            <!-- FORMULAR 2: ADMIN-ZUGANGSDATEN ÄNDERN (NUR HIER IST DIE AKTUELLE PIN PFLICHT) -->
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color);">
                <form method="POST" action="" onsubmit="return validateCredentialsForm(this);" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_credentials">

                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px; color:var(--primary);">
                        <span class="material-icons" style="color:var(--primary);">vpn_key</span> Admin-Zugangsdaten ändern (Passwort &amp; PIN)
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Hier kannst du dein persönliches Passwort und den PIN-Code für die Anmeldung ändern. Lasse die Felder leer, wenn du sie nicht ändern möchtest. Zum Schutz deines Adminbereichs ist vor der Übernahme die Eingabe deiner aktuell gültigen PIN zwingend erforderlich.
                    </p>

                    <!-- Sicherheitsabfrage Box (Erfordert aktuelle PIN, NIEMALS durch Cache/Cookies vorbelegt) -->
                    <div style="background: rgba(178, 108, 57, 0.08); border: 1px solid rgba(178, 108, 57, 0.3); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.75rem;">
                        <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:1rem;">
                            <span class="material-icons" style="color:var(--accent); font-size:22px; margin-top:2px;">shield</span>
                            <div style="font-size:0.88rem; color:var(--text-primary); line-height:1.45;">
                                <strong>Sicherheitsabfrage:</strong> Aus Sicherheitsgründen ist vor jeder Änderung von Passwort oder PIN die Eingabe deiner <u>aktuell noch gültigen PIN</u> erforderlich! Ohne korrekte aktuelle PIN werden neue Eingaben abgewiesen.
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 320px; margin-bottom: 0;">
                            <label for="current_pin" style="font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem; font-size:0.9rem;">
                                Aktuelle PIN <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="current_pin" 
                                       name="current_pin" 
                                       class="form-control" 
                                       placeholder="Aktuell gültige PIN eingeben" 
                                       maxlength="10" 
                                       autocomplete="new-password"
                                       data-lpignore="true"
                                       data-form-type="other"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px; letter-spacing:2px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('current_pin')">visibility</span>
                            </div>
                            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Zwingend erforderlich, um neues Passwort oder neue PIN zu aktivieren.</small>
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label for="new_password">Neues Passwort (optional)</label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       placeholder="Neues Passwort" 
                                       autocomplete="new-password" 
                                       data-lpignore="true"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_password')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;">Nur ausfüllen, wenn du ein neues Passwort setzen möchtest.</small>
                        </div>
                        <div class="form-group">
                            <label for="new_pin">Neuer PIN-Code (optional)</label>
                            <div style="position:relative;">
                                <input type="password" 
                                       id="new_pin" 
                                       name="new_pin" 
                                       class="form-control" 
                                       placeholder="z. B. 4-6 Ziffern" 
                                       maxlength="10" 
                                       autocomplete="new-password" 
                                       data-lpignore="true"
                                       value=""
                                       readonly
                                       onfocus="this.removeAttribute('readonly');"
                                       style="padding-right:40px; letter-spacing:2px;">
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:18px;" onclick="toggleVisibility('new_pin')">visibility</span>
                            </div>
                            <small style="color: var(--text-secondary); display:block; margin-top:4px;">Nur ausfüllen, wenn du eine neue PIN setzen möchtest.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:18px;">vpn_key</span> Neue Zugangsdaten speichern
                    </button>
                </form>
            </div>
        </div>

    </main>

    <script>
        const serverActiveTab = <?php echo json_encode($active_tab); ?>;
    </script>
    <script src="../libs/js/admin.js?v=<?php echo file_exists('../libs/js/admin.js') ? filemtime('../libs/js/admin.js') : time(); ?>"></script>
</body>
</html>
