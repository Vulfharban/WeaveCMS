<?php
/**
 * WeaveCMS – Admin Tab: Kurse & Workshops
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 * @var string $site_url Basis-URL für Einladungslinks
 */
?>
        <!-- ──────────────── SECTION 3: WORKSHOPS/COURSES ──────────────── -->
        <div id="tab-courses" class="admin-section">
            <!-- Add new Course -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neuen Kurs / Workshop planen &amp; Einladungslink generieren</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_course">
                    
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label>Kurstitel</label>
                            <input type="text" name="title" class="form-control" placeholder="z. B. Grundkurs Weidenflechten" required>
                        </div>
                        <div class="form-group">
                            <label>Datum</label>
                            <input type="text" name="date_string" class="form-control" placeholder="z. B. Samstag, 15. Oktober 2026" required>
                        </div>
                        <div class="form-group">
                            <label>Uhrzeit</label>
                            <input type="text" name="time" class="form-control" placeholder="z. B. 10:00 - 16:00 Uhr" value="10:00 - 16:00 Uhr" required>
                        </div>
                        <div class="form-group">
                            <label>Veranstaltungsort</label>
                            <input type="text" name="location" class="form-control" placeholder="z. B. Kraaker Straße 14, 19077 Rastow" value="Werkstatt Kraaker Straße 14, 19077 Rastow" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Kursbeschreibung &amp; Inhalte</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Was lernen die Teilnehmer? Welche Materialien und Pausengetränke sind inklusive?"></textarea>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Preis (z. B. 85 €)</label>
                            <input type="text" name="price" class="form-control" placeholder="85 €" value="85 €">
                        </div>
                        <div class="form-group">
                            <label>Max. Teilnehmer</label>
                            <input type="number" name="max_participants" class="form-control" value="8" min="1">
                        </div>
                        <div class="form-group">
                            <label>Bereits gebucht</label>
                            <input type="number" name="current_participants" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="available" checked style="width:18px; height:18px;">
                                Kurs ist buchbar (aktiv)
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">event_note</span> Kurs anlegen &amp; Einladungslink erstellen
                    </button>
                </form>
            </div>

            <!-- List, Edit & Delete Courses -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Geplante Kurse &amp; Workshops verwalten &amp; teilen</h2>
                <?php if (empty($data['courses'])): ?>
                    <p style="color: var(--text-secondary);">Aktuell sind keine Kurse eingetragen.</p>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:2rem;">
                        <?php foreach ($data['courses'] as $c): ?>
                            <?php 
                                $course_link = $site_url . '?course=' . $c['id'];
                                $wa_text = urlencode("Hallo! Schau dir mal den Kurs \"" . $c['title'] . "\" bei Maxima an: " . $c['date_string'] . " (" . ($c['time'] ?? '') . ") in " . ($c['location'] ?? 'Musterstadt') . " für " . $c['price'] . ". Hier buchen: " . $course_link);
                            ?>
                            <div style="background:rgba(255,255,255,0.01); border:1px solid var(--border-color); padding:1.5rem; border-radius:16px;">
                                <form method="POST" action="" style="margin-bottom:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="edit_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    
                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                        <div class="form-group">
                                            <label>Kurstitel</label>
                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Datum</label>
                                            <input type="text" name="date_string" class="form-control" value="<?php echo htmlspecialchars($c['date_string']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Uhrzeit</label>
                                            <input type="text" name="time" class="form-control" value="<?php echo htmlspecialchars($c['time'] ?? '10:00 - 16:00 Uhr'); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Veranstaltungsort</label>
                                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($c['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow'); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Kursbeschreibung</label>
                                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($c['description']); ?></textarea>
                                    </div>

                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                        <div class="form-group">
                                            <label>Preis</label>
                                            <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($c['price']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Max. Teilnehmer</label>
                                            <input type="number" name="max_participants" class="form-control" value="<?php echo (int)$c['max_participants']; ?>" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label>Bereits gebucht</label>
                                            <input type="number" name="current_participants" class="form-control" value="<?php echo (int)$c['current_participants']; ?>" min="0">
                                        </div>
                                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                                <input type="checkbox" name="available" <?php echo $c['available'] ? 'checked' : ''; ?> style="width:16px; height:16px;">
                                                Buchbar (Aktiv)
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Einladungslink Box -->
                                    <div class="invite-box">
                                        <div style="display:flex; align-items:center; gap:6px; font-weight:600; font-size:0.85rem; color:var(--primary);">
                                            <span class="material-icons" style="font-size:18px;">link</span> Einladungslink:
                                        </div>
                                        <input type="text" readonly class="invite-link-input" id="link-<?php echo $c['id']; ?>" value="<?php echo htmlspecialchars($course_link); ?>">
                                        <div style="display:flex; gap:6px;">
                                            <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem;" onclick="copyLink('<?php echo htmlspecialchars($course_link); ?>')">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">content_copy</span> Kopieren
                                            </button>
                                            <a href="https://api.whatsapp.com/send?text=<?php echo $wa_text; ?>" target="_blank" class="btn btn-primary" style="padding:4px 10px; font-size:0.8rem; background:#25D366; border-color:#25D366; color:#fff;" title="Per WhatsApp teilen">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">share</span> WhatsApp
                                            </a>
                                        </div>
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:1.5rem; border-top:1px solid var(--border-color); padding-top:1rem;">
                                        <span style="font-size:0.85rem; color:var(--text-secondary);">
                                             Verfügbare Plätze: <strong><?php echo max(0, $c['max_participants'] - $c['current_participants']); ?></strong> von <?php echo $c['max_participants']; ?>
                                        </span>
                                        <div style="display:flex; gap:10px;">
                                            <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.85rem;">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">save</span> Speichern
                                            </button>
                                            <button type="button" class="btn btn-danger" style="padding:6px 12px; font-size:0.85rem;" onclick="deleteCourse(<?php echo $c['id']; ?>)">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">delete</span> Kurs Löschen
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Hidden Delete form for course -->
                                <form id="delete-form-<?php echo $c['id']; ?>" method="POST" action="" style="display:none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
