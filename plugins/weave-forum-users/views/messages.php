<?php
/**
 * Frontend: Private Nachrichten (PN) – Posteingang, Gesendet & Neue Nachricht
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
if (!$currentUser) {
    echo '<div style="max-width:520px; margin:40px auto; padding:24px; border-radius:14px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; text-align:center;">'
       . '<strong>Anmeldung erforderlich:</strong> Bitte melde dich an, um deine privaten Nachrichten einzusehen.<br>'
       . '<a href="?page=forum&view=login" class="kunena-btn-primary" style="margin-top:12px;">Zur Anmeldung</a>'
       . '</div>';
    return;
}

$box = trim((string)($_GET['box'] ?? 'inbox'));
if (!in_array($box, ['inbox', 'sent', 'compose'], true)) {
    $box = 'inbox';
}
$readMsgId = trim((string)($_GET['msg'] ?? ''));

$pmFlash = $_SESSION['forum_pm_flash'] ?? null;
unset($_SESSION['forum_pm_flash']);

$myId = (string)$currentUser['id'];
$inbox = ForumMessageManager::getInbox($myId);
$sent = ForumMessageManager::getSent($myId);
$unreadCount = ForumMessageManager::getUnreadCount($myId);

$activeMessage = null;
if ($readMsgId !== '') {
    $activeMessage = ForumMessageManager::getMessage($readMsgId, $myId);
    if ($activeMessage && $activeMessage['recipient_id'] === $myId && empty($activeMessage['is_read'])) {
        ForumMessageManager::markAsRead($readMsgId, $myId);
        $activeMessage['is_read'] = 1;
        $unreadCount = max(0, $unreadCount - 1);
    }
}

$userCache = [];
$resolveMemberName = static function(string $uid) use (&$userCache): string {
    if ($uid === '') return 'Mitglied';
    if (!isset($userCache[$uid])) {
        $userCache[$uid] = ForumUserManager::getUserById($uid);
    }
    $u = $userCache[$uid];
    if (!$u) return 'Gelöschtes Mitglied';
    return ForumUserManager::getDisplayName($u, 'Mitglied');
};

$eligibleRecipients = [];
foreach (ForumUserManager::getAllUsers() as $u) {
    if (($u['id'] ?? '') === $myId) continue;
    if (($u['status'] ?? ForumUserManager::STATUS_ACTIVE) !== ForumUserManager::STATUS_ACTIVE) continue;
    $uPriv = ForumUserManager::getPrivacySettings($u);
    if (empty($uPriv['allow_pm'])) continue;
    $eligibleRecipients[] = [
        'id'   => (string)$u['id'],
        'name' => ForumUserManager::getDisplayName($u, 'Mitglied'),
    ];
}
usort($eligibleRecipients, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$preselectedTo = trim((string)($_GET['to'] ?? ''));
$prefillSubject = trim((string)($_GET['subject'] ?? ''));
?>

<style>
.kunena-pm-wrap {
    max-width: 920px;
    margin: 0 auto 32px auto;
}
.kunena-pm-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid var(--primary, #2e6248);
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.kunena-pm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px 24px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.kunena-pm-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.kunena-pm-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    color: #475569;
    background: #ffffff;
    border: 1px solid #cbd5e1;
}
.kunena-pm-tab.active {
    background: var(--primary, #2e6248);
    border-color: var(--primary, #2e6248);
    color: #ffffff;
}
.kunena-pm-body {
    padding: 24px;
}
.kunena-pm-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    margin-bottom: 10px;
    text-decoration: none;
    color: #0f172a;
}
.kunena-pm-row.unread {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.kunena-pm-row:hover {
    border-color: #94a3b8;
}
</style>

<div class="kunena-pm-wrap">
    <div class="kunena-pm-card">
        <div class="kunena-pm-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="material-icons" style="font-size:24px; color:var(--primary, #2e6248);">mail</span>
                <div>
                    <h2 style="margin:0; font-size:1.25rem; font-weight:800; color:#0f172a;">Private Nachrichten</h2>
                    <div style="font-size:12px; color:#64748b;">Postfach von <?= htmlspecialchars(ForumUserManager::getDisplayName($currentUser)) ?></div>
                </div>
            </div>

            <div class="kunena-pm-tabs">
                <a href="?page=forum&view=messages&box=inbox" class="kunena-pm-tab <?= $box === 'inbox' && !$activeMessage ? 'active' : '' ?>">
                    <span class="material-icons" style="font-size:16px;">inbox</span>
                    <span>Posteingang</span>
                    <?php if ($unreadCount > 0): ?>
                        <span style="padding:1px 7px; border-radius:999px; font-size:11px; background:#dc2626; color:#fff;"><?= (int)$unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="?page=forum&view=messages&box=sent" class="kunena-pm-tab <?= $box === 'sent' && !$activeMessage ? 'active' : '' ?>">
                    <span class="material-icons" style="font-size:16px;">outbox</span>
                    <span>Gesendet (<?= count($sent) ?>)</span>
                </a>
                <a href="?page=forum&view=messages&box=compose" class="kunena-pm-tab <?= $box === 'compose' ? 'active' : '' ?>">
                    <span class="material-icons" style="font-size:16px;">edit_square</span>
                    <span>Neue Nachricht</span>
                </a>
            </div>
        </div>

        <div class="kunena-pm-body">
            <?php if (is_array($pmFlash) && !empty($pmFlash['text'])): ?>
                <?php $isOk = ($pmFlash['type'] ?? '') === 'success'; ?>
                <div style="margin-bottom:18px; padding:12px 16px; border-radius:10px; background:<?= $isOk ? '#f0fdf4' : '#fef2f2' ?>; border:1px solid <?= $isOk ? '#bbf7d0' : '#fecaca' ?>; color:<?= $isOk ? '#166534' : '#991b1b' ?>; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:600;">
                    <span class="material-icons"><?= $isOk ? 'check_circle' : 'error' ?></span>
                    <span><?= htmlspecialchars((string)$pmFlash['text']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($activeMessage): ?>
                <?php
                $isIncoming = ($activeMessage['recipient_id'] === $myId);
                $partnerId = $isIncoming ? $activeMessage['sender_id'] : $activeMessage['recipient_id'];
                $partnerName = $resolveMemberName($partnerId);
                $backBox = $isIncoming ? 'inbox' : 'sent';
                $replySubject = str_starts_with(mb_strtolower($activeMessage['subject']), 're:') ? $activeMessage['subject'] : 'Re: ' . $activeMessage['subject'];
                ?>
                <div style="border:1px solid #e2e8f0; border-radius:12px; padding:20px; background:#f8fafc;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; border-bottom:1px solid #e2e8f0; padding-bottom:14px; margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0 0 6px 0; font-size:1.1rem; font-weight:800; color:#0f172a;">
                                <?= htmlspecialchars($activeMessage['subject']) ?>
                            </h3>
                            <div style="font-size:12.5px; color:#64748b;">
                                <?= $isIncoming ? 'Von' : 'An' ?>:
                                <a href="?page=forum&view=user&id=<?= urlencode($partnerId) ?>" style="color:var(--primary, #2e6248); font-weight:700;">
                                    <?= htmlspecialchars($partnerName) ?>
                                </a>
                                &bull; <?= htmlspecialchars(substr($activeMessage['created_at'], 0, 16)) ?>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <?php if ($isIncoming): ?>
                                <a href="?page=forum&view=messages&box=compose&to=<?= urlencode($partnerId) ?>&subject=<?= urlencode($replySubject) ?>" class="kunena-btn-primary" style="padding:7px 14px; font-size:12.5px;">
                                    <span class="material-icons" style="font-size:15px;">reply</span> Antworten
                                </a>
                            <?php endif; ?>
                            <form method="post" action="?forum_auth=delete_pm" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="msg_id" value="<?= htmlspecialchars($activeMessage['id']) ?>">
                                <input type="hidden" name="box" value="<?= htmlspecialchars($backBox) ?>">
                                <button type="submit" class="kunena-btn-secondary" style="padding:7px 12px; font-size:12.5px; color:#dc2626; cursor:pointer;" onclick="return confirm('Nachricht aus deinem Postfach entfernen?');">
                                    <span class="material-icons" style="font-size:15px;">delete</span> Löschen
                                </button>
                            </form>
                            <a href="?page=forum&view=messages&box=<?= urlencode($backBox) ?>" class="kunena-btn-secondary" style="padding:7px 12px; font-size:12.5px;">
                                Zurück
                            </a>
                        </div>
                    </div>
                    <div style="font-size:14px; line-height:1.7; color:#1e293b; background:#ffffff; padding:16px; border-radius:8px; border:1px solid #e2e8f0;">
                        <?= nl2br(htmlspecialchars($activeMessage['body'])) ?>
                    </div>
                </div>

            <?php elseif ($box === 'compose'): ?>
                <form method="post" action="?forum_auth=send_pm" data-waf-encode="pm_subject,pm_body" style="max-width:640px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="redirect_to" value="?page=forum&view=messages&box=sent">

                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:6px;">Empfänger:</label>
                        <?php if (empty($eligibleRecipients)): ?>
                            <div style="font-size:13px; color:#64748b; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                                Derzeit sind keine weiteren aktiven Mitglieder für den Empfang privater Nachrichten verfügbar.
                            </div>
                        <?php else: ?>
                            <select name="recipient_id" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; background:#ffffff;">
                                <option value="">– Mitglied auswählen –</option>
                                <?php foreach ($eligibleRecipients as $rec): ?>
                                    <option value="<?= htmlspecialchars($rec['id']) ?>" <?= $preselectedTo === $rec['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rec['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:6px;">Betreff:</label>
                        <input type="text" name="pm_subject" value="<?= htmlspecialchars($prefillSubject) ?>" maxlength="150" required
                               style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;" placeholder="Betreff deiner Nachricht...">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:6px;">Nachricht:</label>
                        <textarea name="pm_body" rows="6" maxlength="5000" required
                                  style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; font-family:inherit;" placeholder="Schreibe deine private Nachricht..."></textarea>
                    </div>

                    <button type="submit" class="kunena-btn-primary" style="padding:10px 20px; font-size:13.5px; cursor:pointer;" <?= empty($eligibleRecipients) ? 'disabled' : '' ?>>
                        <span class="material-icons" style="font-size:16px;">send</span> Private Nachricht senden
                    </button>
                </form>

            <?php else: ?>
                <?php $list = ($box === 'sent') ? $sent : $inbox; ?>
                <?php if (empty($list)): ?>
                    <div style="text-align:center; padding:32px 16px; color:#64748b; font-size:13.5px;">
                        <span class="material-icons" style="font-size:36px; color:#94a3b8; display:block; margin-bottom:8px;">inbox</span>
                        <?= $box === 'sent' ? 'Du hast noch keine privaten Nachrichten gesendet.' : 'Dein Posteingang ist leer.' ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($list as $m): ?>
                        <?php
                        $isUnread = ($box === 'inbox' && empty($m['is_read']));
                        $partnerId = ($box === 'sent') ? $m['recipient_id'] : $m['sender_id'];
                        $partnerName = $resolveMemberName($partnerId);
                        ?>
                        <a href="?page=forum&view=messages&box=<?= urlencode($box) ?>&msg=<?= urlencode($m['id']) ?>" class="kunena-pm-row <?= $isUnread ? 'unread' : '' ?>">
                            <div style="min-width:0;">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <?php if ($isUnread): ?>
                                        <span style="padding:2px 7px; border-radius:999px; font-size:10px; font-weight:800; background:#16a34a; color:#fff;">NEU</span>
                                    <?php endif; ?>
                                    <span style="font-weight:<?= $isUnread ? '800' : '700' ?>; font-size:14px; color:#0f172a;">
                                        <?= htmlspecialchars($m['subject']) ?>
                                    </span>
                                </div>
                                <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                    <?= $box === 'sent' ? 'An' : 'Von' ?>: <strong><?= htmlspecialchars($partnerName) ?></strong>
                                    &bull; <?= htmlspecialchars(mb_substr(preg_replace('/\s+/u', ' ', $m['body']), 0, 80)) ?><?= mb_strlen($m['body']) > 80 ? '…' : '' ?>
                                </div>
                            </div>
                            <span style="font-size:11.5px; color:#64748b; flex-shrink:0;"><?= htmlspecialchars(substr($m['created_at'], 0, 16)) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
