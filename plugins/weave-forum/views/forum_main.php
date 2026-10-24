<?php
/**
 * Frontend: Master Forum Container & Router
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

require_once dirname(__DIR__) . '/ForumManager.php';
if (file_exists(dirname(__DIR__, 2) . '/weave-forum-users/ForumUserManager.php')) {
    require_once dirname(__DIR__, 2) . '/weave-forum-users/ForumUserManager.php';
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$view = trim($_GET['view'] ?? 'index');
$cfg = PluginManager::getPluginConfig('weave-forum', $data ?? []);
$forumTitle = $cfg['forum_title'] ?? 'Community-Forum';
?>

<div class="weave-forum-container my-8 max-w-6xl mx-auto px-4">
    <!-- Global Announcement Banner -->
    <?php if (!empty($cfg['announcement_enabled']) && !empty($cfg['announcement_text'])): ?>
        <?php
        $annType = $cfg['announcement_type'] ?? 'info';
        $annBg = ($annType === 'warning') ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 text-amber-800 dark:text-amber-300' :
                 (($annType === 'success') ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 text-emerald-800 dark:text-emerald-300' :
                 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 text-blue-800 dark:text-blue-300');
        $annIcon = ($annType === 'warning') ? 'campaign' : (($annType === 'success') ? 'verified' : 'info');
        ?>
        <div id="forum_announcement_bar" class="p-4 mb-4 border rounded-xl flex items-center justify-between gap-4 <?= $annBg ?> shadow-sm text-sm">
            <div class="flex items-center gap-3">
                <span class="material-icons text-xl"><?= $annIcon ?></span>
                <div>
                    <strong>Foren-Hinweis:</strong> <?= nl2br(htmlspecialchars($cfg['announcement_text'])) ?>
                </div>
            </div>
            <button type="button" class="opacity-70 hover:opacity-100 transition" onclick="document.getElementById('forum_announcement_bar').style.display='none'; sessionStorage.setItem('forum_announcement_dismissed', '1')">
                <span class="material-icons text-base">close</span>
            </button>
        </div>
        <script>
            if (sessionStorage.getItem('forum_announcement_dismissed') === '1') {
                const ab = document.getElementById('forum_announcement_bar');
                if (ab) ab.style.display = 'none';
            }
        </script>
    <?php endif; ?>

    <!-- Forum Top Bar -->
    <div class="forum-topbar flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 mb-6 bg-white dark:bg-gray-900 border rounded-xl shadow-sm">
        <div class="flex items-center gap-3">
            <a href="?page=forum" class="flex items-center gap-2 text-xl font-extrabold text-foreground hover:text-primary transition">
                <span class="material-icons text-primary text-3xl">forum</span>
                <span><?= htmlspecialchars($forumTitle) ?></span>
            </a>
        </div>

        <!-- Breadcrumbs -->
        <div class="text-xs text-muted flex items-center gap-2 overflow-x-auto">
            <a href="?page=forum" class="hover:underline">Forenübersicht</a>
            <?php if ($view === 'board' && !empty($_GET['id'])): ?>
                <?php $b = ForumManager::getBoard($_GET['id']); ?>
                <span>›</span>
                <span class="font-semibold text-foreground"><?= htmlspecialchars($b['title'] ?? 'Forum') ?></span>
            <?php elseif ($view === 'topic' && !empty($_GET['id'])): ?>
                <?php 
                $t = ForumManager::getTopic($_GET['id']); 
                $b = $t ? ForumManager::getBoard($t['board_id']) : null;
                ?>
                <?php if ($b): ?>
                    <span>›</span>
                    <a href="?page=forum&view=board&id=<?= urlencode($b['id']) ?>" class="hover:underline"><?= htmlspecialchars($b['title']) ?></a>
                <?php endif; ?>
                <span>›</span>
                <span class="font-semibold text-foreground truncate max-w-[200px]"><?= htmlspecialchars($t['title'] ?? 'Thema') ?></span>
            <?php elseif ($view === 'new_topic'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Neues Thema</span>
            <?php elseif ($view === 'search'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Suche</span>
            <?php elseif ($view === 'login'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Anmeldung</span>
            <?php elseif ($view === 'register'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Registrierung</span>
            <?php elseif ($view === 'profile'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Mein Profil</span>
            <?php endif; ?>
        </div>

        <!-- Search Bar & User Widget -->
        <div class="flex items-center gap-3">
            <!-- Compact Quick Search -->
            <form method="get" action="" class="relative hidden sm:block">
                <input type="hidden" name="page" value="forum">
                <input type="hidden" name="view" value="search">
                <span class="material-icons absolute left-2.5 top-2 text-muted text-xs">search</span>
                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
                       placeholder="Forum durchsuchen..." 
                       class="input-field text-xs py-1.5 pl-7 pr-2 rounded-lg w-36 md:w-48 bg-gray-50 dark:bg-gray-800">
            </form>

            <?php if ($currentUser): ?>
                <a href="?page=forum&view=profile" class="flex items-center gap-2 hover:opacity-80 transition text-sm">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="font-bold text-foreground"><?= htmlspecialchars($currentUser['username']) ?></span>
                </a>
                <a href="?forum_auth=logout" class="btn btn-outline btn-xs">Abmelden</a>
            <?php else: ?>
                <a href="?page=forum&view=login" class="btn btn-outline btn-xs font-semibold">Anmelden</a>
                <a href="?page=forum&view=register" class="btn btn-primary btn-xs font-semibold">Registrieren</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active View Content -->
    <div class="forum-content">
        <?php
        if ($view === 'board') {
            require __DIR__ . '/forum_board.php';
        } elseif ($view === 'topic') {
            require __DIR__ . '/forum_topic.php';
        } elseif ($view === 'new_topic') {
            require __DIR__ . '/forum_new_topic.php';
        } elseif ($view === 'search') {
            require __DIR__ . '/forum_search.php';
        } elseif ($view === 'login') {
            $userView = dirname(__DIR__, 2) . '/weave-forum-users/views/login.php';
            if (file_exists($userView)) require $userView;
        } elseif ($view === 'register') {
            $userView = dirname(__DIR__, 2) . '/weave-forum-users/views/register.php';
            if (file_exists($userView)) require $userView;
        } elseif ($view === 'profile') {
            $userView = dirname(__DIR__, 2) . '/weave-forum-users/views/profile.php';
            if (file_exists($userView)) require $userView;
        } else {
            require __DIR__ . '/forum_index.php';
        }
        ?>
    </div>
</div>

<style>
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary, #2e6248);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 13px;
}
.avatar-circle-lg {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: var(--primary, #2e6248);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
}
.forum-quote {
    margin: 1rem 0;
    padding: 0.75rem 1rem;
    border-left: 4px solid var(--primary, #2e6248);
    background: rgba(0, 0, 0, 0.03);
    border-radius: 0 8px 8px 0;
}
.forum-quote .quote-author {
    font-size: 0.75rem;
    color: var(--muted, #64748b);
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.forum-quote .quote-body {
    font-size: 0.875rem;
    color: var(--foreground, #1e293b);
}
</style>
