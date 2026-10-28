<?php
/**
 * WeaveCMS – Admin Tab: Downloads & Software (Phase 16)
 * 
 * Bereitstellung von Software, Tools, PDFs und Dokumenten
 * mit Profi-Metadaten (Version, Plattform, Changelog, Screenshots)
 * und manipulationsgeschütztem Klick-Counter.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$downloads = $data['downloads'] ?? [];
$total_downloads = count($downloads);
$total_clicks = 0;
$software_count = 0;
$docs_count = 0;

foreach ($downloads as $d) {
    $total_clicks += (int)($d['download_count'] ?? 0);
    $cat = strtolower($d['category'] ?? '');
    if (strpos($cat, 'software') !== false || strpos($cat, 'tool') !== false || strpos($cat, 'app') !== false) {
        $software_count++;
    } else {
        $docs_count++;
    }
}
?>
        <!-- ──────────────── SECTION DOWNLOADS: SOFTWARE & DATEIEN ──────────────── -->
        <div id="tab-downloads" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">folder_zip</span> <?php echo __a('admin.downloads.title', 'Downloads & Software-Katalog verwalten'); ?>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        <?php echo __a('admin.downloads.desc', 'Stelle deinen Besuchern Software, Vorlagen, Dokumentationen oder Broschüren zum Download bereit.'); ?>
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.downloads.btn_new', '+ Neuen Download anlegen'); ?>
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.downloads.kpi_total', 'Downloads gesamt'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_downloads; ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--primary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.downloads.kpi_clicks', 'Aufrufe / Klicks'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top:4px;">⬇ <?php echo number_format($total_clicks, 0, ',', '.'); ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.downloads.kpi_software', 'Software / Tools'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $software_count; ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.downloads.kpi_docs', 'Dokumente / PDFs'); ?></div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $docs_count; ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'downloads';
                $current_tab = 'tab-downloads';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

<?php
$active_langs = LanguageManager::getActiveLanguages($data);
$primary_lang = LanguageManager::getPrimaryLanguage($data);
$all_languages = LanguageManager::getSupportedLanguages();
?>
            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="download-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="download-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">add_circle</span> <?php echo __a('admin.downloads.drawer_new', 'Neuen Download anlegen'); ?>
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> <?php echo __a('admin.downloads.drawer_close', 'Schließen'); ?>
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="download-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="download_form_action" value="add_download">
                    <input type="hidden" name="id" id="download_id" value="">

                    <!-- Gemeinsame Kategorie -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="download_category" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;"><?php echo __a('admin.downloads.field_category', 'Kategorie:'); ?></label>
                        <input type="text" id="download_category" name="category" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.placeholder_category', 'z. B. Software, Dokumente, Vorlagen')); ?>" value="Software">
                    </div>

                    <?php if (count($active_langs) > 1): ?>
                        <!-- Sprach-Tabs im Editor -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="font-weight:700; color:var(--text-primary); font-size:0.9rem; margin-bottom:0.5rem; display:block;"><?php echo __a('admin.downloads.lang_content', 'Inhalte auf'); ?>:</label>
                            <div style="display:flex; gap:6px; border-bottom:1px solid var(--border-color); padding-bottom:4px;" id="dl-lang-tab-bar">
                                <?php foreach ($active_langs as $idx => $al): 
                                    $is_prim = ($al === $primary_lang);
                                    $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                                ?>
                                    <button type="button" 
                                            class="dl-lang-tab-btn btn <?php echo $is_prim ? 'btn-primary' : 'btn-secondary'; ?>" 
                                            id="dl-btn-tab-<?php echo $al; ?>"
                                            onclick="switchDlLangTab('<?php echo $al; ?>')"
                                            style="padding: 5px 12px; font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                                        <span><?php echo $lang_info['flag']; ?></span>
                                        <span><?php echo htmlspecialchars($lang_info['name']); ?><?php echo $is_prim ? ' ' . __a('admin.downloads.primary_lang_suffix', '(Hauptsprache)') : ''; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab-Inhalt: Hauptsprache -->
                    <div id="dl-lang-content-<?php echo $primary_lang; ?>" class="dl-lang-content">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.downloads.field_title', 'Titel / Dateiname:'); ?> * <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span>
                            </label>
                            <input type="text" id="download_title" name="title" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.placeholder_title', 'z. B. WeaveCMS Starter-Kit')); ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                <?php echo __a('admin.downloads.field_desc', 'Beschreibung:'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">(<?php echo $all_languages[$primary_lang]['name'] ?? 'Hauptsprache'; ?>)</span>
                            </label>
                            <textarea id="download_desc" name="description" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.placeholder_desc', 'Worum handelt es sich? Kurze Übersicht über Inhalt und Nutzen...')); ?>"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_features" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                <?php echo __a('admin.downloads.field_features', 'Highlights / Key-Features (1 Feature pro Zeile):'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);"><?php echo __a('admin.downloads.features_hint', 'Zeilenumbruch für jeden Punkt. Werden als übersichtliche Liste mit Haken dargestellt.'); ?></span>
                            </label>
                            <textarea id="download_features" name="features" class="form-control" rows="3" placeholder="100% Offline-fähig&#10;Integrierter SQLite- &amp; JSON-Speicher&#10;DSGVO-konform ohne Cloud-Zwang"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_changelog" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                <?php echo __a('admin.downloads.field_changelog', 'Versionshinweise / Changelog:'); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);"><?php echo __a('admin.downloads.changelog_hint', 'Unterstützt Markdown (**fett**, Listen, Links etc.)'); ?></span>
                            </label>
                            <textarea id="download_changelog" name="changelog" class="form-control" rows="4" placeholder="### v1.2.0 (2026-10-22)&#10;- Neu: Modulare Captcha-Engine&#10;- Optimiert: Download-Klickcounter&#10;- Fix: Barrierefreie Themes"></textarea>
                        </div>
                    </div>

                    <?php foreach ($active_langs as $al): ?>
                        <?php if ($al === $primary_lang) continue; 
                            $lang_info = $all_languages[$al] ?? ['name' => strtoupper($al), 'flag' => '🌐'];
                        ?>
                        <div id="dl-lang-content-<?php echo $al; ?>" class="dl-lang-content" style="display:none;">
                            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                                ℹ️ <?php echo __a('admin.downloads.trans_info', 'Übersetzungen für aktivierte Sprachen:'); ?> <strong><?php echo $lang_info['flag'] . ' ' . htmlspecialchars($lang_info['name']); ?></strong>.
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="download_trans_<?php echo $al; ?>_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.downloads.field_title_trans', 'Titel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <input type="text" id="download_trans_<?php echo $al; ?>_title" name="trans_<?php echo $al; ?>_title" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.field_title_trans', 'Titel ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="download_trans_<?php echo $al; ?>_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">
                                    <?php echo __a('admin.downloads.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <textarea id="download_trans_<?php echo $al; ?>_desc" name="trans_<?php echo $al; ?>_description" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.field_desc_trans', 'Beschreibung ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="download_trans_<?php echo $al; ?>_features" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                    <?php echo __a('admin.downloads.field_features_trans', 'Features ({lang}, 1 pro Zeile):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <textarea id="download_trans_<?php echo $al; ?>_features" name="trans_<?php echo $al; ?>_features" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.field_features_trans', 'Features ({lang}, 1 pro Zeile):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="download_trans_<?php echo $al; ?>_changelog" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                    <?php echo __a('admin.downloads.field_changelog_trans', 'Changelog ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])]); ?>
                                </label>
                                <textarea id="download_trans_<?php echo $al; ?>_changelog" name="trans_<?php echo $al; ?>_changelog" class="form-control" rows="4" placeholder="<?php echo htmlspecialchars(__a('admin.downloads.field_changelog_trans', 'Changelog ({lang}):', null, ['lang' => htmlspecialchars($lang_info['name'])])); ?>"></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- 2. Quelle: Datei-Upload vs. Externer Release-Link -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.6rem; display:block;"><?php echo __a('admin.downloads.source_heading', 'Dateiquelle'); ?></label>
                        <div style="display:flex; gap:1.5rem; margin-bottom:1rem; flex-wrap:wrap;">
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
                                <input type="radio" name="file_source" value="upload" id="source_upload" checked onchange="toggleDownloadSource()">
                                <span class="material-icons" style="font-size:18px;">upload_file</span> <?php echo __a('admin.downloads.source_upload', 'Datei lokal hochladen'); ?>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
                                <input type="radio" name="file_source" value="external" id="source_external" onchange="toggleDownloadSource()">
                                <span class="material-icons" style="font-size:18px;">link</span> <?php echo __a('admin.downloads.source_external', 'Externer Link / Cloud-Speicher'); ?>
                            </label>
                        </div>

                        <div id="box_source_upload" class="form-group">
                            <label for="download_file" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;"><?php echo __a('admin.downloads.field_file', 'Datei auswählen:'); ?></label>
                            <input type="file" id="download_file" name="download_file" class="form-control">
                            <input type="hidden" id="download_existing_file" name="existing_file" value="">
                            <div id="download_current_file_info" style="display:none; font-size:0.85rem; color:var(--text-secondary); margin-top:6px;"></div>
                        </div>

                        <div id="box_source_external" class="grid-2" style="display:none; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="download_external_url" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;"><?php echo __a('admin.downloads.field_external_url', 'Direkte Download-URL / Link:'); ?> *</label>
                                <input type="url" id="download_external_url" name="external_url" class="form-control" placeholder="https://github.com/user/repo/releases/download/v1.0.0/app.zip">
                            </div>
                            <div class="form-group">
                                <label for="download_file_size" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;"><?php echo __a('admin.downloads.field_file_size', 'Angezeigte Dateigröße (z. B. 12.5 MB):'); ?></label>
                                <input type="text" id="download_file_size" name="file_size" class="form-control" placeholder="z. B. 14.5 MB">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Optionale Profi-Felder (Version, Plattform, Lizenz, Screenshots) -->
                    <div style="background:rgba(255,255,255,0.015); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <h4 style="color:var(--primary); margin:0 0 1rem 0; font-size:1.05rem; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:20px;">tune</span> <?php echo __a('admin.downloads.adv_metadata_heading', 'Erweiterte Metadaten (optional)'); ?>
                        </h4>

                        <div class="grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="download_version" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;"><?php echo __a('admin.downloads.field_version', 'Version:'); ?></label>
                                <input type="text" id="download_version" name="version" class="form-control" placeholder="z. B. v1.2.0">
                            </div>
                            <div class="form-group">
                                <label for="download_platform" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;"><?php echo __a('admin.downloads.field_platform', 'Plattform / Kompatibilität:'); ?></label>
                                <input type="text" id="download_platform" name="platform" class="form-control" placeholder="z. B. Windows 10/11, macOS, Linux">
                            </div>
                            <div class="form-group">
                                <label for="download_license" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;"><?php echo __a('admin.downloads.field_license', 'Lizenz:'); ?></label>
                                <input type="text" id="download_license" name="license" class="form-control" placeholder="z. B. Freeware, MIT, GPLv3">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_screenshots" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                <?php echo __a('admin.downloads.field_screenshots', 'Screenshots / Vorschaubilder (URLs, 1 pro Zeile):'); ?>
                            </label>
                            <input type="text" id="download_screenshots" name="screenshots" class="form-control" placeholder="/images/screenshot1.png, /images/screenshot2.png">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.9rem;">
                                <input type="checkbox" id="download_show_counter" name="show_counter" value="1" checked style="width:17px; height:17px;">
                                <?php echo __a('admin.downloads.show_counter_label', 'Download-Zähler öffentlich anzeigen'); ?>
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()"><?php echo __a('admin.downloads.btn_cancel', 'Abbrechen'); ?></button>
                        <button type="submit" class="btn btn-primary" id="download-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> <?php echo __a('admin.downloads.btn_save_new', 'Download anlegen'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── DOWNLOAD-LISTE ──────────────── -->
            <?php if (empty($downloads)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">folder_zip</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);"><?php echo __a('admin.downloads.empty_title', 'Noch keine Downloads hinterlegt'); ?></h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;"><?php echo __a('admin.downloads.empty_desc', 'Biete nützliche Begleitmaterialien, Formulare, Anleitungen oder Software an.'); ?></p>
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> <?php echo __a('admin.downloads.empty_btn', 'Ersten Download anlegen'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.85rem;">
                    <?php foreach ($downloads as $d): ?>
                        <?php 
                            $d_file = $d['file_path'] ?? '';
                            $d_ext_url = $d['external_url'] ?? '';
                            $d_source = $d['file_source'] ?? (!empty($d_ext_url) ? 'external' : 'upload');
                            $d_clicks = (int)($d['download_count'] ?? 0);
                            $d_version = htmlspecialchars($d['version'] ?? '');
                            $d_platform = htmlspecialchars($d['platform'] ?? '');
                            $d_category = htmlspecialchars($d['category'] ?? 'Software');
                            $d_license = htmlspecialchars($d['license'] ?? '');
                            $d_file_size = htmlspecialchars($d['file_size'] ?? '');

                            $icon = 'folder_zip';
                            if (stripos($d_category, 'software') !== false || stripos($d_platform, 'windows') !== false) {
                                $icon = 'terminal';
                            } elseif (stripos($d_file, '.pdf') !== false) {
                                $icon = 'picture_as_pdf';
                            }
                        ?>
                        <div class="card" style="padding:1.25rem 1.5rem; border-radius:14px; display:flex; justify-content:space-between; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:flex-start; gap:1.1rem; flex:1; min-width:300px;">
                                <div style="width:46px; height:46px; border-radius:12px; background:rgba(46,98,72,0.12); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                                    <span class="material-icons" style="font-size:24px;"><?php echo $icon; ?></span>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                        <h4 style="margin:0; font-size:1.1rem; color:var(--text-primary);">
                                            <?php echo htmlspecialchars($d['title'] ?? 'Download'); ?>
                                        </h4>
                                        <span style="font-size:0.75rem; font-weight:700; color:var(--primary); background:rgba(46,98,72,0.1); padding:2px 8px; border-radius:6px;">
                                            <?php echo $d_category; ?>
                                        </span>
                                        <?php if (!empty($d_version)): ?>
                                            <span style="font-size:0.75rem; font-weight:700; color:#b26c39; background:rgba(178,108,57,0.1); padding:2px 8px; border-radius:6px;">
                                                <?php echo $d_version; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($d_platform)): ?>
                                            <span style="font-size:0.75rem; font-weight:600; color:var(--text-secondary); background:rgba(0,0,0,0.04); padding:2px 8px; border-radius:6px;">
                                                <?php echo $d_platform; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (count($active_langs) > 1): ?>
                                            <?php foreach ($active_langs as $al): ?>
                                                <?php if ($al === $primary_lang) continue; ?>
                                                <?php if (!empty($d['translations'][$al]['title']) || !empty($d['translations'][$al]['description'])): 
                                                    $li = $all_languages[$al] ?? ['flag' => '🌐'];
                                                ?>
                                                    <span title="Übersetzung für <?php echo strtoupper($al); ?> gepflegt" style="font-size:0.75rem; background:rgba(16,185,129,0.12); color:var(--primary); border:1px solid rgba(16,185,129,0.25); padding:1px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:2px;">
                                                        <?php echo $li['flag']; ?> <?php echo strtoupper($al); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($d['description'])): ?>
                                        <div style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:6px; line-height:1.4;">
                                            <?php echo htmlspecialchars($d['description']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display:flex; align-items:center; gap:12px; font-size:0.8rem; color:var(--text-secondary); flex-wrap:wrap;">
                                        <span>
                                            <?php if ($d_source === 'external'): ?>
                                                <span class="material-icons" style="font-size:14px; vertical-align:text-bottom;">link</span> <?php echo __a('admin.downloads.label_external_link', 'Externer Link'); ?>
                                            <?php else: ?>
                                                <span class="material-icons" style="font-size:14px; vertical-align:text-bottom;">attachment</span> <?php echo __a('admin.downloads.label_local_file', 'Lokale Datei'); ?>
                                            <?php endif; ?>
                                            <?php if ($d_file_size): ?> &bull; <?php echo $d_file_size; ?><?php endif; ?>
                                            <?php if ($d_license): ?> &bull; <?php echo $d_license; ?><?php endif; ?>
                                        </span>
                                        <span style="font-weight:700; color:var(--primary); background:rgba(46,98,72,0.08); padding:2px 8px; border-radius:6px;">
                                            ⬇ <?php echo number_format($d_clicks, 0, ',', '.'); ?> <?php echo __a('admin.downloads.downloads_suffix', 'Downloads'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <a href="../api/download.php?id=<?php echo urlencode($d['id'] ?? ''); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;" title="<?php echo htmlspecialchars(__a('admin.downloads.btn_test', 'Testen')); ?>">
                                    <span class="material-icons" style="font-size:16px;">file_download</span> <?php echo __a('admin.downloads.btn_test', 'Testen'); ?>
                                </a>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.downloads.confirm_reset_counter', 'Möchtest du den Klickzähler wirklich auf 0 zurücksetzen?'), ENT_QUOTES, 'UTF-8'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="reset_download_counter">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; font-size:0.82rem;" title="<?php echo htmlspecialchars(__a('admin.downloads.btn_reset_counter', 'Zähler zurücksetzen')); ?>">
                                        <span class="material-icons" style="font-size:16px;">restart_alt</span> <?php echo __a('admin.downloads.btn_reset_counter', 'Reset'); ?>
                                    </button>
                                </form>

                                <button type="button" class="btn btn-secondary" onclick="editDownload(<?php echo htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> <?php echo __a('admin.downloads.btn_edit', 'Bearbeiten'); ?>
                                </button>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars(__a('admin.downloads.confirm_delete', 'Möchtest du diesen Download wirklich löschen?'), ENT_QUOTES, 'UTF-8'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_download">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="<?php echo htmlspecialchars(__a('admin.downloads.btn_delete_title', 'Download löschen')); ?>">
                                        <span class="material-icons" style="font-size:16px;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        function toggleDownloadSource() {
            const isExternal = document.getElementById('source_external').checked;
            document.getElementById('box_source_upload').style.display = isExternal ? 'none' : 'block';
            document.getElementById('box_source_external').style.display = isExternal ? 'grid' : 'none';
            if (isExternal) {
                document.getElementById('download_file').required = false;
                document.getElementById('download_external_url').required = true;
            } else {
                const isNew = document.getElementById('download_form_action').value === 'add_download';
                document.getElementById('download_file').required = isNew && !document.getElementById('download_existing_file').value;
                document.getElementById('download_external_url').required = false;
            }
        }

        const activeDlLangs = <?php echo json_encode($active_langs); ?>;
        const primaryDlLang = <?php echo json_encode($primary_lang); ?>;

        function switchDlLangTab(lang) {
            document.querySelectorAll('.dl-lang-content').forEach(el => el.style.display = 'none');
            const target = document.getElementById('dl-lang-content-' + lang);
            if (target) target.style.display = 'block';

            document.querySelectorAll('.dl-lang-tab-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            const activeBtn = document.getElementById('dl-btn-tab-' + lang);
            if (activeBtn) {
                activeBtn.classList.remove('btn-secondary');
                activeBtn.classList.add('btn-primary');
            }
        }

        function openDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">add_circle</span> ' + <?php echo json_encode(__a('admin.downloads.drawer_new', 'Neuen Download anlegen')); ?>;
            document.getElementById('download_form_action').value = 'add_download';
            document.getElementById('download_id').value = '';
            document.getElementById('download_title').value = '';
            document.getElementById('download_category').value = 'Software';
            document.getElementById('download_desc').value = '';
            document.getElementById('source_upload').checked = true;
            document.getElementById('download_external_url').value = '';
            document.getElementById('download_file_size').value = '';
            document.getElementById('download_version').value = '';
            document.getElementById('download_platform').value = '';
            document.getElementById('download_license').value = '';
            document.getElementById('download_features').value = '';
            document.getElementById('download_screenshots').value = '';
            document.getElementById('download_changelog').value = '';
            document.getElementById('download_show_counter').checked = true;
            document.getElementById('download_existing_file').value = '';
            document.getElementById('download_current_file_info').style.display = 'none';

            activeDlLangs.forEach(al => {
                if (al !== primaryDlLang) {
                    const tEl = document.getElementById('download_trans_' + al + '_title');
                    const dEl = document.getElementById('download_trans_' + al + '_desc');
                    const fEl = document.getElementById('download_trans_' + al + '_features');
                    const cEl = document.getElementById('download_trans_' + al + '_changelog');
                    if (tEl) tEl.value = '';
                    if (dEl) dEl.value = '';
                    if (fEl) fEl.value = '';
                    if (cEl) cEl.value = '';
                }
            });

            switchDlLangTab(primaryDlLang);
            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.downloads.btn_save_new', 'Download anlegen')); ?>;
            toggleDownloadSource();
            document.getElementById('download_title').focus();
        }

        function closeDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'none';
        }

        function editDownload(d) {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">edit</span> ' + <?php echo json_encode(__a('admin.downloads.drawer_edit', 'Download bearbeiten')); ?>;
            document.getElementById('download_form_action').value = 'edit_download';
            document.getElementById('download_id').value = d.id || '';
            document.getElementById('download_title').value = d.title || '';
            document.getElementById('download_category').value = d.category || 'Software';
            document.getElementById('download_desc').value = d.description || '';
            
            const isExt = d.file_source === 'external' || (!d.file_path && d.external_url);
            if (isExt) {
                document.getElementById('source_external').checked = true;
            } else {
                document.getElementById('source_upload').checked = true;
            }
            toggleDownloadSource();

            document.getElementById('download_external_url').value = d.external_url || '';
            document.getElementById('download_file_size').value = d.file_size || '';
            document.getElementById('download_version').value = d.version || '';
            document.getElementById('download_platform').value = d.platform || '';
            document.getElementById('download_license').value = d.license || '';
            
            if (Array.isArray(d.features)) {
                document.getElementById('download_features').value = d.features.join('\n');
            } else {
                document.getElementById('download_features').value = d.features || '';
            }

            if (Array.isArray(d.screenshots)) {
                document.getElementById('download_screenshots').value = d.screenshots.join(', ');
            } else {
                document.getElementById('download_screenshots').value = d.screenshots || '';
            }

            document.getElementById('download_changelog').value = d.changelog || '';
            document.getElementById('download_show_counter').checked = d.show_counter !== false;

            document.getElementById('download_existing_file').value = d.file_path || '';
            document.getElementById('download_file').required = false;

            if (d.file_path) {
                document.getElementById('download_current_file_info').style.display = 'block';
                document.getElementById('download_current_file_info').innerHTML = <?php echo json_encode(__a('admin.downloads.current_file_info', 'Aktuelle Datei: {file} ({size})', null, ['file' => '<code>\' + (d.file_path) + \'</code>', 'size' => 'Neue Datei hochladen zum Ersetzen'])); ?>;
            } else {
                document.getElementById('download_current_file_info').style.display = 'none';
            }

            const tr = d.translations || {};
            activeDlLangs.forEach(al => {
                if (al !== primaryDlLang) {
                    const tEl = document.getElementById('download_trans_' + al + '_title');
                    const dEl = document.getElementById('download_trans_' + al + '_desc');
                    const fEl = document.getElementById('download_trans_' + al + '_features');
                    const cEl = document.getElementById('download_trans_' + al + '_changelog');
                    if (tEl) tEl.value = tr[al]?.title || '';
                    if (dEl) dEl.value = tr[al]?.description || '';
                    if (fEl) {
                        const fVal = tr[al]?.features;
                        fEl.value = Array.isArray(fVal) ? fVal.join('\n') : (fVal || '');
                    }
                    if (cEl) cEl.value = tr[al]?.changelog || '';
                }
            });

            switchDlLangTab(primaryDlLang);
            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> ' + <?php echo json_encode(__a('admin.downloads.btn_save_changes', 'Änderungen speichern')); ?>;
            document.getElementById('download-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>

