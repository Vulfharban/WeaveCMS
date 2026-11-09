<?php
/**
 * Weave Helpdesk – Ticket-Detailansicht (entspricht Screenshot 2)
 */

$ticketId = $_GET['id'] ?? '';
$ticket = HelpdeskManager::getTicketById($ticketId);
$user = HelpdeskUserManager::getCurrentUser();

if (!$ticket || !HelpdeskUserManager::canViewTicket($ticket, $user)) {
    echo "<div class='hd-main-container'><div class='hd-alert-error'>Aufgabe nicht gefunden oder keine Zugriffsberechtigung.</div></div>";
    return;
}

$isBilled = (($ticket['status'] ?? '') === HelpdeskManager::STATUS_BILLED);
$isClosed = (($ticket['status'] ?? '') === HelpdeskManager::STATUS_CLOSED);
$canManage = HelpdeskUserManager::canManage($user);
$activities = $ticket['activities'] ?? [];

// Berechne Gesamtminuten dieses Tickets
$totalMinutes = 0;
foreach ($activities as $act) {
    $totalMinutes += (int)($act['minutes'] ?? 0);
}
?>

<div class="hd-main-container">
    <div style="width: 100%;">
        <?php if ($isBilled): ?>
            <div class="hd-billed-banner">
                <span style="font-size: 16px;">🔒</span>
                <span>
                    <strong>Buchhaltungs-Status: Abgerechnet</strong> – Diese Aufgabe wurde am <?= htmlspecialchars(date('d.m.Y H:i', strtotime($ticket['billed_at'] ?? $ticket['updated_at']))) ?> durch <?= htmlspecialchars($ticket['billed_by'] ?? 'Buchhaltung') ?> final abgerechnet. Das Ticket ist dauerhaft gesperrt; ein erneutes Öffnen oder Bearbeiten ist nicht mehr möglich.
                </span>
            </div>
        <?php endif; ?>

        <!-- Split-View: Links Stammdaten, Rechts Tätigkeiten -->
        <div class="hd-split-container">
            
            <!-- Linke Spalte: Ticket-Stammdaten & Inhalt -->
            <div class="hd-split-left">
                <div class="hd-panel">
                    <div class="hd-panel-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>📋</span>
                            <span>Aufgabe: <?= htmlspecialchars($ticket['id']) ?></span>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <?php if (!$isBilled): ?>
                                <?php if ($canManage): ?>
                                    <button type="button" class="hd-btn hd-btn-orange" onclick="alert('Ticket-Bearbeitung aktiv.');">
                                        ✏️ Bearbeiten
                                    </button>
                                <?php endif; ?>

                                <?php if (!$isClosed): ?>
                                    <form action="?page=helpdesk&action=change_status" method="POST" style="margin: 0;" onsubmit="return confirm('Möchten Sie diese Aufgabe wirklich schließen?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                        <input type="hidden" name="status" value="<?= HelpdeskManager::STATUS_CLOSED ?>">
                                        <button type="submit" class="hd-btn hd-btn-red">
                                            &times; Schließen
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <!-- Wieder öffnen (nur solange NICHT abgerechnet!) -->
                                    <form action="?page=helpdesk&action=change_status" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                        <input type="hidden" name="status" value="<?= HelpdeskManager::STATUS_IN_PROGRESS ?>">
                                        <button type="submit" class="hd-btn hd-btn-orange">
                                            🔄 Wieder öffnen
                                        </button>
                                    </form>

                                    <?php if ($canManage): ?>
                                        <!-- Buchhaltungs-Abrechnung durch Admin/Mitarbeiter -->
                                        <form action="?page=helpdesk&action=change_status" method="POST" style="margin: 0;" onsubmit="return confirm('Aufgabe als \'Abgerechnet\' markieren? Danach ist kein erneutes Öffnen mehr möglich!');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                            <input type="hidden" name="status" value="<?= HelpdeskManager::STATUS_BILLED ?>">
                                            <button type="submit" class="hd-btn hd-btn-blue">
                                                🔒 In Buchhaltung abrechnen
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="hd-btn hd-btn-dark" style="cursor: default; opacity: 0.9;">
                                    🔒 Abgerechnet (Gesperrt)
                                </span>
                            <?php endif; ?>

                            <a href="?page=helpdesk" class="hd-btn hd-btn-dark">
                                ← Zurück
                            </a>
                        </div>
                    </div>

                    <div style="padding: 16px;">
                        <!-- Betreff -->
                        <div class="hd-form-group">
                            <label class="hd-form-label">Betreff *</label>
                            <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['subject']) ?>" readonly>
                        </div>

                        <!-- Zeile 1: Aufgabentyp, Priorität, Status -->
                        <div class="hd-form-row">
                            <div class="hd-form-col">
                                <label class="hd-form-label">Aufgabentyp *</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['type']) ?>" readonly>
                            </div>
                            <div class="hd-form-col">
                                <label class="hd-form-label">Priorität *</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['priority']) ?>" readonly>
                            </div>
                            <div class="hd-form-col">
                                <label class="hd-form-label">Status</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['status']) ?>" readonly style="font-weight: 700; color: <?= $isClosed ? '#27ae60' : ($isBilled ? '#2980b9' : '#2c3e50') ?>;">
                            </div>
                        </div>

                        <!-- Zeile 2: Von Unternehmen, Adresse, Kontaktperson -->
                        <div class="hd-form-row">
                            <div class="hd-form-col">
                                <label class="hd-form-label">Von Unternehmen *</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['company_name']) ?>" readonly>
                            </div>
                            <div class="hd-form-col">
                                <label class="hd-form-label">Adresse *</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['address']) ?>" readonly>
                            </div>
                            <div class="hd-form-col">
                                <label class="hd-form-label">Kontaktperson</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['contact_person']) ?>" readonly>
                            </div>
                        </div>

                        <!-- Zeile 3: Abw. Ansprechpartner, Abw. Rufnummer -->
                        <div class="hd-form-row">
                            <div class="hd-form-col">
                                <label class="hd-form-label">Abw. Ansprechpartner</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['alt_contact_person']) ?>" readonly placeholder="-">
                            </div>
                            <div class="hd-form-col">
                                <label class="hd-form-label">Abw. Rufnummer</label>
                                <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['alt_phone']) ?>" readonly placeholder="-">
                            </div>
                        </div>

                        <!-- Zeile 4: An Dienstleister (aus CMS-Einstellungen!) -->
                        <div class="hd-form-group">
                            <label class="hd-form-label">An Dienstleister *</label>
                            <input type="text" class="hd-input" value="<?= htmlspecialchars($ticket['service_provider']) ?>" readonly style="font-weight: 600;">
                        </div>

                        <!-- Inhalt der Aufgabe -->
                        <div class="hd-form-group">
                            <label class="hd-form-label">Inhalt</label>
                            <div class="hd-editor-content">
                                <strong>Aufgabe: <?= htmlspecialchars($ticket['subject']) ?></strong><br><br>
                                <?= nl2br(htmlspecialchars($ticket['content'])) ?>
                            </div>
                        </div>

                        <div style="font-size: 11px; color: #718096; margin-top: 10px; display: flex; justify-content: space-between;">
                            <span>Erstellt: <?= htmlspecialchars($ticket['created_at']) ?> von <?= htmlspecialchars($ticket['created_by_name'] ?? 'Kunde') ?></span>
                            <span>Gesamtaufwand: <strong><?= $totalMinutes ?> Minuten</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rechte Spalte: Tätigkeiten & Verlauf (Screenshot 2) -->
            <div class="hd-split-right">
                <div class="hd-panel" style="margin-bottom: 0;">
                    <div class="hd-activities-header">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span>📂</span>
                            <span>Tätigkeiten</span>
                            <span class="title-badge"><?= count($activities) ?></span>
                        </div>
                        <?php if (!$isBilled): ?>
                            <button type="button" id="hdOpenActivityModal" class="hd-btn hd-btn-green" style="padding: 3px 8px; font-size: 11px;">
                                + Hinzufügen
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="hd-activities-list">
                        <?php if (empty($activities)): ?>
                            <div style="padding: 20px; text-align: center; color: #a0aec0; font-size: 12px;">
                                Noch keine Tätigkeiten hinterlegt.
                            </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($activities) as $act): ?>
                                <?php
                                $isInternal = !empty($act['is_internal']);
                                if ($isInternal && !$canManage) {
                                    continue; // Kunden dürfen interne Notizen nicht sehen!
                                }
                                $typeIcon = '📁';
                                if (strpos(strtolower($act['type']), 'telefon') !== false || strpos(strtolower($act['description']), 'erreicht') !== false) {
                                    $typeIcon = '📞';
                                } elseif (strpos(strtolower($act['type']), 'mail') !== false || strpos(strtolower($act['description']), 'wg:') !== false) {
                                    $typeIcon = '✉️';
                                } elseif (strpos(strtolower($act['type']), 'fernservice') !== false) {
                                    $typeIcon = '💻';
                                } elseif ($isInternal) {
                                    $typeIcon = '🔒';
                                }
                                ?>
                                <div class="hd-activity-item <?= $isInternal ? 'is-internal' : '' ?>">
                                    <div class="hd-activity-title">
                                        <span><?= $typeIcon ?></span>
                                        <span><?= htmlspecialchars($act['type']) ?></span>
                                        <?php if (!empty($act['minutes'])): ?>
                                            <span style="margin-left: auto; background: #e2e8f0; color: #2d3748; padding: 1px 6px; border-radius: 3px; font-weight: 700; font-size: 11px;">
                                                ⏱️ <?= (int)$act['minutes'] ?> Min.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hd-activity-desc"><?= htmlspecialchars($act['description']) ?></div>
                                    <div class="hd-activity-meta">
                                        <span>👤 <?= htmlspecialchars($act['author_name']) ?></span>
                                        <span><?= htmlspecialchars(date('d.m.y, H:i', strtotime($act['created_at']))) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Tätigkeit hinzufügen -->
<?php if (!$isBilled): ?>
<div id="hdActivityModal" class="hd-modal-backdrop">
    <div class="hd-modal" style="width: 500px;">
        <form action="?page=helpdesk&action=add_activity" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">

            <div class="hd-modal-header">
                <span>➕ Neue Tätigkeit erfassen</span>
                <button type="button" id="hdCloseActivityModalX" style="background: none; border: none; color: #fff; font-size: 16px; cursor: pointer;">&times;</button>
            </div>
            <div class="hd-modal-body">
                <div class="hd-form-group">
                    <label class="hd-form-label">Tätigkeitstyp *</label>
                    <select name="type" class="hd-select" required>
                        <option value="Fernservice">Fernservice (Fernwartung)</option>
                        <option value="Telefonat">Telefonat / Rückruf</option>
                        <option value="E-Mail">E-Mail / Schriftverkehr</option>
                        <option value="Vor-Ort-Service">Vor-Ort-Service</option>
                        <?php if ($canManage): ?>
                            <option value="Interne Notiz">Interne Notiz</option>
                        <?php endif; ?>
                        <option value="Kundenrückmeldung">Kundenrückmeldung</option>
                    </select>
                </div>

                <div class="hd-form-group">
                    <label class="hd-form-label">Aufgewendete Zeit in Minuten (wird vom Vertrag abgezogen)</label>
                    <input type="number" name="minutes" class="hd-input" min="0" step="1" value="15" placeholder="z. B. 15">
                </div>

                <div class="hd-form-group">
                    <label class="hd-form-label">Beschreibung / Protokoll *</label>
                    <textarea name="description" class="hd-textarea" rows="4" required placeholder="z. B. Telefonat mit Herrn Mustermann - Problem analysiert..."></textarea>
                </div>

                <?php if ($canManage): ?>
                    <div class="hd-form-group">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="is_internal" value="1">
                            <span>Nur für Support-Mitarbeiter sichtbar (interne Notiz)</span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hd-modal-footer">
                <button type="button" id="hdCloseActivityModal" class="hd-btn hd-btn-red">
                    Abbrechen
                </button>
                <button type="submit" class="hd-btn hd-btn-green">
                    💾 Speichern
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

