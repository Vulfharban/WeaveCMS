<?php
/**
 * Test-Suite: Phase 17 - Live-Server-Deployment, SSL/HSTS-Härtung & Produktions-Sicherheit
 */

require_once __DIR__ . '/../config.php';

$total = 0;
$passed = 0;

function assert_test(string $name, bool $condition, string $details = '') {
    global $total, $passed;
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        echo "  [FAIL] {$name} - {$details}\n";
    }
}

echo "========================================================\n";
echo "WeaveCMS Test: Phase 17 Produktions-Sicherheit & Deployment\n";
echo "========================================================\n\n";

// 1. Settings & Defaults
echo "1. Produktions-Sicherheits-Einstellungen:\n";
$settings = SecurityManager::getProductionSettings();
assert_test("Standard-Settings haben 'hsts_preload'", array_key_exists('hsts_preload', $settings));
assert_test("Standard-Settings haben 'production_csp'", array_key_exists('production_csp', $settings));
assert_test("Standard-Settings haben 'trusted_proxies'", array_key_exists('trusted_proxies', $settings));
assert_test("Standard-Settings haben 'alert_on_bruteforce'", array_key_exists('alert_on_bruteforce', $settings));

SecurityManager::setProductionSettings([
    'hsts_preload' => true,
    'production_csp' => true,
    'trusted_proxies' => '10.0.0.0/8, 172.16.0.0/12, 192.168.1.100',
    'alert_email' => 'admin@test.de',
    'alert_on_bruteforce' => true,
    'alert_cooldown_minutes' => 10
]);
$updated = SecurityManager::getProductionSettings();
assert_test("Settings können gesetzt und ausgelesen werden", $updated['hsts_preload'] === true && $updated['alert_cooldown_minutes'] === 10);

// 2. CIDR & IP Matching
echo "\n2. CIDR- und IP-Filter (Phase 17.4):\n";
assert_test("IP in /24 Subnetz erkannt", SecurityManager::ipMatchesCidr('192.168.1.45', '192.168.1.0/24'));
assert_test("IP außerhalb /24 Subnetz abgewiesen", !SecurityManager::ipMatchesCidr('192.168.2.45', '192.168.1.0/24'));
assert_test("IP in /8 Subnetz erkannt", SecurityManager::ipMatchesCidr('10.250.12.1', '10.0.0.0/8'));
assert_test("Einzelne IP ohne Maske matcht exakt", SecurityManager::ipMatchesCidr('192.168.1.100', '192.168.1.100'));
assert_test("Einzelne abweichende IP matcht nicht", !SecurityManager::ipMatchesCidr('192.168.1.101', '192.168.1.100'));

// 3. Trusted Proxy & Anti-IP-Spoofing
echo "\n3. Trusted Proxies & Anti-IP-Spoofing Schutz (Phase 17.4):\n";
assert_test("Localhost (127.0.0.1) ist immer trusted", SecurityManager::isTrustedProxy('127.0.0.1'));
assert_test("Localhost (::1) ist immer trusted", SecurityManager::isTrustedProxy('::1'));
assert_test("IP aus konfiguriertem /8 Bereich ist trusted", SecurityManager::isTrustedProxy('10.5.5.1'));
assert_test("Fremde IP aus dem Internet ist UNTRUSTED", !SecurityManager::isTrustedProxy('203.0.113.195'));

// Simulation: Angreifer versucht IP zu fälschen
$_SERVER['REMOTE_ADDR'] = '203.0.113.195'; // Angreifer-IP
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
$_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
$resolvedIp = SecurityManager::getClientIp();
assert_test("Anti-Spoofing: Gefälschtes X-Forwarded-For von untrusted Remote-IP wird ignoriert", $resolvedIp === '203.0.113.195', "Erwartet 203.0.113.195, erhalten: {$resolvedIp}");

// Simulation: Echter Cloudflare / Nginx Reverse Proxy
$_SERVER['REMOTE_ADDR'] = '10.0.0.2'; // Liegt in konfiguriertem trusted_proxies (10.0.0.0/8)
$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.77';
$cfResolvedIp = SecurityManager::getClientIp();
assert_test("Von trusted Proxy wird CF-Connecting-IP akzeptiert", $cfResolvedIp === '198.51.100.77', "Erwartet 198.51.100.77, erhalten: {$cfResolvedIp}");

unset($_SERVER['HTTP_CF_CONNECTING_IP']);
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.88, 10.0.0.2';
$xffResolvedIp = SecurityManager::getClientIp();
assert_test("Von trusted Proxy wird X-Forwarded-For Client-IP akzeptiert", $xffResolvedIp === '198.51.100.88', "Erwartet 198.51.100.88, erhalten: {$xffResolvedIp}");

// Reset Mocking
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP']);

// 4. Subresource Integrity (SRI)
echo "\n4. Subresource Integrity (SRI) Helper (Phase 17.2):\n";
$sriScript = SecurityManager::renderSriTag('script', 'https://example.com/lib.js', 'sha384-xyz123abc', ['defer' => 'defer']);
assert_test("SRI Script-Tag enthält integrity und crossorigin", strpos($sriScript, 'integrity="sha384-xyz123abc"') !== false && strpos($sriScript, 'crossorigin="anonymous"') !== false);
assert_test("SRI Script-Tag enthält optionale Attribute", strpos($sriScript, 'defer="defer"') !== false);

$sriCss = SecurityManager::renderSriTag('css', 'https://example.com/style.css', 'sha384-csshash');
assert_test("SRI CSS-Tag enthält rel='stylesheet' und integrity", strpos($sriCss, 'rel="stylesheet"') !== false && strpos($sriCss, 'integrity="sha384-csshash"') !== false);

// 5. Dateisystem-Berechtigungen & POSIX-Audit
echo "\n5. Permissions- & POSIX-Audit (Phase 17.3):\n";
$audit = SecurityManager::auditPermissions();
assert_test("Audit liefert strukturiertes Array", is_array($audit) && isset($audit['items'], $audit['overall_status']));
assert_test("Audit enthält OS-Information", !empty($audit['os']));
assert_test("Audit prüft config.php", in_array('config.php', array_column($audit['items'], 'path')));
assert_test("Audit prüft backups/.htaccess", in_array('backups/.htaccess', array_column($audit['items'], 'path')));
assert_test("Audit prüft logs/.htaccess", in_array('logs/.htaccess', array_column($audit['items'], 'path')));

// 6. Automatisierte Admin-Alarmierung
echo "\n6. Automatisierte Admin-E-Mail-Alarmierung (Phase 17.5):\n";
// Test ohne alert_email -> false
SecurityManager::setProductionSettings(['alert_on_bruteforce' => true, 'alert_email' => '']);
assert_test("Alarm ohne Empfänger-E-Mail wird sauber abgewiesen", !SecurityManager::checkAndTriggerSecurityAlert('test_action', '192.168.1.1', 5));

// Test deaktiviert -> false
SecurityManager::setProductionSettings(['alert_on_bruteforce' => false, 'alert_email' => 'admin@test.de']);
assert_test("Alarm bei deaktiviertem Schalter wird nicht gesendet", !SecurityManager::checkAndTriggerSecurityAlert('test_action', '192.168.1.1', 5));

echo "\n========================================================\n";
echo "Testergebnis: {$passed} von {$total} Tests bestanden (" . round(($passed / $total) * 100) . "%)\n";
echo "========================================================\n";
exit($passed === $total ? 0 : 1);
