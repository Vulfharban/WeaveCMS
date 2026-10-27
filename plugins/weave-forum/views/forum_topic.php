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

// Aufrufe erhöhen
ForumManager::incrementTopicViews($topicId);

$board = ForumManager::getBoard($topic['board_id']);
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
        <div class="text-center py-4">
            <p class="text-sm text-muted mb-3">Du musst angemeldet sein, um in diesem Thema zu antworten.</p>
            <a href="?page=forum&view=login" class="btn btn-primary btn-sm">Jetzt anmelden</a>
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
    if (!area) return;
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
    let s = raw.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    s = s.replace(/\[quote(?:="([^"]*)")?\]([\s\S]*?)\[\/quote\]/gi, function(_, author, body) {
        author = author ? author : 'Zitat';
        return '<blockquote class="forum-quote"><div class="quote-author"><span class="material-icons text-xs">format_quote</span> <strong>' + author + '</strong> schrieb:</div><div class="quote-body">' + body.replace(/\n/g, '<br>') + '</div></blockquote>';
    });
    s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/\*(.*?)\*/g, '<em>$1</em>');
    s = s.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs">$1</code>');
    s = s.replace(/\n/g, '<br>');
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
