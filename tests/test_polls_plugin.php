<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Weave Polls (Phase 34)
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/php/SecurityManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/plugins/weave-polls/plugin.php';

function assert_true(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_polls_test_' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0755, true);
$tmpData = $tmpDir . DIRECTORY_SEPARATOR . 'polls_data.json';
$tmpVotes = $tmpDir . DIRECTORY_SEPARATOR . 'polls_votes.json';

PollsManager::setTestStorageFiles($tmpData, $tmpVotes);

try {
    // 1. CRUD: Anlegen, Bearbeiten, Duplizieren, Status, Reset, Löschen
    $poll1 = PollsManager::savePoll([
        'id'                 => 'workshop-theme',
        'title'              => 'Welches Workshop-Thema wünschst du dir?',
        'description'        => 'Bitte wähle dein Wunschthema.',
        'choice_mode'        => 'single',
        'status'             => 'active',
        'results_visibility' => 'after_vote',
        'sort_order'         => 10,
        'option_labels'      => ['Weidenkorb', 'Keramikglasur', 'Holzschnitzen'],
        'option_orders'      => [10, 20, 30],
        'option_actives'     => ['1', '1', '0'],
    ]);
    assert_true($poll1['id'] === 'workshop-theme', 'Poll 1 ID mismatch');
    assert_true(count($poll1['options']) === 3, 'Poll 1 must have 3 options');

    $poll2 = PollsManager::savePoll([
        'id'                 => 'multi-times',
        'title'              => 'Welche Wochentage passen dir?',
        'choice_mode'        => 'multiple',
        'max_choices'        => 2,
        'status'             => 'active',
        'results_visibility' => 'live',
        'sort_order'         => 20,
        'option_labels'      => ['Freitag', 'Samstag', 'Sonntag'],
    ]);
    assert_true($poll2['choice_mode'] === 'multiple' && $poll2['max_choices'] === 2, 'Poll 2 multiple-choice settings mismatch');

    $dup = PollsManager::duplicatePoll('workshop-theme');
    assert_true($dup !== null && $dup['status'] === 'draft' && strpos($dup['title'], '(Kopie)') !== false, 'Duplicate poll failed');
    assert_true(PollsManager::setPollStatus($dup['id'], 'closed'), 'setPollStatus failed');
    assert_true(PollsManager::deletePoll($dup['id']), 'deletePoll failed');

    // 2. Single-Choice Validierung & Inaktive Option gesperrt
    $p1Opt1 = $poll1['options'][0]['id'];
    $p1Opt2 = $poll1['options'][1]['id'];
    $p1Opt3Inactive = $poll1['options'][2]['id'];

    $failMultiOnSingle = PollsManager::castVote('workshop-theme', [$p1Opt1, $p1Opt2], null, 'hash_a');
    assert_true(!$failMultiOnSingle['success'], 'Single-choice poll must reject >1 options');

    $failInactive = PollsManager::castVote('workshop-theme', [$p1Opt3Inactive], null, 'hash_a');
    assert_true(!$failInactive['success'], 'Inactive option must be rejected');

    $okSingle = PollsManager::castVote('workshop-theme', [$p1Opt1], 'user_1', 'hash_a');
    assert_true($okSingle['success'], 'Valid single-choice vote should succeed');

    // 3. Mehrfachstimmenschutz via voter_hash & user_id
    $dupHash = PollsManager::castVote('workshop-theme', [$p1Opt2], null, 'hash_a');
    assert_true(!$dupHash['success'], 'Duplicate voter_hash must be blocked');

    $dupUser = PollsManager::castVote('workshop-theme', [$p1Opt2], 'user_1', 'hash_other');
    assert_true(!$dupUser['success'], 'Duplicate user_id must be blocked');

    // 4. Multiple-Choice & max_choices
    $p2Opt1 = $poll2['options'][0]['id'];
    $p2Opt2 = $poll2['options'][1]['id'];
    $p2Opt3 = $poll2['options'][2]['id'];

    $failOverMax = PollsManager::castVote('multi-times', [$p2Opt1, $p2Opt2, $p2Opt3], null, 'hash_m1');
    assert_true(!$failOverMax['success'], 'Multiple-choice > max_choices must be rejected');

    $okMulti = PollsManager::castVote('multi-times', [$p2Opt1, $p2Opt3], null, 'hash_m1');
    assert_true($okMulti['success'], 'Multiple-choice within max_choices must succeed');
    assert_true($okMulti['results']['total_voters'] === 1 && $okMulti['results']['total_votes'] === 2, 'Vote counting mismatch');

    // 5. Zeitsteuerung: Abgelaufene Umfrage sperrt Stimmabgabe und zeigt Ergebnisse für alle
    $expired = PollsManager::savePoll([
        'id'                 => 'expired-poll',
        'title'              => 'Abgelaufene Umfrage',
        'choice_mode'        => 'single',
        'status'             => 'active',
        'ends_at'            => '2020-01-01 00:00:00',
        'results_visibility' => 'after_end',
        'option_labels'      => ['Ja', 'Nein'],
    ]);
    assert_true(PollsManager::getEffectiveStatus($expired) === 'closed', 'Expired poll must report closed');
    assert_true(PollsManager::shouldShowResults($expired, false), 'Closed poll must show results to everyone');
    $failExpired = PollsManager::castVote('expired-poll', [$expired['options'][0]['id']], null, 'hash_exp');
    assert_true(!$failExpired['success'], 'Expired poll must block new votes');

    // 6. Export CSV (inkl. Formula-Injection-Schutz) & JSON + Reset + Cache-Konsistenz
    $formulaPoll = PollsManager::savePoll([
        'id'            => 'csv-formula-test',
        'title'         => '=cmd|test',
        'status'        => 'active',
        'option_labels' => ['=SUM(A1:A2)', '+Normal'],
    ]);
    $csvFormula = PollsManager::exportResultsCsv('csv-formula-test');
    assert_true(strpos($csvFormula, "'=SUM(A1:A2)") !== false && strpos($csvFormula, "'=cmd|test") !== false && strpos($csvFormula, "'+Normal") !== false, 'CSV export must neutralize formula prefixes');
    PollsManager::clearCache();
    assert_true(PollsManager::getPoll('csv-formula-test') !== null, 'Cache reload after clearCache failed');
    PollsManager::deletePoll('csv-formula-test');

    $swappedDates = PollsManager::savePoll([
        'id'            => 'swap-dates-test',
        'title'         => 'Datumsswap',
        'starts_at'     => '2030-05-10 12:00:00',
        'ends_at'       => '2030-05-01 12:00:00',
        'option_labels' => ['A', 'B'],
    ]);
    assert_true($swappedDates['starts_at'] === '2030-05-01 12:00:00' && $swappedDates['ends_at'] === '2030-05-10 12:00:00', 'Reversed poll date bounds should be normalized');
    PollsManager::deletePoll('swap-dates-test');

    $csv = PollsManager::exportResultsCsv('workshop-theme');
    assert_true(strpos($csv, 'Weidenkorb') !== false && strpos($csv, '100.0') !== false, 'CSV export missing expected result row');
    $jsonDecoded = json_decode(PollsManager::exportResultsJson('multi-times'), true);
    assert_true(is_array($jsonDecoded) && ($jsonDecoded['results']['total_voters'] ?? 0) === 1, 'JSON export structure invalid');

    assert_true(PollsManager::resetPollVotes('workshop-theme'), 'resetPollVotes failed');
    $afterReset = PollsManager::computeResults('workshop-theme');
    assert_true($afterReset['total_voters'] === 0 && $afterReset['total_votes'] === 0, 'Reset must zero all votes');

    // 7. Multi-Poll HTML Rendering & Shortcode-Isolation + CSP
    $rawContent = '<p>Hier sind zwei Umfragen:</p>[poll id="workshop-theme"]<p>Doku:</p><code>[poll id="multi-times"]</code><p>Gruppe:</p>[polls ids="workshop-theme,multi-times"]';
    $rendered = PluginManager::applyFilters('content_shortcodes', $rawContent, []);

    assert_true(strpos($rendered, '<code>[poll id="multi-times"]</code>') !== false, 'Shortcode inside <code> must stay literal');
    assert_true(preg_match_all('/class="card weave-poll-card"/', $rendered) === 3, 'Expected 3 isolated poll cards rendered');
    assert_true(strpos($rendered, 'id="wp-workshop-theme-1"') !== false && strpos($rendered, 'id="wp-workshop-theme-2"') !== false, 'Parallel instances of same poll must have unique DOM IDs');
    assert_true(preg_match('/<script\s+nonce="[^"]+"/', $rendered) === 1, 'Shared poll script must carry CSP nonce once');
    assert_true(!preg_match('/\son(click|submit|change)=/i', $rendered), 'Frontend poll HTML must not contain inline event handlers');

    echo "ALL WEAVE-POLLS TESTS PASSED (7/7)\n";
} finally {
    PollsManager::setTestStorageFiles(null, null);
    @unlink($tmpData);
    @unlink($tmpVotes);
    @rmdir($tmpDir);
}
