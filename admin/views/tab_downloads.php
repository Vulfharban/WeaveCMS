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
                        <span class="material-icons" style="font-size:28px;">folder_zip</span> Downloads &amp; Software
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Verwalte Software, Tools, Dokumente und PDFs inklusive Versionen, Changelog, Screenshots und Download-Statistiken.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Neuer Download-Eintrag
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Einträge Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_downloads; ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--primary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Downloads Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top:4px;">⬇ <?php echo number_format($total_clicks, 0, ',', '.'); ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Software &amp; Tools</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $software_count; ?></div>
                </div>
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Dokumente &amp; PDFs</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $docs_count; ?></div>
                </div>
            </div>

            <?php 
                $section_id = 'downloads';
                $current_tab = 'tab-downloads';
                include __DIR__ . '/partials/section_nav_bar.php';
            ?>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="download-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="download-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">add_circle</span> Neuen Download bereitstellen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="download-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="download_form_action" value="add_download">
                    <input type="hidden" name="id" id="download_id" value="">

                    <!-- 1. Basisfelder -->
                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="download_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Titel / Software-Name *</label>
                            <input type="text" id="download_title" name="title" class="form-control" placeholder="z. B. WeaveTool Studio" required>
                        </div>
                        <div class="form-group">
                            <label for="download_category" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Kategorie</label>
                            <input type="text" id="download_category" name="category" class="form-control" placeholder="z. B. Software, Dokumentation, Tools, Kursflyer" value="Software">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="download_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Kurzbeschreibung</label>
                        <textarea id="download_desc" name="description" class="form-control" rows="2" placeholder="Kurze, ansprechende Erklärung der Software oder des Dokuments..."></textarea>
                    </div>

                    <!-- 2. Quelle: Datei-Upload vs. Externer Release-Link -->
                    <div style="background:rgba(0,0,0,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <label style="font-weight:700; color:var(--text-primary); margin-bottom:0.6rem; display:block;">Download-Quelle auswählen</label>
                        <div style="display:flex; gap:1.5rem; margin-bottom:1rem; flex-wrap:wrap;">
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
                                <input type="radio" name="file_source" value="upload" id="source_upload" checked onchange="toggleDownloadSource()">
                                <span class="material-icons" style="font-size:18px;">upload_file</span> Datei hochladen (PDF, ZIP, EXE etc.)
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
                                <input type="radio" name="file_source" value="external" id="source_external" onchange="toggleDownloadSource()">
                                <span class="material-icons" style="font-size:18px;">link</span> Externer Release-Link (z. B. GitHub, Cloud)
                            </label>
                        </div>

                        <div id="box_source_upload" class="form-group">
                            <label for="download_file" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;">Lokale Datei wählen</label>
                            <input type="file" id="download_file" name="download_file" class="form-control">
                            <input type="hidden" id="download_existing_file" name="existing_file" value="">
                            <div id="download_current_file_info" style="display:none; font-size:0.85rem; color:var(--text-secondary); margin-top:6px;"></div>
                        </div>

                        <div id="box_source_external" class="grid-2" style="display:none; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="download_external_url" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;">Externe Download-URL *</label>
                                <input type="url" id="download_external_url" name="external_url" class="form-control" placeholder="https://github.com/user/repo/releases/download/v1.0.0/app.zip">
                            </div>
                            <div class="form-group">
                                <label for="download_file_size" style="font-weight:600; font-size:0.9rem; margin-bottom:0.3rem; display:block;">Dateigröße (Optional)</label>
                                <input type="text" id="download_file_size" name="file_size" class="form-control" placeholder="z. B. 14.5 MB">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Optionale Profi-Felder (Version, Plattform, Lizenz, Features, Screenshots, Changelog) -->
                    <div style="background:rgba(255,255,255,0.015); border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <h4 style="color:var(--primary); margin:0 0 1rem 0; font-size:1.05rem; display:flex; align-items:center; gap:6px;">
                            <span class="material-icons" style="font-size:20px;">tune</span> Erweiterte Metadaten &amp; Software-Features (Optional)
                        </h4>

                        <div class="grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="form-group">
                                <label for="download_version" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">Versions-Badge</label>
                                <input type="text" id="download_version" name="version" class="form-control" placeholder="z. B. v1.2.0">
                            </div>
                            <div class="form-group">
                                <label for="download_platform" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">Plattform / OS</label>
                                <input type="text" id="download_platform" name="platform" class="form-control" placeholder="z. B. Windows 10/11, macOS, Linux">
                            </div>
                            <div class="form-group">
                                <label for="download_license" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">Lizenz</label>
                                <input type="text" id="download_license" name="license" class="form-control" placeholder="z. B. Freeware, MIT, GPLv3">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_features" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                Feature-Bulletpoints (Eine Eigenschaft je Zeile)
                            </label>
                            <textarea id="download_features" name="features" class="form-control" rows="3" placeholder="100% Offline-fähig&#10;Integrierter SQLite- &amp; JSON-Speicher&#10;DSGVO-konform ohne Cloud-Zwang"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_screenshots" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                Screenshot-Galerie (Bild-URLs oder Upload-Pfade, eine Zeile oder Kommagetrennt)
                            </label>
                            <input type="text" id="download_screenshots" name="screenshots" class="form-control" placeholder="/images/screenshot1.png, /images/screenshot2.png">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="download_changelog" style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.3rem;">
                                Versionshistorie / Changelog (Markdown unterstützt)
                            </label>
                            <textarea id="download_changelog" name="changelog" class="form-control" rows="4" placeholder="### v1.2.0 (2026-10-22)&#10;- Neu: Modulare Captcha-Engine&#10;- Optimiert: Download-Klickcounter&#10;- Fix: Barrierefreie Themes"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.9rem;">
                                <input type="checkbox" id="download_show_counter" name="show_counter" value="1" checked style="width:17px; height:17px;">
                                Download-Counter-Badge („⬇ X Downloads“) im Frontend auf der Karte anzeigen
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="download-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Download-Eintrag speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── DOWNLOAD-LISTE ──────────────── -->
            <?php if (empty($downloads)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">folder_zip</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine Downloads oder Software hinterlegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Stelle Software, Hilfsprogramme, Handbücher oder Kursflyer für deine Besucher bereit.</p>
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">add_circle</span> Ersten Eintrag anlegen
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
                                    </div>

                                    <?php if (!empty($d['description'])): ?>
                                        <div style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:6px; line-height:1.4;">
                                            <?php echo htmlspecialchars($d['description']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display:flex; align-items:center; gap:12px; font-size:0.8rem; color:var(--text-secondary); flex-wrap:wrap;">
                                        <span>
                                            <?php if ($d_source === 'external'): ?>
                                                <span class="material-icons" style="font-size:14px; vertical-align:text-bottom;">link</span> Externer Link
                                            <?php else: ?>
                                                <span class="material-icons" style="font-size:14px; vertical-align:text-bottom;">attachment</span> Lokale Datei
                                            <?php endif; ?>
                                            <?php if ($d_file_size): ?> &bull; <?php echo $d_file_size; ?><?php endif; ?>
                                            <?php if ($d_license): ?> &bull; <?php echo $d_license; ?><?php endif; ?>
                                        </span>
                                        <span style="font-weight:700; color:var(--primary); background:rgba(46,98,72,0.08); padding:2px 8px; border-radius:6px;">
                                            ⬇ <?php echo number_format($d_clicks, 0, ',', '.'); ?> Downloads
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <a href="../api/download.php?id=<?php echo urlencode($d['id'] ?? ''); ?>" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px;" title="Herunterladen / Testen">
                                    <span class="material-icons" style="font-size:16px;">file_download</span> Testen
                                </a>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du den Download-Zähler für diesen Eintrag wirklich auf 0 zurücksetzen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="reset_download_counter">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; font-size:0.82rem;" title="Zähler auf 0 zurücksetzen">
                                        <span class="material-icons" style="font-size:16px;">restart_alt</span> Reset
                                    </button>
                                </form>

                                <button type="button" class="btn btn-secondary" onclick="editDownload(<?php echo htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>

                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du diesen Download-Eintrag wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_download">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Eintrag löschen">
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

        function openDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">add_circle</span> Neuen Download bereitstellen';
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
            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Download-Eintrag speichern';
            toggleDownloadSource();
            document.getElementById('download_title').focus();
        }

        function closeDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'none';
        }

        function editDownload(d) {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">edit</span> Download-Eintrag bearbeiten';
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
                document.getElementById('download_current_file_info').innerHTML = 'Aktuelle Datei: <code>' + (d.file_path) + '</code> (Neue Datei hochladen zum Ersetzen)';
            } else {
                document.getElementById('download_current_file_info').style.display = 'none';
            }

            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('download-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>
