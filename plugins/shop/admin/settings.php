<?php
/**
 * ============================================================================
 * WeaveCMS Shop & E-Commerce Plugin – Admin Einstellungs- & Verwaltungsbereich
 * ============================================================================
 * @var array $config Aktuelle Plugin-Konfiguration
 * @var string $csrf_token CSRF-Token
 */
require_once dirname(__DIR__) . '/ShopManager.php';

$config = $config ?? [];
$activeSubTab = $_GET['shop_subtab'] ?? $_POST['active_subtab'] ?? $_POST['shop_subtab'] ?? 'products';

// Produkte & Bestellungen laden
$products = ShopManager::getProducts();
$orders = ShopManager::getOrders();

// KPI-Berechnungen
$totalSales = array_sum(array_column($orders, 'total'));
$paidOrdersCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'paid' || ($o['status'] ?? '') === 'completed'));
$outOfStockCount = count(array_filter($products, fn($p) => empty($p['unlimited']) && ($p['stock'] ?? 0) <= 0));

$currency = $config['currency'] ?? 'EUR';
$shipping_cost = $config['shipping_cost'] ?? 4.90;
$free_shipping_min = $config['free_shipping_min'] ?? 50.0;
$small_business = !empty($config['small_business']);

$cc_enabled = !empty($config['payment_credit_card']['enabled']);
$cc_sandbox = !empty($config['payment_credit_card']['sandbox']);

$pp_enabled = !empty($config['payment_paypal']['enabled']);
$pp_sandbox = !empty($config['payment_paypal']['sandbox']);
$pp_client_id = $config['payment_paypal']['client_id'] ?? '';

$wero_enabled = !empty($config['payment_wero']['enabled']);
$wero_sandbox = !empty($config['payment_wero']['sandbox']);
$wero_name = $config['payment_wero']['merchant_name'] ?? 'WeaveCMS Shop';
$wero_iban = $config['payment_wero']['iban'] ?? 'DE89370400440532013000';
$wero_bic  = $config['payment_wero']['bic'] ?? 'BYLADEM1001';

$bt_enabled = !empty($config['payment_bank_transfer']['enabled']);
$bt_bank = $config['payment_bank_transfer']['bank_name'] ?? '';
$bt_holder = $config['payment_bank_transfer']['account_holder'] ?? '';
$bt_iban = $config['payment_bank_transfer']['iban'] ?? '';
$bt_bic = $config['payment_bank_transfer']['bic'] ?? '';
$bt_instruction = $config['payment_bank_transfer']['instruction'] ?? 'Bitte überweise den Gesamtbetrag unter Angabe deiner Bestellnummer als Verwendungszweck.';

$section_enabled = !empty($config['section_enabled']);
$section_title = $config['section_title'] ?? 'Unser Webshop & Produkte';
$section_subtitle = $config['section_subtitle'] ?? 'Handgefertigte Unikate, kreative DIY-Sets und Zubehör direkt online bestellen.';
?>

<div class="shop-admin-wrapper" style="margin-top: 1rem;">

    <!-- Header-Banner -->
    <div style="background: linear-gradient(135deg, rgba(46,98,72,0.12), rgba(30,58,138,0.08)); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display:flex; align-items:center; gap: 14px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(46,98,72,0.25);">
                <span class="material-icons" style="font-size: 28px;">shopping_bag</span>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800;">🛒 WeaveCMS Shop & Checkout</h3>
                <p style="margin: 2px 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                    Integrierte E-Commerce Engine mit Kreditkarte, PayPal und europäischem WERO Instant Payment.
                </p>
            </div>
        </div>

        <!-- Schnell-KPIs -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="background: var(--card-bg, #fff); padding: 8px 16px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Gesamtumsatz</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary);"><?php echo number_format($totalSales, 2, ',', '.'); ?> €</div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 8px 16px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Bestellungen</div>
                <div style="font-size: 1.15rem; font-weight: 800;"><?php echo count($orders); ?></div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 8px 16px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">Produkte</div>
                <div style="font-size: 1.15rem; font-weight: 800;"><?php echo count($products); ?></div>
            </div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <?php
    $shopSubTabs = [
        'products' => ['title' => 'Produkte (' . count($products) . ')', 'icon' => 'inventory_2'],
        'orders'   => ['title' => 'Bestellungen (' . count($orders) . ')', 'icon' => 'receipt_long'],
        'payments' => ['title' => 'Zahlungsarten (Vorkasse, CC, PayPal, WERO)', 'icon' => 'payments'],
        'settings' => ['title' => 'Shop-Einstellungen', 'icon' => 'tune'],
    ];

    if (class_exists('PluginManager')) {
        $shopSubTabs = PluginManager::applyFilters('shop_admin_subtabs', $shopSubTabs, $config);
    }
    ?>
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; flex-wrap: wrap;">
        <?php foreach ($shopSubTabs as $tabKey => $tabInfo): ?>
            <a href="?tab=tab-plugins&plugin_settings=shop&shop_subtab=<?php echo urlencode($tabKey); ?>#tab-plugins" 
               style="padding: 10px 18px; text-decoration: none; font-weight: 700; font-size: 0.92rem; border-bottom: 3px solid <?php echo $activeSubTab === $tabKey ? 'var(--primary)' : 'transparent'; ?>; color: <?php echo $activeSubTab === $tabKey ? 'var(--primary)' : 'var(--text-secondary)'; ?>; display: inline-flex; align-items: center; gap: 6px;">
                <?php if (!empty($tabInfo['icon'])): ?>
                    <span class="material-icons" style="font-size: 18px;"><?php echo htmlspecialchars($tabInfo['icon']); ?></span>
                <?php endif; ?>
                <?php echo htmlspecialchars($tabInfo['title']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 1: PRODUKTE VERWALTEN                                          -->
    <!-- ===================================================================== -->
    <?php if ($activeSubTab === 'products'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="margin: 0; font-size: 1.15rem; font-weight: 700;">Katalog & Artikel</h4>
            <button type="button" onclick="openShopProductModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; font-weight: 700;">
                <span class="material-icons" style="font-size: 18px;">add</span> Neues Produkt anlegen
            </button>
        </div>

        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                    <tr>
                        <th style="padding: 12px 16px;">Artikel</th>
                        <th style="padding: 12px 16px;">Kategorie</th>
                        <th style="padding: 12px 16px;">Preis</th>
                        <th style="padding: 12px 16px;">Lagerbestand</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                                Keine Produkte vorhanden. Klicke auf „Neues Produkt anlegen“.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                    <div style="font-size: 0.75rem; font-weight: 500; color: var(--text-secondary);">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="background: rgba(0,0,0,0.05); padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($p['category'] ?? 'Allgemein'); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700;">
                                    <?php echo number_format($p['price'], 2, ',', '.'); ?> €
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php if (!empty($p['unlimited'])): ?>
                                        <span style="color: #3b82f6; font-weight: 600;">Digital / Unbegrenzt</span>
                                    <?php elseif (($p['stock'] ?? 0) <= 0): ?>
                                        <span style="color: #ef4444; font-weight: 700;">Ausverkauft (0)</span>
                                    <?php else: ?>
                                        <span style="color: #15803d; font-weight: 600;"><?php echo (int)$p['stock']; ?> Stück</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php if (!empty($p['active'])): ?>
                                        <span style="color: #15803d; font-weight: 600;">Aktiv</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" 
                                                onclick='editShopProduct(<?php echo json_encode($p); ?>)'>
                                            <span class="material-icons" style="font-size: 16px;">edit</span>
                                        </button>
                                        <form method="POST" action="?tab=tab-plugins&plugin_settings=shop&shop_subtab=products#tab-plugins" onsubmit="return confirm('Möchtest du dieses Produkt wirklich löschen?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="shop_delete_product">
                                            <input type="hidden" name="target_tab" value="tab-plugins">
                                            <input type="hidden" name="plugin_slug" value="shop">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($p['id']); ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem; color: #ef4444;">
                                                <span class="material-icons" style="font-size: 16px;">delete</span>
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
    <!-- SUBTAB 2: BESTELLUNGEN VERWALTEN                                      -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubTab === 'orders'): ?>
        <h4 style="margin: 0 0 1rem; font-size: 1.15rem; font-weight: 700;">Eingegangene Bestellungen</h4>

        <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                    <tr>
                        <th style="padding: 12px 16px;">Bestell-Nr. / Datum</th>
                        <th style="padding: 12px 16px;">Kunde</th>
                        <th style="padding: 12px 16px;">Betrag</th>
                        <th style="padding: 12px 16px;">Zahlungsmethode</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" style="padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                                Noch keine Bestellungen eingegangen.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): 
                            $method = $o['payment_method'] ?? 'unknown';
                            $methodBadge = '💳 Kreditkarte';
                            if ($method === 'paypal') $methodBadge = '🅿️ PayPal';
                            if ($method === 'wero') $methodBadge = '🇪🇺 WERO';
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px;">
                                    <strong><?php echo htmlspecialchars($o['order_id']); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($o['created_at']); ?></div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($o['customer']['name'] ?? 'Gast'); ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-secondary);"><?php echo htmlspecialchars($o['customer']['email'] ?? ''); ?></div>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 800; color: var(--primary);">
                                    <?php echo number_format($o['total'], 2, ',', '.'); ?> €
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="background: rgba(0,0,0,0.05); padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                                        <?php echo $methodBadge; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <form method="POST" action="?tab=tab-plugins&plugin_settings=shop&shop_subtab=orders#tab-plugins" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="shop_update_order_status">
                                        <input type="hidden" name="target_tab" value="tab-plugins">
                                        <input type="hidden" name="plugin_slug" value="shop">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($o['order_id']); ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 3px 8px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.82rem; font-weight: 600;">
                                            <option value="paid" <?php echo ($o['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>🟢 Bezahlt</option>
                                            <option value="processing" <?php echo ($o['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>🟡 In Bearbeitung</option>
                                            <option value="shipped" <?php echo ($o['status'] ?? '') === 'shipped' ? 'selected' : ''; ?>>📦 Versendet</option>
                                            <option value="completed" <?php echo ($o['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>✅ Abgeschlossen</option>
                                            <option value="cancelled" <?php echo ($o['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>🔴 Storniert</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.82rem;" 
                                            onclick='viewShopOrder(<?php echo json_encode($o); ?>)'>
                                        <span class="material-icons" style="font-size: 16px;">visibility</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ===================================================================== -->
    <!-- SUBTAB 3: ZAHLUNGSARTEN (Kreditkarte, PayPal, WERO)                  -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubTab === 'payments'): ?>
        <form method="POST" action="?tab=tab-plugins&plugin_settings=shop&shop_subtab=payments#tab-plugins">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_plugin_settings">
            <input type="hidden" name="plugin_slug" value="shop">
            <input type="hidden" name="plugin_settings" value="shop">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="active_subtab" value="payments">

            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- 1. Kreditkarte -->
                <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem;">
                    <div style="display:flex; align-items:center; gap: 10px; margin-bottom: 1rem;">
                        <span style="font-size: 28px;">💳</span>
                        <div>
                            <h4 style="margin:0; font-size: 1.1rem; font-weight: 700;">Kreditkartenzahlung</h4>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">Visa, Mastercard, American Express</div>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_credit_card[enabled]" value="0">
                        <input type="checkbox" name="payment_credit_card[enabled]" value="1" <?php echo $cc_enabled ? 'checked' : ''; ?>>
                        <span>Kreditkarte als Bezahlmethode aktivieren</span>
                    </label>

                    <label style="display:flex; align-items:center; gap: 8px; font-size: 0.88rem; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_credit_card[sandbox]" value="0">
                        <input type="checkbox" name="payment_credit_card[sandbox]" value="1" <?php echo $cc_sandbox ? 'checked' : ''; ?>>
                        <span>Sandbox- / Testmodus (Autorisiert Testkarten)</span>
                    </label>

                    <div style="font-size: 0.8rem; color: var(--text-secondary); background: rgba(0,0,0,0.02); padding: 8px 10px; border-radius: 8px;">
                        Verwendet native Luhn-Prüfung und sichere clientseitige Validierung.
                    </div>
                </div>

                <!-- 2. PayPal -->
                <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem;">
                    <div style="display:flex; align-items:center; gap: 10px; margin-bottom: 1rem;">
                        <span style="font-size: 28px;">🅿️</span>
                        <div>
                            <h4 style="margin:0; font-size: 1.1rem; font-weight: 700;">PayPal</h4>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">Express Checkout & Käuferschutz</div>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_paypal[enabled]" value="0">
                        <input type="checkbox" name="payment_paypal[enabled]" value="1" <?php echo $pp_enabled ? 'checked' : ''; ?>>
                        <span>PayPal als Bezahlmethode aktivieren</span>
                    </label>

                    <label style="display:flex; align-items:center; gap: 8px; font-size: 0.88rem; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_paypal[sandbox]" value="0">
                        <input type="checkbox" name="payment_paypal[sandbox]" value="1" <?php echo $pp_sandbox ? 'checked' : ''; ?>>
                        <span>PayPal Sandbox-Modus</span>
                    </label>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">PayPal Händler-ID / E-Mail</label>
                        <input type="text" name="payment_paypal[client_id]" class="form-control" placeholder="paypal@meine-domain.de" value="<?php echo htmlspecialchars($pp_client_id); ?>">
                    </div>
                </div>

                <!-- 3. WERO (European Payments Initiative) -->
                <div style="background: var(--card-bg, #fff); border: 2px solid rgba(30,58,138,0.3); border-radius: 14px; padding: 1.5rem;">
                    <div style="display:flex; align-items:center; gap: 10px; margin-bottom: 1rem;">
                        <span style="font-size: 28px;">🇪🇺</span>
                        <div>
                            <h4 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #1e3a8a;">WERO (European Instant Payment)</h4>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">Europäische Bankenallianz & SEPA-Instant</div>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 700; color: #1e3a8a; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_wero[enabled]" value="0">
                        <input type="checkbox" name="payment_wero[enabled]" value="1" <?php echo $wero_enabled ? 'checked' : ''; ?>>
                        <span>WERO Bezahlung aktivieren</span>
                    </label>

                    <label style="display:flex; align-items:center; gap: 8px; font-size: 0.88rem; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_wero[sandbox]" value="0">
                        <input type="checkbox" name="payment_wero[sandbox]" value="1" <?php echo $wero_sandbox ? 'checked' : ''; ?>>
                        <span>WERO Testmodus / Simulator</span>
                    </label>

                    <div class="form-group" style="margin-bottom:8px;">
                        <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Empfänger-IBAN für Gutschriften *</label>
                        <input type="text" name="payment_wero[iban]" class="form-control" value="<?php echo htmlspecialchars($wero_iban); ?>" required>
                    </div>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">BIC / BLZ</label>
                            <input type="text" name="payment_wero[bic]" class="form-control" value="<?php echo htmlspecialchars($wero_bic); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Händler-Name</label>
                            <input type="text" name="payment_wero[merchant_name]" class="form-control" value="<?php echo htmlspecialchars($wero_name); ?>">
                        </div>
                    </div>
                </div>

                <!-- 4. Vorkasse / Banküberweisung -->
                <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem;">
                    <div style="display:flex; align-items:center; gap: 10px; margin-bottom: 1rem;">
                        <span style="font-size: 28px;">🏦</span>
                        <div>
                            <h4 style="margin:0; font-size: 1.1rem; font-weight: 700;">Vorkasse / Banküberweisung</h4>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">Klassische SEPA-Überweisung auf dein Geschäftskonto</div>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; cursor:pointer; margin-bottom: 10px;">
                        <input type="hidden" name="payment_bank_transfer[enabled]" value="0">
                        <input type="checkbox" name="payment_bank_transfer[enabled]" value="1" <?php echo $bt_enabled ? 'checked' : ''; ?>>
                        <span>Vorkasse als Bezahlmethode aktivieren</span>
                    </label>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Kontoinhaber</label>
                            <input type="text" name="payment_bank_transfer[account_holder]" class="form-control" value="<?php echo htmlspecialchars($bt_holder); ?>" placeholder="z. B. WeaveCMS Inhaber">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Bank / Institut</label>
                            <input type="text" name="payment_bank_transfer[bank_name]" class="form-control" value="<?php echo htmlspecialchars($bt_bank); ?>" placeholder="z. B. Sparkasse">
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 8px; margin-bottom: 8px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">IBAN</label>
                            <input type="text" name="payment_bank_transfer[iban]" class="form-control" value="<?php echo htmlspecialchars($bt_iban); ?>" placeholder="DE00 0000 0000 0000 0000 00">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">BIC</label>
                            <input type="text" name="payment_bank_transfer[bic]" class="form-control" value="<?php echo htmlspecialchars($bt_bic); ?>" placeholder="BANKDEFFXXX">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Hinweistext für den Kunden</label>
                        <textarea name="payment_bank_transfer[instruction]" class="form-control" rows="2"><?php echo htmlspecialchars($bt_instruction); ?></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; font-weight: 700;">
                <span class="material-icons">save</span> Zahlungsarten speichern
            </button>
        </form>

    <!-- ===================================================================== -->
    <!-- SUBTAB 4: SHOP-EINSTELLUNGEN                                          -->
    <!-- ===================================================================== -->
    <?php elseif ($activeSubTab === 'settings'): ?>
        <form method="POST" action="?tab=tab-plugins&plugin_settings=shop&shop_subtab=settings#tab-plugins">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_plugin_settings">
            <input type="hidden" name="plugin_slug" value="shop">
            <input type="hidden" name="plugin_settings" value="shop">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="active_subtab" value="settings">

            <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 1.25rem; font-size: 1.15rem; font-weight: 700;">Grundeinstellungen & Steuern</h4>

                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Währung</label>
                        <select name="currency" class="form-control">
                            <option value="EUR" <?php echo $currency === 'EUR' ? 'selected' : ''; ?>>EUR (€) – Euro</option>
                            <option value="CHF" <?php echo $currency === 'CHF' ? 'selected' : ''; ?>>CHF – Schweizer Franken</option>
                            <option value="USD" <?php echo $currency === 'USD' ? 'selected' : ''; ?>>USD ($) – US Dollar</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Standard-Versandkosten (€)</label>
                        <input type="number" step="0.01" name="shipping_cost" class="form-control" value="<?php echo htmlspecialchars($shipping_cost); ?>">
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight: 600; font-size: 0.9rem; display:block; margin-bottom: 6px;">Kostenloser Versand ab (€)</label>
                        <input type="number" step="0.01" name="free_shipping_min" class="form-control" value="<?php echo htmlspecialchars($free_shipping_min); ?>">
                    </div>
                </div>

                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem;">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; cursor:pointer;">
                        <input type="hidden" name="small_business" value="0">
                        <input type="checkbox" name="small_business" value="1" <?php echo $small_business ? 'checked' : ''; ?>>
                        <span>Kleinunternehmerregelung anwenden (§ 19 UStG: Keine Umsatzsteuerausweisung)</span>
                    </label>
                </div>

                <h4 style="margin: 0 0 1rem; font-size: 1.1rem; font-weight: 700;">Startseiten-Integration</h4>

                <label style="display:flex; align-items:center; gap: 8px; font-weight: 600; cursor:pointer; margin-bottom: 1rem;">
                    <input type="hidden" name="section_enabled" value="0">
                    <input type="checkbox" name="section_enabled" value="1" <?php echo $section_enabled ? 'checked' : ''; ?>>
                    <span>🌿 Eigenständige Shop-Sektion auf der Startseite aktivieren</span>
                </label>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Sektions-Überschrift</label>
                        <input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars($section_title); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Sektions-Untertitel</label>
                        <input type="text" name="section_subtitle" class="form-control" value="<?php echo htmlspecialchars($section_subtitle); ?>">
                    </div>
                </div>
            </div>

            <!-- Shortcode-Info -->
            <div style="background: rgba(0,0,0,0.03); border: 1px dashed var(--border-color); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.88rem; color: var(--text-secondary);">
                <strong style="color:var(--text-primary);"><span class="material-icons" style="font-size:16px; vertical-align:text-bottom;">code</span> Shortcode-Nutzung:</strong>
                Du kannst den Shop überall in Blogbeiträgen oder Einzelseiten einbetten mit:<br>
                <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-weight:600; color:var(--primary);">[shop]</code> oder 
                <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-weight:600; color:var(--primary);">[shop category="Flechtkunst"]</code>
            </div>

            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; font-weight: 700;">
                <span class="material-icons">save</span> Shop-Einstellungen speichern
            </button>
        </form>
    <?php else: ?>
        <?php
        // Action-Hook für Dritt-Plugins, um eigene Subtab-Inhalte im Shop-Admin zu rendern
        if (class_exists('PluginManager')) {
            PluginManager::doAction('shop_render_admin_subtab_' . $activeSubTab, $config, $csrf_token, $products, $orders);
        }
        ?>
    <?php endif; ?>

</div>

<!-- ========================================================================= -->
<!-- MODAL: PRODUKT ANLEGEN / BEARBEITEN                                      -->
<!-- ========================================================================= -->
<div id="shop-product-modal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
    <div style="background:var(--card-bg, #fff); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:580px; box-shadow:0 15px 40px rgba(0,0,0,0.25); overflow:hidden; margin:auto;">
        
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02);">
            <h4 id="shop-product-modal-title" style="margin:0; font-size:1.15rem; font-weight:700;">Neues Produkt</h4>
            <button type="button" onclick="closeShopProductModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="?tab=tab-plugins&plugin_settings=shop&shop_subtab=products#tab-plugins" style="padding:1.5rem;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" id="shop-prod-action" value="shop_add_product">
            <input type="hidden" name="target_tab" value="tab-plugins">
            <input type="hidden" name="plugin_slug" value="shop">
            <input type="hidden" name="product_id" id="shop-prod-id" value="">

            <div class="grid" style="grid-template-columns: 2fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Titel des Produkts *</label>
                    <input type="text" name="title" id="shop-prod-title" class="form-control" placeholder="z. B. Handgefertigter Weidenkorb" required>
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Artikel-Nr. (SKU)</label>
                    <input type="text" name="sku" id="shop-prod-sku" class="form-control" placeholder="ART-01">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Kategorie</label>
                    <input type="text" name="category" id="shop-prod-category" class="form-control" placeholder="z. B. Flechtkunst">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Preis in € *</label>
                    <input type="number" step="0.01" name="price" id="shop-prod-price" class="form-control" placeholder="29.90" required>
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Lagerbestand</label>
                    <input type="number" name="stock" id="shop-prod-stock" class="form-control" value="10">
                </div>
                <div style="display:flex; align-items:center; padding-top:20px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:600; cursor:pointer;">
                        <input type="hidden" name="unlimited" value="0">
                        <input type="checkbox" name="unlimited" id="shop-prod-unlimited" value="1">
                        <span>Digital / Unbegrenzt</span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Bild-Pfad (optional)</label>
                <input type="text" name="image" id="shop-prod-image" class="form-control" placeholder="images/uploads/mein_produkt.jpg">
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Beschreibung</label>
                <textarea name="description" id="shop-prod-desc" class="form-control" rows="3" placeholder="Details zum Produkt..."></textarea>
            </div>

            <?php
            // Action-Hook für Zusatzfelder im Produktmodal (z. B. Download-Datei-URL, Varianten)
            if (class_exists('PluginManager')) {
                PluginManager::doAction('shop_admin_product_modal_fields', $config);
            }
            ?>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:600; cursor:pointer;">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" id="shop-prod-active" value="1" checked>
                    <span>Im Shop sichtbar</span>
                </label>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-weight:700;">
                    Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BESTELLUNG ANSEHEN                                                -->
<!-- ========================================================================= -->
<div id="shop-order-modal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:1rem; overflow-y:auto;">
    <div style="background:var(--card-bg, #fff); border:1px solid var(--border-color); border-radius:16px; width:100%; max-width:550px; box-shadow:0 15px 40px rgba(0,0,0,0.25); overflow:hidden; margin:auto;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02);">
            <h4 id="order-modal-title" style="margin:0; font-size:1.15rem; font-weight:700;">Bestelldetails</h4>
            <button type="button" onclick="closeShopOrderModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <div id="order-modal-body" style="padding:1.5rem; max-height:70vh; overflow-y:auto; font-size:0.9rem;"></div>
    </div>
</div>

<script>
function openShopProductModal() {
    document.getElementById('shop-product-modal-title').innerText = 'Neues Produkt anlegen';
    document.getElementById('shop-prod-action').value = 'shop_add_product';
    document.getElementById('shop-prod-id').value = '';
    document.getElementById('shop-prod-title').value = '';
    document.getElementById('shop-prod-sku').value = '';
    document.getElementById('shop-prod-category').value = '';
    document.getElementById('shop-prod-price').value = '';
    document.getElementById('shop-prod-stock').value = '10';
    document.getElementById('shop-prod-unlimited').checked = false;
    document.getElementById('shop-prod-image').value = '';
    document.getElementById('shop-prod-desc').value = '';
    document.getElementById('shop-prod-active').checked = true;
    document.getElementById('shop-product-modal').style.display = 'flex';
}

function editShopProduct(p) {
    document.getElementById('shop-product-modal-title').innerText = 'Produkt bearbeiten';
    document.getElementById('shop-prod-action').value = 'shop_edit_product';
    document.getElementById('shop-prod-id').value = p.id;
    document.getElementById('shop-prod-title').value = p.title || '';
    document.getElementById('shop-prod-sku').value = p.sku || '';
    document.getElementById('shop-prod-category').value = p.category || '';
    document.getElementById('shop-prod-price').value = p.price || '';
    document.getElementById('shop-prod-stock').value = p.stock || 0;
    document.getElementById('shop-prod-unlimited').checked = !!p.unlimited;
    document.getElementById('shop-prod-image').value = p.image || '';
    document.getElementById('shop-prod-desc').value = p.description || '';
    document.getElementById('shop-prod-active').checked = !!p.active;
    document.getElementById('shop-product-modal').style.display = 'flex';
}

function closeShopProductModal() {
    document.getElementById('shop-product-modal').style.display = 'none';
}

let currentOrderToPrint = null;

function viewShopOrder(o) {
    currentOrderToPrint = o;
    const titleEl = document.getElementById('order-modal-title');
    const bodyEl = document.getElementById('order-modal-body');
    if (!titleEl || !bodyEl) return;

    titleEl.innerText = 'Bestellung ' + o.order_id;
    const itemsHtml = (o.items || []).map(i => `
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span>${i.qty}x ${i.title}</span>
            <strong>${(i.total || (i.price * i.qty)).toFixed(2).replace('.', ',')} €</strong>
        </div>
    `).join('');

    bodyEl.innerHTML = `
        <div style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border-color);">
            <div style="font-size:0.8rem; color:var(--text-secondary);">Empfänger:</div>
            <strong>${o.customer?.name || ''}</strong><br>
            ${o.customer?.street || ''}<br>
            ${o.customer?.zip || ''} ${o.customer?.city || ''}<br>
            <span style="color:var(--text-secondary); font-size:0.85rem;">E-Mail: ${o.customer?.email || ''}</span>
        </div>
        <div style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border-color);">
            <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:6px;">Artikel:</div>
            ${itemsHtml}
        </div>
        <div style="display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800; color:var(--primary);">
            <span>Gesamtsumme:</span>
            <span>${parseFloat(o.total).toFixed(2).replace('.', ',')} €</span>
        </div>
        <div style="margin-top:8px; font-size:0.8rem; color:var(--text-secondary);">
            Zahlungsart: <strong>${(o.payment_method || '').toUpperCase()}</strong> (Ref: ${o.payment_ref || '-'}) &bull; Status: <strong>${o.status || 'pending'}</strong>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border-color);">
            <button type="button" class="btn btn-outline" onclick="printShopInvoice()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; font-size:0.88rem;">
                <span class="material-icons" style="font-size:18px;">print</span> Rechnung / Beleg drucken
            </button>
            <button type="button" class="btn btn-primary" onclick="closeShopOrderModal()" style="padding:6px 16px; font-size:0.88rem;">Schließen</button>
        </div>
    `;
    document.getElementById('shop-order-modal').style.display = 'flex';
}

function printShopInvoice() {
    if (!currentOrderToPrint) return;
    const o = currentOrderToPrint;
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    if (!printWindow) {
        alert('Popup-Fenster wurde blockiert. Bitte erlaube Popups für den Rechnungsdruck.');
        return;
    }

    const itemsRows = (o.items || []).map((it, idx) => `
        <tr>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:center;">${idx + 1}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb;">
                <strong>${it.title || 'Artikel'}</strong>
                ${it.sku ? '<div style="font-size:11px; color:#6b7280;">Art-Nr: ' + it.sku + '</div>' : ''}
            </td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:center;">${it.qty}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:right;">${parseFloat(it.price).toFixed(2).replace('.', ',')} €</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:right; font-weight:600;">${parseFloat(it.total || (it.price * it.qty)).toFixed(2).replace('.', ',')} €</td>
        </tr>
    `).join('');

    const statusBadge = (o.status === 'paid' || o.status === 'completed') ? 'Bezahlt' : (o.status === 'pending' ? 'Zahlung ausstehend' : (o.status || 'Offen'));

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <title>Rechnung_${o.order_id}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #111827; padding: 40px; margin: 0; font-size: 14px; line-height: 1.5; }
                .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #2e6248; padding-bottom: 20px; margin-bottom: 30px; }
                .shop-name { font-size: 24px; font-weight: 800; color: #2e6248; }
                .inv-title { font-size: 20px; font-weight: 700; text-align: right; }
                .details-grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .addr-box { width: 48%; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                th { background: #f3f4f6; color: #374151; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
                .totals-box { margin-left: auto; width: 300px; margin-bottom: 30px; }
                .totals-row { display: flex; justify-content: space-between; padding: 5px 0; }
                .totals-total { border-top: 2px solid #111827; margin-top: 5px; padding-top: 8px; font-weight: 800; font-size: 16px; color: #2e6248; }
                .footer { border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 11px; color: #6b7280; text-align: center; margin-top: 40px; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom:20px; text-align:right;">
                <button onclick="window.print()" style="padding:8px 16px; background:#2e6248; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:700;">Drucken / PDF speichern</button>
            </div>
            <div class="invoice-header">
                <div>
                    <div class="shop-name">WeaveCMS Shop</div>
                    <div style="color:#6b7280; font-size:12px;">Bestell- & Rechnungsbeleg</div>
                </div>
                <div class="inv-title">
                    <div>RECHNUNG</div>
                    <div style="font-size:14px; font-weight:normal; color:#4b5563; margin-top:4px;">Nr. ${o.order_id}</div>
                    <div style="font-size:13px; font-weight:normal; color:#6b7280;">Datum: ${o.created_at || new Date().toLocaleDateString('de-DE')}</div>
                </div>
            </div>

            <div class="details-grid">
                <div class="addr-box">
                    <div style="font-size:11px; text-transform:uppercase; color:#6b7280; font-weight:700; margin-bottom:4px;">Rechnungsempfänger:</div>
                    <strong>${o.customer?.name || ''}</strong><br>
                    ${o.customer?.street || ''}<br>
                    ${o.customer?.zip || ''} ${o.customer?.city || ''}<br>
                    <span style="color:#4b5563;">E-Mail: ${o.customer?.email || ''}</span>
                </div>
                <div class="addr-box" style="text-align:right;">
                    <div style="font-size:11px; text-transform:uppercase; color:#6b7280; font-weight:700; margin-bottom:4px;">Bestelldetails:</div>
                    Zahlungsart: <strong>${(o.payment_method || '').toUpperCase()}</strong><br>
                    Referenz: ${o.payment_ref || '-'}<br>
                    Status: <strong>${statusBadge}</strong>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">Pos</th>
                        <th>Artikel</th>
                        <th style="width:60px; text-align:center;">Menge</th>
                        <th style="width:100px; text-align:right;">Einzelpreis</th>
                        <th style="width:100px; text-align:right;">Gesamt</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
            </table>

            <div class="totals-box">
                <div class="totals-row">
                    <span>Zwischensumme:</span>
                    <span>${parseFloat(o.subtotal || 0).toFixed(2).replace('.', ',')} €</span>
                </div>
                <div class="totals-row">
                    <span>Versandkosten:</span>
                    <span>${parseFloat(o.shipping || 0) > 0 ? (parseFloat(o.shipping).toFixed(2).replace('.', ',') + ' €') : 'Kostenlos'}</span>
                </div>
                ${parseFloat(o.tax || 0) > 0 ? `
                <div class="totals-row">
                    <span>Inkl. MwSt.:</span>
                    <span>${parseFloat(o.tax).toFixed(2).replace('.', ',')} €</span>
                </div>
                ` : ''}
                <div class="totals-row totals-total">
                    <span>Gesamtbetrag:</span>
                    <span>${parseFloat(o.total || 0).toFixed(2).replace('.', ',')} €</span>
                </div>
            </div>

            <div class="footer">
                Vielen Dank für Ihren Auftrag! Bei Fragen wenden Sie sich bitte an unseren Support.
            </div>

            <script>
                window.onload = function() { window.print(); };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function closeShopOrderModal() {
    document.getElementById('shop-order-modal').style.display = 'none';
}
</script>
