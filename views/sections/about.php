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
            <div class="about-text-body" style="font-size: 1.1rem; color: var(--text-secondary); max-width: 900px; line-height: 1.7;">
                <?php 
                    $about_raw = $data['texts']['about_text'] ?? '';
                    $about_html = MarkdownParser::parse($about_raw);
                    if (class_exists('PluginManager')) {
                        $about_html = PluginManager::applyFilters('content_shortcodes', $about_html, $data);
                    }
                    echo $about_html;
                ?>
            </div>
        </div>
    </div>
</section>
