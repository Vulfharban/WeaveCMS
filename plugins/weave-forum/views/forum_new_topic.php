<?php
/**
 * Frontend: New Topic Form
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$boardId = trim($_GET['board'] ?? '');
$board = ForumManager::getBoard($boardId);

if (!$board) {
    echo '<div class="alert alert-danger">Bitte wähle ein gültiges Forum aus.</div>';
    return;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canCreate = class_exists('ForumUserManager') ? ForumUserManager::userCan('create_topic', $currentUser, $board) : false;

if (!$canCreate || !empty($board['is_locked'])) {
    echo '<div class="alert alert-warning">Du hast keine Berechtigung, in diesem Forum ein neues Thema zu eröffnen.</div>';
    return;
}

$error = $_SESSION['forum_error'] ?? '';
unset($_SESSION['forum_error']);
?>

<div class="card p-8 max-w-3xl mx-auto my-8 border shadow-sm">
    <div class="border-b pb-4 mb-6">
        <div class="text-xs text-muted mb-1">
            Forum: <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="text-primary font-bold hover:underline"><?= htmlspecialchars($board['title']) ?></a>
        </div>
        <h2 class="text-2xl font-bold flex items-center gap-2">
            <span class="material-icons text-primary">add_comment</span>
            Neues Thema eröffnen
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="?forum_action=create_topic" class="space-y-4">
        <input type="hidden" name="board_id" value="<?= htmlspecialchars($board['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="form-group">
            <label class="font-semibold block mb-1">Thementitel:</label>
            <input type="text" name="title" required autofocus class="input-field w-full text-base font-semibold" 
                   placeholder="Prägnanter, aussagekräftiger Titel...">
        </div>

        <div class="form-group">
            <div class="flex items-center justify-between mb-1">
                <label class="font-semibold block">Dein Beitrag (Eröffnungspost):</label>
                <div class="flex items-center gap-1 text-xs">
                    <button type="button" id="tab_nt_write" class="btn btn-xs btn-primary font-semibold"
                            onclick="switchEditorTab('write', 'new_topic_textarea', 'new_topic_preview_box', 'tab_nt_write', 'tab_nt_preview')">
                        Schreiben
                    </button>
                    <button type="button" id="tab_nt_preview" class="btn btn-xs btn-outline font-semibold"
                            onclick="switchEditorTab('preview', 'new_topic_textarea', 'new_topic_preview_box', 'tab_nt_write', 'tab_nt_preview')">
                        Vorschau
                    </button>
                </div>
            </div>
            <textarea id="new_topic_textarea" name="content" rows="10" required class="input-field w-full" 
                      placeholder="Beschreibe deine Frage, Idee oder dein Projekt... (Markdown wird unterstützt)"></textarea>
            <div id="new_topic_preview_box" class="input-field w-full min-h-[220px] p-4 bg-gray-50 dark:bg-gray-900 border rounded-lg hidden"></div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t">
            <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="btn btn-outline">Abbrechen</a>
            <button type="submit" class="btn btn-primary font-bold px-8 py-2.5">
                Thema veröffentlichen
            </button>
        </div>
    </form>
</div>

<script>
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
</script>
