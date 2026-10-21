<?php
/**
 * WeaveCMS – Admin Tab: Downloads & Dokumente
 * 
 * Ermöglicht das Bereitstellen von PDFs, Flyern, Pflegeanleitungen
 * und Dokumenten zum direkten Download für Website-Besucher.
 * 
 * @var array $data Gesamtdatenstruktur
 * @var string $csrf_token CSRF-Schutztoken
 */

$downloads = $data['downloads'] ?? [];
$total_downloads = count($downloads);
?>
        <!-- ──────────────── SECTION DOWNLOADS: DATEIEN & DOKUMENTE ──────────────── -->
        <div id="tab-downloads" class="admin-section card" style="padding: 2.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 0.5rem; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons" style="font-size:28px;">folder_zip</span> Downloads &amp; Dokumente
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin:0;">
                        Lade PDFs, Flyer, Formulare oder Anleitungen hoch, die deine Besucher herunterladen können.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">upload_file</span> Neues Dokument
                    </button>
                </div>
            </div>

            <!-- Kennzahlen-Karten -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Dateien Gesamt</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top:4px;"><?php echo $total_downloads; ?></div>
                </div>
            </div>

            <!-- ──────────────── FORMULAR / EDITOR DRAWER ──────────────── -->
            <div id="download-editor-box" style="display:none; background:rgba(255,255,255,0.02); border:1px solid var(--primary); border-radius:16px; padding:2rem; margin-bottom:2.5rem; animation: fadeInTab 0.25s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
                    <h3 id="download-editor-title" style="color:var(--primary); margin:0; display:flex; align-items:center; gap:8px;">
                        <span class="material-icons">upload_file</span> Neues Dokument bereitstellen
                    </h3>
                    <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()" style="padding:4px 10px; font-size:0.8rem;">
                        <span class="material-icons" style="font-size:16px;">close</span> Schließen
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="download-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="download_form_action" value="add_download">
                    <input type="hidden" name="id" id="download_id" value="">

                    <div class="grid-2" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="download_title" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Dokumenttitel *</label>
                            <input type="text" id="download_title" name="title" class="form-control" placeholder="z. B. Kursflyer Herbst 2026 (PDF)" required>
                        </div>
                        <div class="form-group">
                            <label for="download_file" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Datei hochladen * (PDF, ZIP, DOCX)</label>
                            <input type="file" id="download_file" name="download_file" class="form-control">
                            <input type="hidden" id="download_existing_file" name="existing_file" value="">
                            <div id="download_current_file_info" style="display:none; font-size:0.85rem; color:var(--text-secondary); margin-top:6px;"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="download_desc" style="font-weight:700; color:var(--text-primary); margin-bottom:0.4rem; display:block;">Kurzbeschreibung (Optional)</label>
                        <input type="text" id="download_desc" name="description" class="form-control" placeholder="z. B. Alle Termine, Preise und Anmeldebedingungen auf einen Blick.">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDownloadEditor()">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="download-submit-btn">
                            <span class="material-icons" style="font-size:18px;">save</span> Dokument speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- ──────────────── DOWNLOAD-LISTE ──────────────── -->
            <?php if (empty($downloads)): ?>
                <div style="text-align:center; padding: 3rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:14px;">
                    <span class="material-icons" style="font-size:48px; color:var(--text-secondary); margin-bottom:0.5rem; opacity:0.6;">picture_as_pdf</span>
                    <h3 style="margin:0 0 0.5rem 0; color:var(--text-primary);">Noch keine Downloads hinterlegt</h3>
                    <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:1.5rem;">Stelle Flyer, Checklisten oder Werkstattinfos für deine Kunden zum Herunterladen bereit.</p>
                    <button type="button" class="btn btn-primary" onclick="openDownloadEditor()">
                        <span class="material-icons" style="font-size:18px;">upload_file</span> Erstes Dokument hochladen
                    </button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($downloads as $d): ?>
                        <?php 
                            $d_file = $d['file_path'] ?? '';
                            $d_ext = strtoupper(pathinfo($d_file, PATHINFO_EXTENSION) ?: 'DATEI');
                        ?>
                        <div class="card" style="padding:1.25rem 1.5rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                                <div style="width:44px; height:44px; border-radius:10px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <span class="material-icons">picture_as_pdf</span>
                                </div>
                                <div>
                                    <h4 style="margin:0 0 4px 0; font-size:1.05rem; color:var(--text-primary);">
                                        <?php echo htmlspecialchars($d['title'] ?? 'Dokument'); ?>
                                    </h4>
                                    <?php if (!empty($d['description'])): ?>
                                        <div style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:4px;">
                                            <?php echo htmlspecialchars($d['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span style="font-size:0.78rem; font-weight:700; color:var(--text-secondary); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:4px;">
                                        <?php echo $d_ext; ?> &bull; <?php echo htmlspecialchars($d['file_size'] ?? 'Unbekannt'); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <?php if (!empty($d_file)): ?>
                                    <a href="../<?php echo htmlspecialchars($d_file); ?>" target="_blank" download class="btn btn-secondary" style="padding:6px 12px; font-size:0.82rem; text-decoration:none;" title="Herunterladen">
                                        <span class="material-icons" style="font-size:16px;">file_download</span> Öffnen
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary" onclick="editDownload(<?php echo htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8'); ?>)" style="padding:6px 12px; font-size:0.82rem;">
                                    <span class="material-icons" style="font-size:16px;">edit</span> Bearbeiten
                                </button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Möchtest du dieses Dokument wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete_download">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px; color:#ef4444;" title="Dokument löschen">
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
        function openDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">upload_file</span> Neues Dokument bereitstellen';
            document.getElementById('download_form_action').value = 'add_download';
            document.getElementById('download_id').value = '';
            document.getElementById('download_title').value = '';
            document.getElementById('download_desc').value = '';
            document.getElementById('download_existing_file').value = '';
            document.getElementById('download_file').required = true;
            document.getElementById('download_current_file_info').style.display = 'none';
            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Dokument speichern';
            document.getElementById('download_title').focus();
        }

        function closeDownloadEditor() {
            document.getElementById('download-editor-box').style.display = 'none';
        }

        function editDownload(downloadData) {
            document.getElementById('download-editor-box').style.display = 'block';
            document.getElementById('download-editor-title').innerHTML = '<span class="material-icons">edit</span> Dokument bearbeiten';
            document.getElementById('download_form_action').value = 'edit_download';
            document.getElementById('download_id').value = downloadData.id || '';
            document.getElementById('download_title').value = downloadData.title || '';
            document.getElementById('download_desc').value = downloadData.description || '';
            document.getElementById('download_existing_file').value = downloadData.file_path || '';
            document.getElementById('download_file').required = false;
            if (downloadData.file_path) {
                document.getElementById('download_current_file_info').style.display = 'block';
                document.getElementById('download_current_file_info').innerHTML = 'Aktuelle Datei: <code>' + (downloadData.file_path) + '</code> (Neue Datei hochladen zum Ersetzen)';
            } else {
                document.getElementById('download_current_file_info').style.display = 'none';
            }
            document.getElementById('download-submit-btn').innerHTML = '<span class="material-icons" style="font-size:18px;">save</span> Änderungen speichern';
            document.getElementById('download-editor-box').scrollIntoView({ behavior: 'smooth' });
        }
        </script>
