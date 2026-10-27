<?php
/**
 * WeaveCMS – Admin Tab: Zentrale Mediathek (Phase 22)
 *
 * Verwaltet alle hochgeladenen Bilder, Grafiken, Vektoren und Dokumente
 * mit moderner Kachel- und Listenansicht, Filterung, Suche, 1-Klick-Kopieren,
 * Alt-Text-Pflege und automatischer WebP/AVIF-Bildpipeline.
 *
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$allMedia = MediaManager::scanMedia('all');
$totalCount = count($allMedia);
$imageCount = 0;
$docCount = 0;
$vectorCount = 0;
$totalBytes = 0;
$webpCount = 0;

foreach ($allMedia as $m) {
    $totalBytes += $m['size_bytes'];
    if ($m['type'] === 'image') {
        $imageCount++;
        if (!empty($m['variants']['webp']) || $m['extension'] === 'webp') {
            $webpCount++;
        }
    } elseif ($m['type'] === 'document') {
        $docCount++;
    } elseif ($m['type'] === 'vector') {
        $vectorCount++;
    }
}
$totalSizeFormatted = MediaManager::formatBytes($totalBytes);
?>
<!-- ──────────────── SECTION MEDIA: ZENTRALE MEDIATHEK ──────────────── -->
<div id="tab-media" class="admin-section card" style="padding: 2.5rem;">
    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.25rem; margin-bottom: 2rem;">
        <div>
            <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:10px;">
                <span class="material-icons" style="font-size:32px;">perm_media</span> <?php echo __a('admin.media.title', 'Zentrale Mediathek & Asset-Manager'); ?>
            </h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0; max-width:800px; line-height:1.5;">
                <?php echo __a('admin.media.subtitle', 'Verwalte alle Bilder, Dokumente und Medien an einem zentralen Ort. Die integrierte Bild-Pipeline erzeugt automatisch komprimierte WebP- und AVIF-Varianten für maximale Ladegeschwindigkeit.'); ?>
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="btn btn-primary" onclick="triggerMediaFileInput()" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px;">
                <span class="material-icons" style="font-size:18px;">cloud_upload</span> <?php echo __a('admin.media.upload_files', 'Dateien hochladen'); ?>
            </button>
            <input type="file" id="media-native-input" multiple accept="image/*,.pdf,.zip,.7z,.txt,.csv" style="display:none;" onchange="handleMediaFilesSelected(this.files)">
        </div>
    </div>

    <!-- Kennzahlen-Karten -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:14px; padding: 1.1rem 1.3rem;">
            <div style="font-size: 0.78rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.media.stat_total_files', 'Dateien Gesamt'); ?></div>
            <div style="font-size: 1.9rem; font-weight: 800; color: var(--text-primary); margin-top:4px;" id="stat-total-count"><?php echo $totalCount; ?></div>
        </div>
        <div style="background: rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.25); border-radius:14px; padding: 1.1rem 1.3rem;">
            <div style="font-size: 0.78rem; color: #10b981; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.media.stat_disk_usage', 'Speicherbelegung'); ?></div>
            <div style="font-size: 1.9rem; font-weight: 800; color: #10b981; margin-top:4px;" id="stat-total-size"><?php echo $totalSizeFormatted; ?></div>
        </div>
        <div style="background: rgba(59, 130, 246, 0.05); border:1px solid rgba(59, 130, 246, 0.25); border-radius:14px; padding: 1.1rem 1.3rem;">
            <div style="font-size: 0.78rem; color: #3b82f6; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.media.stat_optimized', 'WebP / AVIF Optimiert'); ?></div>
            <div style="font-size: 1.9rem; font-weight: 800; color: #3b82f6; margin-top:4px;" id="stat-webp-count"><?php echo $webpCount; ?></div>
        </div>
        <div style="background: rgba(245, 158, 11, 0.05); border:1px solid rgba(245, 158, 11, 0.25); border-radius:14px; padding: 1.1rem 1.3rem;">
            <div style="font-size: 0.78rem; color: #f59e0b; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;"><?php echo __a('admin.media.stat_documents', 'Dokumente & PDFs'); ?></div>
            <div style="font-size: 1.9rem; font-weight: 800; color: #f59e0b; margin-top:4px;" id="stat-doc-count"><?php echo $docCount; ?></div>
        </div>
    </div>

    <!-- Drag & Drop Multi-Upload Zone -->
    <div id="media-dropzone" style="border: 2px dashed var(--border-color); border-radius: 16px; padding: 2.2rem 1.5rem; text-align: center; background: rgba(255,255,255,0.015); transition: all 0.25s ease; cursor: pointer; margin-bottom: 2rem;"
         onclick="triggerMediaFileInput()"
         ondragover="handleDragOver(event)"
         ondragleave="handleDragLeave(event)"
         ondrop="handleFileDrop(event)">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(46,98,72,0.12); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
            <span class="material-icons" style="font-size: 30px;">file_upload</span>
        </div>
        <h4 style="margin: 0 0 0.35rem 0; font-size: 1.1rem; color: var(--text-primary); font-weight: 700;">
            <?php echo __a('admin.media.drag_drop_title', 'Dateien hierher ziehen oder'); ?> <span style="color:var(--primary); text-decoration:underline;"><?php echo __a('admin.media.browse', 'durchsuchen'); ?></span>
        </h4>
        <p style="margin: 0; font-size: 0.88rem; color: var(--text-secondary);">
            <?php echo __a('admin.media.drag_drop_subtitle', 'Unterstützt Mehrfach-Upload: JPG, PNG, WebP, GIF, SVG, PDF, ZIP, 7Z (automatische Dateinamens-Bereinigung & WebP-Pipeline)'); ?>
        </p>
        <div id="media-upload-progress-box" style="display:none; max-width:400px; margin:1.25rem auto 0 auto;">
            <div style="display:flex; justify-content:space-between; font-size:0.82rem; color:var(--text-secondary); margin-bottom:4px;">
                <span id="media-progress-text"><?php echo __a('admin.media.uploading', 'Wird hochgeladen...'); ?></span>
                <span id="media-progress-pct">0%</span>
            </div>
            <div style="background:rgba(255,255,255,0.08); border-radius:999px; height:6px; overflow:hidden;">
                <div id="media-progress-bar" style="background:var(--primary); height:100%; width:0%; transition:width 0.2s ease;"></div>
            </div>
        </div>
    </div>

    <!-- Filter-, Such- & Ansichts-Leiste -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); padding:0.75rem 1.25rem; border-radius:12px;">
        <!-- Filter Tabs -->
        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary media-filter-btn" data-filter="all" onclick="setMediaFilter('all', this)" style="padding:6px 14px; font-size:0.84rem;">
                <?php echo __a('admin.media.filter_all', 'Alle'); ?> (<?php echo $totalCount; ?>)
            </button>
            <button type="button" class="btn btn-outline media-filter-btn" data-filter="images" onclick="setMediaFilter('images', this)" style="padding:6px 14px; font-size:0.84rem;">
                🖼️ <?php echo __a('admin.media.filter_images', 'Bilder'); ?> (<?php echo $imageCount; ?>)
            </button>
            <button type="button" class="btn btn-outline media-filter-btn" data-filter="documents" onclick="setMediaFilter('documents', this)" style="padding:6px 14px; font-size:0.84rem;">
                📄 <?php echo __a('admin.media.filter_documents', 'Dokumente & PDFs'); ?> (<?php echo $docCount; ?>)
            </button>
            <button type="button" class="btn btn-outline media-filter-btn" data-filter="vectors" onclick="setMediaFilter('vectors', this)" style="padding:6px 14px; font-size:0.84rem;">
                📐 <?php echo __a('admin.media.filter_vectors', 'Vektoren (SVG)'); ?> (<?php echo $vectorCount; ?>)
            </button>
        </div>

        <!-- Suche & Ansicht -->
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="position:relative; min-width:220px;">
                <span class="material-icons" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--text-secondary); pointer-events:none;">search</span>
                <input type="text" id="media-search-input" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.media.search_placeholder', 'Mediathek durchsuchen...')); ?>" style="padding-left:34px; font-size:0.85rem;" oninput="handleMediaSearch(this.value)">
            </div>
            
            <select id="media-sort-select" class="form-control" style="font-size:0.85rem; padding:6px 10px; width:auto;" onchange="handleMediaSort(this.value)">
                <option value="date_desc"><?php echo __a('admin.media.sort_date_desc', 'Neueste zuerst'); ?></option>
                <option value="date_asc"><?php echo __a('admin.media.sort_date_asc', 'Älteste zuerst'); ?></option>
                <option value="name_asc"><?php echo __a('admin.media.sort_name_asc', 'Dateiname (A–Z)'); ?></option>
                <option value="size_desc"><?php echo __a('admin.media.sort_size_desc', 'Dateigröße (absteigend)'); ?></option>
            </select>

            <div style="display:inline-flex; border:1px solid var(--border-color); border-radius:8px; overflow:hidden;">
                <button type="button" id="btn-view-grid" class="btn btn-primary" onclick="setMediaView('grid')" style="padding:6px 10px; border-radius:0; border:none;" title="<?php echo htmlspecialchars(__a('admin.media.view_grid', 'Kacheln')); ?>">
                    <span class="material-icons" style="font-size:18px;">grid_view</span>
                </button>
                <button type="button" id="btn-view-list" class="btn btn-outline" onclick="setMediaView('list')" style="padding:6px 10px; border-radius:0; border:none;" title="<?php echo htmlspecialchars(__a('admin.media.view_list', 'Liste')); ?>">
                    <span class="material-icons" style="font-size:18px;">view_list</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Kachel-Container (Grid) -->
    <div id="media-grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; margin-bottom: 2rem;">
        <!-- Dynamisch gerendert durch JavaScript -->
    </div>

    <!-- Listen-Container (Table) -->
    <div id="media-list-container" style="display:none; overflow-x:auto; margin-bottom: 2rem;">
        <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--border-color); color:var(--text-secondary); text-align:left;">
                    <th style="padding:10px 12px; width:60px;"><?php echo __a('admin.media.col_preview', 'Vorschau'); ?></th>
                    <th style="padding:10px 12px;"><?php echo __a('admin.media.col_filename', 'Dateiname & Alt-Text'); ?></th>
                    <th style="padding:10px 12px;"><?php echo __a('admin.media.col_type', 'Typ'); ?></th>
                    <th style="padding:10px 12px;"><?php echo __a('admin.media.col_dimensions', 'Dimensionen'); ?></th>
                    <th style="padding:10px 12px;"><?php echo __a('admin.media.col_size', 'Größe'); ?></th>
                    <th style="padding:10px 12px;"><?php echo __a('admin.media.col_date', 'Datum'); ?></th>
                    <th style="padding:10px 12px; text-align:right;"><?php echo __a('admin.media.col_actions', 'Aktionen'); ?></th>
                </tr>
            </thead>
            <tbody id="media-list-tbody">
                <!-- Dynamisch gerendert durch JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Empty State -->
    <div id="media-empty-state" style="display:none; text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
        <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">perm_media</span>
        <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);"><?php echo __a('admin.media.empty', 'Keine Medien gefunden.'); ?></h3>
        <p style="color:var(--text-secondary); font-size:0.92rem; margin:0 0 1rem 0;"><?php echo __a('admin.media.subtitle', 'Ziehe Dateien in den Upload-Bereich oder passe deine Suchkriterien an.'); ?></p>
    </div>
</div>

<!-- ======================================================================
     MODAL: MEDIEN-DETAILS, ALT-TEXT & CODE-SNIPPETS (PHASE 22)
     ====================================================================== -->
<div id="modal-media-detail" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:3300; align-items:center; justify-content:center; backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);" onclick="if(event.target===this) closeMediaDetailModal();">
    <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:18px; max-width:860px; width:calc(100% - 2rem); max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,0.45); overflow:hidden;">
        <!-- Header -->
        <div style="padding:1.1rem 1.6rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
            <h3 style="margin:0; color:var(--primary); font-size:1.15rem; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="font-size:22px;">photo_size_select_actual</span>
                <span id="detail-modal-filename" style="color:var(--text-primary); font-weight:700; word-break:break-all;"></span>
            </h3>
            <button type="button" class="btn btn-secondary" onclick="closeMediaDetailModal()" style="padding:4px 10px; font-size:0.8rem;">
                <span class="material-icons" style="font-size:16px;">close</span>
            </button>
        </div>

        <!-- Body (Zweispalter) -->
        <div style="padding:1.5rem 1.6rem; overflow-y:auto; flex:1; display:grid; grid-template-columns: minmax(240px, 340px) 1fr; gap:1.75rem; align-items:start;">
            <!-- Linke Spalte: Vorschau -->
            <div>
                <div style="background:#0f172a; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; min-height:220px; max-height:360px; margin-bottom:1rem; position:relative;">
                    <img id="detail-modal-img" src="" style="max-width:100%; max-height:360px; object-fit:contain; display:block;">
                    <div id="detail-modal-icon-placeholder" style="display:none; font-size:64px; color:var(--text-secondary); text-align:center; padding:2rem;">
                        <span class="material-icons" style="font-size:64px;">description</span>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; gap:8px;">
                    <a id="detail-modal-direct-link" href="#" target="_blank" class="btn btn-secondary" style="flex:1; text-align:center; padding:6px 10px; font-size:0.8rem; text-decoration:none;">
                        <span class="material-icons" style="font-size:15px; vertical-align:middle;">open_in_new</span> <?php echo __a('admin.media.open_original', 'Original öffnen'); ?>
                    </a>
                    <button type="button" class="btn btn-danger" onclick="deleteCurrentMediaItem()" style="padding:6px 12px; font-size:0.8rem; background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);">
                        <span class="material-icons" style="font-size:15px; vertical-align:middle;">delete</span> <?php echo __a('admin.btn.delete', 'Löschen'); ?>
                    </button>
                </div>
            </div>

            <!-- Rechte Spalte: Metadaten & Snippets -->
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                <!-- Technische Kennzahlen -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:10px; padding:10px 14px; font-size:0.83rem; color:var(--text-secondary); display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                    <div><strong><?php echo __a('admin.media.col_size', 'Größe'); ?>:</strong> <span id="detail-modal-size">-</span></div>
                    <div><strong><?php echo __a('admin.media.col_dimensions', 'Dimensionen'); ?>:</strong> <span id="detail-modal-dims">-</span></div>
                    <div><strong><?php echo __a('admin.media.col_type', 'Typ'); ?>:</strong> <span id="detail-modal-type">-</span></div>
                    <div><strong><?php echo __a('admin.media.col_date', 'Datum'); ?>:</strong> <span id="detail-modal-date">-</span></div>
                </div>

                <!-- Alt-Text & Titel Editor -->
                <div>
                    <label for="detail-modal-alt" style="font-weight:700; color:var(--text-primary); font-size:0.86rem; display:block; margin-bottom:4px;">
                        <?php echo __a('admin.media.alt_label', 'Alt-Text (Bildbeschreibung für Barrierefreiheit & Google)'); ?>
                    </label>
                    <input type="text" id="detail-modal-alt" class="form-control" placeholder="<?php echo htmlspecialchars(__a('admin.media.alt_placeholder', 'Beschreibe kurz den Bildinhalt für Screenreader...')); ?>" style="font-size:0.88rem; margin-bottom:8px;">
                    <div style="display:flex; justify-content:flex-end;">
                        <button type="button" class="btn btn-primary" onclick="saveCurrentMediaMeta()" style="padding:5px 12px; font-size:0.8rem;">
                            <span class="material-icons" style="font-size:14px; vertical-align:middle;">save</span> <?php echo __a('admin.media.save_meta', 'Metadaten speichern'); ?>
                        </button>
                    </div>
                </div>

                <!-- 1-Klick Code-Snippets -->
                <div style="border-top:1px solid var(--border-color); padding-top:1rem;">
                    <div style="font-weight:700; color:var(--text-primary); font-size:0.86rem; margin-bottom:8px;">1-Klick Code-Kopieren:</div>
                    
                    <div style="margin-bottom:8px;">
                        <label style="font-size:0.75rem; color:var(--text-secondary); display:block; margin-bottom:2px;">Web-URL:</label>
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="detail-snippet-url" class="form-control" readonly style="font-size:0.8rem; font-family:monospace; background:rgba(0,0,0,0.15);">
                            <button type="button" class="btn btn-secondary" onclick="copySnippet('detail-snippet-url')" style="padding:4px 10px; font-size:0.8rem;"><?php echo __a('admin.media.copy_url', 'Kopieren'); ?></button>
                        </div>
                    </div>

                    <div style="margin-bottom:8px;">
                        <label style="font-size:0.75rem; color:var(--text-secondary); display:block; margin-bottom:2px;">Markdown-Tag:</label>
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="detail-snippet-md" class="form-control" readonly style="font-size:0.8rem; font-family:monospace; background:rgba(0,0,0,0.15);">
                            <button type="button" class="btn btn-secondary" onclick="copySnippet('detail-snippet-md')" style="padding:4px 10px; font-size:0.8rem;"><?php echo __a('admin.media.copy_markdown', 'Kopieren'); ?></button>
                        </div>
                    </div>

                    <div id="detail-picture-snippet-box">
                        <label style="font-size:0.75rem; color:var(--text-secondary); display:block; margin-bottom:2px;">Responsive &lt;picture&gt; (AVIF &amp; WebP):</label>
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="detail-snippet-picture" class="form-control" readonly style="font-size:0.8rem; font-family:monospace; background:rgba(0,0,0,0.15);">
                            <button type="button" class="btn btn-secondary" onclick="copySnippet('detail-snippet-picture')" style="padding:4px 10px; font-size:0.8rem;"><?php echo __a('admin.media.copy_picture', 'Kopieren'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:0.9rem 1.6rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; background:rgba(255,255,255,0.02);">
            <button type="button" class="btn btn-secondary" onclick="closeMediaDetailModal()"><?php echo __a('admin.btn.close', 'Schließen'); ?></button>
        </div>
    </div>
</div>

<script>
let MEDIA_ITEMS = <?php echo json_encode($allMedia, JSON_UNESCAPED_UNICODE); ?>;
let CURRENT_MEDIA_FILTER = 'all';
let CURRENT_MEDIA_SEARCH = '';
let CURRENT_MEDIA_SORT = 'date_desc';
let CURRENT_MEDIA_VIEW = 'grid';
let ACTIVE_DETAIL_ITEM = null;

function triggerMediaFileInput() {
    document.getElementById('media-native-input').click();
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('media-dropzone').style.borderColor = 'var(--primary)';
    document.getElementById('media-dropzone').style.background = 'rgba(46,98,72,0.08)';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('media-dropzone').style.borderColor = 'var(--border-color)';
    document.getElementById('media-dropzone').style.background = 'rgba(255,255,255,0.015)';
}

function handleFileDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    handleDragLeave(e);
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        handleMediaFilesSelected(e.dataTransfer.files);
    }
}

function handleMediaFilesSelected(files) {
    if (!files || files.length === 0) return;
    const inp = document.getElementById('media-native-input');
    if (inp) inp.value = '';
    uploadMediaFiles(files);
}

function uploadMediaFiles(files) {
    const progressBox = document.getElementById('media-upload-progress-box');
    const progressBar = document.getElementById('media-progress-bar');
    const progressText = document.getElementById('media-progress-text');
    const progressPct = document.getElementById('media-progress-pct');

    progressBox.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = files.length + ' Datei(en) werden verarbeitet...';
    progressPct.textContent = '0%';

    const formData = new FormData();
    formData.append('action', 'upload_media');
    formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php', true);

    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
        }
    };

    xhr.onload = function() {
        progressBox.style.display = 'none';
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success) {
                    showToast('Erfolgreich hochgeladen: ' + (res.uploaded ? res.uploaded.length : 1) + ' Datei(en)');
                    reloadMediaData();
                } else {
                    alert('Upload-Fehler: ' + (res.errors ? res.errors.join('\n') : (res.message || 'Fehler beim Upload')));
                }
            } catch (err) {
                alert('Server-Antwort konnte nicht verarbeitet werden: ' + err);
            }
        } else {
            alert('Upload fehlgeschlagen (HTTP ' + xhr.status + ')');
        }
    };

    xhr.onerror = function() {
        progressBox.style.display = 'none';
        alert('Netzwerkfehler beim Upload');
    };

    xhr.send(formData);
}

function reloadMediaData() {
    fetch('index.php?action=get_media&filter=' + encodeURIComponent(CURRENT_MEDIA_FILTER) + '&sort=' + encodeURIComponent(CURRENT_MEDIA_SORT) + '&search=' + encodeURIComponent(CURRENT_MEDIA_SEARCH))
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                MEDIA_ITEMS = res.items || [];
                renderMediaItems();
                updateStats(res.items);
            }
        })
        .catch(err => console.error(err));
}

function updateStats(items) {
    let bytes = 0;
    let webp = 0;
    let doc = 0;
    items.forEach(i => {
        bytes += i.size_bytes;
        if (i.type === 'image' && (i.variants.webp || i.extension === 'webp')) webp++;
        if (i.type === 'document') doc++;
    });
    document.getElementById('stat-total-count').textContent = items.length;
    document.getElementById('stat-total-size').textContent = formatBytesJs(bytes);
    document.getElementById('stat-webp-count').textContent = webp;
    document.getElementById('stat-doc-count').textContent = doc;
}

function formatBytesJs(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function setMediaFilter(filter, btn) {
    CURRENT_MEDIA_FILTER = filter;
    document.querySelectorAll('.media-filter-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline');
    });
    if (btn) {
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary');
    }
    renderMediaItems();
}

function handleMediaSearch(val) {
    CURRENT_MEDIA_SEARCH = val.trim();
    renderMediaItems();
}

function handleMediaSort(val) {
    CURRENT_MEDIA_SORT = val;
    sortMediaItems();
    renderMediaItems();
}

function sortMediaItems() {
    MEDIA_ITEMS.sort((a, b) => {
        if (CURRENT_MEDIA_SORT === 'date_desc') return b.mtime - a.mtime;
        if (CURRENT_MEDIA_SORT === 'date_asc') return a.mtime - b.mtime;
        if (CURRENT_MEDIA_SORT === 'name_asc') return a.filename.localeCompare(b.filename);
        if (CURRENT_MEDIA_SORT === 'size_desc') return b.size_bytes - a.size_bytes;
        return 0;
    });
}

function setMediaView(view) {
    CURRENT_MEDIA_VIEW = view;
    const btnGrid = document.getElementById('btn-view-grid');
    const btnList = document.getElementById('btn-view-list');
    const gridBox = document.getElementById('media-grid-container');
    const listBox = document.getElementById('media-list-container');

    if (view === 'grid') {
        btnGrid.classList.remove('btn-outline');
        btnGrid.classList.add('btn-primary');
        btnList.classList.remove('btn-primary');
        btnList.classList.add('btn-outline');
        gridBox.style.display = 'grid';
        listBox.style.display = 'none';
    } else {
        btnList.classList.remove('btn-outline');
        btnList.classList.add('btn-primary');
        btnGrid.classList.remove('btn-primary');
        btnGrid.classList.add('btn-outline');
        gridBox.style.display = 'none';
        listBox.style.display = 'block';
    }
}

function getFilteredMediaItems() {
    return MEDIA_ITEMS.filter(m => {
        if (CURRENT_MEDIA_FILTER === 'images' && m.type !== 'image') return false;
        if (CURRENT_MEDIA_FILTER === 'documents' && m.type !== 'document') return false;
        if (CURRENT_MEDIA_FILTER === 'vectors' && m.type !== 'vector') return false;

        if (CURRENT_MEDIA_SEARCH) {
            const q = CURRENT_MEDIA_SEARCH.toLowerCase();
            const match = m.filename.toLowerCase().includes(q) || (m.alt && m.alt.toLowerCase().includes(q)) || (m.title && m.title.toLowerCase().includes(q));
            if (!match) return false;
        }
        return true;
    });
}

function renderMediaItems() {
    const items = getFilteredMediaItems();
    const gridBox = document.getElementById('media-grid-container');
    const listBody = document.getElementById('media-list-tbody');
    const emptyState = document.getElementById('media-empty-state');

    gridBox.innerHTML = '';
    listBody.innerHTML = '';

    if (items.length === 0) {
        emptyState.style.display = 'block';
        return;
    }
    emptyState.style.display = 'none';

    items.forEach(m => {
        // --- 1. Grid Card ---
        const card = document.createElement('div');
        card.style.cssText = 'background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; transition:transform 0.15s ease, box-shadow 0.15s ease; position:relative;';
        card.onmouseenter = () => { card.style.transform = 'translateY(-2px)'; card.style.boxShadow = '0 8px 20px rgba(0,0,0,0.2)'; };
        card.onmouseleave = () => { card.style.transform = 'translateY(0)'; card.style.boxShadow = 'none'; };

        // Thumbnail / Visual
        let thumbHtml = '';
        if (m.type === 'image' || m.type === 'vector') {
            thumbHtml = `<div style="height:140px; background:#0f172a; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; cursor:pointer;" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')">
                <img src="../${m.rel_url}" alt="${escapeHtml(m.alt || '')}" style="width:100%; height:100%; object-fit:cover;">
                <span style="position:absolute; bottom:6px; right:6px; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); color:#fff; font-size:0.68rem; font-weight:700; padding:2px 6px; border-radius:4px; text-transform:uppercase;">${m.extension}</span>
            </div>`;
        } else {
            thumbHtml = `<div style="height:140px; background:rgba(255,255,255,0.03); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; cursor:pointer;" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')">
                <span class="material-icons" style="font-size:42px; color:var(--text-secondary); opacity:0.8;">description</span>
                <span style="background:rgba(255,255,255,0.08); color:var(--text-primary); font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:4px; text-transform:uppercase;">${m.extension}</span>
            </div>`;
        }

        // Card Details
        const dimsText = m.dimensions ? `${m.dimensions.width}×${m.dimensions.height}` : '';
        const cardBody = `
            ${thumbHtml}
            <div style="padding:10px 12px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div style="font-weight:700; font-size:0.84rem; color:var(--text-primary); margin-bottom:3px; word-break:break-all; line-height:1.3;" title="${escapeHtml(m.filename)}">
                        ${escapeHtml(m.filename)}
                    </div>
                    <div style="font-size:0.75rem; color:var(--text-secondary); display:flex; justify-content:space-between; align-items:center;">
                        <span>${m.size_formatted}</span>
                        ${dimsText ? `<span>${dimsText} px</span>` : ''}
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; gap:4px; margin-top:10px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn btn-secondary" onclick="copyTextDirect('${m.web_url}', 'URL kopiert!')" style="padding:4px 8px; font-size:0.75rem; display:inline-flex; align-items:center; gap:3px;" title="Web-URL in Zwischenablage kopieren">
                        <span class="material-icons" style="font-size:13px;">link</span> URL
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="copyTextDirect('![${escapeJsStr(m.alt || m.filename)}](${m.web_url})', 'Markdown kopiert!')" style="padding:4px 8px; font-size:0.75rem; display:inline-flex; align-items:center; gap:3px;" title="Markdown-Tag kopieren">
                        <span class="material-icons" style="font-size:13px;">code</span> MD
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')" style="padding:4px 8px; font-size:0.75rem;" title="Details &amp; Alt-Text bearbeiten">
                        <span class="material-icons" style="font-size:13px;">tune</span>
                    </button>
                </div>
            </div>
        `;
        card.innerHTML = cardBody;
        gridBox.appendChild(card);

        // --- 2. List Table Row ---
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom:1px solid rgba(255,255,255,0.05);';
        tr.innerHTML = `
            <td style="padding:8px 12px;">
                <div style="width:40px; height:40px; border-radius:6px; overflow:hidden; background:#0f172a; display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')">
                    ${(m.type === 'image' || m.type === 'vector') ? `<img src="../${m.rel_url}" style="width:100%; height:100%; object-fit:cover;">` : `<span class="material-icons" style="font-size:22px; color:var(--text-secondary);">description</span>`}
                </div>
            </td>
            <td style="padding:8px 12px; font-weight:600; color:var(--text-primary); cursor:pointer;" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')">
                ${escapeHtml(m.filename)}
                ${m.alt ? `<div style="font-size:0.75rem; font-weight:normal; color:var(--text-secondary); margin-top:2px;">Alt: <em>${escapeHtml(m.alt)}</em></div>` : ''}
            </td>
            <td style="padding:8px 12px; text-transform:uppercase; font-size:0.78rem; color:var(--text-secondary);">${m.extension}</td>
            <td style="padding:8px 12px; font-size:0.8rem; color:var(--text-secondary);">${dimsText ? dimsText + ' px' : '-'}</td>
            <td style="padding:8px 12px; font-size:0.8rem; color:var(--text-secondary);">${m.size_formatted}</td>
            <td style="padding:8px 12px; font-size:0.8rem; color:var(--text-secondary);">${m.date_formatted}</td>
            <td style="padding:8px 12px; text-align:right;">
                <button type="button" class="btn btn-secondary" onclick="copyTextDirect('${m.web_url}', 'URL kopiert!')" style="padding:4px 8px; font-size:0.75rem;">URL</button>
                <button type="button" class="btn btn-secondary" onclick="copyTextDirect('![${escapeJsStr(m.alt || m.filename)}](${m.web_url})', 'Markdown kopiert!')" style="padding:4px 8px; font-size:0.75rem;">MD</button>
                <button type="button" class="btn btn-secondary" onclick="openMediaDetailModal('${escapeJsStr(m.filename)}')" style="padding:4px 8px; font-size:0.75rem;">Details</button>
            </td>
        `;
        listBody.appendChild(tr);
    });
}

function openMediaDetailModal(filename) {
    const item = MEDIA_ITEMS.find(i => i.filename === filename);
    if (!item) return;
    ACTIVE_DETAIL_ITEM = item;

    document.getElementById('detail-modal-filename').textContent = item.filename;
    document.getElementById('detail-modal-size').textContent = item.size_formatted;
    document.getElementById('detail-modal-dims').textContent = item.dimensions ? `${item.dimensions.width} × ${item.dimensions.height} px` : 'n/a';
    document.getElementById('detail-modal-type').textContent = item.extension.toUpperCase();
    document.getElementById('detail-modal-date').textContent = item.date_formatted;
    document.getElementById('detail-modal-alt').value = item.alt || '';

    const imgEl = document.getElementById('detail-modal-img');
    const iconEl = document.getElementById('detail-modal-icon-placeholder');
    if (item.type === 'image' || item.type === 'vector') {
        imgEl.src = '../' + item.rel_url;
        imgEl.style.display = 'block';
        iconEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        iconEl.style.display = 'block';
    }

    document.getElementById('detail-modal-direct-link').href = '../' + item.rel_url;

    // Snippets
    document.getElementById('detail-snippet-url').value = item.web_url;
    document.getElementById('detail-snippet-md').value = `![${item.alt || item.filename}](${item.web_url})`;

    const picBox = document.getElementById('detail-picture-snippet-box');
    if (item.type === 'image') {
        picBox.style.display = 'block';
        const webpUrl = item.variants.webp ? '/' + item.variants.webp : '';
        const avifUrl = item.variants.avif ? '/' + item.variants.avif : '';
        let picCode = '<picture>';
        if (avifUrl) picCode += `<source type="image/avif" srcset="${avifUrl}">`;
        if (webpUrl) picCode += `<source type="image/webp" srcset="${webpUrl}">`;
        picCode += `<img src="${item.web_url}" alt="${item.alt || item.filename}" loading="lazy"></picture>`;
        document.getElementById('detail-snippet-picture').value = picCode;
    } else {
        picBox.style.display = 'none';
    }

    const modal = document.getElementById('modal-media-detail');
    modal.style.display = 'flex';
}

function closeMediaDetailModal() {
    document.getElementById('modal-media-detail').style.display = 'none';
    ACTIVE_DETAIL_ITEM = null;
}

function copySnippet(elementId) {
    const input = document.getElementById(elementId);
    if (!input) return;
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('In die Zwischenablage kopiert!');
    }).catch(e => {
        document.execCommand('copy');
        showToast('In die Zwischenablage kopiert!');
    });
}

function copyTextDirect(text, toastMsg) {
    navigator.clipboard.writeText(text).then(() => {
        showToast(toastMsg || 'Kopiert!');
    }).catch(() => {
        showToast('Fehler beim Kopieren');
    });
}

function saveCurrentMediaMeta() {
    if (!ACTIVE_DETAIL_ITEM) return;
    const newAlt = document.getElementById('detail-modal-alt').value.trim();

    const formData = new URLSearchParams();
    formData.append('action', 'update_media_meta');
    formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
    formData.append('filename', ACTIVE_DETAIL_ITEM.filename);
    formData.append('alt', newAlt);

    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            ACTIVE_DETAIL_ITEM.alt = newAlt;
            showToast('Alt-Text erfolgreich gespeichert!');
            document.getElementById('detail-snippet-md').value = `![${newAlt || ACTIVE_DETAIL_ITEM.filename}](${ACTIVE_DETAIL_ITEM.web_url})`;
            renderMediaItems();
        } else {
            alert('Fehler beim Speichern');
        }
    })
    .catch(err => alert('Fehler: ' + err));
}

function deleteCurrentMediaItem() {
    if (!ACTIVE_DETAIL_ITEM) return;
    if (!confirm('Möchtest du die Datei „' + ACTIVE_DETAIL_ITEM.filename + '“ und alle zugehörigen WebP/AVIF-Varianten wirklich unwiderruflich löschen?')) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'delete_media');
    formData.append('csrf_token', <?php echo json_encode($csrf_token); ?>);
    formData.append('filename', ACTIVE_DETAIL_ITEM.filename);

    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeMediaDetailModal();
            showToast('Medium erfolgreich gelöscht!');
            reloadMediaData();
        } else {
            alert('Löschen fehlgeschlagen: ' + (res.message || 'Unbekannter Fehler'));
        }
    })
    .catch(err => alert('Fehler: ' + err));
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escapeJsStr(str) {
    return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

// Initialer Render
document.addEventListener('DOMContentLoaded', function() {
    renderMediaItems();
});
</script>
