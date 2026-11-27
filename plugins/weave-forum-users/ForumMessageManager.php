<?php
/**
 * ============================================================================
 * WeaveCMS Forum Private Messages – ForumMessageManager
 * ============================================================================
 * Verwaltet private Nachrichten (PN) zwischen registrierten Community-Mitgliedern.
 * Unterstützt MySQL, SQLite und JSON analog zu ForumUserManager.
 * ============================================================================
 */

class ForumMessageManager {

    protected static bool $tableReady = false;

    public static function getJsonFilePath(): string {
        return dirname(__DIR__, 2) . '/data/forum_messages.json';
    }

    /**
     * Stellt die Tabelle weave_forum_messages in MySQL oder SQLite sicher.
     */
    protected static function ensureTable(PDO $pdo, string $driver): void {
        if (self::$tableReady) {
            return;
        }
        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS weave_forum_messages (
                    id VARCHAR(64) PRIMARY KEY,
                    sender_id VARCHAR(64) NOT NULL,
                    recipient_id VARCHAR(64) NOT NULL,
                    subject VARCHAR(200) NOT NULL,
                    body TEXT NOT NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    deleted_by_sender TINYINT(1) NOT NULL DEFAULT 0,
                    deleted_by_recipient TINYINT(1) NOT NULL DEFAULT 0,
                    created_at VARCHAR(32) NOT NULL,
                    INDEX idx_fpm_recipient (recipient_id),
                    INDEX idx_fpm_sender (sender_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS weave_forum_messages (
                    id TEXT PRIMARY KEY,
                    sender_id TEXT NOT NULL,
                    recipient_id TEXT NOT NULL,
                    subject TEXT NOT NULL,
                    body TEXT NOT NULL,
                    is_read INTEGER NOT NULL DEFAULT 0,
                    deleted_by_sender INTEGER NOT NULL DEFAULT 0,
                    deleted_by_recipient INTEGER NOT NULL DEFAULT 0,
                    created_at TEXT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_fpm_recipient ON weave_forum_messages(recipient_id);
                CREATE INDEX IF NOT EXISTS idx_fpm_sender ON weave_forum_messages(sender_id);
            ");
        }
        self::$tableReady = true;
    }

    /**
     * Öffentliche idempotente Schema-Initialisierung für weave_forum_messages (Phase 42).
     */
    public static function ensureSchema(?PDO $pdo = null): void {
        if ($pdo !== null) {
            $drv = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            self::$tableReady = false;
            self::ensureTable($pdo, $drv === 'sqlite' ? 'sqlite' : 'mysql');
            return;
        }
        $driver = class_exists('ForumUserManager') ? ForumUserManager::getStorageDriver() : 'json';
        if (in_array($driver, ['mysql', 'sqlite'], true) && class_exists('ForumUserManager')) {
            self::$tableReady = false;
            self::ensureTable(ForumUserManager::getPdo(), $driver);
        }
    }

    protected static function loadAllJson(): array {
        $file = self::getJsonFilePath();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected static function saveAllJson(array $messages): bool {
        $file = self::getJsonFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode(array_values($messages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        return @rename($tmp, $file);
    }

    protected static function normalizeRow(array $row): array {
        return [
            'id'                   => (string)($row['id'] ?? ''),
            'sender_id'            => (string)($row['sender_id'] ?? ''),
            'recipient_id'         => (string)($row['recipient_id'] ?? ''),
            'subject'              => (string)($row['subject'] ?? ''),
            'body'                 => (string)($row['body'] ?? ''),
            'is_read'              => !empty($row['is_read']) ? 1 : 0,
            'deleted_by_sender'    => !empty($row['deleted_by_sender']) ? 1 : 0,
            'deleted_by_recipient' => !empty($row['deleted_by_recipient']) ? 1 : 0,
            'created_at'           => (string)($row['created_at'] ?? ''),
        ];
    }

    /**
     * Sendet eine private Nachricht von $senderId an $recipientId.
     *
     * @return array ['success' => bool, 'message' => string, 'id' => string|null]
     */
    public static function sendMessage(string $senderId, string $recipientId, string $subject, string $body): array {
        $senderId = trim($senderId);
        $recipientId = trim($recipientId);
        $subject = trim(preg_replace('/\s+/u', ' ', $subject));
        $body = trim($body);

        if ($senderId === '' || $recipientId === '') {
            return ['success' => false, 'message' => 'Ungültiger Absender oder Empfänger.', 'id' => null];
        }
        if ($senderId === $recipientId) {
            return ['success' => false, 'message' => 'Du kannst dir selbst keine private Nachricht senden.', 'id' => null];
        }
        if (mb_strlen($subject) < 2 || mb_strlen($subject) > 150) {
            return ['success' => false, 'message' => 'Der Betreff muss zwischen 2 und 150 Zeichen lang sein.', 'id' => null];
        }
        if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            return ['success' => false, 'message' => 'Die Nachricht muss zwischen 2 und 5000 Zeichen lang sein.', 'id' => null];
        }

        $recipient = ForumUserManager::getUserById($recipientId);
        if (!$recipient || ($recipient['status'] ?? ForumUserManager::STATUS_ACTIVE) !== ForumUserManager::STATUS_ACTIVE) {
            return ['success' => false, 'message' => 'Der gewünschte Empfänger existiert nicht oder ist nicht aktiv.', 'id' => null];
        }

        $priv = ForumUserManager::getPrivacySettings($recipient);
        if (empty($priv['allow_pm'])) {
            return ['success' => false, 'message' => 'Dieses Mitglied möchte derzeit keine privaten Nachrichten empfangen.', 'id' => null];
        }

        $msg = [
            'id'                   => 'pm_' . bin2hex(random_bytes(8)),
            'sender_id'            => $senderId,
            'recipient_id'         => $recipientId,
            'subject'              => $subject,
            'body'                 => $body,
            'is_read'              => 0,
            'deleted_by_sender'    => 0,
            'deleted_by_recipient' => 0,
            'created_at'           => date('Y-m-d H:i:s'),
        ];

        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("
                    INSERT INTO weave_forum_messages (
                        id, sender_id, recipient_id, subject, body,
                        is_read, deleted_by_sender, deleted_by_recipient, created_at
                    ) VALUES (
                        :id, :sender_id, :recipient_id, :subject, :body,
                        0, 0, 0, :created_at
                    )
                ");
                $stmt->execute([
                    ':id'           => $msg['id'],
                    ':sender_id'    => $msg['sender_id'],
                    ':recipient_id' => $msg['recipient_id'],
                    ':subject'      => $msg['subject'],
                    ':body'         => $msg['body'],
                    ':created_at'   => $msg['created_at'],
                ]);
                return ['success' => true, 'message' => 'Deine private Nachricht wurde gesendet.', 'id' => $msg['id']];
            } catch (Exception $e) {
                error_log('ForumMessageManager sendMessage error: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Nachricht konnte nicht in der Datenbank gespeichert werden.', 'id' => null];
            }
        }

        $all = self::loadAllJson();
        array_unshift($all, $msg);
        if (!self::saveAllJson($all)) {
            return ['success' => false, 'message' => 'Nachricht konnte nicht gespeichert werden.', 'id' => null];
        }
        return ['success' => true, 'message' => 'Deine private Nachricht wurde gesendet.', 'id' => $msg['id']];
    }

    /**
     * Liefert alle Posteingangs-Nachrichten für $userId (neueste zuerst).
     */
    public static function getInbox(string $userId): array {
        if ($userId === '') return [];
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("
                    SELECT * FROM weave_forum_messages
                    WHERE recipient_id = ? AND deleted_by_recipient = 0
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
                return array_map([self::class, 'normalizeRow'], $stmt->fetchAll());
            } catch (Exception $e) {
                return [];
            }
        }

        $out = [];
        foreach (self::loadAllJson() as $m) {
            if (($m['recipient_id'] ?? '') === $userId && empty($m['deleted_by_recipient'])) {
                $out[] = self::normalizeRow($m);
            }
        }
        usort($out, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $out;
    }

    /**
     * Liefert alle gesendeten Nachrichten für $userId (neueste zuerst).
     */
    public static function getSent(string $userId): array {
        if ($userId === '') return [];
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("
                    SELECT * FROM weave_forum_messages
                    WHERE sender_id = ? AND deleted_by_sender = 0
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
                return array_map([self::class, 'normalizeRow'], $stmt->fetchAll());
            } catch (Exception $e) {
                return [];
            }
        }

        $out = [];
        foreach (self::loadAllJson() as $m) {
            if (($m['sender_id'] ?? '') === $userId && empty($m['deleted_by_sender'])) {
                $out[] = self::normalizeRow($m);
            }
        }
        usort($out, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $out;
    }

    /**
     * Lädt eine einzelne Nachricht, sofern $userId Absender oder Empfänger ist.
     */
    public static function getMessage(string $msgId, string $userId): ?array {
        if ($msgId === '' || $userId === '') return null;
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("SELECT * FROM weave_forum_messages WHERE id = ? LIMIT 1");
                $stmt->execute([$msgId]);
                $row = $stmt->fetch();
                if (!$row) return null;
                $m = self::normalizeRow($row);
            } catch (Exception $e) {
                return null;
            }
        } else {
            $m = null;
            foreach (self::loadAllJson() as $row) {
                if (($row['id'] ?? '') === $msgId) {
                    $m = self::normalizeRow($row);
                    break;
                }
            }
            if (!$m) return null;
        }

        if ($m['recipient_id'] === $userId && empty($m['deleted_by_recipient'])) {
            return $m;
        }
        if ($m['sender_id'] === $userId && empty($m['deleted_by_sender'])) {
            return $m;
        }
        return null;
    }

    /**
     * Markiert eine Nachricht im Posteingang als gelesen.
     */
    public static function markAsRead(string $msgId, string $userId): bool {
        if ($msgId === '' || $userId === '') return false;
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("
                    UPDATE weave_forum_messages SET is_read = 1
                    WHERE id = ? AND recipient_id = ?
                ");
                return $stmt->execute([$msgId, $userId]);
            } catch (Exception $e) {
                return false;
            }
        }

        $all = self::loadAllJson();
        $changed = false;
        foreach ($all as &$m) {
            if (($m['id'] ?? '') === $msgId && ($m['recipient_id'] ?? '') === $userId && empty($m['is_read'])) {
                $m['is_read'] = 1;
                $changed = true;
                break;
            }
        }
        unset($m);
        return $changed ? self::saveAllJson($all) : true;
    }

    /**
     * Löscht eine Nachricht aus Sicht von Absender bzw. Empfänger (und endgültig, wenn beide gelöscht haben).
     */
    public static function deleteMessage(string $msgId, string $userId): bool {
        if ($msgId === '' || $userId === '') return false;
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("SELECT * FROM weave_forum_messages WHERE id = ? LIMIT 1");
                $stmt->execute([$msgId]);
                $m = $stmt->fetch();
                if (!$m) return false;

                if (($m['recipient_id'] ?? '') === $userId) {
                    $m['deleted_by_recipient'] = 1;
                }
                if (($m['sender_id'] ?? '') === $userId) {
                    $m['deleted_by_sender'] = 1;
                }

                if (!empty($m['deleted_by_recipient']) && !empty($m['deleted_by_sender'])) {
                    return $pdo->prepare("DELETE FROM weave_forum_messages WHERE id = ?")->execute([$msgId]);
                }

                $upd = $pdo->prepare("
                    UPDATE weave_forum_messages
                    SET deleted_by_recipient = ?, deleted_by_sender = ?
                    WHERE id = ?
                ");
                return $upd->execute([
                    !empty($m['deleted_by_recipient']) ? 1 : 0,
                    !empty($m['deleted_by_sender']) ? 1 : 0,
                    $msgId
                ]);
            } catch (Exception $e) {
                return false;
            }
        }

        $all = self::loadAllJson();
        $updated = [];
        $found = false;
        foreach ($all as $m) {
            if (($m['id'] ?? '') === $msgId) {
                if (($m['recipient_id'] ?? '') === $userId) {
                    $m['deleted_by_recipient'] = 1;
                    $found = true;
                }
                if (($m['sender_id'] ?? '') === $userId) {
                    $m['deleted_by_sender'] = 1;
                    $found = true;
                }
                if (!empty($m['deleted_by_recipient']) && !empty($m['deleted_by_sender'])) {
                    continue;
                }
            }
            $updated[] = $m;
        }
        return $found ? self::saveAllJson($updated) : false;
    }

    /**
     * Liefert die Anzahl ungelesener privater Nachrichten für $userId.
     */
    public static function getUnreadCount(string $userId): int {
        if ($userId === '') return 0;
        $driver = ForumUserManager::getStorageDriver();
        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            try {
                $pdo = ForumUserManager::getPdo();
                self::ensureTable($pdo, $driver);
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM weave_forum_messages
                    WHERE recipient_id = ? AND deleted_by_recipient = 0 AND is_read = 0
                ");
                $stmt->execute([$userId]);
                return (int)$stmt->fetchColumn();
            } catch (Exception $e) {
                return 0;
            }
        }

        $count = 0;
        foreach (self::loadAllJson() as $m) {
            if (($m['recipient_id'] ?? '') === $userId && empty($m['deleted_by_recipient']) && empty($m['is_read'])) {
                $count++;
            }
        }
        return $count;
    }
}
