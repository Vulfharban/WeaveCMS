<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Galerie
 * ============================================================================
 *
 * @var array $data
 * @var array $categories
 * @var array $visible_gallery
 */
?>
<!-- Gallery Section -->
<section id="gallery">
    <h2 style="margin-bottom: 1rem; text-align: center;"><?php echo htmlspecialchars($data['texts']['gallery_title'] ?? 'Galerie & Handarbeit'); ?></h2>
    <?php if (!empty($data['texts']['gallery_subtitle'])): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo htmlspecialchars($data['texts']['gallery_subtitle']); ?>
        </p>
    <?php endif; ?>

    <?php if (empty($visible_gallery)): ?>
        <p style="text-align: center; color: var(--text-secondary);"><?php echo htmlspecialchars(__('gallery.no_items', 'Noch keine Werkstücke in der Galerie.')); ?></p>
    <?php else: ?>
        <!-- Category Filter Buttons -->
        <div class="filter-container">
            <button class="filter-btn active" onclick="filterGallery('all', this)"><?php echo htmlspecialchars(__('btn.filter_all', 'Alle')); ?></button>
            <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" onclick="filterGallery('<?php echo htmlspecialchars($cat); ?>', this)"><?php echo htmlspecialchars($cat); ?></button>
            <?php endforeach; ?>
        </div>

        <?php 
            $gallery_layout = $data['settings']['gallery_layout'] ?? 'ambient_blur';
            $gallery_lightbox = $data['settings']['gallery_lightbox'] ?? true;
            
            switch ($gallery_layout) {
                case 'masonry':
                    $container_class = 'gallery-container-masonry';
                    break;
                case 'editorial':
                    $container_class = 'gallery-container-editorial';
                    break;
                case 'classic':
                    $container_class = 'gallery-container-classic';
                    break;
                case 'ambient_blur':
                default:
                    $container_class = 'gallery-container-ambient';
                    break;
            }
        ?>
        <!-- Gallery Grid (Dynamisch: <?php echo htmlspecialchars($gallery_layout); ?>) -->
        <div class="<?php echo $container_class; ?>">
            <?php foreach ($visible_gallery as $idx => $item): ?>
                <?php 
                    $img_raw = $item['image'] ?? '';
                    $img_src = (strpos($img_raw, 'http://') === 0 || strpos($img_raw, 'https://') === 0)
                        ? $img_raw
                        : ((strpos($img_raw, '/') === 0) ? '.' . $img_raw : './' . $img_raw);
                    $is_portrait = false;
                    $local_file = dirname(__DIR__, 2) . '/' . ltrim($item['image'] ?? '', '/\\');
                    if (file_exists($local_file)) {
                        $size = @getimagesize($local_file);
                        if ($size && $size[1] > ($size[0] * 1.1)) {
                            $is_portrait = true;
                        }
                    }
                ?>
                <div class="card gallery-card <?php echo $is_portrait ? 'card-portrait' : ''; ?>" data-category="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" data-index="<?php echo $idx; ?>">
                    <div class="gallery-media-wrapper <?php echo $is_portrait ? 'is-portrait' : ''; ?>" <?php if ($gallery_lightbox): ?>onclick="openLightbox(<?php echo $idx; ?>)" style="cursor:zoom-in;" title="<?php echo htmlspecialchars(__('gallery.zoom_hint', 'Klicken für Vollbildansicht')); ?>"<?php endif; ?>>
                        <?php if ($gallery_layout === 'ambient_blur'): ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-bg-blur" alt="" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-fg-img" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" loading="lazy">
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="gallery-fg-img" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" loading="lazy">
                        <?php endif; ?>
                        <?php if ($gallery_lightbox): ?>
                            <span class="lightbox-hint"><span class="material-icons" style="font-size:18px;">zoom_in</span></span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:1.3rem;"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
                        <span style="font-size:0.75rem; background:rgba(16,185,129,0.1); color:var(--primary); padding:3px 10px; border-radius:12px; font-weight:600;"><?php echo htmlspecialchars($item['category'] ?? ''); ?></span>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
