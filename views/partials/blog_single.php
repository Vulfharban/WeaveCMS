<?php
/**
 * ============================================================================
 * WeaveCMS Frontend View: Einzelner Blog-Artikel (Leseansicht)
 * ============================================================================
 *
 * @var array $data
 * @var array $active_post
 * @var string $site_url
 */

$p_title = htmlspecialchars($active_post['title'] ?? 'Artikel');
$p_slug = htmlspecialchars($active_post['slug'] ?? '');
$p_date_raw = $active_post['date'] ?? '';
$p_date_formatted = !empty($p_date_raw) ? date('d. F Y', strtotime($p_date_raw)) : '';
$p_author = htmlspecialchars($active_post['author'] ?? ($data['texts']['contact_name'] ?? 'Redaktion'));
$p_img = !empty($active_post['image']) ? htmlspecialchars($active_post['image']) : '';
$p_teaser = htmlspecialchars($active_post['teaser'] ?? '');
$p_content_raw = $active_post['content'] ?? '';
$p_tags = $active_post['tags'] ?? [];

// Lesezeit schätzen (ca. 200 Wörter pro Minute)
$word_count = str_word_count(strip_tags($p_content_raw));
$read_time = max(1, ceil($word_count / 180));

// Markdown parsen
$p_content_html = MarkdownParser::parse($p_content_raw);

$current_article_url = $site_url . '?blog=' . urlencode($active_post['slug'] ?? '');
?>

<div class="blog-single-container" style="max-width: 820px; margin: 3rem auto 6rem auto; padding: 0 1.25rem;">
    <!-- Zurück-Button -->
    <div style="margin-bottom: 2rem;">
        <a href="./#blog" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:0.9rem; text-decoration:none;">
            <span class="material-icons" style="font-size:18px;">arrow_back</span> <?php echo LanguageManager::t('btn.back_overview', 'Zurück zur Übersicht', $cur_lang ?? null); ?>
        </a>
    </div>

    <article class="card" style="padding: 2.5rem 3rem; border-radius: var(--card-radius, 24px); overflow:hidden;">
        <!-- Metadaten Header -->
        <header style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
            <?php if (!empty($p_tags)): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1rem;">
                    <?php foreach ($p_tags as $t): ?>
                        <span style="font-size:0.8rem; font-weight:600; padding:3px 12px; border-radius:999px; background:rgba(46,98,72,0.1); color:var(--primary);">
                            #<?php echo htmlspecialchars($t); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h1 style="font-size: 2.2rem; line-height: 1.25; margin-bottom: 1rem; color: var(--text-primary);">
                <?php echo $p_title; ?>
            </h1>

            <div style="display:flex; align-items:center; gap: 1.25rem; flex-wrap:wrap; color: var(--text-secondary); font-size: 0.9rem;">
                <?php if ($p_date_formatted): ?>
                    <span style="display:flex; align-items:center; gap:5px;">
                        <span class="material-icons" style="font-size:16px; color:var(--accent);">calendar_today</span>
                        <?php echo $p_date_formatted; ?>
                    </span>
                <?php endif; ?>
                <?php if ($p_author): ?>
                    <span style="display:flex; align-items:center; gap:5px;">
                        <span class="material-icons" style="font-size:16px;">person</span>
                        <?php echo $p_author; ?>
                    </span>
                <?php endif; ?>
                <span style="display:flex; align-items:center; gap:5px;">
                    <span class="material-icons" style="font-size:16px;">schedule</span>
                    ca. <?php echo $read_time; ?> Min. Lesezeit
                </span>
            </div>
        </header>

        <!-- Beitragsbild -->
        <?php if (!empty($p_img)): ?>
            <div style="margin-bottom: 2.5rem; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06);">
                <img src="<?php echo $p_img; ?>" alt="<?php echo $p_title; ?>" style="width: 100%; max-height: 480px; object-fit: cover; display:block;">
            </div>
        <?php endif; ?>

        <!-- Teaser Lead-Text -->
        <?php if (!empty($p_teaser)): ?>
            <p style="font-size: 1.15rem; line-height: 1.7; color: var(--text-primary); font-weight: 500; margin-bottom: 2rem; border-left: 3px solid var(--primary); padding-left: 1.25rem; font-style: italic;">
                <?php echo $p_teaser; ?>
            </p>
        <?php endif; ?>

        <!-- Hauptinhalt (Markdown) -->
        <div class="blog-body" style="font-size: 1.05rem; line-height: 1.8; color: var(--text-primary);">
            <?php echo $p_content_html; ?>
        </div>

        <!-- Social Sharing & Teilen -->
        <div style="margin-top: 3.5rem; padding-top: 2rem; border-top: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem; display:flex; align-items:center; gap:6px;">
                <span class="material-icons" style="font-size:18px; color:var(--accent);">share</span> <?php echo LanguageManager::t('blog.share_post', 'Artikel teilen:', $cur_lang ?? null); ?>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($p_title . ' ' . $current_article_url); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.85rem; text-decoration:none;" title="Per WhatsApp teilen">
                    💬 WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_article_url); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.85rem; text-decoration:none;" title="Auf Facebook teilen">
                    📘 Facebook
                </a>
                <button type="button" class="btn btn-secondary" style="padding:6px 12px; font-size:0.85rem;" onclick="copyBlogLink(<?php echo json_encode($current_article_url); ?>)">
                    <span class="material-icons" style="font-size:16px;">link</span> <?php echo LanguageManager::t('toast.link_copied', 'Link kopieren', $cur_lang ?? null); ?>
                </button>
            </div>
        </div>
    </article>

    <!-- Weiteres Stöbern -->
    <div style="margin-top: 3rem; text-align: center;">
        <a href="./#blog" class="btn btn-primary" style="padding:12px 24px;">
            <span class="material-icons" style="font-size:18px;">arrow_back</span> Zurück zu allen Beiträgen
        </a>
    </div>
</div>

<script>
function copyBlogLink(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            if (typeof showToast === 'function') {
                showToast('Artikellink in Zwischenablage kopiert!');
            } else {
                alert('Artikellink kopiert!');
            }
        });
    } else {
        prompt('Artikellink kopieren:', url);
    }
}
</script>
