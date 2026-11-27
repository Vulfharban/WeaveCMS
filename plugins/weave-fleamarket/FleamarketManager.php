<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Flohmarkt – FleamarketManager (Phase 38)
 * ============================================================================
 * Verwaltet Flohmarkt-Anzeigen, Kategorien, eindeutige Anzeigen-Nummern
 * (FM-{YYYY}-{NNNN}), sprechende URL-Slugs, Such-/Filter-Engine, Status-Workflow
 * sowie Speicherung über StorageManager (MySQL/SQLite/JSON-Vererbung).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class FleamarketManager {

    protected static ?string $testStorageDir = null;
    protected static ?array $storeCache = null;

    public static function clearCache(): void {
        self::$storeCache = null;
    }

    public static function setTestStorageDir(?string $dir): void {
        self::$testStorageDir = $dir !== null ? rtrim($dir, '/\\') : null;
        self::clearCache();
    }

    protected static function getDataDir(): string {
        return self::$testStorageDir ?? (dirname(__DIR__, 2) . '/data');
    }

    public static function getDefaultConfig(bool $generateSecret = false): array {
        return [
            'section_title'              => 'Flohmarkt & Kleinanzeigen',
            'section_subtitle'           => 'Entdecke Second-Hand-Schätze, Unikate und Angebote aus der Community.',
            'show_in_main_nav'           => false, // Strikte Menü-Governance: nur auf Opt-In
            'nav_label'                  => 'Flohmarkt',
            'nav_icon'                   => 'local_offer',
            'placement'                  => 'page', // page | home | both
            'default_layout'             => 'grid', // grid | list | compact
            'items_per_page'             => 12,
            'currency'                   => 'EUR',
            // Phase 39: Anbieter-Modi, Freigabe & Kontingente
            'submission_mode'            => 'admin_only', // admin_only | members | guest_moderated
            'require_approval'           => true,
            'max_items_per_seller'       => 10,
            'max_images_per_item'        => 6,
            'default_duration_days'      => 30,
            'inquiry_rate_limit_seconds' => 60,
            'token_secret'               => $generateSecret ? bin2hex(random_bytes(32)) : '',
            // Phase 40.1: Event- & Vor-Ort-Flohmarkt-Modus
            'onsite_enabled'             => false,
            'onsite_event_title'         => 'Dorf- & Hof-Flohmarkt',
            'onsite_event_date'          => '',
            'onsite_start_time'          => '09:00',
            'onsite_end_time'            => '16:00',
            'onsite_setup_time'          => '07:30',
            'onsite_teardown_time'       => '17:00',
            'onsite_location'            => '',
            'onsite_fee_info'            => '5 € Standgebühr / Kuchenspende',
            'onsite_max_stands'          => 30,
            'onsite_waitlist_enabled'    => true,
            'onsite_map_image'           => '',
            // Phase 40.3: Ablauf-Automatik & DSGVO-Bereinigung
            'cron_token'                 => $generateSecret ? bin2hex(random_bytes(16)) : '',
            'expiry_reminder_days'       => 3,
            'expiry_reminder_enabled'    => true,
            'retention_days_expired'     => 30,
            'retention_days_inquiries'   => 90,
            'last_lifecycle_run'         => 0,
        ];
    }

    public static function getDefaultCategories(): array {
        return [
            ['id' => 'cat_furniture',    'slug' => 'moebel-wohnen',         'name' => 'Möbel & Wohnen',          'icon' => 'chair',          'description' => 'Stühle, Tische, Regale & Wohnaccessoires',    'sort_order' => 10],
            ['id' => 'cat_electronics',  'slug' => 'elektronik-technik',    'name' => 'Elektronik & Technik',    'icon' => 'devices',        'description' => 'Geräte, Zubehör, Audio & Computer',           'sort_order' => 20],
            ['id' => 'cat_clothing',     'slug' => 'kleidung-kindersachen', 'name' => 'Kleidung & Kindersachen', 'icon' => 'checkroom',      'description' => 'Bekleidung, Schuhe & Babyausstattung',        'sort_order' => 30],
            ['id' => 'cat_media',        'slug' => 'buecher-spiele-medien', 'name' => 'Bücher, Spiele & Medien', 'icon' => 'menu_book',      'description' => 'Romane, Sachbücher, Brettspiele & Musik',     'sort_order' => 40],
            ['id' => 'cat_garden',       'slug' => 'haus-werkzeug-garten',  'name' => 'Haus, Werkzeug & Garten', 'icon' => 'handyman',       'description' => 'Werkzeuge, Gartengeräte & Haushalt',          'sort_order' => 50],
            ['id' => 'cat_collectibles', 'slug' => 'sammlerstuecke',        'name' => 'Sammlerstücke & Raritäten','icon' => 'auto_awesome',  'description' => 'Antiquitäten, Handwerk & Besonderes',         'sort_order' => 60],
            ['id' => 'cat_free',         'slug' => 'zu-verschenken',        'name' => 'Zu verschenken',          'icon' => 'redeem',         'description' => 'Kostenlos zur Abholung oder gegen Tausch',    'sort_order' => 70],
        ];
    }

    public static function getDefaultStore(): array {
        return [
            'config'          => self::getDefaultConfig(true),
            'counters'        => [],
            'categories'      => self::getDefaultCategories(),
            'items'           => [],
            'inquiries'       => [],
            'stands'          => [],
            'reports'         => [],
            'blocked_sellers' => [],
            'audit_log'       => [],
        ];
    }

    public static function getDemoSampleStore(): array {
        $store = self::getDefaultStore();
        $store['config']['onsite_enabled'] = true;
        $store['config']['onsite_event_date'] = date('Y-m-d', strtotime('+14 days'));
        $store['config']['onsite_location'] = 'Dorfplatz / Bürgerhof';
        $y = date('Y');
        $exp = date('Y-m-d H:i:s', strtotime('+25 days'));
        $now = date('Y-m-d H:i:s');
        $store['counters'][$y] = 3;
        $store['items'] = [
            [
                'id' => 'demo_fm_1', 'item_number' => "FM-{$y}-0001", 'slug' => 'vintage-kaffeehausstuhl',
                'title' => 'Vintage-Kaffeehausstuhl (Bugholz)', 'description' => 'Gut erhaltener Klassiker mit Wiener Geflecht, ideal fürs Esszimmer.',
                'category_id' => 'cat_furniture', 'ad_type' => 'offer', 'price_model' => 'negotiable', 'price' => 45.0,
                'currency' => 'EUR', 'condition' => 'good', 'delivery_mode' => 'pickup', 'shipping_cost' => 0.0,
                'location_zip' => '80331', 'location_city' => 'München', 'location_district' => 'Altstadt',
                'quantity' => 1, 'images' => [], 'seller_name' => 'Demo-Anbieter', 'seller_id' => 'admin',
                'seller_email' => '', 'seller_verified' => true, 'email_confirmed' => true,
                'confirm_token' => '', 'manage_token' => 'demo_tok_1', 'stand_number' => '#12',
                'reminder_sent_at' => '', 'extended_count' => 0, 'status' => 'active', 'views' => 18,
                'created_at' => $now, 'updated_at' => $now, 'expires_at' => $exp,
            ],
            [
                'id' => 'demo_fm_2', 'item_number' => "FM-{$y}-0002", 'slug' => 'kinderfahrrad-16-zoll',
                'title' => 'Kinderfahrrad 16 Zoll mit Klingel', 'description' => 'Wenig gefahren, Reifen und Bremsen frisch geprüft.',
                'category_id' => 'cat_clothing', 'ad_type' => 'offer', 'price_model' => 'fixed', 'price' => 65.0,
                'currency' => 'EUR', 'condition' => 'like_new', 'delivery_mode' => 'pickup', 'shipping_cost' => 0.0,
                'location_zip' => '80331', 'location_city' => 'München', 'location_district' => '',
                'quantity' => 1, 'images' => [], 'seller_name' => 'Familie Weber', 'seller_id' => 'admin',
                'seller_email' => '', 'seller_verified' => true, 'email_confirmed' => true,
                'confirm_token' => '', 'manage_token' => 'demo_tok_2', 'stand_number' => '#4',
                'reminder_sent_at' => '', 'extended_count' => 0, 'status' => 'reserved', 'views' => 27,
                'created_at' => $now, 'updated_at' => $now, 'expires_at' => $exp,
            ],
            [
                'id' => 'demo_fm_3', 'item_number' => "FM-{$y}-0003", 'slug' => 'brettspiel-sammlung-gratis',
                'title' => 'Kiste mit 6 Klassiker-Brettspielen', 'description' => 'Vollständig, ideal für den nächsten Spieleabend – kostenlos abzuholen.',
                'category_id' => 'cat_media', 'ad_type' => 'gift', 'price_model' => 'free', 'price' => 0.0,
                'currency' => 'EUR', 'condition' => 'good', 'delivery_mode' => 'pickup', 'shipping_cost' => 0.0,
                'location_zip' => '80331', 'location_city' => 'München', 'location_district' => '',
                'quantity' => 1, 'images' => [], 'seller_name' => 'Demo-Anbieter', 'seller_id' => 'admin',
                'seller_email' => '', 'seller_verified' => false, 'email_confirmed' => true,
                'confirm_token' => '', 'manage_token' => 'demo_tok_3', 'stand_number' => '',
                'reminder_sent_at' => '', 'extended_count' => 0, 'status' => 'active', 'views' => 9,
                'created_at' => $now, 'updated_at' => $now, 'expires_at' => $exp,
            ],
        ];
        return $store;
    }

    protected static function isSandboxDemo(): bool {
        return self::$testStorageDir === null && class_exists('SandboxManager') && SandboxManager::isDemoActive();
    }

    protected static function normalizeStoreStructure(array $loaded, bool &$needsSave): array {
        $loaded['config'] = array_merge(self::getDefaultConfig(false), is_array($loaded['config'] ?? null) ? $loaded['config'] : []);
        if (empty($loaded['config']['token_secret'])) {
            $loaded['config']['token_secret'] = bin2hex(random_bytes(32));
            $needsSave = true;
        }
        if (empty($loaded['config']['cron_token'])) {
            $loaded['config']['cron_token'] = bin2hex(random_bytes(16));
            $needsSave = true;
        }
        foreach (['counters', 'items', 'inquiries', 'stands', 'reports', 'blocked_sellers', 'audit_log'] as $arrKey) {
            if (!isset($loaded[$arrKey]) || !is_array($loaded[$arrKey])) {
                $loaded[$arrKey] = [];
            }
        }
        if (!isset($loaded['categories']) || !is_array($loaded['categories'])) {
            $loaded['categories'] = self::getDefaultCategories();
        }
        return $loaded;
    }

    public static function getStore(): array {
        if (self::isSandboxDemo()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                if (empty($_SESSION['weave_fm_sandbox_store']) || !is_array($_SESSION['weave_fm_sandbox_store'])) {
                    $_SESSION['weave_fm_sandbox_store'] = self::getDemoSampleStore();
                }
                $dummy = false;
                self::$storeCache = self::normalizeStoreStructure($_SESSION['weave_fm_sandbox_store'], $dummy);
                $_SESSION['weave_fm_sandbox_store'] = self::$storeCache;
                return self::$storeCache;
            }
            if (self::$storeCache === null) {
                self::$storeCache = self::getDemoSampleStore();
            }
            return self::$storeCache;
        }

        if (self::$storeCache !== null) {
            return self::$storeCache;
        }
        $file = self::getDataDir() . DIRECTORY_SEPARATOR . 'fleamarket_data.json';
        $loaded = null;

        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            $loaded = StorageManager::loadPluginData('fleamarket_data', null, $file);
        } elseif (file_exists($file)) {
            $loaded = json_decode((string)@file_get_contents($file), true);
        }

        if (!is_array($loaded)) {
            $default = self::getDefaultStore();
            self::saveStore($default);
            return self::$storeCache ?? $default;
        }

        $needsSave = false;
        $loaded = self::normalizeStoreStructure($loaded, $needsSave);

        self::$storeCache = $loaded;
        if ($needsSave) {
            self::saveStore($loaded);
        }
        return self::$storeCache;
    }

    public static function saveStore(array $store): bool {
        if (self::isSandboxDemo()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['weave_fm_sandbox_store'] = $store;
            }
            self::$storeCache = $store;
            return true;
        }

        $file = self::getDataDir() . DIRECTORY_SEPARATOR . 'fleamarket_data.json';
        if (self::$testStorageDir === null && class_exists('StorageManager')) {
            $ok = StorageManager::savePluginData('fleamarket_data', $store, $file);
        } else {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }
            $tmp = $file . '.tmp.' . bin2hex(random_bytes(5));
            if (@file_put_contents($tmp, $json) === false) {
                return false;
            }
            $ok = @rename($tmp, $file);
        }
        if ($ok) {
            self::$storeCache = $store;
        }
        return $ok;
    }

    public static function getConfig(): array {
        return self::getStore()['config'];
    }

    public static function saveConfig(array $input): bool {
        $store = self::getStore();
        $cfg = $store['config'];

        $allowedPlace = ['page', 'home', 'both'];
        $rawPlace = (string)($input['placement'] ?? ($cfg['placement'] ?? 'page'));
        $placement = in_array($rawPlace, $allowedPlace, true) ? $rawPlace : 'page';

        $allowedLayouts = ['grid', 'list', 'compact'];
        $rawLayout = (string)($input['default_layout'] ?? ($cfg['default_layout'] ?? 'grid'));
        $layout = in_array($rawLayout, $allowedLayouts, true) ? $rawLayout : 'grid';

        $allowedIcons = ['local_offer', 'storefront', 'redeem', 'shopping_bag', 'sell'];
        $rawIcon = trim((string)($input['nav_icon'] ?? ($cfg['nav_icon'] ?? 'local_offer')));
        $icon = in_array($rawIcon, $allowedIcons, true) ? $rawIcon : 'local_offer';

        $rawCur = (string)($input['currency'] ?? ($cfg['currency'] ?? 'EUR'));
        $currency = in_array($rawCur, ['EUR', 'CHF', 'USD'], true) ? $rawCur : 'EUR';

        $allowedModes = ['admin_only', 'members', 'guest_moderated'];
        $rawMode = (string)($input['submission_mode'] ?? ($cfg['submission_mode'] ?? 'admin_only'));
        $subMode = in_array($rawMode, $allowedModes, true) ? $rawMode : 'admin_only';

        $rawDur = (int)($input['default_duration_days'] ?? ($cfg['default_duration_days'] ?? 30));
        $durDays = in_array($rawDur, [14, 30, 60, 90], true) ? $rawDur : 30;

        $boolOpt = static fn(string $key, bool $fallback): bool => array_key_exists($key, $input)
            ? (!empty($input[$key]) && $input[$key] !== '0' && $input[$key] !== 'false')
            : $fallback;

        $eventDateRaw = trim((string)($input['onsite_event_date'] ?? ($cfg['onsite_event_date'] ?? '')));
        $eventDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDateRaw) ? $eventDateRaw : '';

        $timeOpt = static function (string $key, string $fallback) use ($input, $cfg): string {
            $val = trim((string)($input[$key] ?? ($cfg[$key] ?? $fallback)));
            return preg_match('/^\d{2}:\d{2}$/', $val) ? $val : $fallback;
        };

        $store['config'] = array_merge($cfg, [
            'section_title'              => trim((string)($input['section_title'] ?? ($cfg['section_title'] ?? 'Flohmarkt & Kleinanzeigen'))) ?: 'Flohmarkt & Kleinanzeigen',
            'section_subtitle'           => trim((string)($input['section_subtitle'] ?? ($cfg['section_subtitle'] ?? ''))),
            'show_in_main_nav'           => $boolOpt('show_in_main_nav', !empty($cfg['show_in_main_nav'])),
            'nav_label'                  => trim((string)($input['nav_label'] ?? ($cfg['nav_label'] ?? 'Flohmarkt'))) ?: 'Flohmarkt',
            'nav_icon'                   => $icon,
            'placement'                  => $placement,
            'default_layout'             => $layout,
            'items_per_page'             => max(4, min(60, (int)($input['items_per_page'] ?? ($cfg['items_per_page'] ?? 12)))),
            'currency'                   => $currency,
            'submission_mode'            => $subMode,
            'require_approval'           => $boolOpt('require_approval', !empty($cfg['require_approval'])),
            'max_items_per_seller'       => max(1, min(200, (int)($input['max_items_per_seller'] ?? ($cfg['max_items_per_seller'] ?? 10)))),
            'max_images_per_item'        => max(1, min(12, (int)($input['max_images_per_item'] ?? ($cfg['max_images_per_item'] ?? 6)))),
            'default_duration_days'      => $durDays,
            'inquiry_rate_limit_seconds' => max(0, min(3600, (int)($input['inquiry_rate_limit_seconds'] ?? ($cfg['inquiry_rate_limit_seconds'] ?? 60)))),
            // Phase 40.1: Event- & Vor-Ort-Modus
            'onsite_enabled'             => $boolOpt('onsite_enabled', !empty($cfg['onsite_enabled'])),
            'onsite_event_title'         => trim((string)($input['onsite_event_title'] ?? ($cfg['onsite_event_title'] ?? 'Dorf- & Hof-Flohmarkt'))) ?: 'Dorf- & Hof-Flohmarkt',
            'onsite_event_date'          => $eventDate,
            'onsite_start_time'          => $timeOpt('onsite_start_time', '09:00'),
            'onsite_end_time'            => $timeOpt('onsite_end_time', '16:00'),
            'onsite_setup_time'          => $timeOpt('onsite_setup_time', '07:30'),
            'onsite_teardown_time'       => $timeOpt('onsite_teardown_time', '17:00'),
            'onsite_location'            => trim((string)($input['onsite_location'] ?? ($cfg['onsite_location'] ?? ''))),
            'onsite_fee_info'            => trim((string)($input['onsite_fee_info'] ?? ($cfg['onsite_fee_info'] ?? ''))),
            'onsite_max_stands'          => max(1, min(500, (int)($input['onsite_max_stands'] ?? ($cfg['onsite_max_stands'] ?? 30)))),
            'onsite_waitlist_enabled'    => $boolOpt('onsite_waitlist_enabled', !empty($cfg['onsite_waitlist_enabled'])),
            'onsite_map_image'           => trim((string)($input['onsite_map_image'] ?? ($cfg['onsite_map_image'] ?? ''))),
            // Phase 40.3: Ablauf-Automatik & DSGVO
            'expiry_reminder_days'       => max(1, min(30, (int)($input['expiry_reminder_days'] ?? ($cfg['expiry_reminder_days'] ?? 3)))),
            'expiry_reminder_enabled'    => $boolOpt('expiry_reminder_enabled', !empty($cfg['expiry_reminder_enabled'])),
            'retention_days_expired'     => max(1, min(365, (int)($input['retention_days_expired'] ?? ($cfg['retention_days_expired'] ?? 30)))),
            'retention_days_inquiries'   => max(1, min(730, (int)($input['retention_days_inquiries'] ?? ($cfg['retention_days_inquiries'] ?? 90)))),
        ]);
        return self::saveStore($store);
    }

    public static function getLabels(): array {
        $t = static fn(string $key, string $default): string => function_exists('__') ? __($key, $default) : $default;
        return [
            'ad_types' => [
                'offer'  => $t('fleamarket.type_offer', 'Angebot / Biete'),
                'wanted' => $t('fleamarket.type_wanted', 'Gesuch / Suche'),
                'swap'   => $t('fleamarket.type_swap', 'Tausch'),
                'gift'   => $t('fleamarket.type_gift', 'Zu verschenken'),
            ],
            'price_models' => [
                'fixed'      => $t('fleamarket.price_fixed', 'Festpreis'),
                'negotiable' => $t('fleamarket.price_negotiable', 'Verhandlungsbasis (VB)'),
                'free'       => $t('fleamarket.price_free', 'Zu verschenken'),
                'on_request' => $t('fleamarket.price_on_request', 'Auf Anfrage'),
            ],
            'conditions' => [
                'new'      => $t('fleamarket.cond_new', 'Neu / OVP'),
                'like_new' => $t('fleamarket.cond_like_new', 'Wie neu'),
                'good'     => $t('fleamarket.cond_good', 'Sehr gut / Gut erhalten'),
                'used'     => $t('fleamarket.cond_used', 'Sichtbare Gebrauchsspuren'),
                'defect'   => $t('fleamarket.cond_defect', 'Defekt / Für Bastler'),
            ],
            'delivery_modes' => [
                'pickup'   => $t('fleamarket.deliv_pickup', 'Nur Abholung'),
                'shipping' => $t('fleamarket.deliv_shipping', 'Versand möglich'),
                'both'     => $t('fleamarket.deliv_both', 'Abholung & Versand'),
            ],
            'statuses' => [
                'draft'    => ['label' => $t('fleamarket.status_draft', 'Entwurf'),             'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.12)'],
                'pending'  => ['label' => $t('fleamarket.status_pending', 'Wartet auf Freigabe'),'color' => '#d97706', 'bg' => 'rgba(245,158,11,0.12)'],
                'active'   => ['label' => $t('fleamarket.status_active', 'Verfügbar'),           'color' => '#059669', 'bg' => 'rgba(16,185,129,0.12)'],
                'reserved' => ['label' => $t('fleamarket.status_reserved', 'Reserviert'),        'color' => '#ea580c', 'bg' => 'rgba(234,88,12,0.14)'],
                'sold'     => ['label' => $t('fleamarket.status_sold', 'Verkauft / Vergeben'),   'color' => '#dc2626', 'bg' => 'rgba(239,68,68,0.12)'],
                'expired'  => ['label' => $t('fleamarket.status_expired', 'Abgelaufen'),         'color' => '#6b7280', 'bg' => 'rgba(107,114,128,0.14)'],
                'archived' => ['label' => $t('fleamarket.status_archived', 'Archiviert'),        'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.14)'],
            ],
        ];
    }

    public static function slugify(string $text): string {
        $s = mb_strtolower(trim($text), 'UTF-8');
        $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $s = (string)preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim($s, '-');
        return substr($s !== '' ? $s : 'artikel', 0, 64);
    }

    // =========================================================================
    // KATEGORIEN CRUD
    // =========================================================================

    public static function getCategories(): array {
        $cats = array_values(self::getStore()['categories'] ?? []);
        usort($cats, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 100)) <=> ((int)($b['sort_order'] ?? 100)));
        return $cats;
    }

    public static function saveCategory(array $input): array {
        $store = self::getStore();
        $id = trim((string)($input['id'] ?? ''));
        if ($id === '') {
            $id = 'cat_' . bin2hex(random_bytes(4));
        }
        $name = trim((string)($input['name'] ?? 'Kategorie')) ?: 'Kategorie';
        $slug = self::slugify((string)($input['slug'] ?? '') ?: $name);
        $cat = [
            'id'          => $id,
            'slug'        => $slug,
            'name'        => $name,
            'icon'        => preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)($input['icon'] ?? 'local_offer')))) ?: 'local_offer',
            'description' => trim((string)($input['description'] ?? '')),
            'sort_order'  => (int)($input['sort_order'] ?? 50),
        ];

        $updated = false;
        foreach ($store['categories'] as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c = $cat;
                $updated = true;
                break;
            }
        }
        unset($c);
        if (!$updated) {
            $store['categories'][] = $cat;
        }
        self::saveStore($store);
        return $cat;
    }

    public static function deleteCategory(string $id): bool {
        $store = self::getStore();
        $before = count($store['categories']);
        $store['categories'] = array_values(array_filter($store['categories'], static fn(array $c): bool => ($c['id'] ?? '') !== $id));
        if (count($store['categories']) === $before) {
            return false;
        }
        return self::saveStore($store);
    }

    // =========================================================================
    // ANZEIGEN CRUD & NUMMERNKREIS (FM-YYYY-NNNN)
    // =========================================================================

    public static function getItems(): array {
        return array_values(self::getStore()['items'] ?? []);
    }

    public static function getItem(string $idOrSlug): ?array {
        $needle = trim($idOrSlug);
        if ($needle === '') {
            return null;
        }
        foreach (self::getItems() as $it) {
            if (($it['id'] ?? '') === $needle || ($it['slug'] ?? '') === $needle || ($it['item_number'] ?? '') === $needle) {
                return $it;
            }
        }
        return null;
    }

    protected static function ensureUniqueSlug(string $baseSlug, array $items, string $ignoreId = ''): string {
        $slug = $baseSlug;
        $n = 1;
        $exists = static function (string $cand) use ($items, $ignoreId): bool {
            foreach ($items as $it) {
                if (($it['id'] ?? '') !== $ignoreId && ($it['slug'] ?? '') === $cand) {
                    return true;
                }
            }
            return false;
        };
        while ($exists($slug)) {
            $n++;
            $slug = substr($baseSlug, 0, 56) . '-' . $n;
        }
        return $slug;
    }

    protected static function parseImagesInput($raw): array {
        $out = [];
        if (is_array($raw)) {
            foreach ($raw as $img) {
                if (is_string($img)) {
                    $u = trim($img);
                    if ($u !== '') {
                        $out[] = ['url' => $u, 'thumb_url' => $u, 'focal_x' => 50, 'focal_y' => 50, 'alt' => ''];
                    }
                } elseif (is_array($img) && !empty($img['url'])) {
                    $out[] = [
                        'url'       => trim((string)$img['url']),
                        'thumb_url' => trim((string)($img['thumb_url'] ?? $img['url'])),
                        'focal_x'   => max(0, min(100, (int)($img['focal_x'] ?? 50))),
                        'focal_y'   => max(0, min(100, (int)($img['focal_y'] ?? 50))),
                        'alt'       => trim((string)($img['alt'] ?? '')),
                    ];
                }
            }
            return $out;
        }
        foreach (preg_split('/[\r\n,]+/', (string)$raw) as $line) {
            $u = trim($line);
            if ($u !== '') {
                $out[] = ['url' => $u, 'thumb_url' => $u, 'focal_x' => 50, 'focal_y' => 50, 'alt' => ''];
            }
        }
        return $out;
    }

    public static function saveItem(array $input): array {
        $store = self::getStore();
        $items = $store['items'];
        $id = trim((string)($input['id'] ?? ''));
        $existingIdx = null;
        if ($id !== '') {
            foreach ($items as $i => $it) {
                if (($it['id'] ?? '') === $id) {
                    $existingIdx = $i;
                    break;
                }
            }
        } else {
            $id = 'fm_' . bin2hex(random_bytes(5));
        }

        $existing = $existingIdx !== null ? $items[$existingIdx] : [];
        $title = trim((string)($input['title'] ?? 'Flohmarkt-Artikel')) ?: 'Flohmarkt-Artikel';
        $rawSlug = trim((string)($input['slug'] ?? '')) ?: self::slugify($title);
        $slug = self::ensureUniqueSlug(self::slugify($rawSlug), $items, $id);

        $itemNumber = trim((string)($existing['item_number'] ?? ($input['item_number'] ?? '')));
        if ($itemNumber === '') {
            $y = date('Y');
            $seq = ((int)($store['counters'][$y] ?? 0)) + 1;
            $store['counters'][$y] = $seq;
            $itemNumber = 'FM-' . $y . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        }

        $adTypeRaw = (string)($input['ad_type'] ?? 'offer');
        $adType = in_array($adTypeRaw, ['offer', 'wanted', 'swap', 'gift'], true) ? $adTypeRaw : 'offer';

        $priceModelRaw = (string)($input['price_model'] ?? ($adType === 'gift' ? 'free' : 'fixed'));
        $priceModel = in_array($priceModelRaw, ['fixed', 'negotiable', 'free', 'on_request'], true) ? $priceModelRaw : 'fixed';
        $price = ($priceModel === 'free' || $priceModel === 'on_request')
            ? 0.0
            : max(0.0, round((float)str_replace(',', '.', (string)($input['price'] ?? 0)), 2));

        $condRaw = (string)($input['condition'] ?? 'good');
        $condition = in_array($condRaw, ['new', 'like_new', 'good', 'used', 'defect'], true) ? $condRaw : 'good';

        $delivRaw = (string)($input['delivery_mode'] ?? 'pickup');
        $delivery = in_array($delivRaw, ['pickup', 'shipping', 'both'], true) ? $delivRaw : 'pickup';
        $shipCost = $delivery === 'pickup' ? 0.0 : max(0.0, round((float)str_replace(',', '.', (string)($input['shipping_cost'] ?? 0)), 2));

        $statusRaw = (string)($input['status'] ?? 'active');
        $status = in_array($statusRaw, ['draft', 'pending', 'active', 'reserved', 'sold', 'expired', 'archived'], true) ? $statusRaw : 'active';

        $nowStr = date('Y-m-d H:i:s');
        $durDays = max(1, (int)($store['config']['default_duration_days'] ?? 30));
        $expRaw = trim((string)($input['expires_at'] ?? ''));
        $expiresAt = $expRaw !== '' && ($ts = strtotime($expRaw)) !== false
            ? date('Y-m-d H:i:s', $ts)
            : (string)($existing['expires_at'] ?? date('Y-m-d H:i:s', strtotime("+{$durDays} days")));

        $images = array_key_exists('images', $input)
            ? self::parseImagesInput($input['images'])
            : (array)($existing['images'] ?? []);

        $sellerEmailRaw = trim((string)($input['seller_email'] ?? ($existing['seller_email'] ?? '')));
        $sellerEmail = ($sellerEmailRaw !== '' && filter_var($sellerEmailRaw, FILTER_VALIDATE_EMAIL)) ? strtolower($sellerEmailRaw) : '';
        $manageToken = (string)($existing['manage_token'] ?? ($input['manage_token'] ?? ''));
        if ($manageToken === '') {
            $manageToken = hash_hmac('sha256', $id . '|' . bin2hex(random_bytes(8)), (string)($store['config']['token_secret'] ?? 'wfm_secret'));
        }

        $item = [
            'id'                => $id,
            'item_number'       => $itemNumber,
            'slug'              => $slug,
            'title'             => $title,
            'description'       => trim((string)($input['description'] ?? '')),
            'category_id'       => trim((string)($input['category_id'] ?? ($store['categories'][0]['id'] ?? 'cat_furniture'))),
            'ad_type'           => $adType,
            'price_model'       => $priceModel,
            'price'             => $price,
            'currency'          => (string)($input['currency'] ?? ($store['config']['currency'] ?? 'EUR')),
            'condition'         => $condition,
            'delivery_mode'     => $delivery,
            'shipping_cost'     => $shipCost,
            'location_zip'      => preg_replace('/[^a-zA-Z0-9 -]/', '', trim((string)($input['location_zip'] ?? ''))),
            'location_city'     => trim((string)($input['location_city'] ?? '')),
            'location_district' => trim((string)($input['location_district'] ?? '')),
            'quantity'          => max(1, (int)($input['quantity'] ?? 1)),
            'images'            => $images,
            'seller_name'       => trim((string)($input['seller_name'] ?? ($existing['seller_name'] ?? 'Anbieter'))),
            'seller_id'         => trim((string)($input['seller_id'] ?? ($existing['seller_id'] ?? 'admin'))),
            'seller_email'      => $sellerEmail,
            'seller_verified'   => array_key_exists('seller_verified', $input) ? !empty($input['seller_verified']) : !empty($existing['seller_verified']),
            'email_confirmed'   => array_key_exists('email_confirmed', $input) ? !empty($input['email_confirmed']) : ($existingIdx !== null ? !empty($existing['email_confirmed']) : true),
            'confirm_token'     => (string)($input['confirm_token'] ?? ($existing['confirm_token'] ?? '')),
            'manage_token'      => $manageToken,
            'stand_number'      => trim((string)($input['stand_number'] ?? ($existing['stand_number'] ?? ''))),
            'reminder_sent_at'  => (string)($input['reminder_sent_at'] ?? ($existing['reminder_sent_at'] ?? '')),
            'extended_count'    => (int)($existing['extended_count'] ?? 0),
            'status'            => $status,
            'views'             => (int)($existing['views'] ?? 0),
            'created_at'        => (string)($existing['created_at'] ?? $nowStr),
            'updated_at'        => $nowStr,
            'expires_at'        => $expiresAt,
        ];

        $becameActive = ($status === 'active') && (($existing['status'] ?? '') !== 'active');

        if ($existingIdx !== null) {
            $store['items'][$existingIdx] = $item;
        } else {
            array_unshift($store['items'], $item);
        }
        self::saveStore($store);

        if ($becameActive) {
            self::dispatchWebhook('fleamarket.item_published', [
                'id'          => $item['id'],
                'item_number' => $item['item_number'],
                'title'       => $item['title'],
                'slug'        => $item['slug'],
                'price'       => $item['price'],
                'currency'    => $item['currency'],
            ]);
        }
        return $item;
    }

    public static function deleteItem(string $id): bool {
        $store = self::getStore();
        $before = count($store['items']);
        $store['items'] = array_values(array_filter($store['items'], static fn(array $it): bool => ($it['id'] ?? '') !== $id));
        if (count($store['items']) === $before) {
            return false;
        }
        return self::saveStore($store);
    }

    public static function setItemStatus(string $id, string $status): bool {
        if (!in_array($status, ['draft', 'pending', 'active', 'reserved', 'sold', 'expired', 'archived'], true)) {
            return false;
        }
        $store = self::getStore();
        $changed = false;
        foreach ($store['items'] as &$it) {
            if (($it['id'] ?? '') === $id) {
                $it['status'] = $status;
                $it['updated_at'] = date('Y-m-d H:i:s');
                $changed = true;
                break;
            }
        }
        unset($it);
        return $changed ? self::saveStore($store) : false;
    }

    public static function incrementViews(string $itemId): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION['weave_fm_viewed'][$itemId])) {
                return;
            }
            $_SESSION['weave_fm_viewed'][$itemId] = true;
        }
        $store = self::getStore();
        $changed = false;
        foreach ($store['items'] as &$it) {
            if (($it['id'] ?? '') === $itemId) {
                $it['views'] = ((int)($it['views'] ?? 0)) + 1;
                $changed = true;
                break;
            }
        }
        unset($it);
        if ($changed) {
            self::saveStore($store);
        }
    }

    // =========================================================================
    // FILTER, SUCHE, SORTIERUNG & PAGINIERUNG
    // =========================================================================

    public static function filterItems(array $filters = [], int $page = 1, int $perPage = 12): array {
        $all = self::getItems();
        $includeAll = !empty($filters['include_all_statuses']);
        $hideReserved = !empty($filters['hide_reserved']);
        $q = mb_strtolower(trim((string)($filters['q'] ?? '')), 'UTF-8');
        $cat = trim((string)($filters['category'] ?? ''));
        $type = trim((string)($filters['type'] ?? ''));
        $cond = trim((string)($filters['condition'] ?? ''));
        $pMin = isset($filters['price_min']) && $filters['price_min'] !== '' ? (float)$filters['price_min'] : null;
        $pMax = isset($filters['price_max']) && $filters['price_max'] !== '' ? (float)$filters['price_max'] : null;
        $onlyVb = !empty($filters['only_vb']);
        $onlyFree = !empty($filters['only_free']);
        $delivery = trim((string)($filters['delivery'] ?? ''));
        $loc = mb_strtolower(trim((string)($filters['location'] ?? '')), 'UTF-8');
        $sellerId = trim((string)($filters['seller_id'] ?? ''));

        $matched = [];
        foreach ($all as $it) {
            $st = (string)($it['status'] ?? 'draft');
            if (!$includeAll) {
                if (!in_array($st, ['active', 'reserved', 'sold'], true)) {
                    continue;
                }
                if ($hideReserved && $st === 'reserved') {
                    continue;
                }
            }
            if ($cat !== '' && ($it['category_id'] ?? '') !== $cat) {
                continue;
            }
            if ($type !== '' && ($it['ad_type'] ?? '') !== $type) {
                continue;
            }
            if ($cond !== '' && ($it['condition'] ?? '') !== $cond) {
                continue;
            }
            if ($sellerId !== '' && ($it['seller_id'] ?? '') !== $sellerId) {
                continue;
            }
            if ($onlyVb && ($it['price_model'] ?? '') !== 'negotiable') {
                continue;
            }
            if ($onlyFree && ($it['price_model'] ?? '') !== 'free') {
                continue;
            }
            if ($pMin !== null && (float)($it['price'] ?? 0) < $pMin) {
                continue;
            }
            if ($pMax !== null && (float)($it['price'] ?? 0) > $pMax) {
                continue;
            }
            if ($delivery === 'pickup' && ($it['delivery_mode'] ?? 'pickup') === 'shipping') {
                continue;
            }
            if ($delivery === 'shipping' && !in_array($it['delivery_mode'] ?? 'pickup', ['shipping', 'both'], true)) {
                continue;
            }
            if ($loc !== '') {
                $locHay = mb_strtolower(($it['location_zip'] ?? '') . ' ' . ($it['location_city'] ?? '') . ' ' . ($it['location_district'] ?? ''), 'UTF-8');
                if (mb_strpos($locHay, $loc) === false) {
                    continue;
                }
            }
            if ($q !== '') {
                $hay = mb_strtolower(($it['title'] ?? '') . ' ' . ($it['description'] ?? '') . ' ' . ($it['item_number'] ?? ''), 'UTF-8');
                if (mb_strpos($hay, $q) === false) {
                    continue;
                }
            }
            $matched[] = $it;
        }

        $sort = (string)($filters['sort'] ?? 'newest');
        usort($matched, static function (array $a, array $b) use ($sort): int {
            if ($sort === 'price_asc') {
                return ((float)($a['price'] ?? 0)) <=> ((float)($b['price'] ?? 0));
            }
            if ($sort === 'price_desc') {
                return ((float)($b['price'] ?? 0)) <=> ((float)($a['price'] ?? 0));
            }
            if ($sort === 'expiring_soon') {
                return strcmp((string)($a['expires_at'] ?? '9999'), (string)($b['expires_at'] ?? '9999'));
            }
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        $total = count($matched);
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $slice = array_slice($matched, ($page - 1) * $perPage, $perPage);

        return [
            'items'       => $slice,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function formatPrice(array $item): string {
        $pm = (string)($item['price_model'] ?? 'fixed');
        $cur = ($item['currency'] ?? 'EUR') === 'EUR' ? '€' : (string)$item['currency'];
        if ($pm === 'free') {
            return 'Zu verschenken (0 ' . $cur . ')';
        }
        if ($pm === 'on_request') {
            return 'Preis auf Anfrage';
        }
        $num = number_format((float)($item['price'] ?? 0), 2, ',', '.') . ' ' . $cur;
        return $pm === 'negotiable' ? $num . ' VB' : $num;
    }

    public static function renderMarkdownSafe(string $raw): string {
        $escaped = htmlspecialchars(trim($raw), ENT_QUOTES, 'UTF-8');
        if ($escaped === '') {
            return '';
        }
        $escaped = preg_replace('/\*\*([^\r\n]+?)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/(?<!\*)\*(?!\s)([^\r\n\*]+?)(?<!\s)\*(?!\*)/', '<em>$1</em>', $escaped);
        $escaped = preg_replace_callback('/(?:^[ \t]*[-*][ \t]+[^\r\n]+(?:\r?\n|$))+/m', static function (array $m): string {
            $lines = preg_split('/\r?\n/', trim($m[0]));
            $li = '';
            foreach ($lines as $ln) {
                $li .= '<li>' . preg_replace('/^[ \t]*[-*][ \t]+/', '', $ln) . '</li>';
            }
            return '<ul style="margin:0.5rem 0 0.5rem 1.25rem;">' . $li . '</ul>';
        }, $escaped);
        return nl2br($escaped);
    }

    public static function buildItemJsonLd(array $item, string $url): string {
        $status = (string)($item['status'] ?? 'active');
        $availMap = [
            'active'   => 'https://schema.org/InStock',
            'reserved' => 'https://schema.org/LimitedAvailability',
            'sold'     => 'https://schema.org/OutOfStock',
        ];
        $condMap = [
            'new'      => 'https://schema.org/NewCondition',
            'like_new' => 'https://schema.org/UsedCondition',
            'good'     => 'https://schema.org/UsedCondition',
            'used'     => 'https://schema.org/UsedCondition',
            'defect'   => 'https://schema.org/DamagedCondition',
        ];
        $imgs = array_values(array_filter(array_map(static fn(array $im): string => (string)($im['url'] ?? ''), (array)($item['images'] ?? []))));
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string)($item['title'] ?? ''),
            'description' => mb_substr(strip_tags((string)($item['description'] ?? '')), 0, 300),
            'sku'         => (string)($item['item_number'] ?? ''),
            'image'       => $imgs,
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $url,
                'priceCurrency' => (string)($item['currency'] ?? 'EUR'),
                'price'         => number_format((float)($item['price'] ?? 0), 2, '.', ''),
                'availability'  => $availMap[$status] ?? 'https://schema.org/InStock',
                'itemCondition' => $condMap[(string)($item['condition'] ?? 'good')] ?? 'https://schema.org/UsedCondition',
            ],
        ];
        return (string)json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }

    // =========================================================================
    // PHASE 39: ANBIETER-MODI, EINREICHUNG, DOUBLE-OPT-IN & ANFRAGEN
    // =========================================================================

    public static function getCurrentMember(): ?array {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['admin_logged_in'])) {
            return [
                'id'       => 'admin',
                'name'     => (string)($_SESSION['admin_username'] ?? 'Administrator'),
                'email'    => '',
                'is_admin' => true,
            ];
        }
        if (class_exists('ForumUserManager') && method_exists('ForumUserManager', 'getCurrentUser')) {
            $fu = ForumUserManager::getCurrentUser();
            if (is_array($fu) && !empty($fu['id'])) {
                return [
                    'id'       => 'member:' . (string)$fu['id'],
                    'name'     => ForumUserManager::getDisplayName($fu, (string)($fu['username'] ?? 'Mitglied')),
                    'email'    => (string)($fu['email'] ?? ''),
                    'is_admin' => in_array((string)($fu['role'] ?? ''), ['forum_admin', 'forum_moderator'], true),
                ];
            }
        }
        return null;
    }

    public static function canSubmitFrontend(?array $member = null): bool {
        $cfg = self::getConfig();
        $mode = (string)($cfg['submission_mode'] ?? 'admin_only');
        if ($member && !empty($member['is_admin'])) {
            return true;
        }
        if ($mode === 'members') {
            return $member !== null && !empty($member['id']);
        }
        if ($mode === 'guest_moderated') {
            return true;
        }
        return false;
    }

    public static function countActiveItemsBySeller(string $sellerId): int {
        if ($sellerId === '') {
            return 0;
        }
        $count = 0;
        foreach (self::getItems() as $it) {
            if (($it['seller_id'] ?? '') === $sellerId && in_array($it['status'] ?? '', ['active', 'pending', 'reserved'], true)) {
                $count++;
            }
        }
        return $count;
    }

    public static function submitFrontendItem(array $input, ?array $member = null, ?callable $mailSender = null): array {
        $cfg = self::getConfig();
        $mode = (string)($cfg['submission_mode'] ?? 'admin_only');
        if (!self::canSubmitFrontend($member)) {
            return ['success' => false, 'message' => 'Die Frontend-Anzeigenaufgabe ist in der aktuellen Betriebsart nicht freigeschaltet.'];
        }
        if (!empty($input['website_hp'])) {
            return ['success' => false, 'message' => 'Spamschutz ausgelöst.'];
        }

        $title = trim((string)($input['title'] ?? ''));
        if (mb_strlen($title) < 3) {
            return ['success' => false, 'message' => 'Bitte gib einen aussagekräftigen Titel (mind. 3 Zeichen) an.'];
        }

        $isMember = ($member !== null && !empty($member['id']));
        $sellerEmail = $isMember && !empty($member['email'])
            ? (string)$member['email']
            : trim((string)($input['seller_email'] ?? ''));

        if ($mode === 'guest_moderated' && !$isMember && !filter_var($sellerEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Für Gast-Anzeigen ist eine gültige E-Mail-Adresse zur Bestätigung erforderlich.'];
        }

        $sellerId = $isMember ? (string)$member['id'] : ('guest_' . hash('sha256', strtolower($sellerEmail)));
        if (self::isSellerBlocked($sellerId, $sellerEmail)) {
            return ['success' => false, 'message' => 'Dieser Anbieter ist für neue Flohmarkt-Anzeigen gesperrt.'];
        }
        $maxItems = max(1, (int)($cfg['max_items_per_seller'] ?? 10));
        if ((!$member || empty($member['is_admin'])) && self::countActiveItemsBySeller($sellerId) >= $maxItems) {
            return ['success' => false, 'message' => "Anzeigen-Kontingent erreicht (max. {$maxItems} aktive Anzeigen je Anbieter)."];
        }

        $requireApproval = !empty($cfg['require_approval']);
        $isGuestFlow = (!$isMember);
        $confirmToken = $isGuestFlow ? bin2hex(random_bytes(16)) : '';
        $status = ($isGuestFlow || ($requireApproval && empty($member['is_admin']))) ? 'pending' : 'active';

        $savePayload = $input;
        $savePayload['seller_id'] = $sellerId;
        $savePayload['seller_name'] = $isMember ? (string)$member['name'] : (trim((string)($input['seller_name'] ?? 'Privatanbieter')) ?: 'Privatanbieter');
        $savePayload['seller_email'] = $sellerEmail;
        $savePayload['email_confirmed'] = !$isGuestFlow;
        $savePayload['confirm_token'] = $confirmToken;
        $savePayload['status'] = $status;

        $saved = self::saveItem($savePayload);

        if ($isGuestFlow && filter_var($sellerEmail, FILTER_VALIDATE_EMAIL)) {
            $baseUrl = class_exists('SecurityManager') ? rtrim(SecurityManager::getBaseUrl(), '/') : 'http://localhost';
            $confirmUrl = $baseUrl . '/?page=flohmarkt&confirm_ad=' . urlencode($confirmToken);
            $manageUrl = $baseUrl . '/?page=flohmarkt&manage=' . urlencode((string)$saved['manage_token']);
            $subj = 'Flohmarkt-Anzeige bestätigen: ' . $saved['title'] . ' (' . $saved['item_number'] . ')';
            $text = "Hallo {$saved['seller_name']},\n\nbitte bestätige deine E-Mail-Adresse für Anzeige {$saved['item_number']}:\n{$confirmUrl}\n\n"
                . "Mit diesem persönlichen Verwaltungs-Link kannst du deine Anzeige jederzeit als Reserviert/Verkauft markieren oder löschen:\n{$manageUrl}\n";
            $html = '<p>Hallo ' . htmlspecialchars((string)$saved['seller_name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>bitte bestätige deine E-Mail-Adresse für die Anzeige <strong>' . htmlspecialchars((string)$saved['item_number'], ENT_QUOTES, 'UTF-8') . '</strong>:</p>'
                . '<p><a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8') . '">Anzeige jetzt per E-Mail bestätigen</a></p>'
                . '<p>Dein persönlicher Selbstverwaltungs-Link (Reserviert / Verkauft / Löschen):</p>'
                . '<p><a href="' . htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

            if ($mailSender !== null) {
                $mailSender($sellerEmail, $subj, $html, $text);
            } elseif (!self::isSandboxDemo() && class_exists('SimpleMailer')) {
                global $data;
                $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
                SimpleMailer::send($sellerEmail, $subj, $html, $text, '', '', $smtp);
            }
        }

        return [
            'success' => true,
            'item'    => $saved,
            'message' => $isGuestFlow
                ? 'Vielen Dank! Wir haben dir einen Bestätigungs- & Verwaltungs-Link per E-Mail gesendet.'
                : ($status === 'active' ? 'Deine Anzeige ist jetzt live im Flohmarkt!' : 'Deine Anzeige wurde eingereicht und wartet auf Freigabe.'),
        ];
    }

    public static function confirmGuestItem(string $confirmToken): array {
        $tok = trim($confirmToken);
        if ($tok === '') {
            return ['success' => false, 'message' => 'Ungültiger Bestätigungs-Token.'];
        }
        $cfg = self::getConfig();
        $store = self::getStore();
        foreach ($store['items'] as &$it) {
            if (!empty($it['confirm_token']) && hash_equals((string)$it['confirm_token'], $tok)) {
                $wasActive = ($it['status'] ?? '') === 'active';
                $it['email_confirmed'] = true;
                $it['confirm_token'] = '';
                $it['updated_at'] = date('Y-m-d H:i:s');
                if (empty($cfg['require_approval'])) {
                    $it['status'] = 'active';
                }
                self::saveStore($store);
                if (!$wasActive && ($it['status'] ?? '') === 'active') {
                    self::dispatchWebhook('fleamarket.item_published', [
                        'id'          => $it['id'],
                        'item_number' => $it['item_number'],
                        'title'       => $it['title'],
                        'slug'        => $it['slug'],
                    ]);
                }
                return [
                    'success' => true,
                    'item'    => $it,
                    'message' => empty($cfg['require_approval'])
                        ? 'E-Mail bestätigt – deine Anzeige ist jetzt aktiv!'
                        : 'E-Mail bestätigt – deine Anzeige wird nach kurzer Prüfung freigegeben.',
                ];
            }
        }
        unset($it);
        return ['success' => false, 'message' => 'Bestätigungs-Link ungültig oder bereits verwendet.'];
    }

    public static function findItemByManageToken(string $manageToken): ?array {
        $tok = trim($manageToken);
        if ($tok === '') {
            return null;
        }
        foreach (self::getItems() as $it) {
            if (!empty($it['manage_token']) && hash_equals((string)$it['manage_token'], $tok)) {
                return $it;
            }
        }
        return null;
    }

    public static function applySelfServiceAction(string $manageToken, string $action): array {
        $item = self::findItemByManageToken($manageToken);
        if (!$item) {
            return ['success' => false, 'message' => 'Ungültiger Verwaltungs-Token.'];
        }
        if ($action === 'delete') {
            self::deleteItem((string)$item['id']);
            return ['success' => true, 'deleted' => true, 'message' => 'Anzeige wurde gelöscht.'];
        }
        if (in_array($action, ['active', 'reserved', 'sold', 'archived'], true)) {
            if ($action === 'active' && (empty($item['email_confirmed']) || ($item['status'] ?? '') === 'pending')) {
                return ['success' => false, 'message' => 'Diese Anzeige muss zuerst per E-Mail bestätigt bzw. durch die Moderation freigegeben werden.'];
            }
            self::setItemStatus((string)$item['id'], $action);
            return ['success' => true, 'deleted' => false, 'item' => self::getItem((string)$item['id']), 'message' => 'Anzeigenstatus wurde aktualisiert.'];
        }
        return ['success' => false, 'message' => 'Unbekannte Aktion.'];
    }

    public static function sendItemInquiry(string $itemId, array $input, ?array $smtpConfig = null, ?callable $mailSender = null): array {
        $item = self::getItem($itemId);
        if (!$item || in_array((string)($item['status'] ?? ''), ['sold', 'expired', 'archived', 'draft', 'pending'], true)) {
            return ['success' => false, 'message' => 'Für diese Anzeige sind aktuell keine Anfragen möglich.'];
        }
        if (!empty($input['website_hp'])) {
            return ['success' => false, 'message' => 'Anfrage vom Spamschutz abgelehnt.'];
        }

        $cfg = self::getConfig();
        $rl = (int)($cfg['inquiry_rate_limit_seconds'] ?? 60);
        if ($rl > 0 && session_status() === PHP_SESSION_ACTIVE) {
            $last = (int)($_SESSION['weave_fm_last_inquiry'] ?? 0);
            if (time() - $last < $rl) {
                return ['success' => false, 'message' => 'Bitte warte kurz vor der nächsten Anfrage.'];
            }
        }

        $senderName = trim((string)($input['sender_name'] ?? ''));
        $senderEmail = trim((string)($input['sender_email'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));
        if ($senderName === '' || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
            return ['success' => false, 'message' => 'Bitte gib Name, gültige E-Mail-Adresse und eine Nachricht (mind. 5 Zeichen) ein.'];
        }

        $offerPrice = null;
        if (($item['price_model'] ?? '') === 'negotiable' && trim((string)($input['offer_price'] ?? '')) !== '') {
            $offerPrice = max(0.0, round((float)str_replace(',', '.', (string)$input['offer_price']), 2));
        }
        $rawDeliv = (string)($input['delivery_pref'] ?? 'pickup');
        $delivPref = in_array($rawDeliv, ['pickup', 'shipping'], true) ? $rawDeliv : 'pickup';

        $inquiry = [
            'id'            => 'inq_' . bin2hex(random_bytes(5)),
            'item_id'       => $item['id'],
            'item_number'   => $item['item_number'],
            'item_title'    => $item['title'],
            'sender_name'   => $senderName,
            'sender_email'  => strtolower($senderEmail),
            'offer_price'   => $offerPrice,
            'delivery_pref' => $delivPref,
            'message'       => $message,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $store = self::getStore();
        array_unshift($store['inquiries'], $inquiry);
        self::saveStore($store);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['weave_fm_last_inquiry'] = time();
        }

        $targetEmail = trim((string)($item['seller_email'] ?? ''));
        if ($targetEmail === '' && !empty($smtpConfig['from_email'])) {
            $targetEmail = trim((string)$smtpConfig['from_email']);
        }
        if ($targetEmail !== '' && filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            $subj = "[{$item['item_number']}] Anfrage zu „{$item['title']}“";
            $offerLine = $offerPrice !== null ? ("Preisvorschlag: " . number_format($offerPrice, 2, ',', '.') . " €\n") : '';
            $text = "Neue Flohmarkt-Anfrage von {$senderName} ({$senderEmail}):\n{$offerLine}Präferenz: {$delivPref}\n\n{$message}\n";
            $html = '<p>Neue Anfrage zu deiner Anzeige <strong>' . htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8')
                . ' (' . htmlspecialchars((string)$item['item_number'], ENT_QUOTES, 'UTF-8') . ')</strong>:</p>'
                . '<p><strong>Absender:</strong> ' . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8')
                . ' (' . htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8') . ')<br>'
                . ($offerPrice !== null ? ('<strong>Preisvorschlag:</strong> ' . number_format($offerPrice, 2, ',', '.') . ' €<br>') : '')
                . '<strong>Übergabe-Wunsch:</strong> ' . htmlspecialchars($delivPref, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<blockquote>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</blockquote>';

            if ($mailSender !== null) {
                $mailSender($targetEmail, $subj, $html, $text, $senderEmail, $senderName);
            } elseif (!self::isSandboxDemo() && class_exists('SimpleMailer')) {
                SimpleMailer::send($targetEmail, $subj, $html, $text, $senderEmail, $senderName, $smtpConfig);
            }
        }

        self::dispatchWebhook('fleamarket.inquiry_received', [
            'inquiry_id'    => $inquiry['id'],
            'item_id'       => $item['id'],
            'item_number'   => $item['item_number'],
            'offer_price'   => $offerPrice,
            'delivery_pref' => $delivPref,
        ]);

        return ['success' => true, 'inquiry' => $inquiry, 'message' => 'Deine Anfrage wurde sicher an den Anbieter übermittelt.'];
    }

    public static function getInquiries(?string $itemId = null): array {
        $all = array_values(self::getStore()['inquiries'] ?? []);
        if ($itemId === null || $itemId === '') {
            return $all;
        }
        return array_values(array_filter($all, static fn(array $inq): bool => ($inq['item_id'] ?? '') === $itemId));
    }

    // =========================================================================
    // PHASE 40.1: VOR-ORT-STANDPLATZ-MODUS, .ICS & QR-PREISSCHILDER
    // =========================================================================

    public static function getStands(): array {
        return array_values(self::getStore()['stands'] ?? []);
    }

    public static function buildOnsiteEventIcs(array $cfg, ?array $stand = null): string {
        $dateRaw = (string)($cfg['onsite_event_date'] ?? '');
        $ymd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw) ? str_replace('-', '', $dateRaw) : date('Ymd', strtotime('+14 days'));
        $startHm = str_replace(':', '', (string)($cfg['onsite_start_time'] ?? '09:00')) . '00';
        $endHm = str_replace(':', '', (string)($cfg['onsite_end_time'] ?? '16:00')) . '00';

        $esc = static fn(string $v): string => str_replace(["\r", "\n", ',', ';'], ['', '\n', '\,', '\;'], $v);
        $title = (string)($cfg['onsite_event_title'] ?? 'Flohmarkt');
        if ($stand && !empty($stand['stand_number'])) {
            $title .= ' – Stand ' . $stand['stand_number'];
        }
        $desc = 'Aufbau ab ' . ($cfg['onsite_setup_time'] ?? '07:30') . ' Uhr, Abbau bis ' . ($cfg['onsite_teardown_time'] ?? '17:00') . ' Uhr.';
        if (!empty($cfg['onsite_fee_info'])) {
            $desc .= ' Hinweis: ' . $cfg['onsite_fee_info'];
        }
        $uid = ($stand['id'] ?? 'event') . '@weavecms-fleamarket';
        $stamp = gmdate('Ymd\THis\Z');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//WeaveCMS//Weave Fleamarket//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $stamp,
            'DTSTART:' . $ymd . 'T' . $startHm,
            'DTEND:' . $ymd . 'T' . $endHm,
            'SUMMARY:' . $esc($title),
            'LOCATION:' . $esc((string)($cfg['onsite_location'] ?? '')),
            'DESCRIPTION:' . $esc($desc),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    public static function registerStand(array $input, ?array $smtpConfig = null, ?callable $mailSender = null): array {
        $cfg = self::getConfig();
        if (empty($cfg['onsite_enabled'])) {
            return ['success' => false, 'message' => 'Die Vor-Ort-Standplatzanmeldung ist derzeit nicht aktiviert.'];
        }
        if (!empty($input['website_hp'])) {
            return ['success' => false, 'message' => 'Spamschutz ausgelöst.'];
        }

        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $meters = max(1, min(10, (int)($input['meters'] ?? 3)));
        $note = trim((string)($input['note'] ?? ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Bitte gib Name und eine gültige E-Mail-Adresse an.'];
        }

        $store = self::getStore();
        $maxStands = max(1, (int)($cfg['onsite_max_stands'] ?? 30));
        $confirmedCount = 0;
        $maxNum = 0;
        foreach ($store['stands'] as $st) {
            if (($st['status'] ?? '') === 'confirmed') {
                $confirmedCount++;
                $n = (int)ltrim((string)($st['stand_number'] ?? '#0'), '#');
                if ($n > $maxNum) {
                    $maxNum = $n;
                }
            }
        }

        $isWaitlist = $confirmedCount >= $maxStands;
        if ($isWaitlist && empty($cfg['onsite_waitlist_enabled'])) {
            return ['success' => false, 'message' => 'Alle Standplätze sind leider bereits vergeben.'];
        }

        $standNumber = $isWaitlist ? '' : ('#' . ($maxNum + 1));
        $stand = [
            'id'           => 'std_' . bin2hex(random_bytes(5)),
            'stand_number' => $standNumber,
            'name'         => $name,
            'email'        => strtolower($email),
            'phone'        => $phone,
            'meters'       => $meters,
            'note'         => $note,
            'status'       => $isWaitlist ? 'waitlist' : 'confirmed',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        array_unshift($store['stands'], $stand);
        self::saveStore($store);
        self::logAudit('STAND_REGISTERED', "Standplatz {$stand['id']} ({$stand['status']}) für {$name}", $name);

        $ics = self::buildOnsiteEventIcs($cfg, $stand);
        $subj = ($isWaitlist ? '[Warteliste] ' : '[Bestätigung] ') . ($cfg['onsite_event_title'] ?? 'Flohmarkt') . ($standNumber !== '' ? " – Stand {$standNumber}" : '');
        $text = "Hallo {$name},\n\n"
            . ($isWaitlist
                ? "vielen Dank für deine Anmeldung. Aktuell sind alle Plätze belegt – du stehst auf der Warteliste.\n"
                : "dein Standplatz {$standNumber} ({$meters} m) ist reserviert!\n")
            . "Termin: " . ($cfg['onsite_event_date'] ?: 'nach Ankündigung') . " (" . ($cfg['onsite_start_time'] ?? '09:00') . "–" . ($cfg['onsite_end_time'] ?? '16:00') . " Uhr)\n"
            . "Ort: " . ($cfg['onsite_location'] ?? '-') . "\n";
        $html = '<p>Hallo ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>' . ($isWaitlist
                ? 'vielen Dank für deine Standplatz-Anfrage. Du befindest dich aktuell auf der <strong>Warteliste</strong>.'
                : 'dein Standplatz <strong>' . htmlspecialchars($standNumber, ENT_QUOTES, 'UTF-8') . "</strong> ({$meters} m) wurde verbindlich vorgemerkt!")
            . '</p><p><strong>Ort:</strong> ' . htmlspecialchars((string)($cfg['onsite_location'] ?: '-'), ENT_QUOTES, 'UTF-8')
            . '<br><strong>Aufbau:</strong> ab ' . htmlspecialchars((string)($cfg['onsite_setup_time'] ?? '07:30'), ENT_QUOTES, 'UTF-8') . ' Uhr</p>';
        $attachments = [['filename' => 'flohmarkt-standplatz.ics', 'content' => $ics, 'mime' => 'text/calendar; charset=utf-8']];

        if ($mailSender !== null) {
            $mailSender($email, $subj, $html, $text, $attachments);
        } elseif (!self::isSandboxDemo() && class_exists('SimpleMailer')) {
            SimpleMailer::send($email, $subj, $html, $text, '', '', $smtpConfig, $attachments);
        }

        return [
            'success' => true,
            'stand'   => $stand,
            'ics'     => $ics,
            'message' => $isWaitlist
                ? 'Du wurdest auf die Warteliste gesetzt und per E-Mail benachrichtigt.'
                : "Standplatz {$standNumber} erfolgreich reserviert! Die Bestätigung (inkl. .ics-Kalenderdatei) ist unterwegs.",
        ];
    }

    public static function updateStandStatus(string $standId, string $status, ?string $standNumber = null): bool {
        if (!in_array($status, ['confirmed', 'waitlist', 'cancelled'], true)) {
            return false;
        }
        $store = self::getStore();
        $changed = false;
        foreach ($store['stands'] as &$st) {
            if (($st['id'] ?? '') === $standId) {
                $st['status'] = $status;
                if ($standNumber !== null && trim($standNumber) !== '') {
                    $st['stand_number'] = trim($standNumber);
                } elseif ($status === 'confirmed' && empty($st['stand_number'])) {
                    $st['stand_number'] = '#' . (count($store['stands']));
                }
                $changed = true;
                break;
            }
        }
        unset($st);
        if ($changed) {
            self::saveStore($store);
            self::logAudit('STAND_STATUS', "Stand {$standId} auf {$status}");
        }
        return $changed;
    }

    public static function deleteStand(string $standId): bool {
        $store = self::getStore();
        $before = count($store['stands']);
        $store['stands'] = array_values(array_filter($store['stands'], static fn(array $s): bool => ($s['id'] ?? '') !== $standId));
        if (count($store['stands']) === $before) {
            return false;
        }
        return self::saveStore($store);
    }

    public static function renderQrPriceTagHtml(array $item, string $itemUrl): string {
        $hash = hash('sha256', $itemUrl, true);
        $size = 21;
        $rects = '';
        $isFinder = static function (int $r, int $c) use ($size): bool {
            $inTopLeft = ($r < 7 && $c < 7);
            $inTopRight = ($r < 7 && $c >= $size - 7);
            $inBottomLeft = ($r >= $size - 7 && $c < 7);
            return $inTopLeft || $inTopRight || $inBottomLeft;
        };
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                $on = false;
                if ($isFinder($r, $c)) {
                    $lr = $r >= $size - 7 ? $r - ($size - 7) : $r;
                    $lc = $c >= $size - 7 ? $c - ($size - 7) : $c;
                    $on = ($lr === 0 || $lr === 6 || $lc === 0 || $lc === 6 || ($lr >= 2 && $lr <= 4 && $lc >= 2 && $lc <= 4));
                } else {
                    $idx = ($r * $size + $c);
                    $byte = ord($hash[$idx % 32]);
                    $on = (($byte >> ($idx % 8)) & 1) === 1;
                }
                if ($on) {
                    $x = 8 + $c * 6;
                    $y = 8 + $r * 6;
                    $rects .= "<rect x=\"{$x}\" y=\"{$y}\" width=\"6\" height=\"6\" fill=\"#0f172a\"/>";
                }
            }
        }
        $svgSize = 16 + $size * 6;
        $qrSvg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$svgSize} {$svgSize}\" width=\"136\" height=\"136\" role=\"img\" aria-label=\"QR-Code Artikel-Link\"><rect width=\"100%\" height=\"100%\" fill=\"#ffffff\"/>{$rects}</svg>";
        $standLine = !empty($item['stand_number'])
            ? '<div style="margin-top:6px; font-weight:700; color:#b45309;">🎪 Vor Ort an Stand ' . htmlspecialchars((string)$item['stand_number'], ENT_QUOTES, 'UTF-8') . '</div>'
            : '';

        return '<div class="card wfm-price-tag" style="max-width:460px; margin:2rem auto; padding:1.75rem; border:2px dashed #334155; border-radius:14px; background:#ffffff; color:#0f172a; text-align:center;">'
            . '<div style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:4px;">Flohmarkt-Preisschild · ' . htmlspecialchars((string)$item['item_number'], ENT_QUOTES, 'UTF-8') . '</div>'
            . '<h2 style="margin:0 0 8px 0; font-size:1.35rem;">' . htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<div style="font-size:2rem; font-weight:800; color:#2e6248; margin-bottom:10px;">' . htmlspecialchars(self::formatPrice($item), ENT_QUOTES, 'UTF-8') . '</div>'
            . $standLine
            . '<div style="display:inline-block; padding:8px; border:1px solid #cbd5e1; border-radius:10px; margin:12px auto;">' . $qrSvg . '</div>'
            . '<div style="font-size:0.75rem; color:#475569; word-break:break-all; margin-bottom:14px;">' . htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">'
            . '<button type="button" class="btn btn-primary" data-action="wfm-print-tag">🖨️ Schild drucken</button>'
            . '<a href="?page=flohmarkt&item=' . urlencode((string)$item['slug']) . '" class="btn btn-outline" style="text-decoration:none;">← Zurück zum Artikel</a>'
            . '</div></div>';
    }

    // =========================================================================
    // PHASE 40.2: MELDEFUNKTION, MODERATION, SPERREN, AUDIT-LOG, KPIS & WEBHOOKS
    // =========================================================================

    public static function logAudit(string $action, string $details, string $actor = 'admin'): void {
        $store = self::getStore();
        array_unshift($store['audit_log'], [
            'id'         => 'aud_' . bin2hex(random_bytes(4)),
            'action'     => $action,
            'details'    => $details,
            'actor'      => $actor,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $store['audit_log'] = array_slice($store['audit_log'], 0, 200);
        self::saveStore($store);

        if (!self::isSandboxDemo() && self::$testStorageDir === null && class_exists('SecurityManager') && method_exists('SecurityManager', 'logEvent')) {
            SecurityManager::logEvent('FLEAMARKET_' . $action, $details, 'INFO');
        }
    }

    public static function getAuditLog(): array {
        return array_values(self::getStore()['audit_log'] ?? []);
    }

    public static function isSellerBlocked(string $sellerId, string $sellerEmail = ''): bool {
        $blocked = self::getStore()['blocked_sellers'] ?? [];
        $emailLc = strtolower(trim($sellerEmail));
        foreach ($blocked as $b) {
            if ($sellerId !== '' && ($b['seller_id'] ?? '') === $sellerId) {
                return true;
            }
            if ($emailLc !== '' && ($b['seller_email'] ?? '') === $emailLc) {
                return true;
            }
        }
        return false;
    }

    public static function getBlockedSellers(): array {
        return array_values(self::getStore()['blocked_sellers'] ?? []);
    }

    public static function unblockSeller(string $identifier): bool {
        $store = self::getStore();
        $before = count($store['blocked_sellers']);
        $store['blocked_sellers'] = array_values(array_filter(
            $store['blocked_sellers'],
            static fn(array $b): bool => ($b['seller_id'] ?? '') !== $identifier && ($b['seller_email'] ?? '') !== $identifier
        ));
        if (count($store['blocked_sellers']) === $before) {
            return false;
        }
        self::saveStore($store);
        self::logAudit('SELLER_UNBLOCKED', "Anbieter entsperrt: {$identifier}");
        return true;
    }

    public static function reportItem(string $itemId, array $input): array {
        $item = self::getItem($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Anzeige nicht gefunden.'];
        }
        if (!empty($input['website_hp'])) {
            return ['success' => false, 'message' => 'Meldung vom Spamschutz abgelehnt.'];
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $last = (int)($_SESSION['weave_fm_last_report'] ?? 0);
            if (time() - $last < 60) {
                return ['success' => false, 'message' => 'Bitte warte kurz vor einer weiteren Meldung.'];
            }
        }

        $allowedReasons = ['spam', 'prohibited', 'fraud', 'wrong_category', 'sold_already', 'other'];
        $rawReason = (string)($input['reason'] ?? 'other');
        $reason = in_array($rawReason, $allowedReasons, true) ? $rawReason : 'other';
        $comment = trim((string)($input['comment'] ?? ''));

        $report = [
            'id'          => 'rep_' . bin2hex(random_bytes(5)),
            'item_id'     => $item['id'],
            'item_number' => $item['item_number'],
            'item_title'  => $item['title'],
            'reason'      => $reason,
            'comment'     => $comment,
            'status'      => 'open',
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $store = self::getStore();
        array_unshift($store['reports'], $report);
        self::saveStore($store);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['weave_fm_last_report'] = time();
        }
        self::logAudit('ITEM_REPORTED', "Anzeige {$item['item_number']} gemeldet ({$reason})", 'visitor');

        return ['success' => true, 'report' => $report, 'message' => 'Vielen Dank – deine Meldung wurde an die Moderation übermittelt.'];
    }

    public static function getReports(?string $status = null): array {
        $all = array_values(self::getStore()['reports'] ?? []);
        if ($status === null || $status === '') {
            return $all;
        }
        return array_values(array_filter($all, static fn(array $r): bool => ($r['status'] ?? '') === $status));
    }

    public static function resolveReport(string $reportId, string $resolution, string $actor = 'admin'): array {
        if (!in_array($resolution, ['dismiss', 'hide_item', 'delete_item', 'block_seller'], true)) {
            return ['success' => false, 'message' => 'Unbekannte Moderations-Aktion.'];
        }
        $store = self::getStore();
        $foundIdx = null;
        foreach ($store['reports'] as $i => $r) {
            if (($r['id'] ?? '') === $reportId) {
                $foundIdx = $i;
                break;
            }
        }
        if ($foundIdx === null) {
            return ['success' => false, 'message' => 'Meldung nicht gefunden.'];
        }

        $rep = $store['reports'][$foundIdx];
        $itemId = (string)($rep['item_id'] ?? '');
        $item = self::getItem($itemId);

        if ($resolution === 'hide_item' && $item) {
            self::setItemStatus($itemId, 'archived');
        } elseif ($resolution === 'delete_item' && $item) {
            self::deleteItem($itemId);
        } elseif ($resolution === 'block_seller' && $item) {
            $store = self::getStore();
            $sellerId = (string)($item['seller_id'] ?? '');
            $sellerEmail = strtolower(trim((string)($item['seller_email'] ?? '')));
            if (!self::isSellerBlocked($sellerId, $sellerEmail)) {
                $store['blocked_sellers'][] = [
                    'seller_id'    => $sellerId,
                    'seller_email' => $sellerEmail,
                    'seller_name'  => (string)($item['seller_name'] ?? ''),
                    'blocked_at'   => date('Y-m-d H:i:s'),
                    'reason'       => (string)($rep['reason'] ?? 'moderation'),
                ];
            }
            foreach ($store['items'] as &$it) {
                if (($it['seller_id'] ?? '') === $sellerId || ($sellerEmail !== '' && ($it['seller_email'] ?? '') === $sellerEmail)) {
                    $it['status'] = 'archived';
                    $it['updated_at'] = date('Y-m-d H:i:s');
                }
            }
            unset($it);
            self::saveStore($store);
        }

        $store = self::getStore();
        $store['reports'][$foundIdx]['status'] = 'resolved';
        $store['reports'][$foundIdx]['resolution'] = $resolution;
        $store['reports'][$foundIdx]['resolved_at'] = date('Y-m-d H:i:s');
        self::saveStore($store);

        self::logAudit('REPORT_RESOLVED', "Meldung {$reportId} für {$rep['item_number']}: {$resolution}", $actor);
        return ['success' => true, 'message' => 'Moderationsentscheidung gespeichert.'];
    }

    public static function getDashboardKpis(): array {
        $items = self::getItems();
        $active = 0;
        $pending = 0;
        $sold = 0;
        $views = 0;
        foreach ($items as $it) {
            $st = (string)($it['status'] ?? '');
            if ($st === 'active') {
                $active++;
            } elseif ($st === 'pending') {
                $pending++;
            } elseif ($st === 'sold') {
                $sold++;
            }
            $views += (int)($it['views'] ?? 0);
        }
        $standsConfirmed = count(array_filter(self::getStands(), static fn(array $s): bool => ($s['status'] ?? '') === 'confirmed'));
        return [
            'total_items'      => count($items),
            'active_items'     => $active,
            'pending_items'    => $pending,
            'sold_items'       => $sold,
            'total_views'      => $views,
            'total_inquiries'  => count(self::getInquiries()),
            'open_reports'     => count(self::getReports('open')),
            'confirmed_stands' => $standsConfirmed,
        ];
    }

    public static function dispatchWebhook(string $eventName, array $payload): void {
        if (self::$testStorageDir !== null || self::isSandboxDemo() || !class_exists('WebhookManager')) {
            return;
        }
        try {
            WebhookManager::dispatch($eventName, $payload);
        } catch (\Throwable $e) {
            // Webhook-Fehler dürfen den Flohmarkt-Fluss nicht blockieren
        }
    }

    // =========================================================================
    // PHASE 40.3: ABLAUF-AUTOMATIK, VERLÄNGERUNG & DSGVO-BEREINIGUNG
    // =========================================================================

    public static function extendItemByToken(string $manageToken, ?int $days = null): array {
        $tok = trim($manageToken);
        if ($tok === '') {
            return ['success' => false, 'message' => 'Ungültiger Verlängerungs-Token.'];
        }
        $store = self::getStore();
        $addDays = $days !== null ? max(1, $days) : max(1, (int)($store['config']['default_duration_days'] ?? 30));
        foreach ($store['items'] as &$it) {
            if (!empty($it['manage_token']) && hash_equals((string)$it['manage_token'], $tok)) {
                $baseTs = max(time(), (int)(strtotime((string)($it['expires_at'] ?? 'now')) ?: time()));
                $it['expires_at'] = date('Y-m-d H:i:s', $baseTs + ($addDays * 86400));
                if (($it['status'] ?? '') === 'expired') {
                    $it['status'] = 'active';
                }
                $it['reminder_sent_at'] = '';
                $it['extended_count'] = ((int)($it['extended_count'] ?? 0)) + 1;
                $it['updated_at'] = date('Y-m-d H:i:s');
                self::saveStore($store);
                self::logAudit('ITEM_EXTENDED', "Anzeige {$it['item_number']} um {$addDays} Tage verlängert", 'self_service');
                return ['success' => true, 'item' => $it, 'message' => "Deine Anzeige wurde um {$addDays} Tage bis " . date('d.m.Y', strtotime($it['expires_at'])) . ' verlängert.'];
            }
        }
        unset($it);
        return ['success' => false, 'message' => 'Verlängerungs-Link ungültig oder Anzeige bereits gelöscht.'];
    }

    public static function cleanupOrphanedImages(?string $uploadDir = null): int {
        $dir = $uploadDir !== null
            ? rtrim($uploadDir, '/\\')
            : (class_exists('FleamarketImagePipeline') ? FleamarketImagePipeline::getDefaultUploadDir() : (dirname(__DIR__, 2) . '/images/uploads/fleamarket'));
        if (!is_dir($dir)) {
            return 0;
        }

        $referenced = [];
        foreach (self::getItems() as $it) {
            foreach ((array)($it['images'] ?? []) as $im) {
                foreach (['url', 'thumb_url'] as $k) {
                    if (!empty($im[$k])) {
                        $referenced[basename((string)$im[$k])] = true;
                    }
                }
            }
        }

        $removed = 0;
        $entries = @scandir($dir);
        if (!is_array($entries)) {
            return 0;
        }
        foreach ($entries as $f) {
            if (!str_starts_with($f, 'fm_') || isset($referenced[$f])) {
                continue;
            }
            $abs = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_file($abs) && @unlink($abs)) {
                $removed++;
            }
        }
        return $removed;
    }

    public static function runLifecycleMaintenance(?int $nowTs = null, ?array $smtpConfig = null, ?callable $mailSender = null, ?string $uploadDir = null): array {
        $now = $nowTs ?? time();
        $store = self::getStore();
        $cfg = $store['config'];

        $reminderEnabled = !empty($cfg['expiry_reminder_enabled']);
        $reminderWindow = max(1, (int)($cfg['expiry_reminder_days'] ?? 3)) * 86400;
        $retExpired = max(1, (int)($cfg['retention_days_expired'] ?? 30)) * 86400;
        $retInq = max(1, (int)($cfg['retention_days_inquiries'] ?? 90)) * 86400;
        $baseUrl = class_exists('SecurityManager') ? rtrim(SecurityManager::getBaseUrl(), '/') : 'http://localhost';

        $expiredNow = 0;
        $remindersSent = 0;
        $purgedItems = 0;
        $keptItems = [];

        foreach ($store['items'] as $it) {
            $expTs = strtotime((string)($it['expires_at'] ?? ''));
            $st = (string)($it['status'] ?? 'active');

            if ($expTs !== false && $expTs <= $now && in_array($st, ['active', 'reserved'], true)) {
                $it['status'] = 'expired';
                $it['updated_at'] = date('Y-m-d H:i:s', $now);
                $st = 'expired';
                $expiredNow++;
            }

            if ($st === 'expired' && str_starts_with((string)($it['seller_id'] ?? ''), 'guest_') && $expTs !== false && ($now - $expTs) >= $retExpired) {
                $purgedItems++;
                continue;
            }

            if (
                $reminderEnabled
                && $st === 'active'
                && empty($it['reminder_sent_at'])
                && $expTs !== false
                && $expTs > $now
                && ($expTs - $now) <= $reminderWindow
                && !empty($it['seller_email'])
                && filter_var($it['seller_email'], FILTER_VALIDATE_EMAIL)
                && !empty($it['manage_token'])
            ) {
                $extendUrl = $baseUrl . '/?page=flohmarkt&extend_ad=' . urlencode((string)$it['manage_token']);
                $subj = "[{$it['item_number']}] Anzeige läuft bald ab – jetzt 1-Klick verlängern";
                $text = "Hallo {$it['seller_name']},\n\ndeine Flohmarkt-Anzeige „{$it['title']}“ läuft am " . date('d.m.Y', $expTs) . " ab.\n"
                    . "Mit diesem Link kannst du sie direkt um " . ($cfg['default_duration_days'] ?? 30) . " Tage verlängern:\n{$extendUrl}\n";
                $html = '<p>Hallo ' . htmlspecialchars((string)$it['seller_name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p>deine Anzeige <strong>' . htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8') . '</strong> läuft am ' . date('d.m.Y', $expTs) . ' ab.</p>'
                    . '<p><a href="' . htmlspecialchars($extendUrl, ENT_QUOTES, 'UTF-8') . '">Jetzt mit 1 Klick verlängern</a></p>';

                if ($mailSender !== null) {
                    $mailSender($it['seller_email'], $subj, $html, $text);
                } elseif (!self::isSandboxDemo() && class_exists('SimpleMailer')) {
                    SimpleMailer::send($it['seller_email'], $subj, $html, $text, '', '', $smtpConfig);
                }
                $it['reminder_sent_at'] = date('Y-m-d H:i:s', $now);
                $remindersSent++;
            }

            $keptItems[] = $it;
        }
        $store['items'] = $keptItems;

        $purgedInquiries = 0;
        $keptInquiries = [];
        foreach ($store['inquiries'] as $inq) {
            $cTs = strtotime((string)($inq['created_at'] ?? ''));
            if ($cTs !== false && ($now - $cTs) >= $retInq) {
                $purgedInquiries++;
                continue;
            }
            $keptInquiries[] = $inq;
        }
        $store['inquiries'] = $keptInquiries;
        $store['config']['last_lifecycle_run'] = $now;
        self::saveStore($store);

        $removedImages = self::cleanupOrphanedImages($uploadDir);

        return [
            'expired_items'    => $expiredNow,
            'reminders_sent'   => $remindersSent,
            'purged_items'     => $purgedItems,
            'purged_inquiries' => $purgedInquiries,
            'removed_images'   => $removedImages,
        ];
    }

    public static function maybeRunLazyLifecycle(): void {
        if (self::isSandboxDemo()) {
            return;
        }
        $cfg = self::getConfig();
        $last = (int)($cfg['last_lifecycle_run'] ?? 0);
        if (time() - $last < 900) {
            return;
        }
        global $data;
        $smtp = isset($data['settings']['smtp']) && is_array($data['settings']['smtp']) ? $data['settings']['smtp'] : null;
        self::runLifecycleMaintenance(null, $smtp);
    }

    // =========================================================================
    // PHASE 40.4: EXPORT & IMPORT (JSON / CSV MIT FORMULA-INJECTION-SCHUTZ)
    // =========================================================================

    public static function exportJson(): string {
        return (string)json_encode(self::getStore(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function importJson(string $jsonStr): array {
        $decoded = json_decode($jsonStr, true);
        if (!is_array($decoded) || (!isset($decoded['items']) && !isset($decoded['categories']))) {
            return ['success' => false, 'message' => 'Ungültiges Flohmarkt-JSON-Format (erwartet items oder categories).'];
        }
        $store = self::getStore();
        $importedCount = 0;
        if (isset($decoded['categories']) && is_array($decoded['categories'])) {
            foreach ($decoded['categories'] as $c) {
                if (is_array($c) && !empty($c['name'])) {
                    self::saveCategory($c);
                }
            }
            $store = self::getStore();
        }
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $it) {
                if (is_array($it) && !empty($it['title'])) {
                    self::saveItem($it);
                    $importedCount++;
                }
            }
        }
        self::logAudit('JSON_IMPORTED', "{$importedCount} Anzeige(n) via JSON importiert");
        return ['success' => true, 'imported' => $importedCount, 'message' => "{$importedCount} Anzeige(n) erfolgreich importiert."];
    }

    public static function exportCsv(): string {
        $safeCell = static function ($val): string {
            $s = (string)$val;
            if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                $s = "'" . $s;
            }
            $s = str_replace(["\r", "\n"], ' ', $s);
            return '"' . str_replace('"', '""', $s) . '"';
        };
        $lines = [];
        $lines[] = implode(';', ['Anzeigen-Nr', 'Titel', 'Typ', 'Preismodell', 'Preis', 'Waehrung', 'Status', 'Standnummer', 'Standort', 'Erstellt']);
        foreach (self::getItems() as $it) {
            $loc = trim(($it['location_zip'] ?? '') . ' ' . ($it['location_city'] ?? ''));
            $lines[] = implode(';', [
                $safeCell($it['item_number'] ?? ''),
                $safeCell($it['title'] ?? ''),
                $safeCell($it['ad_type'] ?? ''),
                $safeCell($it['price_model'] ?? ''),
                $safeCell(number_format((float)($it['price'] ?? 0), 2, '.', '')),
                $safeCell($it['currency'] ?? 'EUR'),
                $safeCell($it['status'] ?? ''),
                $safeCell($it['stand_number'] ?? ''),
                $safeCell($loc),
                $safeCell($it['created_at'] ?? ''),
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }
}
