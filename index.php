<?php
/**
 * ============================================================================
 * ARCHITEKTUR- & DESIGN-GOVERNANCE (STRIKTE REGEL):
 * KEIN MENÜ (WEDER IM FRONTEND NOCH IM BACKEND) DARF OHNE VORHERIGE
 * AUSDRÜCKLICHE ZUSTIMMUNG DES BENUTZERS GEÄNDERT, VERSCHOBEN ODER UMPLATZIERT WERDEN!
 * Dies gilt für:
 * - Hauptnavigation (Header Desktop & Mobile)
 * - Slide-in-Menüs (Kontakt, Impressum, Mobilnavigation)
 * - Sämtliche Menüpunkte, Reihenfolgen, Links und Trigger-Elemente
 * ============================================================================
 */

// Zentrale Konfigurationsdatei und Hilfsfunktionen einbinden
require_once 'config.php';

/**
 * 1. Datenbasis laden
 * Lädt alle Website-Inhalte (Texte, Einstellungen, Galerie, Kurse) aus der zentralen data.json
 */
$data_file = 'data.json';
$data = load_data($data_file, [
    'settings' => [
        'upload_dir' => 'images/uploads',
        'privacy_policy_text' => ''
    ],
    'texts' => [
        'hero_title' => 'Handgefertigte Unikate & Workshops',
        'hero_subtitle' => 'Traditionelles Handwerk, kreative Formgebung, Naturdesign und inspirierende Praxiskurse.',
        'about_title' => 'Über mein Atelier & Handwerk',
        'about_text' => 'Handwerkskunst ist für mich eine lebendige Verbindung aus Natur, Tradition und zeitgenössischem Design...',
        'gallery_title' => 'Galerie & Werkstücke',
        'gallery_subtitle' => 'Ein Auszug aus meinen handgefertigten Designobjekten, Accessoires und maßgeschneiderten Auftragsarbeiten.',
        'courses_title' => 'Kurse & Kreativ-Workshops',
        'courses_subtitle' => 'Lerne die Grundlagen der handwerklichen Gestaltung in persönlicher, inspirierender Werkstatt-Atmosphäre selbst kennen.',
        'past_courses_title' => 'Impressionen vergangener Workshops',
        'past_courses_subtitle' => 'Ein Rückblick auf gemeinsame kreative Stunden und entstandene Meisterstücke.',
        'contact_intro_text' => 'Hast du Fragen zu meinen Workshops, möchtest einen Gutschein erwerben oder ein individuelles Werkstück anfragen?',
        'contact_name' => 'Maxima Musterfrau',
        'contact_street' => 'Musterstraße 12',
        'contact_city' => '12345 Musterstadt',
        'contact_phone' => '+49 123 4567890',
        'contact_email' => 'kontakt@maxima-musterfrau.de',
        'contact_instagram' => 'maxima.musterfrau'
    ],
    'gallery' => [],
    'courses' => [],
    'past_courses' => []
]);

/**
 * 2. Galerie sortieren
 * Sortiert die Galerie-Elemente aufsteigend nach dem konfigurierten 'sort_order'
 */
usort($data['gallery'], function($a, $b) {
    return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
});

/**
 * 3. Definierte Kategorien & sichtbare Galerie-Elemente ermitteln
 * Liest die vom Administrator definierten Kategorien aus data.json.
 * Elemente, deren Kategorie gelöscht wurde, werden in der Galerie nicht mehr angezeigt.
 */
$categories = $data['categories'] ?? ['Korbwaren', 'Gartendeko', 'Sichtschutz', 'Sonstiges'];

$visible_gallery = array_values(array_filter($data['gallery'], function($item) use ($categories) {
    return in_array($item['category'] ?? '', $categories);
}));

/**
 * 4. Basis-URL ermitteln
 * Dynamische Erkennung von Protokoll und Host für Social-Sharing und direkte Kurslinks
 */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$site_url = $proto . '://' . $host . $base_path . '/';

/**
 * 5. Deep-Link-Parameter verarbeiten
 * Prüft, ob ein bestimmter Kurs direkt per URL (z. B. ?course=3) aufgerufen wurde
 */
$selected_course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;

/**
 * 6. Modulare Sektionen & Navigationsmenü (Phase 6)
 */
$visible_sections = SectionManager::getVisibleSections($data);
$nav_items = SectionManager::getNavItems($data);
if (empty($nav_items)) {
    $nav_items = [
        ['id' => 'gallery', 'nav_label' => 'Galerie'],
        ['id' => 'courses', 'nav_label' => 'Workshops'],
        ['id' => 'about', 'nav_label' => 'Über mich']
    ];
}

/**
 * 7. Logo-Konfiguration vorbereiten
 * Unterstützt flexible Positionierung:
 * - 'header_before': Im Header VOR dem Seitentitel
 * - 'header_after': Im Header NACH dem Seitentitel
 * - 'hero_before': In der Hero-Zeile VOR der Überschrift (gleiche Zeile)
 * - 'hero_after': In der Hero-Zeile NACH der Überschrift (gleiche Zeile)
 */
$logo_motif = $data['settings']['hero_logo_motif'] ?? 'paw_beach';
$logo_color = $data['settings']['hero_logo_color'] ?? '#d97706';
$logo_pos = $data['settings']['logo_position'] ?? 'header_before';

$header_logo_html = ($logo_pos === 'header_before' || $logo_pos === 'header_after')
    ? render_paw_logo($logo_motif, $logo_color, '1.2em', 'header-paw-logo')
    : '';

$hero_logo_html = ($logo_pos === 'hero_before' || $logo_pos === 'hero_after')
    ? render_paw_logo($logo_motif, $logo_color, '1.15em', 'hero-paw-logo')
    : '';

/**
 * 7. Dynamische Design- & Styling-Konfiguration laden (Hybrid-Modell)
 * Übernimmt die gespeicherten Design-Werte mit sicherem Standard-Fallback
 */
$container_width = $data['settings']['container_width'] ?? '1200px';
$primary_color = $data['settings']['primary_color'] ?? '#2e6248';
$accent_color = $data['settings']['accent_color'] ?? '#b26c39';
$bg_color = $data['settings']['bg_color'] ?? '#fbf9f6';
$font_scale = $data['settings']['font_scale'] ?? '100%';
$gallery_gap = $data['settings']['gallery_gap'] ?? '2rem';
$card_radius = $data['settings']['card_radius'] ?? '20px';

/**
 * 8. SEO- & OpenGraph-Metadaten vorbereiten
 */
$seo = $data['seo'] ?? [];
$hero_title = $data['texts']['hero_title'] ?? 'Handgefertigte Unikate & Workshops';
$contact_name = $data['texts']['contact_name'] ?? 'Maxima Musterfrau';

$meta_title = !empty(trim($seo['meta_title'] ?? ''))
    ? trim($seo['meta_title'])
    : ($hero_title . ' | ' . $contact_name);

$meta_desc = !empty(trim($seo['meta_description'] ?? ''))
    ? trim($seo['meta_description'])
    : ($data['texts']['hero_subtitle'] ?? 'Entdecke handgefertigte Naturgeflechte und Workshops.');

$meta_keywords = !empty(trim($seo['meta_keywords'] ?? ''))
    ? trim($seo['meta_keywords'])
    : 'Handwerk, Naturgeflechte, Workshops, Kurse';

$meta_robots = !empty(trim($seo['robots'] ?? ''))
    ? trim($seo['robots'])
    : 'index, follow';

$canonical_url = !empty(trim($seo['canonical_url'] ?? ''))
    ? trim($seo['canonical_url'])
    : $site_url;

$og_image_raw = !empty(trim($seo['og_image'] ?? ''))
    ? trim($seo['og_image'])
    : '/images/default_wicker1.png';

$og_image_url = (preg_match('~^https?://~i', $og_image_raw))
    ? $og_image_raw
    : rtrim($site_url, '/') . '/' . ltrim($og_image_raw, '/');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 1. Basis- & SEO-Metadaten -->
    <title><?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($meta_robots, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- 2. OpenGraph-Protokoll (WhatsApp, Facebook, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="de_DE">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($contact_name, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- 3. Twitter Cards (X) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_url, ENT_QUOTES, 'UTF-8'); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="libs/css/styles.css?v=<?php echo file_exists('libs/css/styles.css') ? filemtime('libs/css/styles.css') : time(); ?>" rel="stylesheet">

    <!-- Dynamisches CSS-Variablen-Override (Design-Einstellungen) -->
    <style id="dynamic-design-variables">
        :root {
            --bg-dark: <?php echo htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8'); ?>;
            --primary: <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?>;
            --accent: <?php echo htmlspecialchars($accent_color, ENT_QUOTES, 'UTF-8'); ?>;
            --container-max-width: <?php echo htmlspecialchars($container_width, ENT_QUOTES, 'UTF-8'); ?>;
            --font-scale: <?php echo htmlspecialchars($font_scale, ENT_QUOTES, 'UTF-8'); ?>;
            --card-radius: <?php echo htmlspecialchars($card_radius, ENT_QUOTES, 'UTF-8'); ?>;
            --gallery-gap: <?php echo htmlspecialchars($gallery_gap, ENT_QUOTES, 'UTF-8'); ?>;
        }
    </style>
</head>
<body>

    <!-- Toast Feedback -->
    <div id="toast"><span class="material-icons" style="font-size:18px;">check</span> <span id="toast-text">Link kopiert!</span></div>

    <!-- CSS Slide-in Menu Trigger Checkboxes -->
    <input type="checkbox" id="menu-toggle">
    <input type="checkbox" id="menu-toggle-impressum">
    <input type="checkbox" id="menu-toggle-mobile">

    <!-- ======================================================================
         FRONTEND-MENÜ (HEADER NAVIGATION & MENÜ-TRIGGER)
         STRIKTE VORGABE: KEINE ÄNDERUNGEN AN MENÜPUNKTEN, REIHENFOLGE,
         DESIGN ODER STRUKTUR OHNE VORHERIGE AUSDRÜCKLICHE ZUSTIMMUNG DES USERS!
         ====================================================================== -->
    <header class="header-nav">
        <div class="header-container">
            <a href="#" class="logo"><?php if ($logo_pos === 'header_before'): echo $header_logo_html; endif; ?><span><?php echo htmlspecialchars($data['texts']['contact_name'] ?? 'Atelier Musterfrau'); ?></span><?php if ($logo_pos === 'header_after'): echo $header_logo_html; endif; ?></a>
            <nav class="nav-links">
                <?php foreach ($nav_items as $item): ?>
                    <a href="#<?php echo htmlspecialchars($item['id']); ?>" class="nav-item"><?php echo htmlspecialchars($item['nav_label']); ?></a>
                <?php endforeach; ?>
                <label for="menu-toggle" class="nav-item" style="cursor: pointer;">Kontakt</label>
                <label for="menu-toggle-impressum" class="nav-item" style="cursor: pointer;">Impressum</label>
            </nav>
            <!-- Mobile Menu Trigger (Hamburger) -->
            <label for="menu-toggle-mobile" class="btn btn-outline" style="display: none; padding: 6px 12px; font-size: 0.85rem; cursor: pointer;" id="mobile-nav-trigger">
                <span class="material-icons" style="font-size: 18px; vertical-align: middle;">menu</span>
            </label>
        </div>
    </header>

    <main class="container">
        <?php foreach ($visible_sections as $sec): 
            $sec_id = $sec['id'] ?? '';
        ?>

            <?php if ($sec_id === 'hero'): ?>
                <!-- Hero Section -->
                <section class="hero-section" id="hero">
                    <div class="hero-card">
                        <h1><?php if ($logo_pos === 'hero_before'): echo $hero_logo_html . ' '; endif; ?><?php echo htmlspecialchars($data['texts']['hero_title']); ?><?php if ($logo_pos === 'hero_after'): echo ' ' . $hero_logo_html; endif; ?></h1>
                        <p class="hero-subtitle"><?php echo htmlspecialchars($data['texts']['hero_subtitle']); ?></p>
                    </div>
                </section>

            <?php elseif ($sec_id === 'gallery'): ?>
                <!-- Gallery Section -->
                <section id="gallery">
            <h2 style="margin-bottom: 1rem; text-align: center;"><?php echo htmlspecialchars($data['texts']['gallery_title'] ?? 'Galerie & Handarbeit'); ?></h2>
            <?php if (!empty($data['texts']['gallery_subtitle'])): ?>
                <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
                    <?php echo htmlspecialchars($data['texts']['gallery_subtitle']); ?>
                </p>
            <?php endif; ?>

            <?php if (empty($visible_gallery)): ?>
                <p style="text-align: center; color: var(--text-secondary);">Noch keine Werkstücke in der Galerie.</p>
            <?php else: ?>
                <!-- Category Filter Buttons -->
                <div class="filter-container">
                    <button class="filter-btn active" onclick="filterGallery('all', this)">Alle</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="filter-btn" onclick="filterGallery('<?php echo htmlspecialchars($cat); ?>', this)"><?php echo htmlspecialchars($cat); ?></button>
                    <?php endforeach; ?>
                </div>

                <?php 
                    $gallery_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur';
                    $gallery_lightbox = $data['settings']['gallery_lightbox'] ?? true;
                    
                    switch ($gallery_layout) {
                        case 'masonry':
                            $container_class = 'gallery-container-masonry';
                            break;
                        case 'editorial':
                            $container_class = 'gallery-container-editorial';
                            break;
                        case 'classic':
                            $container_class = 'gallery-container-classic';
                            break;
                        case 'ambient_blur':
                        default:
                            $container_class = 'gallery-container-ambient';
                            break;
                    }
                ?>
                <!-- Gallery Grid (Dynamisch: <?php echo htmlspecialchars($gallery_layout); ?>) -->
                <div class="<?php echo $container_class; ?>">
                    <?php foreach ($visible_gallery as $idx => $item): ?>
                        <?php 
                            $img_raw = $item['image'] ?? '';
                            $img_src = (strpos($img_raw, 'http://') === 0 || strpos($img_raw, 'https://') === 0)
                                ? $img_raw
                                : ((strpos($img_raw, '/') === 0) ? '.' . $img_raw : './' . $img_raw);
                            $is_portrait = false;
                            $local_file = __DIR__ . '/' . ltrim($item['image'], '/\\');
                            if (file_exists($local_file)) {
                                $size = @getimagesize($local_file);
                                if ($size && $size[1] > ($size[0] * 1.1)) {
                                    $is_portrait = true;
                                }
                            }
                        ?>
                        <div class="card gallery-card <?php echo $is_portrait ? 'card-portrait' : ''; ?>" data-category="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" data-index="<?php echo $idx; ?>">
                            <div class="gallery-media-wrapper <?php echo $is_portrait ? 'is-portrait' : ''; ?>" <?php if ($gallery_lightbox): ?>onclick="openLightbox(<?php echo $idx; ?>)" style="cursor:zoom-in;" title="Klicken für Vollbildansicht"<?php endif; ?>>
                                <?php if ($gallery_layout === 'ambient_blur'): ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-bg-blur" alt="" aria-hidden="true">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-fg-img" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-fg-img" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                <?php endif; ?>
                                <?php if ($gallery_lightbox): ?>
                                    <span class="lightbox-hint"><span class="material-icons" style="font-size:18px;">zoom_in</span></span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <h3 style="margin:0; font-size:1.3rem;"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <span style="font-size:0.75rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:3px 10px; border-radius:12px; font-weight:600;"><?php echo htmlspecialchars($item['category'] ?? ''); ?></span>
                            </div>
                            <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;"><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

            <?php elseif ($sec_id === 'courses'): ?>
                <!-- Course Dates Section -->
                <section id="courses">
            <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo htmlspecialchars($data['texts']['courses_title'] ?? 'Kurse & Workshops'); ?></h2>
            <?php if (!empty($data['texts']['courses_subtitle'])): ?>
                <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
                    <?php echo htmlspecialchars($data['texts']['courses_subtitle']); ?>
                </p>
            <?php endif; ?>

            <div style="display:flex; flex-direction:column; gap: 1.5rem; margin-bottom: 5rem;">
                <?php if (empty($data['courses'])): ?>
                    <p style="text-align: center; color: var(--text-secondary);">Aktuell sind keine Kurstermine festgelegt. Schreib mir gern eine Anfrage bei Interesse!</p>
                <?php else: ?>
                    <?php foreach ($data['courses'] as $c): ?>
                        <?php 
                            $spots_left = max(0, $c['max_participants'] - $c['current_participants']);
                            $is_bookable = $c['available'] && ($spots_left > 0);
                            $course_link = $site_url . '?course=' . $c['id'];
                            $time_text = !empty($c['time']) ? $c['time'] : '10:00 - 16:00 Uhr';
                            $location_text = !empty($c['location']) ? $c['location'] : 'Werkstatt Kraaker Straße 14, 19077 Rastow';
                            $is_highlighted = ($selected_course_id === (int)$c['id']);
                        ?>
                        <div class="course-row <?php echo $is_highlighted ? 'highlighted' : ''; ?>" id="course-<?php echo $c['id']; ?>">
                            <div style="flex:1; min-width: 290px;">
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom: 0.75rem; flex-wrap:wrap;">
                                    <span style="background: rgba(217, 119, 6, 0.12); color: var(--accent); padding: 4px 14px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; display:inline-flex; align-items:center; gap:5px;">
                                        <span class="material-icons" style="font-size:16px;">calendar_today</span>
                                        <?php echo htmlspecialchars($c['date_string']); ?>
                                    </span>
                                    
                                    <?php if ($is_bookable): ?>
                                        <span style="color: #10b981; font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:4px; background: rgba(16,185,129,0.08); padding: 4px 10px; border-radius: 12px;">
                                            <span class="material-icons" style="font-size:16px;">check_circle</span> Plätze frei
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #ef4444; font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:4px; background: rgba(239,68,68,0.08); padding: 4px 10px; border-radius: 12px;">
                                            <span class="material-icons" style="font-size:16px;">block</span> Ausgebucht
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.4rem;"><?php echo htmlspecialchars($c['title']); ?></h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom:0.75rem; line-height: 1.5;"><?php echo htmlspecialchars($c['description']); ?></p>
                                
                                <!-- Meta Information: Time, Location, Participants -->
                                <div style="display:flex; gap: 0.75rem; flex-wrap:wrap; margin-top: 0.5rem;">
                                    <span class="course-meta-badge">
                                        <span class="material-icons" style="font-size:15px; color:var(--primary);">schedule</span>
                                        <?php echo htmlspecialchars($time_text); ?>
                                    </span>
                                    <span class="course-meta-badge">
                                        <span class="material-icons" style="font-size:15px; color:var(--primary);">place</span>
                                        <?php echo htmlspecialchars($location_text); ?>
                                    </span>
                                    <span class="course-meta-badge">
                                        <span class="material-icons" style="font-size:15px; color:var(--primary);">group</span>
                                        <strong><?php echo $c['current_participants']; ?> / <?php echo $c['max_participants']; ?></strong> belegt (noch <strong><?php echo $spots_left; ?></strong> frei)
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.75rem; justify-content:center; min-width: 170px;">
                                <span style="font-size: 1.6rem; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($c['price']); ?></span>
                                
                                <div style="display:flex; gap:8px; width:100%; justify-content:flex-end; flex-wrap:wrap;">
                                    <?php if ($is_bookable): ?>
                                        <button onclick="requestCourse('<?php echo htmlspecialchars(addslashes($c['title'])); ?>', '<?php echo htmlspecialchars(addslashes($c['date_string'])); ?>', '<?php echo htmlspecialchars(addslashes($time_text)); ?>', '<?php echo htmlspecialchars(addslashes($location_text)); ?>')" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem; flex:1; justify-content:center;">
                                            Jetzt anfragen
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem; opacity: 0.5; cursor: not-allowed; flex:1; justify-content:center;" disabled>
                                            Ausgebucht
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Share Button -->
                                    <button type="button" class="btn btn-outline" style="padding: 8px 10px; font-size: 0.85rem;" title="Diesen Kurs teilen" onclick="shareCourse('<?php echo htmlspecialchars(addslashes($c['title'])); ?>', '<?php echo htmlspecialchars(addslashes($c['date_string'])); ?>', '<?php echo htmlspecialchars($course_link); ?>')">
                                        <span class="material-icons" style="font-size:16px;">share</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

            <?php elseif ($sec_id === 'past_courses'): ?>
                <!-- Historical Course Images Section -->
                <section id="past_courses">
                    <h3 style="margin-bottom: 1rem; font-size: 1.5rem; text-align: center;"><?php echo htmlspecialchars($data['texts']['past_courses_title'] ?? 'Impressionen vergangener Kurse'); ?></h3>
                    <?php if (!empty($data['texts']['past_courses_subtitle'])): ?>
                        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 2rem auto; font-size: 0.95rem;">
                            <?php echo htmlspecialchars($data['texts']['past_courses_subtitle']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (empty($data['past_courses'])): ?>
                        <p style="color: var(--text-secondary); font-size:0.95rem; text-align: center;">Noch keine Bilder hochgeladen.</p>
                    <?php else: ?>
                        <div class="past-grid">
                            <?php foreach ($data['past_courses'] as $img): ?>
                                <?php 
                                    $img_src = (strpos($img, '/') === 0) ? '.' . $img : './' . $img;
                                ?>
                                <div class="past-card">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Kursimpression">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php elseif ($sec_id === 'about'): ?>
                <!-- About Section -->
                <section id="about">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: center;">
                        <div>
                            <h2 style="color: var(--primary);"><?php echo htmlspecialchars($data['texts']['about_title']); ?></h2>
                            <p style="font-size: 1.1rem; color: var(--text-secondary); max-width: 900px; white-space: pre-line; line-height: 1.7;">
                                <?php echo htmlspecialchars($data['texts']['about_text']); ?>
                            </p>
                        </div>
                    </div>
                </section>

            <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer style="margin-top: 5rem; padding: 4rem 0; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
        <div class="container" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['texts']['contact_name'] ?? 'Maxima Musterfrau'); ?>. Alle Rechte vorbehalten.</p>
            <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; align-items: center;">
                <label for="menu-toggle" style="cursor: pointer; color: var(--primary); font-weight:600;">Kontakt</label>
                <label for="menu-toggle-impressum" style="cursor: pointer; color: var(--primary); font-weight:600;">Impressum &amp; Datenschutz</label>
                <a href="admin/" style="color: var(--text-secondary); opacity: 0.5; font-size:0.8rem;">Admin-Bereich</a>
            </div>
        </div>
    </footer>

    <!-- Slide-in Contact Menu (Right) -->
    <label for="menu-toggle" class="menu-overlay contact-overlay"></label>
    <div class="slide-menu contact-menu">
        <label for="menu-toggle" class="close-menu-btn">&times;</label>
        <h2 style="color: var(--primary); margin-bottom: 2rem;">Kontakt &amp; Kursanfrage</h2>
        <div style="display: flex; flex-direction: column; gap: 1.5rem; color: var(--text-secondary);">
            
            <p><?php echo htmlspecialchars($data['texts']['contact_intro_text'] ?? 'Hast du Fragen zu meinen Kursen, möchtest einen Gutschein erwerben oder ein individuelles Weidengeflecht anfragen?'); ?></p>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">person</span>
                    Inhaberin
                </h4>
                <p><?php echo htmlspecialchars($data['texts']['contact_name']); ?></p>
                <p><?php echo htmlspecialchars($data['texts']['contact_street']); ?></p>
                <p><?php echo htmlspecialchars($data['texts']['contact_city']); ?></p>
            </div>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">phone</span>
                    Telefon
                </h4>
                <p><a href="tel:<?php echo str_replace(' ', '', $data['texts']['contact_phone']); ?>" style="color: var(--text-primary);"><?php echo htmlspecialchars($data['texts']['contact_phone']); ?></a></p>
            </div>

            <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons" style="color: var(--primary);">email</span>
                    E-Mail
                </h4>
                <p><a href="mailto:<?php echo htmlspecialchars($data['texts']['contact_email']); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($data['texts']['contact_email']); ?></a></p>
            </div>

            <?php if (!empty($data['texts']['contact_instagram'])): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                    <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons" style="color: #db2777;">photo_camera</span>
                        Instagram
                    </h4>
                    <p>
                        <a href="https://www.instagram.com/<?php echo urlencode($data['texts']['contact_instagram']); ?>/" target="_blank" style="color: #f472b6; font-weight: 600; text-decoration: underline;">
                            @<?php echo htmlspecialchars($data['texts']['contact_instagram']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Interactive Request form inside sidebar -->
            <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <h4 style="color: var(--text-primary); margin-bottom: 1rem;">Nachricht schreiben</h4>
                <form onsubmit="sendRequestEmail(event, '<?php echo htmlspecialchars($data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de', ENT_QUOTES, 'UTF-8'); ?>')">
                    
                    <!-- Honeypot-Falle für Spam-Roboter (für menschliche Besucher unsichtbar) -->
                    <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <label for="req-website">Bitte dieses Feld freilassen</label>
                        <input type="text" id="req-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="req-name">Dein Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="req-name" class="form-control" style="padding:8px 12px;" placeholder="z. B. Maria Müller" required>
                    </div>

                    <div class="form-group">
                        <label for="req-email">Deine E-Mail-Adresse <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="req-email" class="form-control" style="padding:8px 12px;" placeholder="z. B. maria@beispiel.de" required>
                    </div>

                    <div class="form-group">
                        <label for="req-phone">Telefonnummer <small style="color:var(--text-secondary);">(optional für Rückfragen)</small></label>
                        <input type="tel" id="req-phone" class="form-control" style="padding:8px 12px;" placeholder="z. B. 0173 1234567">
                    </div>

                    <div class="form-group">
                        <label for="req-msg">Nachricht <span style="color:#ef4444;">*</span></label>
                        <textarea id="req-msg" class="form-control" rows="5" style="padding:8px 12px;" placeholder="Deine Frage zu Kursen, Sonderanfertigungen oder Körben..." required></textarea>
                    </div>

                    <!-- Spamschutz: Natur- & Flecht-Icon-Auswahl (1 Klick) -->
                    <?php 
                        $server_active_icons = get_active_captcha_icons($data);
                        $initial_keys = array_keys($server_active_icons);
                        $initial_target_key = !empty($initial_keys) ? $initial_keys[array_rand($initial_keys)] : null;
                        $initial_target = $initial_target_key ? $server_active_icons[$initial_target_key] : null;
                    ?>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label id="spam-protection-label" style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 0.9rem;">
                            Sicherheitsprüfung: Klicke auf <strong><?php echo htmlspecialchars($initial_target['prompt'] ?? 'den Weidenzweig 🌿'); ?></strong>
                        </label>
                        <div id="spam-icon-grid" class="spam-icon-grid">
                            <?php foreach ($server_active_icons as $srv_icon): ?>
                                <button type="button" class="spam-icon-btn" title="<?php echo htmlspecialchars($srv_icon['name']); ?>" data-icon-id="<?php echo htmlspecialchars($srv_icon['id']); ?>" onclick="selectSpamIcon('<?php echo htmlspecialchars($srv_icon['id']); ?>', this);">
                                    <span class="spam-icon-symbol"><?php echo $srv_icon['symbol']; ?></span>
                                    <span class="spam-icon-label"><?php echo htmlspecialchars($srv_icon['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="req-selected-icon" value="">
                        <div id="spam-feedback" style="display:none; color: #ef4444; font-size: 0.82rem; margin-top: 6px; font-weight: 500;"></div>
                    </div>

                    <!-- Status-Meldungsbox für Feedback nach dem Senden -->
                    <div id="contact-status-box" style="display:none; margin-bottom: 1rem; padding: 12px 14px; border-radius: 10px; font-size: 0.88rem; line-height: 1.45;"></div>

                    <?php 
                        $smtp_active = !empty($data['settings']['smtp']['enabled']) && !empty($data['settings']['smtp']['host']);
                    ?>
                    <button type="submit" id="contact-submit-btn" class="btn btn-primary" style="width: 100%; padding: 11px; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                        <span class="material-icons" id="contact-submit-icon" style="font-size:18px;"><?php echo $smtp_active ? 'send' : 'mail'; ?></span>
                        <span id="contact-submit-text"><?php echo $smtp_active ? 'Anfrage senden' : 'Im Mailprogramm öffnen'; ?></span>
                    </button>
                    <?php if (!$smtp_active): ?>
                        <small style="color:var(--text-secondary); display:block; text-align:center; margin-top:6px; font-size:0.78rem;">
                            Öffnet dein E-Mail-Programm mit der fertigen Nachricht.
                        </small>
                    <?php endif; ?>
                </form>
            </div>

        </div>
    </div>

    <!-- Slide-in Impressum & Datenschutz Menu (Right) -->
    <label for="menu-toggle-impressum" class="menu-overlay impressum-overlay"></label>
    <div class="slide-menu impressum-menu" id="legal-slide-menu">
        <label for="menu-toggle-impressum" class="close-menu-btn">&times;</label>
        
        <!-- Tab-Umschalter für Rechtliches: Impressum & Datenschutz -->
        <div class="legal-tab-nav" style="display:flex; gap:8px; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1rem; flex-wrap:wrap;">
            <button type="button" id="legal-tab-btn-impressum" class="btn btn-primary" style="padding:6px 14px; font-size:0.85rem;" onclick="switchLegalTab('impressum')">
                Impressum (§ 5 TMG)
            </button>
            <button type="button" id="legal-tab-btn-datenschutz" class="btn btn-outline" style="padding:6px 14px; font-size:0.85rem;" onclick="switchLegalTab('datenschutz')">
                Datenschutz (DSGVO)
            </button>
        </div>

        <!-- Inhaltsbereich: Impressum -->
        <div id="legal-panel-impressum" style="font-size: 0.9rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 1.5rem;">
            <h2 style="color: var(--primary); margin: 0 0 0.5rem 0; font-size: 1.5rem;">Impressum</h2>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Angaben gemäß § 5 TMG</h4>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_name'] ?? ''); ?></p>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_street'] ?? ''); ?></p>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['contact_city'] ?? ''); ?></p>
            </div>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Kontakt</h4>
                <p style="margin: 2px 0;">Telefon: <?php echo htmlspecialchars($data['texts']['contact_phone'] ?? ''); ?></p>
                <p style="margin: 2px 0;">E-Mail: <?php echo htmlspecialchars($data['texts']['contact_email'] ?? ''); ?></p>
            </div>
            <?php if (!empty($data['texts']['impressum_supervisory'])): ?>
                <div>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Zuständige Aufsichtsbehörde / Kammer</h4>
                    <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_supervisory']); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($data['texts']['impressum_vat_id'])): ?>
                <div>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Umsatzsteuer / Steuerhinweis</h4>
                    <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_vat_id']); ?></p>
                </div>
            <?php endif; ?>
            <div>
                <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Haftungsausschluss</h4>
                <p style="margin: 2px 0;"><?php echo htmlspecialchars($data['texts']['impressum_disclaimer'] ?? 'Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.'); ?></p>
            </div>
        </div>

        <!-- Inhaltsbereich: Datenschutzerklärung -->
        <div id="legal-panel-datenschutz" style="display:none; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
            <h2 style="color: var(--primary); margin: 0 0 1rem 0; font-size: 1.5rem;">Datenschutzerklärung (DSGVO)</h2>
            <div class="privacy-content" style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 14px; border: 1px solid var(--border-color);">
                <?php 
                    $cur_privacy_text = !empty($data['settings']['privacy_policy_text']) 
                        ? $data['settings']['privacy_policy_text'] 
                        : (!empty($data['texts']['privacy_policy_text']) ? $data['texts']['privacy_policy_text'] : get_default_privacy_policy_text($data));
                    echo render_markdown_safe($cur_privacy_text);
                ?>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         MOBIL-MENÜ (SLIDE-IN NAVIGATION)
         STRIKTE VORGABE: KEINE ÄNDERUNGEN OHNE AUSDRÜCKLICHE ZUSTIMMUNG DES USERS!
         ====================================================================== -->
    <label for="menu-toggle-mobile" class="menu-overlay mobile-overlay"></label>
    <div class="slide-menu mobile-menu">
        <label for="menu-toggle-mobile" class="close-menu-btn">&times;</label>
        <h2 style="color: var(--primary); margin-bottom: 2rem;">Navigation</h2>
        <nav style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($nav_items as $item): ?>
                <a href="#<?php echo htmlspecialchars($item['id']); ?>" onclick="closeMobileMenu()" class="nav-item" style="font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block;"><?php echo htmlspecialchars($item['nav_label']); ?></a>
            <?php endforeach; ?>
            <label for="menu-toggle" onclick="closeMobileMenuAndOpenContact()" class="nav-item" style="cursor: pointer; font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block; color: var(--primary);">Kontakt</label>
            <label for="menu-toggle-impressum" onclick="closeMobileMenuAndOpenImpressum()" class="nav-item" style="cursor: pointer; font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; display:block; color: var(--primary);">Impressum</label>
        </nav>
    </div>

    <!-- Lightbox Modal -->
    <div id="gallery-lightbox" class="lightbox-modal" onclick="if(event.target === this) closeLightbox();" aria-hidden="true">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Schließen" title="Schließen (Esc)">
            <span class="material-icons">close</span>
        </button>
        <button class="lightbox-nav lightbox-prev" onclick="changeLightbox(-1)" aria-label="Vorheriges Bild" title="Vorheriges Bild (Pfeil links)">
            <span class="material-icons">chevron_left</span>
        </button>
        <button class="lightbox-nav lightbox-next" onclick="changeLightbox(1)" aria-label="Nächstes Bild" title="Nächstes Bild (Pfeil rechts)">
            <span class="material-icons">chevron_right</span>
        </button>
        
        <div class="lightbox-content">
            <div class="lightbox-media-box">
                <img id="lightbox-img" src="" alt="">
            </div>
            <div class="lightbox-caption">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom: 0.4rem;">
                    <h3 id="lightbox-title" style="margin:0; font-size:1.3rem; color:#fff;"></h3>
                    <span id="lightbox-category" class="lightbox-badge"></span>
                </div>
                <p id="lightbox-desc" style="margin:0; font-size:0.95rem; color:#d1d5db; line-height:1.5;"></p>
                <div id="lightbox-counter" style="margin-top:0.5rem; font-size:0.8rem; color:#9ca3af; text-align:right;"></div>
            </div>
        </div>
    </div>

    <!-- Dynamische Server-Daten vor dem Hauptskript bereitstellen -->
    <script>
        window.SERVER_GALLERY_ITEMS = <?php echo json_encode(array_values(array_map(function($item) {
            $raw = $item['image'] ?? '';
            $img_src = (strpos($raw, 'http://') === 0 || strpos($raw, 'https://') === 0)
                ? $raw
                : ((strpos($raw, '/') === 0) ? '.' . $raw : './' . $raw);
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'category' => $item['category'] ?? '',
                'description' => $item['description'] ?? '',
                'image' => $img_src
            ];
        }, $visible_gallery))); ?>;
        
        window.SERVER_SPAM_ICONS = <?php echo json_encode(array_values(get_active_captcha_icons($data))); ?>;
        window.SERVER_SMTP_ENABLED = <?php echo (!empty($data['settings']['smtp']['enabled']) && !empty($data['settings']['smtp']['host'])) ? 'true' : 'false'; ?>;
        window.CONTACT_MAIL_GREETING = <?php echo json_encode($data['texts']['contact_mail_greeting'] ?? 'Hallo Maxima,'); ?>;
        window.COURSE_REQUEST_TEMPLATE = <?php echo json_encode($data['texts']['course_request_template'] ?? "Hallo Maxima,\n\nich interessiere mich für deinen Workshop \"{kurs}\" am {datum} ({uhrzeit}) in {ort} und möchte fragen, ob noch Plätze frei sind.\n\nViele Grüße"); ?>;
    </script>
    
    <!-- Externes Haupt-JavaScript einbinden -->
    <script src="libs/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        if (typeof initGalleryData === 'function') initGalleryData(window.SERVER_GALLERY_ITEMS);
        if (typeof initSpamIcons === 'function') initSpamIcons(window.SERVER_SPAM_ICONS);

        // Umschalten zwischen Impressum und Datenschutzerklärung im Rechts-Slide-In
        function switchLegalTab(tab) {
            const impPanel = document.getElementById('legal-panel-impressum');
            const dsPanel = document.getElementById('legal-panel-datenschutz');
            const impBtn = document.getElementById('legal-tab-btn-impressum');
            const dsBtn = document.getElementById('legal-tab-btn-datenschutz');
            if (!impPanel || !dsPanel) return;

            if (tab === 'datenschutz') {
                impPanel.style.display = 'none';
                dsPanel.style.display = 'block';
                if (impBtn) { impBtn.className = 'btn btn-outline'; }
                if (dsBtn) { dsBtn.className = 'btn btn-primary'; }
            } else {
                impPanel.style.display = 'flex';
                dsPanel.style.display = 'none';
                if (impBtn) { impBtn.className = 'btn btn-primary'; }
                if (dsBtn) { dsBtn.className = 'btn btn-outline'; }
            }
        }

        // Falls Hash #datenschutz in der URL steht, direkt das Datenschutz-Panel aktivieren
        if (window.location.hash === '#datenschutz') {
            const toggle = document.getElementById('menu-toggle-impressum');
            if (toggle) toggle.checked = true;
            switchLegalTab('datenschutz');
        }
    </script>
</body>
</html>
