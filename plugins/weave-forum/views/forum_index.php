<?php
/**
 * Frontend: Kunena-inspirierte Forenübersicht (Kategorien, Boards, Mitglieder & Statistiken)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

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
            Noch keine Foren-Bereiche eingerichtet.
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
                            <?= $catTopicsCount ?> <?= $catTopicsCount === 1 ? 'Thema' : 'Themen' ?>
                        </span>
                    </div>
                    <button type="button" 
                            class="kunena-collapse-btn"
                            onclick="toggleKunenaCategory('<?= $catCollapseId ?>', this)"
                            title="Kategorie einklappen / ausklappen"
                            aria-label="Kategorie umschalten">
                        <span class="material-icons" style="font-size: 18px;">remove</span>
                    </button>
                </div>

                <!-- Sub-header bar & Board Rows -->
                <div id="<?= $catCollapseId ?>" class="kunena-category-body">
                    <div class="kunena-subhead">
                        <div>Kategorie / Forum</div>
                        <div class="kunena-subhead-stats">Themen &amp; Beiträge</div>
                        <div class="kunena-subhead-last">Letzter Beitrag</div>
                    </div>

                    <!-- Boards Rows -->
                    <div class="kunena-board-list">
                        <?php if (empty($boards)): ?>
                            <div style="padding:1rem 1.5rem; font-size:13px; color:#64748b; font-style:italic;">
                                In dieser Kategorie wurden noch keine Foren angelegt.
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
                                                        <span class="material-icons" style="font-size:12px;">lock</span> Nur für Mitglieder
                                                    </span>
                                                <?php elseif (!empty($board['is_locked'])): ?>
                                                    <span class="material-icons kunena-lock-icon" title="Forum geschlossen">lock</span>
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
                                            <span class="kunena-stat-lbl"><?= $topicCount === 1 ? 'Thema' : 'Themen' ?></span>
                                        </div>
                                        <div class="kunena-stat-item">
                                            <span class="kunena-stat-num"><?= $postCount ?></span>
                                            <span class="kunena-stat-lbl"><?= $postCount === 1 ? 'Beitrag' : 'Beiträge' ?></span>
                                        </div>
                                    </div>

                                    <!-- Right Column: Last Post Block -->
                                    <div class="kunena-board-lastpost">
                                        <?php if (!$canReadBoard): ?>
                                            <div class="kunena-no-post" style="color: #64748b; font-size: 12px; display: flex; align-items: center; gap: 6px;">
                                                <span class="material-icons" style="font-size: 16px; color:#d97706;">lock</span>
                                                <span>Inhalte geschützt &bull; <a href="?page=forum&view=login" style="color:var(--primary); font-weight:700;">Anmelden</a></span>
                                            </div>
                                        <?php elseif ($latestTopic): ?>
                                            <?php 
                                            $authorUser = class_exists('ForumUserManager') ? ForumUserManager::findUser($latestTopic['last_post_user']) : null;
                                            $authorRole = $authorUser['role'] ?? 'forum_user';
                                            $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                                            $relTime = ForumManager::formatRelativeTime($latestTopic['last_post_at']);
                                            ?>
                                            <div class="kunena-avatar-mini" style="background-color: <?= $roleColor ?>18; color: <?= $roleColor ?>; border: 1px solid <?= $roleColor ?>40;">
                                                <?= strtoupper(mb_substr($latestTopic['last_post_user'], 0, 1)) ?>
                                            </div>
                                            <div class="kunena-lastpost-info">
                                                <div class="kunena-lastpost-title">
                                                    <a href="?page=forum&view=topic&id=<?= urlencode($latestTopic['id']) ?>" title="<?= htmlspecialchars($latestTopic['title']) ?>">
                                                        <?= htmlspecialchars($latestTopic['title']) ?>
                                                    </a>
                                                </div>
                                                <div class="kunena-lastpost-meta">
                                                    von <span style="color: <?= $roleColor ?>; font-weight: 700;"><?= htmlspecialchars($latestTopic['last_post_user']) ?></span>
                                                    • <span title="<?= htmlspecialchars($latestTopic['last_post_at']) ?>"><?= $relTime ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="kunena-no-post">
                                                <span class="material-icons" style="font-size: 16px;">schedule</span>
                                                <span>Noch keine Beiträge</span>
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
                    <span>Wer ist online &amp; Mitglieder</span>
                </div>
            </div>
            <div class="kunena-widget-body">
                <div>
                    Aktuell sind <strong><?= $onlineStats['member_count'] ?> Mitglied<?= $onlineStats['member_count'] === 1 ? '' : 'er' ?></strong> und <strong><?= $onlineStats['guest_count'] ?> Gäste</strong> online (Gesamt: <?= $onlineStats['total_online'] ?>).
                </div>
                <?php if (!empty($onlineStats['members'])): ?>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px;">
                        <span style="font-weight:600; color:#64748b;">Anwesende Mitglieder:</span>
                        <?php foreach ($onlineStats['members'] as $m): ?>
                            <span style="display:inline-flex; align-items:center; gap:5px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= $m['color'] ?>15; color:<?= $m['color'] ?>; border:1px solid <?= $m['color'] ?>35;">
                                <span class="kunena-legend-dot" style="background-color: <?= $m['color'] ?>;"></span>
                                <?= htmlspecialchars($m['username']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Role Legend -->
                <div class="kunena-legend-row">
                    <span style="font-weight:700; color:#64748b;">Rollen-Legende:</span>
                    <span class="kunena-legend-item" style="color: #dc2626;">
                        <span class="kunena-legend-dot" style="background-color: #dc2626;"></span> Site Administrator
                    </span>
                    <span class="kunena-legend-item" style="color: #2563eb;">
                        <span class="kunena-legend-dot" style="background-color: #2563eb;"></span> Moderator
                    </span>
                    <span class="kunena-legend-item" style="color: #475569;">
                        <span class="kunena-legend-dot" style="background-color: #475569;"></span> Mitglied
                    </span>
                    <span class="kunena-legend-item" style="color: #94a3b8; font-weight:600;">
                        <span class="kunena-legend-dot" style="background-color: #94a3b8;"></span> Gast
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Statistics Box -->
        <div class="kunena-widget-card stats">
            <div class="kunena-widget-header">
                <div class="kunena-widget-header-title">
                    <span class="material-icons" style="font-size: 20px; color: #2563eb;">bar_chart</span>
                    <span>Foren-Statistiken</span>
                </div>
            </div>
            <div class="kunena-widget-body">
                <div class="kunena-stats-grid">
                    <!-- Column 1 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Beiträge gesamt:</span>
                            <span class="kunena-stats-val"><?= $globalStats['total_posts'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Foren-Bereiche:</span>
                            <span class="kunena-stats-val"><?= $globalStats['total_boards'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Themen heute:</span>
                            <span class="kunena-stats-val"><?= $globalStats['topics_today'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Antworten heute:</span>
                            <span class="kunena-stats-val"><?= $globalStats['posts_today'] ?></span>
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Themen gesamt:</span>
                            <span class="kunena-stats-val"><?= $globalStats['total_topics'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Haupt-Kategorien:</span>
                            <span class="kunena-stats-val"><?= $globalStats['total_categories'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Themen gestern:</span>
                            <span class="kunena-stats-val"><?= $globalStats['topics_yesterday'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Antworten gestern:</span>
                            <span class="kunena-stats-val"><?= $globalStats['posts_yesterday'] ?></span>
                        </div>
                    </div>

                    <!-- Column 3 -->
                    <div class="kunena-stats-col">
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Registrierte Mitglieder:</span>
                            <span class="kunena-stats-val"><?= $globalStats['total_members'] ?></span>
                        </div>
                        <div class="kunena-stats-row">
                            <span class="kunena-stats-label">Neuestes Mitglied:</span>
                            <span class="kunena-stats-val" style="color: var(--primary, #2e6248);"><?= htmlspecialchars($globalStats['latest_member']) ?></span>
                        </div>
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
