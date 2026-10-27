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

<div class="weave-forum-container">
    <!-- Global Announcement Banner -->
    <?php if (!empty($cfg['announcement_enabled']) && !empty($cfg['announcement_text'])): ?>
        <?php
        $annType = $cfg['announcement_type'] ?? 'info';
        $annClass = ($annType === 'warning') ? 'warning' : (($annType === 'success') ? 'success' : 'info');
        $annIcon = ($annType === 'warning') ? 'campaign' : (($annType === 'success') ? 'verified' : 'info');
        ?>
        <div id="forum_announcement_bar" class="kunena-announcement <?= $annClass ?>">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="material-icons" style="font-size: 20px;"><?= $annIcon ?></span>
                <div>
                    <strong>Foren-Hinweis:</strong> <?= nl2br(htmlspecialchars($cfg['announcement_text'])) ?>
                </div>
            </div>
            <button type="button" style="background:none; border:none; cursor:pointer; opacity:0.7; padding:4px;" onclick="document.getElementById('forum_announcement_bar').style.display='none'; sessionStorage.setItem('forum_announcement_dismissed', '1')">
                <span class="material-icons" style="font-size: 18px;">close</span>
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
    <div class="kunena-navbar">
        <div class="kunena-navbar-top">
            <!-- Left: Brand / Title & Menu Links -->
            <div class="kunena-navbar-brand-group">
                <a href="?page=forum" class="kunena-navbar-brand">
                    <span class="material-icons">forum</span>
                    <span><?= htmlspecialchars($forumTitle) ?></span>
                </a>

                <!-- Kunena Nav Tabs -->
                <nav class="kunena-nav-tabs">
                    <a href="?page=forum&view=index" class="kunena-nav-link <?= in_array($view, ['index', 'board'], true) ? 'active' : '' ?>">
                        <span class="material-icons">home</span> Forenübersicht
                    </a>
                    <a href="?page=forum&view=recent" class="kunena-nav-link <?= $view === 'recent' ? 'active' : '' ?>">
                        <span class="material-icons">access_time</span> Aktuelle Themen
                    </a>
                    <a href="?page=forum&view=search" class="kunena-nav-link <?= $view === 'search' ? 'active' : '' ?>">
                        <span class="material-icons">search</span> Suche
                    </a>
                    <a href="?page=forum&view=help" class="kunena-nav-link <?= $view === 'help' ? 'active' : '' ?>">
                        <span class="material-icons">help_outline</span> Hilfe & FAQ
                    </a>
                    <?php if ($currentUser && class_exists('ForumUserManager') && ForumUserManager::userCan('moderate', $currentUser)): ?>
                        <?php $openReports = ForumManager::getReports('open'); ?>
                        <a href="?page=forum&view=moderation" class="kunena-nav-link <?= $view === 'moderation' ? 'active' : '' ?>" style="color: #d97706;">
                            <span class="material-icons" style="font-size: 16px;">shield</span> Moderation
                            <?php if (!empty($openReports)): ?>
                                <span class="kunena-cat-badge" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-size:10px; padding:1px 6px; margin-left:2px;">
                                    <?= count($openReports) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Right: User Login Widget / Avatar -->
            <div class="kunena-navbar-auth">
                <?php if ($currentUser): ?>
                    <?php 
                    $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($currentUser['role'] ?? 'forum_user') : '#2563eb';
                    $roleLabel = class_exists('ForumUserManager') ? ForumUserManager::getRoleLabel($currentUser['role'] ?? 'forum_user') : 'Mitglied';
                    ?>
                    <a href="?page=forum&view=profile" class="kunena-user-badge">
                        <div class="kunena-avatar-mini">
                            <?= strtoupper(mb_substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span style="color: <?= $roleColor ?>;"><?= htmlspecialchars($currentUser['username']) ?></span>
                        <span style="color: #64748b; font-size: 11px;">(<?= htmlspecialchars($roleLabel) ?>)</span>
                    </a>
                    <a href="?forum_auth=logout" class="kunena-collapse-btn" title="Abmelden" style="text-decoration:none;">
                        <span class="material-icons" style="font-size: 16px;">logout</span>
                    </a>
                <?php else: ?>
                    <a href="?page=forum&view=login" class="kunena-btn-secondary">Anmelden</a>
                    <a href="?page=forum&view=register" class="kunena-btn-primary">Registrieren</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Breadcrumb Row -->
        <div class="kunena-breadcrumb">
            <a href="?page=forum">
                <span class="material-icons" style="font-size: 15px;">home</span> Forum
            </a>
            <?php if ($view === 'board' && !empty($_GET['id'])): ?>
                <?php $b = ForumManager::getBoard($_GET['id']); ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current"><?= htmlspecialchars($b['title'] ?? 'Forum') ?></span>
            <?php elseif ($view === 'topic' && !empty($_GET['id'])): ?>
                <?php 
                $t = ForumManager::getTopic($_GET['id']); 
                $b = $t ? ForumManager::getBoard($t['board_id']) : null;
                ?>
                <?php if ($b): ?>
                    <span class="kunena-breadcrumb-sep">›</span>
                    <a href="?page=forum&view=board&id=<?= urlencode($b['id']) ?>"><?= htmlspecialchars($b['title']) ?></a>
                <?php endif; ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current"><?= htmlspecialchars($t['title'] ?? 'Thema') ?></span>
            <?php elseif ($view === 'recent'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Aktuelle Themen</span>
            <?php elseif ($view === 'help'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Hilfe & FAQ</span>
            <?php elseif ($view === 'new_topic'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Neues Thema</span>
            <?php elseif ($view === 'search'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Suche</span>
            <?php elseif ($view === 'login'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Anmeldung</span>
            <?php elseif ($view === 'register'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Registrierung</span>
            <?php elseif ($view === 'profile'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Mein Profil</span>
            <?php elseif ($view === 'moderation'): ?>
                <span class="kunena-breadcrumb-sep">›</span>
                <span class="kunena-breadcrumb-current">Moderationszentrale</span>
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
        } elseif ($view === 'moderation') {
            require __DIR__ . '/forum_moderation.php';
        } else {
            require __DIR__ . '/forum_index.php';
        }
        ?>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- Kunena Classic Stylesheet (Screenshots 1, 2, 3)                -->
<!-- Vollständig eigenständiges CSS – keine Tailwind-Abhängigkeit! -->
<!-- ───────────────────────────────────────────────────────────── -->
<style>
.weave-forum-container {
    width: 100%;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: inherit;
    color: var(--text-primary, #1e293b);
}

.weave-forum-container *,
.weave-forum-container *::before,
.weave-forum-container *::after {
    box-sizing: border-box;
}

/* Announcement */
.kunena-announcement {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 18px;
    margin-bottom: 20px;
    border-radius: 12px;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #1e40af;
    font-size: 13px;
}
.kunena-announcement.warning {
    border-color: #fde68a;
    background: #fffbeb;
    color: #92400e;
}
.kunena-announcement.success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

/* Navbar */
.kunena-navbar {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    margin-bottom: 24px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.kunena-navbar-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    padding: 12px 20px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}

.kunena-navbar-brand-group {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.kunena-navbar-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    text-decoration: none;
}
.kunena-navbar-brand:hover {
    color: var(--primary, #2e6248);
}
.kunena-navbar-brand .material-icons {
    color: var(--primary, #2e6248);
    font-size: 22px;
}

.kunena-nav-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
}

.kunena-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    transition: all 0.15s ease;
}
.kunena-nav-link .material-icons {
    font-size: 16px;
}
.kunena-nav-link:hover {
    color: var(--primary, #2e6248);
    background: rgba(46, 98, 72, 0.08);
}
.kunena-nav-link.active {
    background: var(--primary, #2e6248);
    color: #ffffff;
    font-weight: 700;
}
.kunena-nav-link.active .material-icons {
    color: #ffffff;
}

.kunena-navbar-auth {
    display: flex;
    align-items: center;
    gap: 8px;
}

.kunena-user-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    color: #1e293b;
    font-size: 12px;
    font-weight: 600;
}

.kunena-avatar-mini {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--primary, #2e6248);
    color: #ffffff;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.kunena-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--primary, #2e6248);
    color: #ffffff;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    border: none;
    transition: all 0.15s ease;
}
.kunena-btn-primary:hover {
    opacity: 0.92;
    transform: translateY(-1px);
}

.kunena-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}
.kunena-btn-secondary:hover {
    border-color: var(--primary, #2e6248);
    color: var(--primary, #2e6248);
}

.kunena-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    background: #ffffff;
    font-size: 12px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    overflow-x: auto;
}
.kunena-breadcrumb a {
    color: #64748b;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}
.kunena-breadcrumb a:hover {
    color: var(--primary, #2e6248);
    text-decoration: underline;
}
.kunena-breadcrumb-sep {
    color: #cbd5e1;
    font-size: 12px;
}
.kunena-breadcrumb-current {
    font-weight: 700;
    color: #0f172a;
}

/* Category Cards */
.kunena-cat-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid var(--primary, #2e6248);
    border-radius: 14px;
    margin-bottom: 28px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.kunena-cat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 22px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}

.kunena-cat-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.kunena-cat-icon {
    font-size: 22px;
    color: var(--primary, #2e6248);
}

.kunena-cat-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}

.kunena-cat-badge {
    background: rgba(46, 98, 72, 0.12);
    color: var(--primary, #2e6248);
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
}

.kunena-collapse-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.kunena-collapse-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}

/* Subhead Bar */
.kunena-subhead {
    display: grid;
    grid-template-columns: minmax(340px, 1fr) 150px 320px;
    padding: 10px 22px;
    background: #eef2f6;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #475569;
}
.kunena-subhead-stats {
    text-align: center;
}
.kunena-subhead-last {
    padding-left: 10px;
}

/* Board Rows */
.kunena-board-row {
    display: grid;
    grid-template-columns: minmax(340px, 1fr) 150px 320px;
    padding: 16px 22px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    background: #ffffff;
    transition: background 0.15s ease;
}
.kunena-board-row:last-child {
    border-bottom: none;
}
.kunena-board-row:hover {
    background: #f8fafc;
}

.kunena-board-main {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.kunena-board-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(46, 98, 72, 0.1);
    color: var(--primary, #2e6248);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 22px;
}

.kunena-board-info {
    min-width: 0;
    flex: 1;
}

.kunena-board-title-line {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.kunena-board-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0f172a;
    text-decoration: none;
    transition: color 0.15s ease;
}
.kunena-board-title:hover {
    color: var(--primary, #2e6248);
    text-decoration: underline;
}

.kunena-lock-icon {
    font-size: 16px;
    color: #eab308;
}

.kunena-board-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 4px 0 0 0;
    line-height: 1.45;
}

.kunena-board-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    text-align: center;
    border-left: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    padding: 0 10px;
}

.kunena-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.kunena-stat-num {
    font-size: 14px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
}
.kunena-stat-lbl {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
}

.kunena-board-lastpost {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-left: 12px;
    font-size: 12px;
}

.kunena-lastpost-info {
    min-width: 0;
    flex: 1;
    line-height: 1.35;
}
.kunena-lastpost-title {
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 250px;
}
.kunena-lastpost-title a {
    color: #1e293b;
    text-decoration: none;
}
.kunena-lastpost-title a:hover {
    color: var(--primary, #2e6248);
    text-decoration: underline;
}
.kunena-lastpost-meta {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}
.kunena-no-post {
    color: #94a3b8;
    font-size: 12px;
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Widgets */
.kunena-widget-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid #64748b;
    border-radius: 14px;
    margin-bottom: 24px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}
.kunena-widget-card.stats {
    border-left-color: #2563eb;
}

.kunena-widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 22px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
    font-weight: 700;
    font-size: 13.5px;
    color: #0f172a;
}
.kunena-widget-header-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.kunena-widget-body {
    padding: 18px 22px;
    font-size: 12.5px;
    color: #334155;
}

.kunena-legend-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    font-size: 11.5px;
}
.kunena-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 700;
}
.kunena-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.kunena-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}
.kunena-stats-col {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.kunena-stats-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 6px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12.5px;
}
.kunena-stats-row:last-child {
    border-bottom: none;
}
.kunena-stats-label {
    color: #64748b;
}
.kunena-stats-val {
    font-weight: 800;
    color: #0f172a;
}

/* Pagination & Filter */
.kunena-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #1e293b;
    text-decoration: none;
    font-size: 12px;
    min-width: 30px;
    height: 30px;
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
    font-weight: 700;
}
.kunena-page-btn.disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* --- Post Topic View (Kunena Style) --- */
.kunena-post-card {
    display: flex;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
}
.kunena-post-author {
    width: 220px;
    min-width: 200px;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
}
.kunena-post-body {
    flex: 1;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.kunena-post-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    margin-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
    color: #64748b;
}
.kunena-post-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 14px;
    margin-top: 16px;
    border-top: 1px solid #f1f5f9;
    font-size: 12px;
}

/* Responsive */
@media (max-width: 860px) {
    .kunena-post-card {
        flex-direction: column;
    }
    .kunena-post-author {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        flex-direction: row;
        text-align: left;
    }
    .kunena-subhead {
        display: none;
    }
    .kunena-board-row {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 14px 16px;
    }
    .kunena-board-stats {
        border-left: none;
        border-right: none;
        justify-content: flex-start;
        padding: 0;
        gap: 20px;
    }
    .kunena-board-lastpost {
        padding-left: 0;
        border-top: 1px dashed #f1f5f9;
        padding-top: 10px;
    }
}
</style>
