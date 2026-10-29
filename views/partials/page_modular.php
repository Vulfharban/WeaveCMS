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
               . '<p>' . htmlspecialchars(LanguageManager::t('admin.pages.modular_empty_notice', 'Diese modulare Landingpage enthält aktuell noch keine aktiven Sektionen.', $cur_lang ?? null)) . '</p>'
               . '</div>';
        } else {
            $sec_layouts = $active_page['page_section_layouts'] ?? [];
            $vars_scope = $vars ?? get_defined_vars();

            // Sektionen nach Grid-Layouts gruppieren (Phase 32.1)
            $i = 0;
            $count = count($page_sections);
            while ($i < $count) {
                $secId = $page_sections[$i];
                $layout = $sec_layouts[$secId] ?? 'full';

                if ($layout === 'full' || !in_array($layout, ['2', '3', '1-2', '2-1'], true)) {
                    // 1. Vollbreite-Sektion
                    $sec = SectionManager::getSectionById($secId, $data);
                    if ($sec) {
                        $secType = !empty($sec['type']) ? $sec['type'] : $secId;
                        FrontendController::renderSection($secType, $vars_scope);
                    }
                    $i++;
                } elseif ($layout === '2') {
                    // 2. 2-Spalten-Layout (50% / 50%)
                    $colSecs = [$secId];
                    if (($i + 1) < $count && ($sec_layouts[$page_sections[$i + 1]] ?? 'full') === '2') {
                        $colSecs[] = $page_sections[$i + 1];
                        $i += 2;
                    } else {
                        $i++;
                    }

                    echo '<div class="weave-grid weave-grid-2 weave-section-grid" style="margin: 2.5rem 0; align-items: stretch;">';
                    foreach ($colSecs as $csId) {
                        $cSec = SectionManager::getSectionById($csId, $data);
                        echo '<div class="weave-col weave-section-col" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--card-radius, 16px); padding: 2rem;">';
                        if ($cSec) {
                            $cType = !empty($cSec['type']) ? $cSec['type'] : $csId;
                            FrontendController::renderSection($cType, $vars_scope);
                        }
                        echo '</div>';
                    }
                    // Falls nur eine Spalte vorhanden ist, leeren Platzhalter rendern für sauberes 50%-Design
                    if (count($colSecs) === 1) {
                        echo '<div class="weave-col weave-section-col weave-col-empty" style="display:none;"></div>';
                    }
                    echo '</div>';
                } elseif ($layout === '3') {
                    // 3. 3-Spalten-Layout (33% / 33% / 33%)
                    $colSecs = [$secId];
                    while (count($colSecs) < 3 && ($i + count($colSecs)) < $count && ($sec_layouts[$page_sections[$i + count($colSecs)]] ?? 'full') === '3') {
                        $colSecs[] = $page_sections[$i + count($colSecs)];
                    }
                    $i += count($colSecs);

                    echo '<div class="weave-grid weave-grid-3 weave-section-grid" style="margin: 2.5rem 0; align-items: stretch;">';
                    foreach ($colSecs as $csId) {
                        $cSec = SectionManager::getSectionById($csId, $data);
                        echo '<div class="weave-col weave-section-col" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--card-radius, 16px); padding: 1.5rem;">';
                        if ($cSec) {
                            $cType = !empty($cSec['type']) ? $cSec['type'] : $csId;
                            FrontendController::renderSection($cType, $vars_scope);
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                } elseif ($layout === '1-2' || $layout === '2-1') {
                    // 4. Asymmetrisches Spalten-Layout (1/3 zu 2/3 oder 2/3 zu 1/3)
                    $gridClass = ($layout === '1-2') ? 'weave-grid-1-2' : 'weave-grid-2-1';
                    $colSecs = [$secId];
                    $opposite = ($layout === '1-2') ? '2-1' : '1-2';
                    if (($i + 1) < $count && in_array($sec_layouts[$page_sections[$i + 1]] ?? '', [$opposite, '2', '1-2', '2-1'], true)) {
                        $colSecs[] = $page_sections[$i + 1];
                        $i += 2;
                    } else {
                        $i++;
                    }

                    echo '<div class="weave-grid ' . $gridClass . ' weave-section-grid" style="margin: 2.5rem 0; align-items: stretch;">';
                    foreach ($colSecs as $csId) {
                        $cSec = SectionManager::getSectionById($csId, $data);
                        echo '<div class="weave-col weave-section-col" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--card-radius, 16px); padding: 1.75rem;">';
                        if ($cSec) {
                            $cType = !empty($cSec['type']) ? $cSec['type'] : $csId;
                            FrontendController::renderSection($cType, $vars_scope);
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    $i++;
                }
            }
        }
        ?>
    </main>
</div>
