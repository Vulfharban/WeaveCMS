<?php
/**
 * Weave Helpdesk – Benutzerverwaltung View (für WeaveCMS Backend)
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

$users = HelpdeskUserManager::loadUsers();
$editId = $_GET['edit_user'] ?? '';
$editUser = $editId ? HelpdeskUserManager::getUserById($editId) : null;
?>

<div class="plugin-users-wrapper" style="max-width: 1000px; margin: 0 auto; padding: 15px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #257985; padding-bottom: 12px;">
        <div>
            <h2 style="margin: 0; color: #257985; font-size: 22px;">👥 Helpdesk – Benutzerverwaltung</h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Verwalten Sie Kundenkonten, Support-Mitarbeiter (Agenten) und Serviceverträge mit Inklusivminuten.</p>
        </div>
        <div>
            <a href="?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=settings#plugin-settings-drawer-weave-helpdesk" class="btn btn-secondary" style="background: #64748b; color: #fff; text-decoration: none; padding: 7px 12px; border-radius: 4px; font-size: 13px;">
                ⚙️ Einstellungen
            </a>
        </div>
    </div>

    <!-- Benutzer anlegen / bearbeiten Formular -->
    <div class="card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 25px;">
        <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
            <?= $editUser ? '✏️ Benutzer bearbeiten: ' . htmlspecialchars($editUser['name']) : '➕ Neuen Benutzer anlegen (Kunde oder Mitarbeiter)' ?>
        </h4>

        <form action="?tab=tab-plugins&plugin_settings=weave-helpdesk&subaction=save_user#plugin-settings-drawer-weave-helpdesk" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="action" value="helpdesk_save_user">
            <?php if ($editUser): ?>
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($editUser['id']) ?>">
            <?php endif; ?>

            <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 12px;">
                <div style="flex: 2;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Name / Kontaktperson *</label>
                    <input type="text" name="name" class="form-control" required style="width: 100%; padding: 7px;" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" placeholder="z. B. Max Mustermann">
                </div>
                <div style="flex: 2;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">E-Mail-Adresse (Login) *</label>
                    <input type="email" name="email" class="form-control" required style="width: 100%; padding: 7px;" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" placeholder="name@musterfirma.de">
                </div>
                <div style="flex: 1.5;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Rolle im Helpdesk *</label>
                    <select name="role" class="form-control" required style="width: 100%; padding: 7px;">
                        <option value="customer" <?= (($editUser['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Kunde</option>
                        <option value="agent" <?= (($editUser['role'] ?? '') === 'agent') ? 'selected' : '' ?>>Mitarbeiter (Support-Techniker)</option>
                        <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Helpdesk-Administrator</option>
                    </select>
                </div>
            </div>

            <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 12px;">
                <div style="flex: 2;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">
                        Passwort <?= $editUser ? '(leer lassen für unverändert)' : '*' ?>
                    </label>
                    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?> style="width: 100%; padding: 7px;" placeholder="Mindestens 6 Zeichen">
                </div>
                <div style="flex: 1.5;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Kundentyp</label>
                    <select name="customer_type" class="form-control" style="width: 100%; padding: 7px;">
                        <option value="company" <?= (($editUser['customer_type'] ?? '') === 'company') ? 'selected' : '' ?>>Geschäftskunde (Firma)</option>
                        <option value="private" <?= (($editUser['customer_type'] ?? '') === 'private') ? 'selected' : '' ?>>Privatkunde</option>
                    </select>
                </div>
                <div style="flex: 2;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Firmenname</label>
                    <input type="text" name="company_name" class="form-control" style="width: 100%; padding: 7px;" value="<?= htmlspecialchars($editUser['company_name'] ?? '') ?>" placeholder="z. B. Nordwind Logistik & Handel GmbH">
                </div>
            </div>

            <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 14px;">
                <div style="flex: 3;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Adresse / Standort</label>
                    <input type="text" name="address" class="form-control" style="width: 100%; padding: 7px;" value="<?= htmlspecialchars($editUser['address'] ?? '') ?>" placeholder="Musterstraße 42, 12345 Musterstadt">
                </div>
                <div style="flex: 2;">
                    <label style="font-weight: 600; display: block; font-size: 12px; margin-bottom: 4px;">Telefonnummer</label>
                    <input type="text" name="phone" class="form-control" style="width: 100%; padding: 7px;" value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>" placeholder="+49 123 987654">
                </div>
            </div>

            <!-- Vertrags- & Inklusivminuten-Einstellungen -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 14px; margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #257985; cursor: pointer; margin-bottom: 10px;">
                    <input type="checkbox" name="has_contract" value="1" <?= !empty($editUser['has_contract']) ? 'checked' : '' ?> onchange="document.getElementById('contractFields').style.display = this.checked ? 'block' : 'none';">
                    <span>Servicevertrag mit Inklusivminuten hinterlegen</span>
                </label>

                <div id="contractFields" style="display: <?= !empty($editUser['has_contract']) ? 'block' : 'none' ?>;">
                    <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 10px;">
                        <div style="flex: 1.5;">
                            <label style="font-size: 11px; font-weight: 600;">Vertragsnummer</label>
                            <input type="text" name="contract_number" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($editUser['contract_number'] ?? 'WV-2026-001') ?>" placeholder="WV-2026-001">
                        </div>
                        <div style="flex: 2.5;">
                            <label style="font-size: 11px; font-weight: 600;">Vertragsbezeichnung</label>
                            <input type="text" name="contract_title" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($editUser['contract_title'] ?? 'IT/TK Wartung inkl. Service') ?>" placeholder="IT/TK Wartung inkl. Service">
                        </div>
                        <div style="flex: 1.5;">
                            <label style="font-size: 11px; font-weight: 600;">Inklusivminuten (Guthaben)</label>
                            <input type="number" name="contract_minutes_included" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" min="0" step="1" value="<?= (int)($editUser['contract_minutes_included'] ?? 1260) ?>" placeholder="z. B. 1260">
                        </div>
                    </div>

                    <div class="form-row" style="display: flex; gap: 12px;">
                        <div style="flex: 1;">
                            <label style="font-size: 11px; font-weight: 600;">Zeitraum von</label>
                            <input type="date" name="contract_period_from" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($editUser['contract_period_from'] ?? date('Y-09-01')) ?>">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 11px; font-weight: 600;">Zeitraum bis</label>
                            <input type="date" name="contract_period_to" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($editUser['contract_period_to'] ?? date('Y-02-28', strtotime('+1 year'))) ?>">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 11px; font-weight: 600;">Nächste Abrechnung</label>
                            <input type="date" name="contract_next_billing" class="form-control" style="width: 100%; padding: 6px; font-size: 12px;" value="<?= htmlspecialchars($editUser['contract_next_billing'] ?? date('Y-03-01', strtotime('+1 year'))) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn btn-success" style="background: #257985; border: none; padding: 8px 18px; color: #fff; font-weight: 700; border-radius: 4px; cursor: pointer;">
                    💾 <?= $editUser ? 'Änderungen speichern' : 'Benutzer anlegen' ?>
                </button>
                <?php if ($editUser): ?>
                    <a href="?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users#plugin-settings-drawer-weave-helpdesk" class="btn btn-light" style="padding: 8px 14px; text-decoration: none; color: #4a5568;">
                        Abbrechen
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Benutzer-Tabelle -->
    <div class="card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
        <div style="background: #257985; color: #fff; padding: 10px 16px; font-weight: 600; font-size: 14px;">
            Registrierte Helpdesk-Benutzer (<?= count($users) ?>)
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                    <th style="padding: 10px 12px;">Name</th>
                    <th style="padding: 10px 12px;">E-Mail</th>
                    <th style="padding: 10px 12px;">Rolle</th>
                    <th style="padding: 10px 12px;">Firma</th>
                    <th style="padding: 10px 12px;">Vertrag / Minuten</th>
                    <th style="padding: 10px 12px; text-align: right;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #a0aec0;">
                            Noch keine eigenständigen Helpdesk-Benutzer angelegt. Nutzen Sie das Formular oben, um Mitarbeiter oder Kunden anzulegen.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 10px 12px; font-weight: 600;">
                                <?= htmlspecialchars($u['name']) ?>
                            </td>
                            <td style="padding: 10px 12px; color: #4a5568;">
                                <?= htmlspecialchars($u['email']) ?>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span style="background: #fed7d7; color: #c53030; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;">Admin</span>
                                <?php elseif ($u['role'] === 'agent'): ?>
                                    <span style="background: #feebc8; color: #c05621; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700;">Mitarbeiter</span>
                                <?php else: ?>
                                    <span style="background: #e2e8f0; color: #2d3748; padding: 2px 6px; border-radius: 3px; font-size: 11px;">Kunde</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?= !empty($u['company_name']) ? htmlspecialchars($u['company_name']) : '<span style="color:#a0aec0;">-</span>' ?>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?php if (!empty($u['has_contract'])): ?>
                                    <strong style="color: #257985;"><?= (int)$u['contract_minutes_included'] ?> Min.</strong>
                                    <div style="font-size: 11px; color: #718096;"><?= htmlspecialchars($u['contract_number'] ?? '') ?></div>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">Kein Vertrag</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 12px; text-align: right; white-space: nowrap;">
                                <a href="?tab=tab-plugins&plugin_settings=weave-helpdesk&subtab=users&edit_user=<?= urlencode($u['id']) ?>#plugin-settings-drawer-weave-helpdesk" class="btn btn-sm btn-outline-primary" style="padding: 3px 8px; font-size: 11px; margin-right: 4px;">
                                    Bearbeiten
                                </a>
                                <form action="?tab=tab-plugins&plugin_settings=weave-helpdesk&subaction=delete_user#plugin-settings-drawer-weave-helpdesk" method="POST" style="display: inline;" onsubmit="return confirm('Benutzer <?= htmlspecialchars(addslashes($u['name'])) ?> wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="helpdesk_delete_user">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 3px 8px; font-size: 11px; background: none; border: 1px solid #e74c3c; color: #e74c3c; border-radius: 3px; cursor: pointer;">
                                        Löschen
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

