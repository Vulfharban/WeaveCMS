<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Downloads & Dokumente (PDFs, Flyer, Anleitungen)
 * ============================================================================
 *
 * @var array $data
 */

$downloads = $data['downloads'] ?? [];
$dl_title = !empty($data['texts']['downloads_title']) ? $data['texts']['downloads_title'] : 'Downloads &amp; Dokumente';
$dl_subtitle = !empty($data['texts']['downloads_subtitle']) ? $data['texts']['downloads_subtitle'] : 'Hier findest du Informationsmaterial, Pflegeanleitungen und Kursflyer zum direkten Herunterladen.';
?>
<!-- Downloads Section -->
<section id="downloads" style="margin-bottom: 5rem;">
    <h2 style="margin-bottom: 1rem; text-align: center; color: var(--accent);"><?php echo $dl_title; ?></h2>
    <?php if (!empty($dl_subtitle)): ?>
        <p style="text-align: center; color: var(--text-secondary); max-width: 650px; margin: 0 auto 3rem auto;">
            <?php echo $dl_subtitle; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($downloads)): ?>
        <p style="text-align: center; color: var(--text-secondary);"><?php echo LanguageManager::t('downloads.no_items', 'Aktuell stehen keine Downloads zur Verfügung.', $cur_lang ?? null); ?></p>
    <?php else: ?>
        <div style="max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($downloads as $d): ?>
                <?php 
                    $d_title = htmlspecialchars($d['title'] ?? 'Dokument');
                    $d_desc = htmlspecialchars($d['description'] ?? '');
                    $d_file = htmlspecialchars($d['file_path'] ?? '');
                    $d_size = htmlspecialchars($d['file_size'] ?? '');
                    $d_ext = strtoupper(pathinfo($d_file, PATHINFO_EXTENSION) ?: 'PDF');
                ?>
                <div class="card" style="padding: 1.25rem 1.75rem; border-radius: 14px; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap;">
                    <div style="display:flex; align-items:center; gap: 1.25rem; flex:1; min-width: 260px;">
                        <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(239,68,68,0.1); color: #ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <span class="material-icons" style="font-size: 26px;">picture_as_pdf</span>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 4px 0; font-size: 1.05rem; color: var(--text-primary);"><?php echo $d_title; ?></h4>
                            <?php if (!empty($d_desc)): ?>
                                <p style="margin: 0 0 6px 0; font-size: 0.88rem; color: var(--text-secondary); line-height: 1.4;"><?php echo $d_desc; ?></p>
                            <?php endif; ?>
                            <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); background: rgba(0,0,0,0.04); padding: 2px 8px; border-radius: 4px;">
                                <?php echo $d_ext; ?><?php if ($d_size): ?> &bull; <?php echo $d_size; ?><?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <a href="<?php echo $d_file; ?>" target="_blank" download class="btn btn-primary" style="padding: 8px 18px; font-size: 0.88rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-icons" style="font-size: 17px;">file_download</span> <?php echo LanguageManager::t('btn.download', 'Download', $cur_lang ?? null); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
