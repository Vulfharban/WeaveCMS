<?php
/**
 * WeaveCMS – Anti-Scraping E-Mail-Freischaltung (Canvas Captcha)
 * 
 * Verifiziert das Canvas-Zahlen-Captcha im Impressum und schaltet
 * die E-Mail-Adresse erst nach erfolgreicher Code-Eingabe frei.
 */

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__) . '/config.php';

$action = trim($_REQUEST['action'] ?? 'verify');
$context = in_array(trim($_REQUEST['context'] ?? ''), ['impressum', 'contact'], true) ? trim($_REQUEST['context']) : 'impressum';

if ($action === 'get_challenge') {
    $challenge = CaptchaManager::generateCanvasChallenge($context);
    echo json_encode([
        'success' => true,
        'code' => $challenge['code']
    ]);
    exit;
}

if ($action === 'verify') {
    $submittedCode = trim($_REQUEST['code'] ?? '');
    if (empty($submittedCode)) {
        echo json_encode(['success' => false, 'error' => 'Bitte gib den Bestätigungscode ein.']);
        exit;
    }

    if (CaptchaManager::verifyCanvasChallenge($submittedCode, $context)) {
        $dataFile = dirname(__DIR__) . '/data.json';
        $data = load_data($dataFile);
        $email = $data['texts']['contact_email'] ?? 'kontakt@maxima-musterfrau.de';

        echo json_encode([
            'success' => true,
            'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Der eingegebene Code war nicht korrekt.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion.']);
exit;
