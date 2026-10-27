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
    echo '<div class="alert alert-danger">Dieses Thema existiert nicht mehr oder wurde entfernt.</div>';
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

<!-- Topic Header -->
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <?php if (!empty($topic['is_pinned'])): ?>
                <span class="badge badge-warning text-xs flex items-center gap-1">
                    <span class="material-icons text-xs">push_pin</span> Angepinnt
                </span>
            <?php endif; ?>
            <?php if (!empty($topic['is_solved'])): ?>
                <span class="badge badge-success text-xs flex items-center gap-1 font-bold">
                    <span class="material-icons text-xs">check_circle</span> Gelöst
                </span>
            <?php endif; ?>
            <?php if (!empty($topic['is_locked'])): ?>
                <span class="badge badge-secondary text-xs flex items-center gap-1">
                    <span class="material-icons text-xs">lock</span> Geschlossen
                </span>
            <?php endif; ?>
            <span class="text-xs text-muted">
                In Forum: <a href="?page=forum&view=board&id=<?= urlencode($board['id'] ?? '') ?>" class="text-primary hover:underline font-semibold"><?= htmlspecialchars($board['title'] ?? 'Forum') ?></a>
            </span>
        </div>
        <h2 class="text-2xl font-bold"><?= htmlspecialchars($topic['title']) ?></h2>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <?php 
        $canToggleSolved = $currentUser && (($currentUser['id'] === $topic['author_id']) || $isMod);
        ?>
        <?php if ($canToggleSolved): ?>
            <!-- Gelöst Toggle Button -->
            <form method="post" action="?forum_action=toggle_solved" class="inline">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-sm <?= !empty($topic['is_solved']) ? 'btn-success' : 'btn-outline' ?> flex items-center gap-1"
                        title="Themenstatus umschalten">
                    <span class="material-icons text-xs"><?= !empty($topic['is_solved']) ? 'check_circle' : 'task_alt' ?></span>
                    <?= !empty($topic['is_solved']) ? 'Gelöst ✓' : 'Als gelöst markieren' ?>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($currentUser): ?>
            <!-- Abonnement Toggle -->
            <form method="post" action="?forum_action=toggle_subscription" class="inline">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-sm <?= $isSubscribed ? 'btn-primary' : 'btn-outline' ?> flex items-center gap-1">
                    <span class="material-icons text-xs"><?= $isSubscribed ? 'notifications_active' : 'notifications_none' ?></span>
                    <?= $isSubscribed ? 'Abonniert' : 'Abonnieren' ?>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($canReply && empty($topic['is_locked'])): ?>
            <a href="#reply-box" class="btn btn-primary btn-sm flex items-center gap-1 font-bold">
                <span class="material-icons text-xs">reply</span> Antworten
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Moderation Toolbar (if Moderator / Admin) -->
<?php if ($isMod): ?>
    <div class="card p-3 mb-6 bg-amber-50/40 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800 text-xs flex flex-wrap items-center justify-between gap-3">
        <div class="font-bold flex items-center gap-1 text-amber-700 dark:text-amber-400">
            <span class="material-icons text-sm">shield</span> Moderations-Werkzeuge:
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Pin / Unpin -->
            <form method="post" action="?forum_action=mod_pin_topic" class="inline">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="pinned" value="<?= !empty($topic['is_pinned']) ? '0' : '1' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-outline btn-xs">
                    <?= !empty($topic['is_pinned']) ? 'Lösen (Unpin)' : 'Anpinnen' ?>
                </button>
            </form>

            <!-- Lock / Unlock -->
            <form method="post" action="?forum_action=mod_lock_topic" class="inline">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="locked" value="<?= !empty($topic['is_locked']) ? '0' : '1' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-outline btn-xs">
                    <?= !empty($topic['is_locked']) ? 'Öffnen' : 'Schließen (Sperren)' ?>
                </button>
            </form>

            <!-- Move Topic -->
            <button type="button" class="btn btn-outline btn-xs" onclick="document.getElementById('modal_move_topic').showModal()">
                Verschieben...
            </button>

            <!-- Delete Topic -->
            <form method="post" action="?forum_action=mod_delete_topic" class="inline" onsubmit="return confirm('Möchtest du dieses gesamte Thema wirklich löschen?')">
                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                <input type="hidden" name="board_id" value="<?= htmlspecialchars($board['id'] ?? '') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-danger btn-xs">Thema löschen</button>
            </form>
        </div>
    </div>

    <!-- Modal: Move Topic -->
    <dialog id="modal_move_topic" class="modal-dialog">
        <form method="post" action="?forum_action=mod_move_topic" class="p-6">
            <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <h3 class="text-lg font-bold mb-3">Thema in ein anderes Forum verschieben</h3>
            <div class="form-group mb-4">
                <label class="font-semibold block mb-1">Zielforum auswählen:</label>
                <select name="new_board_id" class="input-field w-full">
                    <?php foreach ($allBoards as $b): ?>
                        <option value="<?= htmlspecialchars($b['id']) ?>" <?= $b['id'] === $topic['board_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Verschieben</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<!-- Posts List -->
<div class="space-y-6 mb-8">
    <?php foreach ($posts as $idx => $post): ?>
        <?php 
        $author = class_exists('ForumUserManager') ? ForumUserManager::getUserById($post['author_id']) : null;
        $authorRole = $author['role'] ?? 'forum_user';
        $isAuthor = ($currentUser && $currentUser['id'] === $post['author_id']);
        $canEdit = ($isAuthor || $isMod);
        ?>
        <div id="post-<?= htmlspecialchars($post['id']) ?>" class="kunena-post-card">
            <!-- Left: Author Card -->
            <div class="kunena-post-author">
                <div class="kunena-avatar-mini" style="width: 48px; height: 48px; font-size: 18px;">
                    <?= strtoupper(substr($post['author_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <?php
                    $authorRoleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                    $authorRoleLabel = class_exists('ForumUserManager') ? ForumUserManager::getRoleLabel($authorRole) : 'Mitglied';
                    ?>
                    <strong style="display:block; font-size: 14px; font-weight:700; color: <?= htmlspecialchars($authorRoleColor) ?>"><?= htmlspecialchars($post['author_name']) ?></strong>
                    <span style="display:inline-block; padding: 2px 8px; margin-top: 4px; border-radius: 6px; font-size: 10px; font-weight: 700; background-color: <?= htmlspecialchars($authorRoleColor) ?>18; color: <?= htmlspecialchars($authorRoleColor) ?>; border: 1px solid <?= htmlspecialchars($authorRoleColor) ?>40;">
                        <?= htmlspecialchars($authorRoleLabel) ?>
                    </span>
                    
                    <?php if ($author): ?>
                        <div style="font-size: 11px; color: #64748b; margin-top: 8px;">
                            <?= (int)($author['post_count'] ?? 0) ?> Beiträge
                        </div>
                    <?php endif; ?>
                </div>
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

                    <!-- User Signature -->
                    <?php if (!empty($author['signature'])): ?>
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
                            <form method="post" action="?forum_action=toggle_like" class="inline">
                                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-xs <?= $hasLiked ? 'btn-primary' : 'btn-outline' ?> flex items-center gap-1" title="Diesen Beitrag als hilfreich markieren">
                                    <span class="material-icons text-xs"><?= $hasLiked ? 'favorite' : 'favorite_border' ?></span>
                                    <span><?= $hasLiked ? 'Hilfreich' : 'Danke' ?></span>
                                    <?php if ($likeCount > 0): ?>
                                        <span class="font-bold ml-0.5">(<?= $likeCount ?>)</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php elseif ($likeCount > 0): ?>
                            <span class="badge badge-secondary text-xs flex items-center gap-1 text-muted">
                                <span class="material-icons text-xs text-rose-500">favorite</span> <?= $likeCount ?> <?= $likeCount === 1 ? 'Danke' : 'Dankes' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Zitat-Button -->
                        <button type="button" class="btn btn-outline btn-xs flex items-center gap-1"
                                onclick="insertQuote('<?= htmlspecialchars(addslashes($post['author_name'])) ?>', <?= htmlspecialchars(json_encode($post['content'])) ?>)">
                            <span class="material-icons text-xs">format_quote</span> Zitieren
                        </button>

                        <!-- Edit Button -->
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-outline btn-xs flex items-center gap-1"
                                    onclick="openEditPostModal('<?= htmlspecialchars($post['id']) ?>', <?= htmlspecialchars(json_encode($post['content'])) ?>)">
                                <span class="material-icons text-xs">edit</span> Bearbeiten
                            </button>
                        <?php endif; ?>

                        <!-- Delete Button (Mod) -->
                        <?php if ($isMod): ?>
                            <form method="post" action="?forum_action=mod_delete_post" class="inline" onsubmit="return confirm('Diesen Beitrag wirklich löschen?')">
                                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-outline btn-xs text-danger" title="Beitrag löschen">
                                    <span class="material-icons text-xs">delete</span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Melden Button -->
                        <?php if ($currentUser && !$isAuthor): ?>
                            <button type="button" class="btn btn-outline btn-xs text-muted" title="Beitrag melden"
                                    onclick="openReportModal('<?= htmlspecialchars($post['id']) ?>')">
                                <span class="material-icons text-xs">flag</span>
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
    <div class="flex justify-center gap-2 mb-8">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=forum&view=topic&id=<?= urlencode($topicId) ?>&p=<?= $i ?>" 
               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- Quick Reply Box -->
<div id="reply-box" class="card p-6 border shadow-sm">
    <?php if (!empty($topic['is_locked'])): ?>
        <div class="p-4 text-center text-muted">
            <span class="material-icons text-2xl mb-1">lock</span>
            <p>Dieses Thema ist geschlossen. Weitere Antworten sind nicht möglich.</p>
        </div>
    <?php elseif ($canReply): ?>
        <?php 
        $replyError = $_SESSION['forum_reply_error'] ?? '';
        unset($_SESSION['forum_reply_error']);
        ?>
        <?php if (!empty($replyError)): ?>
            <div class="alert alert-danger mb-4">
                <span class="material-icons">error_outline</span>
                <?= htmlspecialchars($replyError) ?>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-icons text-primary">reply</span> Antwort verfassen
            </h3>
            <div class="flex items-center gap-1 text-xs">
                <button type="button" id="tab_reply_write" class="btn btn-xs btn-primary font-semibold"
                        onclick="switchEditorTab('write', 'reply_textarea', 'reply_preview_box', 'tab_reply_write', 'tab_reply_preview')">
                    Schreiben
                </button>
                <button type="button" id="tab_reply_preview" class="btn btn-xs btn-outline font-semibold"
                        onclick="switchEditorTab('preview', 'reply_textarea', 'reply_preview_box', 'tab_reply_write', 'tab_reply_preview')">
                    Vorschau
                </button>
            </div>
        </div>
        <form method="post" action="?forum_action=add_post">
            <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="form-group mb-4">
                <textarea id="reply_textarea" name="content" rows="6" required 
                          class="input-field w-full" placeholder="Schreibe deine Antwort... (Markdown und Zitate unterstützt)"></textarea>
                <div id="reply_preview_box" class="input-field w-full min-h-[140px] p-4 bg-gray-50 dark:bg-gray-900 border rounded-lg hidden"></div>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-xs text-muted">Tipp: Mit Zitat-Buttons oben kannst du Textstellen direkt zitieren.</span>
                <button type="submit" class="btn btn-primary font-bold px-6">
                    Antwort absenden
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
<dialog id="modal_edit_post" class="modal-dialog">
    <form method="post" action="?forum_action=edit_post" class="p-6">
        <input type="hidden" name="post_id" id="edit_post_id" value="">
        <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <h3 class="text-lg font-bold mb-3">Beitrag bearbeiten</h3>
        <div class="form-group mb-4">
            <textarea name="content" id="edit_post_content" rows="6" required class="input-field w-full"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </div>
    </form>
</dialog>

<!-- Modal: Report Post -->
<dialog id="modal_report_post" class="modal-dialog">
    <form method="post" action="?forum_action=report_post" class="p-6">
        <input type="hidden" name="post_id" id="report_post_id" value="">
        <input type="hidden" name="topic_id" value="<?= htmlspecialchars($topic['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <h3 class="text-lg font-bold mb-2 flex items-center gap-1 text-danger">
            <span class="material-icons">flag</span> Beitrag der Moderation melden
        </h3>
        <p class="text-xs text-muted mb-3">Bitte gib einen Grund für die Meldung an (z. B. Beleidigung, Spam, unpassender Inhalt):</p>
        
        <div class="form-group mb-4">
            <textarea name="reason" rows="3" required class="input-field w-full" placeholder="Begründung für die Moderatoren..."></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
            <button type="submit" class="btn btn-danger">Meldung absenden</button>
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
        targetEl.innerHTML = '<span class="text-muted italic">Keine Vorschau verfügbar (noch nichts geschrieben)...</span>';
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
        const authorHtml = author ? `<div class="quote-author"><span class="material-icons text-xs" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>${author}</strong> schrieb:</div>` : '';
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
        const authorHtml = author ? `<div class="quote-author"><span class="material-icons text-xs" style="font-size:14px; vertical-align:middle;">format_quote</span> <strong>${author}</strong> schrieb:</div>` : '';
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
        editor.classList.add('hidden');
        preview.classList.remove('hidden');
        btnWrite.classList.remove('btn-primary');
        btnWrite.classList.add('btn-outline');
        btnPreview.classList.remove('btn-outline');
        btnPreview.classList.add('btn-primary');
    } else {
        editor.classList.remove('hidden');
        preview.classList.add('hidden');
        btnPreview.classList.remove('btn-primary');
        btnPreview.classList.add('btn-outline');
        btnWrite.classList.remove('btn-outline');
        btnWrite.classList.add('btn-primary');
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
