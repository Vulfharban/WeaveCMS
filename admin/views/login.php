<?php
/**
 * WeaveCMS – Admin-Login View
 * @var string $login_error Fehlermeldung bei fehlgeschlagenem Login
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Login | WeaveCMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../libs/css/styles.css?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 2) . '/libs/css/styles.css') : time(); ?>" rel="stylesheet">
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
    <script src="../libs/js/admin.js?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/js/admin.js') ? filemtime(dirname(__DIR__, 2) . '/libs/js/admin.js') : time(); ?>"></script>
</body>
</html>
