<?php
/**
 * ============================================================================
 * WeaveCMS – Webspace-Installer & Setup-Assistent (Phase 5 & Phase 7 Vorbereitung)
 * ============================================================================
 * Geführter 5-Schritte-Installationsassistent mit Health-Check,
 * dualer Speichertreiber-Wahl (Flat-File JSON vs. MySQL), Beispieldaten-Seed
 * und automatischer Sperre (installed.lock).
 */

require_once __DIR__ . '/libs/php/Installer.php';
require_once __DIR__ . '/libs/php/LanguageManager.php';
require_once __DIR__ . '/libs/php/storage/StorageManager.php';

$is_locked = Installer::isInstalled();
$lock_data = $is_locked ? Installer::getLockData() : null;
$health = Installer::runHealthCheck();

// AJAX Action: test_db (Live-Datenbankverbindung prüfen)
if (!$is_locked && isset($_GET['action']) && $_GET['action'] === 'test_db') {
    header('Content-Type: application/json');
    $params = [
        'host' => trim($_POST['host'] ?? '127.0.0.1'),
        'port' => (int)($_POST['port'] ?? 3306),
        'database' => trim($_POST['database'] ?? ''),
        'username' => trim($_POST['username'] ?? 'root'),
        'password' => (string)($_POST['password'] ?? ''),
        'create_db' => !empty($_POST['create_db'])
    ];
    echo json_encode(StorageManager::testDbConnection($params));
    exit;
}

$error_msg = '';
$success_redirect = false;

// POST-Verarbeitung der Gesamteinrichtung
if (!$is_locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_install'])) {
    $result = Installer::install($_POST);
    if ($result['success']) {
        $success_redirect = true;
    } else {
        $error_msg = $result['error'] ?? 'Unbekannter Installationsfehler.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeaveCMS – Installations-Assistent</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary: #2e6248;
            --primary-hover: #24503a;
            --primary-light: rgba(46, 98, 72, 0.12);
            --accent: #b26c39;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-main: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }

        .installer-container {
            width: 100%;
            max-width: 860px;
        }

        .installer-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .installer-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 20px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .installer-brand span.logo-icon {
            font-size: 1.6rem;
        }

        .installer-brand span.brand-text {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #34d399, #6ee7b7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .installer-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        .installer-subtitle {
            color: var(--text-secondary);
            font-size: 1.05rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Step Progress */
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .step-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 30px;
            right: 30px;
            height: 2px;
            background: var(--card-border);
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            flex: 1;
        }

        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .step-item.active .step-circle {
            background: var(--primary);
            border-color: #34d399;
            color: #fff;
            box-shadow: 0 0 15px rgba(52, 211, 153, 0.4);
        }

        .step-item.completed .step-circle {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--success);
            color: var(--success);
        }

        .step-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-align: center;
        }

        .step-item.active .step-label {
            color: var(--text-main);
            font-weight: 600;
        }

        /* Step Panes */
        .step-pane {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .step-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
            line-height: 1.5;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        @media (max-width: 680px) {
            .grid-2 { grid-template-columns: 1fr; }
            .step-label { display: none; }
            .card { padding: 1.5rem; }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-control {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #34d399;
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.15);
        }

        /* Health Check Rows */
        .health-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-bottom: 2rem;
        }

        .health-item {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .health-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .health-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-ok {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-warn {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Storage Choice Cards */
        .storage-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        @media (max-width: 600px) {
            .storage-cards { grid-template-columns: 1fr; }
        }

        .storage-card {
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--card-border);
            border-radius: 14px;
            padding: 18px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }

        .storage-card:hover {
            border-color: #64748b;
            transform: translateY(-2px);
        }

        .storage-card.selected {
            border-color: #34d399;
            background: rgba(46, 98, 72, 0.15);
            box-shadow: 0 0 15px rgba(52, 211, 153, 0.2);
        }

        .storage-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .storage-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #34d399;
        }

        .storage-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .storage-card-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .storage-features {
            list-style: none;
            font-size: 0.8rem;
            color: #cbd5e1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .storage-features li {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .storage-features li span.material-icons {
            font-size: 14px;
            color: #34d399;
        }

        /* SQL Drawer */
        .sql-config-drawer {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .sql-config-drawer.open {
            display: block;
        }

        /* Palettes selector */
        .palette-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 580px) {
            .palette-grid { grid-template-columns: 1fr; }
        }

        .palette-card {
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--card-border);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .palette-card:hover {
            border-color: #64748b;
            transform: translateY(-2px);
        }

        .palette-card.selected {
            border-color: #34d399;
            background: rgba(46, 98, 72, 0.15);
        }

        .palette-swatches {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .swatch {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Buttons */
        .btn-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: #2e6248;
            color: #fff;
            box-shadow: 0 4px 12px rgba(46, 98, 72, 0.4);
        }

        .btn-primary:hover {
            background: #24503a;
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid #f87171;
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid #34d399;
            color: #6ee7b7;
        }

        /* Checkbox styling */
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: border-color 0.2s;
        }

        .checkbox-label:hover {
            border-color: #64748b;
        }

        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #34d399;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<div class="installer-container">
    
    <div class="installer-header">
        <div class="installer-brand">
            <span class="logo-icon">🌿</span>
            <span class="brand-text">WeaveCMS v<?php echo htmlspecialchars(Installer::VERSION); ?></span>
        </div>
        <h1 class="installer-title">Installations-Assistent</h1>
        <p class="installer-subtitle">Richte deine moderne, modulare Website in 5 einfachen Schritten ein.</p>
    </div>

    <div class="card">
        
        <?php if ($success_redirect): ?>
            <!-- Erfolgs-Screen mit Weiterleitung -->
            <div style="text-align: center; padding: 2rem 0;">
                <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.2); border: 2px solid var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <span class="material-icons" style="font-size: 48px; color: #34d399;">check</span>
                </div>
                <h2 style="font-size: 1.8rem; margin-bottom: 0.75rem;">Installation erfolgreich abgeschlossen!</h2>
                <p style="color: var(--text-secondary); max-width: 520px; margin: 0 auto 2rem; line-height: 1.6;">
                    Deine Website wurde erfolgreich eingerichtet und gegen wiederholte Aufrufe gesperrt (<code style="color:#34d399; font-family:'JetBrains Mono', monospace;">installed.lock</code> erstellt).
                </p>
                <div style="display:flex; justify-content:center; gap: 1rem;">
                    <a href="admin/?installed=1" class="btn btn-success">
                        <span class="material-icons">dashboard</span> Zum Administrationsbereich
                    </a>
                    <a href="./" class="btn btn-secondary" target="_blank">
                        <span class="material-icons">open_in_new</span> Website öffnen
                    </a>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'admin/?installed=1';
                    }, 2500);
                </script>
            </div>

        <?php elseif ($is_locked): ?>
            <!-- Gesperrter Zustand (System bereits installiert) -->
            <div style="text-align: center; padding: 1.5rem 0;">
                <div style="width: 70px; height: 70px; background: rgba(245, 158, 11, 0.15); border: 2px solid #fbbf24; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                    <span class="material-icons" style="font-size: 36px; color: #fbbf24;">lock</span>
                </div>
                <h2 style="font-size: 1.7rem; margin-bottom: 0.5rem;">WeaveCMS ist bereits installiert</h2>
                <p style="color: var(--text-secondary); max-width: 520px; margin: 0 auto 1.75rem; line-height: 1.6;">
                    Aus Sicherheitsgründen ist dieser Installationsassistent gesperrt. Um das System vollständig zurückzusetzen, lösche bitte die Datei <code style="color:#fbbf24; font-family:'JetBrains Mono', monospace;">installed.lock</code> auf deinem Webspace.
                </p>

                <?php if ($lock_data): ?>
                    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); border-radius: 12px; padding: 1rem; max-width: 440px; margin: 0 auto 2rem; text-align: left; font-size: 0.9rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <span style="color:var(--text-secondary);">Installiert am:</span>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($lock_data['installed_at'] ?? 'Unbekannt'); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <span style="color:var(--text-secondary);">Version:</span>
                            <span style="font-weight:600;">v<?php echo htmlspecialchars($lock_data['version'] ?? Installer::VERSION); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <span style="color:var(--text-secondary);">Speicher-Typ:</span>
                            <span style="font-weight:600; text-transform:uppercase; color:#34d399;"><?php echo htmlspecialchars($lock_data['storage'] ?? 'JSON'); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Administrator:</span>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($lock_data['admin_user'] ?? 'admin'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:center; gap: 1rem;">
                    <a href="admin/" class="btn btn-primary">
                        <span class="material-icons">admin_panel_settings</span> Zum Admin-Bereich
                    </a>
                    <a href="./" class="btn btn-secondary">
                        <span class="material-icons">home</span> Zur Startseite
                    </a>
                </div>
            </div>

        <?php else: ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <span class="material-icons">error</span>
                    <span><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <!-- Progress Header (5 Schritte) -->
            <div class="step-progress">
                <div class="step-item active" id="prog-step-1" onclick="goToStep(1)">
                    <div class="step-circle"><span class="material-icons" style="font-size:20px;">health_and_safety</span></div>
                    <span class="step-label">1. System-Check</span>
                </div>
                <div class="step-item" id="prog-step-2" onclick="goToStep(2)">
                    <div class="step-circle"><span class="material-icons" style="font-size:20px;">storage</span></div>
                    <span class="step-label">2. Speicher</span>
                </div>
                <div class="step-item" id="prog-step-3" onclick="goToStep(3)">
                    <div class="step-circle"><span class="material-icons" style="font-size:20px;">badge</span></div>
                    <span class="step-label">3. Website-Daten</span>
                </div>
                <div class="step-item" id="prog-step-4" onclick="goToStep(4)">
                    <div class="step-circle"><span class="material-icons" style="font-size:20px;">shield</span></div>
                    <span class="step-label">4. Admin-Konto</span>
                </div>
                <div class="step-item" id="prog-step-5" onclick="goToStep(5)">
                    <div class="step-circle"><span class="material-icons" style="font-size:20px;">auto_awesome</span></div>
                    <span class="step-label">5. Beispieldaten</span>
                </div>
            </div>

            <form id="installer-form" method="POST" action="">
                <input type="hidden" name="do_install" value="1">
                <input type="hidden" id="storage_driver_input" name="storage_driver" value="json">
                <input type="hidden" id="theme_palette_input" name="theme_palette" value="nature_classic">

                <!-- ═══════════════════════════════════════════════════════
                     SCHRITT 1: SYSTEM-CHECK (HEALTH CHECK)
                     ═══════════════════════════════════════════════════════ -->
                <div class="step-pane active" id="step-pane-1">
                    <h2 class="form-section-title">
                        <span class="material-icons" style="color: #34d399;">verified</span> System-Voraussetzungen prüfen
                    </h2>
                    <p class="form-section-desc">
                        WeaveCMS prüft automatisch, ob dein Server alle nötigen Module, Datenbank-Treiber und Schreibrechte besitzt.
                    </p>

                    <div class="health-list">
                        <!-- PHP Version -->
                        <div class="health-item">
                            <div class="health-info">
                                <span class="material-icons" style="color: <?php echo $health['php_version']['ok'] ? '#34d399' : '#f87171'; ?>;">
                                    <?php echo $health['php_version']['ok'] ? 'check_circle' : 'cancel'; ?>
                                </span>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($health['php_version']['name']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($health['php_version']['message']); ?></div>
                                </div>
                            </div>
                            <span class="health-badge <?php echo $health['php_version']['ok'] ? 'badge-ok' : 'badge-error'; ?>">
                                <?php echo htmlspecialchars($health['php_version']['current']); ?>
                            </span>
                        </div>

                        <!-- Verzeichnisse -->
                        <?php foreach ($health['directories'] as $d): ?>
                            <div class="health-item">
                                <div class="health-info">
                                    <span class="material-icons" style="color: <?php echo $d['ok'] ? '#34d399' : '#f87171'; ?>;">
                                        <?php echo $d['ok'] ? 'folder_open' : 'folder_off'; ?>
                                    </span>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($d['name']); ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($d['message']); ?></div>
                                    </div>
                                </div>
                                <span class="health-badge <?php echo $d['ok'] ? 'badge-ok' : 'badge-error'; ?>">
                                    <?php echo $d['ok'] ? 'Beschreibbar' : 'Gesperrt'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <!-- PHP Module -->
                        <?php foreach ($health['extensions'] as $ext): ?>
                            <div class="health-item">
                                <div class="health-info">
                                    <span class="material-icons" style="color: <?php echo $ext['ok'] ? '#34d399' : ($ext['required'] ? '#f87171' : '#fbbf24'); ?>;">
                                        <?php echo $ext['ok'] ? 'extension' : 'warning'; ?>
                                    </span>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($ext['name']); ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($ext['message']); ?></div>
                                    </div>
                                </div>
                                <span class="health-badge <?php echo $ext['loaded'] ? 'badge-ok' : ($ext['required'] ? 'badge-error' : 'badge-warn'); ?>">
                                    <?php echo $ext['loaded'] ? 'Aktiv' : ($ext['required'] ? 'Fehlt' : 'Optional'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="btn-row">
                        <div></div>
                        <?php if ($health['can_install']): ?>
                            <button type="button" class="btn btn-primary" onclick="goToStep(2)">
                                Weiter zu Schritt 2 <span class="material-icons">arrow_forward</span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                                Voraussetzungen nicht erfüllt
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SCHRITT 2: DATENSPEICHER-ENGINE WÄHLEN (DUAL-STORAGE)
                     ═══════════════════════════════════════════════════════ -->
                <div class="step-pane" id="step-pane-2">
                    <h2 class="form-section-title">
                        <span class="material-icons" style="color: #34d399;">storage</span> Datenspeicher-Engine wählen
                    </h2>
                    <p class="form-section-desc">
                        Wähle, wie die Inhalte deiner Website gespeichert werden sollen. Du kannst Daten später auch im Adminbereich zwischen JSON und SQL migrieren.
                    </p>

                    <div class="storage-cards">
                        <!-- Flat-File JSON -->
                        <div class="storage-card selected" id="card-storage-json" onclick="selectStorage('json')">
                            <div class="storage-card-header">
                                <div class="storage-card-icon"><span class="material-icons">description</span></div>
                                <span class="health-badge badge-ok">Standard</span>
                            </div>
                            <div class="storage-card-title">Lokale JSON-Datei (Flat-File)</div>
                            <div class="storage-card-desc">
                                Keine Datenbank erforderlich. Alle Daten werden atomar in <code style="color:#34d399; font-family:'JetBrains Mono', monospace;">data.json</code> gespeichert.
                            </div>
                            <ul class="storage-features">
                                <li><span class="material-icons">check</span> Funktioniert auf jedem Standard-Webspace</li>
                                <li><span class="material-icons">check</span> Blitzschnell &amp; wartungsfrei</li>
                                <li><span class="material-icons">check</span> 1-Klick-Backup durch einfaches Kopieren</li>
                            </ul>
                        </div>

                        <!-- MySQL / MariaDB -->
                        <div class="storage-card" id="card-storage-mysql" onclick="selectStorage('mysql')">
                            <div class="storage-card-header">
                                <div class="storage-card-icon" style="color:#38bdf8;"><span class="material-icons">dns</span></div>
                                <?php if (extension_loaded('pdo_mysql')): ?>
                                    <span class="health-badge badge-ok">Verfügbar</span>
                                <?php else: ?>
                                    <span class="health-badge badge-error">pdo_mysql fehlt</span>
                                <?php endif; ?>
                            </div>
                            <div class="storage-card-title">MySQL / MariaDB (SQL)</div>
                            <div class="storage-card-desc">
                                Speichert Inhalte in relationalen Datenbanktabellen via PDO mit vollständiger ACID-Transaktionssicherheit.
                            </div>
                            <ul class="storage-features">
                                <li><span class="material-icons">check</span> Hohe Skalierbarkeit für wachsende Seiten</li>
                                <li><span class="material-icons">check</span> Schutz vor Schreibkonflikten bei vielen Autoren</li>
                                <li><span class="material-icons">check</span> Automatische Tabellen-Erstellung</li>
                            </ul>
                        </div>
                    </div>

                    <!-- MySQL Drawer (nur sichtbar wenn MySQL aktiv ist) -->
                    <div id="mysql-drawer" class="sql-config-drawer">
                        <h3 style="font-size:1.1rem; color: #38bdf8; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
                            <span class="material-icons">settings_suggest</span> MySQL-Verbindungsdaten eingeben
                        </h3>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="db_host">Datenbank-Host *</label>
                                <input type="text" id="db_host" name="db_host" class="form-control" value="127.0.0.1" placeholder="localhost oder 127.0.0.1">
                            </div>
                            <div class="form-group">
                                <label for="db_port">Port</label>
                                <input type="number" id="db_port" name="db_port" class="form-control" value="3306" placeholder="3306">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="db_name">Datenbankname *</label>
                                <input type="text" id="db_name" name="db_name" class="form-control" value="weavecms" placeholder="z. B. weavecms">
                            </div>
                            <div class="form-group">
                                <label for="db_prefix">Tabellen-Präfix</label>
                                <input type="text" id="db_prefix" name="db_prefix" class="form-control" value="weave_" placeholder="weave_">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="db_user">Benutzername *</label>
                                <input type="text" id="db_user" name="db_user" class="form-control" value="root" placeholder="z. B. root oder db_user">
                            </div>
                            <div class="form-group">
                                <label for="db_pass">Datenbank-Passwort</label>
                                <input type="password" id="db_pass" name="db_pass" class="form-control" placeholder="Passwort (falls vorhanden)">
                            </div>
                        </div>

                        <label class="checkbox-label" style="margin-top:0.5rem; margin-bottom:1.25rem; padding: 12px;">
                            <input type="checkbox" id="db_create_if_missing" name="db_create_if_missing" value="1" checked>
                            <span style="font-size: 0.9rem; color:#cbd5e1;">Datenbank automatisch erzeugen, falls sie noch nicht existiert</span>
                        </label>

                        <div style="display:flex; align-items:center; gap: 12px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" id="btn-test-db" onclick="testDatabaseConnection()">
                                <span class="material-icons" style="font-size:18px;">bolt</span> Verbindung testen
                            </button>
                            <div id="db-test-feedback" style="font-size:0.9rem; display:none; align-items:center; gap:6px;"></div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                            <span class="material-icons">arrow_back</span> Zurück
                        </button>
                        <button type="button" class="btn btn-primary" onclick="validateStep2()">
                            Weiter zu Schritt 3 <span class="material-icons">arrow_forward</span>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SCHRITT 3: WEBSITE-GRUNDDATEN
                     ═══════════════════════════════════════════════════════ -->
                <div class="step-pane" id="step-pane-3">
                    <h2 class="form-section-title">
                        <span class="material-icons" style="color: #34d399;">storefront</span> Website- &amp; Kontaktdaten
                    </h2>
                    <p class="form-section-desc">
                        Diese Daten werden für den Header, Impressum und Kontaktformulare verwendet (jederzeit im Adminbereich anpassbar).
                    </p>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="site_name">Website- / Atelier-Name *</label>
                            <input type="text" id="site_name" name="site_name" class="form-control" value="Atelier Musterfrau" placeholder="z. B. Atelier Flechtwerk" required>
                        </div>
                        <div class="form-group">
                            <label for="site_slogan">Slogan / Untertitel</label>
                            <input type="text" id="site_slogan" name="site_slogan" class="form-control" value="Handgefertigte Unikate &amp; Kreativ-Workshops" placeholder="z. B. Zeitloses Handwerk &amp; Kurse">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="owner_name">Inhaber / Betreiber Name *</label>
                            <input type="text" id="owner_name" name="owner_name" class="form-control" value="Maxima Musterfrau" placeholder="Vor- und Nachname" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_email">E-Mail-Adresse für Kontakt *</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control" value="kontakt@maxima-musterfrau.de" placeholder="info@meine-domain.de" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="contact_street">Straße &amp; Hausnummer (optional)</label>
                            <input type="text" id="contact_street" name="contact_street" class="form-control" value="Musterstraße 12" placeholder="z. B. Lindenweg 4">
                        </div>
                        <div class="form-group">
                            <label for="contact_city">PLZ &amp; Ort (optional)</label>
                            <input type="text" id="contact_city" name="contact_city" class="form-control" value="12345 Musterstadt" placeholder="z. B. 10115 Berlin">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Telefonnummer (optional)</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="+49 123 4567890" placeholder="+49 170 1234567">
                    </div>

                    <!-- Phase 8: Mehrsprachigkeit & Primärsprache -->
                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--card-border);">
                        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 8px;">
                            <span class="material-icons" style="color: #34d399; font-size: 20px;">translate</span> Mehrsprachigkeit &amp; Website-Sprachen
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1rem; line-height: 1.4;">
                            Lege die Hauptsprache deiner Website fest. Weitere Sprachen können optional aktiviert werden (Übersetzungen lassen sich im Adminbereich pflegen).
                        </p>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="primary_language">Hauptsprache der Website *</label>
                                <select id="primary_language" name="primary_language" class="form-control" style="background:#0f172a;" onchange="updateInstallerLangs()">
                                    <?php foreach (LanguageManager::getSupportedLanguages() as $code => $info): ?>
                                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $code === 'de' ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($info['flag'] . ' ' . $info['native'] . ' (' . $info['name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="display:block; margin-bottom: 6px;">Zusatzsprachen aktivieren (optional)</label>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; max-height: 130px; overflow-y: auto; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); border-radius: 8px;">
                                    <?php foreach (LanguageManager::getSupportedLanguages() as $code => $info): ?>
                                        <label style="display: flex; align-items: center; gap: 6px; font-size: 0.82rem; cursor: pointer; color: #cbd5e1;">
                                            <input type="checkbox" name="active_languages[]" value="<?php echo htmlspecialchars($code); ?>" class="installer-lang-checkbox" data-lang="<?php echo htmlspecialchars($code); ?>" <?php echo $code === 'de' ? 'checked disabled' : ''; ?>>
                                            <span><?php echo htmlspecialchars($info['flag'] . ' ' . $info['native']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                            <span class="material-icons">arrow_back</span> Zurück
                        </button>
                        <button type="button" class="btn btn-primary" onclick="validateStep3()">
                            Weiter zu Schritt 4 <span class="material-icons">arrow_forward</span>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SCHRITT 4: ADMINISTRATOR-KONTO
                     ═══════════════════════════════════════════════════════ -->
                <div class="step-pane" id="step-pane-4">
                    <h2 class="form-section-title">
                        <span class="material-icons" style="color: #34d399;">lock</span> Administrator-Zugangsdaten
                    </h2>
                    <p class="form-section-desc">
                        WeaveCMS nutzt ein sicheres Zweistufen-System: Ein starkes Passwort für den Login und eine PIN für sensible Aktionen (z. B. Backup-Restore).
                    </p>

                    <div class="form-group">
                        <label for="admin_user">Administrator-Benutzername *</label>
                        <input type="text" id="admin_user" name="admin_user" class="form-control" value="admin" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="admin_pass">Passwort (mindestens 8 Zeichen) *</label>
                            <div style="position:relative;">
                                <input type="password" id="admin_pass" name="admin_pass" class="form-control" style="padding-right: 42px;" placeholder="Mindestens 8 Zeichen" required>
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer;" onclick="toggleVis('admin_pass')">visibility</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="admin_pass_confirm">Passwort bestätigen *</label>
                            <div style="position:relative;">
                                <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" class="form-control" style="padding-right: 42px;" placeholder="Passwort wiederholen" required>
                                <span class="material-icons" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer;" onclick="toggleVis('admin_pass_confirm')">visibility</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="admin_pin">Sicherheits-PIN (4 bis 6 Ziffern) *</label>
                            <input type="password" id="admin_pin" name="admin_pin" class="form-control" maxlength="6" pattern="[0-9]{4,6}" placeholder="z. B. 1977" required>
                            <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:4px;">Wird für Notfall-Wiederherstellungen benötigt.</div>
                        </div>
                        <div class="form-group">
                            <label for="admin_pin_confirm">Sicherheits-PIN bestätigen *</label>
                            <input type="password" id="admin_pin_confirm" name="admin_pin_confirm" class="form-control" maxlength="6" pattern="[0-9]{4,6}" placeholder="PIN wiederholen" required>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(3)">
                            <span class="material-icons">arrow_back</span> Zurück
                        </button>
                        <button type="button" class="btn btn-primary" onclick="validateStep4()">
                            Weiter zu Schritt 5 <span class="material-icons">arrow_forward</span>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SCHRITT 5: BEISPIELDATEN & DESIGN
                     ═══════════════════════════════════════════════════════ -->
                <div class="step-pane" id="step-pane-5">
                    <h2 class="form-section-title">
                        <span class="material-icons" style="color: #34d399;">palette</span> Beispieldaten &amp; Design-Thema
                    </h2>
                    <p class="form-section-desc">
                        Wähle dein bevorzugtes Farbschema und entscheide, ob du mit fertigen Beispielen starten möchtest.
                    </p>

                    <!-- Seed Checkbox -->
                    <label class="checkbox-label">
                        <input type="checkbox" id="install_seed" name="install_seed" value="1" checked>
                        <div>
                            <div style="font-weight: 600; font-size: 1rem; color: #fff; margin-bottom: 2px;">
                                🌿 Muster-Inhalte installieren (Empfohlen für neue Websites)
                            </div>
                            <div style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.4;">
                                Erstellt Galerie-Kategorien, Beispiel-Werkstücke, Workshops und vorformatierte Sektionstexte. Du kannst alles später nach Belieben anpassen oder löschen.
                            </div>
                        </div>
                    </label>

                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.95rem; font-weight: 600;">Start-Farbpalette wählen:</label>
                    <div class="palette-grid">
                        <!-- Weidenglück -->
                        <div class="palette-card selected" onclick="pickPalette(this, 'nature_classic')">
                            <div style="font-weight: 700; font-size: 0.95rem;">Weidenglück (Natur)</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Harmonisches Waldgrün &amp; Bernstein</div>
                            <div class="palette-swatches">
                                <div class="swatch" style="background: #2e6248;"></div>
                                <div class="swatch" style="background: #b26c39;"></div>
                                <div class="swatch" style="background: #fbf9f6;"></div>
                            </div>
                        </div>

                        <!-- Moor & Bernstein -->
                        <div class="palette-card" onclick="pickPalette(this, 'moor_amber')">
                            <div style="font-weight: 700; font-size: 0.95rem;">Sand &amp; Bernstein</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Erdfarben &amp; warmer Goldton</div>
                            <div class="palette-swatches">
                                <div class="swatch" style="background: #4a3f35;"></div>
                                <div class="swatch" style="background: #d97706;"></div>
                                <div class="swatch" style="background: #f7f4ee;"></div>
                            </div>
                        </div>

                        <!-- Nordisch Sage -->
                        <div class="palette-card" onclick="pickPalette(this, 'nordic_sage')">
                            <div style="font-weight: 700; font-size: 0.95rem;">Nordisch Klar</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Salbeigrün &amp; dunkles Schieferblau</div>
                            <div class="palette-swatches">
                                <div class="swatch" style="background: #3a6351;"></div>
                                <div class="swatch" style="background: #2c3e50;"></div>
                                <div class="swatch" style="background: #f4f6f5;"></div>
                            </div>
                        </div>

                        <!-- Warmes Atelier -->
                        <div class="palette-card" onclick="pickPalette(this, 'terracotta_atelier')">
                            <div style="font-weight: 700; font-size: 0.95rem;">Warmes Atelier</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Terracotta &amp; Kaffeebraun</div>
                            <div class="palette-swatches">
                                <div class="swatch" style="background: #a85832;"></div>
                                <div class="swatch" style="background: #322822;"></div>
                                <div class="swatch" style="background: #faf7f2;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(4)">
                            <span class="material-icons">arrow_back</span> Zurück
                        </button>
                        <button type="submit" class="btn btn-success" id="submit-install-btn">
                            <span class="material-icons">rocket_launch</span> Jetzt WeaveCMS installieren
                        </button>
                    </div>
                </div>

            </form>

        <?php endif; ?>

    </div>

    <div style="text-align: center; margin-top: 1.5rem; color: #64748b; font-size: 0.85rem;">
        WeaveCMS &copy; <?php echo date('Y'); ?> &bull; Leichtgewichtiges, modulares Webseiten-System
    </div>

</div>

<script>
let currentStep = 1;

function goToStep(step) {
    if (step < 1 || step > 5) return;
    
    // Validierung bei Sprüngen nach vorn
    if (step > currentStep) {
        if (currentStep === 2 && !validateStep2(true)) return;
        if (currentStep === 3 && !validateStep3(true)) return;
        if (currentStep === 4 && !validateStep4(true)) return;
    }

    currentStep = step;

    // Panes umschalten
    document.querySelectorAll('.step-pane').forEach((p, idx) => {
        p.classList.toggle('active', (idx + 1) === step);
    });

    // Progress-Leiste umschalten
    for (let i = 1; i <= 5; i++) {
        const item = document.getElementById('prog-step-' + i);
        if (!item) continue;
        item.classList.remove('active', 'completed');
        if (i === step) {
            item.classList.add('active');
        } else if (i < step) {
            item.classList.add('completed');
        }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function selectStorage(type) {
    document.getElementById('card-storage-json').classList.toggle('selected', type === 'json');
    document.getElementById('card-storage-mysql').classList.toggle('selected', type === 'mysql');
    document.getElementById('storage_driver_input').value = type;

    const drawer = document.getElementById('mysql-drawer');
    if (drawer) {
        drawer.classList.toggle('open', type === 'mysql');
    }
}

function validateStep2(silent = false) {
    const driver = document.getElementById('storage_driver_input').value;
    if (driver === 'mysql') {
        const host = document.getElementById('db_host').value.trim();
        const db = document.getElementById('db_name').value.trim();
        const user = document.getElementById('db_user').value.trim();

        if (!host || !db || !user) {
            if (!silent) alert('Bitte fülle für MySQL mindestens Host, Datenbankname und Benutzername aus.');
            return false;
        }
    }
    if (!silent) goToStep(3);
    return true;
}

function validateStep3(silent = false) {
    const siteName = document.getElementById('site_name').value.trim();
    const ownerName = document.getElementById('owner_name').value.trim();
    const email = document.getElementById('contact_email').value.trim();

    if (!siteName) {
        if (!silent) alert('Bitte gib einen Website- / Atelier-Namen ein.');
        document.getElementById('site_name').focus();
        return false;
    }
    if (!ownerName) {
        if (!silent) alert('Bitte gib den Namen des Inhabers / Betreibers ein.');
        document.getElementById('owner_name').focus();
        return false;
    }
    if (!email || !email.includes('@')) {
        if (!silent) alert('Bitte gib eine gültige E-Mail-Adresse ein.');
        document.getElementById('contact_email').focus();
        return false;
    }

    if (!silent) goToStep(4);
    return true;
}

function validateStep4(silent = false) {
    const pass = document.getElementById('admin_pass').value;
    const passConf = document.getElementById('admin_pass_confirm').value;
    const pin = document.getElementById('admin_pin').value.trim();
    const pinConf = document.getElementById('admin_pin_confirm').value.trim();

    if (pass.length < 8) {
        if (!silent) alert('Das Administrator-Passwort muss mindestens 8 Zeichen lang sein.');
        document.getElementById('admin_pass').focus();
        return false;
    }
    if (pass !== passConf) {
        if (!silent) alert('Die Passwörter stimmen nicht überein.');
        document.getElementById('admin_pass_confirm').focus();
        return false;
    }
    if (!/^[0-9]{4,6}$/.test(pin)) {
        if (!silent) alert('Die Sicherheits-PIN muss aus 4 bis 6 Ziffern bestehen.');
        document.getElementById('admin_pin').focus();
        return false;
    }
    if (pin !== pinConf) {
        if (!silent) alert('Die Sicherheits-PINs stimmen nicht überein.');
        document.getElementById('admin_pin_confirm').focus();
        return false;
    }

    if (!silent) goToStep(5);
    return true;
}

function testDatabaseConnection() {
    const btn = document.getElementById('btn-test-db');
    const fb = document.getElementById('db-test-feedback');
    if (!btn || !fb) return;

    const host = document.getElementById('db_host').value.trim();
    const port = document.getElementById('db_port').value.trim();
    const db = document.getElementById('db_name').value.trim();
    const user = document.getElementById('db_user').value.trim();
    const pass = document.getElementById('db_pass').value;
    const createDb = document.getElementById('db_create_if_missing').checked ? '1' : '0';

    if (!host || !db || !user) {
        fb.style.display = 'inline-flex';
        fb.style.color = '#f87171';
        fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> Bitte Host, Datenbankname und Benutzer angeben.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="material-icons" style="font-size:18px; animation: spin 1s linear infinite;">sync</span> Prüfe...';
    fb.style.display = 'none';

    const formData = new URLSearchParams();
    formData.append('host', host);
    formData.append('port', port);
    formData.append('database', db);
    formData.append('username', user);
    formData.append('password', pass);
    formData.append('create_db', createDb);

    fetch('install.php?action=test_db', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons" style="font-size:18px;">bolt</span> Verbindung testen';
        fb.style.display = 'inline-flex';
        if (data.success) {
            fb.style.color = '#34d399';
            fb.innerHTML = '<span class="material-icons" style="font-size:16px;">check_circle</span> ' + escapeHtml(data.message);
        } else {
            fb.style.color = '#f87171';
            fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> ' + escapeHtml(data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons" style="font-size:18px;">bolt</span> Verbindung testen';
        fb.style.display = 'inline-flex';
        fb.style.color = '#f87171';
        fb.innerHTML = '<span class="material-icons" style="font-size:16px;">error</span> Serverfehler beim Verbindungstest.';
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function pickPalette(card, paletteKey) {
    document.querySelectorAll('.palette-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('theme_palette_input').value = paletteKey;
}

function toggleVis(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = (el.type === 'password') ? 'text' : 'password';
}

function updateInstallerLangs() {
    const primary = document.getElementById('primary_language').value;
    document.querySelectorAll('.installer-lang-checkbox').forEach(cb => {
        if (cb.dataset.lang === primary) {
            cb.checked = true;
            cb.disabled = true;
        } else {
            cb.disabled = false;
        }
    });
}
</script>

</body>
</html>
