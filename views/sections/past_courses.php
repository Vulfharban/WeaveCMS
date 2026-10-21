<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Impressionen vergangener Kurse
 * ============================================================================
 *
 * @var array $data
 */
?>
<!-- Historical Course Images Section -->
<section id="past_courses">
    <h3 style="margin-bottom: 1rem; font-size: 1.5rem; text-align: center;"><?php echo htmlspecialchars($data['texts']['past_courses_title'] ?? 'Impressionen vergangener Kurse'); ?></h3>
    <?php if (!empty($data['texts']['past_courses_subtitle'])): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 2rem auto; font-size: 0.95rem;">
            <?php echo htmlspecialchars($data['texts']['past_courses_subtitle']); ?>
        </p>
    <?php endif; ?>
    <?php if (empty($data['past_courses'])): ?>
        <p style="color: var(--text-secondary); font-size:0.95rem; text-align: center;">Noch keine Bilder hochgeladen.</p>
    <?php else: ?>
        <div class="past-grid">
            <?php foreach ($data['past_courses'] as $img): ?>
                <?php 
                    $img_src = (strpos($img, '/') === 0) ? '.' . $img : './' . $img;
                ?>
                <div class="past-card">
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Kursimpression">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
