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
$p_content_parsed = MarkdownParser::parse($p_content_raw);
$p_content_html = PluginManager::applyFilters('content_shortcodes', $p_content_parsed, $data);
?>

<?php
$home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
?>
<div class="custom-page-container" style="max-width: var(--container-max-width, 1100px); margin: 100px auto 6rem auto; padding: 0 1.5rem; position: relative; z-index: 10;">
    <!-- Zurück-Button -->
    <div style="margin-bottom: 2rem;">
        <a href="<?php echo htmlspecialchars($home_url); ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; font-size:0.95rem; font-weight:600; text-decoration:none; background:#ffffff; border:1px solid rgba(62,50,40,0.18); color:var(--text-primary); border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.07); transition:all 0.2s ease;" onmouseover="this.style.borderColor='var(--primary)'; this.style.color='var(--primary)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='rgba(62,50,40,0.18)'; this.style.color='var(--text-primary)'; this.style.transform='none';">
            <span class="material-icons" style="font-size:20px; color:var(--primary);">arrow_back</span> <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
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
