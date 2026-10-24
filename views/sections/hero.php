<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Hero
 * ============================================================================
 *
 * @var array $data
 * @var string $logo_pos
 * @var string $hero_logo_html
 */
?>
<!-- Hero Section -->
<section class="hero-section" id="hero">
    <div class="hero-card">
        <h1><?php if ($logo_pos === 'hero_before'): echo $hero_logo_html . ' '; endif; ?><?php echo htmlspecialchars($data['texts']['hero_title'] ?? ''); ?><?php if ($logo_pos === 'hero_after'): echo ' ' . $hero_logo_html; endif; ?></h1>
        <p class="hero-subtitle"><?php echo htmlspecialchars($data['texts']['hero_subtitle'] ?? ''); ?></p>
        <?php if (!empty($data['settings']['hero_cta_enabled'])): ?>
            <div class="hero-cta-group" style="display: flex; justify-content: center; gap: 14px; margin-top: 1.75rem; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars($data['settings']['hero_cta_primary_url'] ?? '?page=tools'); ?>" class="btn btn-primary" style="padding: 11px 24px; font-weight: 700; text-decoration: none; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 19px;">file_download</span>
                    <?php echo htmlspecialchars($data['settings']['hero_cta_primary_text'] ?? 'Tools entdecken'); ?>
                </a>
                <a href="<?php echo htmlspecialchars($data['settings']['hero_cta_secondary_url'] ?? '?page=projects'); ?>" class="btn btn-outline" style="padding: 11px 24px; font-weight: 700; text-decoration: none; border-radius: 12px; border: 1px solid var(--border-color); color: var(--text-primary); background: var(--bg-card); display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-icons" style="font-size: 19px;">rocket_launch</span>
                    <?php echo htmlspecialchars($data['settings']['hero_cta_secondary_text'] ?? 'Projekte ansehen'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
