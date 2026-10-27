<?php
/**
 * Frontend: Kunena-inspirierter Master Forum Container & Router
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
$cfg = class_exists('PluginManager') ? PluginManager::getPluginConfig('weave-forum', $data ?? []) : [];
$forumTitle = $cfg['forum_title'] ?? 'Community-Diskussionsforum';
?>

<div class="weave-forum-container my-6 max-w-6xl mx-auto px-4">
    <!-- Global Announcement Banner -->
    <?php if (!empty($cfg['announcement_enabled']) && !empty($cfg['announcement_text'])): ?>
        <?php
        $annType = $cfg['announcement_type'] ?? 'info';
        $annBg = ($annType === 'warning') ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 text-amber-800 dark:text-amber-300' :
                 (($annType === 'success') ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 text-emerald-800 dark:text-emerald-300' :
                 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 text-blue-800 dark:text-blue-300');
        $annIcon = ($annType === 'warning') ? 'campaign' : (($annType === 'success') ? 'verified' : 'info');
        ?>
        <div id="forum_announcement_bar" class="p-3.5 mb-4 border rounded-xl flex items-center justify-between gap-4 <?= $annBg ?> shadow-sm text-xs">
            <div class="flex items-center gap-2.5">
                <span class="material-icons text-lg"><?= $annIcon ?></span>
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

    <!-- ───────────────────────────────────────────────────────────── -->
    <!-- Kunena Navigation Bar (Screenshot 3 top)                      -->
    <!-- ───────────────────────────────────────────────────────────── -->
    <div class="kunena-navbar border rounded-xl overflow-hidden shadow-sm bg-card mb-5">
        <div class="px-5 py-3 bg-gray-50/90 dark:bg-gray-800/90 border-b flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Left: Brand / Title & Menu Links -->
            <div class="flex items-center gap-4 flex-wrap">
                <a href="?page=forum" class="flex items-center gap-2 font-black text-base text-foreground hover:text-primary transition mr-2">
                    <span class="material-icons text-primary text-xl">forum</span>
                    <span><?= htmlspecialchars($forumTitle) ?></span>
                </a>

                <!-- Kunena Nav Tabs (Screenshot 3) -->
                <nav class="flex items-center gap-1 text-xs">
                    <a href="?page=forum&view=index" class="kunena-nav-link <?= in_array($view, ['index', 'board'], true) ? 'active' : '' ?>">
                        <span class="material-icons text-sm">home</span> Forenübersicht
                    </a>
                    <a href="?page=forum&view=recent" class="kunena-nav-link <?= $view === 'recent' ? 'active' : '' ?>">
                        <span class="material-icons text-sm">access_time</span> Aktuelle Themen
                    </a>
                    <a href="?page=forum&view=search" class="kunena-nav-link <?= $view === 'search' ? 'active' : '' ?>">
                        <span class="material-icons text-sm">search</span> Suche
                    </a>
                    <a href="?page=forum&view=help" class="kunena-nav-link <?= $view === 'help' ? 'active' : '' ?>">
                        <span class="material-icons text-sm">help_outline</span> Hilfe & FAQ
                    </a>
                </nav>
            </div>

            <!-- Right: User Login Widget / Avatar -->
            <div class="flex items-center gap-2.5 text-xs">
                <?php if ($currentUser): ?>
                    <?php 
                    $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($currentUser['role'] ?? 'forum_user') : '#2563eb';
                    $roleLabel = class_exists('ForumUserManager') ? ForumUserManager::getRoleLabel($currentUser['role'] ?? 'forum_user') : 'Mitglied';
                    ?>
                    <a href="?page=forum&view=profile" class="flex items-center gap-2 hover:opacity-80 transition py-1 px-2 rounded-lg hover:bg-black/5 dark:hover:bg-white/5">
                        <div class="kunena-avatar-mini w-6 h-6 rounded-full bg-primary/20 text-primary font-bold flex items-center justify-center text-[11px]">
                            <?= strtoupper(mb_substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="font-bold" style="color: <?= $roleColor ?>;"><?= htmlspecialchars($currentUser['username']) ?></span>
                        <span class="text-[10px] text-muted opacity-80">(<?= htmlspecialchars($roleLabel) ?>)</span>
                    </a>
                    <a href="?forum_auth=logout" class="btn btn-outline btn-xs" title="Abmelden">
                        <span class="material-icons text-xs">logout</span>
                    </a>
                <?php else: ?>
                    <a href="?page=forum&view=login" class="btn btn-outline btn-xs font-semibold">Anmelden</a>
                    <a href="?page=forum&view=register" class="btn btn-primary btn-xs font-semibold">Registrieren</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Breadcrumb Row -->
        <div class="px-5 py-2 bg-gray-100/40 dark:bg-gray-900/30 text-[11px] text-muted flex items-center gap-2 overflow-x-auto">
            <a href="?page=forum" class="hover:underline flex items-center gap-1">
                <span class="material-icons text-[12px]">home</span> Forum
            </a>
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
                <span class="font-semibold text-foreground truncate max-w-[240px]"><?= htmlspecialchars($t['title'] ?? 'Thema') ?></span>
            <?php elseif ($view === 'recent'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Aktuelle Themen</span>
            <?php elseif ($view === 'help'): ?>
                <span>›</span>
                <span class="font-semibold text-foreground">Hilfe & FAQ</span>
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
    </div>

    <!-- Active View Content -->
    <div class="forum-content">
        <?php
        if ($view === 'board') {
            require __DIR__ . '/forum_board.php';
        } elseif ($view === 'topic') {
            require __DIR__ . '/forum_topic.php';
        } elseif ($view === 'recent') {
            require __DIR__ . '/forum_recent.php';
        } elseif ($view === 'help') {
            require __DIR__ . '/forum_help.php';
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

<!-- ───────────────────────────────────────────────────────────── -->
<!-- Kunena Classic Stylesheet (Screenshots 1, 2, 3)                -->
<!-- ───────────────────────────────────────────────────────────── -->
<style>
.weave-forum-container {
    font-family: inherit;
    color: var(--text-primary, #1e293b);
}
.kunena-box {
    background: var(--bg-card, #ffffff);
    border-color: var(--border-color, #e2e8f0);
}
.kunena-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    border-radius: 8px;
    color: var(--text-secondary, #64748b);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.15s ease;
}
.kunena-nav-link:hover {
    color: var(--primary, #2e6248);
    background: rgba(0, 0, 0, 0.04);
}
.kunena-nav-link.active {
    color: var(--primary, #2e6248);
    background: rgba(46, 98, 72, 0.1);
    font-weight: 700;
}
.kunena-header {
    background: rgba(0, 0, 0, 0.02);
}
.kunena-subhead {
    background: rgba(0, 0, 0, 0.03);
}
.kunena-row {
    border-color: var(--border-color, #e2e8f0);
}
.kunena-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    line-height: 1.2;
}
.kunena-badge-pinned {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.kunena-badge-solved {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.kunena-badge-locked {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.kunena-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid var(--border-color, #e2e8f0);
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #1e293b);
    text-decoration: none;
    font-size: 11px;
    min-width: 28px;
    height: 28px;
    transition: all 0.15s ease;
}
.kunena-page-btn:hover:not(.disabled) {
    border-color: var(--primary, #2e6248);
    color: var(--primary, #2e6248);
    background: rgba(46, 98, 72, 0.05);
}
.kunena-page-btn.active {
    background: var(--primary, #2e6248);
    color: #ffffff;
    border-color: var(--primary, #2e6248);
}
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
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--primary, #2e6248);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
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
