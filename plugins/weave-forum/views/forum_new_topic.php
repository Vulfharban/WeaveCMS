<?php
/**
 * Frontend: New Topic Form (Kunena-inspiriertes Design mit Markdown-Toolbar & Live-Vorschau)
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$boardId = trim($_GET['board'] ?? '');
$board = ForumManager::getBoard($boardId);

if (!$board) {
    echo '<div style="padding:20px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; text-align:center;">Bitte wähle ein gültiges Forum aus.</div>';
    return;
}

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$canCreate = class_exists('ForumUserManager') ? ForumUserManager::userCan('create_topic', $currentUser, $board) : false;

if (!$canCreate || !empty($board['is_locked'])) {
    echo '<div style="padding:24px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; text-align:center;">Du hast keine Berechtigung, in diesem Forum ein neues Thema zu eröffnen.</div>';
    return;
}

$error = $_SESSION['forum_error'] ?? '';
unset($_SESSION['forum_error']);
?>

<style>
.kunena-newtopic-wrap {
    max-width: 920px;
    margin: 0 auto 32px auto;
}
.kunena-newtopic-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-left: 6px solid var(--primary, #2e6248);
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.kunena-newtopic-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    padding: 20px 26px;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.kunena-newtopic-body {
    padding: 26px;
}
.kunena-nt-field {
    margin-bottom: 22px;
}
.kunena-nt-label {
    display: block;
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}
.kunena-nt-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.kunena-nt-input:focus,
.kunena-nt-textarea:focus {
    border-color: var(--primary, #2e6248);
    box-shadow: 0 0 0 3px rgba(46, 98, 72, 0.12);
}
.kunena-editor-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-bottom: none;
    border-radius: 10px 10px 0 0;
}
.kunena-md-tools {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
}
.kunena-md-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 5px 9px;
    border-radius: 6px;
    border: 1px solid transparent;
    background: transparent;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.kunena-md-btn:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    color: var(--primary, #2e6248);
}
.kunena-nt-textarea {
    width: 100%;
    min-height: 240px;
    padding: 14px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 0 0 10px 10px;
    font-size: 14px;
    line-height: 1.65;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.kunena-nt-preview {
    display: none;
    width: 100%;
    min-height: 240px;
    padding: 18px 20px;
    border: 1px solid #cbd5e1;
    border-radius: 0 0 10px 10px;
    background: #ffffff;
    font-size: 14px;
    line-height: 1.65;
    color: #1e293b;
    box-sizing: border-box;
}
.kunena-nt-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 20px;
    margin-top: 10px;
    border-top: 1px solid #e2e8f0;
}
</style>

<div class="kunena-newtopic-wrap">
    <div class="kunena-newtopic-card">
        <!-- Header -->
        <div class="kunena-newtopic-header">
            <div>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 15px; color: var(--primary, #2e6248);"><?= htmlspecialchars($board['icon'] ?? 'forum') ?></span>
                    <span>Forum:</span>
                    <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: none;">
                        <?= htmlspecialchars($board['title']) ?>
                    </a>
                </div>
                <h2 style="margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                    <span class="material-icons" style="color: var(--primary, #2e6248); font-size: 26px;">add_comment</span>
                    <span>Neues Thema eröffnen</span>
                </h2>
            </div>

            <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="kunena-btn-secondary" style="padding: 7px 14px;">
                <span class="material-icons" style="font-size: 16px;">arrow_back</span> Zurück zum Forum
            </a>
        </div>

        <!-- Body -->
        <div class="kunena-newtopic-body">
            <?php if (!empty($error)): ?>
                <div style="margin-bottom: 20px; padding: 13px 16px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600;">
                    <span class="material-icons">error_outline</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="?forum_action=create_topic" style="margin: 0;">
                <input type="hidden" name="board_id" value="<?= htmlspecialchars($board['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <!-- Title Field -->
                <div class="kunena-nt-field">
                    <label class="kunena-nt-label" for="nt_title_input">Thementitel / Betreff:</label>
                    <input type="text" id="nt_title_input" name="title" required autofocus minlength="3" maxlength="140"
                           class="kunena-nt-input" placeholder="Prägnanter, aussagekräftiger Titel für dein Anliegen...">
                </div>

                <!-- Content Editor Field -->
                <div class="kunena-nt-field" style="margin-bottom: 14px;">
                    <label class="kunena-nt-label" for="new_topic_textarea">Dein Beitrag (Eröffnungspost):</label>

                    <!-- Editor Toolbar & Write/Preview Tabs -->
                    <div class="kunena-editor-bar">
                        <div class="kunena-md-tools">
                            <button type="button" class="kunena-md-btn" title="Fett (**Text**)" onclick="insertTopicMarkdown('**', '**', 'Fetter Text')">
                                <span class="material-icons" style="font-size: 16px;">format_bold</span>
                            </button>
                            <button type="button" class="kunena-md-btn" title="Kursiv (*Text*)" onclick="insertTopicMarkdown('*', '*', 'Kursiver Text')">
                                <span class="material-icons" style="font-size: 16px;">format_italic</span>
                            </button>
                            <button type="button" class="kunena-md-btn" title="Zitat einfügen" onclick="insertTopicMarkdown('> ', '', 'Zitattext')">
                                <span class="material-icons" style="font-size: 16px;">format_quote</span> Zitat
                            </button>
                            <button type="button" class="kunena-md-btn" title="Inline-Code (`code`)" onclick="insertTopicMarkdown('`', '`', 'code')">
                                <span class="material-icons" style="font-size: 16px;">code</span> Inline
                            </button>
                            <button type="button" class="kunena-md-btn" title="Mehrzeiliger Code-Block" onclick="insertTopicMarkdown('```php\n', '\n```', '// Dein Code hier')">
                                <span class="material-icons" style="font-size: 16px;">terminal</span> Code-Block
                            </button>
                            <button type="button" class="kunena-md-btn" title="Link einfügen" onclick="insertTopicMarkdown('[', '](https://)', 'Linkbeschreibung')">
                                <span class="material-icons" style="font-size: 16px;">link</span> Link
                            </button>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" id="tab_nt_write" class="kunena-btn-primary" style="cursor: pointer; padding: 5px 12px; font-size: 12px;"
                                    onclick="switchEditorTab('write', 'new_topic_textarea', 'new_topic_preview_box', 'tab_nt_write', 'tab_nt_preview')">
                                <span class="material-icons" style="font-size: 14px;">edit</span> Schreiben
                            </button>
                            <button type="button" id="tab_nt_preview" class="kunena-btn-secondary" style="cursor: pointer; padding: 5px 12px; font-size: 12px;"
                                    onclick="switchEditorTab('preview', 'new_topic_textarea', 'new_topic_preview_box', 'tab_nt_write', 'tab_nt_preview')">
                                <span class="material-icons" style="font-size: 14px;">visibility</span> Vorschau
                            </button>
                        </div>
                    </div>

                    <textarea id="new_topic_textarea" name="content" rows="11" required class="kunena-nt-textarea" 
                              placeholder="Beschreibe deine Frage, Idee oder dein Projekt ausführlich... (Markdown, Links, Zitate und Code-Blöcke werden unterstützt)"></textarea>
                    <div id="new_topic_preview_box" class="kunena-nt-preview forum-post-content"></div>
                </div>

                <div style="font-size: 12px; color: #64748b; margin-bottom: 18px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 15px; color: var(--primary, #2e6248);">tips_and_updates</span>
                    <span>Tipp: Nutze die Formatierungsleiste oben für <strong>**Fett**</strong>, <em>*Kursiv*</em>, Zitate oder <code>```Code-Blöcke```</code> und prüfe deinen Beitrag vorab über <strong>Vorschau</strong>.</span>
                </div>

                <!-- Footer Buttons -->
                <div class="kunena-nt-footer">
                    <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="kunena-btn-secondary" style="padding: 10px 18px; font-size: 13.5px;">
                        Abbrechen
                    </a>
                    <button type="submit" class="kunena-btn-primary" style="padding: 11px 26px; font-size: 14px; cursor: pointer;">
                        <span class="material-icons" style="font-size: 18px;">send</span> Thema veröffentlichen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function insertTopicMarkdown(prefix, suffix, placeholder) {
    const area = document.getElementById('new_topic_textarea');
    if (!area) return;
    // Falls aktuell in der Vorschau, zurück zum Schreibmodus wechseln
    if (area.style.display === 'none') {
        switchEditorTab('write', 'new_topic_textarea', 'new_topic_preview_box', 'tab_nt_write', 'tab_nt_preview');
    }
    const start = area.selectionStart || 0;
    const end = area.selectionEnd || 0;
    const selected = area.value.substring(start, end);
    const insertText = selected.length > 0 ? selected : placeholder;
    const replacement = prefix + insertText + suffix;

    area.value = area.value.substring(0, start) + replacement + area.value.substring(end);
    area.focus();
    area.setSelectionRange(start + prefix.length, start + prefix.length + insertText.length);
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
</script>
