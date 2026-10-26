<?php
/**
 * ============================================================================
 * WeaveCMS Sektions-View: Downloads & Software (Phase 16)
 * ============================================================================
 * Modulare Software- & Download-Karten mit Versions-Badges, Plattform-Kennzeichnung,
 * Feature-Bulletpoints, Screenshots, flüssigem Changelog-Modal und manipulations-
 * geschütztem Download-Counter über api/download.php.
 *
 * @var array $data
 */

$downloads = $data['downloads'] ?? [];
$dl_title = !empty($data['texts']['downloads_title']) ? $data['texts']['downloads_title'] : 'Downloads &amp; Software';
$dl_subtitle = !empty($data['texts']['downloads_subtitle']) ? $data['texts']['downloads_subtitle'] : 'Entdecke unsere Software-Lösungen, praktische Werkzeuge, Dokumentationen und Leitfäden zum direkten Herunterladen.';

// Eindeutige Kategorien ermitteln für interaktiven Filter
$categories = [];
foreach ($downloads as $d) {
    $cat = trim($d['category'] ?? '');
    if (!empty($cat) && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
?>
<!-- Downloads & Software Section -->
<section id="downloads" style="margin-bottom: 5.5rem; position: relative;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h2 style="margin-bottom: 0.75rem; color: var(--accent);"><?php echo $dl_title; ?></h2>
        <?php if (!empty($dl_subtitle)): ?>
            <p style="color: var(--text-secondary); max-width: 680px; margin: 0 auto; line-height: 1.6;">
                <?php echo $dl_subtitle; ?>
            </p>
        <?php endif; ?>

        <?php if (count($categories) > 1): ?>
            <!-- Interaktive Kategorie-Filter-Buttons -->
            <div class="dl-filter-bar" style="display:inline-flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:1.5rem; background:rgba(0,0,0,0.03); padding:6px; border-radius:12px; border:1px solid var(--border-color);">
                <button type="button" class="dl-filter-btn active" onclick="filterDownloads('all', this)">
                    <?php echo htmlspecialchars(LanguageManager::t('btn.show_all', 'Alle anzeigen', $cur_lang ?? null)); ?> (<?php echo count($downloads); ?>)
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="dl-filter-btn" onclick="filterDownloads('<?php echo htmlspecialchars(addslashes($cat)); ?>', this)">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($downloads)): ?>
        <p style="text-align: center; color: var(--text-secondary); padding: 3rem 1rem;">
            <?php echo LanguageManager::t('downloads.no_items', 'Aktuell stehen keine Downloads zur Verfügung.', $cur_lang ?? null); ?>
        </p>
    <?php else: ?>
        <div id="downloads-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; max-width: 1100px; margin: 0 auto;">
            <?php foreach ($downloads as $d): ?>
                <?php 
                    $d_id = htmlspecialchars($d['id'] ?? '');
                    $d_title_raw = (!empty($cur_lang) && !empty($d['translations'][$cur_lang]['title'])) ? $d['translations'][$cur_lang]['title'] : ($d['title'] ?? 'Software');
                    $d_desc_raw = (!empty($cur_lang) && !empty($d['translations'][$cur_lang]['description'])) ? $d['translations'][$cur_lang]['description'] : ($d['description'] ?? '');
                    $d_log_raw = (!empty($cur_lang) && !empty($d['translations'][$cur_lang]['changelog'])) ? $d['translations'][$cur_lang]['changelog'] : ($d['changelog'] ?? '');
                    $d_features_raw = (!empty($cur_lang) && !empty($d['translations'][$cur_lang]['features'])) ? $d['translations'][$cur_lang]['features'] : ($d['features'] ?? []);
                    
                    $d_title = htmlspecialchars($d_title_raw);
                    $d_desc = htmlspecialchars($d_desc_raw);
                    $d_cat = htmlspecialchars($d['category'] ?? 'Software');
                    $d_version = htmlspecialchars($d['version'] ?? '');
                    $d_platform = htmlspecialchars($d['platform'] ?? '');
                    $d_license = htmlspecialchars($d['license'] ?? '');
                    $d_size = htmlspecialchars($d['file_size'] ?? '');
                    $d_clicks = (int)($d['download_count'] ?? 0);
                    $d_show_counter = ($d['show_counter'] !== false);
                    $d_changelog = trim($d_log_raw);
                    $d_features = is_array($d_features_raw) ? $d_features_raw : [];
                    $d_screenshots = is_array($d['screenshots'] ?? null) ? $d['screenshots'] : [];

                    // Passendes Icon ermitteln
                    $icon = 'folder_zip';
                    $catLower = strtolower($d_cat);
                    $platLower = strtolower($d_platform);
                    if (strpos($catLower, 'software') !== false || strpos($platLower, 'windows') !== false) {
                        $icon = 'terminal';
                    } elseif (strpos($catLower, 'tool') !== false || strpos($catLower, 'app') !== false) {
                        $icon = 'build';
                    } elseif (strpos($catLower, 'pdf') !== false || strpos($catLower, 'anleitung') !== false || strpos($catLower, 'flyer') !== false) {
                        $icon = 'picture_as_pdf';
                    }
                ?>
                <div class="card dl-card" data-category="<?php echo $d_cat; ?>" style="padding: 1.75rem; border-radius: 18px; display: flex; flex-direction: column; justify-content: space-between; gap: 1.25rem; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div>
                        <!-- Header mit Icon & Badges -->
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 1rem;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(46,98,72,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-icons" style="font-size: 26px;"><?php echo $icon; ?></span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end;">
                                <span style="font-size: 0.76rem; font-weight: 700; color: var(--primary); background: rgba(46,98,72,0.1); padding: 3px 8px; border-radius: 6px;">
                                    <?php echo $d_cat; ?>
                                </span>
                                <?php if (!empty($d_version)): ?>
                                    <span style="font-size: 0.76rem; font-weight: 700; color: #b26c39; background: rgba(178,108,57,0.12); padding: 3px 8px; border-radius: 6px;">
                                        <?php echo $d_version; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($d_platform)): ?>
                                    <span style="font-size: 0.74rem; font-weight: 600; color: var(--text-secondary); background: rgba(0,0,0,0.04); padding: 3px 8px; border-radius: 6px;">
                                        <?php echo $d_platform; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Titel & Beschreibung -->
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; color: var(--text-primary); font-weight: 700;">
                            <?php echo $d_title; ?>
                        </h3>

                        <?php if (!empty($d_desc)): ?>
                            <p style="margin: 0 0 1rem 0; font-size: 0.92rem; color: var(--text-secondary); line-height: 1.5;">
                                <?php echo $d_desc; ?>
                            </p>
                        <?php endif; ?>

                        <!-- Feature-Bulletpoints -->
                        <?php if (!empty($d_features)): ?>
                            <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem 0; font-size: 0.86rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 5px;">
                                <?php foreach ($d_features as $feat): ?>
                                    <li style="display: flex; align-items: flex-start; gap: 6px; line-height: 1.35;">
                                        <span class="material-icons" style="font-size: 16px; color: var(--primary); flex-shrink: 0; margin-top: 2px;">check_circle</span>
                                        <span><?php echo htmlspecialchars($feat); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Optionale Screenshot-Galerie -->
                        <?php if (!empty($d_screenshots)): ?>
                            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 1rem;">
                                <?php foreach ($d_screenshots as $sImg): ?>
                                    <?php $sClean = htmlspecialchars(trim($sImg)); ?>
                                    <img src="<?php echo $sClean; ?>" alt="Screenshot" style="height: 52px; width: 85px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); cursor: pointer; transition: transform 0.2s;" onclick="openLightbox('<?php echo $sClean; ?>')" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Meta-Hinweise (Größe, Lizenz) -->
                        <div style="font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if (!empty($d_size)): ?>
                                <span><span class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">data_usage</span> <?php echo $d_size; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($d_license)): ?>
                                <span><span class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">verified</span> <?php echo $d_license; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions & Buttons -->
                    <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if (!empty($d_changelog)): ?>
                                <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 4px;" onclick="openChangelogModal('<?php echo $d_id; ?>', '<?php echo htmlspecialchars(addslashes($d_title)); ?>')">
                                    <span class="material-icons" style="font-size: 15px;">history</span> Changelog
                                </button>
                                <!-- Versteckter Changelog-HTML-Container -->
                                <div id="raw-changelog-<?php echo $d_id; ?>" style="display: none;">
                                    <?php echo MarkdownParser::parse($d_changelog); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <a href="api/download.php?id=<?php echo urlencode($d['id'] ?? ''); ?>" target="_blank" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-icons" style="font-size: 18px;">file_download</span>
                            <span><?php echo LanguageManager::t('btn.download', 'Download', $cur_lang ?? null); ?><?php if ($d_show_counter): ?> (<?php echo number_format($d_clicks, 0, ',', '.'); ?>)<?php endif; ?></span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ──────────────── CHANGELOG MODAL ──────────────── -->
    <div id="changelog-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;" onclick="if(event.target === this) closeChangelogModal();">
        <div class="card" style="background: var(--bg-card); max-width: 640px; width: 100%; max-height: 85vh; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 20px 45px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden; animation: fadeInModal 0.25s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color);">
                <h3 id="changelog-modal-title" style="color: var(--primary); margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-icons">history</span> Versionshistorie
                </h3>
                <button type="button" onclick="closeChangelogModal()" style="background: none; border: none; font-size: 1.75rem; cursor: pointer; color: var(--text-secondary); line-height: 1;">&times;</button>
            </div>
            <div id="changelog-modal-content" style="padding: 1.75rem; overflow-y: auto; font-size: 0.92rem; line-height: 1.6; color: var(--text-primary);">
            </div>
            <div style="padding: 1rem 1.75rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeChangelogModal()"><?php echo htmlspecialchars(LanguageManager::t('btn.close', 'Schließen', $cur_lang ?? null)); ?></button>
            </div>
        </div>
    </div>
</section>

<script>
function filterDownloads(category, btn) {
    const cards = document.querySelectorAll('.dl-card');
    document.querySelectorAll('.dl-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    cards.forEach(card => {
        const cardCat = card.getAttribute('data-category');
        if (category === 'all' || cardCat === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function openChangelogModal(downloadId, title) {
    const contentEl = document.getElementById('raw-changelog-' + downloadId);
    const modal = document.getElementById('changelog-modal');
    const modalTitle = document.getElementById('changelog-modal-title');
    const modalContent = document.getElementById('changelog-modal-content');

    if (contentEl && modal && modalTitle && modalContent) {
        modalTitle.innerHTML = '<span class="material-icons">history</span> Changelog: ' + title;
        modalContent.innerHTML = contentEl.innerHTML;
        modal.style.display = 'flex';
    }
}

function closeChangelogModal() {
    const modal = document.getElementById('changelog-modal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeChangelogModal();
});
</script>
