<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Backend-Arbeitsbereich (admin/settings.php)
 * ============================================================================
 * Eigenständiges Team-Kalender- & Ressourcenplanungstool im Admin-Backend
 * inkl. Mitarbeiter-Übersicht, Konfliktprüfung, Google/Microsoft/CalDAV Sync
 * und automatischer E-Mail-Erinnerungen.
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

$subtab = $_GET['cal_subtab'] ?? 'team';
$teamView = $_GET['team_view'] ?? 'day';
$refDateRaw = trim((string)($_GET['team_date'] ?? date('Y-m-d')));
$refTs = strtotime($refDateRaw) ?: time();
$refDate = date('Y-m-d', $refTs);

$cfg = CalendarManager::getConfig();
$staffList = CalendarManager::getStaff();
$categories = CalendarManager::getCategories();
$bookings = CalendarManager::getBookings();
$syncLogs = CalendarSync::getSyncLog(20);

$editEventId = trim((string)($_GET['edit_event'] ?? ''));
$editEvent = $editEventId !== '' ? CalendarManager::getEvent($editEventId) : null;

$notice = $_SESSION['weave_cal_admin_msg'] ?? null;
unset($_SESSION['weave_cal_admin_msg']);
$conflicts = $_SESSION['weave_cal_conflicts'] ?? [];
unset($_SESSION['weave_cal_conflicts']);

$pendingBookings = count(array_filter($bookings, static fn(array $b): bool => ($b['status'] ?? 'pending') === 'pending'));
$totalEvents = count(CalendarManager::getEvents());

$staffStatusLabels = [
    'available' => ['Verfügbar', '#10b981', 'rgba(16,185,129,0.12)'],
    'busy'      => ['Im Termin', '#f59e0b', 'rgba(245,158,11,0.12)'],
    'vacation'  => ['Urlaub',    '#3b82f6', 'rgba(59,130,246,0.12)'],
    'sick'      => ['Abwesend',  '#ef4444', 'rgba(239,68,68,0.12)'],
];

$dayStart = $refDate . ' 00:00:00';
$dayEnd = $refDate . ' 23:59:59';
if ($teamView === 'week') {
    $monTs = strtotime('monday this week', $refTs);
    $sunTs = strtotime('sunday this week', $refTs);
    $rangeStart = date('Y-m-d 00:00:00', $monTs);
    $rangeEnd = date('Y-m-d 23:59:59', $sunTs);
} elseif ($teamView === 'month') {
    $rangeStart = date('Y-m-01 00:00:00', $refTs);
    $rangeEnd = date('Y-m-t 23:59:59', $refTs);
} else {
    $rangeStart = $dayStart;
    $rangeEnd = $dayEnd;
}
$rangeEvents = CalendarManager::expandEventsForRange($rangeStart, $rangeEnd, ['include_cancelled' => true]);
?>

<div class="plugin-settings-wrapper" style="max-width: 1180px; margin: 0 auto; padding: 15px;">
    <!-- Header Banner -->
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #0f766e 100%); color: #fff; border-radius: 12px; padding: 20px 24px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(255,255,255,0.16); display: flex; align-items: center; justify-content: center;">
                <span class="material-icons" style="font-size: 28px; color: #fff;">calendar_month</span>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800; color: #fff;">Weave Calendar – Terminplaner &amp; Team-Kalender</h3>
                <p style="margin: 4px 0 0 0; opacity: 0.92; font-size: 0.87rem;">
                    Mitarbeiter-Ressourcenplanung, Serientermine (RRULE), Konfliktprüfung, E-Mail-Erinnerungen (.ics) &amp; Cloud-Sync (Google, Microsoft, iCloud/CalDAV).
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&open_editor=1#tab-plugins" class="btn" style="background: #fff; color: #0f766e; font-weight: 700; text-decoration: none; padding: 8px 14px; border-radius: 8px;">+ Neuer Termin</a>
        </div>
    </div>

    <?php if (is_array($notice) && !empty($notice['text'])): ?>
        <div style="margin-bottom: 14px; padding: 11px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; background: <?php echo !empty($notice['ok']) ? '#f0fdf4' : '#fef2f2'; ?>; border-left: 4px solid <?php echo !empty($notice['ok']) ? '#16a34a' : '#dc2626'; ?>; color: <?php echo !empty($notice['ok']) ? '#166534' : '#991b1b'; ?>;">
            <?php echo htmlspecialchars((string)$notice['text']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($conflicts)): ?>
        <div style="margin-bottom: 14px; padding: 12px 16px; border-radius: 8px; background: #fffbeb; border-left: 4px solid #d97706; color: #92400e; font-size: 13px;">
            <strong>⚠️ Konfliktwarnung (Doppelbelegung erkannt):</strong>
            <ul style="margin: 6px 0 0 18px; padding: 0;">
                <?php foreach ($conflicts as $cf): ?>
                    <li><?php echo htmlspecialchars($cf['staff_name'] . ': Überschneidung mit „' . $cf['event_title'] . '“ (' . $cf['start_at'] . ')'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 18px;">
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary); font-weight: 700;">Termine Gesamt</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f766e;"><?php echo $totalEvents; ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary); font-weight: 700;">Termine im Zeitraum</div>
            <div style="font-size: 20px; font-weight: 800; color: #2563eb;"><?php echo count($rangeEvents); ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary); font-weight: 700;">Mitarbeiter / Team</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f766e;"><?php echo count($staffList); ?></div>
        </div>
        <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary); font-weight: 700;">Offene Anfragen</div>
            <div style="font-size: 20px; font-weight: 800; color: #d97706;"><?php echo $pendingBookings; ?></div>
        </div>
    </div>

    <!-- Sub-Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color, #e2e8f0); margin-bottom: 18px; flex-wrap: wrap;">
        <?php
            $tabs = [
                'team'     => '📅 Team-Kalender & Termine',
                'staff'    => '👥 Mitarbeiter & Kategorien',
                'bookings' => '📩 Terminanfragen (' . $pendingBookings . ')',
                'sync'     => '🔄 Cloud-Sync & .ics',
                'settings' => '🔔 Erinnerungen & Optionen',
            ];
            foreach ($tabs as $tk => $tl):
        ?>
            <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=<?php echo $tk; ?>#tab-plugins"
               class="btn <?php echo $subtab === $tk ? 'btn-primary' : 'btn-outline'; ?>"
               style="padding: 8px 14px; font-weight: 700; font-size: 13px; text-decoration: none;">
                <?php echo htmlspecialchars($tl); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($subtab === 'team'): ?>
        <!-- Ansichts-Umschalter + Datum -->
        <div class="card" style="padding: 1rem 1.25rem; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&team_view=<?php echo urlencode($teamView); ?>&team_date=<?php echo date('Y-m-d', strtotime('-1 ' . ($teamView === 'month' ? 'month' : ($teamView === 'week' ? 'week' : 'day')), $refTs)); ?>#tab-plugins" class="btn btn-outline" style="padding: 5px 10px;">◀</a>
                <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&team_view=<?php echo urlencode($teamView); ?>&team_date=<?php echo date('Y-m-d'); ?>#tab-plugins" class="btn btn-outline" style="padding: 5px 10px;">Heute</a>
                <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&team_view=<?php echo urlencode($teamView); ?>&team_date=<?php echo date('Y-m-d', strtotime('+1 ' . ($teamView === 'month' ? 'month' : ($teamView === 'week' ? 'week' : 'day')), $refTs)); ?>#tab-plugins" class="btn btn-outline" style="padding: 5px 10px;">▶</a>
                <strong style="margin-left: 6px;"><?php echo htmlspecialchars($refDate); ?></strong>
            </div>
            <div style="display: flex; gap: 6px;">
                <?php foreach (['day' => 'Tag (Mitarbeiter-Spalten)', 'week' => 'Woche', 'month' => 'Monat'] as $tv => $tvLabel): ?>
                    <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&team_view=<?php echo $tv; ?>&team_date=<?php echo urlencode($refDate); ?>#tab-plugins"
                       class="btn <?php echo $teamView === $tv ? 'btn-primary' : 'btn-outline'; ?>"
                       style="padding: 5px 12px; font-size: 12.5px; text-decoration: none;">
                        <?php echo htmlspecialchars($tvLabel); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($teamView === 'day'): ?>
            <!-- Mitarbeiter-Ressourcen-Spalten (Tagesübersicht) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 20px;">
                <?php foreach ($staffList as $st): ?>
                    <?php
                        $stBadge = $staffStatusLabels[$st['status'] ?? 'available'] ?? $staffStatusLabels['available'];
                        $stEvents = array_values(array_filter($rangeEvents, static fn(array $ev): bool => in_array($st['id'], (array)($ev['staff_ids'] ?? []), true)));
                    ?>
                    <div class="card" style="padding: 1rem; border-top: 4px solid <?php echo htmlspecialchars((string)($st['color'] ?? '#0f766e')); ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong><?php echo htmlspecialchars((string)$st['name']); ?></strong>
                            <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; color: <?php echo $stBadge[1]; ?>; background: <?php echo $stBadge[2]; ?>;">
                                <?php echo htmlspecialchars($stBadge[0]); ?>
                            </span>
                        </div>
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 10px;">
                            Auslastung am Tag: <?php echo count($stEvents); ?> Termin(e) · Rolle: <?php echo htmlspecialchars((string)($st['role'] ?? 'member')); ?>
                        </div>
                        <?php if (empty($stEvents)): ?>
                            <div style="font-size: 12px; color: #94a3b8; padding: 10px; border-radius: 8px; background: rgba(148,163,184,0.06);">Keine zugewiesenen Termine</div>
                        <?php else: ?>
                            <?php foreach ($stEvents as $ev): ?>
                                <div style="padding: 8px 10px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 8px; border-left: 3px solid <?php echo htmlspecialchars((string)($ev['category_color'] ?? '#0f766e')); ?>;">
                                    <div style="font-weight: 700; font-size: 13px;"><?php echo htmlspecialchars((string)$ev['title']); ?></div>
                                    <div style="font-size: 11.5px; color: #64748b;">
                                        <?php echo !empty($ev['all_day']) ? 'Ganztägig' : (substr((string)$ev['occurrence_start'], 11, 5) . ' – ' . substr((string)$ev['occurrence_end'], 11, 5)); ?>
                                        · <?php echo htmlspecialchars((string)($ev['status'] ?? 'confirmed')); ?>
                                    </div>
                                    <div style="margin-top: 6px; display: flex; gap: 6px;">
                                        <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&edit_event=<?php echo urlencode((string)$ev['id']); ?>#tab-plugins" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; text-decoration: none;">✏️ Bearbeiten</a>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Termin löschen?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="cal_delete_event">
                                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string)$ev['id']); ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; color: #dc2626;">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 1.25rem; margin-bottom: 20px;">
                <h4 style="margin-top: 0;">Termine im gewählten Zeitraum (<?php echo count($rangeEvents); ?>)</h4>
                <?php if (empty($rangeEvents)): ?>
                    <p style="color: var(--text-secondary);">Keine Termine in diesem Zeitraum.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($rangeEvents as $ev): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; border-left: 4px solid <?php echo htmlspecialchars((string)($ev['category_color'] ?? '#0f766e')); ?>;">
                                <div>
                                    <strong><?php echo htmlspecialchars((string)$ev['title']); ?></strong>
                                    <div style="font-size: 12px; color: #64748b;">
                                        <?php echo htmlspecialchars((string)$ev['occurrence_start']); ?> bis <?php echo htmlspecialchars((string)$ev['occurrence_end']); ?>
                                        · Sichtbarkeit: <?php echo htmlspecialchars((string)($ev['visibility'] ?? 'public')); ?>
                                        <?php if (!empty($ev['rrule'])): ?> · Serie: <code><?php echo htmlspecialchars((string)$ev['rrule']); ?></code><?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 6px;">
                                    <a href="?tab=tab-plugins&plugin_settings=weave-calendar&cal_subtab=team&edit_event=<?php echo urlencode((string)$ev['id']); ?>#tab-plugins" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px; text-decoration: none;">✏️</a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Termin löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="cal_delete_event">
                                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string)$ev['id']); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Termin-Editor -->
        <?php
            $evForm = $editEvent ?? [
                'id'          => '',
                'title'       => '',
                'description' => '',
                'location'    => '',
                'meeting_url' => '',
                'start_at'    => $refDate . 'T09:00',
                'end_at'      => $refDate . 'T10:00',
                'all_day'     => false,
                'category_id' => $categories[0]['id'] ?? 'cat_public',
                'status'      => 'confirmed',
                'visibility'  => 'public',
                'staff_ids'   => [],
                'attendees'   => [],
                'rrule'       => '',
                'reminders'   => $cfg['default_reminders'] ?? [1440, 60],
            ];
            $dtLocal = static function (?string $dt): string {
                $ts = strtotime((string)$dt);
                return $ts ? date('Y-m-d\TH:i', $ts) : '';
            };
            $attText = implode(', ', array_map(static fn(array $a): string => trim(($a['name'] ? $a['name'] . ' <' . $a['email'] . '>' : $a['email'])), (array)($evForm['attendees'] ?? [])));
        ?>
        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top: 0;"><?php echo $editEvent ? '✏️ Termin bearbeiten' : '➕ Neuen Termin anlegen'; ?></h4>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="cal_save_event">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$evForm['id']); ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Titel *</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars((string)$evForm['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Kategorie</label>
                        <select name="category_id" class="form-control">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo htmlspecialchars((string)$c['id']); ?>" <?php echo ($evForm['category_id'] ?? '') === $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="confirmed" <?php echo ($evForm['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Bestätigt (confirmed)</option>
                            <option value="tentative" <?php echo ($evForm['status'] ?? '') === 'tentative' ? 'selected' : ''; ?>>Vorläufig (tentative)</option>
                            <option value="cancelled" <?php echo ($evForm['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Abgesagt (cancelled)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sichtbarkeit (DSGVO)</label>
                        <select name="visibility" class="form-control">
                            <option value="public" <?php echo ($evForm['visibility'] ?? '') === 'public' ? 'selected' : ''; ?>>Öffentlich (Frontend)</option>
                            <option value="internal" <?php echo ($evForm['visibility'] ?? '') === 'internal' ? 'selected' : ''; ?>>Intern (nur Backend/Team)</option>
                            <option value="private" <?php echo ($evForm['visibility'] ?? '') === 'private' ? 'selected' : ''; ?>>Privat</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Start *</label>
                        <input type="datetime-local" name="start_at" class="form-control" required value="<?php echo htmlspecialchars($dtLocal((string)$evForm['start_at'])); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ende *</label>
                        <input type="datetime-local" name="end_at" class="form-control" required value="<?php echo htmlspecialchars($dtLocal((string)$evForm['end_at'])); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ort / Raum</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars((string)($evForm['location'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Online-Meeting-Link</label>
                        <input type="url" name="meeting_url" class="form-control" value="<?php echo htmlspecialchars((string)($evForm['meeting_url'] ?? '')); ?>" placeholder="https://...">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>Wiederholung (RRULE RFC 5545, optional)</label>
                        <input type="text" name="rrule" class="form-control" value="<?php echo htmlspecialchars((string)($evForm['rrule'] ?? '')); ?>" placeholder="z. B. FREQ=WEEKLY;COUNT=6">
                    </div>
                    <div class="form-group">
                        <label>Erinnerungen (Min. vorab, kommagetrennt)</label>
                        <input type="text" name="reminders" class="form-control" value="<?php echo htmlspecialchars(implode(', ', (array)($evForm['reminders'] ?? [1440, 60]))); ?>">
                    </div>
                    <div class="form-group">
                        <label>Externe Teilnehmer (E-Mail / Name &lt;mail&gt;)</label>
                        <input type="text" name="attendees" class="form-control" value="<?php echo htmlspecialchars($attText); ?>" placeholder="max@example.de">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Zugewiesene Mitarbeiter (Konfliktprüfung aktiv):</label>
                    <div style="display: flex; gap: 14px; flex-wrap: wrap; padding-top: 4px;">
                        <?php foreach ($staffList as $st): ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="staff_ids[]" value="<?php echo htmlspecialchars((string)$st['id']); ?>" <?php echo in_array($st['id'], (array)($evForm['staff_ids'] ?? []), true) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars((string)$st['name']); ?>
                            </label>
                        <?php endforeach; ?>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin-left: 16px;">
                            <input type="checkbox" name="all_day" value="1" <?php echo !empty($evForm['all_day']) ? 'checked' : ''; ?>>
                            Ganztägiger Termin
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label>Beschreibung</label>
                    <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars((string)($evForm['description'] ?? '')); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 9px 20px;">💾 Termin speichern</button>
            </form>
        </div>

    <?php elseif ($subtab === 'staff'): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px;">
            <div class="card" style="padding: 1.5rem;">
                <h4 style="margin-top: 0;">Mitarbeiter verwalten</h4>
                <?php foreach ($staffList as $st): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                        <div>
                            <strong style="color: <?php echo htmlspecialchars((string)$st['color']); ?>;">●</strong>
                            <strong><?php echo htmlspecialchars((string)$st['name']); ?></strong>
                            <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars((string)$st['email'] . ' · ' . $st['role'] . ' · ' . $st['status']); ?></div>
                        </div>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="cal_delete_staff">
                            <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars((string)$st['id']); ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 3px 8px; color: #dc2626;">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <form method="POST" action="" style="margin-top: 14px; display: grid; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_save_staff">
                    <input type="text" name="name" required class="form-control" placeholder="Mitarbeitername *">
                    <input type="email" name="email" class="form-control" placeholder="E-Mail für Erinnerungen">
                    <div style="display: flex; gap: 8px;">
                        <select name="role" class="form-control">
                            <option value="member">Mitglied</option>
                            <option value="coordinator">Koordinator</option>
                            <option value="admin">Admin</option>
                            <option value="viewer">Nur Lesen</option>
                        </select>
                        <select name="status" class="form-control">
                            <option value="available">Verfügbar</option>
                            <option value="busy">Im Termin</option>
                            <option value="vacation">Urlaub</option>
                            <option value="sick">Abwesend</option>
                        </select>
                        <input type="color" name="color" value="#2563eb" class="form-control" style="width: 55px; padding: 2px;">
                    </div>
                    <button type="submit" class="btn btn-primary">+ Mitarbeiter speichern</button>
                </form>
            </div>

            <div class="card" style="padding: 1.5rem;">
                <h4 style="margin-top: 0;">Kategorien verwalten</h4>
                <?php foreach ($categories as $c): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                        <div>
                            <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:<?php echo htmlspecialchars((string)$c['color']); ?>;"></span>
                            <strong><?php echo htmlspecialchars((string)$c['name']); ?></strong>
                            <small style="color: #64748b;">(<?php echo !empty($c['is_public']) ? 'Öffentlich' : 'Intern'; ?>)</small>
                        </div>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="cal_delete_category">
                            <input type="hidden" name="cat_id" value="<?php echo htmlspecialchars((string)$c['id']); ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 3px 8px; color: #dc2626;">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <form method="POST" action="" style="margin-top: 14px; display: grid; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_save_category">
                    <input type="text" name="name" required class="form-control" placeholder="Kategoriename *">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="color" value="#0f766e" class="form-control" style="width: 55px; padding: 2px;">
                        <label style="display:flex; align-items:center; gap:6px;"><input type="checkbox" name="is_public" value="1" checked> Im öffentlichen Kalender anzeigen</label>
                    </div>
                    <button type="submit" class="btn btn-primary">+ Kategorie hinzufügen</button>
                </form>
            </div>
        </div>

    <?php elseif ($subtab === 'bookings'): ?>
        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top: 0;">Terminanfragen von Website-Besuchern</h4>
            <?php if (empty($bookings)): ?>
                <p style="color: var(--text-secondary);">Aktuell liegen keine Terminanfragen vor.</p>
            <?php else: ?>
                <?php foreach (array_reverse($bookings) as $b): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <strong><?php echo htmlspecialchars((string)$b['subject']); ?></strong> — <?php echo htmlspecialchars((string)$b['name']); ?> (<?php echo htmlspecialchars((string)$b['email']); ?>)
                            <div style="font-size: 12px; color: #64748b;">
                                Wunschtermin: <?php echo htmlspecialchars((string)$b['preferred_start']); ?> (<?php echo (int)($b['duration_min'] ?? 60); ?> Min.) · Status: <strong><?php echo htmlspecialchars((string)$b['status']); ?></strong>
                            </div>
                        </div>
                        <?php if (($b['status'] ?? 'pending') === 'pending'): ?>
                            <div style="display: flex; gap: 6px;">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="cal_booking_status">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string)$b['id']); ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">✓ Bestätigen</button>
                                </form>
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="cal_booking_status">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string)$b['id']); ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 12px; font-size: 12px;">✕ Ablehnen</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($subtab === 'sync'): ?>
        <?php
            $gCfg = CalendarSync::getProviderConfig('google', false);
            $mCfg = CalendarSync::getProviderConfig('microsoft', false);
            $cCfg = CalendarSync::getProviderConfig('caldav', false);
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <!-- Google -->
            <div class="card" style="padding: 1.25rem;">
                <h4 style="margin-top: 0;">Google Calendar (REST API v3 / OAuth 2.0)</h4>
                <form method="POST" action="" style="display: grid; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_save_sync_provider">
                    <input type="hidden" name="provider" value="google">
                    <label><input type="checkbox" name="enabled" value="1" <?php echo !empty($gCfg['enabled']) ? 'checked' : ''; ?>> Google-Sync aktivieren</label>
                    <input type="text" name="client_id" class="form-control" value="<?php echo htmlspecialchars((string)($gCfg['client_id'] ?? '')); ?>" placeholder="OAuth Client-ID">
                    <input type="password" name="client_secret" class="form-control" placeholder="Client-Secret (leer = behalten)">
                    <input type="password" name="refresh_token" class="form-control" placeholder="Refresh-Token (AES-256 verschlüsselt)">
                    <input type="text" name="calendar_id" class="form-control" value="<?php echo htmlspecialchars((string)($gCfg['calendar_id'] ?? 'primary')); ?>" placeholder="Calendar-ID (primary)">
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
                <form method="POST" action="" style="margin-top: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_trigger_sync">
                    <input type="hidden" name="provider" value="google">
                    <button type="submit" class="btn btn-outline">🔄 Google jetzt synchronisieren</button>
                </form>
            </div>

            <!-- Microsoft 365 / Outlook -->
            <div class="card" style="padding: 1.25rem;">
                <h4 style="margin-top: 0;">Microsoft Outlook / 365 (Graph API)</h4>
                <form method="POST" action="" style="display: grid; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_save_sync_provider">
                    <input type="hidden" name="provider" value="microsoft">
                    <label><input type="checkbox" name="enabled" value="1" <?php echo !empty($mCfg['enabled']) ? 'checked' : ''; ?>> Microsoft-Sync aktivieren</label>
                    <input type="text" name="tenant_id" class="form-control" value="<?php echo htmlspecialchars((string)($mCfg['tenant_id'] ?? 'common')); ?>" placeholder="Tenant-ID (common)">
                    <input type="text" name="client_id" class="form-control" value="<?php echo htmlspecialchars((string)($mCfg['client_id'] ?? '')); ?>" placeholder="Application (Client) ID">
                    <input type="password" name="client_secret" class="form-control" placeholder="Client-Secret (leer = behalten)">
                    <input type="password" name="refresh_token" class="form-control" placeholder="Refresh-Token (AES-256 verschlüsselt)">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
                <form method="POST" action="" style="margin-top: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_trigger_sync">
                    <input type="hidden" name="provider" value="microsoft">
                    <button type="submit" class="btn btn-outline">🔄 Outlook jetzt synchronisieren</button>
                </form>
            </div>

            <!-- Apple iCloud / CalDAV -->
            <div class="card" style="padding: 1.25rem;">
                <h4 style="margin-top: 0;">Apple iCloud &amp; CalDAV</h4>
                <form method="POST" action="" style="display: grid; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_save_sync_provider">
                    <input type="hidden" name="provider" value="caldav">
                    <label><input type="checkbox" name="enabled" value="1" <?php echo !empty($cCfg['enabled']) ? 'checked' : ''; ?>> CalDAV aktivieren</label>
                    <input type="url" name="caldav_url" class="form-control" value="<?php echo htmlspecialchars((string)($cCfg['caldav_url'] ?? '')); ?>" placeholder="https://caldav.icloud.com/...">
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars((string)($cCfg['username'] ?? '')); ?>" placeholder="Apple-ID / Benutzername">
                    <input type="password" name="app_password" class="form-control" placeholder="App-spezifisches Passwort (AES-256)">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
                <form method="POST" action="" style="margin-top: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cal_trigger_sync">
                    <input type="hidden" name="provider" value="caldav">
                    <button type="submit" class="btn btn-outline">🔄 CalDAV jetzt synchronisieren</button>
                </form>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; margin-bottom: 16px;">
            <h4 style="margin-top: 0;">.ics Import &amp; Webcal-Abo</h4>
            <p style="font-size: 12.5px; color: #64748b;">
                Öffentlicher .ics Feed: <code>?weave_cal_ics=1&amp;token=<?php echo htmlspecialchars((string)($cfg['feed_token'] ?? '')); ?></code>
            </p>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="cal_import_ics">
                <textarea name="ics_raw" rows="4" class="form-control" placeholder="VCALENDAR / VEVENT .ics Inhalt hier einfügen..." style="margin-bottom: 8px;"></textarea>
                <button type="submit" class="btn btn-primary">📥 .ics Termine importieren</button>
            </form>
        </div>

        <?php if (!empty($syncLogs)): ?>
            <div class="card" style="padding: 1.25rem;">
                <h4 style="margin-top: 0;">Letzte Sync-Protokolle</h4>
                <?php foreach ($syncLogs as $log): ?>
                    <div style="font-size: 12.5px; padding: 5px 0; border-bottom: 1px solid #e2e8f0;">
                        <strong>[<?php echo htmlspecialchars((string)$log['time']); ?>]</strong>
                        <?php echo htmlspecialchars(strtoupper((string)$log['provider']) . ': ' . (string)$log['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($subtab === 'settings'): ?>
        <div class="card" style="padding: 1.5rem; margin-bottom: 16px;">
            <h4 style="margin-top: 0;">Erinnerungen, DSGVO &amp; Integration</h4>
            <form method="POST" action="" style="display: grid; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="cal_save_config">
                <div class="form-group">
                    <label>Sektions-Überschrift (Frontend)</label>
                    <input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['section_title'] ?? '')); ?>">
                </div>
                <div class="form-group">
                    <label>Standard-Erinnerungsvorlauf (Minuten vor Terminbeginn, z. B. 1440, 60, 15)</label>
                    <input type="text" name="default_reminders" class="form-control" value="<?php echo htmlspecialchars(implode(', ', (array)($cfg['default_reminders'] ?? [1440, 60, 15]))); ?>">
                </div>
                <div class="form-group">
                    <label>Konfliktauflösung bei Cloud-Sync</label>
                    <select name="conflict_resolution" class="form-control">
                        <option value="newest" <?php echo ($cfg['conflict_resolution'] ?? '') === 'newest' ? 'selected' : ''; ?>>Neueste Änderung priorisieren (last-modified)</option>
                        <option value="local" <?php echo ($cfg['conflict_resolution'] ?? '') === 'local' ? 'selected' : ''; ?>>WeaveCMS lokal priorisieren</option>
                        <option value="remote" <?php echo ($cfg['conflict_resolution'] ?? '') === 'remote' ? 'selected' : ''; ?>>Externe Quelle priorisieren</option>
                    </select>
                </div>
                <label><input type="checkbox" name="hide_private_staff_details" value="1" <?php echo !empty($cfg['hide_private_staff_details']) ? 'checked' : ''; ?>> DSGVO: Mitarbeiter- und Teilnehmerdaten im öffentlichen Frontend ausblenden</label>
                <label><input type="checkbox" name="allow_public_booking" value="1" <?php echo !empty($cfg['allow_public_booking']) ? 'checked' : ''; ?>> Öffentliches Terminanfrage-Formular im Frontend aktivieren</label>
                <label><input type="checkbox" name="auto_pseudo_cron" value="1" <?php echo !empty($cfg['auto_pseudo_cron']) ? 'checked' : ''; ?>> Pseudo-Cron Fallback für E-Mail-Erinnerungen aktivieren</label>
                <label><input type="checkbox" name="show_in_main_nav" value="1" <?php echo !empty($cfg['show_in_main_nav']) ? 'checked' : ''; ?>> Optional im Hauptmenü verlinken (Standard: deaktiviert gemäß Menü-Governance)</label>
                <div>
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            </form>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <h4 style="margin-top: 0;">E-Mail-Erinnerungs-Cron &amp; Shortcodes</h4>
            <p style="font-size: 13px; color: #64748b;">
                Cron-Endpunkt: <code>plugins/weave-calendar/cron.php?token=<?php echo htmlspecialchars((string)($cfg['cron_token'] ?? '')); ?></code><br>
                Shortcodes: <code>[calendar]</code> oder <code>[calendar view="week" category="cat_course"]</code>
            </p>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="cal_run_reminders">
                <button type="submit" class="btn btn-outline">🔔 Fällige Erinnerungen jetzt prüfen &amp; senden</button>
            </form>
        </div>
    <?php endif; ?>
</div>
