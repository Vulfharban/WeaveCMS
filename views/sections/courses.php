<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Kurse & Workshops
 * ============================================================================
 *
 * @var array $data
 * @var string $site_url
 * @var int $selected_course_id
 */
?>
<!-- Course Dates Section -->
<section id="courses">
    <?php $cur_lang = $cur_lang ?? LanguageManager::detectLanguage($data); ?>
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo htmlspecialchars($data['texts']['courses_title'] ?? 'Kurse & Workshops'); ?></h2>
    <?php if (!empty($data['texts']['courses_subtitle'])): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo htmlspecialchars($data['texts']['courses_subtitle']); ?>
        </p>
    <?php endif; ?>

    <div style="display:flex; flex-direction:column; gap: 1.5rem; margin-bottom: 5rem;">
        <?php if (empty($data['courses'])): ?>
            <p style="text-align: center; color: var(--text-secondary);"><?php echo htmlspecialchars(__('courses.no_dates', 'Aktuell sind keine Kurstermine festgelegt. Schreib mir gern eine Anfrage bei Interesse!')); ?></p>
        <?php else: ?>
            <?php foreach ($data['courses'] as $c): ?>
                <?php 
                    $spots_left = max(0, $c['max_participants'] - $c['current_participants']);
                    $is_bookable = $c['available'] && ($spots_left > 0);
                    $course_link = $site_url . '?course=' . $c['id'];
                    $c_title_raw = (!empty($cur_lang) && !empty($c['translations'][$cur_lang]['title'])) ? $c['translations'][$cur_lang]['title'] : ($c['title'] ?? '');
                    $c_desc_raw = (!empty($cur_lang) && !empty($c['translations'][$cur_lang]['description'])) ? $c['translations'][$cur_lang]['description'] : ($c['description'] ?? '');
                    $c_date_raw = (!empty($cur_lang) && !empty($c['translations'][$cur_lang]['date_string'])) ? $c['translations'][$cur_lang]['date_string'] : ($c['date_string'] ?? '');
                    $time_text = (!empty($cur_lang) && !empty($c['translations'][$cur_lang]['time'])) ? $c['translations'][$cur_lang]['time'] : (!empty($c['time']) ? $c['time'] : '10:00 - 16:00 Uhr');
                    $location_text = (!empty($cur_lang) && !empty($c['translations'][$cur_lang]['location'])) ? $c['translations'][$cur_lang]['location'] : (!empty($c['location']) ? $c['location'] : 'Atelier-Werkstatt, Musterstraße 12, 12345 Musterstadt');
                    $is_highlighted = ($selected_course_id === (int)$c['id']);
                ?>
                <div class="course-row <?php echo $is_highlighted ? 'highlighted' : ''; ?>" id="course-<?php echo $c['id']; ?>">
                    <?php if (!empty($c['image'])):
                        $course_img_raw = $c['image'];
                        $course_img_src = (strpos($course_img_raw, 'http://') === 0 || strpos($course_img_raw, 'https://') === 0)
                            ? $course_img_raw
                            : ((strpos($course_img_raw, '/') === 0) ? '.' . $course_img_raw : './' . ltrim($course_img_raw, './'));
                    ?>
                    <div style="width: 118px; height: 92px; flex-shrink: 0; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); background: rgba(0,0,0,0.06);">
                        <img src="<?php echo htmlspecialchars($course_img_src); ?>" alt="<?php echo htmlspecialchars($c_title_raw); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block; <?php echo htmlspecialchars(MediaManager::getFocusStyle($c['image'])); ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div style="flex:1; min-width: 290px;">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom: 0.75rem; flex-wrap:wrap;">
                            <span style="background: rgba(217, 119, 6, 0.12); color: var(--accent); padding: 4px 14px; border-radius: 20px; font-size: 0.88rem; font-weight: 700; display:inline-flex; align-items:center; gap:5px;">
                                <span class="material-icons" style="font-size:16px;">calendar_today</span>
                                <?php echo htmlspecialchars($c_date_raw); ?>
                            </span>
                            
                            <?php if ($is_bookable): ?>
                                <span style="color: #10b981; font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:4px; background: rgba(16,185,129,0.08); padding: 4px 10px; border-radius: 12px;">
                                    <span class="material-icons" style="font-size:16px;">check_circle</span> <?php echo htmlspecialchars(__('courses.spots_free', 'Plätze frei')); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:4px; background: rgba(239,68,68,0.08); padding: 4px 10px; border-radius: 12px;">
                                    <span class="material-icons" style="font-size:16px;">block</span> <?php echo htmlspecialchars(__('courses.booked', 'Ausgebucht')); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.4rem;"><?php echo htmlspecialchars($c_title_raw); ?></h3>
                        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom:0.75rem; line-height: 1.5;"><?php echo htmlspecialchars($c_desc_raw); ?></p>
                        
                        <!-- Meta Information: Time, Location, Participants -->
                        <div style="display:flex; gap: 0.75rem; flex-wrap:wrap; margin-top: 0.5rem;">
                            <span class="course-meta-badge">
                                <span class="material-icons" style="font-size:15px; color:var(--primary);">schedule</span>
                                <?php echo htmlspecialchars($time_text); ?>
                            </span>
                            <span class="course-meta-badge">
                                <span class="material-icons" style="font-size:15px; color:var(--primary);">place</span>
                                <?php echo htmlspecialchars($location_text); ?>
                            </span>
                            <span class="course-meta-badge">
                                <span class="material-icons" style="font-size:15px; color:var(--primary);">group</span>
                                <strong><?php echo $c['current_participants']; ?> / <?php echo $c['max_participants']; ?></strong> <?php echo str_replace('{spots}', (string)$spots_left, htmlspecialchars(__('courses.booked_spots', 'belegt (noch ' . $spots_left . ' frei)'))); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.75rem; justify-content:center; min-width: 170px;">
                        <span style="font-size: 1.6rem; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($c['price']); ?></span>
                        
                        <div style="display:flex; gap:8px; width:100%; justify-content:flex-end; flex-wrap:wrap;">
                            <?php if ($is_bookable): ?>
                                <button type="button" data-action="request-course" data-title="<?php echo htmlspecialchars($c_title_raw, ENT_QUOTES, 'UTF-8'); ?>" data-date="<?php echo htmlspecialchars($c_date_raw, ENT_QUOTES, 'UTF-8'); ?>" data-time="<?php echo htmlspecialchars($time_text, ENT_QUOTES, 'UTF-8'); ?>" data-location="<?php echo htmlspecialchars($location_text, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem; flex:1; justify-content:center;">
                                    <?php echo htmlspecialchars(__('btn.inquire_now', 'Jetzt anfragen')); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem; opacity: 0.5; cursor: not-allowed; flex:1; justify-content:center;" disabled>
                                    <?php echo htmlspecialchars(__('btn.booked_out', 'Ausgebucht')); ?>
                                </button>
                            <?php endif; ?>
                            
                            <!-- Share Button -->
                            <button type="button" class="btn btn-outline" style="padding: 8px 10px; font-size: 0.85rem;" title="<?php echo htmlspecialchars(__('btn.share', 'Diesen Kurs teilen')); ?>" data-action="share-course" data-title="<?php echo htmlspecialchars($c_title_raw, ENT_QUOTES, 'UTF-8'); ?>" data-date="<?php echo htmlspecialchars($c_date_raw, ENT_QUOTES, 'UTF-8'); ?>" data-link="<?php echo htmlspecialchars($course_link, ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="material-icons" style="font-size:16px;">share</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php
        $cur_safety_default = "1. Eigenverantwortung & Risikoaufklärung\nDie Teilnahme an Kursen und Workshops erfolgt auf eigene Gefahr und Verantwortung. Je nach Kursangebot kann der Umgang mit Werkzeugen, Geräten oder Materialien mit einem erhöhten Verletzungs- oder Verschmutzungsrisiko verbunden sein.\n\n2. Sicherheitsanweisungen\nDie Teilnehmer verpflichten sich, den Sicherheits- und Handhabungshinweisen der Kursleitung Folge zu leisten und bereitgestellte oder eigene Werkzeuge, Geräte und Materialien umsichtig und bestimmungsgemäß zu verwenden. Bei Unsicherheiten ist die Kursleitung vorab zu fragen.\n\n3. Haftungsbeschränkung des Veranstalters\nUnbeschränkte Haftung: Der Veranstalter sowie die Kursleitung haften uneingeschränkt für Schäden aus der Verletzung des Lebens, des Körpers oder der Gesundheit, die auf einer vorsätzlichen oder fahrlässigen Pflichtverletzung beruhen, sowie für sonstige Schäden, die auf einer vorsätzlichen oder grob fahrlässigen Pflichtverletzung beruhen.\nBeschränkte Haftung: Für sonstige Schäden (insbesondere Sach- und Vermögensschäden), die auf einer einfach fahrlässigen Verletzung wesentlicher Vertragspflichten (Kardinalpflichten) beruhen, ist die Haftung auf den vertragstypischen, vorhersehbaren Schaden begrenzt. Im Übrigen ist eine Haftung für einfache Fahrlässigkeit ausgeschlossen.\nPersönliche Gegenstände: Für den Verlust oder die Beschädigung von mitgebrachten Gegenständen, Kleidung oder eigenen Materialien/Werkzeugen der Teilnehmer wird – außer bei Vorsatz oder grober Fahrlässigkeit des Veranstalters – keine Haftung übernommen. Das Tragen geeigneter Kleidung wird empfohlen.\n\n4. Gesundheitliche Voraussetzungen & Minderjährige\nDie Teilnehmer versichern, dass keine gesundheitlichen Bedenken gegen die Teilnahme bestehen. Bei minderjährigen Teilnehmern ist die schriftliche Einverständniserklärung eines Erziehungsberechtigten erforderlich.";
        $cur_safety_notice = array_key_exists('course_safety_notice', $data['texts'] ?? [])
            ? trim((string)$data['texts']['course_safety_notice'])
            : $cur_safety_default;
    ?>
    <?php if ($cur_safety_notice !== ''): ?>
        <div id="course-safety-notice" style="max-width: 900px; margin: 2.5rem auto 0; padding: 1rem 1.25rem; background: var(--surface, #f8fafc); border: 1px solid var(--border, rgba(0,0,0,0.08)); border-left: 4px solid var(--primary, #3f51b5); border-radius: 10px; font-size: 0.9rem; line-height: 1.55; color: var(--text-muted, #4b5563); display: flex; gap: 12px; align-items: flex-start;">
            <span class="material-icons" style="color: var(--primary, #3f51b5); font-size: 20px; flex-shrink: 0; margin-top: 2px;">health_and_safety</span>
            <div>
                <strong style="display: block; color: var(--text-main, #1f2937); margin-bottom: 4px; font-size: 0.95rem;">Sicherheits- &amp; Haftungshinweis</strong>
                <span><?php echo nl2br(htmlspecialchars($cur_safety_notice)); ?></span>
            </div>
        </div>
    <?php endif; ?>
</section>
