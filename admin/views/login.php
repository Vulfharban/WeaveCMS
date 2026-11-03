<?php
/**
 * WeaveCMS – Admin-Login View
 * @var string $login_error Fehlermeldung bei fehlgeschlagenem Login
 */
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($admin_lang ?? LanguageManager::detectAdminLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__a('admin.login.title_tag', 'Admin-Login | WeaveCMS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../libs/css/styles.css?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 2) . '/libs/css/styles.css') : time(); ?>" rel="stylesheet">
</head>
<body class="admin-login-body">
    <?php 
        // Phase 15: Flüchtiger Sandbox-Banner (Login)
        if (class_exists('SandboxManager') && SandboxManager::isDemoActive()) {
            SandboxManager::renderBanner('login');
        }
    ?>
    <div class="login-container">
        <div class="login-box" style="position:relative;">
            <div style="position:absolute; top:18px; right:18px;">
                <?php echo LanguageManager::renderAdminLanguageSwitcher(); ?>
            </div>
            <div style="text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 3rem;">🌿</span>
                <h1 style="font-size: 1.8rem; margin-top: 1rem; margin-bottom: 0.5rem;"><?php echo __a('admin.login.title', 'Admin-Bereich'); ?></h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;"><?php echo __a('admin.login.subtitle', 'Bitte Passwort und PIN-Code eingeben.'); ?></p>
                <?php 
                $isDemo = class_exists('SandboxManager') && SandboxManager::isDemoActive();
                if ($isDemo): 
            ?>
                <div style="margin-top: 0.75rem; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.25); border-radius: 8px; padding: 8px 14px; font-size: 0.85rem; color: #1e40af; text-align: left; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="font-size: 18px; color: #2563eb;">lock</span>
                    <div>
                        <strong>Demo-Zugang gesichert:</strong> Zugangsdaten sind vorausgefüllt und schreibgeschützt.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($login_error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; color: #f87171; padding: 0.85rem; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1.5rem; text-align: center;">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="login" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo AuthManager::getCsrfToken(); ?>">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);"><?php echo __a('admin.login.username', 'Benutzername oder E-Mail'); ?></label>
                <div style="position:relative;">
                    <input type="text" id="username" name="username" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px; <?php echo $isDemo ? 'background: rgba(0,0,0,0.04); cursor: not-allowed; color: #475569;' : ''; ?>" placeholder="admin" value="<?php echo $isDemo ? 'admin' : htmlspecialchars($_POST['username'] ?? 'admin'); ?>" <?php echo $isDemo ? 'readonly' : 'required autofocus'; ?>>
                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); font-size:18px;"><?php echo $isDemo ? 'lock' : 'person'; ?></span>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);"><?php echo __a('admin.login.password', 'Passwort'); ?></label>
                <div style="position:relative;">
                    <input type="<?php echo $isDemo ? 'text' : 'password'; ?>" id="password" name="password" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px; <?php echo $isDemo ? 'background: rgba(0,0,0,0.04); cursor: not-allowed; color: #475569;' : ''; ?>" placeholder="<?php echo htmlspecialchars(__a('admin.login.password_placeholder', 'Passwort eingeben')); ?>" value="<?php echo $isDemo ? 'demo-weave-2026' : ''; ?>" <?php echo $isDemo ? 'readonly' : 'required'; ?>>
                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); <?php echo $isDemo ? '' : 'cursor:pointer;'; ?> font-size:18px;" <?php echo $isDemo ? '' : "onclick=\"toggleVisibility('password')\""; ?>><?php echo $isDemo ? 'lock' : 'visibility'; ?></span>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1.75rem;">
                <label for="pin" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);"><?php echo __a('admin.login.pin', 'PIN-Code'); ?></label>
                <div style="position:relative;">
                    <input type="<?php echo $isDemo ? 'text' : 'password'; ?>" id="pin" name="pin" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px; letter-spacing: 3px; <?php echo $isDemo ? 'background: rgba(0,0,0,0.04); cursor: not-allowed; color: #475569;' : ''; ?>" maxlength="10" value="<?php echo $isDemo ? '0000' : ''; ?>" <?php echo $isDemo ? 'readonly' : 'required'; ?>>
                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); <?php echo $isDemo ? '' : 'cursor:pointer;'; ?> font-size:18px;" <?php echo $isDemo ? '' : "onclick=\"toggleVisibility('pin')\""; ?>><?php echo $isDemo ? 'lock' : 'pin'; ?></span>
                </div>
            </div>
            <?php if (!$isDemo): ?>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="two_factor_code" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);"><?php echo __a("admin.login.2fa_code", "2FA-Code / Notfall-Code (falls aktiviert)"); ?></label>
                <div style="position:relative;">
                    <input type="text" id="two_factor_code" name="two_factor_code" class="form-control" style="width: 100%; padding: 12px 40px 12px 12px; border-radius: 10px; letter-spacing: 2px;" placeholder="<?php echo htmlspecialchars(__a("admin.login.2fa_placeholder", "6-stelliger Code oder Backup-Code")); ?>" autocomplete="one-time-code">
                    <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); font-size:18px;">phonelink_lock</span>
                </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; border-radius: 10px; font-weight: 600; font-size: 1rem;">
                <span class="material-icons" style="font-size:18px; vertical-align:middle;">lock_open</span> <?php echo $isDemo ? 'Als Demo-Admin anmelden' : __a('admin.login.submit', 'Einloggen'); ?>
            </button>
            </form>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="../" style="font-size: 0.9rem; color: var(--text-secondary); text-decoration: none;">&larr; <?php echo __a('admin.login.back_to_site', 'Zurück zur Website'); ?></a>
            </div>
        </div>
    </div>
    <script src="../libs/js/admin.js?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/js/admin.js') ? filemtime(dirname(__DIR__, 2) . '/libs/js/admin.js') : time(); ?>"></script>
</body>
</html>
