<?php
/**
 * ============================================================================
 * WeaveCMS - Besucher- & Zugriffsstatistik (StatsManager) - Phase 10
 * ============================================================================
 * Datenschutzkonformer, cookieloser Zähler für Frontend-Seitenaufrufe.
 * Speichert aggregierte Zahlen (Tageswerte & Gesamt) ohne Erfassung
 * personenbezogener Daten (keine IP-Speicherung, DSGVO-konform).
 * ============================================================================
 */

class StatsManager {

    /**
     * Pfad zur Statistik-Datei.
     * @return string
     */
    protected static function getFile(): string {
        $dir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/stats.json';
    }

    /**
     * Lädt bestehende Statistik-Daten.
     * @return array
     */
    public static function getStats(): array {
        $file = self::getFile();
        if (!file_exists($file)) {
            return [
                'total_views' => 0,
                'daily'       => []
            ];
        }

        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) {
            return [
                'total_views' => 0,
                'daily'       => []
            ];
        }

        return $data;
    }

    /**
     * Erfasst einen Frontend-Aufruf, sofern es sich nicht um einen bekannten Bot handelt.
     * @return void
     */
    public static function recordHit(): void {
        if (php_sapi_name() === 'cli') {
            return;
        }

        // Nicht zählen, wenn ein angemeldeter Admin im Frontend surft
        if (!empty($_SESSION['admin_logged_in'])) {
            return;
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $bots = ['bot', 'crawler', 'spider', 'curl', 'wget', 'slurp', 'facebookexternalhit', 'mediapartners-google'];
        foreach ($bots as $bot) {
            if (strpos($ua, $bot) !== false) {
                return;
            }
        }

        $stats = self::getStats();
        $today = date('Y-m-d');

        $stats['total_views'] = ($stats['total_views'] ?? 0) + 1;
        if (!isset($stats['daily'])) {
            $stats['daily'] = [];
        }

        $stats['daily'][$today] = ($stats['daily'][$today] ?? 0) + 1;

        // Nur die letzten 30 Tage aufbewahren
        if (count($stats['daily']) > 30) {
            ksort($stats['daily']);
            $stats['daily'] = array_slice($stats['daily'], -30, 30, true);
        }

        @file_put_contents(self::getFile(), json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Liefert aufbereitete Dashboard-Kennzahlen (Heute, Gestern, Letzte 7 Tage).
     * @return array
     */
    public static function getDashboardSummary(): array {
        $stats = self::getStats();
        $daily = $stats['daily'] ?? [];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $todayViews = $daily[$today] ?? 0;
        $yesterdayViews = $daily[$yesterday] ?? 0;
        $totalViews = $stats['total_views'] ?? 0;

        // Die letzten 7 Tage vorbereiten (inkl. Tage ohne Aufrufe)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayLabel = date('d.m.', strtotime($date));
            $last7Days[$date] = [
                'label' => $dayLabel,
                'views' => $daily[$date] ?? 0
            ];
        }

        return [
            'total_views'     => $totalViews,
            'today_views'     => $todayViews,
            'yesterday_views' => $yesterdayViews,
            'last_7_days'     => $last7Days
        ];
    }
}
