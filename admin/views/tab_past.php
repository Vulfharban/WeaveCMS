<?php
/**
 * WeaveCMS – Admin Tab: Impressionen & Rückblick (Foto-Impressionen & Archiv)
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */
?>
        <!-- ──────────────── SECTION 4: PAST COURSE IMAGES ──────────────── -->
        <div id="tab-past" class="admin-section card" style="padding: 2.5rem;">
            <?php 
                $section_id = 'past_courses';
                $current_tab = 'tab-past';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;"><?php echo __a('admin.past.title', 'Impressionen &amp; Rückblick verwalten'); ?></h2>
            
            <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add_past_course_image">
                <input type="hidden" name="target_tab" value="tab-past">
                <input type="hidden" name="past_media_url" id="past-course-image-input" value="">

                <div class="form-group" style="margin-bottom:1.25rem; padding:1rem; border:1px dashed var(--border-color); border-radius:12px; background:rgba(255,255,255,0.015);">
                    <label style="display:block; margin-bottom:0.5rem;">Bild aus Mediathek auswählen (Empfohlen)</label>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <img id="past-course-image-preview" src="" alt="" style="display:none; width:64px; height:50px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); flex-shrink:0;">
                        <span id="past-course-image-label" style="font-size:0.85rem; color:var(--text-secondary);">Kein Bild gewählt</span>
                        <button type="button" class="btn btn-outline" style="padding:6px 12px; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;" onclick="openCourseMediaPicker('past-course-image')">
                            <span class="material-icons" style="font-size:17px;">perm_media</span> Aus Mediathek wählen
                        </button>
                        <button type="button" id="past-course-image-clear" class="btn btn-outline" style="display:none; padding:5px 10px; font-size:0.82rem; color:#ef4444; border-color:rgba(239,68,68,0.35);" onclick="clearCourseMedia('past-course-image')">
                            Entfernen
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo __a('admin.past.upload_label', 'Oder neues Bild direkt hochladen (JPG, PNG, WebP)'); ?></label>
                    <input type="file" name="past_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">add_photo_alternate</span> <?php echo __a('admin.past.btn_upload', 'Foto hinzufügen'); ?>
                </button>
            </form>

            <h3 style="font-size:1.2rem; border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:2rem;"><?php echo __a('admin.past.uploaded_title', 'Hochgeladene Fotos'); ?></h3>
            <?php if (empty($data['past_courses'])): ?>
                <p style="color: var(--text-secondary);"><?php echo __a('admin.past.empty', 'Noch keine Bilder hochgeladen.'); ?></p>
            <?php else: ?>
                <div class="grid-past-images">
                    <?php foreach ($data['past_courses'] as $img_path): 
                        $img_thumb = class_exists('MediaManager') ? MediaManager::getThumbUrl((string)$img_path, 'thumb') : (string)$img_path;
                        $img_src = '../' . ltrim($img_thumb, './');
                    ?>
                        <div class="past-image-card">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" loading="lazy" decoding="async" alt="">
                            <form method="POST" action="" onsubmit="return confirm(<?php echo json_encode(__a('admin.past.confirm_delete', 'Möchtest du dieses Bild löschen?')); ?>);">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="delete_past_course_image">
                                <input type="hidden" name="target_tab" value="tab-past">
                                <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($img_path); ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 4px; border-radius: 50%; min-width:0; width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:#dc2626; border:none;" title="<?php echo htmlspecialchars(__a('admin.past.btn_delete_title', 'Bild löschen')); ?>">
                                    <span class="material-icons" style="font-size:16px;">close</span>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
