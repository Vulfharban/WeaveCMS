<?php
/**
 * Admin-Ansicht: Forum-Verwaltung (weave-forum)
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

require_once dirname(__DIR__) . '/ForumManager.php';
if (file_exists(dirname(__DIR__, 2) . '/weave-forum-users/ForumUserManager.php')) {
    require_once dirname(__DIR__, 2) . '/weave-forum-users/ForumUserManager.php';
}

$categories = ForumManager::getCategories();
$boards = ForumManager::getBoards();
$reports = ForumManager::getReports('open');
$auditLogs = ForumManager::getAuditLogs(30);

$currentDriver = ForumManager::getStorageDriver();
$jsonFile = ForumManager::getJsonFilePath();
$sqliteFile = ForumManager::getSqliteFilePath();

$cfg = PluginManager::getPluginConfig('weave-forum', $data);
$msg = $_GET['forum_msg'] ?? '';
$err = $_GET['forum_err'] ?? '';
$isSqliteSupported = ForumManager::isSqliteSupported();
?>

<div class="card p-6 mb-6">
    <div class="flex items-center justify-between border-b pb-4 mb-4">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span class="material-icons text-primary">forum</span>
                Forum-Administration
            </h2>
            <p class="text-sm text-muted">
                Verwalte Bereiche, Unterforen, Forenregeln, Meldungen, Moderations-Queue und Speicher-Engines.
            </p>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-info">
                <?= count($categories) ?> Kategorien / <?= count($boards) ?> Foren
            </span>
            <?php if (!empty($reports)): ?>
                <span class="badge badge-danger flex items-center gap-1">
                    <span class="material-icons text-xs">warning</span> <?= count($reports) ?> Meldungen
                </span>
            <?php endif; ?>
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
            Foreninhalte erfolgreich nach SQLite migriert und aktiviert!
        </div>
    <?php elseif ($msg === 'migrated_json'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            Foreninhalte erfolgreich nach Flat-File JSON migriert und aktiviert!
        </div>
    <?php elseif ($msg === 'cat_saved'): ?>
        <div class="alert alert-success mb-4">Kategorie erfolgreich gespeichert.</div>
    <?php elseif ($msg === 'cat_deleted'): ?>
        <div class="alert alert-success mb-4">Kategorie wurde gelöscht.</div>
    <?php elseif ($msg === 'board_saved'): ?>
        <div class="alert alert-success mb-4">Forum erfolgreich gespeichert.</div>
    <?php elseif ($msg === 'board_deleted'): ?>
        <div class="alert alert-success mb-4">Forum wurde gelöscht.</div>
    <?php elseif ($msg === 'report_resolved'): ?>
        <div class="alert alert-success mb-4">Meldung wurde bearbeitet.</div>
    <?php elseif ($msg === 'settings_saved'): ?>
        <div class="alert alert-success mb-4">Foren-Einstellungen wurden gespeichert.</div>
    <?php endif; ?>

    <!-- Sub-Tabs -->
    <div class="tabs-nav mb-6 flex gap-2 border-b">
        <a href="#forum-structure" class="btn btn-sm btn-outline active">Bereiche & Foren</a>
        <a href="#forum-reports" class="btn btn-sm btn-outline">
            Moderation & Meldungen <?php if (!empty($reports)): ?>(<?= count($reports) ?>)<?php endif; ?>
        </a>
        <a href="#forum-settings" class="btn btn-sm btn-outline">Allgemeine Einstellungen</a>
        <a href="#forum-storage" class="btn btn-sm btn-outline">Speicher & Migration</a>
    </div>

    <!-- TAB 1: BEREICHE & FOREN STRUKTUR -->
    <div id="forum-structure" class="tab-pane">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Foren-Hierarchie</h3>
            <button type="button" class="btn btn-primary btn-sm flex items-center gap-1"
                    onclick="document.getElementById('modal_add_cat').showModal()">
                <span class="material-icons text-sm">add</span> Neue Kategorie anlegen
            </button>
        </div>

        <?php if (empty($categories)): ?>
            <div class="p-6 text-center text-muted border rounded">
                Noch keine Foren-Kategorien vorhanden.
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($categories as $cat): ?>
                    <?php 
                    $catBoards = array_filter($boards, fn($b) => ($b['category_id'] ?? '') === $cat['id']);
                    usort($catBoards, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
                    ?>
                    <div class="card p-4 border bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center justify-between border-b pb-3 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-primary"><?= htmlspecialchars($cat['icon'] ?? 'folder') ?></span>
                                <h4 class="font-bold text-base"><?= htmlspecialchars($cat['title']) ?></h4>
                                <span class="text-xs text-muted">(Sortierung: <?= (int)($cat['sort_order'] ?? 0) ?>)</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-outline btn-xs" 
                                        onclick="openAddBoardModal('<?= htmlspecialchars($cat['id']) ?>', '<?= htmlspecialchars(addslashes($cat['title'])) ?>')">
                                    <span class="material-icons text-xs">add</span> Forum hinzufügen
                                </button>
                                <button type="button" class="btn btn-outline btn-xs text-danger"
                                        onclick="if(confirm('Kategorie <?= htmlspecialchars(addslashes($cat['title'])) ?> wirklich löschen?')) document.getElementById('del_cat_<?= $cat['id'] ?>').submit()">
                                    <span class="material-icons text-xs">delete</span>
                                </button>
                                <form id="del_cat_<?= $cat['id'] ?>" method="post" style="display:none;">
                                    <input type="hidden" name="action" value="forum_delete_category">
                                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($cat['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                </form>
                            </div>
                        </div>

                        <?php if (empty($catBoards)): ?>
                            <div class="text-xs text-muted py-2 italic">Keine Foren in dieser Kategorie.</div>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($catBoards as $b): ?>
                                    <div class="p-3 bg-white dark:bg-gray-900 border rounded flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="material-icons text-muted"><?= htmlspecialchars($b['icon'] ?? 'forum') ?></span>
                                            <div>
                                                <div class="font-semibold text-sm">
                                                    <?= htmlspecialchars($b['title']) ?>
                                                    <?php if (!empty($b['is_locked'])): ?>
                                                        <span class="badge badge-warning text-xs">Gesperrt</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-muted"><?= htmlspecialchars($b['description'] ?? '') ?></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="badge badge-outline">Schreibrecht: <?= htmlspecialchars($b['min_role_write'] ?? 'forum_user') ?></span>
                                            <button type="button" class="btn btn-outline btn-xs text-danger"
                                                    onclick="if(confirm('Forum <?= htmlspecialchars(addslashes($b['title'])) ?> löschen?')) document.getElementById('del_b_<?= $b['id'] ?>').submit()">
                                                <span class="material-icons text-xs">delete</span>
                                            </button>
                                            <form id="del_b_<?= $b['id'] ?>" method="post" style="display:none;">
                                                <input type="hidden" name="action" value="forum_delete_board">
                                                <input type="hidden" name="board_id" value="<?= htmlspecialchars($b['id']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Modal: Add Category -->
        <dialog id="modal_add_cat" class="modal-dialog">
            <form method="post" class="p-6">
                <input type="hidden" name="action" value="forum_save_category">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <h3 class="text-lg font-bold mb-4">Neue Kategorie anlegen</h3>
                <div class="form-group mb-3">
                    <label class="font-semibold block mb-1">Titel:</label>
                    <input type="text" name="title" required class="input-field w-full" placeholder="z. B. Fachbereich Weidenflechten">
                </div>
                <div class="form-group mb-3">
                    <label class="font-semibold block mb-1">Beschreibung:</label>
                    <input type="text" name="description" class="input-field w-full">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="font-semibold block mb-1">Material-Icon:</label>
                        <input type="text" name="icon" value="folder" class="input-field w-full">
                    </div>
                    <div>
                        <label class="font-semibold block mb-1">Sortierung:</label>
                        <input type="number" name="sort_order" value="10" class="input-field w-full">
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Kategorie erstellen</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: Add Board -->
        <dialog id="modal_add_board" class="modal-dialog">
            <form method="post" class="p-6">
                <input type="hidden" name="action" value="forum_save_board">
                <input type="hidden" name="category_id" id="modal_board_cat_id" value="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <h3 class="text-lg font-bold mb-2">Neues Forum anlegen</h3>
                <p class="text-xs text-muted mb-4" id="modal_board_cat_label"></p>

                <div class="form-group mb-3">
                    <label class="font-semibold block mb-1">Forum-Titel:</label>
                    <input type="text" name="title" required class="input-field w-full" placeholder="z. B. Flechtwerkzeuge & Pflege">
                </div>
                <div class="form-group mb-3">
                    <label class="font-semibold block mb-1">Beschreibung:</label>
                    <textarea name="description" rows="2" class="input-field w-full"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="font-semibold block mb-1">Icon:</label>
                        <input type="text" name="icon" value="forum" class="input-field w-full">
                    </div>
                    <div>
                        <label class="font-semibold block mb-1">Sortierung:</label>
                        <input type="number" name="sort_order" value="10" class="input-field w-full">
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="font-semibold block mb-1">Erforderliche Rolle zum Verfassen:</label>
                    <select name="min_role_write" class="input-field w-full">
                        <option value="forum_user">Alle registrierten Benutzer (Standard)</option>
                        <option value="forum_moderator">Nur Moderatoren & Admins (z. B. für Ankündigungen)</option>
                        <option value="forum_admin">Nur Forum-Administratoren</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Forum erstellen</button>
                </div>
            </form>
        </dialog>

        <script>
        function openAddBoardModal(catId, catTitle) {
            document.getElementById('modal_board_cat_id').value = catId;
            document.getElementById('modal_board_cat_label').innerText = 'In Kategorie: ' + catTitle;
            document.getElementById('modal_add_board').showModal();
        }
        </script>
    </div>

    <!-- TAB 2: MODERATION & MELDUNGEN -->
    <div id="forum-reports" class="tab-pane mt-8 pt-6 border-t">
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
            <span class="material-icons text-danger">report</span>
            Gemeldete Beiträge (Moderations-Queue)
        </h3>

        <?php if (empty($reports)): ?>
            <div class="p-6 text-center text-muted border rounded mb-8">
                <span class="material-icons text-success text-3xl mb-1">check_circle</span>
                <p>Aktuell liegen keine offenen Beitragsmeldungen vor.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4 mb-8">
                <?php foreach ($reports as $r): ?>
                    <?php $reportedPost = ForumManager::getPost($r['post_id']); ?>
                    <div class="card p-4 border border-danger/30 bg-red-50/10">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="badge badge-danger">Meldung ID: <?= htmlspecialchars($r['id']) ?></span>
                                <span class="text-xs text-muted ml-2">Von <?= htmlspecialchars($r['reporter_name']) ?> am <?= htmlspecialchars($r['created_at']) ?></span>
                            </div>
                            <div class="flex gap-2">
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="forum_resolve_report">
                                    <input type="hidden" name="report_id" value="<?= htmlspecialchars($r['id']) ?>">
                                    <input type="hidden" name="resolution" value="dismissed">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" class="btn btn-outline btn-xs">Meldung verwerfen</button>
                                </form>
                                <form method="post" class="inline" onsubmit="return confirm('Beitrag wirklich löschen?')">
                                    <input type="hidden" name="action" value="forum_delete_reported_post">
                                    <input type="hidden" name="report_id" value="<?= htmlspecialchars($r['id']) ?>">
                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($r['post_id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Beitrag löschen</button>
                                </form>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-danger mb-2">
                            Grund der Meldung: <?= htmlspecialchars($r['reason']) ?>
                        </div>
                        <?php if ($reportedPost): ?>
                            <div class="p-3 bg-white dark:bg-gray-900 border rounded text-xs">
                                <strong>Beitrag von <?= htmlspecialchars($reportedPost['author_name']) ?>:</strong>
                                <p class="mt-1 text-muted"><?= nl2br(htmlspecialchars($reportedPost['content'])) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="text-xs text-muted italic">Der Beitrag wurde bereits gelöscht.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Audit Log -->
        <h4 class="font-bold text-md mb-2 flex items-center gap-1">
            <span class="material-icons text-sm">history</span> Moderations-Protokoll (Audit Trail)
        </h4>
        <div class="overflow-x-auto">
            <table class="table-modern w-full text-xs">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Moderator</th>
                        <th>Aktion</th>
                        <th>Ziel</th>
                        <th>Begründung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Keine Moderations-Ereignisse protokolliert.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $al): ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars($al['timestamp']) ?></td>
                                <td><strong><?= htmlspecialchars($al['moderator_name']) ?></strong></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($al['action']) ?></span></td>
                                <td><?= htmlspecialchars($al['target_type'] . ':' . $al['target_id']) ?></td>
                                <td><?= htmlspecialchars($al['reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: ALLGEMEINE EINSTELLUNGEN -->
    <div id="forum-settings" class="tab-pane mt-8 pt-6 border-t">
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
            <span class="material-icons text-primary">tune</span> Foren-Konfiguration
        </h3>
        <form method="post" class="space-y-4 max-w-xl">
            <input type="hidden" name="action" value="forum_save_global_settings">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label class="font-semibold block mb-1">Forum-Titel:</label>
                <input type="text" name="forum_title" value="<?= htmlspecialchars($cfg['forum_title'] ?? 'WeaveCMS Community Forum') ?>" class="input-field w-full">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="font-semibold block mb-1">Themen pro Seite:</label>
                    <input type="number" name="topics_per_page" min="5" max="100" value="<?= (int)($cfg['topics_per_page'] ?? 20) ?>" class="input-field w-full">
                </div>
                <div class="form-group">
                    <label class="font-semibold block mb-1">Beiträge pro Seite:</label>
                    <input type="number" name="posts_per_page" min="5" max="100" value="<?= (int)($cfg['posts_per_page'] ?? 15) ?>" class="input-field w-full">
                </div>
            </div>

            <div class="form-group">
                <label class="font-semibold block mb-1">Spam-Wortfilter (kommagetrennt):</label>
                <input type="text" name="badwords" value="<?= htmlspecialchars($cfg['badwords'] ?? 'spamlink, viagra, casino777') ?>" class="input-field w-full">
            </div>

            <div class="form-group">
                <label class="font-semibold block mb-1">Mindestabstand zwischen Beiträgen (Sekunden):</label>
                <input type="number" name="rate_limit_seconds" min="0" max="300" value="<?= (int)($cfg['rate_limit_seconds'] ?? 15) ?>" class="input-field w-full">
                <span class="text-xs text-muted">Schützt vor automatisiertem Spam und Doppel-Posts.</span>
            </div>

            <!-- Global Announcement Banner Configuration -->
            <div class="card p-4 my-4 bg-gray-50 dark:bg-gray-800 border">
                <h4 class="font-bold text-sm mb-3 flex items-center gap-2">
                    <span class="material-icons text-primary text-base">campaign</span>
                    Globaler Foren-Ankündigungsbanner (Notice Box)
                </h4>
                <div class="form-group mb-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="announcement_enabled" value="1" <?= !empty($cfg['announcement_enabled']) ? 'checked' : '' ?>>
                        <span class="font-semibold text-sm">Ankündigungsbanner im Forum oben einblenden</span>
                    </label>
                </div>
                <div class="form-group mb-3">
                    <label class="font-semibold block mb-1 text-xs">Banner-Typ / Farbe:</label>
                    <select name="announcement_type" class="input-field text-xs w-full max-w-xs">
                        <option value="info" <?= ($cfg['announcement_type'] ?? '') === 'info' ? 'selected' : '' ?>>Information (Blau)</option>
                        <option value="warning" <?= ($cfg['announcement_type'] ?? '') === 'warning' ? 'selected' : '' ?>>Wichtig / Warnung (Gelb)</option>
                        <option value="success" <?= ($cfg['announcement_type'] ?? '') === 'success' ? 'selected' : '' ?>>Erfolg / Neuigkeit (Grün)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-semibold block mb-1 text-xs">Hinweistext:</label>
                    <textarea name="announcement_text" rows="2" class="input-field w-full text-xs"
                              placeholder="z. B. Herzlich willkommen! Am Wochenende finden Wartungsarbeiten statt..."><?= htmlspecialchars($cfg['announcement_text'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Einstellungen speichern
            </button>
        </form>
    </div>

    <!-- TAB 4: SPEICHER & MIGRATION -->
    <div id="forum-storage" class="tab-pane mt-8 pt-6 border-t">
        <h3 class="font-bold text-lg mb-3 flex items-center gap-2">
            <span class="material-icons text-primary">storage</span>
            Speicher-Architektur & 1-Klick-Migration
        </h3>
        <p class="text-sm text-muted mb-6">
            WeaveCMS erlaubt es dir, die gesamten Foreninhalte (Kategorien, Boards, Themen, Beiträge, Meldungen)
            entweder als Flat-File JSON (standardmäßig in <code>data/forum_data.json</code>)
            oder in einer performant indizierten SQLite-Datenbank (in <code>data/forum.sqlite</code>) abzulegen.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    Status: <?= file_exists($jsonFile) ? 'Vorhanden (' . filesize($jsonFile) . ' Bytes)' : 'Wird beim ersten Zugriff erstellt' ?>
                </p>
                <?php if ($currentDriver !== 'json'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="forum_migrate_data_storage">
                        <input type="hidden" name="target_driver" value="json">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-outline btn-sm w-full">
                            Foren-Inhalte nach JSON migrieren
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
                    Status: <?= file_exists($sqliteFile) ? 'Vorhanden (' . filesize($sqliteFile) . ' Bytes)' : 'Wird bei Migration automatisch initialisiert' ?>
                </p>

                <?php if (!$isSqliteSupported): ?>
                    <div class="alert alert-warning text-xs mb-3">
                        <strong>Hinweis:</strong> Die PHP-Erweiterung <code>pdo_sqlite</code> ist derzeit nicht geladen.
                        Bitte in der <code>php.ini</code> die Zeile <code>extension=pdo_sqlite</code> aktivieren und den Webserver neu starten.
                    </div>
                <?php endif; ?>

                <?php if ($currentDriver !== 'sqlite'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="forum_migrate_data_storage">
                        <input type="hidden" name="target_driver" value="sqlite">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-primary btn-sm w-full" <?= !$isSqliteSupported ? 'disabled' : '' ?>>
                            Foren-Inhalte nach SQLite migrieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-4 rounded border bg-light text-sm text-muted mt-6">
            <div class="font-bold flex items-center gap-2 text-dark mb-1">
                <span class="material-icons text-primary text-base">info</span>
                Warum werden bei SQLite keine Datenbank-Zugangsdaten (Host, Benutzer, Passwort) abgefragt?
            </div>
            <p class="mb-0">
                <strong>SQLite</strong> ist eine serverlose, dateibasierte SQL-Datenbank (Zero-Configuration). 
                Im Unterschied zu MySQL oder MariaDB benötigt SQLite keinen separaten Datenbank-Serverdienst und keine Zugangsdaten. 
                Alle Foren-Kategorien, Boards, Themen, Beiträge und Likes werden direkt, isoliert und transaktionssicher in der lokalen Datei 
                <code><?= htmlspecialchars($sqliteFile) ?></code> gespeichert. Die Datenbankdatei und alle benötigten Tabellen werden beim Klick auf die Migration vollautomatisch angelegt.
            </p>
        </div>
    </div>
</div>
