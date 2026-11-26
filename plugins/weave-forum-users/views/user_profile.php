<?php
/**
 * Frontend: Öffentliches Mitgliederprofil (ausschließlich für registrierte/angemeldete Benutzer)
 * inkl. granularem Datenschutz, Privater Nachricht (PN) und Kontaktaufnahme per E-Mail.
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;

// Strenger Gästeschutz: Profile sind nur für registrierte & angemeldete Benutzer sichtbar
if (!$currentUser) {
    ?>
    <div class="kunena-cat-card" style="text-align: center; padding: 50px 24px; margin: 24px auto; max-width: 640px; border-left: 6px solid #f59e0b; background:#ffffff; border-radius:14px; border:1px solid #e2e8f0;">
        <div style="width: 64px; height: 64px; margin: 0 auto 16px auto; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
            <span class="material-icons" style="font-size: 34px;">lock</span>
        </div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
            Mitgliederprofile sind geschützt
        </h2>
        <p style="font-size: 14px; color: #64748b; line-height: 1.6; max-width: 480px; margin: 0 auto 22px auto;">
            Benutzerprofile sind ausschließlich für registrierte und angemeldete Community-Mitglieder einsehbar. Bitte melde dich an oder registriere dich kostenlos.
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="?page=forum&view=login" class="kunena-btn-primary" style="padding: 10px 20px; font-size: 13.5px;">
                <span class="material-icons" style="font-size: 17px;">login</span> Jetzt anmelden
            </a>
            <a href="?page=forum&view=register" class="kunena-btn-secondary" style="padding: 10px 20px; font-size: 13.5px;">
                <span class="material-icons" style="font-size: 17px;">person_add</span> Kostenlos registrieren
            </a>
        </div>
    </div>
    <?php
    return;
}

$targetId = trim((string)($_GET['id'] ?? ''));
$profileUser = $targetId !== '' ? ForumUserManager::getUserById($targetId) : $currentUser;

if (!$profileUser || ($profileUser['status'] ?? ForumUserManager::STATUS_ACTIVE) === ForumUserManager::STATUS_BANNED) {
    ?>
    <div style="max-width:600px; margin:28px auto; padding:28px; border-radius:14px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; text-align:center;">
        <span class="material-icons" style="font-size:36px; margin-bottom:8px;">person_off</span>
        <h3 style="margin:0 0 6px 0; font-size:1.15rem; font-weight:800;">Benutzerprofil nicht gefunden</h3>
        <p style="margin:0 0 16px 0; font-size:13.5px;">Dieses Mitglied existiert nicht mehr oder das Profil ist derzeit nicht verfügbar.</p>
        <a href="?page=forum" class="kunena-btn-secondary">Zurück zur Forenübersicht</a>
    </div>
    <?php
    return;
}

$pmFlash = $_SESSION['forum_pm_flash'] ?? null;
$contactFlash = $_SESSION['forum_contact_flash'] ?? null;
unset($_SESSION['forum_pm_flash'], $_SESSION['forum_contact_flash']);

$isOwnProfile = (($currentUser['id'] ?? '') === ($profileUser['id'] ?? ''));
$priv = ForumUserManager::getPrivacySettings($profileUser);
$fields = ForumUserManager::getProfileFields($profileUser);

$displayName = ForumUserManager::getDisplayName($profileUser, 'Mitglied');
$showRole = $isOwnProfile || !empty($priv['show_role']);
$effectiveRole = $showRole ? ($profileUser['role'] ?? ForumUserManager::ROLE_USER) : ForumUserManager::ROLE_USER;
$roleColor = ForumUserManager::getRoleColor($effectiveRole);
$roleLabel = ForumUserManager::getRoleLabel($effectiveRole);

$isVisibleOnline = ForumUserManager::isUserOnline((string)$profileUser['id']);
$isHiddenStaff = $isOwnProfile && ForumUserManager::isOnlineStatusHidden($profileUser);

$showStats = $isOwnProfile || !empty($priv['show_post_count']);
$showJoined = $isOwnProfile || !empty($priv['show_joined_date']);
$showBio = $isOwnProfile || !empty($priv['show_bio']);
$showSignature = $isOwnProfile || !empty($priv['show_signature']);
$showEmail = ($isOwnProfile || !empty($priv['show_email'])) && !empty($profileUser['email']);
$showLocation = ($isOwnProfile || !empty($priv['show_location'])) && $fields['location'] !== '';
$showInterests = ($isOwnProfile || !empty($priv['show_interests'])) && $fields['interests'] !== '';
$showWebsite = ($isOwnProfile || !empty($priv['show_website'])) && $fields['website'] !== '';

$canSendPm = !$isOwnProfile && !empty($priv['allow_pm']);
$canSendEmail = !$isOwnProfile && !empty($priv['allow_email_contact']) && filter_var($profileUser['email'] ?? '', FILTER_VALIDATE_EMAIL);

$recentMemberTopics = [];
if (class_exists('ForumManager')) {
    foreach (ForumManager::getRecentTopics(60) as $t) {
        if (($t['author_id'] ?? '') === $profileUser['id'] && ForumManager::canUserReadBoard($t['board_id'] ?? '', $currentUser)) {
            $recentMemberTopics[] = $t;
            if (count($recentMemberTopics) >= 8) {
                break;
            }
        }
    }
}
?>

<style>
.kunena-uprof-wrap {
    max-width: 840px;
    margin: 0 auto 32px auto;
}
.kunena-uprof-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid <?= htmlspecialchars($roleColor) ?>;
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.kunena-uprof-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 24px 26px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.kunena-uprof-avatar {
    width: 66px;
    height: 66px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary, #2e6248), #1e4631);
    color: #ffffff;
    font-size: 26px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(46, 98, 72, 0.25);
}
.kunena-uprof-body {
    padding: 26px;
}
.kunena-uprof-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.kunena-uprof-stat {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.kunena-uprof-section {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
    background: #ffffff;
}
.kunena-uprof-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}
.kunena-uprof-meta-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.kunena-uprof-contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.kunena-uprof-contact-box {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    background: #f8fafc;
}
.kunena-uprof-input, .kunena-uprof-textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    margin-bottom: 10px;
}
</style>

<div class="kunena-uprof-wrap">
    <div class="kunena-uprof-card">
        <div class="kunena-uprof-header">
            <div style="display:flex; align-items:center; gap:16px;">
                <div class="kunena-uprof-avatar">
                    <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <h2 style="margin:0; font-size:1.4rem; font-weight:800; color:<?= htmlspecialchars($roleColor) ?>;">
                            <?= htmlspecialchars($displayName) ?>
                        </h2>
                        <?php if ($showRole): ?>
                            <span style="padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight:700; background:<?= htmlspecialchars($roleColor) ?>18; color:<?= htmlspecialchars($roleColor) ?>; border:1px solid <?= htmlspecialchars($roleColor) ?>40;">
                                <?= htmlspecialchars($roleLabel) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($isVisibleOnline): ?>
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                                <span style="width:7px; height:7px; border-radius:50%; background:#16a34a; display:inline-block;"></span> Online
                            </span>
                        <?php elseif ($isHiddenStaff): ?>
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;" title="Nur für dich sichtbar">
                                <span class="material-icons" style="font-size:13px;">visibility_off</span> Unsichtbar-Modus aktiv
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($showJoined && !empty($profileUser['created_at'])): ?>
                        <div style="font-size:12px; color:#64748b; margin-top:5px;">
                            Mitglied seit <?= htmlspecialchars(substr($profileUser['created_at'], 0, 10)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <?php if ($isOwnProfile): ?>
                    <a href="?page=forum&view=profile" class="kunena-btn-primary" style="padding:8px 16px;">
                        <span class="material-icons" style="font-size:16px;">edit</span> Profil bearbeiten
                    </a>
                <?php else: ?>
                    <?php if ($canSendPm): ?>
                        <a href="#kontakt-bereich" class="kunena-btn-primary" style="padding:8px 14px;">
                            <span class="material-icons" style="font-size:16px;">mail</span> Private Nachricht
                        </a>
                    <?php endif; ?>
                    <?php if ($canSendEmail): ?>
                        <a href="#kontakt-bereich" class="kunena-btn-secondary" style="padding:8px 14px;">
                            <span class="material-icons" style="font-size:16px;">alternate_email</span> E-Mail senden
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="?page=forum" class="kunena-btn-secondary" style="padding:8px 14px;">
                    <span class="material-icons" style="font-size:16px;">arrow_back</span> Zum Forum
                </a>
            </div>
        </div>

        <div class="kunena-uprof-body">
            <?php foreach ([$pmFlash, $contactFlash] as $fl): ?>
                <?php if (is_array($fl) && !empty($fl['text'])): ?>
                    <?php $isOk = ($fl['type'] ?? '') === 'success'; ?>
                    <div style="margin-bottom:18px; padding:12px 16px; border-radius:10px; background:<?= $isOk ? '#f0fdf4' : '#fef2f2' ?>; border:1px solid <?= $isOk ? '#bbf7d0' : '#fecaca' ?>; color:<?= $isOk ? '#166534' : '#991b1b' ?>; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                        <span class="material-icons"><?= $isOk ? 'check_circle' : 'error' ?></span>
                        <span><?= htmlspecialchars((string)$fl['text']) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($showStats || $showJoined): ?>
                <div class="kunena-uprof-stats">
                    <?php if ($showStats): ?>
                        <div class="kunena-uprof-stat">
                            <div style="font-size:1.35rem; font-weight:800; color:var(--primary, #2e6248);"><?= (int)($profileUser['topic_count'] ?? 0) ?></div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:4px; font-weight:600;">Erstellte Themen</div>
                        </div>
                        <div class="kunena-uprof-stat">
                            <div style="font-size:1.35rem; font-weight:800; color:var(--primary, #2e6248);"><?= (int)($profileUser['post_count'] ?? 0) ?></div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:4px; font-weight:600;">Beiträge</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($showJoined): ?>
                        <div class="kunena-uprof-stat">
                            <div style="font-size:1.05rem; font-weight:800; color:#0f172a;"><?= !empty($profileUser['created_at']) ? htmlspecialchars(substr($profileUser['created_at'], 0, 10)) : '–' ?></div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:4px; font-weight:600;">Registriert am</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php
            $hasMetaGrid = $showEmail || $showLocation || $showInterests || $showWebsite;
            $hasBioText = ($showBio && !empty($profileUser['bio']));
            $hasDetails = $hasMetaGrid || $hasBioText;
            ?>
            <div class="kunena-uprof-section">
                <h3 style="margin:0 0 14px 0; font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:19px; color:var(--primary, #2e6248);">badge</span>
                    <span>Über <?= htmlspecialchars($displayName) ?></span>
                </h3>

                <?php if (!$hasDetails): ?>
                    <div style="font-size:13px; color:#94a3b8; font-style:italic;">
                        Dieses Mitglied hat noch keine weiteren Profilangaben freigegeben.
                    </div>
                <?php else: ?>
                    <?php if ($hasMetaGrid): ?>
                        <div class="kunena-uprof-meta-grid" style="margin-bottom:<?= $hasBioText ? '16px' : '0' ?>;">
                            <?php if ($showEmail): ?>
                                <div class="kunena-uprof-meta-item">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary, #2e6248); margin-top:2px;">email</span>
                                    <div style="min-width:0; word-break:break-word;">
                                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">
                                            E-Mail-Adresse
                                            <?php if ($isOwnProfile && empty($priv['show_email'])): ?>
                                                <span style="text-transform:none; font-weight:500;">(nur für dich sichtbar)</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="mailto:<?= htmlspecialchars((string)$profileUser['email']) ?>" style="font-size:13px; color:var(--primary, #2e6248); font-weight:600; text-decoration:underline;">
                                            <?= htmlspecialchars((string)$profileUser['email']) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($showLocation): ?>
                                <div class="kunena-uprof-meta-item">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary, #2e6248); margin-top:2px;">location_on</span>
                                    <div>
                                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Wohnort</div>
                                        <div style="font-size:13px; color:#0f172a; font-weight:600;"><?= htmlspecialchars($fields['location']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($showInterests): ?>
                                <div class="kunena-uprof-meta-item">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary, #2e6248); margin-top:2px;">interests</span>
                                    <div>
                                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Interessen</div>
                                        <div style="font-size:13px; color:#0f172a; font-weight:600;"><?= htmlspecialchars($fields['interests']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($showWebsite): ?>
                                <div class="kunena-uprof-meta-item">
                                    <span class="material-icons" style="font-size:18px; color:var(--primary, #2e6248); margin-top:2px;">language</span>
                                    <div style="min-width:0; word-break:break-word;">
                                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Website</div>
                                        <a href="<?= htmlspecialchars($fields['website']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:13px; color:var(--primary, #2e6248); font-weight:600; text-decoration:underline;">
                                            <?= htmlspecialchars($fields['website']) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasBioText): ?>
                        <div style="font-size:13.5px; line-height:1.65; color:#334155; background:#f8fafc; padding:12px 14px; border-radius:8px; border:1px solid #e2e8f0;">
                            <?= nl2br(htmlspecialchars((string)$profileUser['bio'])) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($showSignature && !empty($profileUser['signature'])): ?>
                <div class="kunena-uprof-section">
                    <h3 style="margin:0 0 8px 0; font-size:14px; font-weight:800; color:#0f172a;">Signatur</h3>
                    <div style="font-size:13px; color:#64748b; font-style:italic; line-height:1.55;">
                        <?= nl2br(htmlspecialchars((string)$profileUser['signature'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$isOwnProfile): ?>
                <div id="kontakt-bereich" class="kunena-uprof-section">
                    <h3 style="margin:0 0 14px 0; font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:19px; color:var(--primary, #2e6248);">connect_without_contact</span>
                        <span>Kontakt mit <?= htmlspecialchars($displayName) ?> aufnehmen</span>
                    </h3>

                    <?php if (!$canSendPm && !$canSendEmail): ?>
                        <div style="font-size:13px; color:#94a3b8; font-style:italic;">
                            Dieses Mitglied hat den Empfang von privaten Nachrichten und E-Mail-Kontaktanfragen deaktiviert.
                        </div>
                    <?php else: ?>
                        <div class="kunena-uprof-contact-grid">
                            <?php if ($canSendPm): ?>
                                <form method="post" action="?forum_auth=send_pm" data-waf-encode="pm_subject,pm_body" class="kunena-uprof-contact-box">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="recipient_id" value="<?= htmlspecialchars((string)$profileUser['id']) ?>">
                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars('?page=forum&view=user&id=' . urlencode((string)$profileUser['id'])) ?>">

                                    <div style="display:flex; align-items:center; gap:6px; font-weight:800; font-size:13.5px; color:#0f172a; margin-bottom:10px;">
                                        <span class="material-icons" style="font-size:17px; color:var(--primary, #2e6248);">mark_email_unread</span>
                                        <span>Private Nachricht (PN)</span>
                                    </div>
                                    <input type="text" name="pm_subject" class="kunena-uprof-input" placeholder="Betreff" maxlength="150" required>
                                    <textarea name="pm_body" rows="4" class="kunena-uprof-textarea" placeholder="Deine private Nachricht im Forum..." maxlength="5000" required></textarea>
                                    <button type="submit" class="kunena-btn-primary" style="padding:8px 14px; font-size:12.5px; cursor:pointer;">
                                        <span class="material-icons" style="font-size:15px;">send</span> PN absenden
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($canSendEmail): ?>
                                <form method="post" action="?forum_auth=send_member_email" data-waf-encode="mail_subject,mail_body" class="kunena-uprof-contact-box">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="recipient_id" value="<?= htmlspecialchars((string)$profileUser['id']) ?>">

                                    <div style="display:flex; align-items:center; gap:6px; font-weight:800; font-size:13.5px; color:#0f172a; margin-bottom:4px;">
                                        <span class="material-icons" style="font-size:17px; color:var(--primary, #2e6248);">alternate_email</span>
                                        <span>E-Mail senden</span>
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; margin-bottom:10px;">
                                        Die E-Mail wird über das System weitergeleitet. Deine eigene E-Mail-Adresse wird als Antwortadresse (Reply-To) mitgesendet.
                                    </div>
                                    <input type="text" name="mail_subject" class="kunena-uprof-input" placeholder="Betreff der E-Mail" maxlength="120" required>
                                    <textarea name="mail_body" rows="4" class="kunena-uprof-textarea" placeholder="Deine E-Mail-Nachricht..." maxlength="3000" required></textarea>
                                    <button type="submit" class="kunena-btn-secondary" style="padding:8px 14px; font-size:12.5px; cursor:pointer;">
                                        <span class="material-icons" style="font-size:15px;">forward_to_inbox</span> E-Mail absenden
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="kunena-uprof-section" style="margin-bottom:0;">
                <h3 style="margin:0 0 12px 0; font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="font-size:19px; color:var(--primary, #2e6248);">forum</span>
                    <span>Aktuelle Themen von <?= htmlspecialchars($displayName) ?></span>
                </h3>
                <?php if (empty($recentMemberTopics)): ?>
                    <div style="font-size:13px; color:#94a3b8; font-style:italic;">Noch keine Themen in zugänglichen Forenbereichen erstellt.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($recentMemberTopics as $rt): ?>
                            <a href="?page=forum&view=topic&id=<?= urlencode($rt['id']) ?>"
                               style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 14px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0; text-decoration:none; color:#0f172a;">
                                <span style="font-weight:700; font-size:13.5px;"><?= htmlspecialchars($rt['title']) ?></span>
                                <span style="font-size:11.5px; color:#64748b; flex-shrink:0;"><?= ForumManager::formatRelativeTime($rt['created_at'] ?? '') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
