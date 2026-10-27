<?php
/**
 * Frontend: Forum Quick-Search Results View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$query = trim($_GET['q'] ?? '');
$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$results = !empty($query) ? ForumManager::search($query) : ['topics' => [], 'query' => '', 'count' => 0];
if (!empty($results['topics'])) {
    $results['topics'] = array_values(array_filter($results['topics'], fn($t) => ForumManager::canUserReadBoard($t['board_id'], $currentUser)));
    $results['count'] = count($results['topics']);
}
?>

<div class="mb-6">
    <div class="flex items-center gap-2 mb-2">
        <span class="material-icons text-primary text-2xl">search</span>
        <h2 class="text-2xl font-bold">Forensuche</h2>
    </div>
    <p class="text-sm text-muted mb-4">
        Durchsuche alle Themen und Beiträge der Community nach Schlagwörtern.
    </p>

    <!-- Search Form -->
    <form method="get" action="" class="flex gap-2 max-w-xl mb-6">
        <input type="hidden" name="page" value="forum">
        <input type="hidden" name="view" value="search">
        <div class="relative flex-1">
            <span class="material-icons absolute left-3 top-2.5 text-muted text-sm">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" required minlength="2"
                   class="input-field w-full pl-9" placeholder="Suchbegriff eingeben (z. B. Weide, Flechten, Werkzeug)..." autofocus>
        </div>
        <button type="submit" class="btn btn-primary font-bold">
            Suchen
        </button>
    </form>
</div>

<?php if ($query !== ''): ?>
    <div class="card p-0 border shadow-sm overflow-hidden mb-6">
        <div class="bg-gray-100 dark:bg-gray-800 px-6 py-3 border-b flex justify-between items-center text-xs font-bold text-muted uppercase">
            <div>
                Suchergebnisse für &bdquo;<?= htmlspecialchars($query) ?>&ldquo; (<?= $results['count'] ?> gefunden)
            </div>
            <a href="?page=forum" class="text-primary hover:underline font-semibold normal-case">
                Zurück zur Forenübersicht
            </a>
        </div>

        <div class="divide-y">
            <?php if (empty($results['topics'])): ?>
                <div class="p-8 text-center text-muted">
                    <span class="material-icons text-4xl mb-2 text-muted/60">search_off</span>
                    <p class="font-semibold">Keine passenden Themen oder Beiträge gefunden.</p>
                    <p class="text-xs mt-1">Versuche es mit einem kürzeren oder allgemeineren Suchbegriff.</p>
                </div>
            <?php else: ?>
                <?php foreach ($results['topics'] as $t): ?>
                    <div class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="badge badge-secondary text-[11px]">
                                    <?= htmlspecialchars($t['board_title'] ?? 'Forum') ?>
                                </span>
                                <?php if (!empty($t['is_solved'])): ?>
                                    <span class="badge badge-success text-[11px] flex items-center gap-1 font-bold">
                                        <span class="material-icons text-xs">check_circle</span> Gelöst
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="badge badge-warning text-[10px]">Angepinnt</span>
                                <?php endif; ?>
                                <?php if (!empty($t['is_locked'])): ?>
                                    <span class="badge badge-secondary text-[10px]">Geschlossen</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-muted whitespace-nowrap">
                                <?= $t['reply_count'] ?> <?= $t['reply_count'] === 1 ? 'Antwort' : 'Antworten' ?>
                            </span>
                        </div>

                        <h3 class="text-lg font-bold mb-1">
                            <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="hover:text-primary hover:underline">
                                <?= htmlspecialchars($t['title']) ?>
                            </a>
                        </h3>

                        <?php if (!empty($t['snippet'])): ?>
                            <div class="text-xs text-muted mb-2 bg-gray-50 dark:bg-gray-900/40 p-2.5 rounded border">
                                <?= $t['snippet'] ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-[11px] text-muted flex items-center gap-2">
                            <span>Erstellt von <strong><?= htmlspecialchars($t['author_name']) ?></strong></span>
                            <span>•</span>
                            <span>Letzter Beitrag: <strong><?= htmlspecialchars($t['last_post_user']) ?></strong> (<?= htmlspecialchars(substr($t['last_post_at'], 0, 16)) ?>)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
