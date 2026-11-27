<?php
/**
 * Frontend: Kunena-inspirierte Forenübersicht (Kategorien, Boards, Mitglieder & Statistiken)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}
$tr = static fn(string $key, string $default, array $params = []): string => function_exists('__')
    ? __($key, $default, null, $params)
    : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);

$categories = ForumManager::getCategories();
$allBoards = ForumManager::getBoards();
$globalStats = ForumManager::getGlobalStats();
$onlineStats = class_exists('ForumUserManager') ? ForumUserManager::getOnlineUsersStats() : [
    'members' => [], 'member_count' => 0, 'guest_count' => 8, 'total_online' => 8
];
?>

<div class="kunena-forum-wrapper">
    <?php if (empty($categories)): ?>
        <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:3rem; text-align:center; color:#64748b;">
            <?= htmlspecialchars($tr('forum.index.empty', 'Noch keine Foren-Bereiche eingerichtet.')) ?>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $catIndex => $cat): ?>
            <?php 
            $boards = array_filter($allBoards, fn($b) => ($b['category_id'] ?? '') === $cat['id']);
            usort($boards, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
            $catTopicsCount = ForumManager::countCategoryTopics($cat['id']);
            $catCollapseId = 'cat_collapse_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $cat['id']);
            ?>
            <div class="kunena-cat-card">
                <!-- Kunena Category Header Bar -->
                <div class="kunena-cat-header">
                    <div class="kunena-cat-title-group">
                        <span class="material-icons kunena-cat-icon"><?= htmlspecialchars($cat['icon'] ?? 'forum') ?></span>
                        <h3 class="kunena-cat-title">
                            <?= htmlspecialchars($cat['title']) ?>
                        </h3>
                        <span class="kunena-cat-badge">
                            <?= $catTopicsCount ?> <?= htmlspecialchars($catTopicsCount === 1 ? $tr('forum.topic_singular', 'Thema') : $tr('forum.topic_plural', 'Themen')) ?>
                        </span>
                    </div>
                    <button type="button" 
                            class="kunena-collapse-btn"
                            onclick="toggleKunenaCategory('<?= $catCollapseId ?>', this)"
                            title="<?= htmlspecialchars($tr('forum.index.collapse_title', 'Kategorie einklappen / ausklappen')) ?>"
                            aria-label="<?= htmlspecialchars($tr('forum.index.collapse_title', 'Kategorie einklappen / ausklappen')) ?>">
                        <span class="material-icons" style="font-size: 18px;">remove</span>
                    </button>
                </div>

                <!-- Sub-header bar & Board Rows -->
                <div id="<?= $catCollapseId ?>" class="kunena-category-body">
                    <div class="kunena-subhead">
                        <div><?= htmlspecialchars($tr('forum.index.col_category_board', 'Kategorie / Forum')) ?></div>
                        <div class="kunena-subhead-stats"><?= htmlspecialchars($tr('forum.index.col_topics_posts', 'Themen & Beiträge')) ?></div>
                        <div class="kunena-subhead-last"><?= htmlspecialchars($tr('forum.index.col_last_post', 'Letzter Beitrag')) ?></div>
                    </div>

                    <!-- Boards Rows -->
                    <div class="kunena-board-list">
                        <?php if (empty($boards)): ?>
                            <div style="padding:1rem 1.5rem; font-size:13px; color:#64748b; font-style:italic;">
                                <?= htmlspecialchars($tr('forum.index.no_boards_in_cat', 'In dieser Kategorie wurden noch keine Foren angelegt.')) ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($boards as $board): ?>
                                <?php 
                                $canReadBoard = ForumManager::canUserReadBoard($board, $currentUser);
                                $topicCount = ForumManager::countTopics($board['id']);
                                $postCount = ForumManager::countBoardPosts($board['id']);
                                $latestTopics = $canReadBoard ? ForumManager::getTopics($board['id'], 1, 1) : [];
                                $latestTopic = !empty($latestTopics) ? $latestTopics[0] : null;
                                ?>
                                <div class="kunena-board-row">
                                    <!-- Left Column: Icon, Title, Description -->
                                    <div class="kunena-board-main">
                                        <div class="kunena-board-icon">
                                            <span class="material-icons"><?= htmlspecialchars($board['icon'] ?? 'chat_bubble_outline') ?></span>
                                        </div>
                                        <div class="kunena-board-info">
                                            <div class="kunena-board-title-line">
                                                <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="kunena-board-title">
                                                    <?= htmlspecialchars($board['title']) ?>
                                                </a>
                                                <?php if (!$canReadBoard): ?>
                                                    <span class="kunena-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:11px; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; font-weight:700;">
                                                        <span class="material-icons" style="font-size:12px;">lock</span> <?= htmlspecialchars($tr('forum.index.members_only_badge', 'Nur für Mitglieder')) ?>
                                                    </span>
                                                <?php elseif (!empty($board['is_locked'])): ?>
                                                    <span class="material-icons kunena-lock-icon" title="<?= htmlspecialchars($tr('forum.board.closed', 'Forum geschlossen')) ?>">lock</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($board['description'])): ?>
                                                <p class="kunena-board-desc">
                                                    <?= htmlspecialchars($board['description']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Middle Column: Stats (Themen & Beiträge) -->
                                    <div class="kunena-board-stats">
                                        <div class="kunena-stat-item">
                                            <span class="kunena-stat-num"><?= $topicCount ?></span>
                                            <span class="kunena-stat-lbl"><?= htmlspecialchars($topicCount === 1 ? $tr('forum.topic_singular', 'Thema') : $tr('forum.topic_plural', 'Themen')) ?></span>
                                        </div>
                                        <div class="kunena-stat-item">
                                            <span class="kunena-stat-num"><?= $postCount ?></span>
                                            <span class="kunena-stat-lbl"><?= htmlspecialchars($postCount === 1 ? $tr('forum.post_singular', 'Beitrag') : $tr('forum.post_plural', 'Beiträge')) ?></span>
                                        </div>
                                    </div>

                                    <!-- Right Column: Last Post Block -->
                                    <div class="kunena-board-lastpost">
                                        <?php if (!$canReadBoard): ?>
                                            <div class="kunena-no-post" style="color: #64748b; font-size: 12px; display: flex; align-items: center; gap: 6px;">
                                                <span class="material-icons" style="font-size: 16px; color:#d97706;">lock</span>
                                                <span><?= htmlspecialchars($tr('forum.index.protected_content', 'Inhalte geschützt • Anmelden')) ?></span>
                                            </div>
                                        <?php elseif ($latestTopic): ?>
                                            <?php 
                                            $relTime = ForumManager::formatRelativeTime($latestTopic['last_post_at']);
                                            if ($currentUser):
                                                $authorUser = class_exists('ForumUserManager') ? ForumUserManager::findUser($latestTopic['last_post_user']) : null;
                                                $authorPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($authorUser) : ['show_role' => true];
                                                $authorRole = !empty($authorPriv['show_role']) ? ($authorUser['role'] ?? 'forum_user') : 'forum_user';
                                                $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                                                $lastPostDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($authorUser, $latestTopic['last_post_user']) : $latestTopic['last_post_user'];
                                            ?>
                                                <div class="kunena-avatar-mini" style="background-color: <?= $roleColor ?>18; color: <?= $roleColor ?>; border: 1px solid <?= $roleColor ?>40;">
                                                    <?= strtoupper(mb_substr($lastPostDisplayName, 0, 1)) ?>
                                                </div>
                                                <div class="kunena-lastpost-info">
                                                    <div class="kunena-lastpost-title">
                                                        <a href="?page=forum&view=topic&id=<?= urlencode($latestTopic['id']) ?>" title="<?= htmlspecialchars($latestTopic['title']) ?>">
                                                            <?= htmlspecialchars($latestTopic['title']) ?>
                                                        </a>
                                                    </div>
                                                    <div class="kunena-lastpost-meta">
                                                        <?= htmlspecialchars($tr('forum.by_author', 'von')) ?> <span style="color: <?= $roleColor ?>; font-weight: 700;"><?= htmlspecialchars($lastPostDisplayName) ?></span>
                                                        • <span title="<?= htmlspecialchars($latestTopic['last_post_at']) ?>"><?= $relTime ?></span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Gast-Ansicht: Keine Benutzerdaten anzeigen -->
                                                <div class="kunena-avatar-mini" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                                    <span class="material-icons" style="font-size: 15px;">chat_bubble_outline</span>
                                                </div>
                                                <div class="kunena-lastpost-info">
                                                    <div class="kunena-lastpost-title">
                                                        <a href="?page=forum&view=topic&id=<?= urlencode($latestTopic['id']) ?>" title="<?= htmlspecialchars($latestTopic['title']) ?>">
                                                            <?= htmlspecialchars($latestTopic['title']) ?>
                                                        </a>
                                                    </div>
                                                    <div class="kunena-lastpost-meta">
                                                        <span title="<?= htmlspecialchars($latestTopic['last_post_at']) ?>"><?= $relTime ?></span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="kunena-no-post">
                                                <span class="material-icons" style="font-size: 16px;">schedule</span>
                                                <span><?= htmlspecialchars($tr('forum.index.no_posts', 'Noch keine Beiträge')) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ───────────────────────────────────────────────────────────── -->
    <!-- Kunena Bottom Widgets: Members & Statistics (Screenshot 2)    -->
    <!-- ───────────────────────────────────────────────────────────── -->
    <div class="kunena-footer-widgets" style="margin-top: 32px;">
        <!-- 1. Members Box -->
        <div class="kunena-widget-card">
            <div class="kunena-widget-header">
                <div class="kunena-widget-header-title">
                    <span class="material-icons" style="font-size: 20px; color: #475569;">group</span>
                    <span><?= htmlspecialchars($tr('forum.index.who_is_online', 'Wer ist online & Mitglieder')) ?></span>
                </div>
            </div>
            <div class="kunena-widget-body">
                <div>
                    <?= htmlspecialchars($tr('forum.index.online_summary', 'Aktuell sind {members} Mitglieder und {guests} Gäste online', ['members' => (string)$onlineStats['member_count'], 'guests' => (string)$onlineStats['guest_count']])) ?>
                </div>
                <?php if ($currentUser && !empty($onlineStats['members'])): ?>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px;">
                        <span style="font-weight:600; color:#64748b;"><?= htmlspecialchars($tr('forum.index.online_members_label', 'Anwesende Mitglieder:')) ?></span>
                        <?php foreach ($onlineStats['members'] as $m): ?>
                            <?php if (!empty($m['id'])): ?>
                                <a href="?page=forum&view=user&id=<?= urlencode($m['id']) ?>" style="display:inline-flex; align-items:center; gap:5px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= $m['color'] ?>15; color:<?= $m['color'] ?>; border:1px solid <?= $m['color'] ?>35; text-decoration:none;" title="<?= htmlspecialchars($tr('forum.topic.view_profile', 'Mitgliederprofil ansehen')) ?>">
                                    <span class="kunena-legend-dot" style="background-color: <?= $m['color'] ?>;"></span>
                                    <?= htmlspecialchars($m['username']) ?>
                                </a>
                            <?php else: ?>
                                <span style="display:inline-flex; align-items:center; gap:5px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= $m['color'] ?>15; color:<?= $m['color'] ?>; border:1px solid <?= $m['color'] ?>35;">
                                    <span class="kunena-legend-dot" style="background-color: <?= $m['color'] ?>;"></span>
                                    <?= htmlspecialchars($m['username']) ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!$currentUser): ?>
                    <div style="margin-top:8px; font-size:11.5px; color:#64748b; font-style:italic;">
                        <span class="material-icons" style="font-size:13px; vertical-align:middle;">lock</span>
                        <?= htmlspecialchars($tr('forum.topic.profile_members_only', 'Profil nur für Mitglieder')) ?>
                    </div>
                <?php endif; ?>

                <!-- Role Legend -->
                <div class="kunena-legend-row">
                    <span style="font-weight:700; color:#64748b;"><?= htmlspecialchars($tr('forum.index.role_legend', 'Rollen-Legende:')) ?></span>
                    <span class="kunena-legend-item" style="color: #dc2626;">
                        <span class="kunena-legend-dot" style="background-color: #dc2626;"></span> <?= htmlspecialchars($tr('forum.role_admin', 'Site Administrator')) ?>
                    </span>
                    <span class="kunena-legend-item" style="color: #2563eb;">
                        <span class="kunena-legend-dot" style="background-color: #2563eb;"></span> <?= htmlspecialchars($tr('forum.role_moderator', 'Moderator')) ?>
                    </span>
                    <span class="kunena-legend-item" style="color: #475569;">
                        <span class="kunena-legend-dot" style="background-color: #475569;"></span> <?= htmlspecialchars($tr('forum.role_member', 'Mitglied')) ?>
                    </span>
                    <span class="kunena-legend-item" style="color: #94a3b8; font-weight:600;">
                        <span class="kunena-legend-dot" style="background-color: #94a3b8;"></span> <?= htmlspecialchars($tr('forum.role_guest', 'Gast')) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Statistics Box -->
        <div class="kunena-widget-card stats">
            <div class="kunena-widget-header">
                <div class="kunena-widget-header-title">
                    <span class="material-icons" style="font-size: 20px; color: #2563eb;">bar_chart</span>
                    <span><?= htmlspecialchars($tr('forum.index.stats_title', 'Foren-Statistiken')) ?></span>
                </div>
            </div>
            <div class="kunena-widget-body">
                <div class="kunena-stats-grid">
                    <!-- Column 1 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.total_posts', 'Beiträge gesamt:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['total_posts'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.total_boards', 'Foren-Bereiche:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['total_boards'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.topics_today', 'Themen heute:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['topics_today'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.posts_today', 'Antworten heute:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['posts_today'] ?></span>
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.total_topics', 'Themen gesamt:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['total_topics'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.total_categories', 'Haupt-Kategorien:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['total_categories'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.topics_yesterday', 'Themen gestern:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['topics_yesterday'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.posts_yesterday', 'Antworten gestern:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['posts_yesterday'] ?></span>
                        </div>
                    </div>

                    <!-- Column 3 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.total_members', 'Registrierte Mitglieder:')) ?></span>
                            <span class="kunena-stats-val"><?= $globalStats['total_members'] ?></span>
                        </div>
                        <?php if ($currentUser): ?>
                            <div class="kunena-stats-row">
                                <span class="kunena-stats-label"><?= htmlspecialchars($tr('forum.index.newest_member', 'Neuestes Mitglied:')) ?></span>
                                <span class="kunena-stats-val" style="color: var(--primary, #2e6248);"><?= htmlspecialchars($globalStats['latest_member']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleKunenaCategory(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    const isHidden = el.style.display === 'none';
    el.style.display = isHidden ? '' : 'none';
    const icon = btn.querySelector('.material-icons');
    if (icon) {
        icon.textContent = isHidden ? 'remove' : 'add';
    }
    try {
        localStorage.setItem('kunena_' + id, isHidden ? 'open' : 'closed');
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.kunena-category-body').forEach(function(el) {
        const id = el.id;
        if (localStorage.getItem('kunena_' + id) === 'closed') {
            el.style.display = 'none';
            const btn = el.previousElementSibling.querySelector('.kunena-collapse-btn .material-icons');
            if (btn) btn.textContent = 'add';
        }
    });
});
</script>
