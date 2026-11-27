<?php
/**
 * ============================================================================
 * Automatisierte Test-Suite: Weave Flohmarkt Teil 1 (Phase 38)
 * ============================================================================
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'example.com';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require_once dirname(__DIR__) . '/libs/php/SecurityManager.php';
require_once dirname(__DIR__) . '/libs/php/PluginManager.php';
require_once dirname(__DIR__) . '/libs/php/QuickActionManager.php';
require_once dirname(__DIR__) . '/libs/php/WebhookManager.php';
require_once dirname(__DIR__) . '/plugins/weave-fleamarket/plugin.php';

function assert_fm(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'weave_fm_test_' . bin2hex(random_bytes(4));
@mkdir($tmpDir, 0755, true);
FleamarketManager::setTestStorageDir($tmpDir);

try {
    // 1. Menü-Governance: show_in_main_nav standardmäßig false
    $cfg = FleamarketManager::getConfig();
    assert_fm($cfg['show_in_main_nav'] === false, 'show_in_main_nav muss standardmäßig false sein (Menü-Governance)');
    $cats = FleamarketManager::getCategories();
    assert_fm(count($cats) === 7, 'Standardkategorien (7) müssen angelegt sein');

    // 2. Anzeigen CRUD, FM-YYYY-NNNN Nummernkreis & eindeutige Slugs
    $item1 = FleamarketManager::saveItem([
        'title'         => 'Alter Massivholz-Tisch',
        'description'   => 'Sehr **schöner** Holztisch für das Esszimmer.',
        'category_id'   => 'cat_furniture',
        'ad_type'       => 'offer',
        'price_model'   => 'negotiable',
        'price'         => '85.00',
        'condition'     => 'good',
        'delivery_mode' => 'pickup',
        'location_zip'  => '80331',
        'location_city' => 'München',
        'seller_name'   => 'Holzwerk',
        'seller_id'     => 'seller_1',
        'seller_verified' => true,
        'status'        => 'active',
        'images'        => [
            ['url' => 'https://example.com/tisch1.jpg', 'focal_x' => 30, 'focal_y' => 60],
            ['url' => 'https://example.com/tisch2.jpg'],
        ],
    ]);
    $item2 = FleamarketManager::saveItem([
        'title'         => 'Alter Massivholz Tisch', // Kollidierender Slug!
        'category_id'   => 'cat_free',
        'ad_type'       => 'gift',
        'price_model'   => 'free',
        'condition'     => 'used',
        'delivery_mode' => 'both',
        'shipping_cost' => '6.90',
        'location_zip'  => '10115',
        'location_city' => 'Berlin',
        'status'        => 'reserved',
    ]);
    assert_fm($item1['item_number'] === 'FM-' . date('Y') . '-0001', 'Erste Anzeigennummer muss FM-YYYY-0001 sein');
    assert_fm($item2['item_number'] === 'FM-' . date('Y') . '-0002', 'Zweite Anzeigennummer muss FM-YYYY-0002 sein');
    assert_fm($item1['slug'] !== $item2['slug'], 'Kollidierende Titel müssen eindeutige Slugs erhalten');

    // 3. Katalog-Filter & Sortierung & hide_reserved
    $resAll = FleamarketManager::filterItems(['sort' => 'price_asc']);
    assert_fm($resAll['total'] === 2 && $resAll['items'][0]['id'] === $item2['id'], 'Sortierung price_asc fehlgeschlagen');

    $resNoRes = FleamarketManager::filterItems(['hide_reserved' => true]);
    assert_fm($resNoRes['total'] === 1 && $resNoRes['items'][0]['id'] === $item1['id'], 'hide_reserved Filter fehlgeschlagen');

    $resVb = FleamarketManager::filterItems(['only_vb' => true, 'location' => 'München']);
    assert_fm($resVb['total'] === 1 && $resVb['items'][0]['id'] === $item1['id'], 'Kombifilter only_vb + Ort fehlgeschlagen');

    // 4. Views & Statuswechsel
    FleamarketManager::incrementViews($item1['id']);
    $reloaded = FleamarketManager::getItem($item1['slug']);
    assert_fm(($reloaded['views'] ?? 0) === 1, 'Aufrufzähler muss auf 1 steigen');
    assert_fm(FleamarketManager::setItemStatus($item2['id'], 'sold'), 'Statuswechsel auf sold fehlgeschlagen');

    // 5. Detailansicht: Galerie, Steckbrief, vollständige https:// Share-URL, JSON-LD & CSP
    $detailHtml = weave_fleamarket_render_detail($reloaded);
    assert_fm(strpos($detailHtml, 'application/ld+json') !== false && strpos($detailHtml, 'https://schema.org') !== false, 'Schema.org JSON-LD fehlt');
    assert_fm(strpos($detailHtml, 'https://example.com/?page=flohmarkt&amp;item=' . urlencode($item1['slug'])) !== false, 'Vollständige https:// Share-URL fehlt in Detailansicht');
    assert_fm(strpos($detailHtml, 'object-position: 30% 60%') !== false, 'Fokuspunkt object-position fehlt');
    assert_fm(strpos($detailHtml, 'wfm-lightbox') !== false && preg_match('/<script\s+nonce="[^"]+"/', $detailHtml) === 1, 'Lightbox mit CSP-Nonce fehlt');
    assert_fm(!preg_match('/\son(click|submit|change)=/i', $detailHtml), 'Detailansicht darf keine Inline-Handler enthalten');

    // 6. Katalog-Rendering & Shortcodes inkl. <code>-Schutz
    $rawPage = '<p>Flohmarkt:</p>[weave_fleamarket layout="list" limit="4"]<code>[fleamarket]</code>';
    $rendered = PluginManager::applyFilters('content_shortcodes', $rawPage, []);
    assert_fm(strpos($rendered, '<code>[fleamarket]</code>') !== false, '[fleamarket] im <code>-Tag muss geschützt bleiben');
    assert_fm(strpos($rendered, 'weave-fm-layout-list') !== false && strpos($rendered, 'Alter Massivholz-Tisch') !== false, 'Shortcode Katalog-Rendering fehlgeschlagen');

    // 7. SectionManager-Hook mit Opt-In Menü-Konfiguration
    FleamarketManager::saveConfig(['show_in_main_nav' => true, 'nav_label' => 'Basar', 'placement' => 'both']);
    $secs = PluginManager::applyFilters('registered_sections', [], ['plugins' => ['active' => ['weave-fleamarket']]]);
    $fmSec = null;
    foreach ($secs as $s) {
        if (($s['id'] ?? '') === 'flohmarkt') {
            $fmSec = $s;
            break;
        }
    }
    assert_fm($fmSec !== null && $fmSec['in_nav'] === true && $fmSec['nav_label'] === 'Basar' && $fmSec['placement'] === 'both', 'SectionManager-Integration mit Opt-In fehlgeschlagen');

    // 8. Phase 39: Anbieter-Modi (admin_only Sperre, Gast Double-Opt-In + Self-Service Token, Mitglieder-Kontingent)
    FleamarketManager::saveConfig(['submission_mode' => 'admin_only']);
    assert_fm(!FleamarketManager::canSubmitFrontend(null), 'admin_only darf Gäste nicht einreichen lassen');

    FleamarketManager::saveConfig(['submission_mode' => 'guest_moderated', 'require_approval' => false, 'max_items_per_seller' => 2]);
    $guestMails = [];
    $guestMailer = static function (string $to, string $subj, string $html, string $text) use (&$guestMails): void {
        $guestMails[] = ['to' => $to, 'subj' => $subj, 'text' => $text];
    };
    $gRes = FleamarketManager::submitFrontendItem([
        'title'        => 'Vintage Stehlampe',
        'description'  => 'Voll funktionsfähige Retro-Lampe',
        'price_model'  => 'negotiable',
        'price'        => '45.00',
        'seller_name'  => 'Sabine',
        'seller_email' => 'sabine-private@example.com',
    ], null, $guestMailer);
    assert_fm($gRes['success'] && $gRes['item']['status'] === 'pending' && count($guestMails) === 1, 'Gast-Einreichung muss Double-Opt-In-Mail versenden und pending starten');

    $confRes = FleamarketManager::confirmGuestItem((string)$gRes['item']['confirm_token']);
    assert_fm($confRes['success'] && ($confRes['item']['status'] ?? '') === 'active', 'Nach E-Mail-Bestätigung ohne Freigabepflicht muss Anzeige aktiv werden');

    $ssRes = FleamarketManager::applySelfServiceAction((string)$gRes['item']['manage_token'], 'reserved');
    assert_fm($ssRes['success'] && ($ssRes['item']['status'] ?? '') === 'reserved', 'Self-Service-Token muss Status auf reserved setzen können');
    FleamarketManager::applySelfServiceAction((string)$gRes['item']['manage_token'], 'active');

    // 9. Phase 39: Datenschutz-Foto-Pipeline (GD-Rekodierung & EXIF-Entfernung + Thumbnail)
    $srcJpg = $tmpDir . DIRECTORY_SEPARATOR . 'sample.jpg';
    $im = imagecreatetruecolor(200, 120);
    imagejpeg($im, $srcJpg, 90);
    unset($im);
    $imgUpDir = $tmpDir . DIRECTORY_SEPARATOR . 'uploads';
    $pipeRes = FleamarketImagePipeline::processUploadedFile($srcJpg, $imgUpDir, 'images/uploads/fleamarket');
    assert_fm($pipeRes['success'] && file_exists($pipeRes['abs_path']) && file_exists($pipeRes['thumb_path']) && !empty($pipeRes['exif_stripped']), 'Datenschutz-Foto-Pipeline fehlgeschlagen');

    // 10. Phase 39: Spamsichere Anfragen, Preisvorschlag & E-Mail-Schutz im HTML + Merkliste
    $guestDetailHtml = weave_fleamarket_render_detail($gRes['item']);
    assert_fm(strpos($guestDetailHtml, 'sabine-private@example.com') === false, 'Anbieter-E-Mail darf niemals im Detail-HTML stehen');
    assert_fm(strpos($guestDetailHtml, 'name="offer_price"') !== false && strpos($guestDetailHtml, 'wfm-watch-btn') !== false, 'VB-Preisvorschlag & Merklisten-Button fehlen');
    assert_fm(!preg_match('/\son(click|submit|change)=/i', $guestDetailHtml), 'Detailansicht mit Anfrageformular darf keine Inline-Handler enthalten');

    $hpFail = FleamarketManager::sendItemInquiry($gRes['item']['id'], [
        'sender_name'  => 'Bot',
        'sender_email' => 'bot@spam.de',
        'message'      => 'Buy cheap!',
        'website_hp'   => 'http://spam',
    ]);
    assert_fm(!$hpFail['success'], 'Honeypot muss Spam-Anfrage blockieren');

    $inqMails = [];
    $inqRes = FleamarketManager::sendItemInquiry($gRes['item']['id'], [
        'sender_name'   => 'Lukas',
        'sender_email'  => 'lukas@example.com',
        'offer_price'   => '38.50',
        'delivery_pref' => 'pickup',
        'message'       => 'Hallo, wäre 38,50 Euro bei Abholung okay?',
    ], null, static function (string $to, string $subj, string $h, string $t) use (&$inqMails): array {
        $inqMails[] = compact('to', 'subj', 't');
        return ['success' => true];
    });
    assert_fm($inqRes['success'] && count($inqMails) === 1 && strpos($inqMails[0]['t'], '38,50') !== false, 'Anfrage mit Preisvorschlag muss Anbieter erreichen');

    FleamarketManager::setItemStatus($gRes['item']['id'], 'sold');
    $soldInq = FleamarketManager::sendItemInquiry($gRes['item']['id'], [
        'sender_name'  => 'Karl',
        'sender_email' => 'karl@example.com',
        'message'      => 'Noch da?',
    ]);
    assert_fm(!$soldInq['success'], 'Verkaufte Anzeigen müssen Anfragen sperren');

    // 11. Phase 40.1: Event-/Vor-Ort-Modus, Warteliste, .ics-Anhang & QR-Preisschild
    FleamarketManager::saveConfig([
        'onsite_enabled'          => '1',
        'onsite_event_title'      => 'Dorf-Flohmarkt 2026',
        'onsite_event_date'       => '2026-12-10',
        'onsite_location'         => 'Marktplatz 1',
        'onsite_max_stands'       => 1,
        'onsite_waitlist_enabled' => '1',
    ]);
    $standMails = [];
    $standMailer = static function (string $to, string $subj, string $h, string $t, array $atts) use (&$standMails): void {
        $standMails[] = compact('to', 'subj', 'atts');
    };
    $st1 = FleamarketManager::registerStand(['name' => 'Anna', 'email' => 'anna@example.com', 'meters' => 4], null, $standMailer);
    assert_fm($st1['success'] && ($st1['stand']['status'] ?? '') === 'confirmed' && ($st1['stand']['stand_number'] ?? '') === '#1', 'Erster Stand muss #1 confirmed erhalten');
    assert_fm(count($standMails) === 1 && !empty($standMails[0]['atts']) && strpos($standMails[0]['atts'][0]['content'], 'BEGIN:VCALENDAR') !== false, '.ics Kalenderanhang muss erzeugt werden');
    $st2 = FleamarketManager::registerStand(['name' => 'Bernd', 'email' => 'bernd@example.com', 'meters' => 2], null, $standMailer);
    assert_fm($st2['success'] && ($st2['stand']['status'] ?? '') === 'waitlist', 'Überzähliger Stand muss auf Warteliste landen');

    $tagItem = FleamarketManager::saveItem([
        'title'        => 'Porzellankanne mit Stand',
        'price'        => '18.00',
        'stand_number' => '#1',
        'status'       => 'active',
    ]);
    $tagHtml = FleamarketManager::renderQrPriceTagHtml($tagItem, 'https://example.com/?page=flohmarkt&item=' . $tagItem['slug']);
    assert_fm(strpos($tagHtml, 'Vor Ort an Stand #1') !== false && strpos($tagHtml, '<svg ') !== false && strpos($tagHtml, 'data-action="wfm-print-tag"') !== false, 'QR-Preisschild muss SVG-Code, Standnummer und CSP-Button enthalten');

    // 12. Phase 40.2: Meldefunktion, Moderations-Queue, Anbietersperre & Audit-Log
    $repHp = FleamarketManager::reportItem($tagItem['id'], ['reason' => 'spam', 'website_hp' => 'bot']);
    assert_fm(!$repHp['success'], 'Melde-Honeypot muss Spam blockieren');
    $repOk = FleamarketManager::reportItem($tagItem['id'], ['reason' => 'fraud', 'comment' => 'Testmeldung']);
    assert_fm($repOk['success'] && count(FleamarketManager::getReports('open')) === 1, 'Offene Meldung muss gespeichert werden');

    $blkItem = FleamarketManager::saveItem([
        'title'        => 'Gesperrter Artikel',
        'seller_id'    => 'guest_bad_seller',
        'seller_email' => 'bad@example.com',
        'status'       => 'active',
    ]);
    $repBad = FleamarketManager::reportItem($blkItem['id'], ['reason' => 'prohibited']);
    $resBlock = FleamarketManager::resolveReport($repBad['report']['id'], 'block_seller', 'mod_tester');
    assert_fm($resBlock['success'] && FleamarketManager::isSellerBlocked('guest_bad_seller', 'bad@example.com'), 'Anbieter muss gesperrt sein');
    assert_fm((FleamarketManager::getItem($blkItem['id'])['status'] ?? '') === 'archived', 'Anzeigen gesperrter Anbieter müssen archiviert werden');
    $blkSubmit = FleamarketManager::submitFrontendItem(['title' => 'Trotz Sperre', 'seller_email' => 'bad@example.com']);
    assert_fm(!$blkSubmit['success'], 'Gesperrter Anbieter darf keine neuen Anzeigen einreichen');
    assert_fm(count(FleamarketManager::getAuditLog()) >= 3, 'Audit-Log muss Moderationsaktionen enthalten');

    // 13. Phase 40.3: Ablauf-Automatik, Erinnerungs-Mail, 1-Klick-Verlängerung & DSGVO-Bereinigung
    FleamarketManager::saveConfig([
        'submission_mode'          => 'guest_moderated',
        'require_approval'         => '0',
        'expiry_reminder_enabled'  => '1',
        'expiry_reminder_days'     => 5,
        'retention_days_expired'   => 10,
        'retention_days_inquiries' => 30,
    ]);
    assert_fm(FleamarketManager::getConfig()['require_approval'] === false, 'Phase-39-Audit-Fix: require_approval=0 muss false speichern');

    $soonItem = FleamarketManager::saveItem([
        'title'        => 'Bald ablaufend',
        'seller_id'    => 'guest_soon',
        'seller_email' => 'soon@example.com',
        'status'       => 'active',
        'expires_at'   => date('Y-m-d H:i:s', time() + (2 * 86400)),
    ]);
    $oldGuestItem = FleamarketManager::saveItem([
        'title'      => 'Steinalter Gastartikel',
        'seller_id'  => 'guest_old',
        'status'     => 'expired',
        'expires_at' => date('Y-m-d H:i:s', time() - (20 * 86400)),
    ]);
    // Verwaiste Bilddatei anlegen
    $orphanFile = $imgUpDir . DIRECTORY_SEPARATOR . 'fm_20260101_orphan.jpg';
    @file_put_contents($orphanFile, 'orphan');

    $remMails = [];
    $maintStats = FleamarketManager::runLifecycleMaintenance(null, null, static function (string $to, string $subj) use (&$remMails): void {
        $remMails[] = compact('to', 'subj');
    }, $imgUpDir);
    assert_fm($maintStats['reminders_sent'] === 1 && count($remMails) === 1, 'Ablauf-Erinnerungsmail muss genau einmal versendet werden');
    assert_fm($maintStats['purged_items'] >= 1 && FleamarketManager::getItem($oldGuestItem['id']) === null, 'Abgelaufene Gast-Anzeige nach Aufbewahrungsfrist muss gelöscht werden');
    assert_fm(!file_exists($orphanFile) && $maintStats['removed_images'] >= 1, 'Verwaister Bildupload muss bereinigt werden');

    $extRes = FleamarketManager::extendItemByToken((string)$soonItem['manage_token'], 30);
    assert_fm($extRes['success'] && ($extRes['item']['extended_count'] ?? 0) === 1, '1-Klick-Verlängerung muss Ablaufdatum und Zähler aktualisieren');

    // 14. Phase 40.4: JSON/CSV Export & Import + Phase-39-Audit-Fixes
    $pendingUnconfirmed = FleamarketManager::submitFrontendItem(['title' => 'Unbestätigte Gastanzeige', 'seller_email' => 'pending@example.com']);
    $selfActiveFail = FleamarketManager::applySelfServiceAction((string)$pendingUnconfirmed['item']['manage_token'], 'active');
    assert_fm(!$selfActiveFail['success'], 'Phase-39-Audit-Fix: Unbestätigte/pending Anzeige darf via Self-Service nicht selbst aktiviert werden');
    $noDelivInq = FleamarketManager::sendItemInquiry($tagItem['id'], [
        'sender_name'  => 'Nina',
        'sender_email' => 'nina@example.com',
        'message'      => 'Anfrage ohne delivery_pref Feld',
    ]);
    assert_fm($noDelivInq['success'] && $noDelivInq['inquiry']['delivery_pref'] === 'pickup', 'Anfrage ohne delivery_pref muss ohne PHP-Warning funktionieren');

    FleamarketManager::saveItem(['title' => '=SUM(1+1) Gefaehrlicher Titel', 'status' => 'active']);
    $csv = FleamarketManager::exportCsv();
    assert_fm(str_starts_with($csv, "\xEF\xBB\xBF") && strpos($csv, "\"'=SUM(1+1)") !== false, 'CSV-Export muss UTF-8 BOM und Formula-Injection-Schutz enthalten');
    $exportedJson = FleamarketManager::exportJson();
    $impRes = FleamarketManager::importJson($exportedJson);
    assert_fm($impRes['success'] && $impRes['imported'] >= 1, 'JSON-Export und -Import müssen rundlaufen');

    // 15. Phase 40.4: QuickActionManager, WebhookManager, i18n JSON & CSP-Audit
    $qaCatalog = QuickActionManager::getCatalog();
    assert_fm(isset($qaCatalog['fleamarket_admin']), 'QuickActionManager muss fleamarket_admin registrieren');
    $whEvents = WebhookManager::getSupportedEvents();
    assert_fm(isset($whEvents['fleamarket.item_published'], $whEvents['fleamarket.inquiry_received']), 'WebhookManager muss beide Flohmarkt-Events unterstützen');

    $deJson = json_decode((string)file_get_contents(dirname(__DIR__) . '/libs/lang/admin/de.json'), true, 512, JSON_THROW_ON_ERROR);
    $enJson = json_decode((string)file_get_contents(dirname(__DIR__) . '/libs/lang/admin/en.json'), true, 512, JSON_THROW_ON_ERROR);
    assert_fm(isset($deJson['admin.fleamarket.tab_stands'], $enJson['admin.fleamarket.tab_stands']), 'DE/EN Admin-Sprachdateien müssen Flohmarkt-Keys enthalten');

    $adminHtml = (static function (): string {
        $data = [];
        $csrf_token = 'tok_test';
        ob_start();
        require dirname(__DIR__) . '/plugins/weave-fleamarket/admin/settings.php';
        return (string)ob_get_clean();
    })();
    assert_fm(!preg_match('/\son(click|submit|change)=/i', $adminHtml), 'Admin-Backend darf keine Inline-Event-Handler enthalten');

    echo "ALL FLEAMARKET TESTS PASSED (15/15)\n";
} finally {
    FleamarketManager::setTestStorageDir(null);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $entry) {
        $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }
    @rmdir($tmpDir);
}
