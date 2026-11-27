<?php
/**
 * ============================================================================
 * WeaveCMS Diskussionsforum – ForumPollManager (Phase 35)
 * ============================================================================
 * Eigenständige Umfrage-Engine direkt für Forum-Themen (topic_id) und
 * Forum-Beiträge (post_id) innerhalb von weave-forum.
 *
 * Architektonisch strikt getrennt vom CMS-Umfrage-Plugin (weave-polls).
 * Unterstützt MySQL, SQLite und den Forum-JSON-Speicher.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class ForumPollManager {

    protected static ?string $testDriver = null;
    protected static ?string $testJsonPath = null;
    protected static ?PDO $testPdo = null;
    protected static ?array $jsonCache = null;

    /**
     * Erlaubt isolierte Testkonfiguration für JSON und SQLite ohne Produktivdaten zu berühren.
     */
    public static function setTestStorage(?string $driver, ?string $jsonPath = null, ?PDO $pdo = null): void {
        self::$testDriver = $driver;
        self::$testJsonPath = $jsonPath;
        self::$testPdo = $pdo;
        self::$jsonCache = null;
        if ($driver === 'sqlite' && $pdo !== null) {
            ForumManager::initSqliteTables($pdo);
        }
    }

    protected static function isDb(): bool {
        if (self::$testDriver !== null) {
            return in_array(self::$testDriver, ['sqlite', 'mysql'], true);
        }
        return ForumManager::isDatabase();
    }

    protected static function getDb(): PDO {
        if (self::$testPdo !== null) {
            return self::$testPdo;
        }
        return ForumManager::getPdo();
    }

    protected static function loadJson(): array {
        if (self::$jsonCache !== null) {
            return self::$jsonCache;
        }
        if (self::$testJsonPath !== null) {
            if (!file_exists(self::$testJsonPath)) {
                $def = ForumManager::getDefaultData();
                self::saveJson($def);
                return self::$jsonCache ?? $def;
            }
            $decoded = json_decode((string)@file_get_contents(self::$testJsonPath), true);
            self::$jsonCache = is_array($decoded) ? $decoded : ForumManager::getDefaultData();
            return self::$jsonCache;
        }
        self::$jsonCache = ForumManager::loadJsonData();
        return self::$jsonCache;
    }

    protected static function saveJson(array $data): bool {
        $ok = false;
        if (self::$testJsonPath !== null) {
            $dir = dirname(self::$testJsonPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }
            $tmp = self::$testJsonPath . '.tmp.' . bin2hex(random_bytes(5));
            if (@file_put_contents($tmp, $json) === false) {
                return false;
            }
            $ok = @rename($tmp, self::$testJsonPath);
        } else {
            $ok = ForumManager::saveJsonData($data);
        }
        if ($ok) {
            self::$jsonCache = $data;
        }
        return $ok;
    }

    /**
     * Erstellt eine neue Umfrage verknüpft mit einem Forum-Thema oder -Beitrag.
     */
    public static function createPoll(string $topicId, string $postId, array $input, array $user, ?int $now = null): array {
        $topicId = trim($topicId);
        $postId = trim($postId);
        if ($topicId === '' || empty($user['id'])) {
            return ['success' => false, 'message' => 'Ungültiger Kontext oder fehlende Anmeldung für die Umfrage.'];
        }

        $question = trim((string)($input['poll_question'] ?? ''));
        if (mb_strlen($question) < 3) {
            return ['success' => false, 'message' => 'Die Umfragefrage muss mindestens 3 Zeichen lang sein.'];
        }
        $question = mb_substr(ForumManager::filterBadWords($question), 0, 240);

        $rawOptions = $input['poll_options'] ?? [];
        if (!is_array($rawOptions)) {
            $rawOptions = [];
        }
        $options = [];
        $seenLabels = [];
        $idx = 0;
        foreach ($rawOptions as $rawOpt) {
            $label = trim((string)$rawOpt);
            if ($label === '') {
                continue;
            }
            $label = mb_substr(ForumManager::filterBadWords($label), 0, 140);
            $normKey = mb_strtolower($label);
            if (isset($seenLabels[$normKey])) {
                continue;
            }
            $seenLabels[$normKey] = true;
            $idx++;
            $options[] = [
                'id'    => 'opt_' . $idx,
                'label' => $label,
            ];
            if (count($options) >= 30) {
                break;
            }
        }

        if (count($options) < 2) {
            return ['success' => false, 'message' => 'Eine Forum-Umfrage benötigt mindestens zwei unterschiedliche Antwortoptionen.'];
        }

        $choiceMode = (($input['poll_choice_mode'] ?? 'single') === 'multiple') ? 'multiple' : 'single';
        $maxChoices = $choiceMode === 'multiple'
            ? min(count($options), max(1, (int)($input['poll_max_choices'] ?? count($options))))
            : 1;

        $allowVoteChange = !empty($input['poll_allow_vote_change']) ? 1 : 0;
        $isPublic = !empty($input['poll_is_public']) ? 1 : 0;

        $allowedRoles = ['forum_user', 'forum_moderator', 'forum_admin'];
        $minRoleVote = (string)($input['poll_min_role_vote'] ?? 'forum_user');
        if (!in_array($minRoleVote, $allowedRoles, true)) {
            $minRoleVote = 'forum_user';
        }

        $nowTs = $now ?? time();
        $endsAt = '';
        $noEnd = !empty($input['poll_no_end']) || trim((string)($input['poll_ends_at'] ?? '')) === '';
        if (!$noEnd) {
            $ts = strtotime(trim((string)$input['poll_ends_at']));
            if ($ts === false) {
                return ['success' => false, 'message' => 'Ungültiges Ablaufdatum für die Umfrage.'];
            }
            if ($ts <= $nowTs) {
                return ['success' => false, 'message' => 'Das Ablaufdatum der Umfrage muss in der Zukunft liegen.'];
            }
            $endsAt = date('Y-m-d H:i:s', $ts);
        }

        $pollId = 'fpoll_' . bin2hex(random_bytes(6));
        $nowStr = date('Y-m-d H:i:s', $nowTs);
        $optionsJson = (string)json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $poll = [
            'id'                => $pollId,
            'topic_id'          => $topicId,
            'post_id'           => $postId,
            'question'          => $question,
            'options'           => $options,
            'options_json'      => $optionsJson,
            'choice_mode'       => $choiceMode,
            'max_choices'       => $maxChoices,
            'allow_vote_change' => $allowVoteChange,
            'is_public'         => $isPublic,
            'min_role_vote'     => $minRoleVote,
            'ends_at'           => $endsAt,
            'is_closed'         => 0,
            'closed_at'         => '',
            'closed_by'         => '',
            'created_by'        => (string)$user['id'],
            'created_at'        => $nowStr,
        ];

        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("
                    INSERT INTO weave_forum_polls
                    (id, topic_id, post_id, question, options_json, choice_mode, max_choices, allow_vote_change, is_public, min_role_vote, ends_at, is_closed, closed_at, closed_by, created_by, created_at)
                    VALUES (:id, :topic_id, :post_id, :question, :options_json, :choice_mode, :max_choices, :allow_vote_change, :is_public, :min_role_vote, :ends_at, 0, '', '', :created_by, :created_at)
                ");
                $stmt->execute([
                    ':id'                => $poll['id'],
                    ':topic_id'          => $poll['topic_id'],
                    ':post_id'           => $poll['post_id'],
                    ':question'          => $poll['question'],
                    ':options_json'      => $poll['options_json'],
                    ':choice_mode'       => $poll['choice_mode'],
                    ':max_choices'       => $poll['max_choices'],
                    ':allow_vote_change' => $poll['allow_vote_change'],
                    ':is_public'         => $poll['is_public'],
                    ':min_role_vote'     => $poll['min_role_vote'],
                    ':ends_at'           => $poll['ends_at'],
                    ':created_by'        => $poll['created_by'],
                    ':created_at'        => $poll['created_at'],
                ]);
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Datenbankfehler beim Speichern der Umfrage: ' . $e->getMessage()];
            }
        } else {
            $data = self::loadJson();
            if (!isset($data['polls']) || !is_array($data['polls'])) {
                $data['polls'] = [];
            }
            $data['polls'][] = $poll;
            if (!self::saveJson($data)) {
                return ['success' => false, 'message' => 'Fehler beim Speichern der Umfrage im JSON-Speicher.'];
            }
        }

        return ['success' => true, 'poll' => $poll, 'poll_id' => $pollId];
    }

    protected static function hydratePollRow(array $row): array {
        if (empty($row['options']) || !is_array($row['options'])) {
            $decoded = json_decode((string)($row['options_json'] ?? '[]'), true);
            $row['options'] = is_array($decoded) ? $decoded : [];
        }
        $row['max_choices'] = max(1, (int)($row['max_choices'] ?? 1));
        $row['allow_vote_change'] = !empty($row['allow_vote_change']) ? 1 : 0;
        $row['is_public'] = !empty($row['is_public']) ? 1 : 0;
        $row['is_closed'] = !empty($row['is_closed']) ? 1 : 0;
        $row['post_id'] = (string)($row['post_id'] ?? '');
        $row['ends_at'] = (string)($row['ends_at'] ?? '');
        $row['closed_at'] = (string)($row['closed_at'] ?? '');
        $row['closed_by'] = (string)($row['closed_by'] ?? '');
        $row['min_role_vote'] = (string)($row['min_role_vote'] ?? 'forum_user');
        return $row;
    }

    public static function getPoll(string $pollId): ?array {
        $pollId = trim($pollId);
        if ($pollId === '') {
            return null;
        }
        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("SELECT * FROM weave_forum_polls WHERE id = ? LIMIT 1");
                $stmt->execute([$pollId]);
                $row = $stmt->fetch();
                return $row ? self::hydratePollRow($row) : null;
            } catch (Exception $e) {
                return null;
            }
        }
        $data = self::loadJson();
        foreach ($data['polls'] ?? [] as $row) {
            if (($row['id'] ?? '') === $pollId) {
                return self::hydratePollRow($row);
            }
        }
        return null;
    }

    /**
     * Liefert alle Umfragen zu einem Thema getrennt nach Themen-Umfragen und Beitrags-Umfragen.
     * @return array{topic_polls: array, post_polls: array<string, array>}
     */
    public static function getPollsForTopic(string $topicId): array {
        $topicId = trim($topicId);
        $rows = [];
        if ($topicId === '') {
            return ['topic_polls' => [], 'post_polls' => []];
        }

        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("SELECT * FROM weave_forum_polls WHERE topic_id = ? ORDER BY created_at ASC");
                $stmt->execute([$topicId]);
                $rows = $stmt->fetchAll() ?: [];
            } catch (Exception $e) {
                $rows = [];
            }
        } else {
            $data = self::loadJson();
            foreach ($data['polls'] ?? [] as $row) {
                if (($row['topic_id'] ?? '') === $topicId) {
                    $rows[] = $row;
                }
            }
            usort($rows, static fn(array $a, array $b): int => strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? '')));
        }

        $topicPolls = [];
        $postPolls = [];
        foreach ($rows as $raw) {
            $p = self::hydratePollRow($raw);
            if ($p['post_id'] === '') {
                $topicPolls[] = $p;
            } else {
                $postPolls[$p['post_id']][] = $p;
            }
        }
        return ['topic_polls' => $topicPolls, 'post_polls' => $postPolls];
    }

    public static function isPollClosed(array $poll, ?int $now = null): bool {
        if (!empty($poll['is_closed'])) {
            return true;
        }
        $endsAt = trim((string)($poll['ends_at'] ?? ''));
        if ($endsAt !== '') {
            $ts = strtotime($endsAt);
            if ($ts !== false && ($now ?? time()) >= $ts) {
                return true;
            }
        }
        return false;
    }

    public static function getEffectiveEndTime(array $poll): string {
        if (!empty($poll['is_closed']) && !empty($poll['closed_at'])) {
            return (string)$poll['closed_at'];
        }
        return (string)($poll['ends_at'] ?? '');
    }

    public static function userMeetsVoteRole(?array $user, string $minRole): bool {
        if (!$user || empty($user['id'])) {
            return false;
        }
        $ranks = [
            'forum_user'      => 1,
            'forum_moderator' => 2,
            'forum_admin'     => 3,
        ];
        $userRank = $ranks[(string)($user['role'] ?? 'forum_user')] ?? 1;
        $reqRank = $ranks[$minRole] ?? 1;
        return $userRank >= $reqRank;
    }

    public static function canClosePoll(array $poll, ?array $user, ?array $topic = null): bool {
        if (!$user || empty($user['id'])) {
            return false;
        }
        $uid = (string)$user['id'];
        if (($poll['created_by'] ?? '') === $uid) {
            return true;
        }
        if ($topic && ($topic['author_id'] ?? '') === $uid) {
            return true;
        }
        if (class_exists('ForumUserManager') && ForumUserManager::userCan('moderate', $user)) {
            return true;
        }
        return in_array((string)($user['role'] ?? ''), ['forum_moderator', 'forum_admin'], true);
    }

    public static function closePoll(string $pollId, array $user, ?array $topic = null, ?int $now = null): array {
        $poll = self::getPoll($pollId);
        if (!$poll) {
            return ['success' => false, 'http_status' => 404, 'message' => 'Umfrage nicht gefunden.'];
        }
        if (!self::canClosePoll($poll, $user, $topic)) {
            return ['success' => false, 'http_status' => 403, 'message' => 'Keine Berechtigung zum Schließen dieser Umfrage.'];
        }
        if (!empty($poll['is_closed'])) {
            return ['success' => true, 'http_status' => 200, 'message' => 'Umfrage ist bereits geschlossen.', 'poll' => $poll];
        }

        $closedAt = date('Y-m-d H:i:s', $now ?? time());
        $closedBy = (string)$user['id'];

        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("UPDATE weave_forum_polls SET is_closed = 1, closed_at = ?, closed_by = ? WHERE id = ?");
                $stmt->execute([$closedAt, $closedBy, $pollId]);
            } catch (Exception $e) {
                return ['success' => false, 'http_status' => 500, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
            }
        } else {
            $data = self::loadJson();
            foreach ($data['polls'] as &$row) {
                if (($row['id'] ?? '') === $pollId) {
                    $row['is_closed'] = 1;
                    $row['closed_at'] = $closedAt;
                    $row['closed_by'] = $closedBy;
                    break;
                }
            }
            unset($row);
            if (!self::saveJson($data)) {
                return ['success' => false, 'http_status' => 500, 'message' => 'Fehler beim Schließen der Umfrage.'];
            }
        }

        if (self::$testDriver === null && ((class_exists('ForumUserManager') && ForumUserManager::userCan('moderate', $user)) || in_array((string)($user['role'] ?? ''), ['forum_moderator', 'forum_admin'], true))) {
            ForumManager::logModeration('close_poll', $user, 'poll', $pollId, 'Forum-Umfrage geschlossen');
        }

        return [
            'success'     => true,
            'http_status' => 200,
            'message'     => 'Umfrage wurde geschlossen. Die Endergebnisse sind jetzt verbindlich.',
            'poll'        => self::getPoll($pollId),
        ];
    }

    public static function getUserVotes(string $pollId, string $userId): array {
        if ($pollId === '' || $userId === '') {
            return [];
        }
        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("SELECT option_id FROM weave_forum_poll_votes WHERE poll_id = ? AND user_id = ?");
                $stmt->execute([$pollId, $userId]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (Exception $e) {
                return [];
            }
        }
        $data = self::loadJson();
        $res = [];
        foreach ($data['poll_votes'] ?? [] as $v) {
            if (($v['poll_id'] ?? '') === $pollId && ($v['user_id'] ?? '') === $userId) {
                $res[] = (string)$v['option_id'];
            }
        }
        return $res;
    }

    /**
     * Speichert die Stimme eines Benutzers mit serverseitiger Zwangs-Prüfung (403 nach Ende/Sperre).
     */
    public static function castVote(string $pollId, array $selectedOptionIds, ?array $user, ?array $board = null, ?int $now = null): array {
        $poll = self::getPoll($pollId);
        if (!$poll) {
            return ['success' => false, 'http_status' => 404, 'message' => 'Umfrage nicht gefunden.', 'results' => []];
        }

        $nowTs = $now ?? time();
        if (self::isPollClosed($poll, $nowTs)) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => 'Diese Umfrage ist beendet. Eine Stimmabgabe ist nicht mehr möglich.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        if (!$user || empty($user['id'])) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => 'Bitte melde dich an, um an dieser Forum-Umfrage teilzunehmen.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        if ($board && !ForumManager::canUserReadBoard($board, $user)) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => 'Kein Zugriff auf diesen Forenbereich.',
                'results'     => [],
            ];
        }

        if (!self::userMeetsVoteRole($user, (string)($poll['min_role_vote'] ?? 'forum_user'))) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => 'Deine Forum-Rolle berechtigt nicht zur Teilnahme an dieser Umfrage.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        $uid = (string)$user['id'];
        $existingVotes = self::getUserVotes($pollId, $uid);
        if (!empty($existingVotes) && empty($poll['allow_vote_change'])) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => 'Du hast bereits abgestimmt. Eine Änderung der Stimme ist in dieser Umfrage deaktiviert.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        $validOptionIds = [];
        foreach ($poll['options'] as $opt) {
            $validOptionIds[(string)$opt['id']] = true;
        }

        $cleanSelected = [];
        foreach ($selectedOptionIds as $rawOptId) {
            $optId = trim((string)$rawOptId);
            if ($optId !== '' && isset($validOptionIds[$optId]) && !in_array($optId, $cleanSelected, true)) {
                $cleanSelected[] = $optId;
            }
        }

        if (empty($cleanSelected)) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => 'Bitte wähle mindestens eine gültige Antwortoption aus.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        $choiceMode = (string)($poll['choice_mode'] ?? 'single');
        $maxChoices = max(1, (int)($poll['max_choices'] ?? 1));
        if ($choiceMode === 'single' && count($cleanSelected) > 1) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => 'In dieser Umfrage ist nur eine Antwortmöglichkeit erlaubt.',
                'results'     => self::computeResults($pollId, $poll),
            ];
        }
        if ($choiceMode === 'multiple' && count($cleanSelected) > $maxChoices) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => "Es sind maximal {$maxChoices} Auswahlmöglichkeiten erlaubt.",
                'results'     => self::computeResults($pollId, $poll),
            ];
        }

        $displayName = class_exists('ForumUserManager')
            ? ForumUserManager::getDisplayName($user, (string)($user['username'] ?? 'Mitglied'))
            : (string)($user['username'] ?? 'Mitglied');
        $votedAt = date('Y-m-d H:i:s', $nowTs);

        if (self::isDb()) {
            try {
                $pdo = self::getDb();
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM weave_forum_poll_votes WHERE poll_id = ? AND user_id = ?")->execute([$pollId, $uid]);
                $stmtIns = $pdo->prepare("INSERT INTO weave_forum_poll_votes (poll_id, user_id, user_name, option_id, voted_at) VALUES (?, ?, ?, ?, ?)");
                foreach ($cleanSelected as $oid) {
                    $stmtIns->execute([$pollId, $uid, $displayName, $oid, $votedAt]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return [
                    'success'     => false,
                    'http_status' => 500,
                    'message'     => 'Datenbankfehler beim Speichern deiner Stimme.',
                    'results'     => self::computeResults($pollId, $poll),
                ];
            }
        } else {
            $data = self::loadJson();
            $votes = is_array($data['poll_votes'] ?? null) ? $data['poll_votes'] : [];
            $votes = array_values(array_filter(
                $votes,
                static fn(array $v): bool => !(($v['poll_id'] ?? '') === $pollId && ($v['user_id'] ?? '') === $uid)
            ));
            foreach ($cleanSelected as $oid) {
                $votes[] = [
                    'poll_id'   => $pollId,
                    'user_id'   => $uid,
                    'user_name' => $displayName,
                    'option_id' => $oid,
                    'voted_at'  => $votedAt,
                ];
            }
            $data['poll_votes'] = $votes;
            if (!self::saveJson($data)) {
                return [
                    'success'     => false,
                    'http_status' => 500,
                    'message'     => 'Stimme konnte nicht im JSON-Speicher gesichert werden.',
                    'results'     => self::computeResults($pollId, $poll),
                ];
            }
        }

        return [
            'success'     => true,
            'http_status' => 200,
            'message'     => !empty($existingVotes) ? 'Deine Stimme wurde aktualisiert.' : 'Vielen Dank für deine Stimme!',
            'results'     => self::computeResults($pollId, $poll),
        ];
    }

    public static function computeResults(string $pollId, ?array $poll = null): array {
        $poll = $poll ?? self::getPoll($pollId);
        if (!$poll) {
            return [
                'poll_id'      => $pollId,
                'total_voters' => 0,
                'total_votes'  => 0,
                'options'      => [],
            ];
        }

        $voteRows = [];
        if (self::isDb()) {
            try {
                $stmt = self::getDb()->prepare("SELECT user_id, user_name, option_id FROM weave_forum_poll_votes WHERE poll_id = ?");
                $stmt->execute([$pollId]);
                $voteRows = $stmt->fetchAll() ?: [];
            } catch (Exception $e) {
                $voteRows = [];
            }
        } else {
            $data = self::loadJson();
            foreach ($data['poll_votes'] ?? [] as $v) {
                if (($v['poll_id'] ?? '') === $pollId) {
                    $voteRows[] = $v;
                }
            }
        }

        $counts = [];
        $votersByOption = [];
        $uniqueVoters = [];
        $totalVotes = 0;

        foreach ($voteRows as $vr) {
            $oid = (string)($vr['option_id'] ?? '');
            $uid = (string)($vr['user_id'] ?? '');
            if ($oid === '') {
                continue;
            }
            $counts[$oid] = ($counts[$oid] ?? 0) + 1;
            $totalVotes++;
            if ($uid !== '') {
                $uniqueVoters[$uid] = true;
            }
            if (!empty($poll['is_public'])) {
                $uname = trim((string)($vr['user_name'] ?? 'Mitglied'));
                $votersByOption[$oid][] = $uname !== '' ? $uname : 'Mitglied';
            }
        }

        $totalVoters = count($uniqueVoters);
        $optRows = [];
        foreach ($poll['options'] ?? [] as $opt) {
            $oid = (string)($opt['id'] ?? '');
            $c = (int)($counts[$oid] ?? 0);
            $pct = $totalVotes > 0 ? round(($c / $totalVotes) * 100, 1) : 0.0;
            $optRows[] = [
                'id'      => $oid,
                'label'   => (string)($opt['label'] ?? ''),
                'votes'   => $c,
                'percent' => $pct,
                'voters'  => !empty($poll['is_public']) ? ($votersByOption[$oid] ?? []) : [],
            ];
        }

        return [
            'poll_id'      => $pollId,
            'total_voters' => $totalVoters,
            'total_votes'  => $totalVotes,
            'options'      => $optRows,
        ];
    }
}
