<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Über mich & Atelier
 * ============================================================================
 *
 * @var array $data
 */
?>
<!-- About Section -->
<section id="about">
    <div style="display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: center;">
        <div>
            <h2 style="color: var(--primary);"><?php echo htmlspecialchars($data['texts']['about_title'] ?? ''); ?></h2>
            <p style="font-size: 1.1rem; color: var(--text-secondary); max-width: 900px; white-space: pre-line; line-height: 1.7;">
                <?php echo htmlspecialchars($data['texts']['about_text'] ?? ''); ?>
            </p>
        </div>
    </div>
</section>
