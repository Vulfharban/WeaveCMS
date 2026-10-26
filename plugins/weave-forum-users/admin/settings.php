<?php
/**
 * Admin-Ansicht: Forum-Benutzerverwaltung (weave-forum-users)
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

require_once dirname(__DIR__) . '/ForumUserManager.php';

$users = ForumUserManager::getAllUsers();
$currentDriver = ForumUserManager::getStorageDriver();
$jsonFile = ForumUserManager::getJsonFilePath();
$sqliteFile = ForumUserManager::getSqliteFilePath();

$searchQuery = trim($_GET['fuser_q'] ?? '');
$roleFilter = trim($_GET['fuser_role'] ?? '');

$filteredUsers = array_filter($users, function($u) use ($searchQuery, $roleFilter) {
    if (!empty($roleFilter) && ($u['role'] ?? '') !== $roleFilter) {
        return false;
    }
    if (!empty($searchQuery)) {
        $q = mb_strtolower($searchQuery);
        $nameMatch = str_contains(mb_strtolower($u['username'] ?? ''), $q);
        $emailMatch = str_contains(mb_strtolower($u['email'] ?? ''), $q);
        return $nameMatch || $emailMatch;
    }
    return true;
});

$msg = $_GET['fuser_msg'] ?? '';
$err = $_GET['fuser_err'] ?? '';
$migratedCount = isset($_GET['fuser_count']) ? (int)$_GET['fuser_count'] : null;
$isSqliteSupported = ForumUserManager::isSqliteSupported();
?>

<div class="card p-6 mb-6">
    <div class="flex items-center justify-between border-b pb-4 mb-4">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span class="material-icons text-primary">group</span>
                Forum-Benutzerverwaltung
            </h2>
            <p class="text-sm text-muted">
                Verwalte registrierte Forumsmitglieder, Rollenberechtigungen, Status und die Speicher-Engine.
            </p>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-info">
                <?= count($users) ?> Mitglieder
            </span>
            <span class="badge badge-primary">
                Speicher: <?= strtoupper($currentDriver) ?>
            </span>
        </div>
    </div>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger mb-4">
            <span class="material-icons">error</span>
            <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'migrated_sqlite'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            <?= ($migratedCount !== null && $migratedCount > 0)
                ? "Erfolgreich {$migratedCount} Benutzer nach SQLite migriert! Die SQLite-Datenbank (data/forum.sqlite) ist aktiv."
                : "SQLite-Datenbank (data/forum.sqlite) und Tabellen wurden erfolgreich initialisiert und als aktiver Speichertreiber aktiviert!" ?>
        </div>
    <?php elseif ($msg === 'migrated_json'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            Erfolgreich nach Flat-File JSON migriert!
        </div>
    <?php elseif ($msg === 'user_updated'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            Benutzerkonto wurde aktualisiert.
        </div>
    <?php elseif ($msg === 'user_anonymized'): ?>
        <div class="alert alert-warning mb-4">
            <span class="material-icons">info</span>
            Benutzerkonto wurde DSGVO-konform anonymisiert und unkenntlich gemacht.
        </div>
    <?php elseif ($msg === 'user_deleted'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">delete</span>
            Benutzerkonto wurde endgültig gelöscht.
        </div>
    <?php endif; ?>

    <!-- Sub-Nav / Tabs -->
    <div class="tabs-nav mb-6 flex gap-2 border-b">
        <a href="#fusers-list" class="btn btn-sm btn-outline active">Mitgliederliste</a>
        <a href="#fusers-storage" class="btn btn-sm btn-outline">Speicher-Engine & Migration</a>
        <a href="#fusers-settings" class="btn btn-sm btn-outline">Registrierungs-Optionen</a>
    </div>

    <!-- TAB 1: USER LIST -->
    <div id="fusers-list" class="tab-pane">
        <!-- Filter & Search -->
        <form method="get" class="flex flex-wrap gap-3 mb-4 items-center">
            <input type="hidden" name="tab" value="tab-plugins">
            <input type="hidden" name="plugin_settings" value="weave-forum-users">
            
            <input type="text" name="fuser_q" value="<?= htmlspecialchars($searchQuery) ?>" 
                   placeholder="Benutzername oder E-Mail suchen..." class="input-field" style="max-width: 300px;">
            
            <select name="fuser_role" class="input-field" style="max-width: 200px;">
                <option value="">Alle Rollen</option>
                <option value="forum_admin" <?= $roleFilter === 'forum_admin' ? 'selected' : '' ?>>Forum-Administrator</option>
                <option value="forum_moderator" <?= $roleFilter === 'forum_moderator' ? 'selected' : '' ?>>Forum-Moderator</option>
                <option value="forum_user" <?= $roleFilter === 'forum_user' ? 'selected' : '' ?>>Normaler Benutzer</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1">
                <span class="material-icons text-sm">search</span> Filtern
            </button>
            <?php if (!empty($searchQuery) || !empty($roleFilter)): ?>
                <a href="?tab=tab-plugins&plugin_settings=weave-forum-users" class="btn btn-outline btn-sm">Zurücksetzen</a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto">
            <table class="table-modern w-full">
                <thead>
                    <tr>
                        <th>Benutzer</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Status</th>
                        <th>Aktivität</th>
                        <th>Registriert</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredUsers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-6 text-muted">
                                Keine Foren-Benutzer gefunden.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($u['username'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                                            <div class="text-xs text-muted">ID: <?= htmlspecialchars($u['id']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if (($u['role'] ?? '') === 'forum_admin'): ?>
                                        <span class="badge badge-primary">Admin</span>
                                    <?php elseif (($u['role'] ?? '') === 'forum_moderator'): ?>
                                        <span class="badge badge-info">Moderator</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Mitglied</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($u['status'] ?? '') === 'active'): ?>
                                        <span class="badge badge-success">Aktiv</span>
                                    <?php elseif (($u['status'] ?? '') === 'pending_approval'): ?>
                                        <span class="badge badge-warning">Wartet auf Freigabe</span>
                                    <?php elseif (($u['status'] ?? '') === 'banned'): ?>
                                        <span class="badge badge-danger">Gesperrt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-xs">
                                    <?= (int)($u['topic_count'] ?? 0) ?> Themen<br>
                                    <?= (int)($u['post_count'] ?? 0) ?> Beiträge
                                </td>
                                <td class="text-xs text-muted">
                                    <?= htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)) ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <!-- Rolle & Status Dialog Trigger -->
                                        <button type="button" class="btn btn-outline btn-xs" 
                                                onclick="document.getElementById('edit_user_<?= $u['id'] ?>').showModal()">
                                            <span class="material-icons text-xs">edit</span>
                                        </button>

                                        <!-- DSGVO Export -->
                                        <a href="?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_action=export&id=<?= urlencode($u['id']) ?>" 
                                           class="btn btn-outline btn-xs" title="DSGVO-Datenexport">
                                            <span class="material-icons text-xs">download</span>
                                        </a>

                                        <!-- Löschen / Anonymisieren -->
                                        <button type="button" class="btn btn-outline btn-xs text-danger" 
                                                onclick="if(confirm('Möchtest du dieses Konto wirklich anonymisieren oder löschen?')) document.getElementById('del_user_<?= $u['id'] ?>').submit()">
                                            <span class="material-icons text-xs">delete</span>
                                        </button>
                                        <form id="del_user_<?= $u['id'] ?>" method="post" style="display:none;">
                                            <input type="hidden" name="action" value="forum_user_delete">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        </form>
                                    </div>

                                    <!-- Modal zur Bearbeitung -->
                                    <dialog id="edit_user_<?= $u['id'] ?>" class="modal-dialog">
                                        <form method="post" class="p-6">
                                            <input type="hidden" name="action" value="forum_user_edit">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            
                                            <h3 class="text-lg font-bold mb-4">Benutzer bearbeiten: <?= htmlspecialchars($u['username']) ?></h3>
                                            
                                            <div class="form-group mb-4">
                                                <label class="font-semibold block mb-1">Forum-Rolle:</label>
                                                <select name="role" class="input-field w-full">
                                                    <option value="forum_user" <?= ($u['role'] ?? '') === 'forum_user' ? 'selected' : '' ?>>Normales Foren-Mitglied</option>
                                                    <option value="forum_moderator" <?= ($u['role'] ?? '') === 'forum_moderator' ? 'selected' : '' ?>>Forum-Moderator</option>
                                                    <option value="forum_admin" <?= ($u['role'] ?? '') === 'forum_admin' ? 'selected' : '' ?>>Forum-Administrator</option>
                                                </select>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="font-semibold block mb-1">Status:</label>
                                                <select name="status" class="input-field w-full">
                                                    <option value="active" <?= ($u['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktiv</option>
                                                    <option value="pending_approval" <?= ($u['status'] ?? '') === 'pending_approval' ? 'selected' : '' ?>>Wartet auf Freischaltung</option>
                                                    <option value="banned" <?= ($u['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Gesperrt / Gebannt</option>
                                                </select>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="font-semibold block mb-1">Sperr-Grund (optional):</label>
                                                <input type="text" name="ban_reason" value="<?= htmlspecialchars($u['ban_reason'] ?? '') ?>" class="input-field w-full">
                                            </div>

                                            <div class="flex justify-end gap-2 mt-6">
                                                <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                                                <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: SPEICHER-ENGINE & MIGRATION -->
    <div id="fusers-storage" class="tab-pane mt-8 pt-6 border-t">
        <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="material-icons text-primary">storage</span>
            Speicher-Architektur & 1-Klick-Migration
        </h3>
        <p class="text-sm text-muted mb-6">
            WeaveCMS erlaubt es dir, die Forum-Benutzer flexibel entweder als Flat-File JSON (standardmäßig in <code>data/forum_users.json</code>)
            oder in einer indizierten SQLite-Datenbank (in <code>data/forum.sqlite</code>) zu speichern.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="card p-4 <?= $currentDriver === 'json' ? 'border-primary shadow' : '' ?>">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-bold flex items-center gap-2">
                        <span class="material-icons">description</span> Flat-File JSON
                    </h4>
                    <?php if ($currentDriver === 'json'): ?>
                        <span class="badge badge-success">Aktiv</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-muted mb-3">
                    Pfad: <code><?= htmlspecialchars($jsonFile) ?></code><br>
                    Status: <?= file_exists($jsonFile) ? 'Existiert (' . filesize($jsonFile) . ' Bytes)' : 'Noch nicht erstellt' ?>
                </p>
                <?php if ($currentDriver !== 'json'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="forum_migrate_storage">
                        <input type="hidden" name="target_driver" value="json">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-outline btn-sm w-full">
                            Daten nach JSON migrieren & aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card p-4 <?= $currentDriver === 'sqlite' ? 'border-primary shadow' : '' ?>">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-bold flex items-center gap-2">
                        <span class="material-icons">storage</span> SQLite Datenbank
                    </h4>
                    <?php if ($currentDriver === 'sqlite'): ?>
                        <span class="badge badge-success">Aktiv</span>
                    <?php elseif (!$isSqliteSupported): ?>
                        <span class="badge badge-warning">PHP pdo_sqlite fehlt</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-muted mb-3">
                    Pfad: <code><?= htmlspecialchars($sqliteFile) ?></code><br>
                    Status: <?= file_exists($sqliteFile) ? 'Existiert (' . filesize($sqliteFile) . ' Bytes)' : 'Wird bei Aktivierung automatisch erstellt' ?>
                </p>

                <?php if (!$isSqliteSupported): ?>
                    <div class="alert alert-warning text-xs mb-3">
                        <strong>Hinweis:</strong> Die PHP-Erweiterung <code>pdo_sqlite</code> ist derzeit nicht geladen.
                        Bitte in der <code>php.ini</code> die Zeile <code>extension=pdo_sqlite</code> aktivieren und den Webserver neu starten.
                    </div>
                <?php endif; ?>

                <?php if ($currentDriver !== 'sqlite'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="forum_migrate_storage">
                        <input type="hidden" name="target_driver" value="sqlite">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-primary btn-sm w-full" <?= !$isSqliteSupported ? 'disabled' : '' ?>>
                            Daten nach SQLite migrieren & aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-4 rounded border bg-light text-sm text-muted">
            <div class="font-bold flex items-center gap-2 text-dark mb-1">
                <span class="material-icons text-primary text-base">info</span>
                Warum werden bei SQLite keine Datenbank-Zugangsdaten (Host, Benutzer, Passwort) abgefragt?
            </div>
            <p class="mb-0">
                <strong>SQLite</strong> ist eine serverlose, dateibasierte SQL-Datenbank (Zero-Configuration). 
                Im Unterschied zu MySQL oder MariaDB benötigt SQLite keinen separaten Datenbank-Serverdienst und keine Zugangsdaten. 
                Alle Tabellen, Benutzerkonten und Indizes werden direkt, isoliert und transaktionssicher in der Datei 
                <code><?= htmlspecialchars($sqliteFile) ?></code> gespeichert. Die Datenbankdatei und alle benötigten Tabellen werden beim Klick auf die Migration vollautomatisch angelegt.
            </p>
        </div>
    </div>

    <!-- TAB 3: REGISTRIERUNGS-EINSTELLUNGEN -->
    <div id="fusers-settings" class="tab-pane mt-8 pt-6 border-t">
        <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="material-icons text-primary">tune</span>
            Registrierungs- & Sicherheits-Konfiguration
        </h3>
        
        <?php $cfg = PluginManager::getPluginConfig('weave-forum-users', $data); ?>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="forum_users_save_config">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label class="font-semibold block mb-1">Registrierungs-Modus:</label>
                <select name="registration_mode" class="input-field" style="max-width: 400px;">
                    <option value="open" <?= ($cfg['registration_mode'] ?? 'open') === 'open' ? 'selected' : '' ?>>
                        Offen: Benutzer werden sofort nach Registrierung freigeschaltet
                    </option>
                    <option value="approval" <?= ($cfg['registration_mode'] ?? 'open') === 'approval' ? 'selected' : '' ?>>
                        Freigabe erforderlich: Neue Konten müssen vor dem Schreiben von einem Admin bestätigt werden
                    </option>
                    <option value="closed" <?= ($cfg['registration_mode'] ?? 'open') === 'closed' ? 'selected' : '' ?>>
                        Geschlossen: Keine Neuregistrierungen möglich
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label class="font-semibold block mb-1">Passwort-Mindestlänge:</label>
                <input type="number" name="min_password_len" min="6" max="32" 
                       value="<?= (int)($cfg['min_password_len'] ?? 8) ?>" class="input-field" style="max-width: 150px;">
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="allow_avatars" value="1" <?= !empty($cfg['allow_avatars'] ?? 1) ? 'checked' : '' ?>>
                    <span>Benutzern das Hochladen / Ändern von Avataren und Signaturen erlauben</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                Einstellungen speichern
            </button>
        </form>
    </div>
</div>
