<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Frontend Ansicht & Buchungsformular
 * ============================================================================
 * Unterstützt Tag / Woche / Monat / Agenda-Listenansicht, Filter, .ics-Export,
 * webcal:// Abo-Link, DSGVO-Bereinigung und CSP-konforme Event-Delegation.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

if (!function_exists('weave_calendar_render_frontend')) {

    function weave_calendar_render_frontend(array $opts = []): string {
        static $instanceNum = 0;
        $instanceNum++;

        $t = static fn(string $key, string $default, array $params = []): string => function_exists('__')
            ? __($key, $default, null, $params)
            : str_replace(array_map(static fn($k) => '{' . $k . '}', array_keys($params)), array_values($params), $default);

        $cfg = CalendarManager::getConfig();
        $allowedViews = ['month', 'week', 'day', 'agenda'];
        $reqView = trim((string)($_GET['cal_view'] ?? $opts['view'] ?? $cfg['default_view'] ?? 'month'));
        $view = in_array($reqView, $allowedViews, true) ? $reqView : 'month';

        $refDateRaw = trim((string)($_GET['cal_date'] ?? date('Y-m-d')));
        $refTs = strtotime($refDateRaw) ?: time();
        $refDate = date('Y-m-d', $refTs);

        $catFilter = trim((string)($_GET['cal_cat'] ?? $opts['category'] ?? ''));
        $staffFilter = trim((string)($opts['staff'] ?? ''));
        $searchFilter = trim((string)($_GET['cal_q'] ?? ''));

        if ($view === 'day') {
            $rangeStart = date('Y-m-d 00:00:00', $refTs);
            $rangeEnd = date('Y-m-d 23:59:59', $refTs);
            $prevDate = date('Y-m-d', strtotime('-1 day', $refTs));
            $nextDate = date('Y-m-d', strtotime('+1 day', $refTs));
            $periodLabel = date('d.m.Y', $refTs);
        } elseif ($view === 'week') {
            $mondayTs = strtotime('monday this week', $refTs);
            $sundayTs = strtotime('sunday this week', $refTs);
            $rangeStart = date('Y-m-d 00:00:00', $mondayTs);
            $rangeEnd = date('Y-m-d 23:59:59', $sundayTs);
            $prevDate = date('Y-m-d', strtotime('-7 days', $mondayTs));
            $nextDate = date('Y-m-d', strtotime('+7 days', $mondayTs));
            $periodLabel = 'KW ' . date('W', $mondayTs) . ' (' . date('d.m.', $mondayTs) . ' – ' . date('d.m.Y', $sundayTs) . ')';
        } elseif ($view === 'agenda') {
            $rangeStart = date('Y-m-d 00:00:00', $refTs);
            $rangeEnd = date('Y-m-d 23:59:59', strtotime('+60 days', $refTs));
            $prevDate = date('Y-m-d', strtotime('-30 days', $refTs));
            $nextDate = date('Y-m-d', strtotime('+30 days', $refTs));
            $periodLabel = 'Nächste 60 Tage ab ' . date('d.m.Y', $refTs);
        } else {
            $firstDayTs = strtotime(date('Y-m-01', $refTs));
            $lastDayTs = strtotime(date('Y-m-t', $refTs));
            $rangeStart = date('Y-m-d 00:00:00', $firstDayTs);
            $rangeEnd = date('Y-m-d 23:59:59', $lastDayTs);
            $prevDate = date('Y-m-d', strtotime('-1 month', $firstDayTs));
            $nextDate = date('Y-m-d', strtotime('+1 month', $firstDayTs));
            $periodLabel = date('m / Y', $firstDayTs);
        }

        $isAdmin = !empty($_SESSION['admin_logged_in']);
        $events = CalendarManager::expandEventsForRange($rangeStart, $rangeEnd, [
            'public_only' => !$isAdmin,
            'category_id' => $catFilter,
            'staff_id'    => $staffFilter,
            'search'      => $searchFilter,
        ]);

        $eventsByDate = [];
        foreach ($events as $ev) {
            $dKey = substr((string)$ev['occurrence_start'], 0, 10);
            $eventsByDate[$dKey][] = $ev;
        }

        $publicCats = array_values(array_filter(
            CalendarManager::getCategories(),
            static fn(array $c): bool => $isAdmin || !empty($c['is_public'])
        ));

        $csrf = '';
        if (class_exists('AuthManager') && method_exists('AuthManager', 'getCsrfToken')) {
            $csrf = AuthManager::getCsrfToken();
        } elseif (!empty($_SESSION['csrf_token'])) {
            $csrf = (string)$_SESSION['csrf_token'];
        } else {
            $csrf = bin2hex(random_bytes(16));
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['csrf_token'] = $csrf;
            }
        }

        $flash = $_SESSION['weave_cal_frontend_flash'] ?? null;
        unset($_SESSION['weave_cal_frontend_flash']);

        $containerId = 'weave-cal-' . $instanceNum;
        $feedToken = (string)($cfg['feed_token'] ?? '');
        $icsPath = '?weave_cal_ics=1&token=' . urlencode($feedToken);
        $baseUrl = class_exists('SecurityManager') ? rtrim(SecurityManager::getBaseUrl(), '/') : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $webcalBase = preg_replace('~^https?://~i', 'webcal://', $baseUrl);
        $webcalUrl = $webcalBase . '/' . ltrim($icsPath, '/');

        $buildNavUrl = static function (array $override) use ($view, $refDate, $catFilter, $searchFilter): string {
            $params = $_GET;
            $params['cal_view'] = $override['view'] ?? $view;
            $params['cal_date'] = $override['date'] ?? $refDate;
            $params['cal_cat'] = $override['cat'] ?? $catFilter;
            if (($override['q'] ?? $searchFilter) !== '') {
                $params['cal_q'] = $override['q'] ?? $searchFilter;
            } else {
                unset($params['cal_q']);
            }
            return '?' . http_build_query($params) . '#calendar';
        };

        ob_start();
        ?>
        <div id="<?php echo htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8'); ?>"
             class="card weave-calendar-widget"
             style="padding: 1.5rem; border-radius: var(--card-radius, 16px); border: 1px solid var(--border-color, rgba(0,0,0,0.1)); background: var(--bg-card, #ffffff); margin-bottom: 1.5rem;">

            <?php if ($flash && !empty($flash['text'])): ?>
                <div style="margin-bottom: 1rem; padding: 10px 14px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; background: <?php echo !empty($flash['ok']) ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)'; ?>; color: <?php echo !empty($flash['ok']) ? '#065f46' : '#991b1b'; ?>; border: 1px solid <?php echo !empty($flash['ok']) ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'; ?>;">
                    <?php echo htmlspecialchars((string)$flash['text'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Kopfzeile: Ansichten & Navigation -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <a href="<?php echo htmlspecialchars($buildNavUrl(['date' => $prevDate]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 6px 12px; text-decoration: none;">◀</a>
                    <a href="<?php echo htmlspecialchars($buildNavUrl(['date' => date('Y-m-d')]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 6px 12px; text-decoration: none; font-size: 0.85rem;"><?php echo htmlspecialchars($t('calendar.today', 'Heute'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <a href="<?php echo htmlspecialchars($buildNavUrl(['date' => $nextDate]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 6px 12px; text-decoration: none;">▶</a>
                    <strong style="font-size: 1.08rem; margin-left: 6px; color: var(--text-primary, #0f172a);">
                        <?php echo htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </strong>
                </div>

                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <?php foreach (['day' => $t('calendar.view_day', 'Tag'), 'week' => $t('calendar.view_week', 'Woche'), 'month' => $t('calendar.view_month', 'Monat'), 'agenda' => $t('calendar.view_agenda', 'Agenda')] as $vKey => $vLabel): ?>
                        <a href="<?php echo htmlspecialchars($buildNavUrl(['view' => $vKey]), ENT_QUOTES, 'UTF-8'); ?>"
                           class="btn <?php echo $view === $vKey ? 'btn-primary' : 'btn-outline'; ?>"
                           style="padding: 6px 12px; font-size: 0.84rem; text-decoration: none;">
                            <?php echo htmlspecialchars($vLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filter & .ics Abo -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 10px 12px; border-radius: 10px; background: rgba(148,163,184,0.08); margin-bottom: 1rem;">
                <form method="GET" action="" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 0;">
                    <?php if (!empty($_GET['page'])): ?>
                        <input type="hidden" name="page" value="<?php echo htmlspecialchars((string)$_GET['page'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="cal_view" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cal_date" value="<?php echo htmlspecialchars($refDate, ENT_QUOTES, 'UTF-8'); ?>">
                    <select name="cal_cat" class="form-control" style="padding: 5px 10px; font-size: 0.85rem; width: auto;">
                        <option value=""><?php echo htmlspecialchars($t('calendar.all_categories', 'Alle Kategorien'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($publicCats as $c): ?>
                            <option value="<?php echo htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $catFilter === $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="cal_q" value="<?php echo htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($t('calendar.search_placeholder', 'Termin suchen...'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control" style="padding: 5px 10px; font-size: 0.85rem; width: 180px;">
                    <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.84rem;"><?php echo htmlspecialchars($t('calendar.filter_btn', 'Filtern'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>

                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <a href="<?php echo htmlspecialchars($icsPath, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 5px 11px; font-size: 0.82rem; text-decoration: none;"><?php echo htmlspecialchars($t('calendar.ics_export', '⬇ .ics Export'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <a href="<?php echo htmlspecialchars($webcalUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="padding: 5px 11px; font-size: 0.82rem; text-decoration: none;" title="<?php echo htmlspecialchars($t('calendar.subscribe', 'Kalender abonnieren'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('calendar.subscribe', '📅 Kalender abonnieren'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php if (!empty($cfg['allow_public_booking'])): ?>
                        <button type="button"
                                class="btn btn-primary weave-cal-toggle-booking"
                                data-target="<?php echo htmlspecialchars($containerId . '-booking', ENT_QUOTES, 'UTF-8'); ?>"
                                style="padding: 5px 12px; font-size: 0.82rem;">
                            <?php echo htmlspecialchars($t('calendar.request_btn', '+ Termin anfragen'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Buchungsanfrage-Drawer -->
            <?php if (!empty($cfg['allow_public_booking'])): ?>
                <div id="<?php echo htmlspecialchars($containerId . '-booking', ENT_QUOTES, 'UTF-8'); ?>"
                     style="display: none; margin-bottom: 1.25rem; padding: 1.15rem; border-radius: 12px; border: 1px solid var(--border-color, rgba(0,0,0,0.12)); background: rgba(16,185,129,0.05);">
                    <h4 style="margin: 0 0 0.75rem 0;"><?php echo htmlspecialchars($t('calendar.booking_title', 'Unverbindliche Terminanfrage'), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        <input type="hidden" name="weave_cal_action" value="booking_request">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="text" name="name" required placeholder="<?php echo htmlspecialchars($t('calendar.booking_name', 'Dein Name *'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                        <input type="email" name="email" required placeholder="<?php echo htmlspecialchars($t('calendar.booking_email', 'Deine E-Mail *'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                        <input type="text" name="subject" required placeholder="<?php echo htmlspecialchars($t('calendar.booking_subject', 'Anliegen / Terminwunsch *'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                        <input type="datetime-local" name="preferred_start" required class="form-control">
                        <select name="duration_min" class="form-control">
                            <option value="30"><?php echo htmlspecialchars($t('calendar.minutes', '{min} Minuten', ['min' => 30]), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="60" selected><?php echo htmlspecialchars($t('calendar.minutes', '{min} Minuten', ['min' => 60]), ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="90"><?php echo htmlspecialchars($t('calendar.minutes', '{min} Minuten', ['min' => 90]), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                        <input type="text" name="notes" placeholder="<?php echo htmlspecialchars($t('calendar.booking_notes', 'Optionale Hinweise...'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary" style="padding: 8px 18px;"><?php echo htmlspecialchars($t('calendar.booking_submit', 'Anfrage absenden'), ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Ansichten: Tag / Woche / Monat / Agenda -->
            <?php if ($view === 'month'): ?>
                <?php
                    $firstTs = strtotime(date('Y-m-01', $refTs));
                    $daysInMonth = (int)date('t', $firstTs);
                    $startWeekday = (int)date('N', $firstTs); // 1=Mo .. 7=So
                    $weekdays = [
                        $t('calendar.wd_1', 'Mo'), $t('calendar.wd_2', 'Di'), $t('calendar.wd_3', 'Mi'),
                        $t('calendar.wd_4', 'Do'), $t('calendar.wd_5', 'Fr'), $t('calendar.wd_6', 'Sa'),
                        $t('calendar.wd_7', 'So'),
                    ];
                ?>
                <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;">
                    <?php foreach ($weekdays as $wd): ?>
                        <div style="text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-secondary, #64748b); padding: 4px 0;">
                            <?php echo $wd; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php for ($blank = 1; $blank < $startWeekday; $blank++): ?>
                        <div style="min-height: 78px; border-radius: 8px; background: rgba(148,163,184,0.05);"></div>
                    <?php endfor; ?>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php
                            $dStr = date('Y-m', $firstTs) . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
                            $dayEvents = $eventsByDate[$dStr] ?? [];
                            $isToday = ($dStr === date('Y-m-d'));
                        ?>
                        <div style="min-height: 82px; padding: 6px; border-radius: 8px; border: 1px solid <?php echo $isToday ? 'var(--primary, #0f766e)' : 'var(--border-color, rgba(0,0,0,0.08))'; ?>; background: <?php echo $isToday ? 'rgba(15,118,110,0.06)' : 'transparent'; ?>;">
                            <div style="font-size: 0.78rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary, #0f172a);">
                                <a href="<?php echo htmlspecialchars($buildNavUrl(['view' => 'day', 'date' => $dStr]), ENT_QUOTES, 'UTF-8'); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo $day; ?>
                                </a>
                            </div>
                            <?php foreach (array_slice($dayEvents, 0, 3) as $ev): ?>
                                <div style="font-size: 0.72rem; padding: 2px 5px; margin-bottom: 3px; border-radius: 4px; color: #fff; background: <?php echo htmlspecialchars((string)($ev['category_color'] ?? '#0f766e'), ENT_QUOTES, 'UTF-8'); ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                     title="<?php echo htmlspecialchars((string)$ev['title'] . ' (' . substr((string)$ev['occurrence_start'], 11, 5) . ')', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo !empty($ev['all_day']) ? '' : (htmlspecialchars(substr((string)$ev['occurrence_start'], 11, 5), ENT_QUOTES, 'UTF-8') . ' '); ?>
                                    <?php echo htmlspecialchars((string)$ev['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($dayEvents) > 3): ?>
                                <div style="font-size: 0.7rem; color: var(--text-secondary, #64748b);">+<?php echo count($dayEvents) - 3; ?> weitere</div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php elseif ($view === 'week'): ?>
                <?php $mondayTs = strtotime('monday this week', $refTs); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                    <?php for ($w = 0; $w < 7; $w++): ?>
                        <?php
                            $curTs = strtotime("+{$w} days", $mondayTs);
                            $dStr = date('Y-m-d', $curTs);
                            $dayEvents = $eventsByDate[$dStr] ?? [];
                        ?>
                        <div style="padding: 10px; border-radius: 10px; border: 1px solid var(--border-color, rgba(0,0,0,0.1));">
                            <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; border-bottom: 1px solid var(--border-color, rgba(0,0,0,0.08)); padding-bottom: 4px;">
                                <?php echo date('D, d.m.', $curTs); ?>
                            </div>
                            <?php if (empty($dayEvents)): ?>
                                <div style="font-size: 0.78rem; color: var(--text-secondary, #94a3b8);">Keine Termine</div>
                            <?php else: ?>
                                <?php foreach ($dayEvents as $ev): ?>
                                    <div style="padding: 6px 8px; margin-bottom: 6px; border-radius: 6px; border-left: 3px solid <?php echo htmlspecialchars((string)($ev['category_color'] ?? '#0f766e'), ENT_QUOTES, 'UTF-8'); ?>; background: rgba(148,163,184,0.08); font-size: 0.8rem;">
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars((string)$ev['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size: 0.74rem; color: var(--text-secondary, #64748b);">
                                            <?php echo !empty($ev['all_day']) ? 'Ganztägig' : (substr((string)$ev['occurrence_start'], 11, 5) . ' – ' . substr((string)$ev['occurrence_end'], 11, 5)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php else: /* day oder agenda */ ?>
                <?php if (empty($events)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary, #64748b);">
                        In diesem Zeitraum sind keine öffentlichen Termine eingetragen.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($events as $ev): ?>
                            <?php
                                $stTs = strtotime((string)$ev['occurrence_start']);
                                $enTs = strtotime((string)$ev['occurrence_end']);
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border-color, rgba(0,0,0,0.1)); border-left: 4px solid <?php echo htmlspecialchars((string)($ev['category_color'] ?? '#0f766e'), ENT_QUOTES, 'UTF-8'); ?>;">
                                <div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary, #64748b); font-weight: 600;">
                                        <?php echo date('d.m.Y', $stTs); ?>
                                        · <?php echo !empty($ev['all_day']) ? 'Ganztägig' : (date('H:i', $stTs) . ' – ' . date('H:i', $enTs)); ?>
                                        · <?php echo htmlspecialchars((string)($ev['category_name'] ?? 'Termin'), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div style="font-size: 1rem; font-weight: 700; color: var(--text-primary, #0f172a);">
                                        <?php echo htmlspecialchars((string)$ev['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($ev['location'])): ?>
                                        <div style="font-size: 0.82rem; color: var(--text-secondary, #64748b);">
                                            📍 <?php echo htmlspecialchars((string)$ev['location'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="?weave_cal_ics_event=<?php echo urlencode((string)$ev['id']); ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">⬇ .ics</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean() . weave_calendar_frontend_script_once();
    }

    function weave_calendar_frontend_script_once(): string {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;
        $nonceAttr = class_exists('SecurityManager') ? SecurityManager::cspNonceAttr() : '';
        return <<<HTML
<script{$nonceAttr}>
(function() {
    if (window.__weaveCalendarBound) return;
    window.__weaveCalendarBound = true;
    document.addEventListener('click', function(e) {
        const btn = e.target && e.target.closest ? e.target.closest('.weave-cal-toggle-booking') : null;
        if (!btn) return;
        const box = document.getElementById(btn.getAttribute('data-target') || '');
        if (box) {
            box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
        }
    });
})();
</script>
HTML;
    }
}
