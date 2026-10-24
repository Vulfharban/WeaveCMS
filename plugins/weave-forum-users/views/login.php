<?php
/**
 * Frontend: Forum Login View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if ($currentUser) {
    echo '<div class="alert alert-info max-w-lg mx-auto my-8">Du bist bereits als <strong>' . htmlspecialchars($currentUser['username']) . '</strong> angemeldet. <a href="?forum_auth=logout" class="btn btn-sm btn-outline ml-4">Abmelden</a></div>';
    return;
}

$error = $_SESSION['forum_auth_error'] ?? '';
unset($_SESSION['forum_auth_error']);
?>

<div class="card p-8 max-w-md mx-auto my-12 shadow-lg border">
    <div class="text-center mb-6">
        <span class="material-icons text-4xl text-primary mb-2">lock</span>
        <h2 class="text-2xl font-bold">Forum Anmeldung</h2>
        <p class="text-sm text-muted">Melde dich mit deinem Benutzerkonto an, um mitzudiskutieren.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4">
            <span class="material-icons">error_outline</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="?forum_auth=login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <div class="form-group mb-4">
            <label class="font-semibold block mb-1">Benutzername oder E-Mail:</label>
            <input type="text" name="login" required autofocus class="input-field w-full" placeholder="maxmuster oder max@beispiel.de">
        </div>

        <div class="form-group mb-6">
            <label class="font-semibold block mb-1">Passwort:</label>
            <input type="password" name="password" required class="input-field w-full" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 font-bold">
            Anmelden
        </button>

        <div class="text-center mt-6 text-sm">
            Noch kein Konto? <a href="?page=forum&view=register" class="text-primary font-semibold hover:underline">Hier registrieren</a>
        </div>
    </form>
</div>
