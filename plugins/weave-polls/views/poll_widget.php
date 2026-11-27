<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Polls – Frontend Widget & Multi-Poll Renderer
 * ============================================================================
 * Unterstützt mehrere parallele Umfragen auf derselben Seite mit
 * eindeutigen Container-IDs, CSRF-Schutz, AJAX-Submit + POST-Fallback
 * und CSP-konformem Event-Handler (kein inline onclick/onsubmit).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_polls_render_single_poll')) {

    function weave_polls_render_single_poll(array $poll, array $opts = []): string {
        static $instanceCounter = 0;
        $instanceCounter++;

        $pollId = (string)($poll['id'] ?? '');
        if ($pollId === '') {
            return '';
        }

        $uid = 'wp-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $pollId) . '-' . $instanceCounter;
        $nowTs = $opts['now'] ?? time();
        $effStatus = PollsManager::getEffectiveStatus($poll, $nowTs);
        $hasVoted = array_key_exists('has_voted', $opts)
            ? (bool)$opts['has_voted']
            : PollsManager::hasVoted($pollId);
        $voteCheck = PollsManager::canVote($poll, null, null, $nowTs);
        $showResults = PollsManager::shouldShowResults($poll, $hasVoted, $nowTs);
        $results = PollsManager::computeResults($pollId);

        $csrf = '';
        if (class_exists('AuthManager') && method_exists('AuthManager', 'getCsrfToken')) {
            $csrf = AuthManager::getCsrfToken();
        } elseif (!empty($_SESSION['csrf_token'])) {
            $csrf = (string)$_SESSION['csrf_token'];
        } else {
            $csrf = bin2hex(random_bytes(16));
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['csrf_token'] = $csrf;
            }
        }

        $choiceMode = (($poll['choice_mode'] ?? 'single') === 'multiple') ? 'multiple' : 'single';
        $maxChoices = max(1, (int)($poll['max_choices'] ?? 1));
        $isClosed = ($effStatus === 'closed');
        $endsAtStr = !empty($poll['ends_at']) ? date('d.m.Y H:i', strtotime((string)$poll['ends_at'])) : '';

        $flash = $_SESSION['weave_poll_flash'][$pollId] ?? null;
        if (isset($_SESSION['weave_poll_flash'][$pollId])) {
            unset($_SESSION['weave_poll_flash'][$pollId]);
        }

        $activeOptions = array_values(array_filter(
            $poll['options'] ?? [],
            static fn(array $o): bool => !empty($o['active'])
        ));

        ob_start();
        ?>
        <div id="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>"
             class="card weave-poll-card"
             data-poll-id="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>"
             style="padding: 1.5rem; border-radius: var(--card-radius, 16px); border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: var(--bg-card, #ffffff); margin-bottom: 1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; margin-bottom:0.65rem;">
                <h3 style="margin:0; font-size:1.2rem; color:var(--text-primary, #1f2937); display:flex; align-items:center; gap:8px;">
                    <span class="material-icons" style="color:var(--primary, #2e6248); font-size:22px;">poll</span>
                    <span><?php echo htmlspecialchars((string)$poll['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                </h3>
                <?php if ($isClosed): ?>
                    <span style="font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:999px; background:rgba(100,116,139,0.15); color:#475569;">
                        Umfrage beendet<?php echo $endsAtStr !== '' ? ' · ' . htmlspecialchars($endsAtStr) : ''; ?>
                    </span>
                <?php elseif ($endsAtStr !== ''): ?>
                    <span style="font-size:0.75rem; font-weight:600; padding:3px 10px; border-radius:999px; background:rgba(16,185,129,0.12); color:#059669;">
                        Läuft bis <?php echo htmlspecialchars($endsAtStr); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($poll['description'])): ?>
                <p style="margin:0 0 1rem 0; font-size:0.92rem; color:var(--text-secondary, #4b5563); line-height:1.5;">
                    <?php echo nl2br(htmlspecialchars((string)$poll['description'], ENT_QUOTES, 'UTF-8')); ?>
                </p>
            <?php endif; ?>

            <div class="weave-poll-status" style="<?php echo $flash ? '' : 'display:none;'; ?> margin-bottom:0.9rem; padding:9px 12px; border-radius:8px; font-size:0.86rem; font-weight:600; <?php echo !empty($flash['ok']) ? 'background:rgba(16,185,129,0.12); color:#065f46; border:1px solid rgba(16,185,129,0.3);' : 'background:rgba(239,68,68,0.12); color:#991b1b; border:1px solid rgba(239,68,68,0.3);'; ?>">
                <?php echo $flash ? htmlspecialchars((string)($flash['text'] ?? ''), ENT_QUOTES, 'UTF-8') : ''; ?>
            </div>

            <?php if ($voteCheck['allowed']): ?>
                <form method="POST"
                      action=""
                      class="weave-poll-form"
                      data-weave-poll-form="1"
                      data-poll-id="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>"
                      data-container-id="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>"
                      data-choice-mode="<?php echo htmlspecialchars($choiceMode, ENT_QUOTES, 'UTF-8'); ?>"
                      data-max-choices="<?php echo (int)$maxChoices; ?>">
                    <input type="hidden" name="weave_poll_action" value="vote">
                    <input type="hidden" name="poll_id" value="<?php echo htmlspecialchars($pollId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

                    <?php if ($choiceMode === 'multiple'): ?>
                        <div style="font-size:0.8rem; color:var(--text-secondary, #6b7280); margin-bottom:0.6rem;">
                            Mehrfachauswahl möglich (max. <?php echo (int)$maxChoices; ?> Option<?php echo $maxChoices > 1 ? 'en' : ''; ?>).
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:1rem;">
                        <?php foreach ($activeOptions as $opt): ?>
                            <?php $optId = (string)$opt['id']; ?>
                            <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border-color, rgba(0,0,0,0.12)); border-radius:10px; cursor:pointer; background:rgba(255,255,255,0.02); transition:border-color 0.15s;">
                                <?php if ($choiceMode === 'multiple'): ?>
                                    <input type="checkbox"
                                           name="option_ids[]"
                                           value="<?php echo htmlspecialchars($optId, ENT_QUOTES, 'UTF-8'); ?>"
                                           class="weave-poll-input"
                                           style="width:17px; height:17px; accent-color:var(--primary, #2e6248);">
                                <?php else: ?>
                                    <input type="radio"
                                           name="option_ids[]"
                                           value="<?php echo htmlspecialchars($optId, ENT_QUOTES, 'UTF-8'); ?>"
                                           class="weave-poll-input"
                                           style="width:17px; height:17px; accent-color:var(--primary, #2e6248);">
                                <?php endif; ?>
                                <span style="font-size:0.95rem; color:var(--text-primary, #1f2937); font-weight:500;">
                                    <?php echo htmlspecialchars((string)$opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding:8px 18px; font-size:0.9rem; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:17px;">how_to_vote</span> Abstimmen
                    </button>
                </form>
            <?php elseif (!$isClosed && !$hasVoted): ?>
                <div style="padding:10px 12px; border-radius:8px; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); color:#92400e; font-size:0.88rem; margin-bottom:0.85rem;">
                    <?php echo htmlspecialchars($voteCheck['reason'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($showResults): ?>
                <div class="weave-poll-results" style="<?php echo $voteCheck['allowed'] ? 'margin-top:1.15rem; padding-top:1rem; border-top:1px solid var(--border-color, rgba(0,0,0,0.08));' : ''; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-secondary, #6b7280); margin-bottom:0.7rem;">
                        <strong>Ergebnis</strong>
                        <span><?php echo (int)$results['total_voters']; ?> Teilnehmer · <?php echo (int)$results['total_votes']; ?> Stimmen</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($results['options'] as $r): ?>
                            <?php $pct = (float)$r['percent']; ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:4px; color:var(--text-primary, #1f2937);">
                                    <span><?php echo htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span style="font-weight:700;"><?php echo number_format($pct, 1, ',', '.'); ?> % <small style="font-weight:400; color:var(--text-secondary, #6b7280);">(<?php echo (int)$r['votes']; ?>)</small></span>
                                </div>
                                <div style="height:8px; border-radius:999px; background:rgba(148,163,184,0.22); overflow:hidden;">
                                    <div style="height:100%; width:<?php echo min(100, max(0, $pct)); ?>%; background:var(--primary, #2e6248); border-radius:999px; transition:width 0.3s ease;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($hasVoted && !$isClosed): ?>
                <div style="padding:10px 12px; border-radius:8px; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); color:#065f46; font-size:0.88rem;">
                    Vielen Dank für deine Teilnahme! Die Ergebnisse werden nach Ablauf dieser Umfrage veröffentlicht.
                </div>
            <?php endif; ?>
        </div>
        <?php
        $cardHtml = (string)ob_get_clean();
        return $cardHtml . weave_polls_render_shared_script_once();
    }

    function weave_polls_render_poll_group(array $polls, array $opts = []): string {
        if (empty($polls)) {
            return '';
        }
        $html = '<div class="weave-polls-group" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; align-items:start;">';
        foreach ($polls as $poll) {
            $html .= weave_polls_render_single_poll($poll, $opts);
        }
        $html .= '</div>';
        return $html;
    }

    function weave_polls_render_shared_script_once(): string {
        static $scriptEmitted = false;
        if ($scriptEmitted) {
            return '';
        }
        $scriptEmitted = true;
        $nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
        return <<<HTML
<script{$nonceAttr}>
(function() {
    if (window.__weavePollsBound) return;
    window.__weavePollsBound = true;

    document.addEventListener('change', function(e) {
        const input = e.target;
        if (!input || !input.classList || !input.classList.contains('weave-poll-input')) return;
        const form = input.closest('form[data-weave-poll-form="1"]');
        if (!form || form.getAttribute('data-choice-mode') !== 'multiple') return;
        const maxChoices = parseInt(form.getAttribute('data-max-choices') || '1', 10);
        const checked = form.querySelectorAll('input.weave-poll-input:checked');
        if (checked.length > maxChoices) {
            input.checked = false;
            const card = form.closest('.weave-poll-card');
            const statusEl = card ? card.querySelector('.weave-poll-status') : null;
            if (statusEl) {
                statusEl.textContent = 'Maximal ' + maxChoices + ' Auswahlmöglichkeit(en) erlaubt.';
                statusEl.style.display = 'block';
                statusEl.style.background = 'rgba(245,158,11,0.15)';
                statusEl.style.color = '#92400e';
            }
        }
    });

    document.addEventListener('submit', function(e) {
        const form = e.target && e.target.closest ? e.target.closest('form[data-weave-poll-form="1"]') : null;
        if (!form || typeof fetch !== 'function') return;
        e.preventDefault();

        const containerId = form.getAttribute('data-container-id');
        const card = containerId ? document.getElementById(containerId) : form.closest('.weave-poll-card');
        const statusEl = card ? card.querySelector('.weave-poll-status') : null;
        const checked = form.querySelectorAll('input.weave-poll-input:checked');
        if (!checked.length) {
            if (statusEl) {
                statusEl.textContent = 'Bitte wähle mindestens eine Option aus.';
                statusEl.style.display = 'block';
                statusEl.style.background = 'rgba(239,68,68,0.12)';
                statusEl.style.color = '#991b1b';
            }
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        const body = new URLSearchParams(new FormData(form));
        body.append('weave_poll_ajax', '1');

        fetch(window.location.href, {
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
                const nextCard = tmp.querySelector('.weave-poll-card');
                if (nextCard) {
                    card.replaceWith(nextCard);
                    return;
                }
            }
            if (statusEl && res && res.message) {
                statusEl.textContent = res.message;
                statusEl.style.display = 'block';
                statusEl.style.background = res.success ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)';
                statusEl.style.color = res.success ? '#065f46' : '#991b1b';
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
