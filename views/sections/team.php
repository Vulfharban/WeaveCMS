<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Team & Meister (Profilkarten)
 * ============================================================================
 *
 * @var array $data
 */

$cur_lang = $cur_lang ?? LanguageManager::detectLanguage($data);
$team_members = $data['team'] ?? [];
$team_title = !empty($data['texts']['team_title']) ? $data['texts']['team_title'] : 'Unser Team &amp; Meister';
$team_subtitle = !empty($data['texts']['team_subtitle']) ? $data['texts']['team_subtitle'] : 'Lerne die kreativen Köpfe hinter unseren Werkstücken, Kursen und Designs kennen.';
?>
<!-- Team Section -->
<section id="team" style="margin-bottom: 5rem;">
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo $team_title; ?></h2>
    <?php if (!empty($team_subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo $team_subtitle; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($team_members)): ?>
        <p style="text-align: center; color: var(--text-secondary);"><?php echo LanguageManager::t('team.no_items', 'Aktuell sind noch keine Teammitglieder hinterlegt.', $cur_lang ?? null); ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem;">
            <?php foreach ($team_members as $m): ?>
                <?php 
                    $m_name = htmlspecialchars($m['name'] ?? '');
                    $m_role_raw = (!empty($cur_lang) && !empty($m['translations'][$cur_lang]['role'])) ? $m['translations'][$cur_lang]['role'] : ($m['role'] ?? '');
                    $m_bio_raw = (!empty($cur_lang) && !empty($m['translations'][$cur_lang]['bio'])) ? $m['translations'][$cur_lang]['bio'] : ($m['bio'] ?? '');
                    $m_role = htmlspecialchars($m_role_raw);
                    $m_bio = htmlspecialchars($m_bio_raw);
                    $m_img = !empty($m['image']) ? htmlspecialchars($m['image']) : '';
                    $m_email = htmlspecialchars($m['email'] ?? '');
                ?>
                <div class="card" style="text-align:center; padding: 2.5rem 1.75rem; border-radius: var(--card-radius, 20px); display:flex; flex-direction:column; align-items:center;">
                    <?php if (!empty($m_img)): ?>
                        <img src="<?php echo $m_img; ?>" alt="<?php echo $m_name; ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1.25rem; border: 3px solid var(--primary); box-shadow: 0 6px 16px rgba(0,0,0,0.08);">
                    <?php else: ?>
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: rgba(46,98,72,0.1); color: var(--primary); display:flex; align-items:center; justify-content:center; margin-bottom: 1.25rem;">
                            <span class="material-icons" style="font-size: 56px;">person</span>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin: 0 0 0.35rem 0; font-size: 1.25rem; color: var(--text-primary);"><?php echo $m_name; ?></h3>
                    <?php if (!empty($m_role)): ?>
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); background: rgba(46,98,72,0.08); padding: 3px 12px; border-radius: 999px; margin-bottom: 1rem; display:inline-block;">
                            <?php echo $m_role; ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($m_bio)): ?>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.55; margin: 0 0 1.25rem 0; flex:1;">
                            <?php echo $m_bio; ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($m_email)): ?>
                        <a href="mailto:<?php echo $m_email; ?>" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 14px; text-decoration:none;">
                            <span class="material-icons" style="font-size: 15px;">mail</span> <?php echo LanguageManager::t('btn.contact_person', 'Kontaktieren', $cur_lang ?? null); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
