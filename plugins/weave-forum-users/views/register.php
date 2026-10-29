<?php
/**
 * Frontend: Forum Register View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if ($currentUser) {
    $dispName = ForumUserManager::getDisplayName($currentUser);
    echo '<div style="max-width:480px; margin:32px auto; padding:20px; border-radius:12px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; text-align:center;">'
       . 'Du bist bereits als <strong>' . htmlspecialchars($dispName) . '</strong> angemeldet.'
       . '</div>';
    return;
}

$error = $_SESSION['forum_auth_error'] ?? '';
unset($_SESSION['forum_auth_error']);
?>

<div style="max-width: 460px; margin: 36px auto; background: #ffffff; border: 1px solid #cbd5e1; border-top: 5px solid var(--primary, #2e6248); border-radius: 14px; padding: 30px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 52px; height: 52px; margin: 0 auto 12px auto; border-radius: 50%; background: rgba(46, 98, 72, 0.1); color: var(--primary, #2e6248); display: flex; align-items: center; justify-content: center;">
            <span class="material-icons" style="font-size: 26px;">person_add</span>
        </div>
        <h2 style="margin: 0 0 6px 0; font-size: 1.4rem; font-weight: 800; color: #0f172a;">Konto erstellen</h2>
        <p style="margin: 0; font-size: 13px; color: #64748b;">Erstelle dein kostenloses Profil für unsere Community.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="font-size: 18px;">error_outline</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="?forum_auth=register" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">Gewünschter Benutzername:</label>
            <input type="text" name="username" required autofocus placeholder="z. B. Holzwurm99" pattern="^[a-zA-Z0-9_\-\.]+$" title="Buchstaben, Zahlen, Punkte, Bindestriche"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
            <span style="display:block; font-size: 11px; color: #64748b; margin-top: 4px;">Mindestens 3 Zeichen. Nur Buchstaben, Zahlen, Punkte und Bindestriche.</span>
        </div>

        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">E-Mail-Adresse:</label>
            <input type="email" name="email" required placeholder="max@beispiel.de"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">Passwort:</label>
            <input type="password" name="password" required minlength="8" placeholder="Mindestens 8 Zeichen"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div>
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">Passwort wiederholen:</label>
            <input type="password" name="password_confirm" required minlength="8" placeholder="Passwort bestätigen"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <button type="submit" class="kunena-btn-primary" style="width: 100%; justify-content: center; padding: 11px; font-size: 14px; cursor: pointer; margin-top: 4px;">
            <span class="material-icons" style="font-size: 18px;">person_add</span> Kostenlos registrieren
        </button>

        <div style="text-align: center; margin-top: 8px; font-size: 13px; color: #64748b;">
            Bereits registriert? <a href="?page=forum&view=login" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: none;">Hier anmelden</a>
        </div>
    </form>
</div>
