<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – Detailansicht (views/fleamarket_detail.php)
 * ============================================================================
 * Mehrfachbild-Galerie mit Lightbox, Steckbrief, Anbieter-Box, Teilen-Leiste
 * mit vollständiger https://-URL, Schema.org Product/Offer JSON-LD & Breadcrumb.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_fleamarket_render_detail')) {

    function weave_fleamarket_render_detail(array $item, array $opts = []): string {
        $labels = FleamarketManager::getLabels();
        $cats = FleamarketManager::getCategories();
        $catName = 'Flohmarkt';
        foreach ($cats as $c) {
            if (($c['id'] ?? '') === ($item['category_id'] ?? '')) {
                $catName = (string)$c['name'];
                break;
            }
        }

        $stInfo = $labels['statuses'][$item['status'] ?? 'active'] ?? $labels['statuses']['active'];
        $typeLabel = $labels['ad_types'][$item['ad_type'] ?? 'offer'] ?? 'Angebot';
        $condLabel = $labels['conditions'][$item['condition'] ?? 'good'] ?? 'Gut erhalten';
        $delivLabel = $labels['delivery_modes'][$item['delivery_mode'] ?? 'pickup'] ?? 'Nur Abholung';

        $baseUrl = class_exists('SecurityManager')
            ? rtrim(SecurityManager::getBaseUrl(), '/')
            : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fullItemUrl = $baseUrl . '/?page=flohmarkt&item=' . urlencode((string)$item['slug']);
        $jsonLd = FleamarketManager::buildItemJsonLd($item, $fullItemUrl);

        $images = (array)($item['images'] ?? []);
        $mainImg = $images[0]['url'] ?? '';
        $fx = (int)($images[0]['focal_x'] ?? 50);
        $fy = (int)($images[0]['focal_y'] ?? 50);

        $locParts = array_filter([
            trim((string)($item['location_zip'] ?? '')),
            trim((string)($item['location_city'] ?? '')),
            trim((string)($item['location_district'] ?? '')),
        ]);
        $locText = !empty($locParts) ? implode(' ', $locParts) : 'Auf Anfrage';

        $detailId = 'wfm-detail-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$item['id']);

        ob_start();
        ?>
        <script type="application/ld+json"><?php echo $jsonLd; ?></script>

        <div id="<?php echo htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8'); ?>"
             class="weave-fm-detail"
             style="max-width: 1040px; margin: 1.5rem auto 2.5rem auto;">
            <!-- Breadcrumb -->
            <nav aria-label="Breadcrumb" style="font-size: 0.86rem; color: var(--text-secondary, #64748b); margin-bottom: 1rem; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <a href="?page=flohmarkt" style="color: var(--primary, #2e6248); font-weight: 600; text-decoration: none;">← Zurück zum Flohmarkt-Katalog</a>
                <span>/</span>
                <a href="?page=flohmarkt&fm_cat=<?php echo urlencode((string)($item['category_id'] ?? '')); ?>" style="color: inherit; text-decoration: none;">
                    <?php echo htmlspecialchars($catName, ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <span>/</span>
                <span><?php echo htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            </nav>

            <div class="card" style="padding: 1.5rem; border-radius: var(--card-radius, 16px); border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: var(--bg-card, #ffffff);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 1.75rem;">
                    <!-- Linke Spalte: Mehrfachbild-Galerie -->
                    <div>
                        <div class="wfm-main-stage"
                             style="position: relative; border-radius: 12px; overflow: hidden; background: rgba(148,163,184,0.12); height: 340px; display: flex; align-items: center; justify-content: center;">
                            <?php if ($mainImg !== ''): ?>
                                <img src="<?php echo htmlspecialchars($mainImg, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="wfm-hero-img"
                                     data-wfm-open-lightbox="0"
                                     style="width: 100%; height: 100%; object-fit: cover; object-position: <?php echo $fx; ?>% <?php echo $fy; ?>%; cursor: zoom-in;">
                            <?php else: ?>
                                <span class="material-icons" style="font-size: 64px; color: var(--text-secondary, #94a3b8);">local_offer</span>
                            <?php endif; ?>
                            <span style="position: absolute; top: 12px; left: 12px; font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; color: <?php echo $stInfo['color']; ?>; background: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.16);">
                                <?php echo htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <?php if (count($images) > 1): ?>
                            <div style="display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; padding-bottom: 4px;">
                                <?php foreach ($images as $idx => $im): ?>
                                    <button type="button"
                                            class="wfm-thumb-btn"
                                            data-src="<?php echo htmlspecialchars((string)$im['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-idx="<?php echo (int)$idx; ?>"
                                            style="width: 68px; height: 68px; padding: 0; border-radius: 8px; border: 2px solid <?php echo $idx === 0 ? 'var(--primary, #2e6248)' : 'transparent'; ?>; overflow: hidden; cursor: pointer; flex-shrink: 0;">
                                        <img src="<?php echo htmlspecialchars((string)($im['thumb_url'] ?? $im['url']), ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rechte Spalte: Preis, Titel, Steckbrief, Anbieter & Teilen -->
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary, #64748b); margin-bottom: 4px;">
                            <?php echo htmlspecialchars($catName . ' · ' . $typeLabel . ' · ' . $item['item_number'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($item['stand_number'])): ?>
                                <span style="display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: rgba(245,158,11,0.16); color: #b45309; font-weight: 700;">
                                    🎪 Vor Ort an Stand <?php echo htmlspecialchars((string)$item['stand_number'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 style="margin: 0 0 0.75rem 0; font-size: 1.65rem; color: var(--text-primary, #0f172a); line-height: 1.25;">
                            <?php echo htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </h1>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary, #2e6248); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars(FleamarketManager::formatPrice($item), ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <!-- Steckbrief -->
                        <div style="border: 1px solid var(--border-color, rgba(0,0,0,0.1)); border-radius: 10px; padding: 0.9rem 1rem; margin-bottom: 1rem; font-size: 0.88rem; display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px;">
                            <div><strong>Zustand:</strong><br><?php echo htmlspecialchars($condLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><strong>Übergabe:</strong><br><?php echo htmlspecialchars($delivLabel, ENT_QUOTES, 'UTF-8'); ?><?php echo ($item['shipping_cost'] ?? 0) > 0 ? (' (+' . number_format((float)$item['shipping_cost'], 2, ',', '.') . ' €)') : ''; ?></div>
                            <div><strong>Standort:</strong><br><?php echo htmlspecialchars($locText, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><strong>Eingestellt am:</strong><br><?php echo htmlspecialchars(date('d.m.Y', strtotime((string)($item['created_at'] ?? 'now')) ?: time()), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><strong>Menge:</strong><br><?php echo (int)($item['quantity'] ?? 1); ?> Stück</div>
                            <div><strong>Aufrufe:</strong><br><?php echo (int)($item['views'] ?? 0); ?></div>
                        </div>

                        <!-- Anbieter-Infobox -->
                        <div style="padding: 0.9rem 1rem; border-radius: 10px; background: rgba(148,163,184,0.08); margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <div style="font-size: 0.76rem; color: var(--text-secondary, #64748b);">Anbieter</div>
                                <strong><?php echo htmlspecialchars((string)($item['seller_name'] ?? 'Anbieter'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($item['seller_verified'])): ?>
                                    <span style="font-size: 0.72rem; background: #dcfce7; color: #166534; padding: 2px 7px; border-radius: 999px; font-weight: 700; margin-left: 4px;">✓ Verifiziert</span>
                                <?php endif; ?>
                            </div>
                            <a href="?page=flohmarkt&fm_seller=<?php echo urlencode((string)($item['seller_id'] ?? '')); ?>"
                               class="btn btn-outline"
                               style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">
                                Alle Anzeigen dieses Anbieters
                            </a>
                        </div>

                        <!-- Teilen-Leiste & Merkliste mit vollständiger https://-URL -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <button type="button"
                                    class="btn btn-outline wfm-watch-btn"
                                    data-wfm-watch="<?php echo htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-label-on="♥ Gemerkt"
                                    data-label-off="♡ Merken"
                                    style="padding: 6px 12px; font-size: 0.84rem;">
                                ♡ Merken
                            </button>
                            <a href="?page=flohmarkt&item=<?php echo urlencode((string)$item['slug']); ?>&print_tag=1"
                               class="btn btn-outline"
                               style="padding: 6px 12px; font-size: 0.84rem; text-decoration: none;">
                                🖨️ QR-Preisschild
                            </a>
                            <button type="button"
                                    class="btn btn-outline wfm-copy-share"
                                    data-share-url="<?php echo htmlspecialchars($fullItemUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    style="padding: 6px 12px; font-size: 0.84rem;">
                                🔗 Link kopieren
                            </button>
                            <a href="https://wa.me/?text=<?php echo rawurlencode((string)$item['title'] . ' – ' . $fullItemUrl); ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-outline"
                               style="padding: 6px 12px; font-size: 0.84rem; text-decoration: none;">
                                WhatsApp
                            </a>
                            <a href="mailto:?subject=<?php echo rawurlencode((string)$item['title']); ?>&body=<?php echo rawurlencode($fullItemUrl); ?>"
                               class="btn btn-outline"
                               style="padding: 6px 12px; font-size: 0.84rem; text-decoration: none;">
                                E-Mail
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Spamsichere Interessenten-Anfrage (Anbieter-E-Mail bleibt verborgen) -->
                <?php
                    $inqFlash = $_SESSION['weave_fm_inq_flash'][$item['id']] ?? null;
                    unset($_SESSION['weave_fm_inq_flash'][$item['id']]);
                    $inqClosed = in_array((string)($item['status'] ?? 'active'), ['sold', 'expired', 'archived'], true);
                    $csrf = (string)($_SESSION['csrf_token'] ?? '');
                ?>
                <div style="margin-top: 1.5rem; padding: 1.15rem 1.25rem; border-radius: 12px; border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: rgba(148,163,184,0.05);">
                    <h3 style="margin: 0 0 0.65rem 0; font-size: 1.1rem;">Anbieter kontaktieren / Anfrage senden</h3>
                    <?php if ($inqFlash && !empty($inqFlash['text'])): ?>
                        <div style="margin-bottom: 0.85rem; padding: 9px 12px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; background: <?php echo !empty($inqFlash['ok']) ? '#dcfce7' : '#fef2f2'; ?>; color: <?php echo !empty($inqFlash['ok']) ? '#166534' : '#991b1b'; ?>;">
                            <?php echo htmlspecialchars((string)$inqFlash['text'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($inqClosed): ?>
                        <p style="margin: 0; color: var(--text-secondary);">Diese Anzeige ist bereits beendet/verkauft – das Anfrageformular ist geschlossen.</p>
                    <?php else: ?>
                        <form method="POST" action="" style="display: grid; gap: 10px;">
                            <input type="hidden" name="weave_fm_action" value="send_inquiry">
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="text" name="website_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute; left:-9999px; opacity:0;">

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px;">
                                <input type="text" name="sender_name" required class="form-control" placeholder="Dein Name *">
                                <input type="email" name="sender_email" required class="form-control" placeholder="Deine E-Mail-Adresse *">
                                <select name="delivery_pref" class="form-control">
                                    <option value="pickup">Präferenz: Abholung</option>
                                    <option value="shipping">Präferenz: Versand</option>
                                </select>
                                <?php if (($item['price_model'] ?? '') === 'negotiable'): ?>
                                    <input type="number" step="0.5" min="0" name="offer_price" class="form-control" placeholder="Preisvorschlag (€, optional)">
                                <?php endif; ?>
                            </div>
                            <textarea name="message" required rows="3" class="form-control" placeholder="Deine Nachricht an den Anbieter..."></textarea>
                            <div>
                                <button type="submit" class="btn btn-primary" style="padding: 8px 18px;">Anfrage sicher senden</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Anzeige melden (Phase 40.2) -->
                <?php
                    $repFlash = $_SESSION['weave_fm_report_flash'][$item['id']] ?? null;
                    unset($_SESSION['weave_fm_report_flash'][$item['id']]);
                ?>
                <div style="margin-top: 1.25rem;">
                    <?php if ($repFlash && !empty($repFlash['text'])): ?>
                        <div style="margin-bottom: 0.65rem; padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; background: <?php echo !empty($repFlash['ok']) ? '#dcfce7' : '#fef2f2'; ?>; color: <?php echo !empty($repFlash['ok']) ? '#166534' : '#991b1b'; ?>;">
                            <?php echo htmlspecialchars((string)$repFlash['text'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <details style="border: 1px solid var(--border-color, rgba(0,0,0,0.08)); border-radius: 10px; padding: 0.65rem 1rem;">
                        <summary style="cursor: pointer; font-size: 0.86rem; font-weight: 600; color: var(--text-secondary, #64748b);">🚩 Anzeige melden</summary>
                        <form method="POST" action="" style="display: grid; gap: 8px; margin-top: 10px;">
                            <input type="hidden" name="weave_fm_action" value="report_item">
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="text" name="website_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute; left:-9999px; opacity:0;">
                            <select name="reason" class="form-control">
                                <option value="spam">Spam / Werbung</option>
                                <option value="prohibited">Unzulässiger Artikel</option>
                                <option value="fraud">Betrugsverdacht</option>
                                <option value="wrong_category">Falsche Kategorie</option>
                                <option value="sold_already">Bereits verkauft / veraltet</option>
                                <option value="other">Sonstiger Grund</option>
                            </select>
                            <textarea name="comment" rows="2" class="form-control" placeholder="Kurzer Hinweis an die Moderation (optional)..."></textarea>
                            <div><button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.82rem;">Meldung absenden</button></div>
                        </form>
                    </details>
                </div>

                <!-- Beschreibung -->
                <?php if (!empty($item['description'])): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color, rgba(0,0,0,0.08));">
                        <h3 style="margin: 0 0 0.65rem 0; font-size: 1.15rem;">Beschreibung</h3>
                        <div style="line-height: 1.65; color: var(--text-primary, #1f2937);">
                            <?php echo FleamarketManager::renderMarkdownSafe((string)$item['description']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return (string)ob_get_clean() . weave_fleamarket_detail_script_once() . (function_exists('weave_fleamarket_watchlist_script_once') ? weave_fleamarket_watchlist_script_once() : '');
    }

    function weave_fleamarket_detail_script_once(): string {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;
        $nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
        return <<<HTML
<div id="wfm-lightbox" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,0.92); align-items:center; justify-content:center; padding:1rem;">
    <button type="button" id="wfm-lb-close" aria-label="Schließen" style="position:absolute; top:16px; right:20px; background:rgba(255,255,255,0.16); color:#fff; border:none; border-radius:999px; width:40px; height:40px; font-size:22px; cursor:pointer;">✕</button>
    <button type="button" id="wfm-lb-prev" aria-label="Vorheriges Bild" style="position:absolute; left:16px; background:rgba(255,255,255,0.16); color:#fff; border:none; border-radius:999px; width:42px; height:42px; font-size:20px; cursor:pointer;">◀</button>
    <img id="wfm-lb-img" src="" alt="" style="max-width:92vw; max-height:86vh; border-radius:10px; object-fit:contain;">
    <button type="button" id="wfm-lb-next" aria-label="Nächstes Bild" style="position:absolute; right:16px; background:rgba(255,255,255,0.16); color:#fff; border:none; border-radius:999px; width:42px; height:42px; font-size:20px; cursor:pointer;">▶</button>
</div>
<script{$nonceAttr}>
(function() {
    if (window.__weaveFleamarketDetailBound) return;
    window.__weaveFleamarketDetailBound = true;

    let lbImages = [];
    let lbIndex = 0;
    let touchStartX = 0;

    function showLbIndex(nextIdx) {
        if (!lbImages.length) return;
        lbIndex = (nextIdx + lbImages.length) % lbImages.length;
        const imgEl = document.getElementById('wfm-lb-img');
        if (imgEl) imgEl.src = lbImages[lbIndex];
    }

    document.addEventListener('click', function(e) {
        const thumb = e.target && e.target.closest ? e.target.closest('.wfm-thumb-btn') : null;
        if (thumb) {
            const detail = thumb.closest('.weave-fm-detail');
            const hero = detail ? detail.querySelector('.wfm-hero-img') : null;
            if (hero) {
                hero.src = thumb.getAttribute('data-src') || '';
                hero.setAttribute('data-wfm-open-lightbox', thumb.getAttribute('data-idx') || '0');
            }
            return;
        }
        const hero = e.target && e.target.closest ? e.target.closest('.wfm-hero-img') : null;
        if (hero) {
            const detail = hero.closest('.weave-fm-detail');
            const thumbs = detail ? Array.from(detail.querySelectorAll('.wfm-thumb-btn')) : [];
            lbImages = thumbs.length ? thumbs.map(function(b) { return b.getAttribute('data-src') || ''; }) : [hero.src];
            const lb = document.getElementById('wfm-lightbox');
            if (lb) {
                showLbIndex(parseInt(hero.getAttribute('data-wfm-open-lightbox') || '0', 10));
                lb.style.display = 'flex';
            }
            return;
        }
        if (e.target && (e.target.id === 'wfm-lb-close' || e.target.id === 'wfm-lightbox')) {
            const lb = document.getElementById('wfm-lightbox');
            if (lb) lb.style.display = 'none';
            return;
        }
        if (e.target && e.target.id === 'wfm-lb-prev') { showLbIndex(lbIndex - 1); return; }
        if (e.target && e.target.id === 'wfm-lb-next') { showLbIndex(lbIndex + 1); return; }

        const printBtn = e.target && e.target.closest ? e.target.closest('[data-action="wfm-print-tag"]') : null;
        if (printBtn) {
            window.print();
            return;
        }

        const copyBtn = e.target && e.target.closest ? e.target.closest('.wfm-copy-share') : null;
        if (copyBtn) {
            const url = copyBtn.getAttribute('data-share-url') || window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    copyBtn.textContent = '✓ Link kopiert!';
                });
            }
        }
    });

    const lbEl = document.getElementById('wfm-lightbox');
    if (lbEl) {
        lbEl.addEventListener('touchstart', function(e) { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        lbEl.addEventListener('touchend', function(e) {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 40) showLbIndex(diff < 0 ? lbIndex + 1 : lbIndex - 1);
        }, { passive: true });
    }
})();
</script>
HTML;
    }
}
