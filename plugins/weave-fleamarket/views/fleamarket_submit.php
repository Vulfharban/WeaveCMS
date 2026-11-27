<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – Einreichungs-, Selbstverwaltungs- &
 * Mitglieder-Ansichten (views/fleamarket_submit.php) – Phase 39
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_fleamarket_render_submit_form')) {

    function weave_fleamarket_render_submit_form(?array $member = null): string {
        $cfg = FleamarketManager::getConfig();
        $labels = FleamarketManager::getLabels();
        $cats = FleamarketManager::getCategories();
        $csrf = (string)($_SESSION['csrf_token'] ?? '');
        $maxImages = max(1, (int)($cfg['max_images_per_item'] ?? 6));

        if (!FleamarketManager::canSubmitFrontend($member)) {
            return '<div class="card" style="max-width:680px; margin:2rem auto; padding:1.5rem; text-align:center;">'
                . '<h3>Anzeige aufgeben</h3>'
                . '<p style="color:var(--text-secondary);">Aktuell können nur angemeldete Mitglieder bzw. Redakteure Anzeigen einstellen.</p>'
                . '<a href="?page=flohmarkt" class="btn btn-outline">← Zurück zum Flohmarkt</a></div>';
        }

        ob_start();
        ?>
        <div class="card weave-fm-submit" style="max-width: 820px; margin: 1.5rem auto; padding: 1.75rem; border-radius: var(--card-radius, 16px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; font-size: 1.4rem;">+ Neue Flohmarkt-Anzeige aufgeben</h2>
                <a href="?page=flohmarkt" class="btn btn-outline" style="text-decoration: none;">← Zurück</a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" data-wfm-submit-form="1">
                <input type="hidden" name="weave_fm_action" value="frontend_submit">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="website_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute; left:-9999px; opacity:0;">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>Titel *</label><input type="text" name="title" required minlength="3" class="form-control"></div>
                    <div><label>Kategorie</label>
                        <select name="category_id" class="form-control">
                            <?php foreach ($cats as $c): ?>
                                <option value="<?php echo htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Anzeigentyp</label>
                        <select name="ad_type" class="form-control">
                            <?php foreach ($labels['ad_types'] as $tk => $tl): ?>
                                <option value="<?php echo htmlspecialchars($tk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Zustand</label>
                        <select name="condition" class="form-control">
                            <?php foreach ($labels['conditions'] as $ck => $cl): ?>
                                <option value="<?php echo htmlspecialchars($ck, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>Preismodell</label>
                        <select name="price_model" class="form-control">
                            <?php foreach ($labels['price_models'] as $pk => $pl): ?>
                                <option value="<?php echo htmlspecialchars($pk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Preis (€)</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="10.00"></div>
                    <div><label>Übergabe</label>
                        <select name="delivery_mode" class="form-control">
                            <?php foreach ($labels['delivery_modes'] as $dk => $dl): ?>
                                <option value="<?php echo htmlspecialchars($dk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($dl, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Versandkosten (€)</label><input type="number" step="0.01" min="0" name="shipping_cost" class="form-control" value="0"></div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div><label>PLZ</label><input type="text" name="location_zip" class="form-control" placeholder="z. B. 80331"></div>
                    <div><label>Ort</label><input type="text" name="location_city" class="form-control" placeholder="z. B. München"></div>
                    <div><label>Stadtteil (optional)</label><input type="text" name="location_district" class="form-control"></div>
                    <?php if (!empty($cfg['onsite_enabled'])): ?>
                        <div><label>Stand-/Tischnummer (optional)</label><input type="text" name="stand_number" class="form-control" placeholder="z. B. #12"></div>
                    <?php endif; ?>
                </div>

                <?php if (!$member): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; padding: 12px; border-radius: 10px; background: rgba(148,163,184,0.08);">
                        <div><label>Dein Anzeigename *</label><input type="text" name="seller_name" required class="form-control"></div>
                        <div><label>Deine E-Mail (nicht öffentlich, Double-Opt-In) *</label><input type="email" name="seller_email" required class="form-control"></div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 12px;">
                    <label>Fotos hochladen (max. <?php echo $maxImages; ?> Bilder · GPS/EXIF wird automatisch entfernt)</label>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control" data-wfm-max="<?php echo $maxImages; ?>">
                </div>

                <div style="margin-bottom: 14px;">
                    <label>Beschreibung *</label>
                    <textarea name="description" required rows="4" class="form-control" placeholder="Beschreibe Zustand, Maße, Besonderheiten..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 9px 22px;">Anzeige einreichen</button>
            </form>
        </div>
        <?php
        return (string)ob_get_clean();
    }

    function weave_fleamarket_render_manage_view(array $item, string $manageToken): string {
        $labels = FleamarketManager::getLabels();
        $st = $labels['statuses'][$item['status'] ?? 'active'] ?? $labels['statuses']['active'];
        $csrf = (string)($_SESSION['csrf_token'] ?? '');

        ob_start();
        ?>
        <div class="card" style="max-width: 700px; margin: 2rem auto; padding: 1.5rem;">
            <h2 style="margin-top: 0;">Anzeige verwalten: <?php echo htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p style="color: var(--text-secondary);">
                Anzeigen-Nr.: <strong><?php echo htmlspecialchars((string)$item['item_number'], ENT_QUOTES, 'UTF-8'); ?></strong> · Aktueller Status: <strong><?php echo htmlspecialchars($st['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1rem;">
                <?php foreach (['active' => 'Verfügbar', 'reserved' => 'Als Reserviert markieren', 'sold' => 'Als Verkauft markieren', 'archived' => 'Pausieren', 'delete' => 'Unwiderruflich löschen'] as $ak => $al): ?>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="weave_fm_action" value="self_service">
                        <input type="hidden" name="manage_token" value="<?php echo htmlspecialchars($manageToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="fm_op" value="<?php echo htmlspecialchars($ak, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn <?php echo $ak === 'delete' ? 'btn-danger' : 'btn-outline'; ?>" style="padding: 6px 12px; font-size: 0.85rem;">
                            <?php echo htmlspecialchars($al, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            <a href="?page=flohmarkt&item=<?php echo urlencode((string)$item['slug']); ?>" class="btn btn-outline" style="text-decoration: none;">Zur Detailseite</a>
        </div>
        <?php
        return (string)ob_get_clean();
    }

    function weave_fleamarket_render_my_items(array $member): string {
        $res = FleamarketManager::filterItems(['seller_id' => $member['id'], 'include_all_statuses' => true], 1, 50);
        $csrf = (string)($_SESSION['csrf_token'] ?? '');
        ob_start();
        ?>
        <div class="card" style="max-width: 900px; margin: 1.5rem auto; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0;">Meine Anzeigen (<?php echo (int)$res['total']; ?>)</h2>
                <div style="display: flex; gap: 8px;">
                    <a href="?page=flohmarkt&view=submit" class="btn btn-primary" style="text-decoration: none;">+ Neue Anzeige</a>
                    <a href="?page=flohmarkt" class="btn btn-outline" style="text-decoration: none;">Zum Katalog</a>
                </div>
            </div>
            <?php if (empty($res['items'])): ?>
                <p style="color: var(--text-secondary);">Du hast noch keine Flohmarkt-Anzeigen erstellt.</p>
            <?php else: ?>
                <?php foreach ($res['items'] as $it): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color, #e2e8f0); flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong><?php echo htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo htmlspecialchars((string)$it['item_number'] . ' · Status: ' . $it['status']); ?></div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <?php foreach (['reserved' => 'Reserviert', 'sold' => 'Verkauft', 'active' => 'Aktiv', 'delete' => 'Löschen'] as $op => $lbl): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="weave_fm_action" value="member_item_action">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars((string)$it['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="fm_op" value="<?php echo $op; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 4px 9px; font-size: 0.78rem;"><?php echo $lbl; ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean();
    }

    function weave_fleamarket_render_stands_view(): string {
        $cfg = FleamarketManager::getConfig();
        $csrf = (string)($_SESSION['csrf_token'] ?? '');
        if (empty($cfg['onsite_enabled'])) {
            return '<div class="card" style="max-width:680px; margin:2rem auto; padding:1.5rem; text-align:center;">'
                . '<h3>Vor-Ort-Standplätze</h3>'
                . '<p style="color:var(--text-secondary);">Aktuell ist kein Vor-Ort-Flohmarkt-Termin zur Standanmeldung aktiviert.</p>'
                . '<a href="?page=flohmarkt" class="btn btn-outline" style="text-decoration:none;">← Zurück zum Katalog</a></div>';
        }
        $stands = FleamarketManager::getStands();
        $confirmed = count(array_filter($stands, static fn(array $s): bool => ($s['status'] ?? '') === 'confirmed'));
        $maxStands = max(1, (int)($cfg['onsite_max_stands'] ?? 30));
        $isFull = $confirmed >= $maxStands;
        $evDate = !empty($cfg['onsite_event_date']) ? date('d.m.Y', strtotime((string)$cfg['onsite_event_date']) ?: time()) : 'Termin folgt';

        ob_start();
        ?>
        <div class="card weave-fm-stands" style="max-width: 820px; margin: 1.5rem auto; padding: 1.75rem; border-radius: var(--card-radius, 16px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; font-size: 1.4rem;">🎪 Standplatz anmelden: <?php echo htmlspecialchars((string)($cfg['onsite_event_title'] ?? 'Flohmarkt'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <a href="?page=flohmarkt" class="btn btn-outline" style="text-decoration: none;">← Zurück</a>
            </div>

            <div style="padding: 1rem; border-radius: 10px; background: rgba(245,158,11,0.08); border: 1px solid rgba(180,83,9,0.22); margin-bottom: 1.25rem; font-size: 0.9rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                <div><strong>Termin:</strong> <?php echo htmlspecialchars($evDate, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)($cfg['onsite_start_time'] ?? '09:00'), ENT_QUOTES, 'UTF-8'); ?>–<?php echo htmlspecialchars((string)($cfg['onsite_end_time'] ?? '16:00'), ENT_QUOTES, 'UTF-8'); ?> Uhr)</div>
                <div><strong>Auf-/Abbau:</strong> ab <?php echo htmlspecialchars((string)($cfg['onsite_setup_time'] ?? '07:30'), ENT_QUOTES, 'UTF-8'); ?> / bis <?php echo htmlspecialchars((string)($cfg['onsite_teardown_time'] ?? '17:00'), ENT_QUOTES, 'UTF-8'); ?> Uhr</div>
                <?php if (!empty($cfg['onsite_location'])): ?><div><strong>Veranstaltungsort:</strong> <?php echo htmlspecialchars((string)$cfg['onsite_location'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if (!empty($cfg['onsite_fee_info'])): ?><div><strong>Standgebühr / Hinweis:</strong> <?php echo htmlspecialchars((string)$cfg['onsite_fee_info'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <div><strong>Belegung:</strong> <?php echo $confirmed; ?> / <?php echo $maxStands; ?> Stände <?php echo $isFull ? '(Warteliste aktiv)' : ''; ?></div>
            </div>

            <?php if (!empty($cfg['onsite_map_image'])): ?>
                <div style="margin-bottom: 1.25rem;">
                    <img src="<?php echo htmlspecialchars((string)$cfg['onsite_map_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Lageplan" style="max-width: 100%; max-height: 280px; border-radius: 10px; object-fit: contain;">
                </div>
            <?php endif; ?>

            <?php if ($isFull && empty($cfg['onsite_waitlist_enabled'])): ?>
                <p style="font-weight: 600; color: #dc2626;">Leider sind bereits alle Standplätze ausgebucht.</p>
            <?php else: ?>
                <form method="POST" action="" style="display: grid; gap: 12px;">
                    <input type="hidden" name="weave_fm_action" value="register_stand">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="website_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute; left:-9999px; opacity:0;">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div><label>Dein Name / Verein *</label><input type="text" name="name" required class="form-control"></div>
                        <div><label>E-Mail-Adresse (für Bestätigung &amp; .ics) *</label><input type="email" name="email" required class="form-control"></div>
                        <div><label>Telefon (optional)</label><input type="text" name="phone" class="form-control"></div>
                        <div><label>Benötigte Standlänge (Meter)</label><input type="number" name="meters" min="1" max="10" value="3" class="form-control"></div>
                    </div>
                    <div><label>Anmerkung (z. B. Pavillon, Strombedarf, Kinderflohmarkt)</label><textarea name="note" rows="2" class="form-control"></textarea></div>
                    <div><button type="submit" class="btn btn-primary"><?php echo $isFull ? 'Auf Warteliste eintragen' : 'Standplatz verbindlich anmelden'; ?></button></div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
