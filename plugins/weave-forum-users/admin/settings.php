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
        $aliasMatch = str_contains(mb_strtolower($u['alias'] ?? ''), $q);
        $emailMatch = str_contains(mb_strtolower($u['email'] ?? ''), $q);
        return $nameMatch || $aliasMatch || $emailMatch;
    }
    return true;
});

$msg = $_GET['fuser_msg'] ?? '';
$err = $_GET['fuser_err'] ?? '';
$migratedCount = isset($_GET['fuser_count']) ? (int)$_GET['fuser_count'] : null;
$isSqliteSupported = ForumUserManager::isSqliteSupported();
?>

<style>
/* Eigenständiges Admin-Styling für WeaveCMS Forum-Benutzerverwaltung */
.fusers-admin-wrap {
    color: var(--text-primary, #1e293b);
}
.fusers-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    padding-bottom: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}
.fusers-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0 0 4px 0;
    color: var(--text-primary, #0f172a);
}
.fusers-header-sub {
    font-size: 0.875rem;
    color: var(--text-secondary, #64748b);
    margin: 0;
}
.fusers-badges-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.fusers-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.3;
}
.fusers-pill-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.fusers-pill-primary { background: rgba(46, 98, 72, 0.12); color: var(--primary, #2e6248); border: 1px solid rgba(46, 98, 72, 0.25); }
.fusers-pill-admin { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.fusers-pill-mod { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.fusers-pill-user { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.fusers-pill-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fusers-pill-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.fusers-pill-banned { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

.fusers-subnav {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    padding-bottom: 14px;
    margin-bottom: 22px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}
.fusers-sync-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px 20px;
    margin-bottom: 22px;
    border-radius: 12px;
    background: linear-gradient(to right, rgba(46, 98, 72, 0.06), rgba(46, 98, 72, 0.02));
    border: 1px solid rgba(46, 98, 72, 0.25);
}
.fusers-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 14px 16px;
    margin-bottom: 18px;
    background: var(--bg-body, #f8fafc);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
}
.fusers-input, .fusers-select {
    padding: 9px 13px;
    border: 1px solid var(--border-color, #cbd5e1);
    border-radius: 8px;
    font-size: 13.5px;
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #0f172a);
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.fusers-input:focus, .fusers-select:focus {
    border-color: var(--primary, #2e6248);
    box-shadow: 0 0 0 3px rgba(46, 98, 72, 0.12);
}

.fusers-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    background: var(--bg-card, #ffffff);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.fusers-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 13.5px;
}
.fusers-table thead th {
    background: var(--bg-body, #f8fafc);
    color: var(--text-secondary, #475569);
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 13px 16px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    white-space: nowrap;
}
.fusers-table tbody tr {
    border-bottom: 1px solid var(--border-color, #f1f5f9);
    transition: background 0.12s ease;
}
.fusers-table tbody tr:last-child {
    border-bottom: none;
}
.fusers-table tbody tr:hover {
    background: rgba(46, 98, 72, 0.025);
}
.fusers-table td {
    padding: 14px 16px;
    vertical-align: middle;
}
.fusers-user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.fusers-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary, #2e6248), #1e4631);
    color: #ffffff;
    font-weight: 800;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(46, 98, 72, 0.2);
}
.fusers-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.fusers-icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--border-color, #cbd5e1);
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #334155);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}
.fusers-icon-btn:hover {
    border-color: var(--primary, #2e6248);
    color: var(--primary, #2e6248);
    background: rgba(46, 98, 72, 0.06);
}
.fusers-icon-btn.danger:hover {
    border-color: #ef4444;
    color: #dc2626;
    background: #fef2f2;
}

/* Dialog Modal */
dialog.fusers-dialog {
    border: none;
    border-radius: 16px;
    padding: 0;
    width: 92%;
    max-width: 500px;
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #0f172a);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(15, 23, 42, 0.08);
    margin: auto;
}
dialog.fusers-dialog::backdrop {
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(3px);
}
.fusers-dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    background: var(--bg-body, #f8fafc);
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}
.fusers-dialog-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-primary, #0f172a);
}
.fusers-dialog-body {
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.fusers-field label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-primary, #334155);
    margin-bottom: 6px;
}
.fusers-dialog-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 22px;
    background: var(--bg-body, #f8fafc);
    border-top: 1px solid var(--border-color, #e2e8f0);
}
.fusers-storage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.fusers-storage-card {
    padding: 18px;
    border-radius: 12px;
    border: 1px solid var(--border-color, #e2e8f0);
    background: var(--bg-card, #ffffff);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.fusers-storage-card.active {
    border: 2px solid var(--primary, #2e6248);
    box-shadow: 0 4px 14px rgba(46, 98, 72, 0.08);
}
</style>

<div class="card fusers-admin-wrap" style="padding: 24px; margin-bottom: 24px;">
    <div class="fusers-header">
        <div>
            <h2 class="fusers-header-title">
                <span class="material-icons" style="color: var(--primary, #2e6248); font-size: 26px;">group</span>
                <span>Forum-Benutzerverwaltung</span>
            </h2>
            <p class="fusers-header-sub">
                Verwalte registrierte Forumsmitglieder, Rollenberechtigungen, Alias-Namen, Status und die Speicher-Engine.
            </p>
        </div>
        <div class="fusers-badges-row">
            <span class="fusers-pill fusers-pill-info">
                <span class="material-icons" style="font-size: 14px;">people</span>
                <?= count($users) ?> <?= count($users) === 1 ? 'Mitglied' : 'Mitglieder' ?>
            </span>
            <span class="fusers-pill fusers-pill-primary">
                <span class="material-icons" style="font-size: 14px;">storage</span>
                Speicher: <?= strtoupper($currentDriver) ?>
            </span>
        </div>
    </div>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#fef2f2; color:#991b1b; border:1px solid #fecaca;">
            <span class="material-icons">error</span>
            <span><?= htmlspecialchars($err) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'migrated_sqlite'): ?>
        <div class="alert alert-success" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            <span class="material-icons">check_circle</span>
            <span><?= ($migratedCount !== null && $migratedCount > 0)
                ? "Erfolgreich {$migratedCount} Benutzer nach SQLite migriert! Die SQLite-Datenbank (data/forum.sqlite) ist aktiv."
                : "SQLite-Datenbank (data/forum.sqlite) und Tabellen wurden erfolgreich initialisiert und als aktiver Speichertreiber aktiviert!" ?></span>
        </div>
    <?php elseif ($msg === 'migrated_json'): ?>
        <div class="alert alert-success" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            <span class="material-icons">check_circle</span>
            <span>Erfolgreich nach Flat-File JSON migriert!</span>
        </div>
    <?php elseif ($msg === 'user_updated'): ?>
        <div class="alert alert-success" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            <span class="material-icons">check_circle</span>
            <span>Benutzerkonto und Einstellungen wurden erfolgreich aktualisiert.</span>
        </div>
    <?php elseif ($msg === 'user_anonymized'): ?>
        <div class="alert alert-warning" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#fffbeb; color:#92400e; border:1px solid #fde68a;">
            <span class="material-icons">info</span>
            <span>Benutzerkonto wurde DSGVO-konform anonymisiert und unkenntlich gemacht.</span>
        </div>
    <?php elseif ($msg === 'user_deleted'): ?>
        <div class="alert alert-success" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            <span class="material-icons">delete</span>
            <span>Benutzerkonto wurde endgültig gelöscht.</span>
        </div>
    <?php elseif ($msg === 'admin_synced'): ?>
        <div class="alert alert-success" style="margin-bottom: 18px; display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            <span class="material-icons">verified_user</span>
            <div>
                <strong>Seiten-Administrator erfolgreich als Forum-Admin synchronisiert!</strong><br>
                Benutzerkonto <em><?= htmlspecialchars($_GET['fuser_name'] ?? '') ?></em> ist nun als <strong>Forum-Administrator (forum_admin)</strong> in der aktiven Speicher-Engine (<?= strtoupper($currentDriver) ?>) hinterlegt.
            </div>
        </div>
    <?php endif; ?>

    <!-- Sub-Nav / Quick Jump -->
    <div class="fusers-subnav">
        <a href="#fusers-list" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
            <span class="material-icons" style="font-size: 16px;">manage_accounts</span> Mitgliederliste
        </a>
        <a href="#fusers-storage" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
            <span class="material-icons" style="font-size: 16px;">storage</span> Speicher-Engine &amp; Migration
        </a>
        <a href="#fusers-settings" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
            <span class="material-icons" style="font-size: 16px;">tune</span> Registrierungs-Optionen
        </a>
    </div>

    <!-- TAB 1: USER LIST -->
    <div id="fusers-list">
        <!-- Seiten-Administrator Sync Card -->
        <?php
        $siteAdminName = !empty($data['texts']['contact_name']) ? trim($data['texts']['contact_name']) : 'Administrator';
        $siteAdminEmail = !empty($data['texts']['contact_email']) ? trim($data['texts']['contact_email']) : 'admin@weavecms.local';
        $siteAdminAccount = ForumUserManager::getSiteAdminUser($data);
        ?>
        <div class="fusers-sync-banner">
            <div style="display:flex; align-items:flex-start; gap:14px; max-width: 720px;">
                <div style="width:42px; height:42px; border-radius:10px; background:rgba(46, 98, 72, 0.14); color:var(--primary, #2e6248); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="material-icons" style="font-size: 24px;">admin_panel_settings</span>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                        <strong style="font-size: 14px; color: var(--text-primary, #0f172a);">Seiten-Administrator als Forum-Administrator</strong>
                        <?php if ($siteAdminAccount): ?>
                            <span class="fusers-pill fusers-pill-active" style="font-size:11px; padding:2px 8px;">✓ Aktiv im Forum registriert</span>
                        <?php else: ?>
                            <span class="fusers-pill fusers-pill-pending" style="font-size:11px; padding:2px 8px;">Noch nicht übertragen</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: 12.5px; color: var(--text-secondary, #64748b); margin: 0; line-height: 1.45;">
                        Aktueller Seiten-Admin: <strong><?= htmlspecialchars($siteAdminName) ?></strong> (<?= htmlspecialchars($siteAdminEmail) ?>).
                        Mit einem Klick wird dieses Administratorkonto in die Forendatenbank (<?= strtoupper($currentDriver) ?>) mit der Rolle <code>forum_admin</code> eingetragen oder aktualisiert.
                    </p>
                </div>
            </div>
            <div>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="forum_users_sync_site_admin">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
                        <span class="material-icons" style="font-size: 16px;">sync</span>
                        <span><?= $siteAdminAccount ? 'Als Forum-Admin synchronisieren' : 'Seiten-Admin jetzt als Forum-Admin anlegen' ?></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Filter & Search -->
        <form method="get" class="fusers-filter-bar">
            <input type="hidden" name="tab" value="tab-plugins">
            <input type="hidden" name="plugin_settings" value="weave-forum-users">
            
            <div style="position:relative; flex:1; min-width:220px; max-width:340px;">
                <span class="material-icons" style="position:absolute; left:11px; top:50%; transform:translateY(-50%); font-size:18px; color:#94a3b8; pointer-events:none;">search</span>
                <input type="text" name="fuser_q" value="<?= htmlspecialchars($searchQuery) ?>" 
                       placeholder="Benutzername, Alias oder E-Mail..." class="fusers-input" style="width:100%; padding-left:36px;">
            </div>
            
            <select name="fuser_role" class="fusers-select" style="min-width: 190px;">
                <option value="">Alle Rollen anzeigen</option>
                <option value="forum_admin" <?= $roleFilter === 'forum_admin' ? 'selected' : '' ?>>Forum-Administrator</option>
                <option value="forum_moderator" <?= $roleFilter === 'forum_moderator' ? 'selected' : '' ?>>Forum-Moderator</option>
                <option value="forum_user" <?= $roleFilter === 'forum_user' ? 'selected' : '' ?>>Normaler Benutzer</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px;">
                <span class="material-icons" style="font-size: 16px;">filter_list</span>
                <span>Filtern</span>
            </button>
            <?php if (!empty($searchQuery) || !empty($roleFilter)): ?>
                <a href="?tab=tab-plugins&plugin_settings=weave-forum-users" class="btn btn-outline btn-sm" style="padding:9px 14px;">Zurücksetzen</a>
            <?php endif; ?>
        </form>

        <!-- Modern User Table -->
        <div class="fusers-table-wrap">
            <table class="fusers-table">
                <thead>
                    <tr>
                        <th>Benutzer / Alias</th>
                        <th>E-Mail-Adresse</th>
                        <th>Rolle</th>
                        <th>Status</th>
                        <th>Aktivität</th>
                        <th>Registriert</th>
                        <th style="text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredUsers)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px 20px; color: var(--text-secondary, #64748b);">
                                Keine Foren-Benutzer gefunden.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredUsers as $u): ?>
                            <?php $modalId = 'edit_user_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $u['id']); ?>
                            <?php $delFormId = 'del_user_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $u['id']); ?>
                            <tr>
                                <td>
                                    <div class="fusers-user-cell">
                                        <div class="fusers-avatar">
                                            <?= strtoupper(mb_substr(ForumUserManager::getDisplayName($u), 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-primary, #0f172a); font-size: 14px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                <span><?= htmlspecialchars($u['username']) ?></span>
                                                <?php if (!empty($u['alias']) && ForumUserManager::canUseAlias($u)): ?>
                                                    <span class="fusers-pill fusers-pill-info" style="font-size: 11px; padding: 1px 8px;" title=" Öffentlicher Alias im Forum">
                                                        Alias: <?= htmlspecialchars($u['alias']) ?><?= !empty($u['use_alias_only']) ? ' (aktiv)' : '' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 11.5px; color: var(--text-secondary, #64748b); margin-top: 2px;">
                                                ID: <code><?= htmlspecialchars($u['id']) ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--text-secondary, #334155); font-weight: 500;">
                                    <?= htmlspecialchars($u['email']) ?>
                                </td>
                                <td>
                                    <?php if (($u['role'] ?? '') === 'forum_admin'): ?>
                                        <span class="fusers-pill fusers-pill-admin">
                                            <span class="material-icons" style="font-size:13px;">shield</span> Admin
                                        </span>
                                    <?php elseif (($u['role'] ?? '') === 'forum_moderator'): ?>
                                        <span class="fusers-pill fusers-pill-mod">
                                            <span class="material-icons" style="font-size:13px;">verified_user</span> Moderator
                                        </span>
                                    <?php else: ?>
                                        <span class="fusers-pill fusers-pill-user">Mitglied</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($u['status'] ?? '') === 'active'): ?>
                                        <span class="fusers-pill fusers-pill-active">● Aktiv</span>
                                    <?php elseif (($u['status'] ?? '') === 'pending_approval'): ?>
                                        <span class="fusers-pill fusers-pill-pending">⏳ Wartet</span>
                                    <?php elseif (($u['status'] ?? '') === 'banned'): ?>
                                        <span class="fusers-pill fusers-pill-banned">⛔ Gesperrt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 12px; line-height: 1.4; color: var(--text-secondary, #475569);">
                                        <strong><?= (int)($u['topic_count'] ?? 0) ?></strong> Themen<br>
                                        <strong><?= (int)($u['post_count'] ?? 0) ?></strong> Beiträge
                                    </div>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-secondary, #64748b); white-space: nowrap;">
                                    <?= htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)) ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="fusers-actions">
                                        <!-- Rolle, Alias & Status Dialog Trigger -->
                                        <button type="button" class="fusers-icon-btn" title="Benutzer bearbeiten"
                                                onclick="document.getElementById('<?= $modalId ?>').showModal()">
                                            <span class="material-icons" style="font-size: 17px;">edit</span>
                                        </button>

                                        <!-- DSGVO Export -->
                                        <a href="?tab=tab-plugins&plugin_settings=weave-forum-users&fuser_action=export&id=<?= urlencode($u['id']) ?>" 
                                           class="fusers-icon-btn" title="DSGVO-Datenexport (JSON)">
                                            <span class="material-icons" style="font-size: 17px;">download</span>
                                        </a>

                                        <!-- Löschen / Anonymisieren -->
                                        <button type="button" class="fusers-icon-btn danger" title="Konto anonymisieren / sperren"
                                                onclick="if(confirm('Möchtest du dieses Konto wirklich DSGVO-konform anonymisieren und sperren?')) document.getElementById('<?= $delFormId ?>').submit()">
                                            <span class="material-icons" style="font-size: 17px;">delete</span>
                                        </button>
                                        <form id="<?= $delFormId ?>" method="post" style="display:none;">
                                            <input type="hidden" name="action" value="forum_user_delete">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        </form>
                                    </div>

                                    <!-- Modal zur Bearbeitung -->
                                    <dialog id="<?= $modalId ?>" class="fusers-dialog" style="text-align: left;">
                                        <form method="post" style="margin:0;">
                                            <input type="hidden" name="action" value="forum_user_edit">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            
                                            <div class="fusers-dialog-header">
                                                <h3>
                                                    <span class="material-icons" style="color: var(--primary, #2e6248); font-size: 20px;">manage_accounts</span>
                                                    <span>Benutzer bearbeiten: <?= htmlspecialchars($u['username']) ?></span>
                                                </h3>
                                                <button type="button" class="fusers-icon-btn" style="width:28px; height:28px;" onclick="this.closest('dialog').close()">
                                                    <span class="material-icons" style="font-size: 16px;">close</span>
                                                </button>
                                            </div>
                                            
                                            <div class="fusers-dialog-body">
                                                <div class="fusers-field">
                                                    <label>Forum-Rolle:</label>
                                                    <select name="role" class="fusers-select" style="width: 100%;" onchange="const box=this.closest('form').querySelector('.alias-admin-box'); if(box) box.style.display=(this.value==='forum_admin'||this.value==='forum_moderator')?'block':'none';">
                                                        <option value="forum_user" <?= ($u['role'] ?? '') === 'forum_user' ? 'selected' : '' ?>>Normales Foren-Mitglied</option>
                                                        <option value="forum_moderator" <?= ($u['role'] ?? '') === 'forum_moderator' ? 'selected' : '' ?>>Forum-Moderator</option>
                                                        <option value="forum_admin" <?= ($u['role'] ?? '') === 'forum_admin' ? 'selected' : '' ?>>Forum-Administrator</option>
                                                    </select>
                                                </div>

                                                <!-- Alias-Feld für Admins & Moderatoren -->
                                                <div class="alias-admin-box" style="display: <?= ForumUserManager::canUseAlias($u) ? 'block' : 'none' ?>; padding: 14px; border-radius: 10px; background: rgba(46, 98, 72, 0.05); border: 1px solid rgba(46, 98, 72, 0.2);">
                                                    <div class="fusers-field" style="margin-bottom: 10px;">
                                                        <label style="color: var(--primary, #2e6248);">Alias / Anzeigename (für Admin &amp; Moderator):</label>
                                                        <input type="text" name="alias" value="<?= htmlspecialchars($u['alias'] ?? '') ?>" placeholder="z. B. Forenleitung, Admin-Team..." class="fusers-input" style="width: 100%;">
                                                    </div>
                                                    <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer; font-weight:600; color: var(--text-primary, #334155);">
                                                        <input type="checkbox" name="use_alias_only" value="1" <?= !empty($u['use_alias_only']) ? 'checked' : '' ?>>
                                                        <span>Im Forum ausschließlich diesen Alias statt des Benutzernamens anzeigen</span>
                                                    </label>
                                                </div>

                                                <div class="fusers-field">
                                                    <label>Konto-Status:</label>
                                                    <select name="status" class="fusers-select" style="width: 100%;">
                                                        <option value="active" <?= ($u['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktiv (Freigeschaltet)</option>
                                                        <option value="pending_approval" <?= ($u['status'] ?? '') === 'pending_approval' ? 'selected' : '' ?>>Wartet auf Freischaltung</option>
                                                        <option value="banned" <?= ($u['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Gesperrt / Gebannt</option>
                                                    </select>
                                                </div>

                                                <div class="fusers-field">
                                                    <label>Sperr-Grund (optional):</label>
                                                    <input type="text" name="ban_reason" value="<?= htmlspecialchars($u['ban_reason'] ?? '') ?>" placeholder="Wird dem Benutzer bei Sperrung angezeigt..." class="fusers-input" style="width: 100%;">
                                                </div>
                                            </div>

                                            <div class="fusers-dialog-footer">
                                                <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                                                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                                                    <span class="material-icons" style="font-size:16px;">save</span>
                                                    <span>Änderungen speichern</span>
                                                </button>
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
    <div id="fusers-storage" style="margin-top: 34px; padding-top: 26px; border-top: 1px solid var(--border-color, #e2e8f0);">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="color: var(--primary, #2e6248);">storage</span>
            <span>Speicher-Architektur &amp; 1-Klick-Migration</span>
        </h3>
        <p style="font-size: 13.5px; color: var(--text-secondary, #64748b); margin: 0 0 20px 0;">
            WeaveCMS erlaubt es dir, die Forum-Benutzer flexibel entweder als Flat-File JSON (in <code>data/forum_users.json</code>),
            in einer indizierten SQLite-Datenbank (in <code>data/forum.sqlite</code>) oder in MySQL/MariaDB zu speichern.
        </p>

        <div class="fusers-storage-grid">
            <div class="fusers-storage-card <?= $currentDriver === 'json' ? 'active' : '' ?>">
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <h4 style="margin:0; font-size:15px; font-weight:700; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:19px; color:var(--primary);">description</span> Flat-File JSON
                        </h4>
                        <?php if ($currentDriver === 'json'): ?>
                            <span class="fusers-pill fusers-pill-active" style="font-size:11px;">Aktiv</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:12px; color:var(--text-secondary, #64748b); line-height:1.5; margin:0 0 14px 0;">
                        Pfad: <code><?= htmlspecialchars(basename($jsonFile)) ?></code><br>
                        Status: <?= file_exists($jsonFile) ? 'Existiert (' . filesize($jsonFile) . ' Bytes)' : 'Noch nicht erstellt' ?>
                    </p>
                </div>
                <?php if ($currentDriver !== 'json'): ?>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="forum_migrate_storage">
                        <input type="hidden" name="target_driver" value="json">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">
                            Nach JSON migrieren &amp; aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="fusers-storage-card <?= $currentDriver === 'mysql' ? 'active' : '' ?>">
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <h4 style="margin:0; font-size:15px; font-weight:700; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:19px; color:var(--primary);">dns</span> MySQL / MariaDB
                        </h4>
                        <?php if ($currentDriver === 'mysql'): ?>
                            <span class="fusers-pill fusers-pill-active" style="font-size:11px;">Aktiv</span>
                        <?php elseif (!ForumUserManager::isMysqlSupported()): ?>
                            <span class="fusers-pill fusers-pill-pending" style="font-size:11px;">pdo_mysql fehlt</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:12px; color:var(--text-secondary, #64748b); line-height:1.5; margin:0 0 14px 0;">
                        Tabelle: <code>weave_forum_users</code><br>
                        Status: <?= ForumUserManager::isMysqlSupported() ? 'Bereit & Tabellen aktiv' : 'pdo_mysql aktivieren' ?>
                    </p>
                </div>
                <?php if ($currentDriver !== 'mysql'): ?>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="forum_migrate_storage">
                        <input type="hidden" name="target_driver" value="mysql">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;" <?= !ForumUserManager::isMysqlSupported() ? 'disabled' : '' ?>>
                            Nach MySQL migrieren &amp; aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="fusers-storage-card <?= $currentDriver === 'sqlite' ? 'active' : '' ?>">
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <h4 style="margin:0; font-size:15px; font-weight:700; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:19px; color:var(--primary);">storage</span> SQLite Datenbank
                        </h4>
                        <?php if ($currentDriver === 'sqlite'): ?>
                            <span class="fusers-pill fusers-pill-active" style="font-size:11px;">Aktiv</span>
                        <?php elseif (!$isSqliteSupported): ?>
                            <span class="fusers-pill fusers-pill-pending" style="font-size:11px;">pdo_sqlite fehlt</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:12px; color:var(--text-secondary, #64748b); line-height:1.5; margin:0 0 14px 0;">
                        Datei: <code>data/forum.sqlite</code><br>
                        Status: <?= file_exists($sqliteFile) ? 'Existiert (' . filesize($sqliteFile) . ' Bytes)' : 'Wird automatisch erstellt' ?>
                    </p>
                </div>
                <?php if ($currentDriver !== 'sqlite'): ?>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="forum_migrate_storage">
                        <input type="hidden" name="target_driver" value="sqlite">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;" <?= !$isSqliteSupported ? 'disabled' : '' ?>>
                            Nach SQLite migrieren &amp; aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 3: REGISTRIERUNGS-EINSTELLUNGEN -->
    <div id="fusers-settings" style="margin-top: 34px; padding-top: 26px; border-top: 1px solid var(--border-color, #e2e8f0);">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="color: var(--primary, #2e6248);">tune</span>
            <span>Registrierungs- &amp; Sicherheits-Konfiguration</span>
        </h3>
        
        <?php $cfg = PluginManager::getPluginConfig('weave-forum-users', $data); ?>
        <form method="post" style="display:flex; flex-direction:column; gap:16px; max-width: 560px;">
            <input type="hidden" name="action" value="forum_users_save_config">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="fusers-field">
                <label>Registrierungs-Modus:</label>
                <select name="registration_mode" class="fusers-select" style="width: 100%;">
                    <option value="open" <?= ($cfg['registration_mode'] ?? 'open') === 'open' ? 'selected' : '' ?>>
                        Offen: Benutzer werden sofort nach Registrierung freigeschaltet
                    </option>
                    <option value="approval" <?= ($cfg['registration_mode'] ?? 'open') === 'approval' ? 'selected' : '' ?>>
                        Freigabe erforderlich: Neue Konten müssen durch einen Admin bestätigt werden
                    </option>
                    <option value="closed" <?= ($cfg['registration_mode'] ?? 'open') === 'closed' ? 'selected' : '' ?>>
                        Geschlossen: Keine Neuregistrierungen möglich
                    </option>
                </select>
            </div>

            <div class="fusers-field">
                <label>Passwort-Mindestlänge:</label>
                <input type="number" name="min_password_len" min="6" max="32" 
                       value="<?= (int)($cfg['min_password_len'] ?? 8) ?>" class="fusers-input" style="width: 140px;">
            </div>

            <div>
                <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer; font-weight:600;">
                    <input type="checkbox" name="allow_avatars" value="1" <?= !empty($cfg['allow_avatars'] ?? 1) ? 'checked' : '' ?>>
                    <span>Benutzern das Bearbeiten von Biografie und Signaturen erlauben</span>
                </label>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size: 17px;">save</span>
                    <span>Einstellungen speichern</span>
                </button>
            </div>
        </form>
    </div>
</div>
