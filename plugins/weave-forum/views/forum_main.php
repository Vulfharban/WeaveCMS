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

        <!-- User Widget -->
        <div class="flex items-center gap-3">
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
