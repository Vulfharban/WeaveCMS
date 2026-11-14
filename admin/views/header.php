<?php
/**
 * WeaveCMS – Admin Header View
 * 
 * @var string $admin_paw Vorgerendertes SVG-Tatzenlogo
 * @var string $admin_logo_pos Konfigurierte Logo-Position
 */
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($admin_lang ?? LanguageManager::detectAdminLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__a('admin.header.title_tag', 'Administration | WeaveCMS')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../libs/css/styles.css?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 2) . '/libs/css/styles.css') : time(); ?>" rel="stylesheet">
    <script>
        window.WeaveI18n = <?php echo json_encode([
            'confirm_delete_course' => __a('admin.js.confirm_delete_course', 'Möchtest du diesen Kurs wirklich komplett löschen?'),
            'alert_captcha_count' => __a('admin.js.alert_captcha_count', "⚠️ Spamschutz-Symbole:\n\nBitte wähle genau 4 Symbole für den Spamschutz im Kontaktformular aus!\n(Aktuell ausgewählt: {count} von 4)"),
            'alert_no_credentials' => __a('admin.js.alert_no_credentials', "⚠️ Keine Eingabe:\n\nBitte gib ein neues Passwort oder eine neue PIN ein, die du setzen möchtest."),
            'alert_pin_required' => __a('admin.js.alert_pin_required', "⚠️ Sicherheitsabfrage:\n\nBitte gib deine aktuell gültige PIN ein, um Passwort oder PIN zu ändern!"),
            'alert_captcha_max' => __a('admin.js.alert_captcha_max', "⚠️ Maximal 4 Symbole:\n\nEs können genau 4 Symbole für das Kontaktformular ausgewählt werden. Bitte deaktiviere zuerst ein anderes Symbol, bevor du ein neues auswählst."),
            'upload_error' => __a('admin.js.upload_error', 'Upload-Fehler: '),
            'save_error' => __a('admin.js.save_error', 'Fehler beim Speichern'),
            'error_prefix' => __a('admin.js.error_prefix', 'Fehler: '),
            'delete_failed' => __a('admin.js.delete_failed', 'Löschen fehlgeschlagen: '),
            'confirm_remove_field' => __a('admin.js.confirm_remove_field', 'Möchtest du dieses Feld wirklich aus der Definition entfernen?'),
            'confirm_delete_token' => __a('admin.js.confirm_delete_token', 'API-Token unwiderruflich löschen?'),
            'confirm_delete_webhook' => __a('admin.js.confirm_delete_webhook', 'Webhook unwiderruflich löschen?'),
            'confirm_clear_webhook_log' => __a('admin.js.confirm_clear_webhook_log', 'Möchtest du das gesamte Webhook-Protokoll leeren?'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="../libs/js/admin.js?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/js/admin.js') ? filemtime(dirname(__DIR__, 2) . '/libs/js/admin.js') : time(); ?>"></script>
</head>
<body class="admin-body">

    <?php 
        // Phase 15: Flüchtiger Sandbox-Banner (Backend)
        if (class_exists('SandboxManager') && (SandboxManager::isDemoActive() || SandboxManager::isDemoPaused())) {
            SandboxManager::renderBanner('admin');
        }
    ?>

    <!-- Sticky Header -->
    <header class="header-nav">
        <div class="header-container">
            <span class="logo"><?php if ($admin_logo_pos !== 'header_after'): echo $admin_paw; endif; ?><span>WeaveCMS Admin</span><?php if ($admin_logo_pos === 'header_after'): echo $admin_paw; endif; ?></span>
            <div style="display:flex; align-items:center; gap: 0.75rem;">
                <?php echo LanguageManager::renderAdminLanguageSwitcher(); ?>
                <?php if (isset($cur_user) && !empty($cur_user['username'])): ?>
                    <span style="font-size:0.84rem; color:var(--text-secondary); display:inline-flex; align-items:center; gap:5px; background:rgba(0,0,0,0.03); padding:4px 10px; border-radius:20px;">
                        <span class="material-icons" style="font-size:16px;">account_circle</span>
                        <strong><?php echo htmlspecialchars($cur_user['username']); ?></strong>
                    </span>
                <?php endif; ?>
                <a href="../" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;"><?php echo __a('admin.header.view_site', 'Website anzeigen &rarr;'); ?></a>
                <a href="?action=logout" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; background:#dc2626;"><?php echo __a('admin.header.logout', 'Abmelden'); ?></a>
            </div>
        </div>
    </header>

    <main class="container" style="max-width: 1050px;">
        <h1 style="margin-bottom: 2rem;"><?php echo __a('admin.header.title', 'Verwaltungspanel'); ?></h1>
