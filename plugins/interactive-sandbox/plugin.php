<?php
/**
 * ============================================================================
 * WeaveCMS – Interaktive Sandbox & Live-Demo Plugin Einstiegspunkt
 * ============================================================================
 * Phase 15: Sicherer temporärer Demo-Zugang ohne Persistenz.
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

require_once __DIR__ . '/SandboxManager.php';

// -----------------------------------------------------------------------------
// 1. Demo-URL-Steuerung (?demo=start, ?demo=reset, ?demo=stop)
// -----------------------------------------------------------------------------
$demoParam = $_GET['demo'] ?? '';

if ($demoParam === 'start') {
    SandboxManager::startDemoSession();
    // Sauberen Redirect ohne ?demo=start durchführen
    $inAdmin = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
    $target = $inAdmin ? './?demo=1' : './?demo=1';
    header('Location: ' . $target);
    exit;
}

if ($demoParam === 'reset') {
    SandboxManager::resetDemoSession();
    $inAdmin = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
    $target = $inAdmin ? './?demo=1' : './?demo=1';
    header('Location: ' . $target);
    exit;
}

if ($demoParam === 'stop') {
    SandboxManager::stopDemoSession();
    $inAdmin = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
    $target = $inAdmin ? './' : './';
    header('Location: ' . $target);
    exit;
}

// -----------------------------------------------------------------------------
// 2. Storage-Hook registrieren
// -----------------------------------------------------------------------------
if (class_exists('PluginManager')) {
    PluginManager::addFilter('storage_get_driver', function($currentDriver) {
        if (SandboxManager::isDemoActive()) {
            return SandboxManager::getDriver();
        }
        return $currentDriver;
    }, 5);

    // Einstellungs-Reiter im Admin registrieren
    $renderSettingsCallback = function() {
        $settingsFile = __DIR__ . '/admin/settings.php';
        if (file_exists($settingsFile)) {
            include $settingsFile;
        }
    };
    PluginManager::addAction('admin_render_plugin_settings_interactive-sandbox', $renderSettingsCallback);
    PluginManager::addAction('admin_render_plugin_settings_sandbox', $renderSettingsCallback);
}

// -----------------------------------------------------------------------------
// 3. Admin-Aktion zur manuellen Bereinigung (POST)
// -----------------------------------------------------------------------------
if (!empty($_SESSION['admin_logged_in']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'sandbox_cleanup' && !empty($_POST['csrf_token'])) {
        if (class_exists('AuthManager') && AuthManager::verifyCsrfToken($_POST['csrf_token'])) {
            $cleaned = SandboxManager::cleanupExpired();
            header('Location: ?tab=tab-plugins&plugin_settings=interactive-sandbox&sandbox_msg=' . urlencode("Bereinigung abgeschlossen: {$cleaned} veraltete Sitzung(en) entfernt.") . '#tab-plugins');
            exit;
        }
    }
}
