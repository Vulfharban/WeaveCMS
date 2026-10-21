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
