<?php
/**
 * Frontend: Kunena-inspirierte Moderationszentrale (für Moderatoren & Administratoren)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$isMod = class_exists('ForumUserManager') ? ForumUserManager::userCan('moderate', $currentUser) : false;

if (!$isMod) {
    echo '<div style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:2rem; border-radius:12px; text-align:center; font-weight:600;">Zugriff verweigert: Diese Moderationsseite steht nur Administratoren und Moderatoren zur Verfügung.</div>';
    return;
}

$reports = ForumManager::getReports('open');
$allBoards = ForumManager::getBoards();
$allTopics = ForumManager::getRecentTopics(50);
$pinnedTopics = array_filter($allTopics, fn($t) => !empty($t['is_pinned']));
$lockedTopics = array_filter($allTopics, fn($t) => !empty($t['is_locked']));
?>

<div class="kunena-moderation-wrapper">
    <!-- Header Banner -->
    <div class="kunena-cat-card" style="border-left-color: #d97706; margin-bottom: 24px;">
        <div class="kunena-cat-header" style="background: linear-gradient(to right, #fffbeb, #fef3c7);">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: #d97706;">shield</span>
                <h2 class="kunena-cat-title" style="font-size: 1.35rem; color: #92400e;">
                    Foren-Moderationszentrale
                </h2>
                <span class="kunena-cat-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                    <?= htmlspecialchars($currentUser['username'] ?? 'Moderator') ?> (<?= htmlspecialchars(class_exists('ForumUserManager') ? ForumUserManager::getRoleLabel($currentUser['role'] ?? 'forum_moderator') : 'Moderator') ?>)
                </span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="admin/?tab=tab-plugins&plugin_settings=weave-forum#tab-plugins" class="kunena-btn-secondary" style="font-size:11.5px; padding:5px 12px;" target="_blank">
                    <span class="material-icons" style="font-size:15px;">settings</span> CMS Backend-Verwaltung
                </a>
            </div>
        </div>
        <div style="padding:14px 22px; font-size:13px; color:#78350f; background:#fffdf5; line-height:1.5;">
            Hier findest du alle offenen Beitragsmeldungen, gesperrten Themen und hast direkten Zugriff auf alle Moderationswerkzeuge des WeaveCMS-Forums.
        </div>
    </div>

    <!-- Moderation Infobox: Wo finde ich Moderations-Optionen? -->
    <div class="kunena-cat-card" style="border-left-color: #2563eb; margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: #2563eb;">info</span>
                <h3 class="kunena-cat-title" style="font-size: 1.1rem;">Wo und wie moderierst du im Forum?</h3>
            </div>
        </div>
        <div style="padding:16px 22px; font-size:13px; color:#334155; line-height:1.6; background:#ffffff;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                <div style="padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                    <strong style="display:flex; align-items:center; gap:6px; color:#0f172a; margin-bottom:6px;">
                        <span class="material-icons" style="font-size:18px; color:#2563eb;">edit_note</span> Direkt im Thema:
                    </strong>
                    <p style="margin:0; font-size:12.5px; color:#64748b;">
                        Öffne ein beliebiges Thema. Direkt unter dem Titel siehst du die <strong>gelbe Moderations-Leiste</strong>: Thema anpinnen, für Antworten schließen, in ein anderes Forum verschieben oder löschen.
                    </p>
                </div>
                <div style="padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                    <strong style="display:flex; align-items:center; gap:6px; color:#0f172a; margin-bottom:6px;">
                        <span class="material-icons" style="font-size:18px; color:#dc2626;">delete_sweep</span> Bei jedem Beitrag:
                    </strong>
                    <p style="margin:0; font-size:12.5px; color:#64748b;">
                        Rechts unten an jedem Forumsbeitrag befinden sich für dich die Schaltflächen <strong>„Bearbeiten“</strong> und <strong>„Löschen“</strong> sowie das Flaggen-Symbol für Meldungen.
                    </p>
                </div>
                <div style="padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                    <strong style="display:flex; align-items:center; gap:6px; color:#0f172a; margin-bottom:6px;">
                        <span class="material-icons" style="font-size:18px; color:#059669;">manage_accounts</span> Benutzerverwaltung:
                    </strong>
                    <p style="margin:0; font-size:12.5px; color:#64748b;">
                        Im CMS-Backend unter <em>Plugins ➔ weave-forum-users</em> kannst du Rollen vergeben, Verwarnungen aussprechen, Benutzer sperren oder DSGVO-anonymisieren.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Offene Meldungen (Queue) -->
    <div class="kunena-cat-card" style="border-left-color: <?= !empty($reports) ? '#dc2626' : '#10b981' ?>; margin-bottom: 24px;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: <?= !empty($reports) ? '#dc2626' : '#10b981' ?>;">
                    <?= !empty($reports) ? 'report' : 'check_circle' ?>
                </span>
                <h3 class="kunena-cat-title" style="font-size: 1.15rem;">
                    Offene Beitragsmeldungen (<?= count($reports) ?>)
                </h3>
            </div>
        </div>

        <?php if (empty($reports)): ?>
            <div style="padding:2.5rem; text-align:center; color:#64748b; font-size:13px; background:#ffffff;">
                <span class="material-icons" style="font-size:36px; color:#10b981; display:block; margin-bottom:8px;">task_alt</span>
                Aktuell liegen keine gemeldeten Beiträge vor. Alles sauber!
            </div>
        <?php else: ?>
            <div style="background:#ffffff; padding:16px 20px;">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php foreach ($reports as $r): ?>
                        <?php $reportedPost = ForumManager::getPost($r['post_id']); ?>
                        <div style="border:1px solid #fca5a5; border-radius:10px; padding:16px; background:#fff5f5;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                                <div>
                                    <span class="kunena-badge" style="background:#dc2626; color:#ffffff;">Meldung</span>
                                    <span style="font-size:12px; color:#64748b; margin-left:6px;">
                                        Gemeldet von <strong><?= htmlspecialchars($r['reporter_name']) ?></strong> am <?= htmlspecialchars($r['created_at']) ?>
                                    </span>
                                </div>
                                <div style="display:flex; gap:6px;">
                                    <?php if ($reportedPost): ?>
                                        <a href="?page=forum&view=topic&id=<?= urlencode($reportedPost['topic_id']) ?>#post-<?= urlencode($reportedPost['id']) ?>" 
                                           class="kunena-btn-secondary" style="font-size:11px; padding:4px 10px;" target="_blank">
                                            <span class="material-icons" style="font-size:14px;">visibility</span> Zum Beitrag
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" action="?forum_action=resolve_report" style="display:inline;">
                                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($r['id']) ?>">
                                        <input type="hidden" name="resolution" value="dismissed">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <button type="submit" class="kunena-btn-secondary" style="font-size:11px; padding:4px 10px;" title="Meldung abweisen">
                                            Verwerfen
                                        </button>
                                    </form>
                                    <form method="post" action="?forum_action=delete_post" style="display:inline;" onsubmit="return confirm('Beitrag unwiderruflich löschen?');">
                                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($r['post_id']) ?>">
                                        <input type="hidden" name="topic_id" value="<?= htmlspecialchars($reportedPost['topic_id'] ?? '') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <button type="submit" class="kunena-btn-primary" style="background:#dc2626; font-size:11px; padding:4px 10px;">
                                            Beitrag löschen
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php if (!empty($r['reason'])): ?>
                                <div style="font-size:12px; margin-bottom:8px; color:#991b1b;">
                                    <strong>Meldegrund:</strong> <?= htmlspecialchars($r['reason']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($reportedPost): ?>
                                <div style="font-size:12px; padding:10px 14px; background:#ffffff; border:1px solid #fecaca; border-radius:6px; color:#1e293b; max-height:120px; overflow-y:auto;">
                                    <strong>Inhalt (Autor: <?= htmlspecialchars($reportedPost['author_name']) ?>):</strong><br>
                                    <?= nl2br(htmlspecialchars(mb_substr($reportedPost['content'], 0, 400))) ?>...
                                </div>
                            <?php else: ?>
                                <div style="font-size:12px; color:#64748b; font-style:italic;">Der gemeldete Beitrag existiert nicht mehr.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. Angepinnte & Geschlossene Themen -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:24px;">
        <!-- Angepinnte Themen -->
        <div class="kunena-cat-card" style="border-left-color: #f59e0b; margin-bottom:0;">
            <div class="kunena-cat-header">
                <div class="kunena-cat-title-group">
                    <span class="material-icons kunena-cat-icon" style="color: #f59e0b;">push_pin</span>
                    <h3 class="kunena-cat-title" style="font-size: 1.05rem;">Angepinnte Themen (<?= count($pinnedTopics) ?>)</h3>
                </div>
            </div>
            <div style="padding:14px 20px; background:#ffffff; font-size:12.5px;">
                <?php if (empty($pinnedTopics)): ?>
                    <p style="color:#64748b; font-style:italic; margin:0;">Keine Themen aktuell angepinnt.</p>
                <?php else: ?>
                    <ul style="margin:0; padding-left:18px; line-height:1.7;">
                        <?php foreach ($pinnedTopics as $pt): ?>
                            <li>
                                <a href="?page=forum&view=topic&id=<?= urlencode($pt['id']) ?>" style="font-weight:700; color:#1e293b; text-decoration:none;">
                                    <?= htmlspecialchars($pt['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Geschlossene Themen -->
        <div class="kunena-cat-card" style="border-left-color: #64748b; margin-bottom:0;">
            <div class="kunena-cat-header">
                <div class="kunena-cat-title-group">
                    <span class="material-icons kunena-cat-icon" style="color: #64748b;">lock</span>
                    <h3 class="kunena-cat-title" style="font-size: 1.05rem;">Geschlossene Themen (<?= count($lockedTopics) ?>)</h3>
                </div>
            </div>
            <div style="padding:14px 20px; background:#ffffff; font-size:12.5px;">
                <?php if (empty($lockedTopics)): ?>
                    <p style="color:#64748b; font-style:italic; margin:0;">Keine Themen aktuell gesperrt.</p>
                <?php else: ?>
                    <ul style="margin:0; padding-left:18px; line-height:1.7;">
                        <?php foreach ($lockedTopics as $lt): ?>
                            <li>
                                <a href="?page=forum&view=topic&id=<?= urlencode($lt['id']) ?>" style="font-weight:700; color:#1e293b; text-decoration:none;">
                                    <?= htmlspecialchars($lt['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
