<?php
/**
 * ============================================================================
 * WeaveCMS Revision- & Entwurfs-Manager (RevisionManager)
 * ============================================================================
 * Verwaltet Revisions-Historien, atomare Versionierung, Rollbacks, Diff-Vergleiche,
 * Veröffentlichungsstatus (draft, published, scheduled) und sichere Vorschau-Tokens.
 *
 * @package WeaveCMS\Revisions
 * @author WeaveCMS Team
 * @license Proprietary
 */

class RevisionManager {

    /**
     * Maximal archivierte Revisionsstände je Inhaltselement.
     */
    public const MAX_REVISIONS_PER_ITEM = 25;

    /**
     * Ermittelt den absoluten Speicherpfad für Revisionsdateien (Flat-File).
     *
     * @return string
     */
    public static function getStorageDir(): string {
        $dir = dirname(__DIR__, 2) . '/data/revisions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            // Sicherheits-Schutz: Direkter Download unterbinden
            @file_put_contents($dir . '/.htaccess', "# Direct access forbidden\nDeny from all\n");
            @file_put_contents($dir . '/index.html', '');
        }
        return $dir;
    }

    /**
     * Erzeugt ein sicheres Vorschau-Token für ein Inhaltselement.
     *
     * @return string
     */
    public static function generatePreviewToken(): string {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            return md5(uniqid('prev_', true) . microtime(true));
        }
    }

    /**
     * Prüft, ob ein Inhaltselement im Frontend für den aktuellen Besucher sichtbar ist.
     *
     * @param array $item Das Daten-Array des Elements (Page, BlogPost, etc.)
     * @param string|null $providedToken Optional übergebener Vorschau-Token (?preview=...)
     * @return bool True wenn sichtbar, sonst false
     */
    public static function isItemVisible(array $item, ?string $providedToken = null): bool {
        $status = $item['status'] ?? 'published';

        // 1. Regulär veröffentlicht
        if ($status === 'published') {
            return true;
        }

        // 2. Zeitgesteuerte Veröffentlichung (scheduled)
        if ($status === 'scheduled') {
            $publishAt = $item['publish_at'] ?? '';
            if (!empty($publishAt)) {
                $publishTime = strtotime($publishAt);
                if ($publishTime !== false && time() >= $publishTime) {
                    return true; // Veröffentlichungszeitpunkt ist erreicht
                }
            }
        }

        // 3. Admin-Sitzung aktiv
        if (!empty($_SESSION['admin_logged_in'])) {
            return true;
        }

        // 4. Gültiger Vorschau-Token
        if ($providedToken !== null && $providedToken !== '') {
            $expectedToken = $item['preview_token'] ?? '';
            if (!empty($expectedToken) && hash_equals($expectedToken, $providedToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft, ob das Element gerade im Vorschau-Modus (Entwurf/Geplant) betrachtet wird.
     *
     * @param array $item
     * @param string|null $providedToken
     * @return bool
     */
    public static function isPreviewMode(array $item, ?string $providedToken = null): bool {
        $status = $item['status'] ?? 'published';
        if ($status === 'published') {
            return false;
        }
        if ($status === 'scheduled') {
            $publishAt = $item['publish_at'] ?? '';
            if (!empty($publishAt) && time() >= strtotime($publishAt)) {
                return false;
            }
        }
        return self::isItemVisible($item, $providedToken);
    }

    /**
     * Speichert einen neuen Revisionsstand für ein Element ab.
     *
     * @param string $itemType z. B. 'page', 'blog', 'text'
     * @param string $itemId Eindeutige ID des Elements
     * @param array $itemData Der vollständige Datensatz des Elements
     * @param string|null $author Name des Bearbeiters
     * @param string $note Optionale Änderungsnotiz
     * @return string Die generierte Revisions-ID
     */
    public static function createRevision(string $itemType, string $itemId, array $itemData, ?string $author = null, string $note = ''): string {
        $storageDir = self::getStorageDir();
        $safeType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemType);
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemId);
        $file = $storageDir . '/' . $safeType . '_' . $safeId . '.json';

        $history = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $parsed = json_decode((string)$raw, true);
            if (is_array($parsed)) {
                $history = $parsed;
            }
        }

        $revId = 'rev_' . dechex(time()) . '_' . bin2hex(random_bytes(3));
        $newEntry = [
            'rev_id'     => $revId,
            'item_type'  => $safeType,
            'item_id'    => $safeId,
            'created_at' => date('Y-m-d H:i:s'),
            'author'     => $author ?: 'System',
            'title'      => (string)($itemData['title'] ?? ($itemData['name'] ?? 'Ohne Titel')),
            'status'     => (string)($itemData['status'] ?? 'published'),
            'note'       => $note,
            'data'       => $itemData
        ];

        // Neueste Revision oben einfügen
        array_unshift($history, $newEntry);

        // Historie begrenzen
        if (count($history) > self::MAX_REVISIONS_PER_ITEM) {
            $history = array_slice($history, 0, self::MAX_REVISIONS_PER_ITEM);
        }

        @file_put_contents($file, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $revId;
    }

    /**
     * Liefert alle Revisionsstände für ein bestimmtes Element.
     *
     * @param string $itemType
     * @param string $itemId
     * @return array<int, array> Liste der Revisionen (ohne große Payload für schnelle Listenansicht)
     */
    public static function getRevisions(string $itemType, string $itemId): array {
        $storageDir = self::getStorageDir();
        $safeType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemType);
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemId);
        $file = $storageDir . '/' . $safeType . '_' . $safeId . '.json';

        if (!file_exists($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        $parsed = json_decode((string)$raw, true);
        if (!is_array($parsed)) {
            return [];
        }

        // Metadaten für Listen aufbereiten
        $summary = [];
        foreach ($parsed as $rev) {
            $summary[] = [
                'rev_id'     => $rev['rev_id'] ?? '',
                'created_at' => $rev['created_at'] ?? '',
                'author'     => $rev['author'] ?? 'System',
                'title'      => $rev['title'] ?? '',
                'status'     => $rev['status'] ?? 'published',
                'note'       => $rev['note'] ?? ''
            ];
        }

        return $summary;
    }

    /**
     * Liefert einen vollständigen Revisionsstand inklusive seiner Daten.
     *
     * @param string $itemType
     * @param string $itemId
     * @param string $revId
     * @return array|null
     */
    public static function getRevision(string $itemType, string $itemId, string $revId): ?array {
        $storageDir = self::getStorageDir();
        $safeType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemType);
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemId);
        $file = $storageDir . '/' . $safeType . '_' . $safeId . '.json';

        if (!file_exists($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        $parsed = json_decode((string)$raw, true);
        if (!is_array($parsed)) {
            return null;
        }

        foreach ($parsed as $rev) {
            if (($rev['rev_id'] ?? '') === $revId) {
                return $rev;
            }
        }

        return null;
    }

    /**
     * Stellt einen früheren Revisionsstand in den globalen CMS-Daten wieder her.
     *
     * @param string $itemType
     * @param string $itemId
     * @param string $revId
     * @param array &$data Referenz auf das globale CMS-Datenarray
     * @param string|null $author Der ausführende Benutzer
     * @return array ['success' => bool, 'message' => string, 'item' => array|null]
     */
    public static function rollbackRevision(string $itemType, string $itemId, string $revId, array &$data, ?string $author = null): array {
        $revision = self::getRevision($itemType, $itemId, $revId);
        if (!$revision || empty($revision['data'])) {
            return [
                'success' => false,
                'message' => 'Der gewählte Revisionsstand wurde nicht gefunden.',
                'item'    => null
            ];
        }

        $targetKey = ($itemType === 'page') ? 'pages' : (($itemType === 'blog') ? 'blog' : '');
        if ($targetKey === '' || !isset($data[$targetKey]) || !is_array($data[$targetKey])) {
            return [
                'success' => false,
                'message' => 'Ungültiger oder nicht unterstützter Inhaltstyp für Rollback.',
                'item'    => null
            ];
        }

        $restoredData = $revision['data'];
        $foundIndex = -1;

        foreach ($data[$targetKey] as $idx => $entry) {
            if ((string)($entry['id'] ?? '') === (string)$itemId) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === -1) {
            return [
                'success' => false,
                'message' => 'Das Ziel-Element existiert im aktuellen Datenbestand nicht mehr.',
                'item'    => null
            ];
        }

        // Vor dem Überschreiben den aktuellen Stand als Backup-Revision festhalten
        self::createRevision(
            $itemType,
            $itemId,
            $data[$targetKey][$foundIndex],
            $author,
            'Automatischer Snapshot vor Rollback auf Stand ' . ($revision['created_at'] ?? $revId)
        );

        // Wiederhergestellten Stand aktualisieren
        $restoredData['updated_at'] = date('Y-m-d H:i:s');
        $data[$targetKey][$foundIndex] = $restoredData;

        // Rollback selbst als neue Revision festhalten
        self::createRevision(
            $itemType,
            $itemId,
            $restoredData,
            $author,
            'Rollback auf Stand vom ' . ($revision['created_at'] ?? $revId)
        );

        return [
            'success' => true,
            'message' => 'Stand vom ' . ($revision['created_at'] ?? '') . ' erfolgreich wiederhergestellt!',
            'item'    => $restoredData
        ];
    }

    /**
     * Berechnet ein schnelles, zeilenweises Unified-Diff zwischen zwei Texten.
     *
     * @param string $oldText
     * @param string $newText
     * @return array<int, array{type: string, text: string}>
     */
    public static function computeDiff(string $oldText, string $newText): array {
        $oldLines = explode("\n", str_replace("\r\n", "\n", $oldText));
        $newLines = explode("\n", str_replace("\r\n", "\n", $newText));

        $diff = [];
        $max = max(count($oldLines), count($newLines));

        for ($i = 0; $i < $max; $i++) {
            $oldL = $oldLines[$i] ?? null;
            $newL = $newLines[$i] ?? null;

            if ($oldL === $newL) {
                $diff[] = ['type' => 'unchanged', 'text' => $oldL];
            } else {
                if ($oldL !== null && $newL === null) {
                    $diff[] = ['type' => 'removed', 'text' => $oldL];
                } elseif ($oldL === null && $newL !== null) {
                    $diff[] = ['type' => 'added', 'text' => $newL];
                } else {
                    $diff[] = ['type' => 'removed', 'text' => $oldL];
                    $diff[] = ['type' => 'added', 'text' => $newL];
                }
            }
        }

        return $diff;
    }

    /**
     * Rendert das HTML für das visuelle Diff-Modal.
     *
     * @param array $diff
     * @return string
     */
    public static function renderDiffHtml(array $diff): string {
        ob_start();
        ?>
        <div class="revision-diff-container" style="font-family:Consolas, Monaco, monospace; font-size:0.85rem; background:#1e1e1e; color:#d4d4d4; padding:1rem; border-radius:8px; max-height:450px; overflow-y:auto; line-height:1.45;">
            <?php foreach ($diff as $d): 
                $bg = 'transparent';
                $prefix = '  ';
                $color = 'inherit';
                if ($d['type'] === 'added') {
                    $bg = 'rgba(46, 160, 67, 0.25)';
                    $prefix = '+ ';
                    $color = '#7ee787';
                } elseif ($d['type'] === 'removed') {
                    $bg = 'rgba(248, 81, 73, 0.25)';
                    $prefix = '- ';
                    $color = '#ffa198';
                }
            ?>
                <div style="background:<?php echo $bg; ?>; color:<?php echo $color; ?>; padding:1px 6px; white-space:pre-wrap; word-break:break-all;">
                    <span style="user-select:none; opacity:0.6;"><?php echo $prefix; ?></span><?php echo htmlspecialchars($d['text'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
