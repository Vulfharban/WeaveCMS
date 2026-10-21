<?php
/**
 * ============================================================================
 * WeaveCMS Frontend View: Freie Einzelseite / Unterseite (Custom Subpage)
 * ============================================================================
 *
 * @var array $data
 * @var array $active_page
 * @var string $site_url
 */

$p_title = htmlspecialchars($active_page['title'] ?? 'Seite');
$p_slug = htmlspecialchars($active_page['slug'] ?? '');
$p_content_raw = $active_page['content'] ?? '';
$p_content_html = MarkdownParser::parse($p_content_raw);
?>

<div class="custom-page-container" style="max-width: 860px; margin: 3rem auto 6rem auto; padding: 0 1.25rem;">
    <!-- Zurück-Button -->
    <div style="margin-bottom: 2rem;">
        <a href="./" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:0.9rem; text-decoration:none;">
            <span class="material-icons" style="font-size:18px;">arrow_back</span> <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
        </a>
    </div>

    <article class="card" style="padding: 2.5rem 3rem; border-radius: var(--card-radius, 24px);">
        <header style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
            <h1 style="font-size: 2.2rem; line-height: 1.25; margin-bottom: 0.5rem; color: var(--text-primary);">
                <?php echo $p_title; ?>
            </h1>
        </header>

        <!-- Hauptinhalt (Markdown) -->
        <div class="page-body" style="font-size: 1.05rem; line-height: 1.8; color: var(--text-primary);">
            <?php echo $p_content_html; ?>
        </div>
    </article>
</div>
