<?php
/**
 * Frontend: Forum Profile View (inkl. Alias für Admin/Moderatoren & Privatsphäre-Einstellungen)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if (!$currentUser) {
    echo '<div style="max-width:520px; margin:40px auto; padding:24px; border-radius:14px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; text-align:center;">'
       . '<strong>Anmeldung erforderlich:</strong> Bitte melde dich an, um dein Profil zu verwalten.<br>'
       . '<a href="?page=forum&view=login" class="kunena-btn-primary" style="margin-top:12px;">Zur Anmeldung</a>'
       . '</div>';
    return;
}

$msg = $_SESSION['forum_profile_msg'] ?? '';
unset($_SESSION['forum_profile_msg']);

$canUseAlias = ForumUserManager::canUseAlias($currentUser);
$displayName = ForumUserManager::getDisplayName($currentUser);
$roleLabel = ForumUserManager::getRoleLabel($currentUser['role'] ?? 'forum_user');
$roleColor = ForumUserManager::getRoleColor($currentUser['role'] ?? 'forum_user');
$privacy = ForumUserManager::getPrivacySettings($currentUser);
?>

<style>
.kunena-profile-wrap {
    max-width: 820px;
    margin: 0 auto 32px auto;
}
.kunena-profile-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid var(--primary, #2e6248);
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.kunena-profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 22px 26px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.kunena-profile-user {
    display: flex;
    align-items: center;
    gap: 16px;
}
.kunena-profile-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary, #2e6248), #1e4631);
    color: #ffffff;
    font-size: 24px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(46, 98, 72, 0.25);
}
.kunena-profile-body {
    padding: 26px;
}
.kunena-profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 26px;
}
.kunena-profile-stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.kunena-profile-stat-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--primary, #2e6248);
    line-height: 1.2;
}
.kunena-profile-stat-lbl {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 4px;
    font-weight: 600;
}
.kunena-form-section {
    margin-bottom: 24px;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
}
.kunena-form-section.highlight {
    background: linear-gradient(to right, rgba(46, 98, 72, 0.05), rgba(46, 98, 72, 0.015));
    border-color: rgba(46, 98, 72, 0.3);
}
.kunena-form-section-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.kunena-form-section-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 0 0 16px 0;
    line-height: 1.45;
}
.kunena-field {
    margin-bottom: 16px;
}
.kunena-field:last-child {
    margin-bottom: 0;
}
.kunena-field label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}
.kunena-input, .kunena-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    font-family: inherit;
}
.kunena-input:focus, .kunena-textarea:focus {
    border-color: var(--primary, #2e6248);
    box-shadow: 0 0 0 3px rgba(46, 98, 72, 0.12);
}
.kunena-privacy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 10px;
}
.kunena-privacy-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}
.kunena-privacy-item:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.kunena-privacy-item input[type="checkbox"] {
    margin-top: 2px;
    width: 16px;
    height: 16px;
    accent-color: var(--primary, #2e6248);
    flex-shrink: 0;
}
</style>

<div class="kunena-profile-wrap">
    <div class="kunena-profile-card">
        <!-- Profile Header -->
        <div class="kunena-profile-header">
            <div class="kunena-profile-user">
                <div class="kunena-profile-avatar">
                    <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <h2 style="margin:0; font-size:1.35rem; font-weight:800; color:#0f172a;">
                            <?= htmlspecialchars($displayName) ?>
                        </h2>
                        <span style="padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight:700; background:<?= $roleColor ?>18; color:<?= $roleColor ?>; border:1px solid <?= $roleColor ?>40;">
                            <?= htmlspecialchars($roleLabel) ?>
                        </span>
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:4px;">
                        <?php if ($displayName !== $currentUser['username']): ?>
                            <span>Interner Kontoname: <strong><?= htmlspecialchars($currentUser['username']) ?></strong> &bull; </span>
                        <?php endif; ?>
                        <span>Mitglied seit <?= htmlspecialchars(substr($currentUser['created_at'] ?? '', 0, 10)) ?></span>
                    </div>
                </div>
            </div>
            <a href="?forum_auth=logout" class="kunena-btn-secondary" style="padding:8px 14px;">
                <span class="material-icons" style="font-size:16px;">logout</span> Abmelden
            </a>
        </div>

        <div class="kunena-profile-body">
            <?php if ($msg === 'saved'): ?>
                <div style="margin-bottom:20px; padding:13px 16px; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons">check_circle</span>
                    <span>Deine Profileinstellungen, Alias- und Privatsphäre-Optionen wurden erfolgreich gespeichert!</span>
                </div>
            <?php elseif ($msg === 'invalid_email'): ?>
                <div style="margin-bottom:20px; padding:13px 16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons">error</span>
                    <span>Bitte gib eine gültige E-Mail-Adresse an.</span>
                </div>
            <?php elseif ($msg === 'email_taken'): ?>
                <div style="margin-bottom:20px; padding:13px 16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons">error</span>
                    <span>Diese E-Mail-Adresse wird bereits von einem anderen Konto verwendet.</span>
                </div>
            <?php elseif ($msg === 'invalid_alias'): ?>
                <div style="margin-bottom:20px; padding:13px 16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons">error</span>
                    <span>Der Alias muss zwischen 2 und 40 Zeichen lang sein.</span>
                </div>
            <?php elseif ($msg === 'alias_taken'): ?>
                <div style="margin-bottom:20px; padding:13px 16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons">error</span>
                    <span>Dieser Alias-Name ist bereits vergeben. Bitte wähle einen anderen.</span>
                </div>
            <?php endif; ?>

            <!-- User Stats -->
            <div class="kunena-profile-stats">
                <div class="kunena-profile-stat-box">
                    <div class="kunena-profile-stat-val"><?= (int)($currentUser['topic_count'] ?? 0) ?></div>
                    <div class="kunena-profile-stat-lbl">Erstellte Themen</div>
                </div>
                <div class="kunena-profile-stat-box">
                    <div class="kunena-profile-stat-val"><?= (int)($currentUser['post_count'] ?? 0) ?></div>
                    <div class="kunena-profile-stat-lbl">Beiträge</div>
                </div>
                <div class="kunena-profile-stat-box">
                    <div class="kunena-profile-stat-val" style="font-size:1.05rem;">
                        <?= ($currentUser['status'] ?? '') === 'active' ? 'Aktiv ✓' : htmlspecialchars($currentUser['status'] ?? '') ?>
                    </div>
                    <div class="kunena-profile-stat-lbl">Kontostatus</div>
                </div>
                <div class="kunena-profile-stat-box">
                    <div class="kunena-profile-stat-val" style="font-size:1.05rem;">
                        <?= !empty($currentUser['last_login']) ? htmlspecialchars(substr($currentUser['last_login'], 0, 10)) : 'Heute' ?>
                    </div>
                    <div class="kunena-profile-stat-lbl">Letzter Login</div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <form method="post" action="?forum_auth=update_profile">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <!-- 1. ALIAS- SEKTION FÜR ADMINS & MODERATOREN -->
                <?php if ($canUseAlias): ?>
                    <div class="kunena-form-section highlight">
                        <h3 class="kunena-form-section-title" style="color: var(--primary, #2e6248);">
                            <span class="material-icons" style="font-size: 20px;">badge</span>
                            <span>Alias-Name für Administratoren &amp; Moderatoren</span>
                        </h3>
                        <p class="kunena-form-section-desc">
                            Als Administrator oder Moderator kannst du deinem Konto ein öffentliches Pseudonym (Alias) vergeben und festlegen, dass im gesamten Forum ausschließlich dieser Alias anstelle deines echten Benutzernamens angezeigt wird.
                        </p>

                        <div class="kunena-field">
                            <label>Gewünschter Alias / Anzeigename im Forum:</label>
                            <input type="text" name="alias" value="<?= htmlspecialchars($currentUser['alias'] ?? '') ?>" 
                                   class="kunena-input" placeholder="z. B. Forenleitung, Moderator, WeaveCMS-Support..." maxlength="40">
                        </div>

                        <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; color:#0f172a; cursor:pointer; padding-top:4px;">
                            <input type="checkbox" name="use_alias_only" value="1" <?= !empty($currentUser['use_alias_only']) ? 'checked' : '' ?> style="width:17px; height:17px; accent-color:var(--primary, #2e6248);">
                            <span>Nur diesen Alias im Forum anzeigen (meinen echten Benutzernamen „<?= htmlspecialchars($currentUser['username']) ?>“ vor anderen verbergen)</span>
                        </label>
                    </div>
                <?php endif; ?>

                <!-- 2. ALLGEMEINE PROFIL-DATEN -->
                <div class="kunena-form-section">
                    <h3 class="kunena-form-section-title">
                        <span class="material-icons" style="font-size: 20px; color: var(--primary, #2e6248);">person</span>
                        <span>Persönliche Angaben &amp; Signatur</span>
                    </h3>
                    <p class="kunena-form-section-desc">
                        Deine E-Mail-Adresse wird niemals öffentlich im Forum angezeigt.
                    </p>

                    <div class="kunena-field">
                        <label>E-Mail-Adresse (nur für Benachrichtigungen &amp; Login):</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required class="kunena-input">
                    </div>

                    <div class="kunena-field">
                        <label>Über mich / Biografie:</label>
                        <textarea name="bio" rows="3" class="kunena-textarea" placeholder="Erzähle der Community kurz etwas über dich..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="kunena-field">
                        <label>Forum-Signatur (wird unter deinen Beiträgen angezeigt):</label>
                        <textarea name="signature" rows="2" class="kunena-textarea" placeholder="Deine persönliche Signatur (max. 200 Zeichen)..."><?= htmlspecialchars($currentUser['signature'] ?? '') ?></textarea>
                    </div>

                    <div class="kunena-field" style="padding-top: 6px;">
                        <label style="display:flex; align-items:center; gap:10px; font-weight:600; cursor:pointer; margin:0;">
                            <input type="checkbox" name="notification_email" value="1" <?= !empty($currentUser['notification_email']) ? 'checked' : '' ?> style="width:16px; height:16px; accent-color:var(--primary, #2e6248);">
                            <span>E-Mail-Benachrichtigungen bei neuen Antworten auf abonnierte Themen erhalten</span>
                        </label>
                    </div>
                </div>

                <!-- 3. PRIVATSPHÄRE & SICHTBARKEIT FÜR ANDERE BENUTZER -->
                <div class="kunena-form-section">
                    <h3 class="kunena-form-section-title">
                        <span class="material-icons" style="font-size: 20px; color: var(--primary, #2e6248);">visibility</span>
                        <span>Privatsphäre &amp; Sichtbarkeit für andere Mitglieder</span>
                    </h3>
                    <p class="kunena-form-section-desc">
                        Wähle selbst aus, welche deiner Profil-Inhalte anderen <strong>angemeldeten Benutzern</strong> im Forum angezeigt werden dürfen.
                        <br><span style="color: var(--primary, #2e6248); font-weight: 700;">🔒 Hinweis zum Gänseschutz:</span> Nicht angemeldeten Besuchern (Gästen) werden grundsätzlich keinerlei Benutzerdaten angezeigt.
                    </p>

                    <div class="kunena-privacy-grid">
                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_role" value="1" <?= !empty($privacy['show_role']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Rolle / Rang-Badge anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Zeigt deinen Foren-Rang (z. B. Mitglied, Moderator) neben Beiträgen.</div>
                            </div>
                        </label>

                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_post_count" value="1" <?= !empty($privacy['show_post_count']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Beitragszähler anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Zeigt die Anzahl deiner verfassten Beiträge in der Autorenbox.</div>
                            </div>
                        </label>

                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_joined_date" value="1" <?= !empty($privacy['show_joined_date']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Registrierungsdatum anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Zeigt „Mitglied seit ...“ in deiner Autoren-Seitenleiste.</div>
                            </div>
                        </label>

                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_bio" value="1" <?= !empty($privacy['show_bio']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Biografie („Über mich“) anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Zeigt deinen Kurztext in der Autorenkarte neben Beiträgen.</div>
                            </div>
                        </label>

                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_signature" value="1" <?= !empty($privacy['show_signature']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Signatur unter Beiträgen anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Blendet deine persönliche Signatur unter deinen Forenbeiträgen ein.</div>
                            </div>
                        </label>

                        <label class="kunena-privacy-item">
                            <input type="checkbox" name="priv_show_online_status" value="1" <?= !empty($privacy['show_online_status']) ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700; font-size:13px; color:#0f172a;">Online-Status anzeigen</div>
                                <div style="font-size:11.5px; color:#64748b;">Zeigt deinen Namen unten in der Liste „Wer ist online“.</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Submit & DSGVO Export Bar -->
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <button type="submit" class="kunena-btn-primary" style="padding:10px 22px; font-size:13.5px; cursor:pointer;">
                        <span class="material-icons" style="font-size:17px;">save</span> Profil &amp; Einstellungen speichern
                    </button>

                    <a href="?forum_auth=export_data" class="kunena-btn-secondary" style="padding:9px 16px;">
                        <span class="material-icons" style="font-size:16px;">download</span> Meine Daten exportieren (DSGVO)
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
