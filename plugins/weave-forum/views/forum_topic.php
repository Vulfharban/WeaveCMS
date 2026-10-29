<?php
/**
 * Frontend: Forum Topic View (Posts & Replies)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$topicId = trim($_GET['id'] ?? '');
$topic = ForumManager::getTopic($topicId);

if (!$topic) {
    echo '<div style="padding:20px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; text-align:center;">Dieses Thema existiert nicht mehr oder wurde entfernt.</div>';
    return;
}

$board = ForumManager::getBoard($topic['board_id']);
$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canReadBoard = $board ? ForumManager::canUserReadBoard($board, $currentUser) : true;

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
            Dieses Thema befindet sich im geschützten Bereich <strong>&bdquo;<?= htmlspecialchars($board['title'] ?? 'Forum') ?>&ldquo;</strong> und ist nur für registrierte und angemeldete Mitglieder unserer Community zugänglich. Bitte melde dich an oder registriere dich kostenlos, um den Beitrag zu lesen.
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

// Aufrufe nur bei leseberechtigtem Zugriff erhöhen
ForumManager::incrementTopicViews($topicId);

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$totalPosts = ForumManager::countPosts($topicId);
$posts = ForumManager::getPosts($topicId, $page, $perPage);
$totalPages = ceil($totalPosts / $perPage);

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$isMod = class_exists('ForumUserManager') ? ForumUserManager::userCan('moderate', $currentUser) : false;
$canReply = class_exists('ForumUserManager') ? ForumUserManager::userCan('reply', $currentUser, $board) : false;
$isSubscribed = $currentUser ? ForumManager::isSubscribed($currentUser['id'], $topicId) : false;
$allBoards = ForumManager::getBoards();
?>

<style>
.kunena-topic-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-bottom: 16px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}
.kunena-topic-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}
.kunena-mod-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 18px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    font-size: 12.5px;
}
dialog.kunena-dialog {
    border: none;
    border-radius: 14px;
    padding: 0;
    width: 92%;
    max-width: 520px;
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    margin: auto;
}
dialog.kunena-dialog::backdrop {
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(3px);
}
</style>

<!-- Topic Header -->
<div class="kunena-topic-header">
    <div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
            <?php if (!empty($topic['is_pinned'])): ?>
                <span class="kunena-topic-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                    <span class="material-icons" style="font-size:13px;">push_pin</span> Angepinnt
                </span>
            <?php endif; ?>
            <?php if (!empty($topic['is_solved'])): ?>
                <span class="kunena-topic-badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                    <span class="material-icons" style="font-size:13px;">check_circle</span> Gelöst
                </span>
            <?php endif; ?>
            <?php if (!empty($topic['is_locked'])): ?>
                <span class="kunena-topic-badge" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;">
                    <span class="material-icons" style="font-size:13px;">lock</span> Geschlossen
                </span>
            <?php endif; ?>
            <span style="font-size:12px; color:#64748b;">
                In Forum: <a href="?page=forum&view=board&id=<?= urlencode($board['id'] ?? '') ?>" style="color:var(--primary, #2e6248); font-weight:700; text-decoration:none;"><?= htmlspecialchars($board['title'] ?? 'Forum') ?></a>
            </span>
        </div>
        <h2 style="margin:0; font-size:1.5rem; font-weight:800; color:#0f172a;"><?= htmlspecialchars($topic['title']) ?></h2>
    </div>

    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <?php 
        $canToggleSolved = $currentUser && (($currentUser['id'] === $topic['author_id']) || $isMod);
        ?>
        <?php if ($canToggleSolved): ?>
            <!-- Gelöst Toggle Button -->
            <form method="post" action="?forum_action=toggle_solved" style="margin:0;">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="<?= !empty($topic['is_solved']) ? 'kunena-btn-primary' : 'kunena-btn-secondary' ?>" style="cursor:pointer;"
                        title="Themenstatus umschalten">
                    <span class="material-icons" style="font-size:15px;"><?= !empty($topic['is_solved']) ? 'check_circle' : 'task_alt' ?></span>
                    <span><?= !empty($topic['is_solved']) ? 'Gelöst ✓' : 'Als gelöst markieren' ?></span>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($currentUser): ?>
            <!-- Abonnement Toggle -->
            <form method="post" action="?forum_action=toggle_subscription" style="margin:0;">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="<?= $isSubscribed ? 'kunena-btn-primary' : 'kunena-btn-secondary' ?>" style="cursor:pointer;">
                    <span class="material-icons" style="font-size:15px;"><?= $isSubscribed ? 'notifications_active' : 'notifications_none' ?></span>
                    <span><?= $isSubscribed ? 'Abonniert' : 'Abonnieren' ?></span>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canReply && empty($topic['is_locked'])): ?>
            <a href="#reply-box" class="kunena-btn-primary">
                <span class="material-icons" style="font-size:15px;">reply</span> Antworten
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Moderation Toolbar (if Moderator / Admin) -->
<?php if ($isMod): ?>
    <div class="kunena-mod-bar">
        <div style="font-weight:700; display:flex; align-items:center; gap:6px; color:#b45309;">
            <span class="material-icons" style="font-size:18px;">shield</span> Moderations-Werkzeuge:
        </div>
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
            <!-- Pin / Unpin -->
            <form method="post" action="?forum_action=mod_pin_topic" style="margin:0;">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="pinned" value="<?= !empty($topic['is_pinned']) ? '0' : '1' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; cursor:pointer;">
                    <?= !empty($topic['is_pinned']) ? 'Lösen (Unpin)' : 'Anpinnen' ?>
                </button>
            </form>

            <!-- Lock / Unlock -->
            <form method="post" action="?forum_action=mod_lock_topic" style="margin:0;">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="locked" value="<?= !empty($topic['is_locked']) ? '0' : '1' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; cursor:pointer;">
                    <?= !empty($topic['is_locked']) ? 'Öffnen' : 'Schließen (Sperren)' ?>
                </button>
            </form>

            <!-- Move Topic -->
            <button type="button" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; cursor:pointer;" onclick="document.getElementById('modal_move_topic').showModal()">
                Verschieben...
            </button>

            <!-- Delete Topic -->
            <form method="post" action="?forum_action=mod_delete_topic" style="margin:0;" onsubmit="return confirm('Möchtest du dieses gesamte Thema wirklich löschen?')">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="board_id" value="<?= htmlspecialchars($board['id'] ?? '') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; color:#dc2626; border-color:#fca5a5; cursor:pointer;">Thema löschen</button>
            </form>
        </div>
    </div>

    <!-- Modal: Move Topic -->
    <dialog id="modal_move_topic" class="kunena-dialog">
        <form method="post" action="?forum_action=mod_move_topic" style="padding:24px; margin:0;">
            <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <h3 style="margin:0 0 14px 0; font-size:1.1rem; font-weight:800;">Thema in ein anderes Forum verschieben</h3>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Zielforum auswählen:</label>
                <select name="new_board_id" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
                    <?php foreach ($allBoards as $b): ?>
                        <option value="<?= htmlspecialchars($b['id']) ?>" <?= $b['id'] === $topic['board_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="kunena-btn-secondary" style="cursor:pointer;" onclick="this.closest('dialog').close()">Abbrechen</button>
                <button type="submit" class="kunena-btn-primary" style="cursor:pointer;">Verschieben</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<!-- Posts List -->
<div style="margin-bottom: 28px;">
    <?php foreach ($posts as $idx => $post): ?>
        <?php 
        $author = class_exists('ForumUserManager') ? (ForumUserManager::getUserById($post['author_id'] ?? '') ?? ForumUserManager::findUser($post['author_name'] ?? '')) : null;
        $authorRole = $author['role'] ?? 'forum_user';
        $authorPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($author) : [
            'show_role' => true, 'show_post_count' => true, 'show_joined_date' => true, 'show_bio' => true, 'show_signature' => true
        ];
        $authorDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($author, $post['author_name'] ?? 'Mitglied') : ($post['author_name'] ?? 'Mitglied');
        $isAuthor = ($currentUser && $currentUser['id'] === ($post['author_id'] ?? ''));
        $canEdit = ($isAuthor || $isMod);
        ?>
        <div id="post-<?= htmlspecialchars($post['id']) ?>" class="kunena-post-card">
            <!-- Left: Author Card -->
            <div class="kunena-post-author">
                <?php if ($currentUser): ?>
                    <?php
                    $effectiveRole = !empty($authorPriv['show_role']) ? $authorRole : 'forum_user';
                    $authorRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($effectiveRole) : '#2563eb';
                    $authorRoleLabel = class_exists('ForumUserManager') ? ForumUserManager::getRoleLabel($effectiveRole) : 'Mitglied';
                    ?>
                    <div class="kunena-avatar-mini" style="width: 48px; height: 48px; font-size: 18px;">
                        <?= strtoupper(mb_substr($authorDisplayName, 0, 1)) ?>
                    </div>
                    <div>
                        <strong style="display:block; font-size: 14px; font-weight:700; color: <?= htmlspecialchars($authorRoleColor) ?>">
                            <?= htmlspecialchars($authorDisplayName) ?>
                        </strong>

                        <?php if (!empty($authorPriv['show_role'])): ?>
                            <span style="display:inline-block; padding: 2px 8px; margin-top: 4px; border-radius: 6px; font-size: 10px; font-weight: 700; background-color: <?= htmlspecialchars($authorRoleColor) ?>18; color: <?= htmlspecialchars($authorRoleColor) ?>; border: 1px solid <?= htmlspecialchars($authorRoleColor) ?>40;">
                                <?= htmlspecialchars($authorRoleLabel) ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($author && !empty($authorPriv['show_post_count'])): ?>
                            <div style="font-size: 11px; color: #64748b; margin-top: 8px;">
                                <?= (int)($author['post_count'] ?? 0) ?> Beiträge
                            </div>
                        <?php endif; ?>

                        <?php if ($author && !empty($authorPriv['show_joined_date']) && !empty($author['created_at'])): ?>
                            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 3px;">
                                Dabei seit <?= htmlspecialchars(substr($author['created_at'], 0, 10)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($author && !empty($authorPriv['show_bio']) && !empty($author['bio'])): ?>
                            <div style="font-size: 11px; color: #475569; margin-top: 8px; padding-top: 6px; border-top: 1px dashed #e2e8f0; line-height: 1.4;">
                                <?= nl2br(htmlspecialchars(mb_substr($author['bio'], 0, 140))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Gast-Ansicht: Keine Benutzerdaten anzeigen -->
                    <div class="kunena-avatar-mini" style="width: 44px; height: 44px; font-size: 18px; background: #e2e8f0; color: #64748b;">
                        <span class="material-icons" style="font-size: 22px;">person_outline</span>
                    </div>
                    <div>
                        <span style="display:block; font-size: 12.5px; font-weight:600; color: #64748b;">
                            Community-Mitglied
                        </span>
                        <span style="display:block; font-size: 10.5px; color: #94a3b8; margin-top: 4px;">
                            <span class="material-icons" style="font-size: 11px; vertical-align: middle;">lock</span>
                            Profil nur für Mitglieder
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Post Body -->
            <div class="kunena-post-body">
                <div>
                    <!-- Post Header Bar -->
                    <div class="kunena-post-topbar">
                        <span style="display:flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size: 14px;">schedule</span>
                            <?= ForumManager::formatRelativeTime($post['created_at']) ?> <span style="opacity:0.6;">(<?= htmlspecialchars($post['created_at']) ?>)</span>
                        </span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <?php if (!empty($post['updated_at'])): ?>
                                <span style="font-style:italic; font-size: 10.5px;">Bearbeitet am <?= htmlspecialchars(substr($post['updated_at'], 0, 16)) ?></span>
                            <?php endif; ?>
                            <a href="#post-<?= htmlspecialchars($post['id']) ?>" style="color:#64748b; text-decoration:none; font-weight:600;">#<?= $idx + 1 ?></a>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="forum-post-content" style="font-size: 14px; line-height: 1.65; margin-bottom: 20px;">
                        <?= ForumManager::renderFormattedContent($post['content']) ?>
                    </div>

                    <!-- User Signature (nur für eingeloggte Mitglieder und wenn vom Autor freigegeben) -->
                    <?php if ($currentUser && !empty($author['signature']) && !empty($authorPriv['show_signature'])): ?>
                        <div style="border-top:1px solid #f1f5f9; padding-top:10px; margin-top:16px; font-size: 12px; color:#64748b; font-style:italic;">
                            <?= nl2br(htmlspecialchars($author['signature'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Actions Bar -->
                <div class="kunena-post-actions">
                    <!-- Kudos / Danke Button -->
                    <?php
                    $likes = ForumManager::getPostLikes($post['id']);
                    $likeCount = count($likes);
                    $hasLiked = $currentUser ? in_array($currentUser['id'], $likes, true) : false;
                    ?>
                    <div>
                        <?php if ($currentUser): ?>
                            <form method="post" action="?forum_action=toggle_like" style="margin:0; display:inline;">
                                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="<?= $hasLiked ? 'kunena-btn-primary' : 'kunena-btn-secondary' ?>" style="padding:4px 10px; font-size:11.5px; cursor:pointer;" title="Diesen Beitrag als hilfreich markieren">
                                    <span class="material-icons" style="font-size:14px;"><?= $hasLiked ? 'favorite' : 'favorite_border' ?></span>
                                    <span><?= $hasLiked ? 'Hilfreich' : 'Danke' ?></span>
                                    <?php if ($likeCount > 0): ?>
                                        <span style="font-weight:800; margin-left:2px;">(<?= $likeCount ?>)</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php elseif ($likeCount > 0): ?>
                            <span style="display:inline-flex; align-items:center; gap:4px; font-size:11.5px; color:#64748b; background:#f8fafc; padding:3px 9px; border-radius:6px; border:1px solid #e2e8f0;">
                                <span class="material-icons" style="font-size:13px; color:#f43f5e;">favorite</span> <?= $likeCount ?> <?= $likeCount === 1 ? 'Danke' : 'Dankes' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <!-- Zitat-Button -->
                        <?php if ($currentUser): ?>
                            <button type="button" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; cursor:pointer;"
                                    onclick="insertQuote('<?= htmlspecialchars(addslashes($authorDisplayName)) ?>', <?= htmlspecialchars(json_encode($post['content'])) ?>)">
                                <span class="material-icons" style="font-size:14px;">format_quote</span> Zitieren
                            </button>
                        <?php endif; ?>

                        <!-- Edit Button -->
                        <?php if ($canEdit): ?>
                            <button type="button" class="kunena-btn-secondary" style="padding:4px 10px; font-size:11.5px; cursor:pointer;"
                                    onclick="openEditPostModal('<?= htmlspecialchars($post['id']) ?>', <?= htmlspecialchars(json_encode($post['content'])) ?>)">
                                <span class="material-icons" style="font-size:14px;">edit</span> Bearbeiten
                            </button>
                        <?php endif; ?>

                        <!-- Delete Button (Mod) -->
                        <?php if ($isMod): ?>
                            <form method="post" action="?forum_action=mod_delete_post" style="margin:0; display:inline;" onsubmit="return confirm('Diesen Beitrag wirklich löschen?')">
                                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="kunena-btn-secondary" style="padding:4px 8px; color:#dc2626; border-color:#fca5a5; cursor:pointer;" title="Beitrag löschen">
                                    <span class="material-icons" style="font-size:14px;">delete</span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Melden Button -->
                        <?php if ($currentUser && !$isAuthor): ?>
                            <button type="button" class="kunena-btn-secondary" style="padding:4px 8px; color:#64748b; cursor:pointer;" title="Beitrag melden"
                                     onclick="openReportModal('<?= htmlspecialchars($post['id']) ?>')">
                                <span class="material-icons" style="font-size:14px;">flag</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="display:flex; justify-content:center; gap:6px; margin-bottom:28px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=forum&view=topic&id=<?= urlencode($topicId) ?>&p=<?= $i ?>" 
               class="kunena-page-btn <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- Quick Reply Box -->
<div id="reply-box" class="kunena-cat-card" style="padding: 24px;">
    <?php if (!empty($topic['is_locked'])): ?>
        <div style="padding: 16px; text-align: center; color: #64748b;">
            <span class="material-icons" style="font-size: 28px; margin-bottom: 4px;">lock</span>
            <p style="margin:0;">Dieses Thema ist geschlossen. Weitere Antworten sind nicht möglich.</p>
        </div>
    <?php elseif ($canReply): ?>
        <?php 
        $replyError = $_SESSION['forum_reply_error'] ?? '';
        unset($_SESSION['forum_reply_error']);
        ?>
        <?php if (!empty($replyError)): ?>
            <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; display: flex; align-items: center; gap: 8px;">
                <span class="material-icons">error_outline</span>
                <span><?= htmlspecialchars($replyError) ?></span>
            </div>
        <?php endif; ?>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
            <h3 style="margin:0; font-weight:800; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="color:var(--primary, #2e6248);">reply</span> Antwort verfassen
            </h3>
            <div style="display:flex; align-items:center; gap:6px;">
                <button type="button" id="tab_reply_write" class="kunena-btn-primary" style="cursor:pointer;"
                        onclick="switchEditorTab('write', 'reply_textarea', 'reply_preview_box', 'tab_reply_write', 'tab_reply_preview')">
                    Schreiben
                </button>
                <button type="button" id="tab_reply_preview" class="kunena-btn-secondary" style="cursor:pointer;"
                        onclick="switchEditorTab('preview', 'reply_textarea', 'reply_preview_box', 'tab_reply_write', 'tab_reply_preview')">
                    Vorschau
                </button>
            </div>
        </div>
        <form method="post" action="?forum_action=add_post" style="margin:0;">
            <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div style="margin-bottom: 14px;">
                <textarea id="reply_textarea" name="content" rows="6" required 
                          style="width:100%; padding:12px 14px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; font-family:inherit; outline:none; box-sizing:border-box;"
                          placeholder="Schreibe deine Antwort... (Markdown und Zitate unterstützt)"></textarea>
                <div id="reply_preview_box" style="display:none; width:100%; min-height:140px; padding:14px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; box-sizing:border-box;"></div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <span style="font-size:12px; color:#64748b;">Tipp: Mit Zitat-Buttons oben kannst du Textstellen direkt zitieren.</span>
                <button type="submit" class="kunena-btn-primary" style="padding:10px 22px; font-size:13.5px; cursor:pointer;">
                    <span class="material-icons" style="font-size:16px;">send</span> Antwort absenden
                </button>
            </div>
        </form>
    <?php else: ?>
        <div style="text-align: center; padding: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:50%; background:#e0f2fe; color:#0284c7; margin-bottom:10px;">
                <span class="material-icons" style="font-size:24px;">chat</span>
            </div>
            <h4 style="font-weight: 700; font-size: 15px; color: #1e293b; margin: 0 0 6px 0;">Möchtest du mitdiskutieren?</h4>
            <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0; max-width: 480px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                Dieses Thema ist für alle Besucher frei lesbar. Um eine Antwort zu verfassen und dich mit der Community auszutauschen, melde dich bitte an oder erstelle dir ein kostenloses Konto.
            </p>
            <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <a href="?page=forum&view=login" class="kunena-btn-primary" style="padding: 8px 18px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 16px;">login</span> Jetzt anmelden
                </a>
                <a href="?page=forum&view=register" class="kunena-btn-secondary" style="padding: 8px 18px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 16px;">person_add</span> Kostenlos registrieren
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Edit Post -->
<dialog id="modal_edit_post" class="kunena-dialog">
    <form method="post" action="?forum_action=edit_post" style="padding:24px; margin:0;">
        <input type="hidden" name="post_id" id="edit_post_id" value="">
        <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <h3 style="margin:0 0 14px 0; font-size:1.1rem; font-weight:800;">Beitrag bearbeiten</h3>
        <div style="margin-bottom:16px;">
            <textarea name="content" id="edit_post_content" rows="6" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; font-family:inherit; box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="kunena-btn-secondary" style="cursor:pointer;" onclick="this.closest('dialog').close()">Abbrechen</button>
            <button type="submit" class="kunena-btn-primary" style="cursor:pointer;">Speichern</button>
        </div>
    </form>
</dialog>

<!-- Modal: Report Post -->
<dialog id="modal_report_post" class="kunena-dialog">
    <form method="post" action="?forum_action=report_post" style="padding:24px; margin:0;">
        <input type="hidden" name="post_id" id="report_post_id" value="">
        <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <h3 style="margin:0 0 8px 0; font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:6px; color:#dc2626;">
            <span class="material-icons">flag</span> Beitrag der Moderation melden
        </h3>
        <p style="font-size:12.5px; color:#64748b; margin:0 0 14px 0;">Bitte gib einen Grund für die Meldung an (z. B. Beleidigung, Spam, unpassender Inhalt):</p>
        
        <div style="margin-bottom:16px;">
            <textarea name="reason" rows="3" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; font-family:inherit; box-sizing:border-box;" placeholder="Begründung für die Moderatoren..."></textarea>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="kunena-btn-secondary" style="cursor:pointer;" onclick="this.closest('dialog').close()">Abbrechen</button>
            <button type="submit" class="kunena-btn-primary" style="background:#dc2626; cursor:pointer;">Meldung absenden</button>
        </div>
    </form>
</dialog>

<script>
function insertQuote(author, text) {
    const area = document.getElementById('reply_textarea');
    if (!area) {
        const replyBox = document.getElementById('reply-box');
        if (replyBox) {
            replyBox.scrollIntoView({ behavior: 'smooth' });
        }
        return;
    }
    const cleanText = text.replace(/\[quote.*?\[\/quote\]/gs, '').trim();
    area.value += `[quote="${author}"]${cleanText}[/quote]\n\n`;
    area.focus();
    area.scrollIntoView({ behavior: 'smooth' });
}

function renderMarkdownPreview(raw, targetEl) {
    if (!raw.trim()) {
        targetEl.innerHTML = '<span style="color:#64748b; font-style:italic;">Keine Vorschau verfügbar (noch nichts geschrieben)...</span>';
        return;
    }

    const codeTokens = [];
    // 1. Fenced Code Blocks: ```lang ... ```
    let s = raw.replace(/```([a-zA-Z0-9_\-\+]*)\r?\n([\s\S]*?)```/g, function(_, lang, code) {
        const cleanLang = (lang || 'CODE').toUpperCase();
        const safeCode = code.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const ph = `%%%FORUM_PREVIEW_CODE_${codeTokens.length}%%%`;
        codeTokens.push(
            `<div class="forum-code-wrapper">` +
            `<div class="forum-code-header"><span class="forum-code-lang">${cleanLang}</span></div>` +
            `<pre class="forum-code-block"><code class="language-${cleanLang.toLowerCase()}">${safeCode}</code></pre>` +
            `</div>`
        );
        return ph;
    });

    // 2. Escape HTML
    s = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // 3. BBCode Quotes: [quote="User"]Text[/quote]
    s = s.replace(/\[quote(?:="([^"]*)")?\]([\s\S]*?)\[\/quote\]/gi, function(_, author, body) {
        const authorHtml = author ? `<div class="quote-author"><span class="material-icons" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>${author}</strong> schrieb:</div>` : '';
        return `<blockquote class="forum-quote">${authorHtml}<div class="quote-body">${body.replace(/\n/g, '<br>')}</div></blockquote>`;
    });

    // 4. Markdown Quotes: > Zitatzeile
    s = s.replace(/((?:^|\n)&gt;[^\n]*(?:\n&gt;[^\n]*)*)/g, function(match) {
        const lines = match.trim().split('\n');
        let author = '';
        const cleanLines = [];
        lines.forEach(l => {
            const clean = l.replace(/^&gt;\s?/, '');
            const authorMatch = clean.match(/^\*\*([^*]+)\*\*\s+schrieb:?$/i);
            if (!author && authorMatch) {
                author = authorMatch[1];
            } else {
                cleanLines.push(clean);
            }
        });
        const authorHtml = author ? `<div class="quote-author"><span class="material-icons" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>${author}</strong> schrieb:</div>` : '';
        return `<blockquote class="forum-quote">${authorHtml}<div class="quote-body">${cleanLines.join('<br>')}</div></blockquote>`;
    });

    // 5. Links: [text](url)
    s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)\"\']+)(?:\s+"([^"]*)")?\)/g, function(_, label, url, title) {
        const titleAttr = title ? ` title="${title}"` : '';
        return `<a href="${url}" target="_blank" rel="noopener noreferrer"${titleAttr} style="color:var(--primary); font-weight:600; text-decoration:underline;">${label}</a>`;
    });

    // 6. Inline Code: `code`
    s = s.replace(/`([^`]+)`/g, '<code style="background:#f1f5f9; color:#0f172a; padding:2px 6px; border-radius:4px; font-size:0.9em; font-family:monospace; border:1px solid #e2e8f0;">$1</code>');

    // 7. Bold & Italic
    s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/\*([^\*]+)\*/g, '<em>$1</em>');

    // 8. Line breaks
    s = s.replace(/\n/g, '<br>');

    // 9. Restore code tokens
    codeTokens.forEach((ct, idx) => {
        s = s.replace(`%%%FORUM_PREVIEW_CODE_${idx}%%%`, ct);
    });

    targetEl.innerHTML = s;
}

function switchEditorTab(mode, editorId, previewId, btnWriteId, btnPreviewId) {
    const editor = document.getElementById(editorId);
    const preview = document.getElementById(previewId);
    const btnWrite = document.getElementById(btnWriteId);
    const btnPreview = document.getElementById(btnPreviewId);
    if (!editor || !preview) return;

    if (mode === 'preview') {
        renderMarkdownPreview(editor.value, preview);
        editor.style.display = 'none';
        preview.style.display = 'block';
        btnWrite.className = 'kunena-btn-secondary';
        btnPreview.className = 'kunena-btn-primary';
    } else {
        editor.style.display = 'block';
        preview.style.display = 'none';
        btnPreview.className = 'kunena-btn-secondary';
        btnWrite.className = 'kunena-btn-primary';
        editor.focus();
    }
}

function openEditPostModal(postId, content) {
    document.getElementById('edit_post_id').value = postId;
    document.getElementById('edit_post_content').value = content;
    document.getElementById('modal_edit_post').showModal();
}

function openReportModal(postId) {
    document.getElementById('report_post_id').value = postId;
    document.getElementById('modal_report_post').showModal();
}
</script>
