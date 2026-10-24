<?php
/**
 * Frontend: Forum Profile View
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$currentUser = ForumUserManager::getCurrentUser();
if (!$currentUser) {
    echo '<div class="alert alert-warning max-w-lg mx-auto my-8">Bitte melde dich an, um dein Profil zu verwalten. <a href="?page=forum&view=login" class="btn btn-sm btn-primary ml-4">Zur Anmeldung</a></div>';
    return;
}

$msg = $_SESSION['forum_profile_msg'] ?? '';
unset($_SESSION['forum_profile_msg']);
?>

<div class="card p-8 max-w-2xl mx-auto my-8 shadow border">
    <div class="flex items-center justify-between border-b pb-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="avatar-circle-lg">
                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($currentUser['username']) ?></h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="badge badge-primary"><?= htmlspecialchars($currentUser['role']) ?></span>
                    <span class="text-xs text-muted">Mitglied seit <?= htmlspecialchars(substr($currentUser['created_at'], 0, 10)) ?></span>
                </div>
            </div>
        </div>
        <a href="?forum_auth=logout" class="btn btn-outline btn-sm">Abmelden</a>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            Profil erfolgreich aktualisiert!
        </div>
    <?php endif; ?>

    <!-- User Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-center bg-gray-50 dark:bg-gray-800 p-4 rounded">
        <div>
            <div class="text-2xl font-bold text-primary"><?= (int)($currentUser['topic_count'] ?? 0) ?></div>
            <div class="text-xs text-muted">Erstellte Themen</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-primary"><?= (int)($currentUser['post_count'] ?? 0) ?></div>
            <div class="text-xs text-muted">Beiträge</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-primary"><?= htmlspecialchars($currentUser['status']) ?></div>
            <div class="text-xs text-muted">Kontostatus</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-primary"><?= !empty($currentUser['last_login']) ? htmlspecialchars(substr($currentUser['last_login'], 0, 10)) : '-' ?></div>
            <div class="text-xs text-muted">Letzter Login</div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <form method="post" action="?forum_auth=update_profile" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="form-group">
            <label class="font-semibold block mb-1">E-Mail-Adresse:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required class="input-field w-full">
        </div>

        <div class="form-group">
            <label class="font-semibold block mb-1">Über mich / Biografie:</label>
            <textarea name="bio" rows="3" class="input-field w-full" placeholder="Erzähle der Community kurz über dich..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label class="font-semibold block mb-1">Forum-Signatur (wird unter Beiträgen angezeigt):</label>
            <textarea name="signature" rows="2" class="input-field w-full" placeholder="Deine persönliche Signatur (max. 200 Zeichen)..."><?= htmlspecialchars($currentUser['signature'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="notification_email" value="1" <?= !empty($currentUser['notification_email']) ? 'checked' : '' ?>>
                <span>E-Mail-Benachrichtigungen bei Antworten auf abonnierte Themen erhalten</span>
            </label>
        </div>

        <div class="pt-4 border-t flex justify-between items-center">
            <button type="submit" class="btn btn-primary">
                Profil speichern
            </button>

            <!-- DSGVO Export -->
            <a href="?forum_auth=export_data" class="btn btn-outline btn-sm flex items-center gap-1">
                <span class="material-icons text-sm">download</span> Meine Daten exportieren (DSGVO)
            </a>
        </div>
    </form>
</div>
