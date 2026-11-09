<?php
/**
 * Weave Helpdesk – Haupt-Anwendungs-Shell
 */

$user = HelpdeskUserManager::getCurrentUser();
if (!$user) {
    require __DIR__ . '/login.php';
    return;
}

$view = $_GET['view'] ?? 'list';
$activeTicketId = $_GET['id'] ?? '';
$provider = HelpdeskManager::getServiceProviderDetails();
$pluginUrl = PluginManager::getPluginUrl('weave-helpdesk');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($provider['name']) ?> – Helpdesk Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($pluginUrl) ?>/assets/css/helpdesk.css">
    <script src="<?= htmlspecialchars($pluginUrl) ?>/assets/js/helpdesk.js" defer></script>
</head>
<body class="helpdesk-app-body">

<!-- Obere Navigationsleiste (Screenshot 1-4) -->
<header class="hd-navbar">
    <div class="hd-nav-tabs">
        <a href="?page=helpdesk" class="hd-brand-title">
            <span>🛡️</span>
            <span><?= htmlspecialchars($provider['name']) ?> HelpDesk</span>
        </a>

        <!-- Tab 1: Dashboard / Aufgabenliste -->
        <a href="?page=helpdesk" class="hd-tab-link <?= ($view === 'list') ? 'active' : '' ?>">
            <span>📊 Dashboard</span>
        </a>

        <!-- Tab 2: Neue Aufgabe -->
        <?php if ($view === 'create'): ?>
            <a href="?page=helpdesk&view=create" class="hd-tab-link active">
                <span>➕ Neue Aufgabe</span>
                <span class="hd-tab-close" onclick="location.href='?page=helpdesk'; return false;">&times;</span>
            </a>
        <?php endif; ?>

        <!-- Tab 3: Geöffnete Aufgabe -->
        <?php if ($view === 'detail' && !empty($activeTicketId)): ?>
            <a href="?page=helpdesk&view=detail&id=<?= urlencode($activeTicketId) ?>" class="hd-tab-link active">
                <span>📋 <?= htmlspecialchars($activeTicketId) ?></span>
                <span class="hd-tab-close" onclick="location.href='?page=helpdesk'; return false;">&times;</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Benutzer-Menü oben rechts (Screenshot 3) -->
    <div class="hd-user-menu">
        <button type="button" id="hdUserToggle" class="hd-user-toggle">
            <span>👤</span>
            <span><?= htmlspecialchars($user['name']) ?></span>
            <span style="font-size: 10px;">▼</span>
        </button>

        <div id="hdUserDropdown" class="hd-dropdown-menu">
            <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #edf2f7; font-size: 11px;">
                <strong><?= htmlspecialchars($user['email']) ?></strong><br>
                <span style="color: #718096;">
                    Rolle: <?= ($user['role'] === 'admin') ? 'Administrator' : (($user['role'] === 'agent') ? 'Mitarbeiter (Support)' : 'Kunde') ?>
                    <?php if (!empty($user['company_name'])): ?>
                        <br>Firma: <?= htmlspecialchars($user['company_name']) ?>
                    <?php endif; ?>
                </span>
            </div>

            <a href="?page=helpdesk" class="hd-dropdown-item">
                <span>💬</span>
                <span>Benachrichtigungen & Aufgaben</span>
            </a>

            <a href="./" class="hd-dropdown-item">
                <span>🌐</span>
                <span>Zurück zur Website</span>
            </a>

            <?php if (!empty($user['is_cms_admin'])): ?>
                <a href="admin/" class="hd-dropdown-item" style="color: #257985;">
                    <span>⚙️</span>
                    <span>WeaveCMS Backend</span>
                </a>
            <?php endif; ?>

            <a href="#about" class="hd-dropdown-item" onclick="alert('Weave Helpdesk v1.0.0\nEntwickelt für WeaveCMS.\nBetreiber: <?= addslashes($provider['name']) ?>'); return false;">
                <span>ℹ️</span>
                <span>Über Weave Helpdesk</span>
            </a>

            <a href="?page=helpdesk&action=logout" class="hd-dropdown-item logout" style="color: #e74c3c;">
                <span>🚪</span>
                <span>Abmelden</span>
            </a>
        </div>
    </div>
</header>

<!-- Hauptinhalt je nach View -->
<main>
    <?php
    if ($view === 'create') {
        require __DIR__ . '/task_create.php';
    } elseif ($view === 'detail') {
        require __DIR__ . '/task_detail.php';
    } else {
        require __DIR__ . '/task_list.php';
    }
    ?>
</main>

</body>
</html>

