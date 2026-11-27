<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Forum-Umfragen in weave-forum (Phase 35)
 * ============================================================================
 * Prüft JSON- und SQLite-Speicher, Themen- & Beitrags-Umfragen, Rollenrechte,
 * Stimmenänderung, Ablaufdatum / manuelles Schließen (403-Sperre), öffentliche
 * vs. anonyme Auswertung sowie strikte Trennung von weave-polls.
 * ============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/php/SecurityManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/plugins/weave-forum/plugin.php';

function assert_fpoll(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

// 1. Strikte architektonische Trennung von weave-polls
assert_fpoll(!class_exists('PollsManager'), 'weave-forum darf PollsManager (weave-polls) niemals laden');

function run_forum_poll_suite(string $driverLabel, ?PDO $pdo, string $jsonPath): void {
    ForumPollManager::setTestStorage($driverLabel, $jsonPath, $pdo);

    $userAlice = ['id' => 'fusr_alice', 'username' => 'alice', 'role' => 'forum_user'];
    $userBob   = ['id' => 'fusr_bob',   'username' => 'bob',   'role' => 'forum_user'];
    $userMod   = ['id' => 'fusr_mod',   'username' => 'mod01', 'role' => 'forum_moderator'];
    $topicData = ['id' => 'topic_100', 'author_id' => 'fusr_alice', 'board_id' => 'board_welcome'];

    $now = 1800000000; // Fixer Referenzzeitpunkt
    $futureEnd = date('Y-m-d H:i:s', $now + 3600);
    $afterEnd  = $now + 7200;

    // 2. Themen-Umfrage (Single-Choice, dauerhaft offen, keine Stimmenänderung)
    $tpRes = ForumPollManager::createPoll('topic_100', '', [
        'poll_question'          => 'Welches Holz bevorzugst du?',
        'poll_options'           => ['Weide', 'Hasel', 'Rattan'],
        'poll_choice_mode'       => 'single',
        'poll_no_end'            => '1',
        'poll_allow_vote_change' => '0',
        'poll_is_public'         => '1',
        'poll_min_role_vote'     => 'forum_user',
    ], $userAlice, $now);
    assert_fpoll($tpRes['success'], "[{$driverLabel}] Topic-Umfrage anlegen fehlgeschlagen");
    $tpId = $tpRes['poll_id'];

    // Beitrags-Umfrage (Multiple-Choice max. 2, Stimmenänderung erlaubt, mit Ablaufdatum)
    $ppRes = ForumPollManager::createPoll('topic_100', 'post_555', [
        'poll_question'          => 'Wann wollen wir den Kurs machen?',
        'poll_options'           => ['Freitag', 'Samstag', 'Sonntag'],
        'poll_choice_mode'       => 'multiple',
        'poll_max_choices'       => 2,
        'poll_no_end'            => '0',
        'poll_ends_at'           => $futureEnd,
        'poll_allow_vote_change' => '1',
        'poll_is_public'         => '0',
    ], $userBob, $now);
    assert_fpoll($ppRes['success'], "[{$driverLabel}] Beitrags-Umfrage anlegen fehlgeschlagen");
    $ppId = $ppRes['poll_id'];

    // Validierung: Zuordnung zu topic_id bzw. post_id
    $bundle = ForumPollManager::getPollsForTopic('topic_100');
    assert_fpoll(count($bundle['topic_polls']) === 1, "[{$driverLabel}] Erwartet 1 Topic-Umfrage");
    assert_fpoll(count($bundle['post_polls']['post_555'] ?? []) === 1, "[{$driverLabel}] Erwartet 1 Post-Umfrage");

    // 3. Gast-Abstimmung -> harter 403-Block
    $guestVote = ForumPollManager::castVote($tpId, ['opt_1'], null, null, $now);
    assert_fpoll(!$guestVote['success'] && ($guestVote['http_status'] ?? 0) === 403, "[{$driverLabel}] Gast-Vote muss 403 liefern");

    // Abstimmung Single-Choice + Doppelabstimmungs-Block
    $vAlice = ForumPollManager::castVote($tpId, ['opt_1'], $userAlice, null, $now);
    assert_fpoll($vAlice['success'], "[{$driverLabel}] Alice-Vote fehlgeschlagen");

    $vAliceDup = ForumPollManager::castVote($tpId, ['opt_2'], $userAlice, null, $now);
    assert_fpoll(!$vAliceDup['success'] && ($vAliceDup['http_status'] ?? 0) === 403, "[{$driverLabel}] Zweiter Vote ohne allow_vote_change muss 403 liefern");

    // Öffentliche Abstimmung: Wähler sichtbar
    $pubRes = ForumPollManager::computeResults($tpId);
    assert_fpoll(($pubRes['options'][0]['voters'][0] ?? '') === 'alice', "[{$driverLabel}] Öffentliche Umfrage muss Wählernamen zeigen");

    // 4. Multiple-Choice + Stimmenänderung
    $vOver = ForumPollManager::castVote($ppId, ['opt_1', 'opt_2', 'opt_3'], $userAlice, null, $now);
    assert_fpoll(!$vOver['success'] && ($vOver['http_status'] ?? 0) === 400, "[{$driverLabel}] > max_choices muss 400 liefern");

    $vBob1 = ForumPollManager::castVote($ppId, ['opt_1', 'opt_2'], $userBob, null, $now);
    assert_fpoll($vBob1['success'] && $vBob1['results']['total_votes'] === 2, "[{$driverLabel}] Multi-Vote fehlgeschlagen");

    $vBobChange = ForumPollManager::castVote($ppId, ['opt_3'], $userBob, null, $now);
    assert_fpoll($vBobChange['success'] && $vBobChange['results']['total_voters'] === 1 && $vBobChange['results']['total_votes'] === 1, "[{$driverLabel}] Stimmenänderung fehlgeschlagen");
    assert_fpoll(empty($vBobChange['results']['options'][2]['voters']), "[{$driverLabel}] Anonyme Umfrage darf keine Wählernamen liefern");

    // 5. Zeitablauf -> 403 Block & Endanzeige ohne <form>
    $vExpired = ForumPollManager::castVote($ppId, ['opt_1'], $userAlice, null, $afterEnd);
    assert_fpoll(!$vExpired['success'] && ($vExpired['http_status'] ?? 0) === 403, "[{$driverLabel}] Abgelaufene Umfrage muss mit 403 blockieren");

    $closedPollObj = ForumPollManager::getPoll($ppId);
    $htmlMemberClosed = weave_forum_render_poll_card($closedPollObj, $userAlice, $topicData, null, $afterEnd);
    $htmlGuestClosed  = weave_forum_render_poll_card($closedPollObj, null, $topicData, null, $afterEnd);
    foreach ([$htmlMemberClosed, $htmlGuestClosed] as $html) {
        assert_fpoll(strpos($html, '<form') === false, "[{$driverLabel}] Beendete Umfrage darf kein Abstimmformular rendern");
        assert_fpoll(strpos($html, 'Umfrage beendet') !== false, "[{$driverLabel}] Beendete Umfrage muss Hinweis 'Umfrage beendet' zeigen");
        assert_fpoll(strpos($html, 'Verbindliches Endergebnis') !== false, "[{$driverLabel}] Beendete Umfrage muss Endergebnis anzeigen");
        assert_fpoll(strpos($html, '100,0 %') !== false, "[{$driverLabel}] Prozentwerte fehlen in Endanzeige");
    }

    // 6. Manuelles Schließen (Bob != Ersteller/Mod -> 403; Mod/Alice -> OK, danach 403 bei Vote)
    $bobClose = ForumPollManager::closePoll($tpId, $userBob, $topicData, $now);
    assert_fpoll(!$bobClose['success'] && ($bobClose['http_status'] ?? 0) === 403, "[{$driverLabel}] Unberechtigter darf Topic-Umfrage nicht schließen");

    $modClose = ForumPollManager::closePoll($tpId, $userMod, $topicData, $now);
    assert_fpoll($modClose['success'], "[{$driverLabel}] Moderator muss Topic-Umfrage schließen dürfen");

    $voteAfterManualClose = ForumPollManager::castVote($tpId, ['opt_2'], $userBob, null, $now);
    assert_fpoll(!$voteAfterManualClose['success'] && ($voteAfterManualClose['http_status'] ?? 0) === 403, "[{$driverLabel}] Manuell geschlossene Umfrage muss 403 liefern");

    // 7. Rollenbeschränkung (nur Moderator)
    $modOnlyRes = ForumPollManager::createPoll('topic_100', '', [
        'poll_question'      => 'Mod-Entscheidung?',
        'poll_options'       => ['Freigeben', 'Sperren'],
        'poll_min_role_vote' => 'forum_moderator',
        'poll_no_end'        => '1',
    ], $userMod, $now);
    assert_fpoll($modOnlyRes['success'], "[{$driverLabel}] Mod-Umfrage anlegen fehlgeschlagen");
    $userTryModPoll = ForumPollManager::castVote($modOnlyRes['poll_id'], ['opt_1'], $userBob, null, $now);
    assert_fpoll(!$userTryModPoll['success'] && ($userTryModPoll['http_status'] ?? 0) === 403, "[{$driverLabel}] Normaler Nutzer darf nicht in Mod-Umfrage abstimmen");
    $modTryModPoll = ForumPollManager::castVote($modOnlyRes['poll_id'], ['opt_1'], $userMod, null, $now);
    assert_fpoll($modTryModPoll['success'], "[{$driverLabel}] Moderator muss in Mod-Umfrage abstimmen dürfen");

    // 8. Maximale Optionsbegrenzung (30) prüfen
    $manyOpts = [];
    for ($k = 1; $k <= 35; $k++) {
        $manyOpts[] = 'Option ' . $k;
    }
    $capRes = ForumPollManager::createPoll('topic_100', '', [
        'poll_question' => 'Viele Optionen Test',
        'poll_options'  => $manyOpts,
        'poll_no_end'   => '1',
    ], $userAlice, $now);
    assert_fpoll($capRes['success'] && count($capRes['poll']['options']) === 30, "[{$driverLabel}] Forum-Umfrage muss Optionen auf 30 begrenzen");

    ForumPollManager::setTestStorage(null, null, null);
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_fpoll_test_' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0755, true);
$tmpJson = $tmpDir . DIRECTORY_SEPARATOR . 'forum_data.json';

try {
    // Durchlauf 1: JSON-Treiber
    run_forum_poll_suite('json', null, $tmpJson);

    // Durchlauf 2: SQLite-Treiber (In-Memory PDO)
    if (extension_loaded('pdo_sqlite')) {
        $sqlitePdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        run_forum_poll_suite('sqlite', $sqlitePdo, $tmpJson);
    }

    echo "ALL FORUM-POLLS TESTS PASSED (7/7 scenarios across JSON & SQLite)\n";
} finally {
    ForumPollManager::setTestStorage(null, null, null);
    @unlink($tmpJson);
    @rmdir($tmpDir);
}
