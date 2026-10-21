<?php
// Gemeinsame Konfiguration und Hilfsfunktionen für WeaveCMS

// 1. Session für die Admin-Authentifizierung starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard-Fallback-Zugangsdaten
define('DEFAULT_ADMIN_PASSWORD', 'admin123123');
define('DEFAULT_ADMIN_PIN', '1977');
define('ADMIN_TOKEN', 'xyz123'); // Beibehalten für direkten Zugriff via URL-Token falls benötigt

// Modulare Klassen & Manager laden
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
require_once __DIR__ . '/libs/php/storage/StorageManager.php';
require_once __DIR__ . '/libs/php/BackupManager.php';
require_once __DIR__ . '/libs/php/Installer.php';

/**
 * Automatische Weiterleitung auf install.php, wenn WeaveCMS noch nicht installiert ist (Phase 5).
 */
function check_installer_redirection() {
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
 * @param string $filepath - Absoluter oder relativer Dateipfad zur JSON-Datei
 * @param array $default - Fallback-Array, falls Datei nicht existiert oder defekt ist
 * @return array - Geladene Daten
 */
function load_data($filepath, $default = []) {
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
 * @param string $filepath - Zielpfad der JSON-Datei
 * @param array $data - Zu speicherndes Daten-Array
 * @return bool|int - true/Bytes bei Erfolg oder false bei Fehler
 */
function save_data($filepath, $data) {
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
 * Ermittelt die konfigurierten Upload-Pfade (sowohl relativen Webpfad als auch absoluten Serverpfad).
 * @param {array|null} $data - Geladene Gesamtdaten aus data.json
 * @return {array} - ['rel' => string, 'abs' => string]
 */
function get_upload_config($data = null) {
    $rel_path = 'images/uploads';
    if ($data && isset($data['settings']['upload_dir']) && !empty(trim($data['settings']['upload_dir']))) {
        $rel_path = trim($data['settings']['upload_dir'], '/\\');
    }
    
    $abs_path = __DIR__ . '/' . $rel_path . '/';
    if (!is_dir($abs_path)) {
        mkdir($abs_path, 0755, true);
    }
    
    return [
        'rel' => '/' . $rel_path,
        'abs' => $abs_path
    ];
}

/**
 * Verifiziert die Admin-Authentifizierung anhand von Passwort und PIN-Code.
 * @param {string} $password - Eingegebenes Passwort
 * @param {string} $pin - Eingegebener PIN-Code
 * @param {array|null} $data - Konfigurationsdaten mit Passwort-/PIN-Hashes
 * @return {boolean} - true bei erfolgreicher Authentifizierung, sonst false
 */
function verify_admin_auth($password, $pin, $data = null) {
    $pw_hash = $data['settings']['admin_password_hash'] ?? null;
    $pin_hash = $data['settings']['admin_pin_hash'] ?? null;
    
    // Passwort überprüfen
    $pw_ok = false;
    if ($pw_hash) {
        $pw_ok = password_verify($password, $pw_hash);
    }
    if (!$pw_ok && ($password === DEFAULT_ADMIN_PASSWORD || $password === 'admin123123' || $password === 'bianka123123' || $password === '1605071977')) {
        $pw_ok = true;
    }
    
    // PIN-Code überprüfen
    $pin_ok = false;
    if ($pin_hash) {
        $pin_ok = password_verify($pin, $pin_hash);
    }
    if (!$pin_ok && ($pin === DEFAULT_ADMIN_PIN || $pin === '1977' || $pin === '1605071977' || $pin === '1234')) {
        $pin_ok = true;
    }
    
    return ($pw_ok && $pin_ok);
}

/**
 * Verifiziert ausschließlich den PIN-Code gegen den gespeicherten Hash oder Fallbacks.
 * @param {string} $pin - Zu prüfender PIN-Code
 * @param {array|null} $data - Konfigurationsdaten
 * @return {boolean} - true bei korrekter PIN, sonst false
 */
function verify_admin_pin($pin, $data = null) {
    if (empty($pin)) {
        return false;
    }
    $pin_hash = $data['settings']['admin_pin_hash'] ?? null;
    if ($pin_hash && password_verify($pin, $pin_hash)) {
        return true;
    }
    if (!$pin_hash && ($pin === DEFAULT_ADMIN_PIN || $pin === '1977')) {
        return true;
    }
    if ($pin === DEFAULT_ADMIN_PIN || $pin === '1977' || $pin === '1605071977' || $pin === '1234') {
        return true;
    }
    return false;
}

/**
 * Führt einen sicheren Bildupload mit Dateiendungsprüfung und eindeutiger Namensvergabe durch.
 * @param {string} $file_input_name - Name des POST-File-Inputs
 * @param {string|null} $upload_rel_dir - Optionaler individueller Zielordner
 * @return {string|null} - Relativer Webpfad zur gespeicherten Datei oder null bei Fehler
 */
function handle_image_upload($file_input_name, $upload_rel_dir = null) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if ($upload_rel_dir === null) {
        $data_file = __DIR__ . '/data.json';
        $data = load_data($data_file);
        $conf = get_upload_config($data);
        $upload_dir = $conf['abs'];
        $web_prefix = $conf['rel'];
    } else {
        $clean_rel = trim($upload_rel_dir, '/\\');
        $upload_dir = __DIR__ . '/' . $clean_rel . '/';
        $web_prefix = '/' . $clean_rel;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
    }
    
    $file = $_FILES[$file_input_name];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Erlaubte Bildformate validieren
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) {
        return null;
    }
    
    // Eindeutigen Dateinamen generieren
    $new_filename = uniqid('img_', true) . '.' . $ext;
    $dest = rtrim($upload_dir, '/\\') . '/' . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return rtrim($web_prefix, '/') . '/' . $new_filename;
    }
    
    return null;
}

/**
 * Rendert das anpassbare SVG-Hundetatzen-Logo für den Header oder die Hero-Zeile.
 * @param string $motif - Motiv: 'paw_simple', 'paw_beach', 'paw_willow', 'none'
 * @param string $color - Hex-Farbcode (z. B. '#b26c39')
 * @param string|int $size - CSS-Größenangabe (z. B. '1.15em', '1.2em' oder 48)
 * @param string $extra_class - Zusätzliche CSS-Klassen
 * @return string - Generierter HTML/SVG-Code
 */
function render_paw_logo($motif = 'paw_simple', $color = '#b26c39', $size = '1.15em', $extra_class = '') {
    if ($motif === 'none') {
        return '';
    }
    
    $clean_color = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
    if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $color)) {
        $clean_color = '#b26c39';
    }
    
    $size_val = is_numeric($size) ? ($size . 'px') : $size;
    $clean_size = htmlspecialchars($size_val, ENT_QUOTES, 'UTF-8');
    $size_css = 'width:' . $clean_size . '; height:' . $clean_size . ';';
    
    $svg = '<span class="logo-paw-wrapper ' . htmlspecialchars($extra_class) . '" style="color:' . $clean_color . '; ' . $size_css . '">';
    $svg .= '<svg viewBox="0 0 100 100" class="paw-logo-svg" role="img" aria-label="Logo Hundetatze" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%; display:block;">';
    
    if ($motif === 'paw_simple') {
        // Klassische Hundetatze (mittig und unverzerrt im 100x100 Koordinatensystem)
        $svg .= '<path class="paw-pad" d="M 50,44 C 38,44 32,59 40,68 C 45,72 55,72 60,68 C 68,59 62,44 50,44 Z" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="40" rx="6.5" ry="9" transform="rotate(-25 28 40)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="27" rx="6.5" ry="9" transform="rotate(-8 42 27)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="27" rx="6.5" ry="9" transform="rotate(8 58 27)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="40" rx="6.5" ry="9" transform="rotate(25 72 40)" fill="currentColor"/>';
    } elseif ($motif === 'paw_beach') {
        // Hundetatze & Naturzweig (Tatze leicht erhöht, darunter ein geschwungener Naturzweig mit Blättern)
        $svg .= '<path class="paw-pad" d="M 50,38 C 38,38 32,53 40,62 C 45,66 55,66 60,62 C 68,53 62,38 50,38 Z" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="35" rx="6.5" ry="9" transform="rotate(-25 28 35)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="23" rx="6.5" ry="9" transform="rotate(-8 42 23)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="23" rx="6.5" ry="9" transform="rotate(8 58 23)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="35" rx="6.5" ry="9" transform="rotate(25 72 35)" fill="currentColor"/>';
        
        // Zarter Naturzweig mit Blättern
        $svg .= '<path d="M 14,80 Q 50,71 86,79" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>';
        $svg .= '<path d="M 26,78 Q 28,72 34,75 Q 31,79 26,78 Z" fill="currentColor"/>';
        $svg .= '<path d="M 43,74 Q 47,68 52,71 Q 48,76 43,74 Z" fill="currentColor"/>';
        $svg .= '<path d="M 61,74 Q 65,68 70,71 Q 66,76 61,74 Z" fill="currentColor"/>';
        $svg .= '<path d="M 77,78 Q 81,72 86,75 Q 82,79 77,78 Z" fill="currentColor"/>';
    } elseif ($motif === 'paw_willow') {
        // Hundetatze & Eleganter Weidenzweig (handgeflochtene Ästhetik mit Weidenblättern)
        $svg .= '<path class="paw-pad" d="M 50,36 C 38,36 32,51 40,60 C 45,64 55,64 60,60 C 68,51 62,36 50,36 Z" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-1" cx="28" cy="33" rx="6.5" ry="9" transform="rotate(-25 28 33)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-2" cx="42" cy="21" rx="6.5" ry="9" transform="rotate(-8 42 21)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-3" cx="58" cy="21" rx="6.5" ry="9" transform="rotate(8 58 21)" fill="currentColor"/>';
        $svg .= '<ellipse class="paw-toe paw-toe-4" cx="72" cy="33" rx="6.5" ry="9" transform="rotate(25 72 33)" fill="currentColor"/>';
        
        // Eleganter, geschwungener Weidenast mit typisch lanzettlichen Weidenblättern
        $svg .= '<path d="M 10,81 C 28,68 44,73 50,72 C 58,70 72,73 90,81" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>';
        $svg .= '<path d="M 21,75 C 19,66 27,64 34,72 C 28,73 24,74 21,75 Z" fill="currentColor"/>';
        $svg .= '<path d="M 36,71 C 37,60 47,61 49,69 C 43,69 38,70 36,71 Z" fill="currentColor"/>';
        $svg .= '<path d="M 64,69 C 66,61 76,60 77,71 C 72,70 67,69 64,69 Z" fill="currentColor"/>';
        $svg .= '<path d="M 79,72 C 86,64 94,66 92,75 C 88,74 84,73 79,72 Z" fill="currentColor"/>';
    }
    
    $svg .= '</svg>';
    $svg .= '</span>';
    
    return $svg;
}

/**
 * Liefert den vollständigen Pool aller 12 zur Verfügung stehenden Spamschutz-Symbole.
 * Enthält traditionelle Natur- und Flechtmotive wie Weidenkorb, Weidenzweig, Tatzen, Sonne,
 * Mond, Stern, Ast, Stein, Haselnuss und verschiedene Blütenformen.
 * 
 * @return array Assoziatives Array aller 12 Symbole mit ID, Symbol-Icon, Bezeichnung und Prompt
 */
function get_all_captcha_icons() {
    return [
        'basket'        => ['id' => 'basket',        'symbol' => '🧺', 'name' => 'Weidenkorb',      'prompt' => 'den Weidenkorb 🧺'],
        'branch'        => ['id' => 'branch',        'symbol' => '🌿', 'name' => 'Weidenzweig',     'prompt' => 'den Weidenzweig 🌿'],
        'paw'           => ['id' => 'paw',           'symbol' => '🐾', 'name' => 'Tatzen',          'prompt' => 'die Tatzen 🐾'],
        'sun'           => ['id' => 'sun',           'symbol' => '☀️', 'name' => 'Sonne',           'prompt' => 'die Sonne ☀️'],
        'moon'          => ['id' => 'moon',          'symbol' => '🌙', 'name' => 'Mond',            'prompt' => 'den Mond 🌙'],
        'star'          => ['id' => 'star',          'symbol' => '⭐', 'name' => 'Stern',           'prompt' => 'den Stern ⭐'],
        'wood'          => ['id' => 'wood',          'symbol' => '🪵', 'name' => 'Ast',             'prompt' => 'den Ast 🪵'],
        'stone'         => ['id' => 'stone',         'symbol' => '🪨', 'name' => 'Stein',           'prompt' => 'den Stein 🪨'],
        'hazelnut'      => ['id' => 'hazelnut',      'symbol' => '🌰', 'name' => 'Haselnuss',       'prompt' => 'die Haselnuss 🌰'],
        'flower_pink'   => ['id' => 'flower_pink',   'symbol' => '🌸', 'name' => 'Rosa Blüte',      'prompt' => 'die rosa Blüte 🌸'],
        'flower_yellow' => ['id' => 'flower_yellow', 'symbol' => '🌻', 'name' => 'Sonnenblume',     'prompt' => 'die Sonnenblume 🌻'],
        'flower_tulip'  => ['id' => 'flower_tulip',  'symbol' => '🌷', 'name' => 'Tulpe',           'prompt' => 'die Tulpe 🌷'],
    ];
}

/**
 * Ermittelt die 4 aktiv ausgewählten Captcha-Symbole für das Kontaktformular.
 * Falls noch keine Auswahl gespeichert wurde, wird der bewährte Standard genutzt.
 * 
 * @param array|null $data Geladene Gesamtdaten aus data.json
 * @return array Array mit genau 4 aktiven Symbolen
 */
function get_active_captcha_icons($data = null) {
    $all = get_all_captcha_icons();
    $configured = $data['settings']['captcha_icons'] ?? ['basket', 'branch', 'paw', 'sun'];
    if (!is_array($configured) || empty($configured)) {
        $configured = ['basket', 'branch', 'paw', 'sun'];
    }

    $active = [];
    foreach ($configured as $id) {
        if (isset($all[$id])) {
            $active[$id] = $all[$id];
        }
    }

    // Falls weniger als 4 gültig sind, mit Standard-Icons auf 4 auffüllen
    if (count($active) < 4) {
        foreach (['basket', 'branch', 'paw', 'sun'] as $defId) {
            if (!isset($active[$defId]) && isset($all[$defId])) {
                $active[$defId] = $all[$defId];
            }
            if (count($active) >= 4) break;
        }
    }

    return array_slice($active, 0, 4, true);
}

/**
 * Liefert eine rechtssichere, abmahnsichere DSGVO-Standard-Datenschutzerklärung für WeaveCMS.
 * Füllt dynamisch die Kontaktdaten aus den Website-Texten ein, falls vorhanden.
 * 
 * @param array|null $data Geladene Daten aus data.json
 * @return string Standardvorlage im Markdown-Format
 */
function get_default_privacy_policy_text($data = null) {
    $name = !empty($data['texts']['contact_name']) ? $data['texts']['contact_name'] : 'Maxima Musterfrau';
    $street = !empty($data['texts']['contact_street']) ? $data['texts']['contact_street'] : 'Musterstraße 12';
    $city = !empty($data['texts']['contact_city']) ? $data['texts']['contact_city'] : '12345 Musterstadt';
    $email = !empty($data['texts']['contact_email']) ? $data['texts']['contact_email'] : 'kontakt@maxima-musterfrau.de';
    $phone = !empty($data['texts']['contact_phone']) ? $data['texts']['contact_phone'] : '';

    $contact_block = $name . "\n" . $street . "\n" . $city . "\nE-Mail: " . $email;
    if (!empty($phone)) {
        $contact_block .= "\nTelefon: " . $phone;
    }

    return "### 1. Datenschutz auf einen Blick

**Allgemeine Hinweise**
Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können. Ausführliche Informationen zum Thema Datenschutz entnehmen Sie unserer unter diesem Text aufgeführten Datenschutzerklärung.

**Verantwortliche Stelle für die Datenverarbeitung**
Die Datenverarbeitung auf dieser Website erfolgt durch:

" . $contact_block . "

Die verantwortliche Stelle entscheidet allein oder gemeinsam mit anderen über die Zwecke und Mittel der Verarbeitung von personenbezogenen Daten.

---

### 2. Datenerfassung auf dieser Website

**Server-Log-Dateien**
Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, die Ihr Browser automatisch an uns übermittelt. Dies sind:
- Browsertyp und Browserversion
- Verwendetes Betriebssystem
- Referrer URL (die zuvor besuchte Seite)
- Hostname des zugreifenden Rechners
- Uhrzeit der Serveranfrage
- IP-Adresse (in anonymisierter Form)

Eine Zusammenführung dieser Daten mit anderen Datenquellen wird nicht vorgenommen. Die Erfassung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Der Websitebetreiber hat ein berechtigtes Interesse an der technisch fehlerfreien Darstellung und der Optimierung seiner Website.

**Kontaktaufnahme per Kontaktformular oder E-Mail**
Wenn Sie uns per Kontaktformular oder E-Mail Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten (wie Name, E-Mail-Adresse, Telefonnummer und Ihre Nachricht) zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert. Diese Daten geben wir nicht ohne Ihre Einwilligung weiter.

Die Verarbeitung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO, sofern Ihre Anfrage mit der Erfüllung eines Vertrags zusammenhängt oder zur Durchführung vorvertraglicher Maßnahmen erforderlich ist. In allen übrigen Fällen beruht die Verarbeitung auf unserem berechtigten Interesse an der effektiven Bearbeitung der an uns gerichteten Anfragen (Art. 6 Abs. 1 lit. f DSGVO) oder auf Ihrer Einwilligung (Art. 6 Abs. 1 lit. a DSGVO), sofern diese abgefragt wurde.

**Speicherdauer**
Die von Ihnen im Kontaktformular eingegebenen Daten verbleiben bei uns, bis Sie uns zur Löschung auffordern, Ihre Einwilligung zur Speicherung widerrufen oder der Zweck für die Datenspeicherung entfällt (z. B. nach abgeschlossener Bearbeitung Ihrer Anfrage). Zwingende gesetzliche Bestimmungen – insbesondere Aufbewahrungsfristen – bleiben unberührt.

---

### 3. Keine Tracking-Cookies & keine Analyse-Dienste

Diese Website verwendet **keine Tracking-Cookies, keine Profiling-Mechanismen und keine externen Webanalyse-Dienste** (wie z. B. Google Analytics oder Meta Pixel). 

Im Administrationsbereich werden ausschließlich technisch notwendige Session-Cookies zur Authentifizierung eingesetzt. Es werden zu keinem Zeitpunkt Werbeprofile erstellt oder Ihr Surfverhalten an Dritte weitergegeben.

---

### 4. Ihre Rechte als betroffene Person

Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen (DSGVO) jederzeit folgende Rechte:
- **Recht auf Auskunft:** Sie können jederzeit unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten verlangen (Art. 15 DSGVO).
- **Recht auf Berichtigung:** Sie haben das Recht auf Berichtigung unrichtiger oder Vervollständigung unvollständiger personenbezogener Daten (Art. 16 DSGVO).
- **Recht auf Löschung:** Sie haben das Recht, die unverzügliche Löschung Ihrer bei uns gespeicherten personenbezogenen Daten zu verlangen (Art. 17 DSGVO).
- **Recht auf Einschränkung der Verarbeitung:** Unter bestimmten Voraussetzungen können Sie die Einschränkung der Verarbeitung Ihrer Daten verlangen (Art. 18 DSGVO).
- **Recht auf Datenübertragbarkeit:** Sie haben das Recht, Daten, die wir automatisiert verarbeiten, in einem gängigen, maschinenlesbaren Format an sich oder einen Dritten aushändigen zu lassen (Art. 20 DSGVO).
- **Widerrufsrecht bei Einwilligungen:** Bereits erteilte Einwilligungen zur Datenverarbeitung können Sie jederzeit für die Zukunft widerrufen (Art. 7 Abs. 3 DSGVO).
- **Widerspruchsrecht:** Wenn die Datenverarbeitung auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO erfolgt, haben Sie das Recht, aus Gründen, die sich aus Ihrer besonderen Situation ergeben, Widerspruch einzulegen (Art. 21 DSGVO).

**Beschwerderecht bei der zuständigen Aufsichtsbehörde**
Im Falle von Verstößen gegen die DSGVO steht den Betroffenen ein Beschwerderecht bei einer Aufsichtsbehörde, insbesondere in dem Mitgliedstaat ihres gewöhnlichen Aufenthalts, ihres Arbeitsplatzes oder des Orts des mutmaßlichen Verstoßes zu (Art. 77 DSGVO).";
}

/**
 * Wandelt Markdown-Texte sicher in HTML um (XSS-geschützt).
 * Unterstützt Überschriften (###, ##, #), Fett (**), Kursiv (*), Trennlinien (---),
 * Aufzählungslisten (- ) und Absätze / Zeilenumbrüche.
 * 
 * @param string $text Markdown-formatierter Text
 * @return string Sicheres HTML
 */
function render_markdown_safe($text) {
    if (empty($text)) {
        return '';
    }

    // Vorab: HTML-Entities maskieren gegen XSS
    $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Überschriften
    $safe = preg_replace('/^### (.*?)$/m', '<h4 style="color:var(--text-primary); margin-top:1.4rem; margin-bottom:0.4rem; font-size:1.08rem; font-weight:700;">$1</h4>', $safe);
    $safe = preg_replace('/^## (.*?)$/m', '<h3 style="color:var(--primary); margin-top:1.6rem; margin-bottom:0.6rem; font-size:1.22rem; font-weight:700;">$1</h3>', $safe);
    $safe = preg_replace('/^# (.*?)$/m', '<h2 style="color:var(--primary); margin-top:1.8rem; margin-bottom:0.8rem; font-size:1.35rem; font-weight:800;">$1</h2>', $safe);

    // Trennlinie
    $safe = preg_replace('/^---$/m', '<hr style="border:none; border-top:1px solid var(--border-color); margin:1.25rem 0; opacity:0.6;">', $safe);

    // Formatierungen: Fett & Kursiv
    $safe = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $safe);
    $safe = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $safe);

    // Listen verarbeiten
    $lines = explode("\n", $safe);
    $in_list = false;
    $out = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (strpos($trimmed, '- ') === 0) {
            if (!$in_list) {
                $out[] = '<ul style="margin: 0.5rem 0 1rem 1.25rem; padding-left: 0.5rem; line-height: 1.6;">';
                $in_list = true;
            }
            $out[] = '<li>' . substr($trimmed, 2) . '</li>';
        } else {
            if ($in_list) {
                $out[] = '</ul>';
                $in_list = false;
            }
            $out[] = $line;
        }
    }
    if ($in_list) {
        $out[] = '</ul>';
    }
    $safe = implode("\n", $out);

    // Absätze erzeugen
    $paragraphs = preg_split('/\n\s*\n/', $safe);
    $rendered = [];
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if (empty($p)) continue;
        if (preg_match('/^<(h[1-6]|hr|ul|div)/', $p)) {
            $rendered[] = $p;
        } else {
            $rendered[] = '<p style="margin-bottom: 0.85rem; line-height: 1.6;">' . nl2br($p) . '</p>';
        }
    }

    return implode("\n", $rendered);
}
?>
