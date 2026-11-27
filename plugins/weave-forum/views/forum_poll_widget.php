<?php
/**
 * ============================================================================
 * WeaveCMS Diskussionsforum – Umfrage-Builder & Widget (Phase 35)
 * ============================================================================
 * Strikt eigenständige UI-Komponenten für Umfragen in Themen und Beiträgen
 * innerhalb von weave-forum (unabhängig von weave-polls).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_forum_render_poll_builder')) {

    /**
     * Rendert den einklappbaren "Umfrage hinzufügen"-Builder für Thema oder Beitrag.
     */
    function weave_forum_render_poll_builder(string $prefix = 'topic', bool $expanded = false): string {
        $t = static fn(string $key, string $default, array $params = []): string => function_exists('__')
            ? __($key, $default, null, $params)
            : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'topic';
        $boxId = 'fpoll-builder-' . $safePrefix;

        ob_start();
        ?>
        <div class="fpoll-builder-wrap" data-fpoll-builder="<?php echo htmlspecialchars($boxId, ENT_QUOTES, 'UTF-8'); ?>"
             style="margin: 16px 0; border: 1px solid #cbd5e1; border-radius: 12px; background: #f8fafc; overflow: hidden;">
            <div style="padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #f1f5f9;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13.5px; color: #0f172a; margin: 0;">
                    <input type="checkbox"
                           name="enable_poll"
                           value="1"
                           class="fpoll-toggle-chk"
                           data-target="<?php echo htmlspecialchars($boxId . '-body', ENT_QUOTES, 'UTF-8'); ?>"
                           <?php echo $expanded ? 'checked' : ''; ?>
                           style="width: 17px; height: 17px; accent-color: var(--primary, #2e6248);">
                    <span class="material-icons" style="font-size: 18px; color: var(--primary, #2e6248);">poll</span>
                    <span><?php echo htmlspecialchars($t('forum.poll.add_title', 'Umfrage hinzufügen (optional)'), ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
                <span style="font-size: 11.5px; color: #64748b;"><?php echo htmlspecialchars($t('forum.poll.vote_hint', 'Direkt im Forum abstimmen'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div id="<?php echo htmlspecialchars($boxId . '-body', ENT_QUOTES, 'UTF-8'); ?>"
                 style="padding: 16px; display: <?php echo $expanded ? 'block' : 'none'; ?>; background: #ffffff; border-top: 1px solid #e2e8f0;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($t('forum.poll.question_label', 'Umfragefrage *'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <input type="text"
                           name="poll_question"
                           maxlength="240"
                           class="kunena-nt-input"
                           style="padding: 9px 12px; font-size: 13.5px;"
                           placeholder="z. B. Welchen Termin bevorzugst du für das Treffen?">
                </div>

                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; margin: 0;">Antwortoptionen (mind. 2)</label>
                        <button type="button"
                                class="kunena-btn-secondary fpoll-add-option"
                                data-list-id="<?php echo htmlspecialchars($boxId . '-options', ENT_QUOTES, 'UTF-8'); ?>"
                                style="padding: 4px 10px; font-size: 11.5px; cursor: pointer;">
                            + Option hinzufügen
                        </button>
                    </div>
                    <div id="<?php echo htmlspecialchars($boxId . '-options', ENT_QUOTES, 'UTF-8'); ?>"
                         style="display: flex; flex-direction: column; gap: 7px;">
                        <div class="fpoll-option-row" style="display: flex; gap: 6px; align-items: center;">
                            <input type="text" name="poll_options[]" maxlength="140" class="kunena-nt-input" style="padding: 8px 12px; font-size: 13px;" placeholder="Antwortoption 1">
                            <button type="button" class="kunena-btn-secondary fpoll-remove-option" style="padding: 6px 10px; color: #dc2626; cursor: pointer;" title="Entfernen">✕</button>
                        </div>
                        <div class="fpoll-option-row" style="display: flex; gap: 6px; align-items: center;">
                            <input type="text" name="poll_options[]" maxlength="140" class="kunena-nt-input" style="padding: 8px 12px; font-size: 13px;" placeholder="Antwortoption 2">
                            <button type="button" class="kunena-btn-secondary fpoll-remove-option" style="padding: 6px 10px; color: #dc2626; cursor: pointer;" title="Entfernen">✕</button>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Auswahlmodus</label>
                        <select name="poll_choice_mode" class="kunena-nt-input fpoll-mode-select" data-max-wrap="<?php echo htmlspecialchars($boxId . '-maxwrap', ENT_QUOTES, 'UTF-8'); ?>" style="padding: 8px 10px; font-size: 13px;">
                            <option value="single">Single-Choice (1 Stimme)</option>
                            <option value="multiple">Multiple-Choice (Mehrfachauswahl)</option>
                        </select>
                    </div>
                    <div id="<?php echo htmlspecialchars($boxId . '-maxwrap', ENT_QUOTES, 'UTF-8'); ?>" style="display: none;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Max. Stimmen je Nutzer</label>
                        <input type="number" name="poll_max_choices" min="1" max="20" value="2" class="kunena-nt-input" style="padding: 8px 10px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Wer darf abstimmen?</label>
                        <select name="poll_min_role_vote" class="kunena-nt-input" style="padding: 8px 10px; font-size: 13px;">
                            <option value="forum_user">Alle angemeldeten Mitglieder</option>
                            <option value="forum_moderator">Nur Moderatoren &amp; Admins</option>
                            <option value="forum_admin">Nur Forum-Admins</option>
                        </select>
                    </div>
                </div>

                <div style="padding: 10px 12px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 12px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700; color: #1e293b; cursor: pointer; margin-bottom: 8px;">
                        <input type="checkbox"
                               name="poll_no_end"
                               value="1"
                               checked
                               class="fpoll-noend-chk"
                               data-date-wrap="<?php echo htmlspecialchars($boxId . '-endwrap', ENT_QUOTES, 'UTF-8'); ?>"
                               style="width: 16px; height: 16px;">
                        <span>Dauerhaft offen lassen (kein Enddatum)</span>
                    </label>
                    <div id="<?php echo htmlspecialchars($boxId . '-endwrap', ENT_QUOTES, 'UTF-8'); ?>" style="display: none;">
                        <label style="display: block; font-size: 12px; color: #475569; margin-bottom: 4px;">Ablaufdatum &amp; -uhrzeit:</label>
                        <input type="datetime-local" name="poll_ends_at" class="kunena-nt-input" style="padding: 7px 10px; font-size: 13px; max-width: 260px;">
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 12.5px; color: #334155;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="poll_allow_vote_change" value="1" style="width: 15px; height: 15px;">
                        <span>Stimmenänderung erlauben (solange Umfrage offen ist)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="poll_is_public" value="1" style="width: 15px; height: 15px;">
                        <span>Öffentliche Abstimmung (Namen der Teilnehmer sichtbar)</span>
                    </label>
                </div>
            </div>
        </div>
        <?php
        return (string)ob_get_clean() . weave_forum_poll_shared_script_once();
    }

    /**
     * Rendert eine Forum-Umfragekarte (offen mit Formular oder geschlossen mit Endauswertung).
     */
    function weave_forum_render_poll_card(array $poll, ?array $currentUser, ?array $topic = null, ?array $board = null, ?int $now = null): string {
        $t = static fn(string $key, string $default, array $params = []): string => function_exists('__')
            ? __($key, $default, null, $params)
            : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);
        $pollId = (string)($poll['id'] ?? '');
        if ($pollId === '') {
            return '';
        }

        $nowTs = $now ?? time();
        $isClosed = ForumPollManager::isPollClosed($poll, $nowTs);
        $endTimeRaw = ForumPollManager::getEffectiveEndTime($poll);
        $endTimeFormatted = $endTimeRaw !== '' && strtotime($endTimeRaw) ? date('d.m.Y H:i', strtotime($endTimeRaw)) . ' Uhr' : '';

        $userVotes = ($currentUser && !empty($currentUser['id']))
            ? ForumPollManager::getUserVotes($pollId, (string)$currentUser['id'])
            : [];
        $hasVoted = !empty($userVotes);
        $allowChange = !empty($poll['allow_vote_change']);
        $canRoleVote = ForumPollManager::userMeetsVoteRole($currentUser, (string)($poll['min_role_vote'] ?? 'forum_user'));
        $canClose = !$isClosed && ForumPollManager::canClosePoll($poll, $currentUser, $topic);

        $canShowVoteForm = !$isClosed && $canRoleVote && (!$hasVoted || $allowChange);
        $results = ForumPollManager::computeResults($pollId, $poll);
        $choiceMode = (($poll['choice_mode'] ?? 'single') === 'multiple') ? 'multiple' : 'single';
        $maxChoices = max(1, (int)($poll['max_choices'] ?? 1));
        $csrf = (string)($_SESSION['csrf_token'] ?? '');
        $cardId = 'fpoll-card-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $pollId);

        $flash = $_SESSION['forum_poll_flash'][$pollId] ?? null;
        if (isset($_SESSION['forum_poll_flash'][$pollId])) {
            unset($_SESSION['forum_poll_flash'][$pollId]);
        }

        ob_start();
        ?>
        <div id="<?php echo htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8'); ?>"
             class="fpoll-card"
             data-poll-id="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>"
             style="margin: 14px 0 18px 0; padding: 18px 20px; border-radius: 12px; border: 1px solid <?php echo $isClosed ? '#cbd5e1' : '#bfdbfe'; ?>; border-left: 5px solid <?php echo $isClosed ? '#64748b' : 'var(--primary, #2e6248)'; ?>; background: #ffffff; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <?php if ($isClosed): ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: #e2e8f0; color: #334155;">
                                <span class="material-icons" style="font-size: 13px;">lock</span>
                                <?php echo htmlspecialchars($t('forum.poll.status_closed', 'Umfrage beendet'), ENT_QUOTES, 'UTF-8'); ?><?php echo $endTimeFormatted !== '' ? ' · ' . htmlspecialchars($endTimeFormatted, ENT_QUOTES, 'UTF-8') : ''; ?>
                            </span>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: #dcfce7; color: #166534;">
                                <span class="material-icons" style="font-size: 13px;">how_to_vote</span>
                                <?php echo $endTimeFormatted !== '' ? ('Läuft bis ' . htmlspecialchars($endTimeFormatted, ENT_QUOTES, 'UTF-8')) : htmlspecialchars($t('forum.poll.open_forever', 'Dauerhaft offen'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                        <span style="font-size: 11px; color: #64748b;">
                            <?php echo $choiceMode === 'multiple' ? ("Multiple-Choice (max. {$maxChoices})") : 'Single-Choice'; ?>
                            <?php if (!empty($poll['is_public'])): ?>
                                · Öffentliche Abstimmung
                            <?php endif; ?>
                        </span>
                    </div>
                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span class="material-icons" style="font-size: 20px; color: var(--primary, #2e6248);">poll</span>
                        <span><?php echo htmlspecialchars((string)$poll['question'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </h4>
                </div>

                <?php if ($canClose): ?>
                    <form method="post" action="?forum_action=close_forum_poll" class="fpoll-close-form" style="margin: 0;">
                        <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="topic_id" value="<?php echo htmlspecialchars((string)($poll['topic_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="kunena-btn-secondary" style="padding: 4px 10px; font-size: 11.5px; cursor: pointer;">
                            <span class="material-icons" style="font-size: 14px;">stop_circle</span> Umfrage schließen
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="fpoll-status" style="<?php echo $flash ? '' : 'display:none;'; ?> margin-bottom: 10px; padding: 8px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; <?php echo !empty($flash['ok']) ? 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;' : 'background:#fef2f2; color:#991b1b; border:1px solid #fecaca;'; ?>">
                <?php echo $flash ? htmlspecialchars((string)($flash['text'] ?? ''), ENT_QUOTES, 'UTF-8') : ''; ?>
            </div>

            <?php if ($canShowVoteForm): ?>
                <form method="post"
                      action="?forum_action=vote_forum_poll"
                      class="fpoll-vote-form"
                      data-fpoll-vote="1"
                      data-card-id="<?php echo htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8'); ?>"
                      data-choice-mode="<?php echo htmlspecialchars($choiceMode, ENT_QUOTES, 'UTF-8'); ?>"
                      data-max-choices="<?php echo (int)$maxChoices; ?>"
                      style="margin-bottom: 14px;">
                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="topic_id" value="<?php echo htmlspecialchars((string)($poll['topic_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

                    <div style="display: flex; flex-direction: column; gap: 7px; margin-bottom: 10px;">
                        <?php foreach ($poll['options'] as $opt): ?>
                            <?php
                                $oid = (string)$opt['id'];
                                $checked = in_array($oid, $userVotes, true);
                            ?>
                            <label style="display: flex; align-items: center; gap: 9px; padding: 8px 12px; border: 1px solid <?php echo $checked ? 'var(--primary, #2e6248)' : '#e2e8f0'; ?>; border-radius: 8px; cursor: pointer; background: <?php echo $checked ? '#f0fdf4' : '#ffffff'; ?>;">
                                <?php if ($choiceMode === 'multiple'): ?>
                                    <input type="checkbox" name="option_ids[]" value="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked ? 'checked' : ''; ?> class="fpoll-opt-input" style="width: 16px; height: 16px; accent-color: var(--primary, #2e6248);">
                                <?php else: ?>
                                    <input type="radio" name="option_ids[]" value="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked ? 'checked' : ''; ?> class="fpoll-opt-input" style="width: 16px; height: 16px; accent-color: var(--primary, #2e6248);">
                                <?php endif; ?>
                                <span style="font-size: 13.5px; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars((string)$opt['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="kunena-btn-primary" style="padding: 7px 16px; font-size: 13px; cursor: pointer;">
                        <span class="material-icons" style="font-size: 15px;">how_to_vote</span>
                        <span><?php echo htmlspecialchars($hasVoted ? $t('forum.poll.update_vote_btn', 'Stimme aktualisieren') : $t('forum.poll.vote_btn', 'Abstimmen'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                </form>
            <?php elseif (!$isClosed && !$currentUser): ?>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 10px; padding: 7px 10px; background: #f8fafc; border-radius: 7px; border: 1px solid #e2e8f0;">
                    <a href="?page=forum&view=login" style="color: var(--primary, #2e6248); font-weight: 700; text-decoration: none;">Jetzt anmelden</a>, um an dieser Umfrage abzustimmen.
                </div>
            <?php elseif (!$isClosed && !$canRoleVote): ?>
                <div style="font-size: 12px; color: #92400e; margin-bottom: 10px; padding: 7px 10px; background: #fffbeb; border-radius: 7px; border: 1px solid #fde68a;">
                    Diese Umfrage ist auf eine bestimmte Forum-Rolle beschränkt.
                </div>
            <?php endif; ?>

            <!-- Ergebnis-Auswertung (immer nach Ende, nach eigener Stimmabgabe oder für lesende Nutzer sichtbar) -->
            <?php if ($isClosed || $hasVoted || !$canShowVoteForm): ?>
                <div class="fpoll-results" style="<?php echo $canShowVoteForm ? 'padding-top: 12px; border-top: 1px solid #e2e8f0;' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; margin-bottom: 8px;">
                        <strong><?php echo $isClosed ? 'Verbindliches Endergebnis' : 'Aktueller Zwischenstand'; ?></strong>
                        <span><?php echo (int)$results['total_voters']; ?> Teilnehmer · <?php echo (int)$results['total_votes']; ?> Stimmen</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 9px;">
                        <?php foreach ($results['options'] as $r): ?>
                            <?php $pct = (float)$r['percent']; ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 3px; color: #1e293b;">
                                    <span><?php echo htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span style="font-weight: 700;"><?php echo number_format($pct, 1, ',', '.'); ?> % <small style="font-weight: 500; color: #64748b;">(<?php echo (int)$r['votes']; ?>)</small></span>
                                </div>
                                <div style="height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo min(100, max(0, $pct)); ?>%; background: <?php echo $isClosed ? '#475569' : 'var(--primary, #2e6248)'; ?>; border-radius: 999px;"></div>
                                </div>
                                <?php if (!empty($poll['is_public']) && $currentUser && !empty($r['voters'])): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        Abgestimmt: <?php echo htmlspecialchars(implode(', ', $r['voters']), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean() . weave_forum_poll_shared_script_once();
    }

    /**
     * CSP-konformes Event-Delegations-Script für Forum-Umfrage-Builder & AJAX-Abstimmung.
     */
    function weave_forum_poll_shared_script_once(): string {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;
        $nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
        return <<<HTML
<script{$nonceAttr}>
(function() {
    if (window.__weaveForumPollsBound) return;
    window.__weaveForumPollsBound = true;

    document.addEventListener('change', function(e) {
        const el = e.target;
        if (!el) return;

        if (el.classList.contains('fpoll-toggle-chk')) {
            const box = document.getElementById(el.getAttribute('data-target') || '');
            if (box) box.style.display = el.checked ? 'block' : 'none';
            return;
        }
        if (el.classList.contains('fpoll-mode-select')) {
            const wrap = document.getElementById(el.getAttribute('data-max-wrap') || '');
            if (wrap) wrap.style.display = (el.value === 'multiple') ? 'block' : 'none';
            return;
        }
        if (el.classList.contains('fpoll-noend-chk')) {
            const wrap = document.getElementById(el.getAttribute('data-date-wrap') || '');
            if (wrap) wrap.style.display = el.checked ? 'none' : 'block';
            return;
        }
        if (el.classList.contains('fpoll-opt-input')) {
            const form = el.closest('form[data-fpoll-vote="1"]');
            if (!form || form.getAttribute('data-choice-mode') !== 'multiple') return;
            const maxChoices = parseInt(form.getAttribute('data-max-choices') || '1', 10);
            const checked = form.querySelectorAll('input.fpoll-opt-input:checked');
            if (checked.length > maxChoices) {
                el.checked = false;
                const card = form.closest('.fpoll-card');
                const statusEl = card ? card.querySelector('.fpoll-status') : null;
                if (statusEl) {
                    statusEl.textContent = 'Maximal ' + maxChoices + ' Auswahlmöglichkeit(en) erlaubt.';
                    statusEl.style.display = 'block';
                    statusEl.style.background = '#fffbeb';
                    statusEl.style.color = '#92400e';
                }
            }
        }
    });

    document.addEventListener('click', function(e) {
        const addBtn = e.target && e.target.closest ? e.target.closest('.fpoll-add-option') : null;
        if (addBtn) {
            const list = document.getElementById(addBtn.getAttribute('data-list-id') || '');
            if (!list) return;
            const count = list.querySelectorAll('.fpoll-option-row').length + 1;
            const row = document.createElement('div');
            row.className = 'fpoll-option-row';
            row.style.cssText = 'display:flex; gap:6px; align-items:center;';
            row.innerHTML =
                '<input type="text" name="poll_options[]" maxlength="140" class="kunena-nt-input" style="padding:8px 12px; font-size:13px;" placeholder="Antwortoption ' + count + '">' +
                '<button type="button" class="kunena-btn-secondary fpoll-remove-option" style="padding:6px 10px; color:#dc2626; cursor:pointer;" title="Entfernen">✕</button>';
            list.appendChild(row);
            return;
        }

        const rmBtn = e.target && e.target.closest ? e.target.closest('.fpoll-remove-option') : null;
        if (rmBtn) {
            const row = rmBtn.closest('.fpoll-option-row');
            const list = row ? row.parentElement : null;
            if (list && list.querySelectorAll('.fpoll-option-row').length > 2) {
                row.remove();
            }
        }
    });

    document.addEventListener('submit', function(e) {
        const closeForm = e.target && e.target.closest ? e.target.closest('form.fpoll-close-form') : null;
        if (closeForm) {
            if (!window.confirm('Möchtest du diese Umfrage wirklich endgültig schließen?')) {
                e.preventDefault();
            }
            return;
        }
        const form = e.target && e.target.closest ? e.target.closest('form[data-fpoll-vote="1"]') : null;
        if (!form || typeof fetch !== 'function') return;
        e.preventDefault();

        const cardId = form.getAttribute('data-card-id') || '';
        const card = cardId ? document.getElementById(cardId) : form.closest('.fpoll-card');
        const statusEl = card ? card.querySelector('.fpoll-status') : null;
        const checked = form.querySelectorAll('input.fpoll-opt-input:checked');
        if (!checked.length) {
            if (statusEl) {
                statusEl.textContent = 'Bitte wähle mindestens eine Option aus.';
                statusEl.style.display = 'block';
                statusEl.style.background = '#fef2f2';
                statusEl.style.color = '#991b1b';
            }
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        const body = new URLSearchParams(new FormData(form));
        body.append('fpoll_ajax', '1');

        fetch(form.getAttribute('action') || '?forum_action=vote_forum_poll', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.html && card) {
                const tmp = document.createElement('div');
                tmp.innerHTML = res.html;
                const nextCard = tmp.querySelector('.fpoll-card');
                if (nextCard) {
                    card.replaceWith(nextCard);
                    return;
                }
            }
            if (statusEl && res && res.message) {
                statusEl.textContent = res.message;
                statusEl.style.display = 'block';
                statusEl.style.background = res.success ? '#dcfce7' : '#fef2f2';
                statusEl.style.color = res.success ? '#166534' : '#991b1b';
            }
            if (btn) btn.disabled = false;
        })
        .catch(function() {
            form.submit();
        });
    });
})();
</script>
HTML;
    }
}
