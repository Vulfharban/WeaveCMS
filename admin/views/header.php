<?php
/**
 * WeaveCMS – Admin Header View
 * 
 * @var string $admin_paw Vorgerendertes SVG-Tatzenlogo
 * @var string $admin_logo_pos Konfigurierte Logo-Position
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration | WeaveCMS</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../libs/css/styles.css?v=<?php echo file_exists(dirname(__DIR__, 2) . '/libs/css/styles.css') ? filemtime(dirname(__DIR__, 2) . '/libs/css/styles.css') : time(); ?>" rel="stylesheet">
</head>
<body class="admin-body">

    <!-- Sticky Header -->
    <header class="header-nav">
        <div class="header-container">
            <span class="logo"><?php if ($admin_logo_pos !== 'header_after'): echo $admin_paw; endif; ?><span>WeaveCMS Admin</span><?php if ($admin_logo_pos === 'header_after'): echo $admin_paw; endif; ?></span>
            <div style="display:flex; align-items:center; gap: 1rem;">
                <a href="../" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Website anzeigen &rarr;</a>
                <a href="?action=logout" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; background:#dc2626;">Abmelden</a>
            </div>
        </div>
    </header>

    <main class="container" style="max-width: 1050px;">
        <h1 style="margin-bottom: 2rem;">Verwaltungspanel</h1>
