<?php
/**
 * Frontend: Forum Index View (Categories & Boards)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$categories = ForumManager::getCategories();
$allBoards = ForumManager::getBoards();
?>

<div class="space-y-8">
    <?php if (empty($categories)): ?>
        <div class="p-8 text-center text-muted border rounded">
            Noch keine Foren-Bereiche eingerichtet.
        </div>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <?php 
            $boards = array_filter($allBoards, fn($b) => ($b['category_id'] ?? '') === $cat['id']);
            usort($boards, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
            ?>
            <div class="forum-category-card card p-0 border shadow-sm overflow-hidden mb-6">
                <div class="forum-category-header bg-gray-100 dark:bg-gray-800 px-6 py-4 border-b flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-icons text-primary text-2xl"><?= htmlspecialchars($cat['icon'] ?? 'folder') ?></span>
                        <div>
                            <h3 class="font-bold text-lg leading-tight"><?= htmlspecialchars($cat['title']) ?></h3>
                            <?php if (!empty($cat['description'])): ?>
                                <p class="text-xs text-muted"><?= htmlspecialchars($cat['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="forum-boards-list divide-y">
                    <?php if (empty($boards)): ?>
                        <div class="p-4 text-xs text-muted italic">In dieser Kategorie wurden noch keine Foren angelegt.</div>
                    <?php else: ?>
                        <?php foreach ($boards as $board): ?>
                            <?php 
                            $topicCount = ForumManager::countTopics($board['id']);
                            $latestTopics = ForumManager::getTopics($board['id'], 1, 1);
                            $latestTopic = !empty($latestTopics) ? $latestTopics[0] : null;
                            ?>
                            <div class="forum-board-row p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="forum-board-icon mt-1">
                                        <span class="material-icons text-primary text-2xl"><?= htmlspecialchars($board['icon'] ?? 'forum') ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base">
                                            <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="hover:text-primary hover:underline">
                                                <?= htmlspecialchars($board['title']) ?>
                                            </a>
                                            <?php if (!empty($board['is_locked'])): ?>
                                                <span class="material-icons text-xs text-warning" title="Forum geschlossen">lock</span>
                                            <?php endif; ?>
                                        </h4>
                                        <?php if (!empty($board['description'])): ?>
                                            <p class="text-xs text-muted mt-1"><?= htmlspecialchars($board['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex items-center gap-6 text-xs text-muted">
                                    <div class="text-center min-w-[70px]">
                                        <div class="font-bold text-sm text-foreground"><?= $topicCount ?></div>
                                        <div>Themen</div>
                                    </div>
                                    <div class="min-w-[180px] max-w-[240px]">
                                        <?php if ($latestTopic): ?>
                                            <div class="truncate font-semibold">
                                                <a href="?page=forum&view=topic&id=<?= urlencode($latestTopic['id']) ?>" class="hover:underline">
                                                    <?= htmlspecialchars($latestTopic['title']) ?>
                                                </a>
                                            </div>
                                            <div class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                                <span>von <?= htmlspecialchars($latestTopic['last_post_user']) ?></span>
                                                <span>•</span>
                                                <span><?= htmlspecialchars(substr($latestTopic['last_post_at'], 0, 10)) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="italic text-muted">Noch keine Beiträge</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
