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
    <link href="libs/css/styles.css?v=<?php echo file_exists(__DIR__ . '/../../libs/css/styles.css') ? filemtime(__DIR__ . '/../../libs/css/styles.css') : time(); ?>" rel="stylesheet">

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
