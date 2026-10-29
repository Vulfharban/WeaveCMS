<?php
/**
 * Frontend: Forum Register View (mit E-Mail-Token-Verifizierung, Forum-Richtlinien & DSGVO-Zustimmung)
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

$forumGuidelines = ForumUserManager::getForumGuidelines();
?>

<div style="max-width: 500px; margin: 36px auto; background: #ffffff; border: 1px solid #cbd5e1; border-top: 5px solid var(--primary, #2e6248); border-radius: 14px; padding: 30px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);">
    <div style="text-align: center; margin-bottom: 22px;">
        <div style="width: 52px; height: 52px; margin: 0 auto 12px auto; border-radius: 50%; background: rgba(46, 98, 72, 0.1); color: var(--primary, #2e6248); display: flex; align-items: center; justify-content: center;">
            <span class="material-icons" style="font-size: 26px;">person_add</span>
        </div>
        <h2 style="margin: 0 0 6px 0; font-size: 1.4rem; font-weight: 800; color: #0f172a;">Forum-Konto erstellen</h2>
        <p style="margin: 0; font-size: 13px; color: #64748b;">Erstelle dein kostenloses Profil für unsere Community.</p>
    </div>

    <div style="margin-bottom: 18px; padding: 11px 14px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 12.5px; line-height: 1.45; display: flex; align-items: flex-start; gap: 9px;">
        <span class="material-icons" style="font-size: 18px; color: var(--primary, #2e6248); margin-top: 1px; flex-shrink: 0;">mark_email_unread</span>
        <span>Nach der Registrierung erhältst du eine <strong>Bestätigungs-E-Mail mit einem Aktivierungslink</strong>. Erst nach Klick auf diesen Link wird dein Konto zur Anmeldung freigeschaltet.</span>
    </div>

    <?php if (!empty($error)): ?>
        <div style="margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="font-size: 18px; flex-shrink: 0;">error_outline</span>
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
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">E-Mail-Adresse (für Bestätigungs-Link):</label>
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

        <!-- Pflicht-Zustimmungen: Forum-Richtlinien & Datenschutz/DSGVO -->
        <div style="padding: 14px; border-radius: 10px; background: #f8fafc; border: 1px solid #cbd5e1; display: flex; flex-direction: column; gap: 12px; margin-top: 2px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; line-height: 1.45; color: #1e293b; cursor: pointer;">
                <input type="checkbox" name="accept_forum_rules" value="1" required style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--primary, #2e6248); flex-shrink: 0;">
                <span>
                    Ich habe die <a href="#forum-guidelines" onclick="document.getElementById('forumGuidelinesModal').showModal(); return false;" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: underline;">Forum-Richtlinien (Netiquette)</a> gelesen und erkenne diese verbindlich an. *
                </span>
            </label>

            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; line-height: 1.45; color: #1e293b; cursor: pointer;">
                <input type="checkbox" name="accept_privacy_dsgvo" value="1" required style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--primary, #2e6248); flex-shrink: 0;">
                <span>
                    Ich habe die <strong>Datenschutzrichtlinien</strong> zur Kenntnis genommen und stimme der Speicherung und Verarbeitung meiner personenbezogenen Daten (Benutzername, E-Mail-Adresse, Beiträge) gemäß <strong>DSGVO (Art. 6 Abs. 1 lit. a)</strong> für die Nutzung des Forums zu. *
                </span>
            </label>
        </div>

        <button type="submit" class="kunena-btn-primary" style="width: 100%; justify-content: center; padding: 11px; font-size: 14px; cursor: pointer; margin-top: 4px;">
            <span class="material-icons" style="font-size: 18px;">mark_email_read</span> Registrieren &amp; Bestätigungs-E-Mail anfordern
        </button>

        <div style="text-align: center; margin-top: 8px; font-size: 13px; color: #64748b;">
            Bereits registriert? <a href="?page=forum&view=login" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: none;">Hier anmelden</a>
        </div>
    </form>
</div>

<!-- Modal für Forum-Richtlinien -->
<dialog id="forumGuidelinesModal" style="border: none; border-radius: 16px; padding: 0; width: 92%; max-width: 560px; background: #ffffff; color: #0f172a; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="color: var(--primary, #2e6248);">gavel</span>
            <span>Forum-Richtlinien &amp; Netiquette</span>
        </h3>
        <button type="button" onclick="this.closest('dialog').close()" style="background: transparent; border: none; cursor: pointer; color: #64748b; display: flex;">
            <span class="material-icons">close</span>
        </button>
    </div>
    <div style="padding: 22px; font-size: 13.5px; line-height: 1.65; color: #334155; max-height: 60vh; overflow-y: auto; white-space: pre-line;"><?= htmlspecialchars($forumGuidelines) ?></div>
    <div style="padding: 14px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
        <button type="button" class="kunena-btn-primary" onclick="this.closest('dialog').close()" style="cursor: pointer;">Verstanden &amp; Schließen</button>
    </div>
</dialog>
