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
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;"><?php echo __a('admin.courses.add_heading', 'Neuen Flechtkurs anlegen'); ?></h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add_course">

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs Neuer Kurs -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;"><?php echo __a('admin.courses.lang_content', 'Inhalte auf'); ?>:</label>
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
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' ' . __a('admin.courses.primary_lang_suffix', '(Hauptsprache)') : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache Neuer Kurs -->
                    <div id="add_course-lang-content-<?php echo $primary_lang; ?>" class="add_course-lang-content">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                            <div class="form-group">
                                <label><?php echo __a('admin.courses.field_title', 'Kurstitel:'); ?> * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                                <input type="text" name="title" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_title', 'z. B. Weidenflechten für Einsteiger')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo __a('admin.courses.field_date', 'Datum:'); ?> *</label>
                                <input type="text" name="date_string" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_date', 'z. B. Samstag, 15. November 2026')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo __a('admin.courses.field_time', 'Uhrzeit:'); ?> *</label>
                                <input type="text" name="time" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_time', 'z. B. 10:00 – 16:00 Uhr')); ?>" value="10:00 – 16:00 Uhr" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo __a('admin.courses.field_location', 'Ort / Treffpunkt:'); ?> *</label>
                                <input type="text" name="location" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_location', 'z. B. Flechtwerkstatt')); ?>" value="Atelier-Werkstatt, Musterstraße 12, 12345 Musterstadt" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label><?php echo __a('admin.courses.field_desc', 'Beschreibung:'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_desc', 'Kurze Übersicht was geflochten wird, Material etc.')); ?>"></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="add_course-lang-content-<?php echo $al; ?>" class="add_course-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ <?php echo __a('admin.courses.trans_info', 'Übersetzungen für aktivierte Sprachen:'); ?> <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>.
                            </div>
                            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                                <div class="form-group">
                                    <label><?php echo __a('admin.courses.field_title_trans', 'Kurstitel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                    <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_title_trans', 'Kurstitel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php echo __a('admin.courses.field_date_trans', 'Datum ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                    <input type="text" name="trans_<?php echo $al; ?>_date_string" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_date_trans', 'Datum ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php echo __a('admin.courses.field_time_trans', 'Uhrzeit ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                    <input type="text" name="trans_<?php echo $al; ?>_time" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_time_trans', 'Uhrzeit ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php echo __a('admin.courses.field_location_trans', 'Ort ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                    <input type="text" name="trans_<?php echo $al; ?>_location" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_location_trans', 'Ort ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 1rem;">
                                <label><?php echo __a('admin.courses.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                <textarea name="trans_<?php echo $al; ?>_description" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; margin-bottom: 1.5rem; background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1rem;">
                        <div class="form-group">
                            <label><?php echo __a('admin.courses.field_price', 'Preis (z. B. 85 €):'); ?></label>
                            <input type="text" name="price" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.courses.placeholder_price', 'z. B. 85 € inkl. Material')); ?>" value="85 €">
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.courses.field_max', 'Max. Plätze:'); ?></label>
                            <input type="number" name="max_participants" class="form-control" value="8" min="1">
                        </div>
                        <div class="form-group">
                            <label><?php echo __a('admin.courses.field_booked', 'Bereits gebucht:'); ?></label>
                            <input type="number" name="current_participants" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="available" checked style="width:18px; height:18px;">
                                <?php echo __a('admin.courses.active_checkbox', 'Kurs öffentlich auf der Website anzeigen'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:1.25rem; padding:0.9rem 1rem; border:1px dashed var(--border-color); border-radius:12px; background:rgba(255,255,255,0.015);">
                        <label style="display:block; margin-bottom:0.5rem;">Optionales Kursbild (aus Mediathek)</label>
                        <input type="hidden" name="course_image" id="add-course-image-input" value="">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <img id="add-course-image-preview" src="" alt="" style="display:none; width:64px; height:50px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); flex-shrink:0;">
                            <span id="add-course-image-label" style="font-size:0.85rem; color:var(--text-secondary);">Kein Bild gewählt</span>
                            <button type="button" class="btn btn-outline" style="padding:5px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;" onclick="openCourseMediaPicker('add-course-image')">
                                <span class="material-icons" style="font-size:16px;">perm_media</span> Aus Mediathek wählen
                            </button>
                            <button type="button" id="add-course-image-clear" class="btn btn-outline" style="display:none; padding:5px 10px; font-size:0.82rem; color:#ef4444; border-color:rgba(239,68,68,0.35);" onclick="clearCourseMedia('add-course-image')">
                                Entfernen
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons" style="font-size:18px; vertical-align:middle;">event_note</span> <?php echo __a('admin.courses.btn_create', 'Kurs anlegen'); ?>
                    </button>
                </form>
            </div>

            <!-- List, Edit & Delete Courses -->
            <div class="card" style="padding: 2.5rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;"><?php echo __a('admin.courses.manage_heading', 'Bestehende Flechtkurse verwalten'); ?></h2>
                <?php if (empty($data['courses'])): ?>
                    <p style="color: var(--text-secondary);"><?php echo __a('admin.courses.empty', 'Noch keine Kurse eingetragen.'); ?></p>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:1.75rem;">
                        <?php foreach ($data['courses'] as $idx => $c): ?>
                            <?php 
                                $cid = (int)$c['id'];
                                $course_link = $site_url . '?course=' . $c['id'];
                                $wa_raw = __a('admin.courses.whatsapp_share', 'Hallo! Schau dir diesen Flechtkurs an: {title} am {date}: {url}', null, ['title' => $c['title'], 'date' => $c['date_string'], 'url' => $course_link]);
                                $wa_text = urlencode($wa_raw);
                                $spots_left = max(0, (int)$c['max_participants'] - (int)$c['current_participants']);
                                $is_active = !empty($c['available']);
                                $is_bookable = $is_active && ($spots_left > 0);
                                $accent_color = $is_bookable ? '#10b981' : ($is_active ? '#f59e0b' : '#64748b');
                                $status_label = $is_bookable ? 'Buchbar' : ($is_active ? 'Ausgebucht' : 'Inaktiv');
                                $status_bg = $is_bookable ? 'rgba(16,185,129,0.14)' : ($is_active ? 'rgba(245,158,11,0.14)' : 'rgba(100,116,139,0.18)');
                                $card_bg = ($idx % 2 === 0) ? 'rgba(255,255,255,0.025)' : 'rgba(217,119,6,0.035)';
                                $c_img = trim($c['image'] ?? '');
                                $c_img_preview = $c_img !== '' ? ((strpos($c_img, 'http://') === 0 || strpos($c_img, 'https://') === 0) ? $c_img : '../' . ltrim($c_img, './')) : '';
                                $picker_prefix = 'edit-course-image-' . $cid;
                            ?>
                            <div style="background:<?php echo $card_bg; ?>; border:1px solid rgba(255,255,255,0.14); border-left:5px solid <?php echo $accent_color; ?>; padding:1.5rem; border-radius:16px; box-shadow:0 6px 20px rgba(0,0,0,0.12);">
                                <form method="POST" action="" style="margin-bottom:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="edit_course">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">

                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; padding-bottom:0.9rem; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:10px;">
                                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                            <span style="background:var(--primary); color:#fff; font-weight:800; font-size:0.8rem; padding:3px 10px; border-radius:999px;">#<?php echo $idx + 1; ?></span>
                                            <strong style="color:var(--text-primary); font-size:1.1rem;"><?php echo htmlspecialchars($c['title']); ?></strong>
                                            <span style="font-size:0.84rem; color:var(--text-secondary);">· <?php echo htmlspecialchars($c['date_string']); ?></span>
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
                                        <span style="background:<?php echo $status_bg; ?>; color:<?php echo $accent_color; ?>; font-weight:700; font-size:0.78rem; padding:4px 10px; border-radius:999px; border:1px solid <?php echo $accent_color; ?>44;">
                                            <?php echo $status_label; ?> (<?php echo $spots_left; ?>/<?php echo (int)$c['max_participants']; ?> frei)
                                        </span>
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
                                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' ' . __a('admin.courses.primary_lang_suffix', '(Hauptsprache)') : ''; ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Tab-Inhalt: Hauptsprache existierender Kurs -->
                                    <div id="edit_course_<?php echo $cid; ?>-lang-content-<?php echo $primary_lang; ?>" class="edit_course_<?php echo $cid; ?>-lang-content">
                                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom:1rem;">
                                            <div class="form-group">
                                                <label><?php echo __a('admin.courses.field_title', 'Kurstitel:'); ?> * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
                                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?php echo __a('admin.courses.field_date', 'Datum:'); ?> *</label>
                                                <input type="text" name="date_string" class="form-control" value="<?php echo htmlspecialchars($c['date_string']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?php echo __a('admin.courses.field_time', 'Uhrzeit:'); ?> *</label>
                                                <input type="text" name="time" class="form-control" value="<?php echo htmlspecialchars($c['time'] ?? '10:00 – 16:00 Uhr'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?php echo __a('admin.courses.field_location', 'Ort / Treffpunkt:'); ?> *</label>
                                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($c['location'] ?? 'Atelier-Werkstatt, Musterstraße 12, 12345 Musterstadt'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label><?php echo __a('admin.courses.field_desc', 'Beschreibung:'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span></label>
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
                                                    <label><?php echo __a('admin.courses.field_title_trans', 'Kurstitel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_title" class="form-control" value="<?php echo htmlspecialchars($tr_c['title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_title_trans', 'Kurstitel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label><?php echo __a('admin.courses.field_date_trans', 'Datum ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_date_string" class="form-control" value="<?php echo htmlspecialchars($tr_c['date_string'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_date_trans', 'Datum ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label><?php echo __a('admin.courses.field_time_trans', 'Uhrzeit ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_time" class="form-control" value="<?php echo htmlspecialchars($tr_c['time'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_time_trans', 'Uhrzeit ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label><?php echo __a('admin.courses.field_location_trans', 'Ort ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                                    <input type="text" name="trans_<?php echo $al; ?>_location" class="form-control" value="<?php echo htmlspecialchars($tr_c['location'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_location_trans', 'Ort ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label><?php echo __a('admin.courses.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?></label>
                                                <textarea name="trans_<?php echo $al; ?>_description" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(__a('admin.courses.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"><?php echo htmlspecialchars($tr_c['description'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1.25rem; margin-top:1rem; margin-bottom:1rem;">
                                        <div class="form-group">
                                            <label><?php echo __a('admin.courses.field_price', 'Preis (z. B. 85 €):'); ?></label>
                                            <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($c['price']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label><?php echo __a('admin.courses.field_max', 'Max. Plätze:'); ?></label>
                                            <input type="number" name="max_participants" class="form-control" value="<?php echo (int)$c['max_participants']; ?>" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label><?php echo __a('admin.courses.field_booked', 'Bereits gebucht:'); ?></label>
                                            <input type="number" name="current_participants" class="form-control" value="<?php echo (int)$c['current_participants']; ?>" min="0">
                                        </div>
                                        <div class="form-group" style="display:flex; align-items:center; height:100%; padding-top:1.5rem;">
                                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                                <input type="checkbox" name="available" <?php echo $c['available'] ? 'checked' : ''; ?> style="width:16px; height:16px;">
                                                <?php echo __a('admin.courses.active_checkbox', 'Kurs öffentlich auf der Website anzeigen'); ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom:1rem; padding:0.85rem 1rem; border:1px dashed var(--border-color); border-radius:12px; background:rgba(255,255,255,0.015);">
                                        <label style="display:block; margin-bottom:0.45rem;">Optionales Kursbild (aus Mediathek)</label>
                                        <input type="hidden" name="course_image" id="<?php echo $picker_prefix; ?>-input" value="<?php echo htmlspecialchars($c_img); ?>">
                                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                            <img id="<?php echo $picker_prefix; ?>-preview" src="<?php echo htmlspecialchars($c_img_preview); ?>" alt="" style="<?php echo $c_img !== '' ? 'display:block;' : 'display:none;'; ?> width:64px; height:50px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); flex-shrink:0;">
                                            <span id="<?php echo $picker_prefix; ?>-label" style="font-size:0.84rem; color:var(--text-secondary);"><?php echo $c_img !== '' ? htmlspecialchars(basename($c_img)) : 'Kein Bild gewählt'; ?></span>
                                            <button type="button" class="btn btn-outline" style="padding:5px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;" onclick="openCourseMediaPicker('<?php echo $picker_prefix; ?>')">
                                                <span class="material-icons" style="font-size:16px;">perm_media</span> Aus Mediathek wählen
                                            </button>
                                            <button type="button" id="<?php echo $picker_prefix; ?>-clear" class="btn btn-outline" style="<?php echo $c_img !== '' ? 'display:inline-flex;' : 'display:none;'; ?> padding:5px 10px; font-size:0.82rem; color:#ef4444; border-color:rgba(239,68,68,0.35);" onclick="clearCourseMedia('<?php echo $picker_prefix; ?>')">
                                                Entfernen
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Einladungslink Box -->
                                    <div class="invite-box">
                                        <div style="display:flex; align-items:center; gap:6px; font-weight:600; font-size:0.85rem; color:var(--primary);">
                                            <span class="material-icons" style="font-size:18px;">link</span> <?php echo __a('admin.courses.invite_label', 'Einladungslink teilen:'); ?>
                                        </div>
                                        <input type="text" readonly class="invite-link-input" id="link-<?php echo $c['id']; ?>" value="<?php echo htmlspecialchars($course_link); ?>">
                                        <div style="display:flex; gap:6px;">
                                            <button type="button" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem;" onclick="copyLink('<?php echo htmlspecialchars($course_link); ?>')">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">content_copy</span> <?php echo __a('admin.courses.btn_copy', 'Kopieren'); ?>
                                            </button>
                                            <a href="https://api.whatsapp.com/send?text=<?php echo $wa_text; ?>" target="_blank" class="btn btn-primary" style="padding:4px 10px; font-size:0.8rem; background:#25D366; border-color:#25D366; color:#fff;" title="<?php echo htmlspecialchars(__a('admin.courses.btn_whatsapp', 'WhatsApp')); ?>">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">share</span> <?php echo __a('admin.courses.btn_whatsapp', 'WhatsApp'); ?>
                                            </a>
                                        </div>
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:1.5rem; border-top:1px solid var(--border-color); padding-top:1rem;">
                                        <span style="font-size:0.85rem; color:var(--text-secondary);">
                                             <?php echo __a('admin.courses.available_seats', '{free} von {max} Plätzen frei', null, ['free' => '<strong>' . max(0, $c['max_participants'] - $c['current_participants']) . '</strong>', 'max' => $c['max_participants']]); ?>
                                        </span>
                                        <div style="display:flex; gap:10px;">
                                            <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.85rem;">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">save</span> <?php echo __a('admin.courses.btn_save', 'Speichern'); ?>
                                            </button>
                                            <button type="button" class="btn btn-danger" style="padding:6px 12px; font-size:0.85rem;" onclick="deleteCourse(<?php echo $c['id']; ?>)">
                                                <span class="material-icons" style="font-size:14px; vertical-align:middle;">delete</span> <?php echo __a('admin.courses.btn_delete', 'Löschen'); ?>
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


        <!-- MODAL: MEDIATHEK-AUSWAHL FÜR KURSBILD -->
        <div id="modal-course-media-picker" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:3400; align-items:center; justify-content:center; backdrop-filter:blur(5px);" onclick="if(event.target===this) closeCourseMediaPicker();">
            <div style="background:var(--bg-card, #1e293b); border:1px solid var(--border-color); border-radius:16px; max-width:900px; width:calc(100% - 2rem); max-height:86vh; display:flex; flex-direction:column; overflow:hidden;">
                <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; color:var(--primary); display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">perm_media</span> Kursbild aus Mediathek auswählen
                    </h3>
                    <button type="button" class="btn btn-outline" onclick="closeCourseMediaPicker()" style="padding:4px 10px;">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>
                <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <input type="text" id="course-picker-search-input" class="form-control" placeholder="Bilder in Mediathek suchen..." style="flex:1; min-width:200px; margin:0;" oninput="renderCourseMediaPicker(this.value)">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('course-picker-upload-input').click()" style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size:18px;">cloud_upload</span> Neu hochladen
                    </button>
                    <input type="file" id="course-picker-upload-input" multiple accept="image/*" style="display:none;" onchange="uploadFromCoursePicker(this.files)">
                </div>
                <div id="course-picker-grid" style="padding:1.25rem 1.5rem; overflow-y:auto; display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px;"></div>
            </div>
        </div>

        <script>
        let COURSE_PICKER_PREFIX = null;
        let COURSE_PICKER_MEDIA_ITEMS = [];
        function escapeCourseHtml(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function openCourseMediaPicker(prefix) {
            COURSE_PICKER_PREFIX = prefix;
            document.getElementById('modal-course-media-picker').style.display = 'flex';
            fetch('index.php?action=get_media&filter=images&sort=date_desc')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        COURSE_PICKER_MEDIA_ITEMS = res.items || [];
                        renderCourseMediaPicker(document.getElementById('course-picker-search-input').value);
                    }
                });
        }
        function closeCourseMediaPicker() {
            document.getElementById('modal-course-media-picker').style.display = 'none';
        }
        function renderCourseMediaPicker(searchVal) {
            const grid = document.getElementById('course-picker-grid');
            const q = (searchVal || '').trim().toLowerCase();
            const imgs = (COURSE_PICKER_MEDIA_ITEMS || []).filter(m => (m.type === 'image' || m.type === 'vector') && (!q || m.filename.toLowerCase().includes(q) || (m.alt && m.alt.toLowerCase().includes(q))));
            grid.innerHTML = '';
            if (!imgs.length) {
                grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--text-secondary);">Keine Bilder in der Mediathek gefunden.</div>';
                return;
            }
            imgs.forEach(m => {
                const card = document.createElement('div');
                card.style.cssText = 'border:1px solid var(--border-color); border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.02);';
                card.onclick = () => {
                    selectCourseMediaItem(m.web_url, m.rel_url, m.filename);
                    closeCourseMediaPicker();
                };
                card.innerHTML = `
                    <div style="height:110px; background:#0f172a; overflow:hidden;">
                        <img src="../${m.rel_url}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div style="padding:6px 8px; font-size:0.75rem; font-weight:600; word-break:break-all; color:var(--text-primary);">${escapeCourseHtml(m.filename)}</div>
                `;
                grid.appendChild(card);
            });
        }
        function selectCourseMediaItem(webUrl, relUrl, filename) {
            if (!COURSE_PICKER_PREFIX) return;
            const inp = document.getElementById(COURSE_PICKER_PREFIX + '-input');
            const prev = document.getElementById(COURSE_PICKER_PREFIX + '-preview');
            const lbl = document.getElementById(COURSE_PICKER_PREFIX + '-label');
            const clr = document.getElementById(COURSE_PICKER_PREFIX + '-clear');
            if (inp) inp.value = webUrl;
            if (prev) { prev.src = '../' + relUrl; prev.style.display = 'block'; }
            if (lbl) lbl.textContent = filename;
            if (clr) clr.style.display = 'inline-flex';
        }
        function clearCourseMedia(prefix) {
            const inp = document.getElementById(prefix + '-input');
            const prev = document.getElementById(prefix + '-preview');
            const lbl = document.getElementById(prefix + '-label');
            const clr = document.getElementById(prefix + '-clear');
            if (inp) inp.value = '';
            if (prev) { prev.src = ''; prev.style.display = 'none'; }
            if (lbl) lbl.textContent = 'Kein Bild gewählt';
            if (clr) clr.style.display = 'none';
        }
        async function uploadFromCoursePicker(files) {
            if (!files || !files.length) return;
            const prepared = (typeof resizeImageBeforeUpload === 'function') ? await resizeImageBeforeUpload(files[0]) : files[0];
            const fd = new FormData();
            fd.append('action', 'upload_media');
            fd.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
            fd.append('files[]', prepared);
            fetch('index.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.uploaded && res.uploaded.length) {
                        const first = res.uploaded[0];
                        selectCourseMediaItem(first.web_url, first.rel_url, first.filename);
                        closeCourseMediaPicker();
                    } else {
                        alert('Upload-Fehler: ' + (res.errors ? res.errors.join('\n') : (res.message || 'Fehler')));
                    }
                })
                .catch(err => alert('Upload fehlgeschlagen: ' + err));
        }
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
