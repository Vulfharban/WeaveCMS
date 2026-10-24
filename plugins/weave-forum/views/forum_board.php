<?php
/**
 * Frontend: Forum Board View (Topics List)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$boardId = trim($_GET['id'] ?? '');
$board = ForumManager::getBoard($boardId);

if (!$board) {
    echo '<div class="alert alert-danger">Das gewünschte Forum wurde nicht gefunden.</div>';
    return;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$totalTopics = ForumManager::countTopics($boardId);
$topics = ForumManager::getTopics($boardId, $page, $perPage);
$totalPages = ceil($totalTopics / $perPage);

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canCreateTopic = class_exists('ForumUserManager') ? ForumUserManager::userCan('create_topic', $currentUser, $board) : false;
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold flex items-center gap-2">
            <span class="material-icons text-primary"><?= htmlspecialchars($board['icon'] ?? 'forum') ?></span>
            <?= htmlspecialchars($board['title']) ?>
        </h2>
        <?php if (!empty($board['description'])): ?>
            <p class="text-sm text-muted mt-1"><?= htmlspecialchars($board['description']) ?></p>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($canCreateTopic && empty($board['is_locked'])): ?>
            <a href="?page=forum&view=new_topic&board=<?= urlencode($board['id']) ?>" class="btn btn-primary flex items-center gap-1 font-bold">
                <span class="material-icons text-sm">edit</span> Neues Thema erstellen
            </a>
        <?php elseif (!empty($board['is_locked'])): ?>
            <span class="badge badge-warning flex items-center gap-1">
                <span class="material-icons text-xs">lock</span> Forum geschlossen
            </span>
        <?php else: ?>
            <a href="?page=forum&view=login" class="btn btn-outline btn-sm">
                Anmelden zum Schreiben
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card p-0 border shadow-sm overflow-hidden mb-6">
    <div class="bg-gray-100 dark:bg-gray-800 px-6 py-3 border-b flex justify-between text-xs font-bold text-muted uppercase tracking-wider">
        <div>Thema / Verfasser</div>
        <div class="flex gap-12">
            <span class="hidden md:inline">Antworten</span>
            <span class="hidden md:inline">Aufrufe</span>
            <span>Letzter Beitrag</span>
        </div>
    </div>

    <div class="divide-y">
        <?php if (empty($topics)): ?>
            <div class="p-8 text-center text-muted">
                In diesem Forum wurden noch keine Themen eröffnet. Sei der Erste!
            </div>
        <?php else: ?>
            <?php foreach ($topics as $t): ?>
                <?php 
                $replyCount = max(0, ForumManager::countPosts($t['id']) - 1);
                ?>
                <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition <?= !empty($t['is_pinned']) ? 'bg-amber-50/20 dark:bg-amber-900/10' : '' ?>">
                    <div class="flex items-start gap-3 flex-1">
                        <span class="material-icons text-xl <?= !empty($t['is_pinned']) ? 'text-amber-500' : 'text-muted' ?>">
                            <?= !empty($t['is_pinned']) ? 'push_pin' : (!empty($t['is_locked']) ? 'lock' : 'article') ?>
                        </span>
                        <div>
                            <h4 class="font-bold text-base">
                                <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="hover:text-primary hover:underline">
                                    <?= htmlspecialchars($t['title']) ?>
                                </a>
                                <?php if (!empty($t['is_solved'])): ?>
                                    <span class="badge badge-success text-[10px] ml-1 inline-flex items-center gap-0.5 font-bold">
                                        <span class="material-icons text-[11px]">check_circle</span> Gelöst
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="badge badge-warning text-[10px] ml-1">Angepinnt</span>
                                <?php endif; ?>
                                <?php if (!empty($t['is_locked'])): ?>
                                    <span class="badge badge-secondary text-[10px] ml-1">Geschlossen</span>
                                <?php endif; ?>
                            </h4>
                            <div class="text-xs text-muted mt-1">
                                Von <strong><?= htmlspecialchars($t['author_name']) ?></strong> am <?= htmlspecialchars(substr($t['created_at'], 0, 10)) ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-12 text-xs text-muted">
                        <div class="hidden md:block text-center min-w-[50px]">
                            <span class="font-bold text-sm text-foreground"><?= $replyCount ?></span>
                        </div>
                        <div class="hidden md:block text-center min-w-[50px]">
                            <span class="font-bold text-sm text-foreground"><?= (int)($t['views'] ?? 0) ?></span>
                        </div>
                        <div class="min-w-[140px] text-right md:text-left">
                            <div class="font-semibold text-foreground truncate"><?= htmlspecialchars($t['last_post_user']) ?></div>
                            <div class="text-[11px] text-muted"><?= htmlspecialchars(substr($t['last_post_at'], 0, 16)) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2 mt-6">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= $i ?>" 
               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
