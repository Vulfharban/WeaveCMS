<?php
/**
 * ============================================================================
 * WeaveCMS – ApiTokenManager (Phase 31)
 * ============================================================================
 * Verwaltet sichere Bearer-Tokens für die Headless REST-API (/api/v1/).
 * Speichert Token-Hashes (SHA-256), Scopes und Nutzungsstatistiken.
 * ============================================================================
 *
 * @package WeaveCMS
 * @subpackage Security
 */

class ApiTokenManager {
    private static ?string $storageFile = null;

    private static function getStorageFile(): string {
        if (self::$storageFile === null) {
            self::$storageFile = dirname(__DIR__, 2) . '/data/api_tokens.json';
        }
        return self::$storageFile;
    }

    /**
     * Verfügbare Rechte / Scopes für API-Tokens
     */
    public static function getAvailableScopes(): array {
        return [
            '*'             => 'Voller Lesezugriff auf alle Endpunkte',
            'pages:read'    => 'Seiten abfragen (/api/v1/pages)',
            'posts:read'    => 'Blog-Artikel abfragen (/api/v1/posts)',
            'products:read' => 'Shop-Produkte abfragen (/api/v1/products)',
            'courses:read'  => 'Events & Termine abfragen (/api/v1/courses)'
        ];
    }

    /**
     * Lädt alle Token-Definitionen
     */
    public static function getTokens(): array {
        $file = self::getStorageFile();
        if (!file_exists($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert die Token-Definitionen
     */
    private static function saveTokens(array $tokens): bool {
        $file = self::getStorageFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return (bool)@file_put_contents($file, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Erstellt ein neues API-Token.
     * Das Plaintext-Token wird NUR HIER einmalig zurückgegeben!
     */
    public static function createToken(string $name, array $permissions = ['*']): array {
        $name = trim(strip_tags($name));
        if (empty($name)) {
            $name = 'API Token ' . date('Y-m-d H:i');
        }

        // Gültige Scopes filtern
        $validScopes = array_keys(self::getAvailableScopes());
        $filteredPermissions = array_values(array_intersect($permissions, $validScopes));
        if (empty($filteredPermissions)) {
            $filteredPermissions = ['*'];
        }

        // Sicheres Token generieren: Präfix wct_ (WeaveCMS Token) + 40 Zufallszeichen
        $plainToken = 'wct_' . bin2hex(random_bytes(20));
        $tokenHash = hash('sha256', $plainToken);
        $preview = substr($plainToken, 0, 8) . '...' . substr($plainToken, -4);

        $id = 'tok_' . bin2hex(random_bytes(8));
        $record = [
            'id'            => $id,
            'name'          => $name,
            'token_hash'    => $tokenHash,
            'preview'       => $preview,
            'permissions'   => $filteredPermissions,
            'created_at'    => date('c'),
            'last_used_at'  => null,
            'is_active'     => true
        ];

        $tokens = self::getTokens();
        $tokens[] = $record;
        self::saveTokens($tokens);

        if (class_exists('SecurityManager')) {
            SecurityManager::logEvent('API_TOKEN_CREATED', "Neues API-Token '{$name}' ({$preview}) erstellt.", 'INFO');
        }

        return [
            'success'     => true,
            'plain_token' => $plainToken,
            'token'       => $record
        ];
    }

    /**
     * Validiert ein übergebenes Bearer-Token gegen die gespeicherten Token-Hashes.
     */
    public static function validateToken(?string $rawToken, string $requiredPermission = ''): array {
        if (empty($rawToken)) {
            return ['valid' => false, 'error' => 'Kein API-Token übergeben.'];
        }

        // Bearer-Präfix entfernen falls vorhanden
        $token = trim($rawToken);
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        if (empty($token)) {
            return ['valid' => false, 'error' => 'Ungültiges Bearer-Token Format.'];
        }

        $hash = hash('sha256', $token);
        $tokens = self::getTokens();
        $matchedIndex = null;
        $matchedRecord = null;

        foreach ($tokens as $idx => $t) {
            if (!empty($t['is_active']) && hash_equals($t['token_hash'] ?? '', $hash)) {
                $matchedIndex = $idx;
                $matchedRecord = $t;
                break;
            }
        }

        if (!$matchedRecord) {
            return ['valid' => false, 'error' => 'Ungültiges oder inaktives API-Token.'];
        }

        // Berechtigung / Scope prüfen
        if (!empty($requiredPermission)) {
            $perms = $matchedRecord['permissions'] ?? [];
            if (!in_array('*', $perms, true) && !in_array($requiredPermission, $perms, true)) {
                return [
                    'valid' => false,
                    'error' => "Fehlende Berechtigung: Token benötigt den Scope '{$requiredPermission}'."
                ];
            }
        }

        // Last-Used-Timestamp aktualisieren
        $tokens[$matchedIndex]['last_used_at'] = date('c');
        self::saveTokens($tokens);

        return [
            'valid' => true,
            'token' => $matchedRecord
        ];
    }

    /**
     * Löscht ein Token anhand seiner ID
     */
    public static function deleteToken(string $id): bool {
        $tokens = self::getTokens();
        $initialCount = count($tokens);
        $tokens = array_values(array_filter($tokens, fn($t) => ($t['id'] ?? '') !== $id));
        if (count($tokens) !== $initialCount) {
            self::saveTokens($tokens);
            if (class_exists('SecurityManager')) {
                SecurityManager::logEvent('API_TOKEN_DELETED', "API-Token ID '{$id}' gelöscht.", 'INFO');
            }
            return true;
        }
        return false;
    }

    /**
     * Aktiviert oder deaktiviert ein Token
     */
    public static function toggleToken(string $id): bool {
        $tokens = self::getTokens();
        foreach ($tokens as &$t) {
            if (($t['id'] ?? '') === $id) {
                $t['is_active'] = empty($t['is_active']);
                self::saveTokens($tokens);
                return true;
            }
        }
        return false;
    }
}
