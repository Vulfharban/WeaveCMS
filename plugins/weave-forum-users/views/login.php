<?php
/**
 * Frontend: Forum Login View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if ($currentUser) {
    $dispName = ForumUserManager::getDisplayName($currentUser);
    echo '<div style="max-width:480px; margin:32px auto; padding:20px; border-radius:12px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; text-align:center;">'
       . 'Du bist bereits als <strong>' . htmlspecialchars($dispName) . '</strong> angemeldet. '
       . '<a href="?forum_auth=logout" class="kunena-btn-secondary" style="margin-left:10px;">Abmelden</a>'
       . '</div>';
    return;
}

$error = $_SESSION['forum_auth_error'] ?? '';
unset($_SESSION['forum_auth_error']);

$success = $_SESSION['forum_auth_success'] ?? '';
unset($_SESSION['forum_auth_success']);
?>

<div style="max-width: 460px; margin: 36px auto; background: #ffffff; border: 1px solid #cbd5e1; border-top: 5px solid var(--primary, #2e6248); border-radius: 14px; padding: 30px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 52px; height: 52px; margin: 0 auto 12px auto; border-radius: 50%; background: rgba(46, 98, 72, 0.1); color: var(--primary, #2e6248); display: flex; align-items: center; justify-content: center;">
            <span class="material-icons" style="font-size: 26px;">lock</span>
        </div>
        <h2 style="margin: 0 0 6px 0; font-size: 1.4rem; font-weight: 800; color: #0f172a;">Forum Anmeldung</h2>
        <p style="margin: 0; font-size: 13px; color: #64748b;">Melde dich mit deinem bestätigten Benutzerkonto an, um mitzudiskutieren.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: 13.5px; line-height: 1.5; display: flex; align-items: flex-start; gap: 10px;">
            <span class="material-icons" style="font-size: 20px; margin-top: 1px; flex-shrink: 0;">mark_email_read</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="font-size: 18px; flex-shrink: 0;">error_outline</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="?forum_auth=login" style="display: flex; flex-direction: column; gap: 16px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Benutzername oder E-Mail:</label>
            <input type="text" name="login" required autofocus placeholder="maxmuster oder max@beispiel.de"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Passwort:</label>
            <input type="password" name="password" required placeholder="••••••••"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <button type="submit" class="kunena-btn-primary" style="width: 100%; justify-content: center; padding: 11px; font-size: 14px; cursor: pointer; margin-top: 4px;">
            <span class="material-icons" style="font-size: 18px;">login</span> Anmelden
        </button>

        <div style="text-align: center; margin-top: 8px; font-size: 13px; color: #64748b;">
            Noch kein Konto? <a href="?page=forum&view=register" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: none;">Hier registrieren</a>
        </div>
    </form>
</div>
