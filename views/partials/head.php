<?php
/**
 * ============================================================================
 * WeaveCMS Frontend Partial: Head
 * ============================================================================
 * DOCTYPE, Meta-Tags, OpenGraph, Favicon, Webfonts, Stylesheet & dynamische CSS-Variablen.
 *
 * @var string $meta_title
 * @var string $meta_desc
 * @var string $meta_keywords
 * @var string $meta_robots
 * @var string $canonical_url
 * @var string $contact_name
 * @var string $og_image_url
 * @var string $bg_color
 * @var string $primary_color
 * @var string $accent_color
 * @var string $container_width
 * @var string $font_scale
 * @var string $card_radius
 * @var string $gallery_gap
 */
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($cur_lang ?? 'de'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 1. Basis- & SEO-Metadaten -->
    <title><?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($meta_robots, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- 1.1 Favicon & App-Touch-Icon (Phase 11) -->
    <?php echo LogoRenderer::renderFaviconTags($data); ?>

    <?php if (!empty($active_languages) && count($active_languages) > 1): ?>
        <!-- Mehrsprachige hreflang-Tags für Suchmaschinen -->
        <?php foreach ($active_languages as $aLang): ?>
            <link rel="alternate" hreflang="<?php echo htmlspecialchars($aLang); ?>" href="<?php echo htmlspecialchars(rtrim($site_url, '/') . '/?lang=' . $aLang); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- 2. OpenGraph-Protokoll (WhatsApp, Facebook, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?php echo htmlspecialchars($og_locale ?? 'de_DE'); ?>">
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
    <link href="libs/css/styles.css?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 2) . '/libs/css/styles.css') : time(); ?>" rel="stylesheet">

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
        [data-theme="dark"] {
            --bg-dark: #0f0d0b;
            --bg-card: rgba(22, 19, 17, 0.95);
            --border-color: rgba(255, 255, 255, 0.12);
            --text-primary: #f8f6f4;
            --text-secondary: #c5b8ac;
            --primary: #4ade80;
            --accent: #f09a5c;
            --header-bg: rgba(15, 13, 11, 0.92);
        }
    </style>

    <!-- Sofortige Wiederherstellung von Besucher-Theme & Schriftzoom (Flash-Prävention) -->
    <script>
        (function() {
            try {
                var t = localStorage.getItem('weave_user_theme');
                if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
                var z = localStorage.getItem('weave_user_zoom');
                if (z && parseInt(z, 10) >= 85 && parseInt(z, 10) <= 125) {
                    document.documentElement.style.fontSize = z + '%';
                }
            } catch(e) {}
        })();
    </script>

    <!-- 4. Strukturierte Daten (Schema.org / JSON-LD, Phase 24) -->
    <?php
    if (class_exists('SeoManager')) {
        echo SeoManager::renderJsonLd($data, $site_url, [
            'is_home' => empty($active_post) && empty($active_page) && empty($active_section),
            'active_post' => $active_post ?? null,
            'active_page' => $active_page ?? null,
            'og_image_url' => $og_image_url ?? '',
        ]), "\n";
    }
    ?>
</head>
<?php
    $active_menu_style = class_exists('ThemeManager') ? ThemeManager::getActiveMenuStyle($data) : 'horizontal_minimal';
    $active_sidebar_pos = class_exists('ThemeManager') ? ThemeManager::getSidebarPosition($data) : 'left';
    $frontend_body_classes = ['weave-frontend'];
    $frontend_body_classes[] = 'menu-' . str_replace('_', '-', $active_menu_style);
    if ($active_menu_style === 'vertical_sidebar') {
        $frontend_body_classes[] = 'has-sidebar';
        $frontend_body_classes[] = 'has-sidebar-' . $active_sidebar_pos;
    } else {
        $frontend_body_classes[] = 'has-header-top';
    }
    if (!empty($data['settings']['accessibility_bar_enabled'])) {
        $frontend_body_classes[] = 'has-a11y-bar';
    }
?>
<body class="<?php echo implode(' ', $frontend_body_classes); ?>">
