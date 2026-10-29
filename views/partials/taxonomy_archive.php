<?php
/**
 * ============================================================================
 * WeaveCMS Frontend View: Globales Taxonomie-Archiv (Tag-Archivseite)
 * ============================================================================
 * Zeigt alle Inhalte aus Blog, Einzelseiten, Kursen, Galerie und Downloads,
 * die mit dem ausgewählten Schlagwort verknüpft sind (Phase 28.1).
 *
 * @var array $data
 * @var array $tag_data
 * @var string $site_url
 * @var string $cur_lang
 * @var string $primary_lang
 */

$tag_info = $tag_data['tag'] ?? ['name' => 'Schlagwort', 'slug' => ''];
$tag_name = htmlspecialchars($tag_info['name']);
$total_count = (int)($tag_data['total'] ?? 0);
$home_url = (!empty($cur_lang) && $cur_lang !== ($primary_lang ?? 'de')) ? './?lang=' . urlencode($cur_lang) : './';

$all_tags = TaxonomyManager::getAllTags($data);
?>

<div class="taxonomy-archive-container" style="max-width: var(--container-max-width, 1100px); margin: 60px auto 6rem auto; padding: 0 1.25rem; position: relative; z-index: 10;">
    <!-- Zurück-Button -->
    <div style="margin-bottom: 1.5rem;">
        <a href="<?php echo htmlspecialchars($home_url); ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-size:0.9rem; font-weight:600; text-decoration:none; background:#ffffff; border:1px solid rgba(62,50,40,0.18); color:var(--text-primary); border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.07); transition:all 0.2s ease;">
            <span class="material-icons" style="font-size:18px; color:var(--primary);">arrow_back</span> <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
        </a>
    </div>

    <!-- Archiv Header Card -->
    <header class="card" style="padding: 2.5rem; border-radius: var(--card-radius, 20px); margin-bottom: 2rem; border: 1px solid var(--border-color); background: var(--bg-card, #ffffff); box-shadow: 0 8px 30px rgba(0,0,0,0.05);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
            <div>
                <div style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.5rem;">
                    <span class="material-icons" style="font-size:18px;">local_offer</span> <?php echo LanguageManager::t('frontend.tag.archive_label', 'Schlagwort-Archiv', $cur_lang ?? null); ?>
                </div>
                <h1 style="font-size: clamp(2rem, 4vw, 2.6rem); margin: 0 0 0.5rem 0; color: var(--text-primary); font-weight: 800;">
                    #<?php echo $tag_name; ?>
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: 1rem;">
                    <?php 
                    $count_label = ($total_count === 1) 
                        ? LanguageManager::t('frontend.tag.found_single', '1 passender Inhalt gefunden', $cur_lang ?? null)
                        : str_replace('{count}', (string)$total_count, LanguageManager::t('frontend.tag.found_multiple', '{count} passende Inhalte gefunden', $cur_lang ?? null));
                    echo htmlspecialchars($count_label);
                    ?>
                </p>
            </div>

            <!-- Weitere Tags / Tag-Cloud Schnellfilter -->
            <?php if (count($all_tags) > 1): ?>
                <div style="max-width: 420px;">
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text-secondary); margin-bottom:6px;">
                        <?php echo LanguageManager::t('frontend.tag.other_tags', 'Weitere Schlagwörter:', $cur_lang ?? null); ?>
                    </div>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php foreach (array_slice($all_tags, 0, 8) as $ot): if ($ot['slug'] === $tag_info['slug']) continue; ?>
                            <a href="<?php echo htmlspecialchars($home_url . '?tag=' . urlencode($ot['slug'])); ?>" style="font-size:0.78rem; font-weight:600; padding:3px 10px; border-radius:999px; background:rgba(0,0,0,0.04); color:var(--text-secondary); text-decoration:none; transition:all 0.15s ease;" onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(0,0,0,0.04)'; this.style.color='var(--text-secondary)';">
                                #<?php echo htmlspecialchars($ot['name']); ?> (<?php echo (int)$ot['count']; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($total_count === 0): ?>
        <div class="card text-center" style="padding: 3rem 2rem; border-radius: 16px; border: 1px solid var(--border-color);">
            <span class="material-icons" style="font-size: 48px; color: var(--text-secondary); opacity: 0.5;">search_off</span>
            <h3 style="margin-top: 1rem; color: var(--text-primary);"><?php echo LanguageManager::t('frontend.tag.empty_title', 'Keine passenden Inhalte gefunden', $cur_lang ?? null); ?></h3>
            <p style="color: var(--text-secondary); max-width: 500px; margin: 0.5rem auto 1.5rem auto;">
                <?php echo LanguageManager::t('frontend.tag.empty_desc', 'Zu diesem Schlagwort wurden derzeit keine aktiven Beiträge, Seiten oder Angebote hinterlegt.', $cur_lang ?? null); ?>
            </p>
            <a href="<?php echo htmlspecialchars($home_url); ?>" class="btn btn-primary" style="display:inline-block;">
                <?php echo LanguageManager::t('btn.back_home', 'Zurück zur Startseite', $cur_lang ?? null); ?>
            </a>
        </div>
    <?php else: ?>

        <!-- 1. Blog-Beiträge -->
        <?php if (!empty($tag_data['blog'])): ?>
            <section style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">newspaper</span> <?php echo LanguageManager::t('frontend.tag.sec_blog', 'Blog-Artikel', $cur_lang ?? null); ?>
                    <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);">(<?php echo count($tag_data['blog']); ?>)</span>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($tag_data['blog'] as $p): 
                        $b_url = $home_url . '?blog=' . urlencode($p['slug'] ?? '');
                        $b_date = !empty($p['date']) ? date('d.m.Y', strtotime($p['date'])) : '';
                    ?>
                        <article class="card" style="border-radius: var(--card-radius, 16px); overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
                            <?php if (!empty($p['image'])): ?>
                                <a href="<?php echo htmlspecialchars($b_url); ?>" style="display:block; height:180px; overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" style="width:100%; height:100%; object-fit:cover; transition:transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                                </a>
                            <?php endif; ?>
                            <div style="padding: 1.25rem; display:flex; flex-direction:column; flex-grow:1;">
                                <?php if (!empty($b_date)): ?>
                                    <span style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.4rem; display:block;"><?php echo htmlspecialchars($b_date); ?></span>
                                <?php endif; ?>
                                <h3 style="font-size:1.15rem; margin:0 0 0.5rem 0;">
                                    <a href="<?php echo htmlspecialchars($b_url); ?>" style="color:var(--text-primary); text-decoration:none;"><?php echo htmlspecialchars($p['title']); ?></a>
                                </h3>
                                <?php if (!empty($p['teaser'])): ?>
                                    <p style="font-size:0.9rem; color:var(--text-secondary); margin:0 0 1rem 0; line-height:1.5; flex-grow:1;">
                                        <?php echo htmlspecialchars(mb_substr($p['teaser'], 0, 120)) . (mb_strlen($p['teaser']) > 120 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($b_url); ?>" style="color:var(--primary); font-weight:700; font-size:0.88rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                    <?php echo LanguageManager::t('blog.read_more', 'Weiterlesen', $cur_lang ?? null); ?> <span class="material-icons" style="font-size:16px;">arrow_forward</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 2. Einzelseiten -->
        <?php if (!empty($tag_data['pages'])): ?>
            <section style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">article</span> <?php echo LanguageManager::t('frontend.tag.sec_pages', 'Seiten & Inhalte', $cur_lang ?? null); ?>
                    <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);">(<?php echo count($tag_data['pages']); ?>)</span>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
                    <?php foreach ($tag_data['pages'] as $pg): 
                        $pg_url = $home_url . '?page=' . urlencode($pg['slug'] ?? '');
                    ?>
                        <div class="card" style="padding: 1.5rem; border-radius: var(--card-radius, 14px); border: 1px solid var(--border-color); display:flex; flex-direction:column;">
                            <h3 style="font-size:1.15rem; margin:0 0 0.5rem 0;">
                                <a href="<?php echo htmlspecialchars($pg_url); ?>" style="color:var(--text-primary); text-decoration:none;"><?php echo htmlspecialchars($pg['title']); ?></a>
                            </h3>
                            <?php if (!empty($pg['meta_description'])): ?>
                                <p style="font-size:0.9rem; color:var(--text-secondary); margin:0 0 1rem 0; line-height:1.5; flex-grow:1;">
                                     <?php echo htmlspecialchars($pg['meta_description']); ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars($pg_url); ?>" style="color:var(--primary); font-weight:700; font-size:0.88rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                <?php echo LanguageManager::t('btn.view_page', 'Seite ansehen', $cur_lang ?? null); ?> <span class="material-icons" style="font-size:16px;">arrow_forward</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 3. Kurse & Workshops -->
        <?php if (!empty($tag_data['courses'])): ?>
            <section style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">event</span> <?php echo LanguageManager::t('frontend.tag.sec_courses', 'Kurse & Workshops', $cur_lang ?? null); ?>
                    <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);">(<?php echo count($tag_data['courses']); ?>)</span>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
                    <?php foreach ($tag_data['courses'] as $c): 
                        $c_id = $c['id'] ?? 0;
                        $c_url = $home_url . '?course=' . (int)$c_id . '#courses';
                    ?>
                        <div class="card" style="padding: 1.5rem; border-radius: var(--card-radius, 14px); border: 1px solid var(--border-color); display:flex; flex-direction:column;">
                            <?php if (!empty($c['date'])): ?>
                                <span style="font-size:0.8rem; font-weight:700; color:var(--primary); margin-bottom:0.4rem; display:block;"><?php echo htmlspecialchars($c['date']); ?></span>
                            <?php endif; ?>
                            <h3 style="font-size:1.15rem; margin:0 0 0.5rem 0;">
                                <a href="<?php echo htmlspecialchars($c_url); ?>" style="color:var(--text-primary); text-decoration:none;"><?php echo htmlspecialchars($c['title'] ?? ''); ?></a>
                            </h3>
                            <?php if (!empty($c['description'])): ?>
                                <p style="font-size:0.9rem; color:var(--text-secondary); margin:0 0 1rem 0; line-height:1.5; flex-grow:1;">
                                    <?php echo htmlspecialchars(mb_substr($c['description'], 0, 100)) . '...'; ?>
                                </p>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto;">
                                <span style="font-weight:800; color:var(--text-primary);"><?php echo htmlspecialchars($c['price'] ?? ''); ?></span>
                                <a href="<?php echo htmlspecialchars($c_url); ?>" class="btn btn-secondary" style="padding:6px 12px; font-size:0.85rem;">
                                    <?php echo LanguageManager::t('courses.details', 'Details', $cur_lang ?? null); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 4. Galerie -->
        <?php if (!empty($tag_data['gallery'])): ?>
            <section style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">photo_library</span> <?php echo LanguageManager::t('frontend.tag.sec_gallery', 'Galerie & Werkstücke', $cur_lang ?? null); ?>
                    <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);">(<?php echo count($tag_data['gallery']); ?>)</span>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                    <?php foreach ($tag_data['gallery'] as $g): ?>
                        <div class="card" style="border-radius:12px; overflow:hidden; border:1px solid var(--border-color);">
                            <img src="<?php echo htmlspecialchars($g['image']); ?>" alt="<?php echo htmlspecialchars($g['title'] ?? ''); ?>" style="width:100%; height:200px; object-fit:cover;">
                            <?php if (!empty($g['title'])): ?>
                                <div style="padding: 10px 12px; font-size:0.88rem; font-weight:600; color:var(--text-primary); text-align:center;">
                                    <?php echo htmlspecialchars($g['title']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 5. Downloads -->
        <?php if (!empty($tag_data['downloads'])): ?>
            <section style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">folder_zip</span> <?php echo LanguageManager::t('frontend.tag.sec_downloads', 'Downloads & Dokumente', $cur_lang ?? null); ?>
                    <span style="font-size:0.85rem; font-weight:normal; color:var(--text-secondary);">(<?php echo count($tag_data['downloads']); ?>)</span>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
                    <?php foreach ($tag_data['downloads'] as $d): ?>
                        <div class="card" style="padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                            <div>
                                <h4 style="margin:0 0 4px 0; font-size:0.95rem; color:var(--text-primary);"><?php echo htmlspecialchars($d['title'] ?? 'Download'); ?></h4>
                                <span style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($d['file_size'] ?? ''); ?></span>
                            </div>
                            <a href="<?php echo htmlspecialchars($d['file_path'] ?? '#'); ?>" class="btn btn-secondary" download style="padding:6px 12px; font-size:0.85rem;">
                                <span class="material-icons" style="font-size:16px;">file_download</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>
</div>
