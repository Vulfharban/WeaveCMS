<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – Admin Backend (admin/settings.php)
 * ============================================================================
 * Phase 40.2: 6 Sub-Tabs (Dashboard & KPIs, Anzeigen & Freigaben, Kategorien,
 * Anfragen & Meldungen, Vor-Ort-Stände, Einstellungen & Event-Modus).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

$t = static fn(string $key, string $fallback): string => function_exists('__a') ? __a($key, $fallback) : $fallback;

$subtab = (string)($_GET['fm_subtab'] ?? 'dashboard');
$editId = trim((string)($_GET['edit_item'] ?? ''));
if ($editId !== '' && $subtab !== 'editor') {
    $subtab = 'editor';
}
$items = FleamarketManager::getItems();
$categories = FleamarketManager::getCategories();
$cfg = FleamarketManager::getConfig();
$labels = FleamarketManager::getLabels();
$kpis = FleamarketManager::getDashboardKpis();
$inquiries = FleamarketManager::getInquiries();
$reports = FleamarketManager::getReports();
$blocked = FleamarketManager::getBlockedSellers();
$stands = FleamarketManager::getStands();
$auditLog = FleamarketManager::getAuditLog();
$editItem = $editId !== '' ? FleamarketManager::getItem($editId) : null;

$notice = $_SESSION['weave_fm_admin_msg'] ?? null;
unset($_SESSION['weave_fm_admin_msg']);

$baseUrl = class_exists('SecurityManager') ? rtrim(SecurityManager::getBaseUrl(), '/') : '';
$cronUrl = ($baseUrl !== '' ? $baseUrl . '/' : '') . 'plugins/weave-fleamarket/cron.php?token=' . urlencode((string)($cfg['cron_token'] ?? ''));
$nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
?>

<div class="plugin-settings-wrapper" style="max-width: 1140px; margin: 0 auto; padding: 15px;">
    <div style="background: linear-gradient(135deg, #b45309 0%, #92400e 100%); color: #fff; border-radius: 12px; padding: 18px 24px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800; color: #fff;">🏷️ <?php echo htmlspecialchars($t('admin.fleamarket.header_title', 'Weave Flohmarkt – Kleinanzeigen, Moderation & Vor-Ort-Events'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <p style="margin: 4px 0 0 0; opacity: 0.92; font-size: 0.87rem;"><?php echo htmlspecialchars($t('admin.fleamarket.header_desc', 'Verwalte Anzeigen (FM-YYYY-NNNN), Freigaben, Meldungen, Vor-Ort-Stände & DSGVO-Bereinigung.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=editor#tab-plugins" class="btn" style="background: #fff; color: #92400e; font-weight: 700; text-decoration: none; padding: 8px 14px; border-radius: 8px;">+ <?php echo htmlspecialchars($t('admin.fleamarket.new_item', 'Neue Anzeige'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <?php if (is_array($notice) && !empty($notice['text'])): ?>
        <div style="margin-bottom: 14px; padding: 11px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; background: <?php echo !empty($notice['ok']) ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo !empty($notice['ok']) ? '#16a34a' : '#dc2626'; ?>; color: <?php echo !empty($notice['ok']) ? '#166534' : '#991b1b'; ?>;">
            <?php echo htmlspecialchars((string)$notice['text'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color, #e2e8f0); margin-bottom: 18px; flex-wrap: wrap;">
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=dashboard#tab-plugins" class="btn <?php echo $subtab === 'dashboard' ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">📊 <?php echo htmlspecialchars($t('admin.fleamarket.tab_dashboard', 'Dashboard & KPIs'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=items#tab-plugins" class="btn <?php echo in_array($subtab, ['items', 'editor'], true) ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">📋 <?php echo htmlspecialchars($t('admin.fleamarket.tab_items', 'Anzeigen & Freigaben'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo count($items); ?><?php echo $kpis['pending_items'] > 0 ? ' · ' . $kpis['pending_items'] . ' offen' : ''; ?>)</a>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=categories#tab-plugins" class="btn <?php echo $subtab === 'categories' ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">🗂️ <?php echo htmlspecialchars($t('admin.fleamarket.tab_categories', 'Kategorien'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo count($categories); ?>)</a>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=inquiries#tab-plugins" class="btn <?php echo $subtab === 'inquiries' ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">✉️ <?php echo htmlspecialchars($t('admin.fleamarket.tab_inquiries', 'Anfragen & Meldungen'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo count($inquiries); ?> / <?php echo $kpis['open_reports']; ?>)</a>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=stands#tab-plugins" class="btn <?php echo $subtab === 'stands' ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">🎪 <?php echo htmlspecialchars($t('admin.fleamarket.tab_stands', 'Vor-Ort-Stände'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo count($stands); ?>)</a>
        <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=settings#tab-plugins" class="btn <?php echo $subtab === 'settings' ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 8px 13px; text-decoration: none;">⚙️ <?php echo htmlspecialchars($t('admin.fleamarket.tab_settings', 'Einstellungen & Menü'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <?php if ($subtab === 'dashboard'): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Aktive Anzeigen</div><div style="font-size: 1.65rem; font-weight: 800; color: #059669;"><?php echo (int)$kpis['active_items']; ?></div></div>
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Warten auf Freigabe</div><div style="font-size: 1.65rem; font-weight: 800; color: #d97706;"><?php echo (int)$kpis['pending_items']; ?></div></div>
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Verkauft / Vergeben</div><div style="font-size: 1.65rem; font-weight: 800; color: #2563eb;"><?php echo (int)$kpis['sold_items']; ?></div></div>
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Aufrufe gesamt</div><div style="font-size: 1.65rem; font-weight: 800;"><?php echo (int)$kpis['total_views']; ?></div></div>
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Offene Meldungen</div><div style="font-size: 1.65rem; font-weight: 800; color: #dc2626;"><?php echo (int)$kpis['open_reports']; ?></div></div>
            <div class="card" style="padding: 1rem;"><div style="font-size: 12px; color: #64748b;">Bestätigte Stände</div><div style="font-size: 1.65rem; font-weight: 800; color: #b45309;"><?php echo (int)$kpis['confirmed_stands']; ?></div></div>
        </div>

        <div class="card" style="padding: 1.25rem; margin-bottom: 16px;">
            <h4 style="margin-top: 0;">🔄 Ablauf-Automatik, DSGVO-Wartung &amp; Export / Import</h4>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 12px;">
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="fm_run_maintenance">
                    <button type="submit" class="btn btn-primary">🧹 Ablauf &amp; Bereinigung jetzt ausführen</button>
                </form>
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="fm_export_json">
                    <button type="submit" class="btn btn-outline">⬇️ JSON-Export</button>
                </form>
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="fm_export_csv">
                    <button type="submit" class="btn btn-outline">⬇️ CSV-Export</button>
                </form>
                <form method="POST" action="" enctype="multipart/form-data" style="display:inline-flex; gap: 6px; align-items: center;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="fm_import_json">
                    <input type="file" name="import_file" accept="application/json,.json" required class="form-control" style="max-width: 220px;">
                    <button type="submit" class="btn btn-outline">⬆️ JSON-Import</button>
                </form>
            </div>
            <div style="font-size: 12.5px; color: #475569;">
                Cron-Endpunkt: <code><?php echo htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8'); ?></code>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <h4 style="margin-top: 0;">📜 Moderations- &amp; Audit-Log</h4>
            <?php if (empty($auditLog)): ?>
                <p style="color: var(--text-secondary);">Noch keine Audit-Einträge protokolliert.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12.5px;">
                    <?php foreach (array_slice($auditLog, 0, 20) as $log): ?>
                        <div style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <strong><?php echo htmlspecialchars((string)$log['action'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            · <?php echo htmlspecialchars((string)$log['details'], ENT_QUOTES, 'UTF-8'); ?>
                            <span style="color: #64748b;">(<?php echo htmlspecialchars((string)$log['actor'] . ' · ' . $log['created_at'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($subtab === 'items'): ?>
        <div class="card" style="padding: 1.25rem;">
            <?php if (empty($items)): ?>
                <p style="color: var(--text-secondary);">Noch keine Flohmarkt-Anzeigen vorhanden.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($items as $it): ?>
                        <?php $stInfo = $labels['statuses'][$it['status'] ?? 'active'] ?? $labels['statuses']['active']; ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border: 1px solid <?php echo ($it['status'] ?? '') === 'pending' ? '#f59e0b' : '#e2e8f0'; ?>; border-radius: 10px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <strong><?php echo htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; color: <?php echo $stInfo['color']; ?>; background: <?php echo $stInfo['bg']; ?>; font-weight: 700; margin-left: 6px;"><?php echo htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($it['stand_number'])): ?>
                                    <span style="font-size: 11px; padding: 2px 7px; border-radius: 999px; background: rgba(245,158,11,0.16); color: #b45309; font-weight: 700; margin-left: 4px;">Stand <?php echo htmlspecialchars((string)$it['stand_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <div style="font-size: 12px; color: #64748b;">
                                    <?php echo htmlspecialchars((string)$it['item_number'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars(FleamarketManager::formatPrice($it), ENT_QUOTES, 'UTF-8'); ?> · Slug: <code><?php echo htmlspecialchars((string)$it['slug'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?php if (($it['status'] ?? '') === 'pending'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="fm_set_status">
                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">✓ Freigeben</button>
                                    </form>
                                <?php endif; ?>
                                <a href="?tab=tab-plugins&plugin_settings=weave-fleamarket&fm_subtab=editor&edit_item=<?php echo urlencode((string)$it['id']); ?>#tab-plugins" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px; text-decoration: none;">✏️</a>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="fm_set_status">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="<?php echo ($it['status'] ?? 'active') === 'reserved' ? 'active' : 'reserved'; ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px;"><?php echo ($it['status'] ?? 'active') === 'reserved' ? 'Freigeben' : 'Reservieren'; ?></button>
                                </form>
                                <form method="POST" action="" style="display:inline;" data-confirm="Anzeige löschen?">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="fm_delete_item">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($subtab === 'editor'): ?>
        <?php
            $formItem = $editItem ?? [
                'id' => '', 'title' => '', 'description' => '', 'category_id' => $categories[0]['id'] ?? '',
                'ad_type' => 'offer', 'price_model' => 'fixed', 'price' => '15.00', 'condition' => 'good',
                'delivery_mode' => 'pickup', 'shipping_cost' => '0', 'location_zip' => '', 'location_city' => '',
                'location_district' => '', 'quantity' => 1, 'seller_name' => 'Redaktion', 'seller_email' => '',
                'stand_number' => '', 'status' => 'active', 'images' => [],
            ];
            $imgLines = implode("\n", array_map(static fn(array $im): string => (string)($im['url'] ?? ''), (array)($formItem['images'] ?? [])));
        ?>
        <div class="card" style="padding: 1.5rem;">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="fm_save_item">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$formItem['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>Titel *</label><input type="text" name="title" required class="form-control" value="<?php echo htmlspecialchars((string)$formItem['title'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Kategorie</label>
                        <select name="category_id" class="form-control">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['category_id'] ?? '') === $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Anzeigentyp</label>
                        <select name="ad_type" class="form-control">
                            <?php foreach ($labels['ad_types'] as $tk => $tl): ?>
                                <option value="<?php echo htmlspecialchars($tk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['ad_type'] ?? '') === $tk ? 'selected' : ''; ?>><?php echo htmlspecialchars($tl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ($labels['statuses'] as $sk => $si): ?>
                                <option value="<?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['status'] ?? '') === $sk ? 'selected' : ''; ?>><?php echo htmlspecialchars($si['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>Preismodell</label>
                        <select name="price_model" class="form-control">
                            <?php foreach ($labels['price_models'] as $pk => $pl): ?>
                                <option value="<?php echo htmlspecialchars($pk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['price_model'] ?? '') === $pk ? 'selected' : ''; ?>><?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Preis (€)</label><input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars((string)$formItem['price'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Zustand</label>
                        <select name="condition" class="form-control">
                            <?php foreach ($labels['conditions'] as $ck => $cl): ?>
                                <option value="<?php echo htmlspecialchars($ck, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['condition'] ?? '') === $ck ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Übergabe</label>
                        <select name="delivery_mode" class="form-control">
                            <?php foreach ($labels['delivery_modes'] as $dk => $dl): ?>
                                <option value="<?php echo htmlspecialchars($dk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($formItem['delivery_mode'] ?? '') === $dk ? 'selected' : ''; ?>><?php echo htmlspecialchars($dl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Versandkosten (€)</label><input type="number" step="0.01" name="shipping_cost" class="form-control" value="<?php echo htmlspecialchars((string)($formItem['shipping_cost'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>PLZ</label><input type="text" name="location_zip" class="form-control" value="<?php echo htmlspecialchars((string)($formItem['location_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Ort</label><input type="text" name="location_city" class="form-control" value="<?php echo htmlspecialchars((string)($formItem['location_city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Stand-/Tischnummer</label><input type="text" name="stand_number" class="form-control" placeholder="z. B. #12" value="<?php echo htmlspecialchars((string)($formItem['stand_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Anbieter-Name</label><input type="text" name="seller_name" class="form-control" value="<?php echo htmlspecialchars((string)($formItem['seller_name'] ?? 'Redaktion'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Anbieter-E-Mail (intern)</label><input type="email" name="seller_email" class="form-control" value="<?php echo htmlspecialchars((string)($formItem['seller_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>

                <div style="margin-bottom: 12px;"><label>Bild-URLs (eine pro Zeile)</label><textarea name="images" class="form-control" rows="2"><?php echo htmlspecialchars($imgLines, ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div style="margin-bottom: 14px;"><label>Beschreibung (Markdown)</label><textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars((string)($formItem['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>

                <button type="submit" class="btn btn-primary" style="padding: 9px 22px;">💾 Anzeige speichern</button>
            </form>
        </div>

    <?php elseif ($subtab === 'categories'): ?>
        <div class="card" style="padding: 1.5rem;">
            <?php foreach ($categories as $c): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid #e2e8f0;">
                    <div><strong><?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?></strong> (<code><?php echo htmlspecialchars((string)$c['slug'], ENT_QUOTES, 'UTF-8'); ?></code>)</div>
                    <form method="POST" action="" style="display:inline;" data-confirm="Kategorie entfernen?">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="fm_delete_category">
                        <input type="hidden" name="cat_id" value="<?php echo htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-outline" style="padding: 3px 8px; color: #dc2626;">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <form method="POST" action="" style="margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="fm_save_category">
                <input type="text" name="name" required class="form-control" placeholder="Neue Kategorie *" style="max-width: 240px;">
                <input type="text" name="icon" class="form-control" value="local_offer" placeholder="Icon" style="max-width: 140px;">
                <button type="submit" class="btn btn-primary">+ Kategorie hinzufügen</button>
            </form>
        </div>

    <?php elseif ($subtab === 'inquiries'): ?>
        <div class="card" style="padding: 1.5rem; margin-bottom: 16px;">
            <h4 style="margin-top: 0;">🚩 Gemeldete Anzeigen (Moderations-Queue)</h4>
            <?php if (empty($reports)): ?>
                <p style="color: var(--text-secondary);">Aktuell liegen keine Meldungen vor.</p>
            <?php else: ?>
                <?php foreach ($reports as $rep): ?>
                    <div style="padding: 10px 12px; border: 1px solid <?php echo ($rep['status'] ?? 'open') === 'open' ? '#fca5a5' : '#e2e8f0'; ?>; border-radius: 8px; margin-bottom: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong><?php echo htmlspecialchars((string)$rep['item_title'], ENT_QUOTES, 'UTF-8'); ?></strong> (<code><?php echo htmlspecialchars((string)$rep['item_number'], ENT_QUOTES, 'UTF-8'); ?></code>)
                                · Grund: <strong><?php echo htmlspecialchars((string)$rep['reason'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                · Status: <code><?php echo htmlspecialchars((string)($rep['status'] ?? 'open'), ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php if (!empty($rep['comment'])): ?><div style="font-size: 12.5px; color: #475569; margin-top: 4px;"><?php echo htmlspecialchars((string)$rep['comment'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                            </div>
                            <?php if (($rep['status'] ?? 'open') === 'open'): ?>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php foreach (['dismiss' => 'Abweisen', 'hide_item' => 'Verbergen', 'delete_item' => 'Löschen', 'block_seller' => 'Anbieter sperren'] as $rk => $rl): ?>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="fm_resolve_report">
                                            <input type="hidden" name="report_id" value="<?php echo htmlspecialchars((string)$rep['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="resolution" value="<?php echo htmlspecialchars($rk, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn <?php echo $rk === 'dismiss' ? 'btn-outline' : 'btn-danger'; ?>" style="padding: 4px 9px; font-size: 12px;"><?php echo htmlspecialchars($rl, ENT_QUOTES, 'UTF-8'); ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($blocked)): ?>
                <h5 style="margin: 14px 0 6px 0;">⛔ Gesperrte Anbieter</h5>
                <?php foreach ($blocked as $bl): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border: 1px solid #fecaca; border-radius: 6px; margin-bottom: 6px; font-size: 12.5px;">
                        <span><?php echo htmlspecialchars((string)($bl['seller_name'] ?: $bl['seller_id']) . ' (' . ($bl['seller_email'] ?: $bl['seller_id']) . ')', ENT_QUOTES, 'UTF-8'); ?></span>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="fm_unblock_seller">
                            <input type="hidden" name="identifier" value="<?php echo htmlspecialchars((string)($bl['seller_id'] ?: $bl['seller_email']), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 3px 8px; font-size: 11.5px;">Entsperren</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top: 0;">✉️ Eingegangene Interessenten-Anfragen</h4>
            <?php if (empty($inquiries)): ?>
                <p style="color: var(--text-secondary);">Noch keine Anfragen eingegangen.</p>
            <?php else: ?>
                <?php foreach ($inquiries as $inq): ?>
                    <div style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px;">
                        <strong><?php echo htmlspecialchars((string)$inq['item_title'], ENT_QUOTES, 'UTF-8'); ?></strong> (<code><?php echo htmlspecialchars((string)$inq['item_number'], ENT_QUOTES, 'UTF-8'); ?></code>)
                        · Von: <?php echo htmlspecialchars((string)$inq['sender_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$inq['sender_email'], ENT_QUOTES, 'UTF-8'); ?>)
                        <?php if ($inq['offer_price'] !== null): ?> · <strong>Preisvorschlag: <?php echo number_format((float)$inq['offer_price'], 2, ',', '.'); ?> €</strong><?php endif; ?>
                        <div style="font-size: 13px; color: #475569; margin-top: 4px;"><?php echo nl2br(htmlspecialchars((string)$inq['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($subtab === 'stands'): ?>
        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top: 0;">🎪 Vor-Ort-Standplatzanmeldungen &amp; Warteliste</h4>
            <?php if (empty($stands)): ?>
                <p style="color: var(--text-secondary);">Noch keine Standplatz-Anmeldungen vorhanden.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($stands as $st): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong><?php echo htmlspecialchars((string)$st['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($st['stand_number'])): ?> · <span style="font-weight:700; color:#b45309;">Stand <?php echo htmlspecialchars((string)$st['stand_number'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                · <code><?php echo htmlspecialchars((string)($st['status'] ?? 'confirmed'), ENT_QUOTES, 'UTF-8'); ?></code>
                                · <?php echo (int)($st['meters'] ?? 3); ?> m
                                <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars((string)$st['email'] . (!empty($st['phone']) ? ' · ' . $st['phone'] : '') . (!empty($st['note']) ? ' · ' . $st['note'] : ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?php foreach (['confirmed' => 'Bestätigen', 'waitlist' => 'Warteliste', 'cancelled' => 'Stornieren'] as $sk => $sl): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="fm_stand_status">
                                        <input type="hidden" name="stand_id" value="<?php echo htmlspecialchars((string)$st['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 4px 9px; font-size: 12px;"><?php echo htmlspecialchars($sl, ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endforeach; ?>
                                <form method="POST" action="" style="display:inline;" data-confirm="Standanmeldung entfernen?">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="fm_delete_stand">
                                    <input type="hidden" name="stand_id" value="<?php echo htmlspecialchars((string)$st['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 9px; font-size: 12px;">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($subtab === 'settings'): ?>
        <div class="card" style="padding: 1.5rem;">
            <form method="POST" action="" style="display: grid; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="fm_save_config">

                <input type="hidden" name="show_in_main_nav" value="0">
                <label style="font-weight: 700;"><input type="checkbox" name="show_in_main_nav" value="1" <?php echo !empty($cfg['show_in_main_nav']) ? 'checked' : ''; ?>> Als eigenen Menüpunkt in der Hauptnavigation anzeigen (Opt-In gemäß Menü-Governance)</label>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div><label>Anbieter- &amp; Einreichungs-Modus</label>
                        <select name="submission_mode" class="form-control">
                            <option value="admin_only" <?php echo ($cfg['submission_mode'] ?? '') === 'admin_only' ? 'selected' : ''; ?>>1. Nur Betreiber / Redaktion</option>
                            <option value="members" <?php echo ($cfg['submission_mode'] ?? '') === 'members' ? 'selected' : ''; ?>>2. Mitglieder-Flohmarkt („Meine Anzeigen“)</option>
                            <option value="guest_moderated" <?php echo ($cfg['submission_mode'] ?? '') === 'guest_moderated' ? 'selected' : ''; ?>>3. Moderierte Gast-Einreichung (Double-Opt-In + Token)</option>
                        </select>
                    </div>
                    <div><label>Max. aktive Anzeigen je Anbieter</label><input type="number" min="1" max="200" name="max_items_per_seller" class="form-control" value="<?php echo (int)($cfg['max_items_per_seller'] ?? 10); ?>"></div>
                    <div><label>Max. Bilder je Anzeige</label><input type="number" min="1" max="12" name="max_images_per_item" class="form-control" value="<?php echo (int)($cfg['max_images_per_item'] ?? 6); ?>"></div>
                    <div><label>Standard-Laufzeit (Tage)</label>
                        <select name="default_duration_days" class="form-control">
                            <?php foreach ([14, 30, 60, 90] as $dd): ?>
                                <option value="<?php echo $dd; ?>" <?php echo (int)($cfg['default_duration_days'] ?? 30) === $dd ? 'selected' : ''; ?>><?php echo $dd; ?> Tage</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="require_approval" value="0">
                <label><input type="checkbox" name="require_approval" value="1" <?php echo !empty($cfg['require_approval']) ? 'checked' : ''; ?>> Moderations-Freigabe vor Veröffentlichung erforderlich</label>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div><label>Menü-Bezeichnung</label><input type="text" name="nav_label" class="form-control" value="<?php echo htmlspecialchars((string)$cfg['nav_label'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Menü-Icon</label>
                        <select name="nav_icon" class="form-control">
                            <?php foreach (['local_offer', 'storefront', 'redeem', 'shopping_bag', 'sell'] as $ic): ?>
                                <option value="<?php echo htmlspecialchars($ic, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($cfg['nav_icon'] ?? '') === $ic ? 'selected' : ''; ?>><?php echo htmlspecialchars($ic, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Platzierung</label>
                        <select name="placement" class="form-control">
                            <option value="page" <?php echo ($cfg['placement'] ?? '') === 'page' ? 'selected' : ''; ?>>Eigene Unterseite (?page=flohmarkt)</option>
                            <option value="home" <?php echo ($cfg['placement'] ?? '') === 'home' ? 'selected' : ''; ?>>Startseiten-Sektion (#flohmarkt)</option>
                            <option value="both" <?php echo ($cfg['placement'] ?? '') === 'both' ? 'selected' : ''; ?>>Beides (Startseite &amp; Unterseite)</option>
                        </select>
                    </div>
                    <div><label>Standard-Layout</label>
                        <select name="default_layout" class="form-control">
                            <option value="grid" <?php echo ($cfg['default_layout'] ?? '') === 'grid' ? 'selected' : ''; ?>>Kachel-Raster (Grid)</option>
                            <option value="list" <?php echo ($cfg['default_layout'] ?? '') === 'list' ? 'selected' : ''; ?>>Kompakte Liste</option>
                            <option value="compact" <?php echo ($cfg['default_layout'] ?? '') === 'compact' ? 'selected' : ''; ?>>Pinnwand-Karten</option>
                        </select>
                    </div>
                </div>
                <div><label>Überschrift</label><input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars((string)$cfg['section_title'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Untertitel</label><input type="text" name="section_subtitle" class="form-control" value="<?php echo htmlspecialchars((string)$cfg['section_subtitle'], ENT_QUOTES, 'UTF-8'); ?>"></div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 6px 0;">
                <h4 style="margin: 0;">🎪 Vor-Ort-Event- &amp; Standplatz-Modus</h4>
                <input type="hidden" name="onsite_enabled" value="0">
                <label style="font-weight: 700;"><input type="checkbox" name="onsite_enabled" value="1" <?php echo !empty($cfg['onsite_enabled']) ? 'checked' : ''; ?>> Vor-Ort-Flohmarkt-Event &amp; Standplatzanmeldung aktivieren</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px;">
                    <div><label>Event-Titel</label><input type="text" name="onsite_event_title" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_event_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Datum</label><input type="date" name="onsite_event_date" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_event_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Beginn</label><input type="time" name="onsite_start_time" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_start_time'] ?? '09:00'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Ende</label><input type="time" name="onsite_end_time" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_end_time'] ?? '16:00'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Aufbau ab</label><input type="time" name="onsite_setup_time" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_setup_time'] ?? '07:30'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Abbau bis</label><input type="time" name="onsite_teardown_time" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_teardown_time'] ?? '17:00'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Veranstaltungsort</label><input type="text" name="onsite_location" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Standgebühr / Spendenhinweis</label><input type="text" name="onsite_fee_info" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_fee_info'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div><label>Max. Standplätze</label><input type="number" min="1" max="500" name="onsite_max_stands" class="form-control" value="<?php echo (int)($cfg['onsite_max_stands'] ?? 30); ?>"></div>
                    <div><label>Lageplan-Bild-URL (optional)</label><input type="text" name="onsite_map_image" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['onsite_map_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <input type="hidden" name="onsite_waitlist_enabled" value="0">
                <label><input type="checkbox" name="onsite_waitlist_enabled" value="1" <?php echo !empty($cfg['onsite_waitlist_enabled']) ? 'checked' : ''; ?>> Warteliste bei vollem Kontingent aktivieren</label>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 6px 0;">
                <h4 style="margin: 0;">🧹 Ablauf-Automatik &amp; DSGVO-Aufbewahrungsfristen</h4>
                <input type="hidden" name="expiry_reminder_enabled" value="0">
                <label><input type="checkbox" name="expiry_reminder_enabled" value="1" <?php echo !empty($cfg['expiry_reminder_enabled']) ? 'checked' : ''; ?>> Erinnerungs-E-Mail mit 1-Klick-Verlängerung vor Ablauf senden</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div><label>Erinnerung (Tage vor Ablauf)</label><input type="number" min="1" max="30" name="expiry_reminder_days" class="form-control" value="<?php echo (int)($cfg['expiry_reminder_days'] ?? 3); ?>"></div>
                    <div><label>Gast-Anzeigen löschen nach Ablauf (Tage)</label><input type="number" min="1" max="365" name="retention_days_expired" class="form-control" value="<?php echo (int)($cfg['retention_days_expired'] ?? 30); ?>"></div>
                    <div><label>Anfragen DSGVO-löschen nach (Tage)</label><input type="number" min="1" max="730" name="retention_days_inquiries" class="form-control" value="<?php echo (int)($cfg['retention_days_inquiries'] ?? 90); ?>"></div>
                </div>

                <p style="font-size: 12.5px; color: #64748b; margin: 0;">Shortcodes: <code>[fleamarket]</code>, <code>[weave_fleamarket category="..." type="..." limit="6" layout="grid"]</code>, <code>[fleamarket_item id="..."]</code></p>
                <div><button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button></div>
            </form>
        </div>
    <?php endif; ?>
</div>
<script<?php echo $nonceAttr; ?>>
(function() {
    if (window.__weaveFmAdminBound) return;
    window.__weaveFmAdminBound = true;
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || !form.getAttribute) return;
        var msg = form.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    });
})();
</script>
