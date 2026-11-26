<?php
/**
 * Weave Helpdesk – Aufgabenliste View
 * - Geschlossene & abgerechnete Tickets werden standardmäßig ausgeblendet und über den Button „Geschlossene Tickets“ geöffnet.
 * - Gruppierung nach Firma / Einsender (oder nur Benutzer / ungruppiert) sowie Sortierung nach Eingangsreihenfolge.
 */

$user = HelpdeskUserManager::getCurrentUser();
$showClosed = !empty($_GET['closed']);
$search = trim((string)($_GET['search'] ?? ''));

$groupMode = trim((string)($_GET['group'] ?? 'company_user'));
if (!in_array($groupMode, ['company_user', 'user', 'none'], true)) {
    $groupMode = 'company_user';
}

$sortOrder = trim((string)($_GET['sort'] ?? 'asc'));
if (!in_array($sortOrder, ['asc', 'desc'], true)) {
    $sortOrder = 'asc';
}

// Offene und geschlossene Tickets nach Eingangsreihenfolge laden
$openTickets = HelpdeskManager::getTicketsForUser($user, true, $search, false, $sortOrder);
$closedTickets = HelpdeskManager::getTicketsForUser($user, false, $search, true, $sortOrder);

$tickets = $showClosed ? $closedTickets : $openTickets;
$groups = HelpdeskManager::groupTickets($tickets, $groupMode);
$contract = HelpdeskManager::getContractSummary($user);
$canManage = HelpdeskUserManager::canManage($user);

$statusBadgeMap = [];
foreach (HelpdeskManager::getStatuses() as $stKey => $stMeta) {
    $statusBadgeMap[$stKey] = $stMeta['class'] ?? '';
}

$buildListUrl = static function(array $overrides = []) use ($showClosed, $search, $groupMode, $sortOrder): string {
    $params = [
        'page'   => 'helpdesk',
        'closed' => array_key_exists('closed', $overrides) ? $overrides['closed'] : ($showClosed ? '1' : ''),
        'group'  => $overrides['group'] ?? $groupMode,
        'sort'   => $overrides['sort'] ?? $sortOrder,
        'search' => $overrides['search'] ?? $search,
    ];
    $clean = [];
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null) {
            $clean[$k] = $v;
        }
    }
    return '?' . http_build_query($clean);
};
?>

<div class="hd-main-container">
    <!-- Hauptbereich: Aufgabenliste -->
    <div class="hd-content-area">
        <div class="hd-panel">
            <div class="hd-panel-header">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 15px;"><?= $showClosed ? '📦' : '📋' ?></span>
                    <span><?= $showClosed ? 'Geschlossene Tickets (nach Eingang sortiert)' : 'Offene Aufgaben' ?></span>
                    <span class="title-badge"><?= count($tickets) ?> Aufgaben</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <?php if ($showClosed): ?>
                        <a href="<?= htmlspecialchars($buildListUrl(['closed' => ''])) ?>" class="hd-btn hd-btn-outline">
                            📋 Offene Tickets (<?= count($openTickets) ?>)
                        </a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($buildListUrl(['closed' => '1'])) ?>" class="hd-btn hd-btn-outline">
                            📦 Geschlossene Tickets (<?= count($closedTickets) ?>)
                        </a>
                    <?php endif; ?>
                    <a href="?page=helpdesk&view=create" class="hd-btn hd-btn-green">
                        <span>+</span> Neue Aufgabe
                    </a>
                </div>
            </div>

            <!-- Toolbar: Suche, Gruppierung & Eingangs-Sortierung -->
            <div class="hd-toolbar">
                <div class="hd-search-box">
                    <input type="text" id="hdSearchInput" class="hd-search-input" placeholder="+ Suchen nach Nummer, Betreff, Firma, Einsender..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="hd-toolbar-actions" style="flex-wrap: wrap;">
                    <form method="GET" style="display: flex; align-items: center; gap: 6px; margin: 0;">
                        <input type="hidden" name="page" value="helpdesk">
                        <?php if ($showClosed): ?>
                            <input type="hidden" name="closed" value="1">
                        <?php endif; ?>
                        <?php if ($search !== ''): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sortOrder) ?>">
                        <label for="hdGroupSelect" style="font-size: 12px; color: #4a5568; font-weight: 600;">Gruppierung:</label>
                        <select id="hdGroupSelect" name="group" class="hd-select" style="width: auto; padding: 4px 8px; font-size: 12px;" onchange="this.form.submit()">
                            <option value="company_user" <?= $groupMode === 'company_user' ? 'selected' : '' ?>>Firma / Benutzer</option>
                            <option value="user" <?= $groupMode === 'user' ? 'selected' : '' ?>>Nur Benutzer</option>
                            <option value="none" <?= $groupMode === 'none' ? 'selected' : '' ?>>Keine Gruppierung</option>
                        </select>
                    </form>

                    <?php $nextSort = ($sortOrder === 'asc') ? 'desc' : 'asc'; ?>
                    <a href="<?= htmlspecialchars($buildListUrl(['sort' => $nextSort])) ?>" class="hd-btn hd-btn-dark" title="Sortierung nach Eingangszeitpunkt umkehren">
                        <?= $sortOrder === 'asc' ? 'Eingang ↑ (älteste zuerst)' : 'Eingang ↓ (neueste zuerst)' ?>
                    </a>
                </div>
            </div>

            <!-- Ticket-Tabelle -->
            <div class="hd-table-responsive">
                <table class="hd-table">
                    <thead>
                        <tr>
                            <th style="width: 105px;">Aufgabennr.</th>
                            <th>Betreff / Einsender</th>
                            <th style="width: 120px;">Typ</th>
                            <th style="width: 155px;">Status</th>
                            <th style="width: 90px;">Priorität</th>
                            <th style="width: 130px;">Eingang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 26px; color: #718096;">
                                    <?php if ($showClosed): ?>
                                        Keine geschlossenen Tickets vorhanden.
                                        <a href="<?= htmlspecialchars($buildListUrl(['closed' => ''])) ?>" style="color: #257985; font-weight: 700; margin-left: 6px;">Zurück zu offenen Tickets</a>
                                    <?php else: ?>
                                        Keine offenen Aufgaben gefunden. Klicken Sie auf <strong>„+ Neue Aufgabe“</strong>, um ein Ticket anzulegen.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groups as $grp): ?>
                                <?php if ($groupMode !== 'none'): ?>
                                    <tr class="hd-group-row" data-group-id="<?= htmlspecialchars($grp['id']) ?>">
                                        <td colspan="6">
                                            <span style="font-weight: 700; color: #1b5b64;">
                                                <?php if ($groupMode === 'company_user'): ?>
                                                    🏢 <?= htmlspecialchars($grp['company']) ?> &bull; 👤 <?= htmlspecialchars($grp['user']) ?>
                                                <?php else: ?>
                                                    👤 <?= htmlspecialchars($grp['user']) ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="hd-group-count"><?= count($grp['tickets']) ?> <?= count($grp['tickets']) === 1 ? 'Ticket' : 'Tickets' ?></span>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($grp['tickets'] as $t): ?>
                                    <?php
                                    $status = $t['status'] ?? HelpdeskManager::STATUS_NEW;
                                    $rowClass = 'row-open';
                                    if ($status === HelpdeskManager::STATUS_CLOSED) {
                                        $rowClass = 'row-closed';
                                    } elseif ($status === HelpdeskManager::STATUS_BILLED) {
                                        $rowClass = 'row-billed';
                                    }
                                    $badgeClass = $statusBadgeMap[$status] ?? '';
                                    $createdFormatted = date('d.m.y, H:i', strtotime((string)($t['created_at'] ?? 'now')));
                                    $submitterName = $t['created_by_name'] ?? ($t['contact_person'] ?? '');
                                    ?>
                                    <tr class="<?= $rowClass ?>" data-group-id="<?= htmlspecialchars($grp['id']) ?>" data-href="?page=helpdesk&view=detail&id=<?= urlencode($t['id']) ?>">
                                        <td class="ticket-id-cell">
                                            <?= htmlspecialchars($t['id']) ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($t['subject']) ?></strong>
                                            <div style="font-size: 11px; opacity: 0.85;">
                                                <?php if (!empty($t['company_name'])): ?>
                                                    🏢 <?= htmlspecialchars($t['company_name']) ?> &bull;
                                                <?php endif; ?>
                                                👤 <?= htmlspecialchars($submitterName ?: $t['contact_person'] ?? '-') ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($t['type'] ?? 'Serviceanfrage') ?></td>
                                        <td>
                                            <span class="status-badge <?= htmlspecialchars($badgeClass) ?>">
                                                <?php if ($status === HelpdeskManager::STATUS_BILLED): ?>
                                                    🔒 Abgerechnet
                                                <?php else: ?>
                                                    <?= htmlspecialchars($status) ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $prio = $t['priority'] ?? 'Normal';
                                            if (mb_strtolower($prio) === 'kritisch'):
                                            ?>
                                                <span class="priority-badge priority-critical">🚨 <?= htmlspecialchars($prio) ?></span>
                                            <?php elseif (mb_strtolower($prio) === 'hoch'): ?>
                                                <span class="priority-badge priority-high"><?= htmlspecialchars($prio) ?></span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($prio) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($createdFormatted) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Rechte Seitenleiste: Verträge - Inklusivminuten (Widget) -->
    <?php if ($contract): ?>
        <div class="hd-sidebar-area">
            <div class="hd-contract-widget">
                <div class="hd-contract-header">
                    <span>📑 Verträge - Inklusivminuten</span>
                    <button type="button" id="hdOpenContractModal" class="hd-btn hd-btn-outline" style="padding: 2px 6px; font-size: 11px;" title="Vertragsauswertung öffnen">
                        🔍 Details
                    </button>
                </div>
                <div class="hd-contract-body">
                    <div class="hd-contract-title">
                        <?= htmlspecialchars($contract['contract_number']) ?> - <?= htmlspecialchars($contract['title']) ?>
                    </div>
                    <div class="hd-contract-meta">
                        Nächste Abrechnung: <?= htmlspecialchars($contract['next_billing']) ?>
                    </div>

                    <table class="hd-contract-table">
                        <thead>
                            <tr>
                                <th>Verfügbar</th>
                                <th>Verbraucht</th>
                                <th>Differenz</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= number_format($contract['available'], 0, ',', '.') ?> Min.</td>
                                <td><?= number_format($contract['used'], 0, ',', '.') ?> Min.</td>
                                <td class="cell-diff <?= ($contract['difference'] < 60) ? 'negative' : '' ?>">
                                    <?= number_format($contract['difference'], 0, ',', '.') ?> Min.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Vertragsauswertung -->
<?php if ($contract): ?>
<div id="hdContractModal" class="hd-modal-backdrop">
    <div class="hd-modal">
        <div class="hd-modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" id="hdPrintContractBtn" style="background: none; border: none; color: #fff; cursor: pointer; font-size: 15px;" title="Drucken">🖨️</button>
                <span>Vertragsauswertung: <?= htmlspecialchars($contract['contract_number']) ?></span>
            </div>
            <button type="button" id="hdCloseContractModalX" style="background: none; border: none; color: #fff; font-size: 16px; cursor: pointer;">&times;</button>
        </div>
        <div class="hd-modal-body">
            <div style="margin-bottom: 12px; font-weight: 600; color: #257985;">
                <?= htmlspecialchars($contract['title']) ?>
            </div>
            <table class="hd-table" style="background: #fff;">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Von</th>
                        <th>Bis</th>
                        <th>Verfügbar</th>
                        <th>Verbraucht</th>
                        <th>Differenz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contract['history'] as $h): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($h['status']) ?></strong></td>
                            <td><?= htmlspecialchars($h['from']) ?></td>
                            <td><?= htmlspecialchars($h['to']) ?></td>
                            <td><?= number_format($h['available'], 0, ',', '.') ?></td>
                            <td><?= number_format($h['used'], 0, ',', '.') ?></td>
                            <td style="font-weight: 700; color: <?= ($h['difference'] < 0) ? '#e74c3c' : '#27ae60' ?>;">
                                <?= number_format($h['difference'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="hd-modal-footer">
            <button type="button" id="hdCloseContractModal" class="hd-btn hd-btn-red">
                &times; Schließen
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
