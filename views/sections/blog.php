<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Blog & Aktuelles
 * ============================================================================
 *
 * @var array $data
 * @var string $site_url
 */

$all_posts = $data['blog'] ?? [];
$published_posts = array_filter($all_posts, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});

// Nach Datum absteigend sortieren
usort($published_posts, function($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

$blog_title = !empty($data['texts']['blog_title']) ? $data['texts']['blog_title'] : 'Aktuelles &amp; Blog';
$blog_subtitle = !empty($data['texts']['blog_subtitle']) ? $data['texts']['blog_subtitle'] : 'Geschichten, Einblicke und Neuigkeiten direkt aus unserem Atelier.';
?>
<!-- Blog & News Section -->
<section id="blog" style="margin-bottom: 5rem;">
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo $blog_title; ?></h2>
    <?php if (!empty($blog_subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo $blog_subtitle; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($published_posts)): ?>
        <p style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">
            <?php echo LanguageManager::t('blog.no_posts', 'Aktuell sind noch keine Blog-Beiträge veröffentlicht. Schau bald wieder vorbei!', $cur_lang ?? null); ?>
        </p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
            <?php foreach ($published_posts as $post): ?>
                <?php
                    $p_title = htmlspecialchars($post['title'] ?? 'Ohne Titel');
                    $p_slug = htmlspecialchars($post['slug'] ?? '');
                    $p_date_raw = $post['date'] ?? '';
                    $p_date_formatted = !empty($p_date_raw) ? date('d.m.Y', strtotime($p_date_raw)) : '';
                    $p_author = htmlspecialchars($post['author'] ?? ($data['texts']['contact_name'] ?? ''));
                    $p_teaser = htmlspecialchars($post['teaser'] ?? '');
                    $p_img = !empty($post['image']) ? htmlspecialchars($post['image']) : '';
                    $p_tags = $post['tags'] ?? [];
                    $p_url = '?blog=' . urlencode($post['slug'] ?? '');
                ?>
                <article class="card" style="display:flex; flex-direction:column; overflow:hidden; transition:transform 0.25s ease, box-shadow 0.25s ease; border-radius:var(--card-radius, 20px);" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                    <?php if (!empty($p_img)): ?>
                        <a href="<?php echo $p_url; ?>" style="display:block; overflow:hidden; height:210px; position:relative;">
                            <img src="<?php echo $p_img; ?>" alt="<?php echo $p_title; ?>" style="width:100%; height:100%; object-fit:cover; transition:transform 0.35s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='none'">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $p_url; ?>" style="display:flex; align-items:center; justify-content:center; height:150px; background:rgba(46,98,72,0.06); text-decoration:none; color:var(--primary);">
                            <span class="material-icons" style="font-size:44px; opacity:0.6;">article</span>
                        </a>
                    <?php endif; ?>

                    <div style="padding: 1.75rem; display:flex; flex-direction:column; flex:1;">
                        <div style="display:flex; align-items:center; gap:10px; font-size:0.83rem; color:var(--text-secondary); margin-bottom:0.75rem; flex-wrap:wrap;">
                            <?php if ($p_date_formatted): ?>
                                <span style="display:flex; align-items:center; gap:4px;">
                                    <span class="material-icons" style="font-size:15px; color:var(--accent);">calendar_today</span>
                                    <?php echo $p_date_formatted; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p_author): ?>
                                <span style="display:flex; align-items:center; gap:4px;">
                                    <span class="material-icons" style="font-size:15px;">person</span>
                                    <?php echo $p_author; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 style="margin-top:0; margin-bottom:0.75rem; font-size:1.25rem; line-height:1.35;">
                            <a href="<?php echo $p_url; ?>" style="color:var(--text-primary); text-decoration:none; transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-primary)'">
                                <?php echo $p_title; ?>
                            </a>
                        </h3>

                        <?php if (!empty($p_teaser)): ?>
                            <p style="color:var(--text-secondary); font-size:0.92rem; line-height:1.55; margin-bottom:1.25rem; flex:1;">
                                <?php echo $p_teaser; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($p_tags)): ?>
                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:1.25rem;">
                                <?php foreach ($p_tags as $t): ?>
                                    <span style="font-size:0.75rem; padding:2px 8px; border-radius:999px; background:rgba(0,0,0,0.04); color:var(--text-secondary);">
                                        #<?php echo htmlspecialchars($t); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:auto; padding-top:1rem; border-top:1px solid var(--border-color);">
                            <a href="<?php echo $p_url; ?>" class="btn btn-primary" style="width:100%; justify-content:center; padding:10px 16px; font-size:0.92rem;">
                                <?php echo LanguageManager::t('btn.read_more', 'Weiterlesen', $cur_lang ?? null); ?> <span class="material-icons" style="font-size:16px;">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
