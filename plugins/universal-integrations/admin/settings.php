<?php
/**
 * Admin-Ansicht: Universelle Integrationen (universal-integrations)
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD')) {
    exit;
}

require_once dirname(__DIR__) . '/IntegrationManager.php';

$integrations = IntegrationManager::getIntegrations($data);
$presets = IntegrationManager::getPresets();
$msg = $_GET['integ_msg'] ?? '';

// Prüfen, ob das alte webtrees-genealogy Plugin aktiv ist
$hasLegacyWebtrees = PluginManager::isPluginActive('webtrees-genealogy', $data);
?>

<div class="card p-6 mb-6">
    <div class="flex items-center justify-between border-b pb-4 mb-4">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span class="material-icons text-primary">hub</span>
                Universelle Integrationen
            </h2>
            <p class="text-sm text-muted">
                Binde beliebige lokale oder externe Web-Anwendungen (Stammbäume, Foren, Wikis, Kalender) flexibel per iFrame, Vorschaukarte oder Inline-Fragment ein.
            </p>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-info">
                <?= count($integrations) ?> Integrationen konfiguriert
            </span>
            <button type="button" class="btn btn-primary btn-sm flex items-center gap-1"
                    onclick="document.getElementById('modal_add_integration').showModal()">
                <span class="material-icons text-sm">add</span> Neue Integration anlegen
            </button>
        </div>
    </div>

    <!-- Legacy Migration Banner falls altes Webtrees Plugin aktiv -->
    <?php if ($hasLegacyWebtrees): ?>
        <div class="alert alert-warning mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-icons text-2xl">account_tree</span>
                <div>
                    <strong>Bestehendes Webtrees-Plugin erkannt!</strong>
                    <div class="text-xs text-muted">Das neue Universal-Integrations-Plugin unterstützt alle Webtrees-Features 1:1 inklusive Shortcode <code>[webtrees]</code>. Du kannst es mit einem Klick überführen und das alte Spezial-Plugin ablösen.</div>
                </div>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="integrations_migrate_webtrees">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    Jetzt ablösen & migrieren
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'migrated_webtrees'): ?>
        <div class="alert alert-success mb-4">
            <span class="material-icons">check_circle</span>
            Webtrees-Konfiguration erfolgreich in die Universelle Integration überführt! Das alte Spezial-Plugin wurde abgelöst.
        </div>
    <?php elseif ($msg === 'saved'): ?>
        <div class="alert alert-success mb-4">Integration erfolgreich gespeichert!</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success mb-4">Integration wurde gelöscht.</div>
    <?php endif; ?>

    <!-- Liste aller Integrationen -->
    <div class="overflow-x-auto mb-8">
        <table class="table-modern w-full">
            <thead>
                <tr>
                    <th>Anwendung / Name</th>
                    <th>Slug & Shortcode</th>
                    <th>Ziel-Adresse (Pfad / URL)</th>
                    <th>Modus</th>
                    <th>Sichtbarkeit</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($integrations)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-muted">
                            <span class="material-icons text-3xl mb-1 text-muted">hub</span>
                            <p>Noch keine Integrationen angelegt. Klicke auf „Neue Integration anlegen“ und wähle eine Vorlage (z. B. Webtrees, phpBB, DokuWiki).</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($integrations as $item): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-primary"><?= htmlspecialchars($item['icon'] ?? 'apps') ?></span>
                                    <div>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        <div class="text-xs text-muted"><?= htmlspecialchars($item['category'] ?? 'Allgemein') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($item['slug']) ?></code>
                                <div class="text-[11px] text-muted font-mono mt-0.5">[integration name="<?= htmlspecialchars($item['slug']) ?>"]</div>
                            </td>
                            <td class="text-xs max-w-xs truncate" title="<?= htmlspecialchars($item['target_url'] ?? '') ?>">
                                <?= htmlspecialchars($item['target_url'] ?? '-') ?>
                            </td>
                            <td>
                                <span class="badge badge-outline text-xs"><?= htmlspecialchars($item['embed_mode'] ?? 'iframe') ?></span>
                            </td>
                            <td>
                                <span class="badge badge-secondary text-xs"><?= htmlspecialchars($item['access_level'] ?? 'public') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($item['active'])): ?>
                                    <span class="badge badge-success text-xs">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary text-xs">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <!-- Test URL Link -->
                                    <a href="<?= htmlspecialchars(IntegrationManager::buildTargetUrl($item)) ?>" target="_blank" 
                                       class="btn btn-outline btn-xs" title="Ziel-URL testen">
                                        <span class="material-icons text-xs">open_in_new</span>
                                    </a>

                                    <!-- Edit Trigger -->
                                    <button type="button" class="btn btn-outline btn-xs"
                                            onclick='openEditIntegrationModal(<?= json_encode($item) ?>)'>
                                        <span class="material-icons text-xs">edit</span>
                                    </button>

                                    <!-- Delete -->
                                    <form method="post" class="inline" onsubmit="return confirm('Integration <?= htmlspecialchars(addslashes($item['name'])) ?> wirklich löschen?')">
                                        <input type="hidden" name="action" value="integrations_delete">
                                        <input type="hidden" name="slug" value="<?= htmlspecialchars($item['slug']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="btn btn-outline btn-xs text-danger" title="Löschen">
                                            <span class="material-icons text-xs">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal: Add Integration Wizard (Schwebendes zentriertes Fenster) -->
    <dialog id="modal_add_integration" class="modal-dialog" style="border: none; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); max-width: 620px; width: 92vw; padding: 0; background: var(--bg-card, #ffffff); overflow: hidden;">
        <form method="post" style="padding: 2rem 2.25rem; max-height: 85vh; overflow-y: auto;">
            <input type="hidden" name="action" value="integrations_save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                <span class="material-icons text-primary">add_circle</span> Neue Integration anlegen
            </h3>

            <!-- Preset Selector -->
            <div class="form-group mb-4">
                <label class="font-semibold block mb-1">Vorlage / Vorbelegte Anwendung:</label>
                <select id="preset_select" onchange="applyPreset(this.value)" class="input-field w-full">
                    <option value="">-- Manuelle Konfiguration (Benutzerdefiniert) --</option>
                    <?php foreach ($presets as $p): ?>
                        <option value="<?= htmlspecialchars($p['id']) ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['category']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-xs text-muted" id="preset_desc"></span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="font-semibold block mb-1">Anzeigename:</label>
                    <input type="text" name="name" id="integ_name" required class="input-field w-full">
                </div>
                <div>
                    <label class="font-semibold block mb-1">Eindeutiger Slug:</label>
                    <input type="text" name="slug" id="integ_slug" required pattern="^[a-z0-9_-]+$" class="input-field w-full">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="font-semibold block mb-1">Ziel-Adresse (Lokaler Pfad wie <code>/webtrees</code> oder URL):</label>
                <input type="text" name="target_url" id="integ_target_url" required class="input-field w-full">
            </div>

            <div class="form-group mb-3">
                <label class="font-semibold block mb-1">URL-Muster (Platzhalter wie <code>{base}</code>, <code>{tree}</code>):</label>
                <input type="text" name="url_pattern" id="integ_url_pattern" value="{base}" class="input-field w-full">
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="font-semibold block mb-1">Einbettungsmodus:</label>
                    <select name="embed_mode" id="integ_embed_mode" class="input-field w-full">
                        <option value="iframe">Standard iFrame</option>
                        <option value="seamless_iframe">Nahtloses iFrame (rahmenlos)</option>
                        <option value="preview_card">Vorschaukarte</option>
                        <option value="inline">Inline-Fragment (serverseitig)</option>
                        <option value="link">Reiner Link / Button</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold block mb-1">iFrame-Höhe (px):</label>
                    <input type="number" name="iframe_height" id="integ_height" value="800" min="300" max="2000" class="input-field w-full">
                </div>
                <div>
                    <label class="font-semibold block mb-1">Icon:</label>
                    <input type="text" name="icon" id="integ_icon" value="apps" class="input-field w-full">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="font-semibold block mb-1">Zugriffsbeschränkung:</label>
                <select name="access_level" id="integ_access" class="input-field w-full">
                    <option value="public">Öffentlich für alle Besucher</option>
                    <option value="forum_user">Nur für angemeldete Forum-Mitglieder</option>
                    <option value="admin">Nur für CMS-Administratoren</option>
                </select>
            </div>

            <div class="flex items-center gap-6 mb-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_as_section" id="integ_section" value="1" checked>
                    <span>Als Sektion auf Startseite verfügbar machen</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="active" id="integ_active" value="1" checked>
                    <span>Aktiv</span>
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" class="btn btn-outline" onclick="this.closest('dialog').close()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Integration speichern</button>
            </div>
        </form>
    </dialog>
</div>

<script>
const PRESETS = <?= json_encode($presets) ?>;

function applyPreset(presetId) {
    if (!presetId || !PRESETS[presetId]) {
        document.getElementById('preset_desc').innerText = '';
        return;
    }
    const p = PRESETS[presetId];
    document.getElementById('integ_name').value = p.name || '';
    document.getElementById('integ_slug').value = p.id || '';
    document.getElementById('integ_target_url').value = p.target_url || '';
    document.getElementById('integ_url_pattern').value = p.url_pattern || '{base}';
    document.getElementById('integ_embed_mode').value = p.default_mode || 'iframe';
    document.getElementById('integ_height').value = p.default_height || 800;
    document.getElementById('integ_icon').value = p.icon || 'apps';
    document.getElementById('preset_desc').innerText = p.description || '';
}

function openEditIntegrationModal(item) {
    document.getElementById('integ_name').value = item.name || '';
    document.getElementById('integ_slug').value = item.slug || '';
    document.getElementById('integ_target_url').value = item.target_url || '';
    document.getElementById('integ_url_pattern').value = item.url_pattern || '{base}';
    document.getElementById('integ_embed_mode').value = item.embed_mode || 'iframe';
    document.getElementById('integ_height').value = item.iframe_height || 800;
    document.getElementById('integ_icon').value = item.icon || 'apps';
    document.getElementById('integ_access').value = item.access_level || 'public';
    document.getElementById('integ_section').checked = !!item.show_as_section;
    document.getElementById('integ_active').checked = !!item.active;
    document.getElementById('modal_add_integration').showModal();
}
</script>
