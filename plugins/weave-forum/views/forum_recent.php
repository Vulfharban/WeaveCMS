<?php
/**
 * Frontend: Kunena-inspirierte Ansicht "Aktuelle Themen" (Recent Topics)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$topics = ForumManager::getRecentTopics(30);
$allBoards = ForumManager::getBoards();
$boardsMap = [];
foreach ($allBoards as $b) {
    $boardsMap[$b['id']] = $b['title'];
}
?>

<div class="kunena-recent-wrapper space-y-4">
    <!-- Header Banner -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-5">
        <h2 class="text-xl md:text-2xl font-bold flex items-center gap-2 text-foreground">
            <span class="material-icons text-primary">access_time</span>
            <span>Aktuelle Themen & Diskussionen</span>
            <span class="kunena-topic-count text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                <?= count($topics) ?> aktiv
            </span>
        </h2>
        <p class="text-xs text-muted mt-1">
            Hier siehst du die zuletzt aktualisierten und diskutierten Forenthemen im gesamten WeaveCMS-Forum.
        </p>
    </div>

    <!-- Topics Table -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card mb-6">
        <div class="kunena-subhead px-5 py-2.5 bg-gray-100/80 dark:bg-gray-800/80 border-b text-[11px] font-bold text-muted uppercase tracking-wider grid grid-cols-12 gap-4 items-center">
            <div class="col-span-12 md:col-span-7 flex items-center gap-1">
                <span>Thema / Forum</span>
                <span class="material-icons text-xs opacity-60">arrow_downward</span>
            </div>
            <div class="hidden md:block md:col-span-2 text-center">
                Antworten / Aufrufe
            </div>
            <div class="hidden md:block md:col-span-3">
                Letzter Beitrag
            </div>
        </div>

        <div class="divide-y divide-border">
            <?php if (empty($topics)): ?>
                <div class="p-8 text-center text-muted italic text-xs">
                    Aktuell sind noch keine Themen vorhanden.
                </div>
            <?php else: ?>
                <?php foreach ($topics as $t): ?>
                    <?php 
                    $replyCount = max(0, ForumManager::countPosts($t['id']) - 1);
                    $views = (int)($t['views'] ?? 0);
                    $boardTitle = $boardsMap[$t['board_id'] ?? ''] ?? 'Forum';

                    $authorUser = class_exists('ForumUserManager') ? ForumUserManager::findUser($t['author_name']) : null;
                    $authorRole = $authorUser['role'] ?? 'forum_user';
                    $authorRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';

                    $lastPostUser = class_exists('ForumUserManager') ? ForumUserManager::findUser($t['last_post_user']) : null;
                    $lastPostRole = $lastPostUser['role'] ?? 'forum_user';
                    $lastPostRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($lastPostRole) : '#2563eb';

                    $relCreated = ForumManager::formatRelativeTime($t['created_at']);
                    $relLastPost = ForumManager::formatRelativeTime($t['last_post_at']);
                    ?>
                    <div class="kunena-topic-row px-5 py-3.5 grid grid-cols-12 gap-4 items-center hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition <?= !empty($t['is_pinned']) ? 'bg-amber-50/25 dark:bg-amber-950/15' : '' ?>">
                        <div class="col-span-12 md:col-span-7 flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="material-icons text-amber-500 text-lg">push_pin</span>
                                <?php else: ?>
                                    <span class="material-icons text-muted/70 text-lg">article</span>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <?php if (!empty($t['is_pinned'])): ?>
                                        <span class="kunena-badge kunena-badge-pinned">Angepinnt</span>
                                    <?php endif; ?>
                                    <?php if (!empty($t['is_solved'])): ?>
                                        <span class="kunena-badge kunena-badge-solved">
                                            <span class="material-icons text-[10px]">check</span> Gelöst
                                        </span>
                                    <?php endif; ?>
                                    <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="font-bold text-sm text-foreground hover:text-primary transition hover:underline">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="text-[11px] text-muted mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>In <a href="?page=forum&view=board&id=<?= urlencode($t['board_id'] ?? '') ?>" class="text-primary hover:underline font-semibold"><?= htmlspecialchars($boardTitle) ?></a></span>
                                    <span>•</span>
                                    <span>von <span class="font-bold" style="color: <?= $authorRoleColor ?>;"><?= htmlspecialchars($t['author_name']) ?></span></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($relCreated) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="hidden md:block md:col-span-2 text-center text-xs">
                            <div class="font-medium text-muted">
                                Antworten: <strong class="text-foreground"><?= $replyCount ?></strong>
                            </div>
                            <div class="text-[11px] text-muted opacity-80 mt-0.5">
                                Aufrufe: <strong class="text-foreground"><?= $views ?></strong>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-3 text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="kunena-avatar-mini shrink-0 w-7 h-7 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-xs shadow-inner">
                                    <?= strtoupper(mb_substr($t['last_post_user'], 0, 1)) ?>
                                </div>
                                <div class="min-w-0 flex-1 leading-tight">
                                    <div class="text-[11px] text-muted">
                                        Letzter von <span class="font-bold" style="color: <?= $lastPostRoleColor ?>;"><?= htmlspecialchars($t['last_post_user']) ?></span>
                                    </div>
                                    <div class="text-[10px] text-muted opacity-80 mt-0.5">
                                        <?= htmlspecialchars($relLastPost) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
