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
</script>
