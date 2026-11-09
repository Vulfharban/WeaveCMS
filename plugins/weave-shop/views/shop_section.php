<?php
/**
 * ============================================================================
 * WeaveCMS Shop & E-Commerce Plugin – Frontend Shop-Sektion & Produktkatalog
 * ============================================================================
 * @var array $config Plugin-Konfiguration
 * @var array $params Zusätzliche Parameter
 */
require_once dirname(__DIR__) . '/ShopManager.php';

$config = $config ?? [];
$params = $params ?? [];

$title = $params['title'] ?? ($config['section_title'] ?? 'Unser Webshop & Produkte');
$subtitle = $params['subtitle'] ?? ($config['section_subtitle'] ?? 'Handgefertigte Unikate, kreative DIY-Sets und Zubehör direkt online bestellen.');
$currency = $config['currency'] ?? 'EUR';
$isSmallBiz = !empty($config['small_business']);

$btConfig = $config['payment_bank_transfer'] ?? [];
$bt_enabled = !empty($btConfig['enabled']);
$bt_bank = $btConfig['bank_name'] ?? '';
$bt_holder = $btConfig['account_holder'] ?? '';
$bt_iban = $btConfig['iban'] ?? '';
$bt_bic = $btConfig['bic'] ?? '';
$bt_instruction = $btConfig['instruction'] ?? 'Bitte überweise den Gesamtbetrag unter Angabe deiner Bestellnummer als Verwendungszweck.';

$allProducts = ShopManager::getProducts();
$activeProducts = array_filter($allProducts, function($p) {
    return !empty($p['active']);
});

// Kategorien sammeln
$categories = ['Alle'];
foreach ($activeProducts as $p) {
    $cat = trim($p['category'] ?? '');
    if (!empty($cat) && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
?>

<div id="shop" class="shop-section-container" style="margin: 3rem 0; scroll-margin-top: 100px; position: relative;">
    <!-- Sektions-Kopf -->
    <div class="shop-header" style="text-align: center; max-width: 800px; margin: 0 auto 2.5rem;">
        <div style="display:inline-flex; align-items:center; gap:8px; padding: 6px 16px; border-radius: 20px; background: rgba(46,98,72,0.1); color: var(--primary); font-size: 0.88rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
            <span class="material-icons" style="font-size: 18px;">storefront</span>
            <span>Online-Shop</span>
        </div>
        <h2 style="font-size: 2.2rem; margin: 0 0 0.75rem; color: var(--text-primary);"><?php echo htmlspecialchars($title); ?></h2>
        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.6; margin: 0;">
            <?php echo htmlspecialchars($subtitle); ?>
        </p>

        <!-- Bezahlmethoden-Badges -->
        <div style="display: flex; justify-content: center; align-items: center; gap: 14px; margin-top: 1.25rem; flex-wrap: wrap;">
            <span style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 600;">Sichere Bezahlung:</span>
            <span class="shop-pay-badge" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 6px; background: rgba(0,0,0,0.04); font-size: 0.8rem; font-weight: 600;">
                💳 Kreditkarte (Visa, Mastercard)
            </span>
            <span class="shop-pay-badge" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 6px; background: rgba(0,0,0,0.04); font-size: 0.8rem; font-weight: 600;">
                🅿️ PayPal
            </span>
            <span class="shop-pay-badge" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 6px; background: rgba(30,58,138,0.1); color: #1e3a8a; font-size: 0.8rem; font-weight: 700;">
                🇪🇺 WERO (European Instant Payment)
            </span>
            <?php if (!empty($bt_enabled)): ?>
            <span class="shop-pay-badge" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 6px; background: rgba(0,0,0,0.04); font-size: 0.8rem; font-weight: 600;">
                🏦 Vorkasse / Überweisung
            </span>
            <?php endif; ?>
        </div>

        <!-- Kategorie-Filter -->
        <?php if (count($categories) > 2): ?>
            <div class="shop-filter-bar" style="display: flex; justify-content: center; gap: 8px; margin-top: 2rem; flex-wrap: wrap;">
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="shop-cat-btn <?php echo $cat === 'Alle' ? 'active' : ''; ?>" 
                            onclick="filterShopCategory('<?php echo htmlspecialchars($cat); ?>', this)"
                            style="padding: 7px 18px; border-radius: 20px; border: 1px solid var(--border-color); background: <?php echo $cat === 'Alle' ? 'var(--primary)' : 'var(--card-bg, #fff)'; ?>; color: <?php echo $cat === 'Alle' ? '#fff' : 'var(--text-primary)'; ?>; font-weight: 600; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease;">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Produkt-Grid -->
    <?php if (empty($activeProducts)): ?>
        <div style="text-align: center; padding: 3rem 1rem; background: rgba(0,0,0,0.02); border: 2px dashed var(--border-color); border-radius: 16px; max-width: 500px; margin: 0 auto;">
            <span class="material-icons" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 0.5rem;">production_quantity_limits</span>
            <h4 style="margin: 0 0 0.5rem; font-size: 1.15rem;">Aktuell keine Produkte verfügbar</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">Schau bald wieder vorbei!</p>
        </div>
    <?php else: ?>
        <div class="shop-products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.75rem;">
            <?php foreach ($activeProducts as $p): 
                $inStock = !empty($p['unlimited']) || ($p['stock'] ?? 0) > 0;
                $stockCount = (int)($p['stock'] ?? 0);
            ?>
                <div class="shop-product-card" data-category="<?php echo htmlspecialchars($p['category'] ?? 'Allgemein'); ?>" 
                     style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    
                    <!-- Produktbild / Vorschau -->
                    <div style="position: relative; width: 100%; height: 210px; background: rgba(0,0,0,0.03); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($p['image']) && file_exists(dirname(__DIR__, 2) . '/' . ltrim($p['image'], '/'))): ?>
                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; align-items: center; color: var(--text-secondary); opacity: 0.6;">
                                <span class="material-icons" style="font-size: 48px;">category</span>
                                <span style="font-size: 0.75rem; font-weight: 600; margin-top: 4px;"><?php echo htmlspecialchars($p['sku']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Kategorie-Tag -->
                        <span style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            <?php echo htmlspecialchars($p['category'] ?? 'Allgemein'); ?>
                        </span>

                        <!-- Lager-Status -->
                        <?php if (!$inStock): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #ef4444; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                Ausverkauft
                            </span>
                        <?php elseif (!empty($p['unlimited'])): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #3b82f6; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                Sofort verfügbar
                            </span>
                        <?php elseif ($stockCount <= 3): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #f59e0b; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                Nur noch <?php echo $stockCount; ?> Stück
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Produkt-Details -->
                    <div style="padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 1.15rem; margin: 0 0 6px; font-weight: 700; color: var(--text-primary); line-height: 1.35;">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </h3>
                        <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; margin: 0 0 1rem; flex-grow: 1;">
                            <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                        </p>

                        <!-- Preis & Kaufen-Aktion -->
                        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: auto;">
                            <div>
                                <div style="font-size: 1.35rem; font-weight: 800; color: var(--text-primary);">
                                    <?php echo number_format($p['price'], 2, ',', '.'); ?> €
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?php if ($isSmallBiz): ?>
                                        Gem. § 19 UStG steuerfrei
                                    <?php else: ?>
                                        inkl. <?php echo (int)($p['tax_rate'] ?? 19); ?> % MwSt.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button type="button" 
                                    class="btn btn-primary shop-add-cart-btn" 
                                    <?php echo !$inStock ? 'disabled' : ''; ?>
                                    onclick="shopAddToCart('<?php echo htmlspecialchars($p['id']); ?>', '<?php echo htmlspecialchars(addslashes($p['title'])); ?>', <?php echo (float)$p['price']; ?>, this)"
                                    style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 0.9rem; font-weight: 700; border-radius: 10px; <?php echo !$inStock ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                                <span class="material-icons" style="font-size: 18px;">add_shopping_cart</span>
                                <span><?php echo $inStock ? 'Kaufen' : 'Weg'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- SCHWEBENDER WARENKORB-BUTTON (FLOATING CART TRIGGER)                     -->
<!-- ========================================================================= -->
<div id="shop-cart-floating" onclick="toggleShopCartDrawer()" style="position: fixed; bottom: 28px; right: 28px; z-index: 998; cursor: pointer; transition: transform 0.2s ease;">
    <div style="width: 58px; height: 58px; border-radius: 50%; background: var(--primary); color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; position: relative;">
        <span class="material-icons" style="font-size: 26px;">shopping_cart</span>
        <span id="shop-cart-count-badge" style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: #fff; font-size: 0.72rem; font-weight: 800; min-width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
            0
        </span>
    </div>
</div>

<!-- ========================================================================= -->
<!-- WARENKORB SLIDE-OUT DRAWER                                               -->
<!-- ========================================================================= -->
<div id="shop-cart-backdrop" onclick="toggleShopCartDrawer()" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); backdrop-filter: blur(3px); z-index: 1000;"></div>

<div id="shop-cart-drawer" style="position: fixed; top: 0; right: -420px; width: 100%; max-width: 400px; height: 100%; background: var(--card-bg, #fff); z-index: 1001; box-shadow: -10px 0 35px rgba(0,0,0,0.2); transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column;">
    <!-- Drawer Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.015);">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="color: var(--primary); font-size: 24px;">shopping_bag</span>
            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--text-primary);">Dein Warenkorb</h3>
        </div>
        <button type="button" onclick="toggleShopCartDrawer()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary); padding: 4px; border-radius: 6px;">
            &times;
        </button>
    </div>

    <!-- Drawer Body / Artikel-Liste -->
    <div id="shop-cart-items-container" style="flex-grow: 1; overflow-y: auto; padding: 1.25rem 1.5rem;">
        <div id="shop-cart-empty-msg" style="text-align: center; padding: 4rem 1rem; color: var(--text-secondary);">
            <span class="material-icons" style="font-size: 52px; opacity: 0.4; margin-bottom: 0.5rem;">remove_shopping_cart</span>
            <p style="margin: 0; font-size: 0.95rem;">Dein Warenkorb ist noch leer.</p>
        </div>
        <div id="shop-cart-items-list"></div>
        <?php
        if (class_exists('PluginManager')) {
            PluginManager::doAction('shop_cart_after_items', $config);
        }
        ?>
    </div>

    <!-- Drawer Footer / Summen & Checkout-Button -->
    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.015);">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 6px;">
            <span>Zwischensumme:</span>
            <span id="shop-cart-subtotal" style="font-weight: 600; color: var(--text-primary);">0,00 €</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 8px;">
            <span>Versandkosten:</span>
            <span id="shop-cart-shipping" style="font-weight: 600; color: var(--text-primary);">0,00 €</span>
        </div>
        <?php
        if (class_exists('PluginManager')) {
            PluginManager::doAction('shop_cart_summary_rows', $config);
        }
        ?>
        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; color: var(--text-primary); border-top: 1px dashed var(--border-color); padding-top: 8px; margin-bottom: 1.25rem;">
            <span>Gesamtbetrag:</span>
            <span id="shop-cart-total" style="color: var(--primary);">0,00 €</span>
        </div>

        <button type="button" id="shop-checkout-btn" onclick="openShopCheckout()" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; font-size: 1rem; font-weight: 700; border-radius: 10px;">
            <span class="material-icons">lock</span>
            <span>Sicher zur Kasse gehen</span>
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CHECKOUT & BEZAHL-MODAL (Kreditkarte, PayPal, WERO)                      -->
<!-- ========================================================================= -->
<div id="shop-checkout-modal" style="display:none; position: fixed; inset: 0; z-index: 1050; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 18px; width: 100%; max-width: 620px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden; margin: auto; animation: modalFadeIn 0.25s ease;">
        
        <!-- Modal Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="material-icons" style="color: var(--primary); font-size: 26px;">verified_user</span>
                <div>
                    <h3 style="margin:0; font-size: 1.25rem; font-weight: 700;">Sichere WeaveCMS Kasse</h3>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">SSL-verschlüsselte Bestellung & Zahlung</div>
                </div>
            </div>
            <button type="button" onclick="closeShopCheckout()" style="background:none; border:none; font-size: 26px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        </div>

        <!-- Modal Body (Schritte) -->
        <div id="shop-checkout-body" style="padding: 1.75rem; max-height: 75vh; overflow-y: auto;">
            
            <!-- Schritt 1: Adresse & Kundendaten -->
            <div id="checkout-step-1">
                <div style="font-weight: 700; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 0.8rem; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <span>Rechnungs- & Lieferadresse</span>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Vollständiger Name *</label>
                        <input type="text" id="cust_name" class="form-control" placeholder="Max Mustermann" required>
                    </div>
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">E-Mail-Adresse *</label>
                        <input type="email" id="cust_email" class="form-control" placeholder="max@beispiel.de" required>
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Straße & Hausnummer *</label>
                    <input type="text" id="cust_street" class="form-control" placeholder="Musterstraße 12" required>
                </div>

                <div class="grid" style="grid-template-columns: 120px 1fr; gap: 10px; margin-bottom: 1.25rem;">
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">PLZ *</label>
                        <input type="text" id="cust_zip" class="form-control" placeholder="12345" required>
                    </div>
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Stadt *</label>
                        <input type="text" id="cust_city" class="form-control" placeholder="Musterstadt" required>
                    </div>
                </div>

                <!-- Schritt 2: Zahlungsart wählen -->
                <div style="font-weight: 700; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 0.8rem; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <span>Zahlungsmethode auswählen</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 1.25rem;">
                    <!-- Kreditkarte -->
                    <label class="shop-method-card" id="label-pay-cc" style="border: 2px solid var(--primary); border-radius: 12px; padding: 12px 10px; text-align: center; cursor: pointer; background: rgba(46,98,72,0.05); transition: all 0.2s ease;">
                        <input type="radio" name="pay_method" value="credit_card" checked onchange="selectPaymentMethod('credit_card')" style="display:none;">
                        <div style="font-size: 22px; margin-bottom: 4px;">💳</div>
                        <div style="font-weight: 700; font-size: 0.88rem;">Kreditkarte</div>
                        <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Visa, Mastercard</div>
                    </label>

                    <!-- PayPal -->
                    <label class="shop-method-card" id="label-pay-pp" style="border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 10px; text-align: center; cursor: pointer; background: var(--card-bg, #fff); transition: all 0.2s ease;">
                        <input type="radio" name="pay_method" value="paypal" onchange="selectPaymentMethod('paypal')" style="display:none;">
                        <div style="font-size: 22px; margin-bottom: 4px;">🅿️</div>
                        <div style="font-weight: 700; font-size: 0.88rem;">PayPal</div>
                        <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Express & Käuferschutz</div>
                    </label>

                    <!-- WERO -->
                    <label class="shop-method-card" id="label-pay-wero" style="border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 10px; text-align: center; cursor: pointer; background: var(--card-bg, #fff); transition: all 0.2s ease;">
                        <input type="radio" name="pay_method" value="wero" onchange="selectPaymentMethod('wero')" style="display:none;">
                        <div style="font-size: 22px; margin-bottom: 4px;">🇪🇺</div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: #1e3a8a;">WERO</div>
                        <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Bank-Sofortzahlung</div>
                    </label>

                    <!-- Vorkasse / Überweisung -->
                    <label class="shop-method-card" id="label-pay-bt" style="border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 10px; text-align: center; cursor: pointer; background: var(--card-bg, #fff); transition: all 0.2s ease;">
                        <input type="radio" name="pay_method" value="bank_transfer" onchange="selectPaymentMethod('bank_transfer')" style="display:none;">
                        <div style="font-size: 22px; margin-bottom: 4px;">🏦</div>
                        <div style="font-weight: 700; font-size: 0.88rem;">Vorkasse</div>
                        <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Banküberweisung</div>
                    </label>
                </div>

                <!-- Zahlungsdetails-Formulare -->
                
                <!-- 1. Kreditkarten-Formular -->
                <div id="pay-fields-credit_card" style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 8px;">Kartendaten eingeben:</div>
                    <div style="margin-bottom: 8px;">
                        <input type="text" id="cc_holder" class="form-control" placeholder="Karteninhaber (z. B. Max Mustermann)">
                    </div>
                    <div style="margin-bottom: 8px;">
                        <input type="text" id="cc_number" class="form-control" placeholder="Kartennummer (z. B. 4532 0000 0000 0000)" maxlength="19">
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div style="display: flex; gap: 4px;">
                            <input type="number" id="cc_month" class="form-control" placeholder="MM" min="1" max="12" style="text-align:center;">
                            <input type="number" id="cc_year" class="form-control" placeholder="YY" min="25" max="40" style="text-align:center;">
                        </div>
                        <div>
                            <input type="password" id="cc_cvc" class="form-control" placeholder="CVC / Prüfziffer" maxlength="4" style="text-align:center;">
                        </div>
                    </div>
                </div>

                <!-- 2. PayPal Info -->
                <div id="pay-fields-paypal" style="display:none; background: rgba(0,112,186,0.06); border: 1px solid rgba(0,112,186,0.2); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 6px;">🅿️</div>
                    <div style="font-weight: 700; color: #0070ba; margin-bottom: 4px;">PayPal Express Checkout</div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                        Nach Klick auf „Zahlungspflichtig bestellen“ wird deine Zahlung sicher über PayPal abgewickelt und direkt autorisiert.
                    </p>
                </div>

                <!-- 3. WERO European Instant Payment Info -->
                <div id="pay-fields-wero" style="display:none; background: rgba(30,58,138,0.06); border: 1px solid rgba(30,58,138,0.25); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 6px;">🇪🇺</div>
                    <div style="font-weight: 800; color: #1e3a8a; margin-bottom: 4px;">WERO – Das neue europäische Zahlverfahren</div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0 0 10px;">
                        Direkte Konto-zu-Konto Sofortzahlung deiner europäischen Bank (Sparkassen, Volksbanken, Deutsche Bank, BNP, uvm.) per QR-Code oder Banking-App.
                    </p>
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; background: #fff; border: 1px solid #1e3a8a; color: #1e3a8a; font-size: 0.78rem; font-weight: 700;">
                        <span class="material-icons" style="font-size: 16px;">qr_code_2</span> Instant Payment in Sekunden
                    </div>
                </div>

                <!-- 4. Vorkasse Banküberweisung Info -->
                <div id="pay-fields-bank_transfer" style="display:none; background: rgba(46,98,72,0.05); border: 1px solid rgba(46,98,72,0.25); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span style="font-size:24px;">🏦</span>
                        <div>
                            <div style="font-weight: 700; color: var(--primary);">Zahlung per Banküberweisung (Vorkasse)</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Du erhältst unsere Kontodaten nach der Bestellung zusätzlich per E-Mail.</div>
                        </div>
                    </div>
                    <?php if (!empty($bt_iban)): ?>
                    <div style="background:#fff; border:1px solid var(--border-color); border-radius:8px; padding:10px 14px; font-size:0.85rem; margin-top:8px;">
                        <?php if (!empty($bt_holder)): ?><div>Empfänger: <strong><?php echo htmlspecialchars($bt_holder); ?></strong></div><?php endif; ?>
                        <?php if (!empty($bt_bank)): ?><div>Bank: <strong><?php echo htmlspecialchars($bt_bank); ?></strong></div><?php endif; ?>
                        <div>IBAN: <code style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($bt_iban); ?></code></div>
                        <?php if (!empty($bt_bic)): ?><div>BIC: <strong><?php echo htmlspecialchars($bt_bic); ?></strong></div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <p style="font-size: 0.82rem; color: var(--text-secondary); margin: 8px 0 0 0;">
                        <?php echo htmlspecialchars($bt_instruction); ?>
                    </p>
                </div>

                <?php
                if (class_exists('PluginManager')) {
                    PluginManager::doAction('shop_checkout_payment_fields', $config);
                }
                ?>

                <!-- Gesamtsumme & Bestellen-Button -->
                <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 0.82rem; color: var(--text-secondary);">Zu zahlender Endbetrag:</div>
                        <div id="checkout-modal-total" style="font-size: 1.4rem; font-weight: 800; color: var(--primary);">0,00 €</div>
                    </div>
                    <button type="button" id="submit-order-btn" onclick="submitShopOrder()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; font-size: 1rem; font-weight: 700; border-radius: 10px;">
                        <span class="material-icons">check_circle</span>
                        <span>Zahlungspflichtig bestellen</span>
                    </button>
                </div>
            </div>

            <!-- Schritt 2 (Erfolg / WERO QR / Bestellbestätigung) -->
            <div id="checkout-success-view" style="display:none; text-align: center; padding: 1.5rem 0;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <span class="material-icons" style="font-size: 36px;">done_all</span>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin: 0 0 6px; color: var(--text-primary);">Bestellung erfolgreich abgeschlossen!</h3>
                <p id="order-success-msg" style="color: var(--text-secondary); font-size: 0.95rem; margin: 0 0 1.5rem;">
                    Vielen Dank für deine Bestellung. Wir haben dir eine Bestätigung per E-Mail gesendet.
                </p>

                <!-- WERO QR-Code Box falls mit WERO bezahlt -->
                <div id="wero-qr-container" style="display:none; background: rgba(30,58,138,0.04); border: 2px dashed #1e3a8a; border-radius: 14px; padding: 1.5rem; max-width: 380px; margin: 0 auto 1.5rem;">
                    <div style="font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">WERO Instant-Payment QR-Code</div>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 12px;">
                        Scanne den Code mit deiner Bank-App oder WERO App zur finalen Freigabe:
                    </p>
                    <div id="wero-qr-canvas-wrapper" style="background:#fff; padding:12px; border-radius:10px; display:inline-block; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                        <canvas id="wero-qr-canvas" width="180" height="180" style="display:block;"></canvas>
                    </div>
                    <div id="wero-ref-code" style="font-size:0.75rem; font-family:monospace; margin-top:8px; color:var(--text-secondary);"></div>
                </div>

                <!-- Vorkasse Banküberweisung Box -->
                <div id="bt-transfer-container" style="display:none; background: rgba(46,98,72,0.05); border: 2px dashed var(--primary); border-radius: 14px; padding: 1.5rem; max-width: 440px; margin: 0 auto 1.5rem; text-align: left;">
                    <div style="font-weight: 800; color: var(--primary); margin-bottom: 6px; text-align: center;">🏦 Überweisungsinformationen</div>
                    <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 12px; text-align: center;">
                        Bitte überweise den Gesamtbetrag unter Angabe deiner Bestellnummer auf folgendes Konto:
                    </p>
                    <div style="font-size: 0.85rem; line-height: 1.6; background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <?php if (!empty($bt_holder)): ?><div>Empfänger: <strong><?php echo htmlspecialchars($bt_holder); ?></strong></div><?php endif; ?>
                        <?php if (!empty($bt_bank)): ?><div>Bank: <strong><?php echo htmlspecialchars($bt_bank); ?></strong></div><?php endif; ?>
                        <div>IBAN: <strong style="color:var(--primary);"><?php echo htmlspecialchars($bt_iban); ?></strong></div>
                        <?php if (!empty($bt_bic)): ?><div>BIC: <strong><?php echo htmlspecialchars($bt_bic); ?></strong></div><?php endif; ?>
                        <div>Verwendungszweck: <strong id="bt-success-ref" style="color:var(--primary);"></strong></div>
                    </div>
                </div>

                <!-- Bestelldaten Kachel -->
                <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem 1.25rem; text-align: left; max-width: 440px; margin: 0 auto 1.5rem; font-size: 0.88rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color:var(--text-secondary);">Bestellnummer:</span>
                        <strong id="success-order-id">ORD-...</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color:var(--text-secondary);">Zahlungsart:</span>
                        <strong id="success-payment-method">Kreditkarte</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color:var(--text-secondary);">Gesamtbetrag:</span>
                        <strong id="success-order-total" style="color: var(--primary);">0,00 €</strong>
                    </div>
                </div>

                <button type="button" onclick="closeShopCheckout()" class="btn btn-outline" style="padding: 10px 24px; border-radius: 10px; font-weight: 700;">
                    Zurück zum Shop
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ENGINE FÜR WARENKORB & CHECKOUT                                -->
<!-- ========================================================================= -->
<script>
// Lokaler Warenkorb im localStorage
let shopCart = [];
try {
    shopCart = JSON.parse(localStorage.getItem('weave_shop_cart') || '[]');
} catch(e) {
    shopCart = [];
}

let activePaymentMethod = 'credit_card';

function saveShopCart() {
    localStorage.setItem('weave_shop_cart', JSON.stringify(shopCart));
    renderShopCartUI();
}

function shopAddToCart(id, title, price, btnEl) {
    const existing = shopCart.find(i => i.id === id);
    if (existing) {
        existing.qty += 1;
    } else {
        shopCart.push({ id: id, qty: 1 });
    }
    saveShopCart();

    // Button Feedback
    if (btnEl) {
        const origText = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="material-icons" style="font-size:18px;">check</span> <span>Im Korb!</span>';
        btnEl.style.background = '#15803d';
        setTimeout(() => {
            btnEl.innerHTML = origText;
            btnEl.style.background = '';
        }, 1200);
    }
}

function updateShopCartQty(id, delta) {
    const idx = shopCart.findIndex(i => i.id === id);
    if (idx !== -1) {
        shopCart[idx].qty += delta;
        if (shopCart[idx].qty <= 0) {
            shopCart.splice(idx, 1);
        }
    }
    saveShopCart();
}

function removeShopCartItem(id) {
    shopCart = shopCart.filter(i => i.id !== id);
    saveShopCart();
}

function toggleShopCartDrawer() {
    const drawer = document.getElementById('shop-cart-drawer');
    const backdrop = document.getElementById('shop-cart-backdrop');
    if (!drawer || !backdrop) return;

    const isOpen = drawer.style.right === '0px';
    if (isOpen) {
        drawer.style.right = '-420px';
        backdrop.style.display = 'none';
    } else {
        drawer.style.right = '0px';
        backdrop.style.display = 'block';
        fetchCartCalculation();
    }
}

function renderShopCartUI() {
    const countBadge = document.getElementById('shop-cart-count-badge');
    const totalCount = shopCart.reduce((sum, i) => sum + i.qty, 0);
    if (countBadge) {
        countBadge.innerText = totalCount;
        countBadge.style.display = totalCount > 0 ? 'flex' : 'none';
    }

    const emptyMsg = document.getElementById('shop-cart-empty-msg');
    const checkoutBtn = document.getElementById('shop-checkout-btn');
    if (emptyMsg && checkoutBtn) {
        if (shopCart.length === 0) {
            emptyMsg.style.display = 'block';
            checkoutBtn.disabled = true;
            checkoutBtn.style.opacity = '0.5';
        } else {
            emptyMsg.style.display = 'none';
            checkoutBtn.disabled = false;
            checkoutBtn.style.opacity = '1';
        }
    }
}

async function fetchCartCalculation() {
    renderShopCartUI();
    if (shopCart.length === 0) {
        const listEl = document.getElementById('shop-cart-items-list');
        if (listEl) listEl.innerHTML = '';
        document.getElementById('shop-cart-subtotal').innerText = '0,00 €';
        document.getElementById('shop-cart-shipping').innerText = '0,00 €';
        document.getElementById('shop-cart-total').innerText = '0,00 €';
        return;
    }

    try {
        const res = await fetch('api/shop.php?action=calculate_cart', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: shopCart })
        });
        const data = await res.json();
        if (data.success && data.cart) {
            const c = data.cart;
            document.getElementById('shop-cart-subtotal').innerText = c.subtotal.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('shop-cart-shipping').innerText = c.shipping > 0 ? (c.shipping.toFixed(2).replace('.', ',') + ' €') : 'Kostenlos';
            document.getElementById('shop-cart-total').innerText = c.total.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('checkout-modal-total').innerText = c.total.toFixed(2).replace('.', ',') + ' €';

            // Items rendern
            const listEl = document.getElementById('shop-cart-items-list');
            if (listEl) {
                listEl.innerHTML = c.items.map(item => `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                        <div style="flex-grow:1;">
                            <div style="font-weight:700; font-size:0.92rem; color:var(--text-primary);">${item.title}</div>
                            <div style="font-size:0.8rem; color:var(--text-secondary);">${item.price.toFixed(2).replace('.', ',')} € / Stk.</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <button type="button" onclick="updateShopCartQty('${item.id}', -1)" style="width:24px; height:24px; border-radius:4px; border:1px solid var(--border-color); background:none; cursor:pointer;">-</button>
                            <span style="font-size:0.88rem; font-weight:700; min-width:18px; text-align:center;">${item.qty}</span>
                            <button type="button" onclick="updateShopCartQty('${item.id}', 1)" style="width:24px; height:24px; border-radius:4px; border:1px solid var(--border-color); background:none; cursor:pointer;">+</button>
                            <button type="button" onclick="removeShopCartItem('${item.id}')" style="background:none; border:none; color:#ef4444; cursor:pointer; margin-left:4px;">
                                <span class="material-icons" style="font-size:18px;">delete</span>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch(err) {
        console.error('Fehler bei der Warenkorb-Berechnung:', err);
    }
}

function openShopCheckout() {
    toggleShopCartDrawer();
    document.getElementById('shop-checkout-modal').style.display = 'flex';
    document.getElementById('checkout-step-1').style.display = 'block';
    document.getElementById('checkout-success-view').style.display = 'none';
}

function closeShopCheckout() {
    document.getElementById('shop-checkout-modal').style.display = 'none';
}

function selectPaymentMethod(method) {
    activePaymentMethod = method;
    ['credit_card', 'paypal', 'wero', 'bank_transfer'].forEach(m => {
        const fieldEl = document.getElementById('pay-fields-' + m);
        const labelEl = document.getElementById('label-pay-' + (m === 'credit_card' ? 'cc' : (m === 'paypal' ? 'pp' : (m === 'wero' ? 'wero' : 'bt'))));
        if (fieldEl) fieldEl.style.display = (m === method) ? 'block' : 'none';
        if (labelEl) {
            if (m === method) {
                labelEl.style.borderColor = 'var(--primary)';
                labelEl.style.background = 'rgba(46,98,72,0.06)';
            } else {
                labelEl.style.borderColor = 'var(--border-color)';
                labelEl.style.background = 'var(--card-bg, #fff)';
            }
        }
    });
}

async function submitShopOrder() {
    const btn = document.getElementById('submit-order-btn');
    const name = document.getElementById('cust_name').value.trim();
    const email = document.getElementById('cust_email').value.trim();
    const street = document.getElementById('cust_street').value.trim();
    const zip = document.getElementById('cust_zip').value.trim();
    const city = document.getElementById('cust_city').value.trim();

    if (!name || !email || !street || !zip || !city) {
        alert('Bitte fülle alle Pflichtfelder der Adresse aus.');
        return;
    }

    let paymentDetails = {};
    if (activePaymentMethod === 'credit_card') {
        const holder = document.getElementById('cc_holder').value.trim();
        const number = document.getElementById('cc_number').value.replace(/\s+/g, '');
        const month = document.getElementById('cc_month').value.trim();
        const year = document.getElementById('cc_year').value.trim();
        const cvc = document.getElementById('cc_cvc').value.trim();

        if (!holder || !number || !month || !year || !cvc) {
            alert('Bitte fülle alle Kreditkartendaten aus.');
            return;
        }

        paymentDetails = {
            card_holder: holder,
            card_number: number,
            card_exp_month: month,
            card_exp_year: year,
            card_cvc: cvc
        };
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="material-icons rotating">autorenew</span> <span>Wird verarbeitet...</span>';

    try {
        const res = await fetch('api/shop.php?action=checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                email: email,
                street: street,
                zip: zip,
                city: city,
                payment_method: activePaymentMethod,
                payment_details: paymentDetails,
                items: shopCart
            })
        });

        const data = await res.json();
        if (data.success && data.order) {
            // Warenkorb leeren
            shopCart = [];
            saveShopCart();

            // Erfolgsansicht anzeigen
            document.getElementById('checkout-step-1').style.display = 'none';
            document.getElementById('checkout-success-view').style.display = 'block';

            if (data.message) {
                const msgEl = document.getElementById('order-success-msg');
                if (msgEl) msgEl.innerText = data.message;
            }

            document.getElementById('success-order-id').innerText = data.order.order_id;
            document.getElementById('success-payment-method').innerText = (activePaymentMethod === 'bank_transfer' ? 'Vorkasse / Überweisung' : activePaymentMethod.toUpperCase());
            document.getElementById('success-order-total').innerText = data.order.total.toFixed(2).replace('.', ',') + ' €';

            // Falls WERO: QR-Code zeichnen
            if (activePaymentMethod === 'wero') {
                const weroContainer = document.getElementById('wero-qr-container');
                if (weroContainer) {
                    weroContainer.style.display = 'block';
                    document.getElementById('wero-ref-code').innerText = 'Ref: ' + (data.order.payment_ref || '');
                    drawWeroQrCode(data.order.payment_ref || 'WERO');
                }
            } else {
                const weroContainer = document.getElementById('wero-qr-container');
                if (weroContainer) weroContainer.style.display = 'none';
            }

            // Falls Vorkasse: Überweisungshinweise anzeigen
            const btContainer = document.getElementById('bt-transfer-container');
            if (btContainer) {
                if (activePaymentMethod === 'bank_transfer') {
                    btContainer.style.display = 'block';
                    const btRef = document.getElementById('bt-success-ref');
                    if (btRef) btRef.innerText = data.order.order_id;
                } else {
                    btContainer.style.display = 'none';
                }
            }
        } else {
            alert(data.message || 'Die Bezahlung konnte nicht autorisiert werden.');
        }
    } catch(err) {
        console.error('Bestellfehler:', err);
        alert('Verbindungsfehler beim Verarbeiten der Bestellung.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons">check_circle</span> <span>Zahlungspflichtig bestellen</span>';
    }
}

// Zeichnet einen stylischen WERO Instant-Payment QR-Code auf Canvas
function drawWeroQrCode(ref) {
    const canvas = document.getElementById('wero-qr-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const size = canvas.width;
    ctx.clearRect(0, 0, size, size);

    // Weißer Hintergrund
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    // Matrix Simulation für QR-Code
    const matrix = 21;
    const cellSize = size / matrix;
    ctx.fillStyle = '#1e3a8a';

    // Pseudozufällige Muster basierend auf Ref-Hash
    let hash = 0;
    for (let i = 0; i < ref.length; i++) {
        hash = ((hash << 5) - hash) + ref.charCodeAt(i);
        hash |= 0;
    }

    for (let r = 0; r < matrix; r++) {
        for (let c = 0; c < matrix; c++) {
            // Ecken-Markierungen (Finder Patterns)
            const isTopLeft = (r < 7 && c < 7);
            const isTopRight = (r < 7 && c >= matrix - 7);
            const isBottomLeft = (r >= matrix - 7 && c < 7);

            if (isTopLeft || isTopRight || isBottomLeft) {
                const border = (r === 0 || r === 6 || c === 0 || c === 6 || 
                                (isTopRight && (r === 0 || r === 6 || c === matrix - 7 || c === matrix - 1)) ||
                                (isBottomLeft && (r === matrix - 7 || r === matrix - 1 || c === 0 || c === 6)));
                const center = (r >= 2 && r <= 4 && c >= 2 && c <= 4) ||
                               (isTopRight && r >= 2 && r <= 4 && c >= matrix - 5 && c <= matrix - 3) ||
                               (isBottomLeft && r >= matrix - 5 && r <= matrix - 3 && c >= 2 && c <= 4);
                if (border || center) {
                    ctx.fillRect(c * cellSize, r * cellSize, cellSize, cellSize);
                }
            } else {
                // Daten-Pixel
                const val = (Math.sin(r * 13 + c * 7 + hash) * 10000) % 1;
                if (Math.abs(val) > 0.45) {
                    ctx.fillRect(c * cellSize, r * cellSize, cellSize, cellSize);
                }
            }
        }
    }
}

function filterShopCategory(category, btn) {
    const cards = document.querySelectorAll('.shop-product-card');
    cards.forEach(card => {
        if (category === 'Alle' || card.getAttribute('data-category') === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    const buttons = document.querySelectorAll('.shop-cat-btn');
    buttons.forEach(b => {
        b.style.background = 'var(--card-bg, #fff)';
        b.style.color = 'var(--text-primary)';
    });
    if (btn) {
        btn.style.background = 'var(--primary)';
        btn.style.color = '#fff';
    }
}

// Initialer Aufruf
document.addEventListener('DOMContentLoaded', () => {
    renderShopCartUI();
});
</script>
