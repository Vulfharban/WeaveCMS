<?php
/**
 * Frontend: Kunena-inspirierte Board-Ansicht (Themen-Tabelle, Paginierung, Filter)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$boardId = trim($_GET['id'] ?? '');
$board = ForumManager::getBoard($boardId);

if (!$board) {
    echo '<div class="alert alert-danger p-6 text-center">Das gewünschte Forum wurde nicht gefunden.</div>';
    return;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$totalTopics = ForumManager::countTopics($boardId);
$topics = ForumManager::getTopics($boardId, $page, $perPage);
$totalPages = max(1, ceil($totalTopics / $perPage));

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canCreateTopic = class_exists('ForumUserManager') ? ForumUserManager::userCan('create_topic', $currentUser, $board) : false;
?>

<div class="kunena-board-wrapper space-y-4">
    <!-- Board Header Banner (Screenshot 3) -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold flex items-center gap-2 text-foreground">
                    <span class="material-icons text-primary"><?= htmlspecialchars($board['icon'] ?? 'forum') ?></span>
                    <span><?= htmlspecialchars($board['title']) ?></span>
                    <span class="kunena-topic-count text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                        <?= $totalTopics ?> <?= $totalTopics === 1 ? 'Thema' : 'Themen' ?>
                    </span>
                </h2>
                <?php if (!empty($board['description'])): ?>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        <?= htmlspecialchars($board['description']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($canCreateTopic && empty($board['is_locked'])): ?>
                    <a href="?page=forum&view=new_topic&board=<?= urlencode($board['id']) ?>" class="btn btn-primary flex items-center gap-1.5 font-bold text-xs py-2 px-3.5 shadow-sm">
                        <span class="material-icons text-sm">edit</span> Neues Thema erstellen
                    </a>
                <?php elseif (!empty($board['is_locked'])): ?>
                    <span class="badge badge-warning flex items-center gap-1 text-xs">
                        <span class="material-icons text-xs">lock</span> Forum geschlossen
                    </span>
                <?php else: ?>
                    <a href="?page=forum&view=login" class="btn btn-outline btn-xs font-semibold">
                        Anmelden zum Schreiben
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action & Pagination Bar (Screenshot 3) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <!-- Pagination Buttons -->
        <div class="flex items-center gap-1 flex-wrap">
            <?php if ($totalPages > 1): ?>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=1" class="kunena-page-btn <?= $page === 1 ? 'disabled opacity-50' : '' ?>">Start</a>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= max(1, $page - 1) ?>" class="kunena-page-btn <?= $page === 1 ? 'disabled opacity-50' : '' ?>">Zurück</a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= $i ?>" class="kunena-page-btn <?= $i === $page ? 'active font-bold' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= min($totalPages, $page + 1) ?>" class="kunena-page-btn <?= $page === $totalPages ? 'disabled opacity-50' : '' ?>">Weiter</a>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= $totalPages ?>" class="kunena-page-btn <?= $page === $totalPages ? 'disabled opacity-50' : '' ?>">Ende</a>
            <?php else: ?>
                <span class="text-muted text-[11px] font-medium">Seite 1 von 1 (<?= $totalTopics ?> Themen)</span>
            <?php endif; ?>
        </div>

        <!-- Quick Filter / Search in Board -->
        <div class="flex items-center gap-2">
            <form method="get" action="" class="relative">
                <input type="hidden" name="page" value="forum">
                <input type="hidden" name="view" value="search">
                <input type="hidden" name="board" value="<?= htmlspecialchars($boardId) ?>">
                <input type="text" name="q" placeholder="In diesem Forum suchen..." 
                       class="text-xs py-1.5 pl-3 pr-8 rounded-lg border border-border bg-card w-48 md:w-56 focus:outline-none focus:border-primary">
                <button type="submit" class="absolute right-2 top-1.5 text-muted hover:text-primary">
                    <span class="material-icons text-sm">search</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Topics Table (Screenshot 3) -->
    <div class="kunena-box border rounded-xl overflow-hidden shadow-sm bg-card mb-6">
        <!-- Table Header -->
        <div class="kunena-subhead px-5 py-2.5 bg-gray-100/80 dark:bg-gray-800/80 border-b text-[11px] font-bold text-muted uppercase tracking-wider grid grid-cols-12 gap-4 items-center">
            <div class="col-span-12 md:col-span-7 flex items-center gap-1">
                <span>Thema</span>
                <span class="material-icons text-xs opacity-60">arrow_downward</span>
            </div>
            <div class="hidden md:block md:col-span-2 text-center">
                Antworten / Aufrufe
            </div>
            <div class="hidden md:block md:col-span-3">
                Letzter Beitrag
            </div>
        </div>

        <!-- Topics Rows -->
        <div class="divide-y divide-border">
            <?php if (empty($topics)): ?>
                <div class="p-8 text-center text-muted italic text-xs">
                    In diesem Forum wurden noch keine Themen eröffnet. Sei der Erste!
                </div>
            <?php else: ?>
                <?php foreach ($topics as $t): ?>
                    <?php 
                    $replyCount = max(0, ForumManager::countPosts($t['id']) - 1);
                    $views = (int)($t['views'] ?? 0);
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
                        <!-- Left: Status Icon, Title, Badges & Subtitle -->
                        <div class="col-span-12 md:col-span-7 flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="material-icons text-amber-500 text-lg" title="Angepinntes Thema">push_pin</span>
                                <?php elseif (!empty($t['is_locked'])): ?>
                                    <span class="material-icons text-muted text-lg" title="Geschlossenes Thema">lock</span>
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
                                    <?php if (!empty($t['is_locked'])): ?>
                                        <span class="kunena-badge kunena-badge-locked">Geschlossen</span>
                                    <?php endif; ?>
                                    <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="font-bold text-sm text-foreground hover:text-primary transition hover:underline">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="text-[11px] text-muted mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>Erstellt <?= htmlspecialchars($relCreated) ?> von</span>
                                    <span class="font-bold" style="color: <?= $authorRoleColor ?>;"><?= htmlspecialchars($t['author_name']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Replies / Views (Screenshot 3) -->
                        <div class="hidden md:block md:col-span-2 text-center text-xs">
                            <div class="font-medium text-muted">
                                Antworten: <strong class="text-foreground"><?= $replyCount ?></strong>
                            </div>
                            <div class="text-[11px] text-muted opacity-80 mt-0.5">
                                Aufrufe: <strong class="text-foreground"><?= $views ?></strong>
                            </div>
                        </div>

                        <!-- Right: Last Post Block (Screenshot 3) -->
                        <div class="col-span-12 md:col-span-3 text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="kunena-avatar-mini shrink-0 w-7 h-7 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-xs shadow-inner">
                                    <?= strtoupper(mb_substr($t['last_post_user'], 0, 1)) ?>
                                </div>
                                <div class="min-w-0 flex-1 leading-tight">
                                    <div class="text-[11px] text-muted">
                                        Letzter Beitrag von <span class="font-bold" style="color: <?= $lastPostRoleColor ?>;"><?= htmlspecialchars($t['last_post_user']) ?></span>
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
