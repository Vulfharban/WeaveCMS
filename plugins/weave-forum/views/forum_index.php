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

<div class="kunena-forum-wrapper space-y-6">
    <?php if (empty($categories)): ?>
        <div class="card p-8 text-center text-muted border rounded-xl">
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
            <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card mb-6">
                <!-- Kunena Category Header Bar (Screenshot 1) -->
                <div class="kunena-header px-5 py-3.5 bg-gray-50 dark:bg-gray-800/80 border-b flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="material-icons text-primary text-xl"><?= htmlspecialchars($cat['icon'] ?? 'forum') ?></span>
                        <h3 class="font-bold text-base text-foreground tracking-tight m-0">
                            <?= htmlspecialchars($cat['title']) ?>
                        </h3>
                        <span class="kunena-topic-count text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                            <?= $catTopicsCount ?> <?= $catTopicsCount === 1 ? 'Thema' : 'Themen' ?>
                        </span>
                    </div>
                    <button type="button" 
                            class="kunena-collapse-btn text-muted hover:text-foreground text-xs p-1 rounded border border-border transition flex items-center justify-center w-6 h-6"
                            onclick="toggleKunenaCategory('<?= $catCollapseId ?>', this)"
                            title="Kategorie einklappen / ausklappen"
                            aria-label="Kategorie umschalten">
                        <span class="material-icons text-sm">remove</span>
                    </button>
                </div>

                <!-- Sub-header bar: Category / Last Post -->
                <div id="<?= $catCollapseId ?>" class="kunena-category-body">
                    <div class="kunena-subhead px-5 py-2 bg-gray-100/70 dark:bg-gray-900/40 border-b text-[11px] font-bold text-muted uppercase tracking-wider grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-8">Kategorie / Forum</div>
                        <div class="hidden md:block md:col-span-4">Letzter Beitrag</div>
                    </div>

                    <!-- Boards Rows -->
                    <div class="kunena-board-list divide-y divide-border">
                        <?php if (empty($boards)): ?>
                            <div class="p-4 text-xs text-muted italic">In dieser Kategorie wurden noch keine Foren angelegt.</div>
                        <?php else: ?>
                            <?php foreach ($boards as $board): ?>
                                <?php 
                                $topicCount = ForumManager::countTopics($board['id']);
                                $postCount = ForumManager::countBoardPosts($board['id']);
                                $latestTopics = ForumManager::getTopics($board['id'], 1, 1);
                                $latestTopic = !empty($latestTopics) ? $latestTopics[0] : null;
                                ?>
                                <div class="kunena-row px-5 py-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                    <!-- Left Column: Icon, Title + count, Description -->
                                    <div class="md:col-span-8 flex items-start gap-3.5">
                                        <div class="kunena-icon-box shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center mt-0.5">
                                            <span class="material-icons text-xl"><?= htmlspecialchars($board['icon'] ?? 'chat_bubble_outline') ?></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="font-bold text-base text-foreground hover:text-primary transition hover:underline">
                                                    <?= htmlspecialchars($board['title']) ?>
                                                </a>
                                                <span class="text-xs text-muted font-medium">
                                                    <?= $topicCount ?> <?= $topicCount === 1 ? 'Thema' : 'Themen' ?>
                                                    <?php if ($postCount > 0): ?>
                                                        <span class="opacity-75">• <?= $postCount ?> Beiträge</span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if (!empty($board['is_locked'])): ?>
                                                    <span class="material-icons text-xs text-amber-500" title="Forum geschlossen">lock</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($board['description'])): ?>
                                                <p class="text-xs text-muted mt-1 leading-relaxed">
                                                    <?= htmlspecialchars($board['description']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Right Column: Last Post Block (Screenshot 1) -->
                                    <div class="md:col-span-4 text-xs">
                                        <?php if ($latestTopic): ?>
                                            <?php 
                                            $authorUser = class_exists('ForumUserManager') ? ForumUserManager::findUser($latestTopic['last_post_user']) : null;
                                            $authorRole = $authorUser['role'] ?? 'forum_user';
                                            $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                                            $relTime = ForumManager::formatRelativeTime($latestTopic['last_post_at']);
                                            ?>
                                            <div class="flex items-center gap-3">
                                                <div class="kunena-avatar-mini shrink-0 w-8 h-8 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-xs shadow-inner">
                                                    <?= strtoupper(mb_substr($latestTopic['last_post_user'], 0, 1)) ?>
                                                </div>
                                                <div class="min-w-0 flex-1 leading-tight">
                                                    <div class="font-semibold text-foreground truncate max-w-[240px]">
                                                        <a href="?page=forum&view=topic&id=<?= urlencode($latestTopic['id']) ?>" class="hover:underline hover:text-primary" title="<?= htmlspecialchars($latestTopic['title']) ?>">
                                                            <?= htmlspecialchars($latestTopic['title']) ?>
                                                        </a>
                                                    </div>
                                                    <div class="text-[11px] text-muted mt-0.5">
                                                        von <span class="font-bold" style="color: <?= $roleColor ?>;"><?= htmlspecialchars($latestTopic['last_post_user']) ?></span>
                                                    </div>
                                                    <div class="text-[10px] text-muted opacity-80 mt-0.5">
                                                        <?= htmlspecialchars($relTime) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted italic flex items-center gap-1.5 opacity-70">
                                                <span class="material-icons text-sm">hourglass_empty</span>
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
    <div class="kunena-footer-widgets space-y-4 mt-8 pt-4">
        <!-- 1. Members Box (Screenshot 2) -->
        <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card">
            <div class="kunena-header px-5 py-3 bg-gray-50 dark:bg-gray-800/80 border-b flex items-center justify-between">
                <div class="flex items-center gap-2 font-bold text-sm text-foreground">
                    <span class="material-icons text-primary text-lg">group</span>
                    <span>Mitglieder</span>
                </div>
            </div>
            <div class="p-5 text-xs text-muted space-y-2.5">
                <div class="text-foreground">
                    Aktuell online: <strong><?= $onlineStats['member_count'] ?> Mitglieder</strong> und <strong><?= $onlineStats['guest_count'] ?> Gäste</strong> online.
                </div>
                <?php if (!empty($onlineStats['members'])): ?>
                    <div class="flex items-center gap-2 flex-wrap pt-1">
                        <span class="text-muted font-semibold">Anwesend:</span>
                        <?php foreach ($onlineStats['members'] as $m): ?>
                            <span class="font-bold px-1.5 py-0.5 rounded bg-black/5 dark:bg-white/5" style="color: <?= $m['color'] ?>;">
                                <?= htmlspecialchars($m['username']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- Role Legend (Screenshot 2) -->
                <div class="pt-2 border-t border-border/60 flex items-center gap-3 flex-wrap text-[11px]">
                    <span class="font-semibold text-muted">Legende:</span>
                    <span class="inline-flex items-center gap-1 font-bold text-red-600">
                        <span class="w-2 h-2 rounded-full bg-red-600 inline-block"></span> Site Administrator
                    </span>
                    <span class="inline-flex items-center gap-1 font-bold text-blue-600">
                        <span class="w-2 h-2 rounded-full bg-blue-600 inline-block"></span> Moderator
                    </span>
                    <span class="inline-flex items-center gap-1 font-bold text-slate-700 dark:text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-slate-500 inline-block"></span> Mitglied
                    </span>
                    <span class="inline-flex items-center gap-1 font-semibold text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span> Gast
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Statistics Box (Screenshot 2) -->
        <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card">
            <div class="kunena-header px-5 py-3 bg-gray-50 dark:bg-gray-800/80 border-b flex items-center justify-between">
                <div class="flex items-center gap-2 font-bold text-sm text-foreground">
                    <span class="material-icons text-primary text-lg">bar_chart</span>
                    <span>Statistiken</span>
                </div>
            </div>
            <div class="p-5 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Column 1 -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Beiträge gesamt:</span>
                            <strong class="text-foreground"><?= $globalStats['total_posts'] ?></strong>
                        </div>
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Foren-Bereiche:</span>
                            <strong class="text-foreground"><?= $globalStats['total_boards'] ?></strong>
                        </div>
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Themen heute:</span>
                            <strong class="text-foreground"><?= $globalStats['topics_today'] ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Antworten heute:</span>
                            <strong class="text-foreground"><?= $globalStats['posts_today'] ?></strong>
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Themen gesamt:</span>
                            <strong class="text-foreground"><?= $globalStats['total_topics'] ?></strong>
                        </div>
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Kategorien:</span>
                            <strong class="text-foreground"><?= $globalStats['total_categories'] ?></strong>
                        </div>
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Themen gestern:</span>
                            <strong class="text-foreground"><?= $globalStats['topics_yesterday'] ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Antworten gestern:</span>
                            <strong class="text-foreground"><?= $globalStats['posts_yesterday'] ?></strong>
                        </div>
                    </div>

                    <!-- Column 3 -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Registrierte Mitglieder:</span>
                            <strong class="text-foreground"><?= $globalStats['total_members'] ?></strong>
                        </div>
                        <div class="flex justify-between border-b border-border/40 pb-1">
                            <span class="text-muted">Neuestes Mitglied:</span>
                            <strong class="text-primary font-bold"><?= htmlspecialchars($globalStats['latest_member']) ?></strong>
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
