<?php
/**
 * WeaveCMS – Admin Tab: Dashboard & Übersichtsseite (Phase 10)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var array $dashboard_stats Aufbereitete Zugriffsdaten aus StatsManager
 * @var array $cur_user Aktuell angemeldeter Benutzer
 * @var array $all_users Alle registrierten Benutzerkonten
 * @var array $storage_details Aktiver Speichertreiber
 * @var array $recent_security_logs Letzte Sicherheitsereignisse
 * @var int $inbox_new_count Ungelesene Kontaktanfragen
 * @var int $inbox_total_count Gesamte Kontaktanfragen
 */
?>
        <!-- ──────────────── SECTION 0: DASHBOARD & ÜBERSICHT ──────────────── -->
        <div id="tab-dashboard" class="admin-section card active" style="padding: 2.25rem;">
            
            <!-- Willkommens-Kopfzeile -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin:0 0 0.35rem 0; font-size: 1.65rem; display:flex; align-items:center; gap:10px;">
                        <span class="material-icons" style="font-size:30px;">dashboard</span> Dashboard &amp; Systemübersicht
                    </h2>
                    <p style="color: var(--text-secondary); margin:0; font-size: 0.95rem;">
                        Willkommen zurück, <strong><?php echo htmlspecialchars($cur_user['username']); ?></strong>! Hier ist der aktuelle Status deiner Website.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php echo UserManager::getRoleBadge($cur_user['role']); ?>
                    <?php if (SecurityManager::isHttps()): ?>
                        <span style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.3); border-radius:12px; padding:3px 10px; font-size:0.75rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-icons" style="font-size:14px;">https</span> SSL Aktiv
                        </span>
                    <?php else: ?>
                        <span style="background:rgba(217,119,6,0.12); color:#d97706; border:1px solid rgba(217,119,6,0.3); border-radius:12px; padding:3px 10px; font-size:0.75rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;" title="Lokale Entwicklungs- oder Testumgebung">
                            <span class="material-icons" style="font-size:14px;">http</span> Lokaler Dev-Modus
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 1. KPI-STATISTIKKARTEN -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2.25rem;">
                
                <!-- KPI 1: Frontend-Aufrufe -->
                <div style="background: rgba(46,98,72,0.06); border: 1px solid rgba(46,98,72,0.2); border-radius: 16px; padding: 1.25rem; position:relative; overflow:hidden;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Aufrufe heute</span>
                            <div style="font-size:2rem; font-weight:800; color:var(--primary); margin:0.35rem 0 0.15rem 0; line-height:1;">
                                <?php echo number_format($dashboard_stats['today_views'] ?? 0, 0, ',', '.'); ?>
                            </div>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                Gesamt: <strong><?php echo number_format($dashboard_stats['total_views'] ?? 0, 0, ',', '.'); ?></strong>
                            </span>
                        </div>
                        <span class="material-icons" style="font-size:32px; color:var(--primary); opacity:0.7;">visibility</span>
                    </div>
                </div>

                <!-- KPI 2: Sicherheitsstatus -->
                <div style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2); border-radius: 16px; padding: 1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Sicherheit</span>
                            <div style="font-size:1.35rem; font-weight:800; color:#10b981; margin:0.45rem 0 0.25rem 0; line-height:1.2; display:flex; align-items:center; gap:6px;">
                                <span class="material-icons" style="font-size:20px;">shield</span> Aktiv &amp; Geschützt
                            </div>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                Header, Session &amp; Rate-Limit aktiv
                            </span>
                        </div>
                        <span class="material-icons" style="font-size:32px; color:#10b981; opacity:0.7;">lock</span>
                    </div>
                </div>

                <!-- KPI 3: Kontaktanfragen -->
                <div style="background: rgba(217,119,6,0.06); border: 1px solid rgba(217,119,6,0.2); border-radius: 16px; padding: 1.25rem; cursor:pointer;" onclick="switchTab('tab-inbox')">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Kontaktpostfach</span>
                            <div style="font-size:2rem; font-weight:800; color:#d97706; margin:0.35rem 0 0.15rem 0; line-height:1;">
                                <?php echo (int)$inbox_new_count; ?>
                                <span style="font-size:0.9rem; font-weight:600; color:var(--text-secondary);">neu</span>
                            </div>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                <?php echo (int)$inbox_total_count; ?> Anfrage(n) archiviert
                            </span>
                        </div>
                        <span class="material-icons" style="font-size:32px; color:#d97706; opacity:0.7;">inbox</span>
                    </div>
                </div>

                <!-- KPI 4: Benutzer & Team -->
                <div style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 1.25rem; <?php if ($cur_user['role'] === UserManager::ROLE_ADMIN): ?>cursor:pointer;<?php endif; ?>" <?php if ($cur_user['role'] === UserManager::ROLE_ADMIN): ?>onclick="switchTab('tab-users')"<?php endif; ?>>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Benutzerkonten</span>
                            <div style="font-size:2rem; font-weight:800; color:#6366f1; margin:0.35rem 0 0.15rem 0; line-height:1;">
                                <?php echo count($all_users); ?>
                            </div>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                <?php echo UserManager::countActiveAdmins($data); ?> Admin(s) aktiv
                            </span>
                        </div>
                        <span class="material-icons" style="font-size:32px; color:#6366f1; opacity:0.7;">group</span>
                    </div>
                </div>
            </div>

            <!-- 2. SCHNELLZUGRIFFE (QUICK ACTIONS) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2.25rem;">
                <h4 style="margin:0 0 1rem 0; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); display:flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:18px; color:var(--primary);">bolt</span> Schnellzugriffe &amp; Aktionen
                </h4>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-content', 'tab-blog')">
                        <span class="material-icons" style="font-size:17px; color:var(--primary);">article</span> Neuer Blogartikel
                    </button>
                    <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-content', 'tab-gallery')">
                        <span class="material-icons" style="font-size:17px; color:var(--primary);">add_photo_alternate</span> Bild hochladen
                    </button>
                    <?php if ($cur_user['role'] === UserManager::ROLE_ADMIN): ?>
                        <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-users')">
                            <span class="material-icons" style="font-size:17px; color:var(--primary);">person_add</span> Benutzer anlegen
                        </button>
                        <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-security')">
                            <span class="material-icons" style="font-size:17px; color:var(--primary);">folder_zip</span> 1-Klick-Sicherung
                        </button>
                    <?php endif; ?>
                    <a href="../" target="_blank" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:17px; color:var(--primary);">open_in_new</span> Live-Website
                    </a>
                </div>
            </div>

            <!-- 3. ZWEI-SPALTEN MONITORING: VERLAUF & SICHERHEIT -->
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                
                <!-- Spalte 1: Frontend-Zugriffsverlauf (Letzte 7 Tage) -->
                <div style="background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem;">
                    <h3 style="font-size: 1.1rem; margin:0 0 1rem 0; display:flex; align-items:center; justify-content:space-between; color:var(--text-primary);">
                        <span style="display:flex; align-items:center; gap:8px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">bar_chart</span> Seitenaufrufe (Letzte 7 Tage)
                        </span>
                        <span style="font-size:0.75rem; color:var(--text-secondary); background:rgba(0,0,0,0.04); padding:2px 8px; border-radius:10px;">Cookielos &amp; DSGVO-konform</span>
                    </h3>

                    <?php 
                        $last7 = $dashboard_stats['last_7_days'] ?? [];
                        $maxViews = 1;
                        foreach ($last7 as $day) {
                            if ($day['views'] > $maxViews) {
                                $maxViews = $day['views'];
                            }
                        }
                    ?>

                    <div style="display:flex; align-items:flex-end; gap:8px; height:120px; padding:10px 0 0 0; border-bottom:1px solid var(--border-color); margin-bottom:8px;">
                        <?php foreach ($last7 as $dateKey => $dayData): 
                            $heightPct = max(10, round(($dayData['views'] / $maxViews) * 100));
                            $isToday = ($dateKey === date('Y-m-d'));
                        ?>
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end;">
                                <span style="font-size:0.7rem; color:var(--text-secondary); margin-bottom:4px; font-weight:600;"><?php echo (int)$dayData['views']; ?></span>
                                <div style="width:100%; max-width:28px; height:<?php echo $heightPct; ?>%; background:<?php echo $isToday ? 'var(--primary)' : 'rgba(46,98,72,0.35)'; ?>; border-radius:6px 6px 0 0; transition:height 0.3s ease;" title="<?php echo htmlspecialchars($dayData['label'] . ': ' . $dayData['views'] . ' Aufrufe'); ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:8px;">
                        <?php foreach ($last7 as $dayData): ?>
                            <div style="flex:1; text-align:center; font-size:0.72rem; color:var(--text-secondary); font-weight:600;">
                                <?php echo htmlspecialchars($dayData['label']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Spalte 2: Sicherheits-Monitor & Live-Log -->
                <div style="background: rgba(0,0,0,0.015); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem;">
                    <h3 style="font-size: 1.1rem; margin:0 0 1rem 0; display:flex; align-items:center; justify-content:space-between; color:var(--text-primary);">
                        <span style="display:flex; align-items:center; gap:8px;">
                            <span class="material-icons" style="color:var(--primary); font-size:20px;">history</span> Sicherheits-Monitor
                        </span>
                        <span style="font-size:0.75rem; color:var(--text-secondary); background:rgba(0,0,0,0.04); padding:2px 8px; border-radius:10px;">Letzte Ereignisse</span>
                    </h3>

                    <?php if (empty($recent_security_logs)): ?>
                        <div style="text-align:center; padding: 2rem 1rem; color:var(--text-secondary); font-size:0.88rem;">
                            <span class="material-icons" style="font-size:32px; opacity:0.4; display:block; margin-bottom:6px;">check_circle</span>
                            Keine verdächtigen Ereignisse erfasst. Das System läuft stabil.
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($recent_security_logs as $log): ?>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:rgba(0,0,0,0.02); border-radius:10px; font-size:0.82rem;">
                                    <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                                        <?php if ($log['level'] === 'WARNING' || $log['level'] === 'DANGER'): ?>
                                            <span class="material-icons" style="color:#ef4444; font-size:16px;">warning</span>
                                        <?php else: ?>
                                            <span class="material-icons" style="color:#10b981; font-size:16px;">check_circle</span>
                                        <?php endif; ?>
                                        <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($log['type']); ?></strong>
                                            <span style="color:var(--text-secondary); margin-left:4px;"><?php echo htmlspecialchars($log['message']); ?></span>
                                        </div>
                                    </div>
                                    <span style="font-size:0.72rem; color:var(--text-secondary); white-space:nowrap;"><?php echo htmlspecialchars($log['time']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- 4. SYSTEM- & SPEICHER-DETAILS (FOOTER-CARD) -->
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; font-size:0.84rem; color:var(--text-secondary);">
                <div>
                    <strong>Speicher-Engine:</strong> 
                    <span style="color:var(--text-primary); font-weight:600;"><?php echo htmlspecialchars($storage_details['name'] ?? 'Flat-File JSON'); ?></span>
                    <span style="margin: 0 6px;">&bull;</span>
                    <strong>Version:</strong> <span style="color:var(--text-primary); font-weight:600;">WeaveCMS v0.9.0</span>
                    <span style="margin: 0 6px;">&bull;</span>
                    <strong>PHP:</strong> <span style="color:var(--text-primary); font-weight:600;"><?php echo PHP_VERSION; ?></span>
                </div>
                <div>
                    <strong>Angemeldet als:</strong> 
                    <span style="color:var(--text-primary); font-weight:700;"><?php echo htmlspecialchars($cur_user['username']); ?></span> 
                    (<?php echo htmlspecialchars($cur_user['role']); ?>)
                </div>
            </div>

        </div>
