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
                    <a href="?tab=tab-dashboard&check_updates=1" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; display:inline-flex; align-items:center; gap:4px;" title="Updates jetzt online prüfen">
                        <span class="material-icons" style="font-size:14px;">sync</span> Updates prüfen
                    </a>
                </div>
            </div>

            <!-- Phase 12: Prominente Update-Meldungen (Core & Plugins) -->
            <?php if (!empty($core_update_info['update_available'])): ?>
                <div style="background: linear-gradient(135deg, rgba(59,130,246,0.12) 0%, rgba(37,99,235,0.06) 100%); border: 1px solid #3b82f6; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-icons" style="font-size: 26px;">system_update</span>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 0.25rem 0; font-size: 1.05rem; color: #1e40af;">
                                Neues WeaveCMS-Update verfügbar: <strong>v<?php echo htmlspecialchars($core_update_info['latest_version']); ?></strong>
                            </h4>
                            <p style="margin: 0; font-size: 0.88rem; color: var(--text-secondary);">
                                Installiert: <code>v<?php echo htmlspecialchars($core_update_info['current_version']); ?></code> &bull; 
                                <?php echo htmlspecialchars($core_update_info['changelog'] ?? 'Release-Notizen online ansehen'); ?>
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?php echo htmlspecialchars($core_update_info['release_url']); ?>" target="_blank" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-icons" style="font-size: 16px;">open_in_new</span> Release &amp; Download ansehen
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($plugin_updates)): ?>
                <div style="background: rgba(245,158,11,0.08); border: 1px solid #f59e0b; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #b45309; display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons" style="font-size: 20px;">extension</span> Plugin-Updates auf GitHub verfügbar
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($plugin_updates as $pUp): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 10px;">
                                <div>
                                    <strong><?php echo htmlspecialchars($pUp['name']); ?></strong>:
                                    <span style="font-size: 0.85rem; color: var(--text-secondary); margin-left: 6px;">
                                        Installiert: <code>v<?php echo htmlspecialchars($pUp['installed_version']); ?></code> ➔ Neu auf Git: <strong style="color: #b45309;">v<?php echo htmlspecialchars($pUp['latest_version']); ?></strong>
                                    </span>
                                </div>
                                <a href="<?php echo htmlspecialchars($pUp['release_url']); ?>" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-icons" style="font-size: 14px;">open_in_new</span> Details / Git-Repo
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                <?php $dash_audit = SecurityManager::auditPermissions(); ?>
                <div style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2); border-radius: 16px; padding: 1.25rem; cursor:pointer;" onclick="switchTab('tab-security')">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Sicherheit</span>
                            <div style="font-size:1.35rem; font-weight:800; color:<?php echo ($dash_audit['overall_status'] === 'ok') ? '#10b981' : '#d97706'; ?>; margin:0.45rem 0 0.25rem 0; line-height:1.2; display:flex; align-items:center; gap:6px;">
                                <span class="material-icons" style="font-size:20px;"><?php echo ($dash_audit['overall_status'] === 'ok') ? 'shield' : 'warning'; ?></span> 
                                <?php echo ($dash_audit['overall_status'] === 'ok') ? 'Aktiv &amp; Geschützt' : 'Audit prüfen'; ?>
                            </div>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">
                                Header, CSP, HSTS &amp; Anti-Spoofing
                            </span>
                        </div>
                        <span class="material-icons" style="font-size:32px; color:<?php echo ($dash_audit['overall_status'] === 'ok') ? '#10b981' : '#d97706'; ?>; opacity:0.7;">lock</span>
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
                    <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-blog')">
                        <span class="material-icons" style="font-size:17px; color:var(--primary);">article</span> Neuer Blogartikel
                    </button>
                    <button type="button" class="btn btn-outline" style="padding:7px 14px; font-size:0.86rem; display:inline-flex; align-items:center; gap:6px;" onclick="switchTab('tab-gallery')">
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

            <!-- 2b. PROJEKT-STARTER: PROFIL- & BRANCHENAUSWAHL (PHASE 21) -->
            <?php 
                $project_presets = SectionManager::getPresets();
                $active_preset_key = $data['settings']['project_preset'] ?? 'all';
                $is_admin = ($cur_user['role'] ?? '') === UserManager::ROLE_ADMIN;
            ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; margin-bottom: 2.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 1.25rem;">
                    <div>
                        <h3 style="margin:0 0 0.35rem 0; font-size:1.15rem; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                            <span class="material-icons" style="font-size:22px; color:var(--primary);">auto_awesome</span> Website-Typ &amp; Schnellstart-Profile
                        </h3>
                        <p style="margin:0; font-size:0.88rem; color:var(--text-secondary);">
                            Passe mit 1 Klick die Menüführung und sichtbaren Module an dein Projekt an. Vorhandene Inhalte und Texte bleiben im Hintergrund sicher erhalten.
                        </p>
                    </div>
                    <div>
                        <span style="font-size:0.8rem; background:rgba(46,98,72,0.12); color:var(--primary); border:1px solid rgba(46,98,72,0.25); border-radius:20px; padding:4px 12px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:15px;">check_circle</span> Aktives Profil: <?php echo htmlspecialchars($project_presets[$active_preset_key]['name'] ?? 'Benutzerdefiniert'); ?>
                        </span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                    <?php foreach ($project_presets as $pKey => $pDef): 
                        $isActive = ($pKey === $active_preset_key);
                    ?>
                        <div style="border: 2px solid <?php echo $isActive ? 'var(--primary)' : 'var(--border-color)'; ?>; background: <?php echo $isActive ? 'rgba(46,98,72,0.04)' : 'var(--card-bg, #fff)'; ?>; border-radius: 14px; padding: 1.25rem; display:flex; flex-direction:column; justify-content:space-between; transition: all 0.2s ease; box-shadow: <?php echo $isActive ? '0 4px 12px rgba(46,98,72,0.1)' : 'none'; ?>;">
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:38px; height:38px; border-radius:10px; background:<?php echo $isActive ? 'var(--primary)' : 'rgba(0,0,0,0.06)'; ?>; color:<?php echo $isActive ? '#fff' : 'var(--text-primary)'; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                            <span class="material-icons" style="font-size:20px;"><?php echo htmlspecialchars($pDef['icon']); ?></span>
                                        </div>
                                        <strong style="font-size:1rem; color:var(--text-primary);"><?php echo htmlspecialchars($pDef['name']); ?></strong>
                                    </div>
                                    <?php if ($isActive): ?>
                                        <span style="background:var(--primary); color:#fff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:8px;">Aktiv</span>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size:0.83rem; color:var(--text-secondary); line-height:1.45; margin:0 0 1rem 0;">
                                    <?php echo htmlspecialchars($pDef['description']); ?>
                                </p>
                            </div>

                            <div>
                                <div style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.75rem; display:flex; flex-wrap:wrap; gap:4px;">
                                    <span style="font-weight:600; width:100%; margin-bottom:2px;">Module:</span>
                                    <?php foreach ($pDef['active_sections'] as $secKey): ?>
                                        <span style="background:rgba(0,0,0,0.05); padding:2px 6px; border-radius:6px; font-size:0.72rem;"><?php echo htmlspecialchars($secKey); ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($is_admin): ?>
                                    <?php if ($isActive): ?>
                                        <button type="button" class="btn" style="width:100%; padding:7px 12px; font-size:0.82rem; background:rgba(46,98,72,0.15); color:var(--primary); border:1px solid rgba(46,98,72,0.3); cursor:default; display:flex; align-items:center; justify-content:center; gap:6px;" disabled>
                                            <span class="material-icons" style="font-size:16px;">task_alt</span> Aktives Profil
                                        </button>
                                    <?php else: ?>
                                        <form method="post" action="?tab=tab-dashboard" style="margin:0;">
                                            <input type="hidden" name="action" value="apply_project_preset">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="project_preset" value="<?php echo htmlspecialchars($pKey); ?>">
                                            <input type="hidden" name="target_tab" value="tab-dashboard">
                                            <button type="submit" class="btn btn-outline" style="width:100%; padding:7px 12px; font-size:0.82rem; display:flex; align-items:center; justify-content:center; gap:6px;">
                                                <span class="material-icons" style="font-size:16px; color:var(--primary);">play_arrow</span> Profil aktivieren
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                    <strong>Version:</strong> <span style="color:var(--text-primary); font-weight:600;">WeaveCMS v<?php echo defined('WEAVE_VERSION') ? WEAVE_VERSION : '1.0.0'; ?></span>
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
