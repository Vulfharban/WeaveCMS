<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – Katalogansicht (views/fleamarket_catalog.php)
 * ============================================================================
 * Switchbare Ansichten (Grid, Liste, Kompakt), Live-Filter & Suche, Sortierung
 * und Seitennummerierung – Light/Dark-Mode-kompatibel & CSP-konform.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_fleamarket_render_catalog')) {

    function weave_fleamarket_render_catalog(array $opts = []): string {
        $cfg = FleamarketManager::getConfig();
        $labels = FleamarketManager::getLabels();
        $categories = FleamarketManager::getCategories();
        $catMap = [];
        foreach ($categories as $c) {
            $catMap[$c['id']] = $c;
        }

        $allowedLayouts = ['grid', 'list', 'compact'];
        $rawLayout = trim((string)($_GET['fm_layout'] ?? $opts['layout'] ?? $cfg['default_layout'] ?? 'grid'));
        $layout = in_array($rawLayout, $allowedLayouts, true) ? $rawLayout : 'grid';

        $filters = [
            'q'             => trim((string)($_GET['fm_q'] ?? $opts['q'] ?? '')),
            'category'      => trim((string)($_GET['fm_cat'] ?? $opts['category'] ?? '')),
            'type'          => trim((string)($_GET['fm_type'] ?? $opts['type'] ?? '')),
            'condition'     => trim((string)($_GET['fm_cond'] ?? $opts['condition'] ?? '')),
            'price_min'     => trim((string)($_GET['fm_min'] ?? '')),
            'price_max'     => trim((string)($_GET['fm_max'] ?? '')),
            'only_vb'       => !empty($_GET['fm_vb']),
            'only_free'     => !empty($_GET['fm_free']),
            'delivery'      => trim((string)($_GET['fm_deliv'] ?? '')),
            'location'      => trim((string)($_GET['fm_loc'] ?? '')),
            'hide_reserved' => !empty($_GET['fm_nores']),
            'seller_id'     => trim((string)($_GET['fm_seller'] ?? '')),
            'sort'          => trim((string)($_GET['fm_sort'] ?? 'newest')),
        ];

        // Kategorie-Slug auf ID auflösen falls Shortcode category="moebel-wohnen" nutzt
        if ($filters['category'] !== '' && !isset($catMap[$filters['category']])) {
            foreach ($categories as $c) {
                if (($c['slug'] ?? '') === $filters['category']) {
                    $filters['category'] = $c['id'];
                    break;
                }
            }
        }

        $page = max(1, (int)($_GET['fm_p'] ?? 1));
        $perPage = !empty($opts['limit']) ? max(1, (int)$opts['limit']) : (int)($cfg['items_per_page'] ?? 12);
        $res = FleamarketManager::filterItems($filters, $page, $perPage);
        $items = $res['items'];

        $buildUrl = static function (array $override = []) use ($layout, $filters, $page): string {
            $params = $_GET;
            if (!isset($params['page']) && empty($override['keep_home'])) {
                $params['page'] = 'flohmarkt';
            }
            unset($params['item']);
            $params['fm_layout'] = $override['layout'] ?? $layout;
            $params['fm_p'] = $override['p'] ?? $page;
            if (array_key_exists('seller_id', $override)) {
                if ($override['seller_id'] === '') {
                    unset($params['fm_seller']);
                } else {
                    $params['fm_seller'] = $override['seller_id'];
                }
            }
            return '?' . http_build_query($params) . '#flohmarkt';
        };

        $detailUrlFor = static function (string $slug): string {
            return '?page=flohmarkt&item=' . urlencode($slug);
        };
        $t = static fn(string $key, string $default, array $params = []): string => function_exists('__')
            ? __($key, $default, null, $params)
            : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);

        $curMember = FleamarketManager::getCurrentMember();
        $canSubmit = FleamarketManager::canSubmitFrontend($curMember);

        ob_start();
        ?>
        <div class="weave-fm-catalog" style="margin: 1rem 0 2.5rem 0;">
            <?php if (empty($opts['compact_embed']) && !empty($cfg['onsite_enabled'])): ?>
                <?php
                    $standsAll = FleamarketManager::getStands();
                    $confirmedStands = count(array_filter($standsAll, static fn(array $s): bool => ($s['status'] ?? '') === 'confirmed'));
                    $maxStands = max(1, (int)($cfg['onsite_max_stands'] ?? 30));
                    $freeStands = max(0, $maxStands - $confirmedStands);
                    $evDate = !empty($cfg['onsite_event_date']) ? date('d.m.Y', strtotime((string)$cfg['onsite_event_date']) ?: time()) : 'Termin folgt';
                ?>
                <div class="card" style="padding: 1.15rem 1.35rem; margin-bottom: 1rem; border-radius: var(--card-radius, 14px); border: 1px solid rgba(180,83,9,0.28); background: linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(180,83,9,0.05) 100%); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #b45309;"><?php echo htmlspecialchars($t('fleamarket.onsite_badge', '🎪 Vor-Ort-Flohmarkt Event'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <h3 style="margin: 2px 0 4px 0; font-size: 1.15rem; color: var(--text-primary, #0f172a);"><?php echo htmlspecialchars((string)($cfg['onsite_event_title'] ?? 'Flohmarkt'), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div style="font-size: 0.86rem; color: var(--text-secondary, #475569); display: flex; flex-wrap: wrap; gap: 10px;">
                            <span>📅 <?php echo htmlspecialchars($evDate, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)($cfg['onsite_start_time'] ?? '09:00'), ENT_QUOTES, 'UTF-8'); ?>–<?php echo htmlspecialchars((string)($cfg['onsite_end_time'] ?? '16:00'), ENT_QUOTES, 'UTF-8'); ?> Uhr)</span>
                            <?php if (!empty($cfg['onsite_location'])): ?><span>📍 <?php echo htmlspecialchars((string)$cfg['onsite_location'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                            <?php if (!empty($cfg['onsite_fee_info'])): ?><span>💶 <?php echo htmlspecialchars((string)$cfg['onsite_fee_info'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                            <span>🪑 Freie Plätze: <strong><?php echo $freeStands; ?> / <?php echo $maxStands; ?></strong></span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <?php if (!empty($cfg['onsite_map_image'])): ?>
                            <a href="<?php echo htmlspecialchars((string)$cfg['onsite_map_image'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.84rem; text-decoration: none;">🗺️ Lageplan</a>
                        <?php endif; ?>
                        <a href="?page=flohmarkt&view=stands" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.86rem; text-decoration: none;"><?php echo htmlspecialchars($t('fleamarket.register_stand', '🎪 Standplatz anmelden'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (empty($opts['compact_embed'])): ?>
            <!-- Aktions-Leiste: Merkliste ♥ & Anzeige aufgeben -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 0.75rem;">
                <button type="button" class="btn btn-outline wfm-watchlist-filter" style="padding: 6px 12px; font-size: 0.85rem;">
                    <?php echo htmlspecialchars($t('fleamarket.watchlist', '♥ Merkliste'), ENT_QUOTES, 'UTF-8'); ?> (<span class="wfm-watchlist-count">0</span>)
                </button>
                <?php if ($curMember && !empty($curMember['id'])): ?>
                    <a href="?page=flohmarkt&view=my_items" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem; text-decoration: none;"><?php echo htmlspecialchars($t('fleamarket.my_items', 'Meine Anzeigen'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
                <?php if ($canSubmit): ?>
                    <a href="?page=flohmarkt&view=submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.85rem; text-decoration: none;"><?php echo htmlspecialchars($t('fleamarket.submit_btn', '+ Anzeige aufgeben'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
            <!-- Filter- & Suchleiste -->
            <form method="GET" action="" class="card" style="padding: 1.15rem 1.25rem; margin-bottom: 1.25rem; border-radius: var(--card-radius, 14px); border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: var(--bg-card, #ffffff);">
                <?php if (!empty($_GET['page'])): ?>
                    <input type="hidden" name="page" value="<?php echo htmlspecialchars((string)$_GET['page'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <input type="hidden" name="fm_layout" value="<?php echo htmlspecialchars($layout, ENT_QUOTES, 'UTF-8'); ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 8px; margin-bottom: 10px;">
                    <input type="text" name="fm_q" value="<?php echo htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($t('fleamarket.search_placeholder', '🔍 Suche Titel, Text, Nr...'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control">

                    <select name="fm_cat" class="form-control">
                        <option value=""><?php echo htmlspecialchars($t('fleamarket.all_categories', 'Alle Kategorien'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['category'] === $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="fm_type" class="form-control">
                        <option value=""><?php echo htmlspecialchars($t('fleamarket.all_types', 'Alle Anzeigentypen'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($labels['ad_types'] as $tk => $tl): ?>
                            <option value="<?php echo htmlspecialchars($tk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['type'] === $tk ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tl, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="fm_cond" class="form-control">
                        <option value=""><?php echo htmlspecialchars($t('fleamarket.all_conditions', 'Alle Zustände'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($labels['conditions'] as $ck => $cl): ?>
                            <option value="<?php echo htmlspecialchars($ck, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['condition'] === $ck ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="fm_deliv" class="form-control">
                        <option value=""><?php echo htmlspecialchars($t('fleamarket.deliv_both', 'Abholung & Versand'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="pickup" <?php echo $filters['delivery'] === 'pickup' ? 'selected' : ''; ?>><?php echo htmlspecialchars($t('fleamarket.deliv_pickup', 'Abholung möglich'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="shipping" <?php echo $filters['delivery'] === 'shipping' ? 'selected' : ''; ?>><?php echo htmlspecialchars($t('fleamarket.deliv_shipping', 'Versand möglich'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>

                    <input type="text" name="fm_loc" value="<?php echo htmlspecialchars($filters['location'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="📍 PLZ / Ort" class="form-control">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 0.84rem;">
                        <input type="number" name="fm_min" step="0.5" min="0" value="<?php echo htmlspecialchars($filters['price_min'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="ab €" class="form-control" style="width: 82px; padding: 4px 8px;">
                        <input type="number" name="fm_max" step="0.5" min="0" value="<?php echo htmlspecialchars($filters['price_max'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="bis €" class="form-control" style="width: 82px; padding: 4px 8px;">
                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" name="fm_vb" value="1" <?php echo $filters['only_vb'] ? 'checked' : ''; ?>> Nur VB
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" name="fm_free" value="1" <?php echo $filters['only_free'] ? 'checked' : ''; ?>> Zu verschenken
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" name="fm_nores" value="1" <?php echo $filters['hide_reserved'] ? 'checked' : ''; ?>> Reservierte ausblenden
                        </label>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <select name="fm_sort" class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.85rem;">
                            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Neueste zuerst</option>
                            <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>Preis aufsteigend</option>
                            <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>Preis absteigend</option>
                            <option value="expiring_soon" <?php echo $filters['sort'] === 'expiring_soon' ? 'selected' : ''; ?>>Bald ablaufend</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.86rem;">Filtern</button>
                        <a href="<?php echo htmlspecialchars($buildUrl(['layout' => 'grid', 'p' => 1, 'seller_id' => '']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 6px 10px; font-size: 0.84rem; text-decoration: none;">Reset</a>
                    </div>
                </div>
            </form>

            <!-- Ergebnis-Header & Layout-Umschalter -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <div style="font-size: 0.9rem; color: var(--text-secondary, #64748b);">
                    <strong><?php echo (int)$res['total']; ?></strong> Anzeige(n) gefunden
                    <?php if ($filters['seller_id'] !== ''): ?>
                        · Gefiltert auf Anbieter
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php foreach (['grid' => 'Raster', 'list' => 'Liste', 'compact' => 'Pinnwand'] as $lk => $ll): ?>
                        <a href="<?php echo htmlspecialchars($buildUrl(['layout' => $lk, 'p' => 1]), ENT_QUOTES, 'UTF-8'); ?>"
                           class="btn <?php echo $layout === $lk ? 'btn-primary' : 'btn-outline'; ?>"
                           style="padding: 5px 11px; font-size: 0.82rem; text-decoration: none;">
                            <?php echo htmlspecialchars($ll, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Artikel-Liste -->
            <?php if (empty($items)): ?>
                <div class="card" style="padding: 2.5rem; text-align: center; color: var(--text-secondary, #64748b);">
                    Keine passenden Flohmarkt-Anzeigen gefunden.
                </div>
            <?php else: ?>
                <?php
                    $containerStyle = $layout === 'list'
                        ? 'display: flex; flex-direction: column; gap: 12px;'
                        : ($layout === 'compact'
                            ? 'display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;'
                            : 'display: grid; grid-template-columns: repeat(auto-fill, minmax(275px, 1fr)); gap: 1.25rem;');
                ?>
                <div class="weave-fm-items weave-fm-layout-<?php echo htmlspecialchars($layout, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $containerStyle; ?>">
                    <?php foreach ($items as $it): ?>
                        <?php
                            $stInfo = $labels['statuses'][$it['status'] ?? 'active'] ?? $labels['statuses']['active'];
                            $typeLabel = $labels['ad_types'][$it['ad_type'] ?? 'offer'] ?? 'Angebot';
                            $condLabel = $labels['conditions'][$it['condition'] ?? 'good'] ?? 'Gut erhalten';
                            $catName = $catMap[$it['category_id'] ?? '']['name'] ?? 'Flohmarkt';
                            $thumb = $it['images'][0]['thumb_url'] ?? ($it['images'][0]['url'] ?? '');
                            $fx = (int)($it['images'][0]['focal_x'] ?? 50);
                            $fy = (int)($it['images'][0]['focal_y'] ?? 50);
                            $itemUrl = $detailUrlFor((string)$it['slug']);
                            $locStr = trim(($it['location_zip'] ?? '') . ' ' . ($it['location_city'] ?? ''));
                        ?>
                        <article class="card weave-fm-card"
                                 data-fm-id="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                 style="border-radius: var(--card-radius, 14px); border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: var(--bg-card, #ffffff); overflow: hidden; <?php echo $layout === 'list' ? 'display: grid; grid-template-columns: 190px 1fr;' : 'display: flex; flex-direction: column;'; ?>">
                            <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"
                               style="display: block; position: relative; background: rgba(148,163,184,0.14); <?php echo $layout === 'list' ? 'min-height: 150px;' : ($layout === 'compact' ? 'height: 140px;' : 'height: 190px;'); ?> overflow: hidden;">
                                <?php if ($thumb !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="<?php echo htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                         loading="lazy"
                                         style="width: 100%; height: 100%; object-fit: cover; object-position: <?php echo $fx; ?>% <?php echo $fy; ?>%;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary, #94a3b8);">
                                        <span class="material-icons" style="font-size: 42px;">local_offer</span>
                                    </div>
                                <?php endif; ?>
                                <span style="position: absolute; top: 8px; left: 8px; font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; color: <?php echo $stInfo['color']; ?>; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.14);">
                                    <?php echo htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </a>

                            <div style="padding: 1rem; display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1;">
                                <div>
                                    <div style="font-size: 0.74rem; color: var(--text-secondary, #64748b); margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($catName . ' · ' . $typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <h3 style="margin: 0 0 6px 0; font-size: 1.05rem; line-height: 1.3;">
                                        <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" style="color: var(--text-primary, #0f172a); text-decoration: none;">
                                            <?php echo htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h3>
                                    <?php if ($layout !== 'compact' && !empty($it['description'])): ?>
                                        <p style="margin: 0 0 10px 0; font-size: 0.84rem; color: var(--text-secondary, #4b5563); line-height: 1.45;">
                                            <?php echo htmlspecialchars(mb_strimwidth(strip_tags((string)$it['description']), 0, 110, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; padding-top: 8px; border-top: 1px solid var(--border-color, rgba(0,0,0,0.06));">
                                    <strong style="font-size: 1.02rem; color: var(--primary, #2e6248);">
                                        <?php echo htmlspecialchars(FleamarketManager::formatPrice($it), ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                    <span style="font-size: 0.76rem; color: var(--text-secondary, #64748b); display: inline-flex; align-items: center; gap: 6px;">
                                        <?php echo $locStr !== '' ? ('📍 ' . htmlspecialchars($locStr, ENT_QUOTES, 'UTF-8')) : htmlspecialchars($condLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        <button type="button" class="btn btn-outline wfm-watch-btn" data-wfm-watch="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>" style="padding: 2px 7px; font-size: 0.75rem;" title="Merken">♡</button>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Seitennummerierung -->
                <?php if ($res['total_pages'] > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 6px; margin-top: 1.5rem; flex-wrap: wrap;">
                        <?php for ($p = 1; $p <= $res['total_pages']; $p++): ?>
                            <a href="<?php echo htmlspecialchars($buildUrl(['p' => $p]), ENT_QUOTES, 'UTF-8'); ?>"
                               class="btn <?php echo $p === $res['page'] ? 'btn-primary' : 'btn-outline'; ?>"
                               style="padding: 5px 11px; text-decoration: none;">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean() . weave_fleamarket_watchlist_script_once();
    }

    function weave_fleamarket_watchlist_script_once(): string {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;
        $nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
        return <<<HTML
<script{$nonceAttr}>
(function() {
    if (window.__weaveFmWatchlistBound) return;
    window.__weaveFmWatchlistBound = true;
    const KEY = 'weave_fm_watchlist';
    let filterActive = false;

    function getList() {
        try { return JSON.parse(localStorage.getItem(KEY) || '[]') || []; } catch (_) { return []; }
    }
    function saveList(arr) {
        try { localStorage.setItem(KEY, JSON.stringify(arr)); } catch (_) {}
    }
    function syncUi() {
        const list = getList();
        document.querySelectorAll('.wfm-watchlist-count').forEach(function(el) { el.textContent = String(list.length); });
        document.querySelectorAll('.wfm-watch-btn').forEach(function(btn) {
            const id = btn.getAttribute('data-wfm-watch');
            const active = list.indexOf(id) !== -1;
            const lblOn = btn.getAttribute('data-label-on') || '♥ Gemerkt';
            const lblOff = btn.getAttribute('data-label-off') || '♡';
            btn.textContent = active ? lblOn : lblOff;
        });
        document.querySelectorAll('.weave-fm-card').forEach(function(card) {
            if (!filterActive) { card.style.display = ''; return; }
            const cid = card.getAttribute('data-fm-id');
            card.style.display = list.indexOf(cid) !== -1 ? '' : 'none';
        });
    }

    document.addEventListener('click', function(e) {
        const watchBtn = e.target && e.target.closest ? e.target.closest('.wfm-watch-btn') : null;
        if (watchBtn) {
            const id = watchBtn.getAttribute('data-wfm-watch');
            if (!id) return;
            const list = getList();
            const idx = list.indexOf(id);
            if (idx === -1) list.push(id); else list.splice(idx, 1);
            saveList(list);
            syncUi();
            return;
        }
        const filterBtn = e.target && e.target.closest ? e.target.closest('.wfm-watchlist-filter') : null;
        if (filterBtn) {
            filterActive = !filterActive;
            filterBtn.classList.toggle('btn-primary', filterActive);
            syncUi();
        }
    });
    if (document.readyState !== 'loading') {
        syncUi();
    } else {
        document.addEventListener('DOMContentLoaded', syncUi);
    }
})();
</script>
HTML;
    }
}
