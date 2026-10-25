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
            <?php 
                $section_id = 'courses';
                $current_tab = 'tab-courses';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>
<?php
$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$all_languages = LanguageManager::getSupportedLanguages();
?>
            <!-- Add new Course -->
            <div class="card" style="padding: 2.5rem; margin-bottom: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Neuen Kurs / Workshop planen &amp; Einladungslink generieren</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_course">

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs Neuer Kurs -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;">Inhaltssprachen:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="add_course-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="add_course-btn-tab-<?php echo $al; ?>"
                                            onclick="switchCourseLangTab('add_course', '<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' (Hauptsprache)' : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache Neuer Kurs -->
                    <div id="add_course-lang-content-<?php echo $primary_lang; ?>" class="add_course-lang-content">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                            <div class="form-group">
                                <label>Kurstitel * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                                <input type="text" name="title" class="form-control" placeholder="z. B. Grundkurs Weidenflechten" required>
                            </div>
                            <div class="form-group">
                                <label>Datum *</label>
                                <input type="text" name="date_string" class="form-control" placeholder="z. B. Samstag, 15. Oktober 2026" required>
                            </div>
                            <div class="form-group">
                                <label>Uhrzeit *</label>
                                <input type="text" name="time" class="form-control" placeholder="z. B. 10:00 - 16:00 Uhr" value="10:00 - 16:00 Uhr" required>
                            </div>
                            <div class="form-group">
                                <label>Veranstaltungsort *</label>
                                <input type="text" name="location" class="form-control" placeholder="z. B. Kraaker Straße 14, 19077 Rastow" value="Werkstatt Kraaker Straße 14, 19077 Rastow" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label>Kursbeschreibung &amp; Inhalte <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Was lernen die Teilnehmer? Welche Materialien und Pausengetränke sind inklusive?"></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="add_course-lang-content-<?php echo $al; ?>" class="add_course-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ Übersetze Titel, Datum, Uhrzeit und Beschreibung für Besucher mit Sprache <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>. Falls leer, wird die Hauptsprache verwendet.
                            </div>
                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Kurstitel (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                    <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="z. B. Willow Weaving Basics">
                                </div>
                                <div class="form-group">
                                    <label>Datum (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                    <input type="text" name="trans_<?php echo $al; ?>_date_string" class="form-control" placeholder="z. B. Saturday, October 15, 2026">
                                </div>
                                <div class="form-group">
                                    <label>Uhrzeit (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                    <input type="text" name="trans_<?php echo $al; ?>_time" class="form-control" placeholder="z. B. 10:00 AM - 4:00 PM">
                                </div>
                                <div class="form-group">
                                    <label>Veranstaltungsort (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                    <input type="text" name="trans_<?php echo $al; ?>_location" class="form-control" placeholder="z. B. Workshop Studio Rastow">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 1rem;">
                                <label>Kursbeschreibung (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                <textarea name="trans_<?php echo $al; ?>_description" class="form-control" rows="3" placeholder="Description in <?php echo htmlspecialchars($lang_info['name']); ?>..."></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; margin-bottom: 1.5rem; background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1rem;">
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
                                $cid = (int)$c['id'];
                                $course_link = $site_url . '?course=' . $c['id'];
                                $wa_text = urlencode("Hallo! Schau dir mal den Kurs \"" . $c['title'] . "\" bei Maxima an: " . $c['date_string'] . " (" . ($c['time'] ?? '') . ") in " . ($c['location'] ?? 'Musterstadt') . " für " . $c['price'] . ". Hier buchen: " . $course_link);
                            ?>
                            <div style="background:rgba(255,255,255,0.01); border:1px solid var(--border-color); padding:1.5rem; border-radius:16px;">
                                <form method="POST" action="" style="margin-bottom:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="edit_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">

                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:8px;">
                                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <strong style="color:var(--text-primary); font-size:1.1rem;"><?php echo htmlspecialchars($c['title']); ?></strong>
                                            <?php if (count($active_langs) > 1): ?>
                                                <?php foreach ($active_langs as $al): ?>
                                                    <?php if ($al === $primary_lang) continue; ?>
                                                    <?php if (!empty($c['translations'][$al]['title']) || !empty($c['translations'][$al]['description'])): 
                                                        $li = $all_languages[$al] ?? ['flag' => '🌐'];
                                                    ?>
                                                        <span title="Übersetzung für <?php echo strtoupper($al); ?> gepflegt" style="font-size:0.75rem; background:rgba(16,185,129,0.12); color:var(--primary); border:1px solid rgba(16,185,129,0.25); padding:1px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:2px;">
                                                            <?php echo $li['flag']; ?> <?php echo strtoupper($al); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (count($active_langs) > 1): ?>
                                        <!-- Sprach-Tabs für existierenden Kurs -->
                                        <div style="margin-bottom: 1rem;">
                                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;">
                                                <?php foreach ($active_langs as $idx => $al): 
                                                    $is_prim = ($al === $primary_lang);
                                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                                ?>
                                                    <button type="button" 
                                                            class="edit_course_<?php echo $cid; ?>-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                                            id="edit_course_<?php echo $cid; ?>-btn-tab-<?php echo $al; ?>"
                                                            onclick="switchCourseLangTab('edit_course_<?php echo $cid; ?>', '<?php echo $al; ?>')"
                                                            style="padding: 4px 10px; font-size: 0.8rem; display:flex; align-items:center; gap:5px;">
                                                        <span><?php echo $lang_info['flag']; ?></span>
                                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' (Hauptsprache)' : ''; ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Tab-Inhalt: Hauptsprache existierender Kurs -->
                                    <div id="edit_course_<?php echo $cid; ?>-lang-content-<?php echo $primary_lang; ?>" class="edit_course_<?php echo $cid; ?>-lang-content">
                                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                            <div class="form-group">
                                                <label>Kurstitel * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Datum *</label>
                                                <input type="text" name="date_string" class="form-control" value="<?php echo htmlspecialchars($c['date_string']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Uhrzeit *</label>
                                                <input type="text" name="time" class="form-control" value="<?php echo htmlspecialchars($c['time'] ?? '10:00 - 16:00 Uhr'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Veranstaltungsort *</label>
                                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($c['location'] ?? 'Werkstatt Kraaker Straße 14, 19077 Rastow'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Kursbeschreibung <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($c['description']); ?></textarea>
                                        </div>
                                    </div>

                                    <?php foreach ($active_langs as $al): ?>
                                        <?php if ($al === $primary_lang) continue; 
                                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                            $tr_c = $c['translations'][$al] ?? [];
                                        ?>
                                        <div id="edit_course_<?php echo $cid; ?>-lang-content-<?php echo $al; ?>" class="edit_course_<?php echo $cid; ?>-lang-content" style="display:none;">
                                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                                <div class="form-group">
                                                    <label>Kurstitel (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" value="<?php echo htmlspecialchars($tr_c['title'] ?? ''); ?>" placeholder="z. B. Willow Weaving Course">
                                                </div>
                                                <div class="form-group">
                                                    <label>Datum (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_date_string" class="form-control" value="<?php echo htmlspecialchars($tr_c['date_string'] ?? ''); ?>" placeholder="z. B. Saturday, October 15, 2026">
                                                </div>
                                                <div class="form-group">
                                                    <label>Uhrzeit (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_time" class="form-control" value="<?php echo htmlspecialchars($tr_c['time'] ?? ''); ?>" placeholder="z. B. 10:00 AM - 4:00 PM">
                                                </div>
                                                <div class="form-group">
                                                    <label>Veranstaltungsort (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_location" class="form-control" value="<?php echo htmlspecialchars($tr_c['location'] ?? ''); ?>" placeholder="z. B. Workshop Studio Rastow">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Kursbeschreibung (<?php echo htmlspecialchars($lang_info['name']); ?>)</label>
                                                <textarea name="trans_<?php echo $al; ?>_description" class="form-control" rows="2" placeholder="Course description in <?php echo htmlspecialchars($lang_info['name']); ?>..."><?php echo htmlspecialchars($tr_c['description'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1.25rem; margin-top:1rem; margin-bottom:1rem;">
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

        <script>
        function switchCourseLangTab(context, lang) {
            document.querySelectorAll('.' + context + '-lang-content').forEach(el => el.style.display = 'none');
            const target = document.getElementById(context + '-lang-content-' + lang);
            if (target) target.style.display = 'block';

            document.querySelectorAll('.' + context + '-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            const activeBtn = document.getElementById(context + '-btn-tab-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-secondary');
                activeBtn.classList.add('btn-primary');
            }
        }
        </script>
