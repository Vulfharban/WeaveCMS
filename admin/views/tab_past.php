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
                <div class="form-group">
                    <label><?php echo __a('admin.past.upload_label', 'Neues Bild hochladen (JPG, PNG, WebP)'); ?></label>
                    <input type="file" name="past_image" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons" style="font-size:18px; vertical-align:middle;">cloud_upload</span> <?php echo __a('admin.past.btn_upload', 'Foto hochladen'); ?>
                </button>
            </form>

            <h3 style="font-size:1.2rem; border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:2rem;"><?php echo __a('admin.past.uploaded_title', 'Hochgeladene Fotos'); ?></h3>
            <?php if (empty($data['past_courses'])): ?>
                <p style="color: var(--text-secondary);"><?php echo __a('admin.past.empty', 'Noch keine Bilder hochgeladen.'); ?></p>
            <?php else: ?>
                <div class="grid-past-images">
                    <?php foreach ($data['past_courses'] as $img_path): 
                        $img_src = (strpos((string)$img_path, '/') === 0) ? ('..' . $img_path) : ('../' . $img_path);
                    ?>
                        <div class="past-image-card">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="">
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
