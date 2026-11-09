<?php
/**
 * Weave Helpdesk – Neue Aufgabe anlegen (entspricht Screenshot 1)
 */

$user = HelpdeskUserManager::getCurrentUser();
$cfg = HelpdeskManager::getConfig();
$provider = HelpdeskManager::getServiceProviderDetails();

$types = $cfg['ticket_types'] ?? ['Störung', 'Serviceanfrage', 'Wartung', 'Projekt', 'Installation', 'Sonstiges'];
$priorities = $cfg['priorities'] ?? ['Niedrig', 'Normal', 'Hoch', 'Kritisch'];
?>

<div class="hd-main-container">
    <div style="width: 100%;">
        <form action="?page=helpdesk&action=create_ticket" method="POST" id="hdCreateTicketForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="hd-panel">
                <!-- Header exakt wie im Screenshot 1 -->
                <div class="hd-panel-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>📋</span>
                        <span>Aufgabe: Neu</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="hd-btn hd-btn-dark">
                            💾 Speichern
                        </button>
                        <a href="?page=helpdesk" class="hd-btn hd-btn-red">
                            &times; Schließen
                        </a>
                    </div>
                </div>

                <div style="padding: 16px;">
                    <!-- Betreff -->
                    <div class="hd-form-group">
                        <label class="hd-form-label">Betreff *</label>
                        <input type="text" name="subject" class="hd-input" required placeholder="Kurze, prägnante Beschreibung des Anliegens" autofocus>
                    </div>

                    <!-- Zeile 1: Aufgabentyp, Priorität, Status -->
                    <div class="hd-form-row">
                        <div class="hd-form-col">
                            <label class="hd-form-label">Aufgabentyp *</label>
                            <select name="type" class="hd-select" required>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>" <?= ($t === 'Serviceanfrage') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hd-form-col">
                            <label class="hd-form-label">Priorität *</label>
                            <select name="priority" class="hd-select" required>
                                <?php foreach ($priorities as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>" <?= ($p === 'Normal') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hd-form-col">
                            <label class="hd-form-label">Status</label>
                            <input type="text" class="hd-input" value="Neu" readonly style="font-weight: 600;">
                        </div>
                    </div>

                    <!-- Zeile 2: Von Unternehmen, Adresse, Kontaktperson -->
                    <div class="hd-form-row">
                        <div class="hd-form-col">
                            <label class="hd-form-label">Von Unternehmen *</label>
                            <input type="text" name="company_name" class="hd-input" required value="<?= htmlspecialchars($user['company_name'] ?? '') ?>" placeholder="Firmenname">
                        </div>
                        <div class="hd-form-col">
                            <label class="hd-form-label">Adresse *</label>
                            <input type="text" name="address" class="hd-input" required value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Straße, PLZ, Ort">
                        </div>
                        <div class="hd-form-col">
                            <label class="hd-form-label">Kontaktperson</label>
                            <input type="text" name="contact_person" class="hd-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Ansprechpartner">
                        </div>
                    </div>

                    <!-- Zeile 3: Abw. Ansprechpartner, Abw. Rufnummer -->
                    <div class="hd-form-row">
                        <div class="hd-form-col">
                            <label class="hd-form-label">Abw. Ansprechpartner</label>
                            <input type="text" name="alt_contact_person" class="hd-input" placeholder="Optionaler zweiter Ansprechpartner">
                        </div>
                        <div class="hd-form-col">
                            <label class="hd-form-label">Abw. Rufnummer</label>
                            <input type="text" name="alt_phone" class="hd-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Telefonnummer für Rückfragen">
                        </div>
                    </div>

                    <!-- Zeile 4: An Dienstleister * (dynamisch aus den WeaveCMS-Einstellungen!) -->
                    <div class="hd-form-group">
                        <label class="hd-form-label">An Dienstleister *</label>
                        <input type="text" name="service_provider" class="hd-input" value="<?= htmlspecialchars($provider['name']) ?>" readonly style="background: #f8fafc; font-weight: 600;">
                    </div>

                    <!-- Inhalt mit Mock-Toolbar exakt wie im Screenshot 1 -->
                    <div class="hd-form-group">
                        <label class="hd-form-label">Inhalt</label>
                        <div class="hd-editor-toolbar">
                            <button type="button" class="hd-editor-btn" title="Rückgängig" onclick="document.execCommand('undo');">↩</button>
                            <button type="button" class="hd-editor-btn" title="Wiederholen" onclick="document.execCommand('redo');">↪</button>
                            <span style="color: #cbd5e1;">|</span>
                            <button type="button" class="hd-editor-btn" title="Fett" onclick="insertTag('**', '**');"><b>B</b></button>
                            <button type="button" class="hd-editor-btn" title="Kursiv" onclick="insertTag('*', '*');"><i>I</i></button>
                            <button type="button" class="hd-editor-btn" title="Unterstrichen" onclick="insertTag('<u>', '</u>');"><u>U</u></button>
                            <button type="button" class="hd-editor-btn" title="Durchgestrichen" onclick="insertTag('~~', '~~');"><s>S</s></button>
                            <span style="color: #cbd5e1;">|</span>
                            <button type="button" class="hd-editor-btn" title="Aufzählung" onclick="insertTag('\n- ', '');">☰</button>
                            <button type="button" class="hd-editor-btn" title="Nummerierung" onclick="insertTag('\n1. ', '');">1. ≡</button>
                            <button type="button" class="hd-editor-btn" title="Quellcode" onclick="insertTag('```\n', '\n```');">&lt;&gt;</button>
                        </div>
                        <textarea name="content" id="hdTaskContent" class="hd-textarea" rows="8" required placeholder="Detaillierte Beschreibung der Störung oder Serviceanfrage..."></textarea>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
function insertTag(openTag, closeTag) {
    const textarea = document.getElementById('hdTaskContent');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = openTag + selected + closeTag;
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + openTag.length, end + openTag.length);
}
</script>

