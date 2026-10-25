<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Häufige Fragen & Antworten (FAQ Akkordeon)
 * ============================================================================
 *
 * @var array $data
 */

$faqs = $data['faqs'] ?? [];
$faq_title = !empty($data['texts']['faq_title']) ? $data['texts']['faq_title'] : 'Häufige Fragen (FAQ)';
$faq_subtitle = !empty($data['texts']['faq_subtitle']) ? $data['texts']['faq_subtitle'] : 'Hier findest du Antworten auf die meistgestellten Fragen rund um unsere Workshops, Werkstücke und Pflege.';
?>
<!-- FAQ Section -->
<section id="faq" style="margin-bottom: 5rem;">
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo htmlspecialchars($faq_title); ?></h2>
    <?php if (!empty($faq_subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo htmlspecialchars($faq_subtitle); ?>
        </p>
    <?php endif; ?>

    <?php if (empty($faqs)): ?>
        <p style="text-align: center; color: var(--text-secondary);"><?php echo LanguageManager::t('faq.no_items', 'Aktuell sind noch keine FAQ-Einträge hinterlegt.', $cur_lang ?? null); ?></p>
    <?php else: ?>
        <div style="max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($faqs as $idx => $f): ?>
                <?php 
                    $q_raw = (!empty($cur_lang) && !empty($f['translations'][$cur_lang]['question'])) ? $f['translations'][$cur_lang]['question'] : ($f['question'] ?? '');
                    $a_raw = (!empty($cur_lang) && !empty($f['translations'][$cur_lang]['answer'])) ? $f['translations'][$cur_lang]['answer'] : ($f['answer'] ?? '');
                    $q = htmlspecialchars($q_raw);
                    $a = MarkdownParser::parse($a_raw);
                ?>
                <details class="card" style="padding: 1.25rem 1.5rem; border-radius: 14px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                    <summary style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary); list-style: none; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                        <span><?php echo $q; ?></span>
                        <span class="material-icons faq-chevron" style="color: var(--primary); font-size: 22px; transition: transform 0.2s;">expand_more</span>
                    </summary>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.95rem; line-height: 1.65; cursor: default;">
                        <?php echo $a; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <!-- Google Schema.org FAQPage Structured Data -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
                <?php 
                    $schema_entities = [];
                    foreach ($faqs as $f) {
                        $q_schema = (!empty($cur_lang) && !empty($f['translations'][$cur_lang]['question'])) ? $f['translations'][$cur_lang]['question'] : ($f['question'] ?? '');
                        $a_schema = (!empty($cur_lang) && !empty($f['translations'][$cur_lang]['answer'])) ? $f['translations'][$cur_lang]['answer'] : ($f['answer'] ?? '');
                        $schema_entities[] = json_encode([
                            "@type" => "Question",
                            "name" => $q_schema,
                            "acceptedAnswer" => [
                                "@type" => "Answer",
                                "text" => strip_tags(MarkdownParser::parse($a_schema))
                            ]
                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                    echo implode(",\n", $schema_entities);
                ?>
            ]
        }
        </script>
    <?php endif; ?>
</section>
