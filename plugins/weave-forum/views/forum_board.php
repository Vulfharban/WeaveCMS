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
    echo '<div style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:1.5rem; border-radius:12px; text-align:center;">Das gewünschte Forum wurde nicht gefunden.</div>';
    return;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canReadBoard = ForumManager::canUserReadBoard($board, $currentUser);

if (!$canReadBoard) {
    ?>
    <div class="kunena-cat-card" style="text-align: center; padding: 50px 24px; margin: 30px auto; max-width: 680px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border-radius: 14px; border-left: 6px solid #f59e0b;">
        <div style="width: 64px; height: 64px; margin: 0 auto 16px auto; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
            <span class="material-icons" style="font-size: 34px;">lock</span>
        </div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
            Registrierung erforderlich
        </h2>
        <p style="font-size: 14px; color: #64748b; line-height: 1.6; max-width: 520px; margin: 0 auto 24px auto;">
            Der Bereich <strong>&bdquo;<?= htmlspecialchars($board['title']) ?>&ldquo;</strong> ist nur für registrierte und angemeldete Mitglieder unserer Community zugänglich. Bitte melde dich an oder erstelle ein kostenloses Konto, um alle Themen und Diskussionen lesen zu können.
        </p>
        <div style="display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap;">
            <a href="?page=forum&view=login" class="kunena-btn-primary" style="padding: 10px 22px; font-size: 14px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size: 18px;">login</span> Jetzt anmelden
            </a>
            <a href="?page=forum&view=register" class="kunena-btn-secondary" style="padding: 10px 22px; font-size: 14px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size: 18px;">person_add</span> Kostenlos registrieren
            </a>
        </div>
    </div>
    <?php
    return;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$totalTopics = ForumManager::countTopics($boardId);
$topics = ForumManager::getTopics($boardId, $page, $perPage);
$totalPages = max(1, ceil($totalTopics / $perPage));
$canCreateTopic = class_exists('ForumUserManager') ? ForumUserManager::userCan('create_topic', $currentUser, $board) : false;
?>

<div class="kunena-board-wrapper">
    <!-- Board Header Banner -->
    <div class="kunena-cat-card" style="margin-bottom: 20px;">
        <div class="kunena-cat-header" style="flex-wrap: wrap; gap: 14px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-icons kunena-cat-icon"><?= htmlspecialchars($board['icon'] ?? 'forum') ?></span>
                    <h2 class="kunena-cat-title" style="font-size: 1.35rem;">
                        <?= htmlspecialchars($board['title']) ?>
                    </h2>
                    <span class="kunena-cat-badge">
                        <?= $totalTopics ?> <?= $totalTopics === 1 ? 'Thema' : 'Themen' ?>
                    </span>
                </div>
                <?php if (!empty($board['description'])): ?>
                    <p style="font-size: 13px; color: #64748b; margin: 6px 0 0 0; line-height: 1.45;">
                        <?= htmlspecialchars($board['description']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($canCreateTopic && empty($board['is_locked'])): ?>
                    <a href="?page=forum&view=new_topic&board=<?= urlencode($board['id']) ?>" class="kunena-btn-primary" style="padding: 8px 16px; font-size: 13px;">
                        <span class="material-icons" style="font-size: 18px;">edit</span> Neues Thema erstellen
                    </a>
                <?php elseif (!empty($board['is_locked'])): ?>
                    <span class="kunena-badge kunena-badge-locked" style="padding: 6px 12px; font-size: 12px;">
                        <span class="material-icons" style="font-size: 16px;">lock</span> Forum geschlossen
                    </span>
                <?php else: ?>
                    <a href="?page=forum&view=login" class="kunena-btn-secondary">
                        Anmelden zum Schreiben
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action & Pagination Bar -->
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <!-- Pagination Buttons -->
        <div style="display:flex; align-items:center; gap:4px; flex-wrap:wrap;">
            <?php if ($totalPages > 1): ?>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=1" class="kunena-page-btn <?= $page === 1 ? 'disabled' : '' ?>">Start</a>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= max(1, $page - 1) ?>" class="kunena-page-btn <?= $page === 1 ? 'disabled' : '' ?>">Zurück</a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= $i ?>" class="kunena-page-btn <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= min($totalPages, $page + 1) ?>" class="kunena-page-btn <?= $page === $totalPages ? 'disabled' : '' ?>">Weiter</a>
                <a href="?page=forum&view=board&id=<?= urlencode($boardId) ?>&p=<?= $totalPages ?>" class="kunena-page-btn <?= $page === $totalPages ? 'disabled' : '' ?>">Ende</a>
            <?php else: ?>
                <span style="font-size:12px; color:#64748b; font-weight:600;">Seite 1 von 1 (<?= $totalTopics ?> Themen)</span>
            <?php endif; ?>
        </div>

        <!-- Quick Filter / Search in Board -->
        <div>
            <form method="get" action="" style="position:relative; display:inline-block;">
                <input type="hidden" name="page" value="forum">
                <input type="hidden" name="view" value="search">
                <input type="hidden" name="board" value="<?= htmlspecialchars($boardId) ?>">
                <input type="text" name="q" placeholder="In diesem Forum suchen..." 
                       style="font-size:12px; padding:6px 32px 6px 12px; border-radius:8px; border:1px solid #cbd5e1; width:220px; outline:none;">
                <button type="submit" style="position:absolute; right:8px; top:6px; background:none; border:none; color:#64748b; cursor:pointer;">
                    <span class="material-icons" style="font-size:16px;">search</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Topics Table -->
    <div class="kunena-cat-card" style="border-left-color: #3b82f6;">
        <!-- Table Header -->
        <div class="kunena-subhead">
            <div style="display:flex; align-items:center; gap:6px;">
                <span>Thema</span>
                <span class="material-icons" style="font-size: 14px; opacity:0.6;">arrow_downward</span>
            </div>
            <div class="kunena-subhead-stats">
                Antworten / Aufrufe
            </div>
            <div class="kunena-subhead-last">
                Letzter Beitrag
            </div>
        </div>

        <!-- Topics Rows -->
        <div class="kunena-board-list">
            <?php if (empty($topics)): ?>
                <div style="padding:2.5rem; text-align:center; color:#64748b; font-style:italic; font-size:13px;">
                    In diesem Forum wurden noch keine Themen eröffnet. Sei der Erste!
                </div>
            <?php else: ?>
                <?php foreach ($topics as $t): ?>
                    <?php 
                    $replyCount = max(0, ForumManager::countPosts($t['id']) - 1);
                    $views = (int)($t['views'] ?? 0);
                    $relCreated = ForumManager::formatRelativeTime($t['created_at']);
                    $relLastPost = ForumManager::formatRelativeTime($t['last_post_at']);

                    $authorUser = ($currentUser && class_exists('ForumUserManager')) ? (ForumUserManager::getUserById($t['author_id'] ?? '') ?? ForumUserManager::findUser($t['author_name'])) : null;
                    $authorPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($authorUser) : ['show_role' => true];
                    $authorRole = !empty($authorPriv['show_role']) ? ($authorUser['role'] ?? 'forum_user') : 'forum_user';
                    $authorRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                    $authorDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($authorUser, $t['author_name']) : $t['author_name'];

                    $lastPostUser = ($currentUser && class_exists('ForumUserManager')) ? ForumUserManager::findUser($t['last_post_user']) : null;
                    $lastPostPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($lastPostUser) : ['show_role' => true];
                    $lastPostRole = !empty($lastPostPriv['show_role']) ? ($lastPostUser['role'] ?? 'forum_user') : 'forum_user';
                    $lastPostRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($lastPostRole) : '#2563eb';
                    $lastPostDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($lastPostUser, $t['last_post_user']) : $t['last_post_user'];
                    ?>
                    <div class="kunena-board-row" style="<?= !empty($t['is_pinned']) ? 'background: #fffdf5;' : '' ?>">
                        <!-- Left: Status Icon, Title, Badges & Subtitle -->
                        <div class="kunena-board-main">
                            <div style="margin-top: 2px;">
                                <?php if (!empty($t['is_pinned'])): ?>
                                    <span class="material-icons" style="color: #f59e0b; font-size: 20px;" title="Angepinntes Thema">push_pin</span>
                                <?php elseif (!empty($t['is_locked'])): ?>
                                    <span class="material-icons" style="color: #64748b; font-size: 20px;" title="Geschlossenes Thema">lock</span>
                                <?php else: ?>
                                    <span class="material-icons" style="color: #94a3b8; font-size: 20px;">article</span>
                                <?php endif; ?>
                            </div>
                            <div class="kunena-board-info">
                                <div class="kunena-board-title-line">
                                    <?php if (!empty($t['is_pinned'])): ?>
                                        <span class="kunena-badge kunena-badge-pinned">Angepinnt</span>
                                    <?php endif; ?>
                                    <?php if (!empty($t['is_solved'])): ?>
                                        <span class="kunena-badge kunena-badge-solved">
                                            <span class="material-icons" style="font-size:11px;">check</span> Gelöst
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($t['is_locked'])): ?>
                                        <span class="kunena-badge kunena-badge-locked">Geschlossen</span>
                                    <?php endif; ?>
                                    <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="kunena-board-title">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="kunena-board-desc" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <?php if ($currentUser): ?>
                                        <span>Erstellt <?= htmlspecialchars($relCreated) ?> von</span>
                                        <span style="color: <?= $authorRoleColor ?>; font-weight:700;"><?= htmlspecialchars($authorDisplayName) ?></span>
                                    <?php else: ?>
                                        <span>Erstellt <?= htmlspecialchars($relCreated) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Replies / Views -->
                        <div class="kunena-board-stats">
                            <div class="kunena-stat-item">
                                <span class="kunena-stat-num"><?= $replyCount ?></span>
                                <span class="kunena-stat-lbl">Antworten</span>
                            </div>
                            <div class="kunena-stat-item">
                                <span class="kunena-stat-num"><?= $views ?></span>
                                <span class="kunena-stat-lbl">Aufrufe</span>
                            </div>
                        </div>

                        <!-- Right: Last Post Block -->
                        <div class="kunena-board-lastpost">
                            <?php if ($currentUser): ?>
                                <div class="kunena-avatar-mini" style="background-color: <?= $lastPostRoleColor ?>18; color: <?= $lastPostRoleColor ?>; border: 1px solid <?= $lastPostRoleColor ?>40;">
                                    <?= strtoupper(mb_substr($lastPostDisplayName, 0, 1)) ?>
                                </div>
                                <div class="kunena-lastpost-info">
                                    <div style="font-size: 11.5px; color: #475569;">
                                        Letzter Beitrag von <span style="color: <?= $lastPostRoleColor ?>; font-weight: 700;"><?= htmlspecialchars($lastPostDisplayName) ?></span>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($relLastPost) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="kunena-avatar-mini" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                    <span class="material-icons" style="font-size: 15px;">schedule</span>
                                </div>
                                <div class="kunena-lastpost-info">
                                    <div style="font-size: 11.5px; color: #475569; font-weight: 600;">
                                        Letzte Aktivität
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($relLastPost) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
