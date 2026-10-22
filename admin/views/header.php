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
            <div style="display:flex; align-items:center; gap: 0.75rem;">
                <?php if (isset($cur_user) && !empty($cur_user['username'])): ?>
                    <span style="font-size:0.84rem; color:var(--text-secondary); display:inline-flex; align-items:center; gap:5px; background:rgba(0,0,0,0.03); padding:4px 10px; border-radius:20px;">
                        <span class="material-icons" style="font-size:16px;">account_circle</span>
                        <strong><?php echo htmlspecialchars($cur_user['username']); ?></strong>
                    </span>
                <?php endif; ?>
                <a href="../" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Website anzeigen &rarr;</a>
                <a href="?action=logout" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; background:#dc2626;">Abmelden</a>
            </div>
        </div>
    </header>

    <main class="container" style="max-width: 1050px;">
        <h1 style="margin-bottom: 2rem;">Verwaltungspanel</h1>
