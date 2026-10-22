<?php
/**
 * ============================================================================
 * WeaveCMS - Zentrale Konfiguration & Bootstrap (config.php)
 * ============================================================================
 * Initialisiert Sitzungen, lädt alle Kern-Klassen und stellt rückwärtskompatible
 * Hilfsfunktionen für Templates und Module bereit.
 */

// 1. Kern-Sicherheitsmanager laden, Sicherheitsheader senden & gehärtete Session starten (Phase 9)
require_once __DIR__ . '/libs/php/SecurityManager.php';
SecurityManager::applySecurityHeaders();
SecurityManager::initSecureSession();

// 2. Standard-Fallback-Zugangsdaten & Tokens
define('DEFAULT_ADMIN_PASSWORD', 'admin123123');
define('DEFAULT_ADMIN_PIN', '1977');
define('ADMIN_TOKEN', 'xyz123'); // Beibehalten für direkten Zugriff via URL-Token falls benötigt

// 3. Lokale Umgebungskonfiguration laden (falls vorhanden)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// 4. Modulare Kern-Klassen & Service-Manager laden
require_once __DIR__ . '/libs/php/storage/StorageManager.php';
require_once __DIR__ . '/libs/php/BackupManager.php';
require_once __DIR__ . '/libs/php/Installer.php';
require_once __DIR__ . '/libs/php/SectionManager.php';
require_once __DIR__ . '/libs/php/AuthManager.php';
require_once __DIR__ . '/libs/php/ImageUploader.php';
require_once __DIR__ . '/libs/php/CaptchaManager.php';
require_once __DIR__ . '/libs/php/LogoRenderer.php';
require_once __DIR__ . '/libs/php/MarkdownParser.php';
require_once __DIR__ . '/libs/php/ContactInbox.php';
require_once __DIR__ . '/libs/php/UserManager.php';
require_once __DIR__ . '/libs/php/StatsManager.php';
require_once __DIR__ . '/libs/php/AdminController.php';
require_once __DIR__ . '/libs/php/LanguageManager.php';
require_once __DIR__ . '/libs/php/FrontendController.php';

/**
 * Automatische Weiterleitung auf install.php, wenn WeaveCMS noch nicht installiert ist (Phase 5).
 * @return void
 */
function check_installer_redirection(): void {
    if (php_sapi_name() === 'cli') {
        return;
    }
    
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'install.php') {
        return;
    }
    
    if (!Installer::isInstalled()) {
        $in_admin = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'admin');
        $redirect_url = $in_admin ? '../install.php' : './install.php';
        header('Location: ' . $redirect_url);
        exit;
    }
}
check_installer_redirection();

/**
 * Lädt Daten aus dem aktiven Speichertreiber (Flat-File JSON oder SQL) oder direkt aus einer JSON-Datei.
 * @param string $filepath Absoluter oder relativer Dateipfad zur JSON-Datei
 * @param array $default Fallback-Array, falls Datei nicht existiert oder defekt ist
 * @return array Geladene Daten
 */
function load_data(string $filepath, array $default = []): array {
    if (basename($filepath) === 'data.json') {
        $driver = StorageManager::getDriver();
        $data = $driver->loadData();
        return !empty($data) ? $data : $default;
    }

    if (!file_exists($filepath)) {
        return $default;
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

/**
 * Speichert Daten im aktiven Speichertreiber (Flat-File JSON oder SQL) oder direkt formatiert in eine Datei.
 * @param string $filepath Zielpfad der JSON-Datei
 * @param array $data Zu speicherndes Daten-Array
 * @return bool|int true/Bytes bei Erfolg oder false bei Fehler
 */
function save_data(string $filepath, array $data) {
    if (basename($filepath) === 'data.json') {
        $driver = StorageManager::getDriver();
        return $driver->saveData($data);
    }

    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Ermittelt die konfigurierten Upload-Pfade (relativen Webpfad und absoluten Serverpfad).
 * Proxy zu ImageUploader::getUploadConfig().
 * @param array|null $data
 * @return array ['rel' => string, 'abs' => string]
 */
function get_upload_config(?array $data = null): array {
    return ImageUploader::getUploadConfig($data);
}

/**
 * Führt einen sicheren Bildupload mit Dateiendungsprüfung und eindeutiger Namensvergabe durch.
 * Proxy zu ImageUploader::upload().
 * @param string $file_input_name Name des POST-File-Inputs
 * @param string|null $upload_rel_dir Optionaler individueller Zielordner
 * @return string|null Relativer Webpfad zur gespeicherten Datei oder null bei Fehler
 */
function handle_image_upload(string $file_input_name, ?string $upload_rel_dir = null): ?string {
    return ImageUploader::upload($file_input_name, $upload_rel_dir);
}

/**
 * Verifiziert die Admin-Authentifizierung anhand von Passwort und PIN-Code.
 * Proxy zu AuthManager::verifyAdminAuth().
 * @param string $password
 * @param string $pin
 * @param array|null $data
 * @return bool
 */
function verify_admin_auth(string $password, string $pin, ?array $data = null): bool {
    return AuthManager::verifyAdminAuth($password, $pin, $data);
}

/**
 * Verifiziert ausschließlich den PIN-Code gegen den gespeicherten Hash oder Fallbacks.
 * Proxy zu AuthManager::verifyAdminPin().
 * @param string $pin
 * @param array|null $data
 * @return bool
 */
function verify_admin_pin(string $pin, ?array $data = null): bool {
    return AuthManager::verifyAdminPin($pin, $data);
}

/**
 * Rendert das anpassbare SVG-Hundetatzen-Logo für den Header oder die Hero-Zeile.
 * Proxy zu LogoRenderer::renderPawLogo().
 * @param string $motif
 * @param string $color
 * @param string|int $size
 * @param string $extra_class
 * @return string
 */
function render_paw_logo(string $motif = 'paw_simple', string $color = '#b26c39', $size = '1.15em', string $extra_class = ''): string {
    return LogoRenderer::renderPawLogo($motif, $color, $size, $extra_class);
}

/**
 * Liefert alle verfügbaren Logo-Motive.
 * Proxy zu LogoRenderer::getAllMotifs().
 * @return array
 */
function get_all_paw_motifs(): array {
    return LogoRenderer::getAllMotifs();
}

/**
 * Liefert den vollständigen Pool aller 12 Spamschutz-Symbole.
 * Proxy zu CaptchaManager::getAllIcons().
 * @return array
 */
function get_all_captcha_icons(): array {
    return CaptchaManager::getAllIcons();
}

/**
 * Liefert die 4 vom Administrator konfigurierten aktiven Spamschutz-Symbole.
 * Proxy zu CaptchaManager::getActiveIcons().
 * @param array|null $data
 * @return array
 */
function get_active_captcha_icons(?array $data = null): array {
    return CaptchaManager::getActiveIcons($data);
}

/**
 * Konvertiert Markdown in sicheren HTML-Code.
 * Proxy zu MarkdownParser::parse().
 * @param string|null $text
 * @return string
 */
function render_markdown_safe(?string $text): string {
    return MarkdownParser::parse($text);
}

/**
 * Erzeugt den standardisierten Muster-Datenschutzerklärungstext mit dynamischen Inhaberdaten.
 * Proxy zu MarkdownParser::getDefaultPrivacyPolicyText().
 * @param array $data
 * @return string
 */
function get_default_privacy_policy_text(array $data = []): string {
    return MarkdownParser::getDefaultPrivacyPolicyText($data);
}

/**
 * Übersetzt einen Textschlüssel anhand der aktiven Besuchersprache.
 * Proxy zu LanguageManager::t().
 *
 * @param string $key Übersetzungsschlüssel
 * @param string $default Fallback-Text
 * @param string|null $lang Optional abweichende Sprache
 * @return string
 */
function __(string $key, string $default = '', ?string $lang = null): string {
    return LanguageManager::t($key, $default, $lang);
}

/**
 * Alias für __().
 *
 * @param string $key
 * @param string $default
 * @param string|null $lang
 * @return string
 */
function _t(string $key, string $default = '', ?string $lang = null): string {
    return LanguageManager::t($key, $default, $lang);
}
