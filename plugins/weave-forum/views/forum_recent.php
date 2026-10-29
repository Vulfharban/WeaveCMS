<?php
/**
 * Frontend: Kunena-inspirierte Ansicht "Aktuelle Themen" (Recent Topics)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$rawTopics = ForumManager::getRecentTopics(50);
$topics = array_values(array_filter($rawTopics, fn($t) => ForumManager::canUserReadBoard($t['board_id'], $currentUser)));
$allBoards = ForumManager::getBoards();
$boardsMap = [];
foreach ($allBoards as $b) {
    $boardsMap[$b['id']] = $b['title'];
}
?>

<div class="kunena-recent-wrapper">
    <!-- Header Banner -->
    <div class="kunena-cat-card" style="margin-bottom: 20px; border-left-color: #3b82f6;">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: #3b82f6;">access_time</span>
                <h2 class="kunena-cat-title" style="font-size: 1.3rem;">
                    Aktuelle Themen &amp; Diskussionen
                </h2>
                <span class="kunena-cat-badge" style="background:#dbeafe; color:#1d4ed8;">
                    <?= count($topics) ?> aktiv
                </span>
            </div>
        </div>
        <div style="padding:12px 22px; font-size:13px; color:#64748b; background:#ffffff;">
            Hier siehst du die zuletzt aktualisierten und diskutierten Forenthemen im gesamten WeaveCMS-Forum.
        </div>
    </div>

    <!-- Topics Table -->
    <div class="kunena-cat-card">
        <div class="kunena-subhead">
            <div style="display:flex; align-items:center; gap:6px;">
                <span>Thema / Forum</span>
                <span class="material-icons" style="font-size: 14px; opacity:0.6;">arrow_downward</span>
            </div>
            <div class="kunena-subhead-stats">
                Antworten / Aufrufe
            </div>
            <div class="kunena-subhead-last">
                Letzter Beitrag
            </div>
        </div>

        <div class="kunena-board-list">
            <?php if (empty($topics)): ?>
                <div style="padding:2.5rem; text-align:center; color:#64748b; font-style:italic; font-size:13px;">
                    Aktuell sind noch keine Themen vorhanden.
                </div>
            <?php else: ?>
                <?php foreach ($topics as $t): ?>
                    <?php 
                    $replyCount = max(0, ForumManager::countPosts($t['id']) - 1);
                    $views = (int)($t['views'] ?? 0);
                    $boardTitle = $boardsMap[$t['board_id'] ?? ''] ?? 'Forum';

                    $relCreated = ForumManager::formatRelativeTime($t['created_at']);
                    $relLastPost = ForumManager::formatRelativeTime($t['last_post_at']);

                    $authorUser = ($currentUser && class_exists('ForumUserManager')) ? (ForumUserManager::getUserById($t['author_id'] ?? '') ?? ForumUserManager::findUser($t['author_name'])) : null;
                    $authorPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($authorUser) : ['show_role' => true];
                    $authorRole = !empty($authorPriv['show_role']) ? ($authorUser['role'] ?? 'forum_user') : 'forum_user';
                    $authorRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                    $authorDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($authorUser, $t['author_name']) : $t['author_name'];

                    $lastPostUser = ($currentUser && class_exists('ForumUserManager')) ? ForumUserManager::findUser($t['last_post_user']) : null;
                    $lastPostPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($lastPostUser) : ['show_role' => true];
                    $lastPostRole = !empty($lastPostPriv['show_role']) ? ($lastPostUser['role'] ?? 'forum_user') : 'forum_user';
                    $lastPostRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($lastPostRole) : '#2563eb';
                    $lastPostDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($lastPostUser, $t['last_post_user']) : $t['last_post_user'];
                    ?>
                    <div class="kunena-board-row" style="<?= !empty($t['is_pinned']) ? 'background: #fffdf5;' : '' ?>">
                        <div class="kunena-board-main">
                            <div style="margin-top: 2px;">
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="material-icons" style="color: #f59e0b; font-size: 20px;">push_pin</span>
                                <?php else: ?>
                                    <span class="material-icons" style="color: #94a3b8; font-size: 20px;">article</span>
                                <?php endif; ?>
                            </div>
                            <div class="kunena-board-info">
                                <div class="kunena-board-title-line">
                                    <?php if (!empty($t['is_pinned'])): ?>
                                        <span class="kunena-badge kunena-badge-pinned">Angepinnt</span>
                                    <?php endif; ?>
                                    <?php if (!empty($t['is_solved'])): ?>
                                        <span class="kunena-badge kunena-badge-solved">
                                            <span class="material-icons" style="font-size:11px;">check</span> Gelöst
                                        </span>
                                    <?php endif; ?>
                                    <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="kunena-board-title">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="kunena-board-desc" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <span>In <a href="?page=forum&view=board&id=<?= urlencode($t['board_id'] ?? '') ?>" style="color:var(--primary, #2e6248); font-weight:700; text-decoration:none;"><?= htmlspecialchars($boardTitle) ?></a></span>
                                    <?php if ($currentUser): ?>
                                        <span>•</span>
                                        <span>von <span style="color: <?= $authorRoleColor ?>; font-weight:700;"><?= htmlspecialchars($authorDisplayName) ?></span></span>
                                    <?php endif; ?>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($relCreated) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="kunena-board-stats">
                            <div class="kunena-stat-item">
                                <span class="kunena-stat-num"><?= $replyCount ?></span>
                                <span class="kunena-stat-lbl">Antworten</span>
                            </div>
                            <div class="kunena-stat-item">
                                <span class="kunena-stat-num"><?= $views ?></span>
                                <span class="kunena-stat-lbl">Aufrufe</span>
                            </div>
                        </div>

                        <div class="kunena-board-lastpost">
                            <?php if ($currentUser): ?>
                                <div class="kunena-avatar-mini" style="background-color: <?= $lastPostRoleColor ?>18; color: <?= $lastPostRoleColor ?>; border: 1px solid <?= $lastPostRoleColor ?>40;">
                                    <?= strtoupper(mb_substr($lastPostDisplayName, 0, 1)) ?>
                                </div>
                                <div class="kunena-lastpost-info">
                                    <div style="font-size: 11.5px; color: #475569;">
                                        Letzter von <span style="color: <?= $lastPostRoleColor ?>; font-weight: 700;"><?= htmlspecialchars($lastPostDisplayName) ?></span>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($relLastPost) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="kunena-avatar-mini" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                    <span class="material-icons" style="font-size: 15px;">schedule</span>
                                </div>
                                <div class="kunena-lastpost-info">
                                    <div style="font-size: 11.5px; color: #475569; font-weight: 600;">
                                        Letzte Aktivität
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($relLastPost) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
