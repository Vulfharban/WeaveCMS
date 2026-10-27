<?php
/**
 * ============================================================================
 * WeaveCMS Frontend View: Standalone Sektion als Einzelseite (Phase 18.1 / 18.3)
 * ============================================================================
 * Rendert eine Hauptsektion (z. B. Galerie, Kurse, Downloads, Webtrees)
 * im klassischen Multi-Page-Modus als eigenständige Unterseite.
 *
 * @var array $data
 * @var array $active_section
 * @var string $cur_lang
 * @var string $primary_lang
 */

$sec_id = $active_section['id'] ?? '';
$sec_type = $active_section['type'] ?? $sec_id;
$home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';
?>

<div class="standalone-section-wrapper" style="position: relative; z-index: 10;">
    <!-- Breadcrumb / Zurück-Leiste -->
    <div class="container" style="max-width: var(--container-width, 1200px); margin: 90px auto 1rem auto; padding: 0 1.5rem;">
        <a href="<?php echo htmlspecialchars($home_url); ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:0.9rem; font-weight:600; text-decoration:none; background:#ffffff; border:1px solid rgba(62,50,40,0.18); color:var(--text-primary); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:all 0.2s ease;">
            <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_back</span> <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
        </a>
    </div>

    <!-- Sektions-Container -->
    <main class="container">
        <?php 
        $render_target = file_exists(dirname(__DIR__) . '/sections/' . $sec_type . '.php') ? $sec_type : $sec_id;
        FrontendController::renderSection($render_target, $vars ?? get_defined_vars());
        ?>
    </main>
</div>
