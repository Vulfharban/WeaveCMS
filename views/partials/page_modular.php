<?php
/**
 * ============================================================================
 * WeaveCMS Frontend View: Modulare Sektions-Landingpage (Phase 18.2)
 * ============================================================================
 * Rendert eine flexible Landingpage mit frei gestackten Sektionen
 * in benutzerdefinierter Reihenfolge.
 *
 * @var array $data
 * @var array $active_page
 * @var string $site_url
 * @var string $cur_lang
 * @var string $primary_lang
 */

$p_title = htmlspecialchars($active_page['title'] ?? 'Landingpage');
$p_content_raw = trim($active_page['content'] ?? '');
$has_intro = !empty($p_content_raw);
$p_intro_html = '';
if ($has_intro) {
    $parsed = MarkdownParser::parse($p_content_raw);
    $p_intro_html = PluginManager::applyFilters('content_shortcodes', $parsed, $data);
}
$page_sections = $active_page['page_sections'] ?? [];
$home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
?>

<div class="modular-page-wrapper" style="position: relative; z-index: 10;">
    <!-- Breadcrumb / Zurück-Leiste -->
    <div class="container" style="max-width: var(--container-width, 1200px); margin: 90px auto 1rem auto; padding: 0 1.5rem;">
        <a href="<?php echo htmlspecialchars($home_url); ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:0.9rem; font-weight:600; text-decoration:none; background:#ffffff; border:1px solid rgba(62,50,40,0.18); color:var(--text-primary); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:all 0.2s ease;">
            <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_back</span> <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
        </a>
    </div>

    <?php if ($has_intro): ?>
        <section class="container" style="max-width: var(--container-width, 1200px); margin: 0 auto 2.5rem auto; padding: 0 1.5rem;">
            <article class="card" style="padding: 2.5rem 3rem; border-radius: var(--card-radius, 24px); box-shadow: 0 8px 30px rgba(0,0,0,0.04);">
                <header style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                    <h1 style="font-size: 2.4rem; line-height: 1.2; margin-bottom: 0.5rem; color: var(--text-primary);">
                        <?php echo $p_title; ?>
                    </h1>
                </header>
                <div class="page-intro-body" style="font-size: 1.1rem; line-height: 1.8; color: var(--text-primary);">
                    <?php echo $p_intro_html; ?>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <!-- Gestackte Sektionen -->
    <main class="container">
        <?php 
        if (empty($page_sections)) {
            echo '<div style="text-align:center; padding: 4rem 1.5rem; color: var(--text-secondary);">'
               . '<p>Diese modulare Landingpage enthält aktuell noch keine aktiven Sektionen.</p>'
               . '</div>';
        } else {
            foreach ($page_sections as $secId) {
                // Suche Sektionsdaten
                $sec = SectionManager::getSectionById($secId, $data);
                if ($sec) {
                    $secType = !empty($sec['type']) ? $sec['type'] : $secId;
                    FrontendController::renderSection($secType, $vars ?? get_defined_vars());
                }
            }
        }
        ?>
    </main>
</div>
