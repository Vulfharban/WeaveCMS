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
            <label class="font-semibold block mb-1">Dein Beitrag (Eröffnungspost):</label>
            <textarea name="content" rows="10" required class="input-field w-full" 
                      placeholder="Beschreibe deine Frage, Idee oder dein Projekt... (Markdown wird unterstützt)"></textarea>
        </div>

        <div class="flex justify-between items-center pt-4 border-t">
            <a href="?page=forum&view=board&id=<?= urlencode($board['id']) ?>" class="btn btn-outline">Abbrechen</a>
            <button type="submit" class="btn btn-primary font-bold px-8 py-2.5">
                Thema veröffentlichen
            </button>
        </div>
    </form>
</div>
