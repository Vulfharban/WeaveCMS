<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Leistungen & Preise (Pricing Tables)
 * ============================================================================
 *
 * @var array $data
 */

$cur_lang = $cur_lang ?? LanguageManager::detectLanguage($data);
$pricing_plans = $data['pricing'] ?? [];
$pricing_title = !empty($data['texts']['pricing_title']) ? $data['texts']['pricing_title'] : 'Leistungen &amp; Preise';
$pricing_subtitle = !empty($data['texts']['pricing_subtitle']) ? $data['texts']['pricing_subtitle'] : 'Transparente Angebote für individuelle Flechtarbeiten, maßgefertigte Körbe und persönliche Workshops.';
?>
<!-- Pricing Section -->
<section id="pricing" style="margin-bottom: 5rem;">
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo $pricing_title; ?></h2>
    <?php if (!empty($pricing_subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo $pricing_subtitle; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($pricing_plans)): ?>
        <p style="text-align: center; color: var(--text-secondary);"><?php echo LanguageManager::t('pricing.no_items', 'Aktuell sind noch keine Preis- oder Leistungsangebote hinterlegt.', $cur_lang ?? null); ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; max-width: 1100px; margin: 0 auto; align-items: stretch;">
            <?php foreach ($pricing_plans as $plan): ?>
                <?php 
                    $p_title_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['title'])) ? $plan['translations'][$cur_lang]['title'] : ($plan['title'] ?? 'Angebot');
                    $p_period_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['period'])) ? $plan['translations'][$cur_lang]['period'] : ($plan['period'] ?? '');
                    $p_desc_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['description'])) ? $plan['translations'][$cur_lang]['description'] : ($plan['description'] ?? '');
                    $p_badge_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['badge'])) ? $plan['translations'][$cur_lang]['badge'] : ($plan['badge'] ?? '');
                    $p_btn_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['button_text'])) ? $plan['translations'][$cur_lang]['button_text'] : ($plan['button_text'] ?? LanguageManager::t('btn.inquire_now', 'Jetzt anfragen', $cur_lang ?? null));
                    
                    $p_feats_raw = (!empty($cur_lang) && !empty($plan['translations'][$cur_lang]['features'])) ? $plan['translations'][$cur_lang]['features'] : ($plan['features'] ?? []);
                    if (!is_array($p_feats_raw)) {
                        $p_feats_raw = array_filter(array_map('trim', explode("\n", (string)$p_feats_raw)));
                    }

                    $p_title = htmlspecialchars($p_title_raw);
                    $p_price = htmlspecialchars($plan['price'] ?? '');
                    $p_period = htmlspecialchars($p_period_raw);
                    $p_desc = htmlspecialchars($p_desc_raw);
                    $p_badge = htmlspecialchars($p_badge_raw);
                    $p_highlight = !empty($plan['highlighted']);
                    $p_btn = htmlspecialchars($p_btn_raw);
                    $p_link = htmlspecialchars($plan['button_link'] ?? '#contact');
                    $p_features = $p_feats_raw;
                ?>
                <div class="card" style="padding: 2.25rem 2rem; border-radius: var(--card-radius, 20px); display: flex; flex-direction: column; position: relative; <?php echo $p_highlight ? 'border: 2px solid var(--primary); box-shadow: 0 10px 30px rgba(46,98,72,0.15);' : ''; ?>">
                    <?php if (!empty($p_badge)): ?>
                        <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 14px; border-radius: 999px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                            <?php echo $p_badge; ?>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin: 0.5rem 0 0.5rem 0; font-size: 1.35rem; color: var(--text-primary); text-align: center;"><?php echo $p_title; ?></h3>
                    
                    <?php if (!empty($p_desc)): ?>
                        <p style="text-align: center; color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5; margin: 0 0 1.5rem 0;">
                            <?php echo $p_desc; ?>
                        </p>
                    <?php endif; ?>

                    <div style="text-align: center; margin-bottom: 1.75rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                        <span style="font-size: 2.4rem; font-weight: 800; color: var(--primary); font-family: var(--font-heading, sans-serif); line-height: 1;"><?php echo $p_price; ?></span>
                        <?php if (!empty($p_period)): ?>
                            <span style="font-size: 0.85rem; color: var(--text-secondary); margin-left: 4px;"><?php echo $p_period; ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($p_features)): ?>
                        <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; flex: 1; display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($p_features as $feat): ?>
                                <li style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.92rem; color: var(--text-primary);">
                                    <span class="material-icons" style="color: var(--primary); font-size: 18px; margin-top: 1px; flex-shrink: 0;">check_circle</span>
                                    <span><?php echo htmlspecialchars($feat); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div style="margin-top: auto; text-align: center;">
                        <a href="<?php echo $p_link; ?>" class="btn <?php echo $p_highlight ? 'btn-primary' : 'btn-secondary'; ?>" style="width: 100%; justify-content: center; text-decoration: none; padding: 10px 20px;">
                            <?php echo $p_btn; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
