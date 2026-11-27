<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Polls – Admin Backend (admin/settings.php)
 * ============================================================================
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Token
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once dirname(__DIR__) . '/PollsManager.php';

$subtab = $_GET['polls_subtab'] ?? 'list';
$editId = trim((string)($_GET['edit_poll'] ?? ''));
$polls = PollsManager::getPolls();
$cfg = PollsManager::getConfig();
$editPoll = $editId !== '' ? PollsManager::getPoll($editId) : null;

$adminNotice = $_SESSION['weave_polls_admin_msg'] ?? null;
unset($_SESSION['weave_polls_admin_msg']);

$totalPolls = count($polls);
$activePolls = 0;
$closedPolls = 0;
$totalVotesAll = 0;
$votesStore = PollsManager::getVotesStore();
$resultsByPoll = [];
foreach ($polls as $p) {
    $pid = (string)$p['id'];
    $eff = PollsManager::getEffectiveStatus($p);
    if ($eff === 'active') $activePolls++;
    if ($eff === 'closed') $closedPolls++;
    $res = PollsManager::computeResults($pid, $p, $votesStore);
    $resultsByPoll[$pid] = $res;
    $totalVotesAll += (int)($res['total_voters'] ?? 0);
}

$statusLabels = [
    'active'    => ['Aktiv', '#10b981', 'rgba(16,185,129,0.12)'],
    'scheduled' => ['Geplant', '#3b82f6', 'rgba(59,130,246,0.12)'],
    'draft'     => ['Entwurf', '#f59e0b', 'rgba(245,158,11,0.12)'],
    'closed'    => ['Beendet', '#64748b', 'rgba(100,116,139,0.15)'],
    'archived'  => ['Archiviert', '#94a3b8', 'rgba(148,163,184,0.15)'],
];
?>

<div class="plugin-settings-wrapper" style="max-width: 1120px; margin: 0 auto; padding: 15px;">
    <!-- Header-Banner -->
    <div style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: #fff; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 50px; height: 50px; border-radius: 10px; background: rgba(255,255,255,0.16); display: flex; align-items: center; justify-content: center;">
                <span class="material-icons" style="font-size: 28px; color:#fff;">poll</span>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.38rem; font-weight: 800; color: #fff;">Weave Polls – CMS-Umfragen &amp; Abstimmungen</h3>
                <p style="margin: 4px 0 0 0; opacity: 0.92; font-size: 0.88rem;">
                    Erstelle parallele Umfragen für bestehende Seiten &amp; Sektionen – inklusive Zeitsteuerung, Mehrfachauswahl &amp; CSV/JSON-Export.
                </p>
            </div>
        </div>
        <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=editor#tab-plugins" class="btn" style="background: #fff; color: #0f766e; font-weight: 700; text-decoration: none; padding: 8px 16px; border-radius: 8px;">
            + Neue Umfrage anlegen
        </a>
    </div>

    <?php if (is_array($adminNotice) && !empty($adminNotice['text'])): ?>
        <div style="margin-bottom: 16px; padding: 11px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; background: <?php echo !empty($adminNotice['ok']) ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo !empty($adminNotice['ok']) ? '#16a34a' : '#dc2626'; ?>; color: <?php echo !empty($adminNotice['ok']) ? '#166534' : '#991b1b'; ?>;">
            <?php echo htmlspecialchars((string)$adminNotice['text']); ?>
        </div>
    <?php endif; ?>

    <!-- KPI-Karten -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary, #64748b); font-weight: 700;">Umfragen Gesamt</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--primary, #0f766e);"><?php echo $totalPolls; ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary, #64748b); font-weight: 700;">Aktiv laufend</div>
            <div style="font-size: 20px; font-weight: 800; color: #10b981;"><?php echo $activePolls; ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary, #64748b); font-weight: 700;">Beendet</div>
            <div style="font-size: 20px; font-weight: 800; color: #64748b;"><?php echo $closedPolls; ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary, #64748b); font-weight: 700;">Teilnehmer Gesamt</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--primary, #0f766e);"><?php echo $totalVotesAll; ?></div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color, #e2e8f0); margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=list#tab-plugins"
           class="btn <?php echo ($subtab === 'list') ? 'btn-primary' : 'btn-outline'; ?>"
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            📋 Umfragen (<?php echo $totalPolls; ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=editor#tab-plugins"
           class="btn <?php echo ($subtab === 'editor') ? 'btn-primary' : 'btn-outline'; ?>"
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            ✏️ <?php echo $editPoll ? 'Umfrage bearbeiten' : 'Neue Umfrage'; ?>
        </a>
        <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=settings#tab-plugins"
           class="btn <?php echo ($subtab === 'settings') ? 'btn-primary' : 'btn-outline'; ?>"
           style="padding: 8px 16px; text-decoration: none; font-weight: 700; font-size: 13px;">
            ⚙️ Shortcodes &amp; Sektions-Optionen
        </a>
    </div>

    <?php if ($subtab === 'list'): ?>
        <?php if (empty($polls)): ?>
            <div class="card" style="padding: 2.5rem; text-align: center;">
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">Noch keine CMS-Umfragen angelegt.</p>
                <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=editor#tab-plugins" class="btn btn-primary">Erste Umfrage erstellen</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($polls as $p): ?>
                    <?php
                        $pid = (string)$p['id'];
                        $eff = PollsManager::getEffectiveStatus($p);
                        $badge = $statusLabels[$eff] ?? $statusLabels['draft'];
                        $res = $resultsByPoll[$pid] ?? PollsManager::computeResults($pid, $p, $votesStore);
                    ?>
                    <div class="card" style="padding: 1.25rem 1.5rem; border-left: 4px solid <?php echo $badge[1]; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                    <strong style="font-size: 1.08rem;"><?php echo htmlspecialchars((string)$p['title']); ?></strong>
                                    <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; color: <?php echo $badge[1]; ?>; background: <?php echo $badge[2]; ?>;">
                                        <?php echo htmlspecialchars($badge[0]); ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                        <?php echo ($p['choice_mode'] ?? 'single') === 'multiple' ? ('Multiple-Choice (max. ' . (int)$p['max_choices'] . ')') : 'Single-Choice'; ?>
                                        · <?php echo (int)$res['total_voters']; ?> Teilnehmer
                                        · Sortierung: <?php echo (int)($p['sort_order'] ?? 100); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-secondary); font-family: monospace; margin-top:4px;">
                                    Shortcode: <code>[poll id="<?php echo htmlspecialchars($pid); ?>"]</code>
                                    <?php if (!empty($p['ends_at'])): ?>
                                        · Ende: <?php echo htmlspecialchars((string)$p['ends_at']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <a href="?tab=tab-plugins&plugin_settings=weave-polls&polls_subtab=editor&edit_poll=<?php echo urlencode($pid); ?>#tab-plugins" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">✏️ Bearbeiten</a>

                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="poll_duplicate">
                                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pid); ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">📄 Duplizieren</button>
                                </form>

                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="poll_set_status">
                                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pid); ?>">
                                    <input type="hidden" name="status" value="<?php echo ($eff === 'active') ? 'closed' : 'active'; ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">
                                        <?php echo ($eff === 'active') ? '⏹️ Beenden' : '▶️ Aktivieren'; ?>
                                    </button>
                                </form>

                                <a href="?action=poll_export_csv&poll_id=<?php echo urlencode($pid); ?>&csrf_token=<?php echo urlencode($csrf_token); ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">⬇ CSV</a>
                                <a href="?action=poll_export_json&poll_id=<?php echo urlencode($pid); ?>&csrf_token=<?php echo urlencode($csrf_token); ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">⬇ JSON</a>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Stimmen dieser Umfrage wirklich zurücksetzen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="poll_reset_votes">
                                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pid); ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">🔄 Reset</button>
                                </form>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Umfrage wirklich unwiderruflich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="poll_delete">
                                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pid); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($res['options'])): ?>
                            <div style="margin-top: 0.9rem; padding-top: 0.8rem; border-top: 1px solid var(--border-color, rgba(0,0,0,0.08)); display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 8px;">
                                <?php foreach ($res['options'] as $opt): ?>
                                    <div style="font-size: 0.82rem; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.03);">
                                        <div style="display:flex; justify-content:space-between; gap:6px;">
                                            <span style="<?php echo empty($opt['active']) ? 'text-decoration:line-through; opacity:0.6;' : ''; ?>"><?php echo htmlspecialchars((string)$opt['label']); ?></span>
                                            <strong><?php echo number_format((float)$opt['percent'], 1, ',', '.'); ?>% (<?php echo (int)$opt['votes']; ?>)</strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($subtab === 'editor'): ?>
        <?php
            $formPoll = $editPoll ?? [
                'id'                 => '',
                'title'              => '',
                'description'        => '',
                'choice_mode'        => 'single',
                'max_choices'        => 2,
                'status'             => 'active',
                'starts_at'          => '',
                'ends_at'            => '',
                'results_visibility' => 'after_vote',
                'require_login'      => false,
                'sort_order'         => 10,
                'options'            => [
                    ['id' => '', 'label' => '', 'active' => true, 'sort_order' => 10],
                    ['id' => '', 'label' => '', 'active' => true, 'sort_order' => 20],
                ],
            ];
            $toLocalDt = static function (?string $dt): string {
                $dt = trim((string)$dt);
                if ($dt === '') return '';
                $ts = strtotime($dt);
                return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
            };
        ?>
        <div class="card" style="padding: 1.75rem;">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="poll_save">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$formPoll['id']); ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Frage / Umfrage-Titel *</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars((string)$formPoll['title']); ?>" placeholder="z. B. Welcher Workshop interessiert dich am meisten?">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($formPoll['status'] === 'active') ? 'selected' : ''; ?>>Aktiv</option>
                            <option value="draft" <?php echo ($formPoll['status'] === 'draft') ? 'selected' : ''; ?>>Entwurf</option>
                            <option value="closed" <?php echo ($formPoll['status'] === 'closed') ? 'selected' : ''; ?>>Beendet (nur Ergebnis)</option>
                            <option value="archived" <?php echo ($formPoll['status'] === 'archived') ? 'selected' : ''; ?>>Archiviert (ausgeblendet)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sortierreihenfolge</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo (int)($formPoll['sort_order'] ?? 10); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Beschreibung / Kontext (optional)</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Kurze Erklärung zur Abstimmung..."><?php echo htmlspecialchars((string)$formPoll['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; padding: 1rem; border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px;">
                    <div class="form-group">
                        <label>Auswahlmodus</label>
                        <select name="choice_mode" class="form-control">
                            <option value="single" <?php echo ($formPoll['choice_mode'] === 'single') ? 'selected' : ''; ?>>Single-Choice (Radio)</option>
                            <option value="multiple" <?php echo ($formPoll['choice_mode'] === 'multiple') ? 'selected' : ''; ?>>Multiple-Choice (Checkbox)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Max. Auswahlen (bei Multiple)</label>
                        <input type="number" name="max_choices" class="form-control" min="1" max="20" value="<?php echo max(1, (int)($formPoll['max_choices'] ?? 2)); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ergebnis-Sichtbarkeit</label>
                        <select name="results_visibility" class="form-control">
                            <option value="after_vote" <?php echo ($formPoll['results_visibility'] === 'after_vote') ? 'selected' : ''; ?>>Sofort nach Abstimmung</option>
                            <option value="live" <?php echo ($formPoll['results_visibility'] === 'live') ? 'selected' : ''; ?>>Live für alle</option>
                            <option value="after_end" <?php echo ($formPoll['results_visibility'] === 'after_end') ? 'selected' : ''; ?>>Erst nach Umfrageende</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Startdatum (optional)</label>
                        <input type="datetime-local" name="starts_at" class="form-control" value="<?php echo htmlspecialchars($toLocalDt($formPoll['starts_at'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Enddatum (optional, leer = unbefristet)</label>
                        <input type="datetime-local" name="ends_at" class="form-control" value="<?php echo htmlspecialchars($toLocalDt($formPoll['ends_at'] ?? '')); ?>">
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom: 6px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="require_login" value="1" <?php echo !empty($formPoll['require_login']) ? 'checked' : ''; ?> style="width:17px; height:17px;">
                            Nur angemeldete Nutzer dürfen abstimmen
                        </label>
                    </div>
                </div>

                <!-- Antwortoptionen -->
                <div style="margin-bottom: 1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <label style="font-weight:700; margin:0;">Antwortoptionen</label>
                        <button type="button" class="btn btn-outline" style="padding:4px 12px; font-size:0.82rem;" onclick="weavePollsAddOptionRow()">+ Option hinzufügen</button>
                    </div>
                    <div id="weave-polls-options-list" style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($formPoll['options'] as $idx => $opt): ?>
                            <div class="weave-poll-opt-row" style="display:grid; grid-template-columns: 1fr 90px 100px 42px; gap:8px; align-items:center;">
                                <input type="hidden" name="option_ids[]" value="<?php echo htmlspecialchars((string)($opt['id'] ?? '')); ?>">
                                <input type="text" name="option_labels[]" class="form-control" value="<?php echo htmlspecialchars((string)($opt['label'] ?? '')); ?>" placeholder="Antwortmöglichkeit...">
                                <input type="number" name="option_orders[]" class="form-control" value="<?php echo (int)($opt['sort_order'] ?? (($idx + 1) * 10)); ?>" title="Sortierung">
                                <select name="option_actives[]" class="form-control">
                                    <option value="1" <?php echo !isset($opt['active']) || !empty($opt['active']) ? 'selected' : ''; ?>>Aktiv</option>
                                    <option value="0" <?php echo isset($opt['active']) && empty($opt['active']) ? 'selected' : ''; ?>>Inaktiv</option>
                                </select>
                                <button type="button" class="btn btn-outline" style="padding:6px 8px; color:#ef4444;" onclick="this.closest('.weave-poll-opt-row').remove()" title="Entfernen">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="padding:10px 22px;">💾 Umfrage speichern</button>
            </form>
        </div>

        <script<?php echo class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : ''; ?>>
        function weavePollsAddOptionRow() {
            const list = document.getElementById('weave-polls-options-list');
            if (!list) return;
            const order = (list.querySelectorAll('.weave-poll-opt-row').length + 1) * 10;
            const row = document.createElement('div');
            row.className = 'weave-poll-opt-row';
            row.style.cssText = 'display:grid; grid-template-columns: 1fr 90px 100px 42px; gap:8px; align-items:center;';
            row.innerHTML =
                '<input type="hidden" name="option_ids[]" value="">' +
                '<input type="text" name="option_labels[]" class="form-control" value="" placeholder="Weitere Antwortmöglichkeit...">' +
                '<input type="number" name="option_orders[]" class="form-control" value="' + order + '">' +
                '<select name="option_actives[]" class="form-control"><option value="1" selected>Aktiv</option><option value="0">Inaktiv</option></select>' +
                '<button type="button" class="btn btn-outline" style="padding:6px 8px; color:#ef4444;" onclick="this.closest(\'.weave-poll-opt-row\').remove()">✕</button>';
            list.appendChild(row);
        }
        </script>

    <?php elseif ($subtab === 'settings'): ?>
        <div class="card" style="padding: 1.75rem; margin-bottom: 1.5rem;">
            <h4 style="margin-top:0;">Sektions- &amp; Navigations-Einstellungen</h4>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="poll_save_config">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Sektions-Überschrift (bei Einbindung als CMS-Sektion / [polls])</label>
                    <input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['section_title'] ?? 'Aktuelle Umfragen')); ?>">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Untertitel</label>
                    <input type="text" name="section_subtitle" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['section_subtitle'] ?? '')); ?>">
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="show_in_main_nav" value="1" <?php echo !empty($cfg['show_in_main_nav']) ? 'checked' : ''; ?> style="width:17px; height:17px;">
                        Sektion „Umfragen“ optional im Hauptmenü verlinken (Standard: deaktiviert gemäß Menü-Governance)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top:0;">Verfügbare Shortcodes (Multi-Poll-fähig)</h4>
            <ul style="line-height: 1.8; margin:0; padding-left:1.25rem;">
                <li><code>[poll id="DEINE_ID"]</code> – Einzelne Umfrage in beliebiger Seite oder Beitrag einbetten.</li>
                <li><code>[polls]</code> – Alle aktuell aktiven und beendeten Umfragen parallel im Raster darstellen.</li>
                <li><code>[polls ids="id1,id2,id3"]</code> – Bestimmte Umfrage-Gruppe parallel einbetten.</li>
            </ul>
        </div>
    <?php endif; ?>
</div>
