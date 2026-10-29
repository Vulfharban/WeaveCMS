<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Freier Inhaltsabschnitt (custom_text) - Phase 32
 * ============================================================================
 * Unterstützt Markdown, Spalten-Shortcodes ([grid]/[col]), Boxen und Vorlagen.
 *
 * @var array $data
 * @var string $cur_lang
 */

$title = $data['texts']['custom_text_title'] ?? '';
$raw_body = $data['texts']['custom_text_body'] ?? $data['texts']['custom_text'] ?? '';

// Fallback für mehrsprachige Seiten
if (!empty($cur_lang) && !empty($data['texts_i18n'][$cur_lang])) {
    if (!empty($data['texts_i18n'][$cur_lang]['custom_text_title'])) {
        $title = $data['texts_i18n'][$cur_lang]['custom_text_title'];
    }
    if (!empty($data['texts_i18n'][$cur_lang]['custom_text_body'])) {
        $raw_body = $data['texts_i18n'][$cur_lang]['custom_text_body'];
    } elseif (!empty($data['texts_i18n'][$cur_lang]['custom_text'])) {
        $raw_body = $data['texts_i18n'][$cur_lang]['custom_text'];
    }
}

$parsed_html = '';
if (!empty(trim($raw_body))) {
    $parsed_html = MarkdownParser::parse($raw_body);
    if (class_exists('PluginManager')) {
        $parsed_html = PluginManager::applyFilters('content_shortcodes', $parsed_html, $data);
    }
}
?>
<!-- Custom Text Section -->
<section id="custom_text" class="custom-text-section" style="margin: 2.5rem 0;">
    <?php if (!empty(trim($title))): ?>
        <h2 style="color: var(--primary); margin-bottom: 1.5rem; font-size: 2rem;">
            <?php echo htmlspecialchars($title); ?>
        </h2>
    <?php endif; ?>
    
    <?php if (!empty($parsed_html)): ?>
        <div class="custom-text-body" style="font-size: 1.05rem; line-height: 1.75; color: var(--text-primary);">
            <?php echo $parsed_html; ?>
        </div>
    <?php endif; ?>
</section>
