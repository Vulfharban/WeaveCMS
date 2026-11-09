<?php
/**
 * Weave Helpdesk – Aufgabenliste View (entspricht Screenshot 4)
 */

$user = HelpdeskUserManager::getCurrentUser();
$hideClosed = false;
$search = $_GET['search'] ?? '';

$tickets = HelpdeskManager::getTicketsForUser($user, $hideClosed, $search);
$contract = HelpdeskManager::getContractSummary($user);
?>

<div class="hd-main-container">
    <!-- Hauptbereich: Aufgabenliste -->
    <div class="hd-content-area">
        <div class="hd-panel">
            <div class="hd-panel-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 15px;">📋</span>
                    <span>Aufgabenliste</span>
                    <span class="title-badge"><?= count($tickets) ?> Aufgaben</span>
                </div>
                <div>
                    <a href="?page=helpdesk&view=create" class="hd-btn hd-btn-green">
                        <span>+</span> Neue Aufgabe
                    </a>
                </div>
            </div>

            <!-- Toolbar: Suche & Filter -->
            <div class="hd-toolbar">
                <div class="hd-search-box">
                    <input type="text" id="hdSearchInput" class="hd-search-input" placeholder="+ Suchen nach Nummer, Betreff, Firma..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="hd-toolbar-actions">
                    <label class="hd-filter-toggle">
                        <input type="checkbox" id="hdHideClosedCheckbox">
                        <span>Geschlossene Aufgaben verbergen</span>
                    </label>
                </div>
            </div>

            <!-- Ticket-Tabelle -->
            <div class="hd-table-responsive">
                <table class="hd-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Aufgabennr.</th>
                            <th>Betreff</th>
                            <th style="width: 120px;">Typ</th>
                            <th style="width: 130px;">Status</th>
                            <th style="width: 90px;">Priorität</th>
                            <th style="width: 130px;">Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 24px; color: #718096;">
                                    Keine Aufgaben gefunden. Klicken Sie auf <strong>"+ Neue Aufgabe"</strong>, um ein Ticket anzulegen.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <?php
                                $status = $t['status'] ?? HelpdeskManager::STATUS_NEW;
                                $rowClass = 'row-open';
                                if ($status === HelpdeskManager::STATUS_CLOSED) {
                                    $rowClass = 'row-closed';
                                } elseif ($status === HelpdeskManager::STATUS_BILLED) {
                                    $rowClass = 'row-billed';
                                }
                                $createdFormatted = date('d.m.y, H:i', strtotime($t['created_at']));
                                ?>
                                <tr class="<?= $rowClass ?>" data-href="?page=helpdesk&view=detail&id=<?= urlencode($t['id']) ?>">
                                    <td class="ticket-id-cell">
                                        <?= htmlspecialchars($t['id']) ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($t['subject']) ?></strong>
                                        <?php if (HelpdeskUserManager::canManage($user) && !empty($t['company_name'])): ?>
                                            <div style="font-size: 11px; opacity: 0.85;">
                                                🏢 <?= htmlspecialchars($t['company_name']) ?> (<?= htmlspecialchars($t['contact_person']) ?>)
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($t['type'] ?? 'Serviceanfrage') ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?php if ($status === HelpdeskManager::STATUS_BILLED): ?>
                                                🔒 Abgerechnet
                                            <?php else: ?>
                                                <?= htmlspecialchars($status) ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($t['priority'] ?? 'Normal') ?></td>
                                    <td><?= htmlspecialchars($createdFormatted) ?></td>
                                </tr>
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

<!-- Modal: Vertragsauswertung (Screenshot 5) -->
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

