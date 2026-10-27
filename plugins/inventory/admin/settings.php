<?php
/**
 * ============================================================================
 * WeaveCMS Lager- & Artikelverwaltung – Admin-Oberfläche
 * ============================================================================
 * @var array $config Aktuelle Plugin-Konfiguration
 * @var string $csrf_token CSRF-Token
 */
require_once dirname(__DIR__) . '/InventoryManager.php';

$config = $config ?? [];
$activeInvTab = $_GET['inv_subtab'] ?? $_POST['inv_subtab'] ?? 'articles';

// Kennzahlen & Daten laden
$summary = InventoryManager::getInventorySummary();
$articles = InventoryManager::getArticles();
$logs = InventoryManager::getLog(100);

$defaultMinStock = $config['default_min_stock'] ?? 3;
$defaultLocation = $config['default_location'] ?? 'Hauptlager A';
$locations = $config['locations'] ?? ['Hauptlager A', 'Regal B-01', 'Regal B-02', 'Werkstatt', 'Schaufenster / Laden'];
$notifyLowStock = !isset($config['notify_low_stock']) || !empty($config['notify_low_stock']);
$notifyEmail = $config['notify_email'] ?? '';
?>

<div class="inventory-admin-wrapper" style="margin-top: 1rem;">

    <!-- KPI & Status-Banner -->
    <div style="background: linear-gradient(135deg, rgba(30,58,138,0.1), rgba(46,98,72,0.1)); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display:flex; align-items:center; gap: 14px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #1e3a8a; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30,58,138,0.25);">
                <span class="material-icons" style="font-size: 28px;">warehouse</span>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800;">📦 Lager- &amp; Artikelverwaltung</h3>
                <p style="margin: 2px 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                    Bestände, Lagerplätze, Einkaufspreise, Margen &amp; Audit-Trail – synchronisiert mit dem Shop.
                </p>
            </div>
        </div>

        <!-- Schnell-KPIs -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <div style="background: var(--card-bg, #fff); padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Lagerwert (EK)</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #1e3a8a;"><?php echo number_format($summary['total_cost_value'], 2, ',', '.'); ?> €</div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Verkaufswert (VK)</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary);"><?php echo number_format($summary['total_retail_value'], 2, ',', '.'); ?> €</div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Gesamtmarge</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #15803d;">+<?php echo number_format($summary['potential_margin_value'], 2, ',', '.'); ?> € <span style="font-size:0.75rem; font-weight:600;">(<?php echo $summary['potential_margin_percent']; ?>%)</span></div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Meldebestand</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?php echo $summary['low_stock_count'] > 0 ? '#ef4444' : '#15803d'; ?>;">
                    <?php echo $summary['low_stock_count']; ?> Artikel
                </div>
            </div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles#tab-plugins" 
           style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeInvTab === 'articles' ? '#1e3a8a' : 'transparent'; ?>; color: <?php echo $activeInvTab === 'articles' ? '#1e3a8a' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 18px;">inventory_2</span> Artikel &amp; Lagerplätze (<?php echo count($articles); ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=receipt#tab-plugins" 
           style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeInvTab === 'receipt' ? '#1e3a8a' : 'transparent'; ?>; color: <?php echo $activeInvTab === 'receipt' ? '#1e3a8a' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 18px;">local_shipping</span> Wareneingang buchen
        </a>
        <a href="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=log#tab-plugins" 
           style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeInvTab === 'log' ? '#1e3a8a' : 'transparent'; ?>; color: <?php echo $activeInvTab === 'log' ? '#1e3a8a' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 18px;">history</span> Lagerbewegungs-Protokoll (<?php echo count($logs); ?>)
        </a>
        <a href="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=stocktaking#tab-plugins" 
           style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeInvTab === 'stocktaking' ? '#1e3a8a' : 'transparent'; ?>; color: <?php echo $activeInvTab === 'stocktaking' ? '#1e3a8a' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 18px;">checklist</span> Inventur-Abgleich
        </a>
        <a href="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings#tab-plugins" 
           style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeInvTab === 'settings' ? '#1e3a8a' : 'transparent'; ?>; color: <?php echo $activeInvTab === 'settings' ? '#1e3a8a' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
            <span class="material-icons" style="font-size: 18px;">tune</span> Einstellungen &amp; Export
        </a>
    </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 1: ARTIKELÜBERSICHT & LAGERPLÄTZE                              -->
    <!-- ===================================================================== -->
    <?php if ($activeInvTab === 'articles'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="text" id="inv-search-input" placeholder="🔍 Artikel, SKU, Barcode suchen..." 
                       oninput="filterInventoryArticles()" class="form-control" style="width: 260px; font-size: 0.88rem;">
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" id="inv-filter-low-stock" onchange="filterInventoryArticles()">
                    <span>Nur Nachbestellungen (Meldebestand)</span>
                </label>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="?action=inv_export_csv" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px; padding: 7px 14px; font-size:0.88rem;">
                    <span class="material-icons" style="font-size:16px;">download</span> CSV-Export
                </a>
                <button type="button" onclick="openInventoryArticleModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                    <span class="material-icons" style="font-size: 18px;">add_box</span> Neuer Artikel
                </button>
            </div>
        </div>

        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; overflow-x: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <table id="inventory-articles-table" class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                    <tr>
                        <th style="padding: 10px 14px;">Artikel &amp; SKU</th>
                        <th style="padding: 10px 14px;">Lagerort</th>
                        <th style="padding: 10px 14px;">Bestand / Mindest</th>
                        <th style="padding: 10px 14px;">EK / VK</th>
                        <th style="padding: 10px 14px;">Marge</th>
                        <th style="padding: 10px 14px;">Lagerwert (EK)</th>
                        <th style="padding: 10px 14px; text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="7" style="padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                                Keine Artikel im Lager hinterlegt.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($articles as $a): 
                            $isOut = $a['is_out_of_stock'];
                            $isLow = $a['is_low_stock'];
                            $lineValEK = round($a['stock'] * $a['cost_price'], 2);
                        ?>
                            <tr class="inv-article-row" 
                                data-low="<?php echo ($isLow || $isOut) ? '1' : '0'; ?>"
                                data-search="<?php echo strtolower(htmlspecialchars($a['title'] . ' ' . $a['sku'] . ' ' . $a['barcode'] . ' ' . $a['location'])); ?>"
                                style="border-bottom: 1px solid var(--border-color); <?php echo $isOut ? 'background: rgba(239,68,68,0.03);' : ($isLow ? 'background: rgba(245,158,11,0.03);' : ''); ?>">
                                
                                <td style="padding: 10px 14px;">
                                    <div style="font-weight: 700; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($a['title']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); display:flex; gap:8px;">
                                        <span>SKU: <code><?php echo htmlspecialchars($a['sku']); ?></code></span>
                                        <?php if (!empty($a['barcode'])): ?>
                                            <span>EAN: <code><?php echo htmlspecialchars($a['barcode']); ?></code></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td style="padding: 10px 14px;">
                                    <span style="background: rgba(30,58,138,0.08); color: #1e3a8a; padding: 3px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 600;">
                                        📍 <?php echo htmlspecialchars($a['location']); ?>
                                    </span>
                                </td>

                                <td style="padding: 10px 14px;">
                                    <?php if (!empty($a['unlimited'])): ?>
                                        <span style="color: #3b82f6; font-weight: 600;">Digital / ∞</span>
                                    <?php elseif ($isOut): ?>
                                        <span style="background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 12px; font-weight: 800; font-size: 0.75rem;">
                                            Ausverkauft (0 <?php echo htmlspecialchars($a['unit']); ?>)
                                        </span>
                                    <?php elseif ($isLow): ?>
                                        <span style="background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 0.75rem;">
                                            ⚠️ <?php echo $a['stock']; ?> / Min: <?php echo $a['min_stock']; ?> <?php echo htmlspecialchars($a['unit']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #15803d; font-weight: 700;">
                                            <?php echo $a['stock']; ?> <?php echo htmlspecialchars($a['unit']); ?>
                                        </span>
                                        <span style="color: var(--text-secondary); font-size: 0.75rem;">(Min: <?php echo $a['min_stock']; ?>)</span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 10px 14px;">
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">EK: <?php echo number_format($a['cost_price'], 2, ',', '.'); ?> €</div>
                                    <div style="font-weight: 700; color: var(--text-primary);">VK: <?php echo number_format($a['price'], 2, ',', '.'); ?> €</div>
                                </td>

                                <td style="padding: 10px 14px;">
                                    <div style="font-weight: 700; color: #15803d;">+<?php echo number_format($a['margin_unit'], 2, ',', '.'); ?> €</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo $a['margin_percent']; ?> %</div>
                                </td>

                                <td style="padding: 10px 14px; font-weight: 700; color: #1e3a8a;">
                                    <?php echo !empty($a['unlimited']) ? '-' : number_format($lineValEK, 2, ',', '.') . ' €'; ?>
                                </td>

                                <td style="padding: 10px 14px; text-align: right;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <button type="button" class="btn btn-outline" style="padding: 4px 7px; font-size: 0.75rem;" title="Schnell-Zubuchung" 
                                                onclick='openQuickStockModal(<?php echo json_encode($a); ?>)'>
                                            <span class="material-icons" style="font-size: 15px;">add_circle_outline</span>
                                        </button>
                                        <button type="button" class="btn btn-outline" style="padding: 4px 7px; font-size: 0.75rem;" title="Artikel bearbeiten" 
                                                onclick='editInventoryArticle(<?php echo json_encode($a); ?>)'>
                                            <span class="material-icons" style="font-size: 15px;">edit</span>
                                        </button>
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles#tab-plugins" onsubmit="return confirm('Möchtest du diesen Artikel wirklich aus dem System löschen?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="inv_delete_article">
                                            <input type="hidden" name="target_tab" value="tab-plugins">
                                            <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($a['id']); ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 4px 7px; font-size: 0.75rem; color: #ef4444;" title="Löschen">
                                                <span class="material-icons" style="font-size: 15px;">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 2: WARENEINGANG BUCHEN                                         -->
    <!-- ===================================================================== -->
    <?php elseif ($activeInvTab === 'receipt'): ?>
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.75rem; max-width: 720px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                <span class="material-icons" style="color: #1e3a8a; font-size: 28px;">local_shipping</span>
                <div>
                    <h4 style="margin:0; font-size: 1.2rem; font-weight: 700;">Wareneingang / Lieferung zubuchen</h4>
                    <div style="font-size: 0.82rem; color: var(--text-secondary);">Bestände erhöhen und Belege dokumentieren</div>
                </div>
            </div>

            <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=receipt#tab-plugins">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="inv_receive_stock">
                <input type="hidden" name="target_tab" value="tab-plugins">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Artikel auswählen *</label>
                    <select name="article_id" class="form-control" required style="font-size: 0.9rem;">
                        <option value="">-- Artikel wählen --</option>
                        <?php foreach ($articles as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['id']); ?>">
                                <?php echo htmlspecialchars($a['sku'] . ' - ' . $a['title'] . ' (Aktuell: ' . $a['stock'] . ' ' . $a['unit'] . ', EK: ' . number_format($a['cost_price'], 2, ',', '.') . ' €)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Zugangsmenge (+ Stück) *</label>
                        <input type="number" name="quantity" class="form-control" min="1" step="1" value="10" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Neuer Einkaufspreis (EK in €)</label>
                        <input type="number" step="0.01" name="cost_price" class="form-control" placeholder="Leer lassen = bisheriger EK">
                    </div>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Lieferschein- / Belegnummer</label>
                        <input type="text" name="delivery_note" class="form-control" placeholder="z. B. LS-2026-9481">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Lieferant / Bezugsquelle</label>
                        <input type="text" name="supplier" class="form-control" placeholder="z. B. Weidenhof Süd">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Notiz / Buchungsvermerk</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="z. B. Qualitätsprüfung einwandfrei, sofort für Shop freigegeben"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                    <span class="material-icons">add_shopping_cart</span>
                    <span>Wareneingang jetzt verbuchen</span>
                </button>
            </form>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 3: LAGERBEWEGUNGS-PROTOKOLL                                    -->
    <!-- ===================================================================== -->
    <?php elseif ($activeInvTab === 'log'): ?>
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; overflow-x: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                    <tr>
                        <th style="padding: 10px 14px;">Datum / Uhrzeit</th>
                        <th style="padding: 10px 14px;">Artikel &amp; SKU</th>
                        <th style="padding: 10px 14px;">Buchungsart</th>
                        <th style="padding: 10px 14px;">Menge (Delta)</th>
                        <th style="padding: 10px 14px;">Bestand (Vorher ➔ Nachher)</th>
                        <th style="padding: 10px 14px;">Beleg / Notiz</th>
                        <th style="padding: 10px 14px;">Quelle / Benutzer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                                Noch keine Lagerbewegungen aufgezeichnet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): 
                            $type = $l['type'] ?? 'adjust';
                            $typeBadge = 'Korrektur';
                            $badgeColor = '#64748b';
                            $badgeBg = 'rgba(100,116,139,0.1)';

                            if ($type === 'in') {
                                $typeBadge = '📥 Wareneingang';
                                $badgeColor = '#15803d';
                                $badgeBg = '#dcfce7';
                            } elseif ($type === 'shop_sale') {
                                $typeBadge = '🛒 Shop-Verkauf';
                                $badgeColor = '#1e3a8a';
                                $badgeBg = '#dbeafe';
                            } elseif ($type === 'out') {
                                $typeBadge = '📤 Entnahme';
                                $badgeColor = '#b45309';
                                $badgeBg = '#fef3c7';
                            } elseif ($type === 'init') {
                                $typeBadge = '✨ Erstanlage';
                                $badgeColor = '#475569';
                                $badgeBg = '#f1f5f9';
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 14px; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($l['timestamp']); ?>
                                </td>
                                <td style="padding: 10px 14px; font-weight: 700;">
                                    <?php echo htmlspecialchars($l['article_title']); ?>
                                    <div style="font-size:0.75rem; color:var(--text-secondary);">SKU: <?php echo htmlspecialchars($l['sku']); ?></div>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <span style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; padding: 3px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;">
                                        <?php echo $typeBadge; ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 14px; font-weight: 800; color: <?php echo ($l['delta'] > 0) ? '#15803d' : '#ef4444'; ?>;">
                                    <?php echo ($l['delta'] > 0 ? '+' : '') . $l['delta'] . ' ' . htmlspecialchars($l['unit'] ?? 'Stk'); ?>
                                </td>
                                <td style="padding: 10px 14px; font-weight: 600;">
                                    <?php echo (int)($l['stock_before'] ?? 0); ?> ➔ <span style="color:var(--primary); font-weight:800;"><?php echo (int)($l['stock_after'] ?? 0); ?></span>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <?php if (!empty($l['reference'])): ?>
                                        <strong>[<?php echo htmlspecialchars($l['reference']); ?>]</strong> 
                                    <?php endif; ?>
                                    <span style="color:var(--text-secondary);"><?php echo htmlspecialchars($l['note'] ?? ''); ?></span>
                                </td>
                                <td style="padding: 10px 14px; color: var(--text-secondary); font-size: 0.8rem;">
                                    👤 <?php echo htmlspecialchars($l['user'] ?? 'System'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 4: INVENTUR & BESTANDSABGLEICH                                  -->
    <!-- ===================================================================== -->
    <?php elseif ($activeInvTab === 'stocktaking'): ?>
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1.25rem;">
                <div>
                    <h4 style="margin:0; font-size: 1.2rem; font-weight: 700;">Stichtags-Inventur</h4>
                    <p style="margin:2px 0 0 0; color:var(--text-secondary); font-size:0.85rem;">
                        Trage die tatsächlich gezählten Ist-Bestände ein. Differenzen werden automatisch als Inventur-Korrektur verbucht.
                    </p>
                </div>
            </div>

            <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=stocktaking#tab-plugins">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="inv_batch_stocktaking">
                <input type="hidden" name="target_tab" value="tab-plugins">

                <div class="form-group" style="margin-bottom: 1rem; max-width: 400px;">
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Inventur-Beleg / Anlass</label>
                    <input type="text" name="inventory_note" class="form-control" value="Jahresinventur <?php echo date('Y'); ?>">
                </div>

                <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; margin-bottom: 1.5rem;">
                    <thead style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <tr>
                            <th style="padding: 8px 12px;">Artikel</th>
                            <th style="padding: 8px 12px;">Lagerort</th>
                            <th style="padding: 8px 12px;">Soll-Bestand</th>
                            <th style="padding: 8px 12px; width: 140px;">Ist-Bestand (Gezählt)</th>
                            <th style="padding: 8px 12px;">Differenz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): 
                            if (!empty($a['unlimited'])) continue;
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px 12px;">
                                    <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--text-secondary);">SKU: <?php echo htmlspecialchars($a['sku']); ?></div>
                                </td>
                                <td style="padding: 8px 12px;">
                                    📍 <?php echo htmlspecialchars($a['location']); ?>
                                </td>
                                <td style="padding: 8px 12px; font-weight: 700;">
                                    <span id="soll-<?php echo $a['id']; ?>"><?php echo $a['stock']; ?></span> <?php echo htmlspecialchars($a['unit']); ?>
                                </td>
                                <td style="padding: 8px 12px;">
                                    <input type="number" name="actual_stock[<?php echo $a['id']; ?>]" 
                                           id="ist-<?php echo $a['id']; ?>"
                                           class="form-control" style="width: 100px; padding: 4px 8px; font-weight: 700;" 
                                           value="<?php echo $a['stock']; ?>"
                                           oninput="calcInventoryDiff('<?php echo $a['id']; ?>', <?php echo $a['stock']; ?>)">
                                </td>
                                <td style="padding: 8px 12px; font-weight: 800;">
                                    <span id="diff-<?php echo $a['id']; ?>" style="color: #64748b;">0</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                    <span class="material-icons">fact_check</span>
                    <span>Inventur-Ergebnis jetzt verbuchen &amp; Bestände anpassen</span>
                </button>
            </form>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 5: EINSTELLUNGEN & EXPORT                                      -->
    <!-- ===================================================================== -->
    <?php elseif ($activeInvTab === 'settings'): ?>
        <?php 
            $invApiKey = InventoryManager::getApiKey($config);
            $invApiEnabled = !empty($config['api_enabled']);
            $msg = $_GET['msg'] ?? '';
            $importedCount = (int)($_GET['imported'] ?? 0);
            $updatedCount = (int)($_GET['updated'] ?? 0);

            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $apiBaseUrl = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '', 2), '/\\') . '/api/inventory.php';
        ?>

        <?php if ($msg === 'inventory_imported'): ?>
            <div style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; color: #047857; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; max-width: 680px;">
                <span class="material-icons" style="font-size:22px;">check_circle</span>
                <div>
                    <strong>JSON-Import erfolgreich abgeschlossen!</strong> 
                    <?php echo $importedCount; ?> neue(r) Artikel angelegt, <?php echo $updatedCount; ?> bestehende Artikel/Bestände aktualisiert.
                </div>
            </div>
        <?php elseif ($msg === 'api_key_generated'): ?>
            <div style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; color: #047857; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; max-width: 680px;">
                <span class="material-icons" style="font-size:22px;">vpn_key</span>
                <div>
                    <strong>Neuer API-Schlüssel für Lagerverwaltung wurde generiert und gespeichert!</strong>
                </div>
            </div>
        <?php elseif ($msg === 'import_error'): ?>
            <div style="background: rgba(239,68,68,0.12); border: 1px solid #ef4444; color: #b91c1c; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; max-width: 680px;">
                <span class="material-icons" style="font-size:22px;">error</span>
                <div>
                    <strong>Fehler beim Importieren:</strong> Bitte stelle sicher, dass eine valide JSON-Datei übergeben wurde.
                </div>
            </div>
        <?php endif; ?>

        <!-- 1. PARAMETER FORMULAR -->
        <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings#tab-plugins">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_plugin_settings">
            <input type="hidden" name="plugin_slug" value="inventory">
            <input type="hidden" name="plugin_settings" value="inventory">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="inv_subtab" value="settings">

            <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.75rem; margin-bottom: 1.5rem; max-width: 680px;">
                <h4 style="margin: 0 0 1.25rem; font-size: 1.15rem; font-weight: 700;">Lager-Parameter</h4>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Standard-Mindestbestand</label>
                        <input type="number" name="default_min_stock" class="form-control" value="<?php echo (int)$defaultMinStock; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Standard-Lagerort</label>
                        <input type="text" name="default_location" class="form-control" value="<?php echo htmlspecialchars($defaultLocation); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Verfügbare Lagerorte (kommagetrennt)</label>
                    <input type="text" name="locations_list" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $locations)); ?>">
                </div>

                <div style="background: rgba(30,58,138,0.04); border: 1px solid rgba(30,58,138,0.15); border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <h5 style="margin: 0 0 0.75rem; font-size: 0.95rem; font-weight: 700; color: #1e3a8a;">⚠️ Automatischer Mindestbestand-Alarm (E-Mail)</h5>
                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; font-size: 0.9rem; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="notify_low_stock" value="0">
                        <input type="checkbox" name="notify_low_stock" value="1" <?php echo $notifyLowStock ? 'checked' : ''; ?>>
                        <span>E-Mail-Warnung senden, sobald ein Artikel den Mindestbestand erreicht oder unterschreitet</span>
                    </label>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Benachrichtigungs-Empfänger (E-Mail, optional)</label>
                        <input type="email" name="notify_email" class="form-control" placeholder="leer lassen für Standard-Admin-E-Mail" value="<?php echo htmlspecialchars($notifyEmail); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                    <span class="material-icons">save</span> Lager-Einstellungen speichern
                </button>
            </div>
        </form>

        <!-- 2. DATEN-EXPORT (CSV & JSON) -->
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; max-width: 680px; margin-bottom: 1.5rem;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.75rem;">
                <span class="material-icons" style="color:#1e3a8a; font-size:26px;">download</span>
                <h4 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e3a8a;">Lager-Export (CSV &amp; JSON)</h4>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 0 0 1rem; line-height: 1.45;">
                Exportiere deinen gesamten Warenbestand inklusive Einkaufspreisen, Verkaufspreisen, Margen und Barcodes für Excel, Steuerberater oder automatisierte Datensicherungen.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?action=inv_export_csv" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-weight: 700;">
                    <span class="material-icons" style="font-size:16px;">table_view</span> CSV-Export (Excel)
                </a>
                <a href="?action=inv_export_json" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-weight: 700;">
                    <span class="material-icons" style="font-size:16px;">data_object</span> Bestände als JSON
                </a>
                <a href="?action=inv_export_movements_json" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-weight: 700;">
                    <span class="material-icons" style="font-size:16px;">history</span> Audit-Log als JSON
                </a>
            </div>
        </div>

        <!-- 3. DATEN-IMPORT (JSON) -->
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; max-width: 680px; margin-bottom: 1.5rem;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.75rem;">
                <span class="material-icons" style="color:#1e3a8a; font-size:26px;">upload_file</span>
                <h4 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e3a8a;">Lagerbestand importieren (JSON)</h4>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 0 0 1rem; line-height: 1.45;">
                Lade eine JSON-Datei mit Artikeln hoch. Bestandsänderungen werden automatisch im revisionssicheren Audit-Log protokolliert.
            </p>

            <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings#tab-plugins" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="inv_import_json">
                <input type="hidden" name="target_tab" value="tab-plugins">

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">JSON-Datei auswählen:</label>
                    <input type="file" name="import_file" accept=".json,application/json" class="form-control" required style="padding: 6px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Import-Modus:</label>
                    <select name="import_mode" class="form-control">
                        <option value="merge" selected>Zusammenführen (Bestände abgleichen &amp; neue anfügen)</option>
                        <option value="replace">Vollständig ersetzen (Bestehende Liste überschreiben)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                    <span class="material-icons" style="font-size:18px;">publish</span> Lagerbestand importieren
                </button>
            </form>
        </div>

        <!-- 4. REST-API & POS/KASSEN-SCHNITTSTELLE -->
        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.75rem; max-width: 680px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:1.25rem;">
                <div>
                    <h4 style="margin:0 0 0.25rem 0; font-size:1.2rem; font-weight:700; display:flex; align-items:center; gap:8px; color:#1e3a8a;">
                        <span class="material-icons" style="font-size:24px;">point_of_sale</span> REST-API &amp; Kassensystem (POS)
                    </h4>
                    <p style="margin:0; font-size:0.88rem; color:var(--text-secondary);">
                        Echtzeit-Synchronisierung für Kassen im Atelier, Barcode-Scanner-Apps und Warenwirtschaften.
                    </p>
                </div>
                <div>
                    <span style="font-size:0.8rem; background:<?php echo $invApiEnabled ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)'; ?>; color:<?php echo $invApiEnabled ? '#047857' : '#b91c1c'; ?>; border:1px solid <?php echo $invApiEnabled ? '#10b981' : '#ef4444'; ?>; border-radius:20px; padding:4px 12px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:14px;"><?php echo $invApiEnabled ? 'check_circle' : 'block'; ?></span>
                        <?php echo $invApiEnabled ? 'API Aktiv' : 'API Deaktiviert'; ?>
                    </span>
                </div>
            </div>

            <!-- API-Konfiguration speichern -->
            <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings#tab-plugins" style="margin-bottom: 1.25rem;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="save_plugin_settings">
                <input type="hidden" name="plugin_slug" value="inventory">
                <input type="hidden" name="plugin_settings" value="inventory">
                <input type="hidden" name="target_tab" value="tab-plugins">
                <input type="hidden" name="inv_subtab" value="settings">

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 700; font-size: 0.92rem; cursor:pointer; margin-bottom: 12px;">
                        <input type="hidden" name="api_enabled" value="0">
                        <input type="checkbox" name="api_enabled" value="1" <?php echo $invApiEnabled ? 'checked' : ''; ?>>
                        <span>Lager-REST-API aktivieren</span>
                    </label>

                    <div style="margin-bottom: 8px;">
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">API-Schlüssel (Bearer Token):</label>
                        <div style="display:flex; gap: 8px;">
                            <input type="text" name="api_key" id="inv-api-key-display" class="form-control" value="<?php echo htmlspecialchars($invApiKey ?: 'Noch kein Schlüssel generiert'); ?>" readonly style="font-family: monospace; font-size: 0.9rem; background: #f8fafc;">
                            <button type="button" class="btn btn-outline" onclick="navigator.clipboard.writeText(document.getElementById('inv-api-key-display').value); if(typeof showToast==='function') showToast('API-Key kopiert!'); else alert('API-Key kopiert!');" title="In die Zwischenablage kopieren">
                                <span class="material-icons" style="font-size:16px;">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap: 10px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; font-weight: 700; background: #1e3a8a; border-color: #1e3a8a;">
                        <span class="material-icons" style="font-size:16px;">save</span> API-Status speichern
                    </button>
                </div>
            </form>

            <!-- Separater Button für Token-Generierung -->
            <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=settings#tab-plugins" style="margin-bottom: 1.5rem;" onsubmit="return confirm('Möchtest du wirklich einen neuen API-Schlüssel für die Lagerverwaltung generieren?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="inv_generate_api_key">
                <input type="hidden" name="target_tab" value="tab-plugins">
                <button type="submit" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size:0.85rem;">
                    <span class="material-icons" style="font-size:16px; color:#1e3a8a;">autorenew</span> Neuen API-Schlüssel generieren
                </button>
            </form>

            <!-- API Dokumentation / cURL Beispiele -->
            <div style="background: #1e293b; color: #f8fafc; border-radius: 12px; padding: 1.25rem; font-size: 0.84rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                    <strong style="color: #38bdf8; display:flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:16px;">terminal</span> API-Endpunkt &amp; Kassen-Integration
                    </strong>
                    <code style="background: rgba(255,255,255,0.1); color: #fff; padding: 2px 8px; border-radius: 6px;"><?php echo htmlspecialchars($apiBaseUrl); ?></code>
                </div>

                <div style="margin-bottom: 0.75rem;">
                    <span style="color: #94a3b8;"># 1. Bestandsabgleich bei Vor-Ort-Verkauf (POST Kasse/POS):</span><br>
                    <code>curl -X POST -H "Authorization: Bearer <?php echo htmlspecialchars($invApiKey ?: 'DEIN_KEY'); ?>" -H "Content-Type: application/json" -d '{"sku":"ART-01","change":-1,"reason":"Atelier-Kassenverkauf","user":"Kasse 1"}' "<?php echo htmlspecialchars($apiBaseUrl); ?>?action=stock_adjust"</code>
                </div>

                <div style="margin-bottom: 0.75rem;">
                    <span style="color: #94a3b8;"># 2. Alle Lagerbestände abfragen (GET):</span><br>
                    <code>curl -H "Authorization: Bearer <?php echo htmlspecialchars($invApiKey ?: 'DEIN_KEY'); ?>" "<?php echo htmlspecialchars($apiBaseUrl); ?>?action=articles"</code>
                </div>

                <div>
                    <span style="color: #94a3b8;"># 3. Artikel unter Mindestbestand abfragen (GET):</span><br>
                    <code>curl -H "Authorization: Bearer <?php echo htmlspecialchars($invApiKey ?: 'DEIN_KEY'); ?>" "<?php echo htmlspecialchars($apiBaseUrl); ?>?action=low_stock"</code>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<!-- ========================================================================= -->
<!-- MODAL: ARTIKEL ANLEGEN / VOLLSTÄNDIG BEARBEITEN                          -->
<!-- ========================================================================= -->
<div id="inv-article-modal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
    <div style="background:var(--card-bg, #fff); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:680px; box-shadow:0 15px 40px rgba(0,0,0,0.25); overflow:hidden; margin:auto;">
        
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02);">
            <h4 id="inv-article-modal-title" style="margin:0; font-size:1.15rem; font-weight:700;">Artikel anlegen / bearbeiten</h4>
            <button type="button" onclick="closeInventoryArticleModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles#tab-plugins" style="padding:1.5rem; max-height:75vh; overflow-y:auto;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="inv_save_article">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="id" id="inv-form-id" value="">

            <div class="grid" style="grid-template-columns: 2fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Artikelbezeichnung *</label>
                    <input type="text" name="title" id="inv-form-title" class="form-control" placeholder="z. B. Weidenkorb Klassik" required>
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Artikel-Nr. (SKU) *</label>
                    <input type="text" name="sku" id="inv-form-sku" class="form-control" placeholder="ART-01" required>
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Kategorie</label>
                    <input type="text" name="category" id="inv-form-category" class="form-control" placeholder="z. B. Kunsthandwerk">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Lagerort / Regal</label>
                    <input type="text" name="location" id="inv-form-location" class="form-control" placeholder="z. B. Regal A-02">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Einheit</label>
                    <input type="text" name="unit" id="inv-form-unit" class="form-control" placeholder="Stück">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Einkaufspreis (EK in €)</label>
                    <input type="number" step="0.01" name="cost_price" id="inv-form-cost-price" class="form-control" placeholder="15.00">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Verkaufspreis (VK in €) *</label>
                    <input type="number" step="0.01" name="price" id="inv-form-price" class="form-control" placeholder="29.90" required>
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Aktueller Lagerbestand</label>
                    <input type="number" name="stock" id="inv-form-stock" class="form-control" value="10">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Mindestbestand (Meldegrenze)</label>
                    <input type="number" name="min_stock" id="inv-form-min-stock" class="form-control" value="3">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Lieferant / Bezugsquelle</label>
                    <input type="text" name="supplier" id="inv-form-supplier" class="form-control" placeholder="z. B. Weidenhof Süd">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">EAN / Barcode</label>
                    <input type="text" name="barcode" id="inv-form-barcode" class="form-control" placeholder="z. B. 4260123456789">
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Beschreibung</label>
                <textarea name="description" id="inv-form-desc" class="form-control" rows="2" placeholder="Details..."></textarea>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border-color);">
                <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:600; cursor:pointer;">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" id="inv-form-active" value="1" checked>
                    <span>Im Online-Shop anbieten</span>
                </label>
                <button type="submit" class="btn btn-primary" style="padding:9px 24px; font-weight:700; background:#1e3a8a; border-color:#1e3a8a;">
                    Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: SCHNELL-BESTANDSKORREKTUR                                         -->
<!-- ========================================================================= -->
<div id="inv-quick-stock-modal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:var(--card-bg, #fff); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:440px; box-shadow:0 15px 40px rgba(0,0,0,0.25); overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color);">
            <h4 id="quick-stock-title" style="margin:0; font-size:1.1rem; font-weight:700;">Bestand anpassen</h4>
            <button type="button" onclick="closeQuickStockModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="?tab=tab-plugins&plugin_settings=inventory&inv_subtab=articles#tab-plugins" style="padding:1.5rem;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="inv_adjust_stock">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="article_id" id="quick-stock-id" value="">

            <div style="margin-bottom:12px;">
                <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Neuer Bestand (Ist)</label>
                <input type="number" name="new_stock" id="quick-stock-val" class="form-control" min="0" required style="font-size:1.2rem; font-weight:800; text-align:center;">
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="font-size:0.82rem; font-weight:700; display:block; margin-bottom:4px;">Grund / Beleg</label>
                <input type="text" name="reason" class="form-control" placeholder="z. B. Manuelle Zählung, Bruch, Muster">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:10px; font-weight:700; background:#1e3a8a; border-color:#1e3a8a;">
                Bestand aktualisieren
            </button>
        </form>
    </div>
</div>

<script>
function filterInventoryArticles() {
    const q = (document.getElementById('inv-search-input')?.value || '').toLowerCase();
    const onlyLow = document.getElementById('inv-filter-low-stock')?.checked || false;
    const rows = document.querySelectorAll('.inv-article-row');

    rows.forEach(r => {
        const text = r.getAttribute('data-search') || '';
        const isLow = r.getAttribute('data-low') === '1';

        const matchesText = !q || text.includes(q);
        const matchesLow = !onlyLow || isLow;

        if (matchesText && matchesLow) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function openInventoryArticleModal() {
    document.getElementById('inv-article-modal-title').innerText = 'Neuen Artikel anlegen';
    document.getElementById('inv-form-id').value = '';
    document.getElementById('inv-form-title').value = '';
    document.getElementById('inv-form-sku').value = '';
    document.getElementById('inv-form-category').value = '';
    document.getElementById('inv-form-location').value = 'Hauptlager A';
    document.getElementById('inv-form-unit').value = 'Stück';
    document.getElementById('inv-form-cost-price').value = '';
    document.getElementById('inv-form-price').value = '';
    document.getElementById('inv-form-stock').value = '10';
    document.getElementById('inv-form-min-stock').value = '3';
    document.getElementById('inv-form-supplier').value = '';
    document.getElementById('inv-form-barcode').value = '';
    document.getElementById('inv-form-desc').value = '';
    document.getElementById('inv-form-active').checked = true;
    document.getElementById('inv-article-modal').style.display = 'flex';
}

function editInventoryArticle(a) {
    document.getElementById('inv-article-modal-title').innerText = 'Artikel bearbeiten: ' + (a.title || '');
    document.getElementById('inv-form-id').value = a.id || '';
    document.getElementById('inv-form-title').value = a.title || '';
    document.getElementById('inv-form-sku').value = a.sku || '';
    document.getElementById('inv-form-category').value = a.category || '';
    document.getElementById('inv-form-location').value = a.location || 'Hauptlager A';
    document.getElementById('inv-form-unit').value = a.unit || 'Stück';
    document.getElementById('inv-form-cost-price').value = a.cost_price || '';
    document.getElementById('inv-form-price').value = a.price || '';
    document.getElementById('inv-form-stock').value = a.stock || 0;
    document.getElementById('inv-form-min-stock').value = a.min_stock || 3;
    document.getElementById('inv-form-supplier').value = a.supplier || '';
    document.getElementById('inv-form-barcode').value = a.barcode || '';
    document.getElementById('inv-form-desc').value = a.description || '';
    document.getElementById('inv-form-active').checked = !!a.active;
    document.getElementById('inv-article-modal').style.display = 'flex';
}

function closeInventoryArticleModal() {
    document.getElementById('inv-article-modal').style.display = 'none';
}

function openQuickStockModal(a) {
    document.getElementById('quick-stock-title').innerText = 'Bestand: ' + (a.title || '');
    document.getElementById('quick-stock-id').value = a.id || '';
    document.getElementById('quick-stock-val').value = a.stock || 0;
    document.getElementById('inv-quick-stock-modal').style.display = 'flex';
}

function closeQuickStockModal() {
    document.getElementById('inv-quick-stock-modal').style.display = 'none';
}

function calcInventoryDiff(id, soll) {
    const input = document.getElementById('ist-' + id);
    const diffEl = document.getElementById('diff-' + id);
    if (!input || !diffEl) return;

    const ist = parseInt(input.value) || 0;
    const diff = ist - soll;

    if (diff === 0) {
        diffEl.innerText = '0';
        diffEl.style.color = '#64748b';
    } else if (diff > 0) {
        diffEl.innerText = '+' + diff;
        diffEl.style.color = '#15803d';
    } else {
        diffEl.innerText = '' + diff;
        diffEl.style.color = '#ef4444';
    }
}
</script>
