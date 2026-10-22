<?php
/**
 * ============================================================================
 * WeaveCMS - Barrierefreier Symbol-Spamschutz (CaptchaManager)
 * ============================================================================
 * Verwaltet den 12-teiligen Symbol-Pool (Naturmotive & Flechtkunst) sowie
 * die 4 vom Administrator ausgewählten aktiven Symbole für das Kontaktformular.
 */

class CaptchaManager {

    /**
     * Liefert den vollständigen Pool aller 12 zur Verfügung stehenden Spamschutz-Symbole.
     * @return array
     */
    public static function getAllIcons(): array {
        return [
            'basket' => [
                'name' => 'Weidenkorb',
                'symbol' => '🧺',
                'prompt' => 'den Weidenkorb'
            ],
            'branch' => [
                'name' => 'Weidenzweig',
                'symbol' => '🌿',
                'prompt' => 'den Weidenzweig'
            ],
            'paw' => [
                'name' => 'Tatzen',
                'symbol' => '🐾',
                'prompt' => 'die Tatzen'
            ],
            'sun' => [
                'name' => 'Sonne',
                'symbol' => '☀️',
                'prompt' => 'die Sonne'
            ],
            'moon' => [
                'name' => 'Mond',
                'symbol' => '🌙',
                'prompt' => 'den Mond'
            ],
            'star' => [
                'name' => 'Stern',
                'symbol' => '⭐',
                'prompt' => 'den Stern'
            ],
            'wood' => [
                'name' => 'Ast',
                'symbol' => '🪵',
                'prompt' => 'den Ast'
            ],
            'stone' => [
                'name' => 'Stein',
                'symbol' => '🪨',
                'prompt' => 'den Stein'
            ],
            'nut' => [
                'name' => 'Haselnuss',
                'symbol' => '🌰',
                'prompt' => 'die Haselnuss'
            ],
            'blossom' => [
                'name' => 'Rosa Blüte',
                'symbol' => '🌸',
                'prompt' => 'die rosa Blüte'
            ],
            'sunflower' => [
                'name' => 'Sonnenblume',
                'symbol' => '🌻',
                'prompt' => 'die Sonnenblume'
            ],
            'tulip' => [
                'name' => 'Tulpe',
                'symbol' => '🌷',
                'prompt' => 'die Tulpe'
            ]
        ];
    }

    /**
     * Liefert die 4 vom Administrator konfigurierten aktiven Spamschutz-Symbole.
     * @param array|null $data Konfigurationsdaten aus data.json
     * @return array
     */
    public static function getActiveIcons(?array $data = null): array {
        $all = self::getAllIcons();
        $configured = $data['settings']['captcha_icons'] ?? null;

        if (is_array($configured) && count($configured) === 4) {
            $filtered = [];
            foreach ($configured as $key) {
                if (isset($all[$key])) {
                    $filtered[$key] = $all[$key];
                }
            }
            if (count($filtered) === 4) {
                return $filtered;
            }
        }

        // Standard-Vorauswahl
        return [
            'basket' => $all['basket'],
            'branch' => $all['branch'],
            'paw' => $all['paw'],
            'sun' => $all['sun']
        ];
    }

    /**
     * Unterstützte Captcha-Modi (Phase 16).
     */
    public const MODE_ICONS = 'icons';
    public const MODE_MATH = 'math';
    public const MODE_CANVAS = 'canvas';
    public const MODE_HONEYPOT = 'honeypot';

    /**
     * Ermittelt den aktuell konfigurierten Captcha-Modus.
     * @param array|null $data
     * @return string
     */
    public static function getMode(?array $data = null): string {
        $mode = $data['settings']['captcha_mode'] ?? self::MODE_ICONS;
        $allowed = [self::MODE_ICONS, self::MODE_MATH, self::MODE_CANVAS, self::MODE_HONEYPOT];
        return in_array($mode, $allowed, true) ? $mode : self::MODE_ICONS;
    }

    /**
     * Prüft, ob die E-Mail-Adresse im Impressum mit aktiver Captcha-Prüfung geschützt werden soll.
     * @param array|null $data
     * @return bool
     */
    public static function isImpressumEmailProtected(?array $data = null): bool {
        return !empty($data['settings']['impressum_email_captcha']);
    }

    /**
     * Generiert eine dynamische Mathe-Aufgabe (z. B. 4 + 3) und speichert das Ergebnis in der Session.
     * @return array ['question' => string, 'hash' => string]
     */
    public static function generateMathChallenge(): array {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $num1 = random_int(2, 9);
        $num2 = random_int(1, 9);
        $result = $num1 + $num2;

        $_SESSION['weave_captcha_math_result'] = $result;
        $hash = hash_hmac('sha256', (string)$result, session_id() ?: 'weave_salt_math');

        return [
            'question' => "Wie viel ist {$num1} + {$num2}?",
            'num1' => $num1,
            'num2' => $num2,
            'hash' => $hash
        ];
    }

    /**
     * Verifiziert die eingegebene Antwort auf die Mathe-Aufgabe.
     * @param string|int $answer
     * @param string|null $hash
     * @return bool
     */
    public static function verifyMathChallenge($answer, ?string $hash = null): bool {
        $cleanAnswer = (int)trim((string)$answer);
        if ($cleanAnswer <= 0) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['weave_captcha_math_result'])) {
            $expected = (int)$_SESSION['weave_captcha_math_result'];
            if ($cleanAnswer === $expected) {
                unset($_SESSION['weave_captcha_math_result']);
                return true;
            }
        }

        if (!empty($hash)) {
            $expectedHash = hash_hmac('sha256', (string)$cleanAnswer, session_id() ?: 'weave_salt_math');
            if (hash_equals($hash, $expectedHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generiert einen 5-stelligen Zahlencode für das HTML5 Canvas-Captcha.
     * @param string $context 'contact' oder 'impressum'
     * @return array ['code' => string, 'hash' => string]
     */
    public static function generateCanvasChallenge(string $context = 'contact'): array {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $code = (string)random_int(10000, 99999);
        $sessionKey = 'weave_captcha_canvas_' . $context;
        $_SESSION[$sessionKey] = $code;
        $hash = hash_hmac('sha256', $code, session_id() ?: 'weave_salt_canvas');

        return [
            'code' => $code,
            'hash' => $hash
        ];
    }

    /**
     * Verifiziert die eingegebene Canvas-Zahlenfolge.
     * @param string $answer
     * @param string $context 'contact' oder 'impressum'
     * @param string|null $hash
     * @return bool
     */
    public static function verifyCanvasChallenge(string $answer, string $context = 'contact', ?string $hash = null): bool {
        $cleanAnswer = trim($answer);
        if ($cleanAnswer === '') {
            return false;
        }

        $sessionKey = 'weave_captcha_canvas_' . $context;
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$sessionKey])) {
            $expected = (string)$_SESSION[$sessionKey];
            if (hash_equals($expected, $cleanAnswer)) {
                unset($_SESSION[$sessionKey]);
                return true;
            }
        }

        if (!empty($hash)) {
            $expectedHash = hash_hmac('sha256', $cleanAnswer, session_id() ?: 'weave_salt_canvas');
            if (hash_equals($hash, $expectedHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Wählt per Zufall eine Challenge für das Kontaktformular aus den aktiven Symbolen.
     * @param array|null $data
     * @return array ['icons' => array, 'target_key' => string, 'target_prompt' => string]
     */
    public static function getRandomChallenge(?array $data = null): array {
        $active = self::getActiveIcons($data);
        $keys = array_keys($active);
        $targetKey = $keys[array_rand($keys)];

        return [
            'icons' => $active,
            'target_key' => $targetKey,
            'target_prompt' => $active[$targetKey]['prompt']
        ];
    }

    /**
     * Verifiziert eine gelöste Sicherheitsaufgabe.
     * @param string $selectedIcon
     * @param string $targetIcon
     * @return bool
     */
    public static function verifyChallenge(string $selectedIcon, string $targetIcon): bool {
        return !empty($selectedIcon) && !empty($targetIcon) && ($selectedIcon === $targetIcon);
    }
}
