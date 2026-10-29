<?php
/**
 * Frontend: Kunena-inspirierte Forensuche mit Bereichsfilter & Sortierung
 */
if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

$query = trim($_GET['q'] ?? '');
$selectedBoard = trim($_GET['board'] ?? '');
$scope = trim($_GET['scope'] ?? 'all');
$sortBy = trim($_GET['sort'] ?? 'latest');

$currentUser = class_exists('ForumUserManager') ? ForumUserManager::getCurrentUser() : null;
$categories = ForumManager::getCategories();
$allBoards = ForumManager::getBoards();

// Nur Foren im Dropdown anzeigen, die der aktuelle Benutzer/Gast auch lesen darf
$allowedBoards = array_filter($allBoards, fn($b) => ForumManager::canUserReadBoard($b, $currentUser));

$results = ['topics' => [], 'query' => $query, 'count' => 0];
if ($query !== '') {
    $rawResults = ForumManager::search($query, $selectedBoard ?: null, $scope, $sortBy, 50);
    // Zusätzlicher Sicherheitsfilter für Gäste
    if (!empty($rawResults['topics'])) {
        $rawResults['topics'] = array_values(array_filter($rawResults['topics'], fn($t) => ForumManager::canUserReadBoard($t['board_id'], $currentUser)));
        $rawResults['count'] = count($rawResults['topics']);
    }
    $results = $rawResults;
}

$selectedBoardObj = !empty($selectedBoard) ? ForumManager::getBoard($selectedBoard) : null;
?>

<div class="kunena-search-container" style="max-width: 100%;">
    <!-- Search Form Card -->
    <div class="kunena-cat-card" style="margin-bottom: 24px; border-left-color: var(--primary);">
        <div class="kunena-cat-header">
            <div class="kunena-cat-title-group">
                <span class="material-icons kunena-cat-icon" style="color: var(--primary);">search</span>
                <h2 class="kunena-cat-title" style="font-size: 1.35rem;">
                    Erweiterte Forensuche
                </h2>
            </div>
        </div>

        <div style="padding: 24px; background: #ffffff;">
            <p style="font-size: 13px; color: #64748b; margin: 0 0 20px 0; line-height: 1.5;">
                Durchsuche alle Themen, Beiträge und Diskussionen der Community nach Begriffen, oder grenze deine Suche gezielt auf bestimmte Bereiche ein.
            </p>

            <form method="get" action="" style="display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" name="page" value="forum">
                <input type="hidden" name="view" value="search">

                <!-- Row 1: Suchbegriff & Bereichs-Dropdown -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    <!-- Keyword Input -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Suchbegriff(e):
                        </label>
                        <div style="position: relative;">
                            <span class="material-icons" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #94a3b8;">
                                search
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" required minlength="2"
                                   placeholder="z. B. Weide, Flechten, Werkzeug, Schnittzeit..."
                                   style="width: 100%; box-sizing: border-box; padding: 10px 14px 10px 38px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a; outline: none; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'" autofocus>
                        </div>
                    </div>

                    <!-- Board Dropdown -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Bereich / Unterforum eingrenzen:
                        </label>
                        <div style="position: relative;">
                            <span class="material-icons" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #94a3b8;">
                                filter_list
                            </span>
                            <select name="board" 
                                    style="width: 100%; box-sizing: border-box; padding: 10px 14px 10px 38px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a; outline: none; transition: border-color 0.2s; cursor: pointer;"
                                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                                <option value="">🌐 Alle Foren und Bereiche durchsuchen</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php 
                                    $catBoards = array_filter($allowedBoards, fn($b) => ($b['category_id'] ?? '') === $cat['id']);
                                    if (empty($catBoards)) continue;
                                    ?>
                                    <optgroup label="<?= htmlspecialchars($cat['title']) ?>">
                                        <?php foreach ($catBoards as $b): ?>
                                            <option value="<?= htmlspecialchars($b['id']) ?>" <?= $selectedBoard === $b['id'] ? 'selected' : '' ?>>
                                                &nbsp;&nbsp;↳ <?= htmlspecialchars($b['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Suchoptionen (Umfang & Sortierung) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; padding-top: 6px;">
                    <!-- Scope (Wo suchen?) -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Suchbereich:
                        </label>
                        <div style="display: flex; gap: 14px; align-items: center; margin-top: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; cursor: pointer;">
                                <input type="radio" name="scope" value="all" <?= $scope !== 'titles' ? 'checked' : '' ?> style="cursor: pointer;">
                                <span>In Beiträgen &amp; Titeln</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; cursor: pointer;">
                                <input type="radio" name="scope" value="titles" <?= $scope === 'titles' ? 'checked' : '' ?> style="cursor: pointer;">
                                <span>Nur in Thementiteln</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sortierung -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            Ergebnisse sortieren nach:
                        </label>
                        <select name="sort" 
                                style="width: 100%; box-sizing: border-box; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a; outline: none; cursor: pointer;">
                            <option value="latest" <?= $sortBy === 'latest' ? 'selected' : '' ?>>Neueste Aktivität (Zuletzt geantwortet)</option>
                            <option value="created" <?= $sortBy === 'created' ? 'selected' : '' ?>>Erstelldatum des Themas</option>
                            <option value="replies" <?= $sortBy === 'replies' ? 'selected' : '' ?>>Meiste Antworten</option>
                            <option value="views" <?= $sortBy === 'views' ? 'selected' : '' ?>>Meiste Aufrufe</option>
                        </select>
                    </div>
                </div>

                <!-- Submit / Buttons Bar -->
                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-top: 4px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="submit" class="kunena-btn-primary" style="padding: 10px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <span class="material-icons" style="font-size: 18px;">search</span> Suche starten
                        </button>
                        <?php if ($query !== '' || $selectedBoard !== ''): ?>
                            <a href="?page=forum&view=search" class="kunena-btn-secondary" style="padding: 10px 18px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                <span class="material-icons" style="font-size: 16px;">clear</span> Filter zurücksetzen
                            </a>
                        <?php endif; ?>
                    </div>

                    <a href="?page=forum" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-icons" style="font-size: 16px;">arrow_back</span> Zurück zur Forenübersicht
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results Section -->
    <?php if ($query !== ''): ?>
        <div class="kunena-cat-card">
            <!-- Results Header -->
            <div class="kunena-subhead" style="font-size: 13px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="font-size: 16px; color: var(--primary);">playlist_add_check</span>
                    <span>
                        Suchergebnisse für &bdquo;<strong><?= htmlspecialchars($query) ?></strong>&ldquo;
                        <?php if ($selectedBoardObj): ?>
                            im Bereich &bdquo;<em><?= htmlspecialchars($selectedBoardObj['title']) ?></em>&ldquo;
                        <?php endif; ?>
                    </span>
                    <span class="kunena-cat-badge" style="background: #e0f2fe; color: #0369a1; margin-left: 6px;">
                        <?= $results['count'] ?> <?= $results['count'] === 1 ? 'Treffer' : 'Treffer' ?>
                    </span>
                </div>
                <div class="kunena-subhead-stats">Antworten</div>
                <div class="kunena-subhead-last">Letzter Beitrag</div>
            </div>

            <!-- Results List -->
            <div class="kunena-board-list">
                <?php if (empty($results['topics'])): ?>
                    <div style="padding: 50px 20px; text-align: center; color: #64748b;">
                        <div style="width: 56px; height: 56px; margin: 0 auto 14px auto; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center;">
                            <span class="material-icons" style="font-size: 32px;">search_off</span>
                        </div>
                        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                            Keine passenden Themen oder Beiträge gefunden
                        </h3>
                        <p style="font-size: 13px; max-width: 480px; margin: 0 auto 16px auto; line-height: 1.5;">
                            Für deine Suchanfrage &bdquo;<strong><?= htmlspecialchars($query) ?></strong>&ldquo; wurden keine Treffer ermittelt.
                            <?php if ($selectedBoardObj): ?>
                                Eventuell befindet sich das gesuchte Thema in einem anderen Forenbereich?
                            <?php endif; ?>
                        </p>
                        <div style="display: flex; justify-content: center; gap: 10px;">
                            <?php if ($selectedBoard): ?>
                                <a href="?page=forum&view=search&q=<?= urlencode($query) ?>" class="kunena-btn-secondary" style="font-size: 12px; padding: 6px 14px;">
                                    In allen Forenbereichen suchen
                                </a>
                            <?php endif; ?>
                            <a href="?page=forum" class="kunena-btn-secondary" style="font-size: 12px; padding: 6px 14px;">
                                Zurück zur Übersicht
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($results['topics'] as $t): ?>
                        <?php 
                        $replyCount = (int)($t['reply_count'] ?? 0);
                        $relTime = ForumManager::formatRelativeTime($t['last_post_at'] ?? $t['created_at']);

                        $authorObj = ($currentUser && class_exists('ForumUserManager')) ? (ForumUserManager::getUserById($t['author_id'] ?? '') ?? ForumUserManager::findUser($t['author_name'] ?? '')) : null;
                        $authorDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($authorObj, $t['author_name'] ?? 'Mitglied') : ($t['author_name'] ?? 'Mitglied');

                        $lastAuthorObj = ($currentUser && class_exists('ForumUserManager')) ? ForumUserManager::findUser($t['last_post_user'] ?? $t['author_name']) : null;
                        $lastAuthorPriv = class_exists('ForumUserManager') ? ForumUserManager::getPrivacySettings($lastAuthorObj) : ['show_role' => true];
                        $authorRole = !empty($lastAuthorPriv['show_role']) ? ($lastAuthorObj['role'] ?? 'forum_user') : 'forum_user';
                        $roleColor = class_exists('ForumUserManager') ? ForumUserManager::getRoleColor($authorRole) : '#2563eb';
                        $lastPostDisplayName = class_exists('ForumUserManager') ? ForumUserManager::getDisplayName($lastAuthorObj, $t['last_post_user'] ?? 'Mitglied') : ($t['last_post_user'] ?? 'Mitglied');
                        ?>
                        <div class="kunena-board-row">
                            <!-- Left: Topic Icon, Board Badge, Title, Snippet -->
                            <div class="kunena-board-main">
                                <div class="kunena-board-icon" style="background: #f8fafc; border-color: #e2e8f0;">
                                    <span class="material-icons" style="color: var(--primary);"><?= htmlspecialchars($t['board_icon'] ?? 'chat') ?></span>
                                </div>
                                <div class="kunena-board-info">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 4px;">
                                        <!-- Board Tag -->
                                        <a href="?page=forum&view=board&id=<?= urlencode($t['board_id'] ?? '') ?>" 
                                           class="kunena-cat-badge" style="background: #f1f5f9; color: #475569; text-decoration: none; font-size: 11px;">
                                            <?= htmlspecialchars($t['board_title'] ?? 'Forum') ?>
                                        </a>

                                        <?php if (!empty($t['is_solved'])): ?>
                                            <span class="kunena-badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:10px; padding:1px 6px;">
                                                <span class="material-icons" style="font-size:11px; vertical-align:middle;">check_circle</span> Gelöst
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($t['is_pinned'])): ?>
                                            <span class="kunena-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:10px; padding:1px 6px;">
                                                <span class="material-icons" style="font-size:11px; vertical-align:middle;">push_pin</span> Angepinnt
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($t['is_locked'])): ?>
                                            <span class="kunena-badge" style="background:#f1f5f9; color:#64748b; font-size:10px; padding:1px 6px;">
                                                <span class="material-icons" style="font-size:11px; vertical-align:middle;">lock</span> Geschlossen
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="kunena-board-title-line">
                                        <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>" class="kunena-board-title" style="font-size: 15px;">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </a>
                                    </div>

                                    <!-- Highlighted Text Snippet -->
                                    <?php if (!empty($t['snippet'])): ?>
                                        <div style="margin-top: 6px; font-size: 12px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; line-height: 1.5;">
                                            <span class="material-icons" style="font-size: 13px; vertical-align: middle; color: #94a3b8;">format_quote</span>
                                            <?= $t['snippet'] ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
                                        <?php if ($currentUser): ?>
                                            Erstellt von <strong style="color: #475569;"><?= htmlspecialchars($authorDisplayName) ?></strong>
                                            am <?= htmlspecialchars(substr($t['created_at'] ?? '', 0, 10)) ?>
                                        <?php else: ?>
                                            Erstellt am <?= htmlspecialchars(substr($t['created_at'] ?? '', 0, 10)) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle Column: Stats -->
                            <div class="kunena-board-stats">
                                <div class="kunena-stat-item">
                                    <span class="kunena-stat-num"><?= $replyCount ?></span>
                                    <span class="kunena-stat-lbl"><?= $replyCount === 1 ? 'Antwort' : 'Antworten' ?></span>
                                </div>
                                <div class="kunena-stat-item">
                                    <span class="kunena-stat-num"><?= (int)($t['views'] ?? 0) ?></span>
                                    <span class="kunena-stat-lbl">Aufrufe</span>
                                </div>
                            </div>

                            <!-- Right Column: Last Post Block -->
                            <div class="kunena-board-lastpost">
                                <?php if ($currentUser): ?>
                                    <div class="kunena-avatar-mini" style="background-color: <?= $roleColor ?>18; color: <?= $roleColor ?>; border: 1px solid <?= $roleColor ?>40;">
                                        <?= strtoupper(mb_substr($lastPostDisplayName, 0, 1)) ?>
                                    </div>
                                    <div class="kunena-lastpost-info">
                                        <div class="kunena-lastpost-title">
                                            <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>">
                                                Letzte Antwort
                                            </a>
                                        </div>
                                        <div class="kunena-lastpost-meta">
                                            von <span style="color: <?= $roleColor ?>; font-weight: 700;"><?= htmlspecialchars($lastPostDisplayName) ?></span>
                                            • <span title="<?= htmlspecialchars($t['last_post_at'] ?? '') ?>"><?= $relTime ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="kunena-avatar-mini" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                        <span class="material-icons" style="font-size: 15px;">schedule</span>
                                    </div>
                                    <div class="kunena-lastpost-info">
                                        <div class="kunena-lastpost-title">
                                            <a href="?page=forum&view=topic&id=<?= urlencode($t['id']) ?>">
                                                Letzte Antwort
                                            </a>
                                        </div>
                                        <div class="kunena-lastpost-meta">
                                            <span title="<?= htmlspecialchars($t['last_post_at'] ?? '') ?>"><?= $relTime ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
