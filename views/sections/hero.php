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
    </div>
</section>
