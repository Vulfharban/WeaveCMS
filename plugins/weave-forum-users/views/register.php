<?php
/**
 * Frontend: Forum Register View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if ($currentUser) {
    echo '<div class="alert alert-info max-w-lg mx-auto my-8">Du bist bereits als <strong>' . htmlspecialchars($currentUser['username']) . '</strong> angemeldet.</div>';
    return;
}

$error = $_SESSION['forum_auth_error'] ?? '';
unset($_SESSION['forum_auth_error']);
?>

<div class="card p-8 max-w-md mx-auto my-12 shadow-lg border">
    <div class="text-center mb-6">
        <span class="material-icons text-4xl text-primary mb-2">person_add</span>
        <h2 class="text-2xl font-bold">Konto erstellen</h2>
        <p class="text-sm text-muted">Erstelle dein kostenloses Profil für unsere Community.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4">
            <span class="material-icons">error_outline</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="?forum_auth=register">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <div class="form-group mb-4">
            <label class="font-semibold block mb-1">Gewünschter Benutzername:</label>
            <input type="text" name="username" required autofocus class="input-field w-full" 
                   placeholder="z. B. Holzwurm99" pattern="^[a-zA-Z0-9_\-\.]+$" title="Buchstaben, Zahlen, Punkte, Bindestriche">
            <span class="text-xs text-muted">Mindestens 3 Zeichen. Nur Buchstaben, Zahlen, Bindestriche.</span>
        </div>

        <div class="form-group mb-4">
            <label class="font-semibold block mb-1">E-Mail-Adresse:</label>
            <input type="email" name="email" required class="input-field w-full" placeholder="max@beispiel.de">
        </div>

        <div class="form-group mb-4">
            <label class="font-semibold block mb-1">Passwort:</label>
            <input type="password" name="password" required minlength="8" class="input-field w-full" placeholder="Mindestens 8 Zeichen">
        </div>

        <div class="form-group mb-6">
            <label class="font-semibold block mb-1">Passwort wiederholen:</label>
            <input type="password" name="password_confirm" required minlength="8" class="input-field w-full" placeholder="Passwort bestätigen">
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 font-bold">
            Kostenlos registrieren
        </button>

        <div class="text-center mt-6 text-sm">
            Bereits registriert? <a href="?page=forum&view=login" class="text-primary font-semibold hover:underline">Hier anmelden</a>
        </div>
    </form>
</div>
