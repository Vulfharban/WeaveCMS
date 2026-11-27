<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Polls (PollsManager) – Phase 34
 * ============================================================================
 * Verwaltet eigenständige CMS-Umfragen (mehrere parallele Umfragen in Seiten
 * und Sektionen), Antwortoptionen, Zeitsteuerung, Mehrfachstimmenschutz,
 * Ergebnisauswertung sowie CSV/JSON-Exporte über StorageManager (MySQL/JSON).
 *
 * Strikt getrennt von weave-forum.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class PollsManager {

    protected static ?string $customDataFile = null;
    protected static ?string $customVotesFile = null;
    protected static ?array $storeCache = null;
    protected static ?array $votesCache = null;

    /**
     * Leert die internen Request-Caches.
     */
    public static function clearCache(): void {
        self::$storeCache = null;
        self::$votesCache = null;
    }

    /**
     * Ermöglicht isolierte Pfade für automatisierte Tests.
     */
    public static function setTestStorageFiles(?string $dataFile, ?string $votesFile): void {
        self::$customDataFile = $dataFile;
        self::$customVotesFile = $votesFile;
        self::clearCache();
    }

    public static function getDataFilePath(): string {
        return self::$customDataFile ?? (dirname(__DIR__, 2) . '/data/polls_data.json');
    }

    public static function getVotesFilePath(): string {
        return self::$customVotesFile ?? (dirname(__DIR__, 2) . '/data/polls_votes.json');
    }

    public static function getDefaultConfig(bool $generateSalt = false): array {
        return [
            'section_title'    => 'Aktuelle Umfragen',
            'section_subtitle' => 'Nimm an unseren laufenden Umfragen teil und entdecke die Ergebnisse.',
            'show_in_main_nav' => false,
            'hash_salt'        => $generateSalt ? bin2hex(random_bytes(16)) : '',
        ];
    }

    public static function getDefaultStore(): array {
        return [
            'config' => self::getDefaultConfig(true),
            'polls'  => [],
        ];
    }

    protected static function normalizeLoadedStore(array $loaded): array {
        $defaults = self::getDefaultConfig(false);
        $rawCfg = is_array($loaded['config'] ?? null) ? $loaded['config'] : [];
        $loaded['config'] = array_merge($defaults, $rawCfg);
        $needsSave = false;
        if (empty($loaded['config']['hash_salt'])) {
            $loaded['config']['hash_salt'] = bin2hex(random_bytes(16));
            $needsSave = true;
        }
        if (!isset($loaded['polls']) || !is_array($loaded['polls'])) {
            $loaded['polls'] = [];
        }
        if ($needsSave) {
            self::saveStore($loaded);
        }
        return $loaded;
    }

    public static function getStore(): array {
        if (self::$storeCache !== null) {
            return self::$storeCache;
        }

        $file = self::getDataFilePath();

        if (self::$customDataFile === null && class_exists('StorageManager')) {
            $loaded = StorageManager::loadPluginData('polls_data', null, $file);
            if (is_array($loaded)) {
                self::$storeCache = self::normalizeLoadedStore($loaded);
                return self::$storeCache;
            }
        }

        if (file_exists($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) {
                self::$storeCache = self::normalizeLoadedStore($decoded);
                return self::$storeCache;
            }
        }

        $default = self::getDefaultStore();
        self::saveStore($default);
        return self::$storeCache ?? $default;
    }

    public static function saveStore(array $store): bool {
        $ok = false;
        $file = self::getDataFilePath();
        if (self::$customDataFile === null && class_exists('StorageManager')) {
            $ok = StorageManager::savePluginData('polls_data', $store, $file);
        } else {
            $ok = self::writeAtomicJson($file, $store);
        }
        if ($ok) {
            self::$storeCache = $store;
        }
        return $ok;
    }

    public static function getVotesStore(): array {
        if (self::$votesCache !== null) {
            return self::$votesCache;
        }

        $file = self::getVotesFilePath();
        if (self::$customVotesFile === null && class_exists('StorageManager')) {
            $loaded = StorageManager::loadPluginData('polls_votes', null, $file);
            if (is_array($loaded)) {
                self::$votesCache = $loaded;
                return self::$votesCache;
            }
        }
        if (file_exists($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) {
                self::$votesCache = $decoded;
                return self::$votesCache;
            }
        }
        self::$votesCache = [];
        return self::$votesCache;
    }

    public static function saveVotesStore(array $votes): bool {
        $file = self::getVotesFilePath();
        if (self::$customVotesFile === null && class_exists('StorageManager')) {
            $ok = StorageManager::savePluginData('polls_votes', $votes, $file);
        } else {
            $ok = self::writeAtomicJson($file, $votes);
        }
        if ($ok) {
            self::$votesCache = $votes;
        }
        return $ok;
    }

    protected static function writeAtomicJson(string $file, array $data): bool {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(5));
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        return @rename($tmp, $file);
    }

    public static function getConfig(): array {
        $store = self::getStore();
        return $store['config'] ?? self::getDefaultConfig();
    }

    public static function saveConfig(array $config): bool {
        $store = self::getStore();
        $store['config'] = array_merge($store['config'] ?? self::getDefaultConfig(), [
            'section_title'    => trim((string)($config['section_title'] ?? 'Aktuelle Umfragen')),
            'section_subtitle' => trim((string)($config['section_subtitle'] ?? '')),
            'show_in_main_nav' => !empty($config['show_in_main_nav']),
        ]);
        return self::saveStore($store);
    }

    public static function getPolls(): array {
        $store = self::getStore();
        $polls = array_values(is_array($store['polls'] ?? null) ? $store['polls'] : []);
        usort($polls, static function (array $a, array $b): int {
            $sa = (int)($a['sort_order'] ?? 100);
            $sb = (int)($b['sort_order'] ?? 100);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });
        return $polls;
    }

    public static function getPoll(string $id): ?array {
        $cleanId = self::sanitizeId($id);
        if ($cleanId === '') {
            return null;
        }
        $store = self::getStore();
        foreach ($store['polls'] as $poll) {
            if (($poll['id'] ?? '') === $cleanId) {
                return $poll;
            }
        }
        return null;
    }

    /**
     * Liefert alle im Frontend anzeigbaren Umfragen (aktiv, geplant oder beendet – keine Entwürfe/Archiv).
     * Optional filterbar auf bestimmte IDs (in der angegebenen Reihenfolge).
     */
    public static function getPublicPolls(?array $filterIds = null, ?int $now = null): array {
        $all = self::getPolls();
        $nowTs = $now ?? time();

        $byId = [];
        foreach ($all as $poll) {
            $rawStatus = (string)($poll['status'] ?? 'draft');
            if ($rawStatus === 'draft' || $rawStatus === 'archived') {
                continue;
            }
            $eff = self::getEffectiveStatus($poll, $nowTs);
            if ($eff === 'scheduled') {
                continue;
            }
            $byId[$poll['id']] = $poll;
        }

        if ($filterIds === null) {
            return array_values($byId);
        }

        $selected = [];
        foreach ($filterIds as $id) {
            $clean = self::sanitizeId((string)$id);
            if ($clean !== '' && isset($byId[$clean])) {
                $selected[] = $byId[$clean];
            }
        }
        return $selected;
    }

    public static function sanitizeId(string $id): string {
        $s = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower(trim($id)));
        $s = trim((string)preg_replace('/-+/', '-', (string)$s), '-_');
        return substr($s, 0, 64);
    }

    public static function normalizeDateTimeInput(?string $raw): string {
        $val = trim((string)$raw);
        if ($val === '') {
            return '';
        }
        $ts = strtotime($val);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : '';
    }

    public static function savePoll(array $input): array {
        $store = self::getStore();
        $polls = is_array($store['polls'] ?? null) ? $store['polls'] : [];

        $requestedId = self::sanitizeId((string)($input['id'] ?? ''));
        $existingIdx = null;
        if ($requestedId !== '') {
            foreach ($polls as $i => $p) {
                if (($p['id'] ?? '') === $requestedId) {
                    $existingIdx = $i;
                    break;
                }
            }
        }

        $title = trim((string)($input['title'] ?? ''));
        if ($title === '') {
            $title = 'Neue Umfrage';
        }

        if ($requestedId === '') {
            $base = self::sanitizeId($title);
            if ($base === '') {
                $base = 'poll';
            }
            $candidate = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            $requestedId = $candidate;
        }

        $choiceMode = (($input['choice_mode'] ?? 'single') === 'multiple') ? 'multiple' : 'single';
        $maxChoices = max(1, (int)($input['max_choices'] ?? ($choiceMode === 'multiple' ? 2 : 1)));
        if ($choiceMode === 'single') {
            $maxChoices = 1;
        }

        $allowedStatuses = ['draft', 'active', 'closed', 'archived'];
        $rawStatus = (string)($input['status'] ?? 'active');
        $status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'active';

        $allowedVis = ['after_vote', 'live', 'after_end'];
        $rawVis = (string)($input['results_visibility'] ?? 'after_vote');
        $visibility = in_array($rawVis, $allowedVis, true) ? $rawVis : 'after_vote';

        $options = self::parseOptionsInput($input);
        $nowStr = date('Y-m-d H:i:s');
        $createdAt = $existingIdx !== null ? (string)($polls[$existingIdx]['created_at'] ?? $nowStr) : $nowStr;

        $startsAt = self::normalizeDateTimeInput($input['starts_at'] ?? '');
        $endsAt = self::normalizeDateTimeInput($input['ends_at'] ?? '');
        if ($startsAt !== '' && $endsAt !== '' && strtotime($endsAt) < strtotime($startsAt)) {
            [$startsAt, $endsAt] = [$endsAt, $startsAt];
        }

        $poll = [
            'id'                 => $requestedId,
            'title'              => $title,
            'description'        => trim((string)($input['description'] ?? '')),
            'options'            => $options,
            'choice_mode'        => $choiceMode,
            'max_choices'        => $maxChoices,
            'status'             => $status,
            'starts_at'          => $startsAt,
            'ends_at'            => $endsAt,
            'results_visibility' => $visibility,
            'require_login'      => !empty($input['require_login']),
            'sort_order'         => (int)($input['sort_order'] ?? 100),
            'created_at'         => $createdAt,
            'updated_at'         => $nowStr,
        ];

        if ($existingIdx !== null) {
            $polls[$existingIdx] = $poll;
        } else {
            $polls[] = $poll;
        }

        $store['polls'] = array_values($polls);
        self::saveStore($store);
        return $poll;
    }

    protected static function parseOptionsInput(array $input): array {
        $parsed = [];
        if (isset($input['options']) && is_array($input['options'])) {
            $idx = 0;
            foreach ($input['options'] as $opt) {
                $idx++;
                if (is_string($opt)) {
                    $label = trim($opt);
                    if ($label === '') continue;
                    $parsed[] = [
                        'id'         => 'opt_' . $idx,
                        'label'      => $label,
                        'active'     => true,
                        'sort_order' => $idx * 10,
                    ];
                } elseif (is_array($opt)) {
                    $label = trim((string)($opt['label'] ?? ''));
                    if ($label === '') continue;
                    $optId = self::sanitizeId((string)($opt['id'] ?? ''));
                    if ($optId === '') {
                        $optId = 'opt_' . $idx . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
                    }
                    $parsed[] = [
                        'id'         => $optId,
                        'label'      => $label,
                        'active'     => array_key_exists('active', $opt) ? !empty($opt['active']) : true,
                        'sort_order' => (int)($opt['sort_order'] ?? ($idx * 10)),
                    ];
                }
            }
        } elseif (isset($input['option_labels']) && is_array($input['option_labels'])) {
            $labels = $input['option_labels'];
            $ids = is_array($input['option_ids'] ?? null) ? $input['option_ids'] : [];
            $orders = is_array($input['option_orders'] ?? null) ? $input['option_orders'] : [];
            $actives = is_array($input['option_actives'] ?? null) ? $input['option_actives'] : [];

            foreach ($labels as $i => $rawLabel) {
                $label = trim((string)$rawLabel);
                if ($label === '') continue;
                $optId = self::sanitizeId((string)($ids[$i] ?? ''));
                if ($optId === '') {
                    $optId = 'opt_' . ($i + 1) . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
                }
                $isActive = isset($actives[$i]) ? ((string)$actives[$i] === '1' || $actives[$i] === true) : true;
                $parsed[] = [
                    'id'         => $optId,
                    'label'      => $label,
                    'active'     => $isActive,
                    'sort_order' => (int)($orders[$i] ?? (($i + 1) * 10)),
                ];
            }
        }

        usort($parsed, static fn(array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        return array_values($parsed);
    }

    public static function duplicatePoll(string $id): ?array {
        $orig = self::getPoll($id);
        if (!$orig) {
            return null;
        }
        $copy = $orig;
        unset($copy['id']);
        $copy['title'] = $orig['title'] . ' (Kopie)';
        $copy['status'] = 'draft';
        return self::savePoll($copy);
    }

    public static function deletePoll(string $id): bool {
        $cleanId = self::sanitizeId($id);
        $store = self::getStore();
        $before = count($store['polls']);
        $store['polls'] = array_values(array_filter(
            $store['polls'],
            static fn(array $p): bool => ($p['id'] ?? '') !== $cleanId
        ));
        if (count($store['polls']) === $before) {
            return false;
        }
        self::saveStore($store);

        $votes = self::getVotesStore();
        if (isset($votes[$cleanId])) {
            unset($votes[$cleanId]);
            self::saveVotesStore($votes);
        }
        return true;
    }

    public static function setPollStatus(string $id, string $status): bool {
        $allowed = ['draft', 'active', 'closed', 'archived'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $cleanId = self::sanitizeId($id);
        $store = self::getStore();
        $changed = false;
        foreach ($store['polls'] as &$poll) {
            if (($poll['id'] ?? '') === $cleanId) {
                $poll['status'] = $status;
                $poll['updated_at'] = date('Y-m-d H:i:s');
                $changed = true;
                break;
            }
        }
        unset($poll);
        return $changed ? self::saveStore($store) : false;
    }

    public static function resetPollVotes(string $id): bool {
        $cleanId = self::sanitizeId($id);
        $votes = self::getVotesStore();
        $votes[$cleanId] = [];
        if (isset($_SESSION['weave_poll_voted'][$cleanId])) {
            unset($_SESSION['weave_poll_voted'][$cleanId]);
        }
        $cookieKey = 'weave_poll_v_' . $cleanId;
        unset($_COOKIE[$cookieKey]);
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie($cookieKey, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return self::saveVotesStore($votes);
    }

    /**
     * Ermittelt den effektiven Status unter Berücksichtigung von starts_at und ends_at.
     */
    public static function getEffectiveStatus(array $poll, ?int $now = null): string {
        $raw = (string)($poll['status'] ?? 'draft');
        if ($raw === 'draft' || $raw === 'archived' || $raw === 'closed') {
            return $raw;
        }
        $nowTs = $now ?? time();
        $startsAt = trim((string)($poll['starts_at'] ?? ''));
        if ($startsAt !== '') {
            $stTs = strtotime($startsAt);
            if ($stTs !== false && $nowTs < $stTs) {
                return 'scheduled';
            }
        }
        $endsAt = trim((string)($poll['ends_at'] ?? ''));
        if ($endsAt !== '') {
            $endTs = strtotime($endsAt);
            if ($endTs !== false && $nowTs >= $endTs) {
                return 'closed';
            }
        }
        return 'active';
    }

    public static function buildVoterHash(?string $ip = null): string {
        $cfg = self::getConfig();
        $salt = (string)($cfg['hash_salt'] ?? 'weave_polls_salt');
        if ($ip === null) {
            $ip = class_exists('SecurityManager') ? SecurityManager::getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        }
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 80);
        return hash('sha256', $salt . '|' . $ip . '|' . $ua);
    }

    public static function getCurrentUserId(): ?string {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION['forum_user_id'])) {
                return 'forum:' . (string)$_SESSION['forum_user_id'];
            }
            if (!empty($_SESSION['admin_logged_in'])) {
                $adminUser = (string)($_SESSION['admin_username'] ?? $_SESSION['user_id'] ?? 'admin');
                return 'admin:' . $adminUser;
            }
        }
        return null;
    }

    public static function hasVoted(string $pollId, ?string $voterHash = null, ?string $userId = null): bool {
        $cleanId = self::sanitizeId($pollId);
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['weave_poll_voted'][$cleanId])) {
            return true;
        }
        $cookieKey = 'weave_poll_v_' . $cleanId;
        if (!empty($_COOKIE[$cookieKey])) {
            return true;
        }

        $votesStore = self::getVotesStore();
        $pollVotes = $votesStore[$cleanId] ?? [];
        if (!is_array($pollVotes)) {
            return false;
        }

        $hash = $voterHash ?? self::buildVoterHash();
        $uid = $userId ?? self::getCurrentUserId();

        foreach ($pollVotes as $v) {
            if ($uid !== null && !empty($v['user_id']) && $v['user_id'] === $uid) {
                return true;
            }
            if ($hash !== '' && !empty($v['voter_hash']) && hash_equals((string)$v['voter_hash'], $hash)) {
                return true;
            }
        }
        return false;
    }

    public static function canVote(array $poll, ?string $voterHash = null, ?string $userId = null, ?int $now = null): array {
        $eff = self::getEffectiveStatus($poll, $now);
        if ($eff === 'closed') {
            return ['allowed' => false, 'reason' => 'Diese Umfrage ist bereits beendet.'];
        }
        if ($eff === 'scheduled') {
            return ['allowed' => false, 'reason' => 'Diese Umfrage hat noch nicht begonnen.'];
        }
        if ($eff !== 'active') {
            return ['allowed' => false, 'reason' => 'Diese Umfrage ist derzeit nicht aktiv.'];
        }
        $uid = $userId ?? self::getCurrentUserId();
        if (!empty($poll['require_login']) && $uid === null) {
            return ['allowed' => false, 'reason' => 'Für diese Umfrage ist eine Anmeldung erforderlich.'];
        }
        if (self::hasVoted((string)($poll['id'] ?? ''), $voterHash, $uid)) {
            return ['allowed' => false, 'reason' => 'Du hast an dieser Umfrage bereits teilgenommen.'];
        }
        $activeOptions = array_filter($poll['options'] ?? [], static fn(array $o): bool => !empty($o['active']));
        if (empty($activeOptions)) {
            return ['allowed' => false, 'reason' => 'Keine aktiven Antwortoptionen vorhanden.'];
        }
        return ['allowed' => true, 'reason' => ''];
    }

    public static function shouldShowResults(array $poll, bool $hasVoted, ?int $now = null): bool {
        $eff = self::getEffectiveStatus($poll, $now);
        if ($eff === 'closed') {
            return true;
        }
        $vis = (string)($poll['results_visibility'] ?? 'after_vote');
        if ($vis === 'live') {
            return true;
        }
        if ($vis === 'after_vote' && $hasVoted) {
            return true;
        }
        return false;
    }

    public static function castVote(
        string $pollId,
        array $selectedOptionIds,
        ?string $userId = null,
        ?string $voterHash = null,
        ?int $now = null
    ): array {
        $cleanId = self::sanitizeId($pollId);
        $poll = self::getPoll($cleanId);
        if (!$poll) {
            return ['success' => false, 'message' => 'Umfrage nicht gefunden.', 'results' => []];
        }

        $uid = $userId ?? self::getCurrentUserId();
        $hash = $voterHash ?? self::buildVoterHash();
        $check = self::canVote($poll, $hash, $uid, $now);
        if (!$check['allowed']) {
            return [
                'success'     => false,
                'http_status' => 403,
                'message'     => $check['reason'],
                'results'     => self::computeResults($cleanId, $poll),
            ];
        }

        $activeMap = [];
        foreach ($poll['options'] ?? [] as $opt) {
            if (!empty($opt['active'])) {
                $activeMap[(string)$opt['id']] = true;
            }
        }

        $cleanSelected = [];
        foreach ($selectedOptionIds as $rawOptId) {
            $optId = self::sanitizeId((string)$rawOptId);
            if ($optId !== '' && isset($activeMap[$optId]) && !in_array($optId, $cleanSelected, true)) {
                $cleanSelected[] = $optId;
            }
        }

        if (empty($cleanSelected)) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => 'Bitte wähle mindestens eine gültige Antwortoption aus.',
                'results'     => self::computeResults($cleanId, $poll),
            ];
        }

        $choiceMode = (string)($poll['choice_mode'] ?? 'single');
        $maxChoices = max(1, (int)($poll['max_choices'] ?? 1));
        if ($choiceMode === 'single' && count($cleanSelected) > 1) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => 'Bei dieser Umfrage ist nur eine Antwortmöglichkeit zulässig.',
                'results'     => self::computeResults($cleanId, $poll),
            ];
        }
        if ($choiceMode === 'multiple' && count($cleanSelected) > $maxChoices) {
            return [
                'success'     => false,
                'http_status' => 400,
                'message'     => "Es sind maximal {$maxChoices} Antwortmöglichkeiten zulässig.",
                'results'     => self::computeResults($cleanId, $poll),
            ];
        }

        $votesStore = self::getVotesStore();
        if (!isset($votesStore[$cleanId]) || !is_array($votesStore[$cleanId])) {
            $votesStore[$cleanId] = [];
        }
        $votesStore[$cleanId][] = [
            'option_ids' => $cleanSelected,
            'voter_hash' => $hash,
            'user_id'    => $uid,
            'voted_at'   => date('Y-m-d H:i:s', $now ?? time()),
        ];

        if (!self::saveVotesStore($votesStore)) {
            return [
                'success'     => false,
                'http_status' => 500,
                'message'     => 'Stimme konnte nicht gespeichert werden.',
                'results'     => self::computeResults($cleanId, $poll),
            ];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['weave_poll_voted']) || !is_array($_SESSION['weave_poll_voted'])) {
                $_SESSION['weave_poll_voted'] = [];
            }
            $_SESSION['weave_poll_voted'][$cleanId] = true;
        }
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            $cookieKey = 'weave_poll_v_' . $cleanId;
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie($cookieKey, '1', [
                'expires'  => time() + (86400 * 90),
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_COOKIE[$cookieKey] = '1';
        }

        return [
            'success'     => true,
            'http_status' => 200,
            'message'     => 'Vielen Dank für deine Stimme!',
            'results'     => self::computeResults($cleanId, $poll, $votesStore),
        ];
    }

    public static function computeResults(string $pollId, ?array $poll = null, ?array $votesStore = null): array {
        $cleanId = self::sanitizeId($pollId);
        $poll = $poll ?? self::getPoll($cleanId);
        if (!$poll) {
            return [
                'poll_id'      => $cleanId,
                'total_voters' => 0,
                'total_votes'  => 0,
                'options'      => [],
            ];
        }

        $votesStore = $votesStore ?? self::getVotesStore();
        $pollVotes = is_array($votesStore[$cleanId] ?? null) ? $votesStore[$cleanId] : [];
        $counts = [];
        $totalVotes = 0;

        foreach ($pollVotes as $entry) {
            foreach ((array)($entry['option_ids'] ?? []) as $oid) {
                $oid = (string)$oid;
                $counts[$oid] = ($counts[$oid] ?? 0) + 1;
                $totalVotes++;
            }
        }

        $totalVoters = count($pollVotes);
        $optRows = [];
        foreach ($poll['options'] ?? [] as $opt) {
            $oid = (string)($opt['id'] ?? '');
            $c = (int)($counts[$oid] ?? 0);
            $pct = $totalVotes > 0 ? round(($c / $totalVotes) * 100, 1) : 0.0;
            $optRows[] = [
                'id'      => $oid,
                'label'   => (string)($opt['label'] ?? ''),
                'active'  => !empty($opt['active']),
                'votes'   => $c,
                'percent' => $pct,
            ];
        }

        return [
            'poll_id'      => $cleanId,
            'total_voters' => $totalVoters,
            'total_votes'  => $totalVotes,
            'options'      => $optRows,
        ];
    }

    public static function exportResultsCsv(string $pollId): string {
        $poll = self::getPoll($pollId);
        $res = self::computeResults($pollId);
        $lines = [];
        $lines[] = ['Umfrage-ID', 'Titel', 'Status', 'Option-ID', 'Antwortoption', 'Stimmen', 'Anteil (%)', 'Teilnehmer Gesamt'];
        $status = $poll ? self::getEffectiveStatus($poll) : 'unknown';
        $title = $poll['title'] ?? $pollId;
        foreach ($res['options'] as $row) {
            $lines[] = [
                $res['poll_id'],
                $title,
                $status,
                $row['id'],
                $row['label'],
                (string)$row['votes'],
                number_format((float)$row['percent'], 1, '.', ''),
                (string)$res['total_voters'],
            ];
        }

        $sanitizeCsvCell = static function (string $cell): string {
            if ($cell !== '' && preg_match('/^[=+\-@\t\r]/', $cell)) {
                return "'" . $cell;
            }
            return $cell;
        };

        $out = fopen('php://temp', 'r+');
        foreach ($lines as $csvRow) {
            $safeRow = array_map(static fn($val): string => $sanitizeCsvCell((string)$val), $csvRow);
            fputcsv($out, $safeRow, ';', '"', '');
        }
        rewind($out);
        $csv = (string)stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    public static function exportResultsJson(string $pollId): string {
        $poll = self::getPoll($pollId);
        $res = self::computeResults($pollId);
        $payload = [
            'poll'    => $poll,
            'status'  => $poll ? self::getEffectiveStatus($poll) : 'unknown',
            'results' => $res,
            'exported_at' => date('c'),
        ];
        return (string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
