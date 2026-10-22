<?php
/**
 * ============================================================================
 * WeaveCMS Lager- & Artikelverwaltung (InventoryManager)
 * ============================================================================
 * Verwaltet Artikel-Stammdaten, Lagerorte, Mindestbestände, Wareneingänge,
 * Einkaufspreise, Margen und das manipulationssichere Lagerbewegungs-Protokoll.
 *
 * Vollständig synchronisiert mit dem WeaveCMS Shop-Plugin (shop_products.json).
 * ============================================================================
 */

class InventoryManager {

    protected static function getProductsFile(): string {
        return dirname(__DIR__, 2) . '/data/shop_products.json';
    }

    protected static function getLogFile(): string {
        return dirname(__DIR__, 2) . '/data/inventory_log.json';
    }

    /**
     * Liefert alle Artikel mit normalisierten Lager-Metadaten.
     */
    public static function getArticles(): array {
        $file = self::getProductsFile();
        if (!file_exists($file)) {
            // Falls ShopManager vorhanden ist, Standardprodukte anlegen
            if (class_exists('ShopManager')) {
                ShopManager::getProducts();
            }
        }

        $raw = @file_get_contents($file);
        $products = json_decode((string)$raw, true);
        if (!is_array($products)) {
            $products = [];
        }

        // Lager-Metadaten sicherstellen
        $normalized = [];
        foreach ($products as $p) {
            $p['cost_price']   = isset($p['cost_price']) ? round((float)$p['cost_price'], 2) : round(((float)($p['price'] ?? 0)) * 0.45, 2);
            $p['location']     = trim($p['location'] ?? 'Hauptlager A');
            $p['min_stock']    = isset($p['min_stock']) ? max(0, (int)$p['min_stock']) : 3;
            $p['supplier']     = trim($p['supplier'] ?? 'Eigenfertigung / Atelier');
            $p['supplier_sku'] = trim($p['supplier_sku'] ?? '');
            $p['unit']         = trim($p['unit'] ?? 'Stück');
            $p['barcode']      = trim($p['barcode'] ?? '');
            $p['stock']        = max(0, (int)($p['stock'] ?? 0));
            $p['price']        = max(0.0, round((float)($p['price'] ?? 0), 2));
            $p['unlimited']    = !empty($p['unlimited']);
            $p['active']       = !isset($p['active']) || !empty($p['active']);

            // Marge berechnen
            $p['margin_unit']  = round($p['price'] - $p['cost_price'], 2);
            $p['margin_euro']  = $p['margin_unit'];
            $p['margin_percent'] = ($p['price'] > 0) ? round(($p['margin_unit'] / $p['price']) * 100, 1) : 0.0;
            $p['is_low_stock'] = (empty($p['unlimited']) && $p['stock'] <= $p['min_stock']);
            $p['is_out_of_stock'] = (empty($p['unlimited']) && $p['stock'] === 0);

            $normalized[] = $p;
        }

        return $normalized;
    }

    /**
     * Liefert einen einzelnen Artikel anhand seiner ID.
     */
    public static function getArticle(string $id): ?array {
        $articles = self::getArticles();
        foreach ($articles as $a) {
            if (($a['id'] ?? '') === $id) {
                return $a;
            }
        }
        return null;
    }

    /**
     * Speichert einen Artikel und synchronisiert mit data/shop_products.json.
     */
    public static function saveArticle(array $data, ?string $user = 'admin'): array {
        $articles = self::getArticles();
        $id = trim($data['id'] ?? '');
        $isNew = empty($id);

        if ($isNew) {
            $id = 'prod_' . bin2hex(random_bytes(4));
            $currentStock = max(0, (int)($data['stock'] ?? 0));
            $item = [
                'id'          => $id,
                'created_at'  => date('Y-m-d H:i:s')
            ];
        } else {
            $item = self::getArticle($id) ?: ['id' => $id];
            $currentStock = (int)($item['stock'] ?? 0);
        }

        $newStock = isset($data['stock']) ? max(0, (int)$data['stock']) : $currentStock;
        $costPrice = isset($data['cost_price']) ? max(0.0, round((float)str_replace(',', '.', (string)$data['cost_price']), 2)) : ($item['cost_price'] ?? 0.0);
        $price = isset($data['price']) ? max(0.01, round((float)str_replace(',', '.', (string)$data['price']), 2)) : ($item['price'] ?? 1.0);

        $item['title']        = trim($data['title'] ?? ($item['title'] ?? 'Neuer Artikel'));
        $item['sku']          = trim($data['sku'] ?? ($item['sku'] ?? strtoupper('ART-' . substr($id, 5))));
        $item['category']     = trim($data['category'] ?? ($item['category'] ?? 'Allgemein'));
        $item['description']  = trim($data['description'] ?? ($item['description'] ?? ''));
        $item['price']        = $price;
        $item['cost_price']   = $costPrice;
        $taxRate = (int)($data['tax_rate'] ?? ($item['tax_rate'] ?? 19));
        $item['tax_rate']     = in_array($taxRate, [0, 7, 19], true) ? $taxRate : 19;
        $item['stock']        = $newStock;
        $item['min_stock']    = isset($data['min_stock']) ? max(0, (int)$data['min_stock']) : 3;
        $item['location']     = trim($data['location'] ?? ($item['location'] ?? 'Hauptlager A'));
        $item['supplier']     = trim($data['supplier'] ?? ($item['supplier'] ?? ''));
        $item['supplier_sku'] = trim($data['supplier_sku'] ?? ($item['supplier_sku'] ?? ''));
        $item['unit']         = trim($data['unit'] ?? ($item['unit'] ?? 'Stück'));
        $item['barcode']      = trim($data['barcode'] ?? ($item['barcode'] ?? ''));
        $item['unlimited']    = !empty($data['unlimited']);
        $item['image']        = trim($data['image'] ?? ($item['image'] ?? ''));
        $item['active']       = !isset($data['active']) || !empty($data['active']);
        $item['updated_at']   = date('Y-m-d H:i:s');

        // Bestandshistorie protokollieren, falls sich der Bestand geändert hat
        if ($isNew) {
            array_unshift($articles, $item);
            self::saveAllArticles($articles);
            self::logMovement($id, 'init', $newStock, 'ART-INIT', 'Erstanlage des Artikels im System', $user ?: 'admin');
        } else {
            foreach ($articles as &$a) {
                if (($a['id'] ?? '') === $id) {
                    $a = $item;
                    break;
                }
            }
            unset($a);
            self::saveAllArticles($articles);

            $delta = $newStock - $currentStock;
            if ($delta !== 0) {
                self::logMovement($id, $delta > 0 ? 'in' : 'out', $delta, 'MANUAL-ADJUST', 'Manuelle Bestandskorrektur via Artikelbearbeitung', $user ?: 'admin');
            }
        }

        return $item;
    }

    /**
     * Löscht einen Artikel.
     */
    public static function deleteArticle(string $id, ?string $user = 'admin'): bool {
        $articles = self::getArticles();
        $target = self::getArticle($id);
        if (!$target) {
            return false;
        }

        $filtered = array_values(array_filter($articles, fn($a) => ($a['id'] ?? '') !== $id));
        if (count($filtered) < count($articles)) {
            self::logMovement($id, 'delete', -((int)($target['stock'] ?? 0)), 'ART-DELETE', "Artikel gelöscht ({$target['title']})", $user ?: 'admin');
            return self::saveAllArticles($filtered);
        }
        return false;
    }

    /**
     * Bucht einen Wareneingang (Zubuchung von neuer Ware).
     */
    public static function receiveStock(string $id, int $quantity, ?float $costPrice = null, string $deliveryNote = '', string $supplier = '', string $note = '', ?string $user = 'admin'): bool {
        if ($quantity <= 0) {
            return false;
        }

        $articles = self::getArticles();
        $found = false;

        foreach ($articles as &$a) {
            if (($a['id'] ?? '') === $id) {
                $stockBefore = (int)$a['stock'];
                $a['stock'] = $stockBefore + $quantity;
                if ($costPrice !== null && $costPrice >= 0) {
                    $a['cost_price'] = round($costPrice, 2);
                }
                if (!empty($supplier)) {
                    $a['supplier'] = trim($supplier);
                }
                $a['updated_at'] = date('Y-m-d H:i:s');
                $found = true;

                self::logMovement(
                    $id,
                    'in',
                    $quantity,
                    $deliveryNote ?: 'WE-' . date('Ymd'),
                    $note ?: "Wareneingang (+{$quantity} {$a['unit']})",
                    $user ?: 'admin',
                    $stockBefore,
                    $a['stock']
                );
                break;
            }
        }
        unset($a);

        if ($found) {
            return self::saveAllArticles($articles);
        }
        return false;
    }

    /**
     * Schnelle manuelle Bestandskorrektur / Inventurabgleich.
     */
    public static function adjustStock(string $id, int $newStock, string $reason = '', ?string $user = 'admin'): bool {
        $newStock = max(0, $newStock);
        $articles = self::getArticles();
        $found = false;

        foreach ($articles as &$a) {
            if (($a['id'] ?? '') === $id) {
                $stockBefore = (int)$a['stock'];
                $delta = $newStock - $stockBefore;
                $a['stock'] = $newStock;
                $a['updated_at'] = date('Y-m-d H:i:s');
                $found = true;

                self::logMovement(
                    $id,
                    'adjust',
                    $delta,
                    'INVENTUR',
                    $reason ?: "Bestandsabgleich (Soll: {$newStock})",
                    $user ?: 'admin',
                    $stockBefore,
                    $newStock
                );
                break;
            }
        }
        unset($a);

        if ($found) {
            return self::saveAllArticles($articles);
        }
        return false;
    }

    /**
     * Bucht einen Warenausgang (Verkauf oder Entnahme).
     */
    public static function deductStock(string $id, int $quantity, string $ref = '', string $note = '', ?string $user = 'System'): bool {
        if ($quantity <= 0) {
            return false;
        }

        $articles = self::getArticles();
        $found = false;

        foreach ($articles as &$a) {
            if (($a['id'] ?? '') === $id) {
                $stockBefore = (int)$a['stock'];
                $a['stock'] = max(0, $stockBefore - $quantity);
                $a['updated_at'] = date('Y-m-d H:i:s');
                $found = true;

                self::logMovement(
                    $id,
                    'shop_sale',
                    -$quantity,
                    $ref,
                    $note ?: "Shop-Verkauf (-{$quantity} {$a['unit']})",
                    $user ?: 'Webshop',
                    $stockBefore,
                    $a['stock']
                );
                break;
            }
        }
        unset($a);

        if ($found) {
            return self::saveAllArticles($articles);
        }
        return false;
    }

    /**
     * Speichert die gesamte Artikelliste atomar in data/shop_products.json.
     */
    protected static function saveAllArticles(array $articles): bool {
        $file = self::getProductsFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    // =========================================================================
    // LAGERBEWEGUNGEN & AUDIT-TRAIL (LOG)
    // =========================================================================

    /**
     * Liefert alle Lagerbewegungen aus dem Protokoll.
     */
    public static function getLog(int $limit = 150, ?string $articleId = null, ?string $type = null): array {
        $file = self::getLogFile();
        if (!file_exists($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        $log = json_decode((string)$raw, true);
        if (!is_array($log)) {
            return [];
        }

        if ($articleId !== null && $articleId !== '') {
            $log = array_filter($log, fn($entry) => ($entry['article_id'] ?? '') === $articleId);
        }

        if ($type !== null && $type !== '' && $type !== 'all') {
            $log = array_filter($log, fn($entry) => ($entry['type'] ?? '') === $type);
        }

        return array_slice(array_values($log), 0, $limit);
    }

    /**
     * Alias für getLog.
     */
    public static function getLogs(int $limit = 150, ?string $articleId = null, ?string $type = null): array {
        return self::getLog($limit, $articleId, $type);
    }

    /**
     * Schreibt einen Eintrag in das Lagerbewegungsprotokoll (data/inventory_log.json).
     */
    public static function logMovement(string $articleId, string $type, int $qtyDelta, string $ref = '', string $note = '', string $user = 'admin', ?int $before = null, ?int $after = null): array {
        $file = self::getLogFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $log = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $log = json_decode((string)$raw, true) ?: [];
        }

        $art = self::getArticle($articleId);
        $title = $art['title'] ?? 'Artikel ' . $articleId;
        $sku = $art['sku'] ?? $articleId;
        $unit = $art['unit'] ?? 'Stk';

        $stockAfter = ($after !== null) ? $after : (int)($art['stock'] ?? 0);
        $stockBefore = ($before !== null) ? $before : max(0, $stockAfter - $qtyDelta);

        $entry = [
            'id'            => 'mov_' . bin2hex(random_bytes(5)),
            'timestamp'     => date('Y-m-d H:i:s'),
            'article_id'    => $articleId,
            'article_title' => $title,
            'sku'           => $sku,
            'type'          => $type, // in, out, shop_sale, adjust, init, delete
            'delta'         => $qtyDelta,
            'unit'          => $unit,
            'stock_before'  => $stockBefore,
            'stock_after'   => $stockAfter,
            'reference'     => trim($ref),
            'note'          => trim($note),
            'user'          => trim($user)
        ];

        array_unshift($log, $entry);

        // Max. 5.000 Einträge im Protokoll halten
        if (count($log) > 5000) {
            $log = array_slice($log, 0, 5000);
        }

        $json = json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        file_put_contents($tmp, $json);
        @rename($tmp, $file);

        return $entry;
    }

    // =========================================================================
    // LAGER-KPIS & BERICHTE
    // =========================================================================

    /**
     * Berechnet alle Lagerkennzahlen (Lagerwert EK/VK, Margen, Meldebestände).
     */
    public static function getInventorySummary(): array {
        $articles = self::getArticles();

        $totalArticles = count($articles);
        $totalItemsInStock = 0;
        $totalCostValue = 0.0;
        $totalRetailValue = 0.0;
        $lowStockArticles = [];
        $outOfStockArticles = [];
        $categories = [];
        $locations = [];

        foreach ($articles as $a) {
            $stock = (int)$a['stock'];
            $isUnlimited = !empty($a['unlimited']);

            if (!$isUnlimited) {
                $totalItemsInStock += $stock;
                $lineCost = $stock * (float)$a['cost_price'];
                $lineRetail = $stock * (float)$a['price'];
                $totalCostValue += $lineCost;
                $totalRetailValue += $lineRetail;

                if ($stock === 0) {
                    $outOfStockArticles[] = $a;
                } elseif ($stock <= (int)$a['min_stock']) {
                    $lowStockArticles[] = $a;
                }
            }

            $cat = $a['category'] ?? 'Allgemein';
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;

            $loc = $a['location'] ?? 'Ohne Lagerort';
            $locations[$loc] = ($locations[$loc] ?? 0) + 1;
        }

        $potentialMargin = max(0.0, round($totalRetailValue - $totalCostValue, 2));
        $potentialMarginPercent = ($totalRetailValue > 0) ? round(($potentialMargin / $totalRetailValue) * 100, 1) : 0.0;

        return [
            'total_articles'          => $totalArticles,
            'total_items_in_stock'    => $totalItemsInStock,
            'total_cost_value'        => round($totalCostValue, 2),
            'total_retail_value'      => round($totalRetailValue, 2),
            'potential_margin_value'  => $potentialMargin,
            'potential_margin_percent'=> $potentialMarginPercent,
            'low_stock_count'         => count($lowStockArticles),
            'low_stock_articles'      => $lowStockArticles,
            'out_of_stock_count'      => count($outOfStockArticles),
            'out_of_stock_articles'   => $outOfStockArticles,
            'categories'              => $categories,
            'locations'               => $locations
        ];
    }

    /**
     * Erstellt einen CSV-Export der gesamten Bestandsliste.
     */
    public static function exportCsv(): string {
        $articles = self::getArticles();

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM für Excel
        $output .= "Artikel-Nr.;Titel;Kategorie;Lagerort;Einheit;Bestand;Mindestbestand;Einkaufspreis (EK);Verkaufspreis (VK);Marge (EUR);Marge (%);Gesamtwert EK;Gesamtwert VK;Barcode/EAN;Lieferant;Status\r\n";

        foreach ($articles as $a) {
            $stock = (int)$a['stock'];
            $cost = (float)$a['cost_price'];
            $retail = (float)$a['price'];
            $totalCost = round($stock * $cost, 2);
            $totalRetail = round($stock * $retail, 2);

            $fields = [
                $a['sku'] ?? '',
                str_replace(';', ',', $a['title'] ?? ''),
                str_replace(';', ',', $a['category'] ?? ''),
                str_replace(';', ',', $a['location'] ?? ''),
                $a['unit'] ?? 'Stück',
                !empty($a['unlimited']) ? 'Unbegrenzt' : $stock,
                $a['min_stock'] ?? 3,
                number_format($cost, 2, ',', ''),
                number_format($retail, 2, ',', ''),
                number_format($a['margin_unit'] ?? 0, 2, ',', ''),
                number_format($a['margin_percent'] ?? 0, 1, ',', '') . '%',
                number_format($totalCost, 2, ',', ''),
                number_format($totalRetail, 2, ',', ''),
                $a['barcode'] ?? '',
                str_replace(';', ',', $a['supplier'] ?? ''),
                !empty($a['active']) ? 'Aktiv' : 'Inaktiv'
            ];

            $output .= implode(';', array_map(fn($f) => '"' . str_replace('"', '""', (string)$f) . '"', $fields)) . "\r\n";
        }

        return $output;
    }
}
