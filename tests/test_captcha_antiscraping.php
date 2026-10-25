<?php
/**
 * WeaveCMS Test: Anti-Scraping E-Mail Captcha (Impressum & Kontakt)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../libs/php/CaptchaManager.php';

echo "========================================================\n";
echo "WeaveCMS Test: Anti-Scraping E-Mail Captcha\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $message\n";
        $passCount++;
    } else {
        echo "  [FAIL] $message\n";
        $failCount++;
    }
}

// 1. Settings Tests
$data1 = ['settings' => ['impressum_email_captcha' => true, 'contact_email_captcha' => false]];
assertTest(CaptchaManager::isImpressumEmailProtected($data1) === true, 'Impressum E-Mail-Schutz aktiv');
assertTest(CaptchaManager::isContactEmailProtected($data1) === false, 'Kontakt E-Mail-Schutz inaktiv');

$data2 = ['settings' => ['impressum_email_captcha' => false, 'contact_email_captcha' => true]];
assertTest(CaptchaManager::isImpressumEmailProtected($data2) === false, 'Impressum E-Mail-Schutz inaktiv');
assertTest(CaptchaManager::isContactEmailProtected($data2) === true, 'Kontakt E-Mail-Schutz aktiv');

// 2. Challenge Generation & Verification für beide Kontexte
$chalImpressum = CaptchaManager::generateCanvasChallenge('impressum');
assertTest(!empty($chalImpressum['code']) && strlen($chalImpressum['code']) === 5, 'Impressum Canvas Code ist 5-stellig');

$chalContact = CaptchaManager::generateCanvasChallenge('contact');
assertTest(!empty($chalContact['code']) && strlen($chalContact['code']) === 5, 'Kontakt Canvas Code ist 5-stellig');

// Test falscher Code
assertTest(CaptchaManager::verifyCanvasChallenge('00000', 'contact') === false, 'Falscher Code für Kontakt abgewiesen');

// Test richtiger Code
assertTest(CaptchaManager::verifyCanvasChallenge($chalContact['code'], 'contact') === true, 'Richtiger Code für Kontakt erfolgreich verifiziert');
assertTest(CaptchaManager::verifyCanvasChallenge($chalImpressum['code'], 'impressum') === true, 'Richtiger Code für Impressum erfolgreich verifiziert');

echo "\n========================================================\n";
echo "Testergebnis: $passCount von " . ($passCount + $failCount) . " Tests bestanden\n";
echo "========================================================\n";

exit($failCount > 0 ? 1 : 0);
